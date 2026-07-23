<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Input Details</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
      background-color: white;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      padding: 25px;
      margin-top: 30px;
      margin-bottom: 30px;
      max-width: 98%;
    }

    h2 {
      color: #343a40;
      font-weight: 600;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e9ecef;
    }

    .form-control {
      border-radius: 8px;
    }

    .btn {
      border-radius: 8px;
      padding: 8px 16px;
      font-weight: 500;
      transition: all 0.2s;
    }

    .btn-primary:hover,
    .btn-info:hover,
    .btn-outline-secondary:hover {
      transform: translateY(-1px);
    }

    .table-container {
      overflow-x: auto;
      margin-top: 20px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    }

    .table thead th,
    .table tbody td {
      text-align: center;
      vertical-align: middle;
      padding: 10px 8px;
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

    .controls-section {
      background-color: #f8f9fa;
      border-radius: 10px;
      padding: 20px 15px;
      margin-bottom: 20px;
    }

    .filter-row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
      gap: 12px;
      row-gap: 12px;
    }

    .filter-item {
      display: inline-flex;
      align-items: center;
    }

    .date-group {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      justify-content: center;
    }

    .search-input-group {
      min-width: 260px;
      flex: 0 1 auto;
    }

    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
    }

    .search-input-group .input-group {
      width: 100%;
    }

    @media (max-width: 860px) {
      .filter-row {
        flex-direction: column;
        align-items: stretch;
      }

      .filter-item,
      .date-group,
      .search-input-group,
      .action-buttons {
        justify-content: center;
      }

      .date-group {
        justify-content: center;
      }
    }

    .table thead th .column-filter {
      width: 28px;
      padding: 2px 4px;
      height: 28px;
      vertical-align: middle;
      display: inline-block;
      margin-right: 6px;
    }

    .small-text-muted {
      font-size: 12px;
      margin-top: 12px;
      text-align: center;
    }

    .line-subtotal-row td {
      background-color: #e3f2fd;
      color: #0d3c61;
      font-weight: 600;
      border-top: 2px solid #bbdefb;
    }

    .floor-total-row td {
      background-color: green;
      color: white;
      font-weight: 700;
      border-top: 2px solid #64b5f6;
    }

    .grandtotal-row td {
      background-color: black;
      color: #ffffff;
      font-weight: 700;
      border-top: 3px solid #333333;
    }

    .master-row {
      cursor: pointer;
      transition: 0.2s;
    }

    .master-row td:first-child {
      font-weight: 800;
      color: black;
    }

    .expand-icon {
      margin-right: 10px;
      transition: 0.2s;
    }

    /* When a master row is expanded we hide its summary cells except the toggle cell */
    .master-row.collapsed-shown td:not(:first-child) {
      display: none;
    }

    /* .detail-row {
      background: #ffffff;
    } */

    .detail-row:hover td {
      background: #edf0f3 !important;
    }

    .hidden-row {
      display: none;
    }

    .master-total {
      background: #1f7a4d !important;
      color: white !important;
      font-weight: bold;
    }

    .line-total-row td {
      background: #dbe7f3 !important;
      color: #0f172a !important;
      font-weight: bold;
      border-top: 2px solid black;
    }

    .floor-total-row td {
      background: green !important;
      color: white !important;
      font-weight: bold;
    }

    .grandtotal-row td {
      background: black !important;
      color: white !important;
      font-weight: bold;
    }
  </style>

</head>

