<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Кофе в офис»: регулярные поставки зерна тем, у кого кофемашина уже есть; тексты — ИБ типа «Кофе в офис» (bt_office_setup.php), цены — каталог, порог бесплатной аренды — ИБ rent

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$vals = fn($items) => array_values(array_filter(array_column((array)$items, 0), fn($v) => $v !== ''));
$plain = fn($h) => trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], ' ', (string)$h)), ENT_QUOTES));
$co = bt_contacts();
$head = bt_block('sub_head');
$how = bt_block('sub_how');
$rentB = bt_block('sub_rent');
$utp = bt_block('main_utp');
$faq = bt_blocks('sub_faq');
$data = bt_catalog_data();
$coffee = array_values(array_filter($data['coffee'], fn($c) => $c['p'] > 0));
$rentKg = array_filter(array_column(bt_rent_models(), 'kg'));
$sort0 = 0;
foreach ($coffee as $i => $c) {
    $c['code'] === 'botanica-espresso-smes' and $sort0 = $i;
}
$kgs = [1, 2, 3, 5, 10, 20];
$kg0 = 3;
$periods = [[1, 'Раз в месяц'], [2, 'Раз в 2 недели'], [4, 'Раз в неделю']];
// доставка по Екатеринбургу: курьер 350 ₽, бесплатно от 3 000 ₽ за одну доставку (условия — «Оплата и доставка»)
$ship = 350;
$free = 3000;
$cfg = [
    'coffee' => array_map(fn($c) => ['id' => $c['id'], 'n' => preg_replace('/,\s*1\s*кг$/u', '', $c['n']), 'p' => $c['p'], 'bulk' => $c['bulk'] ?? null], $coffee),
    'ship' => $ship, 'free' => $free, 'g' => 8,
    'rent' => $rentKg ? ['kg' => min($rentKg), 'url' => '/arenda-kofemashin/#calc'] : null,
];
$short = fn(string $n) => trim(preg_replace(['/^BOTANICA\s+/u', '/,\s*1\s*кг$/u'], '', $n));

// цифры первого экрана — из каталога: самая большая оптовая скидка и минимальный объём
$maxPct = 0;
foreach ($coffee as $c) {
    foreach ((array)($c['bulk'] ?? []) as $t) {
        $c['bulk'][0]['p'] > 0 and $maxPct = max($maxPct, (int)round((1 - $t['p'] / $c['bulk'][0]['p']) * 100));
    }
}
$facts = array_values(array_filter([
    ['от 1 кг', 'в месяц — без минимальной партии'],
    ['0 ₽', 'доставка по Екатеринбургу от ' . bt_fmt($free)],
    $maxPct ? ['до −' . $maxPct . '%', 'оптовая цена от объёма'] : null,
    ['BOTANICA', 'свежая обжарка, ' . count($coffee) . ' ' . (count($coffee) % 10 === 1 && count($coffee) % 100 !== 11 ? 'сорт' : (in_array(count($coffee) % 10, [2, 3, 4]) && !in_array(count($coffee) % 100, [12, 13, 14]) ? 'сорта' : 'сортов'))],
]));
$steps = array_values(array_filter((array)($how['items'] ?? []), fn($t) => ($t[0] ?? '') !== ''));

$page = 'https://beverteam.ru/kofe-v-ofis/';
$h1 = $APPLICATION->GetTitle(false);
$graph = [['@type' => 'Service', '@id' => $page . '#service', 'name' => $h1, 'serviceType' => 'Поставки кофе в офис', 'url' => $page,
    'description' => $head['subtitle'] ?? '', 'provider' => ['@id' => 'https://beverteam.ru/#org'],
    'areaServed' => ['@type' => 'City', 'name' => 'Екатеринбург'],
    'offers' => array_map(fn($c) => ['@type' => 'Offer', 'name' => $c['n'], 'price' => $c['p'], 'priceCurrency' => 'RUB', 'url' => 'https://beverteam.ru' . $c['url']], $coffee)]];
$steps and $graph[] = ['@type' => 'HowTo', '@id' => $page . '#steps', 'name' => $how['title'] ?? '',
    'step' => array_map(fn($i, $s) => ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => $s[0], 'text' => $s[1]], array_keys($steps), $steps)];
