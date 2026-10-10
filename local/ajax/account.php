<?php
// Личный кабинет: POST action=profile | company_save | company_del | addr_save | addr_del | addr_main | reorder. Только авторизованным, с sessid.
// Реквизиты юрлиц и адреса — профили покупателя Битрикса (UR и FIZ). Ответ: {ok: true, ...} или {ok: false, errors: {поле: текст}}
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale;
use Bitrix\Sale\Internals\UserPropsTable;
use Bitrix\Sale\Internals\UserPropsValueTable;

/** @global CUser $USER */
header('Content-Type: application/json; charset=utf-8');
$req = Context::getCurrent()->getRequest();
$out = function (array $data, int $code = 200) {
    http_response_code($code);
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
};
if (!$req->isPost() || !check_bitrix_sessid()) {
    $out(['ok' => false, 'error' => 'sessid'], 403);
}
if (!$USER->IsAuthorized()) {
    $out(['ok' => false, 'error' => 'auth', 'message' => 'Войдите в кабинет ещё раз'], 401);
}
Loader::includeModule('sale');
Loader::includeModule('catalog');
$uid = (int)$USER->GetID();
$in = fn(string $k, int $max = 255) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$req->getPost($k))), 0, $max);
$pt = fn(string $code) => (int)Sale\Internals\PersonTypeTable::getList(['filter' => ['=CODE' => $code, '=LID' => SITE_ID], 'select' => ['ID']])->fetch()['ID'];
// профиль покупателя: только свой и нужного типа
$own = function (int $id, int $ptId) use ($uid) {
    return $id && UserPropsTable::getList(['filter' => ['=ID' => $id, '=USER_ID' => $uid, '=PERSON_TYPE_ID' => $ptId], 'select' => ['ID']])->fetch();
};
$save = function (int $id, int $ptId, string $name, array $values) use ($uid, $out) {
    $r = $id ? UserPropsTable::update($id, ['NAME' => $name, 'DATE_UPDATE' => new DateTime()])
        : UserPropsTable::add(['NAME' => $name, 'USER_ID' => $uid, 'PERSON_TYPE_ID' => $ptId, 'DATE_UPDATE' => new DateTime()]);
    $r->isSuccess() or $out(['ok' => false, 'message' => implode('; ', $r->getErrorMessages())], 500);
    $id = $id ?: (int)$r->getId();
    $props = array_column(Sale\Internals\OrderPropsTable::getList(['filter' => ['=PERSON_TYPE_ID' => $ptId, '@CODE' => array_keys($values)], 'select' => ['ID', 'CODE', 'NAME']])->fetchAll(), null, 'CODE');
    $have = array_column(UserPropsValueTable::getList(['filter' => ['=USER_PROPS_ID' => $id], 'select' => ['ID', 'ORDER_PROPS_ID']])->fetchAll(), 'ID', 'ORDER_PROPS_ID');
    foreach ($values as $code => $v) {
        if (!isset($props[$code])) {
            continue;
        }
        $pid = (int)$props[$code]['ID'];
        isset($have[$pid]) ? UserPropsValueTable::update($have[$pid], ['VALUE' => $v])
            : UserPropsValueTable::add(['USER_PROPS_ID' => $id, 'ORDER_PROPS_ID' => $pid, 'NAME' => $props[$code]['NAME'], 'VALUE' => $v]);
    }
    return $id;
};
$phoneErr = fn(string $v) => $v !== '' && !bt_phone_digits($v) ? 'Введите номер полностью: +7 и 10 цифр' : '';

