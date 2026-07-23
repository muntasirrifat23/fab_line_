<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$uname = $_SESSION['username'];

// Search filters
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

        .badge-kc {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
        }

        .badge-mc {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
        }

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
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white !important;
            font-weight: 700;
            border-radius: 10px !important;
            padding: 8px 16px !important;
            border: none;
            font-size: 13px !important;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
            transition: all 0.2s ease;
        }
        .btn-action-edit:hover {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
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
                        <p class="banner-subtitle">Manage knitting production cards, view tracking logs, and generate PDF card printouts</p>
                    </div>
                </div>
                <!-- Right: action buttons -->
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="initialPage.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-arrow-left"></i> Dashboard
                    </a>
                    <a href="knitting_program.php" class="btn nav-btn btn-glass">
                        <i class="fa-solid fa-industry"></i> Knitting Programs
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
                    <div class="stat-icon bg-teal-light"><i class="fa-solid fa-id-card"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Cards</div>
                        <div class="fs-4 fw-bold text-dark"><?php echo number_format($total_cards); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-blue-light"><i class="fa-solid fa-weight-hanging"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Req Qty</div>
                        <div class="fs-4 fw-bold text-dark"><?php echo number_format($total_qty, 2); ?> <span class="fs-6 text-muted font-normal">KG</span></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-green-light"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Active Buyers</div>
                        <div class="fs-4 fw-bold text-success"><?php echo number_format(count($buyers_set)); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-amber-light"><i class="fa-solid fa-gears"></i></div>
                    <div>
                        <div class="text-muted small fw-semibold">Active Machines</div>
                        <div class="fs-4 fw-bold" style="color:#b45309;"><?php echo number_format(count($mc_set)); ?></div>
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
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1">From Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-secondary mb-1">To Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
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
                            <?php foreach ($rows_array as $row):
                                $c_id       = intval($row['KCID']);
                                $c_date     = !empty($row['CARD_DATE']) ? date('Y-m-d', strtotime($row['CARD_DATE'])) : '';
                                $c_mc       = $row['MCNO']         ?? '';
                                $c_buyer    = $row['BUYER']        ?? '';
                                $c_booking  = $row['kp_booking']   ?? $row['BOOKING'] ?? '';
                                $c_style    = $row['STYLE']        ?? '';
                                $c_fabrics  = $row['FABRICS_TYPE'] ?? '';
                                $c_yarn     = $row['YARN_TYPE']    ?? '';
                                $c_req_qty  = floatval($row['REQ_QTY'] ?? 0);
                                $c_prep     = $row['PREPARED_BY']  ?? '';
                            ?>
                                <tr>
                                    <td><span class="badge-kc">#KC-<?php echo $c_id; ?></span></td>
                                    <td>
                                        <i class="fa-regular fa-calendar me-1 text-muted"></i>
                                        <?php echo htmlspecialchars($c_date); ?>
                                    </td>
                                    <td><span class="badge-mc">M/C <?php echo htmlspecialchars($c_mc); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($c_buyer); ?></strong></td>
                                    <td><?php echo htmlspecialchars($c_booking); ?></td>
                                    <td><?php echo htmlspecialchars($c_style); ?></td>
                                    <td><?php echo htmlspecialchars($c_fabrics); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($c_yarn); ?></small></td>
                                    <td><strong class="text-success"><?php echo number_format($c_req_qty, 2); ?> KG</strong></td>
                                    <td><small class="text-secondary"><i class="fa-solid fa-user-circle me-1"></i><?php echo htmlspecialchars($c_prep); ?></small></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-2">
                                            <a href="knitting/knit_card_view.php?id=<?php echo $c_id; ?>"
                                               class="btn btn-sm btn-action-view"
                                               title="View Production Log">
                                                <i class="fa-solid fa-eye me-1"></i> View Card
                                            </a>
                                            <a href="knitting/knit_card_print.php?id=<?php echo $c_id; ?>"
                                               class="btn btn-sm btn-action-edit"
                                               target="_blank"
                                               title="Print Card Details">
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
                                    <p class="small mb-0">Try adjusting your filters or search terms.</p>
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