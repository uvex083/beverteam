<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
// Лента журнала: сетка по 3 в ряд (12 на страницу — ровные ряды); «Показать ещё» — следующая страница

$posts = [];
foreach ($arResult['ITEMS'] as $it) {
    $kind = $it['PROPERTIES']['KIND']['VALUE_XML_ID'] ?? 'article';
    $date = $it['ACTIVE_FROM'] ?: $it['DATE_CREATE'];
    $posts[] = [
        'kind' => $kind ?: 'article', 'cat' => bt_blog_rubrics()[(int)$it['IBLOCK_SECTION_ID']]['name'] ?? (($it['PROPERTIES']['RUBRIC']['~VALUE'] ?? '') ?: ($kind === 'news' ? 'Новости' : 'Статьи')),
        'd' => $date ? date('Y-m-d', MakeTimeStamp($date)) : '', 't' => $it['~NAME'], 'lead' => trim(strip_tags((string)$it['~PREVIEW_TEXT'])),
        'url' => $it['~DETAIL_PAGE_URL'], 'img' => bt_img($it['PREVIEW_PICTURE']['ID'] ?? 0, 1040, 650, BX_RESIZE_IMAGE_EXACT), 'id' => (int)$it['ID'],
        'draft' => ($it['PROPERTIES']['REVIEW']['VALUE_XML_ID'] ?? '') === 'draft',
    ];
}
$nav = $arResult['NAV_RESULT'] ?? null;
$page = $nav ? (int)$nav->NavPageNomer : 1;
$e = fn($s) => htmlspecialcharsbx((string)$s);

if (!$posts): ?>
  <p class="muted">Материалов пока нет.</p>
<?php return; endif;

?>
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
