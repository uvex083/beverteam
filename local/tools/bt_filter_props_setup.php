<?php
// Характеристики для фильтра каталога: кофемашины (вода, зёрна, экран, растворимые, телеметрия, оплата) и аксессуары (материал, объём, цвет, фильтр).
// Значения разобраны из «Технических характеристик» и описаний товаров; заполняются только пустые свойства — ручные правки не затираются.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_filter_props_setup.php [show|apply]. Повторный запуск ничего не дублирует.
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$ib = bt_iblock('catalog');
$sec = fn(string $code) => (int)(CIBlockSection::GetList([], ['IBLOCK_ID' => $ib, '=CODE' => $code], false, ['ID'])->Fetch()['ID'] ?? 0);
$machines = $sec('professionalnye-kofemashiny');
$acc = $sec('aksessuary');
$yes = [['Да', 'yes']];

// код => [название, сортировка, раздел, значения [название, код]]; «Да» — в фильтре переключатель
$props = [
    'WATER_SUPPLY' => ['Подключение к водопроводу', 600, $machines, $yes],
    'WATER_TANK' => ['Объём бака для воды', 610, $machines, [['0,6 л', '0-6-l'], ['1,6 л', '1-6-l'], ['2 л', '2-l'], ['4 л', '4-l']]],
    'BEAN_HOPPER' => ['Контейнер для зёрен', 620, $machines, [['до 500 г', 'do-500-g'], ['1–1,5 кг', '1-1-5-kg'], ['2 кг', '2-kg']]],
    'TOUCHSCREEN' => ['Сенсорный экран', 630, $machines, $yes],
    'INSTANT' => ['Растворимые напитки (какао, сухое молоко)', 640, $machines, $yes],
    'TELEMETRY' => ['Онлайн-телеметрия', 650, $machines, $yes],
    'MDB' => ['Подключение платёжного терминала', 660, $machines, $yes],
    'MATERIAL' => ['Материал', 700, $acc, [['Стекло', 'steklo'], ['Керамика', 'keramika']]],
    'VOLUME' => ['Объём', 710, $acc, [['250 мл', '250-ml'], ['300 мл', '300-ml'], ['550 мл', '550-ml'], ['650 мл', '650-ml']]],
    'COLOR' => ['Цвет', 720, $acc, [['Прозрачный', 'prozrachnyy'], ['Белый', 'belyy'], ['Красный', 'krasnyy'], ['Оранжевый', 'oranzhevyy']]],
    'FILTER_SIZE' => ['Размер бумажного фильтра', 730, $acc, [['№2', 'n2'], ['№4', 'n4']]],
];

// кофемашина (ID) => свойство => код значения (у «Да» — yes)
$values = [
    345 => /* BRAVO */ ['BEAN_HOPPER' => '2-kg', 'TOUCHSCREEN' => 'yes', 'INSTANT' => 'yes', 'TELEMETRY' => 'yes'],
    346 => /* JL 03 */ ['WATER_TANK' => '1-6-l', 'BEAN_HOPPER' => 'do-500-g'],
    347 => /* JL 05 */ ['WATER_TANK' => '0-6-l', 'BEAN_HOPPER' => 'do-500-g'],
    348 => /* JL 15 VIVA */ ['WATER_SUPPLY' => 'yes', 'WATER_TANK' => '2-l', 'BEAN_HOPPER' => 'do-500-g', 'TOUCHSCREEN' => 'yes', 'TELEMETRY' => 'yes', 'MDB' => 'yes'],
    349 => /* JL 32 */ ['WATER_SUPPLY' => 'yes', 'WATER_TANK' => '4-l', 'BEAN_HOPPER' => '1-1-5-kg', 'TOUCHSCREEN' => 'yes', 'INSTANT' => 'yes', 'MDB' => 'yes'],
    350 => /* JL 33 */ ['WATER_SUPPLY' => 'yes', 'WATER_TANK' => '4-l', 'BEAN_HOPPER' => '2-kg', 'TOUCHSCREEN' => 'yes', 'INSTANT' => 'yes', 'TELEMETRY' => 'yes'],
    351 => /* JL 36 */ ['WATER_SUPPLY' => 'yes', 'WATER_TANK' => '2-l', 'BEAN_HOPPER' => '1-1-5-kg', 'TOUCHSCREEN' => 'yes', 'TELEMETRY' => 'yes', 'MDB' => 'yes'],
];
// аксессуары ищем по названию: коды у них транслитом длинные
$accValues = [
    'Чайник стеклянный "Бохэ' => ['MATERIAL' => 'steklo', 'VOLUME' => '650-ml', 'COLOR' => 'prozrachnyy'],
    'Чайник заварочный Типод' => ['MATERIAL' => 'steklo', 'VOLUME' => '550-ml', 'COLOR' => 'prozrachnyy'],
    'Чашка стеклянная с рифленым' => ['MATERIAL' => 'steklo', 'VOLUME' => '250-ml', 'COLOR' => 'prozrachnyy'],
    'Чашка стеклянная с ручкой 300' => ['MATERIAL' => 'steklo', 'VOLUME' => '300-ml', 'COLOR' => 'prozrachnyy'],
    'Воронка пуровер из керамики для приготовления кофе Белая' => ['MATERIAL' => 'keramika', 'COLOR' => 'belyy', 'FILTER_SIZE' => 'n4'],
    'Воронка пуровер из керамики для приготовления кофе Красная' => ['MATERIAL' => 'keramika', 'COLOR' => 'krasnyy', 'FILTER_SIZE' => 'n2'],
    'Воронка пуровер из керамики для приготовления кофе Оранжевая' => ['MATERIAL' => 'keramika', 'COLOR' => 'oranzhevyy', 'FILTER_SIZE' => 'n2'],
];

