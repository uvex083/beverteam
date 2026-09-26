<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
/** @global CUser $USER */
// заказ /personal/orders/<ID>/ (правило в urlrewrite.php): гостю — вход, чужой или несуществующий заказ — 404
\Bitrix\Main\Loader::includeModule('sale');
$order = null;
if ($USER->IsAuthorized()) {
    $id = (int)($_GET['ID'] ?? 0);
    $order = $id > 0 ? \Bitrix\Sale\Order::load($id) : null;
    if (!$order || (int)$order->getUserId() !== (int)$USER->GetID() || $order->getSiteId() !== SITE_ID) {
        require $_SERVER['DOCUMENT_ROOT'] . '/404.php';
        die();
    }
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$title = $order ? 'Заказ № ' . $order->getField('ACCOUNT_NUMBER') : 'Заказ';
$APPLICATION->SetTitle($title);
$APPLICATION->SetPageProperty('title', $title . ' — BEVERTEAM');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/personal-order.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
