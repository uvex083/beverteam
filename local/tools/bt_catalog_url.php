<?php
// Магазин переехал с /magazin/ на /catalog/: шаблоны адресов инфоблока каталога и ссылки в текстах элементов всех инфоблоков.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_catalog_url.php [show|apply]. Повторный запуск ничего не меняет.

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
$fix = fn(?string $s) => str_replace('/magazin/', '/catalog/', (string)$s);

$ib = CIBlock::GetByID(bt_iblock('catalog'))->Fetch();
$urls = array_intersect_key($ib, array_flip(['LIST_PAGE_URL', 'SECTION_PAGE_URL', 'DETAIL_PAGE_URL']));
if (array_map($fix, $urls) !== $urls) {
    $say('адреса инфоблока каталога → /catalog/');
    $apply and ((new CIBlock())->Update($ib['ID'], array_map($fix, $urls)) or die("ошибка инфоблока\n"));
}

$el = new CIBlockElement();
$r = CIBlockElement::GetList([], [['LOGIC' => 'OR', '%PREVIEW_TEXT' => '/magazin/', '%DETAIL_TEXT' => '/magazin/']], false, false, ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT']);
while ($x = $r->Fetch()) {
    $say("ссылки в тексте: {$x['ID']} {$x['NAME']}");
    // только тексты — свойства не передаём, чтобы не затереть их (Update с частичными свойствами стирает остальные)
    $apply and ($el->Update($x['ID'], ['PREVIEW_TEXT' => $fix($x['PREVIEW_TEXT']), 'DETAIL_TEXT' => $fix($x['DETAIL_TEXT'])]) or print('  ошибка: ' . $el->LAST_ERROR . "\n"));
}
echo "done\n";
