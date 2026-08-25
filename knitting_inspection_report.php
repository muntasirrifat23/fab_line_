<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | Knitting Inspection Report</title>

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
            max-height: calc(100vh - 180px);
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            min-width: 3400px;
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

        tbody tr:nth-child(even) td:first-child {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #eff6ff;
        }

        tbody tr:hover td:first-child {
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
                <i class="fa-solid fa-arrow-left"></i> Back to Report
            </button>
            <h1 style="font-size:xx-large;">Knitting Inspection Report</h1>
        </div>

        <div class="search-panel">
            <div class="search-row">
                <input type="text" id="bookingInput" placeholder="Search Roll / PO Number / SONO / Buyer / Style">
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
                            <th>KITID</th>
                            <th>BUDAT</th>
                            <th>ROLL</th>
                            <th>MAIN QTY</th>
                            <th>REJECT QTY</th>
                            <th>UPDATE QTY</th>
                            <th>PO_NUMBER</th>
                            <th>QTY</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MCNO</th>
                            <th>MC_DIA</th>
                            <th>CUSTOMER</th>
                            <th>SHIFT</th>
                            <th>YTYPE</th>
                            <th>YCOUNT</th>
                            <th>FTYPE</th>
                            <th>FGSM</th>
                            <th>FDIA</th>
                            <th>O_T</th>
                            <th>SL</th>
                            <th>GGSM</th>
                            <th>FPLAN</th>
                            <th>LOTNO</th>
                            <th>MATERIAL_CODE</th>
                            <th>M_DES</th>
                            <th>TT</th>
                            <th>PATTA</th>
                            <th>SLUB</th>
                            <th>YC_SPOT</th>
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
                            <th>QC_GRADE</th>
                            <th>QC_STATUS</th>
                            <th>UNAME</th>
                            <th>UID</th>
                            <th>P_CREATED</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="51" class="loading-cell">Loading data...</td>
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
            { key: 'KITID', label: 'KITID' },
            { key: 'BUDAT', label: 'BUDAT' },
            { key: 'ROLL', label: 'ROLL' },
            { key: 'MAIN_QTY',   label: 'MAIN QTY'   },
            { key: 'REJECT_QTY', label: 'REJECT QTY'  },
            { key: 'UPDATE_QTY', label: 'UPDATE QTY'  },
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
            { key: 'FTYPE', label: 'FTYPE' },
            { key: 'FGSM', label: 'FGSM' },
            { key: 'FDIA', label: 'FDIA' },
            { key: 'O_T', label: 'O_T' },
            { key: 'SL', label: 'SL' },
            { key: 'GGSM', label: 'GGSM' },
            { key: 'FPLAN', label: 'FPLAN' },
            { key: 'LOTNO', label: 'LOTNO' },
            { key: 'MATERIAL_CODE', label: 'MATERIAL_CODE' },
            { key: 'M_DES', label: 'M_DES' },
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
            { key: 'UNAME', label: 'UNAME' },
            { key: 'UID', label: 'UID' },
            { key: 'P_CREATED', label: 'P_CREATED' }
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

            var rowsHTML = fieldHTML.filter(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : String(f[1]).trim();
                return val !== '' && parseFloat(val) !== 0;
            }).map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                return '<div class="pdf-item"><span class="pdf-label">' + f[0] + ' :</span><span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="position:relative;width:760px;min-height:1050px;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">' +
                '<div style="text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a;border-bottom:3px solid #2563eb;padding-bottom:10px;margin-bottom:16px;">' +
                'Knitting Inspection Report' +
                '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;font-size:14px;font-weight:bold;margin-bottom:10px; margin-left:10px;">' +
                '<span>Roll : ' + (row.ROLL || '') + '</span>' +
                '<span style="margin-right:20px;">User : ' + (row.UNAME || row.UID || '') + '</span>' +
                '</div>' +
                '<div class="pdf-grid">' + rowsHTML + '</div>' +
                '<div style="text-align:center;font-size:11px;color: black; margin-top:16px;border-top:1px solid #e5e7eb;padding-top:8px;">' +
                'Generated from Knitting Inspection Report - ' + new Date().toLocaleString() +
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
                pdf.save('Knitting_Inspection_' + (row.ROLL || 'Roll') + '.pdf');
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
                tbody.append('<tr class="empty-row"><td colspan="51">No data found</td></tr>');
                return;
            }
            currentData.forEach(function(row) {
                var tr = $('<tr>');
                tr.append($('<td>').html(
                    '<button class="btn-print-row" onclick="downloadPDF(\'' + esc(row.ROLL).replace(/'/g, '&#39;') + '\')"><i class="fa-solid fa-file-pdf"></i> PDF</button>'
                ));
                COLS.forEach(function(c) {
                    tr.append($('<td>').text(row[c.key] === null || row[c.key] === undefined ? '' : row[c.key]));
                });
                tbody.append(tr);
            });
        }

        var isSearching = false;

        function searchBooking() {
            var search = $('#bookingInput').val().trim();
            isSearching = (search !== '');
            $('#searchBtn').prop('disabled', true).html('Searching...');
            $.ajax({
                    url: 'ajaxKnittingInspection_Report.php',
                    data: { search: search },
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="51">No data found</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr class="empty-row"><td colspan="51" style="color:#dc2626;">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Search');
                });
        }

        function loadAll(silent) {
            if (!silent) {
                $('#tableBody').html('<tr><td colspan="51" class="loading-cell">Loading data...</td></tr>');
            }
            var search = $('#bookingInput').val().trim();
            $.ajax({
                    url: 'ajaxKnittingInspection_Report.php',
                    data: search !== '' ? { search: search } : {},
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else if (!silent) {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="51">No data returned</td></tr>');
                    }
                })
                .fail(function() {
                    if (!silent) {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="51" style="color:#dc2626;">Error loading data</td></tr>');
                    }
                });
        }

        $(function() {
            $('#backBtn').on('click', function() {
                window.location.href = 'initialPage.php';
            });
            $('#searchBtn').on('click', searchBooking);
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                isSearching = false;
                loadAll(false);
            });
            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) searchBooking();
            });

            loadAll(false);

            // Real-time polling every 5 seconds
            setInterval(function() {
                loadAll(true);
            }, 5000);
        });
    </script>

</body>

</html>
