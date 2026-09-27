<?php
// Почтовые шаблоны сайта: оформление bt_mail (local/templates/bt_mail), тексты писем, выключение ненужных шаблонов решения.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_mail_setup.php [show|apply]. Повторный запуск ничего не дублирует, тексты обновляет.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");
const SITE = 's1';
const TPL = 'bt_mail';

// ---------- блоки письма (инлайн-стили, табличная вёрстка) ----------
const FONT = 'font-family:Manrope,Arial,Helvetica,sans-serif;';
const DISPLAY = "font-family:Unbounded,'Arial Black',Arial,Helvetica,sans-serif;";

function m_head(string $title, string $eyebrow = ''): string
{
    $eb = $eyebrow === '' ? '' : '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 14px"><tr>'
        . '<td bgcolor="#EDF4C4" style="background-color:#EDF4C4;border-radius:999px;padding:5px 12px;' . FONT . 'font-size:12px;font-weight:700;color:#0E0E0C;white-space:nowrap">' . $eyebrow . '</td></tr></table>';
    return $eb . '<h1 class="bt-h1" style="margin:0 0 16px;' . DISPLAY . 'font-size:26px;line-height:1.15;font-weight:800;text-transform:uppercase;letter-spacing:-0.3px;color:#0E0E0C">' . $title . "</h1>\n";
}

function m_p(string $html): string
{
    return '<p style="margin:0 0 14px;' . FONT . 'font-size:15px;line-height:1.6;color:#0E0E0C">' . $html . "</p>\n";
}

function m_note(string $html): string
{
    return '<p style="margin:18px 0 0;' . FONT . 'font-size:13px;line-height:1.55;color:#6C6C64">' . $html . "</p>\n";
}

function m_h2(string $t): string
{
    return '<h2 style="margin:28px 0 12px;' . DISPLAY . 'font-size:15px;line-height:1.2;font-weight:800;text-transform:uppercase;color:#0E0E0C">' . $t . "</h2>\n";
}

function m_btn(string $url, string $text): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 4px"><tr>'
        . '<td bgcolor="#D7E85C" style="background-color:#D7E85C;border-radius:999px">'
        . '<a href="' . $url . '" style="display:inline-block;padding:14px 28px;' . FONT . 'font-size:14px;font-weight:700;line-height:1.2;color:#0E0E0C;text-decoration:none;border-radius:999px">' . $text . ' &rarr;</a>'
        . "</td></tr></table>\n";
}

function m_box(string $html): string
{
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#EFEFE9" style="background-color:#EFEFE9;border-radius:12px;margin:6px 0 4px">'
        . '<tr><td style="padding:18px 20px 8px">' . $html . "</td></tr></table>\n";
}

// крупный код или трек-номер на тёмной плашке
function m_code(string $code, string $caption): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" bgcolor="#0E0E0C" style="background-color:#0E0E0C;border-radius:14px;margin:8px 0 18px"><tr>'
        . '<td style="padding:18px 28px 20px"><div style="' . FONT . 'font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#9C9C94;margin:0 0 6px">' . $caption . '</div>'
        . "<div style=\"font-family:'JetBrains Mono','Courier New',Courier,monospace;font-size:34px;line-height:1.1;font-weight:700;letter-spacing:6px;color:#D7E85C\">" . $code . "</div></td></tr></table>\n";
}

$rows = fn(array $r) => m_box(bt_mail_rows($r));
$hello = 'Здравствуйте<?=trim((string)$arParams["NAME"]) !== "" ? ", " . $arParams["HTML_NAME"] : ""?>!';
$orderBtn = m_btn('#BT_ORDER_URL#', 'Заказ в личном кабинете');
$cabinet = m_btn('https://#SERVER_NAME#/personal/', 'Войти в кабинет');
$phonesBelow = 'позвоните нам — телефоны внизу письма';
$ifText = fn(string $field, string $html) => '<?if (trim(strip_tags((string)$arParams["' . $field . '"])) !== ""):?>' . $html . '<?endif?>';

