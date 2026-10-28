<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$uname = $_SESSION['username'];

$options = "";
$lineNumbers = array();

$date = date('d-m-Y');

// Handle POST actions
if (isset($_POST['sew'])) {
    $_SESSION['USERIDNEW'] = $uname;
    $_SESSION['lineNo'] = $_POST['option'];
    $_SESSION['prdty'] = 'SEWING';
    header('location:lineIn.php');
    exit();
}
if (isset($_POST['knitting_input'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_input.php');
    exit();
}
if (isset($_POST['subcontract_input'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:subcontract_input.php');
    exit();
}
if (isset($_POST['knitting_program'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_program.php');
    exit();
}
if (isset($_POST['knitting_production'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_production.php');
    exit();
}
if (isset($_POST['knit_card'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knit_card.php');
    exit();
}
if (isset($_POST['knitting_inspection'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_inspection.php');
    exit();
}
if (isset($_POST['knitting_store'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_store.php');
    exit();
}
if (isset($_POST['knitting_rack_transfer'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_rack_transfer.php');
    exit();
}
if (isset($_POST['knitting_roll_split'])) {
    if (empty($_SESSION['knitting_roll_split_authorized'])) {
        header('Location: initialPage.php?require_knitting_roll_split_password=1');
        exit();
    }
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_roll_split.php');
    exit();
}
if (isset($_POST['dyeing_batch_card'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:dyeing_batch_card.php');
    exit();
}
if (isset($_POST['dyeing_batch_split'])) {
    if (empty($_SESSION['batch_split_authorized'])) {
        header('Location: initialPage.php?require_batch_split_password=1');
        exit();
    }
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:dyeing_batch_split.php');
    exit();
}
if (isset($_POST['user_management'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:user_management.php');
    exit();
}
if (isset($_POST['report'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:report.php');
    exit();
}
if (isset($_POST['users'])) {
    header('location:users.php');
    exit();
}
if (isset($_POST['users_update'])) {
    header('location:users_update.php');
    exit();
}
if (isset($_POST['uploadCSV'])) {
    header('location:upload.php');
    exit();
}
if (isset($_POST['saveChanges'])) {
    if (isset($_POST['selectedLines']) && !empty($_POST['selectedLines'])) {
        $_SESSION['selectedLines'] = $_POST['selectedLines'];
        $_SESSION['lineNo'] = $_POST['selectedLines'][0];
        echo "<script>window.location.href = 'viewKpiTv.php';</script>";
        exit();
    } else {
        echo "<script>alert('Please select at least one line'); window.location.href='initialpage.php';</script>";
        exit();
    }
}
if (isset($_GET['LOGOUT'])) {
    session_destroy();
    header("location: login.php");
    exit();
}

mysqli_close($db);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Initial Page</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/w3.css">
    <link rel="stylesheet" type="text/css" href="css/mycss.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-1: #1e3a8a;
            --primary-2: #2563eb;
            --primary-3: #3b82f6;
            --primary-gradient: linear-gradient(135deg, #1e3a8a, #2563eb, #60a5fa);
            --accent: #f59e0b;
            --accent-gradient: linear-gradient(135deg, #d97706, #f59e0b);
            --muted: #f8fafc;
            --card-bg: rgba(255, 255, 255, .97);
            --shadow-sm: 0 4px 10px rgba(30, 58, 138, .08);
            --shadow: 0 8px 24px rgba(30, 58, 138, .12);
            --shadow-lg: 0 14px 36px rgba(30, 58, 138, .16);
            --glass-border: 1px solid rgba(37, 99, 235, .15);
        }

        * {
            transition: all 0.2s ease;
        }

        body {
            background: linear-gradient(135deg, #f8fafc, #eef4ff, #f5f9ff);
            font-family: 'Poppins', 'Segoe UI', system-ui, 'Inter', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif;
            position: relative;
            min-height: 100vh;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(25, 60, 90, 0.03) 1.5px, transparent 1.5px);
            background-size: 28px 28px;
            pointer-events: none;
            z-index: 0;
        }


        button i,
        .w3-button i,
        i.fas,
        i.far,
        i.fab,
        i {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .container-centered {
            max-width: 1300px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #1e3a8a, #2563eb, #3b82f6);
            color: white;
            padding: 20px 24px;
            border-radius: 32px;
            text-align: center;
            box-shadow: var(--shadow);
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -60%;
            width: 200%;
            height: 200%;
            background: linear-gradient(115deg, rgba(255, 255, 255, 0) 10%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0) 90%);
            transform: rotate(25deg);
            animation: shine 8s infinite;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%) rotate(25deg);
            }

            20% {
                transform: translateX(100%) rotate(25deg);
            }

            100% {
                transform: translateX(200%) rotate(25deg);
            }
        }

        .page-header p {
            margin: 0;
            font-weight: 700;
            font-size: 1.6rem;
            letter-spacing: 1px;
        }

        .glass-panel {
            background: #ffffff;
            border: 1px solid #dbeafe;
            backdrop-filter: blur(2px);
            border-radius: 36px;
            padding: 24px 28px;
            box-shadow: var(--shadow-lg);
            border: var(--glass-border);
            margin-bottom: 30px;
        }

        .selection-row {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .select-box {
            flex: 1;
            min-width: 220px;
            position: relative;
        }

        .select-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #2563eb;
            z-index: 1;
            pointer-events: none;
        }

        select.w3-select {
            width: 100%;
            padding: 14px 18px 14px 42px;
            border-radius: 20px;
            border: 1px solid #cfd8dc;
            background: white;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            font-size: 1rem;
            appearance: none;
        }

        select.w3-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .15);
            outline: none;
        }

        .btn-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin: 24px 0;
        }

        .btn-grid .w3-button {
            padding: 14px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: clamp(0.72rem, 1.15vw, 1.05rem);
            font-family: 'Poppins', 'Segoe UI', system-ui, -apple-system, 'Roboto', Arial, sans-serif;
            letter-spacing: 0.2px;
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            background-size: 200% auto;
            background-position: left center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            min-height: 64px;
            line-height: 1.3;
            text-align: center;
            overflow: hidden;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .btn-grid .w3-button .fa-solid,
        .btn-grid .w3-button .fa,
        .btn-grid .w3-button i {
            flex-shrink: 0;
            line-height: 1;
            font-size: clamp(0.68rem, 1vw, 0.95rem);
        }

        .btn-grid .w3-button {
            text-transform: none;
        }

        .btn-grid .w3-button:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow);
            background-position: right center;
        }

        .w3-teal {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
        }

        .w3-teal:hover {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
        }

        .w3-black {
            background: linear-gradient(135deg, #374151, #111827);
            color: #fff;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .w3-black:hover {
            background: #ffffff !important;
            color: #000000 !important;
            border: 2px solid #000000 !important;
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .15);
        }

        .w3-black:hover i {
            color: #000000 !important;
        }

        .w3-modal .w3-modal-content {
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(8px);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(37, 99, 235, 0.3);
        }

        #lineSelectionModal input[type="checkbox"] {
            transform: scale(1.1);
            margin-right: 10px;
            accent-color: #2563eb;
        }

        #pageFooter {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #1e293b, #334155);
            color: #fff;
            padding: 12px 0;
            backdrop-filter: blur(6px);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 100;
        }

        #pageFooter a {
            color: #ffecb3;
            text-decoration: none;
        }

        #pageFooter a:hover {
            text-decoration: underline;
        }

        @media (max-width: 1100px) {
            .btn-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }
        }

        @media (max-width: 768px) {
            .btn-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .btn-grid .w3-button {
                font-size: 1rem;
                padding: 12px 8px;
                min-height: 56px;
            }

            .selection-row {
                flex-direction: column;
            }

            .glass-panel {
                padding: 20px;
            }

            .page-header p {
                font-size: 1.3rem;
            }

            #uploadCSV {
                display: none !important;
            }
        }

        @media (max-width: 480px) {
            .btn-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }

            .btn-grid .w3-button {
                font-size: 0.88rem;
                padding: 10px 4px;
                min-height: 48px;
                letter-spacing: 0;
                gap: 6px;
            }
        }

        .btn-grid #uploadCSV {
            display: flex !important;
        }

        .btn-grid .w3-button {
            text-transform: none !important;
        }

        .batch-split-auth-overlay {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(15, 23, 42, .55);
        }

        .batch-split-auth-overlay[hidden],
        .batch-split-error[hidden] {
            display: none;
        }

        .batch-split-auth-dialog {
            width: min(100%, 500px);
            padding: 20px 16px 16px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
            color: #212529;
            font-family: 'Segoe UI', sans-serif;
        }

        .batch-split-auth-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 0 30px;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .batch-split-auth-heading .auth-close {
            margin-left: auto;
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 1.7rem;
            line-height: 1;
            cursor: pointer;
        }

        .batch-split-auth-description {
            margin-bottom: 14px;
            color: #6b7280;
        }

        .batch-split-auth-input {
            width: 100%;
            min-height: 46px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 1rem;
        }

        .batch-split-auth-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 30px;
        }

        .batch-split-auth-actions button {
            min-height: 42px;
            padding: 9px 14px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            font-size: .95rem;
            cursor: pointer;
        }

        #cancelBatchSplitAuth {
            background: #6c757d;
        }

        #verifyBatchSplitAuth {
            background: #0d6efd;
        }

        #verifyBatchSplitAuth:disabled {
            opacity: .7;
            cursor: wait;
        }

        .batch-split-error {
            position: fixed;
            z-index: 10001;
            top: 50%;
            left: 50%;
            width: min(calc(100% - 32px), 390px);
            padding: 22px;
            transform: translate(-50%, -50%);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
            text-align: center;
            color: #334155;
        }

        .batch-split-error-icon {
            display: grid;
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            place-items: center;
            border: 3px solid #ef4444;
            border-radius: 50%;
            color: #ef4444;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .batch-split-error strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1.1rem;
        }

        @media (max-width: 480px) {
            .batch-split-auth-dialog {
                padding: 18px 14px 14px;
            }

            .batch-split-auth-heading {
                margin-bottom: 22px;
                font-size: 1.1rem;
            }

            .batch-split-auth-actions {
                margin-top: 22px;
            }
        }
    </style>
</head>

<body>

    <div class="container-centered">
        <div class="page-header">
            <p><i class="fas fa-chalkboard-user"></i> Welcome To FabLine</p>
        </div>
        <div class="glass-panel">
            <form action="initialPage.php" method="post">

                <div class="btn-grid">
                    <button class="w3-button w3-teal" name="subcontract_input" id="subcontract_input">
                        <i class="fa-solid fa-clipboard-list"></i> Subcontract Input
                    </button>

                    <!-- Knitting Buttons -->
                    <button class="w3-button w3-teal" name="knitting_program" id="knitting_program">
                        <i class="fas fa-industry"></i> Knitting Program
                    </button>

                    <button class="w3-button w3-teal" name="knit_card" id="knit_card">
                        <i class="fas fa-tag"></i> Knit Card
                    </button>

                    <button class="w3-button w3-teal" name="knitting_production" id="knitting_production">
                        <i class="fas fa-industry"></i> Knitting Production
                    </button>

                    <button class="w3-button w3-teal" name="knitting_inspection" id="knitting_inspection">
                        <i class="fas fa-clipboard-check"></i> Knitting Inspection
                    </button>

                    <button class="w3-button w3-teal" name="knitting_store" id="knitting_store">
                        <i class="fas fa-warehouse"></i> Knitting Store
                    </button>
                    
                    <button class="w3-button w3-teal" name="knitting_rack_transfer" id="knitting_rack_transfer">
                        <i class="fas fa-warehouse"></i> Knitting Rack Transfer
                    </button>

                    <button class="w3-button w3-teal" name="knitting_roll_split" id="knitting_roll_split">
                        <i class="fas fa-warehouse"></i> Knitting Roll Split
                    </button>

                    <!-- Dyeing Buttons -->
                    <button class="w3-button w3-teal" name="dyeing_batch_card" id="dyeing_batch_card">
                        <i class="fas fa-id-card"></i> Batch Card
                    </button>

                    <button class="w3-button w3-teal" name="dyeing_batch_split" id="dyeing_batch_split">
                        <i class="fas fa-scissors"></i> Batch Split
                    </button>
                    <button class="w3-button w3-teal" name="user_management" id="user_management">
                        <i class="fas fa-users-cog"></i> User Management
                    </button>

                    <button class="w3-button w3-teal" name="report" id="report">
                        <i class="fas fa-chart-bar"></i> All Reports
                    </button>
                </div>

                <div class="btn-grid">
                    <button class="w3-button w3-black" name="uploadCSV" id="uploadCSV"><i class="fas fa-upload"></i> Upload CSV</button>
                </div>

            </form>
        </div>
    </div>

    <div id="batchSplitAuthOverlay" class="batch-split-auth-overlay" hidden>
        <section class="batch-split-auth-dialog" role="dialog" aria-modal="true" aria-labelledby="batchSplitAuthTitle">
            <h2 class="batch-split-auth-heading" id="batchSplitAuthTitle">
                <i class="fas fa-lock" aria-hidden="true"></i>
                Authentication Required
                <button type="button" class="auth-close" id="closeBatchSplitAuth" aria-label="Close">&times;</button>
            </h2>
            <p class="batch-split-auth-description" id="authDescription">Please enter the password to access <strong>Batch Split</strong>.</p>
            <input type="password" id="batchSplitPassword" class="batch-split-auth-input" placeholder="Enter password" autocomplete="current-password">
            <div class="batch-split-auth-actions">
                <button type="button" id="cancelBatchSplitAuth">Cancel</button>
                <button type="button" id="verifyBatchSplitAuth">Verify &amp; Proceed</button>
            </div>
        </section>
    </div>
    <div id="batchSplitAuthError" class="batch-split-error" role="alert" aria-live="assertive" hidden>
        <span class="batch-split-error-icon" aria-hidden="true">!</span>
        <strong>Wrong password</strong>
        <span>Please enter a valid password.</span>
    </div>

    <!-- Line Selection Modal (for DASHBOARD TV) -->
    <div id="lineSelectionModal" class="w3-modal">
        <div class="w3-modal-content w3-card-4 w3-animate-zoom" style="max-width:600px">
            <div class="w3-container">
                <span onclick="hideLineSelectionModal()" class="w3-button w3-display-topright">&times;</span>
                <h2 class="w3-center w3-margin-top"><i class="fas fa-list-check"></i> Select Line Numbers</h2>
                <form action="initialPage.php" method="post">
                    <?php
                    $lineNumbersCount = count($lineNumbers);
                    $columns = 4;
                    $linesPerColumn = ceil($lineNumbersCount / $columns);
                    for ($i = 0; $i < $columns; $i++) {
                        echo '<div class="w3-col s3">';
                        for ($j = $i * $linesPerColumn; $j < min(($i + 1) * $linesPerColumn, $lineNumbersCount); $j++) {
                            $lineNumber = $lineNumbers[(int)$j];
                            // $lineNumber = $lineNumbers[$j];
                            echo '<input type="checkbox" name="selectedLines[]" value="' . htmlspecialchars($lineNumber) . '" id="' . htmlspecialchars($lineNumber) . '">';
                            echo '<label for="' . htmlspecialchars($lineNumber) . '">' . htmlspecialchars($lineNumber) . '</label><br>';
                        }
                        echo '</div>';
                    }
                    ?>
                    <br>
                    <div class="w3-center">
                        <button class="w3-button w3-teal" name="saveChanges"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </div><br>
        </div>
    </div>

    <!-- Import Excel Modal (for IMPORT MP) -->
    <div id="importExcel" class="w3-modal" style="display:none;">
        <div class="w3-modal-content w3-card-4 w3-animate-zoom" style="max-width:500px">
            <div class="w3-container">
                <span onclick="hideImportModal()" class="w3-button w3-display-topright">&times;</span>
                <h2 class="w3-center w3-margin-top"><i class="fas fa-file-excel"></i> Excel Upload</h2>
                <form action="uploadMP.php" method="post" enctype="multipart/form-data" id="uploadForm">
                    <input type="file" name="excelFile" class="w3-input w3-border" id="inputGroupFile02" accept=".xlsx, .xls, .csv">
                    <button type="button" class="w3-button w3-teal w3-margin-top" onclick="submitForm()"><i class="fas fa-cloud-upload-alt"></i> Upload</button>
                </form>
            </div><br>
        </div>
    </div>

    <!-- Footer -->
    <div id="pageFooter" class="w3-container">
        <div class="w3-col s3 w3-center w3-border-right">
            <p><a href="?LOGOUT=1"><i class="fas fa-sign-out-alt"></i> LOGOUT</a></p>
        </div>
        <div class="w3-col s6 w3-center w3-border-right">
            <p><i class="fas fa-user-circle"></i> USERID: <span id="idUSR"><?php echo htmlspecialchars($uname); ?></span></p>
        </div>
        <div class="w3-col s3 w3-center">
            <p><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($date); ?></p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Helper functions for modals
        function showImportModal() {
            document.getElementById('importExcel').style.display = 'block';
            return false;
        }

        function hideImportModal() {
            document.getElementById('importExcel').style.display = 'none';
            return false;
        }

        function submitForm() {
            var fileInput = document.getElementById('inputGroupFile02');
            if (fileInput.value.match(/\.(xls|xlsx|csv)$/i)) {
                document.getElementById('uploadForm').submit();
            } else {
                alert('Please choose a valid file with extension .xls, .xlsx, or .csv.');
            }
        }

        function showLineSelectionModal() {
            document.getElementById('lineSelectionModal').style.display = 'block';
            return false;
        }

        function hideLineSelectionModal() {
            document.getElementById('lineSelectionModal').style.display = 'none';
        }

        $(function() {
            var batchSplitButton = document.getElementById('dyeing_batch_split');
            var batchSplitForm = batchSplitButton ? batchSplitButton.closest('form') : null;
            var authOverlay = document.getElementById('batchSplitAuthOverlay');
            var authPassword = document.getElementById('batchSplitPassword');
            var authError = document.getElementById('batchSplitAuthError');
            var batchSplitAuthorized = <?php echo !empty($_SESSION['batch_split_authorized']) ? 'true' : 'false'; ?>;
            var knittingRollSplitAuthorized = <?php echo !empty($_SESSION['knitting_roll_split_authorized']) ? 'true' : 'false'; ?>;
            var authErrorTimer = null;
            var authSubmitting = false;
            var authConfig = null;

            function openBatchSplitAuth(config) {
                authConfig = config;
                authOverlay.hidden = false;
                authPassword.value = '';
                authError.hidden = true;
                document.getElementById('authDescription').innerHTML =
                    'Please enter the password to access <strong>' + config.label + '</strong>.';
                authPassword.focus();
            }

            function closeBatchSplitAuth() {
                authOverlay.hidden = true;
                authPassword.value = '';
                authSubmitting = false;
                document.getElementById('verifyBatchSplitAuth').disabled = false;
            }

            function showBatchSplitAuthError(message) {
                authError.querySelector('strong').textContent = message || 'Wrong password';
                authError.querySelector('span:last-child').textContent = message
                    ? 'Please try again.'
                    : 'Please enter a valid password.';
                authError.hidden = false;
                window.clearTimeout(authErrorTimer);
                authErrorTimer = window.setTimeout(function() {
                    authError.hidden = true;
                }, 3000);
            }

            function verifyBatchSplitPassword() {
                if (authSubmitting) return;
                var password = authPassword.value;
                if (!password) {
                    showBatchSplitAuthError();
                    return;
                }

                authSubmitting = true;
                var verifyButton = document.getElementById('verifyBatchSplitAuth');
                verifyButton.disabled = true;
                verifyButton.textContent = 'Verifying...';

                fetch(authConfig.verificationUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: authConfig.verifyAction,
                        password: password
                    })
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (!data.success) {
                        authPassword.value = '';
                        authPassword.focus();
                        showBatchSplitAuthError();
                        return;
                    }

                    var submitter = document.createElement('input');
                    submitter.type = 'hidden';
                    submitter.name = authConfig.formAction;
                    submitter.value = '1';
                    batchSplitForm.appendChild(submitter);
                    closeBatchSplitAuth();
                    batchSplitForm.submit();
                })
                .catch(function(error) {
                    console.error(authConfig.label + ' password verification failed:', error);
                    authPassword.value = '';
                    authPassword.focus();
                    showBatchSplitAuthError('Verification failed');
                })
                .finally(function() {
                    authSubmitting = false;
                    verifyButton.disabled = false;
                    verifyButton.textContent = 'Verify & Proceed';
                });
            }

            batchSplitButton.addEventListener('click', function(event) {
                event.preventDefault();
                openBatchSplitAuth({
                    label: 'Batch Split',
                    verificationUrl: 'dyeing_batch_split.php',
                    verifyAction: 'verify_batch_split_password',
                    formAction: 'dyeing_batch_split'
                });
            });
            var knittingRollSplitButton = document.getElementById('knitting_roll_split');
            knittingRollSplitButton.addEventListener('click', function(event) {
                event.preventDefault();
                openBatchSplitAuth({
                    label: 'Knitting Roll Split',
                    verificationUrl: 'knitting_roll_split.php',
                    verifyAction: 'verify_knitting_roll_split_password',
                    formAction: 'knitting_roll_split'
                });
            });
            document.getElementById('verifyBatchSplitAuth').addEventListener('click', verifyBatchSplitPassword);
            document.getElementById('cancelBatchSplitAuth').addEventListener('click', closeBatchSplitAuth);
            document.getElementById('closeBatchSplitAuth').addEventListener('click', closeBatchSplitAuth);
            authPassword.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') verifyBatchSplitPassword();
                if (event.key === 'Escape') closeBatchSplitAuth();
            });

            var currentUrl = new URL(window.location.href);
            var requiresBatchSplitPassword = currentUrl.searchParams.get('require_batch_split_password') === '1';
            var requiresKnittingRollSplitPassword = currentUrl.searchParams.get('require_knitting_roll_split_password') === '1';

            if (requiresBatchSplitPassword && batchSplitAuthorized) {
                currentUrl.searchParams.delete('require_batch_split_password');
                window.history.replaceState(null, '', currentUrl.pathname + currentUrl.search + currentUrl.hash);
            } else if (requiresBatchSplitPassword) {
                openBatchSplitAuth({
                    label: 'Batch Split',
                    verificationUrl: 'dyeing_batch_split.php',
                    verifyAction: 'verify_batch_split_password',
                    formAction: 'dyeing_batch_split'
                });
            }

            if (requiresKnittingRollSplitPassword && knittingRollSplitAuthorized) {
                currentUrl.searchParams.delete('require_knitting_roll_split_password');
                window.history.replaceState(null, '', currentUrl.pathname + currentUrl.search + currentUrl.hash);
            } else if (requiresKnittingRollSplitPassword) {
                openBatchSplitAuth({
                    label: 'Knitting Roll Split',
                    verificationUrl: 'knitting_roll_split.php',
                    verifyAction: 'verify_knitting_roll_split_password',
                    formAction: 'knitting_roll_split'
                });
            }

            window.addEventListener('pageshow', function(event) {
                if (event.persisted) closeBatchSplitAuth();
            });

            var urTYP = document.getElementById('idUSR').innerText.trim().toLowerCase();
            var idFLR = document.getElementById('sidFLOOR');
            var idLNE = document.getElementById('sidLINE');

            // Button visibility based on user type
            $("#uploadCSV").hide();
            $("#user_management").hide();

            if (urTYP === "admin" || urTYP === "siam" || urTYP === "abuhena" || urTYP === "test" || urTYP === "ppq30" || urTYP === "ppq34" || urTYP === "ppq70") {
                // No direct action   just placeholder
            }

            if (urTYP === "ppq70") {
                $("#idAdjust").hide();
            }

            if (urTYP === "qms01") {
                $("#idBTS, #idBTF, #idBTP, #idChange, #idAdjust, #idExcel, #uploadCSV, #ztarget").hide();
                idFLR.style.display = "none";
            }

            if (!isNaN(urTYP) && Number(urTYP) >= 1 && Number(urTYP) <= 52) {
                $("#idBTF, #idBTP, #idChange, #idAdjust, #idExcel, #uploadCSV, #user_management").hide();
                idFLR.style.display = "none";
            }

            if (!isNaN(urTYP) && Number(urTYP) >= 101 && Number(urTYP) <= 999) {
                $("#idBTS, #idBTP, #idChange, #idAdjust, #idExcel, #uploadCSV").hide();
                idFLR.style.display = "none";
            }

            if ((!isNaN(urTYP) && Number(urTYP) >= 1001) || urTYP === "f1" || urTYP === "f2" || urTYP === "f3" || urTYP === "f4") {
                $("#idBTS, #idBTF, #idChange, #idAdjust, #idExcel, #uploadCSV").hide();
                idLNE.style.display = "none";
            }

            if (urTYP === "ppq29" || urTYP === "ppl04" || urTYP === "ppq28") {
                $("#idBTF, #idBTS, #idBTP, #idChange, #idAdjust, #idExcel, #uploadCSV").hide();
                idFLR.style.display = "none";
            }

            if (urTYP === "ppq71") {
                $("#idBTF, #idBTS, #idChange, #idAdjust, #idExcel, #uploadCSV").hide();
                $("#idBTP").show();
                $("#sidFLOOR").show();
                $("#sidLINE").hide();
            }

            if (urTYP === "ppq30" || urTYP === "ppq34" || urTYP === "ppq70" || urTYP === "ppq57" || urTYP === "ppl04" || urTYP === "admin" || urTYP === "siam" || urTYP === "abuhena" || urTYP === "test") {
                $("#idExcel, #uploadCSV, #user_management, #usersBtn, #updateUserBtn, #report").show();
            } else {
                $("#usersBtn, #updateUserBtn").hide();
            }

            if (urTYP === "tv") {
                $("button").hide();
                $("#sidFLOOR, #sidLINE").hide();
                $("#idDash").show();
                $("button[name='saveChanges']").show();
                $("#lineSelectionModal").show();
            }
        });
    </script>
</body>

</html>