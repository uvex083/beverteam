<?php
// Инфоблоки контентных страниц: «Главная» (блок страницы = инфоблок), «О компании», «Контакты»; стартовое наполнение из реальных данных.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_pages_setup.php [show|apply] [папка выгрузки]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

Loader::includeModule('iblock');
$apply = ($argv[1] ?? 'show') === 'apply';
$dir = rtrim($argv[2] ?? (getenv('HOME') . '/import'), '/') . '/';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

// ---------- типы ИБ: название = страница сайта, сортировка = порядок в дереве «Контент» ----------
$types = ['main' => ['Главная', 5], 'contacts' => ['Контакты', 50]];
foreach ($types as $id => [$name, $sort]) {
    $t = CIBlockType::GetByIDLang($id, 'ru');
    if ($t && $t['NAME'] === $name && (int)$t['SORT'] === $sort) {
        continue;
    }
    $say("тип ИБ $id → «{$name}»");
    if ($apply) {
        $f = ['SORT' => $sort, 'LANG' => ['ru' => ['NAME' => $name, 'ELEMENT_NAME' => 'Элемент', 'SECTION_NAME' => 'Раздел']]];
        $o = new CIBlockType();
        ($t ? $o->Update($id, $f) : $o->Add(['ID' => $id, 'SECTIONS' => 'N', 'IN_RSS' => 'N'] + $f)) or $fail("type $id");
    }
}

// единый набор полей блока страницы (картинка — картинка анонса элемента)
$blockProps = [
    'TITLE' => ['Заголовок', 'S', ['ROW_COUNT' => 3, 'HINT' => 'Перенос строки в поле = перенос строки на сайте']],
    'SUBTITLE' => ['Подзаголовок', 'S', ['ROW_COUNT' => 3]],
    'TEXT' => ['Текст', 'S:HTML', []],
    'CAPTION' => ['Подпись', 'S', []],
    'BTN_TEXT' => ['Текст кнопки', 'S', []],
    'BTN_LINK' => ['Ссылка кнопки', 'S', ['HINT' => 'Чтобы кнопка открывала форму заявки, укажите #zayavka']],
    'BTN2_TEXT' => ['Текст второй кнопки', 'S', []],
    'BTN2_LINK' => ['Ссылка второй кнопки', 'S', ['HINT' => 'Чтобы кнопка открывала форму заявки, укажите #zayavka']],
    'ITEMS' => ['Пункты списка', 'S', ['MULTIPLE' => 'Y', 'WITH_DESCRIPTION' => 'Y', 'MULTIPLE_CNT' => 3, 'HINT' => 'Слева — текст пункта, в поле «описание» — значение справа (цена, срок) или пояснение']],
    'VIDEO' => ['Видео', 'F', ['FILE_TYPE' => 'mp4, webm']],
];
// карточки списковых блоков: название, текст анонса, картинка анонса + иконка
$iconProp = ['ICON' => ['Иконка (файл SVG или PNG)', 'F', ['FILE_TYPE' => 'svg, png']]];

function bt_ib_ensure(array $f, array $props, bool $apply, callable $say, callable $fail): int
{
    $ib = CIBlock::GetList([], ['=CODE' => $f['CODE'], 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    $id = (int)($ib['ID'] ?? 0);
    if (!$id) {
        $say("создать ИБ {$f['CODE']} «{$f['NAME']}»");
        if ($apply) {
            $o = new CIBlock();
            $id = (int)$o->Add($f + ['SITE_ID' => ['s1'], 'ACTIVE' => 'Y', 'GROUP_ID' => ['2' => 'R'], 'VERSION' => 2, 'INDEX_ELEMENT' => 'N', 'WORKFLOW' => 'N'])
                or $fail("iblock {$f['CODE']}: {$o->LAST_ERROR}");
        }
    } elseif ($ib['NAME'] !== $f['NAME'] || (int)$ib['SORT'] !== (int)$f['SORT'] || $ib['IBLOCK_TYPE_ID'] !== $f['IBLOCK_TYPE_ID']) {
        $say("ИБ {$f['CODE']} → «{$f['NAME']}», сортировка {$f['SORT']}");
        $apply and (new CIBlock())->Update($id, ['NAME' => $f['NAME'], 'SORT' => $f['SORT'], 'IBLOCK_TYPE_ID' => $f['IBLOCK_TYPE_ID']]);
    }
    $have = [];
    if ($id) {
        $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $id]);
        while ($p = $r->Fetch()) {
            $have[$p['CODE']] = 1;
        }
    }
    $sort = 100;
    foreach ($props as $code => [$name, $type, $extra]) {
        $sort += 10;
        if (isset($have[$code])) {
            continue;
        }
        $say("  свойство $code «{$name}»");
        if ($apply) {
            [$pt, $ut] = array_pad(explode(':', $type), 2, null);
            $bp = new CIBlockProperty();
            $bp->Add(['IBLOCK_ID' => $id, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'PROPERTY_TYPE' => $pt, 'USER_TYPE' => $ut, 'ACTIVE' => 'Y'] + $extra)
                or $fail("prop $code: {$bp->LAST_ERROR}");
        }
    }
    return $id;
}

