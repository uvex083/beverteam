<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
$host = 'https://' . ($arParams['SERVER_NAME'] ?? '');
?><!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title><?= htmlspecialcharsbx($arParams['SITE_NAME'] ?? 'BEVERTEAM') ?></title>
<style>
:root{color-scheme:light only;supported-color-schemes:light only}
body{margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
table{border-collapse:collapse}
img{border:0;outline:none;text-decoration:none}
a{color:#0E0E0C}
@media (max-width:620px){
  .bt-w{width:100% !important}
  .bt-p{padding-left:20px !important;padding-right:20px !important}
  .bt-h1{font-size:22px !important}
  .bt-col{display:block !important;width:100% !important;padding:0 0 14px 0 !important}
}
</style>
</head>
<body style="margin:0;padding:0;background-color:#EFEFE9">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#EFEFE9" style="background-color:#EFEFE9">
<tr><td align="center" style="padding:24px 12px">
<table role="presentation" class="bt-w" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px">
<tr><td class="bt-p" bgcolor="#0E0E0C" style="background-color:#0E0E0C;border-radius:18px 18px 0 0;padding:22px 36px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
    <td align="left" valign="middle">
      <a href="<?= $host ?>/" style="text-decoration:none"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
        <td><img src="<?= $host ?>/local/templates/beverteam/brand/logo-mail-white.png" width="162" height="34" alt="Бэвертим" style="display:block;border:0;width:162px;height:34px"></td>
      </tr></table></a>
    </td>
    <td align="right" valign="middle" style="font-family:Manrope,Arial,Helvetica,sans-serif;font-size:13px;color:#C9C9C2;white-space:nowrap"><a href="<?= $host ?>/catalog/" style="color:#D7E85C;text-decoration:none;font-weight:700">Каталог</a></td>
  </tr></table>
</td></tr>
<tr><td class="bt-p" bgcolor="#FFFFFF" style="background-color:#FFFFFF;padding:36px 36px 32px;font-family:Manrope,Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0E0E0C">
