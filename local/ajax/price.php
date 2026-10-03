<?php
// Подписка на изменение цен прайс-листа: POST action=sub (email, agree, sessid) → {ok}; GET unsub=<ключ> — отписка по ссылке из письма.
// Редактор цен на странице прайса (право «Изменение цен»): action=ed_load — товары и журнал, ed_save — сохранить, ed_import — разобрать файл Excel/CSV
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
$req = Context::getCurrent()->getRequest();
$ib = bt_iblock('price_subs');

$unsub = (string)$req->getQuery('unsub');
if ($unsub !== '') {
    if ($ib && preg_match('/^[a-f0-9]{32}$/', $unsub)
        && ($el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ib, '=PROPERTY_TOKEN' => $unsub], false, false, ['ID'])->Fetch())) {
        (new CIBlockElement())->Update($el['ID'], ['ACTIVE' => 'N']);
    }
    LocalRedirect('/price/?unsub=1');
}

header('Content-Type: application/json; charset=utf-8');
$out = function (int $code, array $data) {
    http_response_code($code);
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
};
$act = (string)$req->getPost('action');
if (str_starts_with($act, 'ed_')) {
    if (!$req->isPost() || !check_bitrix_sessid() || !bt_price_editor() || !Loader::includeModule('catalog')) {
        $out(403, ['ok' => false, 'error' => 'access']);
    }
    if ($act === 'ed_load') {
        $out(200, ['ok' => true, 'rows' => bt_pe_rows(), 'log' => bt_pe_log(), 'subs' => $ib ? (int)CIBlockElement::GetList([], ['IBLOCK_ID' => $ib, 'ACTIVE' => 'Y'], []) : 0]);
    }
    if ($act === 'ed_save') {
        $out(200, bt_pe_save((array)json_decode((string)$req->getPost('items'), true), $req->getPost('notify') === 'Y'));
    }
    if ($act === 'ed_import') {
        $out(200, bt_pe_import($req->getFile('file')));
    }
    $out(400, ['ok' => false, 'error' => 'action']);
}

if (!$req->isPost() || !check_bitrix_sessid() || !$ib) {
    $out(403, ['ok' => false, 'error' => 'sessid']);
}
if ((string)$req->getPost('website') !== '') {
    $out(200, ['ok' => true]);
}
$email = mb_strtolower(trim((string)$req->getPost('email')));
$err = [];
if (!check_email($email, true)) {
    $err['email'] = $email === '' ? 'Это поле нужно заполнить' : 'Проверьте адрес: нужен формат mail@company.ru';
}
if ($req->getPost('agree') !== 'Y') {
    $err['agree'] = 'Нужно согласие';
}
if ($err) {
    $out(200, ['ok' => false, 'errors' => $err]);
}
$el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ib, '=NAME' => $email], false, false, ['ID'])->Fetch();
$o = new CIBlockElement();
if ($el) {
    $o->Update($el['ID'], ['ACTIVE' => 'Y']);
} else {
    $id = $o->Add(['IBLOCK_ID' => $ib, 'NAME' => $email, 'ACTIVE' => 'Y', 'PROPERTY_VALUES' => ['TOKEN' => md5(random_bytes(16))]]);
    $id or $out(200, ['ok' => false, 'error' => 'save']);
}
$out(200, ['ok' => true]);

// ---------- редактор цен ----------

// товары прайса в его порядке: раздел, подраздел, название и текущие цены из базы (не из кеша)
function bt_pe_rows(): array
{
    $rows = [];
    foreach (bt_price_list() as $code => $t) {
        foreach ($t['subs'] as $sub => $items) {
            foreach ($items as $m) {
                $rows[] = ['id' => (int)$m['id'], 'n' => $m['n'], 'url' => $m['url'], 'cat' => (string)$code, 'catN' => $t['name'], 'sub' => (string)$sub];
            }
        }
    }
    $st = bt_pe_state(array_column($rows, 'id'));
    foreach ($rows as &$r) {
        $r += $st[$r['id']];
    }
    unset($r);
    return $rows;
}

