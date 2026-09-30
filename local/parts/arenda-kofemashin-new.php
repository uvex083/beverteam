<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Аренда кофемашин», новая версия для сравнения: модели и цены — ИБ rent и каталог, тексты пока здесь (после решения — в ИБ типа «Аренда кофемашин»)

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$plain = fn($h) => trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], ' ', (string)$h)), ENT_QUOTES));
$co = bt_contacts();
$models = bt_rent_models();
$data = bt_catalog_data();
usort($models, fn($a, $b) => $a['cups'] <=> $b['cups']);

// характеристики машин — из карточек каталога
$specCodes = ['SCREEN' => 'Экран', 'WATER_SUPPLY' => 'Водопровод', 'WATER_TANK' => 'Бак для воды', 'BEAN_HOPPER' => 'Бункер зерна', 'TELEMETRY' => 'Онлайн-телеметрия', 'MDB' => 'Платёжный терминал', 'DIMENSIONS' => 'Габариты, Ш×Г×В'];
$specs = [];
if ($models && \Bitrix\Main\Loader::includeModule('iblock')) {
    $codes = array_filter(array_map(fn($m) => basename(rtrim($m['url'], '/')), $models));
    $r = \CIBlockElement::GetList([], ['IBLOCK_ID' => bt_iblock('catalog'), 'CODE' => array_values($codes)], false, false, ['ID', 'IBLOCK_ID', 'CODE']);
    while ($ob = $r->GetNextElement()) {
        $f = $ob->GetFields();
        foreach ($ob->GetProperties(false, ['ACTIVE' => 'Y']) as $p) {
            if (isset($specCodes[$p['CODE']]) && !is_array($p['VALUE']) && $p['VALUE'] !== '' && $p['VALUE'] !== false) {
                $specs[$f['CODE']][$p['CODE']] = html_entity_decode((string)$p['VALUE']);
            }
        }
    }
}
$spec = fn($m, $c) => $specs[basename(rtrim($m['url'], '/'))][$c] ?? '';

$minKg = $models ? min(array_filter(array_column($models, 'kg')) ?: [0]) : 0;
$minPrice = $models ? min(array_column($models, 'price')) : 0;

// кофе BOTANICA: цена за кг (оптовая ступень от 5 кг, если есть) и себестоимость чашки 8 г
$beans = array_values(array_filter($data['coffee'], fn($c) => str_contains($c['n'], 'BOTANICA')));
$tier = function (array $c, float $kg): float {
    $p = $c['p'];
    foreach ((array)($c['bulk'] ?? []) as $t) {
        if ($kg >= $t['kg']) {
            $p = $t['p'];
        }
    }
    return (float)$p;
};
$bean0 = 'botanica-espresso-smes';

$calc = [
    'models' => array_map(fn($m) => ['id' => $m['id'], 'm' => $m['model'], 'aud' => $m['audience'], 'f' => $m['feature'], 'cups' => $m['cups'], 'price' => $m['price'], 'kg' => $m['kg'], 'img' => $m['img'], 'url' => $m['url']], $models),
    'beans' => array_map(fn($c) => ['code' => $c['code'], 'n' => preg_replace('/,\s*1\s*кг$/u', '', $c['n']), 'p' => $c['p'], 'bulk' => $c['bulk'] ?? null, 'url' => $c['url']], $beans),
    'g' => 8,
];
$places = [
    ['Офис', 'Сколько сотрудников пьёт кофе', 20, 2, 22],
    ['Кафе, HoReCa', 'Гостей с кофе в день', 60, 1, 30],
    ['Салон, клиника, шоурум', 'Клиентов и сотрудников в день', 25, 1, 26],
    ['Дом', 'Сколько человек пьёт кофе', 3, 2, 30],
];

