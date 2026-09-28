<?php
// Тестовые отзывы о товарах (с фото и без) — посмотреть, как выглядит вкладка «Отзывы». Помечены XML_ID «bt-demo-product».
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_product_reviews_demo.php [show|apply|delete]. Перед запуском сайта — delete.
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');
$mode = $argv[1] ?? 'show';
$ibId = bt_iblock('reviews');
$catId = bt_iblock('catalog');
$mark = 'bt-demo-product';

if ($mode === 'delete') {
    $r = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=XML_ID' => $mark], false, false, ['ID', 'NAME']);
    while ($x = $r->Fetch()) {
        echo "удаляю {$x['ID']} {$x['NAME']}\n";
        CIBlockElement::Delete($x['ID']);
    }
    CIBlock::clearIblockTagCache($ibId);
    exit("done\n");
}
if (CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=XML_ID' => $mark], [])) {
    exit("тестовые отзывы уже есть\n");
}

$product = fn(string $code) => CIBlockElement::GetList([], ['IBLOCK_ID' => $catId, '=CODE' => $code], false, false, ['ID', 'NAME', 'DETAIL_PICTURE', 'PREVIEW_PICTURE'])->Fetch();
// фото к отзыву — копии картинок товара
$photos = function (array $p, int $n) use ($catId): array {
    $ids = array_filter([(int)$p['DETAIL_PICTURE'], (int)$p['PREVIEW_PICTURE']]);
    $r = CIBlockElement::GetProperty($catId, $p['ID'], [], ['CODE' => 'MORE_PHOTO']);
    while ($x = $r->Fetch()) {
        $x['VALUE'] and $ids[] = (int)$x['VALUE'];
    }
    return array_map(fn($id) => ['VALUE' => CFile::MakeFileArray($id)], array_slice(array_values(array_unique($ids)), 0, $n));
};
$yes = (int)(CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'VERIFIED', 'XML_ID' => 'Y'])->Fetch()['ID'] ?? 0);

$list = [
    ['botanica-braziliya-santos', 'Марина Коваленко', 5, 'Jetinno JL15 VIVA', 2, true, '-3 days',
        'Беру уже третий раз для офиса. Мягкий, с ореховой сладостью, без кислинки — капучино получается как в кофейне. Помол поставили на 4, крепость на максимум. Зёрна свежие, дата обжарки на пачке — неделя назад.'],
    ['botanica-braziliya-santos', 'Алексей', 4, '', 0, false, '-9 days',
        'Хороший кофе на каждый день, в эспрессо немного горчит, в американо и с молоком — отлично. Доставили на следующий день.'],
    ['jetinno-jl-32', 'Кофейня «Утро»', 5, '', 1, true, '-20 days',
        'Стоит у нас на точке самообслуживания третий месяц. Терминал оплаты подключили без проблем, телеметрия показывает остатки — удобно. Инженер приезжал на настройку один раз.'],
];
foreach ($list as [$code, $name, $rating, $machine, $nPh, $verified, $ago, $text]) {
    $p = $product($code);
    if (!$p) {
        echo "нет товара {$code}\n";
        continue;
    }
    echo "{$p['NAME']}: {$name}, {$rating}★, фото {$nPh}\n";
    if ($mode !== 'apply') {
        continue;
    }
    $el = new CIBlockElement();
    $id = $el->Add(['IBLOCK_ID' => $ibId, 'XML_ID' => $mark, 'ACTIVE' => 'Y', 'NAME' => $name, 'SORT' => 500,
        'ACTIVE_FROM' => ConvertTimeStamp(strtotime($ago), 'FULL'), 'PREVIEW_TEXT' => $text, 'PREVIEW_TEXT_TYPE' => 'text',
        'PROPERTY_VALUES' => ['RATING' => $rating, 'PRODUCT' => $p['ID'], 'MACHINE' => $machine, 'EMAIL' => 'uvex083@yandex.ru',
            'PHOTOS' => $nPh ? $photos($p, $nPh) : false, 'VERIFIED' => $verified && $yes ? $yes : false]]);
    echo $id ? "  ID {$id}\n" : '  ошибка: ' . $el->LAST_ERROR . "\n";
}
$mode === 'apply' and CIBlock::clearIblockTagCache($ibId);
echo "done\n";
