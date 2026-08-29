<?php
// config.php - Database configuration with better error handling

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check session expiry (exempt 'tv' user)
if (isset($_SESSION['expire_time'])) {
    if (!(isset($_SESSION['username']) && strcasecmp($_SESSION['username'], 'tv') === 0)) {
        if (time() > $_SESSION['expire_time']) {
            session_unset();
            session_destroy();
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('HTTP/1.1 401 Unauthorized');
                echo 'SESSION_EXPIRED';
                exit();
            } else {
                header('Location: login.php');
                exit();
            }
        }
    }
}

// Inject auto logout script into HTML pages for non-AJAX requests
// Detect AJAX request
$isAjax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

// Auto logout script শুধুমাত্র HTML page-এর জন্য
if (
    !$isAjax &&
    isset($_SERVER['SCRIPT_NAME']) &&
    stripos(basename($_SERVER['SCRIPT_NAME']), 'ajax') === false
) {

    $inject_script = '<script src="auto_logout.js"></script>';

    if (!headers_sent()) {
        ob_start(function ($buffer) use ($inject_script) {

            // শুধুমাত্র HTML response হলে inject করবে
            if (stripos($buffer, '<html') === false) {
                return $buffer;
            }

            $pos = stripos($buffer, '</body>');

            if ($pos !== false) {
                return substr_replace(
                    $buffer,
                    $inject_script . '</body>',
                    $pos,
                    7
                );
            }

            return $buffer;
        });
    }
}

// Database connection
$hostname = "localhost";
$username = "root";
$password = "pgadmin";
$databaseName = "knittingdb";

// Connect to mysql database with error handling
$db = mysqli_connect($hostname, $username, $password, $databaseName);

// Check connection
if (!$db) {
    $error = mysqli_connect_error();
    // Log error (you can add error logging here)
    error_log("Database connection failed: " . $error);
    
    // For AJAX requests, return JSON error
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }
    
    // For regular requests, show a user-friendly message
    die("Server Error: Unable to connect to database. Please try again later.");
}

// Set charset
mysqli_set_charset($db, 'utf8mb4');

if (!defined('APP_BASE_URL')) {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') : '/Knitting Project';
    define('APP_BASE_URL', "http://" . $host . $scriptDir);
}

if (!function_exists('get_knit_card_program_col')) {
    /**
     * Dynamically resolves the Knitting Program ID column in knit_card table.
     * Supports KNITTING_PROGRAM_ID, `Knitting Program ID`, knitting_program_id, or KPTID.
     */
    function get_knit_card_program_col($database_conn = null) {
        static $resolved_col = null;
        if ($resolved_col !== null) {
            return $resolved_col;
        }

        global $db;
        $conn = $database_conn ?: $db;
        if (!$conn) {
            return 'KNITTING_PROGRAM_ID';
        }

        $res = @mysqli_query($conn, "SHOW COLUMNS FROM knit_card");
        if ($res) {
            $fields = [];
            while ($r = mysqli_fetch_assoc($res)) {
                $fields[] = $r['Field'];
            }
            if (in_array('Knitting Program ID', $fields)) {
                $resolved_col = '`Knitting Program ID`';
                return $resolved_col;
            }
            if (in_array('KNITTING_PROGRAM_ID', $fields)) {
                $resolved_col = '`KNITTING_PROGRAM_ID`';
                return $resolved_col;
            }
            if (in_array('knitting_program_id', $fields)) {
                $resolved_col = '`knitting_program_id`';
                return $resolved_col;
            }
            if (in_array('KPTID', $fields)) {
                $resolved_col = '`KPTID`';
                return $resolved_col;
            }
        }

        $resolved_col = 'KNITTING_PROGRAM_ID';
        return $resolved_col;
    }
}

// Return database connection for use in other files
?>