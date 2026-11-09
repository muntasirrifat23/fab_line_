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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['success' => false, 'message' => 'Unsupported Dyeing Load request.'], 405);
}

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
if ($action === 'verify_operator') {
    if (isset($_GET['manual']) && $_GET['manual'] === '1' && empty($_SESSION['roll_manual_entry_authorized'])) {
        respond(['success' => false, 'message' => 'Verify the manual-entry password before entering an operator ID.'], 403);
    }

    $operatorId = isset($_GET['operator_id']) ? trim((string) $_GET['operator_id']) : '';
    if ($operatorId === '' || strlen($operatorId) > 50) {
        respond(['success' => false, 'message' => 'Enter a valid Dyeing Operator ID.'], 400);
    }

    $operatorStmt = $db->prepare(
        'SELECT DY_OP_ID, DY_OP_NAME
         FROM dyeing_operator
         WHERE TRIM(DY_OP_ID) = ?
         LIMIT 1'
    );
    if (!$operatorStmt) {
        respond(['success' => false, 'message' => 'Could not prepare operator lookup: ' . $db->error], 500);
    }
    $operatorStmt->bind_param('s', $operatorId);
    if (!$operatorStmt->execute()) {
        $error = $operatorStmt->error;
        $operatorStmt->close();
        respond(['success' => false, 'message' => 'Could not verify operator: ' . $error], 500);
    }

    $result = $operatorStmt->get_result();
    $operator = $result ? $result->fetch_assoc() : null;
    if ($result) {
        $result->free();
    }
    $operatorStmt->close();

    if (!$operator) {
        respond(['success' => false, 'message' => 'Dyeing Operator ID ' . $operatorId . ' was not found.'], 404);
    }
    respond(['success' => true, 'operator' => $operator]);
}

if ($action === 'load_batch') {
    if (isset($_GET['manual']) && $_GET['manual'] === '1' && empty($_SESSION['roll_manual_entry_authorized'])) {
        respond(['success' => false, 'message' => 'Verify the manual-entry password before entering a batch card.'], 403);
    }

    $operatorId = isset($_GET['operator_id']) ? trim((string) $_GET['operator_id']) : '';
    $card = isset($_GET['card_no']) ? trim((string) $_GET['card_no']) : '';
    if ($operatorId === '' || strlen($operatorId) > 50) {
        respond(['success' => false, 'message' => 'Scan a Dyeing Operator ID first.'], 400);
    }
    if ($card === '' || strlen($card) > 30) {
        respond(['success' => false, 'message' => 'Enter a valid Batch Card number.'], 400);
    }

    $operatorStmt = $db->prepare(
        'SELECT DY_OP_ID FROM dyeing_operator WHERE TRIM(DY_OP_ID) = ? LIMIT 1'
    );
    if (!$operatorStmt) {
        respond(['success' => false, 'message' => 'Could not prepare operator check: ' . $db->error], 500);
    }
    $operatorStmt->bind_param('s', $operatorId);
    if (!$operatorStmt->execute()) {
        $error = $operatorStmt->error;
        $operatorStmt->close();
        respond(['success' => false, 'message' => 'Could not check operator: ' . $error], 500);
    }
    $operatorResult = $operatorStmt->get_result();
    $validOperator = $operatorResult && $operatorResult->num_rows > 0;
    if ($operatorResult) {
        $operatorResult->free();
    }
    $operatorStmt->close();
    if (!$validOperator) {
        respond(['success' => false, 'message' => 'Dyeing Operator ID ' . $operatorId . ' was not found.'], 404);
    }

    $batchStmt = $db->prepare(
        'SELECT DRVTID, BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR,
                MCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN,
                LOT_NO, YTYPE, YCOUNT, YBRAND, CATEGORY, DOP, DID, CREATEDAT
         FROM dyeing_roll_verification
         WHERE TRIM(BCARD) = ?
         ORDER BY DRVTID ASC'
    );
    if (!$batchStmt) {
        respond(['success' => false, 'message' => 'Could not prepare batch lookup: ' . $db->error], 500);
    }
    $batchStmt->bind_param('s', $card);
    if (!$batchStmt->execute()) {
        $error = $batchStmt->error;
        $batchStmt->close();
        respond(['success' => false, 'message' => 'Could not load batch data: ' . $error], 500);
    }

    $result = $batchStmt->get_result();
    $rows = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $rows[] = $row;
    }
    if ($result) {
        $result->free();
    }
    $batchStmt->close();

    if (!$rows) {
        respond(['success' => false, 'message' => 'No verified roll data was found for Batch ' . $card . '.'], 404);
    }
    respond([
        'success' => true,
        'card_no' => $card,
        'roll_count' => count($rows),
        'rows' => $rows
    ]);
}

respond(['success' => false, 'message' => 'Unsupported Dyeing Load action.'], 400);
