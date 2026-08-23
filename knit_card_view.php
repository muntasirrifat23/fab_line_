<?php
// knit_card_view.php - Compact Single-Row View: QR Code Left, Info Right
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

// QR Code Payload contains ONLY the Roll Number (strictly as requested)
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

// Extract dynamic values
$val_card_id      = "#KC-" . $card['KCTID'];
$val_sub_tid      = !empty($card['SUB_TID']) ? $card['SUB_TID'] : (!empty($card['prog_sub_tid']) ? $card['prog_sub_tid'] : ('KPT-' . $card['KPTID']));
$val_date         = !empty($card['CREATED_DATE']) ? date('d M Y, H:i', strtotime($card['CREATED_DATE'])) : 'N/A';
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
$val_uname        = !empty($card['UNAME']) ? $card['UNAME'] : 'System';
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
            --bg-canvas: #f1f5f9;
            --card-bg: #ffffff;
            --border-color: #cbd5e1;
            --text-dark: #0f172a;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-canvas);
            font-family: var(--font-main);
            color: var(--text-dark);
            padding: 20px 12px;
        }

        .compact-wrap {
            max-width: 960px;
            margin: 0 auto;
        }

        /* Top Bar */
        .top-action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .top-action-bar h2 {
            font-size: 1.15rem;
            font-weight: 800;
            margin: 0;
            color: #0f172a;
        }

        .btn-compact {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        /* MAIN COMPACT ROW CARD */
        .compact-card {
            background: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: row;
        }

        @media (max-width: 767.98px) {
            .compact-card {
                flex-direction: column;
            }
        }

        /* LEFT SIDE: QR CODE BOX */
        .card-left-qr {
            width: 220px;
            flex-shrink: 0;
            background: #f8fafc;
            border-right: 2px solid #0f172a;
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .card-left-qr {
                width: 100%;
                border-right: none;
                border-bottom: 2px solid #0f172a;
                padding: 16px;
            }
        }

        .roll-lbl {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .roll-val {
            font-size: 24px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: #0f172a;
            margin-bottom: 12px;
            word-break: break-all;
        }

        .qr-frame {
            background: #ffffff;
            padding: 10px;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .qr-frame img, .qr-frame canvas {
            display: block;
            margin: 0 auto;
        }

        .qr-subtext {
            font-size: 10.5px;
            color: #64748b;
            font-weight: 600;
            margin-top: 10px;
        }

        /* RIGHT SIDE: INFORMATION GRID */
        .card-right-info {
            flex-grow: 1;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .info-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .info-header-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-kc-tag {
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        /* SPECS TABLE (COMPACT MULTI-COLUMN) */
        .specs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .specs-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            width: 14%;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.3px;
        }

        .specs-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            color: #0f172a;
            width: 19%;
        }

        /* LOG TABLE (COMPACT) */
        .log-section {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .log-section h5 {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0 0 10px 0;
            letter-spacing: 0.5px;
        }

        .mini-log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }

        .mini-log-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            padding: 6px 8px;
            text-align: center;
        }

        .mini-log-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            font-weight: 600;
        }

        /* Print Rule */
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; }
            .compact-card { border: 2px solid #000000 !important; box-shadow: none !important; }
            .card-left-qr { border-right: 2px solid #000000 !important; }
        }
    </style>
</head>

<body>

    <div class="compact-wrap">

        <!-- TOP BAR ACTIONS -->
        <div class="top-action-bar no-print">
            <h2><i class="fa-solid fa-id-card text-primary me-2"></i>Knit Card #<?php echo $card_id; ?> Overview</h2>
            <div class="d-flex gap-2">
                <a href="knit_card_report.php" class="btn btn-compact btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Directory
                </a>
                <a href="knit_card_print.php?id=<?php echo $card_id; ?>" target="_blank" class="btn btn-compact btn-primary">
                    <i class="fa-solid fa-print"></i> Print Card
                </a>
                <button type="button" onclick="window.print()" class="btn btn-compact btn-success">
                    <i class="fa-solid fa-qrcode"></i> Print Roll QR
                </button>
            </div>
        </div>

        <!-- ALERTS -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 p-2 mb-3 small no-print">
                <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 p-2 mb-3 small no-print">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ═══ SINGLE-ROW COMPACT CARD (LEFT: QR CODE, RIGHT: INFORMATION) ═══ -->
        <div class="compact-card">

            <!-- LEFT COLUMN: ROLL NUMBER & QR CODE ONLY -->
            <div class="card-left-qr">
                <div class="roll-lbl">Roll Number</div>
                <div class="roll-val"><?php echo htmlspecialchars($roll_number); ?></div>

                <div class="qr-frame">
                    <div id="roll_qrcode"></div>
                </div>

                <div class="qr-subtext">
                    <i class="fa-solid fa-qrcode me-1"></i> QR: <strong><?php echo htmlspecialchars($roll_number); ?></strong>
                </div>
            </div>

            <!-- RIGHT COLUMN: ALL INFORMATION IN SAME ROW -->
            <div class="card-right-info">
                <div class="info-header-row">
                    <div class="info-header-title">
                        <i class="fa-solid fa-list-check text-primary"></i> Specifications & Details
                    </div>
                    <div>
                        <span class="badge-kc-tag"><?php echo htmlspecialchars($val_card_id); ?></span>
                    </div>
                </div>

                <!-- SPECS GRID TABLE -->
                <table class="specs-table">
                    <tr>
                        <th>Date</th>
                        <td><?php echo htmlspecialchars($val_date); ?></td>
                        <th>Program ID</th>
                        <td><strong><?php echo htmlspecialchars($val_sub_tid); ?></strong></td>
                        <th>PO Number</th>
                        <td><strong><?php echo htmlspecialchars($val_po); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Buyer</th>
                        <td><strong><?php echo htmlspecialchars($val_buyer); ?></strong></td>
                        <th>Customer</th>
                        <td><?php echo htmlspecialchars($val_customer); ?></td>
                        <th>M/C No</th>
                        <td><strong class="text-primary"><?php echo htmlspecialchars($val_mcno); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Shift</th>
                        <td><?php echo htmlspecialchars($val_shift); ?></td>
                        <th>Style No</th>
                        <td><?php echo htmlspecialchars($val_style); ?></td>
                        <th>Color</th>
                        <td><?php echo htmlspecialchars($val_color); ?></td>
                    </tr>
                    <tr>
                        <th>Fabric Type</th>
                        <td><?php echo htmlspecialchars($val_ftype); ?></td>
                        <th>Open / Tube</th>
                        <td><?php echo htmlspecialchars($val_ot); ?></td>
                        <th>Yarn Type</th>
                        <td><?php echo htmlspecialchars($val_ytype); ?></td>
                    </tr>
                    <tr>
                        <th>Yarn Count</th>
                        <td><?php echo htmlspecialchars($val_ycount); ?></td>
                        <th>Lot No</th>
                        <td><?php echo htmlspecialchars($val_lot); ?></td>
                        <th>S.L / VDQ</th>
                        <td><?php echo htmlspecialchars($val_sl); ?></td>
                    </tr>
                    <tr>
                        <th>Finish GSM</th>
                        <td><?php echo htmlspecialchars($val_fgsm); ?></td>
                        <th>Finish Dia</th>
                        <td><?php echo htmlspecialchars($val_fdia); ?></td>
                        <th>Gray GSM</th>
                        <td><?php echo htmlspecialchars($val_ggsm); ?></td>
                    </tr>
                    <tr>
                        <th>M/C Dia</th>
                        <td><?php echo htmlspecialchars($val_mcdia); ?></td>
                        <th>Feeder Plan</th>
                        <td><?php echo htmlspecialchars($val_feeder); ?></td>
                        <th>Req Qty</th>
                        <td><strong class="text-success"><?php echo htmlspecialchars($val_qty); ?></strong></td>
                    </tr>
                    <tr>
                        <th>SO NO</th>
                        <td><?php echo htmlspecialchars($val_sono); ?></td>
                        <th>User</th>
                        <td colspan="3"><i class="fa-regular fa-user me-1 text-muted"></i><?php echo htmlspecialchars($val_uname); ?></td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- DAILY PRODUCTION LOG (IF LOG ENTRIES EXIST) -->
        <?php if ($prod_res && $prod_res->num_rows > 0): ?>
            <div class="log-section">
                <h5><i class="fa-solid fa-chart-line text-primary me-1"></i> Daily Production Log</h5>
                <div class="table-responsive">
                    <table class="mini-log-table">
                        <thead>
                            <tr>
                                <th>SL#</th>
                                <th>Date</th>
                                <th>Shift A (KG)</th>
                                <th>Shift B (KG)</th>
                                <th>Shift C (KG)</th>
                                <th>Daily Total</th>
                                <th>Cum. Total</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sl_n = 1; ?>
                            <?php while ($p = $prod_res->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $sl_n++; ?></td>
                                    <td><?php echo htmlspecialchars($p['LOG_DATE'] ?? ''); ?></td>
                                    <td><?php echo number_format((float)($p['A_SHIFT_QTY'] ?? 0), 2); ?></td>
                                    <td><?php echo number_format((float)($p['B_SHIFT_QTY'] ?? 0), 2); ?></td>
                                    <td><?php echo number_format((float)($p['C_SHIFT_QTY'] ?? 0), 2); ?></td>
                                    <td><strong class="text-primary"><?php echo number_format((float)($p['PRODUCTION_QTY'] ?? 0), 2); ?></strong></td>
                                    <td><strong class="text-success"><?php echo number_format((float)($p['CUM_TOTAL'] ?? 0), 2); ?></strong></td>
                                    <td><strong class="text-warning"><?php echo number_format((float)($p['BALANCE'] ?? 0), 2); ?></strong></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

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
                    width: 135,
                    height: 135,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });
    </script>
</body>

</html>


