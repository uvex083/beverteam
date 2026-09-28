<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
// Разделы каталога плитками — те же данные, что в блоке «Каталог» на главной (bt_home_tiles): название, подпись, пэкшот; аренда — в том же ряду
$cur = (int)($arParams['BT_CUR_ROOT'] ?? 0);
$e = fn($v) => htmlspecialcharsbx((string)$v);
?>
<nav class="rootcats" id="rootcats" aria-label="Разделы каталога">
<?php foreach (bt_home_tiles() as $t): $on = $cur && ($t['id'] ?? 0) === $cur; ?>
  <a class="rootcat<?= $on ? ' cur' : '' ?>" href="<?= $e($t['url']) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
    <span class="rootcat__n"><?= $e($t['name']) ?></span>
    <?php if (($t['note'] ?? '') !== ''): ?><span class="rootcat__c"><?= $e($t['note']) ?></span><?php endif ?>
    <?php if (!empty($t['pack'])): ?><img class="rootcat__img" src="<?= $e($t['pack']) ?>" alt="" loading="lazy" decoding="async"<?= bt_img_wh($t['pack']) ?>><?php endif ?>
  </a>
<?php endforeach ?>
</nav>
