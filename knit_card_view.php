<?php
// knit_card_view.php - Minimal Roll Number & QR Code View
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

// Fetch Knit Card
$sql = "SELECT * FROM knit_card WHERE KCTID = ?";
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

// QR Code Payload contains ONLY the Roll Number
$qr_payload = strval($roll_number);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roll #<?php echo htmlspecialchars($roll_number); ?> | Knit Card View</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- QR CODE GENERATOR LIBRARY -->
    <script src="js/qrcode.min.js"></script>

    <style>
        :root {
            --color-bg: #f8fafc;
            --color-card: #ffffff;
            --color-primary: #0f172a;
        }

        body {
            background-color: var(--color-bg);
            font-family: 'Inter', sans-serif;
            color: var(--color-primary);
            padding: 30px 15px;
        }

        .roll-card-box {
            max-width: 480px;
            margin: 30px auto;
            background: #ffffff;
            border: 3px solid #0f172a;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.1);
        }

        .roll-title-label {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .roll-number-display {
            font-size: 36px;
            font-weight: 800;
            color: #0f172a;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            letter-spacing: 1px;
            margin-bottom: 30px;
            word-break: break-all;
        }

        .qr-container-box {
            display: inline-block;
            padding: 20px;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .qr-container-box img, .qr-container-box canvas {
            margin: 0 auto;
            display: block;
        }

        /* ── PRINT-FRIENDLY CSS RULES ── */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .roll-card-box {
                border: 3px solid #000000 !important;
                box-shadow: none !important;
                margin: 40px auto !important;
                page-break-inside: avoid;
            }
            .roll-number-display {
                color: #000000 !important;
            }
            .qr-container-box {
                border: 2px solid #000000 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="container" style="max-width: 580px;">

        <!-- ACTION BAR (NO-PRINT) -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="knit_card_report.php" class="btn btn-outline-secondary rounded-pill px-3 fw-bold btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Card Directory
            </a>
            <button type="button" onclick="window.print()" class="btn btn-success rounded-pill px-4 fw-bold btn-sm">
                <i class="fa-solid fa-print me-1"></i> Print Roll QR
            </button>
        </div>

        <!-- SUCCESS / ERROR ALERTS (NO-PRINT) -->
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

        <!-- ROLL NUMBER & QR CODE ONLY CARD -->
        <div class="roll-card-box">
            <div class="roll-title-label">Roll Number</div>
            <div class="roll-number-display"><?php echo htmlspecialchars($roll_number); ?></div>

            <div class="qr-container-box">
                <div id="roll_qrcode"></div>
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
                    width: 220,
                    height: 220,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });
    </script>
</body>

</html>
