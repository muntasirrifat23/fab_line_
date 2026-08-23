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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        // ---------- ROW PDF DOWNLOAD (image-style card, same as Knitting Program Report) ----------
        function downloadRowPdf(row) {
            var fieldHTML = [
                ['Date', row.BUDAT],
                ['Roll No', row.ROLL],
                ['PO Number', row.PO_NUMBER],
                ['PQTY', row.PQTY],
                ['SONO', row.SONO],
                ['Buyer', row.BUYER],
                ['Style', row.STYLE],
                ['Color', row.COLOR],
                ['MCNO', row.MCNO],
                ['MC Dia', row.MC_DIA],
                ['Customer', row.CUSTOMER],
                ['Shift', row.SHIFT],
                ['Yarn Type', row.YARN_TYPE],
                ['Yarn Count', row.YARN_COUNT],
                ['Fabrics Type', row.FABRICS_TYPE],
                ['Finish GSM', row.FINISH_GSM],
                ['Finish Dia', row.FINISH_DIA],
                ['Open / Tube', row.OPEN_TUBE],
                ['SL/VDQ', row.SL_VDQ],
                ['Gray GSM', row.GRAY_GSM],
                ['Feeder Plan', row.FEEDER_PLAN],
                ['Lot No', row.LOT_NO],
                ['KNIT MATERIAL CODE', row.KNIT_MATERIAL_CODE, true],
                ['KNIT M DES', row.KNIT_M_DES, true]
            ];

            var rowsHTML = fieldHTML.map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                var cls = f[2] ? 'pdf-item pdf-item-full' : 'pdf-item';
                return '<div class="' + cls + '"><span class="pdf-label">' + f[0] + ' :</span><span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="position:relative;width:760px;min-height:1050px;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">' +
                '<div style="text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a;border-bottom:3px solid #2563eb;padding-bottom:10px;margin-bottom:16px;">' +
                'Knitting Production Report' +
                '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;font-size:14px;font-weight:bold;margin-bottom:10px; margin-left:10px;">' +
                '<span>Roll : ' + (row.ROLL || '') + '</span>' +
                '</div>' +
                '<div class="pdf-grid">' + rowsHTML + '</div>' +
                '<div style="text-align:center;font-size:11px;color: black; margin-top:16px;border-top:1px solid #e5e7eb;padding-top:8px;">' +
                'Generated from Knitting Production Report - ' + new Date().toLocaleString() +
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
                '.pdf-sign{position:absolute;left:24px;right:24px;bottom:55px;display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:20px;padding:0 10px;text-align:center;font-size:13px;font-weight:bold;color:#000000;}' +
                '.pdf-sign-item{display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:8px;}' +
                '.pdf-sign-line{width:80%;border-top:1px solid #000000;margin-top:0;}' +
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
                pdf.save('Knitting_Production_' + (row.ROLL || 'Roll') + '.pdf');
                document.body.removeChild(tempDiv);
                document.body.removeChild(style);
            }).catch(function(err) {
                console.error('PDF generation error:', err);
                alert('Error generating PDF. Please try again.');
                document.body.removeChild(tempDiv);
                document.body.removeChild(style);
            });
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