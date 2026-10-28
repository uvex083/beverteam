<?php
// оплату страница ждёт сама — ui.js (.sbp, .spay)
\Bitrix\Main\Loader::includeModule('sale');
$e = fn($s) => htmlspecialcharsbx((string)$s);
$id = (int)($_GET['id'] ?? 0);
$order = bt_tbank_order($id);
$p = $order && !$order->isCanceled() ? bt_tbank_payment($order) : null;
if (!$p || bt_tbank_sync($p)) {
    LocalRedirect('/personal/order/success/?id=' . $id);
}
if (bt_tbank_xml($p) !== 'bt_sberpay') {
    LocalRedirect('/personal/order/pay/?id=' . $id);
}
$mobile = bt_tbank_device()['Device'] === 'MobileWeb';
$sp = bt_tbank_sberpay($p, $mobile);
$phone = ($ph = $order->getPropertyCollection()->getPhone()) ? bt_phone_fmt((string)$ph->getValue()) : '';
?>
<div class="wrap okp">
  <div class="ok sbp spay"<?= $sp ? ' data-id="' . $id . '" data-pid="' . $e($sp['pid']) . '" data-done="/personal/order/success/?id=' . $id . '"' : '' ?>>
    <div class="ok__hd">
      <h1 class="display h1">Оплата через SberPay</h1>
      <span class="num">Заказ № <?= $e($order->getField('ACCOUNT_NUMBER')) ?></span>
    </div>
    <div class="sbp__card">
    <?php if ($sp): ?>
      <div class="spay__mark" aria-hidden="true"><?= bt_tbank_sberpay_mark('#fff', '#21A038') ?><b>SberPay</b></div>
      <div class="sbp__info">
        <b class="sbp__sum"><?= bt_fmt($sp['sum']) ?></b>
        <span class="sbp__note">Оплата в приложении СберБанк Онлайн, без ввода карты</span>
        <?php if ($mobile): ?>
        <a class="btn btn--block spay__app" href="<?= $e($sp['link']) ?>"><?= bt_tbank_sberpay_mark('#fff', '#21A038') ?>Оплатить в СберБанк Онлайн</a>
        <ol class="sbp__steps"><li>Нажмите «Оплатить в СберБанк Онлайн»</li><li>Подтвердите оплату в приложении</li><li>Вернитесь сюда — заказ откроется сам</li></ol>
        <?php else: ?>
        <ol class="sbp__steps"><li>Проверьте номер, к которому привязан СберБанк Онлайн</li><li>Нажмите «Отправить запрос в СберБанк Онлайн»</li><li>Подтвердите оплату в приложении на телефоне</li></ol>
        <?php endif ?>
        <form class="spay__form" novalidate<?= $mobile ? ' hidden' : '' ?>>
          <div class="field"><label for="spayPhone">Телефон в СберБанк Онлайн *</label><input id="spayPhone" name="phone" type="tel" autocomplete="tel" placeholder="+7 ___ ___-__-__" value="<?= $e($phone) ?>"></div>
          <button type="submit" class="btn btn--block">Отправить запрос в СберБанк Онлайн</button>
          <p class="spay__sent" role="status" aria-live="polite" hidden>Откройте СберБанк Онлайн на телефоне и подтвердите оплату</p>
        </form>
        <p class="sbp__st" role="status" aria-live="polite"><span>Ждём оплату, после неё сразу откроем заказ</span></p>
        <?php if ($mobile): ?><button type="button" class="spay__other">Оплатить с другого телефона</button><?php endif ?>
      </div>
    <?php else: ?>
      <div class="sbp__fail">
        <b>Банк не ответил</b>
        <span>Попробуйте ещё раз через минуту или оплатите картой.</span>
        <a class="btn" href="/personal/order/sberpay/?id=<?= $id ?>">Попробовать ещё раз</a>
      </div>
    <?php endif ?>
    </div>
    <div class="sbp__alt">
      <a class="btn btn--line" href="/personal/order/pay/?id=<?= $id ?>&amp;card=1">Оплатить картой</a>
      <a class="link" href="/personal/order/success/?id=<?= $id ?>">Вернуться к заказу</a>
    </div>
  </div>
</div>
