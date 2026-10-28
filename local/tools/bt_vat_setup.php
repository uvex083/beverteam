<?php
// НДС 5 % (включён в цену) у товаров каталога и служб доставки — как в кассе Т-Банка: счёт показывает «в т.ч. НДС», цены не меняются.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_vat_setup.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Catalog;
use Bitrix\Main\Loader;
use Bitrix\Sale;

Loader::includeModule('catalog');
Loader::includeModule('sale');
$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
const RATE = 5;

$vat = Catalog\VatTable::getList(['filter' => ['=RATE' => RATE, '=EXCLUDE_VAT' => 'N'], 'select' => ['ID']])->fetch();
$vatId = (int)($vat['ID'] ?? 0);
if (!$vatId) {
    $say('ставка «НДС 5%» — создать');
    if ($apply) {
        $r = Catalog\VatTable::add(['ACTIVE' => 'Y', 'SORT' => 250, 'NAME' => 'НДС 5%', 'RATE' => RATE, 'EXCLUDE_VAT' => 'N']);
        $r->isSuccess() or die('ERROR: ' . implode('; ', $r->getErrorMessages()) . "\n");
        $vatId = (int)$r->getId();
    }
} else {
    $say("ставка «НДС 5%» есть (#$vatId)");
}

// новые товары каталога — со ставкой по умолчанию
foreach (Catalog\CatalogIblockTable::getList(['select' => ['IBLOCK_ID', 'VAT_ID']])->fetchAll() as $ib) {
    if ((int)$ib['VAT_ID'] !== $vatId) {
        $say("каталог ИБ #{$ib['IBLOCK_ID']}: ставка по умолчанию → 5%");
        $apply and CCatalog::Update((int)$ib['IBLOCK_ID'], ['VAT_ID' => $vatId]);
    }
}

$n = 0;
foreach (Catalog\ProductTable::getList(['select' => ['ID', 'VAT_ID', 'VAT_INCLUDED']])->fetchAll() as $p) {
    if ((int)$p['VAT_ID'] === $vatId && $p['VAT_INCLUDED'] === 'Y') {
        continue;
    }
    $n++;
    if ($apply) {
        $r = Catalog\Model\Product::update((int)$p['ID'], ['VAT_ID' => $vatId, 'VAT_INCLUDED' => 'Y']);
        $r->isSuccess() or print('  товар #' . $p['ID'] . ': ' . implode('; ', $r->getErrorMessages()) . "\n");
    }
}
$say("товары: НДС 5% включён в цену — $n шт.");

foreach (Sale\Delivery\Services\Table::getList(['filter' => ['=ACTIVE' => 'Y'], 'select' => ['ID', 'NAME', 'VAT_ID']])->fetchAll() as $d) {
    if ((int)$d['VAT_ID'] !== $vatId) {
        $say("доставка #{$d['ID']} «{$d['NAME']}»: НДС → 5%");
        $apply and Sale\Delivery\Services\Table::update((int)$d['ID'], ['VAT_ID' => $vatId]);
    }
}
$apply and BXClearCache(true);
echo "done\n";
