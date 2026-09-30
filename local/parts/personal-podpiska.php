<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CUser $USER */
// Кабинет: поставки кофе в офис. Поставок как сущности нет — показываем заявки покупателя «Кофе в офис» (и прежние «Подписка на кофе») из ИБ «Заявки»
// (по привязке к покупателю, e-mail или телефону); график, состав и пауза из макета — у менеджера

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

if (!bt_acc_guard()) {
    return;
}
Loader::includeModule('iblock');
$e = fn($s) => htmlspecialcharsbx((string)$s);
$uid = (int)$USER->GetID();
$u = UserTable::getList(['filter' => ['=ID' => $uid], 'select' => ['EMAIL', 'PERSONAL_PHONE', 'PERSONAL_MOBILE']])->fetch();
$d = bt_phone_digits((string)($u['PERSONAL_PHONE'] ?: $u['PERSONAL_MOBILE']));
$who = ['LOGIC' => 'OR', ['=PROPERTY_USER_ID' => $uid]];
$u['EMAIL'] and $who[] = ['=PROPERTY_EMAIL' => $u['EMAIL']];
// телефон в заявках записан как +7 XXX XXX-XX-XX (local/ajax/form.php)
$d and $who[] = ['=PROPERTY_PHONE' => '+7 ' . substr($d, 1, 3) . ' ' . substr($d, 4, 3) . '-' . substr($d, 7, 2) . '-' . substr($d, 9, 2)];
$reqs = [];
$r = CIBlockElement::GetList(['ID' => 'DESC'], ['IBLOCK_ID' => bt_iblock('form_requests'), '=PROPERTY_TOPIC' => ['Кофе в офис', 'Подписка на кофе'], $who], false, ['nTopCount' => 20],
    ['ID', 'DATE_CREATE', 'PROPERTY_MESSAGE']);
while ($f = $r->Fetch()) {
    $reqs[] = $f;
}
$co = bt_contacts();

bt_acc_start('sub', '<h1 class="display h1">Поставки кофе</h1>');
if ($reqs): ?>
<div class="reorder" style="margin-bottom:18px">
  <div><span class="mono" style="color:var(--lime)">Заявка у менеджера</span>
    <h2 style="margin:10px 0 8px">Поставки согласует менеджер</h2>
    <p>Он свяжется и согласует сорт, объём и график доставки. Изменить состав или поставить паузу — тоже через менеджера: <?= $e($co['phone1'] ?? '') ?>.</p></div>
  <div style="display:grid;gap:10px"><a class="btn" href="/kofe-v-ofis/">Новая заявка</a>
    <a class="btn btn--line" style="color:#fff;border-color:#fff" href="<?= $e($co['phone1_href'] ?? '') ?>">Позвонить</a></div>
</div>
<div class="card"><h2 class="h3">Ваши заявки</h2>
  <?php foreach ($reqs as $q): ?>
  <div class="card li"><div><b>Заявка от <?= $e(FormatDate('j F Y', MakeTimeStamp($q['DATE_CREATE']))) ?></b><p><?= nl2br($e($q['PROPERTY_MESSAGE_VALUE'] ?: 'Параметры обсудите с менеджером')) ?></p></div></div>
  <?php endforeach ?>
</div>
<?php else: ?>
<div class="card empty">
  <p class="display h3">Поставок пока нет</p>
  <p class="muted">Зерно для офиса по графику: рассчитайте объём и оставьте заявку — менеджер согласует сорт, доставку и документы.</p>
  <a class="btn" href="/kofe-v-ofis/">Рассчитать поставки</a>
</div>
<?php endif;
bt_acc_end();
