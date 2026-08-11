<?php
date_default_timezone_set('Asia/Dhaka');

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
$mcDia = isset($_POST['mc_dia']) ? trim($_POST['mc_dia']) : null;
$finishGsm = isset($_POST['finish_gsm']) ? trim($_POST['finish_gsm']) : null;
$finishDia = isset($_POST['finish_dia']) ? trim($_POST['finish_dia']) : null;
$openTube = isset($_POST['open_tube']) ? trim($_POST['open_tube']) : null;
$lotNo = isset($_POST['lot_no']) ? trim($_POST['lot_no']) : null;
$knitMaterialCode = isset($_POST['knit_material_code']) ? trim($_POST['knit_material_code']) : null;
$color = isset($_POST['color']) ? trim($_POST['color']) : null;
$slVdq = isset($_POST['sl_vdq']) ? trim($_POST['sl_vdq']) : null;
$grayGsm = isset($_POST['gray_gsm']) ? trim($_POST['gray_gsm']) : null;
$feederPlan = isset($_POST['feeder_plan']) ? trim($_POST['feeder_plan']) : null;
$mcnoQtyData = isset($_POST['mcno_qty']) ? $_POST['mcno_qty'] : [];

// Auto-detect SHIFT based on Bangladesh time
date_default_timezone_set('Asia/Dhaka');

$bdHour = (int)date('G');
if ($bdHour >= 6 && $bdHour < 14) {
    $shift = 'A';
} elseif ($bdHour >= 14 && $bdHour < 22) {
    $shift = 'B';
} else {
    $shift = 'C';
}

// Validate required fields
if ($booking === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking number is required.']);
    mysqli_close($db);
    exit();
}

if (!is_array($mcnoQtyData) || empty($mcnoQtyData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one quantity row is required.']);
    mysqli_close($db);
    exit();
}

// Reuse the existing MAIN_TID for this booking if already saved, otherwise create a new MAIN_TID
$escapedBooking = mysqli_real_escape_string($db, $booking);
$mainTid = null;

$existingMainResult = mysqli_query($db, "SELECT MAIN_TID FROM knitting_program WHERE PO_NUMBER = '$escapedBooking' ORDER BY KPTID DESC LIMIT 1");
if ($existingMainResult && mysqli_num_rows($existingMainResult) > 0) {
    $existingRow = mysqli_fetch_assoc($existingMainResult);
    $mainTid = intval($existingRow['MAIN_TID']);
    mysqli_free_result($existingMainResult);
}

if ($mainTid > 0) {
    $tidResult = mysqli_query($db, "SELECT COALESCE(MAX(SUB_TID), 2000000000) AS max_sub FROM knitting_program");
    if (!$tidResult) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to determine SUB_TID: ' . mysqli_error($db)
        ]);
        mysqli_close($db);
        exit();
    }

    $tidRow = mysqli_fetch_assoc($tidResult);
    $nextSubTid = intval($tidRow['max_sub']) + 1;
    if ($nextSubTid < 2000000001) {
        $nextSubTid = 2000000001;
    }
    mysqli_free_result($tidResult);
} else {
    $tidResult = mysqli_query($db, "
        SELECT
            COALESCE(MAX(MAIN_TID), 1000000000) AS max_main,
            COALESCE(MAX(SUB_TID), 2000000000) AS max_sub
        FROM knitting_program
    ");

    if (!$tidResult) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to determine IDs: ' . mysqli_error($db)
        ]);
        mysqli_close($db);
        exit();
    }

    $tidRow = mysqli_fetch_assoc($tidResult);
    $mainTid = intval($tidRow['max_main']) + 1;
    if ($mainTid < 1000000001) {
        $mainTid = 1000000001;
    }
    $nextSubTid = intval($tidRow['max_sub']) + 1;
    if ($nextSubTid < 2000000001) {
        $nextSubTid = 2000000001;
    }
    mysqli_free_result($tidResult);
}

// Start transaction
if (!mysqli_begin_transaction($db)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to start database transaction.']);
    mysqli_close($db);
    exit();
}

// Prepare insert statement
$insertSql = "INSERT INTO knitting_program (
    MAIN_TID,
    SUB_TID,
    PO_NUMBER,
    SONO,
    STYLE,
    BUYER,
    COLOR,
    QTY,
    FGSM,
    FDIA,
    O_T,
    FTYPE,
    YTYPE,
    SUPPLIER,
    YCOUNT,
    SL,
    MCDIA,
    GGSM,
    FEEDER_PLAN,
    LOT,
    SHIFT,
    KNIT_MATERIAL_CODE,
    KNIT_M_DESCRIPTION
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
    $qty = isset($row['qty']) ? (float)$row['qty'] : 0;

    if ($qty <= 0) {
        mysqli_rollback($db);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Each row needs a valid quantity.']);
        mysqli_stmt_close($stmt);
        mysqli_close($db);
        exit();
    }

    // Generate unique SUB_TID
    $currentSubTid = $nextSubTid++;

    mysqli_stmt_bind_param(
        $stmt,
        "iisssssdsssssssssssssss",
        $mainTid,
        $currentSubTid,
        $booking,
        $sono,
        $style,
        $buyer,
        $color,
        $qty,
        $finishGsm,
        $finishDia,
        $openTube,
        $fabricsType,
        $yarnType,
        $supplier,
        $yarnCount,
        $slVdq,
        $mcDia,
        $grayGsm,
        $feederPlan,
        $lotNo,
        $shift,
        $knitMaterialCode,
        $knitDescription
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_rollback($db);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Insert failed for QTY ' . $qty . ': ' . mysqli_stmt_error($stmt)
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
    'message' => 'Program saved successfully. ' . $insertedCount . ' record(s) inserted. Shift: ' . $shift,
    'inserted_count' => $insertedCount,
    'main_tid' => $mainTid,
    'shift' => $shift
];

echo json_encode($response);
mysqli_close($db);
