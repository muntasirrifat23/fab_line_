<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | Knitting Inspection Report</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">

    <style>
        body {
            padding: 16px;
            background: #f1f5f9;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
        }

        .head-row {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            position: relative;
        }

        h1 {
            flex: 1;
            text-align: center;
            font-size: 1.9rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
        }

        .search-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
        }

        .search-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-panel input {
            flex: 1;
            min-width: 220px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1.05rem;
            outline: none;
        }

        .search-panel input:focus {
            border-color: #2563eb;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background .15s;
        }

        .btn-search {
            background: #2563eb;
        }

        .btn-search:hover {
            background: #1d4ed8;
        }

        .btn-clear {
            background: #64748b;
        }

        .btn-clear:hover {
            background: #475569;
        }

        #backBtn {
            border: 1px solid transparent;
        }

        #backBtn:hover {
            background: #ffffff !important;
            border: 1px solid #000000;
            color: #000000;
        }

        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-scroll {
            overflow-x: auto;
            max-height: calc(100vh - 180px);
            overflow-y: auto;
        }

        .table-scroll table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            min-width: 3400px;
        }

        .table-scroll thead th {
            background: #1e293b !important;
            color: #fff !important;
            padding: 14px 12px;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .table-scroll thead th:first-child {
            left: 0;
            z-index: 8;
        }

        .table-scroll tbody td {
            padding: 12px;
            border-bottom: 1px solid #eef2f7;
            white-space: nowrap;
            color: #1e293b;
        }

        .table-scroll tbody td:first-child {
            position: sticky;
            left: 0;
            background: #ffffff;
            z-index: 3;
        }

        .table-scroll tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .table-scroll tbody tr:nth-child(even) td:first-child {
            background: #f8fafc;
        }

        .table-scroll tbody tr:hover {
            background: #eff6ff;
        }

        .table-scroll tbody tr:hover td:first-child {
            background: #eff6ff;
        }

        .btn-print-row {
            background: #2563eb;
            border: none;
            color: #fff;
            border-radius: 6px;
            padding: 7px 13px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-print-row:hover {
            background: #1d4ed8;
        }

        .empty-row td,
        .loading-cell {
            text-align: center;
            padding: 28px;
            color: #94a3b8;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .search-row {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
        @page {
            size: A4 landscape;
            margin: 5mm;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 5mm;
            }
            body {
                background: #ffffff !important;
            }
            body * {
                visibility: hidden !important;
            }
            #reportModal, #reportModal *, #modalReportContent, #modalReportContent * {
                visibility: visible !important;
            }
            #reportModal {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: auto !important;
                background: transparent !important;
                padding: 0 !important;
            }
            #reportModal > div {
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- Report View & Print Modal -->
    <div id="reportModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:99999; overflow-y:auto; padding:20px 0;">
        <div style="background:#ffffff; width:1150px; margin:0 auto; padding:20px; border-radius:8px; position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
            <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #cbd5e1; padding-bottom:12px;">
                <h4 style="margin:0; font-weight:bold; color:#0f172a; font-size:18px;"><i class="fa-solid fa-file-invoice"></i> Inspection Report Preview</h4>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn" onclick="printModalReport()" style="background:#0284c7; color:#ffffff; padding:7px 16px; border:none; border-radius:5px; font-weight:600; cursor:pointer; font-size:13px;">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                    <button type="button" class="btn" onclick="downloadModalPDF()" style="background:#16a34a; color:#ffffff; padding:7px 16px; border:none; border-radius:5px; font-weight:600; cursor:pointer; font-size:13px;">
                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                    </button>
                    <button type="button" class="btn" onclick="closeReportModal()" style="background:#64748b; color:#ffffff; padding:7px 16px; border:none; border-radius:5px; font-weight:600; cursor:pointer; font-size:13px;">
                        <i class="fa-solid fa-xmark"></i> Close
                    </button>
                </div>
            </div>
            <div id="modalReportContent">
                <!-- Dynamically populated report card -->
            </div>
        </div>
    </div>

    <div class="container-fluid p-0">

        <div class="head-row">
            <button class="btn" id="backBtn" style="background:#1e293b; padding:8px 14px; ">
                <i class="fa-solid fa-arrow-left"></i> Back to Report
            </button>
            <h1 style="font-size:xx-large;">Knitting Inspection Report</h1>
        </div>

        <div class="search-panel">
            <div class="search-row">
                <input type="text" id="bookingInput" placeholder="Search Roll / PO Number / SONO / Buyer / Style">
                <button class="btn btn-search" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                <button class="btn btn-clear" id="clearBtn"><i class="fa-solid fa-rotate-left"></i> Clear</button>
            </div>
        </div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>KITID</th>
                            <th>BUDAT</th>
                            <th>ROLL</th>
                            <th>MAIN QTY</th>
                            <th>REJECT QTY</th>
                            <th>UPDATE QTY</th>
                            <th>PO_NUMBER</th>
                            <th>QTY</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MCNO</th>
                            <th>MC_DIA</th>
                            <th>CUSTOMER</th>
                            <th>SHIFT</th>
                            <th>YTYPE</th>
                            <th>YCOUNT</th>
                            <th>FTYPE</th>
                            <th>FGSM</th>
                            <th>FDIA</th>
                            <th>O_T</th>
                            <th>SL</th>
                            <th>GGSM</th>
                            <th>FPLAN</th>
                            <th>LOTNO</th>
                            <th>MATERIAL_CODE</th>
                            <th>M_DES</th>
                            <th>TT</th>
                            <th>PATTA</th>
                            <th>SLUB</th>
                            <th>YC_SPOT</th>
                            <th>OILSPOT</th>
                            <th>FF</th>
                            <th>SEEDS</th>
                            <th>MSTITCH</th>
                            <th>SINKERMARK</th>
                            <th>NEEDLEMARK</th>
                            <th>LYCOUT</th>
                            <th>OILLINE</th>
                            <th>HOLE</th>
                            <th>LOOP</th>
                            <th>SETUP</th>
                            <th>CMARK</th>
                            <th>TPOINT</th>
                            <th>QC_GRADE</th>
                            <th>QC_STATUS</th>
                            <th>UNAME</th>
                            <th>UID</th>
                            <th>P_CREATED</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="51" class="loading-cell">Loading data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        var COLS = [
            { key: 'KITID', label: 'KITID' },
            { key: 'BUDAT', label: 'BUDAT' },
            { key: 'ROLL', label: 'ROLL' },
            { key: 'OQTY',   label: 'MAIN QTY'   },
            { key: 'RQTY', label: 'REJECT QTY'  },
            { key: 'UQTY', label: 'UPDATE QTY'  },
            { key: 'PO_NUMBER', label: 'PO_NUMBER' },
            { key: 'QTY', label: 'QTY' },
            { key: 'SONO', label: 'SONO' },
            { key: 'BUYER', label: 'BUYER' },
            { key: 'STYLE', label: 'STYLE' },
            { key: 'COLOR', label: 'COLOR' },
            { key: 'MCNO', label: 'MCNO' },
            { key: 'MC_DIA', label: 'MC_DIA' },
            { key: 'CUSTOMER', label: 'CUSTOMER' },
            { key: 'SHIFT', label: 'SHIFT' },
            { key: 'YTYPE', label: 'YTYPE' },
            { key: 'YCOUNT', label: 'YCOUNT' },
            { key: 'FTYPE', label: 'FTYPE' },
            { key: 'FGSM', label: 'FGSM' },
            { key: 'FDIA', label: 'FDIA' },
            { key: 'O_T', label: 'O_T' },
            { key: 'SL', label: 'SL' },
            { key: 'GGSM', label: 'GGSM' },
            { key: 'FPLAN', label: 'FPLAN' },
            { key: 'LOTNO', label: 'LOTNO' },
            { key: 'MATERIAL_CODE', label: 'MATERIAL_CODE' },
            { key: 'M_DES', label: 'M_DES' },
            { key: 'TT', label: 'TT' },
            { key: 'PATTA', label: 'PATTA' },
            { key: 'SLUB', label: 'SLUB' },
            { key: 'YC_SPOT', label: 'YC_SPOT' },
            { key: 'OILSPOT', label: 'OILSPOT' },
            { key: 'FF', label: 'FF' },
            { key: 'SEEDS', label: 'SEEDS' },
            { key: 'MSTITCH', label: 'MSTITCH' },
            { key: 'SINKERMARK', label: 'SINKERMARK' },
            { key: 'NEEDLEMARK', label: 'NEEDLEMARK' },
            { key: 'LYCOUT', label: 'LYCOUT' },
            { key: 'OILLINE', label: 'OILLINE' },
            { key: 'HOLE', label: 'HOLE' },
            { key: 'LOOP', label: 'LOOP' },
            { key: 'SETUP', label: 'SETUP' },
            { key: 'CMARK', label: 'CMARK' },
            { key: 'TPOINT', label: 'TPOINT' },
            { key: 'QC_GRADE', label: 'QC_GRADE' },
            { key: 'QC_STATUS', label: 'QC_STATUS' },
            { key: 'UNAME', label: 'UNAME' },
            { key: 'UID', label: 'UID' },
            { key: 'P_CREATED', label: 'P_CREATED' }
        ];

        var currentData = [];

        function esc(v) {
            if (v === null || v === undefined) return '';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        var activeReportRow = null;

        function renderReportHTML(row) {
            var isPass = (String(row.QC_STATUS || '').toLowerCase() === 'passed');
            var isFail = (String(row.QC_STATUS || '').toLowerCase() === 'failed');
            var isReject = (String(row.QC_GRADE || '').toLowerCase() === 'reject' || String(row.QC_STATUS || '').toLowerCase() === 'rejected');

            return '' +
                '<div id="rowPdfCard" style="position:relative;width:1100px;margin:0 auto;padding:12px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#000000;font-size:9.5px;box-sizing:border-box;">' +
                
                '<!-- Topmost Header Section (Above Outer Border) -->' +
                '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;padding:0 2px;">' +
                '<div style="font-size:9px;line-height:1.3;font-weight:bold;">' +
                'PFL/QF/QAD: 03<br>' +
                'Effective Date : 01-06-2021<br>' +
                'Revision : 00' +
                '</div>' +
                '<div style="text-align:center;">' +
                '<div style="font-size:20px;font-weight:bold;letter-spacing:0.5px;">Purbani Fabrics Limited</div>' +
                '<div style="font-size:11px;font-weight:600;margin-top:1px;">Noorbag, Kaliakoir, Gazipur</div>' +
                '</div>' +
                '<div style="width:100px;"></div>' +
                '</div>' +

                '<!-- Main Outer Box -->' +
                '<div style="border:1.5px solid #000000;padding:0;background:#ffffff;">' +

                '<!-- Title Bar Row -->' +
                '<div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1.5px solid #000000;padding:3px 6px;background:#ffffff;">' +
                '<div style="font-size:10px;font-weight:bold;width:150px;">' +
                'Date : <span style="font-weight:normal;">' + esc(row.P_CREATED || row.BUDAT) + '</span>' +
                '</div>' +
                '<div style="font-size:15px;font-weight:bold;text-align:center;flex:1;">' +
                'Knit Fabric Inspection (4 point System)' +
                '</div>' +
                '<div style="font-size:16px;font-weight:bold;font-style:italic;letter-spacing:1px;color:#1e3a8a;width:120px;text-align:right;">' +
                'PURBANI' +
                '</div>' +
                '</div>' +

                '<!-- Header Specification Table (Metadata Rows 2-4) -->' +
                '<table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #000000;background:#ffffff;margin-bottom:0;" border="1" cellpadding="3">' +
                '<tr>' +
                '<td style="width:16%;border:1px solid #000000;"><b>Buyer :</b> ' + esc(row.BUYER) + '</td>' +
                '<td style="width:16%;border:1px solid #000000;"><b>Style :</b> ' + esc(row.STYLE) + '</td>' +
                '<td style="width:20%;border:1px solid #000000;"><b>Production Order :</b> ' + esc(row.PO_NUMBER || row.KPTID) + '</td>' +
                '<td style="width:20%;border:1px solid #000000;"><b>Sales Order :</b> ' + esc(row.SONO) + '</td>' +
                '<td style="width:14%;border:1px solid #000000;"><b>Shift :</b> ' + esc(row.SHIFT) + '</td>' +
                '<td style="width:14%;border:1px solid #000000;"><b>Quantity :</b> ' + esc(row.QTY) + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td style="border:1px solid #000000;"><b>F/Name :</b> ' + esc(row.FTYPE || row.FABRICS_TYPE) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Color :</b> ' + esc(row.COLOR) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Yarn Count :</b> ' + esc(row.YCOUNT || row.YTYPE) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Yarn Lot :</b> ' + esc(row.LOTNO) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Fabric Type :</b> ' + esc(row.FTYPE) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Customer :</b> ' + esc(row.CUSTOMER) + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td style="border:1px solid #000000;"><b>M/C No. :</b> ' + esc(row.MCNO) + '</td>' +
                '<td style="border:1px solid #000000;"><b>M/C DIA :</b> ' + esc(row.MC_DIA) + '</td>' +
                '<td style="border:1px solid #000000;"><b>Req. DIA :</b> ' + esc(row.FDIA) + ' &nbsp;<b>FGSM :</b> ' + esc(row.FGSM) + '</td>' +
                '<td style="border:1px solid #000000;"><b>R.SSL :</b> ' + esc(row.SL) + ' &nbsp;<b>R.SKL :</b> ' + esc(row.SL) + '</td>' +
                '<td colspan="2" style="border:1px solid #000000;"><b>Start Time :</b> ' + esc(row.P_CREATED) + ' &nbsp;<b>End Time :</b> </td>' +
                '</tr>' +
                '</table>' +

                '<!-- Main Fabric Fault Inspection Grid (25 Columns) -->' +
                '<table style="width:100%;border-collapse:collapse;font-size:7.5px;text-align:center;border:1px solid #000000;background:#ffffff;table-layout:fixed;" border="1" cellpadding="2">' +
                '<thead>' +
                '<tr style="background:#ffffff;color:#000000;font-weight:bold;font-size:7.5px;">' +
                '<th style="width:6%;border:1px solid #000000;padding:3px 1px;">Batch No.</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Wt in kg</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Roll Length</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">G. GSM</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">T&amp;T</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Patta</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Slub</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Y/C</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Oil Spot</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">F/F</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Seeds</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">O/L stitch</th>' +
                '<th style="width:4.5%;border:1px solid #000000;padding:3px 1px;">Sinker Mark</th>' +
                '<th style="width:4.5%;border:1px solid #000000;padding:3px 1px;">Needle Mark</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Lyc Out</th>' +
                '<th style="width:4%;border:1px solid #000000;padding:3px 1px;">Oil Line</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Hole</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Loop</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Setup</th>' +
                '<th style="width:4.5%;border:1px solid #000000;padding:3px 1px;">Crease Mark</th>' +
                '<th style="width:5%;border:1px solid #000000;padding:3px 1px;">Per 100 Sq Yds</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Pass</th>' +
                '<th style="width:3.5%;border:1px solid #000000;padding:3px 1px;">Fail</th>' +
                '<th style="width:5.5%;border:1px solid #000000;padding:3px 1px;">Q.I Name</th>' +
                '<th style="width:5.5%;border:1px solid #000000;padding:3px 1px;">Remarks</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr style="background:#ffffff;color:#000000;">' +
                '<td style="border:1px solid #000000;word-break:break-all;padding:3px 1px;">' + esc(row.PO_NUMBER || row.SONO || row.ROLL) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.MAIN_QTY || row.QTY) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.O_T) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.GGSM) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.TT) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.PATTA) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.SLUB) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.YC_SPOT) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.OILSPOT) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.FF) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.SEEDS) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.MSTITCH) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.SINKERMARK) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.NEEDLEMARK) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.LYCOUT) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.OILLINE) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.HOLE) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.LOOP) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.SETUP) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.CMARK) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;"></td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + (isPass ? '✓' : '') + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + (isFail ? '✓' : '') + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.UNAME || row.UID) + '</td>' +
                '<td style="border:1px solid #000000;padding:3px 1px;">' + esc(row.QC_GRADE) + '</td>' +
                '</tr>' +
                [1, 2, 3, 4, 5, 6, 7].map(function() {
                    return '<tr style="height:18px;background:#ffffff;">' +
                        '<td style="border:1px solid #000000;">&nbsp;</td>'.repeat(25) +
                        '</tr>';
                }).join('') +
                '</tbody>' +
                '<tfoot>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;">' +
                '<td colspan="2" style="border:1px solid #000000;text-align:left;padding-left:4px;">Total Qty : ' + esc(row.QTY) + '</td>' +
                '<td colspan="13" style="border:1px solid #000000;"></td>' +
                '<td colspan="10" style="border:1px solid #000000;text-align:left;padding-left:4px;">Total Points = ' + esc(row.TPOINT) + '</td>' +
                '</tr>' +
                '</tfoot>' +
                '</table>' +

                '<!-- Bottom Summary & Standards Container -->' +
                '<div style="display:flex;gap:3px;padding:3px;font-size:7.5px;align-items:stretch;background:#ffffff;border:1px solid #000000;border-top:none;">' +

                '<!-- Box 1: Defect Point Size in Inch -->' +
                '<div style="width:16%;border:1px solid #000000;padding:1px;background:#ffffff;">' +
                '<table style="width:100%;border-collapse:collapse;text-align:center;font-size:7px;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th colspan="2" style="border:1px solid #000000;padding:2px 1px;">Defect Point Size in Inch</th></tr>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="border:1px solid #000000;padding:2px 1px;">Fault Size</th><th style="border:1px solid #000000;padding:2px 1px;">Point</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr><td style="border:1px solid #000000;">&gt;0" to 3"</td><td style="border:1px solid #000000;">1</td></tr>' +
                '<tr><td style="border:1px solid #000000;">&gt;3" to 6"</td><td style="border:1px solid #000000;">2</td></tr>' +
                '<tr><td style="border:1px solid #000000;">&gt;6" to 9"</td><td style="border:1px solid #000000;">3</td></tr>' +
                '<tr><td style="border:1px solid #000000;">9"above to 36"</td><td style="border:1px solid #000000;">4</td></tr>' +
                '<tr><td style="border:1px solid #000000;">Any hole</td><td style="border:1px solid #000000;">4</td></tr>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th colspan="2" style="border:1px solid #000000;">H&amp;M</th></tr>' +
                '<tr><td style="border:1px solid #000000;">Hole &lt; 1"</td><td style="border:1px solid #000000;">2</td></tr>' +
                '<tr><td style="border:1px solid #000000;">Hole &gt; 1"</td><td style="border:1px solid #000000;">4</td></tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +

                '<!-- Box 2: Formula for 100 sq/yrds -->' +
                '<div style="width:17%;border:1px solid #000000;padding:4px;text-align:center;display:flex;flex-direction:column;justify-content:center;background:#ffffff;color:#000000;">' +
                '<div style="font-weight:bold;margin-bottom:6px;text-decoration:underline;font-size:8px;">Formula for 100 sq/yrds</div>' +
                '<div style="font-size:8px;font-weight:bold;line-height:1.4;">' +
                '<u>Fabrics Fault x 36 x 100</u><br>' +
                'Fabrics Length x Fabrics Width' +
                '</div>' +
                '</div>' +

                '<!-- Box 3: Acceptance Criteria (H&M & Others Buyer) -->' +
                '<div style="width:22%;border:1px solid #000000;padding:1px;background:#ffffff;">' +
                '<div style="display:flex;gap:1px;height:100%;">' +
                '<table style="width:50%;border-collapse:collapse;text-align:center;font-size:7px;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th colspan="2" style="border:1px solid #000000;">H&amp;M</th></tr>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="border:1px solid #000000;">Accept Point (Ins Roll)</th><th style="border:1px solid #000000;">A</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr><td style="border:1px solid #000000;">up to 20</td><td style="border:1px solid #000000;">A</td></tr>' +
                '<tr><td style="border:1px solid #000000;">20 up to 28</td><td style="border:1px solid #000000;">B</td></tr>' +
                '<tr><td style="font-weight:bold;border:1px solid #000000;">Above</td><td rowspan="2" style="font-weight:bold;vertical-align:middle;border:1px solid #000000;">Reject</td></tr>' +
                '<tr><td style="border:1px solid #000000;">&nbsp;</td></tr>' +
                '</tbody>' +
                '</table>' +
                '<table style="width:50%;border-collapse:collapse;text-align:center;font-size:7px;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th colspan="2" style="border:1px solid #000000;">Others Buyer</th></tr>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="border:1px solid #000000;">Accept Point (Ins Roll)</th><th style="border:1px solid #000000;">Grade</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr><td style="border:1px solid #000000;">up to 26</td><td style="border:1px solid #000000;">A</td></tr>' +
                '<tr><td style="border:1px solid #000000;">26 up to 38</td><td style="border:1px solid #000000;">B</td></tr>' +
                '<tr><td style="border:1px solid #000000;">38 up to 60</td><td style="border:1px solid #000000;">C</td></tr>' +
                '<tr><td style="border:1px solid #000000;">Above</td><td style="border:1px solid #000000;">Reject</td></tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +
                '</div>' +

                '<!-- Box 4: Avg Points per 100 sqr. Yards -->' +
                '<div style="width:15%;border:1px solid #000000;padding:1px;background:#ffffff;">' +
                '<table style="width:100%;border-collapse:collapse;text-align:center;font-size:7px;height:100%;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th colspan="3" style="border:1px solid #000000;">Avg Points per 100 sqr. Yards</th></tr>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="border:1px solid #000000;">Pass</th><th style="border:1px solid #000000;">Fail</th><th style="border:1px solid #000000;">Reject</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr>' +
                '<td style="border:1px solid #000000;font-weight:bold;font-size:10px;">' + (isPass ? '✓' : '') + '</td>' +
                '<td style="border:1px solid #000000;font-weight:bold;font-size:10px;">' + (isFail ? '✓' : '') + '</td>' +
                '<td style="border:1px solid #000000;font-weight:bold;font-size:10px;">' + (isReject ? '✓' : '') + '</td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +

                '<!-- Box 5: Special Comments -->' +
                '<div style="width:14%;border:1px solid #000000;padding:1px;font-size:7px;background:#ffffff;">' +
                '<table style="width:100%;border-collapse:collapse;background:#ffffff;" border="1" cellpadding="1">' +
                '<thead>' +
                '<tr style="font-weight:bold;background:#ffffff;color:#000000;"><th style="padding:1px;border:1px solid #000000;">Special Comments :</th></tr>' +
                '</thead>' +
                '<tbody>' +
                '<tr><td style="padding:1px;border:1px solid #000000;">Y/Contta</td></tr>' +
                '<tr><td style="padding:1px;border:1px solid #000000;">Dead/Fibre</td></tr>' +
                '<tr><td style="padding:1px;border:1px solid #000000;">Hairynae</td></tr>' +
                '<tr><td style="padding:1px;border:1px solid #000000;">Patta</td></tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +

                '<!-- Box 6: Comments -->' +
                '<div style="width:16%;border:1px solid #000000;padding:4px;font-size:7.5px;background:#ffffff;color:#000000;">' +
                '<div style="font-weight:bold;text-decoration:underline;margin-bottom:3px;">Comments</div>' +
                '<div style="font-weight:bold;">' + esc(row.QC_GRADE || '') + '</div>' +
                '</div>' +

                '</div>' +

                '</div> <!-- End Main Outer Box -->' +

                '<!-- Signatures Section -->' +
                '<div style="display:flex;justify-content:space-between;margin-top:25px;text-align:center;font-size:9px;font-weight:bold;">' +
                '<div style="width:22%;">' +
                '<div style="border-top:1.5px solid #000000;margin-bottom:3px;"></div>' +
                'Shift-In-Charge QA (PFL)' +
                '</div>' +
                '<div style="width:22%;">' +
                '<div style="border-top:1.5px solid #000000;margin-bottom:3px;"></div>' +
                'Officer QA (PFL)' +
                '</div>' +
                '<div style="width:22%;">' +
                '<div style="border-top:1.5px solid #000000;margin-bottom:3px;"></div>' +
                'Manager/GM (PFL)' +
                '</div>' +
                '<div style="width:22%;">' +
                '<div style="border-top:1.5px solid #000000;margin-bottom:3px;"></div>' +
                'Manager/DGM QA (PFL)' +
                '</div>' +
                '</div>' +

                '</div>';
        }

        function openReportModal(roll) {
            var row = null;
            for (var i = 0; i < currentData.length; i++) {
                if (String(currentData[i].ROLL) === String(roll)) { row = currentData[i]; break; }
            }
            if (!row) { alert('Data not found for this roll.'); return; }
            activeReportRow = row;
            $('#modalReportContent').html(renderReportHTML(row));
            $('#reportModal').css('display', 'block');
        }

        function closeReportModal() {
            $('#reportModal').css('display', 'none');
        }

        function printModalReport() {
            window.print();
        }

        function downloadModalPDF() {
            if (!activeReportRow) return;
            downloadPDFByRow(activeReportRow);
        }

        function downloadPDF(roll) {
            openReportModal(roll);
        }

        function downloadPDFByRow(row) {
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = renderReportHTML(row);
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '0';
            document.body.appendChild(tempDiv);

            showToast('Generating PDF...');
            html2canvas(tempDiv, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false
            }).then(function(canvas) {
                var imgData = canvas.toDataURL('image/png');
                var jsPDFLib = window.jspdf;
                var pdf = new jsPDFLib.jsPDF('l', 'mm', 'a4');
                var pdfWidth = pdf.internal.pageSize.getWidth();
                var pageHeight = pdf.internal.pageSize.getHeight();
                var pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                
                if (pdfHeight > pageHeight) {
                    var scaledWidth = (canvas.width * pageHeight) / canvas.height;
                    pdf.addImage(imgData, 'PNG', (pdfWidth - scaledWidth) / 2, 0, scaledWidth, pageHeight);
                } else {
                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                }

                pdf.save('Knitting_Inspection_' + (row.ROLL || 'Roll') + '.pdf');
                document.body.removeChild(tempDiv);
                showToast('PDF downloaded successfully');
            }).catch(function(err) {
                console.error('PDF generation error:', err);
                alert('Error generating PDF. Please try again.');
                if (document.body.contains(tempDiv)) {
                    document.body.removeChild(tempDiv);
                }
            });
        }

        function showToast(msg) {
            var t = document.getElementById('toastMsg');
            if (!t) {
                t = document.createElement('div');
                t.id = 'toastMsg';
                t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;box-shadow:0 10px 24px rgba(0,0,0,.25);z-index:99999;opacity:0;transition:opacity .25s;';
                document.body.appendChild(t);
            }
            t.textContent = msg;
            t.style.opacity = '1';
            clearTimeout(t._timer);
            t._timer = setTimeout(function() { t.style.opacity = '0'; }, 2200);
        }

        function renderTableRows(data) {
            var tbody = $('#tableBody');
            tbody.empty();
            currentData = data || [];
            if (!currentData.length) {
                tbody.append('<tr class="empty-row"><td colspan="51">No data found</td></tr>');
                return;
            }
            currentData.forEach(function(row) {
                var tr = $('<tr>');
                var rollEsc = esc(row.ROLL).replace(/'/g, '&#39;');
                tr.append($('<td>').html(
                    '<button class="btn-print-row" onclick="openReportModal(\'' + rollEsc + '\')"><i class="fa-solid fa-file-invoice"></i> View / Print Report</button>'
                ));
                COLS.forEach(function(c) {
                    tr.append($('<td>').text(row[c.key] === null || row[c.key] === undefined ? '' : row[c.key]));
                });
                tbody.append(tr);
            });
        }

        var isSearching = false;

        function searchBooking() {
            var search = $('#bookingInput').val().trim();
            isSearching = (search !== '');
            $('#searchBtn').prop('disabled', true).html('Searching...');
            $.ajax({
                    url: 'ajaxKnittingInspection_Report.php',
                    data: { search: search },
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="51">No data found</td></tr>');
                    }
                })
                .fail(function() {
                    $('#tableBody').html('<tr class="empty-row"><td colspan="51" style="color:#dc2626;">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Search');
                });
        }

        function loadAll(silent) {
            if (!silent) {
                $('#tableBody').html('<tr><td colspan="51" class="loading-cell">Loading data...</td></tr>');
            }
            var search = $('#bookingInput').val().trim();
            $.ajax({
                    url: 'ajaxKnittingInspection_Report.php',
                    data: search !== '' ? { search: search } : {},
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else if (!silent) {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="51">No data returned</td></tr>');
                    }
                })
                .fail(function() {
                    if (!silent) {
                        $('#tableBody').html('<tr class="empty-row"><td colspan="51" style="color:#dc2626;">Error loading data</td></tr>');
                    }
                });
        }

        $(function() {
            $('#backBtn').on('click', function() {
                window.location.href = 'initialPage.php';
            });
            $('#searchBtn').on('click', searchBooking);
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                isSearching = false;
                loadAll(false);
            });
            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) searchBooking();
            });

            loadAll(false);

            // Real-time polling every 5 seconds
            setInterval(function() {
                loadAll(true);
            }, 5000);
        });
    </script>

</body>

</html>
