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
function bt_messengers(): array
{
    $co = bt_contacts();
    $list = [];
    foreach (['tg' => 'Telegram', 'wa' => 'WhatsApp', 'max' => 'MAX', 'vk' => 'ВКонтакте'] as $code => $name) {
        $list[] = [$code, $name, $co[$code] ?? ''];
    }
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
            $packs .= '<button type="button" data-kg="' . $t['kg'] . '" aria-pressed="false">' . $t['kg'] . ' кг' . ($pct ? '<s>−' . $pct . '%</s>' : '') . '</button>';
        }
        $packs = '<div class="packs " data-packs="' . $e($m['id']) . '">' . $packs . '</div>';
    }
    $rent = !empty($m['rent']);
    $price = $m['p'] ? bt_fmt($m['p']) : 'По запросу';
    $sub = !empty($m['unit']) ? '<s>' . $e($m['unit']) . '</s>' : (!empty($m['bulk']) ? '<s>' . bt_fmt($m['bulk'][0]['p']) . ' за кг</s>' : (!empty($m['pre']) ? '<s>предзаказ</s>' : ''));
    $old = !empty($m['old']) ? '<span class="price--old">' . bt_fmt($m['old']) . '</span>' : '';
    $ctl = $rent ? '<a class="btn btn--sm" href="/arenda-kofemashin/#calc">Арендовать</a>'
        : '<button class="btn btn--sm" data-add="' . $e($m['id']) . '">' . (!empty($m['pre']) ? 'Предзаказ' : 'В корзину') . '</button>';
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
            $items[(string)$bi->getProductId()] = (float)$bi->getQuantity();
        }
    }
    return ['items' => (object)$items, 'sum' => (float)$basket->getPrice()];
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
    $acc = array_filter($cats, fn($c) => $c['h'] === '/magazin/aksessuary/');
    $cats = array_values(array_merge(array_filter($cats, fn($c) => $c['h'] !== '/magazin/aksessuary/'), $acc));

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
        ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'ACTIVE_FROM', 'DATE_CREATE', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL', 'PROPERTY_KIND', 'PROPERTY_RUBRIC', 'PROPERTY_READ_TIME']);
    while ($f = $r->GetNext()) {
        $posts[] = bt_post_data($f);
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($posts);
    return $posts;
}

// Поля элемента журнала (выборка с PROPERTY_KIND/RUBRIC/READ_TIME) → формат BT_POSTS
function bt_post_data(array $f): array
{
    $kind = 'article';
    if (!empty($f['PROPERTY_KIND_ENUM_ID'])) {
        $kind = \CIBlockPropertyEnum::GetByID($f['PROPERTY_KIND_ENUM_ID'])['XML_ID'] ?? 'article';
    }
    $date = $f['ACTIVE_FROM'] ?: $f['DATE_CREATE'];
    return [
        'id' => $f['CODE'], 'kind' => $kind, 'cat' => ($f['~PROPERTY_RUBRIC_VALUE'] ?? '') ?: ($kind === 'news' ? 'Новости' : 'Статьи'),
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
        . ($p['kind'] === 'news' ? '<span class="tag tag--n">Новость</span>' : '') . '</div>'
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

// Плитка разделов на главной: корневые разделы каталога с картинкой раздела + аренда
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
    $r = \CIBlockSection::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $catId, 'ACTIVE' => 'Y', 'DEPTH_LEVEL' => 1, 'CNT_ACTIVE' => 'Y'], true, ['ID', 'NAME', 'PICTURE', 'SECTION_PAGE_URL', 'LEFT_MARGIN', 'RIGHT_MARGIN']);
    while ($s = $r->GetNext()) {
        $subs = (int)(($s['RIGHT_MARGIN'] - $s['LEFT_MARGIN'] - 1) / 2);
        $tiles[] = [
            'name' => $s['~NAME'], 'url' => $s['~SECTION_PAGE_URL'],
            'img' => bt_img($s['PICTURE'], 400, 300),
            'note' => $subs ? $plural($subs, ['категория', 'категории', 'категорий']) : $plural((int)$s['ELEMENT_CNT'], ['модель', 'модели', 'моделей']),
        ];
    }
    $rent = bt_rent_models();
    if ($rent) {
        // аренда — после кофемашин, как в меню
        array_splice($tiles, min(3, count($tiles)), 0, [[
            'name' => 'Аренда кофемашин', 'url' => '/arenda-kofemashin/', 'img' => $rent[0]['img'],
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
    $u = \Bitrix\Main\UserTable::getList(['filter' => ['=ID' => (int)$USER->GetID()], 'select' => ['NAME', 'LAST_NAME', 'EMAIL', 'LOGIN']])->fetch();
    return $u ? ['name' => trim($u['NAME'] . ' ' . $u['LAST_NAME']), 'email' => (string)($u['EMAIL'] ?: $u['LOGIN'])] : null;
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
    $url = $host . $APPLICATION->GetCurPage(false);
    $img = $APPLICATION->GetPageProperty('og_image') ?: '/local/templates/beverteam/images/og-logo.png';
    $html = '<meta property="og:type" content="' . $e($APPLICATION->GetPageProperty('og_type') ?: 'website') . '"><meta property="og:site_name" content="BEVERTEAM">'
        . '<meta property="og:locale" content="ru_RU"><meta property="og:title" content="' . $e($title) . '">'
        . '<meta property="og:description" content="' . $e($APPLICATION->GetPageProperty('description')) . '">'
        . '<meta property="og:url" content="' . $e($url) . '"><meta property="og:image" content="' . $e(str_starts_with($img, 'http') ? $img : $host . $img) . '">'
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
    $urls = ['/', '/magazin/', '/arenda-kofemashin/', '/podpiska/', '/servis/', '/servis/remont-kofemashin/', '/podbor-kofe/', '/news/',
        '/o-kompanii/', '/otzyvy-o-nas/', '/kontakty/', '/oplata-i-dostavka/', '/vozvrat-i-obmen/', '/politika-konfidencialnosti/',
        '/polzovatelskoe-soglashenie/', '/sitemap/'];
    $r = \CIBlockSection::GetList(['LEFT_MARGIN' => 'ASC'], ['IBLOCK_ID' => bt_iblock('catalog'), 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'], false, ['ID', 'SECTION_PAGE_URL']);
    while ($s = $r->GetNext()) {
        $urls[] = $s['SECTION_PAGE_URL'];
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
