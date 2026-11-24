
<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function knittingRackReportEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function knittingRackReportValue($value, $fallback = 'Not available'): string
{
    return ($value !== null && trim((string)$value) !== '')
        ? (string)$value
        : $fallback;
}

/* =====================================================
   FILTERS
===================================================== */

$search = trim((string)($_GET['q'] ?? ''));
$dateFilter = trim((string)($_GET['date'] ?? ''));
$today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Dhaka')))
    ->format('Y-m-d');

$logs = [];
$reportError = '';

/* Validate date */

if ($dateFilter !== '') {

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {

        $reportError = 'Invalid date filter.';

    } else {

        [$year, $month, $day] = array_map(
            'intval',
            explode('-', $dateFilter)
        );

        if (!checkdate($month, $day, $year)) {
            $reportError = 'Invalid date filter.';
        } elseif ($dateFilter > $today) {
            $reportError = 'Future dates are not allowed.';
        }
    }
}

/* =====================================================
   SQL QUERY
===================================================== */

$sql = "
    SELECT
        l.KRTID AS TRANSFER_ID,
        s.ROLL AS TRANSFER_ROLL,
        l.FRACK AS TRANSFER_FROM_RACK,
        l.TORACK AS TRANSFER_TO_RACK,
        l.BUDAT AS TRANSFER_BUDAT,
        COALESCE(
            l.TBUDATE,
            CAST(l.BUDAT AS DATETIME),
            CAST(s.BUDAT AS DATETIME)
        ) AS TRANSFER_DATETIME,
        s.*

    FROM knitting_rack_transfer l

    INNER JOIN knitting_store s
        ON s.KSTID = l.KSTID
";

$params = [];
$types = '';
$conditions = [];

/* =====================================================
   SEARCH FILTER
===================================================== */

if ($search !== '') {

    $conditions[] = "
        (
            CAST(l.KRTID AS CHAR) LIKE ?
            OR CAST(l.KSTID AS CHAR) LIKE ?
            OR s.ROLL LIKE ?
            OR l.FRACK LIKE ?
            OR l.TORACK LIKE ?
            OR CAST(l.BUDAT AS CHAR) LIKE ?
            OR CAST(l.TBUDATE AS CHAR) LIKE ?
            OR s.PO_NUMBER LIKE ?
            OR s.BUYER LIKE ?
            OR s.STYLE LIKE ?
            OR s.COLOR LIKE ?
            OR s.BUDAT LIKE ?
            OR s.FTYPE LIKE ?
        )
    ";

    $term = '%' . $search . '%';

    $params = array_fill(0, 13, $term);

    $types = str_repeat('s', 13);
}

/* =====================================================
   DATE FILTER
===================================================== */

if ($dateFilter !== '') {

    $conditions[] = "
        DATE(
            COALESCE(
                l.TBUDATE,
                l.BUDAT,
                s.BUDAT
            )
        ) = ?
    ";

    $params[] = $dateFilter;
    $types .= 's';
}

/* =====================================================
   FINAL QUERY
===================================================== */

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY l.KRTID DESC';

/* =====================================================
   DATABASE EXECUTION
===================================================== */

