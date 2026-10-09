<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
/** @var \Bitrix\Sale\Order|null $order — загружен и проверен в personal/orders/detail.php */
// Кабинет: заказ по макету account-order.html — ход заказа, состав, доставка, оплата и документы

use Bitrix\Sale;

$APPLICATION->AddChainItem($order ? 'Заказ № ' . $order->getField('ACCOUNT_NUMBER') : 'Заказ');
if (!bt_acc_guard() || !$order) {
    return;
}
$e = fn($s) => htmlspecialcharsbx((string)$s);
$co = bt_contacts();
$props = [];
foreach ($order->getPropertyCollection() as $p) {
    $props[$p->getField('CODE')] = (string)$p->getValue();
}
$shipment = null;
foreach ($order->getShipmentCollection() as $s) {
    $s->isSystem() or $shipment = $s;
}
$payment = $order->getPaymentCollection()->current() ?: null;
$dCode = $shipment ? (string)(Sale\Delivery\Services\Table::getById($shipment->getDeliveryId())->fetch()['XML_ID'] ?? '') : '';
$pCode = $payment ? (string)(Sale\PaySystem\Manager::getById($payment->getPaymentSystemId())['ACTION_FILE'] ?? '') : '';
$payName = $payment ? trim(explode(' — ', $payment->getPaymentSystemName())[0]) : '';
$o = ['CANCELED' => $order->isCanceled() ? 'Y' : 'N', 'STATUS_ID' => $order->getField('STATUS_ID')];
[$st, $cls] = bt_order_status($o);
$date = fn($d) => $d ? FormatDate('j M, H:i', $d->getTimestamp()) : '';
$city = bt_loc($props['LOCATION'] ?? '')['n'];
$name = $props['FIO'] ?? $props['CONTACT_PERSON'] ?? '';
$phone = $props['PHONE'] ?? '' ? bt_phone_fmt($props['PHONE']) : '';
$dPrice = (float)$order->getDeliveryPrice();
$basket = $order->getBasket();
$disc = (float)$basket->getBasePrice() - (float)$basket->getPrice();
$num = $order->getField('ACCOUNT_NUMBER');
$shipped = in_array($o['STATUS_ID'], ['DS', 'DT', 'DF', 'F'], true) || ($shipment && $shipment->isShipped());

// ход заказа: по счёту сначала оплата, при получении — сначала доставка
$steps = ['acc' => ['Принят', true, $date($order->getDateInsert())],
    'pay' => ['Оплачен', $order->isPaid(), $order->isPaid() ? $date($order->getField('DATE_PAYED')) : ''],
    'ship' => [$dCode === 'bt_pickup' ? 'Готов к выдаче' : 'Передан в доставку', $shipped, ''],
    'done' => ['Выполнен', $o['STATUS_ID'] === 'F', $o['STATUS_ID'] === 'F' ? $date($order->getField('DATE_STATUS')) : '']];
if ($pCode !== 'bill') {
    $steps = ['acc' => $steps['acc'], 'ship' => $steps['ship'], 'pay' => $steps['pay'], 'done' => $steps['done']];
}
$lastDone = array_key_last(array_filter($steps, fn($s) => $s[1]));

bt_acc_start('orders', '<div class="row"><h1 class="display h1">Заказ № ' . $e($num) . '</h1><span class="status ' . $cls . '">' . $e($st) . '</span></div>',
    'Оформлен ' . $e(FormatDate('j F Y в H:i', $order->getDateInsert()->getTimestamp())) . ($payName ? ' · ' . $e(mb_strtolower(mb_substr($payName, 0, 1)) . mb_substr($payName, 1)) : ''));
?>
<div class="card">
  <?php if ($order->isCanceled()): ?>
  <div class="alert alert--err"><span>Заказ отменён<?= $order->getField('REASON_CANCELED') ? ': ' . $e($order->getField('REASON_CANCELED')) : '' ?>. Вопросы — по телефону <a class="link" href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a>.</span></div>
  <?php else: ?>
  <div class="track"><?php foreach ($steps as $k => [$t, $on, $when]): ?><div class="<?= $on ? 'done' : '' ?><?= $k === $lastDone ? ' cur' : '' ?>"><?= $e($t) ?><small><?= $e($when ?: ($on ? '' : '—')) ?></small></div><?php endforeach ?></div>
  <div class="alert alert--info"><span>Сроки и трек-номер сообщит менеджер. Изменить заказ можно до отправки — позвоните <a class="link" href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a>.</span></div>
  <?php endif ?>
</div>

