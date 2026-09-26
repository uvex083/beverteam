<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Заявка отправлена — BEVERTEAM');
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
$APPLICATION->SetTitle('Заявка отправлена');
$co = bt_contacts();
?>
<div class="wrap fsp">
  <div class="ok">
    <div class="ic" aria-hidden="true">✓</div>
    <h1 class="display h1">Заявка отправлена</h1>
    <p>Менеджер свяжется с вами в течение 5 минут в рабочее время (<?= htmlspecialcharsbx($co['hours'] ?? '') ?>). Заявки, отправленные вечером и в выходные, обрабатываем в первый рабочий день.</p>
    <div class="row" style="justify-content:center;margin-top:26px;gap:12px"><a class="btn" href="/">На главную</a><a class="btn btn--line" href="<?= htmlspecialcharsbx($co['phone1_href'] ?? '') ?>">Позвонить сейчас</a></div>
    <div class="card meanwhile">
      <b>Пока ждёте</b>
      <ul>
        <li><a class="link" href="/arenda-kofemashin/">Сравните модели Jetinno по нагрузке</a></li>
        <li><a class="link" href="/magazin/kofe/">Посмотрите кофе BOTANICA под вашу машину</a></li>
        <li><a class="link" href="/oplata-i-dostavka/">Условия оплаты и доставки</a></li>
      </ul>
    </div>
  </div>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
