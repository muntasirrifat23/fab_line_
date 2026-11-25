<?php
$reportConfig = [
    'title' => 'Knitting Batch Delivery Report',
    'from' => 'dyeing_batch_delivery t',
    'machine' => "COALESCE(t.DYMCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'order' => 't.DBDTID DESC',
    'theme_start' => '#1e3a8a',
    'theme_end' => '#4f46e5'
];
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$reportTitle = $reportConfig['title'];
$reportFrom = $reportConfig['from'];
$reportMachine = $reportConfig['machine'];
$reportOperator = $reportConfig['operator'];
$reportOperatorId = $reportConfig['operator_id'];
$reportProcessAlias = $reportConfig['process_alias'];
$reportOrder = $reportConfig['order'];
$reportWhere = isset($reportConfig['where']) ? $reportConfig['where'] : '';
$reportThemeStart = $reportConfig['theme_start'];
$reportThemeEnd = $reportConfig['theme_end'];
$reportTimeColumns = isset($reportConfig['time_columns']) ? $reportConfig['time_columns'] : [];
$reportColumnCount = 26 + count($reportTimeColumns);
$processFields = dyeingProcessFieldNames();

$processSelect = [];
foreach ($processFields as $field) {
    $processSelect[] = "COALESCE({$reportProcessAlias}.`{$field}`, '') AS `{$field}`";
}

$timeSelect = [];
$timeColumnLabels = [
    'START_TIME' => 'Start Time',
    'END_TIME' => 'End Time'
];
foreach ($reportTimeColumns as $timeColumn) {
    if ($timeColumn === 'START_TIME') {
        $timeSelect[] = 't.DYSTART AS START_TIME';
    } elseif ($timeColumn === 'END_TIME') {
        $timeSelect[] = 't.DYEND AS END_TIME';
    } else {
        throw new InvalidArgumentException('Unsupported Knitting Batch Delivery report time column.');
    }
}
$timeSelectSql = $timeSelect
    ? implode(",\n            ", $timeSelect) . ",\n            "
    : '';

$loadError = false;
$challanSelectSql = "'' AS KCHALLAN,
            COALESCE(DATE_FORMAT(t.BUDAT, '%d-%m-%Y'), '') AS CHALLAN_DATE,
            '' AS TO_NAME,
            '' AS GATE_PASS_NO,
            '' AS VEHICLE_NO,
            '' AS DELIVERY_REMARKS";
$deliverySchema = $db->query("SHOW COLUMNS FROM dyeing_batch_delivery");
if (!$deliverySchema) {
    error_log('Knitting Batch Delivery report schema query failed: ' . $db->error);
    $loadError = true;
} else {
    $deliveryColumnNames = [];
    while ($column = $deliverySchema->fetch_assoc()) {
        $deliveryColumnNames[] = strtoupper($column['Field']);
    }
    $deliverySchema->free();
    $selectDeliveryColumn = function ($columnName) use ($deliveryColumnNames) {
        return in_array($columnName, $deliveryColumnNames, true)
            ? "COALESCE(t.`{$columnName}`, '')"
            : "''";
    };
    $challanDateSelect = in_array('BUDAT', $deliveryColumnNames, true)
        ? "COALESCE(DATE_FORMAT(t.BUDAT, '%d-%m-%Y'), '')"
        : "''";
    $challanSelectSql = $selectDeliveryColumn('KCHALLAN') . " AS KCHALLAN,
            {$challanDateSelect} AS CHALLAN_DATE,
            " . $selectDeliveryColumn('TO_NAME') . " AS TO_NAME,
            " . $selectDeliveryColumn('GATE_PASS_NO') . " AS GATE_PASS_NO,
            " . $selectDeliveryColumn('VEHICLE_NO') . " AS VEHICLE_NO,
            " . $selectDeliveryColumn('DELIVERY_REMARKS') . " AS DELIVERY_REMARKS";
}

