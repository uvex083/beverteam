<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// title, description и H1 — вкладка SEO элемента «Первый экран» в ИБ «Наши клиенты»
bt_page_seo('clients_head', 'Наши клиенты — кофемашины Jetinno в офисах, кафе и бизнес-центрах Екатеринбурга | BEVERTEAM', '');
$top = bt_block('clients_head');
$seo = $top ? (new \Bitrix\Iblock\InheritedProperty\ElementValues(bt_iblock('clients_head'), $top['id']))->getValues() : [];
$APPLICATION->SetPageProperty('keywords', $seo['ELEMENT_META_KEYWORDS'] ?? '');
$APPLICATION->SetTitle(($seo['ELEMENT_PAGE_TITLE'] ?? '') ?: 'Наши клиенты');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/nashi-klienty.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
