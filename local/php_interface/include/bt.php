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
function bt_card(array $m): string
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
        . '<a class="pc__ph" href="' . $e($m['url']) . '">' . ($m['img'] ? '<img src="' . $e($m['img']) . '" alt="' . $e($m['n']) . '" loading="lazy">' : '') . '</a>'
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

    $img = fn($fileId) => $fileId ? (\CFile::ResizeImageGet($fileId, ['width' => 600, 'height' => 600], BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'] ?? '') : '';
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
            'id' => 'r' . $f['ID'], 'code' => $f['CODE'], 'url' => '/arenda-kofemashin/', 'img' => $img($f['PREVIEW_PICTURE']),
            'n' => $f['NAME'], 'p' => (float)$f['PROPERTY_PRICE_MONTH_VALUE'], 'unit' => 'в месяц', 'rent' => 1, 'stock' => 1,
            'par' => implode(' · ', array_filter([$f['PROPERTY_AUDIENCE_VALUE'], $f['PROPERTY_CUPS_PER_DAY_VALUE'] ? 'до ' . $f['PROPERTY_CUPS_PER_DAY_VALUE'] . ' чашек/день' : '', $f['PROPERTY_FREE_FROM_KG_VALUE'] ? 'бесплатно от ' . $f['PROPERTY_FREE_FROM_KG_VALUE'] . ' кг кофе' : ''])),
        ];
    }

    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($data);
    return $data;
}
