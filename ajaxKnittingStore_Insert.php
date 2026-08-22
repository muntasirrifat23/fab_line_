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

$rackNo = isset($input['RACKNO']) ? trim($input['RACKNO']) : '';
$rackLocation = isset($input['RACKLOCATION']) ? strtoupper(trim($input['RACKLOCATION'])) : '';
$rack = isset($input['RACK']) ? trim($input['RACK']) : '';
$roll = isset($input['ROLL']) ? trim($input['ROLL']) : '';

if ($roll === '') {
    echo json_encode(['success' => false, 'error' => 'Roll number is required']);
    exit;
}

if ($rackNo !== '' || $rackLocation !== '') {
    if (!preg_match('/^(?:0?[1-9]|[1-4][0-9]|50)$/', $rackNo)) {
        echo json_encode(['success' => false, 'error' => 'Invalid Rack Number. Use 01 to 50.']);
        exit;
    }
    if (!preg_match('/^[A-C][1-3]$/', $rackLocation)) {
        echo json_encode(['success' => false, 'error' => 'Invalid Rack Location. Use A1, A2, A3, B1, B2, B3, C1, C2 or C3.']);
        exit;
    }
    $rackNo = str_pad((string) ((int) $rackNo), 2, '0', STR_PAD_LEFT);
    $rack = $rackNo . $rackLocation;
} elseif ($rack !== '') {
    $rack = strtoupper($rack);
    if (!preg_match('/^(?:0?[1-9]|[1-4][0-9]|50)[A-C][1-3]$/', $rack)) {
        echo json_encode(['success' => false, 'error' => 'Invalid rack format. Use 01A1 to 50C3.']);
        exit;
    }
    preg_match('/^(\d+)([A-C][1-3])$/', $rack, $rackParts);
    $rackNo = str_pad((string) ((int) $rackParts[1]), 2, '0', STR_PAD_LEFT);
    $rackLocation = $rackParts[2];
} else {
    echo json_encode(['success' => false, 'error' => 'Rack Number and Rack Location are required']);
    exit;
}

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
$customer   = val($input, 'CUSTOMER');
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

$columns = mysqli_query($db, "SHOW COLUMNS FROM knitting_store");
$existingColumns = [];
if ($columns) {
    while ($column = mysqli_fetch_assoc($columns)) {
        $existingColumns[] = strtoupper($column['Field']);
    }
}
if (!in_array('RACKNO', $existingColumns, true)) {
    mysqli_query($db, "ALTER TABLE knitting_store ADD COLUMN RACKNO VARCHAR(50) DEFAULT NULL");
}
if (!in_array('RACKLOCATION', $existingColumns, true)) {
    mysqli_query($db, "ALTER TABLE knitting_store ADD COLUMN RACKLOCATION VARCHAR(100) DEFAULT NULL");
}

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
        (BUDAT, RACKNO, RACKLOCATION, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR, MCNO, MCDIA,
         CUSTOMER, YTYPE, YCOUNT, O_T, SL, FTYPE, FGSM, FDIA, GGSM, FEEDER_PLAN, LOT_NO,
         TPOINT, MCODE, MDESCRIPTION, UNAME, UID)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

try {
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($db));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssssssssssssssssssssssssss",
        $budat, $rackNo, $rackLocation, $roll, $po_number, $qty, $sono, $shift, $buyer, $style, $color,
        $mcno, $mcdia, $customer, $ytype, $ycount, $o_t, $sl, $ftype, $fgsm, $fdia,
        $ggsm, $fplan, $lotno, $tpoint, $mcode, $mdesc, $uname, $uid
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    echo json_encode(['success' => true, 'message' => "Stored successfully - Rack No: $rackNo, Location: $rackLocation"]);
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Insert failed: ' . $e->getMessage()]);
}

exit;
?>