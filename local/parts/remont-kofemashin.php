<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Ремонт кофемашин»: все тексты — ИБ типа «Ремонт кофемашин» (bt_repair_setup.php), модели Jetinno — из каталога, отзывы — из отзывов о компании

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$plain = fn($h) => trim(html_entity_decode(strip_tags(str_replace('<br>', ' ', (string)$h)), ENT_QUOTES));
$vals = fn($items) => array_values(array_filter(array_column((array)$items, 0), fn($v) => $v !== ''));
$lead = fn(array $b, string $cls = 'muted r2lead') => !empty($b['subtitle']) ? '<p class="' . $cls . '">' . $e($b['subtitle']) . '</p>' : '';
$co = bt_contacts();

$top = bt_block('repair_top');
$facts = bt_blocks('repair_facts');
$sympH = bt_block('repair_symptoms_head');
$symptoms = bt_blocks('repair_symptoms');
$types = bt_block('repair_types');
$jet = bt_block('repair_jetinno');
$priceH = bt_block('repair_price_head');
$price = bt_blocks('repair_price');
$contract = bt_block('repair_contract');
$errH = bt_block('repair_errors_head');
$errors = bt_blocks('repair_errors');
$steps = bt_block('repair_steps');
$whyH = bt_block('repair_why_head');
$why = bt_list('repair_strip');
$revH = bt_block('repair_reviews');
$geo = bt_block('repair_geo');
$form = bt_block('repair_form');
$faqH = bt_block('repair_faq_head');
$faq = bt_blocks('repair_faq');
$seoB = bt_block('repair_seo');
$links = bt_blocks('repair_links');

$typeItems = array_values(array_filter((array)($types['items'] ?? []), fn($t) => $t[0] !== ''));
$brands = $vals($types['brands'] ?? []);
$cities = $vals($geo['items'] ?? []);
$districts = $vals($geo['districts'] ?? []);
$stepItems = array_values(array_filter((array)($steps['items'] ?? []), fn($t) => $t[0] !== ''));
$contractItems = $vals($contract['items'] ?? []);
$models = $jet ? array_values(array_filter(bt_catalog_data()['machines'] ?? [], fn($m) => !empty($m['img']))) : [];
$revs = $revH ? bt_reviews() : [];

// разметка для поисковиков — из тех же данных, что на странице
$num = fn($t) => preg_match('/(\d[\d\x{00A0} ]*)\s*₽/u', (string)$t, $m) ? (int)preg_replace('/\D/', '', $m[1]) : null;
$offer = fn($name, $p, $desc = '') => array_filter(['@type' => 'Offer', 'name' => $name, 'description' => $desc ?: null, 'priceCurrency' => 'RUB',
    'priceSpecification' => $num($p) !== null ? ['@type' => 'PriceSpecification', 'minPrice' => $num($p), 'priceCurrency' => 'RUB'] : null,
    'itemOffered' => ['@type' => 'Service', 'name' => $name]], fn($v) => $v !== null);
$page = 'https://beverteam.ru/servis/remont-kofemashin/';
$org = ['@id' => 'https://beverteam.ru/#org'];
$h1 = $APPLICATION->GetTitle(false);
$graph = [
    ['@type' => 'WebPage', '@id' => $page . '#page', 'url' => $page, 'name' => $h1, 'inLanguage' => 'ru', 'about' => ['@id' => $page . '#service'], 'publisher' => $org],
    ['@type' => 'Service', '@id' => $page . '#service', 'name' => $h1, 'serviceType' => 'Ремонт и обслуживание кофемашин',
        'description' => $top['subtitle'] ?? '', 'provider' => $org,
        'areaServed' => array_merge(array_map(fn($g) => ['@type' => 'City', 'name' => $g], $cities), [['@type' => 'AdministrativeArea', 'name' => 'Свердловская область']]),
        'brand' => array_map(fn($b) => ['@type' => 'Brand', 'name' => $b], $brands),
        'category' => array_column($typeItems, 0),
        'hoursAvailable' => ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '09:00', 'closes' => '17:00'],
        'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => $priceH['title'] ?? 'Цены', 'itemListElement' => array_values(array_filter([
            $price ? ['@type' => 'OfferCatalog', 'name' => 'Прайс', 'itemListElement' => array_map(fn($p) => $offer($p['name'], $p['price'], $p['term'] !== '' ? 'Срок: ' . $p['term'] : ''), $price)] : null,
            $symptoms ? ['@type' => 'OfferCatalog', 'name' => 'Ремонт по неисправностям', 'itemListElement' => array_map(fn($s) => $offer($s['name'], $s['price'], $plain($s['html'])), $symptoms)] : null,
            $contract ? ['@type' => 'OfferCatalog', 'name' => $plain($contract['title'] ?? ''), 'itemListElement' => [$offer($plain($contract['title'] ?? ''), '', implode('; ', $contractItems))]] : null,
        ]))]],
];
$stepItems and $graph[] = ['@type' => 'HowTo', '@id' => $page . '#steps', 'name' => $steps['title'] ?? '',
    'step' => array_map(fn($i, $st) => ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => $st[0], 'text' => $st[1]], array_keys($stepItems), $stepItems)];
