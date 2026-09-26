<?php
// Инфоблоки страниц услуг: «Аренда кофемашин», «Кофе по подписке», «Сервис» (с ремонтом); стартовое наполнение — только реальные факты.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_services_setup.php [show|apply] [папка выгрузки]. Повторный запуск ничего не дублирует.

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

// ---------- типы ИБ: название = страница сайта ----------
$types = ['services' => ['Аренда кофемашин', 20], 'podpiska' => ['Кофе по подписке', 22], 'servis' => ['Сервис', 25]];
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

// единый набор полей блока страницы — как у блоков главной (bt_pages_setup.php)
$lead = 'Чтобы кнопка открывала форму заявки, укажите #zayavka';
$blockProps = [
    'TITLE' => ['Заголовок', 'S', ['ROW_COUNT' => 3, 'HINT' => 'Перенос строки в поле = перенос строки на сайте']],
    'SUBTITLE' => ['Подзаголовок', 'S', ['ROW_COUNT' => 3]],
    'TEXT' => ['Текст', 'S:HTML', []],
    'CAPTION' => ['Подпись', 'S', []],
    'BTN_TEXT' => ['Текст кнопки', 'S', []],
    'BTN_LINK' => ['Ссылка кнопки', 'S', ['HINT' => $lead]],
    'ITEMS' => ['Пункты списка', 'S', ['MULTIPLE' => 'Y', 'WITH_DESCRIPTION' => 'Y', 'MULTIPLE_CNT' => 3, 'HINT' => 'Слева — текст пункта, в поле «описание» — значение справа (цена, срок) или пояснение']],
];
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
        $say("ИБ {$f['CODE']} → «{$f['NAME']}» в типе {$f['IBLOCK_TYPE_ID']}, сортировка {$f['SORT']}");
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

