<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// Результаты поиска: те же данные, что у живого поиска в шапке (товары и статьи); на страницу ведёт SearchAction главной
$q = trim((string)($_GET['q'] ?? ''));
$APPLICATION->SetTitle($q !== '' ? 'Результаты поиска' : 'Поиск по сайту');
$APPLICATION->SetPageProperty('title', ($q !== '' ? '«' . $q . '» — поиск' : 'Поиск') . ' | Beverteam');
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
$APPLICATION->AddChainItem('Поиск');

$norm = fn(string $s) => str_replace('ё', 'е', mb_strtolower($s));
$words = array_filter(preg_split('/\s+/u', $norm($q)));
$match = fn(string $text) => $words && !array_filter($words, fn($w) => !str_contains($norm($text), $w));

$products = $posts = [];
if ($words) {
    foreach (bt_catalog_data() as $list) {
        foreach ($list as $m) {
            if ($match($m['n'] . ' ' . $m['par'] . ' ' . $m['code'])) {
                $products[] = $m;
            }
        }
    }
    foreach (bt_posts() as $p) {
        if ($match($p['t'] . ' ' . $p['lead'])) {
            $posts[] = $p;
        }
    }
}
$e = fn($s) => htmlspecialcharsbx((string)$s);
?>
<div class="wrap">
  <?php bt_crumbs() ?>
  <h1 class="display h1" style="margin:14px 0 22px"><?php $APPLICATION->ShowTitle(false) ?></h1>
  <form class="search" action="/search/" method="get" style="max-width:640px;margin-bottom:28px">
    <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Кофе, чай, кофемашина или услуга" aria-label="Поисковый запрос">
    <button type="submit" aria-label="Найти"><?= bt_icon('search') ?></button>
  </form>
  <?php if ($q !== '' && !$products && !$posts): ?>
    <p class="muted">По запросу «<?= $e($q) ?>» ничего не нашлось. Попробуйте короче или загляните в <a class="link" href="/magazin/">каталог</a>. Можно позвонить: <a class="link" href="<?= bt_contacts()['phone1_href'] ?? '' ?>"><?= bt_contacts()['phone1'] ?? '' ?></a></p>
  <?php endif ?>
  <?php if ($products): ?>
    <p class="muted" style="margin:0 0 16px">Товары: <?= count($products) ?></p>
    <div class="grid g4"><?php foreach ($products as $m) { echo bt_card($m); } ?></div>
  <?php endif ?>
  <?php if ($posts): ?>
    <section class="sec sec--s">
      <h2 class="display h2" style="margin-bottom:20px">Статьи</h2>
      <ul class="prose"><?php foreach ($posts as $p): ?><li><a class="link" href="<?= $e($p['url']) ?>"><?= $e($p['t']) ?></a></li><?php endforeach ?></ul>
    </section>
  <?php endif ?>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
