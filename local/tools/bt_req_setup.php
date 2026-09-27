<?php
// Реквизиты для «Контактов» и карточки предприятия в PDF: поля в ИБ «Контакты и реквизиты».
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_req_setup.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$ibId = bt_iblock('site_contacts');
$props = [
    ['LEGAL_ADDRESS', 'Юридический адрес', 'S', 1110],
    ['BANK', 'Банк', 'S', 1120],
    ['BIK', 'БИК', 'S', 1130],
    ['RS', 'Расчётный счёт', 'S', 1140],
    ['KS', 'Корреспондентский счёт', 'S', 1150],
    ['REQ_FILE', 'Файл реквизитов (если загружен — скачивается он, иначе PDF собирается из полей)', 'F', 1160],
];
foreach ($props as [$code, $name, $type, $sort]) {
    if (CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => $code])->Fetch()) {
        continue;
    }
    echo ($apply ? '' : '[show] ') . "свойство {$code} «{$name}»\n";
    if ($apply) {
        $p = new CIBlockProperty();
        $p->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code, 'NAME' => $name, 'PROPERTY_TYPE' => $type, 'SORT' => $sort, 'ACTIVE' => 'Y',
            'FILE_TYPE' => $type === 'F' ? 'pdf, doc, docx' : '']) ?: die($p->LAST_ERROR . "\n");
    }
}
$apply and CIBlock::clearIblockTagCache($ibId);
echo "done\n";