<div class="card" style="margin-top:16px">
  <h2 class="h3" style="margin-bottom:8px">Состав заказа</h2>
  <?php foreach ($basket as $bi): $m = bt_product((string)$bi->getProductId()) ?>
  <div class="oit">
    <?php if (!empty($m['img'])): ?><img src="<?= $e($m['img']) ?>" alt="" loading="lazy" width="64" height="64"><?php else: ?><span class="ph"></span><?php endif ?>
    <div><?php if ($m): ?><a href="<?= $e($m['url'] ?? '') ?>"><b><?= $e($bi->getField('NAME')) ?></b></a><?php else: ?><b><?= $e($bi->getField('NAME')) ?></b><?php endif ?><?php if (!empty($m['par'])): ?><span class="muted" style="font-size:12.5px"><?= $e($m['par']) ?></span><?php endif ?></div>
    <span class="q"><?= bt_basket_qty($bi) ?> · <?= bt_fmt($bi->getPrice()) ?><?= bt_basket_pack($bi) ? ' за кг' : '' ?></span>
    <span class="p"><?= bt_fmt($bi->getFinalPrice()) ?></span>
  </div>
  <?php endforeach ?>
  <div class="tot">
    <div><span class="muted">Товары</span><span><?= bt_fmt((float)$basket->getBasePrice()) ?></span></div>
    <?php if ($disc > 0.5): ?><div><span class="muted">Скидка</span><span style="color:var(--ok)">−<?= bt_fmt($disc) ?></span></div><?php endif ?>
    <div><span class="muted">Доставка</span><span><?= $dCode === 'bt_cdek' && $dPrice == 0 ? 'сообщит менеджер' : ($dPrice > 0 ? bt_fmt($dPrice) : 'бесплатно') ?></span></div>
    <div class="t"><span>Итого</span><span><?= bt_fmt($order->getPrice()) ?></span></div>
  </div>
</div>

<div class="two">
  <div class="card"><h2 class="h3" style="margin-bottom:14px">Доставка</h2><dl class="dl">
    <dt>Способ</dt><dd><?= $e($shipment ? $shipment->getDeliveryName() : '—') ?></dd>
    <?php if ($dCode === 'bt_pickup'): ?><dt>Адрес склада</dt><dd><?= $e(($co['city'] ?? '') . ', ' . ($co['street'] ?? '')) ?></dd>
    <?php elseif (($props['PVZ'] ?? '') !== ''): ?><dt>Пункт выдачи</dt><dd><?= $e(($city ? $city . ', ' : '') . $props['PVZ']) ?></dd>
    <?php else: ?><dt>Адрес</dt><dd><?= $e(trim(($city ? $city . ', ' : '') . preg_replace('/\s*#S\S+$/u', '', $props['ADDRESS'] ?? ''), ', ') ?: '—') ?></dd><?php endif ?>
    <dt>Получатель</dt><dd><?= $e($name ?: ($phone ? '' : '—')) ?><?= $phone ? ($name ? ', ' : '') . '<span style="white-space:nowrap">' . $e($phone) . '</span>' : '' ?></dd>
    <?php if ($order->getField('USER_DESCRIPTION')): ?><dt>Комментарий</dt><dd><?= $e($order->getField('USER_DESCRIPTION')) ?></dd><?php endif ?>
  </dl></div>
  <div class="card"><h2 class="h3" style="margin-bottom:14px">Оплата и документы</h2><dl class="dl">
    <dt>Способ</dt><dd><?= $e($payName ?: '—') ?></dd>
    <dt>Статус</dt><dd><span class="status <?= $order->isPaid() ? 'st-paid' : 'st-new' ?>"><?= $order->isPaid() ? 'Оплачен' : 'Не оплачен' ?></span><?php if ($pCode === 'tinkoff' && !$order->isPaid() && !$order->isCanceled()): ?> <a class="btn btn--sm" href="/personal/order/pay/?id=<?= (int)$order->getId() ?>">Оплатить</a><?php endif ?></dd>
    <?php if (($props['COMPANY'] ?? '') !== ''): ?><dt>Покупатель</dt><dd><?= $e($props['COMPANY']) ?>, ИНН <?= $e($props['INN'] ?? '') ?></dd><?php endif ?>
    <dt>Документы</dt><dd><?php if ($pCode === 'bill' && bt_bill_ready($order)): ?><a class="btn btn--sm btn--dark" href="/local/ajax/bill.php?id=<?= (int)$order->getId() ?>"><?= bt_icon('doc') ?> Скачать счёт (PDF)</a><br>
      <?php elseif ($pCode === 'bill' && !$order->isCanceled()): ?>Счёт придёт на e‑mail после подтверждения заказа менеджером<br>
      <?php else: ?><?= $pCode === 'bill' ? 'Счёт и УПД пришлёт менеджер' : 'Чек — при получении, УПД — по запросу' ?><br><?php endif ?>
      <a class="link" href="/kontakty/#form" data-lead="Документы по заказу № <?= $e($num) ?>">Запросить документы</a></dd>
  </dl></div>
</div>

<div class="row" style="margin-top:20px;gap:10px">
  <button class="btn" type="button" data-reorder="<?= (int)$order->getId() ?>"><?= bt_icon('repeat') ?> Повторить заказ</button>
  <a class="btn btn--ghost" href="/kontakty/#form" data-lead="Вопрос по заказу № <?= $e($num) ?>">Вопрос по заказу</a>
  <?php if (!$order->isCanceled() && !$shipped && !$order->isPaid()): ?><a class="btn btn--ghost" href="/kontakty/#form" data-lead="Отменить заказ № <?= $e($num) ?>">Отменить заказ</a><?php endif ?>
</div>
<?php bt_acc_end();
