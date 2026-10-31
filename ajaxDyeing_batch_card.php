<?php
// ajaxDyeing_batch_card.php
session_start();
require_once 'config.php';

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

function nextCardNoFromDb($db) {
    $r = mysqli_query($db, "SELECT COALESCE(MAX(CAST(BCMTID AS UNSIGNED)), 4000000000) + 1 AS nxt FROM dyeing_batch_card");
    if (!$r) return 4000000001;
    $row = mysqli_fetch_assoc($r);
    return (int)$row['nxt'];
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

    case 'search_by_po_color':
        $poNumber = isset($_GET['po_number']) ? trim($_GET['po_number']) : '';
        $color = isset($_GET['color']) ? trim($_GET['color']) : '';
        if ($poNumber === '' || $color === '') {
            echo json_encode(['success' => false, 'message' => 'PO Number and Color are required']);
            exit;
        }

        $stmt = $db->prepare(
            "SELECT ks.*
             FROM knitting_store ks
             WHERE TRIM(ks.PO_NUMBER) = ? AND TRIM(ks.COLOR) = ?
               AND NOT EXISTS (
                   SELECT 1 FROM dyeing_batch_card dbc
                   WHERE TRIM(dbc.ROLL) = TRIM(ks.ROLL)
               )
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
        $poNumber = isset($input['po_number']) ? trim((string)$input['po_number']) : '';
        $color = isset($input['color']) ? trim((string)$input['color']) : '';
        $targetQty = isset($input['target_qty']) && is_numeric($input['target_qty']) ? (float)$input['target_qty'] : 0;
        $rolls = isset($input['rolls']) && is_array($input['rolls']) ? $input['rolls'] : [];

        $rolls = array_values(array_unique(array_filter(array_map(function ($roll) {
            return is_scalar($roll) ? trim((string)$roll) : '';
        }, $rolls), function ($roll) {
            return $roll !== '';
        })));

        if ($poNumber === '' || $color === '' || !is_finite($targetQty) || $targetQty <= 0 || count($rolls) === 0) {
            echo json_encode(['success' => false, 'message' => 'Select a PO Number, Color, target QTY and at least one roll']);
            exit;
        }
        if (count($rolls) > 500) {
            echo json_encode(['success' => false, 'message' => 'Too many rolls selected']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($rolls), '?'));
        $stmt = $db->prepare(
            "SELECT ks.*
             FROM knitting_store ks
             WHERE TRIM(ks.PO_NUMBER) = ? AND TRIM(ks.COLOR) = ?
               AND ks.ROLL IN ($placeholders)
               AND NOT EXISTS (
                   SELECT 1 FROM dyeing_batch_card dbc
                   WHERE TRIM(dbc.ROLL) = TRIM(ks.ROLL)
               )
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
            $selectedRolls[$row['ROLL']] = $row;
            $selectedQty += is_numeric($row['QTY']) ? (float)$row['QTY'] : 0;
        }
        $stmt->close();

        if (count($selectedRolls) !== count($rolls)) {
            echo json_encode(['success' => false, 'message' => 'Some suggested rolls are no longer available. Search again and choose another suggestion.']);
            exit;
        }
        $minimumQty = max(0, $targetQty - 5);
        $maximumQty = $targetQty + 5;
        if ($selectedQty + 0.000001 < $minimumQty || $selectedQty - 0.000001 > $maximumQty) {
            echo json_encode([
                'success' => false,
                'message' => 'Selected roll QTY must be within 5 KG below or above the target'
            ]);
            exit;
        }

        $_SESSION['dyeing_batch']['rolls'] = $selectedRolls;
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
        $result = mysqli_query($db, "SELECT * FROM dyeing_batch_card ORDER BY BCMTID DESC, DBCTID ASC LIMIT 500");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
            exit;
        }
        $cards = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $bc = $row['BCMTID'];
            if (!isset($cards[$bc])) {
                $cards[$bc] = [
                    'card_no' => $bc,
                    'created_at' => $row['CREATED_DATE'],
                    'uname' => $row['UNAME'],
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
