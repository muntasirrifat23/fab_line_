<?php
// ajaxKnittingproduction.php
// Save scanned knitting program production data via AJAX.

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if (!isset($db) || !$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$rawInput = file_get_contents('php://input');
$requestData = $_POST;

if ($rawInput !== false && strlen(trim($rawInput)) > 0) {
    $decoded = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $requestData = $decoded;
    }
}

$requestData = array_change_key_case((array)$requestData, CASE_LOWER);

// Get the booking number - this is the main identifier
$booking = trim($requestData['booking'] ?? '');
if ($booking === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking number is required.']);
    exit();
}

// Check if this booking already exists
$checkSql = "SELECT MAIN_TID, SUB_TID FROM knitting_program WHERE BOOKING = ?";
$checkStmt = mysqli_prepare($db, $checkSql);
mysqli_stmt_bind_param($checkStmt, 's', $booking);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    // Record exists - update it
    $existing = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($checkStmt);
    
    // Mapping of request fields to database columns
    $updateableColumns = [
        'sub_tid' => 'SUB_TID',
        'mcno' => 'MCNO',
        'buyer' => 'BUYER',
        'sono' => 'SONO',
        'style' => 'STYLE',
        'fabrics_type' => 'FABRICS_TYPE',
        'yarn_count' => 'YARN_COUNT',
        'yarn_type' => 'YARN_TYPE',
        'finish_gsm' => 'FINISH_GSM',
        'finish_dia' => 'FINISH_DIA',
        'open_tube' => 'OPEN_TUBE',
        'lot_no' => 'LOT_NO',
        'qty' => 'QTY',
        'sl_vdq' => 'SL_VDQ',
        'color' => 'COLOR'
    ];
    
    $updateFields = [];
    $updateValues = [];
    
    foreach ($updateableColumns as $requestKey => $dbColumn) {
        if (array_key_exists($requestKey, $requestData)) {
            $updateFields[] = "$dbColumn = ?";
            $updateValues[] = trim((string)$requestData[$requestKey]);
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update.']);
        exit();
    }
    
    $updateValues[] = $booking; // for WHERE clause
    $sql = "UPDATE knitting_program SET " . implode(', ', $updateFields) . " WHERE BOOKING = ?";
    $stmt = mysqli_prepare($db, $sql);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare update: ' . mysqli_error($db)]);
        exit();
    }
    
    $types = str_repeat('s', count($updateValues));
    $bindParams = array_merge([$types], $updateValues);
    $refs = [];
    foreach ($bindParams as $key => $value) {
        $refs[$key] = &$bindParams[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
    
    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        exit();
    }
    
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Production data updated successfully.',
        'booking' => $booking,
        'action' => 'updated',
        'rows_affected' => $affected
    ]);
    exit();
}
mysqli_stmt_close($checkStmt);

// --- INSERT NEW RECORD ---

// Get the schema columns
$columnsResult = mysqli_query($db, 'SHOW COLUMNS FROM knitting_program');
if (!$columnsResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to read knitting_program schema: ' . mysqli_error($db)]);
    exit();
}

$schemaColumns = [];
while ($column = mysqli_fetch_assoc($columnsResult)) {
    $schemaColumns[strtoupper($column['Field'])] = true;
}

$knownColumns = [
    'main_tid' => 'MAIN_TID',
    'sub_tid' => 'SUB_TID',
    'mcno' => 'MCNO',
    'buyer' => 'BUYER',
    'booking' => 'BOOKING',
    'sono' => 'SONO',
    'style' => 'STYLE',
    'fabrics_type' => 'FABRICS_TYPE',
    'yarn_count' => 'YARN_COUNT',
    'yarn_type' => 'YARN_TYPE',
    'finish_gsm' => 'FINISH_GSM',
    'finish_dia' => 'FINISH_DIA',
    'open_tube' => 'OPEN_TUBE',
    'lot_no' => 'LOT_NO',
    'sl_vdq' => 'SL_VDQ',
    'qty' => 'QTY',
    'color' => 'COLOR'
];

$insertFields = [];
$insertValues = [];

foreach ($knownColumns as $requestKey => $dbColumn) {
    if (!isset($schemaColumns[$dbColumn])) {
        continue;
    }

    if (array_key_exists($requestKey, $requestData) && trim((string)$requestData[$requestKey]) !== '') {
        $insertFields[] = $dbColumn;
        $insertValues[] = trim((string)$requestData[$requestKey]);
    }
}

// Add defaults for common columns if they exist and are missing
if (isset($schemaColumns['SHIFT']) && !in_array('SHIFT', $insertFields, true)) {
    $insertFields[] = 'SHIFT';
    $insertValues[] = 'A-SHIFT';
}
if (isset($schemaColumns['KNIT_M_DESCRIPTION']) && !in_array('KNIT_M_DESCRIPTION', $insertFields, true)) {
    $insertFields[] = 'KNIT_M_DESCRIPTION';
    $insertValues[] = '';
}
if (isset($schemaColumns['SUPPLIER']) && !in_array('SUPPLIER', $insertFields, true)) {
    $insertFields[] = 'SUPPLIER';
    $insertValues[] = '';
}
if (isset($schemaColumns['KNIT_MATERIAL_CODE']) && !in_array('KNIT_MATERIAL_CODE', $insertFields, true)) {
    $insertFields[] = 'KNIT_MATERIAL_CODE';
    $insertValues[] = '';
}
if (isset($schemaColumns['MAIN_TID']) && !in_array('MAIN_TID', $insertFields, true)) {
    $tidRow = mysqli_fetch_assoc(mysqli_query($db, 'SELECT COALESCE(MAX(MAIN_TID), 1000000000) AS max_main FROM knitting_program'));
    $mainTid = intval($tidRow['max_main']) + 1;
    $insertFields[] = 'MAIN_TID';
    $insertValues[] = (string)$mainTid;
}

if (isset($schemaColumns['SUB_TID']) && !in_array('SUB_TID', $insertFields, true)) {
    $tidRow = mysqli_fetch_assoc(mysqli_query($db, 'SELECT COALESCE(MAX(SUB_TID), 2000000000) AS max_sub FROM knitting_program'));
    $subTid = intval($tidRow['max_sub']) + 1;
    $insertFields[] = 'SUB_TID';
    $insertValues[] = (string)$subTid;
}

if (empty($insertFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid fields were provided for saving.']);
    exit();
}

$placeholders = implode(', ', array_fill(0, count($insertFields), '?'));
$sql = 'INSERT INTO knitting_program (' . implode(', ', $insertFields) . ') VALUES (' . $placeholders . ')';
$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare insert: ' . mysqli_error($db)]);
    exit();
}

$types = str_repeat('s', count($insertValues));
$bindParams = array_merge([$types], $insertValues);
$refs = [];
foreach ($bindParams as $key => $value) {
    $refs[$key] = &$bindParams[$key];
}

call_user_func_array([$stmt, 'bind_param'], $refs);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . mysqli_stmt_error($stmt)]);
    mysqli_stmt_close($stmt);
    exit();
}

$newId = mysqli_stmt_insert_id($stmt);
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'message' => 'Production data saved successfully.',
    'insert_id' => $newId,
    'booking' => $booking,
    'action' => 'inserted'
]);
exit();
?>