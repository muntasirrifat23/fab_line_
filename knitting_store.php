<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$isStoreZone = isset($knittingStoreMode) && $knittingStoreMode === 'zone';
unset($_SESSION['knitting_store_manual_entry_authorized']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $isStoreZone ? 'Knitting Store Common Zone' : 'Knitting | Store'; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <?php if ($isStoreZone): ?>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php endif; ?>
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
      align-items: flex-start;
      padding: 16px;
    }

    .data-row.default-row.status-row {
      display: flex !important;
      justify-content: space-between;
      align-items: center;
      grid-template-columns: none !important;
      gap: 10px;
    }

    .data-row.default-row.status-row .label {
      min-width: auto;
      white-space: nowrap;
    }

    .data-row.default-row.status-row .value {
      flex: 1;
      text-align: right;
      margin-left: 10px;
    }

    .card {
      max-width: 1200px;
      width: 100%;
      margin: 0 auto;
      background: #eef5f3;
      border-radius: 40px;
      padding: 24px 24px 30px;
      box-shadow: 0 20px 45px rgba(10, 60, 55, 0.3);
      border: 1px solid #b5d8d2;
      transition: 0.2s;
    }

    .back-btn {
      background: #000000 !important;
      color: #ffffff !important;
      border: 2px solid #000000 !important;
      border-radius: 10px;
      padding: 12px 24px;
      cursor: pointer;
      transition: all .2s ease;
      font-weight: bold;
      font-size: 1rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .back-btn:hover {
      background: #ffffff !important;
      border-color: #000000 !important;
      color: #000000 !important;
      transform: translateY(-2px);
    }

    .knitting-page-header {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
      align-items: center;
      gap: 12px;
      margin-bottom: 14px;
      padding: 12px 18px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
      color: #fff;
      font-family: "Segoe UI", Arial, sans-serif;
    }

    .knitting-page-header.store-page-header {
      background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    }

    .knitting-page-header h1 {
      grid-column: 2;
      grid-row: 1;
      margin: 0;
      color: #fff;
      text-align: center;
      font-size: clamp(1.2rem, 3vw, 1.5rem);
      font-weight: 800;
      white-space: nowrap;
    }

    .knitting-header-back {
      grid-column: 1;
      grid-row: 1;
      justify-self: start;
      display: inline-flex;
      align-items: center;
      padding: 9px 14px;
      border: 2px solid #000;
      border-radius: 8px;
      background: #000;
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      font-size: 1rem;
      white-space: nowrap;
    }

    .knitting-header-refresh {
      grid-column: 3;
      grid-row: 1;
      justify-self: end;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      min-height: 40px;
      padding: 8px 12px;
      border: 0;
      border-radius: 8px;
      background: #087f70;
      color: #fff;
      font-size: 0.85rem;
      font-weight: 800;
      white-space: nowrap;
      cursor: pointer;
    }

    .knitting-header-back:hover {
      background: #fff;
      color: #000;
      text-decoration: none;
    }

    .knitting-header-refresh:hover {
      filter: brightness(0.94);
    }

    @media (max-width: 800px) {
      .knitting-page-header {
        gap: 8px;
        padding: 10px;
      }

      .knitting-page-header h1 {
        font-size: 1.1rem;
      }

      .knitting-header-back {
        padding: 8px 10px;
      }
    }

    .camera-action-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      background: #0f7a6f;
      color: #ffffff;
      border: 1px solid #0b5c53;
      border-radius: 8px;
      padding: 7px 12px;
      font-size: 0.78rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s ease, transform 0.1s ease;
      white-space: nowrap;
    }

    .camera-action-btn:hover {
      background: #0b5c53;
    }

    .camera-action-btn:active {
      transform: scale(0.97);
    }

    .scanner-container {
      position: relative;
      width: 100%;
      max-width: min(100%, 54vh, 560px);
      max-height: 54vh;
      aspect-ratio: 1 / 1;
      margin-left: auto;
      margin-right: auto;
      background: #d9ede9;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px #b5d8d2, 0 8px 20px rgba(10, 60, 55, 0.18);
      margin-bottom: 0;
    }

    @supports (height: 100dvh) {
      .scanner-container {
        max-width: min(100%, 54dvh, 560px);
        max-height: 54dvh;
      }
    }

    #qr-reader {
      width: 100%;
      height: 100%;
      padding: 0 !important;
      background: #e6f4f1;
      overflow: hidden;
    }

    #qr-reader > div {
      width: 100% !important;
      height: 100% !important;
    }

    #qr-reader video {
      border-radius: 28px;
      width: 100% !important;
      height: 100% !important;
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
      justify-content: center;
      align-items: center;
      flex-wrap: nowrap;
      width: min(100%, 520px);
      gap: 8px;
      margin: 10px auto 0;
      padding: 0 6px;
    }

    .camera-controls .camera-action-btn {
      flex: 0 0 auto;
      min-height: 44px;
      padding: 10px 16px;
      border: 0;
      background: #087f70;
      font-size: 0.98rem;
      font-weight: 800;
    }

    .compact-entry-container {
      width: min(100%, 460px);
      margin: 12px auto 16px;
    }

    .compact-entry-container label {
      display: block;
      margin-bottom: 6px;
      color: #123b39;
      font-size: 1rem;
      font-weight: 700;
    }

    .compact-entry-container .manual-entry {
      display: flex;
      flex-wrap: nowrap;
      gap: 0;
      width: 100%;
      margin-top: 0;
    }

    #toggle-camera-btn {
      order: -1;
    }

    .manual-unlock-controls,
    .manual-password-row {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .manual-password-row {
      flex-wrap: nowrap;
    }

    .manual-unlock-controls {
      justify-self: center;
    }

    .manual-password-row input {
      width: 190px;
      min-width: 0;
      min-height: 42px;
      padding: 9px 10px;
      border: 1px solid #afc8c4;
      border-radius: 8px;
      font-size: 1rem;
    }

    .manual-password-row button {
      min-height: 44px;
    }

    .compact-entry-container .manual-entry input {
      flex: 1 1 0;
      min-height: 46px;
      padding: 10px 12px;
      border: 1px solid #afc8c4;
      border-radius: 8px 0 0 8px;
      font-size: 1rem;
    }

    .compact-entry-container .manual-entry button {
      flex: 0 0 auto;
      min-height: 44px;
      padding: 10px 16px;
      border: 0;
      border-radius: 0 8px 8px 0;
      background: #087f70;
      font-size: 0.98rem;
      font-weight: 800;
    }

    .compact-entry-container .manual-entry button:disabled {
      background: #94a3b8;
      opacity: 1;
    }

    .camera-controls .camera-action-btn:hover,
    .compact-entry-container .manual-entry button:hover {
      filter: brightness(0.94);
    }

    .compact-entry-container .manual-entry input:focus,
    .camera-controls .manual-password-row input:focus {
      outline: 2px solid #54b7a8;
      border-color: #159685;
      box-shadow: none;
    }

    .status-badge {
      background: #dcebe8;
      padding: 8px 18px;
      border-radius: 100px;
      color: #083a36;
      font-size: 0.85rem;
      font-weight: 600;
      display: none;
      align-items: center;
      gap: 8px;
      border: 1px solid #9fc8c1;
    }

    .status-badge i {
      color: #0f7a6f;
      font-size: 0.9rem;
    }

    .result-panel {
      background: #e2efec;
      border-radius: 28px;
      padding: 18px 20px 16px;
      margin-top: 20px;
      border: 1px solid #a9cdc6;
      box-shadow: inset 0 2px 6px rgba(10, 60, 55, 0.1);
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

    .scan-preview-summary {
      margin: 14px 0 8px;
      color: #083a36;
      font-weight: 700;
    }

    .scan-preview-wrap {
      width: 100%;
      overflow: auto;
      border: 1px solid #cbd5e1;
      border-radius: 12px;
      background: #ffffff;
    }

    .scan-preview-table {
      width: max-content;
      min-width: 100%;
      border-collapse: collapse;
      color: #1e293b;
      font-size: 0.78rem;
    }

    .scan-preview-table th {
      position: sticky;
      top: 0;
      z-index: 1;
      padding: 10px 12px;
      background: #1e293b;
      color: #ffffff;
      text-align: left;
      white-space: nowrap;
      border-right: 1px solid #475569;
    }

    .scan-preview-table td {
      padding: 10px 12px;
      border-bottom: 1px solid #e2e8f0;
      white-space: nowrap;
      border-right: 1px solid #cbd5e1;
    }

    .scan-preview-table th:last-child,
    .scan-preview-table td:last-child {
      border-right: 0;
    }

    .scan-preview-table tbody tr:nth-child(even) {
      background: #f1f5f9;
    }

    .scan-preview-table tbody tr:hover {
      background: #e0f2fe;
    }

    .scan-preview-table .remove-roll-btn {
      margin: 0;
      padding: 6px 10px;
      color: #ffffff;
      background: #dc2626;
      border: 1px solid #b91c1c;
    }

    .scan-preview-table .remove-roll-btn:hover {
      color: #ffffff;
      background: #b91c1c;
      border-color: #991b1b;
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

    @media (min-width: 1200px) {
      .data-row.default-row {
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 12px;
      }
    }

    .field-block {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-height: auto;
    }

    .field-block .field-label {
      font-size: 0.78rem;
      color: #0b4f47;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      opacity: 1;
    }

    .field-block .field-value {
      font-size: 1.05rem;
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

    .rack-selection-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
      align-items: start;
    }

    .rack-selection-field {
      min-width: 0;
    }

    .rack-selection-field .rack-section-title {
      min-height: 34px;
      margin-bottom: 8px;
      padding-bottom: 6px;
      font-size: 0.82rem;
    }

    .rack-group {
      position: relative;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 0;
    }

    .rack-selection-grid .sub-rack-group {
      min-width: 0;
      margin-top: 0;
      min-height: 46px;
      padding: 8px;
      gap: 6px;
      align-items: center;
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
      position: absolute;
      z-index: 20;
      top: calc(100% + 4px);
      left: 0;
      width: 100%;
      max-height: 180px;
      overflow-y: auto;
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

    @media (min-width: 768px) and (max-width: 1199.98px) {
      .card {
        max-width: 1200px;
        border-radius: 30px;
        padding: 22px;
      }

      .data-row.default-row {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
      }
    }

    @media (max-width: 767.98px) {
      body {
        padding: 10px;
      }

      .card {
        max-width: 100%;
        border-radius: 24px;
        padding: 18px 14px 20px;
      }

      .scanner-container {
        margin-bottom: 0;
      }

      .result-panel {
        padding: 14px;
        border-radius: 22px;
      }

      .data-row.default-row {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 10px;
      }

      .rack-group .btn,
      .rack-number-input {
        padding: 11px 12px;
      }

      .rack-actions {
        flex-wrap: wrap;
      }

      .rack-selection-grid {
        grid-template-columns: 1fr;
      }
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

    .manual-entry input[readonly] {
      background: #e2e8f0;
      cursor: not-allowed;
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

    .manual-entry button:disabled {
      opacity: .6;
      cursor: not-allowed;
    }

    .manual-entry button.manual-locked-button {
      opacity: .7;
    }

    @media(max-width:480px) {
      .camera-controls {
        flex-wrap: wrap;
        gap: 8px;
      }

      .manual-password-row input {
        width: 190px;
      }

      body {
        padding: 6px;
      }

      .card {
        padding: 14px 10px 16px;
        border-radius: 18px;
      }

      .scanner-container {
        border-radius: 20px;
      }

      #qr-reader video,
      .scan-overlay {
        border-radius: 20px;
      }

      .result-panel {
        padding: 12px 10px;
        border-radius: 18px;
      }

      .data-row.default-row {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px;
        padding: 8px 10px;
      }

      .field-block {
        gap: 4px;
      }

      .field-block .field-label {
        font-size: 0.64rem;
      }

      .field-block .field-value {
        font-size: 0.86rem;
      }

      .sub-rack-group {
        min-height: 44px;
      }

      .rack-actions {
        gap: 8px;
      }

      .btn-action {
        min-width: 0;
        flex-basis: calc(50% - 4px);
        padding: 11px 8px;
      }

      .manual-entry {
        flex-wrap: wrap;
      }

      .manual-entry input {
        flex-basis: 100%;
      }

      .manual-entry button {
        flex: 1;
      }
    }

    .card.store-inventory-card {
      max-width: none;
      width: 100%;
      height: 100vh;
      min-height: 0;
      padding: 12px 16px;
      border: 0;
      border-radius: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    body.store-inventory-body {
      height: 100vh;
      padding: 0;
      overflow: hidden;
    }

    .store-page-header.inventory-page-header {
      flex: 0 0 auto;
      grid-template-columns: auto 1fr;
      margin-bottom: 8px;
      padding: 8px 12px;
      border: 0;
      border-radius: 8px;
    }

    .store-page-header.inventory-page-header h1 {
      grid-column: 2;
    }

    .store-inventory {
      display: flex;
      flex-direction: column;
      flex: 1;
      min-height: 0;
      gap: 6px;
      width: 100%;
      overflow: hidden;
    }

    .store-inventory-controls {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      flex: 0 0 auto;
      gap: 12px;
      align-items: start;
    }

    .store-search-panel {
      display: flex;
      min-width: 0;
      flex-direction: column;
      gap: 6px;
    }

    .store-search-row {
      display: flex;
      flex-direction: column;
      gap: 8px;
      width: 100%;
    }

    .store-search-row input {
      width: 100%;
      min-width: 0;
      min-height: 46px;
      padding: 10px 14px;
      border: 1px solid #9dbbb5;
      border-radius: 9px;
      background: #fff;
      font-size: 1rem;
    }

    .store-search-actions {
      display: flex;
      gap: 8px;
    }

    .store-search-actions button,
    .store-rack-panel button {
      min-height: 44px;
      padding: 10px 18px;
      border: 0;
      border-radius: 9px;
      background: #087f70;
      color: #fff;
      font-size: 0.95rem;
      font-weight: 800;
      cursor: pointer;
    }

    .store-search-actions .store-clear-button {
      background: #526b68;
    }

    @media (max-width: 900px) {
      .store-inventory-controls {
        grid-template-columns: minmax(0, 1fr);
      }

      .store-search-row {
        flex-direction: row;
        align-items: stretch;
        gap: 8px;
      }

      .store-search-row input {
        flex: 0 0 50%;
        width: 50%;
      }

      .store-search-actions {
        flex: 1;
        min-width: 0;
      }

      .store-search-actions button {
        flex: 1;
        min-width: 0;
        padding: 8px;
        white-space: nowrap;
      }
    }

    .store-rack-panel button:disabled {
      opacity: 0.55;
      cursor: not-allowed;
    }

    .store-rack-panel {
      display: grid;
      grid-template-columns: minmax(135px, 0.8fr) minmax(170px, 1.2fr);
      align-items: start;
      gap: 10px;
      min-width: 0;
      padding: 0;
      border: 0;
      border-radius: 0;
      background: transparent;
    }

    .store-rack-primary {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 0;
    }

    .store-rack-field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 0;
    }

    .store-rack-field label,
    .store-rack-label {
      color: #174e46;
      font-weight: 700;
      font-size: 0.9rem;
    }

    .store-rack-field select {
      width: 100%;
      min-height: 44px;
      padding: 8px 10px;
      border: 1px solid #9dbbb5;
      border-radius: 8px;
      background: #fff;
      font-size: 1rem;
    }

    .store-rack-locations {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 5px;
    }

    .store-rack-panel .store-rack-location-button {
      min-height: 34px;
      padding: 5px 8px;
      border: 1px solid #b5d8d2;
      background: #fff;
      color: #174e46;
      font-size: 0.9rem;
    }

    .store-rack-panel .store-rack-location-button.active {
      border-color: #087f70;
      background: #087f70;
      color: #fff;
    }

    .store-list-message {
      min-height: 18px;
      color: #28504a;
      font-weight: 600;
    }

    .store-list-message.error {
      color: #b42318;
    }

    .store-table-wrap {
      width: 100%;
      flex: 1;
      min-height: 0;
      overflow: auto;
      border: 0;
      border-top: 1px solid #b5d8d2;
      border-radius: 0;
      background: #fff;
    }

    .store-list-table {
      width: 100%;
      min-width: 1600px;
      border-collapse: separate;
      border-spacing: 0;
      color: #173b37;
      font-size: 0.9rem;
    }

    .store-list-table th,
    .store-list-table td {
      padding: 6px 8px;
      border-bottom: 1px solid #e1ece9;
      text-align: left;
      white-space: nowrap;
    }

    .store-list-table th {
      position: sticky;
      top: 0;
      z-index: 1;
      background: #c2ded7;
      color: #164b42;
      font-weight: 800;
    }

    .store-list-table th:first-child,
    .store-list-table td:first-child {
      position: sticky;
      left: 0;
      z-index: 2;
      width: 48px;
      text-align: center;
      background: #f4faf8;
    }

    .store-list-table th:first-child {
      z-index: 3;
      background: #c2ded7;
    }

    .store-list-table input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: #087f70;
      cursor: pointer;
    }

    .store-list-table input[type="checkbox"]:disabled {
      cursor: not-allowed;
    }

    @media (max-width: 640px) {
      body.store-inventory-body {
        padding: 0;
      }

      .card.store-inventory-card {
        height: 100vh;
        padding: 8px 10px;
      }

      .store-rack-panel {
        grid-template-columns: minmax(130px, 0.8fr) minmax(160px, 1.2fr);
      }

      .store-rack-primary .store-save-button {
        width: 100%;
      }
    }

    @media (max-width: 420px) {
      .store-rack-panel {
        grid-template-columns: minmax(0, 1fr);
      }
    }
  </style>
</head>

<body class="<?php echo $isStoreZone ? '' : 'store-inventory-body'; ?>">
  <div class="card<?php echo $isStoreZone ? '' : ' store-inventory-card'; ?>">
    <div class="knitting-page-header store-page-header<?php echo $isStoreZone ? '' : ' inventory-page-header'; ?>">
      <a class="knitting-header-back" href="initialPage.php">← Back</a>
      <h1><?php echo $isStoreZone ? 'Knitting Store Common Zone' : 'Knitting Store'; ?></h1>
      <?php if ($isStoreZone): ?>
        <button type="button" class="knitting-header-refresh" id="pageRefreshBtn" title="Reload the full page">
          <i class="fa-solid fa-rotate-right"></i> Refresh
        </button>
      <?php endif; ?>
    </div>

    <?php if (!$isStoreZone): ?>
      <section class="store-inventory" id="storeInventory">
        <div class="store-inventory-controls">
          <div class="store-search-panel">
            <div class="store-search-row">
              <input type="search" id="storeSearchInput" autocomplete="off"
                placeholder="Search SONO, PO Number, Buyer, Style, Color or Fabrics Type">
              <div class="store-search-actions">
                <button type="button" id="storeSearchButton"><i class="fas fa-search"></i> Search</button>
                <button type="button" class="store-clear-button" id="storeClearSearchButton">Clear</button>
              </div>
            </div>
            <div class="store-list-message" id="storeListMessage" role="status" aria-live="polite"></div>
          </div>
          <div class="store-rack-panel" id="storeRackPanel">
            <div class="store-rack-primary">
              <div class="store-rack-field">
                <select id="storeRackNumber" disabled>
                  <option value="">Select Rack No</option>
                  <?php for ($rackNumber = 1; $rackNumber <= 50; $rackNumber++): ?>
                    <option value="<?php echo str_pad((string) $rackNumber, 2, '0', STR_PAD_LEFT); ?>">
                      <?php echo str_pad((string) $rackNumber, 2, '0', STR_PAD_LEFT); ?>
                    </option>
                  <?php endfor; ?>
                </select>
              </div>
              <button type="button" class="store-save-button" id="storeSaveButton" disabled>
                <i class="fas fa-save"></i> Store Selected Rolls
              </button>
            </div>
            <div class="store-rack-field">
              <div class="store-rack-locations" id="storeRackLocations" role="group" aria-label="Rack Location">
                <?php foreach (['A3', 'B3', 'C3', 'A2', 'B2', 'C2', 'A1', 'B1', 'C1'] as $rackLocation): ?>
                  <button type="button" class="store-rack-location-button" data-location="<?php echo $rackLocation; ?>"
                    aria-pressed="false" disabled><?php echo $rackLocation; ?></button>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
        <div class="store-table-wrap">
          <table class="store-list-table" aria-label="Knitting Store Common Zone rolls">
            <thead>
              <tr>
                <th>Select</th>
                <th>Roll</th>
                <th>PO Number</th>
                <th>Color</th>
                <th>QTY</th>
                <th>MCNO</th>
                <th>SONO</th>
                <th>Buyer</th>
                <th>Style</th>
                <th>MC Dia</th>
                <th>Customer</th>
                <th>O / T</th>
                <th>SL/VQ</th>
                <th>Fabrics Type</th>
                <th>Finish GSM</th>
                <th>Finish Dia</th>
                <th>Yarn Type</th>
                <th>Yarn Count</th>
                <th>Yarn Brand</th>
              </tr>
            </thead>
            <tbody id="storeRows">
              <tr><td colspan="19">Loading Knitting Store Common Zone rolls...</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($isStoreZone): ?>
    <div class="scanner-container" id="scannerContainer">
      <div id="qr-reader"></div>
      <div class="scan-overlay"></div>
    </div>

    <div class="camera-controls" id="cameraControls">
      <div class="status-badge">
        <i class="fas fa-video"></i>
        <span id="camera-status">Ready</span>
      </div>
      <div class="manual-unlock-controls">
        <div class="manual-password-row">
          <input type="password" id="manualPassword" autocomplete="off" placeholder="Pass For Entry" aria-label="Password for manual entry">
          <button type="button" class="camera-action-btn" id="unlockManualButton">Unlock</button>
        </div>
      </div>
      <button type="button" class="camera-action-btn" id="toggle-camera-btn" title="Switch Front/Back camera">
        Switch Camera
      </button>
    </div>

    <div class="compact-entry-container" id="manualEntryContainer">
      <label for="manualRollInput">Roll Number</label>
      <div class="manual-entry">
        <input type="text" id="manualRollInput" placeholder="Unlock to enter manually" autocomplete="off" readonly>
        <button type="button" id="manualRollBtn">Add Roll</button>
      </div>
    </div>

    <div class="result-panel" id="resultPanel" style="display:none;">
      <div class="result-header">
        <i class="fas fa-qrcode"></i>
        <span>Scanned Roll Data</span>
        <span style="margin-left: auto; font-size: 0.7rem; background: #14b8a6; padding: 2px 12px; border-radius: 40px; color: #ffffff;">Ready</span>
      </div>
      <div id="result-content">
        <!-- Default content will be injected by JS -->
      </div>
      <div id="rack-section" class="rack-section" style="display:none;">
        <div class="rack-selection-grid" <?php echo $isStoreZone ? 'style="display:none;"' : ''; ?>>
          <div class="rack-selection-field">
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
          </div>
          <div class="rack-selection-field">
            <div class="rack-section-title">
              <i class="fas fa-warehouse"></i>
              <span>Select Rack Location</span>
            </div>
            <div class="sub-rack-group" id="subRackGroup">
              <span class="sub-placeholder">Select a rack location</span>
            </div>
          </div>
          <div class="rack-selection-field">
            <div class="rack-section-title">
              <i class="fas fa-warehouse"></i>
              <span>Select Rack Section</span>
            </div>
            <div class="sub-rack-group" id="subRackOptionsGroup">
              <span class="sub-placeholder">Select A, B or C</span>
            </div>
          </div>
        </div>
        <div class="rack-actions">
          <button class="btn-action save" type="button" id="saveRackBtn" disabled>
            <i class="fas fa-save"></i> <?php echo $isStoreZone ? 'Store Zone' : 'Save Rack'; ?>
          </button>
          <button class="btn-action reset" type="button" id="resetRackBtn">
            <i class="fas fa-undo"></i> Reset
          </button>
        </div>
      </div>
      <div id="message"></div>
    </div>
    <?php endif; ?>

  </div>

  <?php if (!$isStoreZone): ?>
  <script>
    (function() {
      "use strict";

      const searchInput = document.getElementById('storeSearchInput');
      const searchButton = document.getElementById('storeSearchButton');
      const clearSearchButton = document.getElementById('storeClearSearchButton');
      const rackNumber = document.getElementById('storeRackNumber');
      const rackLocationButtons = document.querySelectorAll('.store-rack-location-button');
      const saveButton = document.getElementById('storeSaveButton');
      const rowsContainer = document.getElementById('storeRows');
      const message = document.getElementById('storeListMessage');
      const selectedRolls = new Map();
      let currentRows = [];
      let requestNumber = 0;
      let isSaving = false;
      let selectedRackLocation = '';

      const columns = [
        ['ROLL', 'ROLL'], ['PO_NUMBER', 'PO_NUMBER'], ['COLOR', 'COLOR'], ['QTY', 'QTY'],
        ['MCNO', 'MCNO'], ['SONO', 'SONO'], ['BUYER', 'BUYER'], ['STYLE', 'STYLE'],
        ['MCDIA', 'MCDIA'], ['CUSTOMER', 'CUSTOMER'], ['O_T', 'O_T'], ['SL', 'SL'],
        ['FTYPE', 'FTYPE'], ['FGSM', 'FGSM'], ['FDIA', 'FDIA'], ['YTYPE', 'YTYPE'],
        ['YCOUNT', 'YCOUNT'], ['YBRAND', 'YBRAND']
      ];

      function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function(char) {
          return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
          }[char];
        });
      }

      function showMessage(text, isError) {
        message.textContent = text || '';
        message.classList.toggle('error', Boolean(isError));
      }

      function updateSelectionState() {
        if (!selectedRolls.size) {
          rackNumber.value = '';
          selectedRackLocation = '';
        }
        const hasSelection = selectedRolls.size > 0;
        rackNumber.disabled = !hasSelection || isSaving;
        rackLocationButtons.forEach(function(button) {
          button.disabled = !hasSelection || isSaving;
          button.classList.toggle('active', button.dataset.location === selectedRackLocation);
          button.setAttribute('aria-pressed', button.dataset.location === selectedRackLocation ? 'true' : 'false');
        });
        saveButton.disabled = !hasSelection || isSaving || !rackNumber.value || !selectedRackLocation;
        rowsContainer.querySelectorAll('.store-row-checkbox').forEach(function(checkbox) {
          checkbox.disabled = isSaving;
        });
      }

      function renderRows(rows) {
        currentRows = rows;
        if (!rows.length) {
          rowsContainer.innerHTML = '<tr><td colspan="19">No Knitting Store Common Zone rolls found.</td></tr>';
          updateSelectionState();
          return;
        }

        rowsContainer.innerHTML = rows.map(function(row) {
          const roll = String(row.ROLL || '');
          const cells = columns.map(function(column) {
            const value = row[column[0]];
            return '<td>' + escapeHtml(value === null || value === undefined || value === '' ? '-' : value) + '</td>';
          }).join('');
          return '<tr><td><input type="checkbox" class="store-row-checkbox" data-roll="' + escapeHtml(roll) + '"' +
            (selectedRolls.has(roll) ? ' checked' : '') +
            ' aria-label="Select roll ' + escapeHtml(roll) + '"></td>' + cells + '</tr>';
        }).join('');

        rowsContainer.querySelectorAll('.store-row-checkbox').forEach(function(checkbox) {
          checkbox.addEventListener('change', function() {
            const row = currentRows.find(function(item) {
              return String(item.ROLL) === checkbox.dataset.roll;
            });
            if (!row) return;
            if (checkbox.checked) selectedRolls.set(String(row.ROLL), row);
            else selectedRolls.delete(String(row.ROLL));
            updateSelectionState();
          });
        });
        updateSelectionState();
      }

      function loadRows() {
        if (isSaving) return;
        const thisRequest = ++requestNumber;
        searchButton.disabled = true;
        clearSearchButton.disabled = true;
        showMessage('Loading Knitting Store Common Zone rolls...');
        fetch('ajaxKnitting_store.php?action=available_rolls&search=' + encodeURIComponent(searchInput.value.trim()))
          .then(function(response) {
            return response.json().then(function(data) {
              if (!response.ok || !data.success) {
                throw new Error(data.error || data.message || 'Unable to load Knitting Store Common Zone rolls.');
              }
              return data;
            });
          })
          .then(function(data) {
            if (thisRequest !== requestNumber) return;
            renderRows(Array.isArray(data.data) ? data.data : []);
            showMessage(data.data.length + ' roll(s) found.');
          })
          .catch(function(error) {
            if (thisRequest !== requestNumber) return;
            rowsContainer.innerHTML = '<tr><td colspan="19">Could not load rolls.</td></tr>';
            showMessage(error.message || 'Unable to load Knitting Store Common Zone rolls.', true);
          })
          .finally(function() {
            if (thisRequest === requestNumber) {
              searchButton.disabled = isSaving;
              clearSearchButton.disabled = isSaving;
            }
          });
      }

      searchButton.addEventListener('click', loadRows);
      searchInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') loadRows();
      });
      clearSearchButton.addEventListener('click', function() {
        searchInput.value = '';
        loadRows();
      });

      rackNumber.addEventListener('change', updateSelectionState);
      rackLocationButtons.forEach(function(button) {
        button.addEventListener('click', function() {
          if (button.disabled) return;
          selectedRackLocation = button.dataset.location;
          updateSelectionState();
        });
      });

      saveButton.addEventListener('click', function() {
        if (isSaving) return;
        const items = Array.from(selectedRolls.values());
        if (!items.length || !rackNumber.value || !selectedRackLocation) return;

        isSaving = true;
        searchButton.disabled = true;
        clearSearchButton.disabled = true;
        updateSelectionState();
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        showMessage('Saving selected rolls to Knitting Store...');
        fetch('ajaxKnittingStore_Insert.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            items: items,
            RACKNO: rackNumber.value,
            RACKLOCATION: selectedRackLocation
          })
        })
          .then(function(response) {
            return response.json().then(function(data) {
              if (!response.ok || !data.success) {
                throw new Error(data.error || data.message || 'Unable to store selected rolls.');
              }
              return data;
            });
          })
          .then(function(data) {
            items.forEach(function(row) { selectedRolls.delete(String(row.ROLL)); });
            rackNumber.value = '';
            selectedRackLocation = '';
            showMessage(data.message || data.stored + ' roll(s) stored successfully.');
            renderRows(currentRows);
          })
          .catch(function(error) {
            showMessage(error.message || 'Unable to store selected rolls.', true);
          })
          .finally(function() {
            isSaving = false;
            saveButton.innerHTML = '<i class="fas fa-save"></i> Store Selected Rolls';
            updateSelectionState();
            searchButton.disabled = false;
            clearSearchButton.disabled = false;
          });
      });

      loadRows();
    })();
  </script>
  <?php endif; ?>

  <?php if ($isStoreZone): ?>
  <script>
    (function() {
      "use strict";

      const isZoneMode = <?php echo $isStoreZone ? 'true' : 'false'; ?>;
      const resultContainer = document.getElementById('result-content');
      const resultPanel = document.getElementById('resultPanel');
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
      const manualPassword = document.getElementById('manualPassword');
      const unlockManualButton = document.getElementById('unlockManualButton');
      let currentFacingMode = 'environment';
      let manualEntryAuthorized = false;
      let isCameraTransitioning = false;

      const PREVIEW_COLUMNS = [
        { key: 'BUDAT', label: 'Date' },
        { key: 'ROLL', label: 'Roll No' },
        { key: 'PO_NUMBER', label: 'PO NO' },
        { key: 'QTY', label: 'QTY' },
        { key: 'COLOR', label: 'Color' },
        { key: 'SONO', label: 'SONO' },
        { key: 'BUYER', label: 'Buyer' },
        { key: 'STYLE', label: 'Style' },
        { key: 'MCNO', label: 'Machine No' },
        { key: 'MCDIA', label: 'MC Dia' },
        { key: 'CUSTOMER', label: 'Customer' },
        { key: 'YTYPE', label: 'Yarn Type' },
        { key: 'YCOUNT', label: 'Yarn Count' },
        { key: 'YBRAND', label: 'Yarn Brand' },
        { key: 'O_T', label: 'Open / Tube' },
        { key: 'SL', label: 'SL/VQ' },
        { key: 'FTYPE', label: 'Fabrics Type' },
        { key: 'FGSM', label: 'Finish GSM' },
        { key: 'FDIA', label: 'Finish Dia' },
        { key: 'FEEDER_PLAN', label: 'Feeder Plan' },
        { key: 'LOT_NO', label: 'Lot No' },
        { key: 'CATEGORY', label: 'Category' }
      ];

      const MESSAGE_DURATION_MS = 3000;
      const PREVIEW_NOTICE_DURATION_MS = 2000;
      let scannedRolls = [];
      let pendingRolls = new Set();
      let rollRetryAfter = new Map();
      let rollDuplicateNoticeAfter = new Map();
      let messageTimeout = null;
      let messageModalId = 0;
      let selectedRack = null;
      let selectedRackSection = null;
      let selectedSubRack = null;
      let html5QrCode = null;
      let isScanning = false;
      let isSaving = false;
      let hasLoadedData = false;
      let cameraStartFailed = false;
      let resumeScannerAfterVisibility = false;
      let pageIsLeaving = false;
      if (document.hidden) {
        cameraStatus.innerText = 'Camera paused while page is hidden';
        resumeScannerAfterVisibility = true;
      }
      let lastInvalidQr = '';
      let lastInvalidQrAt = 0;

      function renderDefaultData() {
        if (hasLoadedData) return;
        resultPanel.style.display = 'none';
        hideRackSection();
        saveRackBtn.innerHTML = isZoneMode
          ? getStoreButtonLabel()
          : '<i class="fas fa-save"></i> Save Rack';
        resultContainer.innerHTML = `
          <div class="data-row header-row"><span class="label">Default Information</span><span class="value"></span></div>
          <div class="data-row default-row"><span class="label">Status</span><span class="value">Scan rolls</span></div>
        `;
      }

      function getStoreButtonLabel(count) {
        return isZoneMode
          ? '<i class="fas fa-save"></i> Store Zone'
          : '<i class="fas fa-save"></i> Store All Rolls (' + (count || 0) + ')';
      }

      function showMessage(msg, type) {
        if (messageTimeout) clearTimeout(messageTimeout);
        messageTimeout = null;
        messageContainer.replaceChildren();

        if (typeof Swal !== 'undefined') {
          if (Swal.isVisible()) Swal.close();
          const isPreviewNotice = type === 'success' || type === 'duplicate';
          const messageId = String(++messageModalId);
          const duration = type === 'duplicate' ? PREVIEW_NOTICE_DURATION_MS : MESSAGE_DURATION_MS;
          const icon = type === 'duplicate' ? 'error' : (type === 'error' || type === 'warning' || type === 'success' ? type : 'info');
          const alertOptions = {
            toast: isPreviewNotice,
            position: isPreviewNotice ? 'top-end' : 'center',
            icon: icon,
            title: msg,
            showConfirmButton: false,
            timer: duration,
            timerProgressBar: true,
            didOpen: function(popup) {
              popup.dataset.knittingStoreMessageId = messageId;
            }
          };
          if (!isPreviewNotice) {
            alertOptions.allowOutsideClick = false;
            alertOptions.allowEscapeKey = false;
          }
          Swal.fire(alertOptions);
          messageTimeout = setTimeout(function() {
            const popup = Swal.getPopup();
            if (popup && popup.dataset.knittingStoreMessageId === messageId) {
              Swal.close();
            }
            messageTimeout = null;
          }, duration);
          return;
        }

        const message = document.createElement('div');
        message.className = 'message-row ' + (type || '');
        message.textContent = msg;
        messageContainer.appendChild(message);
        const duration = type === 'duplicate' ? PREVIEW_NOTICE_DURATION_MS : MESSAGE_DURATION_MS;
        messageTimeout = setTimeout(function() {
          if (message.parentNode === messageContainer) {
            messageContainer.removeChild(message);
          }
          messageTimeout = null;
        }, duration);
      }

      function clearMessage() {
        if (messageTimeout) clearTimeout(messageTimeout);
        messageTimeout = null;
        messageModalId += 1;
        messageContainer.replaceChildren();
        if (typeof Swal !== 'undefined' && Swal.isVisible()) Swal.close();
      }

      function extractRollFromQR(qrText) {
        const text = String(qrText || '').trim();
        if (!text) return null;

        if (text.indexOf('|') !== -1) {
          return normalizeProductionRoll(text.split('|')[0]);
        }

        let m = text.match(/^ROLL:\s*(.+)$/i);
        if (m && m[1]) return normalizeProductionRoll(m[1]);

        if (text.charAt(0) === '{' && text.charAt(text.length - 1) === '}') {
          try {
            const payload = JSON.parse(text);
            return normalizeProductionRoll(payload.ROLL || payload.roll || '');
          } catch (err) {
            return null;
          }
        }

        return normalizeProductionRoll(text);
      }

      function normalizeProductionRoll(value) {
        const roll = String(value || '').trim();
        return /^\d+(?:-[RP])?$/i.test(roll) ? roll : null;
      }

      function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function(char) {
          return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
          }[char];
        });
      }

      function getPreviewValue(row, key) {
        const aliases = {
          QTY: ['QTY', 'UQTY'],
          MCDIA: ['MCDIA', 'MC_DIA'],
          FEEDER_PLAN: ['FEEDER_PLAN', 'FPLAN'],
          LOT_NO: ['LOT_NO', 'LOTNO']
        };
        const keys = aliases[key] || [key];
        for (let index = 0; index < keys.length; index += 1) {
          if (row[keys[index]] !== null && row[keys[index]] !== undefined && row[keys[index]] !== '') {
            return row[keys[index]];
          }
        }
        return '';
      }

      function renderScannedData(row) {
        if (row) {
          const rollKey = String(row.ROLL || '').trim().toUpperCase();
          if (!rollKey || scannedRolls.some(function(item) {
              return String(item.ROLL || '').trim().toUpperCase() === rollKey;
            })) {
            return false;
          }
          scannedRolls.push(row);
        }

        hasLoadedData = scannedRolls.length > 0;

        if (!hasLoadedData) {
          renderDefaultData();
          return true;
        }

        clearMessage();
        showRackSection();
        let html = `
          <div class="scan-preview-summary">
            ${scannedRolls.length} roll${scannedRolls.length === 1 ? '' : 's'} ${isZoneMode ? 'ready to store in Knitting Store Common Zone.' : 'ready — choose one rack location for all rolls.'}
          </div>
          <div class="scan-preview-wrap">
            <table class="scan-preview-table" aria-label="Scanned roll preview">
              <thead>
                <tr><th>Action</th>${PREVIEW_COLUMNS.map(function(column) {
                  return '<th>' + escapeHtml(column.label) + '</th>';
                }).join('')}</tr>
              </thead>
              <tbody>
        `;

        scannedRolls.forEach(function(info, rowIndex) {
          html += '<tr>';
          html += '<td><button type="button" class="rescan-btn remove-roll-btn" data-roll-index="' + rowIndex + '" title="Remove roll" aria-label="Remove roll"><i class="fas fa-trash" aria-hidden="true"></i></button></td>';
          PREVIEW_COLUMNS.forEach(function(column) {
            const value = getPreviewValue(info, column.key);
            html += '<td>' + escapeHtml(value === '' || value === null || value === undefined ? '-' : value) + '</td>';
          });
          html += '</tr>';
        });
        html += '</tbody></table></div>';
        resultContainer.innerHTML = html;
        resultContainer.querySelectorAll('.remove-roll-btn').forEach(function(button) {
          button.addEventListener('click', function() {
            const index = Number(button.getAttribute('data-roll-index'));
            scannedRolls.splice(index, 1);
            renderScannedData();
          });
        });
        saveRackBtn.innerHTML = getStoreButtonLabel(scannedRolls.length);
        if (isZoneMode) saveRackBtn.disabled = false;
        return true;
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

      function setActiveRack(rack) {
        selectedRack = rack;
        selectedRackSection = null;
        selectedSubRack = null;
        updateSubRacks(rack);
        if (hasLoadedData) renderScannedData();
      }

      function setActiveRackSection(section) {
        selectedRackSection = section;
        selectedSubRack = null;
        subRackGroup.querySelectorAll('.btn').forEach(function(btn) {
          btn.classList.toggle('active-sub', btn.textContent.trim().toUpperCase() === section);
        });
        updateSubRackOptions(section);
        if (hasLoadedData) renderScannedData();
      }

      function setActiveSubRack(sub) {
        selectedSubRack = sub;
        subRackOptionsGroup.querySelectorAll('.btn').forEach(function(btn) {
          btn.classList.toggle('active-sub', btn.textContent.trim().toUpperCase() === sub);
        });
        saveRackBtn.disabled = false;
        if (hasLoadedData) renderScannedData();
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
          };
          subRackOptionsGroup.appendChild(option);
        });
      }

      function updateRackNumberOptions(value) {
        const rackNumber = String(value || '').replace(/\D/g, '').slice(0, 2);
        rackNumberOptions.innerHTML = '';
        if (rackNumber.length !== 1) {
          rackNumberOptions.classList.remove('visible');
          return;
        }
        const prefix = rackNumber;

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
        if (event.key === 'Enter') {
          event.preventDefault();
          const currentIndex = options.findIndex(function(option) {
            return option.classList.contains('keyboard-active');
          });
          if (currentIndex >= 0) {
            options[currentIndex].click();
          } else {
            commitRackNumber();
          }
          rackNumberOptions.classList.remove('visible');
          return;
        }
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
        } else if (event.key === 'Escape') {
          rackNumberOptions.classList.remove('visible');
          return;
        } else {
          return;
        }

        options.forEach(function(option, index) {
          option.classList.toggle('keyboard-active', index === nextIndex);
        });
        options[nextIndex].scrollIntoView({
          block: 'nearest'
        });
      });

      rackNumberInput.addEventListener('focus', function() {
        updateRackNumberOptions(rackNumberInput.value);
      });

      function commitRackNumber() {
        const rackNumber = Number(rackNumberInput.value.replace(/\D/g, '').slice(0, 2));
        if (!rackNumber || rackNumber > 50) {
          showMessage('Enter a rack number from 01 to 50.', 'error');
          return;
        }

        const rack = String(rackNumber).padStart(2, '0');
        rackNumberInput.value = rack;
        rackNumberOptions.classList.remove('visible');
        setActiveRack(rack);
        rackNumberInput.blur();
      }

      rackNumberInput.addEventListener('change', commitRackNumber);

      rackNumberInput.addEventListener('blur', function() {
        setTimeout(function() {
          rackNumberOptions.classList.remove('visible');
        }, 150);
      });

      function syncManualEntryControls() {
        const manualRollInput = document.getElementById('manualRollInput');
        const manualRollButton = document.getElementById('manualRollBtn');
        if (manualRollInput) {
          manualRollInput.disabled = isSaving;
          manualRollInput.readOnly = !manualEntryAuthorized;
          manualRollInput.placeholder = manualEntryAuthorized
            ? 'ROLL number (e.g. 3000000001)'
            : 'Unlock to enter manually';
        }
        if (manualRollButton) {
          manualRollButton.disabled = isSaving;
          manualRollButton.classList.toggle('manual-locked-button', !manualEntryAuthorized);
        }
      }

      function showManualEntryLocked() {
        const message = 'Enter a valid password and select Unlock before entering a roll manually.';
        if (typeof Swal !== 'undefined') {
          if (Swal.isVisible()) return;
          Swal.fire({ icon: 'warning', title: 'Manual Entry Locked', text: message, confirmButtonText: 'OK' });
        } else {
          alert('Manual Entry Locked: ' + message);
        }
      }

      function unlockManualEntry() {
        const password = manualPassword.value;
        if (!password) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Password Required', text: 'Enter the password to unlock manual entry.' });
          } else {
            alert('Enter the password to unlock manual entry.');
          }
          manualPassword.focus();
          return;
        }

        unlockManualButton.disabled = true;
        fetch('ajaxDyeing_roll_verification.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'authorize_manual_entry',
            scope: 'knitting_store',
            password: password
          })
        }).then(function(response) {
          return response.json().then(function(data) {
            if (!response.ok || !data.success) {
              throw new Error(data.message || 'Could not unlock manual entry.');
            }
            manualPassword.value = '';
            manualEntryAuthorized = true;
            syncManualEntryControls();
            showMessage('Manual entry unlocked.', 'success');
          });
        }).catch(function(error) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Manual Entry Locked', text: error.message });
          } else {
            alert(error.message);
          }
        }).finally(function() {
          unlockManualButton.disabled = false;
        });
      }

      function fetchDataByRoll(roll, isManualEntry) {
        return new Promise(function(resolve, reject) {
          const manualQuery = isManualEntry ? '&manual=1' : '';
          const endpoint = isZoneMode ? 'ajaxKnitting_store_zone.php' : 'ajaxKnitting_store.php';
          fetch(endpoint + '?action=get_by_roll&roll=' + encodeURIComponent(roll) + manualQuery)
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

      function saveRackData(items, rackno, racklocation) {
        var endpoint = 'ajaxKnittingStore_Insert.php';
        var payload = { items: items, RACKNO: rackno, RACKLOCATION: racklocation };
        if (isZoneMode) {
          endpoint = 'ajaxKnitting_store_zone.php?action=insert_zone';
          payload = { action: 'insert_zone', items: items };
        }
        return new Promise(function(resolve, reject) {
          fetch(endpoint, {
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

      function submitManualRoll() {
        const inp = document.getElementById('manualRollInput');
        const roll = inp ? String(inp.value).trim() : '';

        if (!manualEntryAuthorized) {
          showManualEntryLocked();
          return;
        }
        if (isSaving) return;
        if (!roll) {
          showMessage('Please enter a ROLL number first.', 'error');
          return;
        }

        addRollToPreview(roll, true);
        inp.value = '';
      }

      function processScannedData(decodedText) {
        if (isSaving) return;
        const roll = extractRollFromQR(decodedText);

        if (!roll) {
          const invalidText = String(decodedText || '');
          if (invalidText !== lastInvalidQr || Date.now() - lastInvalidQrAt >= MESSAGE_DURATION_MS) {
            showMessage('Invalid QR code. Scan a valid knitting production roll.', 'error');
            lastInvalidQr = invalidText;
            lastInvalidQrAt = Date.now();
          }
          return;
        }

        addRollToPreview(roll, false);
      }

      function addRollToPreview(roll, isManualEntry) {
        if (isManualEntry && !manualEntryAuthorized) return;
        if (isSaving) return;
        const normalizedRoll = normalizeProductionRoll(roll);
        if (!normalizedRoll) {
          showMessage('Invalid ROLL number. Enter a valid knitting production roll.', 'error');
          return;
        }

        const rollKey = normalizedRoll.toUpperCase();
        if (pendingRolls.has(rollKey)) return;
        if (scannedRolls.some(function(row) {
            return String(row.ROLL || '').trim().toUpperCase() === rollKey;
          })) {
          if (Date.now() >= (rollDuplicateNoticeAfter.get(rollKey) || 0)) {
            showMessage('Roll ' + normalizedRoll + ' data is already shown in the preview.', 'duplicate');
            rollDuplicateNoticeAfter.set(rollKey, Date.now() + PREVIEW_NOTICE_DURATION_MS);
          }
          return;
        }
        if (Date.now() < (rollRetryAfter.get(rollKey) || 0)) return;

        pendingRolls.add(rollKey);
        cameraStatus.innerText = 'Searching...';
        cameraStatus.style.color = '#fbbf24';

        fetchDataByRoll(normalizedRoll, isManualEntry)
          .then(function(row) {
            rollRetryAfter.delete(rollKey);
            rollDuplicateNoticeAfter.set(rollKey, Date.now() + MESSAGE_DURATION_MS);
            renderScannedData(row);
            resultPanel.style.display = 'block';
            cameraStatus.innerText = 'Scanning (' + scannedRolls.length + ' loaded)';
            cameraStatus.style.color = '#4b4f51';
            showMessage('Roll ' + normalizedRoll + ' added to preview. Scan the next roll or store all ' + scannedRolls.length + ' rolls.', 'success');
          })
          .catch(function(err) {
            cameraStatus.innerText = isScanning ? 'Scanning' : 'Ready';
            cameraStatus.style.color = '#f7a1a1';
            const errorMessage = typeof err === 'string' ? err : ((err && err.message) || 'Roll data could not be loaded.');
            rollRetryAfter.set(rollKey, Date.now() + MESSAGE_DURATION_MS);
            showMessage(errorMessage, 'error');
          })
          .finally(function() {
            pendingRolls.delete(rollKey);
          });
      }

      resetRackBtn.addEventListener('click', function() {
        resetRackSelection();
        renderScannedData();
        showMessage(isZoneMode ? 'Roll selection reset.' : 'Rack selection reset.', '');
      });

      saveRackBtn.addEventListener('click', function() {
        if (isSaving) return;
        if (!scannedRolls.length) {
          showMessage('No data loaded. Please enter a ROLL number first.', 'error');
          return;
        }

        const rackno = selectedRack ? String(selectedRack).trim() : '';
        const racklocation = selectedSubRack ? String(selectedSubRack).trim().toUpperCase() : '';

        if (!isZoneMode) {
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
        }

        const rollsToSave = scannedRolls.slice();
        const wasScanningBeforeSave = isScanning;
        const manualRollInput = document.getElementById('manualRollInput');
        const manualRollBtn = document.getElementById('manualRollBtn');
        isSaving = true;
        if (manualRollInput) manualRollInput.disabled = true;
        if (manualRollBtn) manualRollBtn.disabled = true;
        saveRackBtn.disabled = true;
        saveRackBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving ' + rollsToSave.length + ' rolls...';

        const saveAllRolls = function() {
          saveRackData(rollsToSave, rackno.padStart(2, '0'), racklocation)
          .then(function(resp) {
            const storedCount = Number(resp.stored) || rollsToSave.length;
            const successMessage = isZoneMode
              ? storedCount + ' roll(s) stored in Knitting Store Common Zone.'
              : storedCount + ' roll(s) stored in Rack ' + rackno.padStart(2, '0') + racklocation;

            if (typeof Swal !== "undefined") {
              Swal.fire({
                icon: "success",
                title: isZoneMode ? "Knitting Store Common Zone Complete!" : "All Rolls Stored!",
                text: successMessage,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
              }).then(function() {
                window.location.reload();
              });
              return;
            }

            showMessage(resp.message || successMessage, 'success');
            setTimeout(function() {
              window.location.reload();
            }, 2000);
          })
          .catch(function(err) {
            var msg = (err && (err.error || err.message)) || String(err);
            if (msg === '[object Object]' || msg === 'undefined') {
              msg = 'Save failed. Please try again.';
            }

            showMessage(msg, 'error');
            isSaving = false;
            syncManualEntryControls();
            saveRackBtn.disabled = false;
            saveRackBtn.innerHTML = getStoreButtonLabel(scannedRolls.length);
            if (wasScanningBeforeSave) restartScanner();
          });
        };

        if (wasScanningBeforeSave && html5QrCode) {
          html5QrCode.stop()
            .then(function() {
              isScanning = false;
              scannerContainer.style.display = 'none';
              cameraControls.style.display = 'none';
              saveAllRolls();
            })
            .catch(function(error) {
              console.error('Unable to pause scanner before saving rolls:', error);
              showMessage('Could not pause the camera. Please try storing again.', 'error');
              isSaving = false;
              syncManualEntryControls();
              saveRackBtn.disabled = false;
              saveRackBtn.innerHTML = getStoreButtonLabel(scannedRolls.length);
            });
        } else {
          saveAllRolls();
        }
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

      async function startNewScanner() {
        if (pageIsLeaving) return;
        if (document.hidden) {
          resumeScannerAfterVisibility = true;
          cameraStatus.innerText = 'Camera paused while page is hidden';
          return;
        }
        if (isCameraTransitioning) return;
        isCameraTransitioning = true;
        const previousScanner = html5QrCode;
        html5QrCode = null;
        isScanning = false;
        if (previousScanner) {
          try {
            await previousScanner.stop();
          } catch (error) {
            console.warn('Unable to stop the previous camera before switching:', error);
          }
          try {
            previousScanner.clear();
          } catch (error) {
            console.warn('Unable to clear the previous camera scanner:', error);
          }
        }

        const qrReaderElement = document.getElementById('qr-reader');
        qrReaderElement.innerHTML = '';

        const scanner = new Html5Qrcode("qr-reader");
        html5QrCode = scanner;

        const config = {
          fps: 30,
          qrbox: function(viewfinderWidth, viewfinderHeight) {
            const size = Math.min(240, Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.7));
            return {
              width: size,
              height: size
            };
          },
          aspectRatio: 1.0
        };

        try {
          await scanner.start({
              facingMode: currentFacingMode
            },
            config,
            onScanSuccess,
            function(err) {}
          );
          if (pageIsLeaving || document.hidden || html5QrCode !== scanner) {
            try { await scanner.stop(); } catch (error) {
              console.warn('Unable to stop camera after scanner page became inactive:', error);
            }
            try { scanner.clear(); } catch (error) {
              console.warn('Unable to clear camera after scanner page became inactive:', error);
            }
            if (html5QrCode === scanner) html5QrCode = null;
            isScanning = false;
            return;
          }
          cameraStartFailed = false;
          isScanning = true;
          cameraStatus.innerText = 'Scanning (' + (currentFacingMode === 'environment' ? 'Rear' : 'Front') + ')';
          cameraStatus.style.color = '#8bcbff';
          scannerContainer.style.display = 'block';
          cameraControls.style.display = 'flex';
          applyCameraZoom();
        } catch (err) {
          isScanning = false;
          cameraStartFailed = true;
          try {
            if (scanner.isScanning === true) await scanner.stop();
          } catch (cleanupError) {
            console.warn('Unable to stop the failed store camera:', cleanupError);
          }
          try { scanner.clear(); } catch (cleanupError) {
            console.warn('Unable to clear the failed store camera:', cleanupError);
          }
          if (html5QrCode === scanner) html5QrCode = null;
          console.error("Camera start error:", err);
          const errorMessage = err && err.message ? err.message : String(err || 'Unknown camera error');
          cameraStatus.innerText = 'Camera busy or unavailable';
          cameraStatus.style.color = '#f7a1a1';
          showMessage('Camera is in use or unavailable (' + errorMessage + '). Close other camera apps or scanner tabs, then retry. You can still add rolls manually.', 'error');
          const retryCameraBtn = document.createElement('button');
          retryCameraBtn.className = 'rescan-btn';
          retryCameraBtn.type = 'button';
          retryCameraBtn.textContent = 'Retry Camera';
          retryCameraBtn.addEventListener('click', function() {
            cameraStartFailed = false;
            restartScanner();
          });
          messageContainer.appendChild(retryCameraBtn);
        } finally {
          isCameraTransitioning = false;
          if (resumeScannerAfterVisibility && !document.hidden && !pageIsLeaving) {
            resumeScannerAfterVisibility = false;
            if (!isScanning && scannerContainer.style.display !== 'none') {
              setTimeout(startNewScanner, 500);
            }
          }
        }
      }

      function applyCameraZoom() {
        try {
          const capabilities = html5QrCode.getRunningTrackCapabilities();
          const zoomRange = capabilities.zoom;
          if (!zoomRange || typeof zoomRange.max !== 'number') return;

          const zoomLevel = Math.min(1.5, zoomRange.max);
          if (zoomLevel <= zoomRange.min) return;

          html5QrCode.applyVideoConstraints({
            advanced: [{ zoom: zoomLevel }]
          }).catch(function(err) {
            console.warn('Camera zoom is not supported:', err);
          });
        } catch (err) {
          console.warn('Unable to read camera zoom capabilities:', err);
        }
      }

      function onScanSuccess(decodedText, decodedResult) {
        console.log('QR Scanned:', decodedText);
        if (navigator.vibrate) navigator.vibrate(20);
        processScannedData(decodedText);
      }

      async function restartScanner() {
        if (isCameraTransitioning) return;
        scannerContainer.style.display = 'block';
        cameraControls.style.display = 'flex';
        cameraStatus.innerText = 'Restarting...';
        cameraStatus.style.color = '#fbbf24';
        await startNewScanner();
      }

      document.getElementById('pageRefreshBtn').addEventListener('click', function() {
        window.location.reload();
      });

      document.getElementById('toggle-camera-btn').addEventListener('click', function(e) {
        e.preventDefault();
        if (isCameraTransitioning) return;
        if (!cameraStartFailed) {
          currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
        }
        cameraStartFailed = false;
        restartScanner();
      });

      document.addEventListener('DOMContentLoaded', function() {
        renderDefaultData();
        syncManualEntryControls();

        unlockManualButton.addEventListener('click', unlockManualEntry);
        manualPassword.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            unlockManualEntry();
          }
        });

        const mRollInput = document.getElementById('manualRollInput');
        const mRollBtn = document.getElementById('manualRollBtn');
        if (mRollBtn) mRollBtn.addEventListener('click', submitManualRoll);
        if (mRollInput) mRollInput.addEventListener('click', function() {
          if (!manualEntryAuthorized) showManualEntryLocked();
        });
        if (mRollInput) mRollInput.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            submitManualRoll();
          }
        });

        setTimeout(function() {
          startScanner();
        }, 500);
      });

      function stopScannerForPageLifecycle() {
        const scanner = html5QrCode;
        if (!scanner || isCameraTransitioning) return Promise.resolve();
        const wasScanning = isScanning || scanner.isScanning === true;
        html5QrCode = null;
        isScanning = false;
        if (!wasScanning) {
          try { scanner.clear(); } catch (error) {
            console.warn('Unable to clear camera after leaving the page:', error);
          }
          return Promise.resolve();
        }
        return Promise.resolve()
          .then(function() { return scanner.stop(); })
          .catch(function(error) {
            console.warn('Unable to stop camera after leaving the page:', error);
          })
          .then(function() {
            try { scanner.clear(); } catch (error) {
              console.warn('Unable to clear camera after leaving the page:', error);
            }
          });
      }

      document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
          resumeScannerAfterVisibility = resumeScannerAfterVisibility || isScanning || isCameraTransitioning;
          if (isScanning) stopScannerForPageLifecycle();
          return;
        }

        if (!resumeScannerAfterVisibility) return;
        if (isCameraTransitioning) return;
        resumeScannerAfterVisibility = false;
        if (scannerContainer.style.display !== 'none' && cameraControls.style.display !== 'none' && !isSaving) {
          setTimeout(startNewScanner, 500);
        }
      });

      window.addEventListener('pagehide', function() {
        pageIsLeaving = true;
        stopScannerForPageLifecycle();
      });
      window.addEventListener('beforeunload', function() {
        pageIsLeaving = true;
        stopScannerForPageLifecycle();
      });

    })();
  </script>
  <?php endif; ?>
</body>

</html>