<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | Program</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --surface-bg: #f8fafc;
            --card-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            --header-bg: #d8d4d6;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        /* reset global mycss icon borders */
        i,
        i.fa-solid,
        i.fas,
        i.far,
        i.fab,
        i.fa-regular {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            display: inline-block !important;
            transform: none !important;
        }

        body {
            padding: 24px;
            background-color: var(--surface-bg);
            font-family: var(--font-main);
            color: #334155;
        }

        /* ============ HEADER BANNER ============ */
        .top-banner {
            position: relative;
            background: var(--header-bg);
            color: white;
            padding: 30px 36px 0 36px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .top-banner::before {
            display: none;
        }

        .top-banner::after {
            display: none;
        }

        .banner-inner {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            padding-bottom: 26px;
        }

        .banner-title-center {
            flex: 1 1 auto;
            text-align: center;
            min-width: 260px;
        }

        .top-banner h1 {
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .banner-subtitle {
            font-size: 16px;
            color: #e5e7eb;
            margin: 0;
            font-weight: 500;
        }

        .nav-btn {
            border-radius: 12px;
            font-weight: 700;
            font-size: 13.5px;
            padding: 10px 20px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-glass {
            background: black;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            backdrop-filter: blur(10px);
        }

        .btn-glass:hover {
            background: white;
            color: black;
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* bottom strip inside banner */
        .banner-info-strip {
            position: relative;
            z-index: 2;
            background: #111827;
            border-top: 1px solid #374151;
            margin: 0 -36px;
            padding: 13px 36px;
            display: flex;
            align-items: center;
            gap: 28px;
            flex-wrap: wrap;
        }

        .strip-item {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 15px;
            color: #e2e8f0;
            font-weight: 600;
        }

        .strip-item .strip-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
            background: #38bdf8;
            box-shadow: 0 0 8px #38bdf8;
        }

        /* ============ STAT CARDS ============ */
        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px 22px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 16px;
            height: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 32px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
        }

        .bg-blue-light {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1d4ed8;
        }

        .bg-sky-light {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: #0284c7;
        }

        .bg-green-light {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            color: #166534;
        }

        .bg-amber-light {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            color: #b45309;
        }

        /* ============ SEARCH PANEL ============ */
        .search-panel {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .search-panel .form-control {
            height: 42px !important;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            padding: 8px 14px !important;
            font-size: 13.5px !important;
            font-weight: 500;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }

        .search-panel .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            background-color: #ffffff;
        }

        .search-panel .btn {
            height: 42px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 12px !important;
            font-weight: 700;
            font-size: 13.5px;
        }

        .report-search-row {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
        }

        .report-search-input {
            width: 25%;
            flex: 0 0 25%;
            min-width: 0;
        }

        .report-search-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }

        .btn-search {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }

        .btn-search:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            transform: translateY(-1px);
        }

        .btn-reset {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .btn-reset:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }

        /* ============ TABLE PANEL ============ */
        .table-panel {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
        }

        .table-scroll {
            overflow: auto;
            max-height: 68vh;
            border-radius: 14px;
            border: 1px solid #eef2f7;
        }

        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }

        .custom-table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #0f172a;
            color: #f8fafc;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            padding: 13px 14px;
            white-space: nowrap;
            border: none;
            border-right: 1px solid rgba(255, 255, 255, 0.07);
            border-bottom: 2px solid #1e293b;
        }

        .custom-table thead th:last-child {
            border-right: none;
        }

        .custom-table tbody td {
            padding: 11px 14px;
            font-size: 13px;
            vertical-align: middle;
            white-space: nowrap;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f8fafc;
            color: #334155;
            font-weight: 500;
        }

        .custom-table tbody td:last-child {
            border-right: none;
        }

        .custom-table tbody tr:nth-child(even) {
            background-color: #fbfcfe;
        }

        .custom-table tbody tr {
            transition: all 0.15s ease;
        }

        .custom-table tbody tr:hover {
            background-color: #eff6ff;
        }

        .prog-no-badge {
            font-weight: 800;
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 12px;
            letter-spacing: 0.3px;
        }

        .qty-cell {
            font-weight: 700;
            color: #0f172a;
        }

        .shift-chip {
            display: inline-block;
            min-width: 26px;
            text-align: center;
            font-weight: 800;
            font-size: 11.5px;
            border-radius: 7px;
            padding: 3px 8px;
        }

        .chip-a {
            background: #dcfce7;
            color: #166534;
        }

        .chip-b {
            background: #fef3c7;
            color: #92400e;
        }

        .chip-c {
            background: #ede9fe;
            color: #5b21b6;
        }

        .btn-pdf {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            color: white;
            border-radius: 9px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 5px 11px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.25);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-pdf:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(37, 99, 235, 0.35);
        }

        /* toolbar above table */
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
            padding: 0 4px;
        }

        .record-count {
            font-size: 13px;
            font-weight: 700;
            color: #475569;
        }

        .record-count span {
            color: #1d4ed8;
        }

        .hint-text {
            font-size: 14px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* empty / loading states */
        .state-cell {
            padding: 46px 10px !important;
            text-align: center;
            white-space: normal !important;
        }

        .state-icon {
            font-size: 34px;
            color: #cbd5e1;
            margin-bottom: 10px;
            display: block;
        }

        .state-text {
            font-size: 13.5px;
            color: #94a3b8;
            font-weight: 600;
        }

        .loading-spinner {
            display: inline-block;
            width: 22px;
            height: 22px;
            border: 3px solid #dbeafe;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 14px;
            }

            .top-banner {
                padding: 22px 20px 0 20px;
                border-radius: 18px;
            }

            .banner-info-strip {
                margin: 0 -20px;
                padding: 12px 20px;
            }

            .top-banner h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid">

        <!-- Header Banner -->
        <div class="top-banner">
            <div class="banner-inner">
                <button class="btn nav-btn btn-glass" id="backBtn">
                    <i class="fa-solid fa-arrow-left"></i> Back to Report
                </button>
                <div class="banner-title-center">
                    <h1 style="font-size: x-large; font-weight: 800; color: #1f2937;">Knitting Program Report</h1>
                </div>
                <div class="d-none d-xl-block" style="width: 190px; flex-shrink: 0;"></div>
            </div>
            <div class="banner-info-strip">
                <span class="strip-item"><span class="strip-dot"></span><span id="stripTotal">Total Programs : —</span></span>
                <span class="strip-item"><span class="strip-dot" style="background:#34d399;box-shadow:0 0 8px #34d399;"></span><span id="stripQty">Total QTY : —</span></span>
                <span class="strip-item ms-auto hint-text" style="color:#cbd5e1;">
                    <i class="fa-regular fa-calendar me-1"></i><?php echo date('d M Y'); ?>
                </span>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-panel">
            <div class="search-panel mt-3">
                <div class="report-search-row">
                    <div class="input-group report-search-input">
                        <input type="text" id="bookingInput" class="form-control border-start-0"
                            style="border-radius: 0 12px 12px 0;"
                            placeholder="Search by Program NO or PO NO" autofocus>
                    </div>
                    <div class="report-search-actions">
                        <button type="button" class="btn btn-search px-4" id="searchBtn" style="white-space: nowrap;">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Search
                        </button>
                        <button type="button" class="btn btn-reset px-3" id="clearBtn" style="white-space: nowrap;">
                            <i class="fa-solid fa-rotate-left me-1"></i> Clear
                        </button>
                    </div>
                </div>
            </div>


            <div class="table-toolbar">
                <div class="record-count">
                    <i class="fa-solid fa-table-list me-1 text-primary"></i> Showing <span id="rowCount">0</span> record(s)
                </div>

            </div>
            <div class="table-scroll">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>PDF</th>
                            <th>Date</th>
                            <th>Program No</th>
                            <th>PO No</th>
                            <th>SONO</th>
                            <th>Buyer</th>
                            <th>Style</th>
                            <th>Color</th>
                            <th>Customer</th>
                            <th>QTY (KG)</th>
                            <th>O / T</th>
                            <th>Finish GSM</th>
                            <th>Finish Dia</th>
                            <th>Fabrics Type</th>
                            <th>Yarn Type</th>
                            <th>Yarn Count</th>
                            <th>SL/VDQ</th>
                            <th>M/C Dia</th>
                            <th>Gray GSM</th>
                            <th>Feeder Plan</th>
                            <!-- <th>Shift</th> -->
                            <th>Lot No</th>
                            <!-- <th>Knit M Description</th> -->
                            <!-- <th>Knit Material Code</th> -->
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="25" class="state-cell">
                                <span class="loading-spinner"></span>
                                <div class="state-text mt-2">Loading data...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        function esc(v) {
            return $('<div>').text(v === null || v === undefined ? '' : v).html();
        }

        function updateStats(data) {
            var rows = data || [];
            var totalQty = 0;
            rows.forEach(function(row) {
                totalQty += parseFloat(row.QTY) || 0;
            });

            $('#statPrograms').text(rows.length.toLocaleString());
            $('#statQty').html(rows.length ? totalQty.toLocaleString(undefined, {
                maximumFractionDigits: 2
            }) + ' <span class="fs-6 text-muted">KG</span>' : '—');
            $('#stripTotal').text('Total Programs : ' + rows.length.toLocaleString());
            $('#stripQty').text('Total QTY : ' + totalQty.toLocaleString(undefined, {
                maximumFractionDigits: 2
            }) + ' KG');
            $('#rowCount').text(rows.length.toLocaleString());
        }

        function shiftChip(val) {
            var v = (val || '').toString().trim().toUpperCase();
            if (v === 'A' || v === 'B' || v === 'C') {
                return '<span class="shift-chip chip-' + v.toLowerCase() + '">' + v + '</span>';
            }
            return esc(val);
        }

        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            updateStats(data);

            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="25" class="state-cell">' +
                    '<i class="fa-regular fa-folder-open state-icon"></i>' +
                    '<div class="state-text">No data found</div></td></tr>');
                return;
            }

            data.forEach(function(row) {
                var tr = $('<tr>');
                var pdfBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('btn-pdf')
                    .attr('title', 'Download PDF')
                    .html('<i class="fa-solid fa-file-pdf"></i> PDF')
                    .on('click', function() {
                        downloadRowPdf(row);
                    });
                tr.append($('<td class="text-center">').append(pdfBtn));
                tr.append($('<td>').text(row.CREATED_DATE || ''));
                tr.append($('<td>').html('<span class="prog-no-badge">' + esc(row.PROGRAM_NO || '') + '</span>'));
                tr.append($('<td class="fw-bold">').text(row.PO_NUMBER || row.BOOKING || ''));
                tr.append($('<td>').text(row.SONO || ''));
                tr.append($('<td>').text(row.BUYER || ''));
                tr.append($('<td>').text(row.STYLE || ''));
                tr.append($('<td>').text(row.COLOR || ''));
                tr.append($('<td>').text(row.CUSTOMER || ''));
                tr.append($('<td class="qty-cell">').text(row.QTY || ''));
                tr.append($('<td>').text(row.O_T || ''));
                tr.append($('<td>').text(row.FGSM || ''));
                tr.append($('<td>').text(row.FDIA || ''));
                tr.append($('<td>').text(row.FTYPE || ''));
                tr.append($('<td>').text(row.YTYPE || ''));
                tr.append($('<td>').text(row.YCOUNT || ''));
                tr.append($('<td>').text(row.SL || ''));
                tr.append($('<td>').text(row.MCDIA || ''));
                tr.append($('<td>').text(row.GGSM || ''));
                tr.append($('<td>').text(row.FEEDER_PLAN || ''));
                // tr.append($('<td>').html(shiftChip(row.SHIFT)));
                tr.append($('<td>').text(row.LOT || ''));
                // tr.append($('<td>').text(row.KNIT_M_DESCRIPTION || ''));
                // tr.append($('<td>').text(row.KNIT_MATERIAL_CODE || ''));
                tbody.append(tr);
            });
        }

        function showState(icon, text) {
            updateStats([]);
            $('#tableBody').html('<tr><td colspan="25" class="state-cell">' +
                '<i class="' + icon + ' state-icon"></i>' +
                '<div class="state-text">' + text + '</div></td></tr>');
        }

        function searchBooking() {
            var booking = $('#bookingInput').val().trim();
            if (!booking) {
                $('#bookingInput').focus();
                return;
            }
            $('#searchBtn').prop('disabled', true)
                .html('<span class="loading-spinner" style="width:15px;height:15px;border-width:2px;"></span>');
            $.ajax({
                    url: 'ajaxKnittingProgram_Report.php',
                    data: {
                        booking: booking
                    },
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success && resp.data && resp.data.length) {
                        renderTableRows(resp.data);
                    } else {
                        showState('fa-regular fa-face-frown', 'No data found for "' + booking + '"');
                    }
                })
                .fail(function() {
                    showState('fa-solid fa-triangle-exclamation', 'Error searching. Please try again.');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass me-1"></i> Search');
                });
        }

        function loadAll() {
            showState('fa-solid fa-circle-notch', '');
            $('#tableBody').find('.state-text').before('<span class="loading-spinner"></span>');
            $('#tableBody').find('.loading-spinner').after('<div class="state-text mt-2">Loading data...</div>');
            $.ajax({
                    url: 'ajaxKnittingProgram_Report.php',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) renderTableRows(resp.data);
                    else showState('fa-regular fa-folder-open', 'No data returned');
                })
                .fail(function() {
                    showState('fa-solid fa-triangle-exclamation', 'Error loading data');
                });
        }

        // ---------- ROW PDF DOWNLOAD (image-style card) ----------
        function downloadRowPdf(row) {
            var fieldHTML = [
                ['Date', row.CREATED_DATE],
                ['Program No', row.PROGRAM_NO],
                ['PO No', row.PO_NUMBER || row.BOOKING || ''],
                ['SONO', row.SONO],
                ['Buyer', row.BUYER],
                ['Style', row.STYLE],
                ['Color', row.COLOR],
                ['Customer', row.CUSTOMER],
                ['QTY', row.QTY],
                ['Open / Tube', row.O_T],
                ['Finish GSM', row.FGSM],
                ['Finish Dia', row.FDIA],
                ['Fabrics Type', row.FTYPE],
                ['Yarn Type', row.YTYPE],
                ['Yarn Count', row.YCOUNT],
                ['SL/VDQ', row.SL],
                ['MC Dia', row.MCDIA],
                ['Gray GSM', row.GGSM],
                ['Feeder Plan', row.FEEDER_PLAN],
                // ['Shift', row.SHIFT],
                ['Lot No', row.LOT],
                // ['KNIT M DESCRIPTION', row.KNIT_M_DESCRIPTION, true],
                // ['KNIT MATERIAL CODE', row.KNIT_MATERIAL_CODE, true]
            ];

            var rowsHTML = fieldHTML.map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                var cls = f[2] ? 'pdf-item pdf-item-full' : 'pdf-item';
                return '<div class="' + cls + '"><span class="pdf-label">' + f[0] + ' :</span><span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="position:relative;width:760px;min-height:1050px;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">' + '<div style="text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a;border-bottom:3px solid #2563eb;padding-bottom:10px;margin-bottom:16px;">' +
                'Knitting Program Report' +
                '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;font-size:14px;font-weight:bold;margin-bottom:10px; margin-left:10px;">' +
                '<span>Program No : ' + (row.PROGRAM_NO || '') + '</span>' +
                '</div>' +
                '<div class="pdf-grid">' + rowsHTML + '</div>' +
                '<div style="text-align:center;font-size:11px;color: black; margin-top:16px;border-top:1px solid #e5e7eb;padding-top:8px;">' +
                'Generated from Knitting Program Report - ' + new Date().toLocaleString() +
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

            var style = document.createElement('style');
            style.textContent = '' +
                '.pdf-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border:2px solid #d1d5db;border-radius:6px;padding:0;background:#ffffff;}' +
                '.pdf-item{font-size:13px;line-height:1.7;border-bottom:1px solid #d1d5db;border-right:1px solid #d1d5db;padding:7px 12px;word-break:break-word;background:#ffffff;color:#000000;}' +
                '.pdf-item:nth-child(2n){border-right:none;}' +
                '.pdf-item-full{grid-column:1 / -1;border-right:none;border-top:2px solid #d1d5db;}' +
                '.pdf-label{font-weight:bold;color:#000000;}' +
                '.pdf-value{margin-left:8px;color:#000000;}' +
                '.pdf-sign{' +
                'position:absolute;' +
                'left:24px;' +
                'right:24px;' +
                'bottom:55px;' +
                'display:grid;' +
                'grid-template-columns:1fr 1fr 1fr 1fr;' +
                'gap:20px;' +
                'padding:0 10px;' +
                'text-align:center;' +
                'font-size:13px;' +
                'font-weight:bold;' +
                'color:#000000;' +
                '}' +

                '.pdf-sign-item{' +
                'display:flex;' +
                'flex-direction:column;' +
                'align-items:center;' +
                'justify-content:flex-end;' +
                'gap:8px;' +
                '}' +

                '.pdf-sign-line{' +
                'width:80%;' +
                'border-top:1px solid #000000;' +
                'margin-top:0;' +
                '}' +
                '</style>';
            document.body.appendChild(style);

            html2canvas(tempDiv, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false
            }).then(function(canvas) {
                var imgData = canvas.toDataURL('image/png');
                var jsPDFLib = window.jspdf;
                var pdf = new jsPDFLib.jsPDF('p', 'mm', 'a4');
                var pdfWidth = pdf.internal.pageSize.getWidth();
                var pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('Program_' + (row.PROGRAM_NO || row.PO_NUMBER || 'Row') + '.pdf');
                document.body.removeChild(tempDiv);
                document.body.removeChild(style);
            }).catch(function(err) {
                console.error('PDF generation error:', err);
                alert('Error generating PDF. Please try again.');
                document.body.removeChild(tempDiv);
                document.body.removeChild(style);
            });
        }

        $(function() {
            $('#backBtn').on('click', function() {
                history.back();
            });
            $('#searchBtn').on('click', searchBooking);
            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) searchBooking();
            });
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                loadAll();
            });

            // initial load: show all data
            loadAll();
        });
    </script>

</body>

</html>