// список элементов в админке — по SORT asc, как на сайте (общая настройка и у каждого пользователя)
function bt_grid_sort(string $type, int $ibId): void
{
    foreach (['tbl_iblock_list_', 'tbl_iblock_element_'] as $prefix) {
        $gridId = $prefix . md5($type . '.' . $ibId);
        $set = function (array $o) {
            $o['views']['default']['last_sort_by'] = 'SORT';
            $o['views']['default']['last_sort_order'] = 'asc';
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

// элемент по коду (одиночный блок) или по названию (карточка списка); существующий не трогаем
function bt_el_seed(int $ibId, string $code, array $fields, array $props, callable $say, callable $fail): void
{
    $filter = ['IBLOCK_ID' => $ibId] + ($code !== '' ? ['=CODE' => $code] : ['=NAME' => $fields['NAME']]);
    if (CIBlockElement::GetList([], $filter, false, false, ['ID'])->Fetch()) {
        return;
    }
    $say("  + {$fields['NAME']}");
    $o = new CIBlockElement();
    $id = (int)$o->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code ?: false, 'ACTIVE' => 'Y'] + array_filter($fields, fn($v) => $v !== null))
        or $fail("element {$fields['NAME']}: {$o->LAST_ERROR}");
    foreach ($props as $k => $v) {
        if ($k === 'TEXT') {
            $props[$k] = ['VALUE' => ['TEXT' => $v, 'TYPE' => 'HTML']];
        } elseif ($k === 'ITEMS') {
            $props[$k] = array_map(fn($x) => ['VALUE' => $x[0], 'DESCRIPTION' => $x[1] ?? ''], $v);
        }
    }
    $props and CIBlockElement::SetPropertyValuesEx($id, $ibId, $props);
}

function bt_icon_file(string $name): array
{
    $tmp = CTempFile::GetFileName($name . '.svg');
    CheckDirPath($tmp);
    file_put_contents($tmp, preg_replace('/ aria-hidden="true" focusable="false"/', ' xmlns="http://www.w3.org/2000/svg"', bt_icon($name)));
    return CFile::MakeFileArray($tmp);
}

$ibs = [];
$def = [
    // код => [тип, название, сортировка = порядок на странице, одиночный блок?, доп. свойства]
    'rent_top' => ['services', 'Первый экран и калькулятор', 10, true, []],
    'rent' => ['services', 'Модели в аренду', 20, false, null],
    'rent_terms' => ['services', 'Условия аренды', 30, false, []],
    'rent_event' => ['services', 'Аренда на мероприятие', 40, true, []],
    'rent_seo' => ['services', 'SEO-текст', 50, true, []],
    'rent_faq' => ['services', 'Вопросы и ответы', 60, false, []],
    'rent_form' => ['services', 'Форма заявки', 70, true, []],
    'sub_head' => ['podpiska', 'Первый экран и конфигуратор', 10, true, []],
    'sub_how' => ['podpiska', 'Как это работает', 20, true, []],
    'sub_faq' => ['podpiska', 'Вопросы о подписке', 30, false, []],
    'servis_head' => ['servis', 'Услуги: заголовок', 10, true, []],
    'servis_dirs' => ['servis', 'Услуги: направления', 20, false, [
        'ITEMS' => ['Пункты', 'S', ['MULTIPLE' => 'Y', 'MULTIPLE_CNT' => 4]],
        'PRICE' => ['Цена', 'S', ['HINT' => 'Пусто у аренды и продажи — цена «от» считается по моделям аренды и каталогу']],
        'PRICE_NOTE' => ['Подпись к цене', 'S', []],
        'LINK' => ['Ссылка', 'S', []],
        'LINK_TEXT' => ['Текст ссылки', 'S', []],
    ]],
    'servis_price' => ['servis', 'Прайс на обслуживание', 30, true, []],
    'servis_brands' => ['servis', 'Ремонт по маркам: заголовок', 40, true, []],
    'repair_brands' => ['servis', 'Ремонт по маркам: бренды', 50, false, null],
    'repair_top' => ['servis', 'Ремонт: первый экран', 60, true, []],
    'repair_strip' => ['servis', 'Ремонт: полоса преимуществ', 70, false, $iconProp],
    'repair_symptoms' => ['servis', 'Ремонт: симптомы', 80, false, []],
    'repair_faq' => ['servis', 'Ремонт: вопросы и ответы', 90, false, []],
    'repair_form' => ['servis', 'Ремонт: форма вызова инженера', 100, true, []],
];
foreach ($def as $code => [$type, $name, $sort, $single, $extra]) {
    $ibs[$code] = bt_ib_ensure(['IBLOCK_TYPE_ID' => $type, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort],
        $extra === null ? [] : ($single ? $blockProps + $extra : $extra), $apply, $say, $fail);
    if ($apply && $ibs[$code]) {
        bt_grid_sort($type, $ibs[$code]);
    }
}

// модели аренды — названия как H1 на старом сайте
$rentNames = ['jl-05-arenda' => 'Аренда кофемашины Jetinno Jl 05', 'jl-15-viva-arenda' => 'Аренда кофемашины Jetinno Jl 15 (VIVA)', 'jl-36-arenda' => 'Аренда кофемашины Jetinno JL 36'];
$r = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibs['rent'], '=CODE' => array_keys($rentNames)], false, false, ['ID', 'CODE', 'NAME']);
while ($e = $r->Fetch()) {
    if ($e['NAME'] !== $rentNames[$e['CODE']]) {
        $say("аренда {$e['CODE']}: «{$e['NAME']}» → «{$rentNames[$e['CODE']]}»");
        $apply and (new CIBlockElement())->Update($e['ID'], ['NAME' => $rentNames[$e['CODE']]]);
    }
}

// SEO страниц брендов ремонта: шаблоны в настройках ИБ, сеошник правит их в админке
if ($ibs['repair_brands']) {
    $tpl = [
        'ELEMENT_PAGE_TITLE' => 'Ремонт кофемашин {=this.Name} в Екатеринбурге',
        'ELEMENT_META_TITLE' => 'Ремонт кофемашин {=this.Name} в Екатеринбурге — сервисный центр | BEVERTEAM',
        'ELEMENT_META_DESCRIPTION' => 'Ремонт и обслуживание кофемашин {=this.Name} в Екатеринбурге: в офисе, кафе и на дому. Сервисный центр BEVERTEAM, оставьте заявку на вызов инженера.',
        'ELEMENT_META_KEYWORDS' => 'ремонт кофемашин {=this.Name}',
    ];
    $ipt = new \Bitrix\Iblock\InheritedProperty\IblockTemplates($ibs['repair_brands']);
    $cur = array_map(fn($x) => $x['TEMPLATE'], $ipt->findTemplates());
    if (array_intersect_key($cur, $tpl) != $tpl) {
        $say('SEO-шаблоны страниц брендов ремонта');
        $apply and $ipt->set($tpl);
    }
}

if (!$apply) {
    echo "done (show: контент переносится только в apply)\n";
    return;
}

