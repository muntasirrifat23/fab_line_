<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

unset($_SESSION['roll_manual_entry_authorized']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FabLine | Roll Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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
        h1 { margin: 0; font-size: 1.4rem; }
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
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .back { color: #fff; background: #000; border: 2px solid #000; }
        .back:hover { color: #000; background: #fff; border-color: #000; }
        .button:disabled { background: #94a3b8; cursor: not-allowed; }
        .panel { padding: 18px; margin-bottom: 14px; }
        .panel-title { margin: 0 0 14px; font-size: 1.08rem; font-weight: 800; }
        .message {
            position: fixed;
            inset: 0;
            z-index: 20;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(15, 23, 42, .48);
        }
        .message.show { display: flex; }
        .message-card {
            position: relative;
            width: min(100%, 480px);
            min-height: 280px;
            padding: 48px 36px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 18px 48px rgba(15, 23, 42, .25);
            text-align: center;
        }
        .message-icon {
            display: flex;
            width: 76px;
            height: 76px;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 2px solid currentColor;
            border-radius: 50%;
            font-size: 2rem;
        }
        .message.success .message-icon { color: #16a34a; }
        .message.warning .message-icon, .message.error .message-icon { color: #d97706; }
        .message.info .message-icon { color: #0284c7; }
        .message-title { margin: 0 0 12px; color: #1f2937; font-size: 1.4rem; }
        .message-text { margin: 0; color: #64748b; font-size: 1rem; line-height: 1.5; }
        .message-close {
            position: absolute;
            top: 8px;
            right: 10px;
            padding: 4px 8px;
            border: 0;
            color: #64748b;
            background: transparent;
            font-size: 1.25rem;
            cursor: pointer;
        }
        .summary { display: flex; flex-wrap: wrap; gap: 10px; margin: 0 0 14px; }
        .summary-item { padding: 9px 12px; border-radius: 8px; background: #ecfdf5; font-weight: 800; }
        .batch-panel-heading { margin-bottom: 12px; }
        .batch-panel-heading .panel-title { margin: 0; }
        #saveVerifiedButton { background: #047857; }
        #saveVerifiedButton:disabled { background: #94a3b8; }
        .verified-roll-actions { display: flex; justify-content: flex-start; margin-top: 12px; }
        .scanner-layout { display: grid; grid-template-columns: minmax(0, 1fr); justify-items: center; gap: 16px; }
        .scanner-stage {
            position: relative;
            width: min(100%, 54vh, 560px);
            max-height: 54vh;
            aspect-ratio: 1 / 1;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 24px;
            background: #eef2f7;
            box-shadow: inset 0 0 0 1px #d7e0ea, 0 8px 20px rgba(30, 60, 120, .12);
        }
        #qr-reader { width: 100%; height: 100%; padding: 0 !important; background: #f4f7fb; }
        #qr-reader > div { width: 100% !important; height: 100% !important; }
        #qr-reader video { display: block; width: 100% !important; height: 100% !important; border-radius: 24px; object-fit: cover; }
        .scanner-controls { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 10px; margin-top: 10px; }
        .scanner-controls .secondary { background: #475569; }
        #switchCameraButton { background: #2563eb; }
        #anotherBatchButton { background: #7c3aed; }
        .manual-scan { width: min(100%, 620px); }
        .scan-form { display: flex; flex-wrap: wrap; align-items: end; gap: 10px; }
        .field { flex: 1; min-width: 180px; }
        label { display: block; margin-bottom: 6px; font-weight: 700; }
        input {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #afc8c4;
            border-radius: 8px;
            font: inherit;
        }
        input:focus { outline: 2px solid #54b7a8; border-color: #159685; }
        .roll-input-group { display: flex; width: 100%; }
        .roll-input-group input { flex: 1; min-width: 0; border-radius: 8px 0 0 8px; }
        .roll-input-group button { border-radius: 0 8px 8px 0; }
        .manual-unlock { display: flex; align-items: center; gap: 7px; }
        .manual-unlock label { margin: 0; white-space: nowrap; }
        .manual-password-row { display: flex; gap: 6px; }
        .manual-password-row input { width: 135px; min-width: 0; min-height: 42px; }
        #unlockManualButton { flex: 0 0 auto; padding-right: 12px; padding-left: 12px; }
        .manual-entry-locked { opacity: .7; }
        .manual-entry-locked input, .manual-entry-locked button[type="submit"] { cursor: not-allowed; }
        .table-wrap { width: 100%; overflow: auto; max-height: calc(100vh - 360px); }
        table { width: 100%; min-width: 1950px; border-collapse: collapse; font-size: .86rem; }
        thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 10px 8px;
            color: #fff;
            background: #134e4a;
            text-align: left;
            white-space: nowrap;
        }
        tbody td { padding: 9px 8px; border-bottom: 1px solid #dbe5e4; white-space: nowrap; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        td[data-batch-cell] { text-align: center; vertical-align: middle; }
        .pending-badge, .fulfilled-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 104px;
            padding: 6px 9px;
            border-radius: 7px;
            font-weight: 800;
        }
        .pending-badge { color: #fff; background: #b45309; }
        .fulfilled-badge { color: #fff; background: #047857; }
        .pending-row td { color: #64748b; background: #fff; }
        .pending-row td:first-child { background: #fff7ed; }
        .fulfilled-row td { background: #dcfce7; }
        .fulfilled-row td:first-child { background: #d1fae5; }
        .pending-details { height: 36px; }
        .batch-total-row td { color: #123b39; background: #ecfdf5; font-weight: 800; }
        .batch-total-row td:first-child { text-align: right; }
        .empty { padding: 24px; text-align: center; color: #64748b; }
        @media (max-width: 700px) {
            body { padding: 8px; }
            .header { flex-wrap: wrap; padding: 12px; }
            h1 { font-size: 1.2rem; }
            .scanner-stage { width: min(100%, 54vh, 460px); max-height: 54vh; }
            .table-wrap { max-height: 60vh; }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <a class="back" href="initialPage.php"><i class="fas fa-arrow-left"></i> Back</a>
            <h1><i class="fas fa-qrcode"></i> Roll Verification</h1>
        </header>

        <div class="message" id="message" role="status" aria-live="polite" aria-hidden="true">
            <div class="message-card">
                <button class="message-close" id="closeMessageButton" type="button" aria-label="Close message">&times;</button>
                <div class="message-icon"><i id="messageIcon" class="fas fa-circle-check"></i></div>
                <h3 class="message-title" id="messageTitle"></h3>
                <p class="message-text" id="messageText"></p>
            </div>
        </div>

        <section class="panel" id="verificationPanel">
            <h2 class="panel-title">Scan or Enter a Roll to Find Its Batch, Then Verify Every Roll</h2>
            <div class="summary">
                <div class="summary-item">Batch: <span id="batchNumber">-</span></div>
                <div class="summary-item">Total QTY: <span id="batchTotalQty">-</span></div>
                <div class="summary-item">Rolls verified: <span id="verifiedCount">0</span> / <span id="totalCount">0</span></div>
            </div>
            <div class="scanner-layout">
                <div class="scanner-stage">
                    <div id="qr-reader"></div>
                </div>
                <div class="scanner-controls">
                    <button class="button secondary" type="button" id="switchCameraButton"><i class="fas fa-camera"></i> <span>Switch Camera</span></button>
                    <button class="button secondary" type="button" id="anotherBatchButton"><i class="fas fa-arrows-rotate"></i> Another Batch Scan</button>
                    <div class="manual-unlock">
                        <div class="manual-password-row">
                            <input type="password" id="manualPassword" autocomplete="off" placeholder="Pass For Entry">
                            <button class="button" type="button" id="unlockManualButton">Unlock</button>
                        </div>
                    </div>
                </div>
                <div class="manual-scan">
                    <form class="scan-form" id="rollForm">
                        <div class="field">
                            <label for="rollInput">Roll Number</label>
                            <div class="roll-input-group">
                                <input type="text" id="rollInput" autocomplete="off" placeholder="Scan or enter roll number" disabled>
                                <button class="button" type="submit" id="verifyButton" disabled>Verify Roll</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="batch-panel-heading"><h2 class="panel-title">Batch Rolls</h2></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th><th>Roll</th><th>Batch</th><th>PO Number</th><th>Color</th><th>QTY</th>
                            <th>MC NO</th><th>SONO</th><th>Buyer</th><th>Style</th><th>MC Dia</th><th>Customer</th>
                            <th>O / T</th><th>SL/VQ</th><th>Fabrics Type</th><th>GSM</th><th>Dia</th>
                            <th>Yarn Type</th><th>Yarn Count</th><th>Yarn Brand</th>
                        </tr>
                    </thead>
                    <tbody id="batchRows">
                        <tr><td class="empty" colspan="20">Scan or enter a roll number to load its batch.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="verified-roll-actions">
                <button class="button" type="button" id="saveVerifiedButton" disabled>
                    <i class="fas fa-save"></i> Verified Rolls
                </button>
            </div>
        </section>
    </main>

    <script>
        (function() {
            'use strict';
            var rollForm = document.getElementById('rollForm');
            var rollInput = document.getElementById('rollInput');
            var verifyButton = document.getElementById('verifyButton');
            var manualPassword = document.getElementById('manualPassword');
            var unlockManualButton = document.getElementById('unlockManualButton');
            var manualEntryAuthorized = false;
            var saveVerifiedButton = document.getElementById('saveVerifiedButton');
            var verificationPanel = document.getElementById('verificationPanel');
            var message = document.getElementById('message');
            var messageTitle = document.getElementById('messageTitle');
            var messageText = document.getElementById('messageText');
            var messageIcon = document.getElementById('messageIcon');
            var batchRows = document.getElementById('batchRows');
            var batch = null;
            var expectedByRoll = new Map();
            var fulfilled = new Set();
            var messageTimer = null;
            var processing = false;
            var savingVerifiedRolls = false;
            var scanner = null;
            var scannerRunning = false;
            var cameraStarting = false;
            var cameraSwitching = false;
            var cameraFacingMode = 'environment';
            var currentCameraId = '';
            var availableCameras = [];
            var lastScannerRoll = '';
            var lastScannerTime = 0;

            function escapeHtml(value) {
                var span = document.createElement('span');
                span.textContent = value === null || value === undefined ? '' : String(value);
                return span.innerHTML;
            }

            function extractRollValue(value) {
                var text = String(value || '').trim();
                if (!text) return '';
                if (text.indexOf('|') !== -1) text = text.split('|')[0].trim();
                var match = text.match(/ROLL\s*:\s*([^\r\n]+)/i);
                return match && match[1] ? match[1].trim() : text;
            }

            function showMessage(text, kind, duration) {
                var messageKind = kind || 'info';
                var titles = {
                    success: 'Verification Successful',
                    warning: 'Roll Not Matched',
                    error: 'Verification Error',
                    info: 'Notice'
                };
                var icons = {
                    success: 'fas fa-circle-check',
                    warning: 'fas fa-triangle-exclamation',
                    error: 'fas fa-triangle-exclamation',
                    info: 'fas fa-circle-info'
                };
                window.clearTimeout(messageTimer);
                messageTitle.textContent = titles[messageKind] || titles.info;
                messageText.textContent = text;
                messageIcon.className = icons[messageKind] || icons.info;
                message.className = 'message show ' + messageKind;
                message.setAttribute('aria-hidden', 'false');
                messageTimer = window.setTimeout(hideMessage, duration || 2000);
            }

            function hideMessage() {
                window.clearTimeout(messageTimer);
                message.className = 'message';
                message.setAttribute('aria-hidden', 'true');
            }

            function updateSummary() {
                document.getElementById('batchNumber').textContent = batch ? batch.card_no : '-';
                var verifiedQty = batch ? calculateVerifiedQty(batch.rows) : null;
                document.getElementById('batchTotalQty').textContent = verifiedQty === null
                    ? '-'
                    : verifiedQty.toLocaleString(undefined, {maximumFractionDigits: 3});
                document.getElementById('verifiedCount').textContent = String(fulfilled.size);
                document.getElementById('totalCount').textContent = batch ? String(batch.rows.length) : '0';
                verificationPanel.hidden = Boolean(batch && batch.rows.length > 0 &&
                    fulfilled.size === batch.rows.length);
                saveVerifiedButton.disabled = savingVerifiedRolls || !batch ||
                    batch.rows.length === 0 || fulfilled.size !== batch.rows.length;
            }

            function calculateVerifiedQty(rows) {
                var total = 0;
                rows.forEach(function(row) {
                    if (!fulfilled.has(String(row.ROLL))) return;
                    var qtyText = String(row.QTY || '').replace(/,/g, '').trim();
                    var qtyValue = Number(qtyText);
                    if (qtyText !== '' && Number.isFinite(qtyValue)) {
                        total += qtyValue;
                    }
                });
                return total;
            }

            function renderRows() {
                if (!batch || !batch.rows.length) {
                    batchRows.innerHTML = '<tr><td class="empty" colspan="20">Scan or enter a roll number to load its batch.</td></tr>';
                    updateSummary();
                    return;
                }
                var details = [
                    'PO_NUMBER', 'COLOR', 'QTY', 'DYMCNO', 'SONO', 'BUYER', 'STYLE',
                    'MCDIA', 'CUSTOMER', 'O_T', 'SL', 'FTYPE', 'FGSM', 'FDIA',
                    'YTYPE', 'YCOUNT', 'YBRAND'
                ];
                var html = '';
                for (var start = 0; start < batch.rows.length;) {
                    var end = start + 1;
                    while (end < batch.rows.length &&
                        String(batch.rows[end].BCARD) === String(batch.rows[start].BCARD)) {
                        end++;
                    }
                    var totalQty = calculateVerifiedQty(batch.rows.slice(start, end));
                    for (var index = start; index < end; index++) {
                        var row = batch.rows[index];
                        var isFulfilled = fulfilled.has(String(row.ROLL));
                        html += '<tr class="' + (isFulfilled ? 'fulfilled-row' : 'pending-row') + '">' +
                            '<td>' + (isFulfilled
                                ? '<span class="fulfilled-badge"><i class="fas fa-circle-check"></i> FULFILLED</span>'
                                : '<span class="pending-badge"><i class="fas fa-hourglass-half"></i> PENDING</span>') + '</td>' +
                            '<td>' + escapeHtml(row.ROLL) + '</td>';
                        if (index === start) {
                            html += '<td data-batch-cell rowspan="' + (end - start) + '">' +
                                escapeHtml(row.BCARD) + '</td>';
                        }
                        if (isFulfilled) {
                            html += details.map(function(field) {
                                return '<td>' + escapeHtml(row[field]) + '</td>';
                            }).join('');
                        } else {
                            html += '<td class="pending-details" colspan="' + details.length + '"></td>';
                        }
                        html += '</tr>';
                    }
                    html += '<tr class="batch-total-row">' +
                        '<td colspan="5">Total QTY :</td>' +
                        '<td>' + escapeHtml(totalQty.toLocaleString(undefined, {maximumFractionDigits: 3})) + '</td>' +
                        '<td colspan="14"></td>' +
                        '</tr>';
                    start = end;
                }
                batchRows.innerHTML = html;
                updateSummary();
            }

            function loadBatchByRoll(roll, isManualEntry) {
                var url = new URL('ajaxDyeing_roll_verification.php', window.location.href);
                url.searchParams.set('action', 'load_batch_by_roll');
                url.searchParams.set('roll', roll);
                if (isManualEntry) url.searchParams.set('manual', '1');
                if (batch) url.searchParams.set('current_batch', batch.card_no);
                return fetch(url.toString()).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Could not load batch rolls.');
                        }
                        return data;
                    });
                });
            }

            function verifyRoll(rawValue, isManualEntry) {
                var roll = extractRollValue(rawValue);
                if (!roll || processing) return;
                if (isManualEntry && !manualEntryAuthorized) {
                    showMessage('Unlock manual entry with the password first.', 'warning');
                    return;
                }
                processing = true;
                verifyButton.disabled = true;
                loadBatchByRoll(roll, isManualEntry).then(function(foundBatch) {
                    var isDifferentBatch = !batch || String(foundBatch.card_no) !== String(batch.card_no);
                    if (isDifferentBatch) {
                        fulfilled.clear();
                    }
                    batch = foundBatch;
                    expectedByRoll.clear();
                    batch.rows.forEach(function(batchRow) {
                        expectedByRoll.set(String(batchRow.ROLL), batchRow);
                    });
                    var row = expectedByRoll.get(roll);
                    if (!row) {
                        showMessage('Roll ' + roll + ' not matched with this batch.', 'warning');
                        return;
                    }
                    if (fulfilled.has(roll)) {
                        showMessage('Roll ' + roll + ' already verified.', 'warning');
                        return;
                    }
                    fulfilled.add(roll);
                    renderRows();
                    if (fulfilled.size === batch.rows.length) {
                        showMessage('Roll ' + roll + ' verified. All rolls in this batch are complete.', 'success');
                    } else {
                        showMessage('Roll ' + roll + ' verified successfully.', 'success');
                    }
                }).catch(function(error) {
                    var errorMessage = error.message || 'Could not verify this roll.';
                    showMessage(errorMessage.indexOf(roll) !== -1
                        ? errorMessage
                        : 'Roll ' + roll + ': ' + errorMessage, 'error');
                }).finally(function() {
                    processing = false;
                    verifyButton.disabled = !manualEntryAuthorized;
                    rollInput.value = '';
                    if (manualEntryAuthorized) rollInput.focus();
                });
            }

            rollForm.addEventListener('submit', function(event) {
                event.preventDefault();
                verifyRoll(rollInput.value, true);
            });

            function setManualEntryAuthorized(isAuthorized) {
                manualEntryAuthorized = isAuthorized;
                rollInput.disabled = !isAuthorized;
                verifyButton.disabled = !isAuthorized || processing;
                document.querySelector('.manual-scan').classList.toggle('manual-entry-locked', !isAuthorized);
                if (isAuthorized) rollInput.focus();
            }

            rollInput.addEventListener('click', function() {
                if (!manualEntryAuthorized) {
                    showMessage('Enter valid Password for Unlock.', 'warning');
                }
            });

            unlockManualButton.addEventListener('click', function() {
                var password = manualPassword.value;
                if (!password) {
                    showMessage('Enter the password to unlock manual roll entry.', 'warning');
                    manualPassword.focus();
                    return;
                }
                unlockManualButton.disabled = true;
                fetch('ajaxDyeing_roll_verification.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'authorize_manual_entry', password: password})
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
                    showMessage(error.message || 'Incorrect password.', 'error');
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

            saveVerifiedButton.addEventListener('click', function() {
                if (!batch || batch.rows.length === 0 || fulfilled.size !== batch.rows.length || savingVerifiedRolls) return;
                var cardNo = batch.card_no;
                var rollsToSave = Array.from(fulfilled);
                savingVerifiedRolls = true;
                saveVerifiedButton.disabled = true;
                fetch('ajaxDyeing_roll_verification.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'save_verified_rolls',
                        card_no: cardNo,
                        rolls: rollsToSave
                    })
                }).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Could not save verified rolls.');
                        }
                        return data;
                    });
                }).then(function(data) {
                    if (data.inserted_rolls && data.inserted_rolls.length) {
                        showMessage(
                            'Batch ' + cardNo + ': verified rolls saved successfully. Rolls: ' +
                                data.inserted_rolls.join(', ') + '.',
                            'success',
                            3000
                        );
                    } else {
                        showMessage(
                            'Batch ' + cardNo + ': these rolls were already saved. Rolls: ' +
                                rollsToSave.join(', ') + '.',
                            'warning',
                            3000
                        );
                    }
                }).catch(function(error) {
                    showMessage(
                        'Batch ' + cardNo + ': could not save verified rolls (' +
                            rollsToSave.join(', ') + '). ' + (error.message || 'Please try again.') ,
                        'error',
                        3000
                    );
                }).finally(function() {
                    savingVerifiedRolls = false;
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                });
            });

            document.getElementById('closeMessageButton').addEventListener('click', hideMessage);
            message.addEventListener('click', function(event) {
                if (event.target === message) hideMessage();
            });

            function handleScannerResult(decodedText) {
                var roll = extractRollValue(decodedText);
                var now = Date.now();
                if (!roll || fulfilled.has(roll) ||
                    (roll === lastScannerRoll && now - lastScannerTime < 2000)) return;
                lastScannerRoll = roll;
                lastScannerTime = now;
                verifyRoll(roll, false);
            }

            document.getElementById('anotherBatchButton').addEventListener('click', function() {
                window.location.reload();
            });

            function cameraErrorMessage(error) {
                var detail = error && error.message ? error.message : String(error || '');
                if (!window.isSecureContext) {
                    return 'Camera access requires HTTPS or localhost. Open this page on localhost or through a secure HTTPS connection.';
                }
                if (/NotAllowedError|Permission|denied/i.test(detail)) {
                    return 'Camera permission was denied. Allow camera access in your browser settings, then try again.';
                }
                if (/NotFoundError|DevicesNotFoundError|no camera/i.test(detail)) {
                    return 'No available camera was found on this device.';
                }
                if (/NotReadableError|TrackStartError|Could not start video source/i.test(detail)) {
                    return 'The camera is busy or unavailable. Close other apps using it and try again.';
                }
                return 'Could not start camera: ' + detail;
            }

            function scannerConfig() {
                return {
                    fps: 30,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        var edge = Math.min(240, Math.floor(viewfinderWidth * 0.7), Math.floor(viewfinderHeight * 0.7));
                        return {width: edge, height: edge};
                    },
                    aspectRatio: 1
                };
            }

            function chooseCamera(cameras, facingMode) {
                var matchingCamera = cameras.find(function(camera) {
                    var label = camera.label || '';
                    return facingMode === 'user'
                        ? /front|user|facetime|selfie/i.test(label)
                        : /back|rear|environment|world/i.test(label);
                });
                if (matchingCamera) return matchingCamera;
                if (currentCameraId && cameras.length > 1) {
                    var otherCamera = cameras.find(function(camera) {
                        return camera.id !== currentCameraId;
                    });
                    if (otherCamera) return otherCamera;
                }
                return facingMode === 'environment' ? cameras[cameras.length - 1] : cameras[0];
            }

            function startCamera(facingMode) {
                if (cameraStarting || scannerRunning) return Promise.resolve();
                if (typeof Html5Qrcode === 'undefined') {
                    showMessage('Camera scanner library could not be loaded. Check your internet connection or enter the roll number manually.', 'error');
                    return Promise.resolve();
                }
                if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showMessage(cameraErrorMessage(''), 'error');
                    return Promise.resolve();
                }

                cameraStarting = true;
                var requestedFacingMode = facingMode || cameraFacingMode;
                return Html5Qrcode.getCameras().then(function(cameras) {
                    if (!cameras || !cameras.length) {
                        throw new Error('No camera devices were found.');
                    }
                    availableCameras = cameras;
                    var selectedCamera = chooseCamera(cameras, requestedFacingMode);
                    if (!scanner) scanner = new Html5Qrcode('qr-reader', {verbose: false});
                    var cameraConfig = selectedCamera
                        ? selectedCamera.id
                        : {facingMode: requestedFacingMode};
                    return scanner.start(
                        cameraConfig,
                        scannerConfig(),
                        handleScannerResult,
                        function() {}
                    ).then(function() {
                        currentCameraId = selectedCamera ? selectedCamera.id : '';
                        cameraFacingMode = requestedFacingMode;
                    });
                }).then(function() {
                    scannerRunning = true;
                    document.querySelector('#switchCameraButton span').textContent = 'Switch Camera';
                }).catch(function(error) {
                    if (scanner && scanner.isScanning) {
                        scanner.stop().catch(function(stopError) {
                            console.error('Could not release camera after scanner startup failed:', stopError);
                        });
                    }
                    scannerRunning = false;
                    showMessage(cameraErrorMessage(error), 'error');
                }).finally(function() {
                    cameraStarting = false;
                });
            }

            function stopCamera() {
                if (!scanner || !scannerRunning) return Promise.resolve();
                return scanner.stop().then(function() {
                    scannerRunning = false;
                });
            }

            document.getElementById('switchCameraButton').addEventListener('click', function() {
                if (cameraStarting || cameraSwitching) return;
                cameraSwitching = true;
                var nextFacingMode = cameraFacingMode === 'environment' ? 'user' : 'environment';
                var stopPromise = scannerRunning ? stopCamera() : Promise.resolve();
                stopPromise.then(function() {
                    return startCamera(nextFacingMode);
                }).catch(function(error) {
                    showMessage('Could not switch camera: ' + error, 'error');
                }).finally(function() {
                    cameraSwitching = false;
                });
            });

            function resumeCameraIfNeeded() {
                if (document.hidden || cameraStarting || cameraSwitching) return;
                if (!scannerRunning || (scanner && !scanner.isScanning)) {
                    scannerRunning = false;
                    startCamera(cameraFacingMode);
                }
            }

            document.addEventListener('visibilitychange', resumeCameraIfNeeded);
            window.addEventListener('pageshow', resumeCameraIfNeeded);
            window.addEventListener('pagehide', function() {
                if (scannerRunning) {
                    scanner.stop().catch(function(error) {
                        console.error('Could not release camera while leaving Roll Verification:', error);
                    }).finally(function() {
                        scannerRunning = false;
                    });
                }
            });

            setManualEntryAuthorized(manualEntryAuthorized);
            startCamera('environment');
        })();
    </script>
</body>
</html>
