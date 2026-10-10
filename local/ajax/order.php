<?php
// Оформление заказа: POST action=city (поиск местоположения) | street (подсказки улицы и дома DaData) | calc (доставки, оплаты, итог без сохранения) | create
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

// Улица и дом через DaData, только в выбранном городе; ключ API — опция bt/dadata_key (bt_dadata_setup.php)
if ($action === 'street') {
    $q = $in('q');
    $city = $in('city');
    $key = \COption::GetOptionString('bt', 'dadata_key');
    if ($key === '' || mb_strlen($q) < 2) {
        $out(['ok' => true, 'list' => []]);
    }
    $http = new \Bitrix\Main\Web\HttpClient(['socketTimeout' => 3, 'streamTimeout' => 3]);
    $http->setHeader('Content-Type', 'application/json');
    $http->setHeader('Accept', 'application/json');
    $http->setHeader('Authorization', 'Token ' . $key);
    $full = $in('full') === '1';
    $body = $full ? ['query' => $q, 'count' => 7]
        : ['query' => $q, 'count' => 7, 'from_bound' => ['value' => 'street'], 'to_bound' => ['value' => 'house'], 'restrict_value' => true];
    if ($city !== '' && !$full) {
        $body['locations'] = [['city' => $city], ['settlement' => $city]];
    }
    $res = json_decode((string)$http->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address', json_encode($body, JSON_UNESCAPED_UNICODE)), true);
    $list = [];
    foreach ($res['suggestions'] ?? [] as $s) {
        $d = $s['data'] ?? [];
        $street = $full ? (string)($s['value'] ?? '')
            : trim(($d['street_with_type'] ?? '') . ($d['house'] ? ', ' . ($d['house_type'] ?? 'д') . ' ' . $d['house'] : '') . ($d['block'] ? ' ' . ($d['block_type'] ?? '') . ' ' . $d['block'] : ''));
        if ($street !== '') {
            $list[] = ['v' => $street, 's' => $d['street_with_type'] ?? '', 'house' => (bool)$d['house'], 'r' => $full ? '' : trim(($d['city_district_with_type'] ?? '') ?: ($d['area_with_type'] ?? ''))];
        }
    }
    $out(['ok' => true, 'list' => $list]);
}

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

// Фото пункта: модуль хранит список сериализованным, элементы — строка-ссылка или ['url' => …]
function bt_pvz_images(string $raw): array
{
    $list = $raw === '' ? [] : @unserialize($raw, ['allowed_classes' => false]);
    $urls = [];
    foreach (is_array($list) ? $list : [] as $i) {
        $u = is_array($i) ? (string)($i['url'] ?? $i['URL'] ?? '') : (string)$i;
        str_starts_with($u, 'https://') and $urls[] = $u;
    }
    return array_slice($urls, 0, 4);
}

// Пункты выдачи СДЭК города — из таблицы модуля ipol.sdek (синхронизирует агент модуля)
function bt_pvz_list(string $loc, string $code = ''): array
{
    $l = Sale\Location\LocationTable::getList(['filter' => ['=CODE' => $loc], 'select' => ['ID']])->fetch();
    if (!$l || !Loader::includeModule('ipol.sdek')) {
        return [];
    }
    $db = \Bitrix\Main\Application::getConnection();
    $c = $db->query('SELECT SDEK_ID FROM ipol_sdekcities WHERE BITRIX_ID = ' . (int)$l['ID'])->fetch();
    if (!$c) {
        return [];
    }
    $sql = "SELECT CODE, ADDRESS, WORK_TIME, NEAREST_METRO_STATION, ADDRESS_COMMENT, LAT, LON, WEIGHT_MAX, HAVE_CASH, HAVE_CASHLESS, OFFICE_IMAGE_LIST FROM ipol_sdek_points
        WHERE CITY_CODE = " . (int)$c['SDEK_ID'] . " AND TYPE = 'PVZ' AND IS_HANDOUT = 'Y' AND SYNC_IS_ACTIVE = 'Y'"
        . ($code !== '' ? " AND CODE = '" . $db->getSqlHelper()->forSql($code) . "'" : '') . ' ORDER BY ADDRESS';
    $list = [];
    foreach ($db->query($sql) as $p) {
        $list[] = ['c' => $p['CODE'], 'a' => $p['ADDRESS'], 'w' => (string)$p['WORK_TIME'], 'm' => (string)$p['NEAREST_METRO_STATION'],
            'n' => (string)$p['ADDRESS_COMMENT'], 'lat' => (float)$p['LAT'], 'lon' => (float)$p['LON'], 'kg' => (float)$p['WEIGHT_MAX'],
            'cash' => $p['HAVE_CASH'] === 'Y', 'card' => $p['HAVE_CASHLESS'] === 'Y', 'img' => bt_pvz_images((string)$p['OFFICE_IMAGE_LIST'])];
    }
    return $list;
}

if ($action === 'pvz') {
    $kg = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID)->getOrderableItems()->getWeight() / 1000;
    $out(['ok' => true, 'list' => array_values(array_filter(bt_pvz_list($in('loc')), fn($p) => !$p['kg'] || $p['kg'] >= $kg))]);
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

function bt_order_shipment(Sale\Order $order): Sale\Shipment
{
    foreach ($order->getShipmentCollection() as $s) {
        if (!$s->isSystem()) {
            return $s;
        }
    }
    throw new \RuntimeException('no shipment');
}

function bt_order_payment(Sale\Order $order): Sale\Payment
{
    foreach ($order->getPaymentCollection() as $p) {
        return $p;
    }
    throw new \RuntimeException('no payment');
}

$userId = $USER->IsAuthorized() ? (int)$USER->GetID() : 0;
$delivery = (int)$req->getPost('delivery');
$pay = (int)$req->getPost('pay');

// доступные доставки и оплаты — по ограничениям Битрикса (город, тип плательщика)
$deliveries = [];
$pays = [];
// модуль СДЭК после сбоя API сам не считает доставку несколько минут — тоже показываем покупателю
$dead = (int)\COption::GetOptionString('ipol.sdek', 'sdekDeadServer', '0');
$cdekDown = $dead && time() - $dead < 60 * (int)\COption::GetOptionString('ipol.sdek', 'timeoutRollback', '1');
if ($locOk && !Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID)->getOrderableItems()->isEmpty()) {
    $probe = bt_order_build($userId, $ptypes[$pt], $loc, 0, 0);
    $shipment = bt_order_shipment($probe);
    foreach (Sale\Delivery\Services\Manager::getRestrictedObjectsList($shipment) as $svc) {
        if ($svc instanceof Sale\Delivery\Services\EmptyDeliveryService) {
            continue;
        }
        $f = Sale\Delivery\Services\Table::getById($svc->getId())->fetch();
        // модуль СДЭК падает, если API не ответил: такую службу просто не показываем, остальные работают
        try {
            $o = bt_order_build($userId, $ptypes[$pt], $loc, $svc->getId(), 0);
            // срок от СДЭК («3-4 дня») — только у служб модуля, у своих срок считает чекаут
            $period = str_starts_with((string)$f['XML_ID'], 'sdek_') ? strip_tags((string)$svc->calculate(bt_order_shipment($o))->getPeriodDescription()) : '';
        } catch (\Throwable $e) {
            $cdekDown = $cdekDown || str_starts_with((string)$f['XML_ID'], 'sdek_');
            continue;
        }
        $deliveries[(int)$svc->getId()] = ['id' => (int)$svc->getId(), 'name' => $svc->getName(), 'desc' => (string)$f['DESCRIPTION'], 'code' => (string)$f['XML_ID'],
            'price' => (float)$o->getDeliveryPrice(), 'base' => (float)bt_order_shipment($o)->getField('BASE_PRICE_DELIVERY'), 'period' => $period];
    }
    if (!isset($deliveries[$delivery])) {
        $delivery = (int)array_key_first($deliveries);
    }
    try {
        $probe = bt_order_build($userId, $ptypes[$pt], $loc, $delivery, 0);
    } catch (\Throwable $e) {
        $probe = bt_order_build($userId, $ptypes[$pt], $loc, 0, 0);
    }
    foreach (Sale\PaySystem\Manager::getListWithRestrictions(bt_order_payment($probe)) as $ps) {
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
    // сумма товаров с правилами корзины (скидка от суммы заказа) — та же, что попадёт в заказ
    $base = (float)$basket->getPrice();
    $sum = $base;
    if (!$basket->isEmpty()) {
        try {
            $o = bt_order_build($userId, $ptypes[$pt], $locOk ? $loc : '', $locOk ? $delivery : 0, 0);
        } catch (\Throwable $e) {
            $o = bt_order_build($userId, $ptypes[$pt], $locOk ? $loc : '', 0, 0);
        }
        $sum = (float)$o->getPrice() - (float)$o->getDeliveryPrice();
    }
    $dPrice = $deliveries[$delivery]['price'] ?? 0;
    $out(['ok' => true, 'loc' => $locOk ? $loc : '', 'deliveries' => array_values($deliveries), 'delivery' => $delivery,
        'pays' => array_values($pays), 'pay' => $pay, 'base' => $base, 'disc' => round($base - $sum, 2), 'sum' => $sum, 'deliveryPrice' => $dPrice, 'total' => $sum + $dPrice,
        'cdekDown' => $cdekDown && !array_filter($deliveries, fn($d) => str_starts_with($d['code'], 'sdek_'))]);
}

if ($action !== 'create') {
    $out(['ok' => false, 'error' => 'action']);
}

// ---------- проверка полей ----------
$f = [];
foreach (['name', 'phone', 'email', 'company', 'inn', 'kpp', 'company_adr', 'mode', 'street', 'flat', 'entrance', 'pvz', 'comment', 'city'] as $k) {
    $f[$k] = $in($k);
}
// способ получения — по самой службе доставки, а не по тому, что прислал браузер
$dCode = $deliveries[(int)$req->getPost('delivery')]['code'] ?? '';
$f['mode'] = $dCode === 'bt_pickup' ? 'pickup' : ($dCode === 'sdek_pickup' ? 'pvz' : 'addr');
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
    } elseif (!bt_inn_ok($inn)) {
        $err['inn'] = 'Проверьте ИНН — в номере ошибка';
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
} elseif ($f['mode'] === 'addr' && !preg_match('/\p{L}{2,}.*\d/u', $f['street'])) {
    $err['street'] = 'Укажите номер дома';
} elseif ($f['mode'] === 'pvz' && !($pvz = bt_pvz_list($loc, $f['pvz'])[0] ?? null)) {
    $err['pvz'] = 'Выберите пункт выдачи';
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
        $userId = bt_user_create($f['email'], $f['name'], $phone);
        $fresh = true;
        if (!is_int($userId)) {
            $out(['ok' => false, 'errors' => ['email' => 'Не получилось сохранить покупателя: ' . $userId]]);
        }
    }
}

