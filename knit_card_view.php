<?php
// knit_card_view.php - Dynamic Roll Number QR & Full Knit Card Information View
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

// Fetch Knit Card joined with Knitting Program for complete dynamic data
$sql = "SELECT kc.*, 
               kp.SUB_TID AS prog_sub_tid, 
               kp.COLOR AS prog_color, 
               kp.CUSTOMER AS prog_customer, 
               kp.YCOUNT AS prog_ycount, 
               kp.FEEDER_PLAN AS prog_feeder_plan, 
               kp.SHIFT AS prog_shift,
               kp.PO_NUMBER AS prog_po
        FROM knit_card kc
        LEFT JOIN knitting_program kp ON kc.KPTID = kp.KPTID
        WHERE kc.KCTID = ?";
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

// Determine Roll Number
$roll_number = !empty($card['ROLL']) ? $card['ROLL'] : ("R-" . $card_id);

// QR Code Payload contains ONLY the Roll Number (as requested)
$qr_payload = strval($roll_number);

// Fetch Daily Production Logs for this Card
$prod_stmt = $db->prepare("SELECT * FROM knit_card_production WHERE KCID = ? ORDER BY LOG_DATE ASC, KCPID ASC");
if ($prod_stmt) {
    $prod_stmt->bind_param("i", $card_id);
    $prod_stmt->execute();
    $prod_res = $prod_stmt->get_result();
} else {
    $prod_res = false;
}

