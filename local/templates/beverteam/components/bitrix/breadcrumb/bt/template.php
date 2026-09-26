<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// Хлебные крошки: «Главная / раздел / … / текущая»; результат шаблона — строка
if (empty($arResult)) {
    return '';
}
$items = array_merge([['TITLE' => 'Главная', 'LINK' => '/']], $arResult);
$last = count($items) - 1;
$html = '<nav class="crumbs" aria-label="Хлебные крошки">';
foreach ($items as $i => $it) {
    $title = htmlspecialcharsbx($it['TITLE']);
    $html .= $i === 0 ? '<a href="/">' . $title . '</a>'
        : '<span>' . ($i < $last && $it['LINK'] ? '<a href="' . htmlspecialcharsbx($it['LINK']) . '">' . $title . '</a>' : $title) . '</span>';
}
return $html . '</nav>';
