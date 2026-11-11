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
    ensureDyeingProcessColumns($db, 'dyeing_batch_delivery');
    ensureDyeingCountColumn($db, 'dyeing_batch_verification');
    ensureDyeingCountColumn($db, 'dyeing_roll_verification');
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize Roll Verification process fields: ' . $error->getMessage()], 500);
}

$requestData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);
    if (!is_array($requestData)) {
        respond(['success' => false, 'message' => 'Invalid request data.'], 400);
    }
}

$action = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (isset($requestData['action']) ? trim((string) $requestData['action']) : '')
    : (isset($_GET['action']) ? trim((string) $_GET['action']) : '');

if ($action === 'authorize_manual_entry') {
    $password = isset($requestData['password']) ? (string) $requestData['password'] : '';
    $lockUntil = isset($_SESSION['roll_manual_entry_lock_until'])
        ? (int) $_SESSION['roll_manual_entry_lock_until']
        : 0;
    if ($lockUntil > time()) {
        respond(['success' => false, 'message' => 'Too many attempts. Please wait one minute and try again.'], 429);
    }

    $passwordHash = '$2y$10$76SxpOic/qcxrHeK5L8VqOpTCCBOwIgXDaXj6aAix0pNzci/dZGUS';
    if (!password_verify($password, $passwordHash)) {
        $attempts = isset($_SESSION['roll_manual_entry_attempts'])
            ? (int) $_SESSION['roll_manual_entry_attempts'] + 1
            : 1;
        if ($attempts >= 5) {
            $_SESSION['roll_manual_entry_attempts'] = 0;
            $_SESSION['roll_manual_entry_lock_until'] = time() + 60;
            respond(['success' => false, 'message' => 'Too many attempts. Please wait one minute and try again.'], 429);
        }
        $_SESSION['roll_manual_entry_attempts'] = $attempts;
        respond(['success' => false, 'message' => 'Incorrect password. Manual roll entry remains locked.'], 403);
    }

    unset($_SESSION['roll_manual_entry_attempts'], $_SESSION['roll_manual_entry_lock_until']);
    $_SESSION['roll_manual_entry_authorized'] = true;
    respond(['success' => true, 'message' => 'Manual roll entry unlocked.']);
}

