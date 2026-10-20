<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
/** @global CUser $USER */

use Bt\Attr;

// отчёт для администраторов: остальным 404
if (!$USER->IsAdmin()) {
    require $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    die();
}

// пометка «бренд / не бренд», сумма сделки по заявке, процент и база — формой, после сохранения назад к тому же отчёту
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    $id = (int)($_POST['id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'brand':
            $v = in_array($_POST['v'] ?? '', ['Y', 'N'], true) ? $_POST['v'] : null;
            $id && Attr\Table::update($id, ['BRAND_MANUAL' => $v]);
            break;
        case 'amount':
            // «1 500», «1.500,50», «1500.5»; пусто, ноль и не число — суммы нет
            $a = str_replace([' ', "\u{A0}"], '', (string)($_POST['amount'] ?? ''));
            $a = str_contains($a, ',') ? str_replace(['.', ','], ['', '.'], $a) : $a;
            $id && Attr\Table::update($id, ['AMOUNT' => is_numeric($a) && (float)$a > 0 ? round((float)$a, 2) : null]);
            break;
        case 'settings':
            $pct = (float)str_replace(',', '.', (string)($_POST['pct'] ?? ''));
            $pct > 0 && $pct <= 100 && \Bitrix\Main\Config\Option::set('bt', 'seo_pct', (string)$pct);
            \Bitrix\Main\Config\Option::set('bt', 'seo_with_delivery', ($_POST['with_delivery'] ?? '') === 'Y' ? 'Y' : 'N');
            break;
    }
    LocalRedirect($APPLICATION->GetCurPageParam('', ['sessid']) . (isset($_POST['anchor']) ? '#' . preg_replace('/[^\w-]/', '', $_POST['anchor']) : ''));
}

$f = Attr\filter($_GET);
$tab = in_array($_GET['tab'] ?? '', ['lead', 'visit'], true) ? $_GET['tab'] : 'order';

// ?format=csv — текущая вкладка файлом для Excel: «;» и BOM
if (($_GET['format'] ?? '') === 'csv') {
    $rows = Attr\report($tab, $f);
    $APPLICATION->RestartBuffer();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="seo-' . $tab . '-' . $f['from'] . '-' . $f['to'] . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $num = fn($v) => str_replace('.', ',', (string)round((float)$v, 2));
    // текст, начинающийся с = + - @, Excel принял бы за формулу (телефон «+7…» тоже)
    $txt = fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : (string)$v;
    $head = ['Дата', 'Источник', 'Поисковик', 'Страница входа', 'Вход', 'Запрос'];
    $head = array_merge($head, $tab === 'order' ? ['Заказ', 'Покупатель', 'Сумма заказа', 'Доставка', 'Статус', 'База', 'Вознаграждение', 'Не засчитан']
        : ($tab === 'lead' ? ['Заявка', 'Имя', 'Телефон', 'Сумма сделки', 'Вознаграждение', 'Не засчитана'] : []));
    fputcsv($out, $head, ';');
    foreach ($rows as $r) {
        $line = [(string)$r['CREATED'], Attr\SOURCES[$r['SOURCE']] ?? $r['SOURCE'], $txt(Attr\ENGINES[$r['ENGINE']] ?? $r['ENGINE']), $txt($r['LANDING']),
            (string)$r['ENTRY_AT'], $r['BRAND'] === 'Y' ? 'брендовый' : 'не брендовый'];
        if ($tab === 'order') {
            $o = $r['ORDER'];
            $line = array_merge($line, [$o['num'] ?? $r['ENTITY_ID'], $txt($o['buyer'] ?? ''), $num($o['price'] ?? 0), $num($o['delivery'] ?? 0), $o['status'] ?? '',
                $num($r['BASE']), $num($r['FEE']), $r['WHY']]);
        } elseif ($tab === 'lead') {
            $l = $r['LEAD'];
            $line = array_merge($line, [$txt($l['topic'] ?? $r['ENTITY_ID']), $txt($l['name'] ?? ''), $txt($l['phone'] ?? ''), $r['AMOUNT'] === null ? '' : $num($r['AMOUNT']), $num($r['FEE']), $r['WHY']]);
        }
        fputcsv($out, $line, ';');
    }
    die();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetPageProperty('title', 'Отчёт: заказы из поиска');
$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
$APPLICATION->SetTitle('Заказы из поиска');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/seo-otchet.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
