<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Лента журнала: первая страница — крупная карточка и «Популярное» сбоку (по просмотрам), дальше сетка; «Показать ещё» — следующая страница

$posts = [];
foreach ($arResult['ITEMS'] as $it) {
    $kind = $it['PROPERTIES']['KIND']['VALUE_XML_ID'] ?? 'article';
    $date = $it['ACTIVE_FROM'] ?: $it['DATE_CREATE'];
    $posts[] = [
        'kind' => $kind ?: 'article', 'cat' => bt_blog_rubrics()[(int)$it['IBLOCK_SECTION_ID']]['name'] ?? (($it['PROPERTIES']['RUBRIC']['~VALUE'] ?? '') ?: ($kind === 'news' ? 'Новости' : 'Статьи')),
        'd' => $date ? date('Y-m-d', MakeTimeStamp($date)) : '', 't' => $it['~NAME'], 'lead' => trim(strip_tags((string)$it['~PREVIEW_TEXT'])),
        'url' => $it['~DETAIL_PAGE_URL'], 'img' => $it['PREVIEW_PICTURE']['SRC'] ?? '', 'id' => (int)$it['ID'],
    ];
}
$nav = $arResult['NAV_RESULT'] ?? null;
$page = $nav ? (int)$nav->NavPageNomer : 1;
$e = fn($s) => htmlspecialcharsbx((string)$s);

if (!$posts): ?>
  <p class="muted">Материалов пока нет.</p>
<?php return; endif;

if ($page === 1):
    $lead = array_shift($posts);
    // «Популярное»: самые просматриваемые материалы (в рубрике — из неё), кроме крупной карточки
    $side = [];
    $r = CIBlockElement::GetList(['SHOW_COUNTER' => 'DESC', 'ACTIVE_FROM' => 'DESC'], array_filter(['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y', '!ID' => $lead['id'], 'SECTION_ID' => (int)(($arParams['PARENT_SECTION'] ?? 0) ?: (array_column($arResult['SECTION']['PATH'] ?? [], 'ID') ?: [0])[count($arResult['SECTION']['PATH'] ?? []) - 1] ?? 0) ?: null, 'INCLUDE_SUBSECTIONS' => 'Y']),
        false, ['nTopCount' => 5], ['ID', 'NAME', 'DETAIL_PAGE_URL', 'ACTIVE_FROM', 'DATE_CREATE', 'IBLOCK_SECTION_ID']);
    while ($x = $r->GetNext()) {
        $date = $x['ACTIVE_FROM'] ?: $x['DATE_CREATE'];
        $side[] = ['t' => $x['~NAME'], 'url' => $x['~DETAIL_PAGE_URL'], 'd' => $date ? date('Y-m-d', MakeTimeStamp($date)) : '',
            'cat' => bt_blog_rubrics()[(int)$x['IBLOCK_SECTION_ID']]['name'] ?? 'Статьи'];
    }
?>
<div class="jlead">
  <?= bt_post_card($lead) ?>
  <?php if ($side): ?><div class="jside"><b class="jside__t">Популярное</b><?php foreach ($side as $p): ?><a href="<?= $e($p['url']) ?>"><span class="ncard__m"><span class="tag"><?= $e($p['cat']) ?></span><time datetime="<?= $e($p['d']) ?>"><?= bt_date_ru($p['d']) ?></time></span><b><?= $e($p['t']) ?></b></a><?php endforeach ?></div><?php endif ?>
</div>
<?php endif ?>
<?php if ($posts): ?><div class="news"><?php foreach ($posts as $p) echo bt_post_card($p) ?></div><?php endif ?>
<?php if ($nav && $nav->NavPageCount > 1):
    $shown = min($nav->NavRecordCount, $page * $nav->NavPageSize);
    $next = $page < $nav->NavPageCount ? $APPLICATION->GetCurPageParam('PAGEN_' . $nav->NavNum . '=' . ($page + 1), ['PAGEN_' . $nav->NavNum]) : '';
?>
<div class="jmore">
  <span class="cnt">Показано <?= $shown ?> из <?= (int)$nav->NavRecordCount ?></span>
  <span class="bar"><i style="width:<?= round($shown / max(1, $nav->NavRecordCount) * 100) ?>%"></i></span>
  <?php if ($next): ?><a class="btn btn--line" href="<?= $e($next) ?>">Показать ещё</a><?php endif ?>
</div>
<?php endif;
