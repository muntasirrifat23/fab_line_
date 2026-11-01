<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dyeing Batch Delivery</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            margin-bottom: 14px;
        }
        h1 { margin: 0; font-size: 1.5rem; }
        .back {
            display: inline-block;
            padding: 9px 14px;
            color: #fff;
            background: #111827;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            white-space: nowrap;
        }
        .panel { padding: 18px; margin-bottom: 14px; }
        .panel-title { margin: 0 0 14px; font-size: 1.08rem; font-weight: 800; }
        .batch-form, .scan-form { display: flex; flex-wrap: wrap; align-items: end; gap: 10px; }
        .field { flex: 1; min-width: 220px; }
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
        .scan-button { background: #2563eb; }
        .confirm-button { background: #059669; }
        .message { display: none; padding: 11px 13px; margin-top: 12px; border-radius: 8px; font-weight: 700; }
        .message.info { display: block; color: #075985; background: #e0f2fe; }
        .message.error { display: block; color: #991b1b; background: #fee2e2; }
        .message.success { display: block; color: #065f46; background: #d1fae5; }
        .is-hidden { display: none !important; }
        .batch-summary { display: flex; flex-wrap: wrap; gap: 10px; margin: 0 0 14px; }
        .summary-item { padding: 9px 12px; border-radius: 8px; background: #ecfdf5; font-weight: 800; }
        .camera-layout { display: grid; grid-template-columns: minmax(260px, 420px) 1fr; gap: 18px; align-items: start; }
        #qr-reader { width: 100%; min-height: 260px; overflow: hidden; border-radius: 12px; background: #f1f5f9; }
        .scan-help { margin: 0 0 12px; color: #475569; }
        .table-wrap { width: 100%; overflow: auto; max-height: calc(100vh - 350px); }
        table { width: 100%; min-width: 1900px; border-collapse: collapse; font-size: .92rem; }
        thead th {
            position: sticky;
            top: 0;
            padding: 11px 9px;
            color: #fff;
            background: #134e4a;
            text-align: left;
            white-space: nowrap;
            z-index: 1;
        }
        tbody td { padding: 10px 9px; border-bottom: 1px solid #dbe5e4; white-space: nowrap; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tfoot th { padding: 12px 9px; background: #ecfdf5; color: #065f46; text-align: right; white-space: nowrap; }
        .actions { display: flex; justify-content: flex-end; margin-top: 14px; }
        .roll-badge { display: inline-block; padding: 4px 9px; border-radius: 20px; color: #065f46; background: #ccfbf1; font-weight: 800; }
        @media (max-width: 800px) {
            body { padding: 9px; }
            .header { flex-wrap: wrap; }
            .camera-layout { grid-template-columns: 1fr; }
            .table-wrap { max-height: 55vh; }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <a class="back" href="initialPage.php">← Back</a>
            <h1>Dyeing Batch Delivery</h1>
        </header>

        <section class="panel">
            <h2 class="panel-title">1. Load Batch Card</h2>
            <form class="batch-form" id="batchForm">
                <div class="field">
                    <label for="batchCardInput">Batch Card Number</label>
                    <input type="text" id="batchCardInput" autocomplete="off" placeholder="Enter batch card number" required>
                </div>
                <button type="submit" id="loadBatchBtn">Load Batch</button>
            </form>
            <div class="message" id="message" role="status" aria-live="polite"></div>
        </section>

        <section class="panel is-hidden" id="deliveryPanel">
            <h2 class="panel-title">2. Scan Each Roll</h2>
            <div class="batch-summary">
                <div class="summary-item">Batch: <span id="summaryCardNo">-</span></div>
                <div class="summary-item">Rolls scanned: <span id="scannedCount">0</span> / <span id="expectedCount">0</span></div>
                <div class="summary-item">Scanned QTY: <span id="scannedQty">0.000</span> KG</div>
                <div class="summary-item">Batch total QTY: <span id="expectedQty">0.000</span> KG</div>
            </div>
            <div class="camera-layout">
                <div>
                    <div id="qr-reader"></div>
                    <button class="scan-button" id="cameraBtn" type="button" style="margin-top:10px">Start Camera Scanner</button>
                </div>
                <div>
                    <p class="scan-help">Scan the barcode/QR of every roll in this batch, or enter a roll number manually. A roll not belonging to this batch will be rejected.</p>
                    <form class="scan-form" id="rollForm">
                        <div class="field">
                            <label for="rollInput">Roll Number</label>
                            <input type="text" id="rollInput" autocomplete="off" placeholder="Scan or enter roll number" disabled>
                        </div>
                        <button type="submit" id="addRollBtn" disabled>Add Roll</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="panel is-hidden" id="scannedPanel">
            <h2 class="panel-title">Scanned Rolls</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Roll</th><th>Rack</th><th>QTY</th><th>PO Number</th><th>SONO</th>
                            <th>Buyer</th><th>Style</th><th>Color</th><th>MC Dia</th><th>Customer</th>
                            <th>Open / Tube</th><th>SL/VQ</th><th>Fabrics Type</th><th>Finish GSM</th>
                            <th>Finish Dia</th><th>Yarn Type</th><th>Yarn Count</th><th>Yarn Brand</th>
                            <th>Feeder Plan</th><th>Lot No</th><th>Category</th>
                        </tr>
                    </thead>
                    <tbody id="scannedRows">
                        <tr><td colspan="21">Scan rolls after loading a batch card.</td></tr>
                    </tbody>
                    <tfoot id="scannedFooter"></tfoot>
                </table>
            </div>
            <div class="actions">
                <button class="confirm-button" id="confirmBtn" type="button" disabled>Confirm Batch Delivery</button>
            </div>
        </section>
    </main>

    <script>
        (function() {
            'use strict';

            var batchForm = document.getElementById('batchForm');
            var batchInput = document.getElementById('batchCardInput');
            var loadButton = document.getElementById('loadBatchBtn');
            var deliveryPanel = document.getElementById('deliveryPanel');
            var scannedPanel = document.getElementById('scannedPanel');
            var rollForm = document.getElementById('rollForm');
            var rollInput = document.getElementById('rollInput');
            var addRollBtn = document.getElementById('addRollBtn');
            var confirmBtn = document.getElementById('confirmBtn');
            var cameraBtn = document.getElementById('cameraBtn');
            var message = document.getElementById('message');
            var qrScanner = null;
            var batch = null;
            var scannedRolls = new Map();
            var expectedByRoll = new Map();
            var processingRoll = false;
            var delivered = false;

            function showMessage(text, type) {
                message.textContent = text;
                message.className = 'message ' + (type || 'info');
            }

            function escapeHtml(value) {
                var element = document.createElement('span');
                element.textContent = value === null || value === undefined ? '' : String(value);
                return element.innerHTML;
            }

            function formatQty(value) {
                var qty = Number(value);
                return Number.isFinite(qty) ? qty.toFixed(3) : '0.000';
            }

            function api(action, payload) {
                var options = payload
                    ? {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)}
                    : {method: 'GET'};
                return fetch('ajaxDyeing_batch_delivery.php?action=' + encodeURIComponent(action) +
                    (payload ? '' : '&card_no=' + encodeURIComponent(batchInput.value.trim())), options)
                    .then(function(response) {
                        return response.json().then(function(data) {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Request failed.');
                            }
                            return data;
                        });
                    });
            }

            function updateSummary() {
                var total = 0;
                scannedRolls.forEach(function(row) { total += Number(row.QTY) || 0; });
                document.getElementById('scannedCount').textContent = String(scannedRolls.size);
                document.getElementById('scannedQty').textContent = formatQty(total);
                document.getElementById('scannedFooter').innerHTML =
                    '<tr><th colspan="2">Scanned total QTY</th><th>' + formatQty(total) +
                    ' KG</th><th colspan="18"></th></tr>';

                var allRollsScanned = batch && scannedRolls.size === batch.rows.length;
                var isOverQty = batch && total - Number(batch.total_qty) > 0.0005;
                confirmBtn.disabled = delivered || !allRollsScanned || isOverQty;
            }

            function renderScannedRows() {
                var rows = Array.from(scannedRolls.values());
                document.getElementById('scannedRows').innerHTML = rows.length
                    ? rows.map(function(row) {
                        return '<tr>' +
                            '<td><span class="roll-badge">' + escapeHtml(row.ROLL) + '</span></td>' +
                            '<td>' + escapeHtml(row.RACK) + '</td>' +
                            '<td>' + escapeHtml(row.QTY) + '</td>' +
                            '<td>' + escapeHtml(row.PO_NUMBER) + '</td>' +
                            '<td>' + escapeHtml(row.SONO) + '</td>' +
                            '<td>' + escapeHtml(row.BUYER) + '</td>' +
                            '<td>' + escapeHtml(row.STYLE) + '</td>' +
                            '<td>' + escapeHtml(row.COLOR) + '</td>' +
                            '<td>' + escapeHtml(row.MCDIA) + '</td>' +
                            '<td>' + escapeHtml(row.CUSTOMER) + '</td>' +
                            '<td>' + escapeHtml(row.O_T) + '</td>' +
                            '<td>' + escapeHtml(row.SL) + '</td>' +
                            '<td>' + escapeHtml(row.FTYPE) + '</td>' +
                            '<td>' + escapeHtml(row.FGSM) + '</td>' +
                            '<td>' + escapeHtml(row.FDIA) + '</td>' +
                            '<td>' + escapeHtml(row.YTYPE) + '</td>' +
                            '<td>' + escapeHtml(row.YCOUNT) + '</td>' +
                            '<td>' + escapeHtml(row.YBRAND) + '</td>' +
                            '<td>' + escapeHtml(row.FEEDER_PLAN) + '</td>' +
                            '<td>' + escapeHtml(row.LOT_NO) + '</td>' +
                            '<td>' + escapeHtml(row.CATEGORY) + '</td>' +
                            '</tr>';
                    }).join('')
                    : '<tr><td colspan="21">Scan rolls after loading a batch card.</td></tr>';
                updateSummary();
            }

            function addScannedRoll(rawRoll) {
                var roll = String(rawRoll || '').trim();
                if (!roll || processingRoll || delivered || !batch) return;
                processingRoll = true;
                rollInput.disabled = true;
                addRollBtn.disabled = true;

                if (!expectedByRoll.has(roll)) {
                    showMessage('Not match this roll for this batch: ' + roll, 'error');
                    finishScan();
                    return;
                }
                if (scannedRolls.has(roll)) {
                    showMessage('Roll ' + roll + ' has already been scanned for this batch.', 'error');
                    finishScan();
                    return;
                }

                var row = expectedByRoll.get(roll);
                var nextTotal = Number(row.QTY);
                scannedRolls.forEach(function(scanned) { nextTotal += Number(scanned.QTY) || 0; });
                if (nextTotal - Number(batch.total_qty) > 0.0005) {
                    showMessage('Over quantity: scanned total would exceed this batch QTY. Delivery is blocked.', 'error');
                    finishScan();
                    return;
                }

                scannedRolls.set(roll, row);
                scannedPanel.classList.remove('is-hidden');
                renderScannedRows();
                showMessage('Roll ' + roll + ' matched this batch.', 'success');
                finishScan();
            }

            function finishScan() {
                processingRoll = false;
                rollInput.value = '';
                rollInput.disabled = delivered || !batch;
                addRollBtn.disabled = delivered || !batch;
                rollInput.focus();
            }

            function stopCamera() {
                if (qrScanner && qrScanner.isScanning) {
                    return qrScanner.stop().catch(function(error) {
                        showMessage('Could not stop camera scanner: ' + error, 'error');
                    });
                }
                return Promise.resolve();
            }

            function startCamera() {
                if (typeof Html5Qrcode === 'undefined') {
                    showMessage('Camera scanner library did not load. Use the roll number input instead.', 'error');
                    return;
                }
                if (qrScanner && qrScanner.isScanning) return;
                cameraBtn.disabled = true;
                cameraBtn.textContent = 'Starting camera...';
                qrScanner = qrScanner || new Html5Qrcode('qr-reader');
                qrScanner.start(
                    {facingMode: 'environment'},
                    {fps: 12, qrbox: {width: 250, height: 180}},
                    function(decodedText) { addScannedRoll(decodedText); },
                    function() {}
                ).then(function() {
                    cameraBtn.textContent = 'Camera Scanner Active';
                    showMessage('Camera scanner is ready. Scan each roll in the loaded batch.', 'info');
                }).catch(function(error) {
                    cameraBtn.disabled = false;
                    cameraBtn.textContent = 'Start Camera Scanner';
                    showMessage('Camera unavailable: ' + error + '. You can enter roll numbers manually.', 'error');
                });
            }

            batchForm.addEventListener('submit', function(event) {
                event.preventDefault();
                var cardNo = batchInput.value.trim();
                if (!cardNo) {
                    showMessage('Enter a Batch Card number.', 'error');
                    return;
                }
                loadButton.disabled = true;
                loadButton.textContent = 'Loading...';
                confirmBtn.disabled = true;
                scannedRolls.clear();
                batch = null;
                expectedByRoll.clear();
                scannedPanel.classList.add('is-hidden');
                deliveryPanel.classList.add('is-hidden');

                api('load_batch').then(function(response) {
                    batch = response;
                    batch.rows.forEach(function(row) { expectedByRoll.set(String(row.ROLL), row); });
                    document.getElementById('summaryCardNo').textContent = response.card_no;
                    document.getElementById('expectedCount').textContent = String(response.roll_count);
                    document.getElementById('expectedQty').textContent = formatQty(response.total_qty);
                    deliveryPanel.classList.remove('is-hidden');
                    rollInput.disabled = Boolean(response.delivery);
                    addRollBtn.disabled = Boolean(response.delivery);
                    delivered = Boolean(response.delivery);
                    if (delivered) {
                        showMessage('This Batch Card was already delivered on ' + response.delivery.DELIVERED_AT +
                            ' by ' + response.delivery.UNAME + '.', 'error');
                    } else {
                        showMessage('Batch loaded. Scan all ' + response.roll_count + ' rolls to confirm delivery.', 'info');
                        rollInput.focus();
                        startCamera();
                    }
                    renderScannedRows();
                }).catch(function(error) {
                    showMessage(error.message, 'error');
                }).then(function() {
                    loadButton.disabled = false;
                    loadButton.textContent = 'Load Batch';
                });
            });

            rollForm.addEventListener('submit', function(event) {
                event.preventDefault();
                addScannedRoll(rollInput.value);
            });

            cameraBtn.addEventListener('click', startCamera);

            confirmBtn.addEventListener('click', function() {
                if (!batch || confirmBtn.disabled) return;
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Confirming...';
                api('confirm_delivery', {
                    card_no: batch.card_no,
                    rolls: Array.from(scannedRolls.keys()),
                    total_qty: Number(batch.total_qty)
                }).then(function(response) {
                    delivered = true;
                    rollInput.disabled = true;
                    addRollBtn.disabled = true;
                    showMessage(response.message + ' ' + response.roll_count + ' roll(s), ' +
                        formatQty(response.total_qty) + ' KG.', 'success');
                    confirmBtn.textContent = 'Delivery Confirmed';
                    stopCamera();
                }).catch(function(error) {
                    showMessage(error.message, 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Confirm Batch Delivery';
                });
            });
        })();
    </script>
</body>
</html>
