<?php
// уведомление Т-Банка. Сайт мог отметить оплату сам (покупатель вернулся раньше уведомления): тогда запоздалые AUTHORIZED/CONFIRMED модулю не отдаём — он снял бы оплату
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
$req = json_decode((string)file_get_contents('php://input'), true);
// уведомление о пробитом чеке — ссылку на чек в заказ; модулю не отдаём, он принял бы его за статус оплаты
if (is_array($req) && isset($req['FiscalDocumentNumber']) && \Bitrix\Main\Loader::includeModule('sale')) {
    bt_tbank_receipt_save($req);
    die('OK');
}
// Платёж заменили новым (повторная оплата) — уведомления по старому номеру тоже не отдаём: на CANCELED модуль отменил бы весь заказ
if (($acc = (string)($req['OrderId'] ?? '')) !== '' && \Bitrix\Main\Loader::includeModule('sale')) {
    $order = ($i = strrpos($acc, '/')) ? \Bitrix\Sale\Order::loadByAccountNumber(substr($acc, 0, $i)) : null;
    $pay = null;
    foreach ($order ? $order->getPaymentCollection() : [] as $p) {
        $p->getField('ACCOUNT_NUMBER') === $acc && $pay = $p;
    }
    if ($order && !$pay) {
        die('OK');
    }
    if ($pay && $pay->isPaid() && in_array($req['Status'] ?? '', ['AUTHORIZED', 'CONFIRMED'], true)) {
        bt_tbank_mark($acc, (string)($req['PaymentId'] ?? ''));
        die('OK');
    }
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/tinkoff/notification.php';
