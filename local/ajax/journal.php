<?php
// Подписка на журнал по ссылкам из писем: confirm=<ключ> — подтвердить адрес, unsub=<ключ> — отписаться.
// POST с unsub в адресе — отписка в один клик из почтового клиента (заголовок List-Unsubscribe-Post), отвечаем без редиректа.
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
$req = Context::getCurrent()->getRequest();
$ib = bt_iblock('journal_subs');
$find = fn(string $t) => $ib && preg_match('/^[a-f0-9]{32}$/', $t)
    ? CIBlockElement::GetList([], ['IBLOCK_ID' => $ib, '=PROPERTY_TOKEN' => $t], false, false, ['ID'])->Fetch() : false;

if (($t = (string)$req->getQuery('unsub')) !== '') {
    ($el = $find($t)) and (new CIBlockElement())->Update($el['ID'], ['ACTIVE' => 'N']);
    if ($req->isPost()) {
        die('OK');
    }
    LocalRedirect('/blog/?unsub=1#jsub');
}
if (($t = (string)$req->getQuery('confirm')) !== '') {
    $el = $find($t);
    $el and (new CIBlockElement())->Update($el['ID'], ['ACTIVE' => 'Y']);
    LocalRedirect('/blog/?sub=' . ($el ? 'ok' : 'bad') . '#jsub');
}
LocalRedirect('/blog/');
