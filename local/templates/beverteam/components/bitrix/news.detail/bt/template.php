<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// Статья: заголовкам текста проставляются якоря, из них — оглавление сбоку (если заголовков два и больше)

$e = fn($s) => htmlspecialcharsbx((string)$s);
$kind = $arResult['PROPERTIES']['KIND']['VALUE_XML_ID'] ?? 'article';
$cat = bt_blog_rubrics()[(int)$arResult['IBLOCK_SECTION_ID']]['name'] ?? (($arResult['PROPERTIES']['RUBRIC']['~VALUE'] ?? '') ?: ($kind === 'news' ? 'Новости' : 'Статьи'));
$tags = bt_tags_split((string)($arResult['~TAGS'] ?? ''));
$min = (int)($arResult['PROPERTIES']['READ_TIME']['VALUE'] ?? 0);
$date = $arResult['ACTIVE_FROM'] ?: $arResult['DATE_CREATE'];
$iso = $date ? date('Y-m-d', MakeTimeStamp($date)) : '';
$toc = [];
$body = preg_replace_callback('~<(h[2-4])([^>]*)>(.*?)</\1>~si', function ($m) use (&$toc) {
    $id = 'h' . (count($toc) + 1);
    $toc[] = [$id, trim(strip_tags($m[3]))];
    return '<' . $m[1] . ' id="' . $id . '"' . $m[2] . '>' . $m[3] . '</' . $m[1] . '>';
}, (string)$arResult['~DETAIL_TEXT']);
// таблица — в прокручиваемой обёртке: на телефоне листается сама, на широком экране тянется во всю колонку
$body = preg_replace(['~<table\b~i', '~</table>~i'], ['<div class="tbl"><table', '</table></div>'], $body);
// товары из статьи — карточки каталога (выключенные и удалённые товары пропускаются)
$prods = array_values(array_filter(array_map(fn($id) => bt_product((string)$id), (array)($arResult['PROPERTIES']['PRODUCTS']['VALUE'] ?? []))));
if ($prods) {
    $toc[] = ['prods', 'Товары из статьи'];
}
$pic = bt_img($arResult['DETAIL_PICTURE']['ID'] ?? ($arResult['PREVIEW_PICTURE']['ID'] ?? 0), 1600, 1600);
?>
<article class="post" style="margin-top:22px" itemscope itemtype="https://schema.org/<?= $kind === 'news' ? 'NewsArticle' : 'Article' ?>">
  <div>
    <meta itemprop="datePublished" content="<?= $e($iso) ?>"><meta itemprop="articleSection" content="<?= $e($cat) ?>">
    <div hidden itemprop="author" itemscope itemtype="https://schema.org/Organization"><meta itemprop="name" content="BEVERTEAM"></div>
    <h1 class="display h1" itemprop="headline"><?= $e($arResult['~NAME']) ?></h1>
    <div class="post__meta">
      <span class="tag"><?= $e($cat) ?></span>
      <?php if ($iso): ?><time datetime="<?= $e($iso) ?>"><?= bt_date_ru($iso) ?></time><?php endif ?>
      <?php if ($min): ?><span>·</span><span><?= $min ?> мин чтения</span><?php endif ?>
      <span>·</span><span>BEVERTEAM</span>
    </div>
    <?php if ($pic): ?><figure class="post__ph"><img src="<?= $e($pic) ?>" alt="<?= $e($arResult['~NAME']) ?>" itemprop="image"></figure><?php endif ?>
    <div class="post__body" itemprop="articleBody"><?= $body ?></div>
    <?php if ($prods): ?>
    <section class="post__prods" id="prods" aria-labelledby="prodsT">
      <h2 class="display h3" id="prodsT">Товары из статьи</h2>
      <div class="grid g3"><?php foreach ($prods as $m) echo bt_card($m) ?></div>
    </section>
    <?php endif ?>
    <?php if ($tags): ?><div class="jtags jtags--post"><?php foreach ($tags as $t): ?><a href="<?= $e(bt_tag_url($t)) ?>">#<?= $e($t) ?></a><?php endforeach ?><meta itemprop="keywords" content="<?= $e(implode(', ', $tags)) ?>"></div><?php endif ?>
    <div class="row" style="margin-top:32px;gap:12px">
      <a class="btn" href="/magazin/kofe/">Выбрать зерно</a>
      <a class="btn btn--line" href="/podbor-kofe/">Подобрать кофе за минуту</a>
    </div>
  </div>
  <?php if (count($toc) >= 2): ?>
  <aside>
    <nav class="post__toc" aria-label="Содержание">
      <b>В статье</b>
      <?php foreach ($toc as [$id, $t]): ?><a href="#<?= $id ?>"><?= $e($t) ?></a><?php endforeach ?>
    </nav>
  </aside>
  <?php endif ?>
</article>
