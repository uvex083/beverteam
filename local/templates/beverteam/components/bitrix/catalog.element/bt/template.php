<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @global CMain $APPLICATION */
use Bitrix\Main\Web\Json;

$m = $arResult['BT'];
$e = fn($s) => htmlspecialcharsbx((string)$s);
$name = $arResult['~NAME'];
$h1 = $arResult['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] ?: $name; // H1 из SEO-настроек товара, если задан
$photos = $arResult['BT_PHOTOS'];
$bulk = $m['bulk'] ?? null;
$unit = 'шт';
$first = $bulk ? $bulk[0]['p'] : ($m['p'] ?? 0);
$section = $arResult['BT_SECTION'];
$reviews = $arResult['BT_REVIEWS'];
$revN = count($reviews);
$co = bt_contacts();
$back = $section['SECTION_PAGE_URL'] ?? '/catalog/';
?>
  <div class="pline"></div>
  <div class="prod" itemscope itemtype="https://schema.org/Product">
    <meta itemprop="name" content="<?= $e($name) ?>">
    <meta itemprop="description" content="<?= $e(TruncateText(trim(preg_replace('/\s+/u', ' ', strip_tags(($arResult['~PREVIEW_TEXT'] ?? '') ?: ($arResult['~DETAIL_TEXT'] ?? '')))) ?: $name, 300)) ?>">
    <meta itemprop="sku" content="<?= $e($arResult['CODE']) ?>"><?php if ($arResult['BT_BRAND']): ?><div itemprop="brand" itemscope itemtype="https://schema.org/Brand"><meta itemprop="name" content="<?= $arResult['BT_BRAND'] ?>"></div><?php endif ?>
    <div class="gal">
      <div class="gal__side">
        <button class="gal__ar" id="thUp" type="button" aria-label="Предыдущие фото"<?= count($photos) > 1 ? '' : ' style="visibility:hidden"' ?>><svg viewBox="0 0 24 24"><path d="m5 15 7-7 7 7"/></svg></button>
        <div class="swiper gal__thumbs" id="thumbs"><div class="swiper-wrapper">
          <?php foreach ($photos as $i => $ph): ?><div class="swiper-slide" role="button" tabindex="0" aria-label="Фото <?= $i + 1 ?> из <?= count($photos) ?>"><span><img src="<?= $ph['th'] ?>" alt="" loading="lazy" width="58" height="58"></span></div><?php endforeach ?>
        </div></div>
        <button class="gal__ar" id="thDn" type="button" aria-label="Следующие фото"<?= count($photos) > 1 ? '' : ' style="visibility:hidden"' ?>><svg viewBox="0 0 24 24"><path d="m5 9 7 7 7-7"/></svg></button>
      </div>
      <div class="gal__main">
        <div class="gal__badges"><?php foreach ($m['badges'] ?? [] as $b): ?><span class="badge"><?= $e($b) ?></span><?php endforeach ?><?php if ($arResult['BT_Q']): ?><span class="badge badge--dark">Q <?= $e($arResult['BT_Q']) ?></span><?php endif ?></div>
        <div class="swiper gal__big" id="galBig"><div class="swiper-wrapper">
          <?php foreach ($photos as $i => $ph): ?><div class="swiper-slide"><img src="<?= $ph['big'] ?>" data-full="<?= $ph['full'] ?>" alt="<?= $e($name) ?> — фото <?= $i + 1 ?>"<?= $i ? ' loading="lazy" decoding="async"' : ' itemprop="image" fetchpriority="high"' ?>></div><?php endforeach ?>
          <?php if (!$photos): ?><div class="swiper-slide"><div class="ph">Фото товара<br>(нужен файл от клиента)</div></div><?php endif ?>
        </div></div>
        <span class="gal__n" id="galN"><?= $photos ? '1 / ' . count($photos) : '' ?></span>
      </div>
    </div>
    <div>
      <?php if ($section): ?><div class="mono muted" style="margin-bottom:12px"><?= $e($section['~NAME']) ?></div><?php endif ?>
      <h1 class="display h1"><?= $e($h1) ?></h1>
      <div class="pmeta">
        <a href="#reviews" id="rTop" class="row" style="gap:8px;text-decoration:none"></a>
        <span class="pc__stock" style="margin:0">В наличии</span>
        <div class="ptools">
          <div class="share">
            <button id="bShare" title="Поделиться" aria-label="Поделиться" aria-haspopup="menu" aria-expanded="false"><span id="iShare"><?= bt_icon('share') ?></span></button>
            <div class="share__m" id="shareMenu" role="menu" hidden>
              <a href="#" role="menuitem" data-share="copy"><?= bt_icon('doc') ?>Скопировать ссылку</a>
              <a href="#" role="menuitem" data-share="tg" target="_blank" rel="noopener"><?= bt_icon('tg') ?>Telegram</a>
              <a href="#" role="menuitem" data-share="wa" target="_blank" rel="noopener"><?= bt_icon('wa') ?>WhatsApp</a>
              <a href="#" role="menuitem" data-share="vk" target="_blank" rel="noopener"><?= bt_icon('vk') ?>ВКонтакте</a>
            </div>
          </div>
          <button id="bCmp" aria-pressed="false" title="Сравнить" aria-label="Сравнить"><span id="iCmp"><?= bt_icon('compare') ?></span></button>
          <button id="bFav" aria-pressed="false" title="В избранное" aria-label="В избранное"><span id="iFav"><?= bt_icon('heart') ?></span></button>
        </div>
      </div>
      <div class="buy" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
        <meta itemprop="price" content="<?= $first ?>"><meta itemprop="priceCurrency" content="RUB"><link itemprop="availability" href="https://schema.org/InStock">
        <div class="buy__price"><b id="pTotal"><?= $first ? bt_fmt($first) : 'По запросу' ?></b><span class="per" id="pPer"><?= $bulk ? bt_fmt($first) . ' за кг · ' . $bulk[0]['kg'] . ' кг' : 'за 1 шт' ?></span><?php if (!empty($m['old'])): ?> <span class="price--old"><?= bt_fmt($m['old']) ?></span><?php endif ?></div>
        <div id="pPacks"></div>
        <div class="buy__row">
          <div class="qty"><button data-d="-" aria-label="Уменьшить">−</button><input id="qty" value="1" inputmode="numeric" aria-label="Количество, <?= $unit ?>"><button data-d="+" aria-label="Увеличить">+</button></div>
          <span id="pAdd"></span>
          <button class="btn btn--line" type="button" data-lead="Купить в один клик" data-product="<?= $e($name) ?>">Купить в один клик</button>
        </div>
        <div class="dship" id="dship"></div>
      </div>
      <?php if ($bulk): ?>
      <details class="tiers__wrap" open>
        <summary>Оптовые цены и выгода</summary>
        <table class="tiers" id="tiers">
          <thead><tr><th>Количество</th><th>Цена за кг</th><th>Выгода</th></tr></thead>
          <tbody>
          <?php foreach ($bulk as $i => $t): $pct = $t['p'] < $first ? (int)round((1 - $t['p'] / $first) * 100) : 0; ?>
            <tr data-min="<?= $t['kg'] ?>"<?= $i ? '' : ' class="on"' ?>><td>от <?= $t['kg'] ?> кг</td><td><?= bt_fmt($t['p']) ?></td><td<?= $pct ? ' class="sv"' : '' ?>><?= $pct ? '−' . $pct . '%' : '—' ?></td></tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </details>
      <?php endif ?>
      <?php if ($arResult['BT_NOTES']): ?>
        <p class="notes" style="margin:22px 0 0;font-size:16px;max-width:54ch;display:flex;flex-wrap:wrap;align-items:center;gap:6px">Во вкусе: <?= implode('', array_map(fn($n) => '<i>' . $e(trim($n)) . '</i>', explode(',', $arResult['BT_NOTES']))) ?></p>
      <?php elseif (!empty($m['par'])): ?>
        <p class="notes" style="margin:22px 0 0;font-size:16px;max-width:54ch;display:block"><?= $e($m['par']) ?></p>
      <?php endif ?>
      <div class="ship" style="margin-top:20px">
        <div>Доставка по всей России — СДЭК, 2–7 дней, тариф считается при оформлении</div>
        <div>Екатеринбург — курьером от 350 ₽, бесплатно от 3 000 ₽</div>
        <div>Самовывоз: <?= $e($co['street'] ?? '') ?>, <?= $e(mb_strtolower($co['hours'] ?? '')) ?></div>
      </div>
    </div>
  </div>

  <div class="tabsblock">
    <div class="tabs" id="ptabs">
      <button aria-selected="true" data-p="desc">Описание</button>
      <?php if ($arResult['BT_TECH']): ?><button aria-selected="false" data-p="tech">Характеристики</button><?php endif ?>
      <?php if ($arResult['BT_BREW']): ?><button aria-selected="false" data-p="brew"><?= $arResult['PROPERTIES']['HOW_TO_USE']['VALUE'] ? 'Как использовать' : 'Как готовить' ?></button><?php endif ?>
      <button aria-selected="false" data-p="rev" id="tabRev">Отзывы<?= $revN ? ' (' . $revN . ')' : '' ?></button>
      <button aria-selected="false" data-p="deliv">Доставка</button>
    </div>
    <div class="pane prose" data-pane="desc">
      <?php if ($arResult['BT_SPECS']): ?>
      <div class="pspecs">
        <h3>Подробнее</h3>
        <div class="pspecs__grid">
          <?php foreach ($arResult['BT_SPECS'] as $label => $v): ?><div><span><?= $label ?></span><b><?= $e($v) ?></b></div><?php endforeach ?>
        </div>
      </div>
      <?php endif ?>
      <?php if (trim($arResult['~DETAIL_TEXT']) !== ''): ?>
        <h3 class="h3" style="margin:0 0 12px">Описание</h3>
        <div class="post__body pdesc"><?= bt_br_list($arResult['~DETAIL_TEXT']) ?></div>
      <?php endif ?>
    </div>
    <?php if ($arResult['BT_TECH']): ?><div class="pane prose" data-pane="tech" hidden><div class="post__body pdesc"><?= bt_br_list($arResult['BT_TECH']) ?></div></div><?php endif ?>
    <?php if ($arResult['BT_BREW']): ?><div class="pane prose" data-pane="brew" hidden><div class="post__body pdesc"><?= bt_br_list($arResult['BT_BREW']) ?></div></div><?php endif ?>
    <div class="pane" data-pane="rev" hidden id="reviews">
      <div class="rating" id="rating"></div>
      <div id="revList"><?php if (!$revN): ?><a class="rev-empty" href="#revform"><?= bt_icon('star') ?><span><b>Отзывов пока нет — станьте первым!</b>Расскажите, как вам <?= $e($name) ?>: оценка и пара слов помогут другим покупателям.</span></a><?php endif ?></div>
      <div class="card" id="revform" style="margin-top:18px;scroll-margin-top:130px">
        <h3 class="h3" style="margin-bottom:6px">Оставить отзыв</h3>
        <p class="muted" style="margin:0 0 18px;font-size:14px">Отзывы публикуем после проверки заказа: пишут только те, кто покупал. Фото помогают другим покупателям.</p>
        <?= bt_review_form(['product' => (int)$arResult['ID'], 'placeholder' => 'Как раскрылся вкус, какой помол выставили, с молоком или без']) ?>
      </div>
    </div>
    <div class="pane prose" data-pane="deliv" hidden>
      <ul><li>По Екатеринбургу — курьером на следующий день после заказа, от 350 ₽; бесплатно при заказе от 3 000 ₽.</li><li>По России — СДЭК, 2–7 дней, стоимость считается при оформлении заказа.</li><li>Самовывоз — <?= $e($co['street'] ?? '') ?>, по договорённости с менеджером.</li></ul>
      <p><a href="/oplata-i-dostavka/">Полные условия оплаты и доставки</a></p>
    </div>
  </div>

  <?php if ($arResult['BT_REC']): ?>
  <section class="sec">
    <div class="row between" style="margin-bottom:22px"><h2 class="display h2">Рекомендуем к нему</h2><a class="link" href="<?= $e($back) ?>">Весь раздел →</a></div>
    <div class="grid g4" id="rec"><?php foreach ($arResult['BT_REC'] as $r) { echo bt_nomicro(bt_card($r)); } ?></div>
  </section>
  <?php endif ?>
<script>window.BT_PAGE=<?= Json::encode(['id' => (string)$arResult['ID'], 'name' => $name, 'unit' => $unit, 'photos' => count($photos), 'reviews' => $reviews]) ?>;</script>
<?php include __DIR__ . '/script.php';
