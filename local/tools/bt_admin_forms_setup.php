<?php
// Формы редактирования блоков страниц в админке: только поля, которые выводит сайт, в порядке страницы, с понятными подписями.
// Общая настройка для всех пользователей (личные настройки форм этих инфоблоков сбрасываются). Данные элементов не меняются.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_admin_forms_setup.php [show|apply|rollback]

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/interface/admin_form.php';
CModule::IncludeModule('iblock');

use Bitrix\Main\Config\Option;

$mode = $argv[1] ?? 'show';
$apply = $mode === 'apply';
$say = fn(string $s) => print(($apply || $mode === 'rollback' ? '' : '[show] ') . $s . "\n");

// код ИБ => [как выводит сайт: block — один элемент, list/blocks — список, поля в порядке страницы]
$forms = [
    'about_cert' => ['block', ['caption', 'title', 'subtitle', 'pic']],
    'about_faq' => ['list', ['name', 'text']],
    'about_form' => ['block', ['caption', 'title', 'subtitle', 'btn_text']],
    'about_history' => ['list', ['name', 'text', 'demo']],
    'about_intro' => ['block', ['text', 'items', 'pic']],
    'about_numbers' => ['list', ['name', 'text', 'demo']],
    'about_partners' => ['list', ['name', 'text', 'link', 'pic', 'demo']],
    'about_places' => ['list', ['name', 'pic', 'demo']],
    'about_rating' => ['block', ['title', 'subtitle', 'btn_link', 'demo']],
    'about_team' => ['list', ['name', 'text', 'pic', 'demo']],
    'about_values' => ['block', ['caption', 'title', 'text']],
    'about_values_items' => ['list', ['name', 'text', 'link', 'icon']],
    'about_work' => ['block', ['title', 'items', 'links']],
    'contacts_how' => ['list', ['name', 'text']],
    'contacts_photos' => ['list', ['name', 'text', 'pic']],
    'main_about' => ['block', ['caption', 'title', 'text', 'form_title', 'btn_text', 'btn_link', 'btn2_text', 'btn2_link']],
    'main_bean' => ['block', ['caption', 'title', 'subtitle', 'product', 'btn_text', 'btn_link', 'btn2_text', 'btn2_link']],
    'main_catalog' => ['block', ['title', 'subtitle', 'btn_text', 'btn_link']],
    'main_config' => ['block', ['title', 'subtitle', 'items', 'btn_text', 'btn_link']],
    'main_facts' => ['list', ['name', 'text']],
    'main_hero' => ['block', ['caption', 'title', 'highlight', 'subtitle', 'link_text', 'link_url', 'btn_text', 'btn_link', 'btn2_text', 'btn2_link', 'btn3_text', 'btn3_link']],
    'main_incl' => ['block', ['caption', 'title', 'items', 'btn_text', 'btn_link']],
    'main_journal' => ['block', ['title', 'subtitle', 'btn_text', 'btn_link']],
    'main_map' => ['block', ['title', 'btn_text', 'btn_link']],
    'main_rent' => ['block', ['title', 'subtitle', 'tab_rent', 'tab_sale', 'btn_text', 'btn_link', 'btn2_text', 'btn2_link']],
    'main_reviews' => ['block', ['title', 'subtitle', 'btn_text', 'btn_link']],
    'main_seo' => ['block', ['title', 'text']],
    'main_service' => ['block', ['caption', 'title', 'highlight', 'subtitle', 'items', 'btn_text', 'btn_link', 'btn2_text', 'btn2_link', 'pic']],
    'main_steps' => ['block', ['title', 'subtitle', 'items']],
    'main_strip' => ['list', ['name', 'icon']],
    'main_sub' => ['block', ['caption', 'title', 'highlight', 'subtitle', 'items', 'btn_text', 'btn_link', 'btn2_text', 'btn2_link', 'pic']],
    'main_utp' => ['block', ['title', 'subtitle']],
    'main_utp_items' => ['list', ['name', 'text', 'icon']],
    'rent_calc' => ['block', ['title', 'subtitle', 'btn_text']],
    'rent_coffee' => ['block', ['title', 'subtitle', 'btn_text', 'btn_link']],
    'rent_contract' => ['block', ['caption', 'title', 'subtitle', 'items', 'btn_text', 'file']],
    'rent_event' => ['block', ['title', 'subtitle', 'btn_text', 'btn_link']],
    'rent_facts' => ['blocks', ['name', 'html']],
    'rent_faq' => ['blocks', ['name', 'html']],
    'rent_form' => ['block', ['title', 'subtitle', 'btn_text']],
    'rent_links' => ['blocks', ['name', 'html', 'link']],
    'rent_models_head' => ['block', ['title', 'btn_text', 'btn_link']],
    'rent_segments' => ['blocks', ['name', 'html', 'place']],
    'rent_seo' => ['block', ['title', 'text']],
    'rent_steps' => ['block', ['title', 'items']],
    'rent_terms' => ['list', ['name', 'text', 'icon']],
    'rent_top' => ['block', ['caption', 'subtitle', 'items', 'btn_text', 'btn_link']],
    'rent_vs' => ['block', ['title', 'items', 'minus']],
    'repair_brands' => ['blocks', ['name', 'models', 'authorized']],
    'repair_contract' => ['block', ['caption', 'title', 'subtitle', 'items', 'btn_text', 'btn_link', 'btn2_text', 'file']],
    'repair_errors' => ['blocks', ['name', 'html', 'engineer']],
    'repair_errors_head' => ['block', ['title', 'subtitle']],
    'repair_facts' => ['blocks', ['name', 'html']],
    'repair_faq' => ['blocks', ['name', 'html']],
    'repair_faq_head' => ['block', ['title']],
    'repair_form' => ['block', ['caption', 'title', 'subtitle', 'btn_text']],
    'repair_geo' => ['block', ['caption', 'title', 'subtitle', 'items', 'districts']],
    'repair_jetinno' => ['block', ['title', 'subtitle']],
    'repair_links' => ['blocks', ['name', 'html', 'link']],
    'repair_price' => ['blocks', ['name', 'price', 'term']],
    'repair_price_head' => ['block', ['caption', 'title', 'btn_text', 'btn_link']],
    'repair_reviews' => ['block', ['title', 'btn_text', 'btn_link']],
    'repair_seo' => ['block', ['title', 'text']],
    'repair_steps' => ['block', ['title', 'items']],
    'repair_strip' => ['list', ['name', 'text', 'icon']],
    'repair_symptoms' => ['blocks', ['name', 'html', 'price']],
    'repair_symptoms_head' => ['block', ['title', 'subtitle']],
    'repair_top' => ['block', ['caption', 'subtitle', 'items', 'note', 'btn_text', 'btn_link']],
    'repair_types' => ['block', ['title', 'subtitle', 'items', 'brands']],
    'repair_why_head' => ['block', ['title']],
    'servis_brands' => ['block', ['title', 'subtitle']],
    'servis_dirs' => ['blocks', ['name', 'html', 'items', 'price', 'price_note', 'link', 'link_text']],
    'servis_head' => ['block', ['subtitle']],
    'servis_price' => ['block', ['caption', 'title', 'subtitle', 'items', 'btn_text', 'btn_link']],
    'sub_faq' => ['blocks', ['name', 'html']],
    'sub_head' => ['block', ['caption', 'title', 'subtitle', 'btn_text']],
    'sub_how' => ['block', ['title', 'items']],
];
// подписи стандартных полей у списков: код ИБ => [name, текст анонса, картинка]
$labels = [
    'about_faq' => ['Вопрос', 'Ответ'], 'rent_faq' => ['Вопрос', 'Ответ'], 'repair_faq' => ['Вопрос', 'Ответ'], 'sub_faq' => ['Вопрос', 'Ответ'],
    'about_history' => ['Год', 'Событие'], 'about_numbers' => ['Цифра (например, 500+)', 'Подпись под цифрой'],
    'main_facts' => ['Цифра (крупно)', 'Подпись под цифрой'], 'repair_facts' => ['Цифра (крупно)', 'Подпись под цифрой'],
    'about_team' => ['Имя и фамилия', 'Должность', 'Фото'], 'about_places' => ['Подпись к фото', '', 'Фото'],
    'about_partners' => ['Компания', 'Подпись (кто это)', 'Логотип'], 'contacts_photos' => ['Подпись к фото', 'Текст', 'Фото'],
    'about_values_items' => ['Заголовок карточки', 'Текст карточки'], 'main_utp_items' => ['Заголовок карточки', 'Текст карточки'],
    'repair_strip' => ['Заголовок карточки', 'Текст карточки'], 'main_strip' => ['Текст'], 'rent_terms' => ['Заголовок карточки', 'Текст карточки'],
    'rent_facts' => ['Цифра (крупно)', 'Подпись под цифрой'], 'rent_segments' => ['Кому подходит', 'Текст'], 'rent_links' => ['Название', 'Текст'],
    'contacts_how' => ['Заголовок', 'Текст'], 'repair_symptoms' => ['Симптом', 'Описание'], 'repair_errors' => ['Ошибка на экране', 'Что она значит'],
    'repair_price' => ['Услуга'], 'repair_links' => ['Название', 'Текст'], 'repair_brands' => ['Марка'], 'servis_dirs' => ['Направление', 'Описание'],
];
// первые блоки страниц: вкладка SEO — title, description (и H1 у ремонта)
$seo = ['main_hero' => false, 'rent_top' => true, 'sub_head' => false, 'repair_top' => true];

