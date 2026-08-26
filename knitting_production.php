<?php
if (isset($_GET['action']) && $_GET['action'] === 'get_roll') {
  require_once 'config.php';
  header('Content-Type: application/json');
  header('X-Content-Type-Options: nosniff');

  $KNITCARD = isset($_GET['knitcard']) ? trim($_GET['knitcard']) :
    (isset($_GET['roll']) ? trim($_GET['roll']) : '');
  if ($KNITCARD === '') {
    echo json_encode(['success' => false, 'error' => 'KNITCARD is required']);
    exit();
  }

  $s = mysqli_real_escape_string($db, $KNITCARD);
  $q = "SELECT * FROM knit_card WHERE KNITCARD = '$s' LIMIT 1";
  $res = mysqli_query($db, $q);

  if ($res && mysqli_num_rows($res) > 0) {
    $data = mysqli_fetch_assoc($res);

    $kc = isset($data['KNITCARD']) && trim($data['KNITCARD']) !== '' ? trim($data['KNITCARD']) : $KNITCARD;
    $origQty = isset($data['QTY']) ? floatval($data['QTY']) : 0;

    $produced = 0;
    $pStmt = mysqli_prepare($db, "SELECT COALESCE(SUM(PQTY),0) AS produced FROM knitting_production WHERE TRIM(KNITCARD) = ?");
    if ($pStmt) {
      mysqli_stmt_bind_param($pStmt, "s", $kc);
      mysqli_stmt_execute($pStmt);
      $pRes = mysqli_stmt_get_result($pStmt);
      if ($pRes) {
        $pRow = mysqli_fetch_assoc($pRes);
        $produced = $pRow ? floatval($pRow['produced']) : 0;
      }
      mysqli_stmt_close($pStmt);
    }

    $data['ORIGINAL_QTY'] = $origQty;
    $data['PRODUCED_QTY'] = round($produced, 2);
    $data['REMAINING_QTY'] = round(max($origQty - $produced, 0), 2);

    echo json_encode(['success' => true, 'data' => $data]);
  } else {
    echo json_encode(['success' => false, 'error' => 'No data found for KNITCARD: ' . $KNITCARD]);
  }
  exit();
}

