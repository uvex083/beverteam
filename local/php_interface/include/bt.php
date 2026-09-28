<?php

use Bitrix\Main\Loader;

// ID инфоблока по символьному коду — ID на dev и проде могут не совпадать
function bt_iblock(string $code): int
{
    static $ids = [];
    if (!isset($ids[$code])) {
        Loader::includeModule('iblock');
        $ib = \Bitrix\Iblock\IblockTable::getList([
            'filter' => ['=CODE' => $code],
            'select' => ['ID'],
            'cache' => ['ttl' => 86400],
        ])->fetch();
        $ids[$code] = $ib ? (int)$ib['ID'] : 0;
    }
    return $ids[$code];
}

// Картинка для вывода: ресайз ядра (не больше $w×$h) и WebP-копия рядом в resize_cache
function bt_img($file, int $w, int $h, int $mode = BX_RESIZE_IMAGE_PROPORTIONAL): string
{
    $src = $file ? (\CFile::ResizeImageGet($file, ['width' => $w, 'height' => $h], $mode)['src'] ?? '') : '';
    return $src !== '' ? bt_webp($src) : '';
}

// Атрибуты width/height по файлу картинки — место под неё резервируется до загрузки
function bt_img_wh(string $src): string
{
    $s = $src !== '' ? @getimagesize($_SERVER['DOCUMENT_ROOT'] . $src) : false;
    return $s ? ' width="' . $s[0] . '" height="' . $s[1] . '"' : '';
}

// WebP-копия файла из /upload: /upload/resize_cache/<путь>.webp, создаётся при первом обращении; удаляется ядром вместе с resize_cache файла
function bt_webp(string $src): string
{
    if (!preg_match('~^/upload/(?:resize_cache/)?(.+\.(jpe?g|png))$~i', $src, $m)) {
        return $src;
    }
    $dst = '/upload/resize_cache/' . $m[1] . '.webp';
    $abs = $_SERVER['DOCUMENT_ROOT'] . $dst;
    if (is_file($abs)) {
        return $dst;
    }
    $from = $_SERVER['DOCUMENT_ROOT'] . $src;
    $im = is_file($from) && function_exists('imagewebp') ? @(strtolower($m[2]) === 'png' ? imagecreatefrompng($from) : imagecreatefromjpeg($from)) : false;
    if (!$im) {
        return $src;
    }
    imagepalettetotruecolor($im);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    CheckDirPath($abs);
    $tmp = $abs . '.' . getmypid() . '.tmp';
    $ok = imagewebp($im, $tmp, 82) && rename($tmp, $abs);
    imagedestroy($im);
    @unlink($tmp);
    return $ok ? $dst : $src;
}

// Контакты и реквизиты: один элемент инфоблока site_contacts, единый источник для шапки, футера и страниц
function bt_contacts(): array
{
    static $co;
    if ($co !== null) {
        return $co;
    }
    $co = [];
    $ibId = bt_iblock('site_contacts');
    if (!$ibId) {
        return $co;
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_contacts', '/bt/contacts')) {
        return $co = $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/contacts');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $el = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y'], false, ['nTopCount' => 1], ['ID', 'IBLOCK_ID', 'NAME'])->GetNextElement();
    if ($el) {
        foreach ($el->GetProperties() as $code => $p) {
            $co[strtolower($code)] = is_array($p['VALUE']) ? $p['VALUE'] : trim((string)$p['VALUE']);
        }
        foreach (['phone1', 'phone2'] as $k) {
            $co[$k . '_href'] = 'tel:+' . preg_replace('/\D/', '', $co[$k] ?? '');
        }
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($co);
    return $co;
}

// Мессенджеры из контактов: [код иконки, название, ссылка], пустые ссылки не выводятся
// Соцсети и мессенджеры из ИБ socials: [код иконки, название, ссылка, SVG]. Выключенные и без ссылки не выводятся
function bt_messengers(): array
{
    $ibId = bt_iblock('socials');
    if (!$ibId) {
        return [];
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_socials', '/bt/blocks')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/blocks');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $list = [];
    $r = \CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y', '!PROPERTY_LINK' => false], false, false,
        ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_LINK', 'PROPERTY_ICON_SET', 'PROPERTY_ICON']);
    while ($x = $r->Fetch()) {
        $code = (string)($x['PROPERTY_ICON_SET_ENUM_ID'] ? (\CIBlockPropertyEnum::GetByID($x['PROPERTY_ICON_SET_ENUM_ID'])['XML_ID'] ?? '') : '');
        $svg = bt_svg((int)$x['PROPERTY_ICON_VALUE']) ?: bt_icon($code);
        $list[] = [$code, $x['NAME'], trim((string)$x['PROPERTY_LINK_VALUE']), $svg ?: '<b>' . htmlspecialcharsbx(mb_substr($x['NAME'], 0, 1)) . '</b>'];
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($list);
    return $list;
}

function bt_icon(string $name): string
{
    static $icons;
    $icons ??= include $_SERVER['DOCUMENT_ROOT'] . '/local/templates/beverteam/include/icons.php';
    return $icons[$name] ?? '';
}

// Товар из bt_catalog_data() по ID элемента
function bt_product(string $id): ?array
{
    static $index;
    if ($index === null) {
        $index = [];
        foreach (bt_catalog_data() as $list) {
            foreach ($list as $m) {
                $index[$m['id']] = $m;
            }
        }
    }
    return $index[$id] ?? null;
}

function bt_fmt(float $n): string
{
    return number_format($n, 0, '', "\u{00A0}") . "\u{00A0}₽";
}

// Карточка товара — серверная копия BT_card() из ui.js в состоянии «не в корзине»; разметку менять в обоих местах
function bt_card(array $m, bool $eager = false): string
{
    $e = fn($s) => htmlspecialcharsbx((string)$s);
    $badges = !empty($m['badges']) ? '<div class="pc__badges">' . implode('', array_map(fn($b) => '<span class="badge">' . $e($b) . '</span>', $m['badges'])) . '</div>' : '';
    $scales = !empty($m['sc']) ? '<div class="pc__scales">' . implode('', array_map(fn($x) => '<div class="pc__scale"><span>' . $e($x[0]) . '</span><i style="--v:' . (int)$x[1] . '%"></i></div>', $m['sc'])) . '</div>' : '';
    $packs = '';
    if (!empty($m['bulk'])) {
        $first = $m['bulk'][0]['p'];
        foreach ($m['bulk'] as $t) {
            $pct = $t['p'] < $first ? (int)round((1 - $t['p'] / $first) * 100) : 0;
            $packs .= '<button type="button" data-kg="' . $t['kg'] . '" aria-pressed="' . ($t === $m['bulk'][0] ? 'true' : 'false') . '">' . $t['kg'] . ' кг' . ($pct ? '<s>−' . $pct . '%</s>' : '') . '</button>';
        }
        $packs = '<div class="packs " data-packs="' . $e($m['id']) . '">' . $packs . '</div>';
    }
    $rent = !empty($m['rent']);
    $price = $m['p'] ? bt_fmt($m['p']) : 'По запросу';
    $sub = !empty($m['unit']) ? '<s>' . $e($m['unit']) . '</s>' : (!empty($m['bulk']) ? '<s>' . bt_fmt($m['bulk'][0]['p']) . ' за кг · ' . $m['bulk'][0]['kg'] . ' кг</s>' : (!empty($m['pre']) ? '<s>предзаказ</s>' : ''));
    $old = !empty($m['old']) ? '<span class="price--old">' . bt_fmt($m['old']) . '</span>' : '';
    $ctl = $rent ? '<a class="btn btn--sm" href="/arenda-kofemashin/#calc">Арендовать</a>'
        : '<button class="btn btn--sm" data-add="' . $e($m['id'] . (!empty($m['bulk']) ? ':' . $m['bulk'][0]['kg'] : '')) . '">' . (!empty($m['pre']) ? 'Предзаказ' : 'В корзину') . '</button>';
    $stock = !empty($m['stock']);
    return '<article class="pc" data-pc="' . $e($m['id']) . '" itemscope itemtype="https://schema.org/Product">'
        . '<meta itemprop="name" content="' . $e($m['n']) . '"><meta itemprop="image" content="' . $e($m['img']) . '"><meta itemprop="description" content="' . $e($m['par']) . '">'
        . $badges
        . '<div class="pc__acts"><button class="pc__fav" aria-pressed="false" title="В избранное" aria-label="В избранное">' . bt_icon('heart') . '</button>'
        . ($rent ? '' : '<button class="pc__cmpi" aria-pressed="false" title="Сравнить" aria-label="Сравнить">' . bt_icon('compare') . '</button>') . '</div>'
        . '<a class="pc__ph" href="' . $e($m['url']) . '">' . ($m['img'] ? '<img src="' . $e($m['img']) . '" alt="' . $e($m['n']) . '"' . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">' : '') . '</a>'
        . '<h3><a href="' . $e($m['url']) . '" itemprop="url">' . $e($m['n']) . '</a></h3>'
        . '<p class="pc__par">' . $e($m['par']) . '</p>'
        . $scales . $packs
        . '<span class="pc__stock' . ($stock ? '' : ' pc__stock--no') . '">' . ($stock ? 'В наличии' : 'Под заказ') . '</span>'
        . '<div class="pc__foot" itemprop="offers" itemscope itemtype="https://schema.org/Offer">'
        . '<meta itemprop="priceCurrency" content="RUB">' . ($m['p'] ? '<meta itemprop="price" content="' . $m['p'] . '">' : '')
        . '<link itemprop="availability" href="https://schema.org/' . ($stock ? 'InStock' : 'PreOrder') . '">'
        . '<div>' . $old . '<span class="price">' . $price . $sub . '</span></div>' . $ctl . '</div>'
        . '</article>';
}

// Состояние корзины Битрикса для ui.js: {items: {ID товара: количество}, sum}
function bt_basket_state(?\Bitrix\Sale\BasketBase $basket = null): array
{
    if (!Loader::includeModule('sale')) {
        return ['items' => [], 'sum' => 0];
    }
    $basket ??= \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), SITE_ID);
    $items = [];
    foreach ($basket as $bi) {
        if ($bi->canBuy() && !$bi->isDelay()) {
            $kg = bt_basket_pack($bi);
            $items[$bi->getProductId() . ($kg ? ':' . $kg : '')] = $kg ? round($bi->getQuantity() / $kg) : (float)$bi->getQuantity();
        }
    }
    return ['items' => (object)$items, 'sum' => (float)$basket->getPrice()];
}

