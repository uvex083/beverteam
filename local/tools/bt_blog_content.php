<?php
// Наполнение журнала: статья «Как выбрать зерновой кофе…», новости магазина и теги статей со старого сайта.
// Повторный запуск ничего не дублирует (поиск по символьному коду). Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_content.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$ibId = bt_iblock('journal');

$section = function (string $name, string $code, int $sort) use ($ibId, $apply, $say): int {
    $s = CIBlockSection::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($s) {
        return (int)$s['ID'];
    }
    $say("рубрика «{$name}»");
    if (!$apply) {
        return 0;
    }
    $bs = new CIBlockSection();
    return (int)$bs->Add(['IBLOCK_ID' => $ibId, 'NAME' => $name, 'CODE' => $code, 'SORT' => $sort, 'ACTIVE' => 'Y']) ?: die($bs->LAST_ERROR . "\n");
};
$kind = function (string $xml) use ($ibId): int {
    return (int)(CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'KIND', 'XML_ID' => $xml])->Fetch()['ID'] ?? 0);
};

$secChoice = $section('Выбор кофе', 'vybor-kofe', 20);
$secNews = $section('Новости', 'novosti', 100);

$posts = [
    [
        'code' => 'kak-vybrat-zernovoj-kofe-dlya-avtomaticheskoj-kofemashiny', 'sec' => $secChoice, 'kind' => 'article', 'date' => '20.09.2026 10:00:00', 'min' => 6,
        'name' => 'Как выбрать зерновой кофе для автоматической кофемашины',
        'tags' => 'выбор зерна, автоматическая кофемашина, обжарка, BOTANICA',
        'preview' => 'Какую обжарку выбрать, чем арабика отличается от смеси с робустой и сколько зерна брать на месяц — разбираем на примере кофе BOTANICA и кофемашин Jetinno.',
        'detail' => <<<'HTML'
<p>Автоматическая кофемашина сама мелет зерно, дозирует порцию и варит напиток. От зерна зависит почти всё: вкус в чашке, стабильность эспрессо и то, как часто придётся чистить кофемашину. Рассказываем, на что смотреть при выборе.</p>
<h2>Обжарка: средняя — самый надёжный вариант</h2>
<p>Для автоматических кофемашин лучше всего подходит средняя обжарка «под эспрессо». Такое зерно раскрывается при коротком времени экстракции и даёт плотный, сбалансированный вкус.</p>
<ul>
<li><strong>Светлая обжарка</strong> — яркая кислотность и фруктовые ноты. Хороша для фильтра, в автомате может получиться кисловатой.</li>
<li><strong>Средняя</strong> — баланс сладости, тела и кислотности. Универсальна и для эспрессо, и для напитков с молоком.</li>
<li><strong>Тёмная</strong> — горчинка и шоколадные ноты. Масло на поверхности зерна со временем забивает жернова и заварочный узел.</li>
</ul>
<h2>Арабика или смесь с робустой</h2>
<p>Моносорта арабики — для тех, кто пьёт эспрессо и американо и хочет почувствовать характер конкретной страны: Эфиопия даёт цветочные и цитрусовые ноты, Бразилия — орехи и шоколад. Смесь с робустой (например, 80% Бразилии и 20% робусты) даёт плотную пенку и крепость, которые хорошо держатся в капучино и латте.</p>
<h2>Свежесть важнее всего</h2>
<p>Зерно лучше всего раскрывается в первые недели после обжарки. Покупайте кофе объёмом, который выпиваете за месяц, и храните в закрытой упаковке вдали от света и тепла. В бункер кофемашины засыпайте столько зерна, сколько уйдёт за 1–2 дня.</p>
<h2>Сколько зерна нужно</h2>
<p>На одну порцию эспрессо уходит 8–10 г зерна. Если в день готовите 5 чашек, за месяц понадобится примерно 1,5 кг. Для офиса на 20 человек — 6–9 кг в месяц: при таком объёме выгоднее брать кофе по подписке, а кофемашину можно получить в пользование бесплатно.</p>
<h2>С чего начать</h2>
<p>Если не знаете, какой сорт выбрать, пройдите <a href="/podbor-kofe/">подбор кофе за минуту</a> — пять вопросов, и мы предложим сорт BOTANICA с ценой. Весь ассортимент — в разделе <a href="/magazin/kofe/">«Кофе в зёрнах»</a>.</p>
HTML,
    ],
    [
        'code' => 'zerno-mesyaca-efiopiya-oromiya', 'sec' => $secNews, 'kind' => 'news', 'date' => '22.09.2026 12:00:00', 'min' => 1,
        'name' => 'Зерно месяца — BOTANICA Эфиопия Оромия',
        'tags' => 'BOTANICA, зерно месяца, опт',
        'preview' => 'В сентябре зерно месяца — Эфиопия Оромия, оценка Q 82,5. Ноты чёрного чая, сухофруктов, лимона и шоколада. При заказе от 30 кг — 1 940 ₽ за килограмм.',
        'detail' => <<<'HTML'
<p>В сентябре зерно месяца — <a href="/magazin/product/botanica-efiopiya-oromiya/">BOTANICA Эфиопия Оромия</a>. Оценка по шкале SCA — Q 82,5.</p>
<p>Во вкусе — чёрный чай, сухофрукты, лимон и шоколад. Кофе хорошо раскрывается и в эспрессо, и в напитках с молоком.</p>
<p>Цена — 2 687 ₽ за килограмм. Чем больше объём, тем ниже цена: при заказе от 30 кг — 1 940 ₽ за килограмм.</p>
HTML,
    ],
    [
        'code' => 'arenda-kofemashin-jetinno-ot-3500', 'sec' => $secNews, 'kind' => 'news', 'date' => '15.09.2026 12:00:00', 'min' => 1,
        'name' => 'Аренда кофемашин Jetinno — от 3 500 ₽ в месяц',
        'tags' => 'аренда, Jetinno, для офиса',
        'preview' => 'Три модели Jetinno для дома, офиса и кафе. Можно платить фиксированную сумму или получить кофемашину бесплатно при регулярном заказе кофе.',
        'detail' => <<<'HTML'
<p>Сдаём в аренду автоматические кофемашины Jetinno — для дома, офиса и кафе. Сервисное обслуживание включено в стоимость.</p>
<ul>
<li><strong>Jetinno JL05</strong> — дом и малый офис, до 10 чашек в день: 3 500 ₽ в месяц или бесплатно при заказе от 3 кг кофе.</li>
<li><strong>Jetinno JL15 (VIVA)</strong> — офис, до 60 чашек в день: 7 500 ₽ в месяц или бесплатно от 6 кг кофе.</li>
<li><strong>Jetinno JL36</strong> — кафе и HoReCa, до 100 чашек в день: 11 000 ₽ в месяц или бесплатно от 9 кг кофе.</li>
</ul>
<p>Работаем с ИП и юрлицами по договору, оплата по счёту. Подобрать модель и рассчитать стоимость — на странице <a href="/arenda-kofemashin/">аренды кофемашин</a>.</p>
HTML,
    ],
    [
        'code' => 'besplatnaya-dostavka-po-ekaterinburgu', 'sec' => $secNews, 'kind' => 'news', 'date' => '05.09.2026 12:00:00', 'min' => 1,
        'name' => 'Бесплатная доставка по Екатеринбургу от 3 000 ₽',
        'tags' => 'доставка, Екатеринбург',
        'preview' => 'Курьер по Екатеринбургу — бесплатно при заказе от 3 000 ₽. По России отправляем СДЭК, самовывоз — со склада на Колокольной, 31А.',
        'detail' => <<<'HTML'
<p>При заказе от 3 000 ₽ доставим по Екатеринбургу бесплатно. Для заказов меньше — курьер от 350 ₽.</p>
<p>По России отправляем СДЭК, срок — 2–7 дней, стоимость считается при оформлении заказа.</p>
<p>Самовывоз — со склада на ул. Колокольной, 31А, пн–пт с 10:00 до 17:00. Подробнее — в разделе <a href="/oplata-i-dostavka/">«Оплата и доставка»</a>.</p>
HTML,
    ],
    [
        'code' => 'servisnyj-centr-jetinno-vyezd-inzhenera', 'sec' => $secNews, 'kind' => 'news', 'date' => '28.08.2026 12:00:00', 'min' => 1,
        'name' => 'Ремонт и обслуживание кофемашин Jetinno с выездом инженера',
        'tags' => 'сервис, ремонт, Jetinno',
        'preview' => 'Авторизованный сервис Jetinno: диагностика, ремонт и плановое ТО в офисе, кафе или у вас дома. Оригинальные запчасти — на складе.',
        'detail' => <<<'HTML'
<p>Мы — авторизованный сервис Jetinno. Обслуживаем модели JL05, JL15 VIVA, JL32, JL33 и JL36, оригинальные запчасти держим на складе.</p>
<p>Инженер выезжает по Екатеринбургу пн–пт с 9:00 до 17:00: диагностика, ремонт, плановое ТО и чистка.</p>
<p>Оставить заявку на вызов инженера — на странице <a href="/servis/remont-kofemashin/">ремонта кофемашин</a>.</p>
HTML,
    ],
];

