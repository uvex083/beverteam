<?php
// Журнал на /blog/: рубрики — разделы инфоблока journal, статья /blog/<код>/, рубрика /blog/<код рубрики>/.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_setup.php [show|apply]

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
$ibId = bt_iblock('journal');
$ib = CIBlock::GetByID($ibId)->Fetch();
if (!$ib) {
    die("нет инфоблока journal\n");
}

$urls = ['LIST_PAGE_URL' => '/blog/', 'SECTION_PAGE_URL' => '/blog/#SECTION_CODE#/', 'DETAIL_PAGE_URL' => '/blog/#ELEMENT_CODE#/'];
if (array_diff_assoc($urls, array_intersect_key($ib, $urls))) {
    $say('адреса инфоблока: ' . implode(', ', $urls));
    $apply and (new CIBlock())->Update($ibId, $urls + ['SECTIONS_NAME' => 'Рубрики', 'SECTION_NAME' => 'Рубрика']);
}

$seo = [
    'SECTION_META_TITLE' => '{=this.Name} — журнал BEVERTEAM',
    'SECTION_META_DESCRIPTION' => '{=this.Name}: статьи и советы от BEVERTEAM — магазина кофе, чая и сервисного центра кофемашин в Екатеринбурге.',
    'SECTION_PAGE_TITLE' => '{=this.Name}',
];
$tpl = new \Bitrix\Iblock\InheritedProperty\IblockTemplates($ibId);
$have = array_map(fn($t) => $t['TEMPLATE'], $tpl->findTemplates());
if (array_diff_assoc($seo, array_intersect_key($have, $seo))) {
    $say('SEO-шаблоны рубрик');
    $apply and $tpl->set($seo);
}

$rubrics = [
    ['Уход за кофемашиной', 'uhod-za-kofemashinoy', 10],
    ['Новости', 'novosti', 100],
];
$secId = [];
foreach ($rubrics as [$name, $code, $sort]) {
    $s = CIBlockSection::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($s) {
        $secId[$name] = (int)$s['ID'];
        continue;
    }
    $say("рубрика «{$name}» /blog/{$code}/");
    if ($apply) {
        $bs = new CIBlockSection();
        $secId[$name] = (int)$bs->Add(['IBLOCK_ID' => $ibId, 'NAME' => $name, 'CODE' => $code, 'SORT' => $sort, 'ACTIVE' => 'Y']) ?: die($bs->LAST_ERROR . "\n");
    }
}

// Статья без рубрики — в рубрику по свойству «Рубрика», новость — в «Новости»
$r = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, 'SECTION_ID' => false], false, false, ['ID', 'NAME', 'PROPERTY_KIND', 'PROPERTY_RUBRIC']);
while ($el = $r->Fetch()) {
    $kind = $el['PROPERTY_KIND_ENUM_ID'] ? (CIBlockPropertyEnum::GetByID($el['PROPERTY_KIND_ENUM_ID'])['XML_ID'] ?? '') : '';
    $rub = $kind === 'news' ? 'Новости' : trim((string)$el['PROPERTY_RUBRIC_VALUE']);
    if (!$rub) {
        echo "без рубрики: {$el['ID']} {$el['NAME']}\n";
        continue;
    }
    $say("«{$el['NAME']}» → {$rub}");
    if ($apply) {
        $sid = $secId[$rub] ?? 0;
        if (!$sid) {
            echo "  нет рубрики «{$rub}» — добавьте её в \$rubrics\n";
            continue;
        }
        CIBlockElement::SetElementSection($el['ID'], [$sid]);
        \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($ibId, $el['ID']);
    }
}
// Кнопка «Все материалы» блока журнала на главной
$main = bt_iblock('main_journal');
$el = CIBlockElement::GetList([], ['IBLOCK_ID' => $main, 'PROPERTY_BTN_LINK' => '/news/'], false, false, ['ID'])->Fetch();
if ($el) {
    $say('главная: ссылка блока журнала → /blog/');
    $apply and CIBlockElement::SetPropertyValuesEx($el['ID'], $main, ['BTN_LINK' => '/blog/']);
    $apply and CIBlock::clearIblockTagCache($main);
}
if ($apply) {
    CIBlock::clearIblockTagCache($ibId);
}
echo "done\n";
