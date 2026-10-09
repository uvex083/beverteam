<?php
// Оптовая лесенка цен для всего кофе: шаги и скидки — как у Эфиопии Оромия на старом сайте. Товар, у которого лесенка уже есть, не трогаем.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_bulk_setup.php [show|apply]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Catalog\Model\Price;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('catalog');

$apply = ($argv[1] ?? 'show') === 'apply';
$steps = [[1, 4, 0], [5, 9, 18], [10, 19, 22], [20, 29, 26], [30, null, 28]];

$ib = bt_iblock('catalog');
$sec = \CIBlockSection::GetList([], ['IBLOCK_ID' => $ib, '=CODE' => 'kofe'], false, ['ID'])->Fetch();
if (!$sec) {
    exit("раздел kofe не найден\n");
}
$base = (int)\CCatalogGroup::GetBaseGroup()['ID'];
$r = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ib, 'ACTIVE' => 'Y', 'SECTION_ID' => $sec['ID'], 'INCLUDE_SUBSECTIONS' => 'Y'], false, false, ['ID', 'NAME']);
while ($el = $r->Fetch()) {
    $prices = \Bitrix\Catalog\PriceTable::getList(['filter' => ['=PRODUCT_ID' => $el['ID'], '=CATALOG_GROUP_ID' => $base], 'select' => ['ID', 'PRICE', 'CURRENCY', 'QUANTITY_FROM'], 'order' => ['QUANTITY_FROM' => 'ASC']])->fetchAll();
    if (count($prices) !== 1 || (float)$prices[0]['PRICE'] <= 0) {
        echo "пропуск  {$el['ID']} {$el['NAME']}: цен " . count($prices) . ($prices ? ', 1 кг ' . (float)$prices[0]['PRICE'] : '') . "\n";
        continue;
    }
    $p = (float)$prices[0]['PRICE'];
    $plan = array_map(fn($s) => [$s[0], $s[1], round($p * (100 - $s[2]) / 100)], $steps);
    echo "{$el['ID']} {$el['NAME']}: " . implode(' · ', array_map(fn($s) => "от {$s[0]} — {$s[2]}", $plan)) . "\n";
    if (!$apply) {
        continue;
    }
    $res = Price::delete($prices[0]['ID']);
    if (!$res->isSuccess()) {
        echo '  ошибка удаления: ' . implode('; ', $res->getErrorMessages()) . "\n";
        continue;
    }
    foreach ($plan as [$from, $to, $price]) {
        $res = Price::add(['PRODUCT_ID' => $el['ID'], 'CATALOG_GROUP_ID' => $base, 'PRICE' => $price, 'CURRENCY' => $prices[0]['CURRENCY'], 'QUANTITY_FROM' => $from, 'QUANTITY_TO' => $to]);
        if (!$res->isSuccess()) {
            echo '  ошибка: ' . implode('; ', $res->getErrorMessages()) . "\n";
        }
    }
}
if ($apply) {
    BXClearCache(true);
    $GLOBALS['CACHE_MANAGER']->CleanAll();
}
echo $apply ? "применено\n" : "show: ничего не менял\n";
