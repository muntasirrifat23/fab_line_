<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

function respond($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['username'])) {
    respond(['success' => false, 'message' => 'Unauthorized. Please login.'], 401);
}

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
if ($action !== 'load_batch_by_roll') {
    respond(['success' => false, 'message' => 'Unsupported Roll Verification request.'], 400);
}

$roll = isset($_GET['roll']) ? trim((string) $_GET['roll']) : '';
if ($roll === '' || strlen($roll) > 50) {
    respond(['success' => false, 'message' => 'Enter a valid roll number.'], 400);
}

$batchStmt = $db->prepare(
    'SELECT DISTINCT BCARD
     FROM dyeing_batch_delivery
     WHERE TRIM(ROLL) = ? AND BCARD IS NOT NULL AND TRIM(BCARD) <> \'\''
);
if (!$batchStmt) {
    respond(['success' => false, 'message' => 'Could not prepare roll lookup: ' . $db->error], 500);
}
$batchStmt->bind_param('s', $roll);
if (!$batchStmt->execute()) {
    $error = $batchStmt->error;
    $batchStmt->close();
    respond(['success' => false, 'message' => 'Could not look up the roll: ' . $error], 500);
}
$batchResult = $batchStmt->get_result();
$batchCards = [];
while ($row = $batchResult->fetch_assoc()) {
    $batchCards[] = trim((string) $row['BCARD']);
}
$batchResult->free();
$batchStmt->close();

if (!$batchCards) {
    respond(['success' => false, 'message' => 'Roll ' . $roll . ' was not found in dyeing_batch_delivery.'], 404);
}
if (count($batchCards) > 1) {
    respond([
        'success' => false,
        'message' => 'Roll ' . $roll . ' belongs to more than one batch card. Please resolve the duplicate roll assignment.'
    ], 409);
}

$card = $batchCards[0];
$rowsStmt = $db->prepare(
    'SELECT DBDTID, BCARD, ROLL, PO_NUMBER, COLOR, QTY, DYMCNO, SONO, BUYER, STYLE,
            MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, YTYPE, YCOUNT, YBRAND,
            FEEDER_PLAN, LOT_NO, CATEGORY
     FROM dyeing_batch_delivery
     WHERE BCARD = ?
     ORDER BY DBDTID ASC'
);
if (!$rowsStmt) {
    respond(['success' => false, 'message' => 'Could not prepare batch roll lookup: ' . $db->error], 500);
}
$rowsStmt->bind_param('s', $card);
if (!$rowsStmt->execute()) {
    $error = $rowsStmt->error;
    $rowsStmt->close();
    respond(['success' => false, 'message' => 'Could not load batch rolls: ' . $error], 500);
}

$result = $rowsStmt->get_result();
$rows = [];
$seenRolls = [];
while ($row = $result->fetch_assoc()) {
    $rowRoll = trim((string) $row['ROLL']);
    if ($rowRoll === '') {
        $rowsStmt->close();
        respond(['success' => false, 'message' => 'Batch ' . $card . ' contains a blank roll number.'], 500);
    }
    if (isset($seenRolls[$rowRoll])) {
        $rowsStmt->close();
        respond(['success' => false, 'message' => 'Batch ' . $card . ' contains duplicate roll ' . $rowRoll . '.'], 500);
    }
    $seenRolls[$rowRoll] = true;
    $row['ROLL'] = $rowRoll;
    $rows[] = $row;
}
$rowsStmt->close();

if (!$rows) {
    respond(['success' => false, 'message' => 'No delivery rolls were found for Batch ' . $card . '.'], 404);
}

respond([
    'success' => true,
    'card_no' => $card,
    'roll_count' => count($rows),
    'rows' => $rows
]);
