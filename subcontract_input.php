<?php
include 'config.php';

date_default_timezone_set('Asia/Dhaka');

$message = '';
$messageType = '';
$keepForm = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subcontract'])) {
    $budat = trim($_POST['budat'] ?? '');
    $po    = trim($_POST['po_number'] ?? '');
    $sono  = trim($_POST['sono'] ?? '');
    $buyer = trim($_POST['buyer'] ?? '');
    $style = trim($_POST['style'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $qty   = trim($_POST['qty'] ?? '');
    $gsm   = trim($_POST['finish_gsm'] ?? '');
    $dia   = trim($_POST['finish_dia'] ?? '');
    $ot    = trim($_POST['open_tube'] ?? '');
    $ft    = trim($_POST['fabrics_type'] ?? '');
    $yt    = trim($_POST['yarn_type'] ?? '');
    $kmc   = trim($_POST['knit_material_code'] ?? '');
    $kmd   = trim($_POST['knit_m_description'] ?? '');

    if ($budat === '') $budat = date('Y-m-d');

    $required = [
        $po, $sono, $buyer, $style, $color, $qty,
        $gsm, $dia, $ot, $ft, $yt, $kmc, $kmd
    ];
    $missing = false;
    foreach ($required as $val) {
        if ($val === '') {
            $missing = true;
            break;
        }
    }

    if ($missing) {
        $message = 'All fields are required.';
        $messageType = 'error';
    } else {
        $cbudat = date('Y-m-d H:i:s');
        $sql = "INSERT INTO knitting_input
                (BUDAT, PO_NUMBER, SONO, BUYER, STYLE, COLOR, QTY,
                 FINISH_GSM, FINISH_DIA, OPEN_TUBE, FABRICS_TYPE, YARN_TYPE,
                 KNIT_MATERIAL_CODE, KNIT_M_DESCRIPTION, CBUDAT)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = mysqli_prepare($db, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssssssssss",
                $budat,
                $po,
                $sono,
                $buyer,
                $style,
                $color,
                $qty,
                $gsm,
                $dia,
                $ot,
                $ft,
                $yt,
                $kmc,
                $kmd,
                $cbudat
            );
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Subcontract input saved successfully for PO ' . $po . '.';
                $messageType = 'success';
                $keepForm = false;
            } else {
                $message = 'Save failed: ' . mysqli_stmt_error($stmt);
                $messageType = 'error';
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = 'Save failed: ' . mysqli_error($db);
            $messageType = 'error';
        }
    }
} else {
    $budat = date('Y-m-d');
    $po = $sono = $buyer = $style = $color = $qty = '';
    $gsm = $dia = $ot = $ft = $yt = $kmc = $kmd = '';
}

