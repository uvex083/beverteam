<?php
// пустая корзина — редирект до вывода шапки: после неё обработчики буфера с кешем дают белый экран
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Sale;

Loader::includeModule('sale');
$basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID)->getOrderableItems();
if ($basket->isEmpty()) {
    // «Назад» со страницы банка: корзина уже стала заказом — ведём на его страницу, там «Оплатить»
    $last = (int)(array_slice((array)($_SESSION['BT_ORDERS'] ?? []), -1)[0] ?? 0);
    $lo = $last ? Sale\Order::load($last) : null;
    LocalRedirect($lo && !$lo->isCanceled() && bt_tbank_payment($lo) && $lo->getDateInsert()->getTimestamp() > time() - 3600 ? '/personal/order/success/?id=' . $last : '/personal/cart/');
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Оформление заказа');
$APPLICATION->SetPageProperty('title', 'Оформление заказа — BEVERTEAM');
$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
$e = fn($s) => htmlspecialcharsbx((string)$s);
$nItems = 0;
$lines = [];
foreach ($basket as $bi) {
    $m = bt_product((string)$bi->getProductId());
    $q = ($pk = bt_basket_pack($bi)) ? (int)round($bi->getQuantity() / $pk) : (int)$bi->getQuantity();
    $nItems += $q;
    $lines[] = ['n' => $bi->getField('NAME'), 'img' => $m['img'] ?? '', 'url' => $m['url'] ?? '', 'q' => $q, 'qty' => bt_basket_qty($bi), 'u' => $pk === 1 ? 'кг' : ($pk ? 'уп.' : 'шт.'),
        'sum' => (float)$bi->getFinalPrice(), 'full' => (float)($m['old'] ?? $m['p'] ?? 0) * $q];
}
$nWord = $nItems . ' ' . (($nItems % 10 === 1 && $nItems % 100 !== 11) ? 'товар' : (in_array($nItems % 10, [2, 3, 4]) && !in_array($nItems % 100, [12, 13, 14]) ? 'товара' : 'товаров'));

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
foreach (Sale\PaySystem\Manager::getList(['filter' => ['=ACTIVE' => 'Y', '!=ACTION_FILE' => 'inner'], 'order' => ['SORT' => 'ASC'], 'select' => ['ID', 'NAME', 'DESCRIPTION', 'ACTION_FILE', 'XML_ID']]) as $ps) {
    $xml = (string)$ps['XML_ID'];
    $pays[] = ['id' => (int)$ps['ID'], 'name' => $ps['NAME'], 'desc' => (string)$ps['DESCRIPTION'], 'code' => $ps['ACTION_FILE'], 'sbp' => $xml === 'bt_sbp',
        'sberpay' => $xml === 'bt_sberpay', 'logo' => $ps['ACTION_FILE'] === 'tinkoff' ? bt_tbank_logos($xml) : ''];
}

$u = ['name' => '', 'email' => '', 'phone' => ''];
$addrs = [];
$orgs = [];
$ship = [];
$orgSel = 0;
if ($USER->IsAuthorized()) {
    $cu = CUser::GetByID($USER->GetID())->Fetch() ?: [];
    $u = ['name' => trim(($cu['NAME'] ?? '') . ' ' . ($cu['LAST_NAME'] ?? '')), 'email' => $cu['EMAIL'] ?? '', 'phone' => $cu['PERSONAL_PHONE'] ?: ($cu['PERSONAL_MOBILE'] ?? '')];
    // основной адрес и первые реквизиты из кабинета
    $addrs = array_map(fn($x) => ['loc' => $x['loc'], 'city' => $x['city'], 'street' => $x['street'], 'flat' => $x['flat'], 'entr' => $x['entr']], bt_addresses((int)$USER->GetID()));
    $a = $addrs[0] ?? null;
    $u += $a ? ['street' => $a['street'], 'flat' => $a['flat'], 'entrance' => $a['entr']] : [];
    // из прошлого заказа — плательщик, способы доставки и оплаты, организация; адрес — основной из кабинета, а нет его — из прошлого заказа
    $ship = CUserOptions::GetOption('bt', 'last_ship', [], (int)$USER->GetID());
    $ship = is_array($ship) ? $ship : [];
    if (!$a && ($ship['street'] ?? '') !== '') {
        $u = ['street' => $ship['street'], 'flat' => $ship['flat'] ?? '', 'entrance' => $ship['entrance'] ?? ''] + $u;
        $addrs[] = ['loc' => $ship['loc'] ?? '', 'city' => bt_loc($ship['loc'] ?? '')['n'], 'street' => $ship['street'], 'flat' => $ship['flat'] ?? '', 'entr' => $ship['entrance'] ?? ''];
    }
    $orgs = bt_profiles((int)$USER->GetID(), 'UR');
    $orgSel = (int)CUserOptions::GetOption('bt', 'last_org', 0, (int)$USER->GetID());
    $orgSel = in_array($orgSel, array_column($orgs, 'id'), true) ? $orgSel : (int)($orgs[0]['id'] ?? 0);
    foreach ($orgs as $o) {
        $o['id'] === $orgSel and $u += $o['v'];
    }
    $city = $a ?: (($ship['loc'] ?? '') !== '' ? ['loc' => $ship['loc'], 'city' => bt_loc($ship['loc'])['n']] : null);
    if ($city && $city['loc'] !== '' && $city['city'] !== '') {
        $popular = array_merge([['code' => $city['loc'], 'n' => $city['city'], 'r' => '']], array_values(array_filter($popular, fn($c) => $c['code'] !== $city['loc'])));
    }
}
$ptDef = ($ship['pt'] ?? '') === 'UR' ? 'UR' : 'FIZ';
$co = bt_contacts();
?>
<div class="wrap cop">
  <nav class="crumbs" aria-label="Хлебные крошки"><a href="/">Главная</a><span><a href="/personal/cart/">Корзина</a></span><span>Оформление</span></nav>
  <div class="pagehead"><h1 class="display h1">Оформление заказа</h1></div>


  <form class="co" id="coForm" novalidate>
    <div class="hp" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
    <div>
      <div class="blk">
        <h2><b>1</b>Получатель</h2>
        <?php if (!$USER->IsAuthorized()): ?><div class="coidp" hidden><div data-idp-row="Войти через:"></div></div>
        <div class="f2 coemail"><div class="field"><label for="coEmail">E‑mail *</label><input id="coEmail" name="email" type="email" autocomplete="email" placeholder="mail@company.ru" value="<?= $e($u['email']) ?>"></div>
          <p class="coemail__note muted">Сюда придёт подтверждение заказа. Если вы уже покупали у нас — предложим войти и заполним данные сами.</p></div>
        <div class="corec" id="coRec" hidden><div><b id="coRecT"></b><span id="coRecD"></span></div><button type="button" class="btn btn--dark" id="coRecBtn">Войти по коду</button></div>
        <?php endif ?>
        <div class="pill-tabs" id="ptypeTabs" role="tablist"><button type="button" role="tab" aria-selected="<?= $ptDef === 'FIZ' ? 'true' : 'false' ?>" data-t="FIZ">Физическое лицо</button><button type="button" role="tab" aria-selected="<?= $ptDef === 'UR' ? 'true' : 'false' ?>" data-t="UR">Юрлицо или ИП</button></div>
        <div id="urFields"<?= $ptDef === 'UR' ? '' : ' hidden' ?> style="margin-top:18px">
          <?php if ($orgs): ?>
          <div class="orgs" id="orgs" role="radiogroup" aria-label="Организация">
            <?php foreach ($orgs as $o): ?>
            <label class="radio-card<?= $o['id'] === $orgSel ? ' on' : '' ?>"><input type="radio" name="org" value="<?= $o['id'] ?>"<?= $o['id'] === $orgSel ? ' checked' : '' ?>
              data-v="<?= $e(Json::encode(['company' => $o['v']['COMPANY'] ?? $o['name'], 'inn' => $o['v']['INN'] ?? '', 'kpp' => $o['v']['KPP'] ?? '', 'company_adr' => $o['v']['COMPANY_ADR'] ?? ''])) ?>">
              <div><div class="t"><?= $e($o['v']['COMPANY'] ?? $o['name']) ?></div><div class="d">ИНН <?= $e($o['v']['INN'] ?? '—') ?><?= ($o['v']['KPP'] ?? '') !== '' ? ' · КПП ' . $e($o['v']['KPP']) : '' ?></div></div></label>
            <?php endforeach ?>
            <label class="radio-card"><input type="radio" name="org" value="0"><div><div class="t">Другая организация</div><div class="d">Заполнить реквизиты</div></div></label>
          </div>
          <?php endif ?>
          <div class="f2" id="orgFields"<?= $orgs ? ' hidden' : '' ?>>
            <div class="field"><label for="coCompany">Название организации *</label><input id="coCompany" name="company" placeholder="ООО «Ромашка»" autocomplete="organization" value="<?= $e($u['COMPANY'] ?? '') ?>"></div>
            <div class="field"><label for="coInn">ИНН *</label><input id="coInn" name="inn" placeholder="10 или 12 цифр" inputmode="numeric" maxlength="12" value="<?= $e($u['INN'] ?? '') ?>"></div>
            <div class="field"><label for="coKpp">КПП</label><input id="coKpp" name="kpp" placeholder="9 цифр, если есть" inputmode="numeric" maxlength="9" value="<?= $e($u['KPP'] ?? '') ?>"></div>
            <div class="field"><label for="coAdr">Юридический адрес</label><input id="coAdr" name="company_adr" value="<?= $e($u['COMPANY_ADR'] ?? '') ?>"></div>
          </div>
          <div class="alert alert--info" style="margin-bottom:18px">Выставим счёт, после оплаты — УПД.</div>
        </div>
        <div class="f2" style="margin-top:18px">
          <div class="field"><label for="coName" id="coNameL">Имя *</label><input id="coName" name="name" autocomplete="name" value="<?= $e($u['name']) ?>"></div>
          <div class="field"><label for="coPhone">Телефон *</label><input id="coPhone" name="phone" type="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" value="<?= $e($u['phone'] ? bt_phone_fmt($u['phone']) : '') ?>"></div>
          <?php if ($USER->IsAuthorized()): ?><div class="field" style="grid-column:1/-1"><label for="coEmail">E‑mail *</label><input id="coEmail" name="email" type="email" autocomplete="email" placeholder="Сюда придёт подтверждение заказа" value="<?= $e($u['email']) ?>"></div><?php endif ?>
        </div>
      </div>

      <div class="blk">
        <h2><b>2</b>Город доставки</h2>
        <div class="field city"><label for="cityIn">Населённый пункт *</label>
          <input id="cityIn" name="city" value="<?= $e($popular[0]['n'] ?? '') ?>" autocomplete="new-password" placeholder="Начните вводить город" role="combobox" aria-autocomplete="list" aria-controls="cityList" aria-expanded="false">
          <button type="button" class="fclr" data-clr aria-label="Очистить поле" hidden><svg width="10" height="10" viewBox="0 0 14 14" aria-hidden="true"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
          <input type="hidden" name="loc" id="locIn" value="<?= $e($popular[0]['code'] ?? '') ?>">
          <ul id="cityList" role="listbox" tabindex="-1"></ul>
        </div>
        <p class="muted" style="font-size:13px;margin:0">От города зависят доступные способы доставки и стоимость.</p>
      </div>

      <div class="blk" id="blkD">
        <h2><b>3</b>Доставка<span class="calcst" id="calcSt" aria-live="polite"></span></h2>
        <div class="pill-tabs dtabs" id="dTabs" role="tablist"><button type="button" role="tab" data-tab="addr">Курьером</button><button type="button" role="tab" data-tab="pickup">Самовывоз</button><button type="button" role="tab" data-tab="pvz">Пункт выдачи</button></div>
        <div class="alert cdekdown" id="cdekDown" hidden>Доставка СДЭК сейчас недоступна — сбой на стороне СДЭК. Обновите страницу через пару минут или выберите другой способ.</div>
        <div class="opts" id="deliv"></div>
        <div id="pickupNote" class="alert alert--info" hidden style="margin-top:14px">Самовывоз: <?= $e(($co['city'] ?? '') . ', ' . ($co['street'] ?? '')) ?>. Заберите <?= $e(mb_strtolower($co['hours'] ?? '')) ?> после звонка менеджера о готовности заказа.</div>
        <div id="addr" hidden style="margin-top:18px">
          <div class="f2"><div class="field city street" style="grid-column:1/-1"><label for="coStreet">Улица, дом *</label><input id="coStreet" name="street" data-v="addr" placeholder="Начните вводить улицу" autocomplete="new-password" spellcheck="false" enterkeyhint="next" role="combobox" aria-autocomplete="list" aria-controls="streetList" aria-expanded="false" value="<?= $e($u['street'] ?? '') ?>"><button type="button" class="fclr" data-clr aria-label="Очистить поле" hidden><svg width="10" height="10" viewBox="0 0 14 14" aria-hidden="true"><path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button><ul id="streetList" role="listbox" tabindex="-1"></ul></div>
            <div class="field"><label for="coFlat">Квартира / офис</label><input id="coFlat" name="flat" value="<?= $e($u['flat'] ?? '') ?>"></div><div class="field"><label for="coEntr">Подъезд, этаж, домофон</label><input id="coEntr" name="entrance" value="<?= $e($u['entrance'] ?? '') ?>"></div></div>
        </div>
        <input type="hidden" name="pvz" id="pvzIn" value="">
        <div class="coitems">
          <div class="coitems__h"><span id="coItemsH"><?= $nWord ?></span><a class="link" href="/personal/cart/">Изменить в корзине</a></div>
          <ul class="coitems__l"><?php foreach ($lines as $i => $l): ?>
            <li><button type="button" class="coitems__i" data-items="<?= $i ?>" aria-label="<?= $e($l['n'] . ' — ' . $l['qty'] . ', ' . bt_fmt($l['sum'])) ?>"><?php if ($l['img'] !== ''): ?><img src="<?= $e($l['img']) ?>" alt="" loading="lazy" width="64" height="64"><?php else: ?><span class="coitems__n"><?= $e($l['n']) ?></span><?php endif ?><?php if ($l['q'] > 1): ?><b>×<?= $l['q'] ?></b><?php endif ?></button></li>
          <?php endforeach ?></ul>
        </div>
      </div>

      <div class="blk" id="blkP">
        <h2><b>4</b>Оплата</h2>
        <div class="opts" id="pay"></div>
      </div>

      <div class="blk">
        <h2><b>5</b>Комментарий</h2>
        <div class="field"><label for="coComment">Комментарий к заказу</label><textarea id="coComment" name="comment" rows="3" maxlength="2000" placeholder="Например: позвонить за час до доставки"></textarea></div>
      </div>
    </div>

    <aside class="sum">
      <div class="l"><span>Товары, <?= $nItems ?> шт.</span><span id="sSub"><?= bt_fmt($basket->getPrice()) ?></span></div>
      <div class="l" id="sItDiscRow" hidden><span>Скидка</span><span id="sItDisc" style="color:var(--ok)"></span></div>
      <div class="l" id="sDiscRow" hidden><span>Скидка от суммы заказа</span><span id="sDisc" style="color:var(--ok)"></span></div>
      <div class="l"><span>Доставка</span><span id="sDel">—</span></div>
      <div class="l t"><span>К оплате</span><span id="sTot"><?= bt_fmt($basket->getPrice()) ?></span></div>
      <p class="totnote" id="sTotNote" hidden>Без учёта доставки — стоимость сообщит менеджер</p>
      <div class="eta" id="sEta">&nbsp;</div>
      <p class="left" id="sLeft" aria-live="polite">&nbsp;</p>
      <button class="btn btn--block" type="submit" id="submit" disabled>Оформить заказ</button>
      <p class="err-form" id="sErr" role="alert"></p>
      <p class="muted cohint" id="sHint">Нажимая кнопку, вы соглашаетесь с <a href="/soglasie-na-obrabotku/" target="_blank">условиями обработки персональных данных</a> и <a href="/polzovatelskoe-soglashenie/" target="_blank">пользовательским соглашением</a></p>
    </aside>
  </form>
  <div class="modal modal--items" id="itemsModal" role="dialog" aria-modal="true" aria-labelledby="itemsT">
    <div class="modal__bg" data-close></div>
    <div class="modal__p">
      <button class="modal__x" type="button" data-close aria-label="Закрыть">×</button>
      <h2 class="itm__t" id="itemsT">Состав заказа</h2>
      <p class="itm__s"><?= $nWord ?> на <?= bt_fmt($basket->getPrice()) ?></p>
      <ul class="itm__l"><?php foreach ($lines as $i => $l): ?>
        <li data-i="<?= $i ?>">
          <a class="itm__ph" href="<?= $e($l['url']) ?>" target="_blank" tabindex="-1" aria-hidden="true"><?php if ($l['img'] !== ''): ?><img src="<?= $e($l['img']) ?>" alt="" loading="lazy" width="72" height="72"><?php endif ?></a>
          <div class="itm__b"><a class="itm__n" href="<?= $e($l['url']) ?>" target="_blank"><?= $e($l['n']) ?></a><span class="itm__q"><?= $e($l['qty']) ?><?= $l['q'] > 1 ? ' · ' . bt_fmt($l['sum'] / $l['q']) . ' за ' . $l['u'] : '' ?></span></div>
          <div class="itm__p"><?php if ($l['full'] > $l['sum'] + 0.5): ?><del><?= bt_fmt($l['full']) ?></del><?php endif ?><b><?= bt_fmt($l['sum']) ?></b></div>
        </li>
      <?php endforeach ?></ul>
      <a class="link itm__e" href="/personal/cart/">Изменить в корзине</a>
    </div>
  </div>
  <div class="modal modal--pvz" id="pvzModal" role="dialog" aria-modal="true" aria-labelledby="pvzT">
    <div class="modal__bg" data-close></div>
    <div class="modal__p">
      <button class="modal__x" type="button" data-close aria-label="Закрыть">×</button>
      <h2 class="pvzm__t" id="pvzT">Выберите пункт выдачи</h2>
      <div class="pvzm">
        <div class="pvzm__map" id="pvzMap"><div class="pvzm__load" id="pvzLoad">Загружаем пункты выдачи…</div></div>
        <div class="pvzm__side">
          <div class="pvzm__list" id="pvzListV">
            <div class="field"><input id="pvzQ" placeholder="Улица, метро или код пункта" autocomplete="new-password" spellcheck="false" aria-label="Найти пункт выдачи"></div>
            <p class="pvzm__n" id="pvzN"></p>
            <div class="pvzm__items" id="pvzList"></div>
          </div>
          <div class="pvzm__card" id="pvzCard" hidden></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
window.BT_CO_DATA=<?= Json::encode(['popular' => $popular, 'pays' => $pays, 'addrs' => $addrs]) ?>;
document.addEventListener('DOMContentLoaded',()=>{
  const {popular:POP,pays:PAYS}=BT_CO_DATA, fmt=BT_fmt, form=coForm;
  const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const post=(data)=>{const fd=new FormData();Object.entries(data).forEach(([k,v])=>fd.append(k,v));fd.append('sessid',window.BT_SID||'');
    return fetch('/local/ajax/order.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json());};
  const N_ITEMS=<?= $nItems ?>;
  let pt=<?= Json::encode($ptDef) ?>, dl=[], avail=[], sel=null, tab=<?= Json::encode(in_array($ship['mode'] ?? '', ['addr', 'pvz', 'pickup'], true) ? $ship['mode'] : 'addr') ?>, want=<?= Json::encode((string)($ship['dkey'] ?? '')) ?>, wantPay=<?= (int)($ship['pay'] ?? 0) ?>, payId=0, calcN=0, sending=false;

  /* доставки Битрикса → варианты макета; СДЭК — службы модуля ipol.sdek: пункт выдачи и курьер */
  const isCdek=d=>/^(sdek_|bt_cdek)/.test(d.code);
  const opts=()=>dl.map(d=>({key:d.code||'d'+d.id,tab:d.code==='bt_pickup'?'pickup':d.code==='sdek_pickup'?'pvz':'addr',d,t:d.name,desc:d.desc}));
  const dateOf=o=>{const x=BT_dates();
    if(o.d.period) return o.d.period;
    if(o.tab==='pickup') return 'готов '+(x.relPack||BT_fmtDate(x.pack,true));
    if(o.d.code==='bt_courier') return (x.relDeliver?x.relDeliver+', ':'')+BT_fmtDate(x.deliver,true);
    return '';};
  const priceOf=o=>o.d.price>0?(o.d.from?'от ':'')+fmt(o.d.price):isCdek(o.d)?'сообщит менеджер':'бесплатно';
  // фокус в доставке или оплате: перерисовка и приглушение на время расчёта не должны выбрасывать его на body
  let reF=null;
  const keepF=()=>{const a=document.activeElement, c=[deliv,pay,dTabs].find(x=>x.contains(a)); if(c) reF={c,a};};
  const backF=()=>{if(!reF||deliv.inert)return; const {c,a}=reF; reF=null;
    if(document.activeElement&&document.activeElement!==document.body)return;
    (a.isConnected?a:c.querySelector('input:checked,[aria-selected="true"]'))?.focus({preventScroll:true});};
  function renderDeliv(){
    keepF();
    const all=opts();
    let nOn=0;
    [...dTabs.children].forEach(b=>{const on=all.some(o=>o.tab===b.dataset.tab); nOn+=on;
      b.setAttribute('aria-selected',b.dataset.tab===tab); b.hidden=!on;});
    dTabs.hidden=nOn<2;
    const list=all.filter(o=>o.tab===tab);
    deliv.innerHTML=list.length?list.map(o=>{const free=o.d.price===0&&o.d.base>0;
      return `<label class="radio-card ${sel&&sel.key===o.key?'on':''}"><input type="radio" name="dopt" value="${o.key}" ${sel&&sel.key===o.key?'checked':''}>
        <div style="flex:1"><div class="t"><span>${esc(o.t)}${free?' <span class="badge badge--ok">Бесплатно</span>':''}</span><span class="p">${free?`<s class="muted">${fmt(o.d.base)}</s>`:priceOf(o)}${dateOf(o)?`<span class="dd">${dateOf(o)}</span>`:''}</span></div>
        <div class="d">${esc(o.desc)}</div>${o.d.from&&/\d/.test(calcSt)?'<div class="d">Не нашли этот адрес на карте — точную стоимость доставки сообщит менеджер.</div>':''}${o.d.code==='sdek_pickup'?pvzPick():''}</div></label>`;}).join('')
      :waiting()?'<div class="radio-card skel" aria-hidden="true"></div>':`<p class="muted" style="margin:0;font-size:14px">${!locIn.value?'Выберите город из списка, чтобы увидеть способы доставки.':dl.length?'Для выбранного города этот способ недоступен — выберите другой.':'В этот город сейчас не можем рассчитать доставку — позвоните нам, подберём вариант.'}</p>`;
    addr.hidden=!sel||sel.tab!=='addr'; pickupNote.hidden=!sel||sel.tab!=='pickup';
    const nw=N_ITEMS+' '+plural(N_ITEMS,['товар','товара','товаров']), dd=sel?dateOf(sel):'';
    coItemsH.textContent=!sel?nw:sel.tab==='pickup'?(dd?'Заберёте '+dd.replace(/^готов /,''):'Самовывоз')+' · '+nw
      :sel.d.code==='bt_courier'&&dd?'Доставим '+dd+' · '+nw:sel.d.period?'Доставка '+sel.d.period+' · '+nw:nw;
    // ушли на другую вкладку — ошибка адреса, поставленная на уходе с поля, не должна ждать возвращения
    if(addr.hidden) addr.querySelectorAll('.field.is-err input').forEach(x=>setErr(x,'')); else placeClr();
    backF();
  }

  /* пункты выдачи СДЭК: карточка доставки показывает выбранный пункт, выбор — в окне с картой.
     Пункты и Яндекс Карты грузим только при открытии окна */
  let pvzCur=<?= Json::encode(($ship['pvz'] ?? '') !== '' && ($ship['pvza'] ?? '') !== '' ? ['c' => $ship['pvz'], 'a' => $ship['pvza'], 'loc' => $ship['loc'] ?? ''] : null) ?>;
  if(pvzCur&&pvzCur.loc===locIn.value) pvzIn.value=pvzCur.c; else pvzCur=null;
  const pvzCache={}; let pvzAll=[], pvzLoc='', pvzMap=null, pvzOm=null, pvzOpenId=-1;
  const pvzPick=()=>pvzCur?`<span class="pvzpick"><span class="pvzpick__a">${esc(pvzCur.a)}</span><button type="button" class="btn btn--dark btn--sm" data-pvz aria-label="Изменить пункт выдачи">Изменить</button></span>`
    :`<span class="pvzpick"><button type="button" class="btn btn--sm" data-pvz>Выбрать пункт на карте</button></span>`;
  const PIN={...BT_mapPin(false),zIndex:0}, PIN_ON={...BT_mapPin(true),zIndex:1000}, ymapsLoad=BT_ymaps;
  const plural=(n,f)=>f[n%10===1&&n%100!==11?0:n%10>=2&&n%10<=4&&(n%100<10||n%100>=20)?1:2];
  function openPvz(){
    if(!locIn.value) return;
    pvzModal.classList.add('open'); pvzCard.hidden=true; pvzListV.hidden=false;
    if(pvzLoc===locIn.value){ if(pvzMap) pvzMap.container.fitToViewport(); renderPvzList(); const c=pvzAll.findIndex(p=>p.c===pvzIn.value); if(c>=0) showPvz(c); return; }
    const loc=pvzLoc=locIn.value; pvzLoad.hidden=false; pvzLoad.textContent='Загружаем пункты выдачи…'; pvzList.innerHTML=''; pvzN.textContent=''; pvzQ.value='';
    const pts=pvzCache[loc]?Promise.resolve(pvzCache[loc]):post({action:'pvz',loc}).then(r=>pvzCache[loc]=r.list||[]);
    Promise.all([pts,ymapsLoad().catch(()=>null)]).then(([list,y])=>{ if(loc!==pvzLoc) return;
      pvzAll=list; pvzOpenId=-1; renderPvzList();
      if(!list.length){pvzLoad.textContent='В этом городе нет пунктов выдачи СДЭК — выберите доставку курьером';return;}
      if(!y){pvzLoad.textContent='Карта не загрузилась — выберите пункт из списка';return;}
      pvzLoad.hidden=true; drawPvzMap(y);
      const c=pvzAll.findIndex(p=>p.c===pvzIn.value); if(c>=0) showPvz(c);
    }).catch(()=>{pvzLoc='';pvzLoad.textContent='Не получилось загрузить пункты — попробуйте ещё раз';});
  }
  function drawPvzMap(y){
    if(!pvzMap) pvzMap=new y.Map(pvzMapBox,{center:[pvzAll[0].lat,pvzAll[0].lon],zoom:11,controls:['zoomControl','geolocationControl']},{suppressMapOpenBlock:true,yandexMapDisablePoiInteractivity:true});
    else pvzMap.geoObjects.removeAll();
    pvzOm=new y.ObjectManager({clusterize:true,gridSize:72});
    pvzOm.clusters.options.set({clusterIcons:[{href:'data:image/svg+xml,'+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="44" height="44"><circle cx="22" cy="22" r="20" fill="#0E0E0C" stroke="#fff" stroke-width="3"/></svg>'),size:[44,44],offset:[-22,-22]}],
      clusterIconContentLayout:y.templateLayoutFactory.createClass('<div class="pvzm__cl">{{ properties.geoObjects.length }}</div>')});
    pvzOm.objects.options.set(PIN);
    pvzOm.add({type:'FeatureCollection',features:pvzAll.map((p,i)=>({type:'Feature',id:i,geometry:{type:'Point',coordinates:[p.lat,p.lon]},properties:{hintContent:esc(p.a)}}))});
    pvzOm.objects.events.add('click',e=>showPvz(e.get('objectId'),true));
    pvzMap.geoObjects.add(pvzOm);
    if(pvzAll.length>1) pvzMap.setBounds(pvzOm.getBounds(),{checkZoomRange:true,zoomMargin:40}); else pvzMap.setCenter([pvzAll[0].lat,pvzAll[0].lon],15);
    pvzMap.container.fitToViewport();
  }
  function renderPvzList(){
    const q=pvzQ.value.trim().toLowerCase().replace(/ё/g,'е');
    const list=pvzAll.map((p,i)=>[p,i]).filter(([p])=>!q||(p.a+' '+p.m+' '+p.c).toLowerCase().replace(/ё/g,'е').includes(q));
    pvzN.textContent=pvzAll.length?(q?`Найдено: ${list.length}`:`${pvzAll.length} ${plural(pvzAll.length,['пункт','пункта','пунктов'])} в городе`):'';
    pvzList.innerHTML=list.slice(0,150).map(([p,i])=>`<button type="button" class="pvzm__it${p.c===pvzIn.value?' on':''}" data-i="${i}"><b>${esc(p.a)}</b><span>${esc([p.m&&'м. '+p.m,p.w].filter(Boolean).join(' · '))}</span></button>`).join('')
      +(list.length>150?'<p class="pvzm__n">Уточните поиск, чтобы увидеть остальные</p>':'')+(q&&!list.length?'<p class="pvzm__n">Ничего не нашли — попробуйте другую улицу</p>':'');
  }
  function showPvz(i,fromMap){
    const p=pvzAll[i]; if(!p) return;
    if(pvzOm){ if(pvzOpenId>=0&&pvzOpenId!==i) pvzOm.objects.setObjectOptions(pvzOpenId,PIN); pvzOm.objects.setObjectOptions(i,PIN_ON); }
    pvzOpenId=i;
    if(pvzMap) pvzMap.setCenter([p.lat,p.lon],Math.max(pvzMap.getZoom(),15),{duration:fromMap?200:300});
    const pay=[p.card&&'картой',p.cash&&'наличными'].filter(Boolean).join(' или ');
    pvzCard.innerHTML=`<button type="button" class="pvzm__back" data-back>← Все пункты</button>
      <div class="pvzm__code">Пункт ${esc(p.c)}</div><h3 class="pvzm__a">${esc(p.a)}</h3>
      <dl class="pvzm__dl">${p.w?`<dt>Время работы</dt><dd>${esc(p.w)}</dd>`:''}${p.m?`<dt>Метро</dt><dd>${esc(p.m)}</dd>`:''}${p.n?`<dt>Как найти</dt><dd>${esc(p.n)}</dd>`:''}${pay?`<dt>Оплата при получении</dt><dd>${pay}</dd>`:''}</dl>
      ${p.img&&p.img.length?`<div class="pvzm__img">${p.img.slice(0,4).map(u=>`<img src="${esc(u)}" alt="" loading="lazy">`).join('')}</div>`:''}
      <button type="button" class="btn btn--block pvzm__take" data-take="${i}">Заберу здесь</button>`;
    pvzListV.hidden=true; pvzCard.hidden=false; pvzCard.scrollTop=0;
    if(fineP&&pvzModal.contains(document.activeElement)) pvzCard.querySelector('[data-take]').focus({preventScroll:true});
  }
  function takePvz(i){ const p=pvzAll[i]; if(!p) return;
    pvzCur={c:p.c,a:p.a,loc:pvzLoc}; pvzIn.value=p.c; pvzModal.classList.remove('open'); renderDeliv(); ready(); }
  pvzQ.addEventListener('input',renderPvzList);
  pvzQ.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();const b=pvzList.querySelector('.pvzm__it');if(b)b.click();}});
  pvzList.addEventListener('click',e=>{const b=e.target.closest('[data-i]');if(b)showPvz(+b.dataset.i);});
  pvzCard.addEventListener('click',e=>{ if(e.target.closest('[data-back]')){pvzCard.hidden=true;pvzListV.hidden=false;renderPvzList();return;}
    const t=e.target.closest('[data-take]'); if(t) takePvz(+t.dataset.take); });
  pvzModal.addEventListener('click',e=>{if(e.target.closest('[data-close]'))pvzModal.classList.remove('open');});
  // с клавиатуры и мышью: на телефоне фокус в поле поиска выдвинул бы клавиатуру поверх карты
  const fineP=matchMedia('(pointer:fine)').matches;
  new MutationObserver(()=>{ const o=pvzModal.classList.contains('open');
    if(o&&fineP) setTimeout(()=>(pvzListV.hidden?pvzCard.querySelector('button'):pvzQ).focus({preventScroll:true}),50);
    if(!o&&(pvzModal.contains(document.activeElement)||document.activeElement===document.body)) deliv.querySelector('[data-pvz]')?.focus({preventScroll:true});
  }).observe(pvzModal,{attributes:true,attributeFilter:['class']});
  pvzModal.addEventListener('keydown',e=>{ if(e.key!=='Tab')return;
    const f=[...pvzModal.querySelectorAll('button,input,[tabindex="0"]')].filter(x=>!x.closest('[hidden]')&&x.offsetParent!==null);
    if(!f.length)return; const i=f.indexOf(document.activeElement);
    if(e.shiftKey&&i<=0){e.preventDefault();f[f.length-1].focus();} else if(!e.shiftKey&&i===f.length-1){e.preventDefault();f[0].focus();} });
  /* кнопка в карточке или клик по карточке, пока пункт не выбран, — открывают окно */
  deliv.addEventListener('click',e=>{const card=e.target.closest('.radio-card'); const r=card&&card.querySelector('input[value="sdek_pickup"]'); if(!r) return;
    const btn=e.target.closest('[data-pvz]'); if(btn) e.preventDefault();
    if(!btn&&pvzIn.value) return;
    if(!r.checked){r.checked=true;r.dispatchEvent(new Event('change',{bubbles:true}));}
    openPvz();});
  const pvzMapBox=document.getElementById('pvzMap');
  function renderPay(){
    keepF();
    const list=PAYS.filter(p=>avail.some(a=>a.id===p.id));
    pay.innerHTML=list.length?list.map(p=>{const desc=p.code==='cash'&&sel&&isCdek(sel.d)?'Наложенным платежом при получении в СДЭК':p.desc;
      return `<label class="radio-card ${payId===p.id?'on':''}"><input type="radio" name="pay" value="${p.id}" ${payId===p.id?'checked':''}>
        <div><div class="t">${esc(p.name)}</div><div class="d">${esc(desc)}</div>${p.logo||''}</div></label>`;}).join('')
      :waiting()?'<div class="radio-card skel" aria-hidden="true"></div><div class="radio-card skel" aria-hidden="true"></div>':`<p class="muted" style="margin:0;font-size:14px">${locIn.value?'Сначала выберите способ доставки.':'Выберите город из списка, чтобы увидеть способы оплаты.'}</p>`;
    backF();
  }
  // «Товары» и «Скидка» — как в корзине: по обычной цене, скидка ступеней и старой цены отдельной строкой
  function subRows(base){ const full=Math.max(base,BT_cartItems().reduce((a,c)=>a+(c.old||c.p)*c.q,0)), itd=Math.round((full-base)*100)/100;
    sSub.textContent=fmt(full); sItDiscRow.hidden=!(itd>0); sItDisc.textContent='−'+fmt(itd); }
  function renderSum(r){ if(!r) return;
    subRows(r.base??r.sum);
    sDiscRow.hidden=!(r.disc>0); sDisc.textContent='−'+fmt(r.disc||0);
    const cdekUnknown=sel&&isCdek(sel.d)&&!(r.deliveryPrice>0);
    sDel.textContent=!sel?'—':cdekUnknown?'сообщит менеджер':r.deliveryPrice===0?'бесплатно':(sel.d.from?'от ':'')+fmt(r.deliveryPrice);
    sTot.textContent=fmt(r.total); sTotNote.hidden=!cdekUnknown;
    const p=PAYS.find(x=>x.id===payId);
    sEta.innerHTML=sel?`${esc(sel.t)}${dateOf(sel)?` · <b>${dateOf(sel)}</b>`:''}${p?' · '+esc(/^[А-ЯЁ][а-яё]/.test(p.name)?p.name.charAt(0).toLowerCase()+p.name.slice(1):p.name):''}`:'&nbsp;';
  }
  let last=null, calcSt='', busyT=0, calcing=false;
  // расчёт дольше 250 мс — показываем, что считаем: старые цены приглушены, сумм в итоге нет, кнопка ждёт.
  // Отправку держим с первой миллисекунды (calcing), а вид меняем только после 250 мс, чтобы быстрые расчёты не мигали
  const cop=document.querySelector('.cop'), sTot0=sTot.textContent;
  function calcShown(){ return document.querySelector('.cop').classList.contains('is-calc'); }
  function waiting(){ return calcShown()||(!!locIn.value&&!last&&calcing); }
  // приглушённые варианты недоступны ни мышью, ни с клавиатуры
  function dim(){ const off=cop.classList.contains('is-calc')||cop.classList.contains('is-stale'); if(off)keepF(); [deliv,pay,dTabs].forEach(x=>x.inert=off); if(!off)backF(); }
  function busy(on){
    clearTimeout(busyT); calcing=on;
    if(!on){ cop.classList.remove('is-calc'); calcSt_.textContent='';
      if(sDel.textContent==='считаем…'){ sDel.textContent='—'; sTot.textContent=last?fmt(last.sum):sTot0; }
      dim(); return; }
    busyT=setTimeout(()=>{ cop.classList.add('is-calc'); dim();
      calcSt_.innerHTML='Считаем'+(cityIn.value.trim()?'<span class="calcst__c"> для города '+esc(cityIn.value.trim())+'</span>':'')+'…';
      sDel.textContent=sTot.textContent='считаем…'; sEta.innerHTML='&nbsp;'; if(!last||last.loc!==locIn.value) coItemsH.textContent=N_ITEMS+' '+plural(N_ITEMS,['товар','товара','товаров']); if(!dl.length){renderDeliv();renderPay();} ready(); },250);
  }
  const calcSt_=document.getElementById('calcSt');
  // расчёт не удался: варианты прежнего города остаются приглушёнными, если город уже другой
  function calcFailed(){ busy(false); cop.classList.toggle('is-stale',!!last&&last.loc!==locIn.value); dim();
    renderDeliv(); renderPay(); if(last&&last.loc===locIn.value) renderSum(last); ready();
    BT_toast('Не получилось пересчитать доставку — обновите страницу'); return false; }
  function calc(again){
    const n=++calcN; busy(true);
    const st=document.getElementById('coStreet').value.trim(); calcSt=st;
    return post({action:'calc',ptype:pt,loc:locIn.value,delivery:sel?sel.d.id:0,pay:payId,street:/\d/.test(st)?st:''}).then(r=>{
      if(n!==calcN) return false;
      if(!r.ok) return calcFailed();
      dl=r.deliveries; avail=r.pays; last=r; cdekDown.hidden=!r.cdekDown;
      if(pvzCur&&pvzCur.loc!==locIn.value){pvzCur=null;pvzIn.value='';}
      const all=opts();
      if(!sel||!all.some(o=>o.key===sel.key)) sel=all.find(o=>o.key===want&&o.tab===tab)||all.find(o=>o.tab===tab)||all[0]||null;
      else sel=all.find(o=>o.key===sel.key);
      if(sel) tab=sel.tab;
      if(sel&&sel.d.id!==r.delivery&&!again) return calc(true);
      busy(false); cop.classList.remove('is-stale'); dim();
      payId=avail.some(a=>a.id===payId)?payId:avail.some(a=>a.id===wantPay)?wantPay:(avail[0]?avail[0].id:0);
      renderDeliv(); renderPay(); renderSum(r); ready(); return true;
    }).catch(()=>n===calcN?calcFailed():false);
  }
  dTabs.addEventListener('click',e=>{const b=e.target.closest('[data-tab]');if(!b)return;
    tab=b.dataset.tab; const o=opts().find(x=>x.tab===tab); if(o){const same=sel&&sel.d.id===o.d.id; sel=o; if(same){renderDeliv();renderPay();renderSum(last);ready();}else{renderDeliv();calc();}}});
  deliv.addEventListener('change',e=>{const o=opts().find(x=>x.key===e.target.value);if(!o)return;const same=sel&&sel.d.id===o.d.id;sel=o;
    if(same){renderDeliv();renderPay();renderSum(last);ready();}else{renderDeliv();calc();}});
  pay.addEventListener('change',e=>{payId=wantPay=+e.target.value;renderPay();renderSum(last);ready();});
  ptypeTabs.addEventListener('click',e=>{const b=e.target.closest('[data-t]');if(!b||b.dataset.t===pt)return;
    [...ptypeTabs.children].forEach(x=>x.setAttribute('aria-selected',x===b));pt=b.dataset.t;urFields.hidden=pt!=='UR';
    if(urFields.hidden) urFields.querySelectorAll('.field.is-err input').forEach(x=>setErr(x,''));
    coNameL.textContent=pt==='UR'?'Имя контактного лица *':'Имя *';calc();ready();});
  coNameL.textContent=pt==='UR'?'Имя контактного лица *':'Имя *';

  /* сохранённые организации: выбрали — подставили реквизиты, «Другая» — пустые поля */
  window.orgs&&orgs.addEventListener('change',e=>{const r=e.target;if(r.name!=='org')return;
    orgs.querySelectorAll('.radio-card').forEach(c=>c.classList.toggle('on',c.contains(r)));
    const v=r.value==='0'?{}:JSON.parse(r.dataset.v);
    ['company','inn','kpp','company_adr'].forEach(k=>{form.elements[k].value=v[k]||'';setErr(form.elements[k],'');});
    orgFields.hidden=r.value!=='0'; if(r.value==='0')coCompany.focus(); ready();});

  /* гость ввёл e-mail постоянного покупателя — предлагаем код, без входа тоже можно */
  const rec=document.getElementById('coRec');
  if(rec){ let recFor='';
    coEmail.addEventListener('blur',()=>{const v=coEmail.value.trim().toLowerCase();
      if(!/^[^\s@]+@[^\s@]+\.[a-zA-Zа-яА-Я]{2,}$/.test(v)){rec.hidden=true;return;}
      if(v===recFor)return; recFor=v;
      BT_authPost({action:'check',email:v}).then(r=>{rec.hidden=!(r.ok&&r.exists&&coEmail.value.trim().toLowerCase()===v);if(rec.hidden)return;
        coRecT.textContent=r.orders?'Вы уже покупали у нас':'У вас есть аккаунт с этим e‑mail';
        coRecD.textContent=r.orders?'Войдите — подставим имя, телефон, адрес и реквизиты из прошлого заказа. Можно продолжить и без входа.':'Войдите — заказ сохранится в личном кабинете. Можно продолжить и без входа.';}).catch(()=>{});});
    coEmail.addEventListener('input',()=>{if(coEmail.value.trim().toLowerCase()!==recFor)rec.hidden=true;});
    coRecBtn.addEventListener('click',()=>BT_auth(coEmail.value.trim()));
    if(coEmail.value.trim())coEmail.dispatchEvent(new Event('blur'));
  }

  /* город: популярные из макета + поиск по местоположениям Битрикса */
  const cityRow=c=>`<li role="option" data-code="${c.code}" data-n="${esc(c.n)}">${esc(c.n)}${c.r?`<small>${esc(c.r)}</small>`:''}</li>`;
  const showCities=list=>{cityList.innerHTML=list.map(cityRow).join('');const o=list.length>0;cityList.classList.toggle('open',o);cityIn.setAttribute('aria-expanded',o);cityIn.removeAttribute('aria-activedescendant');};
  // ↑/↓ двигают подсветку по подсказкам, Enter берёт подсвеченную (без подсветки — первую)
  const navList=(inp,ul)=>inp.addEventListener('keydown',e=>{ if(!ul.classList.contains('open')||(e.key!=='ArrowDown'&&e.key!=='ArrowUp'))return; e.preventDefault();
    const li=[...ul.querySelectorAll('li')], i=li.findIndex(x=>x.classList.contains('act')), j=e.key==='ArrowDown'?Math.min(i+1,li.length-1):Math.max(i-1,0);
    li.forEach((x,k)=>{x.id=x.id||ul.id+'-'+k; x.classList.toggle('act',k===j); x.setAttribute('aria-selected',k===j);});
    inp.setAttribute('aria-activedescendant',li[j].id); li[j].scrollIntoView({block:'nearest'}); });
  const pickLi=ul=>ul.classList.contains('open')&&(ul.querySelector('li.act')||ul.querySelector('li'));
  navList(cityIn,cityList);
  let cityT=0, cityN=0;
  cityIn.addEventListener('input',()=>{locIn.value='';calcN++;busy(false);cop.classList.toggle('is-stale',dl.length>0);dim();sDel.textContent='—';sEta.innerHTML='&nbsp;';coItemsH.textContent=N_ITEMS+' '+plural(N_ITEMS,['товар','товара','товаров']);if(last)sTot.textContent=fmt(last.sum);ready();
    const v=cityIn.value.trim(); clearTimeout(cityT);
    if(v.length<2){showCities(POP.filter(c=>c.n.toLowerCase().startsWith(v.toLowerCase())));return;}
    const n=++cityN; cityT=setTimeout(()=>post({action:'city',q:v}).then(r=>{if(n===cityN&&document.activeElement===cityIn)showCities(r.list||[]);}),200);});
  cityIn.addEventListener('focus',()=>{if(!locIn.value)cityIn.dispatchEvent(new Event('input'));else showCities(POP);});
  cityList.addEventListener('mousedown',e=>e.preventDefault());
  // сменили город — улица, дом и квартира прежнего города не подходят: сохранённый адрес этого города или пустые поля
  let curLoc=locIn.value;
  const pickCity=c=>{cityIn.value=c.n;locIn.value=c.code;showCities([]);setErr(cityIn,'');setTimeout(syncClr);
    let msg='';
    if(c.code!==curLoc){
      const a=(BT_CO_DATA.addrs||[]).find(x=>x.loc?x.loc===c.code:x.city===c.n), st=document.getElementById('coStreet'),
        fl=document.getElementById('coFlat'), en=document.getElementById('coEntr'), had=!!(st.value.trim()||fl.value.trim()||en.value.trim());
      st.value=a?a.street:''; fl.value=a?a.flat:''; en.value=a?a.entr:'';
      setErr(st,''); document.getElementById('streetList').classList.remove('open');
      msg=curLoc&&(had||a)?'Город изменён на '+c.n+(a?' — подставили ваш сохранённый адрес':' — укажите адрес доставки'):'';
      curLoc=c.code;
    }
    ready(); calc().then(ok=>{ if(ok&&msg) BT_toast(esc(msg)); });};
  cityList.addEventListener('click',e=>{const li=e.target.closest('li');if(li)pickCity({n:li.dataset.n,code:li.dataset.code});});
  const letters=v=>String(v||'').toLowerCase().replace(/ё/g,'е').replace(/[^a-zа-я]/g,'');
  // ввели не до конца и ушли из поля — берём первое совпадение по началу названия, мусор не подставляем
  const autoCity=()=>{const v=cityIn.value.trim();if(!v||locIn.value)return;const n=++cityN;clearTimeout(cityT);
    post({action:'city',q:v}).then(r=>{if(n!==cityN||cityIn.value.trim()!==v||locIn.value)return;
      const m=(r.list||[]).filter(c=>letters(c.n).startsWith(letters(v))), c=m.find(c=>letters(c.n)===letters(v))||(m.length===1?m[0]:null);
      c?pickCity(c):setErr(cityIn,'Выберите город из списка');});};
  cityIn.addEventListener('blur',()=>{showCities([]);autoCity();});
  cityIn.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();if(locIn.value){showCities([]);return;}const li=pickLi(cityList);li?li.click():autoCity();}
    if(e.key==='Escape')showCities([]);});

  /* улица и дом: подсказки DaData в пределах выбранного города; без дома — дописываем номер дальше */
  const stIn=document.getElementById('coStreet'), stList=document.getElementById('streetList');
  const showStreets=list=>{stList.innerHTML=list.map(s=>`<li role="option" data-v="${esc(s.v)}" data-h="${s.house?1:0}">${esc(s.v)}${s.r?`<small>${esc(s.r)}</small>`:''}</li>`).join('');
    const o=list.length>0;stList.classList.toggle('open',o);stIn.setAttribute('aria-expanded',o);stIn.removeAttribute('aria-activedescendant');};
  navList(stIn,stList);
  let stT=0, stN=0, stCalcT=0;
  /* адрес с домом изменился — пересчитать цену курьера (она зависит от расстояния) */
  const reStreet=()=>{const v=stIn.value.trim();if(v!==calcSt&&/\d/.test(v))calc();};
  stIn.addEventListener('input',()=>{clearTimeout(stCalcT);stCalcT=setTimeout(reStreet,900);});
  stIn.addEventListener('input',()=>{ready();const v=stIn.value.trim();clearTimeout(stT);if(v.length<2){showStreets([]);return;}
    const n=++stN; stT=setTimeout(()=>post({action:'street',q:v,city:locIn.value?cityIn.value.trim():''}).then(r=>{if(n===stN&&document.activeElement===stIn)showStreets(r.list||[]);}),250);});
  stList.addEventListener('mousedown',e=>e.preventDefault());
  stList.addEventListener('click',e=>{const li=e.target.closest('li');if(!li)return;
    stIn.value=li.dataset.v+(li.dataset.h==='1'?'':', д ');showStreets([]);syncClr();setErr(stIn,'');stIn.focus();ready();reStreet();
    if(li.dataset.h!=='1') stIn.dispatchEvent(new Event('input'));});
  // ввели руками и ушли — улицу берём из справочника, дом оставляем свой, если справочник его не знает
  const autoStreet=()=>{const v=stIn.value.trim();if(v.length<2)return;const n=++stN;clearTimeout(stT);
    post({action:'street',q:v,city:locIn.value?cityIn.value.trim():''}).then(r=>{if(n!==stN||stIn.value.trim()!==v)return;
      const s=(r.list||[])[0], num=v.match(/\d.*$/);
      // подставляем, только если набрано название целиком: «лен» — это ещё не «пр-кт Ленина»
      const words=t=>String(t||'').toLowerCase().replace(/ё/g,'е').split(/[^a-zа-я0-9]+/).filter(Boolean);
      const typed=words(v.replace(/\d.*$/,'').replace(/(^|[\s.,])(ул|улица|д|дом)(?=[\s.,]|$)/gi,' ')), sw=words(s&&s.s);
      if(!s||!typed.length||!typed.every(x=>sw.includes(x)))return;
      stIn.value=s.house&&num&&s.v.replace(/\D/g,'')===num[0].replace(/\D/g,'')?s.v:(s.s||v.replace(/[\s,]*\d.*$/,''))+(num?', д '+num[0].replace(/^(д|дом)[\s.]*/i,''):'');
      showStreets([]);ready();if(num)setErr(stIn,'');reStreet();});};
  stIn.addEventListener('blur',()=>{showStreets([]);autoStreet();});
  stIn.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();const li=pickLi(stList);li?li.click():autoStreet();}
    if(e.key==='Escape'){e.preventDefault();showStreets([]);}});

  // × в поле города и улицы: виден, пока в поле что-то есть
  // по центру поля по вертикали: высота подписи над полем разная на телефоне и компьютере
  const placeClr=()=>document.querySelectorAll('.cop [data-clr]').forEach(b=>{const i=b.parentNode.querySelector('input:not([type=hidden])');if(i.offsetHeight)b.style.top=(i.offsetTop+(i.offsetHeight-40)/2)+'px';});
  placeClr(); addEventListener('resize',placeClr);
  document.querySelectorAll('.cop [data-clr]').forEach(b=>{const inp=b.parentNode.querySelector('input:not([type=hidden])'), sync=()=>{b.hidden=!inp.value;};
    sync(); inp.addEventListener('input',sync);
    b.addEventListener('click',()=>{inp.value='';inp.focus();inp.dispatchEvent(new Event('input',{bubbles:true}));setErr(inp,'');});});
  const syncClr=()=>document.querySelectorAll('.cop [data-clr]').forEach(b=>{b.hidden=!b.parentNode.querySelector('input:not([type=hidden])').value;});

  // миниатюры состава — окно со всеми позициями, нажатая подсвечена
  // окно — прямо в body: внутри страницы шапка и нижняя плашка остаются поверх затемнения
  document.body.appendChild(itemsModal);
  let itmFrom=null;
  document.querySelector('.coitems__l').addEventListener('click',e=>{const b=e.target.closest('[data-items]');if(!b)return;
    itmFrom=b; itemsModal.classList.add('open');
    const li=itemsModal.querySelector(`[data-i="${b.dataset.items}"]`);
    itemsModal.querySelectorAll('.itm__l li').forEach(x=>x.classList.toggle('on',x===li));
    li.scrollIntoView({block:'nearest'}); if(fineP) itemsModal.querySelector('.modal__x').focus({preventScroll:true});});
  itemsModal.addEventListener('click',e=>{if(e.target.closest('[data-close]'))itemsModal.classList.remove('open');});
  new MutationObserver(()=>{if(!itemsModal.classList.contains('open')&&itmFrom&&fineP){itmFrom.focus({preventScroll:true});itmFrom=null;}})
    .observe(itemsModal,{attributes:true,attributeFilter:['class']});

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
    if(pt==='UR'){ if(!val('company'))miss.push('название организации'); if(!BT_innOk(dig('inn')))miss.push('ИНН'); }
    if(val('name').length<2)miss.push(pt==='UR'?'контактное лицо':'имя');
    if(dig('phone').length!==11)miss.push('телефон');
    if(!/^[^\s@]+@[^\s@]+\.[a-zA-Zа-яА-Я]{2,}$/.test(val('email')))miss.push('e‑mail');
    if(!locIn.value)miss.push('город');
    else if(!sel||!last||last.loc!==locIn.value)miss.push('способ доставки');
    else if(sel.tab==='addr'&&!/[а-яёa-z]{2,}.*\d/i.test(val('street')))miss.push(/^(ул|пр-кт|пер|б-р|ш|пл|наб|проезд|туп|мкр|тракт|аллея)\s|,\s*д\s*$/i.test(val('street'))?'номер дома':'адрес доставки');
    else if(sel.tab==='pvz'&&!pvzIn.value)miss.push('пункт выдачи');
    if(locIn.value&&!payId)miss.push('способ оплаты');
    const pp=PAYS.find(x=>x.id===payId)||{}, online=pp.code==='tinkoff';
    sLeft.textContent=miss.length?'Осталось указать: '+miss.join(', '):online?'Всё заполнено — можно оплачивать':'Всё заполнено — можно оформлять';
    if(!sending)submit.textContent=btnText();
    if(calcShown()) sLeft.textContent='Считаем доставку…';
    submit.disabled=sending||calcShown();
    return !miss.length;
  }
  // онлайн-оплата — кнопка ведёт в банк, поэтому и называется по-другому
  const btnText=()=>(PAYS.find(x=>x.id===payId)||{}).code==='tinkoff'?'Оплатить заказ':'Оформить заказ';
  form.addEventListener('input',ready); form.addEventListener('change',ready);
  BT_mbar(submit,sTot,()=>submit.disabled?sLeft.scrollIntoView({behavior:'smooth',block:'center'}):form.requestSubmit(submit),sHint.innerHTML);

  // не всё заполнено: поля подсвечивает BT_validate, остальное — прокручиваем к своему блоку
  function goMissing(){
    if(!BT_validate(form)) return;
    const to=(el,msg)=>{el.scrollIntoView({behavior:'smooth',block:'center'}); if(msg) BT_toast(msg);};
    if(!locIn.value){ setErr(cityIn,'Выберите город из списка'); to(cityIn); if(fineP) cityIn.focus({preventScroll:true}); }
    else if(!sel||!last||last.loc!==locIn.value) to(blkD,'Выберите способ доставки');
    else if(sel.tab==='pvz'&&!pvzIn.value) to(blkD,'Выберите пункт выдачи на карте');
    else if(!payId) to(blkP,'Выберите способ оплаты');
  }
  form.addEventListener('submit',e=>{const bad=e.defaultPrevented; e.preventDefault(); if(bad||sending||calcing) return;
    if(!ready()){ goMissing(); return; }
    sending=true; submit.disabled=true; submit.textContent='Оформляем…'; sErr.textContent='';
    const data={action:'create',ptype:pt,delivery:sel.d.id,mode:sel.tab,dkey:sel.key,pay:payId,agree:'Y'};
    [...form.elements].forEach(el=>{if(el.name&&el.type!=='radio'&&el.type!=='checkbox'&&!(el.name in data))data[el.name]=el.value;});
    post(data).then(r=>{
      if(r.ok){
        Object.keys(BT_CART).forEach(k=>delete BT_CART[k]);
        try{localStorage.setItem('bt_cart','{}');}catch(x){}
        window.BT_BASKET={items:{},sum:0}; BT_cartUpdate(false);
        location.href=r.redirect; return;
      }
      sending=false; submit.textContent=btnText();
      let first=null;
      Object.entries(r.errors||{}).forEach(([k,m])=>{const el=form.elements[k];
        if(el&&el.type!=='hidden'){setErr(el,m);first=first||el;} else sErr.textContent=m;});
      if(window.orgFields&&['company','inn','kpp','company_adr'].some(k=>r.errors&&r.errors[k]))orgFields.hidden=false;
      if(first){first.focus({preventScroll:true});first.scrollIntoView({behavior:'smooth',block:'center'});BT_toast('Проверьте выделенные поля');}
      else if(!r.errors) sErr.textContent='Не получилось оформить заказ — обновите страницу и попробуйте ещё раз';
      ready();
    }).catch(()=>{sending=false;submit.textContent=btnText();sErr.textContent='Нет связи с сервером — попробуйте ещё раз';ready();});
  });
  subRows(<?= (float)$basket->getPrice() ?>);
  calcing=!!locIn.value; renderDeliv(); renderPay(); ready(); calc();
});
</script>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
