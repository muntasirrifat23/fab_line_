<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$filters = [
    'batch' => isset($_GET['batch']) && is_scalar($_GET['batch']) ? trim((string)$_GET['batch']) : '',
    'po_number' => isset($_GET['po_number']) && is_scalar($_GET['po_number']) ? trim((string)$_GET['po_number']) : '',
    'color' => isset($_GET['color']) && is_scalar($_GET['color']) ? trim((string)$_GET['color']) : '',
    'mc_no' => isset($_GET['mc_no']) && is_scalar($_GET['mc_no']) ? trim((string)$_GET['mc_no']) : ''
];
$searched = isset($_GET['search']);
$rows = [];
$errorMessage = '';

if ($searched) {
    $conditions = [];
    $values = [];
    $types = '';

    foreach ([
        'batch' => 'BCARD',
        'po_number' => 'PO_NUMBER',
        'color' => 'COLOR',
        'mc_no' => 'DYMCNO'
    ] as $filter => $column) {
        if ($filters[$filter] !== '') {
            $conditions[] = "`$column` LIKE ?";
            $values[] = '%' . $filters[$filter] . '%';
            $types .= 's';
        }
    }

    if (!$conditions) {
        $errorMessage = 'Enter at least one search value.';
    } else {
        $sql = "SELECT BCARD, PO_NUMBER, COLOR, QTY, SONO, BUYER, STYLE, MCDIA, CUSTOMER,
                       O_T, SL, FTYPE, FGSM, FDIA, YTYPE, YCOUNT, YBRAND, FEEDER_PLAN,
                       LOT_NO, CATEGORY
                FROM dyeing_batch_delivery
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY DBDTID DESC
                LIMIT 500";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            $errorMessage = 'Could not prepare batch verification search: ' . $db->error;
        } else {
            $bindParams = [$types];
            foreach ($values as &$value) {
                $bindParams[] = &$value;
            }
            unset($value);

            if (!call_user_func_array([$stmt, 'bind_param'], $bindParams)) {
                $errorMessage = 'Could not bind batch verification search filters: ' . $stmt->error;
            } elseif (!$stmt->execute()) {
                $errorMessage = 'Batch verification search failed: ' . $stmt->error;
            } else {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            $stmt->close();
        }
    }
}

