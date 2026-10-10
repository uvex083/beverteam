<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CUser $USER */
// Кабинет: адреса доставки по макету account-addresses.html — профили покупателя типа FIZ; основной подставляется при оформлении заказа.
// Карта в окне адреса — только с ключом Яндекс Карт (админка: Управление структурой → «Ключ API Яндекс.Карт»), без ключа — поля

if (!bt_acc_guard()) {
    return;
}
$e = fn($s) => htmlspecialcharsbx((string)$s);
$list = bt_addresses((int)$USER->GetID());
$me = bt_user_js();
$ekb = \Bitrix\Sale\Location\LocationTable::getList(['filter' => ['=NAME.NAME' => 'Екатеринбург', '=NAME.LANGUAGE_ID' => 'ru', '=TYPE.CODE' => 'CITY'], 'select' => ['CODE']])->fetch()['CODE'] ?? '';

bt_acc_start('addr', '<h1 class="display h1">Адреса доставки</h1>');
?>
<?php if (!$list): ?>
<div class="card empty" style="margin-bottom:16px">
  <p class="display h3">Адресов пока нет</p>
  <p class="muted">Сохраните адрес офиса или дома — подставим его при оформлении заказа.</p>
</div>
<?php endif ?>
<?php foreach ($list as $a): ?>
<div class="card li" data-addr='<?= $e(json_encode(['tel' => $a['tel'] !== '' ? bt_phone_fmt($a['tel']) : ''] + $a, JSON_UNESCAPED_UNICODE)) ?>'>
  <div><?php if ($a['main']): ?><span class="badge" style="margin-bottom:8px">Основной</span><?php endif ?><b><?= $e($a['tag']) ?></b>
    <p><?= $e(implode(', ', array_filter([$a['city'], $a['street'], $a['flat']]))) ?><?= $a['entr'] !== '' ? ' · ' . $e($a['entr']) : '' ?>
      <?php if ($a['who'] !== '' || $a['tel'] !== ''): ?><br>Получатель: <?= $e(trim($a['who'] . ($a['tel'] !== '' ? ', ' . bt_phone_fmt($a['tel']) : ''), ', ')) ?><?php endif ?></p></div>
  <div class="acts"><?php if (!$a['main']): ?><button class="btn btn--ghost btn--xs" type="button" data-addr-main>Сделать основным</button><?php endif ?>
    <button class="btn btn--ghost btn--xs" type="button" data-addr-edit>Изменить</button>
    <button class="btn btn--ghost btn--xs" type="button" data-addr-del>Удалить</button></div>
</div>
<?php endforeach ?>
<button class="btn" id="addrAdd" type="button">+ Добавить адрес</button>

<div class="modal" id="addrModal" role="dialog" aria-modal="true" aria-labelledby="adrTitle" data-ekb='<?= $e(json_encode(['loc' => $ekb, 'city' => 'Екатеринбург', 'who' => $me['name'] ?? ''], JSON_UNESCAPED_UNICODE)) ?>'>
  <div class="modal__bg" data-close></div>
  <div class="modal__p">
    <button class="modal__x" type="button" data-close aria-label="Закрыть">×</button>
    <h3 class="display" id="adrTitle" style="font-size:20px;margin-bottom:4px">Новый адрес</h3>
    <p class="muted" style="margin:0 0 18px;font-size:14px">Город выберите из списка — от него зависят способы доставки.</p>
    <div class="amap">
      <div class="amap__box" id="adrMap" hidden></div>
      <form id="adrForm" novalidate>
        <input type="hidden" name="id" value="0">
        <input type="hidden" name="loc" value="">
        <div class="f2">
          <div class="field"><label>Название</label><input name="tag" placeholder="Офис, дом, склад…" maxlength="50"></div>
          <div class="field city"><label>Город *</label><input name="city" autocomplete="new-password" spellcheck="false" placeholder="Начните вводить город" role="combobox" aria-autocomplete="list" aria-expanded="false"><ul role="listbox" tabindex="-1"></ul></div>
          <div class="field" style="grid-column:1/-1"><label>Улица, дом *</label><input name="street" data-v="addr" placeholder="Начните вводить улицу" maxlength="200"></div>
          <div class="field"><label>Квартира / офис</label><input name="flat" maxlength="50"></div>
          <div class="field"><label>Подъезд, этаж, домофон</label><input name="entr" maxlength="100"></div>
          <div class="field"><label>Получатель</label><input name="who" maxlength="100" autocomplete="off"></div>
          <div class="field"><label>Телефон получателя</label><input name="tel" placeholder="+7 (___) ___-__-__"></div>
        </div>
        <label class="check" style="margin:2px 0 18px"><input type="checkbox" name="main" value="Y"> <span>Сделать основным</span></label>
        <div class="row" style="gap:10px"><button class="btn" type="submit">Сохранить адрес</button>
          <button class="btn btn--ghost" type="button" data-close>Отмена</button></div>
      </form>
    </div>
  </div>
</div>
<?php bt_acc_end();
