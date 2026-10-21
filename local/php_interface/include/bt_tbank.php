<?php
// Оплата картой и по СБП через модуль Т-Банка (tinkoff.payment): ссылка на оплату, QR для СБП, сверка статуса с банком, агент сверки

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

// заказ того, кто оформил его в этой сессии, или его владельца
function bt_tbank_order(int $id): ?Sale\Order
{
    global $USER;
    $order = $id > 0 ? Sale\Order::load($id) : null;
    return $order && (in_array($id, (array)($_SESSION['BT_ORDERS'] ?? []), true)
        || (is_object($USER) && $USER->IsAuthorized() && (int)$order->getUserId() === (int)$USER->GetID())) ? $order : null;
}

// оплата по СБП — отдельная платёжная система того же терминала (XML_ID bt_sbp): вместо страницы банка свой экран с QR
function bt_tbank_is_sbp(Sale\Payment $p): bool
{
    return (Sale\PaySystem\Manager::getById($p->getPaymentSystemId())['XML_ID'] ?? '') === 'bt_sbp';
}

// QR для СБП: ссылка в приложение банка и картинка. Платёж в банке тот же, что у страницы банка, — с неё можно заплатить и картой
function bt_tbank_qr(Sale\Payment $p): array
{
    $orderId = (int)$p->getOrderId();
    if (bt_tbank_url($p) === '') {
        return [];
    }
    // повторная попытка могла заменить платёж новым — берём актуальный
    $order = Sale\Order::load($orderId);
    $p = $order ? bt_tbank_payment($order) : null;
    $pid = $p ? (string)$p->getField('PS_INVOICE_ID') : '';
    if ($pid === '') {
        return [];
    }
    $link = (string)(bt_tbank_api($p, 'GetQr', ['PaymentId' => $pid, 'DataType' => 'PAYLOAD'])['Data'] ?? '');
    $svg = (string)(bt_tbank_api($p, 'GetQr', ['PaymentId' => $pid, 'DataType' => 'IMAGE'])['Data'] ?? '');
    return str_starts_with($link, 'https://') && str_starts_with($svg, '<svg') ? ['link' => $link, 'svg' => $svg, 'sum' => $p->getSum()] : [];
}

// экран СБП ждёт деньги: paid — оплачено, dead — по этому QR уже не заплатить (отменён, отклонён, истёк), wait — ждём
function bt_tbank_sbp_state(Sale\Payment $p): string
{
    $pid = (string)$p->getField('PS_INVOICE_ID');
    if ($pid === '') {
        return 'dead';
    }
    $st = (string)(bt_tbank_api($p, 'GetState', ['PaymentId' => $pid])['Status'] ?? '');
    if ($st === 'CONFIRMED') {
        return bt_tbank_sync($p) ? 'paid' : 'wait';
    }
    return in_array($st, ['CANCELED', 'REJECTED', 'DEADLINE_EXPIRED', 'AUTH_FAIL', 'REVERSED', 'REFUNDED', 'PARTIAL_REFUNDED'], true) ? 'dead' : 'wait';
}

// «Оплатить картой» с экрана СБП: платёж — на карточную систему; платёж в банке остаётся тем же, его страница принимает карты
function bt_tbank_to_card(Sale\Payment $p): void
{
    foreach (Sale\PaySystem\Manager::getList(['filter' => ['=ACTION_FILE' => 'tinkoff', '=ACTIVE' => 'Y'], 'order' => ['SORT' => 'ASC'], 'select' => ['ID', 'NAME', 'XML_ID']]) as $ps) {
        if ($ps['XML_ID'] !== 'bt_sbp') {
            $p->setFields(['PAY_SYSTEM_ID' => (int)$ps['ID'], 'PAY_SYSTEM_NAME' => $ps['NAME']]);
            $p->getOrder()->save();
            return;
        }
    }
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
    // чек включён — без состава чека платёж не создаём: оплата без чека нарушает 54-ФЗ
    $receipt = (string)($param['ENABLE_TAXATION'] ?? '0') === '1' ? bt_tbank_receipt($p, $param) : null;
    if ((string)($param['ENABLE_TAXATION'] ?? '0') === '1' && !$receipt) {
        return '';
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
    ] + ($receipt ? ['Receipt' => $receipt] : []));
    if (empty($r['Success']) || !str_starts_with((string)($r['PaymentURL'] ?? ''), 'https://')) {
        // номер уже занят прошлой попыткой (банк не даёт его повторить даже после отмены) — платим новым платежом заказа
        return ($r['ErrorCode'] ?? '') === '8' && !$renewed && ($n = bt_tbank_renew($p)) ? bt_tbank_url($n, true) : '';
    }
    $p->setField('PS_INVOICE_ID', (string)($r['PaymentId'] ?? ''));
    $p->setField('PS_STATUS_DESCRIPTION', mb_substr((string)$r['PaymentURL'], 0, 250));
    $order->save();
    return (string)$r['PaymentURL'];
}

