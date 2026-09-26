<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Адреса доставки');
$APPLICATION->SetPageProperty('title', 'Адреса доставки — BEVERTEAM');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/personal-addresses.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
