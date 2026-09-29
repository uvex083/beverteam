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
    $toc[] = [$id, trim(strip_tags($m[3])), strtolower($m[1]) === 'h2' ? 2 : 3];
    return '<' . $m[1] . ' id="' . $id . '"' . $m[2] . '>' . $m[3] . '</' . $m[1] . '>';
}, (string)$arResult['~DETAIL_TEXT']);
// фото в тексте — уменьшенная копия не шире 1200 px в webp, грузится при прокрутке
$body = preg_replace_callback('~<img\b([^>]*?)\ssrc="(/upload/[^"]+\.(?:jpe?g|png))"([^>]*)>~i', function ($m) {
    $src = bt_img(bt_upload_file_id($m[2]), 1200, 2400) ?: $m[2];
    $attrs = $m[1] . $m[3];
    return '<img' . $m[1] . ' src="' . $src . '"' . $m[3] . (str_contains($attrs, 'loading=') ? '' : ' loading="lazy"') . (str_contains($attrs, 'decoding=') ? '' : ' decoding="async"') . '>';
}, $body);
// таблица — в прокручиваемой обёртке: на телефоне листается сама, на широком экране тянется во всю колонку
$body = preg_replace(['~<table\b~i', '~</table>~i'], ['<div class="tbl"><table', '</table></div>'], $body);
// товары из статьи — карточки каталога (выключенные и удалённые товары пропускаются)
$prods = array_values(array_filter(array_map(fn($id) => bt_product((string)$id), (array)($arResult['PROPERTIES']['PRODUCTS']['VALUE'] ?? []))));
if ($prods) {
    $toc[] = ['prods', 'Товары из статьи', 2];
}
// оглавление: разделы H2 с номерами, подзаголовки H3–H4 — внутри своего раздела
$tree = [];
foreach ($toc as [$id, $t, $lv]) {
    if ($lv === 3 && $tree) {
        $tree[count($tree) - 1]['sub'][] = [$id, $t];
    } else {
        $tree[] = ['id' => $id, 't' => $t, 'sub' => []];
    }
}
$promo = bt_journal_promo((int)$arResult['IBLOCK_SECTION_ID']);
$pic = bt_img($arResult['DETAIL_PICTURE']['ID'] ?? ($arResult['PREVIEW_PICTURE']['ID'] ?? 0), 1600, 1600);
?>
<article class="post" style="margin-top:22px" itemscope itemtype="https://schema.org/<?= $kind === 'news' ? 'NewsArticle' : 'Article' ?>">
  <div>
    <meta itemprop="datePublished" content="<?= $e($iso) ?>"><meta itemprop="articleSection" content="<?= $e($cat) ?>">
    <?php $host = (\CMain::IsHTTPS() ? 'https://' : 'http://') . SITE_SERVER_NAME; $co = bt_contacts(); $mod = $arResult['TIMESTAMP_X'] ?? ''; ?>
    <?php if ($mod): ?><meta itemprop="dateModified" content="<?= $e(date('Y-m-d', MakeTimeStamp($mod))) ?>"><?php endif ?>
    <link itemprop="mainEntityOfPage" href="<?= $e($host . $arResult['~DETAIL_PAGE_URL']) ?>">
    <?php if ($pic): ?><link itemprop="image" href="<?= $e($host . $pic) ?>"><?php endif ?>
    <?php /* автор и издатель — компания: Яндекс требует у организации адрес и телефон */ ?>
    <div hidden itemprop="author publisher" itemscope itemtype="https://schema.org/Organization" itemid="https://beverteam.ru/#org">
      <meta itemprop="name" content="BEVERTEAM"><link itemprop="url" href="<?= $e($host) ?>/">
      <div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject"><link itemprop="url" href="<?= $e($host) ?>/local/templates/beverteam/images/og-logo.png"></div>
      <meta itemprop="telephone" content="<?= $e($co['phone1'] ?? '') ?>">
      <div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress"><meta itemprop="postalCode" content="<?= $e($co['zip'] ?? '') ?>"><meta itemprop="addressLocality" content="<?= $e($co['city'] ?? '') ?>"><meta itemprop="streetAddress" content="<?= $e($co['street'] ?? '') ?>"></div>
    </div>
    <?php if (($arResult['PROPERTIES']['REVIEW']['VALUE_XML_ID'] ?? '') === 'draft'): ?><div class="post__draft">Черновик — статья на вычитке, факты и фото ещё уточняются</div><?php endif ?>
    <h1 class="display h1" itemprop="headline"><?= $e($arResult['~NAME']) ?></h1>
    <div class="post__meta">
      <span class="tag"><?= $e($cat) ?></span>
      <?php if ($iso): ?><time datetime="<?= $e($iso) ?>"><?= bt_date_ru($iso) ?></time><?php endif ?>
      <?php if ($min): ?><span>·</span><span><?= $min ?> мин чтения</span><?php endif ?>
      <span>·</span><span>BEVERTEAM</span>
    </div>
    <?php if ($pic): ?><figure class="post__ph"><img src="<?= $e($pic) ?>" alt="<?= $e($arResult['~NAME']) ?>"></figure><?php endif ?>
    <div class="post__body" itemprop="articleBody"><?= $body ?></div>
    <?php if ($prods): ?>
    <section class="post__prods" id="prods" aria-labelledby="prodsT">
      <h2 class="display h3" id="prodsT">Товары из статьи</h2>
      <div class="grid g3"><?php foreach ($prods as $m) echo bt_card($m) ?></div>
    </section>
    <?php endif ?>
    <?php if ($tags): ?><div class="jtags jtags--post"><?php foreach ($tags as $t): ?><a href="<?= $e(bt_tag_url($t)) ?>">#<?= $e($t) ?></a><?php endforeach ?><meta itemprop="keywords" content="<?= $e(implode(', ', $tags)) ?>"></div><?php endif ?>
    <div class="row" style="margin-top:32px;gap:12px">
      <a class="btn" href="/catalog/kofe/">Выбрать зерно</a>
      <a class="btn btn--line" href="/podbor-kofe/">Подобрать кофе за минуту</a>
    </div>
  </div>
  <?php if (count($tree) >= 2 || $promo): ?>
  <aside>
    <div class="post__side">
      <?php if (count($tree) >= 2): ?>
      <nav class="post__toc" aria-label="Содержание" data-min="<?= $min ?>">
        <div class="post__toc-hd"><b>В статье</b><span class="post__toc-pct"></span></div>
        <span class="post__toc-bar"><i></i></span>
        <ol>
          <?php foreach ($tree as $n => $h): ?>
          <li<?= $h['sub'] ? ' class="has-sub"' : '' ?>><a href="#<?= $h['id'] ?>"><span class="post__toc-n"><?= sprintf('%02d', $n + 1) ?></span><span><?= $e($h['t']) ?></span></a>
            <?php if ($h['sub']): ?><div class="post__toc-sub"><?php foreach ($h['sub'] as [$id, $t]): ?><a href="#<?= $id ?>"><?= $e($t) ?></a><?php endforeach ?></div><?php endif ?></li>
          <?php endforeach ?>
        </ol>
      </nav>
      <?php endif ?>
      <?php if ($promo): ?>
      <a class="post__promo" href="<?= $e($promo['link']) ?>">
        <?php if ($promo['img']): ?><img src="<?= $e($promo['img']) ?>" alt="" width="52" height="52" loading="lazy"><?php endif ?>
        <span><b><?= $e($promo['title']) ?></b><?php if ($promo['text'] !== ''): ?><span><?= $e($promo['text']) ?></span><?php endif ?><em><?= $e($promo['btn']) ?> →</em></span>
      </a>
      <?php endif ?>
    </div>
  </aside>
  <?php endif ?>
</article>
