<?php
/**
 * Остатки из ленты metatool — сайт забирает их сам (05.11.2026, задание metatool).
 *
 * Лента https://metatool.ru/api/feeds/latitudo-ostatki.json всегда ПОЛНАЯ: по каждому
 * предложению (offer_id = ID торгового предложения, у штучного товара — ID товара)
 * все четыре склада, ноль тоже значит «пусто», и stock_total — общий остаток.
 * Остатки уже целые и не отрицательные.
 *
 * Пишем, как и приём толчков (index.php), прямо в b_catalog_store_product и
 * b_catalog_product.QUANTITY — и только то, что поменялось. Список предложений
 * ленты кладём в logs/feed_offers.json: по ним index.php толчки больше не пишет,
 * иначе два источника перезаписывали бы друг друга. Остальные предложения
 * по-прежнему идут через index.php.
 *
 * Ничего не меняем, если лента не ответила, отдала ошибку, count = 0 или
 * stock_updated_at (UTC) старше часа. Предложения не из ленты не трогаем.
 * Раздел skipped — только для сведения.
 *
 * Запуск — cron раз в 5 минут:
 *   php feed_pull.php            — забрать и записать
 *   php feed_pull.php --dry      — показать, что поменялось бы, ничего не писать
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const ND_FEED_URL = 'https://metatool.ru/api/feeds/latitudo-ostatki.json';
const ND_FEED_MAX_AGE = 3600;
// город из ленты => склад сайта (b_catalog_store)
const ND_FEED_STORES = ['Белгород' => 1, 'Воронеж' => 2, 'Москва' => 3, 'Краснодар' => 4];

$dry = in_array('--dry', $argv, true);
$logDir = __DIR__ . '/logs';

$log = function (string $message) use ($logDir) {
    $file = $logDir . '/stock_feed.log';
    if (is_file($file) && filesize($file) > 5 * 1024 * 1024) {
        @rename($file, $logDir . '/stock_feed.' . date('Ymd_His') . '.log');
        $old = glob($logDir . '/stock_feed.*_*.log') ?: [];
        sort($old);
        foreach (array_slice($old, 0, max(0, count($old) - 5)) as $f) {
            @unlink($f);
        }
    }
    @file_put_contents($file, date('Y-m-d H:i:s') . ' | ' . $message . "\n", FILE_APPEND | LOCK_EX);
};

// два запуска разом не нужны: при медленной ленте cron догоняет сам себя
$lock = fopen($logDir . '/feed_pull.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit;
}

// --- лента ---
$ch = curl_init(ND_FEED_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_FOLLOWLOCATION => true,
]);
$body = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($body === false || $code !== 200) {
    $log("Лента не ответила: HTTP {$code} {$err} — ничего не меняю");
    exit(1);
}
$feed = json_decode($body, true);
if (!is_array($feed) || !isset($feed['items']) || !is_array($feed['items'])) {
    $log('Лента: не JSON или нет items — ничего не меняю');
    exit(1);
}
if ((int)($feed['count'] ?? 0) === 0 || !$feed['items']) {
    $log('Лента: count = 0 — ничего не меняю');
    exit(1);
}
$updated = strtotime((string)($feed['stock_updated_at'] ?? ''));
if (!$updated || time() - $updated > ND_FEED_MAX_AGE) {
    $log('Лента: stock_updated_at ' . ($feed['stock_updated_at'] ?? '—') . ' старше часа — ничего не меняю');
    exit(1);
}

// --- база (реквизиты — из настроек Битрикса, как в index.php) ---
$settings = include dirname(__DIR__, 2) . '/bitrix/.settings.php';
$conn = $settings['connections']['value']['default'] ?? [];
$pdo = new PDO(
    'mysql:host=' . ($conn['host'] ?? 'localhost') . ';dbname=' . ($conn['database'] ?? '') . ';charset=utf8mb4',
    $conn['login'] ?? '',
    $conn['password'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$ids = [];
foreach ($feed['items'] as $item) {
    if ((int)($item['offer_id'] ?? 0) > 0) {
        $ids[] = (int)$item['offer_id'];
    }
}
$ids = array_values(array_unique($ids));
$in = implode(',', $ids);

// что сейчас на сайте
$exists = [];
foreach ($pdo->query("SELECT ID, QUANTITY FROM b_catalog_product WHERE ID IN ($in)") as $row) {
    $exists[(int)$row['ID']] = (float)$row['QUANTITY'];
}
$amounts = [];
foreach ($pdo->query("SELECT PRODUCT_ID, STORE_ID, AMOUNT FROM b_catalog_store_product WHERE PRODUCT_ID IN ($in)") as $row) {
    $amounts[(int)$row['PRODUCT_ID']][(int)$row['STORE_ID']] = (float)$row['AMOUNT'];
}

$setStore = $pdo->prepare(
    'INSERT INTO b_catalog_store_product (PRODUCT_ID, STORE_ID, AMOUNT) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE AMOUNT = VALUES(AMOUNT)'
);
$setTotal = $pdo->prepare('UPDATE b_catalog_product SET QUANTITY = ? WHERE ID = ?');

$changed = 0;
$missing = [];
$feedOffers = [];
foreach ($feed['items'] as $item) {
    $id = (int)($item['offer_id'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    if (!isset($exists[$id])) {
        $missing[] = $id;
        continue;
    }
    $feedOffers[] = $id;

    $diff = [];
    foreach ((array)($item['stock'] ?? []) as $row) {
        $store = ND_FEED_STORES[$row['city'] ?? ''] ?? null;
        if ($store === null) {
            continue;
        }
        $qty = (int)($row['quantity'] ?? 0);
        $was = $amounts[$id][$store] ?? null;
        if ($was === null || (float)$was !== (float)$qty) {
            $diff[] = $row['city'] . ' ' . ($was === null ? '—' : $was + 0) . '→' . $qty;
            if (!$dry) {
                $setStore->execute([$id, $store, $qty]);
            }
        }
    }
    $total = (int)($item['stock_total'] ?? 0);
    if ($exists[$id] !== (float)$total) {
        $diff[] = 'всего ' . ($exists[$id] + 0) . '→' . $total;
        if (!$dry) {
            $setTotal->execute([$total, $id]);
        }
    }
    if ($diff) {
        $changed++;
        $line = "Offer {$id} | " . implode(', ', $diff);
        $dry ? print($line . "\n") : $log($line);
    }
}

$summary = 'Лента ' . ($feed['stock_updated_at'] ?? '') . ' | предложений ' . count($feed['items'])
    . ' | изменилось ' . $changed
    . ($missing ? ' | нет на сайте: ' . implode(',', $missing) : '')
    . ' | skipped ' . count((array)($feed['skipped'] ?? []));
if ($dry) {
    echo $summary . " (прогон без записи)\n";
    exit;
}

// по этим предложениям толчки в index.php больше не пишут
file_put_contents($logDir . '/feed_offers.json', json_encode($feedOffers), LOCK_EX);
if ($changed) {
    // кеш каталога сбрасывает сайт по этой отметке (ndStockCacheFlush)
    @touch($logDir . '/cache_dirty.flag');
}
$log($summary);
