<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

unset($_SESSION['knitting_delivery_manual_entry_authorized']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting | Batch Delivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 18px;
            background: linear-gradient(135deg, #d9f1ed, #e5f1fb);
            color: #123b39;
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
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #0f766e 0%, #0d8a80 50%, #0f9b8e 100%);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .1);
            color: #fff;
        }
        .header h1 {
            grid-column: 2;
            grid-row: 1;
            text-align: center;
            font-size: clamp(1.2rem, 3vw, 1.5rem);
        }
        .refresh-button {
            grid-column: 3;
            grid-row: 1;
            justify-self: end;
            min-height: 40px;
            padding: 8px 12px;
            font-size: .85rem;
        }
        h1 { margin: 0; font-size: 1.5rem; }
        .back {
            display: inline-block;
            justify-self: start;
            padding: 9px 14px;
            color: #fff;
            background: #000;
            border: 2px solid #000;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            white-space: nowrap;
            transition: 0.2s ease;
        }
        .back:hover { color: #000; background: #fff; border-color: #000; }
        .panel { padding: 18px; margin-bottom: 14px; }
        .panel-title { margin: 0 0 14px; font-size: 1.08rem; font-weight: 800; }
        .batch-form, .scan-form { display: flex; flex-wrap: wrap; align-items: end; gap: 10px; }
        .field { flex: 1; min-width: 220px; }
        .roll-input-group { display: flex; width: 100%; }
        .roll-input-group input { flex: 1; min-width: 0; border-radius: 8px 0 0 8px; }
        .roll-input-group button {
            flex: 0 0 auto;
            border-radius: 0 8px 8px 0;
        }
        label { display: block; margin-bottom: 6px; font-weight: 700; }
        input {
            width: 100%;
            min-height: 46px;
            padding: 10px 12px;
            border: 1px solid #afc8c4;
            border-radius: 8px;
            font-size: 1rem;
        }
        input:focus { outline: 2px solid #54b7a8; border-color: #159685; }
        button {
            min-height: 44px;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            color: #fff;
            background: #087f70;
            font-weight: 800;
            font-size: .98rem;
            cursor: pointer;
        }
        button:hover { filter: brightness(.94); }
        button:disabled { background: #94a3b8; cursor: not-allowed; }
        .add-roll-button { flex: 0 0 auto; }
        .confirm-button { background: #059669; }
        .message { display: none; padding: 11px 13px; margin-top: 12px; border-radius: 8px; font-weight: 700; }
        .message.info { display: block; color: #075985; background: #e0f2fe; }
        .message.error { display: block; color: #991b1b; background: #fee2e2; }
        .message.success { display: block; color: #065f46; background: #d1fae5; }
        .is-hidden { display: none !important; }
        .batch-summary { display: flex; flex-wrap: wrap; gap: 10px; margin: 0 0 14px; }
        .summary-item { padding: 9px 12px; border-radius: 8px; background: #ecfdf5; font-weight: 800; }
        .batch-selection { display: flex; grid-column: 1; flex-direction: column; gap: 0; align-self: stretch; overflow: hidden; border: 1px solid #0f766e; border-radius: 9px; background: #fff; }
        .batch-selection-title { padding: 10px 9px; border-bottom: 1px solid #0f766e; color: #fff; background: #134e4a; font-weight: 800; text-align: center; }
        .batch-selection-list { display: flex; flex: 1; flex-direction: column; gap: 8px; padding: 8px; }
        .batch-selection-item { display: inline-flex; align-items: center; gap: 5px; padding: 4px; border: 1px solid #c9dedb; border-radius: 9px; background: #f8fffd; }
        .batch-selection-item.is-active { border-color: #087f70; background: #ecfdf5; }
        .batch-selection-item button { min-height: 34px; }
        .batch-selection-item .batch-selection-number { flex: 1; padding: 6px 10px; color: #075e54; background: transparent; }
        .batch-selection-item .batch-selection-number:hover { background: #d1fae5; }
        .batch-selection-item .remove-batch-button { width: 34px; min-height: 34px; padding: 6px; }
        .camera-layout { display: grid; grid-template-columns: minmax(120px, 180px) minmax(0, 1fr); gap: 18px; align-items: start; }
        .camera-layout.has-batches .scanner-stage { grid-column: 2; }
        .scanner-stage { grid-column: 1 / -1; justify-self: center; width: min(100%, 460px); }
        #qr-reader { width: 100%; min-height: 300px; overflow: hidden; border-radius: 12px; background: #f1f5f9; }
        .scanner-controls { display: flex; width: 100%; flex-wrap: wrap; align-items: center; justify-content: center; gap: 8px; margin-top: 10px; }
        .manual-scan { width: 100%; margin-top: 12px; }
        .scan-help { margin: 0 0 12px; color: #475569; }
        .manual-unlock, .manual-password-row { display: flex; align-items: center; gap: 6px; }
        .manual-password-row input { width: 190px; min-width: 0; min-height: 42px; padding: 9px 10px; }
        #unlockManualButton { flex: 0 0 auto; padding: 9px 12px; }
        .manual-entry-locked { opacity: .7; }
        .manual-entry-locked input, .manual-entry-locked button[type="submit"] { cursor: not-allowed; }
        .scan-form { display: flex; width: 100%; flex-wrap: wrap; align-items: end; gap: 10px; }
        .manual-scan .field { min-width: 0; }
        .table-wrap { width: 100%; overflow: auto; max-height: calc(100vh - 350px); }
        table { width: 100%; min-width: 1900px; border: 1px solid #64748b; border-collapse: collapse; font-size: .92rem; }
        thead th {
            position: sticky;
            top: 0;
            padding: 11px 9px;
            border: 1px solid #0f766e;
            color: #fff;
            background: #134e4a;
            text-align: left;
            white-space: nowrap;
            z-index: 1;
        }
        tbody td { padding: 10px 9px; border: 1px solid #94a3b8; white-space: nowrap; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        td.batch-card-cell, td.process-cell { vertical-align: middle; text-align: center; }
        .process-badges {
            display: grid;
            grid-template-columns: repeat(2, max-content);
            justify-content: center;
            gap: 5px;
            min-width: 120px;
        }
        .process-badge {
            display: inline-block;
            padding: 4px 8px;
            border: 1px solid #99d5c9;
            border-radius: 6px;
            color: #075e54;
            background: #e6f8f3;
            font-size: .78rem;
            font-weight: 750;
        }
        tfoot th { padding: 12px 9px; border: 1px solid #94a3b8; background: #ecfdf5; color: #065f46; text-align: right; white-space: nowrap; }
        tfoot th.scanned-total-qty { text-align: left; }
        .actions { display: flex; justify-content: flex-start; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .roll-badge { display: inline-block; padding: 4px 9px; border-radius: 20px; color: #065f46; background: #ccfbf1; font-weight: 800; }
        .remove-roll-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 5px 9px;
            background: #fee2e2;
            color: #b91c1c;
        }
        .remove-roll-button { width: 36px; }
        .trash-icon { width: 16px; height: 16px; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .challan-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
        .challan-fields .field { min-width: 0; }
        .challan-fields .field-to { grid-column: 1 / -1; }
        .remark-input { width: 100%; min-width: 180px; min-height: 36px; padding: 6px 9px; }
        .challan-fields input, .remark-input { text-transform: uppercase; }
        #scannedPanel table { min-width: 900px; }
        .print-button { background: #2563eb; }
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
        @media (max-width: 800px) {
            body { padding: 16px 9px 9px; }
            .header { grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); gap: 8px; padding: 10px; }
            .header h1 { font-size: 1.1rem; }
            .back { padding: 8px 10px; }
            #qr-reader { min-height: 260px; }
            .table-wrap { max-height: 55vh; }
            .challan-fields { grid-template-columns: 1fr; }
            .camera-layout { grid-template-columns: minmax(0, 1fr); }
            .batch-selection { grid-column: 1; }
            .batch-selection-list { flex-direction: row; flex-wrap: wrap; }
            .camera-layout.has-batches .scanner-stage { grid-column: 1; }
        }
        @media print {
            @page { size: A4 portrait; margin: 8mm; }
            body { margin: 0; padding: 0; background: #fff; }
            body > * { display: none !important; }
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
    <main class="page">
        <header class="header">
            <a class="back" href="initialPage.php">← Back</a>
            <h1>Knitting Batch Delivery</h1>
            <button type="button" class="refresh-button" id="pageRefreshBtn" title="Reload the full page">
                <i class="fa-solid fa-rotate-right"></i> Refresh
            </button>
        </header>

        <section class="panel" id="deliveryPanel">
            <h2 class="panel-title">Scan a Batch Card QR first, then scan rolls from that batch. Scan another Batch Card QR to switch batches.</h2>
            <div class="message" id="message" role="status" aria-live="polite"></div>
            <div class="batch-summary">
                <div class="summary-item">Rolls scanned: <span id="scannedCount">0</span> / <span id="expectedCount">0</span></div>
                <div class="summary-item">Scanned QTY: <span id="scannedQty">0</span> KG</div>
                <div class="summary-item">Batch Total QTY: <span id="expectedQty">0</span> KG</div>
            </div>
            <div class="camera-layout">
                <div class="batch-selection is-hidden" id="batchSelectionPanel">
                    <div class="batch-selection-title">Scanned Batches</div>
                    <div class="batch-selection-list" id="batchSelectionList"></div>
                </div>
                <div class="scanner-stage">
                    <div id="qr-reader"></div>
                    <div class="scanner-controls">
                        <button type="button" id="switchCameraBtn">Switch Camera</button>
                        <div class="manual-unlock">
                            <div class="manual-password-row">
                                <input type="password" id="manualPassword" autocomplete="off" placeholder="Pass For Entry" aria-label="Password for manual entry">
                                <button type="button" id="unlockManualButton">Unlock</button>
                            </div>
                        </div>
                    </div>
                    <div class="manual-scan manual-entry-locked">
                        <form class="scan-form" id="rollForm">
                            <div class="field">
                                <label for="rollInput">Batch Card / Roll Number</label>
                                <div class="roll-input-group">
                                <input type="text" id="rollInput" autocomplete="off" placeholder="Scan Batch Card QR first" readonly aria-disabled="true">
                                    <button type="submit" id="addRollBtn" disabled>Add</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel is-hidden" id="scannedPanel">
            <h2 class="panel-title">Scanned Rolls</h2>
            <div class="challan-fields">
                <div class="field field-to">
                    <label for="toNameInput">To</label>
                    <input type="text" id="toNameInput" maxlength="255" autocomplete="off">
                </div>
                <div class="field">
                    <label for="gatePassNoInput">Gate Pass No.</label>
                    <input type="text" id="gatePassNoInput" maxlength="100" autocomplete="off">
                </div>
                <div class="field">
                    <label for="vehicleNoInput">Vehicle No.</label>
                    <input type="text" id="vehicleNoInput" maxlength="100" autocomplete="off">
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th><th>Batch</th><th>Roll</th><th>Fabrics Type</th><th>Batch Qty (kg)</th><th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="scannedRows">
                        <tr><td colspan="6">Scan a Batch Card QR first, then scan its rolls.</td></tr>
                    </tbody>
                    <tfoot id="scannedFooter"></tfoot>
                </table>
            </div>
            <div class="actions">
                <button class="confirm-button" id="confirmBtn" type="button" disabled>Confirm Delivery Challan</button>
            </div>
        </section>
    </main>
    <section id="challanPrint" aria-hidden="true"></section>

    <script>
        (function() {
            'use strict';

            var deliveryPanel = document.getElementById('deliveryPanel');
            var scannedPanel = document.getElementById('scannedPanel');
            var rollForm = document.getElementById('rollForm');
            var rollInput = document.getElementById('rollInput');
            var addRollBtn = document.getElementById('addRollBtn');
            var manualPassword = document.getElementById('manualPassword');
            var unlockManualButton = document.getElementById('unlockManualButton');
            var toNameInput = document.getElementById('toNameInput');
            var gatePassNoInput = document.getElementById('gatePassNoInput');
            var vehicleNoInput = document.getElementById('vehicleNoInput');
            var confirmBtn = document.getElementById('confirmBtn');
            var switchCameraBtn = document.getElementById('switchCameraBtn');
            var message = document.getElementById('message');
            var batchSelectionPanel = document.getElementById('batchSelectionPanel');
            var batchSelectionList = document.getElementById('batchSelectionList');
            var qrScanner = null;
            var batch = null;
            var scannedRolls = new Map();
            var expectedByRoll = new Map();
            var alreadyDeliveredRolls = new Set();
            var loadedBatches = new Map();
            var completedBatches = [];
            var remarksByBatch = new Map();
            var processingRoll = false;
            var scanPopupActive = false;
            var manualEntryAuthorized = false;
            var delivered = false;
            var batchAlreadyDelivered = false;
            var alreadyDeliveredMessage = '';
            var cameraFacingMode = 'environment';
            var cameraSwitching = false;

            function showMessage(text, type) {
                message.textContent = text;
                message.className = 'message ' + (type || 'info');
            }

            function showScanPopup(text, title, icon, duration) {
                message.textContent = '';
                message.className = 'message';
                scanPopupActive = true;
                rollInput.disabled = true;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: icon || 'warning',
                        title: title || 'Batch / Roll Mismatch',
                        text: text,
                        timer: duration || 3000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(function() {
                        scanPopupActive = false;
                        rollInput.disabled = delivered;
                        if (!delivered) rollInput.focus();
                    });
                    return;
                }

                showMessage(text, icon === 'success' ? 'success' : 'error');
                window.setTimeout(function() {
                    message.textContent = '';
                    message.className = 'message';
                    scanPopupActive = false;
                    rollInput.disabled = delivered;
                    if (!delivered) rollInput.focus();
                }, duration || 3000);
            }

            function escapeHtml(value) {
                var element = document.createElement('span');
                element.textContent = value === null || value === undefined ? '' : String(value);
                return element.innerHTML;
            }

            function formatQty(value) {
                var qty = Number(value);
                return Number.isFinite(qty) ? qty.toFixed(3).replace(/\.?0+$/, '') : '0';
            }

            function uppercaseInputValue(input) {
                var originalValue = input.value;
                var start = input.selectionStart;
                var end = input.selectionEnd;
                var uppercaseValue = originalValue.toUpperCase();
                if (originalValue === uppercaseValue) return;
                input.value = uppercaseValue;
                if (start !== null && end !== null) {
                    input.setSelectionRange(
                        originalValue.slice(0, start).toUpperCase().length,
                        originalValue.slice(0, end).toUpperCase().length
                    );
                }
            }

            function extractScanCode(value) {
                var text = String(value || '').trim();
                if (!text) return {type: '', value: ''};

                if (text.indexOf('|') !== -1) {
                    text = text.split('|')[0].trim();
                }

                var match = text.match(/^(BCARD|BATCH_CARD|BATCH|ROLL)\s*[:=]\s*([^\r\n]+)$/i);
                if (match && match[2]) {
                    var type = match[1].toUpperCase();
                    return {
                        type: type === 'ROLL' ? 'roll' : 'batch',
                        value: match[2].trim()
                    };
                }

                return {type: '', value: text};
            }

            function api(action, payload, queryParams) {
                var options = payload
                    ? {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)}
                    : {method: 'GET'};
                var query = new URLSearchParams(queryParams || {});
                query.set('action', action);
                return fetch('ajaxKnitting_batch_delivery.php?' + query.toString(), options)
                    .then(function(response) {
                        return response.json().then(function(data) {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Request failed.');
                            }
                            return data;
                        });
                    });
            }

            function getDeliveryGroups() {
                var groups = completedBatches.map(function(savedBatch) {
                    return {
                        card_no: savedBatch.card_no,
                        rows: savedBatch.rows,
                        target_qty: Number(savedBatch.target_qty) || 0,
                        expected_count: Number(savedBatch.expected_count) || savedBatch.rows.length,
                        remarks: remarksByBatch.has(String(savedBatch.card_no))
                            ? remarksByBatch.get(String(savedBatch.card_no))
                            : (savedBatch.remarks || ''),
                        completed: true
                    };
                });
                if (batch && scannedRolls.size) {
                    groups.push({
                        card_no: batch.card_no,
                        rows: Array.from(scannedRolls.values()),
                        target_qty: Number(batch.target_qty) || 0,
                        expected_count: batch.rows.length,
                        remarks: remarksByBatch.get(String(batch.card_no)) || '',
                        completed: false
                    });
                }
                return groups;
            }

            function getCurrentBatchRolls() {
                return batch ? batch.rows.length : 0;
            }

            function getAlreadyDeliveredRollCount() {
                var count = 0;
                alreadyDeliveredRolls.forEach(function(roll) {
                    if (expectedByRoll.has(String(roll))) count++;
                });
                return count;
            }

            function isCurrentBatchComplete() {
                return batch
                    ? scannedRolls.size + getAlreadyDeliveredRollCount() === batch.rows.length
                    : completedBatches.length > 0;
            }

            function updateSummary() {
                var groups = getDeliveryGroups();
                var scannedCount = 0;
                var expectedCount = completedBatches.reduce(function(total, savedBatch) {
                    return total + (Number(savedBatch.expected_count) || savedBatch.rows.length);
                }, 0) + getCurrentBatchRolls();
                var totalQty = 0;
                var targetQty = completedBatches.reduce(function(total, savedBatch) {
                    return total + (Number(savedBatch.target_qty) || 0);
                }, 0) + (batch ? Number(batch.target_qty) || 0 : 0);
                groups.forEach(function(group) {
                    group.rows.forEach(function(row) {
                        scannedCount++;
                        totalQty += Number(row.QTY) || 0;
                    });
                });

                document.getElementById('scannedCount').textContent = String(scannedCount);
                document.getElementById('expectedCount').textContent = String(expectedCount);
                document.getElementById('scannedQty').textContent = formatQty(totalQty);
                document.getElementById('expectedQty').textContent = formatQty(targetQty);
                document.getElementById('scannedFooter').innerHTML = groups.length
                    ? '<tr><th colspan="4">Total Batch Qty</th><th class="scanned-total-qty">' + formatQty(totalQty) + ' KG</th><th></th></tr>'
                    : '';

                deliveryPanel.classList.toggle('is-hidden', delivered);
                scannedPanel.classList.toggle('is-hidden', groups.length === 0 && !batch);
                var challanFieldsFilled = [
                    toNameInput.value.trim(),
                    gatePassNoInput.value.trim(),
                    vehicleNoInput.value.trim()
                ].every(function(value) {
                    return value !== '';
                });
                confirmBtn.disabled = delivered || !groups.length || scannedCount === 0 || !challanFieldsFilled;
            }

            function trashIconMarkup() {
                return '<svg class="trash-icon" viewBox="0 0 24 24" aria-hidden="true">' +
                    '<path d="M3 6h18M8 6V4h8v2m-11 0 1 14h12l1-14M10 11v5m4-5v5"></path></svg>';
            }

            function renderBatchSelection() {
                var batchItems = [];
                loadedBatches.forEach(function(loadedBatch, cardNo) {
                    var activeClass = batch && String(batch.card_no) === String(cardNo)
                        ? ' is-active'
                        : '';
                    batchItems.push(
                        '<div class="batch-selection-item' + activeClass + '">' +
                        '<button class="remove-roll-button remove-batch-button" type="button" data-remove-batch="' +
                        escapeHtml(cardNo) + '" aria-label="Remove Batch ' + escapeHtml(cardNo) +
                        '" title="Remove Batch ' + escapeHtml(cardNo) + '"' +
                        (delivered ? ' disabled' : '') + '>' + trashIconMarkup() + '</button>' +
                        '<button class="batch-selection-number" type="button" data-select-batch="' +
                        escapeHtml(cardNo) + '">' + escapeHtml(cardNo) + '</button>' +
                        '</div>'
                    );
                });
                batchSelectionList.innerHTML = batchItems.join('');
                batchSelectionPanel.classList.toggle('is-hidden', batchItems.length === 0);
                    document.querySelector('.camera-layout').classList.toggle('has-batches', batchItems.length > 0);
            }

            function sizeBatchRemarkInputs() {
                document.querySelectorAll('#scannedRows .remark-input').forEach(function(input) {
                    var cell = input.closest('td');
                    if (cell) input.style.height = Math.max(36, cell.clientHeight - 20) + 'px';
                });
            }

            function renderScannedRows() {
                var groups = getDeliveryGroups();
                var completedIndex = 0;
                var rowsHtml = '';
                groups.forEach(function(group) {
                    var groupIndex = group.completed ? completedIndex++ : -1;
                    group.rows.forEach(function(row, rowIndex) {
                        rowsHtml += '<tr>';
                        if (group.completed) {
                            rowsHtml += '<td><button class="remove-roll-button" type="button" ' +
                                'data-remove-completed-batch-index="' + groupIndex +
                                '" data-remove-completed-row-index="' + rowIndex +
                                '" aria-label="Remove roll" title="Remove roll"' +
                                (delivered ? ' disabled' : '') + '>' + trashIconMarkup() + '</button></td>';
                        } else if (!group.completed) {
                            var currentIndex = Array.from(scannedRolls.keys()).indexOf(String(row.ROLL));
                            rowsHtml += '<td><button class="remove-roll-button" type="button" data-remove-roll-index="' +
                                currentIndex + '" aria-label="Remove roll" title="Remove roll"' +
                                (delivered ? ' disabled' : '') + '>' + trashIconMarkup() + '</button></td>';
                        }
                        if (rowIndex === 0) {
                            rowsHtml += '<td class="batch-card-cell" rowspan="' + group.rows.length + '">' +
                                escapeHtml(group.card_no) + '</td>';
                        }
                        rowsHtml += '<td><span class="roll-badge">' + escapeHtml(row.ROLL) + '</span></td>' +
                            '<td>' + escapeHtml(row.FTYPE) + '</td>' +
                            '<td>' + escapeHtml(formatQty(row.QTY)) + '</td>';
                        if (rowIndex === 0) {
                            rowsHtml += '<td rowspan="' + group.rows.length + '">' +
                                '<input class="remark-input" type="text" maxlength="255" data-remark-batch="' +
                                escapeHtml(group.card_no) + '" value="' + escapeHtml(group.remarks) + '"' +
                                (delivered ? ' disabled' : '') + '></td>';
                        }
                        rowsHtml += '</tr>';
                    });
                });

                document.getElementById('scannedRows').innerHTML = rowsHtml ||
                    '<tr><td colspan="6">Scan a Batch Card QR first, then scan its rolls.</td></tr>';
                sizeBatchRemarkInputs();
                updateSummary();
            }

            function saveCurrentBatch() {
                if (!batch || !scannedRolls.size || batchAlreadyDelivered) return false;
                completedBatches.push({
                    card_no: batch.card_no,
                    target_qty: Number(batch.target_qty) || 0,
                    expected_count: batch.rows.length,
                    remarks: remarksByBatch.get(String(batch.card_no)) || '',
                    rows: Array.from(scannedRolls.values())
                });
                batch = null;
                scannedRolls.clear();
                expectedByRoll.clear();
                alreadyDeliveredRolls.clear();
                batchAlreadyDelivered = false;
                alreadyDeliveredMessage = '';
                renderScannedRows();
                return true;
            }

            function removeScannedRoll(index) {
                if (delivered) return;
                var row = Array.from(scannedRolls.values())[index];
                if (!row) return;
                scannedRolls.delete(String(row.ROLL));
                renderScannedRows();
                showMessage('Roll ' + row.ROLL + ' removed. You can scan or enter it again.', 'info');
                rollInput.focus();
            }

            function removeCompletedRoll(batchIndex, rowIndex) {
                if (delivered) return;
                var savedBatch = completedBatches[batchIndex];
                if (!savedBatch) return;
                var removedRoll = savedBatch.rows.splice(rowIndex, 1)[0];
                if (!removedRoll) return;
                if (!savedBatch.rows.length) {
                    remarksByBatch.delete(String(savedBatch.card_no));
                    completedBatches.splice(batchIndex, 1);
                }
                renderScannedRows();
                showMessage('Roll ' + removedRoll.ROLL + ' removed. You can scan or enter it again.', 'info');
                rollInput.focus();
            }

            function activateBatch(response) {
                var sameBatch = batch && String(batch.card_no) === String(response.card_no);
                if (sameBatch) {
                    showMessage('Batch Card ' + response.card_no + ' is selected. Scan one of its rolls.', 'info');
                    renderBatchSelection();
                    return;
                }
                if (batch && scannedRolls.size && !batchAlreadyDelivered) {
                    saveCurrentBatch();
                }
                loadedBatches.set(String(response.card_no), response);
                batch = response;
                scannedRolls.clear();
                expectedByRoll.clear();
                alreadyDeliveredRolls = new Set((response.delivered_rolls || []).map(function(deliveredRoll) {
                    return String(deliveredRoll);
                }));
                batch.rows.forEach(function(row) {
                    expectedByRoll.set(String(row.ROLL), row);
                });
                var savedBatchIndex = completedBatches.findIndex(function(savedBatch) {
                    return String(savedBatch.card_no) === String(response.card_no);
                });
                if (savedBatchIndex !== -1) {
                    var savedBatch = completedBatches.splice(savedBatchIndex, 1)[0];
                    savedBatch.rows.forEach(function(savedRow) {
                        scannedRolls.set(String(savedRow.ROLL), savedRow);
                    });
                }
                if (alreadyDeliveredRolls.size === response.rows.length) {
                    batchAlreadyDelivered = true;
                    var delivery = response.delivery || {};
                    alreadyDeliveredMessage = 'Batch Card ' + response.card_no + ' was already delivered' +
                        (delivery.BUDAT ? ' on ' + delivery.BUDAT : '') +
                        (delivery.DOP ? ' by ' + delivery.DOP : '') +
                        (delivery.DID ? ' (ID: ' + delivery.DID + ')' : '') + '.';
                } else {
                    batchAlreadyDelivered = false;
                    alreadyDeliveredMessage = '';
                    var deliveredCount = alreadyDeliveredRolls.size;
                    showMessage(
                        'Batch Card ' + response.card_no + ' found. ' +
                        (deliveredCount
                            ? deliveredCount + ' roll(s) already delivered. Scan remaining rolls.'
                            : 'Scan its rolls.'),
                        'info'
                    );
                }
                scannedPanel.classList.remove('is-hidden');
                renderBatchSelection();
                renderScannedRows();
            }

            function loadBatchByCard(cardNo) {
                return api('load_batch', null, {card_no: cardNo}).then(function(response) {
                    activateBatch(response);
                    return response;
                });
            }

            function findLoadedBatchForRoll(roll) {
                if (batch && expectedByRoll.has(roll)) return {batch: batch, ambiguous: false};

                var matches = [];
                loadedBatches.forEach(function(loadedBatch) {
                    if (loadedBatch.rows.some(function(row) {
                        return String(row.ROLL) === roll;
                    })) {
                        matches.push(loadedBatch);
                    }
                });
                if (matches.length === 1) return {batch: matches[0], ambiguous: false};
                return {batch: null, ambiguous: matches.length > 1};
            }

            function removeBatch(cardNo) {
                if (delivered) return;
                var batchKey = String(cardNo);
                if (!loadedBatches.has(batchKey)) return;

                loadedBatches.delete(batchKey);
                completedBatches = completedBatches.filter(function(savedBatch) {
                    return String(savedBatch.card_no) !== batchKey;
                });
                remarksByBatch.delete(batchKey);
                if (batch && String(batch.card_no) === batchKey) {
                    batch = null;
                    scannedRolls.clear();
                    expectedByRoll.clear();
                    alreadyDeliveredRolls.clear();
                    batchAlreadyDelivered = false;
                    alreadyDeliveredMessage = '';
                }
                renderBatchSelection();
                renderScannedRows();
                showMessage('Batch Card ' + batchKey + ' and its scanned rolls were removed. Scan its Batch Card QR again to add it back.', 'info');
            }

            function addScannedRoll(rawRoll, isManualEntry) {
                var roll = String(rawRoll || '').trim();
                if (!roll || processingRoll || scanPopupActive) return;
                if (isManualEntry && !manualEntryAuthorized) {
                    showScanPopup(
                        'Enter a valid password and select Unlock before entering a roll manually.',
                        'Manual Entry Locked',
                        'warning'
                    );
                    return;
                }
                if (delivered) {
                    showScanPopup(alreadyDeliveredMessage, 'Batch Already Delivered', 'error');
                    return;
                }
                processingRoll = true;
                rollInput.disabled = true;
                addRollBtn.disabled = true;

                Promise.resolve().then(function() {
                    if (delivered) return;
                    if (!batch) {
                        showScanPopup('Scan the Batch Card QR before scanning its roll.', 'Batch Card Required', 'warning');
                        return;
                    }
                    if (batchAlreadyDelivered) {
                        showScanPopup(alreadyDeliveredMessage, 'Batch Already Delivered', 'error');
                        return;
                    }
                    if (!expectedByRoll.has(roll)) {
                        showScanPopup('Not match this roll for this batch: ' + roll);
                        return;
                    }
                    if (alreadyDeliveredRolls.has(roll)) {
                        showScanPopup('Roll ' + roll + ' has already been delivered.');
                        return;
                    }
                    if (scannedRolls.has(roll)) {
                        showScanPopup('Roll ' + roll + ' has already been scanned for this batch.');
                        return;
                    }

                    var row = expectedByRoll.get(roll);
                    var previousTotal = 0;
                    scannedRolls.forEach(function(scanned) { previousTotal += Number(scanned.QTY) || 0; });
                    var nextTotal = previousTotal + (Number(row.QTY) || 0);
                    var targetQty = Number(batch.target_qty);
                    if (nextTotal - targetQty > 0.0005) {
                        showScanPopup(
                            'Over QTY by ' + formatQty(nextTotal - targetQty) + ' KG. Batch total: ' +
                            formatQty(targetQty) + ' KG; previous scanned: ' +
                            formatQty(previousTotal) + ' KG; this roll: ' +
                            formatQty(row.QTY) + ' KG. This roll was not added.'
                        );
                        return;
                    }
                    scannedRolls.set(roll, row);
                    scannedPanel.classList.remove('is-hidden');
                    renderScannedRows();
                    var batchComplete = isCurrentBatchComplete();
                    showScanPopup(
                        batchComplete
                            ? 'All available rolls from Batch Card ' + batch.card_no + ' are scanned. Continue scanning another batch or confirm delivery.'
                            : 'Roll ' + roll + ' added successfully to Batch Card ' + batch.card_no + '.',
                        batchComplete ? 'Batch Scan Complete' : 'Roll Added Successfully',
                        'success',
                        1000
                    );
                }).catch(function(error) {
                    showScanPopup(error.message);
                }).then(function() {
                    finishScan();
                });
            }

            function handleScannedCode(rawValue, isManualEntry) {
                var scannedCode = extractScanCode(rawValue);
                if (!scannedCode.value || processingRoll || scanPopupActive) return;
                if (isManualEntry && !manualEntryAuthorized) {
                    showScanPopup(
                        'Enter a valid password and select Unlock before entering a batch or roll manually.',
                        'Manual Entry Locked',
                        'warning'
                    );
                    return;
                }

                var knownBatch = scannedCode.type === 'batch'
                    ? {batch: null, ambiguous: false}
                    : findLoadedBatchForRoll(scannedCode.value);
                if (knownBatch.ambiguous) {
                    showScanPopup(
                        'Roll ' + scannedCode.value + ' exists in more than one scanned batch. Select the correct batch from the Batch list first.',
                        'Batch Selection Required',
                        'warning'
                    );
                    return;
                }
                if (knownBatch.batch) {
                    if (!batch || String(batch.card_no) !== String(knownBatch.batch.card_no)) {
                        activateBatch(knownBatch.batch);
                    }
                    addScannedRoll(scannedCode.value, isManualEntry);
                    return;
                }

                var isBatchCode = scannedCode.type === 'batch' || scannedCode.type === '' ||
                    (!batch && scannedCode.type !== 'roll');
                if (!isBatchCode) {
                    addScannedRoll(scannedCode.value, isManualEntry);
                    return;
                }

                if (delivered) {
                    showScanPopup(alreadyDeliveredMessage, 'Batch Already Delivered', 'error');
                    return;
                }
                processingRoll = true;
                rollInput.disabled = true;
                addRollBtn.disabled = true;
                loadBatchByCard(scannedCode.value).catch(function(error) {
                    var errorMessage = error.message;
                    if (errorMessage.indexOf('Batch Card') === 0 && errorMessage.indexOf('was not found') !== -1) {
                        errorMessage += ' Scan a valid Batch Card QR before scanning its rolls.';
                    }
                    showScanPopup(errorMessage);
                }).then(function() {
                    finishScan();
                });
            }

            function finishScan() {
                processingRoll = false;
                rollInput.value = '';
                rollInput.disabled = delivered || scanPopupActive;
                addRollBtn.disabled = !manualEntryAuthorized || delivered || scanPopupActive;
                if (!delivered && !scanPopupActive) rollInput.focus();
            }

            function stopCamera() {
                if (qrScanner && qrScanner.isScanning) {
                    return qrScanner.stop().catch(function(error) {
                        showScanPopup('Could not stop camera scanner: ' + error, 'Camera Error', 'error');
                    });
                }
                return Promise.resolve();
            }

            function startCamera() {
                if (typeof Html5Qrcode === 'undefined') {
                    showScanPopup('Camera scanner library did not load. Use the manual Batch Card / Roll Number field instead.', 'Camera Error', 'error');
                    return;
                }
                if (qrScanner && qrScanner.isScanning) return;
                qrScanner = qrScanner || new Html5Qrcode('qr-reader');
                qrScanner.start(
                    {facingMode: cameraFacingMode},
                    {fps: 12, qrbox: {width: 250, height: 180}},
                    function(decodedText) { handleScannedCode(decodedText, false); },
                    function() {}
                ).then(function() {
                    showMessage('Camera scanner is ready. First scan a Batch Card QR, then scan rolls from that batch.', 'info');
                }).catch(function(error) {
                    showScanPopup('Camera unavailable: ' + error + '. You can enter a batch card or roll number manually.', 'Camera Unavailable', 'error');
                });
            }

            function switchCamera() {
                if (cameraSwitching || typeof Html5Qrcode === 'undefined') return;
                cameraSwitching = true;
                switchCameraBtn.disabled = true;
                var wasScanning = Boolean(qrScanner && qrScanner.isScanning);
                var stopPromise = wasScanning ? qrScanner.stop() : Promise.resolve();

                stopPromise.then(function() {
                    if (qrScanner) {
                        qrScanner.clear();
                        qrScanner = null;
                    }
                    cameraFacingMode = cameraFacingMode === 'environment' ? 'user' : 'environment';
                    cameraSwitching = false;
                    switchCameraBtn.disabled = false;
                    startCamera();
                }).catch(function(error) {
                    cameraSwitching = false;
                    switchCameraBtn.disabled = false;
                    showScanPopup('Could not switch camera: ' + error, 'Camera Error', 'error');
                });
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

            function renderChallan(response) {
                var groups = getDeliveryGroups();
                groups.sort(function(left, right) {
                    return String(left.card_no).localeCompare(String(right.card_no));
                });
                var totalQty = 0;
                var rowsHtml = '';
                groups.forEach(function(group) {
                    group.rows.forEach(function(row, index) {
                        totalQty += Number(row.QTY) || 0;
                        rowsHtml += '<tr>' +
                            (index === 0
                                ? '<td rowspan="' + group.rows.length + '">' + escapeHtml(group.card_no) + '</td>'
                                : '') +
                            '<td>' + escapeHtml(row.ROLL) + '</td>' +
                            '<td>' + escapeHtml(row.FTYPE) + '</td>' +
                            (index === 0
                                ? '<td rowspan="' + group.rows.length + '">' + group.rows.length + '</td>'
                                : '') +
                            '<td>' + escapeHtml(row.QTY) + '</td>' +
                            (index === 0
                                ? '<td rowspan="' + group.rows.length + '">' + escapeHtml(group.remarks) + '</td>'
                                : '') +
                            '</tr>';
                    });
                });
                var deliveredRollCount = groups.reduce(function(total, group) {
                    return total + group.rows.length;
                }, 0);
                var challanRowHeight = deliveredRollCount > 12
                    ? Math.min(12, 210 / deliveredRollCount)
                    : 12;
                var challanFontSize = deliveredRollCount > 12
                    ? Math.max(4, Math.min(10, challanRowHeight * 1.6))
                    : 10;
                var challanCellPadding = deliveredRollCount > 12
                    ? Math.max(0.5, Math.min(2, challanRowHeight * 0.2))
                    : 5;
                var challanDensityStyle = '--challan-row-height:' + challanRowHeight.toFixed(2) + 'mm;' +
                    '--challan-font-size:' + challanFontSize.toFixed(1) + 'px;' +
                    '--challan-cell-padding:' + challanCellPadding.toFixed(1) + 'px';
                for (var blankRow = deliveredRollCount; blankRow < 12; blankRow++) {
                    var blankRowClass = blankRow === 11 ? 'blank-fill blank-fill-last' : 'blank-fill';
                    rowsHtml += '<tr class="' + blankRowClass + '">' +
                        '<td></td><td></td><td></td><td></td><td></td><td></td></tr>';
                }
                var dateParts = String(response.delivery_date || '').split('-');
                var displayDate = dateParts.length === 3 ? dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0] : '';
                var totalMilliKg = Math.round(totalQty * 1000);
                var wholeKg = Math.floor(totalMilliKg / 1000);
                var grams = totalMilliKg % 1000;
                var qtyWords = numberToWords(wholeKg) + ' kilogram' +
                    (wholeKg === 1 ? '' : 's');
                if (grams) qtyWords += ' and ' + numberToWords(grams) + ' grams';
                qtyWords += ' only';

                document.getElementById('challanPrint').innerHTML =
                    '<article class="challan-page" style="' + challanDensityStyle + '">' +
                    '<header class="challan-heading">' +
                    '<div class="challan-wordmark">PURBANI</div>' +
                    '<div class="challan-company"><h2>PURBANI FABRICS LTD.</h2>' +
                    '<p>NOORBAGH, MOUCHAK, KALIAKOIR, GAZIPUR</p></div><div></div>' +
                    '</header>' +
                    '<div class="challan-title">DELIVERY CHALLAN</div>' +
                    '<div class="challan-meta">' +
                    '<div class="challan-to"><strong>To:</strong> ' + escapeHtml(response.to_name || '') + '</div>' +
                    '<div class="challan-meta-list">' +
                    '<div><strong>Challan No.:</strong> ' + escapeHtml(response.challan_no || '-') + '</div>' +
                    '<div><strong>Date:</strong> ' + escapeHtml(displayDate) + '</div>' +
                    '<div><strong>Gate Pass No.:</strong> ' + escapeHtml(response.gate_pass_no || '-') + '</div>' +
                    '<div><strong>Vehicle No.:</strong> ' + escapeHtml(response.vehicle_no || '-') + '</div>' +
                    '</div></div>' +
                    '<table><colgroup><col style="width:18%"><col style="width:18%"><col style="width:26%">' +
                    '<col style="width:10%"><col style="width:13%"><col style="width:15%"></colgroup>' +
                    '<thead><tr><th>Batch No.</th><th>Roll No.</th><th>Fabrics Type</th>' +
                    '<th>Roll</th><th>Batch Qty</th><th>Remarks</th></tr></thead><tbody>' +
                    rowsHtml + '</tbody><tfoot><tr><th colspan="3">TOTAL</th><th>' +
                    deliveredRollCount + '</th><th>' + escapeHtml(formatQty(totalQty)) +
                    '</th><th></th></tr></tfoot></table>' +
                    '<div class="challan-quantity-lines">' +
                    '<div class="challan-quantity-line">Total weight: ' +
                    escapeHtml(formatQty(totalQty)) + ' KG</div>' +
                    '<div class="challan-quantity-line">In words: ' + escapeHtml(qtyWords) + '</div>' +
                    '</div>' +
                    '<div class="challan-signatures"><span>Received by</span><span>Prepared By' +
                    '<span class="challan-prepared-name">' + escapeHtml(response.dop || '') + '</span></span>' +
                    '<span>Store Officer</span><span>Store Manager</span><span>Auth. Signature</span></div></article>';
            }

            function printChallanAndReload() {
                window.addEventListener('afterprint', function() {
                    window.location.reload();
                }, {once: true});
                window.print();
            }

            function showDeliveryCompletion(response) {
                var confirmationMessage = response.challan_no +
                    ' successfully confirmed. ' +
                    response.roll_count + ' roll(s), ' + formatQty(response.total_qty) + ' KG.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Delivery Confirmed',
                        text: confirmationMessage,
                        showCancelButton: true,
                        confirmButtonText: 'Print Challan',
                        cancelButtonText: 'Cancel',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            printChallanAndReload();
                        } else {
                            window.location.reload();
                        }
                    });
                    return;
                }

                if (window.confirm(confirmationMessage + '\n\nPrint challan?')) {
                    printChallanAndReload();
                } else {
                    window.location.reload();
                }
            }

            rollForm.addEventListener('submit', function(event) {
                event.preventDefault();
                handleScannedCode(rollInput.value, true);
            });
            rollInput.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                handleScannedCode(rollInput.value, true);
            });
            rollInput.addEventListener('click', function() {
                if (!manualEntryAuthorized) {
                    showScanPopup(
                        'Enter a valid password and select Unlock before entering a batch card or roll manually.',
                        'Manual Entry Locked',
                        'warning'
                    );
                }
            });

            function setManualEntryAuthorized(isAuthorized) {
                manualEntryAuthorized = isAuthorized;
                rollInput.readOnly = !isAuthorized;
                rollInput.placeholder = isAuthorized
                    ? 'Enter a Batch Card or roll number'
                    : 'Unlock to enter a Batch Card or roll manually';
                rollInput.setAttribute('aria-disabled', isAuthorized ? 'false' : 'true');
                addRollBtn.disabled = !isAuthorized || delivered || processingRoll;
                document.querySelector('.manual-scan').classList.toggle('manual-entry-locked', !isAuthorized);
                if (isAuthorized) rollInput.focus();
            }

            unlockManualButton.addEventListener('click', function() {
                var password = manualPassword.value;
                if (!password) {
                    showScanPopup('Enter the password to unlock manual roll entry.', 'Password Required', 'warning');
                    manualPassword.focus();
                    return;
                }

                unlockManualButton.disabled = true;
                fetch('ajaxDyeing_roll_verification.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'authorize_manual_entry',
                        scope: 'knitting_batch_delivery',
                        password: password
                    })
                }).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Could not unlock manual entry.');
                        }
                        return data;
                    });
                }).then(function() {
                    manualPassword.value = '';
                    setManualEntryAuthorized(true);
                    showMessage('Manual roll entry unlocked.', 'success');
                }).catch(function(error) {
                    showScanPopup(error.message || 'Incorrect password.', 'Manual Entry Locked', 'error');
                }).finally(function() {
                    unlockManualButton.disabled = false;
                });
            });

            manualPassword.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    unlockManualButton.click();
                }
            });

            document.getElementById('scannedRows').addEventListener('click', function(event) {
                var button = event.target.closest(
                    '[data-remove-roll-index], [data-remove-completed-row-index]'
                );
                if (!button || !this.contains(button)) return;
                if (button.hasAttribute('data-remove-roll-index')) {
                    removeScannedRoll(Number(button.dataset.removeRollIndex));
                } else {
                    removeCompletedRoll(
                        Number(button.dataset.removeCompletedBatchIndex),
                        Number(button.dataset.removeCompletedRowIndex)
                    );
                }
            });
            batchSelectionList.addEventListener('click', function(event) {
                var removeButton = event.target.closest('[data-remove-batch]');
                if (removeButton && this.contains(removeButton)) {
                    removeBatch(removeButton.dataset.removeBatch);
                    return;
                }
                var selectButton = event.target.closest('[data-select-batch]');
                if (!selectButton || !this.contains(selectButton) ||
                    delivered || processingRoll || scanPopupActive) return;
                var selectedBatch = loadedBatches.get(String(selectButton.dataset.selectBatch));
                if (selectedBatch) activateBatch(selectedBatch);
            });
            document.getElementById('scannedRows').addEventListener('input', function(event) {
                var input = event.target.closest('[data-remark-batch]');
                if (!input || !this.contains(input)) return;
                uppercaseInputValue(input);
                remarksByBatch.set(String(input.dataset.remarkBatch), input.value);
            });

            [toNameInput, gatePassNoInput, vehicleNoInput].forEach(function(input) {
                input.addEventListener('input', function() {
                    uppercaseInputValue(input);
                    updateSummary();
                });
            });

            switchCameraBtn.addEventListener('click', switchCamera);
            document.getElementById('pageRefreshBtn').addEventListener('click', function() {
                window.location.reload();
            });
            confirmBtn.addEventListener('click', function() {
                if (confirmBtn.disabled) return;
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Confirming...';
                var deliveryGroups = getDeliveryGroups();
                var toName = toNameInput.value.trim().toUpperCase();
                var gatePassNo = gatePassNoInput.value.trim().toUpperCase();
                var vehicleNo = vehicleNoInput.value.trim().toUpperCase();
                toNameInput.value = toName;
                gatePassNoInput.value = gatePassNo;
                vehicleNoInput.value = vehicleNo;
                api('confirm_delivery', {
                    batches: deliveryGroups.map(function(group) {
                        return {
                            card_no: group.card_no,
                            rolls: group.rows.map(function(row) { return String(row.ROLL); }),
                            total_qty: Number(group.target_qty),
                            remarks: String(group.remarks || '').toUpperCase()
                        };
                    }),
                    to_name: toName,
                    gate_pass_no: gatePassNo,
                    vehicle_no: vehicleNo
                }).then(function(response) {
                    delivered = true;
                    renderScannedRows();
                    rollInput.disabled = true;
                    addRollBtn.disabled = true;
                    unlockManualButton.disabled = true;
                    manualPassword.disabled = true;
                    document.getElementById('toNameInput').disabled = true;
                    document.getElementById('gatePassNoInput').disabled = true;
                    document.getElementById('vehicleNoInput').disabled = true;
                    confirmBtn.textContent = 'Delivery Challan Confirmed';
                    renderChallan(response);
                    showDeliveryCompletion(response);
                    stopCamera();
                }).catch(function(error) {
                    showScanPopup(error.message, 'Batch Delivery Error', 'error');
                    confirmBtn.textContent = 'Confirm Delivery Challan';
                    updateSummary();
                });
            });

            setManualEntryAuthorized(manualEntryAuthorized);
            rollInput.focus();
            startCamera();
        })();
    </script>
</body>
</html>
