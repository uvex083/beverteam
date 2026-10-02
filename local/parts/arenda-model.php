<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
/** @var array $rentModel */
// Страница модели в аренду: цены — ИБ rent, фото и характеристики — карточка товара каталога, общие блоки — ИБ типа «Аренда кофемашин»

use Bitrix\Main\Loader;

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$vals = fn($items) => array_values(array_filter(array_column((array)$items, 0), fn($v) => $v !== ''));
$m = $rentModel;
$co = bt_contacts();
$top = bt_block('rent_top');
$included = bt_list('rent_terms');
$terms = bt_block('rent_contract');
$form = bt_block('rent_form');
$others = array_values(array_filter(bt_rent_models(), fn($x) => $x['id'] !== $m['id']));
$data = bt_catalog_data();

// карточка товара: фото, описание, характеристики
$photos = $specs = $tech = [];
$about = '';
$specCodes = ['SCREEN' => 'Экран', 'WATER_SUPPLY' => 'Подключение к водопроводу', 'WATER_TANK' => 'Бак для воды', 'BEAN_HOPPER' => 'Бункер зерна', 'TELEMETRY' => 'Онлайн-телеметрия', 'MDB' => 'Платёжный терминал', 'DIMENSIONS' => 'Габариты, Ш×Г×В'];
$code = basename(rtrim($m['url'], '/'));
if ($code !== '' && Loader::includeModule('iblock')) {
    $ob = \CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock('catalog'), '=CODE' => $code], false, ['nTopCount' => 1], ['ID', 'IBLOCK_ID', 'DETAIL_PICTURE', 'DETAIL_TEXT', 'PREVIEW_TEXT'])->GetNextElement();
    if ($ob) {
        $f = $ob->GetFields();
        $p = $ob->GetProperties();
        $files = array_values(array_filter(array_merge([(int)$f['DETAIL_PICTURE']], (array)($p['MORE_PHOTO']['VALUE'] ?? []))));
        $photos = array_map(fn($id) => ['big' => bt_img($id, 900, 900), 'full' => bt_img($id, 1800, 1800), 'th' => bt_img($id, 116, 116, BX_RESIZE_IMAGE_EXACT)], $files);
        foreach ($specCodes as $c => $t) {
            $v = $p[$c]['VALUE'] ?? '';
            $specs[$t] = !is_array($v) && $v !== '' && $v !== false ? html_entity_decode((string)$v) : (in_array($c, ['SCREEN', 'WATER_SUPPLY', 'TELEMETRY', 'MDB'], true) ? 'Нет' : '');
        }
        $specs = array_filter($specs, fn($v) => $v !== '');
        $techHtml = (string)($p['TECH_SPECS']['~VALUE']['TEXT'] ?? '');
        foreach (preg_split('/<br\s*\/?>|<\/p>|\n/u', html_entity_decode($techHtml)) as $line) {
            $line = trim(strip_tags($line));
            if ($line !== '' && !preg_match('/^(Габариты|Экран|Подача воды|Онлайн телеметрия)/u', $line)) {
                $tech[] = preg_replace('/\s{2,}|\s+-\s+/u', ' — ', $line);
            }
        }
        $about = trim((string)($f['~DETAIL_TEXT'] ?: $f['~PREVIEW_TEXT']));
    }
}
if (!$photos && $m['img']) {
    $photos = [['big' => $m['img'], 'full' => $m['img'], 'th' => $m['img']]];
}

// калькулятор этой модели: чашек в день × 8 г × дней в месяц, цена зерна из каталога
$beans = array_values(array_filter($data['coffee'], fn($c) => str_contains($c['n'], 'BOTANICA')));
$bean0 = 'botanica-espresso-smes';
$next = null;
foreach (bt_rent_models() as $x) {
    if ($x['cups'] > $m['cups'] && (!$next || $x['cups'] < $next['cups'])) {
        $next = $x;
    }
}
$cups0 = max(5, (int)round($m['cups'] * 0.6 / 5) * 5);
$calc = [
    'm' => ['m' => $m['model'], 'cups' => $m['cups'], 'price' => $m['price'], 'kg' => $m['kg']],
    'next' => $next ? ['m' => $next['model'], 'url' => bt_rent_url($next)] : null,
    'beans' => array_map(fn($c) => ['code' => $c['code'], 'n' => preg_replace('/,\s*1\s*кг$/u', '', $c['n']), 'p' => $c['p'], 'bulk' => $c['bulk'] ?? null], $beans),
    'g' => 8,
];

