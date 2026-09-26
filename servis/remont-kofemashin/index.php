<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
// общая страница ремонта и страница бренда /servis/remont-kofemashin/<код>/ (правило в urlrewrite.php); выключенный бренд — 404
$brand = null;
$code = (string)($_GET['BRAND'] ?? '');
if ($code !== '') {
    foreach (bt_blocks('repair_brands') as $b) {
        $b['code'] === $code and $brand = $b;
    }
    if (!$brand) {
        require $_SERVER['DOCUMENT_ROOT'] . '/404.php';
        die();
    }
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
if ($brand) {
    $seo = (new \Bitrix\Iblock\InheritedProperty\ElementValues(bt_iblock('repair_brands'), $brand['id']))->getValues();
    $APPLICATION->SetPageProperty('title', $seo['ELEMENT_META_TITLE'] ?? '');
    $APPLICATION->SetPageProperty('description', $seo['ELEMENT_META_DESCRIPTION'] ?? '');
    $APPLICATION->SetPageProperty('keywords', $seo['ELEMENT_META_KEYWORDS'] ?? '');
    $APPLICATION->SetTitle($seo['ELEMENT_PAGE_TITLE'] ?? $brand['name']);
    $APPLICATION->AddChainItem($brand['name']);
} else {
    $APPLICATION->SetPageProperty('title', 'Ремонт кофемашин в Екатеринбурге — сервисный центр | BEVERTEAM');
    $APPLICATION->SetPageProperty('description', 'Ремонт и обслуживание автоматических кофемашин в Екатеринбурге: в офисе, кафе и на дому. Авторизованный сервисный центр Jetinno, заявка на вызов инженера онлайн.');
    $APPLICATION->SetPageProperty('keywords', 'ремонт кофемашин Екатеринбург');
    $APPLICATION->SetTitle('Ремонт кофемашин в Екатеринбурге');
}
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/remont-kofemashin.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
