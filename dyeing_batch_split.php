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
    <title>Dyeing | Batch Split</title>
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
            width: 90%;
            max-width: 1400px;
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
            position: relative;
            justify-content: space-between;
        }

        .top-bar h1 {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 18px;
            background: black;
            color: white;
            border: 2px solid black;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .back-btn:hover {
            background: white;
            color: black;
            text-decoration: none;
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

        .search-row,
        .split-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
            align-items: center;
        }

        .search-input,
        .qty-input {
            flex: 1;
            min-width: 220px;
            padding: 11px 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
            outline: none;
        }

        .search-input:focus,
        .qty-input:focus {
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

        .btn-amber {
            background: linear-gradient(135deg, #d97706, #f59e0b);
        }

        .btn-blue {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
        }

        .btn-slate {
            background: linear-gradient(135deg, #475569, #64748b);
        }

        .btn-green {
            background: linear-gradient(135deg, #059669, #10b981);
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
            font-size: 0.82rem;
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
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .roll-badge {
            background: #ccfbf1;
            color: #065f46;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 100px;
            display: inline-block;
        }

        .empty-row td {
            text-align: center;
            color: #94a3b8;
            padding: 28px;
            font-weight: 600;
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

        .suggestion {
            background: #f8fafc;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
            flex-wrap: wrap;
            transition: 0.2s;
        }

        .suggestion:hover {
            border-color: #0d9488;
            background: #ecfdf5;
        }

        .suggestion.selected {
            border-color: #059669;
            background: #d1fae5;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
        }

        .suggestion .combo {
            font-weight: 700;
            color: #134e4a;
            font-size: 0.95rem;
        }

        .badge {
            background: #0f766e;
            color: #fff;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 0.75rem;
        }

        .badge-near {
            background: #d97706;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 6px;
        }

        .preview-box {
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 14px;
            background: #f8fafc;
        }

        .preview-box h4 {
            color: #134e4a;
            margin-bottom: 8px;
            font-size: 0.98rem;
        }

        .preview-box .roll-line {
            font-weight: 600;
            color: #0f766e;
            font-size: 0.88rem;
            padding: 2px 0;
        }

        .preview-total {
            font-weight: 800;
            color: #059669;
            margin-top: 8px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
        }

        @media (max-width: 900px) {
            .preview-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="top-bar">
            <a href="initialPage.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Report</a>
            <h1><i class="fa-solid fa-scissors"></i> Dyeing Batch Split</h1>
            <span class="badge" id="cardBadge" style="background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.35);">Select a card</span>
        </div>

        <div id="msgBox" class="msg-box"></div>

        <div class="panel">
            <div class="panel-title"><i class="fa-solid fa-clipboard-list"></i> Select Batch Card</div>
            <div class="search-row">
                <div style="position:relative; flex:1; min-width:460px;">
                    <input type="text" id="cardSearch" class="search-input"
                        placeholder="Enter Batch Card" style="width:100%;">
                    <div id="suggestBox"
                        style="position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #cbd5e1; border-radius:10px; margin-top:4px; max-height:240px; overflow-y:auto; display:none; z-index:50;"></div>
                </div>
                <button class="btn btn-blue" id="loadCardBtn"><i class="fa-solid fa-magnifying-glass"></i> Load Card</button>
                <button class="btn btn-slate" id="refreshCardsBtn"><i class="fa-solid fa-rotate"></i> Refresh</button>
            </div>
            <div class="status-line" id="cardStatus"></div>
        </div>

        <div class="panel" id="cardPanel" style="display:none;">
            <div class="panel-title"><i class="fa-solid fa-table-list"></i> Card Rolls
                <span style="margin-left:auto; font-size:0.85rem;">
                    Total QTY: <strong id="cardTotal" style="color:#059669;">0</strong>
                </span>
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
                            <th>BUYER</th>
                            <th>STYLE</th>
                            <th>COLOR</th>
                        </tr>
                    </thead>
                    <tbody id="cardRollsBody"></tbody>
                </table>
            </div>
        </div>

        <div class="panel" id="splitPanel" style="display:none;">
            <div class="panel-title"><i class="fa-solid fa-scissors"></i> Split Qty &amp; Suggestions</div>
            <div class="split-row">
                <input type="number" id="splitQty" class="qty-input" min="0" step="0.01"
                    placeholder="Enter Split Qty" style="max-width:280px;">
                <button class="btn btn-green" id="suggestBtn"><i class="fa-solid fa-wand-magic-sparkles"></i> Suggest</button>
            </div>
            <div class="status-line" id="suggestStatus"></div>
            <div id="suggestionsBox"></div>
        </div>

        <div class="panel" id="previewPanel" style="display:none;">
            <div class="panel-title"><i class="fa-solid fa-eye"></i> Split Preview</div>
            <div class="preview-grid">
                <div class="preview-box" id="partABox">
                    <h4>Part A</h4>
                    <div id="partARolls"></div>
                    <div class="preview-total" id="partATotal"></div>
                </div>
                <div class="preview-box" id="partBBox">
                    <h4>Part B (merged into 1 roll)</h4>
                    <div id="partBRolls"></div>
                    <div class="preview-total" id="partBTotal"></div>
                </div>
            </div>
            <div class="search-row" style="justify-content:flex-end; margin-top:16px;">
                <button class="btn btn-amber" id="splitBtn"><i class="fa-solid fa-scissors"></i> Split Now</button>
            </div>
        </div>
    </div>

    <script>
        var currentCard = null;
        var currentRolls = [];
        var selectedCombo = null;
        var allCards = [];

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

        function apiGet(action, params) {
            return fetch('ajaxDyeing_batch_split.php?action=' + action + '&' + params).then(function(r) { return r.json(); });
        }

        function apiPost(action, payload) {
            return fetch('ajaxDyeing_batch_split.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload || {})
            }).then(function(r) { return r.json(); });
        }

        function loadCards() {
            apiGet('list_cards', '').then(function(resp) {
                allCards = (resp && resp.success) ? (resp.cards || []) : [];
            });
        }

        function showSuggestions() {
            var val = document.getElementById('cardSearch').value.trim().toLowerCase();
            var box = document.getElementById('suggestBox');
            if (!val) {
                box.style.display = 'none';
                return;
            }
            var matched = allCards.filter(function(c) {
                return String(c.BCMTID).toLowerCase().indexOf(val) !== -1;
            }).slice(0, 8);

            if (!matched.length) {
                box.style.display = 'none';
                return;
            }
            var html = '';
            matched.forEach(function(c) {
                html += '<div class="sugg-item" data-card="' + esc(c.BCMTID) + '" style="padding:10px 14px; cursor:pointer; border-bottom:1px solid #eef2f7; font-weight:600;">' +
                    '<i class="fa-solid fa-clipboard"></i> ' + esc(c.BCMTID) +
                    ' <span style="color:#94a3b8; font-weight:500;">(' + c.roll_count + ' roll' + (c.roll_count > 1 ? 's' : '') + ', qty ' + c.total_qty + ')</span>' +
                    '</div>';
            });
            box.innerHTML = html;
            box.style.display = 'block';
            box.querySelectorAll('.sugg-item').forEach(function(el) {
                el.addEventListener('click', function() {
                    document.getElementById('cardSearch').value = this.getAttribute('data-card');
                    box.style.display = 'none';
                    loadCard();
                });
            });
        }

        function hideSuggestions() {
            document.getElementById('suggestBox').style.display = 'none';
        }

        function loadCard() {
            var card = document.getElementById('cardSearch').value.trim();
            if (!card) {
                showMsg('Enter a batch card number first.', 'error');
                return;
            }
            hideMsg();
            hideSuggestions();
            currentCard = card;
            document.getElementById('cardBadge').textContent = card;
            document.getElementById('cardStatus').innerHTML = '<i class="fa-solid fa-circle-info"></i> Loading card ' + card + '...';

            apiGet('get_card', 'card=' + encodeURIComponent(card)).then(function(resp) {
                if (resp && resp.success) {
                    currentRolls = resp.rolls || [];
                    renderCardRolls(currentRolls, resp.total);
                    document.getElementById('cardPanel').style.display = 'block';
                    document.getElementById('splitPanel').style.display = 'block';
                    document.getElementById('previewPanel').style.display = 'none';
                    document.getElementById('suggestionsBox').innerHTML = '';
                    document.getElementById('splitQty').value = '';
                    document.getElementById('cardStatus').innerHTML =
                        '<i class="fa-solid fa-circle-check"></i> ' + currentRolls.length + ' roll(s) loaded. Total: ' + resp.total;
                } else {
                    showMsg(resp.message || 'Could not load card', 'error');
                }
            });
        }

        function renderCardRolls(rolls, total) {
            var tbody = document.getElementById('cardRollsBody');
            if (!rolls.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="8">No rolls found.</td></tr>';
                return;
            }
            var html = '';
            rolls.forEach(function(row, i) {
                html += '<tr>' +
                    '<td><strong>' + (i + 1) + '</strong></td>' +
                    '<td><span class="roll-badge">' + esc(row.ROLL) + '</span></td>' +
                    '<td>' + esc(row.PO_NUMBER) + '</td>' +
                    '<td>' + esc(row.RACK) + '</td>' +
                    '<td><strong>' + esc(row.QTY) + '</strong></td>' +
                    '<td>' + esc(row.BUYER) + '</td>' +
                    '<td>' + esc(row.STYLE) + '</td>' +
                    '<td>' + esc(row.COLOR) + '</td>' +
                    '</tr>';
            });
            html += '<tr style="background:#ccfbf1; font-weight:800; border-top:2px solid #0f766e;">' +
                '<td colspan="4" style="text-align:right; color:#065f46;">Total QTY</td>' +
                '<td style="color:#065f46;">' + esc(total) + '</td>' +
                '<td colspan="3"></td>' +
                '</tr>';
            tbody.innerHTML = html;
            document.getElementById('cardTotal').textContent = total;
        }

        function qtyOf(roll) {
            var q = parseFloat(roll.QTY);
            return isNaN(q) ? 0 : q;
        }

        function findCombinations(rolls, target, tol) {
            var n = rolls.length;
            var exact = [];
            var nearest = [];
            var minDiff = Infinity;
            for (var mask = 1; mask < (1 << n); mask++) {
                var sum = 0;
                var ids = [];
                for (var i = 0; i < n; i++) {
                    if (mask & (1 << i)) {
                        sum += qtyOf(rolls[i]);
                        ids.push(i);
                    }
                }
                sum = Math.round(sum * 100) / 100;
                if (Math.abs(sum - target) <= tol) {
                    exact.push({ ids: ids, sum: sum, cnt: ids.length });
                } else {
                    var d = Math.abs(sum - target);
                    if (d < minDiff) minDiff = d;
                }
            }
            if (exact.length === 0) {
                var sorted = [];
                for (var mask2 = 1; mask2 < (1 << n); mask2++) {
                    var sum2 = 0;
                    var ids2 = [];
                    for (var j = 0; j < n; j++) {
                        if (mask2 & (1 << j)) {
                            sum2 += qtyOf(rolls[j]);
                            ids2.push(j);
                        }
                    }
                    sum2 = Math.round(sum2 * 100) / 100;
                    if (Math.abs(sum2 - target) === minDiff) {
                        sorted.push({ ids: ids2, sum: sum2, cnt: ids2.length });
                    }
                }
                sorted.sort(function(a, b) { return a.cnt - b.cnt; });
                nearest = sorted.slice(0, 5);
            }
            exact.sort(function(a, b) { return a.cnt - b.cnt; });
            return { exact: exact, nearest: nearest };
        }

        function renderSuggestions() {
            var qty = parseFloat(document.getElementById('splitQty').value);
            var box = document.getElementById('suggestionsBox');

            if (!currentRolls.length) {
                box.innerHTML = '<div class="status-line">No rolls to suggest.</div>';
                return;
            }
            if (isNaN(qty) || qty <= 0) {
                showMsg('Enter a valid split qty.', 'error');
                return;
            }
            document.getElementById('splitQty').value = qty;

            var total = 0;
            currentRolls.forEach(function(r) { total += qtyOf(r); });
            total = Math.round(total * 100) / 100;

            if (qty > total) {
                showMsg('Over Qty: ' + qty + ' exceeds card total (' + total + '). Enter a qty less than ' + total + '.', 'error');
                box.innerHTML = '';
                document.getElementById('suggestStatus').innerHTML = '';
                document.getElementById('previewPanel').style.display = 'none';
                selectedCombo = null;
                return;
            }

            if (qty === total) {
                showMsg('Matched: ' + qty + ' equals the full card total (' + total + '). Keep at least one roll for Part B, so enter a smaller qty.', 'error');
                box.innerHTML = '';
                document.getElementById('suggestStatus').innerHTML = '';
                document.getElementById('previewPanel').style.display = 'none';
                selectedCombo = null;
                return;
            }

            var tol = 0.01;
            var res = findCombinations(currentRolls, qty, tol);

            if (res.exact.length === 0 && res.nearest.length === 0) {
                document.getElementById('suggestStatus').innerHTML =
                    '<i class="fa-solid fa-circle-exclamation"></i> No combination of rolls matches qty ' + qty + '.';
                box.innerHTML = '';
                document.getElementById('previewPanel').style.display = 'none';
                return;
            }

            document.getElementById('suggestStatus').innerHTML =
                res.exact.length > 0
                    ? '<i class="fa-solid fa-circle-check"></i> ' + res.exact.length + ' exact combination(s) found for qty ' + qty + '. Click one to select.'
                    : '<i class="fa-solid fa-circle-info"></i> No exact match for ' + qty + '. Showing closest combination(s).';

            var html = '';
            res.exact.forEach(function(c, idx) {
                var parts = c.ids.map(function(ri) {
                    return '<b>' + esc(currentRolls[ri].ROLL) + '</b> (' + esc(currentRolls[ri].QTY) + ')';
                });
                html += '<div class="suggestion" data-idx="' + idx + '" data-exact="1" onclick="selectSuggestion(this)">' +
                    '<span class="combo">' + parts.join(' + ') + ' = <span style="color:#059669;">' + c.sum + '</span></span>' +
                    '<span class="badge">Exact ' + c.sum + '</span>' +
                    '</div>';
            });
            res.nearest.forEach(function(c, idx) {
                var parts = c.ids.map(function(ri) {
                    return '<b>' + esc(currentRolls[ri].ROLL) + '</b> (' + esc(currentRolls[ri].QTY) + ')';
                });
                html += '<div class="suggestion" data-idx="' + idx + '" data-exact="0" onclick="selectSuggestion(this)">' +
                    '<span class="combo">' + parts.join(' + ') + ' = <span style="color:#d97706;">' + c.sum + '</span></span>' +
                    '<span class="badge badge-near">Closest ' + c.sum + '</span>' +
                    '</div>';
            });
            box.innerHTML = html;
            selectedCombo = null;
            document.getElementById('previewPanel').style.display = 'none';
        }

        function selectSuggestion(el) {
            var qty = parseFloat(document.getElementById('splitQty').value);
            var res = findCombinations(currentRolls, qty, 0.01);
            var isExact = el.getAttribute('data-exact') === '1';
            var pool = isExact ? res.exact : res.nearest;
            var idx = parseInt(el.getAttribute('data-idx'), 10);
            var c = pool[idx];
            if (!c) return;

            selectedCombo = c;
            var suggs = document.querySelectorAll('.suggestion');
            suggs.forEach(function(s) { s.classList.remove('selected'); });
            el.classList.add('selected');

            // Build preview
            var selIds = {};
            c.ids.forEach(function(i) { selIds[i] = true; });
            var total = currentRolls.reduce(function(s, r) { return s + qtyOf(r); }, 0);
            total = Math.round(total * 100) / 100;

            var aRolls = '';
            c.ids.forEach(function(i) {
                aRolls += '<div class="roll-line"><span class="roll-badge" style="background:#a7f3d0;">' + esc(currentRolls[i].ROLL) + '</span>  Qty: ' + esc(currentRolls[i].QTY) + '</div>';
            });
            var bRolls = '';
            var bSum = 0;
            currentRolls.forEach(function(r, i) {
                if (!selIds[i]) {
                    bRolls += '<div class="roll-line"><span class="roll-badge">' + esc(r.ROLL) + '</span>  Qty: ' + esc(r.QTY) + '</div>';
                    bSum += qtyOf(r);
                }
            });
            bSum = Math.round(bSum * 100) / 100;

            document.getElementById('partARolls').innerHTML =
                '<div class="roll-line" style="font-weight:800; color:#059669;">→ ' + currentCard + '-A</div>' + aRolls;
            document.getElementById('partATotal').textContent =
                'Total A: ' + c.sum + '  (' + c.ids.length + ' roll' + (c.ids.length > 1 ? 's' : '') + ')';

            document.getElementById('partBRolls').innerHTML =
                '<div class="roll-line" style="font-weight:800; color:#059669;">→ ' + currentCard + '-B</div>' + bRolls;
            document.getElementById('partBTotal').textContent =
                'Total B: ' + bSum + '  (merged into 1 roll)';

            document.getElementById('previewPanel').style.display = 'block';
            hideMsg();
        }

        function doSplit() {
            if (!currentCard || !selectedCombo) {
                showMsg('Select a suggestion first.', 'error');
                return;
            }
            var selRolls = selectedCombo.ids.map(function(i) { return currentRolls[i].ROLL; });
            if (!confirm('Split batch card ' + currentCard + '?\n\nPart A: ' + currentCard + '-A (' + selectedCombo.sum + ' qty)\nPart B: ' + currentCard + '-B (remaining qty merged into 1 roll)')) return;

            var btn = document.getElementById('splitBtn');
            btn.disabled = true;
            var old = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Splitting...';

            apiPost('split', { card: currentCard, rolls_sel: selRolls }).then(function(resp) {
                if (resp && resp.success) {
                    showMsg(resp.message, 'success');
                    setTimeout(function() { location.reload(); }, 2500);
                } else {
                    showMsg(resp.message || 'Split failed', 'error');
                }
            }).catch(function() {
                showMsg('Server Error during split.', 'error');
            }).then(function() {
                btn.disabled = false;
                btn.innerHTML = old;
            });
        }

        document.getElementById('loadCardBtn').addEventListener('click', loadCard);
        document.getElementById('refreshCardsBtn').addEventListener('click', function() {
            loadCards();
            hideMsg();
        });
        document.getElementById('cardSearch').addEventListener('input', showSuggestions);
        document.getElementById('cardSearch').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                hideSuggestions();
                loadCard();
            }
        });
        document.getElementById('splitQty').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                renderSuggestions();
            }
        });
        document.getElementById('suggestBtn').addEventListener('click', renderSuggestions);
        document.getElementById('splitBtn').addEventListener('click', doSplit);

        loadCards();
    </script>
</body>

</html>