<?php
// Счёт PDF — только владельцу заказа или в браузере, где заказ оформлен
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale;

$id = (int)($_GET['id'] ?? 0);
$order = $id && Loader::includeModule('sale') ? Sale\Order::load($id) : null;
$own = $order && ((in_array($id, (array)($_SESSION['BT_ORDERS'] ?? []), true))
    || ($USER->IsAuthorized() && (int)$order->getUserId() === (int)$USER->GetID()));
$fid = $own && bt_bill_ready($order) ? bt_bill_file($order) : 0;
$file = $fid ? CFile::GetFileArray($fid) : null;
if (!$file) {
    CHTTP::SetStatus('404 Not Found');
    die('Счёт не найден');
}
// CFile::ViewByUser открывает в браузере только картинки, PDF отдаём сами
$path = \Bitrix\Main\Application::getDocumentRoot() . $file['SRC'];
$num = (string)$order->getField('ACCOUNT_NUMBER');
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="schet-' . $num . '.pdf"; filename*=utf-8' . "''" . rawurlencode('Счёт № ' . $num . '.pdf'));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
die();
