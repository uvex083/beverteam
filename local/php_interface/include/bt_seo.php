<?php
// Посадочные страницы для SEO (ИБ seo_landings): по адресу страницы — свои title, description, H1 и SEO-текст

// Адрес без служебных параметров: utm, пагинация, сортировка, ajax. Параметры фильтра остаются — посадочная может быть страницей фильтра
function bt_url_key(string $url): string
{
    $p = parse_url(trim($url));
    $path = preg_replace('~/index\.php$~', '/', $p['path'] ?? '/');
    parse_str($p['query'] ?? '', $q);
    $q = array_filter($q, fn($v, $k) => $v !== '' && !preg_match('~^(utm_|PAGEN_|bxajaxid$|ajax$|set_filter$|del_filter$|clear_cache$|sort$|yclid$|gclid$|fbclid$|_openstat$)~i', $k), ARRAY_FILTER_USE_BOTH);
    ksort($q);
    return mb_strtolower($path) . ($q ? '?' . http_build_query($q) : '');
}

// Посадочная для текущего адреса или null
function bt_landing(): ?array
{
    static $done = false, $cur = null;
    if ($done) {
        return $cur;
    }
    $done = true;
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    if (defined('ADMIN_SECTION') || preg_match('~^/(bitrix|local|upload)/~', $uri) || !\Bitrix\Main\Loader::includeModule('iblock') || !($ibId = bt_iblock('seo_landings'))) {
        return null;
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_landings', '/bt/seo')) {
        $map = $cache->getVars();
    } else {
        $cache->startDataCache();
        $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/seo');
        $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
        $map = [];
        $r = \CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'], false, false,
            ['ID', 'IBLOCK_ID', 'PROPERTY_URL', 'PROPERTY_TITLE', 'PROPERTY_DESCRIPTION', 'PROPERTY_H1', 'PROPERTY_SEO_TEXT']);
        while ($x = $r->Fetch()) {
            $key = bt_url_key((string)$x['PROPERTY_URL_VALUE']);
            $map[$key] ??= [
                'title' => trim((string)$x['PROPERTY_TITLE_VALUE']), 'description' => trim((string)$x['PROPERTY_DESCRIPTION_VALUE']),
                'h1' => trim((string)$x['PROPERTY_H1_VALUE']), 'text' => trim((string)($x['PROPERTY_SEO_TEXT_VALUE']['TEXT'] ?? '')),
            ];
        }
        $GLOBALS['CACHE_MANAGER']->EndTagCache();
        $cache->endDataCache($map);
    }
    return $cur = $map[bt_url_key($uri)] ?? null;
}

// OnEpilog: title и description (из них же og:title и og:description), H1 у страниц, где он выводится через заголовок страницы
function bt_landing_meta(): void
{
    global $APPLICATION;
    if (!($l = bt_landing())) {
        return;
    }
    $l['title'] !== '' and $APPLICATION->SetPageProperty('title', $l['title']);
    $l['description'] !== '' and $APPLICATION->SetPageProperty('description', $l['description']);
    $l['h1'] !== '' and $APPLICATION->SetTitle($l['h1']);
}

// OnEndBufferContent: H1 в готовой разметке (товар, раздел каталога) и SEO-текст — вместо текста раздела или под содержимым; на 2-й и дальше страницах списка текста нет
function bt_landing_body(&$content): void
{
    if (!str_contains($content, '</head>') || !($l = bt_landing())) {
        return;
    }
    if ($l['h1'] !== '') {
        $content = preg_replace_callback('~(<h1\b[^>]*>).*?(</h1>)~s', fn($m) => $m[1] . htmlspecialcharsbx($l['h1']) . $m[2], $content, 1);
    }
    $paged = (bool)array_filter($_GET, fn($v, $k) => str_starts_with((string)$k, 'PAGEN_') && (int)$v > 1, ARRAY_FILTER_USE_BOTH);
    if ($l['text'] === '' || $paged) {
        return;
    }
    // блок «seo» страницы (описание раздела каталога и т.п.) — меняем содержимое, считая вложенные div
    if (preg_match('~<div class="seo[ "][^>]*>~', $content, $m, PREG_OFFSET_CAPTURE)) {
        $start = $m[0][1] + strlen($m[0][0]);
        $depth = 1;
        $pos = $start;
        while ($depth && preg_match('~<(/?)div\b[^>]*>~i', $content, $t, PREG_OFFSET_CAPTURE, $pos)) {
            $depth += $t[1][0] === '/' ? -1 : 1;
            $pos = $t[0][1] + strlen($t[0][0]);
            if (!$depth) {
                $content = substr($content, 0, $start) . bt_seo_html($l['text']) . substr($content, $t[0][1]);
                return;
            }
        }
    }
    $content = preg_replace('~<footer class="ftr"~', '<section class="sec sec--t0 seo-landing"><div class="wrap"><div class="seo post__body">' . bt_seo_html($l['text']) . '</div></div></section>$0', $content, 1);
}

// Понятные коды значений списка для ЧПУ фильтра (/filter/country-is-efiopiya/apply/): Битрикс сам ставит хеш — меняем его на транслит названия
function bt_enum_codes(int $propId): int
{
    $used = $fix = [];
    $r = \CIBlockPropertyEnum::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['PROPERTY_ID' => $propId]);
    while ($x = $r->Fetch()) {
        preg_match('~^[0-9a-f]{32}$~', (string)$x['XML_ID']) ? $fix[] = $x : $used[mb_strtolower((string)$x['XML_ID'])] = true;
    }
    foreach ($fix as $x) {
        $base = \CUtil::translit((string)$x['VALUE'], 'ru', ['max_len' => 50, 'change_case' => 'L', 'replace_space' => '-', 'replace_other' => '-', 'delete_repeat_replace' => true]);
        $base = trim($base, '-') ?: 'v' . $x['ID'];
        for ($code = $base, $i = 2; isset($used[$code]); $i++) {
            $code = $base . '-' . $i;
        }
        $used[$code] = true;
        \CIBlockPropertyEnum::Update($x['ID'], ['XML_ID' => $code]);
    }
    return count($fix);
}

// OnAfterIBlockPropertyAdd / OnAfterIBlockPropertyUpdate: значения, добавленные в админке
function bt_enum_codes_on_save(array $f): void
{
    // название, сортировка и галочки свойства видны на сайте сразу
    (int)($f['IBLOCK_ID'] ?? 0) > 0 && \CIBlock::clearIblockTagCache((int)$f['IBLOCK_ID']);
    if (($f['PROPERTY_TYPE'] ?? '') === 'L' && (int)($f['ID'] ?? 0) > 0) {
        bt_enum_codes((int)$f['ID']);
    }
}
