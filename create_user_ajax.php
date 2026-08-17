<?php
include 'config.php';

header('Content-Type: application/json');

if (isset($_POST['submit'])) {

    $userType = isset($_POST['user_type']) ? trim($_POST['user_type']) : 'users';
    $autoPassword = '123';

    // ====== USERS TABLE ======
    if ($userType === 'users') {

        $username = trim(mysqli_real_escape_string($db, $_POST['username']));
        $email = trim(mysqli_real_escape_string($db, $_POST['email']));
        // For auto-created accounts we only require username (email optional).
        if (empty($email)) {
            $email = "default@mail.com";
        }

        // required check (only username)
        if (empty($username)) {
            echo json_encode(["status" => "error", "message" => "User ID is required"]);
            exit;
        }

        // username length
        if (strlen($username) > 10) {
            echo json_encode(["status" => "error", "message" => "Username max 10 characters"]);
            exit;
        }

        // CHECK USERNAME EXISTS
        $checkUser = mysqli_query($db, "SELECT id FROM users WHERE username='$username'");
        if (mysqli_num_rows($checkUser) > 0) {
            echo json_encode(["status" => "error", "message" => "User ID already exists"]);
            exit;
        }

        // CHECK EMAIL EXISTS
        $checkEmail = mysqli_query($db, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($checkEmail) > 0) {
            echo json_encode(["status" => "error", "message" => "Email already exists"]);
            exit;
        }

        // insert with auto password '123'
        $hashedPassword = md5(trim($autoPassword));

        $sql = "INSERT INTO users (username, email, password) 
                VALUES ('$username', '$email', '$hashedPassword')";

        if (mysqli_query($db, $sql)) {
            echo json_encode(["status" => "success", "message" => "User Created Successfully (Password: 123)"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database Error: " . mysqli_error($db)]);
        }

        exit;
    }

    // ====== KNITTING OPERATOR TABLE ======
    if ($userType === 'operator') {

        $operatorName = trim(mysqli_real_escape_string($db, isset($_POST['operator_name']) ? $_POST['operator_name'] : ''));
        $email = trim(mysqli_real_escape_string($db, isset($_POST['operator_email']) ? $_POST['operator_email'] : ''));

        if (empty($operatorName)) {
            echo json_encode(["status" => "error", "message" => "Operator Name is required"]);
            exit;
        }

        if (empty($email)) {
            echo json_encode(["status" => "error", "message" => "Operator Email is required"]);
            exit;
        }

        // OPERATOR_ID = email prefix (before @)
        $operatorId = strtolower(trim(strtok($email, '@')));
        if (empty($operatorId)) {
            echo json_encode(["status" => "error", "message" => "Invalid Operator Email"]);
            exit;
        }
        if (strlen($operatorId) > 20) {
            $operatorId = substr($operatorId, 0, 20);
        }

        // CHECK OPERATOR_ID EXISTS
        $checkId = mysqli_query($db, "SELECT KOTID FROM knitting_operator WHERE OPERATOR_ID='$operatorId'");
        if (mysqli_num_rows($checkId) > 0) {
            echo json_encode(["status" => "error", "message" => "Operator ID already exists ($operatorId)"]);
            exit;
        }

        // CHECK OPERATOR_EMAIL EXISTS
        $checkEmail = mysqli_query($db, "SELECT KOTID FROM knitting_operator WHERE OPERATOR_EMAIL='$email'");
        if (mysqli_num_rows($checkEmail) > 0) {
            echo json_encode(["status" => "error", "message" => "Operator Email already exists"]);
            exit;
        }

        $hashedPassword = md5(trim($autoPassword));

        $sql = "INSERT INTO knitting_operator (OPERATOR_ID, OPERATOR_NAME, OPERATOR_EMAIL, OPERATOR_PASSWORD, CREATED) 
                VALUES ('$operatorId', '$operatorName', '$email', '$hashedPassword', NOW())";

        if (mysqli_query($db, $sql)) {
            echo json_encode(["status" => "success", "message" => "Operator Created Successfully (ID: $operatorId, Password: 123)"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database Error: " . mysqli_error($db)]);
        }

        exit;
    }

    echo json_encode(["status" => "error", "message" => "Invalid user type"]);
    exit;
}
?>