$page = 'https://beverteam.ru' . bt_rent_url($m);
$ld = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $APPLICATION->GetTitle(false), 'url' => $page,
    'brand' => ['@type' => 'Brand', 'name' => 'Jetinno'], 'image' => array_map(fn($ph) => 'https://beverteam.ru' . $ph['full'], array_slice($photos, 0, 4)),
    'description' => $m['audience'] . ($m['cups'] ? ', до ' . $m['cups'] . ' чашек в день' : '') . '. Аренда с доставкой, установкой и сервисом.',
    'offers' => ['@type' => 'Offer', 'url' => $page, 'priceCurrency' => 'RUB', 'price' => $m['price'], 'availability' => 'https://schema.org/InStock',
        'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => $m['price'], 'priceCurrency' => 'RUB', 'unitCode' => 'MON', 'unitText' => 'месяц'],
        'seller' => ['@id' => 'https://beverteam.ru/#org']]];
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap rentp repp rep2 ar2 rmp">
  <?php bt_crumbs() ?>

  <div class="rmhero">
    <div class="rmgal" data-rmgal='<?= htmlspecialcharsbx(json_encode(array_column($photos, 'full'), JSON_UNESCAPED_SLASHES)) ?>'>
      <button type="button" class="rmgal__big" data-rmgal-open aria-label="Открыть фото"><img src="<?= $e($photos[0]['big'] ?? '') ?>" alt="<?= $e($APPLICATION->GetTitle(false)) ?>" width="900" height="900" fetchpriority="high" data-rmgal-img></button>
      <?php if (count($photos) > 1): ?><div class="rmgal__th"><?php foreach ($photos as $i => $ph): ?><button type="button" aria-label="Фото <?= $i + 1 ?>" aria-pressed="<?= $i ? 'false' : 'true' ?>" data-rmgal-i="<?= $i ?>" data-big="<?= $e($ph['big']) ?>"><img src="<?= $e($ph['th']) ?>" alt="" width="58" height="58" loading="lazy"></button><?php endforeach ?></div><?php endif ?>
    </div>
    <div class="rminfo">
      <span class="mono muted"><?= $e($m['audience']) ?><?= $m['cups'] ? ' · до ' . $m['cups'] . ' чашек в день' : '' ?></span>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <?php if ($m['feature'] !== ''): ?><span class="tag rmtag"><?= $e($m['feature']) ?></span><?php endif ?>
      <div class="ar2m__pr rmpr">
        <?php if ($m['kg']): ?><div><b>0 ₽</b><span>в месяц при заказе кофе от <?= $m['kg'] ?>&nbsp;кг</span></div><?php endif ?>
        <div><b><?= $e(bt_fmt($m['price'])) ?></b><span>в месяц, кофе любой</span></div>
      </div>
      <?php if ($chk = $vals($top['items'] ?? [])): ?><ul class="r2chk"><?php foreach ($chk as $h): ?><li><?= $e($h) ?></li><?php endforeach ?></ul><?php endif ?>
      <div class="row" style="gap:12px">
        <a class="btn" href="#form" data-rm-set>Арендовать</a>
        <?php if (!empty($co['phone1'])): ?><a class="btn btn--line" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a><?php endif ?>
      </div>
      <p class="muted rmbuy"><a class="link" href="#calc">Рассчитать расход кофе</a><?php if ($m['buy']): ?> · <a class="link" href="<?= $e($m['url']) ?>">Купить за <?= $e(bt_fmt($m['buy'])) ?></a><?php endif ?></p>
    </div>
  </div>

  <?php if ($beans): ?>
  <section class="sec sec--t0" id="calc">
    <h2 class="display h2">Сколько это будет стоить вам</h2>
    <p class="muted r2lead">Укажите, сколько чашек в день пьют у вас, — посчитаем расход кофе и оба варианта оплаты для <?= $e($m['model']) ?>.</p>
    <div class="ar2calc" data-rmcalc='<?= htmlspecialcharsbx(json_encode($calc, JSON_UNESCAPED_UNICODE)) ?>'>
      <div class="ar2calc__in">
        <label class="ar2q" for="rmCups">Чашек в день <b data-rm-nv><?= $cups0 ?></b></label>
        <input class="rng" id="rmCups" type="range" min="5" max="<?= max(30, (int)ceil($m['cups'] * 1.5 / 5) * 5) ?>" step="5" value="<?= $cups0 ?>" data-rm-n>
        <span class="ar2q">График работы</span>
        <div class="opts" role="group" aria-label="График работы" data-rm-days><?php foreach ([22 => 'Пятидневка', 26 => 'Шестидневка', 30 => 'Без выходных'] as $v => $t): ?><button type="button" class="chipx" data-v="<?= $v ?>" aria-pressed="<?= $v === 22 ? 'true' : 'false' ?>"><?= $e($t) ?></button><?php endforeach ?></div>
        <label class="ar2q" for="rmBean">Кофе</label>
        <select id="rmBean" class="ar2sel" data-rm-bean><?php foreach ($calc['beans'] as $b): ?><option value="<?= $e($b['code']) ?>"<?= $b['code'] === $bean0 ? ' selected' : '' ?>><?= $e($b['n']) ?> — <?= $e(bt_fmt($b['p'])) ?>/кг</option><?php endforeach ?></select>
      </div>
      <div class="ar2calc__out" aria-live="polite">
        <span class="mono" style="color:var(--lime)">Расчёт для <?= $e($m['model']) ?></span>
        <div class="ar2kpi">
          <div><b data-rm-cups></b><span>чашек в день</span></div>
          <div><b data-rm-kg></b><span>кофе в месяц</span></div>
          <div><b data-rm-cup></b><span>себестоимость чашки</span></div>
        </div>
        <div class="ar2tar">
          <div data-rm-a><span class="mono">Машина 0 ₽ + наш кофе</span><b data-rm-at></b><small data-rm-ad></small></div>
          <div data-rm-b><span class="mono">Фиксированная аренда</span><b data-rm-bt></b><small data-rm-bd></small></div>
        </div>
        <p class="ar2note" data-rm-note></p>
        <a class="btn" href="#form" data-rm-go>Отправить расчёт менеджеру</a>
      </div>
    </div>
  </section>
  <?php endif ?>

  <?php if ($specs || $tech): ?>
  <section class="sec sec--t0" id="specs">
    <h2 class="display h2" style="margin-bottom:22px">Характеристики</h2>
    <div class="rmspecs">
      <?php if ($specs): ?><dl class="rmdl"><?php foreach ($specs as $t => $v): ?><div><dt><?= $e($t) ?></dt><dd><?= $e($v) ?></dd></div><?php endforeach ?></dl><?php endif ?>
      <?php if ($tech): ?><ul class="rmtech"><?php foreach ($tech as $t): ?><li><?= $e($t) ?></li><?php endforeach ?></ul><?php endif ?>
    </div>
  </section>
  <?php endif ?>

  <?php if ($about !== ''): ?>
  <section class="sec sec--t0">
    <h2 class="display h2">О модели</h2>
    <div class="post__body rmabout"><?= $about ?></div>
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

  <section class="sec sec--t0" id="form"><form class="card form" data-form="lead" novalidate>
    <div><div class="mono muted">Заявка</div><h2 class="display h2" style="margin:14px 0 10px">Арендовать <?= $e($m['model']) ?></h2>
      <?php if (!empty($form['subtitle'])): ?><p class="muted" style="margin:0;max-width:40ch"><?= $e($form['subtitle']) ?></p><?php endif ?>
      <?php if (!empty($co['phone1'])): ?><p style="margin:18px 0 0"><a class="link" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a></p><?php endif ?></div>
    <div>
      <input type="hidden" name="topic" value="Аренда кофемашины">
      <input type="hidden" name="model" value="<?= $e($m['model']) ?>">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="field"><label>Компания</label><input name="company" maxlength="150" placeholder="Если оформляем на организацию"></div>
      <div class="field"><label>Комментарий</label><textarea name="message" rows="3" maxlength="2000" placeholder="Место установки, число чашек в день, нужные напитки"></textarea></div>
      <?= bt_form_tail(($form['btn_text'] ?? '') ?: 'Отправить заявку') ?>
    </div>
  </form></section>

  <?php if ($others): ?>
  <section class="sec sec--t0">
    <div class="r2head"><h2 class="display h2">Другие модели в аренду</h2><a class="link" href="/arenda-kofemashin/#compare">Сравнить все модели →</a></div>
    <div class="rmothers"><?php foreach ($others as $x): ?>
      <a class="card" href="<?= $e(bt_rent_url($x)) ?>"><img src="<?= $e($x['img']) ?>" alt="" loading="lazy" width="240" height="170">
        <span><span class="mono muted"><?= $e($x['audience']) ?><?= $x['cups'] ? ' · до ' . $x['cups'] . ' чашек' : '' ?></span><b><?= $e($x['model']) ?></b>
          <em><?= $x['kg'] ? '0 ₽ при кофе от ' . $x['kg'] . ' кг · ' : '' ?><?= $e(bt_fmt($x['price'])) ?>/мес</em></span></a>
    <?php endforeach ?></div>
  </section>
  <?php endif ?>
</div>
