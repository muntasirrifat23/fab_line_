<?php
include 'config.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$conditions = [];

if ($search !== '') {
    $s = mysqli_real_escape_string($db, $search);
    $conditions[] = "(
        ROLL LIKE '%$s%'
        OR PO_NUMBER LIKE '%$s%'
        OR SONO LIKE '%$s%'
        OR BUYER LIKE '%$s%'
        OR STYLE LIKE '%$s%'
        OR COLOR LIKE '%$s%'
        OR RACKNO LIKE '%$s%'
        OR RACKLOCATION LIKE '%$s%'
        OR MCNO LIKE '%$s%'
    )";
}

$where = '';
if (count($conditions) > 0) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

$query = "SELECT KSTID, BUDAT, RACKNO, RACKLOCATION, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
                  MCNO, MCDIA, CUSTOMER, YTYPE, YCOUNT, O_T, SL, FTYPE, FGSM, FDIA, GGSM,
                  FEEDER_PLAN, LOT_NO, TPOINT, MCODE, MDESCRIPTION, CREATED_DATE, UNAME, UID
          FROM knitting_store
          $where
          ORDER BY KSTID DESC
          LIMIT 500";

try {
    $result = mysqli_query($db, $query);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    mysqli_close($db);
    exit;
}

if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    mysqli_close($db);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(['success' => true, 'count' => count($data), 'data' => $data]);

mysqli_close($db);
?>
