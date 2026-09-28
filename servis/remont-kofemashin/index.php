<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
// бывшие страницы брендов /servis/remont-kofemashin/<код>/ (правило в urlrewrite.php) — на общую страницу ремонта
if ((string)($_GET['BRAND'] ?? '') !== '') {
    LocalRedirect('/servis/remont-kofemashin/', false, '301 Moved permanently');
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetPageProperty('title', 'Ремонт кофемашин в Екатеринбурге — сервисный центр, выезд инженера | BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Ремонт и обслуживание кофемашин всех марок в Екатеринбурге и Свердловской области: домашние, офисные, профессиональные и вендинговые. Выезд инженера, диагностика, гарантия, договор для юрлиц. Авторизованный сервисный центр Jetinno.');
$APPLICATION->SetPageProperty('keywords', 'ремонт кофемашин Екатеринбург, ремонт кофемашин, сервис кофемашин, обслуживание кофемашин, ремонт кофемашин Jetinno');
$APPLICATION->SetTitle('Ремонт кофемашин в Екатеринбурге');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/remont-kofemashin.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
