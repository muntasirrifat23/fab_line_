<?php
// Public Read-Only View for QR Code Scanning (No Login Required)
$IS_PUBLIC_PAGE = true;
include 'config.php';

$card_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($card_id <= 0) {
    echo "<!DOCTYPE html><html><head><title>Invalid Card</title><link rel='stylesheet' href='css/bootstrap.min.css'></head><body class='p-4 text-center'><h3>Invalid Knit Card ID</h3><p>Please scan a valid Knit Card QR code.</p></body></html>";
    exit();
}

// Fetch Knit Card Header using real column names
$stmt = $db->prepare("SELECT * FROM knit_card WHERE KCID = ?");
if ($stmt) {
    $stmt->bind_param("i", $card_id);
    $stmt->execute();
    $card_res = $stmt->get_result();
} else {
    $card_res = false;
}

if (!$card_res || $card_res->num_rows == 0) {
    echo "<!DOCTYPE html><html><head><title>Card Not Found</title><link rel='stylesheet' href='css/bootstrap.min.css'></head><body class='p-4 text-center'><h3>Knit Card Not Found</h3><p>The requested Knit Card #{$card_id} does not exist.</p></body></html>";
    exit();
}
$card = $card_res->fetch_assoc();

// Fetch Production Logs using real column names
$prod_stmt = $db->prepare("SELECT * FROM knit_card_production WHERE KCID = ? ORDER BY LOG_DATE ASC, KCPID ASC");
if ($prod_stmt) {
    $prod_stmt->bind_param("i", $card_id);
    $prod_stmt->execute();
    $prod_result = $prod_stmt->get_result();
} else {
    $prod_result = false;
}

