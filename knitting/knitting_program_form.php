<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = ($edit_id > 0);

// Pre-fetch Machine List from `mcno` table
$mcno_list = [];
$mcno_res = mysqli_query($db, "SELECT MCNOID, MCNO FROM mcno ORDER BY MCNO ASC");
if ($mcno_res) {
    while ($row = mysqli_fetch_assoc($mcno_res)) {
        $mcno_list[] = $row;
    }
}

// Pre-fetch Operator List from `knitting_operator` table
$operator_list = [];
$op_res = mysqli_query($db, "SELECT KOTID, OPERATOR_ID, OPERATOR_NAME FROM knitting_operator ORDER BY OPERATOR_NAME ASC");
if ($op_res) {
    while ($row = mysqli_fetch_assoc($op_res)) {
        $operator_list[] = $row;
    }
}

// Default field values using uppercase database column names
$main_tid = '';
$sub_tid = '';
$booking = '';
$sono = '';
$style = '';
$buyer = '';
$supplier = '';
$knit_m_description = '';
$mcno = '';
$qty = '0.00';
$shift = 'A-SHIFT';
$yarn_type = '';
$yarn_count = '';
$fabrics_type = '';
$finish_gsm = '';
$finish_dia = '';
$open_tube = 'O';
$lot_no = '';
$knit_material_code = '';
$operator_id = '';

// Load existing record for editing
if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM knitting_program WHERE KPTID = ? OR id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $edit_id, $edit_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows == 1) {
            $row = $res->fetch_assoc();
            $main_tid = $row['MAIN_TID'] ?? '';
            $sub_tid = $row['SUB_TID'] ?? '';
            $booking = $row['BOOKING'] ?? $row['booking_no'] ?? '';
            $sono = $row['SONO'] ?? $row['so_no'] ?? '';
            $style = $row['STYLE'] ?? $row['style_no'] ?? '';
            $buyer = $row['BUYER'] ?? $row['buyer'] ?? '';
            $supplier = $row['SUPPLIER'] ?? $row['supplier'] ?? '';
            $knit_m_description = $row['KNIT_M_DESCRIPTION'] ?? '';
            $mcno = $row['MCNO'] ?? $row['mc_no'] ?? '';
            $qty = $row['QTY'] ?? $row['req_qty'] ?? '0.00';
            $shift = $row['SHIFT'] ?? $row['shift'] ?? 'A-SHIFT';
            $yarn_type = $row['YARN_TYPE'] ?? $row['yarn_type'] ?? '';
            $yarn_count = $row['YARN_COUNT'] ?? $row['yarn_count'] ?? '';
            $fabrics_type = $row['FABRICS_TYPE'] ?? $row['fabrics_type'] ?? '';
            $finish_gsm = $row['FINISH_GSM'] ?? $row['finish_gsm'] ?? '';
            $finish_dia = $row['FINISH_DIA'] ?? $row['finish_dia'] ?? '';
            $open_tube = $row['OPEN_TUBE'] ?? $row['open_tube'] ?? 'O';
            $lot_no = $row['LOT_NO'] ?? $row['lot_no'] ?? '';
            $knit_material_code = $row['KNIT_MATERIAL_CODE'] ?? '';
        } else {
            header("Location: knitting_program_list.php?error=Program+not+found");
            exit();
        }
    }
}

