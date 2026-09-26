<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Аренда кофемашины для офиса и бизнеса | Beverteam');
$APPLICATION->SetPageProperty('description', 'Аренда кофемашины в офис в Екатеринбурге от Beverteam. Автоматические модели Jetinno JL05, JL15 и JL36 в наличии. Цены от 3 500 ₽');
$APPLICATION->SetPageProperty('keywords', 'Аренда кофемашин');
$APPLICATION->SetTitle('Аренда кофемашин');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/arenda-kofemashin.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
