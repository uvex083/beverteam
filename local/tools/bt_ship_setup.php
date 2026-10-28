<?php
// Доставка: статус «Передан в доставку» (S) с письмом — ставит менеджер, у СДЭК ставится сам по трек-номеру.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_ship_setup.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale;

Loader::includeModule('sale');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
const ST = 'S';
const EV = 'SALE_STATUS_CHANGED_' . ST;

// статус — между «Оплачен, формируется к отправке» и «Готов к выдаче»
if (!Sale\Internals\StatusTable::getById(ST)->fetch()) {
    $say('статус ' . ST . ' «Передан в доставку»');
    if ($apply) {
        Sale\Internals\StatusTable::add(['ID' => ST, 'TYPE' => 'O', 'SORT' => 170, 'NOTIFY' => 'Y', 'COLOR' => '#7FB5E8']);
        foreach (['ru' => ['Передан в доставку', 'Курьер BEVERTEAM или СДЭК: заказ отправлен. Покупателю уходит письмо. У СДЭК статус ставится сам, когда придёт трек-номер.'], 'en' => ['Shipped', '']] as $lid => [$n, $d]) {
            Sale\Internals\StatusLangTable::add(['STATUS_ID' => ST, 'LID' => $lid, 'NAME' => $n, 'DESCRIPTION' => $d]);
        }
        // права на статус — как у «Оплачен, формируется к отправке»
        foreach (Sale\Internals\StatusGroupTaskTable::getList(['filter' => ['=STATUS_ID' => 'P']])->fetchAll() as $t) {
            Sale\Internals\StatusGroupTaskTable::add(['STATUS_ID' => ST, 'GROUP_ID' => $t['GROUP_ID'], 'TASK_ID' => $t['TASK_ID']]);
        }
    }
}

// подсказки менеджеру в админке: какой статус для какой доставки
$hints = [
    'R' => 'Только самовывоз: заказ собран на складе. Покупателю уходит письмо с адресом и часами работы. Для курьера и СДЭК — «Передан в доставку».',
    'P' => 'Ставится сам, когда заказ оплачен (онлайн или по счёту). Заказ СДЭК в этом статусе сам выгружается в СДЭК. Заказ «при получении» сразу переводите в «Передан в доставку» или «Готов к выдаче».',
];
foreach ($hints as $id => $d) {
    $row = Sale\Internals\StatusLangTable::getList(['filter' => ['=STATUS_ID' => $id, '=LID' => 'ru'], 'select' => ['STATUS_ID', 'LID', 'DESCRIPTION']])->fetch();
    if ($row && $row['DESCRIPTION'] !== $d) {
        $say("подсказка статуса $id: $d");
        $apply and Sale\Internals\StatusLangTable::update(['STATUS_ID' => $id, 'LID' => 'ru'], ['DESCRIPTION' => $d]);
    }
}

// P после оплаты ставит bt_order_ship_guard и только неотправленному; штатная настройка откатила бы отправленный заказ
if (\Bitrix\Main\Config\Option::get('sale', 'status_on_paid', '') !== '') {
    $say('sale.status_on_paid: выключить (было ' . \Bitrix\Main\Config\Option::get('sale', 'status_on_paid', '') . ')');
    $apply and \Bitrix\Main\Config\Option::set('sale', 'status_on_paid', '');
}

// письмо при смене статуса (текст — в bt_mail_setup.php)
if (!CEventType::GetList(['TYPE_ID' => EV, 'LID' => 'ru'])->Fetch()) {
    $say('почтовое событие ' . EV);
    $apply and (new CEventType())->Add(['LID' => 'ru', 'EVENT_NAME' => EV, 'NAME' => 'Изменение статуса заказа на «Передан в доставку»', 'SORT' => 240, 'DESCRIPTION' =>
        "#ORDER_ID# — номер заказа\n#ORDER_DATE# — дата заказа\n#ORDER_STATUS# — статус\n#EMAIL# — e-mail покупателя\n#ORDER_DESCRIPTION# — описание статуса\n#TEXT# — текст\n#SALE_EMAIL# — e-mail отдела продаж"]);
}
if (!CEventMessage::GetList('id', 'asc', ['TYPE_ID' => EV])->Fetch()) {
    $say('шаблон письма ' . EV);
    $apply and (new CEventMessage())->Add(['ACTIVE' => 'Y', 'EVENT_NAME' => EV, 'LID' => ['s1'], 'EMAIL_FROM' => '#SALE_EMAIL#', 'EMAIL_TO' => '#EMAIL#',
        'SUBJECT' => 'Заказ № #ORDER_ID# передан в доставку', 'BODY_TYPE' => 'text', 'MESSAGE' => "Заказ № #ORDER_ID# передан в доставку.\n"]);
}
echo "done\n";