// ------- In-file AJAX endpoint: verify knitting_operator by OPERATOR_ID -------
if (isset($_GET['action']) && $_GET['action'] === 'get_operator') {
  require_once 'config.php';
  header('Content-Type: application/json');
  header('X-Content-Type-Options: nosniff');

  $op = isset($_GET['operator_id']) ? trim($_GET['operator_id']) : '';
  if ($op === '') {
    echo json_encode(['success' => false, 'error' => 'Operator ID is required']);
    exit();
  }

  $s = mysqli_real_escape_string($db, $op);
  $q = "SELECT OPERATOR_ID, OPERATOR_NAME FROM knitting_operator WHERE OPERATOR_ID = '$s' LIMIT 1";
  $res = mysqli_query($db, $q);

  if ($res && mysqli_num_rows($res) > 0) {
    echo json_encode(['success' => true, 'data' => mysqli_fetch_assoc($res)]);
  } else {
    echo json_encode(['success' => false, 'error' => 'Invalid Operator ID: ' . $op]);
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="js/qrcode.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
      max-width: 650px;
    }

    .scanner-container {
      position: relative;
      background: #eef2f7;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px #d7e0ea, 0 8px 20px rgba(30, 60, 120, 0.12);
      margin-bottom: 24px;
      aspect-ratio: 1 / 1;
    }

    #qr-reader {
      width: 100%;
      height: 100%;
      padding: 0 !important;
      background: #f4f7fb;
    }

    #qr-reader video {
      border-radius: 28px;
      width: 100%;
      height: 100%;
      object-fit: cover;
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
      grid-template-columns: repeat(3, 1fr);
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

    .data-row.default-row.operator-info-row {
      grid-template-columns: 1fr 1fr !important;
      gap: 14px;
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

    .manual-entry {
      display: flex;
      gap: 8px;
      margin-top: 10px;
    }

    .manual-entry input {
      flex: 1;
      min-width: 0;
      padding: 9px 14px;
      border: 1px solid #cbd5e1;
      border-radius: 20px;
      font-size: 0.85rem;
      outline: none;
      background: #ffffff;
      color: #0f172a;
    }

    .manual-entry input:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .manual-entry button {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: #ffffff;
      border: none;
      padding: 9px 20px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      white-space: nowrap;
      transition: 0.2s;
    }

    .manual-entry button:hover {
      filter: brightness(1.1);
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

  <!-- Knit Card Print Area -->
  <style>
    #knitCardPrintArea { display: none; }

    .kc-card {
      font-family: 'Segoe UI', Arial, sans-serif;
      color: #111827;
      border: 2px solid #111827;
      border-radius: 10px;
      padding: 16px 18px;
      background: #ffffff;
    }
    .kc-head {
      text-align: center;
      border-bottom: 2px solid #111827;
      padding-bottom: 8px;
      margin-bottom: 12px;
    }
    .kc-head h2 { margin: 0; font-size: 20px; letter-spacing: 1.5px; text-transform: uppercase; }
    .kc-head .kc-sub { font-size: 11.5px; color: #4b5563; margin-top: 3px; }

    .kc-body { display: flex; gap: 14px; align-items: flex-start; }
    .kc-left {
      width: 168px; flex-shrink: 0;
      display: flex; flex-direction: column; align-items: center;
      gap: 8px;
      padding-top: 4px;
    }
    .kc-qr-box {
      width: 150px; height: 150px;
      border: 1px dashed #9ca3af;
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
    }
    .kc-qr-roll {
      width: 100%;
      text-align: center;
      font-size: 15px;
      font-weight: 800;
      letter-spacing: 0.5px;
      background: #111827;
      color: #ffffff;
      border-radius: 6px;
      padding: 5px 4px;
    }
    .kc-fields {
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      overflow: hidden;
      align-content: start;
    }
    .kc-field {
      padding: 5px 10px;
      font-size: 12px;
      line-height: 1.45;
      border-bottom: 1px solid #e5e7eb;
      border-right: 1px solid #e5e7eb;
      word-break: break-word;
      background: #ffffff;
    }
    .kc-field:nth-child(2n) { border-right: none; }
    .kc-field-full { grid-column: 1 / -1; border-right: none; }
    .kc-label { font-weight: 700; color: #374151; }
    .kc-value { color: #111827; margin-left: 6px; font-weight: 600; }
    .kc-field.kc-highlight { background: #eff6ff; }
    .kc-highlight .kc-value { font-weight: 800; font-size: 13.5px; color: #1d4ed8; }

    @media print {
      body * { visibility: hidden !important; }
      #knitCardPrintArea {
        display: block !important;
        visibility: visible !important;
        position: absolute;
        left: 0; top: 0;
        width: 6cm;
        height: 6cm;
        overflow: hidden;
        background: #ffffff;
      }
      #knitCardPrintArea * { visibility: visible !important; }
      /* rendered label image -> exact 6cm x 6cm at top-left of A4 */
      #knitCardPrintArea img {
        width: 6cm !important;
        height: 6cm !important;
        display: block;
      }
      @page { size: A4 portrait; margin: 0; }
    }
  </style>
  <div id="knitCardPrintArea"></div>

  <script>
    (function() {
      "use strict";

      const resultContainer = document.getElementById('result-content');
      const actionContainer = document.getElementById('action-content');
      const cameraStatus = document.getElementById('camera-status');
      const scannerContainer = document.getElementById('scannerContainer');
      const cameraControls = document.getElementById('cameraControls');

      const QR_FIELDS = [
        'KNITCARD', 'BOOKING', 'SONO', 'BUYER', 'MCNO', 'MC_DIA', 'STYLE',
        'YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM', 'FINISH_DIA',
        'OPEN_TUBE', 'COLOR', 'QTY', 'SL_VDQ', 'LOT_NO'
      ];

      const FIELD_LABELS = {
        'KNITCARD': 'Knit Card No',
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
        'CUSTOMER': 'Customer',
        'GGSM': 'Gray GSM',
        'FEEDER_PLAN': 'Feeder Plan',
        'SHIFT': 'Shift',
        'KNIT_MATERIAL_CODE': 'Knit Material Code',
        'KNIT_M_DESCRIPTION': 'Knit M Description',
        'CREATED_DATE': 'Created Date',
        'UNAME': 'User'
      };

      let scannedInfo = null;
      let html5QrCode = null;
      let isScanning = false;
      let scanCount = 0;
      let operatorScanned = false;
      let verifying = false;
      let processingRoll = false;
      let operatorInfo = null;

      const DEFAULT_DATA = [{
        label: 'Step 1',
        value: 'Scan/ Enter Knitting Operator ID'
      }, {
        label: 'Step 2',
        value: 'Scan/ Enter Production Knit Card QR'
      }];

      function renderDefaultData() {
        scannedInfo = null;
        hideActionContent();

        let html = `
          <div class="manual-entry">
            <input type="text" id="manualOperatorInput" placeholder="Operator ID (e.g. OP01)" autocomplete="off">
            <button type="button" id="manualOperatorBtn">Load</button>
          </div>
        `;
        html += `<div class="data-row header-row"><span class="label">Default Information</span><span class="value"></span></div>`;
        DEFAULT_DATA.forEach(f => {
          html += `<div class="data-row default-row"><span class="label">${f.label}</span><span class="value">${f.value}</span></div>`;
        });
        resultContainer.innerHTML = html;

        const opInput = document.getElementById('manualOperatorInput');
        const opBtn = document.getElementById('manualOperatorBtn');
        if (opBtn) opBtn.addEventListener('click', submitManualOperator);
        if (opInput) opInput.addEventListener('keydown', e => {
          if (e.key === 'Enter') { e.preventDefault(); submitManualOperator(); }
        });
      }

      function submitManualOperator() {
        if (verifying || operatorScanned) return;
        const inp = document.getElementById('manualOperatorInput');
        const val = inp ? String(inp.value).trim() : '';
        if (!val) {
          alert('Please enter Operator ID!');
          return;
        }
        verifying = true;
        verifyOperatorScan(val);
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

        console.log('âœ“ Parsed parts count:', parts.length, 'Parts:', parts);

        const format16WithCustomer = [
          'KNITCARD', 'MCNO', 'BUYER', 'CUSTOMER', 'BOOKING', 'SONO', 'STYLE',
          'FABRICS_TYPE', 'YARN_COUNT', 'YARN_TYPE', 'FINISH_GSM', 'FINISH_DIA',
          'OPEN_TUBE', 'LOT_NO', 'QTY', 'COLOR'
        ];

        const format16WithoutCustomer = [
          'KNITCARD', 'MCNO', 'BUYER', 'BOOKING', 'SONO', 'STYLE',
          'FABRICS_TYPE', 'YARN_COUNT', 'YARN_TYPE', 'FINISH_GSM', 'FINISH_DIA',
          'OPEN_TUBE', 'LOT_NO', 'QTY', 'COLOR'
        ];

        const format17 = [
          'KNITCARD', 'BOOKING', 'SONO', 'BUYER', 'MCNO', 'MC_DIA', 'STYLE',
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
            console.log('âœ“ Detected format17 (17 fields from QR Report), valid fields:', keyFieldsValid);
            return buildData(format17);
          }

          // Even if validation is weak, if length matches exactly, parse as format17
          console.log('âš  Format17 length match, accepting as fallback (valid fields:', keyFieldsValid, ')');
          return buildData(format17);
        }

        // Priority 2: Check for 16-field format with customer
        if (parts.length === format16WithCustomer.length) {
          const mcno = parts[1];
          const booking = parts[4];
          const sono = parts[5];

          if ((looksLikeMachineNo(mcno) || mcno) && looksLikeBooking(booking) && looksLikeSono(sono)) {
            console.log('âœ“ Detected format16WithCustomer');
            return buildData(format16WithCustomer);
          }
          // If customer is blank, still try to parse
          if (parts[3] === '' && booking && sono) {
            console.log('âœ“ Detected format16WithCustomer (empty customer)');
            return buildData(format16WithCustomer);
          }
        }

        // Priority 3: Check for 16-field format without customer
        if (parts.length === format16WithoutCustomer.length) {
          const mcno = parts[1];
          const booking = parts[3];
          const sono = parts[4];

          if ((looksLikeMachineNo(mcno) || mcno) && booking && sono) {
            console.log('âœ“ Detected format16WithoutCustomer');
            return buildData(format16WithoutCustomer);
          }
        }

        // Priority 4: Check if length matches QR_FIELDS exactly
        if (parts.length === QR_FIELDS.length) {
          console.log('âœ“ Detected QR_FIELDS format (length match)');
          return buildData(QR_FIELDS);
        }

        // Fallback: Try to match by checking if we have reasonable field patterns
        if (parts.length >= 10) {
          // If we have at least 10 parts that look semi-reasonable, try format17
          const hasGoodData = parts.filter(p => p && p.length > 0).length >= 8;
          if (hasGoodData) {
            console.log('âš  Partial data detected, attempting format17 interpretation');
            if (parts.length <= 17) {
              return buildData(format17.slice(0, parts.length).concat(format17.slice(parts.length)));
            }
          }
        }

        // If nothing matches, return raw data
        console.log('No format detected - returning raw data. Parts count:', parts.length);
        return {
          raw,
          parsed: false,
          parts
        };
      }

      function buildOperatorInfoHtml() {
        const operatorId = operatorInfo && operatorInfo.OPERATOR_ID ? operatorInfo.OPERATOR_ID : '-';
        const operatorName = operatorInfo && operatorInfo.OPERATOR_NAME ? operatorInfo.OPERATOR_NAME : '-';

        return `
          <div class="data-row default-row operator-info-row">
            <div class="field-block">
              <span class="field-label">Operator ID</span>
              <span class="field-value">${operatorId}</span>
            </div>
            <div class="field-block">
              <span class="field-label">Operator Name</span>
              <span class="field-value">${operatorName}</span>
            </div>
          </div>
        `;
      }

      function renderRollData(row, qrText) {
        const m = (v) => (v === null || v === undefined) ? '' : String(v);

        scannedInfo = {
          KNITCARD: m(row.KNITCARD),
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
          CUSTOMER: m(row.CUSTOMER),
          GGSM: m(row.GGSM),
          FEEDER_PLAN: m(row.FEEDER_PLAN),
          SHIFT: m(row.SHIFT),
          KNIT_MATERIAL_CODE: m(row.KNIT_MATERIAL_CODE),
          KNIT_M_DESCRIPTION: m(row.KNIT_M_DESCRIPTION),
          CREATED_DATE: m(row.CREATED_DATE),
          UNAME: m(row.UNAME),
          raw: qrText,
          parsed: true,
          originalQTY: m(row.QTY)
        };

        const origQty = parseFloat(row.QTY) || 0;
        const producedQty = parseFloat(row.PRODUCED_QTY) || 0;
        let remainingQty;
        if (row.REMAINING_QTY === null || row.REMAINING_QTY === undefined || row.REMAINING_QTY === '') {
          remainingQty = origQty - producedQty;
        } else {
          remainingQty = parseFloat(row.REMAINING_QTY) || 0;
        }
        scannedInfo.ORIGINAL_QTY = origQty;
        scannedInfo.PRODUCED_QTY = producedQty;
        scannedInfo.REMAINING_QTY = Math.max(remainingQty, 0);

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
            <span class="label">Scanned Data (Knit Card Scanned) <span class="scanned-badge">KNITCARD</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
        `;

        html += `
          <div class="data-row default-row knit-flow">
            ${['KNITCARD', 'BOOKING', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'MCNO',
              'MC_DIA', 'CUSTOMER', 'SHIFT', 'YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM',
              'FINISH_DIA', 'OPEN_TUBE', 'SL_VDQ', 'GGSM', 'FEEDER_PLAN', 'LOT_NO'].map(field => `
              <div class="field-block">
                <span class="field-label">${FIELD_LABELS[field] || field}</span>
                <span class="field-value">${scannedInfo[field] || '-'}</span>
              </div>
            `).join('')}
          </div>
        `;

        const remaining = scannedInfo.REMAINING_QTY;
        const qtyColor = remaining <= 0 ? '#b91c1c' : '#047857';

        html += `
          <div class="data-row default-row" style="border-left-color:${remaining <= 0 ? '#ef4444' : '#10b981'}; background:${remaining <= 0 ? '#fef2f2' : '#f0fdf4'};">
            <div class="field-block">
              <span class="field-label">Original QTY (KG)</span>
              <span class="field-value">${origQty || '-'}</span>
            </div>
            <div class="field-block">
              <span class="field-label">Produced QTY (KG)</span>
              <span class="field-value">${producedQty || 0}</span>
            </div>
            <div class="field-block">
              <span class="field-label">Remaining QTY (KG)</span>
              <span class="field-value" style="font-weight:800;color:${qtyColor};">${remaining}</span>
            </div>
          </div>
        `;

        if (remaining <= 0) {
          html += `
            <div class="data-row" style="background:#fef2f2;border-left-color:#ef4444;">
              <span class="value" style="color:#b91c1c;font-weight:700;">
                QTY not available! Remaining qty is 0
              </span>
            </div>
            <button class="rescan-btn" onclick="window.location.reload();">
              <i class="fas fa-redo"></i> Scan Another QR
            </button>
          `;
          resultContainer.innerHTML = html;
          hideActionContent();
          return;
        }

        html += `
          <div class="data-row default-row scale-qty-row">
            <div class="field-block">
              <span class="field-label">Scale QTY</span>
              <input type="number" id="scaleQtyInput" class="field-input" min="0.01" max="${remaining}" step="0.01" placeholder="Max ${remaining}">
            </div>
          </div>
        `;

        html += buildOperatorInfoHtml();

        html += `
          <button class="rescan-btn" onclick="window.location.reload();">
            <i class="fas fa-redo"></i> Scan Another QR
          </button>
        `;

        resultContainer.innerHTML = html;
        renderProductionActions();
      }

      function verifyOperatorScan(qrText) {
        const val = String(qrText).trim();

        fetch('knitting_production.php?action=get_operator&operator_id=' + encodeURIComponent(val))
          .then(r => r.text())
          .then(txt => {
            verifying = false;
            let res = null;
            try {
              res = JSON.parse(String(txt).replace(/^\uFEFF/, '').trim());
            } catch (e) {
              throw new Error('Bad server response');
            }
            if (res && res.success && res.data) {
              operatorScanned = true;
              operatorInfo = res.data;
              renderOperatorVerified();
            } else {
              alert('Please scan Knitting Operator ID first!\n' + ((res && res.error) || 'Invalid Operator ID'));
            }
          })
          .catch(err => {
            verifying = false;
            alert('Operator verification failed: ' + (err && err.message ? err.message : err));
          });
      }

      function renderOperatorVerified() {
        resultContainer.innerHTML = `
          <div class="manual-entry">
            <input type="text" id="manualRollInput" placeholder="Knit Card QR / Card No" autocomplete="off">
            <button type="button" id="manualRollBtn">Load Data</button>
          </div>
          <div class="data-row header-row" style="border-left-color:#10b981;">
            <span class="label">Scanned Data (Knitting Operator) <span class="scanned-badge" style="background:#10b981;">OPERATOR</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
          <div class="data-row default-row operator-info-row">
            <div class="field-block"><span class="field-label">Operator ID</span><span class="field-value">${operatorInfo.OPERATOR_ID || '-'}</span></div>
            <div class="field-block"><span class="field-label">Operator Name</span><span class="field-value">${operatorInfo.OPERATOR_NAME || '-'}</span></div>
          </div>
          <div class="data-row" style="border-left-color:#f59e0b; background:#fffbeb; margin-top:8px;">
            <span class="value" style="color:#92400e; font-weight:600;">Now Scan/ Enter the Knit Card QR</span>
          </div>
        `;
        hideActionContent();
        if (typeof cameraStatus !== 'undefined' && cameraStatus) {
          cameraStatus.innerText = 'Operator verified - scan production roll';
        }

        const rollInput = document.getElementById('manualRollInput');
        const rollBtn = document.getElementById('manualRollBtn');
        if (rollBtn) rollBtn.addEventListener('click', submitManualRoll);
        if (rollInput) rollInput.addEventListener('keydown', e => {
          if (e.key === 'Enter') { e.preventDefault(); submitManualRoll(); }
        });
        if (rollInput) rollInput.focus();
      }

      function submitManualRoll() {
        if (processingRoll || !operatorScanned) return;
        const inp = document.getElementById('manualRollInput');
        const val = inp ? String(inp.value).trim() : '';
        if (!val) {
          alert('Please scan or enter Knit Card QR!');
          return;
        }
        processingRoll = true;
        stopCameraAndProcess(val);
      }

      function renderUnstructuredData(text, msg) {
        resultContainer.innerHTML = `
          <div class="data-row header-row" style="border-left-color:#4fc3f7;">
            <span class="label">QR Scanned <span class="scanned-badge">raw</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
          <div class="data-row" style="border-left-color:#f59e0b; background:#e8eef6;">
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
        fetch('knitting_production.php?action=get_roll&knitcard=' + encodeURIComponent(text))
          .then(r => r.json())
          .then(res => {
            if (res && res.success) {
              renderRollData(res.data, text);
              return;
            }

            // Keep support for older pipe-delimited Knit Card QR payloads.
            if (!scannedInfo || scannedInfo.raw !== text) {
              scannedInfo = parseQrText(text);
            }
            if (!scannedInfo.parsed) {
              renderUnstructuredData(text, (res && res.error) || 'No Knit Card data found');
              return;
            }
            renderScannedDataFromParsedQr(text);
          })
          .catch(err => {
            console.error('Knit Card lookup failed:', err);
            renderUnstructuredData(text, 'Failed to fetch Knit Card data');
          })
          .finally(() => {
            processingRoll = false;
          });
        return;

      }

      function renderScannedDataFromParsedQr(qrText) {
        const text = String(qrText || '').trim();

        if (!scannedInfo || scannedInfo.raw !== text) {
          scannedInfo = parseQrText(text);
        }
        hideActionContent();

        if (!scannedInfo.parsed) {
          resultContainer.innerHTML = `
            <div class="data-row header-row" style="border-left-color:#4fc3f7;">
              <span class="label">QR Scanned <span class="scanned-badge">raw</span></span>
              <span class="value">${new Date().toLocaleTimeString()}</span>
            </div>
            <div class="data-row" style="border-left-color:#f59e0b; background:#e8eef6;">
              <span class="label" style="color:#92400e;">Type:</span>
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
            <span class="label">Scanned Data (Knit Card Scanned) <span class="scanned-badge">KNITCARD</span></span>
            <span class="value">${new Date().toLocaleTimeString()}</span>
          </div>
        `;

        html += buildFieldRow(['KNITCARD', 'BOOKING', 'SONO', 'MCNO', 'MC_DIA', 'BUYER', 'STYLE']);
        html += buildFieldRow(['YARN_TYPE', 'YARN_COUNT', 'FABRICS_TYPE', 'FINISH_GSM', 'FINISH_DIA', 'OPEN_TUBE', 'COLOR']);
        html += buildFieldRow(['SL_VDQ', 'LOT_NO', 'QTY']);
        html += buildOperatorInfoHtml();

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
              <i class="fas fa-save"></i> Save Production
            </button>
            <button class="btn-action cancel" type="button" id="cancelProductionBtn">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        `;

        document.getElementById('productionBtn').addEventListener('click', handleProductionSave);
        document.getElementById('cancelProductionBtn').addEventListener('click', function() {
          window.location.reload();
        });
      }

      function hideActionContent() {
        actionContainer.innerHTML = '';
      }

      function handleProductionSave() {

        if (!scannedInfo || !scannedInfo.parsed) {
          alert("No QR Data");
          return;
        }

        const scaleQtyInput = document.getElementById('scaleQtyInput');
        const pqty = scaleQtyInput ? parseFloat(scaleQtyInput.value) : 0;

        if (!Number.isFinite(pqty) || pqty <= 0) {
          alert('Please enter a valid Scale QTY.');
          if (scaleQtyInput) scaleQtyInput.focus();
          return;
        }

        const remainingQty = scannedInfo ? parseFloat(scannedInfo.REMAINING_QTY) : NaN;
        if (Number.isFinite(remainingQty)) {
          if (remainingQty <= 0) {
            alert('QTY not available! Remaining qty is 0 for this Knit Card.');
            window.location.reload();
            return;
          }
          if (pqty > remainingQty) {
            alert('Scale QTY (' + pqty + ') exceeds Remaining QTY (' + remainingQty + ').\nOriginal - Produced = ' + remainingQty);
            if (scaleQtyInput) scaleQtyInput.focus();
            return;
          }
        }

        window.__kpSavedQty = pqty;

        const payload = {
          knitcard: scannedInfo.KNITCARD || "",
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
          customer: scannedInfo.CUSTOMER || "",
          gray_gsm: scannedInfo.GGSM || "",
          feeder_plan: scannedInfo.FEEDER_PLAN || "",
          lot_no: scannedInfo.LOT_NO || "",
          knit_material_code: scannedInfo.KNIT_MATERIAL_CODE || "",
          knit_m_desc: scannedInfo.KNIT_M_DESCRIPTION || "",
          uid: operatorInfo && operatorInfo.OPERATOR_ID ? operatorInfo.OPERATOR_ID : "",
          uname: operatorInfo && operatorInfo.OPERATOR_NAME ? operatorInfo.OPERATOR_NAME : "",

          pqty: pqty

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

              window.__kpSavedRoll = data.roll || "";
              window.__kpSavedMsg = data.message || "Production saved successfully.";

              const prodBtn = document.getElementById("productionBtn");
              if (prodBtn) prodBtn.disabled = true;

              if (typeof Swal === "undefined") {
                renderResultMessage(data.message, "success");
                return;
              }

              Swal.fire({
                icon: "success",
                title: "Successfully Saved!",
                text: "Roll No: " + (window.__kpSavedRoll || "-"),
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-print"></i> Print Knit Card',
                cancelButtonText: "Close",
                confirmButtonColor: "#1d4ed8",
                cancelButtonColor: "#6b7280",
                allowOutsideClick: false,
                allowEscapeKey: false
              }).then((result) => {
                if (result.isConfirmed) {
                  printKnitCard();
                } else {
                  window.location.reload();
                }
              }).catch(() => {
                window.location.reload();
              });

            } else {

              renderResultMessage(
                data.message,
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

      function escHtml(v) {
        return String(v === null || v === undefined ? '' : v)
          .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      }

      function printKnitCard() {
        const roll = window.__kpSavedRoll || '';
        const info = scannedInfo || {};
        const pqty = window.__kpSavedQty || '';
        const opId = operatorInfo && operatorInfo.OPERATOR_ID ? operatorInfo.OPERATOR_ID : '';
        const opName = operatorInfo && operatorInfo.OPERATOR_NAME ? operatorInfo.OPERATOR_NAME : '';

        // Fallback values if saved row cannot be fetched
        var fallbackRow = {
          ROLL: roll,
          PO_NUMBER: info.BOOKING,
          PQTY: pqty,
          SHIFT: info.SHIFT,
          BUDAT: new Date().toISOString().slice(0, 10),
          UNAME: (opId ? opId : '') + (opName ? ' - ' + opName : ''),
          SONO: info.SONO,
          BUYER: info.BUYER,
          STYLE: info.STYLE,
          COLOR: info.COLOR,
          MCNO: info.MCNO,
          MC_DIA: info.MC_DIA,
          CUSTOMER: info.CUSTOMER,
          YARN_TYPE: info.YARN_TYPE,
          YARN_COUNT: info.YARN_COUNT,
          FABRICS_TYPE: info.FABRICS_TYPE,
          FINISH_GSM: info.FINISH_GSM,
          FINISH_DIA: info.FINISH_DIA,
          OPEN_TUBE: info.OPEN_TUBE,
          SL_VDQ: info.SL_VDQ,
          GRAY_GSM: info.GGSM,
          FEEDER_PLAN: info.FEEDER_PLAN,
          LOT_NO: info.LOT_NO
        };

        // Fetch the exact saved production row (same source as Production Report page)
        fetch('ajaxKnittingProduction_Report.php?search=' + encodeURIComponent(roll))
          .then(function(res) { return res.json(); })
          .then(function(resp) {
            var row = (resp && resp.success && resp.data && resp.data.length > 0) ? resp.data[0] : fallbackRow;
            renderLabelAndPrint(row);
          })
          .catch(function() {
            renderLabelAndPrint(fallbackRow);
          });
      }

      function renderLabelAndPrint(row) {
        var roll = row.ROLL || window.__kpSavedRoll || '';

        var fieldHTML = [
            ['Shift', row.SHIFT],
            ['UName', row.UNAME],
            ['SONO', row.SONO],
          ['LOT', row.LOT_NO],
          ['Style', row.STYLE],
          ['Color', row.COLOR],
          ['MCNO', row.MCNO],
          ['MC Dia', row.MC_DIA],
          ['Customer', row.CUSTOMER],
          ['FGSM', row.FINISH_GSM],
          ['F. DIA', row.FINISH_DIA],
          ['O/T', row.OPEN_TUBE],
          ['SL/VDQ', row.SL_VDQ],
          ['GGSM', row.GRAY_GSM],
            ['Buyer', row.BUYER],
          ['Y. TYPE', row.YARN_TYPE, , 'vertical'],
          ['Y. COUNT', row.YARN_COUNT, 'vertical'],
          ['F. TYPE', row.FABRICS_TYPE, 'vertical'],
          ['F. PLAN', row.FEEDER_PLAN, 'vertical']
        ].map(function(f) {
            var val = (f[1] === null || f[1] === undefined) ? '' : f[1];
          return '<div class="pdf-item' + (f[2] ? ' pdf-' + f[2] : '') + '"><span class="pdf-label">' + f[0] + ':</span> <span class="pdf-value">' + escHtml(val) + '</span></div>';
        }).join('');

        var content = '' +
          '<div id="rowPdfCard" style="width:700px;height:700px;padding:4px;background: white;font-family:Arial,Helvetica,sans-serif;color:#000000;box-sizing:border-box;border:2px solid #000000;font-weight:800;display:flex;flex-direction:column;">' +
          '<div style="display:flex;gap:4px;align-items:stretch;margin-bottom:4px;">' +
          '<div style="flex:1;min-height:215px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;background:#ffffff;border:2px solid #000000;padding:6px;box-sizing:border-box;">' +
          '<div style="background:white;color:#000000;text-align:center;font-size:27px;font-weight:800;padding:2px 0;margin-bottom:8px;letter-spacing:0;white-space:nowrap;display:inline-block;">' +
          '<span style="text-decoration:none;">PURBANI FABRICS LTD.</span>' +
          '<span style="display:block;width:100%;height:3px;background:#000000;margin-top:2px;"></span>' +
          '</div>' +
          '<div style="font-weight:800;color:#000000;line-height:1.5;word-break:break-word;">' +
          '<div style="font-size:27px;">ROLL: ' + escHtml(roll) + '</div>' +
          '<div style="font-size:26px;">QTY: ' + escHtml(row.PQTY || '') + '</div>' +
          '<div style="font-size:24px;">PO NO: ' + escHtml(row.PO_NUMBER || '') + '</div>' +
          '<div style="font-size:23px;">Date: ' + escHtml(row.BUDAT || '') + '</div>' +
          '</div>' +
          '</div>' +
          '<div id="rowQrBoxRight" style="flex:none;width:215px;height:215px;display:flex;align-items:center;justify-content:center;border:2px solid #000000;background:#ffffff;"></div>' +
          '</div>' +
            '<div class="pdf-grid">' + fieldHTML + '</div>' +
            '</div>';

        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';
        tempDiv.style.top = '0';
        document.body.appendChild(tempDiv);

        var qrBox = tempDiv.querySelector('#rowQrBoxRight');
        if (qrBox && typeof QRCode !== 'undefined') {
          new QRCode(qrBox, {
            text: String(roll),
            width: 208,
            height: 208,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
          });
        }

        var style = document.createElement('style');
        style.textContent = '' +
          '.pdf-grid{display:grid;grid-template-columns:repeat(4,1fr);column-gap:8px;row-gap:2px;background:#ffffff;}' +
          '.pdf-item{grid-column:span 2;font-size:25px;font-weight:800;line-height:1.15;margin-left:3px; padding:3px 0;word-break:break-word;background:#ffffff;color:#000000;}' +
          '.pdf-item.pdf-vertical{grid-column:1 / -1;}' +
          '.pdf-label,.pdf-value{font-weight:800;color:#000000;}';
        document.body.appendChild(style);

        // Render to canvas first -> guarantees identical look with the report PDF (colors included)
        setTimeout(function() {
          html2canvas(tempDiv, {
            scale: 3,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false
          }).then(function(canvas) {
            var imgData = canvas.toDataURL('image/png');
            document.body.removeChild(tempDiv);
            document.body.removeChild(style);

            var area = document.getElementById('knitCardPrintArea');
            area.innerHTML = '<img src="' + imgData + '" alt="Knit Card" />';

            setTimeout(function() {
              window.print();
              setTimeout(function() {
                window.location.reload();
              }, 300);
            }, 200);
          }).catch(function(err) {
            console.error('Render error:', err);
            alert('Error preparing print. Please try again.');
            document.body.removeChild(tempDiv);
          });
        }, 400);
      }

      function startScanner() {
        startNewScanner();
      }

      async function startNewScanner() {
        const previousScanner = html5QrCode;
        html5QrCode = null;

        if (previousScanner) {
          try {
            await previousScanner.stop();
            previousScanner.clear();
          } catch (e) {}
        }

        const qrReaderElement = document.getElementById('qr-reader');
        qrReaderElement.innerHTML = '';

        html5QrCode = new Html5Qrcode("qr-reader");

        const config = {
          fps: 30,
          qrbox: function (vw, vh) {
            const edge = Math.min(240, Math.floor(vw * 0.7));
            return { width: edge, height: edge };
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
              <span class="label">ðŸ’¡ Tip:</span>
              <span class="value">Tap restart or grant permissions</span>
            </div>
            <button class="rescan-btn" onclick="window.location.reload();">
              <i class="fas fa-redo"></i> Retry Camera
            </button>
          `;
        });
      }

      function isRollCode(text) {
        const val = String(text).trim();
        return /^\d+$/.test(val) || /^ROLL:\s*\d+$/i.test(val) ||
               (val.startsWith('{') && val.endsWith('}'));
      }

      function stopCameraAndProcess(text) {
        if (html5QrCode) {
          html5QrCode.stop().then(() => {
            isScanning = false;
            cameraStatus.innerText = 'Scan complete';
            cameraStatus.style.color = '#7dd3fc';
            scannerContainer.style.display = 'none';
            cameraControls.style.display = 'none';
            renderScannedData(text);
          }).catch(err => {
            console.warn('Failed to stop scanner:', err);
            renderScannedData(text);
          });
        } else {
          renderScannedData(text);
        }
      }

      function onScanSuccess(decodedText, decodedResult) {
        const val = String(decodedText).trim();
        if (!val || verifying || processingRoll) return;

        console.log('QR Scanned:', val);
        scanCount++;
        if (navigator.vibrate) navigator.vibrate(20);

        if (isRollCode(val)) {
          if (!operatorScanned) {
            alert('Please scan Knitting Operator ID first!\nProduction roll cannot be scanned before operator.');
            return;
          }
          processingRoll = true;
          stopCameraAndProcess(val);
          return;
        }

        if (operatorScanned) {
          if (val === (operatorInfo && operatorInfo.OPERATOR_ID)) {
            return;
          }
          processingRoll = true;
          stopCameraAndProcess(val);
          return;
        }

        verifying = true;
        verifyOperatorScan(val);
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
        cameraStatus.innerText = 'Restarting...';
        startNewScanner();
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
