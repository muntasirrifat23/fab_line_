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

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {

    // Search / list rolls from knitting_store table
    case 'search_rolls':
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $sql = "SELECT * FROM knitting_store";
        if ($search !== '') {
            $search = mysqli_real_escape_string($db, $search);
            $sql .= " WHERE ROLL LIKE '%$search%' OR PO_NUMBER LIKE '%$search%' OR SONO LIKE '%$search%'
                      OR BUYER LIKE '%$search%' OR STYLE LIKE '%$search%' OR COLOR LIKE '%$search%'
                      OR RACK LIKE '%$search%' OR QTY LIKE '%$search%'";
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
