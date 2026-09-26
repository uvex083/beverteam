<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Кофе по подписке в Екатеринбурге — кофемашина в аренду бесплатно | BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Кофе по подписке для офиса и кафе: зерно BOTANICA по графику и кофемашина Jetinno в аренду бесплатно при заказе от 3 кг кофе в месяц. Екатеринбург.');
$APPLICATION->SetPageProperty('keywords', 'кофе по подписке, кофе для офиса, аренда кофемашины бесплатно');
$APPLICATION->SetTitle('Кофе по подписке. Кофемашина — бесплатно');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/podpiska.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
