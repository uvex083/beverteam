<?php
// Свежий sessid для страницы, простоявшей дольше жизни сессии (повтор запроса в ui.js)
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['sessid' => bitrix_sessid()]);
