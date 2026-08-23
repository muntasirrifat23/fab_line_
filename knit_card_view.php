<?php
// knit_card_view.php - 1/6th A4 Tag Card View: QR Code Left, Specs Right
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

// Extract dynamic values
$val_card_id      = "#KC-" . $card['KCTID'];
$val_sub_tid      = !empty($card['SUB_TID']) ? $card['SUB_TID'] : (!empty($card['prog_sub_tid']) ? $card['prog_sub_tid'] : ('KPT-' . $card['KPTID']));
$val_date         = !empty($card['CREATED_DATE']) ? date('d M Y', strtotime($card['CREATED_DATE'])) : date('d M Y');
$val_buyer        = !empty($card['BUYER']) ? $card['BUYER'] : 'N/A';
$val_customer     = !empty($card['CUSTOMER']) ? $card['CUSTOMER'] : ($card['prog_customer'] ?? 'N/A');
$val_po           = !empty($card['PO_NUMBER']) ? $card['PO_NUMBER'] : ($card['prog_po'] ?? 'N/A');
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

// Dynamic User info: UNAME stores the login (USER_ID) at card creation time.
// Look up users table to get both display name and ID in real time.
$val_user_name    = $val_uname;
$val_user_id      = $val_uname;
if (!empty($card['UNAME'])) {
    $u_stmt = $db->prepare("SELECT USER_NAME, USER_ID FROM users WHERE USER_ID = ? LIMIT 1");
    if ($u_stmt) {
        $u_stmt->bind_param("s", $card['UNAME']);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result();
        if ($u_res && $u_row = $u_res->fetch_assoc()) {
            $val_user_name = !empty($u_row['USER_NAME']) ? $u_row['USER_NAME'] : $u_row['USER_ID'];
            $val_user_id   = $u_row['USER_ID'];
        }
        $u_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1/6 A4 Tag Card | Roll #<?php echo htmlspecialchars($roll_number); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- QR CODE GENERATOR & PDF LIBRARIES -->
    <script src="js/qrcode.min.js"></script>
    <script src="js/html2pdf.bundle.min.js"></script>

    <style>
        :root {
            --bg-canvas: #e2e8f0;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-canvas);
            font-family: var(--font-main);
            color: #0f172a;
            padding: 20px;
        }

        .action-bar-top {
            max-width: 395px;
            margin: 0 0 12px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-tag {
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* ═══ 1/6th A4 PAGE TAG CARD (EXACT SIZE: ~98mm x ~93mm / 390px x 350px) ═══ */
        .a4-sixth-card {
            width: 395px;
            min-height: 350px;
            background: #ffffff;
            border: 2.5px solid #000000;
            border-radius: 10px;
            margin: 0 0 20px 0;
            padding: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* CARD TOP HEADER */
        .card-top-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
            margin: 0;
            line-height: 1.1;
        }

        /* CARD MAIN CONTENT (LEFT QR, RIGHT SPECS) */
        .card-main-row {
            display: flex;
            flex-direction: row;
            gap: 8px;
            align-items: stretch;
            flex-grow: 1;
        }

        /* LEFT COLUMN: ROLL NUMBER & MICRO QR CODE */
        .col-qr-left {
            width: 110px;
            flex-shrink: 0;
            border-right: 1.5px solid #000000;
            padding-right: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .lbl-roll-tag {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }

        .val-roll-num {
            font-size: 12px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: #000000;
            margin-bottom: 6px;
            word-break: break-all;
            line-height: 1;
        }

        .qr-frame-box {
            background: #ffffff;
            padding: 4px;
            border: 1px solid #000000;
            border-radius: 6px;
            display: inline-block;
        }

        .qr-frame-box img, .qr-frame-box canvas {
            display: block;
            margin: 0 auto;
        }

        .qr-payload-lbl {
            font-size: 8.5px;
            font-weight: 700;
            color: #334155;
            margin-top: 4px;
        }

        /* RIGHT COLUMN: SPECS GRID TABLE */
        .col-specs-right {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .tag-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        .tag-table th {
            background-color: #f1f5f9;
            color: #000000;
            font-weight: 700;
            padding: 2.5px 4px;
            border: 1px solid #000000;
            width: 25%;
            text-transform: uppercase;
            font-size: 8.5px;
        }

        .tag-table td {
            padding: 2.5px 4px;
            border: 1px solid #000000;
            font-weight: 700;
            color: #000000;
            width: 25%;
            word-break: break-word;
        }

        /* FOOTER STRIP */
        .card-footer-strip {
            border-top: 1.5px solid #000000;
            padding-top: 4px;
            margin-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 8.5px;
            font-weight: 700;
            color: #334155;
        }

        /* PRINT STYLES FOR EXACT 1/6th A4 PRINT */
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; margin: 0 !important; }
            .a4-sixth-card {
                width: 98mm !important;
                height: 92mm !important;
                min-height: 92mm !important;
                margin: 0 !important;
                border: 2px solid #000000 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
            .tag-table th, .tag-table td {
                border: 1px solid #000000 !important;
            }
        }
    </style>
</head>

<body>

    <!-- TOP ACTION BAR (NO-PRINT) -->
    <div class="action-bar-top no-print">
        <a href="knit_card_report.php" class="btn btn-tag btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Directory
        </a>
        <div class="d-flex gap-1">
            <button type="button" onclick="downloadCard()" class="btn btn-tag btn-primary">
                <i class="fa-solid fa-download"></i> Download
            </button>
            <button type="button" onclick="window.print()" class="btn btn-tag btn-success">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- ALERTS (NO-PRINT) -->
    <?php if (!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 p-2 mb-2 small no-print" style="max-width: 395px; margin: 0 0 10px 0;">
            <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ═══ EXACT 1/6th A4 TAG CARD CONTAINER (LEFT ALIGNED) ═══ -->
    <div class="a4-sixth-card">

        <!-- CARD TOP HEADER -->
        <div class="card-top-header">
            <div class="company-title">Purbani Fabrics Ltd.</div>
        </div>

        <!-- MAIN ROW: LEFT QR CODE, RIGHT SPECS -->
        <div class="card-main-row">

            <!-- LEFT COLUMN: ROLL NUMBER & SCANNABLE QR CODE ONLY -->
            <div class="col-qr-left">
                <div class="lbl-roll-tag">ROLL NO</div>
                <div class="val-roll-num"><?php echo htmlspecialchars($roll_number); ?></div>

                <div class="qr-frame-box">
                    <div id="roll_qrcode"></div>
                </div>

                <div class="qr-payload-lbl">
                    User: <strong><?php echo htmlspecialchars($val_user_name); ?></strong><br>
                    User ID: <strong><?php echo htmlspecialchars($val_user_id); ?></strong>
                </div>
            </div>

            <!-- RIGHT COLUMN: SPECIFICATIONS TABLE -->
            <div class="col-specs-right">
                <table class="tag-table">
                    <tr>
                        <th>Prog ID</th>
                        <td><?php echo htmlspecialchars($val_sub_tid); ?></td>
                        <th>PO No</th>
                        <td><?php echo htmlspecialchars($val_po); ?></td>
                    </tr>
                    <tr>
                        <th>Buyer</th>
                        <td><?php echo htmlspecialchars($val_buyer); ?></td>
                        <th>Customer</th>
                        <td><?php echo htmlspecialchars($val_customer); ?></td>
                    </tr>
                    <tr>
                        <th>M/C No</th>
                        <td><strong><?php echo htmlspecialchars($val_mcno); ?></strong></td>
                        <th>Shift</th>
                        <td><?php echo htmlspecialchars($val_shift); ?></td>
                    </tr>
                    <tr>
                        <th>Style</th>
                        <td><?php echo htmlspecialchars($val_style); ?></td>
                        <th>Color</th>
                        <td><?php echo htmlspecialchars($val_color); ?></td>
                    </tr>
                    <tr>
                        <th>Fabric</th>
                        <td><?php echo htmlspecialchars($val_ftype); ?></td>
                        <th>O / T</th>
                        <td><?php echo htmlspecialchars($val_ot); ?></td>
                    </tr>
                    <tr>
                        <th>Yarn Type</th>
                        <td><?php echo htmlspecialchars($val_ytype); ?></td>
                        <th>Yarn Count</th>
                        <td><?php echo htmlspecialchars($val_ycount); ?></td>
                    </tr>
                    <tr>
                        <th>Lot No</th>
                        <td><?php echo htmlspecialchars($val_lot); ?></td>
                        <th>SL/VDQ</th>
                        <td><?php echo htmlspecialchars($val_sl); ?></td>
                    </tr>
                    <tr>
                        <th>Finish GSM</th>
                        <td><?php echo htmlspecialchars($val_fgsm); ?></td>
                        <th>Finish Dia</th>
                        <td><?php echo htmlspecialchars($val_fdia); ?></td>
                    </tr>
                    <tr>
                        <th>Gray GSM</th>
                        <td><?php echo htmlspecialchars($val_ggsm); ?></td>
                        <th>M/C Dia</th>
                        <td><?php echo htmlspecialchars($val_mcdia); ?></td>
                    </tr>
                    <tr>
                        <th>Feeder</th>
                        <td><?php echo htmlspecialchars($val_feeder); ?></td>
                        <th>Req Qty</th>
                        <td><strong style="color: #059669;"><?php echo htmlspecialchars($val_qty); ?></strong></td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- FOOTER STRIP -->
        <div class="card-footer-strip">
            <div>Date: <strong><?php echo htmlspecialchars($val_date); ?></strong></div>
        </div>

    <!-- HIDDEN CONTAINER FOR EXPORTING FULL PRODUCTION CARD PDF (EXACT FULL CARD FORMAT FROM knit_card_print.php) -->
    <div id="export_full_card_wrapper" style="position: absolute; left: -9999px; top: -9999px; width: 800px; background: #ffffff; padding: 25px 30px; border: 2px solid #000000; font-family: Arial, Helvetica, sans-serif; color: #000000; box-sizing: border-box;">
        <!-- Header with Prominent Scannable QR Code -->
        <div style="border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <div style="width: 130px;"></div>
            
            <div style="text-align: center; flex-grow: 1;">
                <h2 style="font-weight: 800; margin: 0; font-size: 24px; letter-spacing: 1px; text-transform: uppercase; color: #000;">Purbani Fabrics Ltd.</h2>
                <h4 style="font-weight: 700; margin: 2px 0 0 0; font-size: 16px; text-transform: uppercase; color: #000;">Knitting Section</h4>
                <div style="display: inline-block; background: #000; color: #fff; padding: 4px 18px; font-weight: 700; font-size: 15px; margin-top: 6px; border-radius: 4px; letter-spacing: 1px;">PRODUCTION CARD</div>
            </div>

            <div style="text-align: center; width: 130px;">
                <div id="full_export_qrcode" style="width: 120px; height: 120px; margin: 0 auto; padding: 4px; background: #fff; border: 1px solid #000;"></div>
            </div>
        </div>

        <!-- Specifications Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;">
            <tr>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; width: 15%; text-align: left; color: #000;">Date</th>
                <td style="border: 1px solid #000; padding: 6px 8px; width: 35%; color: #000;"><strong><?php echo htmlspecialchars($val_date); ?></strong></td>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; width: 15%; text-align: left; color: #000;">Card ID</th>
                <td style="border: 1px solid #000; padding: 6px 8px; width: 35%; color: #000;"><strong><?php echo htmlspecialchars($val_card_id); ?></strong></td>
            </tr>
            <tr>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Buyer</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><strong><?php echo htmlspecialchars($val_buyer); ?></strong></td>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">PO Number</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_po); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">M/C No</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><strong><?php echo htmlspecialchars($val_mcno); ?></strong></td>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Open / Tube</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_ot); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Style No</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_style); ?></td>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Fabric Type</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_ftype); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Yarn Type</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_ytype); ?></td>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Lot No</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_lot); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Finish Dia</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_fdia); ?></td>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Grey / Finish GSM</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_ggsm . ' / ' . $val_fgsm); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">S.L / VDQ</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_sl); ?></td>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">SONO</th>
                <td style="border: 1px solid #000; padding: 6px 8px; color: #000;"><?php echo htmlspecialchars($val_sono); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #000; padding: 6px 8px; background: #f1f5f9; text-align: left; color: #000;">Req Quantity (KG)</th>
                <td colspan="3" style="border: 1px solid #000; padding: 6px 8px; color: #000;"><strong style="font-size: 15px;"><?php echo htmlspecialchars($val_qty); ?></strong></td>
            </tr>
        </table>

        <!-- Daily Production Log Table -->
        <h5 style="font-weight: 700; font-size: 14px; text-transform: uppercase; margin: 15px 0 8px 0; color: #000;">Daily Production Log</h5>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 12px; text-align: center;">
            <thead>
                <tr style="background: #e2e8f0;">
                    <th style="border: 1px solid #000; padding: 6px;">SL#</th>
                    <th style="border: 1px solid #000; padding: 6px;">Date</th>
                    <th style="border: 1px solid #000; padding: 6px;">Shift A (KG)</th>
                    <th style="border: 1px solid #000; padding: 6px;">Shift B (KG)</th>
                    <th style="border: 1px solid #000; padding: 6px;">Shift C (KG)</th>
                    <th style="border: 1px solid #000; padding: 6px;">Daily Total (KG)</th>
                    <th style="border: 1px solid #000; padding: 6px;">Cum. Total (KG)</th>
                    <th style="border: 1px solid #000; padding: 6px;">Balance (KG)</th>
                    <th style="border: 1px solid #000; padding: 6px;">Operators (A/B/C)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($prod_res && $prod_res->num_rows > 0): ?>
                    <?php 
                    $prod_res->data_seek(0);
                    $sl_full = 1; 
                    ?>
                    <?php while ($p = $prod_res->fetch_assoc()): ?>
                        <?php
                        $ops = array_filter([$p['OPERATOR_A'] ?? '', $p['OPERATOR_B'] ?? '', $p['OPERATOR_C'] ?? '']);
                        $op_str = implode(' / ', $ops);
                        ?>
                        <tr>
                            <td style="border: 1px solid #000; padding: 6px;"><?php echo $sl_full++; ?></td>
                            <td style="border: 1px solid #000; padding: 6px;"><?php echo htmlspecialchars($p['LOG_DATE'] ?? ''); ?></td>
                            <td style="border: 1px solid #000; padding: 6px;"><?php echo number_format((float)($p['A_SHIFT_QTY'] ?? 0), 2); ?></td>
                            <td style="border: 1px solid #000; padding: 6px;"><?php echo number_format((float)($p['B_SHIFT_QTY'] ?? 0), 2); ?></td>
                            <td style="border: 1px solid #000; padding: 6px;"><?php echo number_format((float)($p['C_SHIFT_QTY'] ?? 0), 2); ?></td>
                            <td style="border: 1px solid #000; padding: 6px;"><strong><?php echo number_format((float)($p['PRODUCTION_QTY'] ?? 0), 2); ?></strong></td>
                            <td style="border: 1px solid #000; padding: 6px;"><strong><?php echo number_format((float)($p['CUM_TOTAL'] ?? 0), 2); ?></strong></td>
                            <td style="border: 1px solid #000; padding: 6px;"><strong><?php echo number_format((float)($p['BALANCE'] ?? 0), 2); ?></strong></td>
                            <td style="border: 1px solid #000; padding: 6px;"><small><?php echo htmlspecialchars($op_str); ?></small></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <tr>
                            <td style="border: 1px solid #000; padding: 6px;"><?php echo $i; ?></td>
                            <td style="border: 1px solid #000; padding: 6px;">&nbsp;</td>
                            <td style="border: 1px solid #000; padding: 6px;">&nbsp;</td>
                            <td style="border: 1px solid #000; padding: 6px;">&nbsp;</td>
                            <td style="border: 1px solid #000; padding: 6px;">&nbsp;</td>
                            <td style="border: 1px solid #000; padding: 6px;">&nbsp;</td>
                            <td style="border: 1px solid #000; padding: 6px;">&nbsp;</td>
                            <td style="border: 1px solid #000; padding: 6px;">&nbsp;</td>
                            <td style="border: 1px solid #000; padding: 6px;">&nbsp;</td>
                        </tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Signatures -->
        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-top: 10px; font-size: 13px;">
            <div style="width: 40%; text-align: center;">
                <div><?php echo htmlspecialchars($val_uname); ?></div>
                <div style="border-top: 1px solid #000; margin-top: 35px; padding-top: 4px; font-weight: 700;">Prepared By</div>
            </div>
            <div style="width: 40%; text-align: center;">
                <div><?php echo htmlspecialchars($card['AUTHORISED_BY'] ?? ''); ?></div>
                <div style="border-top: 1px solid #000; margin-top: 35px; padding-top: 4px; font-weight: 700;">Production Officer / Authorised By</div>
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
                    width: 90,
                    height: 90,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }

            var fullQrBox = document.getElementById('full_export_qrcode');
            if (fullQrBox && typeof QRCode !== 'undefined') {
                new QRCode(fullQrBox, {
                    text: "KC-<?php echo $card_id; ?>",
                    width: 110,
                    height: 110,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });

        function downloadCard() {
            var element = document.getElementById('export_full_card_wrapper');
            var cardId = <?php echo json_encode($val_card_id); ?>;
            var filename = 'Production_Card_' + cardId.replace('#', '') + '.pdf';

            // Make wrapper temporarily visible in layout for rendering
            element.style.position = 'relative';
            element.style.left = '0';
            element.style.top = '0';

            if (typeof html2pdf !== 'undefined') {
                var opt = {
                    margin:       [8, 8, 8, 8],
                    filename:     filename,
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, logging: false },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };
                html2pdf().set(opt).from(element).save().then(function() {
                    element.style.position = 'absolute';
                    element.style.left = '-9999px';
                    element.style.top = '-9999px';
                }).catch(function() {
                    element.style.position = 'absolute';
                    element.style.left = '-9999px';
                    element.style.top = '-9999px';
                });
            } else {
                alert('Downloading PDF...');
                element.style.position = 'absolute';
                element.style.left = '-9999px';
                element.style.top = '-9999px';
            }
        }
    </script>
</body>

</html>



