<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'config.php';
date_default_timezone_set('Asia/Dhaka');

function escapeCsvImportHtml($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeCsvImportHeader($value)
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', (string)$value);
    $value = strtoupper(trim($value));
    $value = preg_replace('/[^A-Z0-9]+/', '_', $value);
    return trim($value, '_');
}

function bindAndExecuteCsvRow($statement, array $values, $fileName, $rowNumber)
{
    $types = str_repeat('s', count($values));
    $parameters = [$types];
    foreach ($values as &$value) {
        $parameters[] = &$value;
    }
    unset($value);

    if (!call_user_func_array([$statement, 'bind_param'], $parameters)) {
        throw new RuntimeException($fileName . ' row ' . $rowNumber . ' bind failed: ' . $statement->error);
    }
    if (!$statement->execute()) {
        throw new RuntimeException($fileName . ' row ' . $rowNumber . ' insert failed: ' . $statement->error);
    }
}

function renderCsvImportResult($success, array $messages, array $rowResults = [])
{
    if (!$success) {
        http_response_code(500);
    }

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>CSV Import Result</title><style>';
    echo 'html,body{width:100%;min-height:100%;margin:0}body{background:#f1f5f9;';
    echo 'font-family:Arial,sans-serif;color:#172554}.page-shell{box-sizing:border-box;width:100%;';
    echo 'min-height:100vh;padding:18px 22px;display:flex;flex-direction:column}';
    echo '.back-home{display:inline-flex;align-items:center;gap:8px;align-self:flex-start;';
    echo 'padding:9px 13px;margin-bottom:12px;background:#000;border:1px solid #000;border-radius:8px;';
    echo 'color:#fff;text-decoration:none;font-weight:700;transition:background .15s,color .15s}';
    echo '.back-home:hover{background:#fff;color:#000;border-color:#000}';
    echo 'main{flex:1;min-height:0;display:flex;flex-direction:column;padding:22px;background:#fff;';
    echo 'border-radius:12px;box-shadow:0 6px 20px #0001}';
    echo 'h1{margin:0 0 16px;font-size:25px}.status{padding:11px 13px;margin:9px 0;border-radius:8px;font-weight:700}';
    echo '.success{background:#dcfce7;color:#166534}.error{background:#fee2e2;color:#991b1b}';
    echo '.warning{background:#fef3c7;color:#92400e}.row-results{flex:1;min-height:0;overflow:auto;margin-top:14px;';
    echo 'border:1px solid #e2e8f0;border-radius:8px}table{width:100%;border-collapse:collapse;font-size:14px}';
    echo 'th,td{padding:9px 12px;text-align:left;border-bottom:1px solid #e8edf3}';
    echo 'th{position:sticky;top:0;background:#f8fafc;color:#475569}tbody tr:last-child td{border-bottom:0}';
    echo '.row-status{color:#15803d;font-weight:700}@media(max-width:600px){.page-shell{padding:12px}';
    echo 'main{padding:16px}th,td{padding:8px;font-size:12px}}';
    echo '</style></head><body><div class="page-shell">';
    echo '<a class="back-home" href="initialPage.php">';
    echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">';
    echo '<path d="M19 12H5m0 0 7 7m-7-7 7-7" stroke="currentColor" stroke-width="2"';
    echo ' stroke-linecap="round" stroke-linejoin="round"/></svg>Back To Home</a>';
    echo '<main><h1>CSV Import Result</h1>';
    foreach ($messages as $message) {
        echo '<div class="status ' . escapeCsvImportHtml($message['type']) . '">' .
            escapeCsvImportHtml($message['text']) . '</div>';
    }
    if ($rowResults) {
        echo '<div class="row-results"><table><thead><tr><th>Serial</th><th>CSV File</th><th>Row</th>';
        echo '<th>Status</th></tr></thead><tbody>';
        foreach ($rowResults as $index => $rowResult) {
            echo '<tr><td>' . ($index + 1) . '</td>';
            echo '<td>' . escapeCsvImportHtml($rowResult['file']) . '</td>';
            echo '<td>Row ' . escapeCsvImportHtml($rowResult['row']) . '</td>';
            echo '<td class="row-status">Inserted successfully</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</main></div></body></html>';
}

$jobs = [];
$transactionStarted = false;
$messages = [];
$rowResults = [];

try {
    if (!isset($db) || !($db instanceof mysqli)) {
        throw new RuntimeException('Database connection is unavailable.');
    }

    $knittingMap = [
        'PO_NUMBER' => 'PO_NUMBER',
        'SONO' => 'SONO',
        'BUYER' => 'BUYER',
        'STYLE' => 'STYLE',
        'COLOR' => 'COLOR',
        'CUSTOMER' => 'CUSTOMER',
        'QTY' => 'QTY',
        'FINISH_GSM' => 'FINISH_GSM',
        'FINISH_DIA' => 'FINISH_DIA',
        'OPEN_TUBE' => 'OPEN_TUBE',
        'FABRICS_TYPE' => 'FABRICS_TYPE',
        'YARN_TYPE' => 'YARN_TYPE',
        'KNIT_MATERIAL_CODE' => 'KNIT_MATERIAL_CODE',
        'KNIT_M_DESCRIPTION' => 'KNIT_M_DESCRIPTION'
    ];
    $yarnMap = [
        'MATERIAL_NO' => 'MATERIAL_NO',
        'MATERIAL_DESCRIPTION' => 'MATERIAL_DESCRIPTION'
    ];
    $fileDefinitions = [
        [
            'name' => 'Knitting.csv',
            'path' => __DIR__ . DIRECTORY_SEPARATOR . 'Knitting.csv',
            'table' => 'knitting_input',
            'column_map' => $knittingMap,
            'extra_columns' => ['BUDAT', 'CBUDAT'],
            'extra_values' => [date('Y-m-d'), date('Y-m-d H:i:s')],
            'required_headers' => []
        ],
        [
            'name' => 'Yarn_Material.csv',
            'path' => __DIR__ . DIRECTORY_SEPARATOR . 'Yarn_Material.csv',
            'table' => 'yarn_material',
            'column_map' => $yarnMap,
            'extra_columns' => ['BUDAT', 'YIBUDAT'],
            'extra_values' => [date('Y-m-d'), date('Y-m-d H:i:s')],
            'required_headers' => ['MATERIAL_NO', 'MATERIAL_DESCRIPTION']
        ]
    ];

    foreach ($fileDefinitions as $definition) {
        if (!file_exists($definition['path'])) {
            continue;
        }
        if (!is_file($definition['path']) || !is_readable($definition['path'])) {
            throw new RuntimeException($definition['name'] . ' is not a readable file.');
        }

        $handle = fopen($definition['path'], 'rb');
        if (!$handle) {
            throw new RuntimeException('Unable to open ' . $definition['name'] . '.');
        }
        $header = fgetcsv($handle, 1000000, ',');
        if (!is_array($header)) {
            fclose($handle);
            throw new RuntimeException($definition['name'] . ' has no CSV header.');
        }

        $normalizedHeader = array_map('normalizeCsvImportHeader', $header);
        $headerMap = array_flip($normalizedHeader);
        foreach ($definition['required_headers'] as $requiredHeader) {
            if (!array_key_exists($requiredHeader, $headerMap)) {
                fclose($handle);
                throw new RuntimeException(
                    $definition['name'] . ' is missing the required column ' . $requiredHeader . '.'
                );
            }
        }

        $recognizedHeaders = array_intersect(
            array_keys($definition['column_map']),
            array_keys($headerMap)
        );
        if (!$recognizedHeaders) {
            fclose($handle);
            throw new RuntimeException($definition['name'] . ' does not contain any recognized data columns.');
        }

        $columns = array_merge(
            array_values($definition['column_map']),
            $definition['extra_columns']
        );
        $sql = 'INSERT INTO `' . $definition['table'] . '` (`' .
            implode('`, `', $columns) . '`) VALUES (' .
            implode(', ', array_fill(0, count($columns), '?')) . ')';
        $statement = $db->prepare($sql);
        if (!$statement) {
            fclose($handle);
            throw new RuntimeException(
                'Unable to prepare ' . $definition['name'] . ' import: ' . $db->error
            );
        }

        $definition['handle'] = $handle;
        $definition['header_map'] = $headerMap;
        $definition['statement'] = $statement;
        $definition['imported'] = 0;
        $jobs[] = $definition;
    }

    if (!$jobs) {
        throw new RuntimeException('No Knitting CSV or Yarn Material CSV File Is Found To Import.');
    }

    if (!$db->begin_transaction()) {
        throw new RuntimeException('Unable to start the CSV import transaction: ' . $db->error);
    }
    $transactionStarted = true;

    foreach ($jobs as $jobIndex => $job) {
        $rowNumber = 1;
        while (($row = fgetcsv($job['handle'], 1000000, ',')) !== false) {
            $rowNumber++;
            if (!is_array($row)) {
                continue;
            }

            $isEmptyRow = true;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') {
                    $isEmptyRow = false;
                    break;
                }
            }
            if ($isEmptyRow) {
                continue;
            }

            $values = [];
            foreach ($job['column_map'] as $csvColumn => $databaseColumn) {
                if (!array_key_exists($csvColumn, $job['header_map'])) {
                    $values[] = null;
                    continue;
                }
                $index = $job['header_map'][$csvColumn];
                $value = isset($row[$index]) ? trim((string)$row[$index]) : '';
                $values[] = $value === '' ? null : $value;
            }
            foreach ($job['extra_values'] as $extraValue) {
                $values[] = $extraValue;
            }

            bindAndExecuteCsvRow($job['statement'], $values, $job['name'], $rowNumber);
            $jobs[$jobIndex]['imported']++;
            $rowResults[] = ['file' => $job['name'], 'row' => $rowNumber];
        }
    }

    if (!$db->commit()) {
        throw new RuntimeException('Unable to commit CSV imports: ' . $db->error);
    }
    $transactionStarted = false;

    foreach ($jobs as $jobIndex => $job) {
        fclose($jobs[$jobIndex]['handle']);
        $jobs[$jobIndex]['handle'] = null;
    }

    $deleteFailed = false;
    foreach ($jobs as $job) {
        if (!unlink($job['path'])) {
            $deleteFailed = true;
            $messages[] = [
                'type' => 'warning',
                'text' => $job['name'] . ': Success: ' . $job['imported'] .
                    ' row(s), Failed: 0. Import succeeded, but the CSV file could not be deleted.'
            ];
            continue;
        }
        $messages[] = [
            'type' => 'success',
            'text' => $job['name'] . ': Success: ' . $job['imported'] .
                ' row(s), Failed: 0. CSV file deleted successfully.'
        ];
    }

    renderCsvImportResult(!$deleteFailed, $messages, $rowResults);
} catch (RuntimeException $error) {
    if ($transactionStarted) {
        $db->rollback();
    }
    error_log('CSV import failed: ' . $error->getMessage());
    $messages[] = ['type' => 'error', 'text' => $error->getMessage()];
    renderCsvImportResult(false, $messages);
} finally {
    foreach ($jobs as $job) {
        if (isset($job['handle']) && is_resource($job['handle'])) {
            fclose($job['handle']);
        }
        if (isset($job['statement']) && $job['statement'] instanceof mysqli_stmt) {
            $job['statement']->close();
        }
    }
}
?>
