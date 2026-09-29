<?php
// Аренда: поле «Особенность» у моделей и метки #RENT_FROM# / #RENT_FREE_KG# вместо зашитых «от 3 500 ₽» и «от 3 кг» в текстах блоков.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_rent_setup.php [show|apply]. Повторный запуск ничего не меняет.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");

$rentId = bt_iblock('rent') or die("нет ИБ rent\n");
if (!CIBlockProperty::GetList([], ['IBLOCK_ID' => $rentId, 'CODE' => 'FEATURE'])->Fetch()) {
    $say('ИБ «Модели в аренду»: свойство FEATURE «Особенность»');
    $apply and (new CIBlockProperty())->Add(['IBLOCK_ID' => $rentId, 'CODE' => 'FEATURE', 'NAME' => 'Особенность', 'PROPERTY_TYPE' => 'S', 'SORT' => 205, 'ACTIVE' => 'Y',
        'HINT' => 'Коротко, выводится на карточке через точку: например «на сухом молоке»']);
}
$ib = CIBlock::GetArrayByID($rentId);
if (!str_contains((string)$ib['DESCRIPTION'], '#RENT_')) {
    $say('ИБ «Модели в аренду»: описание про метки');
    $apply and (new CIBlock())->Update($rentId, ['DESCRIPTION_TYPE' => 'text', 'DESCRIPTION' => trim($ib['DESCRIPTION'] . "\n\n"
        . 'Цены и объёмы моделей подставляются в тексты сайта сами. В любом тексте в админке можно писать метки: '
        . '#RENT_FROM# — минимальная цена аренды («3 500 ₽»), #RENT_FREE_KG# — минимальный объём кофе для бесплатной аренды («3»), '
        . '#RENT_MODELS# — список моделей («Jetinno Jl 05, Jl 15 (VIVA) и JL 36»). Выключили или добавили модель — тексты обновятся.')]);
}

// где цифра — минимум по всем моделям: [код ИБ, название элемента или '' для всех элементов ИБ]
$targets = [['main_facts', ''], ['main_utp_items', ''], ['search_promos', ''], ['sub_head', ''], ['sub_how', ''], ['sub_faq', ''], ['rent_top', ''], ['rent_terms', ''],
    ['rent_faq', 'Сколько стоит аренда кофемашины?'], ['journal', 'Аренда кофемашин Jetinno — от 3 500 ₽ в месяц']];
$re = ['/от\s3[\s\x{00A0}]?500\s?(?:₽|руб(?:лей|\.)?)/u' => 'от #RENT_FROM#', '/от\s3[\s\x{00A0}]кг/u' => 'от #RENT_FREE_KG# кг'];
$fix = fn(string $s) => preg_replace(array_keys($re), array_values($re), $s);
$el = new CIBlockElement();
foreach ($targets as [$code, $name]) {
    $id = bt_iblock($code);
    if (!$id) {
        continue;
    }
    $r = CIBlockElement::GetList([], ['IBLOCK_ID' => $id] + ($name !== '' ? ['=NAME' => $name] : []), false, false, ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT']);
    while ($o = $r->GetNextElement()) {
        $f = $o->GetFields();
        $upd = [];
        foreach (['NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT'] as $k) {
            $v = (string)$f['~' . $k];
            if ($v !== '' && ($n = $fix($v)) !== $v) {
                $upd[$k] = $n;
            }
        }
        $props = [];
        foreach ($o->GetProperties() as $pc => $p) {
            if ($p['PROPERTY_TYPE'] !== 'S' || $p['MULTIPLE'] === 'Y') {
                continue;
            }
            $html = ($p['USER_TYPE'] ?? '') === 'HTML';
            $v = (string)($html ? ($p['~VALUE']['TEXT'] ?? '') : $p['~VALUE']);
            if ($v !== '' && ($n = $fix($v)) !== $v) {
                $props[$pc] = $html ? ['VALUE' => ['TEXT' => $n, 'TYPE' => $p['~VALUE']['TYPE'] ?? 'HTML']] : $n;
            }
        }
        if (!$upd && !$props) {
            continue;
        }
        foreach ($upd as $k => $v) {
            $say("#{$f['ID']} $code $k: " . mb_substr(strip_tags($v), 0, 160));
        }
        foreach ($props as $k => $v) {
            $say("#{$f['ID']} $code $k: " . mb_substr(strip_tags(is_array($v) ? $v['VALUE']['TEXT'] : $v), 0, 160));
        }
        if ($apply) {
            $upd and $el->Update($f['ID'], $upd);
            $props and CIBlockElement::SetPropertyValuesEx($f['ID'], $id, $props);
        }
    }
    $apply and CIBlock::clearIblockTagCache($id);
}
echo "done\n";
