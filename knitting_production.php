<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Knitting | Production</title>
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
      background: #0b0f1a;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 16px;
    }

    .card {
      max-width: 650px;
      width: 100%;
      background: #141b2b;
      border-radius: 40px;
      padding: 24px 20px 30px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
      border: 1px solid #2e3a52;
      transition: 0.2s;
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
      max-height: 500px;
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
      min-height: 70px;
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
      min-width: 120px;
    }

    .data-row .value {
      color: #e3ecfc;
      text-align: right;
      flex: 1;
      margin-left: 10px;
    }

    .data-row.default-row {
      border-left-color: #7a8bb0;
      opacity: 0.8;
      background: #131d30;
    }

    .data-row.default-row .label {
      color: #a0b9f0;
    }

    .data-row.default-row .value {
      color: #b7c9f0;
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

    .empty-message {
      color: #5b6f97;
      font-style: italic;
      padding: 10px 0 6px 6px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 6px;
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

    .action-content {
      margin-top: 18px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .action-card {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
      align-items: center;
      background: #111827;
      border: 1px solid #2e3a52;
      border-radius: 18px;
      padding: 14px;
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

    .btn-action.production {
      background: linear-gradient(135deg, #10b981, #0f766e);
    }

    .btn-action.edit {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
    }

    .btn-action.cancel {
      background: linear-gradient(135deg, #ef4444, #b91c1c);
    }

    .btn-action:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .field-input {
      width: 100%;
      background: #0f172a;
      border: 1px solid #33415e;
      color: #e3ecfc;
      border-radius: 12px;
      padding: 10px 12px;
      font-size: 0.95rem;
      outline: none;
    }

    .field-label {
      display: block;
      margin-bottom: 5px;
      font-size: 0.8rem;
      color: #94a3b8;
    }

    .field-row {
      display: grid;
      grid-template-columns: 1fr;
      gap: 10px;
      margin-top: 8px;
    }

    .message-row {
      background: #10263d;
      color: #c7e0ff;
      padding: 12px 14px;
      border-radius: 16px;
      border: 1px solid #2d4a72;
      font-size: 0.94rem;
      line-height: 1.5;
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

    .edit-form-row {
      background: #1a2337;
      padding: 10px 14px;
      border-radius: 12px;
      margin-top: 4px;
    }

    .edit-form-row .field-label {
      font-size: 0.75rem;
      color: #8ea4d6;
      margin-bottom: 2px;
    }

    .edit-form-row .field-input {
      font-size: 0.85rem;
      padding: 6px 10px;
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

      .action-card {
        flex-direction: column;
      }

      .scanner-container {
        min-height: 200px;
      }
    }
  </style>
</head>

<body>
  <div class="card">
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
      <div id="action-content" class="action-content"></div>
    </div>
    <div class="footer-note" style="margin-top:16px; text-align:center; color:#44557a; font-size:0.7rem; letter-spacing:0.5px;">
      <i class="fas fa-camera"></i> Scan QR from Knitting Report · data shown instantly <i class="fas fa-arrow-right"></i>
    </div>
  </div>

  <script>
    (function() {
      "use strict";

      const resultContainer = document.getElementById('result-content');
      const actionContainer = document.getElementById('action-content');
      const cameraStatus = document.getElementById('camera-status');
      const scannerContainer = document.getElementById('scannerContainer');
      const cameraControls = document.getElementById('cameraControls');

      const QR_FIELDS = [
        'SUB_TID', 'MCNO', 'BUYER', 'BOOKING', 'SONO', 'STYLE',
        'FABRICS_TYPE', 'YARN_COUNT', 'YARN_TYPE', 'FINISH_GSM',
        'FINISH_DIA', 'OPEN_TUBE', 'LOT_NO', 'QTY', 'COLOR', 'SL_VDQ'
      ];

      const FIELD_LABELS = {
        'SUB_TID': 'Program',
        'MCNO': 'Machine No',
        'BUYER': 'Buyer',
        'BOOKING': 'Booking',
        'SONO': 'SONO',
        'STYLE': 'Style',
        'FABRICS_TYPE': 'Fabrics Type',
        'YARN_COUNT': 'Yarn Count',
        'YARN_TYPE': 'Yarn Type',
        'FINISH_GSM': 'Finish GSM',
        'FINISH_DIA': 'Finish Dia',
        'OPEN_TUBE': 'Open / Tube',
        'LOT_NO': 'Lot No',
        'QTY': 'Qty',
        'COLOR': 'Color'
      };

      let scannedInfo = null;
      let isEditMode = false;
      let html5QrCode = null;
      let isScanning = false;
      let scanCount = 0;

      const DEFAULT_DATA = [{
          label: '📋 Status',
          value: 'Awaiting QR scan'
        },
        {
          label: '📷 Camera',
          value: 'Ready'
        },
        {
          label: '💡 Hint',
          value: 'Scan QR code from Knitting Report'
        }
      ];

      function renderDefaultData() {
        scannedInfo = null;
        isEditMode = false;
        hideActionContent();

        let html = `<div class="data-row header-row"><span class="label">📋 Default Information</span><span class="value"></span></div>`;
        DEFAULT_DATA.forEach(f => {
          html += `<div class="data-row default-row"><span class="label">${f.label}</span><span class="value">${f.value}</span></div>`;
        });
        resultContainer.innerHTML = html;
      }

      function parseQrText(qrText) {
        const raw = String(qrText || '').trim();
        const normalized = raw.replace(/\s*\|\s*/g, '|');
        let parts = normalized.split('|').map(part => part.trim());
        if (parts.length > 0 && parts[parts.length - 1] === '') {
          parts.pop();
        }

        const originalLength = parts.length;
        if (originalLength === QR_FIELDS.length - 1) {
          parts.splice(3, 0, '');
        } else if (originalLength === QR_FIELDS.length - 2) {
          parts.splice(3, 0, '');
          parts.splice(11, 0, '');
        }

        if (parts.length !== QR_FIELDS.length) {
          return {
            raw,
            parsed: false,
            parts
          };
        }

        const data = {};
        QR_FIELDS.forEach((field, index) => {
          data[field] = parts[index] || '';
        });
        data.raw = raw;
        data.parsed = true;
        return data;
      }

      function renderScannedData(qrText) {
        if (!qrText || qrText.trim() === '') {
          renderDefaultData();
          return;
        }

        scannedInfo = parseQrText(qrText);
        isEditMode = false;
        hideActionContent();

        if (!scannedInfo.parsed) {
          resultContainer.innerHTML = `
            <div class="data-row header-row" style="border-left-color:#4fc3f7;">
              <span class="label">✅ QR Scanned <span class="scanned-badge">raw</span></span>
              <span class="value">${new Date().toLocaleTimeString()}</span>
            </div>
            <div class="data-row" style="border-left-color:#f59e0b; background:#1f2a3a;">
              <span class="label" style="color:#fbbf24;">📌 Type:</span>
              <span class="value">Unstructured scan data</span>
            </div>
            <div class="data-row" style="background:#0f172a; border-left-color:#6b7280; flex-wrap:wrap;">
              <span class="label" style="color:#9ca3af; min-width:100%;">Raw Data:</span>
              <span class="value" style="text-align:left; font-size:0.8rem; word-break:break-all; color:#d1d5db;">${scannedInfo.raw}</span>
            </div>
            <button class="rescan-btn" onclick="window.restartQrScanner()">
              <i class="fas fa-redo"></i> Scan Again
            </button>
          `;
          return;
        }

        let html = `
          <div class="data-row header-row" style="border-left-color:#4fc3f7;">
            <span class="label">✅ QR Scanned <span class="scanned-badge">program</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
  
          <div class="data-row default-row" style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;align-items:flex-start; margin-bottom:4px;">
           <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">PROGRAM</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.SUB_TID || '-'}</div>
            </div> 
          <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">BOOKING NO</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.BOOKING || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">SONO</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.SONO || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">BUYER</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.BUYER || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">STYLE</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.STYLE || '-'}</div>
            </div>
          </div>

       
          <div class="data-row default-row" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;align-items:flex-start; margin-bottom:4px;">
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">YARN TYPE</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.YARN_TYPE || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">YARN COUNT</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.YARN_COUNT || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">FABRICS TYPE</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.FABRICS_TYPE || '-'}</div>
            </div>
          </div>

    
          <div class="data-row default-row" style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;align-items:flex-start; margin-bottom:4px;">
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">FINISH GSM</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.FINISH_GSM || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">FINISH DIA</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.FINISH_DIA || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">OPEN / TUBE</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.OPEN_TUBE || '-'}</div>
            </div>
             <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">COLOR</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.COLOR || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">QTY</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.QTY || '-'}</div>
            </div>
          </div>

          <div class="data-row default-row" style="display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:14px;align-items:flex-start;">
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">LOT NO</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.LOT_NO || '-'}</div>
            </div>
            <div>
              <div style="font-size:0.75rem;opacity:0.75;margin-bottom:4px;">SL/VDQ</div>
              <div style="font-size:0.95rem;font-weight:700;">${scannedInfo.SL_VDQ || '-'}</div>
            </div>
          </div>
          
          <button class="rescan-btn" onclick="window.restartQrScanner()">
            <i class="fas fa-redo"></i> Scan Another QR
          </button>
        `;

        resultContainer.innerHTML = html;
        renderProductionActions();
      }

      function renderProductionActions() {
        if (!scannedInfo || !scannedInfo.parsed) {
          hideActionContent();
          return;
        }

        actionContainer.innerHTML = `
          <div class="action-card">
            <button class="btn-action production" type="button" id="productionBtn">
              <i class="fas fa-save"></i> Production
            </button>
            <button class="btn-action edit" type="button" id="editProductionBtn">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn-action cancel" type="button" id="cancelProductionBtn">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        `;

        document.getElementById('productionBtn').addEventListener('click', handleProductionSave);
        document.getElementById('editProductionBtn').addEventListener('click', toggleEditMode);
        document.getElementById('cancelProductionBtn').addEventListener('click', function() {
          isEditMode = false;
          renderScannedData(scannedInfo.raw);
          hideActionContent();
        });
      }

      function hideActionContent() {
        actionContainer.innerHTML = '';
      }

      function toggleEditMode() {
        if (!scannedInfo || !scannedInfo.parsed) {
          return;
        }
        isEditMode = !isEditMode;
        renderEditForm();
      }

      function renderEditForm() {
        if (!scannedInfo || !scannedInfo.parsed) {
          return;
        }

        let html = `
          <div class="data-row header-row" style="border-left-color:#3b82f6;">
            <span class="label">✏️ Edit Mode <span class="scanned-badge" style="background:#3b82f6;">editing</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
          <div class="message-row" style="margin-bottom:8px;">Update values below, then click <strong>Production</strong> to save.</div>
        `;

        QR_FIELDS.forEach(field => {
          const label = FIELD_LABELS[field] || field;
          const value = scannedInfo[field] || '';
          html += `
            <div class="edit-form-row">
              <label class="field-label">${label}</label>
              <input type="text" class="field-input" id="edit-${field}" value="${value}">
            </div>
          `;
        });

        html += `<button class="rescan-btn" onclick="window.restartQrScanner()">
          <i class="fas fa-redo"></i> Scan Another QR
        </button>`;

        resultContainer.innerHTML = html;

        actionContainer.innerHTML = `
          <div class="action-card">
            <button class="btn-action production" type="button" id="productionBtn">
              <i class="fas fa-save"></i> Production
            </button>
            <button class="btn-action edit" type="button" id="saveEditBtn">
              <i class="fas fa-check"></i> Save Edit
            </button>
            <button class="btn-action cancel" type="button" id="cancelProductionBtn">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        `;

        document.getElementById('productionBtn').addEventListener('click', handleProductionSave);
        document.getElementById('saveEditBtn').addEventListener('click', saveEditedData);
        document.getElementById('cancelProductionBtn').addEventListener('click', function() {
          isEditMode = false;
          renderScannedData(scannedInfo.raw);
          hideActionContent();
        });
      }

      function saveEditedData() {
        if (!scannedInfo || !scannedInfo.parsed) {
          return;
        }
        QR_FIELDS.forEach(field => {
          const input = document.getElementById(`edit-${field}`);
          if (input) {
            scannedInfo[field] = input.value.trim();
          }
        });

        scannedInfo.raw = QR_FIELDS.map(field => scannedInfo[field] || '').join(' | ');
        isEditMode = false;
        renderScannedData(scannedInfo.raw);
        renderProductionActions();
      }

      function handleProductionSave() {
        if (!scannedInfo || !scannedInfo.parsed) {
          alert('No valid program data available to save.');
          return;
        }

        const payload = {
          ...scannedInfo,
          raw: scannedInfo.raw
        };

        const button = document.getElementById('productionBtn');
        if (button) {
          button.disabled = true;
          button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        fetch('ajaxKnittingProduction.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
          })
          .then(response => response.json())
          .then(data => {
            if (button) {
              button.disabled = false;
              button.innerHTML = '<i class="fas fa-save"></i> Production';
            }
            if (data && data.success) {
              renderResultMessage('✅ Production saved successfully. ID: ' + (data.insert_id || 'N/A'), 'success');
            } else {
              renderResultMessage('❌ ' + (data.message || 'Failed to save production record.'), 'error');
            }
          })
          .catch(error => {
            if (button) {
              button.disabled = false;
              button.innerHTML = '<i class="fas fa-save"></i> Production';
            }
            renderResultMessage('❌ AJAX error saving production. Check console.', 'error');
            console.error('Production save error:', error);
          });
      }

      function renderResultMessage(message, type) {
        const msgHtml = `<div class="message-row ${type}">${message}</div>`;
        actionContainer.innerHTML = msgHtml;

        if (type === 'success') {
          setTimeout(() => {
            if (scannedInfo && scannedInfo.parsed) {
              renderProductionActions();
            } else {
              hideActionContent();
            }
          }, 5000);
        }
      }

      function startScanner() {
        if (html5QrCode) {
          html5QrCode.stop().then(() => {
            html5QrCode.clear();
            startNewScanner();
          }).catch(err => {
            console.warn("Stop error, force restart", err);
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

        const cameraConstraints = {
          facingMode: "environment"
        };

        html5QrCode.start(
          cameraConstraints,
          config,
          onScanSuccess,
          onScanError
        ).then(() => {
          isScanning = true;
          cameraStatus.innerText = 'Scanning';
          cameraStatus.style.color = '#8bcbff';
          scannerContainer.style.display = 'block';
          cameraControls.style.display = 'flex';
          if (resultContainer.children.length === 0) {
            renderDefaultData();
          }
        }).catch(err => {
          console.error("Camera start error:", err);
          cameraStatus.innerText = 'Camera error';
          cameraStatus.style.color = '#f7a1a1';
          resultContainer.innerHTML = `
            <div class="data-row default-row" style="border-left-color: #c44;">
              <span class="label"><i class="fas fa-exclamation-triangle"></i> Camera unavailable</span>
              <span class="value" style="font-size:0.8rem;">${err.message || 'Please allow camera access'}</span>
            </div>
            <div class="data-row default-row" style="border-left-color:#7a8bb0;">
              <span class="label">💡 Tip:</span>
              <span class="value">Tap restart or grant permissions</span>
            </div>
            <button class="rescan-btn" onclick="window.restartQrScanner()">
              <i class="fas fa-redo"></i> Retry Camera
            </button>
          `;
        });
      }

      function onScanSuccess(decodedText, decodedResult) {
        console.log('QR Scanned:', decodedText);
        scanCount++;
        if (navigator.vibrate) navigator.vibrate(20);

        if (html5QrCode) {
          html5QrCode.stop().then(() => {
            isScanning = false;
            cameraStatus.innerText = 'Scan complete';
            cameraStatus.style.color = '#7dd3fc';
            scannerContainer.style.display = 'none';
            cameraControls.style.display = 'none';
            renderScannedData(decodedText);
          }).catch(err => {
            console.warn('Failed to stop scanner after scan:', err);
            renderScannedData(decodedText);
          });
        } else {
          renderScannedData(decodedText);
        }
      }

      function onScanError(err) {
        // ignore noisy scan failures - only log if not empty
        if (err && err.length > 0 && !err.includes('NotFound')) {
          // console.debug('Scan error:', err);
        }
      }

      function restartScanner() {
        scannerContainer.style.display = 'block';
        cameraControls.style.display = 'flex';
        if (html5QrCode) {
          html5QrCode.stop().then(() => {
            html5QrCode.clear();
            startNewScanner();
          }).catch(err => {
            startNewScanner();
          });
        } else {
          startNewScanner();
        }
        cameraStatus.innerText = 'Restarting...';
        setTimeout(() => {
          if (isScanning) {
            cameraStatus.innerText = 'Scanning';
          }
        }, 400);
      }

      // Expose restart function globally
      window.restartQrScanner = restartScanner;

      document.addEventListener('DOMContentLoaded', function() {
        renderDefaultData();
        setTimeout(() => {
          startScanner();
        }, 500);
      });

      document.getElementById('toggle-camera-btn').addEventListener('click', function(e) {
        e.preventDefault();
        restartScanner();
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