<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$uname = $_SESSION['username'];

// Search filter - single Knitting Program search
$search_program = isset($_GET['program_id']) ? trim($_GET['program_id']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
$search_term    = ltrim($search_program, '#');

// Build query calculating total carded quantity per program dynamically
$kc_prog_col = get_knit_card_program_col($db);
$query = "SELECT kp.*, 
                 MAX(kc.KCTID) AS card_id, 
                 COALESCE(SUM(kc.QTY), 0) AS total_carded_qty, 
                 MAX(kc.MCNO) AS card_mcno
          FROM knitting_program kp
          LEFT JOIN knit_card kc ON (kp.PROGRAM_NO = kc.{$kc_prog_col} OR kp.KPTID = kc.{$kc_prog_col})
          WHERE 1=1";
$params = [];
$types  = '';

if ($search_term !== '') {
    $query   .= " AND (kp.KPTID LIKE ? OR kp.PROGRAM_NO LIKE ? OR kp.PO_NUMBER LIKE ? OR kp.SONO LIKE ? OR kp.BUYER LIKE ? OR kp.STYLE LIKE ? OR kp.YBRAND LIKE ?)";
    $like_val = "%{$search_term}%";
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
    $types   .= 'sssssss';
}

$query .= " GROUP BY kp.KPTID ORDER BY kp.KPTID DESC";

$stmt = $db->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = false;
}

// Pagination setup
$limit        = 10; // records per page
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Helper function for pagination links
function get_page_url($p, $search_prog = '') {
    $params = ['page' => $p];
    if (!empty($search_prog)) {
        $params['program_id'] = $search_prog;
    }
    return 'knit_card.php?' . http_build_query($params);
}

// Summary stats & collect all matching rows
$total_programs  = 0;
$total_req_qty   = 0.00;
$generated_count = 0;
$pending_count   = 0;
$all_rows        = [];

if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $all_rows[] = $r;
        $total_programs++;
        $total_req_qty += (!empty($r['card_id']) && isset($r['card_req_qty'])) ? floatval($r['card_req_qty']) : floatval($r['QTY'] ?? 0);
        if (!empty($r['card_id'])) {
            $generated_count++;
        } else {
            $pending_count++;
        }
    }
}

