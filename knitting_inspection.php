<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>QR Scanner · Instant Data</title>
  <!-- Font Awesome (optional, for icons) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- html5-qrcode library -->
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
      max-width: 550px;
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
      box-shadow: inset 0 0 0 1px #33405e, 0 8px 20px rgba(0,0,0,0.5);
      margin-bottom: 24px;
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

    /* overlay scan frame – pure css */
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
      0% { opacity: 0.4; transform: translate(-50%, -50%) scale(0.96); }
      50% { opacity: 1; transform: translate(-50%, -50%) scale(1.02); }
      100% { opacity: 0.4; transform: translate(-50%, -50%) scale(0.96); }
    }

    /* camera toggle & status */
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

    /* result panel – shows default + scanned data */
    .result-panel {
      background: #101826;
      border-radius: 28px;
      padding: 18px 20px 16px;
      margin-top: 20px;
      border: 1px solid #29364f;
      box-shadow: inset 0 2px 6px rgba(0,0,0,0.4);
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
      gap: 6px;
      word-break: break-word;
    }

    .data-row {
      background: #1a2337;
      padding: 12px 16px;
      border-radius: 18px;
      border-left: 4px solid #4fc3f7;
      color: #e3ecfc;
      font-size: 0.95rem;
      line-height: 1.5;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    .data-row strong {
      color: #8bb1ff;
      font-weight: 600;
      margin-right: 6px;
    }

    .data-row.default-row {
      border-left-color: #7a8bb0;
      opacity: 0.8;
      background: #131d30;
      color: #b7c9f0;
    }

    .data-row.default-row strong {
      color: #a0b9f0;
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

    .flex-row {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 4px 10px;
    }

    .footer-note {
      margin-top: 16px;
      text-align: center;
      color: #44557a;
      font-size: 0.7rem;
      letter-spacing: 0.5px;
    }

    .footer-note i {
      margin: 0 4px;
    }

    /* responsive */
    @media (max-width: 480px) {
      .card { padding: 16px; }
      .data-row { font-size: 0.85rem; padding: 10px 14px; }
    }
  </style>
</head>
<body>
  <div class="card">
    <!-- SCANNER -->
    <div class="scanner-container">
      <div id="qr-reader"></div>
      <div class="scan-overlay"></div>
    </div>

    <!-- Camera controls + status -->
    <div class="camera-controls">
      <div class="status-badge">
        <i class="fas fa-video"></i>
        <span id="camera-status">Ready</span>
      </div>
      <button class="btn-icon" id="toggle-camera-btn" title="Restart / switch camera">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <!-- RESULT PANEL (default + scanned data) -->
    <div class="result-panel">
      <div class="result-header">
        <i class="fas fa-qrcode"></i>
        <span>Scanned Data</span>
        <span style="margin-left: auto; font-size: 0.7rem; background: #1f2a40; padding: 2px 12px; border-radius: 40px; color: #91a9da;">live</span>
      </div>
      <div id="result-content">
        <!-- Default content will be injected by JS -->
        <!-- then replaced/updated when QR scanned -->
      </div>
    </div>
    <div class="footer-note">
      <i class="fas fa-camera"></i>  Scan any QR · data shown instantly  <i class="fas fa-arrow-right"></i>
    </div>
  </div>

  <script>
    (function() {
      "use strict";

      // ----- DOM refs -----
      const resultContainer = document.getElementById('result-content');
      const cameraStatus = document.getElementById('camera-status');

      // ----- DEFAULT DATA (shown before any scan) -----
      const DEFAULT_DATA = {
        title: '📋 Default Information',
        fields: [
          { label: 'Status', value: 'Awaiting QR scan' },
          { label: 'System', value: 'Camera ready' },
          { label: 'Hint', value: 'Point at any QR code' }
        ]
      };

      // ----- render default content (initial) -----
      function renderDefaultData() {
        let html = `<div class="data-row default-row"><strong><i class="fas fa-info-circle" style="margin-right:6px;"></i>${DEFAULT_DATA.title}</strong></div>`;
        DEFAULT_DATA.fields.forEach(f => {
          html += `<div class="data-row default-row"><strong>${f.label}:</strong> ${f.value}</div>`;
        });
        resultContainer.innerHTML = html;
      }

      // ----- render scanned data (overwrites default) -----
      function renderScannedData(qrText) {
        // If QR text is empty / null, fallback to default
        if (!qrText || qrText.trim() === '') {
          renderDefaultData();
          return;
        }

        // Build a rich display with the scanned text
        // Also show "default" style fields BUT with scanned data prominent
        const scannedTitle = '✅ QR Scanned';
        const scannedValue = qrText.trim();

        // Try to detect if it's a URL or JSON-like, but we show raw anyway
        let extraInfo = '';
        if (scannedValue.startsWith('http://') || scannedValue.startsWith('https://')) {
          extraInfo = '🔗 Link detected';
        } else if (scannedValue.startsWith('{') && scannedValue.includes(':')) {
          extraInfo = '📦 JSON-like data';
        } else {
          extraInfo = '📄 Text data';
        }

        // Build result HTML : show scanned data first, then some default fields (modified)
        let html = `
          <div class="data-row" style="border-left-color: #4fc3f7; background: #18203a;">
            <strong><i class="fas fa-scan" style="margin-right:8px;"></i>${scannedTitle}</strong>
            <span class="scanned-badge">new</span>
            <div style="margin-top: 8px; font-size: 0.95rem; background: #0f172a; padding: 10px 12px; border-radius: 14px; border: 1px solid #2b3d60; word-break: break-all;">
              ${scannedValue}
            </div>
          </div>
          <div class="data-row default-row" style="margin-top: 4px;">
            <strong>📌 Type:</strong> ${extraInfo}
          </div>
          <div class="data-row default-row">
            <strong>⏱️ Scanned at:</strong> ${new Date().toLocaleTimeString()}
          </div>
          <div class="data-row default-row">
            <strong>📊 Length:</strong> ${scannedValue.length} characters
          </div>
        `;
        resultContainer.innerHTML = html;
      }

      // ----- QR Code scanner instance -----
      let html5QrCode = null;
      let isScanning = false;
      let cameraId = null; // not used for auto, but we keep

      // ----- start scanner with default camera -----
      function startScanner() {
        if (html5QrCode) {
          // if already exists, stop & clear
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
          } catch(e) {}
          html5QrCode = null;
        }

        const qrReaderElement = document.getElementById('qr-reader');
        // make sure container is empty (avoid duplicates)
        qrReaderElement.innerHTML = '';

        html5QrCode = new Html5Qrcode("qr-reader");

        const config = {
          fps: 20,
          qrbox: { width: 240, height: 240 },
          aspectRatio: 1.0
        };

        // Use default camera (environment preferred)
        const cameraConstraints = { facingMode: "environment" };

        html5QrCode.start(
          cameraConstraints,
          config,
          onScanSuccess,
          onScanError
        ).then(() => {
          isScanning = true;
          cameraStatus.innerText = 'Scanning';
          cameraStatus.style.color = '#8bcbff';
          // On start we also reset to default data (if no scan yet)
          // but only if result panel is empty or default already shown
          // we keep default until scan occurs.
          if (resultContainer.children.length === 0) {
            renderDefaultData();
          }
        }).catch(err => {
          console.error("Camera start error:", err);
          cameraStatus.innerText = 'Camera error';
          cameraStatus.style.color = '#f7a1a1';
          // show error in result?
          resultContainer.innerHTML = `
            <div class="data-row default-row" style="border-left-color: #c44;">
              <strong><i class="fas fa-exclamation-triangle"></i> Camera unavailable</strong>
              <div style="margin-top:6px; font-size:0.85rem;">${err.message || 'Please allow camera access'}</div>
              <div style="margin-top:6px; font-size:0.8rem; color:#7a8bb0;">Tap restart or grant permissions</div>
            </div>
          `;
        });
      }

      // ----- on successful QR scan -----
      function onScanSuccess(decodedText, decodedResult) {
        // decodedText is the string content
        // Show the scanned data immediately (overwrites default)
        renderScannedData(decodedText);
        // Optionally add a small vibration / feedback (haptic)
        if (navigator.vibrate) navigator.vibrate(20);
        // Keep scanning (do not stop)
      }

      // ----- on scan error (ignored, but we keep for debug) -----
      function onScanError(err) {
        // ignore (frequent)
        // could set status but not needed
      }

      // ----- toggle / restart scanner (switch camera) -----
      function restartScanner() {
        if (html5QrCode) {
          html5QrCode.stop().then(() => {
            html5QrCode.clear();
            startNewScanner();
          }).catch(err => {
            // if stop fails, force new
            startNewScanner();
          });
        } else {
          startNewScanner();
        }
        // also re-show default until scan? But if we have scanned data we keep it
        // unless we want to reset: but better keep existing data until new scan.
        // However if user restarts, we keep current data (or default if empty)
        // To match requirement: "age na" (before) means default shows initially
        // but after scan shows scanned; restart does not clear scanned.
        // we keep result as is.
      }

      // ----- set up event listeners -----
      document.addEventListener('DOMContentLoaded', function() {
        // render default first
        renderDefaultData();

        // start scanner after small delay (ensure DOM)
        setTimeout(() => {
          startScanner();
        }, 300);

        // toggle / restart button
        document.getElementById('toggle-camera-btn').addEventListener('click', function(e) {
          e.preventDefault();
          restartScanner();
          // update status feedback
          cameraStatus.innerText = 'Restarting...';
          setTimeout(() => {
            if (isScanning) {
              cameraStatus.innerText = 'Scanning';
            }
          }, 400);
        });
      });

      // Cleanup on page unload
      window.addEventListener('beforeunload', function() {
        if (html5QrCode) {
          try {
            html5QrCode.stop();
            html5QrCode.clear();
          } catch(e) {}
        }
      });

      // Expose restart to console for debugging
      window.restartQrScanner = restartScanner;

    })();
  </script>
</body>
</html>