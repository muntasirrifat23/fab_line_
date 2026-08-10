<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Include config
require_once 'config.php';

// Check if database connection exists
if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection failed'
    ]);
    exit();
}

// Get search parameter (SUB_TID or PO/Booking number)
$search = isset($_GET['sub_tid']) && trim($_GET['sub_tid']) !== '' 
        ? trim($_GET['sub_tid']) 
        : (isset($_GET['booking']) && trim($_GET['booking']) !== '' 
            ? trim($_GET['booking']) 
            : (isset($_POST['sub_tid']) ? trim($_POST['sub_tid']) : ''));

if ($search === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'SUB_TID or PO/Booking number is required'
    ]);
    exit();
}

// Escape the parameter
$s = mysqli_real_escape_string($db, $search);

// 1. First try searching knitting_program table by SUB_TID or KPTID
$progQuery = "SELECT * FROM knitting_program WHERE SUB_TID = '$s' OR KPTID = '$s' LIMIT 1";
$progRes = mysqli_query($db, $progQuery);

if ($progRes && mysqli_num_rows($progRes) > 0) {
    $progData = mysqli_fetch_assoc($progRes);
    $bookingVal = mysqli_real_escape_string($db, $progData['BOOKING'] ?? '');
    
    // Fetch input record for supplementary details if available
    $inputData = [];
    if ($bookingVal !== '') {
        $inQ = mysqli_query($db, "SELECT * FROM knitting_input WHERE BOOKING = '$bookingVal' LIMIT 1");
        if ($inQ && mysqli_num_rows($inQ) > 0) {
            $inputData = mysqli_fetch_assoc($inQ);
        }
    }
    
    $mergedData = array_merge($inputData, array_filter($progData, function($val) { return $val !== null && $val !== ''; }));
    $mergedData['KNITTING_TARGET_QTY'] = $progData['QTY'] ?? ($inputData['KNITTING_TARGET_QTY'] ?? 0);
    $mergedData['SUB_TID'] = $progData['SUB_TID'];
    $mergedData['BOOKING'] = $progData['BOOKING'];
    
    echo json_encode([
        'success' => true,
        'data' => $mergedData,
        'all_data' => [$mergedData],
        'descriptions' => [$mergedData['KNIT_M_DESCRIPTION'] ?? ''],
        'allocated_qty' => (float)($progData['QTY'] ?? 0),
        'remaining_qty' => 0
    ]);
    exit();
}

// 2. Fallback: Search knitting_input table by BOOKING number
$query = "SELECT 
    BOOKING, 
    BUYER, 
    SONO,
    STYLE,
    COLOR,
    YARN_TYPE, 
    FABRICS_TYPE, 
    FINISH_GSM, 
    FINISH_DIA, 
    OPEN_TUBE, 
    KNIT_MATERIAL_CODE,
    KNIT_M_DESCRIPTION, 
    KNITTING_TARGET_QTY,
    BUDAT
FROM knitting_input 
WHERE BOOKING = '$s'";

$result = mysqli_query($db, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Query error: ' . mysqli_error($db)
    ]);
    exit();
}

// Get all rows for this booking
$allData = [];
$descriptions = [];
$firstRow = null;

while ($row = mysqli_fetch_assoc($result)) {
    // Add default values for fields that don't exist in your table
    $row['SUPPLIER'] = isset($row['SUPPLIER']) ? $row['SUPPLIER'] : '';
    $row['SONO'] = isset($row['SONO']) ? $row['SONO'] : '';
    $row['SL_VDQ'] = isset($row['SL_VDQ']) ? $row['SL_VDQ'] : '';
    $row['MC_DIA'] = isset($row['MC_DIA']) ? $row['MC_DIA'] : '';
    $row['LOT_NO'] = isset($row['LOT_NO']) ? $row['LOT_NO'] : '';
    $row['YARN_COUNT'] = isset($row['YARN_COUNT']) ? $row['YARN_COUNT'] : '';
    
    $allData[] = $row;
    if ($firstRow === null) {
        $firstRow = $row;
    }
    if (!empty($row['KNIT_M_DESCRIPTION'])) {
        $descriptions[] = $row['KNIT_M_DESCRIPTION'];
    }
}

if (empty($allData)) {
    echo json_encode([
        'success' => false, 
        'error' => 'No data found for SUB_TID / PO NO: ' . $search
    ]);
    exit();
}

// Use first row as base data
$data = $firstRow;

// Return all data with descriptions
$response = [
    'success' => true,
    'data' => $data,
    'all_data' => $allData,
    'descriptions' => array_values(array_unique($descriptions))
];

// Calculate already allocated qty from knitting_program table for this booking
$allocated = 0;
$allocatedByDesc = [];
try {
    $allocQuery = "SELECT KNIT_M_DESCRIPTION, IFNULL(SUM(QTY),0) AS allocated_qty FROM knitting_program WHERE BOOKING = '$s' GROUP BY KNIT_M_DESCRIPTION";
    $allocRes = mysqli_query($db, $allocQuery);
    if ($allocRes) {
        while ($ar = mysqli_fetch_assoc($allocRes)) {
            $desc = isset($ar['KNIT_M_DESCRIPTION']) ? $ar['KNIT_M_DESCRIPTION'] : '';
            $qty = isset($ar['allocated_qty']) ? (float)$ar['allocated_qty'] : 0;
            $allocatedByDesc[$desc] = $qty;
            $allocated += $qty;
        }
    }
} catch (Throwable $e) {
    // knitting_program table is missing/unavailable - treat allocation as zero
}

$response['allocated_qty'] = $allocated;
$response['allocated_by_description'] = $allocatedByDesc;

// remaining for the default data row (firstRow); clients should use per-description remaining when available
$response['remaining_qty'] = (float)$data['KNITTING_TARGET_QTY'] - ($allocatedByDesc[$data['KNIT_M_DESCRIPTION']] ?? 0);

echo json_encode($response);
?>