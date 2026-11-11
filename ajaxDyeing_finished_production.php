<?php
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';
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
$action = isset($_GET['action']) && is_string($_GET['action']) ? trim($_GET['action']) : '';
if ($action === 'authorize_manual_entry') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'Use POST to unlock manual entry.'], 405);
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $password = is_array($input) && isset($input['password']) && is_string($input['password'])
        ? $input['password']
        : '';
    $lockUntil = isset($_SESSION['finished_production_manual_lock_until'])
        ? (int) $_SESSION['finished_production_manual_lock_until']
        : 0;
    if ($lockUntil > time()) {
        respond(['success' => false, 'message' => 'Too many attempts. Please wait one minute and try again.'], 429);
    }

    $passwordHash = '$2y$10$76SxpOic/qcxrHeK5L8VqOpTCCBOwIgXDaXj6aAix0pNzci/dZGUS';
    if (!password_verify($password, $passwordHash)) {
        $attempts = isset($_SESSION['finished_production_manual_attempts'])
            ? (int) $_SESSION['finished_production_manual_attempts'] + 1
            : 1;
        if ($attempts >= 5) {
            $_SESSION['finished_production_manual_attempts'] = 0;
            $_SESSION['finished_production_manual_lock_until'] = time() + 60;
            respond(['success' => false, 'message' => 'Too many attempts. Please wait one minute and try again.'], 429);
        }
        $_SESSION['finished_production_manual_attempts'] = $attempts;
        respond(['success' => false, 'message' => 'Incorrect password. Manual entry remains locked.'], 403);
    }

    unset($_SESSION['finished_production_manual_attempts'], $_SESSION['finished_production_manual_lock_until']);
    $_SESSION['finished_production_manual_authorized'] = true;
    respond(['success' => true, 'message' => 'Manual entry unlocked.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['success' => false, 'message' => 'Use GET to look up an operator or finished roll.'], 405);
}

$manual = isset($_GET['manual']) && $_GET['manual'] === '1';
if ($manual && empty($_SESSION['finished_production_manual_authorized'])) {
    respond(['success' => false, 'message' => 'Verify the manual-entry password before entering an operator ID or roll.'], 403);
}

$operatorId = isset($_GET['operator_id']) && is_scalar($_GET['operator_id'])
    ? trim((string) $_GET['operator_id'])
    : '';
if ($action === 'verify_operator') {
    if ($operatorId === '' || strlen($operatorId) > 50) {
        respond(['success' => false, 'message' => 'Scan or enter a valid Finishing Operator ID.'], 400);
    }

    $operatorStmt = $db->prepare(
        'SELECT FN_OP_ID
         FROM finishing_operator
         WHERE TRIM(FN_OP_ID) = ?
         LIMIT 1'
    );
    if (!$operatorStmt) {
        respond(['success' => false, 'message' => 'Could not prepare Finishing Operator lookup: ' . $db->error], 500);
    }
    $operatorStmt->bind_param('s', $operatorId);
    if (!$operatorStmt->execute()) {
        $error = $operatorStmt->error;
        $operatorStmt->close();
        respond(['success' => false, 'message' => 'Could not verify Finishing Operator: ' . $error], 500);
    }
    $operatorResult = $operatorStmt->get_result();
    $operator = $operatorResult ? $operatorResult->fetch_assoc() : null;
    if ($operatorResult) {
        $operatorResult->free();
    }
    $operatorStmt->close();
    if (!$operator) {
        respond(['success' => false, 'message' => 'Finishing Operator ID ' . $operatorId . ' was not found.'], 404);
    }
    $_SESSION['finished_production_operator_id'] = (string) $operator['FN_OP_ID'];
    respond(['success' => true, 'operator' => $operator]);
}

if ($action !== 'get_roll') {
    respond(['success' => false, 'message' => 'Unsupported Finished Production action.'], 400);
}
if ($operatorId === '' || strlen($operatorId) > 50) {
    respond(['success' => false, 'message' => 'Scan and verify a Finishing Operator ID first.'], 400);
}
if (
    empty($_SESSION['finished_production_operator_id'])
    || !hash_equals((string) $_SESSION['finished_production_operator_id'], $operatorId)
) {
    respond(['success' => false, 'message' => 'Verify this Finishing Operator ID before scanning rolls.'], 403);
}
$operatorCheck = $db->prepare(
    'SELECT FN_OP_ID
     FROM finishing_operator
     WHERE TRIM(FN_OP_ID) = ?
     LIMIT 1'
);
if (!$operatorCheck) {
    respond(['success' => false, 'message' => 'Could not prepare Finishing Operator check: ' . $db->error], 500);
}
$operatorCheck->bind_param('s', $operatorId);
if (!$operatorCheck->execute()) {
    $error = $operatorCheck->error;
    $operatorCheck->close();
    respond(['success' => false, 'message' => 'Could not verify Finishing Operator: ' . $error], 500);
}
$operatorResult = $operatorCheck->get_result();
$validOperator = $operatorResult && $operatorResult->num_rows > 0;
if ($operatorResult) {
    $operatorResult->free();
}
$operatorCheck->close();
if (!$validOperator) {
    respond(['success' => false, 'message' => 'Finishing Operator ID ' . $operatorId . ' was not found.'], 404);
}

$roll = isset($_GET['roll']) && is_scalar($_GET['roll'])
    ? trim((string) $_GET['roll'])
    : '';
if ($roll === '' || strlen($roll) > 50) {
    respond(['success' => false, 'message' => 'Scan or enter a valid roll number.'], 400);
}

try {
    ensureDyeingProcessColumns($db, 'dyeing_unload');
    ensureDyeingCountColumn($db, 'dyeing_unload');
    ensureDyeingCountColumn($db, 'dyeing_batch_verification');
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize Finished Production fields: ' . $error->getMessage()], 500);
}

$processFields = dyeingProcessFieldNames();
$selectFields = [
    'u.BCARD',
    'u.ROLL',
    'u.DCOUNT',
    'u.PO_NUMBER',
    'u.COLOR',
    'u.QTY',
    'u.MCNO',
    'u.SONO',
    'u.BUYER',
    'u.STYLE',
    'u.MCDIA',
    'u.CUSTOMER',
    'u.O_T',
    'u.SL',
    'u.FTYPE',
    'u.FGSM',
    'u.FDIA',
    'u.YTYPE',
    'u.YCOUNT',
    'u.YBRAND',
    'u.SHIFT',
    'u.DYSTART',
    'u.DYEND',
    'u.DOP AS OPERATOR',
    'u.DID AS OP_ID'
];
foreach ($processFields as $field) {
    $selectFields[] = 'u.`' . $field . '`';
}

$sql = 'SELECT ' . implode(', ', $selectFields) . '
        FROM dyeing_unload u
        WHERE TRIM(u.ROLL) = ?
          AND u.DCOUNT = (
              SELECT MAX(latest.DCOUNT)
              FROM dyeing_unload latest
              WHERE TRIM(latest.BCARD) = TRIM(u.BCARD)
          )
          AND NOT EXISTS (
              SELECT 1
              FROM dyeing_batch_verification redye_started
              WHERE TRIM(redye_started.BCARD) = TRIM(u.BCARD)
                AND redye_started.DCOUNT > u.DCOUNT
          )
        ORDER BY u.DULTID DESC
        LIMIT 1';
$stmt = $db->prepare($sql);
if (!$stmt) {
    respond(['success' => false, 'message' => 'Could not prepare finished roll lookup: ' . $db->error], 500);
}
$stmt->bind_param('s', $roll);
if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    respond(['success' => false, 'message' => 'Could not look up finished roll: ' . $error], 500);
}
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
if ($result) {
    $result->free();
}
$stmt->close();

if (!$row) {
    respond([
        'success' => false,
        'message' => 'Roll ' . $roll . ' was not found in Dyeing Unload, or it has already been sent to Re Dyeing.'
    ], 404);
}

respond(['success' => true, 'row' => $row, 'operator_id' => $operatorId]);
