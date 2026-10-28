<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// та же разметка, что у BT_card() в ui.js
?>
<div class="grid g3" id="cards">
<?php $n = 0; foreach ($arResult['ITEMS'] as $item) {
    $m = bt_product((string)$item['ID']);
    if ($m) {
        echo bt_card($m, $n++ < 3); // первый ряд — без lazy, это LCP раздела
    }
} ?>
</div>
<p class="muted" id="cardsEmpty"<?= $arResult['ITEMS'] ? ' style="display:none"' : '' ?>>По выбранным условиям товаров нет — снимите часть условий фильтра.</p>

<section class="sec sec--s">
  <div class="display h2 th th2" style="margin-bottom:20px">Почему покупают у нас</div>
  <div class="utp" id="utp"></div>
  <script>document.addEventListener('DOMContentLoaded',()=>{utp.innerHTML=BT_utp(4);});</script>
</section>

<?php if (trim($arResult['~DESCRIPTION'] ?? '') !== ''): ?>
  <div class="seo post__body"><?= bt_seo_html($arResult['~DESCRIPTION']) ?></div>
<?php endif;
