<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA synchronous = NORMAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');

    init_schema($pdo);
    return $pdo;
}

function init_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS photocards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            era TEXT NOT NULL,
            title TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'uncollected',
            price REAL DEFAULT NULL,
            notes TEXT NOT NULL DEFAULT '',
            image_path TEXT NOT NULL,
            source_ref TEXT DEFAULT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE INDEX IF NOT EXISTS idx_photocards_status ON photocards(status);
        CREATE INDEX IF NOT EXISTS idx_photocards_era ON photocards(era);
        CREATE INDEX IF NOT EXISTS idx_photocards_sort ON photocards(sort_order, id);

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS card_prices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            card_id INTEGER NOT NULL REFERENCES photocards(id) ON DELETE CASCADE,
            price REAL NOT NULL,
            source TEXT NOT NULL,
            note TEXT NOT NULL DEFAULT '',
            image_path TEXT DEFAULT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE INDEX IF NOT EXISTS idx_card_prices_card_id ON card_prices(card_id);
    ");

    // Auto-migrate legacy photocard prices if card_prices table is empty
    $countPrices = (int) $pdo->query("SELECT COUNT(*) FROM card_prices")->fetchColumn();
    if ($countPrices === 0) {
        $pdo->exec("
            INSERT INTO card_prices (card_id, price, source, note, created_at)
            SELECT id, price, 'Previous Record', notes, created_at
            FROM photocards
            WHERE price IS NOT NULL AND price > 0
        ");
    }

    // Seed default PIN hash if not set
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'admin_pin_hash'");
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        $hash = password_hash(DEFAULT_ADMIN_PIN, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('admin_pin_hash', :val)");
        $ins->execute([':val' => $hash]);
    }

    // Seed random 256-bit HMAC secret if not set
    $secStmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'auth_secret'");
    $secStmt->execute();
    if (!$secStmt->fetchColumn()) {
        $secret = bin2hex(random_bytes(32));
        $insSec = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('auth_secret', :val)");
        $insSec->execute([':val' => $secret]);
    }
}

function is_local_request(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($ip, ['127.0.0.1', '::1', 'localhost'], true);
}

function get_auth_token(): string
{
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'admin_pin_hash'");
    $stmt->execute();
    $hash = (string) $stmt->fetchColumn();

    $secStmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'auth_secret'");
    $secStmt->execute();
    $secret = (string) $secStmt->fetchColumn();

    return hash_hmac('sha256', 'photocard_admin_' . ($hash !== '' ? $hash : DEFAULT_ADMIN_PIN), $secret . '|' . __FILE__);
}

function is_admin(): bool
{
    if (isset($_COOKIE['pc_public_preview']) && $_COOKIE['pc_public_preview'] === '1') {
        return false;
    }

    if (isset($_COOKIE['pc_admin_token']) && $_COOKIE['pc_admin_token'] !== '' && hash_equals(get_auth_token(), $_COOKIE['pc_admin_token'])) {
        return true;
    }

    return false;
}

function verify_admin_pin(string $pin): bool
{
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'admin_pin_hash'");
    $stmt->execute();
    $hash = (string) $stmt->fetchColumn();
    if ($hash !== '') {
        return password_verify($pin, $hash);
    }
    return hash_equals(DEFAULT_ADMIN_PIN, $pin);
}

function update_admin_pin(string $newPin): void
{
    $pdo = get_db();
    $hash = password_hash($newPin, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO settings (key, value) VALUES ('admin_pin_hash', :val) ON CONFLICT(key) DO UPDATE SET value = :val");
    $stmt->execute([':val' => $hash]);
}

/**
 * Convert raw image bytes into an optimized WebP file inside storage/cards/
 * Returns relative web path e.g. 'storage/cards/card_xxxx.webp'
 */
function save_card_image_from_bytes(string $bytes, string $prefix = 'card'): string
{
    if ($bytes === '') {
        throw new RuntimeException('Empty image data provided.');
    }

    $id = bin2hex(random_bytes(6));
    $filename = sprintf('%s_%s_%s.webp', $prefix, date('Ymd_His'), $id);
    $destPath = CARDS_DIR . '/' . $filename;

    $img = @imagecreatefromstring($bytes);
    if ($img !== false) {
        $width = imagesx($img);
        $height = imagesy($img);

        // Resize if larger than 960px tall while maintaining crisp photocard quality
        $maxHeight = 960;
        if ($height > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = (int) round($width * ($maxHeight / $height));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($img);
            $img = $resized;
        } else {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
        }

        if (function_exists('imagewebp') && imagewebp($img, $destPath, 86)) {
            imagedestroy($img);
            return 'storage/cards/' . $filename;
        }
        imagedestroy($img);
    }

    // Fallback: save raw bytes with detected extension
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($bytes) ?: 'image/jpeg';
    $extMap = [
        'image/webp' => 'webp',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
    ];
    $ext = $extMap[$mime] ?? 'jpg';
    $fallbackName = sprintf('%s_%s_%s.%s', $prefix, date('Ymd_His'), $id, $ext);
    file_put_contents(CARDS_DIR . '/' . $fallbackName, $bytes);
    return 'storage/cards/' . $fallbackName;
}

/**
 * Download remote image URL and store as WebP in storage/cards/
 */
function save_card_image_from_url(string $url, string $prefix = 'card'): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    $bytes = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($bytes === false || $code >= 400 || strlen((string) $bytes) < 100) {
        throw new RuntimeException("Failed to fetch image from URL (HTTP $code): $err");
    }

    return save_card_image_from_bytes((string) $bytes, $prefix);
}