// ---------- стартовое наполнение ----------
$one = fn(string $code, array $p, array $f = []) => bt_el_seed($ibs[$code], 'main', ['NAME' => $def[$code][1]] + $f, $p, $say, $fail);
$list = function (string $code, array $items) use ($ibs, $say, $fail) {
    foreach ($items as $i => $it) {
        bt_el_seed($ibs[$code], '', ['NAME' => $it[0], 'SORT' => ($i + 1) * 10, 'PREVIEW_TEXT' => $it[1] ?? '', 'PREVIEW_TEXT_TYPE' => $it[3] ?? 'text'], $it[2] ?? [], $say, $fail);
    }
};
$incl = [['Сервисное обслуживание кофемашины'], ['Подбор модели под нагрузку и набор напитков'], ['Настройка рецептур под ваши пожелания'], ['Консультация специалиста при неисправности']];

// «Аренда кофемашин»
$one('rent_top', [
    'CAPTION' => 'Услуга · Екатеринбург',
    'SUBTITLE' => 'Привозим, устанавливаем и обслуживаем. Сервисное обслуживание входит в стоимость аренды. Договор с ИП и юрлицами, оплата по счёту.',
    'ITEMS' => $incl,
    'TITLE' => 'Где будет стоять машина?', 'BTN_TEXT' => 'Оставить заявку', 'BTN_LINK' => '#form',
]);
$list('rent_terms', [
    ['Фиксированная оплата', 'Аренда с фиксированной ежемесячной оплатой без обязательной привязки к покупке кофе.'],
    ['Кофемашина при заказе кофе', 'Предоставление кофемашины при регулярном заказе согласованного объёма зернового кофе.'],
    ['Сервис включён', 'В представленных вариантах аренды сервисное обслуживание включено.'],
    ['Договор и оплата', 'Договор с ИП и юрлицами, оплата по счёту.'],
]);
$one('rent_event', [
    'TITLE' => 'Кофемашина на мероприятие', 'SUBTITLE' => 'Подберём модель под формат и число гостей. Стоимость — по запросу.',
    'BTN_TEXT' => 'Заявка на мероприятие', 'BTN_LINK' => '#zayavka',
]);
// SEO-текст раздела со старого сайта: вступление — блоком, вопросы и ответы — списком
$sec = [];
foreach (json_decode((string)@file_get_contents($dir . 'sections.json'), true) ?: [] as $s) {
    $s['code'] === 'arenda-kofemashin' and $sec = $s;
}
if ($sec) {
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8"?><body>' . $sec['description_html'] . '</body>', LIBXML_NOERROR);
    $intro = '';
    $faq = [];
    $inFaq = false;
    foreach ($doc->getElementsByTagName('body')->item(0)->childNodes as $n) {
        if ($n->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }
        $html = $doc->saveHTML($n);
        $text = trim($n->textContent);
        if ($n->nodeName === 'h2' && str_starts_with($text, 'Вопросы и ответы')) {
            $inFaq = true;
        } elseif (!$inFaq) {
            $intro .= $html . "\n";
        } elseif ($n->nodeName === 'p' && str_ends_with($text, '?')) {
            $faq[] = [$text, ''];
        } elseif ($faq) {
            $faq[count($faq) - 1][1] .= $html . "\n";
        }
    }
    $one('rent_seo', ['TEXT' => trim($intro)]);
    $list('rent_faq', array_map(fn($q) => [$q[0], trim($q[1]), [], 'html'], $faq));
} else {
    echo "нет {$dir}sections.json — SEO-текст аренды не перенесён\n";
}
$one('rent_form', [
    'TITLE' => 'Подберём модель под вашу нагрузку',
    'SUBTITLE' => 'Менеджер перезвонит в течение 5 минут в рабочее время, уточнит место установки, число чашек в день и нужные напитки.',
    'BTN_TEXT' => 'Отправить заявку',
]);

// «Кофе по подписке»
$one('sub_head', [
    'SUBTITLE' => 'При регулярном заказе зернового кофе кофемашина предоставляется бесплатно, сервисное обслуживание включено.',
    'TITLE' => 'Соберите подписку', 'CAPTION' => 'Менеджер перезвонит, уточнит детали, согласует договор, доставку и установку кофемашины.',
    'BTN_TEXT' => 'Оформить подписку',
]);
$one('sub_how', [
    'TITLE' => 'Как это работает',
    'ITEMS' => [
        ['Выбираете объём и сорт', 'Калькулятор покажет стоимость кофе и кофемашину, которая при таком объёме предоставляется бесплатно.'],
        ['Оставляете заявку', 'Менеджер свяжется в течение 5 минут в рабочее время и уточнит место установки, число чашек в день и нужные напитки.'],
        ['Договор и установка', 'Согласуем график поставок, доставку и установку кофемашины, настроим рецептуры под ваши пожелания.'],
        ['Кофе по графику', 'Привозим зерно с выбранной периодичностью. Сервисное обслуживание кофемашины включено.'],
    ],
]);

