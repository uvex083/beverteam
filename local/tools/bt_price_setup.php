<?php
// Прайс-лист: скидки от суммы заказа (правила корзины), подписка на изменение цен (ИБ, почтовое событие, агент).
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_price_setup.php [show|apply]. Повторный запуск ничего не дублирует, правки в админке не трогает.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/bt_setup_lib.php';

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('sale');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

// ---------- скидки от суммы заказа: условия не пересекаются, поэтому «прекратить применение других» не нужно ----------
$amt = fn(string $logic, float $v) => ['CLASS_ID' => 'CondBsktAmtGroup', 'DATA' => ['logic' => $logic, 'Value' => $v, 'All' => 'AND'], 'CHILDREN' => []];
$rules = [
    'bt_sum_5' => ['Скидка 5% на заказ от 20 000 ₽', 5, [$amt('EqGr', 20000), $amt('Less', 40000)]],
    'bt_sum_10' => ['Скидка 10% на заказ от 40 000 ₽', 10, [$amt('EqGr', 40000)]],
];
foreach ($rules as $xml => [$name, $pct, $conds]) {
    $have = \Bitrix\Sale\Internals\DiscountTable::getList(['filter' => ['=XML_ID' => $xml], 'select' => ['ID', 'UNPACK']])->fetch();
    if ($have) {
        $say("правило «{$name}» уже есть (#{$have['ID']}): " . preg_replace('/\s+/', ' ', mb_substr((string)$have['UNPACK'], 0, 160)));
        continue;
    }
    $say("правило «{$name}»");
    if ($apply) {
        $id = CSaleDiscount::Add([
            'LID' => 's1', 'XML_ID' => $xml, 'NAME' => $name, 'ACTIVE' => 'Y', 'SORT' => 200, 'PRIORITY' => 1, 'LAST_DISCOUNT' => 'N', 'LAST_LEVEL_DISCOUNT' => 'N',
            'CURRENCY' => 'RUB', 'USER_GROUPS' => [2],
            'CONDITIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND', 'True' => 'True'], 'CHILDREN' => $conds],
            'ACTIONS' => ['CLASS_ID' => 'CondGroup', 'DATA' => ['All' => 'AND'], 'CHILDREN' => [
                ['CLASS_ID' => 'ActSaleBsktGrp', 'DATA' => ['Type' => 'Discount', 'Value' => $pct, 'Unit' => 'Perc', 'Max' => 0, 'All' => 'AND', 'True' => 'True'], 'CHILDREN' => []],
            ]],
        ]);
        if (!$id) {
            global $APPLICATION;
            $fail("discount $xml: " . (($e = $APPLICATION->GetException()) ? $e->GetString() : '?'));
        }
        $u = \Bitrix\Sale\Internals\DiscountTable::getById($id)->fetch();
        $say("  #$id UNPACK: " . preg_replace('/\s+/', ' ', (string)$u['UNPACK']));
    }
}

// ---------- подписка на прайс: ИБ в «Заявках», e-mail = название, ключ отписки ----------
$ib = bt_ib_ensure(['IBLOCK_TYPE_ID' => 'forms', 'CODE' => 'price_subs', 'NAME' => 'Подписка на прайс-лист', 'SORT' => 300,
    'DESCRIPTION' => 'Кто получает письмо, когда меняются цены. Название — e-mail. Снять галочку «Активность» — письма перестанут приходить.'],
    ['TOKEN' => ['Ключ отписки (не менять)', 'S', []]], $apply, $say, $fail);
$apply && $ib and bt_grid_sort('forms', $ib);

// ---------- почтовое событие ----------
if (!CEventType::GetList(['TYPE_ID' => 'BT_PRICE_CHANGED', 'LID' => 'ru'])->Fetch()) {
    $say('почтовое событие BT_PRICE_CHANGED');
    $apply and (new CEventType())->Add(['LID' => 'ru', 'EVENT_NAME' => 'BT_PRICE_CHANGED', 'NAME' => 'Прайс-лист: цены изменились', 'EVENT_TYPE' => 'email',
        'DESCRIPTION' => "#EMAIL_TO# — подписчик\n#DATE# — дата изменения цен\n#TOKEN# — ключ отписки"]);
}
if (!CEventMessage::GetList('id', 'asc', ['TYPE_ID' => 'BT_PRICE_CHANGED', 'SITE_ID' => 's1'])->Fetch()) {
    $say('  шаблон письма (оформление — bt_mail_setup.php)');
    $apply and ((new CEventMessage())->Add(['ACTIVE' => 'Y', 'EVENT_NAME' => 'BT_PRICE_CHANGED', 'LID' => ['s1'], 'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
        'EMAIL_TO' => '#EMAIL_TO#', 'SUBJECT' => 'Цены в прайс-листе обновились', 'BODY_TYPE' => 'html', 'MESSAGE' => 'https://#SERVER_NAME#/price/']) or $fail('event message'));
}

// ---------- агент рассылки: отметка «уже сообщили» ставится на текущую дату цен, чтобы первое письмо ушло только после новой правки ----------
if (Option::get('bt', 'price_notified', '') === '') {
    $say('отметка «подписчикам сообщено» = ' . date('d.m.Y H:i', bt_price_date()));
    $apply and Option::set('bt', 'price_notified', (string)bt_price_date());
}
if (!CAgent::GetList([], ['NAME' => 'bt_price_notify_agent();'])->Fetch()) {
    $say('агент bt_price_notify_agent() раз в час');
    $apply and CAgent::AddAgent('bt_price_notify_agent();', '', 'N', 3600, '', 'Y', ConvertTimeStamp(time() + 600, 'FULL'), 100, false, false);
}
BXClearCache(true, '/bt/');
echo "done\n";