// цены товаров: розница (от 1 шт/кг), оптовые ступени [kg, p] (от 2 и выше), старая цена; kg — товар продаётся на вес, ступени только у таких
function bt_pe_state(array $ids): array
{
    $st = [];
    foreach ($ids as $id) {
        $st[$id] = ['p' => 0.0, 'old' => 0.0, 'tiers' => [], 'kg' => false, 'unit' => 'шт'];
    }
    if (!$ids) {
        return $st;
    }
    $r = \Bitrix\Catalog\PriceTable::getList(['filter' => ['@PRODUCT_ID' => $ids, '=CATALOG_GROUP.BASE' => 'Y'],
        'select' => ['PRODUCT_ID', 'PRICE', 'QUANTITY_FROM'], 'order' => ['QUANTITY_FROM' => 'ASC']]);
    while ($p = $r->fetch()) {
        $from = (int)$p['QUANTITY_FROM'];
        if ($from <= 1) {
            $st[$p['PRODUCT_ID']]['p'] = (float)$p['PRICE'];
        } else {
            $st[$p['PRODUCT_ID']]['tiers'][] = [$from, (float)$p['PRICE']];
        }
    }
    $units = [];
    $mr = CCatalogMeasure::getList();
    while ($x = $mr->Fetch()) {
        $units[(int)$x['ID']] = (string)$x['SYMBOL_RUS'];
    }
    foreach (\Bitrix\Catalog\ProductTable::getList(['filter' => ['@ID' => $ids], 'select' => ['ID', 'MEASURE']])->fetchAll() as $p) {
        $u = $units[(int)$p['MEASURE']] ?? '';
        $st[$p['ID']]['unit'] = $u ?: 'шт';
        $st[$p['ID']]['kg'] = $u === 'кг';
    }
    $r = CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock('catalog'), 'ID' => $ids], false, false, ['ID', 'IBLOCK_ID', 'PROPERTY_OLD_PRICE']);
    while ($e = $r->Fetch()) {
        $st[$e['ID']]['old'] = (float)$e['PROPERTY_OLD_PRICE_VALUE'];
    }
    return $st;
}

function bt_pe_log(): array
{
    return (array)json_decode(\Bitrix\Main\Config\Option::get('bt', 'price_log', '[]'), true);
}

// сохранить цены: items — [{id, p, old, tiers: [[kg, p], …]}] полным состоянием товара; ступени пишутся диапазонами количества одной цены BASE
function bt_pe_save(array $items, bool $notify): array
{
    global $USER;
    $catId = bt_iblock('catalog');
    $base = (int)(\Bitrix\Catalog\GroupTable::getList(['filter' => ['=BASE' => 'Y'], 'select' => ['ID']])->fetch()['ID'] ?? 0);
    $names = array_column(bt_pe_rows(), 'n', 'id');
    $ids = array_values(array_intersect(array_map(fn($i) => (int)($i['id'] ?? 0), $items), array_keys($names)));
    $before = bt_pe_state($ids);
    $errors = $log = [];
    $saved = 0;
    $conn = \Bitrix\Main\Application::getConnection();
    $who = trim((string)$USER->GetFullName()) ?: (string)$USER->GetLogin();
    foreach (array_slice($items, 0, 500) as $it) {
        $id = (int)($it['id'] ?? 0);
        if (!isset($before[$id])) {
            continue;
        }
        $p = round((float)($it['p'] ?? 0), 2);
        $old = round((float)($it['old'] ?? 0), 2);
        $tiers = [];
        $err = '';
        foreach ((array)($it['tiers'] ?? []) as $t) {
            $k = (int)($t[0] ?? 0);
            $tp = round((float)($t[1] ?? 0), 2);
            if ($k < 2 || $tp <= 0) {
                $err = 'Ступень: объём от 2 и цена больше нуля';
            } elseif (isset($tiers[$k])) {
                $err = "Две ступени «от $k»";
            }
            $tiers[$k] = $tp;
        }
        ksort($tiers);
        if ($p < 0 || $old < 0 || max([$p, $old, ...array_values($tiers)]) > 10000000) {
            $err = 'Проверьте цену';
        } elseif ($tiers && !$before[$id]['kg']) {
            $err = 'Ступени — только для товаров на вес (единица «кг»)';
        } elseif ($tiers && $p <= 0) {
            $err = 'Сначала укажите розничную цену';
        }
        if ($err) {
            $errors[$id] = $err;
            continue;
        }
        $after = ['p' => $p, 'old' => $old, 'tiers' => array_map(fn($k, $v) => [$k, $v], array_keys($tiers), $tiers)];
        $b = $before[$id];
        $pricesSame = $b['p'] == $p && json_encode($b['tiers']) === json_encode($after['tiers']);
        if ($pricesSame && $b['old'] == $old) {
            continue;
        }
        $conn->startTransaction();
        try {
            if (!$pricesSame) {
                $r = \Bitrix\Catalog\PriceTable::getList(['filter' => ['=PRODUCT_ID' => $id, '=CATALOG_GROUP_ID' => $base], 'select' => ['ID']]);
                while ($x = $r->fetch()) {
                    $res = \Bitrix\Catalog\Model\Price::delete($x['ID']);
                    $res->isSuccess() or throw new RuntimeException(implode('; ', $res->getErrorMessages()));
                }
                // без ступеней — одна цена без диапазона; со ступенями — 1…(k1−1) розница, k1…(k2−1) первая ступень, …, последняя без верхней границы
                $from = $tiers ? array_merge([1], array_keys($tiers)) : [null];
                $price = array_merge([$p], array_values($tiers));
                foreach ($p > 0 ? $from : [] as $i => $f) {
                    $res = \Bitrix\Catalog\Model\Price::add(['PRODUCT_ID' => $id, 'CATALOG_GROUP_ID' => $base, 'PRICE' => $price[$i], 'CURRENCY' => 'RUB',
                        'QUANTITY_FROM' => $f, 'QUANTITY_TO' => isset($from[$i + 1]) ? $from[$i + 1] - 1 : null]);
                    $res->isSuccess() or throw new RuntimeException(implode('; ', $res->getErrorMessages()));
                }
            }
            if ($b['old'] != $old) {
                CIBlockElement::SetPropertyValuesEx($id, $catId, ['OLD_PRICE' => $old > 0 ? $old : false]);
            }
            $conn->commitTransaction();
        } catch (Throwable $e) {
            $conn->rollbackTransaction();
            $errors[$id] = 'Не сохранилось: ' . $e->getMessage();
            continue;
        }
        $saved++;
        $log[] = ['t' => time(), 'u' => $who, 'id' => $id, 'n' => $names[$id], 'b' => ['p' => $b['p'], 'old' => $b['old'], 'tiers' => $b['tiers']], 'a' => $after];
    }
    $sent = 0;
    if ($saved) {
        CIBlock::clearIblockTagCache($catId);
        \Bitrix\Main\Config\Option::set('bt', 'price_log', json_encode(array_slice(array_merge(array_reverse($log), bt_pe_log()), 0, 150), JSON_UNESCAPED_UNICODE));
        // письмо подписчикам сразу; без галочки — только отметка, чтобы агент не разослал его позже
        $notify ? $sent = bt_price_notify_send(time()) : \Bitrix\Main\Config\Option::set('bt', 'price_notified', (string)time());
    }
    return ['ok' => !$errors, 'saved' => $saved, 'sent' => $sent, 'errors' => (object)$errors, 'rows' => bt_pe_rows(), 'log' => bt_pe_log()];
}

