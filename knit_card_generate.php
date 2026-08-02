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

// 3. Obtain corresponding SUB_TID and BOOKING from program to load information from knitting_input (Rule 1 & Rule 2)
$sub_tid = $prog['SUB_TID'] ?? '';
$booking = $prog['BOOKING'] ?? '';
$input = null;

if (!empty($sub_tid) || !empty($booking)) {
    $stmt_in = $db->prepare("SELECT * FROM knitting_input WHERE KITID = ? OR BOOKING = ? OR BOOKING = ? LIMIT 1");
    if ($stmt_in) {
        $stmt_in->bind_param("sss", $sub_tid, $sub_tid, $booking);
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
$p_mcno             = $prog['MCNO'] ?? '';
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
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_knit_card'])) {
    $user_req_qty = floatval($_POST['REQ_QTY'] ?? 0);

    if ($user_req_qty <= 0) {
        $error = "Required Quantity must be a positive number.";
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

                // Rule 7: Update knitting_program.CARD_GENERATED = 1
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
        }

        .top-banner {
            position: relative;
            background: linear-gradient(135deg, var(--header-from) 0%, var(--header-mid) 50%, var(--header-to) 100%);
            color: white;
            padding: 32px 36px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            margin-bottom: 28px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .top-banner h1 {
            font-weight: 800;
            font-size: 1.85rem;
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 60%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-btn {
            border-radius: 12px;
            font-weight: 700;
            font-size: 13.5px;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }
        .nav-btn:hover { transform: translateY(-2px); }

        .btn-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            backdrop-filter: blur(10px);
        }
        .btn-glass:hover { background: rgba(255, 255, 255, 0.15); color: #ffffff; }

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
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-control[readonly], .form-control:disabled {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
            color: #64748b !important;
            font-weight: 600;
            cursor: not-allowed;
        }

        .editable-field {
            background-color: #ffffff !important;
            border: 2px solid #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1) !important;
            font-weight: 800 !important;
            font-size: 1.1rem !important;
            color: #1e3a8a !important;
        }

        .editable-badge {
            background: #dbeafe;
            color: #1e40af;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .readonly-badge {
            background: #f1f5f9;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    <div class="container-fluid" style="max-width: 1350px;">

        <!-- HEADER BANNER -->
        <div class="top-banner">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:54px; height:54px; border-radius:16px; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; font-size:24px;">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>
                    <div>
                        <h1>Generate Knit Card</h1>
                        <p class="mb-0 text-white-50 small">Program ID: <strong class="text-white">#<?php echo $p_kptid; ?></strong> &nbsp;|&nbsp; Sub Transaction ID: <strong class="text-white"><?php echo htmlspecialchars($p_sub_tid); ?></strong></p>
                    </div>
                </div>
                <div>
                    <a href="knitting_program_list.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-arrow-left"></i> Cancel & Return
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 p-3">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="knit_card_generate.php?program_id=<?php echo $p_kptid; ?>">
            <input type="hidden" name="save_knit_card" value="1">
            <input type="hidden" name="program_id" value="<?php echo $p_kptid; ?>">

            <!-- MAIN FORM PANEL -->
            <div class="content-panel">

                <!-- EDITABLE SECTION: QUANTITY ONLY -->
                <div class="p-3 mb-4 rounded-4" style="background:#eff6ff; border:1px solid #bfdbfe;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label mb-0 fs-6 text-primary fw-bold">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Required Quantity (KG)
                        </label>
                        <span class="editable-badge"><i class="fa-solid fa-unlock me-1"></i> Editable Field</span>
                    </div>
                    <p class="small text-muted mb-2">Enter the target production quantity for this Knit Card. Only this quantity field is editable.</p>
                    <div class="input-group input-group-lg">
                        <input type="number" step="0.01" min="0.01" name="REQ_QTY" class="form-control editable-field" value="<?php echo htmlspecialchars($default_qty); ?>" required>
                        <span class="input-group-text bg-primary text-white fw-bold">KG</span>
                    </div>
                </div>

                <!-- READ ONLY SECTION: AUTO GENERATED INFORMATION -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-section-title mb-0 border-0 p-0">
                        <i class="fa-solid fa-lock me-1"></i> Auto-Generated Program Specifications
                    </div>
                    <span class="readonly-badge"><i class="fa-solid fa-lock me-1"></i> Read-Only (Non-Editable)</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Buyer Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_buyer); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Booking No</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_booking); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sales Order (SO No)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_sono); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Style No</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_style); ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Machine (M/C No)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_mcno); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Finish Dia</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_finish_dia); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Finish GSM</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_finish_gsm); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Open / Tube</label>
                        <input type="text" class="form-control" value="<?php echo $p_open_tube === 'T' ? 'Tube (T)' : 'Open (O)'; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Fabric Type</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_fabrics); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Yarn Type</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_yarn_type); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Yarn Count</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_yarn_count); ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Lot No</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_lot_no); ?>" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Knit Material Code</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_knit_mat_code); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Knit Description</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p_knit_m_desc); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Card Generation Date</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($card_date); ?>" readonly>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="knitting_program_list.php" class="btn btn-outline-secondary px-4 py-2 fw-semibold" style="border-radius:12px;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold" style="border-radius:12px; background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save & Generate Knit Card
                    </button>
                </div>

            </div>
        </form>

    </div>

</body>
</html>
