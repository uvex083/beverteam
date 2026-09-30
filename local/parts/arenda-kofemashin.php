<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Аренда кофемашин»: тексты — ИБ типа «Аренда кофемашин» (bt_rent_page_setup.php), модели и цены — ИБ rent и каталог, города — блок «Где работаем» ремонта

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$plain = fn($h) => trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], ' ', (string)$h)), ENT_QUOTES));
$co = bt_contacts();
$models = bt_rent_models();
$data = bt_catalog_data();
usort($models, fn($a, $b) => $a['cups'] <=> $b['cups']);

// характеристики машин — из карточек каталога
$specCodes = ['SCREEN' => 'Экран', 'WATER_SUPPLY' => 'Водопровод', 'WATER_TANK' => 'Бак для воды', 'BEAN_HOPPER' => 'Бункер зерна', 'TELEMETRY' => 'Онлайн-телеметрия', 'MDB' => 'Платёжный терминал', 'DIMENSIONS' => 'Габариты, Ш×Г×В'];
$specs = [];
if ($models && \Bitrix\Main\Loader::includeModule('iblock')) {
    $codes = array_filter(array_map(fn($m) => basename(rtrim($m['url'], '/')), $models));
    $r = \CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock('catalog'), 'CODE' => array_values($codes)], false, false, ['ID', 'IBLOCK_ID', 'CODE']);
    while ($ob = $r->GetNextElement()) {
        $f = $ob->GetFields();
        foreach ($ob->GetProperties(false, ['ACTIVE' => 'Y']) as $p) {
            if (isset($specCodes[$p['CODE']]) && !is_array($p['VALUE']) && $p['VALUE'] !== '' && $p['VALUE'] !== false) {
                $specs[$f['CODE']][$p['CODE']] = html_entity_decode((string)$p['VALUE']);
            }
        }
    }
}
$spec = fn($m, $c) => $specs[basename(rtrim($m['url'], '/'))][$c] ?? '';

// кофе BOTANICA: цена за кг (оптовая ступень от 5 кг, если есть) и себестоимость чашки 8 г
$beans = array_values(array_filter($data['coffee'], fn($c) => str_contains($c['n'], 'BOTANICA')));
$tier = function (array $c, float $kg): float {
    $p = $c['p'];
    foreach ((array)($c['bulk'] ?? []) as $t) {
        if ($kg >= $t['kg']) {
            $p = $t['p'];
        }
    }
    return (float)$p;
};
$bean0 = 'botanica-espresso-smes';

$calc = [
    'models' => array_map(fn($m) => ['id' => $m['id'], 'm' => $m['model'], 'aud' => $m['audience'], 'f' => $m['feature'], 'cups' => $m['cups'], 'price' => $m['price'], 'kg' => $m['kg'], 'img' => $m['img'], 'url' => $m['url']], $models),
    'beans' => array_map(fn($c) => ['code' => $c['code'], 'n' => preg_replace('/,\s*1\s*кг$/u', '', $c['n']), 'p' => $c['p'], 'bulk' => $c['bulk'] ?? null, 'url' => $c['url']], $beans),
    'g' => 8,
];
$places = [
    ['Офис', 'Сколько сотрудников пьёт кофе', 20, 2, 22],
    ['Кафе, HoReCa', 'Гостей с кофе в день', 60, 1, 30],
    ['Салон, клиника, шоурум', 'Клиентов и сотрудников в день', 25, 1, 26],
    ['Дом', 'Сколько человек пьёт кофе', 3, 2, 30],
];

$vals = fn($items) => array_values(array_filter(array_column((array)$items, 0), fn($v) => $v !== ''));
$pairs = fn($items) => array_values(array_filter((array)$items, fn($t) => ($t[0] ?? '') !== ''));
$lead = fn(array $b) => !empty($b['subtitle']) ? '<p class="muted r2lead">' . $e($b['subtitle']) . '</p>' : '';
$top = bt_block('rent_top');
$facts = bt_blocks('rent_facts');
$calcH = bt_block('rent_calc');
$modelsH = bt_block('rent_models_head');
$included = bt_list('rent_terms');
$terms = bt_block('rent_contract');
$coffeeH = bt_block('rent_coffee');
$vs = bt_block('rent_vs');
$segments = bt_blocks('rent_segments');
$stepsB = bt_block('rent_steps');
$steps = $pairs($stepsB['items'] ?? []);
$event = bt_block('rent_event');
$form = bt_block('rent_form');
$faq = bt_blocks('rent_faq');
$seoB = bt_block('rent_seo');
$links = bt_blocks('rent_links');

