<?php
// knitting_inspection.php - 2-Step Operator Authentication & Roll Scanner Fabric Inspection Module (knitting_production UI Match)
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

// ── ACTION: VERIFY OPERATOR QR CODE ──
if (isset($_GET['action']) && $_GET['action'] === 'verify_operator') {
    header('Content-Type: application/json');
    $op_id = trim($_GET['operator_id'] ?? $_GET['query'] ?? '');
    
    if (empty($op_id)) {
        echo json_encode(['success' => false, 'error' => 'Operator ID is required']);
        exit();
    }

    $stmt = $db->prepare("SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator WHERE OPERATOR_ID = ? OR OPERATOR_NAME = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $op_id, $op_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $_SESSION['active_operator'] = [
                'id'   => $row['OPERATOR_ID'],
                'name' => $row['OPERATOR_NAME']
            ];
            echo json_encode([
                'success' => true,
                'data'    => [
                    'OPERATOR_ID'   => $row['OPERATOR_ID'],
                    'OPERATOR_NAME' => $row['OPERATOR_NAME']
                ]
            ]);
            $stmt->close();
            exit();
        }
        $stmt->close();
    }
    echo json_encode(['success' => false, 'error' => 'Invalid Operator ID: ' . $op_id]);
    exit();
}

// ── ACTION: SWITCH / LOGOUT OPERATOR ──
if (isset($_GET['action']) && $_GET['action'] === 'logout_operator') {
    header('Content-Type: application/json');
    unset($_SESSION['active_operator']);
    echo json_encode(['success' => true]);
    exit();
}

