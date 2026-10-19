<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Binary download actions
if ($action === 'export_zip' || $action === 'export_sqlite') {
    if (!is_admin()) {
        http_response_code(403);
        exit('Admin access required.');
    }
    if ($action === 'export_sqlite') {
        handle_export_sqlite();
    } else {
        handle_export_zip();
    }
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $input = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw ?: '{}', true) ?? [];
    } else {
        $input = $_POST;
    }

    if (isset($input['action']) && $action === 'list') {
        $action = (string) $input['action'];
    }

    switch ($action) {
        case 'list':
            echo json_encode(get_state_payload());
            break;

        case 'login':
            $pin = (string) ($input['pin'] ?? '');
            if (!verify_admin_pin($pin)) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'Invalid Admin PIN.']);
                break;
            }
            $expire = time() + 86400 * 90;
            $token = get_auth_token();
            setcookie('pc_admin_token', $token, $expire, '/', '', false, true);
            setcookie('pc_public_preview', '0', time() - 3600, '/', '', false, true);
            $_COOKIE['pc_admin_token'] = $token;
            unset($_COOKIE['pc_public_preview']);
            echo json_encode(['ok' => true, 'is_admin' => true]);
            break;

        case 'logout':
        case 'toggle_preview':
            $preview = (bool) ($input['preview'] ?? true);
            if ($preview) {
                setcookie('pc_public_preview', '1', time() + 86400 * 7, '/', '', false, true);
                setcookie('pc_admin_token', '', time() - 3600, '/', '', false, true);
                $_COOKIE['pc_public_preview'] = '1';
                unset($_COOKIE['pc_admin_token']);
            } else {
                if (!is_admin() && !verify_admin_pin((string) ($input['pin'] ?? ''))) {
                    http_response_code(401);
                    echo json_encode(['ok' => false, 'error' => 'Admin PIN required to unlock.']);
                    break;
                }
                setcookie('pc_public_preview', '0', time() - 3600, '/', '', false, true);
                unset($_COOKIE['pc_public_preview']);
            }
            echo json_encode(['ok' => true, 'is_admin' => is_admin()]);
            break;

        case 'update_pin':
            require_admin();
            $newPin = trim((string) ($input['new_pin'] ?? ''));
            if (strlen($newPin) < 4) {
                throw new RuntimeException('PIN must be at least 4 characters.');
            }
            update_admin_pin($newPin);
            $newToken = get_auth_token();
            setcookie('pc_admin_token', $newToken, time() + 86400 * 90, '/', '', false, true);
            $_COOKIE['pc_admin_token'] = $newToken;
            echo json_encode(['ok' => true, 'message' => 'Admin PIN updated.']);
            break;

        case 'create':
            require_admin();
            $era = trim((string) ($input['era'] ?? ''));
            if ($era === '') {
                throw new RuntimeException('Era / Category is required.');
            }
            $status = validate_status((string) ($input['status'] ?? 'uncollected'));
            $title = trim((string) ($input['title'] ?? ''));
            $price = isset($input['price']) && $input['price'] !== '' ? (float) $input['price'] : null;
            $notes = trim((string) ($input['notes'] ?? ''));

            $imagePath = process_incoming_image($input);
            if ($imagePath === null) {
                throw new RuntimeException('Please provide a photocard image (file upload, Ctrl+V paste, or URL).');
            }

            $pdo = get_db();
            $maxOrder = (int) $pdo->query("SELECT COALESCE(MAX(sort_order), 0) FROM photocards")->fetchColumn();
            $stmt = $pdo->prepare("
                INSERT INTO photocards (era, title, status, price, notes, image_path, sort_order)
                VALUES (:era, :title, :status, :price, :notes, :image_path, :sort_order)
            ");
            $stmt->execute([
                ':era' => $era,
                ':title' => $title,
                ':status' => $status,
                ':price' => $price,
                ':notes' => $notes,
                ':image_path' => $imagePath,
                ':sort_order' => $maxOrder + 1,
            ]);
            $id = (int) $pdo->lastInsertId();
            $card = get_card_by_id($id);
            echo json_encode(['ok' => true, 'card' => $card]);
            break;

        case 'update':
            require_admin();
            $id = (int) ($input['id'] ?? 0);
            $existing = get_card_by_id($id);
            if (!$existing) {
                throw new RuntimeException('Card not found.');
            }

            $era = trim((string) ($input['era'] ?? $existing['era']));
            if ($era === '') {
                throw new RuntimeException('Era / Category cannot be empty.');
            }
            $status = validate_status((string) ($input['status'] ?? $existing['status']));
            $title = trim((string) ($input['title'] ?? $existing['title']));
            $price = array_key_exists('price', $input)
                ? ($input['price'] !== '' && $input['price'] !== null ? (float) $input['price'] : null)
                : $existing['price'];
            $notes = trim((string) ($input['notes'] ?? $existing['notes']));

            $newImagePath = process_incoming_image($input);
            $imagePath = $existing['image_path'];
            if ($newImagePath !== null) {
                $oldFull = __DIR__ . '/' . $existing['image_path'];
                if (is_file($oldFull)) {
                    @unlink($oldFull);
                }
                $imagePath = $newImagePath;
            }

            $pdo = get_db();
            $stmt = $pdo->prepare("
                UPDATE photocards
                SET era = :era,
                    title = :title,
                    status = :status,
                    price = :price,
                    notes = :notes,
                    image_path = :image_path,
                    updated_at = datetime('now')
                WHERE id = :id
            ");
            $stmt->execute([
                ':era' => $era,
                ':title' => $title,
                ':status' => $status,
                ':price' => $price,
                ':notes' => $notes,
                ':image_path' => $imagePath,
                ':id' => $id,
            ]);

            echo json_encode(['ok' => true, 'card' => get_card_by_id($id)]);
            break;

        case 'move':
            require_admin();
            $targetStatus = validate_status((string) ($input['status'] ?? ''));
            $ids = [];
            if (!empty($input['ids']) && is_array($input['ids'])) {
                $ids = array_map('intval', $input['ids']);
            } elseif (!empty($input['id'])) {
                $ids = [(int) $input['id']];
            }
            $ids = array_values(array_filter($ids, fn($v) => $v > 0));
            if (empty($ids)) {
                throw new RuntimeException('No card IDs specified.');
            }

            $pdo = get_db();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $prevStmt = $pdo->prepare("SELECT id, status FROM photocards WHERE id IN ($placeholders)");
            $prevStmt->execute($ids);
            $previous = $prevStmt->fetchAll();

            $updStmt = $pdo->prepare("UPDATE photocards SET status = ?, updated_at = datetime('now') WHERE id IN ($placeholders)");
            $updStmt->execute(array_merge([$targetStatus], $ids));

            echo json_encode([
                'ok' => true,
                'moved_ids' => $ids,
                'status' => $targetStatus,
                'previous' => $previous,
            ]);
            break;

        case 'undo_move':
            require_admin();
            $items = $input['items'] ?? [];
            if (!is_array($items) || empty($items)) {
                throw new RuntimeException('Nothing to undo.');
            }
            $pdo = get_db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE photocards SET status = :status, updated_at = datetime('now') WHERE id = :id");
            foreach ($items as $item) {
                $stmt->execute([
                    ':status' => validate_status((string) ($item['status'] ?? 'uncollected')),
                    ':id' => (int) ($item['id'] ?? 0),
                ]);
            }
            $pdo->commit();
            echo json_encode(['ok' => true]);
            break;

        case 'delete':
            require_admin();
            $ids = [];
            if (!empty($input['ids']) && is_array($input['ids'])) {
                $ids = array_map('intval', $input['ids']);
            } elseif (!empty($input['id'])) {
                $ids = [(int) $input['id']];
            }
            $ids = array_values(array_filter($ids, fn($v) => $v > 0));
            if (empty($ids)) {
                throw new RuntimeException('No card IDs specified.');
            }

            $pdo = get_db();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sel = $pdo->prepare("SELECT image_path FROM photocards WHERE id IN ($placeholders)");
            $sel->execute($ids);
            $paths = $sel->fetchAll(PDO::FETCH_COLUMN);

            $del = $pdo->prepare("DELETE FROM photocards WHERE id IN ($placeholders)");
            $del->execute($ids);

            foreach ($paths as $relPath) {
                $full = __DIR__ . '/' . $relPath;
                if (is_file($full)) {
                    @unlink($full);
                }
            }

            echo json_encode(['ok' => true, 'deleted_ids' => $ids]);
            break;

        case 'import_bundle':
            require_admin();
            $mode = ($input['mode'] ?? 'replace') === 'merge' ? 'merge' : 'replace';
            $result = handle_import_bundle($mode);
            echo json_encode(['ok' => true, 'result' => $result]);
            break;

        case 'list_prices':
            require_admin();
            $cardId = (int) ($input['card_id'] ?? $_GET['card_id'] ?? 0);
            if ($cardId <= 0) {
                throw new RuntimeException('Invalid card ID.');
            }
            echo json_encode(['ok' => true, 'prices' => get_card_prices($cardId)]);
            break;

        case 'create_price':
            require_admin();
            $cardId = (int) ($input['card_id'] ?? 0);
            if ($cardId <= 0 || !get_card_by_id($cardId)) {
                throw new RuntimeException('Card not found.');
            }
            if (!isset($input['price']) || $input['price'] === '' || (float)$input['price'] <= 0) {
                throw new RuntimeException('Price is required and must be greater than 0.');
            }
            $price = (float) $input['price'];
            $source = trim((string) ($input['source'] ?? ''));
            if ($source === '') {
                throw new RuntimeException('Source is required (e.g. Carousell, Twitter @seller, Shopee, or link).');
            }
            $notes = trim((string) ($input['notes'] ?? $input['note'] ?? ''));
            $imagePath = process_incoming_price_image($input);

            $pdo = get_db();
            $stmt = $pdo->prepare("
                INSERT INTO card_prices (card_id, price, source, note, image_path)
                VALUES (:card_id, :price, :source, :note, :image_path)
            ");
            $stmt->execute([
                ':card_id' => $cardId,
                ':price' => $price,
                ':source' => $source,
                ':note' => $notes,
                ':image_path' => $imagePath,
            ]);
            $priceId = (int) $pdo->lastInsertId();

            echo json_encode([
                'ok' => true,
                'price_id' => $priceId,
                'card_id' => $cardId,
                'prices' => get_card_prices($cardId)
            ]);
            break;

        case 'delete_price':
            require_admin();
            $id = (int) ($input['id'] ?? 0);
            $pdo = get_db();
            $stmt = $pdo->prepare("SELECT card_id, image_path FROM card_prices WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new RuntimeException('Price listing not found.');
            }
            $cardId = (int) $row['card_id'];
            if (!empty($row['image_path'])) {
                $full = __DIR__ . '/' . $row['image_path'];
                if (is_file($full)) {
                    @unlink($full);
                }
            }
            $del = $pdo->prepare("DELETE FROM card_prices WHERE id = :id");
            $del->execute([':id' => $id]);

            echo json_encode([
                'ok' => true,
                'deleted_id' => $id,
                'card_id' => $cardId,
                'prices' => get_card_prices($cardId)
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => "Unknown action: $action"]);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ]);
}

