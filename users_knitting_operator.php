<?php
include 'config.php';

$users = [];
$res = mysqli_query($db, "SELECT KOTID, OPERATOR_NAME, OPERATOR_ID, OPERATOR_EMAIL, OPERATOR_PASSWORD, CREATED FROM knitting_operator ORDER BY KOTID ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
}

$qcs = [];
$res2 = mysqli_query($db, "SELECT KQCTID, KNITTING_QC_NAME, KNITTING_QC_ID, KNITTING_QC_EMAIL, KNITTING_QC_PASSWORD, CREATED FROM knitting_operator_qc ORDER BY KQCTID ASC");
if ($res2) {
    while ($row = mysqli_fetch_assoc($res2)) {
        $qcs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Operators | User List</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body {
            padding: 16px;
            background: #f1f5f9;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
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
            font-size: 1.9rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #000000;
            color: #ffffff;
            border: 2px solid #000000;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s, color .15s;
            white-space: nowrap;
        }

        .back-btn:hover {
            background: #ffffff;
            color: #000000;
            text-decoration: none;
        }

        .search-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
        }

        .search-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-panel input {
            flex: 1;
            min-width: 220px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1.05rem;
            outline: none;
        }

        .search-panel input:focus {
            border-color: #2563eb;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background .15s;
        }

        .btn-search {
            background: #2563eb;
        }

        .btn-search:hover {
            background: #1d4ed8;
        }

        .btn-clear {
            background: #64748b;
        }

        .btn-clear:hover {
            background: #475569;
        }

        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-scroll {
            overflow-x: auto;
            max-height: calc(100vh - 180px);
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.12rem;
            min-width: 900px;
        }

        thead th {
            background: #1e293b;
            color: #fff;
            padding: 14px 12px;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        tbody td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #eef2ff;
        }

        .row-count {
            margin-top: 12px;
            color: #475569;
            font-weight: 600;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
            margin: 26px 0 12px;
        }

        .section-title .sec-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.05rem;
        }

        .section-title .op-icon { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .section-title .qc-icon { background: linear-gradient(135deg, #16a34a, #15803d); }

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }

        .print-btn:hover {
            background: #15803d;
        }

        .qr-print-area {
            display: none;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body > *:not(.qr-print-area) {
                display: none !important;
            }

            .qr-print-area {
                display: block !important;
                padding: 14px;
                text-align: left;
            }

            .print-qr-label {
                font-family: sans-serif;
                font-weight: 800;
                font-size: 13px;
                color: #000000;
                margin: 0 0 4px 0;
                line-height: 1.2;
            }

            .qr-print-area #qrPrintBox img,
            .qr-print-area #qrPrintBox canvas {
                width: 1in !important;
                height: 1in !important;
                display: block;
            }
        }
    </style>
</head>

<body>

    <div class="head-row">
        <a href="user_management.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <h1><i class="fa-solid fa-user-tie"></i> Knitting All Operators</h1>
    </div>

    <div class="search-panel">
        <div class="search-row">
            <input type="text" id="searchInput" placeholder="Search by Name, ID or Email..." autocomplete="off">
            <button class="btn btn-search" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <button class="btn btn-clear" id="clearBtn"><i class="fa-solid fa-rotate-left"></i> Clear</button>
        </div>
    </div>

    <h2 class="section-title">
        <span class="sec-icon op-icon"><i class="fa-solid fa-user-tie"></i></span> All Knitting Operator
    </h2>

    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>PRINT</th>
                        <th>ID</th>
                        <th>OPERATOR NAME</th>
                        <th>OPERATOR ID</th>
                        <th>EMAIL</th>
                        <th>PASSWORD</th>
                        <th>CREATED</th>
                    </tr>
                </thead>
                <tbody id="userBody"></tbody>
            </table>
        </div>
    </div>

    <div class="row-count" id="rowCount"></div>

    <h2 class="section-title">
        <span class="sec-icon qc-icon"><i class="fa-solid fa-user-shield"></i></span> Knitting All QC
    </h2>

    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>PRINT</th>
                        <th>ID</th>
                        <th>QC NAME</th>
                        <th>QC ID</th>
                        <th>EMAIL</th>
                        <th>PASSWORD</th>
                        <th>CREATED</th>
                    </tr>
                </thead>
                <tbody id="qcBody"></tbody>
            </table>
        </div>
    </div>

    <div class="row-count" id="qcRowCount"></div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        var allUsers = <?php echo json_encode($users); ?>;
        var allQcs = <?php echo json_encode($qcs); ?>;
        var viewUsers = [];
        var viewQcs = [];

        function esc(v) {
            return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderRows(rows, bodyId, type) {
            var html = '';
            rows.forEach(function(u, i) {
                if (type === 'qc') {
                    html += '<tr>' +
                        '<td><button class="print-btn" onclick="showOperatorBadge(' + i + ', \'qc\')"><i class="fa-solid fa-qrcode"></i> Print / View ID</button></td>' +
                        '<td>' + esc(u.KQCTID) + '</td>' +
                        '<td>' + esc(u.KNITTING_QC_NAME) + '</td>' +
                        '<td><strong class="font-monospace text-primary">' + esc(u.KNITTING_QC_ID) + '</strong></td>' +
                        '<td>' + esc(u.KNITTING_QC_EMAIL) + '</td>' +
                        '<td>' + '********' + '</td>' +
                        '<td>' + esc(u.CREATED) + '</td>' +
                        '</tr>';
                } else {
                    html += '<tr>' +
                        '<td><button class="print-btn" onclick="showOperatorBadge(' + i + ', \'op\')"><i class="fa-solid fa-qrcode"></i> Print / View ID</button></td>' +
                        '<td>' + esc(u.KOTID) + '</td>' +
                        '<td>' + esc(u.OPERATOR_NAME) + '</td>' +
                        '<td><strong class="font-monospace text-primary">' + esc(u.OPERATOR_ID) + '</strong></td>' +
                        '<td>' + esc(u.OPERATOR_EMAIL) + '</td>' +
                        '<td>' + '********' + '</td>' +
                        '<td>' + esc(u.CREATED) + '</td>' +
                        '</tr>';
                }
            });
            document.getElementById(bodyId).innerHTML = html;
        }

        function renderTable() {
            renderRows(viewUsers, 'userBody', 'op');
            renderRows(viewQcs, 'qcBody', 'qc');
            document.getElementById('rowCount').textContent = 'Total Operators: ' + viewUsers.length;
            document.getElementById('qcRowCount').textContent = 'Total QC: ' + viewQcs.length;
        }

        function applyFilter() {
            var q = document.getElementById('searchInput').value.trim().toLowerCase();

            if (q === '') {
                viewUsers = allUsers.slice();
                viewQcs = allQcs.slice();
                renderTable();
                return;
            }

            viewUsers = allUsers.filter(function(u) {
                return String(u.OPERATOR_NAME || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.OPERATOR_ID || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.OPERATOR_EMAIL || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.KOTID || '').toLowerCase().indexOf(q) !== -1;
            });

            viewQcs = allQcs.filter(function(u) {
                return String(u.KNITTING_QC_NAME || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.KNITTING_QC_ID || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.KNITTING_QC_EMAIL || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.KQCTID || '').toLowerCase().indexOf(q) !== -1;
            });

            renderTable();
        }

        document.getElementById('searchBtn').addEventListener('click', applyFilter);
        document.getElementById('clearBtn').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            viewUsers = allUsers.slice();
            viewQcs = allQcs.slice();
            renderTable();
        });
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                applyFilter();
            }
        });

        function showOperatorBadge(idx, type) {
            var u, idVal, roleLabel;
            if (type === 'qc') {
                u = viewQcs[idx];
                idVal = u.KNITTING_QC_ID;
                roleLabel = 'Knitting QC';
            } else {
                u = viewUsers[idx];
                idVal = u.OPERATOR_ID;
                roleLabel = 'Knitting Operator';
            }
            if (!u) return;

            var printBox = document.getElementById('qrPrintBox');
            printBox.innerHTML = '';

            var wrap = document.createElement('div');
            var label = document.createElement('div');
            label.className = 'print-qr-label';
            label.textContent = roleLabel;
            var qrHolder = document.createElement('div');
            qrHolder.id = 'printQrInner';
            wrap.appendChild(label);
            wrap.appendChild(qrHolder);
            printBox.appendChild(wrap);

            setTimeout(function() {
                if (qrHolder && typeof QRCode !== 'undefined') {
                    new QRCode(qrHolder, {
                        text: String(idVal || ''),
                        width: 96,
                        height: 96,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
                window.print();
            }, 100);
        }

        viewUsers = allUsers.slice();
        viewQcs = allQcs.slice();
        renderTable();
    </script>

    <!-- PRINT AREA -->
    <div class="qr-print-area">
        <div class="qr-box" id="qrPrintBox"></div>
    </div>

</body>

</html>