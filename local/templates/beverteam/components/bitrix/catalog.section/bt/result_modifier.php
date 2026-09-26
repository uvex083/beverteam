<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// число товаров нужно в тулбаре над списком — передаём через кеш компонента в component_epilog
$arResult['BT_COUNT'] = count($arResult['ITEMS']);
$cp = $this->getComponent();
if ($cp) {
    $cp->arResultCacheKeys = array_merge($cp->arResultCacheKeys, ['BT_COUNT']);
}
