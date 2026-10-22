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
$pCode === 'tinkoff' && !$payment->isPaid() && !$order->isCanceled() && bt_tbank_sync($payment);
$toPay = !$order->isCanceled() && bt_tbank_payment($order);
$payName = $payment ? trim(explode(' — ', $payment->getPaymentSystemName())[0]) : '';
// в заказе «При получении» без контекста непонятно, о чём речь
$pCode === 'cash' and $payName = 'Оплата при получении';
$o = ['CANCELED' => $order->isCanceled() ? 'Y' : 'N', 'STATUS_ID' => $order->getField('STATUS_ID'), 'PAYED' => $order->isPaid() ? 'Y' : 'N', 'BILL' => $pCode === 'bill'];
[$st, $cls] = bt_order_status($o);
$date = fn($d) => $d ? FormatDate('j M, H:i', $d->getTimestamp()) : '';
$city = bt_loc($props['LOCATION'] ?? '')['n'];
$name = $props['FIO'] ?? $props['CONTACT_PERSON'] ?? '';
$phone = $props['PHONE'] ?? '' ? bt_phone_fmt($props['PHONE']) : '';
$dPrice = (float)$order->getDeliveryPrice();
$basket = $order->getBasket();
$full = 0.0;
foreach ($basket as $bi) {
    $full += bt_basket_full($bi);
}
$disc = $full - (float)$basket->getPrice();
$num = $order->getField('ACCOUNT_NUMBER');
// ход заказа — одна шкала для всех способов, как у маркетплейсов: оплата не шаг, она в блоке «Оплата и документы».
// Собираем — менеджер подтвердил или пришла оплата; у самовывоза третий шаг — статус «Готов к выдаче»
$sid = (string)$o['STATUS_ID'];
$pickup = $dCode === 'bt_pickup';
$shipped = $pickup ? in_array($sid, ['R', 'F'], true) : (in_array($sid, ['DS', 'DT', 'DF', 'F'], true) || ($shipment && $shipment->isShipped()));
// по счёту «Подтверждён» — ещё ждём оплату, собираем после неё
$waitBill = $pCode === 'bill' && !$order->isPaid() && $sid === 'C';
// дата «Готов к выдаче» — из истории заказа: после «Выполнен» дата статуса уже другая
$readyAt = $pickup && $shipped ? \Bitrix\Sale\Internals\OrderChangeTable::getList(['filter' => ['=ORDER_ID' => $order->getId(), '=TYPE' => 'ORDER_STATUS_CHANGED', '%DATA' => 's:9:"STATUS_ID";s:1:"R"'],
    'select' => ['DATE_CREATE'], 'order' => ['ID' => 'DESC'], 'limit' => 1])->fetch()['DATE_CREATE'] ?? null : null;
$steps = ['acc' => ['Принят', true, $date($order->getDateInsert())],
    'build' => ['Собираем', $shipped || $order->isPaid() || $sid === 'P' || ($sid === 'C' && !$waitBill), ''],
    'ship' => [$pickup ? 'Готов к выдаче' : 'Передан в доставку', $shipped, $readyAt ? $date($readyAt) : ''],
    'done' => [$pickup ? 'Выдан' : 'Получен', $sid === 'F', $sid === 'F' ? $date($order->getField('DATE_STATUS')) : '']];
$lastDone = array_key_last(array_filter($steps, fn($s) => $s[1]));

bt_acc_start('orders', '<div class="row"><h1 class="display h1">Заказ № ' . $e($num) . '</h1><span class="status ' . $cls . '">' . $e($st) . '</span></div>',
    'Оформлен ' . $e(FormatDate('j F Y в H:i', $order->getDateInsert()->getTimestamp())) . ($payName ? ' · ' . $e(bt_lcfirst($payName)) : ''));
