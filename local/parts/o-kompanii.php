<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «О компании» по макету about.html: тексты — ИБ типа «О компании», отзывы — ИБ reviews

$e = fn($s) => htmlspecialcharsbx((string)$s);
$intro = bt_block('about_intro');
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

  <?php if ($cert): ?>
  <section class="sec sec--t0"><div class="cert">
    <div><div class="mono" style="color:var(--lime)"><?= $e($cert['caption'] ?? '') ?></div><h2 class="display h2" style="margin:14px 0"><?= $e($cert['title'] ?? '') ?></h2><p style="color:#A8A8A0;margin:0;max-width:40ch"><?= $e($cert['subtitle'] ?? '') ?></p></div>
    <?php if (!empty($cert['pic'])): ?><img class="cert__img" src="<?= $e($cert['pic']) ?>"<?= bt_img_wh($cert['pic']) ?> alt="<?= $e($cert['title'] ?? '') ?>" loading="lazy"><?php else: ?><div class="ph"><span>Скан сертификата авторизации<br><small>нужен файл от клиента</small></span></div><?php endif ?>
  </div></section>
  <?php endif ?>

  <?php if ($revs): ?>
  <section class="sec sec--t0" id="reviews">
    <div class="row between" style="margin-bottom:22px"><h2 class="display h2">Отзывы клиентов</h2><a class="link" href="/otzyvy-o-nas/">Все отзывы →</a></div>
    <div class="grid g3 revs"><?php foreach (array_slice($revs, 0, 3) as $r) echo bt_rev_card($r) ?></div>
  </section>
  <?php endif ?>
</div>
