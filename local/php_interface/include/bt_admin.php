<?php
// Админка: раздел «Страницы сайта» в левом меню — блоки каждой страницы (инфоблоки её типа) в порядке показа на сайте

function bt_admin_menu(&$global, &$modules): void
{
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return;
    }
    // тип инфоблоков → пункт меню; порядок — как в меню сайта
    $pages = ['main' => 'Главная', 'about' => 'О компании', 'contacts' => 'Контакты', 'services' => 'Аренда кофемашин', 'podpiska' => 'Кофе по подписке',
        'servis' => 'Сервис', 'site' => 'Сайт: общие блоки'];
    $items = [];
    foreach ($pages as $type => $title) {
        $sub = [];
        $r = \CIBlock::GetList(['SORT' => 'ASC'], ['TYPE' => $type, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N']);
        while ($ib = $r->Fetch()) {
            $sub[] = ['text' => $ib['NAME'], 'url' => 'iblock_list_admin.php?IBLOCK_ID=' . $ib['ID'] . '&type=' . $type . '&lang=' . LANGUAGE_ID,
                'more_url' => ['iblock_element_edit.php?IBLOCK_ID=' . $ib['ID'] . '&type=' . $type], 'title' => $ib['NAME']];
        }
        if ($sub) {
            $items[] = ['text' => $title, 'title' => $title, 'items_id' => 'bt_pages_' . $type, 'icon' => 'iblock_menu_icon_types', 'items' => $sub];
        }
    }
    if (!$items) {
        return;
    }
    $global['global_menu_bt'] = ['menu_id' => 'content', 'text' => 'Страницы сайта', 'title' => 'Тексты, картинки и блоки страниц сайта',
        'sort' => 150, 'items_id' => 'global_menu_bt', 'help_section' => 'bt', 'items' => []];
    foreach ($items as $i => $it) {
        $modules[] = $it + ['parent_menu' => 'global_menu_bt', 'section' => 'bt_pages', 'sort' => 100 + $i * 10];
    }
}
