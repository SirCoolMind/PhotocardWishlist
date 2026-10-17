<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Fetches the Uncollected sheet HTML view from Google Sheets, parses headers + images,
 * downloads all photocard images concurrently via curl_multi, and populates SQLite.
 */
function sync_from_google_sheet(bool $clearExisting = false): array
{
    $sheetUrl = 'https://docs.google.com/spreadsheets/d/' . GOOGLE_SHEET_ID . '/htmlview/sheet?headers=true&gid=0';

    $ch = curl_init($sheetUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    $html = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$html || $httpCode >= 400) {
        throw new RuntimeException("Could not fetch Google Sheet HTML (HTTP $httpCode).");
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);

    $rows = $xpath->query('//table[contains(@class, "waffle")]/tbody/tr');
    if (!$rows || $rows->length === 0) {
        throw new RuntimeException("No rows found in Uncollected sheet.");
    }

    $currentStatus = 'uncollected';
    $currentHeaders = [];
    $cardsToImport = [];
    $rowNum = 0;

    foreach ($rows as $tr) {
        $rowNum++;
        $cells = $xpath->query('./td', $tr);
        if (!$cells || $cells->length === 0) {
            continue;
        }

        // Inspect row content: does it have images or text headers?
        $rowImages = [];
        $rowTexts = [];
        $colIdx = 0;

        foreach ($cells as $td) {
            /** @var DOMElement $td */
            $colspan = max(1, (int) ($td->getAttribute('colspan') ?: 1));
            $text = trim(preg_replace('/\s+/', ' ', $td->textContent ?? ''));
            $imgNode = $xpath->query('.//img', $td)->item(0);
            $imgSrc = $imgNode instanceof DOMElement ? trim($imgNode->getAttribute('src')) : '';

            for ($c = 0; $c < $colspan; $c++) {
                $currCol = $colIdx + $c;
                if ($imgSrc !== '' && $c === 0) {
                    $rowImages[$currCol] = $imgSrc;
                }
                if ($text !== '') {
                    $rowTexts[$currCol] = $text;
                }
            }
            $colIdx += $colspan;
        }

        // Check if this row is a section divider
        $joinedText = strtoupper(implode(' ', array_unique($rowTexts)));
        if (str_contains($joinedText, 'NO NEED LIST')) {
            $currentStatus = 'no_need';
            $currentHeaders = [];
            continue;
        }
        if (str_contains($joinedText, 'PENDING ORDER')) {
            $currentStatus = 'pending';
            $currentHeaders = [];
            continue;
        }

        // If this row contains header labels (and no images)
        if (!empty($rowTexts) && empty($rowImages)) {
            $currentHeaders = [];
            $firstHeaderInRow = reset($rowTexts);
            $firstHeaderNormalized = $firstHeaderInRow ? normalize_era_name($firstHeaderInRow) : 'Uncategorized';
            $lastSeenHeader = $firstHeaderNormalized;
            for ($c = 0; $c < 15; $c++) {
                if (isset($rowTexts[$c]) && $rowTexts[$c] !== '') {
                    $lastSeenHeader = normalize_era_name($rowTexts[$c]);
                    $currentHeaders[$c] = $lastSeenHeader;
                } else {
                    // Carry header across blank header cells in the same row
                    $currentHeaders[$c] = $lastSeenHeader;
                }
            }
            continue;
        }

        // If this row contains photocard images
        if (!empty($rowImages)) {
            foreach ($rowImages as $c => $imgUrl) {
                $colLetter = chr(65 + $c);
                $cellRef = $colLetter . $rowNum;
                $era = $currentHeaders[$c] ?? 'Uncategorized';
                $cardsToImport[] = [
                    'cell_ref' => $cellRef,
                    'era' => $era,
                    'status' => $currentStatus,
                    'img_url' => $imgUrl,
                    'sort_order' => count($cardsToImport) + 1,
                ];
            }
        }
    }

    if (empty($cardsToImport)) {
        throw new RuntimeException("No photocard images found in the sheet.");
    }

    // Download all images in parallel using curl_multi
    $mh = curl_multi_init();
    $handles = [];
    foreach ($cardsToImport as $idx => $item) {
        $ch = curl_init($item['img_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$idx] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        if ($running > 0) {
            curl_multi_select($mh, 0.5);
        }
    } while ($running > 0);

    $pdo = get_db();
    if ($clearExisting) {
        $pdo->exec("DELETE FROM photocards");
        foreach (glob(CARDS_DIR . '/sheet_*.*') ?: [] as $oldFile) {
            @unlink($oldFile);
        }
    }

    $importedCount = 0;
    $skippedCount = 0;

    $checkStmt = $pdo->prepare("SELECT id FROM photocards WHERE source_ref = :ref");
    $insertStmt = $pdo->prepare("
        INSERT INTO photocards (era, title, status, price, notes, image_path, source_ref, sort_order)
        VALUES (:era, :title, :status, NULL, :notes, :image_path, :source_ref, :sort_order)
    ");

    foreach ($cardsToImport as $idx => $item) {
        $ch = $handles[$idx];
        $bytes = curl_multi_getcontent($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        if (!$clearExisting) {
            $checkStmt->execute([':ref' => 'sheet_' . $item['cell_ref']]);
            if ($checkStmt->fetchColumn()) {
                $skippedCount++;
                continue;
            }
        }

        if (!$bytes || $httpCode >= 400 || strlen($bytes) < 100) {
            continue;
        }

        $prefix = 'sheet_' . strtolower($item['cell_ref']);
        $imagePath = save_card_image_from_bytes($bytes, $prefix);

        $insertStmt->execute([
            ':era' => $item['era'],
            ':title' => '',
            ':status' => $item['status'],
            ':notes' => 'Sheet cell ' . $item['cell_ref'],
            ':image_path' => $imagePath,
            ':source_ref' => 'sheet_' . $item['cell_ref'],
            ':sort_order' => $item['sort_order'],
        ]);
        $importedCount++;
    }

    curl_multi_close($mh);

    return [
        'total_found' => count($cardsToImport),
        'imported' => $importedCount,
        'skipped' => $skippedCount,
    ];
}

function normalize_era_name(string $raw): string
{
    $trimmed = trim($raw);
    if (strcasecmp($trimmed, 'MY FIRST PAGE') === 0) {
        return 'My First Page';
    }
    return $trimmed;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = sync_from_google_sheet(true);
    echo json_encode($res, JSON_PRETTY_PRINT) . PHP_EOL;
}
