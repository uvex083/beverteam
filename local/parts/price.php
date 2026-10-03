<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// Прайс-лист: товары каталога по разделам с оптовыми ступенями цен; количество меняет общую корзину сайта (ui.js, data-prc)

$e = fn($s) => htmlspecialcharsbx((string)$s);
$rub = fn($p) => number_format((float)$p, 0, '', "\u{00A0}") . "\u{00A0}₽";
$list = bt_price_list();
$total = array_sum(array_column($list, 'cnt'));
$ts = bt_price_date();
$months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
// цена за кг для веса $kg по ступеням товара
$tier = function (array $m, int $kg) {
    $p = $m['p'];
    foreach ((array)($m['bulk'] ?? []) as $b) {
        $b['kg'] <= $kg and $p = $b['p'];
    }
    return $p;
};
?>
<div class="wrap prcp">
  <?php bt_crumbs() ?>

  <div class="prchead">
    <div>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <p class="prcsub">Кофе, чай, кофемашины и аксессуары с ценами для офисов, кафе и магазинов. Прайс обновляется вместе с каталогом — ссылка всегда показывает действующие цены.</p>
      <p class="prcdate"><?= bt_icon('calendar') ?>Цены актуальны на <?= date('j', $ts) . ' ' . $months[date('n', $ts) - 1] . ' ' . date('Y', $ts) ?></p>
    </div>
    <div class="prcact">
      <a class="btn btn--line" href="?format=csv" download><?= bt_icon('doc') ?>Скачать Excel</a>
      <button class="btn btn--line" type="button" data-prc-print>Печать / PDF</button>
      <button class="btn btn--line" type="button" data-prc-share>Скопировать ссылку</button>
    </div>
  </div>

  <div class="prcbar" data-prc-bar>
    <label class="prcsearch"><?= bt_icon('search') ?><input type="search" placeholder="Поиск: Оромия, улун, чайник" aria-label="Поиск по прайсу" autocomplete="off" data-prc-q></label>
    <div class="prccats" role="group" aria-label="Категория">
      <button class="chipx" type="button" data-prc-cat="" aria-pressed="true">Весь прайс <s><?= $total ?></s></button>
      <?php foreach ($list as $code => $t): ?><button class="chipx" type="button" data-prc-cat="<?= $e($code) ?>" aria-pressed="false"><?= $e($t['name']) ?> <s><?= $t['cnt'] ?></s></button><?php endforeach ?>
    </div>
  </div>

  <?php foreach ($list as $code => $t):
      $kgs = [];
      foreach ($t['subs'] as $items) {
          foreach ($items as $m) {
              foreach ((array)($m['bulk'] ?? []) as $b) {
                  $kgs[$b['kg']] = true;
              }
          }
      }
      ksort($kgs);
      $kgs = array_keys($kgs);
      $cols = max(1, count($kgs)); ?>
  <section class="prcsec" data-prc-sec="<?= $e($code) ?>">
    <h2 class="prcsec__t"><a href="<?= $e($t['url']) ?>"><?= $e($t['name']) ?></a></h2>
    <?php if ($kgs): ?><p class="prcsec__n">Оптовая цена — за килограмм, считается по общему весу позиции в заказе. Выбранная ступень подсвечивается.</p><?php endif ?>
    <table class="prct<?= $kgs ? ' prct--tiers' : '' ?>">
      <thead><tr>
        <th scope="col">Товар</th>
        <?php if ($kgs): foreach ($kgs as $i => $k): ?><th scope="col" class="prct__p"><?= $i ? 'от ' . $k . ' кг' : $k . ' кг' ?></th><?php endforeach; else: ?><th scope="col" class="prct__p">Цена</th><?php endif ?>
        <th scope="col" class="prct__qh">Количество</th>
      </tr></thead>
      <?php foreach ($t['subs'] as $sub => $items): ?>
      <tbody data-prc-group>
        <?php if ($sub !== ''): ?><tr class="prct__sub"><th colspan="<?= $cols + 2 ?>" scope="colgroup"><?= $e($sub) ?></th></tr><?php endif ?>
        <?php foreach ($items as $m):
            $bulk = !empty($m['bulk']);
            $q = mb_strtolower($m['n'] . ' ' . ($m['par'] ?? '') . ' ' . $sub . ' ' . $t['name']); ?>
        <tr class="prct__r" data-prc-row data-id="<?= $e($m['id']) ?>" data-kg="<?= $bulk ? 1 : 0 ?>" data-q="<?= $e($q) ?>">
          <td class="prct__n">
            <a href="<?= $e($m['url']) ?>"><?= $e($m['n']) ?></a>
            <?php if (!empty($m['par'])): ?><small><?= $e($m['par']) ?></small><?php endif ?>
            <?php if (!empty($m['badges']) || !empty($m['pre']) || empty($m['stock'])): ?><span class="prct__b"><?= implode('', array_map('bt_badge', (array)($m['badges'] ?? []))) ?><?= !empty($m['pre']) ? '<span class="badge badge--warn">Предзаказ</span>' : (empty($m['stock']) ? '<span class="badge badge--soft">Под заказ</span>' : '') ?></span><?php endif ?>
          </td>
          <?php if (!$m['p']): ?>
          <td class="prct__p prct__ask" colspan="<?= $cols ?>">По запросу</td>
          <?php elseif ($kgs && $bulk): foreach ($kgs as $i => $k): ?>
          <td class="prct__p" data-prc-kg="<?= $k ?>" data-label="<?= $i ? 'от ' . $k . ' кг' : $k . ' кг' ?>"><?= $rub($tier($m, $k)) ?></td>
          <?php endforeach; else: ?>
          <td class="prct__p" colspan="<?= $cols ?>"><?= $rub($m['p']) ?><?php if (!empty($m['old']) && $m['old'] > $m['p']): ?> <s><?= $rub($m['old']) ?></s><?php endif ?><?php if ($kgs): ?><small>цена не зависит от объёма</small><?php endif ?></td>
          <?php endif ?>
          <td class="prct__q">
            <?php if ($m['p']): ?>
            <div class="prcq">
              <button type="button" data-prc-step="-1" aria-label="Меньше">−</button><input type="text" inputmode="numeric" value="0" aria-label="<?= $e($m['n']) ?>: количество, <?= $bulk ? 'кг' : 'шт' ?>" data-prc-in><button type="button" data-prc-step="1" aria-label="Больше">+</button>
            </div>
            <span class="prcq__u"><?= $bulk ? 'кг' : 'шт' ?></span>
            <span class="prcq__s" data-prc-sum></span>
            <?php endif ?>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
      <?php endforeach ?>
    </table>
  </section>
  <?php endforeach ?>
  <p class="prcempty" data-prc-empty hidden>Ничего не нашлось. <button class="link" type="button" data-prc-reset>Показать весь прайс</button></p>

  <p class="prcfoot">Цены в рублях. Окончательную стоимость, наличие и сроки подтвердит менеджер после оформления заказа. Нужен счёт на юрлицо — выберите «Юридическое лицо / ИП» при оформлении.</p>
</div>

<div class="prcbag" data-prc-bag hidden>
  <div class="wrap prcbag__in">
    <span class="prcbag__t">В корзине <b data-prc-bag-n></b><span data-prc-bag-s></span></span>
    <a class="btn" href="/personal/cart/">Оформить заказ</a>
  </div>
</div>