$logs_array = [];
if ($prod_result && $prod_result->num_rows > 0) {
    while ($pr = $prod_result->fetch_assoc()) {
        $logs_array[] = $pr;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knit Card #<?php echo $card_id; ?> | Production Card</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">

    <style>
        body {
            background-color: #f1f5f9;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            padding: 15px;
        }

        .paper-card-container {
            background: #ffffff;
            max-width: 860px;
            margin: 0 auto;
            padding: 20px 25px;
            border: 2px solid #000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .doc-ref-text {
            text-align: right;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .card-header-box {
            border: 2px solid #000;
            text-align: center;
            padding: 10px;
            margin-bottom: 12px;
            background: #fff;
        }
        .card-header-box h2  { margin: 0; font-weight: 800; font-size: 22px; letter-spacing: 0.5px; text-transform: uppercase; }
        .card-header-box h4  { margin: 3px 0; font-weight: 700; font-size: 15px; text-transform: uppercase; }
        .card-header-box .banner-title {
            display: inline-block; background: #000; color: #fff; padding: 4px 24px;
            font-weight: 800; font-size: 16px; margin-top: 6px; border-radius: 3px;
            letter-spacing: 1px; text-transform: uppercase;
        }

        .specs-wrapper { display: flex; gap: 12px; margin-bottom: 14px; }

        .spec-table { width: 50%; border-collapse: collapse; }
        .spec-table td { border: 1px solid #000; padding: 5px 8px; font-size: 13px; vertical-align: middle; }
        .spec-table td.lbl { font-weight: 700; width: 38%; background-color: #fff; }
        .spec-table td.val { font-weight: 700; width: 62%; background-color: #fff; }

        .status-header-banner {
            text-align: center; font-weight: 800; font-size: 14px;
            background: #e2e8f0; border: 1px solid #000; border-bottom: none;
            padding: 5px; text-transform: uppercase; letter-spacing: 0.5px;
        }

        .production-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .production-table th, .production-table td { border: 1px solid #000; padding: 6px 4px; font-size: 12px; text-align: center; vertical-align: middle; }
        .production-table th { background-color: #f1f5f9; font-weight: 700; }

        .signature-section { display: flex; justify-content: space-between; margin-top: 35px; padding: 0 10px; }
        .signature-box { width: 35%; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 4px; font-weight: 700; font-size: 12px; }

        @media print {
            body { background: #fff; padding: 0; }
            .paper-card-container { box-shadow: none; width: 100%; max-width: 100%; padding: 10px; }
        }
    </style>
</head>

<body>

    <div class="paper-card-container">

        <div class="doc-ref-text">
            PFL/QF/KNIT/02<br>
            Effective Date : 01-06-2021<br>
            Revision : 00
        </div>

        <div class="card-header-box">
            <h2>PURBANI FABRICS LIMITED</h2>
            <h4>Fabric Processing Unit</h4>
            <h4>Knitting Section</h4>
            <div class="banner-title">PRODUCTION CARD</div>
        </div>

        <!-- Specs Grid -->
        <div class="specs-wrapper">
            <!-- Left -->
            <table class="spec-table">
                <tr><td class="lbl">Date</td><td class="val">: <?php echo htmlspecialchars($card['CARD_DATE'] ?? ''); ?></td></tr>
                <tr><td class="lbl">M/c. No.</td><td class="val">: <?php echo htmlspecialchars($card['MCNO'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Finished Dia</td><td class="val">: <?php echo htmlspecialchars($card['FINISH_DIA'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Grey GSM</td><td class="val">: <?php echo htmlspecialchars($card['GREY_GSM'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Finish GSM</td><td class="val">: <?php echo htmlspecialchars($card['FINISH_GSM'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Open / Tube</td><td class="val">: <?php echo htmlspecialchars($card['OPEN_TUBE'] ?? ''); ?></td></tr>
                <tr><td class="lbl">SI / VDQ</td><td class="val">: <?php echo htmlspecialchars($card['SL_VDQ'] ?? ''); ?></td></tr>
            </table>
            <!-- Right -->
            <table class="spec-table">
                <tr><td class="lbl">Buyer</td><td class="val">: <?php echo htmlspecialchars($card['BUYER'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Booking No.</td><td class="val">: <?php echo htmlspecialchars($card['BOOKING'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Style No</td><td class="val">: <?php echo htmlspecialchars($card['STYLE'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Fabric Type</td><td class="val">: <?php echo htmlspecialchars($card['FABRICS_TYPE'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Yarn Type</td><td class="val">: <?php echo htmlspecialchars($card['YARN_TYPE'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Lot No.</td><td class="val">: <?php echo htmlspecialchars($card['LOT_NO'] ?? ''); ?></td></tr>
                <tr><td class="lbl">Req. Qty</td><td class="val">: <?php echo number_format((float)($card['REQ_QTY'] ?? 0), 2); ?> kg</td></tr>
            </table>
        </div>

        <div class="status-header-banner">PRODUCTION STATUS</div>

        <table class="production-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width:12%;">Date</th>
                    <th rowspan="2" style="width:8%;">A</th>
                    <th rowspan="2" style="width:8%;">B</th>
                    <th rowspan="2" style="width:8%;">C</th>
                    <th rowspan="2" style="width:12%;">Production</th>
                    <th rowspan="2" style="width:13%;">Cum. Total</th>
                    <th rowspan="2" style="width:12%;">Balance</th>
                    <th colspan="3" style="width:27%;">Operator Name</th>
                </tr>
                <tr>
                    <th style="width:9%;">A</th>
                    <th style="width:9%;">B</th>
                    <th style="width:9%;">C</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rowCount = 0;
                foreach ($logs_array as $prow):
                    $rowCount++;
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($prow['LOG_DATE']); ?></strong></td>
                        <td><?php echo number_format((float)$prow['A_SHIFT_QTY'], 2); ?></td>
                        <td><?php echo number_format((float)$prow['B_SHIFT_QTY'], 2); ?></td>
                        <td><?php echo number_format((float)$prow['C_SHIFT_QTY'], 2); ?></td>
                        <td><strong><?php echo number_format((float)$prow['PRODUCTION_QTY'], 2); ?></strong></td>
                        <td><strong><?php echo number_format((float)$prow['CUM_TOTAL'], 2); ?></strong></td>
                        <td><strong><?php echo number_format((float)$prow['BALANCE'], 2); ?></strong></td>
                        <td><small><?php echo htmlspecialchars($prow['OPERATOR_A'] ?? ''); ?></small></td>
                        <td><small><?php echo htmlspecialchars($prow['OPERATOR_B'] ?? ''); ?></small></td>
                        <td><small><?php echo htmlspecialchars($prow['OPERATOR_C'] ?? ''); ?></small></td>
                    </tr>
                <?php endforeach; ?>
                <?php for ($i = $rowCount + 1; $i <= 14; $i++): ?>
                    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="signature-section">
            <div class="signature-box">
                <div><?php echo htmlspecialchars($card['PREPARED_BY'] ?? ''); ?></div>
                <div class="signature-line">Prepared by</div>
            </div>
            <div class="signature-box">
                <div><?php echo htmlspecialchars($card['AUTHORISED_BY'] ?? ''); ?></div>
                <div class="signature-line">Authorised by</div>
            </div>
        </div>

    </div>

</body>
</html>
