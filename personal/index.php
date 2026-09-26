<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Личный кабинет');
$APPLICATION->SetPageProperty('title', 'Личный кабинет — BEVERTEAM');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/personal-profile.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
