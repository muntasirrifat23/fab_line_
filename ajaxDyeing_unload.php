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

function currentDyeingShift()
{
    $dhakaTime = new DateTimeImmutable('now', new DateTimeZone('Asia/Dhaka'));
    $minutes = ((int) $dhakaTime->format('G') * 60) + (int) $dhakaTime->format('i');
    if ($minutes >= 360 && $minutes <= 840) {
        return 'A';
    }
    if ($minutes >= 841 && $minutes <= 1320) {
        return 'B';
    }
    return 'C';
}

if (!isset($_SESSION['username'])) {
    respond(['success' => false, 'message' => 'Unauthorized. Please login.'], 401);
}

try {
    ensureDyeingProcessColumns($db, 'dyeing_batch_card');
    ensureDyeingProcessColumns($db, 'dyeing_load');
    ensureDyeingProcessColumns($db, 'dyeing_unload');
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize Dyeing Unload process fields: ' . $error->getMessage()], 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Unsupported Dyeing Unload request.'], 405);
}

$requestData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);
    if (!is_array($requestData)) {
        respond(['success' => false, 'message' => 'Invalid Dyeing Unload request data.'], 400);
    }
}

$action = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (isset($requestData['action']) ? trim((string) $requestData['action']) : '')
    : (isset($_GET['action']) ? trim((string) $_GET['action']) : '');
$operatorId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (isset($requestData['operator_id']) ? trim((string) $requestData['operator_id']) : '')
    : (isset($_GET['operator_id']) ? trim((string) $_GET['operator_id']) : '');
$card = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (isset($requestData['card_no']) ? trim((string) $requestData['card_no']) : '')
    : (isset($_GET['card_no']) ? trim((string) $_GET['card_no']) : '');

if ($action !== 'verify_operator' && $action !== 'load_batch' && $action !== 'unload_batch') {
    respond(['success' => false, 'message' => 'Unsupported Dyeing Unload action.'], 400);
}

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['manual']) &&
    $_GET['manual'] === '1' &&
    empty($_SESSION['roll_manual_entry_authorized'])
) {
    respond(['success' => false, 'message' => 'Verify the manual-entry password before entering an operator ID or batch card.'], 403);
}

if ($action === 'verify_operator') {
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
    $operatorResult = $operatorStmt->get_result();
    $operator = $operatorResult ? $operatorResult->fetch_assoc() : null;
    if ($operatorResult) {
        $operatorResult->free();
    }
    $operatorStmt->close();

    if (!$operator) {
        respond(['success' => false, 'message' => 'Dyeing Operator ID ' . $operatorId . ' was not found.'], 404);
    }
    respond(['success' => true, 'operator' => $operator]);
}

if ($operatorId === '' || strlen($operatorId) > 50) {
    respond(['success' => false, 'message' => 'Scan a Dyeing Operator ID first.'], 400);
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
$operatorResult = $operatorStmt->get_result();
$operator = $operatorResult ? $operatorResult->fetch_assoc() : null;
if ($operatorResult) {
    $operatorResult->free();
}
$operatorStmt->close();
if (!$operator) {
    respond(['success' => false, 'message' => 'Dyeing Operator ID ' . $operatorId . ' was not found.'], 404);
}

if ($card === '' || strlen($card) > 30) {
    respond(['success' => false, 'message' => 'Enter a valid Batch Card number.'], 400);
}

$sourceFields = 'BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
                 MCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN,
                 LOT_NO, YTYPE, YCOUNT, YBRAND, CATEGORY, DOP, DID, DYSTART,
                 SILICON, NONSILICON, NWASH, EWASH, ENZYME, NONENZYME, PEACH,
                 BRUSH, AOP, SOFTENER, NONSOFTENER, OTHERS';

if ($action === 'load_batch') {
    $unloadedStmt = $db->prepare(
        'SELECT DULTID FROM dyeing_unload WHERE TRIM(BCARD) = ? LIMIT 1'
    );
    if (!$unloadedStmt) {
        respond(['success' => false, 'message' => 'Could not prepare unload status lookup: ' . $db->error], 500);
    }
    $unloadedStmt->bind_param('s', $card);
    if (!$unloadedStmt->execute()) {
        $error = $unloadedStmt->error;
        $unloadedStmt->close();
        respond(['success' => false, 'message' => 'Could not check the unload status: ' . $error], 500);
    }
    $unloadedResult = $unloadedStmt->get_result();
    $alreadyUnloaded = $unloadedResult && $unloadedResult->num_rows > 0;
    if ($unloadedResult) {
        $unloadedResult->free();
    }
    $unloadedStmt->close();
    if ($alreadyUnloaded) {
        respond(['success' => false, 'message' => 'Batch ' . $card . ' has already been unloaded.'], 409);
    }

    $batchStmt = $db->prepare(
        'SELECT ' . $sourceFields . '
         FROM dyeing_load
         WHERE TRIM(BCARD) = ?
         ORDER BY DLTID ASC'
    );
    if (!$batchStmt) {
        respond(['success' => false, 'message' => 'Could not prepare loaded batch lookup: ' . $db->error], 500);
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
        $rawStart = $row['DYSTART'];
        if ($rawStart !== null && $rawStart !== '') {
            $timestamp = strtotime((string) $rawStart);
            if ($timestamp === false) {
                $batchStmt->close();
                respond(['success' => false, 'message' => 'Batch ' . $card . ' has an invalid Start Time and cannot be displayed.'], 500);
            }
            $row['START_TIME'] = date('d-m-Y H:i:s', $timestamp);
        } else {
            $row['START_TIME'] = '-';
        }
        $rows[] = $row;
    }
    if ($result) {
        $result->free();
    }
    $batchStmt->close();

    if (!$rows) {
        respond(['success' => false, 'message' => 'No loaded batch data was found for Batch ' . $card . '.'], 404);
    }

    respond([
        'success' => true,
        'card_no' => $card,
        'roll_count' => count($rows),
        'operator' => $operator,
        'shift' => currentDyeingShift(),
        'rows' => $rows
    ]);
}

if (!$db->begin_transaction()) {
    respond(['success' => false, 'message' => 'Could not start the Dyeing Unload transaction: ' . $db->error], 500);
}

$batchStmt = $db->prepare(
    'SELECT ' . $sourceFields . '
     FROM dyeing_load
     WHERE TRIM(BCARD) = ?
     ORDER BY DLTID ASC
     FOR UPDATE'
);
if (!$batchStmt) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not prepare loaded batch lookup: ' . $db->error], 500);
}
$batchStmt->bind_param('s', $card);
if (!$batchStmt->execute()) {
    $error = $batchStmt->error;
    $batchStmt->close();
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not read the loaded batch: ' . $error], 500);
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
    $db->rollback();
    respond(['success' => false, 'message' => 'No loaded batch data was found for Batch ' . $card . '.'], 404);
}

