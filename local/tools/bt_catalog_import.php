<?php
// Импорт товаров и аренды из выгрузки старого сайта (_import/products.json + img/).
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_catalog_import.php [show|apply] <папка выгрузки>
// Повторный запуск обновляет поля и свойства, картинки грузит только новым товарам.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Catalog\Model\Product;
use Bitrix\Catalog\Model\Price;
use Bitrix\Iblock\InheritedProperty\ElementTemplates;

Loader::includeModule('iblock');
Loader::includeModule('catalog');
$apply = ($argv[1] ?? 'show') === 'apply';
$dir = rtrim($argv[2] ?? '', '/') . '/';
is_file($dir . 'products.json') or die("нет {$dir}products.json\n");
$products = json_decode(file_get_contents($dir . 'products.json'), true);
$fail = fn(string $s) => die("ERROR: $s\n");

$catId = bt_iblock('catalog') ?: $fail('нет ИБ catalog');
$rentId = bt_iblock('rent') ?: $fail('нет ИБ rent');
$basePrice = (int)(CCatalogGroup::GetBaseGroup()['ID'] ?? 0) ?: $fail('нет базового типа цены');

$sections = [];
$r = CIBlockSection::GetList([], ['IBLOCK_ID' => $catId], false, ['ID', 'CODE']);
while ($s = $r->Fetch()) {
    $sections[$s['CODE']] = (int)$s['ID'];
}

// значения списочных свойств: создаём недостающие
function bt_enum(int $ibId, string $code, string $value, bool $apply): ?int
{
    static $cache = [];
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $key = "$ibId.$code";
    if (!isset($cache[$key])) {
        $cache[$key] = ['prop' => (int)CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => $code])->Fetch()['ID'], 'v' => []];
        $r = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $cache[$key]['prop']]);
        while ($e = $r->Fetch()) {
            $cache[$key]['v'][mb_strtolower($e['VALUE'])] = (int)$e['ID'];
        }
    }
    $lk = mb_strtolower($value);
    if (!isset($cache[$key]['v'][$lk])) {
        $id = $apply ? (int)(new CIBlockPropertyEnum())->Add(['PROPERTY_ID' => $cache[$key]['prop'], 'VALUE' => $value, 'SORT' => 100 + 10 * count($cache[$key]['v'])]) : -1;
        $cache[$key]['v'][$lk] = $id;
    }
    return $cache[$key]['v'][$lk];
}

$html = fn(string $s) => ['VALUE' => ['TYPE' => 'HTML', 'TEXT' => $s]];
$plain2html = fn(string $s) => '<p>' . implode('<br>', array_map('htmlspecialcharsbx', array_filter(array_map('trim', preg_split('/\R/u', $s))))) . '</p>';

// вес из названия: «250 г», «200 грамм», «1 кг», «1кг»
function bt_weight(string $name): ?array
{
    if (preg_match('/(\d+(?:[.,]\d+)?)\s*(кг|г|гр|грамм)\b/ui', $name, $m)) {
        $n = (float)str_replace(',', '.', $m[1]);
        $g = mb_strtolower($m[2]) === 'кг' ? $n * 1000 : $n;
        return [$g, $g >= 1000 ? rtrim(rtrim(number_format($g / 1000, 1, ',', ''), '0'), ',') . ' кг' : (int)$g . ' г'];
    }
    return null;
}

// единица «кг» для кофе
$kg = CCatalogMeasure::getList([], ['CODE' => 166])->Fetch();
$kgId = $kg ? (int)$kg['ID'] : 0;
if (!$kgId && $apply) {
    $kgId = (int)CCatalogMeasure::add(['CODE' => 166, 'MEASURE_TITLE' => 'Килограмм', 'SYMBOL_RUS' => 'кг', 'SYMBOL_INTL' => 'kg', 'IS_DEFAULT' => 'N']);
}

// оптовая сетка известна только для Эфиопии Оромия (beverteam-context.md, раздел 8)
$bulk = ['botanica-efiopiya-oromiya' => [[1, 4, 2687], [5, 9, 2203], [10, 19, 2090], [20, 29, 1990], [30, null, 1940]]];

