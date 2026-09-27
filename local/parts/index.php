<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// Главная по макету index.html; H1 — заголовок блока «О магазине», как на старом сайте (SetTitle в /index.php). Тексты — из инфоблоков типа «Главная», данные — из каталога, аренды, отзывов и журнала

// неразрывные пробелы в числах и перед ₽: «3 000 ₽» не рвётся по строкам
$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$co = bt_contacts();
$hero = bt_block('main_hero');
$cfg = bt_block('main_config');
$incl = bt_block('main_incl');
$utp = bt_block('main_utp');
$cat = bt_block('main_catalog');
$rentB = bt_block('main_rent');
$svc = bt_block('main_service');
$bean = bt_block('main_bean');
$steps = bt_block('main_steps');
$revB = bt_block('main_reviews');
$sub = bt_block('main_sub');
$about = bt_block('main_about');
if (!empty($about['title'])) {
    $APPLICATION->SetTitle($about['title']);
}
$jour = bt_block('main_journal');
$seo = bt_block('main_seo');
$rent = bt_rent_models();
$data = bt_catalog_data();
$head = fn(array $b, string $link = '') => '<div class="sec__head" data-rv><div><h2 class="display h2">' . $e($b['title'] ?? '') . '</h2>'
    . (($b['subtitle'] ?? '') !== '' ? '<p>' . $e($b['subtitle']) . '</p>' : '') . '</div>' . $link . '</div>';

$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'BEVERTEAM',
    'url' => 'https://beverteam.ru/', 'potentialAction' => ['@type' => 'SearchAction', 'target' => 'https://beverteam.ru/search/?q={search_term_string}',
    'query-input' => 'required name=search_term_string']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');

// подбор кофемашины: модели аренды + пункты блока (мероприятие — по заявке)
$chips = [];
foreach ($rent as $m) {
    $chips[] = ['k' => $m['audience'], 'img' => $m['img'], 'm' => $m['model'],
        's' => implode(' · ', array_filter([$m['cups'] ? 'До ' . $m['cups'] . ' чашек в день' : '', $m['kg'] ? 'бесплатно при заказе от ' . $m['kg'] . ' кг кофе в месяц' : ''])),
        'p' => bt_fmt($m['price']), 'u' => 'в месяц'];
}
foreach ($cfg['items'] ?? [] as [$name, $desc]) {
    if ($name !== '') {
        $chips[] = ['k' => $name, 'img' => $rent ? end($rent)['img'] : '', 'm' => 'Аренда: ' . mb_strtolower($name), 's' => $desc, 'p' => 'по запросу', 'u' => '', 'lead' => 'Аренда: ' . mb_strtolower($name)];
    }
}
$cur = 0;
foreach ($chips as $i => $c) {
    if (mb_stripos($c['k'], 'офис') === 0) {
        $cur = $i;
    }
}
$c0 = $chips[$cur] ?? null;
?>
<div class="home">
<section class="hero"><div class="wrap hero__grid">
  <div class="hero-in">
    <?php if (!empty($hero['caption'])): ?><div class="eyebrow mono"><i></i><?= $e($hero['caption']) ?></div><?php endif ?>
    <p class="display hero__title"><?= bt_title($hero['title'] ?? '', $hero['highlight'] ?? '') ?></p>
    <?php if (!empty($hero['subtitle'])): ?><p class="hero__sub"><?= $e($hero['subtitle']) ?></p><?php endif ?>
    <div class="hero__cta">
      <?= bt_btn($hero['btn_text'] ?? '', $hero['btn_link'] ?? '') ?>
      <?= bt_btn($hero['btn2_text'] ?? '', $hero['btn2_link'] ?? '', 'btn btn--dark') ?>
      <?= bt_btn($hero['btn3_text'] ?? '', $hero['btn3_link'] ?? '', 'btn btn--line') ?>
    </div>
    <?php if (!empty($hero['link_text'])): ?><p style="margin:14px 0 0"><?= bt_btn($hero['link_text'], $hero['link_url'] ?? '', 'link') ?></p><?php endif ?>
    <?php if ($facts = bt_list('main_facts')): ?>
    <div class="facts">
      <?php foreach ($facts as $f): ?>
      <div><b<?= preg_match('/^\d+(?:[.,]\d+)?/u', $f['name'], $mm) ? ' data-count="' . $e($mm[0]) . '"' : '' ?>><?= $e($f['name']) ?></b><span><?= $e($f['text']) ?></span></div>
      <?php endforeach ?>
    </div>
    <?php endif ?>
  </div>
  <div class="hero__right">
    <?php if ($c0): ?>
    <div class="cfg" data-cfg='<?= $e(json_encode($chips, JSON_UNESCAPED_UNICODE)) ?>'>
      <p class="cfg__q"><?= $e($cfg['title'] ?? '') ?></p>
      <p class="cfg__hint"><?= $e($cfg['subtitle'] ?? '') ?></p>
      <div class="chipsx" role="group" aria-label="<?= $e($cfg['title'] ?? '') ?>">
        <?php foreach ($chips as $i => $c): ?>
        <button type="button" class="chipx" aria-pressed="<?= $i === $cur ? 'true' : 'false' ?>" data-i="<?= $i ?>"><?= $e($c['k']) ?></button>
        <?php endforeach ?>
      </div>
      <div class="cfg__res">
        <div class="cfg__img"><img src="<?= $e($c0['img']) ?>" alt="<?= $e($c0['m']) ?>" data-cfg-img></div>
        <div><p class="cfg__model" data-cfg-m><?= $e($c0['m']) ?></p><p class="cfg__spec" data-cfg-s><?= $e($c0['s']) ?></p>
          <div class="cfg__price"><b data-cfg-p><?= $e($c0['p']) ?></b><span data-cfg-u><?= $e($c0['u']) ?></span></div>
          <a class="btn btn--dark" data-cfg-btn href="<?= $e($cfg['btn_link'] ?? '/arenda-kofemashin/') ?>" data-href="<?= $e($cfg['btn_link'] ?? '/arenda-kofemashin/') ?>" data-text="<?= $e($cfg['btn_text'] ?? '') ?>"<?= !empty($c0['lead']) ? ' data-lead="' . $e($c0['lead']) . '"' : '' ?>><?= $e(!empty($c0['lead']) ? 'Оставить заявку' : ($cfg['btn_text'] ?? '')) ?></a></div>
      </div>
    </div>
    <?php endif ?>
    <?php if ($incl): ?>
    <div class="hincl">
      <span class="hincl__t"><?= $e($incl['title'] ?? '') ?></span>
      <ul><?php foreach ($incl['items'] ?? [] as [$t]): ?><li><?= $e($t) ?></li><?php endforeach ?></ul>
      <div class="hincl__f">
        <span class="muted" style="font-size:12.5px"><?= $e($incl['caption'] ?? '') ?></span>
        <?= bt_btn($incl['btn_text'] ?? '', $incl['btn_link'] ?? '', 'link', ' style="margin-left:auto;font-size:13px;white-space:nowrap"') ?>
      </div>
    </div>
    <?php endif ?>
  </div>
