<?php
// оплата по СБП: на компьютере QR-код, на телефоне кнопка приложения банка; оплату страница ждёт сама (ui.js, .sbp)
\Bitrix\Main\Loader::includeModule('sale');
$e = fn($s) => htmlspecialcharsbx((string)$s);
$id = (int)($_GET['id'] ?? 0);
$order = bt_tbank_order($id);
$p = $order && !$order->isCanceled() ? bt_tbank_payment($order) : null;
if (!$p || bt_tbank_sync($p)) {
    LocalRedirect('/personal/order/success/?id=' . $id);
}
if (!bt_tbank_is_sbp($p)) {
    LocalRedirect('/personal/order/pay/?id=' . $id);
}
$qr = bt_tbank_qr($p);
?>
<div class="wrap okp">
  <div class="ok sbp"<?= $qr ? ' data-id="' . $id . '" data-done="/personal/order/success/?id=' . $id . '"' : '' ?>>
    <div class="ok__hd">
      <h1 class="display h1">Оплата по СБП</h1>
      <span class="num">Заказ № <?= $e($order->getField('ACCOUNT_NUMBER')) ?></span>
    </div>
    <div class="sbp__card">
    <?php if ($qr): ?>
      <div class="sbp__qr"><div class="sbp__vf"><img src="data:image/svg+xml;base64,<?= base64_encode($qr['svg']) ?>" alt="QR-код для оплаты по СБП" width="248" height="248"></div></div>
      <div class="sbp__info">
        <b class="sbp__sum"><?= bt_fmt($qr['sum']) ?></b>
        <span class="sbp__note">Без ввода карты и без комиссии</span>
        <a class="btn btn--block sbp__app" href="<?= $e($qr['link']) ?>">Открыть приложение банка</a>
        <ol class="sbp__steps sbp__d"><li>Откройте камеру или приложение своего банка</li><li>Наведите на QR-код</li><li>Подтвердите оплату в приложении</li></ol>
        <ol class="sbp__steps sbp__m"><li>Нажмите «Открыть приложение банка»</li><li>Выберите свой банк</li><li>Подтвердите оплату и вернитесь сюда</li></ol>
        <p class="sbp__st" role="status" aria-live="polite">Ждём оплату, страница обновится сама</p>
        <button type="button" class="sbp__show">Показать QR-код для другого телефона</button>
      </div>
    <?php else: ?>
      <div class="sbp__fail">
        <b>Банк не выдал QR-код</b>
        <span>Попробуйте ещё раз через минуту или оплатите картой.</span>
        <a class="btn" href="/personal/order/sbp/?id=<?= $id ?>">Попробовать ещё раз</a>
      </div>
    <?php endif ?>
    </div>
    <div class="sbp__alt">
      <a class="btn btn--line" href="/personal/order/pay/?id=<?= $id ?>&amp;card=1">Оплатить картой</a>
      <a class="link" href="/personal/order/success/?id=<?= $id ?>">Вернуться к заказу</a>
    </div>
  </div>
</div>
