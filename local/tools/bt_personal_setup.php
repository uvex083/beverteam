<?php
// Вход по коду и личный кабинет: почтовое событие BT_AUTH_CODE и шаблон письма, свойства заказа «Квартира / офис» и «Подъезд, этаж, домофон»
// для адресов в профилях покупателя, свойство «Покупатель» у заявок с сайта.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_personal_setup.php [show|apply]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\PersonTypeTable;

Loader::includeModule('iblock');
Loader::includeModule('sale');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

// письмо с кодом входа
const EV = 'BT_AUTH_CODE';
if (!CEventType::GetList(['TYPE_ID' => EV, 'LID' => 'ru'])->Fetch()) {
    $say('почтовое событие ' . EV);
    $apply and (new CEventType())->Add(['LID' => 'ru', 'EVENT_NAME' => EV, 'NAME' => 'Код для входа на сайт', 'SORT' => 110, 'DESCRIPTION' =>
        "#EMAIL_TO# — e-mail покупателя\n#CODE# — код из 4 цифр\n#TTL# — сколько минут действует код"]);
}
if (!CEventMessage::GetList('id', 'asc', ['TYPE_ID' => EV])->Fetch()) {
    $say('шаблон письма ' . EV);
    if ($apply) {
        $m = new CEventMessage();
        $m->Add(['ACTIVE' => 'Y', 'EVENT_NAME' => EV, 'LID' => ['s1'], 'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#', 'EMAIL_TO' => '#EMAIL_TO#',
            'SUBJECT' => 'Код для входа: #CODE#', 'BODY_TYPE' => 'text', 'MESSAGE' =>
                "Здравствуйте!\n\nКод для входа в личный кабинет на сайте #SERVER_NAME#: #CODE#\n\nКод действует #TTL# минут. Если вы не запрашивали код, просто не отвечайте на это письмо — без кода в кабинет не войти.\n\nBEVERTEAM\n"])
            or $fail('message: ' . $m->LAST_ERROR);
    }
}

// адрес доставки в профиле покупателя хранится по частям, как в форме оформления заказа
$fiz = (int)(PersonTypeTable::getList(['filter' => ['=CODE' => 'FIZ'], 'select' => ['ID']])->fetch()['ID'] ?? 0) or $fail('нет типа плательщика FIZ');
$group = (int)OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $fiz, '=CODE' => 'ADDRESS'], 'select' => ['PROPS_GROUP_ID']])->fetch()['PROPS_GROUP_ID'];
foreach (['FLAT' => ['Квартира / офис', 730], 'ENTRANCE' => ['Подъезд, этаж, домофон', 740]] as $code => [$name, $sort]) {
    if (OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $fiz, '=CODE' => $code]])->fetch()) {
        continue;
    }
    $say("свойство заказа $code «{$name}» (служебное, для адресов в профиле)");
    if ($apply) {
        $r = OrderPropsTable::add(['PERSON_TYPE_ID' => $fiz, 'NAME' => $name, 'TYPE' => 'STRING', 'CODE' => $code, 'REQUIRED' => 'N', 'ACTIVE' => 'Y',
            'UTIL' => 'Y', 'USER_PROPS' => 'Y', 'SORT' => $sort, 'PROPS_GROUP_ID' => $group, 'DEFAULT_VALUE' => '', 'DESCRIPTION' => '',
            'SETTINGS' => [], 'ENTITY_REGISTRY_TYPE' => 'ORDER', 'ENTITY_TYPE' => 'ORDER', 'XML_ID' => 'bt_' . strtolower($code)]);
        $r->isSuccess() or $fail("prop $code: " . implode('; ', $r->getErrorMessages()));
    }
}

// заявка авторизованного покупателя привязывается к нему — для раздела «Подписка» в кабинете
$ibId = (int)(CIBlock::GetList([], ['=CODE' => 'form_requests', 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'] ?? 0) or $fail('нет ИБ form_requests');
if (!CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'USER_ID'])->Fetch()) {
    $say('свойство заявок USER_ID «Покупатель»');
    if ($apply) {
        $bp = new CIBlockProperty();
        $bp->Add(['IBLOCK_ID' => $ibId, 'CODE' => 'USER_ID', 'NAME' => 'Покупатель', 'SORT' => 180, 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'UserID', 'ACTIVE' => 'Y'])
            or $fail("prop USER_ID: {$bp->LAST_ERROR}");
    }
}

// паролей у покупателей нет: штатные регистрация и восстановление пароля Битрикса посетителям не нужны
foreach (['new_user_registration' => 'N', 'new_user_registration_email_confirmation' => 'N', 'store_password' => 'N'] as $opt => $val) {
    if (COption::GetOptionString('main', $opt) !== $val) {
        $say("главный модуль: $opt = $val");
        $apply and COption::SetOptionString('main', $opt, $val);
    }
}
echo "done\n";
