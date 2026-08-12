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

// 1. Check if card already generated for this program (Rule 8)
$chk = $db->prepare("SELECT KCID FROM knit_card WHERE KPTID = ? LIMIT 1");
if ($chk) {
    $chk->bind_param("i", $program_id);
    $chk->execute();
    $res_chk = $chk->get_result();
    if ($res_chk && $res_chk->num_rows > 0) {
        $existing_card = $res_chk->fetch_assoc();
        $chk->close();
        header("Location: knit_card_view.php?id=" . $existing_card['KCID'] . "&msg=" . urlencode("Knit Card already exists for this program!"));
        exit();
    }
    $chk->close();
}

// 2. Read selected Knitting Program using KPTID (Rule 2)
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

// 3. Obtain corresponding SUB_TID and BOOKING from program to load information from knitting_input
$sub_tid = $prog['SUB_TID'] ?? '';
$booking = $prog['BOOKING'] ?? '';
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
$p_booking          = !empty($prog['BOOKING']) ? $prog['BOOKING'] : ($input['BOOKING'] ?? '');
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

$default_qty        = floatval($prog['QTY'] ?? 0);
$prepared_by        = $_SESSION['username'] ?? '';
$authorised_by      = '';
$error              = '';

// 4. Save Logic (Rule 6 & Rule 7)
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
            $error = "Failed to prepare query: " . $db->error;
        } else {
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

            if ($ins->execute()) {
                $new_kcid = $ins->insert_id;
                $ins->close();

                // Mark the knitting_program as card generated
                $upd = $db->prepare("UPDATE knitting_program SET CARD_GENERATED = 1 WHERE KPTID = ?");
                if ($upd) {
                    $upd->bind_param("i", $p_kptid);
                    $upd->execute();
                    $upd->close();
                }

                header("Location: knit_card_view.php?id=" . $new_kcid . "&msg=" . urlencode("Knit Card generated successfully!"));
                exit();
            } else {
                $error = "Failed to generate Knit Card: " . ($ins->error ?: $db->error);
            }
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
            --bg-canvas: #f8fafc;
            --surface-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --brand-blue: #2563eb;
            --brand-blue-hover: #1d4ed8;
            --brand-blue-light: #eff6ff;
            --border-color: #e2e8f0;
            --card-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.04), 0 8px 10px -6px rgba(15, 23, 42, 0.03);
            --card-shadow-hover: 0 20px 30px -10px rgba(15, 23, 42, 0.08);
            --header-gradient: linear-gradient(135deg, #090d22 0%, #0f172a 50%, #1e3a8a 100%);
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        i, i.fa-solid, i.fas, i.far, i.fab, i.fa-regular {
            border: none !important; outline: none !important; box-shadow: none !important;
            padding: 0 !important; margin: 0 !important; display: inline-block !important; transform: none !important;
        }

        body {
            padding: 24px;
            background-color: var(--bg-canvas);
            font-family: var(--font-main);
            color: var(--text-primary);
            background-image: radial-gradient(circle at 10% 10%, rgba(37, 99, 235, 0.015) 0%, transparent 50%),
                              radial-gradient(circle at 90% 90%, rgba(30, 58, 138, 0.015) 0%, transparent 50%);
        }

        .main-container {
            max-width: 1320px;
            margin: 0 auto;
        }

        /* ── HEADER BANNER ── */
        .top-banner {
            position: relative;
            background: var(--header-gradient);
            color: white;
            padding: 36px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
            margin-bottom: 28px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .top-banner::before {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
            top: -100px; right: -50px;
            border-radius: 50%;
            pointer-events: none;
        }

        .banner-icon-badge {
            width: 58px; height: 58px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; color: #60a5fa;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .top-banner h1 {
            font-weight: 800;
            font-size: 1.9rem;
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 60%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            color: #cbd5e1;
            font-weight: 600;
        }
        .meta-pill strong { color: #ffffff; }

        .btn-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #f8fafc;
            backdrop-filter: blur(10px);
            border-radius: 14px;
            font-weight: 700;
            font-size: 13.5px;
            padding: 10px 20px;
            transition: all 0.2s ease;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none;
        }
        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* ── EDITABLE ACTION CONTAINER ── */
        .action-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #bfdbfe;
            border-radius: 24px;
            padding: 28px 32px;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.06);
            margin-bottom: 28px;
            position: relative;
        }

        .action-header-badge {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .input-group-custom {
            position: relative;
        }

        .form-control-editable, .form-select-editable {
            background-color: #ffffff !important;
            border: 2px solid #cbd5e1 !important;
            border-radius: 14px !important;
            padding: 12px 18px !important;
            font-weight: 700 !important;
            font-size: 1.05rem !important;
            color: #0f172a !important;
            transition: all 0.25s ease !important;
        }

        .form-control-editable:focus, .form-select-editable:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12) !important;
        }

        .input-group-addon {
            background: #2563eb;
            color: white;
            font-weight: 800;
            border: none;
            padding: 0 20px;
            border-top-right-radius: 14px !important;
            border-bottom-right-radius: 14px !important;
            display: flex; align-items: center; justify-content: center;
        }

        /* ── SPECIFICATION CARDS ── */
        .specs-panel {
            background: var(--surface-card);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 28px;
        }

        .section-sub-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 20px;
        }
        .section-sub-title i { color: #2563eb; }

        .spec-grid-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px 20px;
            height: 100%;
            transition: all 0.2s ease;
        }
        .spec-grid-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            transform: translateY(-2px);
        }

        .spec-label {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: flex; align-items: center; gap: 6px;
        }

        .spec-value {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text-primary);
            word-break: break-word;
        }

        /* Custom display badges */
        .badge-tube {
            background: #e0f2fe; color: #0369a1;
            padding: 3px 10px; border-radius: 8px; font-weight: 700; font-size: 12.5px;
        }

        /* ── FOOTER ACTIONS ── */
        .footer-action-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 14px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .btn-submit-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            font-weight: 800;
            font-size: 14px;
            padding: 13px 32px;
            border-radius: 14px;
            border: none;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex; align-items: center; gap: 10px;
        }
        .btn-submit-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(37, 99, 235, 0.45);
        }

        .btn-cancel-secondary {
            background: #ffffff;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            font-weight: 700;
            font-size: 14px;
            padding: 13px 26px;
            border-radius: 14px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-cancel-secondary:hover {
            background: #f1f5f9;
            color: var(--text-primary);
            border-color: #cbd5e1;
        }
    </style>
</head>

<body>

    <div class="main-container">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="top-banner">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="banner-icon-badge">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <h1>Generate Knit Card</h1>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="meta-pill"><i class="fa-solid fa-hashtag text-info"></i> Program ID: <strong>#<?php echo $p_kptid; ?></strong></span>
                            <span class="meta-pill"><i class="fa-solid fa-barcode text-info"></i> Sub Transaction ID: <strong><?php echo htmlspecialchars($p_sub_tid); ?></strong></span>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="knitting_program_list.php" class="btn-glass">
                        <i class="fa-solid fa-arrow-left"></i> Return to Programs
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4 p-3 border-0 shadow-sm" style="background:#fef2f2; color:#991b1b;">
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

            <!-- ═══ TARGET PRODUCTION SETTINGS (EDITABLE) ═══ -->
            <div class="action-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-sliders text-primary fs-5"></i>
                        <h6 class="mb-0 fw-bold text-dark fs-6">Target Production Settings</h6>
                    </div>
                    <span class="action-header-badge"><i class="fa-solid fa-pen me-1"></i> Editable Fields</span>
                </div>
                <p class="small text-muted mb-4">Select or edit the knitting machine assignment and target production quantity for this Knit Card.</p>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark mb-2">
                            <i class="fa-solid fa-gear me-1 text-primary"></i> Machine Number (M/C No) <span class="text-danger">*</span>
                        </label>
                        <select name="MCNO" class="form-select form-select-editable" required>
                            <option value="">-- Select Knitting Machine --</option>
                            <?php foreach ($mcno_list as $mc): ?>
                                <option value="<?php echo htmlspecialchars($mc); ?>" <?php echo ($p_mcno === $mc) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark mb-2">
                            <i class="fa-solid fa-weight-hanging me-1 text-primary"></i> Required Quantity (KG) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-custom">
                            <input type="number" step="0.01" min="0.01" name="REQ_QTY" class="form-control form-control-editable" value="<?php echo htmlspecialchars($default_qty); ?>" required placeholder="Enter quantity in KG">
                            <span class="input-group-text input-group-addon">KG</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ SPECIFICATIONS READONLY PANEL ═══ -->
            <div class="specs-panel">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <div class="section-sub-title mb-0 border-0 p-0">
                        <i class="fa-solid fa-microchip"></i> Program Specifications & Yarn Parameters
                    </div>
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-3 font-semibold" style="font-size:11px; letter-spacing:0.5px; text-transform:uppercase;">
                        <i class="fa-solid fa-lock me-1 text-muted"></i> Auto-Populated from Program
                    </span>
                </div>

                <!-- Category 1: Order & Sales Specs -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-building"></i> Buyer Name</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_buyer ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-file-invoice"></i> Booking No</div>
                            <div class="spec-value text-primary"><?php echo htmlspecialchars($p_booking ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-cart-shopping"></i> Sales Order (SO No)</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_sono ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-shirt"></i> Style No</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_style ?: 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Category 2: Fabric Specs -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-layer-group"></i> Fabric Type</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_fabrics ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-ruler-horizontal"></i> Finish Dia</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_finish_dia ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-gauge-high"></i> Finish GSM</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_finish_gsm ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-circle-nodes"></i> Open / Tube</div>
                            <div class="spec-value">
                                <span class="badge-tube">
                                    <?php echo $p_open_tube === 'T' ? 'Tube (T)' : 'Open (O)'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category 3: Yarn & Material Details -->
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-boxes-stacked"></i> Yarn Type</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_yarn_type ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-calculator"></i> Yarn Count</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_yarn_count ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-cubes"></i> Lot No</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_lot_no ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-palette"></i> Color</div>
                            <div class="spec-value"><?php echo htmlspecialchars($p_color ?: 'N/A'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-barcode"></i> Knit Material Code</div>
                            <div class="spec-value text-break" style="font-size:13px; font-family:monospace; color:#334155;"><?php echo htmlspecialchars($p_knit_mat_code ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-align-left"></i> Knit Description</div>
                            <div class="spec-value" style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($p_knit_m_desc ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="spec-grid-card">
                            <div class="spec-label"><i class="fa-solid fa-calendar-check"></i> Generation Date</div>
                            <div class="spec-value"><i class="fa-regular fa-calendar me-1 text-muted"></i> <?php echo htmlspecialchars($card_date); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="footer-action-bar mt-4">
                    <a href="knitting_program_list.php" class="btn-cancel-secondary">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                    <button type="submit" class="btn-submit-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save & Generate Knit Card
                    </button>
                </div>

            </div>
        </form>

    </div>

</body>
</html>