// Фасовка строки корзины (кофе на развес): 20 — упаковка 20 кг; 0 — штучный товар. Старые строки без свойства — упаковки по 1 кг
function bt_basket_pack(\Bitrix\Sale\BasketItemBase $bi): int
{
    $v = $bi->getPropertyCollection()->getPropertyValues()['PACK']['VALUE'] ?? '';
    return $v !== '' ? (int)$v : (!empty(bt_product((string)$bi->getProductId())['bulk']) ? 1 : 0);
}

// Количество строки для заказа и писем: «2 шт × 20 кг» у кофе на развес, «3 шт» у остального
function bt_basket_qty(\Bitrix\Sale\BasketItemBase $bi): string
{
    $kg = bt_basket_pack($bi);
    return $kg ? round($bi->getQuantity() / $kg) . ' шт × ' . $kg . ' кг' : (float)$bi->getQuantity() . ' шт';
}

// Положить в корзину: $kg — фасовка кофе на развес (каждая фасовка — своя строка, количество в Битриксе — в кг), $q — штук.
// $add — прибавить к тому, что уже лежит (повтор заказа). Возвращает текст ошибки или ''
function bt_basket_put(\Bitrix\Sale\BasketBase $basket, int $id, int $kg, float $q, bool $add = false): string
{
    $m = bt_product((string)$id);
    if (!$m || !\Bitrix\Catalog\ProductTable::getById($id)->fetch()) {
        return 'product';
    }
    $packs = array_column($m['bulk'] ?? [], 'kg');
    $kg = $packs ? (in_array($kg, $packs) ? $kg : (int)$packs[0]) : 0;
    $item = null;
    foreach ($basket as $bi) {
        if ((int)$bi->getProductId() === $id && bt_basket_pack($bi) === $kg) {
            $item = $bi;
            break;
        }
    }
    $q = $add && $item ? round($item->getQuantity() / ($kg ?: 1)) + $q : $q;
    if ($q <= 0) {
        $item?->delete();
        return '';
    }
    if ($item) {
        $r = $item->setField('QUANTITY', $q * ($kg ?: 1));
        return $r->isSuccess() ? '' : implode('; ', $r->getErrorMessages());
    }
    $item = $basket->createItem('catalog', $id);
    $r = $item->setFields(['QUANTITY' => $q * ($kg ?: 1), 'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(), 'LID' => SITE_ID,
        'PRODUCT_PROVIDER_CLASS' => \Bitrix\Catalog\Product\CatalogProvider::class]);
    if ($r->isSuccess() && $kg) {
        $item->getPropertyCollection()->setProperty([['NAME' => 'Фасовка', 'CODE' => 'PACK', 'VALUE' => $kg . ' кг', 'SORT' => 100]]);
    }
    return $r->isSuccess() ? '' : implode('; ', $r->getErrorMessages());
}

// Мегаменю «Каталог»: корневые разделы с подразделами и промо-товаром + аренда; формат CATS из ui.js
function bt_mega_cats(): array
{
    $catId = bt_iblock('catalog');
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_mega_cats', '/bt/catalog')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/catalog');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $catId);
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . bt_iblock('rent'));

    $cats = [];
    $r = \CIBlockSection::GetList(['LEFT_MARGIN' => 'ASC'], ['IBLOCK_ID' => $catId, 'ACTIVE' => 'Y', '<=DEPTH_LEVEL' => 2], false, ['ID', 'NAME', 'DEPTH_LEVEL', 'SECTION_PAGE_URL', 'IBLOCK_SECTION_ID']);
    $roots = [];
    while ($s = $r->GetNext(true, false)) {
        if ($s['DEPTH_LEVEL'] == 1) {
            $roots[$s['ID']] = count($cats);
            $cats[] = ['t' => $s['NAME'], 'h' => $s['SECTION_PAGE_URL'], 'sub' => [], 'id' => (int)$s['ID']];
        } elseif (isset($roots[$s['IBLOCK_SECTION_ID']])) {
            $cats[$roots[$s['IBLOCK_SECTION_ID']]]['sub'][] = [$s['NAME'], $s['SECTION_PAGE_URL']];
        }
    }
    // промо: первый товар раздела с фото
    foreach ($cats as &$c) {
        $el = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $catId, 'SECTION_ID' => $c['id'], 'INCLUDE_SUBSECTIONS' => 'Y', 'ACTIVE' => 'Y', '!PREVIEW_PICTURE' => false], false, ['nTopCount' => 1], ['ID'])->Fetch();
        $m = $el ? bt_product((string)$el['ID']) : null;
        if ($m) {
            $c['promo'] = ['img' => $m['img'], 'b' => $m['n'], 't' => $m['p'] ? bt_fmt($m['p']) . (!empty($m['bulk']) ? ' за кг' : '') : '', 'h' => $m['url']];
        }
        // у кофемашин подразделов нет — показываем модели
        if (!$c['sub']) {
            foreach (bt_catalog_data()[bt_product_group(\CIBlockSection::GetByID($c['id'])->Fetch()['CODE'])] ?? [] as $m2) {
                $c['sub'][] = [$m2['n'], $m2['url']];
            }
        }
        unset($c['id']);
    }
    unset($c);
    $rent = bt_catalog_data()['rent'];
    $cats[] = ['t' => 'Аренда кофемашин', 'h' => '/arenda-kofemashin/', 'sub' => [['Для офиса', '/arenda-kofemashin/'], ['Для кафе и HoReCa', '/arenda-kofemashin/'], ['На мероприятие', '/arenda-kofemashin/#event'], ['Кофе по подписке', '/podpiska/']],
        'promo' => $rent ? ['img' => $rent[0]['img'], 'b' => $rent[0]['n'], 't' => 'от ' . bt_fmt(min(array_column($rent, 'p'))) . ' в месяц', 'h' => '/arenda-kofemashin/'] : null];
    // порядок как в макете: Чай, Кофе, Кофемашины, Аренда, Аксессуары
    $acc = array_filter($cats, fn($c) => $c['h'] === '/catalog/aksessuary/');
    $cats = array_values(array_merge(array_filter($cats, fn($c) => $c['h'] !== '/catalog/aksessuary/'), $acc));

    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($cats);
    return $cats;
}

// Характеристики для страницы сравнения: id => ключи групп BT_SPEC_GROUPS из ui.js
function bt_catalog_specs(): array
{
    $catId = bt_iblock('catalog');
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_catalog_specs', '/bt/catalog')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/catalog');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $catId);
    $map = ['roast' => 'ROAST', 'mix' => 'MIX', 'w' => 'NET_WEIGHT', 'country' => 'COUNTRY', 'region' => 'REGION', 'proc' => 'PROCESSING',
        'q' => 'Q_SCORE', 'notes' => 'NOTES', 'kind' => 'TEA_KIND', 'pack' => 'PACKING', 'taste' => 'TASTE', 'effect' => 'EFFECT',
        'cups' => 'CUPS_PER_DAY', 'dims' => 'DIMENSIONS', 'screen' => 'SCREEN'];
    $specs = [];
    $r = \CIBlockElement::GetList([], ['IBLOCK_ID' => $catId, 'ACTIVE' => 'Y'], false, false, ['ID', 'IBLOCK_ID']);
    while ($o = $r->GetNextElement()) {
        $f = $o->GetFields();
        $pr = $o->GetProperties();
        foreach ($map as $key => $code) {
            $v = $pr[$code]['~VALUE'] ?? '';
            $v = is_array($v) ? implode(', ', $v) : trim((string)$v);
            if ($v !== '') {
                $specs[$f['ID']][$key] = htmlspecialcharsbx($v);
            }
        }
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($specs);
    return $specs;
}

// Группа товара для витрины и сравнения — по корневому разделу каталога
function bt_product_group(string $rootCode): string
{
    return ['kofe' => 'coffee', 'chay' => 'tea', 'professionalnye-kofemashiny' => 'machines'][$rootCode] ?? 'acc';
}

