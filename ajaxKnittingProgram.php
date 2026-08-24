<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once 'config.php';

if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection failed'
    ]);
    exit();
}

$search = isset($_GET['program_no']) && trim($_GET['program_no']) !== ''
        ? trim($_GET['program_no'])
        : (isset($_GET['booking']) && trim($_GET['booking']) !== ''
            ? trim($_GET['booking'])
            : (isset($_POST['program_no']) ? trim($_POST['program_no']) : ''));

if (isset($_GET['action']) && $_GET['action'] === 'finish_gsm_list') {
    $fgRes = mysqli_query($db, "SELECT DISTINCT TRIM(FINISH_GSM) AS FG
                                FROM knitting_input
                                WHERE FINISH_GSM IS NOT NULL AND TRIM(FINISH_GSM) <> ''
                                ORDER BY CAST(TRIM(FINISH_GSM) AS UNSIGNED), TRIM(FINISH_GSM)");
    $fgList = [];
    if ($fgRes) {
        while ($fgRow = mysqli_fetch_assoc($fgRes)) {
            $fgList[] = $fgRow['FG'];
        }
    }
    echo json_encode(['success' => true, 'data' => $fgList]);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'color_list') {
    $colorRes = mysqli_query($db, "SELECT DISTINCT TRIM(COLOR) AS COLOR_VALUE
                                   FROM knitting_input
                                   WHERE COLOR IS NOT NULL AND TRIM(COLOR) <> ''
                                   ORDER BY TRIM(COLOR)");
    $colorList = [];
    if ($colorRes) {
        while ($colorRow = mysqli_fetch_assoc($colorRes)) {
            $colorList[] = $colorRow['COLOR_VALUE'];
        }
    }
    echo json_encode(['success' => true, 'data' => $colorList]);
    exit();
}

if ($search === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'PROGRAM_NO or PO/Booking number is required'
    ]);
    exit();
}

$s = mysqli_real_escape_string($db, $search);


$progQuery = "SELECT * FROM knitting_program WHERE PROGRAM_NO = '$s' OR KPTID = '$s' LIMIT 1";
$progRes = mysqli_query($db, $progQuery);

if ($progRes && mysqli_num_rows($progRes) > 0) {
    $progData = mysqli_fetch_assoc($progRes);
    $bookingVal = mysqli_real_escape_string($db, $progData['PO_NUMBER'] ?? '');
    
    $inputData = [];
    if ($bookingVal !== '') {
        $inQ = mysqli_query($db, "SELECT * FROM knitting_input WHERE PO_NUMBER = '$bookingVal' LIMIT 1");
        if ($inQ && mysqli_num_rows($inQ) > 0) {
            $inputData = mysqli_fetch_assoc($inQ);
        }
    }
    
    $mergedData = array_merge($inputData, array_filter($progData, function($val) { return $val !== null && $val !== ''; }));
    $mergedData['KNITTING_TARGET_QTY'] = $progData['QTY'] ?? ($inputData['QTY'] ?? 0);
    $mergedData['PROGRAM_NO'] = $progData['PROGRAM_NO'];
    $mergedData['PO_NUMBER'] = $progData['PO_NUMBER'];
    $mergedData['BOOKING'] = $progData['PO_NUMBER'];
    
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

$query = "SELECT 
    PO_NUMBER, 
    BUYER, 
    SONO,
    STYLE,
    COLOR,
    CUSTOMER,
    YARN_TYPE,
    FABRICS_TYPE,
    FINISH_GSM, 
    FINISH_DIA, 
    OPEN_TUBE, 
    KNIT_MATERIAL_CODE,
    KNIT_M_DESCRIPTION, 
    QTY,
    BUDAT
FROM knitting_input 
WHERE PO_NUMBER = '$s'";

$result = mysqli_query($db, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Query error: ' . mysqli_error($db)
    ]);
    exit();
}

$allData = [];
$descriptions = [];
$firstRow = null;

while ($row = mysqli_fetch_assoc($result)) {
    $row['BOOKING'] = isset($row['PO_NUMBER']) ? $row['PO_NUMBER'] : '';
    $row['CUSTOMER'] = isset($row['CUSTOMER']) ? $row['CUSTOMER'] : '';
    $row['SONO'] = isset($row['SONO']) ? $row['SONO'] : '';
    $row['SL_VDQ'] = isset($row['SL_VDQ']) ? $row['SL_VDQ'] : '';
    $row['MC_DIA'] = isset($row['MC_DIA']) ? $row['MC_DIA'] : '';
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
        'error' => 'No data found for PO NO: ' . $search
    ]);
    exit();
}

$data = $firstRow;

$response = [
    'success' => true,
    'data' => $data,
    'all_data' => $allData,
    'descriptions' => array_values(array_unique($descriptions))
];

$allocated = 0;
$allocatedByDesc = [];
try {
    $allocQuery = "SELECT KNIT_M_DESCRIPTION, IFNULL(SUM(QTY),0) AS allocated_qty FROM knitting_program WHERE PO_NUMBER = '$s' GROUP BY KNIT_M_DESCRIPTION";
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
$response['remaining_qty'] = (float)($data['KNITTING_TARGET_QTY'] ?? $data['QTY'] ?? 0) - ($allocatedByDesc[$data['KNIT_M_DESCRIPTION']] ?? 0);

echo json_encode($response);
?>