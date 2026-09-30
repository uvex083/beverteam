<?php
// Общие функции установочных скриптов страниц: инфоблок блока, сортировка списка в админке, стартовые элементы, иконка файлом

function bt_ib_ensure(array $f, array $props, bool $apply, callable $say, callable $fail): int
{
    $ib = CIBlock::GetList([], ['=CODE' => $f['CODE'], 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    $id = (int)($ib['ID'] ?? 0);
    if (!$id) {
        $say("создать ИБ {$f['CODE']} «{$f['NAME']}»");
        if ($apply) {
            $o = new CIBlock();
            $id = (int)$o->Add($f + ['SITE_ID' => ['s1'], 'ACTIVE' => 'Y', 'GROUP_ID' => ['2' => 'R'], 'VERSION' => 2, 'INDEX_ELEMENT' => 'N', 'WORKFLOW' => 'N'])
                or $fail("iblock {$f['CODE']}: {$o->LAST_ERROR}");
        }
    } elseif ($ib['NAME'] !== $f['NAME'] || (int)$ib['SORT'] !== (int)$f['SORT'] || $ib['IBLOCK_TYPE_ID'] !== $f['IBLOCK_TYPE_ID']) {
        $say("ИБ {$f['CODE']} → «{$f['NAME']}» в типе {$f['IBLOCK_TYPE_ID']}, сортировка {$f['SORT']}");
        $apply and (new CIBlock())->Update($id, ['NAME' => $f['NAME'], 'SORT' => $f['SORT'], 'IBLOCK_TYPE_ID' => $f['IBLOCK_TYPE_ID']]);
    }
    $have = [];
    if ($id) {
        $r = CIBlockProperty::GetList([], ['IBLOCK_ID' => $id]);
        while ($p = $r->Fetch()) {
            $have[$p['CODE']] = $p;
        }
    }
    $sort = 100;
    foreach ($props as $code => [$name, $type, $extra]) {
        $sort += 10;
        if (isset($have[$code])) {
            if ($have[$code]['NAME'] !== $name) {
                $say("  свойство $code → «{$name}»");
                $apply and (new CIBlockProperty())->Update($have[$code]['ID'], ['NAME' => $name]);
            }
            continue;
        }
        $say("  свойство $code «{$name}»");
        if ($apply) {
            [$pt, $ut] = array_pad(explode(':', $type), 2, null);
            $bp = new CIBlockProperty();
            $bp->Add(['IBLOCK_ID' => $id, 'CODE' => $code, 'NAME' => $name, 'SORT' => $sort, 'PROPERTY_TYPE' => $pt, 'USER_TYPE' => $ut, 'ACTIVE' => 'Y'] + $extra)
                or $fail("prop $code: {$bp->LAST_ERROR}");
        }
    }
    return $id;
}

// список элементов в админке — по SORT asc, как на сайте (общая настройка и у каждого пользователя)
function bt_grid_sort(string $type, int $ibId): void
{
    foreach (['tbl_iblock_list_', 'tbl_iblock_element_'] as $prefix) {
        $gridId = $prefix . md5($type . '.' . $ibId);
        $set = function (array $o) {
            $o['views']['default']['last_sort_by'] = 'SORT';
            $o['views']['default']['last_sort_order'] = 'asc';
            $o['current_view'] ??= 'default';
            return $o;
        };
        CUserOptions::SetOption('main.interface.grid', $gridId, $set(CUserOptions::GetOption('main.interface.grid', $gridId, [], 0) ?: []), true);
        $r = \Bitrix\Main\UserTable::getList(['filter' => ['=ACTIVE' => 'Y'], 'select' => ['ID']]);
        while ($u = $r->fetch()) {
            CUserOptions::SetOption('main.interface.grid', $gridId, $set(CUserOptions::GetOption('main.interface.grid', $gridId, [], $u['ID']) ?: []), false, $u['ID']);
        }
    }
}

// элемент по коду (одиночный блок) или по названию (карточка списка); существующий не трогаем
function bt_el_seed(int $ibId, string $code, array $fields, array $props, callable $say, callable $fail): int
{
    $filter = ['IBLOCK_ID' => $ibId] + ($code !== '' ? ['=CODE' => $code] : ['=NAME' => $fields['NAME']]);
    if ($el = CIBlockElement::GetList([], $filter, false, false, ['ID'])->Fetch()) {
        return (int)$el['ID'];
    }
    $say("  + {$fields['NAME']}");
    $o = new CIBlockElement();
    $id = (int)$o->Add(['IBLOCK_ID' => $ibId, 'CODE' => $code ?: false, 'ACTIVE' => 'Y'] + array_filter($fields, fn($v) => $v !== null))
        or $fail("element {$fields['NAME']}: {$o->LAST_ERROR}");
    foreach ($props as $k => $v) {
        if ($k === 'TEXT') {
            $props[$k] = ['VALUE' => ['TEXT' => $v, 'TYPE' => 'HTML']];
        } elseif ($k === 'ITEMS') {
            $props[$k] = array_map(fn($x) => ['VALUE' => $x[0], 'DESCRIPTION' => $x[1] ?? ''], $v);
        }
    }
    $props and CIBlockElement::SetPropertyValuesEx($id, $ibId, $props);
    return $id;
}

function bt_icon_file(string $name): array
{
    $tmp = CTempFile::GetFileName($name . '.svg');
    CheckDirPath($tmp);
    file_put_contents($tmp, preg_replace('/ aria-hidden="true" focusable="false"/', ' xmlns="http://www.w3.org/2000/svg"', bt_icon($name)));
    return CFile::MakeFileArray($tmp);
}
