<?php
// Корзина для ui.js: POST action=get|set, id — ID товара, q — количество (0 — удалить). Ответ — состояние корзины.
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
    $q = max(0, (int)$req->getPost('q'));
    $item = null;
    foreach ($basket as $bi) {
        if ((int)$bi->getProductId() === $id) {
            $item = $bi;
            break;
        }
    }
    // в корзину — только активные товары каталога (не аренда, не чужие ID)
    $isProduct = $id && CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => bt_iblock('catalog'), 'ACTIVE' => 'Y'], [])
        && \Bitrix\Catalog\ProductTable::getById($id)->fetch();
    if (!$isProduct) {
        $error = 'product';
    } elseif ($q === 0) {
        $item?->delete();
    } elseif ($item) {
        $r = $item->setField('QUANTITY', $q);
        $r->isSuccess() or $error = implode('; ', $r->getErrorMessages());
    } else {
        $item = $basket->createItem('catalog', $id);
        $r = $item->setFields(['QUANTITY' => $q, 'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(), 'LID' => SITE_ID,
            'PRODUCT_PROVIDER_CLASS' => \Bitrix\Catalog\Product\CatalogProvider::class]);
        $r->isSuccess() or $error = implode('; ', $r->getErrorMessages());
    }
    if (!$error) {
        $r = $basket->save();
        $r->isSuccess() or $error = implode('; ', $r->getErrorMessages());
    }
}

echo json_encode(['ok' => $error === '', 'error' => $error] + bt_basket_state($basket), JSON_UNESCAPED_UNICODE);
