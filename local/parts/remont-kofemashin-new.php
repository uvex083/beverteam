<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// черновик «Ремонт кофемашин» по структуре лидеров выдачи; цены, сроки и гарантия — заготовки до подтверждения клиентом

$e = fn($s) => preg_replace(['/(\d) (\d{3})/u', '/ (₽)/u'], ["$1\u{00A0}$2", "\u{00A0}$1"], htmlspecialcharsbx((string)$s));
$co = bt_contacts();
$strip = array_column(bt_list('repair_strip'), 'icon');
$icons = [$strip[0] ?? '', bt_icon('star'), bt_icon('box'), bt_icon('repeat'), bt_icon('doc'), $strip[2] ?? ''];

$hero = ['Кофемашины всех марок: для дома, офиса и HoReCa', 'Авторизованный сервисный центр Jetinno', 'Подменная кофемашина на время ремонта', 'Договор, оплата по счёту, закрывающие документы'];
$facts = [['с 2010', 'работаем с кофемашинами в Екатеринбурге'], ['Все марки', 'бытовые, профессиональные и вендинговые кофемашины'], ['Jetinno', 'авторизованный сервисный центр'], ['до 6 мес.', 'гарантия на работы и запчасти']];

$symptoms = [
    ['Не наливает кофе или течёт мимо чашки', 'Засор заварочного узла, износ уплотнений, сбой дозирования', 'от 1 500 ₽'],
    ['Кофе холодный или чуть тёплый', 'Накипь в бойлере, ТЭН, термостат или датчик температуры', 'от 2 000 ₽'],
    ['Не взбивает молоко, слабая пенка', 'Засор молочной линии, насос или капучинатор', 'от 1 500 ₽'],
    ['Течёт вода под машину', 'Трубки, уплотнения, клапаны, трещина в гидросистеме', 'от 1 500 ₽'],
    ['Не мелет зерно или мелет с треском', 'Износ жерновов, посторонний предмет, мотор кофемолки', 'от 2 500 ₽'],
    ['Шумит помпа, слабый напор', 'Накипь, воздух в системе, износ помпы, флоуметр', 'от 2 000 ₽'],
    ['Горит ошибка и машина не готовит', 'Датчики, заварочный блок, плата управления, прошивка', 'от 1 500 ₽'],
    ['Зависает или не реагирует экран', 'Сбой ПО, переполнена память, шлейф или сенсор', 'от 2 000 ₽'],
    ['Не проходит оплата картой или QR', 'Связь, настройки платёжного модуля, терминал', 'от 1 500 ₽'],
];

$price = [
    ['Диагностика', 'в день выезда', '0 ₽ при ремонте · 1 500 ₽ при отказе'],
    ['Выезд инженера по Екатеринбургу', 'в день обращения или на следующий', '0 ₽ при ремонте · 1 000 ₽'],
    ['Выезд по Свердловской области', 'по согласованию', 'по километражу'],
    ['Плановое ТО домашней кофемашины', '1–2 часа', 'от 2 500 ₽'],
    ['Плановое ТО офисной или профессиональной кофемашины', '1–2 часа', 'от 3 500 ₽'],
    ['Плановое ТО кофейни самообслуживания, вендингового автомата', '2–3 часа', 'от 5 500 ₽'],
    ['Удаление накипи (декальцинация)', '1–2 часа', 'от 2 000 ₽'],
    ['Чистка и дезинфекция молочной системы', '1 час', 'от 2 000 ₽'],
    ['Ремонт или замена заварочного узла', '1 день', 'от 2 500 ₽'],
    ['Ремонт капучинатора', '1 день', 'от 1 500 ₽'],
    ['Замена помпы', '1 день', 'от 2 500 ₽'],
    ['Ремонт бойлера, замена ТЭНа или термостата', '1–2 дня', 'от 3 000 ₽'],
    ['Замена флоуметра, электромагнитного клапана', '1 день', 'от 2 000 ₽'],
    ['Ремонт кофемолки, замена жерновов', '1–2 дня', 'от 3 000 ₽'],
    ['Ремонт платы управления', '2–5 дней', 'от 4 000 ₽'],
    ['Обновление ПО, ремонт сенсорного экрана', '1 день', 'от 2 000 ₽'],
    ['Ремонт рожковой кофемашины: группа, клапаны, манометр', '1–2 дня', 'от 3 000 ₽'],
    ['Настройка платёжного модуля и эквайринга', '1 день', 'от 1 500 ₽'],
];

