<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
/** @global CMain $APPLICATION */
// страница специалиста /o-kompanii/komanda/<код>/ (правило в urlrewrite.php); без адреса или биографии у специалиста — 404
$code = (string)($_GET['CODE'] ?? '');
$expert = null;
foreach (bt_experts() as $x) {
    if ($x['url'] === '/o-kompanii/komanda/' . $code . '/') {
        $expert = $x;
    }
}
if (!$expert) {
    require $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    return;
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$lead = trim(preg_replace('/\s+/u', ' ', strip_tags($expert['bio'])));
$APPLICATION->SetPageProperty('title', $expert['name'] . ($expert['job'] !== '' ? ' — ' . $expert['job'] : '') . ' | BEVERTEAM');
$APPLICATION->SetPageProperty('description', mb_strlen($lead) > 160 ? rtrim(mb_substr($lead, 0, 157)) . '…' : $lead);
$APPLICATION->SetTitle($expert['name']);
$APPLICATION->AddChainItem('Команда', '/o-kompanii/#team');
$APPLICATION->AddChainItem($expert['name']);
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/ekspert.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
