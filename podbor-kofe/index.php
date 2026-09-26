<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Подобрать кофе за минуту — BEVERTEAM');
$APPLICATION->SetPageProperty('description', 'Ответьте на 5 вопросов, и мы подберём кофе BOTANICA под вашу кофемашину, вкус и объём потребления. Подбор занимает минуту.');
$APPLICATION->SetPageProperty('keywords', 'подбор кофе, кофе BOTANICA');
$APPLICATION->SetTitle('Подберём кофе за минуту');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/podbor-kofe.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
