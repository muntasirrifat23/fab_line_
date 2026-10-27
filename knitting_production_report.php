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
            padding: 0 18px 18px;
            background: #f5f7fa;
        }


        .page-header {
            position: relative;
            min-height: 76px;
            padding: 8px 18px;
            display: flex;
            align-items: center !important;
            justify-content: center !important;
            background: #5b9084;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(20, 30, 50, 0.12);
        }

        h1.title {
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 700;
            color: #ffffff;
            font-size: clamp(1.7rem, 3vw, 2.4rem) !important;
            text-align: center;
            margin: 0;
            line-height: 1.15;
            padding: 0 12px;
            box-sizing: border-box;
        }

        .page-header .back-btn {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
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

        .table-container {
            margin-top: 12px;
            background: transparent;
        }

        .report-table {
            min-width: 1700px;
            margin-bottom: 0;
        }

        .table {
            font-size: 16px;
        }

        .table thead th {
            vertical-align: middle;
            font-size: 16px;
            padding: 14px 10px;
            white-space: nowrap;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 16px;
            padding: 12px 8px;
            white-space: nowrap;
        }

        .small-muted {
            font-size: 12px;
            color: #6b7280;
        }

        #backBtn,
        .back-btn {
            background: #000000 !important;
            color: #ffffff !important;
            border: 2px solid #000000 !important;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        #backBtn:hover,
        .back-btn:hover {
            background: #ffffff !important;
            border-color: #000000 !important;
            color: #000000 !important;
            text-decoration: none;
        }

        /* ---- Action buttons ---- */
        .action-cell {
            white-space: nowrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin: 2px;
            font-weight: 600;
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        .btn-print-row {
            background: #16a34a;
            color: #fff;
        }

        .btn-print-row:hover {
            background: #15803d;
            color: #fff;
        }

        .btn-view-row {
            background: #2563eb;
            color: #fff;
        }

        .btn-view-row:hover {
            background: #1d4ed8;
            color: #fff;
        }

        /* ---- Printable label styles (shared by preview & print) ---- */
        .pdf-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            column-gap: 8px;
            row-gap: 2px;
            background: #ffffff;
        }

        .pdf-item {
            grid-column: span 2;
            font-size: 27px;
            font-weight: 800;
            line-height: 1.15;
            margin-left: 3px;
            padding: 3px 0;
            word-break: break-word;
            background: #ffffff;
            color: #000000;
        }

        .pdf-item.pdf-vertical {
            grid-column: 1 / -1;
        }

        .pdf-label,
        .pdf-value {
            font-weight: 800;
            color: #000000;
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
            padding: 20px;
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
            background: #1f2937;
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

        .preview-foot .btn-print-row {
            padding: 8px 18px;
            font-size: 14px;
        }

        @media (max-width: 991.98px) {
            body {
                padding: 0 14px 14px;
            }

            .panel {
                padding: 14px;
            }

            .page-header {
                min-height: 92px;
            }

            .table thead th,
            .table tbody td {
                font-size: 14px;
                padding: 10px 8px;
            }
        }

        @media (max-width: 767.98px) {
            body {
                padding: 0 10px 10px;
            }

            .page-header {
                padding: 10px 12px;
            }

            .page-header .title {
                font-size: clamp(1.7rem, 6vw, 1.9rem) !important;
            }

            #backBtn,
            .back-btn {
                padding: 8px 12px;
                font-size: 14px;
            }

            .panel {
                padding: 12px;
            }

            .controls .form-label {
                font-size: 16px !important;
            }

            .controls .input-group {
                display: flex;
                flex-wrap: wrap;
                gap: 8px !important;
            }

            .controls .input-group .form-control {
                flex: 1 1 100%;
                width: 100%;
                min-width: 0;
            }

            .controls .input-group button {
                flex: 1 1 calc(50% - 4px);
                min-width: 0;
                margin: 0 !important;
                padding-left: 12px !important;
                padding-right: 12px !important;
            }

            .table-container .panel {
                padding: 8px;
            }

            .table thead th,
            .table tbody td {
                font-size: 13px;
                padding: 8px 6px;
            }

            .action-btn {
                font-size: 11px;
                padding: 6px 8px;
            }

            .preview-overlay {
                padding: 8px;
            }

            .preview-box {
                max-height: 96vh;
            }

            .preview-body {
                padding: 10px;
            }

            .preview-foot {
                padding: 10px;
            }
        }

        @media (max-width: 399.98px) {
            body {
                padding: 0 8px 8px;
            }

            .page-header .title {
                font-size: clamp(1.6rem, 6vw, 1.8rem) !important;
            }

            .panel {
                padding: 10px;
            }

            .page-header {
                padding: 12px 10px;
            }

            .page-header .back-btn {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center mb-3">
            <button class="back-btn" id="backBtn">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i>
                Back To Report
            </button>
            <h1 class="title">Knitting Production Report</h1>
        </div>

        <div class="panel mb-3">
            <div class="row g-3 align-items-end controls">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size: larger; color: black;">
                        Search Roll Or PO Number
                    </label>

                    <div class="input-group input-group-sm d-flex align-items-center gap-2">
                        <input type="text" id="bookingInput" class="form-control" placeholder="Enter Roll / PO No">
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
            </div>
        </div>


        <div class="table-container">
            <div class="panel">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm report-table">
                        <thead class="table-dark">
                            <tr>
                                <th>ACTION</th>
                                <th>DATE</th>
                                <th>ROLL NO</th>
                                <th>PO NUMBER</th>
                                <th>QTY</th>
                                <th>SONO</th>
                                <th>BUYER</th>
                                <th>STYLE</th>
                                <th>COLOR</th>
                                <th>MCNO</th>
                                <th>MC DIA</th>
                                <th>CUSTOMER</th>
                                <th>SHIFT</th>
                                <th>FABRICS TYPE</th>
                                <th>FINISH GSM</th>
                                <th>FINISH DIA</th>
                                <th>YARN COUNT</th>
                                <th>YARN BRAND</th>
                                <th>YARN LOT</th>
                                <th>CATEGORY</th>
                                <th>OP NAME</th>
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

    </div>

    <div class="preview-overlay" id="previewOverlay">
        <div class="preview-box">
            <div class="preview-head">
                <h5><i class="fa-solid fa-eye" style="margin-right:8px;background:none;border:none;box-shadow:none;transform:none;"></i>Print Preview</h5>
                <button type="button" class="preview-close" id="previewClose" title="Close">
                    <i class="fa-solid fa-xmark" style="background:none;border:none;box-shadow:none;transform:none;"></i>
                </button>
            </div>
            <div class="preview-body" id="previewBody"></div>
            <div class="preview-foot">
                <button type="button" class="action-btn btn-print-row" id="previewPrint">
                    <i class="fa-solid fa-print" style="background:none;border:none;box-shadow:none;transform:none;"></i> Print
                </button>
            </div>
        </div>
    </div>

    <script src="jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        function buildRowCardElement(row) {
            var escapeLabelValue = function(value) {
                return String(value === null || value === undefined ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            var fieldHTML = [
                ['Shift', row.SHIFT],
                ['OP NAME', row.UNAME],
                ['SONO', row.SONO],
                ['BUYER', row.BUYER],
                ['Style', row.STYLE],
                ['MCNO', row.MCNO],
                ['MC Dia', row.MC_DIA],
                ['Customer', row.CUSTOMER],
                ['FGSM', row.FINISH_GSM],
                ['F. DIA', row.FINISH_DIA],
                ['Color', row.COLOR, 'vertical'],
                ['F. TYPE', row.FABRICS_TYPE, 'vertical'],
                ['Y. COUNT', row.YARN_COUNT, 'vertical'],
                ['Y. BRAND', row.YBRAND, 'vertical'],
                ['Y. LOT', row.YARN_LOT, 'vertical'],
                ['CATEGORY', row.CATEGORY, 'vertical']
            ];

            var rowsHTML = fieldHTML.map(function(f) {
                var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
                return '<div class="pdf-item' + (f[2] ? ' pdf-' + f[2] : '') + '"><span class="pdf-label">' + f[0] + ':</span> <span class="pdf-value">' + escapeLabelValue(val) + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="width:700px;height:700px;padding:4px;background: white;font-family:Arial,Helvetica,sans-serif;color:#000000;box-sizing:border-box;border:2px solid #000000;font-weight:800;text-transform:uppercase;display:flex;flex-direction:column;">' +
                '<div style="display:flex;gap:4px;align-items:stretch;margin-bottom:4px;">' +
                '<div style="flex:1;min-height:215px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;background:#ffffff;border:2px solid #000000;padding:6px;box-sizing:border-box;">' +
                '<div style="background:white;color:#000000;text-align:center;font-size:30px;font-weight:800;padding:2px 0;margin-bottom:8px;letter-spacing:0;white-space:nowrap;display:inline-block;">' +
                '<span style="text-decoration:none;">PURBANI FABRICS LTD.</span>' +
                '<span style="display:block;width:100%;height:3px;background:#000000;margin-top:2px;"></span>' +
                '</div>' +
                '<div style="font-weight:800;color:#000000;line-height:1.3;word-break:break-word;">' +
                '<div style="font-size:26px;">ROLL: ' + escapeLabelValue(row.ROLL || '') + '</div>' +
                '<div style="font-size:25px;">QTY: ' + escapeLabelValue(row.PQTY || '') + '</div>' +
                '<div style="font-size:24px;">PO NO: ' + escapeLabelValue(row.PO_NUMBER || '') + '</div>' +
                '<div style="font-size:22px;">DATE: ' + escapeLabelValue(row.BUDAT || '') + '</div>' +
                '</div>' +
                '</div>' +
                '<div id="rowQrBoxRight" style="flex:none;width:200px;min-height:200px;height:auto;align-self:stretch;box-sizing:border-box;display:flex;align-items:center;justify-content:center;border:2px solid #000000;background:#ffffff;"></div>' +
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
            var qrBox = tempDiv.querySelector('#rowQrBoxRight');
            if (typeof QRCode !== 'undefined' && qrBox) {
                new QRCode(qrBox, {
                    text: String(row.ROLL || ''),
                    width: 194,
                    height: 194,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });

                // Convert the QR canvas to an image so it survives being copied into
                // the print iframe / preview (canvas pixels are not serializable).
                var canvas = qrBox.querySelector('canvas');
                if (canvas) {
                    var img = document.createElement('img');
                    img.src = canvas.toDataURL('image/png');
                    img.width = 194;
                    img.height = 194;
                    qrBox.innerHTML = '';
                    qrBox.appendChild(img);
                }
            }

            return tempDiv;
        }

        function renderCardImage(row, done, fail) {
            var card = buildRowCardElement(row);

            setTimeout(function() {
                html2canvas(card, {
                    scale: 3,
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
            }, 400);
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

                // Identical to the old PDF: A4 portrait, card image 6cm x 6cm at 1cm/1cm.
                var doc = iframe.contentWindow.document;
                doc.open();
                doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Knitting Production</title><style>' +
                    '@page{size:A4 portrait;margin:0;}' +
                    'html,body{margin:0;padding:0;background:#fff;}' +
                    'img{position:absolute;left:1cm;top:1cm;width:6cm;height:6cm;}' +
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
                    printImg.onload = function() {
                        setTimeout(doPrint, 150);
                    };
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
                var availWidth = body.width() - 20;
                var maxW = Math.min(availWidth, 520);
                if (!(maxW > 0)) maxW = 520;

                var img = $('<img>').attr('src', imgData).css({
                    width: maxW + 'px',
                    height: maxW + 'px',
                    display: 'block',
                    margin: '0 auto',
                    border: '1px solid #cbd5e1',
                    background: '#fff'
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

        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="21" class="text-center small-muted">No Roll Or PO No found</td></tr>');
                return;
            }

            data.forEach(function(row) {
                var tr = $('<tr>');
                var printBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('action-btn btn-print-row')
                    .html('<i class="fa-solid fa-print" style="background:none;border:none;box-shadow:none;transform:none;"></i> PRINT')
                    .attr('title', 'Print label')
                    .on('click', function() {
                        printRow(row);
                    });
                var viewBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('action-btn btn-view-row')
                    .html('<i class="fa-solid fa-eye" style="background:none;border:none;box-shadow:none;transform:none;"></i> VIEW')
                    .attr('title', 'Preview label')
                    .on('click', function() {
                        viewRow(row);
                    });
                tr.append($('<td class="text-center action-cell">').append(printBtn).append(viewBtn));
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
                tr.append($('<td>').text(row.FABRICS_TYPE || ''));
                tr.append($('<td>').text(row.FINISH_GSM || ''));
                tr.append($('<td>').text(row.FINISH_DIA || ''));
                tr.append($('<td>').text(row.YARN_COUNT || ''));
                tr.append($('<td>').text(row.YBRAND || ''));
                tr.append($('<td>').text(row.YARN_LOT || ''));
                tr.append($('<td>').text(row.CATEGORY || ''));
                tr.append($('<td>').text(row.UNAME || ''));
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
                            '<tr><td colspan="21" class="text-center small-muted">No data found</td></tr>'
                        );
                    }
                })
                .fail(function() {
                    $('#tableBody').html(
                        '<tr><td colspan="21" class="text-center text-danger">Error searching</td></tr>'
                    );
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html(
                        '<i class="fa-solid fa-magnifying-glass me-1" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i> Search'
                    );
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="21" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxKnittingProduction_Report.php',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) renderTableRows(resp.data);
                    else $('#tableBody').html('<tr><td colspan="21" class="text-center small-muted">No data returned</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="21" class="text-center text-danger">Error loading data</td></tr>');
                });
        }

        $(function() {
            $('#backBtn').on('click', function() {
                history.back();
            });
            $('#searchBtn').on('click', searchBooking);
            $('#bookingInput').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchBooking();
                }
            });
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                loadAll();
            });

            // Preview modal close handlers
            $('#previewClose').on('click', closePreview);
            $('#previewOverlay').on('click', function(e) {
                if (e.target === this) closePreview();
            });
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') closePreview();
            });

            // initial load: show all data
            loadAll();
        });
    </script>

</body>

</html>