$total_records = count($all_rows);
$total_pages   = max(1, ceil($total_records / $limit));
if ($current_page > $total_pages) {
    $current_page = $total_pages;
}
$offset      = ($current_page - 1) * $limit;
$rows_array  = array_slice($all_rows, $offset, $limit);
$start_entry = ($total_records > 0) ? $offset + 1 : 0;
$end_entry   = min($offset + $limit, $total_records);
?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knit Card | Purbani Fabrics</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-teal: #0f172a;
            --dark-teal: #0f172a;
            --accent-green: #10b981;
            --surface-bg: #f8fafc;
            --card-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            --header-from:  #090d22;
            --header-mid:   #0f172a;
            --header-to:    #1e3a8a;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        i, i.fa-solid, i.fas, i.far, i.fab, i.fa-regular {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            display: inline-block !important;
            transform: none !important;
        }

        body {
            padding: 10px 14px;
            background-color: var(--surface-bg);
            font-family: var(--font-main);
            color: #334155;
        }

        .top-banner {
            position: relative;
            background: linear-gradient(135deg, var(--header-from) 0%, var(--header-mid) 50%, var(--header-to) 100%);
            color: white;
            padding: 12px 18px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
            margin-bottom: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .top-banner::before {
            content: '';
            position: absolute;
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.18) 0%, transparent 70%);
            top: -60px; right: -40px;
            border-radius: 50%;
            pointer-events: none;
        }

        .banner-inner {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding-bottom: 0;
        }

        .banner-icon-wrap {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            color: #60a5fa;
        }

        .banner-title-group { display: flex; align-items: center; gap: 10px; }

        .top-banner h1 {
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            letter-spacing: -0.3px;
            line-height: 1.2;
            background: linear-gradient(135deg, #ffffff 60%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .banner-subtitle {
            font-size: 11.5px;
            color: #93c5fd;
            margin: 0;
            font-weight: 500;
            opacity: 0.9;
        }

        .nav-btn {
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            padding: 5px 12px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-glass {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #f8fafc;
        }
        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        /* ═══════════════════════════════════════════
           STAT CARDS
        ═══════════════════════════════════════════ */
        .stat-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 10px 14px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .stat-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .bg-teal-light  { background: #eff6ff; color: #1d4ed8; }
        .bg-blue-light  { background: #f0f9ff; color: #0284c7; }
        .bg-green-light { background: #f0fdf4; color: #166534; }
        .bg-amber-light { background: #fffbeb; color: #b45309; }

        .stat-lbl { font-size: 11px; font-weight: 600; color: #64748b; }
        .stat-val { font-size: 1.15rem; font-weight: 700; line-height: 1.2; }

        /* ═══════════════════════════════════════════
           SEARCH & FILTER PANEL
        ═══════════════════════════════════════════ */
        .search-panel {
            background: #ffffff;
            border-radius: 10px;
            padding: 8px 12px !important;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            margin-bottom: 12px;
        }

        .search-panel .form-control {
            height: 34px !important;
            padding: 4px 12px !important;
            font-size: 12.5px !important;
            border-color: #cbd5e1;
            border-radius: 0 8px 8px 0 !important;
            background-color: #f8fafc;
        }
        
        .search-panel .btn {
            height: 34px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 8px !important;
            font-weight: 600;
            font-size: 12px;
            padding: 0 14px !important;
        }

        /* ═══════════════════════════════════════════
           TABLES
        ═══════════════════════════════════════════ */
        .table-panel {
            background: #ffffff;
            border-radius: 10px;
            padding: 10px 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .custom-table thead th {
            background: #0f172a;
            color: #f8fafc;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border: none;
            border-bottom: 2px solid #1e293b;
            text-align: center;
        }
        .custom-table thead th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .custom-table thead th:last-child  { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
        
        .custom-table tbody td { 
            padding: 6px 8px; 
            font-size: 12px; 
            vertical-align: middle; 
            border-bottom: 1px solid #f1f5f9; 
            color: #334155;
            font-weight: 500;
            text-align: center;
        }
        .custom-table tbody tr:hover { 
            background-color: #f8fafc; 
        }

        .badge-status {
            font-size: 10.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .badge-generated { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-pending   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .btn-teal {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 6px !important;
            padding: 4px 10px !important;
            font-size: 11.5px !important;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
        }
        .btn-teal:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
        }

        .btn-action-view {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white !important;
            font-weight: 600;
            border-radius: 6px !important;
            padding: 4px 10px !important;
            border: none;
            font-size: 11.5px !important;
            transition: all 0.2s ease;
        }
        .btn-action-view:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        }

        .btn-action-edit {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: white !important;
            font-weight: 600;
            border-radius: 6px !important;
            padding: 4px 10px !important;
            border: none;
            font-size: 11.5px !important;
            transition: all 0.2s ease;
        }
        .btn-action-edit:hover {
            background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
        }

        .btn-action-download {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white !important;
            font-weight: 600;
            border-radius: 6px !important;
            padding: 4px 10px !important;
            border: none;
            font-size: 11.5px !important;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .btn-action-download:hover {
            background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
        }

        /* ═══════════════════════════════════════════
           PAGINATION
        ═══════════════════════════════════════════ */
        .pagination-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 8px;
            margin-top: 6px;
            border-top: 1px solid #f1f5f9;
        }
        .pagination-info {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        .custom-pagination {
            display: inline-flex;
            gap: 4px;
            list-style: none;
            padding: 0;
            margin: 0;
            align-items: center;
        }
        .custom-pagination .page-link-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .custom-pagination .page-link-custom:hover:not(.disabled):not(.active) {
            background: #f1f5f9;
            color: #0f172a;
        }
        .custom-pagination .page-item-custom.active .page-link-custom {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }
        .custom-pagination .page-item-custom.disabled .page-link-custom {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #f1f5f9;
            cursor: not-allowed;
            pointer-events: none;
            opacity: 0.6;
        }
        .custom-pagination .page-ellipsis {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 28px;
            color: #94a3b8;
            font-size: 12px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
        }
    </style>
</head>

<body>

    <div class="container-fluid" style="max-width: 1600px;">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="top-banner">
            <div class="banner-inner">
                <!-- Left: icon + title -->
                <div class="banner-title-group">
                    <div class="banner-icon-wrap">
                        <i class="fa-solid fa-tag"></i>
                    </div>
                    <div>
                        <h1>Knit Card</h1>
                        <p class="banner-subtitle">Manage production knitting programs, track quantities, and generate production Knit Cards</p>
                    </div>
                </div>
                <!-- Right: action buttons -->
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="initialPage.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-arrow-left"></i> Dashboard
                    </a>
                    <!--
                    <a href="knitting_program_form.php" class="btn nav-btn btn-blue-solid">
                        <i class="fa-solid fa-plus"></i> New Knit Card
                    </a>
                    -->
                    <a href="knit_card_report.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-id-card"></i> All Knit Cards
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 p-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 p-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="row g-2 mb-2.5">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon bg-teal-light"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div>
                        <div class="stat-lbl">Total Programs</div>
                        <div class="stat-val text-dark"><?php echo number_format($total_programs); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon bg-blue-light"><i class="fa-solid fa-weight-hanging"></i></div>
                    <div>
                        <div class="stat-lbl">Total Required Qty</div>
                        <div class="stat-val text-dark"><?php echo number_format($total_req_qty, 2); ?> <span style="font-size:11px; font-weight:normal; color:#64748b;">KG</span></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon bg-green-light"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div class="stat-lbl">Cards Generated</div>
                        <div class="stat-val text-success"><?php echo number_format($generated_count); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon bg-amber-light"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <div class="stat-lbl">Pending Cards</div>
                        <div class="stat-val" style="color:#b45309;"><?php echo number_format($pending_count); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="search-panel">
            <form method="GET" action="knit_card.php" class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 w-100">
                <div class="input-group flex-grow-1" style="min-width: 200px;">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="fa-solid fa-magnifying-glass text-primary"></i>
                    </span>
                    <input type="text" 
                           name="program_id" 
                           class="form-control border-start-0" 
                           placeholder="Search by Knitting Program (e.g. 57 or 2000000004)..." 
                           value="<?php echo htmlspecialchars($search_program); ?>"
                           autofocus>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button type="submit" class="btn btn-teal flex-grow-1 flex-sm-grow-0" style="white-space: nowrap;">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Search
                    </button>
                    <a href="knit_card.php" class="btn btn-outline-secondary flex-grow-1 flex-sm-grow-0" style="white-space: nowrap;">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="table-panel">
            <div class="table-responsive">
                <table class="table custom-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="text-nowrap">Date</th>
                            <th class="text-nowrap">Program No</th>
                            <th class="text-nowrap">PO</th>
                            <th class="text-nowrap">SONO</th>
                            <th class="text-nowrap">Buyer</th>
                            <th class="text-nowrap">Customer</th>
                            <th class="text-nowrap">Style</th>
                            <th class="text-nowrap">Color</th>
                            <th class="text-nowrap">Fabrics Type</th>
                            <th class="text-nowrap">Yarn Type</th>
                            <th class="text-nowrap">Yarn Count</th>
                            <th class="text-nowrap">Brand</th>
                            <th class="text-nowrap">Lot No</th>
                            <th class="text-nowrap">O/T</th>
                            <th class="text-nowrap">Dia</th>
                            <th class="text-nowrap">GSM</th>
                            <th class="text-nowrap">SL/VDQ</th>
                            <th class="text-nowrap">Feeder Plan</th>
                            <th class="text-nowrap">Gray GSM</th>
                            <th class="text-nowrap">Qty (KG)</th>
                            <th class="text-nowrap">Card Status</th>
                            <th class="text-center text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows_array) > 0): ?>
                            <?php foreach ($rows_array as $row):
                                $p_id          = intval($row['KPTID']);
                                $p_date        = !empty($row['CREATED_DATE']) ? date('Y-m-d', strtotime($row['CREATED_DATE'])) : '';
                                $p_prog        = !empty($row['PROGRAM_NO']) ? $row['PROGRAM_NO'] : (!empty($row['SUB_TID']) ? $row['SUB_TID'] : ($row['KPTID'] ?? ''));
                                $p_po          = $row['PO_NUMBER']   ?? '';
                                $p_sono        = $row['SONO']        ?? '';
                                $p_buyer       = $row['BUYER']       ?? '';
                                $p_style       = $row['STYLE']       ?? '';
                                $p_color       = $row['COLOR']       ?? '';
                                $p_customer    = $row['CUSTOMER']    ?? '';
                                $p_ftype       = $row['FTYPE']       ?? '';
                                $p_ytype       = $row['YTYPE']       ?? '';
                                $p_ycount      = $row['YCOUNT']      ?? '';
                                $p_ybrand      = $row['YBRAND']      ?? '';
                                $p_lot         = $row['LOT']         ?? '';
                                $p_ot          = $row['O_T']         ?? '';
                                $p_dia         = !empty($row['MCDIA']) ? $row['MCDIA'] : ($row['FDIA'] ?? '');
                                $p_sl          = $row['SL']          ?? '';
                                $p_fgsm        = $row['FGSM']        ?? '';
                                $p_feeder_plan = $row['FEEDER_PLAN'] ?? '';
                                $p_ggsm        = $row['GGSM']        ?? '';
                                $p_card_gen    = !empty($row['card_id']) ? 1 : 0;
                                $p_card_id     = $row['card_id'] ?? '';
                            ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <i class="fa-regular fa-calendar me-1 text-muted"></i>
                                        <?php echo htmlspecialchars($p_date); ?>
                                    </td>
                                    <td class="text-nowrap"><strong><?php echo htmlspecialchars($p_prog); ?></strong></td>
                                    <td class="text-nowrap"><strong><?php echo htmlspecialchars($p_po ?: 'N/A'); ?></strong></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_sono ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><strong><?php echo htmlspecialchars($p_buyer ?: 'N/A'); ?></strong></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_customer ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_style ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_color ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_ftype ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_ytype ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_ycount ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_ybrand ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_lot ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_ot ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_dia ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_fgsm ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_sl ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_feeder_plan ?: 'N/A'); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($p_ggsm ?: 'N/A'); ?></td>
                                    <td class="text-nowrap">
                                        <?php
                                            $prog_total_qty = floatval($row['QTY'] ?? 0);
                                            $prog_carded    = floatval($row['total_carded_qty'] ?? 0);
                                            $prog_rem       = max(0.00, $prog_total_qty - $prog_carded);
                                        ?>
                                        <div style="line-height:1.6; font-size:12.5px;">
                                            <div><span style="color:#64748b; font-weight:600;">Req:</span> <strong><?php echo number_format($prog_total_qty, 0); ?> KG</strong></div>
                                            <div><span style="color:#059669; font-weight:600;">Carded:</span> <strong style="color:#059669;"><?php echo number_format($prog_carded, 0); ?> KG</strong></div>
                                            <div><span style="color:#d97706; font-weight:600;">Rem:</span> <strong style="color:#d97706;"><?php echo number_format($prog_rem, 0); ?> KG</strong></div>
                                        </div>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($prog_carded <= 0): ?>
                                            <span class="badge-status badge-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                                        <?php elseif ($prog_rem > 0.001): ?>
                                            <span class="badge-status" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;"><i class="fa-solid fa-spinner"></i> Partial</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-generated"><i class="fa-solid fa-circle-check"></i> Completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="d-inline-flex gap-2">
                                            <?php if ($prog_rem > 0.001): ?>
                                                <a href="knit_card_generate.php?program_id=<?php echo $p_id; ?>"
                                                   class="btn btn-sm btn-teal"
                                                   style="border-radius:10px; font-size:12.5px;"
                                                   title="Generate Knit Card (Remaining: <?php echo number_format($prog_rem, 2); ?> KG)">
                                                    <i class="fa-solid fa-file-circle-plus me-1"></i> Generate Card
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!empty($p_card_id)): ?>
                                                <a href="knit_card_view.php?id=<?php echo intval($p_card_id); ?>"
                                                   class="btn btn-sm btn-action-view"
                                                   title="View Latest Generated Card">
                                                    <i class="fa-solid fa-eye me-1"></i> View Card
                                                </a>
                                                <a href="knit_card_view.php?id=<?php echo intval($p_card_id); ?>&download=1"
                                                   class="btn btn-sm btn-action-download"
                                                   title="Download PDF Card">
                                                    <i class="fa-solid fa-download me-1"></i> Download
                                                </a>
                                            <?php endif; ?>

                                            <a href="knitting_program_form.php?id=<?php echo $p_id; ?>"
                                               class="btn btn-sm btn-action-edit"
                                               title="Edit Program">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="22" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                                    <h6 class="fw-bold">No Knitting Programs Found</h6>
                                    <p class="small mb-0">Try adjusting your filters or click "New Program" to add an entry.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ═══ PAGINATION COMPONENT ═══ -->
            <?php if ($total_pages > 1 || $total_records > 0): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <span class="fw-bold text-dark"><?php echo $start_entry; ?></span> to <span class="fw-bold text-dark"><?php echo $end_entry; ?></span> of <span class="fw-bold text-dark"><?php echo number_format($total_records); ?></span> entries
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <ul class="custom-pagination">
                            <!-- Previous Page Button -->
                            <li class="page-item-custom <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link-custom" href="<?php echo get_page_url($current_page - 1, $search_program); ?>" aria-label="Previous" title="Previous Page">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            </li>

                            <?php
                            $range      = 2;
                            $show_start = max(1, $current_page - $range);
                            $show_end   = min($total_pages, $current_page + $range);

                            // First page + ellipsis
                            if ($show_start > 1) {
                                echo '<li class="page-item-custom"><a class="page-link-custom" href="' . get_page_url(1, $search_program) . '">1</a></li>';
                                if ($show_start > 2) {
                                    echo '<li class="page-ellipsis">&hellip;</li>';
                                }
                            }

                            // Middle page numbers
                            for ($p = $show_start; $p <= $show_end; $p++) {
                                $active_cls = ($p === $current_page) ? 'active' : '';
                                echo '<li class="page-item-custom ' . $active_cls . '"><a class="page-link-custom" href="' . get_page_url($p, $search_program) . '">' . $p . '</a></li>';
                            }

                            // Last page + ellipsis
                            if ($show_end < $total_pages) {
                                if ($show_end < $total_pages - 1) {
                                    echo '<li class="page-ellipsis">&hellip;</li>';
                                }
                                echo '<li class="page-item-custom"><a class="page-link-custom" href="' . get_page_url($total_pages, $search_program) . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <!-- Next Page Button -->
                            <li class="page-item-custom <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link-custom" href="<?php echo get_page_url($current_page + 1, $search_program); ?>" aria-label="Next" title="Next Page">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>
