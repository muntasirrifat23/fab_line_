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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-teal: #00796b;
            --dark-teal: #004d40;
            --accent-green: #059669;
            --surface-bg: #f0f4f9;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            --header-from:  #0a0f2e;
            --header-mid:   #0d2168;
            --header-to:    #1a56db;
        }

        i, i.fa-solid, i.fas, i.far, i.fab {
            border: none !important; outline: none !important; box-shadow: none !important;
            padding: 0 !important; display: inline-block !important; transform: none !important;
        }

        body {
            padding: 20px;
            background-color: var(--surface-bg);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            color: #334155;
        }

        /* ═══════════════════════════════════════════
           HEADER BANNER — Premium Indigo / Violet
        ═══════════════════════════════════════════ */
        .top-banner {
            position: relative;
            background: linear-gradient(135deg, var(--header-from) 0%, var(--header-mid) 45%, var(--header-to) 100%);
            color: white;
            padding: 32px 36px 0 36px;
            border-radius: 22px;
            box-shadow: 0 20px 50px rgba(76, 29, 149, 0.35);
            margin-bottom: 28px;
            overflow: hidden;
        }

        /* Decorative background blobs */
        .top-banner::before {
            content: '';
            position: absolute;
            width: 340px; height: 340px;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.18) 0%, transparent 70%);
            top: -90px; right: -70px;
            border-radius: 50%;
            pointer-events: none;
        }
        .top-banner::after {
            content: '';
            position: absolute;
            width: 220px; height: 220px;
            background: radial-gradient(circle, rgba(147, 197, 253, 0.13) 0%, transparent 70%);
            bottom: 0; left: 50px;
            border-radius: 50%;
            pointer-events: none;
        }

        .banner-inner {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 26px;
        }

        /* Icon badge */
        .banner-icon-wrap {
            width: 58px; height: 58px;
            border-radius: 16px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            flex-shrink: 0;
            backdrop-filter: blur(6px);
        }

        .banner-title-group { display: flex; align-items: center; gap: 18px; }

        .top-banner h1 {
            font-weight: 800;
            font-size: 1.85rem;
            margin: 0 0 6px 0;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .banner-subtitle {
            font-size: 13.5px;
            color: rgba(233, 213, 255, 0.85);
            margin: 0;
            font-weight: 400;
            letter-spacing: 0.2px;
        }

        /* Nav buttons */
        .nav-btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            padding: 9px 18px;
            transition: all 0.22s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.2); }

        .btn-glass {
            background: rgba(255,255,255,0.11);
            border: 1px solid rgba(255,255,255,0.22);
            color: white;
            backdrop-filter: blur(8px);
        }
        .btn-glass:hover { background: rgba(255,255,255,0.2); color: white; }

        .btn-blue-solid {
            background: rgba(59, 130, 246, 0.85);
            border: 1px solid rgba(96, 165, 250, 0.5);
            color: white;
        }
        .btn-blue-solid:hover { background: rgba(37, 99, 235, 1); color: white; }

        /* Bottom glassy strip inside header */
        .banner-info-strip {
            position: relative;
            z-index: 2;
            background: rgba(255,255,255,0.07);
            border-top: 1px solid rgba(255,255,255,0.12);
            margin: 0 -36px;
            padding: 12px 36px;
            display: flex;
            align-items: center;
            gap: 28px;
            flex-wrap: wrap;
        }

        .strip-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: rgba(186, 230, 253, 0.9);
            font-weight: 500;
        }

        .strip-item .strip-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        /* ═══════════════════════════════════════════
           STAT CARDS
        ═══════════════════════════════════════════ */
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px 22px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e8edf2;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
        }
        .stat-icon.navy   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
        .stat-icon.sky    { background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0369a1; }
        .stat-icon.royal  { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563eb; }
        .stat-icon.cobalt { background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #3730a3; }

        .stat-label { font-size: 11.5px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.6px; margin: 0; }
        .stat-value { font-size: 22px; font-weight: 800; color: #0f172a; margin: 2px 0 0; letter-spacing: -0.5px; }

        /* ═══════════════════════════════════════════
           FILTER PANEL
        ═══════════════════════════════════════════ */
        .filter-panel {
            background: #fff;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 9px 14px;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
        }

        /* Specific rules for filter form controls to align perfectly with button height */
        .filter-panel .form-control, .filter-panel .form-select, .filter-panel .input-group-text {
            height: 38px !important;
            padding: 6px 12px !important;
            font-size: 13.5px !important;
        }
        .filter-panel .btn {
            height: 38px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* ═══════════════════════════════════════════
           TABLE
        ═══════════════════════════════════════════ */
        .content-panel {
            background: #fff;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
        }

        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .custom-table thead th {
            background: linear-gradient(135deg, #0a0f2e, #1e3a8a);
            color: #bfdbfe;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 16px;
            border: none;
        }
        .custom-table thead th:first-child { border-top-left-radius: 12px; }
        .custom-table thead th:last-child  { border-top-right-radius: 12px; }
        .custom-table tbody td { padding: 14px 16px; font-size: 13.5px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .custom-table tbody tr:hover { background-color: #eff6ff; }

        .badge-kc    { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e3a8a; font-weight: 700; font-size: 12.5px; padding: 5px 13px; border-radius: 20px; display: inline-block; border: 1px solid #93c5fd; }
        .badge-mc    { background: #f1f5f9; color: #475569; font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 8px; border: 1px solid #cbd5e1; }
        .badge-buyer { color: #0f172a; font-weight: 700; }
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
                                        <div class="d-inline-flex gap-1">
                                            <a href="knit_card_view.php?id=<?php echo intval($row['KCID']); ?>"
                                               class="btn btn-sm btn-primary px-3 py-1"
                                               style="border-radius:8px; font-size:12.5px; background-color:#2563eb; border-color:#2563eb;"
                                               title="View & Manage Production Log">
                                                <i class="fa-solid fa-eye me-1"></i> View Log
                                            </a>
                                            <a href="knit_card_print.php?id=<?php echo intval($row['KCID']); ?>"
                                               target="_blank"
                                               class="btn btn-sm btn-success px-3 py-1"
                                               style="border-radius:8px; font-size:12.5px; background-color:var(--accent-green); border-color:var(--accent-green);"
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