$errors = [
    ['«Нет воды», «Наполните ёмкость»', 'Долейте воду и поставьте бак до щелчка. При подключении к водопроводу проверьте кран и фильтр.', 'Бак полный, а сообщение не уходит — флоуметр или клапан.'],
    ['«Опустошите контейнер для отходов»', 'Опустошите контейнер жмыха и поддон, вставьте до упора.', 'После очистки ошибка осталась — датчик контейнера.'],
    ['«Нет зёрен», «Добавьте кофе»', 'Досыпьте зерно, разровняйте, проверьте, что заслонка бункера открыта.', 'Зерно есть, а кофемолка гудит вхолостую — жернова или мотор.'],
    ['Ошибка заварочного блока', 'Выключите машину на минуту, промойте блок, если он съёмный, включите снова.', 'Повторяется после перезагрузки — механизм блока или датчик положения.'],
    ['Требуется очистка или декальцинация', 'Запустите программу очистки из меню, используйте средство для кофемашин.', 'После программы слабый напор или нет нагрева — нужна профчистка.'],
    ['Экран завис, не реагирует на касания', 'Выключите питание на 1–2 минуты и включите снова.', 'Зависает регулярно — обновление ПО или замена сенсора.'],
];

$steps = [
    ['Заявка', 'Звонок или форма на сайте. Отметьте симптомы и модель — инженер приедет с нужными запчастями.'],
    ['Консультация', 'Часть проблем решаем по телефону: подскажем, что проверить и как перезагрузить машину.'],
    ['Выезд и диагностика', 'Инженер находит причину на месте и называет точную стоимость до начала работ.'],
    ['Ремонт', 'Большинство неисправностей устраняем за один выезд. Сложный ремонт — в сервисе, на это время ставим подменную машину.'],
    ['Проверка и документы', 'Тестовые напитки при вас, акт выполненных работ, гарантия. Юрлицам — счёт и закрывающие документы.'],
];

$why = [
    ['Чиним почти всё', 'Автоматические, рожковые, капсульные и вендинговые кофемашины — от домашней De’Longhi до кофейни самообслуживания.'],
    ['Авторизованный сервис Jetinno', 'Работаем по регламентам производителя: продаём, сдаём в аренду и обслуживаем Jetinno каждый день.'],
    ['Запчасти в наличии', 'Ходовые узлы держим на складе в Екатеринбурге, редкие заказываем у поставщиков. Для Jetinno — оригинальные.'],
    ['Подменная кофемашина', 'Если ремонт займёт больше дня, ставим машину из арендного парка — офис и кафе без кофе не останутся.'],
    ['Работаем с юрлицами', 'Договор, оплата по счёту, акты и закрывающие документы. Можно заключить договор на регулярное обслуживание.'],
    ['Честная смета', 'Цену называем после диагностики и до ремонта. Ремонт нецелесообразен — так и скажем.'],
];

$contract = ['Плановое ТО по графику — раз в 1–3 месяца, в зависимости от нагрузки', 'Приоритетный выезд инженера при поломке', 'Подменная кофемашина на время ремонта', 'Фиксированная цена обслуживания в месяц', 'Журнал обслуживания и отчёт после каждого визита', 'Скидка на кофе BOTANICA для офиса'];

$geo = ['Екатеринбург', 'Верхняя Пышма', 'Берёзовский', 'Среднеуральск', 'Арамиль', 'Сысерть', 'Первоуральск', 'Ревда', 'Полевской', 'Асбест', 'Каменск-Уральский', 'Нижний Тагил'];

