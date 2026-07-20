<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting | Knitting Program</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            padding: 18px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .header .btn-back {
            background-color: #1f2937;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(20, 30, 50, 0.15);
        }

        .header .btn-back:hover {
            background-color: #374151;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(20, 30, 50, 0.2);
        }

        .header h1 {
            font-weight: 700;
            color: #1f2937;
            font-size: 32px;
            margin: 0;
        }

        .search-panel {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(20, 30, 50, 0.08);
            margin-bottom: 28px;
        }

        .search-panel label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-controls input {
            flex: 1;
            min-width: 200px;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-controls input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-controls .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-search {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-clear {
            background: #e5e7eb;
            color: #1f2937;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-clear:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }

        .form-container {
            background: #fff;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(20, 30, 50, 0.12);
            margin-bottom: 28px;
        }

        .form-container.hidden {
            display: none;
        }

        .form-title {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 3px solid #2563eb;
            display: inline-block;
        }

        .form-section {
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-group input,
        .form-group select {
            padding: 12px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: #f9fafb;
            color: #1f2937;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group input[readonly] {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        .info-row {
            background: #f8fafc;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
            border-left: 4px solid #2563eb;
        }

        .info-row .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-row .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-row .info-item label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-row .info-item span {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            margin-top: 4px;
        }

        .dropdown-section {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            border: 2px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .dropdown-section select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: #f9fafb;
            transition: all 0.3s ease;
        }

        .dropdown-section select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .mcno-qty-section {
            background: linear-gradient(135deg, #f0f4f8 0%, #e5eef7 100%);
            padding: 24px;
            border-radius: 12px;
            border: 2px dashed #2563eb;
        }

        .mcno-qty-title {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mcno-qty-title i {
            color: #2563eb;
            font-size: 18px;
        }

        .mcno-qty-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .mcno-qty-table thead {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
        }

        .mcno-qty-table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .mcno-qty-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .mcno-qty-table tbody tr:hover {
            background: #f9fafb;
        }

        .mcno-qty-table input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .mcno-qty-table input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .mcno-qty-table input[readonly] {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        .mcno-qty-table input.invalid-mcno {
            border-color: #dc2626;
            background: #fee2e2;
        }

        .mcno-qty-table input:disabled {
            background: #e5e7eb;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-add-row {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-add-row:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-add-row:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-delete-row {
            background: #ef4444;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-delete-row:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        .btn-delete-row:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-submit {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            padding: 14px 36px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #10b981;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #2563eb;
        }

        #detailsContainer {
            display: none;
        }

        #detailsContainer.visible {
            display: block;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(37, 99, 235, 0.3);
            border-radius: 50%;
            border-top-color: #2563eb;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .error-message {
            color: #dc2626;
            font-size: 13px;
            margin-top: 8px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .target-qty-box {
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #dbeafe;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .target-qty-box label {
            font-size: 13px;
            font-weight: 600;
            color: #1e40af;
            margin: 0;
        }

        .target-qty-box .qty-value {
            font-size: 20px;
            font-weight: 700;
            color: #1e40af;
        }

        .selected-description-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .selected-description-label span {
            color: #1f2937;
            font-weight: 600;
        }

        .mcno-input {
            position: relative;
        }

        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 9999;
        }

        .summary-row {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 100%);
            font-weight: 700;
            border-top: 3px solid #2563eb;
        }

        .summary-row td {
            padding: 14px 16px;
            font-weight: 700;
            color: #1f2937;
        }

        .summary-row .summary-total {
            color: #2563eb;
            font-size: 16px;
        }

        .summary-row .summary-remaining {
            color: #dc2626;
            font-size: 16px;
        }

        .summary-row .summary-label {
            text-align: right;
            padding-right: 20px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        .mcno-cell {
            position: relative;
        }

        .mcno-cell .validation-msg {
            color: #dc2626;
            font-size: 10px;
            display: none;
            margin-top: 2px;
        }

        .mcno-cell .validation-msg.show {
            display: block;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="header">
            <button class="btn btn-dark" id="backBtn" style="background-color:#1f2937;color:#fff;padding:12px;border-radius:8px;">
                <i class="fa-solid fa-arrow-left" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i>
                Back to Initial Page
            </button>
            <h1>Knitting Program</h1>
            <div></div>
        </div>

        <div class="search-panel">
            <label>Search by Booking Number</label>
            <div class="search-controls">
                <input type="text" id="bookingInput" placeholder="Enter Booking Number...">
                <button class="btn px-4" id="searchBtn" style="margin-top:8px; background:#2563eb; border:1px solid #2563eb; color:#fff; border-radius:8px;">
                    <i class="fa-solid fa-magnifying-glass me-1" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i>
                    Search
                </button>

                <button class="btn px-4" id="clearBtn" style="margin-top:8px; margin-left:8px; background:#6b7280; border:1px solid #6b7280; color:#fff; border-radius:8px;">
                    <i class="fa-solid fa-rotate-left me-1" style="margin-right:6px;background:none;border:none;box-shadow:none;transform:none;"></i>
                    Clear
                </button>
            </div>
            <div class="error-message" id="searchError">Please enter a valid booking number</div>
        </div>

        <!-- Form Container -->
        <div class="form-container hidden" id="formContainer">
            <div id="alertBox"></div>

            <h2 class="form-title">
                <i class="fa-solid fa-file-contract" style="margin-right: 12px; color: #2563eb; background:none;border:none;box-shadow:none;transform:none;"></i>
                Knitting Program
            </h2>

            <form id="knittingForm">
                <!-- First Row -->
                <div class="info-row">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Booking Number</label>
                            <span id="display_booking">-</span>
                            <input type="hidden" id="booking" value="">
                        </div>
                        <div class="info-item">
                            <label>SONO</label>
                            <span id="display_sono">-</span>
                            <input type="hidden" id="sono" value="">
                        </div>
                        <div class="info-item">
                            <label>STYLE</label>
                            <span id="display_style">-</span>
                            <input type="hidden" id="style" value="">
                        </div>
                        <div class="info-item">
                            <label>Buyer</label>
                            <span id="display_buyer">-</span>
                            <input type="hidden" id="buyer" value="">
                        </div>
                        <div class="info-item">
                            <label>Supplier</label>
                            <span id="display_supplier">-</span>
                            <input type="hidden" id="supplier" value="">
                        </div>
                    </div>
                </div>

                <!-- Second Row -->
                <div class="dropdown-section">
                    <div class="form-group">
                        <label>Knit M Description</label>
                        <select id="knit_m_description">
                            <option value="">-- Select Knit M Description --</option>
                        </select>
                    </div>
                </div>

                <!-- Third & Fourth Row -->
                <div id="detailsContainer">
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fa-solid fa-scissors" style="margin-right:8px;background:none;border:none;box-shadow:none;transform:none;"></i>
                            Yarn &amp; Fabric Details
                        </div>
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label>Yarn Type</label>
                                <input type="text" id="yarn_type" readonly>
                            </div>
                            <div class="form-group">
                                <label>Yarn Count</label>
                                <input type="text" id="yarn_count" readonly>
                            </div>
                            <div class="form-group">
                                <label>Fabrics Type</label>
                                <input type="text" id="fabrics_type" readonly>
                            </div>
                            <div class="form-group">
                                <label>Finish GSM</label>
                                <input type="text" id="finish_gsm" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">
                            <i class="fa-solid fa-shirt" style="background:none;border:none;box-shadow:none;transform:none;"></i>
                            Quality & Material Details
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Finish DIA</label>
                                <input type="text" id="finish_dia" readonly>
                            </div>
                            <div class="form-group">
                                <label>Open / Tube</label>
                                <input type="text" id="open_tube" readonly>
                            </div>
                            <div class="form-group">
                                <label>Lot No</label>
                                <input type="text" id="lot_no" readonly>
                            </div>
                            <div class="form-group">
                                <label>Knit Material Code</label>
                                <input type="text" id="knit_material_code" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MCNO & QTY Section -->
                <div class="form-section">
                    <div class="mcno-qty-section">
                        <div class="mcno-qty-title">
                            <i class="fa-solid fa-list-ul" style="background:none;border:none;box-shadow:none;transform:none;"></i>
                            Machine No. & Quantity Details
                        </div>

                        <div class="target-qty-box">
                            <label>Total Knitting Target QTY:</label>
                            <span class="qty-value" id="display_target_qty">0</span>
                            <span class="selected-description-label" id="selected_desc_label"></span>
                            <input type="hidden" id="knitting_target_qty" value="">
                        </div>

                        <table class="mcno-qty-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 20%;">MCNO</th>
                                    <th style="width: 20%;">QTY</th>
                                    <th style="width: 20%;">SHIFT</th>
                                    <th style="width: 25%;">Remaining QTY</th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="mcnoQtyTableBody">
                                <tr>
                                    <td>1</td>
                                    <td class="mcno-cell">
                                        <input type="text" class="mcno-input" placeholder="Enter MCNO">
                                        <div class="validation-msg">Invalid MCNO. Please select from list.</div>
                                    </td>
                                    <td><input type="number" class="qty-input" placeholder="Enter Quantity" disabled></td>
                                    <td>
                                        <select class="shift-input" disabled>
                                            <option value="">Select Shift</option>
                                            <option value="A-SHIFT">A-SHIFT</option>
                                            <option value="B-SHIFT">B-SHIFT</option>
                                            <option value="C-SHIFT">C-SHIFT</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="remaining-qty" readonly placeholder="Auto-calculated"></td>
                                    <td><button type="button" class="btn-delete-row" onclick="deleteMcnoRow(this)"><i class="fa-solid fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                            <tfoot id="summaryRow">
                                <tr class="summary-row">
                                    <td colspan="2" class="summary-label">Total</td>
                                    <td class="summary-total" id="totalQtyDisplay">0.00</td>
                                    <td class="summary-remaining" id="totalRemainingDisplay">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="action-buttons">
                            <button type="button" class="btn-add-row" id="addMcnoRowBtn" disabled>
                                <i class="fa-solid fa-plus" style="background:none;border:none;box-shadow:none;transform:none;"></i> Add Row
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="action-buttons" style="margin-top: 32px;">
                    <button type="button" class="btn-submit" id="submitBtn">
                        <i class="fa-solid fa-paper-plane" style="background:none;border:none;box-shadow:none;transform:none;"></i> Save Program
                    </button>
                </div>
            </form>
        </div>

    </div>

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>

    <script>
        var bookingData = null;
        var allRowsData = [];
        var targetQty = 0;
        var validMcnoList = [];
        var isMcnoListLoaded = false;

        // Load MCNO list from database
        function loadMcnoList() {
            $.ajax({
                url: 'ajax_mcno_search.php',
                data: {
                    action: 'list'
                },
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    validMcnoList = data;
                    isMcnoListLoaded = true;
                    console.log('MCNO List loaded:', validMcnoList.length, 'items');

                    // Re-validate all rows after list is loaded
                    $('.mcno-input').each(function() {
                        validateMcno(this);
                        updateQtyInputState(this);
                    });
                },
                error: function() {
                    validMcnoList = [];
                    isMcnoListLoaded = true;
                    console.error('Failed to load MCNO list');
                }
            });
        }

        // Check if MCNO is valid
        function isValidMcno(mcno) {
            if (!mcno || mcno.trim() === '') return false;
            if (!isMcnoListLoaded) return true;

            return validMcnoList.some(function(item) {
                return item.toUpperCase() === mcno.trim().toUpperCase();
            });
        }

        // Enable/disable QTY input based on MCNO validity
        function updateQtyInputState(mcnoInput) {
            var row = $(mcnoInput).closest('tr');
            var qtyInput = row.find('.qty-input');
            var shiftInput = row.find('.shift-input');
            var isValid = validateMcno(mcnoInput);

            if (isValid) {
                qtyInput.prop('disabled', false);
                shiftInput.prop('disabled', false);
                qtyInput.focus();
            } else {
                qtyInput.prop('disabled', true);
                qtyInput.val('');
                shiftInput.prop('disabled', true);
                shiftInput.val('');
            }

            // Update remaining QTY
            updateRemainingQty();
            checkAddRowButton();
        }

        function loadFormData(booking) {
            $('#searchBtn').prop('disabled', true).html('<span class="loading-spinner"></span> Loading...');

            $.ajax({
                    url: 'ajaxKnittingProgram.php',
                    data: {
                        booking: booking
                    },
                    dataType: 'json',
                    method: 'GET',
                    timeout: 30000,
                    beforeSend: function() {
                        $('#formContainer').removeClass('hidden');
                        $('#knit_m_description').html('<option value="">Loading descriptions...</option>');
                        $('#detailsContainer').removeClass('visible');
                        $('#mcnoQtyTableBody').html('<tr><td colspan="5" style="text-align:center;padding:20px;">Loading machine data...</td></tr>');
                        $('#alertBox').html('');
                    }
                })
                .done(function(resp) {

                    if (resp && resp.success && resp.data) {

                        bookingData = resp.data;
                        allRowsData = resp.all_data || [];

                        $('#formContainer').removeClass('hidden');

                        renderForm(bookingData);
                        loadKnitDescriptions(resp);

                    } else {

                        $('#formContainer').addClass('hidden');
                        $('#detailsContainer').removeClass('visible');

                        alert("Booking Data Not Found!");
                    }

                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                    console.error('Response:', jqXHR.responseText);
                    $('#formContainer').addClass('hidden');
                    showAlert('Error loading data: ' + textStatus + '. Please check console for details.', 'error');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass" style="background:none;border:none;box-shadow:none;transform:none;"></i> Search');
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
            $('#display_supplier').text(data.SUPPLIER || '-');
            $('#supplier').val(data.SUPPLIER || '');
        }

        function loadKnitDescriptions(resp) {
            var select = $('#knit_m_description');
            select.html('<option value="">-- Select Knit M Description --</option>');

            var descriptions = [];

            if (resp.descriptions && Array.isArray(resp.descriptions) && resp.descriptions.length > 0) {
                descriptions = resp.descriptions;
            } else if (resp.data && resp.data.KNIT_M_DESCRIPTION) {
                descriptions = [resp.data.KNIT_M_DESCRIPTION];
            }

            if (descriptions.length > 0) {
                descriptions.forEach(function(desc) {
                    if (desc && desc.trim() !== '') {
                        select.append('<option value="' + desc + '">' + desc + '</option>');
                    }
                });

                if (descriptions.length === 1) {
                    select.val(descriptions[0]);
                    loadDetailsForDescription(descriptions[0]);
                }
            } else {
                select.html('<option value="">No descriptions available</option>');
                showAlert('No Knit M Description found for this booking.', 'info');
            }
        }

        function loadDetailsForDescription(description) {
            if (!bookingData) return;

            var rowData = null;
            if (allRowsData && allRowsData.length > 0) {
                for (var i = 0; i < allRowsData.length; i++) {
                    if (allRowsData[i].KNIT_M_DESCRIPTION === description) {
                        rowData = allRowsData[i];
                        break;
                    }
                }
            }

            if (!rowData) {
                rowData = bookingData;
            }

            $('#yarn_type').val(rowData.YARN_TYPE || '');
            $('#yarn_count').val(rowData.YARN_COUNT || '');
            $('#fabrics_type').val(rowData.FABRICS_TYPE || '');
            $('#finish_gsm').val(rowData.FINISH_GSM || '');
            $('#finish_dia').val(rowData.FINISH_DIA || '');
            $('#open_tube').val(rowData.OPEN_TUBE || '');
            $('#lot_no').val(rowData.LOT_NO || '');
            $('#knit_material_code').val(rowData.KNIT_MATERIAL_CODE || rowData.KNIT_MAT_CODE || '');

            targetQty = parseFloat(rowData.KNITTING_TARGET_QTY) || 0;
            $('#display_target_qty').text(targetQty.toFixed(2));
            $('#knitting_target_qty').val(targetQty);
            $('#selected_desc_label').html('(For: <span>' + description + '</span>)');

            resetMcnoRows();
            $('#detailsContainer').addClass('visible');
        }

        function resetMcnoRows() {
            $('#mcnoQtyTableBody').html('');
            addMcnoRow();
            updateRemainingQty();
        }

        function addMcnoRow() {
            var tableBody = $('#mcnoQtyTableBody');
            var rowCount = tableBody.find('tr').length + 1;
            var newRow = $('<tr>');
            newRow.append($('<td>').text(rowCount));
            newRow.append(
                $('<td class="mcno-cell">').html(
                    '<input type="text" class="mcno-input" autocomplete="off" placeholder="Enter MCNO">' +
                    '<div class="validation-msg" style="background:none;border:none;box-shadow:none;transform:none;">Invalid MCNO. Please select from list.</div>'
                )
            );
            newRow.append($('<td>').html('<input type="number" class="qty-input" placeholder="Enter Quantity" disabled>'));
            newRow.append($('<td>').html('<select class="shift-input" disabled>' +
                '<option value="">Select Shift</option>' +
                '<option value="A-SHIFT">A-SHIFT</option>' +
                '<option value="B-SHIFT">B-SHIFT</option>' +
                '<option value="C-SHIFT">C-SHIFT</option>' +
                '</select>'));
            newRow.append($('<td>').html('<input type="text" class="remaining-qty" readonly placeholder="Auto-calculated">'));
            newRow.append($('<td>').html('<button type="button" class="btn-delete-row" onclick="deleteMcnoRow(this)"><i class="fa-solid fa-trash"></i></button>'));
            tableBody.append(newRow);

            var mcnoInput = newRow.find('.mcno-input');
            bindMcnoAutocomplete(mcnoInput);

            mcnoInput.on('change blur', function() {
                validateMcno(this);
                updateQtyInputState(this);
            });

            mcnoInput.on('input', function() {
                var cell = $(this).closest('.mcno-cell');
                cell.find('.validation-msg').removeClass('show');
                $(this).removeClass('invalid-mcno');
                var row = $(this).closest('tr');
                row.find('.qty-input').prop('disabled', true).val('');
                row.find('.shift-input').prop('disabled', true).val('');
                checkAddRowButton();
            });

            // QTY input change
            newRow.find('.qty-input').on('input', function() {
                updateRemainingQty();
                checkAddRowButton();
            });

            // SHIFT select change
            newRow.find('.shift-input').on('change', function() {
                checkAddRowButton();
            });

            updateRemainingQty();
            checkAddRowButton();
        }

        function bindMcnoAutocomplete(inputElement) {
            if (!inputElement) {
                inputElement = $('.mcno-input');
            }

            inputElement.autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'ajax_mcno_search.php',
                        data: {
                            term: request.term
                        },
                        dataType: 'json',
                        success: function(data) {
                            response(data);
                        },
                        error: function() {
                            response([]);
                        }
                    });
                },
                minLength: 1,
                select: function(event, ui) {
                    $(this).val(ui.item.value);
                    var cell = $(this).closest('.mcno-cell');
                    cell.find('.validation-msg').removeClass('show');
                    $(this).removeClass('invalid-mcno');
                    updateQtyInputState(this);
                    return false;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        validateMcno(this);
                        updateQtyInputState(this);
                    } else {
                        var cell = $(this).closest('.mcno-cell');
                        cell.find('.validation-msg').removeClass('show');
                        $(this).removeClass('invalid-mcno');
                        updateQtyInputState(this);
                    }
                }
            });
        }

        function validateMcno(input) {
            var value = $(input).val().trim();
            var cell = $(input).closest('.mcno-cell');
            var msg = cell.find('.validation-msg');

            if (!value) {
                $(input).removeClass('invalid-mcno');
                msg.removeClass('show');
                return false;
            }

            if (!isValidMcno(value)) {
                $(input).addClass('invalid-mcno');
                msg.addClass('show');
                return false;
            } else {
                $(input).removeClass('invalid-mcno');
                msg.removeClass('show');
                return true;
            }
        }

        function checkAddRowButton() {
            var rows = $('#mcnoQtyTableBody tr');
            var allRowsValid = true;
            var allFilled = true;

            rows.each(function() {
                var mcno = $(this).find('.mcno-input').val().trim();
                var qty = $(this).find('.qty-input').val().trim();
                var shift = $(this).find('.shift-input').val().trim();
                var qtyDisabled = $(this).find('.qty-input').prop('disabled');
                var shiftDisabled = $(this).find('.shift-input').prop('disabled');

                // If QTY or SHIFT is disabled, row is not valid
                if (qtyDisabled || shiftDisabled) {
                    allRowsValid = false;
                    allFilled = false;
                    return false;
                }

                if (!mcno || !qty || !shift) {
                    allFilled = false;
                    allRowsValid = false;
                    return false;
                }

                if (!isValidMcno(mcno)) {
                    allRowsValid = false;
                    return false;
                }

                // Check if qty is valid (not exceeding remaining)
                var qtyNum = parseFloat(qty);
                if (qtyNum <= 0) {
                    allRowsValid = false;
                    return false;
                }
            });

            // Also check total doesn't exceed target
            var totalQty = 0;
            rows.each(function() {
                var qty = parseFloat($(this).find('.qty-input').val()) || 0;
                totalQty += qty;
            });

            if (totalQty > targetQty) {
                allRowsValid = false;
            }

            $('#addMcnoRowBtn').prop('disabled', !(allRowsValid && allFilled));
        }

        function updateRemainingDisplayForRow(row) {
            var rowIndex = $(row).index();
            var previousTotal = 0;

            $('#mcnoQtyTableBody tr').each(function(index) {
                if (index < rowIndex) {
                    previousTotal += parseFloat($(this).find('.qty-input').val()) || 0;
                }
            });

            var rowRemaining = targetQty - previousTotal;
            $(row).find('.remaining-qty').val(rowRemaining >= 0 ? rowRemaining.toFixed(2) : '0.00');
        }

        function deleteMcnoRow(btn) {
            var tr = $(btn).closest('tr');
            if ($('#mcnoQtyTableBody tr').length > 1) {
                tr.remove();
                updateMcnoRowNumbers();
                updateRemainingQty();
                checkAddRowButton();
            } else {
                showAlert('Cannot delete the last row. Add more rows first.', 'error');
            }
        }

        function updateMcnoRowNumbers() {
            $('#mcnoQtyTableBody tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });
        }

        function updateRemainingQty() {
            var totalQty = 0;
            var rows = $('#mcnoQtyTableBody tr');

            rows.each(function(index) {
                var rowQty = parseFloat($(this).find('.qty-input').val()) || 0;
                var qtyInput = $(this).find('.qty-input');
                var remainingBefore = targetQty - totalQty;

                if (rowQty > remainingBefore && remainingBefore >= 0) {
                    qtyInput.val(remainingBefore.toFixed(2));
                    rowQty = remainingBefore;
                    qtyInput.removeClass('invalid-mcno');
                    showAlert('Row ' + (index + 1) + ' QTY adjusted to remaining: ' + remainingBefore.toFixed(2), 'info');
                }

                totalQty += rowQty;
                var newRemaining = targetQty - totalQty;
                $(this).find('.remaining-qty').val(newRemaining >= 0 ? newRemaining.toFixed(2) : '0.00');
            });

            // Update summary
            var totalQtySum = totalQty;
            var remainingTotal = targetQty - totalQtySum;
            $('#totalQtyDisplay').text(totalQtySum.toFixed(2));
            $('#totalRemainingDisplay').text(remainingTotal >= 0 ? remainingTotal.toFixed(2) : '0.00');
        }

        function getMcnoQtyData() {
            var data = [];
            var isValid = true;
            var rows = $('#mcnoQtyTableBody tr');

            if (rows.length === 0) {
                return {
                    data: [],
                    isValid: false
                };
            }

            rows.each(function() {
                var mcno = $(this).find('.mcno-input').val().trim();
                var qty = $(this).find('.qty-input').val().trim();
                var shift = $(this).find('.shift-input').val().trim();

                if (!mcno || !qty || !shift) {
                    isValid = false;
                    return false;
                }

                if (!isValidMcno(mcno)) {
                    isValid = false;
                    return false;
                }

                data.push({
                    mcno: mcno,
                    qty: parseFloat(qty),
                    shift: shift
                });
            });

            return {
                data: data,
                isValid: isValid
            };
        }

        function showAlert(message, type, durationMs) {
            var alertClass = type === 'error' ? 'alert-danger' : (type === 'info' ? 'alert-info' : 'alert-success');
            var icon = type === 'error' ? 'circle-exclamation' : (type === 'info' ? 'circle-info' : 'circle-check');
            var alertHtml = '<div class="alert ' + alertClass + '">' +
                '<i class="fa-solid fa-' + icon + '" style="margin-right: 8px;"></i>' +
                message + '</div>';
            $('#alertBox').html(alertHtml);
            setTimeout(function() {
                $('#alertBox').html('');
            }, durationMs || 3000);
        }

        // Document ready
        $(function() {
            // Load MCNO list first
            loadMcnoList();

            // Back button
            $('#backBtn').click(function() {
                window.location.href = 'initialPage.php';
            });

            // Search button
            $('#searchBtn').on('click', function() {
                var booking = $('#bookingInput').val().trim();
                if (!booking) {
                    $('#searchError').addClass('show');
                    setTimeout(function() {
                        $('#searchError').removeClass('show');
                    }, 3000);
                    return;
                }
                $('#searchError').removeClass('show');
                loadFormData(booking);
            });

            // Clear button
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                $('#formContainer').addClass('hidden');
                $('#alertBox').html('');
                bookingData = null;
                allRowsData = [];
                targetQty = 0;
                $('#detailsContainer').removeClass('visible');
                $('#knit_m_description').html('<option value="">-- Select Knit M Description --</option>');
                $('#display_target_qty').text('0');
                $('#knitting_target_qty').val('');
                $('#selected_desc_label').html('');
                $('#mcnoQtyTableBody').html('');
                $('#totalQtyDisplay').text('0.00');
                $('#totalRemainingDisplay').text('0.00');
                $('#addMcnoRowBtn').prop('disabled', true);
                addMcnoRow();
            });

            // Enter key on booking input
            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#searchBtn').click();
                }
            });

            // Remove error on input
            $('#bookingInput').on('input', function() {
                $('#searchError').removeClass('show');
            });

            // Add row button
            $('#addMcnoRowBtn').on('click', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled')) {
                    addMcnoRow();
                }
            });

            $('#mcnoQtyTableBody').on('change input', '.shift-input', function() {
                checkAddRowButton();
            });

            $('#mcnoQtyTableBody').on('input', '.qty-input', function() {
                updateRemainingQty();
                checkAddRowButton();
            });

            // Knit description change
            $('#knit_m_description').on('change', function() {
                var selected = $(this).val();
                if (selected && selected.trim() !== '') {
                    loadDetailsForDescription(selected);
                } else {
                    $('#detailsContainer').removeClass('visible');
                    $('#display_target_qty').text('0');
                    $('#knitting_target_qty').val('');
                    $('#selected_desc_label').html('');
                }
            });

            // Submit button
            $('#submitBtn').on('click', function(e) {
                e.preventDefault();

                if (!isMcnoListLoaded) {
                    showAlert('Please wait, MCNO list is still loading...', 'info');
                    return;
                }

                var mcnoResult = getMcnoQtyData();
                if (!mcnoResult.isValid) {
                    showAlert('Please fill all MCNO, QTY, and SHIFT fields with valid data.', 'error');
                    return;
                }

                if (mcnoResult.data.length === 0) {
                    showAlert('Please add at least one MCNO and QTY', 'error');
                    return;
                }

                var selectedDescription = $('#knit_m_description').val();
                if (!selectedDescription || selectedDescription.trim() === '') {
                    showAlert('Please select a Knit M Description', 'error');
                    return;
                }

                var totalQty = 0;
                mcnoResult.data.forEach(function(item) {
                    totalQty += item.qty;
                });

                if (totalQty > targetQty) {
                    showAlert('Total quantity (' + totalQty.toFixed(2) + ') exceeds target quantity (' + targetQty.toFixed(2) + '). Please adjust.', 'error');
                    return;
                }

                var formData = {
                    booking: $('#booking').val(),
                    sono: $('#sono').val(),
                    style: $('#style').val(),
                    buyer: $('#buyer').val(),
                    supplier: $('#supplier').val(),
                    knit_m_description: selectedDescription,
                    yarn_type: $('#yarn_type').val(),
                    yarn_count: $('#yarn_count').val(),
                    fabrics_type: $('#fabrics_type').val(),
                    finish_gsm: $('#finish_gsm').val(),
                    finish_dia: $('#finish_dia').val(),
                    open_tube: $('#open_tube').val(),
                    lot_no: $('#lot_no').val(),
                    knit_material_code: $('#knit_material_code').val(),
                    knitting_target_qty: $('#knitting_target_qty').val(),
                    mcno_qty: mcnoResult.data
                };

                console.log('Form Data:', formData);

                var $submitBtn = $('#submitBtn');
                $submitBtn.prop('disabled', true).html('<span class="loading-spinner"></span> Saving...');

                $.ajax({
                    url: 'ajax_save_knitting_program.php',
                    method: 'POST',
                    contentType: 'application/json; charset=utf-8',
                    processData: false,
                    data: JSON.stringify(formData),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message || 'Program saved successfully!', 'success', 3000);
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            showAlert(response.message || response.error || 'Failed to save program.', 'error', 3000);
                        }
                    },
                    error: function(jqXHR) {
                        var message = 'Error saving program. Please try again.';
                        if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.message) {
                            message = jqXHR.responseJSON.message;
                        } else if (jqXHR && jqXHR.responseText) {
                            try {
                                var errorData = JSON.parse(jqXHR.responseText);
                                if (errorData && errorData.message) {
                                    message = errorData.message;
                                }
                            } catch (e) {
                                console.error('Save error response:', jqXHR.responseText);
                            }
                        }
                        showAlert(message, 'error', 3000);
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Save Program');
                    }
                });
            });
        });
    </script>

</body>

</html>