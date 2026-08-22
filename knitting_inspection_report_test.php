<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting Test Inspection Report</title>

    <!-- Bootstrap 5 & Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- html2canvas for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- jsPDF for PDF download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        body {
            padding: 18px;
            background: #f5f7fa;
        }

        .panel {
            background: #fff;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(20, 30, 50, 0.06);
        }

        .controls .btn {
            min-width: 120px;
        }

        h1.title {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 18px;
        }

        .table thead th {
            vertical-align: middle;
            font-size: 0.7rem;
            white-space: nowrap;
        }

        .table td {
            font-size: 0.75rem;
            vertical-align: middle;
        }

        .small-muted {
            font-size: 12px;
            color: #6b7280;
        }

        .qr-btn {
            background: #0d6efd;
            border: none;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            transition: 0.2s;
        }

        .qr-btn:hover {
            background: #0b5ed7;
            color: #fff;
        }

        .qr-code-cell {
            text-align: center;
            min-width: 90px;
        }

        .qr-dropdown-panel {
            background: #ffffff;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            padding: 15px;
            max-width: 900px;
            margin: 0 auto;
        }

        .qr-dropdown-panel .print-header {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .qr-dropdown-panel .print-title {
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .qr-dropdown-row td {
            background: #f8f9fa !important;
        }

        .qr-buttons {
            margin-top: 15px;
            text-align: center;
        }

        .qr-buttons button {
            margin: 0 5px;
        }

        .qr-dropdown-panel .qr-data-grid-2col {
            max-height: none;
            overflow: visible;
            border: 1px solid #e9ecef;
        }

        /* 2 column grid for inspection data - wider */
        .qr-data-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 25px;
            font-size: 0.85rem;
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            max-height: 320px;
            overflow-y: auto;
        }

        .qr-data-grid-2col .field {
            font-weight: 600;
            color: #1e293b;
        }

        .qr-data-grid-2col .value {
            color: #334155;
            word-break: break-word;
        }

        .qr-data-item-2col {
            border-bottom: 1px solid #e9ecef;
            padding: 4px 0;
        }

        .qr-data-item-2col:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-dark no-print" id="backBtn" style="background-color:#1f2937;color:#fff;padding:12px;border-radius:8px;">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i> Back to Initial Page
            </button>
            <h1 class="title no-print">Knitting Test Inspection Report</h1>
            <div class="no-print"></div>
        </div>

        <!-- search panel -->
        <div class="panel mb-3 no-print">
            <div class="row g-3 align-items-end controls">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size: larger; color: black;">Search Roll Or PO Number</label>
                    <div class="input-group input-group-sm d-flex align-items-center gap-2">
                        <input type="text" id="bookingInput" class="form-control" placeholder="Enter Roll / PO No">
                        <button class="btn px-4" id="searchBtn" style="background:#2563eb; border:1px solid #2563eb; color:#fff; border-radius:8px;">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Search
                        </button>
                        <button class="btn px-4" id="clearBtn" style="background:#6b7280; border:1px solid #6b7280; color:#fff; border-radius:8px;">
                            <i class="fa-solid fa-rotate-left me-1"></i> Clear
                        </button>
                    </div>
                </div>
                <div class="col-md-4 text-end"></div>
            </div>
        </div>

        <!-- table -->
        <div class="table-container no-print">
            <div class="panel" style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-hover table-sm" id="mainTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width:90px;">DOWNLOAD</th>
                            <th>DATE</th>
                            <th>ROLL</th>
                            <th>PO NO</th>
                            <th>QTY</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MC NO</th>
                            <th>MC DIA</th>
                            <th>CUSTOMER</th>
                            <th>SHIFT</th>
                            <th>YARN TYPE</th>
                            <th>YARN COUNT</th>
                            <th>FABRICS TYPE</th>
                            <th>FINISH GSM</th>
                            <th>FINISH DIA</th>
                            <th>OPEN/TUBE</th>
                            <th>SL/VDQ</th>
                            <th>GRAY GSM</th>
                            <th>FEEDER PLAN</th>
                            <th>LOT NO</th>
                            <th>MAT CODE</th>
                            <th>MAT DESC</th>
                            <th>T.POINT</th>
                            <th>TT</th>
                            <th>PATTA</th>
                            <th>SLUB</th>
                            <th>YC SPOT</th>
                            <th>OIL SPOT</th>
                            <th>FF</th>
                            <th>SEEDS</th>
                            <th>M/STITCH</th>
                            <th>SINKER MARK</th>
                            <th>NEEDLE MARK</th>
                            <th>LYC OUT</th>
                            <th>OIL LINE</th>
                            <th>HOLE</th>
                            <th>LOOP</th>
                            <th>SETUP</th>
                            <th>C MARK</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="42" class="text-center small-muted">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        var currentRowData = null;

        // ---------- RENDER TABLE ----------
        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="42" class="text-center small-muted">No data found</td></tr>');
                return;
            }

            data.forEach(function(row, index) {
                var tr = $('<tr>');
                var qrTd = $('<td>').addClass('qr-code-cell');
                var btn = $('<button>')
                    .addClass('btn btn-sm btn-primary qr-action-btn')
                    .text('Download')
                    .attr('data-idx', index)
                    .data('rowData', row);
                qrTd.append(btn);
                tr.append(qrTd);

                var fields = [
                    'BUDAT', 'ROLL', 'PO_NUMBER', 'QTY', 'SONO', 'BUYER', 'STYLE',
                    'COLOR', 'MCNO', 'MC_DIA', 'CUSTOMER', 'SHIFT', 'YTYPE', 'YCOUNT', 'FTYPE',
                    'FGSM', 'FDIA', 'O_T', 'SL', 'GGSM', 'FPLAN', 'LOTNO', 'MATERIAL_CODE',
                    'M_DES', 'TPOINT', 'TT', 'PATTA', 'SLUB', 'YC_SPOT', 'OILSPOT',
                    'FF', 'SEEDS', 'MSTITCH', 'SINKERMARK', 'NEEDLEMARK', 'LYCOUT', 'OILLINE',
                    'HOLE', 'LOOP', 'SETUP', 'CMARK'
                ];
                fields.forEach(function(f) {
                    var val = (row[f] !== undefined && row[f] !== null) ? row[f] : '';
                    tr.append($('<td>').text(val));
                });

                tbody.append(tr);
            });
        }

        // ---------- BUILD DROPDOWN CONTENT (menu only - actions performed on click) ----------
        function buildDropdownContent(row) {
            return `
                <div class="qr-dropdown-panel">
                    <div class="print-header" style="font-size:16px;text-align:center;">Knitting Test Inspection Report</div>
                    <div class="print-title">What would you like to do?</div>
                    <div class="qr-buttons">
                        <button type="button" class="btn btn-success btn-sm qr-download-btn" data-roll="${row.ROLL || ''}">Download PDF</button>
                        <button type="button" class="btn btn-primary btn-sm qr-print-btn" data-roll="${row.ROLL || ''}">Print</button>
                        <button type="button" class="btn btn-danger btn-sm qr-cancel-btn">Cancel</button>
                    </div>
                </div>
            `;
        }

        function buildRowDataHTML(row) {
            var fieldMap = {
                'DATE': row.BUDAT,
                'ROLL': row.ROLL,
                'PO NO': row.PO_NUMBER,
                'QTY': row.QTY,
                'SONO': row.SONO,
                'BUYER': row.BUYER,
                'STYLE': row.STYLE,
                'COLOR': row.COLOR,
                'MC NO': row.MCNO,
                'MC DIA': row.MC_DIA,
                'CUSTOMER': row.CUSTOMER,
                'SHIFT': row.SHIFT,
                'YARN TYPE': row.YTYPE,
                'YARN COUNT': row.YCOUNT,
                'FABRICS TYPE': row.FTYPE,
                'FINISH GSM': row.FGSM,
                'FINISH DIA': row.FDIA,
                'OPEN/TUBE': row.O_T,
                'SL/VDQ': row.SL,
                'GRAY GSM': row.GGSM,
                'FEEDER PLAN': row.FPLAN,
                'LOT NO': row.LOTNO,
                'MAT CODE': row.MATERIAL_CODE,
                'MAT DESC': row.M_DES,
                'T.POINT': row.TPOINT,
                'TT': row.TT,
                'PATTA': row.PATTA,
                'SLUB': row.SLUB,
                'YC SPOT': row.YC_SPOT,
                'OIL SPOT': row.OILSPOT,
                'FF': row.FF,
                'SEEDS': row.SEEDS,
                'M/STITCH': row.MSTITCH,
                'SINKER MARK': row.SINKERMARK,
                'NEEDLE MARK': row.NEEDLEMARK,
                'LYC OUT': row.LYCOUT,
                'OIL LINE': row.OILLINE,
                'HOLE': row.HOLE,
                'LOOP': row.LOOP,
                'SETUP': row.SETUP,
                'C MARK': row.CMARK
            };

            var html = '';
            for (var key in fieldMap) {
                var val = (fieldMap[key] !== undefined && fieldMap[key] !== null) ? fieldMap[key] : '';
                if (val !== '') {
                    html += `<div class="qr-data-item-2col"><span class="field">${key}:</span> <span class="value">${val}</span></div>`;
                }
            }
            if (html === '') {
                html = '<div class="text-muted text-center">No data available</div>';
            }
            return html;
        }

        // ---------- OPEN / CLOSE DROPDOWN ----------
        function closeDropdown() {
            currentRowData = null;
            $('.qr-dropdown-row').remove();
            $('.qr-action-btn').removeClass('active');
        }

        function openQrDropdown(button) {
            closeDropdown();
            var row = $(button).closest('tr');
            var data = $(button).data('rowData');
            currentRowData = data;
            var dropdownHtml =
                '<tr class="qr-dropdown-row">' +
                '<td colspan="42" style="padding:12px;background:#f8f9fa;">' +
                buildDropdownContent(data) +
                '</td></tr>';
            row.after(dropdownHtml);
            $(button).addClass('active');
        }

        $(document).on('click', '.qr-action-btn', function() {
            var button = $(this);
            if (button.hasClass('active')) {
                closeDropdown();
                return;
            }
            openQrDropdown(button);
        });

        $(document).on('click', '.qr-cancel-btn', function() {
            closeDropdown();
        });

        $(document).on('click', '.qr-download-btn', function() {
            var roll = $(this).data('roll') || 'row';
            if (!currentRowData) {
                alert('No data to generate PDF.');
                return;
            }
            downloadPDF(currentRowData, roll);
        });

        $(document).on('click', '.qr-print-btn', function() {
            if (!currentRowData) {
                alert('No data to print.');
                return;
            }
            printPanel(currentRowData);
        });

        function buildReportPanel(row) {
            var dataHTML = buildRowDataHTML(row);
            var panel = document.createElement('div');
            panel.className = 'qr-dropdown-panel';
            panel.style.maxWidth = '900px';
            panel.style.margin = '0 auto';
            panel.innerHTML =
                '<div class="print-header" style="font-size:16px;text-align:center;">Knitting Test Inspection Report</div>' +
                '<div class="qr-data-grid-2col" style="max-height:none;overflow:visible;border:1px solid #e9ecef;">' + dataHTML + '</div>';
            return panel;
        }

        function downloadPDF(row, roll) {
            if (!row) {
                alert('No data to generate PDF.');
                return;
            }

            var panel = buildReportPanel(row);
            document.body.appendChild(panel);
            panel.style.position = 'fixed';
            panel.style.left = '-9999px';
            panel.style.top = '0';

            html2canvas(panel, {
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
                if (pdfHeight > pdf.internal.pageSize.getHeight()) {
                    pdfHeight = pdf.internal.pageSize.getHeight();
                }
                var xOff = (pdfWidth - (canvas.width * pdfHeight) / canvas.height) / 2;
                pdf.addImage(imgData, 'PNG', xOff, 0, (canvas.width * pdfHeight) / canvas.height, pdfHeight);
                pdf.save('knitting-inspection-' + (roll || 'row') + '.pdf');
            }).catch(function(err) {
                console.error('PDF generation error:', err);
                alert('Error generating PDF. Please try again.');
            }).then(function() {
                if (panel.parentNode) {
                    panel.parentNode.removeChild(panel);
                }
            });
        }

        function printPanel(row) {
            if (!row) {
                alert('No data to print.');
                return;
            }

            var dataHTML = buildRowDataHTML(row);

            var printStyles = '<style>' +
                '@page{size:A4 portrait;margin:15mm;}' +
                'body{margin:0;padding:10mm;font-family:Arial,Helvetica,sans-serif;color:#000;background:#fff;}' +
                '.qr-data-grid-2col{display:grid;grid-template-columns:1fr 1fr;gap:4px 30px;font-size:13px;}' +
                '.qr-data-item-2col{border-bottom:1px solid #eee;padding:5px 0;}' +
                '.qr-data-item-2col .field{font-weight:600;color:#1e293b;}' +
                '.qr-data-item-2col .value{color:#334155;}' +
                '.print-header{font-size:16px;font-weight:bold;text-align:center;margin-bottom:6px;}' +
                '.print-title{text-align:center;font-size:16px;font-weight:600;margin-bottom:12px;border-bottom:2px solid #333;padding-bottom:10px;}' +
                '</style>';

            var printHtml = '<html><head><title>Print Inspection Report</title>' + printStyles + '</head><body>' +
                '<div class="print-header">Knitting Test Inspection Report</div>' +
                '<div class="qr-data-grid-2col">' + dataHTML + '</div>' +
                '</body></html>';

            var iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.style.overflow = 'hidden';
            document.body.appendChild(iframe);

            var doc = iframe.contentWindow ? iframe.contentWindow.document : iframe.contentDocument;
            doc.open();
            doc.write(printHtml);
            doc.close();

            iframe.onload = function() {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error('Print failed:', e);
                    alert('Print failed. Please allow popups or use browser print manually.');
                }
                setTimeout(function() {
                    if (iframe.parentNode) {
                        iframe.parentNode.removeChild(iframe);
                    }
                }, 1500);
            };
        }

        // ---------- AJAX SEARCH / LOAD ----------
        function searchBooking() {
            var search = $('#bookingInput').val().trim();
            if (!search) {
                alert('Please enter Roll or Booking No');
                return;
            }
            $('#searchBtn').prop('disabled', true).html('Searching...');
            $.ajax({
                    url: 'ajaxK_test_inspection_Report.php',
                    data: {
                        search: search
                    },
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        $('#tableBody').html('<tr><td colspan="42" class="text-center small-muted">No data found</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="42" class="text-center text-danger">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass me-1"></i> Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="42" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxK_test_inspection_Report.php',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        $('#tableBody').html('<tr><td colspan="42" class="text-center small-muted">No data returned</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="42" class="text-center text-danger">Error loading data</td></tr>');
                });
        }

        // ---------- INIT ----------
        $(function() {
            $('#backBtn').on('click', function() {
                window.location.href = 'initialPage.php';
            });

            $('#searchBtn').on('click', searchBooking);

            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                loadAll();
            });

            loadAll();
        });
    </script>

</body>

</html>