// Все товары и аренда в формате window.BT_PRODUCTS из ui.js: {coffee:[], tea:[], machines:[], acc:[], rent:[]}
function bt_catalog_data(): array
{
    $catId = bt_iblock('catalog');
    $rentId = bt_iblock('rent');
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_catalog_data', '/bt/catalog')) {
        return $cache->getVars();
    }
    Loader::includeModule('catalog');
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/catalog');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $catId);
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $rentId);

    $img = fn($fileId) => bt_img($fileId, 480, 340);
    $data = ['coffee' => [], 'tea' => [], 'machines' => [], 'acc' => [], 'rent' => []];

    // корневой раздел каждого раздела
    $root = [];
    $r = \CIBlockSection::GetList([], ['IBLOCK_ID' => $catId], false, ['ID', 'CODE', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL']);
    $sec = [];
    while ($s = $r->Fetch()) {
        $sec[$s['ID']] = $s;
    }
    foreach ($sec as $id => $s) {
        while ($s['IBLOCK_SECTION_ID']) {
            $s = $sec[$s['IBLOCK_SECTION_ID']];
        }
        $root[$id] = $s['CODE'];
    }

    // цены: базовая и оптовая сетка по количеству
    $prices = [];
    $r = \Bitrix\Catalog\PriceTable::getList([
        'filter' => ['=CATALOG_GROUP.BASE' => 'Y', '=PRODUCT.IBLOCK_ELEMENT.IBLOCK_ID' => $catId],
        'select' => ['PRODUCT_ID', 'PRICE', 'QUANTITY_FROM'], 'order' => ['QUANTITY_FROM' => 'ASC'],
    ]);
    while ($p = $r->fetch()) {
        $prices[$p['PRODUCT_ID']][] = ['kg' => (int)($p['QUANTITY_FROM'] ?: 1), 'p' => (float)$p['PRICE']];
    }

    $r = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $catId, 'ACTIVE' => 'Y'], false, false,
        ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'IBLOCK_SECTION_ID']);
    while ($o = $r->GetNextElement()) {
        $f = $o->GetFields(); // сырые значения — в ключах с «~», экранирует bt_card()
        $pr = $o->GetProperties();
        $pl = $prices[$f['ID']] ?? [['kg' => 1, 'p' => 0]];
        $m = [
            'id' => (string)$f['ID'], 'code' => $f['CODE'], 'url' => $f['~DETAIL_PAGE_URL'], 'img' => $img($f['PREVIEW_PICTURE']),
            'n' => $f['~NAME'], 'p' => $pl[0]['p'], 'par' => (string)$pr['SHORT_DESC']['~VALUE'], 'stock' => 1,
        ];
        if ($pr['OLD_PRICE']['VALUE']) {
            $m['old'] = (float)$pr['OLD_PRICE']['VALUE'];
        }
        if ($pr['BADGES']['VALUE']) {
            $m['badges'] = array_values((array)$pr['BADGES']['VALUE']);
        }
        if (count($pl) > 1) {
            $m['bulk'] = $pl;
        }
        $sc = [];
        foreach (['DENSITY' => 'Плотность', 'ACIDITY' => 'Кислотность', 'STRENGTH' => 'Крепость', 'AROMA' => 'Аромат'] as $code => $name) {
            if ($pr[$code]['VALUE'] !== '' && $pr[$code]['VALUE'] !== null) {
                $sc[] = [$name, (int)$pr[$code]['VALUE']];
            }
        }
        if ($sc) {
            $m['sc'] = $sc;
        }
        $data[bt_product_group($root[$f['IBLOCK_SECTION_ID']] ?? '')][] = $m;
    }

    $r = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $rentId, 'ACTIVE' => 'Y'], false, false,
        ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE', 'PROPERTY_PRICE_MONTH', 'PROPERTY_AUDIENCE', 'PROPERTY_CUPS_PER_DAY', 'PROPERTY_FREE_FROM_KG']);
    while ($f = $r->Fetch()) {
        $data['rent'][] = [
            'id' => 'r' . $f['ID'], 'code' => $f['CODE'], 'url' => '/arenda-kofemashin/#calc', 'img' => $img($f['PREVIEW_PICTURE']),
            'n' => $f['NAME'], 'p' => (float)$f['PROPERTY_PRICE_MONTH_VALUE'], 'unit' => 'в месяц', 'rent' => 1, 'stock' => 1,
            'par' => implode(' · ', array_filter([$f['PROPERTY_AUDIENCE_VALUE'], $f['PROPERTY_CUPS_PER_DAY_VALUE'] ? 'до ' . $f['PROPERTY_CUPS_PER_DAY_VALUE'] . ' чашек/день' : '', $f['PROPERTY_FREE_FROM_KG_VALUE'] ? 'бесплатно от ' . $f['PROPERTY_FREE_FROM_KG_VALUE'] . ' кг кофе' : ''])),
        ];
    }

    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($data);
    return $data;
}

// Блок страницы из одноэлементного инфоблока (главная, «О компании»): поля по коду свойства в нижнем регистре
function bt_block(string $code): array
{
    return bt_blocks($code, 1)[0] ?? [];
}

// Элементы инфоблока в формате bt_block + название и HTML анонса: карточки со своими полями, вопросы и ответы
function bt_blocks(string $code, int $limit = 0): array
{
    $ibId = bt_iblock($code);
    if (!$ibId) {
        return [];
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_blocks_' . $code . '_' . $limit, '/bt/blocks')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/blocks');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $list = [];
    $r = \CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y'], false, $limit ? ['nTopCount' => $limit] : false,
        ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PREVIEW_PICTURE', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE']);
    while ($el = $r->GetNextElement()) {
        $f = $el->GetFields();
        $b = ['id' => (int)$f['ID'], 'name' => $f['~NAME'], 'code' => (string)$f['CODE'],
            'html' => $f['PREVIEW_TEXT_TYPE'] === 'html' ? trim((string)$f['~PREVIEW_TEXT']) : nl2br(htmlspecialcharsbx(trim((string)$f['~PREVIEW_TEXT'])), false)];
        $b['pic'] = bt_img($f['PREVIEW_PICTURE'], 1000, 1000);
        $b['pic_id'] = (int)$f['PREVIEW_PICTURE'];
        foreach ($el->GetProperties() as $pc => $p) {
            $k = strtolower($pc);
            if ($p['PROPERTY_TYPE'] === 'F') {
                $b[$k] = $p['VALUE'] ? \CFile::GetPath($p['VALUE']) : '';
            } elseif ($p['MULTIPLE'] === 'Y') {
                $b[$k] = [];
                foreach ((array)$p['~VALUE'] as $i => $v) {
                    $b[$k][] = [trim((string)$v), trim((string)($p['~DESCRIPTION'][$i] ?? ''))];
                }
            } elseif (($p['USER_TYPE'] ?? '') === 'HTML') {
                $b[$k] = trim((string)($p['~VALUE']['TEXT'] ?? ''));
            } else {
                $b[$k] = trim((string)$p['~VALUE']);
            }
        }
        $list[] = $b;
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($list);
    return $list;
}

