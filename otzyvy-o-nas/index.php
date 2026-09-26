<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Отзывы о нас | Beverteam');
$APPLICATION->SetPageProperty('description', 'Отзывы о нас — компания Beverteam. Кофемашины в аренду в Екатеринбурге для офиса, кафе, ресторана и мероприятий');
$APPLICATION->SetTitle('Отзывы о нас');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/otzyvy-o-nas.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
