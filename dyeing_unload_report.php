<?php
$isRedyeReport = isset($isRedyeReport) && $isRedyeReport === true;
$reportConfig = [
    'title' => $isRedyeReport ? 'Re Dyeing' : 'Dyeing Unload Report',
    'from' => 'dyeing_unload t',
    'machine' => "COALESCE(t.MCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'time_columns' => ['START_TIME', 'END_TIME'],
    'order' => 't.BCARD, t.DULTID',
    'theme_start' => '#9f1239',
    'theme_end' => '#e11d48'
];
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
if ($isRedyeReport && empty($_SESSION['dyeing_redye_csrf'])) {
    $_SESSION['dyeing_redye_csrf'] = bin2hex(random_bytes(32));
}
$redyeCsrfToken = $isRedyeReport ? $_SESSION['dyeing_redye_csrf'] : '';

$reportTitle = $reportConfig['title'];
$reportFrom = $reportConfig['from'];
$reportMachine = $reportConfig['machine'];
$reportOperator = $reportConfig['operator'];
$reportOperatorId = $reportConfig['operator_id'];
$reportProcessAlias = $reportConfig['process_alias'];
$reportOrder = $reportConfig['order'];
$reportWhere = isset($reportConfig['where']) ? $reportConfig['where'] : '';
if ($isRedyeReport) {
    $reportWhere = "t.DCOUNT = (
        SELECT MAX(latest_unload.DCOUNT)
        FROM dyeing_unload latest_unload
        WHERE TRIM(latest_unload.BCARD) = TRIM(t.BCARD)
    ) AND t.DCOUNT = (
        SELECT COALESCE(MAX(verified.DCOUNT), 0)
        FROM dyeing_batch_verification verified
        WHERE TRIM(verified.BCARD) = TRIM(t.BCARD)
    )";
} else {
    $reportWhere = "NOT EXISTS (
        SELECT 1
        FROM dyeing_batch_verification redye_started
        WHERE TRIM(redye_started.BCARD) = TRIM(t.BCARD)
          AND redye_started.DCOUNT > t.DCOUNT
    )";
}
$reportThemeStart = $reportConfig['theme_start'];
$reportThemeEnd = $reportConfig['theme_end'];
$reportTimeColumns = isset($reportConfig['time_columns']) ? $reportConfig['time_columns'] : [];
$reportColumnCount = 25 + count($reportTimeColumns);
$processFields = dyeingProcessFieldNames();

$processSelect = [];
foreach ($processFields as $field) {
    $processSelect[] = "COALESCE({$reportProcessAlias}.`{$field}`, '') AS `{$field}`";
}

$timeSelect = ["COALESCE(t.SHIFT, '') AS SHIFT"];
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

$rows = [];
$loadError = false;
try {
    ensureDyeingCountColumn($db, 'dyeing_unload');
    if ($isRedyeReport) {
        ensureDyeingCountColumn($db, 'dyeing_batch_verification');
    }
} catch (Throwable $error) {
    error_log('Could not initialize Dyeing report count fields: ' . $error->getMessage());
    http_response_code(500);
    $loadError = true;
}
$sql = "SELECT
            t.BCARD,
            t.ROLL,
            t.DCOUNT,
            t.PO_NUMBER,
            t.COLOR,
            t.QTY,
            {$reportMachine} AS MCNO,
            t.SONO,
            t.BUYER,
            t.STYLE,
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
            " . implode(",\n            ", $timeSelect) . ",
            {$reportOperator} AS OPERATOR,
            {$reportOperatorId} AS OP_ID,
            " . implode(",\n            ", $processSelect) . "
        FROM {$reportFrom}";
if ($reportWhere !== '') {
    $sql .= ' WHERE ' . $reportWhere;
}
$sql .= ' ORDER BY ' . $reportOrder;