if (!$db) {

    $reportError = 'Database connection failed.';

} else {

    try {

        ensureKnittingRackTransferTable($db);

    } catch (Throwable $e) {

        error_log(
            'Store Rack Transfer schema error: ' .
            $e->getMessage()
        );

        $reportError = 'Unable to prepare Store Rack Transfer records.';
    }

    if ($reportError === '') {

        $stmt = $db->prepare($sql);

        if (!$stmt) {

            error_log(
                'Store Rack Transfer prepare error: ' . $db->error
            );

            $reportError = 'Unable to load Store Rack Transfer records.';

        } else {

            /* Bind parameters safely */

            if (!empty($params)) {

                $bindParams = [];
                $bindParams[] = $types;

                foreach ($params as $key => &$value) {
                    $bindParams[] = &$value;
                }

                if (!call_user_func_array(
                    [$stmt, 'bind_param'],
                    $bindParams
                )) {

                    $reportError = 'Unable to bind search parameters.';
                }

                unset($value);
            }

            if ($reportError === '' && $stmt->execute()) {

                $result = $stmt->get_result();

                if ($result) {

                    while ($row = $result->fetch_assoc()) {
                        $logs[] = $row;
                    }

                } else {

                    $reportError = 'Unable to retrieve report data.';
                }

            } elseif ($reportError === '') {

                error_log(
                    'Store Rack Transfer execute error: ' . $stmt->error
                );

                $reportError = 'Unable to load Store Rack Transfer records.';
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
Store Rack Transfer
    <title> Report</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="css/bootstrap.min.css">

    <link rel="stylesheet" href="css/knitting-report-theme.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>

        body {
            padding: clamp(8px, 2vw, 16px);
            background: #f1f5f9;
            color: #1e293b;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            font-size: 1.05rem;
        }

        .report-content {
            width: fit-content;
            max-width: 100%;
            margin: 0 auto;
        }

        .back-link,
        .toolbar-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 8px 13px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .back-link {
            background: #111827;
        }

        .toolbar-button {
            background: #2563eb;
        }

        .toolbar-button:hover {
            color: #fff;
            background: #1d4ed8;
        }

        .report-toolbar {
            display: flex;
            align-items: stretch;
            width: 100%;
            gap: 10px;
            margin-bottom: 14px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
        }

        .report-toolbar input {
            flex: 1;
            min-width: 180px;
            min-height: 40px;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1.05rem;
            outline: none;
        }

        .report-toolbar input:focus {
            border-color: #0f9f8f;
            box-shadow: 0 0 0 3px rgba(15,159,143,.14);
        }

        .report-toolbar input[type="date"] {
            flex: 0 0 175px;
            min-width: 175px;
        }

        .search-button {
            background: #0f766e;
        }

        .search-button:hover {
            background: #115e59;
        }

        .clear-button {
            background: #64748b;
        }

        .clear-button:hover {
            background: #475569;
        }

        .table-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15,23,42,.04);
        }

        .table-scroll {
            width: 100%;
            max-width: 100%;
            max-height: calc(100vh - 190px);
            overflow: auto;
        }

        .transfer-report-table {
            width: 100%;
            table-layout: auto;
            border: 1px solid #94a3b8;
            border-collapse: separate;
            border-spacing: 0;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            font-size: 1.05rem;
        }

        .transfer-report-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            padding: 11px 9px;
            border-right: 1px solid rgba(255,255,255,.2);
            border-bottom: 3px solid #0f766e;
            background: #334155;
            color: #fff;
            font-size: .95rem;
            font-weight: 800;
            text-align: left;
            white-space: nowrap;
        }

        .transfer-report-table tbody td {
            padding: 9px 10px;
            border-right: 1px solid #cbd5e1;
            border-bottom: 1px solid #e5e7eb;
            color: #334155;
            white-space: nowrap;
            vertical-align: middle;
        }

        .transfer-report-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .transfer-report-table tbody tr:hover {
            background: #eff6ff;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 12px;
            border: 0;
            border-radius: 6px;
            background: #16a34a;
            color: #fff;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
        }

        .action-button:hover {
            background: #15803d;
        }

        .empty-row td,
        .error-row td {
            padding: 28px;
            color: #64748b;
            text-align: center;
            font-weight: 700;
        }

        .error-row td {
            color: #b91c1c;
        }

        @media (max-width: 680px) {

            .back-link {
                padding: 7px 9px;
                font-size: .78rem;
            }

            .report-toolbar {
                flex-wrap: wrap;
                gap: 7px;
                padding: 9px;
            }

            .report-toolbar input {
                flex-basis: 100%;
            }

            .toolbar-button {
                flex: 1;
                padding: 7px 9px;
                font-size: .8rem;
            }
        }

        @media print {

            @page {
                size: landscape;
                margin: 10mm;
            }

            body {
                padding: 0;
                background: #fff;
            }

            .report-content {
                width: 100%;
                max-width: none;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .knitting-report-header {
                min-height: auto !important;
                margin-bottom: 8px !important;
                border: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
            }

            .knitting-report-header h1 {
                color: #111827 !important;
                text-shadow: none !important;
            }

            .table-card {
                overflow: visible;
                border: 0;
                box-shadow: none;
            }

            .table-scroll {
                max-height: none;
                overflow: visible;
            }

            .transfer-report-table {
                width: 100%;
                min-width: 0;
                border-collapse: collapse;
                font-size: 8pt;
            }

            .transfer-report-table thead th {
                position: static;
                padding: 6px;
                border: 1px solid #64748b;
                background: #e2e8f0 !important;
                color: #111827 !important;
                print-color-adjust: exact;
            }

            .transfer-report-table tbody td {
                padding: 5px;
                border: 1px solid #94a3b8;
                color: #111827;
            }

            .transfer-report-table tbody tr {
                break-inside: avoid;
            }
        }

    </style>

</head>

<body>

    <main class="report-content">

    <!-- PAGE HEADER -->

    <div class="head-row knitting-report-header knitting-report-header--tabs">

        <a href="report.php" id="backBtn" class="back-link no-print">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Report
        </a>

        <div class="title-tabs">
            <h1>Store Rack Transfer Report</h1>
        </div>

        <div aria-hidden="true"></div>

    </div>

    <!-- SEARCH AND FILTER -->

    <form method="GET" class="report-toolbar no-print">

        <input
            type="date"
            name="date"
            value="<?php echo knittingRackReportEscape($dateFilter); ?>"
            max="<?php echo knittingRackReportEscape($today); ?>"
            aria-label="Filter by date"
        >

        <input
            type="search"
            name="q"
            value="<?php echo knittingRackReportEscape($search); ?>"
            placeholder="Search roll, rack, PO, color, buyer, style, or transfer user"
        >

        <button type="submit" class="toolbar-button search-button">
            <i class="fa-solid fa-magnifying-glass"></i>
            Search
        </button>

        <a href="knitting_rack_transfer_report.php" class="toolbar-button clear-button">
            <i class="fa-solid fa-rotate-left"></i>
            Clear
        </a>

    </form>

    <!-- REPORT TABLE -->

    <div class="table-card">

        <div class="table-scroll">

            <table class="transfer-report-table">

                <thead>

                    <tr>

                        <th class="no-print">Action</th>
                        <th>Transfer Date</th>
                        <th>Roll No</th>
                        <th>PO No</th>
                        <th>Color</th>
                        <th class="text-end">QTY (KG)</th>
                        <th>Fabrics Type</th>
                        <th>From Rack</th>
                        <th>To Rack</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($reportError !== ''): ?>

                        <tr class="error-row">
                            <td colspan="9">
                                <?php echo knittingRackReportEscape($reportError); ?>
                            </td>
                        </tr>

                    <?php elseif (!$logs): ?>

                        <tr class="empty-row">
                            <td colspan="9">
                                No Store Rack Transfer records found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($logs as $row): ?>

                            <?php

                            /* Prepare row values */

                            $row['ROLL'] = $row['TRANSFER_ROLL'] ?? '';

                            $row['FROM_RACK'] =
                                $row['TRANSFER_FROM_RACK'] ?? '';

                            $row['TO_RACK'] =
                                $row['TRANSFER_TO_RACK'] ?? '';

                            $transferDateValue = $row['TRANSFER_DATETIME'] ?? null;
                            $transferDateTimestamp = $transferDateValue
                                ? strtotime((string)$transferDateValue)
                                : false;
                            $row['BUDAT'] = $transferDateTimestamp !== false
                                ? date('d-m-Y H:i:s', $transferDateTimestamp)
                                : knittingRackReportValue(
                                    $row['TRANSFER_BUDAT'] ?? null,
                                    $row['BUDAT'] ?? ''
                                );

                            /* JSON for print */

                            $printJson = json_encode(
                                $row,
                                JSON_HEX_TAG |
                                JSON_HEX_APOS |
                                JSON_HEX_AMP |
                                JSON_HEX_QUOT |
                                JSON_INVALID_UTF8_SUBSTITUTE
                            );

                            ?>

                            <tr>

                                <td class="no-print">

                                    <button
                                        type="button"
                                        class="action-button"
                                        data-print-row="<?php echo knittingRackReportEscape($printJson ?: '{}'); ?>"
                                        onclick="printTransferRow(this)"
                                    >

                                        <i class="fa-solid fa-print"></i>
                                        Print

                                    </button>

                                </td>

                                <td>
                                    <?php echo knittingRackReportEscape($row['BUDAT']); ?>
                                </td>

                                <td>
                                    <?php echo knittingRackReportEscape($row['ROLL']); ?>
                                </td>

                                <td>
                                    <?php echo knittingRackReportEscape($row['PO_NUMBER']); ?>
                                </td>

                                <td>
                                    <?php echo knittingRackReportEscape($row['COLOR']); ?>
                                </td>

                                <td class="text-end">
                                    <?php echo knittingRackReportEscape($row['QTY']); ?>
                                </td>

                                <td>
                                    <?php echo knittingRackReportEscape($row['FTYPE']); ?>
                                </td>

                                <td>
                                    <?php echo knittingRackReportEscape($row['FROM_RACK']); ?>
                                </td>

                                <td>
                                    <?php echo knittingRackReportEscape($row['TO_RACK']); ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    </main>

    <!-- QR CODE LIBRARY -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>

        /* =====================================================
           PRINT COLUMN CONFIGURATION
        ===================================================== */

        var TRANSFER_PRINT_COLS = [

            { key: 'BUDAT', label: 'Transfer Date' },
            { key: 'ROLL', label: 'Roll No' },
            { key: 'FROM_RACK', label: 'From Rack' },
            { key: 'TO_RACK', label: 'To Rack' },
            { key: 'QTY', label: 'QTY' },
            { key: 'QC_STATUS', label: 'QC Pass' },
            { key: 'PO_NUMBER', label: 'PO Number' },
            { key: 'SONO', label: 'SONO' },
            { key: 'SHIFT', label: 'Shift' },
            { key: 'BUYER', label: 'Buyer' },
            { key: 'STYLE', label: 'Style' },
            { key: 'COLOR', label: 'Color' },
            { key: 'MCNO', label: 'Machine No' },
            { key: 'MCDIA', label: 'MC Dia' },
            { key: 'CUSTOMER', label: 'Customer' },
            { key: 'YTYPE', label: 'Yarn Type' },
            { key: 'YCOUNT', label: 'Yarn Count' },
            { key: 'YBRAND', label: 'Yarn Brand' },
            { key: 'O_T', label: 'Open / Tube' },
            { key: 'SL', label: 'SL/VQ' },
            { key: 'FTYPE', label: 'Fabrics Type' },
            { key: 'FGSM', label: 'Finish GSM' },
            { key: 'FDIA', label: 'Finish Dia' },
            { key: 'FEEDER_PLAN', label: 'Feeder Plan' },
            { key: 'LOT_NO', label: 'Lot No' },
            { key: 'TPOINT', label: 'T Point' },
            { key: 'CATEGORY', label: 'Category' },
            { key: 'UNAME', label: 'Operator' }

        ];

        /* =====================================================
           ESCAPE HTML
        ===================================================== */

        function escapePrintText(value) {

            return String(
                value === null || value === undefined ? '' : value
            )
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        }

        /* =====================================================
           BUILD PRINT DOCUMENT
        ===================================================== */

        function buildPrintDocument(row, qrDataUrl) {

            var rowsHTML = TRANSFER_PRINT_COLS.map(function(column) {

                var value = row[column.key];

                if (
                    column.key === 'BUDAT' &&
                    !value
                ) {
                    value = row.TRANSFER_DATETIME || row.TRANSFER_BUDAT || '';
                }

                if (
                    value === null ||
                    value === undefined ||
                    value === ''
                ) {
                    value = 'Not available';
                }

                return `
                    <div class="pdf-item">

                        <span class="pdf-label">
                            ${escapePrintText(column.label)} :
                        </span>

                        <span class="pdf-value">
                            ${escapePrintText(value)}
                        </span>

                    </div>
                `;

            }).join('');

            var roll = String(row.ROLL || '').trim();

            var qrHTML = qrDataUrl
                ? `<img src="${escapePrintText(qrDataUrl)}" alt="Roll QR Code">`
                : `<div class="qr-fallback">${escapePrintText(roll || 'QR unavailable')}</div>`;

            return `
                <!DOCTYPE html>

                <html lang="en">

                <head>

                    <meta charset="UTF-8">

                    <meta name="viewport" content="width=device-width, initial-scale=1.0">

                    <title>Knitting Store Report</title>

                    <style>

                        @page {
                            size: A4 portrait;
                            margin: 8mm;
                        }

                        * {
                            box-sizing: border-box;
                        }

                        body {
                            margin: 0;
                            padding: 0;
                            color: #1f2937;
                            font-family: Arial, Helvetica, sans-serif;
                        }

                        .print-card {
                            position: relative;
                            min-height: 275mm;
                            padding: 8mm 8mm 28mm;
                            background: #fff;
                        }

                        .print-header {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 16px;
                            margin-bottom: 16px;
                            padding-bottom: 12px;
                            border-bottom: 3px solid #2563eb;
                        }

                        .print-title {
                            flex: 1;
                            text-align: center;
                            color: #1e3a8a;
                            font-size: 24px;
                            font-weight: 700;
                        }

                        .row-qr-box {
                            display: flex;
                            width: 116px;
                            height: 116px;
                            align-items: center;
                            justify-content: center;
                            border: 1px solid #cbd5e1;
                            overflow: hidden;
                        }

                        .row-qr-box img {
                            display: block;
                            width: 110px;
                            height: 110px;
                        }

                        .qr-fallback {
                            padding: 5px;
                            font-size: 12px;
                            text-align: center;
                            overflow-wrap: anywhere;
                        }

                        .pdf-grid {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            border: 2px solid #d1d5db;
                        }

                        .pdf-item {
                            display: flex;
                            gap: 6px;
                            min-height: 34px;
                            padding: 8px 10px;
                            border-right: 1px solid #d1d5db;
                            border-bottom: 1px solid #d1d5db;
                            line-height: 1.4;
                            overflow-wrap: anywhere;
                        }

                        .pdf-item:nth-child(2n) {
                            border-right: 0;
                        }

                        .pdf-label {
                            flex-shrink: 0;
                            font-weight: 700;
                        }

                        .pdf-value {
                            font-weight: 400;
                        }

                        .pdf-sign {
                            position: absolute;
                            right: 8mm;
                            bottom: 8mm;
                            left: 8mm;
                            display: grid;
                            grid-template-columns: repeat(4, 1fr);
                            gap: 20px;
                            text-align: center;
                            color: #000;
                            font-size: 13px;
                            font-weight: 700;
                        }

                        .pdf-sign-item {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            gap: 8px;
                        }

                        .pdf-sign-line {
                            width: 75%;
                            border-top: 1px solid #000;
                        }

                        @media print {

                            .print-card {
                                min-height: 275mm;
                            }

                            .pdf-grid,
                            .pdf-item {
                                break-inside: avoid;
                            }

                            img {
                                print-color-adjust: exact;
                            }

                        }

                    </style>

                </head>

                <body>

                    <main class="print-card">

                        <header class="print-header">

                            <div class="print-title">
                                Knitting Store Report
                            </div>

                            <div class="row-qr-box">
                                ${qrHTML}
                            </div>

                        </header>

                        <section class="pdf-grid">

                            ${rowsHTML}

                        </section>

                        <footer class="pdf-sign">

                            <div class="pdf-sign-item">
                                <div class="pdf-sign-line"></div>
                                Supervisor
                            </div>

                            <div class="pdf-sign-item">
                                <div class="pdf-sign-line"></div>
                                Incharge
                            </div>

                            <div class="pdf-sign-item">
                                <div class="pdf-sign-line"></div>
                                AGM
                            </div>

                            <div class="pdf-sign-item">
                                <div class="pdf-sign-line"></div>
                                GM
                            </div>

                        </footer>

                    </main>

                </body>

                </html>
            `;
        }

    </script>
    <script src="js/knitting_rack_transfer_report.js"></script>

</body>

</html>
