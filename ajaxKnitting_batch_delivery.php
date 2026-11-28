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

function formatDeliveryQuantity($value)
{
    if (!is_numeric($value) || !is_finite((float)$value)) {
        throw new InvalidArgumentException('A delivery line has an invalid quantity.');
    }

    $formatted = rtrim(rtrim(number_format((float)$value, 3, '.', ''), '0'), '.');
    return $formatted === '' || $formatted === '-0' ? '0' : $formatted;
}

function ensureDeliveryColumnAfter($db, &$columns, $name, $definition, $after)
{
    $name = strtoupper($name);
    $after = strtoupper($after);
    $columnIndex = array_search($name, $columns, true);
    $afterIndex = array_search($after, $columns, true);
    if ($afterIndex === false) {
        throw new RuntimeException('Cannot position ' . $name . ' after missing column ' . $after . '.');
    }

    if ($columnIndex === false) {
        if (!$db->query("ALTER TABLE dyeing_batch_delivery ADD COLUMN {$name} {$definition} AFTER {$after}")) {
            throw new RuntimeException('Could not add ' . $name . ' to delivery records: ' . $db->error);
        }
        array_splice($columns, $afterIndex + 1, 0, [$name]);
        return;
    }

    if ($columnIndex === $afterIndex + 1) {
        return;
    }

    if (!$db->query("ALTER TABLE dyeing_batch_delivery MODIFY COLUMN {$name} {$definition} AFTER {$after}")) {
        throw new RuntimeException('Could not position ' . $name . ' in delivery records: ' . $db->error);
    }
    array_splice($columns, $columnIndex, 1);
    $afterIndex = array_search($after, $columns, true);
    array_splice($columns, $afterIndex + 1, 0, [$name]);
}

class DeliveryRequestException extends RuntimeException
{
    public $statusCode;

    public function __construct($message, $statusCode)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }
}

if (!isset($_SESSION['username'])) {
    respond(['success' => false, 'message' => 'Unauthorized. Please login.'], 401);
}

