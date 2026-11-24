<?php
// ajaxKnitting_rack_transfer.php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once 'config.php';

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$action = trim($_REQUEST['action'] ?? '');

try {
    ensureKnittingRackTransferTable($db);
} catch (Throwable $e) {
    error_log('Store Rack Transfer schema setup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to prepare Store Rack Transfer logging.']);
    exit();
}

// -------------------------------------------------------------
// 1. GET INVENTORY & STATS
// -------------------------------------------------------------
if ($action === 'get_inventory') {
    $search = trim($_GET['search'] ?? '');
    $rackFilter = trim($_GET['rack_filter'] ?? '');

    $where = [];
    $params = [];
    $types = '';

    if ($search !== '') {
        $escaped = '%' . $search . '%';
        $where[] = "(BUDAT LIKE ? OR ROLL LIKE ? OR PO_NUMBER LIKE ? OR SONO LIKE ? OR BUYER LIKE ? OR STYLE LIKE ? OR COLOR LIKE ? OR MCNO LIKE ? OR LOT_NO LIKE ? OR FTYPE LIKE ? OR CONCAT(COALESCE(RACKNO, ''), ' ', COALESCE(RACKLOCATION, '')) LIKE ?)";
        for ($i = 0; $i < 11; $i++) {
            $params[] = $escaped;
            $types .= 's';
        }
    }

    if ($rackFilter !== '' && $rackFilter !== 'ALL') {
        $where[] = "RACKNO = ?";
        $params[] = str_pad($rackFilter, 2, '0', STR_PAD_LEFT);
        $types .= 's';
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT KSTID, BUDAT, RACKNO, RACKLOCATION, ROLL, PO_NUMBER, 
                   CAST(QTY AS DECIMAL(10,2)) AS QTY, SONO, SHIFT, BUYER, STYLE, 
                   COLOR, MCNO, MCDIA, FTYPE, FGSM, FDIA, LOT_NO, CREATED_DATE, UNAME 
            FROM knitting_store 
            $whereSql 
            ORDER BY KSTID DESC";

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $rolls = [];
    while ($row = $res->fetch_assoc()) {
        $row['FULL_RACK'] = ($row['RACKNO'] ? str_pad($row['RACKNO'], 2, '0', STR_PAD_LEFT) : '') . ($row['RACKLOCATION'] ?? '');
        $rolls[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'count' => count($rolls),
        'data' => $rolls
    ]);
    exit();
}

// -------------------------------------------------------------
// 2. GET SINGLE ROLL DETAILS & TRANSFER HISTORY
// -------------------------------------------------------------
if ($action === 'get_roll_info') {
    $roll = trim($_GET['roll'] ?? '');
    if ($roll === '') {
        echo json_encode(['success' => false, 'message' => 'Roll number is required.']);
        exit();
    }

    $stmt = $db->prepare("SELECT * FROM knitting_store WHERE TRIM(ROLL) = ? LIMIT 1");
    $stmt->bind_param('s', $roll);
    $stmt->execute();
    $res = $stmt->get_result();
    $rollData = $res->fetch_assoc();
    $stmt->close();

    if (!$rollData) {
        echo json_encode(['success' => false, 'message' => "Roll '$roll' was not found in the knitting store."]);
        exit();
    }

    $rollData['RACKNO_PAD'] = $rollData['RACKNO'] ? str_pad($rollData['RACKNO'], 2, '0', STR_PAD_LEFT) : '';
    $rollData['FULL_RACK'] = $rollData['RACKNO_PAD'] . ($rollData['RACKLOCATION'] ?? '');

    // Fetch previous transfer logs for this store record
    $historyStmt = $db->prepare("
        SELECT KRTID AS id, BUDAT, KSTID, ROLLNO AS roll,
               FRACK AS from_rack, TORACK AS to_rack
        FROM knitting_rack_transfer
        WHERE KSTID = ?
        ORDER BY KRTID DESC
        LIMIT 10
    ");
    $historyStmt->bind_param('i', $rollData['KSTID']);
    $historyStmt->execute();
    $histRes = $historyStmt->get_result();
    $history = [];
    while ($h = $histRes->fetch_assoc()) {
        $history[] = $h;
    }
    $historyStmt->close();

    echo json_encode([
        'success' => true,
        'data' => $rollData,
        'history' => $history
    ]);
    exit();
}

// -------------------------------------------------------------
// HELPER: GET RACK OCCUPANCY & FIXED SHELF AVAILABILITY
// -------------------------------------------------------------
function getRackOccupancy($db, $rackNoPad, $excludeRoll = '') {
    $fixedShelves = ['A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3'];
    
    $sql = "SELECT TRIM(ROLL) AS ROLL, UPPER(TRIM(RACKLOCATION)) AS LOC, BUYER, STYLE, QTY, PO_NUMBER 
            FROM knitting_store 
            WHERE LPAD(TRIM(RACKNO), 2, '0') = ? AND RACKLOCATION IS NOT NULL AND TRIM(RACKLOCATION) != ''";
    if ($excludeRoll !== '') {
        $sql .= " AND TRIM(ROLL) != ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ss', $rackNoPad, $excludeRoll);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $rackNoPad);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $occupied = [];
    while ($row = $res->fetch_assoc()) {
        if (in_array($row['LOC'], $fixedShelves, true)) {
            $occupied[$row['LOC']] = $row;
        }
    }
    $stmt->close();

    $available = [];
    $slotList = [];
    foreach ($fixedShelves as $sh) {
        if (isset($occupied[$sh])) {
            $slotList[] = [
                'shelf' => $sh,
                'is_occupied' => true,
                'roll' => $occupied[$sh]['ROLL'],
                'buyer' => $occupied[$sh]['BUYER'],
                'style' => $occupied[$sh]['STYLE'],
                'qty' => $occupied[$sh]['QTY'],
                'po' => $occupied[$sh]['PO_NUMBER']
            ];
        } else {
            $available[] = $sh;
            $slotList[] = [
                'shelf' => $sh,
                'is_occupied' => false,
                'roll' => null
            ];
        }
    }

    return [
        'rack_no' => $rackNoPad,
        'total_slots' => count($fixedShelves),
        'occupied_count' => count($occupied),
        'available_count' => count($available),
        'available_shelves' => $available,
        'first_available' => count($available) > 0 ? $available[0] : null,
        'is_full' => count($available) === 0,
        'slots' => $slotList
    ];
}

// -------------------------------------------------------------
// 2b. GET RACK SHELF AVAILABILITY API
// -------------------------------------------------------------
if ($action === 'get_rack_availability') {
    $rackNo = trim($_GET['rack_no'] ?? '');
    $roll = trim($_GET['roll'] ?? '');

    if ($rackNo === '' || !is_numeric($rackNo)) {
        echo json_encode(['success' => false, 'message' => 'Valid rack number is required.']);
        exit();
    }

    $rackNoPad = str_pad($rackNo, 2, '0', STR_PAD_LEFT);
    $occupancy = getRackOccupancy($db, $rackNoPad, $roll);

    echo json_encode(array_merge(['success' => true], $occupancy));
    exit();
}

// -------------------------------------------------------------
// 2c. GET FIXED RACK LIST WITH STORE OCCUPANCY
// -------------------------------------------------------------
if ($action === 'get_racks') {
    $storedByRack = [];
    $res = $db->query("
        SELECT TRIM(RACKNO) AS rack_no,
               COUNT(DISTINCT KSTID) AS rolls_stored,
               COUNT(DISTINCT CASE
                   WHEN UPPER(TRIM(RACKLOCATION)) IN
                       ('A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3')
                   THEN UPPER(TRIM(RACKLOCATION))
               END) AS occupied_shelves
        FROM knitting_store
        WHERE RACKNO IS NOT NULL AND TRIM(RACKNO) <> ''
        GROUP BY TRIM(RACKNO)
    ");
    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'Unable to load rack occupancy.']);
        exit();
    }

    while ($row = $res->fetch_assoc()) {
        $pad = str_pad($row['rack_no'], 2, '0', STR_PAD_LEFT);
        if (!isset($storedByRack[$pad])) {
            $storedByRack[$pad] = ['rolls_stored' => 0, 'occupied_shelves' => 0];
        }
        $storedByRack[$pad]['rolls_stored'] += (int)$row['rolls_stored'];
        $storedByRack[$pad]['occupied_shelves'] += (int)$row['occupied_shelves'];
    }

    $racks = [];
    for ($r = 1; $r <= 50; $r++) {
        $pad = str_pad($r, 2, '0', STR_PAD_LEFT);
        $rackStats = $storedByRack[$pad] ?? ['rolls_stored' => 0, 'occupied_shelves' => 0];
        $racks[] = [
            'rack_no' => $pad,
            'rack_label' => "Rack $pad",
            'total_shelves' => 9,
            'rolls_stored' => $rackStats['rolls_stored'],
            'available_shelves' => max(0, 9 - $rackStats['occupied_shelves'])
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($racks),
        'data' => $racks
    ]);
    exit();
}

// -------------------------------------------------------------
// 2d. GET FIXED SHELVES FOR A SPECIFIC RACK
// -------------------------------------------------------------
if ($action === 'get_shelves') {
    $rackNo = trim($_GET['rack_no'] ?? '');
    $roll = trim($_GET['roll'] ?? '');

    if ($rackNo === '' || !is_numeric($rackNo)) {
        echo json_encode(['success' => false, 'message' => 'Valid rack number is required.']);
        exit();
    }

    $rackNoPad = str_pad($rackNo, 2, '0', STR_PAD_LEFT);

    $shelfCodes = ['A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3'];
    $sql = "SELECT UPPER(TRIM(RACKLOCATION)) AS shelf, ROLL AS roll, BUYER, STYLE, QTY, PO_NUMBER
            FROM knitting_store
            WHERE LPAD(TRIM(RACKNO), 2, '0') = ?
              AND RACKLOCATION IS NOT NULL
              AND TRIM(RACKLOCATION) <> ''";
    if ($roll !== '') {
        $sql .= " AND TRIM(ROLL) <> ?";
    }
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Unable to load rack shelves.']);
        exit();
    }
    if ($roll !== '') {
        $stmt->bind_param('ss', $rackNoPad, $roll);
    } else {
        $stmt->bind_param('s', $rackNoPad);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $occupiedShelves = [];
    while ($row = $res->fetch_assoc()) {
        if (in_array($row['shelf'], $shelfCodes, true)) {
            $occupiedShelves[$row['shelf']] = $row;
        }
    }
    $stmt->close();

    $shelves = [];
    $occupiedCount = 0;
    foreach ($shelfCodes as $shelf) {
        $row = $occupiedShelves[$shelf] ?? [];
        $isOcc = !empty($row['roll']);
        if ($isOcc) $occupiedCount++;

        $shelves[] = [
            'shelf' => $shelf,
            'section' => 'Section ' . substr($shelf, 0, 1),
            'label' => "Shelf " . $shelf,
            'is_occupied' => $isOcc,
            'roll' => $row['roll'] ?? null,
            'buyer' => $row['BUYER'] ?? null,
            'style' => $row['STYLE'] ?? null,
            'qty' => isset($row['QTY']) ? floatval($row['QTY']) : null,
            'po' => $row['PO_NUMBER'] ?? null
        ];
    }

    echo json_encode([
        'success' => true,
        'rack_no' => $rackNoPad,
        'total_shelves' => count($shelves),
        'occupied_count' => $occupiedCount,
        'available_count' => count($shelves) - $occupiedCount,
        'data' => $shelves
    ]);
    exit();
}

// -------------------------------------------------------------
// 3. EXECUTE Store Rack Transfer (USER-SELECTED RACK & SHELF)
// -------------------------------------------------------------
if ($action === 'transfer_rack') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST request expected.']);
        exit();
    }

    $roll = trim($_POST['roll'] ?? '');
    $toRackNo = trim($_POST['to_rackno'] ?? '');
    $toRackLocation = strtoupper(trim($_POST['to_racklocation'] ?? ''));

    if ($roll === '') {
        echo json_encode(['success' => false, 'message' => 'Roll number is required.']);
        exit();
    }

    if ($toRackNo === '' || !is_numeric($toRackNo) || intval($toRackNo) < 1 || intval($toRackNo) > 99) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid destination Rack Number (1-99).']);
        exit();
    }

    if ($toRackLocation === '') {
        echo json_encode(['success' => false, 'message' => 'Please select a specific Shelf/Location (e.g., A1, B2) for this roll.']);
        exit();
    }

    if (!preg_match('/^[A-Z][0-9]{1,2}$/', $toRackLocation)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Shelf/Location format. Please select a valid shelf (e.g., A1, B2, C3).']);
        exit();
    }

    $toRackNoPad = str_pad($toRackNo, 2, '0', STR_PAD_LEFT);

    // Check existing roll in knitting_store
    $chkStmt = $db->prepare("SELECT KSTID, ROLL, BUDAT, RACKNO, RACKLOCATION, QTY, PO_NUMBER, BUYER, STYLE, COLOR, FTYPE FROM knitting_store WHERE TRIM(ROLL) = ? LIMIT 1");
    $chkStmt->bind_param('s', $roll);
    $chkStmt->execute();
    $res = $chkStmt->get_result();
    $curr = $res->fetch_assoc();
    $chkStmt->close();

    if (!$curr) {
        echo json_encode(['success' => false, 'message' => "Roll '$roll' is not currently in the knitting store."]);
        exit();
    }

    $currRackNoPad = $curr['RACKNO'] ? str_pad($curr['RACKNO'], 2, '0', STR_PAD_LEFT) : '';
    $currLoc = strtoupper(trim($curr['RACKLOCATION'] ?? ''));
    $currFullRack = $currRackNoPad . $currLoc;
    $newFullRack = $toRackNoPad . $toRackLocation;

    if ($currRackNoPad === $toRackNoPad && $currLoc === $toRackLocation) {
        echo json_encode([
            'success' => false, 
            'message' => "Roll '$roll' is already located at Rack $toRackNoPad (Shelf $toRackLocation)! Please select a different shelf."
        ]);
        exit();
    }

    // Begin Transaction
    $db->begin_transaction();
    try {
        // 1. Update knitting_store with explicit user-chosen Rack and Shelf
        $updateStmt = $db->prepare("UPDATE knitting_store SET RACKNO = ?, RACKLOCATION = ? WHERE KSTID = ?");
        $updateStmt->bind_param('ssi', $toRackNoPad, $toRackLocation, $curr['KSTID']);
        if (!$updateStmt->execute()) {
            throw new Exception("Store update failed: " . $updateStmt->error);
        }
        $updateStmt->close();

        // 2. Insert into the KSTID-linked Store Rack Transfer table
        $fromRackRecord = $currFullRack ?: ($currRackNoPad ?: 'NONE');
        $logStmt = $db->prepare("
            INSERT INTO knitting_rack_transfer (BUDAT, KSTID, ROLLNO, FRACK, TORACK, TBUDATE)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $logStmt->bind_param(
            'sisss',
            $curr['BUDAT'],
            $curr['KSTID'],
            $curr['ROLL'],
            $fromRackRecord,
            $newFullRack
        );
        if (!$logStmt->execute()) {
            throw new Exception("Audit log insert failed: " . $logStmt->error);
        }
        $logStmt->close();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Roll '$roll' successfully moved to Rack $toRackNoPad (Shelf $toRackLocation)!",
            'roll' => $roll,
            'from_rack' => $fromRackRecord,
            'to_rack' => $newFullRack,
            'to_rackno' => $toRackNoPad,
            'to_racklocation' => $toRackLocation
        ]);
        exit();

    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// -------------------------------------------------------------