$faq and $graph[] = ['@type' => 'FAQPage', '@id' => $page . '#faq', 'mainEntity' => array_map(fn($q) => ['@type' => 'Question', 'name' => $q['name'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $plain($q['html'])]], $faq)];
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap rentp repp rep2 ofp">
  <?php bt_crumbs() ?>

  <div class="r2hero">
    <div>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <?php if (!empty($head['subtitle'])): ?><p class="sub"><?= $e($head['subtitle']) ?></p><?php endif ?>
      <?php if ($chk = $vals($head['items'] ?? [])): ?><ul class="r2chk"><?php foreach ($chk as $h): ?><li><?= $e($h) ?></li><?php endforeach ?></ul><?php endif ?>
      <div class="row" style="gap:12px">
        <a class="btn" href="#calc">Рассчитать поставки</a>
        <?php if (!empty($co['phone1'])): ?><a class="btn btn--line" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a><?php endif ?>
      </div>
    </div>
    <div class="r2facts"><?php foreach ($facts as [$b, $s]): ?><div><b><?= $e($b) ?></b><span><?= $e($s) ?></span></div><?php endforeach ?></div>
  </div>

  <?php if ($steps): ?>
  <section class="sec sec--t0">
    <h2 class="display h2"><?= bt_title($how['title'] ?? '') ?></h2>
    <div class="r2steps ofsteps"><?php foreach ($steps as $i => [$t, $d]): ?><div><b>Шаг <?= sprintf('%02d', $i + 1) ?></b><h3><?= $e($t) ?></h3><p><?= $e($d) ?></p></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($coffee): ?>
  <section class="sec sec--t0" id="calc">
    <h2 class="display h2"><?= bt_title(($head['title'] ?? '') ?: 'Рассчитайте поставки') ?></h2>
    <p class="muted r2lead">Выберите объём, график и сорт — цена, доставка и стоимость чашки пересчитаются сразу.</p>
    <div class="ar2calc" data-ofcfg='<?= htmlspecialcharsbx(json_encode($cfg, JSON_UNESCAPED_UNICODE)) ?>'>
      <div class="ar2calc__in">
        <span class="ar2q">Кофе в месяц</span>
        <div class="opts" role="group" aria-label="Кофе в месяц" data-of-kg>
          <?php foreach ($kgs as $k): ?><button type="button" class="chipx" data-v="<?= $k ?>" aria-pressed="<?= $k === $kg0 ? 'true' : 'false' ?>"><?= $k ?> кг<s data-of-pct></s></button><?php endforeach ?>
        </div>
        <span class="ar2q">Как часто привозить</span>
        <div class="opts" role="group" aria-label="Как часто привозить" data-of-per>
          <?php foreach ($periods as $i => [$n, $t]): ?><button type="button" class="chipx" data-v="<?= $n ?>" aria-pressed="<?= $i ? 'false' : 'true' ?>"><?= $e($t) ?></button><?php endforeach ?>
        </div>
        <label class="ar2q" for="ofSort">Сорт кофе</label>
        <select id="ofSort" class="ar2sel" data-of-sort>
          <?php foreach ($coffee as $i => $c): ?><option value="<?= $i ?>"<?= $i === $sort0 ? ' selected' : '' ?>><?= $e($short($c['n'])) ?> — <?= $e(bt_fmt($c['p'])) ?>/кг</option><?php endforeach ?>
        </select>
      </div>
      <div class="ar2calc__out" aria-live="polite">
        <span class="mono" style="color:var(--lime)">Ваш расчёт</span>
        <div class="ar2kpi">
          <div><b data-of-kgv></b><span>кофе в месяц</span></div>
          <div><b data-of-cups></b><span>чашек в месяц</span></div>
          <div><b data-of-cup></b><span>за чашку</span></div>
        </div>
        <div class="oftot">
          <div><span data-of-kgl>Кофе</span><span data-of-sum></span></div>
          <div><span>Оптовая скидка</span><span data-of-disc></span></div>
          <div><span data-of-shipl>Доставка</span><span data-of-ship></span></div>
          <div class="t"><span>В месяц</span><b data-of-tot></b></div>
        </div>
        <p class="ar2note" data-of-note></p>
        <p class="ofbonus" data-of-rent hidden></p>
        <a class="btn" href="#form" data-of-go><?= $e(($head['btn_text'] ?? '') ?: 'Заказать поставки') ?></a>
      </div>
    </div>
  </section>

  <section class="sec sec--t0" id="form"><form class="card form" data-form="lead" novalidate>
    <div><div class="mono muted">Заявка</div><h2 class="display h2" style="margin:14px 0 10px">Заказать поставки кофе</h2>
      <?php if (!empty($head['caption'])): ?><p class="muted" style="margin:0;max-width:40ch"><?= $e($head['caption']) ?></p><?php endif ?>
      <?php if (!empty($co['phone1'])): ?><p style="margin:18px 0 0"><a class="link" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a></p><?php endif ?></div>
    <div>
      <input type="hidden" name="topic" value="Кофе в офис">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="field"><label>Компания</label><input name="company" maxlength="150" placeholder="Если оформляем на организацию"></div>
      <div class="field"><label>Комментарий</label><textarea name="message" rows="3" maxlength="2000" data-of-msg placeholder="Адрес, удобный день доставки, модель кофемашины"></textarea></div>
      <?= bt_form_tail(($head['btn_text'] ?? '') ?: 'Заказать поставки') ?>
    </div>
  </form></section>
  <?php endif ?>

  <?php if ($rentB && $cfg['rent']): ?>
  <section class="sec sec--t0"><div class="ofrent">
    <div><h2 class="display h2"><?= bt_title($rentB['title'] ?? '') ?></h2><?php if (!empty($rentB['subtitle'])): ?><p><?= $e($rentB['subtitle']) ?></p><?php endif ?></div>
    <?= bt_btn($rentB['btn_text'] ?? '', $rentB['btn_link'] ?? '') ?>
  </div></section>
  <?php endif ?>

  <?php if ($utpItems = bt_list('main_utp_items')): ?>
  <section class="sec sec--t0">
    <h2 class="display h2"><?= $e($utp['title'] ?? 'Почему BEVERTEAM') ?></h2>
    <div class="r2why ofwhy"><?php foreach ($utpItems as $u): ?><div class="card"><?php if ($u['icon'] !== ''): ?><span class="ic"><?= $u['icon'] ?></span><?php endif ?><b><?= $e($u['name']) ?></b><?php if ($u['text'] !== ''): ?><p><?= $e($u['text']) ?></p><?php endif ?></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($faq): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:20px">Вопросы о поставках кофе</h2>
    <div class="faq"><?php foreach ($faq as $i => $q): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q['name']) ?></summary><div class="faq__a"><?= str_starts_with(ltrim($q['html']), '<') ? $q['html'] : '<p>' . $q['html'] . '</p>' ?></div></details><?php endforeach ?></div>
  </section>
  <?php endif ?>
</div>
