<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
unset($_SESSION['finished_production_manual_authorized']);
unset($_SESSION['finished_production_operator_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finished Production</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        [hidden] { display: none !important; }
        body { margin: 0; padding: 16px; color: #123b39; background: linear-gradient(135deg, #d9f1ed, #e5f1fb); font-family: "Segoe UI", Arial, sans-serif; }
        main { width: 100%; margin: 0 auto; }
        .header, .panel { background: #fff; border: 1px solid #c9dedb; border-radius: 16px; box-shadow: 0 10px 24px rgba(15, 70, 65, .1); }
        .header { display: flex; align-items: center; gap: 14px; padding: 16px 20px; margin-bottom: 14px; }
        h1 { margin: 0; font-size: 1.4rem; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 9px 14px; border: 0; border-radius: 8px; color: #fff; background: #087f70; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .back { color: #fff; background: #000; border: 2px solid #000; }
        .back:hover { color: #000; background: #fff; border-color: #000; }
        .button:disabled { background: #94a3b8; cursor: not-allowed; }
        .panel { padding: 18px; margin-bottom: 14px; }
        .panel-title { margin: 0 0 14px; font-size: 1.08rem; font-weight: 800; }
        .scanner-layout { display: grid; grid-template-columns: minmax(0, 1fr); justify-items: center; gap: 14px; }
        .scanner-stage { position: relative; width: min(100%, 54vh, 560px); max-height: 54vh; aspect-ratio: 1 / 1; overflow: hidden; border-radius: 24px; background: #eef2f7; box-shadow: inset 0 0 0 1px #d7e0ea, 0 8px 20px rgba(30, 60, 120, .12); }
        #qr-reader, #qr-reader > div { width: 100%; height: 100%; padding: 0 !important; }
        #qr-reader { background: #f4f7fb; }
        #qr-reader video { display: block; width: 100% !important; height: 100% !important; border-radius: 24px; object-fit: cover; }
        .scan-controls { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; width: min(100%, 620px); }
        #switchCameraButton { background: #2563eb; }
        #scanAnotherButton { background: #7c3aed; }
        .manual-password-row { display: flex; gap: 6px; }
        .manual-password-row input { width: 135px; min-width: 0; min-height: 42px; padding: 9px 10px; border: 1px solid #afc8c4; border-radius: 8px; font: inherit; -webkit-text-security: disc; }
        .scan-entry { display: grid; gap: 8px; width: min(100%, 620px); }
        .scan-state { width: min(100%, 620px); padding: 12px 14px; border-radius: 10px; color: #075985; background: #e0f2fe; font-weight: 700; }
        .scan-state.ready { color: #065f46; background: #d1fae5; }
        .scan-row { display: flex; gap: 8px; width: min(100%, 620px); }
        .scan-row input { flex: 1; min-width: 0; min-height: 44px; padding: 10px 12px; border: 1px solid #afc8c4; border-radius: 8px; font: inherit; }
        .scan-row input:focus { outline: 2px solid #54b7a8; border-color: #159685; }
        .scan-row input:read-only { background: #f8fafc; cursor: not-allowed; }
        .message { position: fixed; inset: 0; z-index: 10; display: none; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .48); }
        .message.show { display: flex; }
        .message-card { position: relative; display: flex; flex-direction: column; justify-content: center; width: min(100%, 420px); min-height: 240px; padding: 38px 28px 28px; border-radius: 12px; background: #fff; box-shadow: 0 18px 48px rgba(15, 23, 42, .25); text-align: center; }
        .message-icon { display: flex; width: 66px; height: 66px; align-items: center; justify-content: center; margin: 0 auto 16px; border: 2px solid currentColor; border-radius: 50%; font-size: 1.8rem; }
        .message.error .message-icon { color: #d97706; }
        .message-title { margin: 0 0 10px; color: #1f2937; font-size: 1.3rem; }
        .message-text { margin: 0; color: #64748b; line-height: 1.5; }
        .message-close { position: absolute; top: 8px; right: 10px; padding: 4px 8px; border: 0; color: #64748b; background: transparent; font-size: 1.25rem; cursor: pointer; }
        .results-wrap { overflow: auto; }
        .results-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .results-header h2 { margin: 0; font-size: 1.08rem; }
        table { width: 100%; min-width: 1800px; border-collapse: collapse; font-size: .88rem; }
        th, td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: middle; white-space: nowrap; }
        th { position: sticky; top: 0; background: #334155; color: #fff; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .process-list { display: flex; flex-wrap: wrap; gap: 4px; max-width: 240px; white-space: normal; }
        .process-chip { padding: 3px 6px; border: 1px solid #99d5c9; border-radius: 6px; background: #e6f8f3; color: #075e54; font-size: .75rem; font-weight: 700; }
        .empty-row td { padding: 24px; color: #64748b; text-align: center; font-weight: 700; }
        .remove-button { min-height: 30px; padding: 5px 8px; background: #64748b; }
        @media (max-width: 760px) {
            body { padding: 10px; }
            .header { flex-wrap: wrap; padding: 12px; }
            h1 { font-size: 1.2rem; }
            .scanner-stage { width: min(100%, 54vh, 460px); max-height: 54vh; }
        }
    </style>
</head>
<body>
    <main>
        <header class="header">
            <a class="back" href="initialPage.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <h1><i class="fa-solid fa-circle-check"></i> Finished Production</h1>
        </header>

        <section class="panel" id="scanPanel">
            <h2 class="panel-title">Scan Finishing Operator ID, Then Roll</h2>
            <div class="scanner-layout">
                <div class="scanner-stage"><div id="qr-reader"></div></div>
                <div class="scan-controls">
                    <button class="button" type="button" id="switchCameraButton"><i class="fa-solid fa-camera"></i> <span>Switch Camera</span></button>
                    <button class="button" type="button" id="scanAnotherButton"><i class="fa-solid fa-arrows-rotate"></i> Scan Another Roll</button>
                    <form class="manual-password-row" id="manualUnlockForm" autocomplete="off">
                        <input type="password" id="manualPassword" autocomplete="off" placeholder="Pass For Entry" aria-label="Password for manual entry">
                        <button class="button" type="submit" id="unlockManualButton">Unlock</button>
                    </form>
                </div>
                <div class="scan-entry">
                    <div class="scan-state" id="scanState">First scan or enter a Finishing Operator ID.</div>
                    <form id="rollForm" class="scan-row">
                        <input id="rollInput" type="text" autocomplete="off" placeholder="Finishing Operator ID" aria-label="Finishing Operator ID" readonly>
                        <button class="button" id="findRollButton" type="submit" disabled>Verify Operator</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="panel" id="finishedPanel" hidden>
            <div class="results-header">
                <h2>Finished Production Rolls (<span id="rollCount">0</span>)</h2>
                <button class="button" type="button" id="scanAnotherResultButton"><i class="fa-solid fa-camera"></i> Scan Another Roll</button>
            </div>
            <div class="results-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th><th>Batch</th><th>DCOUNT</th><th>Roll</th><th>PO Number</th>
                            <th>Color</th><th>QTY</th><th>MCNO</th><th>SONO</th><th>Buyer</th><th>Style</th>
                            <th>MC Dia</th><th>Customer</th><th>O / T</th><th>SL/VQ</th><th>Fabrics Type</th>
                            <th>Finish GSM</th><th>Finish Dia</th><th>Yarn Type</th><th>Yarn Count</th>
                            <th>Yarn Brand</th><th>Process</th><th>Shift</th><th>Start Time</th><th>End Time</th>
                            <th>Operator</th><th>OP Id</th>
                        </tr>
                    </thead>
                    <tbody id="finishedRows">
                        <tr class="empty-row"><td colspan="27">No rolls scanned yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <div class="message" id="message" role="status" aria-live="polite" aria-hidden="true">
        <div class="message-card">
            <button class="message-close" type="button" id="closeMessage" aria-label="Close message">&times;</button>
            <i class="message-icon fa-solid fa-triangle-exclamation" id="messageIcon" aria-hidden="true"></i>
            <h2 class="message-title" id="messageTitle">Manual Entry Locked</h2>
            <p class="message-text" id="messageText">Enter a valid password and select Unlock first.</p>
        </div>
    </div>
    <script>
        (function() {
            var form = document.getElementById('rollForm');
            var input = document.getElementById('rollInput');
            var findButton = document.getElementById('findRollButton');
            var scanState = document.getElementById('scanState');
            var rowsBody = document.getElementById('finishedRows');
            var scanPanel = document.getElementById('scanPanel');
            var finishedPanel = document.getElementById('finishedPanel');
            var rollCount = document.getElementById('rollCount');
            var manualPassword = document.getElementById('manualPassword');
            var unlockManualButton = document.getElementById('unlockManualButton');
            var message = document.getElementById('message');
            var messageTitle = document.getElementById('messageTitle');
            var messageText = document.getElementById('messageText');
            var messageIcon = document.getElementById('messageIcon');
            var scanned = new Map();
            var scanner = null;
            var scannerRunning = false;
            var scannerStarting = false;
            var scannerStartPromise = null;
            var cameraFacingMode = 'environment';
            var processing = false;
            var manualEntryAuthorized = false;
            var operator = null;
            var messageTimer = null;
            var lastScannedValue = '';
            var lastScanTime = 0;
            var processFields = [
                'SILICON', 'NONSILICON', 'NWASH', 'EWASH', 'ENZYME', 'NONENZYME',
                'PEACH', 'BRUSH', 'AOP', 'SOFTENER', 'NONSOFTENER', 'OTHERS'
            ];
            var fieldsBeforeProcess = [
                ['BCARD', 'Batch'], ['DCOUNT', 'DCOUNT'], ['ROLL', 'Roll'],
                ['PO_NUMBER', 'PO Number'], ['COLOR', 'Color'], ['QTY', 'QTY'],
                ['MCNO', 'MCNO'], ['SONO', 'SONO'], ['BUYER', 'Buyer'], ['STYLE', 'Style'],
                ['MCDIA', 'MC Dia'], ['CUSTOMER', 'Customer'], ['O_T', 'O / T'], ['SL', 'SL/VQ'],
                ['FTYPE', 'Fabrics Type'], ['FGSM', 'Finish GSM'], ['FDIA', 'Finish Dia'],
                ['YTYPE', 'Yarn Type'], ['YCOUNT', 'Yarn Count'], ['YBRAND', 'Yarn Brand']
            ];
            var fieldsAfterProcess = [
                ['SHIFT', 'Shift'], ['DYSTART', 'Start Time'], ['DYEND', 'End Time'],
                ['OPERATOR', 'Operator'], ['OP_ID', 'OP Id']
            ];

            function showScanPanel() {
                finishedPanel.hidden = true;
                scanPanel.hidden = false;
                startScanner(cameraFacingMode);
            }

            function showFinishedPanel() {
                scanPanel.hidden = true;
                finishedPanel.hidden = false;
                stopScanner().catch(function(error) {
                    console.error('Could not stop Finished Production camera:', error);
                });
                finishedPanel.scrollIntoView({behavior: 'smooth', block: 'start'});
            }

            function normalizeScan(value) {
                var text = String(value || '').trim();
                if (text.indexOf('|') !== -1) text = text.split('|')[0].trim();
                var match = text.match(/(?:FN_OP_ID|FINISHING_OPERATOR_ID|OPERATOR_ID|OP_ID|ROLL)\s*[:=]\s*([^\r\n]+)/i);
                return match && match[1] ? match[1].trim() : text;
            }

            function setScanState(message, ready) {
                scanState.textContent = message;
                scanState.className = 'scan-state' + (ready ? ' ready' : '');
            }

            function showMessage(title, text) {
                window.clearTimeout(messageTimer);
                messageTitle.textContent = title;
                messageText.textContent = text;
                messageIcon.className = 'message-icon fa-solid fa-triangle-exclamation';
                message.className = 'message show error';
                message.setAttribute('aria-hidden', 'false');
                messageTimer = window.setTimeout(hideMessage, 2000);
            }

            function hideMessage() {
                window.clearTimeout(messageTimer);
                message.className = 'message';
                message.setAttribute('aria-hidden', 'true');
            }

            function setManualEntryAuthorized(authorized) {
                manualEntryAuthorized = authorized;
                input.readOnly = !authorized;
                input.placeholder = operator ? 'Scan or enter roll number' : 'Finishing Operator ID';
                findButton.disabled = !authorized || processing;
                if (operator) {
                    findButton.textContent = 'Find Roll';
                    input.setAttribute('aria-label', 'Roll number');
                } else {
                    findButton.textContent = 'Verify Operator';
                    input.setAttribute('aria-label', 'Finishing Operator ID');
                }
            }

            function escapeHtml(value) {
                var span = document.createElement('span');
                span.textContent = value === null || value === undefined ? '' : String(value);
                return span.innerHTML;
            }

            function processHtml(row) {
                var values = processFields.filter(function(field) {
                    return String(row[field] || '').trim() !== '';
                });
                if (!values.length) return '-';
                return '<div class="process-list">' + values.map(function(field) {
                    return '<span class="process-chip">' + escapeHtml(row[field]) + '</span>';
                }).join('') + '</div>';
            }

            function renderRows() {
                rowsBody.replaceChildren();
                if (!scanned.size) {
                    var empty = rowsBody.insertRow();
                    empty.className = 'empty-row';
                    var cell = empty.insertCell();
                    cell.colSpan = 27;
                    cell.textContent = 'No rolls scanned yet.';
                    rollCount.textContent = '0';
                    return;
                }
                scanned.forEach(function(row, roll) {
                    var tr = rowsBody.insertRow();
                    var actionCell = tr.insertCell();
                    var removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'button remove-button';
                    removeButton.textContent = 'Remove';
                    removeButton.addEventListener('click', function() {
                        scanned.delete(roll);
                        renderRows();
                        if (!scanned.size) showScanPanel();
                    });
                    actionCell.appendChild(removeButton);
                    fieldsBeforeProcess.forEach(function(field) {
                        var cell = tr.insertCell();
                        cell.textContent = row[field[0]] === null || row[field[0]] === undefined
                            ? ''
                            : String(row[field[0]]);
                    });
                    var processCell = tr.insertCell();
                    processCell.innerHTML = processHtml(row);
                    fieldsAfterProcess.forEach(function(field) {
                        var cell = tr.insertCell();
                        cell.textContent = row[field[0]] === null || row[field[0]] === undefined
                            ? ''
                            : String(row[field[0]]);
                    });
                });
                rollCount.textContent = String(scanned.size);
            }

            function lookupRoll(value) {
                var roll = normalizeScan(value);
                if (!roll || processing || !operator) return;
                if (scanned.has(roll)) {
                    setScanState('Roll ' + roll + ' is already in the results.', false);
                    input.value = '';
                    return;
                }
                processing = true;
                findButton.disabled = true;
                var url = new URL('ajaxDyeing_finished_production.php', window.location.href);
                url.searchParams.set('action', 'get_roll');
                url.searchParams.set('roll', roll);
                url.searchParams.set('operator_id', operator.FN_OP_ID);
                if (manualEntryAuthorized) url.searchParams.set('manual', '1');
                setScanState('Looking up roll ' + roll + '...', true);
                fetch(url.toString())
                    .then(function(response) {
                        return response.json().then(function(data) {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Could not find this roll.');
                            }
                            scanned.set(roll, data.row);
                            renderRows();
                            setScanState('Roll ' + roll + ' added for operator ' + operator.FN_OP_ID + '.', true);
                            showFinishedPanel();
                        });
                    })
                    .catch(function(error) {
                        setScanState(error.message, false);
                    })
                    .finally(function() {
                        processing = false;
                        setManualEntryAuthorized(manualEntryAuthorized);
                        input.value = '';
                        input.focus();
                    });
            }

            function verifyOperator(value) {
                var operatorId = normalizeScan(value);
                if (!operatorId || processing) return;
                processing = true;
                findButton.disabled = true;
                setScanState('Verifying Finishing Operator ' + operatorId + '...', true);
                var url = new URL('ajaxDyeing_finished_production.php', window.location.href);
                url.searchParams.set('action', 'verify_operator');
                url.searchParams.set('operator_id', operatorId);
                if (manualEntryAuthorized) url.searchParams.set('manual', '1');
                fetch(url.toString())
                    .then(function(response) {
                        return response.json().then(function(data) {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Could not verify this Finishing Operator.');
                            }
                            operator = data.operator;
                            setManualEntryAuthorized(manualEntryAuthorized);
                            setScanState('Operator ' + operator.FN_OP_ID + ' verified. Scan a roll barcode.', true);
                        });
                    })
                    .catch(function(error) {
                        setScanState(error.message, false);
                    })
                    .finally(function() {
                        processing = false;
                        setManualEntryAuthorized(manualEntryAuthorized);
                        input.value = '';
                        input.focus();
                    });
            }

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                if (!manualEntryAuthorized) {
                    setScanState('Manual entry is locked. Unlock it with the password, or scan the barcode.', false);
                    return;
                }
                if (operator) lookupRoll(input.value);
                else verifyOperator(input.value);
            });

            function processScan(value) {
                if (processing) return;
                if (operator) lookupRoll(value);
                else verifyOperator(value);
            }

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
                    setScanState('Camera scanner is unavailable. Use a barcode scanner or unlock manual entry.', false);
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
                    var selected = preferred || (cameraFacingMode === 'environment' ? cameras[cameras.length - 1] : cameras[0]);
                    if (!scanner) scanner = new Html5Qrcode('qr-reader', {verbose: false});
                    return scanner.start(
                        selected.id,
                        scannerConfig(),
                        function(decodedText) {
                        var scanValue = normalizeScan(decodedText);
                        var now = Date.now();
                        if (!scanValue || (scanValue === lastScannedValue && now - lastScanTime < 2000)) return;
                        lastScannedValue = scanValue;
                        lastScanTime = now;
                        if (operator && scanValue === String(operator.FN_OP_ID)) return;
                        if (navigator.vibrate) navigator.vibrate(20);
                        processScan(decodedText);
                        },
                        function() {}
                    ).then(function() {
                        scannerRunning = true;
                    });
                }).catch(function(error) {
                    setScanState('Could not start camera scanner: ' + (error.message || String(error)), false);
                }).finally(function() {
                    scannerStarting = false;
                    scannerStartPromise = null;
                });
                return scannerStartPromise;
            }

            function stopScanner() {
                if (!scanner || !scannerRunning) return Promise.resolve();
                return scanner.stop().then(function() {
                    scannerRunning = false;
                });
            }

            document.getElementById('switchCameraButton').addEventListener('click', function() {
                if (scannerStarting) return;
                var nextMode = cameraFacingMode === 'environment' ? 'user' : 'environment';
                stopScanner().then(function() {
                    return startScanner(nextMode);
                }).catch(function(error) {
                    setScanState('Could not switch camera: ' + (error.message || String(error)), false);
                });
            });

            document.getElementById('scanAnotherButton').addEventListener('click', function() {
                input.value = '';
                input.focus();
                setScanState(operator
                    ? 'Operator ' + operator.FN_OP_ID + ' is verified. Scan another roll.'
                    : 'First scan or enter a Finishing Operator ID.', Boolean(operator));
            });

            document.getElementById('scanAnotherResultButton').addEventListener('click', function() {
                input.value = '';
                setScanState(operator
                    ? 'Operator ' + operator.FN_OP_ID + ' is verified. Scan another roll.'
                    : 'First scan or enter a Finishing Operator ID.', Boolean(operator));
                showScanPanel();
                input.focus();
            });

            document.getElementById('manualUnlockForm').addEventListener('submit', function(event) {
                event.preventDefault();
                var password = manualPassword.value;
                if (!password) {
                    setScanState('Enter the password to unlock manual entry.', false);
                    manualPassword.focus();
                    return;
                }
                manualPassword.value = '';
                unlockManualButton.disabled = true;
                fetch('ajaxDyeing_finished_production.php?action=authorize_manual_entry', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({password: password})
                }).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) throw new Error(data.message || 'Could not unlock manual entry.');
                    });
                }).then(function() {
                    setManualEntryAuthorized(true);
                    setScanState(operator
                        ? 'Manual entry unlocked. Scan or enter a roll number.'
                        : 'Manual entry unlocked. Enter the Finishing Operator ID first.', true);
                    input.focus();
                }).catch(function(error) {
                    setScanState(error.message || 'Incorrect password. Manual entry remains locked.', false);
                }).finally(function() {
                    unlockManualButton.disabled = false;
                });
            });

            input.addEventListener('click', function() {
                if (!manualEntryAuthorized) {
                    showMessage('Manual Entry Locked', 'Enter a valid password and select Unlock first.');
                }
            });

            input.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (!manualEntryAuthorized) {
                        showMessage('Manual Entry Locked', 'Enter a valid password and select Unlock first.');
                        return;
                    }
                    form.requestSubmit();
                }
            });

            document.getElementById('closeMessage').addEventListener('click', hideMessage);
            message.addEventListener('click', function(event) {
                if (event.target === message) hideMessage();
            });

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && !scanPanel.hidden && !scannerRunning) startScanner(cameraFacingMode);
            });
            window.addEventListener('pageshow', function() {
                if (!document.hidden && !scanPanel.hidden && !scannerRunning) startScanner(cameraFacingMode);
            });
            window.addEventListener('pagehide', function() {
                stopScanner().catch(function(error) {
                    console.error('Could not release Finished Production camera:', error);
                });
            });

            setManualEntryAuthorized(false);
            startScanner('environment');
        })();
    </script>
</body>
</html>