// 3b. EXECUTE BATCH Store Rack Transfer (USER-SELECTED RACK & SHELF)
// -------------------------------------------------------------
if ($action === 'batch_transfer') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST request expected.']);
        exit();
    }

    $rawRolls = $_POST['rolls'] ?? [];
    if (is_string($rawRolls)) {
        $rawRolls = json_decode($rawRolls, true) ?: explode(',', $rawRolls);
    }
    $rolls = array_values(array_filter(array_map('trim', (array)$rawRolls)));

    $toRackNo = trim($_POST['to_rackno'] ?? '');
    $toRackLocation = strtoupper(trim($_POST['to_racklocation'] ?? ''));

    if (empty($rolls)) {
        echo json_encode(['success' => false, 'message' => 'No rolls selected for transfer.']);
        exit();
    }

    if ($toRackNo === '' || !is_numeric($toRackNo) || intval($toRackNo) < 1 || intval($toRackNo) > 99) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid destination Rack Number (1-99).']);
        exit();
    }

    if ($toRackLocation === '') {
        echo json_encode(['success' => false, 'message' => 'Please select a destination Shelf/Location (e.g., A1, B2) for the batch transfer.']);
        exit();
    }

    if (!preg_match('/^[A-Z][0-9]{1,2}$/', $toRackLocation)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Shelf/Location format. Please select a valid shelf (e.g., A1, B2, C3).']);
        exit();
    }

    $toRackNoPad = str_pad($toRackNo, 2, '0', STR_PAD_LEFT);
    $toRecord = $toRackNoPad . $toRackLocation;

    $db->begin_transaction();
    try {
        $transferredCount = 0;
        $allocations = [];

        foreach ($rolls as $roll) {
            $chkStmt = $db->prepare("SELECT KSTID, ROLL, BUDAT, RACKNO, RACKLOCATION FROM knitting_store WHERE TRIM(ROLL) = ? LIMIT 1");
            $chkStmt->bind_param('s', $roll);
            $chkStmt->execute();
            $curr = $chkStmt->get_result()->fetch_assoc();
            $chkStmt->close();

            if (!$curr) continue;

            $currRackNoPad = $curr['RACKNO'] ? str_pad($curr['RACKNO'], 2, '0', STR_PAD_LEFT) : '';
            $currLoc = strtoupper(trim($curr['RACKLOCATION'] ?? ''));
            $fromRecord = ($currRackNoPad . $currLoc) ?: 'NONE';

            // Update knitting_store with explicit user-chosen Rack and Shelf
            $uStmt = $db->prepare("UPDATE knitting_store SET RACKNO = ?, RACKLOCATION = ? WHERE KSTID = ?");
            $uStmt->bind_param('ssi', $toRackNoPad, $toRackLocation, $curr['KSTID']);
            if (!$uStmt->execute()) {
                throw new Exception("Store update failed for roll '$roll': " . $uStmt->error);
            }
            $uStmt->close();

            // Insert the transfer record linked to the store row
            $lStmt = $db->prepare("
                INSERT INTO knitting_rack_transfer (BUDAT, KSTID, ROLLNO, FRACK, TORACK, TBUDATE)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $lStmt->bind_param(
                'sisss',
                $curr['BUDAT'],
                $curr['KSTID'],
                $curr['ROLL'],
                $fromRecord,
                $toRecord
            );
            if (!$lStmt->execute()) {
                throw new Exception("Transfer log insert failed for roll '$roll': " . $lStmt->error);
            }
            $lStmt->close();

            $transferredCount++;
            $allocations[] = "$roll → $toRecord";
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => "Successfully transferred $transferredCount rolls to Rack $toRackNoPad (Shelf $toRackLocation)!",
            'count' => $transferredCount,
            'to_rackno' => $toRackNoPad,
            'to_racklocation' => $toRackLocation,
            'allocations' => $allocations
        ]);
        exit();
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// -------------------------------------------------------------
// 4. GET TRANSFER AUDIT LOGS
// -------------------------------------------------------------
if ($action === 'get_logs') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    if ($limit <= 0 || $limit > 200) $limit = 50;

    $sql = "SELECT l.KRTID AS id, l.KSTID, l.BUDAT, l.TBUDATE,
                   s.ROLL AS roll,
                   l.FRACK AS from_rack, l.TORACK AS to_rack,
                   s.BUYER, s.STYLE, s.COLOR, CAST(s.QTY AS DECIMAL(10,2)) AS QTY, s.PO_NUMBER
            FROM knitting_rack_transfer l
            INNER JOIN knitting_store s ON s.KSTID = l.KSTID
            ORDER BY l.KRTID DESC
            LIMIT $limit";

    $res = $db->query($sql);
    $logs = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($logs),
        'data' => $logs
    ]);
    exit();
}