</div></section>

<?php if ($strip = bt_list('main_strip')): ?>
<div class="strip" data-rv><div class="wrap strip__in">
  <?php foreach ($strip as $s): ?><div class="strip__i"><span class="ic"><?= $s['icon'] ?></span><p><?= $e($s['name']) ?></p></div><?php endforeach ?>
</div></div>
<?php endif ?>

<?php if ($utpItems = bt_list('main_utp_items')): ?>
<section class="sec"><div class="wrap">
  <?= $head($utp) ?>
  <div class="utp" data-rv>
    <?php foreach ($utpItems as $u): ?><div class="utp__i"><div class="ic"><?= $u['icon'] ?></div><b><?= $e($u['name']) ?></b><p><?= $e($u['text']) ?></p></div><?php endforeach ?>
  </div>
</div></section>
<?php endif ?>

<section class="sec sec--t0"><div class="wrap">
  <?= $head($cat, bt_btn($cat['btn_text'] ?? '', $cat['btn_link'] ?? '', 'link')) ?>
  <div class="tiles" id="tiles">
    <?php foreach (bt_home_tiles() as $t): ?>
    <a class="tile" href="<?= $e($t['url']) ?>"><span class="tile__ph"><?php if ($t['img']): ?><img src="<?= $e($t['img']) ?>" alt="" loading="lazy"><?php endif ?></span><b><?= $e($t['name']) ?></b><small><?= $e($t['note']) ?></small></a>
    <?php endforeach ?>
  </div>
</div></section>

