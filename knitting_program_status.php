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

    $q = "SELECT p.KPTID, p.PROGRAM_NO, p.CREATED_DATE AS BUDAT, p.PO_NUMBER, p.SONO, p.BUYER, p.STYLE, p.COLOR,
                 p.FGSM, p.FDIA, p.O_T, p.FTYPE, p.YTYPE, p.CUSTOMER, p.YCOUNT, p.SL,
                 p.MCDIA, p.GGSM, p.FEEDER_PLAN, p.LOT, p.SHIFT,
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
        .pdf-btn { display: inline-flex; align-items: center; gap: 5px; background: #16a34a; color: #fff; border: none; border-radius: 6px; padding: 5px 10px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .pdf-btn:hover { background: #15803d; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-dark" id="backBtn" style="background:#1f2937;color:#fff;padding:12px;border-radius:8px;">
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
                            <th>PDF</th>
                            <th>DATE</th>
                            <th>PROGRAM NO</th>
                            <th>PO NUMBER</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>FGSM</th>
                            <th>FDIA</th>
                            <th>O/T</th>
                            <th>F TYPE</th>
                            <th>Y TYPE</th>
                            <th>CUSTOMER</th>
                            <th>Y COUNT</th>
                            <th>SL</th>
                            <th>MC DIA</th>
                            <th>GGSM</th>
                            <th>FEEDER PLAN</th>
                            <th>LOT</th>
                            <th>SHIFT</th>
                            <th>INPUT QTY</th>
                            <th>QTY (Program)</th>
                            <th>REMAINING</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr><td colspan="24" class="text-center small-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function loadData(search) {
            var url = 'knitting_program_status.php?action=get_status';
            if (search) url += '&search=' + encodeURIComponent(search);

            $.get(url, function(resp) {
                if (!resp || !resp.success || !resp.data || resp.data.length === 0) {
                    $('#tableBody').html('<tr><td colspan="24" class="text-center small-muted">No data found</td></tr>');
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
                        '<td class="text-center"><button class="pdf-btn" data-idx="' + rows.indexOf(r) + '"><i class="fa-solid fa-file-pdf"></i> PDF</button></td>' +
                        '<td>' + esc(r.BUDAT) + '</td>' +
                        '<td>' + esc(r.PROGRAM_NO) + '</td>' +
                        '<td class="fw-bold">' + esc(r.PO_NUMBER) + '</td>' +
                        '<td>' + esc(r.SONO) + '</td>' +
                        '<td>' + esc(r.BUYER) + '</td>' +
                        '<td>' + esc(r.STYLE) + '</td>' +
                        '<td>' + esc(r.COLOR) + '</td>' +
                        '<td>' + esc(r.FGSM) + '</td>' +
                        '<td>' + esc(r.FDIA) + '</td>' +
                        '<td>' + esc(r.O_T) + '</td>' +
                        '<td>' + esc(r.FTYPE) + '</td>' +
                        '<td>' + esc(r.YTYPE) + '</td>' +
                        '<td>' + esc(r.CUSTOMER) + '</td>' +
                        '<td>' + esc(r.YCOUNT) + '</td>' +
                        '<td>' + esc(r.SL) + '</td>' +
                        '<td>' + esc(r.MCDIA) + '</td>' +
                        '<td>' + esc(r.GGSM) + '</td>' +
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

                $('#tableBody .pdf-btn').off('click').on('click', function() {
                    var idx = parseInt($(this).data('idx'));
                    downloadRowPdf(rows[idx]);
                });
            }, 'json').fail(function() {
                $('#tableBody').html('<tr><td colspan="24" class="text-center text-danger small-muted">Error loading data</td></tr>');
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

        function downloadRowPdf(r) {
            var fieldHTML = [
                ['Program No', r.PROGRAM_NO],
                ['PO Number', r.PO_NUMBER],
                ['SONO', r.SONO],
                ['Buyer', r.BUYER],
                ['Style', r.STYLE],
                ['Color', r.COLOR],
                ['Date', r.BUDAT],
                ['FGSM', r.FGSM],
                ['FDIA', r.FDIA],
                ['O/T', r.O_T],
                ['F Type', r.FTYPE],
                ['Y Type', r.YTYPE],
                ['Customer', r.CUSTOMER],
                ['Y Count', r.YCOUNT],
                ['SL', r.SL],
                ['MC Dia', r.MCDIA],
                ['GGSM', r.GGSM],
                ['Feeder Plan', r.FEEDER_PLAN],
                ['Lot', r.LOT],
                ['Shift', r.SHIFT],
                ['Input QTY', r.INPUT_QTY],
                ['QTY (Program)', r.QTY],
                ['Remaining QTY', r.REMAINING_QTY]
            ].map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                return '<div class="pdf-item"><span class="pdf-label">' + f[0] + ':</span> <span class="pdf-value">' + esc(val) + '</span></div>';
            }).join('');

            var rem = parseFloat(r.REMAINING_QTY) || 0;
            var remColor = rem <= 0 ? '#16a34a' : '#dc2626';

            var content = '' +
                '<div id="rowPdfCard" style="width:700px;padding:16px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#000000;box-sizing:border-box;border:2px solid #94a3b8;">' +
                '<div style="background:#1e3a8a;color:#ffffff;text-align:center;font-size:24px;font-weight:800;padding:8px;border-radius:6px;margin-bottom:12px;letter-spacing:1px;">PURBANI FABRICS LTD.</div>' +
                '<div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:12px;">' +
                '<div style="flex:1;min-width:0;">' +
                '<div style="background:#f1f5f9;border:2px solid #1e3a8a;border-radius:8px;padding:10px 14px;margin-bottom:8px;">' +
                '<div style="font-size:17px;font-weight:800;color:#1e3a8a;margin-bottom:4px;">PO NUMBER</div>' +
                '<div style="font-size:30px;font-weight:800;color:#000000;font-family:Consolas,monospace;line-height:1.2;">' + esc(r.PO_NUMBER) + '</div></div>' +
                '<div style="display:flex;gap:8px;">' +
                '<div style="flex:1;background:#f0fdf4;border:2px solid #16a34a;border-radius:8px;padding:8px 12px;text-align:center;">' +
                '<div style="font-size:11px;font-weight:800;color:#16a34a;">PROGRAM QTY</div>' +
                '<div style="font-size:20px;font-weight:800;color:#16a34a;">' + esc(r.QTY) + '</div></div>' +
                '<div style="flex:1;background:#fef2f2;border:2px solid ' + remColor + ';border-radius:8px;padding:8px 12px;text-align:center;">' +
                '<div style="font-size:11px;font-weight:800;color:' + remColor + ';">REMAINING</div>' +
                '<div style="font-size:20px;font-weight:800;color:' + remColor + ';">' + esc(r.REMAINING_QTY) + '</div></div>' +
                '</div></div>' +
                '<div id="rowQrBox" style="flex:none;width:186px;height:186px;display:flex;align-items:center;justify-content:center;border:2px solid #000000;border-radius:8px;background:#ffffff;"></div>' +
                '</div>' +
                '<div class="pdf-grid">' + fieldHTML + '</div></div>';

            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '0';
            document.body.appendChild(tempDiv);

            var qrBox = tempDiv.querySelector('#rowQrBox');
            if (qrBox && typeof QRCode !== 'undefined') {
                new QRCode(qrBox, { text: String(r.PO_NUMBER), width: 180, height: 180, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H });
            }

            if (!document.getElementById('statusPdfStyle')) {
                var s = document.createElement('style');
                s.id = 'statusPdfStyle';
                s.textContent = '.pdf-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border:2px solid #94a3b8;border-radius:6px;background:#ffffff;}' +
                    '.pdf-item{font-size:14px;line-height:1.3;border-bottom:1px solid #cbd5e1;border-right:1px solid #cbd5e1;padding:4px 8px;word-break:break-word;background:#ffffff;color:#000000;}' +
                    '.pdf-item:nth-child(2n){border-right:none;}' +
                    '.pdf-item:nth-last-child(-n+2){border-bottom:none;}' +
                    '.pdf-label{font-weight:800;color:#1e3a8a;}' +
                    '.pdf-value{color:#111111;}';
                document.body.appendChild(s);
            }

            setTimeout(function() {
                html2canvas(tempDiv, { scale: 3, useCORS: true, backgroundColor: '#ffffff', logging: false }).then(function(canvas) {
                    var jsPDFLib = window.jspdf;
                    var pdf = new jsPDFLib.jsPDF({ orientation: 'portrait', unit: 'cm', format: 'a4' });
                    pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, 6, 6);
                    pdf.save('Program_Status_' + r.PO_NUMBER + '_' + r.PROGRAM_NO + '.pdf');
                    document.body.removeChild(tempDiv);
                }).catch(function(err) {
                    console.error(err);
                    document.body.removeChild(tempDiv);
                });
            }, 400);
        }

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