$sql = "SELECT
            t.BCARD,
            t.ROLL,
            t.PO_NUMBER,
            t.COLOR,
            t.QTY,
            {$reportMachine} AS MCNO,
            t.SONO,
            t.BUYER,
            t.STYLE,
            {$challanSelectSql},
            t.MCDIA,
            t.CUSTOMER,
            t.O_T,
            t.SL,
            t.FTYPE,
            t.FGSM,
            t.FDIA,
            t.YTYPE,
            t.YCOUNT,
            t.YBRAND,
            {$timeSelectSql}{$reportOperator} AS OPERATOR,
            {$reportOperatorId} AS OP_ID,
            " . implode(",\n            ", $processSelect) . "
        FROM {$reportFrom}";
if ($reportWhere !== '') {
    $sql .= ' WHERE ' . $reportWhere;
}
$sql .= ' ORDER BY ' . $reportOrder;

$rows = [];
$result = $loadError ? false : $db->query($sql);
if (!$result) {
    if (!$loadError) {
        error_log('Knitting Batch Delivery report query failed: ' . $db->error);
    }
    http_response_code(500);
    $loadError = true;
} else {
    while ($row = $result->fetch_assoc()) {
        foreach ($reportTimeColumns as $timeColumn) {
            $value = isset($row[$timeColumn]) ? trim((string) $row[$timeColumn]) : '';
            if ($value === '') {
                $row[$timeColumn] = '-';
                continue;
            }
            try {
                $row[$timeColumn] = (new DateTimeImmutable($value, new DateTimeZone('Asia/Dhaka')))
                    ->format('d-m-Y H:i:s');
            } catch (Throwable $error) {
                error_log('Invalid ' . $timeColumn . ' value in Knitting Batch Delivery report: ' . $value);
                $row[$timeColumn] = $value;
            }
        }
        $rows[] = $row;
    }
    $result->free();
}

function dyeingReportEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo dyeingReportEscape($reportTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #f1f5f9; color: #1e293b; font-family: 'Segoe UI', Roboto, system-ui, sans-serif; }
        .report-header { display: flex; align-items: center; gap: 16px; min-height: 58px; margin-bottom: 16px; padding: 10px 14px; border: 1px solid color-mix(in srgb, <?php echo dyeingReportEscape($reportThemeEnd); ?> 72%, white); border-radius: 12px; background: linear-gradient(135deg, rgba(255,255,255,.18), rgba(255,255,255,0) 48%, rgba(15,23,42,.16)), linear-gradient(115deg, color-mix(in srgb, <?php echo dyeingReportEscape($reportThemeStart); ?> 94%, transparent), color-mix(in srgb, <?php echo dyeingReportEscape($reportThemeEnd); ?> 90%, transparent)); box-shadow: inset 0 1px 0 rgba(255,255,255,.32), 0 8px 20px rgba(15,23,42,.18); color: #fff; backdrop-filter: blur(16px) saturate(155%); -webkit-backdrop-filter: blur(16px) saturate(155%); }
        .report-header h1 { flex: 1; margin: 0; color: #fff; text-align: center; font-size: 1.65rem; font-weight: 800; letter-spacing: .2px; text-shadow: 0 2px 6px rgba(15,23,42,.3); }
        .record-count { min-width: 76px; margin: 0; color: #fff; text-align: right; font-size: .88rem; font-weight: 700; text-shadow: 0 1px 4px rgba(15,23,42,.35); }
        .search-panel, .table-card { margin-bottom: 16px; overflow: hidden; border: 1px solid #dbe3ee; border-radius: 10px; background: #fff; box-shadow: 0 4px 12px rgba(15,23,42,.04); }
        .search-panel { padding: 12px; }
        .search-row { display: flex; gap: 8px; }
        .search-row input { flex: 1; min-width: 180px; padding: 10px 13px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem; outline: none; }
        .search-row input:focus { border-color: <?php echo dyeingReportEscape($reportThemeEnd); ?>; box-shadow: 0 0 0 3px <?php echo dyeingReportEscape($reportThemeEnd); ?>22; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 14px; border: 0; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; }
        .btn-back { background: #111827; }
        .btn-search { background: #2563eb; }
        .btn-clear { background: #64748b; }
        .btn-print { padding: 6px 10px; background: #16a34a; }
        .table-scroll { overflow: auto; max-height: calc(100vh - 190px); }
        table { width: 100%; min-width: 1850px; border: 1px solid #94a3b8; border-collapse: separate; border-spacing: 0; font-size: .9rem; }
        thead th { position: sticky; top: 0; z-index: 3; padding: 11px 9px; border-right: 1px solid rgba(255,255,255,.2); border-bottom: 3px solid <?php echo dyeingReportEscape($reportThemeEnd); ?>; background: #334155; color: #fff; text-align: left; white-space: nowrap; }
        tbody td { padding: 9px; border-right: 1px solid #94a3b8; border-bottom: 1px solid #e5e7eb; white-space: nowrap; vertical-align: middle; }
        .report-table tbody tr.batch-group-start td { border-top: 2px solid #64748b; }
        tbody td.go-to-cell { width: 220px; min-width: 180px; max-width: 220px; white-space: normal; overflow-wrap: anywhere; line-height: 1.35; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eff6ff; }
        .process-list { display: grid; grid-template-columns: repeat(2, max-content); gap: 4px; }
        .process-chip { padding: 3px 7px; border: 1px solid #99d5c9; border-radius: 6px; background: #e6f8f3; color: #075e54; font-size: .78rem; font-weight: 700; }
        .empty-row td, .error-row td { padding: 28px; color: #64748b; text-align: center; font-weight: 700; }
        .error-row td { color: #b91c1c; }
        #challanPrint { display: none; }
        .challan-page { display: flex; flex-direction: column; width: 190mm; min-height: 270mm; margin: 0 auto; color: #111; font: 11px Arial, Helvetica, sans-serif; }
        .challan-heading { display: grid; grid-template-columns: 1fr 3fr 1fr; align-items: center; min-height: 16mm; }
        .challan-wordmark { color: #1e3a8a; font-size: 15px; font-style: italic; font-weight: 800; }
        .challan-company { text-align: center; }
        .challan-company h2 { margin: 0; font-size: 20px; letter-spacing: .2px; }
        .challan-company p { margin: 1px 0 0; font-size: 10px; font-weight: 600; }
        .challan-title { display: table; margin: 1px auto 4px; padding: 1px 10px; border: 1px solid #111; border-radius: 20px; font-size: 10px; font-weight: 700; }
        .challan-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 8px; margin-bottom: 2mm; }
        .challan-to { grid-column: 1 / -1; min-height: 5mm; }
        .challan-meta-list { display: grid; grid-column: 1 / -1; grid-template-columns: 1fr 1fr; gap: 2px 8px; }
        .challan-meta-list div { min-height: 13px; }
        .challan-page table { width: 100%; min-width: 0; border-collapse: collapse; table-layout: fixed; font-size: var(--challan-font-size, 10px); }
        .challan-page th, .challan-page td { padding: var(--challan-cell-padding, 5px) 2px; border: 1px solid #222; color: #111; text-align: center; vertical-align: middle; line-height: 1; }
        .challan-page th { border: 2px solid #111; background: #fff; text-align: center; font-weight: 700; }
        .challan-page tbody tr { background: #fff; }
        .challan-page tbody tr.blank-fill td { border-top: 0; border-bottom: 0; }
        .challan-page tbody tr.blank-fill-last td { border-bottom: 1px solid #111; }
        .challan-page td { white-space: normal; }
        .challan-page tbody td { height: var(--challan-row-height, 12mm); }
        .challan-page tfoot th { background: #fff; }
        .challan-quantity-lines { margin-top: 2mm; }
        .challan-quantity-line { min-height: 14px; margin-bottom: 1px; }
        .challan-signatures { display: flex; justify-content: space-between; gap: 8px; margin-top: auto; padding-top: 5mm; }
        .challan-signatures > span { position: relative; flex: 1; padding-top: 4px; border-top: 1px solid #222; text-align: center; white-space: nowrap; }
        .challan-prepared-name { position: absolute; right: 0; bottom: calc(100% + 4px); left: 0; font-size: 9px; font-weight: 700; }
        @media (max-width: 700px) {
            body { padding: 10px; }
            .report-header { flex-wrap: wrap; gap: 8px; }
            .report-header h1 { order: -1; flex-basis: 100%; font-size: 1.35rem; }
            .record-count { flex: 1; text-align: right; }
            .search-row { flex-wrap: wrap; }
            .search-row input { flex-basis: 100%; }
        }
        @media print {
            @page { size: A4 portrait; margin: 8mm; }
            body { padding: 0; background: #fff; }
            body > main { display: none !important; }
            #challanPrint { display: block !important; }
            .challan-page { width: 100%; height: 279mm; min-height: 0; max-height: 279mm; margin: 0; overflow: hidden; page-break-inside: avoid; break-inside: avoid; }
            .challan-page table { page-break-inside: avoid; break-inside: avoid; }
            .challan-page thead { display: table-header-group; }
            .challan-page tr { page-break-inside: avoid; }
            .challan-page tfoot { display: table-row-group; }
        }
    </style>
</head>
<body>
    <main>
        <header class="report-header no-print">
            <a class="btn btn-back" href="report.php"><i class="fa-solid fa-arrow-left"></i> Back to Report</a>
            <h1><?php echo dyeingReportEscape($reportTitle); ?></h1>
            <p class="record-count" id="recordCount"></p>
        </header>
        <div class="search-panel no-print">
            <div class="search-row">
                <input type="search" id="reportSearch" placeholder="Search Batch / Roll / PO Number / SONO / Buyer / Style">
                <button class="btn btn-search" id="searchBtn" type="button"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                <button class="btn btn-clear" id="clearBtn" type="button"><i class="fa-solid fa-rotate-left"></i> Clear</button>
            </div>
        </div>
        <div class="table-card">
            <div class="table-scroll">
                <table class="report-table">
                    <thead><tr id="reportHeader"></tr></thead>
                    <tbody id="reportBody">
                        <?php if ($loadError): ?>
                            <tr class="error-row"><td colspan="<?php echo $reportColumnCount; ?>">Could not load this report. Please try again or contact the administrator.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <section id="challanPrint" aria-hidden="true"></section>
    <script>
        var reportRows = <?php echo json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
        var reportColumns = [
            ['ACTION', 'ACTION'],
            ['KCHALLAN', 'Challan No'],
            ['TO_NAME', 'GO TO'],
            ['VEHICLE_NO', 'Vehicle NO'],
            ['BCARD', 'Batch'],
            ['ROLL', 'Roll'],
            ['PO_NUMBER', 'PO Number'],
            ['COLOR', 'Color'],
            ['QTY', 'QTY'],
            ['MCNO', 'MCNO'],
            ['SONO', 'SONO'],
            ['BUYER', 'Buyer'],
            ['STYLE', 'Style'],
            ['MCDIA', 'MC Dia'],
            ['CUSTOMER', 'Customer'],
            ['O_T', 'O / T'],
            ['SL', 'SL/VQ'],
            ['FTYPE', 'Fabrics Type'],
            ['FGSM', 'Finish GSM'],
            ['FDIA', 'Finish Dia'],
            ['YTYPE', 'Yarn Type'],
            ['YCOUNT', 'Yarn Count'],
            ['YBRAND', 'Yarn Brand'],
            ['PROCESS', 'Process'],
            <?php foreach ($reportTimeColumns as $timeColumn): ?>
            [<?php echo json_encode($timeColumn); ?>, <?php echo json_encode($timeColumnLabels[$timeColumn]); ?>],
            <?php endforeach; ?>
            ['OPERATOR', 'Operator'],
            ['OP_ID', 'OP Id']
        ];
        var processFields = <?php echo json_encode($processFields); ?>;

        function setHeaders() {
            var header = document.getElementById('reportHeader');
            reportColumns.forEach(function(column) {
                var th = document.createElement('th');
                th.textContent = column[1];
                if (column[0] === 'ACTION') th.className = 'action-column';
                header.appendChild(th);
            });
        }

        function processChips(row) {
            var values = processFields.filter(function(field) {
                return String(row[field] || '').trim() !== '';
            });
            if (!values.length) return document.createTextNode('-');
            var list = document.createElement('div');
            list.className = 'process-list';
            values.forEach(function(field) {
                var chip = document.createElement('span');
                chip.className = 'process-chip';
                chip.textContent = row[field];
                list.appendChild(chip);
            });
            return list;
        }

        function escapeHtml(value) {
            var element = document.createElement('span');
            element.textContent = value === null || value === undefined ? '' : String(value);
            return element.innerHTML;
        }

        function formatQty(value) {
            var quantity = Number(value);
            return Number.isFinite(quantity) ? quantity.toFixed(3).replace(/\.?0+$/, '') : '0';
        }

        function numberToWords(value) {
            var ones = [
                'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
                'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
                'seventeen', 'eighteen', 'nineteen'
            ];
            var tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
            var number = Math.floor(Math.max(0, Number(value) || 0));
            if (number < 20) return ones[number];
            if (number < 100) {
                return tens[Math.floor(number / 10)] + (number % 10 ? ' ' + ones[number % 10] : '');
            }
            if (number < 1000) {
                return ones[Math.floor(number / 100)] + ' hundred' +
                    (number % 100 ? ' ' + numberToWords(number % 100) : '');
            }
            var units = [
                {value: 10000000, label: 'crore'},
                {value: 100000, label: 'lakh'},
                {value: 1000, label: 'thousand'}
            ];
            for (var index = 0; index < units.length; index++) {
                if (number >= units[index].value) {
                    var count = Math.floor(number / units[index].value);
                    var remainder = number % units[index].value;
                    return numberToWords(count) + ' ' + units[index].label +
                        (remainder ? ' ' + numberToWords(remainder) : '');
                }
            }
            return '';
        }

        function printBatch(batch, selectedKChallan) {
            var batchRows = reportRows.filter(function(row) {
                return String(row.BCARD || '') === String(batch);
            });
            if (!batchRows.length) return;

            if (selectedKChallan) {
                batchRows = batchRows.filter(function(row) {
                    return String(row.KCHALLAN || '').trim() === selectedKChallan;
                });
            }
            if (!batchRows.length) return;
            var challanNumber = String(batchRows[0].KCHALLAN || '').trim();
            var challanRows = challanNumber
                ? reportRows.filter(function(row) {
                    return String(row.KCHALLAN || '').trim() === challanNumber;
                })
                : batchRows;
            var groups = new Map();
            challanRows.forEach(function(row) {
                var batchNo = String(row.BCARD || '');
                if (!groups.has(batchNo)) groups.set(batchNo, []);
                groups.get(batchNo).push(row);
            });

            var serial = 0;
            var totalQty = 0;
            var tableRows = '';
            var orderedGroups = Array.from(groups.entries()).sort(function(left, right) {
                return String(left[0]).localeCompare(String(right[0]));
            });
            orderedGroups.forEach(function(entry) {
                var batchNo = entry[0];
                var group = entry[1];
                group.forEach(function(row, index) {
                    serial++;
                    totalQty += Number(row.QTY) || 0;
                    tableRows += '<tr>' +
                        (index === 0
                            ? '<td rowspan="' + group.length + '">' + escapeHtml(batchNo) + '</td>'
                            : '') +
                        '<td>' + escapeHtml(row.ROLL) + '</td>' +
                        '<td>' + escapeHtml(row.FTYPE) + '</td>' +
                        (index === 0
                            ? '<td rowspan="' + group.length + '">' + group.length + '</td>'
                            : '') +
                        '<td>' + escapeHtml(formatQty(row.QTY)) + '</td>' +
                        (index === 0
                            ? '<td rowspan="' + group.length + '">' +
                                escapeHtml(group[0].DELIVERY_REMARKS) + '</td>'
                            : '') +
                        '</tr>';
                });
            });
            var challanRowHeight = serial > 12 ? Math.min(12, 210 / serial) : 12;
            var challanFontSize = serial > 12 ? Math.max(4, Math.min(10, challanRowHeight * 1.6)) : 10;
            var challanCellPadding = serial > 12 ? Math.max(0.5, Math.min(2, challanRowHeight * 0.2)) : 5;
            var challanDensityStyle = '--challan-row-height:' + challanRowHeight.toFixed(2) + 'mm;' +
                '--challan-font-size:' + challanFontSize.toFixed(1) + 'px;' +
                '--challan-cell-padding:' + challanCellPadding.toFixed(1) + 'px';
            for (var blankRow = serial; blankRow < 12; blankRow++) {
                var blankRowClass = blankRow === 11 ? 'blank-fill blank-fill-last' : 'blank-fill';
                tableRows += '<tr class="' + blankRowClass + '">' +
                    '<td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>';
            }

            var header = batchRows[0];
            var totalMilliKg = Math.round(totalQty * 1000);
            var wholeKg = Math.floor(totalMilliKg / 1000);
            var grams = totalMilliKg % 1000;
            var quantityWords = numberToWords(wholeKg) + ' kilogram' + (wholeKg === 1 ? '' : 's');
            if (grams) quantityWords += ' and ' + numberToWords(grams) + ' grams';
            quantityWords += ' only';

            document.getElementById('challanPrint').innerHTML =
                '<article class="challan-page" style="' + challanDensityStyle + '">' +
                '<header class="challan-heading">' +
                '<div class="challan-wordmark">PURBANI</div>' +
                '<div class="challan-company"><h2>PURBANI FABRICS LTD.</h2>' +
                '<p>NOORBAGH, MOUCHAK, KALIAKOIR, GAZIPUR</p></div><div></div>' +
                '</header>' +
                '<div class="challan-title">DELIVERY CHALLAN</div>' +
                '<div class="challan-meta">' +
                '<div class="challan-to"><strong>To:</strong> ' + escapeHtml(header.TO_NAME || '') + '</div>' +
                '<div class="challan-meta-list">' +
                '<div><strong>Challan No.:</strong> ' + escapeHtml(header.KCHALLAN || '-') + '</div>' +
                '<div><strong>Date:</strong> ' + escapeHtml(header.CHALLAN_DATE || '-') + '</div>' +
                '<div><strong>Gate Pass No.:</strong> ' + escapeHtml(header.GATE_PASS_NO || '-') + '</div>' +
                '<div><strong>Vehicle No.:</strong> ' + escapeHtml(header.VEHICLE_NO || '-') + '</div>' +
                '</div></div>' +
                '<table><colgroup><col style="width:18%"><col style="width:18%"><col style="width:26%">' +
                '<col style="width:10%"><col style="width:13%"><col style="width:15%"></colgroup>' +
                '<thead><tr><th>Batch No.</th><th>Roll No.</th><th>Fabrics Type</th>' +
                '<th>Roll</th><th>Batch Qty</th><th>Remarks</th></tr></thead><tbody>' +
                tableRows + '</tbody><tfoot><tr><th colspan="3">TOTAL</th><th>' +
                serial + '</th><th>' + escapeHtml(formatQty(totalQty)) +
                '</th><th></th></tr></tfoot></table>' +
                '<div class="challan-quantity-lines">' +
                '<div class="challan-quantity-line">Total weight: ' + escapeHtml(formatQty(totalQty)) + ' KG</div>' +
                '<div class="challan-quantity-line">In words: ' + escapeHtml(quantityWords) + '</div>' +
                '</div><div class="challan-signatures"><span>Received by</span><span>Prepared By' +
                '<span class="challan-prepared-name">' + escapeHtml(header.OPERATOR || '') + '</span></span>' +
                '<span>Store Officer</span><span>Store Manager</span><span>Auth. Signature</span></div>' +
                '</article>';
            document.getElementById('challanPrint').setAttribute('aria-hidden', 'false');
            window.print();
        }

        function renderReport(rows) {
            var body = document.getElementById('reportBody');
            body.replaceChildren();
            document.getElementById('recordCount').textContent = rows.length + ' roll(s)';
            if (!rows.length) {
                var empty = body.insertRow();
                empty.className = 'empty-row';
                var cell = empty.insertCell();
                cell.colSpan = reportColumns.length;
                cell.textContent = 'No data found.';
                return;
            }

            var groups = new Map();
            rows.forEach(function(row) {
                var challanNumber = String(row.KCHALLAN || '').trim();
                var key = challanNumber
                    ? 'challan:' + challanNumber
                    : 'batch:' + String(row.BCARD || '');
                if (!groups.has(key)) groups.set(key, []);
                groups.get(key).push(row);
            });

            groups.forEach(function(group) {
                group.sort(function(left, right) {
                    return String(left.BCARD || '').localeCompare(String(right.BCARD || ''));
                });
                var renderedBatches = new Set();
                var printChallanKey = String(group[0].KCHALLAN || '').trim();

                group.forEach(function(row, index) {
                    var batch = String(row.BCARD || '');
                    var firstBatchRow = !renderedBatches.has(batch);
                    var firstChallanRow = index === 0;
                    if (firstBatchRow) renderedBatches.add(batch);
                    var tr = body.insertRow();
                    if (firstBatchRow) tr.className = 'batch-group-start';
                    tr.dataset.batch = batch;
                    var actionCell = tr.insertCell();
                    actionCell.className = 'action-column';
                    if (firstChallanRow) {
                        var printButton = document.createElement('button');
                        printButton.type = 'button';
                        printButton.className = 'btn btn-print no-print';
                        printButton.innerHTML = '<i class="fa-solid fa-print"></i> Print';
                        printButton.addEventListener('click', function() {
                            printBatch(batch, printChallanKey);
                        });
                        actionCell.appendChild(printButton);
                    }

                    var challanCell = tr.insertCell();
                    challanCell.textContent = firstBatchRow ? row.KCHALLAN || '' : '';

                    var goToCell = tr.insertCell();
                    goToCell.className = 'go-to-cell';
                    goToCell.textContent = firstBatchRow ? row.TO_NAME || '' : '';

                    var vehicleCell = tr.insertCell();
                    vehicleCell.textContent = firstBatchRow ? row.VEHICLE_NO || '' : '';

                    var batchCell = tr.insertCell();
                    batchCell.textContent = firstBatchRow ? batch : '';

                    reportColumns.slice(5).forEach(function(column) {
                        var cell = tr.insertCell();
                        if (column[0] === 'PROCESS') {
                            if (firstBatchRow) cell.appendChild(processChips(row));
                            return;
                        }
                        var value = row[column[0]];
                        cell.textContent = value === null || value === undefined ? '' : value;
                    });
                });
            });
        }

        function applySearch() {
            var query = document.getElementById('reportSearch').value.trim().toLowerCase();
            var rows = query ? reportRows.filter(function(row) {
                return Object.keys(row).some(function(key) {
                    return String(row[key] === null ? '' : row[key]).toLowerCase().indexOf(query) !== -1;
                });
            }) : reportRows;
            renderReport(rows);
        }

        document.getElementById('searchBtn').addEventListener('click', applySearch);
        document.getElementById('clearBtn').addEventListener('click', function() {
            document.getElementById('reportSearch').value = '';
            applySearch();
        });
        document.getElementById('reportSearch').addEventListener('keydown', function(event) {
            if (event.key === 'Enter') applySearch();
        });
        window.addEventListener('afterprint', function() {
            var printArea = document.getElementById('challanPrint');
            printArea.innerHTML = '';
            printArea.setAttribute('aria-hidden', 'true');
        });

        setHeaders();
        renderReport(reportRows);
    </script>
</body>
</html>
