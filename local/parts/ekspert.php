<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
/** @var array $expert */
// Страница специалиста: данные — ИБ «О компании: команда», публикации — статьи журнала, где он указан автором

use Bitrix\Main\Loader;

$e = fn($s) => htmlspecialcharsbx((string)$s);
$host = 'https://beverteam.ru';
$codes = [];
if (Loader::includeModule('iblock')) {
    $r = CIBlockElement::GetList(['ACTIVE_FROM' => 'DESC'], ['IBLOCK_ID' => bt_iblock('journal'), 'ACTIVE' => 'Y', '=PROPERTY_AUTHOR' => $expert['id']], false, false, ['ID', 'CODE']);
    while ($f = $r->Fetch()) {
        $codes[] = $f['CODE'];
    }
}
$posts = array_values(array_filter(bt_posts(), fn($p) => in_array($p['id'], $codes, true)));
$edu = $expert['edu'] !== '' && !str_starts_with(ltrim($expert['edu']), '<') ? '<p>' . nl2br($e($expert['edu'])) . '</p>' : $expert['edu'];
$person = array_filter(['@type' => 'Person', '@id' => $host . $expert['url'] . '#person', 'name' => $expert['name'], 'jobTitle' => $expert['job'],
    'description' => trim(preg_replace('/\s+/u', ' ', strip_tags($expert['bio']))), 'image' => $expert['pic'] ? $host . $expert['pic'] : '',
    'worksFor' => ['@type' => 'Organization', '@id' => $host . '/#org', 'name' => 'BEVERTEAM'], 'knowsAbout' => $expert['spec'],
    'url' => $host . $expert['url'], 'sameAs' => $expert['profiles']]);
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'ProfilePage',
    'url' => $host . $expert['url'], 'mainEntity' => $person], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap expp">
  <?php bt_crumbs() ?>
  <section class="expp__hd">
    <div class="expp__ph"><?php if ($expert['pic']): ?><img src="<?= $e($expert['pic']) ?>" alt="<?= $e($expert['name']) ?>" width="480" height="480"><?php endif ?></div>
    <div class="expp__in">
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <?php if ($expert['job'] !== ''): ?><p class="expp__job"><?= $e($expert['job']) ?></p><?php endif ?>
      <?php if ($expert['spec']): ?><ul class="expp__spec"><?php foreach ($expert['spec'] as $s): ?><li><?= $e($s) ?></li><?php endforeach ?></ul><?php endif ?>
      <?php if ($expert['profiles']): ?><p class="expp__links"><?php foreach ($expert['profiles'] as $u): ?><a class="link" href="<?= $e($u) ?>" target="_blank" rel="noopener me"><?= $e(preg_replace('~^https?://(www\.)?~', '', rtrim($u, '/'))) ?></a><?php endforeach ?></p><?php endif ?>
    </div>
  </section>
  <section class="sec sec--t0 expp__body">
    <div class="th th2">О специалисте</div>
    <div class="post__body"><?= $expert['bio'] ?></div>
    <?php if ($edu !== ''): ?>
    <div class="th th2">Образование и достижения</div>
    <div class="post__body"><?= $edu ?></div>
    <?php endif ?>
  </section>
  <?php if ($posts): ?>
  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">Публикации специалиста</h2>
    <div class="news"><?php foreach ($posts as $p) echo bt_post_card($p) ?></div>
  </section>
  <?php endif ?>
</div>
