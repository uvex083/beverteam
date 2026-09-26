<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Sale;

$APPLICATION->SetTitle('Оформление заказа');
$APPLICATION->SetPageProperty('title', 'Оформление заказа — BEVERTEAM');
$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
Loader::includeModule('sale');

$basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID)->getOrderableItems();
if ($basket->isEmpty()) {
    LocalRedirect('/personal/cart/');
}
$e = fn($s) => htmlspecialcharsbx((string)$s);

// популярные города из макета; названия и области — из местоположений Битрикса
$popular = [];
$codes = ['0000812044', '0000814374', '0000815343', '0000854968', '0000794760', '0000670178', '0000073738', '0000103664', '0000949228'];
$r = Sale\Location\LocationTable::getList([
    'filter' => ['@CODE' => $codes, '=NAME.LANGUAGE_ID' => 'ru', '=PARENT.NAME.LANGUAGE_ID' => 'ru'],
    'select' => ['CODE', 'N' => 'NAME.NAME', 'R' => 'PARENT.NAME.NAME', 'RT' => 'PARENT.TYPE.CODE'],
]);
while ($l = $r->fetch()) {
    $popular[array_search($l['CODE'], $codes)] = ['code' => $l['CODE'], 'n' => $l['N'], 'r' => in_array($l['RT'], ['REGION', 'SUBREGION']) ? $l['R'] : ''];
}
ksort($popular);
$popular = array_values($popular);

$pays = [];
foreach (Sale\PaySystem\Manager::getList(['filter' => ['=ACTIVE' => 'Y', '!=ACTION_FILE' => 'inner'], 'order' => ['SORT' => 'ASC'], 'select' => ['ID', 'NAME', 'DESCRIPTION', 'ACTION_FILE']]) as $ps) {
    $pays[] = ['id' => (int)$ps['ID'], 'name' => $ps['NAME'], 'desc' => (string)$ps['DESCRIPTION'], 'code' => $ps['ACTION_FILE']];
}

