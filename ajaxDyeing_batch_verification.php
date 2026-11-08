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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Use POST to save batch verification.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(['success' => false, 'message' => 'Invalid verification request.'], 400);
}

$action = isset($input['action']) && is_string($input['action']) ? $input['action'] : '';
if ($action === 'update_decisions') {
    $password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';
    $batchId = isset($input['batch_id']) && is_scalar($input['batch_id'])
        ? trim((string)$input['batch_id'])
        : '';
    $decisions = isset($input['decisions']) && is_array($input['decisions'])
        ? $input['decisions']
        : [];
    $roleColumns = ['SINCHARGE', 'SUPERVISOR', 'BINCHARGE', 'BMANAGER'];

    if (!hash_equals('1122', $password)) {
        respond(['success' => false, 'message' => 'Incorrect verification password.'], 403);
    }
    if ($batchId === '' || strlen($batchId) > 30) {
        respond(['success' => false, 'message' => 'Invalid Batch Card.'], 400);
    }

    $passCount = 0;
    $failCount = 0;
    foreach ($roleColumns as $column) {
        if (!isset($decisions[$column]) || !in_array($decisions[$column], ['pass', 'fail'], true)) {
            respond(['success' => false, 'message' => 'Choose PASS or FAIL for all four roles.'], 400);
        }
        if ($decisions[$column] === 'pass') {
            $passCount++;
        } else {
            $failCount++;
        }
    }
    if (!(($passCount >= 2 && $passCount > $failCount) ||
        ($failCount >= 2 && $failCount > $passCount))) {
        respond(['success' => false, 'message' => 'Role decisions cannot be tied; choose a clear PASS or FAIL majority.'], 400);
    }

    $inEditTransaction = false;
    try {
        if (!$db->begin_transaction()) {
            throw new RuntimeException('Could not start role-decision transaction: ' . $db->error);
        }
        $inEditTransaction = true;
        $deliveryStmt = $db->prepare(
            'SELECT COUNT(DISTINCT ROLL) AS ROLL_COUNT
             FROM dyeing_batch_delivery
             WHERE BCARD = ?'
        );
        if (!$deliveryStmt) {
            throw new RuntimeException('Could not prepare delivery roll count: ' . $db->error);
        }
        if (!$deliveryStmt->bind_param('s', $batchId) || !$deliveryStmt->execute()) {
            throw new RuntimeException('Could not count batch delivery rolls: ' . $deliveryStmt->error);
        }
        $deliveryCount = (int)$deliveryStmt->get_result()->fetch_assoc()['ROLL_COUNT'];
        $deliveryStmt->close();
        if ($deliveryCount === 0) {
            throw new RuntimeException('No delivery rolls were found for this Batch Card.');
        }

        $roleStmt = $db->prepare(
            "SELECT SINCHARGE, SUPERVISOR, BINCHARGE, BMANAGER
             FROM dyeing_batch_verification
             WHERE BCARD = ?
             FOR UPDATE"
        );
        if (!$roleStmt) {
            throw new RuntimeException('Could not prepare verification lookup: ' . $db->error);
        }
        if (!$roleStmt->bind_param('s', $batchId) || !$roleStmt->execute()) {
            throw new RuntimeException('Could not load batch verification: ' . $roleStmt->error);
        }
        $verificationResult = $roleStmt->get_result();
        $verificationCount = 0;
        $hasVerifiedRoll = false;
        while ($verification = $verificationResult->fetch_assoc()) {
            $verificationCount++;
            $currentPassCount = 0;
            $currentFailCount = 0;
            foreach ($roleColumns as $column) {
                if ($verification[$column] === 'PASS') {
                    $currentPassCount++;
                } elseif ($verification[$column] === 'FAIL') {
                    $currentFailCount++;
                }
            }
            if (($currentPassCount >= 2 && $currentPassCount > $currentFailCount) ||
                ($currentFailCount >= 2 && $currentFailCount > $currentPassCount)) {
                $hasVerifiedRoll = true;
            }
        }
        $roleStmt->close();
        if ($verificationCount === 0) {
            throw new RuntimeException('Verification records were not found for this Batch Card.');
        }
        if (!$hasVerifiedRoll) {
            throw new RuntimeException('At least one PASS or FAIL roll is required to edit this Batch Card.');
        }

        $roleValues = [];
        foreach ($roleColumns as $column) {
            $roleValues[] = strtoupper($decisions[$column]);
        }
        $insertMissingStmt = $db->prepare(
            "INSERT INTO dyeing_batch_verification (
                BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, DYMCNO,
                MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
                YTYPE, YCOUNT, YBRAND, CATEGORY, SINCHARGE, SUPERVISOR, BINCHARGE,
                BMANAGER, DOP, DID
            )
            SELECT d.BUDAT, d.BCARD, d.ROLL, d.PO_NUMBER, d.QTY, d.SONO, d.BUYER,
                   d.STYLE, d.COLOR, d.DYMCNO, d.MCDIA, d.CUSTOMER, d.O_T, d.SL,
                   d.FTYPE, d.FGSM, d.FDIA, d.FEEDER_PLAN, d.LOT_NO, d.YTYPE,
                   d.YCOUNT, d.YBRAND, d.CATEGORY, ?, ?, ?, ?, d.DOP, d.DID
            FROM dyeing_batch_delivery d
            LEFT JOIN dyeing_batch_verification v
                ON v.BCARD = d.BCARD AND v.ROLL = d.ROLL
            WHERE d.BCARD = ? AND v.DBVTID IS NULL"
        );
        if (!$insertMissingStmt) {
            throw new RuntimeException('Could not prepare missing roll verification insert: ' . $db->error);
        }
        if (!$insertMissingStmt->bind_param(
            'sssss',
            $roleValues[0],
            $roleValues[1],
            $roleValues[2],
            $roleValues[3],
            $batchId
        ) || !$insertMissingStmt->execute()) {
            $error = $insertMissingStmt->error;
            $insertMissingStmt->close();
            throw new RuntimeException('Could not add missing roll verification: ' . $error);
        }
        $insertMissingStmt->close();

        $updateStmt = $db->prepare(
            'UPDATE dyeing_batch_verification
             SET SINCHARGE = ?, SUPERVISOR = ?, BINCHARGE = ?, BMANAGER = ?
             WHERE BCARD = ?'
        );
        if (!$updateStmt) {
            throw new RuntimeException('Could not prepare role-decision update: ' . $db->error);
        }
        if (!$updateStmt->bind_param(
            'sssss',
            $roleValues[0],
            $roleValues[1],
            $roleValues[2],
            $roleValues[3],
            $batchId
        ) || !$updateStmt->execute()) {
            $error = $updateStmt->error;
            $updateStmt->close();
            throw new RuntimeException('Could not update role decisions: ' . $error);
        }
        $updateStmt->close();
        if (!$db->commit()) {
            throw new RuntimeException('Could not complete the role-decision update: ' . $db->error);
        }
        $inEditTransaction = false;
        respond([
            'success' => true,
            'message' => 'Role decisions saved for every roll in the Batch Card.',
            'roll_count' => $deliveryCount,
            'status' => $passCount > $failCount ? 'pass' : 'fail'
        ]);
    } catch (Throwable $error) {
        if ($inEditTransaction) {
            $db->rollback();
        }
        respond(['success' => false, 'message' => $error->getMessage()], 500);
    }
}

