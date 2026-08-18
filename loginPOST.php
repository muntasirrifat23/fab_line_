<?php 
session_start();
include('config.php');

$username = "";
$errors = array(); 

// LOGIN USER
if (isset($_POST['login_user'])) {
    $username = mysqli_real_escape_string($db, $_POST['username']);
    $password = mysqli_real_escape_string($db, $_POST['password']);
    
    if (empty($username)) {
        echo "Username is required";
        exit();
    }
    if (empty($password)) {
        echo "Password is required";
        exit();
    }
    
    // First, check in users table (regular users) – login by USER_ID
    $password_md5 = md5($password);
    $query = "SELECT * FROM users WHERE USER_ID='$username' AND password='$password_md5'";
    $results = mysqli_query($db, $query);
    
    if (mysqli_num_rows($results) == 1) {
        $user_data = mysqli_fetch_assoc($results);
        $_SESSION['username'] = $user_data['USER_ID'];
        $_SESSION['user_name'] = $user_data['USER_NAME'] ?? $user_data['USER_ID'];
        $_SESSION['user_id'] = $user_data['USER_ID'];
        $_SESSION['user_type'] = 'user';
        $_SESSION['user_role'] = $user_data['role'] ?? 'user';
        $_SESSION['success'] = "You are now logged in";
        $_SESSION['expire_time'] = time() + 60000;
        echo 'OK';
        exit();
    }
    
   // Login using only OPERATOR_ID
$query_operator = "SELECT * FROM knitting_operator WHERE OPERATOR_ID='$username'";
$results_operator = mysqli_query($db, $query_operator);

if (mysqli_num_rows($results_operator) == 1) {
    $operator_data = mysqli_fetch_assoc($results_operator);

    $_SESSION['username'] = $operator_data['OPERATOR_ID'];
    $_SESSION['user_type'] = 'operator';
    $_SESSION['operator_id'] = $operator_data['OPERATOR_ID'];
    $_SESSION['operator_name'] = $operator_data['OPERATOR_NAME'] ?? '';
    $_SESSION['line_no'] = $operator_data['LINE_NO'] ?? '';
    $_SESSION['shift'] = $operator_data['SHIFT'] ?? '';
    $_SESSION['success'] = "You are now logged in as operator";
    $_SESSION['expire_time'] = time() + 60000;

    echo "OK";
    exit();
}
    
    // If no match found in either table
    echo 'NOTOK';
    exit();
}

// If user is already logged in
if (isset($_SESSION['username'])) {
    echo 'OK';
    exit();
}

mysqli_close($db);
?>