$backupKey = 'bt.admin_forms_backup';
if ($mode === 'rollback') {
    $b = json_decode(Option::get('main', $backupKey, ''), true) ?: [];
    foreach ($b as $formId => $opt) {
        $say("вернуть $formId");
        $opt ? CUserOptions::SetOption('form', $formId, $opt, true) : CUserOptions::DeleteOption('form', $formId, true);
    }
    Option::delete('main', ['name' => $backupKey]);
    echo "done\n";
    return;
}

$backup = json_decode(Option::get('main', $backupKey, ''), true) ?: [];
foreach ($forms as $code => [$kind, $keys]) {
    $ibId = bt_iblock($code);
    if (!$ibId) {
        echo "нет ИБ $code\n";
        continue;
    }
    $props = [];
    $r = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ibId]);
    while ($p = $r->Fetch()) {
        $props[strtolower($p['CODE'])] = $p;
    }
    [$lName, $lText, $lPic] = ($labels[$code] ?? []) + ['', '', ''];
    $single = $kind === 'block';
    $fields = ['ACTIVE' => $single ? 'Активность' : 'Показывать на сайте',
        'NAME' => $single ? 'Название (видно только в админке)' : ($lName ?: 'Название')];
    $single or $fields['SORT'] = 'Порядок (меньше — выше)';
    $miss = [];
    foreach ($keys as $k) {
        if ($k === 'name') {
            continue;
        }
        $text = $kind === 'list' ? 'text' : 'html';
        if ($k === $text) {
            $fields['PREVIEW_TEXT'] = $lText ?: 'Текст';
        } elseif ($k === 'pic') {
            $fields['PREVIEW_PICTURE'] = $lPic ?: 'Картинка';
        } elseif (isset($props[$k])) {
            $fields['PROPERTY_' . $props[$k]['ID']] = $k === 'caption' ? 'Надпись над заголовком (мелко)' : $props[$k]['NAME'];
        } else {
            $miss[] = $k;
        }
    }
    $tabs = ['edit1' => ['TAB' => $single ? 'Блок' : 'Элемент', 'FIELDS' => $fields]];
    if (isset($seo[$code])) {
        $f = ['IPROPERTY_TEMPLATES_ELEMENT_META_TITLE' => 'Title — заголовок вкладки и в поиске', 'IPROPERTY_TEMPLATES_ELEMENT_META_DESCRIPTION' => 'Description — описание в поиске'];
        $seo[$code] and $f['IPROPERTY_TEMPLATES_ELEMENT_PAGE_TITLE'] = 'Заголовок H1 на странице';
        $tabs['edit14'] = ['TAB' => 'SEO страницы', 'FIELDS' => $f];
    }
    $formId = 'form_element_' . $ibId;
    $say("$code: " . implode(' · ', $fields) . (isset($tabs['edit14']) ? ' | SEO' : '') . ($miss ? ' | НЕТ СВОЙСТВ: ' . implode(', ', $miss) : ''));
    if ($apply) {
        array_key_exists($formId, $backup) or $backup[$formId] = CUserOptions::GetOption('form', $formId, false, 0);
        // личные настройки формы у пользователей перекрыли бы общую — сбрасываем все и ставим общую
        CUserOptions::DeleteOptionsByName('form', $formId);
        CAdminFormSettings::setTabsArray($formId, $tabs, true);
    }
}
if ($apply) {
    Option::set('main', $backupKey, json_encode($backup, JSON_UNESCAPED_UNICODE));
}
echo "done\n";
