<?php
// Верхнее меню шапки: DEPTH_LEVEL 2 — пункты выпадающего списка
$sub = ['DEPTH_LEVEL' => 2];
$aMenuLinks = [
    ['Магазин', '/catalog/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Кофе', '/catalog/kofe/', [], $sub, ''],
    ['Чай', '/catalog/chay/', [], $sub, ''],
    ['Кофемашины', '/catalog/professionalnye-kofemashiny/', [], $sub, ''],
    ['Аксессуары', '/catalog/aksessuary/', [], $sub, ''],
    ['Подбор кофе за минуту', '/podbor-kofe/', [], $sub, ''],
    ['Услуги', '/servis/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Аренда кофемашин', '/arenda-kofemashin/', [], $sub, ''],
    ['Ремонт кофемашин', '/servis/remont-kofemashin/', [], $sub, ''],
    ['Кофе в офис', '/kofe-v-ofis/', [], $sub, ''],
    ['Журнал', '/blog/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Все материалы', '/blog/', [], $sub, ''],
    ...array_map(fn($r) => [$r['name'], $r['url'], [], $sub, ''], array_values(bt_blog_rubrics())),
    ['О компании', '/o-kompanii/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Отзывы', '/otzyvy-o-nas/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Контакты', '/kontakty/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Ещё', '#', [], ['DEPTH_LEVEL' => 1], ''],
    ['Наши клиенты', '/nashi-klienty/', [], $sub, ''],
    ['Оплата и доставка', '/oplata-i-dostavka/', [], $sub, ''],
    ['Возврат и обмен', '/vozvrat-i-obmen/', [], $sub, ''],
];
