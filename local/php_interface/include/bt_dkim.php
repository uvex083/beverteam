<?php
// Подпись DKIM исходящих писем (хостинг сам не подписывает). Ключ лежит вне сайта: Option bt/dkim_key — путь к файлу,
// пустая опция или нет файла — письмо уходит без подписи. Открытый ключ — TXT <селектор>._domainkey.<домен>.

use Bitrix\Main\Config\Option;

function custom_mail($to, $subject, $message, $additional_headers = '', $additional_parameters = '', $context = null)
{
    $sign = bt_dkim_header($to, $subject, $message, (string)$additional_headers);
    if ($sign !== '') {
        $additional_headers = $sign . "\r\n" . $additional_headers;
    }
    return $additional_parameters != ''
        ? @mail($to, $subject, $message, $additional_headers, $additional_parameters)
        : @mail($to, $subject, $message, $additional_headers);
}

function bt_dkim_header(string $to, string $subject, string $body, string $headers): string
{
    $file = Option::get('bt', 'dkim_key', '');
    $selector = Option::get('bt', 'dkim_selector', 'bt');
    if ($file === '' || !is_readable($file) || !($key = openssl_pkey_get_private(file_get_contents($file)))) {
        return '';
    }

    // заголовки в том виде, в каком их соберёт mail(): To и Subject он добавит сам
    $list = ['to' => 'To: ' . $to, 'subject' => 'Subject: ' . $subject];
    foreach (preg_split("/\r?\n(?![ \t])/", trim($headers)) as $line) {
        if (strpos($line, ':') !== false) {
            $list[strtolower(trim(strstr($line, ':', true)))] = $line;
        }
    }
    if (!preg_match('/@([a-z0-9.-]+)>?\s*$/i', $list['from'] ?? '', $m)) {
        return '';
    }
    $domain = strtolower($m[1]);

    $names = array_values(array_intersect(['from', 'to', 'subject', 'date', 'reply-to', 'mime-version', 'content-type', 'list-unsubscribe', 'list-unsubscribe-post'], array_keys($list)));
    $canon = '';
    foreach ($names as $n) {
        $canon .= bt_dkim_relaxed($list[$n]) . "\r\n";
    }

    $lines = explode("\n", str_replace("\r\n", "\n", $body));
    foreach ($lines as &$l) {
        $l = rtrim(preg_replace('/[ \t]+/', ' ', $l), " \t");
    }
    unset($l);
    $canonBody = rtrim(implode("\r\n", $lines), "\r\n");
    $canonBody = $canonBody === '' ? '' : $canonBody . "\r\n";

    $dkim = 'DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed; d=' . $domain . '; s=' . $selector . '; t=' . time()
        . '; h=' . implode(':', $names) . '; bh=' . base64_encode(hash('sha256', $canonBody, true)) . '; b=';
    if (!openssl_sign($canon . bt_dkim_relaxed($dkim), $sig, $key, OPENSSL_ALGO_SHA256)) {
        return '';
    }
    return $dkim . rtrim(chunk_split(base64_encode($sig), 76, "\r\n\t"), "\r\n\t");
}

function bt_dkim_relaxed(string $header): string
{
    [$name, $value] = explode(':', $header, 2);
    $value = preg_replace('/[ \t]+/', ' ', preg_replace("/\r?\n(?=[ \t])/", '', $value));
    return strtolower(trim($name)) . ':' . trim($value);
}
