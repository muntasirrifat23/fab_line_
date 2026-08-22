<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit();
}

// Receive input data (Support both $_POST and JSON body)
$rawInput = file_get_contents('php://input');
$jsonBody = json_decode($rawInput, true);
$data = is_array($jsonBody) ? array_merge($_POST, $jsonBody) : $_POST;

$kptid              = isset($data['KPTID']) ? intval($data['KPTID']) : 0;
$is_edit            = ($kptid > 0);

$booking            = trim($data['PO_NUMBER'] ?? $data['BOOKING'] ?? '');
$sono               = trim($data['SONO'] ?? '');
$style              = trim($data['STYLE'] ?? '');
$buyer              = trim($data['BUYER'] ?? '');
$customer           = trim($data['CUSTOMER'] ?? '');
$knit_m_description = trim($data['KNIT_M_DESCRIPTION'] ?? '');
$qty                = floatval($data['QTY'] ?? 0);
$yarn_type          = trim($data['YARN_TYPE'] ?? '');
$yarn_count         = trim($data['YARN_COUNT'] ?? '');
$fabrics_type       = trim($data['FABRICS_TYPE'] ?? '');
$finish_gsm         = trim($data['FINISH_GSM'] ?? '');
$finish_dia         = trim($data['FINISH_DIA'] ?? '');
$open_tube          = trim($data['OPEN_TUBE'] ?? 'O');
$lot_no             = trim($data['LOT_NO'] ?? '');
$knit_material_code = trim($data['KNIT_MATERIAL_CODE'] ?? '');
$color              = trim($data['COLOR'] ?? '');
$sl_vdq             = trim($data['SL_VDQ'] ?? '');
$operator_id        = trim($data['OPERATOR_ID'] ?? '');
$main_tid           = trim($data['MAIN_TID'] ?? '');
$sub_tid            = trim($data['SUB_TID'] ?? '');

// Auto-detect SHIFT based on current server time
$shiftHour = (int)date('G');
if ($shiftHour >= 6 && $shiftHour < 14) {
    $shift = 'A';
} elseif ($shiftHour >= 14 && $shiftHour < 22) {
    $shift = 'B';
} else {
    $shift = 'C';
}

// Server-side Validation
$errors = [];
if (empty($booking)) {
    $errors[] = "PO Number is required.";
}
if ($qty <= 0) {
    $errors[] = "QTY must be greater than 0.";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Generate MAIN_TID and SUB_TID for new entries if missing
if (empty($main_tid)) {
    $maxRow = mysqli_fetch_assoc(mysqli_query($db, 'SELECT MAX(MAIN_TID) AS max_main FROM knitting_program'));
    $main_tid = intval($maxRow['max_main']) + 1;
    if ($main_tid < 1000000001) {
        $main_tid = 1000000001;
    }
}
if (empty($sub_tid)) {
    $maxRow = mysqli_fetch_assoc(mysqli_query($db, 'SELECT MAX(SUB_TID) AS max_sub FROM knitting_program'));
    $sub_tid = intval($maxRow['max_sub']) + 1;
    if ($sub_tid < 2000000001) {
        $sub_tid = 2000000001;
    }
}

try {
    if ($is_edit) {
        $sql = "UPDATE knitting_program SET 
            MAIN_TID = ?, 
            SUB_TID = ?, 
            PO_NUMBER = ?, 
            SONO = ?, 
            STYLE = ?, 
            BUYER = ?, 
            CUSTOMER = ?, 
            KNIT_M_DESCRIPTION = ?, 
            QTY = ?, 
            SHIFT = ?, 
            YTYPE = ?, 
            YCOUNT = ?, 
            FTYPE = ?, 
            FGSM = ?, 
            FDIA = ?, 
            O_T = ?, 
            LOT = ?, 
            KNIT_MATERIAL_CODE = ? 
            WHERE KPTID = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            "sssssssssdssssssssi",
            $main_tid, $sub_tid, $booking, $sono, $style, $buyer, $customer,
            $knit_m_description, $qty, $shift, $yarn_type, $yarn_count,
            $fabrics_type, $finish_gsm, $finish_dia, $open_tube, $lot_no, $knit_material_code, $kptid
        );

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Knitting program updated successfully!',
                'KPTID' => $kptid,
                'MAIN_TID' => $main_tid,
                'SUB_TID' => $sub_tid
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        $sql = "INSERT INTO knitting_program (
            MAIN_TID, SUB_TID, PO_NUMBER, SONO, STYLE, BUYER, CUSTOMER, 
            KNIT_M_DESCRIPTION, QTY, SHIFT, YTYPE, YCOUNT, 
            FTYPE, FGSM, FDIA, O_T, LOT, KNIT_MATERIAL_CODE
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            "sssssssssdssssssss",
            $main_tid, $sub_tid, $booking, $sono, $style, $buyer, $customer,
            $knit_m_description, $qty, $shift, $yarn_type, $yarn_count,
            $fabrics_type, $finish_gsm, $finish_dia, $open_tube, $lot_no, $knit_material_code
        );

        if ($stmt->execute()) {
            $inserted_id = $db->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'New Knitting Program inserted successfully!',
                'KPTID' => $inserted_id,
                'MAIN_TID' => $main_tid,
                'SUB_TID' => $sub_tid
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$db->close();
?>
