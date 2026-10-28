<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// title, description, H1 — вкладка SEO элемента «Первый экран» ИБ «Аренда кофемашин»
bt_page_seo('rent_top', 'Аренда кофемашин в Екатеринбурге | BEVERTEAM', '');
$top = bt_block('rent_top');
$seo = $top ? (new \Bitrix\Iblock\InheritedProperty\ElementValues(bt_iblock('rent_top'), $top['id']))->getValues() : [];
$APPLICATION->SetPageProperty('keywords', $seo['ELEMENT_META_KEYWORDS'] ?? '');
$APPLICATION->SetTitle(($seo['ELEMENT_PAGE_TITLE'] ?? '') ?: 'Аренда кофемашин в Екатеринбурге');
$APPLICATION->AddChainItem('Услуги', '/servis/');
$APPLICATION->AddChainItem('Аренда кофемашин', '/arenda-kofemashin/');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/arenda-kofemashin.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
