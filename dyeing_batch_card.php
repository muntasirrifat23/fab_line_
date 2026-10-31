<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit;
}

// Start each page load with a fresh batch so old session rolls never linger
if (!isset($_SESSION['dyeing_batch']) || !is_array($_SESSION['dyeing_batch'])) {
    $_SESSION['dyeing_batch'] = [];
}
$_SESSION['dyeing_batch']['rolls'] = [];
unset($_SESSION['dyeing_batch']['card_no']);
unset($_SESSION['dyeing_batch']['created_at']);
unset($_SESSION['dyeing_batch']['saved']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dyeing | Batch Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
            background: linear-gradient(135deg, #f0fdf9, #e0f2fe, #f5f3ff);
            min-height: 100vh;
            padding: 16px;
        }

        .wrap {
            width: 100%;
            max-width: none;
            margin: 0 auto;
        }

        .top-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #134e4a, #0f766e, #0d9488);
            color: #fff;
            padding: 18px 22px;
            border-radius: 18px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            position: relative;
            justify-content: space-between;
        }

        .top-bar h1 {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }

        .card-no-badge {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.35);
            padding: 6px 14px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 118, 110, 0.1);
            padding: 20px;
            margin-bottom: 18px;
        }

        .panel-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #134e4a;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 14px;
        }

        .filter-field {
            flex: 1;
            min-width: 220px;
        }

        .filter-field.target-qty-field {
            flex: 0 1 220px;
        }

        .filter-field label {
            display: block;
            margin-bottom: 6px;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .filter-field-label {
            display: block;
            margin-bottom: 6px;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .search-input {
            flex: 1;
            min-width: 220px;
            padding: 11px 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
            outline: none;
        }

        .search-input:focus,
        .filter-select:focus,
        .qty-input:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }

        .filter-select,
        .qty-input {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            font-size: 0.95rem;
            outline: none;
        }

        .filter-select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .po-input {
            cursor: text !important;
        }

        .suggestion-preview {
            margin-top: 16px;
        }

        .suggestion-preview table {
            width: 100%;
        }

        .suggestion-preview th,
        .suggestion-preview td {
            padding: 8px 14px;
        }

        .suggestion-preview h3 {
            margin-bottom: 10px;
            color: #134e4a;
            font-size: 0.95rem;
        }

        .is-hidden {
            display: none !important;
        }

        .loading-note {
            color: #0f766e;
            font-weight: 600;
        }

        @media (max-width: 720px) {
            .filter-field.target-qty-field {
                flex: 1;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 8px;
            }

            .panel {
                padding: 12px;
            }

            .filter-field {
                min-width: 100%;
            }
        }

        .btn {
            border: none;
            cursor: pointer;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .search-row > .btn {
            min-height: 42px;
            justify-content: center;
            align-self: flex-end;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-green {
            background: linear-gradient(135deg, #059669, #10b981);
        }

        .btn-blue {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
        }

        .btn-amber {
            background: linear-gradient(135deg, #d97706, #f59e0b);
        }

        .btn-slate {
            background: linear-gradient(135deg, #475569, #64748b);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .status-line {
            margin: 10px 0;
            font-weight: 600;
            color: #0f766e;
        }

        .table-scroll {
            overflow-x: auto;
        }

        #searchResults .table-scroll > table {
            width: 100%;
            min-width: 1500px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }

        thead th {
            background: #134e4a;
            color: #fff;
            padding: 12px 10px;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
        }

        tbody td {
            padding: 11px 10px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
            vertical-align: middle;
        }

        tfoot th,
        tfoot td {
            padding: 12px 10px;
            border-top: 2px solid #cbd5e1;
            background: #f0fdfa;
            color: #134e4a;
            font-weight: 800;
            white-space: nowrap;
        }

        tfoot .total-label {
            text-align: right;
        }

        .roll-select-cell {
            width: 48px;
            text-align: center;
        }

        .roll-select-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #0d9488;
            cursor: pointer;
        }

        .remove-roll-btn {
            border: 0;
            border-radius: 8px;
            padding: 7px 10px;
            background: #fee2e2;
            color: #b91c1c;
            font-weight: 700;
            cursor: pointer;
        }

        .remove-roll-btn:hover {
            background: #fecaca;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #ecfdf5;
        }

        .roll-badge {
            background: #ccfbf1;
            color: #065f46;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 100px;
            display: inline-block;
        }

        .add-btn {
            background: #d1fae5;
            color: #047857;
            border: none;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.2s;
        }

        .add-btn:hover {
            background: #a7f3d0;
        }

        .add-btn:disabled {
            background: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
        }

        .delete-btn {
            background: #fee2e2;
            color: #b91c1c;
            border: none;
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .delete-btn:hover {
            background: #fecaca;
            transform: scale(1.08);
        }

        .empty-row td {
            text-align: center;
            color: #94a3b8;
            padding: 28px;
            font-weight: 600;
        }

        .footer-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .msg-box {
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
            margin-bottom: 12px;
            display: none;
        }

        .msg-success {
            background: #dcfce7;
            color: #166534;
            display: block;
        }

        .msg-error {
            background: #fee2e2;
            color: #991b1b;
            display: block;
        }

        .added-tag {
            background: #e2e8f0;
            color: #64748b;
            padding: 3px 10px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .card-group-row {
            background: #0f766e;
            color: #fff;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .card-group-row em {
            font-style: normal;
            opacity: 0.85;
            font-weight: 500;
        }

        .filter-select {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            color: #134e4a;
            outline: none;
        }

        .filter-select:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 18px;
            background: black;
            color: white;
            border: 2px solid black;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .back-btn:hover {
            background: white;
            color: black;
            border-color: black;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="top-bar">
            <a href="initialPage.php" class="back-btn" id="backBtn"><i class="fas fa-arrow-left"></i> Back to Report</a>

            <h1><i class="fa-solid fa-vial-circle-check"></i> Dyeing Batch Card</h1>
            <span class="card-no-badge"><i class="fa-solid fa-hashtag"></i> Batch Card: <span id="cardNo">-</span></span>
        </div>

        <div id="msgBox" class="msg-box" role="status" aria-live="polite"></div>

        <div class="panel">
            <div class="panel-title"><i class="fa-solid fa-filter"></i> Find Rolls for Batch Card</div>
            <div class="search-row">
                <div class="filter-field">
                    <label for="poNumberSelect">PO Number</label>
                    <input type="text" id="poNumberSelect" class="filter-select po-input"
                        list="poNumberOptions" autocomplete="off" placeholder="Enter or select PO Number">
                    <datalist id="poNumberOptions"></datalist>
                </div>
                <div class="filter-field">
                    <label for="colorSelect">Color</label>
                    <select id="colorSelect" class="filter-select" disabled>
                        <option value="">Select a PO Number first</option>
                    </select>
                </div>
                <div class="filter-field target-qty-field">
                    <label for="targetQtyInput">Target QTY (KG)</label>
                    <input type="number" id="targetQtyInput" class="qty-input" min="0.001" step="0.001" placeholder="Enter target QTY">
                </div>
                <button class="btn btn-blue" id="searchBtn" type="button" disabled>
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </div>
        </div>

        <div class="panel is-hidden" id="searchResults">
            <div class="panel-title">
                <i class="fa-solid fa-table-list"></i> Matching Rolls
                <span class="card-no-badge" id="resultCount">0 roll</span>
            </div>
            <div class="status-line" id="resultsStatus"></div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th class="roll-select-cell">Select</th>
                            <th>ROLL</th>
                            <th>Rack</th>
                            <th>PO Number</th>
                            <th>QTY</th>
                            <th>Color</th>
                            <th>SONO</th>
                            <th>Buyer</th>
                            <th>Style</th>
                            <th>MC Dia</th>
                            <th>Customer</th>
                            <th>Open / Tube</th>
                            <th>SL/VQ</th>
                            <th>Fabrics Type</th>
                            <th>Finish GSM</th>
                            <th>Finish Dia</th>
                            <th>Yarn Type</th>
                            <th>Yarn Count</th>
                            <th>Yarn Brand</th>
                            <th>Feeder Plan</th>
                            <th>Lot No</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody id="storeBody">
                        <tr class="empty-row"><td colspan="22">Select PO Number and Color, then search.</td></tr>
                    </tbody>
                    <tfoot id="storeFoot"></tfoot>
                </table>
            </div>
        </div>

        <div class="panel is-hidden" id="suggestionPanel">
            <div class="panel-title"><i class="fa-solid fa-lightbulb"></i> Rolls for Target QTY</div>
            <p id="suggestionIntro" class="status-line"></p>
            <div class="suggestion-preview" id="suggestionPreview">
                <h3 id="suggestionPreviewTitle"></h3>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>ROLL</th><th>Rack</th><th>QTY</th><th>ACTION</th></tr></thead>
                        <tbody id="suggestionRolls">
                            <tr class="empty-row"><td colspan="4">Select rolls from the Matching Rolls table above.</td></tr>
                        </tbody>
                        <tfoot id="selectedRollsFoot"></tfoot>
                    </table>
                </div>
                <div class="footer-actions">
                    <button class="btn btn-green" id="confirmCreateBtn" type="button">
                        <i class="fa-solid fa-check"></i> Confirm &amp; Create Batch Card
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var candidateRolls = [];
        var selectedRolls = new Map();
        var colorRequestId = 0;
        var searchRequestId = 0;
        var poInputTimer = null;
        var targetQtyTimer = null;
        var poNumberSelect = document.getElementById('poNumberSelect');
        var colorSelect = document.getElementById('colorSelect');
        var searchBtn = document.getElementById('searchBtn');
        var targetQtyInput = document.getElementById('targetQtyInput');
        var confirmCreateBtn = document.getElementById('confirmCreateBtn');

        function showMsg(message, type) {
            var box = document.getElementById('msgBox');
            box.className = 'msg-box ' + (type === 'error' ? 'msg-error' : 'msg-success');
            box.textContent = message;
        }

        function hideMsg() {
            var box = document.getElementById('msgBox');
            box.className = 'msg-box';
            box.textContent = '';
        }

        function esc(value) {
            if (value === null || value === undefined || value === '') return '-';
            return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function readJson(response) {
            return response.json().then(function(data) {
                if (!response.ok) {
                    throw new Error(data.message || 'Server request failed');
                }
                return data;
            });
        }

        function apiGet(action, params) {
            var query = new URLSearchParams(params || {});
            query.set('action', action);
            return fetch('ajaxDyeing_batch_card.php?' + query.toString()).then(readJson);
        }

        function apiPost(action, payload) {
            return fetch('ajaxDyeing_batch_card.php?action=' + encodeURIComponent(action), {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload || {})
            }).then(readJson);
        }

        function resetResults(preserveTargetQty) {
            searchRequestId++;
            candidateRolls = [];
            selectedRolls.clear();
            document.getElementById('searchResults').classList.add('is-hidden');
            document.getElementById('suggestionPanel').classList.add('is-hidden');
            document.getElementById('storeBody').innerHTML =
                '<tr class="empty-row"><td colspan="22">Select PO Number and Color, then search.</td></tr>';
            document.getElementById('storeFoot').innerHTML = '';
            document.getElementById('resultCount').textContent = '0 roll';
            if (!preserveTargetQty) targetQtyInput.value = '';
            confirmCreateBtn.disabled = true;
            document.getElementById('suggestionRolls').innerHTML =
                '<tr class="empty-row"><td colspan="4">Select rolls from the Matching Rolls table above.</td></tr>';
            document.getElementById('selectedRollsFoot').innerHTML = '';
        }

        function loadPoNumbers() {
            var options = document.getElementById('poNumberOptions');
            apiGet('po_numbers').then(function(response) {
                if (!response.success) throw new Error(response.message || 'Could not load PO Numbers');
                options.innerHTML = '';
                (response.po_numbers || []).forEach(function(poNumber) {
                    var option = document.createElement('option');
                    option.value = poNumber;
                    options.appendChild(option);
                });
                if (!response.po_numbers || response.po_numbers.length === 0) {
                    showMsg('No PO Numbers with available rolls were found.', 'error');
                }
            }).catch(function(error) {
                showMsg(error.message, 'error');
            });
        }

        function loadColors(poNumber) {
            var requestId = ++colorRequestId;
            resetResults();
            colorSelect.disabled = true;
            searchBtn.disabled = true;
            colorSelect.innerHTML = '<option value="">Loading Colors...</option>';
            if (!poNumber) {
                colorSelect.innerHTML = '<option value="">Select a PO Number first</option>';
                return;
            }

            apiGet('colors', {po_number: poNumber}).then(function(response) {
                if (requestId !== colorRequestId) return;
                if (!response.success) throw new Error(response.message || 'Could not load Colors');
                colorSelect.innerHTML = '<option value="">Select Color</option>';
                (response.colors || []).forEach(function(color) {
                    var option = document.createElement('option');
                    option.value = color;
                    option.textContent = color;
                    colorSelect.appendChild(option);
                });
                if (!response.colors || response.colors.length === 0) {
                    colorSelect.innerHTML = '<option value="">No available Color for this PO</option>';
                }
                colorSelect.disabled = !response.colors || response.colors.length === 0;
            }).catch(function(error) {
                if (requestId !== colorRequestId) return;
                colorSelect.innerHTML = '<option value="">Unable to load Colors</option>';
                showMsg(error.message, 'error');
            });
        }

        function qtyUnits(row) {
            var qty = Number(row.QTY);
            return Number.isFinite(qty) && qty > 0 ? Math.round(qty * 1000) : 0;
        }

        function targetUnits() {
            var target = Number(targetQtyInput.value);
            return Number.isFinite(target) && target > 0 ? Math.round(target * 1000) : 0;
        }

        function renderRolls(rolls) {
            var fields = [
                ['ROLL', 'ROLL'], ['RACK', 'RACK'], ['PO_NUMBER', 'PO_NUMBER'], ['QTY', 'QTY'],
                ['COLOR', 'COLOR'], ['SONO', 'SONO'], ['BUYER', 'BUYER'], ['STYLE', 'STYLE'],
                ['MCDIA', 'MCDIA'], ['CUSTOMER', 'CUSTOMER'], ['O_T', 'O_T'], ['SL', 'SL'],
                ['FTYPE', 'FTYPE'], ['FGSM', 'FGSM'], ['FDIA', 'FDIA'], ['YTYPE', 'YTYPE'],
                ['YCOUNT', 'YCOUNT'], ['YBRAND', 'YBRAND'], ['FEEDER_PLAN', 'FEEDER_PLAN'],
                ['LOT_NO', 'LOT_NO'], ['CATEGORY', 'CATEGORY']
            ];
            var html = '';
            rolls.forEach(function(row, index) {
                var rollKey = String(row.ROLL);
                html += '<tr><td class="roll-select-cell"><input type="checkbox" class="roll-select-checkbox" data-roll-index="' +
                    index + '" aria-label="Select roll ' + esc(rollKey) + '"' +
                    (selectedRolls.has(rollKey) ? ' checked' : '') + '></td>' +
                    fields.map(function(field) {
                    var value = row[field[1]];
                    if (field[0] === 'ROLL') {
                        return '<td><span class="roll-badge">' + esc(value) + '</span></td>';
                    }
                    return '<td>' + esc(value) + '</td>';
                }).join('') + '</tr>';
            });
            document.getElementById('storeBody').innerHTML = html ||
                '<tr class="empty-row"><td colspan="22">No available rolls found for this PO Number and Color.</td></tr>';
            var availableTotal = rolls.reduce(function(total, row) {
                return total + qtyUnits(row);
            }, 0);
            document.getElementById('storeFoot').innerHTML =
                '<tr><th colspan="4" class="total-label">Available total QTY</th>' +
                '<th id="availableQtyTotal">' + (availableTotal / 1000).toFixed(3) + ' KG</th>' +
                '<th colspan="17"></th></tr>';
            document.querySelectorAll('.roll-select-checkbox').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    var row = candidateRolls[Number(this.dataset.rollIndex)];
                    if (row) toggleRollSelection(row, this.checked, this);
                });
            });
            document.getElementById('resultCount').textContent =
                rolls.length + ' available roll' + (rolls.length === 1 ? '' : 's');
        }

        function updateCreateButton() {
            var qty = targetUnits();
            var selected = Array.from(selectedRolls.values());
            var selectedTotal = selected.reduce(function(sum, row) {
                return sum + qtyUnits(row);
            }, 0);
            var minimum = Math.max(0, qty - 5000);
            var maximum = qty + 5000;
            document.getElementById('suggestionPanel').classList.toggle(
                'is-hidden',
                candidateRolls.length === 0 || qty <= 0
            );
            confirmCreateBtn.disabled = !selected.length || selectedTotal < minimum || selectedTotal > maximum;

            var status = document.getElementById('resultsStatus');
            if (candidateRolls.length === 0) {
                status.textContent = 'No available rolls match this PO Number and Color.';
            } else if (qty > 0) {
                status.textContent = 'Available total: ' +
                    (candidateRolls.reduce(function(sum, row) { return sum + qtyUnits(row); }, 0) / 1000).toFixed(3) +
                    ' KG. Selected total: ' + (selectedTotal / 1000).toFixed(3) +
                    ' KG; target: ' + (qty / 1000).toFixed(3) + ' KG (' +
                    (minimum / 1000).toFixed(3) + '–' + (maximum / 1000).toFixed(3) + ' KG allowed).';
            } else {
                status.textContent = 'Available total: ' +
                    (candidateRolls.reduce(function(sum, row) { return sum + qtyUnits(row); }, 0) / 1000).toFixed(3) +
                    ' KG. Enter Target QTY to auto-select rolls.';
            }
            renderSelectedRolls();
        }

        function buildSuggestedRolls(target) {
            var available = candidateRolls.reduce(function(sum, row) {
                return sum + qtyUnits(row);
            }, 0);
            var minimum = Math.max(0, target - 5000);
            var maximum = Math.min(available, target + 5000);
            if (maximum < minimum || maximum > 5000000) return null;

            var stateNodes = new Int32Array(maximum + 1);
            stateNodes.fill(-2);
            stateNodes[0] = -1;
            var previousNodes = [];
            var rowIndexes = [];
            var reachableSums = [0];

            candidateRolls.forEach(function(row, rowIndex) {
                var units = qtyUnits(row);
                if (units <= 0 || units > maximum) return;

                var countBeforeRow = reachableSums.length;
                for (var i = countBeforeRow - 1; i >= 0; i--) {
                    var sum = reachableSums[i];
                    var next = sum + units;
                    if (next > maximum || stateNodes[next] !== -2) continue;

                    previousNodes.push(stateNodes[sum]);
                    rowIndexes.push(rowIndex);
                    stateNodes[next] = previousNodes.length - 1;
                    reachableSums.push(next);
                }
            });

            var total = -1;
            for (var distance = 0; distance <= 5000; distance++) {
                var below = target - distance;
                var above = target + distance;
                if (below >= minimum && below <= maximum && stateNodes[below] !== -2) {
                    total = below;
                    break;
                }
                if (above <= maximum && stateNodes[above] !== -2) {
                    total = above;
                    break;
                }
            }
            if (total < 0) return null;

            var selected = [];
            var node = stateNodes[total];
            while (node >= 0) {
                selected.push(candidateRolls[rowIndexes[node]]);
                node = previousNodes[node];
            }
            return selected;
        }

        function suggestRollsForTarget() {
            var target = targetUnits();
            if (!target || candidateRolls.length === 0) {
                selectedRolls.clear();
                updateCreateButton();
                return;
            }

            var suggested = buildSuggestedRolls(target);
            selectedRolls.clear();
            if (suggested) {
                suggested.forEach(function(row) {
                    selectedRolls.set(String(row.ROLL), row);
                });
            }

            document.querySelectorAll('.roll-select-checkbox').forEach(function(checkbox) {
                var row = candidateRolls[Number(checkbox.dataset.rollIndex)];
                checkbox.checked = Boolean(row && selectedRolls.has(String(row.ROLL)));
            });

            updateCreateButton();
            if (!suggested) {
                document.getElementById('suggestionIntro').textContent =
                    'No automatic combination is within 5 KG of the target. Select or remove rolls manually.';
            }
        }

        function searchRolls() {
            var poNumber = poNumberSelect.value;
            var color = colorSelect.value;
            hideMsg();
            resetResults(true);
            if (!poNumber || !color) {
                showMsg('Select both PO Number and Color before searching.', 'error');
                return;
            }

            var requestId = searchRequestId;
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Searching...';
            document.getElementById('searchResults').classList.remove('is-hidden');
            document.getElementById('resultsStatus').textContent = 'Loading matching rolls...';
            apiGet('search_by_po_color', {po_number: poNumber, color: color}).then(function(response) {
                if (requestId !== searchRequestId) return;
                if (!response.success) throw new Error(response.message || 'Could not search rolls');
                candidateRolls = response.rolls || [];
                renderRolls(candidateRolls);
                if (targetUnits()) {
                    suggestRollsForTarget();
                } else {
                    updateCreateButton();
                }
            }).catch(function(error) {
                if (requestId !== searchRequestId) return;
                document.getElementById('resultsStatus').textContent = '';
                showMsg(error.message, 'error');
            }).then(function() {
                if (requestId !== searchRequestId) return;
                searchBtn.disabled = !poNumberSelect.value || !colorSelect.value;
                searchBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Search';
            });
        }

        function toggleRollSelection(row, checked, checkbox) {
            var key = String(row.ROLL);
            if (checked) {
                var currentTotal = Array.from(selectedRolls.values()).reduce(function(sum, selectedRow) {
                    return sum + qtyUnits(selectedRow);
                }, 0);
                if (!targetUnits()) {
                    checkbox.checked = false;
                    showMsg('Enter Target QTY before selecting rolls.', 'error');
                    return;
                }
                if (currentTotal + qtyUnits(row) > targetUnits() + 5000) {
                    checkbox.checked = false;
                    showMsg('This roll would exceed the target by more than 5 KG. Remove a selected roll or increase the target.', 'error');
                    return;
                }
                selectedRolls.set(key, row);
                hideMsg();
            } else {
                selectedRolls.delete(key);
            }
            updateCreateButton();
        }

        function renderSelectedRolls() {
            var rows = Array.from(selectedRolls.values());
            var total = rows.reduce(function(sum, row) {
                return sum + qtyUnits(row);
            }, 0);
            var target = targetUnits();
            var difference = total - target;
            document.getElementById('suggestionIntro').textContent = rows.length
                ? rows.length + ' roll(s), selected total ' + (total / 1000).toFixed(3) +
                    ' KG' + (target ? ' (' + Math.abs(difference / 1000).toFixed(3) + ' KG ' +
                    (difference === 0 ? 'exact' : difference < 0 ? 'below' : 'above') + ' target)' : '')
                : 'Select rolls from the Matching Rolls table above.';
            document.getElementById('suggestionPreviewTitle').textContent =
                rows.length + ' selected roll(s), selected total ' + (total / 1000).toFixed(3) +
                ' KG; target total ' + (target / 1000).toFixed(3) + ' KG';
            document.getElementById('suggestionRolls').innerHTML = rows.length
                ? rows.map(function(row) {
                    return '<tr><td><span class="roll-badge">' + esc(row.ROLL) + '</span></td>' +
                        '<td>' + esc(row.RACK) + '</td><td>' + esc(row.QTY) + '</td>' +
                        '<td><button type="button" class="remove-roll-btn" data-remove-roll="' +
                        esc(row.ROLL) + '"><i class="fa-solid fa-trash"></i> Remove</button></td></tr>';
                }).join('')
                : '<tr class="empty-row"><td colspan="4">Select rolls from the Matching Rolls table above.</td></tr>';
            document.getElementById('selectedRollsFoot').innerHTML =
                '<tr><th colspan="2" class="total-label">Selected total QTY</th>' +
                '<th id="selectedQtyTotal">' + (total / 1000).toFixed(3) + ' KG</th>' +
                '<th>Target: <span id="targetQtyTotal">' +
                (target ? (target / 1000).toFixed(3) + ' KG' : '-') + '</span></th></tr>';

            document.querySelectorAll('[data-remove-roll]').forEach(function(button) {
                button.addEventListener('click', function() {
                    var key = this.dataset.removeRoll;
                    selectedRolls.delete(key);
                    var matchingCheckbox = Array.from(document.querySelectorAll('.roll-select-checkbox'))
                        .find(function(checkbox) {
                            var row = candidateRolls[Number(checkbox.dataset.rollIndex)];
                            return row && String(row.ROLL) === key;
                        });
                    if (matchingCheckbox) matchingCheckbox.checked = false;
                    updateCreateButton();
                });
            });
        }

        function confirmCreate() {
            var selected = Array.from(selectedRolls.values());
            if (!selected.length || confirmCreateBtn.disabled) {
                showMsg('Select rolls whose total is within 5 KG of the target QTY.', 'error');
                return;
            }

            confirmCreateBtn.disabled = true;
            confirmCreateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
            var payload = {
                po_number: poNumberSelect.value,
                color: colorSelect.value,
                target_qty: Number(targetQtyInput.value),
                rolls: selected.map(function(row) { return row.ROLL; })
            };

            apiPost('select_rolls', payload).then(function(response) {
                if (!response.success) throw new Error(response.message || 'Could not select suggested rolls');
                return fetch('ajaxDyeing_batch_card_Insert.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({})
                }).then(readJson);
            }).then(function(response) {
                if (!response.success) throw new Error(response.message || 'Could not create batch card');
                document.getElementById('cardNo').textContent = response.card_no || '-';
                showMsg(response.message + ' Card No: ' + response.card_no, 'success');
                confirmCreateBtn.innerHTML = '<i class="fa-solid fa-check"></i> Batch Card Created';
            }).catch(function(error) {
                showMsg(error.message, 'error');
                confirmCreateBtn.disabled = false;
                confirmCreateBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm &amp; Create Batch Card';
            });
        }

        function refreshColorsForCurrentPo() {
            if (poInputTimer) {
                clearTimeout(poInputTimer);
                poInputTimer = null;
            }
            hideMsg();
            loadColors(poNumberSelect.value.trim());
            searchBtn.disabled = true;
        }

        poNumberSelect.addEventListener('input', function() {
            colorRequestId++;
            colorSelect.disabled = true;
            colorSelect.innerHTML = '<option value="">Select a PO Number first</option>';
            searchBtn.disabled = true;
            resetResults();
            if (poInputTimer) clearTimeout(poInputTimer);

            var poNumber = this.value.trim();
            if (!poNumber) return;

            poInputTimer = setTimeout(function() {
                poInputTimer = null;
                loadColors(poNumber);
            }, 300);
        });

        poNumberSelect.addEventListener('change', refreshColorsForCurrentPo);
        poNumberSelect.addEventListener('search', refreshColorsForCurrentPo);

        colorSelect.addEventListener('change', function() {
            resetResults();
            searchBtn.disabled = !poNumberSelect.value || !this.value;
        });

        searchBtn.addEventListener('click', searchRolls);
        targetQtyInput.addEventListener('input', function() {
            updateCreateButton();
            if (targetQtyTimer) clearTimeout(targetQtyTimer);
            targetQtyTimer = setTimeout(function() {
                targetQtyTimer = null;
                suggestRollsForTarget();
            }, 250);
        });
        targetQtyInput.addEventListener('change', function() {
            if (targetQtyTimer) {
                clearTimeout(targetQtyTimer);
                targetQtyTimer = null;
            }
            suggestRollsForTarget();
        });
        targetQtyInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (targetQtyTimer) {
                    clearTimeout(targetQtyTimer);
                    targetQtyTimer = null;
                }
                suggestRollsForTarget();
            }
        });
        confirmCreateBtn.addEventListener('click', confirmCreate);
        document.getElementById('backBtn').addEventListener('click', function(event) {
            event.preventDefault();
            window.location.href = 'dyeing_batch_card_report.php';
        });

        apiGet('get_batch').then(function(response) {
            if (response.success) {
                document.getElementById('cardNo').textContent =
                    response.next_card_no || response.card_no || '-';
            }
        }).catch(function(error) {
            showMsg(error.message, 'error');
        });
        loadPoNumbers();
    </script>
</body>

</html>