if ($action === 'save_verified_rolls') {
    $card = isset($requestData['card_no']) ? trim((string) $requestData['card_no']) : '';
    $requestedRolls = isset($requestData['rolls']) && is_array($requestData['rolls'])
        ? $requestData['rolls']
        : [];
    $rolls = [];
    foreach ($requestedRolls as $requestedRoll) {
        if (!is_string($requestedRoll) && !is_numeric($requestedRoll)) {
            respond(['success' => false, 'message' => 'Invalid roll number in save request.'], 400);
        }
        $requestedRoll = trim((string) $requestedRoll);
        if ($requestedRoll === '' || strlen($requestedRoll) > 50) {
            respond(['success' => false, 'message' => 'Invalid roll number in save request.'], 400);
        }
        $rolls[] = $requestedRoll;
    }
    $rolls = array_values(array_unique($rolls));
    if ($card === '' || strlen($card) > 30 || !$rolls) {
        respond(['success' => false, 'message' => 'Select a batch with verified rolls to save.'], 400);
    }

    $countStmt = $db->prepare(
        'SELECT COALESCE(MAX(DCOUNT), 0) AS DCOUNT
         FROM dyeing_batch_verification WHERE BCARD = ?'
    );
    if (!$countStmt) {
        respond(['success' => false, 'message' => 'Could not prepare the batch dyeing count lookup: ' . $db->error], 500);
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

    $sourceStmt = $db->prepare(
        'SELECT BCARD, ROLL, DCOUNT, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, DYMCNO, MCDIA,
                CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO, YTYPE,
                YCOUNT, YBRAND, CATEGORY, DOP, DID
         FROM dyeing_batch_verification
         WHERE BCARD = ? AND TRIM(ROLL) = ? AND DCOUNT = ?
         LIMIT 1'
    );
    if (!$sourceStmt) {
        respond(['success' => false, 'message' => 'Could not prepare verified roll lookup: ' . $db->error], 500);
    }
    $existingStmt = $db->prepare(
        'SELECT DRVTID FROM dyeing_roll_verification
         WHERE BCARD = ? AND ROLL = ? AND DCOUNT = ? LIMIT 1'
    );
    if (!$existingStmt) {
        $sourceStmt->close();
        respond(['success' => false, 'message' => 'Could not prepare saved roll check: ' . $db->error], 500);
    }
    $insertStmt = $db->prepare(
        'INSERT INTO dyeing_roll_verification
         (BUDAT, BCARD, ROLL, DCOUNT, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, MCNO,
          MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO, YTYPE,
          YCOUNT, YBRAND, CATEGORY, DOP, DID)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$insertStmt) {
        $sourceStmt->close();
        $existingStmt->close();
        respond(['success' => false, 'message' => 'Could not prepare verified roll save: ' . $db->error], 500);
    }

    if (!$db->begin_transaction()) {
        $sourceStmt->close();
        $existingStmt->close();
        $insertStmt->close();
        respond(['success' => false, 'message' => 'Could not start save transaction: ' . $db->error], 500);
    }

    $insertedRolls = [];
    $alreadySavedRolls = [];
    $saveError = '';
    $budat = date('Y-m-d');

    foreach ($rolls as $roll) {
        $sourceStmt->bind_param('ssi', $card, $roll, $dcount);
        if (!$sourceStmt->execute()) {
            $saveError = 'Could not verify roll ' . $roll . ': ' . $sourceStmt->error;
            break;
        }
        $sourceResult = $sourceStmt->get_result();
        $source = $sourceResult ? $sourceResult->fetch_assoc() : null;
        if ($sourceResult) {
            $sourceResult->free();
        }
        if (!$source) {
            $saveError = 'Roll ' . $roll . ' is not part of Batch ' . $card . '.';
            break;
        }

        $existingStmt->bind_param('ssi', $card, $roll, $dcount);
        if (!$existingStmt->execute()) {
            $saveError = 'Could not check whether roll ' . $roll . ' was already saved: ' . $existingStmt->error;
            break;
        }
        $existingResult = $existingStmt->get_result();
        $alreadySaved = $existingResult && $existingResult->num_rows > 0;
        if ($existingResult) {
            $existingResult->free();
        }
        if ($alreadySaved) {
            $alreadySavedRolls[] = $roll;
            continue;
        }

        $value = function ($column) use ($source) {
            return array_key_exists($column, $source) ? $source[$column] : null;
        };
        $sourceCard = $value('BCARD');
        $sourceRoll = $value('ROLL');
        $sourceDcount = (int) $value('DCOUNT');
        $poNumber = $value('PO_NUMBER');
        $qty = $value('QTY');
        $sono = $value('SONO');
        $buyer = $value('BUYER');
        $style = $value('STYLE');
        $color = $value('COLOR');
        $mcno = $value('DYMCNO');
        $mcDia = $value('MCDIA');
        $customer = $value('CUSTOMER');
        $openTube = $value('O_T');
        $sl = $value('SL');
        $fabricType = $value('FTYPE');
        $finishGsm = $value('FGSM');
        $finishDia = $value('FDIA');
        $feederPlan = $value('FEEDER_PLAN');
        $lotNo = $value('LOT_NO');
        $yarnType = $value('YTYPE');
        $yarnCount = $value('YCOUNT');
        $yarnBrand = $value('YBRAND');
        $category = $value('CATEGORY');
        $dop = $value('DOP');
        $did = $value('DID');
        $insertStmt->bind_param(
            'sssissssssssssssssssssssss',
            $budat, $sourceCard, $sourceRoll, $sourceDcount, $poNumber, $qty, $sono, $buyer,
            $style, $color, $mcno, $mcDia, $customer, $openTube, $sl, $fabricType,
            $finishGsm, $finishDia, $feederPlan, $lotNo, $yarnType, $yarnCount,
            $yarnBrand, $category, $dop, $did
        );
        if (!$insertStmt->execute()) {
            $saveError = 'Could not save roll ' . $roll . ': ' . $insertStmt->error;
            break;
        }
        $insertedRolls[] = $roll;
    }

    $sourceStmt->close();
    $existingStmt->close();
    $insertStmt->close();
    if ($saveError !== '') {
        $db->rollback();
        respond(['success' => false, 'message' => $saveError], 500);
    }
    if (!$db->commit()) {
        $error = $db->error;
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not commit verified rolls: ' . $error], 500);
    }

    respond([
        'success' => true,
        'message' => 'Verified rolls saved.',
        'inserted_rolls' => $insertedRolls,
        'already_saved_rolls' => $alreadySavedRolls
    ]);
}

if ($action !== 'load_batch_by_roll' || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['success' => false, 'message' => 'Unsupported Roll Verification request.'], 400);
}

