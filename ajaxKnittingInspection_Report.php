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
    $where = "WHERE TRIM(ROLL) LIKE '%$s%'
        OR TRIM(PO_NUMBER) LIKE '%$s%'
        OR TRIM(SONO) LIKE '%$s%'
        OR TRIM(BUYER) LIKE '%$s%'
        OR TRIM(STYLE) LIKE '%$s%'";
}

$sql = "SELECT `KITID`, `BUDAT`, `ROLL`, `OQTY`, `RQTY`, `UQTY`, `PO_NUMBER`, `QTY`, `SONO`, `BUYER`, `STYLE`,
               `COLOR`, `MCNO`, `MC_DIA`, `CUSTOMER`, `SHIFT`, `YTYPE`, `YCOUNT`, `FTYPE`, `FGSM`, `FDIA`,
               `O_T`, `SL`, `GGSM`, `FPLAN`, `LOTNO`, `MATERIAL_CODE`, `M_DES`,
               `TT`, `PATTA`, `SLUB`, `YC_SPOT`, `OILSPOT`, `FF`, `SEEDS`, `MSTITCH`, `SINKERMARK`,
               `NEEDLEMARK`, `LYCOUT`, `OILLINE`, `HOLE`, `LOOP`, `SETUP`, `CMARK`, `TPOINT`,
               `QC_GRADE`, `QC_STATUS`, `UNAME`, `UID`, `P_CREATED`
        FROM knitting_inspection
        $where
        ORDER BY KITID DESC
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
    $data[] = $row;
}

echo json_encode(['success' => true, 'count' => count($data), 'data' => $data]);

mysqli_close($db);
?>
