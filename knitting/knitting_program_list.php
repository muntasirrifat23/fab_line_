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

    <style>
        :root {
            --primary-teal: #00796b;
            --dark-teal: #004d40;
            --surface-bg: #f8fafc;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
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
            padding: 20px;
            background-color: var(--surface-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #334155;
        }

        .top-banner {
            background: linear-gradient(135deg, #004d40 0%, #00796b 50%, #00897b 100%);
            color: white;
            padding: 22px 28px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 121, 107, 0.2);
            margin-bottom: 24px;
        }

        .top-banner h1 {
            font-weight: 700;
            font-size: 1.75rem;
            margin: 0;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 18px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: var(--card-shadow);
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .bg-teal-light  { background: #e0f2f1; color: var(--primary-teal); }
        .bg-blue-light  { background: #dbeafe; color: #1d4ed8; }
        .bg-green-light { background: #dcfce7; color: #15803d; }
        .bg-amber-light { background: #fef3c7; color: #b45309; }

        .search-panel {
            background: #ffffff;
            border-radius: 14px;
            padding: 20px 24px;
            border: 1px solid #e2e8f0;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
        }

        .table-panel {
            background: #ffffff;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: var(--card-shadow);
        }

        .custom-table {
            vertical-align: middle;
            font-size: 13.5px;
        }

        .custom-table thead th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
            padding: 12px 14px;
            border-bottom: 2px solid #cbd5e1;
        }

        .custom-table tbody tr { transition: background 0.15s ease; }
        .custom-table tbody tr:hover { background-color: #f8fafc; }
        .custom-table td { padding: 12px 14px; }

        .badge-status {
            font-size: 11px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-generated { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-pending   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .btn-teal             { background-color: var(--primary-teal); border-color: var(--primary-teal); color: white; }
        .btn-teal:hover       { background-color: var(--dark-teal); color: white; }
    </style>
</head>

<body>

    <div class="container-fluid" style="max-width: 1400px;">

        <!-- Header Banner -->
        <div class="top-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-list-check"></i> Knitting Program Directory
                </h1>
                <p class="mb-0 text-white-50 small mt-1">Manage production knitting programs, track quantities, and generate production Knit Cards</p>
            </div>
            <div class="d-flex gap-2">
                <a href="initialPage.php" class="btn btn-light px-3 py-2 fw-semibold text-dark" style="border-radius:10px;">
                    <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
                </a>
                <a href="knitting_program_form.php" class="btn btn-teal px-3 py-2 fw-semibold" style="border-radius:10px;">
                    <i class="fa-solid fa-plus me-1"></i> New Program
                </a>
                <a href="knit_card_list.php" class="btn btn-outline-light px-3 py-2 fw-semibold" style="border-radius:10px;">
                    <i class="fa-solid fa-id-card me-1"></i> All Knit Cards
                </a>
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
            <form method="GET" action="knitting_program_list.php" class="row g-3 align-items-end">
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
                    <a href="knitting_program_list.php" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
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
                                        <div class="d-inline-flex gap-1">
                                            <?php if ($p_card_gen === 1): ?>
                                                <?php if (!empty($p_card_id)): ?>
                                                    <a href="knit_card_view.php?id=<?php echo intval($p_card_id); ?>"
                                                       class="btn btn-sm btn-primary px-3 py-1"
                                                       style="border-radius:6px; font-size:12.5px; background-color:#2563eb; border-color:#2563eb;"
                                                       title="View Generated Card Log">
                                                        <i class="fa-solid fa-eye me-1"></i> View Card
                                                    </a>
                                                <?php else: ?>
                                                    <a href="knit_card_list.php" class="btn btn-sm btn-secondary px-3 py-1" style="border-radius:6px; font-size:12.5px;">
                                                        <i class="fa-solid fa-id-card me-1"></i> All Cards
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="knit_card_generate.php?program_id=<?php echo $p_id; ?>"
                                                   class="btn btn-sm btn-teal px-3 py-1"
                                                   style="border-radius:6px; font-size:12.5px;"
                                                   onclick="return confirm('Generate a new Knit Card for this program?');"
                                                   title="Generate Knit Card">
                                                    <i class="fa-solid fa-file-circle-plus me-1"></i> Generate Card
                                                </a>
                                            <?php endif; ?>

                                            <a href="knitting_program_form.php?id=<?php echo $p_id; ?>"
                                               class="btn btn-sm btn-warning text-dark px-3 py-1 fw-semibold"
                                               style="border-radius:6px; font-size:12.5px; background-color:#d97706; border-color:#d97706; color:white !important;"
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
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>