// -------------------------------------------------------------
// 5. GET DASHBOARD METRICS / SUMMARY
// -------------------------------------------------------------
if ($action === 'get_stats') {
    // Total rolls & weight in store
    $stRow = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total_rolls, COALESCE(SUM(CAST(QTY AS DECIMAL(10,2))),0) as total_qty, COUNT(DISTINCT RACKNO) as active_racks FROM knitting_store"));
    
    // Transfers today
    $trRow = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as transfers_today FROM knitting_rack_transfer WHERE DATE(TBUDATE) = CURDATE()"));

    // Distinct rack numbers in use
    $rackListRes = mysqli_query($db, "SELECT DISTINCT RACKNO FROM knitting_store WHERE RACKNO IS NOT NULL AND TRIM(RACKNO) <> '' ORDER BY CAST(RACKNO AS UNSIGNED)");
    $racks = [];
    while ($r = mysqli_fetch_assoc($rackListRes)) {
        $racks[] = str_pad($r['RACKNO'], 2, '0', STR_PAD_LEFT);
    }

    echo json_encode([
        'success' => true,
        'total_rolls' => intval($stRow['total_rolls'] ?? 0),
        'total_qty' => round(floatval($stRow['total_qty'] ?? 0), 2),
        'active_racks' => intval($stRow['active_racks'] ?? 0),
        'transfers_today' => intval($trRow['transfers_today'] ?? 0),
        'racks_in_use' => $racks
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid or missing action.']);
exit();
