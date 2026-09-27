<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Статья журнала по макету journal-post.html; title/description — из SEO-шаблонов элемента

?>
<div class="wrap journalp">
  <?php bt_crumbs() ?>
  <?php $id = $APPLICATION->IncludeComponent('bitrix:news.detail', 'bt', [
      'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID'],
      'ELEMENT_ID' => $arResult['VARIABLES']['ELEMENT_ID'] ?? '', 'ELEMENT_CODE' => $arResult['VARIABLES']['ELEMENT_CODE'] ?? '',
      'FIELD_CODE' => ['PREVIEW_PICTURE', 'DETAIL_PICTURE'], 'PROPERTY_CODE' => ['KIND', 'RUBRIC', 'READ_TIME'],
      'CHECK_DATES' => 'Y', 'IBLOCK_URL' => $arResult['FOLDER'], 'DETAIL_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['detail'],
      'CACHE_TYPE' => $arParams['CACHE_TYPE'], 'CACHE_TIME' => $arParams['CACHE_TIME'], 'CACHE_GROUPS' => 'N',
      'SET_TITLE' => 'Y', 'SET_BROWSER_TITLE' => 'Y', 'BROWSER_TITLE' => '-', 'SET_META_KEYWORDS' => 'Y', 'META_KEYWORDS' => '-',
      'SET_META_DESCRIPTION' => 'Y', 'META_DESCRIPTION' => '-', 'SET_CANONICAL_URL' => 'Y',
      'SET_STATUS_404' => 'Y', 'SHOW_404' => 'Y', 'FILE_404' => '/404.php', 'MESSAGE_404' => '',
      'INCLUDE_IBLOCK_INTO_CHAIN' => 'N', 'ADD_SECTIONS_CHAIN' => 'Y', 'ADD_ELEMENT_CHAIN' => 'Y', 'USE_PERMISSIONS' => 'N',
      'DISPLAY_TOP_PAGER' => 'N', 'DISPLAY_BOTTOM_PAGER' => 'N', 'STRICT_SECTION_CHECK' => 'N',
  ], $component) ?>
  <?php
  $more = array_slice(array_values(array_filter(bt_posts(), fn($p) => $p['id'] !== ($arResult['VARIABLES']['ELEMENT_CODE'] ?? ''))), 0, 3);
  if ($id && $more): ?>
  <section class="sec">
    <div class="row between" style="margin-bottom:22px"><h2 class="display h2">Читайте дальше</h2><a class="link" href="<?= $arResult['FOLDER'] ?>">Весь журнал →</a></div>
    <div class="news"><?php foreach ($more as $p) echo bt_post_card($p) ?></div>
  </section>
  <?php endif ?>
</div>
