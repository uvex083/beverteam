<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// черновик новой версии страницы ремонта — для сравнения, закрыт от индексации
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
$APPLICATION->SetPageProperty('canonical', 'https://beverteam.ru/servis/remont-kofemashin/');
$APPLICATION->SetPageProperty('title', 'Ремонт кофемашин Jetinno в Екатеринбурге — авторизованный сервисный центр | BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Ремонт и обслуживание кофемашин Jetinno в Екатеринбурге и Свердловской области: выезд инженера, диагностика, оригинальные запчасти, договор для юрлиц. Авторизованный сервисный центр Jetinno с 2010 года.');
$APPLICATION->SetPageProperty('keywords', 'ремонт кофемашин Екатеринбург, ремонт кофемашин Jetinno, сервисный центр Jetinno, обслуживание кофемашин');
$APPLICATION->SetTitle('Ремонт кофемашин Jetinno в Екатеринбурге');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/remont-kofemashin-new.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
