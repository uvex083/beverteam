<?php
// Счёт для юрлиц: статус заказа «Подтверждён» (C) с письмом покупателю, данные покупателя в счёте из свойств заказа,
// реквизиты продавца из инфоблока «Контакты». Текст письма — bt_mail_setup.php.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_bill_setup.php [show|apply]. Повторный запуск ничего не дублирует.

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
const ST = 'C';
const EV = 'SALE_STATUS_CHANGED_' . ST;

// статус «Подтверждён» между «Принят» и «Оплачен»
if (!Sale\Internals\StatusTable::getById(ST)->fetch()) {
    $say('статус ' . ST . ' «Подтверждён»');
    if ($apply) {
        Sale\Internals\StatusTable::add(['ID' => ST, 'TYPE' => 'O', 'SORT' => 150, 'NOTIFY' => 'Y', 'COLOR' => '#D7E85C']);
        foreach (['ru' => ['Подтверждён', 'Менеджер проверил заказ. Покупателю по счёту уходит письмо со счётом на оплату.'], 'en' => ['Confirmed', '']] as $lid => [$n, $d]) {
            Sale\Internals\StatusLangTable::add(['STATUS_ID' => ST, 'LID' => $lid, 'NAME' => $n, 'DESCRIPTION' => $d]);
        }
        // права на статус — как у «Принят»
        foreach (Sale\Internals\StatusGroupTaskTable::getList(['filter' => ['=STATUS_ID' => 'N']])->fetchAll() as $t) {
            Sale\Internals\StatusGroupTaskTable::add(['STATUS_ID' => ST, 'GROUP_ID' => $t['GROUP_ID'], 'TASK_ID' => $t['TASK_ID']]);
        }
    }
}

// письмо при смене статуса (текст и оформление задаёт bt_mail_setup.php)
if (!CEventType::GetList(['TYPE_ID' => EV, 'LID' => 'ru'])->Fetch()) {
    $say('почтовое событие ' . EV);
    $apply and (new CEventType())->Add(['LID' => 'ru', 'EVENT_NAME' => EV, 'NAME' => 'Изменение статуса заказа на «Подтверждён»', 'SORT' => 150, 'DESCRIPTION' =>
        "#ORDER_ID# — номер заказа\n#ORDER_DATE# — дата заказа\n#ORDER_STATUS# — статус\n#EMAIL# — e-mail покупателя\n#ORDER_DESCRIPTION# — описание статуса\n#TEXT# — текст\n#SALE_EMAIL# — e-mail отдела продаж"]);
}
if (!CEventMessage::GetList('id', 'asc', ['TYPE_ID' => EV])->Fetch()) {
    $say('шаблон письма ' . EV);
    $apply and (new CEventMessage())->Add(['ACTIVE' => 'Y', 'EVENT_NAME' => EV, 'LID' => ['s1'], 'EMAIL_FROM' => '#SALE_EMAIL#', 'EMAIL_TO' => '#EMAIL#',
        'SUBJECT' => 'Заказ № #ORDER_ID# подтверждён', 'BODY_TYPE' => 'text', 'MESSAGE' => "Заказ № #ORDER_ID# подтверждён.\n"]);
}

// покупатель в счёте — из свойств заказа юрлица
$ps = Sale\Internals\PaySystemActionTable::getList(['filter' => ['=ACTION_FILE' => 'bill'], 'select' => ['ID']])->fetch();
$ur = (int)(Sale\Internals\PersonTypeTable::getList(['filter' => ['=CODE' => 'UR', '=LID' => 's1'], 'select' => ['ID']])->fetch()['ID'] ?? 0);
if ($ps && $ur) {
    $props = array_column(Sale\Internals\OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $ur], 'select' => ['ID', 'CODE']])->fetchAll(), 'ID', 'CODE');
    $consumer = Sale\PaySystem\Service::PAY_SYSTEM_PREFIX . $ps['ID'];
    foreach (['BUYER_PERSON_COMPANY_NAME' => 'COMPANY', 'BUYER_PERSON_COMPANY_INN' => 'INN', 'BUYER_PERSON_COMPANY_ADDRESS' => 'COMPANY_ADR',
                 'BUYER_PERSON_COMPANY_PHONE' => 'PHONE', 'BUYER_PERSON_COMPANY_NAME_CONTACT' => 'CONTACT_PERSON'] as $code => $prop) {
        $want = ['PROVIDER_KEY' => 'PROPERTY', 'PROVIDER_VALUE' => (string)($props[$prop] ?? '')];
        $have = Sale\BusinessValue::getMapping($code, $consumer, $ur, ['MATCH' => Sale\BusinessValue::MATCH_EXACT]);
        if (($have['PROVIDER_KEY'] ?? '') !== 'PROPERTY' || (string)($have['PROVIDER_VALUE'] ?? '') !== $want['PROVIDER_VALUE']) {
            $say("счёт: $code ← свойство $prop");
            $apply and Sale\BusinessValue::setMapping($code, $consumer, $ur, $want);
        }
    }
}

$say('реквизиты продавца в счёте — из инфоблока «Контакты»');
$apply and bt_bill_sync();
echo "done\n";
