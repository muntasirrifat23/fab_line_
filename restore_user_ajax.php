<?php
include 'config.php';
header('Content-Type: application/json');

if(isset($_POST['submit'])){
    $username = trim(mysqli_real_escape_string($db, $_POST['username']));

    if(empty($username)){
        echo json_encode(["status"=>"error", "message"=>"User ID required"]);
        exit;
    }

    $newPassword = md5("123"); // reset password to 123

    // 1) users table
    $checkUser = mysqli_query($db, "SELECT id FROM users WHERE USER_ID='$username'");
    if(mysqli_num_rows($checkUser) > 0){
        $update = mysqli_query($db, "UPDATE users SET password='$newPassword' WHERE USER_ID='$username'");
        if($update){
            echo json_encode(["status"=>"success", "message"=>"Password reset to '123' successfully"]);
        } else {
            echo json_encode(["status"=>"error", "message"=>"Database Error"]);
        }
        exit;
    }

    // 2) knitting_operator table (OPERATOR_ID)
    $checkOperator = mysqli_query($db, "SELECT KOTID FROM knitting_operator WHERE OPERATOR_ID='$username'");
    if(mysqli_num_rows($checkOperator) > 0){
        $update = mysqli_query($db, "UPDATE knitting_operator SET OPERATOR_PASSWORD='$newPassword' WHERE OPERATOR_ID='$username'");
        if($update){
            echo json_encode(["status"=>"success", "message"=>"Operator password reset to '123' successfully"]);
        } else {
            echo json_encode(["status"=>"error", "message"=>"Database Error"]);
        }
        exit;
    }

    // 3) knitting_operator_qc table (KNITTING_QC_ID)
    $checkQc = mysqli_query($db, "SELECT KQCTID FROM knitting_operator_qc WHERE KNITTING_QC_ID='$username'");
    if(mysqli_num_rows($checkQc) > 0){
        $update = mysqli_query($db, "UPDATE knitting_operator_qc SET KNITTING_QC_PASSWORD='$newPassword' WHERE KNITTING_QC_ID='$username'");
        if($update){
            echo json_encode(["status"=>"success", "message"=>"QC password reset to '123' successfully"]);
        } else {
            echo json_encode(["status"=>"error", "message"=>"Database Error"]);
        }
        exit;
    }

    echo json_encode(["status"=>"error", "message"=>"User ID not found"]);
    exit;
}
?>