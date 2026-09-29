<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
// title и description — вкладка SEO первого блока страницы в админке; #RENT_FREE_KG# — минимальный объём для бесплатной аренды
bt_page_seo('sub_head', 'Кофе по подписке в Екатеринбурге — кофемашина в аренду бесплатно | BEVERTEAM',
    'Кофе по подписке для офиса и кафе: зерно BOTANICA по графику и кофемашина Jetinno в аренду бесплатно при заказе от #RENT_FREE_KG# кг кофе в месяц. Екатеринбург.');
$APPLICATION->SetPageProperty('keywords', 'кофе по подписке, кофе для офиса, аренда кофемашины бесплатно');
$APPLICATION->SetTitle('Кофе по подписке. Кофемашина — бесплатно');
require $_SERVER['DOCUMENT_ROOT'] . '/local/parts/podpiska.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
