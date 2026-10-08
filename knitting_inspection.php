<?php
// knitting_inspection.php - 2-Step Operator Authentication & Roll Scanner Fabric Inspection Module (knitting_production UI Match)
session_start();
include 'config.php';

// Auto-migration: Ensure YBRAND column exists in knitting_inspection table
if (isset($db) && $db) {
    $col_check = @mysqli_query($db, "SHOW COLUMNS FROM knitting_inspection LIKE 'YBRAND'");
    if ($col_check && mysqli_num_rows($col_check) == 0) {
        @mysqli_query($db, "ALTER TABLE knitting_inspection ADD COLUMN YBRAND varchar(100) NULL AFTER YCOUNT");
    }
}

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

// Start every new page visit with operator authentication.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_GET['action'])) {
  unset($_SESSION['active_operator']);
}

// ── ACTION: VERIFY QC OPERATOR QR CODE / ID ──
// Only authenticated QC Operators from knitting_operator_qc are authorized
// to conduct fabric inspection. Standard operators from knitting_operator are rejected.
if (isset($_GET['action']) && ($_GET['action'] === 'verify_operator' || $_GET['action'] === 'verify_qc')) {
    header('Content-Type: application/json');
    $qc_input = trim($_GET['operator_id'] ?? $_GET['qc_id'] ?? $_GET['query'] ?? '');

    if (empty($qc_input)) {
        echo json_encode(['success' => false, 'error' => 'QC Operator ID is required']);
        exit();
    }

    // ── 1) Dynamic verification against knitting_operator_qc table ──
    $qc_stmt = $db->prepare("
        SELECT KQCTID, KNITTING_QC_ID, KNITTING_QC_NAME, KNITTING_QC_EMAIL
        FROM knitting_operator_qc
        WHERE LOWER(TRIM(KNITTING_QC_ID)) = LOWER(TRIM(?))
           OR LOWER(TRIM(KNITTING_QC_NAME)) = LOWER(TRIM(?))
        LIMIT 1
    ");
    if (!$qc_stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $db->error]);
        exit();
    }

    $qc_stmt->bind_param("ss", $qc_input, $qc_input);
    if (!$qc_stmt->execute()) {
        error_log('QC verification failed: ' . $qc_stmt->error);
        $qc_stmt->close();
        echo json_encode(['success' => false, 'error' => 'QC verification database error']);
        exit();
    }

    $qc_res = $qc_stmt->get_result();
    if ($qc_res && $qc_row = $qc_res->fetch_assoc()) {
        $_SESSION['active_operator'] = [
            'id'         => $qc_row['KNITTING_QC_ID'],
            'name'       => $qc_row['KNITTING_QC_NAME'],
            'email'      => $qc_row['KNITTING_QC_EMAIL'],
            'kotid'      => $qc_row['KQCTID'],
            'kqctid'     => $qc_row['KQCTID'],
            'role'       => 'qc',
            'role_title' => 'Knitting QC'
        ];
        echo json_encode([
            'success' => true,
            'data'    => [
                'OPERATOR_ID'      => $qc_row['KNITTING_QC_ID'],
                'OPERATOR_NAME'    => $qc_row['KNITTING_QC_NAME'],
                'KNITTING_QC_ID'   => $qc_row['KNITTING_QC_ID'],
                'KNITTING_QC_NAME' => $qc_row['KNITTING_QC_NAME'],
                'KOTID'            => $qc_row['KQCTID'],
                'KQCTID'           => $qc_row['KQCTID'],
                'ROLE'             => 'Knitting QC',
                'ROLE_TITLE'       => 'QC Operator'
            ]
        ]);
        $qc_stmt->close();
        exit();
    }
    $qc_stmt->close();

    // ── 2) Check if this is a standard knitting operator to give an informative error ──
    $op_stmt = $db->prepare("
        SELECT OPERATOR_ID, OPERATOR_NAME
        FROM knitting_operator
        WHERE LOWER(TRIM(OPERATOR_ID)) = LOWER(TRIM(?))
           OR LOWER(TRIM(OPERATOR_NAME)) = LOWER(TRIM(?))
        LIMIT 1
    ");
    if ($op_stmt) {
        $op_stmt->bind_param("ss", $qc_input, $qc_input);
        $op_stmt->execute();
        $op_res = $op_stmt->get_result();
        if ($op_res && $op_row = $op_res->fetch_assoc()) {
            $op_stmt->close();
            echo json_encode([
                'success' => false,
                'error'   => 'Access Denied: "' . htmlspecialchars($op_row['OPERATOR_NAME']) . ' (' . htmlspecialchars($op_row['OPERATOR_ID']) . ')" is a Knitting Operator, not a QC Operator. Only authorized QC Operators from "Knitting All QC" can perform fabric inspections.'
            ]);
            exit();
        }
        $op_stmt->close();
    }

    // ── 3) Neither QC nor Operator: Invalid ID ──
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid QC Operator ID: "' . htmlspecialchars($qc_input) . '". Only valid QC Operators from Knitting All QC are authorized.'
    ]);
    exit();
}

