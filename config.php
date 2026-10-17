<?php
declare(strict_types=1);

// Load optional .env file if present
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim(trim($v), "\"'");
    }
}

define('APP_TITLE', $_ENV['APP_TITLE'] ?? 'Winteo Peace Photocard Wishlist');
define('STORAGE_DIR', __DIR__ . '/storage');
define('CARDS_DIR', STORAGE_DIR . '/cards');
define('DB_PATH', STORAGE_DIR . '/database.sqlite');
define('DEFAULT_ADMIN_PIN', $_ENV['ADMIN_PIN'] ?? '1234');
define('CURRENCY_SYMBOL', $_ENV['CURRENCY_SYMBOL'] ?? 'RM');
define('GOOGLE_SHEET_ID', $_ENV['GOOGLE_SHEET_ID'] ?? '13Wge2hBMeeLlxQT9x9mxgSzhJreetM2rM1AcTPL_J0c');

// Ensure storage directories exist
if (!is_dir(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0775, true);
}
if (!is_dir(CARDS_DIR)) {
    mkdir(CARDS_DIR, 0775, true);
}