function require_admin(): void
{
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Admin PIN required to modify cards.']);
        exit;
    }
}

function validate_status(string $status): string
{
    $allowed = ['uncollected', 'pending', 'collected', 'no_need'];
    if (!in_array($status, $allowed, true)) {
        throw new RuntimeException("Invalid status: $status");
    }
    return $status;
}

function get_card_by_id(int $id): ?array
{
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM photocards WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['id'] = (int) $row['id'];
    $row['price'] = $row['price'] !== null ? (float) $row['price'] : null;
    $row['sort_order'] = (int) $row['sort_order'];
    return $row;
}

function process_incoming_image(array $input): ?string
{
    // 1. Standard multipart file upload
    if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $bytes = file_get_contents($_FILES['image']['tmp_name']);
        if ($bytes !== false && $bytes !== '') {
            return save_card_image_from_bytes($bytes, 'upload');
        }
    }

    // 2. Base64 data URI (e.g. from Ctrl+V clipboard paste)
    if (!empty($input['image_base64'])) {
        $b64 = (string) $input['image_base64'];
        if (str_contains($b64, ',')) {
            [, $b64] = explode(',', $b64, 2);
        }
        $bytes = base64_decode($b64, true);
        if ($bytes !== false && strlen($bytes) > 50) {
            return save_card_image_from_bytes($bytes, 'paste');
        }
    }

    // 3. Remote Image URL
    if (!empty($input['image_url'])) {
        $url = trim((string) $input['image_url']);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return save_card_image_from_url($url, 'url');
        }
    }

    return null;
}

