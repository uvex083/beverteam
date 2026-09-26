<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @global CMain $APPLICATION
 */
// Раздел каталога и корень /magazin/ (sections.php подключает этот же файл без раздела)
$sectionCode = $arResult['VARIABLES']['SECTION_CODE'] ?? '';
$section = $rootSection = null;
if ($sectionCode !== '') {
    $section = CIBlockSection::GetList([], ['IBLOCK_ID' => $arParams['IBLOCK_ID'], '=CODE' => $sectionCode, 'ACTIVE' => 'Y'], false,
        ['ID', 'NAME', 'CODE', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DESCRIPTION', 'IBLOCK_SECTION_ID'])->GetNext();
    if (!$section) {
        \Bitrix\Iblock\Component\Tools::process404('', true, true, true);
        return;
    }
    $rootSection = $section['DEPTH_LEVEL'] > 1
        ? CIBlockSection::GetList([], ['IBLOCK_ID' => $arParams['IBLOCK_ID'], '<=LEFT_BORDER' => $section['LEFT_MARGIN'], '>=RIGHT_BORDER' => $section['RIGHT_MARGIN'], 'DEPTH_LEVEL' => 1], false, ['ID', 'CODE'])->Fetch()
        : $section;
}

// сортировка из выпадающего списка тулбара
$priceSort = 'CATALOG_PRICE_' . (int)CCatalogGroup::GetBaseGroup()['ID'];
$sorts = [
    'pop' => ['По популярности', 'SORT', 'ASC'],
    'cheap' => ['Сначала дешевле', $priceSort, 'ASC'],
    'exp' => ['Сначала дороже', $priceSort, 'DESC'],
    'az' => ['По названию А–Я', 'NAME', 'ASC'],
    'za' => ['По названию Я–А', 'NAME', 'DESC'],
];
$sortKey = isset($sorts[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'pop';
$sectionUrl = $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['section'];
$cache = ['CACHE_TYPE' => $arParams['CACHE_TYPE'], 'CACHE_TIME' => $arParams['CACHE_TIME'], 'CACHE_GROUPS' => 'N'];
?>
<div class="wrap cat-page">
  <?php $APPLICATION->ShowViewContent('bt_crumbs') ?>
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowViewContent('bt_h1') ?></h1></div>

  <?php $APPLICATION->IncludeComponent('bitrix:catalog.section.list', 'bt_root', [
      'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID'], 'TOP_DEPTH' => 1,
      'ADD_SECTIONS_CHAIN' => 'N', 'SECTION_URL' => $sectionUrl, 'BT_CUR_ROOT' => $rootSection['ID'] ?? 0,
  ] + $cache, $component, ['HIDE_ICONS' => 'Y']) ?>

  <?php if ($rootSection) {
      $APPLICATION->IncludeComponent('bitrix:catalog.section.list', 'bt_sub', [
          'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID'], 'SECTION_ID' => $rootSection['ID'], 'TOP_DEPTH' => 1,
          'COUNT_ELEMENTS' => 'Y', 'COUNT_ELEMENTS_FILTER' => 'CNT_ACTIVE', 'ADD_SECTIONS_CHAIN' => 'N', 'SECTION_URL' => $sectionUrl,
          'BT_CUR_SECTION' => $section['ID'],
      ] + $cache, $component, ['HIDE_ICONS' => 'Y']);
  } ?>

  <div class="cat">
    <?php $APPLICATION->IncludeComponent('bitrix:catalog.smart.filter', 'bt', [
        'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID'], 'SECTION_ID' => $section['ID'] ?? 0,
        'FILTER_NAME' => $arParams['FILTER_NAME'], 'PRICE_CODE' => $arParams['PRICE_CODE'], 'SAVE_IN_SESSION' => 'N',
        'DISPLAY_ELEMENT_COUNT' => 'Y', 'SEF_MODE' => 'N', 'INSTANT_RELOAD' => 'N', 'XML_EXPORT' => 'N',
        'HIDE_NOT_AVAILABLE' => 'N', 'CONVERT_CURRENCY' => 'N',
    ] + $cache, $component, ['HIDE_ICONS' => 'Y']) ?>

    <div>
      <div class="toolbar">
        <button class="btn btn--ghost btn--sm mob-f" type="button" aria-haspopup="dialog">Фильтр</button>
        <span class="cnt"><?php $APPLICATION->ShowViewContent('bt_cat_count') ?></span>
        <select aria-label="Сортировка" onchange="var u=new window.URL(location.href);u.searchParams.set('sort',this.value);location.href=u">
          <?php foreach ($sorts as $k => [$name]): ?><option value="<?= $k ?>"<?= $k === $sortKey ? ' selected' : '' ?>><?= $name ?></option><?php endforeach ?>
        </select>
      </div>
      <?php $APPLICATION->ShowViewContent('bt_cat_chips') ?>
      <?php $APPLICATION->IncludeComponent('bitrix:catalog.section', 'bt', [
          'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID'], 'SECTION_ID' => $section['ID'] ?? 0,
          'SHOW_ALL_WO_SECTION' => $section ? 'N' : 'Y', 'INCLUDE_SUBSECTIONS' => 'Y', 'FILTER_NAME' => $arParams['FILTER_NAME'],
          'ELEMENT_SORT_FIELD' => $sorts[$sortKey][1], 'ELEMENT_SORT_ORDER' => $sorts[$sortKey][2], 'ELEMENT_SORT_FIELD2' => 'ID', 'ELEMENT_SORT_ORDER2' => 'ASC',
          'PAGE_ELEMENT_COUNT' => $arParams['PAGE_ELEMENT_COUNT'], 'PRICE_CODE' => $arParams['PRICE_CODE'], 'PROPERTY_CODE' => [], 'OFFERS_LIMIT' => 0,
          'SET_TITLE' => $section ? 'Y' : 'N', 'SET_BROWSER_TITLE' => 'Y', 'SET_META_DESCRIPTION' => 'Y', 'ADD_SECTIONS_CHAIN' => $section ? 'Y' : 'N',
          'SET_STATUS_404' => 'Y', 'SHOW_404' => 'Y', 'SET_LAST_MODIFIED' => 'Y', 'CACHE_FILTER' => 'Y',
          'DISPLAY_TOP_PAGER' => 'N', 'DISPLAY_BOTTOM_PAGER' => 'N', 'HIDE_NOT_AVAILABLE' => 'N', 'COMPATIBLE_MODE' => 'N',
          'SECTION_URL' => $sectionUrl, 'DETAIL_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['element'],
      ] + $cache, $component, ['HIDE_ICONS' => 'Y']) ?>
    </div>
  </div>
</div>
<?php
// крошки и H1 выводим после компонентов: только тогда в цепочке и заголовке уже есть раздел
$APPLICATION->AddViewContent('bt_crumbs', $APPLICATION->GetNavChain(false, 0, SITE_TEMPLATE_PATH . '/components/bitrix/breadcrumb/bt/template.php', true, false));
$APPLICATION->AddViewContent('bt_h1', htmlspecialcharsbx($APPLICATION->GetTitle(false)));
