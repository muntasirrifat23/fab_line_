<?php
include 'config.php';

$operators = [];
$res = mysqli_query(
    $db,
    'SELECT DOTID, DY_OP_NAME, DY_OP_ID, DY_OP_EMAIL, CREATED
     FROM dyeing_operator
     ORDER BY DOTID ASC'
);

if (!$res) {
    http_response_code(500);
    error_log('Could not load dyeing operators: ' . mysqli_error($db));
    exit('Unable to load dyeing operators. Please try again later.');
}

while ($row = mysqli_fetch_assoc($res)) {
    $operators[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dyeing Operators | User List</title>

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
            gap: 12px;
            margin-bottom: 16px;
        }

        h1 {
            flex: 1;
            margin: 0;
            color: #1e293b;
            font-size: 1.9rem;
            font-weight: 800;
            text-align: center;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 18px;
            border: 2px solid #000;
            border-radius: 8px;
            background: #000;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .back-btn:hover {
            background: #fff;
            color: #000;
            text-decoration: none;
        }

        .search-panel {
            margin-bottom: 16px;
            padding: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
        }

        .search-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .search-panel input {
            flex: 1 1 220px;
            min-width: 0;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1.05rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
        }

        .btn-search { background: #2563eb; }
        .btn-clear { background: #64748b; }

        .table-card {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
        }

        .table-scroll {
            max-height: calc(100vh - 180px);
            overflow: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
            font-size: 1rem;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 14px 12px;
            background: #1e293b;
            color: #fff;
            text-align: left;
            white-space: nowrap;
        }

        tbody td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eef2ff; }

        .row-count {
            margin-top: 12px;
            color: #475569;
            font-weight: 600;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border: 0;
            border-radius: 8px;
            background: #16a34a;
            color: #fff;
            font-size: .95rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .qr-print-area { display: none; }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body > *:not(.qr-print-area) { display: none !important; }
            .qr-print-area { display: block !important; padding: 14px; }
            .print-qr-label {
                margin: 0 0 4px;
                color: #000;
                font-family: sans-serif;
                font-size: 13px;
                font-weight: 800;
                line-height: 1.2;
            }
            .qr-print-area #qrPrintBox img,
            .qr-print-area #qrPrintBox canvas {
                display: block;
                width: 1in !important;
                height: 1in !important;
            }
        }

        @media (max-width: 576px) {
            body { padding: 10px; }
            .head-row { align-items: flex-start; }
            h1 { font-size: 1.35rem; }
            .back-btn { padding: 8px 10px; font-size: .9rem; }
            .search-row { flex-wrap: nowrap; }
            .search-panel input { padding: 10px; font-size: .9rem; }
            .search-row .btn { padding: 9px 10px; font-size: .9rem; }
        }
    </style>
</head>

<body>
    <div class="head-row">
        <a href="user_management.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <h1><i class="fa-solid fa-user-gear"></i> Dyeing Operators</h1>
    </div>

    <div class="search-panel">
        <div class="search-row">
            <input type="text" id="searchInput" placeholder="Search by Name, ID or Email..." autocomplete="off">
            <button type="button" class="btn btn-search" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <button type="button" class="btn btn-clear" id="clearBtn"><i class="fa-solid fa-rotate-left"></i> Clear</button>
        </div>
    </div>

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
                <tbody id="operatorBody"></tbody>
            </table>
        </div>
    </div>

    <div class="row-count" id="rowCount"></div>
    <div class="qr-print-area">
        <div id="qrPrintBox"></div>
    </div>

    <script>
        var allOperators = <?php echo json_encode($operators, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var visibleOperators = allOperators.slice();

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderOperators() {
            var body = document.getElementById('operatorBody');
            if (!visibleOperators.length) {
                body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No dyeing operators found</td></tr>';
            } else {
                body.innerHTML = visibleOperators.map(function(operator, index) {
                    return '<tr>' +
                        '<td><button type="button" class="print-btn" data-index="' + index + '">' +
                        '<i class="fa-solid fa-qrcode"></i> Print / View ID</button></td>' +
                        '<td>' + escapeHtml(operator.DOTID) + '</td>' +
                        '<td>' + escapeHtml(operator.DY_OP_NAME) + '</td>' +
                        '<td><strong class="font-monospace text-primary">' + escapeHtml(operator.DY_OP_ID) + '</strong></td>' +
                        '<td>' + escapeHtml(operator.DY_OP_EMAIL) + '</td>' +
                        '<td>********</td>' +
                        '<td>' + escapeHtml(operator.CREATED) + '</td>' +
                        '</tr>';
                }).join('');
            }
            document.getElementById('rowCount').textContent = 'Total Dyeing Operators: ' + visibleOperators.length;
        }

        function applyFilter() {
            var query = document.getElementById('searchInput').value.trim().toLowerCase();
            visibleOperators = allOperators.filter(function(operator) {
                return ['DOTID', 'DY_OP_NAME', 'DY_OP_ID', 'DY_OP_EMAIL'].some(function(field) {
                    return String(operator[field] == null ? '' : operator[field]).toLowerCase().indexOf(query) !== -1;
                });
            });
            renderOperators();
        }

        function printOperatorId(index) {
            var operator = visibleOperators[index];
            if (!operator) return;
            if (typeof QRCode === 'undefined') {
                alert('QR code library failed to load. Please refresh and try again.');
                return;
            }

            var printBox = document.getElementById('qrPrintBox');
            printBox.innerHTML = '';
            var label = document.createElement('div');
            label.className = 'print-qr-label';
            label.textContent = operator.DY_OP_ID || '';
            var qrHolder = document.createElement('div');
            printBox.appendChild(label);
            printBox.appendChild(qrHolder);

            new QRCode(qrHolder, {
                text: String(operator.DY_OP_ID || ''),
                width: 96,
                height: 96,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
            window.print();
        }

        document.getElementById('operatorBody').addEventListener('click', function(event) {
            var button = event.target.closest('button[data-index]');
            if (button) printOperatorId(Number(button.dataset.index));
        });
        document.getElementById('searchBtn').addEventListener('click', applyFilter);
        document.getElementById('clearBtn').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            visibleOperators = allOperators.slice();
            renderOperators();
        });
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') applyFilter();
        });

        renderOperators();
    </script>
</body>

</html>