// Карточки спискового инфоблока: название, текст анонса, картинка, иконка
function bt_list(string $code): array
{
    $ibId = bt_iblock($code);
    if (!$ibId) {
        return [];
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_list_' . $code, '/bt/blocks')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/blocks');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $list = [];
    $hasIcon = (bool)\CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'ICON'])->Fetch();
    $r = \CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y'], false, false,
        array_merge(['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'PREVIEW_PICTURE'], $hasIcon ? ['PROPERTY_ICON'] : []));
    while ($f = $r->GetNext()) {
        $list[] = [
            'name' => $f['~NAME'], 'text' => trim(strip_tags((string)$f['~PREVIEW_TEXT'])),
            'pic' => bt_img($f['PREVIEW_PICTURE'], 1000, 1000),
            'icon' => bt_svg((int)($f['PROPERTY_ICON_VALUE'] ?? 0)),
        ];
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($list);
    return $list;
}

// Отзывы о компании: дата, компания, логотип, благодарственные письма (картинки — в просмотр, PDF — ссылкой)
function bt_reviews(): array
{
    $ibId = bt_iblock('reviews');
    if (!$ibId) {
        return [];
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_reviews', '/bt/blocks')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/blocks');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $list = [];
    $r = \CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y'], false, false, ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT']);
    while ($ob = $r->GetNextElement()) {
        $f = $ob->GetFields();
        $p = $ob->GetProperties();
        $ts = ($p['DATE']['VALUE'] ?? '') !== '' ? MakeTimeStamp($p['DATE']['VALUE']) : 0;
        $docs = [];
        foreach ((array)($p['LETTER']['VALUE'] ?? []) as $i => $fid) {
            $file = $fid ? \CFile::GetFileArray($fid) : null;
            if (!$file) {
                continue;
            }
            $pdf = str_ends_with(strtolower($file['FILE_NAME']), '.pdf');
            $docs[] = ['pdf' => $pdf, 'src' => $pdf ? $file['SRC'] : bt_img($fid, 1800, 1800), 'th' => $pdf ? '' : bt_img($fid, 160, 220),
                'cap' => trim((string)($p['LETTER']['DESCRIPTION'][$i] ?? '')) ?: 'Благодарственное письмо'];
        }
        $list[] = [
            'id' => (int)$f['ID'], 'name' => $f['~NAME'], 'text' => trim(strip_tags((string)$f['~PREVIEW_TEXT'])),
            'date' => $ts ? FormatDate('j F Y', $ts) : '', 'iso' => $ts ? date('Y-m-d', $ts) : '',
            'company' => trim((string)($p['COMPANY']['VALUE'] ?? '')), 'rating' => (int)($p['RATING']['VALUE'] ?? 0),
            'logo' => bt_img((int)($p['LOGO']['VALUE'] ?? 0), 96, 96),
            'docs' => $docs,
        ];
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($list);
    return $list;
}

// ID элементов с галочкой «Показывать на главной» (кофемашины каталога, модели аренды, отзывы)
function bt_main_ids(string $code): array
{
    $ibId = bt_iblock($code);
    if (!$ibId) {
        return [];
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_main_ids_' . $code, '/bt/blocks')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/blocks');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $ids = [];
    $r = \CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y', 'PROPERTY_SHOW_MAIN_VALUE' => 'Да'], false, false, ['ID']);
    while ($x = $r->Fetch()) {
        $ids[] = (int)$x['ID'];
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($ids);
    return $ids;
}

// Разметка без микроданных schema.org: карточки товаров, которые на странице не главные (рекомендации к товару)
function bt_nomicro(string $html): string
{
    $html = preg_replace('~<(meta|link)\s+itemprop="[^"]*"[^>]*>~i', '', $html);
    return preg_replace('~\s(itemscope|itemtype="[^"]*"|itemprop="[^"]*"|itemid="[^"]*")~i', '', $html);
}

// Карточка отзыва — одна на все страницы. Длинный текст обрезается, «Читать полностью» открывает окно (ui.js)
function bt_rev_card(array $r): string
{
    $e = fn($s) => htmlspecialcharsbx((string)$s);
    $av = $r['logo'] !== ''
        ? '<img class="rev__av" src="' . $e($r['logo']) . '" alt="" width="44" height="44" loading="lazy">'
        : '<span class="rev__av" aria-hidden="true">' . $e(mb_substr($r['name'], 0, 1)) . '</span>';
    $docs = '';
    foreach ($r['docs'] as $d) {
        $docs .= $d['pdf']
            ? '<a class="rev__doc rev__doc--pdf" href="' . $e($d['src']) . '" target="_blank" rel="noopener"><span>PDF</span>' . $e($d['cap']) . '</a>'
            : '<a class="rev__doc" href="' . $e($d['src']) . '" data-lbox><img src="' . $e($d['th']) . '" alt="" loading="lazy">' . $e($d['cap']) . '</a>';
    }
    // отзыв о компании: организация — та же, что в разметке LocalBusiness на всех страницах (itemid = @id)
    $co = bt_contacts();
    $org = '<div hidden itemprop="itemReviewed" itemscope itemtype="https://schema.org/LocalBusiness" itemid="https://beverteam.ru/#org"><meta itemprop="name" content="BEVERTEAM">'
        . (($co['phone1'] ?? '') !== '' ? '<meta itemprop="telephone" content="' . $e($co['phone1']) . '">' : '')
        . '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress"><meta itemprop="streetAddress" content="' . $e($co['street'] ?? '') . '">'
        . '<meta itemprop="addressLocality" content="' . $e($co['city'] ?? '') . '"><meta itemprop="postalCode" content="' . $e($co['zip'] ?? '') . '"></div></div>'
        . (($r['rating'] ?? 0) > 0 ? '<div hidden itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating"><meta itemprop="ratingValue" content="' . (int)$r['rating'] . '"><meta itemprop="bestRating" content="5"></div>' : '');
    return '<article class="rev" itemscope itemtype="https://schema.org/Review">' . $org
        . '<header class="rev__hd">' . $av . '<div class="rev__who"><b itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">' . $e($r['name']) . '</span></b>'
        . ($r['company'] !== '' ? '<span>' . $e($r['company']) . '</span>' : '') . '</div>'
        . ($r['date'] !== '' ? '<time datetime="' . $r['iso'] . '" itemprop="datePublished">' . $e($r['date']) . '</time>' : '') . '</header>'
        . '<div class="rev__txt" itemprop="reviewBody">' . nl2br($e($r['text'])) . '</div>'
        . '<button class="rev__more" type="button" hidden>Читать полностью</button>'
        . ($docs !== '' ? '<div class="rev__docs">' . $docs . '</div>' : '')
        . '</article>';
}

// Текст товара для вывода: строки через <br> (от трёх коротких подряд) — маркированным списком, таблицы — в обёртке с прокруткой
function bt_br_list(string $html): string
{
    $list = function (string $chunk): string {
        $items = array_values(array_filter(array_map('trim', preg_split('~<br\s*/?>~i', $chunk)), fn($s) => trim(strip_tags(str_replace('&nbsp;', ' ', $s))) !== ''));
        if (count($items) < 3 || max(array_map(fn($s) => mb_strlen(strip_tags($s)), $items)) > 160) {
            return '';
        }
        return "<ul>\n" . implode("\n", array_map(fn($s) => '<li>' . $s . '</li>', $items)) . "\n</ul>";
    };
    $html = preg_replace_callback('~<p\b[^>]*>((?:(?!</p>).)*?<br\s*/?>(?:(?!</p>).)*)</p>~isu', fn($m) => $list($m[1]) ?: $m[0], $html);
    // текст вообще без абзацев, только строки через <br>
    if (!preg_match('~<(p|ul|ol|table|div|h\d)\b~i', $html) && ($ul = $list($html)) !== '') {
        $html = $ul;
    }
    return preg_replace(['~<table\b~i', '~</table>~i'], ['<div class="tbl"><table', '</table></div>'], $html);
}

// Иконки разделов каталога (плитки каталога и окно поиска): спрайт выводится один раз в футере, иконка — <use href="#ico-…">.
// viewBox — по границам рисунка плюс половина линии: иконка стоит ровно по центру круга
function bt_cat_sprite(): string
{
    return '<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>   <symbol id="ico-tea" viewBox="3.25 3.25 17.5 17.5"><path d="M4 20C4 11 10 5 20 4c-1 10-7 16-16 16z"/><path d="M4 20c3-6 7-9 12-11"/></symbol>   <symbol id="ico-bean" viewBox="4.38 2.9 15.24 18.2"><ellipse cx="12" cy="12" rx="6" ry="9" transform="rotate(-30 12 12)"/><path d="M8.5 6.5c-2 5 6 8 4 12"/></symbol>   <symbol id="ico-machine" viewBox="4.25 2.25 15.5 19.5"><rect x="5" y="3" width="14" height="18" rx="2"/><rect x="8" y="6" width="8" height="4" rx="1"/><path d="M12 13v2M9 18h6"/></symbol>   <symbol id="ico-rent" viewBox="3.25 2.25 20 21"><rect x="4" y="3" width="13" height="16" rx="2"/><rect x="7" y="6" width="7" height="3.5" rx="1"/><circle cx="18" cy="18" r="4.5" class="ico-badge" style="fill:var(--lime)"/><path d="M16.6 20v-4h1.6a1.2 1.2 0 0 1 0 2.4h-2.2M16.2 19.2h2"/></symbol>   <symbol id="ico-cup" viewBox="3.25 7.25 18 13.5"><path d="M5 8h11v6a5.5 5.5 0 0 1-11 0z"/><path d="M16 10h2a2.5 2.5 0 0 1 0 5h-2M4 20h14"/></symbol> </defs></svg>';
}

// Точка на карте из «Контакты и реквизиты» (поле «Привязка к Яндекс.Карте»: «широта,долгота») → window.BT_CO_COORDS = [долгота, широта]
function bt_map_js(): string
{
    [$lat, $lon] = array_map('floatval', array_pad(explode(',', (string)(bt_contacts()['map'] ?? '')), 2, 0));
    return $lat && $lon ? ';window.BT_CO_COORDS=' . json_encode([$lon, $lat]) : '';
}

// Окно поиска: «Часто ищут», карточки «Предложения», разделы каталога — всё из админки
function bt_search_cfg(): array
{
    $styles = ['Лаймовый' => 'lime', 'Кофейный' => 'esp', 'Чёрный' => 'dark'];
    $promos = [];
    foreach (bt_blocks('search_promos') as $b) {
        $p = !empty($b['product']) ? bt_product((string)$b['product']) : null;
        $promos[] = ['k' => $b['caption'] ?? '', 't' => $b['name'], 's' => $b['text'] ?? '', 'u' => ($b['link'] ?? '') ?: ($p['url'] ?? '/catalog/'),
            'c' => $styles[$b['style'] ?? ''] ?? 'dark', 'img' => $b['pic'] ?: ($p['img'] ?? ''), 'w' => str_starts_with($b['show'] ?? '', 'Рядом') ? 'none' : 'empty'];
    }
    return [
        'hints' => array_column(bt_blocks('search_hints'), 'name'),
        'promos' => $promos,
        'secs' => array_map(fn($t) => ['t' => $t['name'], 'u' => $t['url'], 'i' => $t['icon'] ?? 'cup'], bt_home_tiles()),
    ];
}

// Иконка из файла: SVG встраиваем (цвет — от родителя через currentColor), PNG — картинкой
function bt_svg(int $fileId): string
{
    $path = $fileId ? (string)\CFile::GetPath($fileId) : '';
    if ($path === '') {
        return '';
    }
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'svg') {
        return '<img src="' . htmlspecialcharsbx($path) . '" alt="" width="24" height="24">';
    }
    $svg = (string)@file_get_contents($_SERVER['DOCUMENT_ROOT'] . $path);
    $svg = preg_replace(['/<\?xml.*?\?>|<!--.*?-->|<script.*?<\/script>/si', '/\son\w+="[^"]*"/i'], '', $svg);
    $svg = preg_replace('/\s(fill|stroke)="(?!none|currentColor)[^"]*"/i', ' $1="currentColor"', $svg);
    return preg_replace('/<svg\b/i', '<svg aria-hidden="true" focusable="false"', trim($svg), 1);
}

// Кнопка блока: ссылка #zayavka открывает попап заявки с темой = текст кнопки
function bt_btn(string $text, string $link, string $cls = 'btn', string $attrs = ''): string
{
    if ($text === '') {
        return '';
    }
    $e = fn($s) => htmlspecialcharsbx($s);
    if ($link === '#zayavka') {
        return '<a class="' . $cls . '" href="/kontakty/#form" data-lead="' . $e($text) . '"' . $attrs . '>' . $e($text) . '</a>';
    }
    return '<a class="' . $cls . '" href="' . $e($link ?: '#') . '"' . $attrs . '>' . $e($text) . '</a>';
}

// Заголовок блока: переносы строк из поля, выделенная часть — в теге $tag
function bt_title(string $title, string $mark = '', string $tag = 'em'): string
{
    $h = nl2br(htmlspecialcharsbx($title), false);
    if ($mark !== '') {
        $m = htmlspecialcharsbx($mark);
        $pos = mb_strpos($h, $m);
        if ($pos !== false) {
            $h = mb_substr($h, 0, $pos) . "<$tag>" . $m . "</$tag>" . mb_substr($h, $pos + mb_strlen($m));
        }
    }
    return $h;
}

// Материалы журнала для поиска в шапке и карточек: формат BT_POSTS из ui.js
function bt_posts(): array
{
    $ibId = bt_iblock('journal');
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_posts', '/bt/journal')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/journal');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $posts = [];
    $r = \CIBlockElement::GetList(['ACTIVE_FROM' => 'DESC', 'SORT' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'], false, false,
        ['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', 'TAGS', 'ACTIVE_FROM', 'DATE_CREATE', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL', 'PROPERTY_KIND', 'PROPERTY_RUBRIC', 'PROPERTY_READ_TIME']);
    while ($f = $r->GetNext()) {
        $posts[] = bt_post_data($f);
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($posts);
    return $posts;
}

// Поля элемента журнала (выборка с IBLOCK_SECTION_ID и PROPERTY_KIND/RUBRIC/READ_TIME) → формат BT_POSTS; рубрика — раздел инфоблока
function bt_post_data(array $f): array
{
    $kind = 'article';
    if (!empty($f['PROPERTY_KIND_ENUM_ID'])) {
        $kind = \CIBlockPropertyEnum::GetByID($f['PROPERTY_KIND_ENUM_ID'])['XML_ID'] ?? 'article';
    }
    $date = $f['ACTIVE_FROM'] ?: $f['DATE_CREATE'];
    return [
        'id' => $f['CODE'], 'kind' => $kind, 'cat' => bt_blog_rubrics()[(int)($f['IBLOCK_SECTION_ID'] ?? 0)]['name'] ?? (($f['~PROPERTY_RUBRIC_VALUE'] ?? '') ?: ($kind === 'news' ? 'Новости' : 'Статьи')),
        'rub' => bt_blog_rubrics()[(int)($f['IBLOCK_SECTION_ID'] ?? 0)]['url'] ?? '',
        'tags' => bt_tags_split((string)($f['~TAGS'] ?? $f['TAGS'] ?? '')),
        'd' => $date ? date('Y-m-d', MakeTimeStamp($date)) : '', 't' => $f['~NAME'], 'lead' => trim(strip_tags((string)$f['~PREVIEW_TEXT'])),
        'min' => (int)($f['PROPERTY_READ_TIME_VALUE'] ?? 0), 'url' => $f['~DETAIL_PAGE_URL'],
        'img' => bt_img($f['PREVIEW_PICTURE'], 1040, 650, BX_RESIZE_IMAGE_EXACT),
    ];
}

function bt_date_ru(string $iso): string
{
    $m = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    $t = strtotime($iso);
    return $t ? date('j', $t) . ' ' . $m[date('n', $t) - 1] . ' ' . date('Y', $t) : '';
}

// Карточка журнала — серверная копия BT_postCard() из ui.js; без фото — фирменная заглушка (рисует BT_phFit)
function bt_post_card(array $p): string
{
    $e = fn($s) => htmlspecialcharsbx((string)$s);
    $img = $p['img'] ? '<img src="' . $e($p['img']) . '" alt="' . $e($p['t']) . '" loading="lazy" width="520" height="325">'
        : '<img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-ph="' . $e($p['cat']) . '" data-ph-t="' . $e($p['t']) . '" alt="' . $e($p['t']) . '" width="520" height="325">';
    return '<a class="ncard" href="' . $e($p['url']) . '">' . $img
        . '<div class="ncard__b"><div class="ncard__m"><span class="tag">' . $e($p['cat']) . '</span><time datetime="' . $e($p['d']) . '">' . bt_date_ru($p['d']) . '</time>'
        . ($p['kind'] === 'news' && $p['cat'] !== 'Новости' ? '<span class="tag tag--n">Новость</span>' : '') . '</div>'
        . '<h3>' . $e($p['t']) . '</h3><p>' . $e($p['lead']) . '</p></div></a>';
}

// Модели аренды для главной: подбор на первом экране и «кофе по подписке»
function bt_rent_models(): array
{
    $ibId = bt_iblock('rent');
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_rent_models', '/bt/catalog')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/catalog');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $list = [];
    $r = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y'], false, false,
        ['ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_PRICE_MONTH', 'PROPERTY_AUDIENCE', 'PROPERTY_CUPS_PER_DAY', 'PROPERTY_FREE_FROM_KG', 'PROPERTY_MACHINE']);
    while ($f = $r->Fetch()) {
        $m = $f['PROPERTY_MACHINE_VALUE'] ? bt_product((string)$f['PROPERTY_MACHINE_VALUE']) : null;
        $list[] = [
            'id' => 'r' . $f['ID'], 'name' => $f['NAME'], 'url' => $m['url'] ?? '', 'buy' => $m['p'] ?? 0,
            'model' => trim(preg_replace('/^Кофемашина\s+|\s+Аренда$/u', '', $m['n'] ?? $f['NAME'])),
            'price' => (float)$f['PROPERTY_PRICE_MONTH_VALUE'], 'audience' => (string)$f['PROPERTY_AUDIENCE_VALUE'],
            'cups' => (int)$f['PROPERTY_CUPS_PER_DAY_VALUE'], 'kg' => (int)$f['PROPERTY_FREE_FROM_KG_VALUE'],
            'img' => $f['PREVIEW_PICTURE'] ? bt_img($f['PREVIEW_PICTURE'], 480, 340) : ($m['img'] ?? ''),
        ];
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($list);
    return $list;
}

// Плитка разделов на главной и в каталоге: корневые разделы каталога с картинкой раздела и иконкой + аренда
function bt_home_tiles(): array
{
    $catId = bt_iblock('catalog');
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_home_tiles', '/bt/catalog')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/catalog');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $catId);
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . bt_iblock('rent'));
    $plural = fn(int $n, array $w) => $n . ' ' . $w[($n % 10 === 1 && $n % 100 !== 11) ? 0 : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 1 : 2)];
    $tiles = [];
    $r = \CIBlockSection::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $catId, 'ACTIVE' => 'Y', 'DEPTH_LEVEL' => 1, 'CNT_ACTIVE' => 'Y'], true, ['ID', 'CODE', 'NAME', 'PICTURE', 'SECTION_PAGE_URL', 'LEFT_MARGIN', 'RIGHT_MARGIN']);
    // иконка плитки в каталоге — по символьному коду раздела; новый раздел получит чашку
    $icons = ['chay' => 'tea', 'kofe' => 'bean', 'professionalnye-kofemashiny' => 'machine', 'aksessuary' => 'cup'];
    while ($s = $r->GetNext()) {
        $subs = (int)(($s['RIGHT_MARGIN'] - $s['LEFT_MARGIN'] - 1) / 2);
        $tiles[] = [
            'id' => (int)$s['ID'], 'name' => $s['~NAME'], 'url' => $s['~SECTION_PAGE_URL'],
            'img' => bt_img($s['PICTURE'], 400, 300), 'icon' => $icons[$s['CODE']] ?? 'cup',
            'note' => $subs ? $plural($subs, ['категория', 'категории', 'категорий']) : $plural((int)$s['ELEMENT_CNT'], ['модель', 'модели', 'моделей']),
        ];
    }
    $rent = bt_rent_models();
    if ($rent) {
        // аренда — после кофемашин, как в меню
        array_splice($tiles, min(3, count($tiles)), 0, [[
            'id' => 0, 'name' => 'Аренда кофемашин', 'url' => '/arenda-kofemashin/', 'img' => $rent[0]['img'], 'icon' => 'rent',
            'note' => 'от ' . bt_fmt(min(array_column($rent, 'price'))) . '/мес',
        ]]);
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($tiles);
    return $tiles;
}

