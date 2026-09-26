<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Аренда кофемашин в Екатеринбурге — цены от 3 500 ₽ | «Бэвертим»');
$APPLICATION->SetPageProperty('description', 'Кофемашины в аренду в Екатеринбурге для офиса, кафе, ресторана и мероприятий. Модели Jetinno от 3 500 ₽. Подбор оборудования, ремонт и сервис от компании «Бэвертим» — Beverteam');
$APPLICATION->SetPageProperty('keywords', 'Аренда кофемашин в Екатеринбурге');
$APPLICATION->SetTitle('Магазин чая и кофе');
$APPLICATION->AddHeadString('<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"BEVERTEAM","url":"https://beverteam.ru/","potentialAction":{"@type":"SearchAction","target":"https://beverteam.ru/search/?q={search_term_string}","query-input":"required name=search_term_string"}}</script>');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/index.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
