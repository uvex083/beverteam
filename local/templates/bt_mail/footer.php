<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
$host = 'https://' . ($arParams['SERVER_NAME'] ?? '');
$co = function_exists('bt_contacts') ? bt_contacts() : [];
$e = fn($s) => htmlspecialcharsbx((string)$s);
$lnk = 'color:#D7E85C;text-decoration:none;font-weight:700';
?>
</td></tr>
<tr><td class="bt-p" bgcolor="#0E0E0C" style="background-color:#0E0E0C;border-radius:0 0 18px 18px;padding:26px 36px 28px;font-family:Manrope,Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#C9C9C2">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
    <td class="bt-col" width="50%" valign="top" style="padding-right:16px">
      <?php foreach (['phone1', 'phone2'] as $k): if (!empty($co[$k])): ?>
      <a href="<?= $e($co[$k . '_href']) ?>" style="color:#FFFFFF;text-decoration:none;font-weight:700;font-size:16px;white-space:nowrap"><?= $e($co[$k]) ?></a><br>
      <?php endif; endforeach ?>
      <?php if (!empty($co['email'])): ?><a href="mailto:<?= $e($co['email']) ?>" style="<?= $lnk ?>"><?= $e($co['email']) ?></a><?php endif ?>
    </td>
    <td class="bt-col" width="50%" valign="top">
      <?= $e(trim(($co['city'] ?? '') . ', ' . ($co['street'] ?? ''), ', ')) ?><br>
      <?php if (!empty($co['hours'])): ?><?= $e($co['hours']) ?><br><?php endif ?>
      <a href="<?= $host ?>/personal/" style="<?= $lnk ?>">Личный кабинет</a> &nbsp;·&nbsp; <a href="<?= $host ?>/kontakty/" style="<?= $lnk ?>">Контакты</a>
    </td>
  </tr></table>
</td></tr>
<tr><td class="bt-p" style="padding:18px 36px 0;font-family:Manrope,Arial,Helvetica,sans-serif;font-size:11.5px;line-height:1.6;color:#6C6C64">
  Письмо отправлено автоматически с сайта <a href="<?= $host ?>/" style="color:#6C6C64"><?= $e($arParams['SERVER_NAME'] ?? '') ?></a>.
  <?php if (!empty($co['legal'])): ?><br><?= $e($co['legal']) ?><?= !empty($co['ogrnip']) ? ', ОГРНИП ' . $e($co['ogrnip']) : '' ?><?= !empty($co['inn']) ? ', ИНН ' . $e($co['inn']) : '' ?><?php endif ?>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