?>
<div class="card">
  <?php if ($order->isCanceled()): ?>
  <div class="alert alert--err"><span>Заказ отменён<?= $order->getField('REASON_CANCELED') ? ': ' . $e($order->getField('REASON_CANCELED')) : '' ?>. Вопросы — по телефону <a class="link" href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a>.</span></div>
  <?php else: ?>
  <div class="track"><?php foreach ($steps as $k => [$t, $on, $when]): ?><div class="<?= $on ? 'done' : '' ?><?= $k === $lastDone ? ' cur' : '' ?>"><?= $e($t) ?><small><?= $e($when ?: ($on ? '' : '—')) ?></small></div><?php endforeach ?></div>
  <?php $tel = '<a class="link" href="' . $e($co['phone1_href'] ?? '') . '">' . $e($co['phone1'] ?? '') . '</a>' ?>
  <div class="alert alert--info"><span><?php if ($sid === 'F'): ?>Заказ <?= $pickup ? 'выдан' : 'получен' ?> — спасибо, что выбрали BEVERTEAM! Вопросы по заказу — <?= $tel ?>.
    <?php elseif ($waitBill): ?>Счёт выставлен — соберём заказ, как только придёт оплата. Вопросы по счёту — <?= $tel ?>.
    <?php elseif ($pickup && $sid === 'R'): ?>Заказ готов — забирайте: <?= $e(($co['city'] ?? '') . ', ' . ($co['street'] ?? '')) ?>, <?= $e(mb_strtolower($co['hours'] ?? '')) ?>. Вопросы — <?= $tel ?>.
    <?php else: ?><?= $e(bt_delivery_next($dCode)) ?> Изменить заказ можно до <?= $pickup ? 'выдачи' : 'отправки' ?> — позвоните <?= $tel ?>.<?php endif ?></span></div>
  <?php endif ?>
</div>

<div class="card" style="margin-top:16px">
  <h2 class="h3" style="margin-bottom:8px">Состав заказа</h2>
  <?php foreach ($basket as $bi): $m = bt_product((string)$bi->getProductId()) ?>
  <div class="oit">
    <?php if (!empty($m['img'])): ?><img src="<?= $e($m['img']) ?>" alt="" loading="lazy" width="64" height="64"><?php else: ?><span class="ph"></span><?php endif ?>
    <div><?php if ($m): ?><a href="<?= $e($m['url'] ?? '') ?>"><b><?= $e($bi->getField('NAME')) ?></b></a><?php else: ?><b><?= $e($bi->getField('NAME')) ?></b><?php endif ?><?php if (!empty($m['par'])): ?><span class="muted" style="font-size:12.5px"><?= $e($m['par']) ?></span><?php endif ?></div>
    <span class="q"><?= bt_basket_qty($bi) ?> · <?= bt_fmt($bi->getPrice()) ?><?= bt_basket_pack($bi) ? ' за кг' : '' ?></span>
    <span class="p"><?php if (bt_basket_full($bi) > $bi->getFinalPrice() + 0.5): ?><del><?= bt_fmt(bt_basket_full($bi)) ?></del><?php endif ?><?= bt_fmt($bi->getFinalPrice()) ?></span>
  </div>
  <?php endforeach ?>
  <div class="tot">
    <div><span class="muted">Товары</span><span><?= bt_fmt($full) ?></span></div>
    <?php if ($disc > 0.5): ?><div><span class="muted">Скидка</span><span style="color:var(--ok)">−<?= bt_fmt($disc) ?></span></div><?php endif ?>
    <div><span class="muted">Доставка</span><span><?= $dCode === 'bt_cdek' && $dPrice == 0 ? 'сообщит менеджер' : ($dPrice > 0 ? bt_fmt($dPrice) : 'бесплатно') ?></span></div>
    <div class="t"><span>Итого</span><span><?= bt_fmt($order->getPrice()) ?></span></div>
  </div>
</div>

