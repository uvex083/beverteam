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

  /* ---- фасовка, количество (шт) и корзина: один источник истины ---- */
  const key = () => BT_keyOf(PM);
  function sync(from){
    const q=Math.max(1,parseInt(qty.value,10)||1), kg=BT_packOf(PM);
    if(String(q)!==qty.value) qty.value=q;
    const pc=BT_piece(PM,kg,q);
    if(PM.p){ pTotal.textContent=BT_fmt(pc*q);
      pPer.textContent = PM.bulk ? `${BT_fmt(BT_perKg(PM,kg,q))} за кг · ${kg} кг${q>1?` × ${q} шт`:''}` : (q>1 ? `${BT_fmt(pc)} × ${q} шт` : 'за 1 шт'); }
    if(tiers&&PM.bulk){ const p=BT_perKg(PM,kg,q); [...tiers.tBodies[0].rows].forEach(r=>r.classList.toggle('on',p===+r.cells[1].textContent.replace(/\D/g,''))); }
    if(PM.bulk) pPacks.innerHTML=BT_packs(PM,true);
    const inCart=BT_CART[key()]||0;
    // кнопку не пересобираем без нужды: уход фокуса из поля количества перерисовал бы её под курсором и съел клик
    if(pAdd.dataset.st===String(inCart)){}
    else if(inCart){ pAdd.dataset.st=inCart;
      pAdd.innerHTML=`<a class="btn btn--dark" href="/personal/cart/">В корзине · ${inCart} шт</a>`;
    } else { pAdd.dataset.st=0;
      pAdd.innerHTML=`<button class="btn" id="pBuy" type="button">В корзину</button>`;
      document.getElementById('pBuy').addEventListener('click',()=>{
        BT_cartSet(key(),Math.max(1,parseInt(qty.value,10)||1));
        sync('cart');
        BT_toast(`${qty.value} шт${PM.bulk?` по ${BT_packOf(PM)} кг`:''} в корзине · <a href="/personal/cart/">Оформить</a>`);
      });
    }
    /* если эта фасовка уже в корзине, степпер правит корзину */
    if(inCart && from==='qty' && inCart!==q){ BT_cartSet(key(),q); sync('cart'); }
  }
  // фасовка: счётчик переключается на её количество в корзине (или 1)
  pPacks.addEventListener('click',e=>{ const b=e.target.closest('[data-kg]'); if(!b) return;
    BT_packSet(PID,+b.dataset.kg); qty.value=BT_CART[key()]||1; sync('packs'); });
  if(BT_CART[key()]) qty.value=BT_CART[key()];
  qty.addEventListener('change',()=>sync('qty'));
  /* «−» на единице: убираем товар из корзины и возвращаем кнопку «В корзину» */
  qty.addEventListener('bt:qtyzero',()=>{ if(BT_CART[key()]){ BT_cartSet(key(),0); BT_toast('Товар убран из корзины'); } sync('cart'); });
  qty.addEventListener('input',()=>{ if(/^\d+$/.test(qty.value)) sync('qty'); });
  document.addEventListener('bt:cart',e=>{ if(e.detail&&e.detail.id===PID) sync('ext'); });
  sync('init');

  const dd=BT_dates(); dship.innerHTML=`${BT_ICONS.truck}<span>Екатеринбург — <b>${dd.relDeliver?dd.relDeliver+', ':''}${BT_fmtDate(dd.deliver)}</b><br><small class="muted">По России — СДЭК, 2–7 дней</small></span>`;
  ptabs.addEventListener('click',e=>{const b=e.target.closest('[data-p]');if(!b)return;[...ptabs.children].forEach(x=>x.setAttribute('aria-selected',x===b));
    document.querySelectorAll('.pane').forEach(p=>p.hidden=p.dataset.pane!==b.dataset.p);});
  const rec=document.getElementById('rec');
  if(rec){ BT_cmpUpdate(); BT_slider(rec,{min:5}); }

  /* ---- загрузка фото к отзыву: превью, удаление, лимит 5 ---- */
  (function(){
    const inp=document.getElementById('rFiles'), zone=document.getElementById('drop'), box=document.getElementById('rThumbs');
    if(!inp) return;
    let files=[];
    inp.btFiles=()=>files; inp.btClear=()=>{ files=[]; paint(); };
    const paint=()=>{
      box.innerHTML=files.map((f,i)=>`<figure><img src="${window.URL.createObjectURL(f)}" alt="">
        <button type="button" data-i="${i}" aria-label="Удалить ${f.name}">×</button>
        <figcaption>${f.name}</figcaption></figure>`).join('');
      zone.querySelector('.drop__t small').textContent = files.length
        ? `Добавлено ${files.length} из 5 — можно добавить ещё` : 'или нажмите, чтобы выбрать — до 5 файлов, JPG или PNG';
    };
    const add=list=>{
      [...list].forEach(f=>{
        if(files.length>=5) return BT_toast('Можно приложить не больше 5 фото');
        if(!/^image\/(png|jpeg)$/.test(f.type)) return BT_toast(`${f.name}: подойдёт только JPG или PNG`);
        if(f.size>10*1024*1024) return BT_toast(`${f.name}: файл больше 10 МБ`);
        if(files.some(x=>x.name===f.name&&x.size===f.size)) return;
        files.push(f);
      });
      paint();
    };
    inp.addEventListener('change',()=>{add(inp.files);inp.value='';});
    box.addEventListener('click',e=>{const b=e.target.closest('button[data-i]');if(!b)return; files.splice(+b.dataset.i,1); paint();});
    ['dragenter','dragover'].forEach(ev=>zone.addEventListener(ev,e=>{e.preventDefault();zone.classList.add('is-over');}));
    ['dragleave','drop'].forEach(ev=>zone.addEventListener(ev,e=>{e.preventDefault();zone.classList.remove('is-over');}));
    zone.addEventListener('drop',e=>add(e.dataTransfer.files));
    paint();
  })();

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
  document.getElementById('rPick').addEventListener('click',e=>{const b=e.target.closest('[data-r]');if(!b)return;
    [...b.parentNode.children].forEach(x=>x.className='btn btn--ghost btn--xs');b.className='btn btn--xs';});
  rTop.addEventListener('click',e=>{e.preventDefault();document.getElementById('tabRev').click();
    document.querySelector('.tabsblock').scrollIntoView({behavior:'smooth',block:'start'});});
  /* покупатель вошёл — имя и почту в форме отзыва подставляем сами (данные из BT_USER, не из кэшируемого шаблона) */
  const rFill=u=>{ if(!u) return; const n=document.getElementById('rName'), m=document.getElementById('rEmail');
    if(n&&!n.value&&u.name) n.value=u.name; if(m&&!m.value&&u.email) m.value=u.email; };
  rFill(window.BT_USER); document.addEventListener('bt:auth',e=>rFill(e.detail));
  /* отправка отзыва: на проверку, опубликует менеджер */
  (function(){
    const send=document.getElementById('rSend'), agree=document.getElementById('rAgree'), errBox=document.getElementById('rErr');
    try{ if(localStorage.getItem('bt_agree')==='1') agree.checked=true; }catch(e){}
    const fld={name:'rName',email:'rEmail',text:'rText',machine:'rMachine'};
    const setErr=(el,msg)=>{ const f=el.closest('.field')||el.closest('.check'); if(!f) return; f.classList.toggle('is-err',!!msg);
      if(f.classList.contains('check')) return; let s=f.querySelector('.err'); if(!s){s=document.createElement('span');s.className='err';f.appendChild(s);} s.textContent=msg||''; };
    const rules={rName:v=>v.trim().length<2?'Как вас подписать? Минимум 2 символа':'', rEmail:v=>v.trim()&&!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim())?'Проверьте адрес: нужен формат mail@company.ru':'',
      rText:v=>v.trim().length<10?'Расскажите чуть подробнее — хотя бы пару слов':''};
    Object.keys(rules).forEach(id=>{ const el=document.getElementById(id);
      el.addEventListener('blur',()=>{ if(el.value.trim()||id==='rText') setErr(el,rules[id](el.value)); });
      el.addEventListener('input',()=>{ if(el.closest('.field').classList.contains('is-err')) setErr(el,rules[id](el.value)); }); });
    agree.addEventListener('change',()=>agree.checked&&setErr(agree,''));
    send.addEventListener('click',()=>{
      let bad=null; Object.keys(rules).forEach(id=>{ const el=document.getElementById(id), m=rules[id](el.value); setErr(el,m); if(m&&!bad) bad=el; });
      if(!agree.checked){ setErr(agree,'Нужно согласие'); bad=bad||agree; }
      if(bad){ bad.focus(); return; }
      const fd=new FormData(); fd.append('sessid',window.BT_SID||''); fd.append('product',P.id);
      fd.append('rating',document.querySelector('#rPick .btn:not(.btn--ghost)')?.dataset.r||'5'); fd.append('agree','Y'); fd.append('website',document.getElementById('rWebsite').value);
      Object.entries(fld).forEach(([k,id])=>fd.append(k,document.getElementById(id).value));
      const inp=document.getElementById('rFiles'); (inp&&inp.btFiles?inp.btFiles():[]).forEach(f=>fd.append('photos[]',f,f.name));
      send.disabled=true; send.textContent='Отправляем…'; errBox.textContent='';
      fetch('/local/ajax/review.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()).then(d=>{
        if(d.ok){ try{localStorage.setItem('bt_agree','1');}catch(e){}
          document.querySelector('#revform .revform').innerHTML='<div class="rev-sent"><b>Спасибо, отзыв отправлен!</b>Он появится на странице после проверки — обычно в течение рабочего дня.</div>'; return; }
        Object.entries(d.errors||{}).forEach(([k,m])=>{ const el=document.getElementById(fld[k]||''); el?setErr(el,m):(errBox.textContent=m); });
        if(d.message) errBox.textContent=d.message;
        if(!d.errors&&!d.message) errBox.textContent='Не получилось отправить. Обновите страницу и попробуйте ещё раз.';
      }).catch(()=>{ errBox.textContent='Нет связи с сервером. Попробуйте ещё раз.'; })
      .finally(()=>{ if(document.body.contains(send)){ send.disabled=false; send.textContent='Отправить отзыв'; } });
    });
  })();

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
