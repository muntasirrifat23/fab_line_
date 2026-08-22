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
            kc.KCTID, kc.KPTID, kc.MCNO, kc.FDIA, kc.FGSM, 
            kc.GGSM, kc.SL, kc.O_T, kc.BUYER, kc.CUSTOMER, kc.PO_NUMBER, 
            kc.SONO, kc.STYLE, kc.FTYPE, kc.YTYPE, kc.YCOUNT, kc.LOT, 
            kc.KNIT_M_DESCRIPTION, kc.QTY, kc.UNAME,
            kp.MAIN_TID, kp.SUB_TID
        FROM knit_card kc
        LEFT JOIN knitting_program kp ON kc.KPTID = kp.KPTID
        WHERE kc.KCTID = ? OR kc.ROLL = ? OR kc.BUYER LIKE ? OR kc.STYLE LIKE ? OR kc.SONO LIKE ? OR kc.PO_NUMBER LIKE ?
        ORDER BY kc.KCTID DESC LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query prepare failed: " . $db->error);
    }

    $search_param = '%' . $query . '%';
    $stmt->bind_param("isssss", $clean_id, $query, $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $row = $res->fetch_assoc()) {
        $kcid = intval($row['KCTID']);

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
        $suggested_weight  = floatval($row['QTY']) > 0 ? floatval($row['QTY']) : 25.00;

        echo json_encode([
            'success'            => true,
            'card_id'            => $kcid,
            'kptid'              => intval($row['KPTID']),
            'buyer'              => $row['BUYER'] ?: 'N/A',
            'style'              => $row['STYLE'] ?: 'N/A',
            'sono'               => $row['SONO'] ?: 'N/A',
            'booking'            => $row['PO_NUMBER'] ?: 'N/A',
            'mcno'               => $row['MCNO'] ?: 'N/A',
            'finish_dia'         => $row['FDIA'] ?: 'N/A',
            'finish_gsm'         => $row['FGSM'] ?: 'N/A',
            'fabrics_type'       => $row['FTYPE'] ?: 'N/A',
            'yarn_type'          => $row['YTYPE'] ?: 'N/A',
            'yarn_count'         => $row['YCOUNT'] ?: 'N/A',
            'lot_no'             => $row['LOT'] ?: 'N/A',
            'req_qty'            => floatval($row['QTY']),
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
