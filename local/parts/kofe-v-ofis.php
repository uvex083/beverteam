<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Кофе в офис»: регулярные поставки зерна тем, у кого кофемашина уже есть; тексты — ИБ типа «Кофе в офис» (bt_office_setup.php), цены — каталог, порог бесплатной аренды — ИБ rent

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$vals = fn($items) => array_values(array_filter(array_column((array)$items, 0), fn($v) => $v !== ''));
$head = bt_block('sub_head');
$how = bt_block('sub_how');
$rentB = bt_block('sub_rent');
$utp = bt_block('main_utp');
$data = bt_catalog_data();
$coffee = array_values(array_filter($data['coffee'], fn($c) => $c['p'] > 0));
$models = bt_rent_models();
$rentKg = array_filter(array_column($models, 'kg'));
$sort0 = 0;
foreach ($coffee as $i => $c) {
    $c['code'] === 'botanica-espresso-smes' and $sort0 = $i;
}
$kgs = [1, 2, 3, 5, 10, 20];
$kg0 = 3;
$periods = [[1, 'Раз в месяц'], [2, 'Раз в 2 недели'], [4, 'Раз в неделю']];
// доставка по Екатеринбургу: курьер 350 ₽, бесплатно от 3 000 ₽ за одну доставку (условия — «Оплата и доставка»)
$cfg = [
    'coffee' => array_map(fn($c) => ['id' => $c['id'], 'n' => $c['n'], 'p' => $c['p'], 'bulk' => $c['bulk'] ?? null], $coffee),
    'ship' => 350, 'free' => 3000, 'g' => 8,
    'rent' => $rentKg ? ['kg' => min($rentKg), 'url' => '/arenda-kofemashin/#calc'] : null,
];
$short = fn(string $n) => trim(preg_replace(['/^BOTANICA\s+/u', '/,\s*1\s*кг$/u'], '', $n));

$faq = bt_blocks('sub_faq');
$page = 'https://beverteam.ru/kofe-v-ofis/';
$h1 = $APPLICATION->GetTitle(false);
$plain = fn($h) => trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], ' ', (string)$h)), ENT_QUOTES));
$graph = [['@type' => 'Service', '@id' => $page . '#service', 'name' => $h1, 'serviceType' => 'Поставки кофе в офис', 'url' => $page,
    'description' => $head['subtitle'] ?? '', 'provider' => ['@id' => 'https://beverteam.ru/#org'],
    'areaServed' => ['@type' => 'City', 'name' => 'Екатеринбург'],
    'offers' => array_map(fn($c) => ['@type' => 'Offer', 'name' => $c['n'], 'price' => $c['p'], 'priceCurrency' => 'RUB', 'url' => 'https://beverteam.ru' . $c['url']], $coffee)]];
