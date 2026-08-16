<?php
date_default_timezone_set('Asia/Dhaka');
$bdHour = (int)date('G');
if ($bdHour >= 6 && $bdHour < 14) {$shift = 'A'; } 
elseif ($bdHour >= 14 && $bdHour < 22) {$shift = 'B'; } 
else { $shift = 'C'; }

session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : (isset($_POST['program_id']) ? intval($_POST['program_id']) : 0);

if ($program_id <= 0) {
    header("Location: knitting_program_list.php?error=Invalid+program+ID");
    exit();
}

// 1. Read selected Knitting Program using KPTID (Rule 2)
$stmt = $db->prepare("SELECT * FROM knitting_program WHERE KPTID = ?");
if (!$stmt) {
    header("Location: knitting_program_list.php?error=" . urlencode("Database error: " . $db->error));
    exit();
}
$stmt->bind_param("i", $program_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows == 0) {
    header("Location: knitting_program_list.php?error=Knitting+Program+not+found");
    exit();
}

$prog = $res->fetch_assoc();
$stmt->close();

// 2. Fetch already carded quantity for this program from knit_card
$already_carded = 0.00;
$sum_stmt = $db->prepare("SELECT SUM(REQ_QTY) AS total_carded FROM knit_card WHERE KPTID = ?");
if ($sum_stmt) {
    $sum_stmt->bind_param("i", $program_id);
    $sum_stmt->execute();
    $res_sum = $sum_stmt->get_result();
    if ($res_sum && $row_sum = $res_sum->fetch_assoc()) {
        $already_carded = floatval($row_sum['total_carded'] ?? 0);
    }
    $sum_stmt->close();
}

$program_qty = floatval($prog['QTY'] ?? 0);
$remaining_qty = max(0.00, $program_qty - $already_carded);

// 3. Obtain corresponding SUB_TID and BOOKING from program to load information from knitting_input
$sub_tid = $prog['SUB_TID'] ?? '';
$booking = $prog['PO_NUMBER'] ?? '';
$input = null;

if (!empty($sub_tid) || !empty($booking)) {
    $stmt_in = $db->prepare("SELECT * FROM knitting_input WHERE KITID = ? OR BOOKING = CONVERT(? USING utf8mb4) LIMIT 1");
    if ($stmt_in) {
        $stmt_in->bind_param("ss", $sub_tid, $booking);
        $stmt_in->execute();
        $res_in = $stmt_in->get_result();
        if ($res_in && $res_in->num_rows > 0) {
            $input = $res_in->fetch_assoc();
        }
        $stmt_in->close();
    }
}

