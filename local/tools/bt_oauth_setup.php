<?php
// Вход через сервисы: включает Яндекс ID, VK ID, T-Bank, Сбер, Альфа в модуле «Социальные сервисы», остальные сервисы модуля выключает,
// запрещает вход через сервисы администраторам и регистрацию силами модуля (аккаунты создаёт BtOAuth::findUser). Ключи не трогает.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_oauth_setup.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
if (!CModule::IncludeModule('socialservices')) {
    die("ERROR: модуль socialservices не установлен\n");
}

$m = new CSocServAuthManager();
$want = [];
foreach (array_keys($m->GetAuthServices('')) as $id) {
    $want[$id] = isset(BtOAuth::IDP[$id]) ? 'Y' : 'N';
}
// порядок кнопок — как в BtOAuth::IDP
$want = array_merge(array_intersect_key(array_fill_keys(array_keys(BtOAuth::IDP), 'Y'), $want), $want);
$opts = ['auth_services' => serialize($want), 'group_deny_auth' => '1', 'allow_registration' => 'N'];
foreach ($opts as $k => $v) {
    $cur = COption::GetOptionString('socialservices', $k, '');
    if ($cur === $v) {
        $say("socialservices.$k — уже как нужно");
        continue;
    }
    $say("socialservices.$k: " . ($k === 'auth_services' ? 'включены ' . implode(', ', array_keys(BtOAuth::IDP)) . ', остальные выключены' : "'$cur' → '$v'"));
    if ($apply) {
        COption::SetOptionString('socialservices', $k, $v);
    }
}

// ключи: только заполнено или нет, значения не печатаем
$keys = ['YandexOAuth' => ['yandex_appid', 'yandex_appsecret'], 'VKontakte' => ['vkontakte_appid', 'vkontakte_appsecret'],
    'BtTbank' => ['bt_tbank_id', 'bt_tbank_secret'], 'BtSber' => ['bt_sber_id', 'bt_sber_secret'], 'BtAlfa' => ['bt_alfa_id', 'bt_alfa_secret']];
foreach ($keys as $id => $names) {
    $filled = array_map(fn($n) => $n . (trim(CSocServAuth::GetOption($n)) !== '' ? ' заполнен' : ' пуст'), $names);
    print("$id: " . implode(', ', $filled) . "\n");
}
print('Кнопки на сайте сейчас: ' . (implode(', ', array_column(BtOAuth::buttons(), 't')) ?: 'нет') . "\n");
