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
        .camera-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; justify-items: center; }
        .scanner-stage { width: min(100%, 460px); }
        #qr-reader { width: 100%; min-height: 300px; overflow: hidden; border-radius: 12px; background: #f1f5f9; }
        .scanner-controls { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 10px; }
        .manual-scan { width: min(100%, 900px); }
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
            #qr-reader { min-height: 260px; }
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

        <section class="panel" id="deliveryPanel">
            <h2 class="panel-title">Scan a Roll to Find Its Batch, Then Scan Every Roll in That Batch</h2>
            <div class="message" id="message" role="status" aria-live="polite"></div>
            <div class="batch-summary">
                <div class="summary-item">Batch: <span id="summaryCardNo">-</span></div>
                <div class="summary-item">Rolls scanned: <span id="scannedCount">0</span> / <span id="expectedCount">0</span></div>
                <div class="summary-item">Scanned QTY: <span id="scannedQty">0.000</span> KG</div>
                <div class="summary-item">Batch total QTY: <span id="expectedQty">0.000</span> KG</div>
            </div>
            <div class="camera-layout">
                <div class="scanner-stage">
                    <div id="qr-reader"></div>
                    <div class="scanner-controls">
                        <button class="scan-button" id="cameraBtn" type="button">Start Camera Scanner</button>
                        <button type="button" id="switchCameraBtn">Switch to Front Camera</button>
                        <button type="button" id="anotherBatchBtn">Another Batch Scan</button>
                    </div>
                </div>
                <div class="manual-scan">
                    <form class="scan-form" id="rollForm">
                        <div class="field">
                            <label for="rollInput">Roll Number</label>
                            <input type="text" id="rollInput" autocomplete="off" placeholder="Scan or enter roll number">
                        </div>
                        <div class="field">
                            <label for="remainingQtyInput">Request QTY Remaining (KG)</label>
                            <input type="text" id="remainingQtyInput" value="-" readonly>
                        </div>
                        <button type="submit" id="addRollBtn">Add Roll</button>
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

            var deliveryPanel = document.getElementById('deliveryPanel');
            var scannedPanel = document.getElementById('scannedPanel');
            var rollForm = document.getElementById('rollForm');
            var rollInput = document.getElementById('rollInput');
            var remainingQtyInput = document.getElementById('remainingQtyInput');
            var addRollBtn = document.getElementById('addRollBtn');
            var confirmBtn = document.getElementById('confirmBtn');
            var cameraBtn = document.getElementById('cameraBtn');
            var switchCameraBtn = document.getElementById('switchCameraBtn');
            var anotherBatchBtn = document.getElementById('anotherBatchBtn');
            var message = document.getElementById('message');
            var qrScanner = null;
            var batch = null;
            var scannedRolls = new Map();
            var expectedByRoll = new Map();
            var processingRoll = false;
            var scanPopupActive = false;
            var delivered = false;
            var cameraFacingMode = 'environment';
            var cameraSwitching = false;

            function showMessage(text, type) {
                message.textContent = text;
                message.className = 'message ' + (type || 'info');
            }

            function showScanPopup(text) {
                message.textContent = '';
                message.className = 'message';
                scanPopupActive = true;
                rollInput.disabled = true;
                addRollBtn.disabled = true;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Quantity / Roll Mismatch',
                        text: text,
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(function() {
                        scanPopupActive = false;
                        if (!delivered) {
                            rollInput.disabled = false;
                            addRollBtn.disabled = false;
                            rollInput.focus();
                        }
                    });
                    return;
                }

                showMessage(text, 'error');
                window.setTimeout(function() {
                    message.textContent = '';
                    message.className = 'message';
                    scanPopupActive = false;
                    if (!delivered) {
                        rollInput.disabled = false;
                        addRollBtn.disabled = false;
                        rollInput.focus();
                    }
                }, 3000);
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

            function api(action, payload, queryParams) {
                var options = payload
                    ? {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)}
                    : {method: 'GET'};
                var query = new URLSearchParams(queryParams || {});
                query.set('action', action);
                return fetch('ajaxDyeing_batch_delivery.php?' + query.toString(), options)
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
                var targetQty = batch ? Number(batch.target_qty) : 0;
                document.getElementById('scannedCount').textContent = String(scannedRolls.size);
                document.getElementById('scannedQty').textContent = formatQty(total);
                document.getElementById('expectedQty').textContent = formatQty(targetQty);
                remainingQtyInput.value = batch ? formatQty(Math.max(0, targetQty - total)) : '-';
                document.getElementById('scannedFooter').innerHTML =
                    '<tr><th colspan="2">Scanned total QTY</th><th>' + formatQty(total) +
                    ' KG</th><th colspan="18"></th></tr>';

                var allRollsScanned = batch && scannedRolls.size === batch.rows.length;
                var isOverQty = batch && total - targetQty > 0.0005;
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
                if (!roll || processingRoll || scanPopupActive || delivered) return;
                processingRoll = true;
                rollInput.disabled = true;
                addRollBtn.disabled = true;

                var resolveBatch = batch
                    ? Promise.resolve(batch)
                    : api('load_batch_by_roll', null, {roll: roll}).then(function(response) {
                        batch = response;
                        batch.rows.forEach(function(row) {
                            expectedByRoll.set(String(row.ROLL), row);
                        });
                        document.getElementById('summaryCardNo').textContent = response.card_no;
                        document.getElementById('expectedCount').textContent = String(response.roll_count);
                        document.getElementById('expectedQty').textContent = formatQty(response.target_qty);
                        updateSummary();
                        if (response.delivery) {
                            delivered = true;
                            showMessage('Batch Card ' + response.card_no + ' was already delivered on ' +
                                response.delivery.DELIVERED_AT + ' by ' + response.delivery.UNAME + '.', 'error');
                        } else {
                            showMessage('Batch Card ' + response.card_no + ' found. Scan its remaining rolls.', 'info');
                        }
                        return response;
                    });

                resolveBatch.then(function() {
                    if (delivered) return;
                    if (!expectedByRoll.has(roll)) {
                        showScanPopup('Not match this roll for this batch: ' + roll);
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
                    showMessage('Roll ' + roll + ' matched Batch Card ' + batch.card_no + '.', 'success');
                }).catch(function(error) {
                    showScanPopup(error.message);
                }).then(function() {
                    finishScan();
                });
            }

            function finishScan() {
                processingRoll = false;
                rollInput.value = '';
                rollInput.disabled = delivered || scanPopupActive;
                addRollBtn.disabled = delivered || scanPopupActive;
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
                    {facingMode: cameraFacingMode},
                    {fps: 12, qrbox: {width: 250, height: 180}},
                    function(decodedText) { addScannedRoll(decodedText); },
                    function() {}
                ).then(function() {
                    cameraBtn.hidden = true;
                    showMessage('Camera scanner is ready. First scan a roll to find its batch.', 'info');
                }).catch(function(error) {
                    cameraBtn.disabled = false;
                    cameraBtn.hidden = false;
                    cameraBtn.textContent = 'Start Camera Scanner';
                    showMessage('Camera unavailable: ' + error + '. You can enter roll numbers manually.', 'error');
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
                    switchCameraBtn.textContent = cameraFacingMode === 'environment'
                        ? 'Switch to Front Camera'
                        : 'Switch to Back Camera';
                    cameraSwitching = false;
                    switchCameraBtn.disabled = false;
                    if (wasScanning) startCamera();
                }).catch(function(error) {
                    cameraSwitching = false;
                    switchCameraBtn.disabled = false;
                    showMessage('Could not switch camera: ' + error, 'error');
                });
            }

            rollForm.addEventListener('submit', function(event) {
                event.preventDefault();
                addScannedRoll(rollInput.value);
            });

            cameraBtn.addEventListener('click', startCamera);
            switchCameraBtn.addEventListener('click', switchCamera);
            anotherBatchBtn.addEventListener('click', function() {
                window.location.reload();
            });

            confirmBtn.addEventListener('click', function() {
                if (!batch || confirmBtn.disabled) return;
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Confirming...';
                api('confirm_delivery', {
                    card_no: batch.card_no,
                    rolls: Array.from(scannedRolls.keys()),
                    total_qty: Number(batch.target_qty)
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

            rollInput.focus();
            startCamera();
        })();
    </script>
</body>
</html>
