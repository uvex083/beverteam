<?php
// Источник заказов и заявок для отчёта /seo-otchet/: вход из поиска запоминается в cookie на 180 дней
// (последний значимый источник: прямой заход его не перебивает), заказ и заявка берут источник при создании.

namespace Bt\Attr;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\DateTime;

const COOKIE = 'bt_src';
const DAYS = 180;
// вход из поиска на эти страницы считаем брендовым запросом (ищут компанию по названию)
const BRAND_PAGES = ['/', '/kontakty/', '/o-kompanii/', '/otzyvy-o-nas/'];

class Table extends \Bitrix\Main\ORM\Data\DataManager
{
    public static function getTableName()
    {
        return 'bt_attr';
    }

    public static function getMap()
    {
        return [
            new Fields\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
            new Fields\StringField('TYPE', ['required' => true, 'size' => 10]),        // visit | order | lead
            new Fields\IntegerField('ENTITY_ID', ['default_value' => 0]),
            new Fields\DatetimeField('CREATED'),
            new Fields\StringField('SOURCE', ['size' => 20]),                           // organic | ads | utm | referral | direct
            new Fields\StringField('ENGINE', ['size' => 20]),
            new Fields\StringField('LANDING', ['size' => 255]),
            new Fields\StringField('REF_HOST', ['size' => 100]),
            new Fields\DatetimeField('ENTRY_AT', ['nullable' => true]),
            new Fields\StringField('BRAND_AUTO', ['size' => 1]),
            new Fields\StringField('BRAND_MANUAL', ['size' => 1, 'nullable' => true]),
            new Fields\FloatField('AMOUNT', ['nullable' => true]),
            new Fields\StringField('YM_UID', ['size' => 40]),
            new Fields\StringField('PHRASE', ['size' => 255, 'nullable' => true]),      // запрос из Метрики: null — ещё не нашли визит, '' — запрос не передан
            new Fields\DatetimeField('PHRASE_AT', ['nullable' => true]),
        ];
    }
}

// источник по адресу и referer; null — прямой заход, переход внутри сайта или служебный сайт (оплата, вход, почта)
function classify(string $ref, array $get, string $host, string $path = '/', bool $hasSource = false): ?array
{
    $own = fn(string $h) => $h !== '' && ($h === preg_replace('/^www\./', '', strtolower($host)) || preg_match('/(^|\.)beverteam\.(ru|na4u\.ru)$/', $h));
    // приложения Android присылают android-app://<пакет>/
    if (str_starts_with($ref, 'android-app://')) {
        $pkg = strtolower((string)parse_url($ref, PHP_URL_HOST));
        $apps = ['com.google.android.googlequicksearchbox' => 'google', 'ru.yandex.searchplugin' => 'yandex', 'com.yandex.browser' => 'yandex', 'ru.yandex.mobile' => 'yandex'];
        if (isset($apps[$pkg])) {
            return ['s' => 'organic', 'e' => $apps[$pkg], 'r' => mb_substr($pkg, 0, 100)];
        }
        $ref = '';   // почта, мессенджеры и прочие приложения — как прямой заход
    }
    $refHost = preg_replace('/^www\./', '', strtolower((string)parse_url($ref, PHP_URL_HOST)));
    // метки на ссылках внутри своего сайта источник не меняют
    if (!$own($refHost)) {
        $medium = strtolower((string)($get['utm_medium'] ?? ''));
        if (isset($get['yclid']) || isset($get['gclid']) || preg_match('/^(cpc|ppc|cpm|paid|cpa)/', $medium)) {
            return ['s' => 'ads', 'e' => isset($get['gclid']) ? 'google' : (isset($get['yclid']) ? 'yandex' : (string)($get['utm_source'] ?? '')), 'r' => $refHost];
        }
        if (isset($get['utm_source']) && $medium !== 'organic') {
            return ['s' => 'utm', 'e' => (string)$get['utm_source'], 'r' => $refHost];
        }
    }
    $engines = [
        'yandex' => '/^(yandex\.(ru|com|by|kz|ua|uz|az|com\.tr|com\.am|com\.ge|co\.il|tm|tj|md|lt|lv|ee|fr|eu)|ya\.ru)$/',
        'google' => '/^(images\.)?google\.(com|[a-z]{2}|com?\.[a-z]{2})$/',
        'bing' => '/^bing\.com$/',
        'mail' => '/^go\.mail\.ru$/',
        'duckduckgo' => '/^duckduckgo\.com$/',
        'rambler' => '/^nova\.rambler\.ru$/',
        'yahoo' => '/^search\.yahoo\.com$/',
    ];
    foreach ($engines as $e => $re) {
        if (preg_match($re, $refHost)) {
            return ['s' => 'organic', 'e' => $e, 'r' => $refHost];
        }
    }
    // ysclid Яндекс добавляет к переходам из поиска, но он остаётся и в скопированной ссылке — засчитываем, только если источника ещё нет
    if ($refHost === '' && isset($get['ysclid']) && !$hasSource) {
        return ['s' => 'organic', 'e' => 'yandex', 'r' => ''];
    }
    // служебные сайты (оплата, 3-D Secure, вход через сервисы, почта) и возврат в кабинет после оплаты источник не меняют
    if ($refHost === '' || $own($refHost) || str_starts_with($path, '/personal/')
        || preg_match('/(^|\.)(tbank\.ru|tinkoff\.ru|nspk\.ru|sber\.ru|sberbank\.ru|alfabank\.ru|vk\.ru|id\.vk\.com|oauth\.vk\.com|passport\.yandex\.[a-z.]+|oauth\.yandex\.[a-z.]+|mail\.yandex\.[a-z.]+|e\.mail\.ru|mail\.google\.com|mail\.rambler\.ru|outlook\.live\.com)$/', $refHost)) {
        return null;
    }
    return ['s' => 'referral', 'e' => '', 'r' => $refHost];
}

