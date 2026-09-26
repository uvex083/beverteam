<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
$n = (int)($arResult['BT_COUNT'] ?? 0);
$word = ['товар', 'товара', 'товаров'][($n % 10 === 1 && $n % 100 !== 11) ? 0 : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 1 : 2)];
$APPLICATION->AddViewContent('bt_cat_count', $n . ' ' . $word);
