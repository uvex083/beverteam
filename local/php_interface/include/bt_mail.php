<?php
// Поля #BT_*# для писем о заказе (шаблоны SALE_*)

use Bitrix\Main\Loader;
use Bitrix\Sale;

// адрес уведомлений менеджеру; отправитель — отдельно: main.email_from / sale.order_email
function bt_notify_email(): string
{
    return \Bitrix\Main\Config\Option::get('bt', 'notify_email') ?: \Bitrix\Main\Config\Option::get('sale', 'order_email') ?: \Bitrix\Main\Config\Option::get('main', 'email_from');
}

// отправитель noreply; копии заказов, «Ответить» и письма на адрес по умолчанию — на адрес уведомлений
function bt_mail_route(array &$fields, array &$message): void
{
    $to = bt_notify_email();
    if ($to === '' || $to === \Bitrix\Main\Config\Option::get('sale', 'order_email')) {
        return;
    }
    // скрытая копия уведомлений — опция bt/notify_bcc
    $bcc = \Bitrix\Main\Config\Option::get('bt', 'notify_bcc');
    if (($fields['BCC'] ?? '') === \Bitrix\Main\Config\Option::get('sale', 'order_email')) {
        $fields['BCC'] = $bcc ? $to . ', ' . $bcc : $to;
    }
    if ($bcc && ($message['EVENT_NAME'] ?? '') === 'BT_FORM_REQUEST') {
        $message['BCC'] = trim(trim((string)($message['BCC'] ?? '')) . ', ' . $bcc, ', ');
    }
    if (trim((string)($message['EMAIL_TO'] ?? '')) === '#DEFAULT_EMAIL_FROM#') {
        $message['EMAIL_TO'] = $to;
    }
    if (trim((string)($message['REPLY_TO'] ?? '')) === '') {
        $message['REPLY_TO'] = $to;
    }
}

// поля заказа — если событие SALE_* передало ORDER_REAL_ID
function bt_mail_before_send(array &$fields, array &$message): void
{
    if (!str_starts_with((string)($message['EVENT_NAME'] ?? ''), 'SALE_') || isset($fields['BT_ORDER'])
        || (int)($fields['ORDER_REAL_ID'] ?? 0) <= 0 || !Loader::includeModule('sale')) {
        return;
    }
    if ($order = Sale\Order::load((int)$fields['ORDER_REAL_ID'])) {
        $fields += bt_mail_order($order);
    }
}

function bt_mail_host(string $siteId): string
{
    $site = \Bitrix\Main\SiteTable::getById($siteId)->fetch();
    return 'https://' . ($site['SERVER_NAME'] ?? '' ?: \Bitrix\Main\Config\Option::get('main', 'server_name'));
}

