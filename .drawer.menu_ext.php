<?php
// Разделы каталога в мобильном меню — из bt_mega_cats(), ставим перед страницами сайта
$catalog = [];
foreach (bt_mega_cats() as $c) {
    $catalog[] = [$c['t'], $c['h'], [], ['DEPTH_LEVEL' => 1], ''];
    foreach ($c['sub'] as [$name, $url]) {
        $catalog[] = [$name, $url, [], ['DEPTH_LEVEL' => 2], ''];
    }
}
$aMenuLinks = array_merge($catalog, $aMenuLinks);
