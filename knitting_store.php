<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Knitting | Store</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Roboto, system-ui, -apple-system, sans-serif;
      background: #c7c8ca;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 16px;
    }

    .card {
      max-width: 650px;
      width: 100%;
      background: linear-gradient(145deg, #123a2f, #0b2420);
      border-radius: 40px;
      padding: 24px 20px 30px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
      border: 1px solid #2e3a52;
      transition: 0.2s;
    }

    .store-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
      padding: 0 4px;
    }

    .store-header h2 {
      color: #e3ecfc;
      font-size: 1.35rem;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .store-header h2 i {
      color: #4fc3f7;
      margin-right: 8px;
    }

    .store-header .badge-store {
      margin-left: auto;
      background: #1d8b5e;
      color: white;
      font-size: 0.7rem;
      padding: 3px 14px;
      border-radius: 100px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }

    .scanner-container {
      position: relative;
      background: #1e2740;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px #33405e, 0 8px 20px rgba(0, 0, 0, 0.5);
      margin-bottom: 24px;
      min-height: 300px;
    }

    #qr-reader {
      width: 100%;
      padding: 0 !important;
      background: #0f1625;
    }

    #qr-reader video {
      border-radius: 28px;
      width: 100%;
      height: auto;
      display: block;
    }

    .scan-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      border-radius: 28px;
      box-shadow: inset 0 0 0 2px rgba(0, 255, 200, 0.3);
    }

    .scan-overlay::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 70%;
      height: 70%;
      transform: translate(-50%, -50%);
      border: 2px solid rgba(0, 255, 200, 0.5);
      border-radius: 20px;
      box-shadow: 0 0 30px rgba(0, 255, 200, 0.1);
      animation: pulse-border 2.2s infinite ease-in-out;
    }

    @keyframes pulse-border {
      0% {
        opacity: 0.4;
        transform: translate(-50%, -50%) scale(0.96);
      }

      50% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.02);
      }

      100% {
        opacity: 0.4;
        transform: translate(-50%, -50%) scale(0.96);
      }
    }

    .camera-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 12px;
      padding: 0 6px;
    }

    .status-badge {
      background: #1f2a40;
      padding: 8px 18px;
      border-radius: 100px;
      color: #a0b3d9;
      font-size: 0.85rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      border: 1px solid #2e3d5a;
    }

    .status-badge i {
      color: #4fc3f7;
      font-size: 0.9rem;
    }

    .btn-icon {
      background: #1f2a40;
      border: 1px solid #33415e;
      color: #cbd5f0;
      width: 44px;
      height: 44px;
      border-radius: 40px;
      font-size: 1.2rem;
      cursor: pointer;
      transition: 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-icon:hover {
      background: #2b3857;
      border-color: #5f79b0;
      color: white;
    }

    .btn-icon:active {
      transform: scale(0.92);
    }

    .result-panel {
      background: #101826;
      border-radius: 28px;
      padding: 18px 20px 16px;
      margin-top: 20px;
      border: 1px solid #29364f;
      box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.4);
      overflow-y: auto;
    }

    .result-panel::-webkit-scrollbar {
      width: 6px;
    }

    .result-panel::-webkit-scrollbar-track {
      background: #0f1625;
      border-radius: 10px;
    }

    .result-panel::-webkit-scrollbar-thumb {
      background: #2e3d5a;
      border-radius: 10px;
    }

    .result-header {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #8ea4d6;
      font-weight: 500;
      letter-spacing: 0.3px;
      font-size: 0.9rem;
      border-bottom: 1px dashed #27344d;
      padding-bottom: 10px;
      margin-bottom: 12px;
    }

    .result-header i {
      color: #4fc3f7;
    }

    #result-content {
      min-height: auto;
      display: flex;
      flex-direction: column;
      gap: 4px;
      word-break: break-word;
    }

    .data-row {
      background: #1a2337;
      padding: 8px 14px;
      border-radius: 12px;
      border-left: 4px solid #4fc3f7;
      color: #e3ecfc;
      font-size: 0.9rem;
      line-height: 1.4;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .data-row .label {
      color: #8bb1ff;
      font-weight: 600;
      min-width: 140px;
    }

    .data-row .value {
      color: #e3ecfc;
      text-align: right;
      flex: 1;
      margin-left: 10px;
    }

    .data-row.default-row {
      display: grid !important;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      align-items: flex-start;
      height: auto;
      min-height: auto;
    }

    .data-row.default-row>div {
      min-width: 0;
    }

    .data-row.default-row div div:first-child {
      font-size: 11px;
      color: #8fa5cf;
      margin-bottom: 5px;
      text-transform: uppercase;
    }

    .data-row.default-row div div:last-child {
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      white-space: normal;
      overflow-wrap: anywhere;
      word-break: break-word;
      line-height: 1.45;
    }

    .field-block {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-height: auto;
    }

    .field-block .field-label {
      font-size: 0.7rem;
      color: #8fa5cf;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      opacity: 0.85;
    }

    .field-block .field-value {
      font-size: 0.95rem;
      color: #ffffff;
      font-weight: 700;
      white-space: normal;
      overflow-wrap: anywhere;
      word-break: break-word;
      line-height: 1.3;
    }

    .data-row.header-row {
      border-left-color: #f59e0b;
      background: #1f2a3a;
      font-weight: 600;
      font-size: 0.95rem;
    }

    .data-row.header-row .label {
      color: #fbbf24;
    }

    .data-row.header-row .value {
      color: #fde68a;
    }

    .scanned-badge {
      background: #1d8b5e;
      color: white;
      font-size: 0.7rem;
      padding: 2px 14px;
      border-radius: 100px;
      display: inline-block;
      margin-left: 8px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }

    .rack-section {
      margin-top: 18px;
    }

    .rack-section-title {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #8ea4d6;
      font-weight: 500;
      letter-spacing: 0.3px;
      font-size: 0.9rem;
      border-bottom: 1px dashed #27344d;
      padding-bottom: 10px;
      margin-bottom: 12px;
    }

    .rack-section-title i {
      color: #4fc3f7;
    }

    .rack-group {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .rack-group .btn {
      flex: 1 1 auto;
      min-width: 72px;
      border-radius: 14px;
      font-weight: 600;
      background: #1f2a40;
      border: 1px solid #33415e;
      color: #cbd5f0;
      padding: 12px 16px;
      font-size: 0.95rem;
      cursor: pointer;
      transition: 0.2s;
    }

    .rack-group .btn:hover {
      background: #2b3857;
      border-color: #5f79b0;
      color: white;
    }

    .rack-group .btn.active-rack {
      background: linear-gradient(135deg, #4fc3f7, #2563eb);
      border-color: #4fc3f7;
      color: white;
      box-shadow: 0 8px 18px rgba(79, 195, 247, 0.25);
    }

    .sub-rack-group {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 10px;
      padding: 12px;
      background: #111827;
      border-radius: 16px;
      border: 1px dashed #33415e;
      min-height: 50px;
    }

    .sub-rack-group .btn {
      min-width: 60px;
      border-radius: 12px;
      font-weight: 600;
      background: #1a2337;
      border: 1px solid #33415e;
      color: #a0b3d9;
      padding: 10px 14px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: 0.2s;
    }

    .sub-rack-group .btn:hover {
      background: #2b3857;
      color: white;
    }

    .sub-rack-group .btn.active-sub {
      background: linear-gradient(135deg, #10b981, #0f766e);
      border-color: #10b981;
      color: white;
    }

    .sub-rack-group .sub-placeholder {
      color: #5b6f97;
      font-size: 0.8rem;
      align-self: center;
    }

    .rack-actions {
      margin-top: 16px;
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    .btn-action {
      flex: 1 1 120px;
      min-width: 120px;
      border: none;
      border-radius: 14px;
      padding: 12px 16px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
      color: #fff;
      background: #1f2a40;
    }

    .btn-action:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.18);
    }

    .btn-action.save {
      background: linear-gradient(135deg, #10b981, #0f766e);
    }

    .btn-action.reset {
      background: linear-gradient(135deg, #64748b, #475569);
    }

    .btn-action:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .message-row {
      background: #10263d;
      color: #c7e0ff;
      padding: 12px 14px;
      border-radius: 16px;
      border: 1px solid #2d4a72;
      font-size: 0.94rem;
      line-height: 1.5;
      margin-top: 14px;
    }

    .message-row.success {
      background: #142f1f;
      border-color: #166534;
      color: #c7f6d1;
    }

    .message-row.error {
      background: #3f1d1d;
      border-color: #b91c1c;
      color: #fee2e2;
    }

    .rescan-btn {
      margin-top: 10px;
      background: #1f2a40;
      border: 1px solid #33415e;
      color: #cbd5f0;
      padding: 8px 20px;
      border-radius: 20px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: 0.2s;
    }

    .rescan-btn:hover {
      background: #2b3857;
      border-color: #5f79b0;
      color: white;
    }

    @media (max-width: 480px) {
      .card {
        padding: 16px;
      }

      .data-row {
        font-size: 0.8rem;
        padding: 6px 10px;
        flex-wrap: wrap;
      }

      .data-row .label {
        min-width: 80px;
        font-size: 0.75rem;
      }

      .data-row .value {
        font-size: 0.8rem;
      }

      .scanner-container {
        min-height: 200px;
      }

      .rack-actions {
        flex-direction: column;
      }
    }

    @media(max-width:768px) {
      .data-row.default-row {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)) !important;
      }
    }

    @media(max-width:480px) {
      .data-row.default-row {
        grid-template-columns: repeat(2, 1fr) !important;
      }
    }
  </style>
</head>

<body>
  <div class="card">
    <div class="store-header">
      <h2><i class="fa-solid fa-warehouse"></i>Knitting Store</h2>
      <span class="badge-store">STORE</span>
    </div>

    <div class="scanner-container" id="scannerContainer">
      <div id="qr-reader"></div>
      <div class="scan-overlay"></div>
    </div>

    <div class="camera-controls" id="cameraControls">
      <div class="status-badge">
        <i class="fas fa-video"></i>
        <span id="camera-status">Ready</span>
      </div>
      <button class="btn-icon" id="toggle-camera-btn" title="Restart / switch camera">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <div class="result-panel">
      <div class="result-header">
        <i class="fas fa-qrcode"></i>
        <span>Scanned Data</span>
        <span style="margin-left: auto; font-size: 0.7rem; background: #1f2a40; padding: 2px 12px; border-radius: 40px; color: #91a9da;">live</span>
      </div>
      <div id="result-content">
        <!-- Default content will be injected by JS -->
      </div>
      <div id="rack-section" class="rack-section" style="display:none;">
        <div class="rack-section-title">
          <i class="fas fa-warehouse"></i>
          <span>Select Rack Location</span>
        </div>
        <div class="rack-group" id="rackGroup">
          <button type="button" class="btn" data-rack="A">A</button>
          <button type="button" class="btn" data-rack="B">B</button>
          <button type="button" class="btn" data-rack="C">C</button>
        </div>
        <div class="sub-rack-group" id="subRackGroup">
          <span class="sub-placeholder">Select a rack above to see sub-racks</span>
        </div>
        <div class="rack-actions">
          <button class="btn-action save" type="button" id="saveRackBtn" disabled>
            <i class="fas fa-save"></i> Save Rack
          </button>
          <button class="btn-action reset" type="button" id="resetRackBtn">
            <i class="fas fa-undo"></i> Reset
          </button>
        </div>
      </div>
      <div id="message"></div>
    </div>

    <!-- FOOTER -->
    <div class="footer-note" style="margin-top:16px; text-align:center; color:#44557a; letter-spacing:0.5px;">
      <button class="btn btn-dark"
        onclick="window.location.href='initialPage.php';"
        style="background-color:white;
               color:black;
               padding:12px;
               border-radius:8px;
               cursor:pointer;
               transition:all .2s ease;
               font-weight:bold;
               font-size:1rem;">
        <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>
        Back to Initial Page
      </button>
    </div>
  </div>

  <script>
    (function() {
      "use strict";

      const resultContainer = document.getElementById('result-content');
      const messageContainer = document.getElementById('message');
      const cameraStatus = document.getElementById('camera-status');
      const scannerContainer = document.getElementById('scannerContainer');
      const cameraControls = document.getElementById('cameraControls');
      const rackSection = document.getElementById('rack-section');
      const rackGroup = document.getElementById('rackGroup');
      const subRackGroup = document.getElementById('subRackGroup');
      const resetRackBtn = document.getElementById('resetRackBtn');
      const saveRackBtn = document.getElementById('saveRackBtn');

      const FIELD_LABELS = {
        BUDAT: 'DATE',
        ROLL: 'ROLL',
        PO_NUMBER: 'PO NUMBER',
        QTY: 'QTY',
        SONO: 'SONO',
        BUYER: 'BUYER',
        STYLE: 'STYLE',
        COLOR: 'COLOR',
        MCNO: 'MACHINE NO',
        MC_DIA: 'MC DIA',
        SUPPLIER: 'SUPPLIER',
        SHIFT: 'SHIFT',
        YTYPE: 'YARN TYPE',
        YCOUNT: 'YARN COUNT',
        FTYPE: 'FABRICS TYPE',
        FGSM: 'FINISH GSM',
        FDIA: 'FINISH DIA',
        O_T: 'OPEN/TUBE',
        SL: 'SL/VDQ',
        GGSM: 'GRAY GSM',
        FPLAN: 'FEEDER PLAN',
        LOTNO: 'LOT NO',
        MATERIAL_CODE: 'MATERIAL CODE',
        M_DES: 'MATERIAL DESC',
        RACK: 'RACK',
        TPOINT: 'T.POINT'
      };

      const subRackOptions = {
        'A': ['A1', 'A2', 'A3'],
        'B': ['B1', 'B2', 'B3'],
        'C': ['C1', 'C2', 'C3']
      };

      let scannedInfo = null;
      let selectedRack = null;
      let selectedSubRack = null;
      let html5QrCode = null;
      let isScanning = false;

      function renderDefaultData() {
        scannedInfo = null;
        hideRackSection();
        resultContainer.innerHTML = `
          <div class="data-row header-row"><span class="label">Default Information</span><span class="value"></span></div>
          <div class="data-row default-row"><span class="label">Status</span><span class="value">Awaiting QR scan</span></div>
        `;
      }

      function showMessage(msg, type) {
        messageContainer.innerHTML = `<div class="message-row ${type || ''}">${msg}</div>`;
      }

      function clearMessage() {
        messageContainer.innerHTML = '';
      }

      function extractRollFromQR(qrText) {
        const text = String(qrText || '').trim();
        if (!text) return null;

        if (text.indexOf('|') !== -1) {
          const first = text.split('|')[0].trim();
          if (first.length > 0) return first;
        }

        let m = text.match(/ROLL:\s*([^\n\r]+)/i);
        if (m && m[1]) return m[1].trim();

        if (/^\d+$/.test(text) && text.length >= 2) return text;

        return null;
      }

      function buildFieldRow(fields) {
        return `
          <div class="data-row default-row">
            ${fields.map(function(field) {
              return `
                <div class="field-block">
                  <span class="field-label">${FIELD_LABELS[field] || field}</span>
                  <span class="field-value">${scannedInfo[field] !== undefined && scannedInfo[field] !== null && scannedInfo[field] !== '' ? scannedInfo[field] : '-'}</span>
                </div>
              `;
            }).join('')}
          </div>
        `;
      }

      function renderScannedData(row) {
        scannedInfo = row;
        clearMessage();
        showRackSection();

        let html = `
          <div class="data-row header-row" style="border-left-color:#4fc3f7;">
            <span class="label">✅ QR Scanned <span class="scanned-badge">ROLL NO</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
        `;

        html += buildFieldRow(['ROLL', 'PO_NUMBER', 'QTY', 'SONO']);
        html += buildFieldRow(['SHIFT', 'BUYER', 'STYLE', 'COLOR']);
        html += buildFieldRow(['MCNO', 'MC_DIA', 'SUPPLIER', 'YTYPE']);
        html += buildFieldRow(['YCOUNT', 'O_T', 'SL', 'FTYPE']);
        html += buildFieldRow(['FGSM', 'FDIA', 'GGSM', 'FPLAN']);
        html += buildFieldRow(['LOTNO', 'TPOINT', 'MATERIAL_CODE', 'M_DES']);

        html += `
          <button class="rescan-btn" onclick="window.location.reload();">
            <i class="fas fa-redo"></i> Scan Another QR
          </button>
        `;

        resultContainer.innerHTML = html;
        updateRackDisplay();
      }

      function showRackSection() {
        rackSection.style.display = 'block';
      }

      function hideRackSection() {
        rackSection.style.display = 'none';
        resetRackSelection();
      }

      function resetRackSelection() {
        selectedRack = null;
        selectedSubRack = null;
        rackGroup.querySelectorAll('.btn').forEach(function(b) {
          b.classList.remove('active-rack');
        });
        subRackGroup.innerHTML = '<span class="sub-placeholder">Select a rack above to see sub-racks</span>';
        saveRackBtn.disabled = true;
      }

      function updateRackDisplay() {
        if (!scannedInfo) return;

        const existingRack = scannedInfo.RACK;
        if (existingRack) {
          const match = existingRack.match(/^([A-Z])(.*)$/i);
          if (match) {
            setActiveRack(match[1].toUpperCase());
            if (match[2]) {
              setActiveSubRack(existingRack.toUpperCase());
            }
          }
        }
      }

      function setActiveRack(rack) {
        rackGroup.querySelectorAll('.btn').forEach(function(b) {
          b.classList.remove('active-rack');
          if (b.dataset.rack === rack) b.classList.add('active-rack');
        });
        selectedRack = rack;
        updateSubRacks(rack);
      }

      function setActiveSubRack(sub) {
        selectedSubRack = sub;
        subRackGroup.querySelectorAll('.btn').forEach(function(b) {
          b.classList.remove('active-sub');
          if (b.textContent.trim().toUpperCase() === sub) b.classList.add('active-sub');
        });
        saveRackBtn.disabled = false;
      }

      function updateSubRacks(rack) {
        const options = subRackOptions[rack] || [];
        subRackGroup.innerHTML = '';

        if (options.length === 0) {
          subRackGroup.innerHTML = '<span class="sub-placeholder">No sub-racks available</span>';
          selectedSubRack = null;
          saveRackBtn.disabled = true;
          return;
        }

        options.forEach(function(opt) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn';
          btn.textContent = opt;
          btn.onclick = function() {
            setActiveSubRack(opt);
            showMessage('Selected: ' + selectedRack + ' - ' + opt, '');
          };
          subRackGroup.appendChild(btn);
        });

        selectedSubRack = null;
        saveRackBtn.disabled = true;
      }

      rackGroup.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn');
        if (!btn) return;
        setActiveRack(btn.dataset.rack);
        showMessage('Selected Rack: ' + selectedRack, '');
      });

      function fetchDataByRoll(roll) {
        return new Promise(function(resolve, reject) {
          fetch('ajaxKnitting_store.php?action=get_by_roll&roll=' + encodeURIComponent(roll))
            .then(function(r) {
              return r.json();
            })
            .then(function(resp) {
              if (resp && resp.success) {
                resolve(resp.data);
              } else {
                reject((resp && resp.error) || 'No data found');
              }
            })
            .catch(function(err) {
              reject(err);
            });
        });
      }

      function saveRackData(roll, rack) {
        var payload = Object.assign({}, scannedInfo || {});
        payload.ROLL = roll;
        payload.RACK = rack;
        return new Promise(function(resolve, reject) {
          fetch('ajaxKnittingStore_Insert.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(payload)
            })
            .then(function(r) {
              return r.json();
            })
            .then(function(resp) {
              if (resp && resp.success) {
                resolve(resp);
              } else {
                reject(resp || { error: 'Save failed' });
              }
            })
            .catch(function(err) {
              reject(err);
            });
        });
      }

      function processScannedData(decodedText) {
        const roll = extractRollFromQR(decodedText);

        if (!roll) {
          renderBareData(decodedText, 'Unrecognized QR data');
          showMessage('Invalid QR code. Please scan a valid knitting QR.', 'error');
          return;
        }

        cameraStatus.innerText = 'Searching...';
        cameraStatus.style.color = '#fbbf24';

        fetchDataByRoll(roll)
          .then(function(row) {
            renderScannedData(row);
            cameraStatus.innerText = 'Found';
            cameraStatus.style.color = '#7dd3fc';
            showMessage('QR Code scanned successfully! Select rack location.', 'success');
          })
          .catch(function(err) {
            cameraStatus.innerText = 'Not found';
            cameraStatus.style.color = '#f7a1a1';
            renderBareData(decodedText, 'No data found for ROLL: ' + roll);
            showMessage((err && err.message) || String(err), 'error');
          });
      }

      function renderBareData(text, msg) {
        resultContainer.innerHTML = `
          <div class="data-row header-row" style="border-left-color:#f59e0b; background:#1f2a3a;">
            <span class="label" style="color:#fbbf24;">📌 ${msg || 'No data'}</span>
            <span class="value"></span>
          </div>
          <div class="data-row" style="background:#0f172a; border-left-color:#6b7280; flex-wrap:wrap;">
            <span class="label" style="color:#9ca3af; min-width:100%;">Scanned Data:</span>
            <span class="value" style="text-align:left; font-size:0.8rem; word-break:break-all; color:#d1d5db;">${text}</span>
          </div>
          <button class="rescan-btn" onclick="window.location.reload();">
            <i class="fas fa-redo"></i> Scan Another QR
          </button>
        `;
        hideRackSection();
      }

      resetRackBtn.addEventListener('click', function() {
        resetRackSelection();
        showMessage('Rack selection reset.', '');
      });

      saveRackBtn.addEventListener('click', function() {
        if (!scannedInfo) {
          showMessage('No data scanned. Please scan a QR code first.', 'error');
          return;
        }
        if (!selectedRack || !selectedSubRack) {
          showMessage('Please select a rack and sub-rack location.', 'error');
          return;
        }

        const roll = scannedInfo.ROLL || '';
        const rack = selectedSubRack.toUpperCase();

        saveRackBtn.disabled = true;
        saveRackBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        saveRackData(roll, rack)
          .then(function(resp) {
            scannedInfo.RACK = rack;
            showMessage('✅ ' + (resp.message || ('Saved to Rack: ' + rack)) + '<br><small>Page will reload in 2 seconds...</small>', 'success');
            saveRackBtn.innerHTML = '<i class="fas fa-save"></i> Save Rack';
            setTimeout(function() {
              window.location.reload();
            }, 2000);
          })
          .catch(function(err) {
            var msg = (err && err.message) || String(err);
            if (!(err && err.messager_exist)) {
              msg = '❌ ' + msg;
            }
            showMessage(msg, 'error');
            saveRackBtn.disabled = false;
            saveRackBtn.innerHTML = '<i class="fas fa-save"></i> Save Rack';
          });
      });

      function startScanner() {
        if (html5QrCode) {
          html5QrCode.stop().then(function() {
            html5QrCode.clear();
            startNewScanner();
          }).catch(function() {
            startNewScanner();
          });
        } else {
          startNewScanner();
        }
      }

      function startNewScanner() {
        if (html5QrCode) {
          try {
            html5QrCode.stop();
            html5QrCode.clear();
          } catch (e) {}
          html5QrCode = null;
        }

        const qrReaderElement = document.getElementById('qr-reader');
        qrReaderElement.innerHTML = '';

        html5QrCode = new Html5Qrcode("qr-reader");

        const config = {
          fps: 30,
          qrbox: {
            width: 240,
            height: 240
          },
          aspectRatio: 1.0
        };

        html5QrCode.start({
            facingMode: "environment"
          },
          config,
          onScanSuccess,
          function(err) {}
        ).then(function() {
          isScanning = true;
          cameraStatus.innerText = 'Scanning';
          cameraStatus.style.color = '#8bcbff';
          scannerContainer.style.display = 'block';
          cameraControls.style.display = 'flex';
        }).catch(function(err) {
          console.error("Camera start error:", err);
          cameraStatus.innerText = 'Camera error';
          cameraStatus.style.color = '#f7a1a1';
          resultContainer.innerHTML = `
            <div class="data-row default-row" style="border-left-color:#c44;">
              <span class="label"><i class="fas fa-exclamation-triangle"></i> Camera unavailable</span>
              <span class="value" style="font-size:0.8rem;">${err.message || 'Please allow camera access'}</span>
            </div>
            <button class="rescan-btn" onclick="window.location.reload();">
              <i class="fas fa-redo"></i> Retry Camera
            </button>
          `;
        });
      }

      function onScanSuccess(decodedText, decodedResult) {
        console.log('QR Scanned:', decodedText);
        if (navigator.vibrate) navigator.vibrate(20);

        if (html5QrCode) {
          html5QrCode.stop().then(function() {
            isScanning = false;
            cameraStatus.innerText = 'Scan complete';
            cameraStatus.style.color = '#7dd3fc';
            scannerContainer.style.display = 'none';
            cameraControls.style.display = 'none';
            processScannedData(decodedText);
          }).catch(function() {
            processScannedData(decodedText);
          });
        } else {
          processScannedData(decodedText);
        }
      }

      function restartScanner() {
        scannerContainer.style.display = 'block';
        cameraControls.style.display = 'flex';
        if (html5QrCode) {
          html5QrCode.stop().then(function() {
            html5QrCode.clear();
            startNewScanner();
          }).catch(function() {
            startNewScanner();
          });
        } else {
          startNewScanner();
        }
        cameraStatus.innerText = 'Restarting...';
        setTimeout(function() {
          if (isScanning) {
            cameraStatus.innerText = 'Scanning';
          }
        }, 400);
      }

      document.getElementById('toggle-camera-btn').addEventListener('click', function(e) {
        e.preventDefault();
        restartScanner();
      });

      document.addEventListener('DOMContentLoaded', function() {
        renderDefaultData();
        setTimeout(function() {
          startScanner();
        }, 500);
      });

      window.addEventListener('beforeunload', function() {
        if (html5QrCode) {
          try {
            html5QrCode.stop();
            html5QrCode.clear();
          } catch (e) {}
        }
      });

    })();
  </script>
</body>

</html>