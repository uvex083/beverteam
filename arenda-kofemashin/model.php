<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
// модель в аренду /arenda-kofemashin/<код>/ (urlrewrite.php); выключенная или неизвестная — 404
$rentModel = bt_rent_by_code((string)($_GET['CODE'] ?? ''));
if (!$rentModel) {
    require $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    return;
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$n = 'Аренда кофемашины ' . $rentModel['model'];
// SEO — только заданное у самой модели (шаблоны ИБ остались от старого импорта), иначе из данных модели
$rentId = (int)substr($rentModel['id'], 1);
$own = array_filter((new \Bitrix\Iblock\InheritedProperty\ElementTemplates(bt_iblock('rent'), $rentId))->findTemplates(), fn($t) => ($t['INHERITED'] ?? 'Y') === 'N');
$seo = array_intersect_key((new \Bitrix\Iblock\InheritedProperty\ElementValues(bt_iblock('rent'), $rentId))->getValues(), $own);
$free = $rentModel['kg'] ? '0 ₽ при заказе кофе от ' . $rentModel['kg'] . ' кг в месяц или ' : '';
$APPLICATION->SetPageProperty('title', ($seo['ELEMENT_META_TITLE'] ?? '') ?: $n . ' в Екатеринбурге — от ' . bt_fmt($rentModel['price']) . '/мес | BEVERTEAM');
$APPLICATION->SetPageProperty('description', ($seo['ELEMENT_META_DESCRIPTION'] ?? '') ?: $n . ' для ' . mb_strtolower($rentModel['audience']) . ($rentModel['cups'] ? ', до ' . $rentModel['cups'] . ' чашек в день' : '')
    . ': ' . $free . bt_fmt($rentModel['price']) . ' в месяц. Доставка, установка и сервис включены. Екатеринбург и Свердловская область.');
$APPLICATION->SetTitle(($seo['ELEMENT_PAGE_TITLE'] ?? '') ?: $n);
$APPLICATION->AddChainItem('Услуги', '/servis/');
$APPLICATION->AddChainItem('Аренда кофемашин', '/arenda-kofemashin/');
$APPLICATION->AddChainItem($rentModel['model'], bt_rent_url($rentModel));
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/arenda-model.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
