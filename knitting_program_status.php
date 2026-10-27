<?php
include 'config.php';

// ================= AJAX: GET STATUS DATA =================
if (isset($_GET['action']) && $_GET['action'] === 'get_status') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $conditions = [];

    if ($search !== '') {
        $s = mysqli_real_escape_string($db, $search);
        $conditions[] = "(p.PO_NUMBER LIKE '%$s%' OR p.SONO LIKE '%$s%' OR p.BUYER LIKE '%$s%' OR p.STYLE LIKE '%$s%' OR p.COLOR LIKE '%$s%')";
    }

    $where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $q = "SELECT p.KPTID, p.PROGRAM_NO, p.CREATED_DATE AS BUDAT, p.PO_NUMBER, p.SONO, p.BUYER, p.STYLE, p.COLOR, p.CATEGORY,
                 p.FGSM, p.FDIA, p.O_T, p.FTYPE, p.YTYPE, p.CUSTOMER, p.YCOUNT, p.YBRAND, p.SL,
                 p.MCDIA, p.FEEDER_PLAN, p.LOT, p.SHIFT,
                 CAST(p.QTY AS DECIMAL(10,2)) AS QTY,
                 (SELECT CAST(i.QTY AS DECIMAL(10,2)) FROM knitting_input i WHERE i.PO_NUMBER = p.PO_NUMBER LIMIT 1) AS INPUT_QTY,
                 (SELECT CAST(i.QTY AS DECIMAL(10,2)) FROM knitting_input i WHERE i.PO_NUMBER = p.PO_NUMBER LIMIT 1)
                 - (SELECT COALESCE(SUM(CAST(p2.QTY AS DECIMAL(10,2))), 0) FROM knitting_program p2 WHERE p2.PO_NUMBER = p.PO_NUMBER) AS REMAINING_QTY
           FROM knitting_program p
           $where
           ORDER BY p.KPTID DESC";

    $res = mysqli_query($db, $q);

    if (!$res) {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
        exit();
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['REMAINING_QTY'] = round(max(floatval($row['REMAINING_QTY']), 0), 2);
        $row['INPUT_QTY'] = round(floatval($row['INPUT_QTY']), 2);
        $row['QTY'] = round(floatval($row['QTY']), 2);
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Status | Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">
    <style>
        body { padding: 18px; background: #f5f7fa; }
        .panel { background: #fff; padding: 18px; border-radius: 8px; box-shadow: 0 6px 18px rgba(20,30,50,0.06); }
        h1.title { font-weight: 700; color: #1f2937; margin-bottom: 18px; }
        .small-muted { font-size: 12px; color: #6b7280; }
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .stat-card { background: #fff; border-radius: 10px; padding: 16px 18px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .stat-card .stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-bottom: 6px; }
        .stat-card .stat-value { font-size: 26px; font-weight: 800; }
        .stat-card.blue  .stat-value { color: #2563eb; }
        .stat-card.green .stat-value { color: #16a34a; }
        .stat-card.red   .stat-value { color: #dc2626; }
        .stat-card.gray  .stat-value { color: #475569; }
        #backBtn, .back-btn {
            background: #000000 !important;
            color: #ffffff !important;
            border: 2px solid #000000 !important;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        #backBtn:hover, .back-btn:hover {
            background: #ffffff !important;
            border-color: #000000 !important;
            color: #000000 !important;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="back-btn" id="backBtn">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i> Back to Initial Page
            </button>
            <h1 class="title">Knitting Program Status</h1>
            <div></div>
        </div>

        <div class="stat-cards" id="statCards"></div>

        <div class="panel mb-3">
            <label class="form-label fw-semibold" style="font-size:larger;color:black;">Search PO / SO / Buyer / Style</label>
            <div class="input-group input-group-sm d-flex align-items-center gap-2">
                <input type="text" id="searchInput" class="form-control" placeholder="Enter PO Number, SO, Buyer..." autocomplete="off">
                <button class="btn px-4" id="searchBtn" style="margin-top:8px;background:#2563eb;border:1px solid #2563eb;color:#fff;border-radius:8px;">
                    <i class="fa-solid fa-magnifying-glass me-1" style="margin-right:6px;"></i> Search
                </button>
                <button class="btn px-4" id="clearBtn" style="margin-top:8px;margin-left:8px;background:#6b7280;border:1px solid #6b7280;color:#fff;border-radius:8px;">
                    <i class="fa-solid fa-rotate-left me-1" style="margin-right:6px;"></i> Clear
                </button>
            </div>
        </div>

        <div class="panel">
            <div style="overflow-x:auto;">
                <table class="table table-bordered table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>DATE</th>
                            <th>PROGRAM NO</th>
                            <th>PO NUMBER</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>CATEGORY</th>
                            <th>FGSM</th>
                            <th>FDIA</th>
                            <th>O/T</th>
                            <th>F TYPE</th>
                            <th>Y TYPE</th>
                            <th>CUSTOMER</th>
                            <th>Y COUNT</th>
                            <th>Y BRAND</th>
                            <th>SL</th>
                            <th>MC DIA</th>
                            
                            <th>FEEDER PLAN</th>
                            <th>LOT</th>
                            <th>SHIFT</th>
                            <th>INPUT QTY</th>
                            <th>QTY (Program)</th>
                            <th>REMAINING</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr><td colspan="25" class="text-center small-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="jquery.min.js"></script>
    <script>
        function loadData(search) {
            var url = 'knitting_program_status.php?action=get_status';
            if (search) url += '&search=' + encodeURIComponent(search);

            $.get(url, function(resp) {
                if (!resp || !resp.success || !resp.data || resp.data.length === 0) {
                    $('#tableBody').html('<tr><td colspan="25" class="text-center small-muted">No data found</td></tr>');
                    renderStats(0, 0, 0);
                    return;
                }

                var rows = resp.data;
                var tInput = 0, tProg = 0, totalRows = rows.length;
                var inputAdded = {};

                var tbody = '';
                rows.forEach(function(r) {
                    var qty = r.QTY || 0;
                    var input = r.INPUT_QTY || 0;
                    var rem = r.REMAINING_QTY || 0;

                    tProg += qty;
                    if (!inputAdded[r.PO_NUMBER]) { inputAdded[r.PO_NUMBER] = true; tInput += input; }

                    var remClass = rem <= 0 ? 'text-success fw-bold' : (rem < input * 0.3 ? 'text-danger fw-bold' : 'text-warning fw-bold');

                    tbody += '<tr>' +
                        '<td>' + esc(r.BUDAT) + '</td>' +
                        '<td>' + esc(r.PROGRAM_NO) + '</td>' +
                        '<td class="fw-bold">' + esc(r.PO_NUMBER) + '</td>' +
                        '<td>' + esc(r.SONO) + '</td>' +
                        '<td>' + esc(r.BUYER) + '</td>' +
                        '<td>' + esc(r.STYLE) + '</td>' +
                        '<td>' + esc(r.COLOR) + '</td>' +
                        '<td><span class="badge bg-light text-dark border">' + esc(r.CATEGORY || 'N/A') + '</span></td>' +
                        '<td>' + esc(r.FGSM) + '</td>' +
                        '<td>' + esc(r.FDIA) + '</td>' +
                        '<td>' + esc(r.O_T) + '</td>' +
                        '<td>' + esc(r.FTYPE) + '</td>' +
                        '<td>' + esc(r.YTYPE) + '</td>' +
                        '<td>' + esc(r.CUSTOMER) + '</td>' +
                        '<td>' + esc(r.YCOUNT) + '</td>' +
                        '<td>' + esc(r.YBRAND) + '</td>' +
                        '<td>' + esc(r.SL) + '</td>' +
                        '<td>' + esc(r.MCDIA) + '</td>' +
                                                '<td>' + esc(r.FEEDER_PLAN) + '</td>' +
                        '<td>' + esc(r.LOT) + '</td>' +
                        '<td>' + esc(r.SHIFT) + '</td>' +
                        '<td class="text-center">' + input + '</td>' +
                        '<td class="text-center fw-bold text-primary">' + qty + '</td>' +
                        '<td class="text-center ' + remClass + '">' + rem + '</td>' +
                        '</tr>';
                });

                renderStats(totalRows, tInput, tProg);
                $('#tableBody').html(tbody);
            }, 'json').fail(function() {
                    $('#tableBody').html('<tr><td colspan="25" class="text-center text-danger small-muted">Error loading data</td></tr>');
            });
        }

        function renderStats(totalPO, totalInput, totalProg) {
            var totalRem = Math.max(totalInput - totalProg, 0);
            $('#statCards').html(
                '<div class="stat-card blue"><div class="stat-label">Total PO</div><div class="stat-value">' + totalPO + '</div></div>' +
                '<div class="stat-card gray"><div class="stat-label">Total Input QTY</div><div class="stat-value">' + totalInput.toFixed(2) + '</div></div>' +
                '<div class="stat-card green"><div class="stat-label">Total Program QTY</div><div class="stat-value">' + totalProg.toFixed(2) + '</div></div>' +
                '<div class="stat-card red"><div class="stat-label">Total Remaining</div><div class="stat-value">' + totalRem.toFixed(2) + '</div></div>'
            );
        }

        function esc(v) { return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

        $(function() {
            loadData();
            $('#searchBtn').on('click', function() { loadData($('#searchInput').val().trim()); });
            $('#clearBtn').on('click', function() { $('#searchInput').val(''); loadData(); });
            $('#searchInput').on('keyup', function(e) { if (e.key === 'Enter') loadData($(this).val().trim()); });
            $('#backBtn').on('click', function() { history.back(); });
        });
    </script>
</body>
</html>
