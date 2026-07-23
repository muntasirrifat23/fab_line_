<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$uname = $_SESSION['username'];

// Search filters
$buyer_filter    = isset($_GET['buyer'])      ? trim($_GET['buyer'])      : '';
$mc_no_filter    = isset($_GET['mc_no'])      ? trim($_GET['mc_no'])      : '';
$booking_filter  = isset($_GET['booking_no']) ? trim($_GET['booking_no']) : '';

// Build query using real KPTID column; LEFT JOIN knit_card on KPTID
$query = "SELECT kp.*, kc.KCID AS card_id
          FROM knitting_program kp
          LEFT JOIN knit_card kc ON kp.KPTID = kc.KPTID
          WHERE 1=1";
$params = [];
$types  = '';

if ($buyer_filter !== '') {
    $query   .= " AND kp.BUYER LIKE ?";
    $params[] = "%{$buyer_filter}%";
    $types   .= 's';
}
if ($mc_no_filter !== '') {
    $query   .= " AND kp.MCNO LIKE ?";
    $params[] = "%{$mc_no_filter}%";
    $types   .= 's';
}
if ($booking_filter !== '') {
    $query   .= " AND kp.BOOKING LIKE ?";
    $params[] = "%{$booking_filter}%";
    $types   .= 's';
}

$query .= " ORDER BY kp.KPTID DESC";

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

// Summary stats
$total_programs = 0;
$total_req_qty  = 0.00;
$generated_count = 0;
$pending_count   = 0;
$rows_array = [];