// Extract dynamic field values
$val_card_id      = "#KC-" . $card['KCTID'];
$val_sub_tid      = !empty($card['SUB_TID']) ? $card['SUB_TID'] : (!empty($card['prog_sub_tid']) ? $card['prog_sub_tid'] : ('KPT-' . $card['KPTID']));
$val_date         = !empty($card['CREATED_DATE']) ? date('d M Y, h:i A', strtotime($card['CREATED_DATE'])) : 'N/A';
$val_buyer        = !empty($card['BUYER']) ? $card['BUYER'] : 'N/A';
$val_customer     = !empty($card['CUSTOMER']) ? $card['CUSTOMER'] : ($card['prog_customer'] ?? 'N/A');
$val_po           = !empty($card['PO_NUMBER']) ? $card['PO_NUMBER'] : ($card['prog_po'] ?? 'N/A');
$val_sono         = !empty($card['SONO']) ? $card['SONO'] : 'N/A';
$val_mcno         = !empty($card['MCNO']) ? $card['MCNO'] : 'N/A';
$val_shift        = !empty($card['SHIFT']) ? $card['SHIFT'] : ($card['prog_shift'] ?? 'N/A');
$val_style        = !empty($card['STYLE']) ? $card['STYLE'] : 'N/A';
$val_color        = !empty($card['COLOR']) ? $card['COLOR'] : ($card['prog_color'] ?? 'N/A');
$val_ftype        = !empty($card['FTYPE']) ? $card['FTYPE'] : 'N/A';
$val_ot           = !empty($card['O_T']) ? $card['O_T'] : 'N/A';
$val_ytype        = !empty($card['YTYPE']) ? $card['YTYPE'] : 'N/A';
$val_ycount       = !empty($card['YCOUNT']) ? $card['YCOUNT'] : ($card['prog_ycount'] ?? 'N/A');
$val_lot          = !empty($card['LOT']) ? $card['LOT'] : 'N/A';
$val_fgsm         = !empty($card['FGSM']) ? $card['FGSM'] : 'N/A';
$val_fdia         = !empty($card['FDIA']) ? $card['FDIA'] : 'N/A';
$val_ggsm         = !empty($card['GGSM']) ? $card['GGSM'] : 'N/A';
$val_sl           = !empty($card['SL']) ? $card['SL'] : 'N/A';
$val_mcdia        = !empty($card['MCDIA']) ? $card['MCDIA'] : 'N/A';
$val_feeder       = !empty($card['FEEDER_PLAN']) ? $card['FEEDER_PLAN'] : ($card['prog_feeder_plan'] ?? 'N/A');
$val_qty          = number_format(floatval($card['QTY'] ?? 0), 2) . ' KG';
$val_mat_code     = !empty($card['KNIT_MATERIAL_CODE']) ? $card['KNIT_MATERIAL_CODE'] : 'N/A';
$val_mat_desc     = !empty($card['KNIT_M_DESCRIPTION']) ? $card['KNIT_M_DESCRIPTION'] : 'N/A';
$val_uname        = !empty($card['UNAME']) ? $card['UNAME'] : 'System';
$val_authorised   = !empty($card['AUTHORISED_BY']) ? $card['AUTHORISED_BY'] : 'Pending Authorization';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knit Card <?php echo htmlspecialchars($val_card_id); ?> | Roll #<?php echo htmlspecialchars($roll_number); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- QR CODE GENERATOR LIBRARY -->
    <script src="js/qrcode.min.js"></script>

    <style>
        :root {
            --bg-canvas: #f8fafc;
            --color-card: #ffffff;
            --color-primary: #0f172a;
            --color-border: #e2e8f0;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-canvas);
            font-family: var(--font-main);
            color: var(--color-primary);
            padding: 24px 16px;
        }

        .view-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ── HERO ACTION BAR ── */
        .action-bar-hero {
            background: linear-gradient(135deg, #090d22 0%, #0f172a 50%, #1e3a8a 100%);
            color: #ffffff;
            border-radius: 20px;
            padding: 24px 30px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .action-bar-title h1 {
            font-weight: 800;
            font-size: 1.6rem;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .action-bar-title p {
            margin: 4px 0 0 0;
            font-size: 13.5px;
            color: #93c5fd;
            font-weight: 500;
        }

        .btn-action-hero {
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            padding: 9px 18px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-glass-hero {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            backdrop-filter: blur(10px);
        }

        .btn-glass-hero:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateY(-2px);
        }

        .btn-green-hero {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-green-hero:hover {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* ── GRID CARDS ── */
        .top-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (max-width: 991.98px) {
            .top-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── ROLL & QR BOX CARD ── */
        .roll-qr-card {
            background: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 20px;
            padding: 28px 24px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .roll-label {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .roll-display {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 1px;
            margin-bottom: 20px;
            word-break: break-all;
        }

        .qr-box-wrap {
            padding: 16px;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            display: inline-block;
        }

        .qr-box-wrap img, .qr-box-wrap canvas {
            margin: 0 auto;
            display: block;
        }

        .qr-note {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 600;
            margin-top: 14px;
        }

        /* ── QUICK DETAILS CARD ── */
        .quick-info-card {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .quick-info-header {
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 14px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .quick-info-header h3 {
            font-size: 16px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .badge-card-id {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-weight: 800;
            font-size: 13px;
            padding: 5px 14px;
            border-radius: 20px;
        }

        .quick-specs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 575.98px) {
            .quick-specs-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .quick-spec-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .spec-item-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .spec-item-val {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        /* ── SECTION PANELS ── */
        .section-panel {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
        }

        .section-panel-header {
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 14px;
            margin-bottom: 24px;
        }

        .section-panel-header i {
            font-size: 20px;
            color: #2563eb;
        }

        .section-panel-header h4 {
            font-size: 16px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        /* ── DETAILED SPECIFICATIONS GRID ── */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 991.98px) {
            .details-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 575.98px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        .detail-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
            transition: all 0.2s ease;
        }

        .detail-card:hover {
            border-color: #cbd5e1;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .detail-lbl {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .detail-val {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        /* ── LOG TABLE ── */
        .custom-log-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .custom-log-table thead th {
            background: #0f172a;
            color: #f8fafc;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 16px;
            border: none;
        }

        .custom-log-table thead th:first-child { border-top-left-radius: 12px; }
        .custom-log-table thead th:last-child { border-top-right-radius: 12px; }

        .custom-log-table tbody td {
            padding: 14px 16px;
            font-size: 13.5px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-weight: 500;
        }

        /* PRINT STYLES */
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; }
            .action-bar-hero { display: none !important; }
            .roll-qr-card { border: 3px solid #000000 !important; box-shadow: none !important; }
            .section-panel, .quick-info-card { box-shadow: none !important; border: 1px solid #000000 !important; }
        }
    </style>
</head>

<body>

    <div class="view-container">

        <!-- ═══ ACTION BAR ═══ -->
        <div class="action-bar-hero no-print">
            <div class="action-bar-title">
                <h1>Knitting Card Overview</h1>
                <p>Card ID: <?php echo htmlspecialchars($val_card_id); ?> &bull; Roll Number: <?php echo htmlspecialchars($roll_number); ?></p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="knit_card_report.php" class="btn btn-action-hero btn-glass-hero">
                    <i class="fa-solid fa-arrow-left"></i> Card Directory
                </a>
                <a href="knit_card_print.php?id=<?php echo $card_id; ?>" target="_blank" class="btn btn-action-hero btn-glass-hero">
                    <i class="fa-solid fa-print"></i> Print Full Card
                </a>
                <button type="button" onclick="window.print()" class="btn btn-action-hero btn-green-hero">
                    <i class="fa-solid fa-qrcode"></i> Print Roll QR
                </button>
            </div>
        </div>

        <!-- ALERTS -->
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

        <!-- ═══ TOP GRID: ROLL QR & QUICK INFO ═══ -->
        <div class="top-grid">

            <!-- Roll Number & Scannable QR Code Box (Payload is STRICTLY Roll Number) -->
            <div class="roll-qr-card">
                <div class="roll-label">Roll Number</div>
                <div class="roll-display"><?php echo htmlspecialchars($roll_number); ?></div>

                <div class="qr-box-wrap">
                    <div id="roll_qrcode"></div>
                </div>
                <div class="qr-note">
                    <i class="fa-solid fa-qrcode me-1 text-primary"></i> QR Code Payload: <strong><?php echo htmlspecialchars($roll_number); ?></strong>
                </div>
            </div>

            <!-- Quick Key Specifications Summary -->
            <div class="quick-info-card">
                <div class="quick-info-header">
                    <h3><i class="fa-solid fa-circle-info text-primary me-2"></i> Card Summary</h3>
                    <span class="badge-card-id"><?php echo htmlspecialchars($val_card_id); ?></span>
                </div>

                <div class="quick-specs-grid mb-3">
                    <div class="quick-spec-item">
                        <div class="spec-item-label">Program ID</div>
                        <div class="spec-item-val"><?php echo htmlspecialchars($val_sub_tid); ?></div>
                    </div>
                    <div class="quick-spec-item">
                        <div class="spec-item-label">PO Number</div>
                        <div class="spec-item-val"><?php echo htmlspecialchars($val_po); ?></div>
                    </div>
                    <div class="quick-spec-item">
                        <div class="spec-item-label">Buyer Name</div>
                        <div class="spec-item-val"><?php echo htmlspecialchars($val_buyer); ?></div>
                    </div>
                    <div class="quick-spec-item">
                        <div class="spec-item-label">Machine No</div>
                        <div class="spec-item-val text-primary"><?php echo htmlspecialchars($val_mcno); ?></div>
                    </div>
                    <div class="quick-spec-item">
                        <div class="spec-item-label">Shift</div>
                        <div class="spec-item-val"><?php echo htmlspecialchars($val_shift); ?></div>
                    </div>
                    <div class="quick-spec-item">
                        <div class="spec-item-label">Required Qty</div>
                        <div class="spec-item-val text-success"><?php echo htmlspecialchars($val_qty); ?></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top text-muted small">
                    <div><i class="fa-regular fa-calendar me-1"></i> Created: <strong><?php echo htmlspecialchars($val_date); ?></strong></div>
                    <div><i class="fa-regular fa-user me-1"></i> User: <strong><?php echo htmlspecialchars($val_uname); ?></strong></div>
                </div>
            </div>

        </div>

        <!-- ═══ FULL SPECIFICATIONS & INFORMATION PANEL ═══ -->
        <div class="section-panel">
            <div class="section-panel-header">
                <i class="fa-solid fa-list-check"></i>
                <h4>Production Specifications & Parameters</h4>
            </div>

            <div class="details-grid">
                <!-- Order & Customer -->
                <div class="detail-card">
                    <div class="detail-lbl">Buyer</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_buyer); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Customer</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_customer); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">PO Number / Booking</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_po); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">SO Number</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_sono); ?></div>
                </div>

                <!-- Style & Fabrics -->
                <div class="detail-card">
                    <div class="detail-lbl">Style No</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_style); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Color</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_color); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Fabric Type</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_ftype); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Open / Tube</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_ot); ?></div>
                </div>

                <!-- Yarn Specs -->
                <div class="detail-card">
                    <div class="detail-lbl">Yarn Type</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_ytype); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Yarn Count</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_ycount); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Lot No</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_lot); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Stitch Length / VDQ</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_sl); ?></div>
                </div>

                <!-- Machine & Finishing Params -->
                <div class="detail-card">
                    <div class="detail-lbl">Finish GSM</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_fgsm); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Finish Dia</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_fdia); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Gray GSM</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_ggsm); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Machine Dia (M/C)</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_mcdia); ?></div>
                </div>

                <div class="detail-card" style="grid-column: span 2;">
                    <div class="detail-lbl">Feeder Plan</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_feeder); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Material Code</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_mat_code); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-lbl">Authorised By</div>
                    <div class="detail-val"><?php echo htmlspecialchars($val_authorised); ?></div>
                </div>
            </div>

            <?php if ($val_mat_desc !== 'N/A'): ?>
                <div class="mt-3 p-3 bg-light rounded-3 border">
                    <div class="detail-lbl">Knit Material Description</div>
                    <div class="detail-val text-muted small mt-1"><?php echo htmlspecialchars($val_mat_desc); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ═══ DAILY PRODUCTION LOG PANEL ═══ -->
        <div class="section-panel">
            <div class="section-panel-header">
                <i class="fa-solid fa-chart-line"></i>
                <h4>Daily Production Log Records</h4>
            </div>

            <div class="table-responsive">
                <table class="table custom-log-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">SL#</th>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 12%;">Shift A (KG)</th>
                            <th style="width: 12%;">Shift B (KG)</th>
                            <th style="width: 12%;">Shift C (KG)</th>
                            <th style="width: 15%;">Daily Total (KG)</th>
                            <th style="width: 16%;">Cum. Total (KG)</th>
                            <th style="width: 16%;">Balance (KG)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($prod_res && $prod_res->num_rows > 0): ?>
                            <?php $sl_no = 1; ?>
                            <?php while ($p = $prod_res->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $sl_no++; ?></strong></td>
                                    <td><i class="fa-regular fa-calendar me-1 text-muted"></i><?php echo htmlspecialchars($p['LOG_DATE'] ?? ''); ?></td>
                                    <td><?php echo number_format((float)($p['A_SHIFT_QTY'] ?? 0), 2); ?></td>
                                    <td><?php echo number_format((float)($p['B_SHIFT_QTY'] ?? 0), 2); ?></td>
                                    <td><?php echo number_format((float)($p['C_SHIFT_QTY'] ?? 0), 2); ?></td>
                                    <td><strong class="text-primary"><?php echo number_format((float)($p['PRODUCTION_QTY'] ?? 0), 2); ?></strong></td>
                                    <td><strong class="text-success"><?php echo number_format((float)($p['CUM_TOTAL'] ?? 0), 2); ?></strong></td>
                                    <td><strong class="text-warning"><?php echo number_format((float)($p['BALANCE'] ?? 0), 2); ?></strong></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                    <span class="small fw-semibold">No daily production logs recorded yet for this Knit Card.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var qrBox = document.getElementById('roll_qrcode');
            var qrText = <?php echo json_encode($qr_payload); ?>;

            if (qrBox && typeof QRCode !== 'undefined') {
                new QRCode(qrBox, {
                    text: qrText,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });
    </script>
</body>

</html>

