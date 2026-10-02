<?php
// Инфоблоки страницы «Наши клиенты» (/nashi-klienty/): тип «Наши клиенты», первый экран, установки, призыв внизу.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_clients_setup.php [show|apply]. Повторный запуск ничего не дублирует и правки клиента не трогает.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/bt_setup_lib.php';

use Bitrix\Main\Loader;

Loader::includeModule('iblock');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");

$type = 'clients';
if (!CIBlockType::GetByID($type)->Fetch()) {
    $say("тип ИБ $type «Наши клиенты»");
    $apply and ((new CIBlockType())->Add(['ID' => $type, 'SECTIONS' => 'N', 'IN_RSS' => 'N', 'SORT' => 42,
        'LANG' => ['ru' => ['NAME' => 'Наши клиенты', 'ELEMENT_NAME' => 'Элемент', 'SECTION_NAME' => 'Раздел']]]) or $fail('type'));
}

$blockProps = [
    'TITLE' => ['Заголовок', 'S', ['ROW_COUNT' => 3]],
    'SUBTITLE' => ['Подзаголовок', 'S', ['ROW_COUNT' => 3]],
    'CAPTION' => ['Подпись', 'S', []],
    'BTN_TEXT' => ['Текст кнопки', 'S', []],
    'BTN_LINK' => ['Ссылка кнопки', 'S', []],
];
$segments = [['Бизнес-центр', 'bc'], ['Офис', 'ofis'], ['Кафе и кофейня', 'kafe'], ['Пекарня', 'pekarnya'], ['АЗС и автомойка', 'azs'],
    ['Магазин', 'magazin'], ['Гостиница', 'gostinica'], ['Автосалон', 'avtosalon'], ['Мероприятие', 'meropriyatie'], ['Другое', 'drugoe']];
$catId = bt_iblock('catalog');
$caseProps = [
    'PHOTOS' => ['Фото установки', 'F', ['MULTIPLE' => 'Y', 'MULTIPLE_CNT' => 3, 'FILE_TYPE' => 'jpg, jpeg, png, webp',
        'HINT' => 'Первое фото — на карточке, все — в просмотре на весь экран. Лучше горизонтальные, от 1600 px по ширине']],
    'CITY' => ['Город', 'S', ['HINT' => 'По городам работает фильтр — пишите одинаково: «Екатеринбург»']],
    'SEGMENT' => ['Тип объекта', 'L', ['VALUES' => array_map(fn($s, $i) => ['VALUE' => $s[0], 'XML_ID' => $s[1], 'SORT' => ($i + 1) * 10], $segments, array_keys($segments))]],
    'MACHINE' => ['Кофемашина', 'E', ['LINK_IBLOCK_ID' => $catId, 'HINT' => 'Товар из каталога. Если модель есть в аренде — ссылка ведёт на её страницу аренды']],
    'CUPS' => ['Чашек в день', 'N', []],
    'DEMO' => ['Демо-данные', 'L', ['LIST_TYPE' => 'C', 'VALUES' => [['VALUE' => 'Да', 'XML_ID' => 'Y', 'DEF' => 'N']],
        'HINT' => 'Стоит галочка — на сайте над списком плашка «Демо». Снимите, когда замените на настоящие данные']],
];
$ctaProps = $blockProps + ['BTN2_TEXT' => ['Вторая кнопка: текст', 'S', []], 'BTN2_LINK' => ['Вторая кнопка: ссылка', 'S', []]];