function h($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FabLine | Batch Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            color: #123b39;
            background: linear-gradient(135deg, #d9f1ed, #e5f1fb);
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .page { width: 100%; margin: 0 auto; }
        .header, .panel {
            background: #fff;
            border: 1px solid #c9dedb;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 70, 65, .1);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            margin-bottom: 14px;
        }
        h1 { margin: 0; font-size: 1.5rem; }
        .back, .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 9px 14px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            background: #087f70;
            font-weight: 700;
            font-size: .95rem;
            text-decoration: none;
            cursor: pointer;
        }
        .back { background: #111827; }
        .panel { padding: 18px; margin-bottom: 14px; }
        .filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr)) auto auto;
            align-items: end;
            gap: 12px;
        }
        label { display: block; margin-bottom: 6px; font-weight: 700; }
        input {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #afc8c4;
            border-radius: 8px;
            font: inherit;
        }
        input:focus { outline: 2px solid #54b7a8; border-color: #159685; }
        .clear { color: #334155; background: #e2e8f0; }
        .status { margin: 14px 0 0; font-weight: 700; }
        .error { color: #991b1b; }
        .table-wrap { width: 100%; overflow: auto; max-height: calc(100vh - 270px); }
        table { width: 100%; min-width: 1750px; border-collapse: collapse; font-size: .88rem; }
        th, td { padding: 10px 9px; border-bottom: 1px solid #dbe5e4; text-align: left; white-space: nowrap; }
        thead th { position: sticky; top: 0; z-index: 1; color: #fff; background: #134e4a; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .empty { padding: 24px; text-align: center; color: #64748b; }
        .verification-tabs { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .verification-tab { min-width: 120px; background: #e2e8f0; color: #334155; }
        .verification-tab[data-status="verification"] { background: #dbeafe; color: #1d4ed8; }
        .verification-tab[data-status="pass"] { background: #bbf7d0; color: #065f46; }
        .verification-tab[data-status="fail"] { background: #fee2e2; color: #991b1b; }
        .verification-tab[aria-pressed="true"][data-status="verification"] { background: #2563eb; color: #fff; }
        .verification-tab[aria-pressed="true"][data-status="pass"] { background: #047857; color: #fff; }
        .verification-tab[aria-pressed="true"][data-status="fail"] { background: #dc2626; color: #fff; }
        .move-to-pass { min-height: 34px; padding: 6px 10px; background: #047857; }
        .move-to-pass[hidden] { display: none; }
        .approval-panel { margin-top: 16px; padding-top: 16px; border-top: 1px solid #dbe5e4; }
        .approval-title { margin: 0 0 12px; color: #134e4a; font-size: 1rem; }
        .approvals { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 12px; }
        .approval-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px;
            border: 1px solid #c9dedb;
            border-radius: 10px;
            background: #f8fafc;
            font-weight: 700;
        }
        .approval-options { display: flex; gap: 6px; }
        .approval-options .button { min-width: 68px; padding: 8px 10px; }
        .approval-choice[data-decision="pass"] { color: #065f46; background: #bbf7d0; }
        .approval-choice[data-decision="pass"][aria-pressed="true"] { color: #fff; background: #047857; }
        .approval-choice[data-decision="fail"][aria-pressed="true"] { background: #dc2626; }
        .approval-choice[data-decision="fail"] { background: #64748b; }
        .approval-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 14px; }
        .approval-count { margin: 0; font-weight: 700; }
        .button:disabled { background: #94a3b8; cursor: not-allowed; }
        .verification-result { margin: 12px 0 0; color: #065f46; font-weight: 700; }
        @media (max-width: 1000px) {
            .filters { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
            .approvals { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
        }
        @media (max-width: 600px) {
            body { padding: 8px; }
            .header { flex-wrap: wrap; padding: 12px; }
            h1 { font-size: 1.25rem; }
            .filters { grid-template-columns: 1fr; }
            .approvals { grid-template-columns: 1fr; }
            .approval-footer { align-items: stretch; flex-direction: column; }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <a class="back" href="initialPage.php"><i class="fas fa-arrow-left"></i> Back</a>
            <h1><i class="fas fa-clipboard-check"></i> Batch Verification</h1>
        </header>

        <nav class="verification-tabs" aria-label="Verification status">
            <button class="button verification-tab" type="button" data-status="verification" aria-pressed="true"<?php echo (!$searched || $errorMessage !== '' || !$rows) ? ' disabled' : ''; ?>>
                Verification
            </button>
            <button class="button verification-tab" type="button" data-status="pass" aria-pressed="false"<?php echo (!$searched || $errorMessage !== '' || !$rows) ? ' disabled' : ''; ?>>
                Pass
            </button>
            <button class="button verification-tab" type="button" data-status="fail" aria-pressed="false"<?php echo (!$searched || $errorMessage !== '' || !$rows) ? ' disabled' : ''; ?>>
                Fail
            </button>
        </nav>

        <section class="panel">
            <form method="get" class="filters">
                <div>
                    <label for="batch">Batch Card</label>
                    <input type="text" id="batch" name="batch" value="<?php echo h($filters['batch']); ?>" placeholder="Search batch card">
                </div>
                <div>
                    <label for="po_number">PO Number</label>
                    <input type="text" id="po_number" name="po_number" value="<?php echo h($filters['po_number']); ?>" placeholder="Search PO number">
                </div>
                <div>
                    <label for="color">Color</label>
                    <input type="text" id="color" name="color" value="<?php echo h($filters['color']); ?>" placeholder="Search color">
                </div>
                <div>
                    <label for="mc_no">MC/NO</label>
                    <input type="text" id="mc_no" name="mc_no" value="<?php echo h($filters['mc_no']); ?>" placeholder="Search MC/NO">
                </div>
                <button class="button" type="submit" name="search" value="1"><i class="fas fa-search"></i> Search</button>
                <a class="button clear" href="dyeing_batch_verification.php">Clear</a>
            </form>
            <?php if ($errorMessage !== ''): ?>
                <p class="status error"><?php echo h($errorMessage); ?></p>
            <?php elseif ($searched): ?>
                <p class="status"><?php echo count($rows); ?> record(s) found<?php echo count($rows) === 500 ? ' (showing the latest 500 matches)' : ''; ?>.</p>
            <?php else: ?>
                <p class="status">Search by Batch Card, PO Number, Color, or MC/NO to view delivery records.</p>
            <?php endif; ?>
        </section>

        <?php if ($searched && $errorMessage === ''): ?>
            <section class="panel">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Batch</th><th>PO Number</th><th>Color</th><th>QTY</th><th>SONO</th>
                                <th>Buyer</th><th>Style</th><th>MC Dia</th><th>Customer</th><th>Open / Tube</th>
                                <th>SL/VQ</th><th>Fabrics Type</th><th>Finish GSM</th><th>Finish Dia</th>
                                <th>Yarn Type</th><th>Yarn Count</th><th>Yarn Brand</th><th>Feeder Plan</th>
                                <th>Lot No</th><th>Category</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows): ?>
                                <tr><td class="empty" colspan="21">No matching delivery records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr class="delivery-row" data-batch="<?php echo h($row['BCARD']); ?>">
                                        <td><?php echo h($row['BCARD']); ?></td>
                                        <td><?php echo h($row['PO_NUMBER']); ?></td>
                                        <td><?php echo h($row['COLOR']); ?></td>
                                        <td><?php echo h($row['QTY']); ?></td>
                                        <td><?php echo h($row['SONO']); ?></td>
                                        <td><?php echo h($row['BUYER']); ?></td>
                                        <td><?php echo h($row['STYLE']); ?></td>
                                        <td><?php echo h($row['MCDIA']); ?></td>
                                        <td><?php echo h($row['CUSTOMER']); ?></td>
                                        <td><?php echo h($row['O_T']); ?></td>
                                        <td><?php echo h($row['SL']); ?></td>
                                        <td><?php echo h($row['FTYPE']); ?></td>
                                        <td><?php echo h($row['FGSM']); ?></td>
                                        <td><?php echo h($row['FDIA']); ?></td>
                                        <td><?php echo h($row['YTYPE']); ?></td>
                                        <td><?php echo h($row['YCOUNT']); ?></td>
                                        <td><?php echo h($row['YBRAND']); ?></td>
                                        <td><?php echo h($row['FEEDER_PLAN']); ?></td>
                                        <td><?php echo h($row['LOT_NO']); ?></td>
                                        <td><?php echo h($row['CATEGORY']); ?></td>
                                        <td><button class="button move-to-pass" type="button" hidden>Move to Pass</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($rows): ?>
                                <tr id="emptyTabRow" hidden>
                                    <td class="empty" colspan="21">There are no batches in this status.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($rows): ?>
                    <div class="approval-panel" id="approvalPanel">
                        <h2 class="approval-title">Batch Approval</h2>
                        <div class="approvals">
                            <?php foreach (['Shift Incharge', 'Supervisor', 'Batch Incharge', 'Batch Manager'] as $index => $role): ?>
                                <div class="approval-item">
                                    <span><?php echo h($role); ?></span>
                                    <div class="approval-options">
                                        <button class="button approval-choice" type="button" data-approval="<?php echo $index; ?>" data-decision="pass" aria-pressed="false">PASS</button>
                                        <button class="button approval-choice" type="button" data-approval="<?php echo $index; ?>" data-decision="fail" aria-pressed="false">FAIL</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="approval-footer">
                            <p class="approval-count" id="approvalCount" aria-live="polite">0 PASS, 0 FAIL. At least 2 matching decisions with no tie are required.</p>
                            <button class="button" type="button" id="verificationDoneButton" disabled>Verification Done</button>
                        </div>
                        <p class="verification-result" id="verificationResult" role="status" hidden></p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php if ($searched && $errorMessage === '' && $rows): ?>
        <script>
            (function() {
                var storageKey = 'fablineBatchVerificationStatus';
                var approvalButtons = Array.from(document.querySelectorAll('.approval-choice'));
                var approvalCount = document.getElementById('approvalCount');
                var doneButton = document.getElementById('verificationDoneButton');
                var result = document.getElementById('verificationResult');
                var tabs = Array.from(document.querySelectorAll('.verification-tab'));
                var rows = Array.from(document.querySelectorAll('.delivery-row'));
                var emptyTabRow = document.getElementById('emptyTabRow');
                var approvalPanel = document.getElementById('approvalPanel');
                var decisions = {};
                var completed = false;
                var activeStatus = 'verification';
                var statuses;

                try {
                    statuses = JSON.parse(window.localStorage.getItem(storageKey) || '{}');
                    if (!statuses || typeof statuses !== 'object' || Array.isArray(statuses)) {
                        statuses = {};
                    }
                    Object.keys(statuses).forEach(function(batchId) {
                        if (statuses[batchId] !== 'verification' &&
                            statuses[batchId] !== 'pass' &&
                            statuses[batchId] !== 'fail') {
                            delete statuses[batchId];
                        }
                    });
                } catch (error) {
                    statuses = {};
                    result.hidden = false;
                    result.textContent = 'Could not read saved verification statuses: ' + error.message;
                }

                var batchIds = Array.from(new Set(rows.map(function(row) {
                    return row.dataset.batch;
                })));

                function saveStatuses() {
                    try {
                        window.localStorage.setItem(storageKey, JSON.stringify(statuses));
                        return true;
                    } catch (error) {
                        result.hidden = false;
                        result.textContent = 'Could not save verification status in this browser: ' + error.message;
                        return false;
                    }
                }

                function updateTabs() {
                    var counts = {verification: 0, pass: 0, fail: 0};
                    batchIds.forEach(function(batchId) {
                        var status = statuses[batchId] || 'verification';
                        if (Object.prototype.hasOwnProperty.call(counts, status)) counts[status]++;
                    });
                    var failActionAdded = {};
                    rows.forEach(function(row) {
                        var status = statuses[row.dataset.batch] || 'verification';
                        var isVisible = status === activeStatus;
                        row.hidden = !isVisible;
                        var moveButton = row.querySelector('.move-to-pass');
                        var hasFailAction = Boolean(failActionAdded[row.dataset.batch]);
                        moveButton.hidden = !isVisible || status !== 'fail' || hasFailAction;
                        if (isVisible && status === 'fail') {
                            failActionAdded[row.dataset.batch] = true;
                        }
                    });
                    emptyTabRow.hidden = rows.some(function(row) {
                        return !row.hidden;
                    });
                    tabs.forEach(function(tab) {
                        tab.setAttribute('aria-pressed', tab.dataset.status === activeStatus ? 'true' : 'false');
                    });
                    approvalPanel.hidden = activeStatus !== 'verification' || counts.verification === 0;
                }

                function updateApprovals() {
                    var decisionValues = Object.keys(decisions).map(function(role) {
                        return decisions[role];
                    });
                    var passed = decisionValues.filter(function(decision) {
                        return decision === 'pass';
                    }).length;
                    var failed = decisionValues.filter(function(decision) {
                        return decision === 'fail';
                    }).length;
                    approvalCount.textContent = passed + ' PASS, ' + failed +
                        ' FAIL. At least 2 matching decisions with no tie are required.';
                    doneButton.disabled = completed ||
                        !((passed >= 2 && passed > failed) || (failed >= 2 && failed > passed));
                }

                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function() {
                        activeStatus = this.dataset.status;
                        result.hidden = true;
                        updateTabs();
                    });
                });

                approvalButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        if (completed) return;
                        var role = this.dataset.approval;
                        var decision = this.dataset.decision;
                        decisions[role] = decision;
                        approvalButtons.forEach(function(roleButton) {
                            if (roleButton.dataset.approval === role) {
                                roleButton.setAttribute(
                                    'aria-pressed',
                                    roleButton.dataset.decision === decision ? 'true' : 'false'
                                );
                            }
                        });
                        result.hidden = true;
                        updateApprovals();
                    });
                });

                doneButton.addEventListener('click', function() {
                    if (this.disabled) return;
                    var passed = Object.keys(decisions).filter(function(role) {
                        return decisions[role] === 'pass';
                    }).length;
                    var failed = Object.keys(decisions).filter(function(role) {
                        return decisions[role] === 'fail';
                    }).length;
                    var finalStatus = passed > failed ? 'pass' : 'fail';
                    batchIds.forEach(function(batchId) {
                        if (!statuses[batchId] || statuses[batchId] === 'verification') {
                            statuses[batchId] = finalStatus;
                        }
                    });
                    if (!saveStatuses()) return;
                    completed = true;
                    approvalButtons.forEach(function(button) {
                        button.disabled = true;
                    });
                    this.textContent = 'Verification ' + finalStatus.toUpperCase();
                    activeStatus = finalStatus;
                    result.textContent = 'Verification ' + finalStatus.toUpperCase() +
                        ': displayed batch cards have been moved to the ' + finalStatus.toUpperCase() + ' tab.';
                    result.hidden = false;
                    updateApprovals();
                    updateTabs();
                });

                document.querySelectorAll('.move-to-pass').forEach(function(button) {
                    button.addEventListener('click', function() {
                        var row = this.closest('.delivery-row');
                        if (!row) return;
                        statuses[row.dataset.batch] = 'pass';
                        if (!saveStatuses()) return;
                        result.hidden = false;
                        result.textContent = 'Batch Card ' + row.dataset.batch + ' moved from FAIL to PASS.';
                        updateTabs();
                    });
                });

                updateApprovals();
                updateTabs();
            })();
        </script>
    <?php endif; ?>
</body>
</html>
