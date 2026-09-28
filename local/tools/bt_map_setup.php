<?php
// «Контакты и реквизиты»: поле «Точка на карте» (привязка к Яндекс.Карте) — точку ставят кликом по карте в админке, её используют все карты сайта.
// Для карты в админке нужен ключ Яндекс.Карт: Настройки → Настройки модулей → Управление структурой → «Ключ API Яндекс.Карт».
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_map_setup.php [show|apply]. Повторный запуск ничего не меняет.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$ib = bt_iblock('site_contacts');
if (!CIBlockProperty::GetList([], ['IBLOCK_ID' => $ib, 'CODE' => 'MAP'])->Fetch()) {
    echo ($apply ? '' : '[show] ') . "свойство MAP «Точка на карте», значение — ул. Колокольная, 31А\n";
    if ($apply) {
        (new CIBlockProperty())->Add(['IBLOCK_ID' => $ib, 'CODE' => 'MAP', 'NAME' => 'Точка на карте (кликните по карте или впишите «широта,долгота»)',
            'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'map_yandex', 'SORT' => 55, 'ACTIVE' => 'Y']) or die("ошибка свойства\n");
        $el = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ib, 'ACTIVE' => 'Y'], false, ['nTopCount' => 1], ['ID'])->Fetch();
        $el and CIBlockElement::SetPropertyValuesEx($el['ID'], $ib, ['MAP' => '56.78314,60.533999']);
        CIBlock::clearIblockTagCache($ib);
    }
}
echo "done\n";
