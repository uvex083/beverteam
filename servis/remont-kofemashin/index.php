<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
// бывшие страницы брендов /servis/remont-kofemashin/<код>/ (правило в urlrewrite.php) — на общую страницу ремонта
if ((string)($_GET['BRAND'] ?? '') !== '') {
    LocalRedirect('/servis/remont-kofemashin/', false, '301 Moved permanently');
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
// title, description и H1 — вкладка SEO элемента «Первый экран» в ИБ «Ремонт кофемашин»
$top = bt_block('repair_top');
$seo = $top ? (new \Bitrix\Iblock\InheritedProperty\ElementValues(bt_iblock('repair_top'), $top['id']))->getValues() : [];
$APPLICATION->SetPageProperty('title', ($seo['ELEMENT_META_TITLE'] ?? '') ?: 'Ремонт кофемашин в Екатеринбурге | BEVERTEAM');
$APPLICATION->SetPageProperty('description', $seo['ELEMENT_META_DESCRIPTION'] ?? '');
$APPLICATION->SetPageProperty('keywords', $seo['ELEMENT_META_KEYWORDS'] ?? '');
$APPLICATION->SetTitle(($seo['ELEMENT_PAGE_TITLE'] ?? '') ?: 'Ремонт кофемашин в Екатеринбурге');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/remont-kofemashin.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
