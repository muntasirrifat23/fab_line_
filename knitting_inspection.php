<?php
// knitting_inspection.php - 2-Step Operator Authentication & Roll Scanner Fabric Inspection Module
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
        echo json_encode(['success' => false, 'message' => 'Operator ID QR Code is empty.']);
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
                'success'       => true,
                'operator_id'   => $row['OPERATOR_ID'],
                'operator_name' => $row['OPERATOR_NAME'],
                'message'       => 'Operator authenticated successfully!'
            ]);
            $stmt->close();
            exit();
        }
        $stmt->close();
    }
    echo json_encode(['success' => false, 'message' => 'Invalid Operator ID: "' . htmlspecialchars($op_id) . '". Inspection access denied.']);
    exit();
}

// ── ACTION: SWITCH / LOGOUT OPERATOR ──
if (isset($_GET['action']) && $_GET['action'] === 'logout_operator') {
    header('Content-Type: application/json');
    unset($_SESSION['active_operator']);
    echo json_encode(['success' => true, 'message' => 'Operator session reset successfully.']);
    exit();
}

// ── INLINE AJAX SCAN/SEARCH ENDPOINT FOR ROLL / CARD ──
if (isset($_GET['action']) && $_GET['action'] === 'search_card') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['active_operator']) || empty($_SESSION['active_operator']['id'])) {
        echo json_encode(['success' => false, 'message' => 'Please scan a valid Operator QR Code first!']);
        exit();
    }

    $query = trim($_GET['query'] ?? '');
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'message' => 'Empty search query']);
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
            'suggested_roll'   => 'R-' . $kcid . '-' . sprintf("%02d", $roll_count),
            'suggested_weight' => floatval($row['QTY']) > 0 ? floatval($row['QTY']) : 25.00
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No Knit Card matching "' . htmlspecialchars($query) . '"']);
    }
    $stmt->close();
    exit();
}

$error = '';
$msg = '';

