<?php

use Bitrix\Main\Loader;

// ID инфоблока по символьному коду — ID на dev и проде могут не совпадать
function bt_iblock(string $code): int
{
    static $ids = [];
    if (!isset($ids[$code])) {
        Loader::includeModule('iblock');
        $ib = \Bitrix\Iblock\IblockTable::getList([
            'filter' => ['=CODE' => $code],
            'select' => ['ID'],
            'cache' => ['ttl' => 86400],
        ])->fetch();
        $ids[$code] = $ib ? (int)$ib['ID'] : 0;
    }
    return $ids[$code];
}

// Контакты и реквизиты: один элемент инфоблока site_contacts, единый источник для шапки, футера и страниц
function bt_contacts(): array
{
    static $co;
    if ($co !== null) {
        return $co;
    }
    $co = [];
    $ibId = bt_iblock('site_contacts');
    if (!$ibId) {
        return $co;
    }
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache(86400, 'bt_contacts', '/bt/contacts')) {
        return $co = $cache->getVars();
    }
    $cache->startDataCache();
    $GLOBALS['CACHE_MANAGER']->StartTagCache('/bt/contacts');
    $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ibId);
    $el = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ibId, 'ACTIVE' => 'Y'], false, ['nTopCount' => 1], ['ID', 'IBLOCK_ID', 'NAME'])->GetNextElement();
    if ($el) {
        foreach ($el->GetProperties() as $code => $p) {
            $co[strtolower($code)] = is_array($p['VALUE']) ? $p['VALUE'] : trim((string)$p['VALUE']);
        }
        foreach (['phone1', 'phone2'] as $k) {
            $co[$k . '_href'] = 'tel:+' . preg_replace('/\D/', '', $co[$k] ?? '');
        }
    }
    $GLOBALS['CACHE_MANAGER']->EndTagCache();
    $cache->endDataCache($co);
    return $co;
}

// Мессенджеры из контактов: [код иконки, название, ссылка], пустые ссылки не выводятся
function bt_messengers(): array
{
    $co = bt_contacts();
    $list = [];
    foreach (['tg' => 'Telegram', 'wa' => 'WhatsApp', 'max' => 'MAX', 'vk' => 'ВКонтакте'] as $code => $name) {
        $list[] = [$code, $name, $co[$code] ?? ''];
    }
    return $list;
}

function bt_icon(string $name): string
{
    static $icons;
    $icons ??= include $_SERVER['DOCUMENT_ROOT'] . '/local/templates/beverteam/include/icons.php';
    return $icons[$name] ?? '';
}

// Путь к файлу шаблона с версией по времени изменения — браузер не держит старый css/js
function bt_asset(string $path): string
{
    $file = $_SERVER['DOCUMENT_ROOT'] . $path;
    return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}