$faq and $graph[] = ['@type' => 'FAQPage', '@id' => $page . '#faq', 'mainEntity' => array_map(fn($q) => ['@type' => 'Question', 'name' => $q['name'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $plain($q['html'])]], $faq)];
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap subp">
  <?php bt_crumbs() ?>
  <div class="pagehead" style="padding-bottom:10px">
    <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
    <?php if (!empty($head['subtitle'])): ?><p class="sub" style="max-width:60ch"><?= $e($head['subtitle']) ?></p><?php endif ?>
    <?php if ($chk = $vals($head['items'] ?? [])): ?><ul class="r2chk ofchk"><?php foreach ($chk as $h): ?><li><?= $e($h) ?></li><?php endforeach ?></ul><?php endif ?>
  </div>

  <div class="subg" style="margin-top:26px">
    <div>
      <?php if (!empty($how['items'])): ?>
      <section class="sec sec--s" style="padding-top:0">
        <h2 class="display h2" style="margin-bottom:20px"><?= $e($how['title'] ?? '') ?></h2>
        <div class="subhow"><?php foreach ($how['items'] as [$t, $d]): if ($t === '') continue; ?><div><b><?= $e($t) ?></b><p><?= $e($d) ?></p></div><?php endforeach ?></div>
      </section>
      <?php endif ?>

      <?php if ($rentB && $cfg['rent']): ?>
      <section class="sec sec--s"><div class="ofrent">
        <div><h2 class="display h3"><?= bt_title($rentB['title'] ?? '') ?></h2><?php if (!empty($rentB['subtitle'])): ?><p><?= $e($rentB['subtitle']) ?></p><?php endif ?></div>
        <?= bt_btn($rentB['btn_text'] ?? '', $rentB['btn_link'] ?? '') ?>
      </div></section>
      <?php endif ?>

      <?php if ($utpItems = bt_list('main_utp_items')): ?>
      <section class="sec sec--s">
        <h2 class="display h2" style="margin-bottom:20px"><?= $e($utp['title'] ?? 'Почему BEVERTEAM') ?></h2>
        <div class="utp"><?php foreach ($utpItems as $u): ?><div class="utp__i"><div class="ic"><?= $u['icon'] ?></div><b><?= $e($u['name']) ?></b><p><?= $e($u['text']) ?></p></div><?php endforeach ?></div>
      </section>
      <?php endif ?>

      <?php if ($faq): ?>
      <section class="sec sec--s">
        <h2 class="display h2" style="margin-bottom:20px">Вопросы о поставках кофе</h2>
        <div class="faq"><?php foreach ($faq as $i => $q): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q['name']) ?></summary><div class="faq__a"><?= str_starts_with(ltrim($q['html']), '<') ? $q['html'] : '<p>' . $q['html'] . '</p>' ?></div></details><?php endforeach ?></div>
      </section>
      <?php endif ?>
    </div>

    <?php if ($coffee): ?>
    <form class="subcard" id="form" data-form="lead" data-ofcfg='<?= htmlspecialcharsbx(json_encode($cfg, JSON_UNESCAPED_UNICODE)) ?>' novalidate>
      <p class="subcard__t"><?= $e(($head['title'] ?? '') ?: 'Рассчитайте поставки') ?></p>
      <p class="muted" style="font-size:13px;margin:0 0 20px">Цена пересчитывается сразу</p>

      <span class="mono muted subcard__l">Кофе в месяц</span>
      <div class="chipsx" role="group" aria-label="Кофе в месяц" style="margin-bottom:22px" data-of-kg>
        <?php foreach ($kgs as $k): ?><button type="button" class="chipx" data-v="<?= $k ?>" aria-pressed="<?= $k === $kg0 ? 'true' : 'false' ?>"><?= $k ?> кг<s data-of-pct></s></button><?php endforeach ?>
      </div>

      <span class="mono muted subcard__l">Доставка</span>
      <div class="chipsx" role="group" aria-label="Доставка" style="margin-bottom:22px" data-of-per>
        <?php foreach ($periods as $i => [$n, $t]): ?><button type="button" class="chipx" data-v="<?= $n ?>" aria-pressed="<?= $i ? 'false' : 'true' ?>"><?= $e($t) ?></button><?php endforeach ?>
      </div>

      <div class="field"><label>Сорт кофе</label><select name="sort" data-of-sort>
        <?php foreach ($coffee as $i => $c): ?><option value="<?= $i ?>"<?= $i === $sort0 ? ' selected' : '' ?>><?= $e($short($c['n'])) ?> · <?= $e(bt_fmt($c['p'])) ?>/кг</option><?php endforeach ?>
      </select></div>

      <div class="subtot">
        <div class="l"><span data-of-kgl>Кофе</span><span data-of-sum></span></div>
        <div class="l"><span>Оптовая цена от объёма</span><span data-of-disc style="color:var(--ok)"></span></div>
        <div class="l"><span data-of-shipl>Доставка</span><span data-of-ship></span></div>
        <div class="l t"><span>В месяц</span><span data-of-tot></span></div>
      </div>
      <p class="muted" style="font-size:12.5px;margin:10px 0 12px;min-height:2.6em" data-of-cup></p>
      <p class="ofbonus" data-of-rent hidden></p>

      <input type="hidden" name="topic" value="Кофе в офис">
      <input type="hidden" name="message" data-of-msg>
      <div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div>
      <div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div>
      <div class="field"><label>Компания</label><input name="company" maxlength="150" placeholder="Если оформляем на организацию"></div>
      <?= bt_form_tail(($head['btn_text'] ?? '') ?: 'Заказать поставки') ?>
      <?php if (!empty($head['caption'])): ?><p class="muted" style="font-size:12px;margin:12px 0 0;text-align:center"><?= $e($head['caption']) ?></p><?php endif ?>
    </form>
    <?php endif ?>
  </div>
</div>
