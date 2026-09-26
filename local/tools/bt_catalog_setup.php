<?php
// Каталог и аренда: инфоблоки, свойства, торговый каталог, разделы. Демо-инфоблоки решения удаляются, если пустые.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_catalog_setup.php [show|apply] [путь к sections.json]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Iblock\Model\PropertyFeature;

Loader::includeModule('iblock');
Loader::includeModule('catalog');
$apply = ($argv[1] ?? 'show') === 'apply';
$sectionsFile = $argv[2] ?? '';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

// ---------- демо-инфоблоки решения «Интернет-магазин» ----------
foreach (['clothes_offers', 'clothes', 'news'] as $code) {
    $ib = CIBlock::GetList([], ['=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if (!$ib) {
        continue;
    }
    $cnt = (int)CIBlockElement::GetList([], ['IBLOCK_ID' => $ib['ID']], []);
    if ($cnt) {
        $say("демо-ИБ $code не пустой ($cnt элементов) — пропуск");
        continue;
    }
    $say("удалить пустой демо-ИБ {$ib['ID']} $code «{$ib['NAME']}»");
    if ($apply) {
        if (CCatalog::GetByID($ib['ID'])) {
            CCatalog::Delete($ib['ID']);
        }
        CIBlock::Delete($ib['ID']) or $fail("delete iblock $code");
    }
}

// ---------- создание инфоблока ----------
function bt_ensure_iblock(array $f, bool $apply, callable $say, callable $fail): int
{
    $ib = CIBlock::GetList([], ['=CODE' => $f['CODE'], 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($ib) {
        return (int)$ib['ID'];
    }
    $say("создать ИБ {$f['CODE']} «{$f['NAME']}»");
    if (!$apply) {
        return 0;
    }
    $o = new CIBlock();
    $id = (int)$o->Add($f + ['SITE_ID' => ['s1'], 'ACTIVE' => 'Y', 'GROUP_ID' => ['2' => 'R'], 'VERSION' => 2]);
    $id or $fail("iblock {$f['CODE']}: {$o->LAST_ERROR}");
    return $id;
}

// ---------- свойства: код => [название, тип, доп. поля] ----------
function bt_ensure_props(int $ibId, array $props, bool $apply, callable $say, callable $fail): void
{
    if (!$ibId) {
        foreach ($props as $code => $p) {
            $say("  свойство $code «{$p[0]}»");
        }
        return;
    }
    $have = [];
    $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId]);
    while ($p = $r->Fetch()) {
        $have[$p['CODE']] = (int)$p['ID'];
    }
    $sort = 100;
    foreach ($props as $code => [$name, $type, $extra]) {
        $sort += 10;
        if (isset($have[$code])) {
            continue;
        }
        $say("  свойство $code «{$name}»");
        if (!$apply) {
            continue;
        }
        [$ptype, $utype] = array_pad(explode(':', $type), 2, null);
        $inList = $extra['_list'] ?? 'N';
        unset($extra['_list']);
        $f = ['IBLOCK_ID' => $ibId, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'ACTIVE' => 'Y',
            'PROPERTY_TYPE' => $ptype, 'USER_TYPE' => $utype] + $extra;
        $bp = new CIBlockProperty();
        $id = (int)$bp->Add($f);
        $id or $fail("prop $code: {$bp->LAST_ERROR}");
        // «Настройки свойств»: показывать в карточке и в списке, иначе компоненты свойство не выведут
        PropertyFeature::setFeatures($id, [
            ['MODULE_ID' => 'iblock', 'FEATURE_ID' => 'DETAIL_PAGE_SHOW', 'IS_ENABLED' => 'Y'],
            ['MODULE_ID' => 'iblock', 'FEATURE_ID' => 'LIST_PAGE_SHOW', 'IS_ENABLED' => $inList],
        ]);
    }
}

$multi = ['MULTIPLE' => 'Y'];
$filter = ['SMART_FILTER' => 'Y'];
$html = ['DEFAULT_VALUE' => ['TYPE' => 'HTML', 'TEXT' => '']];

// ---------- каталог ----------
$catId = bt_ensure_iblock([
    'IBLOCK_TYPE_ID' => 'catalog', 'CODE' => 'catalog', 'API_CODE' => 'Catalog', 'NAME' => 'Каталог товаров', 'SORT' => 10,
    'LIST_PAGE_URL' => '/magazin/', 'SECTION_PAGE_URL' => '/magazin/#SECTION_CODE_PATH#/',
    'DETAIL_PAGE_URL' => '/magazin/product/#ELEMENT_CODE#/', 'INDEX_ELEMENT' => 'Y', 'INDEX_SECTION' => 'Y',
    'FIELDS' => ['CODE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ['UNIQUE' => 'Y', 'TRANSLITERATION' => 'Y', 'TRANS_LEN' => 100, 'TRANS_CASE' => 'L', 'TRANS_SPACE' => '-', 'TRANS_OTHER' => '-', 'TRANS_EAT' => 'Y']],
        'SECTION_CODE' => ['IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ['UNIQUE' => 'Y', 'TRANSLITERATION' => 'Y', 'TRANS_LEN' => 100, 'TRANS_CASE' => 'L', 'TRANS_SPACE' => '-', 'TRANS_OTHER' => '-', 'TRANS_EAT' => 'Y']]],
], $apply, $say, $fail);

bt_ensure_props($catId, [
    // общие
    'BADGES' => ['Метки', 'L', $multi + $filter + ['_list' => 'Y', 'VALUES' => [['VALUE' => 'Хит', 'XML_ID' => 'hit'], ['VALUE' => 'Новинка', 'XML_ID' => 'new'], ['VALUE' => 'Скидка', 'XML_ID' => 'sale'], ['VALUE' => 'Топ продаж', 'XML_ID' => 'top']]]],
    'SHORT_DESC' => ['Кратко для карточки в списке', 'S', ['_list' => 'Y', 'HINT' => 'Одна строка: обжарка · состав · вкус']],
    'OLD_PRICE' => ['Старая цена', 'N', ['_list' => 'Y']],
    'ARTICLE' => ['Артикул', 'S', []],
    'MORE_PHOTO' => ['Фото галереи', 'F', $multi + ['FILE_TYPE' => 'jpg, jpeg, png, webp']],
    'NET_WEIGHT' => ['Вес/фасовка', 'L', $filter + ['_list' => 'Y']],
    'COUNTRY' => ['Страна', 'L', $filter],
    'REGION' => ['Регион', 'S', []],
    // чай
    'TEA_KIND' => ['Вид чая', 'L', $filter],
    'TASTE' => ['Вкус', 'L', $multi + $filter],
    'EFFECT' => ['Действие', 'L', $multi + $filter],
    'PACKING' => ['Фасовка', 'L', $filter],
    'STRENGTH' => ['Крепость, шкала 0–100', 'N', ['_list' => 'Y']],
    'AROMA' => ['Аромат, шкала 0–100', 'N', ['_list' => 'Y']],
    // кофе
    'ROAST' => ['Обжарка', 'L', $filter],
    'MIX' => ['Состав', 'S', []],
    'PROCESSING' => ['Обработка', 'L', $filter],
    'Q_SCORE' => ['Оценка Q', 'S', []],
    'NOTES' => ['Дескрипторы вкуса', 'S', []],
    'DENSITY' => ['Плотность, шкала 0–100', 'N', ['_list' => 'Y']],
    'ACIDITY' => ['Кислотность, шкала 0–100', 'N', ['_list' => 'Y']],
    // кофемашины
    'CUPS_PER_DAY' => ['Чашек в день', 'S', ['_list' => 'Y']],
    'DIMENSIONS' => ['Габариты', 'S', []],
    'SCREEN' => ['Экран', 'S', []],
    'TECH_SPECS' => ['Технические характеристики', 'S:HTML', $html],
    // тексты вкладок
    'HOW_TO_BREW' => ['Как заваривать', 'S:HTML', $html],
    'HOW_TO_USE' => ['Как использовать', 'S:HTML', $html],
    'STORAGE' => ['Как хранить', 'S:HTML', $html],
    'RECOMMEND' => ['Рекомендуем к товару', 'E', $multi],
], $apply, $say, $fail);

// свойства с SMART_FILTER должны быть привязаны к корню ИБ, иначе умный фильтр их не видит
if ($catId) {
    if (CIBlock::GetArrayByID($catId, 'SECTION_PROPERTY') !== 'Y') {
        $say('  ИБ каталога: включить настройки свойств по разделам (SECTION_PROPERTY)');
        $apply and (new CIBlock())->Update($catId, ['SECTION_PROPERTY' => 'Y']);
    }
    $links = [];
    foreach (CIBlockSectionPropertyLink::GetArray($catId, 0) as $l) {
        $links[$l['PROPERTY_ID']] = $l['SMART_FILTER'];
    }
    $filterCodes = ['NET_WEIGHT', 'COUNTRY', 'TEA_KIND', 'TASTE', 'EFFECT', 'PACKING', 'ROAST', 'PROCESSING', 'BADGES'];
    $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $catId]);
    while ($p = $r->Fetch()) {
        if (in_array($p['CODE'], $filterCodes, true) && ($links[$p['ID']] ?? '') !== 'Y') {
            $say("  умный фильтр: {$p['CODE']}");
            $apply and CIBlockSectionPropertyLink::Set(0, $p['ID'], ['SMART_FILTER' => 'Y', 'IBLOCK_ID' => $catId]);
        }
    }
}

if ($catId && !CCatalog::GetByID($catId)) {
    $say("ИБ $catId → торговый каталог");
    $apply and (CCatalog::Add(['IBLOCK_ID' => $catId, 'YANDEX_EXPORT' => 'Y']) or $fail('catalog add'));
}
if ($catId) {
    $recId = (int)(CIBlockProperty::GetList([], ['IBLOCK_ID' => $catId, 'CODE' => 'RECOMMEND'])->Fetch()['ID'] ?? 0);
    $recId and (new CIBlockProperty())->Update($recId, ['LINK_IBLOCK_ID' => $catId]);
}

// ---------- аренда ----------
$rentId = bt_ensure_iblock([
    'IBLOCK_TYPE_ID' => 'services', 'CODE' => 'rent', 'API_CODE' => 'Rent', 'NAME' => 'Аренда кофемашин', 'SORT' => 10,
    'LIST_PAGE_URL' => '/arenda-kofemashin/', 'DETAIL_PAGE_URL' => '/arenda-kofemashin/', 'INDEX_ELEMENT' => 'N',
], $apply, $say, $fail);
bt_ensure_props($rentId, [
    'MACHINE' => ['Кофемашина из каталога', 'E', []],
    'PRICE_MONTH' => ['Аренда, ₽ в месяц', 'N', ['_list' => 'Y']],
    'FREE_FROM_KG' => ['Бесплатно при заказе кофе от, кг/мес', 'N', ['_list' => 'Y']],
    'CUPS_PER_DAY' => ['Чашек в день, до', 'N', ['_list' => 'Y']],
    'AUDIENCE' => ['Для кого', 'S', ['_list' => 'Y']],
    'MORE_PHOTO' => ['Фото', 'F', $multi + ['FILE_TYPE' => 'jpg, jpeg, png, webp']],
], $apply, $say, $fail);
if ($rentId && $catId) {
    $mId = (int)(CIBlockProperty::GetList([], ['IBLOCK_ID' => $rentId, 'CODE' => 'MACHINE'])->Fetch()['ID'] ?? 0);
    $mId and (new CIBlockProperty())->Update($mId, ['LINK_IBLOCK_ID' => $catId]);
}

// ---------- разделы каталога ----------
if (is_file($sectionsFile)) {
    $imgBase = dirname($sectionsFile) . '/';
    $sections = json_decode(file_get_contents($sectionsFile), true);
    $ids = [];
    if ($catId) {
        $r = CIBlockSection::GetList([], ['IBLOCK_ID' => $catId], false, ['ID', 'CODE']);
        while ($s = $r->Fetch()) {
            $ids[$s['CODE']] = (int)$s['ID'];
        }
    }
    foreach ($sections as $s) {
        $parts = explode('/', $s['code']);
        $code = end($parts);
        if ($code === 'arenda-kofemashin') {
            continue; // аренда — отдельный инфоблок и своя страница
        }
        $parent = $s['parent'];
        if ($code === 'sredstva-dlya-chistki-kofemashin') {
            $parent = 'aksessuary'; // скрытый раздел старого сайта кладём в аксессуары
        }
        $parentCode = $parent ? basename($parent) : null;
        if (isset($ids[$code])) {
            continue;
        }
        $say("раздел $code «{$s['name']}»" . ($parentCode ? " в $parentCode" : ''));
        if (!$apply) {
            $ids[$code] = -1;
            continue;
        }
        $f = ['IBLOCK_ID' => $catId, 'CODE' => $code, 'NAME' => $s['name'], 'SORT' => (int)$s['sort'], 'ACTIVE' => 'Y',
            'IBLOCK_SECTION_ID' => $parentCode ? ($ids[$parentCode] ?? $fail("parent $parentCode")) : false,
            'DESCRIPTION' => $s['description_html'] ?? '', 'DESCRIPTION_TYPE' => 'html'];
        if (!empty($s['image']) && is_file($imgBase . $s['image'])) {
            $f['PICTURE'] = CFile::MakeFileArray($imgBase . $s['image']);
        }
        $o = new CIBlockSection();
        $ids[$code] = (int)$o->Add($f) or $fail("section $code: {$o->LAST_ERROR}");
    }
}

// фасетный индекс умного фильтра — пересобираем при каждом apply (63 товара, секунды)
if ($apply && $catId) {
    $idx = \Bitrix\Iblock\PropertyIndex\Manager::createIndexer($catId);
    $idx->startIndex();
    $idx->continueIndex(0);
    $idx->endIndex();
    \Bitrix\Iblock\PropertyIndex\Manager::checkAdminNotification();
    CIBlock::clearIblockTagCache($catId);
    echo "фасетный индекс пересобран\n";
}

echo "done\n";