$top = [
    'caption' => 'Аренда · Екатеринбург и Свердловская область',
    'sub' => 'Суперавтоматы Jetinno для офиса, кафе и бизнеса: 0 ₽ в месяц при заказе кофе BOTANICA или фиксированная аренда без привязки к кофе. Привозим, устанавливаем, настраиваем рецепты и обслуживаем — как авторизованный сервис Jetinno.',
    'chk' => ['Доставка и установка включены', 'Сервис и ремонт — в стоимости', 'Настроим рецепты под ваших гостей', 'Договор, оплата по счёту'],
];
$facts = [
    ['0 ₽', 'аренда в месяц при заказе кофе от ' . $minKg . ' кг'],
    ['от ' . bt_fmt($minPrice), 'в месяц — фиксированная аренда, кофе любой'],
    ['Jetinno', 'авторизованный сервисный центр'],
    ['с 2010', 'поставляем кофе и технику в Екатеринбурге'],
];
$included = [
    ['truck', 'Доставка и установка', 'Привезём, подключим к воде и электричеству, проверим на месте.'],
    ['star', 'Настройка рецептов', 'Крепость, объём, молоко — под ваших сотрудников и гостей.'],
    ['user', 'Обучение', 'Покажем, как заправлять, чистить и что делать при сообщениях на экране.'],
    ['repeat', 'Плановое обслуживание', 'Чистка, декальцинация, замена расходников по графику.'],
    ['doc', 'Ремонт в сервисе Jetinno', 'Оригинальные запчасти, инженеры авторизованного сервиса.'],
    ['phone', 'Связь с инженером', 'Позвоните — подскажем по телефону, при необходимости приедем.'],
    ['box', 'Доставка кофе', 'Курьером по Екатеринбургу на следующий день, бесплатно от 3 000 ₽.'],
    ['calendar', 'Замена модели', 'Выросла нагрузка — поменяем машину на более мощную.'],
];
// условия договора — ЗАГОТОВКИ, подтвердить у клиента (OPEN-QUESTIONS.md)
$terms = [
    'caption' => 'Условия без мелкого шрифта',
    'title' => 'Всё, что обычно прячут в договоре',
    'sub' => 'Бесплатная аренда пугает скрытыми условиями. Пишем их прямо на странице — до звонка менеджеру.',
    'items' => [
        'Залог — 0 ₽',
        'Минимальный срок — 6 месяцев, дальше помесячно',
        'Не выбрали объём кофе за месяц — без штрафов',
        'Кофе по цене каталога, как для всех покупателей; оптовые скидки от 5 кг',
        'Чистящие средства и расходники для обслуживания — за наш счёт',
        'Ремонт — бесплатно, на долгий ремонт привезём подменную машину',
        'Расторжение без штрафа: предупредите за 30 дней, машину заберём сами',
        'Обычный износ при возврате не оплачивается',
        'Можно выкупить машину: часть платежей засчитаем в цену',
    ],
];
$segments = [
    ['Офис', 'Кофе для команды без очередей к кофейне и без покупки техники. Капучино и латте на натуральном молоке одной кнопкой.', 'Офис'],
    ['Кафе и HoReCa', 'Суперавтомат вместо рожковой машины: одинаковый вкус у любого сотрудника, без бариста. Платёжный терминал подключается.', 'Кафе'],
    ['Салоны, клиники, автосалоны', 'Кофе для клиентов в зоне ожидания — часть сервиса. Компактные модели, бак для воды без подключения к водопроводу.', 'Офис'],
    ['Дом', 'Зерновой кофе и напитки с молоком каждый день без вложений в покупку машины.', 'Дом'],
];
$steps = [
    ['Расчёт', 'Посчитайте на калькуляторе или оставьте заявку — менеджер перезвонит за 5 минут в рабочее время.'],
    ['Подбор', 'Уточним место, число чашек и напитки, подберём модель и сорт кофе.'],
    ['Договор', 'Договор с ИП и юрлицом, оплата по счёту, закрывающие документы.'],
    ['Установка', 'Привезём, подключим, настроим рецепты и покажем сотрудникам.'],
    ['Работа', 'Кофе привозим по графику, обслуживание и ремонт — наша забота.'],
];
$faq = [
    ['Как взять кофемашину в аренду бесплатно?', 'Заказывайте у нас кофе BOTANICA от ' . $minKg . ' кг в месяц — тогда аренда машины 0 ₽. Порог зависит от модели: чем мощнее машина, тем больше кофе. Точный порог по каждой модели — в таблице сравнения на этой странице.'],
    ['Можно ли арендовать без покупки кофе?', 'Да. Фиксированная аренда от ' . bt_fmt($minPrice) . ' в месяц — кофе покупаете где хотите. Сервисное обслуживание входит и в этот вариант.'],
    ['Сколько кофе нужно офису?', 'На чашку уходит около 8 г зерна. Офис из 20 человек по 2 чашки в день за 22 рабочих дня расходует около 7 кг кофе в месяц. Посчитайте свой расход на калькуляторе вверху страницы.'],
    ['Что входит в стоимость аренды?', 'Доставка, установка, настройка рецептов, обучение сотрудников, плановое обслуживание и ремонт. Отдельно оплачиваются только кофе и молоко.'],
    ['Есть ли залог?', 'Нет, залог не берём.'],
    ['На какой срок заключается договор?', 'Минимальный срок — 6 месяцев, дальше договор продлевается помесячно. Расторгнуть можно, предупредив за 30 дней.'],
    ['Что будет, если в месяц уйдёт меньше кофе?', 'Штрафов нет. Если расход стабильно меньше порога — подберём модель меньше или переведём на фиксированную аренду.'],
    ['Что делать, если кофемашина сломалась?', 'Позвоните нам: инженер подскажет по телефону, при необходимости приедет. Ремонт делает авторизованный сервис Jetinno. Если ремонт займёт больше дня — привезём подменную машину.'],
    ['Нужно ли подключать кофемашину к водопроводу?', 'Не обязательно. JL15 и JL36 работают от водопровода, встроенного бака на 2 л или внешней канистры. JL05 — от встроенного бака.'],
    ['Какие напитки готовит машина?', 'Эспрессо, американо, капучино, латте, флэт уайт, горячее молоко и кипяток. Рецепты настраиваем под вас: крепость, объём, пропорцию молока.'],
    ['Можно ли на арендованной машине использовать свой кофе?', 'При фиксированной аренде — да, любой зерновой кофе. При бесплатной аренде машина работает на кофе BOTANICA, который вы заказываете у нас.'],
    ['Работаете ли вы с физлицами?', 'Да, аренда для дома возможна. Условия и договор менеджер согласует при заявке.'],
    ['Можно ли потом выкупить кофемашину?', 'Да. Часть уже внесённых платежей засчитаем в цену машины — сумму рассчитает менеджер.'],
    ['Можно ли арендовать кофемашину на мероприятие?', 'Да, на конференцию, выставку или праздник. Подберём модель под число гостей, стоимость — по запросу.'],
    ['Доставляете ли за пределы Екатеринбурга?', 'Да, по Свердловской области — список городов ниже. Условия доставки и выезда инженера зависят от расстояния.'],
    ['Какие документы нужны для договора?', 'Для юрлица и ИП — реквизиты компании. Менеджер пришлёт договор на согласование.'],
    ['Чем отличаются модели JL05, JL15 и JL36?', 'Нагрузкой и оснащением. JL05 — компактная, для дома и малого офиса. JL15 VIVA — офис до 60 чашек в день, тачскрин, телеметрия. JL36 — до 100 чашек, бункер 1,2 кг, капучинатор для потока гостей.'],
];

