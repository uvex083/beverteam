<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// для тулбара и Open Graph — через кэш компонента в component_epilog
$arResult['BT_COUNT'] = count($arResult['ITEMS']);
$first = $arResult['ITEMS'] ? bt_product((string)$arResult['ITEMS'][0]['ID']) : null;
$arResult['BT_OG_IMAGE'] = $first['img'] ?? '';
$cp = $this->getComponent();
if ($cp) {
    $cp->arResultCacheKeys = array_merge($cp->arResultCacheKeys, ['BT_COUNT', 'BT_OG_IMAGE']);
}