if (!$machines || !$acc) {
    die("нет раздела кофемашин или аксессуаров\n");
}

$pid = [];
foreach ($props as $code => [$name, $sort, $secId, $enums]) {
    $p = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ib, 'CODE' => $code])->Fetch();
    if (!$p) {
        $say("свойство {$code} «{$name}»");
        if ($apply) {
            $id = (int)(new CIBlockProperty())->Add(['IBLOCK_ID' => $ib, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'PROPERTY_TYPE' => 'L',
                'LIST_TYPE' => 'L', 'ACTIVE' => 'Y', 'SECTION_PROPERTY' => 'N', 'FILTRABLE' => 'Y',
                'VALUES' => array_map(fn($v, $i) => ['VALUE' => $v[0], 'XML_ID' => $v[1], 'SORT' => 10 * ($i + 1)], $enums, array_keys($enums))]);
            $id or die("ошибка свойства {$code}\n");
            $p = ['ID' => $id];
        }
    }
    if (!$p) {
        continue;
    }
    $pid[$code] = (int)$p['ID'];
    // в фильтре — только в своём разделе (и его подразделах)
    $link = \Bitrix\Iblock\SectionPropertyTable::getList(['filter' => ['IBLOCK_ID' => $ib, 'PROPERTY_ID' => $p['ID'], 'SECTION_ID' => $secId]])->fetch();
    if (!$link || $link['SMART_FILTER'] !== 'Y') {
        $say("  {$code}: в фильтр раздела {$secId}");
        if ($apply) {
            $link and CIBlockSectionPropertyLink::Delete($secId, $p['ID']);
            CIBlockSectionPropertyLink::Add($secId, $p['ID'], ['SMART_FILTER' => 'Y', 'IBLOCK_ID' => $ib]);
        }
    }
}

$enumId = function (string $code, string $xml) use ($pid): int {
    return (int)(CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $pid[$code] ?? 0, 'XML_ID' => $xml])->Fetch()['ID'] ?? 0);
};
$fill = function (array $el, array $set) use ($ib, $apply, $say, $enumId): void {
    $cur = [];
    $r = CIBlockElement::GetProperty($ib, $el['ID'], [], []);
    while ($x = $r->Fetch()) {
        isset($set[$x['CODE']]) && $x['VALUE'] and $cur[$x['CODE']] = true;
    }
    foreach ($set as $code => $xml) {
        if (empty($cur[$code])) {
            $say("  {$el['NAME']}: {$code} = {$xml}");
            $apply and ($e = $enumId($code, $xml)) and CIBlockElement::SetPropertyValuesEx($el['ID'], $ib, [$code => $e]);
        }
    }
};
foreach ($values as $id => $set) {
    $el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ib, 'ID' => $id, 'SECTION_ID' => $machines, 'INCLUDE_SUBSECTIONS' => 'Y'], false, false, ['ID', 'NAME'])->Fetch();
    $el ? $fill($el, $set) : $say("нет кофемашины {$id}");
}
foreach ($accValues as $name => $set) {
    $el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ib, 'SECTION_ID' => $acc, 'INCLUDE_SUBSECTIONS' => 'Y', 'NAME' => $name . '%'], false, false, ['ID', 'NAME'])->Fetch();
    $el ? $fill($el, $set) : $say("нет товара «{$name}»");
}
if ($apply) {
    // фасетный индекс фильтра — пересобрать, иначе новые свойства в нём не видны
    $idx = \Bitrix\Iblock\PropertyIndex\Manager::createIndexer($ib);
    $idx->startIndex();
    $idx->continueIndex(0);
    $idx->endIndex();
    \Bitrix\Iblock\PropertyIndex\Manager::checkAdminNotification();
    CIBlock::clearIblockTagCache($ib);
    BXClearCache(true);
}
echo "done\n";
