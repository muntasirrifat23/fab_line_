<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting | Input Details</title>

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
            <h1 class="title">Input Details</h1>
            <div></div>
        </div>

        <div class="panel mb-3">
            <div class="row g-3 align-items-end controls">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size: larger; color: black;">
                        Search SONO or Document NO
                    </label>

                    <div class="input-group input-group-sm d-flex align-items-center gap-2">
                        <input type="text" id="bookingInput" class="form-control" placeholder="SONO or Document NO">
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
                            <th>BUDAT</th>
                            <th>SUPPLIER</th>
                            <th>BUYER</th>
                            <th>BOOKING</th>
                            <th>MC DIA</th>
                            <th>FINISH DIA</th>
                            <th>OPEN TUBE</th>
                            <th>STYLE</th>
                            <th>YARN TYPE</th>
                            <th>YARN COUNT</th>
                            <th>FABRICS TYPE</th>
                            <th>FINISH GSM</th>
                            <th>COLOR</th>
                            <th>SONO</th>
                            <th>SO ITEM</th>
                            <th>KNIT MATERIAL CODE</th>
                            <th>KNIT MATERIAL DESCRIPTION</th>
                            <th>ORDER TYPE</th>
                            <th>KNITTING TARGET QTY</th>
                            <th>SL VDQ</th>
                            <th>LOT NO</th>
                            <th>FIRST SHIPMENT DATE</th>
                            <th>LAST SHIPMENT DATE</th>
                            <th>KNIT TNA START</th>
                            <th>KNIT TNA END</th>
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

    <script>
      function renderTableRows(data) {

    var tbody = $('#tableBody');
    tbody.empty();

    if (!data || data.length === 0) {
        tbody.append('<tr><td colspan="26" class="text-center">No Data Found</td></tr>');
        return;
    }

    $.each(data, function(index, row) {

        tbody.append(`
        <tr>
            <td>${row.BUDAT ?? ''}</td>
            <td>${row.SUPPLIER ?? ''}</td>
            <td>${row.BUYER ?? ''}</td>
            <td>${row.BOOKING ?? ''}</td>
            <td>${row.MC_DIA ?? ''}</td>
            <td>${row.FINISH_DIA ?? ''}</td>
            <td>${row.OPEN_TUBE ?? ''}</td>
            <td>${row.STYLE ?? ''}</td>
            <td>${row.YARN_TYPE ?? ''}</td>
            <td>${row.YARN_COUNT ?? ''}</td>
            <td>${row.FABRICS_TYPE ?? ''}</td>
            <td>${row.FINISH_GSM ?? ''}</td>
            <td>${row.COLOR ?? ''}</td>
            <td>${row.SONO ?? ''}</td>
            <td>${row.SO_ITEM ?? ''}</td>
            <td>${row.KNIT_MATERIAL_CODE ?? ''}</td>
            <td>${row.KNIT_M_DESCRIPTION ?? ''}</td>
            <td>${row.ORDER_TYPE ?? ''}</td>
            <td>${row.KNITTING_TARGET_QTY ?? ''}</td>
            <td>${row.SL_VDQ ?? ''}</td>
            <td>${row.LOT_NO ?? ''}</td>
            <td>${row.FIRST_SHIPMENT_DATE ?? ''}</td>
            <td>${row.LAST_SHIPMENT_DATE ?? ''}</td>
            <td>${row.KNIT_TNA_START ?? ''}</td>
            <td>${row.KNIT_TNA_END ?? ''}</td>
        </tr>
        `);

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
                    url: 'ajaxKnittingInput.php',
                    data: {
                        booking: booking
                    },
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) renderTableRows(resp.data);
                    else $('#tableBody').html('<tr><td colspan="26" class="text-center small-muted">No data found</td></tr>');
                })
                .fail(function() {
                    $('#tableBody').html('<tr><td colspan="26" class="text-center text-danger">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).text('Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="25" class="text-center small-muted">Loading data...</td></tr>');
            $.ajax({
                    url: 'ajaxKnittingInput.php',
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