if ($action === 'authorize_edit') {
    $password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';
    $batchId = isset($input['batch_id']) && is_scalar($input['batch_id'])
        ? trim((string)$input['batch_id'])
        : '';

    if (!hash_equals('1122', $password)) {
        respond(['success' => false, 'message' => 'Incorrect verification password.'], 403);
    }
    if ($batchId === '' || strlen($batchId) > 30) {
        respond(['success' => false, 'message' => 'Invalid Batch Card.'], 400);
    }

    try {
        $roleStmt = $db->prepare(
            "SELECT SINCHARGE, SUPERVISOR, BINCHARGE, BMANAGER
             FROM dyeing_batch_verification
             WHERE BCARD = ?"
        );
        if (!$roleStmt) {
            throw new RuntimeException('Could not prepare verification lookup: ' . $db->error);
        }
        if (!$roleStmt->bind_param('s', $batchId) || !$roleStmt->execute()) {
            throw new RuntimeException('Could not load batch verification: ' . $roleStmt->error);
        }
        $verificationResult = $roleStmt->get_result();
        $verificationCount = 0;
        $hasVerifiedRoll = false;
        while ($verification = $verificationResult->fetch_assoc()) {
            $verificationCount++;
            $passCount = 0;
            $failCount = 0;
            foreach (['SINCHARGE', 'SUPERVISOR', 'BINCHARGE', 'BMANAGER'] as $roleColumn) {
                if ($verification[$roleColumn] === 'PASS') {
                    $passCount++;
                } elseif ($verification[$roleColumn] === 'FAIL') {
                    $failCount++;
                }
            }
            if (($passCount >= 2 && $passCount > $failCount) ||
                ($failCount >= 2 && $failCount > $passCount)) {
                $hasVerifiedRoll = true;
            }
        }
        $roleStmt->close();
        if ($verificationCount === 0) {
            respond(['success' => false, 'message' => 'Verification records were not found for this Batch Card.'], 404);
        }
        if (!$hasVerifiedRoll) {
            respond(['success' => false, 'message' => 'At least one PASS or FAIL roll is required to edit this Batch Card.'], 409);
        }
        respond(['success' => true, 'message' => 'Edit authorized for every roll in the Batch Card.']);
    } catch (Throwable $error) {
        respond(['success' => false, 'message' => $error->getMessage()], 500);
    }
}