$v = function ($val) use ($keepForm) {
    return $keepForm ? htmlspecialchars($val ?? '', ENT_QUOTES) : '';
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subcontract Input</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f1f5f9;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
            padding: 1.5rem;
            min-height: 100vh;
        }

        .card-unified {
            background: #ffffff;
            border-radius: 24px;
            padding: 1.5rem 1.8rem;
            box-shadow: 0 8px 24px rgba(0, 20, 40, 0.06);
            border: 1px solid #eef2f6;
            margin-bottom: 1.8rem;
        }

        .card-unified .card-title {
            font-weight: 700;
            font-size: 1rem;
            color: #0b2a4a;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-unified .card-title i {
            color: #2563eb;
            font-size: 1.2rem;
        }

        .program-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.8rem;
        }

        .program-header h1 {
            font-weight: 700;
            font-size: 2rem;
            color: #0b2a4a;
            margin: 0;
        }

        .btn-back {
            background: #1e293b;
            color: #fff;
            border: none;
            padding: 0.6rem 1.6rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #0f172a;
            transform: translateY(-2px);
            color: #fff;
        }

        .info-grid-6 {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1.2rem 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .info-item label .req {
            color: #dc2626;
        }

        .info-item input {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fbff;
            font-weight: 600;
            font-size: 0.95rem;
            color: #0b2a4a;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            width: 100%;
        }

        .full-width-item {
            grid-column: 1 / -1;
        }

        .info-item input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
            background: #ffffff;
        }

        @media (max-width: 992px) {
            .info-grid-6 {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .info-grid-6 {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem 1rem;
            }

            .program-header h1 {
                font-size: 1.5rem;
            }

            .card-unified {
                padding: 1.2rem;
            }
        }

        @media (min-width: 993px) {
            .full-width-item {
                grid-column: span 3;
            }
        }

        .alert {
            border-radius: 60px;
            padding: 0.7rem 1.5rem;
            font-weight: 500;
            border: none;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 6px solid #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 6px solid #16a34a;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem;
            margin-top: 1.6rem;
        }

        .btn-submit {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 0.8rem 2.8rem;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1rem;
            transition: 0.2s;
        }

        .btn-submit:hover:not(:disabled) {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-clear {
            background: #e9edf2;
            color: #1e293b;
            border: none;
            padding: 0.8rem 2.8rem;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1rem;
            transition: 0.2s;
        }

        .btn-clear:hover {
            background: #d5dce4;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div class="container-fluid px-0">

        <div class="program-header">
            <button class="btn-back" id="backBtn"><i class="fa-solid fa-arrow-left me-2"></i>Back</button>
            <h1><i class="fa-solid fa-clipboard-list me-2" style="color:#2563eb;"></i>Subcontract Input</h1>
            <div></div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="card-unified" style="padding-bottom: 1rem;">
                <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-danger'; ?> mb-0">
                    <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card-unified">
            <div class="card-title"><i class="fa-regular fa-file-lines"></i> Enter Input Details</div>

            <form method="POST" action="subcontract_input.php" id="subcontractForm">
                <div class="info-grid-6">
                    <div class="info-item">
                        <label>Input Date</label>
                        <input type="date" name="budat" id="budat" value="<?php echo htmlspecialchars($budat); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>PO Number</label>
                        <input type="text" name="po_number" placeholder="Enter PO Number" value="<?php echo $v($po); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>SONO</label>
                        <input type="text" name="sono" placeholder="Enter SONO" value="<?php echo $v($sono); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>Buyer</label>
                        <input type="text" name="buyer" placeholder="Enter Buyer" value="<?php echo $v($buyer); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>Style</label>
                        <input type="text" name="style" placeholder="Enter Style" value="<?php echo $v($style); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>Color</label>
                        <input type="text" name="color" placeholder="Enter Color" value="<?php echo $v($color); ?>" required>
                    </div>

                    <div class="info-item">
                        <label>QTY</label>
                        <input type="number" name="qty" placeholder="Enter QTY" step="0.01" value="<?php echo $v($qty); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>Finish GSM</label>
                        <input type="text" name="finish_gsm" placeholder="Enter Finish GSM" value="<?php echo $v($gsm); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>Finish Dia</label>
                        <input type="text" name="finish_dia" placeholder="Enter Finish Dia" value="<?php echo $v($dia); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>Open / Tube</label>
                        <input type="text" name="open_tube" placeholder="Enter Open / Tube" value="<?php echo $v($ot); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>Fabrics Type</label>
                        <input type="text" name="fabrics_type" placeholder="Enter Fabrics Type" value="<?php echo $v($ft); ?>" required>
                    </div>
                    <div class="info-item">
                        <label>Yarn Type</label>
                        <input type="text" name="yarn_type" placeholder="Enter Yarn Type" value="<?php echo $v($yt); ?>" required>
                    </div>

                    <div class="info-item full-width-item">
                        <label>Knit Material Code</label>
                        <input type="text"
                            name="knit_material_code"
                            placeholder="Enter Knit Material Code"
                            value="<?php echo $v($kmc); ?>"
                            required>
                    </div>

                    <div class="info-item full-width-item">
                        <label>Knit M Description</label>
                        <input type="text"
                            name="knit_m_description"
                            placeholder="Enter Knit M Description"
                            value="<?php echo $v($kmd); ?>"
                            required>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn-submit" name="save_subcontract" id="saveBtn">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Save Subcontract Input
                    </button>
                    <button type="button" class="btn-clear" id="resetBtn">
                        <i class="fa-solid fa-rotate-left me-1"></i>Clear
                    </button>
                </div>
            </form>
        </div>

    </div>

    <script src="jquery.min.js"></script>

    <script>
        $(function() {
            function checkFormValidity() {
                var allFilled = true;
                $('#subcontractForm .info-item input').each(function() {
                    if ($(this).val().trim() === '') {
                        allFilled = false;
                        return false;
                    }
                });
                $('#saveBtn').prop('disabled', !allFilled);
            }

            $('#backBtn').on('click', function() {
                window.location.href = 'initialPage.php';
            });

            $('#subcontractForm').on('input change', checkFormValidity);

            $('#resetBtn').on('click', function() {
                $('#subcontractForm')[0].reset();
                $('#budat').val(new Date().toISOString().split('T')[0]);
                checkFormValidity();
            });

            $('#saveBtn').prop('disabled', true);
            checkFormValidity();
        });
    </script>

</body>

</html>