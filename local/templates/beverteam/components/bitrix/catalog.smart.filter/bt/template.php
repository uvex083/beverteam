<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Фильтр в разметке catalog.html: применяется сразу при выборе (фоновая загрузка страницы), без кнопки; выбранное — чипсами
$reset = strtok($arResult['FORM_ACTION'], '?');
$sort = isset($_GET['sort']) ? htmlspecialcharsbx($_GET['sort']) : '';
$chips = [];
$e = fn($s) => htmlspecialcharsbx((string)$s);

// «Категория» как на Озоне: цепочка родителей «‹», текущий раздел подсвечен, под ним подразделы (в конечном разделе — соседние).
// Выбранные условия фильтра переносятся в адрес раздела
$sid = (int)($arParams['SECTION_ID'] ?? 0);
$secQuery = trim(preg_replace('~(^|&)(PAGEN_\d+|bxajaxid|ajax|clear_cache)=[^&]*~', '', (string)($_SERVER['QUERY_STRING'] ?? '')), '&');
$secHref = fn(string $url) => $url . ($secQuery !== '' ? '?' . $secQuery : '');
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
  <?php foreach ($arResult['HIDDEN'] as $h): ?><input type="hidden" name="<?= $h['CONTROL_NAME'] ?>" value="<?= $h['HTML_VALUE'] ?>"><?php endforeach ?>
  <?php if ($sort): ?><input type="hidden" name="sort" value="<?= $sort ?>"><?php endif ?>
  <div class="filters__hd">
    <b>Фильтр</b>
    <a class="link" href="<?= $e($reset) ?>" data-freset style="font-size:13px;margin-left:auto">Сбросить</a>
    <button class="filters__x" type="button" id="fClose" aria-label="Закрыть фильтр">×</button>
  </div>
  <div class="filters__body">
    <?php
    // порядок групп как в макете: цена, фасовка, страна, вкус, действие…; «Метки» — в конце
    $order = ['PRICE' => 0, 'NET_WEIGHT' => 1, 'PACKING' => 2, 'ROAST' => 3, 'COUNTRY' => 4, 'TEA_KIND' => 5, 'TASTE' => 6, 'EFFECT' => 7, 'PROCESSING' => 8, 'BADGES' => 99];
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
    // «Метки» — переключателями сверху, как «Распродажа» на Озоне
    foreach ($items as $item) {
        if (($item['CODE'] ?? '') !== 'BADGES' || !$item['VALUES']) {
            continue;
        } ?>
      <div class="fgrp fsw">
      <?php foreach ($item['VALUES'] as $v): $off = !empty($v['DISABLED']) && empty($v['CHECKED']);
          if (!empty($v['CHECKED'])) { $chips[] = [$item['NAME'], $v['VALUE'], [$v['CONTROL_NAME']]]; } ?>
        <label class="opt opt--sw"><?= $e($v['VALUE']) ?><input type="checkbox" role="switch" name="<?= $v['CONTROL_NAME'] ?>" value="<?= $v['HTML_VALUE'] ?>"<?= !empty($v['CHECKED']) ? ' checked' : '' ?><?= $off ? ' disabled' : '' ?>></label>
      <?php endforeach ?>
      </div>
    <?php }
    foreach ($items as $item):
        if (isset($item['PRICE'])):
            $min = $item['VALUES']['MIN'];
            $max = $item['VALUES']['MAX'];
            if ($max['VALUE'] - $min['VALUE'] <= 0) {
                continue;
            }
            if ($min['HTML_VALUE'] !== '' || $max['HTML_VALUE'] !== '') {
                $chips[] = ['Цена', trim(($min['HTML_VALUE'] !== '' ? 'от ' . $min['HTML_VALUE'] : '') . ' ' . ($max['HTML_VALUE'] !== '' ? 'до ' . $max['HTML_VALUE'] : '')) . ' ₽', [$min['CONTROL_NAME'], $max['CONTROL_NAME']]];
            } ?>
      <div class="fgrp fprice"><b class="fgrp__t">Цена, ₽</b>
        <div class="range">
          <label><span>от</span><input name="<?= $min['CONTROL_NAME'] ?>" value="<?= $min['HTML_VALUE'] ?>" placeholder="<?= bt_fmt((float)(($min['FILTERED_VALUE'] ?? 0) ?: $min['VALUE'])) ?>" aria-label="Цена от" inputmode="numeric" autocomplete="off"></label>
          <label><span>до</span><input name="<?= $max['CONTROL_NAME'] ?>" value="<?= $max['HTML_VALUE'] ?>" placeholder="<?= bt_fmt((float)(($max['FILTERED_VALUE'] ?? 0) ?: $max['VALUE'])) ?>" aria-label="Цена до" inputmode="numeric" autocomplete="off"></label>
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
        if ($item['CODE'] === 'BADGES') {
            continue;
        }
        foreach ($values as $v) {
            if (!empty($v['CHECKED'])) {
                $chips[] = [$item['NAME'], $v['VALUE'], [$v['CONTROL_NAME']]];
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
  const load=(u,push)=>{
    ctrl&&ctrl.abort(); ctrl=new AbortController();
    const cards=document.getElementById('cards'); if(cards) cards.style.opacity='.45';
    return fetch(u,{signal:ctrl.signal,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.text()).then(html=>{
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
        if(push) history.pushState({bt:1},'',u); else history.replaceState({bt:1},'',u);
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
  form.addEventListener('change',e=>{ if(e.target.type==='checkbox') load(buildUrl(),true); });
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
});
</script>
<?php
// чипсы выбранных условий; контейнер выводится всегда — его подменяет фоновая загрузка
$html = '<div class="chips" id="fChips"' . ($chips ? '' : ' style="display:none"') . '>';
foreach ($chips as [$name, $value, $params]) {
    $url = $APPLICATION->GetCurPageParam('', array_merge($params, ['set_filter']));
    $html .= '<span class="chip"><b>' . $e($name) . ':</b> ' . $e(mb_strtolower($value)) . ' <a href="' . $e($url) . '" data-names="' . $e(implode(',', $params)) . '" aria-label="Убрать">×</a></span>';
}
if ($chips) {
    $html .= '<a class="chip chip--reset" href="' . $e($reset) . '" data-freset>Сбросить все</a>';
}
$APPLICATION->AddViewContent('bt_cat_chips', $html . '</div>');
