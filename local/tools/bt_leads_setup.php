<?php
// Заявки с сайта: папка на каждую форму и кнопку (код папки = код цели Метрики), статус обработки, колонки списка в админке.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_leads_setup.php [show|apply]
if (PHP_SAPI !== 'cli') {
    die();
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn($s) => print(($apply ? '' : '[show] ') . $s . "\n");
$ibId = (int)bt_iblock('form_requests') or die("нет ИБ form_requests\n");

// порядок — как формы идут на сайте
$folders = [
    'bt_lead_degustaciya' => 'Главная: заказать дегустацию',
    'bt_lead_predlozhenie' => 'Главная: персональное предложение',
    'bt_lead_cfg' => 'Главная: подбор кофемашины',
    'bt_lead_contact' => 'Главная: форма внизу страницы',
    'bt_lead_one_click' => 'Товар: купить в один клик',
    'bt_lead_podbor' => 'Подбор кофе',
    'bt_lead_arenda' => 'Аренда кофемашин',
    'bt_lead_ofis' => 'Кофе в офис',
    'bt_lead_servis' => 'Ремонт: вызов инженера',
    'bt_lead_about' => 'О компании',
    'bt_lead_write' => 'Контакты: напишите нам',
    'bt_lead_other' => 'Окно «Оставить заявку»',
    'bt_order_question' => 'Кабинет: вопрос по заказу',
    'bt_order_cancel' => 'Кабинет: отмена заказа',
];
$have = [];
$r = CIBlockSection::GetList([], ['IBLOCK_ID' => $ibId, 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'CODE', 'NAME', 'SORT']);
while ($s = $r->Fetch()) {
    $have[$s['CODE']] = $s;
}
// список заявок в админке — «новые сверху» (по ID), этот порядок действует и на папки: создаём с конца, чтобы первая папка получила больший ID
$sorts = array_combine(array_keys($folders), range(10, 10 * count($folders), 10));
foreach (array_reverse($folders, true) as $code => $name) {
    $sort = $sorts[$code];
    $s = $have[$code] ?? null;
    if (!$s) {
        $say("папка «{$name}»");
        $apply and ((new CIBlockSection())->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'ACTIVE' => 'Y']) or die("папка $code\n"));
    } elseif ($s['NAME'] !== $name || (int)$s['SORT'] !== $sort) {
        $say("папка $code → «{$name}», сортировка $sort");
        $apply and (new CIBlockSection())->Update($s['ID'], ['NAME' => $name, 'SORT' => $sort]);
    }
}

// папки объединённых форм (одна форма на нескольких страницах): пустые удаляем, с заявками — оставляем и сообщаем
foreach (array_diff_key($have, $folders) as $code => $s) {
    if (CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, 'SECTION_ID' => $s['ID'], 'CHECK_PERMISSIONS' => 'N'], [])) {
        echo "папка «{$s['NAME']}» не пустая — перенесите заявки и удалите вручную
";
        continue;
    }
    $say("удалить пустую папку «{$s['NAME']}»");
    $apply and CIBlockSection::Delete($s['ID'], false);
}

// названия папок в меню админки и общий список: папки сверху, заявки внутри
$ib = CIBlock::GetList([], ['ID' => $ibId, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
$want = ['SECTIONS_NAME' => 'Папки', 'SECTION_NAME' => 'Папка', 'LIST_MODE' => 'C'];
if (array_diff_assoc($want, CIBlock::GetMessages($ibId) + ['LIST_MODE' => $ib['LIST_MODE']])) {
    $say('ИБ: папки в меню, общий список');
    $apply and (new CIBlock())->Update($ibId, $want);
}

// статус обработки; новая заявка получает «Новая» (form.php)
$st = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'STATUS'])->Fetch();
if (!$st) {
    $say('свойство STATUS «Статус»: Новая / В работе / Закрыта');
    if ($apply) {
        (new CIBlockProperty())->Add(['IBLOCK_ID' => $ibId, 'CODE' => 'STATUS', 'NAME' => 'Статус', 'SORT' => 5, 'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'L', 'ACTIVE' => 'Y',
            'VALUES' => [['XML_ID' => 'new', 'VALUE' => 'Новая', 'SORT' => 10, 'DEF' => 'Y'], ['XML_ID' => 'work', 'VALUE' => 'В работе', 'SORT' => 20], ['XML_ID' => 'done', 'VALUE' => 'Закрыта', 'SORT' => 30]]])
            or die("STATUS\n");
        $st = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'STATUS'])->Fetch();
    }
}

// колонки и порядок списка: новые сверху, статус и телефон видны сразу (общая настройка и у каждого пользователя)
if ($st) {
    $pid = fn($code) => (int)(CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => $code])->Fetch()['ID'] ?? 0);
    $cols = implode(',', ['NAME', 'PROPERTY_' . $st['ID'], 'PROPERTY_' . $pid('CLIENT_NAME'), 'PROPERTY_' . $pid('PHONE'), 'DATE_CREATE', 'ID']);
    $say("список в админке: колонки $cols, новые сверху");
    if ($apply) {
        foreach (['tbl_iblock_list_', 'tbl_iblock_element_'] as $prefix) {
            $gridId = $prefix . md5($ib['IBLOCK_TYPE_ID'] . '.' . $ibId);
            $set = function (array $o) use ($cols) {
                $o['views']['default']['columns'] = $cols;
                $o['views']['default']['last_sort_by'] = 'ID';
                $o['views']['default']['last_sort_order'] = 'desc';
                $o['current_view'] ??= 'default';
                return $o;
            };
            CUserOptions::SetOption('main.interface.grid', $gridId, $set(CUserOptions::GetOption('main.interface.grid', $gridId, [], 0) ?: []), true);
            $r = \Bitrix\Main\UserTable::getList(['filter' => ['=ACTIVE' => 'Y'], 'select' => ['ID']]);
            while ($u = $r->fetch()) {
                CUserOptions::SetOption('main.interface.grid', $gridId, $set(CUserOptions::GetOption('main.interface.grid', $gridId, [], $u['ID']) ?: []), false, $u['ID']);
            }
        }
    }
}
echo "готово\n";
