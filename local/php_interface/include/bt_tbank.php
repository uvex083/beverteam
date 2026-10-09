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
            if (!$p->getOrder()->save()->isSuccess() || !$p->isPaid()) {
                return false;
            }
            bt_tbank_mark((string)$p->getField('ACCOUNT_NUMBER'), (string)($x['PaymentId'] ?? ''));
            return true;
        }
    }
    return false;
}

// платёж подтверждён — то же в таблицу модуля: его запоздалое уведомление AUTHORIZED иначе снимет оплату, а возврат (REFUNDED) не найдёт платёж
function bt_tbank_mark(string $acc, string $paymentId): void
{
    if ($paymentId === '') {
        return;
    }
    $c = \Bitrix\Main\Application::getConnection();
    $h = $c->getSqlHelper();
    $c->queryExecute("INSERT INTO tb_payment_status (OrderId, PaymentId, current_status) VALUES ('" . $h->forSql($acc) . "', '" . $h->forSql($paymentId) . "', 'CONFIRMED')"
        . " ON DUPLICATE KEY UPDATE current_status = 'CONFIRMED'");
}

// ссылка на страницу оплаты банка: прошлая, пока банк держит её открытой, иначе новый платёж — только по действию покупателя
function bt_tbank_url(Sale\Payment $p, bool $renewed = false): string
{
    $param = ($ps = $p->getPaySystem()) ? $ps->getParamsBusValue($p) : [];
    // с чеком платёж создаёт сам модуль (он собирает позиции чека), адреса возврата тогда — из настроек терминала в кабинете банка
    if ((string)($param['ENABLE_TAXATION'] ?? '0') === '1') {
        $r = $ps->initiatePay($p, null, Sale\PaySystem\BaseServiceHandler::STRING);
        return $r->isSuccess() && preg_match('~action="(https://[^"]+)"~i', (string)$r->getTemplate(), $m) ? htmlspecialchars_decode($m[1]) : '';
    }
    // две вкладки или «Назад» не должны плодить платежи: прошлая ссылка жива — отдаём её
    $amount = (int)round($p->getSum() * 100);
    $prevId = (string)$p->getField('PS_INVOICE_ID');
    $prevUrl = (string)$p->getField('PS_STATUS_DESCRIPTION');
    if ($prevId !== '' && str_starts_with($prevUrl, 'https://')) {
        $st = bt_tbank_api($p, 'GetState', ['PaymentId' => $prevId]);
        if (in_array($st['Status'] ?? '', ['NEW', 'FORM_SHOWED'], true) && (int)($st['Amount'] ?? 0) === $amount) {
            return $prevUrl;
        }
    }
    // модуль не передаёт банку адреса возврата — без них кнопка «В магазин» у банка никуда не ведёт
    $order = $p->getOrder();
    $props = $order->getPropertyCollection();
    $host = 'https://' . \Bitrix\Main\Context::getCurrent()->getRequest()->getHttpHost();
    $back = $host . '/personal/order/success/?id=' . (int)$order->getId();
    $r = bt_tbank_api($p, 'Init', [
        'Amount' => $amount,
        'OrderId' => (string)$p->getField('ACCOUNT_NUMBER'),
        'Description' => 'Заказ № ' . $order->getField('ACCOUNT_NUMBER') . ' в BEVERTEAM',
        'SuccessURL' => $back,
        'FailURL' => $back . '&pay=fail',
        'NotificationURL' => $host . '/personal/order/notification.php',
        'DATA' => array_filter(['Email' => (string)($props->getUserEmail() ? $props->getUserEmail()->getValue() : ''), 'Phone' => (string)($props->getPhone() ? $props->getPhone()->getValue() : '')]),
    ]);
    if (empty($r['Success']) || !str_starts_with((string)($r['PaymentURL'] ?? ''), 'https://')) {
        // номер уже занят прошлой попыткой (банк не даёт его повторить даже после отмены) — платим новым платежом заказа
        return ($r['ErrorCode'] ?? '') === '8' && !$renewed && ($n = bt_tbank_renew($p)) ? bt_tbank_url($n, true) : '';
    }
    $p->setField('PS_INVOICE_ID', (string)($r['PaymentId'] ?? ''));
    $p->setField('PS_STATUS_DESCRIPTION', mb_substr((string)$r['PaymentURL'], 0, 250));
    $order->save();
    return (string)$r['PaymentURL'];
}

// заменить неоплаченный платёж новым (новый номер для банка); прошлые попытки в банке сначала отменяем, чтобы по старой ссылке не заплатили
function bt_tbank_renew(Sale\Payment $p): ?Sale\Payment
{
    $final = ['CONFIRMED', 'CANCELED', 'REVERSED', 'REFUNDED', 'PARTIAL_REFUNDED', 'REJECTED', 'DEADLINE_EXPIRED'];
    foreach ((array)(bt_tbank_api($p, 'CheckOrder', ['OrderId' => (string)$p->getField('ACCOUNT_NUMBER')])['Payments'] ?? []) as $x) {
        if (($x['Status'] ?? '') === 'CONFIRMED' || (!in_array($x['Status'] ?? '', $final, true) && empty(bt_tbank_api($p, 'Cancel', ['PaymentId' => (string)$x['PaymentId']])['Success']))) {
            return null;
        }
    }
    $order = $p->getOrder();
    $service = Sale\PaySystem\Manager::getObjectById($p->getPaymentSystemId());
    if (!$service) {
        return null;
    }
    $n = $order->getPaymentCollection()->createItem($service);
    $n->setField('SUM', $p->getSum());
    $p->delete();
    return $order->save()->isSuccess() ? $n : null;
}

// sale:OnSaleOrderBeforeSaved — уведомление модуля пишет статус платежа в комментарий заказа поверх заметки менеджера: заметку сохраняем, статус — строкой ниже
function bt_tbank_keep_comments(\Bitrix\Main\Event $e): void
{
    $order = $e->getParameter('ENTITY');
    $rx = '/^(AUTHORIZED|CONFIRMED|REJECTED|CANCELED|REVERSED|REFUNDED): .*$/mu';
    $new = (string)$order->getField('COMMENTS');
    if (!$order instanceof Sale\Order || !preg_match($rx, $new) || str_contains($new, "\n")) {
        return;
    }
    $keep = trim(preg_replace($rx, '', (string)($order->getFields()->getOriginalValues()['COMMENTS'] ?? '')));
    $keep !== '' && $order->setField('COMMENTS', $keep . "\n" . $new);
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