// Хвост формы заявки: sessid, ловушка для ботов, согласие, кнопка — одинаково во всех формах
function bt_form_tail(string $btn = 'Отправить заявку'): string
{
    return '<input type="hidden" name="sessid" value="' . bitrix_sessid() . '">'
        . '<div class="hp" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>'
        . '<label class="check check--top" style="margin-bottom:16px"><input type="checkbox" name="agree" value="Y"> <span>Я ознакомлен(а) с <a class="link" href="/polzovatelskoe-soglashenie/" target="_blank">пользовательским соглашением</a> и <a class="link" href="/politika-konfidencialnosti/" target="_blank">политикой конфиденциальности</a></span></label>'
        . '<button class="btn btn--block" type="submit">' . htmlspecialcharsbx($btn) . '</button>';
}

// Хлебные крошки страницы: выводятся в конце сборки, когда цепочка уже полная
function bt_crumbs(): void
{
    global $APPLICATION;
    $APPLICATION->AddBufferContent([$APPLICATION, 'GetNavChain'], false, 0, SITE_TEMPLATE_PATH . '/components/bitrix/breadcrumb/bt/template.php', true, false);
}

// ---------- покупатель и личный кабинет ----------

// Телефон → 11 цифр с 7 в начале, иначе пустая строка
function bt_phone_digits(string $s): string
{
    $d = preg_replace('/\D/', '', $s);
    if (strlen($d) === 10) {
        $d = '7' . $d;
    } elseif (strlen($d) === 11 && $d[0] === '8') {
        $d = '7' . substr($d, 1);
    }
    return strlen($d) === 11 && $d[0] === '7' ? $d : '';
}

