<?php
// Галочка «Показывать на главной» у кофемашин (каталог), моделей аренды и отзывов. Отмечает то, что сейчас стоит на главной:
// все кофемашины и модели аренды, первые три отзыва. Повторный запуск ничего не меняет.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_show_main_setup.php [show|apply]

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

$targets = [
    'catalog' => ['Показывать на главной (кофемашины)', function (int $ib) {
        $sec = CIBlockSection::GetList([], ['IBLOCK_ID' => $ib, '=CODE' => 'professionalnye-kofemashiny'], false, ['ID'])->Fetch();
        $ids = [];
        $r = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ib, 'ACTIVE' => 'Y', 'SECTION_ID' => (int)($sec['ID'] ?? -1), 'INCLUDE_SUBSECTIONS' => 'Y'], false, false, ['ID']);
        while ($x = $r->Fetch()) {
            $ids[] = (int)$x['ID'];
        }
        return $ids;
    }],
    'rent' => ['Показывать на главной', function (int $ib) {
        $ids = [];
        $r = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ib, 'ACTIVE' => 'Y'], false, false, ['ID']);
        while ($x = $r->Fetch()) {
            $ids[] = (int)$x['ID'];
        }
        return $ids;
    }],
    'reviews' => ['Показывать на главной', function (int $ib) {
        $ids = [];
        $r = CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $ib, 'ACTIVE' => 'Y'], false, ['nTopCount' => 3], ['ID']);
        while ($x = $r->Fetch()) {
            $ids[] = (int)$x['ID'];
        }
        return $ids;
    }],
];
foreach ($targets as $code => [$name, $pick]) {
    $ib = bt_iblock($code);
    $prop = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ib, 'CODE' => 'SHOW_MAIN'])->Fetch();
    if ($prop) {
        continue; // уже настроено — отметки не трогаем, их ставит менеджер
    }
    $say("{$code}: свойство «{$name}»");
    $ids = $pick($ib);
    $say('  отметить: ' . implode(', ', $ids));
    if (!$apply) {
        continue;
    }
    $pid = (int)(new CIBlockProperty())->Add(['IBLOCK_ID' => $ib, 'CODE' => 'SHOW_MAIN', 'NAME' => $name, 'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'C',
        'SORT' => 5, 'ACTIVE' => 'Y', 'VALUES' => [['XML_ID' => 'Y', 'VALUE' => 'Да', 'SORT' => 10]]]);
    $pid or die("ошибка свойства в {$code}\n");
    $yes = (int)CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $pid, 'XML_ID' => 'Y'])->Fetch()['ID'];
    foreach ($ids as $id) {
        CIBlockElement::SetPropertyValuesEx($id, $ib, ['SHOW_MAIN' => $yes]);
    }
    CIBlock::clearIblockTagCache($ib);
}
echo "done\n";
