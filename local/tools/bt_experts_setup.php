<?php
// Специалисты (ИБ «О компании: команда»): поля страницы специалиста /o-kompanii/komanda/<код>/, автор у статей журнала,
// блок «Вопросы и ответы» на главной. Пустые поля ничего на сайте не меняют: страница специалиста появляется, когда заполнены адрес и биография.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_experts_setup.php [show|apply]. Повторный запуск ничего не дублирует.

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
// адрес страницы специалиста — символьный код элемента, предлагается из имени
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

$id = bt_ib_ensure(['IBLOCK_TYPE_ID' => 'main', 'CODE' => 'main_faq', 'NAME' => 'Вопросы и ответы', 'SORT' => 175,
    'DESCRIPTION' => 'Блок на главной после SEO-текста. Название элемента — вопрос, текст анонса — ответ. Пока нет активных вопросов, блок на сайте не виден.'], [], $apply, $say, $fail);
$apply && $id and bt_grid_sort('main', $id);
echo "готово\n";
