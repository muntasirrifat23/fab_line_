<?php
// knit_card_view.php - Physical Factory Knit Card Sheet Layout & Production Log
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

// ── Fetch MCNO list for dropdown ──────────────────────────────────────────────
$mcno_list = [];
$mcno_res = $db->query("SELECT MCNO FROM mcno ORDER BY MCNO ASC");
if ($mcno_res) {
    while ($mcno_row = $mcno_res->fetch_assoc()) {
        $mcno_list[] = $mcno_row['MCNO'];
    }
}

// ── Fetch Knit Card Header joined with Knitting Program & Input ─────────────
$sql = "
    SELECT 
        kc.*, 
        kp.MAIN_TID AS MCARD, kp.PO_NUMBER AS KP_PO, kp.SONO AS KP_SONO, kp.BUYER AS KP_BUYER,
        kp.STYLE AS KP_STYLE, kp.COLOR AS KP_COLOR, kp.QTY AS KP_QTY, kp.FGSM AS KP_FGSM,
        kp.FDIA AS KP_FDIA, kp.O_T AS KP_OT, kp.FTYPE AS KP_FTYPE, kp.YTYPE AS KP_YTYPE,
        kp.SUPPLIER AS KP_SUPPLIER, kp.YCOUNT AS KP_YCOUNT, kp.SL AS KP_SL, kp.MCDIA AS KP_MCDIA,
        kp.GGSM AS KP_GGSM, kp.FEEDER_PLAN AS KP_FEEDER_PLAN, kp.LOT AS KP_LOT, kp.SHIFT AS KP_SHIFT,
        kp.KNIT_MATERIAL_CODE AS KP_KMC, kp.KNIT_M_DESCRIPTION AS KP_KMD
    FROM knit_card kc 
    LEFT JOIN knitting_program kp ON kc.KPTID = kp.KPTID 
    WHERE kc.KCID = ?
";

$stmt = $db->prepare($sql);
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

// Count existing rolls to generate roll number
$roll_count = 1;
$c_stmt = $db->prepare("SELECT COUNT(*) AS total_rolls FROM knitting_inspection WHERE KNIT_CARD_ID = ?");
if ($c_stmt) {
    $c_stmt->bind_param("i", $card_id);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result();
    if ($c_res && $c_row = $c_res->fetch_assoc()) {
        $roll_count = intval($c_row['total_rolls']) + 1;
    }
    $c_stmt->close();
}
$suggested_roll = "R-" . $card_id . "-" . sprintf("%02d", $roll_count);

// Build ENRICHED QR Code payload JSON containing complete card metadata
$qr_data_assoc = [
    'card_id'      => $card_id,
    'kptid'        => intval($card['KPTID'] ?? 0),
    'mcard'        => $card['MCARD'] ?? 'N/A',
    'roll_no'      => $suggested_roll,
    'buyer'        => $card['BUYER'] ?? 'N/A',
    'style'        => $card['STYLE'] ?? 'N/A',
    'sono'         => $card['SONO'] ?? 'N/A',
    'booking'      => $card['BOOKING'] ?? 'N/A',
    'mcno'         => $card['MCNO'] ?? 'N/A',
    'finish_dia'   => $card['FINISH_DIA'] ?? 'N/A',
    'finish_gsm'   => $card['FINISH_GSM'] ?? 'N/A',
    'fabrics_type' => $card['FABRICS_TYPE'] ?? 'N/A',
    'yarn_type'    => $card['YARN_TYPE'] ?? 'N/A',
    'lot_no'       => $card['LOT_NO'] ?? 'N/A',
    'req_qty'      => floatval($card['REQ_QTY'] ?? 0)
];
$qr_payload = json_encode($qr_data_assoc, JSON_UNESCAPED_SLASHES);

