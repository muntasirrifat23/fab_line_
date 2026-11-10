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

try {
    ensureDyeingProcessColumns($db, 'dyeing_batch_card');
    ensureDyeingProcessColumns($db, 'dyeing_batch_verification');
    ensureDyeingProcessColumns($db, 'dyeing_load');
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

    $loadedBatchStmt = $db->prepare(
        'SELECT DLTID FROM dyeing_load WHERE TRIM(BCARD) = ? LIMIT 1'
    );
    if (!$loadedBatchStmt) {
        respond(['success' => false, 'message' => 'Could not check whether Batch ' . $card . ' was already loaded: ' . $db->error], 500);
    }
    $loadedBatchStmt->bind_param('s', $card);
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

    $existingStmt = $db->prepare(
        'SELECT DLTID FROM dyeing_load WHERE TRIM(BCARD) = ? LIMIT 1'
    );
    if (!$existingStmt) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not check whether this batch was already started: ' . $db->error], 500);
    }
    $existingStmt->bind_param('s', $card);
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

    $processFields = dyeingProcessFieldNames();
    $processProjection = [];
    $processAggregate = [];
    foreach ($processFields as $processField) {
        $processProjection[] = "COALESCE(batch_processes.`$processField`, '') AS `$processField`";
        $processAggregate[] = "MAX(`$processField`) AS `$processField`";
    }
    $batchStmt = $db->prepare(
        'SELECT verified.BUDAT, verified.BCARD, verified.ROLL, verified.PO_NUMBER,
                verified.QTY, verified.SONO, verified.BUYER, verified.STYLE, verified.COLOR,
                verified.MCNO, verified.MCDIA, verified.CUSTOMER, verified.O_T, verified.SL,
                verified.FTYPE, verified.FGSM, verified.FDIA, verified.FEEDER_PLAN,
                verified.LOT_NO, verified.YTYPE, verified.YCOUNT, verified.YBRAND,
                verified.CATEGORY, ' . implode(', ', $processProjection) . '
         FROM dyeing_roll_verification verified
         LEFT JOIN (
             SELECT BCARD, ' . implode(', ', $processAggregate) . '
             FROM dyeing_batch_card
             GROUP BY BCARD
         ) batch_processes ON TRIM(batch_processes.BCARD) = TRIM(verified.BCARD)
         WHERE TRIM(verified.BCARD) = ?
         ORDER BY verified.DRVTID ASC'
    );
    if (!$batchStmt) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not prepare verified batch lookup: ' . $db->error], 500);
    }
    $batchStmt->bind_param('s', $card);
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
        respond(['success' => false, 'message' => 'No verified roll data was found for Batch ' . $card . '.'], 404);
    }

    $insertStmt = $db->prepare(
        'INSERT INTO dyeing_load
         (BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
          MCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
          YTYPE, YCOUNT, YBRAND, CATEGORY, DOP, DID, SILICON, NONSILICON, NWASH,
          EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP, SOFTENER, NONSOFTENER, OTHERS)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                 ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$insertStmt) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not prepare Dyeing Load insert: ' . $db->error], 500);
    }

    foreach ($rows as $row) {
        $budat = $row['BUDAT'];
        $batchCard = $row['BCARD'];
        $roll = $row['ROLL'];
        $poNumber = $row['PO_NUMBER'];
        $qty = $row['QTY'];
        $sono = $row['SONO'];
        $shift = null;
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
            str_repeat('s', 38),
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
        'operator' => $operator
    ]);
}

respond(['success' => false, 'message' => 'Unsupported Dyeing Load action.'], 400);