$u = ['name' => '', 'email' => '', 'phone' => ''];
if ($USER->IsAuthorized()) {
    $cu = CUser::GetByID($USER->GetID())->Fetch() ?: [];
    $u = ['name' => trim(($cu['NAME'] ?? '') . ' ' . ($cu['LAST_NAME'] ?? '')), 'email' => $cu['EMAIL'] ?? '', 'phone' => $cu['PERSONAL_PHONE'] ?: ($cu['PERSONAL_MOBILE'] ?? '')];
    // основной адрес и первые реквизиты из кабинета
    $a = bt_addresses((int)$USER->GetID())[0] ?? null;
    $u += $a ? ['street' => $a['street'], 'flat' => $a['flat'], 'entrance' => $a['entr']] : [];
    $u += bt_profiles((int)$USER->GetID(), 'UR')[0]['v'] ?? [];
    if ($a && $a['loc'] !== '' && $a['city'] !== '') {
        $popular = array_merge([['code' => $a['loc'], 'n' => $a['city'], 'r' => '']], array_values(array_filter($popular, fn($c) => $c['code'] !== $a['loc'])));
    }
}
$co = bt_contacts();
?>
<div class="wrap cop">
  <nav class="crumbs" aria-label="Хлебные крошки"><a href="/">Главная</a><span><a href="/personal/cart/">Корзина</a></span><span>Оформление</span></nav>
  <div class="pagehead"><h1 class="display h1">Оформление заказа</h1></div>

  <div class="steps"><div class="done"><b>1</b>Корзина</div><div class="cur"><b>2</b>Регион и доставка</div><div><b>3</b>Оплата</div><div><b>4</b>Подтверждение</div></div>

  <form class="co" id="coForm" novalidate>
    <div>
      <div class="blk">
        <h2><b>1</b>Получатель</h2>
        <div class="pill-tabs" id="ptypeTabs" role="tablist"><button type="button" role="tab" aria-selected="true" data-t="FIZ">Физическое лицо</button><button type="button" role="tab" aria-selected="false" data-t="UR">Юрлицо или ИП</button></div>
        <div id="urFields" hidden style="margin-top:18px">
          <div class="f2">
            <div class="field"><label for="coCompany">Название организации *</label><input id="coCompany" name="company" placeholder="ООО «Ромашка»" autocomplete="organization" value="<?= $e($u['COMPANY'] ?? '') ?>"></div>
            <div class="field"><label for="coInn">ИНН *</label><input id="coInn" name="inn" placeholder="10 или 12 цифр" inputmode="numeric" maxlength="12" value="<?= $e($u['INN'] ?? '') ?>"></div>
            <div class="field"><label for="coKpp">КПП</label><input id="coKpp" name="kpp" placeholder="9 цифр, если есть" inputmode="numeric" maxlength="9" value="<?= $e($u['KPP'] ?? '') ?>"></div>
            <div class="field"><label for="coAdr">Юридический адрес</label><input id="coAdr" name="company_adr" value="<?= $e($u['COMPANY_ADR'] ?? '') ?>"></div>
          </div>
          <div class="alert alert--info" style="margin-bottom:18px">Выставим счёт, после оплаты — УПД. Отсрочка платежа для постоянных клиентов — по договорённости.</div>
        </div>
        <div class="f2" style="margin-top:18px">
          <div class="field"><label for="coName" id="coNameL">Имя *</label><input id="coName" name="name" autocomplete="name" value="<?= $e($u['name']) ?>"></div>
          <div class="field"><label for="coPhone">Телефон *</label><input id="coPhone" name="phone" type="tel" autocomplete="tel" placeholder="+7 ___ ___-__-__" value="<?= $e($u['phone']) ?>"></div>
          <div class="field" style="grid-column:1/-1"><label for="coEmail">E‑mail *</label><input id="coEmail" name="email" type="email" autocomplete="email" placeholder="Сюда придёт подтверждение заказа" value="<?= $e($u['email']) ?>"></div>
        </div>
      </div>

      <div class="blk">
        <h2><b>2</b>Город доставки</h2>
        <div class="field city"><label for="cityIn">Населённый пункт *</label>
          <input id="cityIn" name="city" value="<?= $e($popular[0]['n'] ?? '') ?>" autocomplete="off" placeholder="Начните вводить город" role="combobox" aria-autocomplete="list" aria-controls="cityList" aria-expanded="false">
          <input type="hidden" name="loc" id="locIn" value="<?= $e($popular[0]['code'] ?? '') ?>">
          <ul id="cityList" role="listbox"></ul>
        </div>
        <p class="muted" style="font-size:13px;margin:0">От города зависят доступные способы доставки и стоимость.</p>
      </div>

      <div class="blk" id="blkD">
        <h2><b>3</b>Доставка</h2>
        <div class="pill-tabs dtabs" id="dTabs" role="tablist"><button type="button" role="tab" data-tab="addr">Курьером</button><button type="button" role="tab" data-tab="pvz">Пункт выдачи</button><button type="button" role="tab" data-tab="pickup">Самовывоз</button></div>
        <div class="opts" id="deliv"></div>
        <div id="addr" hidden style="margin-top:18px">
          <div class="f2"><div class="field" style="grid-column:1/-1"><label for="coStreet">Улица, дом *</label><input id="coStreet" name="street" placeholder="ул. Ленина, 10" autocomplete="street-address" value="<?= $e($u['street'] ?? '') ?>"></div>
            <div class="field"><label for="coFlat">Квартира / офис</label><input id="coFlat" name="flat" value="<?= $e($u['flat'] ?? '') ?>"></div><div class="field"><label for="coEntr">Подъезд, этаж, домофон</label><input id="coEntr" name="entrance" value="<?= $e($u['entrance'] ?? '') ?>"></div></div>
        </div>
        <div id="pvz" hidden style="margin-top:18px">
          <div class="field"><label for="coPvz">Адрес пункта выдачи СДЭК *</label><input id="coPvz" name="pvz" placeholder="Например: ул. Малышева, 51"><span class="hint muted">Удобный пункт можно найти на cdek.ru — менеджер сверит адрес при звонке</span></div>
        </div>
        <div id="pickupNote" class="alert alert--info" hidden style="margin-top:14px">Самовывоз: <?= $e(($co['city'] ?? '') . ', ' . ($co['street'] ?? '')) ?>. Заберите <?= $e(mb_strtolower($co['hours'] ?? '')) ?> после звонка менеджера о готовности заказа.</div>
      </div>

      <div class="blk" id="blkP">
        <h2><b>4</b>Оплата</h2>
        <div class="opts" id="pay"></div>
      </div>

      <div class="blk">
        <h2><b>5</b>Комментарий</h2>
        <div class="field"><label for="coComment">Комментарий к заказу</label><textarea id="coComment" name="comment" rows="3" maxlength="2000" placeholder="Например: позвонить за час до доставки"></textarea></div>
        <label class="check check--top"><input type="checkbox" name="agree" value="Y" id="coAgree"> <span>Согласен с <a class="link" href="/polzovatelskoe-soglashenie/" target="_blank">пользовательским соглашением</a> и <a class="link" href="/politika-konfidencialnosti/" target="_blank">политикой конфиденциальности</a></span></label>
      </div>
    </div>

    <aside class="sum">
      <div id="sumItems"><?php foreach ($basket as $bi):
          $m = bt_product((string)$bi->getProductId()); ?>
        <div class="it"><?php if (!empty($m['img'])): ?><img src="<?= $e($m['img']) ?>" alt="" loading="lazy" width="44" height="44"><?php endif ?><span><?= $e($bi->getField('NAME')) ?> × <?= (float)$bi->getQuantity() ?></span><b><?= bt_fmt($bi->getFinalPrice()) ?></b></div>
      <?php endforeach ?></div>
      <div class="l" style="margin-top:8px"><span>Товары</span><span id="sSub"><?= bt_fmt($basket->getPrice()) ?></span></div>
      <div class="l"><span>Доставка</span><span id="sDel">—</span></div>
      <div class="l t"><span>К оплате</span><span id="sTot"><?= bt_fmt($basket->getPrice()) ?></span></div>
      <div class="eta" id="sEta">&nbsp;</div>
      <p class="left" id="sLeft" aria-live="polite">&nbsp;</p>
      <button class="btn btn--block" type="submit" id="submit" disabled>Подтвердить заказ</button>
      <p class="err-form" id="sErr" role="alert"></p>
      <p class="muted" style="font-size:12px;margin:12px 0 0;text-align:center">Нажимая кнопку, вы подтверждаете заказ. Менеджер свяжется для уточнения деталей.</p>
    </aside>
  </form>
