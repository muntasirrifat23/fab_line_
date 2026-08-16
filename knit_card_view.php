<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$card_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg     = isset($_GET['msg'])   ? trim($_GET['msg'])   : '';
$error   = isset($_GET['error']) ? trim($_GET['error']) : '';

if ($card_id <= 0) {
    header("Location: knit_card_report.php?error=Invalid+Card+ID");
    exit();
}

// Build simplified QR Code payload (e.g., KC-12) for less dense QR codes
$qr_payload = "KC-" . $card_id;

// ── Handle: Update Header Form (MCNO + REQ_QTY are editable) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_header'])) {
    $f_req_qty = floatval($_POST['REQ_QTY'] ?? 0);
    $f_mcno    = trim($_POST['MCNO'] ?? '');

    if ($f_req_qty <= 0) {
        $error = "Required Quantity must be a positive number.";
    } elseif (empty($f_mcno)) {
        $error = "Machine Number (M/C No) is required.";
    } else {
        // Start Transaction
        $db->begin_transaction();

        try {
            // First fetch the KPTID for this card
            $stmt_kptid = $db->prepare("SELECT KPTID FROM knit_card WHERE KCID = ?");
            if (!$stmt_kptid) {
                throw new Exception("Database error: " . $db->error);
            }
            $stmt_kptid->bind_param("i", $card_id);
            $stmt_kptid->execute();
            $res_kptid = $stmt_kptid->get_result();
            if (!$res_kptid || $res_kptid->num_rows === 0) {
                $stmt_kptid->close();
                throw new Exception("Knit Card not found.");
            }
            $card_kptid = intval($res_kptid->fetch_assoc()['KPTID']);
            $stmt_kptid->close();

            // Lock the knitting_program row to prevent concurrent modifications on this program
            $stmt_lock = $db->prepare("SELECT QTY FROM knitting_program WHERE KPTID = ? FOR UPDATE");
            if (!$stmt_lock) {
                throw new Exception("Database lock error: " . $db->error);
            }
            $stmt_lock->bind_param("i", $card_kptid);
            $stmt_lock->execute();
            $res_lock = $stmt_lock->get_result();
            if (!$res_lock || $res_lock->num_rows === 0) {
                $stmt_lock->close();
                throw new Exception("Knitting program not found.");
            }
            $lock_row = $res_lock->fetch_assoc();
            $stmt_lock->close();

            $program_qty_locked = floatval($lock_row['QTY'] ?? 0);

            // Fetch the sum of REQ_QTY of other cards for this program (excluding this card)
            $stmt_other = $db->prepare("SELECT SUM(REQ_QTY) AS other_carded FROM knit_card WHERE KPTID = ? AND KCID != ?");
            if (!$stmt_other) {
                throw new Exception("Database query error: " . $db->error);
            }
            $stmt_other->bind_param("ii", $card_kptid, $card_id);
            $stmt_other->execute();
            $res_other = $stmt_other->get_result();
            $other_carded = 0.00;
            if ($res_other && $row_other = $res_other->fetch_assoc()) {
                $other_carded = floatval($row_other['other_carded'] ?? 0);
            }
            $stmt_other->close();

            $max_allowed_qty = $program_qty_locked - $other_carded;

            if ($f_req_qty > $max_allowed_qty) {
                throw new Exception("Required quantity cannot exceed the remaining program quantity (" . number_format($max_allowed_qty, 2) . " KG).");
            }

            // Update the card
            $upd = $db->prepare("UPDATE knit_card SET REQ_QTY = ?, MCNO = ? WHERE KCID = ?");
            if (!$upd) {
                throw new Exception("Failed to prepare update query: " . $db->error);
            }
            $upd->bind_param("dsi", $f_req_qty, $f_mcno, $card_id);
            if (!$upd->execute()) {
                $upd_err = $upd->error;
                $upd->close();
                throw new Exception("Failed to update Knit Card: " . $upd_err);
            }
            $upd->close();

            // Commit Transaction
            $db->commit();
            $msg = "Card updated successfully! (M/C No & Quantity saved)";

        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}


// ── Handle: Add Production Log ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_production_log'])) {
    $log_date    = trim($_POST['LOG_DATE']      ?? '');
    $a_shift     = floatval($_POST['A_SHIFT_QTY'] ?? 0);
    $b_shift     = floatval($_POST['B_SHIFT_QTY'] ?? 0);
    $c_shift     = floatval($_POST['C_SHIFT_QTY'] ?? 0);
    $operator_a  = trim($_POST['OPERATOR_A']    ?? '');
    $operator_b  = trim($_POST['OPERATOR_B']    ?? '');
    $operator_c  = trim($_POST['OPERATOR_C']    ?? '');

    if (empty($log_date)) {
        $error = "Log date is required.";
    } elseif ($a_shift < 0 || $b_shift < 0 || $c_shift < 0) {
        $error = "Shift quantities cannot be negative.";
    } else {
        $production_qty = $a_shift + $b_shift + $c_shift;

        // Start Transaction
        $db->begin_transaction();

        try {
            // Fetch REQ_QTY from knit_card with row-level lock (FOR UPDATE)
            $qs = $db->prepare("SELECT REQ_QTY FROM knit_card WHERE KCID = ? FOR UPDATE");
            if (!$qs) {
                throw new Exception("Prepare failed: " . $db->error);
            }
            $qs->bind_param("i", $card_id);
            if (!$qs->execute()) {
                throw new Exception("Execute failed: " . $qs->error);
            }
            $qr = $qs->get_result()->fetch_assoc();
            if (!$qr) {
                $qs->close();
                throw new Exception("Knit Card not found.");
            }
            $target_qty = floatval($qr['REQ_QTY']);
            $qs->close();

            // Fetch previous CUM_TOTAL with lock (FOR UPDATE)
            $ps = $db->prepare("SELECT CUM_TOTAL FROM knit_card_production WHERE KCID = ? ORDER BY LOG_DATE DESC, KCPID DESC LIMIT 1 FOR UPDATE");
            if (!$ps) {
                throw new Exception("Prepare failed: " . $db->error);
            }
            $ps->bind_param("i", $card_id);
            if (!$ps->execute()) {
                throw new Exception("Execute failed: " . $ps->error);
            }
            $pr_res = $ps->get_result();
            $prev_cum = 0.00;
            if ($pr_res && $pr_res->num_rows > 0) {
                $prev_cum = floatval($pr_res->fetch_assoc()['CUM_TOTAL']);
            }
            $ps->close();

            $cum_total = $prev_cum + $production_qty;
            $balance   = max(0, $target_qty - $cum_total);

            // Insert production log using real column names
            $ins = $db->prepare("
                INSERT INTO knit_card_production
                    (KCID, LOG_DATE, A_SHIFT_QTY, B_SHIFT_QTY, C_SHIFT_QTY, PRODUCTION_QTY, CUM_TOTAL, BALANCE, OPERATOR_A, OPERATOR_B, OPERATOR_C)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$ins) {
                throw new Exception("Prepare failed: " . $db->error);
            }
            $ins->bind_param(
                "isddddddsss",
                $card_id, $log_date, $a_shift, $b_shift, $c_shift, $production_qty, $cum_total, $balance,
                $operator_a, $operator_b, $operator_c
            );
            if (!$ins->execute()) {
                $ins_err = $ins->error;
                $ins->close();
                throw new Exception("Failed to insert production log: " . $ins_err);
            }
            $ins->close();

            // Commit Transaction
            $db->commit();
            $msg = "Daily production entry logged successfully!";
        } catch (Exception $e) {
            $db->rollback();
            $error = "Error adding production log: " . $e->getMessage();
        }
    }
}

