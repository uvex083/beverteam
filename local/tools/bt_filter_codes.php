<?php
// Понятные коды значений списков каталога для ЧПУ фильтра (вместо хешей Битрикса).
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_filter_codes.php [show|apply]. Повторный запуск ничего не меняет.
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');
$apply = ($argv[1] ?? 'show') === 'apply';
$r = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => bt_iblock('catalog'), 'PROPERTY_TYPE' => 'L']);
while ($p = $r->Fetch()) {
    $hash = 0;
    $e = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $p['ID']]);
    while ($x = $e->Fetch()) {
        $hash += (int)preg_match('~^[0-9a-f]{32}$~', (string)$x['XML_ID']);
    }
    echo $p['CODE'], ': с хешем ', $hash, $apply && $hash ? ', заменено ' . bt_enum_codes((int)$p['ID']) : '', "\n";
}
if ($apply) {
    BXClearCache(true);
    $GLOBALS['CACHE_MANAGER']->CleanAll();
}
