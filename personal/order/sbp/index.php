<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Оплата по СБП');
$APPLICATION->SetPageProperty('title', 'Оплата по СБП — BEVERTEAM');
$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/order-sbp.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
