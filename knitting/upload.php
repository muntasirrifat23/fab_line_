<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}
$uname = $_SESSION['username'];

include('config.php');

// CSV File path
$filepath = "./Knitting.csv";
$csv_exists = file_exists($filepath);
$import_logs = [];
$success = 0;
$fail = 0;
$csv_deleted = false;

/* ==========================
   Date Convert - FIXED for DDMMYYYY & DDMYYYY format
   ========================== */
function parseDate($raw)
{
    $raw = trim($raw);
    if ($raw == '') {
        return null;
    }
    
    // Remove everything after space or !
    $raw = preg_replace('/[ !].*$/', '', $raw);
    
    // Check if it's a date string
    if (preg_match('/^\d{7,8}$/', $raw)) {
        // For 7-digit dates like 1062026 (which is 01-06-2026)
        if (strlen($raw) == 7) {
            // Add leading zero to make it 8 digits
            // Example: 1062026 -> 01062026
            $raw = '0' . $raw;
        }
        
        // Now parse as DDMMYYYY
        $d = substr($raw, 0, 2);
        $m = substr($raw, 2, 2);
        $y = substr($raw, 4, 4);
        
        if (checkdate($m, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
        
        // If DDMMYYYY fails, try YYYYMMDD
        $y = substr($raw, 0, 4);
        $m = substr($raw, 4, 2);
        $d = substr($raw, 6, 2);
        
        if (checkdate($m, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
    }
    
    // Try other common formats
    $formats = [
        'd-m-Y',
        'd/m/Y',
        'Y-m-d',
        'Y/m/d',
        'dmY'
    ];
    
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $raw);
        if ($dt !== false) {
            return $dt->format('Y-m-d');
        }
    }
    
    return null;
}

if ($csv_exists) {
    $handle = fopen($filepath, "r");
    if ($handle) {
        /* ==========================
           Read Header
           ========================== */
        $header = fgetcsv($handle, 1000000, ",");
        $cleanHeader = [];

        foreach ($header as $h) {
            $h = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h);
            $h = strtoupper(trim($h));
            $h = str_replace([' ', '-', '/'], '_', $h);
            $cleanHeader[] = $h;
        }

        $H = array_flip($cleanHeader);

        /* ==========================
           CSV -> Database Map
           ========================== */
        $columnMap = [
            'SUPPLIER'              => 'SUPPLIER',
            'BUYER'                 => 'BUYER',
            'YARN_TYPE'             => 'YARN_TYPE',
            'YARN_COUNT'            => 'YARN_COUNT',
            'MC_DIA'                => 'MC_DIA',
            'FINISH_DIA'            => 'FINISH_DIA',
            'FABRICS_TYPE'          => 'FABRICS_TYPE',
            'BOOKING'               => 'BOOKING',
            'STYLE'                 => 'STYLE',
            'FINISH_GSM'            => 'FINISH_GSM',
            'OPEN_TUBE'             => 'OPEN_TUBE',
            'SONO'                  => 'SONO',
            'SO_ITEM'               => 'SO_ITEM',
            'KNIT_MATERIAL_CODE'    => 'KNIT_MATERIAL_CODE',
            'KNIT_M_DESCRIPTION'    => 'KNIT_M_DESCRIPTION',
            'ORDER_TYPE'            => 'ORDER_TYPE',
            'KNITTING_TARGET_QTY'   => 'KNITTING_TARGET_QTY',
            'FIRST_SHIPMENT_DATE'   => 'FIRST_SHIPMENT_DATE',
            'LAST_SHIPMENT_DATE'    => 'LAST_SHIPMENT_DATE',
            'KNIT_TNA_START'        => 'KNIT_TNA_START',
            'KNIT_TNA_END'          => 'KNIT_TNA_END',
            'LOT_NO'                => 'LOT_NO',
            'SL_VDQ'                => 'SL_VDQ'
        ];

        /* ==========================
           Prepare Insert - BUDAT (only date) & CBUDAT (with time)
           ========================== */
        $dbColumns = array_values($columnMap);
        $dbColumns[] = "BUDAT";   
        $dbColumns[] = "CBUDAT";  

        $sql = "INSERT INTO knitting_input (" . implode(",", $dbColumns) . ") VALUES (" . implode(",", array_fill(0, count($dbColumns), "?")) . ")";

        $stmt = $db->prepare($sql);
        if ($stmt) {
            $rowNo = 1;
            while (($row = fgetcsv($handle, 1000000, ",")) !== FALSE) {
                $rowNo++;
                $values = [];
                $types = "";

                foreach ($columnMap as $csv => $dbcol) {
                    $val = isset($H[$csv]) ? trim($row[$H[$csv]]) : null;
                    if ($val === '') {
                        $val = null;
                    }

                    // Convert dates
                    if (in_array($dbcol, ['FIRST_SHIPMENT_DATE', 'LAST_SHIPMENT_DATE', 'KNIT_TNA_START', 'KNIT_TNA_END'])) {
                        $val = parseDate($val);
                    }

                    $values[] = $val;
                    $types .= "s";
                }

                // Add BUDAT (without time)
                date_default_timezone_set('Asia/Dhaka');
                $values[] = date('Y-m-d');        
                $types .= "s";

                // Add CBUDAT (with time)
                $values[] = date('Y-m-d H:i:s');  
                $types .= "s";

                $stmt->bind_param($types, ...$values);

                if ($stmt->execute()) {
                    $success++;
                    $import_logs[] = [
                        'status' => 'success',
                        'message' => "Row " . $rowNo . ": Knitting Program Inserted"
                    ];
                } else {
                    $fail++;
                    $import_logs[] = [
                        'status' => 'fail',
                        'message' => "Row " . $rowNo . " Failed: " . $stmt->error
                    ];
                }
            }
            $stmt->close();
        } else {
            $import_logs[] = [
                'status' => 'fail',
                'message' => "Database prepare statement failed: " . $db->error
            ];
        }

        fclose($handle);

        /* ==========================
           Delete CSV after processing
           ========================== */
        if (file_exists($filepath)) {
            clearstatcache();
            $temp = $filepath . "_" . time() . ".tmp";
            if (@rename($filepath, $temp)) {
                if (@unlink($temp)) {
                    $csv_deleted = true;
                }
            }
        }
    }
}
mysqli_close($db);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV Knitting Programs | Purbani Fabrics</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-teal: #0f172a;
            --dark-teal: #0f172a;
            --surface-bg: #f8fafc;
            --card-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            --header-from: #090d22;
            --header-mid: #0f172a;
            --header-to: #1e3a8a;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        i, i.fa-solid, i.fas, i.far, i.fab, i.fa-regular {
            border: none !important; outline: none !important; box-shadow: none !important;
            padding: 0 !important; margin: 0 !important; display: inline-block !important; transform: none !important;
        }

        body {
            padding: 24px;
            background-color: var(--surface-bg);
            font-family: var(--font-main);
            color: #334155;
            background-image: radial-gradient(circle at 10% 20%, rgba(30, 58, 138, 0.015) 0%, transparent 60%),
                              radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.015) 0%, transparent 60%);
        }

        .top-banner {
            position: relative;
            background: linear-gradient(135deg, var(--header-from) 0%, var(--header-mid) 50%, var(--header-to) 100%);
            color: white;
            padding: 36px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .banner-inner {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .banner-title-group { display: flex; align-items: center; gap: 20px; }

        .banner-icon-wrap {
            width: 62px; height: 62px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
            backdrop-filter: blur(10px);
            color: #60a5fa;
        }

        .top-banner h1 {
            font-weight: 800;
            font-size: 2rem;
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 60%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .banner-subtitle {
            font-size: 14px;
            color: #93c5fd;
            margin: 0;
            font-weight: 500;
            opacity: 0.9;
        }

        .nav-btn {
            border-radius: 12px;
            font-weight: 700;
            font-size: 13.5px;
            padding: 10px 20px;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-glass {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            backdrop-filter: blur(10px);
        }
        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .content-panel {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            max-width: 800px;
            margin: 0 auto;
        }

        .log-console {
            background: #0f172a;
            color: #38bdf8;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            padding: 20px;
            border-radius: 16px;
            max-height: 350px;
            overflow-y: auto;
            border: 1px solid #1e293b;
            box-shadow: inset 0 4px 12px rgba(0,0,0,0.3);
            margin-bottom: 24px;
        }

        .log-line {
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .log-success { color: #34d399; }
        .log-error { color: #f87171; }

        .stat-badge {
            font-weight: 800;
            padding: 14px 24px;
            border-radius: 16px;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .stat-success { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .stat-danger { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

        .btn-blue-solid {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            font-weight: 700;
            border-radius: 12px;
            padding: 12px 28px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
            border: none;
        }
        .btn-blue-solid:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
            color: white;
        }

        .btn-outline-custom {
            border: 1px solid #cbd5e1;
            color: #475569;
            background: #ffffff;
            font-weight: 700;
            border-radius: 12px;
            padding: 12px 28px;
            transition: all 0.15s ease;
        }
        .btn-outline-custom:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
    </style>
</head>

<body>

    <div class="container-fluid" style="max-width: 1200px;">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="top-banner">
            <div class="banner-inner">
                <div class="banner-title-group">
                    <div class="banner-icon-wrap">
                        <i class="fa-solid fa-file-csv"></i>
                    </div>
                    <div>
                        <h1>CSV Knitting Import Panel</h1>
                        <p class="banner-subtitle">Batch-process external CSV records into target production database tables</p>
                    </div>
                </div>
                <div>
                    <a href="initialPage.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (!$csv_exists): ?>
            <!-- ═══ ERROR/WARNING: CSV NOT FOUND ═══ -->
            <div class="content-panel text-center py-5">
                <div class="mb-4">
                    <span class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 80px; height: 80px; border: 2px dashed #f59e0b;">
                        <i class="fa-solid fa-triangle-exclamation fa-3x"></i>
                    </span>
                </div>
                <h3 class="fw-extrabold text-dark mb-2" style="font-size: 24px;">Knitting.csv File Not Found</h3>
                <p class="text-secondary max-width-md mx-auto mb-4" style="font-size: 15px; max-width: 500px; line-height: 1.6;">
                    The import source file <code class="text-danger fw-bold bg-light px-2 py-1 rounded">Knitting.csv</code> was not detected in the project root directory. Please upload or move the CSV file to the root folder of the project to initialize the import process.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="initialPage.php" class="btn btn-outline-custom">
                        <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
                    </a>
                    <a href="knitting_program_list.php" class="btn btn-blue-solid">
                        <i class="fa-solid fa-list-check me-1"></i> View Knitting Programs
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- ═══ SUCCESS/IMPORT LOGS ═══ -->
            <div class="content-panel">
                <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-terminal me-2 text-primary"></i> Batch Import Processing Logs</h4>
                
                <div class="log-console">
                    <?php foreach ($import_logs as $log): ?>
                        <div class="log-line <?php echo $log['status'] === 'success' ? 'log-success' : 'log-error'; ?>">
                            <i class="fa-solid <?php echo $log['status'] === 'success' ? 'fa-check-circle me-1' : 'fa-circle-xmark me-1'; ?>"></i>
                            <?php echo htmlspecialchars($log['message']); ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($csv_deleted): ?>
                        <div class="log-line text-white mt-3">
                            <i class="fa-solid fa-trash-can me-1 text-danger"></i> <b>Knitting.csv deleted successfully after import completion.</b>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row align-items-center mb-4 g-3">
                    <div class="col-md-6 text-start">
                        <div class="d-inline-flex gap-3">
                            <div class="stat-badge stat-success">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Success: <strong><?php echo $success; ?></strong></span>
                            </div>
                            <div class="stat-badge stat-danger">
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Failed: <strong><?php echo $fail; ?></strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="text-secondary small fw-semibold">Processed <strong><?php echo ($success + $fail); ?></strong> total CSV records</span>
                    </div>
                </div>

                <hr class="my-4" style="border-color:#e2e8f0;">

                <div class="d-flex justify-content-end gap-3">
                    <a href="initialPage.php" class="btn btn-outline-custom">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <a href="knitting_program_list.php" class="btn btn-blue-solid">
                        <i class="fa-solid fa-list-check me-1"></i> Knitting Program Directory
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>