if (
    isset($_GET['manual']) &&
    $_GET['manual'] === '1' &&
    empty($_SESSION['roll_manual_entry_authorized'])
) {
    respond(['success' => false, 'message' => 'Verify the manual-entry password before entering a roll.'], 403);
}

$roll = isset($_GET['roll']) ? trim((string) $_GET['roll']) : '';
if ($roll === '' || strlen($roll) > 50) {
    respond(['success' => false, 'message' => 'Enter a valid roll number.'], 400);
}
$currentCard = isset($_GET['current_batch']) ? trim((string) $_GET['current_batch']) : '';

$batchStmt = $db->prepare(
    'SELECT v.BCARD
     FROM dyeing_batch_verification v
     WHERE TRIM(v.ROLL) = ? AND v.BCARD IS NOT NULL AND TRIM(v.BCARD) <> \'\'
       AND v.DCOUNT = (
           SELECT MAX(v2.DCOUNT)
           FROM dyeing_batch_verification v2
           WHERE v2.BCARD = v.BCARD
       )
     ORDER BY (v.BCARD = ?) DESC, v.DBVTID DESC
     LIMIT 1'
);
if (!$batchStmt) {
    respond(['success' => false, 'message' => 'Could not prepare roll lookup: ' . $db->error], 500);
}
$batchStmt->bind_param('ss', $roll, $currentCard);
if (!$batchStmt->execute()) {
    $error = $batchStmt->error;
    $batchStmt->close();
    respond(['success' => false, 'message' => 'Could not look up the roll: ' . $error], 500);
}
$batchResult = $batchStmt->get_result();
$batchRow = $batchResult->fetch_assoc();
$card = $batchRow ? trim((string) $batchRow['BCARD']) : '';
$batchResult->free();
$batchStmt->close();

if ($card === '') {
    respond(['success' => false, 'message' => 'Roll ' . $roll . ' was not found in the current Batch Verification cycle.'], 404);
}
$rowsStmt = $db->prepare(
    'SELECT DBVTID, BCARD, ROLL, DCOUNT, PO_NUMBER, COLOR, QTY, DYMCNO, SONO, BUYER, STYLE,
            MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, YTYPE, YCOUNT, YBRAND,
            SILICON, NONSILICON, NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH,
            AOP, SOFTENER, NONSOFTENER, OTHERS, DOP, DID
     FROM dyeing_batch_verification
     WHERE BCARD = ?
       AND DCOUNT = (SELECT MAX(DCOUNT) FROM dyeing_batch_verification WHERE BCARD = ?)
     ORDER BY DBVTID ASC'
);
if (!$rowsStmt) {
    respond(['success' => false, 'message' => 'Could not prepare batch roll lookup: ' . $db->error], 500);
}
$rowsStmt->bind_param('ss', $card, $card);
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
    'dcount' => (int) ($rows[0]['DCOUNT'] ?? 1),
    'roll_count' => count($rows),
    'rows' => $rows
]);
