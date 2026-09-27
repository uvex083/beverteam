<?php
// Тестовые письма всех оформленных шаблонов на один адрес: заказ в памяти из товаров каталога, в БД ничего не сохраняется.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_mail_test.php <e-mail> [СОБЫТИЕ ...]
// Любое письмо, отправленное этим процессом, уходит только на <e-mail>: получатель, копия и скрытая копия подменяются.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Mail\EventManager;
use Bitrix\Sale;

$to = (string)($argv[1] ?? '');
check_email($to, true) or die("usage: bt_mail_test.php <e-mail> [EVENT ...]\n");
$only = array_slice($argv, 2);
Loader::includeModule('sale');
Loader::includeModule('catalog');
$db = Application::getConnection();
if ($db->query("SELECT COUNT(*) C FROM b_event WHERE SUCCESS_EXEC='N'")->fetch()['C'] > 0) {
    die("в очереди b_event есть неотправленные письма — сначала разобраться с ними\n");
}

AddEventHandler('main', 'OnBeforeEventSend', function (&$fields, &$message) use ($to) {
    $message['EMAIL_TO'] = $to;
    $message['CC'] = '';
    $message['BCC'] = '';
    $message['SUBJECT'] = '[ТЕСТ] ' . $message['SUBJECT'];
});

// заказ в памяти: товары каталога по цене сайта, доставка и оплата по ID
function test_order(string $pt, array $items, int $delivery, int $pay, array $props, string $comment = ''): Sale\Order
{
    $ptId = (int)Sale\Internals\PersonTypeTable::getList(['filter' => ['=CODE' => $pt], 'select' => ['ID']])->fetch()['ID'];
    $order = Sale\Order::create('s1');
    $order->setPersonTypeId($ptId);
    $order->setField('CURRENCY', 'RUB');
    $order->setField('USER_DESCRIPTION', $comment);
    $basket = Sale\Basket::create('s1');
    foreach ($items as $id => $qty) {
        $el = CIBlockElement::GetList([], ['ID' => $id], false, false, ['ID', 'NAME', 'DETAIL_PAGE_URL'])->GetNext();
        $price = CCatalogProduct::GetOptimalPrice($id, $qty, [2], 'N', [], 's1');
        $bi = $basket->createItem('catalog', $id);
        $bi->setFields(['NAME' => $el['~NAME'], 'QUANTITY' => $qty, 'CURRENCY' => 'RUB', 'LID' => 's1', 'CUSTOM_PRICE' => 'Y',
            'PRICE' => $price['RESULT_PRICE']['DISCOUNT_PRICE'], 'BASE_PRICE' => $price['RESULT_PRICE']['BASE_PRICE'],
            'DETAIL_PAGE_URL' => $el['~DETAIL_PAGE_URL'], 'MEASURE_NAME' => 'шт']);
    }
    $order->setBasket($basket);
    foreach ($order->getPropertyCollection() as $p) {
        if (isset($props[$p->getField('CODE')])) {
            $p->setValue($props[$p->getField('CODE')]);
        }
    }
    $shipment = $order->getShipmentCollection()->createItem(Sale\Delivery\Services\Manager::getObjectById($delivery));
    foreach ($basket as $bi) {
        $shipment->getShipmentItemCollection()->createItem($bi)->setQuantity($bi->getQuantity());
    }
    $payment = $order->getPaymentCollection()->createItem(Sale\PaySystem\Manager::getObjectById($pay));
    $order->doFinalAction(true);
    $payment->setField('SUM', $order->getPrice());
    return $order;
}

$ekb = (string)Sale\Location\LocationTable::getList(['filter' => ['=NAME.LANGUAGE_ID' => 'ru', '=NAME.NAME' => 'Екатеринбург', '=TYPE.CODE' => 'CITY'], 'select' => ['CODE']])->fetch()['CODE'];
$a = test_order('FIZ', [322 => 2, 325 => 1], 2, 2,
    ['FIO' => 'Анна Смирнова', 'EMAIL' => $to, 'PHONE' => '+79001234567', 'LOCATION' => $ekb, 'ADDRESS' => 'ул. Малышева, 51, кв./офис 12'], 'Позвоните за час до доставки');
