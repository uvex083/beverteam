<?php
// Описания товаров: абзац из строк через <br> (характеристики построчно) → маркированный список <ul><li>.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_desc_lists.php [show|apply]. Повторный запуск ничего не меняет.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$toList = fn(string $html) => preg_replace_callback('~<p\b[^>]*>((?:(?!</p>).)*?<br\s*/?>(?:(?!</p>).)*?<br\s*/?>(?:(?!</p>).)*)</p>~isu', function ($m) {
    $items = array_filter(array_map('trim', preg_split('~<br\s*/?>~i', $m[1])), fn($s) => trim(strip_tags(str_replace('&nbsp;', ' ', $s))) !== '');
    return "<ul>\n" . implode("\n", array_map(fn($s) => '<li>' . $s . '</li>', $items)) . "\n</ul>";
}, $html);

$el = new CIBlockElement();
$r = CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock('catalog'), 'DETAIL_TEXT_TYPE' => 'html'], false, false, ['ID', 'NAME', 'DETAIL_TEXT']);
while ($x = $r->Fetch()) {
    $new = $toList((string)$x['DETAIL_TEXT']);
    if ($new === $x['DETAIL_TEXT']) {
        continue;
    }
    echo ($apply ? '' : '[show] ') . "{$x['ID']} {$x['NAME']}\n";
    // только текст — свойства не передаём, чтобы не затереть их
    $apply and ($el->Update($x['ID'], ['DETAIL_TEXT' => $new, 'DETAIL_TEXT_TYPE' => 'html']) or print('  ошибка: ' . $el->LAST_ERROR . "\n"));
}
$apply and CIBlock::clearIblockTagCache(bt_iblock('catalog'));
echo "done\n";
