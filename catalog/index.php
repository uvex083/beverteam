<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// мета со старого сайта beverteam.ru/catalog (настраивал сеошник); у разделов и товаров — SEO-шаблоны инфоблока
$APPLICATION->SetTitle('Магазин чая и кофе');
$APPLICATION->SetPageProperty('title', 'Интернет-магазин чая и кофе в Екатеринбурге | Beverteam');
$APPLICATION->SetPageProperty('description', 'Купить чай и кофе в интернет-магазине Beverteam в Екатеринбурге. В каталоге — кофе, разные виды чая, кофемашины JETINNO и аксессуары для дома и бизнеса');
$APPLICATION->SetPageProperty('keywords', 'Интернет-магазин чая и кофе');
$APPLICATION->IncludeComponent('bitrix:catalog', 'bt', [
    'IBLOCK_TYPE' => 'catalog',
    'IBLOCK_ID' => bt_iblock('catalog'),
    'SEF_MODE' => 'Y',
    'SEF_FOLDER' => '/catalog/',
    'SEF_URL_TEMPLATES' => [
        'sections' => '',
        'section' => '#SECTION_CODE_PATH#/',
        'smart_filter' => '#SECTION_CODE_PATH#/filter/#SMART_FILTER_PATH#/apply/',
        'element' => 'product/#ELEMENT_CODE#/',
        'compare' => 'compare/',
    ],
    'PRICE_CODE' => ['BASE'],
    'USE_PRICE_COUNT' => 'Y',
    'SHOW_PRICE_COUNT' => 1,
    'PRICE_VAT_INCLUDE' => 'Y',
    'CONVERT_CURRENCY' => 'N',
    'USE_FILTER' => 'Y',
    'FILTER_NAME' => 'btFilter',
    'PAGE_ELEMENT_COUNT' => 200,
    'INCLUDE_SUBSECTIONS' => 'Y',
    'SHOW_ALL_WO_SECTION' => 'Y',
    'HIDE_NOT_AVAILABLE' => 'N',
    'SET_TITLE' => 'Y',
    'SET_STATUS_404' => 'Y',
    'SHOW_404' => 'Y',
    'FILE_404' => '',
    'ADD_SECTIONS_CHAIN' => 'Y',
    'ADD_ELEMENT_CHAIN' => 'Y',
    'SET_LAST_MODIFIED' => 'Y',
    'CACHE_TYPE' => 'A',
    'CACHE_TIME' => 36000000,
    'CACHE_FILTER' => 'Y',
    'CACHE_GROUPS' => 'N',
    'DETAIL_STRICT_SECTION_CHECK' => 'N',
    'SECTION_ID_VARIABLE' => 'SECTION_ID',
    'ACTION_VARIABLE' => 'action',
    'PRODUCT_ID_VARIABLE' => 'id',
    'USE_COMPARE' => 'N',
    'USE_REVIEW' => 'N',
    'USE_STORE' => 'N',
], false);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
