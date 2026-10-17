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
            padding: clamp(8px, 2vw, 16px);
            background: #f1f5f9;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
        }

        .head-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
            position: relative;
        }

        h1 {
            flex: 1 1 240px;
            text-align: center;
            font-size: clamp(1.15rem, 3.2vw, 1.9rem);
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            word-break: break-word;
        }

        .tab-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 14px;
        }

        .tab-btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1e293b;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .tab-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .search-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: clamp(10px, 2vw, 14px);
            margin-bottom: 16px;
        }

        .search-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-panel input {
            flex: 1 1 220px;
            min-width: 0;
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
            -webkit-overflow-scrolling: touch;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: clamp(0.95rem, 1.2vw, 1.12rem);
            min-width: 2400px;
        }

        thead th {
            background: #1e293b;
            color: #fff;
            padding: clamp(9px, 1.4vw, 14px) clamp(8px, 1.2vw, 12px);
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
            padding: clamp(8px, 1.2vw, 12px);
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
            padding: clamp(8px, 3vw, 20px);
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

        /* ---- Dynamic viewport height (mobile browsers) ---- */
        @supports (height: 100dvh) {
            .table-scroll {
                max-height: calc(100dvh - 200px);
            }

            .preview-box {
                max-height: 92dvh;
            }
        }

        /* ---- Tablet ---- */
        @media (max-width: 992px) {
            table {
                min-width: 2000px;
            }
        }

        /* ---- Small tablet / large phone ---- */
        @media (max-width: 768px) {
            .search-row {
                flex-direction: column;
            }

            .search-panel input {
                flex: 1 1 auto;
                width: 100%;
            }

            .search-row .btn {
                width: 100%;
                justify-content: center;
            }

            table {
                min-width: 1500px;
            }

            .preview-body {
                padding: 10px;
            }
        }

        /* ---- Phone ---- */
        @media (max-width: 576px) {
            .head-row {
                justify-content: center;
            }

            #backBtn {
                width: 100%;
                justify-content: center;
            }

            thead th:first-child,
            tbody td:first-child {
                padding-left: 6px;
                padding-right: 6px;
            }

            tbody td:first-child .action-btn {
                display: flex;
                width: 100%;
                justify-content: center;
                margin: 2px 0;
                font-size: 0.85rem;
                padding: 6px 8px;
            }

            table {
                min-width: 1200px;
            }

            .preview-body {
                padding: 8px;
            }

            .preview-head,
            .preview-foot {
                padding: 10px 12px;
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
            <h1>Knitting Store Report</h1>
        </div>

        <div class="tab-bar" id="tabBar">
            <button type="button" class="tab-btn active" data-tab="receive"><i class="fa-solid fa-inbox"></i> Receive</button>
            <button type="button" class="tab-btn" data-tab="hold"><i class="fa-solid fa-pause-circle"></i> Hold</button>
            <button type="button" class="tab-btn" data-tab="pending"><i class="fa-solid fa-hourglass-half"></i> Pending</button>
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
                        <tr id="tableHead">
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="26" class="loading-cell">Loading data...</td>
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
        var REPORT_COLS = [
            { key: 'BUDAT', label: 'Date' },
            { key: 'ROLL', label: 'Roll No' },
            { key: 'RACK', label: 'Rack' },
            { key: 'QTY', label: 'QTY' },
            { key: 'PO_NUMBER', label: 'PO Number' },
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

        var TAB_CONFIG = {
            receive: {
                endpoint: 'ajaxKnittingStore_Report.php',
                cols: REPORT_COLS,
                fields: {}
            },
            hold: {
                endpoint: 'ajaxKnittingInspection_Report.php',
                cols: REPORT_COLS,
                fields: {
                    RACK: [],
                    QTY: ['OQTY', 'MAIN_QTY', 'QTY'],
                    MCDIA: ['MC_DIA'],
                    YTYPE: ['YTYPE'],
                    YCOUNT: ['YCOUNT'],
                    O_T: ['O_T'],
                    SL: ['SL'],
                    FEEDER_PLAN: ['FPLAN'],
                    LOT_NO: ['LOTNO'],
                    TPOINT: ['TPOINT']
                }
            },
            pending: {
                endpoint: 'ajaxKnittingProduction_Report.php',
                cols: REPORT_COLS,
                fields: {
                    RACK: [],
                    QTY: ['PQTY'],
                    MCDIA: ['MC_DIA'],
                    YTYPE: ['YARN_TYPE'],
                    YCOUNT: ['YARN_COUNT'],
                    O_T: ['OPEN_TUBE'],
                    SL: ['SL_VDQ'],
                    FTYPE: ['FABRICS_TYPE'],
                    FGSM: ['FINISH_GSM'],
                    FDIA: ['FINISH_DIA'],
                    FEEDER_PLAN: ['FEEDER_PLAN'],
                    LOT_NO: ['YARN_LOT', 'LOT_NO']
                }
            }
        };

        var currentTab = 'receive';
        var currentData = [];
        var COLS = [];

        function colValue(row, c) {
            if (c.key === 'RACK') {
                if (currentTab !== 'receive') return '';
                var loc = row.RACKLOCATION;
                var no = row.RACKNO;
                loc = (loc === null || loc === undefined) ? '' : String(loc);
                no = (no === null || no === undefined) ? '' : String(no);
                return loc + no;
            }
            var fields = TAB_CONFIG[currentTab].fields[c.key] || [c.key];
            for (var i = 0; i < fields.length; i++) {
                if (row[fields[i]] !== null && row[fields[i]] !== undefined) {
                    return row[fields[i]];
                }
            }
            return '';
        }

        function renderTableHeaders() {
            var head = $('#tableHead').empty();
            head.append($('<th>').text('ACTION'));
            COLS.forEach(function(col) {
                head.append($('<th>').text(col.label));
            });
            $('#tableBody').empty();
        }

        function setActiveTab(tab) {
            currentTab = tab;
            $('.tab-btn').removeClass('active');
            $('.tab-btn[data-tab="' + tab + '"]').addClass('active');

            var cfg = TAB_CONFIG[tab];

            COLS = REPORT_COLS.filter(function(col) {
                return (col.key !== 'RACK' || tab === 'receive') &&
                    (col.key !== 'TPOINT' || tab !== 'pending');
            });

            renderTableHeaders();
            loadAll();
        }

        function esc(v) {
            if (v === null || v === undefined) return '';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function buildRowCardElement(row) {
            var rowsHTML = COLS.map(function(c) {
                var val = colValue(row, c);
                if (val === null || val === undefined) val = '';
                return '<div class="pdf-item"><span class="pdf-label">' + esc(c.label) + ' :</span><span class="pdf-value">' + esc(val) + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="position:relative;width:760px;min-height:1050px;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">' +
                '<div style="text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a;border-bottom:3px solid #2563eb;padding-bottom:10px;margin-bottom:16px;">' +
                'Knitting Store Report' +
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
            var colspan = 1 + COLS.length;
            if (!currentData.length) {
                var colspan = 1 + COLS.length;
                tbody.append('<tr class="empty-row"><td colspan="' + colspan + '">No data found</td></tr>');
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
                    var val = colValue(row, c);
                    tr.append($('<td>').text(val === null || val === undefined ? '' : val));
                });
                tbody.append(tr);
            });
        }

        function searchBooking() {
            var search = $('#bookingInput').val().trim();
            $('#searchBtn').prop('disabled', true).html('Searching...');
            $.ajax({
                    url: TAB_CONFIG[currentTab].endpoint,
                    data: { search: search },
                    dataType: 'json',
                    method: 'GET'
                })
.done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        var colspan = 1 + COLS.length;
                        $('#tableBody').html('<tr class="empty-row"><td colspan="' + colspan + '">No data found</td></tr>');
                    }
                })
                .fail(function() {
                    var colspan = 1 + COLS.length;
                    $('#tableBody').html('<tr class="empty-row"><td colspan="' + colspan + '" style="color:#dc2626;">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="' + (1 + COLS.length) + '" class="loading-cell">Loading data...</td></tr>');
            $.ajax({
                    url: TAB_CONFIG[currentTab].endpoint,
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        var colspan = 1 + COLS.length;
                        $('#tableBody').html('<tr class="empty-row"><td colspan="' + colspan + '">No data returned</td></tr>');
                    }
                })
                .fail(function() {
                    var colspan = 1 + COLS.length;
                    $('#tableBody').html('<tr class="empty-row"><td colspan="' + colspan + '" style="color:#dc2626;">Error loading data. Check the data endpoint or database connection.</td></tr>');
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

            $('.tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                if (tab !== currentTab) {
                    setActiveTab(tab);
                }
            });

            setActiveTab('receive');
        });
    </script>

</body>

</html>
