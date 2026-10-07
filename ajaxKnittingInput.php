<?php
include 'config.php';

header('Content-Type: application/json');

$booking = trim($_GET['booking'] ?? $_GET['po_number'] ?? $_GET['po'] ?? $_GET['sono'] ?? $_GET['search'] ?? $_POST['booking'] ?? $_POST['po_number'] ?? $_POST['po'] ?? '');

$conditions = [];
if ($booking !== '') {
    $b = mysqli_real_escape_string($db, $booking);
    $conditions[] = "(PO_NUMBER LIKE '%$b%' OR SONO LIKE '%$b%' OR BUYER LIKE '%$b%' OR STYLE LIKE '%$b%')";
}

$where = '';
if (count($conditions) > 0) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

$query = "SELECT * FROM knitting_input $where ORDER BY KITID DESC";
$result = mysqli_query($db, $query);

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