// заказ может быть и несохранённым — для тестовых писем
function bt_mail_order(Sale\Order $order): array
{
    $e = fn($s) => htmlspecialcharsbx((string)$s);
    $host = bt_mail_host((string)$order->getSiteId());
    $co = bt_contacts();
    $props = [];
    foreach ($order->getPropertyCollection() as $p) {
        $props[$p->getField('CODE')] = is_array($p->getValue()) ? '' : trim((string)$p->getValue());
    }
    $shipment = null;
    foreach ($order->getShipmentCollection() as $s) {
        if (!$s->isSystem()) {
            $shipment = $s;
        }
    }
    $payment = null;
    foreach ($order->getPaymentCollection() as $p) {
        $payment = $p;
        break;
    }
    $dCode = $shipment ? (string)(Sale\Delivery\Services\Table::getById($shipment->getDeliveryId())->fetch()['XML_ID'] ?? '') : '';
    $pCode = $payment ? (string)(Sale\PaySystem\Manager::getById($payment->getPaymentSystemId())['ACTION_FILE'] ?? '') : '';
    $city = '';
    if ($props['LOCATION'] ?? '') {
        // в новых заказах хранится код местоположения, в старых — ID
        foreach (['=CODE', '=ID'] as $by) {
            $city = (string)(Sale\Location\LocationTable::getList(['filter' => [$by => $props['LOCATION'], '=NAME.LANGUAGE_ID' => 'ru'], 'select' => ['N' => 'NAME.NAME']])->fetch()['N'] ?? '');
            if ($city !== '' || !ctype_digit($props['LOCATION'])) {
                break;
            }
        }
    }
    $name = $props['FIO'] ?? '' ?: ($props['CONTACT_PERSON'] ?? '');
    $d = function_exists('bt_phone_digits') ? bt_phone_digits($props['PHONE'] ?? '') : '';
    $phone = $d ? "+7\u{00A0}" . substr($d, 1, 3) . "\u{00A0}" . substr($d, 4, 3) . "\u{2011}" . substr($d, 7, 2) . "\u{2011}" . substr($d, 9, 2) : ($props['PHONE'] ?? '');

    $cell = 'font-family:Manrope,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.45;color:#0E0E0C;border-bottom:1px solid #DCDCD4;vertical-align:top';
    $head = 'font-family:Manrope,Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6C6C64;padding:0 0 8px;border-bottom:1px solid #0E0E0C';
    $rows = '';
    $full = 0.0;
    foreach ($order->getBasket() as $bi) {
        $title = $e($bi->getField('NAME'));
        $url = (string)$bi->getField('DETAIL_PAGE_URL');
        if ($url !== '') {
            $title = '<a href="' . $e(str_starts_with($url, 'http') ? $url : $host . $url) . '" style="color:#0E0E0C;text-decoration:none;font-weight:600">' . $title . '</a>';
        }
        $qty = str_replace(' ', "\u{00A0}", bt_basket_qty($bi));
        $rows .= '<tr><td style="' . $cell . ';padding:12px 12px 12px 0">' . $title . '</td>'
            . '<td width="56" align="center" style="' . $cell . ';padding:12px 6px;white-space:nowrap">' . $qty . '</td>'
            . '<td align="right" style="' . $cell . ';padding:12px 0 12px 8px;white-space:nowrap;font-weight:700">'
            . (($lf = bt_basket_full($bi)) > $bi->getFinalPrice() + 0.5 ? '<del style="display:block;font-size:12px;font-weight:400;color:#6C6C64">' . bt_fmt($lf) . '</del>' : '')
            . bt_fmt($bi->getFinalPrice()) . '</td></tr>';
        $full += bt_basket_full($bi);
    }
    $disc = $full - (float)$order->getBasket()->getPrice();
    $dPrice = (float)$order->getDeliveryPrice();
    $dText = $dCode === 'bt_cdek' ? 'стоимость сообщит менеджер'
        : ($dPrice > 0 ? bt_fmt($dPrice) : 'бесплатно');
    $sum = 'font-family:Manrope,Arial,Helvetica,sans-serif;font-size:14px;color:#6C6C64;padding:10px 0 0';
    $total = bt_fmt($order->getPrice()) . ($dCode === 'bt_cdek' ? ' + доставка' : '');
    $right = fn(string $v, string $css = '') => '<td colspan="2" align="right" style="' . $sum . $css . '">' . $v . '</td>';
    $items = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px">'
        . '<tr><td style="' . $head . '">Товар</td><td align="center" style="' . $head . '">Кол-во</td><td align="right" style="' . $head . '">Сумма</td></tr>' . $rows
        . '<tr><td style="' . $sum . '">Товары</td>' . $right(bt_fmt($full), ';white-space:nowrap') . '</tr>'
        . ($disc > 0.5 ? '<tr><td style="' . $sum . '">Скидка</td>' . $right('−' . bt_fmt($disc), ';white-space:nowrap;color:#2F7D4A') . '</tr>' : '')
        . ($shipment ? '<tr><td style="' . $sum . '">Доставка</td>' . $right($dText) . '</tr>' : '')
        . '<tr><td valign="top" style="font-family:Manrope,Arial,Helvetica,sans-serif;font-size:16px;font-weight:700;color:#0E0E0C;padding:14px 0 0">' . ($dCode === 'bt_cdek' ? 'Итого без доставки' : 'Итого') . '</td>'
        . '<td colspan="2" align="right" style="font-family:Unbounded,\'Arial Black\',Arial,Helvetica,sans-serif;font-size:20px;font-weight:800;color:#0E0E0C;padding:14px 0 0;white-space:nowrap">' . bt_fmt($order->getPrice()) . '</td></tr></table>';

    $where = preg_replace(['/\s*#S\S+$/u', '/^Пункт выдачи СДЭК:\s*/u'], '', $props['ADDRESS'] ?? '') ?: ($props['PVZ'] ?? '' ?: ($dCode === 'bt_pickup' ? trim(($co['city'] ?? '') . ', ' . ($co['street'] ?? ''), ', ') : ''));
    $det = [];
    if ($shipment) {
        $det['Доставка'] = $e($shipment->getDeliveryName() . ($city ? ', ' . $city : ''));
    }
    if ($where !== '') {
        $label = $dCode === 'bt_pickup' ? 'Самовывоз' : (($props['PVZ'] ?? '') !== '' ? 'Пункт выдачи' : 'Адрес');
        $det[$label] = $e(($city && $dCode !== 'bt_pickup' ? $city . ', ' : '') . $where);
        if (($props['PVZ'] ?? '') !== '' && ($pvp = bt_pvz_point($props['PVZ'])) && $pvp['w'] !== '') {
            $det['Режим работы'] = $e($pvp['w']);
        }
    }
    if ($payment) {
        $det['Оплата'] = $e($payment->getPaymentSystemName());
    }
    if (($props['COMPANY'] ?? '') !== '') {
        $det['Покупатель'] = $e($props['COMPANY'] . (($props['INN'] ?? '') !== '' ? ', ИНН ' . $props['INN'] : ''));
    }
    $det['Получатель'] = $e(trim($name . ($phone ? ', ' . $phone : ''), ', '));
    if ($order->getField('USER_DESCRIPTION')) {
        $det['Комментарий'] = nl2br($e($order->getField('USER_DESCRIPTION')));
    }
    $details = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#EFEFE9" style="background-color:#EFEFE9;border-radius:12px;margin:22px 0 0">'
        . '<tr><td style="padding:18px 20px 8px">' . bt_mail_rows($det) . '</td></tr></table>';

    // что дальше — как на странице «Заказ принят»
    $track = bt_delivery_next($dCode);
    $next = [
        str_starts_with($track, 'Менеджер позвонит') ? '' : 'Менеджер позвонит в рабочее время' . (($co['hours'] ?? '') !== '' ? ' (' . $e($co['hours']) . ')' : '') . ', если потребуется уточнить детали.',
        $pCode === 'bill' ? 'Счёт пришлём на e‑mail после подтверждения заказа менеджером.'
            : ($pCode === 'tinkoff' ? 'Оплата онлайн. Если не успели оплатить, сделайте это на странице заказа в личном кабинете — там же появится кассовый чек.'
            : 'Оплата при получении заказа, чек выдадим при получении.'),
        'Изменить заказ можно до ' . ($dCode === 'bt_pickup' ? 'выдачи' : 'отправки') . ' — позвоните <a href="' . $e($co['phone1_href'] ?? '') . '" style="color:#0E0E0C;font-weight:700;white-space:nowrap">' . $e($co['phone1'] ?? '') . '</a>.',
        $track,
    ];
    $next = array_filter($next);
    $nextHtml = '';
    foreach ($next as $t) {
        $nextHtml .= '<tr><td width="22" valign="top" style="padding:8px 0 10px"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td width="9" height="9" bgcolor="#C7DA43" style="width:9px;height:9px;background-color:#C7DA43;border-radius:5px;font-size:0;line-height:0">&nbsp;</td></tr></table></td>'
            . '<td style="font-family:Manrope,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#0E0E0C;padding:0 0 10px">' . $t . '</td></tr>';
    }

    return [
        'BT_HELLO' => $name !== '' ? 'Здравствуйте, ' . $e($name) . '!' : 'Здравствуйте!',
        'BT_DATE' => FormatDate('j F Y', ($order->getDateInsert() ?: new \Bitrix\Main\Type\DateTime())->getTimestamp()),
        'BT_TOTAL' => $total,
        'BT_ORDER_URL' => $host . '/personal/orders/' . (int)$order->getId() . '/',
        'BT_ORDER' => $items . $details,
        'BT_NEXT' => '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $nextHtml . '</table>',
        'BT_TRACK' => in_array(bt_order_stage(['STATUS_ID' => $order->getField('STATUS_ID'), 'CANCELED' => $order->isCanceled() ? 'Y' : 'N', 'PAYED' => $order->isPaid() ? 'Y' : 'N']
            + (bt_order_ship_info([$order->getId()])[$order->getId()] ?? [])), ['ship', 'done', 'cancel'], true) ? '' : bt_delivery_next($dCode),
        'BT_TRACK_URL' => bt_track_url($dCode, $shipment ? (string)$shipment->getField('TRACKING_NUMBER') : ''),
        'BT_PICKUP_ADDR' => $e(trim(($co['city'] ?? '') . ', ' . ($co['street'] ?? ''), ', ')),
        'BT_PICKUP_HOURS' => $e($co['hours'] ?? ''),
        'BT_DCODE' => $dCode,
        'BT_PAID' => $order->isPaid() ? 'Y' : '',
        'BT_COD' => !$order->isPaid() && $pCode === 'cash' ? 'Y' : '',
        'BT_BILL_DUE' => !$order->isPaid() && $pCode === 'bill' ? 'Y' : '',
        'BT_PAY_URL' => !$order->isPaid() && !$order->isCanceled() && $pCode === 'tinkoff' ? $host . '/personal/order/pay/?id=' . (int)$order->getId() : '',
    ];
}

