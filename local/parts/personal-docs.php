<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CUser $USER */
// Кабинет: счета и документы по макету account-docs.html. Документов в системе нет — показываем заказы с оплатой по счёту,
// сами счета, УПД и акт сверки высылает менеджер по запросу. Отсрочки и задолженности из макета не выводим — данных нет

use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale\Internals\PaymentTable;

if (!bt_acc_guard()) {
    return;
}
Loader::includeModule('sale');
$e = fn($s) => htmlspecialcharsbx((string)$s);
$uid = (int)$USER->GetID();
$bill = array_column(\Bitrix\Sale\Internals\PaySystemActionTable::getList(['filter' => ['=ACTION_FILE' => 'bill'], 'select' => ['ID']])->fetchAll(), 'ID');
$ids = $bill ? array_unique(array_column(PaymentTable::getList(['filter' => ['@PAY_SYSTEM_ID' => $bill, '=ORDER.USER_ID' => $uid], 'select' => ['ORDER_ID']])->fetchAll(), 'ORDER_ID')) : [];
$orders = $ids ? OrderTable::getList(['filter' => ['@ID' => $ids, '=LID' => SITE_ID], 'select' => ['ID', 'ACCOUNT_NUMBER', 'DATE_INSERT', 'PRICE', 'PAYED', 'CANCELED', 'STATUS_ID'], 'order' => ['ID' => 'DESC']])->fetchAll() : [];
$orgs = bt_profiles($uid, 'UR');

bt_acc_start('docs', '<h1 class="display h1">Счета и документы</h1>', 'Счёт на оплату приходит на e‑mail, как только менеджер подтвердит заказ, и сразу доступен здесь. УПД и акт сверки присылает менеджер.');
?>
<?php if ($orgs): ?>
<div class="b2b">
  <?php foreach (array_slice($orgs, 0, 3) as $o): ?>
  <a class="card" href="/personal/"><span class="mono muted">Организация</span><b style="font-size:16px"><?= $e($o['v']['COMPANY'] ?? $o['name']) ?></b><span class="muted" style="font-size:13px">ИНН <?= $e($o['v']['INN'] ?? '') ?></span></a>
  <?php endforeach ?>
</div>
<?php endif ?>

<?php if ($orders): ?>
<div class="row between" style="margin:6px 0 14px"><h2 class="h3">Заказы по счёту</h2></div>
<div class="docs-w"><table class="docs">
  <thead><tr><th>Дата</th><th>Заказ</th><th>Сумма</th><th>Оплата</th><th></th></tr></thead>
  <tbody><?php foreach ($orders as $o): ?>
    <tr><td style="white-space:nowrap"><?= $e(FormatDate('j F Y', $o['DATE_INSERT']->getTimestamp())) ?></td>
      <td><a class="link" href="/personal/orders/<?= (int)$o['ID'] ?>/">№ <?= $e($o['ACCOUNT_NUMBER']) ?></a></td>
      <td style="white-space:nowrap;font-variant-numeric:tabular-nums"><?= bt_fmt((float)$o['PRICE']) ?></td>
      <td><?php if ($o['CANCELED'] === 'Y'): ?><span class="status st-cancel">Отменён</span><?php else: ?><span class="status <?= $o['PAYED'] === 'Y' ? 'st-paid' : 'st-new' ?>"><?= $o['PAYED'] === 'Y' ? 'Оплачен' : 'Ждёт оплаты' ?></span><?php endif ?></td>
      <td style="text-align:right;white-space:nowrap"><?php if ($o['CANCELED'] !== 'Y' && $o['STATUS_ID'] !== 'N'): ?><a href="/local/ajax/bill.php?id=<?= (int)$o['ID'] ?>"><?= bt_icon('doc') ?> Счёт PDF</a>
        <?php elseif ($o['CANCELED'] !== 'Y'): ?><span class="muted" style="font-size:13px">Счёт — после подтверждения</span><?php endif ?></td></tr>
  <?php endforeach ?></tbody>
</table></div>
<?php else: ?>
<div class="card empty">
  <p class="display h3">Заказов по счёту пока нет</p>
  <p class="muted">При оформлении выберите «Юрлицо или ИП» и оплату по счёту — менеджер пришлёт счёт, после отгрузки — УПД.</p>
  <a class="btn" href="/magazin/">Перейти в каталог</a>
</div>
<?php endif ?>
<div class="row" style="margin-top:18px;gap:10px">
  <a class="btn btn--ghost btn--sm" href="/kontakty/#form" data-lead="Запрос акта сверки">Запросить акт сверки</a>
  <a class="btn btn--ghost btn--sm" href="/kontakty/#form" data-lead="Запрос закрывающих документов">Запросить закрывающие документы</a>
</div>
<?php bt_acc_end();
