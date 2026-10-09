<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */

$id = (int)$arResult['ID'];
$pr = $arResult['PROPERTIES'];
$val = fn(string $code) => is_array($pr[$code]['~VALUE'] ?? null) ? implode(', ', $pr[$code]['~VALUE']) : trim((string)($pr[$code]['~VALUE'] ?? ''));
$html = fn(string $code) => trim((string)($pr[$code]['~VALUE']['TEXT'] ?? ''));

$arResult['BT'] = bt_product((string)$id) ?? [];

// галерея: основное фото + дополнительные
$files = array_filter(array_merge([$arResult['DETAIL_PICTURE']['ID'] ?? 0], (array)($pr['MORE_PHOTO']['VALUE'] ?? [])));
$arResult['BT_PHOTOS'] = array_map(fn($f) => [
    'big' => bt_img($f, 900, 900),
    'full' => bt_img($f, 1800, 1800),
    'th' => bt_img($f, 116, 116, BX_RESIZE_IMAGE_EXACT),
    'og' => CFile::ResizeImageGet($f, ['width' => 900, 'height' => 900])['src'] ?? '',
], array_values($files));

// характеристики «Подробнее»: заполненные свойства с галочкой «Показывать на детальной странице» — название и порядок из настроек свойства в админке
$show = (array)\Bitrix\Iblock\Model\PropertyFeature::getDetailPageShowProperties((int)$arResult['IBLOCK_ID'], ['CODE' => 'Y']);
$arResult['BT_SPECS'] = [];
foreach ($pr as $code => $p) {
    if (in_array($code, $show, true) && in_array($p['PROPERTY_TYPE'], ['S', 'L', 'N'], true) && !$p['USER_TYPE'] && ($v = $val($code)) !== '') {
        $arResult['BT_SPECS'][htmlspecialcharsbx($p['NAME'])] = $v;
    }
}
$arResult['BT_NOTES'] = $val('NOTES');
$arResult['BT_Q'] = $val('Q_SCORE');

// вкладка «Как готовить» / «Характеристики» — тексты со старого сайта
$arResult['BT_BREW'] = implode('', array_filter([
    ($t = $html('HOW_TO_BREW')) ? $t : '',
    ($t = $html('HOW_TO_USE')) ? $t : '',
    ($t = $html('STORAGE')) ? '<h3>Хранение</h3>' . $t : '',
]));
$arResult['BT_TECH'] = $html('TECH_SPECS');

// раздел товара
$sectionId = (int)$arResult['IBLOCK_SECTION_ID'];
$arResult['BT_SECTION'] = $sectionId ? CIBlockSection::GetList([], ['ID' => $sectionId], false, ['NAME', 'SECTION_PAGE_URL'])->GetNext() : null;

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
    ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'ACTIVE_FROM', 'DATE_CREATE', 'PROPERTY_RATING', 'PROPERTY_MACHINE', 'PROPERTY_VERIFIED']);
while ($e = $r->Fetch()) {
    $ph = [];
    $pr = CIBlockElement::GetProperty($e['IBLOCK_ID'], $e['ID'], [], ['CODE' => 'PHOTOS']);
    while ($x = $pr->Fetch()) {
        if ($x['VALUE'] && ($f = CFile::GetFileArray($x['VALUE']))) {
            $ph[] = ['s' => CFile::ResizeImageGet($f, ['width' => 240, 'height' => 240], BX_RESIZE_IMAGE_EXACT)['src'], 'f' => CFile::ResizeImageGet($f, ['width' => 1600, 'height' => 1600])['src']];
        }
    }
    $arResult['BT_REVIEWS'][] = ['id' => (int)$e['ID'], 'a' => $e['NAME'], 'r' => (int)$e['PROPERTY_RATING_VALUE'], 't' => $e['PREVIEW_TEXT'],
        'd' => ConvertDateTime($e['ACTIVE_FROM'] ?: $e['DATE_CREATE'], 'YYYY-MM-DD'), 'm' => $e['PROPERTY_MACHINE_VALUE'], 'ok' => (bool)$e['PROPERTY_VERIFIED_VALUE'], 'ph' => $ph];
}
// отзыв опубликовали в админке — кэш карточки сбрасывается по тегу ИБ отзывов
defined('BX_COMP_MANAGED_CACHE') && $GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . bt_iblock('reviews'));

// бренд для разметки Product и данные для Open Graph (component_epilog работает и при кеше)
$arResult['BT_BRAND'] = preg_match('/botanica/i', $arResult['~NAME']) ? 'BOTANICA' : (preg_match('/jetinno/i', $arResult['~NAME']) ? 'Jetinno' : '');
$arResult['BT_OG'] = ['image' => $arResult['BT_PHOTOS'][0]['og'] ?? '', 'price' => $arResult['BT']['bulk'][0]['p'] ?? ($arResult['BT']['p'] ?? 0)];
$cp = $this->getComponent();
if ($cp) {
    $cp->arResultCacheKeys = array_merge($cp->arResultCacheKeys, ['BT_OG']);
}
