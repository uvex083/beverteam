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
    $service = Sale\PaySystem\Manager::getObjectById($p->getPaymentSystemId());
    if (!$service) {
        return '';
    }
    $r = $service->initiatePay($p, null, Sale\PaySystem\BaseServiceHandler::STRING);
    return $r->isSuccess() && preg_match('~action="(https://[^"]+)"~i', (string)$r->getTemplate(), $m) ? htmlspecialchars_decode($m[1]) : '';
}

// агент: неоплаченные заказы с оплатой через Т-Банк за трое суток — сверка с банком
function bt_tbank_agent(): string
{
    if (Loader::includeModule('sale')) {
        $ids = array_column(Sale\PaySystem\Manager::getList(['filter' => ['=ACTION_FILE' => 'tinkoff'], 'select' => ['ID']])->fetchAll(), 'ID');
        if ($ids) {
            $r = Sale\Payment::getList(['filter' => ['@PAY_SYSTEM_ID' => $ids, '=PAID' => 'N', '>=DATE_BILL' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time() - 3 * 86400)], 'select' => ['ID', 'ORDER_ID']]);
            while ($row = $r->fetch()) {
                $order = Sale\Order::load($row['ORDER_ID']);
                $p = $order && !$order->isCanceled() ? $order->getPaymentCollection()->getItemById($row['ID']) : null;
                $p && bt_tbank_sync($p);
            }
        }
    }
    return 'bt_tbank_agent();';
}
