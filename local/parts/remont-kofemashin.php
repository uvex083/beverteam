<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
/** @var array|null $brand бренд из ИБ repair_brands (страница бренда) или null (общая страница ремонта) */
// «Ремонт кофемашин» по макету service-repair.html: тексты — ИБ типа «Сервис», бренды — только активные элементы ИБ repair_brands

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$co = bt_contacts();
$top = bt_block('repair_top');
$price = bt_block('servis_price');
$form = bt_block('repair_form');
$brands = bt_blocks('repair_brands');
$others = array_filter($brands, fn($b) => !$brand || $b['id'] !== $brand['id']);
$note = $brand && $brand['note'] !== '' ? mb_strtoupper(mb_substr($brand['note'], 0, 1)) . mb_substr($brand['note'], 1) . '.' : '';

if ($brand) {
    $APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'Service',
        'name' => 'Ремонт кофемашин ' . $brand['name'] . ' в Екатеринбурге', 'serviceType' => 'Ремонт и обслуживание кофемашин',
        'brand' => ['@type' => 'Brand', 'name' => $brand['name']], 'areaServed' => ['@type' => 'City', 'name' => 'Екатеринбург'],
        'provider' => ['@type' => 'LocalBusiness', '@id' => 'https://beverteam.ru/#org']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
}
?>
<div class="wrap repp">
  <?php bt_crumbs() ?>
  <div class="pagehead">
    <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
    <p class="sub"><?= $e(trim(($top['subtitle'] ?? '') . ' ' . $note)) ?></p>
    <div class="row" style="margin-top:22px;gap:12px">
      <?= bt_btn($top['btn_text'] ?? '', $top['btn_link'] ?? '') ?>
      <?php if (!empty($co['phone1'])): ?><a class="btn btn--line" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a><?php endif ?>
      <?php if ($brand && $brand['authorized'] !== ''): ?><span class="badge badge--ok">Авторизованный сервис</span><?php endif ?>
    </div>
  </div>

  <?php if ($strip = bt_list('repair_strip')): ?>
  <div class="strip"><div class="strip__in">
    <?php foreach ($strip as $s): ?><div class="strip__i"><span class="ic"><?= $s['icon'] ?></span><p><?= $e($s['name']) ?></p></div><?php endforeach ?>
  </div></div>
  <?php endif ?>

  <?php if ($symp = bt_list('repair_symptoms')): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:8px">С чем обращаются чаще всего</h2>
    <p class="muted" style="margin:0 0 22px">Отметьте, что происходит с вашей машиной, — симптомы попадут в заявку, инженер приедет подготовленным.</p>
    <div class="symp" data-symp><?php foreach ($symp as $s): ?><label><input type="checkbox" value="<?= $e($s['name']) ?>"> <?= $e($s['name']) ?></label><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($price): ?>
  <section class="sec sec--t0"><div class="pl">
    <div><div class="mono" style="color:var(--lime)"><?= $e($price['caption'] ?? '') ?></div><h2 class="display h2" style="margin:14px 0"><?= bt_title($price['title'] ?? '') ?></h2>
      <?php if (!empty($price['subtitle'])): ?><p><?= $e($price['subtitle']) ?></p><?php endif ?>
      <a class="btn" href="#form">Оставить заявку</a></div>
    <ul><?php foreach ($price['items'] ?? [] as [$t, $v]): ?><li><?= $e($t) ?> <i><?= $e($v) ?></i></li><?php endforeach ?></ul>
  </div></section>
  <?php endif ?>

  <?php if ($brand && $brand['models'] !== ''): ?>
  <section class="sec sec--t0" id="models">
    <h2 class="display h2" style="margin-bottom:8px">Модели, с которыми работаем</h2>
    <p class="muted" style="margin:0"><?= $e($brand['models']) ?>. Если вашей модели нет в списке, всё равно звоните: механика у линейки общая.</p>
  </section>
  <?php endif ?>

  <?php if ($faq = bt_blocks('repair_faq')): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:20px">Вопросы о ремонте</h2>
    <div class="faq"><?php foreach ($faq as $i => $q): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q['name']) ?></summary><div class="faq__a"><?= $q['html'] ?></div></details><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0" id="form"><form class="card form" data-form="lead" novalidate>
    <div><div class="mono muted">Заявка</div><h2 class="display h2" style="margin:14px 0 10px"><?= $e($form['title'] ?? 'Вызвать инженера') ?></h2>
      <?php if (!empty($form['subtitle'])): ?><p class="muted" style="margin:0;max-width:40ch"><?= $e($form['subtitle']) ?></p><?php endif ?></div>
    <div>
      <input type="hidden" name="topic" value="Вызов инженера<?= $brand ? ': ремонт ' . $e($brand['name']) : '' ?>">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="field"><label>Модель кофемашины</label><input name="model" maxlength="150" placeholder="Например, <?= $e($brand ? $brand['name'] . ' ' . trim(explode(',', $brand['models'])[0]) : 'Jetinno JL15 VIVA') ?>"></div>
      <div class="field"><label>Что случилось</label><textarea name="message" rows="3" maxlength="2000" data-symp-to placeholder="Опишите проблему или отметьте симптомы выше"></textarea></div>
      <?= bt_form_tail($form['btn_text'] ?? 'Вызвать инженера') ?>
    </div>
  </form></section>

  <?php if ($others): ?>
  <section class="sec sec--t0" id="brands">
    <h2 class="display h2" style="margin-bottom:20px"><?= $brand ? 'Ремонтируем и другие марки' : 'Ремонт по маркам' ?></h2>
    <div class="brands">
      <?php foreach ($others as $b): ?><a href="/servis/remont-kofemashin/<?= $e($b['code']) ?>/"><b><?= $e($b['name']) ?></b><small><?= $e($b['models']) ?></small><?php if ($b['authorized'] !== ''): ?><span class="badge badge--ok">Авторизованный сервис</span><?php endif ?></a><?php endforeach ?>
    </div>
  </section>
  <?php endif ?>
</div>