$districts = ['Центр', 'ВИЗ', 'Юго-Западный', 'Академический', 'Уралмаш', 'Эльмаш', 'Пионерский', 'Втузгородок', 'Ботанический', 'Химмаш', 'Сортировка', 'Заречный'];
$contractDoc = is_file($_SERVER['DOCUMENT_ROOT'] . '/upload/docs/obrazec-dogovora-servis.pdf') ? '/upload/docs/obrazec-dogovora-servis.pdf' : '';

$faq = [
    ['Какие кофемашины вы ремонтируете?', 'Почти любые: автоматические для дома и офиса, профессиональные рожковые, капсульные, вендинговые автоматы и кофейни самообслуживания. De’Longhi, Saeco, Philips, Jura, Melitta, Bosch, Krups, Nivona, WMF, Franke, Jetinno и другие марки. Не нашли свою — позвоните.'],
    ['Вы официальный сервис Jetinno?', 'Да, BEVERTEAM — авторизованный сервисный центр Jetinno в Екатеринбурге. Мы продаём и сдаём эти кофемашины в аренду и обслуживаем их по регламентам производителя: JL05, JL15 VIVA, JL03, BRAVO, JL32, JL33, JL36.'],
    ['Домашнюю кофемашину можно привезти самим?', 'Да, привозите в сервис на Колокольной, 31А — диагностируем и позвоним со сметой. Либо вызовите инженера на дом.'],
    ['Сколько стоит ремонт?', 'Зависит от неисправности: ориентиры — в прайсе выше. Точную стоимость инженер называет после диагностики, до начала работ. Если ремонтируем у нас, диагностика бесплатная.'],
    ['Как быстро приедет инженер?', 'По Екатеринбургу — в день обращения или на следующий рабочий день. Выезд инженера — Пн–Пт с 9:00 до 17:00. По области — по согласованию.'],
    ['Сколько длится ремонт?', 'Большинство поломок устраняем за один выезд за 1–3 часа. Ремонт платы или бойлера занимает 1–5 дней — на это время можем поставить подменную машину.'],
    ['Какая гарантия?', 'Гарантия на выполненные работы и установленные запчасти — до 6 месяцев, срок указываем в акте.'],
    ['Какие запчасти ставите?', 'Оригинальные или проверенные совместимые — на выбор, разницу в цене и сроке называем заранее. Для Jetinno — только оригинальные.'],
    ['Можно ли заключить договор на обслуживание?', 'Да. Для офисов, кафе и точек самообслуживания заключаем договор на плановое ТО с фиксированной ценой, приоритетным выездом и подменной машиной.'],
    ['Работаете с юрлицами?', 'Да, работаем с ИП и организациями по договору, оплата по счёту, выдаём акты и закрывающие документы.'],
    ['Машина куплена не у вас — возьмёте?', 'Конечно. Ремонтируем кофемашины независимо от того, где и когда их покупали, в том числе старые модели.'],
    ['Когда ремонт нецелесообразен?', 'Если стоимость ремонта приближается к цене новой машины. В этом случае скажем об этом сразу и предложим замену или аренду.'],
    ['Как часто нужно ТО?', 'Домашней кофемашине — раз в год. Офисной при 30–50 чашках в день — раз в 2–3 месяца, кофейне самообслуживания — ежемесячно. Регулярная чистка и декальцинация продлевают жизнь бойлера и помпы.'],
];

$types = [
    ['Домашние', 'Автоматические, рожковые и капсульные кофемашины. Выезд на дом или приём в сервисе.'],
    ['Офисные и профессиональные', 'Суперавтоматы для офиса, рожковые машины кафе и ресторанов, кофемолки.'],
    ['Вендинг и самообслуживание', 'Кофейные автоматы и кофейни самообслуживания, платёжные модули и эквайринг.'],
];
$brands = ['Jetinno', 'De’Longhi', 'Saeco', 'Philips', 'Jura', 'Melitta', 'Bosch', 'Siemens', 'Krups', 'Nivona', 'Gaggia', 'WMF', 'Franke', 'Schaerer', 'La Cimbali', 'Nuova Simonelli', 'Rancilio', 'Necta', 'Bianchi', 'Dr.Coffee'];
$models = array_values(array_filter(bt_catalog_data()['machines'] ?? [], fn($m) => !empty($m['img'])));
$revs = array_values(array_filter(bt_reviews(), fn($r) => preg_match('/ремонт|сервис|почин|диагност|кофемашин/ui', $r['text'])));

