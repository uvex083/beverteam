<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CUser $USER */
// Кабинет: счета и документы по макету account-docs.html. Документов в системе нет — показываем заказы с оплатой по счёту,
// документы сверх счёта менеджер высылает по запросу. Отсрочки и задолженности из макета не выводим — данных нет

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
// организация — из самого заказа: на неё выписан счёт
$org = [];
if ($orders) {
    $r = \Bitrix\Sale\Internals\OrderPropsValueTable::getList(['filter' => ['@ORDER_ID' => array_column($orders, 'ID'), '@CODE' => ['COMPANY', 'INN']], 'select' => ['ORDER_ID', 'CODE', 'VALUE']]);
    while ($v = $r->fetch()) {
        $org[(int)$v['ORDER_ID']][$v['CODE']] = trim((string)$v['VALUE']);
    }
}
$orgCol = count(array_unique(array_map(fn($o) => $o['INN'] ?? '', $org))) > 1;
$one = !$orgCol && $org ? reset($org) : [];

bt_acc_start('docs', '<h1 class="display h1">Счета и документы</h1>', 'Счёт на оплату приходит на e‑mail, как только менеджер подтвердит заказ, и сразу доступен здесь.');
?>
<?php if ($orders): ?>
<div class="row between" style="margin:6px 0 14px;gap:6px 16px;flex-wrap:wrap"><h2 class="h3">Заказы по счёту</h2>
  <?php if (!empty($one['COMPANY'])): ?><span class="muted" style="font-size:14px"><?= $e($one['COMPANY']) ?><?= !empty($one['INN']) ? ', ИНН ' . $e($one['INN']) : '' ?></span><?php endif ?></div>
<div class="docs-w"><table class="docs">
  <thead><tr><th>Дата</th><th>Заказ</th><?php if ($orgCol): ?><th>Организация</th><?php endif ?><th>Сумма</th><th>Оплата</th><th></th></tr></thead>
  <tbody><?php foreach ($orders as $o): ?>
    <tr><td style="white-space:nowrap"><?= $e(FormatDate('j F Y', $o['DATE_INSERT']->getTimestamp())) ?></td>
      <td><a class="link" href="/personal/orders/<?= (int)$o['ID'] ?>/">№ <?= $e($o['ACCOUNT_NUMBER']) ?></a></td>
      <?php if ($orgCol): $g = $org[(int)$o['ID']] ?? [] ?><td><?= $e($g['COMPANY'] ?? '') ?><?php if (!empty($g['INN'])): ?><span class="muted" style="display:block;font-size:12px">ИНН <?= $e($g['INN']) ?></span><?php endif ?></td><?php endif ?>
      <td style="white-space:nowrap;font-variant-numeric:tabular-nums"><?= bt_fmt((float)$o['PRICE']) ?></td>
      <td><?php if ($o['CANCELED'] === 'Y'): ?><span class="status st-cancel">Отменён</span><?php else: ?><?php if ($o['PAYED'] === 'Y'): ?><span class="status st-done">Оплачен</span><?php elseif ($o['STATUS_ID'] === 'N'): ?><span class="muted" style="font-size:13px">Счёт готовится</span><?php else: ?><span class="status st-new">Ждёт оплаты</span><?php endif ?><?php endif ?></td>
      <td style="text-align:right;white-space:nowrap"><?php if ($o['CANCELED'] !== 'Y' && $o['STATUS_ID'] !== 'N'): ?><a href="/local/ajax/bill.php?id=<?= (int)$o['ID'] ?>" target="_blank" rel="noopener"><?= bt_icon('doc') ?> Счёт</a>
        <?php elseif ($o['CANCELED'] !== 'Y'): ?><span class="muted" style="font-size:13px">Счёт — после подтверждения</span><?php endif ?></td></tr>
  <?php endforeach ?></tbody>
</table></div>
<?php else: ?>
<div class="card empty">
  <p class="display h3">Заказов по счёту пока нет</p>
  <p class="muted">При оформлении выберите «Юрлицо или ИП» и оплату по счёту. Счёт появится здесь и придёт на e‑mail, когда менеджер подтвердит заказ.</p>
  <a class="btn" href="/catalog/">Перейти в каталог</a>
</div>
<?php endif ?>
<div class="row" style="margin-top:18px;gap:10px">
  <a class="btn btn--ghost btn--sm" href="/kontakty/#form" data-lead="Запрос акта сверки">Запросить акт сверки</a>
  <a class="btn btn--ghost btn--sm" href="/kontakty/#form" data-lead="Запрос закрывающих документов">Запросить закрывающие документы</a>
</div>
<?php bt_acc_end();
