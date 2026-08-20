<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($db) || !$db) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

if (isset($input['action']) && $input['action'] !== 'insert_store') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

$rack = isset($input['RACK']) ? trim($input['RACK']) : '';
$roll = isset($input['ROLL']) ? trim($input['ROLL']) : '';

if ($roll === '' || $rack === '') {
    echo json_encode(['success' => false, 'error' => 'Roll number and Rack are required']);
    exit;
}

if (!preg_match('/^[a-zA-Z]\d+$/', $rack)) {
    echo json_encode(['success' => false, 'error' => 'Invalid rack format. Use format like A3 or B2.']);
    exit;
}

$rack = strtoupper($rack);

$chk = mysqli_query($db, "SELECT KSTID FROM knitting_store WHERE ROLL = '" . mysqli_real_escape_string($db, $roll) . "' LIMIT 1");
if ($chk && mysqli_num_rows($chk) > 0) {
    echo json_encode(['success' => false, 'messager_exist' => true, 'message' => 'Data already exists for this Roll number in Knitting Store.']);
    exit;
}

function val($input, $key) {
    return isset($input[$key]) ? $input[$key] : '';
}

$budat      = val($input, 'BUDAT');

// If the scanned card's BUDAT is not today's date, insert with today's date.
$todayBudat = date('Y-m-d');
if ($budat !== $todayBudat) {
    $budat = $todayBudat;
}

$po_number  = val($input, 'PO_NUMBER');
$qty        = val($input, 'QTY');
$sono       = val($input, 'SONO');
$shift      = val($input, 'SHIFT');
$buyer      = val($input, 'BUYER');
$style      = val($input, 'STYLE');
$color      = val($input, 'COLOR');
$mcno       = val($input, 'MCNO');
$mcdia      = val($input, 'MC_DIA');
$supplier   = val($input, 'SUPPLIER');
$ytype      = val($input, 'YTYPE');
$ycount     = val($input, 'YCOUNT');
$o_t        = val($input, 'O_T');
$sl         = val($input, 'SL');
$ftype      = val($input, 'FTYPE');
$fgsm       = val($input, 'FGSM');
$fdia       = val($input, 'FDIA');
$ggsm       = val($input, 'GGSM');
$fplan      = val($input, 'FPLAN');
$lotno      = val($input, 'LOTNO');
$tpoint     = val($input, 'TPOINT');
$mcode      = val($input, 'MATERIAL_CODE');
$mdesc      = val($input, 'M_DES');

$curUser = isset($_SESSION['username']) ? trim($_SESSION['username']) : '';
$uname   = $curUser;
$uid     = $curUser;

if ($curUser !== '') {
    $esc = mysqli_real_escape_string($db, $curUser);
    $r = mysqli_query($db, "SELECT USER_ID, USER_NAME FROM users WHERE USER_ID = '$esc' OR USER_NAME = '$esc' LIMIT 1");
    if ($r && ($row = mysqli_fetch_assoc($r))) {
        $uid   = $row['USER_ID'];
        $uname = $row['USER_NAME'];
    } else {
        $r2 = mysqli_query($db, "SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator WHERE OPERATOR_ID = '$esc' LIMIT 1");
        if ($r2 && ($row2 = mysqli_fetch_assoc($r2))) {
            $uid   = $row2['OPERATOR_ID'];
            $uname = $row2['OPERATOR_NAME'];
        }
    }
}

$sql = "INSERT INTO knitting_store
        (BUDAT, RACK, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR, MCNO, MCDIA,
         SUPPLIER, YTYPE, YCOUNT, O_T, SL, FTYPE, FGSM, FDIA, GGSM, FEEDER_PLAN, LOT_NO,
         TPOINT, MCODE, MDESCRIPTION, UNAME, UID)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($db, $sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . mysqli_error($db)]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssssssssssssssssssss",
    $budat, $rack, $roll, $po_number, $qty, $sono, $shift, $buyer, $style, $color,
    $mcno, $mcdia, $supplier, $ytype, $ycount, $o_t, $sl, $ftype, $fgsm, $fdia,
    $ggsm, $fplan, $lotno, $tpoint, $mcode, $mdesc, $uname, $uid
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => "Stored successfully in Rack: $rack"]);
} else {
    echo json_encode(['success' => false, 'error' => 'Insert failed: ' . mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
?>