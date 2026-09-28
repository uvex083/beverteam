<?php
// Соцсети и мессенджеры сайта (шапка, меню, футер, контакты, разметка организации): инфоблок и стартовый набор.
// Ссылки — ТЕСТОВЫЕ, перед запуском заменить на настоящие (LAUNCH.md). Соцсеть без ссылки на сайте не выводится.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_socials_setup.php [show|apply]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$set = ['tg' => 'Telegram', 'wa' => 'WhatsApp', 'max' => 'MAX', 'vk' => 'ВКонтакте', 'yt' => 'YouTube', 'rt' => 'Rutube', 'ok' => 'Одноклассники', 'dzen' => 'Дзен'];

$ib = CIBlock::GetList([], ['=CODE' => 'socials', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
$id = (int)($ib['ID'] ?? 0);
if (!$id) {
    $say('инфоблок «Соцсети и мессенджеры»');
    if ($apply) {
        $o = new CIBlock();
        $id = (int)$o->Add(['IBLOCK_TYPE_ID' => 'site', 'CODE' => 'socials', 'API_CODE' => 'Socials', 'NAME' => 'Соцсети и мессенджеры', 'SORT' => 20,
            'SITE_ID' => ['s1'], 'ACTIVE' => 'Y', 'GROUP_ID' => ['2' => 'R'], 'VERSION' => 2, 'INDEX_ELEMENT' => 'N',
            'DESCRIPTION' => 'Иконки со ссылками в шапке, меню, футере и на странице контактов. Порядок — по сортировке. Соцсеть без ссылки или выключенная на сайте не показывается.',
            'DESCRIPTION_TYPE' => 'text'])
            or die('ошибка инфоблока: ' . $o->LAST_ERROR . "\n");
    }
}
$have = [];
if ($id) {
    $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $id]);
    while ($p = $r->Fetch()) {
        $have[$p['CODE']] = (int)$p['ID'];
    }
}
$props = [
    'LINK' => ['NAME' => 'Ссылка (без неё соцсеть не показывается)', 'PROPERTY_TYPE' => 'S', 'COL_COUNT' => 60, 'SORT' => 110],
    'ICON_SET' => ['NAME' => 'Иконка из набора', 'PROPERTY_TYPE' => 'L', 'SORT' => 120,
        'VALUES' => array_map(fn($k, $v) => ['XML_ID' => $k, 'VALUE' => $v, 'SORT' => 10], array_keys($set), $set)],
    'ICON' => ['NAME' => 'Своя иконка (SVG или PNG) — вместо иконки из набора', 'PROPERTY_TYPE' => 'F', 'FILE_TYPE' => 'svg, png', 'SORT' => 130],
];
foreach ($props as $code => $f) {
    if (isset($have[$code])) {
        continue;
    }
    $say("  свойство {$code}");
    if ($apply) {
        $have[$code] = (int)(new CIBlockProperty())->Add($f + ['IBLOCK_ID' => $id, 'CODE' => $code, 'ACTIVE' => 'Y']) or die("ошибка свойства {$code}\n");
    }
}

// стартовый набор: ссылки из «Контактов», если заполнены, иначе тестовые
$co = bt_contacts();
$start = [
    ['tg', 'Telegram', 'https://t.me/beverteam_test', 10],
    ['wa', 'WhatsApp', 'https://wa.me/79955419399', 20],
    ['max', 'MAX', 'https://max.ru/beverteam_test', 30],
    ['vk', 'ВКонтакте', 'https://vk.com/beverteam_test', 40],
    ['yt', 'YouTube', '', 50],
];
if ($id && !CIBlockElement::GetList([], ['IBLOCK_ID' => $id], [])) {
    $el = new CIBlockElement();
    foreach ($start as [$code, $name, $link, $sort]) {
        $link = ($co[$code] ?? '') ?: $link;
        $say("{$name}: " . ($link ?: 'без ссылки — на сайте не видна'));
        if ($apply) {
            $enum = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $have['ICON_SET'], 'XML_ID' => $code])->Fetch();
            $el->Add(['IBLOCK_ID' => $id, 'NAME' => $name, 'ACTIVE' => 'Y', 'SORT' => $sort,
                'PROPERTY_VALUES' => ['LINK' => $link, 'ICON_SET' => (int)($enum['ID'] ?? 0)]]) or print('  ошибка: ' . $el->LAST_ERROR . "\n");
        }
    }
}
$apply and $id and CIBlock::clearIblockTagCache($id);
echo "done\n";
