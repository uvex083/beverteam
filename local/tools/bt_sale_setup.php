<?php
// Магазин под макет checkout.html: плательщики, свойства заказа, доставки, оплаты.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_sale_setup.php [show|apply]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Delivery\Services\Table as DeliveryTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\Internals\ServiceRestrictionTable;

Loader::includeModule('sale');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");
$check = fn($r, string $what) => $r->isSuccess() or $fail($what . ': ' . implode('; ', $r->getErrorMessages()));

// ---------- типы плательщиков: символьные коды для кода и шаблонов ----------
$pt = [];
foreach (PersonTypeTable::getList(['select' => ['ID', 'NAME', 'CODE']])->fetchAll() as $p) {
    $code = str_contains($p['NAME'], 'Юрид') ? 'UR' : 'FIZ';
    $pt[$code] = (int)$p['ID'];
    if ($p['CODE'] !== $code) {
        $say("тип плательщика {$p['ID']} «{$p['NAME']}» → код $code");
        $apply and $check(PersonTypeTable::update($p['ID'], ['CODE' => $code]), 'person type');
    }
}
if ($apply && ($pt['UR'] ?? 0)) {
    $name = 'Юридическое лицо или ИП';
    if (PersonTypeTable::getById($pt['UR'])->fetch()['NAME'] !== $name) {
        $check(PersonTypeTable::update($pt['UR'], ['NAME' => $name]), 'person type name');
    }
}

// ---------- свойства заказа: обязательность под сценарии макета ----------
// самовывоз и курьер по городу не требуют индекса и адреса; ИНН юрлицу нужен для счёта
$propsWant = [
    'FIZ' => ['ZIP' => ['REQUIRED' => 'N'], 'ADDRESS' => ['REQUIRED' => 'N'], 'CITY' => ['ACTIVE' => 'N']],
    'UR' => ['ZIP' => ['REQUIRED' => 'N'], 'ADDRESS' => ['REQUIRED' => 'N'], 'CITY' => ['ACTIVE' => 'N'], 'FAX' => ['ACTIVE' => 'N'],
        'INN' => ['REQUIRED' => 'Y'], 'PHONE' => ['REQUIRED' => 'Y']],
];
foreach ($propsWant as $ptCode => $list) {
    foreach ($list as $code => $want) {
        $p = OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $pt[$ptCode], '=CODE' => $code], 'select' => ['ID', 'REQUIRED', 'ACTIVE']])->fetch();
        if (!$p) {
            continue;
        }
        $diff = array_diff_assoc($want, array_intersect_key($p, $want));
        if ($diff) {
            $say("свойство $ptCode.$code → " . json_encode($diff));
            $apply and $check(OrderPropsTable::update($p['ID'], $diff), "prop $code");
        }
    }
    // пункт выдачи СДЭК — адрес ПВЗ строкой (виджет подключим с ключами API)
    if (!OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $pt[$ptCode], '=CODE' => 'PVZ']])->fetch()) {
        $say("свойство $ptCode.PVZ «Пункт выдачи»");
        if ($apply) {
            $group = OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $pt[$ptCode], '=CODE' => 'ADDRESS'], 'select' => ['PROPS_GROUP_ID']])->fetch()['PROPS_GROUP_ID'];
            $check(OrderPropsTable::add(['PERSON_TYPE_ID' => $pt[$ptCode], 'NAME' => 'Пункт выдачи', 'TYPE' => 'STRING', 'CODE' => 'PVZ',
                'REQUIRED' => 'N', 'ACTIVE' => 'Y', 'UTIL' => 'N', 'USER_PROPS' => 'Y', 'SORT' => 720, 'PROPS_GROUP_ID' => $group,
                'DEFAULT_VALUE' => '', 'ENTITY_REGISTRY_TYPE' => 'ORDER', 'ENTITY_TYPE' => 'ORDER', 'SETTINGS' => []]), 'prop PVZ');
        }
    }
}

// ---------- доставки ----------
$ekb = \Bitrix\Sale\Location\LocationTable::getList(['filter' => ['=NAME.NAME' => 'Екатеринбург', '=NAME.LANGUAGE_ID' => 'ru', '=TYPE.CODE' => 'CITY'], 'select' => ['CODE']])->fetch()['CODE'] ?? $fail('нет местоположения Екатеринбург');

