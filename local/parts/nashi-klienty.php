<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Наши клиенты»: установки кофемашин с фото — ИБ типа «Наши клиенты» (bt_clients_setup.php); фильтры и шахматка — ui.js (data-kl)

$e = fn($s) => htmlspecialcharsbx((string)$s);
$word = fn(int $n, string $one, string $few, string $many) => ($n % 10 === 1 && $n % 100 !== 11) ? $one : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? $few : $many);
$head = bt_block('clients_head');
$cta = bt_block('clients_cta');
$cases = array_values(array_filter(bt_clients(), fn($c) => $c['photos']));
$n = count($cases);
$demo = (bool)array_filter($cases, fn($c) => $c['demo']);

// варианты фильтров: тип объекта — в порядке списка в админке, модели — по названию, города — по числу установок
$segs = $models = $cities = [];
foreach ($cases as $c) {
    $c['seg'] !== '' and $segs[$c['seg']] = [$c['segn'], $c['segs']];
    $c['model'] and $models[$c['model']['code']] = trim(preg_replace('/^Jetinno\s+/u', '', $c['model']['n']));
    $c['city'] !== '' and $cities[$c['city']] = ($cities[$c['city']] ?? 0) + 1;
}
uasort($segs, fn($a, $b) => $a[1] <=> $b[1]);
asort($models, SORT_NATURAL);
arsort($cities);
$groups = array_filter([
    'obj' => ['Объект', array_map(fn($s) => $s[0], $segs)],
    'model' => ['Модель', $models],
    'city' => ['Город', array_combine(array_keys($cities), array_keys($cities))],
], fn($g) => count($g[1]) > 1);
$cups = array_sum(array_column($cases, 'cups'));
// иконки типов объектов по коду значения списка «Тип объекта»; для нового типа — общая
$icons = [
    'all' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.6"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.6"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.6"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.6"/>',
    'bc' => '<rect x="5" y="3" width="14" height="18" rx="1.6"/><path d="M9 7h1.5M13.5 7H15M9 11h1.5M13.5 11H15M9 15h1.5M13.5 15H15M10.5 21v-2.5h3V21"/>',
    'ofis' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8.5 7V5.5c0-.8.7-1.5 1.5-1.5h4c.8 0 1.5.7 1.5 1.5V7M3 12.5h18M11 12.5v1.5h2v-1.5"/>',
    'kafe' => '<path d="M4 9h12.5v4.5A5.5 5.5 0 0 1 11 19H9.5A5.5 5.5 0 0 1 4 13.5V9Z"/><path d="M16.5 10.5h1.25a2.5 2.5 0 0 1 0 5H16M8 3.5c0 1.2 1 1.3 1 2.5M12 3.5c0 1.2 1 1.3 1 2.5"/>',
    'pekarnya' => '<path d="M3.5 13.5c0-4 3.8-6.5 8.5-6.5s8.5 2.5 8.5 6.5v3a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2v-3Z"/><path d="M8.5 9.5 9.8 13M12 9v3.6M15.5 9.5 14.2 13"/>',
    'azs' => '<path d="M4.5 20V5.5c0-1.1.9-2 2-2h5c1.1 0 2 .9 2 2V20M3 20h12M4.5 10.5h9"/><path d="M13.5 8.5h1.8l2.7 2.7v5.3a1.5 1.5 0 0 0 3 0V9.2L18 6.2"/>',
    'magazin' => '<path d="M4 9.5 5.5 4h13L20 9.5M4 9.5h16v1a3 3 0 0 1-5.3 1.9A3 3 0 0 1 12 13.5a3 3 0 0 1-2.7-1.1A3 3 0 0 1 4 10.5v-1Z"/><path d="M5.5 13v7h13v-7M10 20v-4h4v4"/>',
    'gostinica' => '<path d="M3 18.5V6M3 14.5h18v4M21 14.5V12a3 3 0 0 0-3-3h-7v5.5"/><circle cx="7" cy="11" r="2"/>',
    'avtosalon' => '<path d="M5 15.5v-3.2L7 7.5h10l2 4.8v3.2"/><path d="M3.5 15.5h17v2.5a1 1 0 0 1-1 1h-1.8a1 1 0 0 1-1-1v-.5H7.3v.5a1 1 0 0 1-1 1H4.5a1 1 0 0 1-1-1v-2.5ZM5 12.3h14"/>',
    'meropriyatie' => '<path d="M5 21 9.5 7.5l7 7L3 19"/><path d="M14 4.5c.6.6.6 1.5 0 2.1M18.5 9.5c.7-.5 1.6-.4 2.1.2M16 3l.5 1.5M20.5 6.5 22 7M17 12.5l1.5.5"/>',
    'drugoe' => '<path d="M12 21s-6.5-6.1-6.5-11A6.5 6.5 0 0 1 18.5 10c0 4.9-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.4"/>',
];
$ico = fn(string $k) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($icons[$k] ?? $icons['drugoe']) . '</svg>';
$stats = array_filter([
    [$n, $word($n, 'объект', 'объекта', 'объектов') . ' с нашими кофемашинами'],
    [count($cities), $word(count($cities), 'город', 'города', 'городов') . (($reg = bt_cities_region(array_keys($cities))) !== '' ? ' ' . $reg : '')],
    [count($models), $word(count($models), 'модель', 'модели', 'моделей') . ' Jetinno'],
    [$cups ? '≈ ' . number_format($cups, 0, '', "\u{00A0}") : 0, 'чашек в день на всех машинах'],
], fn($s) => $s[0]);
?>
<div class="wrap klp">
  <?php bt_crumbs() ?>

  <div class="klhero">
    <div>
      <?php if (!empty($head['caption'])): ?><p class="klkick"><?= $e($head['caption']) ?></p><?php endif ?>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <?php if (!empty($head['subtitle'])): ?><p class="klsub"><?= $e($head['subtitle']) ?></p><?php endif ?>
      <?php if (!empty($head['btn_text'])): ?><div class="row" style="margin-top:24px"><?= bt_btn($head['btn_text'], $head['btn_link'] ?? '#cta') ?></div><?php endif ?>
    </div>
    <?php if ($stats): ?><div class="r2facts klfacts"><?php foreach ($stats as [$b, $s]): ?><div><b><?= $e($b) ?></b><span><?= $e($s) ?></span></div><?php endforeach ?></div><?php endif ?>
  </div>

  <?php if ($cases): ?>
  <?= bt_demo_note('clients', $demo) ?>
  <section aria-labelledby="klh2">
  <h2 class="display h2 klh2" id="klh2"><?= $e(($head['list_title'] ?? '') ?: 'Где работают наши кофемашины') ?></h2>
  <div class="kl1" data-kl-bar>
    <?php if (isset($groups['obj'])): ?>
    <div class="kl1__seg" role="group" aria-label="Тип объекта">
      <?php foreach (['' => 'Все объекты'] + $groups['obj'][1] as $v => $t): ?>
      <button class="kl1__b" type="button" data-kl-f="obj" data-v="<?= $e($v) ?>" data-l="<?= $e($v === '' ? 'все' : $t) ?>" aria-pressed="<?= $v === '' ? 'true' : 'false' ?>"><?= $ico($v === '' ? 'all' : $v) ?><span><?= $e($t) ?></span><s><?= $v === '' ? $n : '' ?></s></button>
      <?php endforeach ?>
    </div>
    <?php endif ?>
    <div class="kl1__row">
      <?php foreach (['model' => 'Все модели', 'city' => 'Все города'] as $g => $all): if (!isset($groups[$g])) continue; ?>
      <div class="kldd" data-kl-dd>
        <button class="kldd__t" type="button" aria-expanded="false" data-kl-dd-t><small><?= $e($groups[$g][0]) ?></small> <b data-kl-cur="<?= $g ?>">все</b><svg class="kldd__ch" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg></button>
        <div class="kldd__p" role="group" aria-label="<?= $e($groups[$g][0]) ?>" hidden>
          <?php foreach (['' => $all] + $groups[$g][1] as $v => $t): ?><button class="kldd__o" type="button" data-kl-f="<?= $g ?>" data-v="<?= $e($v) ?>" data-l="<?= $e($v === '' ? 'все' : $t) ?>" aria-pressed="<?= $v === '' ? 'true' : 'false' ?>"><?= $e($t) ?> <s><?= $v === '' ? $n : '' ?></s></button><?php endforeach ?>
        </div>
      </div>
      <?php endforeach ?>
      <p class="klcount"><span data-kl-count aria-live="polite">Показано <?= $n ?> из <?= $n ?></span> <button class="link" type="button" data-kl-reset data-kl-auto hidden>Сбросить фильтры</button></p>
    </div>
  </div>

  <div class="klgrid" data-kl-grid>
    <?php foreach ($cases as $i => $c):
        $m = $c['model'];
        $alt = $c['name'] . ($c['city'] !== '' ? ', ' . $c['city'] : '') . ($m ? ' — ' . $m['n'] : '');
        $cnt = count($c['photos']);
        $row = intdiv($i, 2); ?>
    <article class="klc<?= $row % 2 ? ' is-flip' : '' ?><?= ($c['tone'] !== '' ? $c['tone'] === 'dark' : ($row + $i) % 2 === 0) ? ' is-dark' : '' ?>" id="client-<?= $c['id'] ?>" style="view-transition-name:kl<?= $c['id'] ?>"
      data-obj="<?= $e($c['seg']) ?>" data-model="<?= $e($m['code'] ?? '') ?>" data-city="<?= $e($c['city']) ?>"<?= $c['tone'] !== '' ? ' data-tone="' . $e($c['tone']) . '"' : '' ?>
      data-kl-ph="<?= $e(json_encode(array_column($c['photos'], 'b'), JSON_UNESCAPED_SLASHES)) ?>" data-kl-cap="<?= $e($alt) ?>">
      <a class="klc__ph" href="<?= $e($c['photos'][0]['b']) ?>" data-kl-open aria-label="<?= $e($c['name']) ?>: фото на весь экран">
        <img src="<?= $e($c['photos'][0]['t']) ?>" width="800" height="800" alt="<?= $e($alt) ?>" loading="<?= $i < 4 ? 'eager' : 'lazy' ?>">
        <?php if ($c['segn'] !== ''): ?><span class="klc__seg"><?= $e($c['segn']) ?></span><?php endif ?>
        <span class="klc__zoom<?= $m ? ' klc__zoom--top' : '' ?>"><?= bt_icon('search') ?><?= $cnt > 1 ? $cnt . ' фото' : 'Смотреть' ?></span>
      </a>
      <div class="klc__t">
        <?php if ($c['city'] !== ''): ?><p class="klc__city"><?= $e($c['city']) ?></p><?php endif ?>
        <div class="klc__name th th3"><?= $e($c['name']) ?></div>
        <?php if ($c['why'] !== ''): ?><p class="klc__why"><?= $e($c['why']) ?></p><?php endif ?>
        <?php if ($m || $c['cups']): ?>
        <div class="klc__ft">
          <?php if ($m): ?><a class="klc__m" href="<?= $e($m['url']) ?>"><?php if ($m['img']): ?><img src="<?= $e($m['img']) ?>" alt="" width="44" height="44" loading="lazy"><?php endif ?><span><small><?= $m['rent'] ? 'В аренду' : 'Кофемашина' ?></small><b><?= $e($m['n']) ?></b></span></a><?php endif ?>
          <?php if ($c['cups']): ?><p class="klc__cups"><b><?= $c['cups'] ?></b><small><?= $word($c['cups'], 'чашка', 'чашки', 'чашек') ?><br>в день</small></p><?php endif ?>
        </div>
        <?php endif ?>
      </div>
    </article>
    <?php endforeach ?>
  </div>
  <p class="klempty" data-kl-empty hidden>Таких установок пока нет на странице. <button class="link" type="button" data-kl-reset>Сбросить фильтры</button></p>
  </section>
  <?php else: ?>
  <p class="muted">Скоро здесь появятся фото наших установок.</p>
  <?php endif ?>

  <?php if ($cta): ?>
  <section class="sec" id="cta"><div class="klcta">
    <div><div class="display h2 th th2"><?= bt_title($cta['title'] ?? '') ?></div><?php if (!empty($cta['subtitle'])): ?><p><?= $e($cta['subtitle']) ?></p><?php endif ?></div>
    <div class="row klcta__b"><?= bt_btn($cta['btn_text'] ?? '', $cta['btn_link'] ?? '') ?><?= bt_btn($cta['btn2_text'] ?? '', $cta['btn2_link'] ?? '', 'btn btn--line') ?></div>
  </div></section>
  <?php endif ?>
</div>
