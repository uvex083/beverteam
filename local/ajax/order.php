<?php
// Оформление заказа: POST action=city (поиск местоположения) | calc (доставки, оплаты, итог без сохранения) | create
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Sale;

header('Content-Type: application/json; charset=utf-8');
$req = Context::getCurrent()->getRequest();
if (!$req->isPost() || !check_bitrix_sessid()) {
    http_response_code(403);
    die(json_encode(['ok' => false, 'error' => 'sessid']));
}
Loader::includeModule('sale');
Loader::includeModule('catalog');

$out = fn(array $a) => die(json_encode($a, JSON_UNESCAPED_UNICODE));
$in = fn(string $k) => trim((string)$req->getPost($k));
$action = $in('action');

if ($action === 'city') {
    $q = str_replace(['ё', 'Ё'], ['е', 'Е'], $in('q'));
    $list = [];
    if (mb_strlen($q) >= 2) {
        $r = Sale\Location\LocationTable::getList([
            'filter' => ['=NAME.LANGUAGE_ID' => 'ru', '=%NAME.NAME' => $q . '%', '@TYPE.CODE' => ['CITY', 'VILLAGE'], '=PARENT.NAME.LANGUAGE_ID' => 'ru'],
            'select' => ['CODE', 'N' => 'NAME.NAME', 'R' => 'PARENT.NAME.NAME', 'RT' => 'PARENT.TYPE.CODE'],
            'order' => ['TYPE.SORT' => 'ASC', 'NAME.NAME' => 'ASC'], 'limit' => 10,
        ]);
        while ($l = $r->fetch()) {
            $list[] = ['code' => $l['CODE'], 'n' => $l['N'], 'r' => in_array($l['RT'], ['REGION', 'SUBREGION']) ? $l['R'] : ''];
        }
    }
    $out(['ok' => true, 'list' => $list]);
}

$ptypes = [];
foreach (Sale\Internals\PersonTypeTable::getList(['filter' => ['=ACTIVE' => 'Y', '=LID' => SITE_ID], 'select' => ['ID', 'CODE']])->fetchAll() as $p) {
    $ptypes[$p['CODE']] = (int)$p['ID'];
}
$pt = $in('ptype') === 'UR' ? 'UR' : 'FIZ';
$loc = $in('loc');
$locOk = $loc !== '' && Sale\Location\LocationTable::getList(['filter' => ['=CODE' => $loc], 'select' => ['ID']])->fetch();

// Заказ в памяти: плательщик, корзина, местоположение, отгрузка и оплата (если выбраны)
function bt_order_build(int $userId, int $ptId, string $loc, int $deliveryId, int $payId): Sale\Order
{
    $order = Sale\Order::create(SITE_ID, $userId ?: null);
    $order->setPersonTypeId($ptId);
    $order->setField('CURRENCY', \Bitrix\Currency\CurrencyManager::getBaseCurrency());
    $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID)->getOrderableItems();
    $order->setBasket($basket);
    if ($loc !== '' && ($p = $order->getPropertyCollection()->getDeliveryLocation())) {
        $p->setValue($loc);
    }
    $shipment = $order->getShipmentCollection()->createItem($deliveryId ? Sale\Delivery\Services\Manager::getObjectById($deliveryId) : null);
    $items = $shipment->getShipmentItemCollection();
    foreach ($basket as $bi) {
        $items->createItem($bi)->setQuantity($bi->getQuantity());
    }
    $payment = $order->getPaymentCollection()->createItem($payId ? Sale\PaySystem\Manager::getObjectById($payId) : null);
    $order->doFinalAction(true);
    $payment->setField('SUM', $order->getPrice());
    return $order;
}

$userId = $USER->IsAuthorized() ? (int)$USER->GetID() : 0;
$delivery = (int)$req->getPost('delivery');
$pay = (int)$req->getPost('pay');

