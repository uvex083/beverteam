<?php
// Товары в статьях журнала: свойство «Товары в статье» и демо-заполнение пустых по рубрике.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_products.php [show|apply]

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
$ibId = bt_iblock('journal');
$catId = bt_iblock('catalog');

if (!CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'PRODUCTS'])->Fetch()) {
    $say('свойство «Товары в статье»');
    $apply and ((new CIBlockProperty())->Add(['IBLOCK_ID' => $ibId, 'NAME' => 'Товары в статье', 'CODE' => 'PRODUCTS', 'PROPERTY_TYPE' => 'E',
        'LINK_IBLOCK_ID' => $catId, 'MULTIPLE' => 'Y', 'SORT' => 400, 'ACTIVE' => 'Y',
        'HINT' => 'Показываются под текстом статьи карточками с кнопкой «В корзину»']) or die("ошибка свойства\n"));
}

$pick = function (string $code, string $name = '', int $n = 3) use ($catId): array {
    $f = ['IBLOCK_ID' => $catId, 'ACTIVE' => 'Y', 'SECTION_CODE' => $code, 'INCLUDE_SUBSECTIONS' => 'Y'] + ($name !== '' ? ['%NAME' => $name] : []);
    $ids = [];
    $r = CIBlockElement::GetList(['SORT' => 'ASC'], $f, false, ['nTopCount' => $n], ['ID']);
    while ($x = $r->Fetch()) {
        $ids[] = (int)$x['ID'];
    }
    return $ids;
};
$bySection = [
    'vybor-kofe' => $pick('kofe'),
    'uhod-za-kofemashinoy' => $pick('aksessuary', 'чист') ?: $pick('aksessuary'),
    'novosti' => $pick('professionalnye-kofemashiny'),
];
$secCode = [];
$r = CIBlockSection::GetList([], ['IBLOCK_ID' => $ibId], false, ['ID', 'CODE']);
while ($s = $r->Fetch()) {
    $secCode[(int)$s['ID']] = $s['CODE'];
}

$r = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId], false, false, ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'PROPERTY_PRODUCTS']);
$seen = [];
while ($x = $r->Fetch()) {
    if (isset($seen[$x['ID']])) {
        continue;
    }
    $seen[$x['ID']] = 1;
    $ids = $bySection[$secCode[(int)$x['IBLOCK_SECTION_ID']] ?? ''] ?? [];
    if ($x['PROPERTY_PRODUCTS_VALUE'] || !$ids) {
        continue;
    }
    $say("товары → {$x['NAME']}");
    $apply and CIBlockElement::SetPropertyValuesEx($x['ID'], $ibId, ['PRODUCTS' => $ids]);
}
$apply and CIBlock::clearIblockTagCache($ibId);
echo "done\n";
