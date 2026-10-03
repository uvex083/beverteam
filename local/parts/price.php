<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// Прайс-лист: товары каталога по разделам с оптовыми ступенями цен; количество меняет общую корзину сайта (ui.js, data-prc)

$e = fn($s) => htmlspecialcharsbx((string)$s);
$rub = fn($p) => number_format((float)$p, 0, '', "\u{00A0}") . "\u{00A0}₽";
$list = bt_price_list();
$total = array_sum(array_column($list, 'cnt'));
$ts = bt_price_date();
$months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
// цена за кг для веса $kg по ступеням товара
$tier = function (array $m, int $kg) {
    $p = $m['p'];
    foreach ((array)($m['bulk'] ?? []) as $b) {
        $b['kg'] <= $kg and $p = $b['p'];
    }
    return $p;
};
$disc = bt_sum_discounts();
$fmtFrom = fn($d) => $d['pct'] . '% от ' . number_format($d['from'], 0, '', "\u{00A0}") . "\u{00A0}₽";
// подборки сверху: «Рекомендуем» — хиты, топ продаж и новинки, «Акции» — товары со старой ценой или меткой скидки
$all = [];
foreach ($list as $t) {
    foreach ($t['subs'] as $items) {
        foreach ($items as $m) {
            $m['p'] and $all[$m['id']] = $m;
        }
    }
}
$hasBadge = fn($m, array $b) => (bool)array_intersect(array_map('mb_strtolower', (array)($m['badges'] ?? [])), $b);
$tabs = array_filter([
    'rec' => ['Рекомендуем', array_slice(array_filter($all, fn($m) => $hasBadge($m, ['хит', 'топ продаж', 'новинка'])), 0, 12)],
    'promo' => ['Акции', array_slice(array_filter($all, fn($m) => (!empty($m['old']) && $m['old'] > $m['p']) || $hasBadge($m, ['скидка', 'распродажа'])), 0, 12)],
], fn($x) => $x[1]);
// запрос счёта: заказ на юрлицо — самовывоз в Екатеринбурге и оплата по счёту, доставку менеджер согласует отдельно
$inv = [];
if (\Bitrix\Main\Loader::includeModule('sale')) {
    $inv = [
        'loc' => (string)(\Bitrix\Sale\Location\LocationTable::getList(['filter' => ['=NAME.NAME' => 'Екатеринбург', '=NAME.LANGUAGE_ID' => 'ru', '=TYPE.CODE' => 'CITY'], 'select' => ['CODE'], 'limit' => 1])->fetch()['CODE'] ?? ''),
        'delivery' => (int)(\Bitrix\Sale\Delivery\Services\Table::getList(['filter' => ['=XML_ID' => 'bt_pickup', '=ACTIVE' => 'Y'], 'select' => ['ID']])->fetch()['ID'] ?? 0),
        'pay' => (int)(\Bitrix\Sale\Internals\PaySystemActionTable::getList(['filter' => ['=ACTION_FILE' => 'bill', '=ACTIVE' => 'Y'], 'select' => ['ID']])->fetch()['ID'] ?? 0),
    ];
    $inv = array_filter($inv) === $inv ? $inv : [];
}
?>
<div class="wrap prcp">
  <?php bt_crumbs() ?>

  <div class="demo-note prcask"><b>Уточнить у клиента</b>
    <ul>
      <li><b>Скидка от суммы заказа</b> 5% от 20&nbsp;000&nbsp;₽ и 10% от 40&nbsp;000&nbsp;₽ — придумали мы, уже работает в корзине, заказе и счёте. Подтвердить пороги и проценты или отключить: Магазин → Правила работы с корзиной.</li>
      <li><b>Оптовые ступени по весу</b> (1/5/10/20/30 кг) есть только у Эфиопии Оромии — нужны ли по остальным сортам кофе и какие.</li>
      <li><b>Отдельные цены для юрлиц и оптовиков</b>, ниже розничных, — нужны ли.</li>
      <li><b>Персональные ссылки</b> вида /price/?m=ivanov — метка попадает в заказ: какие метки раздавать менеджерам.</li>
    </ul>
  </div>

  <div class="prchead">
    <div>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <p class="prcsub">Кофе, чай, кофемашины и аксессуары с ценами для офисов, кафе и магазинов. Прайс обновляется вместе с каталогом — ссылка всегда показывает действующие цены.</p>
      <p class="prcdate"><?= bt_icon('calendar') ?>Цены актуальны на <?= date('j', $ts) . ' ' . $months[date('n', $ts) - 1] . ' ' . date('Y', $ts) ?></p>
    </div>
    <div class="prcact">
      <a class="btn btn--line" href="?format=csv" download><?= bt_icon('doc') ?>Скачать Excel</a>
      <a class="btn btn--line" href="?format=pdf" target="_blank" rel="noopener"><?= bt_icon('doc') ?>PDF</a>
      <button class="btn btn--line" type="button" data-prc-share><?= bt_icon('share') ?>Поделиться ссылкой</button>
    </div>
  </div>

  <?php if ($disc): ?>
  <div class="prcdisc">
    <span class="prcdisc__t">Скидка от суммы заказа</span>
    <?php foreach ($disc as $d): ?><span class="prcdisc__i"><?= $e($fmtFrom($d)) ?></span><?php endforeach ?>
    <span class="prcdisc__n">Считается автоматически в корзине, заказе и счёте</span>
  </div>
  <?php endif ?>

  <?php if ($tabs): ?>
  <section class="prcrec" data-prc-rec>
    <div class="prcrec__tabs" role="tablist">
      <?php foreach (array_keys($tabs) as $i => $k): ?><button type="button" role="tab" aria-selected="<?= $i ? 'false' : 'true' ?>" data-prc-tab="<?= $k ?>"><?= $e($tabs[$k][0]) ?></button><?php endforeach ?>
    </div>
    <?php foreach (array_keys($tabs) as $i => $k): ?>
    <div class="prcrec__list" role="tabpanel" data-prc-pane="<?= $k ?>"<?= $i ? ' hidden' : '' ?>>
      <?php foreach ($tabs[$k][1] as $m): ?>
      <div class="prcrec__c">
        <?php if (!empty($m['img'])): ?><img src="<?= $e($m['img']) ?>" alt="" width="72" height="72" loading="lazy"><?php endif ?>
        <div>
          <a href="<?= $e($m['url']) ?>"><?= $e($m['n']) ?></a>
          <span class="prcrec__p"><?= $rub($m['p']) ?><?= !empty($m['bulk']) ? ' за кг' : '' ?><?php if (!empty($m['old']) && $m['old'] > $m['p']): ?> <s><?= $rub($m['old']) ?></s><?php endif ?></span>
        </div>
        <button class="prcrec__add" type="button" data-prc-add="<?= $e($m['id']) ?>" aria-label="Добавить в заказ: <?= $e($m['n']) ?>">+</button>
      </div>
      <?php endforeach ?>
    </div>
    <?php endforeach ?>
  </section>
  <?php endif ?>

  <div class="prcbar" data-prc-bar>
    <label class="prcsearch"><?= bt_icon('search') ?><input type="search" placeholder="Поиск: Оромия, улун, чайник" aria-label="Поиск по прайсу" autocomplete="off" data-prc-q></label>
    <div class="prccats" role="group" aria-label="Категория">
      <button class="chipx" type="button" data-prc-cat="" aria-pressed="true">Весь прайс <s><?= $total ?></s></button>
      <button class="chipx prccats__sel" type="button" data-prc-cat="sel" aria-pressed="false">Выбранные <s>0</s></button>
      <?php foreach ($list as $code => $t): ?><button class="chipx" type="button" data-prc-cat="<?= $e($code) ?>" aria-pressed="false"><?= $e($t['name']) ?> <s><?= $t['cnt'] ?></s></button><?php endforeach ?>
    </div>
  </div>

  <?php foreach ($list as $code => $t):
      $kgs = [];
      foreach ($t['subs'] as $items) {
          foreach ($items as $m) {
              foreach ((array)($m['bulk'] ?? []) as $b) {
                  $kgs[$b['kg']] = true;
              }
          }
      }
      ksort($kgs);
      $kgs = array_keys($kgs);
      $cols = max(1, count($kgs)); ?>
  <section class="prcsec" data-prc-sec="<?= $e($code) ?>">
    <h2 class="prcsec__t"><a href="<?= $e($t['url']) ?>"><?= $e($t['name']) ?></a></h2>
    <?php if ($kgs): ?><p class="prcsec__n">Оптовая цена — за килограмм, считается по общему весу позиции в заказе. Выбранная ступень подсвечивается.</p><?php endif ?>
    <table class="prct<?= $kgs ? ' prct--tiers' : '' ?>">
      <thead><tr>
        <th scope="col">Товар</th>
        <?php if ($kgs): foreach ($kgs as $i => $k): ?><th scope="col" class="prct__p"><?= $i ? 'от ' . $k . ' кг' : $k . ' кг' ?></th><?php endforeach; else: ?><th scope="col" class="prct__p">Цена</th><?php endif ?>
        <th scope="col" class="prct__qh">Количество</th>
      </tr></thead>
      <?php foreach ($t['subs'] as $sub => $items): ?>
      <tbody data-prc-group>
        <?php if ($sub !== ''): ?><tr class="prct__sub"><th colspan="<?= $cols + 2 ?>" scope="colgroup"><?= $e($sub) ?></th></tr><?php endif ?>
        <?php foreach ($items as $m):
            $bulk = !empty($m['bulk']);
            $q = mb_strtolower($m['n'] . ' ' . ($m['par'] ?? '') . ' ' . $sub . ' ' . $t['name']); ?>
        <tr class="prct__r" data-prc-row data-id="<?= $e($m['id']) ?>" data-kg="<?= $bulk ? 1 : 0 ?>" data-q="<?= $e($q) ?>">
          <td class="prct__n">
            <a href="<?= $e($m['url']) ?>"><?= $e($m['n']) ?></a>
            <?php if (!empty($m['par'])): ?><small><?= $e($m['par']) ?></small><?php endif ?>
            <?php if (!empty($m['badges']) || !empty($m['pre']) || empty($m['stock'])): ?><span class="prct__b"><?= implode('', array_map('bt_badge', (array)($m['badges'] ?? []))) ?><?= !empty($m['pre']) ? '<span class="badge badge--warn">Предзаказ</span>' : (empty($m['stock']) ? '<span class="badge badge--soft">Под заказ</span>' : '') ?></span><?php endif ?>
          </td>
          <?php if (!$m['p']): ?>
          <td class="prct__p prct__ask" colspan="<?= $cols ?>">По запросу</td>
          <?php elseif ($kgs && $bulk): foreach ($kgs as $i => $k): ?>
          <td class="prct__p" data-prc-kg="<?= $k ?>" data-label="<?= $i ? 'от ' . $k . ' кг' : $k . ' кг' ?>"><?= $rub($tier($m, $k)) ?></td>
          <?php endforeach; else: ?>
          <td class="prct__p" colspan="<?= $cols ?>"><?= $rub($m['p']) ?><?php if (!empty($m['old']) && $m['old'] > $m['p']): ?> <s><?= $rub($m['old']) ?></s><?php endif ?><?php if ($kgs): ?><small>цена не зависит от объёма</small><?php endif ?></td>
          <?php endif ?>
          <td class="prct__q">
            <?php if ($m['p']): ?>
            <div class="prcq">
              <button type="button" data-prc-step="-1" aria-label="Меньше">−</button><input type="text" inputmode="numeric" value="0" aria-label="<?= $e($m['n']) ?>: количество, <?= $bulk ? 'кг' : 'шт' ?>" data-prc-in><button type="button" data-prc-step="1" aria-label="Больше">+</button>
            </div>
            <span class="prcq__u"><?= $bulk ? 'кг' : 'шт' ?></span>
            <span class="prcq__s" data-prc-sum></span>
            <?php endif ?>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
      <?php endforeach ?>
    </table>
  </section>
  <?php endforeach ?>
  <p class="prcempty" data-prc-empty hidden>Ничего не нашлось. <button class="link" type="button" data-prc-reset>Показать весь прайс</button></p>

  <section class="prcsubs">
    <div>
      <div class="prcsubs__t th th3">Сообщим, когда изменятся цены</div>
      <p>Одно письмо со ссылкой на прайс после изменения цен. Отписаться — в один клик из письма.</p>
      <?php if (!empty($_GET['unsub'])): ?><p class="prcsubs__ok">Вы отписались — писем об изменении цен больше не будет.</p><?php endif ?>
    </div>
    <form class="prcsubs__f" data-prc-subf novalidate>
      <div class="field"><label for="prcSubEmail">E-mail</label><input id="prcSubEmail" name="email" type="email" autocomplete="email" placeholder="mail@company.ru"></div>
      <?= bt_form_tail('Подписаться') ?>
    </form>
  </section>

  <p class="prcfoot">Цены в рублях. Окончательную стоимость, наличие и сроки подтвердит менеджер после оформления заказа.</p>
