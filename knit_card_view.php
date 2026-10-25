<?php
// knit_card_view.php - Knit Card View & Printing (Matches Knitting Inspection Format)
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$raw_ids = '';
if (!empty($_GET['ids'])) {
    $raw_ids = trim($_GET['ids']);
} elseif (!empty($_GET['id'])) {
    $raw_ids = trim((string)$_GET['id']);
}

$msg   = isset($_GET['msg'])   ? trim($_GET['msg'])   : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';

// Dynamic Back Navigation based on user footprint (Knit Card vs Report)
$back_url   = 'knit_card.php';
$back_label = 'Back to Knit Card';

if (isset($_GET['from'])) {
    $from = strtolower(trim($_GET['from']));
    if ($from === 'report' || $from === 'knit_card_report') {
        $back_url   = 'knit_card_report.php';
        $back_label = 'Back to Report';
    } elseif ($from === 'card' || $from === 'knit_card') {
        $back_url   = 'knit_card.php';
        $back_label = 'Back to Knit Card';
    }
    $_SESSION['knit_card_back_target'] = $back_url;
    $_SESSION['knit_card_back_label']  = $back_label;
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $ref_path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
    $ref_file = strtolower(basename($ref_path ?? ''));
    if ($ref_file === 'knit_card_report.php') {
        $back_url   = 'knit_card_report.php';
        $back_label = 'Back to Report';
        $_SESSION['knit_card_back_target'] = $back_url;
        $_SESSION['knit_card_back_label']  = $back_label;
    } elseif ($ref_file === 'knit_card.php' || $ref_file === 'knit_card_generate.php' || $ref_file === 'initialpage.php') {
        $back_url   = 'knit_card.php';
        $back_label = 'Back to Knit Card';
        $_SESSION['knit_card_back_target'] = $back_url;
        $_SESSION['knit_card_back_label']  = $back_label;
    } elseif ($ref_file !== 'knit_card_print.php' && $ref_file !== 'knit_card_view.php') {
        if (!empty($_SESSION['knit_card_back_target'])) {
            $back_url   = $_SESSION['knit_card_back_target'];
            $back_label = $_SESSION['knit_card_back_label'] ?? ($back_url === 'knit_card_report.php' ? 'Back to Report' : 'Back to Knit Card');
        }
    } else {
        if (!empty($_SESSION['knit_card_back_target'])) {
            $back_url   = $_SESSION['knit_card_back_target'];
            $back_label = $_SESSION['knit_card_back_label'] ?? ($back_url === 'knit_card_report.php' ? 'Back to Report' : 'Back to Knit Card');
        }
    }
} elseif (!empty($_SESSION['knit_card_back_target'])) {
    $back_url   = $_SESSION['knit_card_back_target'];
    $back_label = $_SESSION['knit_card_back_label'] ?? ($back_url === 'knit_card_report.php' ? 'Back to Report' : 'Back to Knit Card');
}

$card_ids = [];
if (!empty($raw_ids)) {
    $parts = explode(',', $raw_ids);
    foreach ($parts as $p) {
        $val = intval(trim($p));
        if ($val > 0) {
            $card_ids[] = $val;
        }
    }
}
$card_ids = array_values(array_unique($card_ids));

if (empty($card_ids)) {
    header("Location: " . $back_url . "?error=Invalid+Card+ID");
    exit();
}

$placeholders = implode(',', array_fill(0, count($card_ids), '?'));
$types = str_repeat('i', count($card_ids));
$kc_prog_col = get_knit_card_program_col($db);