function process_incoming_price_image(array $input): ?string
{
    // 1. Standard multipart file upload
    if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $bytes = file_get_contents($_FILES['image']['tmp_name']);
        if ($bytes !== false && $bytes !== '') {
            return save_price_image_from_bytes($bytes, 'price');
        }
    }

    // 2. Base64 data URI (e.g. from Ctrl+V clipboard paste)
    if (!empty($input['image_base64'])) {
        $b64 = (string) $input['image_base64'];
        if (str_contains($b64, ',')) {
            [, $b64] = explode(',', $b64, 2);
        }
        $bytes = base64_decode($b64, true);
        if ($bytes !== false && strlen($bytes) > 50) {
            return save_price_image_from_bytes($bytes, 'price');
        }
    }

    // 3. Remote Image URL
    if (!empty($input['image_url'])) {
        $url = trim((string) $input['image_url']);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return save_price_image_from_url($url, 'price');
        }
    }

    return null;
}

function create_clean_sqlite_snapshot(): string
{
    $pdo = get_db();
    $snapshotPath = STORAGE_DIR . '/snapshot_' . bin2hex(random_bytes(4)) . '.sqlite';
    if (is_file($snapshotPath)) {
        @unlink($snapshotPath);
    }
    $escaped = str_replace("'", "''", $snapshotPath);
    $pdo->exec("VACUUM INTO '$escaped'");
    return $snapshotPath;
}

