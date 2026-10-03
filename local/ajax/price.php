<?php
// Подписка на изменение цен прайс-листа: POST action=sub (email, agree, sessid) → {ok}; GET unsub=<ключ> — отписка по ссылке из письма
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
$req = Context::getCurrent()->getRequest();
$ib = bt_iblock('price_subs');

$unsub = (string)$req->getQuery('unsub');
if ($unsub !== '') {
    if ($ib && preg_match('/^[a-f0-9]{32}$/', $unsub)
        && ($el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ib, '=PROPERTY_TOKEN' => $unsub], false, false, ['ID'])->Fetch())) {
        (new CIBlockElement())->Update($el['ID'], ['ACTIVE' => 'N']);
    }
    LocalRedirect('/price/?unsub=1');
}

header('Content-Type: application/json; charset=utf-8');
$out = function (int $code, array $data) {
    http_response_code($code);
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
};
if (!$req->isPost() || !check_bitrix_sessid() || !$ib) {
    $out(403, ['ok' => false, 'error' => 'sessid']);
}
if ((string)$req->getPost('website') !== '') {
    $out(200, ['ok' => true]);
}
$email = mb_strtolower(trim((string)$req->getPost('email')));
$err = [];
if (!check_email($email, true)) {
    $err['email'] = $email === '' ? 'Это поле нужно заполнить' : 'Проверьте адрес: нужен формат mail@company.ru';
}
if ($req->getPost('agree') !== 'Y') {
    $err['agree'] = 'Нужно согласие';
}
if ($err) {
    $out(200, ['ok' => false, 'errors' => $err]);
}
$el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ib, '=NAME' => $email], false, false, ['ID'])->Fetch();
$o = new CIBlockElement();
if ($el) {
    $o->Update($el['ID'], ['ACTIVE' => 'Y']);
} else {
    $id = $o->Add(['IBLOCK_ID' => $ib, 'NAME' => $email, 'ACTIVE' => 'Y', 'PROPERTY_VALUES' => ['TOKEN' => md5(random_bytes(16))]]);
    $id or $out(200, ['ok' => false, 'error' => 'save']);
}
$out(200, ['ok' => true]);
