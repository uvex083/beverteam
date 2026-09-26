<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// Отзывы о компании — ИБ reviews (тип «О компании»), новые отзывы приходят заявкой и публикуются из админки

$e = fn($s) => htmlspecialcharsbx((string)$s);
$revs = bt_list('reviews');
$n = count($revs);
$word = ($n % 10 === 1 && $n % 100 !== 11) ? 'отзыв' : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 'отзыва' : 'отзывов');
?>
<div class="wrap aboutp">
  <?php bt_crumbs() ?>
  <div class="pagehead row between" style="align-items:flex-end">
    <div><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1><p class="sub"><?= $n ?> <?= $word ?> клиентов — офисов, кафе и частных покупателей.</p></div>
    <a class="btn" href="/kontakty/#form" data-lead="Отзыв о компании">Оставить отзыв</a>
  </div>
  <div class="grid g3">
    <?php foreach ($revs as $r): ?>
    <div class="rev" itemscope itemtype="https://schema.org/Review">
      <meta itemprop="itemReviewed" content="BEVERTEAM">
      <p itemprop="reviewBody"><?= $e($r['text']) ?></p>
      <div class="who"><b itemprop="author"><?= $e($r['name']) ?></b></div>
    </div>
    <?php endforeach ?>
  </div>
  <?php if (!$revs): ?><p class="muted">Отзывов пока нет.</p><?php endif ?>
</div>