// доступные доставки и оплаты — по ограничениям Битрикса (город, тип плательщика)
$deliveries = [];
$pays = [];
if ($locOk && !Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID)->getOrderableItems()->isEmpty()) {
    $probe = bt_order_build($userId, $ptypes[$pt], $loc, 0, 0);
    $shipment = $probe->getShipmentCollection()->getNotSystemItems()->current();
    foreach (Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment) as $svc) {
        if ($svc instanceof Sale\Delivery\Services\EmptyDeliveryService) {
            continue;
        }
        $o = bt_order_build($userId, $ptypes[$pt], $loc, $svc->getId(), 0);
        $f = Sale\Delivery\Services\Table::getById($svc->getId())->fetch();
        $deliveries[$svc->getId()] = ['id' => $svc->getId(), 'name' => $svc->getName(), 'desc' => (string)$f['DESCRIPTION'], 'code' => (string)$f['XML_ID'],
            'price' => (float)$o->getDeliveryPrice(), 'base' => (float)$o->getShipmentCollection()->getNotSystemItems()->current()->getField('BASE_PRICE_DELIVERY')];
    }
    if (!isset($deliveries[$delivery])) {
        $delivery = (int)array_key_first($deliveries);
    }
    $probe = bt_order_build($userId, $ptypes[$pt], $loc, $delivery, 0);
    foreach (Sale\PaySystem\Manager::getListWithRestrictions($probe->getPaymentCollection()->current()) as $ps) {
        if ($ps['ACTIVE'] === 'Y' && $ps['ACTION_FILE'] !== 'inner') {
            $pays[(int)$ps['ID']] = ['id' => (int)$ps['ID'], 'name' => $ps['NAME'], 'desc' => (string)$ps['DESCRIPTION'], 'code' => $ps['ACTION_FILE']];
        }
    }
    if (!isset($pays[$pay])) {
        $pay = (int)array_key_first($pays);
    }
}

if ($action === 'calc') {
    $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID)->getOrderableItems();
    $sum = (float)$basket->getPrice();
    $dPrice = $deliveries[$delivery]['price'] ?? 0;
    $out(['ok' => true, 'loc' => $locOk ? $loc : '', 'deliveries' => array_values($deliveries), 'delivery' => $delivery,
        'pays' => array_values($pays), 'pay' => $pay, 'sum' => $sum, 'deliveryPrice' => $dPrice, 'total' => $sum + $dPrice]);
}

if ($action !== 'create') {
    $out(['ok' => false, 'error' => 'action']);
}

// ---------- проверка полей ----------
$f = [];
foreach (['name', 'phone', 'email', 'company', 'inn', 'kpp', 'company_adr', 'mode', 'street', 'flat', 'entrance', 'pvz', 'comment', 'city'] as $k) {
    $f[$k] = $in($k);
}
$err = [];
$phone = preg_replace('/\D/', '', $f['phone']);
if (mb_strlen($f['name']) < 2) {
    $err['name'] = $f['name'] === '' ? 'Это поле нужно заполнить' : 'Как к вам обращаться? Минимум 2 символа';
}
if (strlen($phone) !== 11) {
    $err['phone'] = $phone === '' ? 'Это поле нужно заполнить' : 'Введите номер полностью: +7 и 10 цифр';
}
if (!check_email($f['email'], true)) {
    $err['email'] = $f['email'] === '' ? 'Это поле нужно заполнить' : 'Проверьте адрес: нужен формат mail@company.ru';
}
if ($pt === 'UR') {
    if ($f['company'] === '') {
        $err['company'] = 'Это поле нужно заполнить';
    }
    $inn = preg_replace('/\D/', '', $f['inn']);
    if (!in_array(strlen($inn), [10, 12], true)) {
        $err['inn'] = $f['inn'] === '' ? 'Это поле нужно заполнить' : 'ИНН состоит из 10 цифр у компании и 12 у ИП';
    }
    $f['inn'] = $inn;
    if ($f['kpp'] !== '' && !preg_match('/^\d{9}$/', $f['kpp'])) {
        $err['kpp'] = 'КПП состоит из 9 цифр';
    }
}
if (!$locOk) {
    $err['city'] = 'Выберите город из списка';
} elseif (!isset($deliveries[(int)$req->getPost('delivery')])) {
    $err['delivery'] = 'Выберите способ доставки';
} elseif ($f['mode'] === 'addr' && $f['street'] === '') {
    $err['street'] = 'Это поле нужно заполнить';
} elseif ($f['mode'] === 'pvz' && $f['pvz'] === '') {
    $err['pvz'] = 'Это поле нужно заполнить';
}
if ($locOk && !isset($pays[(int)$req->getPost('pay')])) {
    $err['pay'] = 'Выберите способ оплаты';
}
if ($req->getPost('agree') !== 'Y') {
    $err['agree'] = 'Нужно согласие';
}
if (Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID)->getOrderableItems()->isEmpty()) {
    $err['basket'] = 'Корзина пуста';
}
if ($err) {
    $out(['ok' => false, 'errors' => $err]);
}

