<?php
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #f1f5f9;
            --surface-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --brand-blue: #0284c7;
            --brand-blue-hover: #0369a1;
            --brand-blue-light: #f0f9ff;
            --border-color: #cbd5e1;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        i, i.fa-solid, i.fas, i.far, i.fab, i.fa-regular {
            border: none !important; outline: none !important; box-shadow: none !important;
            padding: 0 !important; margin: 0 !important; display: inline-block !important; transform: none !important;
        }

        body {
            padding: 32px 24px;
            background-color: var(--bg-canvas);
            font-family: var(--font-main);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ── HEADER BANNER ── */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .top-header h1 {
            font-weight: 800;
            font-size: 1.5rem;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .btn-return {
            background: var(--surface-card);
            border: 1px solid #cbd5e1;
            color: var(--text-secondary);
            border-radius: 8px;
            font-weight: 700;
            font-size: 12.5px;
            padding: 8px 16px;
            transition: all 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-return:hover {
            background: #f8fafc;
            color: var(--text-primary);
            border-color: #94a3b8;
        }

        /* ── SPLIT PANEL CARDS ── */
        .panel-card {
            background: var(--surface-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            height: 100%;
        }

        .panel-title {
            font-size: 13.5px;
            font-weight: 800;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        /* ── FORM STYLING ── */
        .form-label-custom {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 8px;
            display: block;
        }

        .form-input-custom {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
            color: var(--text-primary) !important;
            transition: all 0.15s ease !important;
            width: 100%;
        }
        .form-input-custom:focus {
            border-color: var(--brand-blue) !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12) !important;
            outline: 0 !important;
        }

        .input-group-text-custom {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: none;
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 13px;
            padding: 0 16px;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            display: flex;
            align-items: center;
        }

        /* ── METRIC TILES ── */
        .metric-tile {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
        }
        .metric-tile-label {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            display: block;
        }
        .metric-tile-value {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-primary);
        }

        /* ── SPEC GRID ── */
        .spec-item {
            padding: 10px 0;
            border-bottom: 1px dashed #f1f5f9;
        }
        .spec-item:last-child {
            border-bottom: none;
        }
        .spec-label-flat {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 2px;
        }
        .spec-value-flat {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .badge-tube {
            background: #f1f5f9; color: var(--text-primary);
            padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;
            border: 1px solid #cbd5e1;
        }

        /* ── BUTTONS ── */
        .btn-submit {
            background: #0f172a;
            color: white;
            font-weight: 700;
            font-size: 13px;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            width: 100%;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover:not([disabled]) {
            background: #1e293b;
        }
        .btn-cancel {
            background: #ffffff;
            color: var(--text-secondary);
            border: 1px solid #cbd5e1;
            font-weight: 700;
            font-size: 13px;
            padding: 12px 24px;
            border-radius: 8px;
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        .btn-cancel:hover {
            background: #f8fafc;
            color: var(--text-primary);
            border-color: #94a3b8;
        }
    </style>
</head>

<body>

    <div class="main-container">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="top-header">
            <div>
                <h1>Generate Knit Card</h1>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <span class="meta-pill"><i class="fa-solid fa-hashtag text-secondary"></i> Program ID: <strong>#<?php echo $p_kptid; ?></strong></span>
                    <span class="meta-pill"><i class="fa-solid fa-barcode text-secondary"></i> Sub Transaction ID: <strong><?php echo htmlspecialchars($p_sub_tid); ?></strong></span>
                </div>
            </div>
            <div>
                <a href="knitting_program_list.php" class="btn-return">
                    <i class="fa-solid fa-arrow-left"></i> Return to Programs
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 p-3 border-0 shadow-sm" style="background:#fef2f2; color:#991b1b;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="knit_card_generate.php?program_id=<?php echo $p_kptid; ?>">
            <input type="hidden" name="save_knit_card" value="1">
            <input type="hidden" name="program_id" value="<?php echo $p_kptid; ?>">

            <div class="row g-4">
                <!-- Left Column: Program Details & Specifications (Rule 3) -->
                <div class="col-lg-7 col-md-6">
                    <div class="panel-card">
                        <div class="panel-title">
                            <i class="fa-solid fa-file-invoice text-secondary"></i> Program Specifications
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Buyer Name</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_buyer ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">PO Number / Booking</span>
                                <span class="spec-value-flat text-primary"><?php echo htmlspecialchars($p_booking ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Sales Order (SO No)</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_sono ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Style No</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_style ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Fabric Type</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_fabrics ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Open / Tube</span>
                                <span class="badge-tube d-inline-block mt-1">
                                    <?php echo $p_open_tube === 'T' ? 'Tube (T)' : 'Open (O)'; ?>
                                </span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Finish Dia</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_finish_dia ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Finish GSM</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_finish_gsm ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Yarn Type & Count</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_yarn_type ?: 'N/A'); ?> (<?php echo htmlspecialchars($p_yarn_count ?: 'N/A'); ?>)</span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Lot No</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_lot_no ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Color</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($p_color ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-sm-6 spec-item">
                                <span class="spec-label-flat">Date</span>
                                <span class="spec-value-flat"><?php echo htmlspecialchars($card_date); ?></span>
                            </div>
                            <div class="col-12 spec-item">
                                <span class="spec-label-flat">Knit Material Code</span>
                                <span class="spec-value-flat text-break" style="font-family:monospace; font-size:12px; color:#475569;"><?php echo htmlspecialchars($p_knit_mat_code ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-12 spec-item pb-0">
                                <span class="spec-label-flat">Description</span>
                                <span class="spec-value-flat text-secondary" style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($p_knit_m_desc ?: 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Knit Card Allocation Form -->
                <div class="col-lg-5 col-md-6">
                    <div class="panel-card d-flex flex-column justify-content-between">
                        <div>
                            <div class="panel-title">
                                <i class="fa-solid fa-circle-plus text-secondary"></i> New Knit Card
                            </div>

                            <!-- Allocation Metrics -->
                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <div class="metric-tile">
                                        <span class="metric-tile-label">Program Total</span>
                                        <span class="metric-tile-value"><?php echo number_format($program_qty, 2); ?> <small style="font-size:11px; font-weight:500;">KG</small></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-tile">
                                        <span class="metric-tile-label">Already Carded</span>
                                        <span class="metric-tile-value text-secondary"><?php echo number_format($already_carded, 2); ?> <small style="font-size:11px; font-weight:500;">KG</small></span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="metric-tile" style="<?php echo ($remaining_qty <= 0) ? 'background:#fee2e2; border-color:#fca5a5;' : 'background:#f0fdf4; border-color:#bbf7d0;'; ?>">
                                        <span class="metric-tile-label <?php echo ($remaining_qty <= 0) ? 'text-danger' : 'text-success'; ?>">Remaining Allocation Balance</span>
                                        <span class="metric-tile-value <?php echo ($remaining_qty <= 0) ? 'text-danger' : 'text-success'; ?>"><?php echo number_format($remaining_qty, 2); ?> <small style="font-size:11px; font-weight:500;">KG</small></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Allocation Input Fields -->
                            <div class="mb-3">
                                <label class="form-label-custom">
                                    <i class="fa-solid fa-gear me-1"></i> Machine Number (M/C No) <span class="text-danger">*</span>
                                </label>
                                <select name="MCNO" class="form-input-custom" required>
                                    <option value="">-- Select Knitting Machine --</option>
                                    <?php foreach ($mcno_list as $mc): ?>
                                        <option value="<?php echo htmlspecialchars($mc); ?>" <?php echo ($p_mcno === $mc) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mc); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label-custom">
                                    <i class="fa-solid fa-weight-hanging me-1"></i> Required Quantity (KG) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0.01" max="<?php echo htmlspecialchars($remaining_qty); ?>" name="REQ_QTY" class="form-control form-input-custom" value="<?php echo htmlspecialchars($default_qty); ?>" required placeholder="Enter quantity in KG" style="border-top-right-radius:0 !important; border-bottom-right-radius:0 !important;">
                                    <span class="input-group-text-custom">KG</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit and cancel actions directly inside the Form Card -->
                        <div class="d-flex flex-column gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn-submit" <?php echo ($remaining_qty <= 0) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                                <i class="fa-solid fa-floppy-disk"></i> Save & Generate Knit Card
                            </button>
                            <a href="knitting_program_list.php" class="btn-cancel">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

</body>
</html>


