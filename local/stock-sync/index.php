<?php
/**
 * Stock Sync API — receives stock data from 1C and updates Bitrix catalogue.
 *
 * Endpoints:
 *   GET  /api/stock/health        — health check (no auth required)
 *   POST /api/stock/update        — update single item
 *   POST /api/stock/bulk-update   — update multiple items
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

// =========================================================================
// Database
// =========================================================================

function get_db_connection(): PDO {
    $host = STOCK_SYNC_DB_HOST;
    $name = STOCK_SYNC_DB_NAME;
    $user = STOCK_SYNC_DB_USER;
    $pass = STOCK_SYNC_DB_PASSWORD;

    // Auto-detect from Bitrix if credentials are not set
    if (empty($host) || empty($name)) {
        $bitrix_root = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
        $settings_file = $bitrix_root . '/bitrix/.settings.php';
        $dbconn_file = $bitrix_root . '/bitrix/php_interface/dbconn.php';

        if (file_exists($settings_file)) {
            $settings = include $settings_file;
            $conn = $settings['connections']['value']['default'] ?? [];
            if (empty($host)) $host = $conn['host'] ?? 'localhost';
            if (empty($name)) $name = $conn['database'] ?? '';
            if (empty($user)) $user = $conn['login'] ?? '';
            if (empty($pass)) $pass = $conn['password'] ?? '';
        } elseif (file_exists($dbconn_file)) {
            // dbconn.php sets global variables: $DBHost, $DBName, $DBLogin, $DBPassword
            include $dbconn_file;
            if (empty($host)) $host = $DBHost ?? 'localhost';
            if (empty($name)) $name = $DBName ?? '';
            if (empty($user)) $user = $DBLogin ?? '';
            if (empty($pass)) $pass = $DBPassword ?? '';
        }
    }

    if (empty($name)) {
        throw new RuntimeException('Database name is not configured and could not be auto-detected from Bitrix');
    }

    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
}

// =========================================================================
// Auth
// =========================================================================

function verify_ip(): void {
    global $ALLOWED_IPS;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, $ALLOWED_IPS, true)) {
        stock_log("Blocked IP: {$ip}");
        json_response(403, ['detail' => 'Forbidden']);
    }
}

function verify_token(): void {
    $token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
    if (empty(STOCK_SYNC_TOKEN)) {
        json_response(500, ['detail' => 'API_TOKEN is not configured on the server']);
    }
    if (!hash_equals(STOCK_SYNC_TOKEN, $token)) {
        json_response(401, ['detail' => 'Invalid or missing X-Api-Token']);
    }
}

// =========================================================================
// Helpers
// =========================================================================

function resolve_sku(PDO $pdo, string $sku): ?int {
    $id = (int)$sku;
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT ID FROM b_catalog_product WHERE ID = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? (int)$row['ID'] : null;
}

function resolve_warehouse(string $warehouse): ?int {
    global $WAREHOUSE_MAP;
    return $WAREHOUSE_MAP[mb_strtolower(trim($warehouse), 'UTF-8')] ?? null;
}

function update_stock(PDO $pdo, int $offer_id, int $store_id, int $quantity): void {
    $stmt = $pdo->prepare(
        "INSERT INTO b_catalog_store_product (PRODUCT_ID, STORE_ID, AMOUNT)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE AMOUNT = ?"
    );
    $stmt->execute([$offer_id, $store_id, $quantity, $quantity]);

    $stmt = $pdo->prepare(
        "UPDATE b_catalog_product
         SET QUANTITY = (
             SELECT COALESCE(SUM(AMOUNT), 0)
             FROM b_catalog_store_product
             WHERE PRODUCT_ID = ?
         )
         WHERE ID = ?"
    );
    $stmt->execute([$offer_id, $offer_id]);
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        json_response(400, ['status' => 'error', 'message' => 'Empty request body']);
    }
    $data = json_decode($raw, true);
    if ($data === null) {
        json_response(400, ['status' => 'error', 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
    }
    return $data;
}

function json_response(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function stock_log(string $message): void {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    $log_file = $log_dir . '/stock_sync.log';
    $max_size = 5 * 1024 * 1024; // 5 MB

    if (file_exists($log_file) && filesize($log_file) > $max_size) {
        @rename($log_file, $log_dir . '/stock_sync.' . date('Ymd_His') . '.log');
    }

    $line = date('Y-m-d H:i:s') . ' | ' . $message . "\n";
    @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

// =========================================================================
// Routing
// =========================================================================

$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Extract action from URL path
// Supports: /api/stock/health, /local/stock-sync/health, ?action=health
$action = '';
if (preg_match('#/(?:api/stock|local/stock-sync)/([a-z-]+)#i', $path, $m)) {
    $action = strtolower($m[1]);
} elseif (isset($_GET['action'])) {
    $action = strtolower($_GET['action']);
}

// =========================================================================
// Endpoints
// =========================================================================

switch ($action) {

    // --- Health Check ---
    case 'health':
        $wh_ids = array_values(array_unique($WAREHOUSE_MAP));
        sort($wh_ids);

        $db_ok = false;
        try {
            $pdo = get_db_connection();
            $pdo->query('SELECT 1');
            $db_ok = true;
        } catch (Exception $e) {
            // DB unavailable
        }

        json_response(200, [
            'status'     => $db_ok ? 'ok' : 'degraded',
            'db'         => $db_ok ? 'connected' : 'unavailable',
            'warehouses' => $wh_ids,
        ]);
        break;

    // --- Single Update ---
    case 'update':
        if ($method !== 'POST') {
            json_response(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        verify_ip();
        verify_token();

        $body = json_input();
        $sku          = trim((string)($body['sku'] ?? ''));
        $warehouse    = trim((string)($body['warehouse'] ?? ''));
        $product_name = trim((string)($body['product_name'] ?? ''));
        $quantity     = isset($body['quantity']) ? (int)$body['quantity'] : -1;

        if ($sku === '' || $warehouse === '') {
            json_response(400, ['status' => 'error', 'message' => 'Missing required fields: sku, warehouse']);
        }
        if ($quantity < 0) {
            json_response(400, ['status' => 'error', 'message' => 'quantity must be >= 0']);
        }

        $store_id = resolve_warehouse($warehouse);
        if ($store_id === null) {
            json_response(400, [
                'status'    => 'error',
                'message'   => 'Unknown warehouse',
                'warehouse' => $warehouse,
            ]);
        }

        try {
            $pdo = get_db_connection();

            $offer_id = resolve_sku($pdo, $sku);
            if ($offer_id === null) {
                $log_name = $product_name !== '' ? " ({$product_name})" : '';
                stock_log("SKU not found | sku={$sku}{$log_name}");
                json_response(404, [
                    'status'  => 'error',
                    'message' => 'Product not found in catalogue',
                    'sku'     => $sku,
                ]);
            }

            update_stock($pdo, $offer_id, $store_id, $quantity);
        } catch (Exception $e) {
            stock_log("DB error on single update: " . $e->getMessage());
            json_response(500, ['status' => 'error', 'message' => 'Internal server error']);
        }

        $log_name = $product_name !== '' ? " ({$product_name})" : '';
        stock_log("Single update | sku={$sku}{$log_name} | offer_id={$offer_id} | warehouse={$warehouse} | store_id={$store_id} | qty={$quantity}");

        json_response(200, [
            'status'    => 'ok',
            'offer_id'  => $offer_id,
            'warehouse' => $warehouse,
            'quantity'  => $quantity,
        ]);
        break;

    // --- Bulk Update ---
    case 'bulk-update':
        if ($method !== 'POST') {
            json_response(405, ['status' => 'error', 'message' => 'Method not allowed']);
        }
        verify_ip();
        verify_token();

        $body  = json_input();
        $items = $body['items'] ?? [];

        if (empty($items) || !is_array($items)) {
            json_response(400, ['status' => 'error', 'message' => 'Missing or empty "items" array']);
        }

        if (count($items) > 1000) {
            json_response(400, ['status' => 'error', 'message' => 'Too many items. Max 1000 per request']);
        }

        $total     = count($items);
        $success   = 0;
        $not_found = 0;
        $errors    = 0;
        $failures  = [];

        try {
            $pdo = get_db_connection();

            foreach ($items as $item) {
                $sku          = trim((string)($item['sku'] ?? ''));
                $warehouse    = trim((string)($item['warehouse'] ?? ''));
                $product_name = trim((string)($item['product_name'] ?? ''));
                $quantity     = (int)($item['quantity'] ?? 0);

                if ($quantity < 0) {
                    $errors++;
                    $failures[] = [
                        'status'  => 'error',
                        'reason'  => 'invalid_quantity',
                        'sku'     => $sku,
                        'message' => 'quantity must be >= 0',
                    ];
                    continue;
                }

                $offer_id = resolve_sku($pdo, $sku);
                if ($offer_id === null) {
                    $not_found++;
                    $log_name = $product_name !== '' ? " ({$product_name})" : '';
                    $failures[] = [
                        'status'  => 'error',
                        'reason'  => 'sku_not_found',
                        'sku'     => $sku,
                        'message' => 'Product not found in catalogue',
                    ];
                    stock_log("SKU not found in bulk | sku={$sku}{$log_name}");
                    continue;
                }

                $store_id = resolve_warehouse($warehouse);
                if ($store_id === null) {
                    $errors++;
                    $failures[] = [
                        'status'    => 'error',
                        'reason'    => 'unknown_warehouse',
                        'warehouse' => $warehouse,
                        'message'   => 'Unknown warehouse',
                    ];
                    continue;
                }

                try {
                    update_stock($pdo, $offer_id, $store_id, $quantity);
                    $success++;
                } catch (Exception $e) {
                    $errors++;
                    $failures[] = [
                        'status'  => 'error',
                        'reason'  => 'db_error',
                        'sku'     => $sku,
                        'message' => $e->getMessage(),
                    ];
                    stock_log("DB error in bulk | sku={$sku} | " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            stock_log("DB connection error during bulk update: " . $e->getMessage());
            json_response(500, ['status' => 'error', 'message' => 'Internal server error']);
        }

        stock_log("Bulk update | total={$total} | success={$success} | not_found={$not_found} | errors={$errors}");

        json_response(200, [
            'status'    => 'ok',
            'total'     => $total,
            'success'   => $success,
            'not_found' => $not_found,
            'errors'    => $errors,
            'details'   => $failures,
        ]);
        break;

    // --- Unknown ---
    default:
        json_response(404, ['status' => 'error', 'message' => 'Unknown endpoint. Available: health, update, bulk-update']);
        break;
}
