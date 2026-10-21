<?php
// Оплата через Т-Банк (модуль tinkoff.payment): настройки платёжной системы без ключей, ограничение «только физлица», агент сверки оплат, оплата по СБП.
// Терминал и пароль вписываются в админке: Магазин → Платёжные системы → «Картой онлайн»; в «По СБП» их копирует агент сверки (или apply). Чек включён: касса Т-Банка подключена к рабочему терминалу.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_tbank_setup.php [show|apply]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale;

$apply = ($argv[1] ?? 'show') === 'apply';
if (!IsModuleInstalled('tinkoff.payment') || !Loader::includeModule('sale')) {
    exit("модуль tinkoff.payment не установлен — поставить из Маркетплейса\n");
}
$all = Sale\PaySystem\Manager::getList(['filter' => ['=ACTION_FILE' => 'tinkoff'], 'order' => ['ID' => 'ASC'], 'select' => ['ID', 'NAME', 'ACTIVE', 'XML_ID']])->fetchAll();
$ps = current(array_filter($all, fn($x) => $x['XML_ID'] !== 'bt_sbp'));
if (!$ps) {
    exit("платёжная система модуля не найдена\n");
}
$id = (int)$ps['ID'];
echo "платёжная система {$id} «{$ps['NAME']}», активна: {$ps['ACTIVE']}\n";

$vals = ['ENABLE_TAXATION' => '1', 'FFD' => 'Y', 'TAXATION' => 'usn_income_outcome', 'PAYMENT_METHOD' => 'full_prepayment',
    'PAYMENT_OBJECT' => 'commodity', 'DELIVERY_TAXATION' => 'vat5', 'LANGUAGE_PAYMENT' => 'ru', 'REDIRECT' => 'N'];
$consumer = Sale\PaySystem\Service::PAY_SYSTEM_PREFIX . $id;
foreach ($vals as $code => $v) {
    echo ($apply ? '' : '[show] ') . "$code = $v\n";
    $apply and Sale\BusinessValue::setMapping($code, $consumer, null, ['PROVIDER_KEY' => 'VALUE', 'PROVIDER_VALUE' => $v]);
}
if ($ps['NAME'] !== 'Картой онлайн') {
    echo ($apply ? '' : '[show] ') . "название «Картой онлайн»\n";
    $apply and Sale\PaySystem\Manager::update($id, ['NAME' => 'Картой онлайн', 'PSA_NAME' => 'Картой онлайн', 'DESCRIPTION' => 'На защищённой странице Т‑Банка сразу после оформления', 'SORT' => 50]);
}
if (!Sale\Internals\ServiceRestrictionTable::getList(['filter' => ['=SERVICE_ID' => $id, '=SERVICE_TYPE' => 1]])->fetch()) {
    echo ($apply ? '' : '[show] ') . "ограничение: только физлица\n";
    $apply and Sale\Internals\ServiceRestrictionTable::add(['SERVICE_ID' => $id, 'SERVICE_TYPE' => 1, 'SORT' => 100,
        'CLASS_NAME' => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType', 'PARAMS' => ['PERSON_TYPE_ID' => [1]]]);
}
// служебное свойство заказа «Кассовый чек»: ссылку пишет уведомление банка о чеке, кабинет выводит её в «Документах»
foreach (Sale\Internals\PersonTypeTable::getList(['filter' => ['=ACTIVE' => 'Y'], 'select' => ['ID']])->fetchAll() as $pt) {
    if (Sale\Internals\OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $pt['ID'], '=CODE' => 'RECEIPT_URL']])->fetch()) {
        continue;
    }
    $group = (int)(CSaleOrderPropsGroup::GetList(['SORT' => 'ASC'], ['PERSON_TYPE_ID' => $pt['ID']])->Fetch()['ID'] ?? 0);
    echo ($apply ? '' : '[show] ') . "свойство заказа «Кассовый чек» (RECEIPT_URL), тип плательщика {$pt['ID']}\n";
    $apply and CSaleOrderProps::Add(['PERSON_TYPE_ID' => $pt['ID'], 'NAME' => 'Кассовый чек', 'TYPE' => 'TEXT', 'REQUIED' => 'N', 'DEFAULT_VALUE' => '', 'SORT' => 1000,
        'CODE' => 'RECEIPT_URL', 'USER_PROPS' => 'N', 'IS_LOCATION' => 'N', 'PROPS_GROUP_ID' => $group, 'UTIL' => 'Y', 'ACTIVE' => 'Y',
        'DESCRIPTION' => 'Ссылка на чек онлайн-оплаты, приходит от Т-Банка']);
}
// оплата по СБП — вторая платёжная система того же терминала со своим экраном QR (/personal/order/sbp/);
// настройки и ключи копируются с «Картой онлайн» (apply и агент сверки) — вписывать их только туда
$sbpId = (int)(current(array_filter($all, fn($x) => $x['XML_ID'] === 'bt_sbp'))['ID'] ?? 0);
if (!$sbpId) {
    echo ($apply ? '' : '[show] ') . "платёжная система «По СБП» (настройки и ключи — копия «Картой онлайн»)\n";
    if ($apply) {
        $src = Sale\Internals\PaySystemActionTable::getById($id)->fetch();
        unset($src['ID'], $src['PAY_SYSTEM_ID'], $src['LOGOTIP']);
        $r = Sale\PaySystem\Manager::add(['NAME' => 'По СБП', 'PSA_NAME' => 'По СБП', 'XML_ID' => 'bt_sbp', 'SORT' => 55,
            'DESCRIPTION' => 'QR-код или приложение вашего банка, без ввода карты'] + $src);
        if (!$r->isSuccess()) {
            exit(implode('; ', $r->getErrorMessages()) . "\n");
        }
        $sbpId = (int)$r->getId();
        Sale\PaySystem\Manager::update($sbpId, ['PAY_SYSTEM_ID' => $sbpId]);
    }
}
if ($sbpId) {
    foreach (bt_tbank_sbp_sync($apply) as $code) {
        echo ($apply ? '' : '[show] ') . "«По СБП»: $code — как у «Картой онлайн»\n";
    }
    if (!Sale\Internals\ServiceRestrictionTable::getList(['filter' => ['=SERVICE_ID' => $sbpId, '=SERVICE_TYPE' => 1]])->fetch()) {
        echo ($apply ? '' : '[show] ') . "«По СБП»: только физлица\n";
        $apply and Sale\Internals\ServiceRestrictionTable::add(['SERVICE_ID' => $sbpId, 'SERVICE_TYPE' => 1, 'SORT' => 100,
            'CLASS_NAME' => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType', 'PARAMS' => ['PERSON_TYPE_ID' => [1]]]);
    }
}
if (!CAgent::GetList([], ['NAME' => 'bt_tbank_agent();'])->Fetch()) {
    echo ($apply ? '' : '[show] ') . "агент bt_tbank_agent() раз в 3 минуты\n";
    $apply and CAgent::AddAgent('bt_tbank_agent();', '', 'N', 180, '', 'Y', ConvertTimeStamp(time() + 180, 'FULL'));
}
echo $apply ? "применено\n" : "show: ничего не менял\n";