$geo = bt_block('repair_geo');
$cities = array_values(array_filter(array_column((array)($geo['items'] ?? []), 0), fn($v) => $v !== ''));
$revs = bt_reviews();

// разметка для поисковиков — из тех же данных
$page = 'https://beverteam.ru/arenda-kofemashin/';
$org = ['@id' => 'https://beverteam.ru/#org'];
$h1 = $APPLICATION->GetTitle(false);
$graph = [
    ['@type' => 'WebPage', '@id' => $page . '#page', 'url' => $page, 'name' => $h1, 'inLanguage' => 'ru', 'about' => ['@id' => $page . '#service'], 'publisher' => $org],
    ['@type' => 'Service', '@id' => $page . '#service', 'name' => $h1, 'serviceType' => 'Аренда кофемашин', 'description' => $top['subtitle'] ?? '', 'provider' => $org,
        'areaServed' => array_merge(array_map(fn($g) => ['@type' => 'City', 'name' => $g], $cities), [['@type' => 'AdministrativeArea', 'name' => 'Свердловская область']]),
        'brand' => ['@type' => 'Brand', 'name' => 'Jetinno'],
        'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => 'Кофемашины в аренду', 'itemListElement' => array_map(fn($m) => [
            '@type' => 'Offer', 'name' => 'Аренда ' . $m['model'], 'priceCurrency' => 'RUB', 'price' => $m['price'],
            'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => $m['price'], 'priceCurrency' => 'RUB', 'unitCode' => 'MON', 'unitText' => 'месяц'],
            'description' => ($m['cups'] ? 'До ' . $m['cups'] . ' чашек в день. ' : '') . ($m['kg'] ? '0 ₽ при заказе кофе от ' . $m['kg'] . ' кг в месяц.' : ''),
            'itemOffered' => ['@type' => 'Product', 'name' => $m['model'], 'brand' => ['@type' => 'Brand', 'name' => 'Jetinno'], 'image' => 'https://beverteam.ru' . $m['img'], 'url' => 'https://beverteam.ru' . $m['url']],
        ], $models)]],
];
$steps and $graph[] = ['@type' => 'HowTo', '@id' => $page . '#steps', 'name' => $plain($stepsB['title'] ?? ''),
    'step' => array_map(fn($i, $s) => ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => $s[0], 'text' => $s[1]], array_keys($steps), $steps)];
