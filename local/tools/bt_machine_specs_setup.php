<?php
// Характеристики кофемашин: текст из «Технических характеристик» разложен по свойствам, чтобы таблица была полной.
// Заполняет только пустые свойства. Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_machine_specs_setup.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Iblock\Model\PropertyFeature;
use Bitrix\Iblock\PropertyFeatureTable;

Loader::includeModule('iblock');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");
$ibId = bt_iblock('catalog') or $fail('нет ИБ catalog');

$showDetail = function (int $propId) {
    $f = [];
    foreach (PropertyFeatureTable::getList(['filter' => ['=PROPERTY_ID' => $propId]])->fetchAll() as $r) {
        $f[$r['MODULE_ID'] . ':' . $r['FEATURE_ID']] = ['MODULE_ID' => $r['MODULE_ID'], 'FEATURE_ID' => $r['FEATURE_ID'], 'IS_ENABLED' => $r['IS_ENABLED']];
    }
    $f['iblock:DETAIL_PAGE_SHOW'] = ['MODULE_ID' => 'iblock', 'FEATURE_ID' => 'DETAIL_PAGE_SHOW', 'IS_ENABLED' => 'Y'];
    $f['iblock:LIST_PAGE_SHOW'] ??= ['MODULE_ID' => 'iblock', 'FEATURE_ID' => 'LIST_PAGE_SHOW', 'IS_ENABLED' => 'N'];
    PropertyFeature::setFeatures($propId, array_values($f));
};

$new = [
    'WATER_BOILER' => ['Бойлер воды', 'S', 500],
    'STEAM_BOILER' => ['Бойлер пара', 'S', 500],
    'INSTANT_HOPPERS' => ['Контейнеры для растворимых', 'S', 500],
    'MILK_FRIDGE' => ['Холодильник для молока', 'S', 500],
    'MAINS_VOLTAGE' => ['Напряжение сети', 'S', 500],
    'POWER_CONSUMPTION' => ['Потребляемая мощность, Вт', 'N', 500],
    'MACHINE_WEIGHT' => ['Вес, кг', 'N', 500],
    'FEATURES' => ['Особенности', 'S', 900, 'Y'],
];
$props = [];
$r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId]);
while ($p = $r->Fetch()) {
    $props[$p['CODE']] = $p;
}
foreach ($new as $code => [$name, $type, $sort]) {
    if (isset($props[$code])) {
        continue;
    }
    $say("свойство $code «{$name}»");
    if ($apply) {
        $bp = new CIBlockProperty();
        $id = (int)$bp->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'ACTIVE' => 'Y',
            'PROPERTY_TYPE' => $type, 'MULTIPLE' => $new[$code][3] ?? 'N']);
        $id or $fail("prop $code: {$bp->LAST_ERROR}");
        $showDetail($id);
        $props[$code] = CIBlockProperty::GetByID($id)->Fetch();
    }
}
// уже заполнены у JL05, но в карточке не выводились
foreach (['CAPPUCCINATORE', 'BURR_MATERIAL'] as $code) {
    $id = (int)($props[$code]['ID'] ?? 0) or $fail("нет свойства $code");
    if (!in_array($code, (array)PropertyFeature::getDetailPageShowProperties($ibId, ['CODE' => 'Y']), true)) {
        $say("показывать в карточке: $code");
        $apply && $showDetail($id);
    }
}