// «Сервис»
$one('servis_head', ['SUBTITLE' => 'Арендовать, купить или починить кофемашину. Авторизованный сервисный центр Jetinno в Екатеринбурге.']);
$list('servis_dirs', [
    ['Аренда кофемашин', 'Кофемашины Jetinno с фиксированной ежемесячной оплатой или бесплатно при регулярном заказе кофе.', [
        'ITEMS' => array_column($incl, 0), 'PRICE_NOTE' => 'в месяц', 'LINK' => '/arenda-kofemashin/', 'LINK_TEXT' => 'Подробнее →']],
    ['Продажа оборудования', 'Автоматические кофемашины Jetinno для дома, офиса и кафе.', [
        'ITEMS' => ['Подбор модели под нагрузку и набор напитков', 'Сервис в собственном сервисном центре Jetinno', 'Доставка по Екатеринбургу и всей России'],
        'PRICE_NOTE' => 'за аппарат', 'LINK' => '/magazin/professionalnye-kofemashiny/', 'LINK_TEXT' => 'Каталог →']],
    ['Ремонт и обслуживание', 'Ремонт и обслуживание автоматических кофемашин в офисах, кафе и на дому.', [
        'ITEMS' => ['Авторизованный сервисный центр Jetinno', 'Плановое ТО и чистка молочной системы', 'Ремонт с заменой узлов', 'Выезд инженера по Екатеринбургу'],
        'PRICE' => 'по заявке', 'LINK' => '/servis/remont-kofemashin/', 'LINK_TEXT' => 'Подробнее →']],
]);
$one('servis_price', [
    'CAPTION' => 'Сервисный центр', 'TITLE' => 'Прайс на обслуживание',
    'SUBTITLE' => 'Стоимость зависит от модели и неисправности — менеджер назовёт её по заявке.',
    'ITEMS' => [['Плановое ТО и чистка молочной системы', 'по запросу'], ['Ремонт с заменой узлов', 'по смете'], ['Выезд инженера по Екатеринбургу', 'по заявке']],
    'BTN_TEXT' => 'Оставить заявку на ремонт', 'BTN_LINK' => '/servis/remont-kofemashin/#form',
]);
$one('servis_brands', ['TITLE' => 'Ремонт по маркам', 'SUBTITLE' => 'Авторизованный сервисный центр Jetinno в Екатеринбурге.']);
$one('repair_top', [
    'SUBTITLE' => 'Ремонт и обслуживание автоматических кофемашин в офисах, кафе и на дому.',
    'BTN_TEXT' => 'Вызвать инженера', 'BTN_LINK' => '#form',
]);
$list('repair_strip', [
    ['Авторизованный сервисный центр Jetinno', '', ['ICON' => bt_icon_file('uTool')]],
    ['Ремонт и обслуживание в офисе, кафе и на дому', '', ['ICON' => bt_icon_file('uTruck')]],
    ['Консультация специалиста при неисправности', '', ['ICON' => bt_icon_file('uSwap')]],
    ['Договор с ИП и юрлицами, оплата по счёту', '', ['ICON' => bt_icon_file('uPrice')]],
]);
$list('repair_symptoms', array_map(fn($s) => [$s], ['Не наливает кофе или течёт мимо', 'Шумит помпа, гудит при заваривании', 'Не взбивает молоко',
    'Течёт вода под машину', 'Горит ошибка на экране', 'Слабый напор, кофе еле капает', 'Не мелет зерно', 'Нужно плановое ТО и чистка']));
$one('repair_form', [
    'TITLE' => 'Вызвать инженера',
    'SUBTITLE' => 'Перезвоним в течение 5 минут в рабочее время, уточним симптомы и согласуем время выезда.',
    'BTN_TEXT' => 'Вызвать инженера',
]);

foreach ($ibs as $id) {
    $id and CIBlock::clearIblockTagCache($id);
}
echo "done\n";
