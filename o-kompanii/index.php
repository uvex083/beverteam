<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'О компании Beverteam — кофейное оборудование в Екатеринбурге');
$APPLICATION->SetPageProperty('description', 'Beverteam — поставщик кофейного оборудования и решений для вендинга и HoReCa с 2007 года. Продажа, аренда и сервис кофемашин в Екатеринбурге');
$APPLICATION->SetPageProperty('keywords', 'О компании Beverteam');
$APPLICATION->SetTitle('О компании');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/o-kompanii.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
