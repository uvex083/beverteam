<?php
// Журнал: инфоблок «Журнал: карточка под оглавлением» — промо-карточка сбоку статьи, своя для рубрики или общая.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_promo_setup.php [show|apply]. Повторный запуск ничего не дублирует.

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
$fail = fn(string $s) => die("ERROR: $s\n");
$jId = bt_iblock('journal') or $fail('нет ИБ journal');
$type = CIBlock::GetArrayByID($jId, 'IBLOCK_TYPE_ID');

$code = 'journal_promo';
$id = bt_iblock($code);
if (!$id) {
    $say("создать ИБ $code «Журнал: карточка под оглавлением»");
    if ($apply) {
        $o = new CIBlock();
        $id = (int)$o->Add(['CODE' => $code, 'NAME' => 'Журнал: карточка под оглавлением', 'IBLOCK_TYPE_ID' => $type, 'SORT' => 20, 'SITE_ID' => ['s1'], 'ACTIVE' => 'Y',
            'GROUP_ID' => ['2' => 'R'], 'VERSION' => 2, 'INDEX_ELEMENT' => 'N', 'WORKFLOW' => 'N',
            'DESCRIPTION' => 'Карточка под оглавлением статьи. Выводится карточка с рубрикой статьи, если такой нет — первая карточка без рубрик. Картинка анонса — фото на карточке.'])
            or $fail($o->LAST_ERROR);
    }
}
$props = [
    'RUBRICS' => ['Рубрики журнала', 'G', ['MULTIPLE' => 'Y', 'MULTIPLE_CNT' => 2, 'LINK_IBLOCK_ID' => $jId, 'HINT' => 'Пусто — карточка для всех остальных рубрик']],
    'TITLE' => ['Заголовок', 'S', []],
    'TEXT' => ['Текст', 'S', ['HINT' => '#PRICE# — подставится «от N ₽» по самому дешёвому товару раздела из ссылки']],
    'BTN_TEXT' => ['Текст кнопки', 'S', []],
    'BTN_LINK' => ['Ссылка', 'S', ['HINT' => 'Без картинки анонса на карточке будет фото самого дешёвого товара раздела из ссылки']],
];
$have = [];
if ($id) {
    $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $id]);
    while ($p = $r->Fetch()) {
        $have[$p['CODE']] = 1;
    }
}
$sort = 100;
foreach ($props as $pc => [$name, $pt, $extra]) {
    $sort += 10;
    if (isset($have[$pc])) {
        continue;
    }
    $say("  свойство $pc «{$name}»");
    if ($apply) {
        $bp = new CIBlockProperty();
        $bp->Add(['IBLOCK_ID' => $id, 'CODE' => $pc, 'NAME' => $name, 'SORT' => $sort, 'PROPERTY_TYPE' => $pt, 'ACTIVE' => 'Y'] + $extra) or $fail("prop $pc: {$bp->LAST_ERROR}");
    }
}

$sec = [];
$r = CIBlockSection::GetList([], ['IBLOCK_ID' => $jId], false, ['ID', 'CODE']);
while ($s = $r->Fetch()) {
    $sec[$s['CODE']] = (int)$s['ID'];
}
// название => [сортировка, рубрики, заголовок, текст, кнопка, ссылка]
$items = [
    'Зерно BOTANICA' => [10, [], 'Зерно BOTANICA', 'Свежая обжарка, #PRICE#', 'В каталог', '/catalog/kofe/'],
    'Ремонт кофемашин' => [20, ['uhod-za-kofemashinoy'], 'Ремонт кофемашин', 'Любые марки, выезд инженера по Екатеринбургу', 'Вызвать инженера', '/servis/remont-kofemashin/#form'],
    'Аренда кофемашин' => [30, ['dlya-biznesa'], 'Аренда кофемашин', 'Jetinno для офиса и кафе, #PRICE# в месяц', 'Подробнее', '/arenda-kofemashin/'],
    'Чай' => [40, ['chay'], 'Чай', 'Пуэр, улун и другие, #PRICE#', 'В каталог', '/catalog/chay/'],
];
foreach ($items as $name => [$s, $rub, $title, $text, $btn, $link]) {
    if ($id && CIBlockElement::GetList([], ['IBLOCK_ID' => $id, '=NAME' => $name], false, false, ['ID'])->Fetch()) {
        continue;
    }
    $say("  + $name");
    if ($apply) {
        $o = new CIBlockElement();
        $eid = (int)$o->Add(['IBLOCK_ID' => $id, 'NAME' => $name, 'SORT' => $s, 'ACTIVE' => 'Y']) or $fail($o->LAST_ERROR);
        CIBlockElement::SetPropertyValuesEx($eid, $id, ['RUBRICS' => array_values(array_filter(array_map(fn($c) => $sec[$c] ?? 0, $rub))) ?: false,
            'TITLE' => $title, 'TEXT' => $text, 'BTN_TEXT' => $btn, 'BTN_LINK' => $link]);
    }
}
if ($apply && $id) {
    foreach (['tbl_iblock_list_', 'tbl_iblock_element_'] as $prefix) {
        $gridId = $prefix . md5($type . '.' . $id);
        $o = CUserOptions::GetOption('main.interface.grid', $gridId, [], 0) ?: [];
        $o['views']['default']['last_sort_by'] = 'SORT';
        $o['views']['default']['last_sort_order'] = 'asc';
        $o['current_view'] ??= 'default';
        CUserOptions::SetOption('main.interface.grid', $gridId, $o, true);
    }
    CIBlock::clearIblockTagCache($id);
}
echo "done\n";
