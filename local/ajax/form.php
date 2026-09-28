<?php
// Заявки с сайта: POST form=lead|contact|write|subscribe + поля. Сначала запись в ИБ form_requests, потом письмо BT_FORM_REQUEST.
// Ответ: {ok: true} или {ok: false, errors: {поле: текст}}
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;

header('Content-Type: application/json; charset=utf-8');
$req = Context::getCurrent()->getRequest();
$out = function (int $code, array $data) {
    http_response_code($code);
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
};
if (!$req->isPost()) {
    $out(405, ['ok' => false, 'error' => 'method']);
}
if (!check_bitrix_sessid()) {
    $out(403, ['ok' => false, 'error' => 'sessid']);
}
Loader::includeModule('iblock');

// обязательные поля по типу формы; остальные — по желанию
$rules = [
    'lead' => ['name', 'phone'],
    'contact' => ['name', 'phone'],
    'write' => ['name', 'phone', 'email'],
    'subscribe' => ['email'],
];
$form = (string)$req->getPost('form');
if (!isset($rules[$form])) {
    $out(400, ['ok' => false, 'error' => 'form']);
}
$v = fn(string $k, int $max) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$req->getPost($k))), 0, $max);
$d = [
    'name' => $v('name', 100), 'phone' => $v('phone', 30), 'email' => $v('email', 100),
    'topic' => $v('topic', 150) ?: 'Заявка с сайта', 'page' => preg_match('~^/[^\s]*$~', (string)$req->getPost('page')) ? mb_substr((string)$req->getPost('page'), 0, 255) : '',
    'message' => mb_substr(trim((string)$req->getPost('message')), 0, 2000),
];
// товар («Купить в один клик»), модель кофемашины (ремонт, аренда) — первой строкой сообщения
if ($product = $v('product', 255)) {
    $d['message'] = 'Товар: ' . $product . ($d['message'] !== '' ? "\n" . $d['message'] : '');
}
if ($model = $v('model', 150)) {
    $d['message'] = 'Модель: ' . $model . ($d['message'] !== '' ? "\n" . $d['message'] : '');
}

// ловушка для ботов: поле скрыто от людей — делаем вид, что всё хорошо
if ((string)$req->getPost('website') !== '') {
    $out(200, ['ok' => true]);
}

$errors = [];
foreach ($rules[$form] as $k) {
    if ($d[$k] === '') {
        $errors[$k] = 'Это поле нужно заполнить';
    }
}
if ($d['name'] !== '' && mb_strlen($d['name']) < 2) {
    $errors['name'] = 'Как к вам обращаться? Минимум 2 символа';
}
$digits = preg_replace('/\D/', '', $d['phone']);
if ($d['phone'] !== '' && !(strlen($digits) === 11 && in_array($digits[0], ['7', '8'], true))) {
    $errors['phone'] = 'Введите номер полностью: +7 и 10 цифр';
}
if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Проверьте адрес: нужен формат mail@company.ru';
}
if ($req->getPost('agree') !== 'Y') {
    $errors['agree'] = 'Нужно согласие с условиями';
}
if ($errors) {
    $out(200, ['ok' => false, 'errors' => $errors]);
}

// частота: не чаще раза в 15 секунд из сессии и не больше 5 заявок за 10 минут с одного IP
$ip = (string)$req->getRemoteAddress();
$ibId = bt_iblock('form_requests');
if (time() - (int)($_SESSION['BT_FORM_LAST'] ?? 0) < 15) {
    $out(429, ['ok' => false, 'error' => 'rate', 'message' => 'Заявка уже отправлена. Повторить можно через несколько секунд.']);
}
$recent = (int)CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=PROPERTY_IP' => $ip,
    '>=DATE_CREATE' => ConvertTimeStamp(time() - 600, 'FULL')], []);
if ($recent >= 5) {
    $out(429, ['ok' => false, 'error' => 'rate', 'message' => 'Слишком много заявок подряд. Позвоните нам или попробуйте позже.']);
}

if ($d['phone'] !== '') {
    $d['phone'] = '+7 ' . substr($digits, 1, 3) . ' ' . substr($digits, 4, 3) . '-' . substr($digits, 7, 2) . '-' . substr($digits, 9, 2);
}
$el = new CIBlockElement();
$id = (int)$el->Add([
    'IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y', 'ACTIVE_FROM' => ConvertTimeStamp(time(), 'FULL'),
    'NAME' => $d['topic'] . ' — ' . ($d['name'] ?: $d['email']),
    'PROPERTY_VALUES' => ['CLIENT_NAME' => $d['name'], 'PHONE' => $d['phone'], 'EMAIL' => $d['email'], 'TOPIC' => $d['topic'],
        'MESSAGE' => $d['message'], 'PAGE' => $d['page'], 'IP' => $ip, 'USER_ID' => $USER->IsAuthorized() ? (int)$USER->GetID() : ''],
]);
if (!$id) {
    $out(500, ['ok' => false, 'error' => 'save']);
}
$_SESSION['BT_FORM_LAST'] = time();

$host = (\CMain::IsHTTPS() ? 'https://' : 'http://') . $req->getHttpHost();
CEvent::Send('BT_FORM_REQUEST', SITE_ID, [
    'EMAIL_TO' => Option::get('sale', 'order_email') ?: Option::get('main', 'email_from'),
    'TOPIC' => $d['topic'], 'CLIENT_NAME' => $d['name'] ?: '—', 'PHONE' => $d['phone'] ?: '—', 'EMAIL' => $d['email'] ?: '—',
    'MESSAGE' => $d['message'] ?: '—', 'PAGE' => $d['page'] ? $host . $d['page'] : '—',
    'ADMIN_URL' => $host . '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $ibId . '&type=forms&ID=' . $id . '&lang=ru',
]);

$out(200, ['ok' => true]);