<section class="sec sec--t0" id="rent"><div class="wrap">
  <?= $head($rentB, '<div class="pill-tabs" role="tablist"><button type="button" role="tab" aria-selected="true" data-tab="rent">Аренда кофемашин</button><button type="button" role="tab" aria-selected="false" data-tab="machines">Продажа кофемашин</button></div>') ?>
  <div class="grid g4" data-rv data-tabpane="rent"><?php foreach ($data['rent'] as $m) echo bt_card($m) ?></div>
  <div class="grid g4" data-tabpane="machines" hidden><?php foreach ($data['machines'] as $m) echo bt_card($m) ?></div>
  <div class="row" style="justify-content:center;margin-top:28px">
    <?= bt_btn($rentB['btn_text'] ?? '', $rentB['btn_link'] ?? '', 'btn btn--line', ' data-tabpane="rent"') ?>
    <?= bt_btn($rentB['btn2_text'] ?? '', $rentB['btn2_link'] ?? '', 'btn btn--line', ' data-tabpane="machines" hidden') ?>
  </div>
</div></section>

<?php if ($svc): ?>
<section class="sec sec--t0"><div class="wrap"><div class="svc" data-rv>
  <div class="svc__l">
    <div class="mono" style="color:var(--lime)"><?= $e($svc['caption'] ?? '') ?></div>
    <h2 class="display h2" style="margin-top:14px"><?= bt_title($svc['title'] ?? '', $svc['highlight'] ?? '', 'span') ?></h2>
    <?php if (!empty($svc['subtitle'])): ?><p><?= $e($svc['subtitle']) ?></p><?php endif ?>
    <?php if (!empty($svc['items'])): ?><ul class="svc__list"><?php foreach ($svc['items'] as [$t, $v]): ?><li><?= $e($t) ?> <i><?= $e($v) ?></i></li><?php endforeach ?></ul><?php endif ?>
    <div class="row"><?= bt_btn($svc['btn_text'] ?? '', $svc['btn_link'] ?? '') ?><?= bt_btn($svc['btn2_text'] ?? '', $svc['btn2_link'] ?? '', 'btn btn--line btn--inv') ?></div>
  </div>
  <div class="svc__r"><?php if ($svc['pic']): ?><img src="<?= $e($svc['pic']) ?>" alt="<?= $e(str_replace("\n", ' ', $svc['title'] ?? '')) ?>" loading="lazy"><?php endif ?></div>
</div></div></section>
<?php endif ?>

<?php
$bp = !empty($bean['product']) ? bt_product((string)$bean['product']) : null;
if ($bp):
    $spec = bt_catalog_specs()[$bp['id']] ?? [];
    $notes = array_filter(array_map(fn($s) => mb_strtoupper(mb_substr(trim($s), 0, 1)) . mb_substr(trim($s), 1), explode(',', htmlspecialchars_decode($spec['notes'] ?? ''))));
?>
<section class="sec sec--t0"><div class="wrap"><div class="bean" data-rv>
  <div class="bean__ph"><?php if (!empty($spec['q'])): ?><div class="qscore"><div><b><?= $spec['q'] ?></b><span>Q-score</span></div></div><?php endif ?><img src="<?= $e($bp['img']) ?>" alt="<?= $e($bp['n']) ?>" loading="lazy"></div>
  <div class="bean__c">
    <div class="mono"><?= $e($bean['caption'] ?? '') ?></div>
    <h2 class="display h2"><?= bt_title($bean['title'] ?? $bp['n']) ?></h2>
    <?php if (!empty($bean['subtitle'])): ?><p><?= $e($bean['subtitle']) ?></p><?php endif ?>
    <?php if ($notes): ?><div class="notes"><?php foreach ($notes as $n): ?><span><?= $e($n) ?></span><?php endforeach ?></div><?php endif ?>
    <div class="row"><?= bt_btn(($bean['btn_text'] ?? '') ?: bt_fmt($bp['p']) . ' · к товару', ($bean['btn_link'] ?? '') ?: $bp['url']) ?><?= bt_btn($bean['btn2_text'] ?? '', $bean['btn2_link'] ?? '', 'btn btn--line') ?></div>
  </div>
</div></div></section>
<?php endif ?>

<?php if (!empty($steps['items'])): ?>
<section class="sec sec--t0"><div class="wrap">
  <?= $head($steps) ?>
  <div class="stepsx">
    <?php foreach ($steps['items'] as $i => [$t, $d]): ?><div class="stepx"><b>Шаг <?= sprintf('%02d', $i + 1) ?></b><h3><?= $e($t) ?></h3><p><?= $e($d) ?></p></div><?php endforeach ?>
  </div>
</div></section>
<?php endif ?>

