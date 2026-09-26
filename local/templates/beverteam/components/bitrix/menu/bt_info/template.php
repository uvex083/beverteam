<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
if (!$arResult) {
    return;
}
?>
<aside class="info-nav">
  <?php foreach ($arResult as $it): ?>
  <a<?= $it['SELECTED'] ? ' class="cur" aria-current="page"' : '' ?> href="<?= htmlspecialcharsbx($it['LINK']) ?>"><?= htmlspecialcharsbx($it['TEXT']) ?></a>
  <?php endforeach ?>
</aside>
