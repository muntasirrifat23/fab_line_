<?php
// knitting_inspection.php - 2-Step Operator Authentication & Roll Scanner Fabric Inspection Module (knitting_production UI Match)
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

// ── SMART SCAN ENDPOINT (Handles both Operator QR & Roll QR seamlessly) ──
if (isset($_GET['action']) && ($_GET['action'] === 'smart_scan' || $_GET['action'] === 'verify_operator' || $_GET['action'] === 'search_card')) {
    header('Content-Type: application/json');
    $query = trim($_GET['query'] ?? $_GET['operator_id'] ?? $_GET['roll'] ?? $_GET['text'] ?? '');
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Scanned code is required']);
        exit();
    }

    // 1. Try matching Operator ID / Name / KOTID first
    $op_clean = preg_replace('/^(OPERATOR|ID|OP_ID)[\s\:\-_]*/i', '', $query);
    $op_clean = trim($op_clean);
    $int_val  = intval(preg_replace('/[^0-9]/', '', $op_clean));

    $op_stmt = $db->prepare("
        SELECT KOTID, OPERATOR_ID, OPERATOR_NAME, OPERATOR_EMAIL 
        FROM knitting_operator 
        WHERE LOWER(OPERATOR_ID) = LOWER(?) 
           OR LOWER(OPERATOR_ID) = LOWER(?) 
           OR LOWER(OPERATOR_NAME) = LOWER(?) 
           OR ( ? > 0 AND KOTID = ? )
           OR REPLACE(LOWER(OPERATOR_ID), '-', '') = LOWER(?) 
        LIMIT 1
    ");
    if ($op_stmt) {
        $op_stmt->bind_param("sssiss", $query, $op_clean, $query, $int_val, $int_val, $op_clean);
        $op_stmt->execute();
        $op_res = $op_stmt->get_result();
        if ($op_res && $op_row = $op_res->fetch_assoc()) {
            $_SESSION['active_operator'] = [
                'id'   => $op_row['OPERATOR_ID'],
                'name' => $op_row['OPERATOR_NAME'],
                'kotid'=> $op_row['KOTID']
            ];
            echo json_encode([
                'success' => true,
                'type'    => 'operator',
                'data'    => [
                    'OPERATOR_ID'   => $op_row['OPERATOR_ID'],
                    'OPERATOR_NAME' => $op_row['OPERATOR_NAME'],
                    'KOTID'         => $op_row['KOTID']
                ]
            ]);
            $op_stmt->close();
            exit();
        }
        $op_stmt->close();
    }

    // 2. Try matching Roll Number / Card ID / PO / Style
    $clean_id = intval(preg_replace('/[^0-9]/', '', $query));

    $roll_stmt = $db->prepare("
        SELECT kc.* FROM knit_card kc
        WHERE kc.ROLL = ? 
           OR kc.KCTID = ? 
           OR kc.PO_NUMBER = ? 
           OR kc.SONO = ? 
           OR kc.STYLE LIKE ?
        ORDER BY kc.KCTID DESC LIMIT 1
    ");
    if ($roll_stmt) {
        $search_param = '%' . $query . '%';
        $roll_stmt->bind_param("sisss", $query, $clean_id, $query, $query, $search_param);
        $roll_stmt->execute();
        $roll_res = $roll_stmt->get_result();
        if ($roll_res && $row = $roll_res->fetch_assoc()) {
            $kcid = intval($row['KCTID']);
            $display_roll = !empty($row['ROLL']) ? $row['ROLL'] : ((strlen($query) > 5) ? $query : ('R-' . $kcid));

            echo json_encode([
                'success' => true,
                'type'    => 'roll',
                'data'    => [
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
                    'suggested_roll'   => $display_roll,
                    'suggested_weight' => floatval($row['QTY']) > 0 ? floatval($row['QTY']) : 25.00
                ]
            ]);
            $roll_stmt->close();
            exit();
        }
        $roll_stmt->close();
    }

    echo json_encode(['success' => false, 'error' => 'No Operator or Roll found matching "' . htmlspecialchars($query) . '"']);
    exit();
}

// ── ACTION: SWITCH / LOGOUT OPERATOR ──
if (isset($_GET['action']) && $_GET['action'] === 'logout_operator') {
    header('Content-Type: application/json');
    unset($_SESSION['active_operator']);
    echo json_encode(['success' => true]);
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
                        `MCNO`, `MC_DIA`, `CUSTOMER`, `SHIFT`, `YTYPE`, `YCOUNT`, `FTYPE`, `FGSM`, `FDIA`, `O_T`,
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

// Ensure an active operator is ALWAYS initialized by default for any user opening the page
if (!isset($_SESSION['active_operator']) || empty($_SESSION['active_operator']['id'])) {
    $sess_uname = $_SESSION['username'] ?? 'System';
    $op_stmt = $db->prepare("SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator WHERE LOWER(OPERATOR_NAME) = LOWER(?) OR LOWER(OPERATOR_ID) = LOWER(?) LIMIT 1");
    if ($op_stmt) {
        $op_stmt->bind_param("ss", $sess_uname, $sess_uname);
        $op_stmt->execute();
        $op_res = $op_stmt->get_result();
        if ($op_res && $op_row = $op_res->fetch_assoc()) {
            $_SESSION['active_operator'] = [
                'id'   => $op_row['OPERATOR_ID'],
                'name' => $op_row['OPERATOR_NAME']
            ];
        } else {
            // Default to first registered operator in system (Md. Rahim / OP01)
            $first_op = $db->query("SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator ORDER BY KOTID ASC LIMIT 1");
            if ($first_op && $f_row = $first_op->fetch_assoc()) {
                $_SESSION['active_operator'] = [
                    'id'   => $f_row['OPERATOR_ID'],
                    'name' => $f_row['OPERATOR_NAME']
                ];
            } else {
                $_SESSION['active_operator'] = [
                    'id'   => 'OP01',
                    'name' => $sess_uname
                ];
            }
        }
        $op_stmt->close();
    }
}
$active_operator = $_SESSION['active_operator'];

// Pre-load default latest Roll specifications dynamically so page is NEVER empty!
$default_roll_data = null;
$def_card_res = $db->query("SELECT * FROM knit_card ORDER BY KCTID DESC LIMIT 1");
if ($def_card_res && $def_card = $def_card_res->fetch_assoc()) {
    $kcid = intval($def_card['KCTID']);
    $display_roll = !empty($def_card['ROLL']) ? $def_card['ROLL'] : ("R-" . $kcid);
    
    $default_roll_data = [
        'card_id'          => $kcid,
        'kptid'            => intval($def_card['KPTID']),
        'buyer'            => $def_card['BUYER'] ?: 'N/A',
        'style'            => $def_card['STYLE'] ?: 'N/A',
        'sono'             => $def_card['SONO'] ?: 'N/A',
        'booking'          => $def_card['PO_NUMBER'] ?: 'N/A',
        'mcno'             => $def_card['MCNO'] ?: 'N/A',
        'finish_dia'       => $def_card['FDIA'] ?: 'N/A',
        'finish_gsm'       => $def_card['FGSM'] ?: 'N/A',
        'fabrics_type'     => $def_card['FTYPE'] ?: 'N/A',
        'yarn_type'        => $def_card['YTYPE'] ?: 'N/A',
        'yarn_count'       => $def_card['YCOUNT'] ?: 'N/A',
        'lot_no'           => $def_card['LOT'] ?: 'N/A',
        'req_qty'          => floatval($def_card['QTY']),
        'suggested_roll'   => $display_roll,
        'suggested_weight' => floatval($def_card['QTY']) > 0 ? floatval($def_card['QTY']) : 25.00
    ];
}
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
        <span id="step-title-text"><?php echo $active_operator ? 'Step 2: Scan Roll QR' : 'Step 1: Scan Operator ID QR'; ?></span>
        <div id="op-header-badge-container" style="margin-left: auto; display: flex; align-items: center;">
          <?php if ($active_operator): ?>
            <span style="font-size: 0.75rem; background: #10b981; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700;">
              <i class="fa-solid fa-user-check me-1"></i> <?php echo htmlspecialchars($active_operator['name']); ?> (<?php echo htmlspecialchars($active_operator['id']); ?>)
            </span>
            <button type="button" onclick="logoutOperator()" style="margin-left: 8px; background:#ef4444; border:none; color:white; font-size:0.7rem; padding:3px 10px; border-radius:20px; cursor:pointer; font-weight:700;">Switch</button>
          <?php else: ?>
            <span style="font-size: 0.75rem; background: #f59e0b; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700;">Operator Auth Required</span>
          <?php endif; ?>
        </div>
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
  <script>
    (function() {
      "use strict";

      const resultContainer = document.getElementById('result-content');
      const actionContainer = document.getElementById('action-content');
      const cameraStatus    = document.getElementById('camera-status');
      const toggleCameraBtn = document.getElementById('toggle-camera-btn');
      
      const isOperatorActive = <?php echo $active_operator ? 'true' : 'false'; ?>;
      let activeOperatorInfo = <?php echo json_encode($active_operator); ?>;
      const initialRollData  = <?php echo json_encode($default_roll_data); ?>;
      
      let html5QrCode = null;
      let isScanning    = false;
      let rollData      = null;
      let selectedFaults = {};
      let scanCooldown   = false;

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
        updateOperatorHeaderUI();
        if (initialRollData) {
          rollData = initialRollData;
          renderInspectionForm(initialRollData);
        } else {
          renderStep2RollScan();
        }
      }

      function updateOperatorHeaderUI() {
        const titleText = document.getElementById('step-title-text');
        const headerContainer = document.getElementById('op-header-badge-container');
        if (isOperatorActive && activeOperatorInfo) {
          if (titleText) titleText.textContent = 'Knitting Fabric Inspection';
          if (headerContainer) {
            headerContainer.innerHTML = `
              <span style="font-size: 0.75rem; background: #10b981; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700;">
                <i class="fa-solid fa-user-check me-1"></i> ${esc(activeOperatorInfo.name)} (${esc(activeOperatorInfo.id)})
              </span>
              <button type="button" onclick="logoutOperator()" style="margin-left: 8px; background:#ef4444; border:none; color:white; font-size:0.7rem; padding:3px 10px; border-radius:20px; cursor:pointer; font-weight:700;">Switch</button>
            `;
          }
        } else {
          if (titleText) titleText.textContent = 'Knitting Fabric Inspection';
          if (headerContainer) {
            headerContainer.innerHTML = `
              <span style="font-size: 0.75rem; background: #f59e0b; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700;">Operator Auth Required</span>
            `;
          }
        }
      }

      function handleSmartScan(val) {
        val = String(val || '').trim();
        if (!val) return;

        const msgDiv = document.getElementById('scanStatusMsg') || document.getElementById('rollStatusMsg') || document.getElementById('opStatusMsg');
        if (msgDiv) msgDiv.innerHTML = '<div style="color:#2563eb; font-weight:700; font-size:0.85rem;"><i class="fas fa-spinner fa-spin"></i> Processing Scanned QR Code...</div>';

        fetch('knitting_inspection.php?action=smart_scan&query=' + encodeURIComponent(val))
          .then(r => r.json())
          .then(res => {
            if (res.success && res.data) {
              if (res.type === 'operator') {
                activeOperatorInfo = {
                  id: res.data.OPERATOR_ID,
                  name: res.data.OPERATOR_NAME
                };
                updateOperatorHeaderUI();
                if (msgDiv) msgDiv.innerHTML = `<div style="color:#10b981; font-weight:700; font-size:0.85rem;"><i class="fas fa-check-circle"></i> Operator Authenticated: ${esc(res.data.OPERATOR_NAME)} (${esc(res.data.OPERATOR_ID)})</div>`;
              } else if (res.type === 'roll') {
                rollData = res.data;
                renderInspectionForm(res.data);
                const newMsg = document.getElementById('scanStatusMsg');
                if (newMsg) newMsg.innerHTML = `<div style="color:#10b981; font-weight:700; font-size:0.85rem;"><i class="fas fa-check-circle"></i> Loaded Roll #${esc(res.data.suggested_roll)}</div>`;
              }
            } else {
              if (msgDiv) msgDiv.innerHTML = `<div style="color:#ef4444; font-weight:700; font-size:0.85rem;"><i class="fas fa-times-circle"></i> ${res.error || 'No matching Operator or Roll found'}</div>`;
            }
          })
          .catch(err => {
            if (msgDiv) msgDiv.innerHTML = `<div style="color:#ef4444; font-weight:700; font-size:0.85rem;"><i class="fas fa-exclamation-triangle"></i> Network or server error</div>`;
          });
      }

      function submitOperator(val) { handleSmartScan(val); }
      function submitRollScan(val) { handleSmartScan(val); }

      function renderStep2RollScan() {
        let html = `
          <div class="manual-entry">
            <input type="text" id="rollInput" placeholder="Scan Roll QR Code or Operator Badge..." autocomplete="off" autofocus>
            <button type="button" id="rollBtn">Scan / Search</button>
          </div>
          <div class="data-row header-row"><span class="label">Roll Selection</span><span class="value"></span></div>
          <div class="data-row"><span class="label">Active Operator:</span><span class="value" style="color:#10b981; font-weight:700;">${activeOperatorInfo ? (activeOperatorInfo.name + ' (' + activeOperatorInfo.id + ')') : 'Default'}</span></div>
          <div class="data-row"><span class="label">Action:</span><span class="value">Scan Roll QR Code</span></div>
          <div id="rollStatusMsg" style="margin-top:8px;"></div>
        `;
        resultContainer.innerHTML = html;

        const inp = document.getElementById('rollInput');
        const btn = document.getElementById('rollBtn');
        if (btn) btn.addEventListener('click', () => handleSmartScan(inp.value));
        if (inp) inp.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') { e.preventDefault(); handleSmartScan(inp.value); }
        });
      }

      // RENDER FULL INSPECTION FORM MATRIX (knitting_production Style)
      function renderInspectionForm(d) {
        selectedFaults = {};
        
        let html = `
          <div class="manual-entry" style="margin-bottom:12px;">
            <input type="text" id="smartInput" placeholder="Scan / Type Roll QR or Operator QR..." autocomplete="off">
            <button type="button" id="smartBtn">Scan / Search</button>
          </div>
          <div id="scanStatusMsg" style="margin-bottom:10px;"></div>

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

        const sinp = document.getElementById('smartInput');
        const sbtn = document.getElementById('smartBtn');
        if (sbtn) sbtn.addEventListener('click', () => handleSmartScan(sinp.value));
        if (sinp) sinp.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') { e.preventDefault(); handleSmartScan(sinp.value); }
        });

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
          .then(() => {
            activeOperatorInfo = null;
            updateOperatorHeaderUI();
          });
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
        if (scanCooldown) return;
        const text = String(decodedText || '').trim();
        if (!text) return;

        scanCooldown = true;
        setTimeout(() => { scanCooldown = false; }, 2000);

        handleSmartScan(text);
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
</body>

</html>