// +7 (904) 384-13-88 — как маска полей телефона
function bt_phone_fmt(string $s): string
{
    $d = bt_phone_digits($s);
    return $d ? '+7 (' . substr($d, 1, 3) . ') ' . substr($d, 4, 3) . '-' . substr($d, 7, 2) . '-' . substr($d, 9, 2) : $s;
}

// Покупатель по телефону: в профилях номера записаны по-разному — сравниваем цифры
function bt_user_by_phone(string $digits): ?array
{
    $r = \Bitrix\Main\UserTable::getList([
        'filter' => [['LOGIC' => 'OR', '%PERSONAL_PHONE' => substr($digits, -2), '%PERSONAL_MOBILE' => substr($digits, -2)]],
        'select' => ['ID', 'EMAIL', 'ACTIVE', 'PERSONAL_PHONE', 'PERSONAL_MOBILE'], 'order' => ['ID' => 'ASC'],
    ]);
    while ($u = $r->fetch()) {
        if (in_array($digits, [bt_phone_digits((string)$u['PERSONAL_PHONE']), bt_phone_digits((string)$u['PERSONAL_MOBILE'])], true)) {
            return $u;
        }
    }
    return null;
}

// Новый покупатель (логин = e-mail, пароль случайный — входят по коду). Возвращает ID или текст ошибки
function bt_user_create(string $email, string $name, string $phone = ''): int|string
{
    $rnd = \Bitrix\Main\Security\Random::class;
    $pass = $rnd::getStringByAlphabet(12, $rnd::ALPHABET_ALPHALOWER | $rnd::ALPHABET_ALPHAUPPER | $rnd::ALPHABET_NUM) . $rnd::getStringByAlphabet(4, $rnd::ALPHABET_SPECIAL);
    $login = $email;
    while (\Bitrix\Main\UserTable::getList(['filter' => ['=LOGIN' => $login], 'select' => ['ID']])->fetch()) {
        $login = $email . '_' . random_int(100, 999);
    }
    $groups = array_filter(array_map('intval', explode(',', COption::GetOptionString('main', 'new_user_registration_def_group', ''))));
    [$first, $last] = array_pad(preg_split('/\s+/u', trim($name), 2), 2, '');
    $d = bt_phone_digits($phone);
    $u = new CUser();
    $id = (int)$u->Add(['LOGIN' => $login, 'EMAIL' => $email, 'NAME' => $first, 'LAST_NAME' => $last, 'PERSONAL_PHONE' => $d ? '+' . $d : '',
        'PASSWORD' => $pass, 'CONFIRM_PASSWORD' => $pass, 'ACTIVE' => 'Y', 'LID' => SITE_ID, 'GROUP_ID' => $groups ?: [2]]);
    return $id ?: strip_tags((string)$u->LAST_ERROR);
}

// Текущий покупатель для ui.js (window.BT_USER), гостю — null
function bt_user_js(): ?array
{
    global $USER;
    if (!is_object($USER) || !$USER->IsAuthorized()) {
        return null;
    }
    $u = \Bitrix\Main\UserTable::getList(['filter' => ['=ID' => (int)$USER->GetID()], 'select' => ['NAME', 'LAST_NAME', 'EMAIL', 'LOGIN', 'PERSONAL_PHONE', 'PERSONAL_MOBILE']])->fetch();
    $phone = (string)($u['PERSONAL_PHONE'] ?: $u['PERSONAL_MOBILE']);
    return $u ? ['name' => trim($u['NAME'] . ' ' . $u['LAST_NAME']), 'email' => (string)($u['EMAIL'] ?: $u['LOGIN']),
        'phone' => $phone !== '' ? bt_phone_fmt($phone) : ''] : null;
}

// Кнопки входа через сервисы для ui.js: только включённые в модуле «Социальные сервисы» и с заполненными ключами
function bt_idp(): array
{
    return Loader::includeModule('socialservices') ? BtOAuth::buttons() : [];
}

// Причина неудачного входа через сервис ['msg', 'email'] — один раз, для окна входа после возврата
function bt_idp_error(): array
{
    $e = (array)($_SESSION['BT_OAUTH_ERR'] ?? []);
    unset($_SESSION['BT_OAUTH_ERR']);
    return $e;
}

// Профили покупателя (реквизиты юрлиц — UR, адреса доставки — FIZ): [id, name, v => значения по коду свойства]
function bt_profiles(int $userId, string $ptCode): array
{
    \Bitrix\Main\Loader::includeModule('sale');
    $pt = (int)(\Bitrix\Sale\Internals\PersonTypeTable::getList(['filter' => ['=CODE' => $ptCode, '=LID' => SITE_ID], 'select' => ['ID']])->fetch()['ID'] ?? 0);
    $list = [];
    $r = \Bitrix\Sale\Internals\UserPropsTable::getList(['filter' => ['=USER_ID' => $userId, '=PERSON_TYPE_ID' => $pt], 'select' => ['ID', 'NAME'], 'order' => ['ID' => 'ASC']]);
    while ($p = $r->fetch()) {
        $list[(int)$p['ID']] = ['id' => (int)$p['ID'], 'name' => $p['NAME'], 'v' => []];
    }
    if ($list) {
        $r = \Bitrix\Sale\Internals\UserPropsValueTable::getList(['filter' => ['@USER_PROPS_ID' => array_keys($list)],
            'select' => ['USER_PROPS_ID', 'VALUE', 'CODE' => 'PROPERTY.CODE']]);
        while ($v = $r->fetch()) {
            $list[(int)$v['USER_PROPS_ID']]['v'][$v['CODE']] = (string)$v['VALUE'];
        }
    }
    return array_values($list);
}

// Название местоположения по коду: «Екатеринбург» и область для подсказки
function bt_loc(string $code): array
{
    if ($code === '' || !\Bitrix\Main\Loader::includeModule('sale')) {
        return ['n' => '', 'r' => ''];
    }
    $l = \Bitrix\Sale\Location\LocationTable::getList(['filter' => ['=CODE' => $code, '=NAME.LANGUAGE_ID' => 'ru', '=PARENT.NAME.LANGUAGE_ID' => 'ru'],
        'select' => ['N' => 'NAME.NAME', 'R' => 'PARENT.NAME.NAME', 'RT' => 'PARENT.TYPE.CODE']])->fetch();
    return $l ? ['n' => $l['N'], 'r' => in_array($l['RT'], ['REGION', 'SUBREGION']) ? $l['R'] : ''] : ['n' => '', 'r' => ''];
}

// Адреса доставки покупателя: основной — первым
function bt_addresses(int $userId): array
{
    $main = (int)CUserOptions::GetOption('bt', 'main_addr', 0, $userId);
    $list = [];
    foreach (bt_profiles($userId, 'FIZ') as $p) {
        $v = $p['v'];
        $list[] = ['id' => $p['id'], 'tag' => $p['name'], 'loc' => $v['LOCATION'] ?? '', 'city' => bt_loc($v['LOCATION'] ?? '')['n'], 'street' => $v['ADDRESS'] ?? '',
            'flat' => $v['FLAT'] ?? '', 'entr' => $v['ENTRANCE'] ?? '', 'who' => $v['FIO'] ?? '', 'tel' => $v['PHONE'] ?? '', 'main' => $p['id'] === $main];
    }
    if ($list && !array_filter(array_column($list, 'main'))) {
        $list[0]['main'] = true;
    }
    usort($list, fn($a, $b) => $b['main'] <=> $a['main']);
    return $list;
}

// Статус заказа для кабинета: [текст, css-класс]; текст — название статуса из настроек магазина до запятой
function bt_order_status(array $o): array
{
    static $names = null;
    if ($names === null) {
        $names = [];
        foreach (\Bitrix\Sale\Internals\StatusLangTable::getList(['filter' => ['=LID' => LANGUAGE_ID]])->fetchAll() as $s) {
            $names[$s['STATUS_ID']] = trim(explode(',', $s['NAME'])[0]);
        }
    }
    if ($o['CANCELED'] === 'Y') {
        return ['Отменён', 'st-cancel'];
    }
    $st = $o['STATUS_ID'];
    $cls = $st === 'F' ? 'st-done' : ($st === 'P' ? 'st-paid' : (in_array($st, ['DS', 'DT', 'DF'], true) ? 'st-ship' : 'st-new'));
    return [$names[$st] ?? $st, $cls];
}

// Кабинет: гостю — приглашение войти (окно входа откроется само); true — можно выводить страницу
function bt_acc_guard(): bool
{
    global $USER;
    if ($USER->IsAuthorized()) {
        return true;
    }
    echo '<div class="wrap accp"><div class="cmp__empty acc-guest" data-auth-open>'
        . '<p class="display h3" style="margin:0 0 10px">Войдите в личный кабинет</p>'
        . '<p class="muted" style="margin:0 0 20px">Здесь заказы, адреса доставки и реквизиты компании. Пароль не нужен — пришлём код на e-mail.</p>'
        . '<button class="btn" type="button" data-auth>Войти или зарегистрироваться</button></div></div>';
    return false;
}

