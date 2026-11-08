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

{
    $conditions = [];
    $values = [];
    $types = '';
    $verificationTableResult = $db->query(
        "SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = 'dyeing_batch_verification'
         LIMIT 1"
    );
    if (!$verificationTableResult) {
        $errorMessage = 'Could not check verification records: ' . $db->error;
    } else {
        $hasVerificationTable = $verificationTableResult->num_rows > 0;
        $verificationTableResult->free();
    }

    if ($errorMessage === '' && $hasVerificationTable) {
        $columnsStmt = $db->prepare(
            "SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'dyeing_batch_verification'
               AND COLUMN_NAME IN ('MCNO', 'DYMCNO')"
        );
        if (!$columnsStmt || !$columnsStmt->execute()) {
            $columnError = $columnsStmt ? $columnsStmt->error : $db->error;
            if ($columnsStmt) {
                $columnsStmt->close();
            }
            $errorMessage = 'Could not inspect verification table columns: ' . $columnError;
        } else {
            $verificationColumns = [];
            $columnResult = $columnsStmt->get_result();
            while ($column = $columnResult->fetch_assoc()) {
                $verificationColumns[] = $column['COLUMN_NAME'];
            }
            $columnsStmt->close();

            if (in_array('MCNO', $verificationColumns, true) &&
                !in_array('DYMCNO', $verificationColumns, true)) {
                if (!$db->query(
                    'ALTER TABLE dyeing_batch_verification
                     CHANGE COLUMN MCNO DYMCNO VARCHAR(100) DEFAULT NULL'
                )) {
                    $errorMessage = 'Could not rename MCNO to DYMCNO: ' . $db->error;
                }
            } elseif (!$verificationColumns && !$db->query(
                'ALTER TABLE dyeing_batch_verification
                 ADD COLUMN DYMCNO VARCHAR(100) DEFAULT NULL'
            )) {
                $errorMessage = 'Could not add DYMCNO to verification table: ' . $db->error;
            }
        }
    }

    if ($errorMessage === '') {
        $filterExpressions = [
            'batch' => 'd.BCARD',
            'po_number' => $hasVerificationTable
                ? 'COALESCE(v.PO_NUMBER, d.PO_NUMBER)'
                : 'd.PO_NUMBER',
            'color' => $hasVerificationTable
                ? 'COALESCE(v.COLOR, d.COLOR)'
                : 'd.COLOR',
            'mc_no' => 'd.DYMCNO'
        ];
        foreach ($filterExpressions as $filter => $expression) {
            if ($filters[$filter] !== '') {
                $conditions[] = $expression . ' LIKE ?';
                $values[] = '%' . $filters[$filter] . '%';
                $types .= 's';
            }
        }

        if ($hasVerificationTable) {
            $sql = "SELECT d.ROLL, d.BCARD,
                               COALESCE(v.PO_NUMBER, d.PO_NUMBER) AS PO_NUMBER,
                               COALESCE(v.COLOR, d.COLOR) AS COLOR,
                               COALESCE(v.QTY, d.QTY) AS QTY,
                               COALESCE(v.DYMCNO, d.DYMCNO) AS DYMCNO,
                               COALESCE(v.SONO, d.SONO) AS SONO,
                               COALESCE(v.BUYER, d.BUYER) AS BUYER,
                               COALESCE(v.STYLE, d.STYLE) AS STYLE,
                               COALESCE(v.MCDIA, d.MCDIA) AS MCDIA,
                               COALESCE(v.CUSTOMER, d.CUSTOMER) AS CUSTOMER,
                               COALESCE(v.O_T, d.O_T) AS O_T,
                               COALESCE(v.SL, d.SL) AS SL,
                               COALESCE(v.FTYPE, d.FTYPE) AS FTYPE,
                               COALESCE(v.FGSM, d.FGSM) AS FGSM,
                               COALESCE(v.FDIA, d.FDIA) AS FDIA,
                               COALESCE(v.YTYPE, d.YTYPE) AS YTYPE,
                               COALESCE(v.YCOUNT, d.YCOUNT) AS YCOUNT,
                               COALESCE(v.YBRAND, d.YBRAND) AS YBRAND,
                               COALESCE(v.FEEDER_PLAN, d.FEEDER_PLAN) AS FEEDER_PLAN,
                               COALESCE(v.LOT_NO, d.LOT_NO) AS LOT_NO,
                               COALESCE(v.CATEGORY, d.CATEGORY) AS CATEGORY,
                               v.SINCHARGE, v.SUPERVISOR, v.BINCHARGE, v.BMANAGER,
                               CASE
                                   WHEN v.DBVTID IS NULL THEN 'verification'
                                   WHEN (
                                       COALESCE(v.SINCHARGE = 'PASS', 0) +
                                       COALESCE(v.SUPERVISOR = 'PASS', 0) +
                                       COALESCE(v.BINCHARGE = 'PASS', 0) +
                                       COALESCE(v.BMANAGER = 'PASS', 0)
                                   ) > (
                                       COALESCE(v.SINCHARGE = 'FAIL', 0) +
                                       COALESCE(v.SUPERVISOR = 'FAIL', 0) +
                                       COALESCE(v.BINCHARGE = 'FAIL', 0) +
                                       COALESCE(v.BMANAGER = 'FAIL', 0)
                                   ) THEN 'pass'
                                   WHEN (
                                       COALESCE(v.SINCHARGE = 'FAIL', 0) +
                                       COALESCE(v.SUPERVISOR = 'FAIL', 0) +
                                       COALESCE(v.BINCHARGE = 'FAIL', 0) +
                                       COALESCE(v.BMANAGER = 'FAIL', 0)
                                   ) > (
                                       COALESCE(v.SINCHARGE = 'PASS', 0) +
                                       COALESCE(v.SUPERVISOR = 'PASS', 0) +
                                       COALESCE(v.BINCHARGE = 'PASS', 0) +
                                       COALESCE(v.BMANAGER = 'PASS', 0)
                                   ) THEN 'fail'
                                   ELSE 'verification'
                               END AS VERIFICATION_STATUS
                        FROM dyeing_batch_delivery d
                        LEFT JOIN dyeing_batch_verification v
                            ON v.BCARD = d.BCARD AND v.ROLL = d.ROLL";
        } else {
            $sql = "SELECT d.ROLL, d.BCARD, d.PO_NUMBER, d.COLOR, d.QTY, d.DYMCNO, d.SONO,
                               d.BUYER, d.STYLE, d.MCDIA, d.CUSTOMER, d.O_T, d.SL,
                               d.FTYPE, d.FGSM, d.FDIA, d.YTYPE, d.YCOUNT, d.YBRAND,
                               d.FEEDER_PLAN, d.LOT_NO, d.CATEGORY,
                               'verification' AS VERIFICATION_STATUS
                        FROM dyeing_batch_delivery d";
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY d.BCARD ASC, VERIFICATION_STATUS ASC, d.DBDTID DESC";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            $errorMessage = 'Could not prepare batch verification search: ' . $db->error;
        } else {
            $bound = true;
            if ($values) {
                $bindParams = [$types];
                foreach ($values as &$value) {
                    $bindParams[] = &$value;
                }
                unset($value);
                $bound = call_user_func_array([$stmt, 'bind_param'], $bindParams);
            }

            if (!$bound) {
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .button[hidden] { display: none; }
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
        tbody tr.delivery-row[data-status="pass"] { background: #f0fdf4; }
        tbody tr.delivery-row[data-status="fail"] { background: #fef2f2; }
        tbody tr.delivery-row[data-status="pass"]:nth-child(even) { background: #dcfce7; }
        tbody tr.delivery-row[data-status="fail"]:nth-child(even) { background: #fee2e2; }
        td[data-action-cell] { text-align: center; vertical-align: middle; }
        .empty { padding: 24px; text-align: center; color: #64748b; }
        .verification-tabs { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .verification-tab { min-width: 120px; background: #e2e8f0; color: #334155; }
        .verification-tab[data-status="verification"] { background: #dbeafe; color: #1d4ed8; }
        .verification-tab[data-status="pass"] { background: #bbf7d0; color: #065f46; }
        .verification-tab[data-status="fail"] { background: #fee2e2; color: #991b1b; }
        .verification-tab[aria-pressed="true"][data-status="verification"] { background: #2563eb; color: #fff; }
        .verification-tab[aria-pressed="true"][data-status="pass"] { background: #047857; color: #fff; }
        .verification-tab[aria-pressed="true"][data-status="fail"] { background: #dc2626; color: #fff; }
        .edit-verification { min-height: 34px; padding: 6px 10px; background: #2563eb; }
        .decision-editor { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 12px; }
        .decision-edit-row {
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
        .decision-edit-options { display: flex; gap: 6px; }
        .decision-edit-options .button { min-height: 38px; min-width: 60px; padding: 8px 10px; }
        .decision-edit-options [data-decision="pass"] { color: #065f46; background: #bbf7d0; }
        .decision-edit-options [data-decision="pass"][aria-pressed="true"] { color: #fff; background: #047857; }
        .decision-edit-options [data-decision="fail"] { background: #cbd5e1; color: #334155; }
        .decision-edit-options [data-decision="fail"][aria-pressed="true"] { background: #dc2626; color: #fff; }
        .decision-edit-actions { display: flex; grid-column: 1 / -1; gap: 6px; }
        .save-decisions { min-height: 32px; padding: 5px 8px; background: #047857; }
        .cancel-edit { min-height: 32px; padding: 5px 8px; background: #64748b; }
        .password-dialog { width: min(420px, calc(100% - 24px)); border: 0; border-radius: 14px; padding: 22px; box-shadow: 0 20px 50px rgba(15, 23, 42, .3); }
        .password-dialog::backdrop { background: rgba(15, 23, 42, .55); }
        .password-dialog h2 { margin: 0 0 16px; }
        .password-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        .password-error { margin: 12px 0 0; color: #991b1b; font-weight: 700; }
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
            .decision-editor { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
        }
        @media (max-width: 600px) {
            body { padding: 8px; }
            .header { flex-wrap: wrap; padding: 12px; }
            h1 { font-size: 1.25rem; }
            .filters { grid-template-columns: 1fr; }
            .approvals { grid-template-columns: 1fr; }
            .decision-editor { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .decision-edit-row { align-items: flex-start; flex-direction: column; }
            .decision-edit-actions { grid-column: auto; }
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
            <button class="button verification-tab" type="button" data-status="verification" aria-pressed="true"<?php echo ($errorMessage !== '' || !$rows) ? ' disabled' : ''; ?>>
                Verification
            </button>
            <button class="button verification-tab" type="button" data-status="pass" aria-pressed="false"<?php echo ($errorMessage !== '' || !$rows) ? ' disabled' : ''; ?>>
                Pass
            </button>
            <button class="button verification-tab" type="button" data-status="fail" aria-pressed="false"<?php echo ($errorMessage !== '' || !$rows) ? ' disabled' : ''; ?>>
                Fail
            </button>
        </nav>

        <dialog class="password-dialog" id="editPasswordDialog" aria-labelledby="editPasswordTitle">
            <form id="editPasswordForm">
                <h2 id="editPasswordTitle">Authorize verification edit</h2>
                <label for="editVerificationPassword">Verification password</label>
                <input type="password" id="editVerificationPassword" autocomplete="current-password" required>
                <p class="password-error" id="editPasswordError" role="alert" hidden></p>
                <div class="password-dialog-actions">
                    <button class="button clear" type="button" id="cancelPasswordEdit">Cancel</button>
                    <button class="button" type="submit" id="authorizePasswordEdit">Continue</button>
                </div>
            </form>
        </dialog>

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
                <p class="status"><?php echo count($rows); ?> record(s) found.</p>
            <?php else: ?>
                <p class="status"><?php echo count($rows); ?> record(s) loaded. Use search fields to filter.</p>
            <?php endif; ?>
        </section>

        <?php if ($errorMessage === ''): ?>
            <section class="panel">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th data-action-header>Action</th><th>Roll</th><th>Batch</th><th>PO Number</th><th>Color</th><th>QTY</th><th>MC NO</th><th>SONO</th>
                                <th>Buyer</th><th>Style</th><th>MC Dia</th><th>Customer</th><th>O / T</th>
                                <th>SL/VQ</th><th>Fabrics Type</th><th>GSM</th><th>Dia</th>
                                <th>Yarn Type</th><th>Yarn Count</th><th>Yarn Brand</th><th>Feeder Plan</th>
                                <th>Lot No</th><th>Category</th>
                                <th data-approval-column hidden>Shift Incharge</th>
                                <th data-approval-column hidden>Supervisor</th>
                                <th data-approval-column hidden>Batch Incharge</th>
                                <th data-approval-column hidden>Batch Manager</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows): ?>
                                <tr><td class="empty" colspan="27">No matching delivery records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr class="delivery-row"
                                        data-batch="<?php echo h($row['BCARD']); ?>"
                                        data-roll="<?php echo h($row['ROLL']); ?>"
                                        data-status="<?php echo h($row['VERIFICATION_STATUS']); ?>"
                                        data-sincharge="<?php echo h($row['SINCHARGE'] ?? ''); ?>"
                                        data-supervisor="<?php echo h($row['SUPERVISOR'] ?? ''); ?>"
                                        data-bincharge="<?php echo h($row['BINCHARGE'] ?? ''); ?>"
                                        data-bmanager="<?php echo h($row['BMANAGER'] ?? ''); ?>">
                                        <td data-action-cell>
                                            <button class="button approve-batch" type="button" hidden>Approval</button>
                                            <button class="button edit-verification" type="button" hidden>Edit</button>
                                        </td>
                                        <td><?php echo h($row['ROLL']); ?></td>
                                        <td data-batch-cell><?php echo h($row['BCARD']); ?></td>
                                        <td data-column="PO_NUMBER"><?php echo h($row['PO_NUMBER']); ?></td>
                                        <td data-column="COLOR"><?php echo h($row['COLOR']); ?></td>
                                        <td data-column="QTY"><?php echo h($row['QTY']); ?></td>
                                        <td data-column="DYMCNO"><?php echo h($row['DYMCNO']); ?></td>
                                        <td data-column="SONO"><?php echo h($row['SONO']); ?></td>
                                        <td data-column="BUYER"><?php echo h($row['BUYER']); ?></td>
                                        <td data-column="STYLE"><?php echo h($row['STYLE']); ?></td>
                                        <td data-column="MCDIA"><?php echo h($row['MCDIA']); ?></td>
                                        <td data-column="CUSTOMER"><?php echo h($row['CUSTOMER']); ?></td>
                                        <td data-column="O_T"><?php echo h($row['O_T']); ?></td>
                                        <td data-column="SL"><?php echo h($row['SL']); ?></td>
                                        <td data-column="FTYPE"><?php echo h($row['FTYPE']); ?></td>
                                        <td data-column="FGSM"><?php echo h($row['FGSM']); ?></td>
                                        <td data-column="FDIA"><?php echo h($row['FDIA']); ?></td>
                                        <td data-column="YTYPE"><?php echo h($row['YTYPE']); ?></td>
                                        <td data-column="YCOUNT"><?php echo h($row['YCOUNT']); ?></td>
                                        <td data-column="YBRAND"><?php echo h($row['YBRAND']); ?></td>
                                        <td data-column="FEEDER_PLAN"><?php echo h($row['FEEDER_PLAN']); ?></td>
                                        <td data-column="LOT_NO"><?php echo h($row['LOT_NO']); ?></td>
                                        <td data-column="CATEGORY"><?php echo h($row['CATEGORY']); ?></td>
                                        <td data-approval-column hidden><?php echo h($row['SINCHARGE'] ?? ''); ?></td>
                                        <td data-approval-column hidden><?php echo h($row['SUPERVISOR'] ?? ''); ?></td>
                                        <td data-approval-column hidden><?php echo h($row['BINCHARGE'] ?? ''); ?></td>
                                        <td data-approval-column hidden><?php echo h($row['BMANAGER'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($rows): ?>
                                <tr id="emptyTabRow" hidden>
                                    <td class="empty" colspan="27">There are no batches in this status.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($rows): ?>
                    <div class="approval-panel" id="roleDecisionPanel" hidden>
                        <h2 class="approval-title">Edit Batch Approval</h2>
                        <div class="decision-editor" id="decisionEditor"></div>
                    </div>
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
    <?php if ($errorMessage === '' && $rows): ?>
        <script>
            (function() {
                var approvalButtons = Array.from(document.querySelectorAll('.approval-choice'));
                var approvalBatchButtons = Array.from(document.querySelectorAll('.approve-batch'));
                var approvalCount = document.getElementById('approvalCount');
                var doneButton = document.getElementById('verificationDoneButton');
                var result = document.getElementById('verificationResult');
                var tabs = Array.from(document.querySelectorAll('.verification-tab'));
                var rows = Array.from(document.querySelectorAll('.delivery-row'));
                var emptyTabRow = document.getElementById('emptyTabRow');
                var approvalPanel = document.getElementById('approvalPanel');
                var actionHeader = document.querySelector('[data-action-header]');
                var roleDecisionPanel = document.getElementById('roleDecisionPanel');
                var decisionEditor = document.getElementById('decisionEditor');
                var passwordDialog = document.getElementById('editPasswordDialog');
                var passwordForm = document.getElementById('editPasswordForm');
                var passwordInput = document.getElementById('editVerificationPassword');
                var passwordError = document.getElementById('editPasswordError');
                var authorizeButton = document.getElementById('authorizePasswordEdit');
                var activeEditButton = null;
                var authorizedPassword = null;
                var decisions = {};
                var completed = false;
                var activeApprovalBatch = null;
                var activeStatus = 'verification';
                var statuses = {};
                rows.forEach(function(row) {
                    statuses[rowKey(row)] = row.dataset.status || 'verification';
                });

                function rowKey(row) {
                    return row.dataset.batch + '\u0000' + row.dataset.roll;
                }

                function showSuccessAndReload(title, message, clearSearch) {
                    var reloadPage = function() {
                        if (clearSearch) {
                            window.location.replace(window.location.pathname);
                            return;
                        }
                        window.location.reload();
                    };
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: title,
                            text: message,
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                            allowOutsideClick: false
                        }).then(function() {
                            reloadPage();
                        });
                        return;
                    }

                    result.textContent = message;
                    result.hidden = false;
                    window.setTimeout(function() {
                        reloadPage();
                    }, 3000);
                }

                function showEditError(message) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Could Not Save Batch',
                            text: message
                        });
                        return;
                    }

                    result.textContent = message;
                    result.hidden = false;
                    result.scrollIntoView({behavior: 'smooth', block: 'center'});
                }

                function updateTabs() {
                    var counts = {verification: 0, pass: 0, fail: 0};
                    rows.forEach(function(row) {
                        var status = statuses[rowKey(row)] || 'verification';
                        if (Object.prototype.hasOwnProperty.call(counts, status)) counts[status]++;
                    });
                    actionHeader.hidden = false;
                    document.querySelectorAll('[data-approval-column]').forEach(function(cell) {
                        cell.hidden = activeStatus === 'verification';
                    });
                    if (activeEditButton && statuses[rowKey(activeEditButton.closest('.delivery-row'))] !== activeStatus) {
                        roleDecisionPanel.hidden = true;
                    }
                    rows.forEach(function(row) {
                        var status = statuses[rowKey(row)] || 'verification';
                        var isVisible = status === activeStatus;
                        row.hidden = !isVisible;
                        var actionCell = row.querySelector('[data-action-cell]');
                        actionCell.hidden = !isVisible;
                        row.querySelector('[data-action-cell]').rowSpan = 1;
                        row.querySelector('.approve-batch').hidden = status !== 'verification';
                        row.querySelector('.edit-verification').hidden =
                            status !== 'pass' && status !== 'fail';
                        var batchCell = row.querySelector('[data-batch-cell]');
                        batchCell.hidden = false;
                        batchCell.rowSpan = 1;
                    });

                    var visibleRows = rows.filter(function(row) {
                        return !row.hidden;
                    });
                    for (var index = 0; index < visibleRows.length;) {
                        var groupEnd = index + 1;
                        while (groupEnd < visibleRows.length &&
                            visibleRows[groupEnd].dataset.batch === visibleRows[index].dataset.batch) {
                            groupEnd++;
                        }
                        var firstBatchCell = visibleRows[index].querySelector('[data-batch-cell]');
                        firstBatchCell.rowSpan = groupEnd - index;
                        var firstActionCell = visibleRows[index].querySelector('[data-action-cell]');
                        firstActionCell.rowSpan = groupEnd - index;
                        for (var duplicateIndex = index + 1; duplicateIndex < groupEnd; duplicateIndex++) {
                            visibleRows[duplicateIndex].querySelector('[data-batch-cell]').hidden = true;
                            visibleRows[duplicateIndex].querySelector('[data-action-cell]').hidden = true;
                        }
                        index = groupEnd;
                    }
                    emptyTabRow.hidden = rows.some(function(row) {
                        return !row.hidden;
                    });
                    tabs.forEach(function(tab) {
                        tab.setAttribute('aria-pressed', tab.dataset.status === activeStatus ? 'true' : 'false');
                    });
                    approvalPanel.hidden = activeStatus !== 'verification' ||
                        counts.verification === 0 || !activeApprovalBatch;
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
                    doneButton.disabled = completed || !activeApprovalBatch ||
                        !((passed >= 2 && passed > failed) || (failed >= 2 && failed > passed));
                }

                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function() {
                        activeStatus = this.dataset.status;
                        if (activeStatus !== 'verification') {
                            activeApprovalBatch = null;
                            decisions = {};
                            completed = false;
                            approvalButtons.forEach(function(button) {
                                button.setAttribute('aria-pressed', 'false');
                            });
                            updateApprovals();
                        }
                        result.hidden = true;
                        updateTabs();
                    });
                });

                approvalBatchButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        var row = this.closest('.delivery-row');
                        if (!row || statuses[rowKey(row)] !== 'verification') return;
                        activeApprovalBatch = row.dataset.batch;
                        decisions = {};
                        completed = false;
                        approvalButtons.forEach(function(approvalButton) {
                            approvalButton.setAttribute('aria-pressed', 'false');
                        });
                        result.hidden = true;
                        approvalPanel.hidden = false;
                        approvalPanel.querySelector('.approval-title').textContent =
                            'Batch Approval - ' + activeApprovalBatch;
                        updateApprovals();
                        approvalPanel.scrollIntoView({behavior: 'smooth', block: 'center'});
                    });
                });

                approvalButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        if (completed || !activeApprovalBatch) return;
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

                doneButton.addEventListener('click', async function() {
                    if (this.disabled) return;
                    var passed = Object.keys(decisions).filter(function(role) {
                        return decisions[role] === 'pass';
                    }).length;
                    var failed = Object.keys(decisions).filter(function(role) {
                        return decisions[role] === 'fail';
                    }).length;
                    var finalStatus = passed > failed ? 'pass' : 'fail';
                    var pendingBatchIds = activeApprovalBatch ? [activeApprovalBatch] : [];
                    var roleColumns = ['SINCHARGE', 'SUPERVISOR', 'BINCHARGE', 'BMANAGER'];
                    var savedDecisions = {};
                    roleColumns.forEach(function(column, index) {
                        if (Object.prototype.hasOwnProperty.call(decisions, String(index))) {
                            savedDecisions[column] = decisions[String(index)];
                        }
                    });

                    doneButton.disabled = true;
                    result.hidden = true;
                    try {
                        var response = await fetch('ajaxDyeing_batch_verification.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                batch_ids: pendingBatchIds,
                                decisions: savedDecisions
                            })
                        });
                        var payload = await response.json();
                        if (!response.ok || !payload.success) {
                            throw new Error(payload.message || 'Could not save batch verification.');
                        }

                        (payload.batch_ids || pendingBatchIds).forEach(function(batchId) {
                            rows.forEach(function(row) {
                                if (row.dataset.batch === batchId) {
                                    row.dataset.status = payload.status || finalStatus;
                                    row.dataset.sincharge = savedDecisions.SINCHARGE
                                        ? savedDecisions.SINCHARGE.toUpperCase() : '';
                                    row.dataset.supervisor = savedDecisions.SUPERVISOR
                                        ? savedDecisions.SUPERVISOR.toUpperCase() : '';
                                    row.dataset.bincharge = savedDecisions.BINCHARGE
                                        ? savedDecisions.BINCHARGE.toUpperCase() : '';
                                    row.dataset.bmanager = savedDecisions.BMANAGER
                                        ? savedDecisions.BMANAGER.toUpperCase() : '';
                                    statuses[rowKey(row)] = row.dataset.status;
                                }
                            });
                        });
                        completed = true;
                        approvalButtons.forEach(function(button) {
                            button.disabled = true;
                        });
                        this.textContent = 'Verification ' + finalStatus.toUpperCase();
                        activeStatus = finalStatus;
                        updateApprovals();
                        updateTabs();
                        showSuccessAndReload(
                            'Verification Saved',
                            'Batch verification data was saved successfully.',
                            true
                        );
                    } catch (error) {
                        result.textContent = 'Verification was not saved: ' + error.message;
                        result.hidden = false;
                        updateApprovals();
                    }
                });

                document.getElementById('cancelPasswordEdit').addEventListener('click', function() {
                    passwordDialog.close();
                });
                passwordDialog.addEventListener('close', function() {
                    if (activeEditButton && !activeEditButton.dataset.authorized) {
                        activeEditButton = null;
                    }
                    passwordError.hidden = true;
                    passwordInput.value = '';
                });

                passwordForm.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    if (!activeEditButton) return;
                    var row = activeEditButton.closest('.delivery-row');
                    var editButton = activeEditButton;
                    if (!row) return;
                    authorizeButton.disabled = true;
                    passwordError.hidden = true;
                    try {
                        var batchRows = rows.filter(function(batchRow) {
                            return batchRow.dataset.batch === row.dataset.batch;
                        });
                        var response = await fetch('ajaxDyeing_batch_verification.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                action: 'authorize_edit',
                                password: passwordInput.value,
                                batch_id: row.dataset.batch
                            })
                        });
                        var payload = await response.json();
                        if (!response.ok || !payload.success) {
                            throw new Error(payload.message || 'Edit authorization failed.');
                        }

                        editButton.dataset.authorized = 'true';
                        var editor = decisionEditor;
                        editor.replaceChildren();
                        [
                            ['SINCHARGE', 'Shift Incharge', 'sincharge'],
                            ['SUPERVISOR', 'Supervisor', 'supervisor'],
                            ['BINCHARGE', 'Batch Incharge', 'bincharge'],
                            ['BMANAGER', 'Batch Manager', 'bmanager']
                        ].forEach(function(role) {
                            var roleRow = document.createElement('div');
                            roleRow.className = 'decision-edit-row';
                            var label = document.createElement('strong');
                            label.textContent = role[1];
                            var options = document.createElement('div');
                            options.className = 'decision-edit-options';
                            ['pass', 'fail'].forEach(function(decision) {
                                var choice = document.createElement('button');
                                choice.className = 'button decision-toggle';
                                choice.type = 'button';
                                choice.dataset.role = role[0];
                                choice.dataset.decision = decision;
                                choice.setAttribute(
                                    'aria-pressed',
                                    row.dataset[role[2]].toLowerCase() === decision ? 'true' : 'false'
                                );
                                choice.textContent = decision.toUpperCase();
                                choice.addEventListener('click', function() {
                                    options.querySelectorAll('.decision-toggle').forEach(function(other) {
                                        other.setAttribute(
                                            'aria-pressed',
                                            other === choice ? 'true' : 'false'
                                        );
                                    });
                                });
                                options.appendChild(choice);
                            });
                            roleRow.appendChild(label);
                            roleRow.appendChild(options);
                            editor.appendChild(roleRow);
                        });
                        var actions = document.createElement('div');
                        actions.className = 'decision-edit-actions';
                        var saveButton = document.createElement('button');
                        saveButton.className = 'button save-decisions';
                        saveButton.type = 'button';
                        saveButton.textContent = 'Save';
                        var cancelButton = document.createElement('button');
                        cancelButton.className = 'button cancel-edit';
                        cancelButton.type = 'button';
                        cancelButton.textContent = 'Cancel';
                        actions.appendChild(saveButton);
                        actions.appendChild(cancelButton);
                        editor.appendChild(actions);
                        activeEditButton = editButton;
                        authorizedPassword = passwordInput.value;
                        passwordInput.value = '';
                        roleDecisionPanel.hidden = false;
                        passwordDialog.close();

                        cancelButton.addEventListener('click', function() {
                            roleDecisionPanel.hidden = true;
                            delete editButton.dataset.authorized;
                            activeEditButton = null;
                            authorizedPassword = null;
                        });
                        saveButton.addEventListener('click', async function() {
                            var editedDecisions = {};
                            editor.querySelectorAll('.decision-toggle[aria-pressed="true"]').forEach(
                                function(choice) {
                                    editedDecisions[choice.dataset.role] = choice.dataset.decision;
                                }
                            );
                            var selectedRoles = Object.keys(editedDecisions).length;
                            var passCount = Object.values(editedDecisions).filter(function(decision) {
                                return decision === 'pass';
                            }).length;
                            var failCount = Object.values(editedDecisions).filter(function(decision) {
                                return decision === 'fail';
                            }).length;
                            if (selectedRoles !== 4) {
                                showEditError('Select PASS or FAIL for all four approval roles.');
                                return;
                            }
                            if (passCount === failCount) {
                                showEditError('This batch has 2 PASS and 2 FAIL decisions. Select a clear majority to save.');
                                return;
                            }
                            saveButton.disabled = true;
                            result.hidden = true;
                            try {
                                var saveResponse = await fetch('ajaxDyeing_batch_verification.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({
                                        action: 'update_decisions',
                                        password: authorizedPassword,
                                        batch_id: row.dataset.batch,
                                        decisions: editedDecisions
                                    })
                                });
                                var savePayload = await saveResponse.json();
                                if (!saveResponse.ok || !savePayload.success) {
                                    throw new Error(savePayload.message || 'Could not save role decisions.');
                                }
                                batchRows.forEach(function(batchRow) {
                                    batchRow.dataset.sincharge = editedDecisions.SINCHARGE.toUpperCase();
                                    batchRow.dataset.supervisor = editedDecisions.SUPERVISOR.toUpperCase();
                                    batchRow.dataset.bincharge = editedDecisions.BINCHARGE.toUpperCase();
                                    batchRow.dataset.bmanager = editedDecisions.BMANAGER.toUpperCase();
                                    batchRow.dataset.status = savePayload.status;
                                    statuses[rowKey(batchRow)] = savePayload.status;
                                });
                                roleDecisionPanel.hidden = true;
                                delete editButton.dataset.authorized;
                                activeEditButton = null;
                                authorizedPassword = null;
                                updateTabs();
                                showSuccessAndReload(
                                    'Batch Updated',
                                    'Batch Card ' + row.dataset.batch + ' was updated for all ' +
                                        (savePayload.roll_count || batchRows.length) + ' roll(s) and is now ' +
                                        savePayload.status.toUpperCase() + '.'
                                );
                            } catch (error) {
                                showEditError('Role decisions were not saved: ' + error.message);
                            } finally {
                                saveButton.disabled = false;
                            }
                        });
                    } catch (error) {
                        passwordError.textContent = error.message;
                        passwordError.hidden = false;
                    } finally {
                        authorizeButton.disabled = false;
                    }
                });

                document.querySelectorAll('.edit-verification').forEach(function(button) {
                    button.addEventListener('click', function() {
                        if (activeEditButton && activeEditButton !== this) return;
                        activeEditButton = this;
                        passwordInput.value = '';
                        passwordError.hidden = true;
                        passwordDialog.showModal();
                        passwordInput.focus();
                    });
                });

                updateApprovals();
                updateTabs();
            })();
        </script>
    <?php endif; ?>
</body>
</html>
