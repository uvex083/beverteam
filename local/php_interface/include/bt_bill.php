<?php
// Счёт на оплату для юрлиц: PDF штатного обработчика «Счёт», выпускается после подтверждения заказа менеджером (статус C)

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Sale;

const BT_BILL_STATUS = 'C';

function bt_bill_payment(Sale\Order $order): ?Sale\Payment
{
    foreach ($order->getPaymentCollection() as $p) {
        $ps = Sale\PaySystem\Manager::getById($p->getPaymentSystemId());
        if (($ps['ACTION_FILE'] ?? '') === 'bill') {
            return $p;
        }
    }
    return null;
}

// Счёт доступен: оплата по счёту, заказ подтверждён менеджером (любой статус после «Принят») и не отменён
function bt_bill_ready(Sale\Order $order): bool
{
    return !$order->isCanceled() && $order->getField('STATUS_ID') !== 'N' && bt_bill_payment($order) !== null;
}

// ID файла счёта; выпускается заново, если сумма заказа изменилась (менеджер уточнил доставку)
function bt_bill_file(Sale\Order $order): int
{
    $payment = bt_bill_payment($order);
    if (!$payment) {
        return 0;
    }
    // реквизиты продавца могли поменяться мимо события инфоблока (скриптом) — сверяем перед выпуском
    $ver = bt_bill_ver();
    Option::get('bt', 'bill_ver') === $ver or bt_bill_sync();
    $key = 'bill_' . $order->getId();
    [$fid, $sum, $was] = array_pad(explode(':', Option::get('bt', $key)), 3, '');
    $cur = number_format((float)$order->getPrice(), 2, '.', '');
    if ((int)$fid && $sum === $cur && $was === $ver && CFile::GetFileArray((int)$fid)) {
        return (int)$fid;
    }
    // в строках счёта служебные свойства позиций не нужны: фасовка уже в названии, «цена без скидки» — для сайта и писем.
    // Правим копию заказа, сам заказ не меняется
    $copy = $order->createClone();
    foreach ($copy->getBasket() as $bi) {
        $drop = array_filter(iterator_to_array($bi->getPropertyCollection()), fn($p) => in_array($p->getField('CODE'), ['PACK', 'FULL_PRICE'], true));
        array_walk($drop, fn($p) => $p->delete());
    }
    $payment = bt_bill_payment($copy);
    $service = Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
    $file = $service && $service->isAffordPdf() ? $service->getPdf($payment) : null;
    if (empty($file['ID'])) {
        return 0;
    }
    (int)$fid and CFile::Delete((int)$fid);
    Option::set('bt', $key, $file['ID'] . ':' . $cur . ':' . $ver);
    return (int)$file['ID'];
}

// Отпечаток реквизитов продавца: поменялся — счёт синхронизируем и выпускаем заново
function bt_bill_ver(): string
{
    $co = bt_contacts();
    return substr(md5(implode('|', array_map(fn($k) => (string)($co[$k] ?? ''), ['legal', 'inn', 'legal_address', 'zip', 'city', 'street', 'phone1', 'bank', 'bik', 'rs', 'ks']))), 0, 8);
}

// Реквизиты продавца в счёте — из инфоблока «Контакты» (там их правит клиент)
function bt_bill_sync(): void
{
    if (!Loader::includeModule('sale')) {
        return;
    }
    $ps = Sale\Internals\PaySystemActionTable::getList(['filter' => ['=ACTION_FILE' => 'bill'], 'select' => ['ID']])->fetch();
    if (!$ps) {
        return;
    }
    $co = bt_contacts();
    $addr = $co['legal_address'] ?? '' ?: trim(implode(', ', array_filter([$co['zip'] ?? '', $co['city'] ?? '', $co['street'] ?? ''])));
    $map = ['SELLER_COMPANY_NAME' => $co['legal'] ?? '' ?: 'BEVERTEAM', 'SELLER_COMPANY_INN' => $co['inn'] ?? '', 'SELLER_COMPANY_ADDRESS' => $addr,
        'SELLER_COMPANY_PHONE' => $co['phone1'] ?? '', 'SELLER_COMPANY_BANK_NAME' => $co['bank'] ?? '', 'SELLER_COMPANY_BANK_BIC' => $co['bik'] ?? '',
        'SELLER_COMPANY_BANK_ACCOUNT' => $co['rs'] ?? '', 'SELLER_COMPANY_BANK_ACCOUNT_CORR' => $co['ks'] ?? ''];
    // у ИП подписывает сам предприниматель, бухгалтера в счёте нет; вместо шаблонного текста про курс доллара — наши условия
    $legal = trim((string)($co['legal'] ?? ''));
    $ip = preg_match('/^ИП\s+(\S+)\s+(\S)\S*\s+(\S)/u', $legal, $m);
    $map += ['SELLER_COMPANY_DIRECTOR_POSITION' => $ip ? 'Индивидуальный предприниматель' : 'Руководитель',
        'SELLER_COMPANY_DIRECTOR_NAME' => $ip ? "{$m[1]} {$m[2]}. {$m[3]}." : '', 'SELLER_COMPANY_ACCOUNTANT_POSITION' => '', 'SELLER_COMPANY_ACCOUNTANT_NAME' => '',
        'BILL_COMMENT1' => 'Счёт действителен 5 рабочих дней. В назначении платежа укажите номер счёта. Товар отгружается после поступления оплаты на расчётный счёт.',
        'BILL_COMMENT2' => ''];
    foreach ($map as $code => $v) {
        Sale\BusinessValue::setMapping($code, Sale\PaySystem\Service::PAY_SYSTEM_PREFIX . $ps['ID'], null, ['PROVIDER_KEY' => 'VALUE', 'PROVIDER_VALUE' => (string)$v]);
    }
    Option::set('bt', 'bill_ver', bt_bill_ver());
}

// iblock:OnAfterIBlockElementUpdate — правка контактов сразу попадает в счёт
function bt_bill_sync_on_contacts(array $f): void
{
    if ((int)($f['IBLOCK_ID'] ?? 0) === bt_iblock('site_contacts')) {
        bt_bill_sync();
    }
}

// main:OnBeforeEventSend — к письму «Заказ подтверждён» прикладываем счёт
function bt_bill_attach(array &$fields, array &$message): void
{
    if (($message['EVENT_NAME'] ?? '') !== 'SALE_STATUS_CHANGED_' . BT_BILL_STATUS || !Loader::includeModule('sale')
        || !($order = Sale\Order::load((int)($fields['ORDER_REAL_ID'] ?? 0)))) {
        return;
    }
    $fid = bt_bill_ready($order) ? bt_bill_file($order) : 0;
    $fields['BT_BILL'] = $fid ? 'Y' : '';
    if ($fid) {
        $message['FILE'][] = $fid;
    }
}