if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $rows_array[]    = $r;
        $total_programs++;
        $total_req_qty  += floatval($r['QTY'] ?? 0);
        if (intval($r['CARD_GENERATED']) === 1) {
            $generated_count++;
        } else {
            $pending_count++;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting Program Directory | Purbani Fabrics</title>

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
        
        .bg-teal-light  { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; }
        .bg-blue-light  { background: linear-gradient(135deg, #f0f9ff, #e0f2fe); color: #0284c7; }
        .bg-green-light { background: linear-gradient(135deg, #f0fdf4, #dcfce7); color: #166534; }
        .bg-amber-light { background: linear-gradient(135deg, #fffbeb, #fef3c7); color: #b45309; }

        /* ═══════════════════════════════════════════
           SEARCH & FILTER PANEL
        ═══════════════════════════════════════════ */
        .search-panel {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            margin-bottom: 28px;
        }

        .form-control {
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            font-size: 14px;
            color: #0f172a;
            font-weight: 500;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            background-color: #ffffff;
        }

        /* Specific rules for filter form controls */
        .search-panel .form-control {
            height: 42px !important;
            padding: 8px 14px !important;
            font-size: 13.5px !important;
            border-color: #cbd5e1;
        }
        
        .search-panel .btn {
            height: 42px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 12px !important;
            font-weight: 700;
            font-size: 13.5px;
        }

        /* ═══════════════════════════════════════════
           TABLES
        ═══════════════════════════════════════════ */
        .table-panel {
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

        .badge-status {
            font-size: 11px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-generated { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-pending   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .btn-teal {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
            font-weight: 700;
            border-radius: 12px;
            padding: 11px 24px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
        }
        .btn-teal:hover {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
        }

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

        .btn-action-edit {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: white !important;
            font-weight: 700;
            border-radius: 10px !important;
            padding: 8px 16px !important;
            border: none;
            font-size: 13px !important;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2);
            transition: all 0.2s ease;
        }
        .btn-action-edit:hover {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(217, 119, 6, 0.35);
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
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div>
                        <h1>Knitting Program Directory</h1>
                        <p class="banner-subtitle">Manage production knitting programs, track quantities, and generate production Knit Cards</p>
                    </div>
                </div>
                <!-- Right: action buttons -->
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="initialPage.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-arrow-left"></i> Dashboard
                    </a>
                    <a href="knitting/knitting_program_form.php" class="btn nav-btn btn-blue-solid">
                        <i class="fa-solid fa-plus"></i> New Program
                    </a>
                    <a href="knitting/knit_card_list.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-id-card"></i> All Knit Cards
                    </a>
                </div>
            </div>
            <!-- Bottom strip -->
            <div class="banner-info-strip">
                <div class="strip-item">
                    <i class="fa-solid fa-user-shield text-info"></i>
                    <span>Authorized User: <?php echo htmlspecialchars($uname); ?></span>
                </div>
                <div class="strip-item ms-auto">
                    <i class="fa-regular fa-clock text-info"></i>
                    <span><?php date_default_timezone_set('Asia/Dhaka'); echo date('d M Y, h:i A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-teal-light"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Programs</div>
                        <div class="fs-4 fw-bold text-dark"><?php echo number_format($total_programs); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-blue-light"><i class="fa-solid fa-weight-hanging"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Required Qty</div>
                        <div class="fs-4 fw-bold text-dark"><?php echo number_format($total_req_qty, 2); ?> <span class="fs-6 text-muted font-normal">KG</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-green-light"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Cards Generated</div>
                        <div class="fs-4 fw-bold text-success"><?php echo number_format($generated_count); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-amber-light"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Pending Cards</div>
                        <div class="fs-4 fw-bold" style="color:#b45309;"><?php echo number_format($pending_count); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="search-panel">
            <form method="GET" action="knit_card.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Filter by Buyer</label>
                    <input type="text" name="buyer" class="form-control form-control-sm" placeholder="Buyer name..." value="<?php echo htmlspecialchars($buyer_filter); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Filter by Machine No (M/C)</label>
                    <input type="text" name="mc_no" class="form-control form-control-sm" placeholder="Machine No..." value="<?php echo htmlspecialchars($mc_no_filter); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary mb-1">Filter by Booking No</label>
                    <input type="text" name="booking_no" class="form-control form-control-sm" placeholder="Booking No..." value="<?php echo htmlspecialchars($booking_filter); ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-teal btn-sm px-4 flex-grow-1 fw-semibold">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Filter
                    </button>
                    <a href="knit_card.php" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
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
                            <th>Program ID</th>
                            <th>Date</th>
                            <th>M/C No</th>
                            <th>Buyer</th>
                            <th>Booking No</th>
                            <th>Style No</th>
                            <th>Fabric Type</th>
                            <th>Yarn Type</th>
                            <th>Req Qty (KG)</th>
                            <th>Card Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows_array) > 0): ?>
                            <?php foreach ($rows_array as $row):
                                $p_id       = intval($row['KPTID']);
                                $p_date     = !empty($row['CREATED_DATE']) ? date('Y-m-d', strtotime($row['CREATED_DATE'])) : '';
                                $p_mc       = $row['MCNO']         ?? '';
                                $p_buyer    = $row['BUYER']        ?? '';
                                $p_booking  = $row['BOOKING']      ?? '';
                                $p_style    = $row['STYLE']        ?? '';
                                $p_fabrics  = $row['FABRICS_TYPE'] ?? '';
                                $p_yarn     = $row['YARN_TYPE']    ?? '';
                                $p_req_qty  = floatval($row['QTY'] ?? 0);
                                $p_card_gen = intval($row['CARD_GENERATED'] ?? 0);
                                $p_card_id  = $row['card_id'] ?? '';
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $p_id; ?></strong></td>
                                    <td>
                                        <i class="fa-regular fa-calendar me-1 text-muted"></i>
                                        <?php echo htmlspecialchars($p_date); ?>
                                    </td>
                                    <td><strong>M/C <?php echo htmlspecialchars($p_mc); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($p_buyer); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p_booking); ?></td>
                                    <td><?php echo htmlspecialchars($p_style); ?></td>
                                    <td><?php echo htmlspecialchars($p_fabrics); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($p_yarn); ?></small></td>
                                    <td><strong class="text-success"><?php echo number_format($p_req_qty, 2); ?> KG</strong></td>
                                    <td>
                                        <?php if ($p_card_gen === 1): ?>
                                            <span class="badge-status badge-generated"><i class="fa-solid fa-circle-check"></i> Generated</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-2">
                                            <?php if ($p_card_gen === 1): ?>
                                                <?php if (!empty($p_card_id)): ?>
                                                    <a href="knitting/knit_card_view.php?id=<?php echo intval($p_card_id); ?>"
                                                       class="btn btn-sm btn-action-view"
                                                       title="View Generated Card Log">
                                                        <i class="fa-solid fa-eye me-1"></i> View Card
                                                    </a>
                                                <?php else: ?>
                                                    <a href="knitting/knit_card_list.php" class="btn btn-sm btn-action-view">
                                                        <i class="fa-solid fa-id-card me-1"></i> All Cards
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="knitting/knit_card_generate.php?program_id=<?php echo $p_id; ?>"
                                                   class="btn btn-sm btn-teal"
                                                   style="border-radius:10px; font-size:12.5px;"
                                                   onclick="return confirm('Generate a new Knit Card for this program?');"
                                                   title="Generate Knit Card">
                                                    <i class="fa-solid fa-file-circle-plus me-1"></i> Generate Card
                                                </a>
                                            <?php endif; ?>

                                            <a href="knitting/knitting_program_form.php?id=<?php echo $p_id; ?>"
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
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                                    <h6 class="fw-bold">No Knitting Programs Found</h6>
                                    <p class="small mb-0">Try adjusting your filters or click "New Program" to add an entry.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="jquery.min.js"></script>
    <script src="knitting/js/bootstrap.bundle.min.js"></script>
</body>

</html>