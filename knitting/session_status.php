<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
// Exempt specific user (tv) from auto-logout — keep session active
if (isset($_SESSION['username']) && strcasecmp($_SESSION['username'], 'tv') === 0) {
    echo json_encode(['status' => 'active', 'remaining' => 'never']);
    exit;
}

if (isset($_SESSION['expire_time'])) {
    $expire = intval($_SESSION['expire_time']);
    $now = time();
    if ($now > $expire) {
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['status' => 'expired']);
        exit;
    } else {
        echo json_encode(['status' => 'active', 'expire_time' => $expire, 'remaining' => $expire - $now]);
        exit;
    }
} else {
    echo json_encode(['status' => 'no_session']);
    exit;
}
