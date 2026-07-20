<?php
include 'config.php';
header('Content-Type: application/json');

if(isset($_POST['submit'])){
    $username = trim(mysqli_real_escape_string($db, $_POST['username']));

    if(empty($username)){
        echo json_encode(["status"=>"error", "message"=>"User ID required"]);
        exit;
    }

    $checkUser = mysqli_query($db, "SELECT id FROM users WHERE username='$username'");
    if(mysqli_num_rows($checkUser) == 0){
        echo json_encode(["status"=>"error", "message"=>"User ID not found"]);
        exit;
    }

    $newPassword = md5("123"); // reset password to 123

    $update = mysqli_query($db, "UPDATE users SET password='$newPassword' WHERE username='$username'");
    if($update){
        echo json_encode(["status"=>"success", "message"=>"Password reset to '123' successfully"]);
    } else {
        echo json_encode(["status"=>"error", "message"=>"Database Error"]);
    }

    exit;
}
?>