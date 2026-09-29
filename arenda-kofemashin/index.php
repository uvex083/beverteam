<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// title и description — вкладка SEO первого блока страницы в админке; метки #RENT_…# — по моделям аренды
bt_page_seo('rent_top', 'Аренда кофемашины для офиса и бизнеса | Beverteam',
    'Аренда кофемашины в офис в Екатеринбурге от Beverteam. Автоматические модели #RENT_MODELS# в наличии. Цены от #RENT_FROM#');
$APPLICATION->SetPageProperty('keywords', 'Аренда кофемашин');
$APPLICATION->SetTitle('Аренда кофемашин');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/arenda-kofemashin.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
