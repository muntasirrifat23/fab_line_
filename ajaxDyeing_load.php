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
    ensureDyeingProcessColumns($db, 'dyeing_batch_delivery');
    ensureDyeingProcessColumns($db, 'dyeing_batch_verification');
    ensureDyeingProcessColumns($db, 'dyeing_load');
    ensureDyeingCountColumn($db, 'dyeing_batch_verification');
    ensureDyeingCountColumn($db, 'dyeing_load');
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize Dyeing Load process fields: ' . $error->getMessage()], 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Unsupported Dyeing Load request.'], 405);
}

$requestData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);
    if (!is_array($requestData)) {
        respond(['success' => false, 'message' => 'Invalid Dyeing Load request data.'], 400);
    }
}

$action = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (isset($requestData['action']) ? trim((string) $requestData['action']) : '')
    : (isset($_GET['action']) ? trim((string) $_GET['action']) : '');
$processFields = dyeingProcessFieldNames();
$processProjection = [];
foreach ($processFields as $processField) {
    $processProjection[] = "COALESCE(verified.`$processField`, '') AS `$processField`";
}
$passCount = "(COALESCE(UPPER(TRIM(verified.SINCHARGE)) = 'PASS', 0) +
    COALESCE(UPPER(TRIM(verified.SUPERVISOR)) = 'PASS', 0) +
    COALESCE(UPPER(TRIM(verified.BINCHARGE)) = 'PASS', 0) +
    COALESCE(UPPER(TRIM(verified.BMANAGER)) = 'PASS', 0))";
$failCount = "(COALESCE(UPPER(TRIM(verified.SINCHARGE)) = 'FAIL', 0) +
    COALESCE(UPPER(TRIM(verified.SUPERVISOR)) = 'FAIL', 0) +
    COALESCE(UPPER(TRIM(verified.BINCHARGE)) = 'FAIL', 0) +
    COALESCE(UPPER(TRIM(verified.BMANAGER)) = 'FAIL', 0))";
$passFilter = $passCount . ' > ' . $failCount;
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

    $countStmt = $db->prepare(
        'SELECT COALESCE(MAX(DCOUNT), 0) AS DCOUNT
         FROM dyeing_batch_verification WHERE TRIM(BCARD) = ?'
    );
    if (!$countStmt) {
        respond(['success' => false, 'message' => 'Could not prepare Batch Verification count lookup: ' . $db->error], 500);
    }
    $countStmt->bind_param('s', $card);
    if (!$countStmt->execute()) {
        $error = $countStmt->error;
        $countStmt->close();
        respond(['success' => false, 'message' => 'Could not determine the current batch cycle: ' . $error], 500);
    }
    $dcount = (int) $countStmt->get_result()->fetch_assoc()['DCOUNT'];
    $countStmt->close();
    if ($dcount < 1) {
        respond(['success' => false, 'message' => 'Batch ' . $card . ' has not completed Batch Verification.'], 409);
    }

    $loadedBatchStmt = $db->prepare(
        'SELECT DLTID FROM dyeing_load WHERE TRIM(BCARD) = ? AND DCOUNT = ? LIMIT 1'
    );
    if (!$loadedBatchStmt) {
        respond(['success' => false, 'message' => 'Could not check whether Batch ' . $card . ' was already loaded: ' . $db->error], 500);
    }
    $loadedBatchStmt->bind_param('si', $card, $dcount);
    if (!$loadedBatchStmt->execute()) {
        $error = $loadedBatchStmt->error;
        $loadedBatchStmt->close();
        respond(['success' => false, 'message' => 'Could not check whether Batch ' . $card . ' was already loaded: ' . $error], 500);
    }
    $loadedBatchResult = $loadedBatchStmt->get_result();
    $alreadyLoaded = $loadedBatchResult && $loadedBatchResult->num_rows > 0;
    if ($loadedBatchResult) {
        $loadedBatchResult->free();
    }
    $loadedBatchStmt->close();
    if ($alreadyLoaded) {
        respond([
            'success' => false,
            'message' => 'Batch ' . $card . ' has already been loaded. Please scan a different batch.'
        ], 409);
    }

    $batchStmt = $db->prepare(
    'SELECT verified.DBVTID, verified.DCOUNT, verified.BUDAT, verified.BCARD, verified.ROLL,
                verified.PO_NUMBER, verified.QTY, verified.SONO, verified.BUYER,
                verified.STYLE, verified.COLOR, verified.DYMCNO AS MCNO, verified.MCDIA,
                verified.CUSTOMER, verified.O_T, verified.SL, verified.FTYPE,
                verified.FGSM, verified.FDIA, verified.YTYPE, verified.YCOUNT,
                verified.YBRAND, ' . implode(', ', $processProjection) . ',
                verified.DOP, verified.DID, verified.CREATEDAT
         FROM dyeing_batch_verification verified
         WHERE TRIM(verified.BCARD) = ?
           AND verified.DCOUNT = ?
           AND ' . $passFilter . '
         ORDER BY verified.DBVTID ASC'
    );
    if (!$batchStmt) {
        respond(['success' => false, 'message' => 'Could not prepare batch lookup: ' . $db->error], 500);
    }
    $batchStmt->bind_param('si', $card, $dcount);
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
        respond(['success' => false, 'message' => 'No PASS verified roll data was found for Batch ' . $card . '.'], 404);
    }
    respond([
        'success' => true,
        'card_no' => $card,
        'roll_count' => count($rows),
        'dcount' => $dcount,
        'shift' => currentDyeingShift(),
        'rows' => $rows
    ]);
}

