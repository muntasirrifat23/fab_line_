<?php
include 'config.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'verify_knitting_roll_split_password'
) {
    header('Content-Type: application/json');
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if (hash_equals('2211', $password)) {
        $_SESSION['knitting_roll_split_authorized'] = true;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Wrong password. Please enter a valid password.']);
    }
    exit();
}

if (empty($_SESSION['knitting_roll_split_authorized'])) {
    if (
        (isset($_GET['action']) && $_GET['action'] === 'get_roll')
        || (isset($_POST['action']) && $_POST['action'] === 'split')
    ) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Knitting Roll Split password verification is required.']);
        exit();
    }

    header('Location: initialPage.php?require_knitting_roll_split_password=1');
    exit();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ================= AJAX: SEARCH ROLL IN STORE =================
if (isset($_GET['action']) && $_GET['action'] === 'get_roll') {
    header('Content-Type: application/json');

    $roll = isset($_GET['roll']) ? trim($_GET['roll']) : '';
    if ($roll === '') {
        echo json_encode(['success' => false, 'error' => 'Roll No is required']);
        exit();
    }

    $s = mysqli_real_escape_string($db, $roll);
    $q = "SELECT * FROM knitting_store WHERE ROLL = '$s' ORDER BY KSTID DESC LIMIT 1";
    $res = mysqli_query($db, $q);

    if ($res && mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Roll not found in Store: ' . $roll]);
    }
    exit();
}

