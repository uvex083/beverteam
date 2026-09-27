<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// Хлебные крошки: «Главная / раздел / … / текущая»; результат шаблона — строка
if (empty($arResult)) {
    return '';
}
$items = $arResult; // первый пункт цепочки — «Главная» из корневого .section.php
$last = count($items) - 1;
$html = '<nav class="crumbs" aria-label="Хлебные крошки">';
foreach ($items as $i => $it) {
    $title = htmlspecialcharsbx($it['TITLE']);
    $html .= $i === 0 ? '<a href="' . htmlspecialcharsbx($it['LINK'] ?: '/') . '">' . $title . '</a>'
        : '<span>' . ($i < $last && $it['LINK'] ? '<a href="' . htmlspecialcharsbx($it['LINK']) . '">' . $title . '</a>' : $title) . '</span>';
}
// BreadcrumbList для поисковиков — из тех же пунктов
$ld = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
foreach ($items as $i => $it) {
    $ld['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $it['TITLE']]
        + ($it['LINK'] ? ['item' => 'https://beverteam.ru' . $it['LINK']] : []);
}
return $html . '</nav><script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
