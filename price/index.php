<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */

// ?format=csv — тот же прайс файлом для Excel: «;» и BOM, иначе русский Excel откроет кракозябры
if (($_GET['format'] ?? '') === 'csv') {
    $list = bt_price_list();
    $kgs = [];
    foreach ($list as $t) {
        foreach ($t['subs'] as $items) {
            foreach ($items as $m) {
                foreach ((array)($m['bulk'] ?? []) as $b) {
                    $kgs[$b['kg']] = true;
                }
            }
        }
    }
    ksort($kgs);
    $kgs = array_slice(array_keys($kgs), 1);
    $host = 'https://' . preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'beverteam.ru');
    $cell = fn($s) => '"' . str_replace('"', '""', (string)$s) . '"';
    $rows = [array_merge(['Категория', 'Раздел', 'Товар', 'Описание', 'Цена, ₽'], array_map(fn($k) => "от $k кг, ₽ за кг", $kgs), ['Наличие', 'Ссылка'])];
    foreach ($list as $t) {
        foreach ($t['subs'] as $sub => $items) {
            foreach ($items as $m) {
                $tiers = [];
                foreach ($kgs as $k) {
                    $p = '';
                    foreach ((array)($m['bulk'] ?? []) as $b) {
                        $b['kg'] <= $k and $p = $b['p'];
                    }
                    $tiers[] = $p;
                }
                $rows[] = array_merge([$t['name'], $sub, $m['n'], $m['par'] ?? '', $m['p'] ?: 'по запросу'], $tiers,
                    [!empty($m['pre']) ? 'предзаказ' : (!empty($m['stock']) ? 'в наличии' : 'под заказ'), $host . $m['url']]);
            }
        }
    }
    $APPLICATION->RestartBuffer();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="beverteam-price-' . date('Y-m-d', bt_price_date()) . '.csv"');
    echo "\xEF\xBB\xBF" . implode("\r\n", array_map(fn($r) => implode(';', array_map($cell, $r)), $rows)) . "\r\n";
    die();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetPageProperty('title', 'Прайс-лист на кофе, чай и кофемашины — оптовые цены | BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Актуальный прайс-лист BEVERTEAM: кофе BOTANICA, чай, кофемашины Jetinno и аксессуары. Оптовые цены от объёма, выгрузка в Excel, заказ прямо из прайса. Екатеринбург.');
$APPLICATION->SetPageProperty('keywords', 'прайс-лист кофе, кофе оптом Екатеринбург, чай оптом, цены на кофе, прайс кофемашины');
$APPLICATION->SetTitle('Прайс-лист');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/price.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
