<?php
// Поиск и остатки главной — в админку: «Поиск: часто ищут», «Поиск: предложения» (тип «Сайт: общие блоки»),
// подписи вкладок аренды/продажи, заголовок формы и блок карты на главной. Стартовое наполнение — то, что было зашито в шаблоне.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_search_setup.php [show|apply]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");

$ensureIb = function (string $type, string $code, string $name, int $sort, string $desc) use ($apply, $say): int {
    $ib = CIBlock::GetList([], ['=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($ib) {
        return (int)$ib['ID'];
    }
    $say("инфоблок «{$name}»");
    if (!$apply) {
        return 0;
    }
    $o = new CIBlock();
    $id = (int)$o->Add(['IBLOCK_TYPE_ID' => $type, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'SITE_ID' => ['s1'], 'ACTIVE' => 'Y',
        'GROUP_ID' => ['2' => 'R'], 'VERSION' => 2, 'INDEX_ELEMENT' => 'N', 'DESCRIPTION' => $desc, 'DESCRIPTION_TYPE' => 'text']);
    $id or die("ошибка инфоблока {$code}: {$o->LAST_ERROR}\n");
    return $id;
};
$ensureProp = function (int $ibId, string $code, array $f) use ($apply, $say): int {
    if (!$ibId) {
        return 0;
    }
    if ($p = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => $code])->Fetch()) {
        return (int)$p['ID'];
    }
    $say("  свойство {$code} «{$f['NAME']}»");
    return $apply ? (int)(new CIBlockProperty())->Add($f + ['IBLOCK_ID' => $ibId, 'CODE' => $code, 'ACTIVE' => 'Y']) : 0;
};
$el = new CIBlockElement();
$empty = fn(int $ibId) => $ibId && !CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId], []);

// «Часто ищут»: фраза = название элемента, порядок — сортировка
$hints = $ensureIb('site', 'search_hints', 'Поиск: часто ищут', 30, 'Подсказки в окне поиска. Фраза — название элемента, порядок — сортировка; выключенные не показываются.');
if ($empty($hints)) {
    foreach (['Эфиопия Оромия', 'кофе для офиса', 'аренда кофемашины', 'Jetinno JL15', 'чай Эрл Грей', 'ремонт кофемашины', 'кофе оптом', 'дрип-пакеты'] as $i => $q) {
        $say("  подсказка «{$q}»");
        $apply and $el->Add(['IBLOCK_ID' => $hints, 'NAME' => $q, 'ACTIVE' => 'Y', 'SORT' => ($i + 1) * 10]);
    }
}

// «Предложения»: карточки справа в окне поиска
$promos = $ensureIb('site', 'search_promos', 'Поиск: предложения', 40,
    'Карточки справа в окне поиска. Заголовок — название элемента. Картинка — «Картинка для анонса» или фото выбранного товара. «Когда показывать»: пока ничего не введено или когда поиск ничего не нашёл.');
$ensureProp($promos, 'CAPTION', ['NAME' => 'Надпись над заголовком', 'PROPERTY_TYPE' => 'S', 'SORT' => 110]);
$ensureProp($promos, 'TEXT', ['NAME' => 'Текст', 'PROPERTY_TYPE' => 'S', 'ROW_COUNT' => 2, 'COL_COUNT' => 60, 'SORT' => 120]);
$ensureProp($promos, 'LINK', ['NAME' => 'Ссылка (если пусто — на выбранный товар)', 'PROPERTY_TYPE' => 'S', 'COL_COUNT' => 60, 'SORT' => 130]);
$ensureProp($promos, 'PRODUCT', ['NAME' => 'Товар (фото и ссылка)', 'PROPERTY_TYPE' => 'E', 'LINK_IBLOCK_ID' => bt_iblock('catalog'), 'SORT' => 140]);
$ensureProp($promos, 'STYLE', ['NAME' => 'Цвет карточки', 'PROPERTY_TYPE' => 'L', 'SORT' => 150, 'VALUES' => [
    ['XML_ID' => 'lime', 'VALUE' => 'Лаймовый', 'SORT' => 10, 'DEF' => 'Y'], ['XML_ID' => 'esp', 'VALUE' => 'Кофейный', 'SORT' => 20], ['XML_ID' => 'dark', 'VALUE' => 'Чёрный', 'SORT' => 30]]]);
$ensureProp($promos, 'SHOW', ['NAME' => 'Когда показывать', 'PROPERTY_TYPE' => 'L', 'SORT' => 160, 'VALUES' => [
    ['XML_ID' => 'empty', 'VALUE' => 'Пока ничего не введено', 'SORT' => 10, 'DEF' => 'Y'], ['XML_ID' => 'none', 'VALUE' => 'Когда поиск ничего не нашёл', 'SORT' => 20]]]);
