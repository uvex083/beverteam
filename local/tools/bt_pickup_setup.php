<?php
// Самовывоз: статус заказа «Готов к выдаче» (R) с письмом покупателю — менеджер ставит его, когда заказ собран на складе.
// Текст письма — bt_mail_setup.php. Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_pickup_setup.php [show|apply]. Повторный запуск ничего не дублирует.

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
const ST = 'R';
const EV = 'SALE_STATUS_CHANGED_' . ST;

// между «Оплачен, формируется к отправке» и «Выполнен»
if (!Sale\Internals\StatusTable::getById(ST)->fetch()) {
    $p = (int)(Sale\Internals\StatusTable::getById('P')->fetch()['SORT'] ?? 200);
    $f = (int)(Sale\Internals\StatusTable::getById('F')->fetch()['SORT'] ?? 300);
    $say('статус ' . ST . ' «Готов к выдаче»');
    if ($apply) {
        Sale\Internals\StatusTable::add(['ID' => ST, 'TYPE' => 'O', 'SORT' => intdiv($p + $f, 2), 'NOTIFY' => 'Y', 'COLOR' => '#9BD27A']);
        foreach (['ru' => ['Готов к выдаче', 'Самовывоз: заказ собран на складе. Покупателю уходит письмо с адресом и часами работы.'], 'en' => ['Ready for pickup', '']] as $lid => [$n, $d]) {
            Sale\Internals\StatusLangTable::add(['STATUS_ID' => ST, 'LID' => $lid, 'NAME' => $n, 'DESCRIPTION' => $d]);
        }
        // права на статус — как у «Оплачен, формируется к отправке»
        foreach (Sale\Internals\StatusGroupTaskTable::getList(['filter' => ['=STATUS_ID' => 'P']])->fetchAll() as $t) {
            Sale\Internals\StatusGroupTaskTable::add(['STATUS_ID' => ST, 'GROUP_ID' => $t['GROUP_ID'], 'TASK_ID' => $t['TASK_ID']]);
        }
    }
}

// письмо при смене статуса (текст и оформление задаёт bt_mail_setup.php)
if (!CEventType::GetList(['TYPE_ID' => EV, 'LID' => 'ru'])->Fetch()) {
    $say('почтовое событие ' . EV);
    $apply and (new CEventType())->Add(['LID' => 'ru', 'EVENT_NAME' => EV, 'NAME' => 'Изменение статуса заказа на «Готов к выдаче»', 'SORT' => 250, 'DESCRIPTION' =>
        "#ORDER_ID# — номер заказа\n#ORDER_DATE# — дата заказа\n#ORDER_STATUS# — статус\n#EMAIL# — e-mail покупателя\n#ORDER_DESCRIPTION# — описание статуса\n#TEXT# — текст\n#SALE_EMAIL# — e-mail отдела продаж"]);
}
if (!CEventMessage::GetList('id', 'asc', ['TYPE_ID' => EV])->Fetch()) {
    $say('шаблон письма ' . EV);
    $apply and (new CEventMessage())->Add(['ACTIVE' => 'Y', 'EVENT_NAME' => EV, 'LID' => ['s1'], 'EMAIL_FROM' => '#SALE_EMAIL#', 'EMAIL_TO' => '#EMAIL#',
        'SUBJECT' => 'Заказ № #ORDER_ID# готов к выдаче', 'BODY_TYPE' => 'text', 'MESSAGE' => "Заказ № #ORDER_ID# готов к выдаче.\n"]);
}
echo "done\n";
