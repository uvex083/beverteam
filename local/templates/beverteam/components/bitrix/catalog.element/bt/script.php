<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<script>
// Страница товара: скрипт из product.html, обобщённый на любой товар — данные из BT_PAGE и BT_find()
document.addEventListener('DOMContentLoaded',()=>{
  const P=window.BT_PAGE, PID=P.id, PM=BT_find(PID)||{p:0}, U=P.unit;
  const qty=document.getElementById('qty'), pTotal=document.getElementById('pTotal'), pPer=document.getElementById('pPer'),
    pPacks=document.getElementById('pPacks'), pAdd=document.getElementById('pAdd'), tiers=document.getElementById('tiers');

  /* ---- галерея ---- */
  if(P.photos){
    const swThumbs=new Swiper('#thumbs',{
      direction:'vertical', slidesPerView:'auto', spaceBetween:10, freeMode:true, watchSlidesProgress:true,
      slideToClickedSlide:true, mousewheel:{forceToAxis:true},
      navigation:{prevEl:'#thUp',nextEl:'#thDn'},
      breakpoints:{0:{direction:'horizontal',slidesPerView:'auto',spaceBetween:8},900:{direction:'vertical',spaceBetween:10}}
    });
    const big=new Swiper('#galBig',{
      slidesPerView:1, spaceBetween:20, speed:380, keyboard:{enabled:true},
      thumbs:{swiper:swThumbs}, a11y:{prevSlideMessage:'Предыдущее фото',nextSlideMessage:'Следующее фото'},
      on:{slideChange(){ galN.textContent=(this.activeIndex+1)+' / '+P.photos; }}
    });
    /* клик по фото — просмотр на весь экран */
    const imgs=[...document.querySelectorAll('#galBig img[data-full]')];
    imgs.forEach((im,i)=>im.addEventListener('click',()=>{ if(!big.allowClick) return;
      BT_lightbox(imgs.map(x=>({src:x.dataset.full||x.src})),i); }));
  }

  /* ---- блок покупки: фасовка → «В корзину»; в корзине — «− N +» и переход в корзину с суммой ---- */
  const key = () => BT_keyOf(PM), pSt=document.getElementById('pSt');
  const label=(kg,q)=>PM.bulk?`${+(kg*q).toFixed(2)} кг`:`${q} шт`;
  function sync(anim){
    const kg=BT_packOf(PM), inCart=BT_CART[key()]||0, q=inCart||1, pc=BT_piece(PM,kg,q), sum=pc*q;
    if(PM.p){ pTotal.textContent=BT_fmt(sum);
      if(PM.bulk){ const per=BT_perKg(PM,kg,q), base=PM.bulk[0].p, tot=kg*q, pct=Math.round((1-per/base)*100);
        pPer.innerHTML=`${BT_fmt(per)} за кг${tot>1?` × ${+tot.toFixed(2)} кг`:''}${pct>0?`<b>выгода ${pct}% · ${BT_fmt(Math.round((base-per)*tot))}</b>`:''}`; }
      else pPer.textContent = q>1 ? `${BT_fmt(pc)} × ${q} шт` : 'за 1 шт'; }
    if(tiers&&PM.bulk){ const p=BT_perKg(PM,kg,q); [...tiers.tBodies[0].rows].forEach(r=>r.classList.toggle('on',p===+r.cells[1].textContent.replace(/\D/g,''))); }
    if(PM.bulk) pPacks.innerHTML=BT_packs(PM,true);
    const pop=anim?' pop':'';
    pAdd.innerHTML = !inCart
      ? `<button class="bb__add" type="button" data-add>В корзину</button>`
      : `<div class="bb__step${pop}" role="group" aria-label="Количество в корзине"><button type="button" data-q="-" aria-label="Уменьшить">−</button><output>${label(kg,q)}</output><button type="button" data-q="+" aria-label="Увеличить">+</button></div>
         <a class="bb__go${pop}" href="/personal/cart/"><span>В корзину <svg viewBox="0 0 24 24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span><b>${BT_fmt(sum)}</b></a>`;
    pSt.textContent=inCart?'В корзине':'В наличии'; pSt.classList.toggle('is-in',!!inCart);
  }
  pAdd.addEventListener('click',e=>{
    if(e.target.closest('[data-add]')){ BT_cartSet(key(),1); sync(true); BT_toast(`${label(BT_packOf(PM),1)} в корзине · <a href="/personal/cart/">Оформить</a>`); return; }
    const s=e.target.closest('[data-q]'); if(!s) return;
    const n=(BT_CART[key()]||0)+(s.dataset.q==='+'?1:-1);
    if(n<1){ BT_cartSet(key(),0); BT_toast('Товар убран из корзины'); } else BT_cartSet(key(),n);
    sync();
  });
  // фасовка: блок показывает, сколько этой фасовки уже в корзине
  pPacks.addEventListener('click',e=>{ const b=e.target.closest('[data-kg]'); if(!b) return; BT_packSet(PID,+b.dataset.kg); sync(); });
  document.addEventListener('bt:cart',e=>{ if(e.detail&&String(e.detail.id).split(':')[0]===String(PID)) sync(); });
  sync();

  const dd=BT_dates(); dship.innerHTML=`${BT_ICONS.truck}<span>Екатеринбург — <b>${dd.relDeliver?dd.relDeliver+', ':''}${BT_fmtDate(dd.deliver)}</b><br><small class="muted">По России — СДЭК, 2–7 дней</small></span>`;
  ptabs.addEventListener('click',e=>{const b=e.target.closest('[data-p]');if(!b)return;[...ptabs.children].forEach(x=>x.setAttribute('aria-selected',x===b));
    document.querySelectorAll('.pane').forEach(p=>p.hidden=p.dataset.pane!==b.dataset.p);});
  const rec=document.getElementById('rec');
  if(rec){ BT_cmpUpdate(); BT_slider(rec,{min:5}); }



  /* ---- отзывы: сводка, распределение оценок, карточки ---- */
  const RV=P.reviews||[], n=RV.length;
  if(n){
    const avg=Math.round(RV.reduce((a,x)=>a+x.r,0)/n*10)/10, w=n===1?'отзыв':n<5?'отзыва':'отзывов';
    rTop.innerHTML=`${BT_stars(avg)}<b style="font-size:14px">${String(avg).replace('.',',')}</b><span class="muted" style="font-size:13px">${n} ${w}</span>`;
    const dist=[5,4,3,2,1].map(k=>({k,n:RV.filter(r=>r.r===k).length}));
    rating.innerHTML=`<div class="rating__n"><b>${String(avg).replace('.',',')}</b>${BT_stars(avg,18)}
        <p class="muted" style="font-size:13px;margin:8px 0 14px">${n} ${w}<br>от покупателей</p>
        <a class="btn btn--sm btn--line" href="#revform">Оставить отзыв</a></div>
      <div class="rating__bars">${dist.map(d=>`<div><span style="width:14px">${d.k}</span>${BT_ICONS.star}
        <i style="--p:${d.n/n*100}%"></i><span class="muted" style="width:24px;text-align:right">${d.n}</span></div>`).join('')}</div>`;
    const esc=s=>String(s||'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    revList.innerHTML=RV.map(r=>`<article class="rev">
      <div class="rev__h"><span class="rev__av">${esc(r.a[0])}</span><b>${esc(r.a)}</b>${BT_stars(r.r)}
        <time class="muted" style="font-size:12.5px" datetime="${r.d}">${BT_postDate(r.d)}</time>
        ${r.ok?'<span class="rev__ok">Покупка подтверждена</span>':''}</div>
      <p>${esc(r.t)}</p>${r.m?`<p class="muted" style="font-size:13px;margin-top:8px">Машина: ${esc(r.m)}</p>`:''}
      ${(r.ph||[]).length?`<div class="rev__ph">${r.ph.map((f,i)=>`<button type="button" data-rev="${esc(r.id)}" data-i="${i}" aria-label="Фото к отзыву"><img src="${esc(f.s)}" alt="" loading="lazy"></button>`).join('')}</div>`:''}</article>`).join('');
    revList.addEventListener('click',e=>{ const b=e.target.closest('[data-rev]'); if(!b) return; const r=RV.find(x=>String(x.id)===b.dataset.rev);
      r&&BT_lightbox(r.ph.map(f=>({src:f.f,cap:r.a})),+b.dataset.i); });
    /* разметка отзывов — только для реальных отзывов */
    const ld=document.createElement('script');ld.type='application/ld+json';
    ld.textContent=JSON.stringify({'@context':'https://schema.org','@type':'Product',name:P.name,
      aggregateRating:{'@type':'AggregateRating',ratingValue:String(avg),reviewCount:String(n),bestRating:'5'},
      review:RV.map(r=>({'@type':'Review',author:{'@type':'Person',name:r.a},datePublished:r.d,
        reviewRating:{'@type':'Rating',ratingValue:String(r.r),bestRating:'5'},reviewBody:r.t}))});
    document.head.appendChild(ld);
  }
  rTop.addEventListener('click',e=>{e.preventDefault();document.getElementById('tabRev').click();
    document.querySelector('.tabsblock').scrollIntoView({behavior:'smooth',block:'start'});});


  /* «Поделиться»: на телефоне — системное окно, на компьютере — своё меню (системное окно Windows непонятное) */
  const shM=document.getElementById('shareMenu');
  const shOpen=on=>{ shM.hidden=!on; bShare.setAttribute('aria-expanded',on); };
  bShare.addEventListener('click',e=>{
    e.stopPropagation();
    if(navigator.share&&matchMedia('(pointer:coarse)').matches){ navigator.share({title:document.title,url:location.href}).catch(()=>{}); return; }
    const u=encodeURIComponent(location.href), t=encodeURIComponent(document.querySelector('h1')?.textContent.trim()||document.title);
    const links={tg:`https://t.me/share/url?url=${u}&text=${t}`,wa:`https://wa.me/?text=${t}%20${u}`,vk:`https://vk.com/share.php?url=${u}`};
    shM.querySelectorAll('[data-share]').forEach(a=>{ if(links[a.dataset.share]) a.href=links[a.dataset.share]; });
    shOpen(shM.hidden);
    /* иконки нарисованы с разными полями — подгоняем рамку каждой по контуру, чтобы все были одного размера */
    if(!shM.hidden&&!shM.dataset.fit){ shM.dataset.fit=1; shM.querySelectorAll('svg').forEach(v=>{
      const b=v.getBBox(), pad=v.getAttribute('stroke')?1:0, m=Math.max(b.width,b.height)+pad*2;
      v.setAttribute('viewBox',`${b.x-(m-b.width)/2} ${b.y-(m-b.height)/2} ${m} ${m}`); }); }
  });
  shM.addEventListener('click',e=>{
    const a=e.target.closest('[data-share]'); if(!a) return;
    if(a.dataset.share==='copy'){ e.preventDefault();
      (navigator.clipboard?navigator.clipboard.writeText(location.href):Promise.reject()).then(()=>BT_toast('Ссылка на товар скопирована'),()=>BT_toast('Не удалось скопировать — скопируйте адрес из строки браузера')); }
    shOpen(false);
  });
  document.addEventListener('click',e=>{ if(!shM.hidden&&!e.target.closest('.share')) shOpen(false); });
  document.addEventListener('keydown',e=>{ if(e.key==='Escape'&&!shM.hidden){ shOpen(false); bShare.focus(); } });
  /* сравнение — общий список BT_CMP, как у карточек в каталоге */
  bCmp.setAttribute('aria-pressed',BT_cmpHas(PID));
  bCmp.addEventListener('click',()=>{BT_cmpToggle(PID);const on=BT_cmpHas(PID);
    bCmp.setAttribute('aria-pressed',on);BT_toast(on?'Товар добавлен к сравнению':'Товар убран из сравнения');});
  bFav.setAttribute('aria-pressed',BT_favHas(PID));
  bFav.addEventListener('click',()=>{const on=BT_favToggle(PID);
    bFav.setAttribute('aria-pressed',on);BT_toast(on?'Товар добавлен в <a href="/personal/favorites/">избранное</a>':'Товар убран из избранного');});
});
</script>
