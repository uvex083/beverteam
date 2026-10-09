<?php
// Оплата через Т-Банк (модуль tinkoff.payment): настройки платёжной системы без ключей, ограничение «только физлица», агент сверки оплат.
// Терминал и пароль вписываются в админке: Магазин → Платёжные системы → «Картой или СБП онлайн». Чек выключен, пока нет онлайн-кассы.
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
$ps = Sale\PaySystem\Manager::getList(['filter' => ['=ACTION_FILE' => 'tinkoff'], 'select' => ['ID', 'NAME', 'ACTIVE']])->fetch();
if (!$ps) {
    exit("платёжная система модуля не найдена\n");
}
$id = (int)$ps['ID'];
echo "платёжная система {$id} «{$ps['NAME']}», активна: {$ps['ACTIVE']}\n";

$vals = ['ENABLE_TAXATION' => '0', 'FFD' => 'Y', 'TAXATION' => 'usn_income_outcome', 'PAYMENT_METHOD' => 'full_prepayment',
    'PAYMENT_OBJECT' => 'commodity', 'DELIVERY_TAXATION' => 'vat5', 'LANGUAGE_PAYMENT' => 'ru', 'REDIRECT' => 'N'];
$consumer = Sale\PaySystem\Service::PAY_SYSTEM_PREFIX . $id;
foreach ($vals as $code => $v) {
    echo ($apply ? '' : '[show] ') . "$code = $v\n";
    $apply and Sale\BusinessValue::setMapping($code, $consumer, null, ['PROVIDER_KEY' => 'VALUE', 'PROVIDER_VALUE' => $v]);
}
if ($ps['NAME'] !== 'Картой или СБП онлайн') {
    echo ($apply ? '' : '[show] ') . "название «Картой или СБП онлайн»\n";
    $apply and Sale\PaySystem\Manager::update($id, ['NAME' => 'Картой или СБП онлайн', 'DESCRIPTION' => 'Оплата на защищённой странице Т-Банка сразу после оформления', 'SORT' => 50]);
}
if (!Sale\Internals\ServiceRestrictionTable::getList(['filter' => ['=SERVICE_ID' => $id, '=SERVICE_TYPE' => 1]])->fetch()) {
    echo ($apply ? '' : '[show] ') . "ограничение: только физлица\n";
    $apply and Sale\Internals\ServiceRestrictionTable::add(['SERVICE_ID' => $id, 'SERVICE_TYPE' => 1, 'SORT' => 100,
        'CLASS_NAME' => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType', 'PARAMS' => ['PERSON_TYPE_ID' => [1]]]);
}
if (!CAgent::GetList([], ['NAME' => 'bt_tbank_agent();'])->Fetch()) {
    echo ($apply ? '' : '[show] ') . "агент bt_tbank_agent() раз в 3 минуты\n";
    $apply and CAgent::AddAgent('bt_tbank_agent();', '', 'N', 180, '', 'Y', ConvertTimeStamp(time() + 180, 'FULL'));
}
echo $apply ? "применено\n" : "show: ничего не менял\n";
