<?php
// Оплата картой/СБП через модуль Т-Банка (tinkoff.payment): ссылка на оплату, сверка статуса с банком, агент сверки

use Bitrix\Main\Loader;
use Bitrix\Sale;

// неоплаченный платёж заказа через Т-Банк
function bt_tbank_payment(Sale\Order $order): ?Sale\Payment
{
    foreach ($order->getPaymentCollection() as $p) {
        if (!$p->isPaid() && !$p->isInner() && (Sale\PaySystem\Manager::getById($p->getPaymentSystemId())['ACTION_FILE'] ?? '') === 'tinkoff') {
            return $p;
        }
    }
    return null;
}

// запрос к API банка с ключами терминала из настроек платёжной системы
function bt_tbank_api(Sale\Payment $p, string $method, array $args): array
{
    $ps = $p->getPaySystem();
    if (!$ps) {
        return [];
    }
    $param = $ps->getParamsBusValue($p);
    if (empty($param['TERMINAL_ID']) || empty($param['SHOP_SECRET_WORD'])) {
        return [];
    }
    include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/tinkoff/sdk/tinkoff_autoload.php';
    try {
        $api = new \TBank\TBankMerchantAPI($param['TERMINAL_ID'], $param['SHOP_SECRET_WORD']);
        return (array)json_decode((string)$api->buildQuery($method, $args), true);
    } catch (\Throwable $e) {
        return [];
    }
}

// уведомление банка могло не дойти — спрашиваем сами; оплачено — отмечаем платёж. true, если платёж оплачен
function bt_tbank_sync(Sale\Payment $p): bool
{
    if ($p->isPaid()) {
        return true;
    }
    $r = bt_tbank_api($p, 'CheckOrder', ['OrderId' => (string)$p->getField('ACCOUNT_NUMBER')]);
    $sum = (int)round($p->getSum() * 100);
    foreach ((array)($r['Payments'] ?? []) as $x) {
        if (($x['Status'] ?? '') === 'CONFIRMED' && (int)($x['Amount'] ?? 0) === $sum) {
            $p->setPaid('Y');
            $p->setField('PS_INVOICE_ID', (string)($x['PaymentId'] ?? ''));
            $p->getOrder()->save();
            return $p->isPaid();
        }
    }
    return false;
}

// ссылка на страницу оплаты банка; каждый вызов — новый платёж в банке, поэтому только по действию покупателя
function bt_tbank_url(Sale\Payment $p): string
{
    $param = ($ps = $p->getPaySystem()) ? $ps->getParamsBusValue($p) : [];
    // с чеком платёж создаёт сам модуль (он собирает позиции чека), адреса возврата тогда — из настроек терминала в кабинете банка
    if ((string)($param['ENABLE_TAXATION'] ?? '0') === '1') {
        $r = $ps->initiatePay($p, null, Sale\PaySystem\BaseServiceHandler::STRING);
        return $r->isSuccess() && preg_match('~action="(https://[^"]+)"~i', (string)$r->getTemplate(), $m) ? htmlspecialchars_decode($m[1]) : '';
    }
    // модуль не передаёт банку адреса возврата — без них кнопка «В магазин» у банка никуда не ведёт
    $order = $p->getOrder();
    $props = $order->getPropertyCollection();
    $host = 'https://' . \Bitrix\Main\Context::getCurrent()->getRequest()->getHttpHost();
    $back = $host . '/personal/order/success/?id=' . (int)$order->getId();
    $r = bt_tbank_api($p, 'Init', [
        'Amount' => (int)round($p->getSum() * 100),
        'OrderId' => (string)$p->getField('ACCOUNT_NUMBER'),
        'Description' => 'Заказ № ' . $order->getField('ACCOUNT_NUMBER') . ' в BEVERTEAM',
        'SuccessURL' => $back,
        'FailURL' => $back . '&pay=fail',
        'NotificationURL' => $host . '/personal/order/notification.php',
        'DATA' => array_filter(['Email' => (string)($props->getUserEmail() ? $props->getUserEmail()->getValue() : ''), 'Phone' => (string)($props->getPhone() ? $props->getPhone()->getValue() : '')]),
    ]);
    return !empty($r['Success']) && str_starts_with((string)($r['PaymentURL'] ?? ''), 'https://') ? (string)$r['PaymentURL'] : '';
}

// платёжные системы модуля Т-Банка
function bt_tbank_ps_ids(): array
{
    static $ids;
    return $ids ??= Loader::includeModule('sale') ? array_map('intval', array_column(Sale\PaySystem\Manager::getList(['filter' => ['=ACTION_FILE' => 'tinkoff'], 'select' => ['ID']])->fetchAll(), 'ID')) : [];
}

// неоплаченные платежи через Т-Банк за трое суток (всех или одного покупателя) — сверка с банком
function bt_tbank_sync_user(int $userId = 0): void
{
    if (!($ids = bt_tbank_ps_ids())) {
        return;
    }
    $f = ['@PAY_SYSTEM_ID' => $ids, '=PAID' => 'N', '>=DATE_BILL' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time() - 3 * 86400)];
    $userId && $f['=ORDER.USER_ID'] = $userId;
    $r = Sale\Payment::getList(['filter' => $f, 'select' => ['ID', 'ORDER_ID']]);
    while ($row = $r->fetch()) {
        $order = Sale\Order::load($row['ORDER_ID']);
        $p = $order && !$order->isCanceled() ? $order->getPaymentCollection()->getItemById($row['ID']) : null;
        $p && bt_tbank_sync($p);
    }
}

// агент: на случай, если уведомление банка не дошло
function bt_tbank_agent(): string
{
    bt_tbank_sync_user();
    return 'bt_tbank_agent();';
}

// main:OnBeforeEventAdd — оплату почти одновременно отмечают уведомление банка и сверка сайта: письмо «Заказ оплачен» — одно
function bt_tbank_paid_mail_once($event, $lid, $fields)
{
    $id = (int)($fields['ORDER_REAL_ID'] ?? 0);
    if ($event !== 'SALE_ORDER_PAID' || $id <= 0) {
        return true;
    }
    $r = \Bitrix\Main\Mail\Internal\EventTable::getList(['filter' => ['=EVENT_NAME' => $event, '>=DATE_INSERT' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time() - 600)], 'select' => ['C_FIELDS']]);
    while ($x = $r->fetch()) {
        if ((int)($x['C_FIELDS']['ORDER_REAL_ID'] ?? 0) === $id) {
            return false;
        }
    }
    return true;
}
