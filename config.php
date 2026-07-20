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
$inject_script = '<script src="auto_logout.js"></script>';
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    if (!headers_sent()) {
        ob_start(function ($buffer) use ($inject_script) {
            $pos = stripos($buffer, '</body>');
            if ($pos !== false) {
                return substr_replace($buffer, $inject_script . '</body>', $pos, 7);
            }
            return $buffer . $inject_script;
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

// Return database connection for use in other files
?>