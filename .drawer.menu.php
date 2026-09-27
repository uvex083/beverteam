<?php
// Мобильное меню: DEPTH_LEVEL 1 — пункты первого экрана (accent — крупные), 2 и 3 — слайды; разделы каталога добавляет .drawer.menu_ext.php
$a = ['DEPTH_LEVEL' => 1, 'accent' => 'Y'];
$sub = ['DEPTH_LEVEL' => 2];
$rubrics = array_values(bt_blog_rubrics());
$aMenuLinks = array_merge([
    ['Каталог', '/magazin/', [], $a, ''],
    ['Аренда кофемашин', '/arenda-kofemashin/', [], $a, ''],
    ['Для офиса', '/arenda-kofemashin/', [], $sub, ''],
    ['Для кафе и HoReCa', '/arenda-kofemashin/', [], $sub, ''],
    ['На мероприятие', '/arenda-kofemashin/#event', [], $sub, ''],
    ['Рассчитать стоимость аренды', '/arenda-kofemashin/#calc', [], $sub, ''],
    ['Сервис и ремонт', '/servis/', [], $a, ''],
    ['Ремонт кофемашин', '/servis/remont-kofemashin/', [], $sub, ''],
    ['Плановое ТО и чистка', '/servis/#price', [], $sub, ''],
    ['Продажа оборудования', '/magazin/professionalnye-kofemashiny/', [], $sub, ''],
    ['Вызвать инженера', '/servis/remont-kofemashin/#form', [], $sub, ''],
    ['Кофе по подписке', '/podpiska/', [], $a, ''],
    ['Журнал', '/blog/', [], $a, ''],
], count($rubrics) > 1 ? array_map(fn($r) => [$r['name'], $r['url'], [], $sub, ''], $rubrics) : [], [
    ['Подбор кофе', '/podbor-kofe/', [], $a + ['badge' => 'за минуту'], ''],
    ['О компании', '/o-kompanii/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Отзывы', '/otzyvy-o-nas/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Оплата и доставка', '/oplata-i-dostavka/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Возврат и обмен', '/vozvrat-i-obmen/', [], ['DEPTH_LEVEL' => 1], ''],
    ['Контакты', '/kontakty/', [], ['DEPTH_LEVEL' => 1], ''],
]);