try {
    ensureDyeingBatchQuantityColumns($db);
    if (!$db->query(
        "CREATE TABLE IF NOT EXISTS dyeing_batch_delivery (
            DBDTID INT(11) NOT NULL AUTO_INCREMENT,
            BUDAT DATE DEFAULT NULL,
            KCHALLAN VARCHAR(20) DEFAULT NULL,
            TO_NAME VARCHAR(255) DEFAULT NULL,
            BCARD VARCHAR(30) DEFAULT NULL,
            ROLL VARCHAR(50) DEFAULT NULL,
            BATCH_LINE_ID INT(11) DEFAULT NULL,
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
            GATE_PASS_NO VARCHAR(100) DEFAULT NULL,
            VEHICLE_NO VARCHAR(100) DEFAULT NULL,
            DOP VARCHAR(100) DEFAULT NULL,
            DID VARCHAR(100) DEFAULT NULL,
            PRIMARY KEY (DBDTID),
            KEY idx_dyeing_delivery_line (BCARD, BATCH_LINE_ID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    )) {
        respond(['success' => false, 'message' => 'Could not initialize delivery records: ' . $db->error], 500);
    }
    ensureDyeingBatchLineSchema($db);
    ensureDyeingProcessColumns($db, 'dyeing_batch_card');
    ensureDyeingProcessColumns($db, 'dyeing_batch_delivery');

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
        $deliveryColumns = array_values($deliveryColumns);
    }

    ensureDeliveryColumnAfter($db, $deliveryColumns, 'KCHALLAN', 'VARCHAR(20) DEFAULT NULL', 'BUDAT');
    ensureDeliveryColumnAfter($db, $deliveryColumns, 'TO_NAME', 'VARCHAR(255) DEFAULT NULL', 'KCHALLAN');
    ensureDeliveryColumnAfter($db, $deliveryColumns, 'GATE_PASS_NO', 'VARCHAR(100) DEFAULT NULL', 'CATEGORY');
    ensureDeliveryColumnAfter($db, $deliveryColumns, 'VEHICLE_NO', 'VARCHAR(100) DEFAULT NULL', 'GATE_PASS_NO');

    $requiredDeliveryColumns = [
        'DBDTID', 'BUDAT', 'KCHALLAN', 'TO_NAME', 'BCARD', 'ROLL', 'BATCH_LINE_ID', 'PO_NUMBER', 'QTY', 'SONO',
        'BUYER', 'STYLE', 'COLOR', 'DYMCNO', 'MCDIA', 'CUSTOMER', 'O_T', 'SL',
        'FTYPE', 'FGSM', 'FDIA', 'FEEDER_PLAN', 'LOT_NO', 'YTYPE', 'YCOUNT',
        'YBRAND', 'CATEGORY', 'GATE_PASS_NO', 'VEHICLE_NO', 'DOP', 'DID'
    ];
    $missingDeliveryColumns = array_diff($requiredDeliveryColumns, $deliveryColumns);
    if ($missingDeliveryColumns) {
        respond([
            'success' => false,
            'message' => 'Delivery table schema is missing required columns: ' . implode(', ', $missingDeliveryColumns)
        ], 500);
    }

    foreach (['DELIVERY_REMARKS' => 'VARCHAR(255) DEFAULT NULL'] as $columnName => $definition) {
        if (!in_array($columnName, $deliveryColumns, true)) {
            if (!$db->query("ALTER TABLE dyeing_batch_delivery ADD COLUMN {$columnName} {$definition}")) {
                respond([
                    'success' => false,
                    'message' => 'Could not add ' . $columnName . ' to delivery details: ' . $db->error
                ], 500);
            }
            $deliveryColumns[] = $columnName;
        }
    }

    $nonOperatorColumns = array_values(array_diff($deliveryColumns, ['DOP', 'DID']));
    $lastNonOperatorColumn = end($nonOperatorColumns);
    ensureDeliveryColumnAfter($db, $deliveryColumns, 'DOP', 'VARCHAR(100) DEFAULT NULL', $lastNonOperatorColumn);
    ensureDeliveryColumnAfter($db, $deliveryColumns, 'DID', 'VARCHAR(100) DEFAULT NULL', 'DOP');

    $legacyTableResult = $db->query("SHOW TABLES LIKE 'knitting_delivery_challan'");
    if (!$legacyTableResult) {
        respond(['success' => false, 'message' => 'Could not check the old Knitting challan table: ' . $db->error], 500);
    }
    if ($legacyTableResult->num_rows > 0) {
        $headerSchema = $db->query("SHOW COLUMNS FROM knitting_delivery_challan");
        if (!$headerSchema) {
            respond(['success' => false, 'message' => 'Could not inspect the old Knitting challan table: ' . $db->error], 500);
        }
        $headerColumns = [];
        while ($column = $headerSchema->fetch_assoc()) {
            $headerColumns[] = strtoupper($column['Field']);
        }
        $headerSchema->free();

        if (!in_array('KNITTING_CHALLAN_ID', $deliveryColumns, true) ||
            !in_array('KDCID', $headerColumns, true)) {
            respond([
                'success' => false,
                'message' => 'Could not migrate old challan data because the legacy challan link is missing.'
            ], 500);
        }

        $legacyExpressions = [
            'KCHALLAN' => in_array('KCHALLAN', $headerColumns, true)
                ? "COALESCE(NULLIF(h.KCHALLAN, ''), CONCAT('C', LPAD(h.KDCID, 9, '0')))"
                : "CONCAT('C', LPAD(h.KDCID, 9, '0'))"
        ];
        foreach ([
            'TO_NAME' => 'TO_NAME',
            'GATE_PASS_NO' => 'GATE_PASS_NO',
            'VEHICLE_NO' => 'VEHICLE_NO'
        ] as $deliveryColumn => $headerColumn) {
            if (in_array($headerColumn, $headerColumns, true)) {
                $legacyExpressions[$deliveryColumn] = "h.{$headerColumn}";
            }
        }

        $legacyAssignments = [];
        $legacyEmptyChecks = [];
        foreach ($legacyExpressions as $deliveryColumn => $expression) {
            $legacyAssignments[] = "t.{$deliveryColumn} = COALESCE(NULLIF(t.{$deliveryColumn}, ''), {$expression})";
            $legacyEmptyChecks[] = "(t.{$deliveryColumn} IS NULL OR t.{$deliveryColumn} = '')";
        }
        if ($legacyAssignments) {
            $migrateSql = "UPDATE dyeing_batch_delivery t
                INNER JOIN knitting_delivery_challan h ON h.KDCID = t.KNITTING_CHALLAN_ID
                SET " . implode(', ', $legacyAssignments) . "
                WHERE " . implode(' OR ', $legacyEmptyChecks);
            if (!$db->query($migrateSql)) {
                respond(['success' => false, 'message' => 'Could not migrate old challan data: ' . $db->error], 500);
            }
        }
    }

    if (in_array('CHALLAN_NO', $deliveryColumns, true) &&
        !$db->query("ALTER TABLE dyeing_batch_delivery DROP COLUMN CHALLAN_NO")) {
        respond(['success' => false, 'message' => 'Could not remove CHALLAN_NO from delivery records: ' . $db->error], 500);
    }
    $legacyLinkIndex = $db->query("SHOW INDEX FROM dyeing_batch_delivery WHERE Key_name = 'idx_knitting_challan'");
    if (!$legacyLinkIndex) {
        respond(['success' => false, 'message' => 'Could not inspect the old challan index: ' . $db->error], 500);
    }
    if ($legacyLinkIndex->num_rows > 0 &&
        !$db->query("ALTER TABLE dyeing_batch_delivery DROP INDEX idx_knitting_challan")) {
        respond(['success' => false, 'message' => 'Could not remove the old challan index: ' . $db->error], 500);
    }
    if (in_array('KNITTING_CHALLAN_ID', $deliveryColumns, true) &&
        !$db->query("ALTER TABLE dyeing_batch_delivery DROP COLUMN KNITTING_CHALLAN_ID")) {
        respond(['success' => false, 'message' => 'Could not remove the old challan link: ' . $db->error], 500);
    }
    if ($legacyTableResult->num_rows > 0 && !$db->query("DROP TABLE knitting_delivery_challan")) {
        respond(['success' => false, 'message' => 'Could not remove the old Knitting challan table: ' . $db->error], 500);
    }
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize delivery records: ' . $error->getMessage()], 500);
}