if ($action === 'start_load') {
    $operatorId = isset($requestData['operator_id']) ? trim((string) $requestData['operator_id']) : '';
    $card = isset($requestData['card_no']) ? trim((string) $requestData['card_no']) : '';
    if ($operatorId === '' || strlen($operatorId) > 50) {
        respond(['success' => false, 'message' => 'Scan a valid Dyeing Operator ID first.'], 400);
    }
    if ($card === '' || strlen($card) > 30) {
        respond(['success' => false, 'message' => 'Load a valid Batch Card first.'], 400);
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
        respond(['success' => false, 'message' => 'Dyeing Operator ID was not found.'], 404);
    }

    if (!$db->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start the Dyeing Load transaction: ' . $db->error], 500);
    }

    $countStmt = $db->prepare(
        'SELECT COALESCE(MAX(DCOUNT), 0) AS DCOUNT
         FROM dyeing_batch_verification WHERE TRIM(BCARD) = ?'
    );
    if (!$countStmt) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not prepare Batch Verification count lookup: ' . $db->error], 500);
    }
    $countStmt->bind_param('s', $card);
    if (!$countStmt->execute()) {
        $error = $countStmt->error;
        $countStmt->close();
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not determine the current batch cycle: ' . $error], 500);
    }
    $dcount = (int) $countStmt->get_result()->fetch_assoc()['DCOUNT'];
    $countStmt->close();
    if ($dcount < 1) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Batch ' . $card . ' has not completed Batch Verification.'], 409);
    }

    $existingStmt = $db->prepare(
        'SELECT DLTID FROM dyeing_load WHERE TRIM(BCARD) = ? AND DCOUNT = ? LIMIT 1'
    );
    if (!$existingStmt) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not check whether this batch was already started: ' . $db->error], 500);
    }
    $existingStmt->bind_param('si', $card, $dcount);
    if (!$existingStmt->execute()) {
        $error = $existingStmt->error;
        $existingStmt->close();
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not check the existing Dyeing Load: ' . $error], 500);
    }
    $existingResult = $existingStmt->get_result();
    $alreadyStarted = $existingResult && $existingResult->num_rows > 0;
    if ($existingResult) {
        $existingResult->free();
    }
    $existingStmt->close();
    if ($alreadyStarted) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Dyeing Load was already started for Batch ' . $card . '.'], 409);
    }

    $batchStmt = $db->prepare(
        'SELECT verified.DCOUNT, verified.BUDAT, verified.BCARD, verified.ROLL, verified.PO_NUMBER,
                verified.QTY, verified.SONO, verified.BUYER, verified.STYLE, verified.COLOR,
                verified.DYMCNO AS MCNO, verified.MCDIA, verified.CUSTOMER, verified.O_T, verified.SL,
                verified.FTYPE, verified.FGSM, verified.FDIA, verified.FEEDER_PLAN,
                verified.LOT_NO, verified.YTYPE, verified.YCOUNT, verified.YBRAND,
                verified.CATEGORY, ' . implode(', ', $processProjection) . '
         FROM dyeing_batch_verification verified
         WHERE TRIM(verified.BCARD) = ?
           AND verified.DCOUNT = ?
           AND ' . $passFilter . '
         ORDER BY verified.DBVTID ASC'
    );
    if (!$batchStmt) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not prepare verified batch lookup: ' . $db->error], 500);
    }
    $batchStmt->bind_param('si', $card, $dcount);
    if (!$batchStmt->execute()) {
        $error = $batchStmt->error;
        $batchStmt->close();
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not load verified batch data: ' . $error], 500);
    }
    $batchResult = $batchStmt->get_result();
    $rows = [];
    while ($batchResult && ($row = $batchResult->fetch_assoc())) {
        $rows[] = $row;
    }
    if ($batchResult) {
        $batchResult->free();
    }
    $batchStmt->close();
    if (!$rows) {
        $db->rollback();
        respond(['success' => false, 'message' => 'No PASS verified roll data was found for Batch ' . $card . '.'], 404);
    }

    $insertStmt = $db->prepare(
        'INSERT INTO dyeing_load
         (BUDAT, BCARD, ROLL, DCOUNT, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
          MCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
          YTYPE, YCOUNT, YBRAND, CATEGORY, DOP, DID, SILICON, NONSILICON, NWASH,
          EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP, SOFTENER, NONSOFTENER, OTHERS)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                 ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$insertStmt) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not prepare Dyeing Load insert: ' . $db->error], 500);
    }

    $shift = currentDyeingShift();
    foreach ($rows as $row) {
        $rowDcount = (int) $row['DCOUNT'];
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
        $processValues = [];
        foreach (dyeingProcessFieldNames() as $processField) {
            $processValues[$processField] = isset($row[$processField]) ? $row[$processField] : '';
        }
        $operatorName = $operator['DY_OP_NAME'];
        $verifiedOperatorId = $operator['DY_OP_ID'];

        if (!$insertStmt->bind_param(
            'sssi' . str_repeat('s', 35),
            $budat,
            $batchCard,
            $roll,
            $rowDcount,
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
            $verifiedOperatorId,
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
            respond(['success' => false, 'message' => 'Could not save the Dyeing Load batch: ' . $error], 500);
        }
    }
    $insertStmt->close();
    if (!$db->commit()) {
        $error = $db->error;
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not commit the Dyeing Load batch: ' . $error], 500);
    }

    respond([
        'success' => true,
        'card_no' => $card,
        'roll_count' => count($rows),
        'operator' => $operator,
        'shift' => $shift,
        'dcount' => $dcount
    ]);
}

respond(['success' => false, 'message' => 'Unsupported Dyeing Load action.'], 400);
