<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['batch_transfer_csrf'])) {
    $_SESSION['batch_transfer_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['batch_transfer_csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FabLine | Batch Transfer</title>
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
            justify-content: space-between;
            gap: 14px;
            padding: 16px 20px;
            margin-bottom: 14px;
        }
        h1 { margin: 0; font-size: 1.4rem; }
        .button {
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
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .button.clear { color: #334155; background: #e2e8f0; }
        .button:disabled { background: #94a3b8; cursor: not-allowed; }
        .panel { padding: 18px; margin-bottom: 14px; }
        .filters {
            display: grid;
            grid-template-columns: repeat(3, minmax(160px, 1fr)) auto auto;
            align-items: end;
            gap: 12px;
        }
        label { display: block; margin-bottom: 6px; font-weight: 700; }
        input, select {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #afc8c4;
            border-radius: 8px;
            background: #fff;
            color: #123b39;
            font: inherit;
        }
        input:focus, select:focus { outline: 2px solid #54b7a8; border-color: #159685; }
        .status { margin: 14px 0 0; font-weight: 700; }
        .status.error { color: #991b1b; }
        .status.success { color: #065f46; }
        .table-wrap { width: 100%; margin-top: 12px; overflow-x: auto; }
        table { width: 100%; min-width: 2200px; border-collapse: collapse; font-size: .86rem; }
        th, td {
            padding: 9px 8px;
            border-bottom: 1px solid #dbe5e4;
            text-align: left;
            white-space: nowrap;
        }
        thead th { color: #fff; background: #134e4a; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        td[data-batch-cell], td[data-machine-cell] { text-align: center; vertical-align: middle; }
        td select { min-width: 170px; }
        .actions { display: flex; justify-content: flex-end; margin-top: 14px; }
        .empty { padding: 24px; text-align: center; color: #64748b; }
        @media (max-width: 700px) {
            body { padding: 8px; }
            .header { align-items: flex-start; flex-direction: column; padding: 12px; }
            h1 { font-size: 1.2rem; }
            .filters { grid-template-columns: 1fr; }
            .filters .button { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <a class="button clear" href="initialPage.php"><i class="fas fa-arrow-left"></i> Back</a>
            <h1><i class="fas fa-right-left"></i> Batch Transfer</h1>
            <span></span>
        </header>

        <section class="panel">
            <form class="filters" id="searchForm">
                <div>
                    <label for="batchSearch">Batch Card</label>
                    <input type="text" id="batchSearch" name="batch" placeholder="Search Batch Card">
                </div>
                <div>
                    <label for="poSearch">PO Number</label>
                    <input type="text" id="poSearch" name="po_number" placeholder="Search PO Number">
                </div>
                <div>
                    <label for="dateSearch">Specific Date</label>
                    <input type="date" id="dateSearch" name="budat" max="<?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <button class="button" type="submit"><i class="fas fa-search"></i> Search</button>
                <button class="button clear" type="reset">Clear</button>
            </form>
            <p class="status" id="status" role="status" aria-live="polite">Loading all delivery rolls...</p>
        </section>

        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Batch</th><th>Roll</th><th>PO Number</th><th>Color</th><th>QTY</th>
                            <th>MCNO</th><th>Change MCNO</th><th>SONO</th><th>Buyer</th><th>Style</th>
                            <th>MC Dia</th><th>Customer</th><th>O /T</th><th>SL/VQ</th>
                            <th>Fabrics Type</th><th>GSM</th><th>Dia</th><th>Yarn Type</th>
                            <th>Yarn Count</th><th>Yarn Brand</th><th>Feeder Plan</th><th>Lot No</th><th>Category</th>
                        </tr>
                    </thead>
                    <tbody id="results">
                        <tr><td class="empty" colspan="23">Loading delivery rolls...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="actions">
                <button class="button" type="button" id="saveButton" disabled><i class="fas fa-save"></i> Save MCNO Changes</button>
            </div>
        </section>
    </main>

    <script>
        (function() {
            'use strict';
            var csrfToken = <?php echo json_encode($csrfToken); ?>;
            var machines = [];
            var machinesLoaded = false;
            var machineLoadError = '';
            var rows = [];
            var form = document.getElementById('searchForm');
            var results = document.getElementById('results');
            var status = document.getElementById('status');
            var saveButton = document.getElementById('saveButton');

            function escapeHtml(value) {
                var span = document.createElement('span');
                span.textContent = value === null || value === undefined ? '' : String(value);
                return span.innerHTML;
            }

            function showStatus(message, kind) {
                status.textContent = message;
                status.className = 'status' + (kind ? ' ' + kind : '');
            }

            function request(action, params, body) {
                var url = new URL('ajaxDyeing_batch_transfer.php', window.location.href);
                url.searchParams.set('action', action);
                Object.keys(params || {}).forEach(function(key) {
                    url.searchParams.set(key, params[key]);
                });
                var options = body
                    ? {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(body)
                    }
                    : {method: 'GET'};
                return fetch(url.toString(), options).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'The request could not be completed.');
                        }
                        return data;
                    });
                });
            }

            function updateSaveButton() {
                var changed = results.querySelectorAll('select[data-batch]').length > 0 &&
                    Array.prototype.some.call(results.querySelectorAll('select[data-batch]'), function(select) {
                        return select.value !== select.dataset.original;
                    });
                saveButton.disabled = !changed;
            }

            function makeMachineOptions(current) {
                var options = '<option value="">Select machine</option>';
                var found = false;
                machines.forEach(function(machine) {
                    var value = String(machine.DMCNO || '');
                    if (value === String(current || '')) found = true;
                    options += '<option value="' + escapeHtml(value) + '"' +
                        (value === String(current || '') ? ' selected' : '') + '>' +
                        escapeHtml(value) + ' | ' + escapeHtml(machine.DCAPACITY) + '</option>';
                });
                if (current && !found) {
                    options = '<option value="' + escapeHtml(current) + '" selected>' +
                        escapeHtml(current) + ' (not in machine list)</option>' + options;
                }
                return options;
            }

            function renderRows() {
                if (!rows.length) {
                    results.innerHTML = '<tr><td class="empty" colspan="23">No matching delivery rolls were found.</td></tr>';
                    saveButton.disabled = true;
                    return;
                }

                var fields = [
                    'BCARD', 'ROLL', 'PO_NUMBER', 'COLOR', 'QTY', 'DYMCNO',
                    'SONO', 'BUYER', 'STYLE', 'MCDIA', 'CUSTOMER', 'O_T', 'SL',
                    'FTYPE', 'FGSM', 'FDIA', 'YTYPE', 'YCOUNT', 'YBRAND',
                    'FEEDER_PLAN', 'LOT_NO', 'CATEGORY'
                ];
                var html = '';
                for (var start = 0; start < rows.length;) {
                    var end = start + 1;
                    while (end < rows.length && String(rows[end].BCARD) === String(rows[start].BCARD)) {
                        end++;
                    }
                    var groupSize = end - start;
                    var machineValues = [];
                    for (var machineIndex = start; machineIndex < end; machineIndex++) {
                        var machineValue = String(rows[machineIndex].DYMCNO || '');
                        if (machineValues.indexOf(machineValue) === -1) {
                            machineValues.push(machineValue);
                        }
                    }
                    var currentMachine = machineValues.length === 1 ? machineValues[0] : '';
                    for (var index = start; index < end; index++) {
                        var row = rows[index];
                        html += '<tr>';
                        if (index === start) {
                            html += '<td data-batch-cell rowspan="' + groupSize + '">' +
                                escapeHtml(row.BCARD) + '</td>';
                        }
                        html += '<td>' + escapeHtml(row.ROLL) + '</td>' +
                            '<td>' + escapeHtml(row.PO_NUMBER) + '</td>' +
                            '<td>' + escapeHtml(row.COLOR) + '</td>' +
                            '<td>' + escapeHtml(row.QTY) + '</td>';
                        if (index === start) {
                            html += '<td data-machine-cell rowspan="' + groupSize + '">' +
                                escapeHtml(machineValues.length > 1 ? 'Multiple' : currentMachine) + '</td>' +
                                '<td data-machine-cell rowspan="' + groupSize + '">' +
                                '<select data-batch="' + escapeHtml(row.BCARD) +
                                '" data-original="' + escapeHtml(currentMachine) + '">' +
                                makeMachineOptions(currentMachine) + '</select></td>';
                        }
                        html += fields.slice(6).map(function(field) {
                            return '<td>' + escapeHtml(row[field]) + '</td>';
                        }).join('') + '</tr>';
                    }
                    start = end;
                }
                results.innerHTML = html;
                Array.prototype.forEach.call(results.querySelectorAll('select[data-batch]'), function(select) {
                    select.addEventListener('change', updateSaveButton);
                });
                updateSaveButton();
            }

            function loadRows() {
                var batch = document.getElementById('batchSearch').value.trim();
                var poNumber = document.getElementById('poSearch').value.trim();
                var budat = document.getElementById('dateSearch').value;
                showStatus('Loading delivery rolls...');
                return request('search', {batch: batch, po_number: poNumber, budat: budat}).then(function(data) {
                    rows = data.rows;
                    renderRows();
                    var batches = Object.create(null);
                    rows.forEach(function(row) {
                        batches[String(row.BCARD || '')] = true;
                    });
                    var batchCount = Object.keys(batches).length;
                    showStatus(batchCount + ' batch(es) found.' +
                        (machineLoadError ? ' ' + machineLoadError : ''));
                }).catch(function(error) {
                    rows = [];
                    renderRows();
                    showStatus(error.message, 'error');
                });
            }

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                if (!machinesLoaded) {
                    showStatus('Dyeing machine list is still loading. Please wait and search again.', 'error');
                    return;
                }
                loadRows();
            });

            form.addEventListener('reset', function() {
                window.setTimeout(function() {
                    if (machinesLoaded) {
                        loadRows();
                    } else {
                        showStatus('Dyeing machine list is still loading. Please wait.', 'error');
                    }
                }, 0);
            });

            saveButton.addEventListener('click', function() {
                var changes = Array.prototype.filter.call(results.querySelectorAll('select[data-batch]'), function(select) {
                    return select.value !== '' && select.value !== select.dataset.original;
                }).map(function(select) {
                    return {
                        batch: select.dataset.batch,
                        mcno: select.value
                    };
                });
                if (!changes.length) {
                    showStatus('Choose a different machine for at least one roll before saving.', 'error');
                    return;
                }

                saveButton.disabled = true;
                showStatus('Saving MCNO changes...');
                request('save', {}, {csrf_token: csrfToken, changes: changes}).then(function(data) {
                    var updated = {};
                    data.updated.forEach(function(item) { updated[String(item.batch)] = item.mcno; });
                    rows.forEach(function(row) {
                        if (Object.prototype.hasOwnProperty.call(updated, String(row.BCARD))) {
                            row.DYMCNO = updated[String(row.BCARD)];
                        }
                    });
                    renderRows();
                    showStatus(data.message, 'success');
                }).catch(function(error) {
                    showStatus(error.message, 'error');
                    updateSaveButton();
                });
            });

            request('machines').then(function(data) {
                machines = data.machines;
                machinesLoaded = true;
                if (!machines.length) {
                    machineLoadError = 'No dyeing machines were found in dyeing_mcno.';
                }
                loadRows();
            }).catch(function(error) {
                machinesLoaded = true;
                machineLoadError = 'Could not load dyeing machines: ' + error.message;
                loadRows();
            });
        })();
    </script>
</body>
</html>