// адрес страницы входа без index.php и в нижнем регистре: /Kontakty/index.php → /kontakty/
function landing(string $path): string
{
    $p = mb_strtolower(preg_replace('#/index\.php$#', '/', $path));
    return $p === '' ? '/' : $p;
}

// подпись cookie: подменённое значение не принимаем
function sign(string $v): string
{
    $key = Option::get('bt', 'attr_secret', '');
    if ($key === '') {
        $key = bin2hex(random_bytes(16));
        Option::set('bt', 'attr_secret', $key);
    }
    return substr(hash_hmac('sha256', $v, $key), 0, 20);
}

// main:OnEpilog — запомнить значимый источник входа; вход из поиска — ещё и строкой статистики.
// На эпилоге уже известно, что страница не 404 и не редирект (после LocalRedirect эпилога нет)
function track(): void
{
    if (defined('ADMIN_SECTION') || defined('ERROR_404') || PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' || headers_sent()) {
        return;
    }
    $path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if (!preg_match('#^/[^/\\\\]#', $path . 'x') || preg_match('#^/(bitrix|local|upload)/#', $path) || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== ''
        || preg_match('/bot|crawl|spider|slurp|preview|headless|lighthouse|monitor|curl|wget|python/i', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
        return;
    }
    $prev = current();
    $c = classify((string)($_SERVER['HTTP_REFERER'] ?? ''), $_GET, (string)($_SERVER['HTTP_HOST'] ?? ''), $path, (bool)$prev);
    if (!$c) {
        return;
    }
    $c = ['s' => $c['s'], 'e' => mb_substr((string)$c['e'], 0, 20), 'r' => mb_substr((string)$c['r'], 0, 100), 'l' => mb_substr(landing($path), 0, 255)];
    $c['b'] = in_array($c['l'], BRAND_PAGES, true) ? 'Y' : 'N';
    $c['t'] = time();
    $json = json_encode($c, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $val = sign($json) . '.' . $json;
    setcookie(COOKIE, $val, ['expires' => time() + DAYS * 86400, 'path' => '/', 'secure' => \CMain::IsHTTPS(), 'httponly' => true, 'samesite' => 'Lax']);
    $_COOKIE[COOKIE] = $val;
    // обновление страницы и возврат «назад» в выдачу в течение 30 минут — тот же вход
    $same = $prev && $prev['s'] === $c['s'] && $prev['e'] === $c['e'] && $prev['l'] === $c['l'] && $c['t'] - $prev['t'] < 1800;
    if ($c['s'] === 'organic' && !$same) {
        add('visit', 0, $c);
    }
}

// действующий источник посетителя (подпись верна, не старше 180 дней) или null
function current(): ?array
{
    [$sig, $json] = array_pad(explode('.', (string)($_COOKIE[COOKIE] ?? ''), 2), 2, '');
    $c = $json !== '' && hash_equals(sign($json), $sig) ? json_decode($json, true) : null;
    if (!is_array($c) || !isset(SOURCES[$c['s'] ?? '']) || time() - (int)($c['t'] ?? 0) > DAYS * 86400) {
        return null;
    }
    return ['s' => (string)$c['s'], 'e' => mb_substr((string)($c['e'] ?? ''), 0, 20), 'r' => mb_substr((string)($c['r'] ?? ''), 0, 100),
        'l' => mb_substr((string)($c['l'] ?? ''), 0, 255), 'b' => ($c['b'] ?? 'N') === 'Y' ? 'Y' : 'N', 't' => (int)$c['t']];
}

// строка отчёта; сбой записи не должен ронять страницу или оформление заказа
function add(string $type, int $id, ?array $c): void
{
    try {
        if ($type !== 'visit' && (!$id || Table::getCount(['=TYPE' => $type, '=ENTITY_ID' => $id]))) {
            return;
        }
        Table::add([
            'TYPE' => $type, 'ENTITY_ID' => $id, 'CREATED' => new DateTime(),
            'SOURCE' => $c['s'] ?? 'direct', 'ENGINE' => mb_substr((string)($c['e'] ?? ''), 0, 20), 'LANDING' => mb_substr((string)($c['l'] ?? ''), 0, 255),
            'REF_HOST' => mb_substr((string)($c['r'] ?? ''), 0, 100), 'ENTRY_AT' => isset($c['t']) ? DateTime::createFromTimestamp((int)$c['t']) : null,
            'BRAND_AUTO' => ($c['b'] ?? 'N') === 'Y' ? 'Y' : 'N', 'YM_UID' => mb_substr(preg_replace('/\D/', '', (string)($_COOKIE['_ym_uid'] ?? '')), 0, 40),
        ]);
    } catch (\Throwable $e) {
        \CEventLog::Add(['SEVERITY' => 'ERROR', 'AUDIT_TYPE_ID' => 'BT_ATTR', 'MODULE_ID' => 'main', 'ITEM_ID' => "$type $id", 'DESCRIPTION' => $e->getMessage()]);
    }
}

// sale:OnSaleOrderSaved (D7) — новый заказ с сайта получает источник покупателя; заказы из админки и CLI не трогаем (там cookie менеджера)
function onOrderSaved(\Bitrix\Main\Event $event): void
{
    if (!$event->getParameter('IS_NEW') || defined('ADMIN_SECTION') || PHP_SAPI === 'cli') {
        return;
    }
    $order = $event->getParameter('ENTITY');
    $order && add('order', (int)$order->getId(), current());
}

// iblock:OnAfterIBlockElementAdd — заявка с сайта
function onLeadAdd(array &$f): void
{
    if (empty($f['ID']) || (int)$f['IBLOCK_ID'] !== (int)bt_iblock('form_requests') || defined('ADMIN_SECTION') || PHP_SAPI === 'cli') {
        return;
    }
    add('lead', (int)$f['ID'], current());
}

function percent(): float
{
    return (float)Option::get('bt', 'seo_pct', '20');
}

function withDelivery(): bool
{
    return Option::get('bt', 'seo_with_delivery', 'N') === 'Y';
}

const ENGINES = ['yandex' => 'Яндекс', 'google' => 'Google', 'bing' => 'Bing', 'mail' => 'Mail.ru', 'duckduckgo' => 'DuckDuckGo', 'rambler' => 'Рамблер', 'yahoo' => 'Yahoo'];
const SOURCES = ['organic' => 'Поиск', 'ads' => 'Реклама', 'utm' => 'Ссылка с меткой', 'referral' => 'Переход с сайта', 'direct' => 'Прямой заход'];

// фильтр отчёта из запроса: период (по умолчанию текущий месяц), источник, брендовость
function filter(array $q): array
{
    $d = fn($v, $def) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? (string)$v : $def;
    return [
        'from' => $d($q['from'] ?? '', date('Y-m-01')),
        'to' => $d($q['to'] ?? '', date('Y-m-d')),
        'src' => ($q['src'] ?? '') === 'all' ? 'all' : 'organic',
        'brand' => in_array($q['brand'] ?? '', ['brand', 'all'], true) ? $q['brand'] : 'nonbrand',
    ];
}

// строки отчёта одного типа (order | lead | visit) за период с расчётом: засчитана ли строка, база и вознаграждение
function report(string $type, array $f): array
{
    $flt = ['=TYPE' => $type,
        '>=CREATED' => new DateTime($f['from'] . ' 00:00:00', 'Y-m-d H:i:s'),
        '<=CREATED' => new DateTime($f['to'] . ' 23:59:59', 'Y-m-d H:i:s')];
    if ($f['src'] === 'organic') {
        $flt['=SOURCE'] = 'organic';
    }
    $rows = [];
    foreach (Table::getList(['filter' => $flt, 'order' => ['CREATED' => 'DESC', 'ID' => 'DESC']])->fetchAll() as $r) {
        // запрос известен из Метрики — бренд по запросу, иначе по странице входа
        if ((string)$r['PHRASE'] !== '') {
            $r['BRAND_AUTO'] = isBrandPhrase($r['PHRASE']) ? 'Y' : 'N';
        }
        $r['BRAND'] = $r['BRAND_MANUAL'] ?: $r['BRAND_AUTO'];
        if ($f['brand'] === 'nonbrand' && $r['BRAND'] === 'Y' || $f['brand'] === 'brand' && $r['BRAND'] !== 'Y') {
            continue;
        }
        $rows[(int)$r['ID']] = $r;
    }
    $pct = percent();
    $ids = array_filter(array_map(fn($r) => (int)$r['ENTITY_ID'], $rows));
    if ($type === 'order' && $ids && \Bitrix\Main\Loader::includeModule('sale')) {
        $orders = [];
        foreach (\Bitrix\Sale\Internals\OrderTable::getList(['filter' => ['@ID' => $ids],
            'select' => ['ID', 'ACCOUNT_NUMBER', 'DATE_INSERT', 'PRICE', 'PRICE_DELIVERY', 'CANCELED', 'STATUS_ID', 'PAYED', 'PERSON_TYPE_ID']])->fetchAll() as $o) {
            $orders[(int)$o['ID']] = $o;
        }
        $names = [];
        foreach (\Bitrix\Sale\Internals\OrderPropsValueTable::getList(['filter' => ['@ORDER_ID' => $ids, '@CODE' => ['FIO', 'CONTACT_PERSON', 'COMPANY']],
            'select' => ['ORDER_ID', 'CODE', 'VALUE']])->fetchAll() as $p) {
            $names[(int)$p['ORDER_ID']][$p['CODE']] = (string)$p['VALUE'];
        }
        $st = array_column(\Bitrix\Sale\Internals\StatusLangTable::getList(['filter' => ['=LID' => 'ru'], 'select' => ['STATUS_ID', 'NAME']])->fetchAll(), 'NAME', 'STATUS_ID');
    }
    if ($type === 'lead' && $ids) {
        $leads = [];
        \Bitrix\Main\Loader::includeModule('iblock');
        $res = \CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock('form_requests'), 'ID' => $ids], false, false,
            ['ID', 'NAME', 'PROPERTY_TOPIC', 'PROPERTY_CLIENT_NAME', 'PROPERTY_PHONE', 'PROPERTY_EMAIL']);
        while ($l = $res->Fetch()) {
            $leads[(int)$l['ID']] = $l;
        }
    }
    foreach ($rows as &$r) {
        $id = (int)$r['ENTITY_ID'];
        $why = $r['SOURCE'] !== 'organic' ? 'не из поиска' : ($r['BRAND'] === 'Y' ? 'брендовый запрос' : '');
        $r['BASE'] = 0.0;
        if ($type === 'order') {
            $o = $orders[$id] ?? null;
            $n = $names[$id] ?? [];
            $r['ORDER'] = $o ? ['num' => $o['ACCOUNT_NUMBER'], 'date' => $o['DATE_INSERT'], 'price' => (float)$o['PRICE'],
                'delivery' => (float)$o['PRICE_DELIVERY'], 'canceled' => $o['CANCELED'] === 'Y', 'paid' => $o['PAYED'] === 'Y',
                'status' => $st[$o['STATUS_ID']] ?? $o['STATUS_ID'], 'buyer' => trim(($n['COMPANY'] ?? '') . ' ' . ($n['CONTACT_PERSON'] ?? $n['FIO'] ?? ''))] : null;
            $why = $why ?: (!$o ? 'заказ удалён' : ($o['CANCELED'] === 'Y' ? 'заказ отменён' : ''));
            $r['BASE'] = $o ? (withDelivery() ? (float)$o['PRICE'] : (float)$o['PRICE'] - (float)$o['PRICE_DELIVERY']) : 0.0;
        } elseif ($type === 'lead') {
            $l = $leads[$id] ?? null;
            $r['LEAD'] = $l ? ['topic' => (string)$l['PROPERTY_TOPIC_VALUE'], 'name' => (string)$l['PROPERTY_CLIENT_NAME_VALUE'],
                'phone' => (string)$l['PROPERTY_PHONE_VALUE'], 'email' => (string)$l['PROPERTY_EMAIL_VALUE']] : null;
            $why = $why ?: (!$l ? 'заявка удалена' : ((float)$r['AMOUNT'] <= 0 ? 'сумма не указана' : ''));
            $r['BASE'] = (float)$r['AMOUNT'];
        }
        $r['WHY'] = $why;
        $r['COUNTED'] = $why === '';
        $r['FEE'] = $r['COUNTED'] && $type !== 'visit' ? round($r['BASE'] * $pct / 100, 2) : 0.0;
    }
    unset($r);
    return $rows;
}

