<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @global CMain $APPLICATION */
$co = bt_contacts();
$menu = fn(string $type) => $APPLICATION->IncludeComponent('bitrix:menu', 'bt_footer', [
    'ROOT_MENU_TYPE' => $type, 'MAX_LEVEL' => 1, 'USE_EXT' => 'N',
    'MENU_CACHE_TYPE' => 'A', 'MENU_CACHE_TIME' => 3600, 'MENU_CACHE_USE_GROUPS' => 'N', 'DELAY' => 'N', 'ALLOW_MULTI_SELECT' => 'N',
], false, ['HIDE_ICONS' => 'Y']);
$msgr = '';
foreach (bt_messengers() as [$code, $name, $href]) {
    $msgr .= '<a href="' . htmlspecialcharsbx($href ?: '#') . '" title="' . $name . '" aria-label="' . $name . '" rel="nofollow noopener" target="_blank">' . bt_icon($code) . '</a>';
}
?>
<footer class="ftr" itemscope itemtype="https://schema.org/LocalBusiness"><div class="wrap">
  <meta itemprop="name" content="BEVERTEAM — чай и кофе для дома и бизнеса">
  <meta itemprop="priceRange" content="650–297000 ₽">
  <div class="ftr__g">
    <div><a class="brand" href="/" style="margin-bottom:14px" title="Чай и кофе для дома и бизнеса BEVERTEAM"><span class="brand__m">B</span><span class="brand__t">BEVERTEAM</span></a>
      <p style="margin:0;max-width:28ch">Чай, кофе и оборудование для дома и бизнеса. <?= $co['city'] ?? '' ?>, с 2010 года.</p>
      <div class="ftr__soc" style="margin-top:14px"><div class="msgr"><?= $msgr ?></div></div>
      <div class="ftr__hours"><b>Время работы</b>Офис: <?= $co['hours'] ?? '' ?><br>Выезд инженера: <?= $co['hours_service'] ?? '' ?><br>Сб–Вс — выходные</div></div>
    <div><h5>Каталог</h5><?php $menu('foot_catalog') ?></div>
    <div><h5>Услуги</h5><?php $menu('foot_services') ?></div>
    <div><h5>Покупателям</h5><?php $menu('foot_buyers') ?></div>
    <div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress"><h5>Контакты</h5><ul>
      <li><a href="<?= $co['phone1_href'] ?? '' ?>" itemprop="telephone"><?= $co['phone1'] ?? '' ?></a></li>
      <li><a href="<?= $co['phone2_href'] ?? '' ?>"><?= $co['phone2'] ?? '' ?></a></li>
      <li><a href="mailto:<?= $co['email'] ?? '' ?>" itemprop="email"><?= $co['email'] ?? '' ?></a></li>
      <li><span itemprop="postalCode"><?= $co['zip'] ?? '' ?></span>, <span itemprop="addressLocality"><?= $co['city'] ?? '' ?></span>,<br><span itemprop="streetAddress"><?= $co['street'] ?? '' ?></span></li>
      <li><meta itemprop="openingHours" content="Mo-Fr 10:00-17:00"><span class="muted"><?= $co['hours'] ?? '' ?></span></li>
    </ul></div>
  </div>
  <div class="ftr__b"><span>© 2010–<?= date('Y') ?> BEVERTEAM · <?= $co['legal'] ?? '' ?> · ОГРНИП <?= $co['ogrnip'] ?? '' ?> · ИНН <?= $co['inn'] ?? '' ?></span><span><a href="/politika-konfidencialnosti/">Политика конфиденциальности</a> · <a href="/polzovatelskoe-soglashenie/">Пользовательское соглашение</a> · <a href="/sitemap/">Карта сайта</a></span></div>
</div></footer>
</div>
</body>
</html>
