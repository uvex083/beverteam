<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Подписка на кофе');
$APPLICATION->SetPageProperty('title', 'Подписка на кофе — BEVERTEAM');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/personal-podpiska.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
