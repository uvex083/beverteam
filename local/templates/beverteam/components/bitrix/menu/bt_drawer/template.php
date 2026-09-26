<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// Мобильное меню: разделы с подпунктами — аккордеоном, остальное — ссылками
$tree = [];
foreach ($arResult as $item) {
    if (($item['PARAMS']['DEPTH_LEVEL'] ?? 1) == 1) {
        $tree[] = $item + ['SUB' => []];
    } elseif ($tree) {
        $tree[array_key_last($tree)]['SUB'][] = $item;
    }
}
foreach ($tree as $n):
    if ($n['SUB']): ?>
    <details class="acc"><summary><?= $n['TEXT'] ?></summary><ul><?php foreach ($n['SUB'] as $s): ?><li><a href="<?= $s['LINK'] ?>"><?= $s['TEXT'] ?></a></li><?php endforeach ?><li><a href="<?= $n['LINK'] ?>" class="link">Все в разделе</a></li></ul></details>
<?php else: ?>
    <a class="drawer__l" href="<?= $n['LINK'] ?>"><?= $n['TEXT'] ?></a>
<?php endif;
endforeach;
