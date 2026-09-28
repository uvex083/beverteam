<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Корзина');
$APPLICATION->SetPageProperty('title', 'Корзина — BEVERTEAM');
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
?>
<div class="wrap cartp">
  <nav class="crumbs" aria-label="Хлебные крошки"><a href="/">Главная</a><span>Корзина</span></nav>
  <div class="pagehead cart__head"><h1 class="display h1">Корзина</h1><div class="cart__acts"><button type="button" class="cart__share" id="shareCart" hidden><?= bt_icon('share') ?>Поделиться корзиной</button><button type="button" class="link cart__clear" id="clearCart" hidden>Очистить корзину</button></div></div>

  <div class="card cshared" id="shared" hidden></div>
  <div id="tl"></div>
  <div class="cartl" id="cartl">
    <div id="items"></div>
    <aside class="sum" id="sumBox">
      <div class="free" id="free"></div>
      <div class="l"><span>Товары, <span id="cnt"></span></span><span id="sub"></span></div>
      <div class="l"><span>Скидка</span><span id="disc" style="color:var(--ok)"></span></div>
      <div class="l"><span>Доставка</span><span class="muted">рассчитаем при оформлении</span></div>
      <div class="l t"><span>Итого</span><span id="tot"></span></div>
      <a class="btn btn--block" id="goOrder" href="/personal/order/make/" style="margin-top:14px">Оформить заказ</a>
      <p class="muted" style="font-size:12.5px;margin:12px 0 0;text-align:center">Юрлицам — оплата по счёту, закрывающие документы</p>
    </aside>
  </div>

  <section class="sec">
    <div class="row between" style="margin-bottom:22px"><h2 class="display h2">С этим берут</h2><a class="link" href="/catalog/">В каталог →</a></div>
    <div class="grid g4" id="rec"></div>
  </section>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const fmt=BT_fmt, esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const plural=(n,a)=>a[n%10===1&&n%100!==11?0:n%10>=2&&n%10<=4&&(n%100<10||n%100>=20)?1:2];
  let busy=false;
  const set=(id,q)=>{busy=true;BT_cartSet(id,q);busy=false;};
  tl.innerHTML=BT_timeline();
  const line=c=>{const p=BT_piece(c,c.kg,c.q);
    return `<span class="price">${fmt(p*c.q)}${c.q>1?`<s>${fmt(p)} × ${c.q} шт</s>`:''}</span>`;};
  const totals=()=>{const cart=BT_cartItems(),t=BT_cartTotal(),full=cart.reduce((a,c)=>a+(c.old||c.p)*c.q,0);
    cnt.textContent=cart.length+' '+plural(cart.length,['позиция','позиции','позиций']);
    sub.textContent=fmt(full); disc.textContent=full>t.sum?'−'+fmt(full-t.sum):'—'; tot.textContent=fmt(t.sum);
    free.innerHTML=BT_freeBar(t.sum);};
  function render(){
    const cart=BT_cartItems();
    if(!cart.length){items.innerHTML=`<div class="card empty"><div class="display">Корзина пуста</div><p>Добавьте кофе, чай или оборудование из каталога.</p><a class="btn" href="/catalog/">В каталог</a></div>`;}
    else items.innerHTML=cart.map(c=>`<div class="ci">
      <a class="ci__ph" href="${esc(c.url)}" aria-label="${esc(c.n)}">${c.img?`<img src="${esc(c.img)}" alt="" loading="lazy" width="90" height="90">`:''}</a>
      <div><h3><a href="${esc(c.url)}">${esc(c.n)}</a></h3>${c.kg?`<p class="ci__pack">Фасовка <b>${c.kg} кг</b> · ${fmt(BT_perKg(c,c.kg,c.q))} за кг</p>`:''}<p class="par">${esc(c.par)}</p>
        <div class="qty"><button type="button" data-d="-" aria-label="Уменьшить количество">−</button><input value="${c.q}" data-id="${c.key}" inputmode="numeric" aria-label="Количество, шт"><button type="button" data-d="+" aria-label="Увеличить количество">+</button></div></div>
      <div class="ci__r">${line(c)}<button type="button" class="del" data-id="${c.key}">Удалить</button></div>
    </div>`).join('');
    totals();
    tl.hidden=sumBox.hidden=clearCart.hidden=shareCart.hidden=!cart.length; cartl.classList.toggle('is-empty',!cart.length);
  }
  // удаление — только после подтверждения; «Вернуть» в уведомлении остаётся
  const remove=id=>{const q=BT_CART[id],m=BT_find(BT_kid(id)),n=esc((m?m.n:'Товар')+(BT_kkg(id)?`, фасовка ${BT_kkg(id)} кг`:''));
    BT_confirm({title:'Удалить товар?',text:`«${n}» будет удалён из корзины.`,ok:'Удалить',cancel:'Оставить'}).then(yes=>{
      if(!yes){render();return;}
      set(id,0); render();
      BT_toast(`«${n}» удалён · <a href="#" id="undoDel">Вернуть</a>`);
      document.getElementById('undoDel').onclick=ev=>{ev.preventDefault();set(id,q);render();BT_toast('Вернули в корзину');};});};
  clearCart.addEventListener('click',()=>{const was={...BT_CART},n=Object.keys(was).length;
    BT_confirm({title:'Очистить корзину?',text:`Из корзины будут удалены все товары: ${n} ${plural(n,['позиция','позиции','позиций'])}.`,ok:'Очистить',cancel:'Оставить'}).then(yes=>{
      if(!yes)return;
      Object.keys(was).forEach(id=>set(id,0)); render();
      BT_toast('Корзина очищена · <a href="#" id="undoClear">Вернуть</a>');
      document.getElementById('undoClear').onclick=ev=>{ev.preventDefault();Object.entries(was).forEach(([id,q])=>set(id,q));render();BT_toast('Вернули товары в корзину');};});});
  // «Поделиться корзиной»: ссылка ?share=ключ*кол-во,… — у получателя товары добавляются в его корзину
  shareCart.addEventListener('click',()=>{
    const u=location.origin+'/personal/cart/?share='+Object.entries(BT_CART).map(([k,q])=>k+'*'+q).join(',');
    if(navigator.share&&matchMedia('(pointer:coarse)').matches){ navigator.share({title:'Корзина BEVERTEAM',url:u}).catch(()=>{}); return; }
    (navigator.clipboard?navigator.clipboard.writeText(u):Promise.reject()).then(
      ()=>BT_toast('Ссылка на корзину скопирована — отправьте её, получатель сможет добавить эти товары к себе'),
      ()=>BT_toast('Не удалось скопировать ссылку: '+esc(u)));
  });
  const got=(new URLSearchParams(location.search).get('share')||'').split(',').map(x=>{const [k,q]=x.split('*');return {k,q:Math.max(1,parseInt(q,10)||1)};})
    .filter(x=>x.k&&BT_find(BT_kid(x.k)));
  const dropShare=()=>history.replaceState(null,'',location.pathname);
  const addShared=()=>{ got.forEach(x=>set(x.k,x.q)); shared.hidden=true; dropShare(); render(); };
  if(got.length&&!Object.keys(BT_CART).length){ addShared(); BT_toast('Добавили в корзину товары по ссылке'); }
  else if(got.length){
    const sum=got.reduce((a,x)=>{const m=BT_find(BT_kid(x.k));return a+BT_piece(m,BT_kkg(x.k)||1,x.q)*x.q;},0);
    shared.innerHTML=`<div><b>Вам поделились корзиной</b> — ${got.length} ${plural(got.length,['товар','товара','товаров'])} на ${fmt(sum)}</div>
      <ul>${got.map(x=>{const m=BT_find(BT_kid(x.k));return `<li>${esc(m.n)}${BT_kkg(x.k)?`, ${BT_kkg(x.k)} кг`:''} — ${x.q} шт</li>`;}).join('')}</ul>
      <div class="row"><button type="button" class="btn btn--sm" id="sharedAdd">Добавить в мою корзину</button><button type="button" class="btn btn--line btn--sm" id="sharedNo">Не нужно</button></div>`;
    shared.hidden=false;
    sharedAdd.onclick=()=>{ addShared(); BT_toast('Товары добавлены в корзину'); };
    sharedNo.onclick=()=>{ shared.hidden=true; dropShare(); };
  } else if(location.search.includes('share=')) dropShare();
  // количество меняем точечно: строка и итог, без пересборки списка
  items.addEventListener('change',e=>{const i=e.target.closest('input[data-id]');if(!i)return;
    const q=Math.max(1,parseInt(i.value,10)||1);i.value=q;set(i.dataset.id,q);
    const c=BT_cartItems().find(x=>x.key===i.dataset.id);
    if(c){const ci=i.closest('.ci');ci.querySelector('.price').outerHTML=line(c);const pk=ci.querySelector('.ci__pack b');
      if(pk)pk.parentNode.innerHTML=`Фасовка <b>${c.kg} кг</b> · ${fmt(BT_perKg(c,c.kg,c.q))} за кг`;}
    totals();});
  items.addEventListener('bt:qtyzero',e=>remove(e.target.dataset.id));
  items.addEventListener('click',e=>{const b=e.target.closest('.del');if(b)remove(b.dataset.id);});
  // добавили из «С этим берут» или сервер откатил количество — перерисовать список
  document.addEventListener('bt:cart',()=>{if(!busy)render();});
  render();
  BT_mbar(goOrder,tot);
  const inCart=new Set(Object.keys(BT_CART));
  rec.innerHTML=[...BT_PRODUCTS.coffee,...BT_PRODUCTS.tea].filter(m=>!inCart.has(m.id)&&m.p).slice(0,4).map(BT_card).join('');
  BT_favUpdate(); BT_cmpUpdate();
});
</script>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
