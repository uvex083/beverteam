<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
// Корневые разделы плиткой с картинкой; аренда — отдельная страница, но в ряду разделов как в макете
$cur = (int)($arParams['BT_CUR_ROOT'] ?? 0);
$img = fn($pic) => $pic ? (CFile::ResizeImageGet($pic['ID'] ?? $pic, ['width' => 80, 'height' => 80], BX_RESIZE_IMAGE_EXACT)['src'] ?? '') : '';
$rent = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => bt_iblock('rent'), 'ACTIVE' => 'Y'], false, ['nTopCount' => 1], ['PREVIEW_PICTURE'])->Fetch();
?>
<div class="rootcats" id="rootcats">
<?php foreach ($arResult['SECTIONS'] as $s): ?>
  <a<?= (int)$s['ID'] === $cur ? ' class="cur"' : '' ?> href="<?= $s['SECTION_PAGE_URL'] ?>"><?php if ($s['PICTURE']): ?><img src="<?= $img($s['PICTURE']) ?>" alt=""><?php endif ?><?= $s['NAME'] ?></a>
<?php endforeach ?>
  <a href="/arenda-kofemashin/"><?php if (!empty($rent['PREVIEW_PICTURE'])): ?><img src="<?= $img($rent['PREVIEW_PICTURE']) ?>" alt=""><?php endif ?>Аренда кофемашин</a>
</div>
