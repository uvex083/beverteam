<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Фильтр в разметке catalog.html: применяется сразу при выборе (фоновая загрузка страницы), без кнопки; выбранное — чипсами
$reset = strtok($arResult['FORM_ACTION'], '?');
$sort = isset($_GET['sort']) ? htmlspecialcharsbx($_GET['sort']) : '';
$chips = [];
$group = 0;
$e = fn($s) => htmlspecialcharsbx((string)$s);
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
    <div class="row between fdesk" style="margin:0 0 6px"><b>Фильтр</b><a class="link" href="<?= $e($reset) ?>" data-freset style="font-size:12.5px">Сбросить</a></div>
    <?php
    // порядок групп как в макете: цена, фасовка, страна, вкус, действие…; «Метки» — в конце
    $order = ['PRICE' => 0, 'NET_WEIGHT' => 1, 'PACKING' => 2, 'ROAST' => 3, 'COUNTRY' => 4, 'TEA_KIND' => 5, 'TASTE' => 6, 'EFFECT' => 7, 'PROCESSING' => 8, 'BADGES' => 99];
    $items = $arResult['ITEMS'];
    uasort($items, fn($a, $b) => ($order[isset($a['PRICE']) ? 'PRICE' : $a['CODE']] ?? 50) <=> ($order[isset($b['PRICE']) ? 'PRICE' : $b['CODE']] ?? 50));
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
      <details open><summary>Цена, ₽</summary><div class="range">
        <input name="<?= $min['CONTROL_NAME'] ?>" value="<?= $min['HTML_VALUE'] ?>" placeholder="от <?= bt_fmt((float)($min['FILTERED_VALUE'] ?? $min['VALUE'])) ?>" aria-label="Цена от" inputmode="numeric" autocomplete="off">
        <input name="<?= $max['CONTROL_NAME'] ?>" value="<?= $max['HTML_VALUE'] ?>" placeholder="до <?= bt_fmt((float)($max['FILTERED_VALUE'] ?? $max['VALUE'])) ?>" aria-label="Цена до" inputmode="numeric" autocomplete="off">
      </div></details>
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
        $open = ++$group <= 3; // первые группы раскрыты, как в макете
        foreach ($values as $v) {
            if (!empty($v['CHECKED'])) {
                $open = true;
                $chips[] = [$item['NAME'], $v['VALUE'], [$v['CONTROL_NAME']]];
            }
        } ?>
      <details<?= $open ? ' open' : '' ?>><summary><?= $e($item['NAME']) ?></summary>
      <?php foreach ($values as $v): $off = !empty($v['DISABLED']) && empty($v['CHECKED']); ?>
        <label class="opt"><input type="checkbox" name="<?= $v['CONTROL_NAME'] ?>" value="<?= $v['HTML_VALUE'] ?>"<?= !empty($v['CHECKED']) ? ' checked' : '' ?><?= $off ? ' disabled' : '' ?>><?= $e($v['VALUE']) ?><span class="n"><?= $off ? 0 : (int)($v['ELEMENT_COUNT'] ?? 0) ?></span></label>
      <?php endforeach ?>
      </details>
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
          if(o.type==='checkbox'){ o.disabled=n.disabled; o.closest('.opt').querySelector('.n').textContent=n.closest('.opt').querySelector('.n').textContent; }
          else o.placeholder=n.placeholder;
        });
        fa.textContent=cnt2?`Показать ${cnt2.textContent}`:'Показать товары';
        if(push) history.pushState({bt:1},'',u); else history.replaceState({bt:1},'',u);
        /* новые карточки: степперы корзины, сравнение, избранное */
        Object.keys(BT_CART).forEach(id=>document.dispatchEvent(new CustomEvent('bt:cart',{detail:{id,q:BT_CART[id]}})));
        BT_cmpUpdate(); window.BT_favUpdate&&BT_favUpdate();
      }).catch(err=>{ if(err.name!=='AbortError') location.href=u; });
  };
  form.addEventListener('change',e=>{ if(e.target.type==='checkbox') load(buildUrl(),true); });
  let pt; form.addEventListener('input',e=>{ if(e.target.type==='checkbox') return; clearTimeout(pt); pt=setTimeout(()=>load(buildUrl(),true),700); });
  form.addEventListener('submit',e=>{ e.preventDefault(); load(buildUrl(),true); });
  /* снять одно условие чипсом или сбросить всё — тоже без перезагрузки */
  document.addEventListener('click',e=>{
    const a=e.target.closest('#fChips a[data-names]');
    if(a){ e.preventDefault(); a.dataset.names.split(',').forEach(n=>form.querySelectorAll(`[name="${CSS.escape(n)}"]`).forEach(i=>{ if(i.type==='checkbox') i.checked=false; else i.value=''; })); load(buildUrl(),true); return; }
    const r=e.target.closest('[data-freset]');
    if(r){ e.preventDefault(); form.querySelectorAll('input[name]').forEach(i=>{ if(i.type==='checkbox') i.checked=false; else if(i.type!=='hidden') i.value=''; }); load(buildUrl(),true); }
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
$APPLICATION->AddViewContent('bt_cat_chips', $html . '</div>');
