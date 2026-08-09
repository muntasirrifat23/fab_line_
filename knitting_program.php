<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting Program · Responsive Cards</title>

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Bootstrap 5 (grid & utilities only) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <!-- jQuery UI for autocomplete -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f1f5f9;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
            padding: 1.5rem;
            min-height: 100vh;
        }

        /* ----- unified card style ----- */
        .card-unified {
            background: #ffffff;
            border-radius: 24px;
            padding: 1.5rem 1.8rem;
            box-shadow: 0 8px 24px rgba(0, 20, 40, 0.06);
            border: 1px solid #eef2f6;
            margin-bottom: 1.8rem;
        }

        .card-unified .card-title {
            font-weight: 700;
            font-size: 1rem;
            color: #0b2a4a;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-unified .card-title i {
            color: #2563eb;
            font-size: 1.2rem;
        }

        /* header */
        .program-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.8rem;
        }

        .program-header h1 {
            font-weight: 700;
            font-size: 2rem;
            color: #0b2a4a;
            margin: 0;
        }

        .btn-back {
            background: #1e293b;
            color: #fff;
            border: none;
            padding: 0.6rem 1.6rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #0f172a;
            transform: translateY(-2px);
            color: #fff;
        }

        /* search panel */
        .search-panel label {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #475569;
            margin-bottom: 0.5rem;
            display: block;
        }

        .search-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            align-items: center;
        }

        .search-controls input {
            flex: 1 1 220px;
            padding: 0.6rem 1.2rem;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            font-size: 0.95rem;
            background: #fafcfd;
            transition: 0.2s;
        }

        .search-controls input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
        }

        .btn-search {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 0.6rem 1.8rem;
            border-radius: 60px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-search:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-clear {
            background: #e9edf2;
            color: #1e293b;
            border: none;
            padding: 0.6rem 1.8rem;
            border-radius: 60px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-clear:hover {
            background: #d5dce4;
            transform: translateY(-2px);
        }

        #searchError {
            color: #b91c1c;
            font-weight: 500;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
        }

        #searchError.show {
            display: block;
        }

        /* ----- info grid: large → 6 cols, medium → 3 cols, small → 2 cols ----- */
        .info-grid-6 {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1.2rem 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .info-item span,
        .info-item input {
            font-weight: 600;
            font-size: 0.95rem;
            color: #0b2a4a;
            word-break: break-word;
        }

        .info-item input {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fbff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            width: 100%;
            margin-top: 0.15rem;
        }

        .info-item input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
            background: #ffffff;
        }

        .info-item .value-mono {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
        }

        /* responsive: medium (tablet) → 3 cols */
        @media (max-width: 992px) {
            .info-grid-6 {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem 1.2rem;
            }
        }

        /* small (mobile) → 2 cols */
        @media (max-width: 576px) {
            .info-grid-6 {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem 1rem;
            }
            .program-header h1 {
                font-size: 1.5rem;
            }
            .card-unified {
                padding: 1.2rem;
            }
        }

        /* yarn & fabric grid — same responsive */
        .yarn-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem 1.8rem;
        }

        @media (max-width: 576px) {
            .yarn-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.8rem;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .form-group input {
            padding: 0.5rem 0.9rem;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-weight: 500;
            font-size: 0.9rem;
            background: #fff;
            transition: 0.2s;
            width: 100%;
        }

        .form-group input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.06);
        }

        .form-group input[readonly] {
            background: #f1f5f9;
            color: #1e293b;
        }

        /* machine table & target */
        .target-qty-box {
            background: #dbeafe;
            border-radius: 60px;
            padding: 0.6rem 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 1.2rem;
            flex-wrap: wrap;
            margin-bottom: 1.2rem;
            border-left: 6px solid #2563eb;
        }

        .target-qty-box label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #1e3a8a;
            margin: 0;
        }

        .target-qty-box .qty-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1e3a8a;
            line-height: 1;
        }

        .selected-desc {
            font-size: 0.8rem;
            font-weight: 500;
            color: #1e3a8a;
            background: rgba(255,255,255,0.5);
            padding: 0.2rem 1rem;
            border-radius: 40px;
        }

        .mcno-qty-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .mcno-qty-table thead {
            background: #1e293b;
            color: #fff;
        }

        .mcno-qty-table th {
            padding: 0.7rem 0.8rem;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .mcno-qty-table td {
            padding: 0.5rem 0.6rem;
            border-bottom: 1px solid #ecf1f6;
            vertical-align: middle;
        }

        .mcno-qty-table input,
        .mcno-qty-table select {
            width: 100%;
            padding: 0.4rem 0.7rem;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.85rem;
            background: #fff;
            transition: 0.15s;
        }

        .mcno-qty-table input:focus,
        .mcno-qty-table select:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.06);
        }

        .mcno-qty-table input:disabled,
        .mcno-qty-table select:disabled {
            background: #f1f5f9;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .invalid-mcno {
            border-color: #dc2626 !important;
            background: #fee2e2 !important;
        }

        .validation-msg {
            color: #b91c1c;
            font-size: 0.65rem;
            font-weight: 600;
            display: none;
            margin-top: 2px;
        }

        .validation-msg.show {
            display: block;
        }

        .btn-delete-row {
            background: #ef4444;
            border: none;
            color: #fff;
            padding: 0.25rem 0.6rem;
            border-radius: 40px;
            font-size: 0.75rem;
            transition: 0.15s;
        }

        .btn-delete-row:hover {
            background: #b91c1c;
        }

        .summary-row {
            background: #eef4fa;
            font-weight: 700;
            border-top: 3px solid #2563eb;
        }

        .summary-row td {
            padding: 0.7rem 0.8rem;
            color: #0b2a4a;
        }

        .summary-label {
            text-align: right;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.3px;
        }

        .summary-total {
            color: #2563eb;
            font-size: 1rem;
        }

        .summary-remaining {
            color: #b45309;
            font-size: 1rem;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .btn-add-row {
            background: #0b2a4a;
            color: #fff;
            border: none;
            padding: 0.6rem 1.8rem;
            border-radius: 60px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-add-row:hover:not(:disabled) {
            background: #1e3a5f;
            transform: translateY(-2px);
        }

        .btn-add-row:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-submit {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 0.8rem 2.8rem;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1rem;
            transition: 0.2s;
        }

        .btn-submit:hover:not(:disabled) {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert-box {
            margin-bottom: 1.2rem;
        }

        .page-alert {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            width: min(92vw, 520px);
            padding: 1.2rem 1.4rem;
            border-radius: 24px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            display: none;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-weight: 600;
        }

        .page-alert.alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .page-alert.alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .page-alert.alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .alert {
            border-radius: 60px;
            padding: 0.7rem 1.5rem;
            font-weight: 500;
            border: none;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 6px solid #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 6px solid #16a34a;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 6px solid #2563eb;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .hidden {
            display: none !important;
        }

        #detailsContainer {
            display: none;
        }

        #detailsContainer.visible {
            display: block;
        }

        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.10);
        }
    </style>