// Form Submission Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = trim($_POST['BOOKING'] ?? '');
    $sono = trim($_POST['SONO'] ?? '');
    $style = trim($_POST['STYLE'] ?? '');
    $buyer = trim($_POST['BUYER'] ?? '');
    $supplier = trim($_POST['SUPPLIER'] ?? '');
    $knit_m_description = trim($_POST['KNIT_M_DESCRIPTION'] ?? '');
    $mcno = trim($_POST['MCNO'] ?? '');
    $qty = floatval($_POST['QTY'] ?? 0);
    $shift = trim($_POST['SHIFT'] ?? 'A-SHIFT');
    $yarn_type = trim($_POST['YARN_TYPE'] ?? '');
    $yarn_count = trim($_POST['YARN_COUNT'] ?? '');
    $fabrics_type = trim($_POST['FABRICS_TYPE'] ?? '');
    $finish_gsm = trim($_POST['FINISH_GSM'] ?? '');
    $finish_dia = trim($_POST['FINISH_DIA'] ?? '');
    $open_tube = trim($_POST['OPEN_TUBE'] ?? 'O');
    $lot_no = trim($_POST['LOT_NO'] ?? '');
    $knit_material_code = trim($_POST['KNIT_MATERIAL_CODE'] ?? '');
    $operator_id = trim($_POST['OPERATOR_ID'] ?? '');
    $main_tid = trim($_POST['MAIN_TID'] ?? '');
    $sub_tid = trim($_POST['SUB_TID'] ?? '');

    // Auto-generate MAIN_TID & SUB_TID if empty
    if (empty($main_tid)) {
        $main_tid = time();
    }
    if (empty($sub_tid)) {
        $sub_tid = time() . rand(10, 99);
    }

    // Validation
    if (empty($booking)) {
        $errors[] = "BOOKING number is required.";
    }
    if (empty($mcno)) {
        $errors[] = "Machine No (MCNO) is required.";
    }
    if ($qty <= 0) {
        $errors[] = "QTY must be greater than 0.";
    }

    if (empty($errors)) {
        if ($is_edit) {
            $sql = "UPDATE knitting_program SET 
                MAIN_TID=?, SUB_TID=?, BOOKING=?, SONO=?, STYLE=?, BUYER=?, SUPPLIER=?, 
                KNIT_M_DESCRIPTION=?, MCNO=?, QTY=?, SHIFT=?, YARN_TYPE=?, YARN_COUNT=?, 
                FABRICS_TYPE=?, FINISH_GSM=?, FINISH_DIA=?, OPEN_TUBE=?, LOT_NO=?, KNIT_MATERIAL_CODE=? 
                WHERE KPTID=? OR id=?";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    "sssssssssdsssssssssii",
                    $main_tid, $sub_tid, $booking, $sono, $style, $buyer, $supplier,
                    $knit_m_description, $mcno, $qty, $shift, $yarn_type, $yarn_count,
                    $fabrics_type, $finish_gsm, $finish_dia, $open_tube, $lot_no, $knit_material_code, $edit_id, $edit_id
                );
                if ($stmt->execute()) {
                    header("Location: knitting_program_list.php?msg=Program+updated+successfully");
                    exit();
                } else {
                    $errors[] = "Database update error: " . $db->error;
                }
            } else {
                $errors[] = "Failed to prepare database update query.";
            }
        } else {
            $sql = "INSERT INTO knitting_program (
                MAIN_TID, SUB_TID, BOOKING, SONO, STYLE, BUYER, SUPPLIER, 
                KNIT_M_DESCRIPTION, MCNO, QTY, SHIFT, YARN_TYPE, YARN_COUNT, 
                FABRICS_TYPE, FINISH_GSM, FINISH_DIA, OPEN_TUBE, LOT_NO, KNIT_MATERIAL_CODE
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    "sssssssssdsssssssss",
                    $main_tid, $sub_tid, $booking, $sono, $style, $buyer, $supplier,
                    $knit_m_description, $mcno, $qty, $shift, $yarn_type, $yarn_count,
                    $fabrics_type, $finish_gsm, $finish_dia, $open_tube, $lot_no, $knit_material_code
                );
                if ($stmt->execute()) {
                    header("Location: knitting_program_list.php?msg=New+program+added+successfully");
                    exit();
                } else {
                    $errors[] = "Database insertion error: " . $db->error;
                }
            } else {
                $errors[] = "Failed to prepare database insertion query.";
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
    <title><?php echo $is_edit ? 'Edit Program #' . $edit_id : 'New Knitting Program'; ?> | Purbani Fabrics</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">

    <style>
        :root {
            --primary-teal: #00796b;
            --dark-teal: #004d40;
            --light-teal: #e0f2f1;
            --accent-blue: #2563eb;
            --surface-bg: #f8fafc;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            --focus-ring: 0 0 0 3px rgba(0, 121, 107, 0.15);
        }

        i, i.fa-solid, i.fas, i.far, i.fab, i.fa-regular {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            display: inline-block !important;
            transform: none !important;
        }

        body {
            padding: 24px;
            background-color: var(--surface-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #334155;
        }

        /* Top Header Banner */
        .top-banner {
            background: linear-gradient(135deg, #004d40 0%, #00796b 50%, #00897b 100%);
            color: white;
            padding: 26px 34px;
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(0, 121, 107, 0.22);
            margin-bottom: 28px;
        }

        .top-banner h1 {
            font-weight: 700;
            font-size: 1.85rem;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .badge-mode {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Section Cards */
        .form-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            transition: border-color 0.2s ease;
        }

        .form-card:hover {
            border-color: #cbd5e1;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 24px;
        }

        .section-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
        }

        .icon-teal { background: #e0f2f1; color: var(--primary-teal); }
        .icon-blue { background: #dbeafe; color: #1d4ed8; }
        .icon-purple { background: #f3e8ff; color: #7e22ce; }
        .icon-amber { background: #fef3c7; color: #b45309; }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.2px;
        }

        .section-subtitle {
            font-size: 12.5px;
            color: #64748b;
            margin: 0;
        }

        /* Inputs & Labels */
        .form-card .form-label {
            display: block !important;
            width: 100% !important;
            margin-bottom: 7px !important;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .form-card .form-control, 
        .form-card .form-select {
            display: block !important;
            width: 100% !important;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 14px;
            color: #0f172a;
            background-color: #ffffff;
            transition: all 0.15s ease-in-out;
        }

        .form-card .form-control:focus, 
        .form-card .form-select:focus {
            border-color: var(--primary-teal);
            box-shadow: var(--focus-ring);
            outline: none;
        }

        .form-card .form-control[readonly] {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
            font-weight: 600;
        }

        .required-tag {
            color: #ef4444;
            font-weight: 700;
            margin-left: 2px;
        }

        /* Custom Input Groups */
        .btn-lookup {
            background: linear-gradient(135deg, #00796b 0%, #004d40 100%);
            color: white;
            border: none;
            border-top-right-radius: 10px !important;
            border-bottom-right-radius: 10px !important;
            padding: 0 20px;
            font-weight: 600;
            font-size: 13.5px;
            transition: opacity 0.2s ease;
        }

        .btn-lookup:hover {
            opacity: 0.92;
            color: white;
        }

        .btn-teal {
            background: linear-gradient(135deg, #00796b 0%, #004d40 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 11px 28px;
            box-shadow: 0 4px 14px rgba(0, 121, 107, 0.25);
            transition: all 0.2s ease;
        }

        .btn-teal:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 121, 107, 0.35);
            color: white;
        }

        .btn-cancel {
            border-radius: 10px;
            padding: 11px 24px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            color: #475569;
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .btn-cancel:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .actions-panel {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px 32px;
            border: 1px solid #e2e8f0;
            box-shadow: var(--card-shadow);
        }
    </style>
</head>

<body>

    <div class="container-fluid" style="max-width: 1350px;">

        <!-- Header Banner -->
        <div class="top-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-3 mb-1">
                    <h1 class="d-flex align-items-center gap-2">
                        <i class="fa-solid <?php echo $is_edit ? 'fa-pen-to-square' : 'fa-plus-circle'; ?>"></i>
                        <?php echo $is_edit ? 'Edit Program Entry #' . $edit_id : 'Program Entry'; ?>
                    </h1>
                </div>
                <p class="mb-0 text-white-50 small">Fill parameters, lookup booking information, and allocate machine production details</p>
            </div>
            <div>
                <a href="knitting_program_list.php" class="btn btn-light px-3 py-2 fw-semibold text-dark" style="border-radius:10px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Program List
                </a>
            </div>
        </div>

        <!-- Validation Alerts -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 p-3 shadow-sm border-danger">
                <h6 class="alert-heading fw-bold mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Validation Errors Detected:</h6>
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="programForm" action="knitting_program_form.php<?php echo $is_edit ? '?id=' . $edit_id : ''; ?>">

            <!-- SECTION 1: Booking Lookup & Machine Selection -->
            <div class="form-card">
                <div class="section-header">
                    <div class="section-icon icon-teal">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                    <div>
                        <h2 class="section-title">1. Booking Lookup & Machine Allocation</h2>
                        <p class="section-subtitle">Fetch details directly from SAP Booking database and select assigned machine</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">BOOKING No <span class="required-tag">*</span></label>
                        <div class="input-group">
                            <input type="text" name="BOOKING" id="bookingInput" class="form-control" placeholder="Enter BOOKING (e.g. 230043287)" value="<?php echo htmlspecialchars($booking); ?>" required>
                            <button type="button" class="btn btn-lookup" id="fetchBookingBtn">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Lookup
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Machine No (MCNO) <span class="required-tag">*</span></label>
                        <select name="MCNO" id="mcnoSelect" class="form-select" required>
                            <option value="">-- Select Machine (from `mcno` table) --</option>
                            <?php foreach ($mcno_list as $m): ?>
                                <option value="<?php echo htmlspecialchars($m['MCNO']); ?>" <?php echo ($mcno == $m['MCNO']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['MCNO']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Shift Selection</label>
                        <select name="SHIFT" class="form-select">
                            <option value="A-SHIFT" <?php echo ($shift == 'A-SHIFT') ? 'selected' : ''; ?>>A-SHIFT</option>
                            <option value="B-SHIFT" <?php echo ($shift == 'B-SHIFT') ? 'selected' : ''; ?>>B-SHIFT</option>
                            <option value="C-SHIFT" <?php echo ($shift == 'C-SHIFT') ? 'selected' : ''; ?>>C-SHIFT</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Order & Description Details -->
            <div class="form-card">
                <div class="section-header">
                    <div class="section-icon icon-blue">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div>
                        <h2 class="section-title">2. Order Information & Description</h2>
                        <p class="section-subtitle">Sales order item details, buyer, supplier and fabric description</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="form-label">SONO</label>
                        <input type="text" name="SONO" id="sonoInput" class="form-control" placeholder="SONO..." value="<?php echo htmlspecialchars($sono); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">STYLE</label>
                        <input type="text" name="STYLE" id="styleInput" class="form-control" placeholder="STYLE..." value="<?php echo htmlspecialchars($style); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">BUYER</label>
                        <input type="text" name="BUYER" id="buyerInput" class="form-control" placeholder="BUYER..." value="<?php echo htmlspecialchars($buyer); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SUPPLIER</label>
                        <input type="text" name="SUPPLIER" id="supplierInput" class="form-control" placeholder="SUPPLIER..." value="<?php echo htmlspecialchars($supplier); ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">KNIT_M_DESCRIPTION</label>
                        <select name="KNIT_M_DESCRIPTION" id="descSelect" class="form-select">
                            <option value="<?php echo htmlspecialchars($knit_m_description); ?>">
                                <?php echo htmlspecialchars($knit_m_description ?: '-- Select Fabric Description --'); ?>
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Technical Specifications -->
            <div class="form-card">
                <div class="section-header">
                    <div class="section-icon icon-purple">
                        <i class="fa-solid fa-scroll"></i>
                    </div>
                    <div>
                        <h2 class="section-title">3. Technical Specifications</h2>
                        <p class="section-subtitle">Yarn count, dia, GSM, tube type and material codes</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="form-label">YARN_TYPE</label>
                        <input type="text" name="YARN_TYPE" id="yarnTypeInput" class="form-control" value="<?php echo htmlspecialchars($yarn_type); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">YARN_COUNT</label>
                        <input type="text" name="YARN_COUNT" id="yarnCountInput" class="form-control" value="<?php echo htmlspecialchars($yarn_count); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">FABRICS_TYPE</label>
                        <input type="text" name="FABRICS_TYPE" id="fabricsTypeInput" class="form-control" value="<?php echo htmlspecialchars($fabrics_type); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">FINISH_GSM</label>
                        <input type="text" name="FINISH_GSM" id="finishGsmInput" class="form-control" value="<?php echo htmlspecialchars($finish_gsm); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">FINISH_DIA</label>
                        <input type="text" name="FINISH_DIA" id="finishDiaInput" class="form-control" value="<?php echo htmlspecialchars($finish_dia); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">OPEN_TUBE</label>
                        <select name="OPEN_TUBE" id="openTubeSelect" class="form-select">
                            <option value="O" <?php echo ($open_tube == 'O') ? 'selected' : ''; ?>>Open (O)</option>
                            <option value="T" <?php echo ($open_tube == 'T') ? 'selected' : ''; ?>>Tube (T)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">LOT_NO</label>
                        <input type="text" name="LOT_NO" id="lotNoInput" class="form-control" value="<?php echo htmlspecialchars($lot_no); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">KNIT_MATERIAL_CODE</label>
                        <input type="text" name="KNIT_MATERIAL_CODE" id="knitMaterialCodeInput" class="form-control" value="<?php echo htmlspecialchars($knit_material_code); ?>">
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Production Operator & Program Quantity -->
            <div class="form-card">
                <div class="section-header">
                    <div class="section-icon icon-amber">
                        <i class="fa-solid fa-weight-hanging"></i>
                    </div>
                    <div>
                        <h2 class="section-title">4. Operator Assignment & Program Quantity</h2>
                        <p class="section-subtitle">Specify operator from DB register and allocated program volume</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">Operator (from `knitting_operator` table)</label>
                        <select name="OPERATOR_ID" class="form-select">
                            <option value="">-- Select Operator --</option>
                            <?php foreach ($operator_list as $op): ?>
                                <option value="<?php echo htmlspecialchars($op['OPERATOR_ID']); ?>" <?php echo ($operator_id == $op['OPERATOR_ID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($op['OPERATOR_NAME'] . ' (' . $op['OPERATOR_ID'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Program QTY (KG) <span class="required-tag">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="QTY" class="form-control fw-bold text-success" placeholder="0.00" value="<?php echo htmlspecialchars($qty); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">MAIN_TID / SUB_TID <small class="text-muted">(Auto-Generated)</small></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars(($main_tid ? $main_tid . ' / ' . $sub_tid : 'Auto-generated on save')); ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Panel -->
            <div class="actions-panel d-flex justify-content-end align-items-center gap-3">
                <a href="knitting_program_list.php" class="btn btn-cancel">
                    <i class="fa-solid fa-xmark me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-teal">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Program Entry
                </button>
            </div>
        </form>
    </div>

    <script src="jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#fetchBookingBtn').click(function() {
                var $btn = $(this);
                var booking = $('#bookingInput').val().trim();
                if (!booking) {
                    alert('Please enter a BOOKING number first.');
                    return;
                }
                
                var originalText = $btn.html();
                $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Searching...').prop('disabled', true);

                $.ajax({
                    url: 'ajaxKnittingProgram.php',
                    data: { booking: booking },
                    dataType: 'json',
                    success: function(resp) {
                        $btn.html(originalText).prop('disabled', false);
                        if (resp && resp.success && resp.data) {
                            var d = resp.data;
                            $('#sonoInput').val(d.SONO || '');
                            $('#styleInput').val(d.STYLE || '');
                            $('#buyerInput').val(d.BUYER || '');
                            $('#supplierInput').val(d.SUPPLIER || '');
                            $('#yarnTypeInput').val(d.YARN_TYPE || '');
                            $('#yarnCountInput').val(d.YARN_COUNT || '');
                            $('#fabricsTypeInput').val(d.FABRICS_TYPE || '');
                            $('#finishGsmInput').val(d.FINISH_GSM || '');
                            $('#finishDiaInput').val(d.FINISH_DIA || '');
                            $('#openTubeSelect').val(d.OPEN_TUBE || 'O');
                            $('#lotNoInput').val(d.LOT_NO || '');
                            $('#knitMaterialCodeInput').val(d.KNIT_MATERIAL_CODE || '');

                            var descSelect = $('#descSelect');
                            descSelect.empty();
                            if (resp.descriptions && resp.descriptions.length > 0) {
                                resp.descriptions.forEach(function(desc) {
                                    descSelect.append(new Option(desc, desc));
                                });
                            } else if (d.KNIT_M_DESCRIPTION) {
                                descSelect.append(new Option(d.KNIT_M_DESCRIPTION, d.KNIT_M_DESCRIPTION));
                            }
                        } else {
                            alert(resp.error || 'No data found for this BOOKING.');
                        }
                    },
                    error: function() {
                        $btn.html(originalText).prop('disabled', false);
                        alert('Error communicating with the server.');
                    }
                });
            });
        });
    </script>
</body>

</html>
