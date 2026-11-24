<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Operator';
$userRole    = $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? 'Staff';

// Rack numbers are fixed from 01 through 50; each rack uses the same fixed shelves.
$initialMasterRacks = [];
for ($r = 1; $r <= 50; $r++) {
    $initialMasterRacks[] = str_pad($r, 2, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Store Rack Transfer | Knitting Store</title>
  <!-- Fonts & Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
      font-size: 1.15rem;
    }

    /* Top Bar */
    .top-navbar {
      background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%);
      border-bottom: 3px solid #0284c7;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
      padding: 14px 38px;
      margin-bottom: 22px;
      position: relative;
      z-index: 100;
    }

    .header-nav-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      gap: 22px;
    }

    .nav-left {
      display: flex;
      align-items: center;
      flex: 0 0 auto;
    }

    .btn-nav-dashboard {
      background: #000000;
      color: #ffffff;
      border: 1.5px solid #000000;
      border-radius: 12px;
      padding: 11px 22px;
      font-weight: 700;
      font-size: 1.05rem;
      text-decoration: none;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }

    .btn-nav-dashboard:hover {
      background: #ffffff;
      color: #000000;
      border-color: #000000;
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
    }

    .btn-nav-dashboard i {
      font-size: 1.15rem;
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
      gap: 16px;
    }

    .brand-icon {
      width: 48px;
      height: 48px;
      border-radius: 13px;
      background: rgba(56, 189, 248, 0.15);
      border: 1.5px solid rgba(56, 189, 248, 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #38bdf8;
      font-size: 1.5rem;
      box-shadow: 0 0 16px rgba(56, 189, 248, 0.2);
    }

    .brand-text-group {
      display: flex;
      flex-direction: column;
    }

    .brand-main-title {
      font-size: 1.78rem;
      font-weight: 800;
      color: #ffffff;
      letter-spacing: -0.4px;
      margin: 0;
      line-height: 1.2;
    }

    .brand-subtitle {
      font-size: 0.92rem;
      font-weight: 600;
      color: #94a3b8;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .nav-right {
      display: flex;
      align-items: center;
      gap: 18px;
      flex: 0 0 auto;
    }

    .user-profile-badge {
      display: flex;
      align-items: center;
      gap: 14px;
      background: rgba(255, 255, 255, 0.06);
      border: 1.5px solid rgba(255, 255, 255, 0.12);
      border-radius: 13px;
      padding: 9px 18px;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 11px;
      background: linear-gradient(135deg, #0284c7, #0d9488);
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35);
    }

    .user-details {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }

    .user-name {
      font-weight: 800;
      font-size: 1.08rem;
      color: #f8fafc;
      letter-spacing: -0.2px;
    }

    .user-role {
      font-size: 0.86rem;
      font-weight: 600;
      color: #38bdf8;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn-nav-history {
      display: inline-flex;
      align-items: center;
      gap: 11px;
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #ffffff;
      border: 1.5px solid #38bdf8;
      border-radius: 12px;
      padding: 11px 22px;
      font-weight: 700;
      font-size: 1.05rem;
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
      border-radius: 16px;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
      transition: all 0.2s ease;
    }

    .kpi-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(15, 23, 42, 0.07);
      border-color: rgba(2, 132, 199, 0.3);
    }

    .kpi-icon {
      width: 48px;
      height: 48px;
      border-radius: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
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
      font-size: 0.9rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #64748b;
      margin-bottom: 2px;
      white-space: nowrap;
    }

    .kpi-val {
      font-size: 1.9rem;
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
      padding: 22px 34px;
      background: #ffffff;
      border-bottom: 1.5px solid #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 18px;
    }

    .table-panel-header h5 {
      font-weight: 800;
      font-size: 1.45rem;
      color: #0f172a;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 13px;
      letter-spacing: -0.3px;
    }

    /* ========================================================= */
    /* SLEEK FLOATING BATCH ACTIONS BAR                         */
    /* ========================================================= */
    .batch-toolbar {
      position: fixed;
      top: 50%;
      bottom: auto;
      left: 50%;
      transform: translate(-50%, calc(-50% + 80px)) scale(0.96);
      z-index: 1040;
      background: #eff6ff;
      border: 1.5px solid #93c5fd;
      border-radius: 14px;
      box-shadow: 0 12px 30px rgba(30, 64, 175, 0.2);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease, visibility 0.3s;
      max-width: 95vw;
    }

    .batch-toolbar.active {
      transform: translate(-50%, -50%) scale(1);
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
    }

    .batch-toolbar-content {
      display: flex;
      align-items: center;
      gap: 18px;
      padding: 11px 22px;
      white-space: nowrap;
      flex-wrap: nowrap;
      justify-content: center;
    }

    .batch-center {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .batch-control-row {
      display: flex;
      align-items: center;
      gap: 12px;
      white-space: nowrap;
    }

    .batch-center .shelf-picker {
      flex: 0 0 180px;
      min-width: 180px;
    }

    .batch-center .shelf-picker-menu {
      position: absolute;
      top: auto !important;
      right: 0;
      bottom: calc(100% + 5px);
      left: auto !important;
      width: 210px;
    }

    .batch-rack-select {
      background: #ecfdf5;
      color: #065f46;
      border: 1.5px solid #6ee7b7;
      border-radius: 12px;
      padding: 8px 16px;
      font-size: 1.05rem;
      font-weight: 700;
      outline: none;
      cursor: pointer;
      min-width: 180px;
      transition: all 0.2s ease;
    }

    .batch-rack-select:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.18);
    }

    .batch-rack-select option {
      background: #ffffff;
      color: #065f46;
      font-size: 1.05rem;
      padding: 8px;
    }

    .batch-right {
      display: flex;
      align-items: center;
      gap: 11px;
    }

    .btn-batch-apply {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      padding: 10px 21px;
      font-size: 1.05rem;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      gap: 7px;
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
      padding: 10px 17px;
      font-size: 1.05rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 5px;
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
      padding: 24px 34px 12px;
      display: flex;
      gap: 18px;
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
      padding: 14px 22px 14px 50px;
      border-radius: 12px;
      border: 1.5px solid #cbd5e1;
      font-size: 1.18rem;
      font-weight: 600;
      color: #0f172a;
      width: 100%;
      transition: all 0.2s;
      background: #f8fafc;
    }

    .search-box input:focus {
      background: #ffffff;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
      outline: none;
    }

    .search-box i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 1.2rem;
    }

    .rack-filter-select {
      border: 1.5px solid #cbd5e1;
      border-radius: 12px;
      padding: 13px 20px;
      font-size: 1.12rem;
      font-weight: 700;
      color: #0f172a;
      background: #f8fafc;
      outline: none;
      cursor: pointer;
      min-width: 185px;
      transition: all 0.2s;
    }

    .rack-filter-select:focus {
      background: #ffffff;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
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
      font-size: 1.12rem;
      white-space: nowrap;
    }

    .rack-table th {
      background: #f8fafc;
      color: #0f172a;
      font-weight: 800;
      font-size: 1.05rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 18px 22px;
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
      padding: 16px 22px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      color: #0f172a;
      font-size: 1.12rem;
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
      border: 1.5px solid #94a3b8;
    }

    /* Roll Barcode Monospace Asset Tag */
    .roll-barcode-text {
      background: #f0f9ff;
      color: #0284c7;
      border: 1.5px solid #bae6fd;
      padding: 6px 15px;
      border-radius: 8px;
      font-weight: 800;
      font-size: 1.22rem;
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
      font-size: 1.1rem;
      padding: 7px 16px;
      border-radius: 10px;
      letter-spacing: 0.2px;
      box-shadow: 0 1px 3px rgba(133, 77, 14, 0.06);
    }

    /* Table Typography Enhancements */
    .table-data-po {
      font-weight: 800;
      font-size: 1.1rem;
      color: #0f172a;
    }

    .table-data-buyer {
      font-weight: 700;
      font-size: 1.1rem;
      color: #1e293b;
    }

    .table-data-style {
      font-weight: 700;
      font-size: 1.1rem;
      color: #334155;
    }

    .table-data-color {
      font-weight: 700;
      font-size: 1.1rem;
      color: #475569;
    }

    .table-data-qty {
      font-weight: 800;
      font-size: 1.25rem;
      color: #0f172a;
      font-family: monospace;
    }

    .badge-machine {
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #cbd5e1;
      border-radius: 7px;
      padding: 5px 12px;
      font-weight: 700;
      font-size: 1rem;
    }

    /* Inline Destination Rack & Shelf Controls */
    .destination-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
      min-width: 295px;
    }

    .destination-selects-wrapper {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .inline-rack-select {
      font-weight: 700;
      font-size: 1.08rem;
      padding: 9px 14px;
      border-radius: 10px;
      border: 1.5px solid #cbd5e1;
      color: #0f172a;
      background: #ffffff;
      outline: none;
      transition: all 0.2s ease;
      width: 138px;
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
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    .inline-shelf-select {
      font-weight: 700;
      font-size: 1.08rem;
      padding: 9px 14px;
      border-radius: 10px;
      border: 1.5px solid #cbd5e1;
      color: #0f172a;
      background: #f8fafc;
      outline: none;
      transition: all 0.2s ease;
      min-width: 158px;
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
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    .shelf-picker {
      position: relative;
      flex: 1;
      min-width: 120px;
    }

    .shelf-picker-trigger {
      position: relative;
      width: 100%;
      min-height: 38px;
      padding: 7px 28px 7px 10px;
      border: 1px solid #cbd5e1;
      border-radius: 7px;
      background: #fff;
      color: #334155;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 400;
      text-align: left;
      white-space: nowrap;
      cursor: pointer;
    }

    .shelf-picker-trigger::after {
      position: absolute;
      top: 50%;
      right: 11px;
      width: 7px;
      height: 7px;
      border-right: 1.5px solid currentColor;
      border-bottom: 1.5px solid currentColor;
      content: "";
      transform: translateY(-70%) rotate(45deg);
    }

    .shelf-picker-trigger:disabled {
      background: #f1f5f9;
      color: #94a3b8;
      cursor: not-allowed;
    }

    .shelf-picker-menu {
      position: fixed;
      z-index: 2100;
      display: grid;
      width: 190px;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 6px;
      padding: 8px;
      border: 1px solid #cbd5e1;
      border-radius: 9px;
      background: #fff;
      box-shadow: 0 8px 22px rgba(15, 23, 42, .16);
    }

    .shelf-picker-menu[hidden] { display: none; }

    .shelf-grid-option {
      min-height: 42px;
      border: 1px solid #000000;
      border-radius: 6px;
      background: #111827;
      color: #ffffff;
      font: inherit;
      font-size: 1.05rem;
      font-weight: 700;
      cursor: pointer;
    }

    .shelf-grid-option:hover,
    .shelf-grid-option:focus-visible {
      border-color: #10b981;
      background: #000000;
      outline: 2px solid rgba(16, 185, 129, .35);
    }

    /* Inline Transfer Button */
    .btn-transfer-row {
      background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
      color: #ffffff;
      border: none;
      border-radius: 10px;
      padding: 10px 20px;
      font-size: 1.08rem;
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

    .toast-alert.toast-centered {
      top: 50%;
      right: auto;
      left: 50%;
      transform: translate(-50%, -50%);
      border-left: 0;
      border-top: 5px solid #10b981;
      min-width: min(360px, calc(100vw - 32px));
      justify-content: center;
      text-align: center;
      animation: centeredToastIn 0.25s ease;
    }

    @keyframes toastIn {
      from { opacity: 0; transform: translateX(40px); }
      to { opacity: 1; transform: translateX(0); }
    }

    @keyframes centeredToastIn {
      from { opacity: 0; transform: translate(-50%, -45%); }
      to { opacity: 1; transform: translate(-50%, -50%); }
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
      font-size: 1.6rem;
      font-weight: 800;
      color: #ffffff;
      margin: 0;
      letter-spacing: -0.2px;
      line-height: 1.25;
    }

    .history-modal-subtitle {
      font-size: 1.05rem;
      font-weight: 500;
      color: #94a3b8;
      margin: 0;
      letter-spacing: 0.1px;
    }

    .btn-modal-dismiss {
      background: rgba(255, 255, 255, 0.08);
      border: 1.5px solid rgba(255, 255, 255, 0.18);
      color: #cbd5e1;
      width: 44px;
      height: 44px;
      min-width: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1.3rem;
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
      padding: 18px 34px;
      background: #f8fafc;
      border-bottom: 1.5px solid #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }

    .history-search-wrapper {
      position: relative;
      flex: 1;
      min-width: 320px;
      max-width: 520px;
    }

    .history-search-wrapper i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 1.2rem;
      pointer-events: none;
    }

    .history-search-input {
      width: 100%;
      padding: 13px 20px 13px 48px;
      border-radius: 12px;
      border: 1.5px solid #cbd5e1;
      background: #ffffff;
      font-size: 1.18rem;
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
      gap: 14px;
    }

    .history-count-pill {
      background: #e2e8f0;
      color: #334155;
      border: 1px solid #cbd5e1;
      border-radius: 20px;
      padding: 8px 18px;
      font-size: 1.08rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-refresh-history {
      background: #ffffff;
      color: #0284c7;
      border: 1.5px solid #0284c7;
      border-radius: 12px;
      padding: 10px 22px;
      font-size: 1.08rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 9px;
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
      max-height: 550px;
      overflow-y: auto;
      padding: 0;
      background: #ffffff;
    }

    .history-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 1.12rem;
    }

    .history-table thead th {
      background: #f8fafc;
      color: #475569;
      font-size: 1.05rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 18px 22px;
      border-bottom: 2px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .history-table tbody td {
      padding: 18px 22px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      font-weight: 600;
      font-size: 1.12rem;
      color: #1e293b;
    }

    .history-table tbody tr:hover {
      background: #f8fafc;
    }

    .history-from-rack {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #f1f5f9 !important;
      color: #0f172a !important; /* Explicit high-contrast dark slate */
      border: 1.5px solid #cbd5e1 !important;
      border-radius: 9px;
      padding: 7px 16px;
      font-size: 1.12rem;
      font-weight: 800;
      letter-spacing: 0.2px;
      opacity: 1 !important;
      line-height: 1.3;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .history-from-rack i {
      color: #475569 !important;
      font-size: 1rem;
    }

    .history-to-rack {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #ecfdf5 !important;
      color: #065f46 !important; /* Vibrant high-contrast emerald */
      border: 1.5px solid #6ee7b7 !important;
      border-radius: 9px;
      padding: 7px 16px;
      font-size: 1.12rem;
      font-weight: 800;
      letter-spacing: 0.2px;
      opacity: 1 !important;
      line-height: 1.3;
      box-shadow: 0 1px 2px rgba(16, 185, 129, 0.08);
    }
    .history-to-rack i {
      color: #059669 !important;
      font-size: 1rem;
    }

    .history-modal-footer {
      padding: 18px 34px;
      background: #f8fafc;
      border-top: 1.5px solid #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px;
      font-size: 1.08rem;
    }

    .btn-close-modal-bottom {
      background: #475569;
      color: #ffffff;
      border: none;
      border-radius: 12px;
      padding: 11px 28px;
      font-size: 1.08rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-close-modal-bottom:hover {
      background: #334155;
      transform: translateY(-1px);
    }

    /* Unified, compact warehouse dashboard theme */
    body {
      padding: clamp(8px, 2vw, 16px);
      padding-bottom: 36px;
      background: #f1f5f9;
      color: #1e293b;
      font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
      font-size: 0.95rem;
    }

    .top-navbar {
      margin: 0 0 12px;
      padding: 10px 14px;
      border: 0;
      border-radius: 10px;
      border-bottom: 3px solid #34d399;
      background: linear-gradient(115deg, #0f766e, #0d8a80 52%, #0f9b8e);
      box-shadow: 0 6px 18px rgba(15, 70, 65, .16);
    }

    .header-nav-container {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
      gap: 12px;
      min-height: 48px;
    }
    .nav-left { grid-column: 1; grid-row: 1; }
    .nav-center { grid-column: 2; grid-row: 1; }
    .brand-title { gap: 12px; }
    .brand-icon {
      width: 42px;
      height: 42px;
      border-radius: 11px;
      background: rgba(255,255,255,.14);
      border-color: rgba(255,255,255,.24);
      color: #fff;
      box-shadow: none;
    }
    .brand-main-title { font-size: clamp(1.15rem, 2.2vw, 1.55rem); }
    .brand-subtitle { font-size: .75rem; color: #d1fae5; }
    .btn-nav-dashboard, .btn-nav-history {
      min-height: 40px;
      padding: 8px 13px;
      border-radius: 8px;
      font-size: .88rem;
      box-shadow: none;
    }
    .btn-nav-dashboard:hover { transform: translateY(-1px); }

    .container-fluid {
      max-width: 1800px;
      margin: 0 auto;
      padding-right: 8px !important;
      padding-left: 8px !important;
      padding-top: 0 !important;
      padding-bottom: 0 !important;
    }
    .kpi-card {
      min-height: 82px;
      padding: 12px 15px;
      gap: 12px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(15,23,42,.045);
    }
    .kpi-card:hover { transform: translateY(-1px); }
    .kpi-icon { width: 40px; height: 40px; border-radius: 10px; font-size: 1.1rem; }
    .kpi-label { font-size: .72rem; letter-spacing: .35px; }
    .kpi-val { font-size: 1.45rem; }

    .table-panel {
      border-radius: 12px;
      border-color: #dbe3ee;
      box-shadow: 0 5px 16px rgba(15,23,42,.055);
    }
    .table-panel-header {
      min-height: 58px;
      padding: 12px 16px;
      gap: 10px;
    }
    .table-panel-header h5 { gap: 9px; font-size: 1.05rem; letter-spacing: 0; }
    .filter-bar {
      align-items: stretch;
      gap: 10px;
      padding: 12px 16px;
      border-bottom: 1px solid #e2e8f0;
      background: #f8fafc;
    }
    .search-box { min-width: 220px; }
    .search-box input {
      min-height: 40px;
      padding: 9px 12px 9px 38px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      background: #fff;
      font-size: .9rem;
      font-weight: 500;
    }
    .search-box i { left: 13px; font-size: .95rem; }
    .rack-filter-select {
      min-height: 40px;
      min-width: 190px;
      padding: 8px 11px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      background: #fff;
      font-size: .88rem;
      font-weight: 600;
    }
    .table-container { padding: 0 16px 16px; }
    .rack-table {
      min-width: 1120px;
      border-collapse: separate;
      border-spacing: 0;
      font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
      font-size: .9rem;
    }
    .rack-table th {
      position: sticky;
      top: 0;
      z-index: 2;
      padding: 11px 10px;
      border: 0;
      border-bottom: 3px solid #0f766e;
      border-radius: 0 !important;
      background: #134e4a;
      color: #fff;
      font-size: .78rem;
      letter-spacing: .3px;
      white-space: nowrap;
    }
    .rack-table th:first-child { border-top-left-radius: 8px !important; }
    .rack-table th:last-child { border-top-right-radius: 8px !important; }
    .rack-table td {
      padding: 9px 10px;
      border-bottom: 1px solid #e2e8f0;
      color: #334155;
      font-size: .88rem;
      line-height: 1.35;
    }
    .rack-table tbody tr:nth-child(even) td { background: #f8fafc; }
    .rack-table tbody tr:hover td { background: #eff6ff; }
    .rack-table tr.row-updated td { background: #ecfdf5 !important; }
    .rack-table tr.row-selected td { background: #e8f3ff !important; }
    .rack-table tr.row-selected td:first-child { border-left: 3px solid #0f766e; }
    .form-check-input { width: 18px; height: 18px; }
    .roll-barcode-text {
      padding: 5px 9px;
      border-radius: 7px;
      font-size: .85rem;
      letter-spacing: 0;
    }
    .badge-curr-rack {
      gap: 5px;
      padding: 5px 9px;
      border-radius: 7px;
      font-size: .82rem;
      box-shadow: none;
    }
    .table-data-po, .table-data-color, .table-data-fabric, .table-data-date {
      font-size: .88rem;
    }
    .table-data-qty { font-size: .92rem; }
    .destination-group { min-width: 240px; gap: 4px; }
    .destination-selects-wrapper { gap: 6px; }
    .inline-rack-select, .inline-shelf-select {
      min-height: 38px;
      padding: 7px 9px;
      border-radius: 7px;
      font-size: .82rem;
    }
    .inline-rack-select { width: 104px; }
    .inline-shelf-select { min-width: 120px; }
    .shelf-picker-trigger { min-height: 38px; padding: 7px 25px 7px 9px; font-size: .92rem; }
    .btn-transfer-row {
      padding: 7px 10px;
      border-radius: 7px;
      font-size: .8rem;
      box-shadow: none;
    }
    .rack-table th { padding: 9px 7px; font-size: 1rem; }
    .rack-table td {
      padding: 6px 7px;
      color: #334155;
      font-size: 1rem;
      font-weight: 500;
      line-height: 1.2;
    }
    .rack-table td .roll-barcode-text,
    .rack-table td .badge-curr-rack,
    .rack-table td .table-data-date,
    .rack-table td.table-data-qty,
    .rack-table td .table-data-qty,
    .rack-table td .table-data-fabric {
      padding: 0;
      border: 0;
      border-radius: 0;
      background: transparent;
      box-shadow: none;
      color: #334155;
      font-family: inherit;
      font-size: 1.2rem;
      font-weight: 400;
      letter-spacing: 0;
    }
    .rack-table td .table-data-po,
    .rack-table td .table-data-color {
      padding: 0;
      border: 0;
      border-radius: 0;
      background: transparent;
      color: #334155;
      font-family: inherit;
      font-size: 1.2rem;
      font-weight: 400;
      line-height: 1.15;
      letter-spacing: 0;
    }
    .rack-table td .badge-curr-rack { gap: 5px; }
    .rack-table td .badge-curr-rack i { color: inherit; }
    .rack-table td .inline-rack-select,
    .rack-table td .inline-shelf-select { font-size: .92rem; }
    .rack-table td .btn-transfer-row { font-size: .88rem; }

    .batch-toolbar {
      top: 50%;
      bottom: auto;
      border: 1px solid #93c5fd;
      border-radius: 15px;
      background: #eff6ff;
      box-shadow: 0 12px 30px rgba(30,64,175,.2);
    }
    .batch-toolbar-content { justify-content: center; gap: 16px; min-height: 84px; padding: 15px 24px; }
    .batch-right { gap: 12px; }
    .batch-rack-select {
      min-height: 50px;
      min-width: 175px;
      padding: 10px 14px;
      border-radius: 9px;
      font-size: 1.05rem;
      background: #d1fae5;
      color: #065f46;
      border: 1.5px solid #34d399;
    }
    .batch-center .shelf-picker-trigger {
      min-height: 50px;
      padding: 10px 30px 10px 13px;
      background: #dbeafe;
      border: 1.5px solid #60a5fa;
      color: #1e40af;
      font-size: 1.05rem;
      font-weight: 600;
    }
    .batch-center .shelf-picker-trigger:hover:not(:disabled),
    .batch-center .shelf-picker-trigger:focus-visible {
      background: #bfdbfe;
      border-color: #3b82f6;
      outline: none;
      box-shadow: 0 0 0 3px rgba(59,130,246,.16);
    }
    .batch-center .shelf-picker-trigger:disabled {
      background: #e2e8f0;
      border-color: #cbd5e1;
      color: #64748b;
    }
    .btn-batch-apply, .btn-batch-cancel {
      min-height: 50px;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: .98rem;
      box-shadow: none;
    }
    .btn-batch-apply { background: #059669; padding-left: 20px; }
    .btn-batch-apply:hover { background: #047857; transform: none; }
    .btn-batch-cancel {
      padding: 10px 14px;
      background: #dc2626;
      color: #ffffff;
      border: 1px solid #b91c1c;
    }
    .btn-batch-cancel:hover {
      background: #b91c1c;
      color: #ffffff;
      border-color: #991b1b;
    }

    .history-modal-container { max-width: 1180px; border-radius: 14px; }
    .history-modal-header {
      padding: 14px 18px;
      border-bottom: 3px solid #34d399;
      background: linear-gradient(115deg, #0f766e, #0d8a80);
    }
    .history-header-icon-box { width: 40px; height: 40px; min-width: 40px; border-radius: 10px; }
    .history-modal-title { font-size: 1.15rem; }
    .history-modal-subtitle { font-size: .82rem; color: #d1fae5; }
    .history-modal-search { gap: 10px; padding: 11px 16px; }
    .history-search-wrapper { min-width: 220px; }
    .history-search-input { padding: 9px 11px 9px 37px; border-radius: 8px; font-size: .88rem; }
    .history-search-wrapper i { left: 13px; font-size: .95rem; }
    .history-actions-wrapper { gap: 8px; }
    .history-count-pill { padding: 6px 10px; font-size: .8rem; }
    .btn-refresh-history, .btn-close-modal-bottom {
      min-height: 38px;
      padding: 7px 11px;
      border-radius: 7px;
      font-size: .82rem;
    }
    .history-modal-body { max-height: min(58vh, 560px); }
    .history-table { font-size: .84rem; }
    .history-table thead th { padding: 10px 11px; font-size: .75rem; }
    .history-table tbody td { padding: 10px 11px; font-size: .84rem; }
    .history-modal-footer { padding: 11px 16px; font-size: .82rem; }

    .toast-alert {
      top: 16px;
      right: 16px;
      padding: 11px 16px;
      border-radius: 10px;
      font-size: .88rem;
    }

    @media (max-width: 992px) {
      .top-navbar { padding: 10px 12px; }
      .header-nav-container { grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); }
      .table-panel-header { padding: 11px 13px; }
      .table-container { padding: 0 12px 12px; }
    }

    @media (max-width: 700px) {
      body { padding-bottom: 100px; }
      .top-navbar { padding: 8px; }
      .header-nav-container {
        grid-template-columns: auto minmax(0, 1fr);
        gap: 8px;
        min-height: 42px;
      }
      .nav-left { grid-column: 1; }
      .nav-center { grid-column: 2; justify-content: flex-end; }
      .brand-icon { width: 32px; height: 32px; font-size: .95rem; }
      .brand-title { gap: 7px; }
      .brand-main-title { font-size: clamp(.85rem, 3.8vw, 1rem); white-space: nowrap; }
      .brand-subtitle { font-size: .56rem; letter-spacing: .25px; }
      .btn-nav-dashboard { min-height: 34px; padding: 6px 8px; font-size: .7rem; gap: 5px; }
      .btn-nav-dashboard i { font-size: .75rem; }
      .container-fluid { padding-right: 10px !important; padding-left: 10px !important; }
      .kpi-card { min-height: 70px; padding: 9px 10px; gap: 8px; }
      .kpi-icon { width: 34px; height: 34px; font-size: .95rem; }
      .kpi-label { white-space: normal; font-size: .63rem; }
      .kpi-val { font-size: 1.18rem; }
      .table-panel-header { align-items: flex-start; flex-direction: column; }
      .filter-bar { flex-direction: column; padding: 10px; }
      .search-box { min-width: 0; width: 100%; }
      .rack-filter-select { width: 100%; }
      .table-container { padding: 0 8px 10px; }
      .batch-toolbar { top: 50%; bottom: auto; width: calc(100% - 16px); max-width: none; }
      .batch-toolbar-content { flex-wrap: nowrap; justify-content: center; gap: 4px; padding: 6px; }
      .batch-center { flex: 0 1 auto; min-width: 0; }
      .batch-control-row { min-width: 0; gap: 8px; }
      .batch-center .shelf-picker { flex: 0 1 120px; min-width: 96px; }
      .batch-center .shelf-picker-trigger {
        min-height: 44px;
        padding: 7px 21px 7px 7px;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: .88rem;
      }
      .batch-rack-select {
        flex: 0 1 108px;
        min-width: 92px;
        width: 108px;
        min-height: 44px;
        padding: 7px 5px;
        font-size: .88rem;
      }
      .batch-right { flex: 0 0 auto; justify-content: center; gap: 6px; }
      .btn-batch-apply, .btn-batch-cancel { min-height: 44px; padding: 7px 8px; font-size: .8rem; white-space: nowrap; }
      .btn-batch-apply { padding-left: 13px; }
      .batch-toolbar-content { min-height: 60px; }
      .history-modal-wrapper { padding: 10px; }
      .history-modal-header, .history-modal-search, .history-modal-footer { padding: 10px 12px; }
      .history-modal-title { font-size: 1rem; }
      .history-modal-subtitle { font-size: .73rem; }
      .history-search-wrapper { min-width: 100%; }
      .history-actions-wrapper { width: 100%; justify-content: space-between; }
      .history-modal-footer { align-items: flex-start; }
    }

    @media (max-width: 380px) {
      .btn-nav-dashboard span { display: none; }
      .brand-main-title { font-size: .82rem; }
      .brand-subtitle { font-size: .5rem; }
      .batch-toolbar-content { flex-wrap: wrap; row-gap: 6px; padding: 8px; }
      .batch-center, .batch-control-row { width: 100%; justify-content: center; }
      .batch-center .shelf-picker { flex: 1 1 110px; max-width: 140px; }
      .batch-rack-select { flex: 1 1 110px; max-width: 140px; }
      .batch-right { width: 100%; }
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
          <span>Back</span>
        </a>
      </div>

      <!-- Center: Title & Icon -->
      <div class="nav-center">
        <div class="brand-title">
          <span class="brand-icon">
            <i class="fa-solid fa-arrows-split-up-and-left"></i>
          </span>
          <div class="brand-text-group">
            <h1 class="brand-main-title">Store Rack Transfer</h1>
          </div>
        </div>
      </div>

    </div>
  </header>

  <div class="container-fluid px-3 px-md-5 py-3">

    <!-- Main Table Panel -->
    <div class="table-panel">
      
      <!-- Filter Controls -->
      <div class="filter-bar">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="tableSearchInput" placeholder="Search Date, Roll No, PO No, Color, Rack...">
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
          
          <!-- Center: Target Rack, Shelf, and actions -->
          <div class="batch-center">
            <div class="batch-control-row">
              <select class="batch-rack-select" id="batchTargetRack">
                <option value="" disabled selected>Select Rack...</option>
                <?php foreach ($initialMasterRacks as $pad): ?>
                    <option value="<?php echo $pad; ?>">Rack <?php echo $pad; ?></option>
                <?php endforeach; ?>
              </select>
              <div class="shelf-picker">
                <input type="hidden" class="batch-shelf-select" id="batchTargetShelf" disabled>
                <button type="button" class="shelf-picker-trigger" aria-haspopup="grid" aria-expanded="false" disabled>Select Rack First...</button>
                <div class="shelf-picker-menu" role="grid" hidden></div>
              </div>
            </div>
          </div>

          <div class="batch-right">
            <button type="button" class="btn-batch-apply" id="btnApplyBatch">
              <i class="fa-solid fa-check"></i> Update Rack
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
              <th>Date</th>
              <th>Roll No</th>
              <th>PO No</th>
              <th>Color</th>
              <th class="text-end">QTY</th>
              <th>Fabrics Type</th>
              <th>Current Rack</th>
              <th style="min-width: 250px;">Destination (Rack & Shelf)</th>
              <th style="width: 130px;" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody id="rollsTableBody">
            <tr>
              <td colspan="10" class="text-center py-5 text-muted fs-5">
                <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading store rolls...
              </td>
            </tr>
          </tbody>
        </table>
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        const $picker = $shelfSelect.closest('.shelf-picker');
        resetShelfPicker($shelfSelect, 'Loading shelves...');
        
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
              const shelfOrder = ['A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3'];
              const order = new Map(shelfOrder.map(function(shelf, index) { return [shelf, index]; }));
              const shelves = resp.data.slice().sort(function(a, b) {
                const aOrder = order.has(a.shelf) ? order.get(a.shelf) : shelfOrder.length;
                const bOrder = order.has(b.shelf) ? order.get(b.shelf) : shelfOrder.length;
                return aOrder - bOrder || String(a.shelf).localeCompare(String(b.shelf));
              });
              if ($picker.length) {
                const $menu = $picker.find('.shelf-picker-menu').empty();
                shelves.forEach(function(item) {
                  $('<button>', {
                    type: 'button',
                    class: 'shelf-grid-option',
                    'data-shelf': item.shelf
                  }).text(item.shelf).appendTo($menu);
                });
                $shelfSelect.val('').prop('disabled', false);
                $picker.find('.shelf-picker-trigger').prop('disabled', false).text('Select Shelf...');
              } else {
                $shelfSelect.empty().append($('<option>', { value: '', disabled: true, selected: true }).text('Select Shelf...'));
                shelves.forEach(function(item) {
                  $shelfSelect.append($('<option>', { value: item.shelf }).text(item.shelf));
                });
                $shelfSelect.prop('disabled', false);
              }
              if (typeof callback === 'function') callback(resp);
            } else {
              resetShelfPicker($shelfSelect, 'No shelves found');
            }
          },
          error: function() {
            resetShelfPicker($shelfSelect, 'Error loading shelves');
          }
        });
      }

      function resetShelfPicker($shelfSelect, label) {
        const $picker = $shelfSelect.closest('.shelf-picker');
        if (!$picker.length) {
          $shelfSelect.val('').prop('disabled', true)
            .empty().append($('<option>', { value: '', disabled: true, selected: true }).text(label));
          return;
        }
        $shelfSelect.val('').prop('disabled', true);
        $picker.find('.shelf-picker-trigger').prop('disabled', true).text(label).attr('aria-expanded', 'false');
        $picker.find('.shelf-picker-menu').prop('hidden', true).empty();
      }

      $(document).ready(function() {
        // Load master racks before fetching stored rolls.
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

        $(document).on('click', '.shelf-picker-trigger', function(event) {
          event.stopPropagation();
          const $picker = $(this).closest('.shelf-picker');
          const $menu = $picker.find('.shelf-picker-menu');
          const willOpen = $menu.prop('hidden');
          $('.shelf-picker-menu').prop('hidden', true);
          $('.shelf-picker-trigger').attr('aria-expanded', 'false');
          if (!willOpen) return;

          if (!$picker.closest('.batch-center').length) {
            const bounds = this.getBoundingClientRect();
            const menuHeight = Math.min(160, Math.ceil($menu.children().length / 3) * 40 + 18);
            const top = bounds.bottom + menuHeight + 8 > window.innerHeight
              ? Math.max(8, bounds.top - menuHeight - 4)
              : bounds.bottom + 4;
            const left = Math.min(bounds.left, window.innerWidth - 198);
            $menu.css({ top: top + 'px', left: Math.max(8, left) + 'px' });
          }
          $menu.prop('hidden', false);
          $(this).attr('aria-expanded', 'true');
        });

        $(document).on('click', '.shelf-grid-option', function(event) {
          event.stopPropagation();
          const $picker = $(this).closest('.shelf-picker');
          const shelf = $(this).attr('data-shelf');
          const $shelfSelect = $picker.find('.inline-shelf-select, .batch-shelf-select');
          $shelfSelect.val(shelf).trigger('change');
          $picker.find('.shelf-picker-trigger').text(shelf).attr('aria-expanded', 'false');
          $picker.find('.shelf-picker-menu').prop('hidden', true);
        });

        $(document).on('click', function(event) {
          if (!$(event.target).closest('.shelf-picker').length) {
            $('.shelf-picker-menu').prop('hidden', true);
            $('.shelf-picker-trigger').attr('aria-expanded', 'false');
          }
        });

        $(window).on('scroll', function() {
          $('.shelf-picker-menu').prop('hidden', true);
          $('.shelf-picker-trigger').attr('aria-expanded', 'false');
        });

        // Rack filter change
        $('#rackFilterSelect').on('change', function() {
          loadStoreRolls();
        });

        // Check All / Batch selection
        $('#checkAll').on('change', function() {
          const isChecked = $(this).is(':checked');
          $('.row-checkbox').prop('checked', isChecked).each(function() {
            $(this).closest('tr').toggleClass('row-selected', isChecked);
          });
          updateBatchToolbar();
        });

        $(document).on('change', '.row-checkbox', function() {
          $(this).closest('tr').toggleClass('row-selected', this.checked);
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

          if (rack) {
            loadDatabaseShelves(rack, $shelf, null);
          } else {
            resetShelfPicker($shelf, 'Select Rack First...');
          }
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
            } else {
              allRollsData = [];
              $('#rollsTableBody').html('<tr><td colspan="10" class="text-center py-5 text-muted fs-5"><i class="fa-solid fa-box-open me-2 text-secondary"></i> No rolls found in store.</td></tr>');
            }
            updateBatchToolbar();
            if (typeof callback === 'function') callback();
          },
          error: function() {
            $('#rollsTableBody').html('<tr><td colspan="10" class="text-center py-5 text-danger fs-5"><i class="fa-solid fa-triangle-exclamation me-2"></i> Failed to connect to server.</td></tr>');
            if (typeof callback === 'function') callback();
          }
        });
      }

      function renderTable(data) {
        const $tbody = $('#rollsTableBody');
        $tbody.empty();

        if (!data || data.length === 0) {
          $tbody.html('<tr><td colspan="10" class="text-center py-5 text-muted fs-5"><i class="fa-solid fa-box-open me-2 text-secondary"></i> No rolls matched the search filter.</td></tr>');
          return;
        }

        data.forEach(function(row) {
          const rawRack = row.RACKNO ? String(row.RACKNO).padStart(2, '0') : '';
          const rackLoc = row.RACKLOCATION || '';
          const fullRackDisp = rawRack ? `Rack ${rawRack}${rackLoc ? ' (' + rackLoc + ')' : ''}` : 'Unassigned';

          const safeDate = $('<div>').text(row.BUDAT || row.CREATED_DATE || '-').html();
          const safeRoll = $('<div>').text(row.ROLL).html();
          const safePO = $('<div>').text(row.PO_NUMBER || '-').html();
          const safeColor = $('<div>').text(row.COLOR || '-').html();
          const qtyText = String(row.QTY == null ? '' : row.QTY).trim();
          const safeQty = /^-?\d+\.0+$/.test(qtyText) ? qtyText.replace(/\.0+$/, '') : $('<div>').text(qtyText || '-').html();
          const safeFabric = $('<div>').text(row.FTYPE || '-').html();

          // Build row
          const tr = `
            <tr id="row-${safeRoll}">
              <td class="text-center">
                <input type="checkbox" class="form-check-input row-checkbox" value="${safeRoll}">
              </td>
              <td><span class="table-data-date">${safeDate}</span></td>
              <td>
                <span class="roll-barcode-text">${safeRoll}</span>
              </td>
              <td><span class="table-data-po">${safePO}</span></td>
              <td><span class="table-data-color">${safeColor}</span></td>
              <td class="text-end table-data-qty">${safeQty}</td>
              <td><span class="table-data-fabric">${safeFabric}</span></td>
              <td>
                <span class="badge-curr-rack" id="badge-${safeRoll}">
                  ${fullRackDisp}
                </span>
              </td>
              <td>
                <div class="destination-group">
                  <div class="destination-selects-wrapper">
                    <select class="inline-rack-select" id="select-rack-${safeRoll}" onchange="window.onRackChange('${safeRoll}')">
                      ${dbRackOptionsHtml}
                    </select>
                    <div class="shelf-picker">
                      <input type="hidden" class="inline-shelf-select" id="select-shelf-${safeRoll}" disabled>
                      <button type="button" class="shelf-picker-trigger" aria-haspopup="grid" aria-expanded="false" disabled>Select Rack First...</button>
                      <div class="shelf-picker-menu" role="grid" hidden></div>
                    </div>
                  </div>
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

        if (!toRack) {
          resetShelfPicker($shelfSelect, 'Select Rack First...');
          return;
        }

        loadDatabaseShelves(toRack, $shelfSelect, roll);
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
          $(`#select-shelf-${roll}`).closest('.shelf-picker').find('.shelf-picker-trigger').trigger('focus');
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
              $(`#badge-${roll}`).text(newDisp);

              // Highlight row
              const $row = $(`#row-${roll}`);
              $row.addClass('row-updated');
              setTimeout(function() {
                $row.removeClass('row-updated');
              }, 2500);

              // Re-check rack shelf availability for this row
              window.onRackChange(roll);

              showTransferSuccess(1, resp.to_rackno, resp.to_racklocation);
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
          $('#batchToolbar').addClass('active');
        } else {
          $('#batchToolbar').removeClass('active');
          $('.rack-table tr.row-selected').removeClass('row-selected');
          $('#batchTargetRack').val('');
          resetShelfPicker($('#batchTargetShelf'), 'Select Rack First...');
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
          $('#batchTargetShelf').closest('.shelf-picker').find('.shelf-picker-trigger').trigger('focus');
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
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...');

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
            $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Update Rack');
            if (resp && resp.success) {
              showTransferSuccess(resp.count, resp.to_rackno, resp.to_racklocation);
              loadStats();
              loadDatabaseRacks();
              loadStoreRolls();
            } else {
              showToast(resp.message || 'Batch transfer failed.', 'danger');
            }
          },
          error: function() {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Update Rack');
            showToast('Error communicating with server.', 'danger');
          }
        });
      }

      // -----------------------------------------------------------
      // 4. Refresh rack filter options.
      // -----------------------------------------------------------
      function loadStats() {
        $.ajax({
          url: 'ajaxKnitting_rack_transfer.php',
          type: 'GET',
          data: { action: 'get_stats' },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.success) {
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
      // 6. Toast Notification
      // -----------------------------------------------------------
      function showToast(msg, type = 'success', duration = 4000, centered = false) {
        const $toast = $('#toastAlert');
        const $icon = $toast.find('i');
        const $msg = $('#toastMessage');
        $toast.toggleClass('toast-centered', centered);

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
        $toast.stop(true, true).css('display', 'flex').hide().fadeIn(200);

        setTimeout(function() {
          $toast.fadeOut(300);
        }, Math.max(0, duration - 300));
      }

      function showTransferSuccess(count, rackNo, shelf) {
        const rackNumber = String(rackNo).padStart(2, '0');
        const message = Number(count) + ' roll(s) stored in Rack ' + rackNumber + shelf;

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'All Rolls Stored!',
            text: message,
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

        showToast(message, 'success', 3000, true);
        setTimeout(function() {
          window.location.reload();
        }, 3000);
      }

    })();
  </script>
</body>
</html>