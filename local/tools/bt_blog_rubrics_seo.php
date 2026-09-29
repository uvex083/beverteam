<?php
// Рубрики журнала: вводный текст под H1 (описание раздела) и title / description (вкладка SEO раздела). Заполняет только пустое.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_rubrics_seo.php [show|apply]

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
$ibId = bt_iblock('journal') or die("нет ИБ journal\n");

// код раздела => [вводный текст, title, description]
$seo = [
    'uhod-za-kofemashinoy' => [
        'Как чистить кофемашину, удалять накипь и ухаживать за заварочным блоком и капучинатором. Разбираем частые неисправности: что можно исправить самому, а когда нужен инженер. Пишем по опыту нашего сервисного центра в Екатеринбурге.',
        'Уход за кофемашиной: чистка, накипь, неисправности — журнал BEVERTEAM',
        'Как чистить кофемашину, удалять накипь, ухаживать за заварочным блоком и капучинатором и что делать при ошибках. Советы сервисного центра BEVERTEAM.',
    ],
    'vybor-kofe' => [
        'Как выбрать зерно под кофемашину и под свой вкус: степень обжарки, помол, арабика и робуста, свежесть и хранение. Рассказываем простым языком на примере кофе BOTANICA.',
        'Как выбрать кофе: обжарка, помол, сорта — журнал BEVERTEAM',
        'Как выбрать зерновой кофе для кофемашины: обжарка, помол, арабика и робуста, эспрессо и американо, хранение зерна. Советы BEVERTEAM на примере BOTANICA.',
    ],
    'dlya-biznesa' => [
        'Кофе в офисе, кафе и точках самообслуживания: как выбрать кофемашину, посчитать нагрузку и расход, что выгоднее — покупка или аренда. Опыт BEVERTEAM в аренде и обслуживании кофемашин Jetinno.',
        'Кофе для бизнеса: кофемашины для офиса и кофейни — журнал BEVERTEAM',
        'Как выбрать кофемашину для офиса, открыть кофейню самообслуживания и какие модели Jetinno подойдут бизнесу. Опыт BEVERTEAM в аренде и сервисе кофемашин.',
    ],
    'chay' => [
        'Как заваривать пуэр, улун и другие чаи: температура воды, время заваривания, посуда и сколько проливов выдерживает лист.',
        'Как заваривать чай: пуэр, улун и другие — журнал BEVERTEAM',
        'Как заваривать пуэр, улун и другие чаи: температура воды, время, посуда и число проливов. Советы и чай из ассортимента BEVERTEAM в Екатеринбурге.',
    ],
    'novosti' => [
        'Новости магазина: новые сорта BOTANICA, условия аренды и доставки, работа сервисного центра.',
        'Новости BEVERTEAM — журнал',
        'Новости магазина BEVERTEAM: новые сорта кофе BOTANICA, аренда кофемашин, доставка по Екатеринбургу и работа сервисного центра.',
    ],
];

$bs = new CIBlockSection();
foreach ($seo as $code => [$intro, $title, $desc]) {
    $s = CIBlockSection::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'NAME', 'DESCRIPTION'])->Fetch();
    if (!$s) {
        echo "нет рубрики $code\n";
        continue;
    }
    if (trim((string)$s['DESCRIPTION']) === '') {
        $say("вводный текст: {$s['NAME']}");
        $apply and $bs->Update($s['ID'], ['DESCRIPTION' => $intro, 'DESCRIPTION_TYPE' => 'text']);
    }
    $tpl = new \Bitrix\Iblock\InheritedProperty\SectionTemplates($ibId, $s['ID']);
    $cur = array_map(fn($x) => $x['TEMPLATE'], array_filter($tpl->findTemplates(), fn($x) => ($x['ENTITY_ID'] ?? '') == $s['ID']));
    $set = array_filter(['SECTION_META_TITLE' => $title, 'SECTION_META_DESCRIPTION' => $desc], fn($v, $k) => trim((string)($cur[$k] ?? '')) === '', ARRAY_FILTER_USE_BOTH);
    if ($set) {
        $say('SEO: ' . $s['NAME'] . ' — ' . implode(', ', array_keys($set)));
        $apply and $tpl->set($set);
    }
}
CIBlock::clearIblockTagCache($ibId);
echo "done\n";
