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
$stats = array_filter([
    [$n, $word($n, 'объект', 'объекта', 'объектов') . ' с нашими кофемашинами'],
    [count($cities), $word(count($cities), 'город', 'города', 'городов') . ' Свердловской области'],
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
  <div class="klbar" data-kl-bar>
    <?php foreach ($groups as $g => [$label, $opts]): ?>
    <div class="klf" role="group" aria-label="<?= $e($label) ?>">
      <span class="klf__l"><?= $e($label) ?></span>
      <div class="klf__r">
        <button class="chipx" type="button" data-kl-f="<?= $g ?>" data-v="" aria-pressed="true">Все <s><?= $n ?></s></button>
        <?php foreach ($opts as $v => $t): ?><button class="chipx" type="button" data-kl-f="<?= $g ?>" data-v="<?= $e($v) ?>" aria-pressed="false"><?= $e($t) ?> <s></s></button><?php endforeach ?>
      </div>
    </div>
    <?php endforeach ?>
    <p class="klcount" aria-live="polite"><span data-kl-count>Показано <?= $n ?> из <?= $n ?></span> <button class="link" type="button" data-kl-reset hidden>Сбросить фильтры</button></p>
  </div>

  <div class="klgrid" data-kl-grid>
    <?php foreach ($cases as $i => $c):
        $m = $c['model'];
        $alt = $c['name'] . ($c['city'] !== '' ? ', ' . $c['city'] : '') . ($m ? ' — ' . $m['n'] : '');
        $cnt = count($c['photos']);
        $row = intdiv($i, 2); ?>
    <article class="klc<?= $row % 2 ? ' is-flip' : '' ?><?= ($row + $i) % 2 ? '' : ' is-dark' ?>" id="client-<?= $c['id'] ?>" style="view-transition-name:kl<?= $c['id'] ?>"
      data-obj="<?= $e($c['seg']) ?>" data-model="<?= $e($m['code'] ?? '') ?>" data-city="<?= $e($c['city']) ?>"
      data-kl-ph="<?= $e(json_encode(array_column($c['photos'], 'b'), JSON_UNESCAPED_SLASHES)) ?>" data-kl-cap="<?= $e($alt) ?>">
      <a class="klc__ph" href="<?= $e($c['photos'][0]['b']) ?>" data-kl-open aria-label="<?= $e($c['name']) ?>: фото на весь экран">
        <img src="<?= $e($c['photos'][0]['t']) ?>" width="800" height="800" alt="<?= $e($alt) ?>" loading="<?= $i < 4 ? 'eager' : 'lazy' ?>">
        <?php if ($c['segn'] !== ''): ?><span class="klc__seg"><?= $e($c['segn']) ?></span><?php endif ?>
        <span class="klc__zoom"><?= bt_icon('search') ?><?= $cnt > 1 ? $cnt . ' фото' : 'Смотреть' ?></span>
      </a>
      <div class="klc__t">
        <?php if ($c['city'] !== ''): ?><p class="klc__city"><?= $e($c['city']) ?></p><?php endif ?>
        <div class="klc__name th th3"><?= $e($c['name']) ?></div>
        <?php if ($c['why'] !== ''): ?><p class="klc__why"><?= $e($c['why']) ?></p><?php endif ?>
        <div class="klc__ft">
          <?php if ($m): ?><a class="klc__m" href="<?= $e($m['url']) ?>"><?php if ($m['img']): ?><img src="<?= $e($m['img']) ?>" alt="" width="44" height="44" loading="lazy"><?php endif ?><span><small><?= $m['rent'] ? 'В аренду' : 'Кофемашина' ?></small><b><?= $e($m['n']) ?></b></span></a><?php endif ?>
          <?php if ($c['cups']): ?><p class="klc__cups"><b><?= $c['cups'] ?></b><small><?= $word($c['cups'], 'чашка', 'чашки', 'чашек') ?><br>в день</small></p><?php endif ?>
        </div>
      </div>
    </article>
    <?php endforeach ?>
  </div>
  <p class="klempty" data-kl-empty hidden>Таких установок пока нет на странице. <button class="link" type="button" data-kl-reset>Сбросить фильтры</button></p>
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
