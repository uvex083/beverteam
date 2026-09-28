<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @global CMain $APPLICATION
 */
// обёртка и крошки здесь, а не в шаблоне товара: ShowViewContent внутри кешируемого шаблона теряет вывод
?><div class="wrap"><?php $APPLICATION->ShowViewContent('bt_crumbs') ?><?php
// ?bb=2 — новый блок покупки (шаблон bt2) для сравнения с текущим
$APPLICATION->IncludeComponent('bitrix:catalog.element', ($_GET['bb'] ?? '') === '2' ? 'bt2' : 'bt', [
    'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID'],
    'ELEMENT_CODE' => $arResult['VARIABLES']['ELEMENT_CODE'], 'SECTION_CODE' => '',
    'PROPERTY_CODE' => [], 'PRICE_CODE' => $arParams['PRICE_CODE'], 'USE_PRICE_COUNT' => 'Y', 'SHOW_PRICE_COUNT' => 1,
    'SET_TITLE' => 'Y', 'SET_BROWSER_TITLE' => 'Y', 'SET_META_DESCRIPTION' => 'Y', 'SET_CANONICAL_URL' => 'Y',
    'ADD_SECTIONS_CHAIN' => 'Y', 'ADD_ELEMENT_CHAIN' => 'Y', 'SET_STATUS_404' => 'Y', 'SHOW_404' => 'Y', 'SET_LAST_MODIFIED' => 'Y',
    'CACHE_TYPE' => $arParams['CACHE_TYPE'], 'CACHE_TIME' => $arParams['CACHE_TIME'], 'CACHE_GROUPS' => 'N',
    'SECTION_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['section'],
    'DETAIL_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['element'],
    'COMPATIBLE_MODE' => 'N', 'HIDE_NOT_AVAILABLE' => 'N', 'CHECK_SECTION_ID_VARIABLE' => 'N',
], $component, ['HIDE_ICONS' => 'Y']);
?></div><?php
// крошки после компонента — в цепочке уже есть разделы и товар
$APPLICATION->AddViewContent('bt_crumbs', $APPLICATION->GetNavChain(false, 0, SITE_TEMPLATE_PATH . '/components/bitrix/breadcrumb/bt/template.php', true, false));
