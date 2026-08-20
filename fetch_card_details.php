<?php
// fetch_card_details.php - Dedicated API Endpoint for Dynamic Full-Data Knit Card Fetching
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

$query = trim($_GET['query'] ?? $_GET['id'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Card ID or Barcode parameter is missing.']);
    exit();
}

// Extract numeric ID if query is formatted like "KC-8", "KCID-8", "Card #8", or "8"
$clean_id = intval(preg_replace('/[^0-9]/', '', $query));

try {
    $sql = "
        SELECT 
            kc.KCID, kc.KPTID, kc.CARD_DATE, kc.MCNO, kc.FINISH_DIA, kc.FINISH_GSM, 
            kc.GREY_GSM, kc.SL_VDQ, kc.OPEN_TUBE, kc.BUYER, kc.SUPPLIER, kc.BOOKING, 
            kc.SONO, kc.STYLE, kc.FABRICS_TYPE, kc.YARN_TYPE, kc.YARN_COUNT, kc.LOT_NO, 
            kc.KNIT_M_DESCRIPTION, kc.REQ_QTY, kc.PREPARED_BY,
            kp.MAIN_TID, kp.SUB_TID
        FROM knit_card kc
        LEFT JOIN knitting_program kp ON kc.KPTID = kp.KPTID
        WHERE kc.KCID = ? OR kc.BUYER LIKE ? OR kc.STYLE LIKE ? OR kc.SONO LIKE ? OR kc.BOOKING LIKE ?
        ORDER BY kc.KCID DESC LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query prepare failed: " . $db->error);
    }

    $search_param = '%' . $query . '%';
    $stmt->bind_param("issss", $clean_id, $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $row = $res->fetch_assoc()) {
        $kcid = intval($row['KCID']);

        // Count existing rolls for this KCID to determine next roll sequence
        $roll_count = 1;
        $c_stmt = $db->prepare("SELECT COUNT(*) AS total_rolls FROM knitting_inspection WHERE KNIT_CARD_ID = ?");
        if ($c_stmt) {
            $c_stmt->bind_param("i", $kcid);
            $c_stmt->execute();
            $c_res = $c_stmt->get_result();
            if ($c_res && $c_row = $c_res->fetch_assoc()) {
                $roll_count = intval($c_row['total_rolls']) + 1;
            }
            $c_stmt->close();
        }

        $suggested_roll_no = "R-" . $kcid . "-" . sprintf("%02d", $roll_count);
        $suggested_weight  = floatval($row['REQ_QTY']) > 0 ? floatval($row['REQ_QTY']) : 25.00;

        echo json_encode([
            'success'            => true,
            'card_id'            => $kcid,
            'kptid'              => intval($row['KPTID']),
            'buyer'              => $row['BUYER'] ?: 'N/A',
            'style'              => $row['STYLE'] ?: 'N/A',
            'sono'               => $row['SONO'] ?: 'N/A',
            'booking'            => $row['BOOKING'] ?: 'N/A',
            'mcno'               => $row['MCNO'] ?: 'N/A',
            'finish_dia'         => $row['FINISH_DIA'] ?: 'N/A',
            'finish_gsm'         => $row['FINISH_GSM'] ?: 'N/A',
            'fabrics_type'       => $row['FABRICS_TYPE'] ?: 'N/A',
            'yarn_type'          => $row['YARN_TYPE'] ?: 'N/A',
            'yarn_count'         => $row['YARN_COUNT'] ?: 'N/A',
            'lot_no'             => $row['LOT_NO'] ?: 'N/A',
            'req_qty'            => floatval($row['REQ_QTY']),
            'suggested_roll'     => $suggested_roll_no,
            'suggested_weight'   => $suggested_weight
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No matching Knit Card found for ID/Barcode: "' . htmlspecialchars($query) . '"'
        ]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
