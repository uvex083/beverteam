<?php
// Таблица bt_attr для отчёта /seo-otchet/ (входы из поиска, источники заказов и заявок). Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_attr_setup.php [show|apply]
if (PHP_SAPI !== 'cli') {
    die();
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$apply = ($argv[1] ?? 'show') === 'apply';
$conn = \Bitrix\Main\Application::getConnection();
$t = \Bt\Attr\Table::getTableName();
if (!$conn->isTableExists($t)) {
    echo ($apply ? '' : '[show] ') . "создать таблицу $t\n";
    if ($apply) {
        \Bt\Attr\Table::getEntity()->createDbTable();
        $conn->createIndex($t, 'ix_bt_attr_type_created', ['TYPE', 'CREATED']);
        $conn->createIndex($t, 'ix_bt_attr_entity', ['TYPE', 'ENTITY_ID']);
    }
} else {
    echo "таблица $t есть, строк: " . \Bt\Attr\Table::getCount() . "\n";
}
// отчёт закрыт от посетителей — и из поиска по сайту тоже
$mask = COption::GetOptionString('search', 'exclude_mask');
if (!str_contains($mask, '/seo-otchet/*')) {
    echo ($apply ? '' : '[show] ') . "поиск по сайту: исключить /seo-otchet/*\n";
    $apply and COption::SetOptionString('search', 'exclude_mask', rtrim($mask, ';') . ';/seo-otchet/*');
}
echo "процент: " . \Bt\Attr\percent() . ", с доставкой: " . (\Bt\Attr\withDelivery() ? 'да' : 'нет') . "\n";
