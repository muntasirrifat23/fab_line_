<?php
include 'config.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$conditions = [];

if ($search !== '') {

    $s = mysqli_real_escape_string($db, $search);

    $conditions[] = "(
        ROLL LIKE '%$s%' 
        OR BOOKING_NO LIKE '%$s%'
        OR SONO LIKE '%$s%'
        OR MCNO LIKE '%$s%'
    )";
}

$where = '';

if (count($conditions) > 0) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}


$query = "SELECT * 
          FROM knitting_production 
          $where 
          ORDER BY PID DESC";


$result = mysqli_query($db, $query);


if (!$result) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => mysqli_error($db)
    ]);

    mysqli_close($db);
    exit;
}


$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}


echo json_encode([
    'success' => true,
    'count' => count($data),
    'data' => $data
]);


mysqli_close($db);

?>