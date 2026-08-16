<?php
// knitting_inspection.php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$error = '';
$msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inspection'])) {
    $knit_card_id       = intval($_POST['KNIT_CARD_ID'] ?? 0);
    $roll_no            = trim($_POST['ROLL_NO'] ?? '');
    $roll_weight        = floatval($_POST['ROLL_WEIGHT'] ?? 0);
    $defect_hole        = intval($_POST['DEFECT_HOLE'] ?? 0);
    $defect_drop_stitch = intval($_POST['DEFECT_DROP_STITCH'] ?? 0);
    $defect_oil_mark    = intval($_POST['DEFECT_OIL_MARK'] ?? 0);
    $defect_lycra_out   = intval($_POST['DEFECT_LYCRA_OUT'] ?? 0);
    $qc_grade           = trim($_POST['QC_GRADE'] ?? '');
    $qc_status          = trim($_POST['QC_STATUS'] ?? '');
    $inspected_by       = trim($_POST['INSPECTED_BY'] ?? '');
    $remarks            = trim($_POST['REMARKS'] ?? '');

    // Validate inputs
    if ($knit_card_id <= 0) {
        $error = "Please select a valid Knit Card.";
    } elseif (empty($roll_no)) {
        $error = "Roll Number is required.";
    } elseif ($roll_weight <= 0) {
        $error = "Roll Weight must be greater than 0.";
    } elseif (empty($qc_grade)) {
        $error = "QC Grade is required.";
    } elseif (empty($qc_status)) {
        $error = "QC Status is required.";
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO knitting_inspection (
                    KNIT_CARD_ID, ROLL_NO, ROLL_WEIGHT, 
                    DEFECT_HOLE, DEFECT_DROP_STITCH, DEFECT_OIL_MARK, DEFECT_LYCRA_OUT, 
                    QC_GRADE, QC_STATUS, INSPECTED_BY, REMARKS
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $db->error);
            }
            $stmt->bind_param(
                "isdiiiissss",
                $knit_card_id, $roll_no, $roll_weight,
                $defect_hole, $defect_drop_stitch, $defect_oil_mark, $defect_lycra_out,
                $qc_grade, $qc_status, $inspected_by, $remarks
            );
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            $msg = "Inspection result for Roll #$roll_no saved successfully!";
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch active Knit Cards for select dropdown
$cards = [];
$c_res = $db->query("
    SELECT KCID, CARD_DATE, MCNO, BUYER, STYLE, REQ_QTY 
    FROM knit_card 
    ORDER BY KCID DESC
");
if ($c_res) {
    while ($row = $c_res->fetch_assoc()) {
        $cards[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting QC & Inspection | Purbani Fabrics</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        :root {
            --color-bg: #f8fafc;
            --color-card: #ffffff;
            --color-primary: #1e293b;
            --color-secondary: #64748b;
            --color-muted: #cbd5e1;
            --color-blue: #2563eb;
            --color-blue-light: #eff6ff;
            --color-blue-border: #bfdbfe;
            --color-success: #16a34a;
            --color-success-light: #f0fdf4;
            --color-success-border: #bbf7d0;
            --color-danger: #dc2626;
            --color-danger-light: #fef2f2;
            --color-danger-border: #fee2e2;
            --border-color: #cbd5e1;
            --radius-card: 16px;
            --radius-box: 12px;
            --radius-input: 10px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        body {
            background-color: var(--color-bg);
            font-family: 'Inter', sans-serif;
            color: var(--color-primary);
            padding: 30px 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ── PAGE HEADER (TOP BAR) ── */
        .top-bar {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            border-radius: var(--radius-card);
            padding: 20px 30px;
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
            padding: 10px;
            border-radius: 12px;
            line-height: 1;
        }
        
        .top-bar-title-wrap h1 {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .top-bar-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            margin: 2px 0 0 0;
        }

        .btn-back {
            background: #ffffff !important;
            border: none !important;
            color: #0f172a !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            padding: 10px 18px !important;
            border-radius: 30px !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none !important;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        .btn-back:hover {
            transform: translateX(-2px);
            background: #f8fafc !important;
        }

        /* ── WORKSPACE GRID ── */
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

        /* ── CARD STYLING ── */
        .workspace-card {
            background: var(--color-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-md);
            padding: 24px;
            display: flex;
            flex-direction: column;
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
            font-size: 15px;
            font-weight: 800;
            color: var(--color-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-pill-header {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 30px;
        }
        .badge-status-blue {
            background: var(--color-blue-light);
            color: var(--color-blue);
            border: 1px solid var(--color-blue-border);
        }
        .badge-status-purple {
            background: #faf5ff;
            color: #7c3aed;
            border: 1px solid #e9d5ff;
        }

        .form-label-custom {
            font-size: 10px;
            font-weight: 700;
            color: var(--color-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }
        .required-label::after {
            content: " *";
            color: var(--color-danger);
        }

        .form-select-custom, .form-input-custom, .form-textarea-custom {
            background-color: #ffffff !important;
            border: 1.5px solid var(--border-color) !important;
            border-radius: var(--radius-input) !important;
            padding: 12px 16px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            color: var(--color-primary) !important;
            transition: all 0.2s ease !important;
            width: 100%;
        }
        .form-select-custom:focus, .form-input-custom:focus, .form-textarea-custom:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            outline: 0 !important;
        }

        /* ── INPUT SUFFIX & DOCKING ── */
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
            user-select: none;
        }

        /* ── DEFECTS GRID ── */
        .defects-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
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
            color: var(--color-secondary) !important;
            font-weight: 700 !important;
            font-size: 13.5px !important;
            padding: 12px 24px !important;
            border-radius: 10px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }
        .btn-cancel:hover {
            background: #f8fafc !important;
            color: var(--color-primary) !important;
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
        .btn-submit:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 14px rgba(30, 58, 95, 0.35) !important;
            filter: brightness(1.1);
        }
    </style>
</head>
<body>

    <div class="main-container">

        <!-- ── PAGE HEADER (TOP BAR) ── -->
        <div class="top-bar">
            <div class="top-bar-left">
                <div class="top-bar-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                <div class="top-bar-title-wrap">
                    <h1>Knitting QC & Inspection</h1>
                    <p class="top-bar-subtitle">Enter quality control metrics and grading for completed rolls</p>
                </div>
            </div>
            <div>
                <a href="knitting_program_list.php" class="btn btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- ── STATUS MESSAGES ── -->
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

        <!-- ── QC INSPECTION FORM ── -->
        <form method="POST" action="knitting_inspection.php">
            <input type="hidden" name="save_inspection" value="1">

            <div class="workspace-grid">
                
                <!-- Left Column: Roll & Card Details -->
                <div class="workspace-card">
                    <div class="card-header-custom">
                        <h4 class="card-header-title">
                            <span>📋</span> Roll & Card Details
                        </h4>
                        <span class="badge-pill-header badge-status-blue">Required Info</span>
                    </div>

                    <!-- Knit Card Dropdown -->
                    <div class="mb-4">
                        <label class="form-label-custom required-label">Select Knit Card</label>
                        <select name="KNIT_CARD_ID" class="form-select-custom" required>
                            <option value="">-- Select Knitting Card --</option>
                            <?php foreach ($cards as $c): ?>
                                <option value="<?php echo $c['KCID']; ?>">
                                    Card #<?php echo $c['KCID']; ?> (Buyer: <?php echo htmlspecialchars($c['BUYER'] ?: 'N/A'); ?> | Style: <?php echo htmlspecialchars($c['STYLE'] ?: 'N/A'); ?> | M/C: <?php echo htmlspecialchars($c['MCNO'] ?: 'N/A'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Roll Number -->
                    <div class="mb-4">
                        <label class="form-label-custom required-label">Roll Number</label>
                        <input type="text" name="ROLL_NO" class="form-input-custom" required placeholder="e.g. R-101">
                    </div>

                    <!-- Roll Weight -->
                    <div class="mb-4">
                        <label class="form-label-custom required-label">Roll Weight (KG)</label>
                        <div class="quantity-input-wrapper">
                            <input type="number" step="0.01" min="0.01" name="ROLL_WEIGHT" class="form-input-custom" required placeholder="0.00">
                            <span class="input-group-addon-custom">KG</span>
                        </div>
                    </div>

                    <!-- Inspected By -->
                    <div class="mb-2">
                        <label class="form-label-custom">Inspected By</label>
                        <input type="text" name="INSPECTED_BY" class="form-input-custom" placeholder="e.g. Inspector name" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Right Column: Defects & Grading -->
                <div class="workspace-card">
                    <div class="card-header-custom">
                        <h4 class="card-header-title">
                            <span>🔍</span> Defects & Grading
                        </h4>
                        <span class="badge-pill-header badge-status-purple">QC Grading</span>
                    </div>

                    <!-- Defects 2x2 Grid -->
                    <div class="defects-grid">
                        <div>
                            <label class="form-label-custom">Defect: Hole</label>
                            <input type="number" name="DEFECT_HOLE" class="form-input-custom" value="0" min="0" required>
                        </div>
                        <div>
                            <label class="form-label-custom">Defect: Drop Stitch</label>
                            <input type="number" name="DEFECT_DROP_STITCH" class="form-input-custom" value="0" min="0" required>
                        </div>
                        <div>
                            <label class="form-label-custom">Defect: Oil Mark</label>
                            <input type="number" name="DEFECT_OIL_MARK" class="form-input-custom" value="0" min="0" required>
                        </div>
                        <div>
                            <label class="form-label-custom">Defect: Lycra Out</label>
                            <input type="number" name="DEFECT_LYCRA_OUT" class="form-input-custom" value="0" min="0" required>
                        </div>
                    </div>

                    <!-- Grading Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label-custom required-label">QC Grade</label>
                            <select name="QC_GRADE" class="form-select-custom" required>
                                <option value="">-- Choose Grade --</option>
                                <option value="Grade A">Grade A</option>
                                <option value="Grade B">Grade B</option>
                                <option value="Reject">Reject</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label-custom required-label">QC Status</label>
                            <select name="QC_STATUS" class="form-select-custom" required>
                                <option value="">-- Choose Status --</option>
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="mb-2">
                        <label class="form-label-custom">Remarks / Comments</label>
                        <textarea name="REMARKS" class="form-textarea-custom" rows="3" placeholder="Enter comments or additional defect notes..."></textarea>
                    </div>
                </div>

            </div>

            <!-- Bottom Action Bar -->
            <div class="bottom-actions">
                <a href="knitting_program_list.php" class="btn btn-cancel">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </a>
                <button type="submit" class="btn btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> Save Inspection Result
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>