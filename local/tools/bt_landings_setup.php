<?php
// Инфоблок «Посадочные страницы» (тип «SEO») и пример — страница фильтра «Скидка» в каталоге.
// Пример помечен XML_ID «bt-demo». Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_landings_setup.php [show|apply]. Повторный запуск ничего не дублирует.

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

// свой тип «SEO» в «Контенте» — чтобы SEO-специалист видел посадочные отдельно от блоков страниц
if (!CIBlockType::GetByID('seo')->Fetch()) {
    $say('тип инфоблоков «SEO»');
    $apply and ((new CIBlockType())->Add(['ID' => 'seo', 'SECTIONS' => 'N', 'IN_RSS' => 'N', 'SORT' => 800,
        'LANG' => ['ru' => ['NAME' => 'SEO', 'ELEMENT_NAME' => 'Посадочная страница']]]) or die("ошибка типа SEO
"));
}
$ib = CIBlock::GetList([], ['=CODE' => 'seo_landings', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
$id = (int)($ib['ID'] ?? 0);
if ($id && $ib['IBLOCK_TYPE_ID'] !== 'seo') {
    $say('инфоблок «SEO: посадочные страницы» → тип «SEO»');
    $apply and (new CIBlock())->Update($id, ['IBLOCK_TYPE_ID' => 'seo', 'NAME' => 'Посадочные страницы', 'SORT' => 10]);
}
$ibDesc = 'Свои title, description, H1 и SEO-текст для любого адреса сайта. Адрес — без домена, например /catalog/kofe/ или /catalog/filter/badges-is-sale/apply/ (страница фильтра: выберите условия в каталоге и скопируйте адрес из строки браузера). Метки utm, сортировку и номер страницы указывать не нужно.';
if (!$id) {
    $say('инфоблок «SEO: посадочные страницы»');
    if ($apply) {
        $o = new CIBlock();
        $id = (int)$o->Add(['IBLOCK_TYPE_ID' => 'seo', 'CODE' => 'seo_landings', 'API_CODE' => 'SeoLandings', 'NAME' => 'Посадочные страницы', 'SORT' => 10,
            'SITE_ID' => ['s1'], 'ACTIVE' => 'Y', 'GROUP_ID' => ['2' => 'R'], 'VERSION' => 2, 'INDEX_ELEMENT' => 'N',
            'DESCRIPTION' => $ibDesc, 'DESCRIPTION_TYPE' => 'text'])
            or die('ошибка инфоблока: ' . $o->LAST_ERROR . "\n");
    }
} elseif (CIBlock::GetArrayByID($id, 'DESCRIPTION') !== $ibDesc) {
    $say('описание инфоблока: адрес фильтра в виде ЧПУ');
    $apply and (new CIBlock())->Update($id, ['DESCRIPTION' => $ibDesc, 'DESCRIPTION_TYPE' => 'text']);
}
$props = [
    'URL' => ['Адрес страницы (без домена, например /catalog/kofe/)', 'S', ['IS_REQUIRED' => 'Y', 'COL_COUNT' => 80]],
    'TITLE' => ['Title — заголовок вкладки и в поиске', 'S', ['COL_COUNT' => 80]],
    'DESCRIPTION' => ['Description — описание в поиске', 'S', ['COL_COUNT' => 80, 'ROW_COUNT' => 3]],
    'H1' => ['Заголовок H1 на странице', 'S', ['COL_COUNT' => 80]],
    'SEO_TEXT' => ['SEO-текст (вместо текста раздела или под содержимым страницы)', 'S:HTML', []],
];
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
    $say("  свойство {$code} «{$name}»");
    if ($apply) {
        [$pt, $ut] = array_pad(explode(':', $type), 2, null);
        (new CIBlockProperty())->Add(['IBLOCK_ID' => $id, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'PROPERTY_TYPE' => $pt, 'USER_TYPE' => $ut, 'ACTIVE' => 'Y'] + $extra)
            or die("ошибка свойства {$code}\n");
    }
}

// пример: страница фильтра «Метки: скидка» в корне каталога (ЧПУ: код свойства -is- код значения)
$cat = bt_iblock('catalog');
$prop = CIBlockProperty::GetList([], ['IBLOCK_ID' => $cat, 'CODE' => 'BADGES'])->Fetch();
$enum = $prop ? CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $prop['ID'], 'VALUE' => 'Скидка'])->Fetch() : null;
$filter = $enum ? '/catalog/filter/badges-is-' . mb_strtolower($enum['XML_ID']) . '/apply/' : '';
// пример со старым адресом фильтра (?btFilter_…) — переводим на ЧПУ
if ($id && $filter && ($old = CIBlockElement::GetList([], ['IBLOCK_ID' => $id, '=XML_ID' => 'bt-demo', '%PROPERTY_URL' => 'btFilter_'], false, false, ['ID'])->Fetch())) {
    $say("пример: адрес → {$filter}");
    $apply and CIBlockElement::SetPropertyValuesEx($old['ID'], $id, ['URL' => $filter]);
}
if ($id && $filter && !CIBlockElement::GetList([], ['IBLOCK_ID' => $id, '=XML_ID' => 'bt-demo'], [])) {
    $say("пример: {$filter}");
    if ($apply) {
        $el = new CIBlockElement();
        $eid = $el->Add(['IBLOCK_ID' => $id, 'NAME' => 'Чай и кофе со скидкой', 'XML_ID' => 'bt-demo', 'ACTIVE' => 'Y', 'SORT' => 100, 'PROPERTY_VALUES' => [
            'URL' => $filter,
            'TITLE' => 'Чай и кофе со скидкой — купить в Екатеринбурге | BEVERTEAM',
            'DESCRIPTION' => 'Чай и свежеобжаренный кофе BOTANICA со скидкой. Доставка по Екатеринбургу бесплатно от 3 000 ₽, отправка по России СДЭК.',
            'H1' => 'Чай и кофе со скидкой',
            'SEO_TEXT' => ['VALUE' => ['TYPE' => 'html', 'TEXT' => '<h2>Чай и кофе со скидкой</h2><p>Здесь собраны позиции, на которые сейчас действует скидка: чай, кофе BOTANICA и аксессуары. Список обновляется — заглядывайте чаще.</p>']],
        ]]);
        echo $eid ? "  ID {$eid}\n" : '  ошибка: ' . $el->LAST_ERROR . "\n";
    }
}
$apply and $id and CIBlock::clearIblockTagCache($id);
echo "done\n";
