<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Карта сайта — BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Карта сайта BEVERTEAM: все разделы каталога, услуги, информация для покупателей и личный кабинет.');
$APPLICATION->SetTitle('Карта сайта');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/sitemap.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