$b = test_order('UR', [323 => 5, 321 => 2], 5, 9,
    ['CONTACT_PERSON' => 'Игорь Волков', 'COMPANY' => 'ООО «Кофейня на Плотинке»', 'INN' => '6671234567', 'EMAIL' => $to, 'PHONE' => '+79127654321',
        'LOCATION' => $ekb, 'PVZ' => 'СДЭК, ул. Ленина, 24/8']);
$host = bt_mail_host('s1');
$saleEmail = Option::get('sale', 'order_email');
$ord = fn(Sale\Order $o, string $num) => ['ORDER_ID' => $num, 'ORDER_REAL_ID' => 0, 'ORDER_ACCOUNT_NUMBER_ENCODE' => urlencode($num),
    'ORDER_DATE' => (new \Bitrix\Main\Type\DateTime())->toString(), 'ORDER_USER' => 'Анна Смирнова', 'PRICE' => bt_fmt($o->getPrice()),
    'EMAIL' => $to, 'BCC' => '', 'SALE_EMAIL' => $saleEmail, 'ORDER_LIST' => '', 'ORDER_PUBLIC_URL' => '']
    + ['BT_ORDER_URL' => $host . '/personal/orders/'] + bt_mail_order($o);
$user = ['USER_ID' => 1024, 'ID' => 1024, 'LOGIN' => $to, 'URL_LOGIN' => urlencode($to), 'EMAIL' => $to, 'NAME' => 'Анна', 'LAST_NAME' => 'Смирнова',
    'CHECKWORD' => 'test' . md5('bt'), 'STATUS' => 'Активен', 'MESSAGE' => '', 'USER_IP' => '127.0.0.1', 'USER_HOST' => ''];
$num = '1001-ТЕСТ';

