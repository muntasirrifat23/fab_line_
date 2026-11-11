<?php
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$userId = strtolower(trim((string) $_SESSION['username']));
$userType = strtolower(trim((string) ($_SESSION['user_type'] ?? '')));
if ($userId !== 'f01' && $userType !== 'finishing_operator') {
    http_response_code(403);
    exit('You are not authorized to view this report.');
}

$columns = [
    'BCARD' => 'Batch Card',
    'ROLL' => 'Roll',
    'DCOUNT' => 'DCount',
    'PO_NUMBER' => 'PO Number',
    'COLOR' => 'Color',
    'QTY' => 'Qty',
    'MCNO' => 'MC No',
    'SONO' => 'SO No',
    'BUYER' => 'Buyer',
    'STYLE' => 'Style',
    'MCDIA' => 'MC Dia',
    'CUSTOMER' => 'Customer',
    'O_T' => 'Open / Tube',
    'SL' => 'SL / VQ',
    'FTYPE' => 'Fabric Type',
    'FGSM' => 'Finish GSM',
    'FDIA' => 'Finish Dia',
    'YTYPE' => 'Yarn Type',
    'YCOUNT' => 'Yarn Count',
    'YBRAND' => 'Yarn Brand',
    'SHIFT' => 'Shift',
    'DYSTART' => 'Start Time',
    'DYEND' => 'End Time',
    'OPERATOR' => 'Operator',
    'OP_ID' => 'Operator ID'
];

$processFields = dyeingProcessFieldNames();
foreach ($processFields as $field) {
    $columns[$field] = $field;
}

$selectFields = [
    'u.BCARD',
    'u.ROLL',
    'u.DCOUNT',
    'u.PO_NUMBER',
    'u.COLOR',
    'u.QTY',
    'u.MCNO',
    'u.SONO',
    'u.BUYER',
    'u.STYLE',
    'u.MCDIA',
    'u.CUSTOMER',
    'u.O_T',
    'u.SL',
    'u.FTYPE',
    'u.FGSM',
    'u.FDIA',
    'u.YTYPE',
    'u.YCOUNT',
    'u.YBRAND',
    'u.SHIFT',
    'u.DYSTART',
    'u.DYEND',
    'u.DOP AS OPERATOR',
    'u.DID AS OP_ID'
];
foreach ($processFields as $field) {
    $selectFields[] = 'u.`' . $field . '`';
}

$rows = [];
$loadError = false;
try {
    ensureDyeingProcessColumns($db, 'dyeing_unload');
    ensureDyeingCountColumn($db, 'dyeing_unload');
    ensureDyeingCountColumn($db, 'dyeing_batch_verification');

    $sql = 'SELECT ' . implode(', ', $selectFields) . '
            FROM dyeing_unload u
            WHERE u.DCOUNT = (
                SELECT MAX(latest.DCOUNT)
                FROM dyeing_unload latest
                WHERE TRIM(latest.BCARD) = TRIM(u.BCARD)
            )
              AND NOT EXISTS (
                SELECT 1
                FROM dyeing_batch_verification redye_started
                WHERE TRIM(redye_started.BCARD) = TRIM(u.BCARD)
                  AND redye_started.DCOUNT > u.DCOUNT
            )
            ORDER BY u.DULTID DESC';
    $result = $db->query($sql);
    if (!$result) {
        throw new RuntimeException('Could not load Finished Production report: ' . $db->error);
    }
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
} catch (Throwable $error) {
    error_log('Finished Production report failed: ' . $error->getMessage());
    http_response_code(500);
    $loadError = true;
}

function finishedProductionReportEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finished Production Report</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #f1f5f9; color: #1e293b; font-family: 'Segoe UI', Arial, sans-serif; }
        header { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: linear-gradient(115deg, #1d4ed8, #3b82f6); color: #fff; }
        h1 { flex: 1; margin: 0; text-align: center; font-size: 1.4rem; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 8px 13px; border: 0; border-radius: 8px; background: #111827; color: #fff; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .search, .table-wrap { margin-bottom: 14px; padding: 12px; overflow: auto; border: 1px solid #dbe3ee; border-radius: 10px; background: #fff; }
        .search input { width: min(100%, 520px); min-height: 40px; padding: 8px 11px; border: 1px solid #cbd5e1; border-radius: 7px; font: inherit; }
        table { width: 100%; min-width: 1800px; border-collapse: collapse; font-size: .88rem; }
        th, td { padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap; }
        th { position: sticky; top: 0; background: #334155; color: #fff; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .message { padding: 20px; color: #64748b; text-align: center; font-weight: 700; }
        @media print { body { padding: 0; } header .button, .search { display: none; } .table-wrap { overflow: visible; border: 0; } }
    </style>
</head>
<body>
    <header>
        <a class="button" href="report.php">Back to Reports</a>
        <h1>Finished Production Report</h1>
        <button class="button" type="button" onclick="window.print()">Print</button>
    </header>
    <section class="search">
        <input id="reportSearch" type="search" placeholder="Search finished production rolls">
    </section>
    <section class="table-wrap">
        <?php if ($loadError): ?>
            <div class="message">Could not load the Finished Production report. Please try again later.</div>
        <?php elseif (!$rows): ?>
            <div class="message">No Finished Production rolls found.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($columns as $label): ?>
                            <th><?php echo finishedProductionReportEscape($label); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody id="reportBody">
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($columns as $field => $label): ?>
                                <td><?php echo finishedProductionReportEscape($row[$field] ?? ''); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="message" id="noSearchResults" hidden>No matching rolls found.</div>
        <?php endif; ?>
    </section>
    <script>
        const reportSearch = document.getElementById('reportSearch');
        if (reportSearch) {
            reportSearch.addEventListener('input', function () {
                const query = this.value.trim().toLowerCase();
                const rows = Array.from(document.querySelectorAll('#reportBody tr'));
                let visibleRows = 0;
                rows.forEach(function (row) {
                    const matches = row.textContent.toLowerCase().includes(query);
                    row.hidden = !matches;
                    if (matches) visibleRows++;
                });
                const noResults = document.getElementById('noSearchResults');
                if (noResults) noResults.hidden = visibleRows !== 0;
            });
        }
    </script>
</body>
</html>
