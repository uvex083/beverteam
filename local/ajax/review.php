<?php
// Отзыв о товаре (POST product) или о компании (без product): rating, name, email, machine | company, text, agree, photos[] (до 5 файлов до 10 МБ; JPG/PNG, у компании ещё PDF).
// Сохраняется выключенным (ACTIVE=N) в ИБ «Отзывы» — публикует менеджер после проверки; менеджеру — письмо BT_FORM_REQUEST.
// Ответ: {ok: true} или {ok: false, errors: {поле: текст}}
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;

header('Content-Type: application/json; charset=utf-8');
$req = Context::getCurrent()->getRequest();
$out = function (int $code, array $data) {
    http_response_code($code);
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
};
if (!$req->isPost()) {
    $out(405, ['ok' => false, 'error' => 'method']);
}
if (!check_bitrix_sessid()) {
    $out(403, ['ok' => false, 'error' => 'sessid']);
}
Loader::includeModule('iblock');
// ловушка для ботов: поле скрыто от людей — делаем вид, что всё хорошо
if ((string)$req->getPost('website') !== '') {
    $out(200, ['ok' => true]);
}

$v = fn(string $k, int $max) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$req->getPost($k))), 0, $max);
$d = ['name' => $v('name', 100), 'email' => $v('email', 100), 'machine' => $v('machine', 150), 'company' => $v('company', 150),
    'text' => mb_substr(trim((string)$req->getPost('text')), 0, 3000), 'rating' => (int)$req->getPost('rating')];
$catId = bt_iblock('catalog');
$pid = (int)$req->getPost('product');
$product = $pid ? CIBlockElement::GetList([], ['IBLOCK_ID' => $catId, 'ID' => $pid, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME', 'DETAIL_PAGE_URL'])->GetNext() : null;
if ($pid && !$product) {
    $out(400, ['ok' => false, 'error' => 'product']);
}

$errors = [];
if ($d['rating'] < 1 || $d['rating'] > 5) {
    $errors['rating'] = 'Поставьте оценку';
}
if (mb_strlen($d['name']) < 2) {
    $errors['name'] = 'Как вас подписать? Минимум 2 символа';
}
if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Проверьте адрес: нужен формат mail@company.ru';
}
if (mb_strlen($d['text']) < 10) {
    $errors['text'] = 'Расскажите чуть подробнее — хотя бы пару слов';
}
if ($req->getPost('agree') !== 'Y') {
    $errors['agree'] = 'Нужно согласие с условиями';
}
// файлы: настоящие JPG/PNG (к отзыву о компании — ещё PDF), не больше 5 и не больше 10 МБ каждый
$photos = [];
$files = $_FILES['photos'] ?? null;
if ($files && is_array($files['name'])) {
    foreach ($files['name'] as $i => $fn) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $ok = $files['error'][$i] === UPLOAD_ERR_OK;
        $type = $ok ? (@getimagesize($files['tmp_name'][$i])['mime'] ?? '') : '';
        if ($ok && !$product && $type === '' && file_get_contents($files['tmp_name'][$i], false, null, 0, 5) === '%PDF-') {
            $type = 'application/pdf';
        }
        if (!in_array($type, $product ? ['image/jpeg', 'image/png'] : ['image/jpeg', 'image/png', 'application/pdf'], true) || $files['size'][$i] > 10 * 1024 * 1024) {
            $errors['photos'] = $product ? 'Подойдут только JPG или PNG до 10 МБ' : 'Подойдут только JPG, PNG или PDF до 10 МБ';
            break;
        }
        $photos[] = ['name' => $fn, 'type' => $type, 'tmp_name' => $files['tmp_name'][$i], 'size' => $files['size'][$i], 'MODULE_ID' => 'iblock'];
    }
    if (count($photos) > 5) {
        $errors['photos'] = 'Можно приложить не больше 5 фото';
    }
}
if ($errors) {
    $out(200, ['ok' => false, 'errors' => $errors]);
}

// частота: не чаще раза в 30 секунд
$ibId = bt_iblock('reviews');
if (time() - (int)($_SESSION['BT_REVIEW_LAST'] ?? 0) < 30) {
    $out(429, ['ok' => false, 'message' => 'Отзыв уже отправлен. Повторить можно через полминуты.']);
}

// покупка подтверждена, если у вошедшего покупателя есть заказ с этим товаром
$verified = false;
if ($product && $USER->IsAuthorized() && Loader::includeModule('sale')) {
    $verified = (bool)\Bitrix\Sale\Internals\BasketTable::getList(['select' => ['ID'], 'limit' => 1,
        'filter' => ['=PRODUCT_ID' => $product['ID'], '=ORDER.USER_ID' => (int)$USER->GetID(), '!ORDER_ID' => false]])->fetch();
}
$yes = $verified ? (int)(CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $ibId, 'CODE' => 'VERIFIED', 'XML_ID' => 'Y'])->Fetch()['ID'] ?? 0) : 0;

$el = new CIBlockElement();
$id = (int)$el->Add([
    'IBLOCK_ID' => $ibId, 'ACTIVE' => 'N', 'NAME' => $d['name'], 'PREVIEW_TEXT' => $d['text'], 'PREVIEW_TEXT_TYPE' => 'text', 'SORT' => 500,
    'PROPERTY_VALUES' => $product
        ? ['RATING' => $d['rating'], 'PRODUCT' => $product['ID'], 'MACHINE' => $d['machine'], 'EMAIL' => $d['email'],
            'PHOTOS' => array_map(fn($f) => ['VALUE' => $f], $photos), 'VERIFIED' => $yes ?: false]
        // отзыв о компании: файлы — в «Благодарственное письмо», подпись видна под миниатюрой
        : ['RATING' => $d['rating'], 'COMPANY' => $d['company'], 'EMAIL' => $d['email'],
            'LETTER' => array_map(fn($f) => ['VALUE' => $f, 'DESCRIPTION' => $f['type'] === 'application/pdf' ? 'Благодарственное письмо' : 'Фото'], $photos)],
]);
if (!$id) {
    $out(500, ['ok' => false, 'error' => 'save']);
}
$_SESSION['BT_REVIEW_LAST'] = time();

$host = (\CMain::IsHTTPS() ? 'https://' : 'http://') . $req->getHttpHost();
CEvent::Send('BT_FORM_REQUEST', SITE_ID, [
    'EMAIL_TO' => Option::get('sale', 'order_email') ?: Option::get('main', 'email_from'),
    'TOPIC' => ($product ? 'Отзыв о товаре' : 'Отзыв о компании') . ' — ждёт проверки', 'CLIENT_NAME' => $d['name'], 'PHONE' => '—', 'EMAIL' => $d['email'] ?: '—',
    'MESSAGE' => ($product ? 'Товар: ' . $product['~NAME'] . "\n" : ($d['company'] !== '' ? 'Компания: ' . $d['company'] . "\n" : '')) . 'Оценка: ' . $d['rating'] . ' из 5' . ($d['machine'] !== '' ? "\nМашина: " . $d['machine'] : '')
        . ($photos ? "\nФайлов: " . count($photos) : '') . ($verified ? "\nПокупка подтверждена" : '') . "\n\n" . $d['text'],
    'PAGE' => $host . ($product ? $product['~DETAIL_PAGE_URL'] : '/otzyvy-o-nas/'),
    'ADMIN_URL' => $host . '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $ibId . '&type=site&ID=' . $id . '&lang=ru',
]);

$out(200, ['ok' => true]);
