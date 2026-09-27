<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Журнал о кофе, чае и кофемашинах — статьи и новости | BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Журнал BEVERTEAM: как выбрать зерно, настроить кофемашину и ухаживать за ней. Советы нашей обжарки и сервисного центра, новости магазина.');
$APPLICATION->SetTitle('Журнал');
$APPLICATION->IncludeComponent('bitrix:news', 'bt', [
    'IBLOCK_TYPE' => 'news',
    'IBLOCK_ID' => bt_iblock('journal'),
    'NEWS_COUNT' => 12,
    'SORT_BY1' => 'ACTIVE_FROM', 'SORT_ORDER1' => 'DESC', 'SORT_BY2' => 'SORT', 'SORT_ORDER2' => 'ASC',
    'CHECK_DATES' => 'Y',
    'SEF_MODE' => 'Y',
    'SEF_FOLDER' => '/blog/',
    'SEF_URL_TEMPLATES' => ['news' => '', 'section' => '#SECTION_CODE#/', 'detail' => '#ELEMENT_CODE#/'],
    'CACHE_TYPE' => 'A', 'CACHE_TIME' => 36000000, 'CACHE_FILTER' => 'Y', 'CACHE_GROUPS' => 'N',
    'SET_TITLE' => 'Y', 'SET_BROWSER_TITLE' => 'Y', 'SET_META_DESCRIPTION' => 'Y', 'SET_META_KEYWORDS' => 'Y',
    'BROWSER_TITLE' => '-', 'META_KEYWORDS' => '-', 'META_DESCRIPTION' => '-',
    'SET_STATUS_404' => 'Y', 'SHOW_404' => 'Y', 'FILE_404' => '/404.php', 'MESSAGE_404' => '',
    'INCLUDE_IBLOCK_INTO_CHAIN' => 'N', 'ADD_SECTIONS_CHAIN' => 'Y', 'ADD_ELEMENT_CHAIN' => 'Y',
    'USE_SEARCH' => 'N', 'USE_RSS' => 'N', 'USE_RATING' => 'N', 'USE_CATEGORIES' => 'N', 'USE_REVIEW' => 'N', 'USE_FILTER' => 'N',
    'USE_PERMISSIONS' => 'N', 'DISPLAY_TOP_PAGER' => 'N', 'DISPLAY_BOTTOM_PAGER' => 'Y', 'PAGER_SHOW_ALWAYS' => 'N',
    'LIST_PROPERTY_CODE' => ['KIND', 'RUBRIC', 'READ_TIME'], 'DETAIL_PROPERTY_CODE' => ['KIND', 'RUBRIC', 'READ_TIME'],
    'LIST_FIELD_CODE' => ['PREVIEW_PICTURE'], 'DETAIL_FIELD_CODE' => ['PREVIEW_PICTURE', 'DETAIL_PICTURE'],
    'STRICT_SECTION_CHECK' => 'N',
], false);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