if ($empty($promos)) {
    $enum = fn(string $code, string $xml) => (int)(CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $promos, 'CODE' => $code, 'XML_ID' => $xml])->Fetch()['ID'] ?? 0);
    $prod = fn(string $code) => (int)(CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock('catalog'), '=CODE' => $code], false, false, ['ID'])->Fetch()['ID'] ?? 0);
    foreach ([
        ['Кофемашина бесплатно', 'Подписка', 'При заказе от 3 кг кофе в месяц. Обслуживание и ремонт наши.', '/podpiska/', 'jetinno-jl-05', 'lime', 'empty'],
        ['Эфиопия Оромия, Q 82,5', 'Зерно месяца', '2 687 ₽ за кг, от 30 кг — 1 940 ₽', '', 'botanica-efiopiya-oromiya', 'esp', 'empty'],
        ['Подберём машину под нагрузку', 'Калькулятор', 'Ответьте на 4 вопроса и увидите цену аренды', '/arenda-kofemashin/#calc', '', 'dark', 'empty'],
        ['Спросите менеджера', 'Подбор', 'Ответим за 5 минут в рабочее время и подберём под задачу', '/kontakty/#form', '', 'dark', 'none'],
    ] as $i => [$name, $cap, $text, $link, $pcode, $style, $show]) {
        $say("  предложение «{$name}»");
        $apply and $el->Add(['IBLOCK_ID' => $promos, 'NAME' => $name, 'ACTIVE' => 'Y', 'SORT' => ($i + 1) * 10, 'PROPERTY_VALUES' => [
            'CAPTION' => $cap, 'TEXT' => $text, 'LINK' => $link, 'PRODUCT' => $pcode ? $prod($pcode) : false,
            'STYLE' => $enum('STYLE', $style), 'SHOW' => $enum('SHOW', $show)]]) or ($apply and print('  ошибка: ' . $el->LAST_ERROR . "\n"));
    }
}

// главная: подписи вкладок «Аренда / Продажа кофемашин» и заголовок формы
$setProp = function (string $ibCode, string $code, string $name, string $value) use ($ensureProp, $apply, $say) {
    $ibId = bt_iblock($ibCode);
    $ensureProp($ibId, $code, ['NAME' => $name, 'PROPERTY_TYPE' => 'S', 'SORT' => 900]);
    $x = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ibId], false, ['nTopCount' => 1], ['ID', 'IBLOCK_ID', 'PROPERTY_' . $code])->Fetch();
    if ($x && trim((string)$x['PROPERTY_' . $code . '_VALUE']) === '') {
        $say("  {$ibCode}.{$code} = «{$value}»");
        $apply and CIBlockElement::SetPropertyValuesEx($x['ID'], $ibId, [$code => $value]);
    }
};
$setProp('main_rent', 'TAB_RENT', 'Вкладка 1: подпись', 'Аренда кофемашин');
$setProp('main_rent', 'TAB_SALE', 'Вкладка 2: подпись', 'Продажа кофемашин');
$setProp('main_about', 'FORM_TITLE', 'Форма: заголовок', 'Напишите нам');

// главная: блок карты (адрес и часы — из «Контактов»)
$map = $ensureIb('main', 'main_map', 'Карта: склад и самовывоз', 180, 'Блок с картой внизу главной. Адрес и часы работы берутся из «Контакты и реквизиты».');
$ensureProp($map, 'TITLE', ['NAME' => 'Заголовок', 'PROPERTY_TYPE' => 'S', 'SORT' => 110]);
$ensureProp($map, 'BTN_TEXT', ['NAME' => 'Текст ссылки', 'PROPERTY_TYPE' => 'S', 'SORT' => 120]);
$ensureProp($map, 'BTN_LINK', ['NAME' => 'Ссылка', 'PROPERTY_TYPE' => 'S', 'SORT' => 130]);
if ($empty($map)) {
    $say('  элемент «Карта»');
    $apply and $el->Add(['IBLOCK_ID' => $map, 'NAME' => 'Карта', 'ACTIVE' => 'Y', 'SORT' => 10,
        'PROPERTY_VALUES' => ['TITLE' => 'Склад и самовывоз', 'BTN_TEXT' => 'Как добраться →', 'BTN_LINK' => '/kontakty/']]);
}

if ($apply) {
    foreach ([$hints, $promos, $map, bt_iblock('main_rent'), bt_iblock('main_about')] as $id) {
        $id and CIBlock::clearIblockTagCache($id);
    }
}
echo "done\n";
