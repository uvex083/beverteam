<?php
// Ключ API DaData для подсказок адреса на оформлении заказа — в опции bt/dadata_key, не в коде и не в гите.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_dadata_setup.php <файл с ключом> (файл удаляется после чтения) | show

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$arg = $argv[1] ?? 'show';
if ($arg !== 'show' && is_file($arg)) {
    $key = trim((string)file_get_contents($arg));
    unlink($arg);
    COption::SetOptionString('bt', 'dadata_key', $key);
}
echo 'dadata_key: ' . (strlen(COption::GetOptionString('bt', 'dadata_key')) ?: 'не задан') . " символов\n";