<div class="two">
  <div class="card"><h2 class="h3" style="margin-bottom:14px">Доставка</h2><dl class="dl">
    <dt>Способ</dt><dd><?= $e($shipment ? $shipment->getDeliveryName() : '—') ?></dd>
    <?php if ($dCode === 'bt_pickup'): ?><dt>Адрес склада</dt><dd><?= $e(($co['city'] ?? '') . ', ' . ($co['street'] ?? '')) ?></dd>
    <?php elseif (($props['PVZ'] ?? '') !== ''): $pvz = bt_pvz_point($props['PVZ']);
      $pvzA = preg_replace(['/\s*#S\S+$/u', '/^Пункт выдачи СДЭК:\s*/u'], '', $props['ADDRESS'] ?? '') ?: ($pvz['a'] ?? ''); ?>
    <dt>Пункт выдачи</dt><dd><?= $e(($city ? $city . ', ' : '') . $pvzA) ?><?php if ($pvz && $pvz['w'] !== ''): ?><span class="muted" style="display:block;font-size:13px"><?= $e($pvz['w']) ?></span><?php endif ?>
      <?php if ($pvz): ?><button type="button" class="link pvzv__go" data-pvzv>На карте</button><?php endif ?></dd>
    <?php else: ?><dt>Адрес</dt><dd><?= $e(trim(($city ? $city . ', ' : '') . preg_replace('/\s*#S\S+$/u', '', $props['ADDRESS'] ?? ''), ', ') ?: '—') ?></dd><?php endif ?>
    <dt>Получатель</dt><dd><?= $e($name ?: ($phone ? '' : '—')) ?><?= $phone ? ($name ? ', ' : '') . '<span style="white-space:nowrap">' . $e($phone) . '</span>' : '' ?></dd>
    <?php if ($order->getField('USER_DESCRIPTION')): ?><dt>Комментарий</dt><dd><?= $e($order->getField('USER_DESCRIPTION')) ?></dd><?php endif ?>
  </dl></div>
  <div class="card"><h2 class="h3" style="margin-bottom:14px">Оплата и документы</h2><dl class="dl">
    <dt>Способ</dt><dd><?= $e($payName ?: '—') ?></dd>
    <dt>Статус</dt><dd><?php $paidOnGet = !$order->isPaid() && $pCode === 'cash' && $sid === 'F'; // при получении: деньги взяли при выдаче, отметку в заказе менеджер мог не поставить ?>
    <span class="pay<?= $order->isPaid() || $paidOnGet ? ' pay--ok' : '' ?>"><?= $order->isPaid() ? 'Оплачен' : ($paidOnGet ? 'Оплачен при получении' : 'Не оплачен') ?></span><?php if (!$order->isPaid() && !$paidOnGet && $pCode === 'cash' && !$order->isCanceled()): ?><span class="dnote" style="margin-top:6px">Наличными или картой</span><?php endif ?><?php if ($toPay): ?> <a class="btn btn--sm" href="/personal/order/pay/?id=<?= (int)$order->getId() ?>">Оплатить</a><?php endif ?></dd>
    <?php if (($props['COMPANY'] ?? '') !== ''): ?><dt>Покупатель</dt><dd><?= $e($props['COMPANY']) ?>, ИНН <?= $e($props['INN'] ?? '') ?></dd><?php endif ?>
    <?php // онлайн-оплата: только ссылка на чек из уведомления банка, пока её нет — строку не показываем
    $check = $pCode === 'tinkoff' && ($cp = $order->getPropertyCollection()->getItemByOrderPropertyCode('RECEIPT_URL')) ? (string)$cp->getValue() : '';
    if ($pCode !== 'tinkoff' || $check !== ''): ?>
    <dt>Документы</dt><dd><?php if ($check !== ''): ?><a class="link" href="<?= $e($check) ?>" target="_blank" rel="noopener">Кассовый чек</a></dd>
    <?php else: if ($pCode === 'bill' && bt_bill_ready($order)): ?><a class="odoc" href="/local/ajax/bill.php?id=<?= (int)$order->getId() ?>" target="_blank" rel="noopener"><?= bt_icon('doc') ?><span class="link">Счёт № <?= $e($num) ?>, PDF</span></a>
      <?php elseif ($pCode === 'bill' && !$order->isCanceled()): ?><span class="dnote">Счёт появится здесь и придёт на e‑mail, когда менеджер подтвердит заказ</span>
      <?php else: ?><span class="dnote"><?= $pCode === 'bill' ? 'Счёт и УПД пришлёт менеджер' : 'Чек — при получении, УПД — по запросу' ?></span><?php endif ?>
      <a class="link dreq" href="/kontakty/#form" data-lead="Документы по заказу № <?= $e($num) ?>">Запросить документы</a></dd>
    <?php endif; endif ?>
  </dl></div>
