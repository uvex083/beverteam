<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Контакты Beverteam в Екатеринбурге — адрес и телефоны');
$APPLICATION->SetPageProperty('description', 'Контакты Beverteam в Екатеринбурге: адрес — ул. Колокольная, 31А, телефоны +7 904 384-13-88 и +7 995 541-93-99, beteam@inbox.ru');
$APPLICATION->SetPageProperty('keywords', 'Контакты Beverteam');
$APPLICATION->SetTitle('Контакты');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/kontakty.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
