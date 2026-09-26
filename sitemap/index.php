<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Карта сайта');
$APPLICATION->SetPageProperty('description', 'Карта сайта');
$APPLICATION->SetPageProperty('keywords', 'Карта сайта');
$APPLICATION->SetTitle('Карта сайта');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/sitemap.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
