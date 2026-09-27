<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// Рубрика журнала /blog/<рубрика>/ — та же лента, что на /blog/, отфильтрованная по разделу
$btRubric = (string)($arResult['VARIABLES']['SECTION_CODE_PATH'] ?? $arResult['VARIABLES']['SECTION_CODE'] ?? '');
include __DIR__ . '/news.php';
