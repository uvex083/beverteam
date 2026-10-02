<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @global CMain $APPLICATION */
$co = bt_contacts();
$menu = fn(string $type) => $APPLICATION->IncludeComponent('bitrix:menu', 'bt_footer', [
    'ROOT_MENU_TYPE' => $type, 'MAX_LEVEL' => 1, 'USE_EXT' => 'N',
    'MENU_CACHE_TYPE' => 'A', 'MENU_CACHE_TIME' => 3600, 'MENU_CACHE_USE_GROUPS' => 'N', 'DELAY' => 'N', 'ALLOW_MULTI_SELECT' => 'N',
], false, ['HIDE_ICONS' => 'Y']);
$msgr = '';
foreach (bt_messengers() as [, $name, $href, $svg]) {
    $msgr .= '<a href="' . htmlspecialcharsbx($href) . '" title="' . htmlspecialcharsbx($name) . '" aria-label="' . htmlspecialcharsbx($name) . '" rel="nofollow noopener" target="_blank">' . $svg . '</a>';
}
?>
<?php if ($APPLICATION->GetDirProperty('bt_layout') === 'info'): ?>
    </div>
  </div>
</div>
<?php endif ?>
<?= bt_cat_sprite() ?>
<?php if (str_starts_with($APPLICATION->GetCurDir(), '/personal/order/make/')): ?>
<footer class="ftr ftr--s"><div class="wrap">
  <div class="ftr__s">
    <div class="ftr__c"><a href="<?= $co['phone1_href'] ?? '' ?>"><?= $co['phone1'] ?? '' ?></a><a href="mailto:<?= $co['email'] ?? '' ?>"><?= $co['email'] ?? '' ?></a><span><?= $co['hours'] ?? '' ?></span></div>
    <div class="ftr__c"><a href="/oplata-i-dostavka/" target="_blank">Оплата и доставка</a><a href="/vozvrat-i-obmen/" target="_blank">Возврат и обмен</a><a href="/politika-konfidencialnosti/" target="_blank">Политика конфиденциальности</a></div>
  </div>
  <div class="ftr__b"><span>© 2010–<?= date('Y') ?> BEVERTEAM · <?= $co['legal'] ?? '' ?> · ИНН <?= $co['inn'] ?? '' ?></span></div>
</div></footer>
<?php else: ?>
<footer class="ftr"><div class="wrap">
  <div class="ftr__g">
    <div><a class="brand" href="/" style="margin-bottom:14px" title="Чай и кофе для дома и бизнеса BEVERTEAM"><span class="brand__m">B</span><span class="brand__t">BEVERTEAM</span></a>
      <p style="margin:0;max-width:28ch">Чай, кофе и оборудование для дома и бизнеса. <?= $co['city'] ?? '' ?>, с 2010 года.</p>
      <div class="ftr__soc" style="margin-top:14px"><div class="msgr"><?= $msgr ?></div></div>
      <div class="ftr__hours"><b>Время работы</b>Офис: <?= $co['hours'] ?? '' ?><br>Выезд инженера: <?= $co['hours_service'] ?? '' ?><br>Сб–Вс — выходные</div></div>
    <div><div class="ftr__h th th5">Каталог</div><?php $menu('foot_catalog') ?></div>
    <div><div class="ftr__h th th5">Услуги</div><?php $menu('foot_services') ?></div>
    <div><div class="ftr__h th th5">Покупателям</div><?php $menu('foot_buyers') ?></div>
    <div><div class="ftr__h th th5">Контакты</div><ul>
      <li><a href="<?= $co['phone1_href'] ?? '' ?>"><?= $co['phone1'] ?? '' ?></a></li>
      <li><a href="<?= $co['phone2_href'] ?? '' ?>"><?= $co['phone2'] ?? '' ?></a></li>
      <li><a href="mailto:<?= $co['email'] ?? '' ?>"><?= $co['email'] ?? '' ?></a></li>
      <li><span><?= $co['zip'] ?? '' ?></span>, <span><?= $co['city'] ?? '' ?></span>,<br><span><?= $co['street'] ?? '' ?></span></li>
      <li><span class="muted"><?= $co['hours'] ?? '' ?></span></li>
    </ul></div>
  </div>
  <div class="ftr__b"><span>© 2010–<?= date('Y') ?> BEVERTEAM · <?= $co['legal'] ?? '' ?> · ОГРНИП <?= $co['ogrnip'] ?? '' ?> · ИНН <?= $co['inn'] ?? '' ?></span><span><a href="/politika-konfidencialnosti/">Политика конфиденциальности</a> · <a href="/polzovatelskoe-soglashenie/">Пользовательское соглашение</a> · <a href="/sitemap/">Карта сайта</a></span></div>
</div></footer>
<?php endif ?>
<div class="modal" id="lead" role="dialog" aria-modal="true" aria-labelledby="leadTitle">
  <div class="modal__bg" data-close></div>
  <div class="modal__p">
    <button class="modal__x" type="button" data-close aria-label="Закрыть">×</button>
    <form class="lead" data-form="lead" novalidate>
      <p class="display h3 lead__t" id="leadTitle">Оставить заявку</p>
      <p class="muted lead__s">Менеджер свяжется с вами в течение 5 минут в рабочее время (<?= htmlspecialcharsbx($co['hours'] ?? '') ?>).</p>
      <input type="hidden" name="topic" value="Оставить заявку">
      <div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div>
      <div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div>
      <div class="field lead__prod" hidden><label>Товар</label><input name="product" readonly tabindex="-1"></div>
      <div class="field lead__msg"><label>Комментарий</label><textarea name="message" rows="3" maxlength="2000" placeholder="Что нужно: модель, количество, адрес"></textarea></div>
      <?= bt_form_tail() ?>
    </form>
    <div class="lead__ok" hidden>
      <div class="lead__ic" aria-hidden="true">✓</div>
      <p class="display h3">Заявка отправлена</p>
      <p class="muted">Менеджер свяжется с вами в течение 5 минут в рабочее время. Заявки, отправленные вечером и в выходные, обрабатываем в первый рабочий день.</p>
      <button class="btn btn--line btn--block" type="button" data-close>Закрыть</button>
    </div>
  </div>
</div>
</div>
</body>
</html>
