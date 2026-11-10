<?php 
session_start();
include('config.php');

$username = "";
$errors = array(); 

// LOGIN USER
if (isset($_POST['login_user'])) {
    $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username)) {
        echo "Username is required";
        exit();
    }
    if (empty($password)) {
        echo "Password is required";
        exit();
    }
    
    // First, check in users table (regular users) – login by USER_ID
    $escapedUsername = mysqli_real_escape_string($db, $username);
    $password_md5 = md5($password);
    $query = "SELECT * FROM users WHERE USER_ID='$escapedUsername' AND password='$password_md5'";
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
$query_operator = "SELECT * FROM knitting_operator WHERE OPERATOR_ID='$escapedUsername' AND OPERATOR_PASSWORD='$password_md5'";
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

    // Login using KNITTING_QC_ID
    $query_qc = "SELECT * FROM knitting_operator_qc WHERE KNITTING_QC_ID='$escapedUsername' AND KNITTING_QC_PASSWORD='$password_md5'";
    $results_qc = mysqli_query($db, $query_qc);

    if (mysqli_num_rows($results_qc) == 1) {
        $qc_data = mysqli_fetch_assoc($results_qc);

        $_SESSION['username'] = $qc_data['KNITTING_QC_ID'];
        $_SESSION['user_name'] = $qc_data['KNITTING_QC_NAME'];
        $_SESSION['user_type'] = 'qc';
        $_SESSION['knitting_qc_id'] = $qc_data['KNITTING_QC_ID'];
        $_SESSION['operator_name'] = $qc_data['KNITTING_QC_NAME'];
        $_SESSION['success'] = "You are now logged in as knitting QC";
        $_SESSION['expire_time'] = time() + 60000;

        echo "OK";
        exit();
    }

    // Login using DY_OP_ID from the dyeing_operator table.
    $dyeingQuery = $db->prepare(
        'SELECT DY_OP_ID, DY_OP_NAME
         FROM dyeing_operator
         WHERE DY_OP_ID = ? AND DY_OP_PASSWORD = ?
         LIMIT 1'
    );
    if (!$dyeingQuery) {
        error_log('Could not prepare dyeing operator login query: ' . $db->error);
        http_response_code(500);
        echo 'SERVER_ERROR';
        exit();
    }

    $dyeingPassword = md5($password);
    $dyeingQuery->bind_param('ss', $username, $dyeingPassword);
    if (!$dyeingQuery->execute()) {
        error_log('Could not execute dyeing operator login query: ' . $dyeingQuery->error);
        $dyeingQuery->close();
        http_response_code(500);
        echo 'SERVER_ERROR';
        exit();
    }

    $dyeingResults = $dyeingQuery->get_result();
    if ($dyeingResults && $dyeingResults->num_rows === 1) {
        $dyeingData = $dyeingResults->fetch_assoc();
        $_SESSION['username'] = $dyeingData['DY_OP_ID'];
        $_SESSION['user_name'] = $dyeingData['DY_OP_NAME'] ?? $dyeingData['DY_OP_ID'];
        $_SESSION['user_type'] = 'dyeing_operator';
        $_SESSION['dyeing_operator_id'] = $dyeingData['DY_OP_ID'];
        $_SESSION['success'] = 'You are now logged in as dyeing operator';
        $_SESSION['expire_time'] = time() + 60000;
        $dyeingQuery->close();
        echo 'OK';
        exit();
    }
    $dyeingQuery->close();

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