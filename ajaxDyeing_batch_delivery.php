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
            ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            BCMTID VARCHAR(30) NOT NULL,
            DELIVERED_QTY DECIMAL(12,3) NOT NULL,
            ROLL_COUNT INT UNSIGNED NOT NULL,
            UNAME VARCHAR(100) DEFAULT NULL,
            DELIVERED_AT TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (ID),
            UNIQUE KEY uq_dyeing_batch_delivery_bcmtid (BCMTID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    )) {
        respond(['success' => false, 'message' => 'Could not initialize delivery records: ' . $db->error], 500);
    }
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize delivery records: ' . $error->getMessage()], 500);
}

function fetchDeliveryBatchRows($db, $cardNo, $forUpdate = false)
{
    $sql = "SELECT dbc.ROLL, dbc.RACK, dbc.QTY, dbc.PO_NUMBER, dbc.SONO, dbc.BUYER,
                   dbc.STYLE, dbc.COLOR, dbc.MCDIA, dbc.CUSTOMER, dbc.O_T, dbc.SL,
                   dbc.FTYPE, dbc.FGSM, dbc.FDIA, dbc.YTYPE, dbc.YCOUNT,
                   COALESCE((SELECT ks.YBRAND FROM knitting_store ks
                             WHERE TRIM(ks.ROLL) = TRIM(dbc.ROLL)
                             ORDER BY ks.KSTID DESC LIMIT 1), '') AS YBRAND,
                   dbc.FEEDER_PLAN, dbc.LOT_NO,
                   COALESCE((SELECT ks.CATEGORY FROM knitting_store ks
                             WHERE TRIM(ks.ROLL) = TRIM(dbc.ROLL)
                             ORDER BY ks.KSTID DESC LIMIT 1), '') AS CATEGORY,
                   dbc.BCMTID
            FROM dyeing_batch_card dbc
            WHERE TRIM(dbc.BCMTID) = ?
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
            "SELECT DELIVERED_QTY, ROLL_COUNT, UNAME, DELIVERED_AT
             FROM dyeing_batch_delivery WHERE BCMTID = ? LIMIT 1"
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
            "SELECT BCMTID
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

        $cardNo = trim((string)$card['BCMTID']);
        $batch = fetchDeliveryBatchRows($db, $cardNo);
        if (!$batch['rows']) {
            respond(['success' => false, 'message' => 'No rolls found for Batch Card ' . $cardNo . '.'], 404);
        }

        $stmt = $db->prepare(
            "SELECT DELIVERED_QTY, ROLL_COUNT, UNAME, DELIVERED_AT
             FROM dyeing_batch_delivery WHERE BCMTID = ? LIMIT 1"
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

        $stmt = $db->prepare("SELECT ID FROM dyeing_batch_delivery WHERE BCMTID = ? LIMIT 1 FOR UPDATE");
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

        $user = isset($_SESSION['username']) ? (string)$_SESSION['username'] : '';
        $stmt = $db->prepare(
            "INSERT INTO dyeing_batch_delivery (BCMTID, DELIVERED_QTY, ROLL_COUNT, UNAME)
             VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new RuntimeException('Database error: ' . $db->error);
        }
        $rollCount = count($scannedRolls);
        $stmt->bind_param('sdis', $cardNo, $scannedQty, $rollCount, $user);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Could not confirm delivery: ' . $error);
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
