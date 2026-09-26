<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Услуги: аренда, продажа и ремонт кофемашин в Екатеринбурге — BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Аренда, продажа и ремонт кофемашин Jetinno в Екатеринбурге. Авторизованный сервисный центр Jetinno, обслуживание в офисе, кафе и на дому.');
$APPLICATION->SetPageProperty('keywords', 'аренда кофемашин, продажа кофемашин, ремонт кофемашин, Екатеринбург');
$APPLICATION->SetTitle('Услуги и сервис');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/servis.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