/**
 * Convert raw image bytes into an optimized WebP file inside storage/prices/
 */
function save_price_image_from_bytes(string $bytes, string $prefix = 'price'): string
{
    if ($bytes === '') {
        throw new RuntimeException('Empty image data provided.');
    }

    $id = bin2hex(random_bytes(6));
    $filename = sprintf('%s_%s_%s.webp', $prefix, date('Ymd_His'), $id);
    $destPath = PRICES_DIR . '/' . $filename;

    $img = @imagecreatefromstring($bytes);
    if ($img !== false) {
        $width = imagesx($img);
        $height = imagesy($img);

        $maxHeight = 960;
        if ($height > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = (int) round($width * ($maxHeight / $height));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($img);
            $img = $resized;
        } else {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
        }

        if (function_exists('imagewebp') && imagewebp($img, $destPath, 86)) {
            imagedestroy($img);
            return 'storage/prices/' . $filename;
        }
        imagedestroy($img);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($bytes) ?: 'image/jpeg';
    $extMap = [
        'image/webp' => 'webp',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
    ];
    $ext = $extMap[$mime] ?? 'jpg';
    $fallbackName = sprintf('%s_%s_%s.%s', $prefix, date('Ymd_His'), $id, $ext);
    file_put_contents(PRICES_DIR . '/' . $fallbackName, $bytes);
    return 'storage/prices/' . $fallbackName;
}

/**
 * Download remote image URL and store as WebP in storage/prices/
 */
function save_price_image_from_url(string $url, string $prefix = 'price'): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    $bytes = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($bytes === false || $code >= 400 || strlen((string) $bytes) < 100) {
        throw new RuntimeException("Failed to fetch image from URL (HTTP $code): $err");
    }

    return save_price_image_from_bytes((string) $bytes, $prefix);
}

function get_card_prices(int $cardId): array
{
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM card_prices WHERE card_id = :card_id ORDER BY price ASC, id DESC");
    $stmt->execute([':card_id' => $cardId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['card_id'] = (int) $r['card_id'];
        $r['price'] = (float) $r['price'];
    }
    return $rows;
}

function get_state_payload(): array
{
    $pdo = get_db();
    $cards = $pdo->query("SELECT * FROM photocards ORDER BY sort_order ASC, id DESC")->fetchAll();
    $isAdmin = is_admin();

    $counts = [
        'all' => count($cards),
        'uncollected' => 0,
        'pending' => 0,
        'collected' => 0,
        'no_need' => 0,
    ];
    $erasMap = [];

    // Aggregate price count and lowest price per card for admin view
    $priceStats = [];
    if ($isAdmin) {
        $pRows = $pdo->query("SELECT card_id, COUNT(*) as cnt, MIN(price) as min_p FROM card_prices GROUP BY card_id")->fetchAll();
        foreach ($pRows as $pr) {
            $priceStats[(int) $pr['card_id']] = [
                'count' => (int) $pr['cnt'],
                'min_price' => (float) $pr['min_p'],
            ];
        }
    }

    foreach ($cards as &$c) {
        $c['id'] = (int) $c['id'];
        $c['sort_order'] = (int) $c['sort_order'];

        if ($isAdmin) {
            $stat = $priceStats[$c['id']] ?? null;
            $c['price_count'] = $stat ? $stat['count'] : 0;
            $c['min_price'] = $stat ? $stat['min_price'] : ($c['price'] !== null ? (float)$c['price'] : null);
            $c['price'] = $c['min_price'];
        } else {
            // Strictly hide all price data from non-admin/public viewers
            $c['price'] = null;
            $c['min_price'] = null;
            $c['price_count'] = 0;
        }

        $st = $c['status'];
        if (isset($counts[$st])) {
            $counts[$st]++;
        }
        $era = $c['era'];
        $erasMap[$era] = ($erasMap[$era] ?? 0) + 1;
    }
    unset($c);

    ksort($erasMap, SORT_NATURAL | SORT_FLAG_CASE);
    $eras = [];
    foreach ($erasMap as $name => $cnt) {
        $eras[] = ['name' => $name, 'count' => $cnt];
    }

    $dbSizeKb = is_file(DB_PATH) ? round(filesize(DB_PATH) / 1024, 1) : 0;

    return [
        'ok' => true,
        'app_title' => APP_TITLE,
        'currency' => CURRENCY_SYMBOL,
        'is_admin' => $isAdmin,
        'is_local' => is_local_request(),
        'public_preview' => isset($_COOKIE['pc_public_preview']) && $_COOKIE['pc_public_preview'] === '1',
        'db_size_kb' => $dbSizeKb,
        'counts' => $counts,
        'eras' => $eras,
        'cards' => $cards,
    ];
}

