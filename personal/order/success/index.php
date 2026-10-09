<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Sale;

$APPLICATION->SetTitle('Заказ принят');
$APPLICATION->SetPageProperty('title', 'Заказ принят — BEVERTEAM');
$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
Loader::includeModule('sale');
$e = fn($s) => htmlspecialcharsbx((string)$s);

// заказ показываем только тому, кто его оформил в этой сессии, или его владельцу
$id = (int)($_GET['id'] ?? 0);
$order = null;
if ($id && (in_array($id, (array)($_SESSION['BT_ORDERS'] ?? []), true) || $USER->IsAuthorized())) {
    $order = Sale\Order::load($id);
    if ($order && !in_array($id, (array)($_SESSION['BT_ORDERS'] ?? []), true) && (int)$order->getUserId() !== (int)$USER->GetID()) {
        $order = null;
    }
}
$co = bt_contacts();
if (!$order) {
    CHTTP::SetStatus("404 Not Found");
}
?>
<div class="wrap okp">
<?php if (!$order): ?>
  <div class="ok"><div class="ok__hd">
    <h1 class="display h1">Заказ не найден</h1>
    <p>Страница заказа доступна только в том браузере, где его оформляли. Статус заказа можно узнать у менеджера по телефону <a class="link" href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a>.</p>
    <div class="row" style="justify-content:center;margin-top:28px"><a class="btn" href="/catalog/">В каталог</a></div>
  </div></div>
<?php else:
    $props = [];
    foreach ($order->getPropertyCollection() as $p) {
        $props[$p->getField('CODE')] = $p->getValue();
    }
    $shipment = null;
    foreach ($order->getShipmentCollection() as $s) {
        if (!$s->isSystem()) {
            $shipment = $s;
        }
    }
    $payment = null;
    foreach ($order->getPaymentCollection() as $p) {
        $payment = $p;
        break;
    }
    if ($payment && (Sale\PaySystem\Manager::getById($payment->getPaymentSystemId())['ACTION_FILE'] ?? '') === 'tinkoff' && !$payment->isPaid()) {
        bt_tbank_sync($payment);
    }
    $dCode = $shipment ? (string)(Sale\Delivery\Services\Table::getById($shipment->getDeliveryId())->fetch()['XML_ID'] ?? '') : '';
    $pCode = $payment ? (string)(Sale\PaySystem\Manager::getById($payment->getPaymentSystemId())['ACTION_FILE'] ?? '') : '';
    $city = '';
    if (!empty($props['LOCATION'])) {
        $city = (string)(Sale\Location\LocationTable::getList(['filter' => ['=CODE' => $props['LOCATION'], '=NAME.LANGUAGE_ID' => 'ru'], 'select' => ['N' => 'NAME.NAME']])->fetch()['N'] ?? '');
    }
    $name = $props['FIO'] ?? $props['CONTACT_PERSON'] ?? '';
    $phone = preg_replace('/\D/', '', (string)($props['PHONE'] ?? ''));
    $phoneMask = strlen($phone) === 11 ? '+7 ' . $phone[1] . '•• •••-••-' . substr($phone, 9) : '';
    $dPrice = (float)$order->getDeliveryPrice();
    $delivText = $shipment ? $shipment->getDeliveryName() . ($city ? ', ' . $city : '') . ' — '
        . ($dCode === 'bt_cdek' ? 'стоимость сообщит менеджер' : ($dPrice > 0 ? bt_fmt($dPrice) : ($dCode === 'bt_courier' ? 'бесплатно (заказ от 3 000 ₽)' : 'бесплатно'))) : '';
    $where = preg_replace('/\s*#S\S+$/u', '', $props['ADDRESS'] ?? '') ?: ($props['PVZ'] ?? '' ?: ($dCode === 'bt_pickup' ? ($co['city'] ?? '') . ', ' . ($co['street'] ?? '') : ''));
    $date = $order->getDateInsert();
