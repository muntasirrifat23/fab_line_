<?php
// ------- In-file AJAX endpoint for knit_card_test data -------
$dataAction = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

if ($dataAction === 'load' || $dataAction === 'search') {
    require_once 'config.php';
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    $where = '';
    if ($dataAction === 'search') {
        $booking = isset($_GET['booking']) ? trim($_GET['booking']) : (isset($_POST['booking']) ? trim($_POST['booking']) : '');
        if ($booking !== '') {
            $b = mysqli_real_escape_string($db, $booking);
            $where = " WHERE PO_NUMBER LIKE '%$b%' OR SONO LIKE '%$b%' OR MCARD LIKE '%$b%' OR ROLL LIKE '%$b%' OR STYLE LIKE '%$b%'";
        }
    }

    $query = "SELECT * FROM knit_card_test" . $where . " ORDER BY KCTID DESC";
    $result = mysqli_query($db, $query);

    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Query error: ' . mysqli_error($db)]);
        exit();
    }

    $rows = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }

    echo json_encode(['success' => true, 'count' => count($rows), 'data' => $rows]);
    exit();
}
?>
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
            gap: 4px;
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
                            <th>ROLL</th>
                            <th>MCNO</th>
                            <th>QTY</th>
                            <th>PO NUMBER</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>FGSM</th>
                            <th>FDIA</th>
                            <th>O_T</th>
                            <th>FTYPE</th>
                            <th>YTYPE</th>
                            <th>SUPPLIER</th>
                            <th>YCOUNT</th>
                            <th>SL</th>
                            <th>MCDIA</th>
                            <th>GGSM</th>
                            <th>FEEDER_PLAN</th>
                            <th>LOT</th>
                            <th>SHIFT</th>
                            <th>KNIT_MATERIAL_CODE</th>
                            <th>KNIT_M_DESCRIPTION</th>
                            <th>CREATED_DATE</th>
                            <th>UNAME</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="26" class="text-center small-muted">Loading data...</td>
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
                tbody.append('<tr><td colspan="26" class="text-center small-muted">No data found</td></tr>');
                return;
            }

            data.forEach(function(row) {
                var tr = $('<tr>');
                var actionBtn = $('<button>')
                    .addClass('btn btn-sm btn-primary qr-action-btn')
                    .text('QR CODE')
                    .data('row', row);

                tr.append($('<td>').append(actionBtn));
                tr.append($('<td>').text(row.ROLL || ''));
                tr.append($('<td>').text(row.MCNO || ''));
                tr.append($('<td>').text(row.QTY || ''));
                tr.append($('<td>').text(row.PO_NUMBER || ''));
                tr.append($('<td>').text(row.SONO || ''));
                tr.append($('<td>').text(row.BUYER || ''));
                tr.append($('<td>').text(row.STYLE || ''));
                tr.append($('<td>').text(row.COLOR || ''));
                tr.append($('<td>').text(row.FGSM || ''));
                tr.append($('<td>').text(row.FDIA || ''));
                tr.append($('<td>').text(row.O_T || ''));
                tr.append($('<td>').text(row.FTYPE || ''));
                tr.append($('<td>').text(row.YTYPE || ''));
                tr.append($('<td>').text(row.SUPPLIER || ''));
                tr.append($('<td>').text(row.YCOUNT || ''));
                tr.append($('<td>').text(row.SL || ''));
                tr.append($('<td>').text(row.MCDIA || ''));
                tr.append($('<td>').text(row.GGSM || ''));
                tr.append($('<td>').text(row.FEEDER_PLAN || ''));
                tr.append($('<td>').text(row.LOT || ''));
                tr.append($('<td>').text(row.SHIFT || ''));
                tr.append($('<td>').text(row.KNIT_MATERIAL_CODE || ''));
                tr.append($('<td>').text(row.KNIT_M_DESCRIPTION || ''));
                tr.append($('<td>').text(row.CREATED_DATE || ''));
                tr.append($('<td>').text(row.UNAME || ''));

                tbody.append(tr);
            });
        }

        function buildDropdownContent(row) {
            return `
<div class="qr-dropdown-panel">

<div class="qr-content">

    <div class="qr-code-wrapper">
        <div class="qr-code-target"></div>
    </div>

    <div class="qr-info">

        <div class="qr-grid">

            <div><b>Program :</b> ${row.MCARD||''}</div>
            <div><b>KPTID :</b> ${row.KPTID||''}</div>
            <div><b>Shift :</b> ${row.SHIFT||''}</div>

            <div><b>Buyer :</b> ${row.BUYER||''}</div>
            <div><b>PO Number :</b> ${row.PO_NUMBER||''}</div>

            <div><b>SONO :</b> ${row.SONO||''}</div>
            <div><b>Style :</b> ${row.STYLE||''}</div>

            <div><b>Fabrics :</b> ${row.FTYPE||''}</div>
            <div><b>Yarn Count :</b> ${row.YCOUNT||''}</div>

            <div><b>Yarn Type :</b> ${row.YTYPE||''}</div>
            <div><b>Finish GSM :</b> ${row.FGSM||''}</div>

            <div><b>Finish Dia :</b> ${row.FDIA||''}</div>
            <div><b>Open/Tube :</b> ${row.O_T||''}</div>

            <div><b>Qty :</b> ${row.QTY||''}</div>
            <div><b>Color :</b> ${row.COLOR||''}</div>

            <div><b>SL/VDQ :</b> ${row.SL||''}</div>

            <div><b>LOT NO :</b> ${row.LOT||''}</div>

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

    var qrText =
        "Program: " + (row.MCARD || '') + "\n" +
        "KPTID: " + (row.KPTID || '');

    console.log(qrText);

    new QRCode(target[0], {
        text: qrText,
        width: 160,
        height: 160,
        colorDark: "#000000",
        colorLight: "#ffffff",
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

            var panel = container.closest('.qr-dropdown-panel')[0];
            if (!panel) {
                alert('Print panel is not available.');
                return;
            }

            var clone = panel.cloneNode(true);
            var cloneQrWrapper = clone.querySelector('.qr-code-wrapper');
            if (cloneQrWrapper) {
                cloneQrWrapper.innerHTML = '<img src="' + dataUrl + '" style="width:100%; height:auto; display:block;" />';
            }

            var buttons = clone.querySelector('.qr-buttons');
            if (buttons) {
                buttons.parentNode.removeChild(buttons);
            }

            var printStyles = '<style>' +
                '@page{size:A4 portrait;margin:15mm;}' +
                'body{margin:0;padding:10mm;font-family:Arial,Helvetica,sans-serif;color:#000;background:#fff;}' +
                '.qr-dropdown-panel{width:100%;padding:0;border:none;box-shadow:none;}' +
                '.qr-content{display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;}' +
                '.qr-code-wrapper{width:260px;flex:0 0 260px;}' +
                '.qr-code-wrapper img{width:100% !important;height:auto !important;border:1px solid #333;}' +
                '.qr-info{flex:1;min-width:260px;font-size:14px;line-height:1.5;}' +
                '.qr-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px;}' +
                '.qr-grid div{white-space:normal;}' +
                '.qr-lot{margin-top:16px;border-top:1px solid #666;padding-top:10px;font-size:14px;line-height:1.5;}' +
                '.qr-buttons{display:none !important;}' +
                '</style>';

            var printHtml = '<html><head><title>Print QR Code</title>' + printStyles + '</head><body>' + clone.outerHTML +
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
                '<td colspan="26" style="padding:10px;background:#f8f9fa;">' +
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
                    url: 'knitting_qr.php',
                    data: {
                        action: 'search',
                        booking: booking
                    },
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) renderTableRows(resp.data);
                    else $('#tableBody').html(
                    '<tr><td colspan="26" class="text-center small-muted">No data found</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html(
                        '<tr><td colspan="26" class="text-center text-danger">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).text('Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="26" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'knitting_qr.php?action=load',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) renderTableRows(resp.data);
                    else $('#tableBody').html(
                    '<tr><td colspan="26" class="text-center small-muted">No data returned</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html(
                        '<tr><td colspan="26" class="text-center text-danger">Error loading data</td></tr>');
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