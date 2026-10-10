<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Фильтр в разметке catalog.html: применяется сразу при выборе (фоновая загрузка страницы), без кнопки; выбранное — чипсами
// ЧПУ: адрес раздела без условий и адрес с выбранными условиями; «filter/clear/apply/» — это «без условий»
$reset = preg_replace('~filter/clear/apply/$~', '', (string)($arResult['SEF_DEL_FILTER_URL'] ?? strtok($arResult['FORM_ACTION'], '?')));
$sefUrl = (string)($arResult['SEF_SET_FILTER_URL'] ?? '');
$sefUrl = $sefUrl === '' || str_ends_with($sefUrl, 'filter/clear/apply/') ? $reset : $sefUrl;
$smartPath = preg_match('~/filter/(.+)/apply/$~', $sefUrl, $m) ? $m[1] : '';
$keep = array_intersect_key($_GET, ['sort' => 1]);
// условия пришли параметрами (фоновая загрузка, старая ссылка) или ЧПУ записан не так, как его строит фильтр
// (пустой, другой порядок, значения не из этого раздела) — постоянный редирект на понятный адрес
$curPage = (string)$APPLICATION->GetCurPage(false);
if (isset($_GET['set_filter']) || (str_contains($curPage, '/filter/') && $curPage !== $sefUrl)) {
    LocalRedirect($sefUrl . ($keep ? '?' . http_build_query($keep) : ''), true, '301 Moved permanently');
}
$sort = isset($_GET['sort']) ? htmlspecialcharsbx($_GET['sort']) : '';
$chips = [];
$e = fn($s) => htmlspecialcharsbx((string)$s);

