<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'О компании Beverteam — кофейное оборудование в Екатеринбурге');
$APPLICATION->SetPageProperty('description', 'Beverteam — продажа, аренда и сервис кофемашин Jetinno, кофе BOTANICA собственной обжарки и чай в Екатеринбурге с 2010 года.');
$APPLICATION->SetTitle('О компании');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/o-kompanii.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