function bt_delivery(array $f, bool $apply, callable $say, callable $check): int
{
    $d = DeliveryTable::getList(['filter' => ['=XML_ID' => $f['XML_ID']], 'select' => ['ID']])->fetch()
        ?: DeliveryTable::getList(['filter' => ['=NAME' => $f['_OLD_NAME'] ?? '-'], 'select' => ['ID']])->fetch();
    unset($f['_OLD_NAME']);
    if ($d) {
        $cur = DeliveryTable::getById($d['ID'])->fetch();
        $upd = array_filter($f, fn($v, $k) => $k !== 'CONFIG' && ($cur[$k] ?? null) != $v, ARRAY_FILTER_USE_BOTH);
        if (isset($f['CONFIG']) && $cur['CONFIG'] != $f['CONFIG']) {
            $upd['CONFIG'] = $f['CONFIG'];
        }
        if ($upd) {
            $say("доставка {$d['ID']} {$f['NAME']}: " . implode(', ', array_keys($upd)));
            $apply and $check(Sale\Delivery\Services\Manager::update($d['ID'], $upd), 'delivery update');
        }
        return (int)$d['ID'];
    }
    $say("создать доставку «{$f['NAME']}»");
    if (!$apply) {
        return 0;
    }
    $r = Sale\Delivery\Services\Manager::add($f + ['PARENT_ID' => 0, 'CURRENCY' => 'RUB', 'CLASS_NAME' => '\Bitrix\Sale\Delivery\Services\Configurable']);
    $check($r, 'delivery add');
    return (int)$r->getId();
}
// ограничение по местоположению — только Екатеринбург
function bt_delivery_only_city(int $id, string $locCode, bool $apply, callable $say): void
{
    if (!$id || \Bitrix\Sale\Delivery\DeliveryLocationTable::getList(['filter' => ['=DELIVERY_ID' => $id, '=LOCATION_CODE' => $locCode]])->fetch()) {
        return;
    }
    $say("  доставка $id: только Екатеринбург");
    if ($apply) {
        \Bitrix\Sale\Delivery\DeliveryLocationTable::resetMultipleForOwner($id, ['L' => [$locCode]]);
        if (!ServiceRestrictionTable::getList(['filter' => ['=SERVICE_ID' => $id, '=CLASS_NAME' => '\Bitrix\Sale\Delivery\Restrictions\ByLocation']])->fetch()) {
            ServiceRestrictionTable::add(['SERVICE_ID' => $id, 'SERVICE_TYPE' => \Bitrix\Sale\Services\Base\RestrictionManager::SERVICE_TYPE_SHIPMENT,
                'CLASS_NAME' => '\Bitrix\Sale\Delivery\Restrictions\ByLocation', 'SORT' => 100, 'PARAMS' => []]);
        }
    }
}

$cfg = fn(int $price, int $from, int $to) => ['MAIN' => ['PRICE' => $price, 'CURRENCY' => 'RUB', 'PERIOD' => ['FROM' => $from, 'TO' => $to, 'TYPE' => 'D']]];
$courier = bt_delivery(['XML_ID' => 'bt_courier', '_OLD_NAME' => 'Доставка курьером', 'NAME' => 'Курьер BEVERTEAM', 'ACTIVE' => 'Y', 'SORT' => 10,
    'DESCRIPTION' => 'По Екатеринбургу на следующий рабочий день. Бесплатно от 3 000 ₽.', 'CONFIG' => $cfg(350, 1, 1)], $apply, $say, $check);
$pickup = bt_delivery(['XML_ID' => 'bt_pickup', '_OLD_NAME' => 'Самовывоз', 'NAME' => 'Самовывоз со склада', 'ACTIVE' => 'Y', 'SORT' => 20,
    'DESCRIPTION' => 'ул. Колокольная, 31А, по договорённости с менеджером.', 'CONFIG' => $cfg(0, 0, 0)], $apply, $say, $check);
$cdek = bt_delivery(['XML_ID' => 'bt_cdek', 'NAME' => 'СДЭК', 'ACTIVE' => 'Y', 'SORT' => 30,
    'DESCRIPTION' => 'До двери или пункта выдачи по всей России. Стоимость сообщит менеджер после оформления.', 'CONFIG' => $cfg(0, 2, 7)], $apply, $say, $check);
