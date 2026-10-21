<?php
// Оплата картой и по СБП через модуль Т-Банка (tinkoff.payment): ссылка на оплату, QR для СБП, сверка статуса с банком, агент сверки

use Bitrix\Main\Loader;
use Bitrix\Sale;

// статусы платежа в банке, по которым уже не заплатить
const BT_TBANK_DEAD = ['CANCELED', 'REJECTED', 'DEADLINE_EXPIRED', 'AUTH_FAIL', 'REVERSED', 'REFUNDED', 'PARTIAL_REFUNDED'];

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
    return str_starts_with($link, 'https://') && preg_match('/^\s*(<\?xml[^>]*>\s*)?<svg/i', $svg)
        ? ['link' => $link, 'svg' => $svg, 'sum' => $p->getSum(), 'pid' => $pid] : [];
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
    return in_array($st, BT_TBANK_DEAD, true) ? 'dead' : 'wait';
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

// логотипы способа онлайн-оплаты для плитки на оформлении: карты (МИР, Visa, Mastercard) или СБП
function bt_tbank_logos(bool $sbp): string
{
    $l = $sbp
        ? ['<span class="paylogo paylogo--sbp"><svg viewBox="13 9 38 46" fill-rule="evenodd"><path fill="#575eaa" d="m14.091799,44.239796l5.36213,-9.45225l0,-5.77392l-5.37089,-9.47053l0.00876,24.6967z"/><path fill="#da195a" d="m39.729499,22.524216l-5.03061,3.04282l0,6.21422l15.32442,-9.26447l-10.29381,0.00743z"/><path fill="#fbb42d" d="m29.317269,10.020376l0,18.74245l5.38184,3.26189l-0.02817,-12.53628l-5.35367,-9.46806z"/><path fill="#f37632" d="m34.670879,19.488406l5.05878,3.03569l10.29381,-0.00744l-20.70626,-12.49631l5.35367,9.46806z"/><path fill="#5fba46" d="m29.318959,53.768056l5.37996,-9.47518l0,-6.08346l-5.38121,-3.20023l0.00125,18.75887z"/><path fill="#1289ca" d="m19.453859,29.013596l20.26331,12.26454l10.28567,-0.01209l-35.91986,-21.72298l5.37088,9.47053z"/><path fill="#0b8341" d="m39.717099,41.278166l-5.01808,3.01462l-5.37997,9.47518l20.68372,-12.50189l-10.28567,0.01209z"/><path fill="#90509f" d="m19.453859,34.787666l-5.36212,9.45226l15.26807,-9.23071l-5.13423,-3.10758l-4.77172,2.88603z"/></svg><b>СБП</b></span>']
        : ['<span class="paylogo"><svg viewBox="42 151 696 198"><defs><linearGradient id="btMir" x1="536" x2="738" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#00A0E5"/><stop offset="1" stop-color="#0077C3"/></linearGradient></defs><path fill="#37A72E" d="M544.1,240.5v108h60v-64h68c28.6-0.2,52.9-18.5,62.1-44H544.1z"/><path fill="url(#btMir)" d="M536.1,151.5c3.5,44.1,45.3,79,96.3,79c0.2,0,104.3,0,104.3,0 c0.8-4,1.2-8.2,1.2-12.5c0-36.6-29.5-66.2-66-66.5L536.1,151.5z"/><path fill="#37A72E" d="M447.3,229.4l0-0.1L447.3,229.4c0.7-1.2,1.8-1.9,3.2-1.9c2,0,3.5,1.6,3.6,3.5l0,0 v116.5h60v-196h-60c-7.6,0.3-16.2,5.8-19.4,12.7L387,266.6c-0.1,0.4-0.3,0.8-0.5,1.2l0,0l0,0c-0.7,1-1.9,1.7-3.3,1.7 c-2.2,0-4-1.8-4-4v-114h-60v196h60v0c7.5-0.4,15.9-5.9,19.1-12.7l49-105.1C447.2,229.6,447.3,229.5,447.3,229.4L447.3,229.4z"/><path fill="#37A72E" d="M223.3,232.8l-35.1,114.7H145L110,232.7c-0.3-1.8-1.9-3.2-3.9-3.2 c-2.2,0-3.9,1.8-3.9,3.9c0,0,0,0,0,0l0,114h-60v-196h51.5H109c11,0,22.6,8.6,25.8,19.1l29.2,95.5c1.5,4.8,3.8,4.7,5.3,0 l29.2-95.5c3.2-10.6,14.8-19.1,25.8-19.1h15.3h51.5v196h-60v-114c0,0,0,0,0-0.1c0-2.2-1.8-3.9-3.9-3.9 C225.2,229.5,223.6,230.9,223.3,232.8L223.3,232.8z"/></svg></span>',
            '<span class="paylogo"><svg viewBox="60 143 660 214"><path fill="#1434CB" d="M489.823 143.111C442.988 143.111 401.134 167.393 401.134 212.256C401.134 263.706 475.364 267.259 475.364 293.106C475.364 303.989 462.895 313.731 441.6 313.731C411.377 313.731 388.789 300.119 388.789 300.119L379.123 345.391C379.123 345.391 405.145 356.889 439.692 356.889C490.898 356.889 531.19 331.415 531.19 285.784C531.19 231.419 456.652 227.971 456.652 203.981C456.652 195.455 466.887 186.114 488.122 186.114C512.081 186.114 531.628 196.014 531.628 196.014L541.087 152.289C541.087 152.289 519.818 143.111 489.823 143.111ZM61.3294 146.411L60.1953 153.011C60.1953 153.011 79.8988 156.618 97.645 163.814C120.495 172.064 122.122 176.868 125.971 191.786L167.905 353.486H224.118L310.719 146.411H254.635L198.989 287.202L176.282 167.861C174.199 154.203 163.651 146.411 150.74 146.411H61.3294ZM333.271 146.411L289.275 353.486H342.756L386.598 146.411H333.271ZM631.554 146.411C618.658 146.411 611.825 153.318 606.811 165.386L528.458 353.486H584.542L595.393 322.136H663.72L670.318 353.486H719.805L676.633 146.411H631.554ZM638.848 202.356L655.473 280.061H610.935L638.848 202.356Z"/></svg></span>',
            '<span class="paylogo paylogo--mc"><svg viewBox="109 76 562 348"><path fill="#FF5A00" d="M465.738 113.525H313.812V386.475H465.738V113.525Z"/><path fill="#EB001B" d="M323.926 250C323.926 194.545 349.996 145.326 390 113.525C360.559 90.3769 323.42 76.3867 282.91 76.3867C186.945 76.3867 109.297 154.035 109.297 250C109.297 345.965 186.945 423.614 282.91 423.614C323.42 423.614 360.559 409.623 390 386.475C349.94 355.123 323.926 305.455 323.926 250Z"/><path fill="#F79E1B" d="M670.711 250C670.711 345.965 593.062 423.614 497.098 423.614C456.588 423.614 419.449 409.623 390.008 386.475C430.518 354.618 456.082 305.455 456.082 250C456.082 194.545 430.012 145.326 390.008 113.525C419.393 90.3769 456.532 76.3867 497.041 76.3867C593.062 76.3867 670.711 154.541 670.711 250Z"/></svg></span>'];
    return '<span class="paylogos" aria-hidden="true">' . implode('', $l) . '</span>';
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
        // платёж ещё проводится (покупатель подтверждает в приложении банка) — тоже не новый: новый отменил бы проводимый
        if (($st['Status'] ?? '') !== '' && !in_array($st['Status'], BT_TBANK_DEAD, true) && (int)($st['Amount'] ?? 0) === $amount) {
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
    foreach ((array)(bt_tbank_api($p, 'CheckOrder', ['OrderId' => (string)$p->getField('ACCOUNT_NUMBER')])['Payments'] ?? []) as $x) {
        if (in_array($x['Status'] ?? '', BT_TBANK_DEAD, true)) {
            continue;
        }
        // оплаченный или проводимый не трогаем; QR СБП банк отменить не даёт — тогда тоже без нового платежа
        if (!in_array($x['Status'] ?? '', ['NEW', 'FORM_SHOWED'], true) || empty(bt_tbank_api($p, 'Cancel', ['PaymentId' => (string)$x['PaymentId']])['Success'])) {
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

// «По СБП» работает на ключах и настройках «Картой онлайн»: копирует их агент и bt_tbank_setup.php. Возвращает коды, которые отличались
function bt_tbank_sbp_sync(bool $apply = true): array
{
    $card = $sbp = 0;
    foreach (Loader::includeModule('sale') ? Sale\PaySystem\Manager::getList(['filter' => ['=ACTION_FILE' => 'tinkoff'], 'order' => ['ID' => 'ASC'], 'select' => ['ID', 'XML_ID']])->fetchAll() : [] as $ps) {
        if ($ps['XML_ID'] === 'bt_sbp') {
            $sbp = $sbp ?: (int)$ps['ID'];
        } else {
            $card = $card ?: (int)$ps['ID'];
        }
    }
    if (!$card || !$sbp) {
        return [];
    }
    $pre = Sale\PaySystem\Service::PAY_SYSTEM_PREFIX;
    $key = fn($m) => $m['CODE_KEY'] . '|' . (int)$m['PERSON_TYPE_ID'];
    $have = [];
    foreach (Sale\Internals\BusinessValueTable::getList(['filter' => ['=CONSUMER_KEY' => $pre . $sbp]])->fetchAll() as $m) {
        $have[$key($m)] = $m;
    }
    $diff = [];
    foreach (Sale\Internals\BusinessValueTable::getList(['filter' => ['=CONSUMER_KEY' => $pre . $card]])->fetchAll() as $m) {
        $h = $have[$key($m)] ?? null;
        if ($h && $h['PROVIDER_KEY'] === $m['PROVIDER_KEY'] && $h['PROVIDER_VALUE'] === $m['PROVIDER_VALUE']) {
            continue;
        }
        $diff[] = $m['CODE_KEY'];
        $apply && Sale\BusinessValue::setMapping($m['CODE_KEY'], $pre . $sbp, (int)$m['PERSON_TYPE_ID'] ?: null,
            ['PROVIDER_KEY' => $m['PROVIDER_KEY'], 'PROVIDER_VALUE' => $m['PROVIDER_VALUE']]);
    }
    return $diff;
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
    bt_tbank_sbp_sync();
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
