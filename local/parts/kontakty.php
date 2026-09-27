<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Контакты» по макету contacts.html: реквизиты — bt_contacts(), «Как добраться» и фото — ИБ типа «Контакты»

$e = fn($s) => htmlspecialcharsbx((string)$s);
$co = bt_contacts();
$how = bt_list('contacts_how');
$photos = bt_list('contacts_photos');
$msgr = '';
foreach (bt_messengers() as [$code, $name, $href]) {
    $msgr .= '<a href="' . $e($href ?: '#') . '" title="' . $name . '" aria-label="' . $name . '" rel="nofollow noopener" target="_blank">' . bt_icon($code) . '</a>';
}
?>
<div class="wrap contp">
  <?php bt_crumbs() ?>
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1></div>
  <div class="cl">
    <div class="card" id="rekv">
      <dl class="dl2">
        <div><dt>Телефоны</dt><dd><a href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a><br><a href="<?= $e($co['phone2_href'] ?? '') ?>"><?= $e($co['phone2'] ?? '') ?></a></dd></div>
        <div><dt>Почта</dt><dd><a href="mailto:<?= $e($co['email'] ?? '') ?>"><?= $e($co['email'] ?? '') ?></a></dd></div>
        <div><dt>Адрес склада и самовывоза</dt><dd><?= $e($co['zip'] ?? '') ?>, <?= $e($co['city'] ?? '') ?>,<br><?= $e($co['street'] ?? '') ?></dd></div>
        <div><dt>Время работы офиса</dt><dd><?= $e($co['hours'] ?? '') ?><br><span class="muted" style="font-weight:400;font-size:13px">Сб–Вс — выходные, самовывоз по договорённости с менеджером</span></dd></div>
        <?php if (!empty($co['hours_service'])): ?><div><dt>Выезд технического специалиста</dt><dd><?= $e($co['hours_service']) ?><br><span class="muted" style="font-weight:400;font-size:13px">по Екатеринбургу, по заявке</span></dd></div><?php endif ?>
        <div><dt>Мессенджеры</dt><dd><div class="msgr msgr--lg"><?= $msgr ?></div></dd></div>
      </dl>
      <hr class="hr" style="margin:22px 0">
      <?php $req = bt_requisites(); $onPage = array_filter($req, fn($r) => !in_array($r[0], ['Фактический адрес', 'Телефон', 'E-mail', 'Сайт'], true)); ?>
      <div class="req">
        <div class="req__hd"><div><span class="req__k">Реквизиты</span><b>BEVERTEAM — чай и кофе для дома и бизнеса</b><span class="muted">на рынке с 2010 года</span></div><?= bt_icon('doc') ?></div>
        <dl class="req__list"><?php foreach ($onPage as [$l, $v]): ?><div><dt><?= $e($l) ?></dt><dd><?= $e($v) ?></dd></div><?php endforeach ?></dl>
        <div class="req__btns">
          <a class="btn" href="/local/ajax/requisites.php" download="BEVERTEAM-rekvizity.pdf"><?= bt_icon('doc') ?>Скачать реквизиты (PDF)</a>
          <button class="btn btn--line" type="button" data-copy="<?= $e(implode("
", array_map(fn($r) => $r[0] . ': ' . $r[1], $req))) ?>">Скопировать</button>
        </div>
      </div>
    </div>
    <div class="map" data-ymap><div class="pin"></div><div class="cap">Яндекс Карты · <?= $e($co['street'] ?? '') ?></div></div>
  </div>

  <?php if ($how): ?>
  <section class="sec">
    <h2 class="display h2" style="margin-bottom:22px">Как добраться</h2>
    <div class="how"><?php foreach ($how as $h): ?><div><b><?= $e($h['name']) ?></b><?= $e($h['text']) ?></div><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:22px">Офис и склад</h2>
    <div class="office">
      <?php if ($photos): foreach ($photos as $p): ?>
      <figure><img src="<?= $e($p['pic']) ?>" alt="<?= $e($p['name']) ?>" loading="lazy"><figcaption><?= $e($p['text'] ?: $p['name']) ?></figcaption></figure>
      <?php endforeach; else: foreach (['Фото входной группы' => 'Вход и вывеска BEVERTEAM', 'Фото офиса' => 'Офис', 'Фото склада / зоны выдачи' => 'Склад и зона самовывоза заказов'] as $ph => $cap): ?>
      <figure><div class="ph"><?= $ph ?><br>(нужен файл от клиента)</div><figcaption><?= $cap ?></figcaption></figure>
      <?php endforeach; endif ?>
    </div>
  </section>

  <section class="sec sec--t0" id="form"><form class="card form" data-form="write" novalidate>
    <div><div class="mono muted">Заявка</div><h2 class="display h2" style="margin:14px 0 10px">Напишите нам</h2><p class="muted" style="margin:0;max-width:40ch">Менеджер перезвонит или напишет в течение 5 минут в рабочее время. Подберём кофе, машину или запишем на ремонт.</p></div>
    <div>
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="field"><label>E-mail *</label><input name="email" type="email" placeholder="mail@company.ru" maxlength="100"></div>
      <div class="field"><label>Что интересует</label><select name="topic"><option>Аренда кофемашины</option><option>Покупка кофемашины</option><option>Ремонт и обслуживание</option><option>Кофе и чай оптом</option><option>Другое</option></select></div>
      <div class="field"><label>Сообщение</label><textarea name="message" rows="3" maxlength="2000"></textarea></div>
      <?= bt_form_tail() ?>
    </div>
  </form></section>
</div>
