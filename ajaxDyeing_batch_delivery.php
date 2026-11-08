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

try {
    if (!$db->query(
        "CREATE TABLE IF NOT EXISTS dyeing_batch_delivery (
            DBDTID INT(11) NOT NULL AUTO_INCREMENT,
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
            DOP VARCHAR(100) DEFAULT NULL,
            DID VARCHAR(100) DEFAULT NULL,
            PRIMARY KEY (DBDTID),
            UNIQUE KEY uq_dyeing_delivery_bcard_roll (BCARD, ROLL)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    )) {
        respond(['success' => false, 'message' => 'Could not initialize delivery records: ' . $db->error], 500);
    }

    $deliveryColumnsResult = $db->query("SHOW COLUMNS FROM dyeing_batch_delivery");
    if (!$deliveryColumnsResult) {
        respond(['success' => false, 'message' => 'Could not inspect delivery table: ' . $db->error], 500);
    }
    $deliveryColumns = [];
    while ($column = $deliveryColumnsResult->fetch_assoc()) {
        $deliveryColumns[] = strtoupper($column['Field']);
    }
    if (in_array('MCNO', $deliveryColumns, true) && in_array('DYMCNO', $deliveryColumns, true)) {
        respond(['success' => false, 'message' => 'Both MCNO and DYMCNO columns exist in dyeing_batch_delivery. Resolve the schema before continuing.'], 500);
    }
    if (in_array('MCNO', $deliveryColumns, true)) {
        if (!$db->query("ALTER TABLE dyeing_batch_delivery CHANGE COLUMN MCNO DYMCNO VARCHAR(100) DEFAULT NULL")) {
            respond(['success' => false, 'message' => 'Could not rename MCNO to DYMCNO: ' . $db->error], 500);
        }
        $deliveryColumns[array_search('MCNO', $deliveryColumns, true)] = 'DYMCNO';
    } elseif (!in_array('DYMCNO', $deliveryColumns, true)) {
        if (!$db->query("ALTER TABLE dyeing_batch_delivery ADD COLUMN DYMCNO VARCHAR(100) DEFAULT NULL AFTER COLOR")) {
            respond(['success' => false, 'message' => 'Could not add DYMCNO: ' . $db->error], 500);
        }
        $deliveryColumns[] = 'DYMCNO';
    }
    if (in_array('RACK', $deliveryColumns, true) && !$db->query("ALTER TABLE dyeing_batch_delivery DROP COLUMN RACK")) {
        respond(['success' => false, 'message' => 'Could not remove RACK from dyeing_batch_delivery: ' . $db->error], 500);
    }
    $rackIndex = array_search('RACK', $deliveryColumns, true);
    if ($rackIndex !== false) {
        unset($deliveryColumns[$rackIndex]);
    }

    $requiredDeliveryColumns = [
        'DBDTID', 'BUDAT', 'BCARD', 'ROLL', 'PO_NUMBER', 'QTY', 'SONO',
        'BUYER', 'STYLE', 'COLOR', 'DYMCNO', 'MCDIA', 'CUSTOMER', 'O_T', 'SL',
        'FTYPE', 'FGSM', 'FDIA', 'FEEDER_PLAN', 'LOT_NO', 'YTYPE', 'YCOUNT',
        'YBRAND', 'CATEGORY', 'DOP', 'DID'
    ];
    $missingDeliveryColumns = array_diff($requiredDeliveryColumns, $deliveryColumns);
    if ($missingDeliveryColumns) {
        respond([
            'success' => false,
            'message' => 'Delivery table schema is missing required columns: ' . implode(', ', $missingDeliveryColumns)
        ], 500);
    }
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize delivery records: ' . $error->getMessage()], 500);
}

