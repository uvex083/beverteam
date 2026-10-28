<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// картинка для Open Graph — через кэш компонента в component_epilog
$arResult['BT_OG_IMAGE'] = $arResult['DETAIL_PICTURE']['SRC'] ?? ($arResult['PREVIEW_PICTURE']['SRC'] ?? '');
$cp = $this->getComponent();
if ($cp) {
    $cp->arResultCacheKeys = array_merge($cp->arResultCacheKeys, ['BT_OG_IMAGE']);
}
