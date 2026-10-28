<?php
// Специалисты (ИБ «О компании: команда»): страница специалиста, автор статей, «Вопросы и ответы» и лента установок на главной.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_experts_setup.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require __DIR__ . '/bt_setup_lib.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$fail = fn(string $s) => die("ERROR: $s\n");
$ib = fn(string $code) => CIBlock::GetList([], ['=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch() ?: $fail("нет ИБ $code");

$team = $ib('about_team');
bt_ib_ensure(['IBLOCK_TYPE_ID' => $team['IBLOCK_TYPE_ID'], 'CODE' => 'about_team', 'NAME' => $team['NAME'], 'SORT' => $team['SORT']], [
    'SPECIALIZATION' => ['Специализация и компетенции', 'S', ['MULTIPLE' => 'Y', 'MULTIPLE_CNT' => 3, 'HINT' => 'По одной теме в строке: «ремонт кофемашин Jetinno», «обжарка кофе»']],
    'EDUCATION' => ['Образование, сертификаты, достижения', 'S:HTML', []],
    'PROFILES' => ['Профили в сети', 'S', ['MULTIPLE' => 'Y', 'MULTIPLE_CNT' => 2, 'HINT' => 'Ссылки на профессиональные профили: VK, Telegram-канал, hh.ru']],
], $apply, $say, $fail);
if ($team['DETAIL_PAGE_URL'] !== '/o-kompanii/komanda/#ELEMENT_CODE#/') {
    $say('ИБ about_team: адрес страницы /o-kompanii/komanda/#ELEMENT_CODE#/, код из имени');
    if ($apply) {
        $fields = CIBlock::GetFields($team['ID']);
        $fields['CODE']['DEFAULT_VALUE'] = ['TRANSLITERATION' => 'Y', 'TRANS_LEN' => 100, 'UNIQUE' => 'Y', 'TRANS_CASE' => 'L', 'TRANS_SPACE' => '-', 'TRANS_OTHER' => '-', 'TRANS_EAT' => 'Y', 'USE_GOOGLE' => 'N'];
        (new CIBlock())->Update($team['ID'], ['DETAIL_PAGE_URL' => '/o-kompanii/komanda/#ELEMENT_CODE#/', 'FIELDS' => $fields]) or $fail('about_team');
    }
}

$journal = $ib('journal');
bt_ib_ensure(['IBLOCK_TYPE_ID' => $journal['IBLOCK_TYPE_ID'], 'CODE' => 'journal', 'NAME' => $journal['NAME'], 'SORT' => $journal['SORT']], [
    'AUTHOR' => ['Автор статьи', 'E', ['LINK_IBLOCK_ID' => $team['ID'], 'HINT' => 'Специалист из «О компании: команда». Пусто — автор статьи компания']],
], $apply, $say, $fail);

// «Вопросы и ответы» на главной: заголовок и вопросы — отдельные ИБ, между SEO-текстом и картой
$head = bt_ib_ensure(['IBLOCK_TYPE_ID' => 'main', 'CODE' => 'main_faq_head', 'NAME' => 'Вопросы и ответы: заголовок', 'SORT' => 174,
    'DESCRIPTION' => 'Заголовок и подзаголовок блока вопросов на главной. Сам блок виден, когда в «Вопросы и ответы: вопросы» есть активные вопросы.'], [
    'TITLE' => ['Заголовок', 'S', []],
    'SUBTITLE' => ['Подзаголовок', 'S', ['ROW_COUNT' => 3]],
], $apply, $say, $fail);
if ($apply && $head) {
    bt_grid_sort('main', $head);
    bt_el_seed($head, 'main_faq_head', ['NAME' => 'Вопросы и ответы', 'SORT' => 10], ['TITLE' => 'Вопросы и ответы'], $say, $fail);
}
$id = bt_ib_ensure(['IBLOCK_TYPE_ID' => 'main', 'CODE' => 'main_faq', 'NAME' => 'Вопросы и ответы: вопросы', 'SORT' => 176,
    'DESCRIPTION' => 'Вопросы на главной после SEO-текста. Название элемента — вопрос, текст анонса — ответ, порядок — сортировка. Пока нет активных вопросов, блок на сайте не виден.'], [], $apply, $say, $fail);
$apply && $id and bt_grid_sort('main', $id);

// подписи ленты установок (фото берутся со страницы «Наши клиенты», когда она открыта)
$about = $ib('main_about');
bt_ib_ensure(['IBLOCK_TYPE_ID' => 'main', 'CODE' => 'main_about', 'NAME' => $about['NAME'], 'SORT' => $about['SORT']], [
    'WORKS_TITLE' => ['Лента фото установок: заголовок', 'S', ['HINT' => 'Пусто — «Наши установки». Фото берутся со страницы «Наши клиенты», лента видна, когда страница открыта']],
    'WORKS_BTN' => ['Лента фото установок: текст ссылки', 'S', ['HINT' => 'Пусто — «Все работы →». Ведёт на /nashi-klienty/']],
], $apply, $say, $fail);

// подсказки в описаниях инфоблоков: что на сайте собирается само
$hint = [
    'main_bean' => 'Следом за выбранным кофе в слайдер сами попадают кофе с метками «Хит» и «Топ продаж» (до трёх) — метки ставятся в карточке товара.',
    'about_team' => 'Страница специалиста /o-kompanii/komanda/<адрес>/ появляется, когда заполнены «Адрес страницы специалиста» и «Биография». Тогда карточка в «Команде» становится ссылкой, а специалиста можно выбрать автором статьи журнала.',
];
foreach ($hint as $code => $text) {
    $x = $ib($code);
    if (!str_contains((string)$x['DESCRIPTION'], $text)) {
        $say("описание ИБ $code: + подсказка");
        $apply and (new CIBlock())->Update($x['ID'], ['DESCRIPTION' => trim($x['DESCRIPTION'] . "\n\n" . $text), 'DESCRIPTION_TYPE' => 'text']);
    }
}
echo "готово\n";
