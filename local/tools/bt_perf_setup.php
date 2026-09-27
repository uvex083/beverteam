<?php
// Настройки скорости главного модуля. Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_perf_setup.php [show|apply|rollback]
// apply выставляет значения из $want, rollback возвращает то, что было до первого apply (сохраняется в опции bt/perf_backup)

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Config\Option;

// «Оптимизация CSS/JS»: объединять и брать .min-версии — да; перенос JS в конец body — нет: слайдеры и счётчики шапки
// дорисовывались бы после первой отрисовки (прыжки); gzip-копии не нужны — сжимает nginx; автокеш компонентов — включён
$want = [
    'optimize_css_files' => 'Y',
    'optimize_js_files' => 'Y',
    'use_minified_assets' => 'Y',
    'move_js_to_body' => 'N',
    'compres_css_js_files' => 'N',
    'component_cache_on' => 'Y',
];
if (isset($argv[2]) && preg_match('/^(\w+)=([YN])$/', $argv[2], $m) && isset($want[$m[1]])) {
    $want[$m[1]] = $m[2]; // разовая проверка: apply move_js_to_body=Y
}

$mode = $argv[1] ?? 'show';
$backup = json_decode(Option::get('bt', 'perf_backup', ''), true) ?: null;
foreach ($want as $name => $value) {
    $cur = Option::get('main', $name, '');
    echo str_pad($name, 22) . ' сейчас=' . ($cur === '' ? '(по умолчанию)' : $cur) . ' нужно=' . $value . "\n";
}
if ($mode === 'apply') {
    if (!$backup) {
        Option::set('bt', 'perf_backup', json_encode(array_map(fn($n) => Option::get('main', $n, ''), array_combine(array_keys($want), array_keys($want)))));
    }
    foreach ($want as $name => $value) {
        Option::set('main', $name, $value);
    }
    BXClearCache(true);
    echo "применено, кеш сброшен\n";
} elseif ($mode === 'rollback' && $backup) {
    foreach ($backup as $name => $value) {
        $value === '' ? Option::delete('main', ['name' => $name]) : Option::set('main', $name, $value);
    }
    Option::delete('bt', ['name' => 'perf_backup']);
    BXClearCache(true);
    echo "откачено к состоянию до apply\n";
}
echo "done\n";
