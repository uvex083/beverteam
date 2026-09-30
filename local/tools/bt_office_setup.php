<?php
// Инфоблоки страницы «Кофе в офис» (/kofe-v-ofis/, бывшая «Кофе по подписке»): тип podpiska → «Кофе в офис», тексты, SEO, ссылки /podpiska/ в блоках других страниц.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_office_setup.php [show|apply]. Повторный запуск ничего не дублирует и правки клиента не трогает.

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
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

$type = 'podpiska';
$t = CIBlockType::GetByIDLang($type, 'ru');
if ($t && $t['NAME'] !== 'Кофе в офис') {
    $say("тип ИБ $type «{$t['NAME']}» → «Кофе в офис»");
    $apply and (new CIBlockType())->Update($type, ['LANG' => ['ru' => ['NAME' => 'Кофе в офис', 'ELEMENT_NAME' => 'Элемент', 'SECTION_NAME' => 'Раздел']]]);
}

$blockProps = [
    'TITLE' => ['Заголовок', 'S', ['ROW_COUNT' => 3, 'HINT' => 'Перенос строки в поле = перенос строки на сайте']],
    'SUBTITLE' => ['Подзаголовок', 'S', ['ROW_COUNT' => 3]],
    'TEXT' => ['Текст', 'S:HTML', []],
    'CAPTION' => ['Подпись', 'S', []],
    'BTN_TEXT' => ['Текст кнопки', 'S', []],
    'BTN_LINK' => ['Ссылка кнопки', 'S', []],
    'ITEMS' => ['Пункты списка', 'S', ['MULTIPLE' => 'Y', 'WITH_DESCRIPTION' => 'Y', 'MULTIPLE_CNT' => 3, 'HINT' => 'Слева — текст пункта, в поле «описание» — пояснение (до 255 знаков)']],
];
$def = [
    'sub_head' => ['Первый экран и конфигуратор', 10, true],
    'sub_how' => ['Как это работает', 20, true],
    'sub_rent' => ['Кофемашины нет?', 25, true],
    'sub_faq' => ['Вопросы и ответы', 30, false],
];
$ibs = [];
foreach ($def as $code => [$name, $sort, $single]) {
    $ibs[$code] = bt_ib_ensure(['IBLOCK_TYPE_ID' => $type, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort], $single ? $blockProps : [], $apply, $say, $fail);
    $apply && $ibs[$code] and bt_grid_sort($type, $ibs[$code]);
}