$batchIds = isset($input['batch_ids']) && is_array($input['batch_ids'])
    ? array_values(array_unique(array_filter(array_map(function ($batchId) {
        return is_string($batchId) || is_numeric($batchId) ? trim((string)$batchId) : '';
    }, $input['batch_ids']), function ($batchId) {
        return $batchId !== '';
    })))
    : [];
$decisions = isset($input['decisions']) && is_array($input['decisions'])
    ? $input['decisions']
    : [];

if (!$batchIds || count($batchIds) > 500) {
    respond(['success' => false, 'message' => 'No valid batch cards were provided.'], 400);
}
foreach ($batchIds as $batchId) {
    if (strlen($batchId) > 30) {
        respond(['success' => false, 'message' => 'A Batch Card number is too long.'], 400);
    }
}

$roleColumns = ['SINCHARGE', 'SUPERVISOR', 'BINCHARGE', 'BMANAGER'];
$validDecisions = ['pass', 'fail'];
$passCount = 0;
$failCount = 0;
foreach ($roleColumns as $column) {
    if (!array_key_exists($column, $decisions)) {
        continue;
    }
    if (!in_array($decisions[$column], $validDecisions, true)) {
        respond(['success' => false, 'message' => 'Role decisions must be PASS or FAIL.'], 400);
    }
    if ($decisions[$column] === 'pass') {
        $passCount++;
    } else {
        $failCount++;
    }
}

if (!(($passCount >= 2 && $passCount > $failCount) || ($failCount >= 2 && $failCount > $passCount))) {
    respond(['success' => false, 'message' => 'At least two matching decisions with no tie are required.'], 400);
}

