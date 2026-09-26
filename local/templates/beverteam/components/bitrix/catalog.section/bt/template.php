<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// Карточки рисует bt_card() по данным bt_catalog_data(): та же разметка, что у BT_card() в ui.js
?>
<div class="grid g3" id="cards">
<?php foreach ($arResult['ITEMS'] as $item) {
    $m = bt_product((string)$item['ID']);
    if ($m) {
        echo bt_card($m);
    }
} ?>
</div>
<p class="muted" id="cardsEmpty"<?= $arResult['ITEMS'] ? ' style="display:none"' : '' ?>>По выбранным условиям товаров нет — снимите часть условий фильтра.</p>

<section class="sec sec--s">
  <h2 class="display h2" style="margin-bottom:20px">Почему покупают у нас</h2>
  <div class="utp" id="utp" style="grid-template-columns:repeat(2,1fr)"></div>
  <script>document.addEventListener('DOMContentLoaded',()=>{utp.innerHTML=BT_utp(4);});</script>
</section>

<?php if (trim($arResult['~DESCRIPTION'] ?? '') !== ''): ?>
  <div class="seo prose"><?= $arResult['~DESCRIPTION'] ?></div>
<?php endif;