// Список элементов в админке — по SORT asc, как блоки идут на сайте
function bt_grid_sort(string $type, int $ibId): void
{
    foreach (['tbl_iblock_list_', 'tbl_iblock_element_'] as $prefix) {
        $gridId = $prefix . md5($type . '.' . $ibId);
        $opt = CUserOptions::GetOption('main.interface.grid', $gridId, [], 0) ?: [];
        $opt['views']['default']['last_sort_by'] = 'SORT';
        $opt['views']['default']['last_sort_order'] = 'asc';
        $opt['current_view'] ??= 'default';
        CUserOptions::SetOption('main.interface.grid', $gridId, $opt, true);
        $r = \Bitrix\Main\UserTable::getList(['filter' => ['=ACTIVE' => 'Y'], 'select' => ['ID']]);
        while ($u = $r->fetch()) {
            $uo = CUserOptions::GetOption('main.interface.grid', $gridId, [], $u['ID']) ?: [];
            $uo['views']['default']['last_sort_by'] = 'SORT';
            $uo['views']['default']['last_sort_order'] = 'asc';
            $uo['current_view'] ??= 'default';
            CUserOptions::SetOption('main.interface.grid', $gridId, $uo, false, $u['ID']);
        }
    }
}

// Элемент по коду (одиночный блок) или по названию (карточка списка); существующий не трогаем
function bt_el_seed(int $ibId, string $code, array $fields, array $props, callable $say, callable $fail): void
{
    $filter = ['IBLOCK_ID' => $ibId] + ($code !== '' ? ['=CODE' => $code] : ['=NAME' => $fields['NAME']]);
    if (CIBlockElement::GetList([], $filter, false, false, ['ID'])->Fetch()) {
        return;
    }
    $say("  + {$fields['NAME']}");
    $o = new CIBlockElement();
    $fields = array_filter($fields, fn($v) => $v !== null);
    $id = (int)$o->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code ?: false, 'ACTIVE' => 'Y'] + $fields) or $fail("element {$fields['NAME']}: {$o->LAST_ERROR}");
    if ($props) {
        foreach ($props as $k => $v) {
            if ($k === 'TEXT' && is_string($v)) {
                $props[$k] = ['VALUE' => ['TEXT' => $v, 'TYPE' => 'HTML']];
            } elseif ($k === 'ITEMS') {
                $props[$k] = array_map(fn($x) => ['VALUE' => $x[0], 'DESCRIPTION' => $x[1] ?? ''], $v);
            }
        }
        CIBlockElement::SetPropertyValuesEx($id, $ibId, $props);
    }
}

// файл иконки из набора шаблона — во временный svg
function bt_icon_file(string $name): array
{
    $tmp = CTempFile::GetFileName($name . '.svg');
    CheckDirPath($tmp);
    file_put_contents($tmp, preg_replace('/ aria-hidden="true" focusable="false"/', ' xmlns="http://www.w3.org/2000/svg"', bt_icon($name)));
    return CFile::MakeFileArray($tmp);
}

// картинка анонса товара/аренды — копия файла для блока
function bt_pic_of(string $ibCode, string $elCode): ?array
{
    $el = CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock($ibCode), '=CODE' => $elCode], false, false, ['PREVIEW_PICTURE', 'DETAIL_PICTURE'])->Fetch();
    $fid = $el ? ($el['DETAIL_PICTURE'] ?: $el['PREVIEW_PICTURE']) : 0;
    return $fid ? CFile::MakeFileArray($fid) : null;
}