// ---------- заказ ----------
$delivery = (int)$req->getPost('delivery');
$pay = (int)$req->getPost('pay');
// модуль СДЭК бросает исключение, если API не ответил
try {
    $order = bt_order_build($userId, $ptypes[$pt], $loc, $delivery, $pay);
} catch (\Throwable $e) {
    $out(['ok' => false, 'errors' => ['form' => 'Служба доставки сейчас не отвечает. Попробуйте через минуту или выберите другой способ доставки.']]);
}
$order->setField('USER_DESCRIPTION', mb_substr($f['comment'], 0, 2000));
// для менеджера: заказ пришёл запросом счёта из прайс-листа и/или по персональной ссылке прайса (?m=…)
$note = [];
$in('src') === 'price' and $note[] = 'Запрос счёта из прайс-листа: доставку и сроки согласовать с клиентом.';
preg_match('/^[a-z0-9_-]{1,40}$/i', (string)($_COOKIE['bt_pm'] ?? ''), $pm) and $note[] = 'Персональная ссылка прайса: ' . $pm[0];
$note and $order->setField('COMMENTS', implode("\n", $note));
$addr = $f['mode'] === 'addr' ? implode(', ', array_filter([$f['street'], $f['flat'] !== '' ? 'кв./офис ' . $f['flat'] : '', $f['entrance']])) : '';
// модуль СДЭК берёт код пункта из адреса после «#S»
$f['mode'] === 'pvz' and $addr = 'Пункт выдачи СДЭК: ' . $pvz['a'] . ' #S' . $pvz['c'];
$values = ['ZIP' => '', 'EMAIL' => $f['email'], 'PHONE' => '+' . $phone, 'LOCATION' => $loc, 'ADDRESS' => $addr, 'PVZ' => $f['mode'] === 'pvz' ? $pvz['c'] : '']
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
bt_order_payment($order)->setField('SUM', $order->getPrice());
$r = $order->save();
if (!$r->isSuccess()) {
    $out(['ok' => false, 'errors' => ['form' => 'Не получилось оформить заказ: ' . implode('; ', $r->getErrorMessages())]]);
}
$_SESSION['BT_ORDERS'][] = (int)$order->getId();

