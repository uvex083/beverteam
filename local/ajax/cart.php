<?php
// Корзина для ui.js: POST action=get|set, id — ID товара, kg — фасовка кофе на развес, q — штук (0 — удалить). Ответ — состояние корзины.
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Sale;

header('Content-Type: application/json; charset=utf-8');
$req = Context::getCurrent()->getRequest();
if (!$req->isPost() || !check_bitrix_sessid()) {
    http_response_code(403);
    die(json_encode(['ok' => false, 'error' => 'sessid']));
}
Loader::includeModule('sale');
Loader::includeModule('catalog');

$basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID);
$error = '';

if ($req->getPost('action') === 'set') {
    $id = (int)$req->getPost('id');
    // в корзину — только активные товары каталога (не аренда, не чужие ID)
    $isProduct = $id && CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => bt_iblock('catalog'), 'ACTIVE' => 'Y'], []);
    $error = $isProduct ? bt_basket_put($basket, $id, (int)$req->getPost('kg'), max(0, (int)$req->getPost('q'))) : 'product';
    if (!$error) {
        $r = $basket->save();
        $r->isSuccess() or $error = implode('; ', $r->getErrorMessages());
    }
}

echo json_encode(['ok' => $error === '', 'error' => $error] + bt_basket_state($basket), JSON_UNESCAPED_UNICODE);
