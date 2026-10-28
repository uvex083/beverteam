<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Избранное');
$APPLICATION->AddChainItem('Избранное');
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
?>
<div class="wrap cat-page">
  <?php $APPLICATION->ShowViewContent('bt_crumbs') ?>
  <div class="pagehead"><h1 class="display h1">Избранное</h1></div>
  <div class="grid g4" id="favList"></div>
  <div class="cmp__empty" id="favEmpty" hidden>
    <p class="display h3" style="margin:0 0 10px">В избранном пока пусто</p>
    <p class="muted" style="margin:0 0 20px">Отмечайте товары сердечком в каталоге — они соберутся здесь.</p>
    <a class="btn" href="/catalog/">Перейти в каталог</a>
  </div>
</div>
<script>
// список избранного хранится в браузере (bt_fav), карточки — BT_card из ui.js
document.addEventListener('DOMContentLoaded',()=>{
  const paint=()=>{
    const items=BT_FAV.map(BT_find).filter(Boolean);
    favList.innerHTML=items.map(BT_card).join('');
    favEmpty.hidden=items.length>0;
    BT_favUpdate(); BT_cmpUpdate();
  };
  favList.addEventListener('click',e=>{ if(e.target.closest('.pc__fav')) setTimeout(paint,0); });
  paint();
});
</script>
<?php
$APPLICATION->AddViewContent('bt_crumbs', $APPLICATION->GetNavChain(false, 0, SITE_TEMPLATE_PATH . '/components/bitrix/breadcrumb/bt/template.php', true, false));
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
