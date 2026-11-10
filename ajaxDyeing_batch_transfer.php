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
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize Batch Transfer process fields: ' . $error->getMessage()], 500);
}

$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';

if ($action === 'machines') {
    $result = $db->query("SELECT DMCNO, DCAPACITY FROM dyeing_mcno ORDER BY DMCNO");
    if (!$result) {
        respond(['success' => false, 'message' => 'Could not load dyeing machines: ' . $db->error], 500);
    }
    $machines = [];
    while ($machine = $result->fetch_assoc()) {
        $machines[] = $machine;
    }
    $result->free();
    respond(['success' => true, 'machines' => $machines]);
}

if ($action === 'search') {
    $batch = isset($_GET['batch']) ? trim((string) $_GET['batch']) : '';
    $poNumber = isset($_GET['po_number']) ? trim((string) $_GET['po_number']) : '';
    $budat = isset($_GET['budat']) ? trim((string) $_GET['budat']) : '';
    if ($budat !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $budat)) {
            respond(['success' => false, 'message' => 'Select a valid date.'], 400);
        }
        $dateParts = explode('-', $budat);
        if (!checkdate((int) $dateParts[1], (int) $dateParts[2], (int) $dateParts[0])) {
            respond(['success' => false, 'message' => 'Select a valid date.'], 400);
        }
        if ($budat > date('Y-m-d')) {
            respond(['success' => false, 'message' => 'Future dates cannot be searched.'], 400);
        }
    }

    $sql = "SELECT DBDTID, BCARD, ROLL, PO_NUMBER, COLOR, QTY, DYMCNO,
                   SONO, BUYER, STYLE, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA,
                   YTYPE, YCOUNT, YBRAND, SILICON, NONSILICON, NWASH, EWASH, ENZYME,
                   NONENZYME, PEACH, BRUSH, AOP, SOFTENER, NONSOFTENER, OTHERS
            FROM dyeing_batch_delivery
            WHERE (? = '' OR BCARD LIKE CONCAT('%', ?, '%'))
              AND (? = '' OR PO_NUMBER LIKE CONCAT('%', ?, '%'))
              AND (? = '' OR BUDAT = ?)
            ORDER BY BCARD ASC, DBDTID ASC";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        respond(['success' => false, 'message' => 'Could not prepare Batch Transfer search: ' . $db->error], 500);
    }
    $stmt->bind_param('ssssss', $batch, $batch, $poNumber, $poNumber, $budat, $budat);
    if (!$stmt->execute()) {
        $message = $stmt->error;
        $stmt->close();
        respond(['success' => false, 'message' => 'Could not search delivery rolls: ' . $message], 500);
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    respond(['success' => true, 'rows' => $rows]);
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        respond(['success' => false, 'message' => 'Invalid transfer request.'], 400);
    }
    $csrfToken = isset($input['csrf_token']) ? (string) $input['csrf_token'] : '';
    if (
        empty($_SESSION['batch_transfer_csrf']) ||
        !hash_equals((string) $_SESSION['batch_transfer_csrf'], $csrfToken)
    ) {
        respond(['success' => false, 'message' => 'Your session token is invalid. Refresh the page and try again.'], 403);
    }

    $changes = isset($input['changes']) && is_array($input['changes']) ? $input['changes'] : [];
    if (!$changes || count($changes) > 1000) {
        respond(['success' => false, 'message' => 'Select between 1 and 1000 roll machine changes.'], 400);
    }

    $normalizedChanges = [];
    $seenBatches = [];
    foreach ($changes as $change) {
        if (!is_array($change) || !isset($change['batch'], $change['mcno'])) {
            respond(['success' => false, 'message' => 'A batch machine change is incomplete.'], 400);
        }
        $batch = trim((string) $change['batch']);
        $mcno = trim((string) $change['mcno']);
        if ($batch === '' || strlen($batch) > 30 || $mcno === '') {
            respond(['success' => false, 'message' => 'Each selected batch and machine must be valid.'], 400);
        }
        if (isset($seenBatches[$batch])) {
            respond(['success' => false, 'message' => 'A batch was included more than once.'], 400);
        }
        $seenBatches[$batch] = true;
        $normalizedChanges[] = ['batch' => $batch, 'mcno' => $mcno];
    }

    $machineStmt = $db->prepare('SELECT DMCNO FROM dyeing_mcno WHERE DMCNO = ? LIMIT 1');
    if (!$machineStmt) {
        respond(['success' => false, 'message' => 'Could not validate dyeing machines: ' . $db->error], 500);
    }
    foreach ($normalizedChanges as $change) {
        $mcno = $change['mcno'];
        $machineStmt->bind_param('s', $mcno);
        if (!$machineStmt->execute()) {
            $message = $machineStmt->error;
            $machineStmt->close();
            respond(['success' => false, 'message' => 'Could not validate selected machine: ' . $message], 500);
        }
        $machineResult = $machineStmt->get_result();
        $machineFound = $machineResult->fetch_assoc();
        $machineResult->free();
        if (!$machineFound) {
            $machineStmt->close();
            respond(['success' => false, 'message' => 'A selected machine is no longer available. Reload the page and try again.'], 400);
        }
    }
    $machineStmt->close();

    if (!$db->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start the transfer transaction: ' . $db->error], 500);
    }
    $updateStmt = $db->prepare('UPDATE dyeing_batch_delivery SET DYMCNO = ? WHERE BCARD = ?');
    $countStmt = $db->prepare(
        'SELECT COUNT(*) AS roll_count, SUM(DYMCNO = ?) AS matching_count
         FROM dyeing_batch_delivery WHERE BCARD = ?'
    );
    if (!$updateStmt || !$countStmt) {
        $error = $db->error;
        if ($updateStmt) {
            $updateStmt->close();
        }
        if ($countStmt) {
            $countStmt->close();
        }
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not prepare the transfer update: ' . $error], 500);
    }

    $updated = [];
    foreach ($normalizedChanges as $change) {
        $batch = $change['batch'];
        $mcno = $change['mcno'];
        $countStmt->bind_param('ss', $mcno, $batch);
        if (!$countStmt->execute()) {
            $error = $countStmt->error;
            $updateStmt->close();
            $countStmt->close();
            $db->rollback();
            respond(['success' => false, 'message' => 'Could not verify rolls for batch ' . $batch . ': ' . $error], 500);
        }
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $countResult->free();
        $rollCount = (int) $countRow['roll_count'];
        $matchingCount = (int) $countRow['matching_count'];
        if ($rollCount === 0) {
            $updateStmt->close();
            $countStmt->close();
            $db->rollback();
            respond(['success' => false, 'message' => 'Batch ' . $batch . ' was not found. No changes were saved.'], 404);
        }
        if ($matchingCount === $rollCount) {
            $updateStmt->close();
            $countStmt->close();
            $db->rollback();
            respond(['success' => false, 'message' => 'Batch ' . $batch . ' already uses MCNO ' . $mcno . '. No change was made.'], 409);
        }
        if (!$updateStmt->bind_param('ss', $mcno, $batch) || !$updateStmt->execute()) {
            $error = $updateStmt->error;
            $updateStmt->close();
            $countStmt->close();
            $db->rollback();
            respond(['success' => false, 'message' => 'Could not update machine assignment for batch ' . $batch . ': ' . $error], 500);
        }
        $updated[] = ['batch' => $batch, 'mcno' => $mcno, 'roll_count' => $rollCount];
    }

    $updateStmt->close();
    $countStmt->close();
    if (!$db->commit()) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not commit the transfer changes: ' . $db->error], 500);
    }
    respond([
        'success' => true,
        'message' => array_sum(array_column($updated, 'roll_count')) . ' roll(s) transferred successfully across ' . count($updated) . ' batch(es).',
        'updated' => $updated
    ]);
}

respond(['success' => false, 'message' => 'Unsupported Batch Transfer request.'], 400);
