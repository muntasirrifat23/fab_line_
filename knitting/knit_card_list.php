<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$buyer_filter  = isset($_GET['buyer'])      ? trim($_GET['buyer'])      : '';
$mc_no_filter  = isset($_GET['mc_no'])      ? trim($_GET['mc_no'])      : '';
$start_date    = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date      = isset($_GET['end_date'])   ? trim($_GET['end_date'])   : '';

// Build query using real column names
$query  = "SELECT kc.*, kp.BOOKING AS kp_booking
           FROM knit_card kc
           LEFT JOIN knitting_program kp ON kc.KPTID = kp.KPTID
           WHERE 1=1";
$params = [];
$types  = '';

if ($buyer_filter !== '') {
    $query   .= " AND kc.BUYER LIKE ?";
    $params[] = "%{$buyer_filter}%";
    $types   .= 's';
}
if ($mc_no_filter !== '') {
    $query   .= " AND kc.MCNO LIKE ?";
    $params[] = "%{$mc_no_filter}%";
    $types   .= 's';
}
if ($start_date !== '') {
    $query   .= " AND kc.CARD_DATE >= ?";
    $params[] = $start_date;
    $types   .= 's';
}
if ($end_date !== '') {
    $query   .= " AND kc.CARD_DATE <= ?";
    $params[] = $end_date;
    $types   .= 's';
}

$query .= " ORDER BY kc.KCID DESC";

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

$total_cards = 0;
$total_qty   = 0.00;
$buyers_set  = [];
$mc_set      = [];
$rows_array  = [];

