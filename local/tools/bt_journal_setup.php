<?php
// Рассылка журнала: ИБ подписчиков, почтовые события (подтверждение, подборка), агент.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_journal_setup.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/bt_setup_lib.php';

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

$ib = bt_ib_ensure(['IBLOCK_TYPE_ID' => 'forms', 'CODE' => 'journal_subs', 'NAME' => 'Подписка на журнал', 'SORT' => 310,
    'DESCRIPTION' => 'Кто получает подборки новых статей. Название — e-mail. Активные — подтвердили адрес; снять галочку «Активность» — письма перестанут приходить.'],
    ['TOKEN' => ['Ключ подтверждения и отписки (не менять)', 'S', []]], $apply, $say, $fail);
$apply && $ib and bt_grid_sort('forms', $ib);

$events = [
    'BT_JOURNAL_CONFIRM' => ['Журнал: подтверждение подписки', "#EMAIL_TO# — адрес подписчика\n#TOKEN# — ключ подтверждения", []],
    'BT_JOURNAL_DIGEST' => ['Журнал: подборка новых статей', "#EMAIL_TO# — подписчик\n#POSTS# — карточки статей (HTML)\n#TOKEN# — ключ отписки",
        [['NAME' => 'List-Unsubscribe', 'VALUE' => '<https://#SERVER_NAME#/local/ajax/journal.php?unsub=#TOKEN#>'],
         ['NAME' => 'List-Unsubscribe-Post', 'VALUE' => 'List-Unsubscribe=One-Click']]],
];
foreach ($events as $ev => [$name, $desc, $headers]) {
    if (!CEventType::GetList(['TYPE_ID' => $ev, 'LID' => 'ru'])->Fetch()) {
        $say("почтовое событие $ev");
        $apply and (new CEventType())->Add(['LID' => 'ru', 'EVENT_NAME' => $ev, 'NAME' => $name, 'EVENT_TYPE' => 'email', 'DESCRIPTION' => $desc]);
    }
    $m = CEventMessage::GetList('id', 'asc', ['TYPE_ID' => $ev, 'SITE_ID' => 's1'])->Fetch();
    if (!$m) {
        $say("  шаблон письма $ev (оформление — bt_mail_setup.php)");
        $apply and ((new CEventMessage())->Add(['ACTIVE' => 'Y', 'EVENT_NAME' => $ev, 'LID' => ['s1'], 'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#', 'SUBJECT' => $name, 'BODY_TYPE' => 'html', 'MESSAGE' => 'https://#SERVER_NAME#/blog/', 'ADDITIONAL_FIELD' => $headers]) or $fail("event message $ev"));
    } elseif ($headers && empty($m['ADDITIONAL_FIELD'])) {
        $say("  заголовки отписки в шаблоне #{$m['ID']}");
        $apply and ((new CEventMessage())->Update($m['ID'], ['ADDITIONAL_FIELD' => $headers]) or $fail("headers $ev"));
    }
}

// первая подборка — только из статей, вышедших после запуска
if (Option::get('bt', 'journal_digest_min', '') === '') {
    $say('порог подборки: 5 статей');
    $apply and Option::set('bt', 'journal_digest_min', '5');
}
if (Option::get('bt', 'journal_digest_last', '') === '') {
    $say('отметка «прошлая подборка» = ' . date('d.m.Y H:i'));
    $apply and Option::set('bt', 'journal_digest_last', (string)time());
}
if (!CAgent::GetList([], ['NAME' => 'bt_journal_digest_agent();'])->Fetch()) {
    $say('агент bt_journal_digest_agent() раз в сутки, в 10:00');
    $apply and CAgent::AddAgent('bt_journal_digest_agent();', '', 'N', 86400, '', 'Y', date('d.m.Y 10:00:00', strtotime('+1 day')), 100, false, false);
}

// удаляем старые тестовые подписки журнала из «Заявок с сайта»
if ($fr = bt_iblock('form_requests')) {
    $r = CIBlockElement::GetList([], ['IBLOCK_ID' => $fr, '=PROPERTY_TOPIC' => 'Подписка на новые материалы журнала'], false, false, ['ID', 'NAME']);
    while ($x = $r->Fetch()) {
        $say("удалить заявку-подписку #{$x['ID']} {$x['NAME']}");
        $apply and CIBlockElement::Delete($x['ID']);
    }
}
echo "done\n";
