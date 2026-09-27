<?php
// Отзывы с главной старого сайта (beverteam.ru), которых не было на странице «Отзывы о нас». Порядок — как на старой главной.
// Повтор не дублирует (сверка по имени и началу текста). Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_reviews_import.php [show|apply]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$ibId = bt_iblock('reviews');
$reviews = [
    [2, 'Регина Р.', 'Всегда вкусный кофе, который радует каждый день. Персонал очень дружелюбный и всегда готов помочь с выбором. Спасибо, что делаете утро незабываемым!'],
    [4, 'Юлия М.', 'Регулярно покупаю здесь зерна и каждый раз наслаждаюсь отличным вкусом и ароматом. Заказ и доставка всегда проходят безупречно. Рекомендую всем любителям качественного кофе!'],
    [6, 'Наталья', 'Сдавала в ремонт свою кофемашину, которая верой и правдой прослужила 20 лет. Я и не надеялась, что можно найти запчасти и починить такую старинную технику за неделю и за небольшие деньги, но специалист этого сервиса справился с этой сложной задачей. Спасибо огромное! Уже 2 недели всей семьей пьем ароматный кофе, кофемашина работает исправно. Купила у ребят еще свежеобжаренный зерновой кофе! Очень вкусный! В ассортименте есть еще и чаи. Цены адекватные! Рекомендую Beverteam.ru и специалистов этого сервиса.'],
    [8, 'Елена', "Обращались от организации - Василий научил делать перезагрузку кофемашины и даже не хотел брать денег. Очень внимательный и человечный подход! Диагностику провели очень быстро - вернули аппарат в тот же день!\nСпасибо, довольны сотрудничеством!"],
];
$el = new CIBlockElement();
foreach ($reviews as [$sort, $name, $text]) {
    $dup = false;
    $r = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=NAME' => $name], false, false, ['ID', 'PREVIEW_TEXT']);
    while ($row = $r->Fetch()) {
        $dup = $dup || mb_substr(trim($row['PREVIEW_TEXT']), 0, 40) === mb_substr($text, 0, 40);
    }
    if ($dup) {
        continue;
    }
    echo ($apply ? '' : '[show] ') . "отзыв «{$name}»\n";
    if ($apply) {
        $id = $el->Add(['IBLOCK_ID' => $ibId, 'NAME' => $name, 'ACTIVE' => 'Y', 'SORT' => $sort, 'PREVIEW_TEXT' => $text, 'PREVIEW_TEXT_TYPE' => 'text',
            'PROPERTY_VALUES' => ['RATING' => 5]]);
        echo $id ? "  ID {$id}\n" : '  ошибка: ' . $el->LAST_ERROR . "\n";
    }
}
$apply and CIBlock::clearIblockTagCache($ibId);
echo "done\n";
