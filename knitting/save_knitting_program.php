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

$booking            = trim($data['BOOKING'] ?? '');
$sono               = trim($data['SONO'] ?? '');
$style              = trim($data['STYLE'] ?? '');
$buyer              = trim($data['BUYER'] ?? '');
$supplier           = trim($data['SUPPLIER'] ?? '');
$knit_m_description = trim($data['KNIT_M_DESCRIPTION'] ?? '');
$mcno               = trim($data['MCNO'] ?? '');
$qty                = floatval($data['QTY'] ?? 0);
$shift              = trim($data['SHIFT'] ?? 'A-SHIFT');
$yarn_type          = trim($data['YARN_TYPE'] ?? '');
$yarn_count         = trim($data['YARN_COUNT'] ?? '');
$fabrics_type       = trim($data['FABRICS_TYPE'] ?? '');
$finish_gsm         = trim($data['FINISH_GSM'] ?? '');
$finish_dia         = trim($data['FINISH_DIA'] ?? '');
$open_tube          = trim($data['OPEN_TUBE'] ?? 'O');
$lot_no             = trim($data['LOT_NO'] ?? '');
$knit_material_code = trim($data['KNIT_MATERIAL_CODE'] ?? '');
$operator_id        = trim($data['OPERATOR_ID'] ?? '');
$main_tid           = trim($data['MAIN_TID'] ?? '');
$sub_tid            = trim($data['SUB_TID'] ?? '');

// Server-side Validation
$errors = [];
if (empty($booking)) {
    $errors[] = "BOOKING number is required.";
}
if (empty($mcno)) {
    $errors[] = "Machine No (MCNO) is required.";
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
    $main_tid = time();
}
if (empty($sub_tid)) {
    $sub_tid = time() . rand(10, 99);
}

try {
    if ($is_edit) {
        $sql = "UPDATE knitting_program SET 
            MAIN_TID = ?, 
            SUB_TID = ?, 
            BOOKING = ?, 
            SONO = ?, 
            STYLE = ?, 
            BUYER = ?, 
            SUPPLIER = ?, 
            KNIT_M_DESCRIPTION = ?, 
            MCNO = ?, 
            QTY = ?, 
            SHIFT = ?, 
            YARN_TYPE = ?, 
            YARN_COUNT = ?, 
            FABRICS_TYPE = ?, 
            FINISH_GSM = ?, 
            FINISH_DIA = ?, 
            OPEN_TUBE = ?, 
            LOT_NO = ?, 
            KNIT_MATERIAL_CODE = ? 
            WHERE KPTID = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            "sssssssssdsssssssssi",
            $main_tid, $sub_tid, $booking, $sono, $style, $buyer, $supplier,
            $knit_m_description, $mcno, $qty, $shift, $yarn_type, $yarn_count,
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
            MAIN_TID, SUB_TID, BOOKING, SONO, STYLE, BUYER, SUPPLIER, 
            KNIT_M_DESCRIPTION, MCNO, QTY, SHIFT, YARN_TYPE, YARN_COUNT, 
            FABRICS_TYPE, FINISH_GSM, FINISH_DIA, OPEN_TUBE, LOT_NO, KNIT_MATERIAL_CODE
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            "sssssssssdsssssssss",
            $main_tid, $sub_tid, $booking, $sono, $style, $buyer, $supplier,
            $knit_m_description, $mcno, $qty, $shift, $yarn_type, $yarn_count,
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
