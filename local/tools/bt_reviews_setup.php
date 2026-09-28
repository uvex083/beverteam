<?php
// Отзывы о компании: дата, компания, логотип и благодарственные письма (картинки или PDF) + одна тестовая карточка со всеми полями.
// Тестовый отзыв помечен XML_ID «bt-demo» — перед запуском удалить: ... bt_reviews_setup.php delete
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_reviews_setup.php [show|apply|delete]. Повторный запуск ничего не дублирует.

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
$say = fn(string $s) => print(($mode === 'show' ? '[show] ' : '') . $s . "\n");
$ibId = bt_iblock('reviews');

if ($mode === 'delete') {
    $r = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=XML_ID' => 'bt-demo'], false, false, ['ID', 'NAME']);
    while ($x = $r->Fetch()) {
        CIBlockElement::Delete($x['ID']);
        $say("удалён {$x['ID']} {$x['NAME']}");
    }
    CIBlock::clearIblockTagCache($ibId);
    die("done\n");
}

$props = [
    'DATE' => ['NAME' => 'Дата отзыва', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'Date', 'SORT' => 5],
    'COMPANY' => ['NAME' => 'Компания (для отзыва от организации)', 'PROPERTY_TYPE' => 'S', 'SORT' => 6],
    'LOGO' => ['NAME' => 'Логотип компании', 'PROPERTY_TYPE' => 'F', 'FILE_TYPE' => 'jpg, jpeg, png, webp, svg', 'SORT' => 7],
    'LETTER' => ['NAME' => 'Благодарственное письмо (картинка или PDF)', 'PROPERTY_TYPE' => 'F', 'FILE_TYPE' => 'jpg, jpeg, png, webp, pdf',
        'MULTIPLE' => 'Y', 'WITH_DESCRIPTION' => 'Y', 'SORT' => 8],
];
$ibp = new CIBlockProperty();
foreach ($props as $code => $f) {
    if (CIBlockProperty::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => $code])->Fetch()) {
        continue;
    }
    $say("свойство {$code}");
    $apply and ($ibp->Add($f + ['IBLOCK_ID' => $ibId, 'CODE' => $code, 'ACTIVE' => 'Y']) or die('ошибка свойства: ' . $ibp->LAST_ERROR . "\n"));
}

// тестовая карточка: логотип и письмо рисуем GD, чтобы не зависеть от файлов клиента
$name = 'Анна Смирнова';
if (!CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=XML_ID' => 'bt-demo'], [])) {
    $say("тестовый отзыв «{$name}»");
    if ($apply) {
        $tmp = sys_get_temp_dir();
        $ttf = is_file('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf') ? '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf' : '';
        $txt = function ($im, float $size, int $x, int $y, int $col, string $s) use ($ttf) {
            $ttf ? imagettftext($im, $size, 0, $x, $y, $col, $ttf, $s) : imagestring($im, 5, $x, $y - 14, $s, $col);
        };

        $logo = imagecreatetruecolor(240, 240);
        imagefill($logo, 0, 0, imagecolorallocate($logo, 215, 232, 92));
        imagefilledellipse($logo, 120, 120, 150, 150, imagecolorallocate($logo, 14, 14, 12));
        $txt($logo, 44, 72, 142, imagecolorallocate($logo, 215, 232, 92), 'КТ');
        imagepng($logo, $tmp . '/bt-demo-logo.png');

        $l = imagecreatetruecolor(1240, 1754);
        $white = imagecolorallocate($l, 255, 255, 255);
        $ink = imagecolorallocate($l, 14, 14, 12);
        $gray = imagecolorallocate($l, 150, 150, 142);
        imagefill($l, 0, 0, $white);
        imagecopyresampled($l, $logo, 110, 110, 0, 0, 160, 160, 240, 240);
        $txt($l, 30, 300, 180, $ink, 'ООО «Кофейня Тест»');
        $txt($l, 18, 300, 225, $gray, 'Екатеринбург, ул. Примерная, 1');
        imagefilledrectangle($l, 110, 320, 1130, 323, $ink);
        $txt($l, 44, 330, 480, $ink, 'БЛАГОДАРСТВЕННОЕ ПИСЬМО');
        foreach (range(0, 13) as $i) {
            imagefilledrectangle($l, 110, 600 + $i * 62, $i % 4 === 3 ? 760 : 1130, 614 + $i * 62, imagecolorallocate($l, 225, 225, 218));
        }
        $txt($l, 20, 110, 1560, $ink, 'Директор  ___________  А. Смирнова');
        imagepng($l, $tmp . '/bt-demo-letter.png');

        $el = new CIBlockElement();
        $id = $el->Add(['IBLOCK_ID' => $ibId, 'NAME' => $name, 'XML_ID' => 'bt-demo', 'ACTIVE' => 'Y', 'SORT' => 1, 'PREVIEW_TEXT_TYPE' => 'text',
            'PREVIEW_TEXT' => "Работаем с BEVERTEAM больше года: взяли в аренду две кофемашины Jetinno для зала и офиса, зерно BOTANICA привозят каждую неделю. "
                . "За это время ни одного срыва поставки — если нужно срочно, привозят в тот же день.\n"
                . "Отдельное спасибо сервисной службе: когда у машины в зале забился капучинатор, инженер приехал через два часа и заодно показал бариста, как его чистить, чтобы такого не повторялось. "
                . "Помогли подобрать помол под наше зерно, провели дегустацию для персонала и настроили рецепты под наше меню.\n"
                . "Гости стали чаще брать вторую чашку, а выручка кофейного направления за полгода выросла почти на треть. Рекомендуем как надёжного партнёра!",
            'PROPERTY_VALUES' => [
                'RATING' => 5,
                'DATE' => ConvertTimeStamp(strtotime('2026-09-12'), 'SHORT'),
                'COMPANY' => 'ООО «Кофейня Тест»',
                'LOGO' => CFile::MakeFileArray($tmp . '/bt-demo-logo.png'),
                'LETTER' => ['n0' => ['VALUE' => CFile::MakeFileArray($tmp . '/bt-demo-letter.png'), 'DESCRIPTION' => 'Благодарственное письмо']],
            ]]);
        echo $id ? "  ID {$id}\n" : '  ошибка: ' . $el->LAST_ERROR . "\n";
    }
}
$apply and CIBlock::clearIblockTagCache($ibId);
echo "done\n";
