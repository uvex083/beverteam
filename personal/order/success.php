<?php
// возврат из Т-Банка (адрес по умолчанию у модуля tinkoff.payment): ведём на нашу страницу заказа
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('sale');
$acc = preg_replace('~/[^/]*$~', '', (string)($_GET['OrderId'] ?? ''));
$order = $acc !== '' ? \Bitrix\Sale\Order::loadByAccountNumber($acc) : null;
LocalRedirect($order ? '/personal/order/success/?id=' . (int)$order->getId() : '/personal/');
