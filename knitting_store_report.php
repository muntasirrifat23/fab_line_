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
            border: 1px solid transparent;
        }

        #backBtn:hover {
            background: #ffffff !important;
            border: 1px solid #000000;
            color: #000000;
        }

        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-scroll {
            overflow-x: auto;
            max-height: 68vh;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.12rem;
            min-width: 1400px;
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

        .btn-print-row {
            background: #2563eb;
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
        }

        .btn-print-row:hover {
            background: #1d4ed8;
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
            <button class="btn" id="backBtn" style="background:#1e293b; padding:8px 14px; ">
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
                            <th>DATE</th>
                            <th>RACK</th>
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
                            <th>SUPPLIER</th>
                            <th>YARN TYPE</th>
                            <th>YARN COUNT</th>
                            <th>OPEN / TUBE</th>
                            <th>SL/VQ</th>
                            <th>FABRICS TYPE</th>
                            <th>FINISH GSM</th>
                            <th>FINISH DIA</th>
                            <th>GRAY GSM</th>
                            <th>FEEDER PLAN</th>
                            <th>LOT NO</th>
                            <th>T POINT</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="25" class="loading-cell">Loading data...</td>
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
        var COLS = [
            { key: 'BUDAT', label: 'Date' },
            { key: 'RACK', label: 'Rack' },
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
            { key: 'SUPPLIER', label: 'Supplier' },
            { key: 'YTYPE', label: 'Yarn Type' },
            { key: 'YCOUNT', label: 'Yarn Count' },
            { key: 'O_T', label: 'Open / Tube' },
            { key: 'SL', label: 'SL/VQ' },
            { key: 'FTYPE', label: 'Fabrics Type' },
            { key: 'FGSM', label: 'Finish GSM' },
            { key: 'FDIA', label: 'Finish Dia' },
            { key: 'GGSM', label: 'Gray GSM' },
            { key: 'FEEDER_PLAN', label: 'Feeder Plan' },
            { key: 'LOT_NO', label: 'Lot No' },
            { key: 'TPOINT', label: 'T Point' }
        ];

        var currentData = [];

        function esc(v) {
            if (v === null || v === undefined) return '';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function downloadPDF(roll) {
            var row = null;
            for (var i = 0; i < currentData.length; i++) {
                if (String(currentData[i].ROLL) === String(roll)) { row = currentData[i]; break; }
            }
            if (!row) { alert('Data not found for this roll.'); return; }

            var fieldHTML = COLS.map(function(c) {
                return [c.label, row[c.key]];
            });

            var rowsHTML = fieldHTML.map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                return '<div class="pdf-item"><span class="pdf-label">' + f[0] + ' :</span><span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="position:relative;width:760px;min-height:1050px;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">' +
                '<div style="text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a;border-bottom:3px solid #2563eb;padding-bottom:10px;margin-bottom:16px;">' +
                'Knitting Store Report' +
                '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;font-size:14px;font-weight:bold;margin-bottom:10px; margin-left:10px;">' +
                '<span>Roll : ' + (row.ROLL || '') + '</span>' +
                '<span style="margin-right:20px;">User : ' + (row.UID || row.UNAME || '') + '</span>' +
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

            var style = document.createElement('style');
            style.textContent = '' +
                '.pdf-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border:2px solid #d1d5db;border-radius:6px;padding:0;background:#ffffff;}' +
                '.pdf-item{font-size:13px;line-height:1.7;border-bottom:1px solid #d1d5db;border-right:1px solid #d1d5db;padding:7px 12px;word-break:break-word;background:#ffffff;color:#000000;}' +
                '.pdf-item:nth-child(2n){border-right:none;}' +
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
                pdf.save('Knitting_Store_' + (row.ROLL || 'Roll') + '.pdf');
                document.body.removeChild(tempDiv);
                document.body.removeChild(style);
                showToast('PDF downloaded');
            }).catch(function(err) {
                console.error('PDF generation error:', err);
                alert('Error generating PDF. Please try again.');
                document.body.removeChild(tempDiv);
                document.body.removeChild(style);
            });
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
                tbody.append('<tr class="empty-row"><td colspan="25">No data found</td></tr>');
                return;
            }
            currentData.forEach(function(row) {
                var tr = $('<tr>');
                tr.append($('<td>').html(
                    '<button class="btn-print-row" onclick="downloadPDF(\'' + esc(row.ROLL).replace(/'/g, '&#39;') + '\')"><i class="fa-solid fa-file-pdf"></i> PDF</button>'
                ));
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
                        $('#tableBody').html('<tr class="empty-row"><td colspan="25">No data found</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr class="empty-row"><td colspan="25" style="color:#dc2626;">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="25" class="loading-cell">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxKnittingStore_Report.php',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="25">No data returned</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr class="empty-row"><td colspan="25" style="color:#dc2626;">Error loading data</td></tr>');
                });
        }

        $(function() {
            $('#backBtn').on('click', function() {
                window.location.href = 'initialPage.php';
            });
            $('#searchBtn').on('click', searchBooking);
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                loadAll();
            });
            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) searchBooking();
            });

            loadAll();
        });
    </script>

</body>

</html>