<body>
  <div class="container">
    <a href="report.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Report</a>
    <h2 class="text-center" style="font-weight: bold; font-size:xx-large;">Input Details</h2>

    <div class="controls-section">
      <div class="filter-row">
        <div class="filter-item date-group">
          <input type="date" id="fromDate" class="form-control" style="width:150px;" placeholder="From Date">
          <span class="mx-1">TO</span>
          <input type="date" id="toDate" class="form-control" style="width:150px;" placeholder="To Date">
        </div>

        <div class="filter-item">
          <button class="btn btn-primary" id="searchButton"><i class="fas fa-calendar-alt"></i> Load Data</button>
        </div>

        <div class="filter-item search-input-group">
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" id="globalSearchInput" class="form-control" placeholder="SONO or Document NO" autocomplete="off">
          </div>
        </div>

        <div class="filter-item action-buttons">
          <button class="btn btn-info" id="applySearchBtn"><i class="fas fa-search"></i> Search</button>
          <button class="btn btn-outline-secondary" id="clearSearchBtn"><i class="fas fa-times"></i> Clear</button>
          <button class="btn btn-outline-danger" id="clearAllFiltersBtn"><i class="fas fa-undo-alt"></i> Reset All</button>
          <button class="btn btn-success" id="exportExcelBtn"><i class="fas fa-file-excel"></i> Export Excel</button>
          <button class="btn btn-outline-primary" id="showCollapseBtn"><i class="fas fa-chevron-down"></i> Show Collapse Data</button>
        </div>
      </div>
    </div>

    <div class="table-container">
      <table class="table table-bordered table-hover">
        <thead>
          <tr style="background-color: #343a40; color: white;">
            <th>INPUT DATE <select class="form-control form-control-sm column-filter" data-col="BUDAT"></select></th>
            <th>LINENO <select class="form-control form-control-sm column-filter" data-col="LINENO"></select></th>
            <th>SONO <select class="form-control form-control-sm column-filter" data-col="SONO"></select></th>
            <th>BUYER <select class="form-control form-control-sm column-filter" data-col="BUYER"></select></th>
            <th>STYLE <select class="form-control form-control-sm column-filter" data-col="STYLE"></select></th>
            <th>COLOR <select class="form-control form-control-sm column-filter" data-col="COLOR"></select></th>
            <th>DOC NO <select class="form-control form-control-sm column-filter" data-col="MBLNR"></select></th>
            <th>SIZE <select class="form-control form-control-sm column-filter" data-col="SIZE"></select></th>
            <th>PRODUCT TYPE <select class="form-control form-control-sm column-filter" data-col="P_PRO_TYPE"></select></th>
            <th>QTY</th>
            <th>NOP</th>
            <th>PANEL</th>
          </tr>
        </thead>
        <tbody id="getdata">
          <tr>
            <td colspan="12" class="text-center">Select date range and click "Load Data" or use Search with SONO/Doc No</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function bindCollapseEvents() {
      document.querySelectorAll('.line-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
          const targetId = this.dataset.target;
          const totalRowId = this.dataset.totalrow;
          const detailRows = document.querySelectorAll('.' + targetId);
          const totalRow = document.querySelector('.' + totalRowId);
          const icon = this.querySelector('.expand-icon');
          const masterRow = this.closest('tr.master-row');

          // determine if details are currently visible
          const expanded = detailRows.length > 0 && !detailRows[0].classList.contains('hidden-row');

          if (expanded) {
            collapseDetailGroup(detailRows, totalRow, masterRow, icon);
          } else {
            expandDetailGroup(detailRows, totalRow, masterRow, icon);
          }
        });
      });
    }

    function expandDetailGroup(detailRows, totalRow, masterRow, icon) {
      detailRows.forEach(r => r.classList.remove('hidden-row'));
      if (totalRow) totalRow.classList.remove('hidden-row');
      if (masterRow) masterRow.style.display = 'none';
      if (icon) {
        icon.classList.remove('fa-plus-circle');
        icon.classList.add('fa-minus-circle');
      }

      const targetId = detailRows.length > 0 ? Array.from(detailRows[0].classList).find(c => c.startsWith('details_')) : null;
      if (!targetId) return;
      const collapseId = 'collapse-' + targetId;
      const existingCollapse = document.querySelector('#' + collapseId);
      if (!existingCollapse && detailRows.length > 0) {
        const firstDetail = detailRows[0];
        const collapseRow = document.createElement('tr');
        collapseRow.id = collapseId;
        collapseRow.className = 'collapse-control-row';
        collapseRow.innerHTML = `<td colspan="12" style="text-align:left;padding:6px 12px;background:#f1f3f5;border-bottom:1px solid #ddd;"><button type="button" class="btn btn-sm btn-secondary collapse-details">Collapse</button></td>`;
        firstDetail.parentNode.insertBefore(collapseRow, firstDetail);

        collapseRow.querySelector('.collapse-details').addEventListener('click', function() {
          collapseDetailGroup(detailRows, totalRow, masterRow, icon);
          collapseRow.remove();
        });
      }
    }

    function collapseDetailGroup(detailRows, totalRow, masterRow, icon) {
      detailRows.forEach(r => r.classList.add('hidden-row'));
      if (totalRow) totalRow.classList.add('hidden-row');
      const targetId = detailRows.length > 0 ? Array.from(detailRows[0].classList).find(c => c.startsWith('details_')) : null;
      if (targetId) {
        const collapseId = 'collapse-' + targetId;
        const existingCollapse = document.querySelector('#' + collapseId);
        if (existingCollapse) existingCollapse.remove();
      }
      if (masterRow) masterRow.style.display = '';
      if (icon) {
        icon.classList.remove('fa-minus-circle');
        icon.classList.add('fa-plus-circle');
      }
    }

    function expandAllDetails() {
      document.querySelectorAll('.detail-row').forEach(r => r.classList.remove('hidden-row'));
      document.querySelectorAll('.line-total-row').forEach(r => r.classList.remove('hidden-row'));
      document.querySelectorAll('.collapse-control-row').forEach(r => r.remove());
      document.querySelectorAll('tr.master-row').forEach(r => r.style.display = 'none');
      document.querySelectorAll('.line-toggle .expand-icon').forEach(i => {
        i.classList.remove('fa-plus-circle');
        i.classList.add('fa-minus-circle');
      });
    }

    function collapseAllDetails() {
      document.querySelectorAll('.detail-row').forEach(r => r.classList.add('hidden-row'));
      document.querySelectorAll('.line-total-row').forEach(r => r.classList.add('hidden-row'));
      document.querySelectorAll('.collapse-control-row').forEach(r => r.remove());
      document.querySelectorAll('tr.master-row').forEach(r => r.style.display = '');
      document.querySelectorAll('.line-toggle .expand-icon').forEach(i => {
        i.classList.remove('fa-minus-circle');
        i.classList.add('fa-plus-circle');
      });
    }

    let allMasterRows = [];
    let currentSearchTerm = "";
    let activeColumnFilters = {};
    let lastLoadedFromDate = "";
    let lastLoadedToDate = "";

    function escapeHtml(text) {
      if (!text) return '';
      return String(text).replace(/["&'<>]/g, function(a) {
        return {
          '"': '&quot;',
          '&': '&amp;',
          "'": "&#39;",
          "<": "&lt;",
          ">": "&gt;"
        } [a];
      });
    }

    function parseNumber(val) {
      let num = parseFloat(val);
      return isNaN(num) ? 0 : num;
    }

    function getFloorFromLineNo(lineno) {
      if (!lineno) return null;
      let match = String(lineno)
        .toUpperCase()
        .match(/L-(\d{2}B?)/);
      if (!match) return null;
      let line = match[1];
      const floor1 = [];
      const floor2 = [];
      const floor3 = [];
      const floor4 = [];
      const floor5 = [];

      for (let i = 1; i <= 52; i++) {
        const floor = String(i).padStart(2, '0');

        if (i >= 1 && i <= 10) {
          floor1.push(floor);
        } else if ((i >= 11 && i <= 20) || (i >= 41 && i <= 46)) {
          floor2.push(floor);
        } else if (i >= 21 && i <= 30) {
          floor3.push(floor);
        } else if ((i >= 31 && i <= 40) || (i >= 47 && i <= 50)) {
          floor4.push(floor);
        }
        else if (i >= 51 && i <= 52) {
          floor5.push(floor);
        }
      }

      // floor1, floor2, floor3, floor4, floor5 ready
      if (floor1.includes(line)) return 1;
      if (floor2.includes(line)) return 2;
      if (floor3.includes(line)) return 3;
      if (floor4.includes(line)) return 4;
      if (floor5.includes(line)) return 5;
      return null;
    }

    function sortRowsByFloorAndLine(rows) {
      const lineOrder = [
        '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
        '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
        '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
        '31', '32', '33', '34', '35', '36', '37', '38', '39', '40',
        '41', '42', '43', '44', '45', '46', '47', '48', '49', '50',
        '51', '52', 'LEFTOVER'
      ];

      function extractLine(lineNo) {
        let match = String(lineNo || '')
          .toUpperCase()
          .match(/L-(\d{2}B?)/);
        return match ? match[1] : '';
      }
      return [...rows].sort((a, b) => {
        let floorA = getFloorFromLineNo(a.LINENO) ?? 999;
        let floorB = getFloorFromLineNo(b.LINENO) ?? 999;
        if (floorA !== floorB) {
          return floorA - floorB;
        }
        let lineA = extractLine(a.LINENO);
        let lineB = extractLine(b.LINENO);
        return lineOrder.indexOf(lineA) - lineOrder.indexOf(lineB);
      });
    }

    function renderFilteredTable() {
      if (!allMasterRows.length) {
        $('#getdata').html(`
      <tr>
        <td colspan="12" class="text-center">
          No data available
        </td>
      </tr>
    `);
        return;
      }

      let filtered = allMasterRows.filter(row => {
        for (let col in activeColumnFilters) {
          let filterVal = activeColumnFilters[col];
          if (!filterVal) continue;
          let cellValue = row[col] ? String(row[col]) : "";
          if (col === 'SIZE' && filterVal === "__EMPTY__") {
            if (cellValue !== "") return false;
          } else {
            if (cellValue !== filterVal) return false;
          }
        }
        return true;
      });

      if (currentSearchTerm.trim() !== "") {
        const term = currentSearchTerm.toLowerCase();
        filtered = filtered.filter(row => {
          const sono = row.SONO ? String(row.SONO).toLowerCase() : "";
          const docno = row.MBLNR ? String(row.MBLNR).toLowerCase() : "";
          return sono.includes(term) || docno.includes(term);
        });
      }

      if (!filtered.length) {
        $('#getdata').html(`
      <tr>
        <td colspan="12" class="text-center text-warning">
          No matching records
        </td>
      </tr>
    `);
        return;
      }

      filtered = sortRowsByFloorAndLine(filtered);
      let grouped = {};
      filtered.forEach(row => {
        let line = row.LINENO || 'UNKNOWN';
        if (!grouped[line]) {
          grouped[line] = [];
        }
        grouped[line].push(row);
      });

      let html = '';
      let grandQty = 0;
      let grandNop = 0;
      let grandPanel = 0;
      let floorTotals = {
        1: {
          qty: 0,
          nop: 0,
          panel: 0
        },
        2: {
          qty: 0,
          nop: 0,
          panel: 0
        },
        3: {
          qty: 0,
          nop: 0,
          panel: 0
        },
        4: {
          qty: 0,
          nop: 0,
          panel: 0
        },
        5: {
          qty: 0,
          nop: 0,
          panel: 0
        }

      };

      Object.keys(grouped).forEach(lineNo => {
        let rows = grouped[lineNo];
        let lineQty = 0;
        let lineNop = 0;
        let linePanel = 0;
        rows.forEach(r => {
          lineQty += parseNumber(r.QTY);
          lineNop += parseNumber(r.NOP);
          linePanel += parseNumber(r.PANEL);
        });

        let floor = getFloorFromLineNo(lineNo);
        if (floorTotals[floor]) {
          floorTotals[floor].qty += lineQty;
          floorTotals[floor].nop += lineNop;
          floorTotals[floor].panel += linePanel;
        }

        grandQty += lineQty;
        grandNop += lineNop;
        grandPanel += linePanel;
      });

      const floorLines = {
        1: [
          'L-01', 'L-02', 'L-03', 'L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-10'
        ],
        2: [
          'L-11', 'L-12', 'L-13', 'L-14', 'L-15', 'L-16', 'L-17', 'L-18', 'L-19', 'L-20',
          'L-41', 'L-42', 'L-43', 'L-44', 'L-45', 'L-46'
        ],
        3: [
          'L-21', 'L-22', 'L-23', 'L-24', 'L-25', 'L-26', 'L-27', 'L-28', 'L-29', 'L-30'
        ],
        4: [
          'L-31', 'L-32', 'L-33', 'L-34', 'L-35', 'L-36', 'L-37', 'L-38', 'L-39', 'L-40',
          'L-47', 'L-48', 'L-49', 'L-50'
        ],
        5: [
          'L-51', 'L-52', 'LEFTOVER'
        ]
      };

      for (let floor = 1; floor <= 5; floor++) {
        floorLines[floor].forEach(lineNo => {
          if (!grouped[lineNo]) return;
          let rows = grouped[lineNo];
          let lineQty = 0;
          let lineNop = 0;
          let linePanel = 0;

          rows.forEach(r => {
            lineQty += parseNumber(r.QTY);
            lineNop += parseNumber(r.NOP);
            linePanel += parseNumber(r.PANEL);
          });

          let detailId = 'details_' + lineNo.replace(/[^a-zA-Z0-9]/g, '_');
          let totalId = 'total_' + lineNo.replace(/[^a-zA-Z0-9]/g, '_');

          html += `
    <tr class="master-row">

      <td></td>

      <td class="line-toggle"
          data-target="${detailId}"
          data-totalrow="${totalId}"
          style="cursor:pointer;">

        <i class="fas fa-plus-circle expand-icon"></i>

        <strong>${lineNo}</strong>

      </td>

      <td colspan="7"
          style="text-align:center;font-weight:bold;">

        Total for ${lineNo}
        (${rows.length} Rows)

      </td>

      <td class="master-total">${lineQty}</td>
      <td class="master-total">${lineNop}</td>
      <td class="master-total">${linePanel}</td>

    </tr>
    `;

          rows.forEach(r => {
            html += `
      <tr class="detail-row ${detailId} hidden-row">
        <td>${r.BUDAT || ''}</td>
        <td>${r.LINENO || ''}</td>
        <td>${r.SONO || ''}</td>
        <td>${r.BUYER || ''}</td>
        <td>${r.STYLE || ''}</td>
        <td>${r.COLOR || ''}</td>
        <td>${r.MBLNR || ''}</td>
        <td>${r.SIZE || ''}</td>
        <td>${r.P_PRO_TYPE || ''}</td>
        <td>${parseNumber(r.QTY)}</td>
        <td>${parseNumber(r.NOP)}</td>
        <td>${parseNumber(r.PANEL)}</td>
      </tr>
      `;
          });

          html += `
    <tr class="line-total-row ${totalId} hidden-row">

      <td colspan="9" style="text-align:right;">
        TOTAL for ${lineNo}
      </td>
      <td>${lineQty}</td>
      <td>${lineNop}</td>
      <td>${linePanel}</td>
    </tr>
    `;
        });

        html += `
  <tr class="floor-total-row">

    <td colspan="9">
      TOTAL Unit ${String(floor).padStart(2, '0')}
    </td>

    <td>${floorTotals[floor].qty}</td>
    <td>${floorTotals[floor].nop}</td>
    <td>${floorTotals[floor].panel}</td>

  </tr>
  `;
      }

      html += `
<tr class="grandtotal-row">

  <td colspan="9">
    GRAND TOTAL (Input Details)
  </td>
  <td>${grandQty}</td>
  <td>${grandNop}</td>
  <td>${grandPanel}</td>

</tr>
`;

      $('#getdata').html(html);
      bindCollapseEvents();
      updateDropdownOptionsBasedOnVisibleData(filtered);
    }

    function updateDropdownOptionsBasedOnVisibleData(visibleRows) {
      const cols = ['BUDAT', 'LINENO', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'MBLNR', 'SIZE', 'P_PRO_TYPE'];
      let sets = {};
      cols.forEach(c => {
        sets[c] = new Set();
      });

      visibleRows.forEach(row => {
        cols.forEach(col => {
          let val = row[col];
          if (col === 'SIZE') {
            if (val === undefined || val === null || val === "") {
              sets[col].add("(BLANK)");
            } else {
              sets[col].add(String(val));
            }
          } else {
            if (val !== undefined && val !== null && val !== "") {
              sets[col].add(String(val));
            }
          }
        });
      });

      cols.forEach(col => {
        let $select = $(`.column-filter[data-col="${col}"]`);
        let currentStoredVal = activeColumnFilters[col] || "";
        $select.html('<option value="">All</option>');
        let sortedValues = Array.from(sets[col]).sort((a, b) => {
          // Push (BLANK) to the end or beginning - here we'll put at beginning for visibility
          if (a === "(BLANK)") return -1;
          if (b === "(BLANK)") return 1;
          return a.localeCompare(b);
        });
        sortedValues.forEach(v => {
          let displayValue = v;
          let optionValue = v;
          if (v === "(BLANK)") {
            optionValue = "__EMPTY__";
          }
          $select.append(`<option value="${escapeHtml(optionValue)}">${escapeHtml(displayValue)}</option>`);
        });
        if (currentStoredVal) {
          // Handle the special __EMPTY__ value
          let matchValue = currentStoredVal;
          if (currentStoredVal === "__EMPTY__") {
            $select.val("__EMPTY__");
          } else if ($select.find(`option[value="${currentStoredVal.replace(/"/g, '&quot;')}"]`).length) {
            $select.val(currentStoredVal);
          } else {
            if (currentStoredVal) activeColumnFilters[col] = "";
            $select.val("");
          }
        } else {
          if (currentStoredVal) activeColumnFilters[col] = "";
          $select.val("");
        }
      });
    }

    function populateFullDropdownsFromMaster() {
      if (!allMasterRows.length) {
        const cols = ['BUDAT', 'LINENO', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'MBLNR', 'SIZE', 'P_PRO_TYPE'];
        cols.forEach(col => {
          $(`.column-filter[data-col="${col}"]`).html('<option value="">All</option>');
        });
        return;
      }
      const cols = ['BUDAT', 'LINENO', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'MBLNR', 'SIZE', 'P_PRO_TYPE'];
      let sets = {};
      cols.forEach(c => {
        sets[c] = new Set();
      });
      allMasterRows.forEach(row => {
        cols.forEach(col => {
          let val = row[col];
          if (col === 'SIZE') {
            if (val === undefined || val === null || val === "") {
              sets[col].add("(BLANK)");
            } else {
              sets[col].add(String(val));
            }
          } else {
            if (val !== undefined && val !== null && val !== "") sets[col].add(String(val));
          }
        });
      });
      cols.forEach(col => {
        let $select = $(`.column-filter[data-col="${col}"]`);
        $select.html('<option value="">All</option>');
        let sortedValues = Array.from(sets[col]).sort((a, b) => {
          if (a === "(BLANK)") return -1;
          if (b === "(BLANK)") return 1;
          return a.localeCompare(b);
        });
        sortedValues.forEach(v => {
          let displayValue = v;
          let optionValue = v;
          if (v === "(BLANK)") {
            optionValue = "__EMPTY__";
          }
          $select.append(`<option value="${escapeHtml(optionValue)}">${escapeHtml(displayValue)}</option>`);
        });
      });
    }

    function getVisibleRows() {
      if (!allMasterRows || !allMasterRows.length) return [];
      let filtered = allMasterRows.filter(row => {
        for (let col in activeColumnFilters) {
          let filterVal = activeColumnFilters[col];
          if (filterVal === undefined || filterVal === "") continue;
          let cellValue = (row[col] !== undefined && row[col] !== null) ? String(row[col]) : "";
          if (cellValue !== filterVal) return false;
        }
        return true;
      });

      const searchInputVal = ($('#globalSearchInput').val() || '').toString().trim().toLowerCase();
      if (searchInputVal !== "") {
        filtered = filtered.filter(row => {
          const sono = (row.SONO !== undefined && row.SONO !== null) ? String(row.SONO).toLowerCase() : "";
          const docno = (row.MBLNR !== undefined && row.MBLNR !== null) ? String(row.MBLNR).toLowerCase() : "";
          return sono.includes(searchInputVal) || docno.includes(searchInputVal);
        });
      }

      const sorted = sortRowsByFloorAndLine(filtered);
      return sorted;
    }

    function exportVisibleToExcel() {
      const visibleRows = getVisibleRows();
      if (!visibleRows || visibleRows.length === 0) {
        alert('No data visible to export.');
        return;
      }

      const headers = ['INPUT DATE', 'LINENO', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'DOC NO', 'SIZE', 'PRODUCT TYPE', 'QTY', 'NOP', 'PANEL'];
      const ws_data = [headers];
      let currentFloor = null;
      let floorQty = 0,
        floorNop = 0,
        floorPanel = 0;
      let currentLine = null;
      let lineQty = 0,
        lineNop = 0,
        linePanel = 0;
      let grandQty = 0,
        grandNop = 0,
        grandPanel = 0;

      function pushLineSubtotal(line) {
        if (line !== null) {
          ws_data.push([
            `TOTAL of LINE : ${line}`,
            '', '', '', '', '', '', '', '',
            lineQty,
            lineNop,
            linePanel
          ]);
          floorQty += lineQty;
          floorNop += lineNop;
          floorPanel += linePanel;
          lineQty = 0;
          lineNop = 0;
          linePanel = 0;
        }
      }

      function pushFloorTotal(floor) {
        if (floor !== null && (floorQty !== 0 || floorNop !== 0 || floorPanel !== 0)) {
          const floorLabel = `TOTAL of FLOOR : 0${floor}`;
          ws_data.push([
            floorLabel,
            '', '', '', '', '', '', '', '',
            floorQty,
            floorNop,
            floorPanel
          ]);
          floorQty = 0;
          floorNop = 0;
          floorPanel = 0;
        }
      }

      visibleRows.forEach(r => {
        const lineNo = (r.LINENO !== undefined && r.LINENO !== null) ? String(r.LINENO) : '';
        const floor = getFloorFromLineNo(lineNo);
        const qty = parseNumber(r.QTY);
        const nop = parseNumber(r.NOP);
        const panel = parseNumber(r.PANEL);

        if (currentFloor !== floor) {
          pushLineSubtotal(currentLine);
          pushFloorTotal(currentFloor);
          currentFloor = floor;
          currentLine = null;
        }

        if (currentLine !== lineNo) {
          pushLineSubtotal(currentLine);
          currentLine = lineNo;
        }

        ws_data.push([
          r.BUDAT || '',
          r.LINENO || '',
          r.SONO || '',
          r.BUYER || '',
          r.STYLE || '',
          r.COLOR || '',
          r.MBLNR || '',
          r.SIZE || '',
          r.P_PRO_TYPE || '',
          qty,
          nop,
          panel
        ]);

        lineQty += qty;
        lineNop += nop;
        linePanel += panel;
        grandQty += qty;
        grandNop += nop;
        grandPanel += panel;
      });

      pushLineSubtotal(currentLine);
      pushFloorTotal(currentFloor);

      ws_data.push([
        'GRAND TOTAL', '', '', '', '', '', '', '', '',
        grandQty,
        grandNop,
        grandPanel
      ]);

      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet(ws_data);
      XLSX.utils.book_append_sheet(wb, ws, 'InputDetails');
      const now = new Date();
      const fname = `input_details_${now.getFullYear()}${String(now.getMonth()+1).padStart(2,'0')}${String(now.getDate()).padStart(2,'0')}.xlsx`;
      XLSX.writeFile(wb, fname);
    }

    function syncColumnFiltersFromSelects() {
      $('.column-filter').each(function() {
        let col = $(this).data('col');
        let val = $(this).val();
        if (val && val !== "") {
          if (col === 'SIZE' && val === "__EMPTY__") {
            activeColumnFilters[col] = "__EMPTY__";
          } else {
            activeColumnFilters[col] = val;
          }
        } else {
          delete activeColumnFilters[col];
        }
      });
    }

    function resetAllFilters() {
      currentSearchTerm = "";
      $('#globalSearchInput').val("");
      activeColumnFilters = {};
      $('.column-filter').each(function() {
        $(this).val('');
      });
      renderFilteredTable();
    }

    function populateFullDropdownsFromMaster() {
      if (!allMasterRows.length) {
        const cols = ['BUDAT', 'LINENO', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'MBLNR', 'SIZE', 'P_PRO_TYPE'];
        cols.forEach(col => {
          $(`.column-filter[data-col="${col}"]`).html('<option value=""></option>');
        });
        return;
      }
      const cols = ['BUDAT', 'LINENO', 'SONO', 'BUYER', 'STYLE', 'COLOR', 'MBLNR', 'SIZE', 'P_PRO_TYPE'];
      let sets = {};
      cols.forEach(c => {
        sets[c] = new Set();
      });
      allMasterRows.forEach(row => {
        cols.forEach(col => {
          let val = row[col];
          if (val !== undefined && val !== null && val !== "") sets[col].add(String(val));
        });
      });
      cols.forEach(col => {
        let $select = $(`.column-filter[data-col="${col}"]`);
        $select.html('<option value=""></option>');
        Array.from(sets[col]).sort().forEach(v => {
          $select.append(`<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`);
        });
      });
    }

    function loadDataFromServer(callback) {
      let fromDateISO = $('#fromDate').val();
      let toDateISO = $('#toDate').val();
      if (!fromDateISO || !toDateISO) {
        alert('Please select both From Date and To Date.');
        if (callback) callback(false);
        return false;
      }

      let fromDateDMY = fromDateISO.split('-').reverse().join('-');
      let toDateDMY = toDateISO.split('-').reverse().join('-');
      $('#getdata').html('<tr><td colspan="12" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
      $.ajax({
        url: 'ajaxInputDetails.php',
        type: 'POST',
        data: {
          fromDate: fromDateDMY,
          toDate: toDateDMY
        },
        dataType: 'json',
        success: function(resp) {
          if (resp.error) {
            alert(resp.error);
            $('#getdata').html('<tr><td colspan="12" class="text-center text-danger">' + resp.error + '</td></tr>');
            allMasterRows = [];
            populateFullDropdownsFromMaster();
            lastLoadedFromDate = fromDateISO;
            lastLoadedToDate = toDateISO;
            if (callback) callback(false);
            return;
          }
          allMasterRows = resp.data || [];
          lastLoadedFromDate = fromDateISO;
          lastLoadedToDate = toDateISO;
          if (allMasterRows.length === 0) {
            $('#getdata').html('<tr><td colspan="12" class="text-center">No data available for selected dates</td></tr>');
            populateFullDropdownsFromMaster();
            resetAllFilters();
            if (callback) callback(true);
            return;
          }
          currentSearchTerm = "";
          $('#globalSearchInput').val("");
          activeColumnFilters = {};
          populateFullDropdownsFromMaster();
          $('.column-filter').val('');
          renderFilteredTable();
          if (callback) callback(true);
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', xhr.responseText);
          alert('Server error: ' + error + '\nPlease check console.');
          $('#getdata').html('<tr><td colspan="12" class="text-center text-danger">Failed to load data.</td></tr>');
          if (callback) callback(false);
        }
      });
      return true;
    }

    function ensureDataLoadedThen(actionAfterLoad) {
      let fromDateISO = $('#fromDate').val();
      let toDateISO = $('#toDate').val();
      if (!fromDateISO || !toDateISO) {
        alert('Please select both From Date and To Date.');
        return false;
      }
      if (allMasterRows.length > 0 && lastLoadedFromDate === fromDateISO && lastLoadedToDate === toDateISO) {
        if (actionAfterLoad) actionAfterLoad();
        return true;
      } else {
        loadDataFromServer(function(success) {
          if (success && actionAfterLoad) actionAfterLoad();
        });
        return true;
      }
    }

    function applyGlobalSearchWithAutoLoad() {
      let searchVal = $('#globalSearchInput').val().trim();
      if (searchVal == "") {
        alert("Please enter SONO or DOC NO");
        return;
      }

      $('#getdata').html(`
    <tr>
      <td colspan="12" class="text-center">
        <i class="fas fa-spinner fa-spin"></i> Searching...
      </td>
    </tr>
  `);

      $.ajax({
        url: 'ajaxInputDetails.php',
        type: 'POST',
        data: {
          searchTerm: searchVal
        },
        dataType: 'json',

        success: function(resp) {
          if (resp.error) {
            alert(resp.error);
            return;
          }

          allMasterRows = resp.data || [];
          currentSearchTerm = "";
          activeColumnFilters = {};
          populateFullDropdownsFromMaster();
          renderFilteredTable();
        },

        error: function(xhr, status, error) {
          alert('Search failed');
          console.log(xhr.responseText);
        }
      });
    }

    function clearGlobalSearch() {
      currentSearchTerm = "";
      $('#globalSearchInput').val("");
      renderFilteredTable();
    }

    $(document).ready(function() {
      let today = new Date().toISOString().split("T")[0];
      $("#fromDate").attr("max", today);
      $("#toDate").attr("max", today);
      $("#fromDate").val(today);
      $("#toDate").val(today);
      $('#searchButton').off('click').on('click', function(e) {
        e.preventDefault();
        loadDataFromServer(function() {
          currentSearchTerm = "";
          $('#globalSearchInput').val("");
          activeColumnFilters = {};
          $('.column-filter').val('');
          renderFilteredTable();
        });
      });

      $('#applySearchBtn').off('click').on('click', function(e) {
        e.preventDefault();
        applyGlobalSearchWithAutoLoad();
      });

      $('#clearSearchBtn').off('click').on('click', function(e) {
        e.preventDefault();
        clearGlobalSearch();
      });

      $('#clearAllFiltersBtn').off('click').on('click', function(e) {
        e.preventDefault();
        resetAllFilters();
      });

      $('#exportExcelBtn').off('click').on('click', function(e) {
        e.preventDefault();
        ensureDataLoadedThen(function() {
          exportVisibleToExcel();
        });
      });

      $('#showCollapseBtn').off('click').on('click', function(e) {
        e.preventDefault();
        if (!allMasterRows.length) {
          alert('Please load data first.');
          return;
        }
        const isExpanded = $(this).data('expanded') === true;
        if (isExpanded) {
          collapseAllDetails();
          $(this).data('expanded', false);
          $(this).html('<i class="fas fa-chevron-down"></i> Show Collapse Data');
        } else {
          expandAllDetails();
          $(this).data('expanded', true);
          $(this).html('<i class="fas fa-chevron-up"></i> Hide Collapse Data');
        }
      });

      $('#globalSearchInput').off('keypress').on('keypress', function(e) {
        if (e.which === 13) {
          e.preventDefault();
          applyGlobalSearchWithAutoLoad();
        }
      });

      $(document).off('change', '.column-filter').on('change', '.column-filter', function() {
        syncColumnFiltersFromSelects();
        renderFilteredTable();
      });
      $('#getdata').html('<tr><td colspan="12" class="text-center">Select date range and click "Load Data" or use Search with SONO/Doc No</td></tr>');
    });
  </script>
</body>

</html>