$num = fn($t) => preg_match('/(\d[\d\x{00A0} ]*)\s*₽/u', $t, $m) ? (int)preg_replace('/\D/', '', $m[1]) : null;
$offer = fn($name, $p, $desc = '') => array_filter(['@type' => 'Offer', 'name' => $name, 'description' => $desc ?: null, 'priceCurrency' => 'RUB',
    'priceSpecification' => $num($p) !== null ? ['@type' => 'PriceSpecification', 'minPrice' => $num($p), 'priceCurrency' => 'RUB'] : null,
    'itemOffered' => ['@type' => 'Service', 'name' => $name]], fn($v) => $v !== null);
$page = 'https://beverteam.ru/servis/remont-kofemashin/';
$org = ['@id' => 'https://beverteam.ru/#org'];
$area = array_merge(array_map(fn($g) => ['@type' => 'City', 'name' => $g], $geo), [['@type' => 'AdministrativeArea', 'name' => 'Свердловская область']]);
$ld = ['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'WebPage', '@id' => $page . '#page', 'url' => $page, 'name' => 'Ремонт кофемашин в Екатеринбурге', 'inLanguage' => 'ru', 'about' => ['@id' => $page . '#service'], 'publisher' => $org],
    ['@type' => 'Service', '@id' => $page . '#service', 'name' => 'Ремонт кофемашин в Екатеринбурге', 'serviceType' => 'Ремонт и обслуживание кофемашин',
        'description' => 'Ремонт и плановое обслуживание домашних, офисных, профессиональных и вендинговых кофемашин всех марок. Авторизованный сервисный центр Jetinno.',
        'provider' => $org, 'areaServed' => $area, 'brand' => array_map(fn($b) => ['@type' => 'Brand', 'name' => $b], $brands),
        'audience' => [['@type' => 'BusinessAudience', 'audienceType' => 'Офисы, кафе, рестораны, вендинг'], ['@type' => 'PeopleAudience', 'audienceType' => 'Владельцы домашних кофемашин']],
        'category' => array_column($types, 0),
        'hoursAvailable' => ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '09:00', 'closes' => '17:00'],
        'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => 'Цены на ремонт и обслуживание кофемашин', 'itemListElement' => [
            ['@type' => 'OfferCatalog', 'name' => 'Прайс', 'itemListElement' => array_map(fn($p) => $offer($p[0], $p[2], 'Срок: ' . $p[1]), $price)],
            ['@type' => 'OfferCatalog', 'name' => 'Ремонт по неисправностям', 'itemListElement' => array_map(fn($p) => $offer($p[0], $p[2], $p[1]), $symptoms)],
            ['@type' => 'OfferCatalog', 'name' => 'Сервисный договор на обслуживание', 'itemListElement' => [$offer('Сервисный договор на обслуживание кофемашин', '', implode('; ', $contract))]],
        ]]],
    ['@type' => 'HowTo', '@id' => $page . '#steps', 'name' => 'Как проходит ремонт кофемашины', 'totalTime' => 'P1D',
        'step' => array_map(fn($i, $st) => ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => $st[0], 'text' => $st[1]], array_keys($steps), $steps)],
    ['@type' => 'ItemList', '@id' => $page . '#errors', 'name' => 'Ошибки на экране кофемашины: что делать',
        'itemListElement' => array_map(fn($i, $er) => ['@type' => 'ListItem', 'position' => $i + 1, 'item' => ['@type' => 'HowTo', 'name' => 'Что делать: ' . trim($er[0], '«»'),
            'step' => [['@type' => 'HowToStep', 'name' => 'Самостоятельно', 'text' => $er[1]], ['@type' => 'HowToStep', 'name' => 'Когда вызвать инженера', 'text' => $er[2]]]]], array_keys($errors), $errors)],
    ['@type' => 'ItemList', '@id' => $page . '#types', 'name' => 'Какие кофемашины ремонтируем',
        'itemListElement' => array_map(fn($i, $t) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $t[0], 'description' => $t[1]], array_keys($types), $types)],
    ['@type' => 'FAQPage', '@id' => $page . '#faq', 'mainEntity' => array_merge(
        array_map(fn($q) => ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]], $faq),
        array_map(fn($er) => ['@type' => 'Question', 'name' => 'Кофемашина пишет ' . $er[0] . ' — что делать?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $er[1] . ' ' . $er[2]]], $errors))],
]];
$APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>');
?>
<div class="wrap repp rep2">
  <?php bt_crumbs() ?>

  <div class="r2hero">
    <div>
      <div class="mono muted">Сервисный центр · Екатеринбург и область</div>
      <h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1>
      <p class="sub">Ремонтируем и обслуживаем кофемашины любых марок — для дома, офиса, кафе и вендинга. Выезжаем по Екатеринбургу и области или принимаем в сервисе. Авторизованный сервисный центр Jetinno.</p>
      <ul class="r2chk"><?php foreach ($hero as $h): ?><li><?= $e($h) ?></li><?php endforeach ?></ul>
      <div class="row" style="gap:12px">
        <a class="btn" href="#form">Вызвать инженера</a>
        <?php if (!empty($co['phone1'])): ?><a class="btn btn--line" href="<?= $e($co['phone1_href']) ?>"><?= $e($co['phone1']) ?></a><?php endif ?>
      </div>
      <p class="muted r2note">Выезд инженера Пн–Пт <?= $e(preg_replace('/^[^0-9]*/u', '', $co['hours_service'] ?? '')) ?> · ответим за 5 минут в рабочее время</p>
    </div>
    <div class="r2facts"><?php foreach ($facts as [$b, $t]): ?><div><b><?= $e($b) ?></b><span><?= $e($t) ?></span></div><?php endforeach ?></div>
  </div>

  <section class="sec sec--t0" id="symptoms">
    <h2 class="display h2">Что случилось с кофемашиной?</h2>
    <p class="muted r2lead">Отметьте симптомы — они попадут в заявку, инженер приедет с нужными запчастями. Цена — ориентир, точную назовём после диагностики.</p>
    <div class="r2symp" data-symp><?php foreach ($symptoms as [$s, $why_, $p]): ?>
      <label><input type="checkbox" value="<?= $e($s) ?>"><span><b><?= $e($s) ?></b><small><?= $e($why_) ?></small><i><?= $e($p) ?></i></span></label>
    <?php endforeach ?></div>
  </section>

  <section class="sec sec--t0" id="types">
    <h2 class="display h2">Какие кофемашины ремонтируем</h2>
    <p class="muted r2lead">Почти любые — от домашней капсульной до кофейни самообслуживания. Не нашли свою марку — позвоните.</p>
    <div class="r2types"><?php foreach ($types as [$t, $d]): ?><div class="card"><b><?= $e($t) ?></b><p><?= $e($d) ?></p></div><?php endforeach ?></div>
    <div class="r2geo r2brands"><?php foreach ($brands as $b): ?><span><?= $e($b) ?></span><?php endforeach ?></div>
  </section>

  <?php if ($models): ?>
  <section class="sec sec--t0" id="models">
    <h2 class="display h2">Авторизованный сервис Jetinno</h2>
    <p class="muted r2lead">Продаём, сдаём в аренду и обслуживаем Jetinno по регламентам производителя, запчасти — оригинальные. Также JL03, JL33, BRAVO и другие модели линейки.</p>
    <div class="r2models"><?php foreach ($models as $m): ?>
      <a href="<?= $e($m['url']) ?>"><img src="<?= $e($m['img']) ?>" alt="<?= $e($m['n']) ?>" loading="lazy" width="240" height="170"><b><?= $e(preg_replace('/^Кофемашина\s+/u', 'Ремонт ', $m['n'])) ?></b></a>
    <?php endforeach ?></div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0" id="price">
    <div class="r2head"><h2 class="display h2">Цены на ремонт и обслуживание</h2><a class="link" href="#form">Узнать точную стоимость →</a></div>
    <div class="tblw"><table class="tbl r2tbl">
      <thead><tr><th>Услуга</th><th>Срок</th><th>Стоимость</th></tr></thead>
      <tbody><?php foreach ($price as [$t, $d, $p]): ?><tr><td><?= $e($t) ?></td><td class="muted"><?= $e($d) ?></td><td><b><?= $e($p) ?></b></td></tr><?php endforeach ?></tbody>
    </table></div>
    <p class="muted r2foot">Цены без стоимости запчастей. Окончательную смету согласуем до начала работ.</p>
  </section>

  <section class="sec sec--t0"><div class="pl">
    <div><div class="mono" style="color:var(--lime)">Для офисов, кафе и HoReCa</div>
      <h2 class="display h2" style="margin:14px 0">Сервисный договор на&nbsp;обслуживание</h2>
      <p>Кофемашина не ломается внезапно, если за ней следят. Берём обслуживание на себя — вы платите фиксированную сумму и не думаете о технике.</p>
      <div class="row" style="gap:12px"><a class="btn" href="#form">Рассчитать договор</a>
        <?php if ($contractDoc): ?><a class="btn btn--line btn--inv" href="<?= $e($contractDoc) ?>" target="_blank" rel="noopener">Образец договора, PDF</a><?php else: ?><a class="btn btn--line btn--inv" href="#form">Запросить образец договора</a><?php endif ?></div></div>
    <ul class="r2list"><?php foreach ($contract as $c): ?><li><?= $e($c) ?></li><?php endforeach ?></ul>
  </div></section>

  <section class="sec sec--t0" id="errors">
    <h2 class="display h2">Ошибка на экране кофемашины: что делать</h2>
    <p class="muted r2lead">Часть ошибок можно убрать самостоятельно за пару минут. Если не получилось — вызывайте инженера.</p>
    <div class="r2err"><?php foreach ($errors as [$m, $self, $eng]): ?>
      <div class="card"><b><?= $e($m) ?></b><p><span class="mono">Сами</span><?= $e($self) ?></p><p><span class="mono">Инженер</span><?= $e($eng) ?></p></div>
    <?php endforeach ?></div>
  </section>

  <section class="sec sec--t0">
    <h2 class="display h2">Как проходит ремонт</h2>
    <div class="r2steps"><?php foreach ($steps as $i => [$t, $d]): ?><div><b>Шаг <?= sprintf('%02d', $i + 1) ?></b><h3><?= $e($t) ?></h3><p><?= $e($d) ?></p></div><?php endforeach ?></div>
  </section>

  <section class="sec sec--t0">
    <h2 class="display h2">Почему доверяют BEVERTEAM</h2>
    <div class="r2why"><?php foreach ($why as $i => [$t, $d]): ?><div class="card"><?php if (!empty($icons[$i])): ?><span class="ic"><?= $icons[$i] ?></span><?php endif ?><b><?= $e($t) ?></b><p><?= $e($d) ?></p></div><?php endforeach ?></div>
  </section>

  <?php if ($revs): ?>
  <section class="sec sec--t0">
    <div class="r2head"><h2 class="display h2">Отзывы о сервисе</h2><a class="link" href="/otzyvy-o-nas/">Все отзывы →</a></div>
    <div class="grid g3 revs"><?php foreach (array_slice($revs, 0, 3) as $r) echo bt_rev_card($r) ?></div>
  </section>
  <?php endif ?>

  <section class="sec sec--t0">
    <h2 class="display h2">Где работаем</h2>
    <p class="muted r2lead">Выезжаем по Екатеринбургу и Свердловской области. Нет вашего города — позвоните, согласуем выезд.</p>
    <div class="r2geo"><?php foreach ($geo as $g): ?><span><?= $e($g) ?></span><?php endforeach ?></div>
    <p class="muted r2lead" style="margin:18px 0 10px">Районы Екатеринбурга — выезд без доплаты:</p>
    <div class="r2geo r2dist"><?php foreach ($districts as $d): ?><span><?= $e($d) ?></span><?php endforeach ?></div>
  </section>

  <section class="sec sec--t0" id="form"><form class="card form" data-form="lead" novalidate>
    <div><div class="mono muted">Заявка</div><h2 class="display h2" style="margin:14px 0 10px">Вызвать инженера</h2>
      <p class="muted" style="margin:0;max-width:40ch">Перезвоним в течение 5 минут в рабочее время, уточним симптомы и согласуем время выезда.</p>
      <p style="margin:18px 0 0"><a class="link" href="<?= $e($co['phone1_href'] ?? '') ?>"><?= $e($co['phone1'] ?? '') ?></a></p></div>
    <div>
      <input type="hidden" name="topic" value="Вызов инженера: ремонт кофемашины">
      <div class="f2"><div class="field"><label>Имя *</label><input name="name" placeholder="Как к вам обращаться" maxlength="100"></div><div class="field"><label>Телефон *</label><input name="phone" placeholder="+7 ___ ___-__-__"></div></div>
      <div class="field"><label>Модель кофемашины</label><input name="model" maxlength="150" placeholder="Например, De’Longhi ECAM 22 или Jetinno JL15"></div>
      <div class="field"><label>Что случилось</label><textarea name="message" rows="3" maxlength="2000" data-symp-to placeholder="Опишите проблему или отметьте симптомы выше"></textarea></div>
      <?= bt_form_tail('Вызвать инженера') ?>
    </div>
  </form></section>

  <section class="sec sec--t0">
    <h2 class="display h2" style="margin-bottom:20px">Вопросы о ремонте</h2>
    <div class="faq"><?php foreach ($faq as $i => [$q, $a]): ?><details<?= $i ? '' : ' open' ?>><summary><?= $e($q) ?></summary><div class="faq__a"><p><?= $e($a) ?></p></div></details><?php endforeach ?></div>
  </section>

  <section class="sec sec--t0 r2text">
    <h2 class="display h2">Сервисный центр по ремонту кофемашин в Екатеринбурге</h2>
    <div class="r2cols">
      <div><h3>Ремонт кофемашин на дому и в офисе</h3>
        <p>Инженер BEVERTEAM приезжает с диагностическим оборудованием и ходовыми запчастями, поэтому большинство поломок устраняем за один визит. Домашнюю кофемашину можно привезти в сервис на Колокольной, 31А — после диагностики позвоним и согласуем смету.</p></div>
      <div><h3>Ремонт кофемашин De’Longhi, Saeco, Philips, Jura</h3>
        <p>Чиним автоматические, рожковые и капсульные кофемашины популярных марок: De’Longhi, Saeco, Philips, Jura, Melitta, Bosch, Siemens, Krups, Nivona, Gaggia. Для профессиональных машин — WMF, Franke, Schaerer, La Cimbali, Nuova Simonelli, Rancilio; для вендинга — Necta, Bianchi, Jetinno.</p></div>
      <div><h3>Чистка от накипи и плановое ТО</h3>
        <p>Накипь и кофейные масла — причина большинства поломок: холодный кофе, слабый напор, течи. Регулярная декальцинация, чистка заварочного блока и молочной системы продлевают жизнь бойлеру и помпе и обходятся дешевле ремонта.</p></div>
      <div><h3>Цены на ремонт кофемашин</h3>
        <p>Стоимость зависит от марки и неисправности — ориентиры в прайсе выше. Диагностика бесплатна, если ремонт делаем мы. Точную цену инженер называет до начала работ, после ремонта выдаём акт и гарантию.</p></div>
    </div>
  </section>

  <section class="sec sec--t0">
    <div class="r2more">
      <a class="card" href="/arenda-kofemashin/"><b>Аренда кофемашин Jetinno</b><span>Для офиса и кафе, обслуживание включено</span></a>
      <a class="card" href="/podpiska/"><b>Кофе по подписке</b><span>Кофемашина бесплатно при регулярных поставках</span></a>
      <a class="card" href="/catalog/professionalnye-kofemashiny/"><b>Купить кофемашину</b><span>Jetinno с установкой и сервисом</span></a>
    </div>
  </section>
</div>