// аренда: условия из beverteam-context.md (бесплатно от N кг кофе, для кого)
$rentMeta = [
    'jl-05-arenda' => ['machine' => 'jetinno-jl-05', 'kg' => 3, 'aud' => 'Дом и малый офис', 'sort' => 10],
    'jl-15-viva-arenda' => ['machine' => 'jetinno-jl-15-viva', 'kg' => 6, 'aud' => 'Офис', 'sort' => 20],
    'jl-36-arenda' => ['machine' => 'jetinno-jl36', 'kg' => 9, 'aud' => 'Кафе и HoReCa', 'sort' => 30],
];

// характеристики кофе BOTANICA — из описаний товаров на старом сайте (свойствами там не заведены)
$coffee = [
    'botanica-efiopiya-oromiya' => ['country' => 'Эфиопия', 'region' => 'Оромия', 'q' => '82,5', 'notes' => 'чёрный чай, сухофрукты, лимон, шоколад'],
    'botanica-efiopiya-sidamo-1' => ['country' => 'Эфиопия', 'region' => 'Сидамо', 'proc' => 'Мытая', 'q' => '83', 'notes' => 'чёрный чай, цветы, лайм'],
    'botanica-braziliya-santos' => ['country' => 'Бразилия', 'proc' => 'Натуральная', 'notes' => 'шоколад, орехи, какао'],
    'botanica-espresso-smes' => ['mix' => 'Бразилия 80% + робуста 20%', 'notes' => 'шоколад, орехи'],
    'botanica-milk' => ['mix' => 'Арабика 90% + робуста 10%', 'notes' => 'сухофрукты, шоколад, табак'],
    'botanica-vending' => ['mix' => 'Бразилия 50% + робуста 50%', 'notes' => 'какао, шоколад'],
];