// Handle form submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_inspection'])) {
    if (!isset($_SESSION['active_operator']) || empty($_SESSION['active_operator']['id'])) {
        $error = "Unauthorized: You must scan a valid Operator ID QR Code before completing fabric inspection.";
    } else {
        $knit_card_id        = intval($_POST['KNIT_CARD_ID'] ?? 0);
        $roll_no             = trim($_POST['ROLL_NO'] ?? '');
        $roll_weight         = floatval($_POST['ROLL_WEIGHT'] ?? 0);
        
        // Fetch card metadata
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

        // 16 Fabric Faults
        $defect_tt          = isset($_POST['DEFECT_TT']) ? 1 : 0;
        $defect_patta       = isset($_POST['DEFECT_PATTA']) ? 1 : 0;
        $defect_slub        = isset($_POST['DEFECT_SLUB']) ? 1 : 0;
        $defect_yc          = isset($_POST['DEFECT_YC']) ? 1 : 0;
        
        $defect_oil_spot    = isset($_POST['DEFECT_OIL_SPOT']) ? 1 : 0;
        $defect_ff          = isset($_POST['DEFECT_FF']) ? 1 : 0;
        $defect_seeds       = isset($_POST['DEFECT_SEEDS']) ? 1 : 0;
        $defect_m_stitch    = isset($_POST['DEFECT_M_STITCH']) ? 1 : 0;

        $defect_sinker_mark = isset($_POST['DEFECT_SINKER_MARK']) ? 1 : 0;
        $defect_needle_mark = isset($_POST['DEFECT_NEEDLE_MARK']) ? 1 : 0;
        $defect_lycra_out   = isset($_POST['DEFECT_LYCRA_OUT']) ? 1 : 0;
        $defect_oil_line    = isset($_POST['DEFECT_OIL_LINE']) ? 1 : 0;

        $defect_hole        = isset($_POST['DEFECT_HOLE']) ? 1 : 0;
        $defect_loop        = isset($_POST['DEFECT_LOOP']) ? 1 : 0;
        $defect_setup       = isset($_POST['DEFECT_SETUP']) ? 1 : 0;
        $defect_crease_mark = isset($_POST['DEFECT_CREASE_MARK']) ? 1 : 0;

        $total_points = ($defect_tt * 1) + ($defect_patta * 1) + ($defect_slub * 1) + ($defect_yc * 1) + 
                        ($defect_oil_spot * 2) + ($defect_ff * 2) + ($defect_seeds * 2) + ($defect_m_stitch * 2) + 
                        ($defect_sinker_mark * 3) + ($defect_needle_mark * 3) + ($defect_lycra_out * 3) + 
                        ($defect_oil_line * 3) + ($defect_hole * 4) + ($defect_loop * 4) + ($defect_setup * 4) + 
                        ($defect_crease_mark * 4);

        $qc_grade     = trim($_POST['QC_GRADE'] ?? '');
        $qc_status    = trim($_POST['QC_STATUS'] ?? '');
        $remarks      = trim($_POST['REMARKS'] ?? '');

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
                $msg = "Inspection record for Roll #$roll_no saved successfully by Operator " . htmlspecialchars($uname) . " (" . htmlspecialchars($uid) . ")!";
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

// Fetch registered Operators for selection grid
$registered_operators = [];
$op_res = $db->query("SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator ORDER BY OPERATOR_ID ASC");
if ($op_res) {
    while ($op = $op_res->fetch_assoc()) {
        $registered_operators[] = $op;
    }
}

// Fetch recent inspection records
$inspections = [];
$i_res = $db->query("
    SELECT ki.* 
    FROM knitting_inspection ki
    ORDER BY ki.KITID DESC LIMIT 15
");
if ($i_res) {
    while ($r = $i_res->fetch_assoc()) {
        $inspections[] = $r;
    }
}

$active_operator = $_SESSION['active_operator'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2-Step Knit Fabric Inspection | Purbani Fabrics</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- HTML5 QR CODE SCANNER LIBRARY -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        :root {
            --color-bg: #f8fafc;
            --color-card: #ffffff;
            --color-primary: #0f172a;
            --color-secondary: #475569;
            --border-color: #cbd5e1;
            --radius-card: 16px;
            --radius-input: 10px;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--color-bg);
            font-family: 'Inter', sans-serif;
            color: var(--color-primary);
            padding: 30px 20px;
        }

        .main-container {
            max-width: 1240px;
            margin: 0 auto;
        }

        .top-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: var(--radius-card);
            padding: 20px 28px;
            color: #ffffff;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .btn-dashboard {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            padding: 8px 18px !important;
            border-radius: 30px !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-dashboard:hover {
            background: rgba(255, 255, 255, 0.2) !important;
        }

        /* ── STEP BADGES & CONTAINERS ── */
        .auth-step-box {
            background: #ffffff;
            border: 2px solid #3b82f6;
            border-radius: 20px;
            padding: 36px 30px;
            margin-bottom: 28px;
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.12);
        }

        .operator-card-pill {
            background: #f1f5f9;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .operator-card-pill:hover {
            background: #e0f2fe;
            border-color: #0284c7;
            transform: translateY(-2px);
        }

        .workspace-card {
            background: var(--color-card);
            border-radius: var(--radius-card);
            border: 1px solid var(--border-color);
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-md);
        }

        .scanner-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 14px;
            padding: 20px;
            color: #ffffff;
            margin-bottom: 24px;
        }

        .scanner-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-scan-fetch {
            background: #2563eb !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            padding: 10px 22px !important;
            border-radius: 10px !important;
            border: none !important;
            font-size: 13.5px !important;
        }

        .btn-direct-camera {
            background: #10b981 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            padding: 10px 20px !important;
            border-radius: 10px !important;
            border: none !important;
            font-size: 13.5px !important;
        }

        .direct-viewfinder-panel {
            background: #000000;
            border-radius: 14px;
            padding: 16px;
            margin-top: 16px;
            border: 2px solid #10b981;
            text-align: center;
        }
        #direct-qr-reader, #op-qr-reader {
            width: 100% !important;
            max-width: 380px !important;
            margin: 0 auto !important;
            border-radius: 10px !important;
            overflow: hidden !important;
        }

        .scanned-details-banner {
            background: #eff6ff;
            border: 2px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .scanned-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }
        .detail-item-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
        }
        .detail-item-value {
            font-size: 13.5px;
            font-weight: 800;
            color: #0f172a;
        }

        /* ── TICK CARDS (DEFECT MATRIX) ── */
        .tick-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }
        .tick-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .tick-card:hover {
            border-color: #94a3b8;
            transform: translateY(-2px);
        }
        .tick-card.active {
            background: #fef2f2;
            border-color: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
        }
        .tick-card-label {
            font-size: 13.5px;
            font-weight: 700;
            color: #1e293b;
        }
        .tick-card.active .tick-card-label {
            color: #b91c1c;
        }

        .btn-submit-inspection {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            padding: 14px 32px !important;
            border-radius: 12px !important;
            border: none !important;
            width: 100%;
        }

        .badge-grade-a { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-grade-b { background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
        .badge-reject  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        .badge-status-passed { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-status-failed { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    </style>
</head>

<body>

    <div class="main-container">

        <!-- ── HEADER TOP BAR ── -->
        <div class="top-bar">
            <div class="d-flex align-items-center gap-3">
                <div style="font-size:24px; background:rgba(255,255,255,0.1); padding:8px 12px; border-radius:12px;">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold mb-0 text-white">2-Step Fabric Inspection Workflow</h1>
                    <p class="mb-0 text-white-50 small">Step 1: Operator ID QR Scan $\rightarrow$ Step 2: Roll QR Scan & Inspection</p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <?php if ($active_operator): ?>
                    <span class="badge bg-success fs-6 px-3 py-2 border border-white">
                        <i class="fa-solid fa-circle-check me-1"></i> Operator: <?php echo htmlspecialchars($active_operator['name']); ?> (<?php echo htmlspecialchars($active_operator['id']); ?>)
                    </span>
                    <button type="button" onclick="logoutOperator()" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold">
                        <i class="fa-solid fa-right-from-bracket me-1"></i> Switch Operator
                    </button>
                <?php else: ?>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                        <i class="fa-solid fa-lock me-1"></i> Operator Auth Required
                    </span>
                <?php endif; ?>

                <a href="initialPage.php" class="btn-dashboard ms-2">
                    <i class="fa-solid fa-house me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- ── ALERTS ── -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 p-3 border-0 shadow-sm" style="background:#fef2f2; color:#991b1b;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 p-3 border-0 shadow-sm" style="background:#f0fdf4; color:#166534;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check fs-5"></i>
                    <strong>Success:</strong> <?php echo htmlspecialchars($msg); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════════════
             STEP 1: OPERATOR AUTHENTICATION INTERFACE (WHEN NO OPERATOR IS LOGGED IN)
        ══════════════════════════════════════════════════════════════════════ -->
        <?php if (!$active_operator): ?>
            <div class="auth-step-box">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="font-size:32px; color:#2563eb;">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <div>
                        <h3 class="h5 fw-bold mb-1 text-dark">STEP 1: OPERATOR IDENTIFICATION REQUIRED</h3>
                        <p class="text-muted mb-0 small">Knitting inspection can only be performed by registered operators. Please scan your Operator QR Code.</p>
                    </div>
                </div>

                <div class="scanner-card mb-4">
                    <label class="form-label text-white fw-bold mb-2">
                        <i class="fa-solid fa-qrcode me-1"></i> Scan Operator Badge QR Code (Scanner Gun or Camera)
                    </label>
                    <div class="scanner-input-group">
                        <input type="text" id="op_scan_input" class="form-control form-control-lg font-monospace text-dark fw-bold" placeholder="Scan Operator QR (e.g. OP01, OP02)..." autofocus autocomplete="off">
                        <button type="button" class="btn btn-primary fw-bold px-4" onclick="performOperatorVerify()">
                            <i class="fa-solid fa-shield-check me-1"></i> Verify ID
                        </button>
                        <button type="button" class="btn btn-emerald text-white fw-bold px-3" style="background:#10b981;" id="btn_op_camera_toggle" onclick="toggleOpCamera()">
                            <i class="fa-solid fa-camera-retro me-1"></i> Camera Scan
                        </button>
                    </div>

                    <!-- OPERATOR VIEWFINDER PANEL -->
                    <div id="op_camera_panel" class="direct-viewfinder-panel d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2 text-white px-2">
                            <span class="fw-bold small"><i class="fa-solid fa-record-vinyl text-danger me-1 fa-beat"></i> Operator Scanner Feed</span>
                            <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="closeOpCamera()">
                                <i class="fa-solid fa-xmark me-1"></i> Close
                            </button>
                        </div>
                        <div id="op-qr-reader"></div>
                        <div id="op_camera_status" class="mt-2 text-info small fw-semibold">Align Operator Badge QR Code in camera frame</div>
                    </div>

                    <div id="op_scan_msg" class="mt-2 text-warning small fw-bold d-none"></div>
                </div>

                <!-- REGISTERED OPERATOR QUICK SELECTION GRID -->
                <div>
                    <label class="form-label font-monospace fw-bold text-secondary small mb-2 text-uppercase">
                        <i class="fa-solid fa-users me-1"></i> Or Select Authorized Operator:
                    </label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($registered_operators as $rop): ?>
                            <div class="operator-card-pill" onclick="quickVerifyOperator('<?php echo htmlspecialchars($rop['OPERATOR_ID']); ?>')">
                                <i class="fa-solid fa-user-check text-primary"></i>
                                <div>
                                    <strong class="d-block text-dark" style="font-size:13px;"><?php echo htmlspecialchars($rop['OPERATOR_NAME']); ?></strong>
                                    <span class="badge bg-secondary font-monospace" style="font-size:10px;"><?php echo htmlspecialchars($rop['OPERATOR_ID']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <!-- ══════════════════════════════════════════════════════════════════════
             STEP 2: ROLL QR SCANNER & FABRIC INSPECTION MATRIX (UNLOCKED UPON AUTH)
        ══════════════════════════════════════════════════════════════════════ -->
        <?php if ($active_operator): ?>
            <form method="POST" action="knitting_inspection.php" id="inspectionForm">
                <input type="hidden" name="save_inspection" value="1">

                <!-- ── CARD 1: ROLL & CARD DETAILS + SCANNER ── -->
                <div class="workspace-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-header-title mb-0">
                            <i class="fa-solid fa-barcode text-primary"></i> STEP 2: Scan Roll QR Code
                        </h4>
                        <span class="badge bg-success text-white px-3 py-2">
                            <i class="fa-solid fa-user-check me-1"></i> Authorized: <?php echo htmlspecialchars($active_operator['name']); ?>
                        </span>
                    </div>

                    <!-- PROMINENT ROLL QR SCANNER BANNER -->
                    <div class="scanner-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label-custom text-white mb-0" style="font-size:12px;">
                                <i class="fa-solid fa-qrcode me-1"></i> Scan Roll QR Code from Knit Card (Gun Scanner or Camera)
                            </label>
                            <span class="badge bg-info text-dark font-monospace" style="font-size: 10px;">Full-Data JSON API</span>
                        </div>
                        
                        <div class="scanner-input-group">
                            <input type="text" id="scan_input" class="form-input-custom text-dark fw-bold" autofocus placeholder="Scan Roll QR (e.g. 300099903, R-8-01) or Card ID..." autocomplete="off">
                            <button type="button" class="btn-scan-fetch" onclick="performScanSearch()">
                                <i class="fa-solid fa-magnifying-glass"></i> Fetch Details
                            </button>
                            <button type="button" class="btn-direct-camera" id="btn_camera_toggle" onclick="toggleDirectCamera()">
                                <i class="fa-solid fa-camera-retro"></i> Scan with Camera
                            </button>
                        </div>

                        <!-- DIRECT EMBEDDED LIVE CAMERA VIEWFINDER PANEL -->
                        <div id="direct_camera_panel" class="direct-viewfinder-panel d-none">
                            <div class="d-flex justify-content-between align-items-center mb-2 text-white px-2">
                                <span class="fw-bold small"><i class="fa-solid fa-record-vinyl text-danger me-1 fa-beat"></i> Live Camera Feed</span>
                                <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="closeDirectCamera()">
                                    <i class="fa-solid fa-xmark me-1"></i> Close Camera
                                </button>
                            </div>
                            <div id="direct-qr-reader"></div>
                            <div id="camera_status_msg" class="mt-2 text-info small fw-semibold">Align Roll QR Code within camera frame</div>
                        </div>

                        <div id="scan_msg" class="mt-2 text-warning small fw-bold d-none"></div>
                    </div>

                    <!-- DYNAMICALLY POPULATED CARD SUMMARY BANNER -->
                    <div id="scanned_card_banner" class="scanned-details-banner d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary text-white px-2 py-1" id="sum_card_id">Knit Card #8</span>
                            <span class="text-muted small fw-bold" id="sum_booking">Booking: N/A</span>
                        </div>
                        <div class="scanned-details-grid">
                            <div>
                                <div class="detail-item-title">Buyer</div>
                                <div class="detail-item-value" id="sum_buyer">-</div>
                            </div>
                            <div>
                                <div class="detail-item-title">Style</div>
                                <div class="detail-item-value" id="sum_style">-</div>
                            </div>
                            <div>
                                <div class="detail-item-title">SONO</div>
                                <div class="detail-item-value" id="sum_sono">-</div>
                            </div>
                            <div>
                                <div class="detail-item-title">M/C No</div>
                                <div class="detail-item-value" id="sum_mcno">-</div>
                            </div>
                            <div>
                                <div class="detail-item-title">Finish Dia / GSM</div>
                                <div class="detail-item-value" id="sum_dia_gsm">-</div>
                            </div>
                            <div>
                                <div class="detail-item-title">Fabric / Yarn</div>
                                <div class="detail-item-value" id="sum_fabric_yarn">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- KNIT CARD SELECT DROPDOWN & ROLL WEIGHT INPUT -->
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label-custom">SELECT KNIT CARD *</label>
                            <select name="KNIT_CARD_ID" id="KNIT_CARD_ID" class="form-select-custom" required>
                                <option value="">-- Select Active Knit Card --</option>
                                <?php foreach ($cards as $c): ?>
                                    <option value="<?php echo $c['KCTID']; ?>">
                                        Card #<?php echo $c['KCTID']; ?> - M/C: <?php echo htmlspecialchars($c['MCNO']); ?> | Buyer: <?php echo htmlspecialchars($c['BUYER']); ?> | Style: <?php echo htmlspecialchars($c['STYLE']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label-custom">ROLL NUMBER (BARCODE / QR) *</label>
                            <input type="text" name="ROLL_NO" id="ROLL_NO" class="form-input-custom font-monospace fw-bold text-primary" required placeholder="e.g. 300099903, R-8-01">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-custom">ROLL WEIGHT (KG) *</label>
                            <input type="number" step="0.01" min="0.01" name="ROLL_WEIGHT" id="ROLL_WEIGHT" class="form-input-custom" required placeholder="0.00">
                        </div>
                    </div>
                </div>

                <!-- ── CARD 2: 4-POINT SYSTEM FABRIC DEFECT MATRIX ── -->
                <div class="workspace-card">
                    <div class="card-header-custom">
                        <h4 class="card-header-title">
                            <i class="fa-solid fa-list-check text-warning"></i> Card 2: Fabric Faults (4-Point Penalty Grid)
                        </h4>
                        <span class="badge-pill-header bg-warning text-dark">Interactive Checkbox Cards</span>
                    </div>

                    <!-- 1-POINT PENALTY FAULTS -->
                    <div class="mb-4">
                        <div class="section-divider">
                            <span class="section-divider-title text-info"><i class="fa-solid fa-circle-dot"></i> 1-Point Penalty Faults</span>
                        </div>
                        <div class="tick-card-grid">
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_TT" value="1" data-weight="1" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Thick & Thin (TT)</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_PATTA" value="1" data-weight="1" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Patta / Barre</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_SLUB" value="1" data-weight="1" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Yarn Slub</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_YC" value="1" data-weight="1" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Yarn Contamination / Spot</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- 2-POINTS PENALTY FAULTS -->
                    <div class="mb-4">
                        <div class="section-divider">
                            <span class="section-divider-title text-primary"><i class="fa-solid fa-circle-dot"></i> 2-Points Penalty Faults</span>
                        </div>
                        <div class="tick-card-grid">
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_OIL_SPOT" value="1" data-weight="2" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Oil Spot</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_FF" value="1" data-weight="2" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Fly Frame / Foreign Fiber</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_SEEDS" value="1" data-weight="2" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Cotton Seeds / Neps</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_M_STITCH" value="1" data-weight="2" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Miss Stitch</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- 3-POINTS PENALTY FAULTS -->
                    <div class="mb-4">
                        <div class="section-divider">
                            <span class="section-divider-title text-warning"><i class="fa-solid fa-circle-dot"></i> 3-Points Penalty Faults</span>
                        </div>
                        <div class="tick-card-grid">
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_SINKER_MARK" value="1" data-weight="3" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Sinker Mark</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_NEEDLE_MARK" value="1" data-weight="3" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Needle Mark</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_LYCRA_OUT" value="1" data-weight="3" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Lycra Out / Drop</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_OIL_LINE" value="1" data-weight="3" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Continuous Oil Line</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- 4-POINTS PENALTY FAULTS -->
                    <div>
                        <div class="section-divider">
                            <span class="section-divider-title text-danger"><i class="fa-solid fa-circle-dot"></i> 4-Points Severe Faults</span>
                        </div>
                        <div class="tick-card-grid">
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_HOLE" value="1" data-weight="4" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Fabric Hole / Cut</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_LOOP" value="1" data-weight="4" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Big Loop / Tuck</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_SETUP" value="1" data-weight="4" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Wrong Machine Setup</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                            <div class="tick-card" onclick="toggleTickCard(this, event)">
                                <div class="tick-card-info">
                                    <input type="checkbox" name="DEFECT_CREASE_MARK" value="1" data-weight="4" class="custom-checkbox-input fault-checkbox">
                                    <span class="tick-card-label">Crease Mark</span>
                                </div>
                                <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── CARD 3: AUTOMATED GRADING & SUMMARY ── -->
                <div class="workspace-card">
                    <div class="card-header-custom">
                        <h4 class="card-header-title">
                            <i class="fa-solid fa-calculator text-success"></i> Card 3: Grading & Inspection Summary
                        </h4>
                        <span class="badge-pill-header bg-success text-white">Fully Automated</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label-custom">Total Penalty Points</label>
                            <input type="number" id="TOTAL_POINTS" name="TOTAL_POINTS" class="form-input-custom fw-bold fs-5 text-center bg-light" value="0" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label-custom">QC Grade (Auto)</label>
                            <input type="text" id="QC_GRADE" name="QC_GRADE" class="form-input-custom text-center auto-input-grade-a" value="Grade A" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label-custom">QC Status (Auto)</label>
                            <input type="text" id="QC_STATUS" name="QC_STATUS" class="form-input-custom text-center auto-input-passed" value="Passed" readonly>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Inspection Remarks & Notes</label>
                        <textarea name="REMARKS" class="form-textarea-custom" rows="3" placeholder="Enter additional notes or defect comments..."></textarea>
                    </div>

                    <!-- ── ACTION BUTTON ── -->
                    <button type="submit" class="btn-submit-inspection">
                        <i class="fa-solid fa-floppy-disk"></i> Save Inspection Record
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <!-- ── RECENT INSPECTION RECORDS TABLE ── -->
        <div class="workspace-card">
            <div class="card-header-custom">
                <h4 class="card-header-title">
                    <i class="fa-solid fa-list-check text-info"></i> Recent Inspection Records
                </h4>
                <span class="badge-pill-header bg-info text-white">Latest 15 Logs</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>KITID</th>
                            <th>PO Number</th>
                            <th>Buyer / Style</th>
                            <th>Roll No</th>
                            <th>Weight (KG)</th>
                            <th>Total Points</th>
                            <th>QC Grade</th>
                            <th>QC Status</th>
                            <th>Operator</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inspections)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No inspection records found yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inspections as $ins): ?>
                                <tr>
                                    <td><strong>#<?php echo $ins['KITID']; ?></strong></td>
                                    <td>PO #<?php echo htmlspecialchars($ins['PO_NUMBER'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($ins['BUYER'] ?: 'N/A'); ?> <br><small class="text-muted"><?php echo htmlspecialchars($ins['STYLE'] ?: ''); ?></small></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($ins['ROLL']); ?></span></td>
                                    <td><?php echo number_format(floatval($ins['QTY']), 2); ?> KG</td>
                                    <td><strong class="text-primary"><?php echo intval($ins['TPOINT']); ?> pts</strong></td>
                                    <td>
                                        <?php 
                                            $g_cls = 'badge-grade-a';
                                            if ($ins['QC_GRADE'] === 'Grade B') $g_cls = 'badge-grade-b';
                                            elseif ($ins['QC_GRADE'] === 'Reject') $g_cls = 'badge-reject';
                                        ?>
                                        <span class="badge <?php echo $g_cls; ?> px-2 py-1"><?php echo htmlspecialchars($ins['QC_GRADE']); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $s_cls = ($ins['QC_STATUS'] === 'Passed') ? 'badge-status-passed' : 'badge-status-failed';
                                        ?>
                                        <span class="badge <?php echo $s_cls; ?> px-2 py-1"><?php echo htmlspecialchars($ins['QC_STATUS']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($ins['UNAME'] ?: 'N/A'); ?></td>
                                    <td><small class="text-muted"><?php echo !empty($ins['P_CREATED']) ? date('d-M-Y H:i', strtotime($ins['P_CREATED'])) : 'N/A'; ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── STEP 1: OPERATOR VERIFICATION SCRIPT ──
        const opScanInput = document.getElementById('op_scan_input');
        const opScanMsg   = document.getElementById('op_scan_msg');
        let opHtml5QrCode = null;
        let isOpCameraActive = false;

        if (opScanInput) {
            opScanInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performOperatorVerify();
                }
            });
        }

        function quickVerifyOperator(opId) {
            if (opScanInput) opScanInput.value = opId;
            performOperatorVerify();
        }

        function performOperatorVerify() {
            if (!opScanInput) return;
            const opQuery = opScanInput.value.trim();
            if (!opQuery) return;

            if (opScanMsg) {
                opScanMsg.classList.remove('d-none', 'text-danger', 'text-success');
                opScanMsg.classList.add('text-warning');
                opScanMsg.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Verifying Operator ID QR Code...';
            }

            fetch('knitting_inspection.php?action=verify_operator&operator_id=' + encodeURIComponent(opQuery))
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (opScanMsg) {
                            opScanMsg.classList.remove('text-warning', 'text-danger');
                            opScanMsg.classList.add('text-success');
                            opScanMsg.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> ' + data.message;
                        }
                        // Reload page to switch to STEP 2
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        if (opScanMsg) {
                            opScanMsg.classList.remove('text-warning', 'text-success');
                            opScanMsg.classList.add('text-danger');
                            opScanMsg.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i> ' + data.message;
                        }
                    }
                })
                .catch(err => {
                    if (opScanMsg) {
                        opScanMsg.classList.remove('text-warning', 'text-success');
                        opScanMsg.classList.add('text-danger');
                        opScanMsg.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Verification Network Error';
                    }
                });
        }

        function logoutOperator() {
            fetch('knitting_inspection.php?action=logout_operator')
                .then(r => r.json())
                .then(d => {
                    window.location.reload();
                });
        }

        function toggleOpCamera() {
            if (isOpCameraActive) closeOpCamera(); else openOpCamera();
        }

        function openOpCamera() {
            const panel = document.getElementById('op_camera_panel');
            const statusMsg = document.getElementById('op_camera_status');
            if (panel) panel.classList.remove('d-none');

            setTimeout(() => {
                if (!opHtml5QrCode) {
                    opHtml5QrCode = new Html5Qrcode("op-qr-reader");
                }
                opHtml5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 15, qrbox: { width: 240, height: 240 } },
                    (decodedText) => {
                        closeOpCamera();
                        if (opScanInput) opScanInput.value = decodedText;
                        performOperatorVerify();
                    },
                    (err) => {}
                ).then(() => {
                    isOpCameraActive = true;
                    if (statusMsg) statusMsg.innerHTML = '<i class="fa-solid fa-circle-dot text-success me-1"></i> Camera Active: Align Operator QR Code';
                }).catch(err => {
                    isOpCameraActive = false;
                    if (statusMsg) statusMsg.innerHTML = '<span class="text-danger">Camera error or permission denied.</span>';
                });
            }, 200);
        }

        function closeOpCamera() {
            const panel = document.getElementById('op_camera_panel');
            if (opHtml5QrCode && opHtml5QrCode.isScanning) {
                opHtml5QrCode.stop().then(() => {
                    isOpCameraActive = false;
                    if (panel) panel.classList.add('d-none');
                });
            } else {
                isOpCameraActive = false;
                if (panel) panel.classList.add('d-none');
            }
        }


        // ── STEP 2: ROLL QR SCANNER WORKFLOW ──
        const scanInput = document.getElementById('scan_input');
        const scanMsg = document.getElementById('scan_msg');
        let html5QrCode = null;
        let isCameraActive = false;

        if (scanInput) {
            scanInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performScanSearch();
                }
            });
        }

        function toggleDirectCamera() {
            if (isCameraActive) {
                closeDirectCamera();
            } else {
                openDirectCamera();
            }
        }

        function openDirectCamera() {
            const panel = document.getElementById('direct_camera_panel');
            const btn = document.getElementById('btn_camera_toggle');
            const statusMsg = document.getElementById('camera_status_msg');

            panel.classList.remove('d-none');
            if (btn) btn.innerHTML = '<i class="fa-solid fa-stop me-1"></i> Stop Camera';
            if (statusMsg) statusMsg.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Starting camera...';

            setTimeout(() => {
                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode("direct-qr-reader");
                }
                
                const config = { fps: 15, qrbox: { width: 250, height: 250 } };
                
                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    onScanSuccess,
                    onScanFailure
                ).then(() => {
                    isCameraActive = true;
                    if (statusMsg) statusMsg.innerHTML = '<i class="fa-solid fa-circle-dot text-success me-1"></i> Camera Active: Align Roll QR Code in frame';
                }).catch(err => {
                    console.error("Camera access error:", err);
                    isCameraActive = false;
                    if (statusMsg) {
                        statusMsg.innerHTML = '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Camera access error or permission denied.</span>';
                    }
                });
            }, 200);
        }

        function closeDirectCamera() {
            const panel = document.getElementById('direct_camera_panel');
            const btn = document.getElementById('btn_camera_toggle');
            
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    isCameraActive = false;
                    panel.classList.add('d-none');
                    if (btn) btn.innerHTML = '<i class="fa-solid fa-camera-retro me-1"></i> Scan with Camera';
                }).catch(err => {
                    console.error(err);
                    panel.classList.add('d-none');
                });
            } else {
                isCameraActive = false;
                panel.classList.add('d-none');
                if (btn) btn.innerHTML = '<i class="fa-solid fa-camera-retro me-1"></i> Scan with Camera';
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            closeDirectCamera();
            if (scanInput) {
                scanInput.value = decodedText;
            }
            performScanSearch();
        }

        function onScanFailure(error) {}

        function performScanSearch() {
            if (!scanInput) return;
            const query = scanInput.value.trim();
            if (!query) return;

            scanMsg.classList.remove('d-none', 'text-danger', 'text-success');
            scanMsg.classList.add('text-warning');
            scanMsg.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Fetching Roll & Knit Card details...';

            fetch('knitting_inspection.php?action=search_card&query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        scanMsg.classList.remove('text-warning', 'text-danger');
                        scanMsg.classList.add('text-success');
                        scanMsg.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Loaded Card #' + data.card_id + ' (Buyer: ' + data.buyer + ' | Style: ' + data.style + ')';

                        const cardSelect = document.getElementById('KNIT_CARD_ID');
                        if (cardSelect) cardSelect.value = data.card_id;

                        const rollInput = document.getElementById('ROLL_NO');
                        if (rollInput) rollInput.value = query.length > 5 ? query : data.suggested_roll;

                        const weightInput = document.getElementById('ROLL_WEIGHT');
                        if (weightInput) weightInput.value = parseFloat(data.suggested_weight).toFixed(2);

                        const summaryBanner = document.getElementById('scanned_card_banner');
                        if (summaryBanner) {
                            summaryBanner.classList.remove('d-none');
                            document.getElementById('sum_card_id').textContent = 'Knit Card #' + data.card_id;
                            document.getElementById('sum_booking').textContent = 'Booking: ' + data.booking;
                            document.getElementById('sum_buyer').textContent = data.buyer;
                            document.getElementById('sum_style').textContent = data.style;
                            document.getElementById('sum_sono').textContent = data.sono;
                            document.getElementById('sum_mcno').textContent = data.mcno;
                            document.getElementById('sum_dia_gsm').textContent = data.finish_dia + ' / ' + data.finish_gsm + ' GSM';
                            document.getElementById('sum_fabric_yarn').textContent = data.fabrics_type + ' (' + data.yarn_type + ')';
                        }

                        if (weightInput) weightInput.focus();

                    } else {
                        scanMsg.classList.remove('text-warning', 'text-success');
                        scanMsg.classList.add('text-danger');
                        scanMsg.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i> ' + (data.message || 'Card not found');
                        
                        const summaryBanner = document.getElementById('scanned_card_banner');
                        if (summaryBanner) summaryBanner.classList.add('d-none');
                    }
                })
                .catch(err => {
                    scanMsg.classList.remove('text-warning', 'text-success');
                    scanMsg.classList.add('text-danger');
                    scanMsg.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i> Network Error';
                });
        }

        // ── DEFECT CHECKBOX SELECTION & AUTOMATED GRADE CALCULATION ──
        function toggleTickCard(card, evt) {
            const cb = card.querySelector('.fault-checkbox');
            if (cb) {
                if (evt && evt.target === cb) {
                } else {
                    cb.checked = !cb.checked;
                }
                updateCardVisual(card, cb);
                calculateTotalPoints();
            }
        }

        function updateCardVisual(card, cb) {
            const icon = card.querySelector('.active-icon');
            if (cb.checked) {
                card.classList.add('active');
                if (icon) icon.classList.remove('d-none');
            } else {
                card.classList.remove('active');
                if (icon) icon.classList.add('d-none');
            }
        }

        function calculateTotalPoints() {
            let total = 0;
            document.querySelectorAll('.fault-checkbox').forEach(cb => {
                const card = cb.closest('.tick-card');
                if (card) updateCardVisual(card, cb);

                if (cb.checked) {
                    let weight = parseInt(cb.getAttribute('data-weight')) || 1;
                    total += weight;
                }
            });

            const totalElem = document.getElementById('TOTAL_POINTS');
            if (totalElem) totalElem.value = total;

            const gradeElem = document.getElementById('QC_GRADE');
            const statusElem = document.getElementById('QC_STATUS');

            if (gradeElem && statusElem) {
                gradeElem.className = 'form-input-custom text-center ';
                statusElem.className = 'form-input-custom text-center ';

                if (total <= 10) {
                    gradeElem.value = 'Grade A';
                    gradeElem.classList.add('auto-input-grade-a');
                    statusElem.value = 'Passed';
                    statusElem.classList.add('auto-input-passed');
                } else if (total <= 25) {
                    gradeElem.value = 'Grade B';
                    gradeElem.classList.add('auto-input-grade-b');
                    statusElem.value = 'Passed';
                    statusElem.classList.add('auto-input-passed');
                } else {
                    gradeElem.value = 'Reject';
                    gradeElem.classList.add('auto-input-reject');
                    statusElem.value = 'Failed';
                    statusElem.classList.add('auto-input-failed');
                }
            }
        }

        document.querySelectorAll('.fault-checkbox').forEach(cb => {
            cb.addEventListener('change', function(e) {
                const card = this.closest('.tick-card');
                if (card) updateCardVisual(card, this);
                calculateTotalPoints();
            });
        });

        calculateTotalPoints();
    </script>
</body>

</html>
