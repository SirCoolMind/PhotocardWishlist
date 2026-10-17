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
    ");

    // Seed default PIN hash if not set
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'admin_pin_hash'");
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        $hash = password_hash(DEFAULT_ADMIN_PIN, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('admin_pin_hash', :val)");
        $ins->execute([':val' => $hash]);
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
    return hash_hmac('sha256', 'photocard_admin_' . $hash, __FILE__);
}

function is_admin(): bool
{
    // If user explicitly toggled "public_preview" cookie, show read-only mode even on localhost
    if (isset($_COOKIE['pc_public_preview']) && $_COOKIE['pc_public_preview'] === '1') {
        return false;
    }

    // Check signed auth cookie
    if (isset($_COOKIE['pc_admin_token']) && hash_equals(get_auth_token(), $_COOKIE['pc_admin_token'])) {
        return true;
    }

    // Auto-unlock on localhost by default for zero friction
    if (is_local_request()) {
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
    if ($hash && password_verify($pin, $hash)) {
        return true;
    }
    return $pin === DEFAULT_ADMIN_PIN;
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

function get_state_payload(): array
{
    $pdo = get_db();
    $cards = $pdo->query("SELECT * FROM photocards ORDER BY sort_order ASC, id DESC")->fetchAll();

    $counts = [
        'all' => count($cards),
        'uncollected' => 0,
        'pending' => 0,
        'collected' => 0,
        'no_need' => 0,
    ];
    $erasMap = [];

    foreach ($cards as &$c) {
        $c['id'] = (int) $c['id'];
        $c['price'] = $c['price'] !== null ? (float) $c['price'] : null;
        $c['sort_order'] = (int) $c['sort_order'];
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
        'is_admin' => is_admin(),
        'is_local' => is_local_request(),
        'public_preview' => isset($_COOKIE['pc_public_preview']) && $_COOKIE['pc_public_preview'] === '1',
        'db_size_kb' => $dbSizeKb,
        'counts' => $counts,
        'eras' => $eras,
        'cards' => $cards,
    ];
}

