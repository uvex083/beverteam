<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
// Подразделы текущего корневого раздела пилюлями с числом товаров
if (empty($arResult['SECTIONS'])) {
    return;
}
$cur = (int)($arParams['BT_CUR_SECTION'] ?? 0);
?>
<div class="subcats">
<?php foreach ($arResult['SECTIONS'] as $s): ?>
  <a<?= (int)$s['ID'] === $cur ? ' class="cur"' : '' ?> href="<?= $s['SECTION_PAGE_URL'] ?>"><?= $s['NAME'] ?> <span><?= (int)$s['ELEMENT_CNT'] ?></span></a>
<?php endforeach ?>
</div>
