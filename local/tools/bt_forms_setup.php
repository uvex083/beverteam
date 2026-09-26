<?php
// Заявки с сайта: тип ИБ «Заявки», ИБ form_requests, почтовое событие BT_FORM_REQUEST и шаблон письма.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_forms_setup.php [show|apply]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

Loader::includeModule('iblock');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

if (!CIBlockType::GetByID('forms')->Fetch()) {
    $say('создать тип ИБ forms «Заявки»');
    $apply and ((new CIBlockType())->Add(['ID' => 'forms', 'SECTIONS' => 'N', 'IN_RSS' => 'N', 'SORT' => 950,
        'LANG' => ['ru' => ['NAME' => 'Заявки', 'ELEMENT_NAME' => 'Заявка']]]) or $fail('type forms'));
}

$ibId = (int)(CIBlock::GetList([], ['=CODE' => 'form_requests', 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'] ?? 0);
if (!$ibId) {
    $say('создать ИБ form_requests «Заявки с сайта»');
    if ($apply) {
        $o = new CIBlock();
        // гостям доступа нет: заявки видит только админка
        $ibId = (int)$o->Add(['IBLOCK_TYPE_ID' => 'forms', 'CODE' => 'form_requests', 'API_CODE' => 'FormRequests', 'NAME' => 'Заявки с сайта', 'SORT' => 10,
            'SITE_ID' => ['s1'], 'ACTIVE' => 'Y', 'GROUP_ID' => ['2' => 'D'], 'VERSION' => 2, 'INDEX_ELEMENT' => 'N', 'WORKFLOW' => 'N',
            'ELEMENTS_NAME' => 'Заявки', 'ELEMENT_NAME' => 'Заявка', 'ELEMENT_ADD' => 'Добавить заявку'])
            or $fail("iblock: {$o->LAST_ERROR}");
    }
}
$props = [
    'CLIENT_NAME' => ['Имя', 'S', []],
    'PHONE' => ['Телефон', 'S', []],
    'EMAIL' => ['E-mail', 'S', []],
    'TOPIC' => ['Тема', 'S', []],
    'MESSAGE' => ['Сообщение', 'S', ['ROW_COUNT' => 5]],
    'PAGE' => ['Страница, с которой отправлена', 'S', []],
    'IP' => ['IP-адрес', 'S', []],
];
$have = [];
if ($ibId) {
    $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId]);
    while ($p = $r->Fetch()) {
        $have[$p['CODE']] = 1;
    }
}
$sort = 100;
foreach ($props as $code => [$name, $type, $extra]) {
    $sort += 10;
    if (isset($have[$code])) {
        continue;
    }
    $say("  свойство $code «{$name}»");
    if ($apply) {
        $bp = new CIBlockProperty();
        $bp->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'PROPERTY_TYPE' => $type, 'ACTIVE' => 'Y'] + $extra)
            or $fail("prop $code: {$bp->LAST_ERROR}");
    }
}

// почтовое событие и шаблон
const EV = 'BT_FORM_REQUEST';
if (!CEventType::GetList(['TYPE_ID' => EV, 'LID' => 'ru'])->Fetch()) {
    $say('почтовое событие ' . EV);
    $apply and (new CEventType())->Add(['LID' => 'ru', 'EVENT_NAME' => EV, 'NAME' => 'Заявка с сайта', 'SORT' => 100, 'DESCRIPTION' =>
        "#EMAIL_TO# — кому отправить (e-mail из настроек магазина)\n#TOPIC# — тема заявки\n#CLIENT_NAME# — имя\n#PHONE# — телефон\n#EMAIL# — e-mail\n#MESSAGE# — сообщение\n#PAGE# — страница сайта\n#ADMIN_URL# — заявка в админке"]);
}
if (!CEventMessage::GetList('id', 'asc', ['TYPE_ID' => EV])->Fetch()) {
    $say('шаблон письма ' . EV);
    if ($apply) {
        $m = new CEventMessage();
        $m->Add(['ACTIVE' => 'Y', 'EVENT_NAME' => EV, 'LID' => ['s1'], 'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#', 'EMAIL_TO' => '#EMAIL_TO#',
            'SUBJECT' => '#SITE_NAME#: заявка с сайта — #TOPIC#', 'BODY_TYPE' => 'text', 'MESSAGE' =>
                "Новая заявка с сайта #SERVER_NAME#\n\nТема: #TOPIC#\nИмя: #CLIENT_NAME#\nТелефон: #PHONE#\nE-mail: #EMAIL#\n\nСообщение:\n#MESSAGE#\n\nОтправлено со страницы: #PAGE#\nЗаявка в админке: #ADMIN_URL#\n"])
            or $fail('message: ' . $m->LAST_ERROR);
    }
}

// список заявок в админке — новые сверху
if ($apply && $ibId) {
    foreach (['tbl_iblock_list_', 'tbl_iblock_element_'] as $prefix) {
        $gridId = $prefix . md5('forms.' . $ibId);
        $opt = CUserOptions::GetOption('main.interface.grid', $gridId, [], 0) ?: [];
        $opt['views']['default']['last_sort_by'] = 'ID';
        $opt['views']['default']['last_sort_order'] = 'desc';
        CUserOptions::SetOption('main.interface.grid', $gridId, $opt, true);
    }
}
echo "done\n";
