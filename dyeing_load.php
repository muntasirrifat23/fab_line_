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
    <title>FabLine | Dyeing Load</title>
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
        .scanner-layout { display: grid; grid-template-columns: minmax(0, 1fr); justify-items: center; gap: 14px; }
        .scanner-stage {
            position: relative;
            width: min(100%, 54vh, 560px);
            max-height: 54vh;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 24px;
            background: #eef2f7;
            box-shadow: inset 0 0 0 1px #d7e0ea, 0 8px 20px rgba(30, 60, 120, .12);
        }
        #qr-reader, #qr-reader > div { width: 100%; height: 100%; padding: 0 !important; }
        #qr-reader { background: #f4f7fb; }
        #qr-reader video { display: block; width: 100% !important; height: 100% !important; border-radius: 24px; object-fit: cover; }
        .scan-controls { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        #switchCameraButton { background: #2563eb; }
        #resetBatchButton { background: #7c3aed; }
        #resetBatchScanButton { background: #7c3aed; }
        .manual-password-row { display: flex; gap: 6px; }
        .manual-password-row input {
            width: 135px;
            min-width: 0;
            min-height: 42px;
            padding: 9px 10px;
            border: 1px solid #afc8c4;
            border-radius: 8px;
            font: inherit;
        }
        #manualPassword { -webkit-text-security: disc; }
        #unlockManualButton { flex: 0 0 auto; padding-right: 12px; padding-left: 12px; }
        .scan-state {
            width: min(100%, 620px);
            padding: 12px 14px;
            border-radius: 10px;
            color: #075985;
            background: #e0f2fe;
            font-weight: 700;
        }
        .scan-state.operator-ready { color: #065f46; background: #d1fae5; }
        .scan-entry { display: flex; width: min(100%, 620px); gap: 8px; }
        .scan-entry input {
            flex: 1;
            min-width: 0;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #afc8c4;
            border-radius: 8px;
            font: inherit;
        }
        .scan-entry input:focus { outline: 2px solid #54b7a8; border-color: #159685; }
        .operator-summary { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
        .summary-item { padding: 9px 12px; border-radius: 8px; background: #ecfdf5; font-weight: 800; }
        .table-wrap { width: 100%; overflow: auto; max-height: calc(100vh - 220px); }
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
        tbody tr:nth-child(even) td { background: #f8fafc; }
        td[data-batch-cell] { text-align: center; vertical-align: middle; font-weight: 800; }
        .empty { padding: 24px; text-align: center; color: #64748b; }
        .process-badges { display: grid; grid-template-columns: repeat(2, max-content); gap: 5px; }
        .process-badge {
            display: inline-block; padding: 4px 8px; border: 1px solid #99d5c9;
            border-radius: 6px; color: #075e54; background: #e6f8f3;
            font-size: .78rem; font-weight: 750; white-space: nowrap;
        }
        .load-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        #startDyeingLoadButton { background: #047857; }
        [hidden] { display: none !important; }
        .message {
            position: fixed;
            inset: 0;
            z-index: 10;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(15, 23, 42, .48);
        }
        .message.show { display: flex; }
        .message-card {
            position: relative;
            width: min(100%, 420px);
            min-height: 240px;
            padding: 38px 28px 28px;
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
            width: 66px;
            height: 66px;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            border: 2px solid currentColor;
            border-radius: 50%;
            font-size: 1.8rem;
        }
        .message.success .message-icon { color: #16a34a; }
        .message.error .message-icon { color: #d97706; }
        .message-title { margin: 0 0 10px; color: #1f2937; font-size: 1.3rem; }
        .message-text { margin: 0; color: #64748b; line-height: 1.5; }
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
            <h1><i class="fas fa-dolly"></i> Dyeing Load</h1>
        </header>

        <div class="message" id="message" role="status" aria-live="polite" aria-hidden="true">
            <div class="message-card">
                <button class="message-close" id="closeMessage" type="button" aria-label="Close message">&times;</button>
                <div class="message-icon"><i id="messageIcon" class="fas fa-circle-check"></i></div>
                <h2 class="message-title" id="messageTitle"></h2>
                <p class="message-text" id="messageText"></p>
            </div>
        </div>

        <section class="panel" id="scanPanel">
            <h2 class="panel-title">Scan Dyeing Operator ID, Then Batch Card</h2>
            <div class="scanner-layout">
                <div class="scanner-stage"><div id="qr-reader"></div></div>
                <div class="scan-controls">
                    <button class="button" type="button" id="switchCameraButton">
                        <i class="fas fa-camera"></i> <span>Switch Camera</span>
                    </button>
                    <button class="button" type="button" id="resetBatchScanButton">
                        <i class="fas fa-arrows-rotate"></i> Scan Another Batch
                    </button>
                    <form class="manual-password-row" id="manualUnlockForm" autocomplete="off">
                        <input type="text" id="manualPassword" name="manual_access_code" autocomplete="off" placeholder="Pass For Entry" aria-label="Password for manual entry">
                        <button class="button" type="submit" id="unlockManualButton">Unlock</button>
                    </form>
                </div>
                <div class="scan-state" id="scanState">First scan or enter a Dyeing Operator ID.</div>
                <form class="scan-entry locked" id="scanForm">
                    <input type="text" id="scanInput" autocomplete="off" placeholder="Dyeing Operator ID" aria-label="Dyeing Operator ID" readonly>
                    <button class="button" type="submit" id="scanButton">Verify Operator</button>
                </form>
            </div>
        </section>

        <section class="panel" id="batchPanel" hidden>
            <div class="operator-summary" id="operatorSummary" hidden>
                <div class="summary-item">Dyeing Operator: <span id="operatorId">-</span></div>
                <div class="summary-item">Name: <span id="operatorName">-</span></div>
                <div class="summary-item">Batch: <span id="batchNumber">-</span></div>
                <div class="summary-item">Rolls: <span id="rollCount">0</span></div>
            </div>
            <h2 class="panel-title">Verified Batch Rolls</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr id="tableHead"></tr></thead>
                    <tbody id="batchRows">
                        <tr><td class="empty">Scan an operator ID, then a batch card to load verified roll data.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="load-actions">
                <button class="button" type="button" id="startDyeingLoadButton" disabled>
                    <i class="fas fa-play"></i> Dyeing Load Start
                </button>
                <button class="button" type="button" id="resetBatchButton">
                    <i class="fas fa-arrows-rotate"></i> Scan Another Batch
                </button>
            </div>
        </section>
    </main>

    <script>
        (function() {
            'use strict';
            var scanForm = document.getElementById('scanForm');
            var scanInput = document.getElementById('scanInput');
            var scanButton = document.getElementById('scanButton');
            var manualPassword = document.getElementById('manualPassword');
            var unlockManualButton = document.getElementById('unlockManualButton');
            var scanState = document.getElementById('scanState');
            var batchRows = document.getElementById('batchRows');
            var batchPanel = document.getElementById('batchPanel');
            var scanPanel = document.getElementById('scanPanel');
            var startDyeingLoadButton = document.getElementById('startDyeingLoadButton');
            var operatorSummary = document.getElementById('operatorSummary');
            var message = document.getElementById('message');
            var messageTitle = document.getElementById('messageTitle');
            var messageText = document.getElementById('messageText');
            var messageIcon = document.getElementById('messageIcon');
            var operator = null;
            var loadedBatch = '';
            var manualEntryAuthorized = false;
            var scanner = null;
            var scannerRunning = false;
            var scannerStarting = false;
            var scannerStartPromise = null;
            var scannerStopPromise = null;
            var processing = false;
            var messageTimer = null;
            var lastScanValue = '';
            var lastScanTime = 0;
            var cameraFacingMode = 'environment';
            var loadStarted = false;
            var columns = [
                ['ROLL', 'Roll'], ['BCARD', 'Batch'], ['PO_NUMBER', 'PO Number'], ['COLOR', 'Color'],
                ['QTY', 'QTY'], ['MCNO', 'MC NO'], ['SONO', 'SONO'], ['BUYER', 'Buyer'],
                ['STYLE', 'Style'], ['MCDIA', 'MC Dia'], ['CUSTOMER', 'Customer'], ['O_T', 'O / T'],
                ['SL', 'SL/VQ'], ['FTYPE', 'Fabrics Type'], ['FGSM', 'GSM'], ['FDIA', 'Dia'],
                ['YTYPE', 'Yarn Type'], ['YCOUNT', 'Yarn Count'], ['YBRAND', 'Yarn Brand'],
                ['PROCESS', 'Process']
            ];
            var processFields = [
                'SILICON', 'NONSILICON', 'NWASH', 'EWASH', 'ENZYME', 'NONENZYME',
                'PEACH', 'BRUSH', 'AOP', 'SOFTENER', 'NONSOFTENER', 'OTHERS'
            ];

            function escapeHtml(value) {
                var span = document.createElement('span');
                span.textContent = value === null || value === undefined ? '' : String(value);
                return span.innerHTML;
            }

            function normalizeScan(value) {
                var text = String(value || '').trim();
                if (!text) return '';
                if (text.indexOf('|') !== -1) text = text.split('|')[0].trim();
                return text.replace(/^(DY_OP_ID|DYEING_OPERATOR_ID|OPERATOR_ID|BCARD|BATCH_CARD|BATCH)\s*[:=]\s*/i, '').trim();
            }

            function showMessage(title, text, kind) {
                window.clearTimeout(messageTimer);
                messageTitle.textContent = title;
                messageText.textContent = text;
                messageIcon.className = kind === 'success' ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation';
                message.className = 'message show ' + kind;
                message.setAttribute('aria-hidden', 'false');
                messageTimer = window.setTimeout(hideMessage, 2000);
            }

            function hideMessage() {
                window.clearTimeout(messageTimer);
                message.className = 'message';
                message.setAttribute('aria-hidden', 'true');
            }

            function updateScanStep() {
                if (!operator) {
                    scanState.className = 'scan-state';
                    scanState.textContent = 'First scan or enter a Dyeing Operator ID.';
                    scanInput.placeholder = 'Dyeing Operator ID';
                    scanInput.setAttribute('aria-label', 'Dyeing Operator ID');
                    scanButton.textContent = 'Verify Operator';
                    return;
                }
                scanState.className = 'scan-state operator-ready';
                scanState.textContent = 'Operator ' + operator.DY_OP_ID +
                    (operator.DY_OP_NAME ? ' (' + operator.DY_OP_NAME + ')' : '') +
                    ' verified. Now scan or enter a Batch Card.';
                scanInput.placeholder = 'Batch Card Number';
                scanInput.setAttribute('aria-label', 'Batch Card Number');
                scanButton.textContent = 'Load Batch';
                document.getElementById('operatorId').textContent = operator.DY_OP_ID;
                document.getElementById('operatorName').textContent = operator.DY_OP_NAME || '-';
                operatorSummary.hidden = false;
            }

            function renderBatch(data) {
                document.getElementById('batchNumber').textContent = data.card_no;
                document.getElementById('rollCount').textContent = String(data.roll_count);
                document.getElementById('tableHead').innerHTML = columns.map(function(column) {
                    return '<th>' + escapeHtml(column[1]) + '</th>';
                }).join('');
                var html = '';
                for (var start = 0; start < data.rows.length;) {
                    var end = start + 1;
                    while (end < data.rows.length &&
                        String(data.rows[end].BCARD) === String(data.rows[start].BCARD)) {
                        end++;
                    }
                    for (var index = start; index < end; index++) {
                        var row = data.rows[index];
                        html += '<tr>' + columns.map(function(column) {
                            if (column[0] === 'BCARD') {
                                return index === start
                                    ? '<td data-batch-cell rowspan="' + (end - start) + '">' + escapeHtml(row.BCARD) + '</td>'
                                    : '';
                            }
                            if (column[0] === 'PROCESS') {
                                var selected = processFields.filter(function(field) {
                                    return String(row[field] || '').trim() !== '';
                                });
                                var badges = selected.map(function(field) {
                                    return '<span class="process-badge">' + escapeHtml(row[field]) + '</span>';
                                }).join('');
                                return '<td><div class="process-badges">' + (badges || '-') + '</div></td>';
                            }
                            return '<td>' + escapeHtml(row[column[0]]) + '</td>';
                        }).join('') + '</tr>';
                    }
                    start = end;
                }
                batchRows.innerHTML = html;
                loadedBatch = String(data.card_no);
                loadStarted = false;
                startDyeingLoadButton.disabled = false;
                startDyeingLoadButton.innerHTML = '<i class="fas fa-play"></i> Dyeing Load Start';
                batchPanel.hidden = false;
                scanPanel.hidden = true;
            }

            function stopScanner() {
                if (scannerStopPromise) return scannerStopPromise;
                if (scannerStarting && scannerStartPromise) {
                    return scannerStartPromise.then(function() {
                        return stopScanner();
                    });
                }
                if (!scanner || !scannerRunning) return Promise.resolve();
                scannerStopPromise = scanner.stop().then(function() {
                    scannerRunning = false;
                    return scanner.clear();
                }).then(function() {
                    scanner = null;
                    scannerStopPromise = null;
                }).catch(function(error) {
                    scannerRunning = false;
                    scannerStopPromise = null;
                    throw error;
                });
                return scannerStopPromise;
            }

            function request(action, parameters) {
                var url = new URL('ajaxDyeing_load.php', window.location.href);
                url.searchParams.set('action', action);
                Object.keys(parameters).forEach(function(key) {
                    url.searchParams.set(key, parameters[key]);
                });
                return fetch(url.toString()).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) {
                            var error = new Error(data.message || 'Dyeing Load request failed.');
                            error.status = response.status;
                            throw error;
                        }
                        return data;
                    });
                });
            }

            function startDyeingLoad() {
                if (!operator || !loadedBatch || loadStarted) return;
                loadStarted = true;
                startDyeingLoadButton.disabled = true;
                fetch('ajaxDyeing_load.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'start_load',
                        operator_id: operator.DY_OP_ID,
                        card_no: loadedBatch
                    })
                }).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) {
                            var error = new Error(data.message || 'Could not start Dyeing Load.');
                            error.status = response.status;
                            throw error;
                        }
                        return data;
                    });
                }).then(function(data) {
                    startDyeingLoadButton.innerHTML = '<i class="fas fa-circle-check"></i> Load Started';
                    showMessage(
                        'Dyeing Load Started',
                        'Batch ' + data.card_no + ' started for ' + data.roll_count + ' roll(s). Operator: ' +
                            data.operator.DY_OP_NAME + ' (' + data.operator.DY_OP_ID + ').',
                        'success'
                    );
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                }).catch(function(error) {
                    if (error.status === 409) {
                        resetBatchScan();
                        showMessage('Batch Already Loaded', error.message || 'This batch has already been loaded.', 'error');
                        return;
                    }
                    loadStarted = false;
                    startDyeingLoadButton.disabled = false;
                    showMessage(
                        'Dyeing Load Failed',
                        error.message || 'Could not start Dyeing Load.',
                        'error'
                    );
                });
            }

            startDyeingLoadButton.addEventListener('click', startDyeingLoad);

            function processScan(rawValue, manual) {
                var value = normalizeScan(rawValue);
                if (!value || processing) return;
                if (manual && !manualEntryAuthorized) {
                    showMessage('Manual Entry Locked', 'Enter a valid password and select Unlock before manual entry.', 'error');
                    return;
                }
                if (!operator) {
                    processing = true;
                    scanButton.disabled = true;
                    request('verify_operator', {
                        operator_id: value,
                        manual: manual ? '1' : '0'
                    }).then(function(data) {
                        operator = data.operator;
                        updateScanStep();
                        showMessage('Operator Verified', 'Dyeing operator ' + operator.DY_OP_ID + ' is ready.', 'success');
                    }).catch(function(error) {
                        showMessage('Operator Not Found', error.message, 'error');
                    }).finally(function() {
                        processing = false;
                        scanButton.disabled = !manualEntryAuthorized;
                        scanInput.value = '';
                        scanInput.focus();
                    });
                    return;
                }

                if (loadedBatch && value === loadedBatch) return;
                processing = true;
                scanButton.disabled = true;
                request('load_batch', {
                    operator_id: operator.DY_OP_ID,
                    card_no: value,
                    manual: manual ? '1' : '0'
                }).then(function(data) {
                    return stopScanner().then(function() {
                        renderBatch(data);
                        return data;
                    });
                }).then(function(data) {
                    showMessage(
                        'Dyeing Load Ready',
                        'Batch ' + data.card_no + ' loaded with ' + data.roll_count + ' verified roll(s).',
                        'success'
                    );
                }).catch(function(error) {
                    showMessage(
                        error.status === 409 ? 'Batch Already Loaded' : 'Batch Load Failed',
                        error.message || 'Could not load batch.',
                        'error'
                    );
                }).finally(function() {
                    processing = false;
                    scanButton.disabled = !manualEntryAuthorized;
                    scanInput.value = '';
                    if (!scanPanel.hidden) scanInput.focus();
                });
            }

            scanForm.addEventListener('submit', function(event) {
                event.preventDefault();
                processScan(scanInput.value, true);
            });

            scanInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    processScan(scanInput.value, true);
                }
            });

            scanInput.addEventListener('click', function() {
                if (!manualEntryAuthorized) {
                    showMessage('Manual Entry Locked', 'Enter a valid password and select Unlock first.', 'error');
                }
            });

            function setManualEntryAuthorized(isAuthorized) {
                manualEntryAuthorized = isAuthorized;
                scanInput.readOnly = !isAuthorized;
                scanButton.disabled = !isAuthorized || processing;
                if (isAuthorized) scanInput.focus();
            }

            document.getElementById('manualUnlockForm').addEventListener('submit', function(event) {
                event.preventDefault();
                var password = manualPassword.value;
                if (!password) {
                    showMessage('Password Required', 'Enter the password to unlock manual entry.', 'error');
                    manualPassword.focus();
                    return;
                }
                manualPassword.value = '';
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
                    setManualEntryAuthorized(true);
                    showMessage('Manual Entry Unlocked', 'Manual operator and batch entry is ready.', 'success');
                }).catch(function(error) {
                    showMessage('Unlock Failed', error.message || 'Incorrect password.', 'error');
                }).finally(function() {
                    unlockManualButton.disabled = false;
                });
            });

            document.getElementById('closeMessage').addEventListener('click', hideMessage);
            message.addEventListener('click', function(event) {
                if (event.target === message) hideMessage();
            });

            function resetBatchScan() {
                loadedBatch = '';
                batchPanel.hidden = true;
                scanPanel.hidden = false;
                startDyeingLoadButton.disabled = true;
                startDyeingLoadButton.innerHTML = '<i class="fas fa-play"></i> Dyeing Load Start';
                loadStarted = false;
                batchRows.innerHTML = '<tr><td class="empty" colspan="' + columns.length + '">Scan a batch card to load its verified rolls.</td></tr>';
                document.getElementById('tableHead').innerHTML = '';
                document.getElementById('batchNumber').textContent = '-';
                document.getElementById('rollCount').textContent = '0';
                scanInput.value = '';
                updateScanStep();
                scanInput.focus();
                stopScanner().then(function() {
                    startScanner(cameraFacingMode);
                }).catch(function(error) {
                    showMessage('Camera Could Not Restart', error.message || String(error), 'error');
                });
            }

            document.getElementById('resetBatchButton').addEventListener('click', resetBatchScan);
            document.getElementById('resetBatchScanButton').addEventListener('click', resetBatchScan);

            function scannerConfig() {
                return {
                    fps: 30,
                    qrbox: function(width, height) {
                        var edge = Math.min(240, Math.floor(width * 0.7), Math.floor(height * 0.7));
                        return {width: edge, height: edge};
                    },
                    aspectRatio: 1
                };
            }

            function startScanner(facingMode) {
                if (scannerRunning) return Promise.resolve();
                if (scannerStarting) return scannerStartPromise || Promise.resolve();
                if (typeof Html5Qrcode === 'undefined') {
                    showMessage('Camera Unavailable', 'The QR scanner could not be loaded. Refresh the page and try again.', 'error');
                    return Promise.resolve();
                }
                scannerStarting = true;
                cameraFacingMode = facingMode || cameraFacingMode;
                scannerStartPromise = Html5Qrcode.getCameras().then(function(cameras) {
                    if (!cameras || !cameras.length) throw new Error('No camera devices were found.');
                    var preferred = cameras.find(function(camera) {
                        return cameraFacingMode === 'user'
                            ? /front|user|facetime|selfie/i.test(camera.label || '')
                            : /back|rear|environment|world/i.test(camera.label || '');
                    });
                    var selected = preferred || (cameraFacingMode === 'environment'
                        ? cameras[cameras.length - 1]
                        : cameras[0]);
                    if (!scanner) scanner = new Html5Qrcode('qr-reader', {verbose: false});
                    return scanner.start(
                        selected.id,
                        scannerConfig(),
                        function(decodedText) {
                            var value = normalizeScan(decodedText);
                            var now = Date.now();
                            if (!value || (value === lastScanValue && now - lastScanTime < 2000)) return;
                            lastScanValue = value;
                            lastScanTime = now;
                            if (operator && value === String(operator.DY_OP_ID)) return;
                            if (loadedBatch && value === loadedBatch) return;
                            if (navigator.vibrate) navigator.vibrate(20);
                            processScan(decodedText, false);
                        },
                        function() {}
                    ).then(function() {
                        scannerRunning = true;
                        document.querySelector('#switchCameraButton span').textContent = 'Switch Camera';
                    });
                }).catch(function(error) {
                    showMessage('Camera Unavailable', error.message || String(error), 'error');
                }).finally(function() {
                    scannerStarting = false;
                    scannerStartPromise = null;
                });
                return scannerStartPromise;
            }

            document.getElementById('switchCameraButton').addEventListener('click', function() {
                if (scannerStarting) return;
                var nextMode = cameraFacingMode === 'environment' ? 'user' : 'environment';
                if (!scanner || !scannerRunning) {
                    startScanner(nextMode);
                    return;
                }
                scannerStarting = true;
                scanner.stop().then(function() {
                    scannerRunning = false;
                    return scanner.clear();
                }).then(function() {
                    scanner = null;
                    scannerStarting = false;
                    startScanner(nextMode);
                }).catch(function(error) {
                    scannerStarting = false;
                    showMessage('Could Not Switch Camera', error.message || String(error), 'error');
                });
            });

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && !scanPanel.hidden && !scannerRunning) startScanner(cameraFacingMode);
            });
            window.addEventListener('pageshow', function() {
                if (!document.hidden && !scanPanel.hidden && !scannerRunning) startScanner(cameraFacingMode);
            });
            window.addEventListener('pagehide', function() {
                if (scannerRunning && scanner) {
                    scanner.stop().catch(function(error) {
                        console.error('Could not release Dyeing Load camera:', error);
                    }).finally(function() {
                        scannerRunning = false;
                    });
                }
            });

            updateScanStep();
            setManualEntryAuthorized(false);
            startScanner('environment');
        })();
    </script>
</body>
</html>
