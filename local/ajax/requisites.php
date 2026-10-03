<?php
// Карточка предприятия для кнопки «Скачать реквизиты» в «Контактах»: загруженный клиентом файл или PDF из полей ИБ «Контакты и реквизиты»
define('NO_KEEP_STATISTIC', true);
define('NOT_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$co = bt_contacts();
if (!empty($co['req_file']) && ($path = CFile::GetPath((int)$co['req_file']))) {
    LocalRedirect($path);
}
if (!\Bitrix\Main\Loader::includeModule('sale') || !CSalePdf::isPdfAvailable()) {
    CHTTP::SetStatus('404 Not Found');
    die();
}

$rows = bt_requisites();
$pdf = new CSalePdf('P', 'pt', 'A4');
$pdf->AddFont('Font', '', 'pt_sans-regular.ttf', true);
$pdf->AddFont('Font', 'B', 'pt_sans-bold.ttf', true);
$pdf->SetAutoPageBreak(true, 50);
$pdf->SetMargins(50, 50, 50);
$pdf->AddPage();
$t = fn($s) => CSalePdf::prepareToPdf((string)$s);
$w = $pdf->GetPageWidth() - 100;

// шапка: фирменная полоса и логотип (JPG — PDF-библиотека не понимает прозрачность PNG)
$pdf->SetFillColor(14, 14, 12);
$pdf->Rect(0, 0, $pdf->GetPageWidth(), 110, 'F');
$pdf->Image($_SERVER['DOCUMENT_ROOT'] . '/local/templates/beverteam/brand/logo-pdf.jpg', 50, 30, 162, 34);
$pdf->SetFont('Font', '', 10);
$pdf->SetTextColor(201, 201, 194);
$pdf->SetXY(50, 72);
$pdf->Cell(300, 14, $t('Чай, кофе и оборудование для дома и бизнеса'));

$pdf->SetXY(50, 140);
$pdf->SetTextColor(14, 14, 12);
$pdf->SetFont('Font', 'B', 18);
$pdf->Cell($w, 24, $t('Карточка предприятия'), 0, 1);
$pdf->SetFont('Font', '', 10);
$pdf->SetTextColor(108, 108, 100);
$pdf->Cell($w, 16, $t('Реквизиты для договоров и счетов · актуально на ' . date('d.m.Y')), 0, 1);
$pdf->Ln(16);

// строки реквизитов: подпись слева, значение справа, тонкий разделитель
$lw = 170;
$pdf->SetDrawColor(220, 220, 212);
foreach ($rows as [$label, $value]) {
    $y = $pdf->GetY();
    $pdf->SetFont('Font', '', 10);
    $pdf->SetTextColor(108, 108, 100);
    $pdf->SetXY(50, $y + 9);
    $pdf->MultiCell($lw - 10, 14, $t($label), 0, 'L');
    $h1 = $pdf->GetY();
    $pdf->SetFont('Font', 'B', 11);
    $pdf->SetTextColor(14, 14, 12);
    $pdf->SetXY(50 + $lw, $y + 8);
    $pdf->MultiCell($w - $lw, 15, $t($value), 0, 'L');
    $bottom = max($h1, $pdf->GetY()) + 9;
    $pdf->Line(50, $bottom, 50 + $w, $bottom);
    $pdf->SetY($bottom);
}

$pdf->Ln(24);
$pdf->SetFont('Font', '', 9);
$pdf->SetTextColor(155, 155, 146);
$pdf->MultiCell($w, 13, $t('BEVERTEAM · ' . ($co['city'] ?? '') . ', ' . ($co['street'] ?? '') . ' · ' . ($co['phone1'] ?? '') . ' · ' . ($co['email'] ?? '') . ' · beverteam.ru'), 0, 'L');

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="BEVERTEAM-rekvizity.pdf"');
echo $pdf->Output('', 'S');
