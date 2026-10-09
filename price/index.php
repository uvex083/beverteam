<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
/** @global CUser $USER */

// прайс пока в доработке — видит только администратор, остальным 404 (из меню и карты сайта убран)
if (!$USER->IsAdmin()) {
    require $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    die();
}

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
    $ed = !empty($_GET['ed']) && bt_price_editor();
    $host = 'https://' . preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'beverteam.ru');
    $cell = fn($s) => '"' . str_replace('"', '""', (string)$s) . '"';
    $rows = [array_merge(['Категория', 'Раздел', 'Товар', 'Описание', 'Цена, ₽'], $ed ? ['Старая цена, ₽'] : [], array_map(fn($k) => "от $k кг, ₽ за кг", $kgs), ['Наличие', 'Ссылка'])];
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
                $rows[] = array_merge([$t['name'], $sub, $m['n'], $m['par'] ?? '', $m['p'] ?: 'по запросу'], $ed ? [!empty($m['old']) ? $m['old'] : ''] : [], $tiers,
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

// ?format=pdf — прайс в PDF: открывается во вкладке браузера, оттуда его скачивают или печатают
if (($_GET['format'] ?? '') === 'pdf' && \Bitrix\Main\Loader::includeModule('sale') && CSalePdf::isPdfAvailable()) {
    $list = bt_price_list();
    $ts = bt_price_date();
    $co = bt_contacts();
    $pdf = new CSalePdf('P', 'pt', 'A4');
    $pdf->AddFont('Font', '', 'pt_sans-regular.ttf', true);
    $pdf->AddFont('Font', 'B', 'pt_sans-bold.ttf', true);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(30, 30, 30);
    $t = fn($s) => CSalePdf::prepareToPdf((string)$s);
    $rub = fn($p) => $t(number_format((float)$p, 0, '', ' ') . ' ₽');
    $W = $pdf->GetPageWidth() - 60;
    $H = $pdf->GetPageHeight() - 36;
    $page = function () use ($pdf, $t, $co, $ts, $W) {
        $pdf->AddPage();
        $pdf->SetFillColor(14, 14, 12);
        $pdf->Rect(0, 0, $pdf->GetPageWidth(), 64, 'F');
        $pdf->Image($_SERVER['DOCUMENT_ROOT'] . '/local/templates/beverteam/brand/logo-pdf.jpg', 30, 18, 134, 28);
        $pdf->SetFont('Font', '', 9);
        $pdf->SetTextColor(201, 201, 194);
        $pdf->SetXY(30, 20);
        $pdf->Cell($W, 12, $t(trim(($co['phone1'] ?? '') . '   ' . ($_SERVER['HTTP_HOST'] ?? 'beverteam.ru') . '/price/')), 0, 2, 'R');
        $pdf->Cell($W, 12, $t('Цены на ' . FormatDate('j F Y', $ts)), 0, 0, 'R');
        $pdf->SetY(82);
    };
    // перенос названия по ширине колонки
    $wrap = function (string $s, float $w) use ($pdf, $t) {
        $lines = [''];
        foreach (preg_split('/\s+/u', trim($s)) as $word) {
            $try = ltrim(end($lines) . ' ' . $word);
            if ($pdf->GetStringWidth($t($try)) > $w && end($lines) !== '') {
                $lines[] = $word;
            } else {
                $lines[count($lines) - 1] = $try;
            }
        }
        return $lines;
    };
    $page();
    $pdf->SetTextColor(14, 14, 12);
    $pdf->SetFont('Font', 'B', 20);
    $pdf->Cell($W, 26, $t('Прайс-лист'), 0, 1);
    if ($disc = bt_sum_discounts()) {
        $pdf->SetFont('Font', '', 10);
        $pdf->SetTextColor(47, 125, 74);
        $pdf->Cell($W, 16, $t('Скидка от суммы заказа: ' . implode(', ', array_map(fn($d) => $d['pct'] . '% от ' . number_format($d['from'], 0, '', ' ') . ' ₽', $disc))), 0, 1);
    }
    $pdf->Ln(6);
    foreach ($list as $cat) {
        $kgs = [];
        foreach ($cat['subs'] as $items) {
            foreach ($items as $m) {
                foreach ((array)($m['bulk'] ?? []) as $b) {
                    $kgs[$b['kg']] = true;
                }
            }
        }
        ksort($kgs);
        $kgs = array_keys($kgs) ?: [0];
        $pw = 62;
        $nw = $W - $pw * count($kgs);
        if ($pdf->GetY() > $H - 90) {
            $page();
        }
        $pdf->SetFont('Font', 'B', 13);
        $pdf->SetTextColor(14, 14, 12);
        $pdf->Cell($W, 22, $t(mb_strtoupper($cat['name'])), 0, 1);
        $pdf->SetFont('Font', 'B', 8);
        $pdf->SetTextColor(108, 108, 100);
        $pdf->Cell($nw, 14, $t('Товар'), 'B');
        foreach ($kgs as $i => $k) {
            $pdf->Cell($pw, 14, $t($k ? ($i ? 'от ' . $k . ' кг' : $k . ' кг') : 'Цена'), 'B', 0, 'R');
        }
        $pdf->Ln();
        foreach ($cat['subs'] as $sub => $items) {
            if ($sub !== '') {
                $pdf->GetY() > $H - 40 and $page();
                $pdf->SetFillColor(14, 14, 12);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('Font', 'B', 9);
                $pdf->Cell($W, 16, $t('  ' . $sub), 0, 1, 'L', true);
            }
            foreach ($items as $m) {
                $pdf->SetFont('Font', 'B', 9);
                $lines = $wrap($m['n'], $nw - 8);
                $par = trim((string)($m['par'] ?? ''));
                $h = count($lines) * 11 + ($par !== '' ? 10 : 0) + 8;
                $pdf->GetY() + $h > $H and $page();
                $y = $pdf->GetY();
                $pdf->SetTextColor(14, 14, 12);
                foreach ($lines as $j => $line) {
                    $pdf->SetXY(30, $y + 4 + $j * 11);
                    $pdf->Cell($nw, 11, $t($line));
                }
                if ($par !== '') {
                    $pdf->SetFont('Font', '', 7.5);
                    $pdf->SetTextColor(108, 108, 100);
                    $pdf->SetXY(30, $y + 4 + count($lines) * 11);
                    $pdf->Cell($nw, 9, $t(mb_strimwidth($par, 0, 110, '…')));
                }
                $pdf->SetFont('Font', 'B', 9);
                $pdf->SetTextColor(14, 14, 12);
                foreach ($kgs as $i => $k) {
                    $p = $m['p'];
                    if (!empty($m['bulk'])) {
                        foreach ($m['bulk'] as $b) {
                            $b['kg'] <= $k and $p = $b['p'];
                        }
                    }
                    $pdf->SetXY(30 + $nw + $i * $pw, $y + 4);
                    $pdf->Cell($pw, 11, !$m['p'] ? ($i ? '' : $t('по запросу')) : (empty($m['bulk']) && $i ? '' : $rub($p)), 0, 0, 'R');
                }
                $pdf->SetDrawColor(220, 220, 212);
                $pdf->Line(30, $y + $h, 30 + $W, $y + $h);
                $pdf->SetY($y + $h);
            }
        }
        $pdf->Ln(14);
    }
    $pdf->GetY() > $H - 30 and $page();
    $pdf->SetFont('Font', '', 8);
    $pdf->SetTextColor(108, 108, 100);
    $pdf->MultiCell($W, 11, $t('Цены в рублях. Окончательную стоимость, наличие и сроки подтвердит менеджер. Актуальный прайс: ' . ($_SERVER['HTTP_HOST'] ?? 'beverteam.ru') . '/price/'));
    $APPLICATION->RestartBuffer();
    $pdf->Output('beverteam-price-' . date('Y-m-d', $ts) . '.pdf', 'I');
    die();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetPageProperty('title', 'Прайс-лист на кофе, чай и кофемашины — оптовые цены | BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Актуальный прайс-лист BEVERTEAM: кофе BOTANICA, чай, кофемашины Jetinno и аксессуары. Оптовые цены от объёма, выгрузка в Excel, заказ прямо из прайса. Екатеринбург.');
$APPLICATION->SetPageProperty('keywords', 'прайс-лист кофе, кофе оптом Екатеринбург, чай оптом, цены на кофе, прайс кофемашины');
$APPLICATION->SetTitle('Прайс-лист');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/price.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