// Auto-populate required information combining knitting_program and knitting_input
$card_date          = date('Y-m-d');
$p_kptid            = intval($prog['KPTID']);
$p_sub_tid          = $sub_tid;
$p_buyer            = !empty($prog['BUYER']) ? $prog['BUYER'] : ($input['BUYER'] ?? '');
$p_supplier         = !empty($prog['SUPPLIER']) ? $prog['SUPPLIER'] : ($input['SUPPLIER'] ?? '');
$p_booking          = !empty($prog['PO_NUMBER']) ? $prog['PO_NUMBER'] : ($input['BOOKING'] ?? '');
$p_sono             = !empty($prog['SONO']) ? $prog['SONO'] : ($input['SONO'] ?? '');
$p_style            = !empty($prog['STYLE']) ? $prog['STYLE'] : ($input['STYLE'] ?? '');
$p_mcno             = !empty($prog['MCNO']) ? $prog['MCNO'] : '';
$p_finish_dia       = !empty($prog['FINISH_DIA']) ? $prog['FINISH_DIA'] : ($input['FINISH_DIA'] ?? '');
$p_finish_gsm       = !empty($prog['FINISH_GSM']) ? $prog['FINISH_GSM'] : ($input['FINISH_GSM'] ?? '');
$p_grey_gsm         = $p_finish_gsm;
$p_open_tube        = !empty($prog['OPEN_TUBE']) ? $prog['OPEN_TUBE'] : ($input['OPEN_TUBE'] ?? 'O');
$p_fabrics          = !empty($prog['FABRICS_TYPE']) ? $prog['FABRICS_TYPE'] : ($input['FABRICS_TYPE'] ?? '');
$p_yarn_type        = !empty($prog['YARN_TYPE']) ? $prog['YARN_TYPE'] : ($input['YARN_TYPE'] ?? '');
$p_yarn_count       = !empty($prog['YARN_COUNT']) ? $prog['YARN_COUNT'] : ($input['YARN_COUNT'] ?? '');
$p_color            = !empty($prog['COLOR']) ? $prog['COLOR'] : ($input['COLOR'] ?? '');
$p_lot_no           = !empty($prog['LOT_NO']) ? $prog['LOT_NO'] : ($input['LOT_NO'] ?? '');
$p_knit_m_desc      = !empty($prog['KNIT_M_DESCRIPTION']) ? $prog['KNIT_M_DESCRIPTION'] : ($input['KNIT_M_DESCRIPTION'] ?? '');
$p_knit_mat_code    = !empty($prog['KNIT_MATERIAL_CODE']) ? $prog['KNIT_MATERIAL_CODE'] : ($input['KNIT_MATERIAL_CODE'] ?? '');
$p_sl_vdq           = floatval(!empty($prog['SL_VDQ']) ? $prog['SL_VDQ'] : ($input['SL_VDQ'] ?? 0));

$default_qty        = $remaining_qty;
$prepared_by        = $_SESSION['username'] ?? '';
$authorised_by      = '';
$error              = '';

if ($remaining_qty <= 0) {
    $error = "No remaining quantity is available for this program.";
}

