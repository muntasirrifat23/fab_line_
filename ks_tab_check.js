var TAB_CONFIG = {
            receive: {
                title: 'Knitting Store Report',
                endpoint: 'ajaxKnittingStore_Report.php',
                cols: [
                    { key: 'BUDAT', label: 'Date' },
                    { key: 'RACK', label: 'Rack' },
                    { key: 'ROLL', label: 'Roll No' },
                    { key: 'PO_NUMBER', label: 'PO Number' },
                    { key: 'QTY', label: 'QTY' },
                    { key: 'SONO', label: 'SONO' },
                    { key: 'SHIFT', label: 'Shift' },
                    { key: 'BUYER', label: 'Buyer' },
                    { key: 'STYLE', label: 'Style' },
                    { key: 'COLOR', label: 'Color' },
                    { key: 'MCNO', label: 'Machine No' },
                    { key: 'MCDIA', label: 'MC Dia' },
                    { key: 'CUSTOMER', label: 'Customer' },
                    { key: 'YTYPE', label: 'Yarn Type' },
                    { key: 'YCOUNT', label: 'Yarn Count' },
                    { key: 'YBRAND', label: 'Yarn Brand' },
                    { key: 'O_T', label: 'Open / Tube' },
                    { key: 'SL', label: 'SL/VQ' },
                    { key: 'FTYPE', label: 'Fabrics Type' },
                    { key: 'FGSM', label: 'Finish GSM' },
                    { key: 'FDIA', label: 'Finish Dia' },
                    { key: 'FEEDER_PLAN', label: 'Feeder Plan' },
                    { key: 'LOT_NO', label: 'Lot No' },
                    { key: 'TPOINT', label: 'T Point' },
                    { key: 'UNAME', label: 'Uname' }
                ],
                exclude: ['UNAME'],
                useRackCombined: true
            },
            hold: {
                title: 'Knitting Inspection Report (Hold)',
                endpoint: 'ajaxKnittingInspection_Report.php',
                cols: [
                    { key: 'BUDAT', label: 'Date' },
                    { key: 'ROLL', label: 'Roll No' },
                    { key: 'PO_NUMBER', label: 'PO Number' },
                    { key: 'SONO', label: 'SONO' },
                    { key: 'SHIFT', label: 'Shift' },
                    { key: 'BUYER', label: 'Buyer' },
                    { key: 'STYLE', label: 'Style' },
                    { key: 'COLOR', label: 'Color' },
                    { key: 'MCNO', label: 'Machine No' },
                    { key: 'MCDIA', label: 'MC Dia' },
                    { key: 'CUSTOMER', label: 'Customer' },
                    { key: 'YCOUNT', label: 'Yarn Count' },
                    { key: 'YBRAND', label: 'Yarn Brand' },
                    { key: 'FTYPE', label: 'Fabrics Type' },
                    { key: 'FGSM', label: 'Finish GSM' },
                    { key: 'FDIA', label: 'Finish Dia' },
                    { key: 'TOTALMTR', label: 'Total MTR' },
                    { key: 'ACCEPT', label: 'Accept' },
                    { key: 'RQTY', label: 'Reject' },
                    { key: 'QC_GRADE', label: 'QC Grade' },
                    { key: 'UNAME', label: 'Uname' }
                ],
                exclude: ['UNAME']
            },
            pending: {
                title: 'Knitting Production Report (Pending)',
                endpoint: 'ajaxKnittingProduction_Report.php',
                cols: [
                    { key: 'BUDAT', label: 'Date' },
                    { key: 'ROLL', label: 'Roll No' },
                    { key: 'PO_NUMBER', label: 'PO Number' },
                    { key: 'PQTY', label: 'Qty' },
                    { key: 'SONO', label: 'SONO' },
                    { key: 'SHIFT', label: 'Shift' },
                    { key: 'BUYER', label: 'Buyer' },
                    { key: 'STYLE', label: 'Style' },
                    { key: 'COLOR', label: 'Color' },
                    { key: 'MCNO', label: 'Machine No' },
                    { key: 'MC_DIA', label: 'MC Dia' },
                    { key: 'CUSTOMER', label: 'Customer' },
                    { key: 'YARN_COUNT', label: 'Yarn Count' },
                    { key: 'YBRAND', label: 'Yarn Brand' },
                    { key: 'FABRICS_TYPE', label: 'Fabrics Type' },
                    { key: 'FINISH_GSM', label: 'Finish GSM' },
                    { key: 'FINISH_DIA', label: 'Finish Dia' },
                    { key: 'UNAME', label: 'Uname' }
                ],
                exclude: ['UNAME']
            }
        };

        var currentTab = 'receive';
        var currentData = [];
        var COLS = [];
        var PDF_COLS = [];
        var PDF_EXCLUDE = [];

        // RACK is a computed field: RackLocation + RackNo (e.g. A1 + 18 => A118)
        function colValue(row, c) {
            if (c.key === 'RACK') {
                var loc = row.RACKLOCATION;
                var no = row.RACKNO;
                loc = (loc === null || loc === undefined) ? '' : String(loc);
                no = (no === null || no === undefined) ? '' : String(no);
                return loc + no;
            }
            return row[c.key];
        }

        function setActiveTab(tab) {
            currentTab = tab;
            $('.tab-btn').removeClass('active');
            $('.tab-btn[data-tab="' + tab + '"]').addClass('active');

            var cfg = TAB_CONFIG[tab];
            $('h1').text(cfg.title);
            $('title').text(cfg.title + ' | Report');

            COLS = (cfg.cols || []).slice();
            PDF_EXCLUDE = (cfg.exclude || []).slice();
            PDF_COLS = COLS.filter(function(c) {
                return PDF_EXCLUDE.indexOf(c.key) === -1;
            });

            renderTableHeaders();
            loadAll();
        }

        function esc(v) {
            if (v === null || v === undefined) return '';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function buildRowCardElement(row) {
            var rowsHTML = PDF_COLS.map(function(c) {
                var val = colValue(row, c);
                if (val === null || val === undefined) val = '';
                return '<div class="pdf-item"><span class="pdf-label">' + c.label + ' :</span><span class="pdf-value">' + val + '</span></div>';
            }).join('');

            var content = '' +
                '<div id="rowPdfCard" style="position:relative;width:760px;min-height:1050px;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">' +
                '<div style="text-align:center;font-size:20px;font-weight:bold;color:#1e3a8a;border-bottom:3px solid #2563eb;padding-bottom:10px;margin-bottom:16px;">' +
                'Knitting Store Report' +
                '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;font-size:14px;font-weight:bold;margin-bottom:10px; margin-left:10px;">' +
                '<span>Roll : ' + (row.ROLL || '') + '</span>' +
                '</div>' +
                '<div class="pdf-grid">' + rowsHTML + '</div>' +
                '<div style="text-align:center;font-size:11px;color: black; margin-top:16px;border-top:1px solid #e5e7eb;padding-top:8px;">' +
                'Generated from Knitting Store Report - ' + new Date().toLocaleString() +
                '</div>' +

                '<div class="pdf-sign">' +
                '<div class="pdf-sign-item"><div class="pdf-sign-line"></div><span>Supervisor</span></div>' +
                '<div class="pdf-sign-item"><div class="pdf-sign-line"></div><span>Incharge</span></div>' +
                '<div class="pdf-sign-item"><div class="pdf-sign-line"></div><span>AGM</span></div>' +
                '<div class="pdf-sign-item"><div class="pdf-sign-line"></div><span>GM</span></div>' +
                '</div>' +

                '</div>';

            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '0';
            document.body.appendChild(tempDiv);

            return tempDiv;
        }

        function renderCardImage(row, done, fail) {
            var card = buildRowCardElement(row);

            setTimeout(function() {
                html2canvas(card, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(function(canvas) {
                    if (card.parentNode) card.parentNode.removeChild(card);
                    done(canvas.toDataURL('image/png'));
                }).catch(function(err) {
                    console.error('Render error:', err);
                    if (card.parentNode) card.parentNode.removeChild(card);
                    if (fail) fail(err);
                });
            }, 150);
        }

        function printRow(row) {
            renderCardImage(row, function(imgData) {
                var iframe = document.createElement('iframe');
                iframe.setAttribute('aria-hidden', 'true');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);

                // Identical to the old PDF: A4 portrait, full-width card image at top-left.
                var doc = iframe.contentWindow.document;
                doc.open();
                doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Knitting Store Report</title><style>' +
                    '@page{size:A4 portrait;margin:0;}' +
                    'html,body{margin:0;padding:0;background:#fff;}' +
                    'img{display:block;width:210mm;height:auto;}' +
                    '</style></head><body>' +
                    '<img src="' + imgData + '">' +
                    '</body></html>');
                doc.close();

                var doPrint = function() {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };

                var printImg = doc.querySelector('img');
                if (printImg && !printImg.complete) {
                    printImg.onload = function() { setTimeout(doPrint, 150); };
                } else {
                    setTimeout(doPrint, 300);
                }

                setTimeout(function() {
                    if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
                }, 60000);
            }, function() {
                alert('Error preparing print. Please try again.');
            });
        }

        function viewRow(row) {
            var body = $('#previewBody');
            body.empty();
            body.html('<div class="preview-loading">Preparing preview...</div>');
            $('#previewOverlay').addClass('show');

            renderCardImage(row, function(imgData) {
                body.empty();
                var availWidth = body.width() - 40;
                var maxW = Math.min(availWidth, 620);
                if (!(maxW > 0)) maxW = 620;

                var img = $('<img>').attr('src', imgData).css({
                    width: maxW + 'px',
                    height: 'auto'
                });
                body.append(img);

                $('#previewPrint').off('click').on('click', function() {
                    printRow(row);
                });
            }, function() {
                body.html('<div class="preview-loading">Failed to render preview.</div>');
            });
        }

        function closePreview() {
            $('#previewOverlay').removeClass('show');
            $('#previewBody').empty();
        }

        function showToast(msg) {
            var t = document.getElementById('toastMsg');
            if (!t) {
                t = document.createElement('div');
                t.id = 'toastMsg';
                t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;box-shadow:0 10px 24px rgba(0,0,0,.25);z-index:9999;opacity:0;transition:opacity .25s;';
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
            var colspan = 1 + COLS.length;
            if (!currentData.length) {
                var colspan = 1 + COLS.length;
                tbody.append('<tr class="empty-row"><td colspan="' + colspan + '">No data found</td></tr>');
                return;
            }
            currentData.forEach(function(row) {
                var tr = $('<tr>');
                var printBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('action-btn btn-print-row')
                    .html('<i class="fa-solid fa-print"></i> PRINT')
                    .attr('title', 'Print')
                    .on('click', function() {
                        printRow(row);
                    });
                var viewBtn = $('<button>')
                    .attr('type', 'button')
                    .addClass('action-btn btn-view-row')
                    .html('<i class="fa-solid fa-eye"></i> VIEW')
                    .attr('title', 'Preview')
                    .on('click', function() {
                        viewRow(row);
                    });
                tr.append($('<td>').append(printBtn).append(viewBtn));
                COLS.forEach(function(c) {
                    var val = colValue(row, c);
                    tr.append($('<td>').text(val === null || val === undefined ? '' : val));
                });
                tbody.append(tr);
            });
        }

        function searchBooking() {
            var search = $('#bookingInput').val().trim();
            $('#searchBtn').prop('disabled', true).html('Searching...');
            $.ajax({
                    url: TAB_CONFIG[currentTab].endpoint,
                    data: { search: search },
                    dataType: 'json',
                    method: 'GET'
                })
.done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        var colspan = 1 + COLS.length;
                        $('#tableBody').html('<tr class="empty-row"><td colspan="' + colspan + '">No data found</td></tr>');
                    }
                })
                .fail(function() {
                    var colspan = 1 + COLS.length;
                    $('#tableBody').html('<tr class="empty-row"><td colspan="' + colspan + '" style="color:#dc2626;">Error searching</td></tr>');
                })
                .always(function() {
                    $('#searchBtn').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Search');
                });
        }

        function loadAll() {
            $('#tableBody').html('<tr><td colspan="' + (1 + COLS.length) + '" class="loading-cell">Loading data...</td></tr>');
            $.ajax({
                    url: TAB_CONFIG[currentTab].endpoint,
                    dataType: 'json',
                    method: 'GET'
                })
                .done(function(resp) {
                    if (resp && resp.success) {
                        renderTableRows(resp.data);
                    } else {
                        var colspan = 1 + COLS.length;
                        $('#tableBody').html('<tr class="empty-row"><td colspan="' + colspan + '">No data returned</td></tr>');
                    }
                })
                .fail(function() {
                    var colspan = 1 + COLS.length;
                    $('#tableBody').html('<tr class="empty-row"><td colspan="' + colspan + '" style="color:#dc2626;">Error loading data</td></tr>');
                });
        }

        $(function() {
            $('#backBtn').on('click', function() {
                window.location.href = 'report.php';
            });
            $('#searchBtn').on('click', searchBooking);
            $('#clearBtn').on('click', function() {
                $('#bookingInput').val('');
                loadAll();
            });
            $('#bookingInput').on('keypress', function(e) {
                if (e.which === 13) searchBooking();
            });

            // Preview modal close handlers
            $('#previewClose').on('click', closePreview);
            $('#previewOverlay').on('click', function(e) {
                if (e.target === this) closePreview();
            });
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') closePreview();
            });

            $('.tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                if (tab !== currentTab) {
                    setActiveTab(tab);
                }
            });

            setActiveTab('receive');
        });
    
