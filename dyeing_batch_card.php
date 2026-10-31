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
            margin-bottom: 14px;
        }

        .filter-field {
            flex: 1;
            min-width: 220px;
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

        .qty-toolbar {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) minmax(150px, 1fr) minmax(180px, 1fr) auto;
            gap: 12px;
            align-items: end;
            padding: 14px;
            margin-bottom: 14px;
            background: #f0fdfa;
            border: 1px solid #ccfbf1;
            border-radius: 12px;
        }

        .selection-value {
            min-height: 42px;
            display: flex;
            align-items: center;
            padding: 10px 12px;
            border: 1px solid #ccfbf1;
            border-radius: 10px;
            background: #fff;
            color: #134e4a;
            font-weight: 700;
        }

        .suggestion-preview {
            margin-top: 16px;
        }

        .suggestion-preview table {
            width: auto;
            min-width: 420px;
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
            .qty-toolbar {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 8px;
            }

            .panel {
                padding: 12px;
            }

            .qty-toolbar {
                grid-template-columns: 1fr;
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
            width: max-content;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        thead th {
            background: #134e4a;
            color: #fff;
            padding: 10px 8px;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
        }

        tbody td {
            padding: 9px 8px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
            vertical-align: middle;
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
            <div class="qty-toolbar is-hidden" id="qtyToolbar">
                <div>
                    <label class="filter-field-label">PO Number</label>
                    <div class="selection-value" id="selectedPoDisplay">-</div>
                </div>
                <div>
                    <label class="filter-field-label">Color</label>
                    <div class="selection-value" id="selectedColorDisplay">-</div>
                </div>
                <div>
                    <label for="targetQtyInput" class="filter-field-label">Target QTY (KG)</label>
                    <input type="number" id="targetQtyInput" class="qty-input" min="0.001" step="0.001" placeholder="Enter required total QTY">
                </div>
                <button class="btn btn-amber is-hidden" id="createCardBtn" type="button" disabled>
                    <i class="fa-solid fa-clipboard-check"></i> Create Batch Card
                </button>
            </div>
            <div class="status-line" id="resultsStatus"></div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
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
                        <tr class="empty-row"><td colspan="21">Select PO Number and Color, then search.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel is-hidden" id="suggestionPanel">
            <div class="panel-title"><i class="fa-solid fa-lightbulb"></i> Rolls for Target QTY</div>
            <p id="suggestionIntro" class="status-line"></p>
            <div class="suggestion-preview is-hidden" id="suggestionPreview">
                <h3 id="suggestionPreviewTitle"></h3>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>ROLL</th><th>Rack</th><th>QTY</th></tr></thead>
                        <tbody id="suggestionRolls"></tbody>
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
        var suggestions = [];
        var selectedSuggestionIndex = -1;
        var colorRequestId = 0;
        var searchRequestId = 0;
        var poInputTimer = null;
        var poNumberSelect = document.getElementById('poNumberSelect');
        var colorSelect = document.getElementById('colorSelect');
        var searchBtn = document.getElementById('searchBtn');
        var createCardBtn = document.getElementById('createCardBtn');
        var targetQtyInput = document.getElementById('targetQtyInput');

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

        function resetResults() {
            searchRequestId++;
            candidateRolls = [];
            suggestions = [];
            selectedSuggestionIndex = -1;
            document.getElementById('searchResults').classList.add('is-hidden');
            document.getElementById('suggestionPanel').classList.add('is-hidden');
            document.getElementById('qtyToolbar').classList.add('is-hidden');
            createCardBtn.classList.add('is-hidden');
            document.getElementById('storeBody').innerHTML =
                '<tr class="empty-row"><td colspan="21">Select PO Number and Color, then search.</td></tr>';
            document.getElementById('resultCount').textContent = '0 roll';
            targetQtyInput.value = '';
            createCardBtn.disabled = true;
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
            rolls.forEach(function(row) {
                html += '<tr>' + fields.map(function(field) {
                    var value = row[field[1]];
                    if (field[0] === 'ROLL') {
                        return '<td><span class="roll-badge">' + esc(value) + '</span></td>';
                    }
                    return '<td>' + esc(value) + '</td>';
                }).join('') + '</tr>';
            });
            document.getElementById('storeBody').innerHTML = html ||
                '<tr class="empty-row"><td colspan="21">No available rolls found for this PO Number and Color.</td></tr>';
            document.getElementById('resultCount').textContent =
                rolls.length + ' available roll' + (rolls.length === 1 ? '' : 's');
        }

        function updateCreateButton() {
            var qty = targetUnits();
            var available = candidateRolls.reduce(function(sum, row) {
                return sum + qtyUnits(row);
            }, 0);
            document.getElementById('qtyToolbar').classList.toggle('is-hidden', candidateRolls.length === 0);
            createCardBtn.classList.toggle('is-hidden', candidateRolls.length === 0 || qty <= 0);
            createCardBtn.disabled = candidateRolls.length === 0 || qty <= 0 || qty > available + 5000;

            var status = document.getElementById('resultsStatus');
            if (candidateRolls.length === 0) {
                status.textContent = 'No available rolls match this PO Number and Color.';
            } else if (qty > available + 5000) {
                status.textContent = 'Available total (' + (available / 1000).toFixed(3) +
                    ' KG) is more than 5 KG below the target.';
            } else if (qty > 0) {
                status.textContent = 'Available total: ' + (available / 1000).toFixed(3) + ' KG.';
            } else {
                status.textContent = 'Enter the required total QTY to find rolls.';
            }
        }

        function searchRolls() {
            var poNumber = poNumberSelect.value;
            var color = colorSelect.value;
            hideMsg();
            resetResults();
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
                document.getElementById('selectedPoDisplay').textContent = poNumber;
                document.getElementById('selectedColorDisplay').textContent = color;
                renderRolls(candidateRolls);
                updateCreateButton();
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

        function buildSuggestion(rows, target) {
            var minimum = Math.max(0, target - 5000);
            var available = rows.reduce(function(sum, row) {
                return sum + qtyUnits(row);
            }, 0);
            var maximum = Math.min(target + 5000, available);
            if (maximum < minimum) return null;

            var stateNodes = new Int32Array(maximum + 1);
            stateNodes.fill(-2);
            stateNodes[0] = -1;

            var previousNodes = [];
            var rowIndexes = [];
            var reachableSums = [0];

            for (var i = 0; i < rows.length; i++) {
                var units = qtyUnits(rows[i]);
                if (units <= 0 || units > maximum) continue;

                var currentReachableCount = reachableSums.length;
                for (var j = currentReachableCount - 1; j >= 0; j--) {
                    var sum = reachableSums[j];
                    var nextSum = sum + units;
                    if (nextSum > maximum || stateNodes[nextSum] !== -2) continue;

                    previousNodes.push(stateNodes[sum]);
                    rowIndexes.push(i);
                    stateNodes[nextSum] = previousNodes.length - 1;
                    reachableSums.push(nextSum);
                }
            }

            var total = -1;
            for (var distance = 0; distance <= 5000; distance++) {
                var lowerTotal = target - distance;
                var higherTotal = target + distance;
                if (lowerTotal >= minimum && lowerTotal <= maximum && stateNodes[lowerTotal] !== -2) {
                    total = lowerTotal;
                    break;
                }
                if (higherTotal <= maximum && stateNodes[higherTotal] !== -2) {
                    total = higherTotal;
                    break;
                }
            }
            if (total < 0) return null;

            var selected = [];
            var node = stateNodes[total];
            while (node >= 0) {
                selected.push(rows[rowIndexes[node]]);
                node = previousNodes[node];
            }
            selected.reverse();
            return {rows: selected, total: total};
        }

        function createSuggestions() {
            hideMsg();
            var target = targetUnits();
            if (!target || candidateRolls.length === 0) {
                showMsg('Enter a valid target QTY after searching for rolls.', 'error');
                return;
            }

            var suggestion = buildSuggestion(candidateRolls, target);
            if (!suggestion) {
                showMsg('No roll combination is available between ' +
                    Math.max(0, target - 5000) / 1000 + ' and ' +
                    ((target + 5000) / 1000).toFixed(3) + ' KG (within 5 KG below or above target).', 'error');
                return;
            }

            suggestions = [suggestion];
            var difference = suggestion.total - target;
            document.getElementById('suggestionIntro').textContent = difference === 0
                ? 'Exact match: ' + (target / 1000).toFixed(3) + ' KG. Review the rolls and confirm.'
                : 'Target: ' + (target / 1000).toFixed(3) + ' KG. Closest available total: ' +
                    (suggestion.total / 1000).toFixed(3) + ' KG (' +
                    Math.abs(difference / 1000).toFixed(3) + ' KG ' +
                    (difference < 0 ? 'below' : 'above') + ' target).';
            document.getElementById('suggestionPanel').classList.remove('is-hidden');
            renderSuggestion(0);
            document.getElementById('suggestionPanel').scrollIntoView({behavior: 'smooth', block: 'start'});
        }

        function renderSuggestion(index) {
            selectedSuggestionIndex = index;
            var suggestion = suggestions[index];
            if (!suggestion) return;

            document.getElementById('suggestionPreview').classList.remove('is-hidden');
            document.getElementById('suggestionPreviewTitle').textContent =
                suggestion.rows.length + ' roll(s), total ' +
                (suggestion.total / 1000).toFixed(3) + ' KG';
            document.getElementById('suggestionRolls').innerHTML = suggestion.rows.map(function(row) {
                return '<tr><td><span class="roll-badge">' + esc(row.ROLL) + '</span></td>' +
                    '<td>' + esc(row.RACK) + '</td><td>' + esc(row.QTY) + '</td></tr>';
            }).join('');
        }

        function confirmCreate() {
            var suggestion = suggestions[selectedSuggestionIndex];
            if (!suggestion) {
                showMsg('Choose a roll suggestion first.', 'error');
                return;
            }

            var button = document.getElementById('confirmCreateBtn');
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
            var payload = {
                po_number: poNumberSelect.value,
                color: colorSelect.value,
                target_qty: Number(targetQtyInput.value),
                rolls: suggestion.rows.map(function(row) { return row.ROLL; })
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
                createCardBtn.disabled = true;
                showMsg(response.message + ' Card No: ' + response.card_no, 'success');
                document.getElementById('suggestionPanel').classList.add('is-hidden');
                button.innerHTML = '<i class="fa-solid fa-check"></i> Batch Card Created';
            }).catch(function(error) {
                showMsg(error.message, 'error');
                button.disabled = false;
                button.innerHTML = '<i class="fa-solid fa-check"></i> Confirm &amp; Create Batch Card';
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
        createCardBtn.addEventListener('click', createSuggestions);
        targetQtyInput.addEventListener('input', function() {
            document.getElementById('suggestionPanel').classList.add('is-hidden');
            updateCreateButton();
        });
        targetQtyInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (!createCardBtn.disabled) createSuggestions();
            }
        });
        document.getElementById('confirmCreateBtn').addEventListener('click', confirmCreate);
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