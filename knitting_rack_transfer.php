<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Operator';
$userRole    = $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? 'Staff';

// Fetch active master racks directly from database
$initialMasterRacks = [];
$rkMasterRes = $db->query("SELECT DISTINCT rack_no FROM rack_master WHERE is_active = 1 ORDER BY CAST(rack_no AS UNSIGNED) ASC, rack_no ASC");
if ($rkMasterRes) {
    while ($rkRow = $rkMasterRes->fetch_assoc()) {
        $initialMasterRacks[] = str_pad($rkRow['rack_no'], 2, '0', STR_PAD_LEFT);
    }
}
if (empty($initialMasterRacks)) {
    for ($r = 1; $r <= 50; $r++) {
        $initialMasterRacks[] = str_pad($r, 2, '0', STR_PAD_LEFT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rack Transfer | Knitting Store</title>
  <!-- Fonts & Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">

  <style>
    :root {
      --primary: #0284c7;
      --primary-hover: #0369a1;
      --teal: #0d9488;
      --teal-dark: #0f766e;
      --emerald: #10b981;
      --dark-slate: #0f172a;
      --border-color: #e2e8f0;
      --light-bg: #f8fafc;
    }

    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      background: #f1f5f9;
      color: #0f172a;
      min-height: 100vh;
      padding-bottom: 60px;
    }

    /* Top Bar */
    .top-navbar {
      background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%);
      border-bottom: 3px solid #0284c7;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
      padding: 12px 36px;
      margin-bottom: 20px;
      position: relative;
      z-index: 100;
    }

    .header-nav-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      gap: 20px;
    }

    .nav-left {
      display: flex;
      align-items: center;
      flex: 0 0 auto;
    }

    .btn-nav-dashboard {
      background: rgba(255, 255, 255, 0.08);
      color: #f8fafc;
      border: 1.5px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      padding: 10px 20px;
      font-weight: 700;
      font-size: 0.96rem;
      text-decoration: none;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      gap: 10px;
      backdrop-filter: blur(8px);
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-nav-dashboard:hover {
      background: #ffffff;
      color: #0f172a;
      border-color: #ffffff;
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(255, 255, 255, 0.2);
    }

    .btn-nav-dashboard i {
      font-size: 1.05rem;
      transition: transform 0.2s ease;
    }

    .btn-nav-dashboard:hover i {
      transform: translateX(-3px);
    }

    .nav-center {
      display: flex;
      align-items: center;
      justify-content: center;
      flex: 1 1 auto;
    }

    .brand-title {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .brand-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: rgba(56, 189, 248, 0.15);
      border: 1.5px solid rgba(56, 189, 248, 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #38bdf8;
      font-size: 1.35rem;
      box-shadow: 0 0 16px rgba(56, 189, 248, 0.2);
    }

    .brand-text-group {
      display: flex;
      flex-direction: column;
    }

    .brand-main-title {
      font-size: 1.55rem;
      font-weight: 800;
      color: #ffffff;
      letter-spacing: -0.4px;
      margin: 0;
      line-height: 1.2;
    }

    .brand-subtitle {
      font-size: 0.8rem;
      font-weight: 600;
      color: #94a3b8;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .nav-right {
      display: flex;
      align-items: center;
      gap: 16px;
      flex: 0 0 auto;
    }

    .user-profile-badge {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, 0.06);
      border: 1.5px solid rgba(255, 255, 255, 0.12);
      border-radius: 12px;
      padding: 8px 16px;
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, #0284c7, #0d9488);
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35);
    }

    .user-details {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }

    .user-name {
      font-weight: 800;
      font-size: 0.96rem;
      color: #f8fafc;
      letter-spacing: -0.2px;
    }

    .user-role {
      font-size: 0.74rem;
      font-weight: 600;
      color: #38bdf8;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn-nav-history {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #ffffff;
      border: 1.5px solid #38bdf8;
      border-radius: 12px;
      padding: 10px 20px;
      font-weight: 700;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 14px rgba(2, 132, 199, 0.28);
    }

    .btn-nav-history:hover {
      background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
      border-color: #7dd3fc;
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(2, 132, 199, 0.4);
      color: #ffffff;
    }

    .btn-nav-history i {
      font-size: 1.05rem;
    }

    @media (max-width: 992px) {
      .top-navbar {
        padding: 16px 20px;
      }
      .header-nav-container {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
      }
      .nav-left, .nav-center, .nav-right {
        justify-content: center;
      }
      .nav-left {
        justify-content: flex-start;
      }
      .nav-right {
        justify-content: space-between;
        width: 100%;
      }
    }

    /* Compact KPI Cards */
    .kpi-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 12px 18px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
      transition: all 0.2s ease;
    }

    .kpi-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(15, 23, 42, 0.07);
      border-color: rgba(2, 132, 199, 0.3);
    }

    .kpi-icon {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
    }

    .kpi-icon.blue {
      background: #eff6ff;
      color: #2563eb;
      border: 1px solid #bfdbfe;
    }
    .kpi-icon.teal {
      background: #f0fdfa;
      color: #0d9488;
      border: 1px solid #99f6e4;
    }
    .kpi-icon.amber {
      background: #fffbeb;
      color: #d97706;
      border: 1px solid #fde68a;
    }
    .kpi-icon.purple {
      background: #faf5ff;
      color: #9333ea;
      border: 1px solid #e9d5ff;
    }

    .kpi-label {
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #64748b;
      margin-bottom: 2px;
      white-space: nowrap;
    }

    .kpi-val {
      font-size: 1.55rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.15;
      letter-spacing: -0.3px;
    }

    /* Main Container Card */
    .table-panel {
      background: #ffffff;
      border-radius: 22px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 12px 32px -4px rgba(15, 23, 42, 0.05), 0 4px 6px -2px rgba(15, 23, 42, 0.02);
      overflow: hidden;
    }

    .table-panel-header {
      padding: 24px 34px;
      background: #ffffff;
      border-bottom: 1.5px solid #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }

    .table-panel-header h5 {
      font-weight: 800;
      font-size: 1.32rem;
      color: #0f172a;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
      letter-spacing: -0.3px;
    }

    /* ========================================================= */
    /* SLEEK FLOATING BATCH ACTIONS BAR                         */
    /* ========================================================= */
    .batch-toolbar {
      position: fixed;
      bottom: 28px;
      left: 50%;
      transform: translate(-50%, 80px) scale(0.96);
      z-index: 1040;
      background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%);
      border: 1.5px solid rgba(56, 189, 248, 0.35);
      border-radius: 20px;
      box-shadow: 0 20px 45px -6px rgba(11, 19, 41, 0.55), 0 4px 16px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease, visibility 0.3s;
      max-width: 95vw;
    }

    .batch-toolbar.active {
      transform: translate(-50%, 0) scale(1);
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
    }

    .batch-toolbar-content {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 10px 20px;
      white-space: nowrap;
      flex-wrap: nowrap;
    }

    .batch-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .badge-batch-count {
      background: rgba(56, 189, 248, 0.16);
      color: #38bdf8;
      border: 1px solid rgba(56, 189, 248, 0.35);
      font-size: 0.92rem;
      font-weight: 700;
      padding: 7px 14px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      letter-spacing: 0.2px;
    }

    .batch-label {
      color: #e2e8f0;
      font-weight: 700;
      font-size: 0.94rem;
      letter-spacing: -0.2px;
    }

    .batch-center {
      display: flex;
      align-items: center;
    }

    .batch-rack-select {
      background: #0f172a;
      color: #f8fafc;
      border: 1.5px solid #475569;
      border-radius: 12px;
      padding: 8px 16px;
      font-size: 0.92rem;
      font-weight: 700;
      outline: none;
      cursor: pointer;
      min-width: 175px;
      transition: all 0.2s ease;
    }

    .batch-rack-select:focus {
      border-color: #38bdf8;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25);
    }

    .batch-rack-select option {
      background: #1e293b;
      color: #ffffff;
    }

    .batch-shelf-select {
      background: #0f172a;
      color: #f8fafc;
      border: 1.5px solid #475569;
      border-radius: 12px;
      padding: 8px 16px;
      font-size: 0.92rem;
      font-weight: 700;
      outline: none;
      cursor: pointer;
      min-width: 145px;
      transition: all 0.2s ease;
    }

    .batch-shelf-select:disabled {
      opacity: 0.45;
      cursor: not-allowed;
      border-color: #334155;
    }

    .batch-shelf-select:enabled {
      border-color: #38bdf8;
      background: #1e293b;
    }

    .batch-shelf-select:focus {
      border-color: #38bdf8;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25);
    }

    .batch-shelf-select option, .batch-shelf-select optgroup {
      background: #1e293b;
      color: #ffffff;
    }

    .batch-divider {
      width: 1px;
      height: 26px;
      background: rgba(255, 255, 255, 0.16);
      flex-shrink: 0;
    }

    .batch-right {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn-batch-apply {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      padding: 9px 20px;
      font-size: 0.92rem;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
      transition: all 0.2s ease;
    }

    .btn-batch-apply:hover {
      background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
      box-shadow: 0 6px 18px rgba(2, 132, 199, 0.55);
      transform: translateY(-1px);
      color: #ffffff;
    }

    .btn-batch-apply:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .btn-batch-cancel {
      background: rgba(255, 255, 255, 0.08);
      color: #cbd5e1;
      border: 1px solid rgba(255, 255, 255, 0.16);
      border-radius: 12px;
      padding: 9px 15px;
      font-size: 0.92rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-batch-cancel:hover {
      background: rgba(239, 68, 68, 0.18);
      color: #fca5a5;
      border-color: rgba(239, 68, 68, 0.4);
    }

    /* Filter Controls */
    .filter-bar {
      padding: 24px 34px 10px;
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      align-items: center;
      background: #ffffff;
    }

    .search-box {
      flex: 1;
      min-width: 320px;
      position: relative;
    }

    .search-box input {
      padding: 14px 22px 14px 52px;
      border-radius: 12px;
      border: 2px solid #cbd5e1;
      font-size: 1.05rem;
      font-weight: 600;
      color: #0f172a;
      width: 100%;
      transition: all 0.2s;
      background: #f8fafc;
    }

    .search-box input:focus {
      background: #ffffff;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
      outline: none;
    }

    .search-box i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 1.15rem;
    }

    .rack-filter-select {
      border: 2px solid #cbd5e1;
      border-radius: 12px;
      padding: 13px 20px;
      font-size: 1.02rem;
      font-weight: 700;
      color: #0f172a;
      background: #f8fafc;
      outline: none;
      cursor: pointer;
      min-width: 180px;
      transition: all 0.2s;
    }

    .rack-filter-select:focus {
      background: #ffffff;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
    }

    /* Table Styles */
    .table-container {
      padding: 18px 34px 34px;
      overflow-x: auto;
    }

    .rack-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 1.02rem;
      white-space: nowrap;
    }

    .rack-table th {
      background: #f8fafc;
      color: #0f172a;
      font-weight: 800;
      font-size: 0.94rem;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      padding: 20px 24px;
      border-bottom: 2px solid #e2e8f0;
      border-top: 1px solid #e2e8f0;
      vertical-align: middle;
    }

    .rack-table th:first-child {
      border-top-left-radius: 14px;
      border-bottom-left-radius: 14px;
    }

    .rack-table th:last-child {
      border-top-right-radius: 14px;
      border-bottom-right-radius: 14px;
    }

    .rack-table td {
      padding: 20px 24px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      color: #0f172a;
      font-size: 1.02rem;
      line-height: 1.5;
      transition: background 0.2s;
    }

    .rack-table tr:hover td {
      background: #f8fafc;
    }

    .rack-table tr.row-updated td {
      background: #ecfdf5 !important;
    }

    .form-check-input {
      width: 22px;
      height: 22px;
      cursor: pointer;
      border: 2px solid #94a3b8;
    }

    /* Roll Barcode Monospace Asset Tag */
    .roll-barcode-text {
      background: #f0f9ff;
      color: #0284c7;
      border: 1.5px solid #bae6fd;
      padding: 6px 14px;
      border-radius: 8px;
      font-weight: 800;
      font-size: 1.08rem;
      font-family: 'JetBrains Mono', Consolas, monospace;
      letter-spacing: 0.5px;
      display: inline-block;
      box-shadow: 0 1px 2px rgba(2, 132, 199, 0.06);
    }

    /* Current Rack Badge */
    .badge-curr-rack {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #fefce8;
      color: #854d0e;
      border: 1.5px solid #fde047;
      font-weight: 800;
      font-size: 0.98rem;
      padding: 7px 16px;
      border-radius: 10px;
      letter-spacing: 0.3px;
      box-shadow: 0 1px 3px rgba(133, 77, 14, 0.06);
    }

    /* Table Typography Enhancements */
    .table-data-po {
      font-weight: 800;
      font-size: 1.02rem;
      color: #0f172a;
    }

    .table-data-buyer {
      font-weight: 700;
      font-size: 1.02rem;
      color: #1e293b;
    }

    .table-data-style {
      font-weight: 700;
      font-size: 1.02rem;
      color: #334155;
    }

    .table-data-color {
      font-weight: 700;
      font-size: 1.02rem;
      color: #475569;
    }

    .table-data-qty {
      font-weight: 800;
      font-size: 1.1rem;
      color: #0f172a;
      font-family: monospace;
    }

    .badge-machine {
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 4px 10px;
      font-weight: 700;
      font-size: 0.88rem;
    }

    /* Inline Destination Rack & Shelf Controls */
    .destination-group {
      display: flex;
      flex-direction: column;
      gap: 4px;
      min-width: 290px;
    }

    .destination-selects-wrapper {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .inline-rack-select {
      font-weight: 700;
      font-size: 0.95rem;
      padding: 9px 12px;
      border-radius: 10px;
      border: 2px solid #cbd5e1;
      color: #0f172a;
      background: #ffffff;
      outline: none;
      transition: all 0.2s ease;
      width: 135px;
      cursor: pointer;
      flex-shrink: 0;
    }

    .inline-rack-select:hover {
      border-color: #94a3b8;
      background: #f8fafc;
    }

    .inline-rack-select:focus {
      border-color: var(--teal);
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15);
    }

    .inline-shelf-select {
      font-weight: 700;
      font-size: 0.9rem;
      padding: 9px 12px;
      border-radius: 10px;
      border: 2px solid #cbd5e1;
      color: #0f172a;
      background: #f8fafc;
      outline: none;
      transition: all 0.2s ease;
      min-width: 155px;
      flex-grow: 1;
      cursor: pointer;
    }

    .inline-shelf-select:enabled {
      background: #ffffff;
      border-color: #0d9488;
    }

    .inline-shelf-select:focus {
      border-color: var(--teal);
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15);
    }

    .shelf-hint-text {
      font-size: 0.76rem;
      font-weight: 700;
      line-height: 1.2;
      display: flex;
      align-items: center;
      gap: 4px;
      min-height: 16px;
    }

    .badge-batch-capacity {
      margin-left: 12px;
      font-size: 0.85rem;
      font-weight: 700;
      padding: 6px 12px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      letter-spacing: 0.2px;
    }

    /* Inline Transfer Button */
    .btn-transfer-row {
      background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      padding: 11px 22px;
      font-size: 0.96rem;
      font-weight: 800;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(13, 148, 136, 0.28);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-transfer-row:hover:not(:disabled) {
      background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(13, 148, 136, 0.38);
      color: #fff;
    }

    .btn-transfer-row:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Custom Notification Toast */
    .toast-alert {
      position: fixed;
      top: 24px;
      right: 28px;
      z-index: 9999;
      background: #0f172a;
      color: #ffffff;
      padding: 16px 26px;
      border-radius: 14px;
      box-shadow: 0 12px 28px rgba(0,0,0,0.28);
      border-left: 6px solid #10b981;
      font-weight: 700;
      font-size: 1rem;
      display: none;
      align-items: center;
      gap: 14px;
      animation: toastIn 0.3s ease;
    }

    @keyframes toastIn {
      from { opacity: 0; transform: translateX(40px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* ========================================================= */
    /* BULLETPROOF TRANSFER HISTORY MODAL STYLES                */
    /* ========================================================= */
    .history-modal-backdrop {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(15, 23, 42, 0.72);
      backdrop-filter: blur(5px);
      -webkit-backdrop-filter: blur(5px);
      z-index: 99998;
      opacity: 0;
      transition: opacity 0.25s ease;
    }
    .history-modal-backdrop.show {
      display: block !important;
      opacity: 1 !important;
    }

    .history-modal-wrapper {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      z-index: 99999;
      overflow-x: hidden;
      overflow-y: auto;
      padding: 30px 16px;
      opacity: 0;
      transition: opacity 0.25s ease;
    }
    .history-modal-wrapper.show {
      display: flex !important;
      align-items: center;
      justify-content: center;
      opacity: 1 !important;
    }

    /* ========================================================= */
    /* BULLETPROOF TRANSFER HISTORY MODAL STYLES                */
    /* ========================================================= */
    .history-modal-backdrop {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      z-index: 99998;
      opacity: 0;
      transition: opacity 0.25s ease;
    }
    .history-modal-backdrop.show {
      display: block !important;
      opacity: 1 !important;
    }

    .history-modal-wrapper {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      z-index: 99999;
      overflow-x: hidden;
      overflow-y: auto;
      padding: 32px 20px;
      opacity: 0;
      transition: opacity 0.25s ease;
    }
    .history-modal-wrapper.show {
      display: flex !important;
      align-items: center;
      justify-content: center;
      opacity: 1 !important;
    }

    .history-modal-container {
      width: 100%;
      max-width: 1140px;
      margin: auto;
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 25px 70px -10px rgba(15, 23, 42, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.15);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transform: translateY(-20px) scale(0.98);
      transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .history-modal-wrapper.show .history-modal-container {
      transform: translateY(0) scale(1);
    }

    .history-modal-header {
      background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%);
      border-bottom: 3px solid #0284c7;
      padding: 20px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      color: #ffffff;
    }

    .history-header-left {
      display: flex;
      align-items: center;
      gap: 16px;
      min-width: 0;
    }

    .history-header-icon-box {
      width: 48px;
      height: 48px;
      min-width: 48px;
      border-radius: 14px;
      background: linear-gradient(135deg, rgba(2, 132, 199, 0.25) 0%, rgba(56, 189, 248, 0.15) 100%);
      border: 1.5px solid rgba(56, 189, 248, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #38bdf8;
      font-size: 1.35rem;
      box-shadow: 0 4px 16px rgba(2, 132, 199, 0.25);
      flex-shrink: 0;
    }

    .history-header-titles {
      display: flex;
      flex-direction: column;
      gap: 2px;
      min-width: 0;
    }

    .history-modal-title {
      font-size: 1.25rem;
      font-weight: 800;
      color: #ffffff;
      margin: 0;
      letter-spacing: -0.2px;
      line-height: 1.25;
    }

    .history-modal-subtitle {
      font-size: 0.84rem;
      font-weight: 500;
      color: #94a3b8;
      margin: 0;
      letter-spacing: 0.1px;
    }

    .btn-modal-dismiss {
      background: rgba(255, 255, 255, 0.08);
      border: 1.5px solid rgba(255, 255, 255, 0.18);
      color: #cbd5e1;
      width: 38px;
      height: 38px;
      min-width: 38px;
      border-radius: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1.15rem;
      flex-shrink: 0;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      outline: none;
      margin-left: auto;
    }

    .btn-modal-dismiss:hover {
      background: #ef4444;
      border-color: #ef4444;
      color: #ffffff;
      transform: rotate(90deg);
      box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
    }

    .history-modal-search {
      padding: 16px 28px;
      background: #f8fafc;
      border-bottom: 1.5px solid #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    .history-search-wrapper {
      position: relative;
      flex: 1;
      min-width: 280px;
      max-width: 480px;
    }

    .history-search-wrapper i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 1rem;
      pointer-events: none;
    }

    .history-search-input {
      width: 100%;
      padding: 10px 16px 10px 44px;
      border-radius: 12px;
      border: 1.5px solid #cbd5e1;
      background: #ffffff;
      font-size: 0.94rem;
      font-weight: 600;
      color: #0f172a;
      outline: none;
      transition: all 0.2s ease;
    }

    .history-search-input:focus {
      background: #ffffff;
      border-color: #0284c7;
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .history-actions-wrapper {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .history-count-pill {
      background: #e2e8f0;
      color: #334155;
      border: 1px solid #cbd5e1;
      border-radius: 20px;
      padding: 7px 16px;
      font-size: 0.85rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-refresh-history {
      background: #ffffff;
      color: #0284c7;
      border: 1.5px solid #0284c7;
      border-radius: 12px;
      padding: 8px 18px;
      font-size: 0.88rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-refresh-history:hover {
      background: #0284c7;
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
      transform: translateY(-1px);
    }

    .history-modal-body {
      max-height: 520px;
      overflow-y: auto;
      padding: 0;
      background: #ffffff;
    }

    .history-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 0.94rem;
    }

    .history-table thead th {
      background: #f8fafc;
      color: #475569;
      font-size: 0.78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      padding: 14px 18px;
      border-bottom: 2px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .history-table tbody td {
      padding: 13px 18px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      font-weight: 600;
      color: #1e293b;
    }

    .history-table tbody tr:hover {
      background: #f8fafc;
    }

    .history-from-rack {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #f1f5f9 !important;
      color: #0f172a !important; /* Explicit high-contrast dark slate */
      border: 1.5px solid #cbd5e1 !important;
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 0.92rem;
      font-weight: 800;
      letter-spacing: 0.3px;
      opacity: 1 !important;
      line-height: 1.3;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .history-from-rack i {
      color: #475569 !important;
      font-size: 0.85rem;
    }

    .history-to-rack {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #ecfdf5 !important;
      color: #065f46 !important; /* Vibrant high-contrast emerald */
      border: 1.5px solid #6ee7b7 !important;
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 0.92rem;
      font-weight: 800;
      letter-spacing: 0.3px;
      opacity: 1 !important;
      line-height: 1.3;
      box-shadow: 0 1px 2px rgba(16, 185, 129, 0.08);
    }
    .history-to-rack i {
      color: #059669 !important;
      font-size: 0.85rem;
    }

    .history-modal-footer {
      padding: 16px 28px;
      background: #f8fafc;
      border-top: 1.5px solid #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }

    .btn-close-modal-bottom {
      background: #475569;
      color: #ffffff;
      border: none;
      border-radius: 11px;
      padding: 9px 24px;
      font-size: 0.92rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-close-modal-bottom:hover {
      background: #334155;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <header class="top-navbar">
    <div class="header-nav-container">
      
      <!-- Left: Back to Dashboard -->
      <div class="nav-left">
        <a href="initialPage.php" class="btn-nav-dashboard" title="Back to Dashboard">
          <i class="fa-solid fa-arrow-left"></i>
          <span>Dashboard</span>
        </a>
      </div>

      <!-- Center: Title & Icon -->
      <div class="nav-center">
        <div class="brand-title">
          <span class="brand-icon">
            <i class="fa-solid fa-arrows-split-up-and-left"></i>
          </span>
          <div class="brand-text-group">
            <h1 class="brand-main-title">Knitting Rack Transfer</h1>
            <span class="brand-subtitle">Warehouse Inventory Relocation</span>
          </div>
        </div>
      </div>

      <!-- Right: User Profile & Transfer History -->
      <div class="nav-right">
        <div class="user-profile-badge">
          <div class="user-avatar">
            <i class="fa-solid fa-user-tie"></i>
          </div>
          <div class="user-details">
            <span class="user-name"><?php echo htmlspecialchars($currentUser); ?></span>
            <span class="user-role"><?php echo htmlspecialchars(ucfirst($userRole)); ?></span>
          </div>
        </div>

        <button type="button" class="btn-nav-history" id="btnOpenHistory" style="cursor: pointer;">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <span>Transfer History</span>
        </button>
      </div>

    </div>
  </header>

  <div class="container-fluid px-3 px-md-5 py-3">

    <!-- KPI Summary Row (Compact & Slim) -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
          <div class="kpi-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
          <div>
            <div class="kpi-label">Total Rolls in Store</div>
            <div class="kpi-val" id="kpiTotalRolls">--</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
          <div class="kpi-icon teal"><i class="fa-solid fa-weight-hanging"></i></div>
          <div>
            <div class="kpi-label">Total Weight (KG)</div>
            <div class="kpi-val" id="kpiTotalQty">--</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
          <div class="kpi-icon amber"><i class="fa-solid fa-warehouse"></i></div>
          <div>
            <div class="kpi-label">Active Racks</div>
            <div class="kpi-val" id="kpiActiveRacks">--</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card">
          <div class="kpi-icon purple"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
          <div>
            <div class="kpi-label">Transfers Today</div>
            <div class="kpi-val" id="kpiTransfersToday">--</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Table Panel -->
    <div class="table-panel">
      
      <!-- Panel Header -->
      <div class="table-panel-header">
        <h5>
          <i class="fa-solid fa-layer-group text-primary"></i>
          Stored Rolls & Rack Management
        </h5>
        <div class="d-flex align-items-center gap-3">
          <span class="text-muted fw-bold" id="rollCountSummary" style="font-size: 0.95rem;">Loading store records...</span>
          <button class="btn btn-outline-secondary btn-md rounded-2 px-3 py-2" id="btnRefresh" title="Refresh Table">
            <i class="fa-solid fa-rotate"></i>
          </button>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="filter-bar">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="tableSearchInput" placeholder="Search Roll Barcode, PO Number, Buyer, Style, Color, Rack...">
        </div>

        <select id="rackFilterSelect" class="rack-filter-select">
          <option value="ALL">All Active Racks (<?php echo count($initialMasterRacks); ?>)</option>
          <?php foreach ($initialMasterRacks as $pad): ?>
            <option value="<?php echo $pad; ?>">Rack <?php echo $pad; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Sleek Floating Batch Actions Bar -->
      <div class="batch-toolbar" id="batchToolbar">
        <div class="batch-toolbar-content">
          
          <!-- Left: Count Badge & Action Label -->
          <div class="batch-left">
            <span class="badge-batch-count" id="selectedCountBadge">
              <i class="fa-solid fa-circle-check me-1"></i> 0 rolls selected
            </span>
            <span class="batch-label d-none d-md-inline">Move to:</span>
          </div>

          <!-- Center: Target Rack & Shelf Dropdown -->
          <div class="batch-center">
            <div class="d-flex align-items-center gap-2">
              <select class="batch-rack-select" id="batchTargetRack">
                <option value="" disabled selected>Select Destination Rack...</option>
                <?php foreach ($initialMasterRacks as $pad): ?>
                    <option value="<?php echo $pad; ?>">Rack <?php echo $pad; ?></option>
                <?php endforeach; ?>
              </select>
              <select class="batch-shelf-select" id="batchTargetShelf" disabled>
                <option value="" disabled selected>Select Rack First...</option>
              </select>
            </div>
            <span id="batchCapacityBadge" class="badge-batch-capacity" style="display: none;"></span>
          </div>

          <!-- Vertical Divider -->
          <div class="batch-divider"></div>

          <!-- Right: Action Buttons -->
          <div class="batch-right">
            <button type="button" class="btn-batch-apply" id="btnApplyBatch">
              <i class="fa-solid fa-check"></i> Apply Batch
            </button>
            <button type="button" class="btn-batch-cancel" id="btnCancelBatch" title="Clear roll selection">
              <i class="fa-solid fa-xmark"></i> Cancel
            </button>
          </div>

        </div>
      </div>

      <!-- Data Table -->
      <div class="table-container">
        <table class="rack-table">
          <thead>
            <tr>
              <th style="width: 48px;" class="text-center">
                <input type="checkbox" class="form-check-input" id="checkAll">
              </th>
              <th>Roll Barcode</th>
              <th>Current Rack</th>
              <th>PO Number</th>
              <th>Buyer</th>
              <th>Style</th>
              <th>Color</th>
              <th class="text-end">Qty (KG)</th>
              <th>Machine</th>
              <th style="min-width: 310px;">Destination (Rack & Shelf)</th>
              <th style="width: 130px;" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody id="rollsTableBody">
            <tr>
              <td colspan="11" class="text-center py-5 text-muted fs-5">
                <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading store rolls...
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

  </div>

  <!-- BULLETPROOF TRANSFER HISTORY MODAL -->
  <div id="historyModalBackdrop" class="history-modal-backdrop"></div>
  
  <div id="historyModal" class="history-modal-wrapper" role="dialog" aria-modal="true">
    <div class="history-modal-container">
      
      <!-- Modal Header -->
      <div class="history-modal-header">
        <div class="history-header-left">
          <div class="history-header-icon-box">
            <i class="fa-solid fa-clock-rotate-left"></i>
          </div>
          <div class="history-header-titles">
            <div class="d-flex align-items-center gap-2">
              <h5 class="history-modal-title">Rack Transfer Audit History</h5>
              <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 rounded-pill fw-bold" style="font-size: 0.74rem;">Audit Log</span>
            </div>
            <p class="history-modal-subtitle">Complete chronological log of roll movements and rack relocations</p>
          </div>
        </div>
        <button type="button" class="btn-modal-dismiss" id="btnCloseHistoryModal" title="Close" aria-label="Close modal">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Modal Search & Filter Bar -->
      <div class="history-modal-search">
        <div class="history-search-wrapper">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="modalHistorySearch" class="history-search-input" placeholder="Filter logs by Roll Barcode, User, Rack, Buyer, Style...">
        </div>
        <div class="history-actions-wrapper">
          <span class="history-count-pill" id="historyCountBadge">
            <i class="fa-solid fa-database text-primary"></i> -- entries
          </span>
          <button type="button" class="btn-refresh-history" id="btnRefreshHistoryModal">
            <i class="fa-solid fa-arrows-rotate"></i> Refresh
          </button>
        </div>
      </div>

      <!-- Modal Body / Table Container -->
      <div class="history-modal-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 text-nowrap history-table">
            <thead>
              <tr>
                <th class="ps-4">#ID</th>
                <th>Roll Barcode</th>
                <th>From Rack</th>
                <th class="text-center px-1"></th>
                <th>To Rack</th>
                <th>Transferred By</th>
                <th>Date & Time</th>
                <th class="pe-4">Buyer / Style</th>
              </tr>
            </thead>
            <tbody id="historyTableBody">
              <tr>
                <td colspan="8" class="text-center py-5 text-muted fs-6">
                  <i class="fa-solid fa-spinner fa-spin me-2 text-primary"></i> Loading audit history...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="history-modal-footer">
        <div class="d-flex align-items-center gap-2 text-muted fw-semibold small">
          <i class="fa-solid fa-shield-halved text-success fs-6"></i>
          <span>Permanent audit records in <code>rack_transfer_log</code> table</span>
        </div>
        <button type="button" class="btn-close-modal-bottom" id="btnCloseHistoryModalBottom">Close</button>
      </div>

    </div>
  </div>

  <!-- Notification Toast -->
  <div id="toastAlert" class="toast-alert">
    <i class="fa-solid fa-circle-check fs-4 text-success"></i>
    <span id="toastMessage">Action completed successfully.</span>
  </div>

  <!-- Scripts -->
  <script src="jquery.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>

  <script>
    (function() {
      "use strict";

      let allRollsData = [];
      let databaseRacks = [];
      let dbRackOptionsHtml = '<option value="" disabled selected>Select Rack...</option>';

      // -----------------------------------------------------------
      // Dynamic Database Master Racks Loader
      // -----------------------------------------------------------
      function loadDatabaseRacks(callback) {
        $.ajax({
          url: 'ajaxKnitting_rack_transfer.php',
          type: 'GET',
          data: { action: 'get_racks' },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.success && resp.data) {
              databaseRacks = resp.data;

              // Build HTML options for rack dropdowns
              let opts = '<option value="" disabled selected>Select Rack...</option>';
              databaseRacks.forEach(function(r) {
                opts += `<option value="${r.rack_no}">${r.rack_label}</option>`;
              });
              dbRackOptionsHtml = opts;

              // Dynamically update batch toolbar rack selector if empty or on refresh
              const curBatchRack = $('#batchTargetRack').val();
              $('#batchTargetRack').html(opts);
              if (curBatchRack) $('#batchTargetRack').val(curBatchRack);

              // Dynamically update filter dropdown
              const curFilter = $('#rackFilterSelect').val() || 'ALL';
              let filterOpts = `<option value="ALL">All Active Racks (${databaseRacks.length})</option>`;
              databaseRacks.forEach(function(r) {
                filterOpts += `<option value="${r.rack_no}">${r.rack_label} (${r.rolls_stored} rolls)</option>`;
              });
              $('#rackFilterSelect').html(filterOpts).val(curFilter);
            }
            if (typeof callback === 'function') callback();
          },
          error: function() {
            if (typeof callback === 'function') callback();
          }
        });
      }

      // -----------------------------------------------------------
      // Dynamic Dependent Shelves Loader (From Database)
      // -----------------------------------------------------------
      function loadDatabaseShelves(rackNo, $shelfSelect, currentRoll, callback) {
        $shelfSelect.prop('disabled', true).html('<option value="" disabled selected>Loading shelves from database...</option>');
        
        $.ajax({
          url: 'ajaxKnitting_rack_transfer.php',
          type: 'GET',
          data: {
            action: 'get_shelves',
            rack_no: rackNo,
            roll: currentRoll || ''
          },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.success && resp.data && resp.data.length > 0) {
              // Group shelves by section (Section A, B, C...)
              const sections = {};
              resp.data.forEach(function(item) {
                const sec = item.section || 'General';
                if (!sections[sec]) sections[sec] = [];
                sections[sec].push(item);
              });

              let opts = '<option value="" disabled selected>Select Shelf...</option>';
              for (const [secName, items] of Object.entries(sections)) {
                opts += `<optgroup label="${secName}">`;
                items.forEach(function(item) {
                  const statusNote = item.is_occupied ? ` (Contains Roll ${item.roll})` : ' (Empty)';
                  opts += `<option value="${item.shelf}">${item.label}${statusNote}</option>`;
                });
                opts += '</optgroup>';
              }
              $shelfSelect.html(opts).prop('disabled', false);
              if (typeof callback === 'function') callback(resp);
            } else {
              $shelfSelect.html('<option value="" disabled selected>No shelves found</option>');
            }
          },
          error: function() {
            $shelfSelect.html('<option value="" disabled selected>Error loading shelves</option>');
          }
        });
      }

      $(document).ready(function() {
        // First load master racks dynamically from DB, then fetch KPIs and stored rolls
        loadDatabaseRacks(function() {
          loadStats();
          loadStoreRolls();
        });

        // Search input with debounce
        let searchTimer = null;
        $('#tableSearchInput').on('input', function() {
          clearTimeout(searchTimer);
          searchTimer = setTimeout(loadStoreRolls, 250);
        });

        // Rack filter change
        $('#rackFilterSelect').on('change', function() {
          loadStoreRolls();
        });

        // Refresh button
        $('#btnRefresh').on('click', function() {
          const $btn = $(this);
          $btn.find('i').addClass('fa-spin');
          loadDatabaseRacks(function() {
            loadStats();
            loadStoreRolls(function() {
              $btn.find('i').removeClass('fa-spin');
            });
          });
        });

        // Check All / Batch selection
        $('#checkAll').on('change', function() {
          const isChecked = $(this).is(':checked');
          $('.row-checkbox').prop('checked', isChecked);
          updateBatchToolbar();
        });

        $(document).on('change', '.row-checkbox', function() {
          updateBatchToolbar();
          const totalCheckboxes = $('.row-checkbox').length;
          const checkedCount = $('.row-checkbox:checked').length;
          $('#checkAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCount);
        });

        $('#btnCancelBatch').on('click', function() {
          $('.row-checkbox, #checkAll').prop('checked', false);
          updateBatchToolbar();
        });

        // Batch Apply
        $('#btnApplyBatch').on('click', function() {
          applyBatchTransfer();
        });

        // Batch target rack change listener -> loads dynamic shelves from database
        $('#batchTargetRack').on('change', function() {
          const rack = $(this).val();
          const $shelf = $('#batchTargetShelf');
          const $badge = $('#batchCapacityBadge');

          if (rack) {
            $badge.show().css({
              'background': 'rgba(56, 189, 248, 0.15)',
              'color': '#7dd3fc',
              'border': '1px solid rgba(56, 189, 248, 0.35)'
            }).html('<i class="fa-solid fa-spinner fa-spin"></i> Loading shelves from database...');

            loadDatabaseShelves(rack, $shelf, null, function(resp) {
              $badge.html(`<i class="fa-solid fa-layer-group"></i> Rack ${rack}: ${resp.available_count} of ${resp.total_shelves} shelves empty — please select a shelf`);
            });
          } else {
            $shelf.val('').prop('disabled', true).html('<option value="" disabled selected>Select Rack First...</option>');
            $badge.hide();
          }
        });

        // Batch target shelf change listener
        $('#batchTargetShelf').on('change', function() {
          const rack = $('#batchTargetRack').val();
          const shelf = $(this).val();
          const count = $('.row-checkbox:checked').length;
          const $badge = $('#batchCapacityBadge');

          if (rack && shelf) {
            $badge.show().css({
              'background': 'rgba(16, 185, 129, 0.2)',
              'color': '#6ee7b7',
              'border': '1px solid rgba(16, 185, 129, 0.4)'
            }).html(`<i class="fa-solid fa-check-circle"></i> Target: Rack ${rack} (Shelf ${shelf}) for ${count} ${count === 1 ? 'roll' : 'rolls'}`);
          }
        });

        // -----------------------------------------------------------
        // Transfer History Modal Functions (Bulletproof)
        // -----------------------------------------------------------
        function openHistoryModal() {
          $('#historyModalBackdrop').addClass('show');
          $('#historyModal').addClass('show');
          $('body').css('overflow', 'hidden');
          loadHistoryLogs();
        }

        function closeHistoryModal() {
          $('#historyModal').removeClass('show');
          $('#historyModalBackdrop').removeClass('show');
          $('body').css('overflow', '');
        }

        // Open modal on Transfer History button click
        $('#btnOpenHistory').on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          openHistoryModal();
        });

        // Close modal on dismiss buttons
        $(document).on('click', '#btnCloseHistoryModal, #btnCloseHistoryModalBottom', function(e) {
          e.preventDefault();
          closeHistoryModal();
        });

        // Close when clicking outside modal content
        $('#historyModal, #historyModalBackdrop').on('click', function(e) {
          if (e.target === this) {
            closeHistoryModal();
          }
        });

        // Close on Escape key
        $(document).on('keydown', function(e) {
          if (e.key === 'Escape' && $('#historyModal').hasClass('show')) {
            closeHistoryModal();
          }
        });

        // Search filter inside modal
        $('#modalHistorySearch').on('input', function() {
          const q = $(this).val().toLowerCase().trim();
          filterHistoryLogs(q);
        });

        // Refresh inside modal
        $('#btnRefreshHistoryModal').on('click', function() {
          const $btn = $(this);
          $btn.prop('disabled', true).find('i').addClass('fa-spin');
          loadHistoryLogs(function() {
            $btn.prop('disabled', false).find('i').removeClass('fa-spin');
          });
        });
      });

      // -----------------------------------------------------------
      // 1. Load Stored Rolls & Render Table
      // -----------------------------------------------------------
      function loadStoreRolls(callback) {
        const search = $('#tableSearchInput').val().trim();
        const rackFilter = $('#rackFilterSelect').val();

        $.ajax({
          url: 'ajaxKnitting_rack_transfer.php',
          type: 'GET',
          data: {
            action: 'get_inventory',
            search: search,
            rack_filter: rackFilter
          },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.success && resp.data && resp.data.length > 0) {
              allRollsData = resp.data;
              renderTable(resp.data);
              $('#rollCountSummary').text(`Showing ${resp.count} rolls in store`);
            } else {
              allRollsData = [];
              $('#rollsTableBody').html('<tr><td colspan="11" class="text-center py-5 text-muted fs-5"><i class="fa-solid fa-box-open me-2 text-secondary"></i> No rolls found in store.</td></tr>');
              $('#rollCountSummary').text('Showing 0 rolls');
            }
            updateBatchToolbar();
            if (typeof callback === 'function') callback();
          },
          error: function() {
            $('#rollsTableBody').html('<tr><td colspan="11" class="text-center py-5 text-danger fs-5"><i class="fa-solid fa-triangle-exclamation me-2"></i> Failed to connect to server.</td></tr>');
            if (typeof callback === 'function') callback();
          }
        });
      }

      function renderTable(data) {
        const $tbody = $('#rollsTableBody');
        $tbody.empty();

        if (!data || data.length === 0) {
          $tbody.html('<tr><td colspan="11" class="text-center py-5 text-muted fs-5"><i class="fa-solid fa-box-open me-2 text-secondary"></i> No rolls matched the search filter.</td></tr>');
          return;
        }

        data.forEach(function(row) {
          const rawRack = row.RACKNO ? String(row.RACKNO).padStart(2, '0') : '';
          const rackLoc = row.RACKLOCATION || '';
          const fullRackDisp = rawRack ? `Rack ${rawRack}${rackLoc ? ' (' + rackLoc + ')' : ''}` : 'Unassigned';

          const safeRoll = $('<div>').text(row.ROLL).html();
          const safePO = $('<div>').text(row.PO_NUMBER || '-').html();
          const safeBuyer = $('<div>').text(row.BUYER || '-').html();
          const safeStyle = $('<div>').text(row.STYLE || '-').html();
          const safeColor = $('<div>').text(row.COLOR || '-').html();
          const safeQty = parseFloat(row.QTY || 0).toFixed(2);
          const safeMC = $('<div>').text(row.MCNO || '-').html();

          // Build row
          const tr = `
            <tr id="row-${safeRoll}">
              <td class="text-center">
                <input type="checkbox" class="form-check-input row-checkbox" value="${safeRoll}">
              </td>
              <td>
                <span class="roll-barcode-text">${safeRoll}</span>
              </td>
              <td>
                <span class="badge-curr-rack" id="badge-${safeRoll}">
                  <i class="fa-solid fa-location-dot"></i> ${fullRackDisp}
                </span>
              </td>
              <td><span class="table-data-po">${safePO}</span></td>
              <td><span class="table-data-buyer">${safeBuyer}</span></td>
              <td><span class="table-data-style">${safeStyle}</span></td>
              <td><span class="table-data-color">${safeColor}</span></td>
              <td class="text-end table-data-qty">${safeQty}</td>
              <td><span class="badge-machine">${safeMC}</span></td>
              <td>
                <div class="destination-group">
                  <div class="destination-selects-wrapper">
                    <select class="inline-rack-select" id="select-rack-${safeRoll}" onchange="window.onRackChange('${safeRoll}')">
                      ${dbRackOptionsHtml}
                    </select>
                    <select class="inline-shelf-select" id="select-shelf-${safeRoll}" onchange="window.onShelfChange('${safeRoll}')" disabled>
                      <option value="" disabled selected>Select Rack First...</option>
                    </select>
                  </div>
                  <div class="shelf-hint-text" id="shelf-hint-${safeRoll}"></div>
                </div>
              </td>
              <td class="text-center">
                <button type="button" class="btn-transfer-row" onclick="window.transferSingleRoll('${safeRoll}')" id="btn-trans-${safeRoll}">
                  <i class="fa-solid fa-arrow-right-arrow-left"></i> Transfer
                </button>
              </td>
            </tr>
          `;
          $tbody.append(tr);
        });
      }

      // -----------------------------------------------------------
      // 2. Selectable Shelf Handlers (Dynamic Database Shelves)
      // -----------------------------------------------------------
      window.onRackChange = function(roll) {
        const toRack = $(`#select-rack-${roll}`).val();
        const $shelfSelect = $(`#select-shelf-${roll}`);
        const $hint = $(`#shelf-hint-${roll}`);

        if (!toRack) {
          $shelfSelect.val('').prop('disabled', true).html('<option value="" disabled selected>Select Rack First...</option>');
          $hint.empty();
          return;
        }

        $hint.html('<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Loading shelves from database...</span>');

        // Dynamically fetch shelves for this rack directly from database
        loadDatabaseShelves(toRack, $shelfSelect, roll, function(resp) {
          $hint.html(`<span class="text-primary"><i class="fa-solid fa-hand-pointer"></i> Rack ${toRack}: ${resp.available_count} of ${resp.total_shelves} shelves empty. Choose a shelf.</span>`);
        });
      };

      window.onShelfChange = function(roll) {
        const toRack = $(`#select-rack-${roll}`).val();
        const toShelf = $(`#select-shelf-${roll}`).val();
        const $hint = $(`#shelf-hint-${roll}`);

        if (toRack && toShelf) {
          $hint.html(`<span class="text-success"><i class="fa-solid fa-circle-check"></i> Destination: Rack ${toRack} (Shelf ${toShelf})</span>`);
        }
      };

      // -----------------------------------------------------------
      // 2b. Single Roll Transfer Execution (User-Selected Rack & Shelf)
      // -----------------------------------------------------------
      window.transferSingleRoll = function(roll) {
        const toRackNo = $(`#select-rack-${roll}`).val();
        const toRackLoc = $(`#select-shelf-${roll}`).val();

        if (!toRackNo) {
          showToast('Please select a Destination Rack first.', 'warning');
          $(`#select-rack-${roll}`).focus();
          return;
        }

        if (!toRackLoc) {
          showToast('Please select a specific Shelf/Location (e.g., A1, B2) for this roll.', 'warning');
          $(`#select-shelf-${roll}`).focus();
          return;
        }

        const $btn = $(`#btn-trans-${roll}`);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Moving...');

        $.ajax({
          url: 'ajaxKnitting_rack_transfer.php',
          type: 'POST',
          data: {
            action: 'transfer_rack',
            roll: roll,
            to_rackno: toRackNo,
            to_racklocation: toRackLoc
          },
          dataType: 'json',
          success: function(resp) {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-arrow-right-arrow-left"></i> Transfer');
            if (resp && resp.success) {
              // Update badge in real-time
              const newDisp = `Rack ${resp.to_rackno}${resp.to_racklocation ? ' (' + resp.to_racklocation + ')' : ''}`;
              $(`#badge-${roll}`).html(`<i class="fa-solid fa-location-dot"></i> ${newDisp}`);

              // Highlight row
              const $row = $(`#row-${roll}`);
              $row.addClass('row-updated');
              setTimeout(function() {
                $row.removeClass('row-updated');
              }, 2500);

              // Re-check rack shelf availability for this row
              window.onRackChange(roll);

              showToast(resp.message, 'success');
              loadStats();
              loadDatabaseRacks();
            } else {
              showToast(resp.message || 'Transfer failed.', 'danger');
            }
          },
          error: function() {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-arrow-right-arrow-left"></i> Transfer');
            showToast('Server communication failed.', 'danger');
          }
        });
      };

      // -----------------------------------------------------------
      // 3. Batch Roll Transfer & Target Controls
      // -----------------------------------------------------------
      function updateBatchToolbar() {
        const checkedBoxes = $('.row-checkbox:checked');
        const count = checkedBoxes.length;

        if (count > 0) {
          $('#selectedCountBadge').html(`<i class="fa-solid fa-circle-check me-1"></i> ${count} ${count === 1 ? 'roll' : 'rolls'} selected`);
          $('#batchToolbar').addClass('active');
        } else {
          $('#batchToolbar').removeClass('active');
          $('#batchCapacityBadge').hide();
          $('#batchTargetRack').val('');
          $('#batchTargetShelf').val('').prop('disabled', true);
        }
      }

      function applyBatchTransfer() {
        const toRackNo = $('#batchTargetRack').val();
        if (!toRackNo) {
          showToast('Please select a target Destination Rack in the batch bar.', 'warning');
          $('#batchTargetRack').focus();
          return;
        }

        const toRackLocation = $('#batchTargetShelf').val();
        if (!toRackLocation) {
          showToast('Please select a target Shelf/Location (e.g., A1, B2) in the batch bar.', 'warning');
          $('#batchTargetShelf').focus();
          return;
        }

        const selectedRolls = [];
        $('.row-checkbox:checked').each(function() {
          selectedRolls.push($(this).val());
        });

        if (selectedRolls.length === 0) {
          showToast('No rolls selected.', 'warning');
          return;
        }

        const $btn = $('#btnApplyBatch');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Applying Batch...');

        $.ajax({
          url: 'ajaxKnitting_rack_transfer.php',
          type: 'POST',
          data: {
            action: 'batch_transfer',
            rolls: selectedRolls,
            to_rackno: toRackNo,
            to_racklocation: toRackLocation
          },
          dataType: 'json',
          success: function(resp) {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Apply Batch');
            if (resp && resp.success) {
              showToast(resp.message, 'success');
              $('#btnCancelBatch').trigger('click');
              loadStats();
              loadDatabaseRacks();
              loadStoreRolls();
            } else {
              showToast(resp.message || 'Batch transfer failed.', 'danger');
            }
          },
          error: function() {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Apply Batch');
            showToast('Error communicating with server.', 'danger');
          }
        });
      }

      // -----------------------------------------------------------
      // 4. Load KPI Metrics
      // -----------------------------------------------------------
      function loadStats() {
        $.ajax({
          url: 'ajaxKnitting_rack_transfer.php',
          type: 'GET',
          data: { action: 'get_stats' },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.success) {
              $('#kpiTotalRolls').text(resp.total_rolls || 0);
              $('#kpiTotalQty').text(parseFloat(resp.total_qty || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }));
              $('#kpiActiveRacks').text(resp.active_racks || 0);
              $('#kpiTransfersToday').text(resp.transfers_today || 0);

              // Update Rack Filter dropdown while preserving selection
              const curFilter = $('#rackFilterSelect').val() || 'ALL';
              const $select = $('#rackFilterSelect');
              if (databaseRacks && databaseRacks.length > 0) {
                let filterOpts = `<option value="ALL">All Active Racks (${databaseRacks.length})</option>`;
                databaseRacks.forEach(function(r) {
                  filterOpts += `<option value="${r.rack_no}">${r.rack_label} (${r.rolls_stored} rolls)</option>`;
                });
                $select.html(filterOpts);
              } else if (resp.racks_in_use && resp.racks_in_use.length > 0) {
                let filterOpts = '<option value="ALL">All Racks</option>';
                resp.racks_in_use.forEach(function(r) {
                  filterOpts += `<option value="${r}">Rack ${r}</option>`;
                });
                $select.html(filterOpts);
              }
              $select.val(curFilter);
            }
          }
        });
      }

      // -----------------------------------------------------------
      // 5. Load Audit History Logs & Modal Search
      // -----------------------------------------------------------
      let cachedHistoryLogs = [];

      function loadHistoryLogs(callback) {
        const $tbody = $('#historyTableBody');
        $tbody.html('<tr><td colspan="8" class="text-center py-5 text-muted fs-6"><i class="fa-solid fa-spinner fa-spin me-2 text-primary"></i> Loading audit records...</td></tr>');
        $('#historyCountBadge').text('Loading...');

        $.ajax({
          url: 'ajaxKnitting_rack_transfer.php',
          type: 'GET',
          data: { action: 'get_logs' },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.success && resp.data) {
              cachedHistoryLogs = resp.data;
              const searchVal = $('#modalHistorySearch').val().toLowerCase().trim();
              if (searchVal) {
                filterHistoryLogs(searchVal);
              } else {
                renderHistoryTable(cachedHistoryLogs);
              }
            } else {
              $tbody.html('<tr><td colspan="8" class="text-center py-5 text-muted fs-6">No transfer logs recorded yet.</td></tr>');
              $('#historyCountBadge').text('0 entries');
            }
            if (typeof callback === 'function') callback();
          },
          error: function() {
            $tbody.html('<tr><td colspan="8" class="text-center py-5 text-danger fs-6"><i class="fa-solid fa-triangle-exclamation me-2"></i> Failed to load transfer history.</td></tr>');
            $('#historyCountBadge').html('<i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Error');
            if (typeof callback === 'function') callback();
          }
        });
      }

      function filterHistoryLogs(query) {
        if (!query) {
          renderHistoryTable(cachedHistoryLogs);
          return;
        }
        const filtered = cachedHistoryLogs.filter(function(log) {
          const hay = `${log.id} ${log.roll} ${log.from_rack || ''} ${log.to_rack || ''} ${log.transfer_by || ''} ${log.transfer_date || ''} ${log.BUYER || ''} ${log.STYLE || ''} ${log.PO_NUMBER || ''}`.toLowerCase();
          return hay.includes(query);
        });
        renderHistoryTable(filtered);
      }

      function renderHistoryTable(logs) {
        const $tbody = $('#historyTableBody');
        $tbody.empty();

        if (!logs || logs.length === 0) {
          $tbody.html('<tr><td colspan="8" class="text-center py-5 text-muted fs-6">No matching transfer records found.</td></tr>');
          $('#historyCountBadge').html('<i class="fa-solid fa-database text-primary me-1"></i> 0 entries');
          return;
        }

        $('#historyCountBadge').html(`<i class="fa-solid fa-database text-primary me-1"></i> ${logs.length} ${logs.length === 1 ? 'entry' : 'entries'}`);

        logs.forEach(function(log) {
          const safeId = log.id;
          const safeRoll = $('<div>').text(log.roll).html();
          const safeFrom = $('<div>').text(log.from_rack || '-').html();
          const safeTo = $('<div>').text(log.to_rack || '-').html();
          const safeBy = $('<div>').text(log.transfer_by || 'User').html();
          const safeDate = $('<div>').text(log.transfer_date).html();
          const safeBuyer = $('<div>').text(log.BUYER || '-').html();
          const safeStyle = $('<div>').text(log.STYLE ? ' / ' + log.STYLE : '').html();

          const tr = `
            <tr>
              <td class="ps-4">
                <span class="badge bg-light text-secondary border px-2 py-1 fw-bold font-monospace">#${safeId}</span>
              </td>
              <td>
                <span class="roll-barcode-text" style="font-size: 0.95rem;">${safeRoll}</span>
              </td>
              <td>
                <span class="history-from-rack" style="color: #0f172a !important; font-weight: 800; background: #f1f5f9 !important; border: 1.5px solid #cbd5e1 !important; opacity: 1 !important;">
                  <i class="fa-solid fa-location-dot me-1" style="color: #475569 !important;"></i> ${safeFrom}
                </span>
              </td>
              <td class="text-center px-1">
                <i class="fa-solid fa-arrow-right-long fs-6 text-primary"></i>
              </td>
              <td>
                <span class="history-to-rack" style="color: #065f46 !important; font-weight: 800; background: #ecfdf5 !important; border: 1.5px solid #6ee7b7 !important; opacity: 1 !important;">
                  <i class="fa-solid fa-circle-check me-1" style="color: #059669 !important;"></i> ${safeTo}
                </span>
              </td>
              <td>
                <span class="badge bg-dark-subtle text-dark px-2.5 py-1.5 fw-bold" style="font-size: 0.86rem;">
                  <i class="fa-solid fa-user-check me-1 text-primary"></i> ${safeBy}
                </span>
              </td>
              <td>
                <span class="text-muted fw-semibold" style="font-size: 0.86rem;">
                  <i class="fa-regular fa-clock me-1 text-secondary"></i> ${safeDate}
                </span>
              </td>
              <td class="pe-4">
                <span class="fw-bold text-dark small">${safeBuyer}</span> <span class="text-muted small">${safeStyle}</span>
              </td>
            </tr>
          `;
          $tbody.append(tr);
        });
      }

      // -----------------------------------------------------------
      // 6. Toast Notification
      // -----------------------------------------------------------
      function showToast(msg, type = 'success') {
        const $toast = $('#toastAlert');
        const $icon = $toast.find('i');
        const $msg = $('#toastMessage');

        if (type === 'success') {
          $toast.css('border-left-color', '#10b981');
          $icon.attr('class', 'fa-solid fa-circle-check fs-4 text-success');
        } else if (type === 'warning') {
          $toast.css('border-left-color', '#f59e0b');
          $icon.attr('class', 'fa-solid fa-triangle-exclamation fs-4 text-warning');
        } else {
          $toast.css('border-left-color', '#ef4444');
          $icon.attr('class', 'fa-solid fa-circle-xmark fs-4 text-danger');
        }

        $msg.text(msg);
        $toast.stop(true, true).fadeIn(200);

        setTimeout(function() {
          $toast.fadeOut(300);
        }, 4000);
      }

    })();
  </script>
</body>
</html>