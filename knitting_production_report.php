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

        .table {
            font-size: 16px;
        }

        .table thead th {
            vertical-align: middle;
            font-size: 16px;
            padding: 14px 10px;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 16px;
            padding: 12px 8px;
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
        function downloadRowPdf(row) {
            var fieldHTML = [
                ['Shift', row.SHIFT],
                ['UName', row.UNAME],
                ['SONO', row.SONO],
                ['LOT', row.LOT_NO],
                ['Style', row.STYLE],
                ['Color', row.COLOR],
                ['MCNO', row.MCNO],
                ['MC Dia', row.MC_DIA],
                ['Customer', row.CUSTOMER],
                ['FGSM', row.FINISH_GSM],
                ['F. DIA', row.FINISH_DIA],
                ['O/T', row.OPEN_TUBE],
                ['SL/VDQ', row.SL_VDQ],
                ['GGSM', row.GRAY_GSM],
                ['Buyer', row.BUYER, , 'vertical'],
                ['Y. TYPE', row.YARN_TYPE, 'vertical'],
                ['Y. COUNT', row.YARN_COUNT, 'vertical'],
                ['F. TYPE', row.FABRICS_TYPE, 'vertical'],
                ['F. PLAN', row.FEEDER_PLAN, 'vertical']
            ];

            var rowsHTML = fieldHTML.map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                return '<div class="pdf-item' + (f[2] ? ' pdf-' + f[2] : '') + '"><span class="pdf-label">' + f[0] + ':</span> <span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="width:700px;height:700px;padding:4px;background: white;font-family:Arial,Helvetica,sans-serif;color:#000000;box-sizing:border-box;border:2px solid #000000;font-weight:800;display:flex;flex-direction:column;">' +
                '<div style="display:flex;gap:4px;align-items:stretch;margin-bottom:4px;">' +
                '<div style="flex:1;min-height:215px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;background:#ffffff;border:2px solid #000000;padding:6px;box-sizing:border-box;">' +
                '<div style="background:white;color:#000000;text-align:center;font-size:27px;font-weight:800;padding:2px 0;margin-bottom:8px;letter-spacing:0;white-space:nowrap;display:inline-block;">' +
                '<span style="text-decoration:none;">PURBANI FABRICS LTD.</span>' +
                '<span style="display:block;width:100%;height:3px;background:#000000;margin-top:2px;"></span>' +
                '</div>' +
                '<div style="font-weight:800;color:#000000;line-height:1.5;word-break:break-word;">' +
                '<div style="font-size:27px;">ROLL: ' + (row.ROLL || '') + '</div>' +
                '<div style="font-size:26px;">QTY: ' + (row.PQTY || '') + '</div>' +
                '<div style="font-size:24px;">PO NO: ' + (row.PO_NUMBER || '') + '</div>' +
                '<div style="font-size:23px;">Date: ' + (row.BUDAT || '') + '</div>' +
                '</div>' +
                '</div>' +
                '<div id="rowQrBoxRight" style="flex:none;width:215px;height:215px;display:flex;align-items:center;justify-content:center;border:2px solid #000000;background:#ffffff;"></div>' +
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
            var qrBoxes = [tempDiv.querySelector('#rowQrBoxRight')];
            if (typeof QRCode !== 'undefined') {
                qrBoxes.forEach(function(qrBox) {
                    if (qrBox) {
                        new QRCode(qrBox, {
                            text: String(row.ROLL || ''),
                            width: 208,
                            height: 208,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    }
                });
            }

            var style = document.createElement('style');
            style.textContent = '' +
                '.pdf-grid{display:grid;grid-template-columns:repeat(4,1fr);column-gap:8px;row-gap:2px;background:#ffffff;}' +
                '.pdf-item{grid-column:span 2;font-size:25px;font-weight:800;line-height:1.15;margin-left:3px; padding:3px 0;word-break:break-word;background:#ffffff;color:#000000;}' +
                '.pdf-item.pdf-vertical{grid-column:1 / -1;}' +
                '.pdf-label,.pdf-value{font-weight:800;color:#000000;}' +
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
                    // Keep the printed label proportional to its actual content height.
                    var pdf = new jsPDFLib.jsPDF({
                        orientation: 'portrait',
                        unit: 'cm',
                        format: 'a4'
                    });
                    // Leave a printer-safe margin on the A4 page.
                    pdf.addImage(imgData, 'PNG', 1, 1, 6, 6);
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