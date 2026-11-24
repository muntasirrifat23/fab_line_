<?php
include 'config.php';

header('Content-Type: application/json');

function respond($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function insertKnittingStoreRoll($statement, $item, $rackNo, $rackLocation, $uname, $uid) {
    $budat = isset($item['BUDAT']) ? $item['BUDAT'] : '';
    if ($budat !== date('Y-m-d')) {
        $budat = date('Y-m-d');
    }

    $roll = trim((string) $item['ROLL']);
    $poNumber = isset($item['PO_NUMBER']) ? $item['PO_NUMBER'] : '';
    $qty = isset($item['QTY']) ? $item['QTY'] : '';
    $sono = isset($item['SONO']) ? $item['SONO'] : '';
    $shift = isset($item['SHIFT']) ? $item['SHIFT'] : '';
    $buyer = isset($item['BUYER']) ? $item['BUYER'] : '';
    $style = isset($item['STYLE']) ? $item['STYLE'] : '';
    $color = isset($item['COLOR']) ? $item['COLOR'] : '';
    $mcno = isset($item['MCNO']) ? $item['MCNO'] : '';
    $mcdia = isset($item['MCDIA']) ? $item['MCDIA'] : (isset($item['MC_DIA']) ? $item['MC_DIA'] : '');
    $customer = isset($item['CUSTOMER']) ? $item['CUSTOMER'] : '';
    $ytype = isset($item['YTYPE']) ? $item['YTYPE'] : '';
    $ycount = isset($item['YCOUNT']) ? $item['YCOUNT'] : '';
    $ybrand = isset($item['YBRAND']) ? $item['YBRAND'] : '';
    $oT = isset($item['O_T']) ? $item['O_T'] : '';
    $sl = isset($item['SL']) ? $item['SL'] : '';
    $ftype = isset($item['FTYPE']) ? $item['FTYPE'] : '';
    $fgsm = isset($item['FGSM']) ? $item['FGSM'] : '';
    $fdia = isset($item['FDIA']) ? $item['FDIA'] : '';
    $fplan = isset($item['FEEDER_PLAN']) ? $item['FEEDER_PLAN'] : (isset($item['FPLAN']) ? $item['FPLAN'] : '');
    $lotno = isset($item['LOT_NO']) ? $item['LOT_NO'] : (isset($item['LOTNO']) ? $item['LOTNO'] : '');
    $tpoint = isset($item['TPOINT']) ? $item['TPOINT'] : '';
    $mcode = isset($item['MCODE']) ? $item['MCODE'] : (isset($item['MATERIAL_CODE']) ? $item['MATERIAL_CODE'] : '');
    $mdesc = isset($item['MDESCRIPTION']) ? $item['MDESCRIPTION'] : (isset($item['M_DES']) ? $item['M_DES'] : '');
    $qcstatus = isset($item['QC_STATUS']) ? $item['QC_STATUS'] : '';
    $category = isset($item['CATEGORY']) ? $item['CATEGORY'] : '';

    mysqli_stmt_bind_param(
        $statement,
        "sssssssssssssssssssssssssssssss",
        $budat, $rackNo, $rackLocation, $roll, $poNumber, $qty, $sono, $shift, $buyer, $style, $color,
        $mcno, $mcdia, $customer, $ytype, $ycount, $ybrand, $oT, $sl, $ftype, $fgsm, $fdia,
        $fplan, $lotno, $tpoint, $mcode, $mdesc, $uname, $uid, $qcstatus, $category
    );

    if (!mysqli_stmt_execute($statement)) {
        throw new Exception('Insert failed for Roll ' . $roll . ': ' . mysqli_stmt_error($statement));
    }
}

function assertKnittingRollPassed($db, $roll) {
    $escapedRoll = mysqli_real_escape_string($db, $roll);
    $sql = "SELECT QC_STATUS FROM knitting_store_zone
        WHERE TRIM(ROLL) = '$escapedRoll'
        ORDER BY KSZTID DESC
        LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (!$result) {
        throw new Exception('Unable to verify Store Common Zone data for Roll ' . $roll . ': ' . mysqli_error($db));
    }

    $zoneRoll = mysqli_fetch_assoc($result);
    if (!$zoneRoll) {
        throw new RuntimeException('ROLL_ZONE_NOT_FOUND:' . $roll);
    }
    if (strtolower(trim((string) $zoneRoll['QC_STATUS'])) !== 'passed') {
        throw new RuntimeException('ROLL_NOT_QC_PASSED:' . $roll);
    }
}

if (!isset($db) || !$db) {
    respond(['success' => false, 'error' => 'Database connection failed'], 500);
}

if (empty($_SESSION['username'])) {
    respond(['success' => false, 'error' => 'Login required.'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(['success' => false, 'error' => 'Invalid input data'], 400);
}

if (isset($input['action']) && $input['action'] !== 'insert_store') {
    respond(['success' => false, 'error' => 'Invalid action'], 400);
}

$items = isset($input['items']) ? $input['items'] : [$input];
if (!is_array($items) || count($items) === 0) {
    respond(['success' => false, 'error' => 'At least one roll is required'], 400);
}

$rackNo = isset($input['RACKNO']) ? trim((string) $input['RACKNO']) : '';
$rackLocation = isset($input['RACKLOCATION']) ? strtoupper(trim((string) $input['RACKLOCATION'])) : '';
$rack = isset($input['RACK']) ? trim((string) $input['RACK']) : '';

if ($rackNo !== '' || $rackLocation !== '') {
    if (!preg_match('/^(?:0?[1-9]|[1-4][0-9]|50)$/', $rackNo)) {
        respond(['success' => false, 'error' => 'Invalid Rack Number. Use 01 to 50.'], 400);
    }
    if (!preg_match('/^[A-C][1-3]$/', $rackLocation)) {
        respond(['success' => false, 'error' => 'Invalid Rack Location. Use A1, A2, A3, B1, B2, B3, C1, C2 or C3.'], 400);
    }
    $rackNo = str_pad((string) ((int) $rackNo), 2, '0', STR_PAD_LEFT);
} elseif ($rack !== '') {
    $rack = strtoupper($rack);
    if (!preg_match('/^(?:0?[1-9]|[1-4][0-9]|50)[A-C][1-3]$/', $rack)) {
        respond(['success' => false, 'error' => 'Invalid rack format. Use 01A1 to 50C3.'], 400);
    }
    preg_match('/^(\d+)([A-C][1-3])$/', $rack, $rackParts);
    $rackNo = str_pad((string) ((int) $rackParts[1]), 2, '0', STR_PAD_LEFT);
    $rackLocation = $rackParts[2];
} else {
    respond(['success' => false, 'error' => 'Rack Number and Rack Location are required'], 400);
}

$rollKeys = [];
foreach ($items as $index => $item) {
    if (!is_array($item)) {
        respond(['success' => false, 'error' => 'Invalid roll data at item ' . ($index + 1)], 400);
    }

    $roll = isset($item['ROLL']) ? trim((string) $item['ROLL']) : '';
    if ($roll === '') {
        respond(['success' => false, 'error' => 'Roll number is required for item ' . ($index + 1)], 400);
    }

    $rollKey = strtoupper($roll);
    if (isset($rollKeys[$rollKey])) {
        respond(['success' => false, 'error' => 'Duplicate roll in this batch: ' . $roll], 400);
    }
    $rollKeys[$rollKey] = true;
}

$columns = mysqli_query($db, 'SHOW COLUMNS FROM knitting_store');
if (!$columns) {
    respond(['success' => false, 'error' => 'Unable to inspect Knitting Store columns: ' . mysqli_error($db)], 500);
}

$existingColumns = [];
while ($column = mysqli_fetch_assoc($columns)) {
    $existingColumns[] = strtoupper($column['Field']);
}

$requiredColumns = [
    'RACKNO' => 'ADD COLUMN RACKNO VARCHAR(50) DEFAULT NULL',
    'RACKLOCATION' => 'ADD COLUMN RACKLOCATION VARCHAR(100) DEFAULT NULL',
    'YBRAND' => 'ADD COLUMN YBRAND VARCHAR(100) DEFAULT NULL AFTER YCOUNT',
    'QC_STATUS' => 'ADD COLUMN QC_STATUS VARCHAR(50) DEFAULT NULL',
    'CATEGORY' => 'ADD COLUMN CATEGORY VARCHAR(100) DEFAULT NULL'
];
foreach ($requiredColumns as $columnName => $definition) {
    if (!in_array($columnName, $existingColumns, true) && !mysqli_query($db, 'ALTER TABLE knitting_store ' . $definition)) {
        respond(['success' => false, 'error' => 'Unable to add ' . $columnName . ' column: ' . mysqli_error($db)], 500);
    }
}

$curUser = trim((string) $_SESSION['username']);
$uname = $curUser;
$uid = $curUser;
$escapedUser = mysqli_real_escape_string($db, $curUser);
$userResult = mysqli_query($db, "SELECT USER_ID, USER_NAME FROM users WHERE USER_ID = '$escapedUser' OR USER_NAME = '$escapedUser' LIMIT 1");
if ($userResult && ($userRow = mysqli_fetch_assoc($userResult))) {
    $uid = $userRow['USER_ID'];
    $uname = $userRow['USER_NAME'];
} else {
    $operatorResult = mysqli_query($db, "SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator WHERE OPERATOR_ID = '$escapedUser' LIMIT 1");
    if ($operatorResult && ($operatorRow = mysqli_fetch_assoc($operatorResult))) {
        $uid = $operatorRow['OPERATOR_ID'];
        $uname = $operatorRow['OPERATOR_NAME'];
    }
}

$insertSql = "INSERT INTO knitting_store
    (BUDAT, RACKNO, RACKLOCATION, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR, MCNO, MCDIA,
     CUSTOMER, YTYPE, YCOUNT, YBRAND, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
     TPOINT, MCODE, MDESCRIPTION, UNAME, UID, QC_STATUS, CATEGORY)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$duplicateCheck = null;
$insertStatement = null;
$updateStatement = null;
$existingRolls = [];
$updatedCount = 0;
try {
    if (!mysqli_begin_transaction($db)) {
        throw new Exception('Unable to start store transaction: ' . mysqli_error($db));
    }

    $duplicateCheck = mysqli_prepare($db, 'SELECT KSTID FROM knitting_store WHERE TRIM(ROLL) = ? LIMIT 1');
    if (!$duplicateCheck) {
        throw new Exception('Unable to check existing rolls: ' . mysqli_error($db));
    }
    $existingKstid = null;
    if (!mysqli_stmt_bind_result($duplicateCheck, $existingKstid)) {
        throw new Exception('Unable to read existing roll checks: ' . mysqli_stmt_error($duplicateCheck));
    }

    foreach ($items as $item) {
        $roll = trim((string) $item['ROLL']);
        mysqli_stmt_bind_param($duplicateCheck, 's', $roll);
        if (!mysqli_stmt_execute($duplicateCheck)) {
            throw new Exception('Unable to check Roll ' . $roll . ': ' . mysqli_stmt_error($duplicateCheck));
        }
        $fetchResult = mysqli_stmt_fetch($duplicateCheck);
        if ($fetchResult === true) {
            $existingRolls[strtoupper($roll)] = true;
        }
        if ($fetchResult === false) {
            throw new Exception('Unable to read existing Roll ' . $roll . ': ' . mysqli_stmt_error($duplicateCheck));
        }
    }
    mysqli_stmt_close($duplicateCheck);
    $duplicateCheck = null;

    foreach ($items as $item) {
        assertKnittingRollPassed($db, trim((string) $item['ROLL']));
    }

    $insertStatement = mysqli_prepare($db, $insertSql);
    if (!$insertStatement) {
        throw new Exception('Unable to prepare store insert: ' . mysqli_error($db));
    }

    $updateStatement = mysqli_prepare(
        $db,
        'UPDATE knitting_store SET RACKNO = ?, RACKLOCATION = ? WHERE TRIM(ROLL) = ?'
    );
    if (!$updateStatement) {
        throw new Exception('Unable to prepare rack location update: ' . mysqli_error($db));
    }

    foreach ($items as $item) {
        $roll = trim((string) $item['ROLL']);
        if (isset($existingRolls[strtoupper($roll)])) {
            mysqli_stmt_bind_param($updateStatement, 'sss', $rackNo, $rackLocation, $roll);
            if (!mysqli_stmt_execute($updateStatement)) {
                throw new Exception('Unable to update rack location for Roll ' . $roll . ': ' . mysqli_stmt_error($updateStatement));
            }
            if (mysqli_stmt_affected_rows($updateStatement) > 0) {
                $updatedCount++;
            }
        } else {
            insertKnittingStoreRoll($insertStatement, $item, $rackNo, $rackLocation, $uname, $uid);
        }
    }

    mysqli_stmt_close($insertStatement);
    $insertStatement = null;
    mysqli_stmt_close($updateStatement);
    $updateStatement = null;
    if (!mysqli_commit($db)) {
        throw new Exception('Unable to commit store batch: ' . mysqli_error($db));
    }
} catch (RuntimeException $e) {
    mysqli_rollback($db);
    if ($duplicateCheck instanceof mysqli_stmt) {
        mysqli_stmt_close($duplicateCheck);
    }
    if ($insertStatement instanceof mysqli_stmt) {
        mysqli_stmt_close($insertStatement);
    }
    if ($updateStatement instanceof mysqli_stmt) {
        mysqli_stmt_close($updateStatement);
    }
    $message = $e->getMessage();
    if (strpos($message, 'ROLL_NOT_QC_PASSED:') === 0) {
        $roll = substr($message, strlen('ROLL_NOT_QC_PASSED:'));
        respond([
            'success' => false,
            'error' => 'Roll ' . $roll . ' has not passed QC and cannot be stored.'
        ], 422);
    }
    if (strpos($message, 'ROLL_ZONE_NOT_FOUND:') === 0) {
        $roll = substr($message, strlen('ROLL_ZONE_NOT_FOUND:'));
        respond([
            'success' => false,
            'error' => 'Roll ' . $roll . ' was not found in Store Common Zone.'
        ], 404);
    }
    error_log('Knitting Store batch insert failed: ' . $message);
    respond(['success' => false, 'error' => $message], 500);
} catch (Exception $e) {
    mysqli_rollback($db);
    if ($duplicateCheck instanceof mysqli_stmt) {
        mysqli_stmt_close($duplicateCheck);
    }
    if ($insertStatement instanceof mysqli_stmt) {
        mysqli_stmt_close($insertStatement);
    }
    if ($updateStatement instanceof mysqli_stmt) {
        mysqli_stmt_close($updateStatement);
    }
    error_log('Knitting Store batch insert failed: ' . $e->getMessage());
    respond(['success' => false, 'error' => $e->getMessage()], 500);
}

$storedCount = count($items);
respond([
    'success' => true,
    'stored' => $storedCount,
    'updated' => $updatedCount,
    'message' => $storedCount . ' roll(s) assigned to Rack No: ' . $rackNo . ', Location: ' . $rackLocation .
        ($updatedCount ? ' (' . $updatedCount . ' existing roll location(s) updated).' : '.')
]);
