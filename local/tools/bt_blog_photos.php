<?php
// Фото к статьям журнала: обложка (анонс и детальная картинка) и фото в текст после первого абзаца нужного раздела.
// Запуск: ~/beverteam.na4u.ru/bin/php local/tools/bt_blog_photos.php [show|apply] <папка>: в ней _shots.json и img/<код>-cover.jpg, img/<код>-1.jpg …
// Статью с обложкой не трогаем; фото в текст не вставляем, если в тексте уже есть картинки.

if (PHP_SAPI !== 'cli') {
    die('cli only');
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$apply = ($argv[1] ?? 'show') === 'apply';
$dir = rtrim((string)($argv[2] ?? ''), '/') . '/';
$say = fn(string $s) => print(($apply ? '' : '[show] ') . $s . "\n");
$ibId = bt_iblock('journal') or die("нет ИБ journal\n");
$shots = json_decode((string)@file_get_contents($dir . '_shots.json'), true) or die("нет {$dir}_shots.json\n");
$e = fn($s) => htmlspecialcharsbx((string)$s);

$el = new CIBlockElement();
foreach ($shots as $code => $s) {
    $row = CIBlockElement::GetList([], ['IBLOCK_ID' => $ibId, '=CODE' => $code], false, false, ['ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_TEXT'])->Fetch();
    if (!$row) {
        echo "нет статьи $code\n";
        continue;
    }
    $upd = [];
    if (!$row['PREVIEW_PICTURE'] && is_file($f = $dir . "img/{$code}-cover.jpg")) {
        $say("обложка: {$row['NAME']}");
        $pic = CFile::MakeFileArray($f) + ['description' => $s['cover']['alt'] ?? ''];
        $upd['PREVIEW_PICTURE'] = $pic;
        $upd['DETAIL_PICTURE'] = $pic;
    }
    $text = (string)$row['DETAIL_TEXT'];
    if (!str_contains($text, '<img')) {
        foreach ($s['inline'] as $i => $ph) {
            $f = $dir . "img/{$code}-" . ($i + 1) . '.jpg';
            $h2 = '<h2>' . $ph['after_h2'] . '</h2>';
            $pos = mb_strpos($text, $h2);
            if (!is_file($f) || $pos === false) {
                echo "  нет места или файла для фото " . ($i + 1) . ": $code\n";
                continue;
            }
            $end = mb_strpos($text, '</p>', $pos);
            if ($end === false) {
                continue;
            }
            $say("  фото в текст после «{$ph['after_h2']}»");
            $src = '';
            if ($apply) {
                $fid = (int)CFile::SaveFile(CFile::MakeFileArray($f) + ['MODULE_ID' => 'iblock', 'description' => $ph['alt']], 'iblock');
                $src = (string)CFile::GetPath($fid);
            }
            $fig = "\n<figure><img src=\"" . $e($src) . '" alt="' . $e($ph['alt']) . '" width="1600" height="900" loading="lazy"><figcaption>' . $e($ph['caption']) . '</figcaption></figure>';
            $text = mb_substr($text, 0, $end + 4) . $fig . mb_substr($text, $end + 4);
        }
        $text !== $row['DETAIL_TEXT'] and $upd += ['DETAIL_TEXT' => $text, 'DETAIL_TEXT_TYPE' => 'html'];
    }
    if ($upd && $apply) {
        $el->Update($row['ID'], $upd) or print("  ошибка: {$el->LAST_ERROR}\n");
    }
}
CIBlock::clearIblockTagCache($ibId);
echo "done\n";
