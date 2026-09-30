<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// черновик новой версии страницы аренды — для сравнения, закрыт от индексации
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
$APPLICATION->SetPageProperty('canonical', 'https://beverteam.ru/arenda-kofemashin/');
$APPLICATION->SetPageProperty('title', 'Аренда кофемашин в Екатеринбурге — 0 ₽ при заказе кофе, от #RENT_FROM# | BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Кофемашины Jetinno в аренду для офиса, кафе и бизнеса в Екатеринбурге и Свердловской области: 0 ₽ в месяц при заказе кофе BOTANICA от #RENT_FREE_KG# кг в месяц или фиксированная аренда от #RENT_FROM#. Доставка, установка и сервис включены.');
$APPLICATION->SetPageProperty('keywords', 'аренда кофемашины, кофемашина в аренду, аренда кофемашины для офиса, аренда кофемашины бесплатно при покупке кофе, аренда кофемашины Екатеринбург, аренда кофемашины Jetinno');
$APPLICATION->SetTitle('Аренда кофемашин в Екатеринбурге');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/arenda-kofemashin-new.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