$faq and $graph[] = ['@type' => 'FAQPage', '@id' => $page . '#faq', 'mainEntity' => array_map(fn($q) => ['@type' => 'Question', 'name' => $q['name'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $plain($q['html'])]], $faq)];
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap rentp repp rep2 ar2">
  <?php bt_crumbs() ?>

  <div class="r2hero">
    <div>
      <?php if (!empty($top['caption'])): ?><div class="mono muted"><?= $e($top['caption']) ?></div><?php endif ?>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <?php if (!empty($top['subtitle'])): ?><p class="sub"><?= $e($top['subtitle']) ?></p><?php endif ?>
      <?php if ($chk = $vals($top['items'] ?? [])): ?><ul class="r2chk"><?php foreach ($chk as $h): ?><li><?= $e($h) ?></li><?php endforeach ?></ul><?php endif ?>
      <div class="row" style="gap:12px">
        <?= bt_btn($top['btn_text'] ?? '', $top['btn_link'] ?? '') ?>
        <?php if (!empty($co['phone1'])): ?><a class="btn btn--line" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a><?php endif ?>
      </div>
    </div>
    <?php if ($facts): ?><div class="r2facts"><?php foreach ($facts as $f): ?><div><b><?= $e($f['name']) ?></b><span><?= $e($plain($f['html'])) ?></span></div><?php endforeach ?></div><?php endif ?>
  </div>

  <?php if ($models && $beans): ?>
  <section class="sec sec--t0" id="calc">
    <h2 class="display h2"><?= bt_title(($calcH['title'] ?? '') ?: 'Калькулятор аренды') ?></h2>
    <?= $lead($calcH ?? []) ?>
    <div class="ar2calc" data-rcalc2='<?= htmlspecialcharsbx(json_encode($calc, JSON_UNESCAPED_UNICODE)) ?>'>
      <div class="ar2calc__in">
        <span class="ar2q">Куда поставим машину?</span>
        <div class="opts" role="group" aria-label="Куда поставим машину?"><?php foreach ($places as $i => [$pl, $who, $n, $per, $days]): ?>
          <button type="button" class="chipx" aria-pressed="<?= $i ? 'false' : 'true' ?>" data-rc2-place='<?= htmlspecialcharsbx(json_encode(['who' => $who, 'n' => $n, 'per' => $per, 'days' => $days], JSON_UNESCAPED_UNICODE)) ?>'><?= $e($pl) ?></button>
        <?php endforeach ?></div>
        <label class="ar2q" for="rc2n"><span data-rc2-who><?= $e($places[0][1]) ?></span> <b data-rc2-nv><?= $places[0][2] ?></b></label>
        <input class="rng" id="rc2n" type="range" min="1" max="150" step="1" value="<?= $places[0][2] ?>" data-rc2-n>
        <span class="ar2q">Чашек на человека в день</span>
        <div class="opts" role="group" aria-label="Чашек на человека в день" data-rc2-per><?php foreach ([1, 2, 3] as $v): ?><button type="button" class="chipx" data-v="<?= $v ?>" aria-pressed="<?= $v === $places[0][3] ? 'true' : 'false' ?>"><?= $v ?></button><?php endforeach ?></div>
        <span class="ar2q">График работы</span>
        <div class="opts" role="group" aria-label="График работы" data-rc2-days><?php foreach ([22 => 'Пятидневка', 26 => 'Шестидневка', 30 => 'Без выходных'] as $v => $t): ?><button type="button" class="chipx" data-v="<?= $v ?>" aria-pressed="<?= $v === $places[0][4] ? 'true' : 'false' ?>"><?= $e($t) ?></button><?php endforeach ?></div>
        <label class="ar2q" for="rc2b">Кофе</label>
        <select id="rc2b" class="ar2sel" data-rc2-bean><?php foreach ($calc['beans'] as $b): ?><option value="<?= $e($b['code']) ?>"<?= $b['code'] === $bean0 ? ' selected' : '' ?>><?= $e($b['n']) ?> — <?= $e(bt_fmt($b['p'])) ?>/кг</option><?php endforeach ?></select>
      </div>
      <div class="ar2calc__out" aria-live="polite">
        <div class="ar2pick">
          <img src="<?= $e($models[0]['img']) ?>" alt="" width="120" height="96" data-rc2-img>
          <div><span class="mono muted">Подходит</span><h3 data-rc2-name></h3><p data-rc2-s></p></div>
        </div>
        <div class="ar2kpi">
          <div><b data-rc2-cups></b><span>чашек в день</span></div>
          <div><b data-rc2-kg></b><span>кофе в месяц</span></div>
          <div><b data-rc2-cup></b><span>себестоимость чашки</span></div>
        </div>
        <div class="ar2tar">
          <div data-rc2-a><span class="mono">Машина 0 ₽ + наш кофе</span><b data-rc2-at></b><small data-rc2-ad></small></div>
          <div data-rc2-b><span class="mono">Фиксированная аренда</span><b data-rc2-bt></b><small data-rc2-bd></small></div>
        </div>
        <p class="ar2note" data-rc2-note></p>
        <a class="btn" href="#form" data-rc2-go><?= $e(($calcH['btn_text'] ?? '') ?: 'Отправить расчёт менеджеру') ?></a>
      </div>
    </div>
  </section>
  <?php endif ?>

  <?php if ($models): ?>
  <section class="sec sec--t0" id="models">
    <div class="r2head"><h2 class="display h2"><?= bt_title(($modelsH['title'] ?? '') ?: 'Кофемашины в аренду') ?></h2><?= bt_btn($modelsH['btn_text'] ?? '', $modelsH['btn_link'] ?? '', 'link') ?></div>
    <div class="ar2models"><?php foreach ($models as $m): ?>
      <article class="card ar2m">
        <a class="ar2m__ph" href="<?= $e($m['url']) ?>"><img src="<?= $e($m['img']) ?>" alt="<?= $e($m['model']) ?>" loading="lazy" width="480" height="340"></a>
        <div class="ar2m__b">
          <span class="mono muted"><?= $e($m['audience']) ?><?= $m['cups'] ? ' · до ' . $m['cups'] . ' чашек в день' : '' ?></span>
          <h3><a href="<?= $e($m['url']) ?>"><?= $e($m['model']) ?></a></h3>
          <?php if ($m['feature'] !== ''): ?><span class="tag"><?= $e($m['feature']) ?></span><?php endif ?>
          <div class="ar2m__pr">
            <?php if ($m['kg']): ?><div><b>0 ₽</b><span>при заказе кофе от <?= $m['kg'] ?>&nbsp;кг в месяц</span></div><?php endif ?>
            <div><b><?= $e(bt_fmt($m['price'])) ?></b><span>в месяц, кофе любой</span></div>
          </div>
          <ul class="ar2m__sp"><?php foreach (['SCREEN', 'BEAN_HOPPER', 'WATER_SUPPLY', 'TELEMETRY'] as $c): ?><li><span><?= $e($specCodes[$c]) ?></span><?= $e($spec($m, $c) ?: 'Нет') ?></li><?php endforeach ?></ul>
          <button type="button" class="btn btn--line btn--sm" data-rc2-model="<?= $e($m['id']) ?>">Рассчитать для этой модели</button>
        </div>
      </article>
    <?php endforeach ?></div>
  </section>

  <section class="sec sec--t0" id="compare">
    <h2 class="display h2" style="margin-bottom:22px">Сравнение моделей</h2>
    <div class="tblw"><table class="tbl">
      <thead><tr><th>Параметр</th><?php foreach ($models as $m): ?><th><?= $e($m['model']) ?></th><?php endforeach ?></tr></thead>
      <tbody>
        <tr><td>Для кого</td><?php foreach ($models as $m): ?><td><?= $e($m['audience']) ?></td><?php endforeach ?></tr>
        <tr><td>Нагрузка</td><?php foreach ($models as $m): ?><td><?= $m['cups'] ? 'до ' . $m['cups'] . ' чашек в день' : '—' ?></td><?php endforeach ?></tr>
        <tr><td>Аренда 0 ₽</td><?php foreach ($models as $m): ?><td><?= $m['kg'] ? 'при кофе от ' . $m['kg'] . '&nbsp;кг в месяц' : '—' ?></td><?php endforeach ?></tr>
        <tr><td>Фиксированная аренда</td><?php foreach ($models as $m): ?><td><b><?= $e(bt_fmt($m['price'])) ?>/мес</b></td><?php endforeach ?></tr>
        <?php foreach ($specCodes as $c => $t): if (!array_filter($models, fn($m) => $spec($m, $c) !== '')) continue; ?>
        <tr><td><?= $e($t) ?></td><?php foreach ($models as $m): ?><td><?= $e($spec($m, $c) ?: (in_array($c, ['SCREEN', 'WATER_SUPPLY', 'TELEMETRY', 'MDB'], true) ? 'Нет' : '—')) ?></td><?php endforeach ?></tr>
        <?php endforeach ?>
        <tr><td>Купить</td><?php foreach ($models as $m): ?><td><?= $m['buy'] ? '<a class="link" href="' . $e($m['url']) . '">' . $e(bt_fmt($m['buy'])) . '</a>' : 'по запросу' ?></td><?php endforeach ?></tr>
      </tbody>
    </table></div>
  </section>
  <?php endif ?>

  <?php if ($included): ?>
  <section class="sec sec--t0">
    <h2 class="display h2">Что входит в аренду</h2>
    <div class="r2why ar2inc"><?php foreach ($included as $w): ?><div class="card"><?php if ($w['icon'] !== ''): ?><span class="ic"><?= $w['icon'] ?></span><?php endif ?><b><?= $e($w['name']) ?></b><?php if ($w['text'] !== ''): ?><p><?= $e($w['text']) ?></p><?php endif ?></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($terms): ?>
  <section class="sec sec--t0"><div class="pl">
    <div><?php if (!empty($terms['caption'])): ?><div class="mono" style="color:var(--lime)"><?= $e($terms['caption']) ?></div><?php endif ?>
      <h2 class="display h2" style="margin:14px 0"><?= bt_title($terms['title'] ?? '') ?></h2>
      <?php if (!empty($terms['subtitle'])): ?><p><?= $e($terms['subtitle']) ?></p><?php endif ?>
      <?php if (!empty($terms['file'])): ?><div class="row" style="gap:12px"><a class="btn" href="<?= $e($terms['file']) ?>" target="_blank" rel="noopener"><?= $e(($terms['btn_text'] ?? '') ?: 'Образец договора, PDF') ?></a></div><?php endif ?></div>
    <?php if ($termItems = $vals($terms['items'] ?? [])): ?><ul class="r2list"><?php foreach ($termItems as $t): ?><li><?= $e($t) ?></li><?php endforeach ?></ul><?php endif ?>
  </div></section>
  <?php endif ?>

  <?php if ($beans): ?>
  <section class="sec sec--t0" id="coffee">
    <div class="r2head"><h2 class="display h2"><?= bt_title(($coffeeH['title'] ?? '') ?: 'Кофе для машины') ?></h2><?= bt_btn($coffeeH['btn_text'] ?? '', $coffeeH['btn_link'] ?? '', 'link') ?></div>
    <?= $lead($coffeeH ?? []) ?>
    <div class="ar2cf"><?php foreach ($beans as $c): $p5 = $tier($c, 5); ?>
      <a class="card" href="<?= $e($c['url']) ?>">
        <?php if (!empty($c['img'])): ?><img src="<?= $e($c['img']) ?>" alt="" loading="lazy" width="120" height="120"><?php endif ?>
        <b><?= $e(preg_replace('/,\s*1\s*кг$/u', '', $c['n'])) ?></b>
        <?php if (!empty($c['par'])): ?><small><?= $e($c['par']) ?></small><?php endif ?>
        <span class="ar2cf__p"><?= $e(bt_fmt($p5)) ?>/кг · <b><?= $e(bt_fmt(round($p5 * 8 / 1000))) ?></b> за чашку</span>
      </a>
    <?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($models): ?>
  <section class="sec sec--t0">
    <h2 class="display h2"><?= bt_title(($vs['title'] ?? '') ?: 'Аренда или покупка') ?></h2>
    <?php $plus = $vals($vs['items'] ?? []); $minus = $vals($vs['minus'] ?? []); $buy0 = min(array_filter(array_column($models, 'buy')) ?: [0]); ?>
    <?php if ($plus || $minus): ?><div class="ar2vs">
      <div class="card"><b>Аренда</b><ul class="ar2pm"><?php foreach ($plus as $t): ?><li><?= $e($t) ?></li><?php endforeach ?></ul></div>
      <div class="card"><b>Покупка</b><ul class="ar2pm ar2pm--no"><?php if ($buy0): ?><li>Сразу от <?= $e(bt_fmt($buy0)) ?></li><?php endif ?><?php foreach ($minus as $t): ?><li><?= $e($t) ?></li><?php endforeach ?></ul></div>
    </div><?php endif ?>
    <div class="tblw" style="margin-top:14px"><table class="tbl r2tbl">
      <thead><tr><th>Модель</th><th>Покупка</th><th>Аренда за 12 месяцев</th></tr></thead>
      <tbody><?php foreach ($models as $m): if (!$m['buy']) continue; ?><tr><td><?= $e($m['model']) ?></td><td><?= $e(bt_fmt($m['buy'])) ?></td><td><b><?= $m['kg'] ? '0 ₽ с нашим кофе' : '' ?></b><?= $m['kg'] ? ' или ' : '' ?><?= $e(bt_fmt($m['price'] * 12)) ?></td></tr><?php endforeach ?></tbody>
    </table></div>
  </section>
  <?php endif ?>

  <?php if ($segments): ?>
  <section class="sec sec--t0">
    <h2 class="display h2">Кому подходит аренда</h2>
    <div class="r2types ar2seg"><?php foreach ($segments as $sg): ?><div class="card"><b><?= $e($sg['name']) ?></b><p><?= $e($plain($sg['html'])) ?></p><?php if (($sg['place'] ?? '') !== ''): ?><button type="button" class="link" data-rc2-goplace="<?= $e($sg['place']) ?>">Рассчитать →</button><?php else: ?><span></span><?php endif ?></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($steps): ?>
  <section class="sec sec--t0">
    <h2 class="display h2"><?= bt_title($stepsB['title'] ?? '') ?></h2>
    <div class="r2steps"><?php foreach ($steps as $i => [$t, $d]): ?><div><b>Шаг <?= sprintf('%02d', $i + 1) ?></b><h3><?= $e($t) ?></h3><p><?= $e($d) ?></p></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($event): ?>
  <section class="sec sec--t0" id="event"><div class="cta">
    <div><h2 class="display h2"><?= bt_title($event['title'] ?? '') ?></h2><?php if (!empty($event['subtitle'])): ?><p><?= $e($event['subtitle']) ?></p><?php endif ?></div>
    <?php if (!empty($event['btn_text'])): ?><a class="btn btn--dark" href="<?= $e(($event['btn_link'] ?? '') ?: '#form') ?>" data-rc2-set="На мероприятие"><?= $e($event['btn_text']) ?></a><?php endif ?>
  </div></section>
  <?php endif ?>

  <?php if ($revs): ?>
  <section class="sec sec--t0">
    <div class="r2head"><h2 class="display h2">Отзывы клиентов</h2><a class="link" href="/otzyvy-o-nas/">Все отзывы →</a></div>
    <div class="grid g3 revs" data-revs><?php foreach ($revs as $r) echo bt_rev_card($r) ?></div>
  </section>
  <?php endif ?>

  <?php if ($cities): ?>
  <section class="sec sec--t0">
    <h2 class="display h2">Где работаем</h2>
    <p class="muted r2lead">Устанавливаем и обслуживаем кофемашины в Екатеринбурге и городах Свердловской области.</p>
    <div class="r2geo"><?php foreach ($cities as $g): ?><span><?= $e($g) ?></span><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0" id="form"><form class="card form" data-form="lead" novalidate>
    <div><div class="mono muted">Заявка</div><h2 class="display h2" style="margin:14px 0 10px"><?= bt_title(($form['title'] ?? '') ?: 'Заявка на аренду') ?></h2>
      <?php if (!empty($form['subtitle'])): ?><p class="muted" style="margin:0;max-width:40ch"><?= $e($form['subtitle']) ?></p><?php endif ?>
      <?php if (!empty($co['phone1'])): ?><p style="margin:18px 0 0"><a class="link" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a></p><?php endif ?></div>
    <div>
      <input type="hidden" name="topic" value="Аренда кофемашины">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="f2"><div class="field"><label>Модель</label><select name="model"><?php foreach ($models as $m): ?><option value="<?= $e($m['model']) ?>"><?= $e($m['model']) ?> — <?= $e(bt_fmt($m['price'])) ?>/мес</option><?php endforeach ?><option value="На мероприятие">На мероприятие</option><option value="Помогите подобрать" selected>Помогите подобрать</option></select></div>
        <div class="field"><label>Компания</label><input name="company" maxlength="150" placeholder="Если оформляем на организацию"></div></div>
      <div class="field"><label>Комментарий</label><textarea name="message" rows="3" maxlength="2000" placeholder="Место установки, число чашек в день, нужные напитки"></textarea></div>
      <?= bt_form_tail(($form['btn_text'] ?? '') ?: 'Отправить заявку') ?>
    </div>
  </form></section>

  <?php if ($faq): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:20px">Вопросы об аренде кофемашин</h2>
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
