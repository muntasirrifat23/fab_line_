<?php
$reportConfig = [
    'title' => 'Dyeing Batch Card Report',
    'from' => 'dyeing_batch_card t',
    'machine' => "COALESCE(t.DYMCNO, '')",
    'operator' => "COALESCE(t.DNAME, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'order' => 'CAST(t.BCARD AS UNSIGNED) DESC, t.DBCTID ASC',
    'theme_start' => '#1e3a8a',
    'theme_end' => '#2563eb'
];
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

try {
    ensureDyeingBatchLineSchema($db);
    ensureDyeingBatchQuantityColumns($db);
    ensureDyeingBatchCategoryColumn($db);
    ensureDyeingProcessColumns($db, 'dyeing_batch_card');
    ensureDyeingBatchCreatorColumns($db);
} catch (Throwable $error) {
    error_log('Dyeing batch quantity initialization failed: ' . $error->getMessage());
    http_response_code(500);
    exit('Could not initialize dyeing batch quantities. Please contact the administrator.');
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
$reportColumnCount = 19 + count($reportTimeColumns);
$processFields = dyeingBatchCardProcessFieldNames();

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
        throw new InvalidArgumentException('Unsupported dyeing report time column.');
    }
}
$timeSelectSql = $timeSelect
    ? implode(",\n            ", $timeSelect) . ",\n            "
    : '';

$sql = "SELECT
            t.BCARD,
            t.BUDAT,
            t.CATEGORY,
            t.PO_NUMBER,
            t.COLOR,
            t.RQTY AS BATCH_QTY,
            t.BODY_QTY,
            t.RIB_QTY,
            t.NECK_QTY,
            {$reportMachine} AS MCNO,
            t.SONO,
            t.BUYER,
            t.STYLE,
            t.MCDIA,
            t.SL,
            t.FTYPE,
            t.FGSM,
            t.FDIA,
            t.YTYPE,
            t.YCOUNT,
            t.YBRAND,
            t.LOT_NO,
            t.CREATED_DATE,
            {$timeSelectSql}{$reportOperator} AS DNAME,
            {$reportOperatorId} AS DID,
            " . implode(",\n            ", $processSelect) . "
        FROM {$reportFrom}";
if ($reportWhere !== '') {
    $sql .= ' WHERE ' . $reportWhere;
}
$sql .= ' ORDER BY ' . $reportOrder;