$flat = ['BURR_TYPE' => 'Плоские', 'BURR_MATERIAL' => 'Сталь'];
$thermo = ['HEATER_TYPE' => 'Термоблок', 'BOILER_POWER' => 1500];
$fridge = ['MILK_FRIDGE' => 'Опция, 4,5 л'];
$canister = 'Подключение внешней канистры';
$data = [
    'jetinno-bravo' => $flat + ['COFFEE_DOSE' => 24, 'GRINDER' => 'Жернова 64 мм', 'WATER_BOILER' => '700 мл, 1500 Вт', 'STEAM_BOILER' => 'Есть',
        'INSTANT_HOPPERS' => '2 × 1000 г', 'FEATURES' => ['Кран горячей воды (опция)', 'Стимер (опция)']],
    'jetinno-jl-03' => $flat + ['COFFEE_DOSE' => 14, 'GRINDER' => 'Кофемолка Jetinno', 'HEATER_TYPE' => 'Термоблок', 'BOILER_POWER' => 1300,
        'STEAM_BOILER' => 'Термоблок 1300 Вт', 'PUMP_TYPE' => 'Вибрационная', 'SPOUT_HEIGHT' => 140, 'WASTE_CAPACITY' => 10,
        'GRIND_ADJUSTMENT' => 'Да', 'STRENGTH_ADJUSTMENT' => 'Да', 'MILK_CONTAINER' => 'Опция'],
    'jetinno-jl-15-viva' => $flat + $thermo + $fridge + ['COFFEE_DOSE' => 14, 'GRINDER' => 'Кофемолка Jetinno', 'STEAM_BOILER' => 'Термоблок 1300 Вт',
        'FEATURES' => ['Заварочный блок на 14 или 20 г с предсмачиванием и регулировкой сжатия', 'Два напитка одновременно одним нажатием',
            'Датчик наличия зерна', $canister]],
    'jetinno-jl-32' => ['COFFEE_DOSE' => 20, 'GRINDER' => 'Кофемолка Jetinno PGH64', 'WATER_BOILER' => '700 мл, 2700 Вт', 'STEAM_BOILER' => 'Термоблок 1400 Вт',
        'WASTE_CAPACITY' => 40, 'INSTANT_HOPPERS' => '3 л + 2 л', 'MAINS_VOLTAGE' => '220–240 В, 50/60 Гц', 'POWER_CONSUMPTION' => 2700, 'MACHINE_WEIGHT' => 30,
        'FEATURES' => ['Заварочный блок P20 или P14', 'Миксер для растворимых напитков', 'Сброс отходов в дренаж (опция)', $canister]],
    'jetinno-jl33' => $flat + $thermo + $fridge + ['COFFEE_DOSE' => 14, 'GRINDER' => 'Кофемолка Jetinno', 'INSTANT_HOPPERS' => '1,3 л + 1,2 л',
        'CAPPUCCINATORE' => 'Автоматический',
        'FEATURES' => ['Заварочный блок на 14 г, опция 20 г, с предсмачиванием и регулировкой сжатия', 'Съёмные бункеры зерна с индикатором наличия', $canister]],
    'jetinno-jl36' => $flat + $thermo + $fridge + ['COFFEE_DOSE' => 20, 'GRINDER' => 'Кофемолка Jetinno', 'STEAM_BOILER' => 'Термоблок 1300 Вт',
        'CAPPUCCINATORE' => 'Автоматический',
        'FEATURES' => ['Запатентованный заварочный блок до 20 г', 'Съёмные бункеры зерна с индикатором наличия', $canister]],
];

$enum = function (string $code, string $value) use ($props, $fail): int {
    $e = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $props[$code]['ID'], 'VALUE' => $value])->Fetch();
    return (int)($e['ID'] ?? 0) ?: $fail("$code: нет значения «{$value}»");
};
foreach ($data as $elCode => $vals) {
    $el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $elCode], false, false, ['ID', 'IBLOCK_ID'])->GetNextElement();
    $el or $fail("нет товара $elCode");
    $id = (int)$el->GetFields()['ID'];
    $cur = $el->GetProperties();
    $set = [];
    foreach ($vals as $code => $v) {
        $old = $cur[$code]['VALUE'] ?? null;
        if (!($old === null || $old === '' || $old === false || $old === [])) {
            continue;
        }
        if (!$apply && !isset($props[$code]['ID'])) {
            $say("  $elCode: $code = " . (is_array($v) ? implode(' | ', $v) : $v) . ' (новое свойство)');
            continue;
        }
        $set[$code] = ($props[$code]['PROPERTY_TYPE'] ?? '') === 'L' ? $enum($code, (string)$v) : $v;
        $say("  $elCode: $code = " . (is_array($v) ? implode(' | ', $v) : $v));
    }
    if ($apply && $set) {
        CIBlockElement::SetPropertyValuesEx($id, $ibId, $set);
    }
}
if ($apply) {
    CIBlock::clearIblockTagCache($ibId);
}
$say('готово');
