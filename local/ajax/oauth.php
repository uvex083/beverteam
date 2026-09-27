<?php
// Вход через сервисы. ?go=<сервис>&back=<путь> — уводим к сервису; сюда же возвращают T-Bank, Сбер и Альфа (code, state).
// Яндекс и VK возвращают на штатные /bitrix/tools/oauth/yandex.php и vkontakte.php модуля «Социальные сервисы».
define('NOT_CHECK_PERMISSIONS', true);
define('STOP_STATISTICS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/socialservices/include/state_processing.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Socialservices\OAuth\StateService;

/** @global CUser $USER */
$fail = function (string $msg, string $back = '/') {
    $_SESSION['BT_OAUTH_ERR'] = $msg;
    LocalRedirect($back . (str_contains($back, '?') ? '&' : '?') . 'auth_service_error=1');
};
if (!CModule::IncludeModule('socialservices')) {
    LocalRedirect('/');
}

$go = (string)($_GET['go'] ?? '');
if ($go !== '') {
    $back = (string)($_GET['back'] ?? '/');
    if (!preg_match('~^/(?![/\\\\])~', $back)) {
        $back = '/';
    }
    if ($USER->IsAuthorized()) {
        LocalRedirect($back);
    }
    if (!in_array($go, array_column(BtOAuth::buttons(), 'id'), true)) {
        $fail('Вход через этот сервис сейчас недоступен. Получите код на почту.', $back);
    }
    $class = BtOAuth::IDP[$go][0];
    $s = new $class();
    LocalRedirect($go === 'YandexOAuth' ? $s->getUrl('page', null, ['BACKURL' => $back]) : $s->getUrl(['BACKURL' => $back]), true);
}

// возврат от банка: сервис берём из state, выданного этой сессии; чужой или устаревший state — отказ
$payload = StateService::getInstance()->getPayload((string)($_GET['state'] ?? ''));
$id = (string)($payload['service'] ?? '');
if (!isset(BtOAuth::IDP[$id]) || !is_subclass_of(BtOAuth::IDP[$id][0], 'BtOAuthBank')) {
    $fail('Сессия входа устарела или ссылка чужая. Начните вход заново.');
}
(new CSocServAuthManager())->Authorize($id);
$fail('Вход через этот сервис сейчас недоступен. Получите код на почту.');