function handle_export_sqlite(): void
{
    $snapshot = create_clean_sqlite_snapshot();
    $filename = 'photocard-wishlist-db-' . date('Ymd-His') . '.sqlite';

    header('Content-Type: application/vnd.sqlite3');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($snapshot));
    readfile($snapshot);
    @unlink($snapshot);
}

function handle_export_zip(): void
{
    $snapshot = create_clean_sqlite_snapshot();
    $zipPath = STORAGE_DIR . '/export_' . bin2hex(random_bytes(4)) . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        @unlink($snapshot);
        throw new RuntimeException('Could not create ZIP archive.');
    }

    $zip->addFile($snapshot, 'database.sqlite');

    $cardFiles = glob(CARDS_DIR . '/*.*') ?: [];
    foreach ($cardFiles as $file) {
        if (is_file($file)) {
            $zip->addFile($file, 'cards/' . basename($file));
        }
    }

    $priceFiles = glob(PRICES_DIR . '/*.*') ?: [];
    foreach ($priceFiles as $file) {
        if (is_file($file)) {
            $zip->addFile($file, 'prices/' . basename($file));
        }
    }

    $manifest = [
        'exported_at' => date('c'),
        'app' => APP_TITLE,
        'card_images_count' => count($cardFiles),
        'price_images_count' => count($priceFiles),
    ];
    $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    $zip->close();
    @unlink($snapshot);

    $filename = 'photocard-wishlist-bundle-' . date('Ymd-His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    @unlink($zipPath);
}

