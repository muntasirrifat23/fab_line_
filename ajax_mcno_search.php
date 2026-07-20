<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header('X-Content-Type-Options: nosniff');

// Include config
require_once 'config.php';

// Check if database connection exists
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

// Function to get MCNO list
function getMcnoList($db, $searchTerm = '') {
    $data = [];
    
    if ($searchTerm === '') {
        // Return all MCNO
        $sql = "SELECT DISTINCT MCNO FROM mcno WHERE MCNO IS NOT NULL AND MCNO != '' ORDER BY MCNO";
        $result = mysqli_query($db, $sql);
        
        if (!$result) {
            return $data;
        }
        
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row['MCNO'];
        }
    } else {
        // Return filtered MCNO for autocomplete
        $sql = "SELECT DISTINCT MCNO FROM mcno WHERE MCNO LIKE ? ORDER BY MCNO";
        $stmt = mysqli_prepare($db, $sql);
        
        if (!$stmt) {
            return $data;
        }
        
        $search = "%" . $searchTerm . "%";
        mysqli_stmt_bind_param($stmt, "s", $search);
        
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return $data;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row['MCNO'];
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return $data;
}

// Handle different request types
if ($action === 'list') {
    // Return all MCNO for validation
    $data = getMcnoList($db);
    echo json_encode($data);
    exit;
}

if ($term !== '') {
    // Return filtered MCNO for autocomplete
    $data = getMcnoList($db, $term);
    echo json_encode($data);
    exit;
}

// Default: return all MCNO
$data = getMcnoList($db);
echo json_encode($data);
?>