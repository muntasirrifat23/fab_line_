<?php
// knitting_inspection.php - Interactive Checkbox-Based 4-Point System Fabric Inspection Module (Dynamic Full-Data Fetch)
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

// ── INLINE AJAX SCAN/SEARCH ENDPOINT (COMPATIBLE WITH fetch_card_details.php) ──
if (isset($_GET['action']) && $_GET['action'] === 'search_card') {
    header('Content-Type: application/json');
    $query = trim($_GET['query'] ?? '');
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'message' => 'Empty search query']);
        exit();
    }

    // Extract numeric ID if query is formatted like "KC-8", "8", etc.
    $clean_id = intval(preg_replace('/[^0-9]/', '', $query));

    $sql = "SELECT 
                kc.KCID, kc.KPTID, kc.CARD_DATE, kc.MCNO, kc.FINISH_DIA, kc.FINISH_GSM, 
                kc.GREY_GSM, kc.SL_VDQ, kc.OPEN_TUBE, kc.BUYER, kc.SUPPLIER, kc.BOOKING, 
                kc.SONO, kc.STYLE, kc.FABRICS_TYPE, kc.YARN_TYPE, kc.YARN_COUNT, kc.LOT_NO, 
                kc.KNIT_M_DESCRIPTION, kc.REQ_QTY, kc.PREPARED_BY
            FROM knit_card kc
            WHERE kc.KCID = ? OR kc.BUYER LIKE ? OR kc.STYLE LIKE ? OR kc.SONO LIKE ? OR kc.BOOKING LIKE ?
            ORDER BY kc.KCID DESC LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $search_param = '%' . $query . '%';
    $stmt->bind_param("issss", $clean_id, $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $row = $res->fetch_assoc()) {
        $kcid = intval($row['KCID']);
        
        // Count existing rolls for this card to suggest sequence
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

        echo json_encode([
            'success'          => true,
            'card_id'          => $kcid,
            'kptid'            => intval($row['KPTID']),
            'buyer'            => $row['BUYER'] ?: 'N/A',
            'style'            => $row['STYLE'] ?: 'N/A',
            'sono'             => $row['SONO'] ?: 'N/A',
            'booking'          => $row['BOOKING'] ?: 'N/A',
            'mcno'             => $row['MCNO'] ?: 'N/A',
            'finish_dia'       => $row['FINISH_DIA'] ?: 'N/A',
            'finish_gsm'       => $row['FINISH_GSM'] ?: 'N/A',
            'fabrics_type'     => $row['FABRICS_TYPE'] ?: 'N/A',
            'yarn_type'        => $row['YARN_TYPE'] ?: 'N/A',
            'yarn_count'       => $row['YARN_COUNT'] ?: 'N/A',
            'lot_no'           => $row['LOT_NO'] ?: 'N/A',
            'req_qty'          => floatval($row['REQ_QTY']),
            'suggested_roll'   => 'R-' . $kcid . '-' . sprintf("%02d", $roll_count),
            'suggested_weight' => floatval($row['REQ_QTY']) > 0 ? floatval($row['REQ_QTY']) : 25.00
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inspection'])) {
    $knit_card_id        = intval($_POST['KNIT_CARD_ID'] ?? 0);
    $roll_no             = trim($_POST['ROLL_NO'] ?? '');
    $roll_weight         = floatval($_POST['ROLL_WEIGHT'] ?? 0);
    
    // 16 Fabric Faults (Checkboxes: 1 if checked, 0 if unchecked)
    // 1-Point Faults
    $defect_tt          = isset($_POST['DEFECT_TT']) ? 1 : 0;
    $defect_patta       = isset($_POST['DEFECT_PATTA']) ? 1 : 0;
    $defect_slub        = isset($_POST['DEFECT_SLUB']) ? 1 : 0;
    $defect_yc          = isset($_POST['DEFECT_YC']) ? 1 : 0;
    
    // 2-Points Faults
    $defect_oil_spot    = isset($_POST['DEFECT_OIL_SPOT']) ? 1 : 0;
    $defect_ff          = isset($_POST['DEFECT_FF']) ? 1 : 0;
    $defect_seeds       = isset($_POST['DEFECT_SEEDS']) ? 1 : 0;
    $defect_m_stitch    = isset($_POST['DEFECT_M_STITCH']) ? 1 : 0;

    // 3-Points Faults
    $defect_sinker_mark = isset($_POST['DEFECT_SINKER_MARK']) ? 1 : 0;
    $defect_needle_mark = isset($_POST['DEFECT_NEEDLE_MARK']) ? 1 : 0;
    $defect_lycra_out   = isset($_POST['DEFECT_LYCRA_OUT']) ? 1 : 0;
    $defect_oil_line    = isset($_POST['DEFECT_OIL_LINE']) ? 1 : 0;

    // 4-Points Faults
    $defect_hole        = isset($_POST['DEFECT_HOLE']) ? 1 : 0;
    $defect_loop        = isset($_POST['DEFECT_LOOP']) ? 1 : 0;
    $defect_setup       = isset($_POST['DEFECT_SETUP']) ? 1 : 0;
    $defect_crease_mark = isset($_POST['DEFECT_CREASE_MARK']) ? 1 : 0;

    // Calculate weighted total points
    $total_points = ($defect_tt * 1) + ($defect_patta * 1) + ($defect_slub * 1) + ($defect_yc * 1) + 
                    ($defect_oil_spot * 2) + ($defect_ff * 2) + ($defect_seeds * 2) + ($defect_m_stitch * 2) + 
                    ($defect_sinker_mark * 3) + ($defect_needle_mark * 3) + ($defect_lycra_out * 3) + 
                    ($defect_oil_line * 3) + ($defect_hole * 4) + ($defect_loop * 4) + ($defect_setup * 4) + 
                    ($defect_crease_mark * 4);

    $qc_grade     = trim($_POST['QC_GRADE'] ?? '');
    $qc_status    = trim($_POST['QC_STATUS'] ?? '');
    $inspected_by = trim($_POST['INSPECTED_BY'] ?? '');
    $remarks      = trim($_POST['REMARKS'] ?? '');

    // Server-side fallback computation for QC Grade & Status
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

    // Validation
    if ($knit_card_id <= 0) {
        $error = "Please select a valid Knit Card.";
    } elseif (empty($roll_no)) {
        $error = "Roll Number is required.";
    } elseif ($roll_weight <= 0) {
        $error = "Roll Weight must be greater than 0.";
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO knitting_inspection (
                    KNIT_CARD_ID, ROLL_NO, ROLL_WEIGHT,
                    DEFECT_TT, DEFECT_PATTA, DEFECT_SLUB, DEFECT_YC,
                    DEFECT_OIL_SPOT, DEFECT_FF, DEFECT_SEEDS, DEFECT_M_STITCH,
                    DEFECT_SINKER_MARK, DEFECT_NEEDLE_MARK, DEFECT_LYCRA_OUT, DEFECT_OIL_LINE,
                    DEFECT_HOLE, DEFECT_LOOP, DEFECT_SETUP, DEFECT_CREASE_MARK,
                    TOTAL_POINTS, QC_GRADE, QC_STATUS, INSPECTED_BY, REMARKS
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $db->error);
            }
            
            // 24 parameters: 'isd' (3) + 17 'i's (16 defects + total_points) + 'ssss' (4) = 24
            $types = "isd" . str_repeat("i", 17) . "ssss";

            $stmt->bind_param(
                $types,
                $knit_card_id, $roll_no, $roll_weight,
                $defect_tt, $defect_patta, $defect_slub, $defect_yc,
                $defect_oil_spot, $defect_ff, $defect_seeds, $defect_m_stitch,
                $defect_sinker_mark, $defect_needle_mark, $defect_lycra_out, $defect_oil_line,
                $defect_hole, $defect_loop, $defect_setup, $defect_crease_mark,
                $total_points, $qc_grade, $qc_status, $inspected_by, $remarks
            );
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            $msg = "Inspection record for Roll #$roll_no (Points: $total_points | Grade: $qc_grade | Status: $qc_status) saved successfully!";
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch active Knit Cards for select dropdown
$cards = [];
$c_res = $db->query("
    SELECT KCID, CARD_DATE, MCNO, BUYER, STYLE, SONO, REQ_QTY 
    FROM knit_card 
    ORDER BY KCID DESC
");
if ($c_res) {
    while ($row = $c_res->fetch_assoc()) {
        $cards[] = $row;
    }
}

// Fetch recent inspection records
$inspections = [];
$i_res = $db->query("
    SELECT ki.*, kc.BUYER, kc.STYLE, kc.MCNO 
    FROM knitting_inspection ki
    LEFT JOIN knit_card kc ON ki.KNIT_CARD_ID = kc.KCID
    ORDER BY ki.ID DESC LIMIT 15
");
if ($i_res) {
    while ($r = $i_res->fetch_assoc()) {
        $inspections[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knit Fabric Inspection (4-Point System) | Purbani Fabrics</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05);
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

        /* ── GRADIENT HEADER ── */
        .top-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: var(--radius-card);
            padding: 24px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            color: #ffffff;
        }
        
        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .top-bar-icon {
            font-size: 28px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px;
            border-radius: 12px;
            line-height: 1;
        }
        
        .top-bar-title {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .top-bar-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            margin: 2px 0 0 0;
        }

        .btn-dashboard {
            background: #ffffff !important;
            border: none !important;
            color: #0f172a !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            padding: 10px 20px !important;
            border-radius: 30px !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }
        .btn-dashboard:hover {
            background: #f1f5f9 !important;
            transform: translateX(-2px);
        }

        /* ── SCANNER INPUT BANNER ── */
        .scanner-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 14px;
            padding: 20px 24px;
            color: #ffffff;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.15);
        }
        .scanner-input-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .scanner-input-group input {
            font-size: 16px !important;
            font-weight: 700 !important;
            padding: 12px 18px !important;
            border-radius: 10px !important;
            border: 2px solid #64748b !important;
            flex: 1;
            min-width: 260px;
        }
        .scanner-input-group input:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25) !important;
        }
        .btn-scan-fetch {
            background: #38bdf8 !important;
            color: #0f172a !important;
            font-weight: 800 !important;
            padding: 0 22px !important;
            border-radius: 10px !important;
            border: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            height: 48px;
        }
        .btn-scan-fetch:hover {
            background: #0ea5e9 !important;
            color: #ffffff !important;
        }
        .btn-direct-camera {
            background: #10b981 !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            padding: 0 24px !important;
            border-radius: 10px !important;
            border: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            height: 48px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .btn-direct-camera:hover {
            background: #059669 !important;
            transform: translateY(-1px);
        }

        /* ── DIRECT CAMERA VIEWFINDER STYLING ── */
        .direct-viewfinder-panel {
            background: #0f172a;
            border: 2px solid #38bdf8;
            border-radius: 14px;
            padding: 16px;
            margin-top: 16px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        #direct-qr-reader {
            width: 100%;
            max-width: 460px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            background: #020617;
        }

        /* ── FULL DATA SUMMARY BANNER ── */
        .scanned-details-banner {
            background: #f0f9ff;
            border: 1.5px solid #7dd3fc;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .scanned-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .detail-item-title {
            font-size: 10px;
            font-weight: 700;
            color: #0284c7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .detail-item-value {
            font-size: 13.5px;
            font-weight: 800;
            color: #0f172a;
        }

        /* ── CARD STYLING ── */
        .workspace-card {
            background: var(--color-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid var(--border-color);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .card-header-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--color-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-pill-header {
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 30px;
        }

        .form-label-custom {
            font-size: 11px;
            font-weight: 700;
            color: var(--color-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }
        .required-label::after {
            content: " *";
            color: #dc2626;
        }

        .form-select-custom, .form-input-custom, .form-textarea-custom {
            background-color: #ffffff !important;
            border: 1.5px solid var(--border-color) !important;
            border-radius: var(--radius-input) !important;
            padding: 10px 14px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            color: var(--color-primary) !important;
            transition: all 0.2s ease !important;
            width: 100%;
        }
        .form-select-custom:focus, .form-input-custom:focus, .form-textarea-custom:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
            outline: 0 !important;
        }

        .quantity-input-wrapper {
            display: flex;
        }
        .quantity-input-wrapper .form-input-custom {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-right: none !important;
        }
        .input-group-addon-custom {
            background: #f8fafc;
            border: 1.5px solid var(--border-color);
            border-left: none;
            color: var(--color-secondary);
            font-weight: 700;
            font-size: 13px;
            padding: 0 16px;
            border-top-right-radius: var(--radius-input);
            border-bottom-right-radius: var(--radius-input);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── FAULT GROUP PANELS ── */
        .point-group-panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 20px;
        }
        .point-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .point-group-title {
            font-size: 14px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── CLICKABLE TICK FAULT CARDS ── */
        .checkbox-fault-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 14px;
        }

        .tick-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .tick-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* 1-Point Active */
        .group-1pt .tick-card.active {
            background: #f0f9ff;
            border-color: #0284c7;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.15);
        }

        /* 2-Point Active */
        .group-2pt .tick-card.active {
            background: #eff6ff;
            border-color: #2563eb;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.15);
        }

        /* 3-Point Active */
        .group-3pt .tick-card.active {
            background: #fffbeb;
            border-color: #d97706;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.15);
        }

        /* 4-Point Active */
        .group-4pt .tick-card.active {
            background: #fef2f2;
            border-color: #dc2626;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.15);
        }

        .tick-card-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tick-card-label {
            font-size: 13.5px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1.2;
        }

        .custom-checkbox-input {
            width: 20px;
            height: 20px;
            accent-color: #0f172a;
            cursor: pointer;
        }

        /* ── AUTO GRADED READONLY INPUTS ── */
        .auto-input-grade-a {
            background-color: #dcfce7 !important;
            color: #15803d !important;
            border-color: #86efac !important;
            font-weight: 800 !important;
        }
        .auto-input-grade-b {
            background-color: #fef9c3 !important;
            color: #a16207 !important;
            border-color: #fde047 !important;
            font-weight: 800 !important;
        }
        .auto-input-reject {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
            border-color: #fca5a5 !important;
            font-weight: 800 !important;
        }

        .auto-input-passed {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
            border-color: #93c5fd !important;
            font-weight: 800 !important;
        }
        .auto-input-failed {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
            border-color: #fca5a5 !important;
            font-weight: 800 !important;
        }

        /* ── BUTTON STYLING ── */
        .btn-submit-inspection {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            padding: 14px 32px !important;
            border-radius: 12px !important;
            border: none !important;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.3) !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s ease !important;
            width: 100%;
        }
        .btn-submit-inspection:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.4) !important;
            filter: brightness(1.1);
        }

        /* ── BADGES FOR RECENT TABLE ── */
        .badge-grade-a { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-grade-b { background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
        .badge-reject  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        .badge-status-passed { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-status-failed { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <div class="main-container">

        <!-- ── HEADER ── -->
        <div class="top-bar">
            <div class="top-bar-left">
                <div class="top-bar-icon"><i class="fa-solid fa-camera"></i></div>
                <div>
                    <h1 class="top-bar-title">Knit Fabric Inspection (Full Dynamic Fetch)</h1>
                    <p class="top-bar-subtitle">Dynamic Knit Card & Roll Fetching with 4-Point Inspection Matrix</p>
                </div>
            </div>
            <div>
                <a href="initialPage.php" class="btn-dashboard">
                    <i class="fa-solid fa-house"></i> Dashboard
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

        <form method="POST" action="knitting_inspection.php" id="inspectionForm">
            <input type="hidden" name="save_inspection" value="1">

            <!-- ── CARD 1: ROLL & CARD DETAILS + SCANNER ── -->
            <div class="workspace-card">
                <div class="card-header-custom">
                    <h4 class="card-header-title">
                        <i class="fa-solid fa-barcode text-primary"></i> Card 1: Roll & Knit Card Info
                    </h4>
                    <span class="badge-pill-header bg-primary text-white">Full-Data Dynamic Fetch</span>
                </div>

                <!-- PROMINENT DIRECT CAMERA & INPUT SCANNER BANNER -->
                <div class="scanner-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label-custom text-white mb-0" style="font-size:12px;">
                            <i class="fa-solid fa-qrcode me-1"></i> Scan / Enter Card or Roll ID (Gun Scanner or Live Camera)
                        </label>
                        <span class="badge bg-info text-dark font-monospace" style="font-size: 10px;">Full-Data JSON API</span>
                    </div>
                    
                    <div class="scanner-input-group">
                        <input type="text" id="scan_input" class="form-input-custom text-dark fw-bold" autofocus placeholder="Scan QR/Barcode or type Card ID (e.g. KC-8)..." autocomplete="off">
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
                        <div id="camera_status_msg" class="mt-2 text-info small fw-semibold">Align QR Code or Barcode within the camera frame</div>
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
                            <div class="detail-item-title">S/O No</div>
                            <div class="detail-item-value" id="sum_sono">-</div>
                        </div>
                        <div>
                            <div class="detail-item-title">M/C No</div>
                            <div class="detail-item-value" id="sum_mcno">-</div>
                        </div>
                        <div>
                            <div class="detail-item-title">Finish Dia & GSM</div>
                            <div class="detail-item-value" id="sum_dia_gsm">-</div>
                        </div>
                        <div>
                            <div class="detail-item-title">Fabric / Yarn</div>
                            <div class="detail-item-value" id="sum_fabric_yarn">-</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-custom required-label">Knit Card Selection</label>
                        <select name="KNIT_CARD_ID" id="KNIT_CARD_ID" class="form-select-custom" required>
                            <option value="">-- Select Active Knit Card --</option>
                            <?php foreach ($cards as $c): ?>
                                <option value="<?php echo $c['KCID']; ?>">
                                    Knit Card #<?php echo $c['KCID']; ?> (Buyer: <?php echo htmlspecialchars($c['BUYER'] ?: 'N/A'); ?> | Style: <?php echo htmlspecialchars($c['STYLE'] ?: 'N/A'); ?> | M/C: <?php echo htmlspecialchars($c['MCNO'] ?: 'N/A'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-custom required-label">Roll Number</label>
                        <input type="text" name="ROLL_NO" id="ROLL_NO" class="form-input-custom" required placeholder="e.g. R-101">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label-custom required-label">Roll Weight (KG)</label>
                        <div class="quantity-input-wrapper">
                            <input type="number" step="0.01" min="0.01" name="ROLL_WEIGHT" id="ROLL_WEIGHT" class="form-input-custom" required placeholder="0.00">
                            <span class="input-group-addon-custom">KG</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Inspected By</label>
                        <input type="text" name="INSPECTED_BY" class="form-input-custom" placeholder="Inspector Name" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- ── CARD 2: CHECKBOX-BASED 4-POINT FAULT MATRIX ── -->
            <div class="workspace-card">
                <div class="card-header-custom">
                    <h4 class="card-header-title">
                        <i class="fa-solid fa-list-check text-warning"></i> Card 2: 4-Point Fault Input Matrix (Checkboxes)
                    </h4>
                    <span class="badge-pill-header bg-warning text-dark">Tick Off Faults</span>
                </div>

                <!-- SECTION 1: 1-POINT FAULTS -->
                <div class="point-group-panel group-1pt">
                    <div class="point-group-header">
                        <h5 class="point-group-title text-info"><i class="fa-solid fa-circle-1 me-1"></i> 1-Point Faults</h5>
                        <span class="badge bg-info text-dark px-3 py-1 fw-bold">1 Pt Each</span>
                    </div>
                    <div class="checkbox-fault-grid">
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_TT" value="1" data-weight="1" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">T&T (Tension & Twist)</span>
                            </div>
                            <i class="fa-solid fa-check text-info d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_PATTA" value="1" data-weight="1" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Patta (Barre)</span>
                            </div>
                            <i class="fa-solid fa-check text-info d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_SLUB" value="1" data-weight="1" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Slub</span>
                            </div>
                            <i class="fa-solid fa-check text-info d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_YC" value="1" data-weight="1" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Y/C (Contamination)</span>
                            </div>
                            <i class="fa-solid fa-check text-info d-none active-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: 2-POINTS FAULTS -->
                <div class="point-group-panel group-2pt">
                    <div class="point-group-header">
                        <h5 class="point-group-title text-primary"><i class="fa-solid fa-circle-2 me-1"></i> 2-Points Faults</h5>
                        <span class="badge bg-primary text-white px-3 py-1 fw-bold">2 Pts Each</span>
                    </div>
                    <div class="checkbox-fault-grid">
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_OIL_SPOT" value="1" data-weight="2" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Oil Spot</span>
                            </div>
                            <i class="fa-solid fa-check text-primary d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_FF" value="1" data-weight="2" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">F/F (Fly Frame)</span>
                            </div>
                            <i class="fa-solid fa-check text-primary d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_SEEDS" value="1" data-weight="2" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Seeds / Trash</span>
                            </div>
                            <i class="fa-solid fa-check text-primary d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_M_STITCH" value="1" data-weight="2" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">M/Stitch (Missing Stitch)</span>
                            </div>
                            <i class="fa-solid fa-check text-primary d-none active-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: 3-POINTS FAULTS -->
                <div class="point-group-panel group-3pt">
                    <div class="point-group-header">
                        <h5 class="point-group-title text-warning"><i class="fa-solid fa-circle-3 me-1"></i> 3-Points Faults</h5>
                        <span class="badge bg-warning text-dark px-3 py-1 fw-bold">3 Pts Each</span>
                    </div>
                    <div class="checkbox-fault-grid">
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_SINKER_MARK" value="1" data-weight="3" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Sinker Mark</span>
                            </div>
                            <i class="fa-solid fa-check text-warning d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_NEEDLE_MARK" value="1" data-weight="3" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Needle Mark</span>
                            </div>
                            <i class="fa-solid fa-check text-warning d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_LYCRA_OUT" value="1" data-weight="3" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Lycra Out</span>
                            </div>
                            <i class="fa-solid fa-check text-warning d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_OIL_LINE" value="1" data-weight="3" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Oil Line</span>
                            </div>
                            <i class="fa-solid fa-check text-warning d-none active-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: 4-POINTS FAULTS -->
                <div class="point-group-panel group-4pt">
                    <div class="point-group-header">
                        <h5 class="point-group-title text-danger"><i class="fa-solid fa-circle-4 me-1"></i> 4-Points Faults</h5>
                        <span class="badge bg-danger text-white px-3 py-1 fw-bold">4 Pts Each</span>
                    </div>
                    <div class="checkbox-fault-grid">
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_HOLE" value="1" data-weight="4" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Hole</span>
                            </div>
                            <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_LOOP" value="1" data-weight="4" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Loop</span>
                            </div>
                            <i class="fa-solid fa-check text-danger d-none active-icon"></i>
                        </div>
                        <div class="tick-card" onclick="toggleTickCard(this, event)">
                            <div class="tick-card-info">
                                <input type="checkbox" name="DEFECT_SETUP" value="1" data-weight="4" class="custom-checkbox-input fault-checkbox">
                                <span class="tick-card-label">Setup Defect</span>
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
                            <th>ID</th>
                            <th>Knit Card</th>
                            <th>Buyer / Style</th>
                            <th>Roll No</th>
                            <th>Weight (KG)</th>
                            <th>Total Points</th>
                            <th>QC Grade</th>
                            <th>QC Status</th>
                            <th>Inspected By</th>
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
                                    <td><strong>#<?php echo $ins['ID']; ?></strong></td>
                                    <td>Knit Card #<?php echo $ins['KNIT_CARD_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($ins['BUYER'] ?: 'N/A'); ?> <br><small class="text-muted"><?php echo htmlspecialchars($ins['STYLE'] ?: ''); ?></small></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($ins['ROLL_NO']); ?></span></td>
                                    <td><?php echo number_format($ins['ROLL_WEIGHT'], 2); ?> KG</td>
                                    <td><strong class="text-primary"><?php echo $ins['TOTAL_POINTS']; ?> pts</strong></td>
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
                                    <td><?php echo htmlspecialchars($ins['INSPECTED_BY'] ?: 'N/A'); ?></td>
                                    <td><small class="text-muted"><?php echo date('d-M-Y H:i', strtotime($ins['INSPECTION_DATE'])); ?></small></td>
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
        // SCANNER GUN & DIRECT CAMERA VIEWFINDER WORKFLOW
        const scanInput = document.getElementById('scan_input');
        const scanMsg = document.getElementById('scan_msg');
        let html5QrCode = null;
        let isCameraActive = false;

        if (scanInput) {
            scanInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Prevent main form submission when gun scanning
                    performScanSearch();
                }
            });
        }

        // Direct Live Camera Toggle
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
                    if (statusMsg) statusMsg.innerHTML = '<i class="fa-solid fa-circle-dot text-success me-1"></i> Camera Active: Align QR Code / Barcode in frame';
                }).catch(err => {
                    console.error("Camera access error:", err);
                    isCameraActive = false;
                    if (statusMsg) {
                        statusMsg.innerHTML = '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Camera access error or permission denied.<br><small class="text-white-50">Note: Web Camera API requires <code>localhost</code> or <code>https://</code>.</small></span>';
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
            console.log("Direct Camera Scanned:", decodedText);
            
            // Instantly close live camera panel
            closeDirectCamera();

            // Inject scanned code and execute AJAX search
            if (scanInput) {
                scanInput.value = decodedText;
            }
            performScanSearch();
        }

        function onScanFailure(error) {
            // Quiet frame scanner
        }

        // FULL-DATA AJAX FETCH WORKFLOW
        function performScanSearch() {
            const query = scanInput.value.trim();
            if (!query) return;

            scanMsg.classList.remove('d-none', 'text-danger', 'text-success');
            scanMsg.classList.add('text-warning');
            scanMsg.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Fetching complete Knit Card & Roll details...';

            fetch('fetch_card_details.php?query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        scanMsg.classList.remove('text-warning', 'text-danger');
                        scanMsg.classList.add('text-success');
                        scanMsg.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Loaded Knit Card #' + data.card_id + ' (Buyer: ' + data.buyer + ' | Style: ' + data.style + ')';

                        // 1. Update Select Dropdown
                        const cardSelect = document.getElementById('KNIT_CARD_ID');
                        if (cardSelect) {
                            cardSelect.value = data.card_id;
                        }

                        // 2. Auto Populate Specific Roll Number
                        const rollInput = document.getElementById('ROLL_NO');
                        if (rollInput) {
                            rollInput.value = data.suggested_roll;
                        }

                        // 3. Auto Populate Roll Weight
                        const weightInput = document.getElementById('ROLL_WEIGHT');
                        if (weightInput) {
                            weightInput.value = parseFloat(data.suggested_weight).toFixed(2);
                        }

                        // 4. Populate Full Reference Summary Banner
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

                        // Focus roll weight input for rapid entry
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
                    scanMsg.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i> Network / Server Error when calling fetch_card_details.php';
                });
        }

        // Toggle Tick Card Selection when clicked
        function toggleTickCard(card, evt) {
            const cb = card.querySelector('.fault-checkbox');
            if (cb) {
                if (evt && evt.target === cb) {
                    // Let native checkbox toggle
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

        // Auto Calculate Total Points & Fully Automated Grade & Status
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
                // Reset classes
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

        // Add direct listener to checkboxes
        document.querySelectorAll('.fault-checkbox').forEach(cb => {
            cb.addEventListener('change', function(e) {
                const card = this.closest('.tick-card');
                if (card) updateCardVisual(card, this);
                calculateTotalPoints();
            });
        });

        // Initialize on page load
        calculateTotalPoints();
    </script>
</body>
</html>
