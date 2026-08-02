<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting Inspection Report + QR</title>

    <!-- Bootstrap 5 & Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- QRCode.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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

        #qrModal .modal-body {
            background: #f8fafc;
        }

        /* Wider Modal */
        #qrModal .modal-dialog {
            max-width: 900px;
            width: 95%;
        }

        .qr-display-wrapper {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .qr-display-wrapper canvas,
        .qr-display-wrapper img {
            max-width: 200px;
            height: auto;
            margin: 0 auto;
        }

        .modal-footer .btn {
            min-width: 90px;
        }

        .modal-header .modal-title {
            font-weight: 600;
        }

        /* 2 column grid for QR data - wider */
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

        /* Side by side buttons */
        .qr-action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 12px;
        }

        .qr-action-buttons .btn {
            flex: 1;
            max-width: 130px;
        }

        /* Print Styles - EXACTLY matching QR data in 2 columns */
        @media print {
            body * {
                visibility: hidden;
            }

            #printSection,
            #printSection * {
                visibility: visible;
            }

            #printSection {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 40px;
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }
        }

        .print-only {
            display: none;
        }

        #printSection {
            padding: 30px;
            background: white;
            font-family: Arial, Helvetica, sans-serif;
        }

        #printSection .print-header {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            color: #1f2937;
        }

        #printSection .print-qr-wrapper {
            text-align: center;
            margin: 15px 0;
        }

        #printSection .print-qr-wrapper img,
        #printSection .print-qr-wrapper canvas {
            max-width: 180px;
            height: auto;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 8px;
        }

        #printSection .print-data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 30px;
            font-size: 13px;
            margin-top: 15px;
            padding: 10px 0;
        }

        #printSection .print-data-item {
            border-bottom: 1px solid #eee;
            padding: 5px 0;
        }

        #printSection .print-data-item .field {
            font-weight: 600;
            color: #1e293b;
        }

        #printSection .print-data-item .value {
            color: #334155;
        }

        #printSection .print-footer {
            text-align: center;
            font-size: 11px;
            color: #999;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        #printSection .print-title {
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1f2937;
        }

        /* PDF Download Button Style */
        #downloadPDFBtn {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        #downloadPDFBtn:hover {
            background: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-dark no-print" id="backBtn" style="background-color:#1f2937;color:#fff;padding:12px;border-radius:8px;">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i> Back
            </button>
            <h1 class="title no-print">Knitting Test Inspection Report</h1>
            <div class="no-print"></div>
        </div>

        <!-- search panel -->
        <div class="panel mb-3 no-print">
            <div class="row g-3 align-items-end controls">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size: larger; color: black;">Search Roll Or Booking No</label>
                    <div class="input-group input-group-sm d-flex align-items-center gap-2">
                        <input type="text" id="bookingInput" class="form-control" placeholder="Enter Roll / Booking No">
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
                            <th style="min-width:70px;">QR</th>
                            <th>DATE</th>
                            <th>ROLL</th>
                            <th>BOOKING</th>
                            <th>SONO</th>
                            <th>MC NO</th>
                            <th>MC DIA</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>YARN TYPE</th>
                            <th>YARN COUNT</th>
                            <th>FABRICS TYPE</th>
                            <th>FINISH GSM</th>
                            <th>FINISH DIA</th>
                            <th>OPEN/TUBE</th>
                            <th>COLOR</th>
                            <th>SL/VDQ</th>
                            <th>UQTY</th>
                            <th>LOT NO</th>
                            <th>T.POINT</th>
                            <th>ACCEPT</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="21" class="text-center small-muted">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- QR Modal - Wider -->
    <div class="modal fade no-print" id="qrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-qrcode me-2"></i> QR Code : Full Row Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- left: QR -->
                        <div class="col-md-5 text-center">
                            <div class="qr-display-wrapper">
                                <div id="modalQRContainer" style="display:inline-block;"></div>
                                <div class="qr-action-buttons">
                                    <button class="btn btn-danger btn-sm" id="downloadPDFBtn">
                                        <i class="fa-solid fa-file-pdf"></i> PDF
                                    </button>
                                    <button class="btn btn-primary btn-sm" id="printModalBtn">
                                        <i class="fa-solid fa-print"></i> Print
                                    </button>

                                </div>
                                <div class="mt-2 text-muted small">Scan QR to view all data</div>
                            </div>
                        </div>
                        <!-- right: data list in 2 columns - wider -->
                        <div class="col-md-7">
                            <!-- <h6 class="text-muted mb-2"><i class="fa-regular fa-rectangle-list me-1"></i> All Data</h6> -->
                            <div class="qr-data-grid-2col" id="modalDataList">
                                <!-- filled by JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" id="cancelModalBtn">
                        <i class="fa-solid fa-xmark me-1"></i> Cancel
                    </button>
                    <button class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fa-regular fa-circle-check me-1"></i> Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Print Section for PDF/Print -->
    <div id="printSection" style="display:none;">
        <div class="print-header">QR Code : Full Row Data</div>
        <div class="print-qr-wrapper" id="printQRContainer"></div>
        <div class="print-data-grid" id="printDataList"></div>
        <div class="print-footer">Printed from Inspection Report</div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ---------- GLOBALS ----------
        let currentModalRowData = null;
        let qrCodeInstance = null;
        let currentQRDataURL = null;

        // ---------- RENDER TABLE ----------
        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="21" class="text-center small-muted">No data found</td></tr>');
                return;
            }

            data.forEach(function(row, index) {
                var tr = $('<tr>');
                var qrTd = $('<td>').addClass('qr-code-cell');
                var btn = $('<button>')
                    .addClass('qr-btn')
                    .text('Generate QR')
                    .attr('data-idx', index)
                    .data('rowData', row);
                qrTd.append(btn);
                tr.append(qrTd);

                var fields = [
                    'BUDAT', 'ROLL', 'BOOKING_NO', 'SONO', 'MCNO', 'MC_DIA', 'BUYER', 'STYLE',
                    'YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM', 'FINISH_DIA', 'OPEN_TUBE',
                    'COLOR', 'SL_VDQ', 'UQTY', 'LOT_NO', 'T_POINT', 'ACCEPT'
                ];
                fields.forEach(function(f) {
                    var val = (row[f] !== undefined && row[f] !== null) ? row[f] : '';
                    tr.append($('<td>').text(val));
                });

                tbody.append(tr);
            });

            $('.qr-btn').off('click').on('click', function() {
                var rowData = $(this).data('rowData');
                if (rowData) {
                    openQRModal(rowData);
                }
            });
        }

        // ---------- BUILD DATA HTML ----------
        function buildDataHTML(row) {
            var fieldMap = {
                'DATE': row.BUDAT,
                'ROLL': row.ROLL,
                'BOOKING': row.BOOKING_NO,
                'SONO': row.SONO,
                'MC NO': row.MCNO,
                'MC DIA': row.MC_DIA,
                'BUYER': row.BUYER,
                'STYLE': row.STYLE,
                'YARN TYPE': row.YARN_TYPE,
                'YARN COUNT': row.YARN_COUNT,
                'FABRICS TYPE': row.FABRICS_TYPE,
                'FINISH GSM': row.FINISH_GSM,
                'FINISH DIA': row.FINISH_DIA,
                'OPEN/TUBE': row.OPEN_TUBE,
                'COLOR': row.COLOR,
                'SL/VDQ': row.SL_VDQ,
                'UQTY': row.UQTY,
                'LOT NO': row.LOT_NO,
                'T.POINT': row.T_POINT,
                'ACCEPT': row.ACCEPT
            };

            var html = '';
            var count = 0;
            for (var key in fieldMap) {
                var val = (fieldMap[key] !== undefined && fieldMap[key] !== null) ? fieldMap[key] : '';
                if (val !== '') {
                    html += `<div class="print-data-item"><span class="field">${key}:</span> <span class="value">${val}</span></div>`;
                    count++;
                }
            }
            if (count === 0) {
                html = '<div class="text-muted text-center">No data available</div>';
            }
            return html;
        }

        // ---------- BUILD QR TEXT ----------
        function buildQRText(row) {
            var fieldsForQR = {
                'DATE': row.BUDAT,
                'ROLL': row.ROLL,
                'BOOKING': row.BOOKING_NO,
                'SONO': row.SONO,
                'MC NO': row.MCNO,
                'MC DIA': row.MC_DIA,
                'BUYER': row.BUYER,
                'STYLE': row.STYLE,
                'YARN TYPE': row.YARN_TYPE,
                'YARN COUNT': row.YARN_COUNT,
                'FABRICS TYPE': row.FABRICS_TYPE,
                'FINISH GSM': row.FINISH_GSM,
                'FINISH DIA': row.FINISH_DIA,
                'OPEN/TUBE': row.OPEN_TUBE,
                'COLOR': row.COLOR,
                'SL/VDQ': row.SL_VDQ,
                'UQTY': row.UQTY,
                'LOT NO': row.LOT_NO,
                'T.POINT': row.T_POINT,
                'ACCEPT': row.ACCEPT
            };

            var lines = [];
            for (var k in fieldsForQR) {
                var v = (fieldsForQR[k] !== undefined && fieldsForQR[k] !== null) ? fieldsForQR[k] : '';
                if (v !== '') {
                    lines.push(k + ': ' + v);
                }
            }
            return lines.join('\n');
        }

        // ---------- OPEN MODAL ----------
        function openQRModal(row) {
            currentModalRowData = row;

            // Modal data list
            var html = '';
            var fieldMap = {
                'DATE': row.BUDAT,
                'ROLL': row.ROLL,
                'BOOKING': row.BOOKING_NO,
                'SONO': row.SONO,
                'MC NO': row.MCNO,
                'MC DIA': row.MC_DIA,
                'BUYER': row.BUYER,
                'STYLE': row.STYLE,
                'YARN TYPE': row.YARN_TYPE,
                'YARN COUNT': row.YARN_COUNT,
                'FABRICS TYPE': row.FABRICS_TYPE,
                'FINISH GSM': row.FINISH_GSM,
                'FINISH DIA': row.FINISH_DIA,
                'OPEN/TUBE': row.OPEN_TUBE,
                'COLOR': row.COLOR,
                'SL/VDQ': row.SL_VDQ,
                'UQTY': row.UQTY,
                'LOT NO': row.LOT_NO,
                'T.POINT': row.T_POINT,
                'ACCEPT': row.ACCEPT
            };
            for (var key in fieldMap) {
                var val = (fieldMap[key] !== undefined && fieldMap[key] !== null) ? fieldMap[key] : '';
                if (val !== '') {
                    html += `<div class="qr-data-item-2col"><span class="field">${key}:</span> <span class="value">${val}</span></div>`;
                }
            }
            $('#modalDataList').html(html);

            // Generate QR
            var container = document.getElementById('modalQRContainer');
            container.innerHTML = '';
            var qrText = buildQRText(row);

            try {
                qrCodeInstance = new QRCode(container, {
                    text: qrText,
                    width: 200,
                    height: 200,
                    colorDark: "#1f2937",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
                setTimeout(function() {
                    var canvas = container.querySelector('canvas');
                    if (canvas) {
                        currentQRDataURL = canvas.toDataURL('image/png');
                    }
                }, 100);
            } catch (e) {
                container.innerHTML = '<span class="text-danger">QR error</span>';
            }

            var modal = new bootstrap.Modal(document.getElementById('qrModal'));
            modal.show();
        }

        // ---------- PRINT ----------
        function printModalContent() {
            if (!currentModalRowData) {
                alert('No data to print.');
                return;
            }

            var row = currentModalRowData;
            var container = document.getElementById('modalQRContainer');
            var canvas = container.querySelector('canvas');
            var qrImageSrc = canvas ? canvas.toDataURL('image/png') : '';

            var dataHTML = buildDataHTML(row);

            $('#printQRContainer').html(qrImageSrc ? `<img src="${qrImageSrc}" style="max-width:180px;height:auto;border:1px solid #ddd;padding:8px;border-radius:8px;" />` : 'No QR');
            $('#printDataList').html(dataHTML);

            $('#printSection').css('display', 'block');

            setTimeout(function() {
                window.print();
                setTimeout(function() {
                    $('#printSection').css('display', 'none');
                }, 500);
            }, 300);
        }

        // ---------- DOWNLOAD PDF (matches print exactly) ----------
        function downloadPDF() {
            if (!currentModalRowData) {
                alert('No data to generate PDF.');
                return;
            }

            var row = currentModalRowData;
            var container = document.getElementById('modalQRContainer');
            var canvas = container.querySelector('canvas');
            var qrImageSrc = canvas ? canvas.toDataURL('image/png') : '';

            var dataHTML = buildDataHTML(row);

            // Build print content
            var printContent = `
            <div style="padding:30px;background:white;font-family:Arial,Helvetica,sans-serif;max-width:800px;margin:0 auto;">
                <div style="text-align:center;font-size:20px;font-weight:bold;margin-bottom:20px;border-bottom:2px solid #333;padding-bottom:12px;color:#1f2937;">
                    QR Code : Full Row Data
                </div>
                <div style="text-align:center;margin:15px 0;">
                    <img src="${qrImageSrc}" style="max-width:180px;height:auto;border:1px solid #ddd;padding:8px;border-radius:8px;" />
                </div>
                <div style="text-align:center;font-size:16px;font-weight:600;margin-bottom:15px;color:#1f2937;">
                    All Data
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 30px;font-size:13px;margin-top:15px;padding:10px 0;">
                    ${dataHTML}
                </div>
                <div style="text-align:center;font-size:11px;color:#999;margin-top:20px;border-top:1px solid #ddd;padding-top:10px;">
                    Printed from Inspection Report
                </div>
            </div>
        `;

            // Create a temporary div for PDF generation
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = printContent;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '0';
            tempDiv.style.width = '800px';
            tempDiv.style.background = 'white';
            tempDiv.style.padding = '20px';
            document.body.appendChild(tempDiv);

            // Use html2canvas to capture the div
            html2canvas(tempDiv, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false
            }).then(function(canvas) {
                var imgData = canvas.toDataURL('image/png');
                var {
                    jsPDF
                } = window.jspdf;
                var pdf = new jsPDF('p', 'mm', 'a4');
                var pdfWidth = pdf.internal.pageSize.getWidth();
                var pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('QR_Data.pdf');

                // Clean up
                document.body.removeChild(tempDiv);
            }).catch(function(err) {
                console.error('PDF generation error:', err);
                alert('Error generating PDF. Please try again.');
                document.body.removeChild(tempDiv);
            });
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
                        $('#tableBody').html('<tr><td colspan="21" class="text-center small-muted">No data found</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="21" class="text-center text-danger">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass me-1"></i> Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="21" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxK_test_inspection_Report.php',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        $('#tableBody').html('<tr><td colspan="21" class="text-center small-muted">No data returned</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="21" class="text-center text-danger">Error loading data</td></tr>');
                });
        }

        // ---------- INIT ----------
        $(function() {
            $('#backBtn').on('click', function() {
                history.back();
            });

            $('#searchBtn').on('click', searchBooking);

            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                loadAll();
            });

            $('#printModalBtn').on('click', printModalContent);
            $('#downloadPDFBtn').on('click', downloadPDF);

            $('#cancelModalBtn').on('click', function() {
                // Closes modal via data-bs-dismiss
            });

            loadAll();
        });
    </script>

</body>

</html>