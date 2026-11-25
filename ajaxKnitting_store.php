<?php
// ajaxKnitting_store.php
include 'config.php';

header('Content-Type: application/json');

// Check if database connection exists
if (!isset($db) || !$db) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$TABLE = 'knitting_inspection';

if ($action === 'available_rolls') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    if ($date !== '') {
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date || $date > date('Y-m-d')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Select a valid date that is not in the future.']);
            exit;
        }
    }
    $sql = "SELECT z.*
            FROM knitting_store_zone z
            WHERE (? = ''
                OR CONVERT(z.SONO USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    LIKE CONVERT(CONCAT('%', ?, '%') USING utf8mb4) COLLATE utf8mb4_unicode_ci
                OR CONVERT(z.PO_NUMBER USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    LIKE CONVERT(CONCAT('%', ?, '%') USING utf8mb4) COLLATE utf8mb4_unicode_ci
                OR CONVERT(z.BUYER USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    LIKE CONVERT(CONCAT('%', ?, '%') USING utf8mb4) COLLATE utf8mb4_unicode_ci
                OR CONVERT(z.STYLE USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    LIKE CONVERT(CONCAT('%', ?, '%') USING utf8mb4) COLLATE utf8mb4_unicode_ci
                OR CONVERT(z.COLOR USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    LIKE CONVERT(CONCAT('%', ?, '%') USING utf8mb4) COLLATE utf8mb4_unicode_ci
                OR CONVERT(z.FTYPE USING utf8mb4) COLLATE utf8mb4_unicode_ci
                    LIKE CONVERT(CONCAT('%', ?, '%') USING utf8mb4) COLLATE utf8mb4_unicode_ci)
              AND NOT EXISTS (
                  SELECT 1
                  FROM knitting_store ks
                  WHERE CONVERT(TRIM(ks.ROLL) USING utf8mb4) COLLATE utf8mb4_unicode_ci
                      = CONVERT(TRIM(z.ROLL) USING utf8mb4) COLLATE utf8mb4_unicode_ci
              )
              AND (? = '' OR z.BUDAT = ?)
            ORDER BY z.KSZTID DESC";
    $statement = mysqli_prepare($db, $sql);
    if (!$statement) {
        echo json_encode(['success' => false, 'error' => 'Unable to prepare Knitting Store Common Zone search: ' . mysqli_error($db)]);
        exit;
    }

    mysqli_stmt_bind_param($statement, 'sssssssss', $search, $search, $search, $search, $search, $search, $search, $date, $date);
    if (!mysqli_stmt_execute($statement)) {
        $error = mysqli_stmt_error($statement);
        mysqli_stmt_close($statement);
        echo json_encode(['success' => false, 'error' => 'Unable to load Knitting Store Common Zone rolls: ' . $error]);
        exit;
    }

    $result = mysqli_stmt_get_result($statement);
    if (!$result) {
        $error = mysqli_stmt_error($statement);
        mysqli_stmt_close($statement);
        echo json_encode(['success' => false, 'error' => 'Unable to read Knitting Store Common Zone rolls: ' . $error]);
        exit;
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_stmt_close($statement);
    echo json_encode(['success' => true, 'data' => $data]);

} elseif ($action === 'search') {
    // Search by ROLL or PO_NUMBER
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (empty($search)) {
        echo json_encode(['success' => false, 'error' => 'Search term is required']);
        exit;
    }

    $search = mysqli_real_escape_string($db, $search);

    $sql = "SELECT * FROM $TABLE WHERE ROLL LIKE '%$search%' OR PO_NUMBER LIKE '%$search%' ORDER BY KITID DESC";
    $result = mysqli_query($db, $sql);

    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    }

} elseif ($action === 'get_by_roll') {
    // Get data by ROLL (for QR scan)
    if (isset($_GET['manual']) && $_GET['manual'] === '1' && empty($_SESSION['knitting_store_manual_entry_authorized'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unlock manual entry before searching for a roll.']);
        exit;
    }

    $roll = isset($_GET['roll']) ? trim($_GET['roll']) : '';

    if (empty($roll)) {
        echo json_encode(['success' => false, 'error' => 'Roll number is required']);
        exit;
    }

    $rollEsc = mysqli_real_escape_string($db, $roll);
    // If the roll is already stored in knitting_store, do NOT show data again
    $storeRes = mysqli_query($db, "SELECT ROLL FROM knitting_store WHERE TRIM(ROLL) = '$rollEsc' LIMIT 1");
    if (!$storeRes) {
        echo json_encode(['success' => false, 'error' => 'Unable to check whether this roll is already stored.']);
        exit;
    }
    if ($storeRes && mysqli_num_rows($storeRes) > 0) {
        echo json_encode(['success' => false, 'error' => "Data already stored in Knitting Store for Roll: $roll"]);
        exit;
    }

    // Knitting Store receives rolls from the already-populated Common Zone.
    $sql = "SELECT * FROM knitting_store_zone WHERE TRIM(ROLL) = '$rollEsc' ORDER BY KSZTID DESC LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Unable to match this roll in Store Common Zone: ' . mysqli_error($db)]);
        exit;
    }

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        if ($data) {
            $qcStatus = strtolower(trim((string) (isset($data['QC_STATUS']) ? $data['QC_STATUS'] : '')));
            if ($qcStatus !== 'passed') {
                echo json_encode([
                    'success' => false,
                    'error' => "Roll $roll has not passed QC in Store Common Zone and cannot be scanned into Knitting Store."
                ]);
                exit;
            }

            $data['ROLL'] = $roll;
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'error' => "Roll $roll does not match any ROLL in Store Common Zone."]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => "Roll $roll does not match any ROLL in Store Common Zone."]);
    }

} elseif ($action === 'save_rack') {
    // Save rack location
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid input data']);
        exit;
    }

    $roll = isset($input['ROLL']) ? trim($input['ROLL']) : '';
    $rack = isset($input['RACK']) ? trim($input['RACK']) : '';

    if (empty($roll) || empty($rack)) {
        echo json_encode(['success' => false, 'error' => 'Roll number and Rack are required']);
        exit;
    }

    $roll = mysqli_real_escape_string($db, $roll);
    $rack = mysqli_real_escape_string($db, $rack);

    // Check if rack column exists in knitting_inspection_test, if not add it
    $checkColumn = mysqli_query($db, "SHOW COLUMNS FROM $TABLE LIKE 'RACK'");
    if (mysqli_num_rows($checkColumn) == 0) {
        mysqli_query($db, "ALTER TABLE $TABLE ADD COLUMN RACK VARCHAR(50) DEFAULT NULL");
    }

    $sql = "UPDATE $TABLE SET RACK = '$rack' WHERE TRIM(ROLL) = '$roll'";
    $result = mysqli_query($db, $sql);

    if ($result) {
        if (mysqli_affected_rows($db) > 0) {
            echo json_encode(['success' => true, 'message' => "Rack location saved successfully: $rack"]);
        } else {
            // Check if roll exists at all
            $chk = mysqli_query($db, "SELECT KITID FROM $TABLE WHERE TRIM(ROLL) = '$roll' LIMIT 1");
            if (mysqli_num_rows($chk) > 0) {
                echo json_encode(['success' => true, 'message' => "Rack location saved successfully: $rack"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Roll number not found or no changes made']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    }

} elseif ($action === 'get_all') {
    // Get all data
    $sql = "SELECT * FROM $TABLE ORDER BY KITID DESC";
    $result = mysqli_query($db, $sql);

    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    }

} else {
    // Default: return all data (for backward compatibility)
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $sql = "SELECT * FROM $TABLE";

    if ($search != '') {
        $search = mysqli_real_escape_string($db, $search);
        $sql .= " WHERE ROLL LIKE '%$search%' OR PO_NUMBER LIKE '%$search%'";
    }

    $sql .= " ORDER BY KITID DESC";
    $result = mysqli_query($db, $sql);

    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    }
}
?>