$errors and $graph[] = ['@type' => 'ItemList', '@id' => $page . '#errors', 'name' => $errH['title'] ?? '',
    'itemListElement' => array_map(fn($i, $er) => ['@type' => 'ListItem', 'position' => $i + 1, 'item' => ['@type' => 'HowTo', 'name' => 'Что делать: ' . trim($er['name'], '«»'),
        'step' => [['@type' => 'HowToStep', 'name' => 'Самостоятельно', 'text' => $plain($er['html'])], ['@type' => 'HowToStep', 'name' => 'Когда вызвать инженера', 'text' => $er['engineer']]]]], array_keys($errors), $errors)];
$typeItems and $graph[] = ['@type' => 'ItemList', '@id' => $page . '#types', 'name' => $types['title'] ?? '',
    'itemListElement' => array_map(fn($i, $t) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $t[0], 'description' => $t[1]], array_keys($typeItems), $typeItems)];
($faq || $errors) and $graph[] = ['@type' => 'FAQPage', '@id' => $page . '#faq', 'mainEntity' => array_merge(
    array_map(fn($q) => ['@type' => 'Question', 'name' => $q['name'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $plain($q['html'])]], $faq),
    array_map(fn($er) => ['@type' => 'Question', 'name' => 'Кофемашина пишет ' . $er['name'] . ' — что делать?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim($plain($er['html']) . ' ' . $er['engineer'])]], $errors))];
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap repp rep2">
  <?php bt_crumbs() ?>

  <div class="r2hero">
    <div>
      <?php if (!empty($top['caption'])): ?><div class="mono muted"><?= $e($top['caption']) ?></div><?php endif ?>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <?= $lead($top, 'sub') ?>
      <?php if ($hero = $vals($top['items'] ?? [])): ?><ul class="r2chk"><?php foreach ($hero as $h): ?><li><?= $e($h) ?></li><?php endforeach ?></ul><?php endif ?>
      <div class="row" style="gap:12px">
        <?= bt_btn($top['btn_text'] ?? '', $top['btn_link'] ?? '') ?>
        <?php if (!empty($co['phone1'])): ?><a class="btn btn--line" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a><?php endif ?>
      </div>
      <?php if (!empty($top['note'])): ?><p class="muted r2note"><?= $e($top['note']) ?></p><?php endif ?>
    </div>
    <?php if ($facts): ?><div class="r2facts"><?php foreach ($facts as $f): ?><div><b><?= $e($f['name']) ?></b><span><?= $e($plain($f['html'])) ?></span></div><?php endforeach ?></div><?php endif ?>
  </div>

  <?php if ($symptoms): ?>
  <section class="sec sec--t0" id="symptoms">
    <h2 class="display h2"><?= bt_title($sympH['title'] ?? '') ?></h2>
    <?= $lead($sympH) ?>
    <div class="r2symp" data-symp><?php foreach ($symptoms as $s): ?>
      <label><input type="checkbox" value="<?= $e($s['name']) ?>"><span><b><?= $e($s['name']) ?></b><?php if ($s['html'] !== ''): ?><small><?= $s['html'] ?></small><?php endif ?><?php if ($s['price'] !== ''): ?><i><?= $e($s['price']) ?></i><?php endif ?></span></label>
    <?php endforeach ?></div>
    <?php $go = ($form['btn_text'] ?? '') ?: 'Вызвать инженера'; ?>
    <div class="r2sympcta" data-symp-cta>
      <p data-symp-note>Отметьте симптомы или сразу оставьте заявку — инженер уточнит детали по телефону.</p>
      <div class="r2sympcta__b">
        <a class="btn" href="#form" data-symp-go><?= $e($go) ?></a>
        <?php if (!empty($co['phone1'])): ?><a class="btn btn--line" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a><?php endif ?>
      </div>
    </div>
  </section>
  <?php endif ?>

  <?php if ($types): ?>
  <section class="sec sec--t0" id="types">
    <h2 class="display h2"><?= bt_title($types['title'] ?? '') ?></h2>
    <?= $lead($types) ?>
    <?php if ($typeItems): ?><div class="r2types"><?php foreach ($typeItems as [$t, $d]): ?><div class="card"><b><?= $e($t) ?></b><?php if ($d !== ''): ?><p><?= $e($d) ?></p><?php endif ?></div><?php endforeach ?></div><?php endif ?>
    <?php if ($brands): ?><div class="r2geo r2brands"><?php foreach ($brands as $b): ?><span><?= $e($b) ?></span><?php endforeach ?></div><?php endif ?>
  </section>
  <?php endif ?>

  <?php if ($models): ?>
  <section class="sec sec--t0" id="models">
    <h2 class="display h2"><?= bt_title($jet['title'] ?? '') ?></h2>
    <?= $lead($jet) ?>
    <div class="r2models"><?php foreach ($models as $m): ?>
      <a href="<?= $e($m['url']) ?>"><img src="<?= $e($m['img']) ?>" alt="<?= $e($m['n']) ?>" loading="lazy" width="240" height="170"><b><?= $e(preg_replace('/^Кофемашина\s+/u', 'Ремонт ', $m['n'])) ?></b></a>
    <?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($price): ?>
  <section class="sec sec--t0" id="price">
    <div class="r2head"><h2 class="display h2"><?= bt_title($priceH['title'] ?? '') ?></h2><?= bt_btn($priceH['btn_text'] ?? '', $priceH['btn_link'] ?? '', 'link') ?></div>
    <div class="tblw"><table class="tbl r2tbl">
      <thead><tr><th>Услуга</th><th>Срок</th><th>Стоимость</th></tr></thead>
      <tbody><?php foreach ($price as $p): ?><tr><td><?= $e($p['name']) ?></td><td class="muted"><?= $e($p['term']) ?></td><td><b><?= $e($p['price']) ?></b></td></tr><?php endforeach ?></tbody>
    </table></div>
    <?php if (!empty($priceH['caption'])): ?><p class="muted r2foot"><?= $e($priceH['caption']) ?></p><?php endif ?>
  </section>
  <?php endif ?>

  <?php if ($contract): ?>
  <section class="sec sec--t0"><div class="pl">
    <div><?php if (!empty($contract['caption'])): ?><div class="mono" style="color:var(--lime)"><?= $e($contract['caption']) ?></div><?php endif ?>
      <h2 class="display h2" style="margin:14px 0"><?= bt_title($contract['title'] ?? '') ?></h2>
      <?php if (!empty($contract['subtitle'])): ?><p><?= $e($contract['subtitle']) ?></p><?php endif ?>
      <div class="row" style="gap:12px"><?= bt_btn($contract['btn_text'] ?? '', $contract['btn_link'] ?? '') ?>
        <?php if (!empty($contract['btn2_text'])): ?><?= !empty($contract['file'])
            ? '<a class="btn btn--line btn--inv" href="' . $e($contract['file']) . '" target="_blank" rel="noopener" download="obrazec-dogovora-beverteam.pdf">Скачать ' . $e(mb_strtolower($contract['btn2_text'])) . ', PDF</a>'
            : '<a class="btn btn--line btn--inv" href="#form">Запросить ' . $e(mb_strtolower($contract['btn2_text'])) . '</a>' ?><?php endif ?></div></div>
    <?php if ($contractItems): ?><ul class="r2list"><?php foreach ($contractItems as $c): ?><li><?= $e($c) ?></li><?php endforeach ?></ul><?php endif ?>
  </div></section>
  <?php endif ?>

  <?php if ($errors): ?>
  <section class="sec sec--t0" id="errors">
    <h2 class="display h2"><?= bt_title($errH['title'] ?? '') ?></h2>
    <?= $lead($errH) ?>
    <div class="r2err"><?php foreach ($errors as $er): ?>
      <div class="card"><b><?= $e($er['name']) ?></b><?php if ($er['html'] !== ''): ?><p><span class="mono">Сами</span><?= $er['html'] ?></p><?php endif ?><?php if ($er['engineer'] !== ''): ?><p><span class="mono">Инженер</span><?= $e($er['engineer']) ?></p><?php endif ?></div>
    <?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($stepItems): ?>
  <section class="sec sec--t0">
    <h2 class="display h2"><?= bt_title($steps['title'] ?? '') ?></h2>
    <div class="r2steps"><?php foreach ($stepItems as $i => [$t, $d]): ?><div><b>Шаг <?= sprintf('%02d', $i + 1) ?></b><h3><?= $e($t) ?></h3><p><?= $e($d) ?></p></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($why): ?>
  <section class="sec sec--t0">
    <h2 class="display h2"><?= bt_title($whyH['title'] ?? '') ?></h2>
    <div class="r2why"><?php foreach ($why as $w): ?><div class="card"><?php if ($w['icon'] !== ''): ?><span class="ic"><?= $w['icon'] ?></span><?php endif ?><b><?= $e($w['name']) ?></b><?php if ($w['text'] !== ''): ?><p><?= $e($w['text']) ?></p><?php endif ?></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($revs): ?>
  <section class="sec sec--t0">
    <div class="r2head"><h2 class="display h2"><?= bt_title($revH['title'] ?? '') ?></h2><?= bt_btn($revH['btn_text'] ?? '', $revH['btn_link'] ?? '', 'link') ?></div>
    <div class="grid g3 revs" data-revs><?php foreach ($revs as $r) echo bt_rev_card($r) ?></div>
  </section>
  <?php endif ?>

  <?php if ($geo): ?>
  <section class="sec sec--t0">
    <h2 class="display h2"><?= bt_title($geo['title'] ?? '') ?></h2>
    <?= $lead($geo) ?>
    <?php if ($cities): ?><div class="r2geo"><?php foreach ($cities as $g): ?><span><?= $e($g) ?></span><?php endforeach ?></div><?php endif ?>
    <?php if ($districts): ?>
      <?php if (!empty($geo['caption'])): ?><p class="muted r2lead" style="margin:18px 0 10px"><?= $e($geo['caption']) ?></p><?php endif ?>
      <div class="r2geo r2dist"><?php foreach ($districts as $d): ?><span><?= $e($d) ?></span><?php endforeach ?></div>
    <?php endif ?>
  </section>
  <?php endif ?>

  <section class="sec sec--t0" id="form"><form class="card form" data-form="lead" novalidate>
    <div><div class="mono muted"><?= $e($form['caption'] ?? 'Заявка') ?></div><h2 class="display h2" style="margin:14px 0 10px"><?= bt_title($form['title'] ?? 'Вызвать инженера') ?></h2>
      <?php if (!empty($form['subtitle'])): ?><p class="muted" style="margin:0;max-width:40ch"><?= $e($form['subtitle']) ?></p><?php endif ?>
      <?php if (!empty($co['phone1'])): ?><p style="margin:18px 0 0"><a class="link" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a></p><?php endif ?></div>
    <div>
      <input type="hidden" name="topic" value="Вызов инженера: ремонт кофемашины">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="field"><label>Модель кофемашины</label><input name="model" maxlength="150" placeholder="Например, De’Longhi ECAM 22 или Jetinno JL15"></div>
      <div class="field"><label>Что случилось</label><textarea name="message" rows="3" maxlength="2000" data-symp-to placeholder="Опишите проблему или отметьте симптомы выше"></textarea></div>
      <?= bt_form_tail(($form['btn_text'] ?? '') ?: 'Вызвать инженера') ?>
    </div>
  </form></section>

  <?php if ($faq): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:20px"><?= bt_title($faqH['title'] ?? '') ?></h2>
    <div class="faq"><?php foreach ($faq as $i => $q): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q['name']) ?></summary><div class="faq__a"><?= str_starts_with(ltrim($q['html']), '<') ? $q['html'] : '<p>' . $q['html'] . '</p>' ?></div></details><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if (!empty($seoB['text'])): ?>
  <section class="sec sec--t0">
    <h2 class="display h2"><?= bt_title($seoB['title'] ?? '') ?></h2>
    <div class="r2seo"><?= $seoB['text'] ?></div>
  </section>
  <?php endif ?>

  <?php if ($links): ?>
  <section class="sec sec--t0">
    <div class="r2more"><?php foreach ($links as $l): ?><a class="card" href="<?= $e($l['link'] ?: '#') ?>"><b><?= $e($l['name']) ?></b><?php if ($l['html'] !== ''): ?><span><?= $e($plain($l['html'])) ?></span><?php endif ?></a><?php endforeach ?></div>
  </section>
  <?php endif ?>
</div>
