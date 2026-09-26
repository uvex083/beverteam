<?php
// Контентные инфоблоки: журнал, отзывы, бренды ремонта; названия типов ИБ как разделы сайта. Перенос реального контента старого сайта.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_content_setup.php [show|apply] <папка выгрузки>

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

Loader::includeModule('iblock');
$apply = ($argv[1] ?? 'show') === 'apply';
$dir = rtrim($argv[2] ?? '', '/') . '/';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

// ---------- типы ИБ: название = раздел сайта, сортировка = порядок в админке ----------
$types = [
    'catalog' => ['Магазин', 10],
    'services' => ['Аренда и сервис', 20],
    'news' => ['Журнал', 30],
    'about' => ['О компании', 40],
    'site' => ['Сайт: общие блоки', 900],
];
foreach ($types as $id => [$name, $sort]) {
    $t = CIBlockType::GetByIDLang($id, 'ru');
    if ($t && $t['NAME'] === $name && (int)$t['SORT'] === $sort) {
        continue;
    }
    $say("тип ИБ $id → «{$name}»");
    if (!$apply) {
        continue;
    }
    $f = ['SORT' => $sort, 'LANG' => ['ru' => ['NAME' => $name, 'ELEMENT_NAME' => 'Элемент', 'SECTION_NAME' => 'Раздел']]];
    $o = new CIBlockType();
    ($t ? $o->Update($id, $f) : $o->Add(['ID' => $id, 'SECTIONS' => 'Y', 'IN_RSS' => 'N'] + $f)) or $fail("type $id");
}
// пустой тип демо-решения «Торговые предложения»
if (CIBlockType::GetByID('offers')->Fetch() && !CIBlock::GetList([], ['TYPE' => 'offers', 'CHECK_PERMISSIONS' => 'N'])->Fetch()) {
    $say('удалить пустой тип ИБ offers');
    $apply and CIBlockType::Delete('offers');
}

function bt_iblock_ensure(array $f, array $props, bool $apply, callable $say, callable $fail): int
{
    $ib = CIBlock::GetList([], ['=CODE' => $f['CODE'], 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    $id = (int)($ib['ID'] ?? 0);
    if (!$id) {
        $say("создать ИБ {$f['CODE']} «{$f['NAME']}»");
        if ($apply) {
            $o = new CIBlock();
            $id = (int)$o->Add($f + ['SITE_ID' => ['s1'], 'ACTIVE' => 'Y', 'GROUP_ID' => ['2' => 'R'], 'VERSION' => 2]) or $fail("iblock {$f['CODE']}: {$o->LAST_ERROR}");
        }
    }
    $have = [];
    if ($id) {
        $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $id]);
        while ($p = $r->Fetch()) {
            $have[$p['CODE']] = 1;
        }
    }
    $sort = 100;
    foreach ($props as $code => [$name, $type, $extra]) {
        $sort += 10;
        if (isset($have[$code])) {
            continue;
        }
        $say("  свойство $code «{$name}»");
        if ($apply) {
            [$pt, $ut] = array_pad(explode(':', $type), 2, null);
            $bp = new CIBlockProperty();
            $bp->Add(['IBLOCK_ID' => $id, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'PROPERTY_TYPE' => $pt, 'USER_TYPE' => $ut, 'ACTIVE' => 'Y'] + $extra)
                or $fail("prop $code: {$bp->LAST_ERROR}");
        }
    }
    return $id;
}

