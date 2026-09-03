<?php
/**
 * Stock Sync Configuration — ОБРАЗЕЦ.
 *
 * Реальный config.php лежит ВНЕ Git: скопировать этот файл и подставить токен.
 *
 *     cp config.sample.php config.php
 *
 * Прежний токен (lat1tud0_st0ck_sync_2026) лежал прямо здесь и в SetEnv внутри
 * .htaccess, то есть попал в публичный репозиторий — поэтому он сменён
 * (3 сентября 2026). Реквизиты БД по-прежнему берутся из настроек Битрикса.
 */

// --- Auth ---
// Боевой токен лежит в config.php, который ВНЕ Git. Здесь только заглушка.
define('STOCK_SYNC_TOKEN', getenv('STOCK_SYNC_TOKEN') ?: 'ПОДСТАВЬ_СВОЙ_ТОКЕН');

// Прежний токен — принимается ВРЕМЕННО, пока его не сменят на стороне 1С.
// Как только обмен переедет на новый, эту строку убрать.
define('STOCK_SYNC_TOKEN_OLD', '');

// --- IP whitelist ---
$ALLOWED_IPS = ['94.25.96.22', '82.151.114.182'];

// --- Database ---
// Leave empty to auto-detect from Bitrix (.settings.php / dbconn.php)
define('STOCK_SYNC_DB_HOST', '');
define('STOCK_SYNC_DB_NAME', '');
define('STOCK_SYNC_DB_USER', '');
define('STOCK_SYNC_DB_PASSWORD', '');

// --- Warehouse mapping ---
// Maps warehouse identifiers from 1C to Bitrix store IDs
$WAREHOUSE_MAP = [
    // Numeric IDs (recommended — no encoding issues)
    '1' => 1,
    '2' => 2,
    '3' => 3,
    '4' => 4,
    // Russian names (lowercase for case-insensitive matching)
    'белгород'          => 1,
    'склад в белгороде' => 1,
    'склад белгород'    => 1,
    'belgorod'           => 1,
    'воронеж'            => 2,
    'склад воронеж'     => 2,
    'voronezh'           => 2,
    'москва'             => 3,
    'склад москва'      => 3,
    'moscow'             => 3,
    'краснодар'          => 4,
    'склад краснодар'   => 4,
    'krasnodar'          => 4,
];