$inTransaction = false;
try {
    $createTableSql = "CREATE TABLE IF NOT EXISTS dyeing_batch_verification (
        DBVTID INT(11) NOT NULL AUTO_INCREMENT,
        BUDAT DATE DEFAULT NULL,
        BCARD VARCHAR(30) DEFAULT NULL,
        ROLL VARCHAR(50) NOT NULL,
        PO_NUMBER VARCHAR(50) DEFAULT NULL,
        QTY VARCHAR(50) DEFAULT NULL,
        SONO VARCHAR(50) DEFAULT NULL,
        BUYER VARCHAR(100) DEFAULT NULL,
        STYLE VARCHAR(100) DEFAULT NULL,
        COLOR VARCHAR(100) DEFAULT NULL,
        DYMCNO VARCHAR(100) DEFAULT NULL,
        MCDIA VARCHAR(100) DEFAULT NULL,
        CUSTOMER VARCHAR(100) DEFAULT NULL,
        O_T VARCHAR(20) DEFAULT NULL,
        SL VARCHAR(100) DEFAULT NULL,
        FTYPE VARCHAR(50) DEFAULT NULL,
        FGSM VARCHAR(20) DEFAULT NULL,
        FDIA VARCHAR(20) DEFAULT NULL,
        FEEDER_PLAN VARCHAR(100) DEFAULT NULL,
        LOT_NO VARCHAR(100) DEFAULT NULL,
        YTYPE VARCHAR(50) DEFAULT NULL,
        YCOUNT VARCHAR(100) DEFAULT NULL,
        YBRAND VARCHAR(100) DEFAULT NULL,
        CATEGORY VARCHAR(100) DEFAULT NULL,
        SINCHARGE VARCHAR(100) DEFAULT NULL,
        SUPERVISOR VARCHAR(100) DEFAULT NULL,
        BINCHARGE VARCHAR(100) DEFAULT NULL,
        BMANAGER VARCHAR(100) DEFAULT NULL,
        DOP VARCHAR(100) DEFAULT NULL,
        DID VARCHAR(100) DEFAULT NULL,
        CREATEDAT DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (DBVTID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    if (!$db->query($createTableSql)) {
        throw new RuntimeException('Could not initialize batch verification table: ' . $db->error);
    }

    $insertSql = "INSERT INTO dyeing_batch_verification (
        BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, DYMCNO,
        MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
        YTYPE, YCOUNT, YBRAND, CATEGORY, SINCHARGE, SUPERVISOR, BINCHARGE,
        BMANAGER, DOP, DID
    )
    SELECT BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, DYMCNO,
           MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
           YTYPE, YCOUNT, YBRAND, CATEGORY, ?, ?, ?, ?, DOP, DID
    FROM dyeing_batch_delivery
    WHERE BCARD = ?";
    $insertStmt = $db->prepare($insertSql);
    if (!$insertStmt) {
        throw new RuntimeException('Could not prepare verification insert: ' . $db->error);
    }

    $deleteStmt = $db->prepare('DELETE FROM dyeing_batch_verification WHERE BCARD = ?');
    if (!$deleteStmt) {
        $insertStmt->close();
        throw new RuntimeException('Could not prepare verification update: ' . $db->error);
    }

    if (!$db->begin_transaction()) {
        throw new RuntimeException('Could not start verification transaction: ' . $db->error);
    }
    $inTransaction = true;
    $savedCards = [];
    foreach ($batchIds as $batchId) {
        $deleteStmt->bind_param('s', $batchId);
        if (!$deleteStmt->execute()) {
            throw new RuntimeException('Could not replace prior verification for ' . $batchId . ': ' . $deleteStmt->error);
        }

        $shiftIncharge = isset($decisions['SINCHARGE']) ? strtoupper($decisions['SINCHARGE']) : null;
        $supervisor = isset($decisions['SUPERVISOR']) ? strtoupper($decisions['SUPERVISOR']) : null;
        $batchIncharge = isset($decisions['BINCHARGE']) ? strtoupper($decisions['BINCHARGE']) : null;
        $batchManager = isset($decisions['BMANAGER']) ? strtoupper($decisions['BMANAGER']) : null;
        $insertStmt->bind_param(
            'sssss',
            $shiftIncharge,
            $supervisor,
            $batchIncharge,
            $batchManager,
            $batchId
        );
        if (!$insertStmt->execute()) {
            throw new RuntimeException('Could not save verification for ' . $batchId . ': ' . $insertStmt->error);
        }
        if ($insertStmt->affected_rows < 1) {
            throw new RuntimeException('No delivery rolls were found for Batch Card ' . $batchId . '.');
        }
        $savedCards[] = $batchId;
    }

    $insertStmt->close();
    $deleteStmt->close();
    $db->commit();
    $inTransaction = false;
    respond([
        'success' => true,
        'message' => 'Batch verification saved successfully.',
        'batch_ids' => $savedCards,
        'status' => $passCount > $failCount ? 'pass' : 'fail'
    ]);
} catch (Throwable $error) {
    if ($inTransaction) {
        $db->rollback();
    }
    respond(['success' => false, 'message' => $error->getMessage()], 500);
}