// «Категория» как на Озоне: цепочка родителей «‹», текущий раздел подсвечен, под ним подразделы (в конечном разделе — соседние).
// Выбранные условия фильтра переносятся в адрес раздела
$sid = (int)($arParams['SECTION_ID'] ?? 0);
$secHref = fn(string $url) => $url . ($smartPath !== '' ? 'filter/' . $smartPath . '/apply/' : '') . ($keep ? '?' . http_build_query($keep) : '');
$secList = function (int $parent) use ($arParams): array {
    $out = [];
    $r = CIBlockSection::GetList(['SORT' => 'ASC', 'NAME' => 'ASC'], ['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y',
        'SECTION_ID' => $parent ?: false, 'CNT_ACTIVE' => 'Y'], true, ['ID', 'NAME', 'SECTION_PAGE_URL']);
    while ($s = $r->GetNext()) {
        if ((int)$s['ELEMENT_CNT'] > 0) {
            $out[] = ['id' => (int)$s['ID'], 'name' => $s['~NAME'], 'url' => $s['~SECTION_PAGE_URL']];
        }
    }
    return $out;
};
$catRoot = preg_replace('~/+~', '/', str_replace('#SITE_DIR#', SITE_DIR, (string)CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'LIST_PAGE_URL')));
$back = [];
$cur = ['id' => 0, 'name' => 'Все категории', 'url' => $catRoot];
if ($sid) {
    $back[] = ['id' => 0, 'name' => 'Все категории', 'url' => $catRoot];
    $r = CIBlockSection::GetNavChain($arParams['IBLOCK_ID'], $sid, ['ID', 'NAME', 'SECTION_PAGE_URL']);
    while ($s = $r->GetNext()) {
        $back[] = ['id' => (int)$s['ID'], 'name' => $s['~NAME'], 'url' => $s['~SECTION_PAGE_URL']];
    }
    $cur = array_pop($back);
}
$catList = $secList($sid);
if ($catList) {
    array_unshift($catList, $cur);
} elseif ($sid) {
    $catList = $secList((int)end($back)['id']);
}
$FMORE = 6;
?>
<aside class="filters" id="filters">
  <form method="get" action="<?= $e($reset) ?>">
  <?php foreach ($arResult['HIDDEN'] as $h): if ($h['CONTROL_NAME'] === 'sort') continue; ?><input type="hidden" name="<?= $h['CONTROL_NAME'] ?>" value="<?= $h['HTML_VALUE'] ?>"><?php endforeach ?>
  <?php if ($sort): ?><input type="hidden" name="sort" value="<?= $sort ?>"><?php endif ?>
  <div class="filters__hd">
    <b>Фильтр</b>
    <a class="link" href="<?= $e($reset) ?>" data-freset style="font-size:13px;margin-left:auto">Сбросить</a>
    <button class="filters__x" type="button" id="fClose" aria-label="Закрыть фильтр">×</button>
  </div>
  <div class="filters__body">
    <?php
    // порядок групп как в макете: цена, фасовка, страна, вкус, действие…; «Метки» — в конце
    $order = ['PRICE' => 0, 'NET_WEIGHT' => 1, 'PACKING' => 2, 'ROAST' => 3, 'COUNTRY' => 4, 'TEA_KIND' => 5, 'TASTE' => 6, 'EFFECT' => 7, 'PROCESSING' => 8,
        'WATER_TANK' => 10, 'BEAN_HOPPER' => 11, 'MATERIAL' => 12, 'VOLUME' => 13, 'COLOR' => 14, 'FILTER_SIZE' => 15, 'BADGES' => 99];
    // свойство с единственным значением «Да» (код yes) — переключатель с названием свойства
    $isYes = fn($it) => count($it['VALUES'] ?? []) === 1 && (reset($it['VALUES'])['URL_ID'] ?? '') === 'yes';
    $items = $arResult['ITEMS'];
    uasort($items, fn($a, $b) => ($order[isset($a['PRICE']) ? 'PRICE' : $a['CODE']] ?? 50) <=> ($order[isset($b['PRICE']) ? 'PRICE' : $b['CODE']] ?? 50));
    if (count($catList) > 1): ?>
      <div class="fgrp fcat"><b class="fgrp__t">Категория</b>
      <?php foreach ($back as $x): ?><a class="fcat__back" href="<?= $e($secHref($x['url'])) ?>"><?= $e($x['name']) ?></a><?php endforeach ?>
      <ul class="fcat__list">
      <?php $i = 0; foreach ($catList as $x): $on = $x['id'] === $sid; $more = !$on && ++$i > $FMORE; ?>
        <li<?= $more ? ' class="more"' : '' ?>><a<?= $on ? ' class="is-on" aria-current="page"' : '' ?> href="<?= $e($secHref($x['url'])) ?>"><?= $e($x['name']) ?></a></li>
      <?php endforeach ?>
      </ul>
      <?php if ($i > $FMORE): ?><button class="fmore" type="button">Посмотреть все</button><?php endif ?>
      </div>
    <?php endif;
    // «Метки» и свойства «да / нет» — переключателями сверху, как «Распродажа» на Озоне
    foreach ($items as $item) {
        $yes = $isYes($item);
        if ((($item['CODE'] ?? '') !== 'BADGES' && !$yes) || !$item['VALUES']) {
            continue;
        } ?>
      <div class="fgrp fsw">
      <?php foreach ($item['VALUES'] as $v): $off = !empty($v['DISABLED']) && empty($v['CHECKED']);
          // из меток в фильтре только «Скидка» и «Новинка»: «Хит» и «Топ продаж» — оформление карточки, не критерий выбора
          if (!$yes && !in_array($v['URL_ID'] ?? '', ['sale', 'new'], true)) { continue; }
          if (!empty($v['CHECKED'])) { $chips[] = [$item['NAME'], $yes ? 'да' : mb_strtolower($v['VALUE']), [$v['CONTROL_NAME']]]; } ?>
        <label class="opt opt--sw"><?= $e($yes ? $item['NAME'] : $v['VALUE']) ?><input type="checkbox" role="switch" name="<?= $v['CONTROL_NAME'] ?>" value="<?= $v['HTML_VALUE'] ?>"<?= !empty($v['CHECKED']) ? ' checked' : '' ?><?= $off ? ' disabled' : '' ?>></label>
      <?php endforeach ?>
      </div>
    <?php }
    foreach ($items as $item):
        if (isset($item['PRICE'])):
            $min = $item['VALUES']['MIN'];
            $max = $item['VALUES']['MAX'];
            // в индексе фильтра лежат и оптовые ступени кофе — границы и подсказки считаем по цене за 1 шт/кг
            $pk = 'CATALOG_PRICE_' . $item['ID'];
            $base = ['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y', 'INCLUDE_SUBSECTIONS' => 'Y', 'CATALOG_SHOP_QUANTITY_' . $item['ID'] => 1, '>' . $pk => 0] + ($sid ? ['SECTION_ID' => $sid] : []);
            $other = array_filter($GLOBALS[$arParams['FILTER_NAME']] ?? [], fn($k) => !str_contains((string)$k, $pk), ARRAY_FILTER_USE_KEY);
            $edge = fn(array $f, string $dir) => (float)(CIBlockElement::GetList([$pk => $dir], $f, false, ['nTopCount' => 1], ['ID', 'CATALOG_GROUP_' . $item['ID']])->Fetch()[$pk] ?? 0);
            $min['VALUE'] = $edge($base, 'ASC');
            $max['VALUE'] = $edge($base, 'DESC');
            $min['FILTERED_VALUE'] = $other ? $edge($other + $base, 'ASC') : 0;
            $max['FILTERED_VALUE'] = $other ? $edge($other + $base, 'DESC') : 0;
            if ($max['VALUE'] - $min['VALUE'] <= 0) {
                continue;
            }
            if ($min['HTML_VALUE'] !== '' || $max['HTML_VALUE'] !== '') {
                $chips[] = ['Цена', trim(($min['HTML_VALUE'] !== '' ? 'от ' . $min['HTML_VALUE'] : '') . ' ' . ($max['HTML_VALUE'] !== '' ? 'до ' . $max['HTML_VALUE'] : '')) . ' ₽', [$min['CONTROL_NAME'], $max['CONTROL_NAME']]];
            } ?>
      <div class="fgrp fprice"><b class="fgrp__t">Цена, ₽</b>
        <div class="range">
          <label><span>от</span><input name="<?= $min['CONTROL_NAME'] ?>" value="<?= $min['HTML_VALUE'] ?>" placeholder="<?= number_format((float)(($min['FILTERED_VALUE'] ?? 0) ?: $min['VALUE']), 0, '', "\u{00A0}") ?>" aria-label="Цена от" inputmode="numeric" autocomplete="off"></label>
          <label><span>до</span><input name="<?= $max['CONTROL_NAME'] ?>" value="<?= $max['HTML_VALUE'] ?>" placeholder="<?= number_format((float)(($max['FILTERED_VALUE'] ?? 0) ?: $max['VALUE']), 0, '', "\u{00A0}") ?>" aria-label="Цена до" inputmode="numeric" autocomplete="off"></label>
        </div>
        <div class="fslider"><i></i>
          <input type="range" tabindex="-1" aria-hidden="true" min="<?= floor($min['VALUE']) ?>" max="<?= ceil($max['VALUE']) ?>" value="<?= $min['HTML_VALUE'] !== '' ? (float)$min['HTML_VALUE'] : floor($min['VALUE']) ?>">
          <input type="range" tabindex="-1" aria-hidden="true" min="<?= floor($min['VALUE']) ?>" max="<?= ceil($max['VALUE']) ?>" value="<?= $max['HTML_VALUE'] !== '' ? (float)$max['HTML_VALUE'] : ceil($max['VALUE']) ?>">
        </div>
      </div>
        <?php continue;
        endif;
        // недоступные при текущем выборе значения не прячем, а гасим — фильтр не прыгает
        $values = $item['VALUES'];
        if (!$values || isset($values['MIN'])) {
            continue;
        }
        if ($item['CODE'] === 'NET_WEIGHT') { // вес по возрастанию: 50 г … 1 кг
            $grams = fn($v) => (float)str_replace(',', '.', $v['VALUE']) * (str_contains($v['VALUE'], 'кг') ? 1000 : 1);
            uasort($values, fn($a, $b) => $grams($a) <=> $grams($b));
        }
        if ($item['CODE'] === 'BADGES' || $isYes($item)) {
            continue;
        }
        foreach ($values as $v) {
            if (!empty($v['CHECKED'])) {
                // страна и регион — имена собственные, с заглавной
                $chips[] = [$item['NAME'], in_array($item['CODE'], ['COUNTRY', 'REGION', 'BRAND'], true) ? $v['VALUE'] : mb_strtolower($v['VALUE']), [$v['CONTROL_NAME']]];
            }
        } ?>
      <div class="fgrp"><b class="fgrp__t"><?= $e($item['NAME']) ?></b>
      <?php $i = 0; foreach ($values as $v): $off = !empty($v['DISABLED']) && empty($v['CHECKED']); $more = empty($v['CHECKED']) && ++$i > $FMORE - 1; ?>
        <label class="opt<?= $more ? ' more' : '' ?>"><input type="checkbox" name="<?= $v['CONTROL_NAME'] ?>" value="<?= $v['HTML_VALUE'] ?>"<?= !empty($v['CHECKED']) ? ' checked' : '' ?><?= $off ? ' disabled' : '' ?>><?= $e($v['VALUE']) ?></label>
      <?php endforeach ?>
      <?php if ($i > $FMORE - 1): ?><button class="fmore" type="button">Посмотреть все</button><?php endif ?>
      </div>
    <?php endforeach ?>
  </div>
  <button class="btn btn--block btn--sm fbtn" style="margin-top:14px" id="fApply" type="button">Показать товары</button>
  </form>