$stat = ['new' => 0, 'upd' => 0, 'rent' => 0];
$notes = [];
$sort = 0;
foreach ($products as $p) {
    $sort += 10;
    $code = $p['code'];
    $isRent = in_array('arenda-kofemashin', $p['sections'], true);
    $ibId = $isRent ? $rentId : $catId;
    $imgs = array_values(array_filter(array_map(fn($f) => $dir . 'img/products/' . $code . '/' . $f, $p['images']), 'is_file'));

    $props = [];
    if ($isRent) {
        $m = $rentMeta[$code] ?? $fail("нет условий аренды для $code");
        $props['PRICE_MONTH'] = $p['price'];
        $props['FREE_FROM_KG'] = $m['kg'];
        $props['AUDIENCE'] = $m['aud'];
        $cups = $p['options']['Количество чашек'] ?? '';
        $props['CUPS_PER_DAY'] = preg_match('/\d+/', $cups, $mm) ? (int)$mm[0] : '';
        $mach = CIBlockElement::GetList([], ['IBLOCK_ID' => $catId, '=CODE' => $m['machine']], false, false, ['ID'])->Fetch();
        $props['MACHINE'] = $mach['ID'] ?? '';
        $mach or $notes[] = "аренда $code: машина {$m['machine']} ещё не создана — запустить импорт повторно";
    } else {
        $pr = $p['props'] + $p['options'];
        foreach (['EFFECT' => 'Действие', 'TASTE' => 'Вкус чая'] as $pc => $src) {
            $v = $p['options'][$src] ?? $pr[$src] ?? null;
            $list = is_array($v) ? $v : ($v ? preg_split('/\s*\/\s*/u', $v) : []);
            $props[$pc] = array_values(array_filter(array_map(fn($x) => bt_enum($catId, $pc, $x, $apply), $list)));
        }
        foreach (['TEA_KIND' => 'Вид чая', 'PACKING' => 'Фасовка'] as $pc => $src) {
            $props[$pc] = isset($pr[$src]) ? bt_enum($catId, $pc, $pr[$src], $apply) : '';
        }
        $country = $pr['Страна'] ?? $pr['Страна производителя'] ?? '';
        $props['COUNTRY'] = $country ? bt_enum($catId, 'COUNTRY', $country, $apply) : '';
        $props['REGION'] = $pr['Регион'] ?? '';
        $props['NOTES'] = $pr['Вкус'] ?? '';
        $w = bt_weight($pr['Вес'] ?? '') ?? bt_weight($p['name']);
        $props['NET_WEIGHT'] = $w ? bt_enum($catId, 'NET_WEIGHT', $w[1], $apply) : '';
        $props['STORAGE'] = isset($pr['Хранение']) ? $html($plain2html($pr['Хранение'])) : '';
        $specs = $pr['Технические характеристики'] ?? '';
        $props['TECH_SPECS'] = $specs ? $html($plain2html($specs)) : '';
        $props['DIMENSIONS'] = preg_match('/Габариты\s+([^\n]+)/u', $specs, $mm) ? trim($mm[1]) : '';
        $props['SCREEN'] = preg_match('/Экран\s+([^\n]+)/u', $specs, $mm) ? trim(preg_replace('/\s+/u', ' ', $mm[1])) : '';
        $props['HOW_TO_BREW'] = isset($p['extra_tabs_html']['Как заваривать?']) ? $html($p['extra_tabs_html']['Как заваривать?']) : '';
        $props['HOW_TO_USE'] = isset($p['extra_tabs_html']['Как использовать']) ? $html($p['extra_tabs_html']['Как использовать']) : '';
        $old = (float)($p['old_price'] ?? 0);
        $props['OLD_PRICE'] = $old > $p['price'] ? $old : '';
        if ($old && $old <= $p['price']) {
            $notes[] = "$code: старая цена $old не больше текущей {$p['price']} — не перенесена";
        }
        // кратко для карточки: страна · вид · действие
        $short = array_filter([$country, $pr['Вид чая'] ?? '', is_string($pr['Действие'] ?? null) ? $pr['Действие'] : '']);
        $props['SHORT_DESC'] = implode(' · ', $short);
        $props['BADGES'] = $props['OLD_PRICE'] ? [bt_enum($catId, 'BADGES', 'Скидка', $apply)] : [];
    }

    $secCode = basename($p['sections'][0]);
    if (isset($coffee[$code])) {
        $c = $coffee[$code];
        $props['ROAST'] = bt_enum($catId, 'ROAST', $secCode === 'filtr-kofe' ? 'Под фильтр' : 'Под эспрессо', $apply);
        $props['COUNTRY'] = isset($c['country']) ? bt_enum($catId, 'COUNTRY', $c['country'], $apply) : '';
        $props['PROCESSING'] = isset($c['proc']) ? bt_enum($catId, 'PROCESSING', $c['proc'], $apply) : '';
        $props['REGION'] = $c['region'] ?? '';
        $props['MIX'] = $c['mix'] ?? '';
        $props['Q_SCORE'] = $c['q'] ?? '';
        $props['NOTES'] = $c['notes'];
        $props['SHORT_DESC'] = implode(' · ', array_filter([$c['country'] ?? $c['mix'] ?? '', $c['proc'] ?? '', isset($c['q']) ? 'Q ' . $c['q'] : '', $c['notes']]));
    }
    $fields = [
        'IBLOCK_ID' => $ibId, 'NAME' => $p['name'], 'CODE' => $code, 'XML_ID' => (string)$p['sku'], 'ACTIVE' => 'Y',
        'SORT' => $isRent ? $rentMeta[$code]['sort'] : $sort,
        'DETAIL_TEXT' => $p['description_html'] ?? '', 'DETAIL_TEXT_TYPE' => 'html',
    ];
    if (!$isRent) {
        $fields['IBLOCK_SECTION_ID'] = $sections[$secCode] ?? $fail("нет раздела $secCode для $code");
        // товар бывает в нескольких разделах (улуны — и в «Зелёном чае»), основной — первый
        $fields['IBLOCK_SECTION'] = array_values(array_unique(array_map(fn($c) => $sections[basename($c)] ?? $fail("нет раздела $c"), $p['sections'])));
    }

    $ex = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code], false, false, ['ID', 'DETAIL_PICTURE'])->Fetch();
    $isRent ? $stat['rent']++ : ($ex ? $stat['upd']++ : $stat['new']++);
    if (!$apply) {
        continue;
    }
    $el = new CIBlockElement();
    // картинки грузим только тем, у кого их ещё нет — повторный запуск не плодит копии файлов
    $needImg = $imgs && !($ex['DETAIL_PICTURE'] ?? 0);
    if ($needImg) {
        $fields['DETAIL_PICTURE'] = CFile::MakeFileArray($imgs[0]);
        $fields['PREVIEW_PICTURE'] = CFile::MakeFileArray($imgs[0]);
        if (count($imgs) > 1) {
            $props['MORE_PHOTO'] = array_map(fn($f) => ['VALUE' => CFile::MakeFileArray($f), 'DESCRIPTION' => ''], array_slice($imgs, 1));
        }
    }
    if ($ex) {
        $id = (int)$ex['ID'];
        $el->Update($id, $fields) or $fail("update $code: {$el->LAST_ERROR}");
    } else {
        $id = (int)$el->Add($fields) or $fail("add $code: {$el->LAST_ERROR}");
    }
    CIBlockElement::SetPropertyValuesEx($id, $ibId, $props);

    // SEO со старого сайта
    $seo = array_filter(['ELEMENT_META_TITLE' => $p['seo_title'] ?? '', 'ELEMENT_META_DESCRIPTION' => $p['meta_description'] ?? '', 'ELEMENT_META_KEYWORDS' => $p['meta_keywords'] ?? '']);
    if ($seo) {
        (new ElementTemplates($ibId, $id))->set($seo);
    }

    if ($isRent) {
        continue;
    }
    // товар торгового каталога: всегда доступен к покупке (складской учёт выключен)
    $w = bt_weight($p['name']);
    $pf = ['QUANTITY_TRACE' => 'N', 'CAN_BUY_ZERO' => 'Y', 'AVAILABLE' => 'Y', 'WEIGHT' => $w[0] ?? 0,
        'MEASURE' => ($secCode === 'dlya-espresso' || $secCode === 'filtr-kofe') && $kgId ? $kgId : null];
    $pf = array_filter($pf, fn($v) => $v !== null);
    $r = Product::getList(['filter' => ['=ID' => $id], 'select' => ['ID']])->fetch()
        ? Product::update($id, $pf) : Product::add(['ID' => $id] + $pf);
    $r->isSuccess() or $fail("product $code: " . implode('; ', $r->getErrorMessages()));

    // цены: одна базовая или оптовая сетка по количеству
    $old = Price::getList(['filter' => ['=PRODUCT_ID' => $id, '=CATALOG_GROUP_ID' => $basePrice], 'select' => ['ID']]);
    while ($o = $old->fetch()) {
        Price::delete($o['ID']);
    }
    foreach ($bulk[$code] ?? [[null, null, $p['price']]] as [$from, $to, $price]) {
        $r = Price::add(['PRODUCT_ID' => $id, 'CATALOG_GROUP_ID' => $basePrice, 'PRICE' => $price, 'CURRENCY' => 'RUB', 'QUANTITY_FROM' => $from, 'QUANTITY_TO' => $to]);
        $r->isSuccess() or $fail("price $code: " . implode('; ', $r->getErrorMessages()));
    }
}

