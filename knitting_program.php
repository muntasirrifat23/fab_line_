<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knitting | Program</title>

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
            padding: clamp(0.75rem, 2vw, 1.5rem);
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
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            padding: 12px 18px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            background: linear-gradient(135deg, #0f766e 0%, #0d8a80 50%, #0f9b8e 100%);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
        }

        .program-header h1 {
            grid-column: 2;
            grid-row: 1;
            font-weight: 700;
            font-size: clamp(1.25rem, 3vw, 2rem);
            color: #fff;
            margin: 0;
            text-align: center;
        }

        .program-header h1 i {
            color: #fff !important;
        }

        .program-header .btn-back {
            grid-column: 1;
            grid-row: 1;
            justify-self: start;
        }

        .program-header > div:last-child {
            grid-column: 3;
            grid-row: 1;
        }

        .btn-back {
            background: #000000;
            color: #ffffff;
            border: 2px solid #000000;
            padding: 0.6rem 1.6rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: #ffffff;
            border-color: #000000;
            color: #000000;
            transform: translateY(-2px);
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
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 1.2rem 1.5rem;
        }

        .info-item {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .remarks-item {
            grid-column: 1 / -1;
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
        .info-item input,
        .info-item select {
            font-weight: 600;
            font-size: 0.95rem;
            color: #0b2a4a;
            word-break: break-word;
        }

        .info-item input,
        .info-item select {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fbff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            width: 100%;
            margin-top: 0.15rem;
        }

        .info-item input:focus,
        .info-item select:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
            background: #ffffff;
        }

        .info-dropdown-control {
            display: flex;
            align-items: center;
            gap: 4px;
            width: 100%;
            margin-top: 0.15rem;
        }

        .info-dropdown-control input {
            min-width: 0;
            margin-top: 0;
        }

        .fabric-type-toggle {
            flex: 0 0 42px;
            height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #334155;
        }

        .fabric-type-toggle:hover {
            background: #e2e8f0;
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

        #lot_no {
            background: #f8fbff;
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
            background: rgba(255, 255, 255, 0.5);
            padding: 0.2rem 1rem;
            border-radius: 40px;
        }

        .mcno-qty-table {
            width: 100%;
            min-width: 840px;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
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

        .yarn-count-control {
            display: flex;
            position: relative;
            gap: 4px;
        }

        .yarn-count-control .yarn_count_row {
            min-width: 0;
        }

        .yarn-material-toggle {
            flex: 0 0 34px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            color: #334155;
        }

        .yarn-material-toggle:hover {
            background: #e2e8f0;
        }

        .yarn-material-menu {
            position: fixed;
            z-index: 10000;
            width: 300px;
            max-height: 280px;
            padding: 8px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        }

        .yarn-material-search {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            outline: none;
        }

        .yarn-material-search:focus {
            border-color: #2563eb;
        }

        .yarn-material-options {
            max-height: 220px;
            margin-top: 6px;
            overflow-y: auto;
        }

        .yarn-material-option {
            display: block;
            width: 100%;
            padding: 7px 9px;
            border: 0;
            border-radius: 6px;
            background: #fff;
            color: #0f172a;
            text-align: left;
        }

        .yarn-material-option:hover,
        .yarn-material-option.active {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .yarn-material-empty {
            padding: 8px 9px;
            color: #64748b;
            font-size: 0.85rem;
        }

        .btn-delete-row {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 10px;
            background: #fee2e2;
            color: #b91c1c;
            transition: 0.15s;
        }

        .btn-delete-row:hover {
            background: #fecaca;
            color: #991b1b;
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

        .summary-row {
            background: #eef4fa;
            font-weight: 700;
            border-top: 3px solid #2563eb;
        }

        .summary-row td {
            padding: 0.7rem 0.8rem;
            color: #0b2a4a;
        }

        .summary-row .summary-label {
            text-align: right;
            color: #000;
            font-size: 1rem;
            font-weight: 700;
        }

        @media (max-width: 576px) {
            .mcno-qty-table {
                min-width: 760px;
            }
        }

        .mcno-table-wrap {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            border-radius: 16px;
            -webkit-overflow-scrolling: touch;
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
            color: #000;
            font-size: 1rem;
            font-weight: 700;
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
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.10);
        }

        @media (max-width: 992px) {
            .card-unified {
                padding: 1.25rem;
            }

            .program-header h1 {
                font-size: clamp(1.45rem, 3vw, 1.8rem);
            }
        }

        @media (min-width: 577px) and (max-width: 768px) {
            .mcno-table-wrap {
                overflow: visible;
            }

            .mcno-qty-table {
                display: block;
                min-width: 0;
                overflow: visible;
                box-shadow: none;
            }

            .mcno-qty-table thead {
                display: none;
            }

            .mcno-qty-table tbody,
            .mcno-qty-table tfoot {
                display: block;
                width: 100%;
            }

            .mcno-qty-table tbody tr:not(.summary-row) {
                display: grid;
                grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.1fr) minmax(0, 1fr) 40px;
                grid-template-rows: auto auto;
                gap: 10px;
                padding: 12px;
                margin-bottom: 10px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #ffffff;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td {
                display: flex;
                min-width: 0;
                flex-direction: column;
                justify-content: flex-end;
                gap: 5px;
                padding: 0;
                border: 0;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td::before {
                content: attr(data-label);
                color: #64748b;
                font-size: 0.68rem;
                font-weight: 700;
                text-transform: uppercase;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(1) { grid-column: 1; grid-row: 1; }
            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(2) { grid-column: 2; grid-row: 1; }
            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(3) { grid-column: 3; grid-row: 1; }
            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(4) { grid-column: 1; grid-row: 2; }
            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(5) { grid-column: 2; grid-row: 2; }
            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(6) { grid-column: 3; grid-row: 2; }
            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(7) {
                grid-column: 4;
                grid-row: 2;
                align-items: center;
            }

            .mcno-qty-table input,
            .mcno-qty-table select {
                min-width: 0;
            }

            .mcno-qty-table tfoot .summary-row {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                padding: 12px;
            }

            .mcno-qty-table tfoot .summary-row td {
                display: block;
                min-width: 0;
                padding: 0;
                border: 0;
                text-align: left;
            }

            .mcno-qty-table tfoot .summary-row td[colspan],
            .mcno-qty-table tfoot .summary-row td:last-child {
                display: none;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 0.75rem;
            }

            .program-header {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
                gap: 0.5rem;
                margin-bottom: 1rem;
            }

            .program-header h1 {
                grid-column: 2;
                grid-row: 1;
                font-size: 1.1rem;
                text-align: center;
                line-height: 1.25;
                white-space: normal;
            }

            .program-header .btn-back {
                padding: 0.5rem 0.65rem;
            }

            .btn-back {
                padding: 0.5rem 0.9rem;
            }

            .card-unified {
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 18px;
            }

            .card-unified .card-title {
                align-items: flex-start;
                font-size: 0.95rem;
                margin-bottom: 0.9rem;
            }

            .search-controls {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 0.55rem;
            }

            .search-controls input {
                grid-column: 1 / -1;
                width: 100%;
                min-width: 0;
                padding: 0.65rem 0.9rem;
            }

            .search-controls button {
                width: 100%;
                padding: 0.6rem 0.7rem;
            }

            .info-grid-6 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.8rem;
            }

            .info-grid-6 .remarks-item {
                grid-column: 1 / -1;
            }

            .info-item input,
            .info-item select {
                padding: 0.65rem 0.7rem;
                border-radius: 12px;
                font-size: 0.9rem;
            }

            .target-qty-box {
                width: 100%;
                justify-content: space-between;
                gap: 0.5rem;
                padding: 0.65rem 0.85rem;
                border-radius: 16px;
            }

            .target-qty-box label {
                font-size: 0.78rem;
            }

            .target-qty-box .qty-value {
                font-size: 1.35rem;
            }

            .mcno-table-wrap {
                margin: 0;
                width: 100%;
                overflow: visible;
            }

            .mcno-qty-table {
                display: block;
                min-width: 0;
                margin-bottom: 0.5rem;
                overflow: visible;
                box-shadow: none;
            }

            .mcno-qty-table thead {
                display: none;
            }

            .mcno-qty-table tbody,
            .mcno-qty-table tfoot {
                display: block;
                width: 100%;
            }

            .mcno-qty-table tbody tr:not(.summary-row) {
                display: grid;
                grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.1fr) minmax(0, 1fr) 40px;
                grid-template-rows: auto auto;
                gap: 8px;
                padding: 10px;
                margin-bottom: 10px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #ffffff;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            }

            .mcno-qty-table tbody tr:not(.summary-row) td {
                display: flex;
                min-width: 0;
                flex-direction: column;
                justify-content: flex-end;
                gap: 4px;
                padding: 0;
                border: 0;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td::before {
                content: attr(data-label);
                color: #64748b;
                font-size: 0.62rem;
                font-weight: 700;
                line-height: 1.2;
                text-transform: uppercase;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(1) {
                grid-column: 1;
                grid-row: 1;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(2) {
                grid-column: 2;
                grid-row: 1;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(3) {
                grid-column: 3;
                grid-row: 1;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(4) {
                grid-column: 1;
                grid-row: 2;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(5) {
                grid-column: 2;
                grid-row: 2;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(6) {
                grid-column: 3;
                grid-row: 2;
            }

            .mcno-qty-table tbody tr:not(.summary-row) td:nth-child(7) {
                grid-column: 4;
                grid-row: 2;
                align-items: center;
            }

            .mcno-qty-table th {
                padding: 0.65rem 0.6rem;
                font-size: 0.68rem;
            }

            .mcno-qty-table input,
            .mcno-qty-table select {
                min-width: 0;
                padding: 0.5rem 0.35rem;
                border-radius: 9px;
                font-size: 0.76rem;
            }

            .mcno-qty-table tbody tr:not(.summary-row) .btn-delete-row {
                width: 34px;
                height: 36px;
            }

            .mcno-qty-table tfoot .summary-row {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                padding: 10px;
                border-radius: 10px;
            }

            .mcno-qty-table tfoot .summary-row td {
                display: block;
                min-width: 0;
                padding: 0;
                border: 0;
                text-align: left;
                font-size: 0.78rem;
            }

            .mcno-qty-table tfoot .summary-row td[colspan],
            .mcno-qty-table tfoot .summary-row td:last-child {
                display: none;
            }

            .summary-row td,
            .summary-label,
            .summary-total,
            .summary-remaining {
                font-size: 0.82rem;
            }

            .action-buttons {
                margin-top: 0.75rem;
            }

            .btn-add-row,
            .btn-submit {
                width: 100%;
                max-width: 320px;
                padding: 0.7rem 1rem;
            }
        }

        @media (max-width: 380px) {
            .info-grid-6 {
                grid-template-columns: minmax(0, 1fr);
            }

            .info-grid-6 .remarks-item {
                grid-column: auto;
            }

            .program-header h1 {
                font-size: 1.25rem;
            }
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
            <div class="search-controls">
                <input type="text" id="bookingInput" placeholder="Please Enter PO Number">
                <button class="btn-search" id="searchBtn"><i class="fa-solid fa-magnifying-glass me-1"></i>Search</button>
                <button class="btn-clear" id="clearBtn"><i class="fa-solid fa-rotate-left me-1"></i>Clear</button>
            </div>
            <div class="error-message" id="searchError"><i class="fa-solid fa-circle-exclamation me-1"></i>Not available PO number</div>
        </div>

        <!-- FORM CONTAINER -->
        <div class="card-unified hidden" id="formContainer">

            <div class="alert-box" id="alertBox"></div>

            <div class="card-title"><i class="fa-regular fa-file-lines"></i> Knitting Program (Default Data)</div>

            <form id="knittingForm">

                <div>
                    <div class="info-grid-6" id="infoGrid">
                        <div class="info-item"><label>PO Number</label><span id="display_booking">-</span><input type="hidden" id="booking"></div>
                        <div class="info-item"><label>SONO</label><span id="display_sono">-</span><input type="hidden" id="sono"></div>
                        <div class="info-item"><label>Buyer</label><span id="display_buyer">-</span><input type="hidden" id="buyer"></div>
                        <div class="info-item"><label>STYLE</label><span id="display_style">-</span><input type="hidden" id="style"></div>
                        <div class="info-item"><label>CUSTOMER</label><span id="display_customer">-</span><input type="hidden" id="customer"></div>
                        <div class="info-item"><label>Yarn Type</label><span id="display_yarn_type">-</span><input type="hidden" id="yarn_type"></div>
                        <div class="info-item"><label>Finish GSM</label><select id="finish_gsm"></select></div>
                        <div class="info-item"><label>Finish DIA</label><input type="text" id="finish_dia" placeholder="Finish DIA"></div>
                        <div class="info-item"><label>Open / Tube</label><input type="text" id="open_tube" placeholder="Open / Tube"></div>
                        <div class="info-item">
                            <label for="fabrics_type">Fabric Type</label>
                            <div class="info-dropdown-control">
                                <input type="text" id="fabrics_type" placeholder="Fabric Type" autocomplete="off">
                                <button type="button" class="fabric-type-toggle" id="fabricTypeToggle" title="Browse fabric types" aria-label="Browse fabric types">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="info-item">
                            <label for="category">Category</label>
                            <select id="category">
                                <option value="In House (Bulk)">In House (Bulk)</option>
                                <option value="In House (Sample)">In House (Sample)</option>
                                <option value="Knitting (Service)">Knitting (Service)</option>
                                <option value="Knitting (Outside)">Knitting (Outside)</option>
                            </select>
                        </div>
                        <input type="hidden" id="knit_material_code">
                    </div>
                    <input type="hidden" id="knit_m_description">
                </div>

                <div id="detailsContainer">
                    <div class="card-title" style="margin-top:1.8rem; border-top:2px solid #eef2f6; padding-top:1.2rem;"><i class="fa-solid fa-scissors"></i> Enter Fabric Details</div>
                    <div class="info-grid-6" id="infoGrid">
                        <div class="info-item"><label>MC DIA</label><input type="text" id="mc_dia" placeholder="Enter MC DIA"></div>
                        <div class="info-item"><label>SL/VQ</label><input type="text" id="sl_vdq" placeholder="Enter SL/VQ"></div>
                        <div class="info-item"><label>Feeder Plan</label><input type="text" id="feeder_plan" placeholder="Enter Feeder Plan"></div>
                        <div class="info-item remarks-item"><label>Remarks</label><textarea id="remarks" rows="2" placeholder="Enter remarks (optional)" style="padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 16px; background: #f8fbff; width: 100%; font-weight: 500; resize: vertical;"></textarea></div>
                    </div>
                </div>

                <!-- MACHINE NO & QTY -->
                <!-- <div class="card-title" style="margin-top:2rem; border-top:2px solid #eef2f6; padding-top:1.2rem;"><i class="fa-solid fa-list-check"></i> Enter Quantity</div> -->

                <div class="target-qty-box" style="margin-top:2rem;">
                    <label>Total Knitting Target QTY :</label>
                    <span class="qty-value" id="display_target_qty">0.00</span>
                </div>

                <div class="mcno-table-wrap">
                <table class="mcno-qty-table">
                    <thead>
                        <tr>
                            <th>Yarn Count</th>
                            <th>Yarn Brand</th>
                            <th>Color</th>
                            <th>Lot No</th>
                            <th>QTY</th>
                            <th>Remaining</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="mcnoQtyTableBody"></tbody>
                    <tfoot>
                        <tr class="summary-row">
                            <td colspan="4"></td>
                            <td class="summary-label">Total: <span class="summary-total" id="totalQtyDisplay">0.00</span></td>
                            <td class="summary-remaining">Remaining: <span id="totalRemainingDisplay">0.00</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>

                <div class="action-buttons">
                    <button type="button" class="btn-add-row" id="addMcnoRowBtn">
                        <i class="fa-solid fa-plus me-2"></i>Add New Data
                    </button>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        (function($) {
            "use strict";

            // ---------- state ----------
            var bookingData = null;
            var allRowsData = [];
            var targetQty = 0;
            var originalTargetQty = 0;
            var allocatedByDescription = {};
            var allocatedByDescriptionColor = {};
            var pendingColor = '';
            var colorOptions = [];
            var pendingFinishGsm = '';

            function setAvailableColor(value) {
                pendingColor = $.trim(value || '');
                if (pendingColor && colorOptions.indexOf(pendingColor) === -1) {
                    colorOptions.push(pendingColor);
                }
                $('#mcnoQtyTableBody .color_row').each(function() {
                    populateColorSelect($(this), pendingColor);
                });
            }

            function setColorOptions(values) {
                colorOptions = (values || []).map(function(value) {
                    return $.trim(String(value || ''));
                }).filter(function(value, index, list) {
                    return value !== '' && list.indexOf(value) === index;
                });
                if (pendingColor && colorOptions.indexOf(pendingColor) === -1) {
                    colorOptions.push(pendingColor);
                }
                $('#mcnoQtyTableBody .color_row').each(function() {
                    var select = $(this);
                    populateColorSelect(select, select.val() || pendingColor);
                });
            }

            function populateColorSelect(select, selectedValue) {
                var selected = $.trim(selectedValue || '');
                select.empty().append($('<option>', { value: '', text: 'Select Color' }));
                colorOptions.forEach(function(value) {
                    select.append($('<option>', { value: value, text: value }));
                });
                if (selected && colorOptions.indexOf(selected) === -1) {
                    select.append($('<option>', { value: selected, text: selected }));
                }
                select.val(selected);
            }

            function setAvailableFinishGsm(value) {
                pendingFinishGsm = $.trim(value || '');
                var finishGsmExists = false;
                $('#finish_gsm option').each(function() {
                    if ($(this).val() === pendingFinishGsm) {
                        finishGsmExists = true;
                        return false;
                    }
                });
                $('#finish_gsm').val(finishGsmExists ? pendingFinishGsm : '');
            }

            // ---------- load form data ----------
            function loadFormData(booking) {
                $('#searchBtn').prop('disabled', true).html('<span class="loading-spinner"></span>');
                $.ajax({
                    url: 'ajaxKnittingProgram.php',
                    data: {
                        booking: booking
                    },
                    dataType: 'json',
                    method: 'GET',
                    timeout: 30000,
                    beforeSend: function() {
                        $('#formContainer').addClass('hidden');
                        $('#detailsContainer').removeClass('visible');
                        $('#alertBox').html('');
                    },
                    success: function(resp) {
                        if (resp && resp.success && resp.data) {
                            window.bookingResponse = resp;
                            bookingData = resp.data;
                            allRowsData = resp.all_data || [];
                            allocatedByDescription = resp.allocated_by_description || {};
                            allocatedByDescriptionColor = resp.allocated_by_description_color || {};
                            var poTotal = parseFloat(resp.po_total_qty || resp.data.KNITTING_TARGET_QTY || resp.data.QTY) || 0;
                            originalTargetQty = poTotal;
                            var allocated = parseFloat(allocatedByDescription[resp.data.KNIT_M_DESCRIPTION]) || 0;
                            targetQty = originalTargetQty - allocated;

                            // Populate color and finish_gsm dropdowns based on PO-specific data
                            if (allRowsData && allRowsData.length) {
                                var colorSet = {};
                                var fgSet = {};
                                for (var i = 0; i < allRowsData.length; i++) {
                                    var r = allRowsData[i];
                                    if (r.COLOR) colorSet[String(r.COLOR).trim()] = true;
                                    if (r.FINISH_GSM) fgSet[String(r.FINISH_GSM).trim()] = true;
                                }
                                var fgSelect = $('#finish_gsm');
                                fgSelect.empty();
                                pendingColor = '';
                                setColorOptions(Object.keys(colorSet).sort());
                                Object.keys(fgSet).sort(function(a, b) {
                                    var na = parseFloat(a) || 0,
                                        nb = parseFloat(b) || 0;
                                    if (na !== nb) return na - nb;
                                    return String(a).localeCompare(String(b));
                                }).forEach(function(v) {
                                    fgSelect.append($('<option>', {
                                        value: v,
                                        text: v
                                    }));
                                });
                                setAvailableFinishGsm(resp.data.FINISH_GSM);
                                setAvailableColor(resp.data.COLOR);
                            }

                            renderForm(bookingData);
                            setKnitMDescription(resp);
                            $('#sl_vdq, #mc_dia, #feeder_plan').val('');
                            $('#remarks').val('');
                            checkSubmitButton();
                            $('#formContainer').removeClass('hidden');

                        } else {
                            $('#formContainer').addClass('hidden');
                            showPageAlert(resp.error || 'Not available PO number', 'error', 5000);
                        }
                    },
                    error: function() {
                        $('#formContainer').addClass('hidden');
                        showPageAlert('Not available PO number', 'error', 5000);
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

                $('#finish_gsm').val('');
                $('#finish_dia').val(data.FINISH_DIA || '');
                $('#open_tube').val(data.OPEN_TUBE || '');
                $('#fabrics_type').val(data.FABRICS_TYPE || '');
                $('#yarn_type').val(data.YARN_TYPE || '');
                $('#feeder_plan').val('');
                $('#knit_material_code').val(data.KNIT_MATERIAL_CODE || '');
                $('#knit_m_description').val(data.KNIT_M_DESCRIPTION || '');
                $('#customer').val(data.CUSTOMER || '');
                $('#yarn_count').val('');
                $('#sl_vdq').val('');
$('#mc_dia').val('');
                    $('#lot_no').val('');
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

                $('#display_customer').text(rowData.CUSTOMER || '-');
                $('#customer').val(rowData.CUSTOMER || '');

                // Rebuild dropdowns for this specific knit_m_description if available
                if (allRowsData && allRowsData.length) {
                    var colorSet = {},
                        fgSet = {};
                    for (var i = 0; i < allRowsData.length; i++) {
                        var r = allRowsData[i];
                        if (r.COLOR) colorSet[String(r.COLOR).trim()] = true;
                        if (r.FINISH_GSM) fgSet[String(r.FINISH_GSM).trim()] = true;
                    }
                    var fgSelect = $('#finish_gsm');
                    fgSelect.empty();
                    pendingColor = '';
                    setColorOptions(Object.keys(colorSet).sort());
                    Object.keys(fgSet).sort(function(a, b) {
                        var na = parseFloat(a) || 0,
                            nb = parseFloat(b) || 0;
                        if (na !== nb) return na - nb;
                        return String(a).localeCompare(String(b));
                    }).forEach(function(v) {
                        fgSelect.append($('<option>', {
                            value: v,
                            text: v
                        }));
                    });
                }

                setAvailableFinishGsm(rowData.FINISH_GSM);
                setAvailableColor(rowData.COLOR);
                $('#finish_dia').val(rowData.FINISH_DIA || '');
                $('#open_tube').val(rowData.OPEN_TUBE || '');
                $('#fabrics_type').val(rowData.FABRICS_TYPE || '');
                $('#display_yarn_type').text(rowData.YARN_TYPE || '-');
                $('#yarn_type').val(rowData.YARN_TYPE || '');
                $('#display_knit_material_code').text(rowData.KNIT_MATERIAL_CODE || '-');
                $('#knit_material_code').val(rowData.KNIT_MATERIAL_CODE || '');
                $('#display_knit_m_description').text(rowData.KNIT_M_DESCRIPTION || '-');
                $('#knit_m_description').val(rowData.KNIT_M_DESCRIPTION || '');
                $('#customer').val(rowData.CUSTOMER || '');
                $('#yarn_count').val('');
                $('#sl_vdq').val('');
$('#mc_dia').val('');
                    $('#feeder_plan').val('');
                $('#lot_no').val('');

                var poTotal = parseFloat((window.bookingResponse && window.bookingResponse.po_total_qty) || rowData.KNITTING_TARGET_QTY || rowData.QTY) || 0;
                originalTargetQty = getDescriptionTargetQty(description, poTotal);
                var allocated = getDescriptionAllocatedQty(description);
                targetQty = originalTargetQty - allocated;
                $('#display_target_qty').text(targetQty > 0 ? targetQty.toFixed(2) : '0.00');
                $('#knitting_target_qty').val(originalTargetQty);
                $('#remarks').val('');

                resetMcnoRows();
                $('#detailsContainer').addClass('visible');
            }

            function normalizeColor(value) {
                return $.trim(String(value || '')).toUpperCase();
            }

            function normalizeDescription(value) {
                return $.trim(String(value || '')).toUpperCase();
            }

            function getDescriptionTargetQty(description, fallback) {
                if (!allRowsData.length) return fallback;
                var target = 0;
                allRowsData.forEach(function(rowData) {
                    if (normalizeDescription(rowData.KNIT_M_DESCRIPTION) === normalizeDescription(description)) {
                        target += parseFloat(rowData.QTY) || 0;
                    }
                });
                return target;
            }

            function getDescriptionAllocatedQty(description) {
                var normalizedDescription = normalizeDescription(description);
                var allocated = 0;
                Object.keys(allocatedByDescription).forEach(function(key) {
                    if (normalizeDescription(key) === normalizedDescription) {
                        allocated += parseFloat(allocatedByDescription[key]) || 0;
                    }
                });
                return allocated;
            }

            function getColorRemainingQty(description, color) {
                var normalizedDescription = normalizeDescription(description);
                var normalizedColor = normalizeColor(color);
                if (!normalizedDescription || !normalizedColor) return 0;

                var target = 0;
                allRowsData.forEach(function(rowData) {
                    if (normalizeDescription(rowData.KNIT_M_DESCRIPTION) === normalizedDescription &&
                        normalizeColor(rowData.COLOR) === normalizedColor) {
                        target += parseFloat(rowData.QTY) || 0;
                    }
                });
                var allocations = allocatedByDescriptionColor[normalizedDescription] || {};
                var allocated = parseFloat(allocations[normalizedColor]) || 0;
                return Math.max(0, target - allocated);
            }

            function areRowsWithinColorTargets() {
                var totalByColor = {};
                var valid = true;
                $('#mcnoQtyTableBody tr').each(function() {
                    var color = normalizeColor($(this).find('.color_row').val());
                    var qty = parseFloat($(this).find('.qty-input').val()) || 0;
                    if (qty <= 0) return true;
                    if (!color) {
                        valid = false;
                        return false;
                    }
                    totalByColor[color] = (totalByColor[color] || 0) + qty;
                    if (totalByColor[color] - getColorRemainingQty($('#knit_m_description').val(), color) > 0.000001) {
                        valid = false;
                        return false;
                    }
                });
                return valid;
            }

            function resetMcnoRows() {
                $('#mcnoQtyTableBody').html('');
                addMcnoRow();
                updateRemainingQty();
            }

            // ---------- row management ----------
            function addMcnoRow() {
                var tbody = $('#mcnoQtyTableBody');
                var row = $('<tr>');
                var yarnCountInput = $('<input>', {
                    type: 'text',
                    class: 'yarn_count_row',
                    placeholder: 'Yarn Count',
                    'aria-label': 'Yarn Count'
                }).attr('autocomplete', 'off');
                var yarnMaterialToggle = $('<button>', {
                    type: 'button',
                    class: 'yarn-material-toggle',
                    title: 'Browse yarn materials',
                    'aria-label': 'Browse yarn materials'
                }).append($('<i>', { class: 'fa-solid fa-chevron-down' }));
                row.append($('<td>', { 'data-label': 'Yarn Count' }).append(
                    $('<div>', { class: 'yarn-count-control' }).append(yarnCountInput, yarnMaterialToggle)
                ));
                row.append($('<td>', { 'data-label': 'Yarn Brand' }).html('<input type="text" class="yarn_brand_row" placeholder="Yarn Brand">'));
                var colorSelect = $('<select>', { class: 'color_row', 'aria-label': 'Color' });
                populateColorSelect(colorSelect, pendingColor);
                row.append($('<td>', { 'data-label': 'Color' }).append(colorSelect));
                row.append($('<td>', { 'data-label': 'Lot No' }).html('<input type="text" class="lot_no_row" placeholder="Lot No">'));
                row.append($('<td>', { 'data-label': 'QTY' }).html('<input type="number" class="qty-input" placeholder="QTY" step="0.01">'));
                row.append($('<td>', { 'data-label': 'Remaining' }).html('<input type="text" class="remaining-qty" readonly placeholder="Remaining">'));
                row.append($('<td>', { 'data-label': 'Action' }).append(
                    $('<button>', {
                        type: 'button',
                        class: 'btn-delete-row',
                        title: 'Delete row',
                        'aria-label': 'Delete this yarn row'
                    }).append($('<i>', {
                        class: 'fa-solid fa-trash'
                    }))
                ));

                tbody.append(row);

                 row.find('.qty-input, .yarn_count_row, .yarn_brand_row, .color_row, .lot_no_row').on('input change', function() {
                     updateRemainingQty();
                     checkSubmitButton();
                     updateAddRowButton();
                 });
                 updateRemainingQty();
                 checkSubmitButton();
                 updateAddRowButton();
            }

            function updateAddRowButton() {
                var rows = $('#mcnoQtyTableBody tr');
                var allRowsComplete = true;
                var totalQty = 0;

                rows.each(function() {
                    var yarnCount = $.trim($(this).find('.yarn_count_row').val() || '');
                    var yarnBrand = $.trim($(this).find('.yarn_brand_row').val() || '');
                    var lotNo = $.trim($(this).find('.lot_no_row').val() || '');
                    var qtyValue = $.trim($(this).find('.qty-input').val() || '');
                    var qty = parseFloat(qtyValue);

                    var color = normalizeColor($(this).find('.color_row').val());
                    if (!yarnCount || !yarnBrand || !lotNo || !color || !qtyValue || isNaN(qty) || qty <= 0) {
                        allRowsComplete = false;
                    } else {
                        totalQty += qty;
                    }
                });

                var withinTarget = targetQty > 0 && totalQty <= targetQty && areRowsWithinColorTargets();
                $('#addMcnoRowBtn').prop('disabled', !(allRowsComplete && withinTarget));
            }

            function updateRemainingQty() {
                var totalQty = 0;
                var rows = $('#mcnoQtyTableBody tr');
                var overAlertShown = false;
                var usedByColor = {};
                var description = $('#knit_m_description').val();
                rows.each(function() {
                    var rowQty = parseFloat($(this).find('.qty-input').val()) || 0;
                    var color = normalizeColor($(this).find('.color_row').val());
                    if (!color) {
                        $(this).find('.remaining-qty').val('');
                        totalQty += rowQty;
                        return true;
                    }
                    var availableBefore = Math.max(0, getColorRemainingQty(description, color) - (usedByColor[color] || 0));
                    if (!overAlertShown && rowQty > availableBefore) {
                        showPageAlert('Over QTY for color ' + color + '. Remaining color quantity is ' + availableBefore.toFixed(2) + '.', 'error', 3000);
                        overAlertShown = true;
                    }
                    usedByColor[color] = (usedByColor[color] || 0) + rowQty;
                    totalQty += rowQty;
                    var newRemaining = getColorRemainingQty(description, color) - usedByColor[color];
                    $(this).find('.remaining-qty').val(newRemaining >= 0 ? newRemaining.toFixed(2) : '0.00');
                });
                $('#totalQtyDisplay').text(totalQty.toFixed(2));
                var remainingTotal = targetQty - totalQty;
                $('#totalRemainingDisplay').text(remainingTotal >= 0 ? remainingTotal.toFixed(2) : '0.00');
                if (totalQty > targetQty && !overAlertShown) {
                    showAlert('Total QTY exceeds target. Available qty is 0.00.', 'error');
                }
            }

            var activeYarnCountInput = null;
            var yarnMaterialRequest = null;
            var yarnMaterialSearchTimer = null;
            var yarnMaterialActiveIndex = -1;
            var yarnMaterialEnterPending = false;
            var yarnMaterialQuery = '';
            var yarnMaterialOffset = 0;
            var yarnMaterialHasMore = false;
            var yarnMaterialLoading = false;
            var yarnMaterialRequestVersion = 0;
            var yarnMaterialPendingMove = false;

            function getYarnMaterialMenu() {
                var menu = $('#yarnMaterialMenu');
                if (!menu.length) {
                    menu = $('<div>', {
                        id: 'yarnMaterialMenu',
                        class: 'yarn-material-menu',
                        role: 'dialog',
                        'aria-label': 'Yarn material suggestions'
                    }).append(
                        $('<input>', {
                            type: 'search',
                            class: 'yarn-material-search',
                            placeholder: 'Search materials...',
                            'aria-label': 'Search yarn materials'
                        }).attr('autocomplete', 'off'),
                        $('<div>', {
                            class: 'yarn-material-options',
                            role: 'listbox'
                        }).on('scroll', function() {
                            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 40) {
                                loadMoreYarnMaterials();
                            }
                        })
                    ).appendTo(document.body).hide();
                }
                return menu;
            }

            function positionYarnMaterialMenu(input) {
                var menu = getYarnMaterialMenu();
                var rect = input.getBoundingClientRect();
                var width = Math.min(Math.max(rect.width + 38, 300), Math.max(1, window.innerWidth - 16));
                var left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
                menu.css({
                    left: left + 'px',
                    top: Math.max(8, Math.min(rect.bottom + 4, window.innerHeight - 290)) + 'px',
                    width: width + 'px'
                });
            }

            function cancelYarnMaterialSearch() {
                clearTimeout(yarnMaterialSearchTimer);
                yarnMaterialSearchTimer = null;
                if (yarnMaterialRequest) {
                    yarnMaterialRequest.abort();
                    yarnMaterialRequest = null;
                }
                yarnMaterialLoading = false;
            }

            function selectYarnMaterial(value) {
                if (!activeYarnCountInput) return;
                cancelYarnMaterialSearch();
                $(activeYarnCountInput).val(value).trigger('change');
                getYarnMaterialMenu().hide();
                activeYarnCountInput = null;
                yarnMaterialEnterPending = false;
            }

            function renderYarnMaterials(values, message, append) {
                var menu = getYarnMaterialMenu();
                var options = menu.find('.yarn-material-options');
                if (!append) {
                    options.empty();
                    yarnMaterialActiveIndex = -1;
                } else {
                    options.find('.yarn-material-empty').remove();
                }

                if (!values || !values.length) {
                    if (!append || message || !options.find('.yarn-material-option').length) {
                        options.append($('<div>', {
                            class: 'yarn-material-empty',
                            text: message || 'No yarn materials found.'
                        }));
                    }
                    return;
                }

                values.forEach(function(value) {
                    options.append($('<button>', {
                        type: 'button',
                        class: 'yarn-material-option',
                        role: 'option',
                        text: value
                    }).on('click', function() {
                        selectYarnMaterial(value);
                    }));
                });
            }

            function searchYarnMaterials(input, query) {
                activeYarnCountInput = input;
                yarnMaterialQuery = $.trim(String(query || ''));
                yarnMaterialOffset = 0;
                yarnMaterialHasMore = false;
                yarnMaterialLoading = false;
                yarnMaterialPendingMove = false;
                var requestVersion = ++yarnMaterialRequestVersion;
                var menu = getYarnMaterialMenu();
                positionYarnMaterialMenu(input);
                menu.show();

                if (yarnMaterialRequest) {
                    yarnMaterialRequest.abort();
                    yarnMaterialRequest = null;
                }
                renderYarnMaterials([], 'Loading materials...');
                menu.find('.yarn-material-options').scrollTop(0);
                loadYarnMaterialPage(input, requestVersion, false);
            }

            function loadYarnMaterialPage(input, requestVersion, append) {
                if (yarnMaterialLoading || (append && !yarnMaterialHasMore)) return;
                yarnMaterialLoading = true;
                var menu = getYarnMaterialMenu();
                if (append) {
                    menu.find('.yarn-material-empty').remove();
                    menu.find('.yarn-material-options').append($('<div>', {
                        class: 'yarn-material-empty',
                        text: 'Loading more materials...'
                    }));
                }
                var requestOffset = yarnMaterialOffset;
                var request = $.ajax({
                    url: 'ajaxKnittingProgram.php',
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'yarn_material_list',
                        search: yarnMaterialQuery,
                        offset: requestOffset
                    }
                }).done(function(resp) {
                    if (requestVersion !== yarnMaterialRequestVersion) return;
                    yarnMaterialLoading = false;
                    if (yarnMaterialRequest === request) yarnMaterialRequest = null;
                    if (activeYarnCountInput !== input || !menu.is(':visible')) return;
                    if (resp && resp.success && Array.isArray(resp.data)) {
                        yarnMaterialOffset += resp.data.length;
                        yarnMaterialHasMore = resp.has_more === true;
                        renderYarnMaterials(resp.data, '', append);
                        if (yarnMaterialHasMore) {
                            menu.find('.yarn-material-options').append($('<div>', {
                                class: 'yarn-material-empty',
                                text: 'Scroll to load more materials...'
                            }));
                        }
                        if (yarnMaterialPendingMove && resp.data.length) {
                            yarnMaterialPendingMove = false;
                            moveYarnMaterialSelection(1);
                        } else if (yarnMaterialPendingMove) {
                            yarnMaterialPendingMove = false;
                        }
                        if (yarnMaterialEnterPending) {
                            yarnMaterialEnterPending = false;
                            chooseActiveYarnMaterial();
                        }
                    } else {
                        yarnMaterialEnterPending = false;
                        yarnMaterialHasMore = false;
                        renderYarnMaterials([], 'Unable to load yarn materials.', append);
                    }
                }).fail(function(xhr, status) {
                    if (status === 'abort' || requestVersion !== yarnMaterialRequestVersion) return;
                    yarnMaterialLoading = false;
                    if (yarnMaterialRequest === request) yarnMaterialRequest = null;
                    if (activeYarnCountInput !== input) return;
                    yarnMaterialEnterPending = false;
                    yarnMaterialPendingMove = false;
                    renderYarnMaterials([], 'Unable to load yarn materials.', append);
                });
                yarnMaterialRequest = request;
            }

            function loadMoreYarnMaterials() {
                if (!activeYarnCountInput || !yarnMaterialHasMore || yarnMaterialLoading) return;
                loadYarnMaterialPage(activeYarnCountInput, yarnMaterialRequestVersion, true);
            }

            function closeYarnMaterialMenu() {
                cancelYarnMaterialSearch();
                getYarnMaterialMenu().hide();
                activeYarnCountInput = null;
                yarnMaterialActiveIndex = -1;
                yarnMaterialEnterPending = false;
                yarnMaterialPendingMove = false;
            }

            function moveYarnMaterialSelection(direction) {
                var options = getYarnMaterialMenu().find('.yarn-material-option');
                if (!options.length) return;
                if (direction > 0 && yarnMaterialActiveIndex === options.length - 1 &&
                    yarnMaterialHasMore && !yarnMaterialLoading) {
                    yarnMaterialPendingMove = true;
                    loadMoreYarnMaterials();
                    return;
                }
                yarnMaterialActiveIndex = (yarnMaterialActiveIndex + direction + options.length) % options.length;
                options.removeClass('active').eq(yarnMaterialActiveIndex).addClass('active')[0].scrollIntoView({
                    block: 'nearest'
                });
            }

            function chooseActiveYarnMaterial() {
                var options = getYarnMaterialMenu().find('.yarn-material-option');
                if (!options.length) return;
                var index = yarnMaterialActiveIndex < 0 ? 0 : yarnMaterialActiveIndex;
                options.eq(index).trigger('click');
            }

            function isManualDataValid() {
                // Only user-editable visible inputs are required here.
                // Hidden fields (customer, knit_m_description) come from the PO and
                // must not block the Save button since the user cannot type into them.
                var requiredFields = [
                    '#finish_dia',
                    '#open_tube',
                    '#fabrics_type',
                    '#sl_vdq',
                    '#mc_dia',
                    '#feeder_plan'
                ];
                for (var i = 0; i < requiredFields.length; i++) {
                    var value = $(requiredFields[i]).val();
                    if (!value || value.toString().trim() === '') {
                        return false;
                    }
                }
                return true;
            }

             function checkSubmitButton() {
                 var rows = $('#mcnoQtyTableBody tr');
                 var allValid = true;
                 var hasData = false;

                 rows.each(function() {
                     var yarnCount = $(this).find('.yarn_count_row').val().trim();
                     var yarnBrand = $(this).find('.yarn_brand_row').val().trim();
                     var color = $.trim($(this).find('.color_row').val() || '');
                     var lotNoRow = $(this).find('.lot_no_row').val().trim();
                     var qty = $(this).find('.qty-input').val().trim();
                     var isEmpty = !qty && !yarnCount && !yarnBrand && !color && !lotNoRow;
                     if (isEmpty) return true;
                     hasData = true;
                     if (!qty || !yarnCount || !yarnBrand || !lotNoRow) {
                         allValid = false;
                         return false;
                     }
                     if (!color) {
                         allValid = false;
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
                 if (hasData && (totalQty > targetQty || !areRowsWithinColorTargets())) allValid = false;

                 var manualValid = isManualDataValid();
                 var submitEnabled = allValid && hasData && manualValid;
                 $('#submitBtn').prop('disabled', !submitEnabled);
             }

            function getMcnoQtyData() {
                var data = [];
                var isValid = true;
                var rows = $('#mcnoQtyTableBody tr');
                if (rows.length === 0) return {
                    data: [],
                    isValid: false
                };
                rows.each(function() {
                    var yarnCount = $(this).find('.yarn_count_row').val().trim();
                    var yarnBrand = $(this).find('.yarn_brand_row').val().trim();
                    var color = $.trim($(this).find('.color_row').val() || '');
                    var lotNoRow = $(this).find('.lot_no_row').val().trim();
                    var qty = $(this).find('.qty-input').val().trim();
                    var isEmpty = !qty && !yarnCount && !yarnBrand && !color && !lotNoRow;
                    if (isEmpty) return true;
                    if (!qty || !yarnCount || !yarnBrand || !lotNoRow) {
                        isValid = false;
                        return false;
                    }
                    if (!color) {
                        isValid = false;
                        return false;
                    }
                    var qtyNum = parseFloat(qty);
                    if (isNaN(qtyNum) || qtyNum <= 0) {
                        isValid = false;
                        return false;
                    }
                    data.push({
                        qty: qtyNum,
                        yarn_count: yarnCount,
                        yarn_brand: yarnBrand,
                        color: $.trim($(this).find('.color_row').val() || ''),
                        lot_no: lotNoRow
                    });
                });
                return {
                    data: data,
                    isValid: isValid
                };
            }

            // ---------- alert ----------
            function showAlert(msg, type, duration) {
                var cls = type === 'error' ? 'alert-danger' : (type === 'info' ? 'alert-info' : 'alert-success');
                var icon = type === 'error' ? 'circle-exclamation' : (type === 'info' ? 'circle-info' : 'circle-check');
                $('#alertBox').html(
                    '<div class="alert ' + cls + '"><i class="fa-solid fa-' + icon + ' me-2"></i>' + msg + '</div>'
                );
                if (duration !== false) {
                    setTimeout(function() {
                        $('#alertBox').html('');
                    }, duration || 3000);
                }
            }

            function showPageAlert(msg, type, duration) {
                var cls = type === 'error' ? 'alert-danger' : (type === 'info' ? 'alert-info' : 'alert-success');
                $('#pageAlert').removeClass('alert-danger alert-info alert-success').addClass(cls).html(
                    '<i class="fa-solid fa-' + (type === 'error' ? 'triangle-exclamation' : (type === 'info' ? 'circle-info' : 'circle-check')) + ' me-2"></i>' + msg
                ).fadeIn(150);
                if (duration !== false) {
                    setTimeout(function() {
                        $('#pageAlert').fadeOut(150);
                    }, duration || 3000);
                }
            }

            // ---------- ready ----------
            $(function() {
                sessionStorage.removeItem('kp_booking');

                var $fabricTypeField = $('#fabrics_type');
                $fabricTypeField.autocomplete({
                    minLength: 0,
                    delay: 180,
                    source: function(request, response) {
                        $fabricTypeField.autocomplete('widget').empty();
                        $.ajax({
                            url: 'ajaxKnittingProgram.php',
                            method: 'GET',
                            dataType: 'json',
                            data: {
                                action: 'fabric_type_list',
                                search: request.term
                            }
                        }).done(function(resp) {
                            if (resp && resp.success && Array.isArray(resp.data)) {
                                response(resp.data);
                            } else {
                                response([]);
                                showPageAlert('Unable to load fabric types.', 'error', 3000);
                            }
                        }).fail(function(xhr, status) {
                            response([]);
                            if (status !== 'abort') {
                                showPageAlert('Unable to load fabric types.', 'error', 3000);
                            }
                        });
                    }
                });

                $('#fabricTypeToggle').on('click', function() {
                    $fabricTypeField.autocomplete('search', '');
                    $fabricTypeField.trigger('focus');
                });

                // Load all Finish GSM options from database for dropdown
                $.ajax({
                    url: 'ajaxKnittingProgram.php',
                    data: {
                        action: 'finish_gsm_list'
                    },
                    dataType: 'json',
                    method: 'GET'
                }).done(function(resp) {
                    if (resp && resp.success) {
var finishGsmSelect = $('#finish_gsm');
                    finishGsmSelect.empty();
                    resp.data.forEach(function(v) {
                            var value = String(v);
                            finishGsmSelect.append($('<option>', {
                                value: value,
                                text: value
                            }));
                        });
                        setAvailableFinishGsm(pendingFinishGsm || (bookingData && bookingData.FINISH_GSM));
                    }
                });

                // Load all available colors for the per-yarn-row dropdowns.
                $.ajax({
                    url: 'ajaxKnittingProgram.php',
                    data: {
                        action: 'color_list'
                    },
                    dataType: 'json',
                    method: 'GET'
                }).done(function(resp) {
                    if (resp && resp.success) {
                        var values = (bookingData && allRowsData.length)
                            ? allRowsData.map(function(row) { return row.COLOR; })
                            : resp.data;
                        setColorOptions(values);
                        if (!pendingColor && bookingData) setAvailableColor(bookingData.COLOR);
                    }
                });

                $('#backBtn').on('click', function() {
                    window.location.href = 'initialPage.php';
                });

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

                $('#clearBtn').on('click', function() {
                    sessionStorage.removeItem('kp_booking');
                    $('#bookingInput').val('');
                    $('#formContainer').addClass('hidden');
                    $('#alertBox').html('');
                    bookingData = null;
                    allRowsData = [];
                    window.bookingResponse = null;
                    targetQty = 0;
                    allocatedByDescription = {};
                    allocatedByDescriptionColor = {};
                    pendingColor = '';
                    pendingFinishGsm = '';
                    setColorOptions([]);
                    pendingColor = '';
                    $('#finish_gsm').val('');
                    $('#detailsContainer').removeClass('visible');
                    $('#display_target_qty').text('0.00');
                    $('#knitting_target_qty').val('');
                    $('#selected_desc_label').html('');
                     $('#mcnoQtyTableBody').html('');
                     $('#totalQtyDisplay').text('0.00');
                     $('#totalRemainingDisplay').text('0.00');
                     // reset all display fields
                     $('#infoGrid span').text('-');
                     $('#finish_dia, #open_tube, #fabrics_type, #customer, #sl_vdq, #mc_dia, #feeder_plan, #knit_m_description, #remarks').val('');
                     $('#category').val('In House (Bulk)');
                     addMcnoRow();
                     $('#submitBtn').prop('disabled', true);
                 });

                $('#addMcnoRowBtn').on('click', function() {
                    addMcnoRow();
                });

                $('#mcnoQtyTableBody').on('click', '.btn-delete-row', function() {
                    $(this).closest('tr').remove();
                    updateRemainingQty();
                    checkSubmitButton();
                    updateAddRowButton();
                });

                $('#bookingInput').on('keypress', function(e) {
                    if (e.which === 13) $('#searchBtn').click();
                });
                $('#bookingInput').on('input', function() {
                    $('#searchError').removeClass('show');
                });

                $(document).on('input', '#bookingInput, #knittingForm input[type="text"]:not(.yarn_count_row), #knittingForm textarea', function() {
                    var start = this.selectionStart;
                    var end = this.selectionEnd;
                    this.value = this.value.toUpperCase();
                    if (start !== null && end !== null) {
                        this.setSelectionRange(start, end);
                    }
                });

                var manualFieldSelectors = [
                    '#finish_dia',
                    '#open_tube',
                    '#fabrics_type',
                    '#customer',
                    '#sl_vdq',
                    '#mc_dia',
                    '#feeder_plan',
                    '#knit_m_description'
                ].join(', ');

                 $(manualFieldSelectors).on('input change', function() {
                     checkSubmitButton();
                 });
                 $(document).on('input change', '.yarn_count_row, .yarn_brand_row, .color_row, .lot_no_row, .qty-input', function() {
                     checkSubmitButton();
                 });
                 $(document).on('input', '.yarn_count_row', function() {
                     var input = this;
                     yarnMaterialEnterPending = false;
                     clearTimeout(yarnMaterialSearchTimer);
                     yarnMaterialSearchTimer = setTimeout(function() {
                         yarnMaterialSearchTimer = null;
                         searchYarnMaterials(input, $(input).val());
                     }, 180);
                 });
                 $(document).on('click', '.yarn-material-toggle', function() {
                     var input = $(this).siblings('.yarn_count_row')[0];
                     searchYarnMaterials(input, '');
                     getYarnMaterialMenu().find('.yarn-material-search').val('').trigger('focus');
                 });
                 $(document).on('input', '.yarn-material-search', function() {
                     if (!activeYarnCountInput) return;
                     var input = activeYarnCountInput;
                     yarnMaterialEnterPending = false;
                     clearTimeout(yarnMaterialSearchTimer);
                     yarnMaterialSearchTimer = setTimeout(function() {
                         yarnMaterialSearchTimer = null;
                         searchYarnMaterials(input, $('.yarn-material-search').val());
                     }, 180);
                 });
                 $(document).on('keydown', '.yarn_count_row, .yarn-material-search', function(e) {
                     if (e.key === 'ArrowDown') {
                         e.preventDefault();
                         moveYarnMaterialSelection(1);
                     } else if (e.key === 'ArrowUp') {
                         e.preventDefault();
                         moveYarnMaterialSelection(-1);
                     } else if (e.key === 'Enter') {
                         e.preventDefault();
                         var menu = getYarnMaterialMenu();
                         if (yarnMaterialSearchTimer) {
                             clearTimeout(yarnMaterialSearchTimer);
                             yarnMaterialSearchTimer = null;
                             yarnMaterialEnterPending = true;
                             var input = activeYarnCountInput || $(this).closest('.yarn-count-control').find('.yarn_count_row')[0];
                             searchYarnMaterials(input, $(this).val());
                         } else if (menu.is(':visible') && menu.find('.yarn-material-option').length) {
                             chooseActiveYarnMaterial();
                         } else {
                             var input = activeYarnCountInput || $(this).closest('.yarn-count-control').find('.yarn_count_row')[0];
                             if (input) {
                                 yarnMaterialEnterPending = true;
                                 searchYarnMaterials(input, $(this).val());
                             }
                         }
                     } else if (e.key === 'Escape') {
                         closeYarnMaterialMenu();
                     }
                 });
                 $(document).on('mousedown', function(e) {
                     if (!$(e.target).closest('#yarnMaterialMenu, .yarn-count-control').length) {
                         closeYarnMaterialMenu();
                     }
                 });
                 $(window).on('resize scroll', closeYarnMaterialMenu);
                 $('.mcno-table-wrap').on('scroll', closeYarnMaterialMenu);
                 $('#knittingForm').on('input change', function() {
                     checkSubmitButton();
                 });


                $('#submitBtn').on('click', function(e) {
                    e.preventDefault();
                     var mcnoResult = getMcnoQtyData();
                     if (!mcnoResult.isValid) {
                         showAlert('Fill all row fields with valid data.', 'error');
                         return;
                     }
                     if (mcnoResult.data.length === 0) {
                         showAlert('Add at least one quantity row.', 'error');
                         return;
                     }
                     if (!isManualDataValid()) {
                         showAlert('Please fill all required fields.', 'error');
                         return;
                     }
                     var desc = $('#knit_m_description').val();
                    var totalQty = 0;
                    mcnoResult.data.forEach(function(item) {
                        totalQty += item.qty;
                    });
                    var targetVal = parseFloat($('#display_target_qty').text()) || 0;
                    if (totalQty > targetVal || !areRowsWithinColorTargets()) {
                        showAlert('QTY exceeds the remaining target for its selected color. Adjust the color-wise quantities.', 'error');
                        return;
                    }

                    var formData = {
                        booking: $('#booking').val(),
                        sono: $('#sono').val(),
                        style: $('#style').val(),
                        buyer: $('#buyer').val(),
                        customer: $('#customer').val(),
                        knit_m_description: desc,
                        yarn_type: $('#yarn_type').val(),
                        yarn_count: $('#yarn_count').val(),
                        fabrics_type: $('#fabrics_type').val(),
                        category: $('#category').val(),
                        finish_gsm: $('#finish_gsm').val(),
                        feeder_plan: $('#feeder_plan').val(),
                        sl_vdq: $('#sl_vdq').val(),
                        mc_dia: $('#mc_dia').val(),
                        finish_dia: $('#finish_dia').val(),
                        open_tube: $('#open_tube').val(),
                        lot_no: $('#lot_no').val(),
                        knit_material_code: $('#knit_material_code').val(),
                        knitting_target_qty: $('#knitting_target_qty').val(),
                        remarks: $('#remarks').val(),
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
                                sessionStorage.removeItem('kp_booking');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Saved Successfully',
                                    text: resp.message || 'Program saved successfully.',
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                    allowOutsideClick: false
                                }).then(function() {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Save Failed',
                                    text: resp.message || 'Save failed.',
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false
                                });
                            }
                        },
                        error: function(xhr) {
                            var errorMessage = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'Error saving program.';
                            Swal.fire({
                                icon: 'error',
                                title: 'Save Failed',
                                text: errorMessage,
                                timer: 3000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            });
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html('<i class="fa-regular fa-floppy-disk me-2"></i>Save Program');
                        }
                    });
                });

                // initial row
                addMcnoRow();

                $('#submitBtn').prop('disabled', true);

                var urlParams = new URLSearchParams(window.location.search);
                 var bookingParam = urlParams.get('booking') || urlParams.get('po_number') || urlParams.get('po');
                 if (bookingParam) {
                     $('#bookingInput').val(bookingParam);
                     loadFormData(bookingParam);
                 }
            });

        })(jQuery);
    </script>

</body>

</html>