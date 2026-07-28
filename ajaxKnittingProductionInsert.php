<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();

require_once "config.php";

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);
    exit;
}

mysqli_set_charset($db, "utf8");

$raw=file_get_contents("php://input");

$data=json_decode($raw,true);

if(!is_array($data)){
    $data=$_POST;
}

$data=array_change_key_case((array)$data,CASE_LOWER);

function val($name){
    global $data;
    return trim($data[$name] ?? "");
}
//========================
// GET DATA
//========================

$roll = val("sub_tid");      // QR Roll No
$lot_no = val("lot_no");

if ($roll == "") {
    $roll = $lot_no;
}

if ($roll == "") {
    echo json_encode([
        "success" => false,
        "message" => "Roll No not found."
    ]);
    exit;
}

$booking      = val("booking");
$sono         = val("sono");
$mcno         = val("mcno");
$mc_dia       = val("mc_dia");
$buyer        = val("buyer");
$style        = val("style");
$yarn_type    = val("yarn_type");
$yarn_count   = val("yarn_count");
$fabrics_type = val("fabrics_type");
$finish_gsm   = val("finish_gsm");
$finish_dia   = val("finish_dia");
$open_tube    = val("open_tube");
$color        = val("color");
$sl_vdq       = val("sl_vdq");

$oqty         = val("oqty");
$rqty         = val("rqty");
$uqty         = val("uqty");

if ($oqty <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Original Qty missing."
    ]);
    exit;
}

if ($rqty < 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid Reject Qty."
    ]);
    exit;
}

if ($rqty > $oqty) {
    echo json_encode([
        "success" => false,
        "message" => "Reject Qty cannot be greater than Original Qty."
    ]);
    exit;
}

//========================
// DUPLICATE CHECK
//========================

$sql = "SELECT PID FROM knitting_production WHERE ROLL=?";

$stmt = mysqli_prepare($db, $sql);

mysqli_stmt_bind_param($stmt, "s", $roll);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => false,
        "message" => "Already production done.",
        "duplicate" => true
    ]);

    exit;
}

mysqli_stmt_close($stmt);


 $sql = "INSERT INTO knitting_production
(
BUDAT,
ROLL,
BOOKING_NO,
SONO,
MCNO,
MC_DIA,
BUYER,
STYLE,
YARN_TYPE,
YARN_COUNT,
FABRICS_TYPE,
FINISH_GSM,
FINISH_DIA,
OPEN_TUBE,
COLOR,
SL_VDQ,
OQTY,
RQTY,
UQTY,
LOT_NO
)
VALUES
(
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?
)";

$stmt = mysqli_prepare($db,$sql);

if(!$stmt){

    echo json_encode([
        "success"=>false,
        "message"=>mysqli_error($db)
    ]);

    exit;
}

$budat=date("Y-m-d");

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssssssssssss",

    $budat,
    $roll,
    $booking,
    $sono,
    $mcno,
    $mc_dia,
    $buyer,
    $style,
    $yarn_type,
    $yarn_count,
    $fabrics_type,
    $finish_gsm,
    $finish_dia,
    $open_tube,
    $color,
    $sl_vdq,
    $oqty,
    $rqty,
    $uqty,
    $lot_no
);

if(!mysqli_stmt_execute($stmt)){

    echo json_encode([
        "success"=>false,
        "message"=>"Insert Failed : ".mysqli_stmt_error($stmt)
    ]);

    mysqli_stmt_close($stmt);

    exit;
}
//========================
// SUCCESS
//========================

$newPID = mysqli_insert_id($db);

mysqli_stmt_close($stmt);
ob_clean();

echo json_encode([
    "success" => true,
    "message" => "Production Saved Successfully.",
    "pid" => $newPID,
    "roll" => $roll,
    "booking" => $booking,
    "oqty" => $oqty,
    "rqty" => $rqty,
    "uqty" => $uqty
]);

exit;