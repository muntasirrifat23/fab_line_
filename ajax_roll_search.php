<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit();
}

$roll = isset($_REQUEST['roll']) ? trim($_REQUEST['roll']) : '';

if (empty($roll)) {
    echo json_encode(['success' => false, 'message' => 'Please enter or select a Roll Number.']);
    exit();
}

$stmt = $db->prepare("SELECT * FROM knitting_inspection WHERE ROLL = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $db->error]);
    exit();
}

$stmt->bind_param("s", $roll);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $stmt->close();
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} else {
    $stmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'No inspection record found for Roll Number: ' . htmlspecialchars($roll)
    ]);
}
exit();
?>