// ── Fetch MCNO list for dropdown ──────────────────────────────────────────────
$mcno_list = [];
$mcno_res = $db->query("SELECT MCNO FROM mcno ORDER BY MCNO ASC");
if ($mcno_res) {
    while ($mcno_row = $mcno_res->fetch_assoc()) {
        $mcno_list[] = $mcno_row['MCNO'];
    }
}

// ── Fetch Knit Card Header ─────────────────────────────────────────────────
$stmt = $db->prepare("SELECT kc.*, kp.PO_NUMBER AS kp_booking FROM knit_card kc LEFT JOIN knitting_program kp ON kc.KPTID = kp.KPTID WHERE kc.KCID = ?");
if ($stmt) {
    $stmt->bind_param("i", $card_id);
    $stmt->execute();
    $card_res = $stmt->get_result();
} else {
    $card_res = false;
}

if (!$card_res || $card_res->num_rows == 0) {
    header("Location: knit_card_report.php?error=Card+not+found");
    exit();
}
$card = $card_res->fetch_assoc();

// ── Fetch Production Logs ──────────────────────────────────────────────────
$prod_stmt = $db->prepare("SELECT * FROM knit_card_production WHERE KCID = ? ORDER BY LOG_DATE ASC, KCPID ASC");
if ($prod_stmt) {
    $prod_stmt->bind_param("i", $card_id);
    $prod_stmt->execute();
    $prod_result = $prod_stmt->get_result();
} else {
    $prod_result = false;
}

