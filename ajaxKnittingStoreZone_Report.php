<?php
include 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($db) || !$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$where = '';
if ($search !== '') {
    $escapedSearch = mysqli_real_escape_string($db, $search);
    $where = "WHERE (
        ROLL LIKE '%$escapedSearch%'
        OR PO_NUMBER LIKE '%$escapedSearch%'
        OR SONO LIKE '%$escapedSearch%'
        OR BUYER LIKE '%$escapedSearch%'
        OR STYLE LIKE '%$escapedSearch%'
        OR COLOR LIKE '%$escapedSearch%'
        OR MCNO LIKE '%$escapedSearch%'
        OR YBRAND LIKE '%$escapedSearch%'
    )";
}

$query = "SELECT *
    FROM knitting_store_zone
    $where
    ORDER BY KSZTID DESC
    LIMIT 500";

try {
    $result = mysqli_query($db, $query);
} catch (mysqli_sql_exception $e) {
    error_log('Store Common Zone report query failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load Store Common Zone report data.']);
    exit;
}

if (!$result) {
    error_log('Store Common Zone report query failed: ' . mysqli_error($db));
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load Store Common Zone report data.']);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(['success' => true, 'count' => count($data), 'data' => $data]);
