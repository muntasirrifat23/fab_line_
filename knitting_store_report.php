<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | Knitting Store Report</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">

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

        #backBtn {
            background: #000000 !important;
            color: #ffffff !important;
            border: 2px solid #000000 !important;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        #backBtn:hover {
            background: #ffffff !important;
            border-color: #000000 !important;
            color: #000000 !important;
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
            min-width: 2400px;
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
            z-index: 5;
        }

        thead th:first-child {
            left: 0;
            z-index: 8;
        }

        tbody td {
            padding: 12px;
            border-bottom: 1px solid #eef2f7;
            white-space: nowrap;
            color: #1e293b;
        }

        tbody td:first-child {
            position: sticky;
            left: 0;
            background: #ffffff;
            z-index: 3;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #eff6ff;
        }

        .action-btn {
            border: none;
            color: #fff;
            border-radius: 6px;
            padding: 7px 13px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin: 2px;
        }

        .btn-print-row {
            background: #16a34a;
        }

        .btn-print-row:hover {
            background: #15803d;
        }

        .btn-view-row {
            background: #2563eb;
        }

        .btn-view-row:hover {
            background: #1d4ed8;
        }

        /* ---- Printable card styles (shared by preview & print) ---- */
        .pdf-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            padding: 0;
            background: #ffffff;
        }

        .pdf-item {
            font-size: 13px;
            line-height: 1.7;
            border-bottom: 1px solid #d1d5db;
            border-right: 1px solid #d1d5db;
            padding: 7px 12px;
            word-break: break-word;
            background: #ffffff;
            color: #000000;
        }

        .pdf-item:nth-child(2n) {
            border-right: none;
        }

        .pdf-label {
            font-weight: bold;
            color: #000000;
        }

        .pdf-value {
            margin-left: 8px;
            color: #000000;
        }

        .pdf-sign {
            position: absolute;
            left: 24px;
            right: 24px;
            bottom: 55px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 20px;
            padding: 0 10px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: #000000;
        }

        .pdf-sign-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .pdf-sign-line {
            width: 80%;
            border-top: 1px solid #000000;
            margin-top: 0;
        }

        /* ---- Preview modal ---- */
        .preview-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1050;
            padding: 20px;
        }

        .preview-overlay.show {
            display: flex;
        }

        .preview-box {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 820px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .preview-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #1e293b;
            color: #fff;
        }

        .preview-head h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .preview-close {
            background: #ef4444;
            color: #fff;
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .preview-close:hover {
            background: #dc2626;
        }

        .preview-body {
            overflow: auto;
            background: #eef1f5;
            padding: 16px;
        }

        .preview-body img {
            display: block;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            background: #fff;
        }

        .preview-loading {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            padding: 40px 0;
        }

        .preview-foot {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .empty-row td,
        .loading-cell {
            text-align: center;
            padding: 28px;
            color: #94a3b8;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .search-row {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid p-0">

        <div class="head-row">
            <button class="btn" id="backBtn">
                <i class="fa-solid fa-arrow-left" ></i> Back to Report
            </button>
            <h1 style="font-size:xx-large;">Knitting Store Report</h1>
        </div>

        <div class="search-panel">
            <div class="search-row">
                <input type="text" id="bookingInput" placeholder="Search Roll / PO Number / SONO / Buyer / Style / Rack">
                <button class="btn btn-search" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                <button class="btn btn-clear" id="clearBtn"><i class="fa-solid fa-rotate-left"></i> Clear</button>
            </div>
        </div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>KSTID</th>
                            <th>DATE</th>
                            <th>RACK NO</th>
                            <th>LOCATION</th>
                            <th>ROLL NO</th>
                            <th>PO NUMBER</th>
                            <th>QTY</th>
                            <th>SONO</th>
                            <th>SHIFT</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MACHINE NO</th>
                            <th>MC DIA</th>
                            <th>CUSTOMER</th>
                            <th>YARN TYPE</th>
                            <th>YARN COUNT</th>
                            <th>YARN BRAND</th>
                            <th>OPEN / TUBE</th>
                            <th>SL/VQ</th>
                            <th>FABRICS TYPE</th>
                            <th>FINISH GSM</th>
                            <th>FINISH DIA</th>
                            <th>FEEDER PLAN</th>
                            <th>LOT NO</th>
                            <th>T POINT</th>
                            <th>UNAME</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="28" class="loading-cell">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="preview-overlay" id="previewOverlay">
        <div class="preview-box">
            <div class="preview-head">
                <h5><i class="fa-solid fa-eye"></i> Print Preview</h5>
                <button type="button" class="preview-close" id="previewClose" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="preview-body" id="previewBody"></div>
            <div class="preview-foot">
                <button type="button" class="action-btn btn-print-row" id="previewPrint">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>

    <script src="jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        var COLS = [
            { key: 'KSTID', label: 'KSTID' },
            { key: 'BUDAT', label: 'Date' },
            { key: 'RACKNO', label: 'Rack No' },
            { key: 'RACKLOCATION', label: 'Location' },
            { key: 'ROLL', label: 'Roll No' },
            { key: 'PO_NUMBER', label: 'PO Number' },
            { key: 'QTY', label: 'QTY' },
            { key: 'SONO', label: 'SONO' },
            { key: 'SHIFT', label: 'Shift' },
            { key: 'BUYER', label: 'Buyer' },
            { key: 'STYLE', label: 'Style' },
            { key: 'COLOR', label: 'Color' },
            { key: 'MCNO', label: 'Machine No' },
            { key: 'MCDIA', label: 'MC Dia' },
            { key: 'CUSTOMER', label: 'Customer' },
            { key: 'YTYPE', label: 'Yarn Type' },
            { key: 'YCOUNT', label: 'Yarn Count' },
            { key: 'YBRAND', label: 'Yarn Brand' },
            { key: 'O_T', label: 'Open / Tube' },
            { key: 'SL', label: 'SL/VQ' },
            { key: 'FTYPE', label: 'Fabrics Type' },
            { key: 'FGSM', label: 'Finish GSM' },
            { key: 'FDIA', label: 'Finish Dia' },
            { key: 'FEEDER_PLAN', label: 'Feeder Plan' },
            { key: 'LOT_NO', label: 'Lot No' },
            { key: 'TPOINT', label: 'T Point' },
            { key: 'UNAME', label: 'Uname' }
        ];

        // PDF/print excludes these fields (per requirement)
        var PDF_EXCLUDE = ['KSTID', 'UNAME'];
        var PDF_COLS = COLS.filter(function(c) {
            return PDF_EXCLUDE.indexOf(c.key) === -1;
        });

        var currentData = [];

        function esc(v) {
            if (v === null || v === undefined) return '';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function buildRowCardElement(row) {
            var rowsHTML = PDF_COLS.map(function(c) {
                var val = row[c.key];
                if (val === null || val === undefined) val = '';
                return '<div class="pdf-item"><span class="pdf-label">' + c.label + ' :</span><span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="position:relative;width:760px;min-height:1050px;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">' +
                '<div style="text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a;border-bottom:3px solid #2563eb;padding-bottom:10px;margin-bottom:16px;">' +
                'Knitting Store Report' +
                '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;font-size:14px;font-weight:bold;margin-bottom:10px; margin-left:10px;">' +
                '<span>Roll : ' + (row.ROLL || '') + '</span>' +
                '</div>' +
                '<div class="pdf-grid">' + rowsHTML + '</div>' +
                '<div style="text-align:center;font-size:11px;color: black; margin-top:16px;border-top:1px solid #e5e7eb;padding-top:8px;">' +
                'Generated from Knitting Store Report - ' + new Date().toLocaleString() +
                '</div>' +

                '<div class="pdf-sign">' +
                '<div class="pdf-sign-item"><div class="pdf-sign-line"></div><span>Supervisor</span></div>' +
                '<div class="pdf-sign-item"><div class="pdf-sign-line"></div><span>Incharge</span></div>' +
                '<div class="pdf-sign-item"><div class="pdf-sign-line"></div><span>AGM</span></div>' +
                '<div class="pdf-sign-item"><div class="pdf-sign-line"></div><span>GM</span></div>' +
                '</div>' +

                '</div>';

            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '0';
            document.body.appendChild(tempDiv);

            return tempDiv;
        }

        function renderCardImage(row, done, fail) {
            var card = buildRowCardElement(row);

            setTimeout(function() {
                html2canvas(card, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(function(canvas) {
                    if (card.parentNode) card.parentNode.removeChild(card);
                    done(canvas.toDataURL('image/png'));
                }).catch(function(err) {
                    console.error('Render error:', err);
                    if (card.parentNode) card.parentNode.removeChild(card);
                    if (fail) fail(err);
                });
            }, 150);
        }

        function printRow(row) {
            renderCardImage(row, function(imgData) {
                var iframe = document.createElement('iframe');
                iframe.setAttribute('aria-hidden', 'true');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);

                // Identical to the old PDF: A4 portrait, full-width card image at top-left.
                var doc = iframe.contentWindow.document;
                doc.open();
                doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Knitting Store Report</title><style>' +
                    '@page{size:A4 portrait;margin:0;}' +
                    'html,body{margin:0;padding:0;background:#fff;}' +
                    'img{display:block;width:210mm;height:auto;}' +
                    '</style></head><body>' +
                    '<img src="' + imgData + '">' +
                    '</body></html>');
                doc.close();

                var doPrint = function() {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };

                var printImg = doc.querySelector('img');
                if (printImg && !printImg.complete) {
                    printImg.onload = function() { setTimeout(doPrint, 150); };
                } else {
                    setTimeout(doPrint, 300);
                }

                setTimeout(function() {
                    if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
                }, 60000);
            }, function() {
                alert('Error preparing print. Please try again.');
            });
        }

        function viewRow(row) {
            var body = $('#previewBody');
            body.empty();
            body.html('<div class="preview-loading">Preparing preview...</div>');
            $('#previewOverlay').addClass('show');

            renderCardImage(row, function(imgData) {
                body.empty();
                var availWidth = body.width() - 40;
                var maxW = Math.min(availWidth, 620);
                if (!(maxW > 0)) maxW = 620;

                var img = $('<img>').attr('src', imgData).css({
                    width: maxW + 'px',
                    height: 'auto'
                });
                body.append(img);

                $('#previewPrint').off('click').on('click', function() {
                    printRow(row);
                });
            }, function() {
                body.html('<div class="preview-loading">Failed to render preview.</div>');
            });
        }

        function closePreview() {
            $('#previewOverlay').removeClass('show');
            $('#previewBody').empty();
        }

        function showToast(msg) {
            var t = document.getElementById('toastMsg');
            if (!t) {
                t = document.createElement('div');
                t.id = 'toastMsg';
                t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;box-shadow:0 10px 24px rgba(0,0,0,.25);z-index:9999;opacity:0;transition:opacity .25s;';
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
                tbody.append('<tr class="empty-row"><td colspan="28">No data found</td></tr>');
                return;
            }
            currentData.forEach(function(row) {
                var tr = $('<tr>');
                var printBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('action-btn btn-print-row')
                    .html('<i class="fa-solid fa-print"></i> PRINT')
                    .attr('title', 'Print')
                    .on('click', function() {
                        printRow(row);
                    });
                var viewBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('action-btn btn-view-row')
                    .html('<i class="fa-solid fa-eye"></i> VIEW')
                    .attr('title', 'Preview')
                    .on('click', function() {
                        viewRow(row);
                    });
                tr.append($('<td>').append(printBtn).append(viewBtn));
                COLS.forEach(function(c) {
                    tr.append($('<td>').text(row[c.key] || ''));
                });
                tbody.append(tr);
            });
        }

        function searchBooking() {
            var search = $('#bookingInput').val().trim();
            $('#searchBtn').prop('disabled', true).html('Searching...');
            $.ajax({
                    url: 'ajaxKnittingStore_Report.php',
                    data: { search: search },
                    dataType: 'json',
                    method: 'GET'
                })
.done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="28">No data found</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr class="empty-row"><td colspan="28" style="color:#dc2626;">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="28" class="loading-cell">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxKnittingStore_Report.php',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="28">No data returned</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr class="empty-row"><td colspan="28" style="color:#dc2626;">Error loading data</td></tr>');
                });
        }

        $(function() {
            $('#backBtn').on('click', function() {
                window.location.href = 'report.php';
            });
            $('#searchBtn').on('click', searchBooking);
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                loadAll();
            });
            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) searchBooking();
            });

            // Preview modal close handlers
            $('#previewClose').on('click', closePreview);
            $('#previewOverlay').on('click', function(e) {
                if (e.target === this) closePreview();
            });
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') closePreview();
            });

            loadAll();
        });
    </script>

</body>

</html>