$ibs = [];
$def = [
    // код => [тип, название, сортировка, одиночный блок?, доп. свойства]
    'main_hero' => ['main', 'Первый экран', 10, true, [
        'HIGHLIGHT' => ['Выделить в заголовке (часть текста)', 'S', []],
        'BTN3_TEXT' => ['Текст третьей кнопки', 'S', []], 'BTN3_LINK' => ['Ссылка третьей кнопки', 'S', ['HINT' => 'Чтобы кнопка открывала форму заявки, укажите #zayavka']],
        'LINK_TEXT' => ['Текст ссылки под кнопками', 'S', []], 'LINK_URL' => ['Адрес ссылки под кнопками', 'S', []],
    ]],
    'main_facts' => ['main', 'Первый экран: цифры', 20, false, []],
    'main_config' => ['main', 'Первый экран: подбор кофемашины', 30, true, []],
    'main_incl' => ['main', 'Первый экран: что входит в аренду', 40, true, []],
    'main_strip' => ['main', 'Полоса преимуществ', 50, false, $iconProp],
    'main_utp' => ['main', 'Почему BEVERTEAM: заголовок', 60, true, []],
    'main_utp_items' => ['main', 'Почему BEVERTEAM: карточки', 70, false, $iconProp],
    'main_catalog' => ['main', 'Каталог', 80, true, []],
    'main_rent' => ['main', 'Аренда и продажа кофемашин', 90, true, []],
    'main_service' => ['main', 'Сервисный центр', 100, true, ['HIGHLIGHT' => ['Выделить в заголовке (часть текста)', 'S', []]]],
    'main_bean' => ['main', 'Зерно месяца', 110, true, ['PRODUCT' => ['Товар', 'E', ['LINK_IBLOCK_ID' => bt_iblock('catalog')]]]],
    'main_steps' => ['main', 'Как проходит аренда', 120, true, []],
    'main_reviews' => ['main', 'Отзывы', 130, true, []],
    'main_sub' => ['main', 'Кофе по подписке', 140, true, ['HIGHLIGHT' => ['Выделить в заголовке (часть текста)', 'S', []]]],
    'main_about' => ['main', 'О магазине и форма заявки', 150, true, []],
    'main_journal' => ['main', 'Журнал', 160, true, []],
    'main_seo' => ['main', 'SEO-текст', 170, true, []],
    'about_intro' => ['about', 'О компании: описание', 5, true, []],
    'about_work' => ['about', 'О компании: чем занимаемся', 6, true, []],
    'about_team' => ['about', 'О компании: команда', 7, false, []],
    'about_cert' => ['about', 'О компании: сертификат', 8, true, []],
    'contacts_how' => ['contacts', 'Как добраться', 10, false, []],
    'contacts_photos' => ['contacts', 'Фото офиса и склада', 20, false, []],
];
foreach ($def as $code => [$type, $name, $sort, $single, $extra]) {
    $ibs[$code] = bt_ib_ensure(
        ['IBLOCK_TYPE_ID' => $type, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort],
        $single ? $blockProps + $extra : $extra, $apply, $say, $fail
    );
    if ($apply && $ibs[$code]) {
        bt_grid_sort($type, $ibs[$code]);
    }
}
// отзывы после блоков «О компании»
$rev = CIBlock::GetList([], ['=CODE' => 'reviews', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
if ($rev && (int)$rev['SORT'] !== 30) {
    $say('ИБ reviews → сортировка 30');
    $apply and (new CIBlock())->Update($rev['ID'], ['SORT' => 30]);
}

if (!$apply) {
    echo "done (show: контент переносится только в apply)\n";
    return;
}

// ---------- стартовое наполнение: только реальные факты со старого сайта и из каталога ----------
$lead = '#zayavka';
$one = fn(string $code, array $p, array $f = []) => bt_el_seed($ibs[$code], 'main', ['NAME' => $def[$code][1]] + $f, $p, $say, $fail);
$list = function (string $code, array $items) use ($ibs, $say, $fail) {
    foreach ($items as $i => $it) {
        bt_el_seed($ibs[$code], '', ['NAME' => $it[0], 'SORT' => ($i + 1) * 10, 'PREVIEW_TEXT' => $it[1] ?? ''] + ($it[2] ?? []), [] + ($it[3] ?? []), $say, $fail);
    }
};

$one('main_hero', [
    'CAPTION' => 'Екатеринбург · на рынке с 2010 года',
    'TITLE' => "Кофемашина\nв офис и кафе\nот 3 500 ₽ в месяц", 'HIGHLIGHT' => 'от 3 500 ₽',
    'SUBTITLE' => 'Привозим, устанавливаем и обслуживаем. Сервисное обслуживание входит в стоимость аренды. Свой сервисный центр Jetinno в Екатеринбурге.',
    'BTN_TEXT' => 'Рассчитать аренду', 'BTN_LINK' => '/arenda-kofemashin/',
    'BTN2_TEXT' => 'Заказать дегустацию', 'BTN2_LINK' => $lead,
    'BTN3_TEXT' => 'Получить персональное предложение', 'BTN3_LINK' => $lead,
    'LINK_TEXT' => 'Смотреть каталог →', 'LINK_URL' => '/magazin/',
]);
$list('main_facts', [
    ['16 лет', 'работаем с кофе и оборудованием'],
    ['от 3 кг', 'кофе в месяц — аренда кофемашины 0 ₽'],
    ['от 3 000 ₽', 'бесплатная доставка по Екатеринбургу'],
]);
$one('main_config', [
    'TITLE' => 'Куда нужна кофемашина?', 'SUBTITLE' => 'Подберём модель и покажем цену аренды',
    'BTN_TEXT' => 'Взять в аренду', 'BTN_LINK' => '/arenda-kofemashin/',
    'ITEMS' => [['На мероприятие', 'Подберём модель под формат и число гостей. Стоимость — по запросу']],
]);
$one('main_incl', [
    'TITLE' => 'Входит в аренду',
    'ITEMS' => [['Сервисное обслуживание кофемашины'], ['Подбор модели под нагрузку и набор напитков'], ['Настройка рецептур под ваши пожелания'],
        ['Консультация специалиста при неисправности']],
    'CAPTION' => 'Свой сервисный центр Jetinno в Екатеринбурге', 'BTN_TEXT' => 'Отзывы →', 'BTN_LINK' => '/otzyvy-o-nas/',
]);
$list('main_strip', [
    ['Авторизованный сервисный центр Jetinno', '', [], ['ICON' => bt_icon_file('uTool')]],
    ['Сервисное обслуживание входит в аренду', '', [], ['ICON' => bt_icon_file('uSwap')]],
    ['Кофе BOTANICA и чай — привозим вместе с машиной', '', [], ['ICON' => bt_icon_file('uTruck')]],
    ['Договор с ИП и юрлицами, оплата по счёту', '', [], ['ICON' => bt_icon_file('uPrice')]],
]);
$one('main_utp', ['TITLE' => 'Почему BEVERTEAM', 'SUBTITLE' => 'Чем работа с нами отличается от покупки кофемашины «в коробке» и разовой закупки зерна.']);
$list('main_utp_items', [
    ['Аренда кофемашины бесплатно', 'Не платите за аппарат — при заказе от 3 кг кофе в месяц аренда 0 ₽.', [], ['ICON' => bt_icon_file('uRent')]],
    ['Опт на индивидуальных условиях', 'Гибкая система скидок для ресторанов, кафе и офисов, отсрочка платежа постоянным клиентам.', [], ['ICON' => bt_icon_file('uPrice')]],
    ['Бесплатная доставка от 3 000 ₽', 'По Екатеринбургу — на следующий день после заказа, в регионы — СДЭК.', [], ['ICON' => bt_icon_file('uTruck')]],
    ['Свой сервисный центр', 'Авторизованный сервис Jetinno в Екатеринбурге: ремонт и обслуживание в офисе, кафе и на дому.', [], ['ICON' => bt_icon_file('uTool')]],
]);
$one('main_catalog', [
    'TITLE' => 'Каталог', 'SUBTITLE' => 'Кофе собственной обжарки BOTANICA, чай, кофемашины и аксессуары. Бесплатная доставка по Екатеринбургу от 3 000 ₽.',
    'BTN_TEXT' => 'Весь каталог →', 'BTN_LINK' => '/magazin/',
]);
$one('main_rent', [
    'TITLE' => 'Аренда и продажа кофемашин', 'SUBTITLE' => 'Одни и те же модели можно арендовать или купить. В аренде сервисное обслуживание уже включено.',
    'BTN_TEXT' => 'Перейти в раздел «Аренда кофемашин»', 'BTN_LINK' => '/arenda-kofemashin/',
    'BTN2_TEXT' => 'Перейти в раздел «Продажа кофемашин»', 'BTN2_LINK' => '/magazin/professionalnye-kofemashiny/',
]);
$one('main_service', [
    'CAPTION' => 'Сервисный центр', 'TITLE' => "Сервис кофемашин\nJetinno", 'HIGHLIGHT' => 'Jetinno',
    'SUBTITLE' => 'Ремонт и обслуживание автоматических кофемашин в офисах, кафе и на дому.',
    'ITEMS' => [['Плановое ТО и чистка молочной системы', 'от 2 500 ₽'], ['Ремонт с заменой узлов', 'по смете'], ['Выезд инженера по Екатеринбургу', 'по заявке']],
    'BTN_TEXT' => 'Услуги и прайс', 'BTN_LINK' => '/servis/#price', 'BTN2_TEXT' => 'Перейти в раздел «Сервис»', 'BTN2_LINK' => '/servis/',
], ['PREVIEW_PICTURE' => bt_pic_of('catalog', 'jetinno-jl36')]);
$oromia = CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock('catalog'), '=CODE' => 'botanica-efiopiya-oromiya'], false, false, ['ID'])->Fetch();
$one('main_bean', [
    'CAPTION' => 'Продажа кофе · зерно месяца', 'TITLE' => "Эфиопия\nОромия",
    'SUBTITLE' => 'Сладкий кофе с нотами чёрного чая, сухофруктов, лимона и шоколадным послевкусием. Подходит для любых кофемашин.',
    'PRODUCT' => $oromia['ID'] ?? false, 'BTN2_TEXT' => 'Перейти в раздел «Кофе»', 'BTN2_LINK' => '/magazin/kofe/',
]);
$one('main_steps', [
    'TITLE' => 'Как проходит аренда',
    'ITEMS' => [
        ['Заявка', 'Оставьте заявку или позвоните — менеджер свяжется в течение 5 минут в рабочее время и уточнит место установки, число чашек в день и нужные напитки.'],
        ['Подбор и договор', 'Подберём модель под нагрузку, согласуем ежемесячную стоимость и формат: фиксированная оплата или кофемашина при заказе кофе.'],
        ['Установка', 'Согласуем доставку и установку, проверим подачу воды и место, настроим рецептуры под ваши пожелания.'],
        ['Обслуживание', 'Сервисное обслуживание входит в аренду. При неисправности специалист подскажет удалённо или приедет.'],
    ],
]);
$one('main_reviews', ['TITLE' => 'Отзывы клиентов', 'SUBTITLE' => 'Что говорят о нас офисы, кафе и частные покупатели.', 'BTN_TEXT' => 'Все отзывы →', 'BTN_LINK' => '/otzyvy-o-nas/']);
$one('main_sub', [
    'CAPTION' => 'Подписка', 'TITLE' => "Кофе по графику,\nмашина бесплатно", 'HIGHLIGHT' => 'бесплатно',
    'SUBTITLE' => 'При регулярном заказе зернового кофе кофемашина предоставляется бесплатно, сервисное обслуживание включено.',
    'BTN_TEXT' => 'Собрать подписку', 'BTN_LINK' => '/podpiska/', 'BTN2_TEXT' => 'Подобрать кофе', 'BTN2_LINK' => '/podbor-kofe/',
], ['PREVIEW_PICTURE' => bt_pic_of('rent', 'jl-15-viva-arenda')]);
$one('main_about', [
    'TITLE' => 'Магазин чая и кофе BEVERTEAM',
    'TEXT' => '<p>Работаем с 2010 года: поставляем чай, кофе собственной обжарки BOTANICA и средства ухода за кофейным оборудованием мелким и крупным оптом на индивидуальных условиях. Обслуживаем и ремонтируем автоматические кофемашины в офисах, кафе и дома. Работаем напрямую с ресторанами, барами и офисами — гибкая система скидок и доставка по Екатеринбургу и всей России.</p>',
    'CAPTION' => 'Менеджер перезвонит или напишет в течение 5 минут',
    'BTN_TEXT' => 'О компании', 'BTN_LINK' => '/o-kompanii/', 'BTN2_TEXT' => 'Оплата и доставка', 'BTN2_LINK' => '/oplata-i-dostavka/',
]);
$one('main_journal', ['TITLE' => 'Журнал', 'SUBTITLE' => 'Как ухаживать за кофемашиной, выбирать зерно и оборудование. Пишем сами.', 'BTN_TEXT' => 'Все материалы →', 'BTN_LINK' => '/news/']);
$one('main_seo', [
    'TITLE' => 'Чай, кофе и кофемашины в Екатеринбурге',
    'TEXT' => '<p>BEVERTEAM — магазин чая, кофе и кофейного оборудования, работающий с 2010 года. Мы продаём обжаренный кофе в зёрнах BOTANICA, чай и аксессуары, поставляем и обслуживаем автоматические кофемашины Jetinno.</p>'
        . '<p>Основные направления: <a href="/arenda-kofemashin/">аренда кофемашин</a> для дома, офиса и кафе от 3 500 ₽ в месяц, <a href="/magazin/professionalnye-kofemashiny/">продажа оборудования</a> и <a href="/servis/">ремонт кофемашин</a> в собственном авторизованном сервисном центре. Доставляем по Екатеринбургу и всей России.</p>',
]);