// ссылки на /podpiska/ в блоках других страниц
$fix = [];
$r = CIBlock::GetList([], ['CHECK_PERMISSIONS' => 'N']);
while ($ib = $r->Fetch()) {
    if (in_array($ib['IBLOCK_TYPE_ID'], ['catalog', 'news', 'forms'], true)) {
        continue;
    }
    $er = CIBlockElement::GetList([], ['IBLOCK_ID' => $ib['ID']], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
    while ($ob = $er->GetNextElement()) {
        $f = $ob->GetFields();
        foreach ($ob->GetProperties() as $p) {
            if ($p['PROPERTY_TYPE'] === 'S' && $p['MULTIPLE'] !== 'Y' && is_string($p['VALUE']) && str_contains($p['VALUE'], '/podpiska/')) {
                $fix[] = [$ib['ID'], $f['ID'], $p['CODE'], $ib['CODE'] . ' «' . $f['NAME'] . '» ' . $p['CODE']];
            }
        }
    }
}
foreach ($fix as [, , , $label]) {
    $say("ссылка /podpiska/ → /kofe-v-ofis/: $label");
}

if (!$apply) {
    echo "done (show: наполнение — только в apply)\n";
    return;
}
foreach ($fix as [$ibId, $elId, $code]) {
    $v = CIBlockElement::GetProperty($ibId, $elId, [], ['CODE' => $code])->Fetch()['VALUE'] ?? '';
    CIBlockElement::SetPropertyValuesEx($elId, $ibId, [$code => str_replace('/podpiska/', '/kofe-v-ofis/', $v)]);
}
// карточки «Другие услуги» на аренде и ремонте вели на подписку
foreach (['rent_links', 'repair_links'] as $code) {
    $ibId = bt_iblock($code);
    $el = $ibId ? CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=NAME' => 'Кофе по подписке'], false, false, ['ID'])->Fetch() : null;
    if ($el) {
        $say("  ~ $code: «Кофе по подписке» → «Кофе в офис»");
        (new CIBlockElement())->Update($el['ID'], ['NAME' => 'Кофе в офис', 'PREVIEW_TEXT' => 'Регулярная доставка зерна для офиса и кафе', 'PREVIEW_TEXT_TYPE' => 'text']);
        CIBlockElement::SetPropertyValuesEx($el['ID'], $ibId, ['LINK' => '/kofe-v-ofis/']);
    }
}

$props = function (array $p): array {
    foreach ($p as $k => $v) {
        $k === 'ITEMS' and $p[$k] = array_map(fn($x) => ['VALUE' => $x[0], 'DESCRIPTION' => $x[1] ?? ''], $v);
    }
    return $p;
};
$v2 = [
    'sub_head' => [
        'TITLE' => 'Рассчитайте поставки',
        'SUBTITLE' => 'Зерно BOTANICA свежей обжарки для офиса и кафе: от 1 кг в месяц, доставка по графику, оптовая цена от объёма. Подходит к любой зерновой кофемашине.',
        'ITEMS' => [['Для любой кофемашины'], ['Доставка по графику, бесплатно от 3 000 ₽'], ['Оптовая цена от объёма'], ['Договор, оплата по счёту, закрывающие документы']],
        'BTN_TEXT' => 'Заказать поставки',
        'CAPTION' => 'Менеджер перезвонит в течение 5 минут в рабочее время, уточнит сорт, адрес и удобный день доставки.',
    ],
    'sub_how' => ['TITLE' => 'Как это работает', 'ITEMS' => [
        ['Считаете объём', 'Калькулятор покажет стоимость кофе, оптовую скидку, доставку и цену чашки.'],
        ['Оставляете заявку', 'Менеджер перезвонит, поможет выбрать сорт под вашу кофемашину и напитки.'],
        ['Договор и документы', 'Работаем с ИП и организациями: договор, оплата по счёту, закрывающие документы.'],
        ['Кофе по графику', 'Привозим зерно в удобный день — раз в неделю, в две недели или в месяц. Объём и сорт меняются по звонку.'],
    ]],
];
if (Option::get('bt', 'office_v1', '') !== 'Y') {
    foreach ($v2 as $code => $p) {
        $el = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibs[$code], '=CODE' => 'main'], false, false, ['ID'])->Fetch();
        if ($el) {
            $say("  ~ новые тексты: {$def[$code][0]}");
            CIBlockElement::SetPropertyValuesEx($el['ID'], $ibs[$code], $props($p));
        }
    }
    $top = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibs['sub_head'], '=CODE' => 'main'], false, false, ['ID'])->Fetch();
    if ($top) {
        $say('  SEO страницы — во вкладке SEO элемента «Первый экран и конфигуратор»');
        (new \Bitrix\Iblock\InheritedProperty\ElementTemplates($ibs['sub_head'], $top['ID']))->set([
            'ELEMENT_META_TITLE' => 'Кофе в офис с доставкой в Екатеринбурге — зерно BOTANICA по графику | BEVERTEAM',
            'ELEMENT_META_DESCRIPTION' => 'Кофе для офиса и кафе с доставкой по Екатеринбургу: зерно BOTANICA свежей обжарки от 1 кг в месяц, доставка по графику, оптовые цены, оплата по счёту. Кофемашина в аренду за 0 ₽ от #RENT_FREE_KG# кг.',
            'ELEMENT_META_KEYWORDS' => 'кофе в офис, кофе для офиса, доставка кофе Екатеринбург, кофе в зернах для офиса, кофе для кофемашины в офис',
            'ELEMENT_PAGE_TITLE' => 'Кофе в офис с доставкой в Екатеринбурге',
        ]);
    }
    Option::set('bt', 'office_v1', 'Y');
}
$one = fn(string $code, array $p) => bt_el_seed($ibs[$code], 'main', ['NAME' => $def[$code][0]], $p, $say, $fail);
foreach ($v2 as $code => $p) {
    $one($code, $p);
}
$one('sub_rent', ['TITLE' => 'Кофемашины нет?', 'BTN_TEXT' => 'Рассчитать аренду', 'BTN_LINK' => '/arenda-kofemashin/#calc',
    'SUBTITLE' => 'При заказе кофе от #RENT_FREE_KG# кг в месяц поставим суперавтомат Jetinno в аренду за 0 ₽ — с установкой и обслуживанием.']);
foreach ([
    ['Подойдёт ли ваш кофе к нашей кофемашине?', 'Да, если машина зерновая: суперавтомат, рожковая или кофемолка. Под автомат советуем Эспрессо смесь или Милк, под рожок и фильтр — моносорта. Менеджер поможет выбрать по модели машины.'],
    ['Какой минимальный заказ?', 'От 1 кг в месяц. Доставка по Екатеринбургу бесплатная, если одна поставка от 3 000 ₽, иначе — 350 ₽ за доставку.'],
    ['Как часто привозите?', 'Раз в неделю, раз в две недели или раз в месяц — как удобно. День доставки согласуем, график можно поменять по звонку.'],
    ['Есть ли оптовые цены?', 'Да, цена за килограмм снижается с объёмом — калькулятор показывает скидку по выбранному сорту. Для больших объёмов условия обсудим индивидуально.'],
    ['Работаете с организациями?', 'Да, с ИП и юрлицами: договор, оплата по счёту, закрывающие документы.'],
    ['Можно поменять сорт или объём?', 'Да, в любой момент — позвоните или напишите менеджеру. Новый сорт приедет со следующей поставкой.'],
    ['Как хранить кофе в офисе?', 'В закрытой упаковке с клапаном, в тёмном месте, не в холодильнике. Вскрытую пачку лучше использовать за 2–3 недели — поэтому удобнее получать кофе чаще небольшими партиями.'],
    ['А если кофемашины нет?', 'При заказе от #RENT_FREE_KG# кг в месяц дадим суперавтомат Jetinno в аренду за 0 ₽ — с установкой, настройкой рецептов и обслуживанием. Подробнее — на странице аренды кофемашин.'],
] as $i => [$q, $a]) {
    bt_el_seed($ibs['sub_faq'], '', ['NAME' => $q, 'SORT' => ($i + 1) * 10, 'PREVIEW_TEXT' => $a, 'PREVIEW_TEXT_TYPE' => 'text'], [], $say, $fail);
}

foreach ($ibs as $id) {
    $id and CIBlock::clearIblockTagCache($id);
}
foreach (['rent_links', 'repair_links'] as $code) {
    ($id = bt_iblock($code)) and CIBlock::clearIblockTagCache($id);
}
echo "done\n";
