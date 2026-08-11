<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | Program</title>

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
            <h1 class="title">Knitting Program Report</h1>
            <div></div>
        </div>

        <div class="panel mb-3">
            <div class="row g-3 align-items-end controls">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size: larger; color: black;">
                        Search SONO or Document NO
                    </label>

                    <div class="input-group input-group-sm d-flex align-items-center gap-2">
                        <input type="text" id="bookingInput" class="form-control" placeholder="SONO, BOOKING or Document NO">
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
                            <th>MAIN TID</th>
                            <th>SUB TID</th>
                            <th>PO NO</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>SUPPLIER</th>
                            <th>QTY</th>
                            <th>O / T</th>
                            <th>FINISH GSM</th>
                            <th>FINISH DIA</th>
                            <th>FABRICS TYPE</th>
                            <th>YARN TYPE</th>
                            <th>YARN COUNT</th>
                            <th>SL/VDQ</th>
                            <th>MCDIA</th>
                            <th>GRAY GSM</th>
                            <th>FEEDER PLAN</th>
                            <th>SHIFT</th>
                            <th>LOT NO</th>
                            <th>KNIT M DESCRIPTION</th>
                            <th>KNIT MATERIAL CODE</th>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="25" class="text-center small-muted">No data found</td></tr>');
                return;
            }

            data.forEach(function(row) {
                var tr = $('<tr>');
                var pdfBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('btn btn-danger btn-sm pdf-row-btn')
                    .html('<i class="fa-solid fa-file-pdf"></i> PDF')
                    .attr('title', 'Download PDF')
                    .on('click', function() { downloadRowPdf(row); });
                tr.append($('<td class="text-center">').append(pdfBtn));
                tr.append($('<td>').text(row.CREATED_DATE || ''));
                tr.append($('<td>').text(row.MAIN_TID || ''));
                tr.append($('<td>').text(row.SUB_TID || ''));
                tr.append($('<td>').text(row.PO_NUMBER || ''));
                tr.append($('<td>').text(row.SONO || ''));
                tr.append($('<td>').text(row.BUYER || ''));
                tr.append($('<td>').text(row.STYLE || ''));
                tr.append($('<td>').text(row.COLOR || ''));
                tr.append($('<td>').text(row.SUPPLIER || ''));
                tr.append($('<td>').text(row.QTY || ''));
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
                tr.append($('<td>').text(row.SHIFT || ''));
                tr.append($('<td>').text(row.LOT || ''));
                tr.append($('<td>').text(row.KNIT_M_DESCRIPTION || ''));
                tr.append($('<td>').text(row.KNIT_MATERIAL_CODE || ''));
                tbody.append(tr);
            });
        }

        function searchBooking() {
            var booking = $('#bookingInput').val().trim();
            if (!booking) {
                alert('Please enter SONO or Booking to search');
                return;
            }
            $('#searchBtn').prop('disabled', true).text('Searching...');
            $.ajax({
                    url: 'ajaxKnittingProgram_Report.php',
                    data: {
                        booking: booking
                    },
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) renderTableRows(resp.data);
                    else $('#tableBody').html('<tr><td colspan="25" class="text-center small-muted">No data found</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="25" class="text-center text-danger">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).text('Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="25" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxKnittingProgram_Report.php',
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

        // ---------- ROW PDF DOWNLOAD (image-style card) ----------
        function downloadRowPdf(row) {
            var fieldHTML = [
                ['Date', row.CREATED_DATE],
                ['Main TID', row.MAIN_TID],
                ['Sub TID', row.SUB_TID],
                ['PO Number', row.PO_NUMBER],
                ['SONO', row.SONO],
                ['Buyer', row.BUYER],
                ['Style', row.STYLE],
                ['Color', row.COLOR],
                ['Supplier', row.SUPPLIER],
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
                ['Shift', row.SHIFT],
                ['Lot No', row.LOT],
                ['Knit M Description', row.KNIT_M_DESCRIPTION],
                ['Knit Material Code', row.KNIT_MATERIAL_CODE]
            ];

            var rowsHTML = fieldHTML.map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                return '<div class="pdf-item"><span class="pdf-label">' + f[0] + ' :</span><span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="width:760px;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">' +
                    '<div style="text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a;border-bottom:3px solid #2563eb;padding-bottom:10px;margin-bottom:16px;">' +
                        'Knitting Program Report' +
                    '</div>' +
                    '<div style="font-size:14px;font-weight:bold;margin-bottom:10px;">Program : ' + (row.SUB_TID || '') + '</div>' +
                    '<div class="pdf-grid">' + rowsHTML + '</div>' +
                    '<div style="text-align:center;font-size:11px;color:#9ca3af;margin-top:16px;border-top:1px solid #e5e7eb;padding-top:8px;">' +
                        'Generated from Knitting Program Report - ' + new Date().toLocaleString() +
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
                '.pdf-grid{display:grid;grid-template-columns:1fr;gap:6px 0;border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:#f9fafb;}' +
                '.pdf-item{font-size:13px;line-height:1.6;border-bottom:1px dashed #e5e7eb;padding:4px 2px;word-break:break-word;}' +
                '.pdf-label{font-weight:bold;color:#374151;}' +
                '.pdf-value{margin-left:6px;color:#111827;}';
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
                pdf.save('Program_' + (row.SUB_TID || row.PO_NUMBER || 'Row') + '.pdf');
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