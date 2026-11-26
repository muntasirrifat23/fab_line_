<?php
// ajaxDyeing_batch_card.php
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

if (!isset($_SESSION['dyeing_batch']) || !is_array($_SESSION['dyeing_batch'])) {
    $_SESSION['dyeing_batch'] = [];
}
if (!isset($_SESSION['dyeing_batch']['rolls']) || !is_array($_SESSION['dyeing_batch']['rolls'])) {
    $_SESSION['dyeing_batch']['rolls'] = [];
}

try {
    ensureDyeingBatchQuantityColumns($db);
    ensureDyeingBatchCreatorColumns($db);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
    exit;
}

function nextCardNoFromDb($db) {
    $r = mysqli_query($db, "SELECT COALESCE(MAX(CAST(BCARD AS UNSIGNED)), 4000000000) + 1 AS nxt FROM dyeing_batch_card");
    if (!$r) return 4000000001;
    $row = mysqli_fetch_assoc($r);
    return $row && isset($row['nxt']) ? (int)$row['nxt'] : 4000000001;
}

function rollExistsInDatabase($db, $roll) {
    $roll = trim((string)$roll);
    if ($roll === '') {
        return false;
    }

    $stmt = $db->prepare("SELECT 1 FROM dyeing_batch_card WHERE TRIM(ROLL) = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $roll);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function formatRackNumberFirst($row) {
    $location = isset($row['RACKLOCATION']) ? trim((string)$row['RACKLOCATION']) : '';
    $number = isset($row['RACKNO']) ? trim((string)$row['RACKNO']) : '';
    return $number . $location;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {

    case 'po_numbers':
        $sql = "SELECT DISTINCT TRIM(ks.PO_NUMBER) AS PO_NUMBER
                FROM knitting_store ks
                WHERE ks.PO_NUMBER IS NOT NULL AND TRIM(ks.PO_NUMBER) <> ''
                ORDER BY PO_NUMBER";
        $result = mysqli_query($db, $sql);
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
            exit;
        }

        $poNumbers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $poNumbers[] = $row['PO_NUMBER'];
        }
        echo json_encode(['success' => true, 'po_numbers' => $poNumbers]);
        exit;

    case 'dyeing_machines':
        $result = mysqli_query($db, "SELECT DMCNO, DCAPACITY FROM dyeing_mcno ORDER BY DMCNO");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Could not load dyeing machines: ' . mysqli_error($db)]);
            exit;
        }
        $machines = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $machines[] = $row;
        }
        echo json_encode(['success' => true, 'machines' => $machines]);
        exit;

    case 'colors':
        $poNumber = isset($_GET['po_number']) ? trim($_GET['po_number']) : '';
        if ($poNumber === '') {
            echo json_encode(['success' => false, 'message' => 'PO Number is required']);
            exit;
        }

        $stmt = $db->prepare(
            "SELECT DISTINCT TRIM(ks.COLOR) AS COLOR
             FROM knitting_store ks
             WHERE TRIM(ks.PO_NUMBER) = ? AND ks.COLOR IS NOT NULL AND TRIM(ks.COLOR) <> ''
             ORDER BY COLOR"
        );
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param('s', $poNumber);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
            $stmt->close();
            exit;
        }

        $result = $stmt->get_result();
        $colors = [];
        while ($row = $result->fetch_assoc()) {
            $colors[] = $row['COLOR'];
        }
        $stmt->close();
        echo json_encode(['success' => true, 'colors' => $colors]);
        exit;

    case 'fabric_totals':
        $poNumber = isset($_GET['po_number']) ? trim($_GET['po_number']) : '';
        $color = isset($_GET['color']) ? trim($_GET['color']) : '';
        if ($poNumber === '' || $color === '') {
            echo json_encode(['success' => false, 'message' => 'PO Number and Color are required']);
            exit;
        }

        $stmt = $db->prepare(
            "SELECT TRIM(ks.FTYPE) AS FTYPE, TRIM(ks.COLOR) AS COLOR,
                    SUM(CAST(NULLIF(TRIM(ks.QTY), '') AS DECIMAL(12,3))) AS TOTAL_QTY,
                    MAX(ks.O_T) AS O_T, MAX(ks.MCDIA) AS MCDIA, MAX(ks.SL) AS SL,
                    MAX(ks.FGSM) AS FGSM, MAX(ks.FDIA) AS FDIA, MAX(ks.SONO) AS SONO,
                    MAX(ks.BUYER) AS BUYER, MAX(ks.STYLE) AS STYLE,
                    MAX(ks.CUSTOMER) AS CUSTOMER, MAX(ks.YTYPE) AS YTYPE,
                    MAX(ks.YCOUNT) AS YCOUNT, MAX(ks.YBRAND) AS YBRAND,
                    MAX(ks.LOT_NO) AS LOT_NO, MAX(ks.TPOINT) AS TPOINT,
                    MAX(ks.FEEDER_PLAN) AS FEEDER_PLAN
             FROM knitting_store ks
             WHERE TRIM(ks.PO_NUMBER) = ? AND TRIM(ks.COLOR) = ?
               AND ks.FTYPE IS NOT NULL AND TRIM(ks.FTYPE) <> ''
             GROUP BY TRIM(ks.FTYPE), TRIM(ks.COLOR)
             ORDER BY TRIM(ks.FTYPE)"
        );
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Could not prepare fabric totals query: ' . $db->error]);
            exit;
        }
        $stmt->bind_param('ss', $poNumber, $color);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Could not load fabric totals: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();
        $fabrics = [];
        while ($row = $result->fetch_assoc()) {
            $row['TOTAL_QTY'] = round((float)$row['TOTAL_QTY'], 3);
            $fabrics[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'fabrics' => $fabrics]);
        exit;

    case 'select_fabrics':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['success' => false, 'message' => 'Invalid batch card selection data']);
            exit;
        }
        $poNumber = isset($input['po_number']) ? trim((string)$input['po_number']) : '';
        $color = isset($input['color']) ? trim((string)$input['color']) : '';
        $dyeingMcno = isset($input['dymcno']) ? trim((string)$input['dymcno']) : '';
        $requestedItems = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
        $allowedCategories = ['BODY', 'RIB', 'NECK'];
        if ($poNumber === '' || $color === '' || $dyeingMcno === '' ||
            count($requestedItems) === 0 || count($requestedItems) > 300) {
            echo json_encode(['success' => false, 'message' => 'Select a PO Number, Color, Dyeing MC No and at least one fabric row']);
            exit;
        }

        $machineStmt = $db->prepare('SELECT DMCNO FROM dyeing_mcno WHERE DMCNO = ? LIMIT 1');
        if (!$machineStmt) {
            echo json_encode(['success' => false, 'message' => 'Could not validate dyeing machine: ' . $db->error]);
            exit;
        }
        $machineStmt->bind_param('s', $dyeingMcno);
        if (!$machineStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Could not validate dyeing machine: ' . $machineStmt->error]);
            $machineStmt->close();
            exit;
        }
        $machineResult = $machineStmt->get_result();
        $machine = $machineResult ? $machineResult->fetch_assoc() : null;
        $machineStmt->close();
        if (!$machine) {
            echo json_encode(['success' => false, 'message' => 'Selected Dyeing MC No is not available. Please choose again.']);
            exit;
        }
        $dyeingMcno = trim((string)$machine['DMCNO']);

        $allowedProcessFields = dyeingBatchCardProcessFieldNames();
        $requestedProcesses = isset($input['processes']) && is_array($input['processes']) ? $input['processes'] : [];
        $selectedProcesses = [];
        foreach ($requestedProcesses as $processField => $processValue) {
            if (is_int($processField)) {
                $processField = is_string($processValue) ? $processValue : '';
                $processValue = $processField;
            }
            if (!is_string($processField) || !in_array($processField, $allowedProcessFields, true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid Dyeing Process selection']);
                exit;
            }
            if ($processField === 'OTHERS') {
                $processValue = is_string($processValue) ? trim($processValue) : '';
                $selectedProcesses[$processField] = $processValue !== '' ? $processValue : 'Others';
            } else {
                $selectedProcesses[$processField] = $processField;
            }
        }
        if (count($selectedProcesses) === 0) {
            echo json_encode(['success' => false, 'message' => 'Select at least one Dyeing Process']);
            exit;
        }

        $sourceStmt = $db->prepare(
            "SELECT SUM(CAST(NULLIF(TRIM(QTY), '') AS DECIMAL(12,3))) AS TOTAL_QTY,
                    MAX(O_T) AS O_T, MAX(MCDIA) AS MCDIA, MAX(SL) AS SL,
                    MAX(FGSM) AS FGSM, MAX(FDIA) AS FDIA, MAX(SONO) AS SONO,
                    MAX(BUYER) AS BUYER, MAX(STYLE) AS STYLE, MAX(CUSTOMER) AS CUSTOMER,
                    MAX(YTYPE) AS YTYPE, MAX(YCOUNT) AS YCOUNT, MAX(YBRAND) AS YBRAND,
                    MAX(LOT_NO) AS LOT_NO, MAX(TPOINT) AS TPOINT, MAX(FEEDER_PLAN) AS FEEDER_PLAN
             FROM knitting_store
             WHERE TRIM(PO_NUMBER) = ? AND TRIM(COLOR) = ? AND TRIM(FTYPE) = ?"
        );
        if (!$sourceStmt) {
            echo json_encode(['success' => false, 'message' => 'Could not prepare fabric quantity validation: ' . $db->error]);
            exit;
        }

        $items = [];
        $seen = [];
        $allocatedByFabric = [];
        $totalQty = 0;
        foreach ($requestedItems as $requestedItem) {
            if (!is_array($requestedItem)) {
                echo json_encode(['success' => false, 'message' => 'Invalid fabric selection row']);
                $sourceStmt->close();
                exit;
            }
            $category = isset($requestedItem['category']) ? strtoupper(trim((string)$requestedItem['category'])) : '';
            $fabricType = isset($requestedItem['fabric_type']) ? trim((string)$requestedItem['fabric_type']) : '';
            $quantity = isset($requestedItem['quantity']) && is_numeric($requestedItem['quantity'])
                ? round((float)$requestedItem['quantity'], 3)
                : 0;
            $fabricKey = strtoupper($fabricType);
            $key = $fabricKey . '|' . $category;
            if (!in_array($category, $allowedCategories, true) || $fabricType === '' ||
                !is_finite($quantity) || $quantity <= 0 || isset($seen[$key])) {
                echo json_encode(['success' => false, 'message' => 'Each selected fabric needs a unique F/T per Fabric Type and valid Batch QTY']);
                $sourceStmt->close();
                exit;
            }

            $sourceStmt->bind_param('sss', $poNumber, $color, $fabricType);
            if (!$sourceStmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Could not verify ' . $fabricType . ' quantity: ' . $sourceStmt->error]);
                $sourceStmt->close();
                exit;
            }
            $sourceResult = $sourceStmt->get_result();
            $source = $sourceResult ? $sourceResult->fetch_assoc() : null;
            $availableQty = $source && is_numeric($source['TOTAL_QTY']) ? round((float)$source['TOTAL_QTY'], 3) : 0;
            $remainingQty = max(0, round($availableQty - (isset($allocatedByFabric[$fabricKey])
                ? $allocatedByFabric[$fabricKey]
                : 0), 3));
            if (!$source || $remainingQty <= 0 || $quantity - $remainingQty > 0.000001) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Batch QTY for ' . $fabricType . ' cannot exceed its total ' .
                        number_format($remainingQty, 3) . ' KG remaining.'
                ]);
                $sourceStmt->close();
                exit;
            }

            $source['CATEGORY'] = $category;
            $source['FTYPE'] = $fabricType;
            $source['TOTAL_QTY'] = $availableQty;
            $source['RQTY'] = $quantity;
            $source['AQTY'] = max(0, round($remainingQty - $quantity, 3));
            $items[] = $source;
            $seen[$key] = true;
            $allocatedByFabric[$fabricKey] = round(
                (isset($allocatedByFabric[$fabricKey]) ? $allocatedByFabric[$fabricKey] : 0) + $quantity,
                3
            );
            $totalQty += $quantity;
        }
        $sourceStmt->close();

        $_SESSION['dyeing_batch']['items'] = $items;
        $_SESSION['dyeing_batch']['target_qty'] = round($totalQty, 3);
        $_SESSION['dyeing_batch']['dymcno'] = $dyeingMcno;
        $_SESSION['dyeing_batch']['processes'] = $selectedProcesses;
        $_SESSION['dyeing_batch']['po_number'] = $poNumber;
        $_SESSION['dyeing_batch']['color'] = $color;
        echo json_encode([
            'success' => true,
            'count' => count($items),
            'total_qty' => round($totalQty, 3)
        ]);
        exit;

    case 'search_by_po_color':
        $poNumber = isset($_GET['po_number']) ? trim($_GET['po_number']) : '';
        $color = isset($_GET['color']) ? trim($_GET['color']) : '';
        if ($poNumber === '' || $color === '') {
            echo json_encode(['success' => false, 'message' => 'PO Number and Color are required']);
            exit;
        }

        $stmt = $db->prepare(
            "SELECT ks.*, ks.QTY AS KQTY,
                    GREATEST(CAST(ks.QTY AS DECIMAL(12,3)) - COALESCE((
                        SELECT SUM(CAST(dbc.RQTY AS DECIMAL(12,3)))
                        FROM dyeing_batch_card dbc
                        WHERE TRIM(dbc.ROLL) = TRIM(ks.ROLL)
                    ), 0), 0) AS AQTY
             FROM knitting_store ks
             WHERE TRIM(ks.PO_NUMBER) = ? AND TRIM(ks.COLOR) = ?
               AND CAST(ks.QTY AS DECIMAL(12,3)) > COALESCE((
                   SELECT SUM(CAST(dbc.RQTY AS DECIMAL(12,3)))
                   FROM dyeing_batch_card dbc
                   WHERE TRIM(dbc.ROLL) = TRIM(ks.ROLL)
               ), 0)
             ORDER BY ks.KSTID DESC
             LIMIT 500"
        );
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param('ss', $poNumber, $color);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
            $stmt->close();
            exit;
        }

        $result = $stmt->get_result();
        $rolls = [];
        while ($row = $result->fetch_assoc()) {
            $row['RACK'] = formatRackNumberFirst($row);
            $rolls[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'rolls' => $rolls]);
        exit;

    case 'select_rolls':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            echo json_encode(['success' => false, 'message' => 'Invalid batch card selection data']);
            exit;
        }
        $poNumber = isset($input['po_number']) ? trim((string)$input['po_number']) : '';
        $color = isset($input['color']) ? trim((string)$input['color']) : '';
        $targetQty = isset($input['target_qty']) && is_numeric($input['target_qty']) ? (float)$input['target_qty'] : 0;
        $categoryQuantities = [];
        foreach (['body_qty', 'rib_qty', 'neck_qty'] as $quantityField) {
            if (!isset($input[$quantityField]) || !is_numeric($input[$quantityField])) {
                echo json_encode(['success' => false, 'message' => 'Enter valid Body, RIB and Neck quantities']);
                exit;
            }
            $quantity = (float)$input[$quantityField];
            if (!is_finite($quantity) || $quantity < 0) {
                echo json_encode(['success' => false, 'message' => 'Body, RIB and Neck quantities cannot be negative']);
                exit;
            }
            $categoryQuantities[$quantityField] = round($quantity, 3);
        }
        $categoryTotal = round(array_sum($categoryQuantities), 3);
        $dyeingMcno = isset($input['dymcno']) ? trim((string)$input['dymcno']) : '';
        $requestedRolls = isset($input['rolls']) && is_array($input['rolls']) ? $input['rolls'] : [];
        $allowedProcessFields = dyeingBatchCardProcessFieldNames();
        $requestedProcesses = isset($input['processes']) && is_array($input['processes'])
            ? $input['processes']
            : [];
        $selectedProcesses = [];
        foreach ($requestedProcesses as $processField => $processValue) {
            if (is_int($processField)) {
                $processField = is_string($processValue) ? $processValue : '';
                $processValue = $processField;
            }
            if (!is_string($processField) || !in_array($processField, $allowedProcessFields, true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid Dyeing Process selection']);
                exit;
            }
            if ($processField === 'OTHERS') {
                $processValue = is_string($processValue) ? trim($processValue) : '';
                $selectedProcesses[$processField] = $processValue !== '' ? $processValue : 'Others';
            } else {
                $selectedProcesses[$processField] = $processField;
            }
        }
        if (count($selectedProcesses) === 0) {
            echo json_encode(['success' => false, 'message' => 'Select at least one Dyeing Process']);
            exit;
        }

        $requiredQtyByRoll = [];
        foreach ($requestedRolls as $requestedRoll) {
            if (!is_array($requestedRoll) || !isset($requestedRoll['ROLL']) ||
                !isset($requestedRoll['required_qty']) || !is_numeric($requestedRoll['required_qty'])) {
                echo json_encode(['success' => false, 'message' => 'Each selected roll must include a valid Required QTY']);
                exit;
            }
            $roll = trim((string)$requestedRoll['ROLL']);
            $requiredQty = round((float)$requestedRoll['required_qty'], 3);
            if ($roll === '' || !is_finite($requiredQty) || $requiredQty <= 0 ||
                isset($requiredQtyByRoll[$roll])) {
                echo json_encode(['success' => false, 'message' => 'Selected rolls or Required QTY values are invalid']);
                exit;
            }
            $requiredQtyByRoll[$roll] = $requiredQty;
        }
        $rolls = array_keys($requiredQtyByRoll);

        if ($poNumber === '' || $color === '' || $dyeingMcno === '' ||
            !is_finite($targetQty) || $targetQty <= 0 || $categoryTotal <= 0 ||
            abs(round($targetQty, 3) - $categoryTotal) > 0.001 || count($rolls) === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Select a PO Number, Color, Dyeing MC No, at least one roll, and a valid Body/RIB/Neck quantity'
            ]);
            exit;
        }
        if (count($rolls) > 500) {
            echo json_encode(['success' => false, 'message' => 'Too many rolls selected']);
            exit;
        }

        $machineStmt = $db->prepare("SELECT DMCNO FROM dyeing_mcno WHERE DMCNO = ? LIMIT 1");
        if (!$machineStmt) {
            echo json_encode(['success' => false, 'message' => 'Could not validate dyeing machine: ' . $db->error]);
            exit;
        }
        $machineStmt->bind_param('s', $dyeingMcno);
        if (!$machineStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Could not validate dyeing machine: ' . $machineStmt->error]);
            $machineStmt->close();
            exit;
        }
        $machineResult = $machineStmt->get_result();
        $machine = $machineResult ? $machineResult->fetch_assoc() : null;
        $machineStmt->close();
        if (!$machine) {
            echo json_encode(['success' => false, 'message' => 'Selected Dyeing MC No is not available. Please choose again.']);
            exit;
        }
        $dyeingMcno = trim((string)$machine['DMCNO']);

        $placeholders = implode(',', array_fill(0, count($rolls), '?'));
        $stmt = $db->prepare(
            "SELECT ks.*, ks.QTY AS KQTY,
                    GREATEST(CAST(ks.QTY AS DECIMAL(12,3)) - COALESCE((
                        SELECT SUM(CAST(dbc.RQTY AS DECIMAL(12,3)))
                        FROM dyeing_batch_card dbc
                        WHERE TRIM(dbc.ROLL) = TRIM(ks.ROLL)
                    ), 0), 0) AS AQTY
             FROM knitting_store ks
             WHERE TRIM(ks.PO_NUMBER) = ? AND TRIM(ks.COLOR) = ?
               AND ks.ROLL IN ($placeholders)
             ORDER BY ks.KSTID DESC"
        );
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
            exit;
        }

        $params = array_merge([$poNumber, $color], $rolls);
        $bindArgs = ['ss' . str_repeat('s', count($rolls))];
        foreach ($params as &$param) {
            $bindArgs[] = &$param;
        }
        unset($param);
        call_user_func_array([$stmt, 'bind_param'], $bindArgs);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
            $stmt->close();
            exit;
        }

        $result = $stmt->get_result();
        $selectedRolls = [];
        $selectedQty = 0;
        while ($row = $result->fetch_assoc()) {
            $row['RACK'] = formatRackNumberFirst($row);
            $roll = (string)$row['ROLL'];
            $requiredQty = isset($requiredQtyByRoll[$roll]) ? $requiredQtyByRoll[$roll] : 0;
            $availableQty = is_numeric($row['AQTY']) ? (float)$row['AQTY'] : 0;
            if ($requiredQty <= 0 || $requiredQty - $availableQty > 0.000001) {
                continue;
            }
            $row['RQTY'] = $requiredQty;
            $selectedRolls[$roll] = $row;
            $selectedQty += $requiredQty;
        }
        $stmt->close();

        if (count($selectedRolls) !== count($rolls)) {
            $unavailableRolls = array_values(array_diff($rolls, array_keys($selectedRolls)));
            echo json_encode([
                'success' => false,
                'message' => 'These rolls are no longer available for the requested quantity: ' .
                    implode(', ', $unavailableRolls) . '. Search again and choose available rolls.'
            ]);
            exit;
        }
        $_SESSION['dyeing_batch']['rolls'] = $selectedRolls;
        $_SESSION['dyeing_batch']['target_qty'] = $targetQty;
        $_SESSION['dyeing_batch']['category_quantities'] = $categoryQuantities;
        $_SESSION['dyeing_batch']['dymcno'] = $dyeingMcno;
        $_SESSION['dyeing_batch']['processes'] = $selectedProcesses;
        echo json_encode([
            'success' => true,
            'count' => count($selectedRolls),
            'total_qty' => round($selectedQty, 3)
        ]);
        exit;

    // Search / list rolls from knitting_store table
    case 'search_rolls':
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $sql = "SELECT * FROM knitting_store";
        if ($search !== '') {
            $search = mysqli_real_escape_string($db, $search);
            $sql .= " WHERE ROLL LIKE '%$search%' OR PO_NUMBER LIKE '%$search%' OR SONO LIKE '%$search%'
                      OR BUYER LIKE '%$search%' OR STYLE LIKE '%$search%' OR COLOR LIKE '%$search%'
                      OR RACK LIKE '%$search%'
                      OR CONCAT(COALESCE(RACKNO, ''), COALESCE(RACKLOCATION, '')) LIKE '%$search%'
                      OR QTY LIKE '%$search%'";
        }
        $sql .= " ORDER BY KSTID DESC LIMIT 100";

        $result = mysqli_query($db, $sql);
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
            exit;
        }

        // Rolls already saved in a batch card
        $savedRolls = [];
        $r2 = mysqli_query($db, "SELECT DISTINCT ROLL FROM dyeing_batch_card");
        if ($r2) {
            while ($x = mysqli_fetch_row($r2)) {
                $savedRolls[$x[0]] = true;
            }
        }

        $rolls = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['RACK'] = formatRackNumberFirst($row);
            $inSession = isset($_SESSION['dyeing_batch']['rolls'][$row['ROLL']]);
            $row['in_batch'] = $inSession || isset($savedRolls[$row['ROLL']]);
            $rolls[] = $row;
        }

        echo json_encode(['success' => true, 'rolls' => $rolls]);
        exit;

    // Fetch a roll's data from knitting_store table
    case 'get_roll':
        $roll = isset($_GET['roll']) ? trim($_GET['roll']) : '';
        if ($roll === '') {
            echo json_encode(['success' => false, 'message' => 'Roll number is required']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM knitting_store WHERE TRIM(ROLL) = ? LIMIT 1");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param('s', $roll);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $row['RACK'] = formatRackNumberFirst($row);
            $already = isset($_SESSION['dyeing_batch']['rolls'][$row['ROLL']]);
            echo json_encode([
                'success' => true,
                'data' => $row,
                'already_added' => $already
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No data found in knitting_store for ROLL: ' . $roll]);
        }
        $stmt->close();
        exit;

    // Add a scanned roll to the current batch card (fetches fresh data from knitting_store)
    case 'add_roll':
        $input = json_decode(file_get_contents('php://input'), true);
        $roll = isset($input['ROLL']) ? trim($input['ROLL']) : '';
        if ($roll === '') {
            echo json_encode(['success' => false, 'message' => 'Roll number is required']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM knitting_store WHERE TRIM(ROLL) = ? LIMIT 1");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param('s', $roll);
        $stmt->execute();
        $result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
            $stmt->close();
            $row['RACK'] = formatRackNumberFirst($row);
            if (isset($_SESSION['dyeing_batch']['rolls'][$row['ROLL']])) {
                echo json_encode(['success' => false, 'message' => 'Roll ' . $row['ROLL'] . ' is already added to this batch card']);
                exit;
            }
            $rs = mysqli_query($db, "SELECT 1 FROM dyeing_batch_card WHERE ROLL = '" . mysqli_real_escape_string($db, $row['ROLL']) . "' LIMIT 1");
            if ($rs && mysqli_num_rows($rs) > 0) {
                echo json_encode(['success' => false, 'message' => 'Roll ' . $row['ROLL'] . ' is already saved in a batch card']);
                exit;
            }
            $_SESSION['dyeing_batch']['rolls'][$row['ROLL']] = $row;
            echo json_encode([
                'success' => true,
                'data' => $row,
                'rolls' => array_values($_SESSION['dyeing_batch']['rolls']),
                'count' => count($_SESSION['dyeing_batch']['rolls']),
                'card_no' => isset($_SESSION['dyeing_batch']['card_no']) ? $_SESSION['dyeing_batch']['card_no'] : null,
                'next_card_no' => nextCardNoFromDb($db)
            ]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Roll not found in knitting_store: ' . $roll]);
        }
        exit;

    // Remove a roll from the current batch card
    case 'delete_roll':
        $input = json_decode(file_get_contents('php://input'), true);
        $roll = isset($input['ROLL']) ? trim($input['ROLL']) : '';

        if (isset($_SESSION['dyeing_batch']['rolls'][$roll])) {
            unset($_SESSION['dyeing_batch']['rolls'][$roll]);
            echo json_encode([
                'success' => true,
                'rolls' => array_values($_SESSION['dyeing_batch']['rolls']),
                'count' => count($_SESSION['dyeing_batch']['rolls'])
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Roll not found in this batch card']);
        }
        exit;

    // Return current batch card data
    case 'get_batch':
        $cardNo = isset($_SESSION['dyeing_batch']['card_no']) ? $_SESSION['dyeing_batch']['card_no'] : null;
        // Drop legacy (non-numeric) card numbers
        if ($cardNo !== null && !ctype_digit((string)$cardNo)) {
            $cardNo = null;
        }
        $next = nextCardNoFromDb($db);

        echo json_encode([
            'success' => true,
            'card_no' => $cardNo,
            'next_card_no' => $next,
            'created_at' => isset($_SESSION['dyeing_batch']['created_at']) ? $_SESSION['dyeing_batch']['created_at'] : null,
            'rolls' => array_values($_SESSION['dyeing_batch']['rolls'])
        ]);
        exit;

    // Return already-created batch cards from DB
    case 'saved_cards':
        $result = mysqli_query($db, "SELECT * FROM dyeing_batch_card ORDER BY BCARD DESC, DBCTID ASC LIMIT 500");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
            exit;
        }
        $cards = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $bc = $row['BCARD'];
            if (!isset($cards[$bc])) {
                $cards[$bc] = [
                    'card_no' => $bc,
                    'created_at' => $row['CREATED_DATE'],
                    'did' => $row['DID'],
                    'dname' => $row['DNAME'],
                    'rolls' => []
                ];
            }
            $cards[$bc]['rolls'][] = $row;
        }
        echo json_encode(['success' => true, 'cards' => array_values($cards)]);
        exit;

    // Finalize the batch card (assign a card number)
    case 'create_card':
        $rolls = array_values($_SESSION['dyeing_batch']['rolls']);
        if (count($rolls) === 0) {
            echo json_encode(['success' => false, 'message' => 'Scan at least one roll to create a batch card']);
            exit;
        }

        $seq = isset($_SESSION['dyeing_batch']['seq']) ? (int)$_SESSION['dyeing_batch']['seq'] : 4000000001;
        $cardNo = (string)$seq;

        $_SESSION['dyeing_batch']['card_no'] = $cardNo;
        $_SESSION['dyeing_batch']['created_at'] = date('Y-m-d H:i:s');
        $_SESSION['dyeing_batch']['seq'] = $seq + 1;

        echo json_encode([
            'success' => true,
            'card_no' => $cardNo,
            'count' => count($rolls)
        ]);
        exit;

    // Start a fresh batch card
    case 'new_card':
        unset($_SESSION['dyeing_batch']['rolls']);
        unset($_SESSION['dyeing_batch']['card_no']);
        unset($_SESSION['dyeing_batch']['created_at']);
        unset($_SESSION['dyeing_batch']['saved']);
        unset($_SESSION['dyeing_batch']['processes']);
        $_SESSION['dyeing_batch']['seq'] = nextCardNoFromDb($db);
        echo json_encode([
            'success' => true,
            'next_card_no' => (int)$_SESSION['dyeing_batch']['seq']
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
        exit;
}
?>
