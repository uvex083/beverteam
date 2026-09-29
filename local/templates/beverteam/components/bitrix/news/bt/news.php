<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Журнал по макету journal.html: рубрики — разделы инфоблока (/blog/<рубрика>/, section.php подключает этот же файл), список — news.list bt

$rubric = (string)($btRubric ?? '');
$rubrics = bt_blog_rubrics();
$tags = bt_blog_tags();
$tag = (string)($_GET['tag'] ?? '');
$tag = isset($tags[$tag]) ? $tag : '';
$posts = $rubric !== '' ? array_values(array_filter(bt_posts(), fn($p) => $p['rub'] === $arResult['FOLDER'] . $rubric . '/')) : bt_posts();
global $btJournalFilter;
$btJournalFilter = [];
if ($tag !== '') {
    // подборка по тегу — служебная страница: не индексируем (canonical не ставим — с noindex он лишний)
    $posts = array_values(array_filter($posts, fn($p) => in_array($tag, $p['tags'] ?? [], true)));
    $btJournalFilter['=CODE'] = array_column($posts, 'id') ?: ['-'];
    $APPLICATION->SetTitle('#' . $tag);
    $APPLICATION->SetPageProperty('title', 'Материалы по теме «' . $tag . '» — журнал BEVERTEAM');
    $APPLICATION->SetPageProperty('robots', 'noindex, follow');
    $APPLICATION->AddChainItem('#' . $tag);
    $n = count($posts);
    $sub = 'Статьи журнала по теме «' . $tag . '» — ' . $n . ' ' . ($n % 10 === 1 && $n % 100 !== 11 ? 'материал' : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 'материала' : 'материалов'));
    $APPLICATION->SetPageProperty('description', $sub . '. Журнал BEVERTEAM о кофе, чае и кофемашинах.');
} elseif ($rubric !== '') {
    // вводный текст рубрики — описание раздела в админке
    $sub = trim(strip_tags((string)(CIBlockSection::GetList([], ['IBLOCK_ID' => $arParams['IBLOCK_ID'], '=CODE' => $rubric], false, ['DESCRIPTION'])->Fetch()['DESCRIPTION'] ?? '')));
}
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'ItemList',
    'itemListElement' => array_map(fn($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => 'https://' . SITE_SERVER_NAME . $p['url'], 'name' => $p['t']], $posts, array_keys($posts))],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap journalp">
  <?php bt_crumbs() ?>
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1><p class="sub"><?= htmlspecialcharsbx(($sub ?? '') ?: 'Как выбирать зерно, настраивать кофемашину и ухаживать за оборудованием. Пишем сами, на своём опыте обжарки и сервиса.') ?></p></div>

  <?php if (count($rubrics) > 1 || $rubric !== '' || $tag !== ''): ?>
  <nav class="jfilter" aria-label="Рубрики">
    <a href="<?= $arResult['FOLDER'] ?>" aria-pressed="<?= $rubric === '' && $tag === '' ? 'true' : 'false' ?>">Всё</a>
    <?php foreach ($rubrics as $r): ?><a href="<?= htmlspecialcharsbx($r['url']) ?>" aria-pressed="<?= $r['code'] === $rubric ? 'true' : 'false' ?>"><?= htmlspecialcharsbx($r['name']) ?></a><?php endforeach ?>
  </nav>
  <?php endif ?>
  <?php if ($tags): ?>
  <?php $top = 10; $open = $tag !== '' && array_search($tag, array_keys($tags), true) >= $top ?>
  <nav class="jtags<?= $open ? ' is-open' : '' ?>" aria-label="Темы"><span>Темы:</span><?php $i = 0; foreach ($tags as $t => $n): ?><a href="<?= htmlspecialcharsbx(bt_tag_url($t)) ?>"<?= $i++ >= $top ? ' class="jtags__more"' : '' ?><?= $t === $tag ? ' aria-current="page"' : '' ?>>#<?= htmlspecialcharsbx($t) ?><sup><?= $n ?></sup></a><?php endforeach ?>
    <?php if (count($tags) > $top): ?><button type="button" class="jtags__btn" data-tags-more>Ещё <?= count($tags) - $top ?></button><?php endif ?></nav>
  <?php endif ?>

  <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'bt', [
      'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID'],
      'NEWS_COUNT' => $arParams['NEWS_COUNT'], 'SORT_BY1' => $arParams['SORT_BY1'], 'SORT_ORDER1' => $arParams['SORT_ORDER1'],
      'SORT_BY2' => $arParams['SORT_BY2'], 'SORT_ORDER2' => $arParams['SORT_ORDER2'],
      'FIELD_CODE' => ['PREVIEW_PICTURE'], 'PROPERTY_CODE' => ['KIND', 'RUBRIC', 'READ_TIME', 'REVIEW'],
      'DETAIL_URL' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['detail'],
      'PARENT_SECTION_CODE' => $rubric, 'INCLUDE_SUBSECTIONS' => 'Y', 'CHECK_DATES' => 'Y', 'FILTER_NAME' => 'btJournalFilter',
      'CACHE_TYPE' => $arParams['CACHE_TYPE'], 'CACHE_TIME' => $arParams['CACHE_TIME'], 'CACHE_FILTER' => 'Y', 'CACHE_GROUPS' => 'N',
      'SET_TITLE' => $rubric !== '' ? 'Y' : 'N', 'SET_BROWSER_TITLE' => $rubric !== '' ? 'Y' : 'N', 'SET_META_KEYWORDS' => 'N',
      'SET_META_DESCRIPTION' => $rubric !== '' ? 'Y' : 'N', 'SET_LAST_MODIFIED' => 'N',
      'SET_STATUS_404' => $rubric !== '' ? 'Y' : 'N', 'SHOW_404' => $rubric !== '' ? 'Y' : 'N', 'FILE_404' => '/404.php',
      'INCLUDE_IBLOCK_INTO_CHAIN' => 'N', 'ADD_SECTIONS_CHAIN' => $rubric !== '' ? 'Y' : 'N', 'DISPLAY_TOP_PAGER' => 'N', 'DISPLAY_BOTTOM_PAGER' => 'Y',
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
