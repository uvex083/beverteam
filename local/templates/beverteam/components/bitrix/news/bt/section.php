<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// Рубрика журнала /blog/<рубрика>/ — та же лента, что на /blog/, отфильтрованная по разделу; пустая рубрика — 404
$btRubric = (string)($arResult['VARIABLES']['SECTION_CODE_PATH'] ?? $arResult['VARIABLES']['SECTION_CODE'] ?? '');
if (!in_array($btRubric, array_column(bt_blog_rubrics(), 'code'), true)) {
    \Bitrix\Iblock\Component\Tools::process404('', true, true, true, '/404.php');
    return;
}
include __DIR__ . '/news.php';