// итоги по строкам: сколько засчитано, база, вознаграждение
function totals(array $rows): array
{
    $c = array_filter($rows, fn($r) => $r['COUNTED']);
    return ['all' => count($rows), 'n' => count($c), 'base' => array_sum(array_column($c, 'BASE')), 'fee' => array_sum(array_column($c, 'FEE'))];
}

// брендовый запрос: название компании в любом написании («бэвертим», «бевертим», «beverteam»); корни — опция bt/seo_brand_re
function isBrandPhrase(string $phrase): bool
{
    $re = Option::get('bt', 'seo_brand_re', 'бэвер|бевер|бивер|bever|beaver');
    return (bool)@preg_match('/' . str_replace('/', '\/', $re) . '/iu', $phrase);
}

// поисковый запрос последнего значимого входа перед заказом или заявкой — из Метрики по номеру посетителя (_ym_uid).
// null — визита в Метрике нет (ещё не обработан, блокировщик рекламы); '' — визит есть, запрос поисковик не передал
function metrikaPhrase(string $uid, DateTime $at): ?string
{
    $file = Option::get('bt', 'metrika_token_file', '');
    $token = $file !== '' && is_readable($file) ? trim((string)file_get_contents($file)) : '';
    if ($token === '' || !ctype_digit($uid)) {
        throw new \RuntimeException('нет токена Метрики');
    }
    $http = new \Bitrix\Main\Web\HttpClient(['socketTimeout' => 10, 'streamTimeout' => 15]);
    $http->setHeader('Authorization', 'OAuth ' . $token);
    $ts = $at->getTimestamp();
    $json = $http->get('https://api-metrika.yandex.net/stat/v1/data?' . http_build_query([
        'ids' => (int)Option::get('bt', 'metrika_counter', '110309111'), 'accuracy' => 'full', 'limit' => 1, 'sort' => '-ym:s:dateTime',
        'date1' => date('Y-m-d', max($ts - DAYS * 86400, strtotime('2026-09-24'))), 'date2' => date('Y-m-d', $ts),
        'metrics' => 'ym:s:visits', 'dimensions' => 'ym:s:dateTime,ym:s:lastSignTrafficSource,ym:s:lastSignSearchPhrase',
        'filters' => "ym:s:clientID=='$uid' AND ym:s:dateTime<='" . date('Y-m-d H:i:s', $ts) . "'",
    ]));
    if ($http->getStatus() !== 200 || !is_array($d = json_decode((string)$json, true))) {
        throw new \RuntimeException('Метрика ответила ' . $http->getStatus() . ': ' . mb_substr((string)$json, 0, 200));
    }
    $row = $d['data'][0]['dimensions'] ?? null;
    if (!$row) {
        return null;
    }
    return ($row[1]['id'] ?? '') === 'organic' ? mb_substr((string)($row[2]['name'] ?? ''), 0, 255) : '';
}