// ── Handle: Update Header Form (MCNO + REQ_QTY are editable) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_header'])) {
    $f_req_qty = floatval($_POST['REQ_QTY'] ?? 0);
    $f_mcno    = trim($_POST['MCNO'] ?? '');

    if ($f_req_qty <= 0) {
        $error = "Required Quantity must be a positive number.";
    } elseif (empty($f_mcno)) {
        $error = "Machine Number (M/C No) is required.";
    } else {
        $db->begin_transaction();

        try {
            $stmt_kptid = $db->prepare("SELECT KPTID FROM knit_card WHERE KCID = ?");
            if (!$stmt_kptid) throw new Exception("Database error: " . $db->error);
            $stmt_kptid->bind_param("i", $card_id);
            $stmt_kptid->execute();
            $res_kptid = $stmt_kptid->get_result();
            if (!$res_kptid || $res_kptid->num_rows === 0) {
                $stmt_kptid->close();
                throw new Exception("Knit Card not found.");
            }
            $card_kptid = intval($res_kptid->fetch_assoc()['KPTID']);
            $stmt_kptid->close();

            $stmt_lock = $db->prepare("SELECT QTY FROM knitting_program WHERE KPTID = ? FOR UPDATE");
            if (!$stmt_lock) throw new Exception("Database lock error: " . $db->error);
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

            $stmt_other = $db->prepare("SELECT SUM(REQ_QTY) AS other_carded FROM knit_card WHERE KPTID = ? AND KCID != ?");
            if (!$stmt_other) throw new Exception("Database query error: " . $db->error);
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
                throw new Exception("Required quantity cannot exceed remaining program quantity (" . number_format($max_allowed_qty, 2) . " KG).");
            }

            $upd = $db->prepare("UPDATE knit_card SET REQ_QTY = ?, MCNO = ? WHERE KCID = ?");
            if (!$upd) throw new Exception("Failed to prepare update query: " . $db->error);
            $upd->bind_param("dsi", $f_req_qty, $f_mcno, $card_id);
            if (!$upd->execute()) {
                $upd_err = $upd->error;
                $upd->close();
                throw new Exception("Failed to update Knit Card: " . $upd_err);
            }
            $upd->close();

            $db->commit();
            $msg = "Card updated successfully! (M/C No & Quantity saved)";
            $card['REQ_QTY'] = $f_req_qty;
            $card['MCNO']    = $f_mcno;

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
        $db->begin_transaction();

        try {
            $qs = $db->prepare("SELECT REQ_QTY FROM knit_card WHERE KCID = ? FOR UPDATE");
            if (!$qs) throw new Exception("Prepare failed: " . $db->error);
            $qs->bind_param("i", $card_id);
            $qs->execute();
            $qr = $qs->get_result()->fetch_assoc();
            if (!$qr) {
                $qs->close();
                throw new Exception("Knit Card not found.");
            }
            $target_qty = floatval($qr['REQ_QTY']);
            $qs->close();

            $ps = $db->prepare("SELECT CUM_TOTAL FROM knit_card_production WHERE KCID = ? ORDER BY LOG_DATE DESC, KCPID DESC LIMIT 1 FOR UPDATE");
            if (!$ps) throw new Exception("Prepare failed: " . $db->error);
            $ps->bind_param("i", $card_id);
            $ps->execute();
            $pr_res = $ps->get_result();
            $prev_cum = 0.00;
            if ($pr_res && $pr_res->num_rows > 0) {
                $prev_cum = floatval($pr_res->fetch_assoc()['CUM_TOTAL']);
            }
            $ps->close();

            $cum_total = $prev_cum + $production_qty;
            $balance   = max(0, $target_qty - $cum_total);

            $ins = $db->prepare("
                INSERT INTO knit_card_production
                    (KCID, LOG_DATE, A_SHIFT_QTY, B_SHIFT_QTY, C_SHIFT_QTY, PRODUCTION_QTY, CUM_TOTAL, BALANCE, OPERATOR_A, OPERATOR_B, OPERATOR_C)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$ins) throw new Exception("Prepare failed: " . $db->error);
            
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

            $db->commit();
            $msg = "Daily production entry logged successfully!";
        } catch (Exception $e) {
            $db->rollback();
            $error = "Error adding production log: " . $e->getMessage();
        }
    }
}

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