// ---------- покупатель: текущий, найденный по e-mail (без входа под ним) или новый ----------
if (!$userId) {
    $found = \Bitrix\Main\UserTable::getList(['filter' => ['=EMAIL' => $f['email']], 'select' => ['ID'], 'order' => ['ID' => 'ASC'], 'limit' => 1])->fetch();
    if ($found) {
        $userId = (int)$found['ID'];
    } else {
        $pass = \Bitrix\Main\Security\Random::getStringByAlphabet(12, \Bitrix\Main\Security\Random::ALPHABET_ALPHALOWER | \Bitrix\Main\Security\Random::ALPHABET_ALPHAUPPER | \Bitrix\Main\Security\Random::ALPHABET_NUM)
            . \Bitrix\Main\Security\Random::getStringByAlphabet(4, \Bitrix\Main\Security\Random::ALPHABET_SPECIAL);
        $login = $f['email'];
        while (\Bitrix\Main\UserTable::getList(['filter' => ['=LOGIN' => $login], 'select' => ['ID']])->fetch()) {
            $login = $f['email'] . '_' . random_int(100, 999);
        }
        $groups = array_filter(array_map('intval', explode(',', COption::GetOptionString('main', 'new_user_registration_def_group', ''))));
        $u = new CUser();
        $userId = (int)$u->Add(['LOGIN' => $login, 'EMAIL' => $f['email'], 'NAME' => $f['name'], 'PERSONAL_PHONE' => '+' . $phone,
            'PASSWORD' => $pass, 'CONFIRM_PASSWORD' => $pass, 'ACTIVE' => 'Y', 'LID' => SITE_ID, 'GROUP_ID' => $groups ?: [2]]);
        if (!$userId) {
            $out(['ok' => false, 'errors' => ['email' => 'Не получилось сохранить покупателя: ' . strip_tags($u->LAST_ERROR)]]);
        }
    }
}

// ---------- заказ ----------
$delivery = (int)$req->getPost('delivery');
$pay = (int)$req->getPost('pay');
$order = bt_order_build($userId, $ptypes[$pt], $loc, $delivery, $pay);
$order->setField('USER_DESCRIPTION', mb_substr($f['comment'], 0, 2000));
$addr = $f['mode'] === 'addr' ? implode(', ', array_filter([$f['street'], $f['flat'] !== '' ? 'кв./офис ' . $f['flat'] : '', $f['entrance']])) : '';
$values = ['EMAIL' => $f['email'], 'PHONE' => '+' . $phone, 'LOCATION' => $loc, 'ADDRESS' => $addr, 'PVZ' => $f['mode'] === 'pvz' ? $f['pvz'] : '']
    + ($pt === 'UR'
        ? ['CONTACT_PERSON' => $f['name'], 'COMPANY' => $f['company'], 'INN' => $f['inn'], 'KPP' => $f['kpp'], 'COMPANY_ADR' => $f['company_adr']]
        : ['FIO' => $f['name']]);
foreach ($order->getPropertyCollection() as $prop) {
    $code = $prop->getField('CODE');
    if (array_key_exists($code, $values)) {
        $prop->setValue($values[$code]);
    }
}
$order->doFinalAction(true);
$order->getPaymentCollection()->current()->setField('SUM', $order->getPrice());
$r = $order->save();
if (!$r->isSuccess()) {
    $out(['ok' => false, 'errors' => ['form' => 'Не получилось оформить заказ: ' . implode('; ', $r->getErrorMessages())]]);
}
$_SESSION['BT_ORDERS'][] = (int)$order->getId();
$out(['ok' => true, 'orderId' => (int)$order->getId(), 'accountNumber' => $order->getField('ACCOUNT_NUMBER'),
    'redirect' => '/personal/order/success/?id=' . (int)$order->getId()]);