</div>

<?php if ($inv): ?>
<div class="modal modal--revf" id="prcInv" role="dialog" aria-modal="true" aria-labelledby="prcInvT">
  <div class="modal__bg" data-close></div>
  <div class="modal__p">
    <button class="modal__x" type="button" data-close aria-label="Закрыть">×</button>
    <h3 class="h3" id="prcInvT" style="margin:0 0 6px;padding-right:36px">Запросить счёт</h3>
    <p class="muted" style="margin:0 0 18px;font-size:14px">Оформим заказ на организацию по товарам из корзины — <b data-prc-inv-sum></b>. Менеджер подтвердит наличие и доставку, счёт придёт на e-mail.</p>
    <form data-prc-invf data-loc="<?= $e($inv['loc']) ?>" data-delivery="<?= $inv['delivery'] ?>" data-pay="<?= $inv['pay'] ?>" novalidate>
      <div class="grid g2" style="gap:0 14px">
        <div class="field"><label for="prcInvCo">Название организации *</label><input id="prcInvCo" name="company" autocomplete="organization" placeholder="ООО «Ромашка»"></div>
        <div class="field"><label for="prcInvInn">ИНН *</label><input id="prcInvInn" name="inn" inputmode="numeric" maxlength="12" placeholder="10 или 12 цифр"></div>
        <div class="field"><label for="prcInvName">Контактное лицо *</label><input id="prcInvName" name="name" autocomplete="name"></div>
        <div class="field"><label for="prcInvTel">Телефон *</label><input id="prcInvTel" name="phone" type="tel" autocomplete="tel" placeholder="+7 ___ ___-__-__"></div>
        <div class="field"><label for="prcInvMail">E-mail для счёта *</label><input id="prcInvMail" name="email" type="email" autocomplete="email"></div>
        <div class="field"><label for="prcInvKpp">КПП</label><input id="prcInvKpp" name="kpp" inputmode="numeric" maxlength="9" placeholder="Для ООО"></div>
      </div>
      <div class="field"><label for="prcInvCom">Комментарий</label><textarea id="prcInvCom" name="comment" rows="2" maxlength="2000" placeholder="Адрес доставки, удобное время, вопросы"></textarea></div>
      <?= bt_form_tail('Запросить счёт') ?>
      <p class="err-form" data-prc-inv-err role="alert"></p>
    </form>
  </div>
</div>
<?php endif ?>

<div class="prcbag" data-prc-bag hidden>
  <div class="wrap prcbag__in">
    <div class="prcbag__t">
      <span>В корзине <b data-prc-bag-n></b> на <b data-prc-bag-s></b><span class="prcbag__d" data-prc-bag-d></span></span>
      <small data-prc-bag-h></small>
    </div>
    <div class="prcbag__b">
      <?php if ($inv): ?><button class="btn btn--line" type="button" data-prc-inv>Запросить счёт</button><?php endif ?>
      <a class="btn" href="/personal/cart/">Оформить заказ</a>
    </div>
  </div>
</div>