?>
  <div class="steps" style="max-width:760px;margin:24px auto 0"><div class="done"><b>1</b>Корзина</div><div class="done"><b>2</b>Регион и доставка</div><div class="done"><b>3</b>Оплата</div><div class="cur"><b>4</b>Подтверждение</div></div>
  <div class="ok">
    <div class="ok__hd">
      <div class="ic">✓</div>
      <h1 class="display h1">Заказ принят</h1>
      <p>Спасибо<?= $name ? ', ' . $e($name) : '' ?>. Подтверждение отправили на <?= $e($props['EMAIL'] ?? 'почту') ?>. Менеджер позвонит в рабочее время, если потребуется уточнить детали.</p>
      <span class="num">Заказ № <?= $e($order->getField('ACCOUNT_NUMBER')) ?> · <?= $e(FormatDate('j F Y, H:i', $date->getTimestamp())) ?></span>
    </div>

    <div class="payblk"><div><small>Способ оплаты: <?php $pn = $payment ? $payment->getPaymentSystemName() : ''; echo $e(mb_strtolower(mb_substr($pn, 0, 1)) . mb_substr($pn, 1)) ?></small>
      <b><?= bt_fmt($order->getPrice()) ?></b>
      <small><?= $pCode === 'bill' ? 'счёт пришлём на e‑mail после подтверждения заказа менеджером' : ($pCode === 'tinkoff' ? ($order->isPaid() ? 'оплачено, спасибо' : 'заказ ждёт оплаты') : 'оплата при получении заказа') ?></small></div>
      <?php if ($pCode === 'tinkoff' && !$order->isPaid() && ($_GET['pay'] ?? '') === 'fail'): ?><small style="flex-basis:100%;color:#fff">Оплата не прошла. Попробуйте ещё раз или позвоните нам — поможем.</small><?php endif ?>
      <?php if ($pCode === 'tinkoff' && !$order->isPaid() && !$order->isCanceled()): ?><a class="btn" href="/personal/order/pay/?id=<?= (int)$order->getId() ?>">Оплатить <?= bt_fmt($order->getPrice()) ?></a><?php endif ?></div>

    <div class="card">
      <h3 class="h3" style="margin-bottom:12px">Состав заказа</h3>
      <div class="oitems">
        <?php foreach ($order->getBasket() as $bi):
            $m = bt_product((string)$bi->getProductId());
            $tag = !empty($m['url']) ? 'a' : 'div'; ?>
        <<?= $tag ?> class="oit"<?= $tag === 'a' ? ' href="' . $e($m['url']) . '"' : '' ?>>
          <span class="oit__img"><?php if (!empty($m['img'])): ?><img src="<?= $e($m['img']) ?>" alt="" loading="lazy" width="56" height="56"><?php endif ?></span>
          <span class="oit__n"><?= $e($bi->getField('NAME')) ?><small><?= bt_basket_qty($bi) ?> · <?= bt_fmt($bi->getPrice()) ?><?= bt_basket_pack($bi) ? ' за кг' : '' ?></small></span>
          <b><?= bt_fmt($bi->getFinalPrice()) ?></b>
        </<?= $tag ?>>
        <?php endforeach ?>
      </div>
      <div class="otot">
        <div><span>Товары</span><span><?= bt_fmt($order->getBasket()->getPrice()) ?></span></div>
        <div><span>Доставка</span><span><?= $dCode === 'bt_cdek' ? 'сообщит менеджер' : ($dPrice > 0 ? bt_fmt($dPrice) : 'бесплатно') ?></span></div>
        <div class="t"><span>Итого</span><span><?= bt_fmt($order->getPrice()) ?></span></div>
      </div>
      <dl class="dl">
        <dt>Доставка</dt><dd><?= $e($delivText) ?></dd>
        <?php if ($where): ?><dt><?= $dCode === 'bt_pickup' ? 'Самовывоз' : ($props['PVZ'] ?? '' ? 'Пункт выдачи' : 'Адрес') ?></dt><dd><?= $e(($city && $dCode !== 'bt_pickup' ? $city . ', ' : '') . $where) ?></dd><?php endif ?>
        <?php if (!empty($props['COMPANY'])): ?><dt>Покупатель</dt><dd><?= $e($props['COMPANY']) ?>, ИНН <?= $e($props['INN'] ?? '') ?></dd><?php endif ?>
        <dt>Получатель</dt><dd><?= $e(trim($name . ($phoneMask ? ', ' . $phoneMask : ''))) ?></dd>
        <?php if ($order->getField('USER_DESCRIPTION')): ?><dt>Комментарий</dt><dd><?= $e($order->getField('USER_DESCRIPTION')) ?></dd><?php endif ?>
      </dl>
    </div>

    <?php if (!$USER->IsAuthorized() && !empty($props['EMAIL'])): ?>
    <div class="alert alert--info oklogin"><b>Кабинет покупателя</b><span>История заказов, адреса и реквизиты для следующих покупок. Пароль не нужен — пришлём код на <?= $e($props['EMAIL']) ?>.</span>
      <button type="button" class="btn btn--dark" onclick="BT_auth(<?= $e(Json::encode($props['EMAIL'])) ?>)">Войти в кабинет</button></div>
    <?php endif ?>
    <div class="next">
      <div><b>Отследить заказ</b><span>Статус и трек-номер сообщит менеджер, история заказов — в <a class="link" href="/personal/">личном кабинете</a></span></div>
      <div><b>Изменить заказ</b><span>Позвоните <a class="link" href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a> до отправки</span></div>
      <div><b>Нужен документ?</b><span><?= $pCode === 'bill' ? 'Счёт и УПД пришлём на e‑mail' : ($pCode === 'tinkoff' ? 'УПД — по запросу' : 'Чек выдадим при получении, УПД — по запросу') ?></span></div>
    </div>
    <div class="row" style="justify-content:center;margin-top:28px;gap:12px"><a class="btn btn--line" href="/catalog/">Продолжить покупки</a></div>
  </div>
<?php endif ?>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
