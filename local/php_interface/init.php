<?php

require_once __DIR__ . '/include/bt.php';

// FAQPage для поисковиков — из блоков вопросов-ответов готовой страницы
AddEventHandler('main', 'OnEndBufferContent', 'bt_faq_ld');

// покупатели входят только по коду или через сервисы: пароль — только у администраторов
AddEventHandler('main', 'OnBeforeUserLogin', 'bt_password_login_guard');
AddEventHandler('main', 'OnBeforeUserChangePassword', 'bt_password_change_guard');
require_once __DIR__ . '/include/bt_mail.php';

// письма о заказе: состав и детали заказа полями #BT_*#
AddEventHandler('main', 'OnBeforeEventSend', 'bt_mail_before_send');

// вход через Яндекс ID, VK ID, T-Bank, Сбер, Альфа (модуль «Социальные сервисы»)
\Bitrix\Main\Loader::registerAutoLoadClasses(null, array_fill_keys(['BtOAuth', 'BtOAuthBank', 'BtOAuthTbank', 'BtOAuthSber', 'BtOAuthAlfa'], '/local/php_interface/include/bt_oauth.php'));
AddEventHandler('socialservices', 'OnAuthServicesBuildList', ['BtOAuth', 'services']);
AddEventHandler('socialservices', 'OnFindSocialservicesUser', ['BtOAuth', 'findUser']);

// тестовые домены хостинга закрыты от индексации
if (preg_match('/\.na4u\.ru$/i', $_SERVER['HTTP_HOST'] ?? '')) {
    header('X-Robots-Tag: noindex, nofollow');
}

// push-сервер не подключён: клиент Push & Pull на сайте не запускаем, иначе у авторизованных ошибка PULL_DISABLED в консоли
if (!defined('ADMIN_SECTION')) {
    define('BX_PULL_SKIP_INIT', true);
    // ядро BX посетителям не нужно: его тянут только клиент pull и счётчик модуля «Конверсия»
    $em = \Bitrix\Main\EventManager::getInstance();
    foreach ($em->findEventHandlers('main', 'OnProlog') as $key => $h) {
        if (in_array($h['TO_MODULE_ID'] ?? '', ['pull', 'conversion'], true)) {
            $em->removeEventHandler('main', 'OnProlog', $key);
        }
    }
    unset($em, $key, $h);
}
