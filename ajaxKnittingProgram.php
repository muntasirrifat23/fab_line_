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

// Get booking parameter
$booking = isset($_GET['booking']) ? trim($_GET['booking']) : '';

if ($booking === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Booking number is required'
    ]);
    exit();
}

// Escape the booking number
$b = mysqli_real_escape_string($db, $booking);

// Get all data for this booking
$query = "SELECT 
    BOOKING, 
    SUPPLIER, 
    STYLE,
    BUYER, 
    YARN_TYPE, 
    YARN_COUNT, 
    FABRICS_TYPE, 
    FINISH_GSM, 
    FINISH_DIA, 
    OPEN_TUBE, 
    SONO, 
    LOT_NO, 
    KNIT_M_DESCRIPTION, 
    KNIT_MATERIAL_CODE,
    KNITTING_TARGET_QTY 
FROM knitting_input 
WHERE BOOKING = '$b'";

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
        'error' => 'No data found for booking: ' . $booking
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
$allocQuery = "SELECT KNIT_M_DESCRIPTION, IFNULL(SUM(QTY),0) AS allocated_qty FROM knitting_program WHERE BOOKING = '$b' GROUP BY KNIT_M_DESCRIPTION";
$allocRes = mysqli_query($db, $allocQuery);
$allocated = 0;
$allocatedByDesc = [];
if ($allocRes) {
    while ($ar = mysqli_fetch_assoc($allocRes)) {
        $desc = isset($ar['KNIT_M_DESCRIPTION']) ? $ar['KNIT_M_DESCRIPTION'] : '';
        $qty = isset($ar['allocated_qty']) ? (float)$ar['allocated_qty'] : 0;
        $allocatedByDesc[$desc] = $qty;
        $allocated += $qty;
    }
}

$response['allocated_qty'] = $allocated;
$response['allocated_by_description'] = $allocatedByDesc;

// remaining for the default data row (firstRow); clients should use per-description remaining when available
$response['remaining_qty'] = (float)$data['KNITTING_TARGET_QTY'] - ($allocatedByDesc[$data['KNIT_M_DESCRIPTION']] ?? 0);
// ];

echo json_encode($response);
?>