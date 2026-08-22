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
      background: linear-gradient(135deg, #b9efe6, #a4d9f6, #c9f2dd);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 16px;
    }

    .card {
      max-width: 650px;
      width: 100%;
      background: #eef5f3;
      border-radius: 40px;
      padding: 24px 20px 30px;
      box-shadow: 0 20px 45px rgba(10, 60, 55, 0.3);
      border: 1px solid #b5d8d2;
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
      color: #083a36;
      font-size: 1.4rem;
      font-weight: 800;
      letter-spacing: 0.5px;
    }

    .store-header h2 i {
      color: #0f7a6f;
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
      background: #d9ede9;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px #b5d8d2, 0 8px 20px rgba(10, 60, 55, 0.18);
      margin-bottom: 24px;
      min-height: 300px;
    }

    #qr-reader {
      width: 100%;
      padding: 0 !important;
      background: #e6f4f1;
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
      background: #dcebe8;
      padding: 8px 18px;
      border-radius: 100px;
      color: #083a36;
      font-size: 0.85rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      border: 1px solid #9fc8c1;
    }

    .status-badge i {
      color: #0f7a6f;
      font-size: 0.9rem;
    }

    .btn-icon {
      background: #dcebe8;
      border: 1px solid #9fc8c1;
      color: #083a36;
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
      background: #c3dcd7;
      border-color: #0f7a6f;
      color: #042f2c;
    }

    .btn-icon:active {
      transform: scale(0.92);
    }

    .result-panel {
      background: #e2efec;
      border-radius: 28px;
      padding: 18px 20px 16px;
      margin-top: 20px;
      border: 1px solid #a9cdc6;
      box-shadow: inset 0 2px 6px rgba(10, 60, 55, 0.1);
      overflow-y: auto;
    }

    .result-panel::-webkit-scrollbar {
      width: 6px;
    }

    .result-panel::-webkit-scrollbar-track {
      background: #cfdfdb;
      border-radius: 10px;
    }

    .result-panel::-webkit-scrollbar-thumb {
      background: #7fb0a7;
      border-radius: 10px;
    }

    .result-header {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #083a36;
      font-weight: 700;
      letter-spacing: 0.3px;
      font-size: 0.9rem;
      border-bottom: 1px dashed #9fc8c1;
      padding-bottom: 10px;
      margin-bottom: 12px;
    }

    .result-header i {
      color: #0f7a6f;
    }

    #result-content {
      min-height: auto;
      display: flex;
      flex-direction: column;
      gap: 4px;
      word-break: break-word;
    }

    .data-row {
      background: #d6e9e5;
      padding: 8px 14px;
      border-radius: 12px;
      border-left: 4px solid #0f7a6f;
      color: #062e2b;
      font-size: 0.9rem;
      line-height: 1.4;
      box-shadow: 0 2px 6px rgba(10, 60, 55, 0.12);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .data-row .label {
      color: #0b4f47;
      font-weight: 700;
      min-width: 140px;
    }

    .data-row .value {
      color: #062e2b;
      font-weight: 600;
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
      color: #0b4f47;
      font-weight: 700;
      margin-bottom: 5px;
      text-transform: uppercase;
    }

    .data-row.default-row div div:last-child {
      color: #052522;
      font-size: 16px;
      font-weight: 700;
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
      color: #0b4f47;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      opacity: 1;
    }

    .field-block .field-value {
      font-size: 0.95rem;
      color: #042e2b;
      font-weight: 800;
      white-space: normal;
      overflow-wrap: anywhere;
      word-break: break-word;
      line-height: 1.3;
    }

    .data-row.header-row {
      border-left-color: #f59e0b;
      background: #c8e2dd;
      font-weight: 700;
      font-size: 0.95rem;
    }

    .data-row.header-row .label {
      color: #8a4a00;
      font-weight: 800;
    }

    .data-row.header-row .value {
      color: #7c3d00;
      font-weight: 700;
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
      color: #083a36;
      font-weight: 700;
      letter-spacing: 0.3px;
      font-size: 0.9rem;
      border-bottom: 1px dashed #9fc8c1;
      padding-bottom: 10px;
      margin-bottom: 12px;
    }

    .rack-section-title i {
      color: #0f7a6f;
    }

    .rack-group {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .rack-levels {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .rack-levels .sub-rack-group {
      min-width: 0;
      margin-top: 0;
    }

    .rack-group .btn {
      flex: 1 1 auto;
      min-width: 72px;
      border-radius: 14px;
      font-weight: 700;
      background: #dcebe8;
      border: 1px solid #9fc8c1;
      color: #083a36;
      padding: 12px 16px;
      font-size: 0.95rem;
      cursor: pointer;
      transition: 0.2s;
    }

    .rack-group .btn:hover {
      background: #c3dcd7;
      border-color: #0f7a6f;
      color: #042f2c;
    }

    .rack-number-input {
      width: 100%;
      border-radius: 14px;
      font-weight: 700;
      background: #dcebe8;
      border: 1px solid #9fc8c1;
      color: #083a36;
      padding: 12px 16px;
      font-size: 0.95rem;
      outline: none;
    }

    .rack-number-input:focus {
      border-color: #0f7a6f;
      box-shadow: 0 0 0 3px rgba(15, 122, 111, 0.15);
    }

    .rack-number-options {
      display: none;
      width: 100%;
      max-height: 180px;
      overflow-y: auto;
      margin-top: 4px;
      padding: 4px;
      background: #eef8f5;
      border: 1px solid #9fc8c1;
      border-radius: 12px;
      box-shadow: 0 8px 18px rgba(10, 60, 55, 0.18);
    }

    .rack-number-options.visible {
      display: block;
    }

    .rack-number-option {
      width: 100%;
      border: 0;
      border-radius: 8px;
      background: transparent;
      color: #083a36;
      padding: 8px 12px;
      text-align: left;
      font-weight: 700;
      cursor: pointer;
    }

    .rack-number-option:hover {
      background: #c3dcd7;
    }

    .rack-number-option.keyboard-active {
      background: #0f7a6f;
      color: white;
    }

    .rack-group .btn.active-rack {
      background: linear-gradient(135deg, #0f7a6f, #0b4f47);
      border-color: #0f7a6f;
      color: white;
      box-shadow: 0 8px 18px rgba(10, 60, 55, 0.3);
    }

    .sub-rack-group {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 10px;
      padding: 12px;
      background: #d6e9e5;
      border-radius: 16px;
      border: 1px dashed #7fb0a7;
      min-height: 50px;
    }

    .sub-rack-group .btn {
      min-width: 60px;
      border-radius: 12px;
      font-weight: 700;
      background: #e4f1ee;
      border: 1px solid #9fc8c1;
      color: #0b4f47;
      padding: 10px 14px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: 0.2s;
    }

    .sub-rack-group .btn:hover {
      background: #c3dcd7;
      color: #042f2c;
    }

    .sub-rack-group .btn.active-sub {
      background: linear-gradient(135deg, #10b981, #0b4f47);
      border-color: #10b981;
      color: white;
    }

    .sub-rack-group .sub-placeholder {
      color: #0b4f47;
      font-weight: 600;
      font-size: 0.85rem;
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
      background: linear-gradient(135deg, #0f766e, #134e4a);
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
      background: #dcebe8;
      border: 1px solid #9fc8c1;
      color: #083a36;
      font-weight: 600;
      padding: 8px 20px;
      border-radius: 20px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: 0.2s;
    }

    .rescan-btn:hover {
      background: #c3dcd7;
      border-color: #0f7a6f;
      color: #042f2c;
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
        <span style="margin-left: auto; font-size: 0.7rem; background: #14b8a6; padding: 2px 12px; border-radius: 40px; color: #ffffff;">live</span>
      </div>
      <div id="result-content">
        <!-- Default content will be injected by JS -->
      </div>
      <div id="rack-section" class="rack-section" style="display:none;">
        <div class="rack-section-title">
          <i class="fas fa-warehouse"></i>
          <span>Select Rack Number</span>
        </div>
        <div class="rack-group" id="rackGroup">
          <input class="rack-number-input" type="text" id="rackNumberInput"
            inputmode="numeric" maxlength="2"
            placeholder="Enter rack number (01-50)" autocomplete="off">
          <div class="rack-number-options" id="rackNumberOptions"></div>
        </div>

        <div class="rack-section-title">
          <i class="fas fa-warehouse"></i>
          <span>Select Rack Location</span>
        </div>
        <div class="rack-levels">
          <div class="sub-rack-group" id="subRackGroup">
            <span class="sub-placeholder">Select a rack location</span>
          </div>
          <div class="sub-rack-group" id="subRackOptionsGroup">
            <span class="sub-placeholder">Select A, B or C</span>
          </div>
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
    <div class="footer-note" style="margin-top:16px; text-align:center; color:#115e59; letter-spacing:0.5px;">
      <button class="btn btn-dark"
        onclick="window.location.href='initialPage.php';"
        style="background-color:#083a36;
               color:white;
               padding:12px 18px;
               border:none;
               border-radius:10px;
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
      const rackNumberInput = document.getElementById('rackNumberInput');
      const rackNumberOptions = document.getElementById('rackNumberOptions');
      const subRackGroup = document.getElementById('subRackGroup');
      const subRackOptionsGroup = document.getElementById('subRackOptionsGroup');
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
        CUSTOMER: 'CUSTOMER',
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
        RACKNO: 'RACK NO',
        RACKLOCATION: 'RACK LOCATION',
        TPOINT: 'T.POINT'
      };

      let scannedInfo = null;
      let selectedRack = null;
      let selectedRackSection = null;
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
        html += buildFieldRow(['MCNO', 'MC_DIA', 'CUSTOMER', 'YTYPE']);
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
        selectedRackSection = null;
        selectedSubRack = null;
        rackNumberInput.value = '';
        subRackGroup.innerHTML = '<span class="sub-placeholder">Select a rack location</span>';
        subRackOptionsGroup.innerHTML = '<span class="sub-placeholder">Select A, B or C</span>';
        saveRackBtn.disabled = true;
      }

      function updateRackDisplay() {
        if (!scannedInfo) return;

        const existingRack = scannedInfo.RACK;
        if (existingRack) {
          const match = existingRack.match(/^(\d+)([A-Z])?(\d+)?$/i);
          if (match) {
            rackNumberInput.value = match[1];
            setActiveRack(match[1]);
            if (match[2]) {
              setActiveRackSection(match[2].toUpperCase());
              if (match[3]) setActiveSubRack(match[2].toUpperCase() + match[3]);
            }
          }
        }
      }

      function setActiveRack(rack) {
        selectedRack = rack;
        selectedRackSection = null;
        selectedSubRack = null;
        updateSubRacks(rack);
      }

      function setActiveRackSection(section) {
        selectedRackSection = section;
        selectedSubRack = null;
        subRackGroup.querySelectorAll('.btn').forEach(function(btn) {
          btn.classList.toggle('active-sub', btn.textContent.trim().toUpperCase() === section);
        });
        updateSubRackOptions(section);
      }

      function setActiveSubRack(sub) {
        selectedSubRack = sub;
        subRackOptionsGroup.querySelectorAll('.btn').forEach(function(btn) {
          btn.classList.toggle('active-sub', btn.textContent.trim().toUpperCase() === sub);
        });
        saveRackBtn.disabled = false;
      }

      function showSelectedRackMessage() {
        if (selectedRack && selectedSubRack) {
          showMessage('Selected: ' + selectedRack + '-' + selectedSubRack, '');
        }
      }

      function updateSubRacks(rack) {
        const options = ['A', 'B', 'C'];
        subRackGroup.innerHTML = '';

        options.forEach(function(opt) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn';
          btn.textContent = opt;
          btn.onclick = function() {
            setActiveRackSection(opt);
            showMessage('Selected: ' + selectedRack + '-' + opt, '');
          };
          subRackGroup.appendChild(btn);
        });

        subRackOptionsGroup.innerHTML = '<span class="sub-placeholder">Select A, B or C</span>';
        saveRackBtn.disabled = true;
      }

      function updateSubRackOptions(section) {
        subRackOptionsGroup.innerHTML = '';
        ['1', '2', '3'].forEach(function(number) {
          const option = document.createElement('button');
          option.type = 'button';
          option.className = 'btn';
          option.textContent = section + number;
          option.onclick = function() {
            setActiveSubRack(option.textContent);
            showSelectedRackMessage();
          };
          subRackOptionsGroup.appendChild(option);
        });
      }

      function updateRackNumberOptions(value) {
        const prefix = String(value || '').replace(/\D/g, '').slice(0, 1);
        rackNumberOptions.innerHTML = '';
        if (!prefix) {
          rackNumberOptions.classList.remove('visible');
          return;
        }

        const numbers = [];
        for (let number = 1; number <= 50; number += 1) {
          const rackNumber = String(number).padStart(2, '0');
          const matchesPreviousRange = prefix === '0' ?
            number >= 1 && number <= 9 :
            prefix >= '1' && prefix <= '4' ?
            Math.floor(number / 10) === Number(prefix) :
            rackNumber.endsWith(prefix);

          if (matchesPreviousRange) numbers.push(rackNumber);
        }

        numbers.forEach(function(rackNumber) {
          const option = document.createElement('button');
          option.type = 'button';
          option.className = 'rack-number-option';
          option.textContent = rackNumber;
          option.addEventListener('click', function() {
            rackNumberInput.value = option.textContent;
            rackNumberInput.dispatchEvent(new Event('input', {
              bubbles: true
            }));
            rackNumberOptions.classList.remove('visible');
            rackNumberInput.focus();
          });
          rackNumberOptions.appendChild(option);
        });
        rackNumberOptions.classList.add('visible');
      }

      rackNumberInput.addEventListener('input', function() {
        let value = rackNumberInput.value.replace(/\D/g, '').slice(0, 2);
        if (Number(value) > 50) value = '50';
        rackNumberInput.value = value;
        updateRackNumberOptions(value);
        selectedSubRack = null;
        saveRackBtn.disabled = true;

        const rack = Number(value);
        if (value.length === 2 && rack >= 1 && rack <= 50) {
          setActiveRack(String(rack).padStart(2, '0'));
          showMessage('Selected Rack: ' + selectedRack, '');
        } else {
          selectedRack = null;
          selectedRackSection = null;
          selectedSubRack = null;
          subRackGroup.innerHTML = '<span class="sub-placeholder">Select a rack number</span>';
          subRackOptionsGroup.innerHTML = '<span class="sub-placeholder">Select A, B or C</span>';
        }
      });

      rackNumberInput.addEventListener('keydown', function(event) {
        const options = Array.from(rackNumberOptions.querySelectorAll('.rack-number-option'));
        if (!options.length) return;

        const currentIndex = options.findIndex(function(option) {
          return option.classList.contains('keyboard-active');
        });
        let nextIndex = currentIndex;

        if (event.key === 'ArrowDown') {
          event.preventDefault();
          nextIndex = currentIndex < options.length - 1 ? currentIndex + 1 : 0;
        } else if (event.key === 'ArrowUp') {
          event.preventDefault();
          nextIndex = currentIndex > 0 ? currentIndex - 1 : options.length - 1;
        } else if (event.key === 'Enter' && currentIndex >= 0) {
          event.preventDefault();
          options[currentIndex].click();
          return;
        } else if (event.key === 'Escape') {
          rackNumberOptions.classList.remove('visible');
          return;
        } else {
          return;
        }

        options.forEach(function(option, index) {
          option.classList.toggle('keyboard-active', index === nextIndex);
        });
        options[nextIndex].scrollIntoView({ block: 'nearest' });
      });

      rackNumberInput.addEventListener('focus', function() {
        updateRackNumberOptions(rackNumberInput.value);
      });

      rackNumberInput.addEventListener('blur', function() {
        setTimeout(function() {
          rackNumberOptions.classList.remove('visible');
        }, 150);
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

      function saveRackData(roll, rackno, racklocation) {
        var payload = Object.assign({}, scannedInfo || {});
        payload.ROLL = roll;
        payload.RACKNO = rackno;
        payload.RACKLOCATION = racklocation;
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
                reject(resp || {
                  error: 'Save failed'
                });
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
          <div class="data-row header-row" style="border-left-color:#f59e0b; background:#c8e2dd;">
            <span class="label" style="color:#8a4a00; font-weight:800;">📌 ${msg || 'No data'}</span>
            <span class="value"></span>
          </div>
          <div class="data-row" style="background:#d6e9e5; border-left-color:#6b7280; flex-wrap:wrap;">
            <span class="label" style="color:#0b4f47; font-weight:700; min-width:100%;">Scanned Data:</span>
            <span class="value" style="text-align:left; font-size:0.8rem; word-break:break-all; color:#052522; font-weight:600;">${text}</span>
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

        const rackno = selectedRack ? String(selectedRack).trim() : '';
        const racklocation = selectedSubRack ? String(selectedSubRack).trim().toUpperCase() : '';

        if (!rackno || Number(rackno) < 1 || Number(rackno) > 50) {
          showMessage('Please select a Rack Number (01-50) first.', 'error');
          return;
        }
        if (!racklocation) {
          showMessage('Please select a Rack Location (A1, A2, B3...) first.', 'error');
          return;
        }
        if (!/^[A-C][1-9]$/.test(racklocation)) {
          showMessage('Invalid Rack Location. Use format like A1, A2, A3, B1...', 'error');
          return;
        }

        const roll = scannedInfo.ROLL || '';

        saveRackBtn.disabled = true;
        saveRackBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        saveRackData(roll, rackno.padStart(2, '0'), racklocation)
          .then(function(resp) {
            scannedInfo.RACKNO = rackno.padStart(2, '0');
            scannedInfo.RACKLOCATION = racklocation;
            showMessage('✅ ' + (resp.message || ('Saved - Rack No: ' + rackno + ', Location: ' + racklocation)) + '<br><small>Page will reload in 2 seconds...</small>', 'success');
            saveRackBtn.innerHTML = '<i class="fas fa-save"></i> Save Rack';
            setTimeout(function() {
              window.location.reload();
            }, 2000);
          })
          .catch(function(err) {
            var msg = (err && (err.error || err.message)) || String(err);
            if (msg === '[object Object]' || msg === 'undefined') {
              msg = 'Save failed. Please try again.';
            }
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