$geo = bt_block('repair_geo');
$cities = array_values(array_filter(array_column((array)($geo['items'] ?? []), 0), fn($v) => $v !== ''));
$revs = bt_reviews();

// разметка для поисковиков — из тех же данных
$page = 'https://beverteam.ru/arenda-kofemashin/';
$org = ['@id' => 'https://beverteam.ru/#org'];
$h1 = $APPLICATION->GetTitle(false);
$graph = [
    ['@type' => 'WebPage', '@id' => $page . '#page', 'url' => $page, 'name' => $h1, 'inLanguage' => 'ru', 'about' => ['@id' => $page . '#service'], 'publisher' => $org],
    ['@type' => 'Service', '@id' => $page . '#service', 'name' => $h1, 'serviceType' => 'Аренда кофемашин', 'description' => $top['sub'], 'provider' => $org,
        'areaServed' => array_merge(array_map(fn($g) => ['@type' => 'City', 'name' => $g], $cities), [['@type' => 'AdministrativeArea', 'name' => 'Свердловская область']]),
        'brand' => ['@type' => 'Brand', 'name' => 'Jetinno'],
        'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => 'Кофемашины в аренду', 'itemListElement' => array_map(fn($m) => [
            '@type' => 'Offer', 'name' => 'Аренда ' . $m['model'], 'priceCurrency' => 'RUB', 'price' => $m['price'],
            'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => $m['price'], 'priceCurrency' => 'RUB', 'unitCode' => 'MON', 'unitText' => 'месяц'],
            'description' => ($m['cups'] ? 'До ' . $m['cups'] . ' чашек в день. ' : '') . ($m['kg'] ? '0 ₽ при заказе кофе от ' . $m['kg'] . ' кг в месяц.' : ''),
            'itemOffered' => ['@type' => 'Product', 'name' => $m['model'], 'brand' => ['@type' => 'Brand', 'name' => 'Jetinno'], 'image' => 'https://beverteam.ru' . $m['img'], 'url' => 'https://beverteam.ru' . $m['url']],
        ], $models)]],
    ['@type' => 'HowTo', '@id' => $page . '#steps', 'name' => 'Как взять кофемашину в аренду',
        'step' => array_map(fn($i, $s) => ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => $s[0], 'text' => $s[1]], array_keys($steps), $steps)],
    ['@type' => 'FAQPage', '@id' => $page . '#faq', 'mainEntity' => array_map(fn($q) => ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]], $faq)],
];
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap rentp repp rep2 ar2">
  <?php bt_crumbs() ?>

  <div class="r2hero">
    <div>
      <div class="mono muted"><?= $e($top['caption']) ?></div>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <p class="sub"><?= $e($top['sub']) ?></p>
      <ul class="r2chk"><?php foreach ($top['chk'] as $h): ?><li><?= $e($h) ?></li><?php endforeach ?></ul>
      <div class="row" style="gap:12px">
        <a class="btn" href="#calc">Рассчитать аренду</a>
        <?php if (!empty($co['phone1'])): ?><a class="btn btn--line" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a><?php endif ?>
      </div>
    </div>
    <div class="r2facts"><?php foreach ($facts as [$b, $s]): ?><div><b><?= $e($b) ?></b><span><?= $e($s) ?></span></div><?php endforeach ?></div>
  </div>

  <?php if ($models && $beans): ?>
  <section class="sec sec--t0" id="calc">
    <h2 class="display h2">Калькулятор аренды</h2>
    <p class="muted r2lead">Сколько кофе уйдёт в месяц, какая машина справится и сколько стоит чашка — сразу в двух вариантах оплаты.</p>
    <div class="ar2calc" data-rcalc2='<?= htmlspecialcharsbx(json_encode($calc, JSON_UNESCAPED_UNICODE)) ?>'>
      <div class="ar2calc__in">
        <span class="ar2q">Куда поставим машину?</span>
        <div class="opts" role="group" aria-label="Куда поставим машину?"><?php foreach ($places as $i => [$pl, $who, $n, $per, $days]): ?>
          <button type="button" class="chipx" aria-pressed="<?= $i ? 'false' : 'true' ?>" data-rc2-place='<?= htmlspecialcharsbx(json_encode(['who' => $who, 'n' => $n, 'per' => $per, 'days' => $days], JSON_UNESCAPED_UNICODE)) ?>'><?= $e($pl) ?></button>
        <?php endforeach ?></div>
        <label class="ar2q" for="rc2n"><span data-rc2-who><?= $e($places[0][1]) ?></span> <b data-rc2-nv><?= $places[0][2] ?></b></label>
        <input class="rng" id="rc2n" type="range" min="1" max="150" step="1" value="<?= $places[0][2] ?>" data-rc2-n>
        <span class="ar2q">Чашек на человека в день</span>
        <div class="opts" role="group" aria-label="Чашек на человека в день" data-rc2-per><?php foreach ([1, 2, 3] as $v): ?><button type="button" class="chipx" data-v="<?= $v ?>" aria-pressed="<?= $v === $places[0][3] ? 'true' : 'false' ?>"><?= $v ?></button><?php endforeach ?></div>
        <span class="ar2q">График работы</span>
        <div class="opts" role="group" aria-label="График работы" data-rc2-days><?php foreach ([22 => 'Пятидневка', 26 => 'Шестидневка', 30 => 'Без выходных'] as $v => $t): ?><button type="button" class="chipx" data-v="<?= $v ?>" aria-pressed="<?= $v === $places[0][4] ? 'true' : 'false' ?>"><?= $e($t) ?></button><?php endforeach ?></div>
        <label class="ar2q" for="rc2b">Кофе</label>
        <select id="rc2b" class="ar2sel" data-rc2-bean><?php foreach ($calc['beans'] as $b): ?><option value="<?= $e($b['code']) ?>"<?= $b['code'] === $bean0 ? ' selected' : '' ?>><?= $e($b['n']) ?> — <?= $e(bt_fmt($b['p'])) ?>/кг</option><?php endforeach ?></select>
      </div>
      <div class="ar2calc__out" aria-live="polite">
        <div class="ar2pick">
          <img src="<?= $e($models[0]['img']) ?>" alt="" width="120" height="96" data-rc2-img>
          <div><span class="mono muted">Подходит</span><h3 data-rc2-name></h3><p data-rc2-s></p></div>
        </div>
        <div class="ar2kpi">
          <div><b data-rc2-cups></b><span>чашек в день</span></div>
          <div><b data-rc2-kg></b><span>кофе в месяц</span></div>
          <div><b data-rc2-cup></b><span>себестоимость чашки</span></div>
        </div>
        <div class="ar2tar">
          <div data-rc2-a><span class="mono">Машина 0 ₽ + наш кофе</span><b data-rc2-at></b><small data-rc2-ad></small></div>
          <div data-rc2-b><span class="mono">Фиксированная аренда</span><b data-rc2-bt></b><small data-rc2-bd></small></div>
        </div>
        <p class="ar2note" data-rc2-note></p>
        <a class="btn" href="#form" data-rc2-go>Отправить расчёт менеджеру</a>
      </div>
    </div>
  </section>
  <?php endif ?>

  <?php if ($models): ?>
  <section class="sec sec--t0" id="models">
    <div class="r2head"><h2 class="display h2">Кофемашины в аренду</h2><a class="link" href="/catalog/professionalnye-kofemashiny/">Хочу купить →</a></div>
    <div class="ar2models"><?php foreach ($models as $m): ?>
      <article class="card ar2m">
        <a class="ar2m__ph" href="<?= $e($m['url']) ?>"><img src="<?= $e($m['img']) ?>" alt="<?= $e($m['model']) ?>" loading="lazy" width="480" height="340"></a>
        <div class="ar2m__b">
          <span class="mono muted"><?= $e($m['audience']) ?><?= $m['cups'] ? ' · до ' . $m['cups'] . ' чашек в день' : '' ?></span>
          <h3><a href="<?= $e($m['url']) ?>"><?= $e($m['model']) ?></a></h3>
          <?php if ($m['feature'] !== ''): ?><span class="tag"><?= $e($m['feature']) ?></span><?php endif ?>
          <div class="ar2m__pr">
            <?php if ($m['kg']): ?><div><b>0 ₽</b><span>при заказе кофе от <?= $m['kg'] ?>&nbsp;кг в месяц</span></div><?php endif ?>
            <div><b><?= $e(bt_fmt($m['price'])) ?></b><span>в месяц, кофе любой</span></div>
          </div>
          <ul class="ar2m__sp"><?php foreach (['SCREEN', 'BEAN_HOPPER', 'WATER_SUPPLY', 'TELEMETRY'] as $c): ?><li><span><?= $e($specCodes[$c]) ?></span><?= $e($spec($m, $c) ?: 'Нет') ?></li><?php endforeach ?></ul>
          <button type="button" class="btn btn--line btn--sm" data-rc2-model="<?= $e($m['id']) ?>">Рассчитать для этой модели</button>
        </div>
      </article>
    <?php endforeach ?></div>
  </section>

  <section class="sec sec--t0" id="compare">
    <h2 class="display h2" style="margin-bottom:22px">Сравнение моделей</h2>
    <div class="tblw"><table class="tbl">
      <thead><tr><th>Параметр</th><?php foreach ($models as $m): ?><th><?= $e($m['model']) ?></th><?php endforeach ?></tr></thead>
      <tbody>
        <tr><td>Для кого</td><?php foreach ($models as $m): ?><td><?= $e($m['audience']) ?></td><?php endforeach ?></tr>
        <tr><td>Нагрузка</td><?php foreach ($models as $m): ?><td><?= $m['cups'] ? 'до ' . $m['cups'] . ' чашек в день' : '—' ?></td><?php endforeach ?></tr>
        <tr><td>Аренда 0 ₽</td><?php foreach ($models as $m): ?><td><?= $m['kg'] ? 'при кофе от ' . $m['kg'] . '&nbsp;кг в месяц' : '—' ?></td><?php endforeach ?></tr>
        <tr><td>Фиксированная аренда</td><?php foreach ($models as $m): ?><td><b><?= $e(bt_fmt($m['price'])) ?>/мес</b></td><?php endforeach ?></tr>
        <?php foreach ($specCodes as $c => $t): if (!array_filter($models, fn($m) => $spec($m, $c) !== '')) continue; ?>
        <tr><td><?= $e($t) ?></td><?php foreach ($models as $m): ?><td><?= $e($spec($m, $c) ?: (in_array($c, ['SCREEN', 'WATER_SUPPLY', 'TELEMETRY', 'MDB'], true) ? 'Нет' : '—')) ?></td><?php endforeach ?></tr>
        <?php endforeach ?>
        <tr><td>Купить</td><?php foreach ($models as $m): ?><td><?= $m['buy'] ? '<a class="link" href="' . $e($m['url']) . '">' . $e(bt_fmt($m['buy'])) . '</a>' : 'по запросу' ?></td><?php endforeach ?></tr>
      </tbody>
    </table></div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0">
    <h2 class="display h2">Что входит в аренду</h2>
    <div class="r2why ar2inc"><?php foreach ($included as [$ic, $t, $d]): ?><div class="card"><span class="ic"><?= bt_icon($ic) ?></span><b><?= $e($t) ?></b><p><?= $e($d) ?></p></div><?php endforeach ?></div>
  </section>

  <section class="sec sec--t0"><div class="pl">
    <div><div class="mono" style="color:var(--lime)"><?= $e($terms['caption']) ?></div>
      <h2 class="display h2" style="margin:14px 0"><?= $e($terms['title']) ?></h2>
      <p><?= $e($terms['sub']) ?></p>
      <div class="row" style="gap:12px"><a class="btn" href="/upload/docs/dogovor-arendy-kofemashiny-obrazec.pdf" target="_blank" rel="noopener" download>Скачать образец договора, PDF</a></div></div>
    <ul class="r2list"><?php foreach ($terms['items'] as $t): ?><li><?= $e($t) ?></li><?php endforeach ?></ul>
  </div></section>

  <?php if ($beans): ?>
  <section class="sec sec--t0" id="coffee">
    <div class="r2head"><h2 class="display h2">Кофе для машины</h2><a class="link" href="/catalog/kofe/">Весь кофе →</a></div>
    <p class="muted r2lead">Свежая обжарка BOTANICA. Цена чашки — при 8 г зерна на порцию, по цене от 5 кг.</p>
    <div class="ar2cf"><?php foreach ($beans as $c): $p5 = $tier($c, 5); ?>
      <a class="card" href="<?= $e($c['url']) ?>">
        <?php if (!empty($c['img'])): ?><img src="<?= $e($c['img']) ?>" alt="" loading="lazy" width="120" height="120"><?php endif ?>
        <b><?= $e(preg_replace('/,\s*1\s*кг$/u', '', $c['n'])) ?></b>
        <?php if (!empty($c['par'])): ?><small><?= $e($c['par']) ?></small><?php endif ?>
        <span class="ar2cf__p"><?= $e(bt_fmt($p5)) ?>/кг · <b><?= $e(bt_fmt(round($p5 * 8 / 1000))) ?></b> за чашку</span>
      </a>
    <?php endforeach ?></div>
  </section>
  <?php endif ?>

  <?php if ($models): ?>
  <section class="sec sec--t0">
    <h2 class="display h2">Аренда или покупка</h2>
    <div class="ar2vs">
      <div class="card"><b>Аренда</b><ul class="ar2pm"><li>0 ₽ вложений на старте</li><li>Обслуживание и ремонт включены</li><li>Подменная машина на время ремонта</li><li>Можно сменить модель под нагрузку</li></ul></div>
      <div class="card"><b>Покупка</b><ul class="ar2pm ar2pm--no"><li>Сразу <?= $e(bt_fmt(min(array_filter(array_column($models, 'buy')) ?: [0]))) ?> и больше</li><li>Обслуживание и ремонт — отдельно</li><li>Простой на время ремонта</li><li>Машина ваша — выгодно при сроке от 1–2 лет</li></ul></div>
    </div>
    <div class="tblw" style="margin-top:14px"><table class="tbl r2tbl">
      <thead><tr><th>Модель</th><th>Покупка</th><th>Аренда за 12 месяцев</th></tr></thead>
      <tbody><?php foreach ($models as $m): if (!$m['buy']) continue; ?><tr><td><?= $e($m['model']) ?></td><td><?= $e(bt_fmt($m['buy'])) ?></td><td><b><?= $m['kg'] ? '0 ₽ с нашим кофе' : '' ?></b><?= $m['kg'] ? ' или ' : '' ?><?= $e(bt_fmt($m['price'] * 12)) ?></td></tr><?php endforeach ?></tbody>
    </table></div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0">
    <h2 class="display h2">Кому подходит аренда</h2>
    <div class="r2types ar2seg"><?php foreach ($segments as [$t, $d, $place]): ?><div class="card"><b><?= $e($t) ?></b><p><?= $e($d) ?></p><button type="button" class="link" data-rc2-goplace="<?= $e($place) ?>">Рассчитать →</button></div><?php endforeach ?></div>
  </section>

  <section class="sec sec--t0">
    <h2 class="display h2">Как взять кофемашину в аренду</h2>
    <div class="r2steps"><?php foreach ($steps as $i => [$t, $d]): ?><div><b>Шаг <?= sprintf('%02d', $i + 1) ?></b><h3><?= $e($t) ?></h3><p><?= $e($d) ?></p></div><?php endforeach ?></div>
  </section>

  <section class="sec sec--t0" id="event"><div class="cta">
    <div><h2 class="display h2">Кофемашина на мероприятие</h2><p>Конференция, выставка, праздник: подберём модель под формат и число гостей, привезём и заберём. Стоимость — по запросу.</p></div>
    <a class="btn btn--dark" href="#form" data-rc2-set="На мероприятие">Заявка на мероприятие</a>
  </div></section>

  <?php if ($revs): ?>
  <section class="sec sec--t0">
    <div class="r2head"><h2 class="display h2">Отзывы клиентов</h2><a class="link" href="/otzyvy-o-nas/">Все отзывы →</a></div>
    <div class="grid g3 revs" data-revs><?php foreach ($revs as $r) echo bt_rev_card($r) ?></div>
  </section>
  <?php endif ?>

  <?php if ($cities): ?>
  <section class="sec sec--t0">
    <h2 class="display h2">Где работаем</h2>
    <p class="muted r2lead">Устанавливаем и обслуживаем кофемашины в Екатеринбурге и городах Свердловской области.</p>
    <div class="r2geo"><?php foreach ($cities as $g): ?><span><?= $e($g) ?></span><?php endforeach ?></div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0" id="form"><form class="card form" data-form="lead" novalidate>
    <div><div class="mono muted">Заявка</div><h2 class="display h2" style="margin:14px 0 10px">Подберём машину под вашу нагрузку</h2>
      <p class="muted" style="margin:0;max-width:40ch">Менеджер перезвонит в течение 5 минут в рабочее время, уточнит место, число чашек и напитки.</p>
      <?php if (!empty($co['phone1'])): ?><p style="margin:18px 0 0"><a class="link" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a></p><?php endif ?></div>
    <div>
      <input type="hidden" name="topic" value="Аренда кофемашины">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="f2"><div class="field"><label>Модель</label><select name="model"><?php foreach ($models as $m): ?><option value="<?= $e($m['model']) ?>"><?= $e($m['model']) ?> — <?= $e(bt_fmt($m['price'])) ?>/мес</option><?php endforeach ?><option value="На мероприятие">На мероприятие</option><option value="Помогите подобрать" selected>Помогите подобрать</option></select></div>
        <div class="field"><label>Компания</label><input name="company" maxlength="150" placeholder="Если оформляем на организацию"></div></div>
      <div class="field"><label>Комментарий</label><textarea name="message" rows="3" maxlength="2000" placeholder="Место установки, число чашек в день, нужные напитки"></textarea></div>
      <?= bt_form_tail('Отправить заявку') ?>
    </div>
  </form></section>

  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:20px">Вопросы об аренде кофемашин</h2>
    <div class="faq"><?php foreach ($faq as $i => [$q, $a]): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q) ?></summary><div class="faq__a"><p><?= $e($a) ?></p></div></details><?php endforeach ?></div>
  </section>

  <section class="sec sec--t0">
    <h2 class="display h2">Аренда кофемашин в Екатеринбурге: как выбрать и сколько стоит</h2>
    <div class="r2seo">
      <h3>Бесплатная аренда при покупке кофе</h3>
      <p>Самый частый формат — кофемашина бесплатно при покупке кофе: вы платите только за зерно, а машина, её установка и обслуживание входят в стоимость. У BEVERTEAM порог начинается от <?= $minKg ?> кг кофе BOTANICA в месяц и зависит от модели. Цена кофе — как в каталоге, с оптовыми скидками от 5 кг.</p>
      <h3>Фиксированная аренда без привязки к кофе</h3>
      <p>Если у вас уже есть поставщик зерна, берите машину с фиксированной оплатой — от <?= $e(bt_fmt($minPrice)) ?> в месяц. Кофе любой, сервисное обслуживание всё равно включено.</p>
      <h3>Как выбрать модель по количеству чашек</h3>
      <p>Считайте не только среднее число чашек в день, но и пик: утро в офисе или обед в кафе. Для дома и небольшого офиса хватит компактной модели, для офиса до 60 чашек в день — Jetinno JL15 VIVA, для кафе и потока гостей до 100 чашек — Jetinno JL36.</p>
      <h3>Аренда кофемашины для офиса</h3>
      <p>Суперавтомат готовит эспрессо, американо, капучино и латте одной кнопкой — сотрудникам не нужно уметь варить кофе. Машины с телеметрией сообщают о нехватке зерна и ошибках, а платёжный терминал позволяет продавать кофе гостям.</p>
      <h3>Аренда кофемашины для кафе</h3>
      <p>Вместо рожковой машины и бариста — автомат, который выдаёт одинаковый вкус в любую смену. Подходит кафе, пекарням, АЗС, автомойкам и шоурумам, где кофе — дополнительная услуга.</p>
      <h3>Сервис авторизованного центра Jetinno</h3>
      <p>BEVERTEAM — авторизованный сервисный центр Jetinno в Екатеринбурге: ремонт с оригинальными запчастями, обслуживание по графику и консультация инженера по телефону.</p>
    </div>
  </section>

  <section class="sec sec--t0">
    <div class="r2more">
      <a class="card" href="/podpiska/"><b>Кофе по подписке</b><span>Регулярная доставка зерна с оптовой ценой</span></a>
      <a class="card" href="/servis/remont-kofemashin/"><b>Ремонт кофемашин</b><span>Если машина уже есть — починим и обслужим</span></a>
      <a class="card" href="/catalog/professionalnye-kofemashiny/"><b>Купить кофемашину</b><span>Jetinno в собственность с гарантией</span></a>
    </div>
  </section>
</div>
