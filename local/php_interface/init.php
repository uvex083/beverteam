<?php

require_once __DIR__ . '/include/bt.php';

// тестовые домены хостинга закрыты от индексации
if (preg_match('/\.na4u\.ru$/i', $_SERVER['HTTP_HOST'] ?? '')) {
    header('X-Robots-Tag: noindex, nofollow');
}

// push-сервер не подключён: клиент Push & Pull на сайте не запускаем, иначе у авторизованных ошибка PULL_DISABLED в консоли
if (!defined('ADMIN_SECTION')) {
    define('BX_PULL_SKIP_INIT', true);
}
