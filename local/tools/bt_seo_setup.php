<?php
// Агент пересборки sitemap.xml (раз в сутки) и первая сборка. Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_seo_setup.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$apply = ($argv[1] ?? 'show') === 'apply';
if (!CAgent::GetList([], ['NAME' => 'bt_sitemap_build();'])->Fetch()) {
    echo ($apply ? '' : '[show] ') . "агент bt_sitemap_build() раз в сутки\n";
    $apply and CAgent::AddAgent('bt_sitemap_build();', '', 'N', 86400, '', 'Y', ConvertTimeStamp(time() + 3600, 'FULL'));
}
if ($apply) {
    bt_sitemap_build();
    echo 'sitemap.xml: ' . substr_count(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml'), '<url>') . " адресов\n";
}
echo "done\n";