// ── ACTION: FETCH ROLL / KNIT CARD DETAILS ──
if (isset($_GET['action']) && $_GET['action'] === 'search_card') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['active_operator']) || empty($_SESSION['active_operator']['id'])) {
        echo json_encode(['success' => false, 'error' => 'Please scan Operator ID QR Code first!']);
        exit();
    }

    $query = trim($_GET['query'] ?? $_GET['roll'] ?? '');
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Roll number or Card ID is required']);
        exit();
    }

    $clean_id = intval(preg_replace('/[^0-9]/', '', $query));

    $sql = "SELECT 
                kc.KCTID, kc.KPTID, kc.MCNO, kc.FDIA, kc.FGSM, 
                kc.GGSM, kc.SL, kc.O_T, kc.BUYER, kc.CUSTOMER, kc.PO_NUMBER, 
                kc.SONO, kc.STYLE, kc.FTYPE, kc.YTYPE, kc.YCOUNT, kc.LOT, 
                kc.KNIT_M_DESCRIPTION, kc.QTY, kc.UNAME
            FROM knit_card kc
            WHERE kc.KCTID = ? OR kc.ROLL = ? OR kc.BUYER LIKE ? OR kc.STYLE LIKE ? OR kc.SONO LIKE ? OR kc.PO_NUMBER LIKE ?
            ORDER BY kc.KCTID DESC LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $search_param = '%' . $query . '%';
    $stmt->bind_param("isssss", $clean_id, $query, $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $row = $res->fetch_assoc()) {
        $kcid = intval($row['KCTID']);
        
        $roll_count = 1;
        $roll_pattern = "R-" . $kcid . "-%";
        $c_stmt = $db->prepare("SELECT COUNT(*) AS total_rolls FROM knitting_inspection WHERE ROLL LIKE ?");
        if ($c_stmt) {
            $c_stmt->bind_param("s", $roll_pattern);
            $c_stmt->execute();
            $c_res = $c_stmt->get_result();
            if ($c_res && $c_row = $c_res->fetch_assoc()) {
                $roll_count = intval($c_row['total_rolls']) + 1;
            }
            $c_stmt->close();
        }

        echo json_encode([
            'success'          => true,
            'data'             => [
                'card_id'          => $kcid,
                'kptid'            => intval($row['KPTID']),
                'buyer'            => $row['BUYER'] ?: 'N/A',
                'style'            => $row['STYLE'] ?: 'N/A',
                'sono'             => $row['SONO'] ?: 'N/A',
                'booking'          => $row['PO_NUMBER'] ?: 'N/A',
                'mcno'             => $row['MCNO'] ?: 'N/A',
                'finish_dia'       => $row['FDIA'] ?: 'N/A',
                'finish_gsm'       => $row['FGSM'] ?: 'N/A',
                'fabrics_type'     => $row['FTYPE'] ?: 'N/A',
                'yarn_type'        => $row['YTYPE'] ?: 'N/A',
                'yarn_count'       => $row['YCOUNT'] ?: 'N/A',
                'lot_no'           => $row['LOT'] ?: 'N/A',
                'req_qty'          => floatval($row['QTY']),
                'suggested_roll'   => (strlen($query) > 5) ? $query : ('R-' . $kcid . '-' . sprintf("%02d", $roll_count)),
                'suggested_weight' => floatval($row['QTY']) > 0 ? floatval($row['QTY']) : 25.00
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No Knit Card or Roll matching "' . htmlspecialchars($query) . '"']);
    }
    $stmt->close();
    exit();
}

$error = '';
$msg = '';

// ── SAVE INSPECTION RECORD ──
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_inspection'])) {
    if (!isset($_SESSION['active_operator']) || empty($_SESSION['active_operator']['id'])) {
        $error = "Unauthorized: You must scan a valid Operator ID QR Code before completing fabric inspection.";
    } else {
        $knit_card_id        = intval($_POST['KNIT_CARD_ID'] ?? 0);
        $roll_no             = trim($_POST['ROLL_NO'] ?? '');
        $roll_weight         = floatval($_POST['ROLL_WEIGHT'] ?? 0);
        
        $card_meta = [];
        if ($knit_card_id > 0) {
            $c_q = $db->prepare("
                SELECT kc.*, kp.MAIN_TID, kp.SUB_TID 
                FROM knit_card kc 
                LEFT JOIN knitting_program kp ON kc.KPTID = kp.KPTID 
                WHERE kc.KCTID = ?
            ");
            if ($c_q) {
                $c_q->bind_param("i", $knit_card_id);
                $c_q->execute();
                $c_res = $c_q->get_result();
                if ($c_res && $row = $c_res->fetch_assoc()) {
                    $card_meta = $row;
                }
                $c_q->close();
            }
        }

        $defect_tt          = isset($_POST['DEFECT_TT']) ? 1 : 0;
        $defect_patta       = isset($_POST['DEFECT_PATTA']) ? 1 : 0;
        $defect_slub        = isset($_POST['DEFECT_SLUB']) ? 1 : 0;
        $defect_yc          = isset($_POST['DEFECT_YC_SPOT']) ? 1 : 0;
        
        $defect_oil_spot    = isset($_POST['DEFECT_OILSPOT']) ? 1 : 0;
        $defect_ff          = isset($_POST['DEFECT_FF']) ? 1 : 0;
        $defect_seeds       = isset($_POST['DEFECT_SEEDS']) ? 1 : 0;
        $defect_m_stitch    = isset($_POST['DEFECT_MSTITCH']) ? 1 : 0;

        $defect_sinker_mark = isset($_POST['DEFECT_SINKERMARK']) ? 1 : 0;
        $defect_needle_mark = isset($_POST['DEFECT_NEEDLEMARK']) ? 1 : 0;
        $defect_lycra_out   = isset($_POST['DEFECT_LYCOUT']) ? 1 : 0;
        $defect_oil_line    = isset($_POST['DEFECT_OILLINE']) ? 1 : 0;

        $defect_hole        = isset($_POST['DEFECT_HOLE']) ? 1 : 0;
        $defect_loop        = isset($_POST['DEFECT_LOOP']) ? 1 : 0;
        $defect_setup       = isset($_POST['DEFECT_SETUP']) ? 1 : 0;
        $defect_crease_mark = isset($_POST['DEFECT_CMARK']) ? 1 : 0;

        $total_points = ($defect_tt * 1) + ($defect_patta * 1) + ($defect_slub * 1) + ($defect_yc * 1) + 
                        ($defect_oil_spot * 2) + ($defect_ff * 2) + ($defect_seeds * 2) + ($defect_m_stitch * 2) + 
                        ($defect_sinker_mark * 3) + ($defect_needle_mark * 3) + ($defect_lycra_out * 3) + 
                        ($defect_oil_line * 3) + ($defect_hole * 4) + ($defect_loop * 4) + ($defect_setup * 4) + 
                        ($defect_crease_mark * 4);

        $qc_grade     = trim($_POST['QC_GRADE'] ?? '');
        $qc_status    = trim($_POST['QC_STATUS'] ?? '');

        if (empty($qc_grade)) {
            if ($total_points <= 10) {
                $qc_grade = 'Grade A';
            } elseif ($total_points <= 25) {
                $qc_grade = 'Grade B';
            } else {
                $qc_grade = 'Reject';
            }
        }
        if (empty($qc_status)) {
            $qc_status = ($qc_grade === 'Reject') ? 'Failed' : 'Passed';
        }

        if ($knit_card_id <= 0 && empty($roll_no)) {
            $error = "Please select a valid Knit Card or enter Roll Number.";
        } elseif (empty($roll_no)) {
            $error = "Roll Number is required.";
        } elseif ($roll_weight <= 0) {
            $error = "Roll Weight must be greater than 0.";
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO knitting_inspection (
                        `BUDAT`, `ROLL`, `OQTY`, `RQTY`, `UQTY`, `PO_NUMBER`, `QTY`, `SONO`, `BUYER`, `STYLE`, `COLOR`,
                        `MCNO`, `MC_DIA`, `SUPPLIER`, `SHIFT`, `YTYPE`, `YCOUNT`, `FTYPE`, `FGSM`, `FDIA`, `O_T`,
                        `SL`, `GGSM`, `FPLAN`, `LOTNO`, `MATERIAL_CODE`, `M_DES`,
                        `TT`, `PATTA`, `SLUB`, `YC_SPOT`, `OILSPOT`, `FF`, `SEEDS`, `MSTITCH`, `SINKERMARK`, `NEEDLEMARK`,
                        `LYCOUT`, `OILLINE`, `HOLE`, `LOOP`, `SETUP`, `CMARK`, `TPOINT`,
                        `QC_GRADE`, `QC_STATUS`, `UNAME`, `UID`
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?
                    )
                ");
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $db->error);
                }
                
                $budat       = date('Y-m-d');
                $oqty        = strval($card_meta['QTY'] ?? $roll_weight);
                $rqty        = strval($card_meta['QTY'] ?? $roll_weight);
                $uqty        = strval($roll_weight);
                $po_number   = strval($card_meta['PO_NUMBER'] ?? '');
                $qty         = strval($roll_weight);
                $sono        = strval($card_meta['SONO'] ?? '');
                $buyer       = strval($card_meta['BUYER'] ?? '');
                $style       = strval($card_meta['STYLE'] ?? '');
                $color       = strval($card_meta['COLOR'] ?? '');
                $mcno        = strval($card_meta['MCNO'] ?? '');
                $mc_dia      = strval($card_meta['MCDIA'] ?? '');
                $supplier    = strval($card_meta['CUSTOMER'] ?? '');
                $shift       = strval($card_meta['SHIFT'] ?? '');
                $ytype       = strval($card_meta['YTYPE'] ?? '');
                $ycount      = strval($card_meta['YCOUNT'] ?? '');
                $ftype       = strval($card_meta['FTYPE'] ?? '');
                $fgsm        = strval($card_meta['FGSM'] ?? '');
                $fdia        = strval($card_meta['FDIA'] ?? '');
                $o_t         = strval($card_meta['O_T'] ?? '');
                $sl          = floatval($card_meta['SL'] ?? 0.00);
                $ggsm        = strval($card_meta['GGSM'] ?? '');
                $fplan       = strval($card_meta['FEEDER_PLAN'] ?? '');
                $lotno       = strval($card_meta['LOT'] ?? '');
                $mat_code    = strval($card_meta['KNIT_MATERIAL_CODE'] ?? '');
                $m_des       = strval($card_meta['KNIT_M_DESCRIPTION'] ?? '');

                $v_tt         = strval($defect_tt);
                $v_patta      = strval($defect_patta);
                $v_slub       = strval($defect_slub);
                $v_yc_spot    = strval($defect_yc);
                $v_oilspot    = strval($defect_oil_spot);
                $v_ff         = strval($defect_ff);
                $v_seeds      = strval($defect_seeds);
                $v_mstitch    = strval($defect_m_stitch);
                $v_sinkermark = strval($defect_sinker_mark);
                $v_needlemark = strval($defect_needle_mark);
                $v_lycout     = strval($defect_lycra_out);
                $v_oilline    = strval($defect_oil_line);
                $v_hole       = strval($defect_hole);
                $v_loop       = strval($defect_loop);
                $v_setup      = strval($defect_setup);
                $v_cmark      = strval($defect_crease_mark);
                $v_tpoint     = strval($total_points);

                $uname        = strval($_SESSION['active_operator']['name']);
                $uid          = strval($_SESSION['active_operator']['id']);

                $types = str_repeat('s', 21) . 'd' . str_repeat('s', 26);

                $stmt->bind_param(
                    $types,
                    $budat, $roll_no, $oqty, $rqty, $uqty, $po_number, $qty, $sono, $buyer, $style, $color,
                    $mcno, $mc_dia, $supplier, $shift, $ytype, $ycount, $ftype, $fgsm, $fdia, $o_t,
                    $sl, $ggsm, $fplan, $lotno, $mat_code, $m_des,
                    $v_tt, $v_patta, $v_slub, $v_yc_spot, $v_oilspot, $v_ff, $v_seeds, $v_mstitch, $v_sinkermark, $v_needlemark,
                    $v_lycout, $v_oilline, $v_hole, $v_loop, $v_setup, $v_cmark, $v_tpoint,
                    $qc_grade, $qc_status, $uname, $uid
                );

                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $stmt->close();
                $msg = "Inspection record for Roll #$roll_no saved successfully!";
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch active Knit Cards for select dropdown
$cards = [];
$c_res = $db->query("
    SELECT KCTID, MCNO, BUYER, STYLE, SONO, QTY 
    FROM knit_card 
    ORDER BY KCTID DESC
");
if ($c_res) {
    while ($row = $c_res->fetch_assoc()) {
        $cards[] = $row;
    }
}

$active_operator = $_SESSION['active_operator'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Knitting | Fabric Inspection</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Roboto, system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #e2e8f0, #f8fafc, #dbeafe);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 16px;
    }

    .card {
      max-width: 650px;
      width: 100%;
      background: #ffffff;
      border-radius: 40px;
      padding: 24px 24px 30px;
      box-shadow: 0 20px 45px rgba(30, 60, 120, 0.2);
      border: 1px solid #dbe4ef;
      transition: max-width 0.3s ease;
    }

    .production-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
      padding: 0 4px;
    }

    .production-header h2 {
      color: #083a36;
      font-size: 1.4rem;
      font-weight: 800;
      letter-spacing: 0.5px;
      margin: 0;
    }

    .production-header h2 i {
      color: #0f7a6f;
      margin-right: 8px;
    }

    .production-header .badge-production {
      margin-left: auto;
      background: #10b981;
      color: white;
      font-size: 0.7rem;
      padding: 3px 14px;
      border-radius: 100px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }

    .scanner-container {
      position: relative;
      background: #eef2f7;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px #d7e0ea, 0 8px 20px rgba(30, 60, 120, 0.12);
      margin-bottom: 24px;
      aspect-ratio: 1 / 1;
    }

    #qr-reader {
      width: 100%;
      height: 100%;
      padding: 0 !important;
      background: #f4f7fb;
    }

    #qr-reader video {
      border-radius: 28px;
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .scan-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      border-radius: 28px;
      box-shadow: inset 0 0 0 2px rgba(0, 255, 200, 0.3);
    }

    .scan-overlay::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 70%;
      height: 70%;
      transform: translate(-50%, -50%);
      border: 2px solid rgba(0, 255, 200, 0.5);
      border-radius: 20px;
      box-shadow: 0 0 30px rgba(0, 255, 200, 0.1);
      animation: pulse-border 2.2s infinite ease-in-out;
    }

    @keyframes pulse-border {
      0% { opacity: 0.4; transform: translate(-50%, -50%) scale(0.96); }
      50% { opacity: 1; transform: translate(-50%, -50%) scale(1.02); }
      100% { opacity: 0.4; transform: translate(-50%, -50%) scale(0.96); }
    }

    .camera-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 12px;
      padding: 0 6px;
    }

    .status-badge {
      background: #eef2f7;
      padding: 8px 18px;
      border-radius: 100px;
      color: #334155;
      font-size: 0.85rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      border: 1px solid #cbd5e1;
    }

    .status-badge i {
      color: #2563eb;
      font-size: 0.9rem;
    }

    .btn-icon {
      background: #eef2f7;
      border: 1px solid #cbd5e1;
      color: #334155;
      width: 44px;
      height: 44px;
      border-radius: 40px;
      font-size: 1.2rem;
      cursor: pointer;
      transition: 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-icon:hover {
      background: #e2e8f0;
      border-color: #2563eb;
      color: #1e3a8a;
    }

    .result-panel {
      background: #f4f7fb;
      border-radius: 28px;
      padding: 18px 20px 16px;
      margin-top: 20px;
      border: 1px solid #d7e0ea;
      box-shadow: inset 0 2px 6px rgba(30, 60, 120, 0.06);
    }

    .result-header {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #334155;
      font-weight: 700;
      letter-spacing: 0.3px;
      font-size: 0.9rem;
      border-bottom: 1px dashed #cbd5e1;
      padding-bottom: 10px;
      margin-bottom: 12px;
    }

    .result-header i {
      color: #2563eb;
    }

    .data-row {
      background: #eef2f7;
      padding: 8px 14px;
      border-radius: 12px;
      border-left: 4px solid #2563eb;
      color: #1e293b;
      font-size: 0.9rem;
      line-height: 1.4;
      box-shadow: 0 2px 6px rgba(30, 60, 120, 0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }

    .data-row .label {
      color: #475569;
      font-weight: 700;
    }

    .data-row .value {
      color: #0f172a;
      font-weight: 600;
      text-align: right;
    }

    .data-row.header-row {
      border-left-color: #f59e0b;
      background: #e8eef6;
      font-weight: 700;
      font-size: 0.95rem;
    }

    .manual-entry {
      display: flex;
      gap: 8px;
      margin-top: 10px;
      margin-bottom: 12px;
    }

    .manual-entry input {
      flex: 1;
      min-width: 0;
      padding: 9px 14px;
      border: 1px solid #cbd5e1;
      border-radius: 20px;
      font-size: 0.85rem;
      outline: none;
      background: #ffffff;
      color: #0f172a;
    }

    .manual-entry input:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .manual-entry button {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: #ffffff;
      border: none;
      padding: 9px 20px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      white-space: nowrap;
      transition: 0.2s;
    }

    .manual-entry button:hover {
      filter: brightness(1.1);
    }

    .fault-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
      gap: 8px;
      margin-top: 10px;
      margin-bottom: 16px;
    }

    .fault-btn {
      background: #ffffff;
      border: 1.5px solid #cbd5e1;
      border-radius: 12px;
      padding: 8px 10px;
      font-size: 0.78rem;
      font-weight: 700;
      color: #334155;
      cursor: pointer;
      text-align: center;
      transition: 0.2s;
      user-select: none;
    }

    .fault-btn:hover {
      border-color: #2563eb;
      background: #f1f5f9;
    }

    .fault-btn.active {
      background: #fee2e2;
      border-color: #ef4444;
      color: #b91c1c;
      box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }

    .field-input {
      width: 100%;
      background: #ffffff;
      border: 1px solid #cbd5e1;
      color: #0f172a;
      border-radius: 12px;
      padding: 10px 12px;
      font-size: 0.95rem;
      outline: none;
    }

    .field-input:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .action-content {
      margin-top: 18px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .action-card {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
      align-items: center;
      background: #eef2f7;
      border: 1px solid #d7e0ea;
      border-radius: 18px;
      padding: 14px;
    }

    .btn-action {
      flex: 1 1 120px;
      min-width: 120px;
      border: none;
      border-radius: 14px;
      padding: 12px 16px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
      color: #fff;
      background: linear-gradient(135deg, #475569, #334155);
    }

    .btn-action.production {
      background: linear-gradient(135deg, #10b981, #0f766e);
    }

    .btn-action.cancel {
      background: linear-gradient(135deg, #ef4444, #b91c1c);
    }
  </style>
</head>

<body>

  <div class="card" id="mainCard">
    <div class="production-header">
      <h2><i class="fa-solid fa-list-check"></i>Knitting Inspection</h2>
      <span class="badge-production">INSPECTION</span>
    </div>

    <!-- SCANNER CONTAINER (LIVE QR CAMERA) -->
    <div class="scanner-container" id="scannerContainer">
      <div id="qr-reader"></div>
      <div class="scan-overlay"></div>
    </div>

    <!-- CAMERA CONTROLS -->
    <div class="camera-controls" id="cameraControls">
      <div class="status-badge">
        <i class="fas fa-video"></i>
        <span id="camera-status">Ready</span>
      </div>
      <div style="display: flex; gap: 8px;">
        <button class="btn-icon" id="rotate-camera-btn" title="Rotate camera 90°">
          <i class="fa-solid fa-rotate-right"></i>
        </button>
        <button class="btn-icon" id="toggle-camera-btn" title="Switch Front/Back camera">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>
    </div>

    <!-- RESULT & WORKFLOW PANEL -->
    <div class="result-panel">
      <div class="result-header">
        <i class="fas fa-qrcode"></i>
        <span id="step-title-text"><?php echo $active_operator ? 'Step 2: Scan Production Roll QR' : 'Step 1: Scan Operator ID QR'; ?></span>
        <?php if ($active_operator): ?>
          <span style="margin-left: auto; font-size: 0.75rem; background: #10b981; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700;">
            <i class="fa-solid fa-user-check me-1"></i> <?php echo htmlspecialchars($active_operator['name']); ?> (<?php echo htmlspecialchars($active_operator['id']); ?>)
          </span>
          <button type="button" onclick="logoutOperator()" style="margin-left: 8px; background:#ef4444; border:none; color:white; font-size:0.7rem; padding:3px 10px; border-radius:20px; cursor:pointer; font-weight:700;">Switch</button>
        <?php else: ?>
          <span style="margin-left: auto; font-size: 0.75rem; background: #f59e0b; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700;">Operator Auth Required</span>
        <?php endif; ?>
      </div>

      <?php if (!empty($msg)): ?>
        <div style="background:#142f1f; border:1px solid #166534; color:#c7f6d1; padding:10px 14px; border-radius:12px; margin-bottom:12px; font-weight:600; font-size:0.88rem;">
          <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div style="background:#3f1d1d; border:1px solid #b91c1c; color:#fee2e2; padding:10px 14px; border-radius:12px; margin-bottom:12px; font-weight:600; font-size:0.88rem;">
          <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <div id="result-content">
        <!-- Rendered by JS -->
      </div>

      <div id="action-content" class="action-content"></div>
    </div>

    <!-- FOOTER -->
    <div class="footer-note" style="margin-top:20px; text-align:center;">
      <button onclick="window.location.href='initialPage.php';"
        style="background-color:#1e3a8a; color:white; padding:12px 18px; border:none; border-radius:10px; cursor:pointer; font-weight:bold; font-size:1rem; width:100%;">
        <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>
        Back to Initial Page
      </button>
    </div>
  </div>

  <script>
    (function() {
      "use strict";

      const resultContainer = document.getElementById('result-content');
      const actionContainer = document.getElementById('action-content');
      const cameraStatus    = document.getElementById('camera-status');
      const toggleCameraBtn = document.getElementById('toggle-camera-btn');
      
      const isOperatorActive = <?php echo $active_operator ? 'true' : 'false'; ?>;
      let activeOperatorInfo = <?php echo json_encode($active_operator); ?>;
      
      let html5QrCode = null;
      let isScanning    = false;
      let rollData      = null;
      let selectedFaults = {};

      const FAULTS = [
        { id: 'TT', name: 'Thick & Thin', weight: 1 },
        { id: 'PATTA', name: 'Patta / Barre', weight: 1 },
        { id: 'SLUB', name: 'Yarn Slub', weight: 1 },
        { id: 'YC_SPOT', name: 'Yarn Spot', weight: 1 },
        { id: 'OILSPOT', name: 'Oil Spot', weight: 2 },
        { id: 'FF', name: 'Fly Frame', weight: 2 },
        { id: 'SEEDS', name: 'Cotton Seeds', weight: 2 },
        { id: 'MSTITCH', name: 'Miss Stitch', weight: 2 },
        { id: 'SINKERMARK', name: 'Sinker Mark', weight: 3 },
        { id: 'NEEDLEMARK', name: 'Needle Mark', weight: 3 },
        { id: 'LYCOUT', name: 'Lycra Out', weight: 3 },
        { id: 'OILLINE', name: 'Oil Line', weight: 3 },
        { id: 'HOLE', name: 'Fabric Hole', weight: 4 },
        { id: 'LOOP', name: 'Big Loop', weight: 4 },
        { id: 'SETUP', name: 'Wrong Setup', weight: 4 },
        { id: 'CMARK', name: 'Crease Mark', weight: 4 }
      ];

      function initView() {
        if (!isOperatorActive) {
          renderStep1Operator();
        } else {
          renderStep2RollScan();
        }
      }

      // STEP 1: OPERATOR QR SCAN ONLY (NO TYPING INPUT)
      function renderStep1Operator() {
        actionContainer.innerHTML = '';
        let html = `
          <div style="background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:18px; padding:16px; margin-bottom:14px; text-align:center; color:#1e3a8a;">
            <div style="font-size:2.2rem; margin-bottom:6px; color:#2563eb;"><i class="fa-solid fa-qrcode"></i></div>
            <div style="font-weight:800; font-size:1.05rem; margin-bottom:4px;">Scan Operator Badge QR Code</div>
            <div style="font-size:0.85rem; opacity:0.85;">Align your Operator QR Code within the live camera feed above to authenticate.</div>
          </div>
          <div class="data-row header-row"><span class="label">Step 1: Operator Authentication</span><span class="value">Scan QR Only</span></div>
          <div class="data-row"><span class="label">Step 1:</span><span class="value" style="color:#2563eb; font-weight:700;">Scan Operator QR Code</span></div>
          <div class="data-row"><span class="label">Step 2:</span><span class="value">Scan Production Roll QR</span></div>
          <div id="opStatusMsg" style="margin-top:8px;"></div>
        `;
        resultContainer.innerHTML = html;
      }

      function submitOperator(val) {
        val = String(val || '').trim();
        if (!val) { alert('Please enter or scan Operator ID!'); return; }

        const msgDiv = document.getElementById('opStatusMsg');
        if (msgDiv) msgDiv.innerHTML = '<div style="color:#2563eb; font-weight:700; font-size:0.85rem;"><i class="fas fa-spinner fa-spin"></i> Verifying Operator ID...</div>';

        fetch('knitting_inspection.php?action=verify_operator&operator_id=' + encodeURIComponent(val))
          .then(r => r.json())
          .then(res => {
            if (res.success) {
              window.location.reload();
            } else {
              if (msgDiv) msgDiv.innerHTML = `<div style="color:#ef4444; font-weight:700; font-size:0.85rem;"><i class="fas fa-times-circle"></i> ${res.error || 'Invalid Operator ID'}</div>`;
            }
          })
          .catch(err => {
            if (msgDiv) msgDiv.innerHTML = `<div style="color:#ef4444; font-weight:700; font-size:0.85rem;"><i class="fas fa-exclamation-triangle"></i> Verification Error</div>`;
          });
      }

      // STEP 2: ROLL SCAN & INSPECTION FORM
      function renderStep2RollScan() {
        let html = `
          <div class="manual-entry">
            <input type="text" id="rollInput" placeholder="Roll QR / Barcode (e.g. 300099903)" autocomplete="off" autofocus>
            <button type="button" id="rollBtn">Load</button>
          </div>
          <div class="data-row header-row"><span class="label">Step 2: Roll Selection</span><span class="value"></span></div>
          <div class="data-row"><span class="label">Operator:</span><span class="value" style="color:#10b981; font-weight:700;">${activeOperatorInfo.name} (${activeOperatorInfo.id})</span></div>
          <div class="data-row"><span class="label">Action:</span><span class="value">Scan Production Roll QR Code</span></div>
          <div id="rollStatusMsg" style="margin-top:8px;"></div>
        `;
        resultContainer.innerHTML = html;

        const inp = document.getElementById('rollInput');
        const btn = document.getElementById('rollBtn');
        if (btn) btn.addEventListener('click', () => submitRollScan(inp.value));
        if (inp) inp.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') { e.preventDefault(); submitRollScan(inp.value); }
        });
      }

      function submitRollScan(val) {
        val = String(val || '').trim();
        if (!val) { alert('Please enter or scan Roll QR!'); return; }

        const msgDiv = document.getElementById('rollStatusMsg');
        if (msgDiv) msgDiv.innerHTML = '<div style="color:#2563eb; font-weight:700; font-size:0.85rem;"><i class="fas fa-spinner fa-spin"></i> Fetching Roll Data...</div>';

        fetch('knitting_inspection.php?action=search_card&query=' + encodeURIComponent(val))
          .then(r => r.json())
          .then(res => {
            if (res.success && res.data) {
              rollData = res.data;
              renderInspectionForm(res.data);
            } else {
              if (msgDiv) msgDiv.innerHTML = `<div style="color:#ef4444; font-weight:700; font-size:0.85rem;"><i class="fas fa-times-circle"></i> ${res.error || 'Roll not found'}</div>`;
            }
          })
          .catch(err => {
            if (msgDiv) msgDiv.innerHTML = `<div style="color:#ef4444; font-weight:700; font-size:0.85rem;"><i class="fas fa-exclamation-triangle"></i> Network error loading roll</div>`;
          });
      }

      // RENDER FULL INSPECTION FORM MATRIX (knitting_production Style)
      function renderInspectionForm(d) {
        selectedFaults = {};
        
        let html = `
          <div class="data-row header-row"><span class="label">Roll Information</span><span class="value">Knit Card #${d.card_id}</span></div>
          <div class="data-row"><span class="label">Roll Number:</span><span class="value" style="color:#2563eb; font-weight:800;">${d.suggested_roll}</span></div>
          <div class="data-row"><span class="label">PO Number:</span><span class="value">${d.booking}</span></div>
          <div class="data-row"><span class="label">Buyer / Style:</span><span class="value">${d.buyer} (${d.style})</span></div>
          <div class="data-row"><span class="label">Fabric / GSM:</span><span class="value">${d.fabrics_type} (${d.finish_gsm} GSM)</span></div>
          
          <div style="margin-top:14px; font-weight:800; font-size:0.85rem; color:#334155;">
            <i class="fa-solid fa-weight-hanging me-1 text-primary"></i> ROLL WEIGHT (KG):
          </div>
          <div style="margin-top:4px;">
            <input type="number" step="0.01" id="weightInput" class="field-input" value="${parseFloat(d.suggested_weight).toFixed(2)}" style="font-weight:700; font-size:1.1rem; text-align:center;">
          </div>

          <div style="margin-top:16px; font-weight:800; font-size:0.85rem; color:#334155;">
            <i class="fa-solid fa-list-check me-1 text-warning"></i> FABRIC FAULTS (4-POINT MATRIX):
          </div>
          <div class="fault-grid">
        `;

        FAULTS.forEach(f => {
          html += `
            <div class="fault-btn" id="fault_${f.id}" onclick="window.toggleFault('${f.id}', ${f.weight})">
              ${f.name} (+${f.weight}p)
            </div>
          `;
        });

        html += `</div>`;

        html += `
          <div class="data-row" style="background:#eff6ff; border-left-color:#2563eb; margin-top:10px;">
            <span class="label">Total Points:</span>
            <span class="value" id="calc_points" style="font-size:1.1rem; color:#2563eb; font-weight:800;">0 pts</span>
          </div>
          <div class="data-row" style="background:#f0fdf4; border-left-color:#10b981;">
            <span class="label">QC Grade / Status:</span>
            <span class="value" id="calc_grade" style="font-size:1rem; color:#166534; font-weight:800;">Grade A (Passed)</span>
          </div>
        `;

        resultContainer.innerHTML = html;

        actionContainer.innerHTML = `
          <div class="action-card">
            <button class="btn-action production" id="saveBtn" onclick="window.saveInspectionRecord()">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Inspection Record
            </button>
            <button class="btn-action cancel" onclick="window.location.reload()">
              <i class="fa-solid fa-rotate-left me-1"></i> Reset
            </button>
          </div>
        `;
      }

      window.toggleFault = function(id, weight) {
        const btn = document.getElementById('fault_' + id);
        if (selectedFaults[id]) {
          delete selectedFaults[id];
          if (btn) btn.classList.remove('active');
        } else {
          selectedFaults[id] = weight;
          if (btn) btn.classList.add('active');
        }
        recalcPoints();
      };

      function recalcPoints() {
        let total = 0;
        Object.keys(selectedFaults).forEach(k => {
          total += selectedFaults[k];
        });
        const ptElem = document.getElementById('calc_points');
        const grElem = document.getElementById('calc_grade');

        if (ptElem) ptElem.textContent = total + ' pts';

        let grade = 'Grade A';
        let status = 'Passed';
        if (total > 25) {
          grade = 'Reject';
          status = 'Failed';
        } else if (total > 10) {
          grade = 'Grade B';
          status = 'Passed';
        }

        if (grElem) {
          grElem.textContent = `${grade} (${status})`;
          grElem.style.color = (status === 'Passed') ? '#166534' : '#b91c1c';
        }
      }

      window.saveInspectionRecord = function() {
        if (!rollData) return;
        const weightInput = document.getElementById('weightInput');
        const weightVal = weightInput ? parseFloat(weightInput.value) : 25.00;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'knitting_inspection.php';

        const addField = (k, v) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = k;
          input.value = v;
          form.appendChild(input);
        };

        addField('save_inspection', '1');
        addField('KNIT_CARD_ID', rollData.card_id);
        addField('ROLL_NO', rollData.suggested_roll);
        addField('ROLL_WEIGHT', weightVal);

        FAULTS.forEach(f => {
          if (selectedFaults[f.id]) {
            addField('DEFECT_' + f.id, '1');
          }
        });

        document.body.appendChild(form);
        form.submit();
      };

      window.logoutOperator = function() {
        fetch('knitting_inspection.php?action=logout_operator')
          .then(r => r.json())
          .then(() => window.location.reload());
      };

      // QR CAMERA SCANNER INITIALIZATION WITH ROTATION & FLIP
      const rotateCameraBtn = document.getElementById('rotate-camera-btn');
      let currentRotation   = 0;
      let currentFacingMode = "environment";

      function applyVideoRotation() {
        setTimeout(() => {
          const videoElem = document.querySelector('#qr-reader video');
          if (videoElem) {
            videoElem.style.transform = `rotate(${currentRotation}deg)`;
            videoElem.style.transition = 'transform 0.3s ease';
          }
        }, 150);
      }

      function startCameraScanner() {
        try {
          if (html5QrCode && isScanning) {
            html5QrCode.stop().then(() => {
              isScanning = false;
              initScannerObject();
            }).catch(() => initScannerObject());
          } else {
            initScannerObject();
          }
        } catch (e) {
          console.warn(e);
        }
      }

      function initScannerObject() {
        html5QrCode = new Html5Qrcode("qr-reader");
        html5QrCode.start(
          { facingMode: currentFacingMode },
          { fps: 15, qrbox: { width: 250, height: 250 } },
          onScanSuccess,
          onScanFailure
        ).then(() => {
          isScanning = true;
          if (cameraStatus) cameraStatus.textContent = 'Scanning (' + (currentFacingMode === 'environment' ? 'Rear' : 'Front') + ')';
          applyVideoRotation();
        }).catch(err => {
          console.warn("Camera start failed:", err);
          if (cameraStatus) cameraStatus.textContent = 'Camera Unavailable';
        });
      }

      function onScanSuccess(decodedText) {
        const text = String(decodedText || '').trim();
        if (!text) return;

        if (!isOperatorActive) {
          submitOperator(text);
        } else {
          submitRollScan(text);
        }
      }

      function onScanFailure(err) {}

      if (rotateCameraBtn) {
        rotateCameraBtn.addEventListener('click', () => {
          currentRotation = (currentRotation + 90) % 360;
          applyVideoRotation();
          if (cameraStatus) {
            cameraStatus.textContent = 'Rotated ' + currentRotation + '°';
          }
        });
      }

      if (toggleCameraBtn) {
        toggleCameraBtn.addEventListener('click', () => {
          currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
          startCameraScanner();
        });
      }

      // Initialize
      initView();
      startCameraScanner();

    })();
  </script>
</body>

</html>
