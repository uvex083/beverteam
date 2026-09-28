<?php
// Разделы каталога в мобильном меню — из bt_mega_cats(), слайдом под пунктом «Каталог»; аренда — отдельным пунктом первого экрана
$catalog = [];
foreach (bt_mega_cats() as $c) {
    if ($c['h'] === '/arenda-kofemashin/') {
        continue;
    }
    $catalog[] = [$c['t'], $c['h'], [], ['DEPTH_LEVEL' => 2, 'img' => $c['promo']['img'] ?? ''], ''];
    foreach ($c['sub'] as [$name, $url]) {
        $catalog[] = [$name, $url, [], ['DEPTH_LEVEL' => 3], ''];
    }
}
$at = array_search('/catalog/', array_column($aMenuLinks, 1), true);
array_splice($aMenuLinks, $at === false ? 0 : $at + 1, 0, $catalog);