$rows = [];
$loadError = false;
$result = $db->query($sql);
if (!$result) {
    error_log('Dyeing report query failed: ' . $db->error);
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
                error_log('Invalid ' . $timeColumn . ' value in dyeing report: ' . $value);
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
        table { width: 100%; min-width: 1850px; border-collapse: separate; border-spacing: 0; font-size: .9rem; }
        thead th { position: sticky; top: 0; z-index: 3; padding: 11px 9px; border-bottom: 3px solid <?php echo dyeingReportEscape($reportThemeEnd); ?>; background: #334155; color: #fff; text-align: left; white-space: nowrap; }
        tbody td { padding: 9px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; vertical-align: middle; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eff6ff; }
        .process-list { display: grid; grid-template-columns: 1fr; gap: 4px; }
        .process-chip { padding: 3px 7px; border: 1px solid #99d5c9; border-radius: 6px; background: #e6f8f3; color: #075e54; font-size: .78rem; font-weight: 700; }
        .empty-row td, .error-row td { padding: 28px; color: #64748b; text-align: center; font-weight: 700; }
        .error-row td { color: #b91c1c; }
        .batch-print-report { display: none; }
        .print-page { position: relative; box-sizing: border-box; width: 210mm; height: 297mm; margin: 0 auto; padding: 6mm 8mm 13mm; overflow: hidden; color: #111; background: #fff; font: 6.3pt Arial, sans-serif; }
        .print-page table { width: 100%; min-width: 0; border-collapse: collapse; table-layout: fixed; font-size: 6.1pt; }
        .print-page th, .print-page td { padding: 1px 2px; border: 1px solid #000; color: #000; text-align: center; vertical-align: middle; overflow-wrap: anywhere; word-break: break-word; }
        .print-header td { height: 20mm; }
        .print-brand { width: 14%; color: #a51f79; font: italic 15pt Georgia, serif; }
        .print-title { width: 57%; font-size: 10pt; font-weight: 700; }
        .print-title span { font-size: 8pt; }
        .print-header-info { width: 29%; font-size: 5.2pt; text-align: left !important; }
        .print-qr-cell { float: right; width: 18mm; border: 0 !important; }
        .print-qr-cell img, .print-qr-cell canvas { width: 18mm !important; height: 18mm !important; }
        .print-meta { margin-top: 1mm; }
        .print-meta .meta-label { width: 10%; font-weight: 700; }
        .print-meta td { height: 6mm; }
        .print-batch-layout, .print-route-section { margin-top: 1mm; }
        .print-section-title { width: 8%; font-size: 7pt; font-weight: 700; }
        .print-batch-inner { width: 100%; }
        .print-fabric-table th { font-size: 5.5pt; font-weight: 700; }
        .print-fabric-table td { height: 5.5mm; }
        .print-fabric-table th:nth-child(1) { width: 9%; }
        .print-fabric-table th:nth-child(2) { width: 25%; }
        .print-fabric-table th:nth-child(3) { width: 19%; }
        .print-fabric-table th:nth-child(4),
        .print-fabric-table th:nth-child(5),
        .print-fabric-table th:nth-child(6) { width: 10%; }
        .print-fabric-table th:nth-child(7) { width: 7%; }
        .print-fabric-table th:nth-child(8) { width: 10%; }
        .print-process-cell { width: 25%; padding: 0 !important; vertical-align: top !important; }
        .print-process-options th { height: 4mm; font-size: 6.2pt; }
        .print-process-options td { height: 3.7mm; text-align: left; white-space: nowrap; }
        .print-process-options .process-check { width: 8%; text-align: center; }
        .print-grand-total { font-weight: 700; text-align: right !important; }
        .print-comment { height: 8mm; text-align: left !important; vertical-align: top !important; }
        .print-subtitle { font-weight: 700; }
        .print-signatures { position: absolute; right: 8mm; bottom: 5mm; left: 8mm; width: 194mm !important; margin: 0; border: 0; }
        .print-signatures td { height: 10mm; padding: 0 2mm; border: 0; font-size: 6pt; font-weight: 700; white-space: nowrap; vertical-align: bottom; }
        .print-sign-line { width: 86%; height: 0; margin: 0 auto 2mm; border-top: 1px solid #000; }
        .print-route-label { width: 8%; font-weight: 700; }
        .print-route-section td { height: 6.5mm; }
        .print-route-section .print-comment { height: 10mm; text-align: left !important; vertical-align: top !important; }
        .print-route-section .print-section-title { height: auto; }
        .print-dyeing,
        .print-heat-set,
        .print-finishing,
        .print-quality,
        .print-store { margin-top: 1mm; }
        .print-dyeing td,
        .print-heat-set td,
        .print-finishing td,
        .print-quality td,
        .print-store td { height: 5.2mm; }
        .print-dyeing .print-section-title,
        .print-heat-set .print-section-title,
        .print-finishing .print-section-title,
        .print-quality .print-section-title,
        .print-store .print-section-title { width: 8%; height: auto; font-size: 7pt; font-weight: 700; }
        .print-dyeing .print-comment,
        .print-heat-set .print-comment,
        .print-finishing .print-comment,
        .print-quality .print-comment,
        .print-store .print-comment { height: 7mm; text-align: left !important; vertical-align: top !important; }
        .print-heat-set .print-subtitle,
        .print-finishing .print-subtitle { height: 4mm; }
        @media (max-width: 700px) {
            body { padding: 10px; }
            .report-header { flex-wrap: wrap; gap: 8px; }
            .report-header h1 { order: -1; flex-basis: 100%; font-size: 1.35rem; }
            .record-count { flex: 1; text-align: right; }
            .search-row { flex-wrap: wrap; }
            .search-row input { flex-basis: 100%; }
        }
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            main > .table-card { display: none !important; }
            .batch-print-report { display: block !important; }
            .print-page { margin: 0; padding: 5mm 8mm 10mm; width: 210mm; height: 297mm; min-height: 0; break-inside: avoid; page-break-inside: avoid; }
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
                <input type="search" id="reportSearch" placeholder="Search Batch / F/T / Fabric Type / PO Number / SONO / Buyer / Style">
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
        <section class="batch-print-report" id="batchPrintReport" aria-hidden="true"></section>
    </main>
    <script>
        var reportRows = <?php echo json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
        var reportColumns = [
            ['ACTION', 'ACTION'],
            ['BCARD', 'Batch'],
            ['CATEGORY', 'F/T'],
            ['PO_NUMBER', 'PO Number'],
            ['COLOR', 'Color'],
            ['BATCH_QTY', 'Batch QTY'],
            ['MCNO', 'MCNO'],
            ['SONO', 'SONO'],
            ['BUYER', 'Buyer'],
            ['STYLE', 'Style'],
            ['MCDIA', 'MC Dia'],
            ['SL', 'SL/VQ'],
            ['FTYPE', 'Fabrics Type'],
            ['FGSM', 'Finish GSM'],
            ['FDIA', 'Finish Dia'],
            ['YCOUNT', 'Yarn Count'],
            ['PROCESS', 'Process'],
            <?php foreach ($reportTimeColumns as $timeColumn): ?>
            [<?php echo json_encode($timeColumn); ?>, <?php echo json_encode($timeColumnLabels[$timeColumn]); ?>],
            <?php endforeach; ?>
            ['DNAME', 'Batch Created'],
            ['DID', 'Op ID']
        ];
        var processFields = <?php echo json_encode($processFields); ?>;
        var quantityFields = ['BATCH_QTY'];

        function formatQty(value) {
            if (value === null || value === undefined || String(value).trim() === '') return '';
            var qty = Number(value);
            return Number.isFinite(qty)
                ? qty.toFixed(3).replace(/\.?0+$/, '')
                : value;
        }

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
            return String(value === null || value === undefined ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function hasProcess(row, field) {
            return String(row[field] || '').trim() !== '';
        }

        function categoryForRow(row) {
            var category = String(row.CATEGORY || '').trim().toUpperCase();
            if (['BODY', 'RIB', 'NECK'].indexOf(category) !== -1) return category;
            if (Number(row.BODY_QTY) > 0) return 'BODY';
            if (Number(row.RIB_QTY) > 0) return 'RIB';
            if (Number(row.NECK_QTY) > 0) return 'NECK';
            return '';
        }

        function categoryLabel(row) {
            var category = categoryForRow(row);
            return {BODY: 'Body', RIB: 'RIB', NECK: 'Neck'}[category] || '';
        }

        function renderPrintReport(batch, rows) {
            if (!rows.length) throw new Error('No database records were found for batch card ' + batch + '.');

            var first = rows[0];
            var totalQty = rows.reduce(function(total, row) {
                var qty = Number(row.BATCH_QTY);
                return total + (Number.isFinite(qty) ? qty : 0);
            }, 0);
            var categoryLabels = {BODY: 'Body', RIB: 'RIB', NECK: 'Neck'};
            var fabricationGroups = {};
            function addUniqueValue(values, value) {
                var text = String(value || '').trim();
                if (text && values.indexOf(text) === -1) values.push(text);
            }
            rows.forEach(function(row) {
                var category = categoryForRow(row);
                var fabrication = String(row.FTYPE || '').trim();
                if (!fabrication) return;
                var fabricationKey = fabrication.toLocaleUpperCase();
                if (!fabricationGroups[fabricationKey]) {
                    fabricationGroups[fabricationKey] = {
                        fabrication: fabrication,
                        categories: {BODY: {qty: 0}, RIB: {qty: 0}, NECK: {qty: 0}},
                        machineGauge: [],
                        finishDia: [],
                        gsm: [],
                        qty: 0
                    };
                }
                var group = fabricationGroups[fabricationKey];
                var qty = Number(row.BATCH_QTY);
                if (categoryLabels[category]) {
                    group.categories[category].qty += Number.isFinite(qty) ? qty : 0;
                }
                addUniqueValue(group.machineGauge, row.MCDIA);
                addUniqueValue(group.finishDia, row.FDIA);
                addUniqueValue(group.gsm, row.FGSM);
                group.qty += Number.isFinite(qty) ? qty : 0;
            });

            var processLabels = {
                SILICON: 'Silicon',
                NONSILICON: 'Non-Silicon',
                NWASH: 'Normal Wash (YD)',
                EWASH: 'Enzyme Wash (YD)',
                ENZYME: 'Enzyme',
                NONENZYME: 'Non-Enzyme',
                PEACH: 'Peach',
                BRUSH: 'Brush',
                AOP: 'AOP',
                SOFTENER: 'Softener',
                NONSOFTENER: 'Non-Softener',
                OTHERS: 'Others',
                SHEARING: 'Shearing',
                SINGEING: 'Singeing',
                HEAT_SET: 'Heat Set'
            };
            var processOptions = [
                'SILICON', 'PEACH', 'NONSILICON', 'BRUSH', 'NWASH', 'AOP',
                'EWASH', 'ENZYME', 'NONENZYME', 'SOFTENER', 'OTHERS',
                'NONSOFTENER', 'SINGEING', 'SHEARING', 'HEAT_SET'
            ];
            function processRowFor(field) {
                return rows.find(function(row) {
                    return hasProcess(row, field);
                }) || null;
            }
            var selectedProcessOptions = processOptions.filter(function(field) {
                return processRowFor(field) !== null;
            });
            var processRows = [];
            for (var processIndex = 0; processIndex < selectedProcessOptions.length; processIndex += 2) {
                var processCells = selectedProcessOptions.slice(processIndex, processIndex + 2).map(function(field) {
                    var processRow = processRowFor(field);
                    var label = field === 'OTHERS' && processRow
                        ? processRow[field]
                        : (processLabels[field] || field);
                    return '<td class="process-check">' +
                        '&#9745;</td><td>' +
                        escapeHtml(label) + '</td>';
                }).join('');
                if (processIndex + 1 >= selectedProcessOptions.length) {
                    processCells += '<td colspan="2"></td>';
                }
                processRows.push('<tr>' + processCells + '</tr>');
            }
            if (!processRows.length) {
                processRows.push('<tr><td colspan="4">-</td></tr>');
            }
            var processHtml = '<table class="print-process-options"><thead><tr>' +
                '<th colspan="4">Process</th></tr></thead><tbody>' +
                processRows.join('') + '</tbody></table>';
            var fabricRows = '';
            var categoryOrder = ['BODY', 'RIB', 'NECK'];
            Object.keys(fabricationGroups).forEach(function(fabricationKey) {
                var group = fabricationGroups[fabricationKey];
                var presentCategories = categoryOrder.filter(function(category) {
                    return group.categories[category].qty > 0;
                });
                if (!presentCategories.length) {
                    presentCategories = ['BODY'];
                }
                presentCategories.forEach(function(category, categoryIndex) {
                    fabricRows += '<tr><td>' + categoryLabels[category] + '</td>';
                    if (categoryIndex === 0) {
                        fabricRows += '<td rowspan="' + presentCategories.length + '">' +
                            escapeHtml(group.fabrication) + '</td>';
                    }
                    fabricRows += '<td>' + escapeHtml(group.machineGauge.join(', ')) + '</td>' +
                        '<td>' + escapeHtml(group.finishDia.join(', ')) + '</td>' +
                        '<td>' + escapeHtml(group.gsm.join(', ')) + '</td>' +
                        '<td>' + formatQty(group.categories[category].qty) +
                        '</td><td></td><td></td></tr>';
                });
            });

            var dateValue = String(first.BUDAT || '').trim();
            if (dateValue && /^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
                var dateParts = dateValue.split('-');
                dateValue = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
            }

            var pageOne = '<article class="print-page">' +
                '<table class="print-header"><tr><td class="print-brand">PURBANI</td>' +
                '<td class="print-title">PURBANI FABRICS LTD.<br><span>Process Route Card</span></td>' +
                '<td class="print-header-info"><div class="print-qr-cell"><div class="batch-qr" data-batch="' +
                escapeHtml(batch) + '"></div></div><strong>FORM NO:</strong> PFL-QF-BATCH-001<br>' +
                '<strong>EFFECTIVE DATE:</strong> 01.06.2021<br><strong>REVISION NO.</strong> 00<br>' +
                '<strong>DATE:</strong> ' + escapeHtml(dateValue) + '</td></tr></table>' +
                '<table class="print-meta">' +
                '<tr><td class="meta-label">Dyeing Batch No.</td><td>' + escapeHtml(batch) +
                '</td><td class="meta-label">Style No.</td><td>' + escapeHtml(first.STYLE) +
                '</td><td class="meta-label">M/C No.</td><td>' + escapeHtml(first.MCNO) +
                '</td><td class="meta-label">PO Number</td><td>' + escapeHtml(first.PO_NUMBER) + '</td></tr>' +
                '<tr><td class="meta-label">Buyer</td><td>' + escapeHtml(first.BUYER) +
                '</td><td class="meta-label">Grey GSM</td><td>' + escapeHtml(first.SL) +
                '</td><td class="meta-label">Order</td><td>' + escapeHtml(first.SONO) +
                '</td><td class="meta-label">Yarn Count</td><td>' + escapeHtml(first.YCOUNT) + '</td></tr>' +
                '<tr><td class="meta-label">Color</td><td>' + escapeHtml(first.COLOR) +
                '</td><td class="meta-label">Finish GSM</td><td>' + escapeHtml(first.FGSM) +
                '</td><td class="meta-label">SO Number</td><td>' + escapeHtml(first.SONO) +
                '</td><td class="meta-label">Yarn Lot</td><td>' + escapeHtml(first.LOT_NO) + '</td></tr></table>' +
                '<table class="print-batch-layout"><tr><td class="print-section-title">Batch</td>' +
                '<td><table class="print-batch-inner"><thead><tr><th>F/T</th><th>Fabrication</th>' +
                '<th>M/C DIA X Gauge</th><th>F/DIA</th><th>GSM</th><th>Qty (kg)</th><th>Roll</th>' +
                '<th>Remark</th></tr></thead><tbody>' + fabricRows +
                '<tr><td colspan="5" class="print-grand-total">Total Wt (kg) / Total Roll</td><td>' +
                formatQty(totalQty) + '</td><td></td><td></td></tr></tbody></table></td>' +
                '<td class="print-process-cell">' + processHtml + '</td></tr>' +
                '<tr><td colspan="3" class="print-comment">Comments:</td></tr></table>' +
                '<table class="print-dyeing"><tr><td class="print-section-title" rowspan="3">Dyeing</td>' +
                '<td rowspan="2"><strong>Shade</strong><br>□ OK &nbsp; □ Not OK</td>' +
                '<td rowspan="2"><strong>Sign</strong></td>' +
                '<td colspan="5" class="print-subtitle">Proposed Parameter (Stenter)</td>' +
                '<td colspan="3" class="print-subtitle">Color Fastness</td><td rowspan="2"><strong>Sign</strong></td></tr>' +
                '<tr><td>Speed</td><td>Temp.</td><td>Softener</td><td>P/P</td><td>pH</td>' +
                '<td>Wash</td><td>Water</td><td>Rubbing / Perspiration</td></tr>' +
                '<tr><td colspan="2">Shade: □ OK &nbsp; □ Not OK</td>' +
                '<td colspan="5" class="print-comment">Comments:</td>' +
                '<td colspan="4">Sign: __________________</td></tr></table>' +
                '<table class="print-heat-set"><tr><td class="print-section-title" rowspan="2">Heat Set<br>Parameter</td>' +
                '<td>R. Dia</td><td>Set Dia</td><td>A. Dia</td><td>R. GSM</td><td>A. GSM</td>' +
                '<td>Speed</td><td>Temp.</td><td>Over Feed%</td><td>Blower</td><td>Remark</td><td>Sign</td></tr>' +
                '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr></table>' +
                '<table class="print-finishing"><tr><td class="print-section-title" rowspan="13">Finishing</td>' +
                '<td colspan="10" class="print-subtitle">Shade Roll Parameter (Stenter)</td></tr>' +
                '<tr><td>Shade</td><td>Sign</td><td>Speed</td><td>Temp.</td><td>Softener</td><td>P/P</td><td>pH</td><td>Over Feed%</td><td>Remark</td><td>Sign</td></tr>' +
                '<tr><td>OK / Not OK</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>' +
                '<tr><td colspan="10" class="print-subtitle">Shade Roll Parameter (Compactor)</td></tr>' +
                '<tr><td>Shade</td><td>Sign</td><td>Speed</td><td>Temp.</td><td>B/P</td><td>Set Dia</td><td>Act. Dia</td><td>Comp.%</td><td>Remark</td><td>Sign</td></tr>' +
                '<tr><td>OK / Not OK</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>' +
                '<tr><td colspan="10" class="print-subtitle">Bulk Parameter (Stenter)</td></tr>' +
                '<tr><td>DIA</td><td>GSM</td><td>Speed</td><td>Temp.</td><td>Softener</td><td>P/P</td><td>pH</td><td>Over Feed%</td><td>Remark</td><td>Sign</td></tr>' +
                '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>' +
                '<tr><td colspan="10" class="print-subtitle">Bulk Parameter (Compactor)</td></tr>' +
                '<tr><td>DIA</td><td>GSM</td><td>Speed</td><td>Temp.</td><td>B/P</td><td>Set Dia</td><td>Act. Dia</td><td>Comp.%</td><td>Remark</td><td>Sign</td></tr>' +
                '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>' +
                '<tr><td colspan="10" class="print-comment">Comments:</td></tr></table>' +
                '<table class="print-quality"><tr><td class="print-section-title" rowspan="4">Quality</td>' +
                '<td colspan="4" class="print-subtitle">Online Check (Stenter)</td>' +
                '<td colspan="4" class="print-subtitle">Online Check (Compact)</td>' +
                '<td colspan="2" class="print-subtitle">Decision</td></tr>' +
                '<tr><td>Shade</td><td>Sign</td><td>DIA</td><td>GSM</td>' +
                '<td>Shade</td><td>Sign</td><td>Surface</td><td>Uneven</td>' +
                '<td colspan="2">□ Go for Compacting</td></tr>' +
                '<tr><td>□ OK</td><td></td><td></td><td></td><td>□ OK / □ Not OK</td><td></td><td></td><td></td>' +
                '<td colspan="2">□ Return to Dyeing/Finishing</td></tr>' +
                '<tr><td colspan="9" class="print-comment">Comments:</td><td colspan="2">Sign: __________</td></tr></table>' +
                '<table class="print-store"><tr><td class="print-section-title" rowspan="2">Finish<br>Fabrics<br>Store</td>' +
                '<td>Rack No.</td><td>Other Position</td><td>Position after R/P</td><td>Remark</td><td>Sign</td></tr>' +
                '<tr><td></td><td></td><td></td><td></td><td></td></tr></table>' +
                '<table class="print-signatures"><tr><td><div class="print-sign-line"></div>Batch Shift Incharge</td>' +
                '<td><div class="print-sign-line"></div>Batch Supervisor</td><td><div class="print-sign-line"></div>' +
                'Batch Incharge</td><td><div class="print-sign-line"></div>Batch Manager</td></tr></table></article>';

            var printReport = document.getElementById('batchPrintReport');
            printReport.innerHTML = pageOne;
            printReport.setAttribute('aria-hidden', 'false');
            if (typeof QRCode === 'undefined') {
                throw new Error('QR-code library could not be loaded. Check the internet connection and retry printing.');
            }
            var qrContainer = printReport.querySelector('.batch-qr');
            new QRCode(qrContainer, {
                text: String(batch),
                width: 68,
                height: 68,
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        function printBatch(batch) {
            fetch('ajaxDyeing_batch_card_Report.php?batch=' + encodeURIComponent(batch), {
                headers: {'Accept': 'application/json'}
            }).then(function(response) {
                if (!response.ok) throw new Error('Could not load batch card details for printing.');
                return response.json();
            }).then(function(response) {
                if (!response.success) {
                    throw new Error(response.error || 'Could not load batch card details for printing.');
                }
                renderPrintReport(batch, response.data || []);
                window.print();
            }).catch(function(error) {
                window.alert(error.message);
            });
        }

        function renderReport(rows) {
            var body = document.getElementById('reportBody');
            body.replaceChildren();
            document.getElementById('recordCount').textContent = rows.length + ' fabric line(s)';
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
                var key = String(row.BCARD || '');
                if (!groups.has(key)) groups.set(key, []);
                groups.get(key).push(row);
            });

            groups.forEach(function(group, batch) {
                group.forEach(function(row, index) {
                    var tr = body.insertRow();
                    tr.dataset.batch = batch;
                    if (index === 0) {
                        var actionCell = tr.insertCell();
                        actionCell.rowSpan = group.length;
                        actionCell.className = 'action-column';
                        var printButton = document.createElement('button');
                        printButton.type = 'button';
                        printButton.className = 'btn btn-print no-print';
                        printButton.innerHTML = '<i class="fa-solid fa-print"></i> Print';
                        printButton.addEventListener('click', function() { printBatch(batch); });
                        actionCell.appendChild(printButton);

                        var batchCell = tr.insertCell();
                        batchCell.rowSpan = group.length;
                        batchCell.textContent = batch;
                    }

                    reportColumns.slice(2).forEach(function(column) {
                        var cell = tr.insertCell();
                        if (column[0] === 'PROCESS') {
                            if (index === 0) {
                                cell.rowSpan = group.length;
                                cell.appendChild(processChips(group[0]));
                            } else {
                                tr.deleteCell(tr.cells.length - 1);
                            }
                            return;
                        }
                        var value = column[0] === 'CATEGORY'
                            ? categoryLabel(row)
                            : row[column[0]];
                        cell.textContent = quantityFields.indexOf(column[0]) >= 0
                            ? formatQty(value)
                            : (value === null || value === undefined ? '' : value);
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
            var printReport = document.getElementById('batchPrintReport');
            printReport.replaceChildren();
            printReport.setAttribute('aria-hidden', 'true');
        });

        setHeaders();
        renderReport(reportRows);
    </script>
</body>
</html>
