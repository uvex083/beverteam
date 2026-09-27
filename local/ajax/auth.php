<?php
// Вход = регистрация по коду из письма. POST action=send (login) | verify (code) | register (name) | logout.
// Код — 4 цифры, в сессии только хэш: 10 минут, 5 попыток. Отправки и ошибки пишутся в журнал событий — по нему лимиты на e-mail и IP.
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\EventLog\Internal\EventLogTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

/** @global CUser $USER */
header('Content-Type: application/json; charset=utf-8');
$req = Context::getCurrent()->getRequest();
$out = function (array $data, int $code = 200) {
    http_response_code($code);
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
};
if (!$req->isPost()) {
    $out(['ok' => false, 'error' => 'method'], 405);
}
if (!check_bitrix_sessid()) {
    $out(['ok' => false, 'error' => 'sessid'], 403);
}

const TTL = 600;
const WAIT = 59;
const TRIES = 5;
$A = $_SESSION['BT_AUTH'] ?? [];
$ip = (string)$req->getRemoteAddress();
$log = fn(string $type, string $email) => CEventLog::Add(['SEVERITY' => 'SECURITY', 'AUDIT_TYPE_ID' => $type, 'MODULE_ID' => 'main',
    'ITEM_ID' => md5($email), 'DESCRIPTION' => preg_replace('/(?<=.).(?=[^@]*@)/u', '*', $email)]);
$count = fn(string $type, array $filter, int $sec) => EventLogTable::getCount(['=AUDIT_TYPE_ID' => $type,
    '>=TIMESTAMP_X' => DateTime::createFromTimestamp(time() - $sec)] + $filter);
$user = fn(int $id) => UserTable::getList(['filter' => ['=ID' => $id], 'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL', 'ACTIVE']])->fetch();
// вход по коду — только для покупателей: администраторы входят через /bitrix/admin/ с паролем
$allowed = fn(array $u) => $u['ACTIVE'] === 'Y' && !in_array(1, array_map('intval', CUser::GetUserGroup((int)$u['ID'])), true);
$login = function (int $id) use ($USER, $out, $user) {
    unset($_SESSION['BT_AUTH']);
    $USER->Authorize($id, true);
    BtOAuth::link($id);
    $u = $user($id);
    $out(['ok' => true, 'user' => ['name' => trim($u['NAME'] . ' ' . $u['LAST_NAME']), 'email' => $u['EMAIL']], 'sessid' => bitrix_sessid()]);
};

