<?php
// ------- In-file AJAX endpoint: fetch knit_card_test row by ROLL -------
if (isset($_GET['action']) && $_GET['action'] === 'get_roll') {
  require_once 'config.php';
  header('Content-Type: application/json');
  header('X-Content-Type-Options: nosniff');

  $roll = isset($_GET['roll']) ? trim($_GET['roll']) : '';
  if ($roll === '') {
    echo json_encode(['success' => false, 'error' => 'ROLL is required']);
    exit();
  }

  $s = mysqli_real_escape_string($db, $roll);
  $q = "SELECT * FROM knit_card_test WHERE ROLL = '$s' LIMIT 1";
  $res = mysqli_query($db, $q);

  if ($res && mysqli_num_rows($res) > 0) {
    echo json_encode(['success' => true, 'data' => mysqli_fetch_assoc($res)]);
  } else {
    echo json_encode(['success' => false, 'error' => 'No data found for ROLL: ' . $roll]);
  }
  exit();
}
?>
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
      background: linear-gradient(135deg, #e2e8f0, #f8fafc, #dbeafe);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 16px;
    }

    .card {
      max-width: 650px;
      width: 100%;
      background: #ffffff;
      border-radius: 40px;
      padding: 24px 24px 30px;
      box-shadow: 0 20px 45px rgba(30, 60, 120, 0.2);
      border: 1px solid #dbe4ef;
      transition: max-width 0.3s ease;
    }

    .card.expanded {
      max-width: 900px;
    }

    .scanner-container {
      position: relative;
      background: #eef2f7;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px #d7e0ea, 0 8px 20px rgba(30, 60, 120, 0.12);
      margin-bottom: 24px;
      min-height: 300px;
    }

    #qr-reader {
      width: 100%;
      padding: 0 !important;
      background: #f4f7fb;
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
      background: #eef2f7;
      padding: 8px 18px;
      border-radius: 100px;
      color: #334155;
      font-size: 0.85rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      border: 1px solid #cbd5e1;
    }

    .status-badge i {
      color: #2563eb;
      font-size: 0.9rem;
    }

    .btn-icon {
      background: #eef2f7;
      border: 1px solid #cbd5e1;
      color: #334155;
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
      background: #e2e8f0;
      border-color: #2563eb;
      color: #1e3a8a;
    }

    .btn-icon:active {
      transform: scale(0.92);
    }

    .result-panel {
      background: #f4f7fb;
      border-radius: 28px;
      padding: 18px 20px 16px;
      margin-top: 20px;
      border: 1px solid #d7e0ea;
      box-shadow: inset 0 2px 6px rgba(30, 60, 120, 0.06);
      /* max-height: 00px; */
      overflow-y: auto;
    }

    .result-panel::-webkit-scrollbar {
      width: 6px;
    }

    .result-panel::-webkit-scrollbar-track {
      background: #e2e8f0;
      border-radius: 10px;
    }

    .result-panel::-webkit-scrollbar-thumb {
      background: #94a3b8;
      border-radius: 10px;
    }

    .result-header {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #334155;
      font-weight: 700;
      letter-spacing: 0.3px;
      font-size: 0.9rem;
      border-bottom: 1px dashed #cbd5e1;
      padding-bottom: 10px;
      margin-bottom: 12px;
    }

    .result-header i {
      color: #2563eb;
    }

    #result-content {
      min-height: auto;
      display: flex;
      flex-direction: column;
      gap: 4px;
      word-break: break-word;
    }

    .data-row {
      background: #eef2f7;
      padding: 8px 14px;
      border-radius: 12px;
      border-left: 4px solid #2563eb;
      color: #1e293b;
      font-size: 0.9rem;
      line-height: 1.4;
      box-shadow: 0 2px 6px rgba(30, 60, 120, 0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .data-row .label {
      color: #475569;
      font-weight: 700;
      min-width: 140px;
    }

    .data-row .value {
      color: #0f172a;
      font-weight: 600;
      text-align: right;
      flex: 1;
      margin-left: 10px;
    }

    .single-row {
      display: grid !important;
      grid-template-columns: 2fr 1fr !important;
      gap: 14px;
    }

    .half-row {
      display: grid !important;
      grid-template-columns: 1fr 1fr !important;
      gap: 14px;
    }

    .knit-pair {
      display: grid !important;
      grid-template-columns: 1fr 1fr !important;
      gap: 14px;
    }

    .knit-flow .knit-pair {
      grid-column: span 6;
    }

    @media (max-width:480px) {
      .single-row {
        grid-template-columns: 1fr 1fr !important;
      }

      .half-row {
        grid-template-columns: 1fr 1fr !important;
      }
    }

    .data-row.default-row {
      display: grid !important;
      grid-template-columns: repeat(7, 1fr);
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
      color: #475569;
      font-weight: 700;
      margin-bottom: 5px;
      text-transform: uppercase;
    }

    .data-row.default-row div div:last-child {
      color: #0f172a;
      font-size: 16px;
      font-weight: 700;

      /* Important */
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
      color: #475569;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      opacity: 1;
    }

    .field-block .field-value {
      font-size: 0.95rem;
      color: #0f172a;
      font-weight: 800;
      white-space: normal;
      overflow-wrap: anywhere;
      word-break: break-word;
      line-height: 1.3;
    }

    .data-row.default-row .label {
      color: #475569;
    }

    .data-row.default-row .value {
      color: #0f172a;
    }

    .data-row.header-row {
      border-left-color: #f59e0b;
      background: #e8eef6;
      font-weight: 700;
      font-size: 0.95rem;
    }

    .data-row.header-row .label {
      color: #92400e;
      font-weight: 800;
    }

    .data-row.header-row .value {
      color: #78350f;
      font-weight: 700;
    }

    .empty-message {
      color: #64748b;
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
      background: #eef2f7;
      border: 1px solid #d7e0ea;
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
      background: linear-gradient(135deg, #475569, #334155);
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
      background: #ffffff;
      border: 1px solid #cbd5e1;
      color: #0f172a;
      border-radius: 12px;
      padding: 10px 12px;
      font-size: 0.95rem;
      outline: none;
    }

    .field-input:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .field-label {
      display: block;
      margin-bottom: 5px;
      font-size: 0.8rem;
      color: #475569;
    }

    .field-row {
      display: grid;
      grid-template-columns: 1fr;
      gap: 10px;
      margin-top: 8px;
    }

    .message-row {
      background: #eff6ff;
      color: #1e3a8a;
      padding: 12px 14px;
      border-radius: 16px;
      border: 1px solid #bfdbfe;
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
      background: #eef2f7;
      padding: 10px 14px;
      border-radius: 12px;
      margin-top: 4px;
    }

    .edit-form-row .field-label {
      font-size: 0.75rem;
      color: #475569;
      margin-bottom: 2px;
    }

    .edit-form-row .field-input {
      font-size: 0.85rem;
      padding: 6px 10px;
    }

    .rescan-btn {
      margin-top: 10px;
      background: #eef2f7;
      border: 1px solid #cbd5e1;
      color: #334155;
      font-weight: 600;
      padding: 8px 20px;
      border-radius: 20px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: 0.2s;
    }

    .rescan-btn:hover {
      background: #e2e8f0;
      border-color: #2563eb;
      color: #1e3a8a;
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

    @media(max-width:768px) {
      .data-row.default-row {
        grid-template-columns: repeat(3, 1fr) !important;
      }

      .knit-flow .knit-pair {
        grid-column: 1 / -1;
      }
    }

    @media(max-width:480px) {
      .data-row.default-row {
        grid-template-columns: repeat(3, 1fr) !important;
      }

      .knit-flow .knit-pair {
        grid-column: 1 / -1;
      }
    }

    .data-row.default-row .label {
    white-space: nowrap;
}

.data-row.default-row .value {
    white-space: nowrap;
}

    .production-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
      padding: 0 4px;
    }

    .production-header h2 {
      color: #083a36;
      font-size: 1.4rem;
      font-weight: 800;
      letter-spacing: 0.5px;
      margin: 0;
    }

    .production-header h2 i {
      color: #0f7a6f;
      margin-right: 8px;
    }

    .production-header .badge-production {
      margin-left: auto;
      background: #2563eb;
      color: white;
      font-size: 0.7rem;
      padding: 3px 14px;
      border-radius: 100px;
      font-weight: 600;
      letter-spacing: 0.3px;
    }
  </style>
</head>

<body>
  <div class="card" id="mainCard">
    <div class="production-header">
      <h2><i class="fa-solid fa-industry"></i>Knitting Production</h2>
      <span class="badge-production">PRODUCTION</span>
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
        <span style="margin-left: auto; font-size: 0.7rem; background: #2563eb; padding: 2px 12px; border-radius: 40px; color: #ffffff;">live</span>
      </div>
      <div id="result-content">
        <!-- Default content will be injected by JS -->
      </div>
      <div id="action-content" class="action-content"></div>
    </div>
    <!-- FOOTER -->
    <div class="footer-note" style="margin-top:6px; text-align:center; color:#334155; letter-spacing:0.5px;">
      <button class="btn btn-dark"
        onclick="window.location.href='initialPage.php';"
        style="background-color:#1e3a8a;
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
      const actionContainer = document.getElementById('action-content');
      const cameraStatus = document.getElementById('camera-status');
      const scannerContainer = document.getElementById('scannerContainer');
      const cameraControls = document.getElementById('cameraControls');

      const QR_FIELDS = [
        'SUB_TID', 'BOOKING', 'SONO', 'BUYER', 'MCNO', 'MC_DIA', 'STYLE',
        'YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM', 'FINISH_DIA',
        'OPEN_TUBE', 'COLOR', 'QTY', 'SL_VDQ', 'LOT_NO'
      ];

      const FIELD_LABELS = {
        'SUB_TID': 'ROLL NO',
        'BOOKING': 'PO NUMBER',
        'SONO': 'SONO',
        'BUYER': 'Buyer',
        'MCNO': 'Machine No',
        'MC_DIA': 'Machine Dia',
        'STYLE': 'Style',
        'YARN_TYPE': 'Yarn Type',
        'YARN_COUNT': 'Yarn Count',
        'FABRICS_TYPE': 'Fabrics Type',
        'FINISH_GSM': 'Finish GSM',
        'FINISH_DIA': 'Finish Dia',
        'OPEN_TUBE': 'Open / Tube',
        'COLOR': 'Color',
        'QTY': 'QTY',
        'SL_VDQ': 'SL / VDQ',
        'LOT_NO': 'Lot No',
        'MCARD': 'MCARD',
        'ROLL': 'ROLL',
        'SUPPLIER': 'Supplier',
        'GGSM': 'Gray GSM',
        'FEEDER_PLAN': 'Feeder Plan',
        'SHIFT': 'Shift',
        'KNIT_MATERIAL_CODE': 'Knit Material Code',
        'KNIT_M_DESCRIPTION': 'Knit M Description',
        'CREATED_DATE': 'Created Date',
        'UNAME': 'User'
      };

      let scannedInfo = null;
      let isEditMode = false;
      let html5QrCode = null;
      let isScanning = false;
      let scanCount = 0;

      const DEFAULT_DATA = [{
        label: 'Status',
        value: 'Awaiting QR scan'
      }];

      function renderDefaultData() {
        scannedInfo = null;
        isEditMode = false;
        hideActionContent();

        let html = `<div class="data-row header-row"><span class="label">Default Information</span><span class="value"></span></div>`;
        DEFAULT_DATA.forEach(f => {
          html += `<div class="data-row default-row"><span class="label">${f.label}</span><span class="value">${f.value}</span></div>`;
        });
        resultContainer.innerHTML = html;
      }

      function parseQrText(qrText) {
        const raw = String(qrText || '').trim();
        // Handle both '|' and ' | ' delimiters
        const normalized = raw.replace(/\s*\|\s*/g, '|');
        let parts = normalized.split('|').map(part => String(part).trim());

        // Remove trailing empty parts
        while (parts.length > 0 && parts[parts.length - 1] === '') {
          parts.pop();
        }

        console.log('✓ Parsed parts count:', parts.length, 'Parts:', parts);

        const format16WithSupplier = [
          'SUB_TID', 'MCNO', 'BUYER', 'SUPPLIER', 'BOOKING', 'SONO', 'STYLE',
          'FABRICS_TYPE', 'YARN_COUNT', 'YARN_TYPE', 'FINISH_GSM', 'FINISH_DIA',
          'OPEN_TUBE', 'LOT_NO', 'QTY', 'COLOR'
        ];

        const format16WithoutSupplier = [
          'SUB_TID', 'MCNO', 'BUYER', 'BOOKING', 'SONO', 'STYLE',
          'FABRICS_TYPE', 'YARN_COUNT', 'YARN_TYPE', 'FINISH_GSM', 'FINISH_DIA',
          'OPEN_TUBE', 'LOT_NO', 'QTY', 'COLOR'
        ];

        const format17 = [
          'SUB_TID', 'BOOKING', 'SONO', 'BUYER', 'MCNO', 'MC_DIA', 'STYLE',
          'YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM', 'FINISH_DIA',
          'OPEN_TUBE', 'COLOR', 'QTY', 'SL_VDQ', 'LOT_NO'
        ];

        const buildData = (fields) => {
          const data = {};
          fields.forEach((field, index) => {
            data[field] = parts[index] || '';
          });
          data.raw = raw;
          data.parsed = true;
          data.originalQTY = data.QTY || '';
          data.edited = false;
          return data;
        };

        // Soft validators - less strict
        const isNumeric = (value) => /^\d+$/.test(value);
        const looksLikeBooking = (value) => isNumeric(value) && value.length >= 4;
        const looksLikeSono = (value) => isNumeric(value) && value.length >= 4;
        const looksLikeMachineNo = (value) => /^[A-Za-z0-9\-\/]+$/.test(value) && value.length >= 2;
        const looksLikeBuyer = (value) => /[A-Za-z]/.test(value) && value.length >= 2;

        // Priority 1: Check for 17-field format (from Knitting QR Report) - most common
        if (parts.length === format17.length) {
          // Soft validation: check key fields without being too strict
          const booking = parts[1];
          const sono = parts[2];
          const buyer = parts[3];
          const mcno = parts[4];

          // If at least 2 key fields look right, accept it
          const keyFieldsValid =
            (looksLikeBooking(booking) ? 1 : 0) +
            (looksLikeSono(sono) ? 1 : 0) +
            (looksLikeBuyer(buyer) ? 1 : 0) +
            (looksLikeMachineNo(mcno) ? 1 : 0);

          if (keyFieldsValid >= 2 || (booking && sono)) {
            console.log('✓ Detected format17 (17 fields from QR Report), valid fields:', keyFieldsValid);
            return buildData(format17);
          }

          // Even if validation is weak, if length matches exactly, parse as format17
          console.log('⚠ Format17 length match, accepting as fallback (valid fields:', keyFieldsValid, ')');
          return buildData(format17);
        }

        // Priority 2: Check for 16-field format with supplier
        if (parts.length === format16WithSupplier.length) {
          const mcno = parts[1];
          const booking = parts[4];
          const sono = parts[5];

          if ((looksLikeMachineNo(mcno) || mcno) && looksLikeBooking(booking) && looksLikeSono(sono)) {
            console.log('✓ Detected format16WithSupplier');
            return buildData(format16WithSupplier);
          }
          // If supplier is blank, still try to parse
          if (parts[3] === '' && booking && sono) {
            console.log('✓ Detected format16WithSupplier (empty supplier)');
            return buildData(format16WithSupplier);
          }
        }

        // Priority 3: Check for 16-field format without supplier
        if (parts.length === format16WithoutSupplier.length) {
          const mcno = parts[1];
          const booking = parts[3];
          const sono = parts[4];

          if ((looksLikeMachineNo(mcno) || mcno) && booking && sono) {
            console.log('✓ Detected format16WithoutSupplier');
            return buildData(format16WithoutSupplier);
          }
        }

        // Priority 4: Check if length matches QR_FIELDS exactly
        if (parts.length === QR_FIELDS.length) {
          console.log('✓ Detected QR_FIELDS format (length match)');
          return buildData(QR_FIELDS);
        }

        // Fallback: Try to match by checking if we have reasonable field patterns
        if (parts.length >= 10) {
          // If we have at least 10 parts that look semi-reasonable, try format17
          const hasGoodData = parts.filter(p => p && p.length > 0).length >= 8;
          if (hasGoodData) {
            console.log('⚠ Partial data detected, attempting format17 interpretation');
            if (parts.length <= 17) {
              return buildData(format17.slice(0, parts.length).concat(format17.slice(parts.length)));
            }
          }
        }

        // If nothing matches, return raw data
        console.log('❌ No format detected - returning raw data. Parts count:', parts.length);
        return {
          raw,
          parsed: false,
          parts
        };
      }

      // Render full data fetched from knit_card_test by ROLL
      function renderRollData(row, qrText) {
        const m = (v) => (v === null || v === undefined) ? '' : String(v);

        scannedInfo = {
          SUB_TID: m(row.ROLL),
          BOOKING: m(row.PO_NUMBER),
          SONO: m(row.SONO),
          BUYER: m(row.BUYER),
          MCNO: m(row.MCNO),
          MC_DIA: m(row.MCDIA),
          STYLE: m(row.STYLE),
          YARN_TYPE: m(row.YTYPE),
          YARN_COUNT: m(row.YCOUNT),
          FABRICS_TYPE: m(row.FTYPE),
          FINISH_GSM: m(row.FGSM),
          FINISH_DIA: m(row.FDIA),
          OPEN_TUBE: m(row.O_T),
          COLOR: m(row.COLOR),
          QTY: m(row.QTY),
          SL_VDQ: m(row.SL),
          LOT_NO: m(row.LOT),
          MCARD: m(row.MCARD),
          ROLL: m(row.ROLL),
          SUPPLIER: m(row.SUPPLIER),
          GGSM: m(row.GGSM),
          FEEDER_PLAN: m(row.FEEDER_PLAN),
          SHIFT: m(row.SHIFT),
          KNIT_MATERIAL_CODE: m(row.KNIT_MATERIAL_CODE),
          KNIT_M_DESCRIPTION: m(row.KNIT_M_DESCRIPTION),
          CREATED_DATE: m(row.CREATED_DATE),
          UNAME: m(row.UNAME),
          raw: qrText,
          parsed: true,
          originalQTY: m(row.QTY),
          edited: false
        };

        isEditMode = false;
        hideActionContent();

        const buildFieldRow = (fields, extraClass) => `
          <div class="data-row default-row ${extraClass || ''}">
            ${fields.map(field => `
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS[field] || field}</span>
                <span class="field-value">${scannedInfo[field] || '-'}</span>
              </div>
            `).join('')}
          </div>
        `;

        let html = `
          <div class="data-row header-row" style="border-left-color:#4fc3f7;">
            <span class="label">✅ QR Scanned <span class="scanned-badge">ROLL NO</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
        `;

        html += `
          <div class="data-row default-row knit-flow">
            ${['ROLL', 'BOOKING', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'MCNO',
              'MC_DIA', 'SUPPLIER', 'SHIFT', 'YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM',
              'FINISH_DIA', 'OPEN_TUBE', 'SL_VDQ', 'GGSM', 'FEEDER_PLAN', 'LOT_NO', 'QTY'].map(field => `
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS[field] || field}</span>
                <span class="field-value">${scannedInfo[field] || '-'}</span>
              </div>
            `).join('')}
            <div class="knit-pair">
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS.KNIT_MATERIAL_CODE}</span>
                <span class="field-value">${scannedInfo.KNIT_MATERIAL_CODE || '-'}</span>
              </div>
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS.KNIT_M_DESCRIPTION}</span>
                <span class="field-value">${scannedInfo.KNIT_M_DESCRIPTION || '-'}</span>
              </div>
            </div>
          </div>
        `;

        html += `
          <button class="rescan-btn" onclick="window.location.reload();">
            <i class="fas fa-redo"></i> Scan Another QR
          </button>
        `;

        resultContainer.innerHTML = html;
        renderProductionActions();
      }

      function renderUnstructuredData(text, msg) {
        resultContainer.innerHTML = `
          <div class="data-row header-row" style="border-left-color:#4fc3f7;">
            <span class="label">✅ QR Scanned <span class="scanned-badge">raw</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
          <div class="data-row" style="border-left-color:#f59e0b; background:#e8eef6;">
            <span class="label" style="color:#92400e;">📌 Type:</span>
            <span class="value">${msg || 'Unstructured scan data'}</span>
          </div>
          <div class="data-row" style="background:#eef2f7; border-left-color:#6b7280; flex-wrap:wrap;">
            <span class="label" style="color:#475569; min-width:100%;">Raw Data:</span>
            <span class="value" style="text-align:left; font-size:0.8rem; word-break:break-all; color:#0f172a;">${text}</span>
          </div>
          <button class="rescan-btn" onclick="window.location.reload();">
            <i class="fas fa-redo"></i> Scan Another QR
          </button>
        `;
        hideActionContent();
      }

      function renderScannedData(qrText) {
        if (!qrText || qrText.trim() === '') {
          renderDefaultData();
          return;
        }

        const text = String(qrText).trim();
        const isBareRoll = /^\d+$/.test(text) || /^ROLL:\s*\d+$/i.test(text);

        if (isBareRoll) {
          const roll = text.replace(/^ROLL:\s*/i, '').trim();
          fetch('knitting_production.php?action=get_roll&roll=' + encodeURIComponent(roll))
            .then(r => r.json())
            .then(res => {
              if (res && res.success) {
                renderRollData(res.data, text);
              } else {
                renderUnstructuredData(text, (res && res.error) || 'No data found for ROLL: ' + roll);
              }
            })
            .catch(err => {
              console.error('ROLL lookup failed:', err);
              renderUnstructuredData(text, 'Failed to fetch ROLL data');
            });
          return;
        }

        if (!scannedInfo || scannedInfo.raw !== text) {
          scannedInfo = parseQrText(text);
        }
        isEditMode = false;
        hideActionContent();

        if (!scannedInfo.parsed) {
          resultContainer.innerHTML = `
            <div class="data-row header-row" style="border-left-color:#4fc3f7;">
              <span class="label">✅ QR Scanned <span class="scanned-badge">raw</span></span>
              <span class="value">${new Date().toLocaleTimeString()}</span>
            </div>
            <div class="data-row" style="border-left-color:#f59e0b; background:#e8eef6;">
              <span class="label" style="color:#92400e;">📌 Type:</span>
              <span class="value">Unstructured scan data</span>
            </div>
            <div class="data-row" style="background:#eef2f7; border-left-color:#6b7280; flex-wrap:wrap;">
              <span class="label" style="color:#475569; min-width:100%;">Raw Data:</span>
              <span class="value" style="text-align:left; font-size:0.8rem; word-break:break-all; color:#0f172a;">${scannedInfo.raw}</span>
            </div>
           <button class="rescan-btn" onclick="window.location.reload();">
              <i class="fas fa-redo"></i> Scan Another QR
           </button>
          `;
          return;
        }

        const buildFieldRow = (fields, extraClass) => `
          <div class="data-row default-row ${extraClass || ''}">
            ${fields.map(field => `
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS[field] || field}</span>
                <span class="field-value">${scannedInfo[field] || '-'}</span>
              </div>
            `).join('')}
          </div>
        `;

        let html = `
          <div class="data-row header-row" style="border-left-color:#4fc3f7;">
            <span class="label">✅ QR Scanned <span class="scanned-badge">ROLL NO</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
        `;

        html += buildFieldRow(['SUB_TID', 'BOOKING', 'SONO', 'MCNO', 'MC_DIA', 'BUYER', 'STYLE']);
        html += buildFieldRow(['YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM', 'FINISH_DIA', 'OPEN_TUBE', 'COLOR']);
        html += buildFieldRow(['SL_VDQ', 'LOT_NO', 'QTY']);

        html += `
          <button class="rescan-btn" onclick="window.location.reload();">
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
          window.location.reload();
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

        const qtyValue = scannedInfo.QTY || '';

        const originalQty = scannedInfo.originalQTY || scannedInfo.QTY || '';

        const buildFieldRow = (fields, extraClass) => `
          <div class="data-row default-row ${extraClass || ''}">
            ${fields.map(field => `
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS[field] || field}</span>
                <span class="field-value">${scannedInfo[field] || '-'}</span>
              </div>
            `).join('')}
          </div>
        `;

        let html = `
          <div class="data-row header-row" style="border-left-color:#3b82f6;">
            <span class="label">✏️ Edit Mode <span class="scanned-badge" style="background:#3b82f6;">editing</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
          <div class="message-row" style="margin-bottom:8px;">Only <strong>REJ QTY</strong> is editable. Maximum available qty: <strong>${originalQty || 'N/A'}</strong>.</div>
        `;

        html += `
          <div class="data-row default-row knit-flow">
            ${['ROLL', 'BOOKING', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'MCNO',
              'MC_DIA', 'SUPPLIER', 'SHIFT', 'YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM',
              'FINISH_DIA', 'OPEN_TUBE', 'SL_VDQ', 'GGSM', 'FEEDER_PLAN', 'LOT_NO'].map(field => `
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS[field] || field}</span>
                <span class="field-value">${scannedInfo[field] || '-'}</span>
              </div>
            `).join('')}
            <div class="field-block">
              <span class="field-label">REJ QTY</span>
              <input type="text" class="field-input" id="edit-QTY" value="${qtyValue}">
            </div>
            <div class="knit-pair">
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS.KNIT_MATERIAL_CODE}</span>
                <span class="field-value">${scannedInfo.KNIT_MATERIAL_CODE || '-'}</span>
              </div>
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS.KNIT_M_DESCRIPTION}</span>
                <span class="field-value">${scannedInfo.KNIT_M_DESCRIPTION || '-'}</span>
              </div>
            </div>
          </div>
        `;

        html += `
          <button class="rescan-btn" onclick="window.location.reload();">
            <i class="fas fa-redo"></i> Scan Another QR
          </button>
        `;

        resultContainer.innerHTML = html;

        actionContainer.innerHTML = `
        <div class="action-card">
        
            <button class="btn-action production" type="button" id="editedProductionBtn">
                <i class="fas fa-save"></i> Edited Production
            </button>
        
            <button class="btn-action cancel" type="button" id="cancelProductionBtn">
                <i class="fas fa-times"></i> Cancel
            </button>
        
        </div>
        `;

        document.getElementById("editedProductionBtn").addEventListener("click", editedProductionSave);
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

        const input = document.getElementById('edit-QTY');
        if (!input) {
          return;
        }

        const newQtyRaw = input.value.trim();
        const maxQty = parseFloat((scannedInfo.originalQTY || scannedInfo.QTY || '').replace(/,/g, '.')) || 0;
        const newQty = parseFloat(newQtyRaw.replace(/,/g, '.'));

        if (!newQtyRaw) {
          alert('Qty cannot be empty.');
          return;
        }
        if (isNaN(newQty) || newQty <= 0) {
          alert('Qty must be a valid number.');
          return;
        }
        if (maxQty > 0 && newQty > maxQty + 0.000001) {
          alert('Qty exceeds available amount. Maximum available qty: ' + (scannedInfo.originalQTY || scannedInfo.QTY));
          return;
        }

        const previousOriginalQty = scannedInfo.originalQTY;
        scannedInfo.QTY = newQtyRaw;
        scannedInfo.raw = QR_FIELDS.map(field => scannedInfo[field] || '').join(" | ");
        isEditMode = false;
        renderScannedData(scannedInfo.raw);

        if (scannedInfo && scannedInfo.parsed) {
          scannedInfo.originalQTY = previousOriginalQty;
        }

        const productionBtn = document.getElementById("productionBtn");
        if (productionBtn) {
          productionBtn.disabled = false;
        }
      }

      function editedProductionSave() {

        const input = document.getElementById("edit-QTY");
        if (!input) return;

        const newQtyRaw = input.value.trim();
        const maxQty = parseFloat(scannedInfo.originalQTY || 0);
        const newQty = parseFloat(newQtyRaw);

        if (newQtyRaw === "") {
          alert("Qty cannot be empty");
          return;
        }

        if (isNaN(newQty) || newQty <= 0) {
          alert("Invalid Qty");
          return;
        }

        if (newQty > maxQty) {
          alert("Qty exceeds available Qty");
          return;
        }

        scannedInfo.QTY = newQtyRaw;
        handleProductionSave();

      }

      function handleProductionSave() {

        if (!scannedInfo || !scannedInfo.parsed) {
          alert("No QR Data");
          return;
        }

        let oqty = parseFloat(scannedInfo.originalQTY || scannedInfo.QTY || 0);
        let rqty = 0;
        if (String(scannedInfo.QTY) !== String(scannedInfo.originalQTY)) {
          rqty = parseFloat(scannedInfo.QTY || 0);
        }
        let uqty = (oqty - rqty).toFixed(2);

        const payload = {
          sub_tid: scannedInfo.SUB_TID || "",
          booking: scannedInfo.BOOKING || "",
          sono: scannedInfo.SONO || "",
          buyer: scannedInfo.BUYER || "",
          mcno: scannedInfo.MCNO || "",
          mc_dia: scannedInfo.MC_DIA || "",
          style: scannedInfo.STYLE || "",
          yarn_type: scannedInfo.YARN_TYPE || "",
          yarn_count: scannedInfo.YARN_COUNT || "",
          fabrics_type: scannedInfo.FABRICS_TYPE || "",
          finish_gsm: scannedInfo.FINISH_GSM || "",
          finish_dia: scannedInfo.FINISH_DIA || "",
          open_tube: scannedInfo.OPEN_TUBE || "",
          color: scannedInfo.COLOR || "",
          sl_vdq: scannedInfo.SL_VDQ || "",
          supplier: scannedInfo.SUPPLIER || "",
          gray_gsm: scannedInfo.GGSM || "",
          feeder_plan: scannedInfo.FEEDER_PLAN || "",
          lot_no: scannedInfo.LOT_NO || "",
          knit_material_code: scannedInfo.KNIT_MATERIAL_CODE || "",
          knit_m_desc: scannedInfo.KNIT_M_DESCRIPTION || "",

          oqty: oqty,
          rqty: rqty,
          uqty: uqty

        };

        console.log(payload);

        fetch("ajaxKnittingProductionInsert.php", {

            method: "POST",

            headers: {
              "Content-Type": "application/json"
            },

            body: JSON.stringify(payload)

          })

          .then(async res => {

            const txt = await res.text();

            console.log(txt);

            return JSON.parse(txt);

          })

          .then(data => {

            console.log(data);

            if (data.success) {

              renderResultMessage(
                "✅ " + data.message,
                "success"
              );

            } else {

              renderResultMessage(
                "❌ " + data.message,
                "error"
              );

            }

          })

          .catch(err => {

            console.error(err);

            renderResultMessage(
              err.message,
              "error"
            );

          });

      }

      function renderResultMessage(message, type) {

        actionContainer.innerHTML = `
        <div class="message-row ${type}">
            ${message}<br>
        </div>
    `;

        if (type === "success") {

          const btn = document.getElementById("productionBtn");
          if (btn) btn.disabled = true;

          setTimeout(() => {
            window.location.reload();
          }, 2000);

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
            <button class="rescan-btn" onclick="window.location.reload();">
              <i class="fas fa-redo"></i> Retry Camera
            </button>
          `;
        });
      }

      function onScanSuccess(decodedText, decodedResult) {
        console.log('QR Scanned:', decodedText);
        scanCount++;
        if (navigator.vibrate) navigator.vibrate(20);

        const cardEl = document.getElementById('mainCard');
        if (cardEl) cardEl.classList.add('expanded');

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