// состав чека из заказа: товары по ценам со скидкой и доставка; признаки расчёта и налоги — из настроек платёжной системы
function bt_tbank_receipt(Sale\Payment $p, array $param): ?array
{
    $order = $p->getOrder();
    $props = $order->getPropertyCollection();
    $email = trim((string)($props->getUserEmail() ? $props->getUserEmail()->getValue() : ''));
    $phone = preg_replace('/\D/', '', (string)($props->getPhone() ? $props->getPhone()->getValue() : ''));
    $def = (string)($param['DELIVERY_TAXATION'] ?? '') ?: 'vat5';
    // НДС товара — из каталога; не задан — как у доставки (у клиента НДС 5 % на всё)
    $vat = fn($rate) => $rate === null || $rate === '' ? $def
        : (['0' => 'vat0', '5' => 'vat5', '7' => 'vat7', '10' => 'vat10', '20' => 'vat20', '22' => 'vat22'][(string)round((float)$rate * 100)] ?? $def);
    $ffd12 = ($param['FFD'] ?? '') === 'Y';
    $line = fn(string $name, int $price, float $q, string $tax, string $obj, string $unit) => ['Name' => mb_substr($name, 0, 128), 'Price' => $price,
        'Quantity' => $q, 'Amount' => (int)round($price * $q), 'Tax' => $tax, 'PaymentMethod' => (string)($param['PAYMENT_METHOD'] ?? '') ?: 'full_prepayment',
        'PaymentObject' => $obj] + ($ffd12 ? ['MeasurementUnit' => $unit] : []);
    $items = [];
    foreach ($order->getBasket()->getOrderableItems() as $bi) {
        $items[] = $line((string)$bi->getField('NAME'), (int)round($bi->getPrice() * 100), (float)$bi->getQuantity(), $vat($bi->getField('VAT_RATE')),
            (string)($param['PAYMENT_OBJECT'] ?? '') ?: 'commodity', bt_basket_pack($bi) ? 'кг' : 'шт');
    }
    if (($d = (int)round($order->getDeliveryPrice() * 100)) > 0) {
        $items[] = $line('Доставка', $d, 1, $def, 'service', 'шт');
    }
    if (!$items || ($email === '' && strlen($phone) !== 11)) {
        return null;
    }
    // копейки округления — на последнюю позицию в одну штуку: банк принимает чек, только если сумма позиций равна сумме платежа
    if ($diff = (int)round($p->getSum() * 100) - array_sum(array_column($items, 'Amount'))) {
        $k = array_key_last(array_filter($items, fn($x) => $x['Quantity'] == 1));
        if ($k === null || abs($diff) > 100) {
            return null;
        }
        $items[$k]['Price'] += $diff;
        $items[$k]['Amount'] += $diff;
    }
    return array_filter(['Email' => $email, 'Phone' => strlen($phone) === 11 ? '+' . $phone : '', 'Taxation' => (string)($param['TAXATION'] ?? ''),
        'FfdVersion' => $ffd12 ? '1.2' : '', 'Items' => $items]);
}

// уведомление банка о пробитом чеке: подпись проверяем паролем терминала, ссылку на чек — в свойство заказа RECEIPT_URL («Документы» в кабинете)
function bt_tbank_receipt_save(array $req): void
{
    $acc = (string)($req['OrderId'] ?? '');
    $order = ($i = strrpos($acc, '/')) ? Sale\Order::loadByAccountNumber(substr($acc, 0, $i)) : null;
    $pay = null;
    foreach ($order ? $order->getPaymentCollection() : [] as $x) {
        $x->getField('ACCOUNT_NUMBER') === $acc && $pay = $x;
    }
    $pwd = $pay && ($ps = $pay->getPaySystem()) ? (string)($ps->getParamsBusValue($pay)['SHOP_SECRET_WORD'] ?? '') : '';
    $flat = array_filter($req, fn($v, $k) => $k !== 'Token' && !is_array($v), ARRAY_FILTER_USE_BOTH) + ['Password' => $pwd];
    ksort($flat);
    $sign = hash('sha256', implode('', array_map(fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : (string)$v, $flat)));
    // чек возврата в кабинет не выводим — там ссылка на чек оплаты
    if ($pwd === '' || !hash_equals($sign, (string)($req['Token'] ?? '')) || ($req['Type'] ?? 'Income') !== 'Income') {
        return;
    }
    $url = (string)($req['Url'] ?? '');
    if (!str_starts_with($url, 'https://') && ($req['FnNumber'] ?? '') !== '' && ($req['FiscalDocumentAttribute'] ?? '') !== '') {
        // чек в «Платформе ОФД» (ОФД кассы Т-Банка): форма проверки с подставленными номерами
        $url = 'https://lk.platformaofd.ru/web/noauth/cheque?' . http_build_query(['fn' => $req['FnNumber'], 'fp' => $req['FiscalDocumentAttribute'], 'i' => $req['FiscalDocumentNumber'] ?? '']);
    }
    $prop = str_starts_with($url, 'https://') ? $order->getPropertyCollection()->getItemByOrderPropertyCode('RECEIPT_URL') : null;
    if ($prop) {
        $prop->setValue(mb_substr($url, 0, 500));
        $order->save();
    }
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
    // два сохранения: при удалении старого в том же сохранении ядро выдало бы новому занятый номер и оставило его пустым
    $n = $order->getPaymentCollection()->createItem($service);
    $n->setField('SUM', $p->getSum());
    if (!$order->save()->isSuccess() || (string)$n->getField('ACCOUNT_NUMBER') === '') {
        return null;
    }
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
