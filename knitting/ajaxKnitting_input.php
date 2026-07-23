<?php
include 'config.php';

header('Content-Type: application/json');

$booking = isset($_GET['booking']) ? trim($_GET['booking']) : '';

$conditions = [];
if ($booking !== '') {
    $b = mysqli_real_escape_string($db, $booking);
    $conditions[] = "(BOOKING LIKE '%$b%' OR SONO LIKE '%$b%')";
}

$where = '';
if (count($conditions) > 0) $where = 'WHERE ' . implode(' AND ', $conditions);

$query = "SELECT *, KITID AS KID, KITID AS KPID FROM knitting_input $where ORDER BY KITID DESC";
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