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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Use POST to start a redyeing cycle.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(['success' => false, 'message' => 'Invalid redyeing request.'], 400);
}

$csrfToken = isset($input['csrf_token']) ? (string) $input['csrf_token'] : '';
if (
    empty($_SESSION['dyeing_redye_csrf']) ||
    !hash_equals((string) $_SESSION['dyeing_redye_csrf'], $csrfToken)
) {
    respond(['success' => false, 'message' => 'Your session token is invalid. Refresh the page and try again.'], 403);
}

$card = isset($input['card_no']) && is_scalar($input['card_no'])
    ? trim((string) $input['card_no'])
    : '';
if ($card === '' || strlen($card) > 30) {
    respond(['success' => false, 'message' => 'Enter a valid Batch Card number.'], 400);
}

try {
    ensureDyeingProcessColumns($db, 'dyeing_batch_verification');
    ensureDyeingProcessColumns($db, 'dyeing_unload');
    ensureDyeingCountColumn($db, 'dyeing_batch_verification');
    ensureDyeingCountColumn($db, 'dyeing_unload');
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize redyeing data: ' . $error->getMessage()], 500);
}

if (!$db->begin_transaction()) {
    respond(['success' => false, 'message' => 'Could not start the redyeing transaction: ' . $db->error], 500);
}

$unloadStmt = $db->prepare(
    'SELECT DULTID, DCOUNT
     FROM dyeing_unload
     WHERE TRIM(BCARD) = ?
     ORDER BY DCOUNT DESC, DULTID ASC
     FOR UPDATE'
);
if (!$unloadStmt) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not prepare unloaded batch lookup: ' . $db->error], 500);
}
$unloadStmt->bind_param('s', $card);
if (!$unloadStmt->execute()) {
    $error = $unloadStmt->error;
    $unloadStmt->close();
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not read unloaded batch: ' . $error], 500);
}
$unloadResult = $unloadStmt->get_result();
$latestUnloadCount = 0;
while ($unloadRow = $unloadResult->fetch_assoc()) {
    $latestUnloadCount = max($latestUnloadCount, (int) $unloadRow['DCOUNT']);
}
$unloadStmt->close();
if ($latestUnloadCount < 1) {
    $db->rollback();
    respond(['success' => false, 'message' => 'No unloaded batch data was found for Batch ' . $card . '.'], 404);
}

$verificationStmt = $db->prepare(
    'SELECT COALESCE(MAX(DCOUNT), 0) AS DCOUNT
     FROM dyeing_batch_verification
     WHERE TRIM(BCARD) = ?'
);
if (!$verificationStmt) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not prepare Batch Verification cycle lookup: ' . $db->error], 500);
}
$verificationStmt->bind_param('s', $card);
if (!$verificationStmt->execute()) {
    $error = $verificationStmt->error;
    $verificationStmt->close();
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not check the current verification cycle: ' . $error], 500);
}
$verificationCount = (int) $verificationStmt->get_result()->fetch_assoc()['DCOUNT'];
$verificationStmt->close();
if ($verificationCount > $latestUnloadCount) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Redyeing has already been sent to Batch Verification for this unload cycle.'], 409);
}
if ($verificationCount !== $latestUnloadCount) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Batch Verification and Dyeing Unload cycles do not match. Contact the administrator.'], 409);
}

$nextCount = $latestUnloadCount + 1;
$insertStmt = $db->prepare(
    'INSERT INTO dyeing_batch_verification
     (BUDAT, BCARD, ROLL, DCOUNT, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, DYMCNO,
      MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO, YTYPE,
      YCOUNT, YBRAND, CATEGORY, SINCHARGE, SUPERVISOR, BINCHARGE, BMANAGER, DOP, DID,
      SILICON, NONSILICON, NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP,
      SOFTENER, NONSOFTENER, OTHERS)
     SELECT BUDAT, BCARD, ROLL, ?, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, MCNO,
            MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO, YTYPE,
            YCOUNT, YBRAND, CATEGORY, NULL, NULL, NULL, NULL, DOP, DID,
            SILICON, NONSILICON, NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH,
            AOP, SOFTENER, NONSOFTENER, OTHERS
     FROM dyeing_unload
     WHERE TRIM(BCARD) = ? AND DCOUNT = ?'
);
if (!$insertStmt) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not prepare redyeing verification rows: ' . $db->error], 500);
}
$insertStmt->bind_param('isi', $nextCount, $card, $latestUnloadCount);
if (!$insertStmt->execute()) {
    $error = $insertStmt->error;
    $insertStmt->close();
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not send batch back to verification: ' . $error], 500);
}
$insertedRows = $insertStmt->affected_rows;
$insertStmt->close();
if ($insertedRows < 1) {
    $db->rollback();
    respond(['success' => false, 'message' => 'No rolls were copied into the new Batch Verification cycle.'], 500);
}
if (!$db->commit()) {
    $error = $db->error;
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not commit redyeing cycle: ' . $error], 500);
}

respond([
    'success' => true,
    'card_no' => $card,
    'dcount' => $nextCount,
    'roll_count' => $insertedRows
]);