$total_cum_produced = 0.00;
$latest_balance     = floatval($card['REQ_QTY'] ?? 0);
$logs_array = [];
if ($prod_result && $prod_result->num_rows > 0) {
    while ($pr = $prod_result->fetch_assoc()) {
        $logs_array[]       = $pr;
        $total_cum_produced = floatval($pr['CUM_TOTAL']);
        $latest_balance     = floatval($pr['BALANCE']);
    }
}

// Fetch Operator List for dropdown
$operator_list = [];
$op_res = $db->query("SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator ORDER BY OPERATOR_NAME ASC");
if ($op_res) {
    while ($op = $op_res->fetch_assoc()) {
        $operator_list[] = $op;
    }
}

$target_qty     = floatval($card['REQ_QTY'] ?? 0);
$completion_pct = ($target_qty > 0) ? min(100, round(($total_cum_produced / $target_qty) * 100, 1)) : 0;

// Calculate the maximum allowed quantity for this card (to enforce in HTML and validation) (Rule 10)
$program_kptid = intval($card['KPTID']);
$stmt_pqty = $db->prepare("SELECT QTY FROM knitting_program WHERE KPTID = ?");
$program_qty = 0.00;
if ($stmt_pqty) {
    $stmt_pqty->bind_param("i", $program_kptid);
    $stmt_pqty->execute();
    $res_pqty = $stmt_pqty->get_result();
    if ($res_pqty && $row_pqty = $res_pqty->fetch_assoc()) {
        $program_qty = floatval($row_pqty['QTY'] ?? 0);
    }
    $stmt_pqty->close();
}

$stmt_sum_other = $db->prepare("SELECT SUM(REQ_QTY) AS other_carded FROM knit_card WHERE KPTID = ? AND KCID != ?");
$other_carded = 0.00;
if ($stmt_sum_other) {
    $stmt_sum_other->bind_param("ii", $program_kptid, $card_id);
    $stmt_sum_other->execute();
    $res_sum_other = $stmt_sum_other->get_result();
    if ($res_sum_other && $row_sum_other = $res_sum_other->fetch_assoc()) {
        $other_carded = floatval($row_sum_other['other_carded'] ?? 0);
    }
    $stmt_sum_other->close();
}

