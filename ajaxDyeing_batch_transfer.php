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
    ensureDyeingProcessColumns($db, 'dyeing_load');
    ensureDyeingCountColumn($db, 'dyeing_load');
    ensureDyeingCountColumn($db, 'dyeing_unload');
} catch (Throwable $error) {
    respond(['success' => false, 'message' => 'Could not initialize Batch Transfer fields: ' . $error->getMessage()], 500);
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

    $sql = "SELECT DLTID, BCARD, DCOUNT, ROLL, PO_NUMBER, COLOR, QTY, MCNO,
                   SONO, BUYER, STYLE, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA,
                   YTYPE, YCOUNT, YBRAND, SILICON, NONSILICON, NWASH, EWASH, ENZYME,
                   NONENZYME, PEACH, BRUSH, AOP, SOFTENER, NONSOFTENER, OTHERS
            FROM dyeing_load
            WHERE NOT EXISTS (
                  SELECT 1
                  FROM dyeing_unload AS unloaded
                  WHERE TRIM(unloaded.BCARD) = TRIM(dyeing_load.BCARD)
                    AND unloaded.DCOUNT = dyeing_load.DCOUNT
              )
              AND (? = '' OR BCARD LIKE CONCAT('%', ?, '%'))
              AND (? = '' OR PO_NUMBER LIKE CONCAT('%', ?, '%'))
              AND (? = '' OR BUDAT = ?)
            ORDER BY BCARD ASC, DLTID ASC";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        respond(['success' => false, 'message' => 'Could not prepare Batch Transfer search: ' . $db->error], 500);
    }
    $stmt->bind_param('ssssss', $batch, $batch, $poNumber, $poNumber, $budat, $budat);
    if (!$stmt->execute()) {
        $message = $stmt->error;
        $stmt->close();
        respond(['success' => false, 'message' => 'Could not search loaded rolls: ' . $message], 500);
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

    $rollId = isset($input['roll_id']) ? filter_var($input['roll_id'], FILTER_VALIDATE_INT) : false;
    $mcno = isset($input['mcno']) ? trim((string) $input['mcno']) : '';
    if ($rollId === false || $rollId < 1 || $mcno === '' || strlen($mcno) > 100) {
        respond(['success' => false, 'message' => 'Select a valid roll and machine.'], 400);
    }

    $machineStmt = $db->prepare('SELECT DMCNO FROM dyeing_mcno WHERE DMCNO = ? LIMIT 1');
    if (!$machineStmt) {
        respond(['success' => false, 'message' => 'Could not validate dyeing machines: ' . $db->error], 500);
    }
    $machineStmt->bind_param('s', $mcno);
    if (!$machineStmt->execute()) {
        $message = $machineStmt->error;
        $machineStmt->close();
        respond(['success' => false, 'message' => 'Could not validate selected machine: ' . $message], 500);
    }
    $machineResult = $machineStmt->get_result();
    $machineFound = $machineResult->fetch_assoc();
    $machineResult->free();
    $machineStmt->close();
    if (!$machineFound) {
        respond(['success' => false, 'message' => 'The selected machine is no longer available. Reload the page and try again.'], 400);
    }

    if (!$db->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start the transfer transaction: ' . $db->error], 500);
    }
    $currentStmt = $db->prepare(
        'SELECT loaded.MCNO
         FROM dyeing_load loaded
         WHERE loaded.DLTID = ?
           AND NOT EXISTS (
               SELECT 1
               FROM dyeing_unload unloaded
               WHERE TRIM(unloaded.BCARD) = TRIM(loaded.BCARD)
                 AND unloaded.DCOUNT = loaded.DCOUNT
           )
         FOR UPDATE'
    );
    $updateStmt = $db->prepare('UPDATE dyeing_load SET MCNO = ? WHERE DLTID = ?');
    if (!$currentStmt || !$updateStmt) {
        $error = $db->error;
        if ($currentStmt) {
            $currentStmt->close();
        }
        if ($updateStmt) {
            $updateStmt->close();
        }
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not prepare the transfer update: ' . $error], 500);
    }

    $currentStmt->bind_param('i', $rollId);
    if (!$currentStmt->execute()) {
        $error = $currentStmt->error;
        $currentStmt->close();
        $updateStmt->close();
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not verify the selected roll: ' . $error], 500);
    }
    $currentResult = $currentStmt->get_result();
    $currentRow = $currentResult->fetch_assoc();
    $currentResult->free();
    $currentStmt->close();
    if (!$currentRow) {
        $updateStmt->close();
        $db->rollback();
        respond(['success' => false, 'message' => 'The selected loaded roll was not found. No change was saved.'], 404);
    }
    if ((string) $currentRow['MCNO'] === $mcno) {
        $updateStmt->close();
        $db->rollback();
        respond(['success' => false, 'message' => 'This roll already uses MCNO ' . $mcno . '. No change was made.'], 409);
    }
    if (!$updateStmt->bind_param('si', $mcno, $rollId) || !$updateStmt->execute()) {
        $error = $updateStmt->error;
        $updateStmt->close();
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not update machine assignment for the selected roll: ' . $error], 500);
    }
    $updateStmt->close();
    if (!$db->commit()) {
        $db->rollback();
        respond(['success' => false, 'message' => 'Could not commit the transfer changes: ' . $db->error], 500);
    }
    respond([
        'success' => true,
        'message' => 'Machine assignment updated for one roll.',
        'updated' => ['roll_id' => $rollId, 'mcno' => $mcno]
    ]);
}

respond(['success' => false, 'message' => 'Unsupported Batch Transfer request.'], 400);
