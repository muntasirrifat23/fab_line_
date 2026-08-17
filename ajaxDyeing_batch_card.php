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

        $rolls = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['in_batch'] = isset($_SESSION['dyeing_batch']['rolls'][$row['ROLL']]);
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
            $_SESSION['dyeing_batch']['rolls'][$row['ROLL']] = $row;
            echo json_encode([
                'success' => true,
                'data' => $row,
                'rolls' => array_values($_SESSION['dyeing_batch']['rolls']),
                'count' => count($_SESSION['dyeing_batch']['rolls']),
                'card_no' => isset($_SESSION['dyeing_batch']['card_no']) ? $_SESSION['dyeing_batch']['card_no'] : null
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
        echo json_encode([
            'success' => true,
            'card_no' => isset($_SESSION['dyeing_batch']['card_no']) ? $_SESSION['dyeing_batch']['card_no'] : null,
            'created_at' => isset($_SESSION['dyeing_batch']['created_at']) ? $_SESSION['dyeing_batch']['created_at'] : null,
            'rolls' => array_values($_SESSION['dyeing_batch']['rolls'])
        ]);
        exit;

    // Finalize the batch card (assign a card number)
    case 'create_card':
        $rolls = array_values($_SESSION['dyeing_batch']['rolls']);
        if (count($rolls) === 0) {
            echo json_encode(['success' => false, 'message' => 'Scan at least one roll to create a batch card']);
            exit;
        }

        $seq = isset($_SESSION['dyeing_batch']['seq']) ? (int)$_SESSION['dyeing_batch']['seq'] : 1;
        $cardNo = 'DBC-' . date('Ymd') . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);

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
        if (!isset($_SESSION['dyeing_batch']['seq'])) {
            $_SESSION['dyeing_batch']['seq'] = 1;
        }
        echo json_encode(['success' => true]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
        exit;
}
?>
