<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
/** @global CUser $USER */
// Кабинет: профиль по макету account-profile.html — личные данные и реквизиты юрлиц (профили покупателя типа UR)

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Internals\OrderTable;

$APPLICATION->AddChainItem('Профиль');
if (!bt_acc_guard()) {
    return;
}
Loader::includeModule('sale');
$e = fn($s) => htmlspecialcharsbx((string)$s);
$uid = (int)$USER->GetID();
$u = UserTable::getList(['filter' => ['=ID' => $uid], 'select' => ['NAME', 'LAST_NAME', 'EMAIL', 'PERSONAL_PHONE', 'PERSONAL_MOBILE']])->fetch();
$phone = $u['PERSONAL_PHONE'] ?: $u['PERSONAL_MOBILE'];
$last = OrderTable::getList(['filter' => ['=USER_ID' => $uid, '=LID' => SITE_ID], 'select' => ['ID', 'ACCOUNT_NUMBER', 'STATUS_ID', 'CANCELED'],
    'order' => ['ID' => 'DESC'], 'limit' => 1])->fetch();
$addr = bt_addresses($uid)[0] ?? null;
$orgs = bt_profiles($uid, 'UR');
$co = bt_contacts();

bt_acc_start('profile', '<h1 class="display h1">Личный кабинет</h1>');
?>
<div class="grid g3" style="margin-bottom:16px">
  <?php if ($last): [$st, $cls] = bt_order_status($last) ?>
  <a class="card" href="/personal/orders/<?= (int)$last['ID'] ?>/"><span class="mono muted">Последний заказ</span><b class="kpi">№ <?= $e($last['ACCOUNT_NUMBER']) ?></b><span class="status <?= $cls ?>"><?= $e($st) ?></span></a>
  <?php else: ?>
  <a class="card" href="/catalog/"><span class="mono muted">Заказы</span><b class="kpi">Пока нет</b><span class="link" style="font-size:13.5px">Перейти в каталог</span></a>
  <?php endif ?>
  <a class="card" href="/personal/addresses/"><span class="mono muted">Адрес доставки</span>
    <?php if ($addr): ?><b class="kpi"><?= $e($addr['city']) ?></b><span class="muted" style="font-size:13px"><?= $e($addr['street']) ?></span>
    <?php else: ?><b class="kpi">Не указан</b><span class="muted" style="font-size:13px">Добавьте — подставим при оформлении</span><?php endif ?></a>
  <div class="card"><span class="mono muted">Вопросы по заказам</span><b class="kpi"><a href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a></b><span class="muted" style="font-size:13px"><?= $e($co['hours'] ?? '') ?></span></div>
</div>

<form class="card" id="accProfile" novalidate>
  <h2 class="h3">Личные данные</h2>
  <div class="f2">
    <div class="field"><label>Имя *</label><input name="name" value="<?= $e($u['NAME']) ?>" maxlength="50" autocomplete="given-name"></div>
    <div class="field"><label>Фамилия</label><input name="last_name" value="<?= $e($u['LAST_NAME']) ?>" maxlength="50" autocomplete="family-name"></div>
    <div class="field"><label>Телефон</label><input name="phone" value="<?= $e($phone ? bt_phone_fmt($phone) : '') ?>" placeholder="+7 (___) ___-__-__"></div>
    <div class="field"><label>E‑mail</label><input value="<?= $e($u['EMAIL']) ?>" readonly><span class="hint">Для входа и писем о заказах</span></div>
  </div>
  <button class="btn" type="submit">Сохранить</button>
</form>

<div class="card" style="margin-top:16px">
  <h2 class="h3" style="margin-bottom:10px">Юридические лица</h2>
  <p class="muted" style="margin:0 0 16px;font-size:14px">Добавьте реквизиты — подставим их при оформлении заказа на юрлицо, счёт выставит менеджер. Компаний может быть несколько.</p>
  <?php foreach ($orgs as $o): $v = $o['v'] ?>
  <div class="card li" data-org='<?= $e(json_encode(['id' => $o['id'], 'company' => $v['COMPANY'] ?? '', 'inn' => $v['INN'] ?? '', 'kpp' => $v['KPP'] ?? '', 'company_adr' => $v['COMPANY_ADR'] ?? ''], JSON_UNESCAPED_UNICODE)) ?>'>
    <div><b><?= $e($v['COMPANY'] ?? $o['name']) ?></b>
      <p>ИНН <?= $e($v['INN'] ?? '') ?><?= !empty($v['KPP']) ? ' · КПП ' . $e($v['KPP']) : '' ?><?= !empty($v['COMPANY_ADR']) ? ' · ' . $e($v['COMPANY_ADR']) : '' ?></p></div>
    <div class="acts"><button class="btn btn--ghost btn--xs" type="button" data-org-edit>Изменить</button><button class="btn btn--ghost btn--xs" type="button" data-org-del>Удалить</button></div>
  </div>
  <?php endforeach ?>
  <form class="card orgf" id="orgForm" hidden novalidate>
    <input type="hidden" name="id" value="0">
    <div class="f2">
      <div class="field"><label>Организация *</label><input name="company" placeholder="ООО «Ромашка»" maxlength="200" autocomplete="organization"></div>
      <div class="field"><label>ИНН *</label><input name="inn" placeholder="10 или 12 цифр" maxlength="12"></div>
      <div class="field"><label>КПП</label><input name="kpp" placeholder="9 цифр, если есть" inputmode="numeric" maxlength="9"></div>
      <div class="field"><label>Юридический адрес</label><input name="company_adr" maxlength="300"></div>
    </div>
    <div class="row" style="gap:10px">
      <button class="btn btn--sm" type="submit">Сохранить реквизиты</button>
      <button class="btn btn--sm btn--ghost" type="button" data-org-cancel>Отмена</button>
    </div>
  </form>
  <button class="btn btn--line" id="orgAdd" type="button">+ Добавить юрлицо</button>
</div>
<?php bt_acc_end();
