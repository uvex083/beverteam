<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
// Мобильное меню: первый экран — крупные разделы, вход в кабинет и контакты; подпункты открываются слайдом справа (до третьего уровня)
$items = array_values($arResult);
$i = 0;
$build = function (int $lvl) use (&$build, &$i, $items): array {
    $out = [];
    while ($i < count($items)) {
        $d = max(1, (int)($items[$i]['PARAMS']['DEPTH_LEVEL'] ?? 1));
        if ($d < $lvl) {
            break;
        }
        if ($d > $lvl) {
            if ($out) {
                $out[array_key_last($out)]['SUB'] = $build($lvl + 1);
            } else {
                $i++;
            }
            continue;
        }
        $out[] = ['T' => $items[$i]['TEXT'], 'L' => $items[$i]['LINK'], 'P' => $items[$i]['PARAMS'], 'SUB' => []];
        $i++;
    }
    return $out;
};
$tree = $build(1);

$e = fn($s) => htmlspecialcharsbx((string)$s);
$panels = [];
$n = 0;
// Пункт с подпунктами — кнопка слайда, его панель копится в $panels
$row = function (array $it, string $cls) use (&$row, &$panels, &$n, $e): string {
    $img = !empty($it['P']['img']) ? '<img src="' . $e($it['P']['img']) . '" alt="" loading="lazy">' : '';
    $badge = !empty($it['P']['badge']) ? '<em>' . $e($it['P']['badge']) . '</em>' : '';
    if (!$it['SUB']) {
        return '<a class="' . $cls . '" href="' . $e($it['L']) . '">' . $img . '<span>' . $it['T'] . '</span>' . $badge . '</a>';
    }
    $id = 'dp' . (++$n);
    $list = '<a class="dn__all" href="' . $e($it['L']) . '">Смотреть всё' . bt_icon('arrR') . '</a>';
    foreach ($it['SUB'] as $s) {
        $list .= $row($s, 'dn__i');
    }
    $panels[] = '<section class="dn__p" id="' . $id . '"><button class="dn__back" type="button" data-dback>' . bt_icon('arrL') . 'Назад</button>'
        . '<h3 class="dn__h">' . $it['T'] . '</h3><nav class="dn__list">' . $list . '</nav></section>';
    return '<button class="' . $cls . '" type="button" data-dgo="' . $id . '">' . $img . '<span>' . $it['T'] . '</span>' . $badge . bt_icon('arrR') . '</button>';
};
$accent = $plain = '';
foreach ($tree as $it) {
    if (($it['P']['accent'] ?? '') === 'Y') {
        $accent .= $row($it, 'dn__a');
    } else {
        $plain .= $row($it, 'dn__s');
    }
}
$co = bt_contacts();
$msgr = '';
foreach (bt_messengers() as [, $name, $href, $svg]) {
    $msgr .= '<a href="' . $e($href) . '" title="' . $e($name) . '" aria-label="' . $e($name) . '" rel="nofollow noopener" target="_blank">' . $svg . '</a>';
}
?>
<div class="dn" id="dn">
  <section class="dn__p dn__root is-on" id="dp0">
    <nav class="dn__accent" aria-label="Разделы сайта"><?= $accent ?></nav>
    <nav class="dn__plain" aria-label="Покупателям"><?= $plain ?></nav>
    <div class="dn__acc">
      <a class="dn__me" href="/personal/">
        <i data-dme-i><?= bt_icon('user') ?></i>
        <span><b data-dme-name>Войти или зарегистрироваться</b><small data-dme-sub>Заказы, адреса и документы в одном месте</small></span>
        <?= bt_icon('arrR') ?>
      </a>
      <div class="dn__tiles">
        <a href="/personal/favorites/"><?= bt_icon('heart') ?><span>Избранное</span><b data-dcnt="fav"></b></a>
        <a href="/catalog/compare/"><?= bt_icon('compare') ?><span>Сравнение</span><b data-dcnt="cmp"></b></a>
      </div>
    </div>
    <div class="dn__ct">
      <span class="dn__k">Связаться с нами</span>
      <div class="msgr msgr--lg"><?= $msgr ?></div>
      <a class="dn__tel" href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a>
      <span class="dn__addr"><?= $e($co['city'] ?? '') ?>, <?= $e($co['street'] ?? '') ?><br><?= $e($co['hours'] ?? '') ?></span>
      <a class="btn btn--block" href="/kontakty/#form" data-lead="Оставить заявку">Оставить заявку</a>
    </div>
  </section>
  <?= implode('', $panels) ?>
</div>
