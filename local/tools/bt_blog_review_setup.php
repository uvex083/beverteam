<?php
// Журнал: свойство «Вычитка» (на вычитке / проверено) — пока статья на вычитке, на сайте видна плашка «Черновик».
// Новые статьи (с вопросами к вычитке) — «На вычитке» и включаются; остальные — «Проверено»; демо-заглушки demo-… удаляются.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_review_setup.php [show|apply]. Повторный запуск правки редактора не трогает.

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
$ibId = bt_iblock('journal') or die("нет ИБ journal\n");

$prop = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'REVIEW'])->Fetch();
if (!$prop) {
    $say('свойство REVIEW «Вычитка»');
    if ($apply) {
        (new CIBlockProperty())->Add(['IBLOCK_ID' => $ibId, 'CODE' => 'REVIEW', 'NAME' => 'Вычитка', 'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'L', 'SORT' => 5, 'ACTIVE' => 'Y',
            'HINT' => 'Пока стоит «На вычитке», на сайте у статьи видна плашка «Черновик». Перед запуском сайта такие статьи выключить',
            'VALUES' => [['XML_ID' => 'draft', 'VALUE' => 'На вычитке', 'SORT' => 10, 'DEF' => 'Y'], ['XML_ID' => 'ready', 'VALUE' => 'Проверено, готово к публикации', 'SORT' => 20]]])
            or die("prop REVIEW\n");
    }
}
$enum = [];
$r = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'REVIEW']);
while ($v = $r->Fetch()) {
    $enum[$v['XML_ID']] = (int)$v['ID'];
}

$el = new CIBlockElement();
$r = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => $ibId], false, false, ['ID', 'CODE', 'NAME', 'ACTIVE', 'PROPERTY_REVIEW', 'PROPERTY_EDITOR_NOTES']);
while ($a = $r->Fetch()) {
    if (str_starts_with((string)$a['CODE'], 'demo-')) {
        $say("удалить демо: {$a['NAME']}");
        $apply and CIBlockElement::Delete($a['ID']);
        continue;
    }
    if ($a['PROPERTY_REVIEW_ENUM_ID']) {
        continue;
    }
    $notes = $a['PROPERTY_EDITOR_NOTES_VALUE'];
    $isNew = trim(strip_tags((string)(is_array($notes) ? ($notes['TEXT'] ?? '') : $notes))) !== '';
    $say(($isNew ? 'на вычитке, включить: ' : 'проверено: ') . $a['NAME']);
    if ($apply && $enum) {
        CIBlockElement::SetPropertyValuesEx($a['ID'], $ibId, ['REVIEW' => $enum[$isNew ? 'draft' : 'ready']]);
        $isNew && $a['ACTIVE'] !== 'Y' and $el->Update($a['ID'], ['ACTIVE' => 'Y']);
    }
}
CIBlock::clearIblockTagCache($ibId);
echo "done\n";
