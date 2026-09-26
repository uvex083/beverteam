<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
// Корневые разделы плиткой с картинкой; аренда — отдельная страница, но в ряду разделов как в макете
$cur = (int)($arParams['BT_CUR_ROOT'] ?? 0);
$img = fn($pic) => $pic ? (CFile::ResizeImageGet($pic['ID'] ?? $pic, ['width' => 80, 'height' => 80], BX_RESIZE_IMAGE_EXACT)['src'] ?? '') : '';
$rent = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => bt_iblock('rent'), 'ACTIVE' => 'Y'], false, ['nTopCount' => 1], ['PREVIEW_PICTURE'])->Fetch();
// картинки разделов со старого сайта — баннеры с надписями, в плитке 40px нечитаемы; берём фото первого товара раздела
$photo = function (int $sectionId) use ($arParams) {
    $el = CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'SECTION_ID' => $sectionId, 'INCLUDE_SUBSECTIONS' => 'Y', 'ACTIVE' => 'Y', '!PREVIEW_PICTURE' => false], false, ['nTopCount' => 1], ['PREVIEW_PICTURE'])->Fetch();
    return $el['PREVIEW_PICTURE'] ?? 0;
};
?>
<div class="rootcats" id="rootcats">
<?php foreach ($arResult['SECTIONS'] as $s): $pic = $photo((int)$s['ID']); ?>
  <a<?= (int)$s['ID'] === $cur ? ' class="cur"' : '' ?> href="<?= $s['SECTION_PAGE_URL'] ?>"><?php if ($pic): ?><img src="<?= $img($pic) ?>" alt=""><?php endif ?><?= $s['NAME'] ?></a>
<?php endforeach ?>
  <a href="/arenda-kofemashin/"><?php if (!empty($rent['PREVIEW_PICTURE'])): ?><img src="<?= $img($rent['PREVIEW_PICTURE']) ?>" alt=""><?php endif ?>Аренда кофемашин</a>
</div>
