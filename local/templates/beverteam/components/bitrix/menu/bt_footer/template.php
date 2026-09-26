<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
?><ul><?php foreach ($arResult as $item): ?><li><a href="<?= $item['LINK'] ?>"><?= $item['TEXT'] ?></a></li><?php endforeach ?></ul>