$sql = "SELECT kc.*, 
               kp.PROGRAM_NO AS prog_program_no, 
               kp.COLOR AS prog_color, 
               kp.CUSTOMER AS prog_customer, 
               kp.YCOUNT AS prog_ycount, 
               kp.FEEDER_PLAN AS prog_feeder_plan, 
               kp.SHIFT AS prog_shift,
               kp.PO_NUMBER AS prog_po,
               kp.YBRAND AS prog_ybrand,
               kp.REMARKS AS prog_remarks,
               kp.MCDIA AS prog_mcdia,
               kp.FDIA AS prog_fdia,
               kp.FGSM AS prog_fgsm,
               kp.SONO AS prog_sono
         FROM knit_card kc
         LEFT JOIN knitting_program kp ON (kc.{$kc_prog_col} = kp.PROGRAM_NO OR kc.{$kc_prog_col} = kp.KPTID)
         WHERE kc.KCTID IN ($placeholders)";

$stmt = $db->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$card_ids);
    $stmt->execute();
    $card_res = $stmt->get_result();
} else {
    $card_res = false;
}

if (!$card_res || $card_res->num_rows == 0) {
    header("Location: " . $back_url . "?error=Card+not+found");
    exit();
}

$fetched_cards = [];
while ($row = $card_res->fetch_assoc()) {
    $fetched_cards[intval($row['KCTID'])] = $row;
}
$stmt->close();

$cards_list = [];
foreach ($card_ids as $cid) {
    if (isset($fetched_cards[$cid])) {
        $cards_list[] = $fetched_cards[$cid];
    }
}

if (empty($cards_list)) {
    header("Location: " . $back_url . "?error=Card+not+found");
    exit();
}

$user_cache = [];
$cards_data = [];