</aside>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const filters=document.getElementById('filters'), form=filters.querySelector('form'), fb=document.querySelector('.mob-f'),
    fx=document.getElementById('fClose'), fa=document.getElementById('fApply');
  /* мобильная шторка фильтра: блокировка фона, Esc, закрытие по кнопке */
  const openF=()=>{filters.classList.add('open');BT_lock();setTimeout(()=>fx.focus(),40);};
  const closeF=()=>{filters.classList.remove('open');BT_lock();fb&&fb.focus();};
  fb&&fb.addEventListener('click',openF);
  fx.addEventListener('click',closeF);
  fa.addEventListener('click',closeF);
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&filters.classList.contains('open'))closeF();});

  /* ---- применение на лету: фоновая загрузка той же страницы с фильтром и подмена списка ---- */
  const buildUrl=()=>{
    const u=new window.URL(form.getAttribute('action'),location.origin); let any=false;
    for(const [k,v] of new FormData(form)){ if(v==='') continue; u.searchParams.append(k,v); if(k!=='sort') any=true; }
    if(any) u.searchParams.set('set_filter','Y');
    return u;
  };
  let ctrl=null;
  /* фильтр при выборе остаётся на месте (как на Озоне): высоту колонки товаров держим, рядом с пунктом — «Показать N товаров» */
  let lastEl=null, hint=null;
  const col=document.querySelector('.toolbar')?.parentElement;
  const dropHint=()=>{ hint&&hint.remove(); hint=null; };
  const showHint=text=>{
    dropHint(); if(!lastEl||!document.contains(lastEl)) return;
    const r=lastEl.getBoundingClientRect(), fr=filters.getBoundingClientRect();
    hint=document.createElement('button'); hint.type='button'; hint.className='fhint'; hint.textContent=text;
    hint.style.cssText=`left:${fr.right+scrollX+10}px;top:${r.top+scrollY+r.height/2}px`;
    hint.addEventListener('click',()=>{ const tb=document.querySelector('.toolbar'); dropHint();
      tb&&scrollTo({top:scrollY+tb.getBoundingClientRect().top-topGap,behavior:'smooth'}); });
    document.body.appendChild(hint);
  };
  addEventListener('scroll',()=>{ const tb=document.querySelector('.toolbar');
    if(tb&&tb.getBoundingClientRect().top>=0){ dropHint(); if(col) col.style.minHeight=''; } },{passive:true});
  const load=(u,push)=>{
    ctrl&&ctrl.abort(); ctrl=new AbortController(); dropHint();
    if(col&&!filters.classList.contains('open')) col.style.minHeight=col.offsetHeight+'px';
    const cards=document.getElementById('cards'); if(cards) cards.style.opacity='.45';
    let to=u; /* сервер перенаправляет на ЧПУ — в строку браузера пишем его */
    return fetch(u,{signal:ctrl.signal,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>{ to=r.url||u; return r.text(); }).then(html=>{
        const d=new DOMParser().parseFromString(html,'text/html');
        ['#cards','#cardsEmpty','#fChips'].forEach(sel=>{const a=document.querySelector(sel),b=d.querySelector(sel);if(a&&b)a.replaceWith(b);});
        const cnt=document.querySelector('.toolbar .cnt'), cnt2=d.querySelector('.toolbar .cnt'); if(cnt&&cnt2) cnt.textContent=cnt2.textContent;
        /* числа и доступность значений — без перерисовки фильтра: раскрытые группы и фокус остаются */
        d.querySelectorAll('#filters input[name]').forEach(n=>{
          const o=form.querySelector(`input[name="${CSS.escape(n.name)}"]`); if(!o) return;
          if(o.type==='checkbox') o.disabled=n.disabled; else o.placeholder=n.placeholder;
        });
        /* ссылки «Разделов» несут текущие условия фильтра — берём их из ответа */
        const ns=d.querySelectorAll('.fcat a'); form.querySelectorAll('.fcat a').forEach((a,i)=>{ if(ns[i]) a.href=ns[i].getAttribute('href'); });
        fa.textContent=cnt2?`Показать ${cnt2.textContent}`:'Показать товары';
        if(push) history.pushState({bt:1},'',to); else history.replaceState({bt:1},'',to);
        /* начало списка выше экрана — страницу не двигаем, показываем кнопку к товарам */
        const tb=document.querySelector('.toolbar');
        if(tb&&!filters.classList.contains('open')&&tb.getBoundingClientRect().top<0) showHint('Показать '+(cnt2?cnt2.textContent.trim():'товары'));
        else if(col) col.style.minHeight='';
        /* новые карточки: степперы корзины, сравнение, избранное */
        Object.keys(BT_CART).forEach(id=>document.dispatchEvent(new CustomEvent('bt:cart',{detail:{id,q:BT_CART[id]}})));
        BT_cmpUpdate(); window.BT_favUpdate&&BT_favUpdate();
      }).catch(err=>{ if(err.name!=='AbortError') location.href=u; });
  };
  /* «Посмотреть все» / «Свернуть» у длинных списков */
  form.addEventListener('click',e=>{ const m=e.target.closest('.fmore'); if(!m) return; m.textContent=m.closest('.fgrp').classList.toggle('is-all')?'Свернуть':'Посмотреть все'; });
  /* ползунок цены: двигаем — пишем в поля, отпустили — применяем */
  const pr=form.querySelector('.fprice'), rg=pr?[...pr.querySelectorAll('input[type=range]')]:[];
  const syncSlider=()=>{ if(!pr) return; const [a,b]=rg, lo=+a.min, w=(+a.max-lo)||1;
    pr.querySelector('.fslider i').style.cssText=`left:${(a.value-lo)/w*100}%;right:${100-(b.value-lo)/w*100}%`; };
  if(pr){
    const [fMin,fMax]=pr.querySelectorAll('.range input'), [a,b]=rg;
    rg.forEach(r=>r.addEventListener('input',()=>{
      if(+a.value>+b.value){ if(r===a) a.value=b.value; else b.value=a.value; }
      fMin.value=+a.value>+a.min?a.value:''; fMax.value=+b.value<+b.max?b.value:''; syncSlider();
    }));
    rg.forEach(r=>r.addEventListener('change',()=>load(buildUrl(),true)));
    [fMin,fMax].forEach(f=>f.addEventListener('input',()=>{ a.value=fMin.value||a.min; b.value=fMax.value||b.max; syncSlider(); }));
    syncSlider();
  }
  form.addEventListener('change',e=>{ lastEl=e.target.closest('.opt,.fprice')||e.target; if(e.target.type==='checkbox') load(buildUrl(),true); });
  let pt; form.addEventListener('input',e=>{ if(e.target.type==='checkbox'||e.target.type==='range') return; clearTimeout(pt); pt=setTimeout(()=>load(buildUrl(),true),700); });
  form.addEventListener('submit',e=>{ e.preventDefault(); load(buildUrl(),true); });
  /* снять одно условие чипсом или сбросить всё — тоже без перезагрузки */
  document.addEventListener('click',e=>{
    const a=e.target.closest('#fChips a[data-names]');
    if(a){ e.preventDefault(); a.dataset.names.split(',').forEach(n=>form.querySelectorAll(`[name="${CSS.escape(n)}"]`).forEach(i=>{ if(i.type==='checkbox') i.checked=false; else i.value=''; })); load(buildUrl(),true); return; }
    const r=e.target.closest('[data-freset]');
    if(r){ e.preventDefault(); form.querySelectorAll('input[name]').forEach(i=>{ if(i.type==='checkbox') i.checked=false; else if(i.type!=='hidden') i.value=''; });
      rg.forEach((i,k)=>i.value=k?i.max:i.min); syncSlider(); load(buildUrl(),true); }
  });
  addEventListener('popstate',e=>{ if(e.state&&e.state.bt) location.reload(); });
  /* длинный фильтр на десктопе прокручивается вместе с товарами и встаёт, когда показался его низ; вверх — сразу едет вверх до шапки (как на Озоне) */
  const topGap=parseFloat(getComputedStyle(filters).top)||0, desk=matchMedia('(min-width:1051px)');
  let lastY=scrollY, off=topGap;
  const stick=()=>{
    if(!desk.matches){ filters.style.top=''; return; }
    const minTop=Math.min(topGap, innerHeight-filters.offsetHeight-20);
    off=Math.max(minTop, Math.min(topGap, off-(scrollY-lastY))); lastY=scrollY;
    filters.style.top=off+'px';
  };
  addEventListener('scroll',stick,{passive:true}); addEventListener('resize',stick);
  new ResizeObserver(stick).observe(filters);
});
</script>
<?php
// чипсы выбранных условий; контейнер выводится всегда — его подменяет фоновая загрузка
$html = '<div class="chips" id="fChips"' . ($chips ? '' : ' style="display:none"') . '>';
// выбранные условия полем → значение: ссылка «×» ведёт на раздел с остальными условиями, сервер перенаправит на ЧПУ
$cur = [];
foreach ($arResult['ITEMS'] as $it) {
    foreach ($it['VALUES'] as $k => $v) {
        if (in_array($k, ['MIN', 'MAX'], true) ? ($v['HTML_VALUE'] ?? '') !== '' : !empty($v['CHECKED'])) {
            $cur[$v['CONTROL_NAME']] = $v['HTML_VALUE'];
        }
    }
}
foreach ($chips as [$name, $value, $params]) {
    $rest = array_diff_key($cur, array_flip($params));
    $url = $reset . (($q = http_build_query(($rest ? $rest + ['set_filter' => 'Y'] : []) + $keep)) !== '' ? '?' . $q : '');
    $html .= '<a class="chip" href="' . $e($url) . '" data-names="' . $e(implode(',', $params)) . '" title="Убрать условие">' . $e($name) . ': ' . $e($value) . '<i><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M5 5l6 6M11 5l-6 6"/></svg></i></a>';
}
if ($chips) {
    $html .= '<a class="chip chip--reset" href="' . $e($reset) . '" data-freset>Сбросить все<i><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M5 5l6 6M11 5l-6 6"/></svg></i></a>';
}
$APPLICATION->AddViewContent('bt_cat_chips', $html . '</div>');