bt_delivery_only_city($courier, $ekb, $apply, $say);
bt_delivery_only_city($pickup, $ekb, $apply, $say);

// бесплатная доставка курьером от 3 000 ₽ — наценка/скидка на доставку через правило корзины
$ruleName = 'Бесплатная доставка курьером от 3 000 ₽';
if (!\Bitrix\Sale\Internals\DiscountTable::getList(['filter' => ['=NAME' => $ruleName]])->fetch()) {
    $say("правило корзины «{$ruleName}»");
    if ($apply && $courier) {
        // CSaleDiscount::Add сам собирает условия и действия в исполняемый код правила
        $id = CSaleDiscount::Add([
            'LID' => 's1', 'NAME' => $ruleName, 'ACTIVE' => 'Y', 'SORT' => 100, 'PRIORITY' => 1, 'LAST_DISCOUNT' => 'N', 'CURRENCY' => 'RUB',
            'USER_GROUPS' => [2],
            'CONDITIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND', 'True' => 'True'], 'CHILDREN' => [
                ['CLASS_ID' => 'CondSaleOrderSumm', 'DATA' => ['logic' => 'EqGr', 'value' => 3000]],
                ['CLASS_ID' => 'CondSaleDelivery', 'DATA' => ['logic' => 'Equal', 'value' => [$courier]]],
            ]],
            'ACTIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND'], 'CHILDREN' => [
                ['CLASS_ID' => 'ActSaleDelivery', 'DATA' => ['Type' => 'Discount', 'Value' => 100, 'Unit' => 'Perc']],
            ]],
        ]);
        $id or $fail('discount: ' . ($GLOBALS['APPLICATION']->GetException()?->GetString() ?? ''));
    }
}

// ---------- оплаты ----------
$payWant = [
    'cash' => ['NAME' => 'При получении — наличными или картой', 'ACTIVE' => 'Y', 'SORT' => 10, 'DESCRIPTION' => 'Курьеру BEVERTEAM или при самовывозе в Екатеринбурге.'],
    'bill' => ['NAME' => 'По счёту для юрлиц и ИП', 'ACTIVE' => 'Y', 'SORT' => 20, 'DESCRIPTION' => 'Выставим счёт после подтверждения заказа менеджером.'],
    // демо-оплаты решения: без договоров не работают
    'inner' => ['ACTIVE' => 'N'], 'cashondeliverycalc' => ['ACTIVE' => 'N'], 'yandex' => ['ACTIVE' => 'N'], 'sberbank' => ['ACTIVE' => 'N'], 'webmoney' => ['ACTIVE' => 'N'],
];
foreach (PaySystemActionTable::getList(['select' => ['ID', 'NAME', 'ACTION_FILE', 'ACTIVE', 'SORT', 'DESCRIPTION']])->fetchAll() as $ps) {
    $want = $payWant[$ps['ACTION_FILE']] ?? null;
    if (!$want) {
        continue;
    }
    $diff = array_diff_assoc($want, array_intersect_key($ps, $want));
    if ($diff) {
        $say("оплата {$ps['ID']} {$ps['ACTION_FILE']} → " . json_encode($diff, JSON_UNESCAPED_UNICODE));
        $apply and $check(Sale\PaySystem\Manager::update($ps['ID'], $diff), 'paysystem');
    }
}
// «по счёту» — только юрлицам: ограничение по типу плательщика
$bill = PaySystemActionTable::getList(['filter' => ['=ACTION_FILE' => 'bill'], 'select' => ['ID']])->fetch()['ID'] ?? 0;
$cls = '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType';
if ($bill && !ServiceRestrictionTable::getList(['filter' => ['=SERVICE_ID' => $bill, '=CLASS_NAME' => $cls]])->fetch()) {
    $say("  оплата по счёту: только {$pt['UR']} (юрлица)");
    $apply and ServiceRestrictionTable::add(['SERVICE_ID' => $bill, 'SERVICE_TYPE' => \Bitrix\Sale\Services\Base\RestrictionManager::SERVICE_TYPE_PAYMENT,
        'CLASS_NAME' => $cls, 'SORT' => 100, 'PARAMS' => ['PERSON_TYPE_ID' => [$pt['UR']]]]);
}

echo "done\n";
