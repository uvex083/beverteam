<?php
// Общесайтовые настройки: тип ИБ «Сайт: общие блоки», ИБ «Контакты и реквизиты», шаблон сайта.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_site_setup.php [show|apply]. Повторный запуск ничего не дублирует.

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

const TYPE = 'site';
const CONTACTS = 'site_contacts';

$props = [
    // код => [название, сортировка, значение по умолчанию]
    'PHONE1' => ['Телефон основной', 10, '+7 995 541-93-99'],
    'PHONE2' => ['Телефон дополнительный', 20, '+7 904 384-13-88'],
    'EMAIL' => ['E-mail', 30, 'beteam@inbox.ru'],
    'ZIP' => ['Индекс', 40, '620105'],
    'CITY' => ['Город', 50, 'Екатеринбург'],
    'STREET' => ['Адрес', 60, 'ул. Колокольная, 31А'],
    'HOURS' => ['Время работы офиса', 70, 'Пн–Пт 10:00–17:00'],
    'HOURS_SERVICE' => ['Время выезда инженера', 80, 'Пн–Пт 9:00–17:00'],
    'LEGAL' => ['Юридическое лицо', 90, 'ИП Чичиланов Василий Павлович'],
    'OGRNIP' => ['ОГРНИП', 100, '310667128700035'],
    'INN' => ['ИНН', 110, '667113850366'],
    'TG' => ['Ссылка Telegram', 200, ''],
    'WA' => ['Ссылка WhatsApp', 210, ''],
    'MAX' => ['Ссылка MAX', 220, ''],
    'VK' => ['Ссылка ВКонтакте', 230, ''],
];

// тип инфоблоков
if (!CIBlockType::GetByID(TYPE)->Fetch()) {
    $say('создать тип ИБ ' . TYPE . ' «Сайт: общие блоки»');
    if ($apply) {
        $ok = (new CIBlockType())->Add([
            'ID' => TYPE, 'SECTIONS' => 'N', 'IN_RSS' => 'N', 'SORT' => 900,
            'LANG' => ['ru' => ['NAME' => 'Сайт: общие блоки', 'ELEMENT_NAME' => 'Элемент']],
        ]);
        $ok or die('type: ' . $GLOBALS['APPLICATION']->GetException()?->GetString() . "\n");
    }
}

// инфоблок контактов
$ibId = (int)(CIBlock::GetList([], ['=CODE' => CONTACTS, 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'] ?? 0);
if (!$ibId) {
    $say('создать ИБ ' . CONTACTS . ' «Контакты и реквизиты»');
    if ($apply) {
        $ib = new CIBlock();
        $ibId = (int)$ib->Add([
            'IBLOCK_TYPE_ID' => TYPE, 'CODE' => CONTACTS, 'API_CODE' => 'SiteContacts', 'NAME' => 'Контакты и реквизиты',
            'SITE_ID' => ['s1'], 'SORT' => 10, 'ACTIVE' => 'Y', 'INDEX_ELEMENT' => 'N', 'INDEX_SECTION' => 'N',
            'GROUP_ID' => ['2' => 'R'],
        ]);
        $ibId or die('iblock: ' . $ib->LAST_ERROR . "\n");
    }
}

// свойства
if ($ibId) {
    $have = [];
    $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId]);
    while ($p = $r->Fetch()) {
        $have[$p['CODE']] = true;
    }
    foreach ($props as $code => [$name, $sort]) {
        if (isset($have[$code])) {
            continue;
        }
        $say("свойство $code «{$name}»");
        if ($apply) {
            $bp = new CIBlockProperty();
            $bp->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'PROPERTY_TYPE' => 'S', 'ACTIVE' => 'Y'])
                or die("prop $code: {$bp->LAST_ERROR}\n");
        }
    }
    // единственный элемент
    $el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId], false, ['nTopCount' => 1], ['ID'])->Fetch();
    if (!$el) {
        $say('создать элемент «Контакты BEVERTEAM» со значениями по умолчанию');
        if ($apply) {
            $vals = [];
            foreach ($props as $code => [, , $v]) {
                $vals[$code] = $v;
            }
            $e = new CIBlockElement();
            $e->Add(['IBLOCK_ID' => $ibId, 'NAME' => 'Контакты BEVERTEAM', 'ACTIVE' => 'Y', 'PROPERTY_VALUES' => $vals])
                or die("element: {$e->LAST_ERROR}\n");
        }
    }
}

// шаблон сайта s1
$tpl = [];
$r = CSite::GetTemplateList('s1');
while ($t = $r->Fetch()) {
    $tpl[] = $t;
}
if (count($tpl) !== 1 || $tpl[0]['TEMPLATE'] !== 'beverteam' || $tpl[0]['CONDITION'] !== '') {
    $say('шаблон сайта s1: ' . implode(', ', array_column($tpl, 'TEMPLATE')) . ' → beverteam');
    if ($apply) {
        (new CSite())->Update('s1', ['TEMPLATE' => [['TEMPLATE' => 'beverteam', 'SORT' => 1, 'CONDITION' => '']]])
            or die("site: {$GLOBALS['APPLICATION']->LAST_ERROR}\n");
    }
}

if ($apply) {
    BXClearCache(true, '/bt/');
}
echo "done\n";
