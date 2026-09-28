<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Main\Web\Json;

/** @global CMain $APPLICATION */
$co = bt_contacts();
$asset = Asset::getInstance();
// товары для поиска, корзины и сравнения в ui.js — до подключения скриптов
$asset->addString('<script>window.BT_DATA=' . Json::encode(bt_catalog_data()) . ';window.BT_CATS=' . Json::encode(bt_mega_cats()) . ';window.BT_BASKET=' . Json::encode(bt_basket_state()) . ';window.BT_POSTS=' . Json::encode(bt_posts()) . ';window.BT_SRCH=' . Json::encode(bt_search_cfg()) . bt_map_js() . ';window.BT_PAGES=' . Json::encode(bt_search_pages())
    . ';window.BT_UTP=' . Json::encode(array_map(fn($u) => [$u['icon'], $u['name'], $u['text']], bt_list('main_utp_items'))) . ';window.BT_USER=' . Json::encode(bt_user_js()) . ';window.BT_IDP=' . Json::encode(bt_idp()) . ';window.BT_SID=' . Json::encode(bitrix_sessid())
    . (isset($_GET['auth_service_error']) ? ';window.BT_AUTH_ERR=' . Json::encode(bt_idp_error()) : '') . ';</script>', false, AssetLocation::AFTER_CSS);
$asset->addCss(SITE_TEMPLATE_PATH . '/vendor/swiper-bundle.min.css');
$asset->addCss(SITE_TEMPLATE_PATH . '/css/ui.css');
$asset->addJs(SITE_TEMPLATE_PATH . '/vendor/swiper-bundle.min.js');
$asset->addJs(SITE_TEMPLATE_PATH . '/js/ui.js');

$msgr = function (string $cls = '') {
    $html = '';
    foreach (bt_messengers() as [, $name, $href, $svg]) {
        $html .= '<a href="' . htmlspecialcharsbx($href) . '" title="' . htmlspecialcharsbx($name) . '" aria-label="' . htmlspecialcharsbx($name) . '" rel="nofollow noopener" target="_blank">' . $svg . '</a>';
    }
    return '<div class="msgr ' . $cls . '">' . $html . '</div>';
};
$logo = '<span class="brand__m">B</span><span class="brand__t">BEVERTEAM</span>';
?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#0E0E0C">
<title><?php $APPLICATION->ShowTitle() ?></title>
<link rel="preload" href="<?= SITE_TEMPLATE_PATH ?>/fonts/Unbounded-3959e2.woff2" as="font" type="font/woff2" crossorigin><link rel="preload" href="<?= SITE_TEMPLATE_PATH ?>/fonts/Manrope-e5e254.woff2" as="font" type="font/woff2" crossorigin><link rel="preload" href="<?= SITE_TEMPLATE_PATH ?>/fonts/JetBrainsMono-6ca536.woff2" as="font" type="font/woff2" crossorigin>
<link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="/favicon.svg">
<meta name="yandex-verification" content="36480871fc540413">
<?php $APPLICATION->AddBufferContent('bt_og') ?><?= bt_org_ld() ?>
<?php $APPLICATION->ShowHead() ?>
<?= bt_metrika() ?>
</head>
<body>
<?php $APPLICATION->ShowPanel() ?>
<div id="app">
<header class="hdr" id="hdr">
  <div class="wrap hdr__top">
    <button class="burger" id="burger" aria-label="Меню" aria-expanded="false" aria-controls="drawer"><?= bt_icon('burger') . bt_icon('close') ?></button>
    <a class="brand" href="/" title="Чай и кофе для дома и бизнеса BEVERTEAM" aria-label="Чай и кофе для дома и бизнеса BEVERTEAM — на главную"><?= $logo ?></a>
    <button class="catbtn" id="catbtn" aria-expanded="false" aria-controls="mega"><span class="catbtn__i"><?= bt_icon('cat') . bt_icon('close') ?></span><span class="lbl">Каталог</span></button>
    <div class="hdr__msgr"><?= $msgr() ?></div>
    <div class="hdr__acts">
      <a class="hdr__tel" href="<?= $co['phone1_href'] ?? '' ?>"><?= $co['phone1'] ?? '' ?></a>
      <button class="hact" id="srchBtn" aria-label="Поиск по сайту" aria-haspopup="dialog"><?= bt_icon('search') ?><span>Поиск</span></button>
      <a class="hact" href="/personal/"><?= bt_icon('user') ?><span>Кабинет</span></a>
      <a class="hact" href="/personal/favorites/"><?= bt_icon('heart') ?><span>Избранное</span></a>
      <a class="hact hact--cmp" href="/catalog/compare/" aria-label="Сравнение товаров"><?= bt_icon('compare') ?><span class="cnt" hidden>0</span><span>Сравнение</span></a>
      <a class="hact" href="/personal/cart/" aria-label="Корзина"><?= bt_icon('cart') ?><span class="cnt" hidden>0</span><span>Корзина</span></a>
    </div>
  </div>
  <div class="wrap hdr__nav">
    <?php $APPLICATION->IncludeComponent('bitrix:menu', 'bt_top', [
        'ROOT_MENU_TYPE' => 'top', 'MAX_LEVEL' => 1, 'USE_EXT' => 'Y',
        'MENU_CACHE_TYPE' => 'A', 'MENU_CACHE_TIME' => 3600, 'MENU_CACHE_USE_GROUPS' => 'N', 'DELAY' => 'N', 'ALLOW_MULTI_SELECT' => 'N',
    ], false, ['HIDE_ICONS' => 'Y']) ?>
    <span class="r"><b><?= $co['city'] ?? '' ?></b>, <?= $co['street'] ?? '' ?> · <?= $co['hours'] ?? '' ?></span>
  </div>
  <div class="mega" id="mega"><div class="wrap mega__in">
    <div class="mega__cats" id="megaCats"></div>
    <div class="mega__panel" id="megaPanel"></div>
  </div></div>
</header>
<div class="drawer" id="drawer" aria-label="Меню">
  <div class="drawer__p">
    <?php $APPLICATION->IncludeComponent('bitrix:menu', 'bt_drawer', [
        'ROOT_MENU_TYPE' => 'drawer', 'MAX_LEVEL' => 1, 'USE_EXT' => 'Y',
        'MENU_CACHE_TYPE' => 'A', 'MENU_CACHE_TIME' => 3600, 'MENU_CACHE_USE_GROUPS' => 'N', 'DELAY' => 'N', 'ALLOW_MULTI_SELECT' => 'N',
    ], false, ['HIDE_ICONS' => 'Y']) ?>
  </div>
</div>
<?php if ($APPLICATION->GetDirProperty('bt_layout') === 'info'): // текстовые страницы для покупателей: крошки, заголовок, боковое меню ?>
<div class="wrap infop">
  <?php $APPLICATION->AddBufferContent([$APPLICATION, 'GetNavChain'], false, 0, SITE_TEMPLATE_PATH . '/components/bitrix/breadcrumb/bt/template.php', true, false) ?>
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1></div>
  <div class="info-l">
    <?php $APPLICATION->IncludeComponent('bitrix:menu', 'bt_info', [
        'ROOT_MENU_TYPE' => 'info', 'MAX_LEVEL' => 1, 'USE_EXT' => 'N',
        'MENU_CACHE_TYPE' => 'A', 'MENU_CACHE_TIME' => 3600, 'MENU_CACHE_USE_GROUPS' => 'N', 'DELAY' => 'N', 'ALLOW_MULTI_SELECT' => 'N',
    ], false, ['HIDE_ICONS' => 'Y']) ?>
    <div class="prose">
<?php endif ?>
