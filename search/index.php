<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// Результаты поиска: те же данные, что у живого поиска в шапке (товары, разделы и услуги, журнал); на страницу ведёт SearchAction главной
$q = trim((string)($_GET['q'] ?? ''));
$APPLICATION->SetTitle($q !== '' ? 'Результаты поиска' : 'Поиск по сайту');
$APPLICATION->SetPageProperty('title', ($q !== '' ? '«' . $q . '» — поиск' : 'Поиск') . ' | Beverteam');
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
$APPLICATION->AddChainItem('Поиск');

$found = bt_search($q);
[$products, $pages, $posts] = [$found['prod'], $found['pages'], $found['posts']];
$e = fn($s) => htmlspecialcharsbx((string)$s);
$plural = fn(int $n, array $f) => $f[($n % 10 == 1 && $n % 100 != 11) ? 0 : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 1 : 2)];
$groups = array_filter(['prod' => ['Товары', count($products)], 'pages' => ['Разделы и услуги', count($pages)], 'posts' => ['Журнал', count($posts)]], fn($g) => $g[1] > 0);
$total = array_sum(array_column($groups, 1));
$tab = isset($groups[$_GET['t'] ?? '']) ? $_GET['t'] : 'all';
$show = fn(string $g) => isset($groups[$g]) && ($tab === 'all' || $tab === $g);
$tabUrl = fn(string $t) => '/search/?q=' . urlencode($q) . ($t === 'all' ? '' : '&t=' . $t);
$co = bt_contacts();
$cfg = bt_search_cfg();
$hints = $cfg['hints'] ?: ['кофе в зёрнах', 'аренда кофемашины', 'ремонт кофемашины', 'Jetinno', 'чай', 'дрип-пакеты'];
?>
<div class="wrap spage">
  <?php bt_crumbs() ?>
  <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
  <form class="spage__f<?= $q !== '' ? ' has-q' : '' ?>" action="/search/" method="get" role="search">
    <?= bt_icon('search') ?>
    <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Кофе, чай, кофемашина или услуга" aria-label="Поисковый запрос" autocomplete="off">
    <button type="button" class="spage__clr" aria-label="Очистить"><?= bt_icon('close') ?></button>
    <button type="submit" class="btn">Найти</button>
  </form>

  <div id="spRes">
  <?php if ($total): ?>
    <p class="spage__found">По запросу <mark class="spage__q"><?= $e($found['q']) ?></mark> <?= $plural($total, ['найден', 'найдено', 'найдено']) ?> <?= $total ?> <?= $plural($total, ['результат', 'результата', 'результатов']) ?><?= $found['fixed'] ? '. Вы набрали «' . $e($q) . '» в другой раскладке' : '' ?></p>
    <nav class="spage__tabs" aria-label="Что показать">
      <?php foreach ((count($groups) > 1 ? ['all' => ['Все', $total]] + $groups : []) as $t => [$name, $n]): ?>
        <a class="spage__tab" href="<?= $e($tabUrl($t)) ?>"<?= $t === $tab ? ' aria-current="page"' : '' ?>><?= $name ?><sup><?= $n ?></sup></a>
      <?php endforeach ?>
    </nav>
  <?php elseif ($q !== ''): ?>
    <div class="spage__empty">
      <b>По запросу «<?= $e($q) ?>» ничего не нашлось</b>
      <p>Проверьте написание или сократите запрос. Можно заглянуть в <a class="link" href="/catalog/">каталог</a> или позвонить: <a class="link" href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a></p>
    </div>
  <?php endif ?>
  <?php if (!$total): ?>
    <div class="spage__sum"><span>Часто ищут:</span><?php foreach ($hints as $h): ?><a class="chipx" href="/search/?q=<?= urlencode($h) ?>"><?= $e($h) ?></a><?php endforeach ?></div>
    <?php if ($q !== '' && $cfg['secs']): ?><div class="spage__sum"><span>Разделы:</span><?php foreach ($cfg['secs'] as $c): ?><a class="chipx" href="<?= $e($c['u']) ?>"><?= $e($c['t']) ?></a><?php endforeach ?></div><?php endif ?>
  <?php endif ?>

  <?php if ($show('prod')): ?>
    <section class="spage__sec">
      <?php if ($tab === 'all'): ?><h2 class="display h2">Товары <sup><?= count($products) ?></sup></h2><?php endif ?>
      <div class="grid g4"><?php foreach ($products as $m) { echo bt_card($m); } ?></div>
    </section>
  <?php endif ?>
  <?php if ($show('pages')): ?>
    <section class="spage__sec">
      <?php if ($tab === 'all'): ?><h2 class="display h2">Разделы и услуги <sup><?= count($pages) ?></sup></h2><?php endif ?>
      <div class="spage__pages">
        <?php foreach ($pages as $p): ?>
          <a href="<?= $e($p['u']) ?>"><span><b><?= $e($p['t']) ?></b><span><?= $e($p['d']) ?></span></span><?= bt_icon('arrR') ?></a>
        <?php endforeach ?>
      </div>
    </section>
  <?php endif ?>
  <?php if ($show('posts')): ?>
    <section class="spage__sec">
      <?php if ($tab === 'all'): ?><h2 class="display h2">Журнал <sup><?= count($posts) ?></sup></h2><?php endif ?>
      <div class="news"><?php foreach ($posts as $p) { echo bt_post_card($p); } ?></div>
    </section>
  <?php endif ?>
  </div>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