$ibs = [
    'clients_head' => bt_ib_ensure(['IBLOCK_TYPE_ID' => $type, 'CODE' => 'clients_head', 'NAME' => 'Первый экран', 'SORT' => 10], array_diff_key($blockProps, ['TITLE' => 1]), $apply, $say, $fail),
    'clients' => bt_ib_ensure(['IBLOCK_TYPE_ID' => $type, 'CODE' => 'clients', 'NAME' => 'Установки', 'SORT' => 20,
        'DESCRIPTION' => 'Фото установок, название клиента, город, модель, чашек в день и почему выбрана именно она — запросить у Василия'], $caseProps, $apply, $say, $fail),
    'clients_cta' => bt_ib_ensure(['IBLOCK_TYPE_ID' => $type, 'CODE' => 'clients_cta', 'NAME' => 'Призыв внизу страницы', 'SORT' => 30], $ctaProps, $apply, $say, $fail),
];
if (!$apply) {
    echo "done (show: наполнение — только в apply)\n";
    return;
}
foreach ($ibs as $id) {
    bt_grid_sort($type, $id);
}
(new CIBlock())->Update($ibs['clients'], ['ELEMENT_NAME' => 'Установка', 'ELEMENTS_NAME' => 'Установки', 'ELEMENT_ADD' => 'Добавить установку', 'ELEMENT_EDIT' => 'Изменить установку']);

$headId = bt_el_seed($ibs['clients_head'], 'main', ['NAME' => 'Первый экран'], [
    'CAPTION' => 'Установки Jetinno · Екатеринбург и область',
    'SUBTITLE' => 'Бизнес-центры, офисы, кафе, пекарни и АЗС, где работают наши кофемашины. Для каждой установки — какая модель стоит, сколько чашек готовит и почему выбрали именно её. Фото открываются на весь экран.',
    'BTN_TEXT' => 'Подобрать машину', 'BTN_LINK' => '#cta',
], $say, $fail);
$tpl = new \Bitrix\Iblock\InheritedProperty\ElementTemplates($ibs['clients_head'], $headId);
if (!array_filter($tpl->findTemplates(), fn($t) => $t['INHERITED'] === 'N')) {
    $say('  SEO страницы — во вкладке SEO элемента «Первый экран»');
    $tpl->set([
        'ELEMENT_META_TITLE' => 'Наши клиенты — кофемашины Jetinno в офисах, кафе и бизнес-центрах Екатеринбурга | BEVERTEAM',
        'ELEMENT_META_DESCRIPTION' => 'Реальные установки кофемашин Jetinno в Екатеринбурге и Свердловской области: бизнес-центры, офисы, кафе, пекарни, АЗС. Какая модель, сколько чашек в день и почему её выбрали.',
        'ELEMENT_META_KEYWORDS' => 'наши клиенты, кофемашины в офисе, кофемашина для кафе, установки Jetinno Екатеринбург',
        'ELEMENT_PAGE_TITLE' => 'Наши клиенты',
    ]);
}
bt_el_seed($ibs['clients_cta'], 'main', ['NAME' => 'Призыв внизу страницы'], [
    'TITLE' => 'Поставим такую же машину у вас',
    'SUBTITLE' => 'Расскажите, сколько людей пьёт кофе и какой у вас объект, — подберём модель, привезём и настроим рецепты. Аренда 0 ₽ при заказе кофе BOTANICA.',
    'BTN_TEXT' => 'Арендовать кофемашину', 'BTN_LINK' => '/arenda-kofemashin/#calc',
    'BTN2_TEXT' => 'Купить кофемашину', 'BTN2_LINK' => '/catalog/professionalnye-kofemashiny/',
], $say, $fail);

