<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

// Check if user has permission to access this page
$uname = $_SESSION['username'];
$allowedUsers = ['admin', 'abuhena', 'test', 'ppq30', 'ppq34', 'ppq70', 'ppq57', 'ppl04'];
if (!in_array($uname, $allowedUsers)) {
    echo "<script>alert('Access denied: You do not have permission to view this page'); window.location.href='initialPage.php';</script>";
    exit();
}

// Fetch all users from database
$query = "SELECT id, username, email, password, production_time FROM users_update ORDER BY id DESC";
$result = mysqli_query($db, $query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($db));
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>users Update Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/w3.css">
    <link rel="stylesheet" type="text/css" href="css/mycss.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- QRCode.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --primary-1: #00796b;
            --primary-2: #26a69a;
            --primary-gradient: linear-gradient(135deg, #00796b, #26a69a, #4db6ac);
            --accent: #ff6f60;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 12px 32px rgba(0, 0, 0, 0.12);
            --card-bg: rgba(255, 255, 255, 0.95);
            --glass-border: 1px solid rgba(255, 255, 255, 0.3);
        }

        * {
            transition: all 0.2s ease;
        }

        body {
            background: linear-gradient(145deg, #dceaf5 0%, #e9f2f9 100%);
            font-family: 'Poppins', 'Segoe UI', system-ui, 'Inter', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .container-centered {
            max-width: 1400px;
            margin: 0 auto;
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

        .page-header h1 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        .page-header i {
            margin-right: 10px;
        }

        .glass-panel {
            background: var(--card-bg);
            backdrop-filter: blur(2px);
            border-radius: 36px;
            padding: 24px 28px;
            box-shadow: var(--shadow-lg);
            border: var(--glass-border);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            min-width: 800px;
        }

        .user-table thead {
            background: linear-gradient(95deg, #00796b, #26a69a);
            color: white;
        }

        .user-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .user-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .user-table tbody tr:hover {
            background: #f5f5f5;
            transition: background 0.3s ease;
        }

        .user-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .user-table tbody tr:nth-child(even):hover {
            background: #f0f0f0;
        }

        .user-id {
            font-weight: 600;
            color: #00796b;
        }

        .user-username {
            font-weight: 600;
            color: #1a1a1a;
        }

        .user-email {
            color: #555;
        }

        .user-password {
            font-family: 'Courier New', monospace;
            color: #666;
            font-size: 0.85rem;
        }

        .user-production_time {
            color: #555;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .btn-back {
            background: linear-gradient(95deg, #2c3e4e, #1e2f3a);
            color: white;
            padding: 14px 28px;
            border-radius: 20px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: white;
            text-decoration: none;
        }

        .btn-back i {
            font-size: 1.2rem;
        }

        .stats-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .stats-count {
            font-size: 1.1rem;
            color: #333;
            font-weight: 600;
        }

        .stats-count span {
            color: #00796b;
            font-size: 1.3rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #bdbdbd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 10px;
        }

        /* QR Code Button Styles */
        .btn-qr {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            min-width: 80px;
        }

        .btn-qr-scan {
            background: linear-gradient(95deg, #00796b, #26a69a);
            color: white;
        }

        .btn-qr-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 121, 107, 0.3);
        }

        .btn-qr-print {
            background: linear-gradient(95deg, #e53935, #ff6f60);
            color: white;
        }

        .btn-qr-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(229, 57, 53, 0.3);
        }

        .action-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* Modal Styles */
        .qr-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .qr-modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 24px;
            max-width: 550px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .qr-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .qr-modal-header h2 {
            margin: 0;
            color: #00796b;
            font-size: 1.5rem;
        }

        .qr-modal-close {
            background: #e53935;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-modal-close:hover {
            background: #c62828;
            transform: rotate(90deg);
        }

        .qr-code-container {
            text-align: center;
            padding: 20px 0;
        }

        #qrcode {
            display: inline-block;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Clean User Info Display */
        .qr-user-info {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #00796b;
        }

        .qr-user-info .info-item {
            display: flex;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .qr-user-info .info-item:last-child {
            border-bottom: none;
        }

        .qr-user-info .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 100px;
            font-size: 0.95rem;
        }

        .qr-user-info .info-value {
            color: #212529;
            font-size: 0.95rem;
            word-break: break-all;
            flex: 1;
        }

        /* Action Buttons in Modal */
        .qr-action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-cancel {
            background: linear-gradient(95deg, #c62828, #e53935);
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-cancel:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(198, 40, 40, 0.4);
        }

        .qr-print-btn {
            background: linear-gradient(95deg, #2c3e4e, #1e2f3a);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .qr-print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Print-specific styles */
        @media print {
            body * {
                visibility: hidden;
            }

            .qr-modal,
            .qr-modal * {
                visibility: visible;
            }

            .qr-modal {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background: white !important;
                backdrop-filter: none !important;
                display: block !important;
                z-index: 9999 !important;
            }

            .qr-modal-content {
                margin: 0 auto !important;
                padding: 20px !important;
                max-width: 400px !important;
                box-shadow: none !important;
                border: 2px solid #ddd !important;
                border-radius: 12px !important;
                background: white !important;
                position: relative !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }

            .qr-modal-header,
            .qr-modal-close,
            .qr-print-btn,
            .qr-action-buttons {
                display: none !important;
            }

            #qrcode {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                padding: 15px !important;
            }

            .qr-user-info {
                background: #f8f9fa !important;
                border: 1px solid #e9ecef !important;
            }

            .btn-back,
            .stats-bar .stats-count,
            .btn-qr,
            .page-header::after {
                display: none !important;
            }

            .glass-panel {
                box-shadow: none !important;
                border: none !important;
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.4rem;
            }

            .glass-panel {
                padding: 16px 18px;
            }

            .btn-back {
                padding: 12px 20px;
                font-size: 0.9rem;
            }

            .user-table {
                font-size: 0.85rem;
                min-width: 700px;
            }

            .user-table th,
            .user-table td {
                padding: 10px 8px;
            }

            .action-cell {
                flex-direction: column;
                gap: 5px;
            }

            .btn-qr {
                width: 100%;
                min-width: unset;
                padding: 6px 12px;
                font-size: 0.75rem;
            }

            .qr-modal-content {
                margin: 10% auto;
                padding: 20px;
            }

            .qr-user-info .info-label {
                min-width: 80px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .stats-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-back {
                width: 100%;
                justify-content: center;
            }

            .user-table {
                font-size: 0.75rem;
                min-width: 600px;
            }

            .user-table th,
            .user-table td {
                padding: 6px 4px;
            }
        }

        /* Scrollbar styling */
        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #00796b;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #004d40;
        }
    </style>
</head>

<body>

    <div class="container-centered">
        <div class="page-header">
            <h1> Update Users</h1>
        </div>

        <div class="glass-panel">
            <div class="stats-bar">
                <a href="initialPage.php" class="btn-back">
                    Back to Initial Page
                </a>
                <div class="stats-count">
                    Total Update Users: <span><?php echo mysqli_num_rows($result); ?></span>
                </div>
            </div>

            <div class="table-container">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Password</th>
                                <th>Time</th>
                                <th>Scan QR</th>
                                <th>Print QR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="user-id"><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td class="user-username">
                                        <?php echo htmlspecialchars($row['username']); ?>
                                    </td>
                                    <td class="user-email">
                                        <?php echo htmlspecialchars($row['email']); ?>
                                    </td>
                                    <td class="user-password">
                                        <?php echo htmlspecialchars($row['password']); ?>
                                    </td>
                                    <td class="user-production_time">
                                        <?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($row['production_time']))); ?>
                                    </td>
                                    <td>
                                        <button class="btn-qr btn-qr-scan" onclick="scanQR(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                            Scan QR
                                        </button>
                                    </td>
                                    <td>
                                        <button class="btn-qr btn-qr-print" onclick="printQR(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                            Print QR
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Update Users Found</h3>
                        <p>There are no update users registered in the system yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="qr-modal">
        <div class="qr-modal-content">
            <div class="qr-modal-header">
                <h2> User QR Code</h2>
                <button class="qr-modal-close" onclick="closeQRModal()">&times;</button>
            </div>
            <div class="qr-code-container">
                <div id="qrcode"></div>
            </div>
            <div class="qr-user-info" id="qrUserInfo">
                <div class="info-item">
                    <span class="info-label"> Username:</span>
                    <span class="info-value" id="qrUsername">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label"> Email:</span>
                    <span class="info-value" id="qrEmail">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label"></i> Password:</span>
                    <span class="info-value" id="qrPassword">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label"> production_time:</span>
                    <span class="info-value" id="qrproduction_time">-</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 10px;">
                <button class="qr-print-btn" onclick="printQRCode()">
                    Print QR Code
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentQRData = null;
        let qrCodeInstance = null;

        function scanQR(userData) {
            currentQRData = userData;

            // Create formatted text for QR code
            const qrText = `Username: ${userData.username}\nEmail: ${userData.email}\nPassword: ${userData.password}\nproduction_time: ${formatDate(userData.production_time)}`;

            // Update user info in modal
            document.getElementById('qrUsername').textContent = userData.username;
            document.getElementById('qrEmail').textContent = userData.email;
            document.getElementById('qrPassword').textContent = userData.password;
            document.getElementById('qrproduction_time').textContent = formatDate(userData.production_time);

            // Generate new QR code with formatted text
            qrCodeInstance = new QRCode(document.getElementById('qrcode'), {
                text: qrText,
                width: 200,
                height: 200,
                colorDark: "#00796b",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Show modal
            document.getElementById('qrModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function printQR(userData) {
            currentQRData = userData;

            // Create formatted text for QR code
            const qrText = `Username: ${userData.username}\nEmail: ${userData.email}\nPassword: ${userData.password}\nproduction_time: ${formatDate(userData.production_time)}`;

            // Update user info in modal
            document.getElementById('qrUsername').textContent = userData.username;
            document.getElementById('qrEmail').textContent = userData.email;
            document.getElementById('qrPassword').textContent = userData.password;
            document.getElementById('qrproduction_time').textContent = formatDate(userData.production_time);

            // Generate new QR code with formatted text
            qrCodeInstance = new QRCode(document.getElementById('qrcode'), {
                text: qrText,
                width: 250,
                height: 250,
                colorDark: "#00796b",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Show modal
            document.getElementById('qrModal').style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Automatically print after a short delay
            setTimeout(function() {
                printQRCode();
            }, 500);
        }

        function printQRCode() {
            window.print();
        }

        function closeQRModal() {
            document.getElementById('qrModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            if (qrCodeInstance) {
                document.getElementById('qrcode').innerHTML = '';
                qrCodeInstance = null;
            }
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return `${day}/${month}/${year}, ${hours}:${minutes} ${ampm}`;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('qrModal');
            if (event.target === modal) {
                closeQRModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeQRModal();
            }
        });
    </script>

</body>

</html>
<?php
// Close database connection
mysqli_close($db);
?>