switch ((string)$req->getPost('action')) {
    case 'logout':
        unset($_SESSION['BT_AUTH']);
        $USER->Logout();
        $out(['ok' => true, 'sessid' => bitrix_sessid()]);

    case 'send':
        if ($USER->IsAuthorized()) {
            $out(['ok' => false, 'message' => 'Вы уже вошли. Обновите страницу.']);
        }
        $in = trim((string)$req->getPost('login'));
        $phone = '';
        $hint = '';
        if (str_contains($in, '@')) {
            $email = mb_strtolower($in);
            if (!check_email($email, true)) {
                $out(['ok' => false, 'errors' => ['login' => 'Проверьте адрес: нужен формат mail@company.ru']]);
            }
            $phone = $A['phone'] ?? '';
        } else {
            $phone = bt_phone_digits($in);
            if (!$phone) {
                $out(['ok' => false, 'errors' => ['login' => 'Введите e-mail или телефон полностью']]);
            }
            // SMS не подключены: по номеру находим покупателя и шлём код на его e-mail
            $found = bt_user_by_phone($phone);
            if (!$found || !check_email((string)$found['EMAIL'], true)) {
                $_SESSION['BT_AUTH'] = ['phone' => $phone, 'sent' => (int)($A['sent'] ?? 0)];
                $out(['ok' => false, 'need' => 'email', 'message' => 'Этот номер не привязан к аккаунту. Вход по SMS пока недоступен — введите e‑mail, код придёт на почту.']);
            }
            $email = mb_strtolower($found['EMAIL']);
            $hint = mb_substr($email, 0, 1) . '***' . mb_substr($email, mb_strpos($email, '@'));
        }

        // пауза между письмами — и в этой сессии, и для этого e-mail из любой другой
        $last = EventLogTable::getList(['filter' => ['=AUDIT_TYPE_ID' => 'BT_AUTH_SEND', '=ITEM_ID' => md5($email)], 'select' => ['TIMESTAMP_X'],
            'order' => ['ID' => 'DESC'], 'limit' => 1])->fetch();
        $left = max(WAIT - (time() - (int)($A['sent'] ?? 0)), $last ? WAIT - (time() - $last['TIMESTAMP_X']->getTimestamp()) : 0);
        if ($left > 0) {
            $out(['ok' => false, 'wait' => $left, 'message' => 'Код уже отправлен. Повторить можно через ' . $left . ' с.'], 429);
        }
        if ($count('BT_AUTH_SEND', ['=REMOTE_ADDR' => $ip], 3600) >= 10 || $count('BT_AUTH_SEND', ['=ITEM_ID' => md5($email)], 3600) >= 6
            || $count('BT_AUTH_SEND', ['=ITEM_ID' => md5($email)], 86400) >= 20) {
            $out(['ok' => false, 'message' => 'Слишком много запросов кода. Попробуйте через час или позвоните нам.'], 429);
        }

        $found = UserTable::getList(['filter' => ['=EMAIL' => $email], 'select' => ['ID', 'ACTIVE'], 'order' => ['ID' => 'ASC'], 'limit' => 1])->fetch();
        if ($found && !$allowed($found)) {
            $out(['ok' => false, 'errors' => ['login' => 'Для этого аккаунта вход по коду недоступен. Позвоните нам, поможем.']]);
        }
        $code = sprintf('%04d', random_int(0, 9999));
        $salt = bin2hex(random_bytes(8));
        // без копии на скрытый адрес из настроек почты: код видит только владелец ящика
        $sent = CEvent::SendImmediate('BT_AUTH_CODE', SITE_ID, ['EMAIL_TO' => $email, 'CODE' => $code, 'TTL' => TTL / 60], 'N');
        if ($sent !== \Bitrix\Main\Mail\Event::SEND_RESULT_SUCCESS) {
            $out(['ok' => false, 'message' => 'Не получилось отправить письмо с кодом. Попробуйте ещё раз или позвоните нам.'], 500);
        }
        $log('BT_AUTH_SEND', $email);
        $_SESSION['BT_AUTH'] = ['email' => $email, 'uid' => (int)($found['ID'] ?? 0), 'phone' => $phone, 'salt' => $salt,
            'hash' => hash_hmac('sha256', $code, $salt), 'exp' => time() + TTL, 'tries' => 0, 'sent' => time(), 'ok' => false];
        $out(['ok' => true, 'to' => $hint ?: $email, 'wait' => WAIT]);

    case 'verify':
        if (empty($A['hash'])) {
            $out(['ok' => false, 'expired' => true, 'message' => 'Запросите код ещё раз.']);
        }
        if (time() > $A['exp']) {
            unset($_SESSION['BT_AUTH']['hash']);
            $out(['ok' => false, 'expired' => true, 'message' => 'Код устарел — запросите новый.']);
        }
        $code = preg_replace('/\D/', '', (string)$req->getPost('code'));
        if (!hash_equals($A['hash'], hash_hmac('sha256', $code, $A['salt']))) {
            $log('BT_AUTH_FAIL', $A['email']);
            $tries = ++$_SESSION['BT_AUTH']['tries'];
            if ($tries >= TRIES) {
                unset($_SESSION['BT_AUTH']['hash']);
                $out(['ok' => false, 'expired' => true, 'message' => 'Код введён неверно ' . TRIES . ' раз. Запросите новый код.']);
            }
            $out(['ok' => false, 'message' => 'Неверный код. Осталось попыток: ' . (TRIES - $tries) . '.']);
        }
        unset($_SESSION['BT_AUTH']['hash']);
        if ($A['uid'] && ($u = $user($A['uid'])) && $allowed($u)) {
            $login($A['uid']);
        }
        $_SESSION['BT_AUTH']['ok'] = true;
        $out(['ok' => true, 'need' => 'name']);

    case 'register':
        if (empty($A['ok']) || time() > $A['exp'] + TTL) {
            $out(['ok' => false, 'expired' => true, 'message' => 'Подтвердите e-mail кодом ещё раз.']);
        }
        $name = trim(preg_replace('/\s+/u', ' ', (string)$req->getPost('name')));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $out(['ok' => false, 'errors' => ['name' => 'Как к вам обращаться? Минимум 2 символа']]);
        }
        // пока вводили имя, покупатель мог появиться (заказ с этим e-mail)
        $found = UserTable::getList(['filter' => ['=EMAIL' => $A['email']], 'select' => ['ID', 'ACTIVE'], 'order' => ['ID' => 'ASC'], 'limit' => 1])->fetch();
        $id = $found ? ($allowed($found) ? (int)$found['ID'] : 0) : bt_user_create($A['email'], $name, $A['phone'] ?? '');
        if (!is_int($id) || !$id) {
            $out(['ok' => false, 'message' => 'Не получилось создать аккаунт' . (is_string($id) ? ': ' . $id : '') . '. Позвоните нам, поможем.'], 500);
        }
        $login($id);
}
$out(['ok' => false, 'error' => 'action'], 400);
