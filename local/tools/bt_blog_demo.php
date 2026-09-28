<?php
// Демо-наполнение журнала для проверки вёрстки: 14 материалов с картинками (фото из каталога и аренды) и полным набором элементов статьи
// (подзаголовки, фото с подписью, списки, нумерация, цитата, таблица). Картинки получают и существующие материалы без картинок.
// Демо-материалы помечены XML_ID «bt-demo» — перед запуском удалить: ... bt_blog_demo.php delete
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_demo.php [show|apply|delete]. Повторный запуск ничего не дублирует.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$mode = $argv[1] ?? 'show';
$apply = $mode === 'apply';
$say = fn(string $s) => print(($apply || $mode === 'delete' ? '' : '[show] ') . $s . "\n");
$ibId = bt_iblock('journal');
$el = new CIBlockElement();

if ($mode === 'delete') {
    $r = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=XML_ID' => 'bt-demo'], false, false, ['ID', 'NAME']);
    while ($x = $r->Fetch()) {
        CIBlockElement::Delete($x['ID']);
        $say("удалён {$x['ID']} {$x['NAME']}");
    }
    CIBlock::clearIblockTagCache($ibId);
    die("done\n");
}

$sec = [];
$r = CIBlockSection::GetList([], ['IBLOCK_ID' => $ibId], false, ['ID', 'CODE']);
while ($s = $r->Fetch()) {
    $sec[$s['CODE']] = (int)$s['ID'];
}
$kindId = fn(string $xml) => (int)(CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'KIND', 'XML_ID' => $xml])->Fetch()['ID'] ?? 0);

// картинки: фото товаров каталога (по одному с раздела по кругу) и кофемашин из аренды
$pool = [];
foreach ([bt_iblock('catalog'), bt_iblock('rent')] as $src) {
    $r = CIBlockElement::GetList(['IBLOCK_SECTION_ID' => 'ASC', 'SORT' => 'ASC'], ['IBLOCK_ID' => $src, 'ACTIVE' => 'Y', '!PREVIEW_PICTURE' => false], false, ['nTopCount' => 40], ['PREVIEW_PICTURE']);
    while ($x = $r->Fetch()) {
        $pool[] = (int)$x['PREVIEW_PICTURE'];
    }
}
$pool = array_values(array_unique($pool));
shuffle($pool);
$pick = function () use (&$pool) {
    $id = array_shift($pool);
    $pool[] = $id;
    return $id;
};
$src = fn(int $id) => (string)(CFile::GetFileArray($id)['SRC'] ?? '');

// тело статьи со всеми элементами оформления
$body = function (string $topic) use ($pick, $src): string {
    $i1 = $src($pick());
    $i2 = $src($pick());
    return <<<HTML
<p>Разбираем тему «{$topic}» на опыте нашей обжарки и сервисного центра. Коротко, по делу и с цифрами — чтобы можно было сразу применить.</p>
<h2>С чего начать</h2>
<p>Главное — понять, <strong>для кого</strong> и <strong>в каком объёме</strong> вы готовите кофе. От этого зависят зерно, помол и режим обслуживания кофемашины. Подробнее о зерне — в <a href="/magazin/kofe/">каталоге BOTANICA</a>.</p>
<figure><img src="{$i1}" alt="{$topic}"><figcaption>Фото: BEVERTEAM. Подпись к иллюстрации в тексте статьи</figcaption></figure>
<h3>Что проверить в первую очередь</h3>
<ul>
<li>Дату обжарки на упаковке — лучше всего кофе в первые 2–6 недель</li>
<li>Степень обжарки: под эспрессо или под фильтр</li>
<li>Как хранится зерно: закрытая упаковка, без солнца и влаги</li>
<li>Чистоту заварочного блока и капучинатора</li>
</ul>
<h2>Пошагово</h2>
<ol>
<li>Засыпьте свежее зерно и сделайте тестовую порцию эспрессо.</li>
<li>Оцените вкус: кислит — помол мельче, горчит — крупнее.</li>
<li>Запишите настройки, чтобы повторять результат.</li>
<li>Раз в неделю промывайте блок, раз в 1–2 месяца — декальцинация.</li>
</ol>
<blockquote><p>Хорошая чашка — это на 70% свежее зерно и чистая машина, и только на 30% — сама кофемашина.</p><cite>Инженер сервисного центра BEVERTEAM</cite></blockquote>
<h2>Сравнение вариантов</h2>
<table>
<thead><tr><th>Нагрузка</th><th>Чашек в день</th><th>Расход зерна в месяц</th><th>Обслуживание</th></tr></thead>
<tbody>
<tr><td>Дом, малый офис</td><td>до 20</td><td>1–3 кг</td><td>Промывка раз в неделю</td></tr>
<tr><td>Офис</td><td>30–60</td><td>5–10 кг</td><td>ТО раз в 3 месяца</td></tr>
<tr><td>Кафе, HoReCa</td><td>100+</td><td>от 20 кг</td><td>ТО ежемесячно</td></tr>
</tbody>
</table>
<figure><img src="{$i2}" alt="{$topic} — пример"><figcaption>Вторая иллюстрация внутри статьи</figcaption></figure>
<h3>Итог</h3>
<p>Начните с малого: подберите зерно под свою машину и заведите простой график ухода. Если нужна помощь — <a href="/kontakty/">напишите нам</a>, подскажем по телефону.</p>
<hr>
<p><em>Цены и сроки в статье актуальны на дату публикации.</em></p>
HTML;
};

