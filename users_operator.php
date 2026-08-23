<?php
include 'config.php';

$users = [];
$res = mysqli_query($db, "SELECT KOTID, OPERATOR_NAME, OPERATOR_ID, OPERATOR_EMAIL, OPERATOR_PASSWORD, CREATED FROM knitting_operator ORDER BY KOTID ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
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
                display: flex !important;
                align-items: flex-start;
                justify-content: flex-start;
                padding: 20px;
            }

            .qr-print-area .qr-box {
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <div class="head-row">
        <a href="user_management.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <h1><i class="fa-solid fa-user-tie"></i> All Operators</h1>
    </div>

    <div class="search-panel">
        <div class="search-row">
            <input type="text" id="searchInput" placeholder="Search by Name, ID or Email..." autocomplete="off">
            <button class="btn btn-search" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <button class="btn btn-clear" id="clearBtn"><i class="fa-solid fa-rotate-left"></i> Clear</button>
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
                <tbody id="userBody"></tbody>
            </table>
        </div>
    </div>

    <div class="row-count" id="rowCount"></div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        var allUsers = <?php echo json_encode($users); ?>;

        function esc(v) {
            return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderTable(rows) {
            var body = document.getElementById('userBody');
            var html = '';
            rows.forEach(function(u, i) {
                html += '<tr>' +
                    '<td><button class="print-btn" onclick="showOperatorBadge(' + i + ')"><i class="fa-solid fa-qrcode"></i> Print / View ID</button></td>' +
                    '<td>' + esc(u.KOTID) + '</td>' +
                    '<td>' + esc(u.OPERATOR_NAME) + '</td>' +
                    '<td><strong class="font-monospace text-primary">' + esc(u.OPERATOR_ID) + '</strong></td>' +
                    '<td>' + esc(u.OPERATOR_EMAIL) + '</td>' +
                    '<td>' + '********' + '</td>' +
                    '<td>' + esc(u.CREATED) + '</td>' +
                    '</tr>';
            });
            body.innerHTML = html;
            document.getElementById('rowCount').textContent = 'Total Operators: ' + rows.length;
        }

        function applyFilter() {
            var q = document.getElementById('searchInput').value.trim().toLowerCase();
            if (q === '') {
                renderTable(allUsers);
                return;
            }
            var filtered = allUsers.filter(function(u) {
                return String(u.OPERATOR_NAME || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.OPERATOR_ID || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.OPERATOR_EMAIL || '').toLowerCase().indexOf(q) !== -1 ||
                    String(u.KOTID || '').toLowerCase().indexOf(q) !== -1;
            });
            renderTable(filtered);
        }

        document.getElementById('searchBtn').addEventListener('click', applyFilter);
        document.getElementById('clearBtn').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            renderTable(allUsers);
        });
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                applyFilter();
            }
        });

        function showOperatorBadge(idx) {
            var u = allUsers[idx];
            if (!u) return;

            document.getElementById('modalOpName').textContent = u.OPERATOR_NAME;
            document.getElementById('modalOpId').textContent = u.OPERATOR_ID;

            var qrBox = document.getElementById('modalQrBox');
            qrBox.innerHTML = '';
            if (typeof QRCode !== 'undefined') {
                new QRCode(qrBox, {
                    text: String(u.OPERATOR_ID || ''),
                    width: 180,
                    height: 180,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }

            var printBox = document.getElementById('qrPrintBox');
            printBox.innerHTML = '';
            var printBadge = document.createElement('div');
            printBadge.className = 'badge-print-box';
            printBadge.style.cssText = 'width: 320px; background: #ffffff; padding: 20px; border: 3px solid #000000; border-radius: 12px; text-align: center; color: #000000; font-family: sans-serif;';
            printBadge.innerHTML = `
                <div style="font-weight: 800; font-size: 16px; text-transform: uppercase; color: #000000; margin-bottom: 4px;">Purbani Fabrics Ltd.</div>
                <div style="display: inline-block; background: #000000; color: #ffffff; padding: 3px 14px; font-weight: 700; font-size: 11px; border-radius: 12px; margin-bottom: 14px; text-transform: uppercase;">Knitting Operator Badge</div>
                <div style="background: #ffffff; padding: 14px; border: 2px solid #000000; border-radius: 8px; display: inline-block; margin-bottom: 12px;" id="printQrInner"></div>
                <div style="font-weight: 800; font-size: 18px; color: #000000; margin-bottom: 2px;">${esc(u.OPERATOR_NAME)}</div>
                <div style="font-weight: 800; font-size: 16px; font-family: monospace; color: #1e40af;">ID: ${esc(u.OPERATOR_ID)}</div>
            `;
            printBox.appendChild(printBadge);

            setTimeout(function() {
                var printQrInner = document.getElementById('printQrInner');
                if (printQrInner && typeof QRCode !== 'undefined') {
                    new QRCode(printQrInner, {
                        text: String(u.OPERATOR_ID || ''),
                        width: 200,
                        height: 200,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            }, 100);

            var modalElem = document.getElementById('opBadgeModal');
            if (modalElem && typeof bootstrap !== 'undefined') {
                var myModal = new bootstrap.Modal(modalElem);
                myModal.show();
            }
        }

        function triggerBadgePrint() {
            window.print();
        }

        renderTable(allUsers);
    </script>

    <!-- OPERATOR BADGE & QR MODAL (SCREEN PREVIEW) -->
    <div class="modal fade no-print" id="opBadgeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fs-6 fw-bold mb-0">
                        <i class="fa-solid fa-id-card me-2 text-primary"></i> Operator Badge QR Code
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4" style="background: #f8fafc;">
                    <div id="modalBadgeCard" class="badge-print-box p-3 bg-white border border-2 border-dark rounded-3 shadow-sm mx-auto" style="max-width: 300px;">
                        <div class="text-uppercase fw-extrabold text-dark small mb-1" style="letter-spacing: 0.5px; font-weight: 800;">Purbani Fabrics Ltd.</div>
                        <div class="badge bg-dark text-white px-3 py-1 mb-3 rounded-pill fw-bold text-uppercase" style="font-size: 10px;">Knitting Operator</div>
                        
                        <!-- QR Code Frame with White Quiet Zone -->
                        <div class="qr-frame-box p-3 bg-white border border-2 border-dark rounded-3 d-inline-block shadow-sm mb-3">
                            <div id="modalQrBox"></div>
                        </div>

                        <div class="fw-bold text-dark fs-5 mb-0" id="modalOpName">Md. Rahim</div>
                        <div class="font-monospace text-primary fw-bold fs-6" id="modalOpId">OP01</div>
                    </div>
                    <div class="text-muted small mt-3" style="font-size: 11px;">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i> Point your camera at this QR code in <strong>Knitting Inspection</strong> page to authenticate.
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary px-3 btn-sm fw-bold" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success px-4 btn-sm fw-bold" onclick="triggerBadgePrint()">
                        <i class="fa-solid fa-print me-1"></i> Print Badge
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PRINT AREA -->
    <div class="qr-print-area">
        <div class="qr-box" id="qrPrintBox"></div>
    </div>

</body>

</html>