// Кабинет: крошки, заголовок и меню разделов; $head — готовый HTML заголовка
function bt_acc_start(string $cur, string $head, string $sub = ''): void
{
    global $USER;
    $u = bt_user_js() ?? ['name' => '', 'email' => ''];
    $e = fn($s) => htmlspecialcharsbx((string)$s);
    \Bitrix\Main\Loader::includeModule('sale');
    $cnt = \Bitrix\Sale\Internals\OrderTable::getCount(['=USER_ID' => (int)$USER->GetID(), '=LID' => SITE_ID]);
    $nav = [['profile', '/personal/', 'Профиль', ''], ['orders', '/personal/orders/', 'Заказы', $cnt ? '<span class="cnt">' . $cnt . '</span>' : ''],
        ['addr', '/personal/addresses/', 'Адреса доставки', ''], ['docs', '/personal/docs/', 'Счета и документы', ''],
        ['sub', '/personal/podpiska/', 'Подписка на кофе', ''], ['fav', '/personal/favorites/', 'Избранное', '']];
    echo '<div class="wrap accp">';
    bt_crumbs();
    echo '<div class="pagehead">' . $head . ($sub !== '' ? '<p class="sub">' . $sub . '</p>' : '') . '</div><div class="acc-l"><aside class="acc-nav">'
        . '<div class="u"><i>' . $e(mb_strtoupper(mb_substr($u['name'] ?: $u['email'], 0, 1))) . '</i><div><b>' . $e(explode(' ', $u['name'])[0] ?: 'Покупатель') . '</b><small>' . $e($u['email']) . '</small></div></div>';
    foreach ($nav as [$code, $href, $text, $extra]) {
        echo '<a href="' . $href . '"' . ($code === $cur ? ' class="cur" aria-current="page"' : '') . '>' . $text . ' ' . $extra . '</a>';
    }
    echo '<button class="out" type="button" data-logout>Выйти</button></aside><div class="acc-c">';
}

function bt_acc_end(): void
{
    echo '</div></div></div>';
}

// Open Graph и canonical: считаются после выполнения страницы (AddBufferContent); тип и картинку страница задаёт свойствами og_type / og_image
function bt_og(): string
{
    global $APPLICATION;
    $e = fn($s) => htmlspecialcharsbx(trim(strip_tags((string)$s)));
    $host = 'https://beverteam.ru';
    $title = $APPLICATION->GetPageProperty('title') ?: $APPLICATION->GetTitle();
    // посадочная страница (в т.ч. страница фильтра) — canonical на себя, иначе поисковик склеит её с чистым адресом
    $url = $host . (function_exists('bt_landing') && bt_landing() ? bt_url_key((string)$_SERVER['REQUEST_URI']) : $APPLICATION->GetCurPage(false));
    // картинки и адрес для соцсетей — с того домена, где открыта страница: на тестовом домене файлов боевого сайта нет
    $self = 'https://' . preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'beverteam.ru');
    $img = $APPLICATION->GetPageProperty('og_image') ?: '/local/templates/beverteam/images/og-logo.png';
    $img = str_starts_with($img, 'http') ? $img : $self . bt_og_card($img);
    $html = '<meta property="og:type" content="' . $e($APPLICATION->GetPageProperty('og_type') ?: 'website') . '"><meta property="og:site_name" content="BEVERTEAM">'
        . '<meta property="og:locale" content="ru_RU"><meta property="og:title" content="' . $e($title) . '">'
        . '<meta property="og:description" content="' . $e($APPLICATION->GetPageProperty('description')) . '">'
        . '<meta property="og:url" content="' . $e($self . $APPLICATION->GetCurPage(false)) . '"><meta property="og:image" content="' . $e($img) . '"><meta property="og:image:alt" content="' . $e($title) . '"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">'
        . '<meta property="og:logo" content="' . $self . '/local/templates/beverteam/images/og-logo.png"><meta name="twitter:image" content="' . $e($img) . '">'
        . '<meta name="twitter:card" content="summary_large_image">' . $APPLICATION->GetPageProperty('og_extra');
    // страницы фильтра, сортировки и поиска склеиваем с чистым адресом; у товара canonical ставит сам компонент
    if (!$APPLICATION->GetPageProperty('canonical')) {
        $html .= '<link rel="canonical" href="' . $e($url) . '">';
    }
    return $html;
}

// Организация для поисковиков: JSON-LD из контактов сайта (ИБ «Контакты и реквизиты»), на всех страницах
function bt_org_ld(): string
{
    $co = bt_contacts();
    $host = 'https://beverteam.ru/';
    $sameAs = array_values(array_filter(array_map(fn($m) => $m[2], bt_messengers())));
    $ld = ['@context' => 'https://schema.org', '@type' => 'LocalBusiness', '@id' => $host . '#org',
        'name' => 'BEVERTEAM', 'alternateName' => 'Бэвертим', 'legalName' => $co['legal'] ?? '', 'url' => $host,
        'logo' => $host . 'local/templates/beverteam/images/og-logo.png', 'image' => $host . 'local/templates/beverteam/images/og-logo.png',
        'description' => 'Кофе BOTANICA, чай, кофемашины Jetinno: продажа, аренда, ремонт и сервис в Екатеринбурге.',
        'email' => $co['email'] ?? '', 'telephone' => array_values(array_filter([$co['phone1'] ?? '', $co['phone2'] ?? ''])),
        'priceRange' => '₽₽', 'taxID' => $co['inn'] ?? '',
        'address' => ['@type' => 'PostalAddress', 'postalCode' => $co['zip'] ?? '', 'addressLocality' => $co['city'] ?? '',
            'streetAddress' => $co['street'] ?? '', 'addressCountry' => 'RU'],
        'openingHoursSpecification' => [['@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '10:00', 'closes' => '17:00']]];
    if ($sameAs) {
        $ld['sameAs'] = $sameAs;
    }
    return '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

// FAQPage для поисковиков из блоков вопросов-ответов на готовой странице (details/summary внутри .faq); обработчик OnEndBufferContent
function bt_faq_ld(&$content): void
{
    if (defined('ADMIN_SECTION') || !str_contains($content, 'faq') || str_contains($content, '"FAQPage"') || !str_contains($content, '</head>')) {
        return;
    }
    $doc = new \DOMDocument();
    @$doc->loadHTML('<?xml encoding="utf-8"?>' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xp = new \DOMXPath($doc);
    $qs = [];
    foreach ($xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " faq ")]//details') as $d) {
        $q = $xp->query('./summary', $d)->item(0);
        if (!$q) {
            continue;
        }
        $a = [];
        foreach ($d->childNodes as $n) {
            if ($n !== $q) {
                $a[] = trim(preg_replace('/\s+/u', ' ', $n->textContent));
            }
        }
        $a = trim(implode(' ', array_filter($a)));
        if ($a !== '') {
            $qs[] = ['@type' => 'Question', 'name' => trim(preg_replace('/\s+/u', ' ', $q->textContent)), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]];
        }
    }
    if ($qs) {
        $ld = '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qs], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
        $content = preg_replace('~</head>~', $ld . '</head>', $content, 1);
    }
}

// Яндекс.Метрика — счётчик старого сайта, только на боевом домене: заходы с dev не портят статистику клиента
function bt_metrika(): string
{
    if (!preg_match('/^(www\.)?beverteam\.ru$/i', $_SERVER['HTTP_HOST'] ?? '')) {
        return '';
    }
    return <<<'HTML'
<script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();
for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}
k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");
ym(110309111,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true,ecommerce:"dataLayer"});</script>
<noscript><div><img src="https://mc.yandex.ru/watch/110309111" style="position:absolute;left:-9999px" alt=""></div></noscript>
HTML;
}

