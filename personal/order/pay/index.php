<?php
// переход к оплате заказа в Т-Банке: сначала спрашиваем банк, не оплачен ли уже, — иначе новый платёж
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CUser $USER */

use Bitrix\Main\Loader;
use Bitrix\Sale;

Loader::includeModule('sale');
$id = (int)($_GET['id'] ?? 0);
$back = '/personal/order/success/?id=' . $id;
$order = $id ? Sale\Order::load($id) : null;
$own = $order && (in_array($id, (array)($_SESSION['BT_ORDERS'] ?? []), true) || ($USER->IsAuthorized() && (int)$order->getUserId() === (int)$USER->GetID()));
$p = $own && !$order->isCanceled() ? bt_tbank_payment($order) : null;
if ($p && !bt_tbank_sync($p) && ($url = bt_tbank_url($p))) {
    LocalRedirect($url, true);
}
LocalRedirect($order && !$own ? '/personal/' : $back . ($p ? '&pay=fail' : ''));
