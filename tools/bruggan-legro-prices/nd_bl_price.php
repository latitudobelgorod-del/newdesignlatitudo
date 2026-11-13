<?php
// Цены Bruggan и Legro по плану bl_new_prices.json (прайс WPDecking 2026 × 98 ₽/€, 13.11.2026). php nd_bl_price.php dry|run|rollback [--ids=27055,...]
$_SERVER['DOCUMENT_ROOT'] = getenv('HOME') . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true); define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('catalog');
$dir = __DIR__; $mode = $argv[1] ?? 'dry'; $ids = null;
foreach ($argv as $a) if (strpos($a, '--ids=') === 0) $ids = array_map('intval', explode(',', substr($a, 6)));
$bak = "$dir/prices_before.json";
if ($mode === 'rollback') {
    foreach (json_decode(file_get_contents($bak), true) as $r) {
        CPrice::Update($r['priceid'], ['PRICE' => $r['old']]);
        echo "{$r['id']} -> {$r['old']}\n";
    }
} else {
    $plan = json_decode(file_get_contents("$dir/bl_new_prices.json"), true);
    $done = file_exists($bak) ? json_decode(file_get_contents($bak), true) : [];
    foreach ($plan as $p) {
        if ($ids && !in_array((int)$p['id'], $ids, true)) continue;
        $cur = CPrice::GetByID($p['priceid']);
        if (!$cur || (int)$cur['PRODUCT_ID'] !== (int)$p['id']) { echo "{$p['id']}: priceid не тот, пропуск\n"; continue; }
        if (abs($cur['PRICE'] - $p['old']) >= 1) { echo "{$p['id']}: сейчас {$cur['PRICE']}, ждали {$p['old']} — пропуск\n"; continue; }
        echo "{$p['id']} {$p['name']}: {$cur['PRICE']} -> {$p['new']}\n";
        if ($mode === 'run') {
            $done[] = ['id' => $p['id'], 'priceid' => $p['priceid'], 'old' => (float)$cur['PRICE']];
            CPrice::Update($p['priceid'], ['PRICE' => $p['new']]);
        }
    }
    if ($mode === 'run') file_put_contents($bak, json_encode($done, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
if ($mode !== 'dry') { CIBlock::clearIblockTagCache(19); CIBlock::clearIblockTagCache(20); }
