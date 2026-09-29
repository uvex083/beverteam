<?php
// Статьи журнала из JSON-файлов (по одному на статью): создаются неактивными, вопросы к вычитке — в свойстве EDITOR_NOTES (на сайте не выводится).
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_articles.php [show|apply] <папка с *.json>. Статья с тем же кодом уже есть — не трогаем.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$dir = rtrim((string)($argv[2] ?? ''), '/') . '/';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$ibId = bt_iblock('journal') or die("нет ИБ journal\n");
is_dir($dir) or die("нет папки $dir\n");

// рубрики: код в JSON => [название, символьный код раздела, сортировка]
$rubrics = ['uhod' => ['Уход за кофемашиной', 'uhod-za-kofemashinoy', 10], 'kofe' => ['Выбор кофе', 'vybor-kofe', 20], 'recept' => ['Приготовление кофе', 'prigotovlenie-kofe', 25],
    'biznes' => ['Для бизнеса', 'dlya-biznesa', 30], 'chay' => ['Чай', 'chay', 40]];
$secId = [];
foreach ($rubrics as $k => [$name, $code, $sort]) {
    $s = CIBlockSection::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($s) {
        $secId[$k] = (int)$s['ID'];
        continue;
    }
    $say("рубрика «{$name}»");
    if ($apply) {
        $bs = new CIBlockSection();
        $secId[$k] = (int)$bs->Add(['IBLOCK_ID' => $ibId, 'NAME' => $name, 'CODE' => $code, 'SORT' => $sort, 'ACTIVE' => 'Y']) ?: die($bs->LAST_ERROR . "\n");
    }
}

if (!CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'EDITOR_NOTES'])->Fetch()) {
    $say('свойство EDITOR_NOTES «Вопросы к вычитке (на сайте не выводится)»');
    $apply and ((new CIBlockProperty())->Add(['IBLOCK_ID' => $ibId, 'CODE' => 'EDITOR_NOTES', 'NAME' => 'Вопросы к вычитке (на сайте не выводится)',
        'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'HTML', 'SORT' => 900, 'ACTIVE' => 'Y']) or die("prop EDITOR_NOTES\n"));
}
$kind = (int)(CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'KIND', 'XML_ID' => 'article'])->Fetch()['ID'] ?? 0);

$files = glob($dir . '*.json');
sort($files);
$el = new CIBlockElement();
foreach ($files as $f) {
    $a = json_decode((string)file_get_contents($f), true);
    if (!$a || empty($a['code']) || empty($a['name']) || empty($a['detail'])) {
        echo 'пропуск (не разобран): ' . basename($f) . "\n";
        continue;
    }
    if (CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $a['code']], false, false, ['ID'])->Fetch()) {
        continue;
    }
    $say("статья «{$a['name']}» → {$a['rubric']}");
    if (!$apply) {
        continue;
    }
    $notes = '<ol>' . implode('', array_map(fn($q) => '<li>' . htmlspecialcharsbx($q) . '</li>', (array)($a['questions'] ?? []))) . '</ol>';
    $id = $el->Add([
        'IBLOCK_ID' => $ibId, 'IBLOCK_SECTION_ID' => $secId[$a['rubric']] ?? $secId['kofe'], 'NAME' => $a['name'], 'CODE' => $a['code'], 'ACTIVE' => 'N',
        'ACTIVE_FROM' => ConvertTimeStamp(time(), 'FULL'), 'TAGS' => (string)($a['tags'] ?? ''),
        'PREVIEW_TEXT' => (string)($a['preview'] ?? ''), 'PREVIEW_TEXT_TYPE' => 'text', 'DETAIL_TEXT' => $a['detail'], 'DETAIL_TEXT_TYPE' => 'html',
        'PROPERTY_VALUES' => ['KIND' => $kind, 'READ_TIME' => (int)($a['read_time'] ?? 5), 'EDITOR_NOTES' => ['VALUE' => ['TEXT' => $notes, 'TYPE' => 'HTML']]],
        'IPROPERTY_TEMPLATES' => ['ELEMENT_META_TITLE' => (string)($a['meta_title'] ?? ''), 'ELEMENT_META_DESCRIPTION' => (string)($a['meta_description'] ?? '')],
    ]);
    echo $id ? "  ID {$id}\n" : '  ошибка: ' . $el->LAST_ERROR . "\n";
}
CIBlock::clearIblockTagCache($ibId);
echo "done\n";
