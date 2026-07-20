<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => 'Failed to save program.'];

$hostname = 'localhost';
$username = 'root';
$password = 'pgadmin';
$databaseName = 'knittingdb';

$db = mysqli_connect($hostname, $username, $password, $databaseName);

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit();
}

mysqli_set_charset($db, 'utf8mb4');

// Ensure MAIN_TID and SUB_TID can hold values above 2,147,483,647
$columnResult = mysqli_query($db, "SHOW COLUMNS FROM knitting_program WHERE Field IN ('MAIN_TID','SUB_TID')");
$needsBigInt = false;
if ($columnResult) {
    while ($col = mysqli_fetch_assoc($columnResult)) {
        if (stripos($col['Type'], 'bigint') === false) {
            $needsBigInt = true;
            break;
        }
    }
    mysqli_free_result($columnResult);
}

if ($needsBigInt) {
    mysqli_query($db, "ALTER TABLE knitting_program MODIFY MAIN_TID BIGINT NOT NULL, MODIFY SUB_TID BIGINT NOT NULL");
}

// Decode JSON request body if needed
$rawInput = file_get_contents('php://input');
if ($rawInput !== false && strlen(trim($rawInput)) > 0) {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (stripos($contentType, 'application/json') !== false || empty($_POST)) {
        $decoded = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $_POST = $decoded;
        }
    }
}

// If mcno_qty is sent as a JSON string in a form payload, decode it
if (isset($_POST['mcno_qty']) && is_string($_POST['mcno_qty'])) {
    $decodedMcnoQty = json_decode($_POST['mcno_qty'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMcnoQty)) {
        $_POST['mcno_qty'] = $decodedMcnoQty;
    }
}

// Get POST data
$booking = isset($_POST['booking']) ? trim($_POST['booking']) : '';
$sono = isset($_POST['sono']) ? trim($_POST['sono']) : null;
$style = isset($_POST['style']) ? trim($_POST['style']) : null;
$buyer = isset($_POST['buyer']) ? trim($_POST['buyer']) : null;
$supplier = isset($_POST['supplier']) ? trim($_POST['supplier']) : null;
$knitDescription = isset($_POST['knit_m_description']) ? trim($_POST['knit_m_description']) : null;
$yarnType = isset($_POST['yarn_type']) ? trim($_POST['yarn_type']) : null;
$yarnCount = isset($_POST['yarn_count']) ? trim($_POST['yarn_count']) : null;
$fabricsType = isset($_POST['fabrics_type']) ? trim($_POST['fabrics_type']) : null;
$finishGsm = isset($_POST['finish_gsm']) ? trim($_POST['finish_gsm']) : null;
$finishDia = isset($_POST['finish_dia']) ? trim($_POST['finish_dia']) : null;
$openTube = isset($_POST['open_tube']) ? trim($_POST['open_tube']) : null;
$lotNo = isset($_POST['lot_no']) ? trim($_POST['lot_no']) : null;
$knitMaterialCode = isset($_POST['knit_material_code']) ? trim($_POST['knit_material_code']) : null;
$mcnoQtyData = isset($_POST['mcno_qty']) ? $_POST['mcno_qty'] : [];

// Validate required fields
if ($booking === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking number is required.']);
    mysqli_close($db);
    exit();
}

if (!is_array($mcnoQtyData) || empty($mcnoQtyData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one MCNO and quantity is required.']);
    mysqli_close($db);
    exit();
}

// Determine MAIN_TID and starting SUB_TID using only valid prior IDs
$tidResult = mysqli_query($db, "SELECT 
    COALESCE(MAX(CASE WHEN MAIN_TID >= 2000000990 THEN MAIN_TID END), 2000000990) AS max_main,
    COALESCE(MAX(CASE WHEN SUB_TID >= 3000000990 THEN SUB_TID END), 3000000990) AS max_sub
FROM knitting_program");
if (!$tidResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to determine IDs: ' . mysqli_error($db)]);
    mysqli_close($db);
    exit();
}
$tidRow = mysqli_fetch_assoc($tidResult);
$mainTid = ((int)$tidRow['max_main'] < 2000000990 ? 2000000990 : (int)$tidRow['max_main']) + 1;
$nextSubTid = ((int)$tidRow['max_sub'] < 3000000990 ? 3000000990 : (int)$tidRow['max_sub']) + 1;

// Start transaction
if (!mysqli_begin_transaction($db)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to start database transaction.']);
    mysqli_close($db);
    exit();
}

$insertSql = "INSERT INTO knitting_program (
    MAIN_TID,
    SUB_TID,
    BOOKING,
    SONO,
    STYLE,
    BUYER,
    SUPPLIER,
    KNIT_M_DESCRIPTION,
    MCNO,
    QTY,
    SHIFT,
    YARN_TYPE,
    YARN_COUNT,
    FABRICS_TYPE,
    FINISH_GSM,
    FINISH_DIA,
    OPEN_TUBE,
    LOT_NO,
    KNIT_MATERIAL_CODE
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($db, $insertSql);

if (!$stmt) {
    mysqli_rollback($db);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . mysqli_error($db)]);
    mysqli_close($db);
    exit();
}

$insertedCount = 0;

foreach ($mcnoQtyData as $row) {
    $mcno = isset($row['mcno']) ? trim($row['mcno']) : '';
    $qty = isset($row['qty']) ? (float)$row['qty'] : 0;
    $shift = isset($row['shift']) ? trim($row['shift']) : '';

    if ($mcno === '' || $qty <= 0 || $shift === '') {
        mysqli_rollback($db);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Each MCNO row needs a valid MCNO, quantity, and shift.']);
        mysqli_stmt_close($stmt);
        mysqli_close($db);
        exit();
    }

    // Generate unique SUB_TID
    $currentSubTid = $nextSubTid++;

   mysqli_stmt_bind_param(
    $stmt,
    "iisssssssdsssssssss",
    $mainTid,            // i
    $currentSubTid,      // i
    $booking,            // s
    $sono,               // s
    $style,              // s
    $buyer,              // s
    $supplier,           // s
    $knitDescription,    // s
    $mcno,               // s
    $qty,                // d
    $shift,              // s
    $yarnType,           // s
    $yarnCount,          // s
    $fabricsType,        // s
    $finishGsm,          // s
    $finishDia,          // s
    $openTube,           // s
    $lotNo,              // s
    $knitMaterialCode    // s
);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_rollback($db);
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Insert failed for MCNO ' . $mcno . ': ' . mysqli_stmt_error($stmt)
        ]);
        mysqli_stmt_close($stmt);
        mysqli_close($db);
        exit();
    }

    $insertedCount++;
}

mysqli_stmt_close($stmt);

// Commit transaction
if (!mysqli_commit($db)) {
    mysqli_rollback($db);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to commit save transaction.']);
    mysqli_close($db);
    exit();
}

$response = [
    'success' => true,
    'message' => 'Program saved successfully.',
    'inserted_count' => $insertedCount
];

echo json_encode($response);
mysqli_close($db);
?>