// «Собираем заказ» сразу после онлайн-оплаты не шлём: статус ставит сайт, а «Оплата получена» уже ушло
function bt_mail_skip_p($event, $lid, $fields)
{
    $id = (int)($fields['ORDER_REAL_ID'] ?? 0);
    if ($event !== 'SALE_STATUS_CHANGED_P' || $id <= 0 || !Loader::includeModule('sale') || !($order = Sale\Order::load($id))) {
        return true;
    }
    foreach ($order->getPaymentCollection() as $p) {
        $d = $p->getField('DATE_PAID');
        if ($p->isPaid() && !$p->isInner() && $d && $d->getTimestamp() > time() - 600) {
            return false;
        }
    }
    return true;
}

// лишние письма: «Передан в доставку» у СДЭК с трек-номером (уже ушло «Трек-номер заказа»)
// и «Оплата получена» у заказа «при получении» — деньги покупатель отдал сам
function bt_mail_skip_extra($event, $lid, $fields)
{
    $id = (int)($fields['ORDER_REAL_ID'] ?? 0);
    if (!in_array($event, ['SALE_STATUS_CHANGED_S', 'SALE_ORDER_PAID'], true) || $id <= 0 || !Loader::includeModule('sale')) {
        return true;
    }
    if ($event === 'SALE_ORDER_PAID') {
        return !bt_order_by_bill($id, 'cash');
    }
    $i = bt_order_ship_info([$id])[$id] ?? [];
    return !(str_starts_with($i['DCODE'] ?? '', 'sdek_') && ($i['TRACK'] ?? '') !== '');
}

// значения уже экранированы
function bt_mail_rows(array $rows): string
{
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
    foreach ($rows as $k => $v) {
        $html .= '<tr><td width="34%" valign="top" style="font-family:Manrope,Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:#6C6C64;padding:0 12px 10px 0">' . $k . '</td>'
            . '<td valign="top" style="font-family:Manrope,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#0E0E0C;padding:0 0 10px;word-break:break-word">' . $v . '</td></tr>';
    }
    return $html . '</table>';
}
