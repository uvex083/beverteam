<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Счета и документы');
$APPLICATION->SetPageProperty('title', 'Счета и документы — BEVERTEAM');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/personal-docs.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