// SEO разделов со старого сайта (настраивал сеошник): H1, title, description
if (is_file($dir . 'sections.json')) {
    foreach (json_decode(file_get_contents($dir . 'sections.json'), true) as $sec) {
        $sid = $sections[basename($sec['code'])] ?? 0;
        $seo = array_filter(['SECTION_PAGE_TITLE' => $sec['h1'] ?? '', 'SECTION_META_TITLE' => $sec['seo_title'] ?? '', 'SECTION_META_DESCRIPTION' => $sec['meta_description'] ?? '']);
        if (!$sid || !$seo) {
            continue;
        }
        if ($apply) {
            (new \Bitrix\Iblock\InheritedProperty\SectionTemplates($catId, $sid))->set($seo);
        } else {
            echo "[show] SEO раздела {$sec['code']}: {$seo['SECTION_PAGE_TITLE']}\n";
        }
    }
}

echo ($apply ? '' : '[show] ') . "каталог: новых {$stat['new']}, обновить {$stat['upd']}; аренда: {$stat['rent']}\n";
foreach (array_unique($notes) as $n) {
    echo "  ! $n\n";
}
if ($apply) {
    CIBlock::clearIblockTagCache($catId);
    CIBlock::clearIblockTagCache($rentId);
}
echo "done\n";
