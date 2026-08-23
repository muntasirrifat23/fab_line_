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
        LEFT JOIN knitting_program kp ON kc.KPTID = kc.KPTID
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

    <!-- QR CODE GENERATOR LIBRARY -->
    <script src="js/qrcode.min.js"></script>

    <style>
        :root {
            --bg-canvas: #e2e8f0;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-canvas);
            font-family: var(--font-main);
            color: #0f172a;
            padding: 20px 10px;
        }

        .action-bar-top {
            max-width: 410px;
            margin: 0 auto 12px auto;
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
            margin: 0 auto;
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

        .card-id-pill {
            background: #000000;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
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
                margin: 0 auto !important;
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
            <a href="knit_card_print.php?id=<?php echo $card_id; ?>" target="_blank" class="btn btn-tag btn-primary">
                <i class="fa-solid fa-print"></i> Full Card
            </a>
            <button type="button" onclick="window.print()" class="btn btn-tag btn-success">
                <i class="fa-solid fa-qrcode"></i> Print 1/6 A4 Tag
            </button>
        </div>
    </div>

    <!-- ALERTS (NO-PRINT) -->
    <?php if (!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 p-2 mb-2 small no-print" style="max-width: 395px; margin: 0 auto 10px auto;">
            <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ═══ EXACT 1/6th A4 TAG CARD CONTAINER (395px x 350px / 98mm x 92mm) ═══ -->
    <div class="a4-sixth-card">

        <!-- CARD TOP HEADER -->
        <div class="card-top-header">
            <div class="company-title">Purbani Fabrics Ltd.</div>
            <div class="card-id-pill"><?php echo htmlspecialchars($val_card_id); ?></div>
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
                    QR: <strong><?php echo htmlspecialchars($roll_number); ?></strong>
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
                        <th>Yarn</th>
                        <td><?php echo htmlspecialchars($val_ytype); ?></td>
                        <th>Count</th>
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
            <div>User: <strong><?php echo htmlspecialchars($val_uname); ?></strong></div>
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
        });
    </script>
</body>

</html>



