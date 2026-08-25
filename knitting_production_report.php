<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | Production</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">

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

        .controls .form-label {
            font-size: 12px;
            color: #6b7280;
        }

        .controls .btn {
            min-width: 120px;
        }

        h1.title {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 18px;
        }

        .table-container {
            margin-top: 12px;
            background: transparent;
        }

        .table thead th {
            vertical-align: middle;
        }

        .small-muted {
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-dark" id="backBtn" style="background-color:#1f2937;color:#fff;padding:12px;border-radius:8px;">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i>
                Back to Initial Page
            </button>
            <h1 class="title">Knitting Production Report</h1>
            <div></div>
        </div>

        <div class="panel mb-3">
            <div class="row g-3 align-items-end controls">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size: larger; color: black;">
                        Search Roll Or PO Number
                    </label>

                    <div class="input-group input-group-sm d-flex align-items-center gap-2">
                        <input type="text" id="bookingInput" class="form-control" placeholder="Enter Roll / Booking No">
                        <button class="btn px-4" id="searchBtn" style="margin-top:8px; background:#2563eb; border:1px solid #2563eb; color:#fff; border-radius:8px;">
                            <i class="fa-solid fa-magnifying-glass me-1" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i>
                            Search
                        </button>

                        <button class="btn px-4" id="clearBtn" style="margin-top:8px; margin-left:8px; background:#6b7280; border:1px solid #6b7280; color:#fff; border-radius:8px;">
                            <i class="fa-solid fa-rotate-left me-1" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i>
                            Clear
                        </button>
                    </div>

                </div>
                <div class="col-md-4 text-end">
                    <!-- reserved -->
                </div>
            </div>
        </div>


        <div class="table-container">
            <div class="panel">
                <table class="table table-bordered table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>PDF</th>
                            <th>DATE</th>
                            <th>ROLL NO</th>
                            <th>PO NUMBER</th>
                            <th>PQTY</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MCNO</th>
                            <th>MC DIA</th>
                            <th>CUSTOMER</th>
                            <th>SHIFT</th>
                            <th>YARN TYPE</th>
                            <th>YARN COUNT</th>
                            <th>FABRICS TYPE</th>
                            <th>FINISH GSM</th>
                            <th>FINISH DIA</th>
                            <th>OPEN / TUBE</th>
                            <th>SL/VDQ</th>
                            <th>GRAY GSM</th>
                            <th>FEEDER PLAN</th>
                            <th>LOT NO</th>
                            <th>KNIT MATERIAL CODE</th>
                            <th>KNIT M DES</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="25" class="text-center small-muted">Loading data...</td>
                        </tr>
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
        // ---------- ROW PDF DOWNLOAD (A4 page, 6cm x 6cm label top-left, QR contains ROLL No only) ----------
        function downloadRowPdf(row) {
            var fieldHTML = [
                ['PO Number', row.PO_NUMBER],
                ['QTY', row.PQTY],
                ['Shift', row.SHIFT],
                ['Date', row.BUDAT],
                ['UName', row.UNAME],
                ['SONO', row.SONO],
                ['Buyer', row.BUYER],
                ['Style', row.STYLE],
                ['Color', row.COLOR],
                ['MCNO', row.MCNO],
                ['MC Dia', row.MC_DIA],
                ['Customer', row.CUSTOMER],
                ['Yarn Type', row.YARN_TYPE],
                ['Yarn Count', row.YARN_COUNT],
                ['Fabrics Type', row.FABRICS_TYPE],
                ['Finish GSM', row.FINISH_GSM],
                ['Finish Dia', row.FINISH_DIA],
                ['Open / Tube', row.OPEN_TUBE],
                ['SL/VDQ', row.SL_VDQ],
                ['Gray GSM', row.GRAY_GSM],
                ['Feeder Plan', row.FEEDER_PLAN],
                ['Lot No', row.LOT_NO]
            ];

            var rowsHTML = fieldHTML.map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                return '<div class="pdf-item"><span class="pdf-label">' + f[0] + ':</span> <span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="width:700px;height:700px;padding:16px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#000000;box-sizing:border-box;border:2px solid #94a3b8;">' +
                '<div style="background:#1e3a8a;color:#ffffff;text-align:center;font-size:24px;font-weight:800;padding:8px;border-radius:6px;margin-bottom:12px;letter-spacing:1px;">' +
                'PURBANI FABRICS LTD.' +
                '</div>' +
                '<div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:12px;">' +
                '<div style="flex:1;min-width:0;background:#f1f5f9;border:2px solid #1e3a8a;border-radius:8px;padding:10px 14px;">' +
                '<div style="font-size:17px;font-weight:800;color:#1e3a8a;margin-bottom:4px;">ROLL NO</div>' +
                '<div style="font-size:30px;font-weight:800;color:#000000;font-family:Consolas,monospace;word-break:break-all;line-height:1.2;">' + (row.ROLL || '') + '</div>' +
                '</div>' +
                '<div id="rowQrBox" style="flex:none;width:186px;height:186px;display:flex;align-items:center;justify-content:center;border:2px solid #000000;border-radius:8px;background:#ffffff;"></div>' +
                '</div>' +
                '<div class="pdf-grid">' + rowsHTML + '</div>' +
                '</div>';

            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '0';
            document.body.appendChild(tempDiv);

            // Generate QR code - contains ONLY the ROLL number (knitting_production.ROLL)
            var qrBox = tempDiv.querySelector('#rowQrBox');
            if (qrBox && typeof QRCode !== 'undefined') {
                new QRCode(qrBox, {
                    text: String(row.ROLL || ''),
                    width: 180,
                    height: 180,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }

            var style = document.createElement('style');
            style.textContent = '' +
                '.pdf-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border:2px solid #94a3b8;border-radius:6px;background:#ffffff;}' +
                '.pdf-item{font-size:16px;line-height:1.35;border-bottom:1px solid #cbd5e1;border-right:1px solid #cbd5e1;padding:5px 9px;word-break:break-word;background:#ffffff;color:#000000;}' +
                '.pdf-item:nth-child(2n){border-right:none;}' +
                '.pdf-item:nth-last-child(-n+2){border-bottom:none;}' +
                '.pdf-label{font-weight:800;color:#1e3a8a;}' +
                '.pdf-value{color:#111111;}' +
                '</style>';
            document.body.appendChild(style);

            setTimeout(function() {
                html2canvas(tempDiv, {
                    scale: 3,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(function(canvas) {
                    var imgData = canvas.toDataURL('image/png');
                    var jsPDFLib = window.jspdf;
                    // A4 page - 6cm x 6cm label placed at top-left
                    var pdf = new jsPDFLib.jsPDF({
                        orientation: 'portrait',
                        unit: 'cm',
                        format: 'a4'
                    });
                    pdf.addImage(imgData, 'PNG', 0, 0, 6, 6);
                    pdf.save('Knitting_Production_' + (row.ROLL || 'Roll') + '.pdf');
                    document.body.removeChild(tempDiv);
                    document.body.removeChild(style);
                }).catch(function(err) {
                    console.error('PDF generation error:', err);
                    alert('Error generating PDF. Please try again.');
                    document.body.removeChild(tempDiv);
                    document.body.removeChild(style);
                });
            }, 400);
        }

        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="25" class="text-center small-muted">No Roll Or Booking No found</td></tr>');
                return;
            }

            data.forEach(function(row) {
                var tr = $('<tr>');
                var pdfBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('btn btn-primary btn-sm pdf-row-btn')
                    .html('<i class="fa-solid fa-file-pdf" style="background:none;border:none;box-shadow:none;transform:none;"></i> PDF')
                    .attr('title', 'Download PDF')
                    .on('click', function() {
                        downloadRowPdf(row);
                    });
                tr.append($('<td class="text-center">').append(pdfBtn));
                tr.append($('<td>').text(row.BUDAT || ''));
                tr.append($('<td>').text(row.ROLL || ''));
                tr.append($('<td>').text(row.PO_NUMBER || ''));
                tr.append($('<td>').text(row.PQTY || ''));
                tr.append($('<td>').text(row.SONO || ''));
                tr.append($('<td>').text(row.BUYER || ''));
                tr.append($('<td>').text(row.STYLE || ''));
                tr.append($('<td>').text(row.COLOR || ''));
                tr.append($('<td>').text(row.MCNO || ''));
                tr.append($('<td>').text(row.MC_DIA || ''));
                tr.append($('<td>').text(row.CUSTOMER || ''));
                tr.append($('<td>').text(row.SHIFT || ''));
                tr.append($('<td>').text(row.YARN_TYPE || ''));
                tr.append($('<td>').text(row.YARN_COUNT || ''));
                tr.append($('<td>').text(row.FABRICS_TYPE || ''));
                tr.append($('<td>').text(row.FINISH_GSM || ''));
                tr.append($('<td>').text(row.FINISH_DIA || ''));
                tr.append($('<td>').text(row.OPEN_TUBE || ''));
                tr.append($('<td>').text(row.SL_VDQ || ''));
                tr.append($('<td>').text(row.GRAY_GSM || ''));
                tr.append($('<td>').text(row.FEEDER_PLAN || ''));
                tr.append($('<td>').text(row.LOT_NO || ''));
                tr.append($('<td>').text(row.KNIT_MATERIAL_CODE || ''));
                tr.append($('<td>').text(row.KNIT_M_DES || ''));
                tbody.append(tr);
            });
        }

        function searchBooking() {
            var search = $('#bookingInput').val().trim();

            if (!search) {
                alert('Please enter Roll No or Booking No');
                return;
            }

            $('#searchBtn').prop('disabled', true).html('Searching...');

            $.ajax({
                    url: 'ajaxKnittingProduction_Report.php',
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
                        $('#tableBody').html(
                            '<tr><td colspan="25" class="text-center small-muted">No data found</td></tr>'
                        );
                    }
                })
                .fail(function() {
                    $('#tableBody').html(
                        '<tr><td colspan="25" class="text-center text-danger">Error searching</td></tr>'
                    );
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html(
                        '<i class="fa-solid fa-magnifying-glass me-1" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i> Search'
                    );
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="25" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxKnittingProduction_Report.php',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) renderTableRows(resp.data);
                    else $('#tableBody').html('<tr><td colspan="25" class="text-center small-muted">No data returned</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="25" class="text-center text-danger">Error loading data</td></tr>');
                });
        }

        $(function() {
            $('#backBtn').on('click', function() {
                history.back();
            });
            $('#searchBtn').on('click', searchBooking);
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