// 4. Save Logic (Rule 6 & Rule 7 with Concurrency & Transaction Safety)
// Fetch MCNO list for dropdown
$mcno_list = [];
$mcno_res = $db->query("SELECT MCNO FROM mcno ORDER BY MCNO ASC");
if ($mcno_res) {
    while ($mcno_row = $mcno_res->fetch_assoc()) {
        $mcno_list[] = $mcno_row['MCNO'];
    }
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_knit_card'])) {
    $user_req_qty = floatval($_POST['REQ_QTY'] ?? 0);
    $p_mcno       = trim($_POST['MCNO'] ?? '');

    if ($user_req_qty <= 0) {
        $error = "Required Quantity must be a positive number.";
    } elseif (empty($p_mcno)) {
        $error = "Machine Number (M/C No) is required.";
    } else {
        // Start Transaction
        $db->begin_transaction();

        try {
            // Lock the knitting_program row to prevent concurrent modifications on this program
            $stmt_lock = $db->prepare("SELECT QTY FROM knitting_program WHERE KPTID = ? FOR UPDATE");
            if (!$stmt_lock) {
                throw new Exception("Database lock error: " . $db->error);
            }
            $stmt_lock->bind_param("i", $p_kptid);
            $stmt_lock->execute();
            $res_lock = $stmt_lock->get_result();
            if (!$res_lock || $res_lock->num_rows === 0) {
                $stmt_lock->close();
                throw new Exception("Knitting program not found.");
            }
            $lock_row = $res_lock->fetch_assoc();
            $stmt_lock->close();

            $program_qty_locked = floatval($lock_row['QTY'] ?? 0);

            // Fetch current sum of REQ_QTY for this program from knit_card (using lock since it's in transaction)
            $stmt_sum = $db->prepare("SELECT SUM(REQ_QTY) AS total_carded FROM knit_card WHERE KPTID = ?");
            if (!$stmt_sum) {
                throw new Exception("Database query error: " . $db->error);
            }
            $stmt_sum->bind_param("i", $p_kptid);
            $stmt_sum->execute();
            $res_sum = $stmt_sum->get_result();
            $already_carded_locked = 0.00;
            if ($res_sum && $row_sum = $res_sum->fetch_assoc()) {
                $already_carded_locked = floatval($row_sum['total_carded'] ?? 0);
            }
            $stmt_sum->close();

            $remaining_qty_locked = $program_qty_locked - $already_carded_locked;

            // Check if remaining quantity is zero
            if ($remaining_qty_locked <= 0) {
                throw new Exception("No remaining quantity is available for this program.");
            }

            // Check if user required quantity exceeds remaining quantity
            if ($user_req_qty > $remaining_qty_locked) {
                throw new Exception("Required quantity cannot exceed the remaining program quantity.");
            }

            // Insert into knit_card
            $ins = $db->prepare("
                INSERT INTO knit_card (
                    KPTID, CARD_DATE, MCNO, FINISH_DIA, FINISH_GSM, GREY_GSM, SL_VDQ,
                    OPEN_TUBE, BUYER, SUPPLIER, BOOKING, SONO, STYLE,
                    FABRICS_TYPE, YARN_TYPE, YARN_COUNT, LOT_NO, KNIT_M_DESCRIPTION,
                    REQ_QTY, PREPARED_BY, AUTHORISED_BY
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$ins) {
                throw new Exception("Failed to prepare insert query: " . $db->error);
            }

            $ins->bind_param(
                "isssssdsssssssssssdss",
                $p_kptid,
                $card_date,
                $p_mcno,
                $p_finish_dia,
                $p_finish_gsm,
                $p_grey_gsm,
                $p_sl_vdq,
                $p_open_tube,
                $p_buyer,
                $p_supplier,
                $p_booking,
                $p_sono,
                $p_style,
                $p_fabrics,
                $p_yarn_type,
                $p_yarn_count,
                $p_lot_no,
                $p_knit_m_desc,
                $user_req_qty,
                $prepared_by,
                $authorised_by
            );

            if (!$ins->execute()) {
                $ins_err = $ins->error;
                $ins->close();
                throw new Exception("Failed to generate Knit Card: " . $ins_err);
            }

            $new_kcid = $ins->insert_id;
            $ins->close();

            // Mark the knitting_program as card generated
            $upd = $db->prepare("UPDATE knitting_program SET CARD_GENERATED = 1 WHERE KPTID = ?");
            if ($upd) {
                $upd->bind_param("i", $p_kptid);
                $upd->execute();
                $upd->close();
            }

            // Commit Transaction
            $db->commit();

            header("Location: knit_card_view.php?id=" . $new_kcid . "&msg=" . urlencode("Knit Card generated successfully!"));
            exit();

        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Knit Card #<?php echo $p_kptid; ?> | Purbani Fabrics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #f8fafc;
            --surface-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --text-muted-light: #cbd5e1;
            
            --color-success: #16a34a;
            --color-success-light: #dcfce7;
            --color-success-border: #bbf7d0;
            
            --color-blue: #2563eb;
            --color-blue-light: #eff6ff;
            --color-blue-border: #dbeafe;
            
            --color-purple: #7c3aed;
            --color-purple-light: #f3e8ff;
            --color-purple-border: #e9d5ff;

            --color-amber: #d97706;
            --color-amber-light: #fef3c7;
            --color-amber-border: #fde68a;
            
            --border-color: #e2e8f0;
            --font-main: 'Inter', system-ui, -apple-system, sans-serif;
            
            --radius-input: 10px;
            --radius-box: 12px;
            --radius-card: 16px;
        }

        i, i.fa-solid, i.fas, i.far, i.fab, i.fa-regular {
            border: none !important; outline: none !important; box-shadow: none !important;
            padding: 0 !important; margin: 0 !important; display: inline-block !important; transform: none !important;
        }

        body {
            background-color: var(--bg-canvas);
            font-family: var(--font-main);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            padding: 32px 24px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ── PAGE HEADER (TOP BAR) ── */
        .top-bar {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            border-radius: var(--radius-card);
            padding: 20px 24px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.15);
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .top-bar-icon {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .top-bar-title-wrap h1 {
            font-weight: 800;
            font-size: 22px;
            margin: 0;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .top-bar-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin: 2px 0 0 0;
            font-weight: 400;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pill-badge {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 30px;
            letter-spacing: 0.3px;
        }

        .btn-back {
            background: #ffffff;
            color: #1e293b;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 18px;
            border-radius: 30px;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .btn-back:hover {
            background: #f1f5f9;
            color: #0f172a;
            transform: translateY(-1px);
            text-decoration: none;
        }

        /* ── RESPONSIVE 2-COLUMN GRID ── */
        .workspace-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        @media (max-width: 900px) {
            .workspace-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── WORKSPACE CARDS ── */
        .workspace-card {
            background: var(--surface-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-card);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            padding: 24px;
        }

        .card-header-custom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .card-header-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .badge-pill-header {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-status-blue {
            background: var(--color-blue-light);
            color: var(--color-blue);
            border: 1px solid var(--color-blue-border);
        }
        .badge-status-purple {
            background: var(--color-purple-light);
            color: var(--color-purple);
            border: 1px solid var(--color-purple-border);
        }

        /* ── METRICS ROW ── */
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .metric-col {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .metric-val-blue {
            font-size: 20px;
            font-weight: 800;
            color: var(--color-blue);
        }
        .metric-val-gray {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-secondary);
        }
        .metric-val-green {
            font-size: 20px;
            font-weight: 800;
            color: var(--color-success);
        }
        .metric-lbl {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* ── PROGRESS BAR ── */
        .progress-bar-container {
            background: #f1f5f9;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 24px;
            position: relative;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 100%);
            width: 0%; /* Animated via JS */
            border-radius: 3px;
        }

        /* ── FORM FIELDS ── */
        .form-label-custom {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }
        .required-label::after {
            content: " *";
            color: #ef4444;
        }
        
        .form-select-custom, .form-input-custom {
            background-color: #ffffff !important;
            border: 1.5px solid var(--border-color) !important;
            border-radius: var(--radius-input) !important;
            padding: 12px 16px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            color: var(--text-primary) !important;
            transition: all 0.2s ease !important;
            width: 100%;
        }
        .form-select-custom:focus, .form-input-custom:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            outline: 0 !important;
        }

        /* Quantity Input Suffix */
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
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 13px;
            padding: 0 16px;
            border-top-right-radius: var(--radius-input);
            border-bottom-right-radius: var(--radius-input);
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }

        .validation-msg {
            font-size: 12px;
            font-weight: 600;
            color: var(--color-success);
        }

        /* ── RIGHT CARD (SPECIFICATIONS GRID) ── */
        .specs-inner-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 900px) {
            .specs-inner-grid {
                grid-template-columns: 1fr;
            }
        }

        .spec-box-col {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-box);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .spec-box-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid var(--border-color);
            padding-bottom: 8px;
            margin-bottom: 4px;
            display: block;
        }

        .spec-row {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            font-size: 13px;
        }

        .spec-label {
            color: var(--text-secondary);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spec-value {
            color: var(--text-primary);
            font-weight: 700;
            text-align: left;
        }

        /* Highlights */
        .value-highlight-blue {
            color: var(--color-blue);
            font-weight: 700;
        }
        .value-muted-italic {
            color: var(--text-muted);
            font-style: italic;
            font-weight: 500;
        }

        /* Badges & Pills */
        .badge-pill-green {
            background: var(--color-success-light);
            color: var(--color-success);
            border: 1px solid var(--color-success-border);
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .dot-green {
            width: 6px;
            height: 6px;
            background-color: var(--color-success);
            border-radius: 50%;
            display: inline-block;
        }

        .badge-pill-color {
            background: #f1f5f9;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .dot-color {
            width: 8px;
            height: 8px;
            background-color: var(--text-secondary);
            border-radius: 50%;
            display: inline-block;
        }

        .badge-pill-amber {
            background: var(--color-amber-light);
            color: var(--color-amber);
            border: 1px solid var(--color-amber-border);
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            text-align: center;
        }

        .chip-monospace-blue {
            font-family: monospace;
            font-size: 11px;
            font-weight: 700;
            background: var(--color-blue-light);
            color: var(--color-blue);
            border: 1px solid var(--color-blue-border);
            padding: 4px 8px;
            border-radius: 6px;
            word-break: break-all;
            display: block;
            margin-top: 4px;
            text-align: left;
        }

        .block-monospace-gray {
            font-family: monospace;
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid var(--border-color);
            padding: 10px 14px;
            border-radius: 8px;
            word-break: break-all;
            white-space: normal;
            line-height: 1.4;
            display: block;
            margin-top: 6px;
            text-align: left;
            width: 100%;
        }

        /* ── BOTTOM ACTION BAR ── */
        .bottom-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-cancel {
            background: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            color: var(--text-secondary) !important;
            font-weight: 700 !important;
            font-size: 13.5px !important;
            padding: 12px 24px !important;
            border-radius: 10px !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }
        .btn-cancel:hover {
            background: #f8fafc !important;
            color: var(--text-primary) !important;
            border-color: #94a3b8 !important;
        }

        .btn-submit {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 13.5px !important;
            padding: 12px 28px !important;
            border-radius: 10px !important;
            border: none !important;
            box-shadow: 0 4px 10px rgba(30, 58, 95, 0.25) !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease !important;
        }
        .btn-submit:hover:not([disabled]) {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 14px rgba(30, 58, 95, 0.35) !important;
            filter: brightness(1.1);
        }
        .btn-submit[disabled] {
            background: #cbd5e1 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
            opacity: 0.7;
        }
    </style>
</head>

<body>

    <div class="main-container">

        <!-- ═══ PAGE HEADER (TOP BAR) ═══ -->
        <div class="top-bar">
            <div class="top-bar-left">
                <div class="top-bar-icon">🧶</div>
                <div class="top-bar-title-wrap">
                    <h1>Generate Knit Card</h1>
                    <p class="top-bar-subtitle">Create production card for knitting operations</p>
                </div>
            </div>
            <div class="top-bar-right">
                <span class="pill-badge">Program #<?php echo $p_kptid; ?></span>
                <span class="pill-badge">Sub TID: <?php echo htmlspecialchars($p_sub_tid ?: 'N/A'); ?></span>
                <a href="knitting_program_list.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Back to Programs
                </a>
            </div>
        </div>

        <!-- ═══ ERROR DISPLAY ═══ -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 p-3 border-0 shadow-sm" style="background:#fef2f2; color:#991b1b;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ═══ FORM & WORKSPACE LAYOUT ═══ -->
        <form method="POST" action="knit_card_generate.php?program_id=<?php echo $p_kptid; ?>">
            <input type="hidden" name="save_knit_card" value="1">
            <input type="hidden" name="program_id" value="<?php echo $p_kptid; ?>">

            <div class="workspace-grid">
                
                <!-- Left Column: Target Production Settings Card -->
                <div class="workspace-card">
                    <div class="card-header-custom">
                        <h4 class="card-header-title">
                            <span style="font-size:16px;">⚙️</span> Target Production Settings
                        </h4>
                        <span class="badge-pill-header badge-status-blue">Editable</span>
                    </div>

                    <!-- Metrics Row -->
                    <div class="metrics-row">
                        <div class="metric-col">
                            <span class="metric-val-blue"><?php echo number_format($program_qty, 2); ?></span>
                            <span class="metric-lbl">Program Qty (KG)</span>
                        </div>
                        <div class="metric-col">
                            <span class="metric-val-gray"><?php echo number_format($already_carded, 2); ?></span>
                            <span class="metric-lbl">Already Carded (KG)</span>
                        </div>
                        <div class="metric-col">
                            <span class="metric-val-green"><?php echo number_format($remaining_qty, 2); ?></span>
                            <span class="metric-lbl">Remaining (KG)</span>
                        </div>
                    </div>

                    <!-- Completion percentage & dynamic progress bar -->
                    <?php
                        $completion_pct = ($program_qty > 0) ? ($already_carded / $program_qty) * 100 : 0;
                        $completion_pct = min(100, max(0, $completion_pct));
                    ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="metric-lbl" style="font-size: 10px;">Completion Status</span>
                        <span class="metric-lbl" style="font-size: 10px; font-weight: 800; color: var(--color-blue);"><?php echo number_format($completion_pct, 0); ?>% Completion</span>
                    </div>
                    <div class="progress-bar-container">
                        <div id="progressBarFill" class="progress-bar-fill"></div>
                    </div>

                    <!-- Allocation Fields -->
                    <!-- Machine Number & Shift Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label-custom required-label">Machine Number (M/C No)</label>
                            <select name="MCNO" class="form-select-custom" required>
                                <option value="">-- Select Knitting Machine --</option>
                                <?php foreach ($mcno_list as $mc): ?>
                                    <option value="<?php echo htmlspecialchars($mc); ?>" <?php echo ($p_mcno === $mc) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label-custom">SHIFT (AUTO)</label>
                            <input type="text" name="SHIFT" class="form-input-custom" value="<?php echo $shift; ?>" readonly style="background-color: #f8fafc !important; cursor: not-allowed;">
                        </div>
                    </div>

                    <!-- Required Quantity (KG) -->
                    <div class="mb-2">
                        <label class="form-label-custom required-label">Required Quantity (KG)</label>
                        <div class="quantity-input-wrapper">
                            <input type="number" step="0.01" min="0.01" max="<?php echo htmlspecialchars($remaining_qty); ?>" name="REQ_QTY" class="form-input-custom" value="<?php echo htmlspecialchars($default_qty); ?>" required placeholder="Enter quantity">
                            <span class="input-group-addon-custom">KG</span>
                        </div>
                        <div class="validation-msg mt-2 d-flex align-items-center gap-1">
                            <i class="fa-solid fa-circle-check"></i> Within remaining capacity
                        </div>
                    </div>
                </div>

                <!-- Right Column: Program Details & Specifications Card -->
                <div class="workspace-card">
                    <div class="card-header-custom">
                        <h4 class="card-header-title">
                            <span style="font-size:16px;">📋</span> Program Details & Specifications
                        </h4>
                        <span class="badge-pill-header badge-status-purple">Auto-Populated</span>
                    </div>

                    <div class="specs-inner-grid">
                        
                        <!-- Column 1 — Order & Customer -->
                        <div class="spec-box-col">
                            <span class="spec-box-title">Order & Customer</span>
                            
                            <div class="spec-row">
                                <span class="spec-label">Buyer Name</span>
                                <span class="spec-value <?php echo !empty($p_buyer) ? 'value-highlight-blue' : ''; ?>">
                                    <?php echo htmlspecialchars($p_buyer ?: 'N/A'); ?>
                                </span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">PO Number</span>
                                <span class="spec-value <?php echo empty($p_booking) ? 'value-muted-italic' : ''; ?>">
                                    <?php echo htmlspecialchars($p_booking ?: 'N/A'); ?>
                                </span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Sales Order</span>
                                <span class="spec-value"><?php echo htmlspecialchars($p_sono ?: 'N/A'); ?></span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Style No</span>
                                <span class="spec-value <?php echo !empty($p_style) ? 'value-highlight-blue' : ''; ?>">
                                    <?php echo htmlspecialchars($p_style ?: 'N/A'); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Column 2 — Fabric Specifications -->
                        <div class="spec-box-col">
                            <span class="spec-box-title">Fabric Specifications</span>
                            
                            <div class="spec-row">
                                <span class="spec-label">Fabric Type</span>
                                <span class="spec-value"><?php echo htmlspecialchars($p_fabrics ?: 'N/A'); ?></span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Finish Dia</span>
                                <span class="spec-value"><?php echo htmlspecialchars($p_finish_dia ?: 'N/A'); ?></span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Finish GSM</span>
                                <span class="spec-value"><?php echo htmlspecialchars($p_finish_gsm ?: 'N/A'); ?></span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Open/Tube</span>
                                <span class="spec-value">
                                    <span class="badge-pill-green">
                                        <span class="dot-green"></span>
                                        <?php echo ($p_open_tube === 'T') ? 'Tube (T)' : 'Open (O)'; ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Color</span>
                                <span class="spec-value">
                                    <span class="badge-pill-color">
                                        <span class="dot-color"></span>
                                        <?php echo htmlspecialchars($p_color ?: 'N/A'); ?>
                                    </span>
                                </span>
                            </div>
                            
                            <!-- Dynamic Extraction of Finish -->
                            <?php
                                $finish_name = 'N/A';
                                if (!empty($p_knit_m_desc)) {
                                    $parts = explode('|', $p_knit_m_desc);
                                    if (isset($parts[4]) && trim($parts[4]) !== '') {
                                        $finish_name = str_replace('+', ' + ', trim($parts[4]));
                                    }
                                }
                            ?>
                            <div class="spec-row">
                                <span class="spec-label">Finish</span>
                                <span class="spec-value mt-1">
                                    <span class="badge-pill-amber"><?php echo htmlspecialchars($finish_name); ?></span>
                                </span>
                            </div>
                        </div>

                        <!-- Column 3 — Yarn & Materials -->
                        <div class="spec-box-col">
                            <span class="spec-box-title">Yarn & Materials</span>
                            
                            <div class="spec-row">
                                <span class="spec-label">Yarn Type & Count</span>
                                <span class="spec-value" style="font-size:12px;"><?php echo htmlspecialchars($p_yarn_type ?: 'N/A'); ?> (<?php echo htmlspecialchars($p_yarn_count ?: 'N/A'); ?>)</span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Lot No</span>
                                <span class="spec-value"><?php echo htmlspecialchars($p_lot_no ?: 'N/A'); ?></span>
                            </div>
                            
                            <?php
                                $gen_date = !empty($prog['CREATED_DATE']) ? date('Y-m-d', strtotime($prog['CREATED_DATE'])) : date('Y-m-d');
                            ?>
                            <div class="spec-row">
                                <span class="spec-label">Generation Date</span>
                                <span class="spec-value"><?php echo htmlspecialchars($gen_date); ?></span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Material Code</span>
                                <span class="chip-monospace-blue"><?php echo htmlspecialchars($p_knit_mat_code ?: 'N/A'); ?></span>
                            </div>
                            
                            <div class="spec-row">
                                <span class="spec-label">Knit Material Description</span>
                                <span class="block-monospace-gray"><?php echo htmlspecialchars($p_knit_m_desc ?: 'N/A'); ?></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar -->
            <div class="bottom-actions">
                <a href="knitting_program_list.php" class="btn btn-cancel">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </a>
                <button type="submit" class="btn btn-submit" <?php echo ($remaining_qty <= 0) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                    <i class="fa-solid fa-floppy-disk"></i> Save & Generate Knit Card
                </button>
            </div>
        </form>
    </div>

    <!-- ═══ METRICS ANIMATION SCRIPT ═══ -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                var fill = document.getElementById("progressBarFill");
                if (fill) {
                    fill.style.width = "<?php echo number_format($completion_pct, 2); ?>%";
                }
            }, 150);
        });
    </script>

</body>
</html>
