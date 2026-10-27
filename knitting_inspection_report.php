<?php
// knitting_inspection_report.php - Comprehensive Inspection Report with Category (Pass/Rejection) & Multi-Parameter Filters
session_start();
include 'config.php';

// Fetch distinct filter values from database
$filter_buyers = [];
if (isset($db) && $db) {
    $res_b = @mysqli_query($db, "SELECT DISTINCT TRIM(BUYER) AS b FROM knitting_inspection WHERE BUYER IS NOT NULL AND TRIM(BUYER) != '' ORDER BY b ASC");
    if ($res_b) { while ($r = mysqli_fetch_assoc($res_b)) { $filter_buyers[] = $r['b']; } }
}

$filter_machines = [];
if (isset($db) && $db) {
    $res_m = @mysqli_query($db, "SELECT DISTINCT TRIM(MCNO) AS m FROM knitting_inspection WHERE MCNO IS NOT NULL AND TRIM(MCNO) != '' ORDER BY m ASC");
    if ($res_m) { while ($r = mysqli_fetch_assoc($res_m)) { $filter_machines[] = $r['m']; } }
}

$filter_shifts = [];
if (isset($db) && $db) {
    $res_s = @mysqli_query($db, "SELECT DISTINCT TRIM(SHIFT) AS s FROM knitting_inspection WHERE SHIFT IS NOT NULL AND TRIM(SHIFT) != '' ORDER BY s ASC");
    if ($res_s) { while ($r = mysqli_fetch_assoc($res_s)) { $filter_shifts[] = $r['s']; } }
}

