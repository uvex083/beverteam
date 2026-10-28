<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// пункт без ссылки — только заголовок выпадающего списка
$tree = [];
foreach ($arResult as $item) {
    if (($item['PARAMS']['DEPTH_LEVEL'] ?? 1) == 1) {
        $tree[] = $item + ['SUB' => []];
    } elseif ($tree) {
        $tree[array_key_last($tree)]['SUB'][] = $item;
    }
}
foreach ($tree as $n):
    $link = $n['LINK'] !== '#' ? $n['LINK'] : ''; ?>
  <div class="nav-i<?= $n['SUB'] ? '' : ' nav-i--plain' ?><?= !empty($n['PARAMS']['dot']) ? ' nav-i--dot' : '' ?>">
    <a href="<?= $link ?: '#' ?>"<?= $link ? '' : ' onclick="return false"' ?>><?= $n['TEXT'] ?></a>
    <?php if ($n['SUB']): ?><div class="nav-d"><?php foreach ($n['SUB'] as $s): ?><a href="<?= $s['LINK'] ?>"><?= $s['TEXT'] ?></a><?php endforeach ?></div><?php endif ?>
  </div>
<?php endforeach;