<?php if ($revs = bt_list('reviews')): ?>
<section class="sec sec--t0"><div class="wrap">
  <?= $head($revB, bt_btn($revB['btn_text'] ?? '', $revB['btn_link'] ?? '', 'link')) ?>
  <div class="grid g3" data-rv>
    <?php foreach (array_slice($revs, 0, 3) as $r): ?><div class="rcard"><p><?= $e($r['text']) ?></p><footer><span class="av" aria-hidden="true"><?= $e(mb_substr($r['name'], 0, 1)) ?></span><span><b><?= $e($r['name']) ?></b></span></footer></div><?php endforeach ?>
  </div>
</div></section>
<?php endif ?>

<?php if ($sub && $rent): ?>
<section class="sec sec--t0"><div class="wrap"><div class="svc" data-rv>
  <div class="svc__l">
    <div class="mono" style="color:var(--lime)"><?= $e($sub['caption'] ?? '') ?></div>
    <h2 class="display h2" style="margin-top:14px"><?= bt_title($sub['title'] ?? '', $sub['highlight'] ?? '', 'span') ?></h2>
    <?php if (!empty($sub['subtitle'])): ?><p><?= $e($sub['subtitle']) ?></p><?php endif ?>
    <ul class="svc__list">
      <?php foreach ($rent as $m) if ($m['kg']): ?><li>От <?= $m['kg'] ?> кг кофе в месяц <i><?= $e($m['model']) ?> — 0 ₽ аренда</i></li><?php endif ?>
      <?php foreach ($sub['items'] ?? [] as [$t, $v]): ?><li><?= $e($t) ?> <i><?= $e($v) ?></i></li><?php endforeach ?>
    </ul>
    <div class="row"><?= bt_btn($sub['btn_text'] ?? '', $sub['btn_link'] ?? '') ?><?= bt_btn($sub['btn2_text'] ?? '', $sub['btn2_link'] ?? '', 'btn btn--line btn--inv') ?></div>
  </div>
  <div class="svc__r"><?php if ($sub['pic']): ?><img src="<?= $e($sub['pic']) ?>" alt="<?= $e(str_replace("\n", ' ', $sub['title'] ?? '')) ?>" loading="lazy"><?php endif ?></div>
</div></div></section>
<?php endif ?>

<section class="sec sec--t0" id="form"><div class="wrap">
  <div class="wr">
    <div>
      <h1 class="display h2"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <div class="home__about"><?= $about['text'] ?? '' ?></div>
      <div class="row" style="margin-top:24px"><?= bt_btn($about['btn_text'] ?? '', $about['btn_link'] ?? '', 'btn btn--line') ?><?= bt_btn($about['btn2_text'] ?? '', $about['btn2_link'] ?? '', 'btn btn--line') ?></div>
    </div>
    <form class="wr__f" data-form="contact" novalidate>
      <div class="hd"><i aria-hidden="true">✍</i><div><b>НАПИШИТЕ НАМ</b><small><?= $e($about['caption'] ?? '') ?></small></div></div>
      <input type="hidden" name="topic" value="Сообщение с главной страницы">
      <div class="field"><label>Ваше имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div>
      <div class="field"><label>E-mail</label><input name="email" type="email" placeholder="mail@company.ru" maxlength="100"></div>
      <div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div>
      <?= bt_form_tail() ?>
    </form>
  </div>
</div></section>

<?php if ($posts = bt_posts()): ?>
<section class="sec sec--t0"><div class="wrap">
  <?= $head($jour, bt_btn($jour['btn_text'] ?? '', $jour['btn_link'] ?? '', 'link')) ?>
  <div class="news" id="news"><?php foreach (array_slice($posts, 0, 3) as $p) echo bt_post_card($p) ?></div>
</div></section>
<?php endif ?>

<?php if ($seo): ?>
<section class="sec sec--t0"><div class="wrap seo">
  <h2 class="display"><?= $e($seo['title'] ?? '') ?></h2>
  <?= $seo['text'] ?? '' ?>
</div></section>
<?php endif ?>

<section class="sec sec--t0"><div class="wrap">
  <div class="ymap" data-ymap>
    <div class="pin"></div><div class="cap">Яндекс Карты · <?= $e($co['street'] ?? '') ?></div>
    <div class="addr"><b>Склад и самовывоз</b><?= $e($co['zip'] ?? '') ?>, <?= $e($co['city'] ?? '') ?>,<br><?= $e($co['street'] ?? '') ?><br><span class="muted"><?= $e($co['hours'] ?? '') ?></span><br><a class="link" href="/kontakty/" style="font-size:13px">Как добраться →</a></div>
  </div>
</div></section>
</div>