// ---------- письма: событие => [тема, кому, от кого, скрытая копия, текст] ----------
$sale = ['#EMAIL#', '#SALE_EMAIL#'];
$messages = [
    // главный модуль
    'NEW_USER' => ['Новый покупатель на сайте: #EMAIL#', '#DEFAULT_EMAIL_FROM#', '#DEFAULT_EMAIL_FROM#', '',
        m_head('Новый покупатель', 'Регистрация на сайте')
        . m_p('На сайте #SERVER_NAME# зарегистрировался покупатель.')
        . $rows(['Имя' => '#HTML_NAME# #HTML_LAST_NAME#', 'E-mail' => '#HTML_EMAIL#', 'Логин' => '#HTML_LOGIN#', 'ID' => '#USER_ID#'])
        . m_btn('https://#SERVER_NAME#/bitrix/admin/user_edit.php?ID=#USER_ID#&amp;lang=ru', 'Открыть в админке')],
    'USER_INFO' => ['Ваш личный кабинет BEVERTEAM', '#EMAIL#', '#DEFAULT_EMAIL_FROM#', '',
        m_head('Личный кабинет')
        . m_p($hello)
        . m_p('Вы — покупатель сайта #SERVER_NAME#. Пароль не нужен: в личный кабинет входите по e-mail <b>#HTML_EMAIL#</b>, мы пришлём код для входа.')
        . m_p('В кабинете — история заказов, адреса доставки и реквизиты компаний для счетов.')
        . $cabinet],
    'USER_PASS_REQUEST' => ['Смена пароля на сайте BEVERTEAM', '#EMAIL#', '#DEFAULT_EMAIL_FROM#', '',
        m_head('Смена пароля')
        . m_p($hello)
        . m_p('Для логина <b>#HTML_LOGIN#</b> запросили смену пароля. Если это были вы — задайте новый пароль по ссылке.')
        . m_btn('https://#SERVER_NAME#/bitrix/admin/index.php?change_password=yes&amp;lang=ru&amp;USER_CHECKWORD=#CHECKWORD#&amp;USER_LOGIN=#URL_LOGIN#', 'Задать новый пароль')
        . m_note('Если вы не запрашивали смену пароля, просто не отвечайте на это письмо — пароль останется прежним. Покупателям пароль не нужен: в личный кабинет входят по коду из письма.')],
    'USER_PASS_CHANGED' => ['Пароль на сайте BEVERTEAM изменён', '#EMAIL#', '#DEFAULT_EMAIL_FROM#', '',
        m_head('Пароль изменён')
        . m_p($hello)
        . m_p('Пароль для логина <b>#HTML_LOGIN#</b> на сайте #SERVER_NAME# изменён.')
        . m_note('Если это сделали не вы, ' . $phonesBelow . '.')],
    'USER_INVITE' => ['Приглашение в личный кабинет BEVERTEAM', '#EMAIL#', '#DEFAULT_EMAIL_FROM#', '',
        m_head('Добро пожаловать')
        . m_p($hello)
        . m_p('Мы добавили вас как покупателя сайта #SERVER_NAME#. Пароль не нужен: в личный кабинет входите по e-mail <b>#HTML_EMAIL#</b>, мы пришлём код для входа.')
        . m_p('В кабинете — история заказов, адреса доставки и реквизиты компаний для счетов.')
        . $cabinet],

    // свои события
    'BT_AUTH_CODE' => ['Код для входа: #CODE#', '#EMAIL_TO#', '#DEFAULT_EMAIL_FROM#', '',
        m_head('Код для входа')
        . m_p('Введите код на сайте #SERVER_NAME#, чтобы войти в личный кабинет.')
        . m_code('#CODE#', 'Ваш код')
        . m_p('Код действует <b>#TTL# минут</b>.')
        . m_note('Если вы не запрашивали код, просто не отвечайте на это письмо — без кода в кабинет не войти.')],
    'BT_FORM_REQUEST' => ['Заявка с сайта: #TOPIC#', '#EMAIL_TO#', '#DEFAULT_EMAIL_FROM#', '',
        m_head('#HTML_TOPIC#', 'Заявка с сайта')
        . $rows(['Имя' => '#HTML_CLIENT_NAME#', 'Телефон' => '<a href="tel:#PHONE#" style="color:#0E0E0C;font-weight:700;text-decoration:none;white-space:nowrap">#HTML_PHONE#</a>',
            'E-mail' => '#HTML_EMAIL#', 'Сообщение' => '#HTML_MESSAGE#', 'Страница' => '<a href="#PAGE#" style="color:#0E0E0C">#HTML_PAGE#</a>'])
        . m_btn('#ADMIN_URL#', 'Заявка в админке')
        . m_note('Заявка сохранена в админке: Контент → Заявки → Заявки с сайта.')],

    // магазин
    'SALE_NEW_ORDER' => ['Заказ № #ORDER_ID# принят', $sale[0], $sale[1], '#BCC#',
        m_head('Заказ принят', 'Заказ № #ORDER_ID# · #BT_DATE#')
        . m_p('#BT_HELLO# Спасибо за заказ — ниже его состав и детали.')
        . '#BT_ORDER#' . m_h2('Что дальше') . '#BT_NEXT#' . $orderBtn],
    'SALE_ORDER_PAID' => ['Заказ № #ORDER_ID# оплачен', $sale[0], $sale[1], '#BCC#',
        m_head('Оплата получена', 'Заказ № #ORDER_ID#')
        . m_p('#BT_HELLO# Оплата по заказу № #ORDER_ID# поступила, спасибо. Статус и трек-номер сообщит менеджер.')
        . '#BT_ORDER#' . $orderBtn],
    'SALE_ORDER_CANCEL' => ['Заказ № #ORDER_ID# отменён', $sale[0], $sale[1], '#BCC#',
        m_head('Заказ отменён', 'Заказ № #ORDER_ID#')
        . m_p('#BT_HELLO# Заказ № #ORDER_ID# от #BT_DATE# отменён.')
        . $ifText('ORDER_CANCEL_DESCRIPTION', $rows(['Причина' => '#HTML_ORDER_CANCEL_DESCRIPTION#']))
        . m_p('Если заказ отменён по ошибке или остались вопросы, ' . $phonesBelow . '.')
        . '#BT_ORDER#' . $orderBtn],
    'SALE_ORDER_DELIVERY' => ['Заказ № #ORDER_ID# готовим к отправке', $sale[0], $sale[1], '#BCC#',
        m_head('Готовим к отправке', 'Заказ № #ORDER_ID#')
        . m_p('#BT_HELLO# Заказ № #ORDER_ID# подтверждён к отправке. Статус и трек-номер сообщит менеджер.')
        . '#BT_ORDER#' . $orderBtn],
    'SALE_ORDER_REMIND_PAYMENT' => ['Напоминаем об оплате заказа № #ORDER_ID#', $sale[0], $sale[1], '#BCC#',
        m_head('Заказ ждёт оплаты', 'Заказ № #ORDER_ID#')
        . m_p('#BT_HELLO# Заказ № #ORDER_ID# от #BT_DATE# на сумму <b style="white-space:nowrap">#BT_TOTAL#</b> пока не оплачен.')
        . m_p('Если вы уже оплатили заказ, просто не отвечайте на это письмо. Вопросы по оплате — по телефонам внизу письма.')
        . '#BT_ORDER#' . $orderBtn],
    'SALE_ORDER_TRACKING_NUMBER' => ['Трек-номер заказа № #ORDER_ID#', $sale[0], $sale[1], '#BCC#',
        m_head('Заказ в пути', 'Заказ № #ORDER_ID#')
        . m_p('#BT_HELLO# Заказ № #ORDER_ID# передан в доставку. Отследить его можно на сайте службы доставки по трек-номеру:')
        . m_code('#ORDER_TRACKING_NUMBER#', 'Трек-номер')
        . '#BT_ORDER#' . $orderBtn],
    'SALE_ORDER_SHIPMENT_STATUS_CHANGED' => ['Заказ № #ORDER_NO#: #STATUS_NAME#', $sale[0], $sale[1], '#BCC#',
        m_head('#HTML_STATUS_NAME#', 'Заказ № #ORDER_NO#')
        . m_p('Здравствуйте! Служба доставки #HTML_DELIVERY_NAME# обновила статус отправления по заказу № #ORDER_NO#.')
        . $rows(['Статус' => '#HTML_STATUS_NAME#', 'Подробности' => '#HTML_STATUS_DESCRIPTION#', 'Трек-номер' => '#HTML_TRACKING_NUMBER#'])
        . $ifText('DELIVERY_TRACKING_URL', m_btn('#DELIVERY_TRACKING_URL#', 'Отследить отправление'))
        . m_note('История заказов — в <a href="https://#SERVER_NAME#/personal/orders/" style="color:#6C6C64">личном кабинете</a>.')],
    'SALE_STATUS_CHANGED_P' => ['Заказ № #ORDER_ID# оплачен и собирается', $sale[0], $sale[1], '',
        m_head('Собираем заказ', 'Заказ № #ORDER_ID#')
        . m_p('#BT_HELLO# Заказ № #ORDER_ID# оплачен и формируется к отправке. Статус и трек-номер сообщит менеджер.')
        . $ifText('TEXT', m_p('#HTML_TEXT#'))
        . '#BT_ORDER#' . $orderBtn],
    'SALE_STATUS_CHANGED_F' => ['Заказ № #ORDER_ID# выполнен', $sale[0], $sale[1], '',
        m_head('Заказ выполнен', 'Заказ № #ORDER_ID#')
        . m_p('#BT_HELLO# Заказ № #ORDER_ID# доставлен и оплачен. Спасибо, что выбрали BEVERTEAM!')
        . $ifText('TEXT', m_p('#HTML_TEXT#'))
        . m_p('Повторить заказ можно в личном кабинете.')
        . '#BT_ORDER#' . $orderBtn],
    'SALE_CHECK_PRINT' => ['Кассовый чек по заказу № #ORDER_ID#', $sale[0], $sale[1], '#BCC#',
        m_head('Кассовый чек', 'Заказ № #ORDER_ID#')
        . m_p('Здравствуйте! По заказу № #ORDER_ID# от #ORDER_DATE# сформирован кассовый чек — он доступен по ссылке.')
        . m_btn('#CHECK_LINK#', 'Открыть чек')
        . m_note('История заказов — в <a href="https://#SERVER_NAME#/personal/orders/" style="color:#6C6C64">личном кабинете</a>.')],
    'SALE_CHECK_PRINT_ERROR' => ['Ошибка печати чека по заказу № #ORDER_ACCOUNT_NUMBER#', $sale[0], $sale[1], '',
        m_head('Чек не напечатан', 'Заказ № #ORDER_ACCOUNT_NUMBER#')
        . m_p('Чек № #CHECK_ID# по заказу № #ORDER_ACCOUNT_NUMBER# от #ORDER_DATE# не удалось напечатать. Проверьте кассу и чек в заказе.')
        . m_btn('https://#SERVER_NAME#/bitrix/admin/sale_order_view.php?ID=#ORDER_ID#&amp;lang=ru', 'Заказ в админке')],
    'SALE_CHECK_VALIDATION_ERROR' => ['Чек по заказу № #ORDER_ACCOUNT_NUMBER# не сформирован', $sale[0], $sale[1], '',
        m_head('Чек не сформирован', 'Заказ № #ORDER_ACCOUNT_NUMBER#')
        . m_p('Чек по заказу № #ORDER_ACCOUNT_NUMBER# от #ORDER_DATE# не сформирован. Проверьте данные заказа и настройки кассы.')
        . m_btn('https://#SERVER_NAME#/bitrix/admin/sale_order_view.php?ID=#ORDER_ID#&amp;lang=ru', 'Заказ в админке')],
];

