<?php
include 'config.php';
require_once 'dyeing_process_fields.php';

header('Content-Type: application/json');

try {
    ensureDyeingBatchQuantityColumns($db);
    ensureDyeingBatchCategoryColumn($db);
    ensureDyeingProcessColumns($db, 'dyeing_batch_card');
    ensureDyeingBatchCreatorColumns($db);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $error->getMessage()]);
    mysqli_close($db);
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$batchCard = isset($_GET['batch']) ? trim($_GET['batch']) : '';

$conditions = [];

if ($batchCard !== '') {
    $conditions[] = 'BCARD = ?';
} elseif ($search !== '') {
    $s = mysqli_real_escape_string($db, $search);
    $conditions[] = "(
        BCARD LIKE '%$s%'
        OR PO_NUMBER LIKE '%$s%'
        OR SONO LIKE '%$s%'
        OR BUYER LIKE '%$s%'
        OR STYLE LIKE '%$s%'
        OR COLOR LIKE '%$s%'
        OR CATEGORY LIKE '%$s%'
        OR FTYPE LIKE '%$s%'
    )";
}

$where = '';
if (count($conditions) > 0) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

$query = "SELECT DBCTID, BCARD, BUDAT, PO_NUMBER, KQTY, AQTY, RQTY AS BATCH_QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
                 CATEGORY, BODY_QTY, RIB_QTY, NECK_QTY, DYMCNO, MCDIA, CUSTOMER, YTYPE, YCOUNT, YBRAND, SILICON, NONSILICON,
                 NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP, SOFTENER,
                 NONSOFTENER, OTHERS, SHEARING, SINGEING, HEAT_SET, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN,
                 LOT_NO, TPOINT, DID, DNAME, CREATED_DATE, CATEGORY
          FROM dyeing_batch_card
          $where
          ORDER BY CAST(BCARD AS UNSIGNED) DESC, DBCTID ASC
          " . ($batchCard === '' ? 'LIMIT 500' : '');

$stmt = mysqli_prepare($db, $query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    mysqli_close($db);
    exit;
}

$executed = $batchCard === ''
    ? mysqli_stmt_execute($stmt)
    : (mysqli_stmt_bind_param($stmt, 's', $batchCard) && mysqli_stmt_execute($stmt));
$result = $executed ? mysqli_stmt_get_result($stmt) : false;
if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt) ?: mysqli_error($db)]);
    mysqli_stmt_close($stmt);
    mysqli_close($db);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

mysqli_stmt_close($stmt);
echo json_encode(['success' => true, 'count' => count($data), 'data' => $data]);

mysqli_close($db);
?>