// «О компании»
$aboutPic = is_file($dir . 'img/pages/o-kompanii/01.jpg') ? CFile::MakeFileArray($dir . 'img/pages/o-kompanii/01.jpg') : null;
bt_el_seed($ibs['about_intro'], 'main', ['NAME' => 'О компании: описание', 'PREVIEW_PICTURE' => $aboutPic], [
    'TEXT' => '<p>BEVERTEAM работает на рынке чая, кофе и кофейного оборудования с 2010 года. Мы поставляем и обслуживаем автоматические кофемашины Jetinno в Екатеринбурге, продаём обжаренный кофе BOTANICA, чай и средства по уходу за кофейным оборудованием.</p>'
        . '<p>Работаем напрямую с ресторанами, кафе, барами и офисами, доставляем домой. Осуществляем поставки мелкого и крупного опта на индивидуальных условиях, для постоянных клиентов — отсрочка платежа и бесплатные образцы продукции.</p>',
    'ITEMS' => [['2010', 'на рынке с'], ['Jetinno', 'авторизованный сервисный центр'], ['от 3 000 ₽', 'бесплатная доставка по Екатеринбургу'], ['ИП', 'ОГРНИП 310667128700035']],
], $say, $fail);
bt_el_seed($ibs['about_work'], 'main', ['NAME' => 'О компании: чем занимаемся'], [
    'TITLE' => 'Чем занимаемся',
    'ITEMS' => [
        ['Аренда и продажа', 'Кофемашины Jetinno для дома, офиса и кафе. Подбор под нагрузку, сервисное обслуживание в аренде.'],
        ['Сервисный центр', 'Авторизованный сервис Jetinno. Ремонт и обслуживание кофемашин в офисе, кафе и на дому.'],
        ['Кофе и чай', 'Кофе BOTANICA собственной обжарки под эспрессо и фильтр, чай, средства по уходу за оборудованием.'],
        ['Опт и HoReCa', 'Поставки для ресторанов, кафе, баров и офисов. Индивидуальные условия, отсрочка платежа для постоянных клиентов.'],
    ],
], $say, $fail);
bt_el_seed($ibs['about_cert'], 'main', ['NAME' => 'О компании: сертификат'], [
    'CAPTION' => 'Документы', 'TITLE' => 'Авторизованный сервисный центр Jetinno',
    'SUBTITLE' => 'Ремонт и сервисное обслуживание кофемашин Jetinno в Екатеринбурге.',
], $say, $fail);

// «Контакты»
$list('contacts_how', [['Самовывоз', 'Позвоните заранее — соберём заказ и встретим. Самовывоз по предварительной договорённости с менеджером.']]);

echo "done\n";
