<?php
include 'config.php';

header('Content-Type: application/json');

if (isset($_POST['submit'])) {

    $username = trim(mysqli_real_escape_string($db, $_POST['username']));
    $email = trim(mysqli_real_escape_string($db, $_POST['email']));
    // For auto-created accounts we only require username (email optional).
    if (empty($email)) {
        $email = "default@mail.com";
    }

    // 🔴 required check (only username)
    if (empty($username)) {
        echo json_encode(["status" => "error", "message" => "User ID is required"]);
        exit;
    }

    // username length
    if (strlen($username) > 10) {
        echo json_encode(["status" => "error", "message" => "Username max 10 characters"]);
        exit;
    }

    // 🔴 CHECK USERNAME EXISTS
    $checkUser = mysqli_query($db, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($checkUser) > 0) {
        echo json_encode(["status" => "error", "message" => "User ID already exists"]);
        exit;
    }

    // 🔴 CHECK EMAIL EXISTS
    $checkEmail = mysqli_query($db, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($checkEmail) > 0) {
        echo json_encode(["status" => "error", "message" => "Email already exists"]);
        exit;
    }


    // insert with auto password '123'
    $autoPassword = '123';
    $hashedPassword = md5(trim($autoPassword));

    $sql = "INSERT INTO users (username, email, password) 
            VALUES ('$username', '$email', '$hashedPassword')";

    if (mysqli_query($db, $sql)) {
        echo json_encode(["status" => "success", "message" => "User Created Successfully (Password: 123)"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database Error"]);
    }

    exit;
}
?>