<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit;
}

// Start each page load with a fresh fabric batch so stale selections never linger
if (!isset($_SESSION['dyeing_batch']) || !is_array($_SESSION['dyeing_batch'])) {
    $_SESSION['dyeing_batch'] = [];
}
$_SESSION['dyeing_batch']['rolls'] = [];
unset($_SESSION['dyeing_batch']['items']);
unset($_SESSION['dyeing_batch']['card_no']);
unset($_SESSION['dyeing_batch']['created_at']);
unset($_SESSION['dyeing_batch']['target_qty']);
unset($_SESSION['dyeing_batch']['category_quantities']);
unset($_SESSION['dyeing_batch']['dymcno']);
unset($_SESSION['dyeing_batch']['po_number']);
unset($_SESSION['dyeing_batch']['color']);
unset($_SESSION['dyeing_batch']['processes']);
unset($_SESSION['dyeing_batch']['saved']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dyeing | Batch Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .batch-search-layout {
            display: grid;
            grid-template-columns: minmax(440px, 1fr) minmax(620px, 1.2fr);
            align-items: stretch;
            gap: 16px;
        }

        #batchSearchPanel {
            container-type: inline-size;
        }

        .search-controls {
            display: grid;
            gap: 12px;
            min-width: 0;
        }

        .search-po-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .search-action-row {
            display: flex;
            justify-content: flex-end;
            align-items: end;
            gap: 10px;
        }

        .fabric-search-field {
            flex: 1;
            min-width: 260px;
        }

        .fabric-search-field .filter-select {
            min-height: 42px;
        }

        .process-panel {
            min-width: 0;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #f8fffd;
        }

        .process-panel h3 {
            margin: 0 0 10px;
            color: #134e4a;
            font-size: .95rem;
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr)) minmax(180px, 1.2fr);
            gap: 8px 10px;
        }

        .others-detail input {
            width: 100%;
            min-height: 34px;
            padding: 7px 9px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #0f172a;
            font-size: .82rem;
            outline: none;
        }

        .others-detail input:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, .12);
        }

        .others-detail input:disabled {
            background: #f1f5f9;
        }

        .process-others {
            grid-column: 4;
            grid-row: 3;
        }

        .process-nonsilicon {
            grid-column: 3;
            grid-row: 4;
        }

        .process-peach {
            grid-column: 4;
            grid-row: 1;
        }

        .process-shearing {
            grid-column: 4;
            grid-row: 2;
        }

        .others-detail {
            min-width: 0;
            grid-column: 4;
            grid-row: 4;
        }

        .others-detail input {
            min-width: 0;
        }

        .process-option {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            min-width: 0;
            color: #334155;
            font-size: .8rem;
            font-weight: 650;
            line-height: 1.3;
            white-space: nowrap;
            cursor: pointer;
        }

        .process-option input {
            flex: 0 0 auto;
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #0d9488;
        }

        .process-panel:disabled {
            opacity: .58;
        }

        .process-panel:disabled .process-option {
            cursor: not-allowed;
        }

        .filter-field {
            min-width: 0;
        }

        .filter-field.target-qty-field {
            min-width: 0;
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

        .is-hidden {
            display: none !important;
        }

        .loading-note {
            color: #0f766e;
            font-weight: 600;
        }

        @media (max-width: 720px) {
            .batch-search-layout {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @container (max-width: 1100px) {
            .batch-search-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .process-panel {
                width: 100%;
            }
        }

        @media (max-width: 700px) {
            .process-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .process-others,
            .others-detail,
            .process-nonsilicon,
            .process-peach,
            .process-shearing {
                grid-column: auto;
                grid-row: auto;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 8px;
            }

            .panel {
                padding: 12px;
            }

            .search-po-row,
            .search-action-row {
                grid-template-columns: minmax(0, 1fr);
            }

            .search-action-row > .btn {
                width: 100%;
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
            align-self: end;
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
            min-width: 1750px;
        }

        #searchResults th,
        #searchResults td {
            padding: 7px 6px;
            font-size: 0.82rem;
        }

        #searchResults .selection-cell {
            width: 32px;
            text-align: center;
        }

        #searchResults .selection-checkbox {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #0d9488;
        }

        #searchResults .fabric-category-select {
            width: 82px;
            min-width: 82px;
            max-width: 82px;
            padding: 6px 7px;
        }

        #searchResults .batch-qty-input {
            width: 82px;
            min-width: 82px;
            padding: 7px 6px;
        }

        #searchResults .meta-cell {
            max-width: 115px;
            overflow: hidden;
            text-overflow: ellipsis;
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

        tfoot .selected-total-value {
            text-align: left;
            padding-left: 24px;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #ecfdf5;
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
            <a href="initialPage.php" class="back-btn" id="backBtn"><i class="fas fa-arrow-left"></i> Back</a>

            <h1><i class="fa-solid fa-vial-circle-check"></i> Dyeing Batch Card</h1>
            <span class="card-no-badge"><i class="fa-solid fa-hashtag"></i> Batch Card: <span id="cardNo">-</span></span>
        </div>

        <div id="msgBox" class="msg-box" role="status" aria-live="polite"></div>

        <div class="panel" id="batchSearchPanel">
            <div class="panel-title"><i class="fa-solid fa-filter"></i> Find Fabric Quantity for Batch Card</div>
            <div class="batch-search-layout">
                <div class="search-controls">
                    <div class="search-po-row">
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
                        <div class="filter-field">
                            <label for="dyeingMcnoSelect">Dyeing MC No (Required)</label>
                            <select id="dyeingMcnoSelect" class="filter-select" disabled>
                                <option value="">Loading dyeing machines...</option>
                            </select>
                        </div>
                    </div>
                    <div class="search-action-row search-row">
                        <div class="filter-field fabric-search-field">
                            <label for="fabricSearchInput">Search Fabrics Type / SONO / Yarn Type / Yarn Count / Yarn Brand</label>
                            <input type="search" id="fabricSearchInput" class="filter-select"
                                placeholder="Search fabrics and yarn details" autocomplete="off">
                        </div>
                        <button class="btn btn-blue" id="searchBtn" type="button" disabled>
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                    </div>
                </div>
                <fieldset class="process-panel" id="processPanel" disabled>
                    <h3>Dyeing Process</h3>
                    <div class="process-grid">
                        <label class="process-option"><input type="checkbox" data-process-field="SILICON"> Silicon</label>
                        <label class="process-option"><input type="checkbox" data-process-field="ENZYME"> Enzyme</label>
                        <label class="process-option"><input type="checkbox" data-process-field="AOP"> AOP</label>
                        <label class="process-option process-nonsilicon"><input type="checkbox" data-process-field="NONSILICON"> Non-Silicon</label>
                        <label class="process-option"><input type="checkbox" data-process-field="NONENZYME"> Non-Enzyme</label>
                        <label class="process-option"><input type="checkbox" data-process-field="SOFTENER"> Softener</label>
                        <label class="process-option"><input type="checkbox" data-process-field="NWASH"> Normal Wash (YD)</label>
                        <label class="process-option process-peach"><input type="checkbox" data-process-field="PEACH"> Peach</label>
                        <label class="process-option"><input type="checkbox" data-process-field="NONSOFTENER"> Non-Softener</label>
                        <label class="process-option"><input type="checkbox" data-process-field="EWASH"> Enzyme Wash (YD)</label>
                        <label class="process-option"><input type="checkbox" data-process-field="BRUSH"> Brush</label>
                        <label class="process-option process-shearing"><input type="checkbox" data-process-field="SHEARING"> Shearing</label>
                        <label class="process-option"><input type="checkbox" data-process-field="SINGEING"> Singeing</label>
                        <label class="process-option"><input type="checkbox" data-process-field="HEAT_SET"> Heat Set</label>
                        <label class="process-option process-others"><input type="checkbox" data-process-field="OTHERS"> Others</label>
                        <div class="others-detail is-hidden" id="othersDetail">
                            <input type="text" id="othersProcessInput" maxlength="100"
                                placeholder="Enter other process" aria-label="Other dyeing process" disabled>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="panel is-hidden" id="searchResults">
            <div class="panel-title">
                <i class="fa-solid fa-table-list"></i> Fabric Type Totals (PO + Color)
                <span class="card-no-badge" id="resultCount">0 fabric type</span>
            </div>
            <div class="status-line" id="resultsStatus"></div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th class="selection-cell" aria-label="Select"></th>
                            <th>F/T</th>
                            <th>Fabrics Type</th>
                            <th>Knitting QTY (KG)</th>
                            <th>Available QTY (KG)</th>
                            <th>Batch QTY (KG)</th>
                            <th>Color</th>
                            <th>SONO</th>
                            <th>Buyer</th>
                            <th>Style</th>
                            <th>MC Dia</th>
                            <th>SL/VQ</th>
                            <th>Finish GSM</th>
                            <th>Finish Dia</th>
                            <th>Yarn Type</th>
                            <th>Yarn Count</th>
                            <th>Yarn Brand</th>
                            <th>Lot No</th>
                        </tr>
                    </thead>
                    <tbody id="storeBody">
                    <tr class="empty-row"><td colspan="18">Select PO Number and Color, then search.</td></tr>
                    </tbody>
                    <tfoot id="storeFoot"></tfoot>
                </table>
            </div>
            <div class="footer-actions">
                <button class="btn btn-green" id="confirmCreateBtn" type="button" disabled>
                    <i class="fa-solid fa-check"></i> Confirm &amp; Create Batch Card
                </button>
            </div>
        </div>
    </div>

    <script>
        var fabricTotals = [];
        var fabricAllocations = [];
        var nextFabricAllocationId = 1;
        var colorRequestId = 0;
        var colorLoadPoNumber = '';
        var loadedColorPoNumber = '';
        var colorsLoading = false;
        var searchRequestId = 0;
        var searchInProgress = false;
        var loadedPoNumber = '';
        var loadedColor = '';
        var poInputTimer = null;
        var batchMessageTimer = null;
        var poNumberSelect = document.getElementById('poNumberSelect');
        var colorSelect = document.getElementById('colorSelect');
        var fabricSearchInput = document.getElementById('fabricSearchInput');
        var searchBtn = document.getElementById('searchBtn');
        var dyeingMcnoSelect = document.getElementById('dyeingMcnoSelect');
        var confirmCreateBtn = document.getElementById('confirmCreateBtn');
        var processPanel = document.getElementById('processPanel');
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

        function showBatchPopup(message, type) {
            var box = document.getElementById('msgBox');
            if (batchMessageTimer) clearTimeout(batchMessageTimer);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type === 'error' ? 'error' : 'success',
                    title: type === 'error' ? 'Batch Card Not Created' : 'Successfully Saved!',
                    text: message,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: type !== 'success',
                    allowEscapeKey: type !== 'success'
                }).then(function() {
                    if (type === 'success') window.location.reload();
                });
                return;
            }

            box.className = 'msg-box ' + (type === 'error' ? 'msg-error' : 'msg-success');
            box.textContent = message;
            batchMessageTimer = setTimeout(function() {
                if (type === 'success') {
                    window.location.reload();
                    return;
                }
                hideMsg();
                batchMessageTimer = null;
            }, 3000);
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

        function setProcessSelectionEnabled(enabled) {
            processPanel.disabled = !enabled;
            if (!enabled) {
                processPanel.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                    checkbox.checked = false;
                });
                document.getElementById('othersProcessInput').value = '';
                document.getElementById('othersProcessInput').disabled = true;
                document.getElementById('othersDetail').classList.add('is-hidden');
            }
        }

        function getSelectedProcesses() {
            var selected = {};
            processPanel.querySelectorAll('input[data-process-field]:checked').forEach(function(checkbox) {
                var field = checkbox.dataset.processField;
                selected[field] = field === 'OTHERS'
                    ? (document.getElementById('othersProcessInput').value.trim() || 'Others')
                    : field;
            });
            return selected;
        }

        function resetResults() {
            searchRequestId++;
            searchInProgress = false;
            fabricTotals = [];
            fabricAllocations = [];
            nextFabricAllocationId = 1;
            loadedPoNumber = '';
            loadedColor = '';
            setProcessSelectionEnabled(false);
            document.getElementById('searchResults').classList.add('is-hidden');
            document.getElementById('storeBody').innerHTML =
                '<tr class="empty-row"><td colspan="18">Select PO Number and Color, then search.</td></tr>';
            document.getElementById('storeFoot').innerHTML = '';
            document.getElementById('resultCount').textContent = '0 fabric type';
            document.getElementById('resultsStatus').textContent = '';
            confirmCreateBtn.disabled = true;
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
                    showMsg('No PO Numbers were found in knitting_store.', 'error');
                }
            }).catch(function(error) {
                showMsg(error.message, 'error');
            });
        }

        function loadColors(poNumber) {
            if (colorsLoading && colorLoadPoNumber === poNumber) return;
            if (loadedColorPoNumber === poNumber && !colorSelect.disabled) return;
            var requestId = ++colorRequestId;
            colorLoadPoNumber = poNumber;
            loadedColorPoNumber = '';
            colorsLoading = true;
            resetResults();
            colorSelect.disabled = true;
            searchBtn.disabled = true;
            colorSelect.innerHTML = '<option value="">Loading Colors...</option>';
            if (!poNumber) {
                colorSelect.innerHTML = '<option value="">Select a PO Number first</option>';
                colorsLoading = false;
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
                loadedColorPoNumber = poNumber;
                colorsLoading = false;
            }).catch(function(error) {
                if (requestId !== colorRequestId) return;
                colorSelect.innerHTML = '<option value="">Unable to load Colors</option>';
                colorsLoading = false;
                loadedColorPoNumber = '';
                showMsg(error.message, 'error');
            });
        }

        function formatQty(value) {
            var qty = Number(value);
            return Number.isFinite(qty)
                ? qty.toFixed(3).replace(/\.?0+$/, '')
                : '';
        }

        function createFabricAllocation() {
            return {id: nextFabricAllocationId++, selected: false, category: '', quantity: ''};
        }

        function getFabricAllocation(index, allocationId) {
            var allocations = fabricAllocations[index] || [];
            return allocations.find(function(allocation) {
                return allocation.id === allocationId;
            }) || null;
        }

        function availableBeforeAllocation(index, allocationId) {
            var row = fabricTotals[index];
            var allocations = fabricAllocations[index] || [];
            var remaining = row && Number(row.TOTAL_QTY);
            if (!Number.isFinite(remaining)) return 0;

            var found = false;
            allocations.forEach(function(allocation) {
                if (found) return;
                if (allocation.id === allocationId) {
                    found = true;
                    return;
                }
                if (allocation.selected) {
                    var quantity = Number(allocation.quantity);
                    if (Number.isFinite(quantity) && quantity > 0) {
                        remaining = Math.max(0, remaining - quantity);
                    }
                }
            });
            return Math.max(0, remaining);
        }

        function ensureNextFabricAllocation(index) {
            var allocations = fabricAllocations[index] || [];
            var activeCategories = allocations.filter(function(allocation) {
                return allocation.selected && allocation.category;
            }).length;
            var hasEmptyRow = allocations.some(function(allocation) {
                return !allocation.selected && !allocation.category && !allocation.quantity;
            });
            var available = Number(fabricTotals[index].TOTAL_QTY);
            allocations.forEach(function(allocation) {
                if (allocation.selected) {
                    var quantity = Number(allocation.quantity);
                    if (Number.isFinite(quantity) && quantity > 0) {
                        available -= quantity;
                    }
                }
            });
            if (activeCategories > 0 && activeCategories < 3 && !hasEmptyRow && available > 0.000001) {
                allocations.push(createFabricAllocation());
            }
        }

        function trimEmptyFabricAllocations(index) {
            var allocations = fabricAllocations[index] || [];
            while (allocations.length > 1) {
                var last = allocations[allocations.length - 1];
                if (last.selected || last.category || last.quantity) break;
                allocations.pop();
            }
        }

        function selectedFabricItems() {
            var items = [];
            var valid = true;
            var usedCategories = {};
            fabricTotals.forEach(function(row, index) {
                var fabricKey = String(row.FTYPE || '').trim().toUpperCase();
                (fabricAllocations[index] || []).forEach(function(allocation) {
                    if (!allocation.selected) return;

                    var category = allocation.category;
                    var quantity = Number(allocation.quantity);
                    var key = fabricKey + '|' + category;
                    var available = availableBeforeAllocation(index, allocation.id);
                    if (['BODY', 'RIB', 'NECK'].indexOf(category) === -1 ||
                        !Number.isFinite(quantity) || quantity <= 0 ||
                        quantity - available > 0.000001 || usedCategories[key]) {
                        valid = false;
                        return;
                    }
                    usedCategories[key] = true;
                    items.push({
                        category: category,
                        fabric_type: String(row.FTYPE || '').trim(),
                        quantity: Math.round(quantity * 1000) / 1000
                    });
                });
            });
            return {items: items, valid: valid};
        }

        function updateAllocationAvailability() {
            fabricTotals.forEach(function(row, index) {
                (fabricAllocations[index] || []).forEach(function(allocation) {
                    var tableRow = document.querySelector(
                        '.allocation-row[data-fabric-index="' + index + '"][data-allocation-id="' + allocation.id + '"]'
                    );
                    if (!tableRow) return;

                    var available = availableBeforeAllocation(index, allocation.id);
                    var qtyInput = tableRow.querySelector('.batch-qty-input');
                    var categorySelect = tableRow.querySelector('.fabric-category-select');
                    var checkbox = tableRow.querySelector('.fabric-selection-checkbox');
                    tableRow.querySelector('.available-qty').textContent = formatQty(available);
                    qtyInput.max = formatQty(available);
                    checkbox.disabled = available <= 0 && !allocation.selected;
                    qtyInput.disabled = !allocation.selected || !allocation.category || available <= 0;
                    categorySelect.disabled = !allocation.selected;
                });
            });
        }

        function visibleFabricIndices() {
            var query = fabricSearchInput.value.trim().toLocaleLowerCase();
            return fabricTotals.reduce(function(indices, row, index) {
                var matches = !query || ['FTYPE', 'SONO', 'YTYPE', 'YCOUNT', 'YBRAND'].some(function(field) {
                    return String(row[field] || '').toLocaleLowerCase().indexOf(query) !== -1;
                });
                var selected = (fabricAllocations[index] || []).some(function(allocation) {
                    return allocation.selected;
                });
                if (matches || selected) indices.push(index);
                return indices;
            }, []);
        }

        function renderFabricTotals() {
            var categories = [
                ['BODY', 'Body'],
                ['RIB', 'RIB'],
                ['NECK', 'Neck']
            ];
            var indices = visibleFabricIndices();
            var html = indices.map(function(index) {
                var row = fabricTotals[index];
                var allocations = fabricAllocations[index] || [];
                return allocations.map(function(allocation) {
                    var duplicateCategories = allocations.filter(function(other) {
                        return other.id !== allocation.id && other.selected && other.category;
                    }).map(function(other) {
                        return other.category;
                    });
                    var available = formatQty(availableBeforeAllocation(index, allocation.id));
                    var availableAmount = Number(available);
                    var batchQuantity = allocation.quantity === '' ? '' : esc(allocation.quantity);
                    var categoryOptions = categories.map(function(category) {
                        var selected = allocation.category === category[0] ? ' selected' : '';
                        var disabled = duplicateCategories.indexOf(category[0]) !== -1 ? ' disabled' : '';
                        return '<option value="' + category[0] + '"' + selected + disabled + '>' +
                            category[1] + '</option>';
                    }).join('');
                    function metaCell(value) {
                        var content = esc(value);
                        return '<td class="meta-cell" title="' + content + '">' + content + '</td>';
                    }
                    return '<tr class="allocation-row" data-fabric-index="' + index +
                        '" data-allocation-id="' + allocation.id + '">' +
                        '<td class="selection-cell"><input type="checkbox" class="selection-checkbox fabric-selection-checkbox"' +
                        ' aria-label="Select ' + esc(row.FTYPE) + ' for F/T allocation"' +
                        (allocation.selected ? ' checked' : '') +
                        (availableAmount <= 0 && !allocation.selected ? ' disabled' : '') + '></td>' +
                        '<td><select class="filter-select fabric-category-select" aria-label="F/T for ' +
                        esc(row.FTYPE) + '"' + (allocation.selected ? '' : ' disabled') + '>' +
                        '<option value="">Select F/T</option>' + categoryOptions + '</select></td>' +
                        metaCell(row.FTYPE) +
                        '<td>' + formatQty(row.TOTAL_QTY) + '</td>' +
                        '<td class="available-qty">' + available + '</td>' +
                        '<td><input type="number" class="qty-input batch-qty-input" min="0.001" max="' +
                        esc(available) + '" step="0.001" value="' + batchQuantity +
                        '" placeholder="Max ' + esc(available) + '" aria-label="Batch QTY for ' +
                        esc(row.FTYPE) + '"' + (!allocation.selected || !allocation.category || Number(available) <= 0
                            ? ' disabled'
                            : '') + '></td>' +
                        metaCell(row.COLOR) + metaCell(row.SONO) + metaCell(row.BUYER) +
                        metaCell(row.STYLE) + metaCell(row.MCDIA) + metaCell(row.SL) +
                        metaCell(row.FGSM) + metaCell(row.FDIA) + metaCell(row.YTYPE) +
                        metaCell(row.YCOUNT) + metaCell(row.YBRAND) + metaCell(row.LOT_NO) +
                        '</tr>';
                }).join('');
            }).join('');
            document.getElementById('storeBody').innerHTML = html ||
                '<tr class="empty-row"><td colspan="18">' +
                (fabricTotals.length
                    ? 'No matching fabric types. Selected rows remain visible.'
                    : 'No fabric types found for this PO Number and Color.') +
                '</td></tr>';
            document.getElementById('resultCount').textContent =
                indices.length + ' of ' + fabricTotals.length +
                ' fabric type' + (fabricTotals.length === 1 ? '' : 's');

            document.querySelectorAll('.allocation-row').forEach(function(tableRow) {
                var index = Number(tableRow.dataset.fabricIndex);
                var allocationId = Number(tableRow.dataset.allocationId);
                var allocation = getFabricAllocation(index, allocationId);
                var checkbox = tableRow.querySelector('.fabric-selection-checkbox');
                var categorySelect = tableRow.querySelector('.fabric-category-select');
                var qtyInput = tableRow.querySelector('.batch-qty-input');

                checkbox.addEventListener('change', function() {
                    allocation.selected = checkbox.checked;
                    if (!allocation.selected) {
                        allocation.category = '';
                        allocation.quantity = '';
                        trimEmptyFabricAllocations(index);
                    }
                    renderFabricTotals();
                });
                categorySelect.addEventListener('change', function() {
                    allocation.category = categorySelect.value;
                    ensureNextFabricAllocation(index);
                    renderFabricTotals();
                });
                qtyInput.addEventListener('input', function() {
                    allocation.quantity = qtyInput.value;
                    updateCreateButton();
                });
            });
            updateCreateButton();
        }

        function updateCreateButton() {
            updateAllocationAvailability();
            var selection = selectedFabricItems();
            var selectedTotal = selection.items.reduce(function(total, item) {
                return total + Number(item.quantity);
            }, 0);
            var sourceTotal = fabricTotals.reduce(function(total, row) {
                var qty = Number(row.TOTAL_QTY);
                return total + (Number.isFinite(qty) ? qty : 0);
            }, 0);
            var hasSelectedProcess = processPanel.querySelectorAll('input[data-process-field]:checked').length > 0;
            confirmCreateBtn.disabled = !fabricTotals.length || !selection.items.length ||
                !selection.valid || !dyeingMcnoSelect.value || !hasSelectedProcess;
            confirmCreateBtn.title = !dyeingMcnoSelect.value
                ? 'Select a Dyeing MC No to enable batch creation.'
                : !selection.items.length || !selection.valid
                    ? 'Select rows, choose F/T, and enter valid Batch QTY values.'
                    : !hasSelectedProcess
                        ? 'Select at least one Dyeing Process.'
                        : 'Create the batch card.';

            if (!fabricTotals.length) {
                document.getElementById('resultsStatus').textContent =
                    'No fabric types found for this PO Number and Color.';
            } else {
                var visibleCount = visibleFabricIndices().length;
                document.getElementById('resultsStatus').textContent =
                    'Knitting total: ' + formatQty(sourceTotal) + ' KG. Selected batch total: ' +
                    formatQty(selectedTotal) + ' KG across ' + selection.items.length + ' allocation(s).' +
                    (visibleCount < fabricTotals.length
                        ? ' Showing ' + visibleCount + ' of ' + fabricTotals.length + ' fabric types.'
                        : '');
            }
            document.getElementById('storeFoot').innerHTML = fabricTotals.length
                ? '<tr><th colspan="3" class="total-label">Totals:</th><th>' +
                    formatQty(sourceTotal) + ' KG</th><th></th><th>' + formatQty(selectedTotal) +
                    ' KG selected</th><th colspan="12"></th></tr>'
                : '';
        }

        function searchFabricTotals() {
            var poNumber = poNumberSelect.value;
            var color = colorSelect.value;
            hideMsg();
            if (!poNumber || !color) {
                showMsg('Select both PO Number and Color before searching.', 'error');
                return;
            }

            if (loadedPoNumber === poNumber && loadedColor === color && fabricTotals.length) {
                renderFabricTotals();
                return;
            }

            resetResults();
            var requestId = searchRequestId;
            searchInProgress = true;
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Searching...';
            document.getElementById('searchResults').classList.remove('is-hidden');
            document.getElementById('resultsStatus').textContent = 'Loading fabric totals...';
            apiGet('fabric_totals', {po_number: poNumber, color: color}).then(function(response) {
                if (requestId !== searchRequestId) return;
                if (!response.success) throw new Error(response.message || 'Could not load fabric totals');
                fabricTotals = response.fabrics || [];
                loadedPoNumber = poNumber;
                loadedColor = color;
                nextFabricAllocationId = 1;
                fabricAllocations = fabricTotals.map(function() {
                    return [createFabricAllocation()];
                });
                renderFabricTotals();
                setProcessSelectionEnabled(fabricTotals.length > 0);
                updateCreateButton();
            }).catch(function(error) {
                if (requestId !== searchRequestId) return;
                document.getElementById('resultsStatus').textContent = '';
                showMsg(error.message, 'error');
            }).then(function() {
                if (requestId !== searchRequestId) return;
                searchInProgress = false;
                searchBtn.disabled = !poNumberSelect.value || !colorSelect.value;
                searchBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Search';
            });
        }

        function confirmCreate() {
            var selection = selectedFabricItems();
            var selected = selection.items;
            var processes = getSelectedProcesses();
            if (!dyeingMcnoSelect.value) {
                showMsg('Select a Dyeing MC No before creating the batch card.', 'error');
                return;
            }
            if (Object.keys(processes).length === 0) {
                showMsg('Select at least one Dyeing Process before creating the batch card.', 'error');
                return;
            }
            if (!selected.length || !selection.valid || confirmCreateBtn.disabled) {
                showMsg('Choose an F/T and enter a valid Batch QTY for at least one fabric type.', 'error');
                return;
            }

            confirmCreateBtn.disabled = true;
            confirmCreateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
            var payload = {
                po_number: poNumberSelect.value,
                color: colorSelect.value,
                dymcno: dyeingMcnoSelect.value,
                processes: processes,
                items: selected
            };

            apiPost('select_fabrics', payload).then(function(response) {
                if (!response.success) throw new Error(response.message || 'Could not validate selected fabric quantities');
                return fetch('ajaxDyeing_batch_card_Insert.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({})
                }).then(readJson).then(function(insertResponse) {
                    if (!insertResponse.success) {
                        throw new Error(insertResponse.message || 'Database insert failed. Batch Card was not created.');
                    }
                    return insertResponse;
                });
            }).then(function(response) {
                document.getElementById('cardNo').textContent = response.card_no || '-';
                var successMessage = 'Batch Created Successfully!\n' + response.count +
                    ' fabric line(s) saved in the database.\nBatch Card No: ' + response.card_no;
                showMsg(successMessage.replace(/\n/g, ' '), 'success');
                showBatchPopup(successMessage.replace(/\n/g, ' '), 'success');
                confirmCreateBtn.innerHTML = '<i class="fa-solid fa-check"></i> Batch Card Created';
            }).catch(function(error) {
                showMsg(error.message, 'error');
                showBatchPopup('Batch Card was NOT created. ' + error.message, 'error');
                confirmCreateBtn.disabled = false;
                confirmCreateBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm &amp; Create Batch Card';
            });
        }

        function loadDyeingMachines() {
            dyeingMcnoSelect.disabled = true;
            dyeingMcnoSelect.innerHTML = '<option value="">Loading dyeing machines...</option>';
            apiGet('dyeing_machines').then(function(response) {
                if (!response.success) throw new Error(response.message || 'Could not load dyeing machines');
                dyeingMcnoSelect.innerHTML = '<option value="">Select Dyeing MC No</option>';
                (response.machines || []).forEach(function(machine) {
                    var option = document.createElement('option');
                    option.value = machine.DMCNO;
                    option.textContent = machine.DMCNO + ' | ' + machine.DCAPACITY;
                    dyeingMcnoSelect.appendChild(option);
                });
                dyeingMcnoSelect.disabled = !response.machines || response.machines.length === 0;
                if (!response.machines || response.machines.length === 0) {
                    dyeingMcnoSelect.innerHTML = '<option value="">No dyeing machines found</option>';
                    showMsg('No dyeing machines found in dyeing_mcno.', 'error');
                }
                updateCreateButton();
            }).catch(function(error) {
                dyeingMcnoSelect.innerHTML = '<option value="">Unable to load dyeing machines</option>';
                dyeingMcnoSelect.disabled = true;
                showMsg(error.message, 'error');
            });
        }

        function refreshColorsForCurrentPo() {
            if (poInputTimer) {
                clearTimeout(poInputTimer);
                poInputTimer = null;
            }
            hideMsg();
            var poNumber = poNumberSelect.value.trim();
            if (poNumber === loadedColorPoNumber && !colorSelect.disabled) return;
            if (poNumber === colorLoadPoNumber && colorsLoading) return;
            loadColors(poNumber);
        }

        poNumberSelect.addEventListener('input', function() {
            colorRequestId++;
            colorLoadPoNumber = '';
            loadedColorPoNumber = '';
            colorsLoading = false;
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
            if (poNumberSelect.value.trim() && this.value) {
                searchFabricTotals();
            } else {
                resetResults();
                searchBtn.disabled = true;
            }
        });

        searchBtn.addEventListener('click', searchFabricTotals);
        fabricSearchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchFabricTotals();
            }
        });
        processPanel.querySelectorAll('input[data-process-field]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.dataset.processField === 'OTHERS') {
                    var othersInput = document.getElementById('othersProcessInput');
                    var enabled = this.checked;
                    othersInput.disabled = !enabled;
                    document.getElementById('othersDetail').classList.toggle('is-hidden', !enabled);
                    if (!enabled) othersInput.value = '';
                }
                updateCreateButton();
            });
        });
        document.getElementById('othersProcessInput').addEventListener('input', updateCreateButton);
        dyeingMcnoSelect.addEventListener('change', updateCreateButton);
        confirmCreateBtn.addEventListener('click', confirmCreate);
        document.getElementById('backBtn').addEventListener('click', function(event) {
            event.preventDefault();
            window.location.href = 'initialPage.php';
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
        loadDyeingMachines();
    </script>
</body>

</html>