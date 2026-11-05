<?php
// ajaxDyeing_batch_card_Insert.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

if (!isset($_SESSION['dyeing_batch']) || !is_array($_SESSION['dyeing_batch'])) {
    $_SESSION['dyeing_batch'] = [];
}
if (!isset($_SESSION['dyeing_batch']['rolls']) || !is_array($_SESSION['dyeing_batch']['rolls'])) {
    $_SESSION['dyeing_batch']['rolls'] = [];
}

$rolls = array_values($_SESSION['dyeing_batch']['rolls']);

if (count($rolls) === 0) {
    echo json_encode(['success' => false, 'message' => 'Select at least one roll to create a batch card']);
    exit;
}

foreach ([
    'YBRAND' => "VARCHAR(100) DEFAULT NULL",
    'CATEGORY' => "VARCHAR(100) DEFAULT NULL"
] as $column => $definition) {
    $columnResult = $db->query("SHOW COLUMNS FROM dyeing_batch_card LIKE '$column'");
    if (!$columnResult) {
        echo json_encode(['success' => false, 'message' => 'Could not check batch card column ' . $column . ': ' . $db->error]);
        exit;
    }
    if ($columnResult->num_rows === 0 && !$db->query("ALTER TABLE dyeing_batch_card ADD COLUMN `$column` $definition")) {
        echo json_encode(['success' => false, 'message' => 'Could not add batch card column ' . $column . ': ' . $db->error]);
        exit;
    }
}

// Assign next card number from DB
$r = mysqli_query($db, "SELECT COALESCE(MAX(CAST(BCARD AS UNSIGNED)), 4000000000) + 1 AS nxt FROM dyeing_batch_card");
if (!$r) {
    echo json_encode(['success' => false, 'message' => 'Could not generate the next batch card number: ' . $db->error]);
    exit;
}
$rowX = $r ? mysqli_fetch_assoc($r) : null;
if (!$rowX || !isset($rowX['nxt'])) {
    echo json_encode(['success' => false, 'message' => 'Could not generate the next batch card number']);
    exit;
}
$seq = (int)$rowX['nxt'];
$cardNo = (string)$seq;

$uname = $_SESSION['username'];
$budat = date('Y-m-d');

$inserted = 0;
$errorMsg = '';
$db->autocommit(false);

foreach ($rolls as $row) {
    $stmt = $db->prepare(
        "INSERT INTO dyeing_batch_card
         (BUDAT, BCARD, ROLL, PO_NUMBER, RACK, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
          MCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
          YTYPE, YCOUNT, YBRAND, CATEGORY, TPOINT, UNAME)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        $errorMsg = 'Database prepare error: ' . $db->error;
        break;
    }

    $vPO_NUMBER = isset($row['PO_NUMBER']) ? $row['PO_NUMBER'] : null;
    $vRACK = isset($row['RACK']) ? $row['RACK'] : null;
    $vQTY = isset($row['QTY']) ? $row['QTY'] : null;
    $vSONO = isset($row['SONO']) ? $row['SONO'] : null;
    $vSHIFT = isset($row['SHIFT']) ? $row['SHIFT'] : null;
    $vBUYER = isset($row['BUYER']) ? $row['BUYER'] : null;
    $vSTYLE = isset($row['STYLE']) ? $row['STYLE'] : null;
    $vCOLOR = isset($row['COLOR']) ? $row['COLOR'] : null;
    $vMCNO = isset($row['MCNO']) ? $row['MCNO'] : null;
    $vMCDIA = isset($row['MCDIA']) ? $row['MCDIA'] : null;
    $vCUSTOMER = isset($row['CUSTOMER']) ? $row['CUSTOMER'] : null;
    $vYTYPE = isset($row['YTYPE']) ? $row['YTYPE'] : null;
    $vYCOUNT = isset($row['YCOUNT']) ? $row['YCOUNT'] : null;
    $vO_T = isset($row['O_T']) ? $row['O_T'] : null;
    $vSL = isset($row['SL']) ? $row['SL'] : null;
    $vFTYPE = isset($row['FTYPE']) ? $row['FTYPE'] : null;
    $vFGSM = isset($row['FGSM']) ? $row['FGSM'] : null;
    $vFDIA = isset($row['FDIA']) ? $row['FDIA'] : null;
    $vFEEDER_PLAN = isset($row['FEEDER_PLAN']) ? $row['FEEDER_PLAN'] : null;
    $vLOT_NO = isset($row['LOT_NO']) ? $row['LOT_NO'] : null;
    $vYBRAND = isset($row['YBRAND']) ? $row['YBRAND'] : null;
    $vCATEGORY = isset($row['CATEGORY']) ? $row['CATEGORY'] : null;
    $vTPOINT = isset($row['TPOINT']) ? $row['TPOINT'] : null;
    $vROLL = isset($row['ROLL']) ? $row['ROLL'] : '';

    $stmt->bind_param(
        'sssssssssssssssssssssssssss',
        $budat,
        $cardNo,
        $vROLL,
        $vPO_NUMBER,
        $vRACK,
        $vQTY,
        $vSONO,
        $vSHIFT,
        $vBUYER,
        $vSTYLE,
        $vCOLOR,
        $vMCNO,
        $vMCDIA,
        $vCUSTOMER,
        $vO_T,
        $vSL,
        $vFTYPE,
        $vFGSM,
        $vFDIA,
        $vFEEDER_PLAN,
        $vLOT_NO,
        $vYTYPE,
        $vYCOUNT,
        $vYBRAND,
        $vCATEGORY,
        $vTPOINT,
        $uname
    );

    if (!$stmt->execute()) {
        $errorMsg = 'Insert error for roll ' . $row['ROLL'] . ': ' . $stmt->error;
        $stmt->close();
        break;
    }
    $stmt->close();
    $inserted++;
}

if ($inserted === count($rolls)) {
    if (!$db->commit()) {
        $errorMsg = 'Database commit failed: ' . $db->error;
        $db->rollback();
        $db->autocommit(true);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    unset($_SESSION['dyeing_batch']['rolls']);
    unset($_SESSION['dyeing_batch']['card_no']);
    unset($_SESSION['dyeing_batch']['created_at']);
    unset($_SESSION['dyeing_batch']['target_qty']);
    $_SESSION['dyeing_batch']['seq'] = $seq + 1;

    echo json_encode([
        'success' => true,
        'card_no' => $cardNo,
        'next_card_no' => $seq + 1,
        'count' => $inserted,
        'message' => 'Batch Card created with ' . $inserted . ' roll(s).'
    ]);
} else {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $errorMsg !== '' ? $errorMsg : 'Database error during batch card insert']);
}

$db->autocommit(true);
exit;
?>
