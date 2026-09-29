<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «О компании» по макету about.html: тексты — ИБ типа «О компании», отзывы — ИБ reviews

$e = fn($s) => htmlspecialcharsbx((string)$s);
$intro = bt_block('about_intro');
$numbers = bt_list('about_numbers');
$history = bt_list('about_history');
$places = bt_list('about_places');
$partners = bt_list('about_partners');
$rating = bt_block('about_rating');
$faq = bt_list('about_faq');
$form = bt_block('about_form');
$isDemo = fn(array $items) => (bool)array_filter($items, fn($x) => $x['demo']);
$values = bt_block('about_values');
$valueItems = bt_list('about_values_items');
$gallery = array_values(array_filter(bt_list('about_gallery'), fn($g) => $g['pic']));
$work = bt_block('about_work');
$cert = bt_block('about_cert');
$team = bt_list('about_team');
$revs = bt_reviews();
?>
<div class="wrap aboutp">
  <?php bt_crumbs() ?>
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1></div>
  <div class="intro">
    <div>
      <?= $intro['text'] ?? '' ?>
      <?php if (!empty($intro['items'])): ?>
      <div class="facts"><?php foreach ($intro['items'] as [$v, $d]): ?><div><b><?= $e($v) ?></b><span><?= $e($d) ?></span></div><?php endforeach ?></div>
      <?php endif ?>
    </div>
    <div class="intro__ph"><?php if (!empty($intro['pic'])): ?><img src="<?= $e($intro['pic']) ?>" alt="Чай и кофе BEVERTEAM" fetchpriority="high"><?php else: ?><div class="ph"><span>Фото компании<br><small>нужен файл от клиента</small></span></div><?php endif ?></div>
  </div>

  <?php if ($numbers): ?>
  <section class="sec sec--t0" style="padding-top:var(--pad)"><?= bt_demo_note('about_numbers', $isDemo($numbers)) ?>
    <div class="anum"><?php foreach ($numbers as $n): ?><div><b><?= $e($n['name']) ?></b><span><?= $e($n['text']) ?></span></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($values || $valueItems): ?>
  <section class="sec"><div class="vals">
    <div class="vals__hd">
      <?php if (!empty($values['caption'])): ?><div class="mono"><?= $e($values['caption']) ?></div><?php endif ?>
      <h2 class="display h2"><?= $e($values['title'] ?? '') ?></h2>
      <?= $values['text'] ?? '' ?>
    </div>
    <?php if ($valueItems): ?><div class="vals__g"><?php foreach ($valueItems as $u): ?><?= $u['link'] ? '<a class="utp__i utp__i--a" href="' . $e($u['link']) . '">' : '<div class="utp__i">' ?><?php if ($u['icon']): ?><div class="ic"><?= $u['icon'] ?></div><?php endif ?><b><?= $e($u['name']) ?></b><p><?= $e($u['text']) ?></p><?= $u['link'] ? '</a>' : '</div>' ?><?php endforeach ?></div><?php endif ?>
  </div></section>
  <?php endif ?>

  <section class="sec"><div class="grid g2" style="gap:40px">
    <div><h2 class="display h2" style="margin-bottom:22px"><?= $e($work['title'] ?? 'Чем занимаемся') ?></h2>
      <div class="tl"><?php foreach ($work['items'] ?? [] as $i => [$t, $d]): $href = $work['links'][$i][0] ?? ''; ?><?= $href !== '' ? '<a href="' . $e($href) . '">' : '<div>' ?><b><?= $e($t) ?></b><?= $e($d) ?><?= $href !== '' ? ' <span class="tl__go">→</span></a>' : '</div>' ?><?php endforeach ?></div></div>
    <div><h2 class="display h2" style="margin-bottom:22px">Команда</h2>
      <?php if ($team): ?>
      <?= bt_demo_note('about_team', $isDemo($team)) ?>
      <div class="grid g2 ateam"><?php foreach ($team as $p): ?><div class="person"><?php if ($p['pic']): ?><img class="ph" src="<?= $e($p['pic']) ?>" alt="<?= $e($p['name']) ?>" loading="lazy"><?php else: ?><div class="ph">фото</div><?php endif ?><b><?= $e($p['name']) ?></b><small><?= $e($p['text']) ?></small></div><?php endforeach ?></div>
      <?php else: ?>
      <div class="grid g2"><?php for ($i = 0; $i < 4; $i++): ?><div class="person"><div class="ph">фото</div><b>Имя</b><small>Должность</small></div><?php endfor ?></div>
      <p class="muted" style="font-size:13px;margin:12px 0 0">Фото и имена сотрудников — нужен файл от клиента.</p>
      <?php endif ?>
    </div>
  </div></section>

  <?php if ($history): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">История</h2>
    <?= bt_demo_note('about_history', $isDemo($history)) ?>
    <ol class="ahist"><?php foreach ($history as $h): ?><li><b><?= $e($h['name']) ?></b><span><?= $e($h['text']) ?></span></li><?php endforeach ?></ol>
  </section>
  <?php endif ?>

  <?php if ($places): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">Офис, склад и сервис</h2>
    <?= bt_demo_note('about_places', $isDemo($places)) ?>
    <div class="aplaces"><?php foreach ($places as $pl): ?><figure><?php if ($pl['pic']): ?><img src="<?= $e($pl['pic']) ?>" alt="<?= $e($pl['name']) ?>" loading="lazy"><?php else: ?><div class="ph"><span>фото</span></div><?php endif ?><figcaption><?= $e($pl['name']) ?></figcaption></figure><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($gallery): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">Фотогалерея нашей продукции</h2>
    <div class="agal"><div data-gallery><?php foreach ($gallery as $g): ?><a href="<?= $e($g['pic']) ?>"><img src="<?= $e($g['pic']) ?>"<?= bt_img_wh($g['pic']) ?> alt="<?= $e($g['name']) ?>" loading="lazy"></a><?php endforeach ?></div></div>
  </section>
  <?php endif ?>

  <?php if ($cert): ?>
  <section class="sec sec--t0"><div class="cert">
    <div><div class="mono" style="color:var(--lime)"><?= $e($cert['caption'] ?? '') ?></div><h2 class="display h2" style="margin:14px 0"><?= $e($cert['title'] ?? '') ?></h2><p style="color:#A8A8A0;margin:0;max-width:40ch"><?= $e($cert['subtitle'] ?? '') ?></p></div>
    <?php if (!empty($cert['pic'])): ?><img class="cert__img" src="<?= $e($cert['pic']) ?>"<?= bt_img_wh($cert['pic']) ?> alt="<?= $e($cert['title'] ?? '') ?>" loading="lazy"><?php else: ?><div class="ph"><span>Скан сертификата авторизации<br><small>нужен файл от клиента</small></span></div><?php endif ?>
  </div></section>
  <?php endif ?>

  <?php if ($partners): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">Партнёры и клиенты</h2>
    <?= bt_demo_note('about_partners', $isDemo($partners)) ?>
    <div class="apart"><?php foreach ($partners as $pt): $tag = $pt['link'] !== '' ? 'a' : 'div'; ?><<?= $tag ?> class="apart__i<?= $pt['demo'] ? ' is-demo' : '' ?>"<?= $tag === 'a' ? ' href="' . $e($pt['link']) . '"' . (str_starts_with($pt['link'], 'http') ? ' target="_blank" rel="noopener"' : '') : '' ?>><?php if ($pt['pic']): ?><img src="<?= $e($pt['pic']) ?>" alt="<?= $e($pt['name']) ?>" loading="lazy"><?php else: ?><b><?= $e($pt['name']) ?></b><?php endif ?><small><?= $e($pt['text']) ?></small></<?= $tag ?>><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($revs): ?>
  <section class="sec sec--t0" id="about-reviews">
    <?php if (!empty($rating['title'])): ?><?= bt_demo_note('about_rating', !empty($rating['demo'])) ?><?php endif ?>
    <div class="row between" style="margin-bottom:22px;gap:16px;flex-wrap:wrap"><h2 class="display h2">Отзывы клиентов</h2>
      <div class="row" style="gap:18px;flex-wrap:wrap;align-items:center">
        <?php if (!empty($rating['title'])): ?><a class="arate" href="<?= $e($rating['btn_link'] ?? '') ?>" target="_blank" rel="noopener"><b><?= $e($rating['title']) ?></b><span class="arate__st" aria-hidden="true">★★★★★</span><span><?= $e($rating['subtitle'] ?? '') ?></span></a><?php endif ?>
        <a class="link" href="/otzyvy-o-nas/">Все отзывы →</a>
      </div></div>
    <div class="grid g3 revs" data-revs><?php foreach ($revs as $r) echo bt_rev_card($r) ?></div>
  </section>
  <?php endif ?>

  <?php if ($faq): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:20px">Вопросы о работе с нами</h2>
    <div class="faq"><?php foreach ($faq as $i => $q): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q['name']) ?></summary><div class="faq__a"><p><?= $e($q['text']) ?></p></div></details><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php $req = bt_requisites(); if ($req): ?>
  <section class="sec sec--t0" id="rekvizity">
    <div class="areq">
      <div>
        <h2 class="display h2">Реквизиты</h2>
        <p class="muted">BEVERTEAM — чай и кофе для дома и бизнеса. Работаем с ИП и юрлицами по договору: оплата по счёту, закрывающие документы.</p>
        <div class="req__btns">
          <a class="btn" href="/local/ajax/requisites.php" download="BEVERTEAM-rekvizity.pdf"><?= bt_icon('doc') ?>Скачать реквизиты (PDF)</a>
          <button class="btn btn--line" type="button" data-copy="<?= $e(implode(PHP_EOL, array_map(fn($r) => $r[0] . ': ' . $r[1], $req))) ?>">Скопировать</button>
        </div>
      </div>
      <dl class="req__list"><?php foreach ($req as [$l, $v]): ?><div><dt><?= $e($l) ?></dt><dd><?= $e($v) ?></dd></div><?php endforeach ?></dl>
    </div>
  </section>
  <?php endif ?>

  <?php if ($form): $co = bt_contacts(); ?>
  <section class="sec sec--t0" id="form"><form class="card form aform" data-form="lead" novalidate>
    <div><div class="mono muted"><?= $e($form['caption'] ?? 'Заявка') ?></div><h2 class="display h2" style="margin:14px 0 10px"><?= $e($form['title'] ?? '') ?></h2>
      <?php if (!empty($form['subtitle'])): ?><p class="muted" style="margin:0;max-width:40ch"><?= $e($form['subtitle']) ?></p><?php endif ?>
      <?php if (!empty($co['phone1'])): ?><p style="margin:18px 0 0"><a class="link" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a></p><?php endif ?></div>
    <div>
      <input type="hidden" name="topic" value="Заявка со страницы «О компании»">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="field"><label>Компания</label><input name="company" maxlength="150" placeholder="Название кафе, офиса или ИП — если заявка от бизнеса"></div>
      <div class="field"><label>Комментарий</label><textarea name="message" rows="3" maxlength="2000" placeholder="Что нужно: кофе, кофемашина, сервис, опт"></textarea></div>
      <?= bt_form_tail(($form['btn_text'] ?? '') ?: 'Отправить заявку') ?>
    </div>
  </form></section>
  <?php endif ?>
  <script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'AboutPage', 'name' => 'О компании BEVERTEAM', 'url' => 'https://beverteam.ru/o-kompanii/',
      'mainEntity' => ['@id' => 'https://beverteam.ru/#org'],
      'about' => ['@id' => 'https://beverteam.ru/#org', 'hasCredential' => ['@type' => 'EducationalOccupationalCredential', 'credentialCategory' => 'Авторизованный сервисный центр Jetinno']]],
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</div>