$el = new CIBlockElement();
foreach ($posts as $p) {
    if (CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $p['code']], false, false, ['ID'])->Fetch()) {
        continue;
    }
    $say("{$p['kind']}: «{$p['name']}»");
    if (!$apply) {
        continue;
    }
    $id = $el->Add([
        'IBLOCK_ID' => $ibId, 'IBLOCK_SECTION_ID' => $p['sec'], 'NAME' => $p['name'], 'CODE' => $p['code'], 'ACTIVE' => 'Y', 'ACTIVE_FROM' => $p['date'],
        'TAGS' => $p['tags'], 'PREVIEW_TEXT' => $p['preview'], 'PREVIEW_TEXT_TYPE' => 'text', 'DETAIL_TEXT' => $p['detail'], 'DETAIL_TEXT_TYPE' => 'html',
        'PROPERTY_VALUES' => ['KIND' => $kind($p['kind']), 'READ_TIME' => $p['min']],
    ]);
    echo $id ? "  ID {$id}\n" : '  ошибка: ' . $el->LAST_ERROR . "\n";
}

// теги статей, перенесённых со старого сайта
$oldTags = [
    'kak-pravilno-chistit-avtomaticheskuyu-kofemashinu-v-domashnih-usloviyah' => 'уход, чистка, автоматическая кофемашина, декальцинация',
    'kak-pravilno-chistit-kofemashinu' => 'уход, чистка, рожковая кофемашина',
];
foreach ($oldTags as $code => $tags) {
    $row = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code], false, false, ['ID', 'TAGS'])->Fetch();
    if ($row && trim((string)$row['TAGS']) === '') {
        $say("теги «{$code}»: {$tags}");
        $apply and $el->Update($row['ID'], ['TAGS' => $tags]);
    }
}
$apply and CIBlock::clearIblockTagCache($ibId);
echo "done\n";