function fetchDeliveryBatchRows($db, $cardNo, $forUpdate = false)
{
    $sql = "SELECT dbc.ROLL, dbc.PO_NUMBER, dbc.COLOR, dbc.QTY, dbc.DYMCNO, dbc.SONO, dbc.BUYER,
                   dbc.STYLE, dbc.MCDIA, dbc.CUSTOMER, dbc.O_T, dbc.SL,
                   dbc.FTYPE, dbc.FGSM, dbc.FDIA, dbc.YTYPE, dbc.YCOUNT,
                   dbc.FEEDER_PLAN, dbc.LOT_NO, dbc.YBRAND, dbc.CATEGORY, dbc.BCARD
            FROM dyeing_batch_card dbc
            WHERE TRIM(dbc.BCARD) = ?
            ORDER BY dbc.DBCTID ASC";
    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Database error: ' . $db->error);
    }
    $stmt->bind_param('s', $cardNo);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Database error: ' . $error);
    }

    $result = $stmt->get_result();
    $rows = [];
    $rolls = [];
    $totalQty = 0;
    while ($row = $result->fetch_assoc()) {
        $roll = trim((string)$row['ROLL']);
        if ($roll === '' || !is_numeric($row['QTY']) ||
            !is_finite((float)$row['QTY']) || (float)$row['QTY'] <= 0) {
            $stmt->close();
            throw new RuntimeException('This batch contains an invalid roll number or QTY.');
        }
        if (isset($rolls[$roll])) {
            $stmt->close();
            throw new RuntimeException('This batch contains duplicate roll ' . $roll . '. Please correct the batch data.');
        }
        $rolls[$roll] = true;
        $row['ROLL'] = $roll;
        $totalQty += (float)$row['QTY'];
        $rows[] = $row;
    }
    $stmt->close();

    return [
        'rows' => $rows,
        'rolls' => $rolls,
        'total_qty' => $totalQty,
        'target_qty' => $totalQty
    ];
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$input = json_decode(file_get_contents('php://input'), true);
$inTransaction = false;

