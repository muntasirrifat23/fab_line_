<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$uname = $_SESSION['username'];
// $userIP = $_SERVER['REMOTE_ADDR'];

// // Only for 'test' user
// if ($uname === 'test') {

//     $allowedIPs = ['192.168.16.171', '127.0.0.1', '::1'];

//     if (!in_array($userIP, $allowedIPs)) {
//         session_destroy();
//         echo "<script>alert('Access denied: Only office WiFi allowed'); window.location.href='login.php';</script>";
//         exit();
//     }
// }

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
if (isset($_POST['knitting_program'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_program_list.php');
    exit();
}
if (isset($_POST['knit_cards'])) {
    header('location:knit_card_list.php');
    exit();
}
if (isset($_POST['knitting_store'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:knitting_store.php');
    exit();
}
if (isset($_POST['user_management'])) {
    $_SESSION['lineNo'] = $_POST['option'];
    header('location:user_management.php');
    exit();
}
if (isset($_POST['report'])) {
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
    <style>
        :root {
            --primary-1: #00796b;
            --primary-2: #26a69a;
            --primary-gradient: linear-gradient(135deg, #00796b, #26a69a, #4db6ac);
            --accent: #ff6f60;
            --accent-gradient: linear-gradient(135deg, #e53935, #ff6f60);
            --muted: #f5f5f5;
            --card-bg: rgba(255, 255, 255, 0.92);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.05);
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 12px 32px rgba(0, 0, 0, 0.12);
            --bg-gradient: radial-gradient(circle at 10% 30%, #e0f2f1, #e8f5e9, #fff3e0);
            --glass-border: 1px solid rgba(255, 255, 255, 0.3);
        }

        * {
            transition: all 0.2s ease;
        }

        body {
            background: linear-gradient(145deg, #dceaf5 0%, #e9f2f9 100%);
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
            background: linear-gradient(105deg, #004d40, #00796b, #00897b);
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
            background: var(--card-bg);
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
            color: #00796b;
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
            border-color: var(--primary-1);
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.2);
            outline: none;
        }

        .btn-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
            margin: 24px 0;
        }

        .btn-grid .w3-button {
            padding: 14px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            background-size: 200% auto;
            background-position: left center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-grid .w3-button:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
            background-position: right center;
        }

        .w3-teal {
            background: linear-gradient(95deg, #00796b, #26a69a, #4db6ac);
            color: white;
        }

        .w3-teal:hover {
            background: linear-gradient(95deg, #00695c, #1e88a0, #3d9b91);
        }

        .w3-black {
            background: linear-gradient(95deg, #2c2c2c, #4a4a4a, #6c6c6c);
            color: white;
        }

        .w3-black:hover {
            background: linear-gradient(95deg, #1f1f1f, #3a3a3a, #5a5a5a);
        }

        .w3-modal .w3-modal-content {
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(8px);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(0, 121, 107, 0.3);
        }

        #lineSelectionModal input[type="checkbox"] {
            transform: scale(1.1);
            margin-right: 10px;
            accent-color: #00796b;
        }

        #pageFooter {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(95deg, #2c3e4e, #1e2f3a, #2c3e4e);
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
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .btn-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
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
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>

    <div class="container-centered">
        <div class="page-header">
            <p><i class="fas fa-chalkboard-user"></i> SELECT LINE NO</p>
        </div>
        <div class="glass-panel">
            <form action="initialPage.php" method="post">
                <!-- <div class="selection-row">
                    <div class="select-box">
                        <i class="fas fa-building"></i>
                        <select name="sFLOOR" id="sidFLOOR" class="w3-select">
                            <option value="FLOOR-01">FLOOR-01</option>
                            <option value="FLOOR-02">FLOOR-02</option>
                            <option value="FLOOR-03">FLOOR-03</option>
                            <option value="FLOOR-04">FLOOR-04</option>
                            <option value="FLOOR-05">FLOOR-05</option>
                        </select>
                    </div>
                    <div class="select-box">
                        <i class="fas fa-code-branch"></i>
                        <select name="option" id="sidLINE" class="w3-select">
                            <option value="DEFAULT">DEFAULT</option>
                        </select>
                    </div>
                </div> -->

                <div class="btn-grid">
                    <button class="w3-button w3-teal" name="knitting_input" id="knitting_input"><i class="fas fa-sign-in-alt"></i> Knitting Input</button>
                    <button class="w3-button w3-teal" name="knitting_program" id="knitting_program"><i class="fas fa-tshirt"></i> Knitting Program</button>
                    <button class="w3-button w3-teal" name="knit_cards" id="knit_cards"><i class="fas fa-id-card"></i> Knit Cards</button>
                    <button class="w3-button w3-teal" name="knitting_store" id="knitting_store"><i class="fas fa-warehouse"></i> Knitting Store</button>
                    <button class="w3-button w3-teal" name="user_management" id="user_management"><i class="fas fa-user-plus"></i> User Management</button>
                    <button class="w3-button w3-teal" name="report" id="report"><i class="fas fa-file-alt"></i> Report</button>
                </div>

                <div class="btn-grid">
                    <!-- <button class="w3-button w3-teal" name="users" id="usersBtn"><i class="fas fa-users"></i> All Users</button>
                    <button class="w3-button w3-teal" name="users_update" id="updateUserBtn"><i class="fas fa-user-edit"></i> Update User</button>
                  -->
                    <button class="w3-button w3-black" name="uploadCSV" id="uploadCSV"><i class="fas fa-upload"></i> UPLOAD CSV</button>
                </div>
            </form>
        </div>
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
            var urTYP = document.getElementById('idUSR').innerText.trim().toLowerCase();
            var idFLR = document.getElementById('sidFLOOR');
            var idLNE = document.getElementById('sidLINE');

            // Button visibility based on user type
            $("#uploadCSV").hide();
            $("#user_management").hide();

            if (urTYP === "admin" || urTYP === "abuhena" || urTYP === "test" || urTYP === "ppq30" || urTYP === "ppq34" || urTYP === "ppq70") {
                // No direct action   just placeholder
            }

            if (urTYP === "ppq70") {
                $("#idAdjust").hide();
            }

            if (urTYP === "qms01") {
                $("#idBTS, #idBTF, #idBTP, #idChange, #idAdjust, #idExcel, #uploadCSV, #ztarget").hide();
                if (idFLR) idFLR.style.display = "none";
            }

            if (!isNaN(urTYP) && Number(urTYP) >= 1 && Number(urTYP) <= 52) {
                $("#idBTF, #idBTP, #idChange, #idAdjust, #idExcel, #uploadCSV, #user_management").hide();
                if (idFLR) idFLR.style.display = "none";
            }

            if (!isNaN(urTYP) && Number(urTYP) >= 101 && Number(urTYP) <= 999) {
                $("#idBTS, #idBTP, #idChange, #idAdjust, #idExcel, #uploadCSV").hide();
                if (idFLR) idFLR.style.display = "none";
            }

            if ((!isNaN(urTYP) && Number(urTYP) >= 1001) || urTYP === "f1" || urTYP === "f2" || urTYP === "f3" || urTYP === "f4") {
                $("#idBTS, #idBTF, #idChange, #idAdjust, #idExcel, #uploadCSV").hide();
                if (idLNE) idLNE.style.display = "none";
            }

            if (urTYP === "ppq29" || urTYP === "ppl04" || urTYP === "ppq28") {
                $("#idBTF, #idBTS, #idBTP, #idChange, #idAdjust, #idExcel, #uploadCSV").hide();
                if (idFLR) idFLR.style.display = "none";
            }

            if (urTYP === "ppq71") {
                $("#idBTF, #idBTS, #idChange, #idAdjust, #idExcel, #uploadCSV").hide();
                $("#idBTP").show();
                $("#sidFLOOR").show();
                $("#sidLINE").hide();
            }

            if (urTYP === "ppq30" || urTYP === "ppq34" || urTYP === "ppq70" || urTYP === "ppq57" || urTYP === "ppl04" || urTYP === "admin" || urTYP === "abuhena" || urTYP === "test" || urTYP === "mukul" || urTYP === "noman" || urTYP === "abcd" || urTYP === "hossain" || urTYP === "18003835") {
                $("#idExcel, #uploadCSV, #user_management, #usersBtn, #updateUserBtn").show();
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