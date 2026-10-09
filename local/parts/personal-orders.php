<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CUser $USER */
// Кабинет: заказы покупателя по макету account-orders.html — фильтр по статусу, повтор последнего заказа

use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale\Internals\PaymentTable;
use Bitrix\Sale\Internals\ShipmentTable;

if (!bt_acc_guard()) {
    return;
}
Loader::includeModule('sale');
$e = fn($s) => htmlspecialcharsbx((string)$s);
$uid = (int)$USER->GetID();
bt_tbank_sync_user($uid);
$orders = OrderTable::getList(['filter' => ['=USER_ID' => $uid, '=LID' => SITE_ID],
    'select' => ['ID', 'ACCOUNT_NUMBER', 'DATE_INSERT', 'STATUS_ID', 'CANCELED', 'PRICE', 'PAYED'], 'order' => ['ID' => 'DESC']])->fetchAll();
$ids = array_column($orders, 'ID');
$items = $pays = $ships = $toPay = [];
if ($ids) {
    foreach (BasketTable::getList(['filter' => ['@ORDER_ID' => $ids], 'select' => ['ORDER_ID', 'PRODUCT_ID', 'NAME', 'QUANTITY', 'MEASURE_NAME'], 'order' => ['ID' => 'ASC']])->fetchAll() as $b) {
        $items[$b['ORDER_ID']][] = $b;
    }
    $online = bt_tbank_ps_ids();
    foreach (PaymentTable::getList(['filter' => ['@ORDER_ID' => $ids], 'select' => ['ORDER_ID', 'PAY_SYSTEM_NAME', 'PAY_SYSTEM_ID', 'PAID']])->fetchAll() as $p) {
        $pays[$p['ORDER_ID']] = trim(explode(' — ', $p['PAY_SYSTEM_NAME'])[0]);
        $p['PAID'] !== 'Y' && in_array((int)$p['PAY_SYSTEM_ID'], $online, true) && $toPay[$p['ORDER_ID']] = true;
    }
    foreach (ShipmentTable::getList(['filter' => ['@ORDER_ID' => $ids, '=SYSTEM' => 'N'], 'select' => ['ORDER_ID', 'DELIVERY_NAME']])->fetchAll() as $s) {
        $ships[$s['ORDER_ID']] = $s['DELIVERY_NAME'];
    }
}
$group = fn(array $o) => $o['CANCELED'] === 'Y' ? 'cancel' : ($o['STATUS_ID'] === 'F' ? 'done' : 'active');
$cnt = array_count_values(array_map($group, $orders));
// повторить предлагаем последний оплаченный или подтверждённый менеджером заказ
$repeat = current(array_filter($orders, fn($o) => $o['CANCELED'] !== 'Y' && ($o['PAYED'] === 'Y' || $o['STATUS_ID'] !== 'N') && !empty($items[$o['ID']])));
$word = fn(int $n) => $n . ' ' . (($n % 10 === 1 && $n % 100 !== 11) ? 'товар' : (in_array($n % 10, [2, 3, 4]) && !in_array($n % 100, [12, 13, 14]) ? 'товара' : 'товаров'));

bt_acc_start('orders', '<h1 class="display h1">Мои заказы</h1>');
if (!$orders): ?>
  <div class="card empty">
    <p class="display h3">Заказов пока нет</p>
    <p class="muted">Здесь появятся заказы, оформленные с вашим e-mail: статус, состав и повтор в один клик.</p>
    <a class="btn" href="/catalog/">Перейти в каталог</a>
  </div>
<?php else: ?>
  <?php if ($repeat): ?>
  <div class="reorder">
    <div><span class="mono" style="color:var(--lime)">Последний заказ · <?= $e(FormatDate('j F', $repeat['DATE_INSERT']->getTimestamp())) ?></span>
      <h2>Повторить заказ № <?= $e($repeat['ACCOUNT_NUMBER']) ?></h2>
      <p>Положим тот же состав в корзину. Доставку и оплату выберете при оформлении.</p>
      <div class="it"><?php foreach (array_slice($items[$repeat['ID']], 0, 6) as $b): ?><span><?= $e($b['NAME']) ?> · <?= (float)$b['QUANTITY'] ?> <?= $e($b['MEASURE_NAME'] ?: 'шт') ?></span><?php endforeach ?></div></div>
    <div style="display:grid;gap:10px"><button class="btn" type="button" data-reorder="<?= (int)$repeat['ID'] ?>"><?= bt_icon('repeat') ?> Повторить заказ</button>
      <a class="btn btn--line" style="color:#fff;border-color:#fff" href="/personal/docs/">Счета и документы</a></div>
  </div>
  <?php endif ?>
  <div class="ftabs" data-ftabs>
    <button type="button" aria-pressed="true" data-f="">Все · <?= count($orders) ?></button>
    <?php foreach (['active' => 'Активные', 'done' => 'Выполненные', 'cancel' => 'Отменённые'] as $k => $t): if (!empty($cnt[$k])): ?>
    <button type="button" aria-pressed="false" data-f="<?= $k ?>"><?= $t ?> · <?= $cnt[$k] ?></button>
    <?php endif; endforeach ?>
  </div>
  <?php foreach ($orders as $o): [$st, $cls] = bt_order_status($o); $list = $items[$o['ID']] ?? [] ?>
  <div class="ord" data-g="<?= $group($o) ?>">
    <a class="ord__lnk" href="/personal/orders/<?= (int)$o['ID'] ?>/" aria-label="Заказ № <?= $e($o['ACCOUNT_NUMBER']) ?>"></a>
    <div><div class="hd"><span class="n">№ <?= $e($o['ACCOUNT_NUMBER']) ?></span><span class="dt"><?= $e(FormatDate('j F Y', $o['DATE_INSERT']->getTimestamp())) ?></span><span class="ord__st"><span class="status <?= $cls ?>"><?= $e($st) ?></span><?php if ($o['PAYED'] === 'Y'): ?><span class="pay pay--ok">Оплачен</span><?php elseif ($o['CANCELED'] !== 'Y'): ?><span class="pay">Не оплачен</span><?php endif ?></span></div>
      <div class="items"><?php foreach (array_slice($list, 0, 4) as $b): $m = bt_product((string)$b['PRODUCT_ID']);
          if (!empty($m['img'])): ?><img src="<?= $e($m['img']) ?>" alt="" loading="lazy" width="44" height="44"><?php endif; endforeach ?>
        <span><?= $word(count($list)) ?><?= !empty($ships[$o['ID']]) ? ' · ' . $e($ships[$o['ID']]) : '' ?></span></div></div>
    <div class="r"><span class="sum"><?= bt_fmt((float)$o['PRICE']) ?></span>
      <?php if (!empty($pays[$o['ID']])): ?><span class="muted" style="font-size:12.5px"><?= $e($pays[$o['ID']]) ?></span><?php endif ?>
      <?php if (!empty($toPay[$o['ID']]) && $o['CANCELED'] !== 'Y'): ?><a class="btn btn--xs" href="/personal/order/pay/?id=<?= (int)$o['ID'] ?>">Оплатить</a>
      <?php elseif ($list): ?><button class="btn btn--ghost btn--xs" type="button" data-reorder="<?= (int)$o['ID'] ?>">Повторить</button><?php endif ?></div>
  </div>
  <?php endforeach ?>
<?php endif;
bt_acc_end();
