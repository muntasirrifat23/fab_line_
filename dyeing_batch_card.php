<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dyeing Batch Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
            background: linear-gradient(135deg, #f0fdf9, #e0f2fe, #f5f3ff);
            min-height: 100vh;
            padding: 16px;
        }

        .wrap {
            max-width: 1200px;
            margin: 0 auto;
        }

        .top-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #134e4a, #0f766e, #0d9488);
            color: #fff;
            padding: 18px 22px;
            border-radius: 18px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .top-bar h1 {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-no-badge {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.35);
            padding: 6px 14px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 118, 110, 0.1);
            padding: 20px;
            margin-bottom: 18px;
        }

        .panel-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #134e4a;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .search-input {
            flex: 1;
            min-width: 220px;
            padding: 11px 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
            outline: none;
        }

        .search-input:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }

        .btn {
            border: none;
            cursor: pointer;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-green {
            background: linear-gradient(135deg, #059669, #10b981);
        }

        .btn-blue {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
        }

        .btn-amber {
            background: linear-gradient(135deg, #d97706, #f59e0b);
        }

        .btn-slate {
            background: linear-gradient(135deg, #475569, #64748b);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .status-line {
            margin: 10px 0;
            font-weight: 600;
            color: #0f766e;
        }

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        thead th {
            background: #134e4a;
            color: #fff;
            padding: 10px 8px;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
        }

        tbody td {
            padding: 9px 8px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #ecfdf5;
        }

        .roll-badge {
            background: #ccfbf1;
            color: #065f46;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 100px;
            display: inline-block;
        }

        .add-btn {
            background: #d1fae5;
            color: #047857;
            border: none;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.2s;
        }

        .add-btn:hover {
            background: #a7f3d0;
        }

        .add-btn:disabled {
            background: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
        }

        .delete-btn {
            background: #fee2e2;
            color: #b91c1c;
            border: none;
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .delete-btn:hover {
            background: #fecaca;
            transform: scale(1.08);
        }

        .empty-row td {
            text-align: center;
            color: #94a3b8;
            padding: 28px;
            font-weight: 600;
        }

        .footer-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .msg-box {
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
            margin-bottom: 12px;
            display: none;
        }

        .msg-success {
            background: #dcfce7;
            color: #166534;
            display: block;
        }

        .msg-error {
            background: #fee2e2;
            color: #991b1b;
            display: block;
        }

        .added-tag {
            background: #e2e8f0;
            color: #64748b;
            padding: 3px 10px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.75rem;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="top-bar">
            <h1><i class="fa-solid fa-vial-circle-check"></i> Dyeing Batch Card</h1>
            <span class="card-no-badge"><i class="fa-solid fa-hashtag"></i> Card: <span id="cardNo">-</span></span>
        </div>

        <div id="msgBox" class="msg-box"></div>

        <div class="panel">
            <div class="panel-title"><i class="fa-solid fa-magnifying-glass"></i> Knitting Store Rolls</div>
            <div class="search-row">
                <input type="text" id="searchInput" class="search-input"
                    placeholder="Search by Roll / PO Number / SONO / Buyer / Style / Color / Rack ...">
                <button class="btn btn-blue" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                <button class="btn btn-slate" id="showAllBtn"><i class="fa-solid fa-list"></i> Show All</button>
            </div>
            <div class="status-line" id="storeStatus"><i class="fa-solid fa-circle-info"></i> Loading rolls...</div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ROLL</th>
                            <th>PO_NUMBER</th>
                            <th>RACK</th>
                            <th>QTY</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MCNO</th>
                            <th>MCDIA</th>
                            <th>SUPPLIER</th>
                            <th>YTYPE</th>
                            <th>YCOUNT</th>
                            <th>O_T</th>
                            <th>SL</th>
                            <th>FTYPE</th>
                            <th>FGSM</th>
                            <th>FDIA</th>
                            <th>GGSM</th>
                            <th>FEEDER_PLAN</th>
                            <th>LOT_NO</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="storeBody">
                        <tr class="empty-row"><td colspan="23">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-title"><i class="fa-solid fa-table-list"></i> Batch Rolls
                <span class="card-no-badge" style="margin-left:8px; background:#ccfbf1; color:#065f46; border-color:#99f6e4;" id="rollCount">0 roll</span>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ROLL</th>
                            <th>PO_NUMBER</th>
                            <th>RACK</th>
                            <th>QTY</th>
                            <th>SONO</th>
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                            <th>MCNO</th>
                            <th>MCDIA</th>
                            <th>SUPPLIER</th>
                            <th>YTYPE</th>
                            <th>YCOUNT</th>
                            <th>O_T</th>
                            <th>SL</th>
                            <th>FTYPE</th>
                            <th>FGSM</th>
                            <th>FDIA</th>
                            <th>GGSM</th>
                            <th>FEEDER_PLAN</th>
                            <th>LOT_NO</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="rollsBody">
                        <tr class="empty-row"><td colspan="23">No roll added yet. Select rolls from the list above.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="footer-actions">
                <button class="btn btn-amber" id="createCardBtn"><i class="fa-solid fa-clipboard-check"></i> Create Batch Card</button>
                <button class="btn btn-slate" id="newCardBtn"><i class="fa-solid fa-plus"></i> New Batch Card</button>
            </div>
        </div>
    </div>

    <script>
        function showMsg(msg, type) {
            var box = document.getElementById('msgBox');
            box.className = 'msg-box ' + (type === 'error' ? 'msg-error' : 'msg-success');
            box.innerHTML = msg;
        }

        function hideMsg() {
            var box = document.getElementById('msgBox');
            box.className = 'msg-box';
            box.innerHTML = '';
        }

        function esc(v) {
            if (v === null || v === undefined) return '-';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function escAttr(v) {
            return esc(v).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function apiGet(action, params) {
            return fetch('ajaxDyeing_batch_card.php?action=' + action + '&' + params).then(function(r) { return r.json(); });
        }

        function apiPost(action, payload) {
            return fetch('ajaxDyeing_batch_card.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload || {})
            }).then(function(r) { return r.json(); });
        }

        function loadStore(searchTerm) {
            var params = searchTerm ? 'search=' + encodeURIComponent(searchTerm) : '';
            document.getElementById('storeStatus').innerHTML = '<i class="fa-solid fa-circle-info"></i> Loading rolls...';
            apiGet('search_rolls', params).then(function(resp) {
                if (resp && resp.success) {
                    renderStore(resp.rolls || []);
                    document.getElementById('storeStatus').innerHTML =
                        '<i class="fa-solid fa-circle-info"></i> ' + (resp.rolls ? resp.rolls.length : 0) + ' roll(s) found.';
                } else {
                    document.getElementById('storeStatus').innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + (resp.message || 'Load failed');
                }
            });
        }

        function renderStore(rolls) {
            var tbody = document.getElementById('storeBody');

            if (!rolls || rolls.length === 0) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="23">No rolls found in knitting store.</td></tr>';
                return;
            }

            var html = '';
            rolls.forEach(function(row, i) {
                var actionHtml;
                if (row.in_batch) {
                    actionHtml = '<span class="added-tag"><i class="fa-solid fa-check"></i> Added</span>';
                } else {
                    actionHtml = '<button class="add-btn" onclick="addRoll(\'' + escAttr(row.ROLL) + '\')"><i class="fa-solid fa-plus"></i> Add</button>';
                }
                html += '<tr>' +
                    '<td><strong>' + (i + 1) + '</strong></td>' +
                    '<td><span class="roll-badge">' + esc(row.ROLL) + '</span></td>' +
                    '<td>' + esc(row.PO_NUMBER) + '</td>' +
                    '<td>' + esc(row.RACK) + '</td>' +
                    '<td>' + esc(row.QTY) + '</td>' +
                    '<td>' + esc(row.SONO) + '</td>' +
                    '<td>' + esc(row.BUYER) + '</td>' +
                    '<td>' + esc(row.STYLE) + '</td>' +
                    '<td>' + esc(row.COLOR) + '</td>' +
                    '<td>' + esc(row.MCNO) + '</td>' +
                    '<td>' + esc(row.MCDIA) + '</td>' +
                    '<td>' + esc(row.SUPPLIER) + '</td>' +
                    '<td>' + esc(row.YTYPE) + '</td>' +
                    '<td>' + esc(row.YCOUNT) + '</td>' +
                    '<td>' + esc(row.O_T) + '</td>' +
                    '<td>' + esc(row.SL) + '</td>' +
                    '<td>' + esc(row.FTYPE) + '</td>' +
                    '<td>' + esc(row.FGSM) + '</td>' +
                    '<td>' + esc(row.FDIA) + '</td>' +
                    '<td>' + esc(row.GGSM) + '</td>' +
                    '<td>' + esc(row.FEEDER_PLAN) + '</td>' +
                    '<td>' + esc(row.LOT_NO) + '</td>' +
                    '<td>' + actionHtml + '</td>' +
                    '</tr>';
            });
            tbody.innerHTML = html;
        }

        function renderRolls(rolls) {
            var tbody = document.getElementById('rollsBody');
            var count = document.getElementById('rollCount');

            if (!rolls || rolls.length === 0) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="23">No roll added yet. Select rolls from the list above.</td></tr>';
                count.textContent = '0 roll';
                return;
            }

            count.textContent = rolls.length + ' roll' + (rolls.length > 1 ? 's' : '');

            var html = '';
            rolls.forEach(function(row, i) {
                html += '<tr>' +
                    '<td><strong>' + (i + 1) + '</strong></td>' +
                    '<td><span class="roll-badge">' + esc(row.ROLL) + '</span></td>' +
                    '<td>' + esc(row.PO_NUMBER) + '</td>' +
                    '<td>' + esc(row.RACK) + '</td>' +
                    '<td>' + esc(row.QTY) + '</td>' +
                    '<td>' + esc(row.SONO) + '</td>' +
                    '<td>' + esc(row.BUYER) + '</td>' +
                    '<td>' + esc(row.STYLE) + '</td>' +
                    '<td>' + esc(row.COLOR) + '</td>' +
                    '<td>' + esc(row.MCNO) + '</td>' +
                    '<td>' + esc(row.MCDIA) + '</td>' +
                    '<td>' + esc(row.SUPPLIER) + '</td>' +
                    '<td>' + esc(row.YTYPE) + '</td>' +
                    '<td>' + esc(row.YCOUNT) + '</td>' +
                    '<td>' + esc(row.O_T) + '</td>' +
                    '<td>' + esc(row.SL) + '</td>' +
                    '<td>' + esc(row.FTYPE) + '</td>' +
                    '<td>' + esc(row.FGSM) + '</td>' +
                    '<td>' + esc(row.FDIA) + '</td>' +
                    '<td>' + esc(row.GGSM) + '</td>' +
                    '<td>' + esc(row.FEEDER_PLAN) + '</td>' +
                    '<td>' + esc(row.LOT_NO) + '</td>' +
                    '<td><button class="delete-btn" title="Remove from batch" onclick="deleteRoll(\'' + escAttr(row.ROLL) + '\')"><i class="fa-solid fa-trash"></i></button></td>' +
                    '</tr>';
            });
            tbody.innerHTML = html;
        }

        function refreshAll() {
            apiGet('get_batch', '').then(function(resp) {
                if (resp && resp.success) {
                    document.getElementById('cardNo').textContent = resp.card_no || '-';
                    renderRolls(resp.rolls || []);
                }
            });
            loadStore(document.getElementById('searchInput').value.trim());
        }

        function addRoll(roll) {
            hideMsg();
            apiPost('add_roll', { ROLL: roll }).then(function(resp) {
                if (resp && resp.success) {
                    showMsg('Roll ' + roll + ' added to batch card.', 'success');
                    refreshAll();
                } else {
                    showMsg(resp.message || 'Could not add roll', 'error');
                }
            });
        }

        function deleteRoll(roll) {
            apiPost('delete_roll', { ROLL: roll }).then(function(resp) {
                if (resp && resp.success) {
                    showMsg('Roll ' + roll + ' removed from this batch card.', 'success');
                    refreshAll();
                } else {
                    showMsg(resp.message || 'Could not delete roll', 'error');
                }
            });
        }

        document.getElementById('searchBtn').addEventListener('click', function() {
            hideMsg();
            loadStore(document.getElementById('searchInput').value.trim());
        });

        document.getElementById('showAllBtn').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            hideMsg();
            loadStore('');
        });

        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                hideMsg();
                loadStore(this.value.trim());
            }
        });

        document.getElementById('createCardBtn').addEventListener('click', function() {
            apiPost('create_card', {}).then(function(resp) {
                if (resp && resp.success) {
                    document.getElementById('cardNo').textContent = resp.card_no;
                    showMsg('Batch Card <strong>' + resp.card_no + '</strong> created with ' + resp.count + ' roll(s)!', 'success');
                    refreshAll();
                } else {
                    showMsg(resp.message || 'Could not create batch card', 'error');
                }
            });
        });

        document.getElementById('newCardBtn').addEventListener('click', function() {
            if (!confirm('Start a new batch card? This clears the current roll list.')) return;
            apiPost('new_card', {}).then(function(resp) {
                if (resp && resp.success) {
                    document.getElementById('cardNo').textContent = '-';
                    showMsg('New batch card started. Select rolls to add.', 'success');
                    refreshAll();
                }
            });
        });

        loadStore('');
        apiGet('get_batch', '').then(function(resp) {
            if (resp && resp.success) {
                document.getElementById('cardNo').textContent = resp.card_no || '-';
                renderRolls(resp.rolls || []);
            }
        });
    </script>
</body>

</html>