</div>

<div class="row" style="margin-top:20px;gap:10px">
  <?php if ($toPay): ?><a class="btn" href="/personal/order/pay/?id=<?= (int)$order->getId() ?>">Оплатить заказ</a>
  <?php elseif ($order->isCanceled() || $order->getField('STATUS_ID') === 'F'): ?><button class="btn" type="button" data-reorder="<?= (int)$order->getId() ?>"><?= bt_icon('repeat') ?> Повторить заказ</button><?php endif ?>
  <a class="btn btn--ghost" href="/kontakty/#form" data-lead="Вопрос по заказу № <?= $e($num) ?>">Вопрос по заказу</a>
  <?php if (!$order->isCanceled() && !$shipped && !$order->isPaid()): ?><a class="btn btn--ghost" href="/kontakty/#form" data-lead="Отменить заказ № <?= $e($num) ?>">Отменить заказ</a><?php endif ?>
</div>
<?php if (!empty($pvz)): ?>
<div class="modal modal--pvzv" id="pvzView" role="dialog" aria-modal="true" aria-labelledby="pvzVT">
  <div class="modal__bg" data-close></div>
  <div class="modal__p">
    <button class="modal__x" type="button" data-close aria-label="Закрыть">×</button>
    <div class="pvzv">
      <div>
        <h2 class="pvzm__t" id="pvzVT">Пункт выдачи СДЭК</h2>
        <p class="pvzm__a"><?= $e(($city ? $city . ', ' : '') . $pvzA) ?></p>
        <dl class="pvzm__dl">
          <?php if ($pvz['w'] !== ''): ?><dt>Режим работы</dt><dd><?= $e($pvz['w']) ?></dd><?php endif ?>
          <?php if ($pvz['m'] !== ''): ?><dt>Метро</dt><dd><?= $e($pvz['m']) ?></dd><?php endif ?>
          <?php if ($pvz['n'] !== ''): ?><dt>Как найти</dt><dd><?= $e($pvz['n']) ?></dd><?php endif ?>
          <?php if ($pvz['card'] || $pvz['cash']): ?><dt>Оплата в пункте</dt><dd class="pvzv__pay"><?= $pvz['card'] ? '<span>Карта</span>' : '' ?><?= $pvz['cash'] ? '<span>Наличные</span>' : '' ?></dd><?php endif ?>
        </dl>
        <?php if ($pvz['img']): ?><div class="pvzm__img"><?php foreach ($pvz['img'] as $u): ?><img src="<?= $e($u) ?>" alt="" loading="lazy"><?php endforeach ?></div><?php endif ?>
      </div>
      <?php if ($pvz['lat'] && $pvz['lon']): ?><div class="pvzv__map" id="pvzVMap" data-lat="<?= $pvz['lat'] ?>" data-lon="<?= $pvz['lon'] ?>"></div><?php endif ?>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const m=document.getElementById('pvzView'); if(!m) return;
  document.body.appendChild(m);
  let map=null;
  document.addEventListener('click',e=>{
    if(e.target.closest('[data-pvzv]')){ m.classList.add('open');
      const box=document.getElementById('pvzVMap'); if(!box) return;
      if(map){ map.container.fitToViewport(); return; }
      const c=[+box.dataset.lat,+box.dataset.lon];
      BT_ymaps().then(y=>{ map=new y.Map(box,{center:c,zoom:16,controls:['zoomControl']},{suppressMapOpenBlock:true,yandexMapDisablePoiInteractivity:true});
        map.geoObjects.add(new y.Placemark(c,{},BT_mapPin(true))); }).catch(()=>{ box.textContent='Карта не загрузилась'; });
    }
    if(e.target.closest('#pvzView [data-close]')) m.classList.remove('open');
  });
});
</script>
<?php endif ?>
<?php bt_acc_end();
