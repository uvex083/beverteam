<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Оплата через SberPay');
$APPLICATION->SetPageProperty('title', 'Оплата через SberPay — BEVERTEAM');
$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/order-sberpay.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