if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $rows_array[]   = $r;
        $total_cards++;
        $total_qty     += floatval($r['REQ_QTY'] ?? 0);
        if (!empty($r['BUYER'])) $buyers_set[$r['BUYER']] = true;
        if (!empty($r['MCNO']))  $mc_set[$r['MCNO']]      = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knit Cards Directory | Purbani Fabrics</title>

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
            --card-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            --header-from:  #090d22;
            --header-mid:   #0f172a;
            --header-to:    #1e3a8a;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        i, i.fa-solid, i.fas, i.far, i.fab {
            border: none !important; outline: none !important; box-shadow: none !important;
            padding: 0 !important; display: inline-block !important; transform: none !important;
        }

        body {
            padding: 24px;
            background-color: var(--surface-bg);
            font-family: var(--font-main);
            color: #334155;
            background-image: radial-gradient(circle at 10% 20%, rgba(30, 58, 138, 0.015) 0%, transparent 60%),
                              radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.015) 0%, transparent 60%);
        }

        /* ═══════════════════════════════════════════
           HEADER BANNER
        ═══════════════════════════════════════════ */
        .top-banner {
            position: relative;
            background: linear-gradient(135deg, var(--header-from) 0%, var(--header-mid) 50%, var(--header-to) 100%);
            color: white;
            padding: 36px 40px 0 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Decorative background blobs */
        .top-banner::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.22) 0%, transparent 70%);
            top: -120px; right: -80px;
            border-radius: 50%;
            pointer-events: none;
        }
        .top-banner::after {
            content: '';
            position: absolute;
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(147, 197, 253, 0.15) 0%, transparent 70%);
            bottom: -20px; left: 80px;
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
            gap: 20px;
            padding-bottom: 30px;
        }

        /* Icon badge */
        .banner-icon-wrap {
            width: 62px; height: 62px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            color: #60a5fa;
        }

        .banner-title-group { display: flex; align-items: center; gap: 20px; }

        .top-banner h1 {
            font-weight: 800;
            font-size: 2rem;
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
            line-height: 1.15;
            background: linear-gradient(135deg, #ffffff 60%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .banner-subtitle {
            font-size: 14px;
            color: #93c5fd;
            margin: 0;
            font-weight: 500;
            letter-spacing: 0.1px;
            opacity: 0.9;
        }

        /* Nav buttons */
        .nav-btn {
            border-radius: 12px;
            font-weight: 700;
            font-size: 13.5px;
            padding: 10px 20px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
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
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-blue-solid {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: 1px solid rgba(96, 165, 250, 0.3);
            color: white;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }
        .btn-blue-solid:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
        }

        /* Bottom glassy strip inside header */
        .banner-info-strip {
            position: relative;
            z-index: 2;
            background: rgba(15, 23, 42, 0.25);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin: 0 -40px;
            padding: 14px 40px;
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .strip-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #e2e8f0;
            font-weight: 600;
        }

        .strip-item .strip-dot {
            width: 9px; height: 9px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px currentColor;
        }

        /* ═══════════════════════════════════════════
           STAT CARDS
        ═══════════════════════════════════════════ */
        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 22px 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }

        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.02);
        }
        .stat-icon.navy   { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; }
        .stat-icon.sky    { background: linear-gradient(135deg, #f0f9ff, #e0f2fe); color: #0284c7; }
        .stat-icon.royal  { background: linear-gradient(135deg, #f5f3ff, #ede9fe); color: #6d28d9; }
        .stat-icon.cobalt { background: linear-gradient(135deg, #fef2f2, #fee2e2); color: #dc2626; }

        .stat-label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; margin: 0; }
        .stat-value { font-size: 26px; font-weight: 800; color: #0f172a; margin: 4px 0 0; letter-spacing: -1px; line-height: 1; }

        /* ═══════════════════════════════════════════
           FILTER PANEL
        ═══════════════════════════════════════════ */
        .filter-panel {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            margin-bottom: 28px;
        }

        .filter-panel h6 {
            font-size: 14.5px;
            letter-spacing: -0.2px;
            color: #0f172a;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            font-size: 14px;
            color: #0f172a;
            font-weight: 500;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            background-color: #ffffff;
        }

        /* Specific rules for filter form controls to align perfectly with button height */
        .filter-panel .form-control, .filter-panel .form-select, .filter-panel .input-group-text {
            height: 42px !important;
            padding: 8px 14px !important;
            font-size: 13.5px !important;
            border-color: #cbd5e1;
        }
        .filter-panel .input-group-text {
            border-radius: 12px 0 0 12px !important;
            background-color: #f1f5f9 !important;
            border-right: none;
            color: #64748b;
        }
        .filter-panel .input-group .form-control {
            border-radius: 0 12px 12px 0 !important;
            border-left: none;
        }
        .filter-panel .btn {
            height: 42px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 12px !important;
            font-weight: 700;
            font-size: 13.5px;
        }

        /* ═══════════════════════════════════════════
           TABLE
        ═══════════════════════════════════════════ */
        .content-panel {
            background: #ffffff;
            border-radius: 24px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .custom-table thead th {
            background: #0f172a;
            color: #f8fafc;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 16px;
            border: none;
            border-bottom: 2px solid #1e293b;
        }
        .custom-table thead th:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
        .custom-table thead th:last-child  { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }
        
        .custom-table tbody td { 
            padding: 16px; 
            font-size: 14px; 
            vertical-align: middle; 
            border-bottom: 1px solid #f1f5f9; 
            color: #334155;
            font-weight: 500;
        }
        .custom-table tbody tr {
            transition: all 0.2s ease;
        }
        .custom-table tbody tr:hover { 
            background-color: #f8fafc; 
            transform: scale(1.002);
        }

        .badge-kc    { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1e40af; font-weight: 700; font-size: 12.5px; padding: 6px 14px; border-radius: 20px; display: inline-block; border: 1px solid #bfdbfe; }
        .badge-mc    { background: #f1f5f9; color: #334155; font-weight: 700; font-size: 12.5px; padding: 5px 12px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .badge-buyer { color: #0f172a; font-weight: 700; }

        /* Action buttons */
        .btn-action-view {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white !important;
            font-weight: 700;
            border-radius: 10px !important;
            padding: 8px 16px !important;
            border: none;
            font-size: 13px !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            transition: all 0.2s ease;
        }
        .btn-action-view:hover {
            background: linear-gradient(135deg, #3d75f5 0%, #2563eb 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }

        .btn-action-print {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white !important;
            font-weight: 700;
            border-radius: 10px !important;
            padding: 8px 16px !important;
            border: none;
            font-size: 13px !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
        }
        .btn-action-print:hover {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        }
    </style>
</head>

<body>

    <div class="container-fluid" style="max-width: 1400px;">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="top-banner">
            <div class="banner-inner">
                <!-- Left: icon + title -->
                <div class="banner-title-group">
                    <div class="banner-icon-wrap">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <h1>Knit Cards Directory</h1>
                        <p class="banner-subtitle">Digital production cards generated from knitting programs — Purbani Fabrics Ltd.</p>
                    </div>
                </div>
                <!-- Right: nav buttons -->
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="initialPage.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <a href="knitting_program_list.php" class="btn nav-btn btn-blue-solid">
                        <i class="fa-solid fa-list-check"></i> Knitting Programs
                    </a>
                </div>
            </div>

            <!-- Glassy info strip at the bottom of the header -->
            <div class="banner-info-strip">
                <div class="strip-item">
                    <span class="strip-dot" style="background:#60a5fa;"></span>
                    <span>Total Cards: <strong style="color:#fff;"><?php echo $total_cards; ?></strong></span>
                </div>
                <div class="strip-item">
                    <span class="strip-dot" style="background:#7dd3fc;"></span>
                    <span>Total Req. Qty: <strong style="color:#fff;"><?php echo number_format($total_qty, 2); ?> KG</strong></span>
                </div>
                <div class="strip-item">
                    <span class="strip-dot" style="background:#6ee7b7;"></span>
                    <span>Active Buyers: <strong style="color:#fff;"><?php echo count($buyers_set); ?></strong></span>
                </div>
                <div class="strip-item">
                    <span class="strip-dot" style="background:#93c5fd;"></span>
                    <span>Active Machines: <strong style="color:#fff;"><?php echo count($mc_set); ?></strong></span>
                </div>
                <div class="strip-item ms-auto">
                    <i class="fa-regular fa-clock" style="color:rgba(186,230,253,0.7);"></i>
                    <span style="color:rgba(186,230,253,0.7);"><?php echo date('d M Y, h:i A'); ?></span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon navy"><i class="fa-solid fa-file-invoice"></i></div>
                    <div>
                        <p class="stat-label">Total Cards</p>
                        <p class="stat-value"><?php echo $total_cards; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon sky"><i class="fa-solid fa-weight-hanging"></i></div>
                    <div>
                        <p class="stat-label">Total Req. Quantity</p>
                        <p class="stat-value"><?php echo number_format($total_qty, 2); ?> <small style="font-size:13px;font-weight:500;color:#94a3b8;">KG</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon royal"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <p class="stat-label">Active Buyers</p>
                        <p class="stat-value"><?php echo count($buyers_set); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon cobalt"><i class="fa-solid fa-gears"></i></div>
                    <div>
                        <p class="stat-label">Active Machines</p>
                        <p class="stat-value"><?php echo count($mc_set); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="filter-panel">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2"><i class="fa-solid fa-filter" style="color:var(--primary-teal);"></i> Filter Knit Cards</h6>
            <form method="GET" action="knit_card_list.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Buyer Name</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="buyer" class="form-control" placeholder="Search Buyer..." value="<?php echo htmlspecialchars($buyer_filter); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1">Machine (M/C No)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-hard-drive"></i></span>
                        <input type="text" name="mc_no" class="form-control" placeholder="e.g. 87" value="<?php echo htmlspecialchars($mc_no_filter); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1">From Date</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary"><i class="fa-regular fa-calendar"></i></span>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1">To Date</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary"><i class="fa-regular fa-calendar"></i></span>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1 py-2 fw-semibold" style="border-radius:10px; background-color:#1a56db; border-color:#1a56db;">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Apply Filter
                        </button>
                        <a href="knit_card_list.php" class="btn btn-sm btn-outline-secondary py-2 px-3 fw-semibold" style="border-radius:10px;">
                            <i class="fa-solid fa-rotate-left me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="content-panel">
            <div class="table-responsive">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Card ID</th>
                            <th>Card Date</th>
                            <th>M/C No</th>
                            <th>Buyer</th>
                            <th>Booking No</th>
                            <th>Style</th>
                            <th>Fabric Type</th>
                            <th>Yarn Type</th>
                            <th>Req Qty (KG)</th>
                            <th>Prepared By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows_array) > 0): ?>
                            <?php foreach ($rows_array as $row): ?>
                                <tr>
                                    <td><span class="badge-kc">#KC-<?php echo intval($row['KCID']); ?></span></td>
                                    <td><i class="fa-regular fa-calendar me-1 text-muted"></i><?php echo htmlspecialchars($row['CARD_DATE'] ?? ''); ?></td>
                                    <td><span class="badge-mc">M/C <?php echo htmlspecialchars($row['MCNO'] ?? ''); ?></span></td>
                                    <td><span class="badge-buyer"><?php echo htmlspecialchars($row['BUYER'] ?? ''); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['BOOKING'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['STYLE'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['FABRICS_TYPE'] ?? ''); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($row['YARN_TYPE'] ?? ''); ?></small></td>
                                    <td><strong class="text-success"><?php echo number_format((float)($row['REQ_QTY'] ?? 0), 2); ?> KG</strong></td>
                                    <td><small class="text-secondary"><i class="fa-solid fa-user-circle me-1"></i><?php echo htmlspecialchars($row['PREPARED_BY'] ?? ''); ?></small></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-2">
                                            <a href="knit_card_view.php?id=<?php echo intval($row['KCID']); ?>"
                                               class="btn btn-action-view"
                                               title="View & Manage Production Log">
                                                <i class="fa-solid fa-eye me-1"></i> View Log
                                            </a>
                                            <a href="knit_card_print.php?id=<?php echo intval($row['KCID']); ?>"
                                               target="_blank"
                                               class="btn btn-action-print"
                                               title="Print Production Card">
                                                <i class="fa-solid fa-print me-1"></i> Print
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                                    <h6 class="fw-bold">No Knit Cards Found</h6>
                                    <p class="small mb-0">Try clearing your search filters or generate a new card from the Knitting Programs page.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