foreach ($cards_list as $card) {
    $card_id = intval($card['KCTID']);
    $roll_number = !empty($card['KNITCARD']) ? $card['KNITCARD'] : ("KC-" . $card_id);
    $qr_payload = strval($roll_number);

    $val_card_id      = "#KC-" . $card_id;
    $prog_id_val      = !empty($card['KNITTING_PROGRAM_ID']) ? $card['KNITTING_PROGRAM_ID'] : (!empty($card['Knitting Program ID']) ? $card['Knitting Program ID'] : (!empty($card['knitting_program_id']) ? $card['knitting_program_id'] : ($card['KPTID'] ?? '')));
    $val_sub_tid      = !empty($card['prog_program_no']) ? $card['prog_program_no'] : ('KP-' . $prog_id_val);
    $val_date         = !empty($card['CREATED_DATE']) ? date('d M Y', strtotime($card['CREATED_DATE'])) : date('d M Y');
    $val_buyer        = !empty($card['BUYER']) ? $card['BUYER'] : 'N/A';
    $val_customer     = !empty($card['CUSTOMER']) ? $card['CUSTOMER'] : ($card['prog_customer'] ?? 'N/A');
    $val_sono         = !empty($card['SONO']) ? $card['SONO'] : (!empty($card['prog_sono']) ? $card['prog_sono'] : 'N/A');
    $val_po           = !empty($card['PO_NUMBER']) ? $card['PO_NUMBER'] : ($card['prog_po'] ?? 'N/A');
    $val_mcno         = !empty($card['MCNO']) ? $card['MCNO'] : 'N/A';
    $val_shift        = !empty($card['SHIFT']) ? $card['SHIFT'] : ($card['prog_shift'] ?? 'N/A');
    $val_style        = !empty($card['STYLE']) ? $card['STYLE'] : 'N/A';
    $val_color        = !empty($card['COLOR']) ? $card['COLOR'] : ($card['prog_color'] ?? 'N/A');
    $val_ftype        = !empty($card['FTYPE']) ? $card['FTYPE'] : 'N/A';
    $val_ot           = !empty($card['O_T']) ? $card['O_T'] : 'N/A';
    $val_ytype        = !empty($card['YTYPE']) ? $card['YTYPE'] : 'N/A';
    $val_ycount       = !empty($card['YCOUNT']) ? $card['YCOUNT'] : ($card['prog_ycount'] ?? 'N/A');
    $val_ybrand       = !empty($card['YBRAND']) ? $card['YBRAND'] : ($card['prog_ybrand'] ?? 'N/A');
    $val_remarks      = !empty($card['REMARKS']) ? $card['REMARKS'] : ($card['prog_remarks'] ?? 'N/A');
    $val_lot          = !empty($card['LOT']) ? $card['LOT'] : 'N/A';
    $val_fgsm         = !empty($card['FGSM']) ? $card['FGSM'] : (!empty($card['prog_fgsm']) ? $card['prog_fgsm'] : 'N/A');
    $val_fdia         = !empty($card['FDIA']) ? $card['FDIA'] : (!empty($card['prog_fdia']) ? $card['prog_fdia'] : 'N/A');
    $val_sl           = !empty($card['SL']) ? $card['SL'] : 'N/A';
    $val_mcdia        = !empty($card['MCDIA']) ? $card['MCDIA'] : (!empty($card['prog_mcdia']) ? $card['prog_mcdia'] : 'N/A');
    $val_feeder       = !empty($card['FEEDER_PLAN']) ? $card['FEEDER_PLAN'] : ($card['prog_feeder_plan'] ?? 'N/A');
    $val_qty          = number_format(floatval($card['QTY'] ?? 0), 2) . ' KG';
    $val_uname        = !empty($card['UNAME']) ? $card['UNAME'] : 'System';

    $val_user_name    = $val_uname;
    $val_user_id      = $val_uname;

    if (!empty($card['UNAME'])) {
        if (isset($user_cache[$card['UNAME']])) {
            $val_user_name = $user_cache[$card['UNAME']]['name'];
            $val_user_id   = $user_cache[$card['UNAME']]['id'];
        } else {
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
            $user_cache[$card['UNAME']] = ['name' => $val_user_name, 'id' => $val_user_id];
        }
    }

    $cards_data[] = [
        'card_id'       => $card_id,
        'roll_number'   => $roll_number,
        'qr_payload'    => $qr_payload,
        'val_card_id'   => $val_card_id,
        'val_sub_tid'   => $val_sub_tid,
        'val_date'      => $val_date,
        'val_buyer'     => $val_buyer,
        'val_customer'  => $val_customer,
        'val_sono'      => $val_sono,
        'val_po'        => $val_po,
        'val_mcno'      => $val_mcno,
        'val_shift'     => $val_shift,
        'val_style'     => $val_style,
        'val_color'     => $val_color,
        'val_ftype'     => $val_ftype,
        'val_ot'        => $val_ot,
        'val_ytype'     => $val_ytype,
        'val_ycount'    => $val_ycount,
        'val_ybrand'    => $val_ybrand,
        'val_remarks'   => $val_remarks,
        'val_lot'       => $val_lot,
        'val_fgsm'      => $val_fgsm,
        'val_fdia'      => $val_fdia,
        'val_sl'        => $val_sl,
        'val_mcdia'     => $val_mcdia,
        'val_feeder'    => $val_feeder,
        'val_qty'       => $val_qty,
        'val_user_name' => $val_user_name,
        'val_user_id'   => $val_user_id,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo count($cards_data) > 1 ? ('Knit Tag Cards (' . count($cards_data) . ') | Purbani Fabrics') : ('Knit Tag Card | Roll #' . htmlspecialchars($cards_data[0]['roll_number'])); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- QR CODE & RENDERING LIBRARIES -->
    <script src="js/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        :root {
            --bg-canvas: #e2e8f0;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--bg-canvas);
            font-family: var(--font-main);
            color: #0f172a;
            padding: 20px;
            margin: 0;
        }

        .action-bar-top,
        .card-unit-wrapper,
        .page-alert {
            max-width: 660px;
            margin-left: auto;
            margin-right: auto;
        }

        .action-bar-top { margin-bottom: 16px; }

        .action-bar-top,
        .card-action-bar,
        .btn-group-tag {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .card-unit-wrapper { margin-bottom: 26px; }

        .card-action-bar {
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 9px 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }

        .card-bar-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .btn-tag {
            font-size: 13px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
        }

        .btn-back-dir {
            background-color: #000000 !important;
            color: #ffffff !important;
            border: 1.5px solid #000000 !important;
            transition: all 0.2s ease;
        }

        .btn-back-dir:hover {
            background-color: #ffffff !important;
            border-color: #000000 !important;
            color: #000000 !important;
        }

        /* Screen preview: scrolls sideways on small screens instead of squeezing the card */
        .card-preview-viewport {
            width: 100%;
            overflow-x: auto;
        }

        /* ═══ KNIT TAG CARD (fixed 660px design; printed/downloaded at 190mm wide) ═══ */
        .knit-tag-card {
            width: 660px;
            min-width: 660px;
            max-width: 660px;
            background: #ffffff;
            border: 2px solid #000000;
            border-radius: 8px;
            margin: 0 auto;
            padding: 12px 16px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            font-family: var(--font-main);
            color: #000000;
        }

        .card-top-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-title {
            font-size: 17px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #000000;
            margin: 0;
            line-height: 1.2;
        }

        .card-badge {
            color: #000000;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .card-main-row {
            display: flex;
            flex-direction: row;
            gap: 12px;
            align-items: center;
        }

        .col-qr-left {
            width: 155px;
            flex-shrink: 0;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .lbl-roll-tag {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #000000;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }

        .val-roll-num {
            font-size: 15px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: #000000;
            margin-bottom: 6px;
            word-break: break-all;
            line-height: 1.1;
        }

        .qr-frame-box {
            background: #ffffff;
            padding: 4px;
            border: 1.5px solid #000000;
            border-radius: 6px;
            display: inline-block;
            line-height: 0;
        }

        .qr-frame-box .tag-qr {
            width: 130px;
            height: 130px;
        }

        .qr-frame-box img,
        .qr-frame-box canvas {
            width: 130px !important;
            height: 130px !important;
            display: block;
            margin: 0 auto;
        }

        .qr-payload-lbl {
            font-size: 10.5px;
            font-weight: 700;
            color: #000000;
            margin-top: 6px;
            line-height: 1.3;
        }

        .col-specs-right {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 2px solid #000000;
            padding-left: 12px;
        }

        .tag-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tag-table td {
            padding: 4px 7px;
            border: 1px solid #000000;
            color: #000000;
            width: 50%;
            word-break: break-word;
            font-size: 11.5px;
            line-height: 1.3;
            vertical-align: middle;
        }

        .tag-table .tag-lbl {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10.5px;
        }

        .tag-table .tag-val {
            font-weight: 800;
        }

        .tag-table td.req-qty .tag-val {
            font-size: 13px;
        }

        /* Printing is done through a hidden iframe with a rendered image (see printCardImages) */
        @media print {
            body > *:not(#print_card_iframe) { display: none !important; }
        }
    </style>
</head>

<body>

    <!-- TOP ACTION BAR (NO-PRINT) -->
    <div class="action-bar-top no-print d-flex justify-content-between align-items-center">
        <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-tag btn-back-dir">
            <i class="fa-solid fa-arrow-left"></i> <?php echo htmlspecialchars($back_label); ?>
        </a>
        <div class="btn-group-tag">
            <?php if (count($cards_data) > 1): ?>
                <button type="button" onclick="printAllCards()" class="btn btn-tag btn-secondary">
                    <i class="fa-solid fa-print"></i> Print All Cards (<?php echo count($cards_data); ?>)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ALERTS (NO-PRINT) -->
    <?php if (!empty($msg)): ?>
        <div class="page-alert alert alert-success alert-dismissible fade show rounded-3 p-2 mb-3 small no-print">
            <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="page-alert alert alert-danger alert-dismissible fade show rounded-3 p-2 mb-3 small no-print">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ═══ RENDER ALL KNIT TAG CARDS ═══ -->
    <?php foreach ($cards_data as $idx => $c): ?>
    <div class="card-unit-wrapper" id="card_wrapper_<?php echo $idx; ?>">
        <div class="card-action-bar no-print d-flex justify-content-between align-items-center mb-2">
            <span class="card-bar-title">
                <i class="fa-solid fa-tag me-1 text-primary"></i> <strong>Knit Card <?php echo htmlspecialchars($c['val_card_id']); ?></strong>
                <span class="text-muted ms-1">(Roll: <?php echo htmlspecialchars($c['roll_number']); ?>)</span>
            </span>
            <div class="btn-group-tag">
                <button type="button" onclick="downloadSingleCard('card_box_<?php echo $idx; ?>', <?php echo htmlspecialchars(json_encode(strval($c['roll_number'])), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode(strval($c['card_id'])), ENT_QUOTES); ?>)" class="btn btn-tag btn-primary">
                    <i class="fa-solid fa-download"></i> Download
                </button>
                <button type="button" onclick="printSingleCard('card_box_<?php echo $idx; ?>', <?php echo htmlspecialchars(json_encode(strval($c['roll_number'])), ENT_QUOTES); ?>)" class="btn btn-tag btn-success">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        </div>

        <div class="card-preview-viewport">
            <div class="knit-tag-card" id="card_box_<?php echo $idx; ?>">

                <div class="card-top-header">
                    <div class="company-title">Purbani Fabrics Ltd.</div>
                    <div class="card-badge">Knit Tag Card</div>
                </div>

                <div class="card-main-row">
                    <div class="col-qr-left">
                        <div class="lbl-roll-tag">ROLL NO</div>
                        <div class="val-roll-num"><?php echo htmlspecialchars($c['roll_number']); ?></div>
                        <div class="qr-frame-box">
                            <div class="tag-qr" id="roll_qrcode_<?php echo $idx; ?>"></div>
                        </div>
                        <div class="qr-payload-lbl">
                            User: <strong><?php echo htmlspecialchars($c['val_user_name']); ?></strong><br>
                            ID: <strong><?php echo htmlspecialchars($c['val_user_id']); ?></strong><br>
                            Date: <strong><?php echo htmlspecialchars($c['val_date']); ?></strong>
                        </div>
                    </div>

                    <div class="col-specs-right">
                        <!-- FIXED SERIAL (do not change, do not remove fields):
                             left column top-to-bottom (Prog ID..Shift), then right column (Finish Dia..Yarn Brand),
                             then M/C No + M/C Dia, then O/T + SL/VDQ, then Lot No + Feeder, then Req Qty -->
                        <table class="tag-table">
                            <tr>
                                <td><span class="tag-lbl">Prog ID:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_sub_tid']); ?></span></td>
                                <td><span class="tag-lbl">Finish Dia:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_fdia']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">PO No:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_po']); ?></span></td>
                                <td><span class="tag-lbl">Fabric:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_ftype']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">SONO:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_sono']); ?></span></td>
                                <td><span class="tag-lbl">Finish GSM:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_fgsm']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">Buyer:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_buyer']); ?></span></td>
                                <td><span class="tag-lbl">Customer:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_customer']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">Style:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_style']); ?></span></td>
                                <td><span class="tag-lbl">Yarn Type:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_ytype']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">Color:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_color']); ?></span></td>
                                <td><span class="tag-lbl">Yarn Count:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_ycount']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">Shift:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_shift']); ?></span></td>
                                <td><span class="tag-lbl">Yarn Brand:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_ybrand']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">M/C No:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_mcno']); ?></span></td>
                                <td><span class="tag-lbl">M/C Dia:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_mcdia']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">O / T:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_ot']); ?></span></td>
                                <td><span class="tag-lbl">SL/VDQ:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_sl']); ?></span></td>
                            </tr>
                            <tr>
                                <td><span class="tag-lbl">Lot No:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_lot']); ?></span></td>
                                <td><span class="tag-lbl">Feeder:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_feeder']); ?></span></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="req-qty"><span class="tag-lbl">Req Qty:</span> <span class="tag-val"><?php echo htmlspecialchars($c['val_qty']); ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            <?php foreach ($cards_data as $idx => $c): ?>
            (function() {
                var qrBox = document.getElementById("roll_qrcode_<?php echo $idx; ?>");
                if (qrBox && typeof QRCode !== 'undefined') {
                    new QRCode(qrBox, {
                        text: <?php echo json_encode(strval($c['qr_payload'])); ?>,
                        width: 130,
                        height: 130,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            })();
            <?php endforeach; ?>

            <?php if (isset($_GET['download']) && $_GET['download'] == '1' && count($cards_data) === 1): ?>
            setTimeout(function() {
                downloadSingleCard('card_box_0', <?php echo json_encode(strval($cards_data[0]['roll_number'])); ?>, <?php echo json_encode(strval($cards_data[0]['card_id'])); ?>);
            }, 700);
            <?php endif; ?>
        });

        /* ═══════════════════════════════════════════════════════════════════
           PRINT / DOWNLOAD ENGINE
           The card is always rendered at its fixed 660px design width into an
           image (html2canvas), then placed on A4 at exactly 190mm wide. This
           keeps the format identical in Firefox / Chrome / Edge and on any
           screen size, instead of depending on each browser's print layout.
           ═══════════════════════════════════════════════════════════════════ */
        var TAG_PRINT_WIDTH_MM = 190;   // card width on paper
        var TAG_PAGE_MARGIN_MM = 10;    // left/top margin on A4
        var TAG_GAP_MM         = 6;     // gap between cards when several go on one page
        var TAG_DOWNLOAD_TOP_MM = 25.4; // 1 inch blank space on top of downloaded PDF (for punching a hole / tying)

        function prepareCardClone(cardElement) {
            var clone = cardElement.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.margin = '0';

            // qrcode.js puts both a <canvas> and an <img> in the box -> keep exactly one image
            var origQrs = cardElement.querySelectorAll('.tag-qr');
            var cloneQrs = clone.querySelectorAll('.tag-qr');
            for (var i = 0; i < origQrs.length; i++) {
                var dataUrl = '';
                var oImg = origQrs[i].querySelector('img');
                if (oImg && oImg.src && oImg.src.indexOf('data:image') === 0) {
                    dataUrl = oImg.src;
                } else {
                    var oCanvas = origQrs[i].querySelector('canvas');
                    if (oCanvas) { try { dataUrl = oCanvas.toDataURL('image/png'); } catch (e) {} }
                }
                cloneQrs[i].removeAttribute('id');
                cloneQrs[i].innerHTML = '';
                if (dataUrl) {
                    var img = document.createElement('img');
                    img.src = dataUrl;
                    img.alt = 'QR Code';
                    img.style.cssText = 'width:130px;height:130px;display:block;margin:0 auto;';
                    cloneQrs[i].appendChild(img);
                }
            }
            return clone;
        }

        function renderCardToImage(cardElement) {
            return new Promise(function(resolve, reject) {
                if (!cardElement) { reject(new Error('No card element')); return; }
                if (typeof html2canvas === 'undefined') {
                    reject(new Error('html2canvas not loaded (check internet connection)'));
                    return;
                }
                var clone = prepareCardClone(cardElement);
                var holder = document.createElement('div');
                holder.style.cssText = 'position:absolute;left:-10000px;top:0;width:700px;background:#ffffff;padding:0;margin:0;';
                holder.appendChild(clone);
                document.body.appendChild(holder);

                setTimeout(function() {
                    html2canvas(clone, {
                        scale: 3,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        logging: false
                    }).then(function(canvas) {
                        document.body.removeChild(holder);
                        resolve({
                            src: canvas.toDataURL('image/png'),
                            heightMm: TAG_PRINT_WIDTH_MM * canvas.height / canvas.width
                        });
                    }).catch(function(err) {
                        if (holder.parentNode) document.body.removeChild(holder);
                        reject(err);
                    });
                }, 150);
            });
        }

        function printCardImages(cardElements, title) {
            var cards = Array.prototype.filter.call(cardElements || [], function(c) { return !!c; });
            if (!cards.length) return;

            Promise.all(cards.map(renderCardToImage)).then(function(images) {
                var old = document.getElementById('print_card_iframe');
                if (old && old.parentNode) old.parentNode.removeChild(old);
                var iframe = document.createElement('iframe');
                iframe.id = 'print_card_iframe';
                iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
                document.body.appendChild(iframe);

                // Fill A4 pages top-to-bottom with as many cards as fit
                var usable = 297 - (TAG_PAGE_MARGIN_MM * 2);
                var pagesHtml = '';
                var pageImgs = '';
                var y = 0;
                images.forEach(function(im) {
                    if (pageImgs && (y + im.heightMm) > usable) {
                        pagesHtml += '<div class="lbl-page">' + pageImgs + '</div>';
                        pageImgs = '';
                        y = 0;
                    }
                    pageImgs += '<img src="' + im.src + '" alt="Knit Tag Card" style="top:' + (TAG_PAGE_MARGIN_MM + y).toFixed(2) + 'mm;height:' + im.heightMm.toFixed(2) + 'mm;" />';
                    y += im.heightMm + TAG_GAP_MM;
                });
                if (pageImgs) pagesHtml += '<div class="lbl-page">' + pageImgs + '</div>';

                var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + (title || 'Knit Tag Card') + '</title>' +
                    '<style>' +
                    '@page { size: A4 portrait; margin: 0; }' +
                    'html, body { margin: 0 !important; padding: 0 !important; background: #ffffff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
                    '.lbl-page { position: relative; width: 210mm; height: 296mm; overflow: hidden; page-break-after: always; break-after: page; }' +
                    '.lbl-page:last-child { page-break-after: auto; break-after: auto; }' +
                    '.lbl-page img { position: absolute; left: ' + TAG_PAGE_MARGIN_MM + 'mm; width: ' + TAG_PRINT_WIDTH_MM + 'mm; display: block; margin: 0; padding: 0; }' +
                    '<\/style><\/head><body>' + pagesHtml + '<\/body><\/html>';

                var doc = iframe.contentWindow.document;
                doc.open();
                doc.write(html);
                doc.close();

                var imgs = doc.images;
                var pending = imgs.length;
                var fired = false;
                function go() {
                    if (fired) return;
                    fired = true;
                    setTimeout(function() {
                        try {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        } catch (e) {
                            console.error('Iframe print error:', e);
                        }
                    }, 200);
                }
                if (!pending) { go(); return; }
                for (var i = 0; i < imgs.length; i++) {
                    if (imgs[i].complete) { if (--pending === 0) go(); }
                    else { imgs[i].onload = imgs[i].onerror = function() { if (--pending === 0) go(); }; }
                }
                setTimeout(go, 1500);
            }).catch(function(err) {
                console.error('Print error:', err);
                alert('Error preparing print: ' + err.message);
            });
        }

        function printSingleCard(boxId, roll) {
            printCardImages([document.getElementById(boxId)], 'Knit Tag Card Roll #' + roll);
        }

        function printAllCards() {
            printCardImages(document.querySelectorAll('.knit-tag-card'), 'All Knit Tag Cards');
        }

        function downloadSingleCard(boxId, roll, cardId) {
            var card = document.getElementById(boxId);
            if (!card) return;
            var filename = 'Knit_Card_' + roll + '_KC-' + String(cardId).replace('#', '') + '.pdf';

            renderCardToImage(card).then(function(im) {
                var jsPDFLib = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : (window.jsPDF || null);
                if (jsPDFLib) {
                    var pdf = new jsPDFLib('p', 'mm', 'a4');
                    pdf.addImage(im.src, 'PNG', TAG_PAGE_MARGIN_MM, TAG_DOWNLOAD_TOP_MM, TAG_PRINT_WIDTH_MM, im.heightMm);
                    pdf.save(filename);
                } else {
                    var a = document.createElement('a');
                    a.href = im.src;
                    a.download = filename.replace('.pdf', '.png');
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }
            }).catch(function(err) {
                console.error('Download error:', err);
                alert('Error generating download: ' + err.message);
            });
        }
    </script>
</body>

</html>