</head>
<body>

<div class="container-fluid px-0">
    <div id="pageAlert" class="page-alert"></div>

    <!-- HEADER -->
    <div class="program-header">
        <button class="btn-back" id="backBtn"><i class="fa-solid fa-arrow-left me-2"></i>Back</button>
        <h1><i class="fa-solid fa-circle-knot me-2" style="color:#2563eb;"></i>Knitting Program</h1>
        <div></div>
    </div>

    <!-- SEARCH -->
    <div class="card-unified">
        <label style="margin-bottom: 4px;">Search by PO Number</label>
        <div class="search-controls">
            <input type="text" id="bookingInput" placeholder="Please enter PO number">
            <button class="btn-search" id="searchBtn"><i class="fa-solid fa-magnifying-glass me-1"></i>Search</button>
            <button class="btn-clear" id="clearBtn"><i class="fa-solid fa-rotate-left me-1"></i>Clear</button>
        </div>
        <div class="error-message" id="searchError"><i class="fa-solid fa-circle-exclamation me-1"></i>Please enter a valid PO</div>
    </div>

    <!-- FORM CONTAINER -->
    <div class="card-unified hidden" id="formContainer">

        <div class="alert-box" id="alertBox"></div>

        <div class="card-title"><i class="fa-regular fa-file-lines"></i> Knitting Program</div>

        <form id="knittingForm">

            <!-- INFO GRID: 6 cols (large) → 3 cols (medium) → 2 cols (small) -->
            <div class="info-grid-6" id="infoGrid">
                <!-- each item: label + span -->
                <div class="info-item"><label>PO Number</label><span id="display_booking">-</span><input type="hidden" id="booking"></div>
                <div class="info-item"><label>SONO</label><span id="display_sono">-</span><input type="hidden" id="sono"></div>
                <div class="info-item"><label>Buyer</label><span id="display_buyer">-</span><input type="hidden" id="buyer"></div>
                <div class="info-item"><label>STYLE</label><span id="display_style">-</span><input type="hidden" id="style"></div>
                <div class="info-item"><label>COLOR</label><span id="display_color">-</span><input type="hidden" id="color"></div>
                <div class="info-item"><label>Finish GSM</label><span id="display_finish_gsm">-</span><input type="hidden" id="finish_gsm"></div>
                <div class="info-item"><label>Finish DIA</label><input type="text" id="finish_dia" placeholder="Finish DIA"></div>
                <div class="info-item"><label>Open / Tube</label><input type="text" id="open_tube" placeholder="Open / Tube"></div>
                <div class="info-item"><label>Fabrics Type</label><input type="text" id="fabrics_type" placeholder="Fabrics Type"></div>
                <div class="info-item"><label>Yarn Type</label><span id="display_yarn_type">-</span><input type="hidden" id="yarn_type"></div>
                <div class="info-item"><label>Knit Material Code</label><span id="display_knit_material_code">-</span><input type="hidden" id="knit_material_code"></div>
                <div class="info-item"><label>Knit M Description</label><span id="display_knit_m_description">-</span><input type="hidden" id="knit_m_description"></div>
            </div>

            <!-- YARN & FABRIC (3 cols → 2 on small) -->
            <div id="detailsContainer">
                <div class="card-title" style="margin-top:1.8rem; border-top:2px solid #eef2f6; padding-top:1.2rem;"><i class="fa-solid fa-scissors"></i> Yarn &amp; Fabric Details</div>
                <div class="yarn-grid">
                    <div class="form-group"><label>Supplier</label><input type="text" id="supplier" placeholder="Enter Supplier"></div>
                    <div class="form-group"><label>Yarn Count</label><input type="text" id="yarn_count" placeholder="Enter Yarn Count"></div>
                    <div class="form-group"><label>SL/VQ</label><input type="text" id="sl_vdq" placeholder="Enter SL/VQ"></div>
                    <div class="form-group"><label>MC DIA</label><input type="text" id="mc_dia" placeholder="Enter MC DIA"></div>
                    <div class="form-group"><label>Gray GSM</label><input type="text" id="gray_gsm" placeholder="Enter Gray GSM"></div>
                    <div class="form-group"><label>Lot No</label><input type="text" id="lot_no" placeholder="Enter Lot No"></div>
                </div>
            </div>

            <!-- MACHINE NO & QTY -->
            <div class="card-title" style="margin-top:2rem; border-top:2px solid #eef2f6; padding-top:1.2rem;"><i class="fa-solid fa-list-check"></i> Machine No. &amp; Quantity</div>

            <div class="target-qty-box">
                <label>Total Knitting Target QTY :</label>
                <span class="qty-value" id="display_target_qty">0.00</span>
            </div>

            <table class="mcno-qty-table">
                <thead>
                    <tr>
                        <th style="width:10%;">#</th>
                        <th style="width:35%;">QTY</th>
                        <th style="width:35%;">Remaining</th>
                        <th style="width:20%;">Action</th>
                    </tr>
                </thead>
                <tbody id="mcnoQtyTableBody"></tbody>
                <tfoot>
                    <tr class="summary-row">
                        <td class="summary-label">Total</td>
                        <td class="summary-total" id="totalQtyDisplay">0.00</td>
                        <td class="summary-remaining" id="totalRemainingDisplay">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div class="action-buttons">
                <button type="button" class="btn-add-row" id="addMcnoRowBtn" disabled><i class="fa-solid fa-plus me-1"></i>Add Row</button>
            </div>

            <div class="action-buttons" style="margin-top:1.8rem;">
                <button type="button" class="btn-submit" id="submitBtn"><i class="fa-regular fa-floppy-disk me-2"></i>Save Program</button>
            </div>

        </form>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>

