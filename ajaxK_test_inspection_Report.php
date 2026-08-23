<?php
// ajaxK_test_inspection_Report.php
include 'config.php';

header('Content-Type: application/json');

if (!isset($db) || !$db) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$TABLE = 'knitting_inspection';

if ($search !== '') {
    $s = mysqli_real_escape_string($db, $search);
    $sql = "SELECT * FROM $TABLE
            WHERE TRIM(ROLL) LIKE '%$s%'
               OR TRIM(PO_NUMBER) LIKE '%$s%'
               OR TRIM(SONO) LIKE '%$s%'
            ORDER BY KITID DESC";
} else {
    $sql = "SELECT * FROM $TABLE ORDER BY KITID DESC";
}

$result = mysqli_query($db, $sql);

if ($result) {
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Clean trailing whitespace/newlines from ROLL
        if (isset($row['ROLL'])) {
            $row['ROLL'] = trim($row['ROLL']);
        }
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
}
?>