// агент: запросы для новых заказов и заявок. Визит появляется в Метрике не сразу — не нашли, повторяем раз в час двое суток
function phraseAgent(): string
{
    $now = time();
    $rows = Table::getList(['select' => ['ID', 'YM_UID', 'CREATED', 'PHRASE_AT'], 'order' => ['ID' => 'ASC'], 'limit' => 20, 'filter' => [
        '@TYPE' => ['order', 'lead'], '!=YM_UID' => '', '=PHRASE' => null, '<=CREATED' => DateTime::createFromTimestamp($now - 1200),
        ['LOGIC' => 'OR', '=PHRASE_AT' => null, ['<=PHRASE_AT' => DateTime::createFromTimestamp($now - 3600), '>=CREATED' => DateTime::createFromTimestamp($now - 2 * 86400)]],
    ]])->fetchAll();
    foreach ($rows as $r) {
        try {
            $p = metrikaPhrase((string)$r['YM_UID'], $r['CREATED']);
        } catch (\Throwable $e) {
            \CEventLog::Add(['SEVERITY' => 'WARNING', 'AUDIT_TYPE_ID' => 'BT_ATTR_METRIKA', 'MODULE_ID' => 'main', 'ITEM_ID' => (string)$r['ID'], 'DESCRIPTION' => $e->getMessage()]);
            break;
        }
        Table::update($r['ID'], ['PHRASE' => $p, 'PHRASE_AT' => new DateTime()]);
    }
    return '\Bt\Attr\phraseAgent();';
}
