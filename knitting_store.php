<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting | Store</title>
    
    <!-- Bootstrap 5 & Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <style>
        body {
            background: #f0f2f5;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .scanner-container {
            max-width: 750px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .scanner-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 20px;
        }
        
        .scanner-header h2 {
            color: #1f2937;
            font-weight: 700;
        }
        
        .scanner-header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        #video-container {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            min-height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        #video {
            width: 100%;
            height: auto;
            display: block;
        }
        
        #scan-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 3px solid rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.3);
            pointer-events: none;
        }
        
        #scan-overlay::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            width: 30px;
            height: 30px;
            border-top: 4px solid #00ff88;
            border-left: 4px solid #00ff88;
            border-radius: 4px 0 0 0;
        }
        
        #scan-overlay::after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 30px;
            height: 30px;
            border-bottom: 4px solid #00ff88;
            border-right: 4px solid #00ff88;
            border-radius: 0 0 4px 0;
        }
        
        .scan-line {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 180px;
            height: 2px;
            background: #00ff88;
            animation: scanMove 2s ease-in-out infinite;
            box-shadow: 0 0 15px #00ff88;
        }
        
        @keyframes scanMove {
            0%, 100% { top: 25%; }
            50% { top: 75%; }
        }
        
        .scanner-controls {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 18px;
            flex-wrap: wrap;
        }
        
        .scanner-controls .btn {
            min-width: 140px;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-start {
            background: #10b981;
            border: none;
            color: white;
        }
        
        .btn-start:hover {
            background: #059669;
            color: white;
        }
        
        .btn-stop {
            background: #ef4444;
            border: none;
            color: white;
        }
        
        .btn-stop:hover {
            background: #dc2626;
            color: white;
        }
        
        /* Data Display */
        .data-display {
            margin-top: 20px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e9ecef;
            display: none;
        }
        
        .data-display.active {
            display: block;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 25px;
            font-size: 0.85rem;
        }
        
        .data-item {
            border-bottom: 1px solid #e9ecef;
            padding: 5px 0;
        }
        
        .data-item .field {
            font-weight: 600;
            color: #1e293b;
        }
        
        .data-item .value {
            color: #334155;
            word-break: break-word;
        }
        
        /* Rack Selection */
        .rack-section {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            display: none;
        }
        
        .rack-section.active {
            display: block;
        }
        
        .rack-section h6 {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .rack-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .rack-group .btn {
            min-width: 60px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .rack-group .btn-outline-primary.active-rack {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .sub-rack-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px dashed #d1d5db;
            min-height: 50px;
        }
        
        .sub-rack-group .btn {
            min-width: 55px;
            border-radius: 6px;
            font-size: 13px;
        }
        
        .sub-rack-group .btn-outline-secondary.active-sub {
            background: #6b7280;
            color: white;
            border-color: #6b7280;
        }
        
        .rack-actions {
            margin-top: 15px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .rack-actions .btn {
            min-width: 100px;
            border-radius: 8px;
        }
        
        .btn-save {
            background: #2563eb;
            border: none;
            color: white;
        }
        
        .btn-save:hover {
            background: #1d4ed8;
            color: white;
        }
        
        .btn-save:disabled {
            background: #93a3b8;
            cursor: not-allowed;
        }
        
        .btn-reset {
            background: #e5e7eb;
            border: none;
            color: #374151;
        }
        
        .btn-reset:hover {
            background: #d1d5db;
            color: #1f2937;
        }
        
        /* Status Message */
        .status-msg {
            margin-top: 12px;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }
        
        .status-msg.show {
            display: block;
        }
        
        .status-msg.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .status-msg.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .status-msg.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .manual-input-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .manual-input-section .btn {
            border-radius: 8px;
        }
        
        @media (max-width: 576px) {
            .scanner-container {
                padding: 15px;
            }
            .data-grid {
                grid-template-columns: 1fr;
            }
            .scanner-controls .btn {
                min-width: 100px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="scanner-container">
    <div class="scanner-header">
        <h2><i class="fa-solid fa-qrcode me-2" style="color:#2563eb;"></i> QR Scanner</h2>
        <p>Scan QR code to view and assign rack location</p>
    </div>
    
    <!-- Video Container -->
    <div id="video-container">
        <video id="video" playsinline></video>
        <div id="scan-overlay"></div>
        <div class="scan-line"></div>
        <div style="position:absolute;bottom:15px;left:50%;transform:translateX(-50%);color:white;font-size:12px;background:rgba(0,0,0,0.5);padding:5px 15px;border-radius:20px;pointer-events:none;z-index:10;">
            Point the camera at the QR code
        </div>
    </div>
    
    <!-- Controls -->
    <div class="scanner-controls">
        <button class="btn btn-start" id="startScanBtn">
            <i class="fa-solid fa-play me-1"></i> Start Scan
        </button>
        <button class="btn btn-stop" id="stopScanBtn">
            <i class="fa-solid fa-stop me-1"></i> Stop
        </button>
    </div>
    
    <!-- Manual Input -->
    <div class="manual-input-section">
        <div class="row g-2">
            <div class="col-md-8">
                <input type="text" id="manualRollInput" class="form-control" placeholder="Enter ROLL number manually">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" id="manualFetchBtn">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Fetch
                </button>
            </div>
        </div>
    </div>
    
    <!-- Status Message -->
    <div id="statusMsg" class="status-msg"></div>
    
    <!-- Data Display -->
    <div class="data-display" id="dataDisplay">
        <h6 class="mb-2"><i class="fa-regular fa-rectangle-list me-1"></i> Scanned Data</h6>
        <div class="data-grid" id="scannedData"></div>
    </div>
    
    <!-- Rack Selection -->
    <div class="rack-section" id="rackSection">
        <h6><i class="fa-solid fa-warehouse me-1"></i> Select Rack Location</h6>
        
        <div class="rack-group" id="rackGroup">
            <button class="btn btn-outline-primary" data-rack="A">A</button>
            <button class="btn btn-outline-primary" data-rack="B">B</button>
            <button class="btn btn-outline-primary" data-rack="C">C</button>
        </div>
        
        <div class="sub-rack-group" id="subRackGroup">
            <span class="text-muted" style="font-size:13px;">Select a rack above to see sub-racks</span>
        </div>
        
        <div class="rack-actions">
            <button class="btn btn-reset" id="resetRackBtn">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset
            </button>
            <button class="btn btn-save" id="saveRackBtn">
                <i class="fa-solid fa-floppy-disk me-1"></i> Save
            </button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsqr/1.4.0/jsQR.min.js"></script>

<script>
    // ---------- GLOBALS ----------
    let scannerActive = false;
    let scanLoopActive = false;
    let stream = null;
    let scanCanvas = null;
    let scanContext = null;
    let barcodeDetector = null;
    let currentScannedData = null;
    let selectedRack = null;
    let selectedSubRack = null;
    let isProcessing = false;
    
    // Sub-rack options
    const subRackOptions = {
        'A': ['A1', 'A2', 'A3'],
        'B': ['B1', 'B2', 'B3'],
        'C': ['C1', 'C2', 'C3']
    };
    
    // ---------- API CALLS ----------
    function initScannerCanvas() {
        if (!scanCanvas) {
            scanCanvas = document.createElement('canvas');
            scanCanvas.style.display = 'none';
            document.body.appendChild(scanCanvas);
        }
        if (!scanContext) {
            scanContext = scanCanvas.getContext('2d');
        }
    }

    async function scanFrame() {
        if (!scannerActive || !scanLoopActive) return;

        const video = document.getElementById('video');
        if (video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth > 0 && video.videoHeight > 0) {
            scanCanvas.width = video.videoWidth;
            scanCanvas.height = video.videoHeight;
            scanContext.drawImage(video, 0, 0, scanCanvas.width, scanCanvas.height);

            let qrText = null;

            if (barcodeDetector) {
                try {
                    const barcodes = await barcodeDetector.detect(scanCanvas);
                    if (barcodes && barcodes.length > 0) {
                        qrText = barcodes[0].rawValue || barcodes[0].boundingBox || null;
                    }
                } catch (detectorError) {
                    console.warn('BarcodeDetector failed:', detectorError);
                }
            }

            if (!qrText) {
                const imageData = scanContext.getImageData(0, 0, scanCanvas.width, scanCanvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);
                if (code && code.data) {
                    qrText = code.data;
                }
            }

            if (qrText) {
                scanLoopActive = false;
                processScannedData(qrText);
                return;
            }
        }

        requestAnimationFrame(scanFrame);
    }

    function fetchDataByRoll(roll) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'ajaxKnitting_store.php',
                method: 'GET',
                data: { action: 'get_by_roll', roll: roll },
                dataType: 'json'
            })
            .done(function(resp) {
                if (resp && resp.success) {
                    resolve(resp.data);
                } else {
                    reject(resp.error || 'No data found');
                }
            })
            .fail(function(xhr, status, error) {
                reject('AJAX request failed: ' + error);
            });
        });
    }
    
    function saveRackData(roll, rack) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'ajaxKnitting_store.php',
                method: 'POST',
                data: JSON.stringify({ action: 'save_rack', ROLL: roll, RACK: rack }),
                contentType: 'application/json',
                dataType: 'json'
            })
            .done(function(resp) {
                if (resp && resp.success) {
                    resolve(resp);
                } else {
                    reject(resp.error || 'Save failed');
                }
            })
            .fail(function(xhr, status, error) {
                reject('AJAX request failed: ' + error);
            });
        });
    }
    
    // ---------- SCANNER FUNCTIONS ----------
    async function startScanner() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            
            const video = document.getElementById('video');
            video.srcObject = stream;
            await video.play();
            
            initScannerCanvas();
            if ('BarcodeDetector' in window) {
                try {
                    barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
                } catch (e) {
                    console.warn('BarcodeDetector init failed:', e);
                    barcodeDetector = null;
                }
            }

            scannerActive = true;
            scanLoopActive = true;
            scanFrame();
            showStatus('Scanner started. Point camera at QR code.', 'info');
            document.getElementById('startScanBtn').innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Scanning...';
            
        } catch (err) {
            console.error('Camera error:', err);
            showStatus('Unable to access camera. Please allow camera permissions.', 'error');
            document.getElementById('startScanBtn').innerHTML = '<i class="fa-solid fa-play me-1"></i> Start Scan';
        }
    }
    
    function stopScanner() {
        scannerActive = false;
        scanLoopActive = false;
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        const video = document.getElementById('video');
        video.srcObject = null;
        document.getElementById('startScanBtn').innerHTML = '<i class="fa-solid fa-play me-1"></i> Start Scan';
        showStatus('Scanner stopped.', 'info');
    }
    
    // ---------- PROCESS SCANNED DATA ----------
    async function processScannedData(data) {
        if (isProcessing) return;
        isProcessing = true;
        
        try {
            // If data is a string (QR text), try to extract ROLL
            if (typeof data === 'string') {
                // Try to extract roll number from various QR formats
                let roll = extractRollFromQR(data);
                
                if (roll) {
                    showStatus(`Searching for ROLL: ${roll}...`, 'info');
                    
                    const dbData = await fetchDataByRoll(roll);
                    if (dbData) {
                        currentScannedData = dbData;
                        displayData(dbData);
                        document.getElementById('rackSection').classList.add('active');
                        showStatus('QR Code scanned successfully! Select rack location.', 'success');
                        stopScanner();
                        return;
                    }
                    showStatus(`No data found for ROLL: ${roll}`, 'error');
                    return;
                }
                
                showStatus('Invalid QR code. Please scan a valid knitting QR.', 'error');
                return;
            }
            
            // If data is already an object
            if (data && typeof data === 'object') {
                currentScannedData = data;
                displayData(data);
                document.getElementById('rackSection').classList.add('active');
                showStatus('QR Code scanned successfully! Select rack location.', 'success');
                stopScanner();
            }
            
        } catch (error) {
            showStatus('Error: ' + error, 'error');
        } finally {
            isProcessing = false;
        }
    }
    
    // Extract roll number from any QR format
    function extractRollFromQR(qrText) {
        if (!qrText || typeof qrText !== 'string') return null;
        var text = qrText.trim();
        
        // Format 1: Pipe-delimited (from knitting_qr.php)
        // SUB_TID|BOOKING|SONO|BUYER|MCNO|MC_DIA|STYLE|...
        if (text.indexOf('|') !== -1) {
            var parts = text.split('|');
            var first = (parts[0] || '').trim();
            if (first.length > 0) return first;
        }
        
        // Format 2: KEY: VALUE newline format (from knitting_inspection_report_test.php)
        // ROLL: 12345
        var m = text.match(/ROLL:\s*([^\n\r]+)/i);
        if (m && m[1]) return m[1].trim();
        
        // Format 3: SUB_TID: value
        m = text.match(/SUB_TID:\s*([^\n\r]+)/i);
        if (m && m[1]) return m[1].trim();
        
        // Format 4: Plain numeric (just a roll number directly)
        if (/^\d+$/.test(text) && text.length >= 2) return text;
        
        return null;
    }
    
    // ---------- DISPLAY DATA ----------
    function displayData(data) {
        const dataDiv = document.getElementById('scannedData');
        let html = '';
        
        const displayFields = [
            'BUDAT', 'ROLL', 'BOOKING_NO', 'SONO', 'MCNO', 'MC_DIA', 'BUYER', 'STYLE',
            'YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM', 'FINISH_DIA', 'OPEN_TUBE',
            'COLOR', 'SL_VDQ', 'UQTY', 'LOT_NO', 'T_POINT', 'ACCEPT'
        ];
        
        const fieldLabels = {
            'BUDAT': 'DATE',
            'ROLL': 'ROLL',
            'BOOKING_NO': 'BOOKING',
            'SONO': 'SONO',
            'MCNO': 'MC NO',
            'MC_DIA': 'MC DIA',
            'BUYER': 'BUYER',
            'STYLE': 'STYLE',
            'YARN_TYPE': 'YARN TYPE',
            'YARN_COUNT': 'YARN COUNT',
            'FABRICS_TYPE': 'FABRICS TYPE',
            'FINISH_GSM': 'FINISH GSM',
            'FINISH_DIA': 'FINISH DIA',
            'OPEN_TUBE': 'OPEN/TUBE',
            'COLOR': 'COLOR',
            'SL_VDQ': 'SL/VDQ',
            'UQTY': 'UQTY',
            'LOT_NO': 'LOT NO',
            'T_POINT': 'T.POINT',
            'ACCEPT': 'ACCEPT'
        };
        
        displayFields.forEach(field => {
            const val = data[field] || '';
            if (val !== '') {
                const label = fieldLabels[field] || field;
                html += `<div class="data-item"><span class="field">${label}:</span> <span class="value">${val}</span></div>`;
            }
        });
        
        // Show existing rack if any
        if (data.RACK) {
            html += `<div class="data-item" style="background:#dbeafe;padding:6px 8px;border-radius:4px;border-bottom-color:#93c5fd;">
                <span class="field">RACK:</span> <span class="value" style="font-weight:600;color:#1d4ed8;">${data.RACK}</span>
            </div>`;
        }
        
        dataDiv.innerHTML = html;
        document.getElementById('dataDisplay').classList.add('active');
    }
    
    // ---------- RACK SELECTION ----------
    function updateSubRacks(rack) {
        const subGroup = document.getElementById('subRackGroup');
        const options = subRackOptions[rack] || [];
        
        subGroup.innerHTML = '';
        if (options.length === 0) {
            subGroup.innerHTML = '<span class="text-muted" style="font-size:13px;">No sub-racks available</span>';
            return;
        }
        
        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'btn btn-outline-secondary';
            btn.textContent = opt;
            btn.dataset.subrack = opt;
            btn.onclick = function() {
                subGroup.querySelectorAll('.btn').forEach(b => b.classList.remove('active-sub'));
                this.classList.add('active-sub');
                selectedSubRack = opt;
                showStatus(`Selected: ${selectedRack} - ${selectedSubRack}`, 'info');
            };
            subGroup.appendChild(btn);
        });
        
        selectedSubRack = null;
    }
    
    // ---------- RACK BUTTON EVENTS ----------
    document.querySelectorAll('#rackGroup .btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#rackGroup .btn').forEach(b => b.classList.remove('active-rack'));
            this.classList.add('active-rack');
            selectedRack = this.dataset.rack;
            updateSubRacks(selectedRack);
            showStatus(`Selected Rack: ${selectedRack}`, 'info');
        });
    });
    
    // ---------- SAVE RACK ----------
    document.getElementById('saveRackBtn').addEventListener('click', async function() {
        if (!currentScannedData) {
            showStatus('No data scanned. Please scan a QR code first.', 'error');
            return;
        }
        
        if (!selectedRack) {
            showStatus('Please select a rack (A, B, or C).', 'error');
            return;
        }
        
        if (!selectedSubRack) {
            showStatus('Please select a sub-rack location.', 'error');
            return;
        }
        
        const roll = currentScannedData.ROLL;
        if (!roll) {
            showStatus('No ROLL number found in scanned data.', 'error');
            return;
        }
        
        const saveBtn = document.getElementById('saveRackBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="loading-spinner"></span> Saving...';
        
        try {
            const result = await saveRackData(roll, selectedSubRack);
            showStatus(`✅ Successfully saved to Rack: ${selectedSubRack}`, 'success');
            
            // Update the displayed data with new rack
            currentScannedData.RACK = selectedSubRack;
            displayData(currentScannedData);
            
        } catch (error) {
            showStatus('Error: ' + error, 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save';
        }
    });
    
    // ---------- RESET RACK ----------
    document.getElementById('resetRackBtn').addEventListener('click', function() {
        selectedRack = null;
        selectedSubRack = null;
        
        document.querySelectorAll('#rackGroup .btn').forEach(b => b.classList.remove('active-rack'));
        document.getElementById('subRackGroup').innerHTML = '<span class="text-muted" style="font-size:13px;">Select a rack above to see sub-racks</span>';
        
        showStatus('Rack selection reset.', 'info');
    });
    
    // ---------- STATUS MESSAGE ----------
    function showStatus(msg, type = 'info') {
        const el = document.getElementById('statusMsg');
        el.textContent = msg;
        el.className = 'status-msg show ' + type;
        
        if (type !== 'error') {
            clearTimeout(el._timeout);
            el._timeout = setTimeout(() => {
                el.classList.remove('show');
            }, 8000);
        }
    }
    
    // ---------- MANUAL FETCH ----------
    document.getElementById('manualFetchBtn').addEventListener('click', function() {
        const roll = document.getElementById('manualRollInput').value.trim();
        if (!roll) {
            showStatus('Please enter a ROLL number.', 'error');
            return;
        }
        
        showStatus(`Fetching data for ROLL: ${roll}...`, 'info');
        
        fetchDataByRoll(roll)
            .then(data => {
                if (data) {
                    currentScannedData = data;
                    displayData(data);
                    document.getElementById('rackSection').classList.add('active');
                    showStatus('Data fetched successfully! Select rack location.', 'success');
                    if (scannerActive) stopScanner();
                }
            })
            .catch(error => {
                showStatus('Error: ' + error, 'error');
            });
    });
    
    // Press Enter key in manual input
    document.getElementById('manualRollInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('manualFetchBtn').click();
        }
    });
    
    // ---------- SCANNER CONTROLS ----------
    document.getElementById('startScanBtn').addEventListener('click', function() {
        if (scannerActive) {
            showStatus('Scanner is already running.', 'info');
            return;
        }
        startScanner();
    });
    
    document.getElementById('stopScanBtn').addEventListener('click', function() {
        stopScanner();
        showStatus('Scanner stopped.', 'info');
    });
    
    // ---------- VIDEO CLICK ----------
    document.getElementById('video-container').addEventListener('click', function() {
        if (scannerActive) {
            showStatus('Scanning... point the camera at the QR code.', 'info');
        } else {
            showStatus('Start the scanner first.', 'info');
        }
    });
    
    // ---------- INIT ----------
    $(function() {
        showStatus('Click "Start Scan" to begin scanning QR codes.', 'info');
    });
</script>

</body>
</html>