$events = [
    'NEW_USER' => $user,
    'USER_INFO' => $user,
    'USER_PASS_REQUEST' => $user,
    'USER_PASS_CHANGED' => $user,
    'USER_INVITE' => $user,
    'BT_AUTH_CODE' => ['EMAIL_TO' => $to, 'CODE' => '4821', 'TTL' => 10],
    'BT_FORM_REQUEST' => ['EMAIL_TO' => $to, 'TOPIC' => 'Аренда кофемашины (тест)', 'CLIENT_NAME' => 'Мария Кузнецова', 'PHONE' => '+7 912 345-67-89',
        'EMAIL' => 'maria@example.ru', 'MESSAGE' => "Модель: Jetinno JL 05\nНужна кофемашина в офис на 30 человек, хотим обсудить условия.",
        'PAGE' => $host . '/arenda-kofemashin/', 'ADMIN_URL' => $host . '/bitrix/admin/iblock_list_admin.php?type=forms&lang=ru'],
    'SALE_NEW_ORDER' => $ord($a, $num),
    'SALE_ORDER_PAID' => $ord($a, $num),
    'SALE_ORDER_CANCEL' => $ord($a, $num) + ['ORDER_CANCEL_DESCRIPTION' => 'Покупатель попросил отменить заказ по телефону'],
    'SALE_ORDER_DELIVERY' => $ord($a, $num),
    'SALE_ORDER_REMIND_PAYMENT' => $ord($b, '1002-ТЕСТ'),
    'SALE_ORDER_TRACKING_NUMBER' => $ord($b, '1002-ТЕСТ') + ['ORDER_TRACKING_NUMBER' => '1234567890'],
    'SALE_ORDER_SHIPMENT_STATUS_CHANGED' => ['ORDER_NO' => '1002-ТЕСТ', 'SHIPMENT_NO' => '1002/1', 'STATUS_NAME' => 'Прибыл в пункт выдачи',
        'STATUS_DESCRIPTION' => 'Заказ ждёт получателя в пункте выдачи СДЭК', 'TRACKING_NUMBER' => '1234567890', 'DELIVERY_NAME' => 'СДЭК',
        'DELIVERY_TRACKING_URL' => 'https://www.cdek.ru/ru/tracking/', 'EMAIL' => $to, 'BCC' => '', 'SALE_EMAIL' => $saleEmail],
    'SALE_STATUS_CHANGED_P' => $ord($a, $num) + ['ORDER_STATUS' => 'Оплачен, формируется к отправке', 'ORDER_DESCRIPTION' => '', 'TEXT' => ''],
    'SALE_STATUS_CHANGED_F' => $ord($a, $num) + ['ORDER_STATUS' => 'Выполнен', 'ORDER_DESCRIPTION' => '', 'TEXT' => ''],
    'SALE_CHECK_PRINT' => ['ORDER_ID' => $num, 'ORDER_DATE' => (new \Bitrix\Main\Type\DateTime())->toString(), 'ORDER_USER' => 'Анна Смирнова',
        'EMAIL' => $to, 'SALE_EMAIL' => $saleEmail, 'CHECK_LINK' => $host . '/personal/orders/', 'BCC' => ''],
    'SALE_CHECK_PRINT_ERROR' => ['ORDER_ACCOUNT_NUMBER' => $num, 'CHECK_ID' => 77, 'ORDER_ID' => 0, 'ORDER_DATE' => (new \Bitrix\Main\Type\DateTime())->toString(),
        'EMAIL' => $to, 'SALE_EMAIL' => $saleEmail],
    'SALE_CHECK_VALIDATION_ERROR' => ['ORDER_ACCOUNT_NUMBER' => $num, 'ORDER_ID' => 0, 'ORDER_DATE' => (new \Bitrix\Main\Type\DateTime())->toString(),
        'EMAIL' => $to, 'SALE_EMAIL' => $saleEmail],
];

foreach ($events as $event => $fields) {
    if ($only && !in_array($event, $only, true)) {
        continue;
    }
    // BT_MAIL_PREVIEW=<папка> — собрать письма в html-файлы, ничего не отправляя
    if ($dir = getenv('BT_MAIL_PREVIEW')) {
        $r = CEventMessage::GetList('id', 'asc', ['TYPE_ID' => $event, 'SITE_ID' => 's1', 'ACTIVE' => 'Y']);
        while ($m = $r->Fetch()) {
            $f = $fields;
            $msg = \Bitrix\Main\Mail\Internal\EventMessageTable::getRowById($m['ID']);
            $res = null;
            foreach (GetModuleEvents('main', 'OnBeforeEventSend', true) as $h) {
                ExecuteModuleEventEx($h, [&$f, &$msg, null, &$res]);
            }
            $c = \Bitrix\Main\Mail\EventMessageCompiler::createInstance(['EVENT' => ['EVENT_NAME' => $event], 'FIELDS' => $f, 'MESSAGE' => $msg, 'SITE' => ['s1'], 'CHARSET' => 'UTF-8']);
            $c->compile();
            file_put_contents("$dir/$event.html", $c->getMailBody());
            echo str_pad($event, 38) . ' ' . $c->getMailSubject() . ' → ' . $c->getMailTo() . "\n";
        }
        continue;
    }
    $id = (int)CEvent::Send($event, 's1', $fields, 'N');
    for ($i = 0; $i < 5 && $db->query("SELECT COUNT(*) C FROM b_event WHERE SUCCESS_EXEC='N'")->fetch()['C'] > 0; $i++) {
        EventManager::executeEvents();
    }
    $row = $db->query('SELECT SUCCESS_EXEC FROM b_event WHERE ID=' . $id)->fetch();
    echo str_pad($event, 38) . " b_event #$id SUCCESS_EXEC=" . ($row['SUCCESS_EXEC'] ?? '?') . "\n";
}
