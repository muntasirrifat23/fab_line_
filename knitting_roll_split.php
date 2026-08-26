<?php
include 'config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ================= AJAX: SEARCH ROLL IN STORE =================
if (isset($_GET['action']) && $_GET['action'] === 'get_roll') {
    header('Content-Type: application/json');

    $roll = isset($_GET['roll']) ? trim($_GET['roll']) : '';
    if ($roll === '') {
        echo json_encode(['success' => false, 'error' => 'Roll No is required']);
        exit();
    }

    $s = mysqli_real_escape_string($db, $roll);
    $q = "SELECT * FROM knitting_store WHERE ROLL = '$s' ORDER BY KSTID DESC LIMIT 1";
    $res = mysqli_query($db, $q);

    if ($res && mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Roll not found in Store: ' . $roll]);
    }
    exit();
}

// ================= AJAX: SPLIT ROLL IN STORE =================
if (isset($_POST['action']) && $_POST['action'] === 'split') {
    header('Content-Type: application/json');

    $roll = isset($_POST['roll']) ? trim($_POST['roll']) : '';
    $splitQty = isset($_POST['split_qty']) ? floatval($_POST['split_qty']) : 0;

    if ($roll === '') {
        echo json_encode(['success' => false, 'error' => 'Roll No is required']);
        exit();
    }
    if ($splitQty <= 0) {
        echo json_encode(['success' => false, 'error' => 'Split QTY must be greater than 0']);
        exit();
    }

    $s = mysqli_real_escape_string($db, $roll);
    $q = "SELECT * FROM knitting_store WHERE ROLL = '$s' ORDER BY KSTID DESC LIMIT 1";
    $res = mysqli_query($db, $q);

    if (!$res || mysqli_num_rows($res) == 0) {
        echo json_encode(['success' => false, 'error' => 'Roll not found in Store: ' . $roll]);
        exit();
    }

    $orig = mysqli_fetch_assoc($res);
    $qty = floatval($orig['QTY']);

    if ($splitQty >= $qty) {
        echo json_encode(['success' => false, 'error' => 'Split QTY (' . $splitQty . ') must be less than current Store QTY (' . $qty . ')']);
        exit();
    }

    // Base roll number (strip existing suffix like -A, -B)
    $base = $roll;
    $dashPos = strpos($roll, '-');
    if ($dashPos !== false) {
        $base = substr($roll, 0, $dashPos);
    }

    // Find used suffix letters for this base (e.g. 300000001-A, 300000001-B)
    $baseEsc = mysqli_real_escape_string($db, $base);
    $sufQ = mysqli_query($db, "SELECT ROLL FROM knitting_store WHERE ROLL LIKE '$baseEsc-%'");
    $used = [];
    if ($sufQ) {
        while ($r = mysqli_fetch_assoc($sufQ)) {
            $suffix = substr($r['ROLL'], strlen($base) + 1);
            if (strlen($suffix) === 1 && ctype_alpha($suffix)) {
                $used[strtoupper($suffix)] = true;
            }
        }
    }

    // Pick next available letters (A, B, C, ...)
    $letters = [];
    foreach (range('A', 'Z') as $ch) {
        if (!isset($used[$ch])) {
            $letters[] = $ch;
            if (count($letters) === 2) break;
        }
    }
    if (count($letters) < 2) {
        echo json_encode(['success' => false, 'error' => 'No suffix letters available for base ' . $base]);
        exit();
    }

    $newRollA = $base . '-' . $letters[0];
    $newRollB = $base . '-' . $letters[1];
    $remainB = round($qty - $splitQty, 2);

    // Copy fields from original store row
    $fBUDAT   = mysqli_real_escape_string($db, $orig['BUDAT']);
    $fRACKNO  = mysqli_real_escape_string($db, $orig['RACKNO']);
    $fRACKLOC = mysqli_real_escape_string($db, $orig['RACKLOCATION']);
    $fPO      = mysqli_real_escape_string($db, $orig['PO_NUMBER']);
    $fSONO    = mysqli_real_escape_string($db, $orig['SONO']);
    $fSHIFT   = mysqli_real_escape_string($db, $orig['SHIFT']);
    $fBUYER   = mysqli_real_escape_string($db, $orig['BUYER']);
    $fSTYLE   = mysqli_real_escape_string($db, $orig['STYLE']);
    $fCOLOR   = mysqli_real_escape_string($db, $orig['COLOR']);
    $fMCNO    = mysqli_real_escape_string($db, $orig['MCNO']);
    $fMCDIA   = mysqli_real_escape_string($db, $orig['MCDIA']);
    $fCUST    = mysqli_real_escape_string($db, $orig['CUSTOMER']);
    $fYTYPE   = mysqli_real_escape_string($db, $orig['YTYPE']);
    $fYCOUNT  = mysqli_real_escape_string($db, $orig['YCOUNT']);
    $fOT      = mysqli_real_escape_string($db, $orig['O_T']);
    $fSL      = mysqli_real_escape_string($db, $orig['SL']);
    $fFTYPE   = mysqli_real_escape_string($db, $orig['FTYPE']);
    $fFGSM    = mysqli_real_escape_string($db, $orig['FGSM']);
    $fFDIA    = mysqli_real_escape_string($db, $orig['FDIA']);
    $fGGSM    = mysqli_real_escape_string($db, $orig['GGSM']);
    $fFEEDER  = mysqli_real_escape_string($db, $orig['FEEDER_PLAN']);
    $fLOT     = mysqli_real_escape_string($db, $orig['LOT_NO']);
    $fTPOINT  = mysqli_real_escape_string($db, $orig['TPOINT']);
    $fMCODE   = mysqli_real_escape_string($db, $orig['MCODE']);
    $fMDES    = mysqli_real_escape_string($db, $orig['MDESCRIPTION']);
    $fUNAME   = mysqli_real_escape_string($db, $orig['UNAME']);
    $fUID     = mysqli_real_escape_string($db, $orig['UID']);

    mysqli_begin_transaction($db);

    // Original store row becomes base-A with split qty
    $u1 = "UPDATE knitting_store SET ROLL = '$newRollA', QTY = '$splitQty' WHERE KSTID = '" . intval($orig['KSTID']) . "'";
    // New store row becomes base-B with remaining qty
    $i2 = "INSERT INTO knitting_store
            (BUDAT, RACKNO, RACKLOCATION, ROLL, PO_NUMBER, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
             MCNO, MCDIA, CUSTOMER, YTYPE, YCOUNT, O_T, SL, FTYPE, FGSM, FDIA, GGSM,
             FEEDER_PLAN, LOT_NO, TPOINT, MCODE, MDESCRIPTION, UNAME, UID)
           VALUES
            ('$fBUDAT', '$fRACKNO', '$fRACKLOC', '$newRollB', '$fPO', '$remainB', '$fSONO', '$fSHIFT', '$fBUYER', '$fSTYLE', '$fCOLOR',
             '$fMCNO', '$fMCDIA', '$fCUST', '$fYTYPE', '$fYCOUNT', '$fOT', '$fSL', '$fFTYPE', '$fFGSM', '$fFDIA', '$fGGSM',
             '$fFEEDER', '$fLOT', '$fTPOINT', '$fMCODE', '$fMDES', '$fUNAME', '$fUID')";

    $ok1 = mysqli_query($db, $u1);
    $ok2 = $ok1 ? mysqli_query($db, $i2) : false;

    if ($ok1 && $ok2) {
        mysqli_commit($db);
        echo json_encode([
            'success' => true,
            'message' => 'Roll split successfully',
            'old_roll' => $roll,
            'roll_a' => $newRollA,
            'qty_a' => $splitQty,
            'roll_b' => $newRollB,
            'qty_b' => $remainB
        ]);
    } else {
        mysqli_rollback($db);
        echo json_encode(['success' => false, 'error' => 'Split failed - ' . mysqli_error($db)]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting Roll Split</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        h1.title {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 18px;
        }

        .small-muted {
            font-size: 12px;
            color: #6b7280;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 10px;
        }

        .info-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .info-item .lbl {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .info-item .val {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .info-item.hl {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .info-item.hl .val {
            color: #1d4ed8;
            font-family: Consolas, monospace;
            font-size: 17px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-dark" id="backBtn" style="background-color:#1f2937;color:#fff;padding:12px;border-radius:8px;">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>
                Back to Initial Page
            </button>
            <h1 class="title">Knitting Roll Split</h1>
            <div></div>
        </div>

        <div class="panel mb-3">
            <label class="form-label fw-semibold" style="font-size: larger; color: black;">
                Search Roll Number <span class="small-muted">(from Knitting Store)</span>
            </label>
            <div class="input-group input-group-sm d-flex align-items-center gap-2">
                <input type="text" id="rollInput" class="form-control" placeholder="Enter Roll No" autocomplete="off">
                <button class="btn px-4" id="searchBtn" style="margin-top:8px;background:#2563eb;border:1px solid #2563eb;color:#fff;border-radius:8px;">
                    <i class="fa-solid fa-magnifying-glass me-1" style="margin-right:6px;"></i> Search
                </button>
                <button class="btn px-4" id="clearBtn" style="margin-top:8px;margin-left:8px;background:#6b7280;border:1px solid #6b7280;color:#fff;border-radius:8px;">
                    <i class="fa-solid fa-rotate-left me-1" style="margin-right:6px;"></i> Clear
                </button>
            </div>
        </div>

        <div id="resultArea"></div>
    </div>

    <script src="jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        var currentRoll = null;

        $('#backBtn').on('click', function() {
            history.back();
        });

        function esc(v) {
            return String(v == null ? '' : v);
        }

        function infoItem(lbl, val, hl) {
            return '<div class="info-item' + (hl ? ' hl' : '') + '"><div class="lbl">' + esc(lbl) + '</div><div class="val">' + esc(val) + '</div></div>';
        }

        function renderRoll(d) {
            currentRoll = d;

            var html = '' +
                '<div class="panel">' +
                '<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">' +
                '<h5 class="mb-0 fw-bold" style="color:#1e293b;"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Store Roll Details</h5>' +
                '<span class="badge bg-success fs-6">Current QTY: ' + esc(d.QTY) + '</span>' +
                '</div>' +
                '<div class="info-grid mb-3">' +
                infoItem('Roll No', d.ROLL, true) +
                infoItem('Date', d.BUDAT) +
                infoItem('Rack No', d.RACKNO) +
                infoItem('Rack Location', d.RACKLOCATION) +
                infoItem('PO Number', d.PO_NUMBER) +
                infoItem('SO No', d.SONO) +
                infoItem('Buyer', d.BUYER) +
                infoItem('Style', d.STYLE) +
                infoItem('Color', d.COLOR) +
                infoItem('Machine No', d.MCNO) +
                infoItem('Machine Dia', d.MCDIA) +
                infoItem('Customer', d.CUSTOMER) +
                infoItem('Shift', d.SHIFT) +
                infoItem('Yarn Type', d.YTYPE) +
                infoItem('Yarn Count', d.YCOUNT) +
                infoItem('Fabrics Type', d.FTYPE) +
                infoItem('Finish GSM', d.FGSM) +
                infoItem('Finish Dia', d.FDIA) +
                infoItem('Open / Tube', d.O_T) +
                infoItem('Gray GSM', d.GGSM) +
                infoItem('Feeder Plan', d.FEEDER_PLAN) +
                infoItem('Lot No', d.LOT_NO) +
                infoItem('Entry By', d.UNAME) +
                '</div>' +
                '<hr>' +
                '<h5 class="fw-bold mb-3" style="color:#1e293b;"><i class="fa-solid fa-scissors me-2 text-danger"></i>Split This Roll</h5>' +
                '<div class="row g-3 align-items-end">' +
                '<div class="col-md-4">' +
                '<label class="form-label fw-semibold" style="color:black;">Split QTY</label>' +
                '<input type="number" id="splitQtyInput" class="form-control" min="0.01" step="0.01" placeholder="Enter split QTY">' +
                '<div class="small-muted mt-1">Example: Roll 300000001 (QTY 500), split 200 &rarr; <b>300000001-A</b> (200) + <b>300000001-B</b> (300)</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                '<button class="btn px-4" id="splitBtn" style="background:#dc2626;border:1px solid #dc2626;color:#fff;border-radius:8px;padding:10px 18px;">' +
                '<i class="fa-solid fa-scissors me-1"></i> Split Roll' +
                '</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('#resultArea').html(html);

            $('#splitBtn').on('click', doSplit);
            $('#splitQtyInput').on('keyup', function(e) {
                if (e.key === 'Enter') doSplit();
            });
        }

        function doSplit() {
            if (!currentRoll) return;

            var splitQty = parseFloat($('#splitQtyInput').val());
            var qty = parseFloat(currentRoll.QTY);

            if (isNaN(splitQty) || splitQty <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid QTY',
                    text: 'Please enter a valid Split QTY',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
            if (splitQty >= qty) {
                Swal.fire({
                    icon: 'error',
                    title: 'QTY not valid!',
                    text: 'Split QTY must be less than current Store QTY (' + qty + ')',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            Swal.fire({
                title: 'Split this store roll?',
                html: 'Roll <b>' + esc(currentRoll.ROLL) + '</b> (' + qty + ')<br>Split QTY: <b>' + splitQty + '</b>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Split!'
            }).then(function(r) {
                if (!r.isConfirmed) return;

                $('#splitBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Splitting...');

                $.ajax({
                        url: 'knitting_roll_split.php',
                        method: 'POST',
                        data: {
                            action: 'split',
                            roll: currentRoll.ROLL,
                            split_qty: splitQty
                        },
                        dataType: 'json'
                    })
                    .done(function(resp) {
                        if (resp && resp.success) {
                            var msgHtml =
                                '<div style="text-align:left;font-size:15px;">' +
                                '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;margin-bottom:8px;">' +
                                '<b>Original Roll:</b> ' + resp.old_roll + '</div>' +
                                '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:8px;">' +
                                '<b>Roll A:</b> <span style="color:#16a34a;font-weight:800;">' + resp.roll_a + '</span> &rarr; QTY: <b>' + resp.qty_a + '</b></div>' +
                                '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;">' +
                                '<b>Roll B:</b> <span style="color:#16a34a;font-weight:800;">' + resp.roll_b + '</span> &rarr; QTY: <b>' + resp.qty_b + '</b></div>' +
                                '<div class="small-muted mt-2">Old roll number will no longer exist - print new labels for both rolls</div>' +
                                '</div>';

                            var after = function() {
                                loadRoll(resp.roll_a);
                            };

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Roll Split Successful!',
                                    html: msgHtml,
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#2563eb'
                                }).then(after)['catch'](after);
                            } else {
                                alert('Roll Split Successful! ' + resp.roll_a + ' (' + resp.qty_a + ') & ' + resp.roll_b + ' (' + resp.qty_b + ')');
                                after();
                            }
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Split Failed',
                                    text: (resp && resp.error) ? resp.error : 'Unknown error',
                                    confirmButtonColor: '#2563eb'
                                });
                            } else {
                                alert('Split Failed: ' + ((resp && resp.error) || 'Unknown error'));
                            }
                            $('#splitBtn').prop('disabled', false).html('<i class="fa-solid fa-scissors me-1"></i> Split Roll');
                        }
                    })
                    .fail(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Could not connect to server',
                            confirmButtonColor: '#2563eb'
                        });
                        $('#splitBtn').prop('disabled', false).html('<i class="fa-solid fa-scissors me-1"></i> Split Roll');
                    });
            });
        }

        function loadRoll(roll) {
            $.ajax({
                    url: 'knitting_roll_split.php',
                    method: 'GET',
                    data: {
                        action: 'get_roll',
                        roll: roll
                    },
                    dataType: 'json'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderRoll(resp.data);
                    } else {
                        $('#resultArea').html('<div class="panel"><div class="text-center small-muted">' + esc((resp && resp.error) || 'Roll not found') + '</div></div>');
                    }
                })
                .fail(function() {
                    $('#resultArea').html('<div class="panel"><div class="text-center text-danger small-muted">Error loading roll</div></div>');
                });
        }

        function searchRoll() {
            var roll = $('#rollInput').val().trim();
            if (!roll) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Roll No required',
                    text: 'Please enter a Roll No',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
            $('#resultArea').html('<div class="panel"><div class="text-center small-muted">Loading...</div></div>');
            loadRoll(roll);
        }

        $(function() {
            $('#searchBtn').on('click', searchRoll);
            $('#clearBtn').on('click', function() {
                $('#rollInput').val('');
                currentRoll = null;
                $('#resultArea').html('');
            });
            $('#rollInput').on('keyup', function(e) {
                if (e.key === 'Enter') searchRoll();
            });
        });
    </script>

</body>

</html>