function handle_import_bundle(string $mode): array
{
    if (empty($_FILES['bundle']['tmp_name']) || !is_uploaded_file($_FILES['bundle']['tmp_name'])) {
        throw new RuntimeException('No backup file uploaded.');
    }

    $uploadedTmp = $_FILES['bundle']['tmp_name'];
    $origName = strtolower((string) ($_FILES['bundle']['name'] ?? ''));
    $ext = pathinfo($origName, PATHINFO_EXTENSION);

    $tempDir = STORAGE_DIR . '/import_tmp_' . bin2hex(random_bytes(4));
    mkdir($tempDir, 0775, true);

    try {
        $importedDbPath = null;
        $importedCardsDir = null;

        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($uploadedTmp) !== true) {
                throw new RuntimeException('Invalid ZIP file.');
            }
            $zip->extractTo($tempDir);
            $zip->close();

            if (is_file($tempDir . '/database.sqlite')) {
                $importedDbPath = $tempDir . '/database.sqlite';
            } elseif (is_file($tempDir . '/storage/database.sqlite')) {
                $importedDbPath = $tempDir . '/storage/database.sqlite';
            }

            if (is_dir($tempDir . '/cards')) {
                $importedCardsDir = $tempDir . '/cards';
            } elseif (is_dir($tempDir . '/storage/cards')) {
                $importedCardsDir = $tempDir . '/storage/cards';
            }

            $importedPricesDir = null;
            if (is_dir($tempDir . '/prices')) {
                $importedPricesDir = $tempDir . '/prices';
            } elseif (is_dir($tempDir . '/storage/prices')) {
                $importedPricesDir = $tempDir . '/storage/prices';
            }
        } elseif (in_array($ext, ['sqlite', 'sqlite3', 'db'], true)) {
            $importedDbPath = $tempDir . '/uploaded.sqlite';
            copy($uploadedTmp, $importedDbPath);
        } else {
            throw new RuntimeException('Unsupported file format. Upload a .zip bundle or .sqlite database file.');
        }

        if (!$importedDbPath || !is_file($importedDbPath)) {
            throw new RuntimeException('No database.sqlite found inside the uploaded backup.');
        }

        // Verify the uploaded SQLite database has a photocards table
        $srcPdo = new PDO('sqlite:' . $importedDbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $srcCards = $srcPdo->query("SELECT * FROM photocards ORDER BY sort_order ASC, id ASC")->fetchAll();

        $destPdo = get_db();
        $destPdo->beginTransaction();

        if ($mode === 'replace') {
            $destPdo->exec("DELETE FROM card_prices");
            $destPdo->exec("DELETE FROM photocards");
            foreach (glob(CARDS_DIR . '/*.*') ?: [] as $existingImg) {
                @unlink($existingImg);
            }
            foreach (glob(PRICES_DIR . '/*.*') ?: [] as $existingPriceImg) {
                @unlink($existingPriceImg);
            }
        }

        $ins = $destPdo->prepare("
            INSERT INTO photocards (id, era, title, status, price, notes, image_path, source_ref, sort_order, created_at, updated_at)
            VALUES (:id, :era, :title, :status, :price, :notes, :image_path, :source_ref, :sort_order, :created_at, :updated_at)
        ");

        $importedCount = 0;
        foreach ($srcCards as $sc) {
            $imgRel = (string) $sc['image_path'];
            $imgBase = basename($imgRel);

            // Copy image file if present in ZIP bundle
            if ($importedCardsDir && is_file($importedCardsDir . '/' . $imgBase)) {
                copy($importedCardsDir . '/' . $imgBase, CARDS_DIR . '/' . $imgBase);
                $imgRel = 'storage/cards/' . $imgBase;
            }

            $ins->execute([
                ':id' => (int) $sc['id'],
                ':era' => $sc['era'],
                ':title' => $sc['title'] ?? '',
                ':status' => $sc['status'] ?? 'uncollected',
                ':price' => $sc['price'] ?? null,
                ':notes' => $sc['notes'] ?? '',
                ':image_path' => $imgRel,
                ':source_ref' => $sc['source_ref'] ?? null,
                ':sort_order' => (int) ($sc['sort_order'] ?? 0),
                ':created_at' => $sc['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at' => $sc['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $importedCount++;
        }

        // Import card_prices if present in uploaded database
        $hasPricesTable = (bool) $srcPdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='card_prices'")->fetchColumn();
        if ($hasPricesTable) {
            $srcPrices = $srcPdo->query("SELECT * FROM card_prices ORDER BY id ASC")->fetchAll();
            $insPrice = $destPdo->prepare("
                INSERT INTO card_prices (card_id, price, source, note, image_path, created_at)
                VALUES (:card_id, :price, :source, :note, :image_path, :created_at)
            ");
            foreach ($srcPrices as $sp) {
                $pImgRel = !empty($sp['image_path']) ? (string) $sp['image_path'] : null;
                if ($pImgRel && $importedPricesDir) {
                    $pBase = basename($pImgRel);
                    if (is_file($importedPricesDir . '/' . $pBase)) {
                        copy($importedPricesDir . '/' . $pBase, PRICES_DIR . '/' . $pBase);
                        $pImgRel = 'storage/prices/' . $pBase;
                    }
                }
                $insPrice->execute([
                    ':card_id' => (int) $sp['card_id'],
                    ':price' => (float) $sp['price'],
                    ':source' => (string) $sp['source'],
                    ':note' => (string) ($sp['note'] ?? ''),
                    ':image_path' => $pImgRel,
                    ':created_at' => $sp['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }

        $destPdo->commit();
        $srcPdo = null;

        return [
            'mode' => $mode,
            'cards_imported' => $importedCount,
        ];
    } finally {
        delete_dir_recursive($tempDir);
    }
}

function delete_dir_recursive(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            delete_dir_recursive($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
