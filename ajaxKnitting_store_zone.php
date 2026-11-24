<?php
include 'config.php';

header('Content-Type: application/json');

function respondStoreZone($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if (!isset($db) || !$db) {
    respondStoreZone(['success' => false, 'error' => 'Database connection failed.'], 500);
}

if (empty($_SESSION['username'])) {
    respondStoreZone(['success' => false, 'error' => 'Login required.'], 401);
}

function getInspectionRollForZone($db, $roll)
{
    $statement = mysqli_prepare(
        $db,
        'SELECT * FROM knitting_inspection WHERE TRIM(ROLL) = ? ORDER BY KITID DESC LIMIT 1'
    );
    if (!$statement) {
        throw new RuntimeException('Unable to prepare the knitting inspection lookup: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($statement, 's', $roll);
    if (!mysqli_stmt_execute($statement)) {
        $error = mysqli_stmt_error($statement);
        mysqli_stmt_close($statement);
        throw new RuntimeException('Unable to read inspection data for Roll ' . $roll . ': ' . $error);
    }

    $result = mysqli_stmt_get_result($statement);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($statement);

    if (!$row) {
        throw new RuntimeException('ROLL_NOT_FOUND:' . $roll);
    }
    if (strtolower(trim((string) ($row['QC_STATUS'] ?? ''))) !== 'passed') {
        throw new RuntimeException('ROLL_NOT_QC_PASSED:' . $roll);
    }

    if (preg_match('/-R$/i', $roll)) {
        $row['QTY'] = (isset($row['RQTY']) && (float) $row['RQTY'] > 0)
            ? $row['RQTY']
            : ((isset($row['OQTY']) && (float) $row['OQTY'] > 0) ? $row['OQTY'] : $row['QTY']);
    } elseif (preg_match('/-P$/i', $roll)) {
        $row['QTY'] = (isset($row['UQTY']) && (float) $row['UQTY'] > 0)
            ? $row['UQTY']
            : ((isset($row['OQTY']) && (float) $row['OQTY'] > 0) ? $row['OQTY'] : $row['QTY']);
    } elseif (isset($row['UQTY']) && (float) $row['UQTY'] > 0) {
        $row['QTY'] = $row['UQTY'];
    } elseif (isset($row['OQTY']) && (float) $row['OQTY'] > 0) {
        $row['QTY'] = $row['OQTY'];
    }

    $row['ROLL'] = $roll;
    return $row;
}

function ensureRollNotInZone($db, $roll)
{
    $statement = mysqli_prepare($db, 'SELECT KSZTID FROM knitting_store_zone WHERE TRIM(ROLL) = ? LIMIT 1');
    if (!$statement) {
        throw new RuntimeException('Unable to prepare the Store Common Zone duplicate check: ' . mysqli_error($db));
    }
    mysqli_stmt_bind_param($statement, 's', $roll);
    if (!mysqli_stmt_execute($statement)) {
        $error = mysqli_stmt_error($statement);
        mysqli_stmt_close($statement);
        throw new RuntimeException('Unable to check whether Roll ' . $roll . ' is already zoned: ' . $error);
    }
    $result = mysqli_stmt_get_result($statement);
    $existing = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($statement);

    if ($existing) {
        throw new RuntimeException('ROLL_ALREADY_IN_ZONE:' . $roll);
    }
}

function storeInspectionRollInZone($statement, $row, $uname, $uid)
{
    $values = [
        (string) (($row['BUDAT'] ?? '') ?: date('Y-m-d')),
        (string) $row['ROLL'],
        (string) ($row['PO_NUMBER'] ?? ''),
        (string) ($row['QTY'] ?? ''),
        (string) ($row['SONO'] ?? ''),
        (string) ($row['SHIFT'] ?? ''),
        (string) ($row['BUYER'] ?? ''),
        (string) ($row['STYLE'] ?? ''),
        (string) ($row['COLOR'] ?? ''),
        (string) ($row['MCNO'] ?? ''),
        (string) ($row['MC_DIA'] ?? ''),
        (string) ($row['CUSTOMER'] ?? ''),
        (string) ($row['YTYPE'] ?? ''),
        (string) ($row['YCOUNT'] ?? ''),
        (string) ($row['YBRAND'] ?? ''),
        (string) ($row['O_T'] ?? ''),
        (string) ($row['SL'] ?? ''),
        (string) ($row['FTYPE'] ?? ''),
        (string) ($row['FGSM'] ?? ''),
        (string) ($row['FDIA'] ?? ''),
        (string) ($row['FPLAN'] ?? ''),
        (string) ($row['LOTNO'] ?? ''),
        (string) ($row['TPOINT'] ?? ''),
        (string) ($row['MATERIAL_CODE'] ?? ''),
        (string) ($row['M_DES'] ?? ''),
        (string) ($row['QC_STATUS'] ?? ''),
        (string) ($row['CATEGORY'] ?? ''),
        $uname,
        $uid
    ];

    if ($values[1] === '' || $values[2] === '' || $values[3] === '' || $values[5] === '') {
        throw new RuntimeException('Roll ' . $values[1] . ' is missing a required PO, QTY, or SHIFT value.');
    }

    $types = str_repeat('s', count($values));
    $references = [];
    $references[] = &$types;
    foreach ($values as &$value) {
        $references[] = &$value;
    }
    unset($value);

    if (!call_user_func_array([$statement, 'bind_param'], $references)) {
        throw new RuntimeException('Unable to bind Store Common Zone values: ' . mysqli_stmt_error($statement));
    }
    if (!mysqli_stmt_execute($statement)) {
        throw new RuntimeException('Unable to store Roll ' . $values[1] . ': ' . mysqli_stmt_error($statement));
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'get_by_roll') {
    if (isset($_GET['manual']) && $_GET['manual'] === '1' && empty($_SESSION['knitting_store_manual_entry_authorized'])) {
        respondStoreZone(['success' => false, 'error' => 'Unlock manual entry before searching for a roll.'], 403);
    }

    $roll = trim((string) ($_GET['roll'] ?? ''));
    if ($roll === '') {
        respondStoreZone(['success' => false, 'error' => 'Roll number is required.'], 400);
    }

    try {
        ensureRollNotInZone($db, $roll);
        $row = getInspectionRollForZone($db, $roll);
        unset($row['QC_PASSES']);
        respondStoreZone(['success' => true, 'data' => $row]);
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
        if (strpos($message, 'ROLL_ALREADY_IN_ZONE:') === 0) {
            respondStoreZone(['success' => false, 'error' => 'Roll ' . $roll . ' is already stored in the Common Zone.'], 409);
        }
        if (strpos($message, 'ROLL_NOT_FOUND:') === 0) {
            respondStoreZone(['success' => false, 'error' => 'Roll ' . $roll . ' was not found in Knitting Inspection.'], 404);
        }
        if (strpos($message, 'ROLL_NOT_QC_PASSED:') === 0) {
            respondStoreZone(['success' => false, 'error' => 'Roll ' . $roll . ' has not passed QC and cannot be stored in the Common Zone.'], 422);
        }
        error_log('Store Common Zone lookup failed: ' . $message);
        respondStoreZone(['success' => false, 'error' => 'Unable to load inspection data for this roll.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $action !== 'insert_zone') {
    respondStoreZone(['success' => false, 'error' => 'Invalid Store Common Zone request.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true);
$items = is_array($input) && isset($input['items']) ? $input['items'] : null;
if (!is_array($items) || !$items) {
    respondStoreZone(['success' => false, 'error' => 'At least one roll is required.'], 400);
}

$rolls = [];
$seenRolls = [];
foreach ($items as $index => $item) {
    if (!is_array($item) || !isset($item['ROLL']) || !is_string($item['ROLL'])) {
        respondStoreZone(['success' => false, 'error' => 'Invalid roll data at item ' . ($index + 1) . '.'], 400);
    }
    $roll = trim($item['ROLL']);
    if ($roll === '') {
        respondStoreZone(['success' => false, 'error' => 'Roll number is required for item ' . ($index + 1) . '.'], 400);
    }
    $rollKey = strtoupper($roll);
    if (isset($seenRolls[$rollKey])) {
        respondStoreZone(['success' => false, 'error' => 'Duplicate roll in this batch: ' . $roll], 400);
    }
    $seenRolls[$rollKey] = true;
    $rolls[] = $roll;
}

$uname = trim((string) $_SESSION['username']);
$uid = $uname;
$userStatement = mysqli_prepare($db, 'SELECT USER_ID, USER_NAME FROM users WHERE USER_ID = ? OR USER_NAME = ? LIMIT 1');
if ($userStatement) {
    mysqli_stmt_bind_param($userStatement, 'ss', $uname, $uname);
    if (mysqli_stmt_execute($userStatement)) {
        $userResult = mysqli_stmt_get_result($userStatement);
        if ($userResult && ($user = mysqli_fetch_assoc($userResult))) {
            $uid = (string) $user['USER_ID'];
            $uname = (string) $user['USER_NAME'];
        }
    }
    mysqli_stmt_close($userStatement);
}

$insertSql = 'INSERT INTO knitting_store_zone
    (BUDAT, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR, MCNO, MCDIA, CUSTOMER,
     YTYPE, YCOUNT, YBRAND, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO, TPOINT,
     MCODE, MDESCRIPTION, QC_STATUS, CATEGORY, UNAME, UID)
    VALUES (' . implode(',', array_fill(0, 29, '?')) . ')';

$insertStatement = null;
$transactionStarted = false;
try {
    if (!mysqli_begin_transaction($db)) {
        throw new RuntimeException('Unable to start Store Common Zone transaction: ' . mysqli_error($db));
    }
    $transactionStarted = true;

    $insertStatement = mysqli_prepare($db, $insertSql);
    if (!$insertStatement) {
        throw new RuntimeException('Unable to prepare Store Common Zone insert: ' . mysqli_error($db));
    }

    foreach ($rolls as $roll) {
        ensureRollNotInZone($db, $roll);
        $row = getInspectionRollForZone($db, $roll);
        storeInspectionRollInZone($insertStatement, $row, $uname, $uid);
    }

    mysqli_stmt_close($insertStatement);
    $insertStatement = null;
    if (!mysqli_commit($db)) {
        throw new RuntimeException('Unable to commit Store Common Zone batch: ' . mysqli_error($db));
    }
    $transactionStarted = false;
} catch (RuntimeException $e) {
    if ($transactionStarted) {
        mysqli_rollback($db);
    }
    if ($insertStatement instanceof mysqli_stmt) {
        mysqli_stmt_close($insertStatement);
    }

    $message = $e->getMessage();
    if (strpos($message, 'ROLL_ALREADY_IN_ZONE:') === 0) {
        $roll = substr($message, strlen('ROLL_ALREADY_IN_ZONE:'));
        respondStoreZone(['success' => false, 'error' => 'Roll ' . $roll . ' is already stored in the Common Zone.'], 409);
    }
    if (strpos($message, 'ROLL_NOT_FOUND:') === 0) {
        $roll = substr($message, strlen('ROLL_NOT_FOUND:'));
        respondStoreZone(['success' => false, 'error' => 'Roll ' . $roll . ' was not found in Knitting Inspection.'], 404);
    }
    if (strpos($message, 'ROLL_NOT_QC_PASSED:') === 0) {
        $roll = substr($message, strlen('ROLL_NOT_QC_PASSED:'));
        respondStoreZone(['success' => false, 'error' => 'Roll ' . $roll . ' has not passed QC and cannot be stored in the Common Zone.'], 422);
    }
    error_log('Store Common Zone insert failed: ' . $message);
    respondStoreZone(['success' => false, 'error' => $message], 500);
}

respondStoreZone([
    'success' => true,
    'stored' => count($rolls),
    'message' => count($rolls) . ' roll(s) stored in Common Zone.'
]);
