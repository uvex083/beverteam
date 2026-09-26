<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Кофе по подписке» по макету subscription.html: объёмы — пороги бесплатной аренды из ИБ rent, цены — каталог с оптовой сеткой; оформление — заявка

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$head = bt_block('sub_head');
$how = bt_block('sub_how');
$utp = bt_block('main_utp');
$data = bt_catalog_data();
$vols = array_values(array_filter(bt_rent_models(), fn($m) => $m['kg'] > 0));
usort($vols, fn($a, $b) => $a['kg'] <=> $b['kg']);
$coffee = $data['coffee'];
$sort0 = 0;
foreach ($coffee as $i => $c) {
    $c['code'] === 'botanica-espresso-smes' and $sort0 = $i;
}
$vol0 = min(1, count($vols) - 1);
$periods = ['Раз в месяц', 'Раз в 2 недели', 'Раз в 2 месяца'];
$cfg = [
    'vols' => array_map(fn($m) => ['kg' => $m['kg'], 'm' => $m['model'], 'rent' => $m['price']], $vols),
    'coffee' => array_map(fn($c) => ['id' => $c['id'], 'n' => $c['n'], 'p' => $c['p'], 'bulk' => $c['bulk'] ?? null], $coffee),
];
$short = fn(string $n) => trim(preg_replace(['/^BOTANICA\s+/u', '/,\s*1\s*кг$/u'], '', $n));

if ($vols && $coffee) {
    $minSum = min(array_map(fn($c) => $c['p'], $coffee)) * $vols[0]['kg'];
    $APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'Service',
        'name' => 'Кофе по подписке с бесплатной арендой кофемашины', 'serviceType' => 'Подписка на кофе', 'areaServed' => ['@type' => 'City', 'name' => 'Екатеринбург'],
        'provider' => ['@type' => 'LocalBusiness', '@id' => 'https://beverteam.ru/#org'],
        'offers' => ['@type' => 'Offer', 'priceCurrency' => 'RUB', 'price' => (string)$minSum, 'description' => 'От ' . $vols[0]['kg'] . ' кг кофе в месяц, аренда кофемашины 0 ₽']],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
}
?>
<div class="wrap subp">
  <?php bt_crumbs() ?>
  <div class="pagehead" style="padding-bottom:10px">
    <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
    <?php if (!empty($head['subtitle'])): ?><p class="sub" style="max-width:56ch"><?= $e($head['subtitle']) ?></p><?php endif ?>
  </div>

  <div class="subg" style="margin-top:26px">
    <div>
      <?php if (!empty($how['items'])): ?>
      <section class="sec sec--s" style="padding-top:0">
        <h2 class="display h2" style="margin-bottom:20px"><?= $e($how['title'] ?? '') ?></h2>
        <div class="subhow"><?php foreach ($how['items'] as [$t, $d]): ?><div><b><?= $e($t) ?></b><p><?= $e($d) ?></p></div><?php endforeach ?></div>
      </section>
      <?php endif ?>

      <?php if ($utpItems = bt_list('main_utp_items')): ?>
      <section class="sec sec--s">
        <h2 class="display h2" style="margin-bottom:20px"><?= $e($utp['title'] ?? 'Почему BEVERTEAM') ?></h2>
        <div class="utp"><?php foreach ($utpItems as $u): ?><div class="utp__i"><div class="ic"><?= $u['icon'] ?></div><b><?= $e($u['name']) ?></b><p><?= $e($u['text']) ?></p></div><?php endforeach ?></div>
      </section>
      <?php endif ?>

      <?php if ($faq = bt_blocks('sub_faq')): ?>
      <section class="sec sec--s">
        <h2 class="display h2" style="margin-bottom:20px">Вопросы о подписке</h2>
        <div class="faq"><?php foreach ($faq as $i => $q): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q['name']) ?></summary><div class="faq__a"><?= $q['html'] ?></div></details><?php endforeach ?></div>
      </section>
      <?php endif ?>
    </div>

    <?php if ($vols && $coffee): ?>
    <form class="subcard" id="form" data-form="lead" data-subcfg='<?= htmlspecialcharsbx(json_encode($cfg, JSON_UNESCAPED_UNICODE)) ?>' novalidate>
      <p class="subcard__t"><?= $e($head['title'] ?? 'Соберите подписку') ?></p>
      <p class="muted" style="font-size:13px;margin:0 0 20px">Цена пересчитывается сразу</p>

      <span class="mono muted subcard__l">Объём в месяц</span>
      <div class="vols">
        <?php foreach ($vols as $i => $v): ?>
        <label class="vol<?= $i === $vol0 ? ' on' : '' ?>"><input type="radio" name="vol" value="<?= $i ?>"<?= $i === $vol0 ? ' checked' : '' ?>>
          <span><b>от <?= $v['kg'] ?> кг</b><small><?= $e($v['model']) ?> — аренда 0 ₽ вместо <?= $e(bt_fmt($v['price'])) ?></small></span><span class="pct" data-sub-pct></span></label>
        <?php endforeach ?>
      </div>

      <span class="mono muted subcard__l">Периодичность доставки</span>
      <div class="chipsx" role="group" aria-label="Периодичность доставки" style="margin-bottom:22px">
        <?php foreach ($periods as $i => $p): ?><button type="button" class="chipx" data-sub-per aria-pressed="<?= $i ? 'false' : 'true' ?>"><?= $e($p) ?></button><?php endforeach ?>
      </div>

      <div class="field"><label>Сорт кофе</label><select name="sort" data-sub-sort>
        <?php foreach ($coffee as $i => $c): ?><option value="<?= $i ?>"<?= $i === $sort0 ? ' selected' : '' ?>><?= $e($short($c['n'])) ?> · <?= $e(bt_fmt($c['p'])) ?>/кг</option><?php endforeach ?>
      </select></div>

      <div class="subtot">
        <div class="l"><span data-sub-kg>Кофе</span><span data-sub-sum></span></div>
        <div class="l" data-sub-discrow><span>Оптовая цена от объёма</span><span data-sub-disc style="color:var(--ok)"></span></div>
        <div class="l"><span>Аренда кофемашины</span><span style="color:var(--ok)">0 ₽</span></div>
        <div class="l t"><span>В месяц</span><span data-sub-tot></span></div>
      </div>
      <p class="muted" style="font-size:12.5px;margin:10px 0 18px;min-height:2.6em" data-sub-cup></p>

      <input type="hidden" name="topic" value="Подписка на кофе">
      <input type="hidden" name="message" data-sub-msg>
      <div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div>
      <div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div>
      <?= bt_form_tail($head['btn_text'] ?? 'Оформить подписку') ?>
      <?php if (!empty($head['caption'])): ?><p class="muted" style="font-size:12px;margin:12px 0 0;text-align:center"><?= $e($head['caption']) ?></p><?php endif ?>
    </form>
    <?php endif ?>
  </div>
</div>
