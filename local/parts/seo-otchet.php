<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
/** @var array $f фильтр отчёта */
/** @var string $tab order | lead | visit */

use Bt\Attr;

$e = fn($s) => htmlspecialcharsbx((string)$s);
$rub = fn($v) => number_format((float)$v, (float)$v == floor((float)$v) ? 0 : 2, ',', ' ') . ' ₽';
$pct = Attr\percent();
$orders = Attr\report('order', $f);
$leads = Attr\report('lead', $f);
$visits = Attr\report('visit', $f);
$to = Attr\totals($orders);
$tl = Attr\totals($leads);
$url = fn(array $p) => '?' . http_build_query(array_merge(['from' => $f['from'], 'to' => $f['to'], 'src' => $f['src'], 'brand' => $f['brand'], 'tab' => $tab], $p));
$periods = [
    'Этот месяц' => [date('Y-m-01'), date('Y-m-d')],
    'Прошлый месяц' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    '30 дней' => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
    'С начала года' => [date('Y-01-01'), date('Y-m-d')],
];
$dt = fn($d) => $d ? FormatDate('j M Y, H:i', MakeTimeStamp((string)$d)) : '—';
$entry = fn($r) => '<b>' . $e(Attr\ENGINES[$r['ENGINE']] ?? (Attr\SOURCES[$r['SOURCE']] ?? $r['SOURCE'])) . ($r['SOURCE'] === 'referral' && $r['REF_HOST'] ? ' · ' . $e($r['REF_HOST']) : '') . '</b>'
    . '<span class="muted">' . $e($r['LANDING']) . '</span><span class="muted">' . $dt($r['ENTRY_AT']) . '</span>';
