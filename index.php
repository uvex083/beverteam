<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// title и description — вкладка SEO элемента «Первый экран» в админке; #RENT_FROM# — минимальная цена аренды
bt_page_seo('main_hero', 'Аренда кофемашин в Екатеринбурге — цены от #RENT_FROM# | «Бэвертим»',
    'Кофемашины в аренду в Екатеринбурге для офиса, кафе, ресторана и мероприятий. Модели Jetinno от #RENT_FROM#. Подбор оборудования, ремонт и сервис от компании «Бэвертим» — Beverteam');
$APPLICATION->SetPageProperty('keywords', 'Аренда кофемашин в Екатеринбурге');
$APPLICATION->SetTitle('Магазин чая и кофе');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/index.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
