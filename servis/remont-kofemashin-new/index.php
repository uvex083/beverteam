<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// черновик новой версии страницы ремонта — для сравнения, закрыт от индексации
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
$APPLICATION->SetPageProperty('canonical', 'https://beverteam.ru/servis/remont-kofemashin/');
$APPLICATION->SetPageProperty('title', 'Ремонт кофемашин в Екатеринбурге — сервисный центр, выезд инженера | BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Ремонт и обслуживание кофемашин всех марок в Екатеринбурге и Свердловской области: домашние, офисные, профессиональные и вендинговые. Выезд инженера, диагностика, гарантия, договор для юрлиц. Авторизованный сервисный центр Jetinno.');
$APPLICATION->SetPageProperty('keywords', 'ремонт кофемашин Екатеринбург, ремонт кофемашин, сервис кофемашин, обслуживание кофемашин, ремонт кофемашин Jetinno');
$APPLICATION->SetTitle('Ремонт кофемашин в Екатеринбурге');
$APPLICATION->AddChainItem('Ремонт кофемашин');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/remont-kofemashin-new.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
