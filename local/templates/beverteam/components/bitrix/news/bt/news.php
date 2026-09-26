<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Журнал по макету journal.html: фильтр «статьи / новости» — параметром ?kind, список — news.list bt

$kinds = ['article' => 'Статьи', 'news' => 'Новости'];
$kind = (string)($_GET['kind'] ?? '');
$kind = isset($kinds[$kind]) ? $kind : '';
global $btJournalFilter;
$btJournalFilter = [];
if ($kind) {
    $enum = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'CODE' => 'KIND', 'XML_ID' => $kind])->Fetch();
    $btJournalFilter['PROPERTY_KIND'] = $enum['ID'] ?? -1;
}
$posts = bt_posts();
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'ItemList',
    'itemListElement' => array_map(fn($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => 'https://' . SITE_SERVER_NAME . $p['url'], 'name' => $p['t']], $posts, array_keys($posts))],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap journalp">
  <?php bt_crumbs() ?>
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1><p class="sub">Как выбирать зерно, настраивать кофемашину и ухаживать за оборудованием. Пишем сами, на своём опыте обжарки и сервиса.</p></div>

  <nav class="jfilter" aria-label="Тип материалов">
    <a href="<?= $arResult['FOLDER'] ?>" aria-pressed="<?= $kind === '' ? 'true' : 'false' ?>">Всё</a>
    <?php foreach ($kinds as $k => $name): ?><a href="<?= $arResult['FOLDER'] ?>?kind=<?= $k ?>" aria-pressed="<?= $kind === $k ? 'true' : 'false' ?>"><?= $name ?></a><?php endforeach ?>
  </nav>

  <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'bt', [
      'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID'],
      'NEWS_COUNT' => $arParams['NEWS_COUNT'], 'SORT_BY1' => $arParams['SORT_BY1'], 'SORT_ORDER1' => $arParams['SORT_ORDER1'],
      'SORT_BY2' => $arParams['SORT_BY2'], 'SORT_ORDER2' => $arParams['SORT_ORDER2'],
      'FIELD_CODE' => ['PREVIEW_PICTURE'], 'PROPERTY_CODE' => ['KIND', 'RUBRIC', 'READ_TIME'],
      'DETAIL_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['detail'],
      'FILTER_NAME' => 'btJournalFilter', 'CHECK_DATES' => 'Y',
      'CACHE_TYPE' => $arParams['CACHE_TYPE'], 'CACHE_TIME' => $arParams['CACHE_TIME'], 'CACHE_FILTER' => 'Y', 'CACHE_GROUPS' => 'N',
      'SET_TITLE' => 'N', 'SET_BROWSER_TITLE' => 'N', 'SET_META_KEYWORDS' => 'N', 'SET_META_DESCRIPTION' => 'N', 'SET_STATUS_404' => 'N',
      'INCLUDE_IBLOCK_INTO_CHAIN' => 'N', 'ADD_SECTIONS_CHAIN' => 'N', 'DISPLAY_TOP_PAGER' => 'N', 'DISPLAY_BOTTOM_PAGER' => 'Y',
      'PAGER_SHOW_ALWAYS' => 'N', 'PAGER_TEMPLATE' => '.default',
  ], $component) ?>

  <section class="sec sec--t0" style="padding-top:var(--pad)">
    <div class="wr">
      <div>
        <h2 class="display h2">Не пропускайте новые материалы</h2>
        <p style="margin:16px 0 0;max-width:46ch;color:#3A3A34">Оставьте e-mail — напишем, когда выйдут новые статьи и новости. Отписаться можно в любой момент.</p>
      </div>
      <form class="wr__f" data-form="subscribe" novalidate>
        <input type="hidden" name="topic" value="Подписка на новые материалы журнала">
        <div class="field"><label>E-mail *</label><input name="email" type="email" placeholder="mail@company.ru" maxlength="100"></div>
        <?= bt_form_tail('Подписаться') ?>
      </form>
    </div>
  </section>
</div>
