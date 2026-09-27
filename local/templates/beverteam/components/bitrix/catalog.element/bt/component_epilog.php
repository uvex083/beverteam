<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Open Graph товара: тип, фото, цена
$og = $arResult['BT_OG'] ?? [];
$APPLICATION->SetPageProperty('og_type', 'product');
if (!empty($og['image'])) {
    $APPLICATION->SetPageProperty('og_image', $og['image']);
}
if (!empty($og['price'])) {
    $APPLICATION->SetPageProperty('og_extra', '<meta property="product:price:amount" content="' . (float)$og['price'] . '"><meta property="product:price:currency" content="RUB">');
}
