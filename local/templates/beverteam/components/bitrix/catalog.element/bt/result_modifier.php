<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */

$id = (int)$arResult['ID'];
$pr = $arResult['PROPERTIES'];
$val = fn(string $code) => is_array($pr[$code]['VALUE'] ?? null) ? implode(', ', $pr[$code]['VALUE']) : trim((string)($pr[$code]['VALUE'] ?? ''));
$html = fn(string $code) => trim((string)($pr[$code]['~VALUE']['TEXT'] ?? ''));

$arResult['BT'] = bt_product((string)$id) ?? [];

// галерея: основное фото + дополнительные
$files = array_filter(array_merge([$arResult['DETAIL_PICTURE']['ID'] ?? 0], (array)($pr['MORE_PHOTO']['VALUE'] ?? [])));
$arResult['BT_PHOTOS'] = array_map(fn($f) => [
    'big' => bt_img($f, 900, 900),
    'th' => bt_img($f, 116, 116, BX_RESIZE_IMAGE_EXACT),
    'og' => CFile::ResizeImageGet($f, ['width' => 900, 'height' => 900])['src'] ?? '',
], array_values($files));

// характеристики «Подробнее»: только заполненные
$place = implode(', ', array_filter([$val('COUNTRY'), $val('REGION')]));
$arResult['BT_SPECS'] = array_filter([
    'Оценка Q-грейдера' => $val('Q_SCORE'),
    'Степень обжарки' => $val('ROAST'),
    'Регион' => $place,
    'Способ обработки' => $val('PROCESSING'),
    'Состав' => $val('MIX'),
    'Вид чая' => $val('TEA_KIND'),
    'Вкус' => $val('TASTE'),
    'Действие' => $val('EFFECT'),
    'Фасовка' => $val('PACKING'),
    'Вес упаковки' => $val('NET_WEIGHT'),
    'Чашек в день' => $val('CUPS_PER_DAY'),
    'Габариты' => $val('DIMENSIONS'),
    'Экран' => $val('SCREEN'),
    'Артикул' => $val('ARTICLE'),
], fn($v) => $v !== '');
$arResult['BT_NOTES'] = $val('NOTES');
$arResult['BT_Q'] = $val('Q_SCORE');

// вкладка «Как готовить» / «Характеристики» — тексты со старого сайта
$arResult['BT_BREW'] = implode('', array_filter([
    ($t = $html('HOW_TO_BREW')) ? $t : '',
    ($t = $html('HOW_TO_USE')) ? $t : '',
    ($t = $html('STORAGE')) ? '<h3>Хранение</h3>' . $t : '',
]));
$arResult['BT_TECH'] = $html('TECH_SPECS');

// раздел товара и соседи по разделу для «Предыдущий / Следующий»
$sectionId = (int)$arResult['IBLOCK_SECTION_ID'];
$arResult['BT_SECTION'] = $sectionId ? CIBlockSection::GetList([], ['ID' => $sectionId], false, ['NAME', 'SECTION_PAGE_URL'])->GetNext() : null;
$ids = [];
$r = CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'SECTION_ID' => $sectionId, 'ACTIVE' => 'Y'], false, false, ['ID']);
while ($e = $r->Fetch()) {
    $ids[] = (string)$e['ID'];
}
$pos = array_search((string)$id, $ids, true);
$arResult['BT_PREV'] = $pos !== false && $pos > 0 ? bt_product($ids[$pos - 1]) : null;
$arResult['BT_NEXT'] = $pos !== false && $pos < count($ids) - 1 ? bt_product($ids[$pos + 1]) : null;

// «Рекомендуем»: заданные вручную или товары той же группы
$rec = array_map('strval', (array)($pr['RECOMMEND']['VALUE'] ?: []));
if (!$rec) {
    foreach (bt_catalog_data() as $group => $list) {
        if (in_array((string)$id, array_column($list, 'id'), true)) {
            $rec = array_slice(array_values(array_diff(array_column($list, 'id'), [(string)$id])), 0, 8);
            break;
        }
    }
}
$arResult['BT_REC'] = array_values(array_filter(array_map('bt_product', $rec)));

// отзывы о товаре
$arResult['BT_REVIEWS'] = [];
$r = CIBlockElement::GetList(['ACTIVE_FROM' => 'DESC', 'ID' => 'DESC'], ['IBLOCK_ID' => bt_iblock('reviews'), 'ACTIVE' => 'Y', 'PROPERTY_PRODUCT' => $id], false, false,
    ['ID', 'NAME', 'PREVIEW_TEXT', 'ACTIVE_FROM', 'DATE_CREATE', 'PROPERTY_RATING', 'PROPERTY_MACHINE', 'PROPERTY_VERIFIED']);
while ($e = $r->Fetch()) {
    $arResult['BT_REVIEWS'][] = ['a' => $e['NAME'], 'r' => (int)$e['PROPERTY_RATING_VALUE'], 't' => $e['PREVIEW_TEXT'],
        'd' => ConvertDateTime($e['ACTIVE_FROM'] ?: $e['DATE_CREATE'], 'YYYY-MM-DD'), 'm' => $e['PROPERTY_MACHINE_VALUE'], 'ok' => (bool)$e['PROPERTY_VERIFIED_VALUE']];
}

// бренд для разметки Product и данные для Open Graph (component_epilog работает и при кеше)
$arResult['BT_BRAND'] = preg_match('/botanica/i', $arResult['~NAME']) ? 'BOTANICA' : (preg_match('/jetinno/i', $arResult['~NAME']) ? 'Jetinno' : '');
$arResult['BT_OG'] = ['image' => $arResult['BT_PHOTOS'][0]['og'] ?? '', 'price' => $arResult['BT']['bulk'][0]['p'] ?? ($arResult['BT']['p'] ?? 0)];
$cp = $this->getComponent();
if ($cp) {
    $cp->arResultCacheKeys = array_merge($cp->arResultCacheKeys, ['BT_OG']);
}