// демо-установки: фото — CC0 из Wikimedia Commons (Unsplash), лежат в /upload/demo/clients/
$enum = [];
$r = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $ibs['clients']]);
while ($e = $r->Fetch()) {
    $enum[$e['PROPERTY_CODE']][$e['XML_ID']] = (int)$e['ID'];
}
$machine = [];
$r = CIBlockElement::GetList([], ['IBLOCK_ID' => $catId, '=CODE' => ['jetinno-jl36', 'jetinno-jl-15-viva', 'jetinno-jl-05', 'jetinno-jl-32', 'jetinno-jl33', 'jetinno-jl-03']], false, false, ['ID', 'CODE']);
while ($m = $r->Fetch()) {
    $machine[$m['CODE']] = (int)$m['ID'];
}
$dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/demo/clients/';
$demo = [
    ['Бизнес-центр на Ленина', 'bc', 'Екатеринбург', 'jetinno-jl36', 180, [20, 25], 'Поток арендаторов и гостей в лобби с утра до вечера. JL 36 держит больше 200 чашек в день, оплата картой и по QR — прямо на машине.'],
    ['Кофейня «Зерно»', 'kafe', 'Екатеринбург', 'jetinno-jl-15-viva', 90, [13, 5], 'Работает вместо второго бариста в часы пик. Капучино одинаковый в любую смену, рецепты настроили под меню кофейни.'],
    ['Пекарня на Вайнера', 'pekarnya', 'Екатеринбург', 'jetinno-jl-05', 60, [21, 12], 'Кофе к выпечке без отдельного сотрудника. Компактная машина встала на край витрины, гости наливают сами.'],
    ['IT-офис в Академическом', 'ofis', 'Екатеринбург', 'jetinno-jl-05', 40, [27, 19], 'Команда из 35 человек, кофе по одной кнопке. Машина бесплатно — офис заказывает зерно BOTANICA раз в месяц.'],
    ['Отель у Плотинки', 'gostinica', 'Екатеринбург', 'jetinno-jl36', 120, [22, 4], 'Завтраки и лобби 24/7: гость сам выбирает напиток на большом сенсорном экране, персонал не отвлекается.'],
    ['Ресторан «Печка»', 'kafe', 'Верхняя Пышма', 'jetinno-jl-32', 100, [24, 18], 'Кофе для зала и на вынос со свежим молоком. Капучино за 40 секунд, мойка молочной системы — автоматически.'],
    ['АЗС на Тюменском тракте', 'azs', 'Берёзовский', 'jetinno-jl33', 150, [9, 8], 'Самообслуживание круглые сутки и оплата картой. Телеметрия сама сообщает, когда заканчивается зерно.'],
    ['Коворкинг на Малышева', 'ofis', 'Екатеринбург', 'jetinno-jl-15-viva', 70, [11, 26], 'Резиденты платят за кофе картой прямо на машине — коворкинг окупает аренду и зарабатывает на напитках.'],
    ['Автосалон на Кулибина', 'avtosalon', 'Екатеринбург', 'jetinno-jl-15-viva', 50, [17, 0], 'Кофе гостям, пока оформляют машину. Корпус в чёрном стекле вписался в интерьер шоурума.'],
    ['Кофейня в Нижнем Тагиле', 'kafe', 'Нижний Тагил', 'jetinno-jl-03', 45, [12, 13], 'Небольшая точка у остановки: компактная JL 03 заменила рожковую машину и бариста на утренней смене.'],
];
foreach ($demo as $i => [$name, $seg, $city, $code, $cups, $photos, $why]) {
    bt_el_seed($ibs['clients'], '', ['NAME' => $name, 'SORT' => ($i + 1) * 10, 'PREVIEW_TEXT' => $why, 'PREVIEW_TEXT_TYPE' => 'text'], [
        'PHOTOS' => array_map(fn($n) => ['VALUE' => CFile::MakeFileArray($dir . $n . '.jpg')], array_filter($photos, fn($n) => is_file($dir . $n . '.jpg'))),
        'CITY' => $city, 'SEGMENT' => $enum['SEGMENT'][$seg] ?? false, 'MACHINE' => $machine[$code] ?? false, 'CUPS' => $cups, 'DEMO' => $enum['DEMO']['Y'] ?? false,
    ], $say, $fail);
}
BXClearCache(true, '/bt/');
$GLOBALS['CACHE_MANAGER']->ClearByTag('iblock_id_' . $ibs['clients']);
echo "done\n";
