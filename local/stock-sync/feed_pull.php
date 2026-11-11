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
 * Единицы (11.11.2026): часть досок metatool отдаёт в погонных метрах (unit «пог. м»),
 * а на сайте они в штуках. Такие остатки делим на длину из названия
 * («…146x24х3000мм…» → 3 м) и округляем вниз. Не нашли длину — предложение
 * не трогаем и пишем в журнал.
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

// в журнале — московское время, как у index.php (у php из cron по умолчанию UTC)
date_default_timezone_set('Europe/Moscow');

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

// откат обнуления: вернуть последние сохранённые значения из nonfeed_zero.jsonl
if (in_array('--restore', $argv, true)) {
    if (!is_file($logDir . '/nonfeed_zero.off')) {
        exit("Сначала положите logs/nonfeed_zero.off, иначе следующий запуск обнулит снова
");
    }
    $settings = include dirname(__DIR__, 2) . '/bitrix/.settings.php';
    $conn = $settings['connections']['value']['default'] ?? [];
    $pdo = new PDO('mysql:host=' . ($conn['host'] ?? 'localhost') . ';dbname=' . ($conn['database'] ?? '') . ';charset=utf8mb4',
        $conn['login'] ?? '', $conn['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $last = [];
    foreach (file($logDir . '/nonfeed_zero.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $r = json_decode($line, true);
        if (is_array($r) && !isset($last[$r['id']])) {
            $last[$r['id']] = $r; // первая запись = значение до самого первого обнуления
        }
    }
    $setStore = $pdo->prepare('INSERT INTO b_catalog_store_product (PRODUCT_ID, STORE_ID, AMOUNT) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE AMOUNT = VALUES(AMOUNT)');
    $setTotal = $pdo->prepare('UPDATE b_catalog_product SET QUANTITY = ? WHERE ID = ?');
    foreach ($last as $id => $r) {
        foreach (array_filter(explode(',', $r['s'])) as $pair) {
            [$st, $am] = explode(':', $pair);
            $setStore->execute([$id, (int)$st, (float)$am]);
        }
        $setTotal->execute([$r['q'], $id]);
    }
    @touch($logDir . '/cache_dirty.flag');
    exit('Восстановлено: ' . count($last) . "
");
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

/** «пог. м» → «погм», «шт.» → «шт»: единица без пробелов, точек и регистра */
function nd_feed_unit(string $unit): string
{
    return preg_replace('/[\s.]+/u', '', mb_strtolower($unit));
}

/** длина в метрах из названия: «146x24х3000мм» → 3.0; не нашли — null */
function nd_feed_length(string $name): ?float
{
    if (preg_match('/\d+\s*[xх×*]\s*\d+\s*[xх×*]\s*(\d{3,5})\s*(?:мм|$|\s)/ui', $name, $m)) {
        $mm = (int)$m[1];
        return $mm >= 500 ? $mm / 1000 : null;
    }
    return null;
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
$siteUnit = [];
foreach ($pdo->query(
    "SELECT p.ID, p.QUANTITY, m.CODE FROM b_catalog_product p
     LEFT JOIN b_catalog_measure m ON m.ID = p.MEASURE WHERE p.ID IN ($in)"
) as $row) {
    $exists[(int)$row['ID']] = (float)$row['QUANTITY'];
    // штуки = код ОКЕИ 796 (подпись «шт» у Битрикса в языковом файле, в базе пусто);
    // единица не задана — тоже штуки (единица по умолчанию)
    $siteUnit[(int)$row['ID']] = ($row['CODE'] === null || (int)$row['CODE'] === 796) ? 'шт' : (string)$row['CODE'];
}
$names = [];
foreach ($pdo->query("SELECT ID, NAME FROM b_iblock_element WHERE ID IN ($in)") as $row) {
    $names[(int)$row['ID']] = (string)$row['NAME'];
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

    // погонные метры → штуки (см. шапку)
    $div = 1.0;
    if (nd_feed_unit((string)($item['unit'] ?? '')) === 'погм' && ($siteUnit[$id] ?? '') === 'шт') {
        $len = nd_feed_length((string)($item['name_1c'] ?? '')) ?? nd_feed_length($names[$id] ?? '');
        if ($len === null) {
            $line = "Offer {$id} | пог. м, а длину из названия не понять — не трогаю";
            $dry ? print($line . "\n") : $log($line);
            continue;
        }
        $div = $len;
    }
    $conv = function ($q) use ($div) {
        return $div === 1.0 ? (int)$q : (int)floor((int)$q / $div + 1e-9);
    };

    $diff = [];
    foreach ((array)($item['stock'] ?? []) as $row) {
        $store = ND_FEED_STORES[$row['city'] ?? ''] ?? null;
        if ($store === null) {
            continue;
        }
        $qty = $conv($row['quantity'] ?? 0);
        $was = $amounts[$id][$store] ?? null;
        if ($was === null || (float)$was !== (float)$qty) {
            $diff[] = $row['city'] . ' ' . ($was === null ? '—' : $was + 0) . '→' . $qty;
            if (!$dry) {
                $setStore->execute([$id, $store, $qty]);
            }
        }
    }
    $total = $conv($item['stock_total'] ?? 0);
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

// --- 11.11.2026: остаток только у товаров ленты (галочка latitudo.ru в metatool) ---
// Всё остальное в каталоге (простые товары и предложения, TYPE 1 и 4) обнуляем:
// склады и QUANTITY. Распродажу (свой раздел, своя лента nd_sale_sync) не трогаем.
// Защита: в ленте меньше 100 найденных на сайте предложений — не обнуляем ничего.
// Выключить: файл logs/nonfeed_zero.off. Прежние значения — в logs/nonfeed_zero.jsonl
// (откат: php feed_pull.php --restore, сначала положить nonfeed_zero.off).
$zeroed = 0;
if (!is_file($logDir . '/nonfeed_zero.off') && count($feedOffers) >= 100) {
    $keep = implode(',', $feedOffers);
    $sale = (int)$pdo->query("SELECT ID FROM b_iblock_section WHERE CODE = 'rasprodazha' AND IBLOCK_ID = 19")->fetchColumn();
    $saleCond = $sale ? "AND p.ID NOT IN (SELECT IBLOCK_ELEMENT_ID FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID = {$sale})" : '';
    $rows = $pdo->query(
        "SELECT p.ID, p.QUANTITY,
                (SELECT GROUP_CONCAT(CONCAT(sp.STORE_ID, ':', sp.AMOUNT)) FROM b_catalog_store_product sp
                  WHERE sp.PRODUCT_ID = p.ID AND sp.AMOUNT <> 0) AS STORES
           FROM b_catalog_product p
          WHERE p.TYPE IN (1, 4) AND p.ID NOT IN ({$keep}) {$saleCond}
            AND (p.QUANTITY <> 0 OR EXISTS (SELECT 1 FROM b_catalog_store_product sp
                                             WHERE sp.PRODUCT_ID = p.ID AND sp.AMOUNT <> 0))"
    )->fetchAll();
    if ($rows && !$dry) {
        $back = fopen($logDir . '/nonfeed_zero.jsonl', 'a');
        $zStores = $pdo->prepare('UPDATE b_catalog_store_product SET AMOUNT = 0 WHERE PRODUCT_ID = ?');
        $zTotal = $pdo->prepare('UPDATE b_catalog_product SET QUANTITY = 0 WHERE ID = ?');
        foreach ($rows as $row) {
            fwrite($back, json_encode(['t' => date('c'), 'id' => (int)$row['ID'],
                'q' => (float)$row['QUANTITY'], 's' => (string)$row['STORES']]) . "
");
            $zStores->execute([$row['ID']]);
            $zTotal->execute([$row['ID']]);
        }
        fclose($back);
    }
    $zeroed = count($rows);
    if ($dry) {
        foreach (array_slice($rows, 0, 20) as $row) {
            echo "Обнулю {$row['ID']} | всего {$row['QUANTITY']} | склады {$row['STORES']}
";
        }
    }
    if ($zeroed) {
        $changed++;
    }
}

$summary = 'Лента ' . ($feed['stock_updated_at'] ?? '') . ' | предложений ' . count($feed['items'])
    . ' | изменилось ' . $changed
    . ($zeroed ? ' | обнулено не из ленты ' . $zeroed : '')
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
