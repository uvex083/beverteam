<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
// Разделы каталога плитками — те же данные, что в блоке «Каталог» на главной (bt_home_tiles): название, подпись, иконка; аренда — в том же ряду
$cur = (int)($arParams['BT_CUR_ROOT'] ?? 0);
$e = fn($v) => htmlspecialcharsbx((string)$v);
?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>
  <symbol id="ico-tea" viewBox="0 0 24 24"><path d="M4 20C4 11 10 5 20 4c-1 10-7 16-16 16z"/><path d="M4 20c3-6 7-9 12-11"/></symbol>
  <symbol id="ico-bean" viewBox="0 0 24 24"><ellipse cx="12" cy="12" rx="6" ry="9" transform="rotate(-30 12 12)"/><path d="M8.5 6.5c-2 5 6 8 4 12"/></symbol>
  <symbol id="ico-machine" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><rect x="8" y="6" width="8" height="4" rx="1"/><path d="M12 13v2M9 18h6"/></symbol>
  <symbol id="ico-rent" viewBox="0 0 24 24"><rect x="4" y="3" width="13" height="16" rx="2"/><rect x="7" y="6" width="7" height="3.5" rx="1"/><circle cx="18" cy="18" r="4.5" class="ico-badge"/><path d="M16.6 20v-4h1.6a1.2 1.2 0 0 1 0 2.4h-2.2M16.2 19.2h2"/></symbol>
  <symbol id="ico-cup" viewBox="0 0 24 24"><path d="M5 8h11v6a5.5 5.5 0 0 1-11 0z"/><path d="M16 10h2a2.5 2.5 0 0 1 0 5h-2M4 20h14"/></symbol>
</defs></svg>
<nav class="rootcats" id="rootcats" aria-label="Разделы каталога">
<?php foreach (bt_home_tiles() as $t): $on = $cur && ($t['id'] ?? 0) === $cur; ?>
  <a class="rootcat<?= $on ? ' cur' : '' ?>" href="<?= $e($t['url']) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
    <span class="rootcat__n"><?= $e($t['name']) ?></span>
    <?php if (($t['note'] ?? '') !== ''): ?><span class="rootcat__c"><?= $e($t['note']) ?></span><?php endif ?>
    <span class="rootcat__ic" aria-hidden="true"><svg><use href="#ico-<?= $e($t['icon'] ?? 'cup') ?>"/></svg></span>
  </a>
<?php endforeach ?>
</nav>
