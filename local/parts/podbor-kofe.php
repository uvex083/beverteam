<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
// «Подбор кофе» по макету quiz.html: вопросы и правила подбора — здесь, результат — реальный товар каталога (ui.js, блок квиза)

$e = fn($s) => htmlspecialcharsbx((string)$s);
$q = [
    ['k' => 'dev', 'q' => 'На чём готовите?', 'h' => 'От этого зависит помол и обжарка.', 'o' => [
        ['auto', 'Автоматическая кофемашина', 'зерно засыпается в бункер'], ['horn', 'Рожковая кофеварка', 'темпер, холдер, портафильтр'],
        ['filter', 'Воронка, аэропресс, френч', 'альтернативные способы'], ['turk', 'Турка', 'варю на плите']]],
    ['k' => 'milk', 'q' => 'С молоком или без?', 'h' => 'Под молочные напитки обычно берут более плотный кофе.', 'o' => [
        ['milk', 'Чаще с молоком', 'капучино, латте, флэт уайт'], ['black', 'Чаще без молока', 'эспрессо, американо, фильтр'], ['both', 'И так, и так', 'в офисе пьют по-разному']]],
    ['k' => 'taste', 'q' => 'Какой вкус ближе?', 'h' => '', 'o' => [
        ['choco', 'Шоколад и орехи', 'привычный, без кислинки'], ['fruit', 'Ягоды и цитрус', 'яркий, с кислотностью'], ['balance', 'Сбалансированный', 'чтобы всем подошло']]],
    ['k' => 'vol', 'q' => 'Сколько кофе уходит в месяц?', 'h' => 'Подскажем, с какого объёма включается оптовая цена и бесплатная аренда кофемашины.', 'o' => [
        ['s', 'До 1 кг', 'дом или пара человек'], ['m', '1–5 кг', 'небольшой офис'], ['l', '5–20 кг', 'офис или кафе'], ['xl', 'Больше 20 кг', 'сеть, HoReCa, вендинг']]],
    ['k' => 'price', 'q' => 'Что важнее?', 'h' => '', 'o' => [
        ['qual', 'Вкус', 'готов доплатить за интересный лот'], ['balance2', 'Баланс', 'хороший кофе по разумной цене'], ['cost', 'Цена', 'нужен стабильный рабочий вариант']]],
];
// результат: код товара → почему подходит; товары и цены — из каталога
$res = [
    'botanica-vending' => 'Смесь для вендинга и кофемашин с высокой нагрузкой.',
    'botanica-milk' => 'Купаж BOTANICA для напитков с молоком.',
    'botanica-efiopiya-sidamo-1' => 'Кофе под фильтр и альтернативные способы заваривания.',
    'botanica-efiopiya-oromiya' => 'Сладкий кофе с нотами чёрного чая, сухофруктов, лимона и шоколадным послевкусием.',
    'botanica-braziliya-santos' => 'Классический вкус без экзотики: шоколад, орехи, какао.',
    'botanica-espresso-smes' => 'Универсальная смесь под эспрессо: для автоматической кофемашины и рожка, с молоком и без.',
];
$ids = [];
foreach (bt_catalog_data()['coffee'] as $c) {
    isset($res[$c['code']]) and $ids[$c['code']] = $c['id'];
}
$quiz = ['q' => $q, 'res' => array_map(fn($code) => ['id' => $ids[$code], 'why' => $res[$code]], array_combine(array_keys($ids), array_keys($ids))), 'fallback' => 'botanica-espresso-smes'];
$q0 = $q[0];
?>
<div class="wrap quizp">
  <?php bt_crumbs() ?>
  <div class="pagehead" style="text-align:center"><h1 class="display h1"><?php $APPLICATION->ShowTitle(false) ?></h1><p class="sub" style="margin-left:auto;margin-right:auto;max-width:60ch">Пять вопросов о том, как вы пьёте кофе. Покажем сорт BOTANICA, который подойдёт, — сразу с ценой.</p></div>
  <div class="quiz" id="quiz" data-quiz='<?= $e(json_encode($quiz, JSON_UNESCAPED_UNICODE)) ?>'>
    <div class="quiz__head"><div class="quiz__bar"><i style="width:0%"></i></div><span class="n">1 / <?= count($q) ?></span></div>
    <p class="quiz__q"><?= $e($q0['q']) ?></p><p class="quiz__hint"><?= $e($q0['h']) ?></p>
    <div class="quiz__opts"><?php foreach ($q0['o'] as [$v, $t, $s]): ?><button type="button" data-v="<?= $e($v) ?>"><?= $e($t) ?><small><?= $e($s) ?></small></button><?php endforeach ?></div>
    <div class="quiz__nav"><span></span><span>Осталось <?= count($q) - 1 ?> вопроса</span></div>
  </div>
</div>