function fetchDeliveryBatchRows($db, $cardNo, $forUpdate = false)
{
    $sql = "SELECT dbc.DBCTID AS BATCH_LINE_ID, dbc.PO_NUMBER, dbc.COLOR, dbc.RQTY AS QTY,
                   COALESCE(delivered.DELIVERED_QTY, 0) AS DELIVERED_QTY,
                   GREATEST(COALESCE(dbc.RQTY, 0) - COALESCE(delivered.DELIVERED_QTY, 0), 0) AS REMAINING_QTY,
                   dbc.DYMCNO, dbc.SONO, dbc.BUYER,
                   dbc.STYLE, dbc.MCDIA, dbc.CUSTOMER, dbc.O_T, dbc.SL,
                   dbc.FTYPE, dbc.FGSM, dbc.FDIA, dbc.YTYPE, dbc.YCOUNT,
                   dbc.FEEDER_PLAN, dbc.LOT_NO, dbc.YBRAND, dbc.CATEGORY, dbc.BCARD,
                   dbc.SILICON, dbc.NONSILICON, dbc.NWASH, dbc.EWASH, dbc.ENZYME,
                   dbc.NONENZYME, dbc.PEACH, dbc.BRUSH, dbc.AOP, dbc.SOFTENER,
                   dbc.NONSOFTENER, dbc.OTHERS
            FROM dyeing_batch_card dbc
            LEFT JOIN (
                SELECT BATCH_LINE_ID,
                       SUM(CAST(NULLIF(TRIM(QTY), '') AS DECIMAL(12,3))) AS DELIVERED_QTY
                FROM dyeing_batch_delivery
                WHERE BATCH_LINE_ID IS NOT NULL
                GROUP BY BATCH_LINE_ID
            ) delivered ON delivered.BATCH_LINE_ID = dbc.DBCTID
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
    $totalQty = 0;
    $totalRemainingQty = 0;
    while ($row = $result->fetch_assoc()) {
        if (!is_numeric($row['BATCH_LINE_ID']) || (int)$row['BATCH_LINE_ID'] < 1 ||
            !is_numeric($row['QTY']) || !is_finite((float)$row['QTY']) ||
            (float)$row['QTY'] <= 0 || trim((string)$row['FTYPE']) === '') {
            $stmt->close();
            throw new RuntimeException('This batch contains an invalid fabric line or quantity.');
        }
        $totalQty += (float)$row['QTY'];
        $totalRemainingQty += (float)$row['REMAINING_QTY'];
        $rows[] = $row;
    }
    $stmt->close();

    return [
        'rows' => $rows,
        'total_qty' => $totalQty,
        'remaining_qty' => $totalRemainingQty,
        'target_qty' => $totalQty
    ];
}

