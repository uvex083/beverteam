<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Аренда кофемашин» по макету service-rent.html: модели, калькулятор, сравнение — из ИБ rent и каталога; тексты — ИБ типа «Аренда кофемашин»

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$top = bt_block('rent_top');
$event = bt_block('rent_event');
$seo = bt_block('rent_seo');
$form = bt_block('rent_form');
$models = bt_rent_models();
$data = bt_catalog_data();

// калькулятор: расход 8 г на чашку × 22 рабочих дня, цена зерна — эспрессо-смесь BOTANICA
$bean = null;
foreach ($data['coffee'] as $c) {
    if ($c['code'] === 'botanica-espresso-smes') {
        $bean = $c;
    }
}
$bean ??= $data['coffee'][0] ?? null;
$byCups = $models;
usort($byCups, fn($a, $b) => $a['cups'] <=> $b['cups']);
$calc = [
    'models' => array_map(fn($m) => ['id' => $m['id'], 'm' => $m['model'], 'aud' => $m['audience'], 'cups' => $m['cups'], 'price' => $m['price'], 'kg' => $m['kg'], 'img' => $m['img']], $byCups),
    'bean' => $bean ? ['n' => $bean['n'], 'p' => $bean['p'], 'bulk' => $bean['bulk'] ?? null, 'url' => $bean['url']] : null,
    'g' => 8, 'days' => 22,
];
$cups0 = 30;
$m0 = null;
foreach ($byCups as $m) {
    if (!$m0 && $m['cups'] >= $cups0) {
        $m0 = $m;
    }
}
$m0 ??= end($byCups) ?: null;
$maxCups = $byCups ? max(array_column($byCups, 'cups')) : 100;
?>
<div class="wrap rentp">
  <?php bt_crumbs() ?>
  <div class="top">
    <div>
      <?php if (!empty($top['caption'])): ?><div class="mono muted" style="margin-bottom:14px"><?= $e($top['caption']) ?></div><?php endif ?>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <?php if (!empty($top['subtitle'])): ?><p class="l"><?= $e($top['subtitle']) ?></p><?php endif ?>
      <?php if (!empty($top['items'])): ?><div class="incl"><?php foreach ($top['items'] as [$t]): ?><div><?= $e($t) ?></div><?php endforeach ?></div><?php endif ?>
    </div>
    <?php if ($m0 && $bean): ?>
    <div class="calc" id="calc" data-rcalc='<?= htmlspecialcharsbx(json_encode($calc, JSON_UNESCAPED_UNICODE)) ?>'>
      <span class="q"><?= $e($top['title'] ?? 'Где будет стоять машина?') ?></span>
      <div class="opts" role="group" aria-label="<?= $e($top['title'] ?? 'Где будет стоять машина?') ?>">
        <?php foreach ($byCups as $m): ?><button type="button" class="chipx" data-rc-m="<?= $e($m['id']) ?>" aria-pressed="<?= $m === $m0 ? 'true' : 'false' ?>"><?= $e($m['audience']) ?></button><?php endforeach ?>
        <?php if ($event): ?><a class="chipx" href="#event">На мероприятие</a><?php endif ?>
      </div>
      <label class="q" for="rcCups">Сколько чашек в день? <b data-rc-n><?= $cups0 ?></b></label>
      <input class="rng" id="rcCups" type="range" min="5" max="<?= $maxCups + 50 ?>" step="5" value="<?= $cups0 ?>" data-rc-cups>
      <div class="calc__out">
        <img src="<?= $e($m0['img']) ?>" alt="<?= $e($m0['model']) ?>" data-rc-img width="96" height="108">
        <div>
          <h4 data-rc-name><?= $e($m0['model']) ?></h4>
          <p class="sp" data-rc-s></p>
          <div class="pr"><span data-rc-p><?= $e(bt_fmt($m0['price'])) ?></span> <small>в месяц</small></div>
          <p class="calc__alt" data-rc-alt></p>
          <a class="btn btn--sm" href="#form" data-rc-btn><?= $e($top['btn_text'] ?? 'Оставить заявку') ?></a>
        </div>
      </div>
      <p class="calc__note" data-rc-note></p>
    </div>
    <?php endif ?>
  </div>

  <?php if ($data['rent']): ?>
  <section class="sec" id="models">
    <div class="row between" style="margin-bottom:22px"><h2 class="display h2">Модели в аренду</h2><a class="link" href="/magazin/professionalnye-kofemashiny/">Купить в собственность →</a></div>
    <div class="grid g3"><?php foreach ($data['rent'] as $m) echo bt_card($m) ?></div>
  </section>

  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">Сравнение моделей</h2>
    <div class="tblw"><table class="tbl">
      <thead><tr><th>Параметр</th><?php foreach ($models as $m): ?><th><?= $e($m['model']) ?></th><?php endforeach ?></tr></thead>
      <tbody>
        <tr><td>Для кого</td><?php foreach ($models as $m): ?><td><?= $e($m['audience']) ?></td><?php endforeach ?></tr>
        <tr><td>Нагрузка</td><?php foreach ($models as $m): ?><td><?= $m['cups'] ? 'до ' . $m['cups'] . ' чашек в день' : '—' ?></td><?php endforeach ?></tr>
        <tr><td>Аренда</td><?php foreach ($models as $m): ?><td><b><?= $e(bt_fmt($m['price'])) ?>/мес</b></td><?php endforeach ?></tr>
        <tr><td>Аренда 0 ₽</td><?php foreach ($models as $m): ?><td><?= $m['kg'] ? 'при заказе кофе от ' . $m['kg'] . '&nbsp;кг в месяц' : '—' ?></td><?php endforeach ?></tr>
        <tr><td>Покупка</td><?php foreach ($models as $m): ?><td><?= $m['buy'] ? '<a class="link" href="' . $e($m['url']) . '">' . $e(bt_fmt($m['buy'])) . '</a>' : 'по запросу' ?></td><?php endforeach ?></tr>
      </tbody>
    </table></div>
  </section>
  <?php endif ?>

  <?php if ($terms = bt_list('rent_terms')): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">Условия аренды</h2>
    <div class="grid g4"><?php foreach ($terms as $t): ?><div class="card"><b><?= $e($t['name']) ?></b><p class="muted" style="font-size:13.5px;margin:8px 0 0"><?= $e($t['text']) ?></p></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($event): ?>
  <section class="sec sec--t0" id="event"><div class="cta">
    <div><h2 class="display h2"><?= bt_title($event['title'] ?? '') ?></h2><?php if (!empty($event['subtitle'])): ?><p><?= $e($event['subtitle']) ?></p><?php endif ?></div>
    <?= bt_btn($event['btn_text'] ?? '', $event['btn_link'] ?? '', 'btn btn--dark') ?>
  </div></section>
  <?php endif ?>

  <?php if (!empty($seo['text'])): ?>
  <section class="sec sec--t0"><div class="seo"><?= $seo['text'] ?></div></section>
  <?php endif ?>

  <?php if ($faq = bt_blocks('rent_faq')): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">Вопросы и ответы об аренде кофемашин</h2>
    <div class="faq">
      <?php foreach ($faq as $i => $q): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q['name']) ?></summary><div class="faq__a"><?= $q['html'] ?></div></details><?php endforeach ?>
    </div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0" id="form"><form class="card form" data-form="lead" novalidate>
    <div><div class="mono muted">Заявка</div><h2 class="display h2" style="margin:14px 0 10px"><?= $e($form['title'] ?? 'Заявка на аренду') ?></h2><?php if (!empty($form['subtitle'])): ?><p class="muted" style="margin:0;max-width:40ch"><?= $e($form['subtitle']) ?></p><?php endif ?></div>
    <div>
      <input type="hidden" name="topic" value="Аренда кофемашины">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="field"><label>Модель</label><select name="model"><?php foreach ($models as $m): ?><option value="<?= $e($m['model']) ?>"><?= $e($m['model']) ?> — <?= $e(bt_fmt($m['price'])) ?>/мес</option><?php endforeach ?><option value="Помогите подобрать" selected>Помогите подобрать</option></select></div>
      <div class="field"><label>Комментарий</label><textarea name="message" rows="3" maxlength="2000" placeholder="Место установки, число чашек в день, нужные напитки"></textarea></div>
      <?= bt_form_tail($form['btn_text'] ?? 'Отправить заявку') ?>
    </div>
  </form></section>
</div>