// для следующего заказа: плательщик, способы доставки и оплаты, последний адрес; гость с чужим e-mail в чужой кабинет не пишет
if ($USER->IsAuthorized() || !empty($fresh)) {
    $prev = CUserOptions::GetOption('bt', 'last_ship', [], $userId);
    $ship = ['pt' => $pt, 'mode' => $f['mode'], 'dkey' => $in('dkey'), 'pay' => $pay, 'loc' => $loc, 'pvz' => $f['mode'] === 'pvz' ? $pvz['c'] : ($prev['pvz'] ?? ''), 'pvza' => $f['mode'] === 'pvz' ? $pvz['a'] : ($prev['pvza'] ?? '')];
    $ship += $f['mode'] === 'addr' ? ['street' => $f['street'], 'flat' => $f['flat'], 'entrance' => $f['entrance']]
        : array_intersect_key(is_array($prev) ? $prev : [], array_flip(['street', 'flat', 'entrance']));
    CUserOptions::SetOption('bt', 'last_ship', $ship, false, $userId);
    $f['mode'] === 'addr' and bt_address_remember($userId, $loc, $f['street'], $f['flat'], $f['entrance'], $f['name'], '+' . $phone);
    // реквизиты организации — в профиль покупателя (по ИНН), чтобы в следующий раз выбрать её из списка
    if ($pt === 'UR') {
        $urId = $ptypes['UR'];
        $orgId = 0;
        foreach (bt_profiles($userId, 'UR') as $p) {
            if (($p['v']['INN'] ?? '') === $f['inn']) {
                $orgId = $p['id'];
            }
        }
        $r = $orgId ? Sale\Internals\UserPropsTable::update($orgId, ['NAME' => $f['company'], 'DATE_UPDATE' => new \Bitrix\Main\Type\DateTime()])
            : Sale\Internals\UserPropsTable::add(['NAME' => $f['company'], 'USER_ID' => $userId, 'PERSON_TYPE_ID' => $urId, 'DATE_UPDATE' => new \Bitrix\Main\Type\DateTime()]);
        if ($r->isSuccess()) {
            $orgId = $orgId ?: (int)$r->getId();
            $vals = ['COMPANY' => $f['company'], 'INN' => $f['inn'], 'KPP' => $f['kpp'], 'COMPANY_ADR' => $f['company_adr']];
            $props = array_column(Sale\Internals\OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $urId, '@CODE' => array_keys($vals)], 'select' => ['ID', 'CODE', 'NAME']])->fetchAll(), null, 'CODE');
            $have = array_column(Sale\Internals\UserPropsValueTable::getList(['filter' => ['=USER_PROPS_ID' => $orgId], 'select' => ['ID', 'ORDER_PROPS_ID']])->fetchAll(), 'ID', 'ORDER_PROPS_ID');
            foreach ($props as $code => $pr) {
                isset($have[$pr['ID']]) ? Sale\Internals\UserPropsValueTable::update($have[$pr['ID']], ['VALUE' => $vals[$code]])
                    : Sale\Internals\UserPropsValueTable::add(['USER_PROPS_ID' => $orgId, 'ORDER_PROPS_ID' => $pr['ID'], 'NAME' => $pr['NAME'], 'VALUE' => $vals[$code]]);
            }
            CUserOptions::SetOption('bt', 'last_org', $orgId, false, $userId);
        }
    }
}
$out(['ok' => true, 'orderId' => (int)$order->getId(), 'accountNumber' => $order->getField('ACCOUNT_NUMBER'),
    'redirect' => (bt_tbank_payment($order) ? '/personal/order/pay/' : '/personal/order/success/') . '?id=' . (int)$order->getId()]);
