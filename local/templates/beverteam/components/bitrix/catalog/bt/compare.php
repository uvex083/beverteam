<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// Сравнение товаров: список — BT_CMP в браузере, характеристики — bt_catalog_specs()
use Bitrix\Main\Web\Json;

$APPLICATION->SetTitle('Сравнение товаров');
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
$APPLICATION->AddChainItem('Сравнение товаров');
?>
<div class="wrap">
  <?php $APPLICATION->ShowViewContent('bt_crumbs') ?>
  <h1 class="display h1" style="margin:14px 0 22px">Сравнение товаров</h1>
  <div id="cmpCats" class="cmp__cats"></div>
  <div id="cmpBody"></div>
</div>
<div class="cmp__stick" id="cmpStick"><div class="wrap" style="position:relative"><div id="cmpStickRow"></div></div></div>
<script>window.BT_SPECS=<?= Json::encode(bt_catalog_specs()) ?>;</script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  let cat=null, onlyDiff=false, swHead=null, swStick=null, swRows=[];
  const cats=()=>{const m={};BT_CMP.forEach(id=>{const c=BT_cmpCat(id);(m[c]=m[c]||[]).push(id);});return m;};
  const list=()=>{const m=cats();return m[cat]||[];};

  function renderCats(){
    const m=cats(), keys=Object.keys(m);
    if(!keys.length){ cmpCats.innerHTML=''; return; }
    if(!m[cat]) cat=keys[0];
    cmpCats.innerHTML=keys.map(k=>`<span class="cmp__cat ${k===cat?'on':''}" data-c="${k}">
      <button type="button">${BT_CAT_NAMES[k]} <span class="n">${m[k].length}</span></button>
      <button type="button" class="x" title="Убрать всю группу" aria-label="Убрать группу ${BT_CAT_NAMES[k]}">×</button></span>`).join('');
  }

  function destroy(){ [swHead,swStick].concat(swRows).forEach(x=>{try{x&&x.destroy(true,true);}catch(e){}});
    swHead=swStick=null; swRows=[]; }

  function renderBody(){
    destroy();
    const ids=list();
    if(!ids.length){
      cmpBody.innerHTML=`<div class="cmp__empty">
        <p class="display h3" style="margin:0 0 10px">Пока нечего сравнивать</p>
        <p class="muted" style="margin:0 0 20px">Добавьте товары кнопкой сравнения в каталоге — она в правом верхнем углу карточки.</p>
        <a class="btn" href="/catalog/">Перейти в каталог</a></div>`;
      cmpStickRow.innerHTML=''; cmpStick.classList.remove('on'); return;
    }
    const P=ids.map(id=>BT_find(id)), S=ids.map(id=>BT_SPECS.vals[id]||{});
    /* строки — свойства с галочкой «Показывать на детальной странице»; пустые у всех сравниваемых не выводим */
    const groups=[['Характеристики',BT_SPECS.names.filter(f=>S.some(x=>x[f[0]]))]];

    const head=ids.map((id,i)=>{const m=P[i]; if(!m) return '<div class="swiper-slide"></div>';
      return `<div class="swiper-slide"><div class="cc">
        <a class="cc__ph" href="${m.url}">${m.img?`<img src="${m.img}" alt="${m.n}" loading="lazy">`:'<i class="noimg" aria-hidden="true"></i>'}</a>
        <h3><a href="${m.url}">${m.n}</a></h3>
        <div class="cc__tools">
          <button class="fav" data-fav="${id}" aria-pressed="${BT_favHas(id)}" title="В избранное" aria-label="В избранное">${BT_ICONS.heart}</button>
          <button class="del" data-del="${id}" title="Убрать из сравнения" aria-label="Убрать из сравнения">${BT_ICONS.trash}</button>
        </div>
        <div class="cc__buy">
          <span class="cc__p">${m.p?BT_fmt(m.p):'по запросу'}${m.unit?`<small>${m.unit}</small>`:(m.bulk?'<small>за 1 кг</small>':'')}</span>
          <span class="cc__ctl" data-ctl="${id}">${m.p?BT_addCtl(m):`<a class="btn btn--sm btn--ghost" href="/kontakty/">Запрос</a>`}</span>
        </div></div></div>`;}).join('');

    const rows=groups.map(g=>{
      const body=g[1].map(f=>{
        const vals=S.map(x=>x[f[0]]||'—');
        const diff=new Set(vals).size>1;
        if(onlyDiff&&!diff) return '';
        return `<div class="cmp__r ${diff?'diff':''}"><div class="lbl">${f[1]}</div>
          <div class="swiper cmp-row"><div class="swiper-wrapper">${vals.map(v=>`<div class="swiper-slide">${v}</div>`).join('')}</div></div></div>`;
      }).join('');
      return body.trim() ? `<details class="cmp__grp" open><summary>${g[0]}</summary><div>${body}</div></details>` : '';
    }).join('');

    cmpBody.innerHTML=`<div class="cmp">
      <div class="swiper cmp-head" id="cHead"><div class="swiper-wrapper">${head}</div></div>
      ${BT_sliderBtns()}
      <div class="cmp__opts">
        <label class="tgl"><input type="checkbox" role="switch" id="cDiff" ${onlyDiff?'checked':''}><span>Показывать только отличия</span></label>
      </div>
      ${rows||'<p class="muted" style="padding:20px 0">Все характеристики совпадают.</p>'}
    </div>`;

    cmpStickRow.innerHTML=`<div class="swiper cmp-stick-sw"><div class="swiper-wrapper">${ids.map((id,i)=>{const m=P[i];
      return m?`<div class="swiper-slide"><div class="cs">${m.img?`<img src="${m.img}" alt="">`:''}<div style="min-width:0">
        <b>${m.p?BT_fmt(m.p):'—'}</b><span>${m.n}</span></div></div></div>`:'';}).join('')}</div></div>`+BT_sliderBtns();

    const wrap=cmpBody.querySelector('.cmp');
    const common={slidesPerView:1,spaceBetween:12,watchOverflow:true,
      breakpoints:{700:{slidesPerView:2},1000:{slidesPerView:3},1280:{slidesPerView:4}}};
    swHead=new Swiper(wrap.querySelector('.cmp-head'),Object.assign({},common,{
      a11y:{prevSlideMessage:'Предыдущие товары',nextSlideMessage:'Следующие товары'},
      navigation:{prevEl:wrap.querySelector(':scope > .sw-btn--p'),nextEl:wrap.querySelector(':scope > .sw-btn--n')}}));
    swRows=[...wrap.querySelectorAll('.cmp-row')].map(el=>new Swiper(el,Object.assign({},common,{allowTouchMove:false})));
    swStick=new Swiper(cmpStickRow.querySelector('.cmp-stick-sw'),Object.assign({},common,{
      navigation:{prevEl:cmpStickRow.querySelector('.sw-btn--p'),nextEl:cmpStickRow.querySelector('.sw-btn--n')}}));
    /* строки характеристик и липкая панель ходят синхронно с шапкой */
    swHead.controller.control=swRows.concat(swStick);
    swStick.controller.control=[swHead].concat(swRows);
  }

  cmpCats.addEventListener('click',e=>{
    const w=e.target.closest('.cmp__cat'); if(!w) return;
    if(e.target.closest('.x')){ BT_cmpClear(cats()[w.dataset.c]); BT_toast('Группа убрана из сравнения'); render(); return; }
    cat=w.dataset.c; render();
  });
  cmpBody.addEventListener('click',e=>{
    const d=e.target.closest('[data-del]');
    if(d){ BT_cmpRemove(d.dataset.del); BT_toast('Товар убран из сравнения'); render(); return; }
    const a=e.target.closest('[data-add]');
    if(a){ BT_cartAdd(a.dataset.add); BT_toast('Товар в корзине · <a href="/personal/cart/">Оформить</a>'); return; }
    const f=e.target.closest('.fav');
    if(f){ const on=BT_favToggle(f.dataset.fav); f.setAttribute('aria-pressed',on);
      BT_toast(on?'Добавлено в <a href="/personal/favorites/">избранное</a>':'Убрано из избранного'); return; }
  });
  /* после «В корзину» — степпер количества, как в каталоге */
  document.addEventListener('bt:cart',e=>{ const id=e.detail&&e.detail.id, m=id&&BT_find(id);
    cmpBody.querySelectorAll(`[data-ctl="${id}"]`).forEach(c=>{ if(m) c.innerHTML=BT_addCtl(m); }); });
  cmpBody.addEventListener('change',e=>{ if(e.target.id==='cDiff'){ onlyDiff=e.target.checked; renderBody(); } });

  /* мини-панель выезжает, когда карточки ушли под шапку */
  const hdrH=()=>{const h=document.querySelector('.hdr');return h?Math.round(h.getBoundingClientRect().height):0;};
  addEventListener('scroll',()=>{
    const h=document.getElementById('cHead'); if(!h){cmpStick.classList.remove('on');return;}
    cmpStick.style.top=hdrH()+'px';
    const r=h.getBoundingClientRect(), tail=cmpBody.querySelector('.cmp__grp:last-of-type');
    const end=tail?tail.getBoundingClientRect().bottom:1e9;
    cmpStick.classList.toggle('on', r.bottom<hdrH()+10 && end>hdrH()+80);
  },{passive:true});

  function render(){ renderCats(); renderBody(); }
  render();
});
</script>
<?php
$APPLICATION->AddViewContent('bt_crumbs', $APPLICATION->GetNavChain(false, 0, SITE_TEMPLATE_PATH . '/components/bitrix/breadcrumb/bt/template.php', true, false));