// не нужны магазину: блог, форум, опросы, рассылки, подписка на товар, продление подписки, форма обратной связи main.feedback,
// подтверждение регистрации и вход по коду ядра (на сайте свой вход BT_AUTH_CODE), «вход с нового устройства» (у покупателей нет пароля),
// «статус: принят» — дублирует письмо о новом заказе
$off = ['FEEDBACK_FORM', 'NEW_USER_CONFIRM', 'USER_CODE_REQUEST', 'NEW_DEVICE_LOGIN', 'SALE_STATUS_CHANGED_N', 'SALE_SUBSCRIBE_PRODUCT',
    'SALE_NEW_ORDER_RECURRING', 'SALE_RECURRING_CANCEL', 'CATALOG_PRODUCT_SUBSCRIBE_LIST_CONFIRM', 'CATALOG_PRODUCT_SUBSCRIBE_NOTIFY',
    'CATALOG_PRODUCT_SUBSCRIBE_NOTIFY_REPEATED', 'SENDER_SUBSCRIBE_CONFIRM', 'SUBSCRIBE_CONFIRM', 'VOTE_FOR'];
$offPrefix = ['NEW_BLOG_', 'BLOG_', 'NEW_FORUM_', 'EDIT_FORUM_', 'FORUM_'];

// ---------- применение ----------
$list = [];
$r = CEventMessage::GetList('id', 'asc', ['SITE_ID' => SITE]);
while ($m = $r->Fetch()) {
    $list[$m['EVENT_NAME']][] = $m;
}
$em = new CEventMessage();
$upd = function (array $m, array $f, string $what) use ($apply, $say, $fail, $em) {
    $say("  #{$m['ID']} {$m['EVENT_NAME']}: $what");
    if ($apply) {
        $em->Update($m['ID'], $f) or $fail("update #{$m['ID']}: " . $em->LAST_ERROR);
    }
};