function fetchDeliveryStatus($db, $cardNo)
{
    $stmt = $db->prepare(
        "SELECT BUDAT, DOP, DID
         FROM dyeing_batch_delivery
         WHERE BCARD = ?
         ORDER BY DBDTID ASC"
    );
    if (!$stmt) {
        throw new RuntimeException('Database error: ' . $db->error);
    }
    $stmt->bind_param('s', $cardNo);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Database error: ' . $error);
    }

    $delivery = null;
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($delivery === null) {
            $delivery = $row;
        }
    }
    $stmt->close();

    return ['delivery' => $delivery];
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$input = json_decode(file_get_contents('php://input'), true);
$inTransaction = false;
$hasChallanSequenceLock = false;

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

        $deliveryStatus = fetchDeliveryStatus($db, $cardNo);

        respond([
            'success' => true,
            'card_no' => $cardNo,
            'rows' => $batch['rows'],
            'line_count' => count($batch['rows']),
            'total_qty' => round($batch['total_qty'], 3),
            'remaining_qty' => round($batch['remaining_qty'], 3),
            'target_qty' => round($batch['target_qty'], 3),
            'delivery' => $deliveryStatus['delivery']
        ]);
    }

    if ($action === 'load_batch_by_roll') {
        respond(['success' => false, 'message' => 'Roll-based delivery is no longer supported. Scan a Batch Card QR instead.'], 410);
    }

    if ($action === 'confirm_delivery') {
        $batchesInput = isset($input['batches']) && is_array($input['batches']) ? $input['batches'] : [];
        $toName = isset($input['to_name']) && is_scalar($input['to_name'])
            ? trim((string)$input['to_name'])
            : '';
        $gatePassNo = isset($input['gate_pass_no']) && is_scalar($input['gate_pass_no'])
            ? trim((string)$input['gate_pass_no'])
            : '';
        $vehicleNo = isset($input['vehicle_no']) && is_scalar($input['vehicle_no'])
            ? trim((string)$input['vehicle_no'])
            : '';
        if ($toName === '' || $gatePassNo === '' || $vehicleNo === '') {
            respond([
                'success' => false,
                'message' => 'To, Gate Pass No., and Vehicle No. are required.'
            ], 400);
        }
        if (!$batchesInput || count($batchesInput) > 50 ||
            strlen($toName) > 255 || strlen($gatePassNo) > 100 || strlen($vehicleNo) > 100) {
            respond(['success' => false, 'message' => 'Provide a valid delivery challan and at least one batch.'], 400);
        }

        $seenCards = [];
        $seenRolls = [];
        $remarksByCard = [];
        $rollCount = 0;
        foreach ($batchesInput as $batchInput) {
            if (!is_array($batchInput)) {
                respond(['success' => false, 'message' => 'Invalid batch data in the delivery challan.'], 400);
            }
            $cardNo = isset($batchInput['card_no']) && is_scalar($batchInput['card_no'])
                ? trim((string)$batchInput['card_no'])
                : '';
            $rolls = isset($batchInput['rolls']) && is_array($batchInput['rolls']) ? $batchInput['rolls'] : [];
            $loadedTotalQty = isset($batchInput['total_qty']) && is_numeric($batchInput['total_qty'])
                ? (float)$batchInput['total_qty']
                : -1;
            if ($cardNo === '' || isset($seenCards[$cardNo]) || !$rolls ||
                !is_finite($loadedTotalQty) || $loadedTotalQty < 0) {
                respond(['success' => false, 'message' => 'A batch is missing its card number, rolls, or quantity.'], 400);
            }
            $batchRemark = isset($batchInput['remarks']) && is_scalar($batchInput['remarks'])
                ? trim((string)$batchInput['remarks'])
                : '';
            if (strlen($batchRemark) > 255) {
                respond(['success' => false, 'message' => 'A batch remark must be 255 characters or fewer.'], 400);
            }
            if (count($rolls) > 500 || $rollCount + count($rolls) > 500) {
                respond(['success' => false, 'message' => 'This challan has too many rolls to deliver in one request.'], 400);
            }
            $seenCards[$cardNo] = true;
            $remarksByCard[$cardNo] = $batchRemark;
            foreach ($rolls as $roll) {
                $roll = is_scalar($roll) ? trim((string)$roll) : '';
                if ($roll === '' || isset($seenRolls[$cardNo][$roll])) {
                    respond(['success' => false, 'message' => 'A roll is missing or appears more than once in this challan.'], 400);
                }
                $seenRolls[$cardNo][$roll] = true;
                $rollCount++;
            }
        }
        $lockResult = $db->query("SELECT GET_LOCK('fabline_knitting_challan_sequence', 10) AS acquired");
        if (!$lockResult) {
            throw new RuntimeException('Could not reserve the next challan number: ' . $db->error);
        }
        $lockRow = $lockResult->fetch_assoc();
        $lockResult->free();
        if (!$lockRow || (int)$lockRow['acquired'] !== 1) {
            throw new DeliveryRequestException('Could not reserve a challan number. Please try again.', 503);
        }
        $hasChallanSequenceLock = true;

        $sequenceResult = $db->query(
            "SELECT MAX(CAST(SUBSTRING(KCHALLAN, 2) AS UNSIGNED)) AS last_number
             FROM dyeing_batch_delivery
             WHERE KCHALLAN REGEXP '^C[0-9]+$'"
        );
        if (!$sequenceResult) {
            throw new RuntimeException('Could not read the last challan number: ' . $db->error);
        }
        $sequenceRow = $sequenceResult->fetch_assoc();
        $sequenceResult->free();
        $nextChallanNumber = (int)($sequenceRow['last_number'] ?? 0) + 1;
        $kChallan = 'C' . str_pad((string)$nextChallanNumber, 9, '0', STR_PAD_LEFT);

        $db->begin_transaction();
        $inTransaction = true;
        $validatedBatches = [];
        $totalQty = 0;
        foreach ($batchesInput as $batchInput) {
            $cardNo = trim((string)$batchInput['card_no']);
            $scannedRolls = array_map(function ($roll) {
                return trim((string)$roll);
            }, $batchInput['rolls']);
            $loadedTotalQty = (float)$batchInput['total_qty'];
            $batch = fetchDeliveryBatchRows($db, $cardNo, true);
            if (!$batch['rows']) {
                throw new DeliveryRequestException('Batch Card ' . $cardNo . ' was not found.', 404);
            }
            if (abs($loadedTotalQty - $batch['target_qty']) > 0.0005) {
                throw new DeliveryRequestException(
                    'Batch ' . $cardNo . ' quantity changed after loading. Reload it before confirming.',
                    409
                );
            }

            $stmt = $db->prepare("SELECT ROLL FROM dyeing_batch_delivery WHERE BCARD = ? FOR UPDATE");
            if (!$stmt) {
                throw new RuntimeException('Database error: ' . $db->error);
            }
            $stmt->bind_param('s', $cardNo);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new RuntimeException('Database error: ' . $error);
            }
            $deliveredRolls = [];
            $deliveredResult = $stmt->get_result();
            while ($deliveredRow = $deliveredResult->fetch_assoc()) {
                $deliveredRolls[trim((string)$deliveredRow['ROLL'])] = true;
            }
            $stmt->close();

            foreach ($scannedRolls as $roll) {
                if (!isset($batch['rolls'][$roll])) {
                    throw new DeliveryRequestException(
                        'Roll ' . $roll . ' does not match Batch Card ' . $cardNo . '.',
                        422
                    );
                }
                if (isset($deliveredRolls[$roll])) {
                    throw new DeliveryRequestException(
                        'Roll ' . $roll . ' from Batch Card ' . $cardNo . ' has already been delivered.',
                        409
                    );
                }
            }
            $rowsByRoll = [];
            foreach ($batch['rows'] as $row) {
                $rowsByRoll[$row['ROLL']] = $row;
            }
            $batchQty = 0;
            foreach ($scannedRolls as $roll) {
                $batchQty += (float)$rowsByRoll[$roll]['QTY'];
            }
            $totalQty += $batchQty;
            $validatedBatches[] = [
                'card_no' => $cardNo,
                'rolls' => $scannedRolls,
                'rows_by_roll' => $rowsByRoll,
                'remarks' => $remarksByCard[$cardNo]
            ];
        }

        $dop = trim((string)($_SESSION['user_name'] ?? ''));
        if ($dop === '') {
            $dop = trim((string)($_SESSION['operator_name'] ?? ''));
        }
        if ($dop === '') {
            $dop = (string)$_SESSION['username'];
        }
        $did = (string)$_SESSION['username'];
        $deliveryDate = date('Y-m-d');
        $stmt = $db->prepare(
            "INSERT INTO dyeing_batch_delivery
             (BUDAT, KCHALLAN, TO_NAME, BCARD, ROLL, PO_NUMBER, QTY, SONO,
              BUYER, STYLE, COLOR, DYMCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA,
              FEEDER_PLAN, LOT_NO, YTYPE, YCOUNT, YBRAND, CATEGORY, GATE_PASS_NO, VEHICLE_NO,
              SILICON, NONSILICON, NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP,
              SOFTENER, NONSOFTENER, OTHERS, DELIVERY_REMARKS, DOP, DID)
             VALUES (" . implode(', ', array_fill(0, 42, '?')) . ")"
        );
        if (!$stmt) {
            throw new RuntimeException('Database error: ' . $db->error);
        }
        foreach ($validatedBatches as $validatedBatch) {
            foreach ($validatedBatch['rolls'] as $roll) {
                $row = $validatedBatch['rows_by_roll'][$roll];
                $processValues = [];
                foreach (dyeingProcessFieldNames() as $processField) {
                    $processValues[$processField] = isset($row[$processField]) ? $row[$processField] : '';
                }
                $quantity = formatDeliveryQuantity($row['QTY']);
                $deliveryValues = [
                    $deliveryDate,
                    $kChallan,
                    $toName,
                    $validatedBatch['card_no'],
                    $row['ROLL'],
                    $row['PO_NUMBER'],
                    $quantity,
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
                    $gatePassNo,
                    $vehicleNo,
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
                    $processValues['OTHERS'],
                    $validatedBatch['remarks'],
                    $dop,
                    $did
                ];
                $bindValues = [str_repeat('s', count($deliveryValues))];
                foreach ($deliveryValues as &$deliveryValue) {
                    $bindValues[] = &$deliveryValue;
                }
                $bound = call_user_func_array([$stmt, 'bind_param'], $bindValues);
                unset($deliveryValue, $bindValues);
                if (!$bound) {
                    throw new RuntimeException('Could not bind delivery values: ' . $stmt->error);
                }
                if (!$stmt->execute()) {
                    $error = $stmt->error;
                    $stmt->close();
                    throw new RuntimeException('Could not save delivery for roll ' . $roll . ': ' . $error);
                }
            }
        }
        $stmt->close();
        if (!$db->commit()) {
            throw new RuntimeException('Could not commit the delivery confirmation: ' . $db->error);
        }
        $inTransaction = false;
        if (!$db->query("SELECT RELEASE_LOCK('fabline_knitting_challan_sequence')")) {
            error_log('Could not release the Knitting challan sequence lock: ' . $db->error);
        } else {
            $hasChallanSequenceLock = false;
        }

        respond([
            'success' => true,
            'message' => $kChallan . ' delivery confirmed.',
            'challan_no' => $kChallan,
            'kchallan' => $kChallan,
            'delivery_date' => $deliveryDate,
            'to_name' => $toName,
            'gate_pass_no' => $gatePassNo,
            'vehicle_no' => $vehicleNo,
            'dop' => $dop,
            'roll_count' => $rollCount,
            'batch_count' => count($validatedBatches),
            'total_qty' => round($totalQty, 3)
        ]);
    }

    respond(['success' => false, 'message' => 'Unknown action.'], 400);
} catch (DeliveryRequestException $error) {
    if ($inTransaction) {
        $db->rollback();
    }
    if ($hasChallanSequenceLock) {
        $db->query("SELECT RELEASE_LOCK('fabline_knitting_challan_sequence')");
    }
    respond(['success' => false, 'message' => $error->getMessage()], $error->statusCode);
} catch (Throwable $error) {
    if ($inTransaction) {
        $db->rollback();
    }
    if ($hasChallanSequenceLock) {
        $db->query("SELECT RELEASE_LOCK('fabline_knitting_challan_sequence')");
    }
    respond(['success' => false, 'message' => $error->getMessage()], 500);
}
