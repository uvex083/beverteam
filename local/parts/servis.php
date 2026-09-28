<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Сервис» по макету services.html: направления, прайс, бренды ремонта — ИБ типа «Сервис», оборудование — из аренды и каталога

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$head = bt_block('servis_head');
$price = bt_block('servis_price');
$brandsB = bt_block('servis_brands');
$data = bt_catalog_data();
$minRent = $data['rent'] ? min(array_column($data['rent'], 'p')) : 0;
$machinePrices = array_filter(array_column($data['machines'], 'p'));
$minMachine = $machinePrices ? min($machinePrices) : 0;
?>
<div class="wrap servp">
  <?php bt_crumbs() ?>
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1><?php if (!empty($head['subtitle'])): ?><p class="sub"><?= $e($head['subtitle']) ?></p><?php endif ?></div>

  <div class="grid g3">
    <?php foreach (bt_blocks('servis_dirs') as $i => $d):
        $link = $d['link'] ?: '#';
        $p = $d['price'];
        if ($p === '' && $link === '/arenda-kofemashin/' && $minRent) {
            $p = 'от ' . bt_fmt($minRent);
        } elseif ($p === '' && str_starts_with($link, '/catalog/') && $minMachine) {
            $p = 'от ' . bt_fmt($minMachine);
        }
    ?>
    <a class="dir" href="<?= $e($link) ?>"><span class="num"><?= sprintf('%02d', $i + 1) ?></span><h2><span><?= $e($d['name']) ?></span></h2>
      <?php if ($d['html'] !== ''): ?><p><?= $d['html'] ?></p><?php endif ?>
      <?php if ($d['items']): ?><ul><?php foreach ($d['items'] as [$t]): ?><li><?= $e($t) ?></li><?php endforeach ?></ul><?php endif ?>
      <span class="foot"><b><?= $e($p) ?><?php if ($d['price_note'] !== ''): ?><s><?= $e($d['price_note']) ?></s><?php endif ?></b><span class="link"><?= $e($d['link_text'] ?: 'Подробнее →') ?></span></span></a>
    <?php endforeach ?>
  </div>

  <?php if ($price): ?>
  <section class="sec" id="price"><div class="pl">
    <div><div class="mono" style="color:var(--lime)"><?= $e($price['caption'] ?? '') ?></div><h2 class="display h2" style="margin:14px 0"><?= bt_title($price['title'] ?? '') ?></h2>
      <?php if (!empty($price['subtitle'])): ?><p><?= $e($price['subtitle']) ?></p><?php endif ?>
      <?= bt_btn($price['btn_text'] ?? '', $price['btn_link'] ?? '') ?></div>
    <ul><?php foreach ($price['items'] ?? [] as [$t, $v]): ?><li><?= $e($t) ?> <i><?= $e($v) ?></i></li><?php endforeach ?></ul>
  </div></section>
  <?php endif ?>

  <?php if ($brands = bt_blocks('repair_brands')): ?>
  <section class="sec sec--t0" id="brands">
    <h2 class="display h2" style="margin-bottom:8px"><?= $e($brandsB['title'] ?? 'Ремонт по маркам') ?></h2>
    <?php if (!empty($brandsB['subtitle'])): ?><p class="muted" style="margin:0 0 20px;max-width:60ch"><?= $e($brandsB['subtitle']) ?></p><?php endif ?>
    <div class="brands">
      <?php foreach ($brands as $b): ?><a href="/servis/remont-kofemashin/<?= $e($b['code']) ?>/"><b><?= $e($b['name']) ?></b><small><?= $e($b['models']) ?></small><?php if ($b['authorized'] !== ''): ?><span class="badge badge--ok">Авторизованный сервис</span><?php endif ?></a><?php endforeach ?>
    </div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0">
    <div class="row between" style="margin-bottom:22px;gap:14px"><h2 class="display h2">Оборудование</h2><div class="pill-tabs" role="tablist"><button type="button" role="tab" aria-selected="true" data-tab="rent">Аренда</button><button type="button" role="tab" aria-selected="false" data-tab="machines">Покупка</button></div></div>
    <div class="grid g4" data-tabpane="rent"><?php foreach ($data['rent'] as $m) echo bt_card($m) ?></div>
    <div class="grid g4" data-tabpane="machines" hidden><?php foreach ($data['machines'] as $m) echo bt_card($m) ?></div>
  </section>
</div>
