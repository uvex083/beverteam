<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('og_type', 'article');
if (!empty($arResult['BT_OG_IMAGE'])) {
    $APPLICATION->SetPageProperty('og_image', $arResult['BT_OG_IMAGE']);
}