</div>
<script>
window.BT_CO_DATA=<?= Json::encode(['popular' => $popular, 'pays' => $pays]) ?>;
document.addEventListener('DOMContentLoaded',()=>{
  const {popular:POP,pays:PAYS}=BT_CO_DATA, fmt=BT_fmt, form=coForm;
  const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const post=(data)=>{const fd=new FormData();Object.entries(data).forEach(([k,v])=>fd.append(k,v));fd.append('sessid',BX.bitrix_sessid());
    return fetch('/local/ajax/order.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json());};
  let pt='FIZ', dl=[], avail=[], sel=null, tab='addr', payId=0, calcN=0, sending=false;

  /* доставки Битрикса → варианты макета: СДЭК до двери и в пункт выдачи — одна служба с разным адресом */
  const opts=()=>dl.flatMap(d=>d.code==='bt_cdek'
    ?[{key:'cdek_door',tab:'addr',d,t:'СДЭК — курьер до двери',desc:'Доставка по адресу, 2–7 дней'},{key:'cdek_pvz',tab:'pvz',d,t:'СДЭК — пункт выдачи',desc:'Заберите в удобном пункте, 2–7 дней'}]
    :[{key:d.code||'d'+d.id,tab:d.code==='bt_pickup'?'pickup':'addr',d,t:d.name,desc:d.desc}]);
  const dateOf=o=>{const x=BT_dates(1);
    if(o.tab==='pickup') return 'готов '+(x.relPack||BT_fmtDate(x.pack,true));
    if(o.d.code==='bt_courier') return (x.relDeliver?x.relDeliver+', ':'')+BT_fmtDate(x.deliver,true);
    return '';};
  const priceOf=o=>o.d.code==='bt_cdek'?'сообщит менеджер':o.d.price===0?'бесплатно':fmt(o.d.price);
  function renderDeliv(){
    const all=opts();
    [...dTabs.children].forEach(b=>{const on=all.some(o=>o.tab===b.dataset.tab);
      b.setAttribute('aria-selected',b.dataset.tab===tab);b.setAttribute('aria-disabled',!on);
      b.title=on?'':'Недоступно для выбранного города';});
    const list=all.filter(o=>o.tab===tab);
    deliv.innerHTML=list.length?list.map(o=>{const free=o.d.price===0&&o.d.base>0;
      return `<label class="radio-card ${sel&&sel.key===o.key?'on':''}"><input type="radio" name="dopt" value="${o.key}" ${sel&&sel.key===o.key?'checked':''}>
        <div style="flex:1"><div class="t"><span>${esc(o.t)}${free?' <span class="badge badge--ok">Бесплатно</span>':''}</span><span class="p">${priceOf(o)}${dateOf(o)?`<span class="dd">${dateOf(o)}</span>`:''}</span></div>
        <div class="d">${esc(o.desc)}</div></div></label>`;}).join('')
      :`<p class="muted" style="margin:0;font-size:14px">${dl.length?'Для выбранного города этот способ недоступен — выберите другой.':'Выберите город из списка, чтобы увидеть способы доставки.'}</p>`;
    addr.hidden=!sel||sel.tab!=='addr'; pvz.hidden=!sel||sel.tab!=='pvz'; pickupNote.hidden=!sel||sel.tab!=='pickup';
  }
  function renderPay(){
    pay.innerHTML=PAYS.map(p=>{const on=avail.some(a=>a.id===p.id);
      const why=p.code==='bill'?'Только для юрлиц и ИП':p.code==='cash'&&pt==='UR'?'Юрлица оплачивают по счёту':'Недоступно для выбранной доставки';
      const desc=p.code==='cash'&&sel&&sel.d.code==='bt_cdek'?'Наложенным платежом при получении в СДЭК':p.desc;
      return `<label class="radio-card ${on&&payId===p.id?'on':''} ${on?'':'off'}"><input type="radio" name="pay" value="${p.id}" ${on&&payId===p.id?'checked':''} ${on?'':'disabled'}>
        <div><div class="t">${esc(p.name)}</div><div class="d">${esc(on?desc:why)}</div></div></label>`;}).join('');
  }
  function renderSum(r){ if(!r) return;
    const cdek=sel&&sel.d.code==='bt_cdek';
    sDel.textContent=!sel?'—':cdek?'сообщит менеджер':r.deliveryPrice===0?'бесплатно':fmt(r.deliveryPrice);
    sTot.textContent=fmt(r.total)+(cdek?' + доставка':'');
    const p=PAYS.find(x=>x.id===payId);
    sEta.innerHTML=sel?`${esc(sel.t)}${dateOf(sel)?` · <b>${dateOf(sel)}</b>`:''}${p?' · '+esc(p.name.charAt(0).toLowerCase()+p.name.slice(1)):''}`:'&nbsp;';
  }
  let last=null;
  function calc(again){
    const n=++calcN;
    return post({action:'calc',ptype:pt,loc:locIn.value,delivery:sel?sel.d.id:0,pay:payId}).then(r=>{
      if(n!==calcN||!r.ok) return;
      dl=r.deliveries; avail=r.pays; last=r;
      const all=opts();
      if(!sel||!all.some(o=>o.key===sel.key)) sel=all.find(o=>o.tab===tab)||all[0]||null;
      else sel=all.find(o=>o.key===sel.key);
      if(sel) tab=sel.tab;
      if(sel&&sel.d.id!==r.delivery&&!again) return calc(true);
      payId=avail.some(a=>a.id===payId)?payId:(avail[0]?avail[0].id:0);
      renderDeliv(); renderPay(); renderSum(r); ready();
    }).catch(()=>BT_toast('Не получилось пересчитать доставку — обновите страницу'));
  }
  dTabs.addEventListener('click',e=>{const b=e.target.closest('[data-tab]');if(!b||b.getAttribute('aria-disabled')==='true')return;
    tab=b.dataset.tab; const o=opts().find(x=>x.tab===tab); if(o){const same=sel&&sel.d.id===o.d.id; sel=o; if(same){renderDeliv();renderPay();renderSum(last);ready();}else calc();}});
  deliv.addEventListener('change',e=>{const o=opts().find(x=>x.key===e.target.value);if(!o)return;const same=sel&&sel.d.id===o.d.id;sel=o;
    if(same){renderDeliv();renderPay();renderSum(last);ready();}else calc();});
  pay.addEventListener('change',e=>{payId=+e.target.value;renderPay();renderSum(last);ready();});
  ptypeTabs.addEventListener('click',e=>{const b=e.target.closest('[data-t]');if(!b||b.dataset.t===pt)return;
    [...ptypeTabs.children].forEach(x=>x.setAttribute('aria-selected',x===b));pt=b.dataset.t;urFields.hidden=pt!=='UR';
    coNameL.textContent=pt==='UR'?'Имя контактного лица *':'Имя *';calc();ready();});

  /* город: популярные из макета + поиск по местоположениям Битрикса */
  const cityRow=c=>`<li role="option" data-code="${c.code}" data-n="${esc(c.n)}">${esc(c.n)}${c.r?`<small>${esc(c.r)}</small>`:''}</li>`;
  const showCities=list=>{cityList.innerHTML=list.map(cityRow).join('');const o=list.length>0;cityList.classList.toggle('open',o);cityIn.setAttribute('aria-expanded',o);};
  let cityT=0, cityN=0;
  cityIn.addEventListener('input',()=>{locIn.value='';dl=[];avail=[];sel=null;renderDeliv();renderPay();ready();
    const v=cityIn.value.trim(); clearTimeout(cityT);
    if(v.length<2){showCities(POP.filter(c=>c.n.toLowerCase().startsWith(v.toLowerCase())));return;}
    const n=++cityN; cityT=setTimeout(()=>post({action:'city',q:v}).then(r=>{if(n===cityN&&document.activeElement===cityIn)showCities(r.list||[]);}),200);});
  cityIn.addEventListener('focus',()=>{if(!locIn.value)cityIn.dispatchEvent(new Event('input'));else showCities(POP);});
  cityList.addEventListener('mousedown',e=>e.preventDefault());
  cityList.addEventListener('click',e=>{const li=e.target.closest('li');if(!li)return;
    cityIn.value=li.dataset.n;locIn.value=li.dataset.code;showCities([]);setErr(cityIn,'');
    calc().then(()=>BT_toast('Доставка пересчитана для города '+esc(li.dataset.n)));});
  cityIn.addEventListener('blur',()=>{showCities([]);if(cityIn.value.trim()&&!locIn.value)setTimeout(()=>setErr(cityIn,'Выберите город из списка'),0);});
  cityIn.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();const li=cityList.querySelector('li');if(li&&cityList.classList.contains('open'))li.click();}
    if(e.key==='Escape')showCities([]);});

  /* ошибки под полем — та же разметка, что у BT_checkField */
  function setErr(el,msg){
    const f=el.closest('.field')||el.closest('.check'); if(!f) return;
    if(f.classList.contains('check')){f.classList.toggle('is-err',!!msg);return;}
    let s=f.querySelector('.err'); if(!s){s=document.createElement('span');s.className='err';s.id='e'+el.name;f.appendChild(s);}
    f.classList.toggle('is-err',!!msg); s.textContent=msg||'';
    if(msg){el.setAttribute('aria-invalid','true');el.setAttribute('aria-describedby',s.id);}else{el.removeAttribute('aria-invalid');el.removeAttribute('aria-describedby');}
  }
  /* тихая проверка: без подсветки, только кнопка и подсказка «осталось указать» */
  const val=n=>(form.elements[n]?.value||'').trim(), dig=n=>val(n).replace(/\D/g,'');
  function ready(){
    const miss=[];
    if(pt==='UR'){ if(!val('company'))miss.push('название организации'); if(![10,12].includes(dig('inn').length))miss.push('ИНН'); }
    if(val('name').length<2)miss.push(pt==='UR'?'контактное лицо':'имя');
    if(dig('phone').length!==11)miss.push('телефон');
    if(!/^[^\s@]+@[^\s@]+\.[a-zA-Zа-яА-Я]{2,}$/.test(val('email')))miss.push('e‑mail');
    if(!locIn.value)miss.push('город');
    else if(!sel)miss.push('способ доставки');
    else if(sel.tab==='addr'&&!val('street'))miss.push('адрес доставки');
    else if(sel.tab==='pvz'&&!val('pvz'))miss.push('пункт выдачи');
    if(locIn.value&&!payId)miss.push('способ оплаты');
    if(!coAgree.checked)miss.push('согласие с условиями');
    sLeft.textContent=miss.length?'Осталось указать: '+miss.join(', '):'Всё заполнено — можно подтверждать';
    submit.disabled=!!miss.length||sending;
    return !miss.length;
  }
  form.addEventListener('input',ready); form.addEventListener('change',ready);

  form.addEventListener('submit',e=>{const bad=e.defaultPrevented; e.preventDefault(); if(bad||sending||!ready()) return;
    sending=true; submit.disabled=true; submit.textContent='Оформляем…'; sErr.textContent='';
    const data={action:'create',ptype:pt,delivery:sel.d.id,mode:sel.tab,pay:payId,agree:coAgree.checked?'Y':''};
    [...form.elements].forEach(el=>{if(el.name&&el.type!=='radio'&&el.type!=='checkbox'&&!(el.name in data))data[el.name]=el.value;});
    post(data).then(r=>{
      if(r.ok){
        Object.keys(BT_CART).forEach(k=>delete BT_CART[k]);
        try{localStorage.setItem('bt_cart','{}');}catch(x){}
        window.BT_BASKET={items:{},sum:0}; BT_cartUpdate(false);
        location.href=r.redirect; return;
      }
      sending=false; submit.textContent='Подтвердить заказ';
      let first=null;
      Object.entries(r.errors||{}).forEach(([k,m])=>{const el=form.elements[k];
        if(el&&el.type!=='hidden'){setErr(el,m);first=first||el;} else sErr.textContent=m;});
      if(r.errors&&r.errors.agree){setErr(coAgree,'x');first=first||coAgree;}
      if(first){first.focus({preventScroll:true});first.scrollIntoView({behavior:'smooth',block:'center'});}
      BT_toast('Проверьте выделенные поля'); ready();
    }).catch(()=>{sending=false;submit.textContent='Подтвердить заказ';sErr.textContent='Нет связи с сервером — попробуйте ещё раз';ready();});
  });
  renderDeliv(); renderPay(); ready(); calc();
});
</script>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
