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
    <title>FabLine | Roll Verification</title>
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
            display: none;
            padding: 11px 13px;
            margin: 0 0 12px;
            border-radius: 8px;
            font-weight: 700;
        }
        .message.info { display: block; color: #075985; background: #e0f2fe; }
        .message.error { display: block; color: #991b1b; background: #fee2e2; }
        .message.success { display: block; color: #065f46; background: #d1fae5; }
        .summary { display: flex; flex-wrap: wrap; gap: 10px; margin: 0 0 14px; }
        .summary-item { padding: 9px 12px; border-radius: 8px; background: #ecfdf5; font-weight: 800; }
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
        .scanner-controls { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 10px; }
        .scanner-controls .secondary { background: #475569; }
        .manual-scan { width: min(100%, 1100px); }
        .scan-form { display: flex; flex-wrap: wrap; align-items: end; gap: 10px; }
        .field { flex: 1; min-width: 220px; }
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
        tbody tr.fulfilled { background: #dcfce7; }
        .roll-badge { display: inline-block; padding: 4px 9px; border-radius: 20px; color: #065f46; background: #ccfbf1; font-weight: 800; }
        .pending-badge, .fulfilled-badge { display: inline-block; padding: 4px 9px; border-radius: 20px; font-weight: 800; }
        .pending-badge { color: #92400e; background: #fef3c7; }
        .fulfilled-badge { color: #065f46; background: #bbf7d0; }
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
            <h1><i class="fas fa-barcode"></i> Roll Verification</h1>
        </header>

        <section class="panel" id="verificationPanel">
            <h2 class="panel-title">Scan or Enter a Roll to Find Its Batch, Then Verify Every Roll</h2>
            <div class="message" id="message" role="status" aria-live="polite"></div>
            <div class="summary">
                <div class="summary-item">Batch: <span id="batchNumber">-</span></div>
                <div class="summary-item">Rolls verified: <span id="verifiedCount">0</span> / <span id="totalCount">0</span></div>
            </div>
            <div class="scanner-layout">
                <div class="scanner-stage">
                    <div id="qr-reader"></div>
                </div>
                <div class="scanner-controls">
                    <button class="button secondary" type="button" id="switchCameraButton">Switch to Front Camera</button>
                    <button class="button secondary" type="button" id="anotherBatchButton">Another Batch Scan</button>
                </div>
                <div class="manual-scan">
                    <form class="scan-form" id="rollForm">
                        <div class="field">
                            <label for="rollInput">Roll Number</label>
                            <div class="roll-input-group">
                                <input type="text" id="rollInput" autocomplete="off" placeholder="Scan or enter roll number">
                                <button class="button" type="submit" id="verifyButton">Verify Roll</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Batch Rolls</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th><th>Roll</th><th>Batch</th><th>PO Number</th><th>Color</th><th>QTY</th>
                            <th>MC NO</th><th>SONO</th><th>Buyer</th><th>Style</th><th>MC Dia</th><th>Customer</th>
                            <th>O / T</th><th>SL/VQ</th><th>Fabrics Type</th><th>GSM</th><th>Finish Dia</th>
                            <th>Yarn Type</th><th>Yarn Count</th><th>Yarn Brand</th><th>Feeder Plan</th><th>Lot No</th><th>Category</th>
                        </tr>
                    </thead>
                    <tbody id="batchRows">
                        <tr><td class="empty" colspan="23">Scan or enter a roll number to load its batch.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        (function() {
            'use strict';
            var rollForm = document.getElementById('rollForm');
            var rollInput = document.getElementById('rollInput');
            var verifyButton = document.getElementById('verifyButton');
            var message = document.getElementById('message');
            var batchRows = document.getElementById('batchRows');
            var batch = null;
            var expectedByRoll = new Map();
            var fulfilled = new Set();
            var processing = false;
            var scanner = null;
            var scannerRunning = false;
            var cameraStarting = false;
            var cameraSwitching = false;
            var cameraFacingMode = 'environment';
            var currentCameraId = '';
            var availableCameras = [];

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

            function showMessage(text, kind) {
                message.textContent = text;
                message.className = 'message ' + (kind || 'info');
            }

            function updateSummary() {
                document.getElementById('batchNumber').textContent = batch ? batch.card_no : '-';
                document.getElementById('verifiedCount').textContent = String(fulfilled.size);
                document.getElementById('totalCount').textContent = batch ? String(batch.rows.length) : '0';
            }

            function renderRows() {
                if (!batch || !batch.rows.length) {
                    batchRows.innerHTML = '<tr><td class="empty" colspan="23">Scan or enter a roll number to load its batch.</td></tr>';
                    updateSummary();
                    return;
                }
                batchRows.innerHTML = batch.rows.map(function(row) {
                    var isFulfilled = fulfilled.has(String(row.ROLL));
                    return '<tr class="' + (isFulfilled ? 'fulfilled' : '') + '">' +
                        '<td>' + (isFulfilled
                            ? '<span class="fulfilled-badge">FULFILLED</span>'
                            : '<span class="pending-badge">PENDING</span>') + '</td>' +
                        '<td><span class="roll-badge">' + escapeHtml(row.ROLL) + '</span></td>' +
                        '<td>' + escapeHtml(row.BCARD) + '</td>' +
                        '<td>' + escapeHtml(row.PO_NUMBER) + '</td>' +
                        '<td>' + escapeHtml(row.COLOR) + '</td>' +
                        '<td>' + escapeHtml(row.QTY) + '</td>' +
                        '<td>' + escapeHtml(row.DYMCNO) + '</td>' +
                        '<td>' + escapeHtml(row.SONO) + '</td>' +
                        '<td>' + escapeHtml(row.BUYER) + '</td>' +
                        '<td>' + escapeHtml(row.STYLE) + '</td>' +
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
                }).join('');
                updateSummary();
            }

            function loadBatchByRoll(roll) {
                var url = new URL('ajaxDyeing_roll_verification.php', window.location.href);
                url.searchParams.set('action', 'load_batch_by_roll');
                url.searchParams.set('roll', roll);
                return fetch(url.toString()).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Could not load batch rolls.');
                        }
                        return data;
                    });
                });
            }

            function verifyRoll(rawValue) {
                var roll = extractRollValue(rawValue);
                if (!roll || processing) return;
                processing = true;
                verifyButton.disabled = true;
                var batchPromise = batch ? Promise.resolve(batch) : loadBatchByRoll(roll);
                batchPromise.then(function(foundBatch) {
                    if (!batch) {
                        batch = foundBatch;
                        batch.rows.forEach(function(row) {
                            expectedByRoll.set(String(row.ROLL), row);
                        });
                    }
                    var row = expectedByRoll.get(roll);
                    if (!row) {
                        showMessage('Roll ' + roll + ' does not belong to Batch ' + batch.card_no + '.', 'error');
                        return;
                    }
                    if (fulfilled.has(roll)) {
                        showMessage('Roll ' + roll + ' has already been verified.', 'error');
                        return;
                    }
                    fulfilled.add(roll);
                    renderRows();
                    if (fulfilled.size === batch.rows.length) {
                        showMessage('All ' + fulfilled.size + ' rolls in Batch ' + batch.card_no + ' have been verified.', 'success');
                    } else {
                        showMessage('Roll ' + roll + ' verified for Batch ' + batch.card_no + '.', 'success');
                    }
                }).catch(function(error) {
                    showMessage(error.message, 'error');
                }).finally(function() {
                    processing = false;
                    verifyButton.disabled = false;
                    rollInput.value = '';
                    rollInput.focus();
                });
            }

            rollForm.addEventListener('submit', function(event) {
                event.preventDefault();
                verifyRoll(rollInput.value);
            });

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
                        function(decodedText) { verifyRoll(decodedText); },
                        function() {}
                    ).then(function() {
                        currentCameraId = selectedCamera ? selectedCamera.id : '';
                        cameraFacingMode = requestedFacingMode;
                    });
                }).then(function() {
                    scannerRunning = true;
                    document.getElementById('switchCameraButton').textContent =
                        cameraFacingMode === 'environment' ? 'Switch to Front Camera' : 'Switch to Back Camera';
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

            document.addEventListener('visibilitychange', function() {
                if (document.hidden && scannerRunning) {
                    stopCamera().catch(function(error) {
                        showMessage('Could not stop the camera while the page is hidden: ' + error, 'error');
                    });
                }
            });
            window.addEventListener('pagehide', function() {
                if (scannerRunning) {
                    scanner.stop().catch(function(error) {
                        console.error('Could not release camera while leaving Roll Verification:', error);
                    });
                }
            });

            rollInput.focus();
            startCamera('environment');
        })();
    </script>
</body>
</html>