// Program Qty & Max Allowed Calculation
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
    <title>Knit Card #<?php echo $card_id; ?> | Factory Physical Layout & Production Sheet</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- QR CODE GENERATOR LIBRARY -->
    <script src="js/qrcode.min.js"></script>

    <style>
        :root {
            --color-bg: #f8fafc;
            --color-card: #ffffff;
            --color-primary: #0f172a;
            --color-secondary: #475569;
            --border-color: #cbd5e1;
        }

        body {
            background-color: var(--color-bg);
            font-family: 'Inter', sans-serif;
            color: var(--color-primary);
            padding: 24px;
        }

        .main-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        /* ── ACTION TOP BANNER (NO-PRINT) ── */
        .top-action-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
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

        .btn-print-card {
            background: #10b981 !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 14px !important;
            padding: 10px 24px !important;
            border-radius: 30px !important;
            border: none !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3) !important;
            transition: all 0.2s ease;
        }
        .btn-print-card:hover {
            background: #059669 !important;
            transform: translateY(-2px);
        }

        /* ── PHYSICAL FACTORY KNIT CARD CONTAINER ── */
        .physical-knit-card {
            background: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
        }

        .card-header-factory {
            background: #0f172a;
            color: #ffffff;
            padding: 20px 28px;
            border-bottom: 2px solid #0f172a;
        }

        .card-param-table th {
            background-color: #f1f5f9 !important;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            padding: 8px 12px;
            vertical-align: middle;
            border-color: #cbd5e1 !important;
        }
        .card-param-table td {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            padding: 8px 12px;
            vertical-align: middle;
            border-color: #cbd5e1 !important;
        }

        /* ── PRINT-FRIENDLY CSS RULES ── */
        @media print {
            .no-print, .top-action-bar, .alert, form button, .btn-dashboard, nav, header {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
            }
            .main-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .physical-knit-card {
                border: 2px solid #000000 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
                page-break-inside: avoid;
            }
            .card-header-factory {
                background: #000000 !important;
                color: #ffffff !important;
                padding: 14px 20px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .card-param-table th, .card-param-table td {
                border: 1px solid #000000 !important;
                color: #000000 !important;
                padding: 6px 10px !important;
                font-size: 12px !important;
            }
            .card-param-table th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="main-container">

        <!-- ── ACTION TOP BAR (NO-PRINT) ── -->
        <div class="top-action-bar no-print">
            <div class="d-flex align-items-center gap-3">
                <div style="font-size:26px; background:rgba(255,255,255,0.1); padding:10px 14px; border-radius:12px;">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold mb-0">Physical Factory Knit Card Sheet</h1>
                    <p class="mb-0 text-white-50 small">Standard Production Layout & Enriched QR Code</p>
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <a href="knit_card_report.php" class="btn btn-outline-light rounded-pill px-3 btn-sm fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> Card Directory
                </a>
                <a href="knitting_inspection.php" class="btn btn-info text-dark rounded-pill px-3 btn-sm fw-bold">
                    <i class="fa-solid fa-list-check me-1"></i> Inspection Matrix
                </a>
                <button type="button" onclick="window.print()" class="btn-print-card">
                    <i class="fa-solid fa-print"></i> Print Knit Card
                </button>
            </div>
        </div>

        <!-- ── ALERTS (NO-PRINT) ── -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 p-3 no-print">
                <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 p-3 no-print">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════════════
             PHYSICAL FACTORY KNIT CARD CONTAINER (EXACT MATCH TO FACTORY TICKET)
        ══════════════════════════════════════════════════════════════════════ -->
        <div class="physical-knit-card">
            
            <!-- HEADER -->
            <div class="card-header-factory">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1.5px; font-size: 24px;">PURBANI FABRICS LIMITED</h2>
                        <div class="small fw-semibold text-info text-uppercase" style="letter-spacing: 0.5px;">Knitting Production & Roll Quality Information Ticket</div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-warning text-dark font-monospace fs-5 px-3 py-2 border border-dark">CARD #<?php echo $card_id; ?></span>
                    </div>
                </div>
            </div>

            <!-- SHEET BODY -->
            <div class="p-4">
                <div class="row g-4 align-items-center">
                    
                    <!-- LEFT COLUMN: LARGE HIGH-RES QR CODE & BARCODE -->
                    <div class="col-lg-3 col-md-4 text-center border-end pe-md-4">
                        <div class="p-3 bg-light rounded-4 border mb-3 shadow-sm d-inline-block">
                            <div id="physical_card_qrcode"></div>
                        </div>
                        <div class="badge bg-secondary font-monospace px-3 py-2 fs-6 mb-2">ROLL #<?php echo htmlspecialchars($suggested_roll); ?></div>
                        <div class="text-muted small">Scan for Instant Floor Inspection</div>
                    </div>

                    <!-- RIGHT COLUMN: MULTI-COLUMN PARAMETER GRID (MATCHING ALL 24 REQUIRED FIELDS) -->
                    <div class="col-lg-9 col-md-8">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle card-param-table mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width:14%;">KCTID / ID</th>
                                        <td style="width:19%;" class="text-primary fs-6">#<?php echo $card_id; ?></td>
                                        <th style="width:14%;">KPTID</th>
                                        <td style="width:19%;">#<?php echo intval($card['KPTID']); ?></td>
                                        <th style="width:14%;">MCARD</th>
                                        <td style="width:20%;"><?php echo htmlspecialchars($card['MCARD'] ?: 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>ROLL NO</th>
                                        <td class="text-success fs-6"><?php echo htmlspecialchars($suggested_roll); ?></td>
                                        <th>M/C NO</th>
                                        <td class="text-primary fs-6"><?php echo htmlspecialchars($card['MCNO']); ?></td>
                                        <th>QTY (KG)</th>
                                        <td class="text-danger fs-6"><?php echo number_format(floatval($card['REQ_QTY']), 2); ?> KG</td>
                                    </tr>
                                    <tr>
                                        <th>PO NUMBER</th>
                                        <td><?php echo htmlspecialchars($card['BOOKING'] ?: $card['KP_PO'] ?: 'N/A'); ?></td>
                                        <th>SONO</th>
                                        <td><?php echo htmlspecialchars($card['SONO'] ?: $card['KP_SONO'] ?: 'N/A'); ?></td>
                                        <th>SHIFT</th>
                                        <td><?php echo htmlspecialchars($card['KP_SHIFT'] ?: 'A/B/C'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>BUYER</th>
                                        <td><?php echo htmlspecialchars($card['BUYER'] ?: 'N/A'); ?></td>
                                        <th>STYLE</th>
                                        <td><?php echo htmlspecialchars($card['STYLE'] ?: 'N/A'); ?></td>
                                        <th>COLOR</th>
                                        <td><?php echo htmlspecialchars($card['KP_COLOR'] ?: 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>FINISH GSM</th>
                                        <td><?php echo htmlspecialchars($card['FINISH_GSM'] ?: 'N/A'); ?></td>
                                        <th>FINISH DIA</th>
                                        <td><?php echo htmlspecialchars($card['FINISH_DIA'] ?: 'N/A'); ?></td>
                                        <th>O / T</th>
                                        <td><?php echo ($card['OPEN_TUBE'] === 'T') ? 'Tube (T)' : 'Open (O)'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>FABRICS</th>
                                        <td colspan="3"><?php echo htmlspecialchars($card['FABRICS_TYPE'] ?: 'N/A'); ?></td>
                                        <th>YARN TYPE</th>
                                        <td><?php echo htmlspecialchars($card['YARN_TYPE'] ?: 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>SUPPLIER</th>
                                        <td><?php echo htmlspecialchars($card['SUPPLIER'] ?: 'N/A'); ?></td>
                                        <th>YARN COUNT</th>
                                        <td><?php echo htmlspecialchars($card['YARN_COUNT'] ?: 'N/A'); ?></td>
                                        <th>SL / VDQ</th>
                                        <td><?php echo htmlspecialchars($card['SL_VDQ'] ?: '0.00'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>MC DIA</th>
                                        <td><?php echo htmlspecialchars($card['KP_MCDIA'] ?: 'N/A'); ?></td>
                                        <th>GRAY GSM</th>
                                        <td><?php echo htmlspecialchars($card['GREY_GSM'] ?: 'N/A'); ?></td>
                                        <th>FEEDER PLAN</th>
                                        <td><?php echo htmlspecialchars($card['KP_FEEDER_PLAN'] ?: 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>LOT NO</th>
                                        <td><?php echo htmlspecialchars($card['LOT_NO'] ?: 'N/A'); ?></td>
                                        <th>KNIT MAT CODE</th>
                                        <td colspan="3"><?php echo htmlspecialchars($card['KP_KMC'] ?: 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>MATERIAL DESC</th>
                                        <td colspan="5"><?php echo htmlspecialchars($card['KNIT_M_DESCRIPTION'] ?: $card['KP_KMD'] ?: 'N/A'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── SECTION 2: EDITABLE M/C & QTY FORM (NO-PRINT) ── -->
        <div class="bg-white p-4 rounded-4 shadow-sm border mb-4 no-print">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Update Machine No & Required Quantity</h5>
                <small class="text-muted"><i class="fa-solid fa-lock me-1"></i> Remaining Program Max Allowed: <strong><?php echo number_format($max_allowed_qty, 2); ?> KG</strong></small>
            </div>

            <form method="POST" action="knit_card_view.php?id=<?php echo $card_id; ?>">
                <input type="hidden" name="update_header" value="1">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label font-monospace fw-bold text-secondary small">MACHINE NUMBER (M/C NO) *</label>
                        <select name="MCNO" class="form-select fw-bold text-primary" style="border:2px solid #2563eb; background:#eff6ff;" required>
                            <option value="">-- Select Machine --</option>
                            <?php foreach ($mcno_list as $mc): ?>
                                <option value="<?php echo htmlspecialchars($mc); ?>" <?php echo (($card['MCNO'] ?? '') === $mc) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-monospace fw-bold text-secondary small">REQUIRED QUANTITY (KG) *</label>
                        <input type="number" step="0.01" min="0.01" max="<?php echo htmlspecialchars($max_allowed_qty); ?>" name="REQ_QTY" class="form-control fw-bold text-primary" style="border:2px solid #2563eb; background:#eff6ff;" value="<?php echo htmlspecialchars($card['REQ_QTY'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-3">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ── SECTION 3: DAILY PRODUCTION LOG TABLE ── -->
        <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-table-list text-primary me-2"></i> Daily Shift Production Logs</h5>
                <span class="text-muted small">Total <strong><?php echo count($logs_array); ?></strong> Entries</span>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>SL#</th>
                            <th>Log Date</th>
                            <th>Shift A (KG)</th>
                            <th>Shift B (KG)</th>
                            <th>Shift C (KG)</th>
                            <th>Daily Prod (KG)</th>
                            <th>Cum Total (KG)</th>
                            <th>Balance (KG)</th>
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
                                    <td><strong>#<?php echo $sl++; ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($prow['LOG_DATE']); ?></strong></td>
                                    <td><?php echo number_format((float)$prow['A_SHIFT_QTY'], 2); ?></td>
                                    <td><?php echo number_format((float)$prow['B_SHIFT_QTY'], 2); ?></td>
                                    <td><?php echo number_format((float)$prow['C_SHIFT_QTY'], 2); ?></td>
                                    <td class="fw-bold text-primary"><?php echo number_format((float)$prow['PRODUCTION_QTY'], 2); ?> KG</td>
                                    <td class="fw-bold text-success"><?php echo number_format((float)$prow['CUM_TOTAL'], 2); ?> KG</td>
                                    <td class="fw-bold text-danger"><?php echo number_format((float)$prow['BALANCE'], 2); ?> KG</td>
                                    <td><small><?php echo htmlspecialchars($prow['OPERATOR_A'] ?? ''); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($prow['OPERATOR_B'] ?? ''); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($prow['OPERATOR_C'] ?? ''); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">No production logs registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ADD LOG FORM (NO-PRINT) -->
            <div class="bg-light p-4 border rounded-3 no-print">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-plus-circle me-1 text-primary"></i> Log New Shift Production Entry</h6>
                <form method="POST" action="knit_card_view.php?id=<?php echo $card_id; ?>">
                    <input type="hidden" name="add_production_log" value="1">
                    <div class="row gx-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label font-monospace small fw-bold">Log Date *</label>
                            <input type="date" name="LOG_DATE" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-monospace small fw-bold">Shift A Qty (KG)</label>
                            <input type="number" step="0.01" min="0" name="A_SHIFT_QTY" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-monospace small fw-bold">Shift B Qty (KG)</label>
                            <input type="number" step="0.01" min="0" name="B_SHIFT_QTY" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-monospace small fw-bold">Shift C Qty (KG)</label>
                            <input type="number" step="0.01" min="0" name="C_SHIFT_QTY" class="form-control" value="0.00">
                        </div>
                    </div>
                    <div class="row gx-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label font-monospace small fw-bold">Operator A</label>
                            <select name="OPERATOR_A" class="form-select">
                                <option value="">-- Select Operator --</option>
                                <?php foreach ($operator_list as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['OPERATOR_ID']); ?>"><?php echo htmlspecialchars($op['OPERATOR_NAME'] . ' (' . $op['OPERATOR_ID'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-monospace small fw-bold">Operator B</label>
                            <select name="OPERATOR_B" class="form-select">
                                <option value="">-- Select Operator --</option>
                                <?php foreach ($operator_list as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['OPERATOR_ID']); ?>"><?php echo htmlspecialchars($op['OPERATOR_NAME'] . ' (' . $op['OPERATOR_ID'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-monospace small fw-bold">Operator C</label>
                            <select name="OPERATOR_C" class="form-select">
                                <option value="">-- Select Operator --</option>
                                <?php foreach ($operator_list as $op): ?>
                                    <option value="<?php echo htmlspecialchars($op['OPERATOR_ID']); ?>"><?php echo htmlspecialchars($op['OPERATOR_NAME'] . ' (' . $op['OPERATOR_ID'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">
                                <i class="fa-solid fa-plus me-1"></i> Add Log Entry
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var qrBox = document.getElementById('physical_card_qrcode');
            const payloadText = <?php echo json_encode($qr_payload); ?>;

            if (qrBox && typeof QRCode !== 'undefined') {
                new QRCode(qrBox, {
                    text: payloadText,
                    width: 170,
                    height: 170,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        });
    </script>
</body>
</html>