// ================= AJAX: SPLIT ROLL IN STORE =================
if (isset($_POST['action']) && $_POST['action'] === 'split') {
    header('Content-Type: application/json');

    $roll = isset($_POST['roll']) ? trim($_POST['roll']) : '';
    $splitQty = isset($_POST['split_qty']) ? floatval($_POST['split_qty']) : 0;

    if ($roll === '') {
        echo json_encode(['success' => false, 'error' => 'Roll No is required']);
        exit();
    }
    if ($splitQty <= 0) {
        echo json_encode(['success' => false, 'error' => 'Split QTY must be greater than 0']);
        exit();
    }

    $s = mysqli_real_escape_string($db, $roll);
    $q = "SELECT * FROM knitting_store WHERE ROLL = '$s' ORDER BY KSTID DESC LIMIT 1";
    $res = mysqli_query($db, $q);

    if (!$res || mysqli_num_rows($res) == 0) {
        echo json_encode(['success' => false, 'error' => 'Roll not found in Store: ' . $roll]);
        exit();
    }

    $orig = mysqli_fetch_assoc($res);
    $qty = floatval($orig['QTY']);

    if ($splitQty >= $qty) {
        echo json_encode(['success' => false, 'error' => 'Split QTY (' . $splitQty . ') must be less than current Store QTY (' . $qty . ')']);
        exit();
    }

    // Base roll number (strip existing suffix like -A, -B)
    $base = $roll;
    $dashPos = strpos($roll, '-');
    if ($dashPos !== false) {
        $base = substr($roll, 0, $dashPos);
    }

    // Find used suffix letters for this base (e.g. 300000001-A, 300000001-B)
    $baseEsc = mysqli_real_escape_string($db, $base);
    $sufQ = mysqli_query($db, "SELECT ROLL FROM knitting_store WHERE ROLL LIKE '$baseEsc-%'");
    $used = [];
    if ($sufQ) {
        while ($r = mysqli_fetch_assoc($sufQ)) {
            $suffix = substr($r['ROLL'], strlen($base) + 1);
            if (strlen($suffix) === 1 && ctype_alpha($suffix)) {
                $used[strtoupper($suffix)] = true;
            }
        }
    }

    // Pick next available letters (A, B, C, ...)
    $letters = [];
    foreach (range('A', 'Z') as $ch) {
        if (!isset($used[$ch])) {
            $letters[] = $ch;
            if (count($letters) === 2) break;
        }
    }
    if (count($letters) < 2) {
        echo json_encode(['success' => false, 'error' => 'No suffix letters available for base ' . $base]);
        exit();
    }

    $newRollA = $base . '-' . $letters[0];
    $newRollB = $base . '-' . $letters[1];
    $remainB = round($qty - $splitQty, 2);

    // Copy fields from original store row
    $fBUDAT   = mysqli_real_escape_string($db, $orig['BUDAT']);
    $fRACKNO  = mysqli_real_escape_string($db, $orig['RACKNO']);
    $fRACKLOC = mysqli_real_escape_string($db, $orig['RACKLOCATION']);
    $fPO      = mysqli_real_escape_string($db, $orig['PO_NUMBER']);
    $fSONO    = mysqli_real_escape_string($db, $orig['SONO']);
    $fSHIFT   = mysqli_real_escape_string($db, $orig['SHIFT']);
    $fBUYER   = mysqli_real_escape_string($db, $orig['BUYER']);
    $fSTYLE   = mysqli_real_escape_string($db, $orig['STYLE']);
    $fCOLOR   = mysqli_real_escape_string($db, $orig['COLOR']);
    $fMCNO    = mysqli_real_escape_string($db, $orig['MCNO']);
    $fMCDIA   = mysqli_real_escape_string($db, $orig['MCDIA']);
    $fCUST    = mysqli_real_escape_string($db, $orig['CUSTOMER']);
    $fYTYPE   = mysqli_real_escape_string($db, $orig['YTYPE']);
    $fYCOUNT  = mysqli_real_escape_string($db, $orig['YCOUNT']);
    $fYBRAND  = mysqli_real_escape_string($db, isset($orig['YBRAND']) ? $orig['YBRAND'] : '');
    $fOT      = mysqli_real_escape_string($db, $orig['O_T']);
    $fSL      = mysqli_real_escape_string($db, $orig['SL']);
    $fFTYPE   = mysqli_real_escape_string($db, $orig['FTYPE']);
    $fFGSM    = mysqli_real_escape_string($db, $orig['FGSM']);
    $fFDIA    = mysqli_real_escape_string($db, $orig['FDIA']);
    $fFEEDER  = mysqli_real_escape_string($db, $orig['FEEDER_PLAN']);
    $fLOT     = mysqli_real_escape_string($db, $orig['LOT_NO']);
    $fCATEGORY = mysqli_real_escape_string($db, isset($orig['CATEGORY']) ? $orig['CATEGORY'] : '');
    $fQCSTATUS = mysqli_real_escape_string($db, isset($orig['QC_STATUS']) ? $orig['QC_STATUS'] : '');
    $fTPOINT  = mysqli_real_escape_string($db, $orig['TPOINT']);
    $fMCODE   = mysqli_real_escape_string($db, $orig['MCODE']);
    $fMDES    = mysqli_real_escape_string($db, $orig['MDESCRIPTION']);
    // Copy user identity from the original row; if it is missing, fall back to
    // the currently logged-in user so split rows never end up blank/NULL.
    $srcUNAME = isset($orig['UNAME']) ? trim((string) $orig['UNAME']) : '';
    $srcUID   = isset($orig['UID']) ? trim((string) $orig['UID']) : '';
    if ($srcUNAME === '' || $srcUID === '') {
        $sessUser = isset($_SESSION['username']) ? trim((string) $_SESSION['username']) : '';
        if ($sessUser !== '') {
            $esc  = mysqli_real_escape_string($db, $sessUser);
            $uRes = mysqli_query($db, "SELECT USER_ID, USER_NAME FROM users WHERE USER_ID = '$esc' OR USER_NAME = '$esc' LIMIT 1");
            if ($uRes && ($uRow = mysqli_fetch_assoc($uRes))) {
                if ($srcUNAME === '') $srcUNAME = $uRow['USER_NAME'];
                if ($srcUID === '')   $srcUID   = $uRow['USER_ID'];
            } else {
                $oRes = mysqli_query($db, "SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator WHERE OPERATOR_ID = '$esc' LIMIT 1");
                if ($oRes && ($oRow = mysqli_fetch_assoc($oRes))) {
                    if ($srcUNAME === '') $srcUNAME = $oRow['OPERATOR_NAME'];
                    if ($srcUID === '')   $srcUID   = $oRow['OPERATOR_ID'];
                } else {
                    if ($srcUNAME === '') $srcUNAME = $sessUser;
                    if ($srcUID === '')   $srcUID   = $sessUser;
                }
            }
        }
    }
    $fUNAME = mysqli_real_escape_string($db, $srcUNAME);
    $fUID   = mysqli_real_escape_string($db, $srcUID);

    mysqli_begin_transaction($db);

    // Original store row becomes base-A with split qty
    $u1 = "UPDATE knitting_store SET ROLL = '$newRollA', QTY = '$splitQty' WHERE KSTID = '" . intval($orig['KSTID']) . "'";
    // New store row becomes base-B with remaining qty
    $i2 = "INSERT INTO knitting_store
            (BUDAT, RACKNO, RACKLOCATION, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
             MCNO, MCDIA, CUSTOMER, YTYPE, YCOUNT, YBRAND, O_T, SL, FTYPE, FGSM, FDIA,
             FEEDER_PLAN, CATEGORY, LOT_NO, TPOINT, MCODE, MDESCRIPTION, UNAME, UID, QC_STATUS, CREATED_DATE)
           VALUES
            ('$fBUDAT', '$fRACKNO', '$fRACKLOC', '$newRollB', '$fPO', '$remainB', '$fSONO', '$fSHIFT', '$fBUYER', '$fSTYLE', '$fCOLOR',
             '$fMCNO', '$fMCDIA', '$fCUST', '$fYTYPE', '$fYCOUNT', '$fYBRAND', '$fOT', '$fSL', '$fFTYPE', '$fFGSM', '$fFDIA',
             '$fFEEDER', '$fCATEGORY', '$fLOT', '$fTPOINT', '$fMCODE', '$fMDES', '$fUNAME', '$fUID', '$fQCSTATUS', NOW())";

    $ok1 = mysqli_query($db, $u1);
    $ok2 = $ok1 ? mysqli_query($db, $i2) : false;

    if ($ok1 && $ok2) {
        mysqli_commit($db);
        echo json_encode([
            'success' => true,
            'message' => 'Roll split successfully',
            'old_roll' => $roll,
            'roll_a' => $newRollA,
            'qty_a' => $splitQty,
            'roll_b' => $newRollB,
            'qty_b' => $remainB
        ]);
    } else {
        mysqli_rollback($db);
        echo json_encode(['success' => false, 'error' => 'Split failed - ' . mysqli_error($db)]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting Roll Split</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        body {
            padding: 18px;
            background: #f5f7fa;
        }

        .panel {
            background: #fff;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(20, 30, 50, 0.06);
        }

        h1.title {
            font-weight: 700;
            color: #ffffff;
            font-size: clamp(1.25rem, 3vw, 1.8rem);
            margin: 0;
            text-align: center;
        }

        .page-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 12px;
            width: 100%;
            margin-bottom: 20px;
            padding: 14px 18px;
            border: 1px solid #0f766e;
            border-radius: 14px;
            background: linear-gradient(120deg, #0f766e, #2563eb);
            box-shadow: 0 8px 20px rgba(15, 118, 110, 0.18);
        }

        .page-header .back-btn {
            justify-self: start;
        }

        .reenter-btn {
            justify-self: end;
            display: none;
            align-items: center;
            gap: 7px;
            padding: 10px 16px;
            border: 1px solid rgba(255, 255, 255, 0.75);
            border-radius: 8px;
            background: #ffffff;
            color: #0f766e;
            font-weight: 700;
            transition: background .2s ease, transform .2s ease;
        }

        .reenter-btn:hover {
            background: #ecfeff;
            transform: translateY(-1px);
        }

        @media (max-width: 767.98px) {
            .page-header {
                grid-template-columns: 1fr auto;
                gap: 10px;
                padding: 12px;
            }

            .page-header .back-btn {
                grid-column: 1;
                grid-row: 1;
                padding: 8px 10px;
            }

            h1.title {
                grid-column: 1 / -1;
                grid-row: 2;
            }

            .reenter-btn {
                grid-column: 2;
                grid-row: 1;
                padding: 8px 10px;
            }
        }

        .small-muted {
            font-size: 12px;
            color: #6b7280;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 10px;
        }

        .info-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .info-item .lbl {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .info-item .val {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .info-item.hl {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .info-item.hl .val {
            color: #1d4ed8;
            font-family: Consolas, monospace;
            font-size: 17px;
        }

        .back-btn, #backBtn {
            background: #000000 !important;
            color: #ffffff !important;
            border: 2px solid #000000 !important;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        .back-btn:hover, #backBtn:hover {
            background: #ffffff !important;
            border-color: #000000 !important;
            color: #000000 !important;
            text-decoration: none;
        }

        #qrScannerPanel {
            margin-bottom: 16px;
        }

        .lookup-panel {
            width: min(100%, 900px);
            margin: 0 auto 20px;
            padding: 26px 30px;
            text-align: center;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            background: linear-gradient(135deg, #ffffff, #eff6ff);
            box-shadow: 0 10px 24px rgba(30, 64, 175, 0.09);
        }

        .lookup-panel label {
            display: block;
            margin-bottom: 14px;
            font-size: 1.15rem !important;
        }

        .lookup-controls {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .lookup-controls .form-control {
            flex: 1 1 360px;
            max-width: 560px;
            min-height: 46px;
            font-size: 1rem;
        }

        .lookup-controls .btn {
            min-height: 44px;
            margin: 0 !important;
        }

        .roll-split-popup {
            width: min(620px, calc(100vw - 28px)) !important;
            padding: 38px 34px !important;
        }

        .roll-split-popup .swal2-title {
            font-size: 2rem !important;
        }

        .roll-split-popup .swal2-html-container {
            font-size: 1.3rem !important;
            line-height: 1.6 !important;
        }

        .roll-split-popup .swal2-icon {
            transform: scale(1.25);
        }

        .roll-split-popup .swal2-actions {
            gap: 12px;
            margin-top: 1.5rem;
        }

        .roll-split-popup .swal2-styled {
            min-width: 120px;
            min-height: 48px;
            padding: 12px 22px !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
        }

        #splitQtyInput {
            min-height: 44px;
            font-size: 1.35rem;
            padding: 10px 14px;
        }

        .split-form-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
        }

        .split-qty-field {
            flex: 0 0 33.333%;
            max-width: 33.333%;
            min-width: 0;
        }

        .split-action {
            flex: 0 0 auto;
            padding-top: 32px;
        }

        .split-action .btn {
            min-height: 44px;
            white-space: nowrap;
        }

        .lookup-controls .form-control {
            font-size: 1.35rem;
            padding: 10px 14px;
        }

        @media (max-width: 991.98px) {
            .split-qty-field {
                flex-basis: 66.667%;
                max-width: 66.667%;
            }
        }

        @media (max-width: 360px) {
            .split-form-row {
                gap: 8px;
            }

            .split-action {
                padding-top: 32px;
            }

            .split-action .btn {
                padding-right: 10px !important;
                padding-left: 10px !important;
                font-size: 0.85rem;
            }
        }

        .scanner-container {
            position: relative;
            width: 100%;
            max-width: 520px;
            aspect-ratio: 1 / 1;
            margin-left: auto;
            margin-right: auto;
            background: #d9ede9;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: inset 0 0 0 1px #b5d8d2, 0 8px 20px rgba(10, 60, 55, 0.18);
            margin-bottom: 12px;
        }

        #qr-reader {
            width: 100%;
            height: 100%;
            padding: 0 !important;
            background: #e6f4f1;
            overflow: hidden;
        }

        #qr-reader video {
            border-radius: 28px;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .scan-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            border-radius: 28px;
            box-shadow: inset 0 0 0 2px rgba(0, 255, 200, 0.3);
        }

        .scan-overlay::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 76%;
            height: 76%;
            transform: translate(-50%, -50%);
            border: 2px solid rgba(0, 255, 200, 0.5);
            border-radius: 20px;
            box-shadow: 0 0 30px rgba(0, 255, 200, 0.1);
            animation: pulse-border 2.2s infinite ease-in-out;
        }

        @keyframes pulse-border {
            0% {
                opacity: 0.4;
                transform: translate(-50%, -50%) scale(0.96);
            }

            50% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.02);
            }

            100% {
                opacity: 0.4;
                transform: translate(-50%, -50%) scale(0.96);
            }
        }

        .camera-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 520px;
            margin: 12px auto 0;
            padding: 0 6px;
        }

        .status-badge {
            background: #dcebe8;
            padding: 8px 18px;
            border-radius: 100px;
            color: #083a36;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #9fc8c1;
        }

        .status-badge i {
            color: #0f7a6f;
            font-size: 0.9rem;
        }

        .btn-icon {
            background: #dcebe8;
            border: 1px solid #9fc8c1;
            color: #083a36;
            width: 44px;
            height: 44px;
            border-radius: 40px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon:hover {
            background: #c3dcd7;
            border-color: #0f7a6f;
            color: #042f2c;
        }

        .btn-icon:active {
            transform: scale(0.92);
        }

        #qrScannerStatus {
            color: inherit;
        }

        @media (min-width: 576px) and (max-width: 991.98px) {
            body {
                padding: 14px;
            }

            .lookup-panel {
                width: min(100%, 820px);
                padding: 22px;
            }

            .info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            body {
                padding: 10px;
            }

            .page-header {
                margin-bottom: 14px;
                padding: 10px;
            }

            .page-header .back-btn,
            .reenter-btn {
                max-width: 100%;
                font-size: 0.85rem;
            }

            .page-header .back-btn {
                white-space: nowrap;
            }

            .lookup-panel {
                padding: 18px 14px;
            }

            .lookup-panel label {
                font-size: 1rem !important;
            }

            .lookup-controls {
                gap: 8px;
            }

            .lookup-controls .form-control {
                flex: 1 1 100%;
                max-width: none;
            }

            .lookup-controls .btn {
                flex: 1 1 calc(50% - 8px);
                padding-right: 10px !important;
                padding-left: 10px !important;
            }

            .panel {
                padding: 14px;
            }

            .info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .info-item {
                min-width: 0;
                padding: 9px;
            }

            .info-item .val {
                font-size: 0.9rem;
            }

            .info-item.hl .val {
                font-size: 1rem;
            }

            .roll-split-popup {
                width: calc(100vw - 20px) !important;
                padding: 24px 16px !important;
            }

            .roll-split-popup .swal2-title {
                font-size: 1.55rem !important;
            }

            .roll-split-popup .swal2-html-container {
                font-size: 1rem !important;
            }

            .roll-split-popup .swal2-styled {
                min-width: 104px;
                min-height: 46px;
                padding: 10px 14px !important;
                font-size: 1rem !important;
            }
        }

        @media (max-width: 360px) {
            body {
                padding: 6px;
            }

            .page-header .back-btn,
            .reenter-btn {
                padding: 8px !important;
                font-size: 0.75rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .camera-controls {
                padding: 0;
            }

            .status-badge {
                padding: 7px 12px;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 767.98px) {
            .scanner-container,
            .camera-controls {
                max-width: 100%;
            }

            .scanner-container {
                width: min(100%, 520px);
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="page-header">
            <button class="back-btn" id="backBtn">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>
                Back to Initial Page
            </button>
            <h1 class="title">Knitting Roll Split</h1>
            <button class="reenter-btn" id="reenterBtn" type="button">
                <i class="fa-solid fa-rotate-right"></i> Re-enter Roll
            </button>
        </div>

        <div id="rollLookupPanel">
        <div id="qrScannerPanel">
            <div class="scanner-container" id="scannerContainer">
                <div id="qr-reader"></div>
                <div class="scan-overlay"></div>
            </div>
            <div class="camera-controls">
                <div class="status-badge">
                    <i class="fas fa-video"></i>
                    <span id="qrScannerStatus" role="status" aria-live="polite">Starting camera...</span>
                </div>
                <button class="btn-icon" id="scanQrBtn" type="button" title="Restart / switch camera" aria-label="Restart camera">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <div class="panel lookup-panel mb-3">
            <label class="form-label fw-semibold" style="font-size:x-large; color: black;">
                Search Roll Number <span class="small-muted">(from Knitting Store)</span>
            </label>
            <div class="lookup-controls">
                <input type="text" id="rollInput" class="form-control" placeholder="Enter Roll No" autocomplete="off">
                <button class="btn px-4" id="searchBtn" style="margin-top:8px;background:#2563eb;border:1px solid #2563eb;color:#fff;border-radius:8px;">
                    <i class="fa-solid fa-magnifying-glass me-1" style="margin-right:6px;"></i> Search
                </button>
                <button class="btn px-4" id="clearBtn" style="margin-top:8px;margin-left:8px;background:#6b7280;border:1px solid #6b7280;color:#fff;border-radius:8px;">
                    <i class="fa-solid fa-rotate-left me-1" style="margin-right:6px;"></i> Clear
                </button>
            </div>
        </div>
        </div>

        <div id="resultArea"></div>
    </div>

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        var currentRoll = null;
        var qrScanner = null;
        var qrScannerRunning = false;
        var qrScanLocked = false;

        $('#backBtn').on('click', function() {
            history.back();
        });

        $('#reenterBtn').on('click', function() {
            window.location.reload();
        });

        function esc(v) {
            return String(v == null ? '' : v);
        }

        function infoItem(lbl, val, hl) {
            return '<div class="info-item' + (hl ? ' hl' : '') + '"><div class="lbl">' + esc(lbl) + '</div><div class="val">' + esc(val) + '</div></div>';
        }

        function renderRoll(d) {
            currentRoll = d;
            $('#rollLookupPanel').hide();
            $('#reenterBtn').css('display', 'inline-flex');

            var html = '' +
                '<div class="panel">' +
                '<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2" style="font-size: x-large; color: black; font-weight: bold;">' +
                '<h5 class="mb-0 fw-bold" style="color:#1e293b; font-weight: bold; font-size:x-large;"><i class="fa-solid fa-circle-info me-2 text-primary"></i> Store Roll Details</h5>' +
                '</div>' +
                '<div class="info-grid mb-3">' +
                infoItem('Roll No', d.ROLL, true) +
                infoItem('Current QTY', d.QTY) +
                infoItem('Rack No', d.RACKNO) +
                infoItem('Rack Location', d.RACKLOCATION) +
                infoItem('PO Number', d.PO_NUMBER) +
                infoItem('SO No', d.SONO) +
                infoItem('Buyer', d.BUYER) +
                infoItem('Style', d.STYLE) +
                infoItem('Color', d.COLOR) +
                infoItem('Machine No', d.MCNO) +
                infoItem('Machine Dia', d.MCDIA) +
                infoItem('Customer', d.CUSTOMER) +
                infoItem('Fabrics Type', d.FTYPE) +
                infoItem('Finish GSM', d.FGSM) +
                infoItem('Finish Dia', d.FDIA) +
                infoItem('Open / Tube', d.O_T) +
                infoItem('SL / VDQ', d.SL) +
                infoItem('YARN TYPE', d.YTYPE) +
                infoItem('YARN COUNT', d.YCOUNT) +
                infoItem('YARN BRAND', d.YBRAND) +
                infoItem('LOT NO', d.LOT_NO) +
                infoItem('Feeder Plan', d.FEEDER_PLAN) +
                infoItem('CATEGORY', d.CATEGORY) +
                infoItem('QC_STATUS', d.QC_STATUS) +
                '</div>' +
                '<hr>' +
                '<h5 class="fw-bold mb-3" style="color:#1e293b;"><i class="fa-solid fa-scissors me-2 text-danger"></i>Split This Roll</h5>' +
                '<div class="split-form-row">' +
                '<div class="split-qty-field">' +
                '<label class="form-label fw-semibold" style="color:black;">Split QTY</label>' +
                '<input type="number" id="splitQtyInput" class="form-control" min="0.01" step="0.01" placeholder="Enter split QTY">' +
                '<div class="small-muted mt-1">Example: Roll 300000001 (QTY 500), split 200 &rarr; <b>300000001-A</b> (200) + <b>300000001-B</b> (300)</div>' +
                '</div>' +
                '<div class="split-action">' +
                '<button class="btn" id="splitBtn" style="background:#dc2626;border:1px solid #dc2626;color:#fff;border-radius:8px;">' +
                '<i class="fa-solid fa-scissors me-1"></i> Split Roll' +
                '</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('#resultArea').html(html);

            $('#splitBtn').on('click', doSplit);
            $('#splitQtyInput').on('keyup', function(e) {
                if (e.key === 'Enter') doSplit();
            });
        }

        function doSplit() {
            if (!currentRoll) return;

            var splitQty = parseFloat($('#splitQtyInput').val());
            var qty = parseFloat(currentRoll.QTY);

            if (isNaN(splitQty) || splitQty <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid QTY',
                    text: 'Please enter a valid Split QTY',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
            if (splitQty >= qty) {
                Swal.fire({
                    icon: 'error',
                    title: 'QTY not valid!',
                    text: 'Split QTY must be less than current Store QTY (' + qty + ')',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            Swal.fire({
                title: 'Split this store roll?',
                html: 'Roll <b>' + esc(currentRoll.ROLL) + '</b> (' + qty + ')<br>Split QTY: <b>' + splitQty + '</b>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Split!',
                customClass: { popup: 'roll-split-popup' }
            }).then(function(r) {
                if (!r.isConfirmed) return;

                $('#splitBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Splitting...');

                $.ajax({
                        url: 'knitting_roll_split.php',
                        method: 'POST',
                        data: {
                            action: 'split',
                            roll: currentRoll.ROLL,
                            split_qty: splitQty
                        },
                        dataType: 'json'
                    })
                    .done(function(resp) {
                        if (resp && resp.success) {
                            var msgHtml =
                                '<div style="text-align:left;font-size:18px;line-height:1.55;">' +
                                '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 16px;margin-bottom:10px;">' +
                                '<b>Original Roll:</b> ' + resp.old_roll + '</div>' +
                                '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px 16px;margin-bottom:10px;">' +
                                '<b>Roll A:</b> <span style="color:#16a34a;font-weight:800;">' + resp.roll_a + '</span> &rarr; QTY: <b>' + resp.qty_a + '</b></div>' +
                                '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px 16px;">' +
                                '<b>Roll B:</b> <span style="color:#16a34a;font-weight:800;">' + resp.roll_b + '</span> &rarr; QTY: <b>' + resp.qty_b + '</b></div>' +
                                '<div class="small-muted mt-3" style="font-size:15px;">Old roll number will no longer exist - print new labels for both rolls</div>' +
                                '</div>';

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Roll Split Successful!',
                                    html: msgHtml,
                                    customClass: { popup: 'roll-split-popup' },
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false
                                }).then(function() {
                                    window.location.reload();
                                });
                            } else {
                                alert('Roll Split Successful! ' + resp.roll_a + ' (' + resp.qty_a + ') & ' + resp.roll_b + ' (' + resp.qty_b + ')');
                                window.location.reload();
                            }
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Split Failed',
                                    text: (resp && resp.error) ? resp.error : 'Unknown error',
                                    confirmButtonColor: '#2563eb'
                                });
                            } else {
                                alert('Split Failed: ' + ((resp && resp.error) || 'Unknown error'));
                            }
                            $('#splitBtn').prop('disabled', false).html('<i class="fa-solid fa-scissors me-1"></i> Split Roll');
                        }
                    })
                    .fail(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Could not connect to server',
                            confirmButtonColor: '#2563eb'
                        });
                        $('#splitBtn').prop('disabled', false).html('<i class="fa-solid fa-scissors me-1"></i> Split Roll');
                    });
            });
        }

        function showQrError(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: message,
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                });
                return;
            }
            alert(message);
        }

        function loadRoll(roll, restartScannerOnFailure) {
            $.ajax({
                    url: 'knitting_roll_split.php',
                    method: 'GET',
                    data: {
                        action: 'get_roll',
                        roll: roll
                    },
                    dataType: 'json'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderRoll(resp.data);
                    } else {
                        showQrError('Wrong QR Code! Please scan a valid knitting Store Roll.');
                        if (restartScannerOnFailure) restartScanner();
                    }
                })
                .fail(function() {
                    showQrError('Unable to load roll data. Please try again.');
                    if (restartScannerOnFailure) restartScanner();
                });
        }

        function extractRollFromQR(qrText) {
            var text = String(qrText || '').trim();
            if (!text) return null;

            if (text.indexOf('|') !== -1) {
                var first = text.split('|')[0].trim();
                if (first) return first;
            }

            var match = text.match(/ROLL:\s*([^\n\r]+)/i);
            if (match && match[1]) return match[1].trim();

            return /^[a-zA-Z0-9_-]{2,}$/.test(text) ? text : null;
        }

        function setQrScannerStatus(message, isError) {
            $('#qrScannerStatus')
                .text(message)
                .toggleClass('text-danger', Boolean(isError));
        }

        function stopQrScanner() {
            if (!qrScanner) return Promise.resolve(true);

            var scanner = qrScanner;
            var wasRunning = qrScannerRunning;
            qrScanner = null;
            qrScannerRunning = false;
            $('#scanQrBtn').prop('disabled', false);

            var stopped;
            try {
                stopped = wasRunning ? scanner.stop() : Promise.resolve();
            } catch (error) {
                console.error('Unable to stop QR scanner:', error);
                setQrScannerStatus('Unable to stop the camera: ' + (error.message || error), true);
                return Promise.resolve(false);
            }
            return stopped.then(function() {
                scanner.clear();
                return true;
            }).catch(function(error) {
                console.error('Unable to stop QR scanner:', error);
                setQrScannerStatus('Unable to stop the camera: ' + (error.message || error), true);
                return false;
            });
        }

        function startQrScanner() {
            $('#qrScannerPanel').show();
            if (typeof Html5Qrcode === 'undefined') {
                setQrScannerStatus('QR scanner could not be loaded. Use roll number search instead.', true);
                return;
            }

            $('#scanQrBtn').prop('disabled', true);
            setQrScannerStatus('Starting camera...');
            qrScanner = new Html5Qrcode('qr-reader');

            qrScanner.start(
                { facingMode: 'environment' },
                {
                    fps: 20,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        var size = Math.floor(Math.min(320, viewfinderWidth * 0.76, viewfinderHeight * 0.76));
                        return { width: size, height: size };
                    }
                },
                function(decodedText) {
                    if (qrScanLocked) return;
                    qrScanLocked = true;

                    var roll = extractRollFromQR(decodedText);
                    if (!roll) {
                        setQrScannerStatus('This QR code does not contain a valid roll number.', true);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid QR Code',
                            text: 'Please scan a valid knitting store roll QR code.',
                            confirmButtonColor: '#2563eb'
                        }).then(function() {
                            qrScanLocked = false;
                            setQrScannerStatus('Scanning...');
                        });
                        return;
                    }

                    $('#rollInput').val(roll);
                    setQrScannerStatus('QR scanned. Loading store data...');
                    stopQrScanner().then(function(stopped) {
                        if (!stopped) {
                            qrScanLocked = false;
                            return;
                        }
                        $('#qr-reader').empty();
                        $('#qrScannerPanel').show();
                        $('#resultArea').html('<div class="panel"><div class="text-center small-muted">Loading...</div></div>');
                        loadRoll(roll, true);
                    });
                },
                function() {}
            ).then(function() {
                qrScannerRunning = true;
                $('#scanQrBtn').prop('disabled', false);
                setQrScannerStatus('Scanning for a roll QR code...');
            }).catch(function(error) {
                console.error('Unable to start QR scanner:', error);
                if (qrScanner) {
                    try {
                        qrScanner.clear();
                    } catch (cleanupError) {
                        console.error('Unable to clear failed QR scanner:', cleanupError);
                    }
                }
                qrScanner = null;
                qrScannerRunning = false;
                $('#scanQrBtn').prop('disabled', false);
                setQrScannerStatus('Camera unavailable', true);
                Swal.fire({
                    icon: 'error',
                    title: 'Camera unavailable',
                    text: (error.message || error) + '. You can still search by roll number.',
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        }

        function searchRoll() {
            var roll = $('#rollInput').val().trim();
            if (!roll) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Roll No required',
                    text: 'Please enter a Roll No',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
            stopQrScanner().then(function(stopped) {
                if (stopped) $('#qrScannerPanel').hide();
                $('#resultArea').html('<div class="panel"><div class="text-center small-muted">Loading...</div></div>');
                loadRoll(roll);
            });
        }

        function restartScanner() {
            qrScanLocked = false;
            $('#qrScannerPanel').show();
            $('#qr-reader').empty();
            startQrScanner();
        }

        $(function() {
            startQrScanner();
            $('#searchBtn').on('click', searchRoll);
            $('#scanQrBtn').on('click', function() {
                $(this).prop('disabled', true);
                stopQrScanner().then(function(stopped) {
                    if (stopped) {
                        $('#qrScannerPanel').show();
                        $('#qr-reader').empty();
                        startQrScanner();
                    } else {
                        $('#scanQrBtn').prop('disabled', false);
                    }
                });
            });
            $('#clearBtn').on('click', function() {
                stopQrScanner().then(function(stopped) {
                    $('#rollInput').val('');
                    currentRoll = null;
                    $('#resultArea').html('');
                    if (stopped) {
                        $('#qrScannerPanel').show();
                        $('#qr-reader').empty();
                        startQrScanner();
                    }
                });
            });
            $('#rollInput').on('keyup', function(e) {
                if (e.key === 'Enter') searchRoll();
            });
        });
    </script>

</body>

</html>