$posts = [
    ['Как настроить помол в автоматической кофемашине', 'vybor-kofe', 'article', '#помол, #настройка, #Jetinno'],
    ['Эспрессо или фильтр: какую обжарку выбрать', 'vybor-kofe', 'article', '#обжарка, #BOTANICA'],
    ['Сколько кофе нужно офису на месяц', 'vybor-kofe', 'article', '#для офиса, #расход'],
    ['Как хранить зерновой кофе дома и в офисе', 'vybor-kofe', 'article', '#хранение, #BOTANICA'],
    ['Декальцинация кофемашины: как часто и чем', 'uhod-za-kofemashinoy', 'article', '#декальцинация, #уход'],
    ['Капучинатор: ежедневная чистка за 2 минуты', 'uhod-za-kofemashinoy', 'article', '#уход, #чистка'],
    ['Пять ошибок, которые сокращают жизнь кофемашины', 'uhod-za-kofemashinoy', 'article', '#ремонт, #уход'],
    ['Когда пора на ТО: признаки усталости машины', 'uhod-za-kofemashinoy', 'article', '#сервис, #ТО'],
    ['Новые сорта BOTANICA в каталоге', 'novosti', 'news', '#BOTANICA, #новинки'],
    ['Jetinno JL15 VIVA снова в наличии', 'novosti', 'news', '#Jetinno, #аренда'],
    ['График работы в праздничные дни', 'novosti', 'news', '#Екатеринбург'],
    ['Дегустация кофе в офисе — бесплатно', 'novosti', 'news', '#дегустация, #для офиса'],
    ['Самовывоз со склада на Колокольной', 'novosti', 'news', '#доставка, #Екатеринбург'],
    ['Скидка на ТО при аренде кофемашины', 'novosti', 'news', '#сервис, #аренда'],
];

$t = strtotime('2026-09-18 10:00');
foreach ($posts as $i => [$name, $secCode, $kind, $tags]) {
    $code = 'demo-' . CUtil::translit($name, 'ru', ['replace_space' => '-', 'replace_other' => '-']);
    if (CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code], [])) {
        continue;
    }
    $say("демо: {$name}");
    if (!$apply) {
        continue;
    }
    $prev = $pick();
    $id = $el->Add(['IBLOCK_ID' => $ibId, 'IBLOCK_SECTION_ID' => $sec[$secCode] ?? false, 'NAME' => $name, 'CODE' => $code, 'XML_ID' => 'bt-demo', 'ACTIVE' => 'Y',
        'ACTIVE_FROM' => ConvertTimeStamp($t - ($i + 1) * 5 * 86400, 'FULL'), 'TAGS' => str_replace('#', '', $tags),
        'PREVIEW_TEXT' => 'Демо-материал для проверки вёрстки журнала: ' . mb_strtolower($name) . '. Коротко о главном и с практическими советами.', 'PREVIEW_TEXT_TYPE' => 'text',
        'DETAIL_TEXT' => $body($name), 'DETAIL_TEXT_TYPE' => 'html',
        'PREVIEW_PICTURE' => CFile::MakeFileArray($prev), 'DETAIL_PICTURE' => CFile::MakeFileArray($prev),
        'PROPERTY_VALUES' => ['KIND' => $kindId($kind), 'READ_TIME' => 3 + $i % 5]]);
    echo $id ? "  ID {$id}\n" : '  ошибка: ' . $el->LAST_ERROR . "\n";
}

// существующим материалам без картинок — анонс и детальная картинка
$r = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '!XML_ID' => 'bt-demo', 'PREVIEW_PICTURE' => false], false, false, ['ID', 'NAME']);
while ($x = $r->Fetch()) {
    $say("картинки: {$x['NAME']}");
    if ($apply) {
        $p = $pick();
        $el->Update($x['ID'], ['PREVIEW_PICTURE' => CFile::MakeFileArray($p), 'DETAIL_PICTURE' => CFile::MakeFileArray($p)]) or print('  ошибка: ' . $el->LAST_ERROR . "\n");
    }
}
$apply and CIBlock::clearIblockTagCache($ibId);
echo "done\n";