switch ((string)$req->getPost('action')) {
    case 'profile':
        $f = ['NAME' => $in('name', 50), 'LAST_NAME' => $in('last_name', 50), 'PERSONAL_PHONE' => $in('phone', 30)];
        $err = array_filter(['name' => mb_strlen($f['NAME']) < 2 ? 'Как к вам обращаться? Минимум 2 символа' : '', 'phone' => $phoneErr($f['PERSONAL_PHONE'])]);
        $err and $out(['ok' => false, 'errors' => $err]);
        $f['PERSONAL_PHONE'] = $f['PERSONAL_PHONE'] !== '' ? '+' . bt_phone_digits($f['PERSONAL_PHONE']) : '';
        $u = new CUser();
        $u->Update($uid, $f) or $out(['ok' => false, 'message' => strip_tags((string)$u->LAST_ERROR)], 500);
        $out(['ok' => true, 'user' => bt_user_js()]);

    case 'company_save':
        $ur = $pt('UR');
        $id = (int)$req->getPost('id');
        if ($id && !$own($id, $ur)) {
            $out(['ok' => false, 'message' => 'Реквизиты не найдены'], 404);
        }
        $f = ['COMPANY' => $in('company', 200), 'INN' => preg_replace('/\D/', '', $in('inn', 20)), 'KPP' => preg_replace('/\D/', '', $in('kpp', 20)), 'COMPANY_ADR' => $in('company_adr', 300)];
        $err = array_filter([
            'company' => $f['COMPANY'] === '' ? 'Это поле нужно заполнить' : '',
            'inn' => !in_array(strlen($f['INN']), [10, 12], true) ? ($f['INN'] === '' ? 'Это поле нужно заполнить' : 'ИНН состоит из 10 цифр у компании и 12 у ИП') : (bt_inn_ok($f['INN']) ? '' : 'Проверьте ИНН — в номере ошибка'),
            'kpp' => $f['KPP'] !== '' && strlen($f['KPP']) !== 9 ? 'КПП состоит из 9 цифр' : '',
        ]);
        $err and $out(['ok' => false, 'errors' => $err]);
        $out(['ok' => true, 'id' => $save($id, $ur, $f['COMPANY'], $f)]);

    case 'addr_save':
        $fiz = $pt('FIZ');
        $id = (int)$req->getPost('id');
        if ($id && !$own($id, $fiz)) {
            $out(['ok' => false, 'message' => 'Адрес не найден'], 404);
        }
        $loc = $in('loc', 50);
        $f = ['LOCATION' => $loc, 'ADDRESS' => $in('street', 200), 'FLAT' => $in('flat', 50), 'ENTRANCE' => $in('entr', 100), 'FIO' => $in('who', 100), 'PHONE' => $in('tel', 30)];
        $err = array_filter([
            'city' => $loc === '' || !Sale\Location\LocationTable::getList(['filter' => ['=CODE' => $loc], 'select' => ['ID']])->fetch() ? 'Выберите город из списка' : '',
            'street' => $f['ADDRESS'] === '' ? 'Это поле нужно заполнить' : '',
            'tel' => $phoneErr($f['PHONE']),
        ]);
        $err and $out(['ok' => false, 'errors' => $err]);
        $f['PHONE'] = $f['PHONE'] !== '' ? '+' . bt_phone_digits($f['PHONE']) : '';
        $id = $save($id, $fiz, $in('tag', 50) ?: 'Адрес', $f);
        if ($req->getPost('main') === 'Y') {
            CUserOptions::SetOption('bt', 'main_addr', $id, false, $uid);
        }
        $out(['ok' => true, 'id' => $id]);

    case 'addr_main':
        $id = (int)$req->getPost('id');
        $own($id, $pt('FIZ')) or $out(['ok' => false, 'message' => 'Адрес не найден'], 404);
        CUserOptions::SetOption('bt', 'main_addr', $id, false, $uid);
        $out(['ok' => true]);

    case 'company_del':
    case 'addr_del':
        $id = (int)$req->getPost('id');
        $own($id, $pt($req->getPost('action') === 'addr_del' ? 'FIZ' : 'UR')) or $out(['ok' => false, 'message' => 'Не найдено'], 404);
        CSaleOrderUserProps::Delete($id);
        $out(['ok' => true]);

    case 'reorder':
        // позиции заказа — в корзину; товары, которых больше нет в каталоге, пропускаем
        $order = Sale\Order::load((int)$req->getPost('id'));
        if (!$order || (int)$order->getUserId() !== $uid) {
            $out(['ok' => false, 'message' => 'Заказ не найден'], 404);
        }
        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID);
        $added = $skipped = 0;
        foreach ($order->getBasket() as $oi) {
            $id = (int)$oi->getProductId();
            $active = CIBlockElement::GetList([], ['ID' => $id, 'IBLOCK_ID' => bt_iblock('catalog'), 'ACTIVE' => 'Y'], []);
            if (!$active) {
                $skipped++;
                continue;
            }
            $kg = bt_basket_pack($oi);
            bt_basket_put($basket, $id, $kg, $kg ? round($oi->getQuantity() / $kg) : $oi->getQuantity(), true) === '' ? $added++ : $skipped++;
        }
        $r = $basket->save();
        $r->isSuccess() or $out(['ok' => false, 'message' => implode('; ', $r->getErrorMessages())], 500);
        $out(['ok' => true, 'added' => $added, 'skipped' => $skipped] + bt_basket_state($basket));
}
$out(['ok' => false, 'error' => 'action'], 400);
