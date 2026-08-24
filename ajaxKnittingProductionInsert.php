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

$lot_no = val("lot_no");

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
$customer     = val("customer");
$gray_gsm     = val("gray_gsm");
$feeder_plan  = val("feeder_plan");
$knit_material_code = val("knit_material_code");
$knit_m_des   = val("knit_m_desc") ? val("knit_m_desc") : val("knit_m_description");
$pqty         = val("pqty");

if ($pqty <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Production Qty missing."
    ]);
    exit;
}

$rollRes = mysqli_query($db, "SELECT COALESCE(MAX(CAST(ROLL AS UNSIGNED)), 3000000000) AS max_roll FROM knitting_production");
$rollRow = mysqli_fetch_assoc($rollRes);
$roll = intval($rollRow['max_roll']) + 1;

if ($roll < 3000000001) {
    $roll = 3000000001;
}

date_default_timezone_set('Asia/Dhaka');

$uid   = val("uid");
$uname = val("uname");

if ($uid === '') {
    $uid = isset($_SESSION['operator_id']) ? trim($_SESSION['operator_id']) : (isset($_SESSION['username']) ? trim($_SESSION['username']) : '');
}

if ($uname === '') {
    $uname = isset($_SESSION['operator_name']) ? trim($_SESSION['operator_name']) : (isset($_SESSION['username']) ? trim($_SESSION['username']) : '');
}

$bdHour = (int)date('G');
if ($bdHour >= 6 && $bdHour < 14) {
    $shift = 'A';
} elseif ($bdHour >= 14 && $bdHour < 22) {
    $shift = 'B';
} else {
    $shift = 'C';
}

$budat = date('Y-m-d');
$sql = "SELECT PID FROM knitting_production WHERE ROLL=?";
$stmt = mysqli_prepare($db, $sql);

mysqli_stmt_bind_param($stmt, "s", $roll);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => false,
        "message" => "Already production done. Scan another roll.",
        "duplicate" => true
    ]);
    exit;
}

mysqli_stmt_close($stmt);

 $sql = "INSERT INTO knitting_production
(
BUDAT,
ROLL,
PO_NUMBER,
PQTY,
SONO,
BUYER,
STYLE,
COLOR,
MCNO,
MC_DIA,
CUSTOMER,
SHIFT,
YARN_TYPE,
YARN_COUNT,
FABRICS_TYPE,
FINISH_GSM,
FINISH_DIA,
OPEN_TUBE,
SL_VDQ,
GRAY_GSM,
FEEDER_PLAN,
LOT_NO,
KNIT_MATERIAL_CODE,
KNIT_M_DES,
UNAME,
UID
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

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssssssssssssssssss",

    $budat,
    $roll,
    $booking,
    $pqty,
    $sono,
    $buyer,
    $style,
    $color,
    $mcno,
    $mc_dia,
    $customer,
    $shift,
    $yarn_type,
    $yarn_count,
    $fabrics_type,
    $finish_gsm,
    $finish_dia,
    $open_tube,
    $sl_vdq,
    $gray_gsm,
    $feeder_plan,
    $lot_no,
    $knit_material_code,
    $knit_m_des,
    $uname,
    $uid
);

if(!mysqli_stmt_execute($stmt)){
    echo json_encode([
        "success"=>false,
        "message"=>"Insert Failed : ".mysqli_stmt_error($stmt)
    ]);
    mysqli_stmt_close($stmt);
    exit;
}

$newPID = mysqli_insert_id($db);
mysqli_stmt_close($stmt);
ob_clean();

echo json_encode([
    "success" => true,
    "message" => "Production Saved Successfully.",
    "pid" => $newPID,
    "roll" => $roll,
    "booking" => $booking,
    "pqty" => $pqty
]);

exit;