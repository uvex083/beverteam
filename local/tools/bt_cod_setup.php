<?php
// Оплата при получении — только у своих служб (курьер BEVERTEAM, самовывоз): у СДЭК наложенного платежа нет, там оплата сразу или по счёту.
// Модулю СДЭК — список безналичных оплат (все, кроме «При получении»), иначе оплаченный заказ уйдёт в СДЭК наложенным платежом.
// Новую онлайн-оплату добавили — запустить снова.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_cod_setup.php [show|apply]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Sale\Services\PaySystem\Restrictions\Delivery as ByDelivery;

Loader::includeModule('sale');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");

$cash = (int)(Sale\PaySystem\Manager::getList(['filter' => ['=ACTION_FILE' => 'cash', '=ACTIVE' => 'Y'], 'select' => ['ID']])->fetch()['ID'] ?? 0);
$own = array_map('intval', array_column(Sale\Delivery\Services\Table::getList(['filter' => ['@XML_ID' => ['bt_courier', 'bt_pickup'], '=ACTIVE' => 'Y'], 'select' => ['ID']])->fetchAll(), 'ID'));
if (!$cash || !$own) {
    die("нет оплаты «При получении» или своих служб доставки\n");
}

$have = Sale\Internals\ServiceRestrictionTable::getList(['filter' => ['=SERVICE_ID' => $cash, '=SERVICE_TYPE' => Sale\Services\Base\RestrictionManager::SERVICE_TYPE_PAYMENT, '=CLASS_NAME' => '\\' . ByDelivery::class], 'select' => ['ID']])->fetch();
$links = array_map('intval', Sale\Internals\DeliveryPaySystemTable::getLinks($cash, Sale\Internals\DeliveryPaySystemTable::ENTITY_TYPE_PAYSYSTEM));
sort($links);
sort($own);
if (!$have || $links !== $own) {
    $say("«При получении» (ПС $cash) — только доставки " . implode(', ', $own) . ' (было: ' . ($links ? implode(', ', $links) : 'все') . ')');
    if ($apply) {
        $r = ByDelivery::save(['SERVICE_ID' => $cash, 'SERVICE_TYPE' => Sale\Services\Base\RestrictionManager::SERVICE_TYPE_PAYMENT, 'SORT' => 100, 'PARAMS' => ['DELIVERY' => $own]], (int)($have['ID'] ?? 0));
        $r->isSuccess() or die(implode('; ', $r->getErrorMessages()) . "\n");
    }
}

$noCod = array_map('strval', array_column(Sale\PaySystem\Manager::getList(['filter' => ['=ACTIVE' => 'Y', '!@ACTION_FILE' => ['cash', 'inner']], 'select' => ['ID'], 'order' => ['ID' => 'ASC']])->fetchAll(), 'ID'));
$cur = unserialize(\COption::GetOptionString('ipol.sdek', 'paysystems', 'a:0:{}'), ['allowed_classes' => false]) ?: [];
if ($cur !== $noCod) {
    $say('СДЭК, безналичные оплаты: ' . implode(', ', $noCod) . ' (было: ' . implode(', ', $cur) . ')');
    $apply and \COption::SetOptionString('ipol.sdek', 'paysystems', serialize($noCod));
}
// у самовывоза на оформлении описания нет: адрес и часы — в плашке под выбором доставки
$pickup = Sale\Delivery\Services\Table::getList(['filter' => ['=XML_ID' => 'bt_pickup'], 'select' => ['ID', 'DESCRIPTION']])->fetch();
$desc = '';
if ($pickup && $pickup['DESCRIPTION'] !== $desc) {
    $say("самовывоз: «{$desc}»");
    $apply and Sale\Delivery\Services\Manager::update((int)$pickup['ID'], ['DESCRIPTION' => $desc]);
}
echo "готово\n";
