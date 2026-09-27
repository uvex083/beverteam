<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/urlrewrite.php';
CHTTP::SetStatus('404 Not Found');
@define('ERROR_404', 'Y');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetPageProperty('title', 'Страница не найдена — BEVERTEAM');
$APPLICATION->SetPageProperty('robots', 'noindex, follow');
$APPLICATION->SetTitle('Страница не найдена');
?>
<div class="wrap nfp">
  <div class="nf">
    <div>
      <div class="code" aria-hidden="true">4<svg class="code__bean" viewBox="0 0 80 110" focusable="false"><ellipse class="code__shd" cx="40" cy="104" rx="22" ry="4"/><g class="code__b"><ellipse cx="40" cy="66" rx="26" ry="36"/><path d="M40 32c-12 14 12 28 0 68"/></g></svg>4</div>
      <h1 class="display h2" style="margin-top:14px">Такой страницы нет</h1>
      <p>Возможно, товар закончился, раздел переехал или в адресе опечатка. Кофе от этого не хуже.</p>
      <form class="search nf__s" data-nf-search role="search"><input name="q" placeholder="Поиск по каталогу" aria-label="Поиск по каталогу"><button type="submit" aria-label="Найти"><?= bt_icon('search') ?></button></form>
      <a class="btn" href="/">На главную</a>
    </div>
    <div class="links">
      <a href="/magazin/">Каталог чая и кофе <span>→</span></a>
      <a href="/arenda-kofemashin/">Аренда кофемашин <span>→</span></a>
      <a href="/servis/">Ремонт и обслуживание <span>→</span></a>
      <a href="/oplata-i-dostavka/">Оплата и доставка <span>→</span></a>
      <a href="/kontakty/">Контакты <span>→</span></a>
    </div>
  </div>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
