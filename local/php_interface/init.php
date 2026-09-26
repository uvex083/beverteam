<?php

// тестовые домены хостинга закрыты от индексации
if (preg_match('/\.na4u\.ru$/i', $_SERVER['HTTP_HOST'] ?? '')) {
    header('X-Robots-Tag: noindex, nofollow');
}
