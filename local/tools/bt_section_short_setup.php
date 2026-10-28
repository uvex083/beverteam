<?php
// Разделы каталога: поле «Короткое название (фильтр)» — фильтр слева показывает его вместо длинного SEO-названия раздела.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_section_short_setup.php [show|apply]
if (PHP_SAPI !== 'cli') {
    die();
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$say = fn($s) => print(($apply ? '' : '[show] ') . $s . "\n");
$ibId = (int)bt_iblock('catalog') or die("нет ИБ catalog\n");
$entity = 'IBLOCK_' . $ibId . '_SECTION';

if (!CUserTypeEntity::GetList([], ['ENTITY_ID' => $entity, 'FIELD_NAME' => 'UF_SHORT_NAME'])->Fetch()) {
    $say("поле UF_SHORT_NAME «Короткое название (фильтр)»");
    if ($apply) {
        $label = ['ru' => 'Короткое название (фильтр)', 'en' => 'Short name (filter)'];
        (new CUserTypeEntity())->Add(['ENTITY_ID' => $entity, 'FIELD_NAME' => 'UF_SHORT_NAME', 'USER_TYPE_ID' => 'string', 'SORT' => 100,
            'MULTIPLE' => 'N', 'MANDATORY' => 'N', 'SHOW_FILTER' => 'N', 'SHOW_IN_LIST' => 'Y', 'EDIT_IN_LIST' => 'Y', 'IS_SEARCHABLE' => 'N',
            'EDIT_FORM_LABEL' => $label, 'LIST_COLUMN_LABEL' => $label, 'LIST_FILTER_LABEL' => $label,
            'HELP_MESSAGE' => ['ru' => 'Если заполнено — так раздел подписан в фильтре слева. Название раздела (заголовок страницы, SEO) не меняется']])
            or die("UF_SHORT_NAME\n");
    }
}

// заполняем только пустые — ручную правку в админке не трогаем
foreach (['professionalnye-kofemashiny' => 'Кофемашины Jetinno'] as $code => $short) {
    $s = CIBlockSection::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'UF_SHORT_NAME'])->Fetch();
    if ($s && (string)($s['UF_SHORT_NAME'] ?? '') === '') {
        $say("раздел $code: короткое название «{$short}»");
        $apply and (new CIBlockSection())->Update($s['ID'], ['UF_SHORT_NAME' => $short]);
    }
}
echo "готово\n";
