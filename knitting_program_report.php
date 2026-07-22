<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Report</title>

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
                            <th>DATE</th>
                            <th>MAIN TID</th>
                            <th>SUB TID</th>
                            <th>BOOKING</th>
                            <th>SONO</th>
                            <th>STYLE</th>
                            <th>BUYER</th>
                            <th>SUPPLIER</th>
                            <th>KNIT M DESCRIPTION</th>
                            <th>MCNO</th>
                            <th>QTY</th>
                            <th>SHIFT</th>
                            <th>YARN TYPE</th>
                            <th>YARN COUNT</th>
                            <th>FABRICS TYPE</th>
                            <th>FINISH GSM</th>
                            <th>FINISH DIA</th>
                            <th>OPEN / TUBE</th>
                            <th>LOT NO</th>
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

    <script>
        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="21" class="text-center small-muted">No data found</td></tr>');
                return;
            }

            data.forEach(function(row) {
                var tr = $('<tr>');
                tr.append($('<td>').text(row.CREATED_DATE || ''));
                tr.append($('<td>').text(row.MAIN_TID || ''));
                tr.append($('<td>').text(row.SUB_TID || ''));
                tr.append($('<td>').text(row.BOOKING || ''));
                tr.append($('<td>').text(row.SONO || ''));
                tr.append($('<td>').text(row.STYLE || ''));
                tr.append($('<td>').text(row.BUYER || ''));
                tr.append($('<td>').text(row.SUPPLIER || ''));
                tr.append($('<td>').text(row.KNIT_M_DESCRIPTION || ''));
                tr.append($('<td>').text(row.MCNO || ''));
                tr.append($('<td>').text(row.QTY || ''));
                tr.append($('<td>').text(row.SHIFT || ''));
                tr.append($('<td>').text(row.YARN_TYPE || ''));
                tr.append($('<td>').text(row.YARN_COUNT || ''));
                tr.append($('<td>').text(row.FABRICS_TYPE || ''));
                tr.append($('<td>').text(row.FINISH_GSM || ''));
                tr.append($('<td>').text(row.FINISH_DIA || ''));
                tr.append($('<td>').text(row.OPEN_TUBE || ''));
                tr.append($('<td>').text(row.LOT_NO || ''));
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
                    else $('#tableBody').html('<tr><td colspan="21" class="text-center small-muted">No data found</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="21" class="text-center text-danger">Error searching</td></tr>');
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