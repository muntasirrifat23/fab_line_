<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$card_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($card_id <= 0) {
    echo "Invalid Card ID";
    exit();
}

// Build dynamic QR Code URL for scanning (points to public unauthenticated view)
$qr_url = APP_BASE_URL . "/knit_card_public_view.php?id=" . $card_id;

// Fetch Card Header using real KCID column
$stmt = $db->prepare("SELECT * FROM knit_card WHERE KCID = ?");
if ($stmt) {
    $stmt->bind_param("i", $card_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = false;
}

if (!$res || $res->num_rows == 0) {
    echo "Knit Card not found";
    exit();
}

$card = $res->fetch_assoc();

// Fetch Production Log Entries using real KCID and column names
$prod_stmt = $db->prepare("SELECT * FROM knit_card_production WHERE KCID = ? ORDER BY LOG_DATE ASC, KCPID ASC");
if ($prod_stmt) {
    $prod_stmt->bind_param("i", $card_id);
    $prod_stmt->execute();
    $prod_res = $prod_stmt->get_result();
} else {
    $prod_res = false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Knit Card #<?php echo $card['KCID']; ?></title>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="knitting/js/qrcode.min.js"></script>

    <style>
        body {
            background-color: #f8fafc;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            padding: 20px;
        }

        .print-card-container {
            background: #fff;
            max-width: 960px;
            margin: 0 auto;
            padding: 25px 30px;
            border: 2px solid #000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .company-header {
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .company-header h2 {
            font-weight: 800;
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .company-header h4 {
            font-weight: 700;
            margin: 2px 0 0 0;
            font-size: 16px;
            text-transform: uppercase;
        }

        .company-header .card-title-banner {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 4px 18px;
            font-weight: 700;
            font-size: 15px;
            margin-top: 6px;
            border-radius: 4px;
            letter-spacing: 1px;
        }

        .spec-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .spec-table th,
        .spec-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 13px;
        }

        .spec-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            width: 15%;
        }

        .spec-table td {
            width: 35%;
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .log-table th,
        .log-table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 12px;
            text-align: center;
        }

        .log-table th {
            background-color: #e2e8f0;
            font-weight: 700;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 10px;
        }

        .signature-box {
            width: 40%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 4px;
            font-weight: 700;
            font-size: 13px;
        }

        .no-print-bar {
            max-width: 960px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }

            .print-card-container {
                box-shadow: none;
                border: 2px solid #000;
                width: 100%;
                max-width: 100%;
                padding: 15px;
            }

            .no-print-bar {
                display: none !important;
            }

            .spec-table th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .log-table th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-qr-box, #print_qrcode, #print_qrcode img, #print_qrcode canvas {
                display: block !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>

    <!-- Non-printable top action bar -->
    <div class="no-print-bar">
        <a href="knit_card_view.php?id=<?php echo $card['KCID']; ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Card View
        </a>
        <div>
            <button onclick="window.print();" class="btn btn-success btn-sm px-4">
                <i class="fa-solid fa-print me-1"></i> Print Production Card
            </button>
        </div>
    </div>

    <!-- Printable Paper Production Card Container -->
    <div class="print-card-container">
        <!-- Header with Prominent Scannable QR Code -->
        <div class="company-header d-flex justify-content-between align-items-center">
            <div style="width: 140px;" class="d-none d-sm-block"></div>
            
            <div class="text-center flex-grow-1">
                <h2>Purbani Fabrics Ltd.</h2>
                <h4>Knitting Section</h4>
                <div class="card-title-banner">PRODUCTION CARD</div>
            </div>

            <!-- Prominent QR Code for Printed Floor Card -->
            <div class="text-center print-qr-box" style="width: 140px;">
                <div id="print_qrcode" style="width: 130px; height: 130px; margin: 0 auto; padding: 4px; background: #fff; border: 1px solid #000;"></div>
                <div style="font-size: 8.5px; font-weight: 700; text-transform: uppercase; margin-top: 4px; line-height: 1.1;">Scan for live production status</div>
            </div>
        </div>

        <!-- Card Header Specifications Table -->
        <table class="spec-table">
            <tr>
                <th>Date</th>
                <td><strong><?php echo htmlspecialchars($card['CARD_DATE'] ?? ''); ?></strong></td>
                <th>Card ID</th>
                <td><strong>#KC-<?php echo $card['KCID']; ?></strong></td>
            </tr>
            <tr>
                <th>Buyer</th>
                <td><strong><?php echo htmlspecialchars($card['BUYER'] ?? ''); ?></strong></td>
                <th>Booking No</th>
                <td><?php echo htmlspecialchars($card['BOOKING'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>M/C No</th>
                <td><strong><?php echo htmlspecialchars($card['MCNO'] ?? ''); ?></strong></td>
                <th>Open / Tube</th>
                <td><?php echo htmlspecialchars($card['OPEN_TUBE'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Style No</th>
                <td><?php echo htmlspecialchars($card['STYLE'] ?? ''); ?></td>
                <th>Fabric Type</th>
                <td><?php echo htmlspecialchars($card['FABRICS_TYPE'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Yarn Type</th>
                <td><?php echo htmlspecialchars($card['YARN_TYPE'] ?? ''); ?></td>
                <th>Lot No</th>
                <td><?php echo htmlspecialchars($card['LOT_NO'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Finish Dia</th>
                <td><?php echo htmlspecialchars($card['FINISH_DIA'] ?? ''); ?></td>
                <th>Grey GSM / Finish GSM</th>
                <td><?php echo htmlspecialchars(($card['GREY_GSM'] ?? '') . ' / ' . ($card['FINISH_GSM'] ?? '')); ?></td>
            </tr>
            <tr>
                <th>S.L / VDQ</th>
                <td><?php echo htmlspecialchars($card['SL_VDQ'] ?? ''); ?></td>
                <th>SONO</th>
                <td><?php echo htmlspecialchars($card['SONO'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Req Quantity (KG)</th>
                <td colspan="3"><strong style="font-size: 15px;"><?php echo number_format((float)($card['REQ_QTY'] ?? 0), 2); ?> KG</strong></td>
            </tr>
        </table>

        <!-- Daily Production Log Table -->
        <h5 style="font-weight: 700; font-size: 14px; text-transform: uppercase; margin-bottom: 8px;">Daily Production Log</h5>
        <table class="log-table">
            <thead>
                <tr>
                    <th style="width: 5%;">SL#</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 11%;">Shift A (KG)</th>
                    <th style="width: 11%;">Shift B (KG)</th>
                    <th style="width: 11%;">Shift C (KG)</th>
                    <th style="width: 13%;">Daily Total (KG)</th>
                    <th style="width: 13%;">Cum. Total (KG)</th>
                    <th style="width: 12%;">Balance (KG)</th>
                    <th style="width: 12%;">Operators (A/B/C)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($prod_res && $prod_res->num_rows > 0): ?>
                    <?php $sl = 1; ?>
                    <?php while ($p = $prod_res->fetch_assoc()): ?>
                        <?php
                        $ops = array_filter([$p['OPERATOR_A'] ?? '', $p['OPERATOR_B'] ?? '', $p['OPERATOR_C'] ?? '']);
                        $op_str = implode(' / ', $ops);
                        ?>
                        <tr>
                            <td><?php echo $sl++; ?></td>
                            <td><?php echo htmlspecialchars($p['LOG_DATE']); ?></td>
                            <td><?php echo number_format((float)$p['A_SHIFT_QTY'], 2); ?></td>
                            <td><?php echo number_format((float)$p['B_SHIFT_QTY'], 2); ?></td>
                            <td><?php echo number_format((float)$p['C_SHIFT_QTY'], 2); ?></td>
                            <td><strong><?php echo number_format((float)$p['PRODUCTION_QTY'], 2); ?></strong></td>
                            <td><strong><?php echo number_format((float)$p['CUM_TOTAL'], 2); ?></strong></td>
                            <td><strong><?php echo number_format((float)$p['BALANCE'], 2); ?></strong></td>
                            <td><small><?php echo htmlspecialchars($op_str); ?></small></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <div><?php echo htmlspecialchars($card['PREPARED_BY'] ?? ''); ?></div>
                <div class="signature-line">Prepared By</div>
            </div>
            <div class="signature-box">
                <div><?php echo htmlspecialchars($card['AUTHORISED_BY'] ?? ''); ?></div>
                <div class="signature-line">Production Officer / Authorised By</div>
            </div>
        </div>
    </div>

    <!-- Client-Side QR Code Generator Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var printQrBox = document.getElementById("print_qrcode");
            if (printQrBox) {
                new QRCode(printQrBox, {
                    text: "<?php echo $qr_url; ?>",
                    width: 130,
                    height: 130,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });
    </script>
</body>

</html>