// ── ACTION: SWITCH / LOGOUT QC OPERATOR ──
if (isset($_GET['action']) && ($_GET['action'] === 'logout_operator' || $_GET['action'] === 'logout_qc')) {
    header('Content-Type: application/json');
    unset($_SESSION['active_operator']);
    echo json_encode(['success' => true]);
    exit();
}

// ── ACTION: FETCH PRODUCTION ROLL DETAILS ──
if (isset($_GET['action']) && $_GET['action'] === 'search_card') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['active_operator']) || empty($_SESSION['active_operator']['id']) || ($_SESSION['active_operator']['role'] ?? '') !== 'qc') {
        echo json_encode(['success' => false, 'error' => 'Please scan QC Operator ID first!']);
        exit();
    }

    $query = trim($_GET['query'] ?? $_GET['roll'] ?? '');
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Roll number or Card ID is required']);
        exit();
    }

    $sql = "SELECT p.PID, p.BUDAT, p.ROLL, p.KNITCARD, p.PO_NUMBER, p.PQTY, p.SONO, p.BUYER, p.STYLE, p.COLOR,
             p.MCNO, p.MC_DIA, p.CUSTOMER, p.SHIFT, p.YARN_TYPE, p.YARN_COUNT,
             p.FABRICS_TYPE, p.FINISH_GSM, p.FINISH_DIA, p.OPEN_TUBE, p.SL_VDQ,
             p.FEEDER_PLAN, p.LOT_NO, p.KNIT_MATERIAL_CODE,
             p.KNIT_M_DES, p.UNAME, p.UID,
             COALESCE(
                 (SELECT NULLIF(TRIM(k.YBRAND), '') FROM knit_card k WHERE TRIM(k.KNITCARD) = TRIM(p.KNITCARD) LIMIT 1),
                 (SELECT NULLIF(TRIM(prg.YBRAND), '') FROM knitting_program prg WHERE TRIM(prg.PO_NUMBER) = TRIM(p.PO_NUMBER) LIMIT 1)
             ) AS YARN_BRAND
        FROM knitting_production p
        WHERE TRIM(p.ROLL) = ?
        ORDER BY p.PID DESC LIMIT 1";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
      echo json_encode(['success' => false, 'error' => 'Unable to search production rolls: ' . $db->error]);
      exit();
    }
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $row = $res->fetch_assoc()) {
        // ── DUPLICATE ROLL GUARD: one roll can be inspected only once ──
        $roll_val = trim($row['ROLL']);
        $dup_stmt = $db->prepare("SELECT KITID, BUDAT, UNAME FROM knitting_inspection WHERE TRIM(ROLL) = ? LIMIT 1");
        if ($dup_stmt) {
            $dup_stmt->bind_param("s", $roll_val);
            $dup_stmt->execute();
            $dup_res = $dup_stmt->get_result();
            if ($dup_res && $dup_row = $dup_res->fetch_assoc()) {
                echo json_encode([
                    'success' => false,
                    'error'   => 'Roll "' . htmlspecialchars($roll_val) . '" is already inspected! One roll cannot be inspected twice. (Inspected on: ' . htmlspecialchars($dup_row['BUDAT']) . ' by ' . htmlspecialchars($dup_row['UNAME']) . ')',
                    'duplicate' => true
                ]);
                $dup_stmt->close();
                $stmt->close();
                exit();
            }
            $dup_stmt->close();
        }

        echo json_encode([
            'success'          => true,
            'data'             => [
            'production_id'   => intval($row['PID']),
            'production_date' => $row['BUDAT'],
                'buyer'            => $row['BUYER'] ?: 'N/A',
                'style'            => $row['STYLE'] ?: 'N/A',
                'sono'             => $row['SONO'] ?: 'N/A',
                'booking'          => $row['PO_NUMBER'] ?: 'N/A',
                'mcno'             => $row['MCNO'] ?: 'N/A',
            'mc_dia'           => $row['MC_DIA'] ?: 'N/A',
            'finish_dia'       => $row['FINISH_DIA'] ?: 'N/A',
            'finish_gsm'       => $row['FINISH_GSM'] ?: 'N/A',
            'fabrics_type'     => $row['FABRICS_TYPE'] ?: 'N/A',
            'yarn_type'        => $row['YARN_TYPE'] ?: 'N/A',
            'yarn_count'       => $row['YARN_COUNT'] ?: 'N/A',
            'yarn_brand'       => $row['YARN_BRAND'] ?: 'N/A',
            'lot_no'           => $row['LOT_NO'] ?: 'N/A',
            'color'            => $row['COLOR'] ?: 'N/A',
            'customer'         => $row['CUSTOMER'] ?: 'N/A',
            'shift'            => $row['SHIFT'] ?: 'N/A',
            'open_tube'        => $row['OPEN_TUBE'] ?: 'N/A',
            'sl_vdq'           => $row['SL_VDQ'] ?: 'N/A',
            'feeder_plan'      => $row['FEEDER_PLAN'] ?: 'N/A',
            'material_code'    => $row['KNIT_MATERIAL_CODE'] ?: 'N/A',
            'material_desc'    => $row['KNIT_M_DES'] ?: 'N/A',
            'production_qty'   => floatval($row['PQTY']),
            'suggested_roll'   => $row['ROLL'],
            'suggested_weight' => floatval($row['PQTY']) > 0 ? floatval($row['PQTY']) : 25.00
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No production data found for Roll "' . htmlspecialchars($query) . '"']);
    }
    $stmt->close();
    exit();
}

$error = '';
$msg = '';

// ── SAVE INSPECTION RECORD ──
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_inspection'])) {
    if (!isset($_SESSION['active_operator']) || empty($_SESSION['active_operator']['id']) || ($_SESSION['active_operator']['role'] ?? '') !== 'qc') {
        $error = "Unauthorized: You must authenticate with a valid QC Operator ID QR Code before completing fabric inspection.";
    } else {
        $production_id       = intval($_POST['PRODUCTION_ID'] ?? 0);
        $roll_no             = trim($_POST['ROLL_NO'] ?? '');
        $main_qty            = floatval($_POST['MAIN_QTY'] ?? 0);
        $reject_qty          = floatval($_POST['REJECT_QTY'] ?? 0);
        $update_qty          = max(0, $main_qty - $reject_qty);
        
        $card_meta = [];
        if ($production_id > 0) {
          $c_q = $db->prepare("SELECT p.*,
                    COALESCE(
                        (SELECT NULLIF(TRIM(k.YBRAND), '') FROM knit_card k WHERE TRIM(k.KNITCARD) = TRIM(p.KNITCARD) LIMIT 1),
                        (SELECT NULLIF(TRIM(prg.YBRAND), '') FROM knitting_program prg WHERE TRIM(prg.PO_NUMBER) = TRIM(p.PO_NUMBER) LIMIT 1)
                    ) AS YARN_BRAND
                FROM knitting_production p WHERE p.PID = ?");
            if ($c_q) {
                $c_q->bind_param("i", $production_id);
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

        if ($production_id <= 0 && empty($roll_no)) {
          $error = "Please select a valid production roll or enter Roll Number.";
        } elseif (empty($roll_no)) {
            $error = "Roll Number is required.";
        } elseif ($main_qty <= 0) {
            $error = "Main Quantity must be greater than 0.";
        } else {
            // ── DUPLICATE ROLL GUARD (server-side safety net) ──
            $dup2_stmt = $db->prepare("SELECT KITID FROM knitting_inspection WHERE TRIM(ROLL) = ? LIMIT 1");
            if ($dup2_stmt) {
                $dup2_stmt->bind_param("s", $roll_no);
                $dup2_stmt->execute();
                $dup2_res = $dup2_stmt->get_result();
                if ($dup2_res && $dup2_res->num_rows > 0) {
                    $error = "Roll #$roll_no is already inspected! One roll cannot be inspected twice.";
                }
                $dup2_stmt->close();
            }
        }

        if (empty($error)) {
          try {
            $stmt = $db->prepare("
                    INSERT INTO knitting_inspection (
                        `BUDAT`, `ROLL`, `OQTY`, `RQTY`, `UQTY`, `PO_NUMBER`, `QTY`, `SONO`, `BUYER`, `STYLE`, `COLOR`,
                        `MCNO`, `MC_DIA`, `CUSTOMER`, `SHIFT`, `YTYPE`, `YCOUNT`, `YBRAND`, `FTYPE`, `FGSM`, `FDIA`, `O_T`,
                        `SL`, `FPLAN`, `LOTNO`, `MATERIAL_CODE`, `M_DES`,
                        `TT`, `PATTA`, `SLUB`, `YC_SPOT`, `OILSPOT`, `FF`, `SEEDS`, `MSTITCH`, `SINKERMARK`, `NEEDLEMARK`,
                        `LYCOUT`, `OILLINE`, `HOLE`, `LOOP`, `SETUP`, `CMARK`, `TPOINT`,
                        `QC_GRADE`, `QC_STATUS`, `UNAME`, `UID`
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?
                    )
                ");
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $db->error);
                }
                
                $budat       = date('Y-m-d');
                $v_main_qty  = $main_qty;
                $v_reject    = $reject_qty;
                $v_update    = $update_qty;
                $po_number   = strval($card_meta['PO_NUMBER'] ?? '');
                $qty         = strval($main_qty);
                $sono        = strval($card_meta['SONO'] ?? '');
                $buyer       = strval($card_meta['BUYER'] ?? '');
                $style       = strval($card_meta['STYLE'] ?? '');
                $color       = strval($card_meta['COLOR'] ?? '');
                $mcno        = strval($card_meta['MCNO'] ?? '');
                $mc_dia      = strval($card_meta['MC_DIA'] ?? '');
                $supplier    = strval($card_meta['CUSTOMER'] ?? '');
                $shift       = strval($card_meta['SHIFT'] ?? '');
                $ytype       = strval($card_meta['YARN_TYPE'] ?? '');
                $ycount      = strval($card_meta['YARN_COUNT'] ?? '');
                $ybrand      = strval($card_meta['YARN_BRAND'] ?? ($card_meta['YBRAND'] ?? ''));
                $ftype       = strval($card_meta['FABRICS_TYPE'] ?? '');
                $fgsm        = strval($card_meta['FINISH_GSM'] ?? '');
                $fdia        = strval($card_meta['FINISH_DIA'] ?? '');
                $o_t         = strval($card_meta['OPEN_TUBE'] ?? '');
                $sl          = floatval($card_meta['SL_VDQ'] ?? 0.00);
                $fplan       = strval($card_meta['FEEDER_PLAN'] ?? '');
                $lotno       = strval($card_meta['LOT_NO'] ?? '');
                $mat_code    = strval($card_meta['KNIT_MATERIAL_CODE'] ?? '');
                $m_des       = strval($card_meta['KNIT_M_DES'] ?? '');

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

                // BUDAT(s), ROLL(s), OQTY(d), RQTY(d), UQTY(d),
                // PO_NUMBER..O_T = 17 strings(s), SL(d), FPLAN..M_DES = 4 strings(s),
                // defects 17 strings(s), QC_GRADE..UID = 4 strings(s)
                $types = 'ssddd' . str_repeat('s', 17) . 'd' . str_repeat('s', 4)
                       . str_repeat('s', 17) . str_repeat('s', 4);

                $stmt->bind_param(
                    $types,
                    $budat, $roll_no, $v_main_qty, $v_reject, $v_update,
                    $po_number, $qty, $sono, $buyer, $style, $color,
                    $mcno, $mc_dia, $supplier, $shift, $ytype, $ycount, $ybrand, $ftype, $fgsm, $fdia, $o_t,
                    $sl, $fplan, $lotno, $mat_code, $m_des,
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
                if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'uniq_roll') !== false) {
                    $error = "Roll #$roll_no is already inspected! One roll cannot be inspected twice.";
                } else {
                    $error = "Database Error: " . $e->getMessage();
                }
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
if ($active_operator && ($active_operator['role'] ?? '') !== 'qc') {
    unset($_SESSION['active_operator']);
    $active_operator = null;
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
      padding: 10px;
    }

    .card {
      max-width: 520px;
      width: 100%;
      background: #ffffff;
      border-radius: 18px;
      padding: 14px 16px 18px;
      box-shadow: 0 10px 25px rgba(30, 60, 120, 0.12);
      border: 1px solid #dbe4ef;
      transition: max-width 0.3s ease;
    }

    .card.card-wide {
      max-width: 1100px;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-top: 6px;
      margin-bottom: 12px;
    }

    @media (max-width: 992px) {
      .info-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 576px) {
      .info-grid {
        grid-template-columns: 1fr;
      }
    }

    .info-item {
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-left: 3px solid #2563eb;
      border-radius: 8px;
      padding: 6px 10px;
      font-size: 0.8rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-height: 48px;
    }

    .info-item .info-label {
      font-size: 0.68rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      margin-bottom: 2px;
      letter-spacing: 0.2px;
    }

    .info-item .info-val {
      font-size: 0.85rem;
      font-weight: 700;
      color: #0f172a;
      word-break: break-word;
    }

    .qty-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-top: 8px;
      margin-bottom: 12px;
    }

    @media (max-width: 600px) {
      .qty-grid {
        grid-template-columns: 1fr;
      }
    }

    .production-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 10px;
      padding: 0 2px;
    }

    .production-header h2 {
      color: #083a36;
      font-size: 1.15rem;
      font-weight: 800;
      letter-spacing: 0.3px;
      margin: 0;
    }

    .production-header h2 i {
      color: #0f7a6f;
      margin-right: 6px;
    }

    .production-header .badge-production {
      margin-left: auto;
      background: #10b981;
      color: white;
      font-size: 0.65rem;
      padding: 2px 10px;
      border-radius: 100px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }

    .scanner-container {
      position: relative;
      background: #eef2f7;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px #d7e0ea, 0 4px 12px rgba(30, 60, 120, 0.1);
      margin-bottom: 10px;
      max-height: 200px;
      aspect-ratio: 16 / 9;
    }

    #qr-reader {
      width: 100%;
      height: 100%;
      padding: 0 !important;
      background: #f4f7fb;
    }

    #qr-reader video {
      border-radius: 14px;
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
      border-radius: 14px;
      box-shadow: inset 0 0 0 2px rgba(0, 255, 200, 0.3);
    }

    .scan-overlay::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 65%;
      height: 65%;
      transform: translate(-50%, -50%);
      border: 2px solid rgba(0, 255, 200, 0.5);
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0, 255, 200, 0.1);
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
      margin-top: 6px;
      padding: 0 2px;
    }

    .status-badge {
      background: #eef2f7;
      padding: 4px 12px;
      border-radius: 100px;
      color: #334155;
      font-size: 0.75rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
      border: 1px solid #cbd5e1;
    }

    .status-badge i {
      color: #2563eb;
      font-size: 0.8rem;
    }

    .btn-icon {
      background: #eef2f7;
      border: 1px solid #cbd5e1;
      color: #334155;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      font-size: 0.9rem;
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
      border-radius: 14px;
      padding: 10px 12px;
      margin-top: 10px;
      border: 1px solid #d7e0ea;
      box-shadow: inset 0 2px 4px rgba(30, 60, 120, 0.04);
    }

    .result-header {
      display: flex;
      align-items: center;
      gap: 6px;
      color: #334155;
      font-weight: 700;
      letter-spacing: 0.2px;
      font-size: 0.82rem;
      border-bottom: 1px dashed #cbd5e1;
      padding-bottom: 6px;
      margin-bottom: 8px;
    }

    .result-header i {
      color: #2563eb;
    }

    .data-row {
      background: #eef2f7;
      padding: 4px 10px;
      border-radius: 6px;
      border-left: 3px solid #2563eb;
      color: #1e293b;
      font-size: 0.78rem;
      line-height: 1.3;
      box-shadow: 0 1px 3px rgba(30, 60, 120, 0.05);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 3px;
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
      font-size: 0.82rem;
    }

    .manual-entry {
      display: flex;
      gap: 6px;
      margin-top: 6px;
      margin-bottom: 8px;
    }

    .manual-entry input {
      flex: 1;
      min-width: 0;
      padding: 6px 10px;
      border: 1px solid #cbd5e1;
      border-radius: 12px;
      font-size: 0.8rem;
      outline: none;
      background: #ffffff;
      color: #0f172a;
    }

    .manual-entry input:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .manual-entry button {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: #ffffff;
      border: none;
      padding: 6px 14px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.8rem;
      cursor: pointer;
      white-space: nowrap;
      transition: 0.2s;
    }

    .manual-entry button:hover {
      filter: brightness(1.1);
    }

    .fault-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));
      gap: 5px;
      margin-top: 6px;
      margin-bottom: 10px;
    }

    .fault-btn {
      background: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      padding: 5px 6px;
      font-size: 0.7rem;
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
      box-shadow: 0 1px 5px rgba(239, 68, 68, 0.2);
    }

    .field-input {
      width: 100%;
      background: #ffffff;
      border: 1px solid #cbd5e1;
      color: #0f172a;
      border-radius: 8px;
      padding: 6px 10px;
      font-size: 0.85rem;
      outline: none;
    }

    .field-input:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .action-content {
      margin-top: 10px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .action-card {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
      align-items: center;
      background: #eef2f7;
      border: 1px solid #d7e0ea;
      border-radius: 12px;
      padding: 8px;
    }

    .btn-action {
      flex: 1 1 100px;
      min-width: 100px;
      border: none;
      border-radius: 10px;
      padding: 8px 12px;
      font-size: 0.85rem;
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
        <span id="step-title-text"><?php echo $active_operator ? 'Step 2: Scan Roll QR' : 'QC Operator ID'; ?></span>
        <div id="op-header-badge-container" style="margin-left: auto; display: flex; align-items: center;">
          <?php if ($active_operator): ?>
            <span style="font-size: 0.75rem; background: #10b981; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700; display:inline-flex; align-items:center;">
              <i class="fa-solid fa-user-shield me-1"></i> <?php echo htmlspecialchars($active_operator['name']); ?> (<?php echo htmlspecialchars($active_operator['id']); ?>)
              <span style="background:#0284c7; margin-left:6px; font-size:0.65rem; padding:1px 6px; border-radius:10px;">QC</span>
            </span>
            <button type="button" onclick="logoutOperator()" style="margin-left: 8px; background:#ef4444; border:none; color:white; font-size:0.7rem; padding:3px 10px; border-radius:20px; cursor:pointer; font-weight:700;">Switch QC</button>
          <?php else: ?>
            <span style="font-size: 0.75rem; background: #f59e0b; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700; display:inline-flex; align-items:center;">
              <i class="fa-solid fa-shield-halved me-1"></i> Operator Auth Required
            </span>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($msg)): ?>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Inspection Completed Successfully!',
                text: <?php echo json_encode($msg); ?>,
                confirmButtonColor: '#10b981',
                confirmButtonText: '<i class="fa-solid fa-check me-1"></i> OK / Next Scan',
                timer: 5000,
                timerProgressBar: true
              });
            }
          });
        </script>
        <div style="background:#142f1f; border:1px solid #166534; color:#c7f6d1; padding:10px 14px; border-radius:12px; margin-bottom:12px; font-weight:600; font-size:0.88rem;">
          <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Inspection Alert',
                text: <?php echo json_encode($error); ?>,
                confirmButtonColor: '#ef4444'
              });
            }
          });
        </script>
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
    <div class="footer-note" style="margin-top:10px; text-align:center;">
      <button onclick="window.location.href='initialPage.php';"
        style="background-color:#1e3a8a; color:white; padding:8px 14px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:0.85rem; width:100%;">
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
      
      let isOperatorActive = <?php echo $active_operator ? 'true' : 'false'; ?>;
      let activeOperatorInfo = <?php echo json_encode($active_operator); ?>;
      
      let html5QrCode = null;
      let isScanning    = false;
      let verifying     = false;
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

      function hideCameraScanner() {
        const sc = document.getElementById('scannerContainer');
        const cc = document.getElementById('cameraControls');
        const mc = document.getElementById('mainCard');
        if (sc) sc.style.display = 'none';
        if (cc) cc.style.display = 'none';
        if (mc) mc.classList.add('card-wide');
        if (html5QrCode && isScanning) {
          try {
            html5QrCode.stop().then(() => { isScanning = false; }).catch(() => {});
          } catch(e) {}
        }
      }

      function showCameraScanner() {
        const sc = document.getElementById('scannerContainer');
        const cc = document.getElementById('cameraControls');
        const mc = document.getElementById('mainCard');
        if (sc) sc.style.display = 'block';
        if (cc) cc.style.display = 'flex';
        if (mc) mc.classList.remove('card-wide');
        if (!isScanning) {
          startCameraScanner();
        }
      }

      function initView() {
        if (!isOperatorActive) {
          renderStep1Operator();
        } else {
          renderStep2RollScan();
        }
      }

      // STEP 1: QC OPERATOR QR SCAN / AUTHENTICATION
      function renderStep1Operator() {
        showCameraScanner();
        actionContainer.innerHTML = '';
        let html = `
          <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:10px; margin-bottom:10px; text-align:center; color:#1e3a8a;">
            <div style="font-size:1.5rem; margin-bottom:2px; color:#2563eb;"><i class="fa-solid fa-user-shield"></i></div>
            <div style="font-weight:800; font-size:0.95rem; margin-bottom:2px;">QC Operator ID</div>
          </div>
          <div class="manual-entry">
            <input type="text" id="opInput" placeholder="Enter / Scan QC Operator ID" autocomplete="off" autofocus>
            <button type="button" id="opBtn">Authenticate</button>
          </div>
          <div class="data-row header-row"><span class="label">Workflow Progress</span><span class="value">Step 1 of 2</span></div>
          <div class="data-row"><span class="label">Current Action:</span><span class="value" style="color:#2563eb; font-weight:700;">Scan QC Operator QR Code</span></div>
          <div class="data-row"><span class="label">Next Action:</span><span class="value">Scan Roll QR</span></div>
          <div id="opStatusMsg" style="margin-top:8px;"></div>
        `;
        resultContainer.innerHTML = html;

        const inp = document.getElementById('opInput');
        const btn = document.getElementById('opBtn');
        if (btn) btn.addEventListener('click', () => submitOperator(inp.value));
        if (inp) inp.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') { e.preventDefault(); submitOperator(inp.value); }
        });
      }

      function esc(v) {
        if (v === null || v === undefined) return '';
        return String(v).replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"');
      }

      function updateOperatorHeaderUI() {
        const titleText = document.getElementById('step-title-text');
        const headerContainer = document.getElementById('op-header-badge-container');
        if (isOperatorActive && activeOperatorInfo) {
          if (titleText) titleText.textContent = 'Step 2: Scan Roll QR';
          if (headerContainer) {
            headerContainer.innerHTML = `
              <span style="font-size: 0.75rem; background: #10b981; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700; display:inline-flex; align-items:center;">
                <i class="fa-solid fa-user-shield me-1"></i> ${esc(activeOperatorInfo.name)} (${esc(activeOperatorInfo.id)})
                <span style="background:#0284c7; margin-left:6px; font-size:0.65rem; padding:1px 6px; border-radius:10px;">QC</span>
              </span>
              <button type="button" onclick="logoutOperator()" style="margin-left: 8px; background:#ef4444; border:none; color:white; font-size:0.7rem; padding:3px 10px; border-radius:20px; cursor:pointer; font-weight:700;">Switch QC</button>
            `;
          }
        } else {
          if (titleText) titleText.textContent = 'QC Operator ID';
          if (headerContainer) {
            headerContainer.innerHTML = `
              <span style="font-size: 0.75rem; background: #f59e0b; padding: 2px 12px; border-radius: 40px; color: #ffffff; font-weight:700; display:inline-flex; align-items:center;">
                <i class="fa-solid fa-shield-halved me-1"></i> Operator Auth Required
              </span>
            `;
          }
        }
      }

      function submitOperator(val) {
        val = String(val || '').trim();
        if (!val) { alert('Please enter or scan QC Operator ID!'); return; }

        const msgDiv = document.getElementById('opStatusMsg');
        if (msgDiv) msgDiv.innerHTML = '<div style="color:#2563eb; font-weight:700; font-size:0.85rem;"><i class="fas fa-spinner fa-spin"></i> Verifying QC Operator ID...</div>';

        fetch('knitting_inspection.php?action=verify_operator&operator_id=' + encodeURIComponent(val), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
          })
          .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status + ': ' + r.statusText);
            return r.text();
          })
          .then(text => {
            let res;
            try {
              res = JSON.parse(text.replace(/^\uFEFF/, '').trim());
            } catch (e) {
              throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
            return res;
          })
          .then(res => {
            if (res.success && res.data) {
              verifying = false;
              isOperatorActive = true;
              activeOperatorInfo = {
                id: res.data.OPERATOR_ID,
                name: res.data.OPERATOR_NAME,
                role: res.data.ROLE || 'Knitting QC'
              };
              updateOperatorHeaderUI();
              renderStep2RollScan();
            } else {
              verifying = false;
              if (msgDiv) msgDiv.innerHTML = `<div style="color:#ef4444; font-weight:700; font-size:0.85rem;"><i class="fas fa-times-circle"></i> ${res.error || 'Invalid QC Operator ID'}</div>`;
            }
          })
          .catch(err => {
            verifying = false;
            if (msgDiv) msgDiv.innerHTML = `<div style="color:#ef4444; font-weight:700; font-size:0.85rem;"><i class="fas fa-exclamation-triangle"></i> Verification Error: ${err.message}</div>`;
            console.error('QC Operator verification failed:', err);
          });
      }

      // STEP 2: ROLL SCAN & INSPECTION FORM
      function renderStep2RollScan() {
        showCameraScanner();
        let html = `
          <div class="manual-entry">
            <input type="text" id="rollInput" placeholder="Roll QR / Barcode (e.g. 300099903)" autocomplete="off" autofocus>
            <button type="button" id="rollBtn">Load</button>
          </div>
          <div class="data-row header-row"><span class="label">Step 2: Roll Selection</span><span class="value"></span></div>
          <div class="data-row"><span class="label">QC Operator:</span><span class="value" style="color:#10b981; font-weight:700;"><i class="fa-solid fa-user-shield me-1"></i> ${activeOperatorInfo.name} (${activeOperatorInfo.id})</span></div>
          <div class="data-row"><span class="label">Action:</span><span class="value">Scan Roll QR Code</span></div>
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

      // RENDER FULL INSPECTION FORM MATRIX (Wide 4-Row Grid Layout with Auto-Hidden Camera)
      function renderInspectionForm(d) {
        selectedFaults = {};
        hideCameraScanner();
        
        let html = `
          <div class="data-row header-row" style="margin-bottom:8px;"><span class="label">Production Roll Information</span><span class="value">Production #${d.production_id}</span></div>
          
          <div class="info-grid">
            <div class="info-item" style="border-left-color:#2563eb; background:#eff6ff;">
              <div class="info-label">Roll Number</div>
              <div class="info-val" style="color:#2563eb; font-size:1rem; font-weight:800;">${d.suggested_roll}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Production Date</div>
              <div class="info-val">${d.production_date || 'N/A'}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Production Qty</div>
              <div class="info-val">${d.production_qty || 0} KG</div>
            </div>
            <div class="info-item">
              <div class="info-label">PO Number (Booking)</div>
              <div class="info-val">${d.booking || 'N/A'}</div>
            </div>

            <div class="info-item">
              <div class="info-label">SO Number</div>
              <div class="info-val">${d.sono || 'N/A'}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Buyer / Style</div>
              <div class="info-val">${d.buyer} (${d.style})</div>
            </div>
            <div class="info-item">
              <div class="info-label">Color / Customer</div>
              <div class="info-val">${d.color} / ${d.customer}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Machine / Dia / Shift</div>
              <div class="info-val">${d.mcno} / ${d.mc_dia || 'N/A'} / ${d.shift}</div>
            </div>

            <div class="info-item">
              <div class="info-label">Fabric & GSM</div>
              <div class="info-val">${d.fabrics_type} (${d.finish_gsm} GSM)</div>
            </div>
            <div class="info-item">
              <div class="info-label">Finish Dia</div>
              <div class="info-val">${d.finish_dia || 'N/A'}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Yarn Type / Count</div>
              <div class="info-val">${d.yarn_type || 'N/A'} / ${d.yarn_count || 'N/A'}</div>
            </div>
            <div class="info-item" style="border-left-color:#0f7a6f;">
              <div class="info-label">Yarn Brand</div>
              <div class="info-val" style="color:#0f7a6f;">${d.yarn_brand || 'N/A'}</div>
            </div>

            <div class="info-item">
              <div class="info-label">Open Tube / SL-VDQ</div>
              <div class="info-val">${d.open_tube || 'N/A'} / ${d.sl_vdq || 'N/A'}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Lot No / Feeder Plan</div>
              <div class="info-val">${d.lot_no || 'N/A'} / ${d.feeder_plan || 'N/A'}</div>
            </div>
            <div class="info-item" style="grid-column: span 2;">
              <div class="info-label">Material Code / Desc</div>
              <div class="info-val">${d.material_code || 'N/A'} - ${d.material_desc || 'N/A'}</div>
            </div>
          </div>
          
          <div class="qty-grid">
            <div>
              <div style="font-weight:800; font-size:0.78rem; color:#1d4ed8; margin-bottom:4px;">
                <i class="fa-solid fa-weight-hanging me-1"></i> MAIN QTY (KG):
              </div>
              <input type="number" step="0.01" min="0" id="mainQtyInput" class="field-input"
                value="${parseFloat(d.suggested_weight).toFixed(2)}"
                style="font-weight:700; font-size:0.95rem; text-align:center;"
                oninput="window.calcUpdateQty()">
            </div>
            <div>
              <div style="font-weight:800; font-size:0.78rem; color:#b91c1c; margin-bottom:4px;">
                <i class="fa-solid fa-ban me-1"></i> REJECT QTY (KG):
              </div>
              <input type="number" step="0.01" min="0" id="rejectQtyInput" class="field-input"
                value="0" placeholder="0.00"
                style="font-weight:700; font-size:0.95rem; text-align:center; border-color:#fca5a5;"
                oninput="window.calcUpdateQty()">
            </div>
            <div>
              <div style="font-weight:800; font-size:0.78rem; color:#166534; margin-bottom:4px;">
                <i class="fa-solid fa-circle-check me-1"></i> NET GOOD QTY (KG):
              </div>
              <input type="number" step="0.01" id="updateQtyInput" class="field-input"
                value="${parseFloat(d.suggested_weight).toFixed(2)}"
                readonly tabindex="-1"
                style="font-weight:800; font-size:0.95rem; text-align:center; background:#f0fdf4; border-color:#86efac; color:#166534; cursor:not-allowed;">
            </div>
          </div>

          <div style="margin-top:10px; font-weight:800; font-size:0.78rem; color:#334155;">
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
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:8px;">
            <div class="data-row" style="background:#eff6ff; border-left-color:#2563eb; margin:0;">
              <span class="label">Total Points:</span>
              <span class="value" id="calc_points" style="font-size:0.95rem; color:#2563eb; font-weight:800;">0 pts</span>
            </div>
            <div class="data-row" style="background:#f0fdf4; border-left-color:#10b981; margin:0;">
              <span class="label">QC Grade / Status:</span>
              <span class="value" id="calc_grade" style="font-size:0.9rem; color:#166534; font-weight:800;">Grade A (Passed)</span>
            </div>
          </div>
        `;

        resultContainer.innerHTML = html;

        actionContainer.innerHTML = `
          <div class="action-card">
            <button class="btn-action production" id="saveBtn" onclick="window.saveInspectionRecord()">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Inspection Record
            </button>
            <button class="btn-action cancel" onclick="window.location.reload()">
              <i class="fa-solid fa-rotate-left me-1"></i> Reset / Scan New Roll
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

      // ── Real-time UPDATE QTY calculator ──
      window.calcUpdateQty = function() {
        const mainQty   = parseFloat(document.getElementById('mainQtyInput')?.value) || 0;
        const rejectQty = parseFloat(document.getElementById('rejectQtyInput')?.value) || 0;
        const updateQty = Math.max(0, mainQty - rejectQty);
        const updateEl  = document.getElementById('updateQtyInput');
        if (updateEl) updateEl.value = updateQty.toFixed(2);
      };

      window.saveInspectionRecord = function() {
        if (!rollData) return;
        const mainQty   = parseFloat(document.getElementById('mainQtyInput')?.value)   || 0;
        const rejectQty = parseFloat(document.getElementById('rejectQtyInput')?.value) || 0;
        const updateQty = Math.max(0, mainQty - rejectQty);

        if (mainQty <= 0) {
          alert('Please enter a valid Main Quantity (must be > 0).');
          document.getElementById('mainQtyInput')?.focus();
          return;
        }
        if (rejectQty > mainQty) {
          alert('Reject QTY cannot exceed Main QTY.');
          document.getElementById('rejectQtyInput')?.focus();
          return;
        }

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
        addField('PRODUCTION_ID', rollData.production_id || 0);
        addField('ROLL_NO', rollData.suggested_roll);
        addField('MAIN_QTY',   mainQty.toFixed(2));
        addField('REJECT_QTY', rejectQty.toFixed(2));
        addField('UPDATE_QTY', updateQty.toFixed(2));

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
            isOperatorActive = false;
            activeOperatorInfo = null;
            rollData = null;
            updateOperatorHeaderUI();
            renderStep1Operator();
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
          { fps: 10, qrbox: { width: 180, height: 180 } },
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

      function isRollCode(text) {
        const val = String(text).trim();
        return /^\d+$/.test(val) ||
               /^ROLL:\s*\d+$/i.test(val) ||
               val.indexOf('|') !== -1 ||
               (val.startsWith('{') && val.endsWith('}'));
      }

      let lastScannedText = '';
      let lastScanTime = 0;

      function onScanSuccess(decodedText) {
        const now = Date.now();
        const text = String(decodedText || '').trim().replace(/[\r\n]+/g, '');

        if (!text || text === lastScannedText && now - lastScanTime < 1500) {
          return;
        }
        lastScannedText = text;
        lastScanTime = now;

        if (!isOperatorActive) {
          if (isRollCode(text)) {
            alert('Please scan QC Operator ID first!\nProduction roll cannot be scanned before operator authentication.');
            return;
          }
          if (verifying) return;
          verifying = true;
          submitOperator(text);
          return;
        }

        submitRollScan(text);
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
