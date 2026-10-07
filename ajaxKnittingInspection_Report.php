<?php
// ajaxKnittingInspection_Report.php
include 'config.php';

header('Content-Type: application/json');

if (!isset($db) || !$db) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = '';
if ($search !== '') {
    $s = mysqli_real_escape_string($db, $search);
    $where = "WHERE TRIM(i.ROLL) LIKE '%$s%'
        OR TRIM(i.PO_NUMBER) LIKE '%$s%'
        OR TRIM(i.SONO) LIKE '%$s%'
        OR TRIM(i.BUYER) LIKE '%$s%'
        OR TRIM(i.STYLE) LIKE '%$s%'";
}

$sql = "SELECT i.*,
               COALESCE(
                   NULLIF(TRIM(i.YBRAND), ''),
                   (SELECT NULLIF(TRIM(k.YBRAND), '') FROM knit_card k WHERE TRIM(k.PO_NUMBER) = TRIM(i.PO_NUMBER) LIMIT 1),
                   (SELECT NULLIF(TRIM(p.YBRAND), '') FROM knitting_program p WHERE TRIM(p.PO_NUMBER) = TRIM(i.PO_NUMBER) LIMIT 1)
               ) AS YBRAND
        FROM knitting_inspection i
        $where
        ORDER BY i.KITID DESC
        LIMIT 500";

try {
    $result = mysqli_query($db, $sql);
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
    if (isset($row['ROLL'])) {
        $row['ROLL'] = trim($row['ROLL']);
    }
    $row['MAIN_QTY']   = $row['OQTY'] ?? '';
    $row['REJECT_QTY'] = $row['RQTY'] ?? '';
    $row['UPDATE_QTY'] = $row['UQTY'] ?? '';
    $data[] = $row;
}

echo json_encode(['success' => true, 'count' => count($data), 'data' => $data]);

mysqli_close($db);
?>