$max_allowed_qty = max(0.00, $program_qty - $other_carded);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knit Card #<?php echo $card_id; ?> | View & Production Log | Purbani Fabrics</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">
    <script src="js/qrcode.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-teal: #0f172a;
            --dark-teal: #0f172a;
            --accent-green: #10b981;
            --surface-bg: #f8fafc;
            --card-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            --header-from:  #090d22;
            --header-mid:   #0f172a;
            --header-to:    #1e3a8a;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        i, i.fa-solid, i.fas, i.far, i.fab, i.fa-regular {
            border: none !important; outline: none !important; box-shadow: none !important;
            padding: 0 !important; margin: 0 !important; display: inline-block !important; transform: none !important;
        }

        body {
            padding: 24px;
            background-color: var(--surface-bg);
            font-family: var(--font-main);
            color: #334155;
            background-image: radial-gradient(circle at 10% 20%, rgba(30, 58, 138, 0.015) 0%, transparent 60%),
                              radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.015) 0%, transparent 60%);
        }

        /* ═══════════════════════════════════════════
           HEADER BANNER
        ═══════════════════════════════════════════ */
        .top-banner {
            position: relative;
            background: linear-gradient(135deg, var(--header-from) 0%, var(--header-mid) 50%, var(--header-to) 100%);
            color: white;
            padding: 36px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Decorative background blobs */
        .top-banner::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.22) 0%, transparent 70%);
            top: -120px; right: -80px;
            border-radius: 50%;
            pointer-events: none;
        }
        .top-banner::after {
            content: '';
            position: absolute;
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(147, 197, 253, 0.15) 0%, transparent 70%);
            bottom: -20px; left: 80px;
            border-radius: 50%;
            pointer-events: none;
        }

        .top-banner h1 {
            font-weight: 800;
            font-size: 2rem;
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
            line-height: 1.15;
            background: linear-gradient(135deg, #ffffff 60%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Nav buttons */
        .nav-btn {
            border-radius: 12px;
            font-weight: 700;
            font-size: 13.5px;
            padding: 10px 20px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            backdrop-filter: blur(10px);
        }
        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-blue-solid {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: 1px solid rgba(96, 165, 250, 0.3);
            color: white;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }
        .btn-blue-solid:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
        }

        /* ═══════════════════════════════════════════
           CONTENT PANELS
        ═══════════════════════════════════════════ */
        .content-panel {
            background: #ffffff;
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            display: block !important;
            width: 100% !important;
            margin-bottom: 8px !important;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
        }

        .form-control, .form-select {
            display: block !important;
            width: 100% !important;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #0f172a;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            background-color: #ffffff;
            outline: none;
        }

        .content-panel .row > [class*="col-"] {
            margin-bottom: 20px !important;
        }

        /* ═══════════════════════════════════════════
           STAT BOXES
        ═══════════════════════════════════════════ */
        .stat-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 22px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
            transition: all 0.25s ease;
        }
        .stat-box:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
            box-shadow: 0 10px 20px rgba(0,0,0,0.02);
        }

        /* ═══════════════════════════════════════════
           TABLES
        ═══════════════════════════════════════════ */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }
        .custom-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .custom-table thead th {
            background: #0f172a;
            color: #f8fafc;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 16px;
            vertical-align: middle;
            border: none;
            border-bottom: 2px solid #1e293b;
        }
        .custom-table tbody td {
            padding: 16px;
            font-size: 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-weight: 500;
        }
        .custom-table tbody tr {
            transition: background-color 0.2s ease;
        }
        .custom-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* ═══════════════════════════════════════════
           BUTTONS
        ═══════════════════════════════════════════ */
        .btn-teal {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: white;
            font-weight: 700;
            border-radius: 12px;
            padding: 11px 24px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            transition: all 0.2s ease;
        }
        .btn-teal:hover {
            background: linear-gradient(135deg, #3d75f5 0%, #2563eb 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }

        /* ═══════════════════════════════════════════
           MODALS
        ═══════════════════════════════════════════ */
        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeInModal .25s ease-out;
        }
        @keyframes fadeInModal { from { opacity:0; } to { opacity:1; } }
        
        .custom-modal-container {
            background: #ffffff;
            border-radius: 24px;
            width: 90%;
            max-width: 460px;
            padding: 30px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
            text-align: center;
            position: relative;
            border: 1px solid #e2e8f0;
        }
        .custom-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 24px;
        }
        .custom-modal-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .custom-modal-close {
            background: none;
            border: none;
            font-size: 26px;
            font-weight: 700;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
            padding: 0 4px;
            transition: color 0.2s ease;
        }
        .custom-modal-close:hover { color: #0f172a; }
        .custom-modal-body { padding: 10px 0; }
        
        .qr-img-wrapper {
            background: #ffffff;
            padding: 20px;
            border-radius: 20px;
            display: inline-block;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 20px rgba(0,0,0,.03);
            margin-bottom: 16px;
        }
        .qr-caption { font-size: 14.5px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .qr-url-text { font-size: 12px; font-family: monospace; color: #64748b; margin-bottom: 0; word-break: break-all; }
        
        .custom-modal-footer {
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            margin-top: 24px;
            display: flex;
            justify-content: center;
        }
        .custom-modal-btn {
            border-radius: 12px;
            padding: 10px 28px;
            font-weight: 700;
            font-size: 14.5px;
        }
    </style>
</head>

<body>

    <div class="container-fluid" style="max-width: 1350px;">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="top-banner">
            <div class="banner-inner">
                <!-- Left: icon + title -->
                <div class="banner-title-group">
                    <div class="banner-icon-wrap">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <h1>Knit Card #<?php echo $card_id; ?> Details</h1>
                        <p class="banner-subtitle">
                            Date: <strong style="color:white;"><?php echo htmlspecialchars($card['CARD_DATE']); ?></strong> &nbsp;|&nbsp;
                            Machine: <strong style="color:white;">M/C <?php echo htmlspecialchars($card['MCNO']); ?></strong> &nbsp;|&nbsp;
                            Buyer: <strong style="color:white;"><?php echo htmlspecialchars($card['BUYER']); ?></strong>
                        </p>
                    </div>
                </div>
                <!-- Right: nav buttons -->
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="knit_card_report.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-arrow-left"></i> Back to Directory
                    </a>
                    <a href="knitting_program_list.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-list-check"></i> Programs List
                    </a>
                    <button type="button" class="btn nav-btn btn-glass" id="btnOpenQrModal">
                        <i class="fa-solid fa-qrcode"></i> QR Code
                    </button>
                    <a href="knit_card_print.php?id=<?php echo $card_id; ?>" target="_blank" class="btn nav-btn btn-blue-solid">
                        <i class="fa-solid fa-print"></i> Print Floor Card
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 p-3">
                <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 p-3">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Progress Summary -->
        <div class="content-panel p-4 mb-4">
            <div class="row align-items-center g-3">
                <div class="col-md-3">
                    <div class="stat-box">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Target Quantity</small>
                        <h4 class="mb-0 fw-bold text-dark"><?php echo number_format($target_qty, 2); ?> <small style="font-size:14px;">KG</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Cumulative Produced</small>
                        <h4 class="mb-0 fw-bold text-success"><?php echo number_format($total_cum_produced, 2); ?> <small style="font-size:14px;">KG</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Remaining Balance</small>
                        <h4 class="mb-0 fw-bold text-danger"><?php echo number_format($latest_balance, 2); ?> <small style="font-size:14px;">KG</small></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Production Completion</small>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:12px; border-radius:6px;">
                                <div class="progress-bar bg-success" style="width:<?php echo $completion_pct; ?>%;"></div>
                            </div>
                            <span class="fw-bold small text-dark"><?php echo $completion_pct; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 1: Card Header Editable Form -->
        <div class="content-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-section-title mb-0 border-0 p-0">
                    <i class="fa-solid fa-sliders me-1"></i> Card Specifications Header
                </div>
                <small class="text-muted"><i class="fa-solid fa-lock me-1"></i> Auto-generated specs are read-only. M/C No and Quantity can be modified.</small>
            </div>

            <form method="POST" action="knit_card_view.php?id=<?php echo $card_id; ?>">
                <input type="hidden" name="update_header" value="1">

                <div class="row gx-3">
                    <div class="col-md-2">
                        <label class="form-label text-primary fw-bold"><i class="fa-solid fa-pen-to-square me-1"></i> M/C No <span class="text-danger">*</span></label>
                        <select name="MCNO" class="form-select fw-bold text-primary" style="border: 2px solid #2563eb; background:#eff6ff;" required>
                            <option value="">-- Select Machine --</option>
                            <?php foreach ($mcno_list as $mc): ?>
                                <option value="<?php echo htmlspecialchars($mc); ?>" <?php echo (($card['MCNO'] ?? '') === $mc) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Finish Dia</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['FINISH_DIA'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Grey GSM</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['GREY_GSM'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Finish GSM</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['FINISH_GSM'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">SL / VDQ</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['SL_VDQ'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Open/Tube</label><input type="text" class="form-control" value="<?php echo ($card['OPEN_TUBE'] ?? '') === 'T' ? 'Tube (T)' : 'Open (O)'; ?>" readonly></div>
                </div>

                <div class="row gx-3">
                    <div class="col-md-2"><label class="form-label">Buyer</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['BUYER'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Supplier</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['SUPPLIER'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">PO Number</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['BOOKING'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">SONO</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['SONO'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Style</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['STYLE'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Fabric Type</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['FABRICS_TYPE'] ?? ''); ?>" readonly></div>
                </div>

                <div class="row gx-3">
                    <div class="col-md-2"><label class="form-label">Yarn Type</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['YARN_TYPE'] ?? ''); ?>" readonly></div>
                    <div class="col-md-2"><label class="form-label">Yarn Count</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['YARN_COUNT'] ?? ''); ?>" readonly></div>
                    <div class="col-md-3"><label class="form-label">Lot No</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['LOT_NO'] ?? ''); ?>" readonly></div>
                    <div class="col-md-3">
                        <label class="form-label text-primary fw-bold"><i class="fa-solid fa-pen-to-square me-1"></i> Req Qty (KG) - Editable</label>
                        <input type="number" step="0.01" min="0.01" max="<?php echo htmlspecialchars($max_allowed_qty); ?>" name="REQ_QTY" class="form-control fw-bold text-primary" style="border: 2px solid #2563eb; background:#eff6ff;" value="<?php echo htmlspecialchars($card['REQ_QTY'] ?? ''); ?>" required>
                        <small class="text-muted" style="font-size: 11px;">Max allowed: <strong><?php echo number_format($max_allowed_qty, 2); ?> KG</strong> (Prog: <?php echo number_format($program_qty, 2); ?>, Other: <?php echo number_format($other_carded, 2); ?>)</small>
                    </div>
                    <div class="col-md-2"><label class="form-label">Prepared By</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['PREPARED_BY'] ?? ''); ?>" readonly></div>
                </div>

                <div class="row gx-3">
                    <div class="col-md-12"><label class="form-label">Knit Material Description</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($card['KNIT_M_DESCRIPTION'] ?? ''); ?>" readonly></div>
                </div>

                <div class="d-flex justify-content-end pt-3 border-top">
                    <button type="submit" class="btn btn-teal"><i class="fa-solid fa-floppy-disk me-1"></i> Update Quantity</button>
                </div>
            </form>
        </div>

        <!-- SECTION 2: Production Log Table -->
        <div class="content-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-section-title mb-0 border-0 p-0">
                    <i class="fa-solid fa-table-list me-1"></i> Daily Production Log Records
                </div>
                <span class="text-muted small">Total <strong><?php echo count($logs_array); ?></strong> log entries</span>
            </div>

            <div class="table-responsive-wrapper mb-4">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px;">SL#</th>
                            <th style="width:120px;">Log Date</th>
                            <th>Shift A (KG)</th>
                            <th>Shift B (KG)</th>
                            <th>Shift C (KG)</th>
                            <th style="background-color:#2563eb; color:white;">Daily Prod (KG)</th>
                            <th style="background-color:#059669; color:white;">Cum. Total (KG)</th>
                            <th style="background-color:#d97706; color:white;">Balance (KG)</th>
                            <th>Operator A</th>
                            <th>Operator B</th>
                            <th>Operator C</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs_array) > 0): ?>
                            <?php $sl = 1; ?>
                            <?php foreach ($logs_array as $prow): ?>
                                <tr>
                                    <td class="text-center fw-bold">#<?php echo $sl++; ?></td>
                                    <td><i class="fa-regular fa-calendar me-1 text-muted"></i><strong><?php echo htmlspecialchars($prow['LOG_DATE']); ?></strong></td>
                                    <td><?php echo number_format((float)$prow['A_SHIFT_QTY'], 2); ?></td>
                                    <td><?php echo number_format((float)$prow['B_SHIFT_QTY'], 2); ?></td>
                                    <td><?php echo number_format((float)$prow['C_SHIFT_QTY'], 2); ?></td>
                                    <td class="fw-bold text-primary" style="background-color:#eff6ff;"><?php echo number_format((float)$prow['PRODUCTION_QTY'], 2); ?> KG</td>
                                    <td class="fw-bold text-success" style="background-color:#f0fdf4;"><?php echo number_format((float)$prow['CUM_TOTAL'], 2); ?> KG</td>
                                    <td class="fw-bold text-danger" style="background-color:#fffbeb;"><?php echo number_format((float)$prow['BALANCE'], 2); ?> KG</td>
                                    <td><small class="text-secondary"><?php echo htmlspecialchars($prow['OPERATOR_A'] ?? ''); ?></small></td>
                                    <td><small class="text-secondary"><?php echo htmlspecialchars($prow['OPERATOR_B'] ?? ''); ?></small></td>
                                    <td><small class="text-secondary"><?php echo htmlspecialchars($prow['OPERATOR_C'] ?? ''); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                                    <h6 class="fw-bold">No Daily Production Log Entries</h6>
                                    <p class="small mb-0">Use the form below to enter the first shift production entry for this card.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Production Log Form -->
            <div class="bg-light p-4 border rounded-3" style="border-color:#e2e8f0 !important;">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-plus-circle me-1" style="color:var(--primary-teal);"></i> Add Daily Production Log Entry</h6>
                <form method="POST" action="knit_card_view.php?id=<?php echo $card_id; ?>">
                    <input type="hidden" name="add_production_log" value="1">
                    <div class="row gx-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Log Date <span class="text-danger">*</span></label>
                            <input type="date" name="LOG_DATE" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Shift A Qty (KG)</label>
                            <input type="number" step="0.01" min="0" name="A_SHIFT_QTY" class="form-control" placeholder="0.00" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Shift B Qty (KG)</label>
                            <input type="number" step="0.01" min="0" name="B_SHIFT_QTY" class="form-control" placeholder="0.00" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Shift C Qty (KG)</label>
                            <input type="number" step="0.01" min="0" name="C_SHIFT_QTY" class="form-control" placeholder="0.00" value="0.00">
                        </div>
                    </div>
                    <div class="row gx-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Operator A</label>
                            <select name="OPERATOR_A" class="form-select">
                                <option value="">-- Select Operator --</option>
                                <?php foreach ($operator_list as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['OPERATOR_ID']); ?>"><?php echo htmlspecialchars($op['OPERATOR_NAME'] . ' (' . $op['OPERATOR_ID'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Operator B</label>
                            <select name="OPERATOR_B" class="form-select">
                                <option value="">-- Select Operator --</option>
                                <?php foreach ($operator_list as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['OPERATOR_ID']); ?>"><?php echo htmlspecialchars($op['OPERATOR_NAME'] . ' (' . $op['OPERATOR_ID'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Operator C</label>
                            <select name="OPERATOR_C" class="form-select">
                                <option value="">-- Select Operator --</option>
                                <?php foreach ($operator_list as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['OPERATOR_ID']); ?>"><?php echo htmlspecialchars($op['OPERATOR_NAME'] . ' (' . $op['OPERATOR_ID'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-teal w-100 py-2">
                                <i class="fa-solid fa-plus me-1"></i> Add Log Entry
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- QR Code Modal -->
    <div id="customQrModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal-container">
            <div class="custom-modal-header">
                <h5 class="custom-modal-title"><i class="fa-solid fa-qrcode" style="color:#00796b;"></i> Live Knit Card QR Code</h5>
                <button type="button" class="custom-modal-close" id="btnCloseQrModalX">&times;</button>
            </div>
            <div class="custom-modal-body">
                <div class="qr-img-wrapper"><div id="modal_qrcode"></div></div>
                <p class="qr-caption">Scan to view live card</p>
                <p class="qr-url-text"><?php echo htmlspecialchars($qr_payload); ?></p>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="btn btn-secondary custom-modal-btn" id="btnCloseQrModal">Close</button>
            </div>
        </div>
    </div>

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal   = document.getElementById('customQrModal');
            var btnOpen = document.getElementById('btnOpenQrModal');
            var btnCloseX = document.getElementById('btnCloseQrModalX');
            var btnClose  = document.getElementById('btnCloseQrModal');
            var qrBox   = document.getElementById('modal_qrcode');

            if (qrBox && typeof QRCode !== 'undefined') {
                new QRCode(qrBox, {
                    text: "<?php echo $qr_payload; ?>",
                    width: 220, height: 220,
                    colorDark: "#000000", colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }

            function openModal()  { if (modal) modal.style.display = 'flex'; }
            function closeModal() { if (modal) modal.style.display = 'none'; }

            if (btnOpen)   btnOpen.addEventListener('click', openModal);
            if (btnCloseX) btnCloseX.addEventListener('click', closeModal);
            if (btnClose)  btnClose.addEventListener('click', closeModal);
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModal();
                });
            }
        });
    </script>
</body>
</html>
