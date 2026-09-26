<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Мои заказы');
$APPLICATION->SetPageProperty('title', 'Мои заказы — BEVERTEAM');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/personal-orders.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
