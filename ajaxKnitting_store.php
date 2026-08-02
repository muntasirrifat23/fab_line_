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

// Helper function to escape strings
function escapeString($db, $str) {
    return mysqli_real_escape_string($db, trim($str));
}

if ($action === 'search') {
    // Search by ROLL or BOOKING_NO
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if (empty($search)) {
        echo json_encode(['success' => false, 'error' => 'Search term is required']);
        exit;
    }
    
    $search = mysqli_real_escape_string($db, $search);
    
    $sql = "SELECT * FROM knitting_inspection WHERE ROLL LIKE '%$search%' OR BOOKING_NO LIKE '%$search%' ORDER BY KITID DESC";
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
    $roll = isset($_GET['roll']) ? trim($_GET['roll']) : '';
    
    if (empty($roll)) {
        echo json_encode(['success' => false, 'error' => 'Roll number is required']);
        exit;
    }
    
    $roll = mysqli_real_escape_string($db, $roll);
    
    $sql = "SELECT * FROM knitting_inspection WHERE ROLL = '$roll'";
    $result = mysqli_query($db, $sql);
    
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No data found for this Roll number']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
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
    
    // Check if rack column exists, if not add it
    $checkColumn = mysqli_query($db, "SHOW COLUMNS FROM knitting_inspection LIKE 'RACK'");
    if (mysqli_num_rows($checkColumn) == 0) {
        mysqli_query($db, "ALTER TABLE knitting_inspection ADD COLUMN RACK VARCHAR(50) DEFAULT NULL");
    }
    
    $sql = "UPDATE knitting_inspection SET RACK = '$rack' WHERE ROLL = '$roll'";
    $result = mysqli_query($db, $sql);
    
    if ($result) {
        if (mysqli_affected_rows($db) > 0) {
            echo json_encode(['success' => true, 'message' => "Rack location saved successfully: $rack"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Roll number not found or no changes made']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    }
    
} elseif ($action === 'get_all') {
    // Get all data
    $sql = "SELECT * FROM knitting_inspection ORDER BY KITID DESC";
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
    
    $sql = "SELECT * FROM knitting_inspection";
    
    if ($search != '') {
        $search = mysqli_real_escape_string($db, $search);
        $sql .= " WHERE ROLL LIKE '%$search%' OR BOOKING_NO LIKE '%$search%'";
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