$brand = function (array $r) use ($e) {
    $h = '<form method="post" class="seor__brand">' . bitrix_sessid_post() . '<input type="hidden" name="action" value="brand"><input type="hidden" name="id" value="' . (int)$r['ID'] . '">'
        . '<input type="hidden" name="anchor" value="r' . (int)$r['ID'] . '">';
    foreach (['' => 'Авто: ' . ($r['BRAND_AUTO'] === 'Y' ? 'бренд' : 'не бренд'), 'N' => 'Не бренд', 'Y' => 'Бренд'] as $v => $t) {
        $on = (string)$r['BRAND_MANUAL'] === $v;
        $h .= '<button type="submit" name="v" value="' . $v . '" class="chipx"' . ($on ? ' aria-pressed="true"' : '') . '>' . $e($t) . '</button>';
    }
    return $h . '</form>';
};
?>
<div class="wrap seor">
  <div class="pagehead"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
    <p class="sub">Заказы и заявки посетителей, пришедших из поиска по небрендовому запросу. Вознаграждение — <?= $e(str_replace('.', ',', (string)$pct)) ?>% от <?= Attr\withDelivery() ? 'суммы заказа с доставкой' : 'суммы товаров без доставки' ?>.</p></div>

  <form class="seor__filter card" method="get">
    <input type="hidden" name="tab" value="<?= $e($tab) ?>">
    <div class="field"><label for="sfFrom">С</label><input id="sfFrom" type="date" name="from" value="<?= $e($f['from']) ?>"></div>
    <div class="field"><label for="sfTo">По</label><input id="sfTo" type="date" name="to" value="<?= $e($f['to']) ?>"></div>
    <div class="field"><label for="sfSrc">Источник</label><select id="sfSrc" name="src">
      <option value="organic"<?= $f['src'] === 'organic' ? ' selected' : '' ?>>Только поиск</option><option value="all"<?= $f['src'] === 'all' ? ' selected' : '' ?>>Все источники</option></select></div>
    <div class="field"><label for="sfBrand">Запрос</label><select id="sfBrand" name="brand">
      <option value="nonbrand"<?= $f['brand'] === 'nonbrand' ? ' selected' : '' ?>>Не брендовый</option><option value="brand"<?= $f['brand'] === 'brand' ? ' selected' : '' ?>>Брендовый</option><option value="all"<?= $f['brand'] === 'all' ? ' selected' : '' ?>>Любой</option></select></div>
    <button class="btn" type="submit">Показать</button>
    <div class="seor__periods"><?php foreach ($periods as $t => [$a, $b]): ?><a class="chipx"<?= $f['from'] === $a && $f['to'] === $b ? ' aria-pressed="true"' : '' ?> href="<?= $e($url(['from' => $a, 'to' => $b])) ?>"><?= $e($t) ?></a><?php endforeach ?></div>
  </form>

  <div class="seor__sum">
    <div class="card"><span class="muted">Входы из поиска</span><b><?= count($visits) ?></b><span class="muted">по выбранному запросу</span></div>
    <div class="card"><span class="muted">Засчитано заказов</span><b><?= $to['n'] ?></b><span class="muted">на <?= $rub($to['base']) ?></span></div>
    <div class="card"><span class="muted">Засчитано заявок</span><b><?= $tl['n'] ?></b><span class="muted">на <?= $rub($tl['base']) ?></span></div>
    <div class="card seor__fee"><span>Вознаграждение <?= $e(str_replace('.', ',', (string)$pct)) ?>%</span><b><?= $rub($to['fee'] + $tl['fee']) ?></b><span>заказы <?= $rub($to['fee']) ?> · заявки <?= $rub($tl['fee']) ?></span></div>
  </div>

  <div class="seor__tabs">
    <?php foreach (['order' => ['Заказы', $orders], 'lead' => ['Заявки', $leads], 'visit' => ['Входы', $visits]] as $k => [$t, $l]): ?>
      <a class="chipx"<?= $tab === $k ? ' aria-pressed="true"' : '' ?> href="<?= $e($url(['tab' => $k])) ?>"><?= $e($t) ?> <span class="muted"><?= count($l) ?></span></a>
    <?php endforeach ?>
    <a class="seor__csv" href="<?= $e($url(['format' => 'csv'])) ?>">Скачать CSV для Excel</a>
  </div>

  <div class="seor__scroll">
  <?php if ($tab === 'order'): ?>
    <?php if (!$orders): ?><p class="seor__empty">За этот период заказов с таким источником нет.</p><?php else: ?>
    <table class="tbl">
      <thead><tr><th>Заказ</th><th>Сумма</th><th>Вход на сайт</th><th>Запрос</th><th>Вознаграждение</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $r): $o = $r['ORDER']; ?>
        <tr id="r<?= (int)$r['ID'] ?>"<?= $r['COUNTED'] ? '' : ' class="is-off"' ?>>
          <td><b><?php if ($o): ?><a href="/bitrix/admin/sale_order_view.php?ID=<?= (int)$r['ENTITY_ID'] ?>&amp;lang=ru" target="_blank">№ <?= $e($o['num']) ?></a><?php else: ?>№ <?= (int)$r['ENTITY_ID'] ?><?php endif ?></b>
            <span class="muted"><?= $dt($r['CREATED']) ?></span><?php if ($o): ?><span><?= $e($o['buyer']) ?></span><span class="muted"><?= $e($o['status']) ?><?= $o['paid'] ? ', оплачен' : '' ?></span><?php endif ?></td>
          <td><?php if ($o): ?><b><?= $rub($o['price']) ?></b><span class="muted">доставка <?= $rub($o['delivery']) ?></span><span class="muted">база <?= $rub($r['BASE']) ?></span><?php endif ?></td>
          <td><?= $entry($r) ?></td>
          <td><?= $brand($r) ?></td>
          <td><?= $r['COUNTED'] ? '<b>' . $rub($r['FEE']) . '</b>' : '<span class="muted">' . $e($r['WHY']) . '</span>' ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php endif ?>
  <?php elseif ($tab === 'lead'): ?>
    <?php if (!$leads): ?><p class="seor__empty">За этот период заявок с таким источником нет.</p><?php else: ?>
    <table class="tbl">
      <thead><tr><th>Заявка</th><th>Вход на сайт</th><th>Запрос</th><th>Сумма сделки</th><th>Вознаграждение</th></tr></thead>
      <tbody>
      <?php foreach ($leads as $r): $l = $r['LEAD']; ?>
        <tr id="r<?= (int)$r['ID'] ?>"<?= $r['COUNTED'] ? '' : ' class="is-off"' ?>>
          <td><b><a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=<?= (int)bt_iblock('form_requests') ?>&amp;type=forms&amp;ID=<?= (int)$r['ENTITY_ID'] ?>&amp;lang=ru" target="_blank"><?= $e(($l['topic'] ?? '') ?: 'Заявка ' . $r['ENTITY_ID']) ?></a></b>
            <span class="muted"><?= $dt($r['CREATED']) ?></span><?php if ($l): ?><span><?= $e(trim($l['name'] . ' ' . $l['phone'])) ?></span><?php endif ?></td>
          <td><?= $entry($r) ?></td>
          <td><?= $brand($r) ?></td>
          <td><form method="post" class="seor__amount"><?= bitrix_sessid_post() ?><input type="hidden" name="action" value="amount"><input type="hidden" name="id" value="<?= (int)$r['ID'] ?>"><input type="hidden" name="anchor" value="r<?= (int)$r['ID'] ?>">
            <input name="amount" inputmode="decimal" placeholder="0" aria-label="Сумма сделки, ₽" value="<?= $r['AMOUNT'] === null ? '' : $e(rtrim(rtrim(number_format((float)$r['AMOUNT'], 2, ',', ' '), '0'), ',')) ?>"><button class="btn btn--sm btn--line" type="submit">Сохранить</button></form></td>
          <td><?= $r['COUNTED'] ? '<b>' . $rub($r['FEE']) . '</b>' : '<span class="muted">' . $e($r['WHY']) . '</span>' ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php endif ?>
  <?php else: ?>
    <?php if (!$visits): ?><p class="seor__empty">За этот период входов с таким источником нет.</p><?php else:
      $pages = array_count_values(array_column($visits, 'LANDING')); arsort($pages); ?>
    <div class="seor__pages card"><b>Страницы входа</b><?php foreach (array_slice($pages, 0, 15, true) as $p => $n): ?><span><a href="<?= $e($p) ?>" target="_blank"><?= $e($p) ?></a><span class="muted"><?= $n ?></span></span><?php endforeach ?></div>
    <table class="tbl">
      <thead><tr><th>Дата</th><th>Вход на сайт</th><th>Запрос</th></tr></thead>
      <tbody>
      <?php foreach ($visits as $r): ?>
        <tr id="r<?= (int)$r['ID'] ?>"><td><?= $dt($r['CREATED']) ?></td><td><?= $entry($r) ?></td><td><?= $brand($r) ?></td></tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php endif ?>
  <?php endif ?>
  </div>

  <div class="seor__foot">
    <form method="post" class="seor__set card"><?= bitrix_sessid_post() ?><input type="hidden" name="action" value="settings">
      <b>Расчёт</b>
      <div class="field"><label for="ssPct">Процент</label><input id="ssPct" name="pct" inputmode="decimal" value="<?= $e(str_replace('.', ',', (string)$pct)) ?>"></div>
      <label class="check"><input type="checkbox" name="with_delivery" value="Y"<?= Attr\withDelivery() ? ' checked' : '' ?>><span>Считать с доставкой</span></label>
      <button class="btn btn--sm" type="submit">Сохранить</button>
    </form>
    <div class="seor__how card"><b>Как считается</b>
      <ul>
        <li>Вход из поиска — переход из Яндекса, Google и других поисковиков без рекламной метки.</li>
        <li>Источник помнится 180 дней. Прямой заход его не перебивает, реклама и переход с другого сайта — перебивают.</li>
        <li>Брендовым считается вход на главную, «Контакты», «О компании» и «Отзывы». Любую строку можно переключить вручную.</li>
        <li>Засчитываются все оформленные заказы, кроме отменённых. По заявкам — сумма сделки, вписанная вручную.</li>
        <li>Поисковики не передают текст запроса, поэтому брендовость определяется по странице входа.</li>
      </ul>
    </div>
  </div>
</div>