$unloadedStmt = $db->prepare(
    'SELECT DULTID FROM dyeing_unload WHERE TRIM(BCARD) = ? LIMIT 1'
);
if (!$unloadedStmt) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not prepare unload status lookup: ' . $db->error], 500);
}
$unloadedStmt->bind_param('s', $card);
if (!$unloadedStmt->execute()) {
    $error = $unloadedStmt->error;
    $unloadedStmt->close();
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not check whether the batch was already unloaded: ' . $error], 500);
}
$unloadedResult = $unloadedStmt->get_result();
$alreadyUnloaded = $unloadedResult && $unloadedResult->num_rows > 0;
if ($unloadedResult) {
    $unloadedResult->free();
}
$unloadedStmt->close();
if ($alreadyUnloaded) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Batch ' . $card . ' has already been unloaded.'], 409);
}

$insertStmt = $db->prepare(
    'INSERT INTO dyeing_unload
     (BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
      MCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
      YTYPE, YCOUNT, YBRAND, CATEGORY, DOP, DID, DYSTART, SILICON, NONSILICON,
      NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP, SOFTENER, NONSOFTENER,
      OTHERS)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
if (!$insertStmt) {
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not prepare Dyeing Unload insert: ' . $db->error], 500);
}

$shift = currentDyeingShift();
foreach ($rows as $row) {
    $budat = $row['BUDAT'];
    $batchCard = $row['BCARD'];
    $roll = $row['ROLL'];
    $poNumber = $row['PO_NUMBER'];
    $qty = $row['QTY'];
    $sono = $row['SONO'];
    $buyer = $row['BUYER'];
    $style = $row['STYLE'];
    $color = $row['COLOR'];
    $mcno = $row['MCNO'];
    $mcDia = $row['MCDIA'];
    $customer = $row['CUSTOMER'];
    $openTube = $row['O_T'];
    $sl = $row['SL'];
    $fabricType = $row['FTYPE'];
    $finishGsm = $row['FGSM'];
    $finishDia = $row['FDIA'];
    $feederPlan = $row['FEEDER_PLAN'];
    $lotNo = $row['LOT_NO'];
    $yarnType = $row['YTYPE'];
    $yarnCount = $row['YCOUNT'];
    $yarnBrand = $row['YBRAND'];
    $category = $row['CATEGORY'];
    $operatorName = $operator['DY_OP_NAME'];
    $unloadOperatorId = $operator['DY_OP_ID'];
    $startTime = $row['DYSTART'];
    $processValues = [];
    foreach (dyeingProcessFieldNames() as $processField) {
        $processValues[$processField] = isset($row[$processField]) ? $row[$processField] : '';
    }

    if (!$insertStmt->bind_param(
        str_repeat('s', 39),
        $budat,
        $batchCard,
        $roll,
        $poNumber,
        $qty,
        $sono,
        $shift,
        $buyer,
        $style,
        $color,
        $mcno,
        $mcDia,
        $customer,
        $openTube,
        $sl,
        $fabricType,
        $finishGsm,
        $finishDia,
        $feederPlan,
        $lotNo,
        $yarnType,
        $yarnCount,
        $yarnBrand,
        $category,
        $operatorName,
        $unloadOperatorId,
        $startTime,
        $processValues['SILICON'],
        $processValues['NONSILICON'],
        $processValues['NWASH'],
        $processValues['EWASH'],
        $processValues['ENZYME'],
        $processValues['NONENZYME'],
        $processValues['PEACH'],
        $processValues['BRUSH'],
        $processValues['AOP'],
        $processValues['SOFTENER'],
        $processValues['NONSOFTENER'],
        $processValues['OTHERS']
    ) || !$insertStmt->execute()) {
        $error = $insertStmt->error;
        $insertStmt->close();
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not save the Dyeing Unload batch: ' . $error], 500);
    }
}
$insertStmt->close();

if (!$db->commit()) {
    $error = $db->error;
    $db->rollback();
    respond(['success' => false, 'message' => 'Could not commit the Dyeing Unload batch: ' . $error], 500);
}

respond([
    'success' => true,
    'card_no' => $card,
    'roll_count' => count($rows),
    'shift' => $shift
]);
