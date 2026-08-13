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
                            <th>DATE</th>
                            <th>ROLL NO</th>
                            <th>PO NUMBER</th>
                            <th>OQTY</th>
                            <th>REJ QTY</th>
                            <th>UQTY</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MCNO</th>
                            <th>MC DIA</th>
                            <th>SUPPLIER</th>
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
                            <td colspan="26" class="text-center small-muted">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="jquery.min.js"></script>

    <script>
        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="26" class="text-center small-muted">No Roll Or Booking No found</td></tr>');
                return;
            }

            data.forEach(function(row) {
                var tr = $('<tr>');
                tr.append($('<td>').text(row.BUDAT || ''));
                tr.append($('<td>').text(row.ROLL || ''));
                tr.append($('<td>').text(row.PO_NUMBER || ''));
                tr.append($('<td>').text(row.OQTY || ''));
                tr.append($('<td>').text(row.RQTY || ''));
                tr.append($('<td>').text(row.UQTY || ''));
                tr.append($('<td>').text(row.SONO || ''));
                tr.append($('<td>').text(row.BUYER || ''));
                tr.append($('<td>').text(row.STYLE || ''));
                tr.append($('<td>').text(row.COLOR || ''));
                tr.append($('<td>').text(row.MCNO || ''));
                tr.append($('<td>').text(row.MC_DIA || ''));
                tr.append($('<td>').text(row.SUPPLIER || ''));
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
                            '<tr><td colspan="26" class="text-center small-muted">No data found</td></tr>'
                        );
                    }
                })
                .fail(function() {
                    $('#tableBody').html(
                        '<tr><td colspan="26" class="text-center text-danger">Error searching</td></tr>'
                    );
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html(
                        '<i class="fa-solid fa-magnifying-glass me-1" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i> Search'
                    );
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="26" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxKnittingProduction_Report.php',
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) renderTableRows(resp.data);
                    else $('#tableBody').html('<tr><td colspan="26" class="text-center small-muted">No data returned</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="26" class="text-center text-danger">Error loading data</td></tr>');
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