<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Фильтр в разметке catalog.html: обычная GET-форма, без AJAX-пересчёта; выбранные значения — чипсами над списком
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
    <a class="link" href="<?= $e($reset) ?>" style="font-size:13px;margin-left:auto">Сбросить</a>
    <button class="filters__x" type="button" id="fClose" aria-label="Закрыть фильтр">×</button>
  </div>
  <div class="filters__body">
    <div class="row between fdesk" style="margin:0 0 6px"><b>Фильтр</b><a class="link" href="<?= $e($reset) ?>" style="font-size:12.5px">Сбросить</a></div>
    <?php foreach ($arResult['ITEMS'] as $item):
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
        <input name="<?= $min['CONTROL_NAME'] ?>" value="<?= $min['HTML_VALUE'] ?>" placeholder="от <?= bt_fmt((float)$min['VALUE']) ?>" aria-label="Цена от" inputmode="numeric">
        <input name="<?= $max['CONTROL_NAME'] ?>" value="<?= $max['HTML_VALUE'] ?>" placeholder="до <?= bt_fmt((float)$max['VALUE']) ?>" aria-label="Цена до" inputmode="numeric">
      </div></details>
        <?php continue;
        endif;
        $values = array_filter($item['VALUES'], fn($v) => empty($v['DISABLED']) || !empty($v['CHECKED']));
        if (!$values || isset($values['MIN'])) {
            continue;
        }
        $open = ++$group <= 3; // первые группы раскрыты, как в макете
        foreach ($values as $v) {
            if (!empty($v['CHECKED'])) {
                $open = true;
                $chips[] = [$item['NAME'], $v['VALUE'], [$v['CONTROL_NAME']]];
            }
        } ?>
      <details<?= $open ? ' open' : '' ?>><summary><?= $e($item['NAME']) ?></summary>
      <?php foreach ($values as $v): ?>
        <label class="opt"><input type="checkbox" name="<?= $v['CONTROL_NAME'] ?>" value="<?= $v['HTML_VALUE'] ?>"<?= !empty($v['CHECKED']) ? ' checked' : '' ?>><?= $e($v['VALUE']) ?><span class="n"><?= (int)($v['ELEMENT_COUNT'] ?? 0) ?></span></label>
      <?php endforeach ?>
      </details>
    <?php endforeach ?>
  </div>
  <button class="btn btn--block btn--sm fbtn" style="margin-top:14px" id="fApply" type="submit" name="set_filter" value="Y">Показать товары</button>
  </form>
</aside>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  /* мобильная шторка фильтра: блокировка фона, Esc, закрытие по кнопке */
  const filters=document.getElementById('filters'), fb=document.querySelector('.mob-f'), fx=document.getElementById('fClose');
  const openF=()=>{filters.classList.add('open');BT_lock();setTimeout(()=>fx.focus(),40);};
  const closeF=()=>{filters.classList.remove('open');BT_lock();fb&&fb.focus();};
  fb&&fb.addEventListener('click',openF);
  fx.addEventListener('click',closeF);
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&filters.classList.contains('open'))closeF();});
});
</script>
<?php
if ($chips) {
    // ссылка на снятие одного условия: текущий адрес без его параметров
    $html = '<div class="chips">';
    foreach ($chips as [$name, $value, $params]) {
        $url = $APPLICATION->GetCurPageParam('', $params);
        $html .= '<span class="chip"><b>' . $e($name) . ':</b> ' . $e(mb_strtolower($value)) . ' <a href="' . $e($url) . '" aria-label="Убрать">×</a></span>';
    }
    $APPLICATION->AddViewContent('bt_cat_chips', $html . '</div>');
}