$say('Письма в оформлении ' . TPL . ':');
foreach ($messages as $event => [$subject, $to, $from, $bcc, $body]) {
    if (empty($list[$event])) {
        $say("  $event: шаблона нет — пропуск");
        continue;
    }
    $main = array_shift($list[$event]);
    $f = ['ACTIVE' => 'Y', 'SUBJECT' => $subject, 'EMAIL_TO' => $to, 'EMAIL_FROM' => $from, 'BCC' => $bcc, 'BODY_TYPE' => 'html',
        'SITE_TEMPLATE_ID' => TPL, 'MESSAGE' => $body];
    $diff = array_keys(array_filter($f, fn($v, $k) => (string)$main[$k] !== $v, ARRAY_FILTER_USE_BOTH));
    $diff ? $upd($main, $f, 'обновить ' . implode(', ', $diff)) : $say("  #{$main['ID']} $event: без изменений");
    foreach ($list[$event] as $dup) {
        $dup['ACTIVE'] === 'Y' and $upd($dup, ['ACTIVE' => 'N'], 'выключить дубль');
    }
    unset($list[$event]);
}

$say('Выключить:');
foreach ($list as $event => $items) {
    $dupOfMain = count($items) > 1 && !in_array($event, $off, true);
    foreach ($items as $i => $m) {
        $need = in_array($event, $off, true) || array_filter($offPrefix, fn($p) => str_starts_with($event, $p)) || ($dupOfMain && $i > 0);
        if ($need && $m['ACTIVE'] === 'Y') {
            $upd($m, ['ACTIVE' => 'N'], $dupOfMain && $i > 0 ? 'выключить дубль' : 'выключить');
        }
    }
}
echo "done\n";
