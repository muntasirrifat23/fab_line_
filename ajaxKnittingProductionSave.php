<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

ob_start();

$host = "localhost";
$user = "root";
$pass = "pgadmin";
$db   = "knittingdb";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode([
        "success" => false,
        "message" => "Database Connection Failed : " . $conn->connect_error
    ]);
    exit;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_end_clean();
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON Received",
        "raw" => $rawData
    ]);
    exit;
}

$PROGRAM      = trim($data['SUB_TID'] ?? '');
$BOOKING_NO   = trim($data['BOOKING'] ?? '');
$SONO         = trim($data['SONO'] ?? '');
$BUYER        = trim($data['BUYER'] ?? '');
$STYLE        = trim($data['STYLE'] ?? '');
$YARN_TYPE    = trim($data['YARN_TYPE'] ?? '');
$YARN_COUNT   = trim($data['YARN_COUNT'] ?? '');
$FABRICS_TYPE = trim($data['FABRICS_TYPE'] ?? '');
$FINISH_GSM   = trim($data['FINISH_GSM'] ?? '');
$FINISH_DIA   = trim($data['FINISH_DIA'] ?? '');
$OPEN_TUBE    = trim($data['OPEN_TUBE'] ?? '');
$COLOR        = trim($data['COLOR'] ?? '');
$QTY          = trim($data['QTY'] ?? '');
$LOT_NO       = trim($data['LOT_NO'] ?? '');

if ($PROGRAM == "") {
    ob_end_clean();
    echo json_encode([
        "success" => false,
        "message" => "Program is Required"
    ]);
    exit;
}

/* Duplicate Check */

$check = $conn->prepare("SELECT PID FROM knitting_production WHERE PROGRAM=? LIMIT 1");

if (!$check) {
    ob_end_clean();
    echo json_encode([
        "success"=>false,
        "message"=>$conn->error
    ]);
    exit;
}

$check->bind_param("s",$PROGRAM);
$check->execute();
$check->store_result();

if($check->num_rows>0){

    $check->close();
    $conn->close();

    ob_end_clean();

    echo json_encode([
        "success"=>false,
        "message"=>"This Program Already Production Done."
    ]);
    exit;
}

$check->close();

/* Insert */

$sql="INSERT INTO knitting_production
(
PROGRAM,
BOOKING_NO,
SONO,
BUYER,
STYLE,
YARN_TYPE,
YARN_COUNT,
FABRICS_TYPE,
FINISH_GSM,
FINISH_DIA,
OPEN_TUBE,
COLOR,
QTY,
LOT_NO
)
VALUES
(
?,?,?,?,?,?,?,?,?,?,?,?,?,?
)";

$stmt=$conn->prepare($sql);

if(!$stmt){

    ob_end_clean();

    echo json_encode([
        "success"=>false,
        "message"=>"Prepare Failed : ".$conn->error
    ]);

    exit;
}

$stmt->bind_param(
"ssssssssssssss",
$PROGRAM,
$BOOKING_NO,
$SONO,
$BUYER,
$STYLE,
$YARN_TYPE,
$YARN_COUNT,
$FABRICS_TYPE,
$FINISH_GSM,
$FINISH_DIA,
$OPEN_TUBE,
$COLOR,
$QTY,
$LOT_NO
);

if($stmt->execute()){

    ob_end_clean();

    echo json_encode([
        "success"=>true,
        "message"=>"Production Saved Successfully.",
        "insert_id"=>$stmt->insert_id
    ]);

}else{

    ob_end_clean();

    echo json_encode([
        "success"=>false,
        "message"=>"Insert Failed : ".$stmt->error
    ]);

}

$stmt->close();
$conn->close();
exit;