try {
    if ($action === 'load_batch') {
        $cardNo = isset($_GET['card_no']) ? trim((string)$_GET['card_no']) : '';
        if ($cardNo === '') {
            respond(['success' => false, 'message' => 'Enter a Batch Card number.'], 400);
        }

        $batch = fetchDeliveryBatchRows($db, $cardNo);
        if (!$batch['rows']) {
            respond(['success' => false, 'message' => 'Batch Card ' . $cardNo . ' was not found.'], 404);
        }

        $stmt = $db->prepare(
            "SELECT BUDAT, DOP, DID
             FROM dyeing_batch_delivery WHERE BCARD = ? ORDER BY DBDTID ASC LIMIT 1"
        );
        if (!$stmt) {
            respond(['success' => false, 'message' => 'Database error: ' . $db->error], 500);
        }
        $stmt->bind_param('s', $cardNo);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            respond(['success' => false, 'message' => 'Database error: ' . $error], 500);
        }
        $delivery = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        respond([
            'success' => true,
            'card_no' => $cardNo,
            'rows' => $batch['rows'],
            'roll_count' => count($batch['rows']),
            'total_qty' => round($batch['total_qty'], 3),
            'target_qty' => round($batch['target_qty'], 3),
            'delivery' => $delivery
        ]);
    }

    if ($action === 'load_batch_by_roll') {
        $roll = isset($_GET['roll']) ? trim((string)$_GET['roll']) : '';
        if ($roll === '') {
            respond(['success' => false, 'message' => 'Scan or enter a roll number first.'], 400);
        }

        $stmt = $db->prepare(
            "SELECT BCARD
             FROM dyeing_batch_card
             WHERE TRIM(ROLL) = ?
             ORDER BY DBCTID DESC
             LIMIT 1"
        );
        if (!$stmt) {
            respond(['success' => false, 'message' => 'Database error: ' . $db->error], 500);
        }
        $stmt->bind_param('s', $roll);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            respond(['success' => false, 'message' => 'Database error: ' . $error], 500);
        }
        $card = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$card) {
            respond(['success' => false, 'message' => 'Roll ' . $roll . ' was not found in any Dyeing Batch Card.'], 404);
        }

        $cardNo = trim((string)$card['BCARD']);
        $batch = fetchDeliveryBatchRows($db, $cardNo);
        if (!$batch['rows']) {
            respond(['success' => false, 'message' => 'No rolls found for Batch Card ' . $cardNo . '.'], 404);
        }

        $stmt = $db->prepare(
            "SELECT BUDAT, DOP, DID
             FROM dyeing_batch_delivery WHERE BCARD = ? ORDER BY DBDTID ASC LIMIT 1"
        );
        if (!$stmt) {
            respond(['success' => false, 'message' => 'Database error: ' . $db->error], 500);
        }
        $stmt->bind_param('s', $cardNo);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            respond(['success' => false, 'message' => 'Database error: ' . $error], 500);
        }
        $delivery = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        respond([
            'success' => true,
            'card_no' => $cardNo,
            'rows' => $batch['rows'],
            'roll_count' => count($batch['rows']),
            'total_qty' => round($batch['total_qty'], 3),
            'target_qty' => round($batch['target_qty'], 3),
            'delivery' => $delivery
        ]);
    }

    if ($action === 'confirm_delivery') {
        $cardNo = isset($input['card_no']) && is_scalar($input['card_no'])
            ? trim((string)$input['card_no'])
            : '';
        $scannedRolls = isset($input['rolls']) && is_array($input['rolls']) ? $input['rolls'] : [];
        $loadedTotalQty = isset($input['total_qty']) && is_numeric($input['total_qty'])
            ? (float)$input['total_qty']
            : -1;
        $scannedRolls = array_map(function ($roll) {
            return is_scalar($roll) ? trim((string)$roll) : '';
        }, $scannedRolls);

        if ($cardNo === '' || !$scannedRolls || !is_finite($loadedTotalQty) || $loadedTotalQty < 0) {
            respond(['success' => false, 'message' => 'Load a batch and scan all of its rolls first.'], 400);
        }
        if (count($scannedRolls) > 500) {
            respond(['success' => false, 'message' => 'This batch has too many rolls to deliver in one request.'], 400);
        }
        if (count(array_unique($scannedRolls)) !== count($scannedRolls)) {
            respond(['success' => false, 'message' => 'A roll was scanned more than once.'], 400);
        }

        $db->begin_transaction();
        $inTransaction = true;
        $batch = fetchDeliveryBatchRows($db, $cardNo, true);
        if (!$batch['rows']) {
            $db->rollback();
            $inTransaction = false;
            respond(['success' => false, 'message' => 'Batch Card ' . $cardNo . ' was not found.'], 404);
        }
        if (abs($loadedTotalQty - $batch['target_qty']) > 0.0005) {
            $db->rollback();
            $inTransaction = false;
            respond(['success' => false, 'message' => 'Batch Target QTY changed after it was loaded. Reload the batch before confirming delivery.'], 409);
        }

        $stmt = $db->prepare("SELECT DBDTID FROM dyeing_batch_delivery WHERE BCARD = ? LIMIT 1 FOR UPDATE");
        if (!$stmt) {
            throw new RuntimeException('Database error: ' . $db->error);
        }
        $stmt->bind_param('s', $cardNo);
        if (!$stmt->execute()) {
            throw new RuntimeException('Database error: ' . $stmt->error);
        }
        $alreadyDelivered = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($alreadyDelivered) {
            $db->rollback();
            $inTransaction = false;
            respond(['success' => false, 'message' => 'This Batch Card has already been delivered.'], 409);
        }

        foreach ($scannedRolls as $roll) {
            if ($roll === '' || !isset($batch['rolls'][$roll])) {
                $db->rollback();
                $inTransaction = false;
                respond([
                    'success' => false,
                    'message' => 'Roll ' . $roll . ' does not match this batch.'
                ], 422);
            }
        }
        if (count($scannedRolls) !== count($batch['rows'])) {
            $missing = array_values(array_diff(array_keys($batch['rolls']), $scannedRolls));
            $db->rollback();
            $inTransaction = false;
            respond([
                'success' => false,
                'message' => 'Delivery not confirmed. Scan all rolls in this batch. Missing: ' . implode(', ', $missing)
            ], 422);
        }

        $scannedQty = 0;
        foreach ($scannedRolls as $roll) {
            foreach ($batch['rows'] as $row) {
                if ($row['ROLL'] === $roll) {
                    $scannedQty += (float)$row['QTY'];
                    break;
                }
            }
        }
        if ($scannedQty - $batch['target_qty'] > 0.0005) {
            $db->rollback();
            $inTransaction = false;
            respond([
                'success' => false,
                'message' => 'Over quantity by ' . number_format($scannedQty - $batch['target_qty'], 3) .
                    ' KG. Batch total is ' . number_format($batch['target_qty'], 3) .
                    ' KG; scanned total is ' . number_format($scannedQty, 3) .
                    ' KG. Delivery was not confirmed.'
            ], 422);
        }
        if (abs($scannedQty - $batch['target_qty']) > 0.0005) {
            $db->rollback();
            $inTransaction = false;
            respond([
                'success' => false,
                'message' => 'Scanned QTY ' . number_format($scannedQty, 3) .
                    ' KG does not match batch total ' . number_format($batch['target_qty'], 3) .
                    ' KG. Delivery was not confirmed.'
            ], 422);
        }

        $dop = isset($_SESSION['user_name']) ? (string)$_SESSION['user_name']
            : (isset($_SESSION['operator_name']) ? (string)$_SESSION['operator_name'] : (string)$_SESSION['username']);
        $did = isset($_SESSION['user_id']) ? (string)$_SESSION['user_id']
            : (isset($_SESSION['operator_id']) ? (string)$_SESSION['operator_id']
                : (isset($_SESSION['knitting_qc_id']) ? (string)$_SESSION['knitting_qc_id'] : (string)$_SESSION['username']));
        $deliveryDate = date('Y-m-d');
        $stmt = $db->prepare(
            "INSERT INTO dyeing_batch_delivery
             (BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, DYMCNO,
              MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO, YTYPE,
              YCOUNT, YBRAND, CATEGORY, DOP, DID)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new RuntimeException('Database error: ' . $db->error);
        }
        $rollCount = count($scannedRolls);
        foreach ($scannedRolls as $roll) {
            $row = null;
            foreach ($batch['rows'] as $batchRow) {
                if ($batchRow['ROLL'] === $roll) {
                    $row = $batchRow;
                    break;
                }
            }
            if ($row === null) {
                $stmt->close();
                throw new RuntimeException('Roll ' . $roll . ' was not found in the loaded batch.');
            }
            $stmt->bind_param(
                'sssssssssssssssssssssssss',
                $deliveryDate,
                $cardNo,
                $row['ROLL'],
                $row['PO_NUMBER'],
                $row['QTY'],
                $row['SONO'],
                $row['BUYER'],
                $row['STYLE'],
                $row['COLOR'],
                $row['DYMCNO'],
                $row['MCDIA'],
                $row['CUSTOMER'],
                $row['O_T'],
                $row['SL'],
                $row['FTYPE'],
                $row['FGSM'],
                $row['FDIA'],
                $row['FEEDER_PLAN'],
                $row['LOT_NO'],
                $row['YTYPE'],
                $row['YCOUNT'],
                $row['YBRAND'],
                $row['CATEGORY'],
                $dop,
                $did
            );
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new RuntimeException('Could not save delivery for roll ' . $roll . ': ' . $error);
            }
        }
        $stmt->close();
        if (!$db->commit()) {
            throw new RuntimeException('Could not commit the delivery confirmation: ' . $db->error);
        }
        $inTransaction = false;

        respond([
            'success' => true,
            'message' => 'Batch Card ' . $cardNo . ' delivery confirmed.',
            'card_no' => $cardNo,
            'roll_count' => $rollCount,
            'total_qty' => round($scannedQty, 3)
        ]);
    }

    respond(['success' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $error) {
    if ($inTransaction) {
        $db->rollback();
    }
    respond(['success' => false, 'message' => $error->getMessage()], 500);
}