if (!$loadError) {
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
        .btn-redye { padding: 6px 10px; background: #7c3aed; }
        .btn-redye:disabled { opacity: .6; cursor: wait; }
        .table-scroll { overflow: auto; max-height: calc(100vh - 190px); }
        table { width: 100%; min-width: 1850px; border-collapse: separate; border-spacing: 0; font-size: .9rem; }
        thead th { position: sticky; top: 0; z-index: 3; padding: 11px 9px; border-bottom: 3px solid <?php echo dyeingReportEscape($reportThemeEnd); ?>; background: #334155; color: #fff; text-align: left; white-space: nowrap; }
        tbody td { padding: 9px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; vertical-align: middle; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eff6ff; }
        .process-list { display: grid; grid-template-columns: repeat(2, max-content); gap: 4px; }
        .process-chip { padding: 3px 7px; border: 1px solid #99d5c9; border-radius: 6px; background: #e6f8f3; color: #075e54; font-size: .78rem; font-weight: 700; }
        .empty-row td, .error-row td { padding: 28px; color: #64748b; text-align: center; font-weight: 700; }
        .error-row td { color: #b91c1c; }
        .confirm-modal { position: fixed; inset: 0; z-index: 20; display: none; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .48); }
        .confirm-modal.show { display: flex; }
        .confirm-card { position: relative; width: min(100%, 420px); padding: 30px 28px 24px; border-radius: 12px; background: #fff; box-shadow: 0 18px 48px rgba(15, 23, 42, .25); text-align: center; }
        .confirm-icon { display: flex; width: 66px; height: 66px; align-items: center; justify-content: center; margin: 0 auto 16px; border: 2px solid #d97706; border-radius: 50%; color: #d97706; font-size: 1.8rem; }
        .confirm-title { margin: 0 0 10px; color: #1f2937; font-size: 1.3rem; }
        .confirm-text { margin: 0; color: #64748b; line-height: 1.5; }
        .confirm-actions { display: flex; justify-content: center; gap: 10px; margin-top: 24px; }
        .btn-confirm-yes { min-width: 90px; background: #16a34a; }
        .btn-confirm-cancel { min-width: 90px; background: #64748b; }
        @media (max-width: 700px) {
            body { padding: 10px; }
            .report-header { flex-wrap: wrap; gap: 8px; }
            .report-header h1 { order: -1; flex-basis: 100%; font-size: 1.35rem; }
            .record-count { flex: 1; text-align: right; }
            .search-row { flex-wrap: wrap; }
            .search-row input { flex-basis: 100%; }
        }
        @media print {
            @page { size: landscape; margin: 10mm; }
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            .table-card, .table-scroll { overflow: visible !important; max-height: none !important; }
            table { width: 100%; min-width: 0; font-size: 8pt; }
            thead th { position: static; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody td { padding: 5px; white-space: normal; }
            .process-list { display: flex; flex-wrap: wrap; }
            .report-table tbody tr:not(.print-target) { display: none !important; }
            .report-table .action-column { display: none !important; }
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
    <div class="confirm-modal" id="redyeConfirmModal" role="dialog" aria-modal="true" aria-labelledby="redyeConfirmTitle" aria-describedby="redyeConfirmText" aria-hidden="true">
        <div class="confirm-card">
            <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div>
            <h2 class="confirm-title" id="redyeConfirmTitle">Confirm Re Dyeing</h2>
            <p class="confirm-text" id="redyeConfirmText"></p>
            <div class="confirm-actions">
                <button class="btn btn-confirm-yes" id="confirmRedyeYes" type="button">Yes</button>
                <button class="btn btn-confirm-cancel" id="confirmRedyeCancel" type="button">Cancel</button>
            </div>
        </div>
    </div>
    <script>
        var reportRows = <?php echo json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
        var isRedyeReport = <?php echo $isRedyeReport ? 'true' : 'false'; ?>;
        var redyeCsrfToken = <?php echo json_encode($redyeCsrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var redyeConfirmResolver = null;
        var reportColumns = [
            ['ACTION', 'ACTION'],
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
            ['SHIFT', 'Shift'],
            <?php foreach ($reportTimeColumns as $timeColumn): ?>
            [<?php echo json_encode($timeColumn); ?>, <?php echo json_encode($timeColumnLabels[$timeColumn]); ?>],
            <?php endforeach; ?>
            ['DCOUNT', 'DCount'],
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

        function printBatch(batch) {
            document.querySelectorAll('.report-table tbody tr').forEach(function(row) {
                row.classList.toggle('print-target', row.dataset.group === batch);
            });
            window.print();
        }

        function confirmRedyeing(batch) {
            var modal = document.getElementById('redyeConfirmModal');
            document.getElementById('redyeConfirmText').textContent =
                'Send Batch ' + batch + ' back to Batch Verification for re-dyeing?';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.getElementById('confirmRedyeYes').focus();
            return new Promise(function(resolve) {
                redyeConfirmResolver = resolve;
            });
        }

        function closeRedyeConfirmation(confirmed) {
            if (!redyeConfirmResolver) return;
            var resolve = redyeConfirmResolver;
            redyeConfirmResolver = null;
            var modal = document.getElementById('redyeConfirmModal');
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            resolve(confirmed);
        }

        function startRedyeing(batch, button) {
            confirmRedyeing(batch).then(function(confirmed) {
                if (!confirmed) return;
                button.disabled = true;
                fetch('ajaxDyeing_redye.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ card_no: batch, csrf_token: redyeCsrfToken })
                })
                    .then(function(response) {
                        return response.json().then(function(data) {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Could not start re-dyeing.');
                            }
                            window.location.reload();
                        });
                    })
                    .catch(function(error) {
                        button.disabled = false;
                        window.alert(error.message);
                    });
            });
        }

        document.getElementById('confirmRedyeYes').addEventListener('click', function() {
            closeRedyeConfirmation(true);
        });
        document.getElementById('confirmRedyeCancel').addEventListener('click', function() {
            closeRedyeConfirmation(false);
        });
        document.getElementById('redyeConfirmModal').addEventListener('click', function(event) {
            if (event.target === this) closeRedyeConfirmation(false);
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeRedyeConfirmation(false);
        });

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
                var batch = String(row.BCARD || '');
                var key = JSON.stringify([batch, String(row.DCOUNT || '1')]);
                if (!groups.has(key)) groups.set(key, []);
                groups.get(key).push(row);
            });

            groups.forEach(function(group, groupKey) {
                group.forEach(function(row, index) {
                    var tr = body.insertRow();
                    tr.dataset.batch = String(group[0].BCARD || '');
                    tr.dataset.group = groupKey;
                    if (index === 0) {
                        var actionCell = tr.insertCell();
                        actionCell.rowSpan = group.length;
                        actionCell.className = 'action-column';
                        var actionButton = document.createElement('button');
                        actionButton.type = 'button';
                        actionButton.className = 'btn no-print ' + (isRedyeReport ? 'btn-redye' : 'btn-print');
                        actionButton.innerHTML = isRedyeReport
                            ? '<i class="fa-solid fa-rotate"></i> Re Dyeing'
                            : '<i class="fa-solid fa-print"></i> Print';
                        actionButton.addEventListener('click', function() {
                            if (isRedyeReport) {
                                startRedyeing(String(group[0].BCARD || ''), actionButton);
                            } else {
                                printBatch(groupKey);
                            }
                        });
                        actionCell.appendChild(actionButton);

                        var batchCell = tr.insertCell();
                        batchCell.rowSpan = group.length;
                        batchCell.textContent = String(group[0].BCARD || '');
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
            document.querySelectorAll('.print-target').forEach(function(row) {
                row.classList.remove('print-target');
            });
        });

        setHeaders();
        renderReport(reportRows);
    </script>
</body>
</html>