// разбор файла: тот же формат, что «Скачать Excel» (колонки «Товар», «Цена, ₽», «от N кг», «Ссылка»; «Старая цена» — по желанию), .xlsx или .csv
function bt_pe_import($file): array
{
    if (!is_array($file) || ($file['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Файл не загрузился'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Файл больше 5 МБ'];
    }
    $ext = mb_strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $table = match ($ext) {
        'xlsx' => bt_pe_xlsx($file['tmp_name']),
        'csv', 'txt' => bt_pe_csv($file['tmp_name']),
        default => null,
    };
    if ($table === null) {
        return ['ok' => false, 'error' => $ext === 'xls' ? 'Старый формат .xls — сохраните файл как .xlsx или .csv' : 'Нужен файл .xlsx или .csv'];
    }
    $col = null;
    foreach ($table as $hi => $row) {
        foreach ($row as $ci => $h) {
            if (preg_match('/^\s*товар/ui', (string)$h)) {
                $col = ['head' => $hi, 'name' => $ci, 'tiers' => []];
                break 2;
            }
        }
    }
    if (!$col) {
        return ['ok' => false, 'error' => 'Не нашли колонку «Товар». Скачайте прайс кнопкой «Скачать Excel» и правьте его'];
    }
    foreach ($table[$col['head']] as $ci => $h) {
        $h = mb_strtolower(trim((string)$h));
        if (preg_match('/от\s*(\d+)\s*кг/u', $h, $m)) {
            $col['tiers'][$ci] = (int)$m[1];
        } elseif (str_contains($h, 'старая')) {
            $col['old'] = $ci;
        } elseif (str_starts_with($h, 'цена')) {
            $col['p'] = $ci;
        } elseif (str_contains($h, 'ссылка')) {
            $col['url'] = $ci;
        }
    }
    if (!isset($col['p']) && !$col['tiers']) {
        return ['ok' => false, 'error' => 'Не нашли колонку «Цена»'];
    }
    $norm = fn($s) => preg_replace('/\s+/u', ' ', str_replace('ё', 'е', mb_strtolower(trim((string)$s))));
    $code = fn($u) => preg_match('#/catalog/product/([^/?\#]+)#', (string)$u, $m) ? $m[1] : '';
    $byCode = $byName = [];
    $rows = bt_pe_rows();
    foreach ($rows as $r) {
        $byCode[$code($r['url'])] = $r['id'];
        $k = $norm($r['n']);
        $byName[$k] = isset($byName[$k]) ? 0 : $r['id'];
    }
    $kg = array_column($rows, 'kg', 'id');
    $num = function ($v) {
        $v = trim((string)$v);
        if ($v === '') {
            return null;
        }
        if (preg_match('/запрос/ui', $v)) {
            return 0.0;
        }
        $v = preg_replace('/[^\d.\-]/u', '', str_replace(',', '.', $v));
        return is_numeric($v) ? (float)$v : null;
    };
    $items = $unknown = [];
    $notKg = 0;
    foreach (array_slice($table, $col['head'] + 1) as $row) {
        $name = trim((string)($row[$col['name']] ?? ''));
        if ($name === '') {
            continue;
        }
        $id = ($byCode[$code($row[$col['url'] ?? -1] ?? '')] ?? 0) ?: ($byName[$norm($name)] ?? 0);
        if (!$id) {
            $unknown[] = $name;
            continue;
        }
        $it = ['id' => $id];
        isset($col['p']) && ($v = $num($row[$col['p']] ?? '')) !== null and $it['p'] = $v;
        isset($col['old']) and $it['old'] = (float)$num($row[$col['old']] ?? '');
        if ($col['tiers']) {
            // в выгрузке пустая ступень повторяет предыдущую цену — такие повторы не ступени
            $prev = $it['p'] ?? null;
            $tiers = [];
            foreach ($col['tiers'] as $ci => $k) {
                $v = $num($row[$ci] ?? '');
                if ($v && $v != $prev && $k > 1) {
                    $tiers[] = [$k, $v];
                    $prev = $v;
                }
            }
            if ($tiers && !$kg[$id]) {
                $notKg++;
            } else {
                $it['tiers'] = $tiers;
            }
        }
        $items[] = $it;
    }
    return ['ok' => true, 'items' => $items, 'unknown' => array_slice($unknown, 0, 30), 'unknownCnt' => count($unknown), 'notKg' => $notKg];
}

function bt_pe_xlsx(string $path): array
{
    $z = new ZipArchive();
    if ($z->open($path) !== true) {
        return [];
    }
    $ss = [];
    if (($x = $z->getFromName('xl/sharedStrings.xml')) !== false && ($xml = simplexml_load_string($x))) {
        foreach ($xml->si as $si) {
            $t = isset($si->t) ? (string)$si->t : '';
            foreach ($si->r as $run) {
                $t .= (string)$run->t;
            }
            $ss[] = $t;
        }
    }
    $sheet = $z->getFromName('xl/worksheets/sheet1.xml');
    for ($i = 0; $sheet === false && $i < $z->numFiles; $i++) {
        preg_match('#^xl/worksheets/[^/]+\.xml$#', (string)$z->getNameIndex($i)) and $sheet = $z->getFromIndex($i);
    }
    $z->close();
    $xml = $sheet ? simplexml_load_string($sheet) : null;
    if (!$xml) {
        return [];
    }
    $rows = [];
    foreach ($xml->sheetData->row as $row) {
        $line = [];
        foreach ($row->c as $c) {
            preg_match('/^[A-Z]+/', (string)$c['r'], $m);
            $ci = 0;
            foreach (str_split($m[0] ?? 'A') as $ch) {
                $ci = $ci * 26 + ord($ch) - 64;
            }
            $t = (string)$c['t'];
            $line[$ci - 1] = $t === 's' ? ($ss[(int)$c->v] ?? '') : ($t === 'inlineStr' ? (string)$c->is->t : (string)$c->v);
        }
        $line and $rows[] = array_replace(array_fill(0, max(array_keys($line)) + 1, ''), $line);
    }
    return $rows;
}

function bt_pe_csv(string $path): array
{
    $s = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents($path));
    mb_check_encoding($s, 'UTF-8') or $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1251');
    $first = strtok($s, "\n");
    $d = ';';
    $best = 0;
    foreach ([';', ',', "\t"] as $c) {
        if (substr_count((string)$first, $c) > $best) {
            $best = substr_count((string)$first, $c);
            $d = $c;
        }
    }
    $f = fopen('php://temp', 'r+');
    fwrite($f, $s);
    rewind($f);
    $rows = [];
    while (($r = fgetcsv($f, 0, $d, '"', '')) !== false) {
        $rows[] = $r;
    }
    fclose($f);
    return $rows;
}
