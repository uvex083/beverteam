<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// title, description и H1 — вкладка SEO элемента «Первый экран и конфигуратор» в ИБ «Кофе в офис»
bt_page_seo('sub_head', 'Кофе в офис с доставкой в Екатеринбурге | BEVERTEAM', '');
$top = bt_block('sub_head');
$seo = $top ? (new \Bitrix\Iblock\InheritedProperty\ElementValues(bt_iblock('sub_head'), $top['id']))->getValues() : [];
$APPLICATION->SetPageProperty('keywords', $seo['ELEMENT_META_KEYWORDS'] ?? '');
$APPLICATION->SetTitle(($seo['ELEMENT_PAGE_TITLE'] ?? '') ?: 'Кофе в офис с доставкой в Екатеринбурге');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/kofe-v-ofis.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
