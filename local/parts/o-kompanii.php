<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «О компании» по макету about.html: тексты — ИБ типа «О компании», отзывы — ИБ reviews

$e = fn($s) => htmlspecialcharsbx((string)$s);
$intro = bt_block('about_intro');
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

  <?php if ($values || $valueItems): ?>
  <section class="sec"><div class="vals">
    <div class="vals__hd">
      <?php if (!empty($values['caption'])): ?><div class="mono"><?= $e($values['caption']) ?></div><?php endif ?>
      <h2 class="display h2"><?= $e($values['title'] ?? '') ?></h2>
      <?= $values['text'] ?? '' ?>
    </div>
    <?php if ($valueItems): ?><div class="vals__g"><?php foreach ($valueItems as $u): ?><div class="utp__i"><?php if ($u['icon']): ?><div class="ic"><?= $u['icon'] ?></div><?php endif ?><b><?= $e($u['name']) ?></b><p><?= $e($u['text']) ?></p></div><?php endforeach ?></div><?php endif ?>
  </div></section>
  <?php endif ?>

  <section class="sec"><div class="grid g2" style="gap:40px">
    <div><h2 class="display h2" style="margin-bottom:22px"><?= $e($work['title'] ?? 'Чем занимаемся') ?></h2>
      <div class="tl"><?php foreach ($work['items'] ?? [] as [$t, $d]): ?><div><b><?= $e($t) ?></b><?= $e($d) ?></div><?php endforeach ?></div></div>
    <div><h2 class="display h2" style="margin-bottom:22px">Команда</h2>
      <?php if ($team): ?>
      <div class="grid g2"><?php foreach ($team as $p): ?><div class="person"><?php if ($p['pic']): ?><img class="ph" src="<?= $e($p['pic']) ?>" alt="<?= $e($p['name']) ?>" loading="lazy"><?php else: ?><div class="ph">фото</div><?php endif ?><b><?= $e($p['name']) ?></b><small><?= $e($p['text']) ?></small></div><?php endforeach ?></div>
      <?php else: ?>
      <div class="grid g2"><?php for ($i = 0; $i < 4; $i++): ?><div class="person"><div class="ph">фото</div><b>Имя</b><small>Должность</small></div><?php endfor ?></div>
      <p class="muted" style="font-size:13px;margin:12px 0 0">Фото и имена сотрудников — нужен файл от клиента.</p>
      <?php endif ?>
    </div>
  </div></section>

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

  <?php if ($revs): ?>
  <section class="sec sec--t0" id="about-reviews">
    <div class="row between" style="margin-bottom:22px"><h2 class="display h2">Отзывы клиентов</h2><a class="link" href="/otzyvy-o-nas/">Все отзывы →</a></div>
    <div class="grid g3 revs" data-revs><?php foreach ($revs as $r) echo bt_rev_card($r) ?></div>
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
</div>