// sitemap.xml из реальных данных: страницы, разделы и товары каталога, статьи, бренды ремонта; вызывается агентом раз в сутки
function bt_sitemap_build(): string
{
    Loader::includeModule('iblock');
    $host = 'https://beverteam.ru';
    $urls = ['/', '/catalog/', '/arenda-kofemashin/', '/podpiska/', '/servis/', '/servis/remont-kofemashin/', '/podbor-kofe/', '/blog/',
        '/o-kompanii/', '/otzyvy-o-nas/', '/kontakty/', '/oplata-i-dostavka/', '/vozvrat-i-obmen/', '/politika-konfidencialnosti/',
        '/polzovatelskoe-soglashenie/', '/sitemap/'];
    $r = \CIBlockSection::GetList(['LEFT_MARGIN' => 'ASC'], ['IBLOCK_ID' => bt_iblock('catalog'), 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'], false, ['ID', 'SECTION_PAGE_URL']);
    while ($s = $r->GetNext()) {
        $urls[] = $s['SECTION_PAGE_URL'];
    }
    foreach (bt_blog_rubrics() as $rub) {
        $urls[] = $rub['url'];
    }
    foreach (['catalog', 'journal', 'repair_brands'] as $code) {
        $r = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => bt_iblock($code), 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'], false, false, ['ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL', 'TIMESTAMP_X']);
        while ($e = $r->GetNext()) {
            $urls[] = [$e['DETAIL_PAGE_URL'], date('Y-m-d', MakeTimeStamp($e['TIMESTAMP_X']))];
        }
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (array_unique($urls, SORT_REGULAR) as $u) {
        [$loc, $mod] = is_array($u) ? $u : [$u, null];
        $xml .= '<url><loc>' . htmlspecialcharsbx($host . $loc) . '</loc>' . ($mod ? '<lastmod>' . $mod . '</lastmod>' : '') . "</url>\n";
    }
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml', $xml . '</urlset>' . "\n");
    return 'bt_sitemap_build();';
}

// Паролей у покупателей нет: вход по паролю и смена пароля — только для администраторов (группа 1). Обработчики OnBeforeUserLogin / OnBeforeUserChangePassword
function bt_is_admin_login(string $login): bool
{
    $u = \Bitrix\Main\UserTable::getList(['filter' => ['=LOGIN' => $login], 'select' => ['ID'], 'limit' => 1])->fetch();
    return $u && in_array(1, \CUser::GetUserGroup((int)$u['ID']), false);
}

function bt_password_login_guard(array &$fields): bool
{
    if (bt_is_admin_login((string)($fields['LOGIN'] ?? ''))) {
        return true;
    }
    $GLOBALS['APPLICATION']->ThrowException('Вход по паролю отключён. Войдите на сайте по коду из письма.');
    return false;
}

function bt_password_change_guard(array &$fields): bool
{
    if (bt_is_admin_login((string)($fields['LOGIN'] ?? ''))) {
        return true;
    }
    $GLOBALS['APPLICATION']->ThrowException('Пароли на сайте не используются. Войдите по коду из письма.');
    return false;
}

// Разделы и услуги для поиска (шапка и /search/): страницы сайта + направления сервиса + модели в аренду; k — слова, по которым находится
function bt_search_pages(): array
{
    $dirs = bt_iblock('servis_dirs');
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_search_pages', '/bt/blocks')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/blocks');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $dirs);
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . bt_iblock('rent'));
    $pages = [
        ['t' => 'Услуги и сервис', 'u' => '/servis/', 'd' => 'Аренда, продажа, ремонт и обслуживание кофемашин', 'k' => 'услуги сервис обслуживание'],
        ['t' => 'Ремонт и обслуживание кофемашин', 'u' => '/servis/remont-kofemashin/', 'd' => 'Сервисный центр Jetinno · выезд инженера', 'k' => 'ремонт сервис обслуживание починка неисправность диагностика инженер jetinno'],
        ['t' => 'Аренда кофемашин', 'u' => '/arenda-kofemashin/', 'd' => 'Для дома, офиса и кафе', 'k' => 'аренда прокат кофемашина офис кафе'],
        ['t' => 'Кофе по подписке', 'u' => '/podpiska/', 'd' => 'Регулярная доставка кофе, кофемашина бесплатно', 'k' => 'подписка доставка кофе регулярно'],
        ['t' => 'Подбор кофе', 'u' => '/podbor-kofe/', 'd' => '5 вопросов — сорт BOTANICA с ценой', 'k' => 'подбор кофе тест выбрать'],
        ['t' => 'Оплата и доставка', 'u' => '/oplata-i-dostavka/', 'd' => 'Способы оплаты, доставка и самовывоз', 'k' => 'оплата доставка самовывоз курьер'],
        ['t' => 'Возврат и обмен', 'u' => '/vozvrat-i-obmen/', 'd' => 'Условия возврата товара', 'k' => 'возврат обмен гарантия'],
        ['t' => 'О компании', 'u' => '/o-kompanii/', 'd' => 'BEVERTEAM с 2010 года', 'k' => 'о компании beverteam'],
        ['t' => 'Отзывы', 'u' => '/otzyvy-o-nas/', 'd' => 'Что говорят клиенты', 'k' => 'отзывы'],
        ['t' => 'Журнал', 'u' => '/blog/', 'd' => 'Статьи и новости', 'k' => 'журнал статьи новости блог'],
        ['t' => 'Контакты', 'u' => '/kontakty/', 'd' => 'Екатеринбург, ул. Колокольная, 31А', 'k' => 'контакты адрес телефон склад самовывоз'],
    ];
    $known = array_column($pages, 'u');
    $r = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $dirs, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME', 'PREVIEW_TEXT', 'PROPERTY_LINK']);
    while ($f = $r->Fetch()) {
        $u = (string)$f['PROPERTY_LINK_VALUE'];
        if ($u !== '' && !in_array($u, $known, true)) {
            $pages[] = ['t' => $f['NAME'], 'u' => $u, 'd' => 'Услуга · ' . trim(strip_tags((string)$f['PREVIEW_TEXT'])), 'k' => 'услуги'];
        }
    }
    foreach (bt_rent_models() as $m) {
        $pages[] = ['t' => $m['name'], 'u' => '/arenda-kofemashin/', 'd' => 'Аренда · ' . bt_fmt($m['price']) . ' в месяц · ' . $m['audience'], 'k' => 'аренда ' . $m['model']];
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($pages);
    return $pages;
}

// Рубрики журнала (разделы инфоблока journal) с числом активных материалов; пустые рубрики не показываем
function bt_blog_rubrics(): array
{
    $ibId = bt_iblock('journal');
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_blog_rubrics', '/bt/journal')) {
        return $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/journal');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $list = [];
    $r = \CIBlockSection::GetList(['SORT' => 'ASC', 'NAME' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y', 'CNT_ACTIVE' => 'Y'], true, ['ID', 'NAME', 'CODE', 'SECTION_PAGE_URL']);
    while ($s = $r->GetNext()) {
        if ($s['ELEMENT_CNT'] > 0) {
            $list[(int)$s['ID']] = ['name' => $s['~NAME'], 'code' => $s['CODE'], 'url' => $s['~SECTION_PAGE_URL'], 'cnt' => (int)$s['ELEMENT_CNT']];
        }
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($list);
    return $list;
}

// Меню строятся из инфоблоков (каталог, аренда, рубрики журнала), а кеш компонента меню без тегов — сбрасываем его при правке этих инфоблоков
function bt_menu_cache_reset($fields): void
{
    static $done = false;
    $ib = (int)(is_array($fields) ? ($fields['IBLOCK_ID'] ?? 0) : 0);
    if ($done || !in_array($ib, [bt_iblock('catalog'), bt_iblock('journal'), bt_iblock('rent'), bt_iblock('socials')], true)) {
        return;
    }
    $done = true;
    \CBitrixComponent::clearComponentCache('bitrix:menu');
}

// Реквизиты для «Контактов» и PDF: только заполненные поля ИБ «Контакты и реквизиты», по порядку карточки предприятия
function bt_requisites(): array
{
    $co = bt_contacts();
    $addr = trim(implode(', ', array_filter([$co['zip'] ?? '', $co['city'] ?? '', $co['street'] ?? ''])));
    $rows = [
        ['Полное наименование', $co['legal'] ?? ''],
        ['ИНН', $co['inn'] ?? ''],
        ['ОГРНИП', $co['ogrnip'] ?? ''],
        ['Юридический адрес', $co['legal_address'] ?? ''],
        ['Фактический адрес', $addr],
        ['Банк', $co['bank'] ?? ''],
        ['БИК', $co['bik'] ?? ''],
        ['Расчётный счёт', $co['rs'] ?? ''],
        ['Корреспондентский счёт', $co['ks'] ?? ''],
        ['Телефон', $co['phone1'] ?? ''],
        ['E-mail', $co['email'] ?? ''],
        ['Сайт', 'beverteam.ru'],
    ];
    return array_values(array_filter($rows, fn($r) => trim((string)$r[1]) !== ''));
}

// Теги журнала: поле элемента «Теги» (через запятую) → список; облако — все теги с числом материалов, частые первыми
function bt_tags_split(string $tags): array
{
    return array_values(array_unique(array_filter(array_map(fn($t) => trim($t), explode(',', $tags)), 'strlen')));
}

function bt_blog_tags(): array
{
    $cnt = [];
    foreach (bt_posts() as $p) {
        foreach ($p['tags'] ?? [] as $t) {
            $cnt[$t] = ($cnt[$t] ?? 0) + 1;
        }
    }
    uksort($cnt, fn($a, $b) => [$cnt[$b], $a] <=> [$cnt[$a], $b]);
    return $cnt;
}

function bt_tag_url(string $tag): string
{
    return '/blog/?tag=' . rawurlencode($tag);
}

// Картинка для соцсетей 1200×630: фото (часто вертикальное) вписывается целиком на фирменный фон. Готовый файл кешируется в /upload/bt_og/
function bt_og_card(string $src): string
{
    $root = $_SERVER['DOCUMENT_ROOT'];
    $file = $root . parse_url($src, PHP_URL_PATH);
    if (!is_file($file) || !function_exists('imagecreatetruecolor')) {
        return $src;
    }
    $out = '/upload/bt_og/' . md5($src . filemtime($file)) . '.jpg';
    if (is_file($root . $out)) {
        return $out;
    }
    $im = @imagecreatefromstring((string)file_get_contents($file));
    if (!$im) {
        return $src;
    }
    [$W, $H, $pad] = [1200, 630, 40];
    $w = imagesx($im);
    $h = imagesy($im);
    $k = min(($W - 2 * $pad) / $w, ($H - 2 * $pad) / $h);
    $nw = (int)round($w * $k);
    $nh = (int)round($h * $k);
    $card = imagecreatetruecolor($W, $H);
    imagefill($card, 0, 0, imagecolorallocate($card, 0xEF, 0xEF, 0xE9));
    imagecopyresampled($card, $im, (int)(($W - $nw) / 2), (int)(($H - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
    \Bitrix\Main\IO\Directory::createDirectory($root . '/upload/bt_og');
    imagejpeg($card, $root . $out, 86);
    return $out;
}