$transCode = ['CODE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ['UNIQUE' => 'Y', 'TRANSLITERATION' => 'Y', 'TRANS_LEN' => 100, 'TRANS_CASE' => 'L', 'TRANS_SPACE' => '-', 'TRANS_OTHER' => '-', 'TRANS_EAT' => 'Y']]];

$journalId = bt_iblock_ensure([
    'IBLOCK_TYPE_ID' => 'news', 'CODE' => 'journal', 'API_CODE' => 'Journal', 'NAME' => 'Статьи и новости', 'SORT' => 10,
    'LIST_PAGE_URL' => '/news/', 'DETAIL_PAGE_URL' => '/news/#ELEMENT_CODE#/', 'INDEX_ELEMENT' => 'Y', 'FIELDS' => $transCode,
], [
    'KIND' => ['Тип материала', 'L', ['VALUES' => [['VALUE' => 'Статья', 'XML_ID' => 'article', 'DEF' => 'Y'], ['VALUE' => 'Новость', 'XML_ID' => 'news']]]],
    'RUBRIC' => ['Рубрика', 'S', []],
    'READ_TIME' => ['Время чтения, мин', 'N', []],
], $apply, $say, $fail);

$reviewsId = bt_iblock_ensure([
    'IBLOCK_TYPE_ID' => 'about', 'CODE' => 'reviews', 'API_CODE' => 'Reviews', 'NAME' => 'Отзывы', 'SORT' => 20,
    'LIST_PAGE_URL' => '/otzyvy-o-nas/', 'INDEX_ELEMENT' => 'N',
], [
    'RATING' => ['Оценка, 1–5', 'N', []],
    'PRODUCT' => ['Товар (для отзыва о товаре)', 'E', ['LINK_IBLOCK_ID' => bt_iblock('catalog')]],
    'MACHINE' => ['На какой машине готовили', 'S', []],
    'PHOTOS' => ['Фото', 'F', ['MULTIPLE' => 'Y', 'FILE_TYPE' => 'jpg, jpeg, png, webp']],
    'VERIFIED' => ['Подтверждённая покупка', 'L', ['LIST_TYPE' => 'C', 'VALUES' => [['VALUE' => 'Да', 'XML_ID' => 'Y']]]],
    'EMAIL' => ['E-mail автора (не публикуется)', 'S', []],
], $apply, $say, $fail);

$brandsId = bt_iblock_ensure([
    'IBLOCK_TYPE_ID' => 'services', 'CODE' => 'repair_brands', 'API_CODE' => 'RepairBrands', 'NAME' => 'Ремонт: бренды', 'SORT' => 20,
    'LIST_PAGE_URL' => '/servis/remont-kofemashin/', 'DETAIL_PAGE_URL' => '/servis/remont-kofemashin/#ELEMENT_CODE#/', 'INDEX_ELEMENT' => 'Y', 'FIELDS' => $transCode,
], [
    'MODELS' => ['Модели', 'S', []],
    'NOTE' => ['Особенность ремонта', 'S', []],
    'AUTHORIZED' => ['Авторизованный сервис', 'L', ['LIST_TYPE' => 'C', 'VALUES' => [['VALUE' => 'Да', 'XML_ID' => 'Y']]]],
], $apply, $say, $fail);

if (!$apply) {
    echo "done (show: контент переносится только в apply)\n";
    return;
}

// ---------- контент ----------
function bt_el_ensure(int $ibId, string $code, array $fields, array $props, callable $say, callable $fail): void
{
    $filter = ['IBLOCK_ID' => $ibId] + ($code !== '' ? ['=CODE' => $code] : ['=NAME' => $fields['NAME']]);
    if (CIBlockElement::GetList([], $filter, false, false, ['ID'])->Fetch()) {
        return;
    }
    $say("  + {$fields['NAME']}");
    $o = new CIBlockElement();
    $id = (int)$o->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code ?: false] + $fields) or $fail("element {$fields['NAME']}: {$o->LAST_ERROR}");
    $props and CIBlockElement::SetPropertyValuesEx($id, $ibId, $props);
}

// статьи старого сайта
foreach (glob($dir . 'pages/news__*.json') as $f) {
    $a = json_decode(file_get_contents($f), true);
    $code = substr(basename($f, '.json'), 6);
    $date = $a['date'] ?? null;
    bt_el_ensure($journalId, $code, [
        'NAME' => $a['h1'], 'ACTIVE' => 'Y', 'ACTIVE_FROM' => $date ? ConvertTimeStamp(strtotime($date), 'FULL') : false,
        'DETAIL_TEXT' => $a['content_html'], 'DETAIL_TEXT_TYPE' => 'html',
        'PREVIEW_TEXT' => mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($a['content_html']))), 0, 220) . '…',
        'IPROPERTY_TEMPLATES' => array_filter(['ELEMENT_META_TITLE' => $a['title'] ?? '', 'ELEMENT_META_DESCRIPTION' => $a['meta_description'] ?? '']),
    ], ['KIND' => CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $journalId, 'CODE' => 'KIND', 'XML_ID' => 'article'])->Fetch()['ID'] ?? false,
        'RUBRIC' => 'Уход за кофемашиной', 'READ_TIME' => max(1, (int)round(mb_strlen(strip_tags($a['content_html'])) / 1200))], $say, $fail);
}

// отзывы о компании со старого сайта (без дат и оценок — их там нет)
if (is_file($dir . 'reviews.json')) {
    $sort = 0;
    foreach (json_decode(file_get_contents($dir . 'reviews.json'), true) as $r) {
        $sort += 10;
        bt_el_ensure($reviewsId, '', ['NAME' => $r['author'], 'ACTIVE' => 'Y', 'SORT' => $sort, 'PREVIEW_TEXT' => $r['text']], [], $say, $fail);
    }
}

// бренды ремонта: подтверждён только Jetinno (авторизованный сервис), остальные выключены до ответа клиента
$brands = [
    ['jetinno', 'Jetinno', 'JL05, JL15 VIVA, JL32, JL33, JL36', 'авторизованный сервис, оригинальные запчасти на складе', 'Y'],
    ['saeco', 'Saeco', 'Lirika, Aulika, Royal, Idea', 'частая проблема — заварочный блок и помпа', 'N'],
    ['jura', 'Jura', 'WE8, X8, E6, Giga', 'нужен фирменный сервис-режим, работаем с ним', 'N'],
    ['nuova-simonelli', 'Nuova Simonelli', 'Appia, Aurelia, Musica', 'рожковые для кафе: группы, бойлер, теплообменник', 'N'],
    ['wmf', 'WMF', '1100S, 1500S, 5000S', 'молочные системы и автопромывка', 'N'],
    ['delonghi', "De'Longhi", 'Magnifica, Dinamica, Eletta', 'бытовые автоматы, ремонт и чистка на дому', 'N'],
];
$authEnum = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $brandsId, 'CODE' => 'AUTHORIZED'])->Fetch()['ID'] ?? false;
foreach ($brands as $i => [$code, $name, $models, $note, $active]) {
    bt_el_ensure($brandsId, $code, ['NAME' => $name, 'ACTIVE' => $active, 'SORT' => ($i + 1) * 10], [
        'MODELS' => $models, 'NOTE' => $note, 'AUTHORIZED' => $code === 'jetinno' ? $authEnum : false,
    ], $say, $fail);
}

echo "done\n";