$filter_grades = [];
if (isset($db) && $db) {
    $res_g = @mysqli_query($db, "SELECT DISTINCT TRIM(QC_GRADE) AS g FROM knitting_inspection WHERE QC_GRADE IS NOT NULL AND TRIM(QC_GRADE) != '' ORDER BY g ASC");
    if ($res_g) { while ($r = mysqli_fetch_assoc($res_g)) { $filter_grades[] = $r['g']; } }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | Knitting Inspection Report</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #16a34a;
            --danger: #dc2626;
            --slate-800: #1e293b;
            --slate-100: #f1f5f9;
        }

        body {
            padding: 16px;
            background: #f1f5f9;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
            color: #1e293b;
        }

        .head-row {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            position: relative;
        }

        h1 {
            flex: 1;
            text-align: center;
            font-size: 1.85rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            letter-spacing: -0.5px;
        }

        #backBtn {
            background: #000000 !important;
            color: #ffffff !important;
            border: 2px solid #000000 !important;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        #backBtn:hover {
            background: #ffffff !important;
            border-color: #000000 !important;
            color: #000000 !important;
        }

        /* ─── Filter Panel ─── */
        .filter-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* Category Toggle Pills */
        .category-pill-group {
            display: inline-flex;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 4px;
            gap: 4px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .cat-pill {
            border: none;
            background: transparent;
            padding: 7px 16px;
            font-size: 0.92rem;
            font-weight: 600;
            border-radius: 7px;
            cursor: pointer;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.18s ease;
        }

        .cat-pill:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .cat-pill.active[data-cat="all"] {
            background: #1e293b;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(30, 41, 59, 0.25);
        }

        .cat-pill.active[data-cat="pass"] {
            background: #16a34a;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3);
        }

        .cat-pill.active[data-cat="rejection"] {
            background: #dc2626;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }

        @media (max-width: 1200px) {
            .filter-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.4px;
        }

        .form-control, .form-select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            height: 40px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .btn-action-group {
            display: flex;
            gap: 6px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 40px;
            transition: all .15s;
        }

        .btn-search {
            background: #2563eb;
            color: #fff;
        }

        .btn-search:hover {
            background: #1d4ed8;
        }

        .btn-clear {
            background: #64748b;
            color: #fff;
        }

        .btn-clear:hover {
            background: #475569;
        }

        /* ─── Summary KPI Bar ─── */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .stat-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .stat-card-icon.blue   { background: #eff6ff; color: #2563eb; }
        .stat-card-icon.green  { background: #ecfdf5; color: #16a34a; }
        .stat-card-icon.red    { background: #fef2f2; color: #dc2626; }
        .stat-card-icon.amber  { background: #fffbeb; color: #d97706; }
        .stat-card-icon.purple { background: #faf5ff; color: #7c3aed; }

        .stat-card-info {
            line-height: 1.2;
        }

        .stat-card-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 3px;
        }

        .stat-card-val {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
        }

        /* ─── Table ─── */
        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .table-scroll {
            overflow-x: auto;
            max-height: calc(100vh - 270px);
            overflow-y: auto;
        }

        .table-scroll table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.93rem;
            min-width: 3500px;
        }

        .table-scroll thead th {
            background: #1e293b !important;
            color: #fff !important;
            padding: 13px 12px;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 5;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }

        .table-scroll thead th:first-child {
            left: 0;
            z-index: 8;
        }

        .table-scroll tbody td {
            padding: 11px 12px;
            border-bottom: 1px solid #eef2f7;
            white-space: nowrap;
            color: #1e293b;
        }

        .table-scroll tbody td:first-child {
            position: sticky;
            left: 0;
            background: #ffffff;
            z-index: 3;
        }

        .table-scroll tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .table-scroll tbody tr:nth-child(even) td:first-child {
            background: #f8fafc;
        }

        .table-scroll tbody tr:hover {
            background: #eff6ff;
        }

        .table-scroll tbody tr:hover td:first-child {
            background: #eff6ff;
        }

        /* Badges */
        .badge-roll-pass {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            font-weight: 800;
            font-size: 0.88rem;
            padding: 4px 9px;
            border-radius: 6px;
            display: inline-block;
            letter-spacing: 0.3px;
        }

        .badge-roll-reject {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-weight: 800;
            font-size: 0.88rem;
            padding: 4px 9px;
            border-radius: 6px;
            display: inline-block;
            letter-spacing: 0.3px;
        }

        .badge-cat-pass {
            background: #10b981;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.78rem;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-cat-reject {
            background: #ef4444;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.78rem;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .text-reject-qty {
            color: #dc2626;
            font-weight: 700;
        }

        .btn-print-row {
            background: #2563eb;
            border: none;
            color: #fff;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.15s;
            white-space: nowrap;
        }

        .btn-print-row:hover {
            background: #1d4ed8;
        }

        .btn-qr-action {
            background: #dc2626;
            border: none;
            color: #fff;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 5px;
            transition: background 0.15s;
            white-space: nowrap;
        }

        .btn-qr-action:hover {
            background: #b91c1c;
        }

        .btn-qr-pass {
            background: #16a34a;
        }

        .btn-qr-pass:hover {
            background: #15803d;
        }

        .qr-print-area {
            display: none;
        }

        .empty-row td,
        .loading-cell {
            text-align: center;
            padding: 32px;
            color: #94a3b8;
            font-weight: 600;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            body * {
                visibility: hidden !important;
            }

            /* Scenario 1: ASTM Inspection Report Modal */
            #reportModal, #reportModal *, #modalReportContent, #modalReportContent * {
                visibility: visible !important;
            }
            #reportModal {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: auto !important;
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }
            #reportModal > div {
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #rowPdfCard {
                margin: 0 auto !important;
                padding: 10px 14px !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                min-height: 190mm !important;
            }

            /* Pin the QA signatures to the bottom of the printed page */
            #reportSignatures {
                margin-top: auto !important;
                page-break-inside: avoid;
            }

            /* Scenario 2: Rejection / Good QR Card Modal */
            #rejectionLabelModal, #rejectionLabelModal *, 
            #rejectionCardReportContent, #rejectionCardReportContent *, 
            #reportRejectionCardBox, #reportRejectionCardBox * {
                visibility: visible !important;
            }
            #rejectionLabelModal {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: auto !important;
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }
            #rejectionLabelModal > div {
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #reportRejectionCardBox {
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* Scenario 3: QR Print Area */
            .qr-print-area, .qr-print-area * {
                visibility: visible !important;
                display: block !important;
            }
            .qr-print-area {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 8px !important;
            }
            .qr-print-area img {
                max-width: 100% !important;
                height: auto !important;
                display: block !important;
                margin: 0 auto !important;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- Report View & Print Modal -->
    <div id="reportModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:99999; overflow-y:auto; padding:20px 0;">
        <div style="background:#ffffff; width:1150px; margin:0 auto; padding:20px; border-radius:8px; position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
            <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #cbd5e1; padding-bottom:12px;">
                <h4 style="margin:0; font-weight:bold; color:#0f172a; font-size:18px;"><i class="fa-solid fa-file-invoice"></i> Inspection Report Preview</h4>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn" onclick="printModalReport()" style="background:#0284c7; color:#ffffff; padding:7px 16px; border:none; border-radius:5px; font-weight:600; cursor:pointer; font-size:13px;">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                    <button type="button" class="btn" onclick="downloadModalPDF()" style="background:#16a34a; color:#ffffff; padding:7px 16px; border:none; border-radius:5px; font-weight:600; cursor:pointer; font-size:13px;">
                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                    </button>
                    <button type="button" class="btn" onclick="closeReportModal()" style="background:#64748b; color:#ffffff; padding:7px 16px; border:none; border-radius:5px; font-weight:600; cursor:pointer; font-size:13px;">
                        <i class="fa-solid fa-xmark"></i> Close
                    </button>
                </div>
            </div>
            <div id="modalReportContent">
                <!-- Dynamically populated report card -->
            </div>
        </div>
    </div>

    <!-- Rejection Roll QR Card Modal for Report -->
    <div id="rejectionLabelModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:999999; overflow-y:auto; padding:20px; align-items:center; justify-content:center;">
        <div style="background:#ffffff; max-width:820px; width:100%; border-radius:14px; box-shadow:0 15px 35px rgba(0,0,0,0.4); padding:20px; position:relative; max-height:95vh; overflow-y:auto; margin: auto;">
            <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:2px solid #ef4444; padding-bottom:10px;">
                <div>
                    <span id="repModalBadge" style="background:#fee2e2; color:#b91c1c; font-weight:800; font-size:0.75rem; padding:3px 10px; border-radius:20px; display:inline-flex; align-items:center;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> 4-POINT QC REJECTION QR CARD
                    </span>
                    <h4 id="repRejModalTitle" style="margin:4px 0 0 0; font-weight:800; color:#0f172a; font-size:1.15rem;">
                        Rejection Roll
                    </h4>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn" onclick="printRejectionCardFromReport()" style="background:#dc2626; color:#ffffff; font-weight:700; border:none; padding:7px 16px; border-radius:6px; cursor:pointer; font-size:0.85rem;">
                        <i class="fa-solid fa-print me-1"></i> Print QR Card
                    </button>
                    <button type="button" class="btn" onclick="closeRejectionLabelModal()" style="background:#64748b; color:#ffffff; font-weight:700; border:none; padding:7px 14px; border-radius:6px; cursor:pointer; font-size:0.85rem;">
                        <i class="fa-solid fa-xmark me-1"></i> Close
                    </button>
                </div>
            </div>
            <div id="rejectionCardReportContent"></div>
        </div>
    </div>
    <div id="knitCardPrintArea" class="qr-print-area"></div>

    <div class="container-fluid p-0">

        <!-- Top Header Row -->
        <div class="head-row">
            <button class="btn" id="backBtn">
                <i class="fa-solid fa-arrow-left"></i> Back to Report
            </button>
            <h1>Knitting Inspection Report</h1>
        </div>

        <!-- ═══ FILTER PANEL ═══ -->
        <div class="filter-panel">


            <!-- Multi-Parameter Filter Grid -->
            <div class="filter-grid">

                <!-- Search Input -->
                <div class="filter-item">
                    <label class="filter-label" for="bookingInput"><i class="fa-solid fa-magnifying-glass me-1"></i>Search Keyword</label>
                    <input type="text" id="bookingInput" class="form-control" placeholder="Search Roll (-R/-P), PO, SONO, Buyer, Style...">
                </div>

                <!-- Category Dropdown -->
                <div class="filter-item">
                    <label class="filter-label" for="categorySelect"><i class="fa-solid fa-filter me-1"></i>Category</label>
                    <select id="categorySelect" class="form-select">
                        <option value="all">All Categories</option>
                        <option value="pass">Pass (-P)</option>
                        <option value="rejection">Rejection (-R)</option>
                    </select>
                </div>

                <!-- From Date -->
                <div class="filter-item">
                    <label class="filter-label" for="fromDateInput"><i class="fa-regular fa-calendar me-1"></i>From Date</label>
                    <input type="date" id="fromDateInput" class="form-control">
                </div>

                <!-- To Date -->
                <div class="filter-item">
                    <label class="filter-label" for="toDateInput"><i class="fa-regular fa-calendar me-1"></i>To Date</label>
                    <input type="date" id="toDateInput" class="form-control">
                </div>

                <!-- Shift -->
                <div class="filter-item">
                    <label class="filter-label" for="shiftSelect"><i class="fa-solid fa-clock me-1"></i>Shift</label>
                    <select id="shiftSelect" class="form-select">
                        <option value="all">All Shifts</option>
                        <option value="A">Shift A</option>
                        <option value="B">Shift B</option>
                        <option value="C">Shift C</option>
                    </select>
                </div>

                <!-- Machine (MCNO) -->
                <div class="filter-item">
                    <label class="filter-label" for="mcnoSelect"><i class="fa-solid fa-industry me-1"></i>Machine</label>
                    <select id="mcnoSelect" class="form-select">
                        <option value="all">All Machines</option>
                        <?php foreach ($filter_machines as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Buyer -->
                <div class="filter-item">
                    <label class="filter-label" for="buyerSelect"><i class="fa-solid fa-user-tag me-1"></i>Buyer</label>
                    <select id="buyerSelect" class="form-select">
                        <option value="all">All Buyers</option>
                        <?php foreach ($filter_buyers as $b): ?>
                            <option value="<?php echo htmlspecialchars($b); ?>"><?php echo htmlspecialchars($b); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="filter-item">
                    <label class="filter-label">&nbsp;</label>
                    <div class="btn-action-group">
                        <button class="btn btn-search" id="searchBtn" title="Apply Filters">
                            <i class="fa-solid fa-filter"></i> Apply
                        </button>
                        <button class="btn btn-clear" id="clearBtn" title="Reset Filters to Default">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- ═══ SUMMARY STATS KPI STRIP ═══ -->
        <div class="stats-bar" id="statsBar">
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="fa-solid fa-list-check"></i></div>
                <div class="stat-card-info">
                    <div class="stat-card-label">Total Rolls</div>
                    <div class="stat-card-val" id="statTotalRolls">0</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-card-info">
                    <div class="stat-card-label">Pass Rolls (-P)</div>
                    <div class="stat-card-val" id="statPassRolls" style="color: #16a34a;">0</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="stat-card-info">
                    <div class="stat-card-label">Rejection Rolls (-R)</div>
                    <div class="stat-card-val" id="statRejectRolls" style="color: #dc2626;">0</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon purple"><i class="fa-solid fa-weight-hanging"></i></div>
                <div class="stat-card-info">
                    <div class="stat-card-label">Main Qty (KG)</div>
                    <div class="stat-card-val" id="statMainQty">0.00</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-card-info">
                    <div class="stat-card-label">Reject Qty (KG)</div>
                    <div class="stat-card-val" id="statRejectQty" style="color: #d97706;">0.00</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="stat-card-info">
                    <div class="stat-card-label">Usable Qty (KG)</div>
                    <div class="stat-card-val" id="statUpdateQty" style="color: #16a34a;">0.00</div>
                </div>
            </div>
        </div>

        <!-- ═══ INSPECTION DATA TABLE ═══ -->
        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>BUDAT</th>
                            <th>ROLL</th>
                            <th>PASS / REJ</th>
                            <th>MAIN QTY</th>
                            <th>REJECT QTY</th>
                            <th>UPDATE QTY</th>
                            <th>PO NO</th>
                            <th>QTY</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MCNO</th>
                            <th>MC DIA</th>
                            <th>CUSTOMER</th>
                            <th>SHIFT</th>
                            <th>YTYPE</th>
                            <th>YCOUNT</th>
                            <th>YBRAND</th>
                            <th>FTYPE</th>
                            <th>FGSM</th>
                            <th>FDIA</th>
                            <th>O / T</th>
                            <th>SL</th>
                            <th>FPLAN</th>
                            <th>LOTNO</th>
                            <th>CATEGORY</th>
                            <th>TT</th>
                            <th>PATTA</th>
                            <th>SLUB</th>
                            <th>YC SPOT</th>
                            <th>OILSPOT</th>
                            <th>FF</th>
                            <th>SEEDS</th>
                            <th>MSTITCH</th>
                            <th>SINKERMARK</th>
                            <th>NEEDLEMARK</th>
                            <th>LYCOUT</th>
                            <th>OILLINE</th>
                            <th>HOLE</th>
                            <th>LOOP</th>
                            <th>SETUP</th>
                            <th>CMARK</th>
                            <th>TPOINT</th>
                            <th>QC GRADE</th>
                            <th>QC STATUS</th>
                            <th>OP NAME</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="48" class="loading-cell">Loading inspection data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="jquery.min.js"></script>
    <script src="js/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        var COLS = [
            { key: 'BUDAT', label: 'BUDAT' },
            { key: 'ROLL', label: 'ROLL' },
            { key: 'INSPECTION_CATEGORY', label: 'PASS / REJ' },
            { key: 'OQTY',   label: 'MAIN QTY'   },
            { key: 'RQTY', label: 'REJECT QTY'  },
            { key: 'UQTY', label: 'UPDATE QTY'  },
            { key: 'PO_NUMBER', label: 'PO_NUMBER' },
            { key: 'QTY', label: 'QTY' },
            { key: 'SONO', label: 'SONO' },
            { key: 'BUYER', label: 'BUYER' },
            { key: 'STYLE', label: 'STYLE' },
            { key: 'COLOR', label: 'COLOR' },
            { key: 'MCNO', label: 'MCNO' },
            { key: 'MC_DIA', label: 'MC_DIA' },
            { key: 'CUSTOMER', label: 'CUSTOMER' },
            { key: 'SHIFT', label: 'SHIFT' },
            { key: 'YTYPE', label: 'YTYPE' },
            { key: 'YCOUNT', label: 'YCOUNT' },
            { key: 'YBRAND', label: 'YBRAND' },
            { key: 'FTYPE', label: 'FTYPE' },
            { key: 'FGSM', label: 'FGSM' },
            { key: 'FDIA', label: 'FDIA' },
            { key: 'O_T', label: 'O_T' },
            { key: 'SL', label: 'SL' },
            { key: 'FPLAN', label: 'FPLAN' },
            { key: 'LOTNO', label: 'LOTNO' },
            { key: 'CATEGORY', label: 'CATEGORY' },
            { key: 'TT', label: 'TT' },
            { key: 'PATTA', label: 'PATTA' },
            { key: 'SLUB', label: 'SLUB' },
            { key: 'YC_SPOT', label: 'YC_SPOT' },
            { key: 'OILSPOT', label: 'OILSPOT' },
            { key: 'FF', label: 'FF' },
            { key: 'SEEDS', label: 'SEEDS' },
            { key: 'MSTITCH', label: 'MSTITCH' },
            { key: 'SINKERMARK', label: 'SINKERMARK' },
            { key: 'NEEDLEMARK', label: 'NEEDLEMARK' },
            { key: 'LYCOUT', label: 'LYCOUT' },
            { key: 'OILLINE', label: 'OILLINE' },
            { key: 'HOLE', label: 'HOLE' },
            { key: 'LOOP', label: 'LOOP' },
            { key: 'SETUP', label: 'SETUP' },
            { key: 'CMARK', label: 'CMARK' },
            { key: 'TPOINT', label: 'TPOINT' },
            { key: 'QC_GRADE', label: 'QC_GRADE' },
            { key: 'QC_STATUS', label: 'QC_STATUS' },
            { key: 'UNAME', label: 'UNAME' }
        ];

        var currentData = [];
        var activeReportRow = null;

        function esc(v) {
            if (v === null || v === undefined) return '';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderReportHTML(row) {
            var isPass = (String(row.INSPECTION_CATEGORY || '').toLowerCase() === 'pass');
            var isReject = (String(row.INSPECTION_CATEGORY || '').toLowerCase() === 'rejection');
            var isFail = (String(row.QC_STATUS || '').toLowerCase() === 'failed' || isReject);

            return '' +
                '<div id="rowPdfCard" style="position:relative;width:1100px;margin:0 auto;padding:18px 20px 16px 20px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#000000;font-size:9.5px;box-sizing:border-box;">' +
                
                '<!-- Topmost Header Section (Above Outer Border) -->' +
                '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;padding:0 4px;">' +
                '<div style="font-size:9px;line-height:1.3;font-weight:bold;">' +
                'PFL/QF/QAD: 03<br>' +
                'Effective Date : 01-06-2021<br>' +
                'Revision : 00' +
                '</div>' +
                '<div style="text-align:center;">' +
                '<div style="font-size:20px;font-weight:bold;letter-spacing:0.5px;">Purbani Fabrics Limited</div>' +
                '<div style="font-size:11px;font-weight:600;margin-top:1px;">Noorbag, Kaliakoir, Gazipur</div>' +
                '</div>' +
                '<div style="width:100px;"></div>' +
                '</div>' +

                '<!-- Main Outer Box -->' +
                '<div style="border:1.5px solid #000000;padding:0;background:#ffffff;">' +

                '<!-- Title Bar Row -->' +
                '<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1.5px solid #000000;padding:4px 8px;background:#ffffff;">' +
                '<div style="font-size:10px;font-weight:bold;width:150px;">' +
                'Date : <span style="font-weight:normal;">' + esc(row.P_CREATED || row.BUDAT) + '</span>' +
                '</div>' +
                '<div style="font-size:15px;font-weight:bold;text-align:center;flex:1;">' +
                'Knit Fabric Inspection (4 point System)' +
                '</div>' +
                '<div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;min-width:150px;">' +
                '<span style="font-size:16px;font-weight:bold;font-style:italic;letter-spacing:1px;color:#1e3a8a;">PURBANI</span>' +
                '<div id="modalReportQrBox" style="width:40px;height:40px;border:1px solid #000;background:#ffffff;display:inline-flex;align-items:center;justify-content:center;"></div>' +
                '</div>' +
                '</div>' +

                '<!-- Header Specification Table (Metadata Rows 2-4) -->' +
                '<table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #000000;background:#ffffff;margin-bottom:0;" border="1" cellpadding="3">' +
                '<tr>' +
                '<td style="width:16%;border:1px solid #000000;"><b>Buyer :</b> ' + esc(row.BUYER) + '</td>' +
                '<td style="width:16%;border:1px solid #000000;"><b>Style :</b> ' + esc(row.STYLE) + '</td>' +
                '<td style="width:20%;border:1px solid #000000;"><b>Production Order :</b> ' + esc(row.PO_NUMBER || row.KPTID) + '</td>' +
                '<td style="width:20%;border:1px solid #000000;"><b>Sales Order :</b> ' + esc(row.SONO) + '</td>' +
                '<td style="width:14%;border:1px solid #000000;"><b>Shift :</b> ' + esc(row.SHIFT) + '</td>' +
                '<td style="width:14%;border:1px solid #000000;"><b>Quantity :</b> ' + esc(row.QTY) + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td style="border:1px solid #000000;"><b>F/Name :</b> ' + esc(row.FTYPE || row.FABRICS_TYPE) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Color :</b> ' + esc(row.COLOR) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Yarn Count :</b> ' + esc(row.YCOUNT || row.YTYPE) + '</td>' +
                '<td style="border:1.5px solid #000000;"><b>Yarn Brand :</b> ' + esc(row.YBRAND) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Yarn Lot :</b> ' + esc(row.LOTNO) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Fabric Type :</b> ' + esc(row.FTYPE) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Customer :</b> ' + esc(row.CUSTOMER) + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td style="border:1px solid #000000;"><b>M/C No. :</b> ' + esc(row.MCNO) + '</td>' +
                '<td style="border:1px solid #000000;"><b>M/C DIA :</b> ' + esc(row.MC_DIA) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Req. DIA :</b> ' + esc(row.FDIA) + ' &nbsp;<b>FGSM :</b> ' + esc(row.FGSM) + '</td>' +
                '<td style="border:1px solid #000000;"><b>R.SSL :</b> ' + esc(row.SL) + ' &nbsp;<b>R.SKL :</b> ' + esc(row.SL) + '</td>' +
                '<td colspan="2" style="border:1px solid #000000;"><b>Start Time :</b> ' + esc(row.P_CREATED) + ' &nbsp;<b>End Time :</b> </td>' +
                '</tr>' +
                '</table>' +

                '<!-- Main Fabric Fault Inspection Grid (25 Columns) -->' +
                '<table style="width:100%;border-collapse:collapse;font-size:7.5px;text-align:center;border:1px solid #000000;background:#ffffff;table-layout:fixed;" border="1" cellpadding="2">' +
                '<thead>' +
                '<tr style="background:#ffffff;color:#000000;font-weight:bold;font-size:7.5px;">' +
                '<th style="width:7%;border:1px solid #000000;padding:3px 1px;">Roll No</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Wt in kg</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Roll Length</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">T&amp;T</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Patta</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Slub</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Y/C</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Oil Spot</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">F/F</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Seeds</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">O/L stitch</th>' +
                '<th style="width:4.5%;border:1px solid #000000;padding:3px 1px;">Sinker Mark</th>' +
                '<th style="width:4.5%;border:1px solid #000000;padding:3px 1px;">Needle Mark</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Lyc Out</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Oil Line</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Hole</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Loop</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Setup</th>' +
                '<th style="width:4.5%;border:1px solid #000000;padding:3px 1px;">Crease Mark</th>' +
                '<th style="width:5%;border:1px solid #000000;padding:3px 1px;">Per 100 Sq Yds</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Pass</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Fail</th>' +
                '<th style="width:5.5%;border:1px solid #000000;padding:3px 1px;">Q.I Name</th>' +
                '<th style="width:5.5%;border:1px solid #000000;padding:3px 1px;">Remarks</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr style="background:#ffffff;color:#000000;">' +
                '<td style="border:1px solid #000000;word-break:break-all;padding:3px 1px;font-weight:bold;">' + esc(row.ROLL) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.MAIN_QTY || row.QTY) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.O_T) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.TT) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.PATTA) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.SLUB) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.YC_SPOT) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.OILSPOT) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.FF) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.SEEDS) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.MSTITCH) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.SINKERMARK) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.NEEDLEMARK) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.LYCOUT) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.OILLINE) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.HOLE) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.LOOP) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.SETUP) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.CMARK) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;"></td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + (isPass ? '✓' : '') + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + (isFail ? '✓' : '') + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.UNAME || row.UID) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.QC_GRADE) + '</td>' +
                '</tr>' +
                [1, 2, 3, 4, 5, 6, 7].map(function() {
                    return '<tr style="height:18px;background:#ffffff;">' +
                        '<td style="border:1px solid #000000;">&nbsp;</td>'.repeat(25) +
                        '</tr>';
                }).join('') +
                '</tbody>' +
                '<tfoot>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;">' +
                '<td colspan="2" style="border:1px solid #000000;text-align:left;padding-left:4px;">Total Qty : ' + esc(row.QTY) + '</td>' +
                '<td colspan="13" style="border:1px solid #000000;"></td>' +
                '<td colspan="10" style="border:1px solid #000000;text-align:left;padding-left:4px;">Total Points = ' + esc(row.TPOINT) + '</td>' +
                '</tr>' +
                '</tfoot>' +
                '</table>' +

                '<!-- Bottom Summary & Standards Container -->' +
                '<div style="display:flex;gap:3px;padding:3px;font-size:7.5px;align-items:stretch;background:#ffffff;border:1px solid #000000;border-top:none;">' +

                '<!-- Box 1: Defect Point Size in Inch -->' +
                '<div style="width:20%;border:1px solid #000000;padding:1px;background:#ffffff;">' +
                '<table style="width:100%;border-collapse:collapse;text-align:center;font-size:7px;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th colspan="2" style="border:1px solid #000000;padding:2px 1px;">Defect Point Size in Inch</th></tr>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="border:1px solid #000000;padding:2px 1px;">Fault Size</th><th style="border:1px solid #000000;padding:2px 1px;">Point</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr><td style="border:1px solid #000000;">&gt;0" to 3"</td><td style="border:1px solid #000000;">1</td></tr>' +
                '<tr><td style="border:1px solid #000000;">&gt;3" to 6"</td><td style="border:1px solid #000000;">2</td></tr>' +
                '<tr><td style="border:1px solid #000000;">&gt;6" to 9"</td><td style="border:1px solid #000000;">3</td></tr>' +
                '<tr><td style="border:1px solid #000000;">9"above to 36"</td><td style="border:1px solid #000000;">4</td></tr>' +
                '<tr><td style="border:1px solid #000000;">Any hole</td><td style="border:1px solid #000000;">4</td></tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +

                '<!-- Box 2: Formula for 100 sq/yrds -->' +
                '<div style="width:20%;border:1px solid #000000;padding:4px;text-align:center;display:flex;flex-direction:column;justify-content:center;background:#ffffff;color:#000000;">' +
                '<div style="font-weight:bold;margin-bottom:6px;text-decoration:underline;font-size:8px;">Formula for 100 sq/yrds</div>' +
                '<div style="font-size:8px;font-weight:bold;line-height:1.4;">' +
                '<u>Fabrics Fault x 36 x 100</u><br>' +
                'Fabrics Length x Fabrics Width' +
                '</div>' +
                '</div>' +

                '<!-- Box 3: Acceptance Criteria (Others Buyer) -->' +
                '<div style="width:20%;border:1px solid #000000;padding:1px;background:#ffffff;">' +
                '<table style="width:100%;border-collapse:collapse;text-align:center;font-size:7px;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th colspan="2" style="border:1px solid #000000;">Others Buyer</th></tr>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="border:1px solid #000000;">Accept Point (Ins Roll)</th><th style="border:1px solid #000000;">Grade</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr><td style="border:1px solid #000000;">up to 26</td><td style="border:1px solid #000000;">A</td></tr>' +
                '<tr><td style="border:1px solid #000000;">26 to 38</td><td style="border:1px solid #000000;">B</td></tr>' +
                '<tr><td style="border:1px solid #000000;">38 to 60</td><td style="border:1px solid #000000;">C</td></tr>' +
                '<tr><td style="border:1px solid #000000;">Above</td><td style="border:1px solid #000000;">Reject</td></tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +

                '<!-- Box 4: Avg Points per 100 sqr. Yards -->' +
                '<div style="width:18%;border:1px solid #000000;padding:1px;background:#ffffff;">' +
                '<table style="width:100%;border-collapse:collapse;text-align:center;font-size:7px;height:100%;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th colspan="3" style="border:1px solid #000000;">Avg Points per 100 sqr. Yards</th></tr>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="border:1px solid #000000;">Pass</th><th style="border:1px solid #000000;">Fail</th><th style="border:1px solid #000000;">Reject</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr>' +
                '<td style="border:1px solid #000000;font-weight:bold;font-size:10px;">' + (isPass ? '✓' : '') + '</td>' +
                '<td style="border:1px solid #000000;font-weight:bold;font-size:10px;">' + (isFail ? '✓' : '') + '</td>' +
                '<td style="border:1px solid #000000;font-weight:bold;font-size:10px;">' + (isReject ? '✓' : '') + '</td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +

                '<!-- Box 5: Special Comments -->' +
                '<div style="width:11%;border:1px solid #000000;padding:1px;font-size:7px;background:#ffffff;">' +
                '<table style="width:100%;border-collapse:collapse;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="padding:1px;border:1px solid #000000;">Special Comments :</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr><td style="padding:1px;border:1px solid #000000;">Y/Contta</td></tr>' +
                '<tr><td style="padding:1px;border:1px solid #000000;">Dead/Fibre</td></tr>' +
                '<tr><td style="padding:1px;border:1px solid #000000;">Hairynae</td></tr>' +
                '<tr><td style="padding:1px;border:1px solid #000000;">Patta</td></tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +

                '<!-- Box 6: Comments -->' +
                '<div style="width:11%;border:1px solid #000000;padding:4px;font-size:7.5px;background:#ffffff;color:#000000;">' +
                '<div style="font-weight:bold;text-decoration:underline;margin-bottom:3px;">Comments</div>' +
                '<div style="font-weight:bold;">' + esc(row.QC_GRADE || '') + '</div>' +
                '</div>' +

                '</div>' +

                '</div> <!-- End Main Outer Box -->' +

                '<!-- Signatures Section -->' +
                '<div id="reportSignatures" style="display:flex;justify-content:space-between;margin-top:25px;text-align:center;font-size:9px;font-weight:bold;">' +
                '<div style="width:22%;">' +
                '<div style="border-top:1.5px solid #000000;margin-bottom:3px;"></div>' +
                'Shift-In-Charge QA (PFL)' +
                '</div>' +
                '<div style="width:22%;">' +
                '<div style="border-top:1.5px solid #000000;margin-bottom:3px;"></div>' +
                'Officer QA (PFL)' +
                '</div>' +
                '<div style="width:22%;">' +
                '<div style="border-top:1.5px solid #000000;margin-bottom:3px;"></div>' +
                'Manager/GM (PFL)' +
                '</div>' +
                '<div style="width:22%;">' +
                '<div style="border-top:1.5px solid #000000;margin-bottom:3px;"></div>' +
                'Manager/DGM QA (PFL)' +
                '</div>' +
                '</div>' +

                '</div>';
        }

        function openReportModal(idOrRoll) {
            var row = null;
            for (var i = 0; i < currentData.length; i++) {
                if (String(currentData[i].KITID) === String(idOrRoll) || 
                    String(currentData[i].ROLL) === String(idOrRoll) || 
                    String(currentData[i].RAW_ROLL) === String(idOrRoll)) { 
                    row = currentData[i]; 
                    break; 
                }
            }
            if (!row) { alert('Data not found for this roll.'); return; }
            activeReportRow = row;
            $('#modalReportContent').html(renderReportHTML(row));
            var qBox = document.getElementById('modalReportQrBox');
            if (qBox && typeof QRCode !== 'undefined') {
                new QRCode(qBox, {
                    text: String(row.ROLL || ''),
                    width: 38,
                    height: 38,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
            $('#reportModal').css('display', 'block');
        }

        function closeReportModal() {
            $('#reportModal').css('display', 'none');
            activeReportRow = null;
        }

        function getRowDefectsList(row) {
            var defects = [];
            var faultWeights = [
                { k: 'TT', l: 'T&T (+1p)' },
                { k: 'PATTA', l: 'Patta (+1p)' },
                { k: 'SLUB', l: 'Slub (+1p)' },
                { k: 'YC_SPOT', l: 'Y/C Spot (+1p)' },
                { k: 'OILSPOT', l: 'Oil Spot (+2p)' },
                { k: 'FF', l: 'F/F (+2p)' },
                { k: 'SEEDS', l: 'Seeds (+2p)' },
                { k: 'MSTITCH', l: 'Miss Stitch (+2p)' },
                { k: 'SINKERMARK', l: 'Sinker Mark (+3p)' },
                { k: 'NEEDLEMARK', l: 'Needle Mark (+3p)' },
                { k: 'LYCOUT', l: 'Lycra Out (+3p)' },
                { k: 'OILLINE', l: 'Oil Line (+3p)' },
                { k: 'HOLE', l: 'Hole (+4p)' },
                { k: 'LOOP', l: 'Loop (+4p)' },
                { k: 'SETUP', l: 'Set Up (+4p)' },
                { k: 'CMARK', l: 'Crease Mark (+4p)' }
            ];
            faultWeights.forEach(function(fw) {
                var v = parseInt(row[fw.k]) || 0;
                if (v > 0) defects.push(fw.l);
            });
            return defects.length > 0 ? defects.join(', ') : 'ASTM 4-Point Criteria';
        }

        var activeRejectionCardRow = null;

        function renderKnittingProductionCardHTML(row, rollCode, qty) {
            var fieldHTML = [
                ['Shift', row.SHIFT],
                ['OP NAME', row.UNAME || row.UID],
                ['SONO', row.SONO],
                ['BUYER', row.BUYER],
                ['Style', row.STYLE],
                ['MCNO', row.MCNO],
                ['MC Dia', row.MC_DIA],
                ['Customer', row.CUSTOMER],
                ['FGSM', row.FGSM || row.FINISH_GSM],
                ['F. DIA', row.FDIA || row.FINISH_DIA],
                ['Color', row.COLOR, 'vertical'],
                ['F. TYPE', row.FTYPE || row.FABRICS_TYPE, 'vertical'],
                ['Y. COUNT', row.YCOUNT || row.YARN_COUNT, 'vertical'],
                ['Y. BRAND', row.YBRAND, 'vertical'],
                ['Y. LOT', row.LOTNO || row.YARN_LOT, 'vertical'],
                ['CATEGORY', row.CATEGORY, 'vertical']
            ].map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                return '<div class="pdf-item' + (f[2] ? ' pdf-' + f[2] : '') + '" style="font-size:27px;font-weight:800;line-height:1.15;margin-left:3px;padding:3px 0;word-break:break-word;background:#ffffff;color:#000000;' + (f[2] ? 'grid-column:1 / -1;' : 'grid-column:span 2;') + '"><span style="font-weight:800;color:#000000;">' + f[0] + ':</span> <span style="font-weight:800;color:#000000;">' + esc(val) + '</span></div>';
            }).join('');

            return '' +
                '<div id="reportCardPrintBox" style="width:700px;height:700px;padding:4px;background:white;font-family:Arial,Helvetica,sans-serif;color:#000000;box-sizing:border-box;border:2px solid #000000;font-weight:800;text-transform:uppercase;display:flex;flex-direction:column;margin:0 auto;">' +
                '<div style="display:flex;gap:4px;align-items:stretch;margin-bottom:4px;">' +
                '<div style="flex:1;min-height:215px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;background:#ffffff;border:2px solid #000000;padding:6px;box-sizing:border-box;">' +
                '<div style="background:white;color:#000000;text-align:center;font-size:30px;font-weight:800;padding:2px 0;margin-bottom:8px;letter-spacing:0;white-space:nowrap;display:inline-block;">' +
                '<span style="text-decoration:none;">PURBANI FABRICS LTD.</span>' +
                '<span style="display:block;width:100%;height:3px;background:#000000;margin-top:2px;"></span>' +
                '</div>' +
                '<div style="font-weight:800;color:#000000;line-height:1.3;word-break:break-word;">' +
                '<div style="font-size:26px;">ROLL: ' + esc(rollCode) + '</div>' +
                '<div style="font-size:25px;">QTY: ' + esc(qty) + '</div>' +
                '<div style="font-size:24px;">PO NO: ' + esc(row.PO_NUMBER || '') + '</div>' +
                '<div style="font-size:22px;">DATE: ' + esc(row.BUDAT || row.P_CREATED || '') + '</div>' +
                '</div>' +
                '</div>' +
                '<div id="reportRejQrTarget" style="flex:none;width:200px;min-height:200px;height:auto;align-self:stretch;box-sizing:border-box;display:flex;align-items:center;justify-content:center;border:2px solid #000000;background:#ffffff;"></div>' +
                '</div>' +
                '<div style="display:grid;grid-template-columns:repeat(4,1fr);column-gap:8px;row-gap:2px;background:#ffffff;">' + fieldHTML + '</div>' +
                '</div>';
        }

        function openRejectionQrFromReport(idOrRoll) {
            var row = null;
            for (var i = 0; i < currentData.length; i++) {
                if (String(currentData[i].KITID) === String(idOrRoll) || 
                    String(currentData[i].ROLL) === String(idOrRoll) || 
                    String(currentData[i].RAW_ROLL) === String(idOrRoll)) { 
                    row = currentData[i]; 
                    break; 
                }
            }
            if (!row) { alert('Data not found for this roll.'); return; }
            activeRejectionCardRow = row;

            var rollCode = row.ROLL || row.RAW_ROLL || '';
            var rejectQty = row.REJECT_QTY || row.RQTY || row.QTY || '0.00';

            $('#repModalBadge').html('<i class="fa-solid fa-triangle-exclamation me-1"></i> KNITTING REJECTION QR LABEL (KNITTING PRODUCTION FORMAT)').css('background', '#fee2e2').css('color', '#b91c1c');
            $('#repRejModalTitle').text('Roll #' + rollCode + ' (' + rejectQty + ' KG)');

            var html = renderKnittingProductionCardHTML(row, rollCode, rejectQty);

            $('#rejectionCardReportContent').html(html);
            var qTarget = document.getElementById('reportRejQrTarget');
            if (qTarget && typeof QRCode !== 'undefined') {
                new QRCode(qTarget, {
                    text: String(rollCode),
                    width: 194,
                    height: 194,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
            $('#rejectionLabelModal').css('display', 'flex');
        }

        function openPassQrFromReport(idOrRoll) {
            var row = null;
            for (var i = 0; i < currentData.length; i++) {
                if (String(currentData[i].KITID) === String(idOrRoll) || 
                    String(currentData[i].ROLL) === String(idOrRoll) || 
                    String(currentData[i].RAW_ROLL) === String(idOrRoll)) { 
                    row = currentData[i]; 
                    break; 
                }
            }
            if (!row) { alert('Data not found for this roll.'); return; }
            activeRejectionCardRow = row;

            var rollCode = row.ROLL || row.RAW_ROLL || '';
            var goodQty = row.UPDATE_QTY || row.UQTY || row.MAIN_QTY || row.QTY || '0.00';

            $('#repModalBadge').html('<i class="fa-solid fa-circle-check me-1"></i> KNITTING PASS QR LABEL (KNITTING PRODUCTION FORMAT)').css('background', '#dcfce7').css('color', '#166534');
            $('#repRejModalTitle').text('Roll #' + rollCode + ' (' + goodQty + ' KG)');

            var html = renderKnittingProductionCardHTML(row, rollCode, goodQty);

            $('#rejectionCardReportContent').html(html);
            var qTarget = document.getElementById('reportRejQrTarget');
            if (qTarget && typeof QRCode !== 'undefined') {
                new QRCode(qTarget, {
                    text: String(rollCode),
                    width: 194,
                    height: 194,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
            $('#rejectionLabelModal').css('display', 'flex');
        }

        function closeRejectionLabelModal() {
            $('#rejectionLabelModal').css('display', 'none');
            activeRejectionCardRow = null;
        }

        // Same output as Knitting Production: render the 700x700 card to an image
        // with html2canvas, then print it at exactly 6cm x 6cm (1cm from top-left of A4).
        function printCardDirectly(cardElement, title) {
            if (!cardElement) return;

            var clone = cardElement.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.display = 'flex';
            clone.style.flexDirection = 'column';
            clone.style.margin = '0';
            clone.style.transform = 'none';

            // qrcode.js puts both a <canvas> and an <img> in the box -> keep exactly one 194px QR image
            var origQr = cardElement.querySelector('#reportRejQrTarget');
            var cloneQr = clone.querySelector('#reportRejQrTarget');
            if (origQr && cloneQr) {
                cloneQr.removeAttribute('id');
                var dataUrl = '';
                var oImg = origQr.querySelector('img');
                if (oImg && oImg.src && oImg.src.indexOf('data:image') === 0) {
                    dataUrl = oImg.src;
                } else {
                    var oCanvas = origQr.querySelector('canvas');
                    if (oCanvas) { try { dataUrl = oCanvas.toDataURL('image/png'); } catch (e) {} }
                }
                cloneQr.innerHTML = '';
                if (dataUrl) {
                    var qImg = document.createElement('img');
                    qImg.src = dataUrl;
                    qImg.alt = 'QR Code';
                    qImg.style.cssText = 'width:194px;height:194px;display:block;margin:0 auto;';
                    cloneQr.appendChild(qImg);
                }
            }

            var holder = document.createElement('div');
            holder.style.position = 'absolute';
            holder.style.left = '-9999px';
            holder.style.top = '0';
            holder.style.background = '#ffffff';
            holder.appendChild(clone);
            document.body.appendChild(holder);

            if (typeof html2canvas === 'undefined') {
                document.body.removeChild(holder);
                alert('Print library (html2canvas) not loaded. Please check internet connection.');
                return;
            }

            setTimeout(function() {
                html2canvas(clone, {
                    scale: 3,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(function(canvas) {
                    document.body.removeChild(holder);
                    var imgData = canvas.toDataURL('image/png');

                    var old = document.getElementById('print_card_iframe');
                    if (old && old.parentNode) old.parentNode.removeChild(old);
                    var iframe = document.createElement('iframe');
                    iframe.id = 'print_card_iframe';
                    iframe.style.position = 'fixed';
                    iframe.style.right = '0';
                    iframe.style.bottom = '0';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = '0';
                    document.body.appendChild(iframe);

                    var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + (title || 'QR Card Print') + '</title>' +
                        '<style>' +
                        '@page { size: A4 portrait; margin: 0; }' +
                        'html, body { margin: 0 !important; padding: 0 !important; background: #ffffff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
                        '.lbl-page { position: relative; width: 210mm; height: 296mm; overflow: hidden; }' +
                        '.lbl-page img { position: absolute; left: 1cm; top: 1cm; width: 6cm; height: 6cm; display: block; margin: 0; padding: 0; }' +
                        '</style></head><body><div class="lbl-page"><img src="' + imgData + '" alt="QR Label" /></div><\/body><\/html>';

                    var doc = iframe.contentWindow.document;
                    doc.open();
                    doc.write(html);
                    doc.close();

                    var fired = false;
                    function go() {
                        if (fired) return;
                        fired = true;
                        setTimeout(function() {
                            try {
                                iframe.contentWindow.focus();
                                iframe.contentWindow.print();
                            } catch (e) {
                                console.error('Iframe print error:', e);
                            }
                        }, 200);
                    }
                    var lbl = doc.images[0];
                    if (!lbl || lbl.complete) { go(); }
                    else { lbl.onload = lbl.onerror = go; }
                    setTimeout(go, 1500);
                }).catch(function(err) {
                    console.error('Render error:', err);
                    if (holder.parentNode) document.body.removeChild(holder);
                    alert('Error preparing print. Please try again.');
                });
            }, 150);
        }

        function printRejectionCardFromReport() {
            var card = document.getElementById('reportCardPrintBox');
            if (!card) return;
            printCardDirectly(card, 'Knitting Production QR Card');
        }

        function printModalReport() {
            window.print();
        }

        function downloadModalPDF() {
            if (!activeReportRow) return;
            downloadPDFByRow(activeReportRow);
        }

        function downloadPDF(idOrRoll) {
            openReportModal(idOrRoll);
        }

        function downloadPDFByRow(row) {
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = renderReportHTML(row);
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '0';
            document.body.appendChild(tempDiv);

            showToast('Generating PDF...');
            html2canvas(tempDiv, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false
            }).then(function(canvas) {
                var imgData = canvas.toDataURL('image/png');
                var jsPDFLib = window.jspdf;
                var pdf = new jsPDFLib.jsPDF('l', 'mm', 'a4');
                var pdfWidth = pdf.internal.pageSize.getWidth();
                var pageHeight = pdf.internal.pageSize.getHeight();
                var margin = 6;
                var printW = pdfWidth - (margin * 2);
                var printH = pageHeight - (margin * 2);
                var pdfHeight = (canvas.height * printW) / canvas.width;
                
                if (pdfHeight > printH) {
                    var scaledWidth = (canvas.width * printH) / canvas.height;
                    pdf.addImage(imgData, 'PNG', margin + (printW - scaledWidth) / 2, margin, scaledWidth, printH);
                } else {
                    pdf.addImage(imgData, 'PNG', margin, margin, printW, pdfHeight);
                }

                pdf.save('Knitting_Inspection_' + (row.ROLL || 'Roll') + '.pdf');
                document.body.removeChild(tempDiv);
                showToast('PDF downloaded successfully');
            }).catch(function(err) {
                console.error('PDF generation error:', err);
                alert('Error generating PDF. Please try again.');
                if (document.body.contains(tempDiv)) {
                    document.body.removeChild(tempDiv);
                }
            });
        }

        function showToast(msg) {
            var t = document.getElementById('toastMsg');
            if (!t) {
                t = document.createElement('div');
                t.id = 'toastMsg';
                t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;box-shadow:0 10px 24px rgba(0,0,0,.25);z-index:99999;opacity:0;transition:opacity .25s;';
                document.body.appendChild(t);
            }
            t.textContent = msg;
            t.style.opacity = '1';
            clearTimeout(t._timer);
            t._timer = setTimeout(function() { t.style.opacity = '0'; }, 2200);
        }

        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            currentData = data || [];
            if (!currentData.length) {
                tbody.append('<tr class="empty-row"><td colspan="48"><i class="fa-regular fa-folder-open me-2"></i>No inspection records found for the selected filters</td></tr>');
                return;
            }
            currentData.forEach(function(row) {
                var tr = $('<tr>');
                var isReject = (row.INSPECTION_CATEGORY === 'Rejection');
                var kitIdEsc = esc(row.KITID);

                // Action Column
                var actHtml = '<div style="display:flex;gap:4px;align-items:center;">';
                actHtml += '<button class="btn-print-row" onclick="openReportModal(\'' + kitIdEsc + '\')"><i class="fa-solid fa-file-invoice"></i> Report</button>';
                if (isReject || parseFloat(row.RQTY || row.REJECT_QTY || 0) > 0) {
                    actHtml += '<button class="btn-qr-action" onclick="openRejectionQrFromReport(\'' + kitIdEsc + '\')" title="Generate & Print Rejection QR Code"><i class="fa-solid fa-qrcode"></i> Rejection QR</button>';
                } else {
                    actHtml += '<button class="btn-qr-action btn-qr-pass" onclick="openPassQrFromReport(\'' + kitIdEsc + '\')" title="Generate & Print Pass QR Code"><i class="fa-solid fa-qrcode"></i> QR</button>';
                }
                actHtml += '</div>';
                tr.append($('<td>').html(actHtml));

                // Loop through columns
                COLS.forEach(function(c) {
                    var val = (row[c.key] === null || row[c.key] === undefined) ? '' : row[c.key];

                    if (c.key === 'ROLL') {
                        // Any roll number with a rejection quantity has "-R" appended, passed has "-P" appended
                        var rollBadge = isReject 
                            ? '<span class="badge-roll-reject">' + esc(val) + '</span>'
                            : '<span class="badge-roll-pass">' + esc(val) + '</span>';
                        tr.append($('<td>').html(rollBadge));
                    } else if (c.key === 'INSPECTION_CATEGORY') {
                        // Category Badge
                        var catBadge = isReject
                            ? '<span class="badge-cat-reject"><i class="fa-solid fa-circle-xmark"></i> Rejection (-R)</span>'
                            : '<span class="badge-cat-pass"><i class="fa-solid fa-circle-check"></i> Pass (-P)</span>';
                        tr.append($('<td>').html(catBadge));
                    } else if (c.key === 'RQTY' || c.key === 'REJECT_QTY') {
                        // Highlight non-zero rejection qty
                        var numRqty = parseFloat(val) || 0;
                        if (numRqty > 0) {
                            tr.append($('<td>').html('<span class="text-reject-qty">' + esc(val) + '</span>'));
                        } else {
                            tr.append($('<td>').text(val));
                        }
                    } else {
                        tr.append($('<td>').text(val));
                    }
                });
                tbody.append(tr);
            });
        }

        function updateStatsBar(stats) {
            if (!stats) return;
            $('#statTotalRolls').text(stats.total_rolls || 0);
            $('#statPassRolls').text(stats.pass_rolls || 0);
            $('#statRejectRolls').text(stats.reject_rolls || 0);
            $('#statMainQty').text((parseFloat(stats.total_main_qty) || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#statRejectQty').text((parseFloat(stats.total_reject_qty) || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#statUpdateQty').text((parseFloat(stats.total_update_qty) || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        }

        function getFilterPayload() {
            return {
                search:    $('#bookingInput').val().trim(),
                category:  $('#categorySelect').val(),
                from_date: $('#fromDateInput').val(),
                to_date:   $('#toDateInput').val(),
                shift:     $('#shiftSelect').val(),
                mcno:      $('#mcnoSelect').val(),
                buyer:     $('#buyerSelect').val()
            };
        }

        function loadInspectionData(silent) {
            if (!silent) {
                $('#tableBody').html('<tr><td colspan="48" class="loading-cell"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading inspection report data...</td></tr>');
            }
            var payload = getFilterPayload();
            $.ajax({
                url: 'ajaxKnittingInspection_Report.php',
                data: payload,
                dataType: 'json',
                method: 'GET'
            })
            .done(function(resp) {
                if (resp && resp.success) {
                    renderTableRows(resp.data);
                    updateStatsBar(resp.stats);
                } else if (!silent) {
                    $('#tableBody').html('<tr class="empty-row"><td colspan="48">No data returned from server</td></tr>');
                }
            })
            .fail(function() {
                if (!silent) {
                    $('#tableBody').html('<tr class="empty-row"><td colspan="48" style="color:#dc2626;"><i class="fa-solid fa-triangle-exclamation me-2"></i>Error loading inspection data</td></tr>');
                }
            })
            .always(function() {
                $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-filter"></i> Apply');
            });
        }

        $(function() {
            // Back button
            $('#backBtn').on('click', function() {
                window.location.href = 'report.php';
            });

            // Category Select change
            $('#categorySelect').on('change', function() {
                loadInspectionData(false);
            });

            // Apply Filters button
            $('#searchBtn').on('click', function() {
                $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Filtering...');
                loadInspectionData(false);
            });

            // Enter key on search input triggers filter
            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) {
                    loadInspectionData(false);
                }
            });

            // Clear / Reset Filters
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                $('#categorySelect').val('all');
                $('#fromDateInput').val('');
                $('#toDateInput').val('');
                $('#shiftSelect').val('all');
                $('#mcnoSelect').val('all');
                $('#buyerSelect').val('all');

                loadInspectionData(false);
            });

            // Auto-filter on change of dropdowns
            $('#shiftSelect, #mcnoSelect, #buyerSelect, #fromDateInput, #toDateInput').on('change', function() {
                loadInspectionData(false);
            });

            // Initial load (default view: all records available)
            loadInspectionData(false);

            // Real-time polling every 6 seconds (if modal is not open)
            setInterval(function() {
                if ($('#reportModal').css('display') === 'none') {
                    loadInspectionData(true);
                }
            }, 6000);
        });
    </script>

</body>

</html>