<script>
    (function($) {
        "use strict";

        // ---------- state ----------
        var bookingData = null;
        var allRowsData = [];
        var targetQty = 0;
        var originalTargetQty = 0;
        var allocatedByDescription = {};

        // ---------- load form data ----------
        function loadFormData(booking) {
            $('#searchBtn').prop('disabled', true).html('<span class="loading-spinner"></span>');
            $.ajax({
                url: 'ajaxKnittingProgram.php',
                data: { booking: booking },
                dataType: 'json',
                method: 'GET',
                timeout: 30000,
                beforeSend: function() {
                    $('#formContainer').removeClass('hidden');
                    $('#detailsContainer').removeClass('visible');
                    $('#alertBox').html('');
                },
                success: function(resp) {
                    if (resp && resp.success && resp.data) {
                        bookingData = resp.data;
                        allRowsData = resp.all_data || [];
                        allocatedByDescription = resp.allocated_by_description || {};
                        originalTargetQty = parseFloat(resp.data.KNITTING_TARGET_QTY || resp.data.QTY) || 0;
                        var allocated = parseFloat(allocatedByDescription[resp.data.KNIT_M_DESCRIPTION]) || 0;
                        targetQty = originalTargetQty - allocated;
                        renderForm(bookingData);
                        setKnitMDescription(resp);
                    } else {
                        $('#formContainer').addClass('hidden');
                        showAlert(resp.error || 'Booking not found', 'error');
                    }
                },
                error: function() {
                    $('#formContainer').addClass('hidden');
                    showAlert('Error loading data', 'error');
                },
                complete: function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass me-1"></i>Search');
                }
            });
        }

        function renderForm(data) {
            $('#display_booking').text(data.BOOKING || '-');
            $('#booking').val(data.BOOKING || '');
            $('#display_sono').text(data.SONO || '-');
            $('#sono').val(data.SONO || '');
            $('#display_style').text(data.STYLE || '-');
            $('#style').val(data.STYLE || '');
            $('#display_buyer').text(data.BUYER || '-');
            $('#buyer').val(data.BUYER || '');

            $('#color').val(data.COLOR || '');
            $('#finish_gsm').val(data.FINISH_GSM || '');
            $('#finish_dia').val(data.FINISH_DIA || '');
            $('#open_tube').val(data.OPEN_TUBE || '');
            $('#fabrics_type').val(data.FABRICS_TYPE || '');
            $('#yarn_type').val(data.YARN_TYPE || '');
            $('#knit_material_code').val(data.KNIT_MATERIAL_CODE || '');
            $('#knit_m_description').val(data.KNIT_M_DESCRIPTION || '');
            $('#supplier').val(data.SUPPLIER || '');
            $('#yarn_count').val(data.YARN_COUNT || '');
            $('#sl_vdq').val(data.SL_VDQ || '');
            $('#mc_dia').val(data.MC_DIA || '');
            $('#gray_gsm').val(data.GRAY_GSM || '');
            $('#lot_no').val(data.LOT_NO || '');
        }

        function setKnitMDescription(resp) {
            var desc = resp.data.KNIT_M_DESCRIPTION || '';
            $('#knit_m_description').val(desc);
            $('#display_knit_m_description').text(desc || '-');
            loadDetailsForDescription(desc);
        }

        function loadDetailsForDescription(description) {
            if (!bookingData) return;
            var rowData = null;
            if (allRowsData && allRowsData.length) {
                for (var i = 0; i < allRowsData.length; i++) {
                    if (allRowsData[i].KNIT_M_DESCRIPTION === description) {
                        rowData = allRowsData[i];
                        break;
                    }
                }
            }
            if (!rowData) rowData = bookingData;

            $('#display_color').text(rowData.COLOR || '-');
            $('#color').val(rowData.COLOR || '');
            $('#display_finish_gsm').text(rowData.FINISH_GSM || '-');
            $('#finish_gsm').val(rowData.FINISH_GSM || '');
            $('#finish_dia').val(rowData.FINISH_DIA || '');
            $('#open_tube').val(rowData.OPEN_TUBE || '');
            $('#fabrics_type').val(rowData.FABRICS_TYPE || '');
            $('#display_yarn_type').text(rowData.YARN_TYPE || '-');
            $('#yarn_type').val(rowData.YARN_TYPE || '');
            $('#display_knit_material_code').text(rowData.KNIT_MATERIAL_CODE || '-');
            $('#knit_material_code').val(rowData.KNIT_MATERIAL_CODE || '');
            $('#display_knit_m_description').text(rowData.KNIT_M_DESCRIPTION || '-');
            $('#knit_m_description').val(rowData.KNIT_M_DESCRIPTION || '');
            $('#supplier').val(rowData.SUPPLIER || '');
            $('#yarn_count').val(rowData.YARN_COUNT || '');
            $('#sl_vdq').val(rowData.SL_VDQ || '');
            $('#mc_dia').val(rowData.MC_DIA || '');
            $('#gray_gsm').val(rowData.GRAY_GSM || '');
            $('#lot_no').val(rowData.LOT_NO || '');

            originalTargetQty = parseFloat(rowData.KNITTING_TARGET_QTY || rowData.QTY) || 0;
            var allocated = parseFloat(allocatedByDescription[description]) || 0;
            targetQty = originalTargetQty - allocated;
            $('#display_target_qty').text(targetQty > 0 ? targetQty.toFixed(2) : '0.00');
            $('#knitting_target_qty').val(originalTargetQty);

            resetMcnoRows();
            $('#detailsContainer').addClass('visible');
        }

        function resetMcnoRows() {
            $('#mcnoQtyTableBody').html('');
            addMcnoRow();
            updateRemainingQty();
        }

        // ---------- row management ----------
        function addMcnoRow() {
            var tbody = $('#mcnoQtyTableBody');
            var rowCount = tbody.find('tr').length + 1;
            var row = $('<tr>');
            row.append($('<td>').text(rowCount));
            row.append($('<td>').html('<input type="number" class="qty-input" placeholder="QTY" step="0.01">'));
            row.append($('<td>').html('<input type="text" class="remaining-qty" readonly placeholder="Remaining">'));
            row.append($('<td>').html('<button type="button" class="btn-delete-row" onclick="deleteMcnoRow(this)"><i class="fa-solid fa-trash"></i></button>'));
            tbody.append(row);

            row.find('.qty-input').on('input', function() {
                updateRemainingQty();
                checkAddRowButton();
            });
            updateRemainingQty();
            checkAddRowButton();
        }



        window.deleteMcnoRow = function(btn) {
            var tr = $(btn).closest('tr');
            if ($('#mcnoQtyTableBody tr').length > 1) {
                tr.remove();
                updateMcnoRowNumbers();
                updateRemainingQty();
                checkAddRowButton();
            } else {
                showAlert('Cannot delete last row', 'error');
            }
        };

        function updateMcnoRowNumbers() {
            $('#mcnoQtyTableBody tr').each(function(idx) {
                $(this).find('td:first').text(idx + 1);
            });
        }

        function updateRemainingQty() {
            var totalQty = 0;
            var rows = $('#mcnoQtyTableBody tr');
            var overAlertShown = false;
            rows.each(function(index) {
                var rowQty = parseFloat($(this).find('.qty-input').val()) || 0;
                var remainingBefore = targetQty - totalQty;
                if (!overAlertShown && rowQty > remainingBefore && remainingBefore >= 0) {
                    showPageAlert('Over QTY. Available qty is ' + remainingBefore.toFixed(2) + '.', 'error', 3000);
                    overAlertShown = true;
                }
                totalQty += rowQty;
                var newRemaining = targetQty - totalQty;
                $(this).find('.remaining-qty').val(newRemaining >= 0 ? newRemaining.toFixed(2) : '0.00');
            });
            $('#totalQtyDisplay').text(totalQty.toFixed(2));
            var remainingTotal = targetQty - totalQty;
            $('#totalRemainingDisplay').text(remainingTotal >= 0 ? remainingTotal.toFixed(2) : '0.00');
            if (totalQty > targetQty && !overAlertShown) {
                showAlert('Total QTY exceeds target. Available qty is 0.00.', 'error');
            }
        }
        

        function isManualDataValid() {
            var requiredFields = [
                '#finish_dia',
                '#open_tube',
                '#fabrics_type',
                '#supplier',
                '#yarn_count',
                '#sl_vdq',
                '#mc_dia',
                '#gray_gsm',
                '#lot_no',
                '#knit_m_description'
            ];
            for (var i = 0; i < requiredFields.length; i++) {
                var value = $(requiredFields[i]).val();
                if (!value || value.toString().trim() === '') {
                    return false;
                }
            }
            return true;
        }

        function checkAddRowButton() {
            var rows = $('#mcnoQtyTableBody tr');
            var allValid = true;
            var allFilled = true;
            var hasData = false;

            rows.each(function() {
                var qty = $(this).find('.qty-input').val().trim();
                var isEmpty = !qty;
                if (isEmpty) return true;
                hasData = true;
                if (!qty) {
                    allValid = false;
                    allFilled = false;
                    return false;
                }
                var qtyNum = parseFloat(qty);
                if (isNaN(qtyNum) || qtyNum <= 0) {
                    allValid = false;
                    return false;
                }
            });

            var totalQty = 0;
            rows.each(function() {
                var qty = parseFloat($(this).find('.qty-input').val()) || 0;
                if (qty > 0) totalQty += qty;
            });
            if (hasData && totalQty > targetQty) allValid = false;

            var addRowsEnabled = allValid && allFilled;
            $('#addMcnoRowBtn').prop('disabled', !addRowsEnabled);

            var submitEnabled = addRowsEnabled && hasData && isManualDataValid();
            $('#submitBtn').prop('disabled', !submitEnabled);
        }

        function getMcnoQtyData() {
            var data = [];
            var isValid = true;
            var rows = $('#mcnoQtyTableBody tr');
            if (rows.length === 0) return { data: [], isValid: false };
            rows.each(function() {
                var qty = $(this).find('.qty-input').val().trim();
                var isEmpty = !qty;
                if (isEmpty) return true;
                if (!qty) {
                    isValid = false;
                    return false;
                }
                var qtyNum = parseFloat(qty);
                if (isNaN(qtyNum) || qtyNum <= 0) {
                    isValid = false;
                    return false;
                }
                data.push({ qty: qtyNum });
            });
            return { data: data, isValid: isValid };
        }

        // ---------- alert ----------
        function showAlert(msg, type, duration) {
            var cls = type === 'error' ? 'alert-danger' : (type === 'info' ? 'alert-info' : 'alert-success');
            var icon = type === 'error' ? 'circle-exclamation' : (type === 'info' ? 'circle-info' : 'circle-check');
            $('#alertBox').html(
                '<div class="alert ' + cls + '"><i class="fa-solid fa-' + icon + ' me-2"></i>' + msg + '</div>'
            );
            if (duration !== false) {
                setTimeout(function() { $('#alertBox').html(''); }, duration || 3000);
            }
        }

        function showPageAlert(msg, type, duration) {
            var cls = type === 'error' ? 'alert-danger' : (type === 'info' ? 'alert-info' : 'alert-success');
            $('#pageAlert').removeClass('alert-danger alert-info alert-success').addClass(cls).html(
                '<i class="fa-solid fa-' + (type === 'error' ? 'triangle-exclamation' : (type === 'info' ? 'circle-info' : 'circle-check')) + ' me-2"></i>' + msg
            ).fadeIn(150);
            if (duration !== false) {
                setTimeout(function() { $('#pageAlert').fadeOut(150); }, duration || 3000);
            }
        }

        // ---------- ready ----------
        $(function() {
            $('#backBtn').on('click', function() {
                window.location.href = 'initialPage.php';
            });

            $('#searchBtn').on('click', function() {
                var booking = $('#bookingInput').val().trim();
                if (!booking) {
                    $('#searchError').addClass('show');
                    setTimeout(function() { $('#searchError').removeClass('show'); }, 3000);
                    return;
                }
                $('#searchError').removeClass('show');
                loadFormData(booking);
            });

            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                $('#formContainer').addClass('hidden');
                $('#alertBox').html('');
                bookingData = null;
                allRowsData = [];
                targetQty = 0;
                $('#detailsContainer').removeClass('visible');
                $('#display_target_qty').text('0.00');
                $('#knitting_target_qty').val('');
                $('#selected_desc_label').html('');
                $('#mcnoQtyTableBody').html('');
                $('#totalQtyDisplay').text('0.00');
                $('#totalRemainingDisplay').text('0.00');
                $('#addMcnoRowBtn').prop('disabled', true);
                // reset all display fields
                $('#infoGrid span').text('-');
                addMcnoRow();
            });

            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) $('#searchBtn').click();
            });
            $('#bookingInput').on('input', function() {
                $('#searchError').removeClass('show');
            });

            var manualFieldSelectors = [
                '#finish_dia',
                '#open_tube',
                '#fabrics_type',
                '#supplier',
                '#yarn_count',
                '#sl_vdq',
                '#mc_dia',
                '#gray_gsm',
                '#lot_no',
                '#knit_m_description'
            ].join(', ');

            $(manualFieldSelectors).on('input change', function() {
                checkAddRowButton();
            });

            $('#addMcnoRowBtn').on('click', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled')) addMcnoRow();
            });

            $('#submitBtn').on('click', function(e) {
                e.preventDefault();
                var mcnoResult = getMcnoQtyData();
                if (!mcnoResult.isValid) {
                    showAlert('Fill all QTY fields with valid data.', 'error');
                    return;
                }
                if (mcnoResult.data.length === 0) {
                    showAlert('Add at least one quantity row.', 'error');
                    return;
                }
                var desc = $('#knit_m_description').val();
                if (!desc || desc.trim() === '') {
                    showAlert('Knit M Description missing.', 'error');
                    return;
                }
                var totalQty = 0;
                mcnoResult.data.forEach(function(item) { totalQty += item.qty; });
                var targetVal = parseFloat($('#display_target_qty').text()) || 0;
                if (totalQty > targetVal) {
                    showAlert('Total QTY exceeds target. Adjust.', 'error');
                    return;
                }

                var formData = {
                    booking: $('#booking').val(),
                    sono: $('#sono').val(),
                    style: $('#style').val(),
                    buyer: $('#buyer').val(),
                    supplier: $('#supplier').val(),
                    knit_m_description: desc,
                    yarn_type: $('#yarn_type').val(),
                    yarn_count: $('#yarn_count').val(),
                    fabrics_type: $('#fabrics_type').val(),
                    finish_gsm: $('#finish_gsm').val(),
                    gray_gsm: $('#gray_gsm').val(),
                    sl_vdq: $('#sl_vdq').val(),
                    color: $('#color').val(),
                    mc_dia: $('#mc_dia').val(),
                    finish_dia: $('#finish_dia').val(),
                    open_tube: $('#open_tube').val(),
                    lot_no: $('#lot_no').val(),
                    knit_material_code: $('#knit_material_code').val(),
                    knitting_target_qty: $('#knitting_target_qty').val(),
                    mcno_qty: mcnoResult.data
                };

                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="loading-spinner"></span>');
                $.ajax({
                    url: 'ajax_save_knitting_program.php',
                    method: 'POST',
                    contentType: 'application/json; charset=utf-8',
                    processData: false,
                    data: JSON.stringify(formData),
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            showAlert(resp.message || 'Saved!', 'success', 1500);
                            setTimeout(function() { window.location.reload(); }, 1500);
                        } else {
                            showAlert(resp.message || 'Save failed.', 'error', 2000);
                        }
                    },
                    error: function() {
                        showAlert('Error saving program.', 'error', 2000);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fa-regular fa-floppy-disk me-2"></i>Save Program');
                    }
                });
            });

            // initial row
            addMcnoRow();
        });

    })(jQuery);
</script>

</body>
</html>