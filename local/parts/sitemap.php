<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */

$e = fn($s) => htmlspecialcharsbx((string)$s);
$ul = function (array $items) use (&$ul, $e): string {
    $h = '<ul>';
    foreach ($items as $it) {
        $h .= '<li><a href="' . $e($it[1]) . '">' . $e($it[0]) . '</a>' . (!empty($it[2]) ? $ul($it[2]) : '') . '</li>';
    }
    return $h . '</ul>';
};
$shop = [];
foreach (bt_mega_cats() as $c) {
    if ($c['h'] !== '/arenda-kofemashin/') {
        // товары в карту не выводим — только разделы
        $shop[] = [$c['t'], $c['h'], array_values(array_filter(array_map(fn($s) => [$s[0], $s[1]], $c['sub']), fn($s) => !str_contains($s[1], '/catalog/product/')))];
    }
}
$shop[] = ['Сравнение товаров', '/catalog/compare/'];
$rent = array_map(fn($m) => [$m['model'] . ' — ' . mb_strtolower($m['audience']), bt_rent_url($m)], bt_rent_models());
$services = [
    ['Аренда кофемашин', '/arenda-kofemashin/', $rent],
    ['Аренда на мероприятия', '/arenda-kofemashin/#event'],
    ['Кофе в офис', '/kofe-v-ofis/'],
    ['Сервисное обслуживание', '/servis/', [['Ремонт кофемашин', '/servis/remont-kofemashin/']]],
    ['Подбор кофе за минуту', '/podbor-kofe/'],
];
$account = [['Личный кабинет', '/personal/'], ['Корзина', '/personal/cart/'], ['Избранное', '/personal/favorites/']];
$info = [
    ['Журнал', '/blog/', array_map(fn($r) => [$r['name'], $r['url']], array_values(bt_blog_rubrics()))],
    ['О компании', '/o-kompanii/'], ['Отзывы о нас', '/otzyvy-o-nas/'],
    ['Оплата и доставка', '/oplata-i-dostavka/'], ['Возврат и обмен', '/vozvrat-i-obmen/'],
    ['Политика конфиденциальности', '/politika-konfidencialnosti/'], ['Пользовательское соглашение', '/polzovatelskoe-soglashenie/'], ['Согласие на обработку персональных данных', '/soglasie-na-obrabotku/'],
    ['Контакты и реквизиты', '/kontakty/'], ['Написать нам', '/kontakty/#form'],
];
?>
<div class="wrap">
  <?php bt_crumbs() ?>
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1><p class="sub">Все разделы сайта на одной странице — для быстрой навигации и для поисковых роботов.</p></div>
  <div class="smap">
    <section><h2>Магазин</h2><?= $ul($shop) ?></section>
    <section><h2>Услуги</h2><?= $ul($services) ?><h2 style="margin-top:24px">Покупателям</h2><?= $ul($account) ?></section>
    <section><h2>Информация</h2><?= $ul($info) ?></section>
  </div>
</div>
