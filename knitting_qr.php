<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting QR Report</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">

    <style>
        .qr-dropdown-panel {
            display: inline-block;
            background: #fff;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            padding: 15px;
        }

        .qr-content {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .qr-code-wrapper {
            width: 170px;
            text-align: center;
        }

        .qr-info {
            flex: 1;
            font-size: 13px;
        }

        .qr-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 25px;
        }

        .qr-grid div {
            white-space: nowrap;
        }

        .qr-lot {
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            word-break: break-word;
        }

        .qr-buttons {
            margin-top: 15px;
        }

        .qr-buttons button {
            margin-right: 8px;
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
            <h1 class="title">Knitting QR Report</h1>
            <div></div>
        </div>

        <div class="table-container">
            <div class="panel">
                <table class="table table-bordered table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>QR PRINT</th>
                            <th>Program</th>
                            <th>MCNO</th>
                            <th>BUYER</th>
                            <th>BOOKING</th>
                            <th>SONO</th>
                            <th>STYLE</th>
                            <th>FABRICS TYPE</th>
                            <th>YARN COUNT</th>
                            <th>YARN TYPE</th>
                            <th>FINISH GSM</th>
                            <th>FINISH DIA</th>
                            <th>OPEN / TUBE</th>
                            <th>LOT NO</th>
                            <th>QTY</th>
                            <th>COLOR</th>
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

    <script src="jquery.min.js"></script>
    <script src="js/qrcode.min.js"></script>

    <script>
        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="16" class="text-center small-muted">No data found</td></tr>');
                return;
            }

            data.forEach(function(row) {
                var tr = $('<tr>');
                var actionBtn = $('<button>')
                    .addClass('btn btn-sm btn-primary qr-action-btn')
                    .text('QR CODE')
                    .data('row', row);

                tr.append($('<td>').append(actionBtn));
                tr.append($('<td>').text(row.SUB_TID || ''));
                tr.append($('<td>').text(row.MCNO || ''));
                tr.append($('<td>').text(row.BUYER || ''));
                tr.append($('<td>').text(row.BOOKING || ''));
                tr.append($('<td>').text(row.SONO || ''));
                tr.append($('<td>').text(row.STYLE || ''));
                tr.append($('<td>').text(row.FABRICS_TYPE || ''));
                tr.append($('<td>').text(row.YARN_COUNT || ''));
                tr.append($('<td>').text(row.YARN_TYPE || ''));
                tr.append($('<td>').text(row.FINISH_GSM || ''));
                tr.append($('<td>').text(row.FINISH_DIA || ''));
                tr.append($('<td>').text(row.OPEN_TUBE || ''));
                tr.append($('<td>').text(row.LOT_NO || ''));
                tr.append($('<td>').text(row.QTY || ''));
                tr.append($('<td>').text(row.COLOR || ''));
                tbody.append(tr);
            });
        }

     function buildDropdownContent(row){

return `
<div class="qr-dropdown-panel">

<div class="qr-content">

    <div class="qr-code-wrapper">
        <div class="qr-code-target"></div>
    </div>

    <div class="qr-info">

        <div class="qr-grid">

            <div><b>Program :</b> ${row.SUB_TID||''}</div>
            <div><b>MCNO :</b> ${row.MCNO||''}</div>

            <div><b>Buyer :</b> ${row.BUYER||''}</div>
            <div><b>Booking :</b> ${row.BOOKING||''}</div>

            <div><b>SONO :</b> ${row.SONO||''}</div>
            <div><b>Style :</b> ${row.STYLE||''}</div>

            <div><b>Fabrics :</b> ${row.FABRICS_TYPE||''}</div>
            <div><b>Yarn Count :</b> ${row.YARN_COUNT||''}</div>

            <div><b>Yarn Type :</b> ${row.YARN_TYPE||''}</div>
            <div><b>Finish GSM :</b> ${row.FINISH_GSM||''}</div>

            <div><b>Finish Dia :</b> ${row.FINISH_DIA||''}</div>
            <div><b>Open/Tube :</b> ${row.OPEN_TUBE||''}</div>

            <div><b>Qty :</b> ${row.QTY||''}</div>
            <div><b>Color :</b> ${row.COLOR||''}</div>

        </div>

        <div class="qr-lot">
            <b>LOT NO :</b> ${row.LOT_NO||''}
        </div>

        <div class="qr-buttons">
            <button class="btn btn-success btn-sm qr-download-btn">Download</button>
            <button class="btn btn-primary btn-sm qr-print-btn">Print QR</button>
            <button class="btn btn-danger btn-sm qr-cancel-btn">Cancel</button>
        </div>

    </div>

</div>

</div>
`;

}
        function generateQrCode(row, target) {
            target.empty();
            var qrText = [
                row.SUB_TID,
                row.MCNO,
                row.BUYER,
                row.BOOKING,
                row.SONO,
                row.STYLE,
                row.FABRICS_TYPE,
                row.YARN_COUNT,
                row.YARN_TYPE,
                row.FINISH_GSM,
                row.FINISH_DIA,
                row.OPEN_TUBE,
                row.LOT_NO,
                row.QTY,
                row.COLOR
            ].filter(Boolean).join(' | ');

            new QRCode(target[0], {
                text: qrText || 'QR CODE',
                width: 160,
                height: 160,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        function downloadQrImage(container, filename) {
            var img = container.find('img')[0];
            var canvas = container.find('canvas')[0];
            var link = document.createElement('a');
            if (img) {
                link.href = img.src;
            } else if (canvas) {
                link.href = canvas.toDataURL('image/png');
            } else {
                alert('QR code is not ready yet.');
                return;
            }
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function printQrImage(container) {
            var img = container.find('img')[0];
            var canvas = container.find('canvas')[0];
            var dataUrl = img ? img.src : (canvas ? canvas.toDataURL('image/png') : null);
            if (!dataUrl) {
                alert('QR code is not ready yet.');
                return;
            }
            var printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Unable to open print window. Please allow popups.');
                return;
            }
            printWindow.document.write('<html><head><title>Print QR Code</title></head><body style="margin:0; display:flex; align-items:center; justify-content:center; height:100vh;"><img src="' + dataUrl + '" style="max-width:100%; height:auto;"> </body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }

        function closeQrDropdown() {
            $('.qr-dropdown-row').remove();
            $('.qr-action-btn').removeClass('active');
        }

        function openQrDropdown(button) {
            closeQrDropdown();
            var row = button.closest('tr');
            var data = button.data('row');
var dropdownHtml =
'<tr class="qr-dropdown-row">' +
'<td colspan="16" style="padding:10px;background:#f8f9fa;">' +
buildDropdownContent(data) +
'</td></tr>';
            row.after(dropdownHtml);
            var panel = row.next().find('.qr-code-target');
            generateQrCode(data, panel);
        }

        $(document).on('click', '.qr-action-btn', function() {
            var button = $(this);
            if (button.hasClass('active')) {
                closeQrDropdown();
                return;
            }
            openQrDropdown(button);
            button.addClass('active');
        });

        $(document).on('click', '.qr-cancel-btn', function() {
            closeQrDropdown();
        });

        $(document).on('click', '.qr-download-btn', function() {
            var container = $(this).closest('.qr-dropdown-panel');
            var filename = 'knitting-qr.png';
            downloadQrImage(container, filename);
        });

        $(document).on('click', '.qr-print-btn', function() {
            var container = $(this).closest('.qr-dropdown-panel');
            printQrImage(container);
        });

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
                    else $('#tableBody').html('<tr><td colspan="21" class="text-center small-muted">No data found</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="16" class="text-center text-danger">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).text('Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="21" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxKnittingProgram_Report.php',
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