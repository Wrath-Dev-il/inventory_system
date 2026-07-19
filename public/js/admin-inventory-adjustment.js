(function() {
  'use strict';

  /* ── State ── */
  var BASE    = window.location.pathname.replace(/\/admin\/inventory\/adjustment.*/, '');
  var DATA_URL= BASE + '/admin/inventory/adjustment/data';
  var SAVE_URL= BASE + '/admin/inventory/adjustment/save';

  var products     = [];
  var filteredRows = [];
  var paginatedRows= [];

  var currentPage   = 1;
  var perPage       = 25;
  var activeTab     = 'all';
  var sortKey       = null;
  var sortAsc       = true;
  var searchFilters = {};

  /* Multi-item tracking */
  var pendingAdjustments = {};  /* { id: { id, original_qty, new_qty, previous_qty, row } } */

  /* DOM refs */
  var $       = function(s, ctx) { return (ctx || document).querySelector(s); };
  var $$      = function(s, ctx) { return Array.from((ctx || document).querySelectorAll(s)); };

  var container = document.querySelector('[data-inv-adjustment]');
  if (!container) return;

  var tbody      = $('#invTableBody');
  var pageInfo   = $('#invPageInfo');
  var pageLinks  = $('#invPageLinks');
  var statsEls   = { total:$('#statTotal'), high:$('#statHigh'), low:$('#statLow') };
  var noticeEl   = $('#invNotice');

  /* Sticky bar elements */
  var stickyBar = $('#invStickyBar');
  var pendingCountEl = $('#invPendingCount');
  var saveBtn        = $('#invSaveBtn');

  /* ── Utility ── */
  function qs(key) {
    return encodeURIComponent(key);
  }

  /* ── Notice ── */
  function showNotice(type, msg) {
    noticeEl.className = 'inv-notice inv-notice--' + type;
    noticeEl.textContent = msg;
    noticeEl.style.display = 'block';
  }
  function hideNotice() {
    noticeEl.style.display = 'none';
    noticeEl.className = 'inv-notice';
  }

  /* ── Format number (for restock_level and other decimal fields) ── */
  function fmt(n) {
    return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  /* ── Format QTY as integer ── */
  function fmtQty(n) {
    return Math.trunc(Number(n)).toLocaleString('en-US');
  }

  /* ── Fetch data ── */
  function loadProducts() {
    fetch(DATA_URL)
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.products) {
          products = res.products;
          updateStats(res.stats);
          applyFiltersAndRender();
        }
      })
      .catch(function() { /* noop */ });
  }

  function updateStats(s) {
    if (!s) return;
    statsEls.total.textContent = fmt(s.total_products).replace(/\.00$/, '');
    statsEls.high.textContent  = fmt(s.high_stocks).replace(/\.00$/, '');
    statsEls.low.textContent   = fmt(s.low_stocks).replace(/\.00$/, '');
  }

  /* ── Filter / Sort / Paginate ── */
  function applyFiltersAndRender() {
    var data = products;

    /* tab filter */
    if (activeTab === 'high') {
      data = data.filter(function(p) { return Number(p.qty) >= Number(p.restock_level); });
    } else if (activeTab === 'low') {
      data = data.filter(function(p) { return Number(p.qty) < Number(p.restock_level); });
    }

    /* search filters */
    Object.keys(searchFilters).forEach(function(key) {
      var val = searchFilters[key].toLowerCase().trim();
      if (!val) return;
      data = data.filter(function(p) {
        var cell = (p[key] != null ? String(p[key]) : '');
        return cell.toLowerCase().indexOf(val) !== -1;
      });
    });

    /* sort */
    if (sortKey) {
      data.sort(function(a, b) {
        var av = a[sortKey], bv = b[sortKey];
        if (av == null) av = '';
        if (bv == null) bv = '';
        var numA = parseFloat(av), numB = parseFloat(bv);
        if (!isNaN(numA) && !isNaN(numB)) {
          return sortAsc ? numA - numB : numB - numA;
        }
        av = String(av).toLowerCase();
        bv = String(bv).toLowerCase();
        return sortAsc ? (av < bv ? -1 : av > bv ? 1 : 0) : (bv < av ? -1 : bv > av ? 1 : 0);
      });
    }

    filteredRows = data;
    currentPage = 1;
    renderPage();
  }

  function renderPage() {
    var start = (currentPage - 1) * perPage;
    var end   = start + perPage;
    paginatedRows = filteredRows.slice(start, end);

    renderRows();
    renderPagination();

    updateStickyBar();
  }

  /* ── Render rows ── */
  function renderRows() {
    if (!paginatedRows.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="inv-empty">No products found.</td></tr>';
      return;
    }

    var html = '';
    paginatedRows.forEach(function(p) {
      var pend  = pendingAdjustments[p.id];
      var isEdited = !!pend;
      var displayQty = isEdited ? pend.new_qty : p.qty;
      var origQty     = p.qty;
      var editedClass = isEdited ? 'is-edited' : '';
      var oldHtml     = isEdited ? '<span class="inv-old-qty">' + fmtQty(origQty) + '</span>' : '';

      html += '<tr data-id="' + p.id + '" class="' + (isEdited ? 'inv-row--edited' : '') + '">'
        + '<td>' + esc(p.item_no) + '</td>'
        + '<td>' + esc(p.product) + '</td>'
        + '<td>' + esc(p.brand) + '</td>'
        + '<td>' + esc(p.unit) + '</td>'
        + '<td>' + fmt(p.restock_level) + '</td>'
        + '<td class="inv-qty-cell ' + editedClass + '" data-id="' + p.id + '" data-original="' + origQty + '" data-edited-label="EDITED">'
        +   oldHtml
        +   '<span class="inv-qty-val">' + fmtQty(displayQty) + '</span>'
        + '</td>'
        + '</tr>';
    });
    tbody.innerHTML = html;

    /* Attach click listeners to quantity cells */
    $$('.inv-qty-cell', tbody).forEach(function(cell) {
      cell.addEventListener('click', onQtyClick);
    });
  }

  function esc(str) {
    if (str == null) return '';
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }

  /* ── Pagination ── */
  function renderPagination() {
    var total   = filteredRows.length;
    var pages   = Math.ceil(total / perPage) || 1;
    if (currentPage > pages) currentPage = pages;

    pageInfo.textContent = 'Showing ' + ((currentPage-1)*perPage+1) + ' to ' + Math.min(currentPage*perPage, total) + ' of ' + total;

    var html  = '';
    var startPage = Math.max(1, currentPage - 2);
    var endPage   = Math.min(pages, currentPage + 2);

    html += '<button class="inv-page-btn" data-page="prev" ' + (currentPage <= 1 ? 'disabled' : '') + '>&#9664;</button>';

    if (startPage > 1) {
      html += '<button class="inv-page-btn" data-page="1">1</button>';
      if (startPage > 2) html += '<button class="inv-page-btn" disabled>...</button>';
    }

    for (var i = startPage; i <= endPage; i++) {
      html += '<button class="inv-page-btn' + (i === currentPage ? ' is-active' : '') + '" data-page="' + i + '">' + i + '</button>';
    }

    if (endPage < pages) {
      if (endPage < pages - 1) html += '<button class="inv-page-btn" disabled>...</button>';
      html += '<button class="inv-page-btn" data-page="' + pages + '">' + pages + '</button>';
    }

    html += '<button class="inv-page-btn" data-page="next" ' + (currentPage >= pages ? 'disabled' : '') + '>&#9654;</button>';
    pageLinks.innerHTML = html;

    $$('[data-page]', pageLinks).forEach(function(btn) {
      btn.addEventListener('click', function() {
        var pg = this.getAttribute('data-page');
        if (pg === 'prev') changePage(currentPage - 1);
        else if (pg === 'next') changePage(currentPage + 1);
        else changePage(parseInt(pg, 10));
      });
    });
  }

  function changePage(p) {
    if (p < 1 || p > Math.ceil(filteredRows.length / perPage)) return;
    currentPage = p;
    renderPage();
    container.scrollIntoView({ behavior:'smooth', block:'start' });
  }

  /* ── Tab switching ── */
  function initTabs() {
    $$('.inv-tab').forEach(function(tab) {
      tab.addEventListener('click', function() {
        $$('.inv-tab').forEach(function(t) { t.classList.remove('is-active'); t.setAttribute('aria-selected','false'); });
        this.classList.add('is-active'); this.setAttribute('aria-selected','true');
        activeTab = this.getAttribute('data-tab');
        applyFiltersAndRender();
      });
    });
  }

  /* ── Sorting ── */
  function initSort() {
    $$('.inv-th-sort').forEach(function(th) {
      th.addEventListener('click', function() {
        var key = this.getAttribute('data-sort');
        if (sortKey === key) { sortAsc = !sortAsc; } else { sortKey = key; sortAsc = true; }
        $$('.inv-th-sort').forEach(function(t) { t.classList.remove('is-asc','is-desc'); });
        this.classList.add(sortAsc ? 'is-asc' : 'is-desc');
        applyFiltersAndRender();
      });
    });
  }

  /* ── Search ── */
  function initSearch() {
    $$('[data-search]', container).forEach(function(input) {
      input.addEventListener('input', function() {
        var key = this.getAttribute('data-search');
        searchFilters[key] = this.value;
        applyFiltersAndRender();
      });
    });
  }

  /* ── Pending Adjustments ── */
  function trackPendingAdjustment(id, originalQty, newQty, rowEl) {
    pendingAdjustments[id] = {
      id: id,
      original_qty: originalQty,
      new_qty: newQty,
      previous_qty: originalQty,
      row: rowEl
    };
    updateStickyBar();
  }

  function removePendingAdjustment(id) {
    delete pendingAdjustments[id];
    updateStickyBar();
  }

  function getPendingCount() {
    return Object.keys(pendingAdjustments).length;
  }

  function hasPendingChanges() {
    return getPendingCount() > 0;
  }

  function updateStickyBar() {
    var count = getPendingCount();
    if (count > 0) {
      pendingCountEl.textContent = count + ' item' + (count !== 1 ? 's' : '') + ' pending';
      pendingCountEl.className = 'inv-sticky-bar__count has-pending';
      stickyBar.classList.add('is-visible');
      saveBtn.disabled = false;
    } else {
      pendingCountEl.textContent = 'No pending changes';
      pendingCountEl.className = 'inv-sticky-bar__count';
      stickyBar.classList.remove('is-visible');
      saveBtn.disabled = true;
    }
  }

  /* ── Click-to-edit quantity ── */
  function onQtyClick(e) {
    var cell = e.currentTarget;
    if (cell.querySelector('.inv-qty-input')) return;

    var id        = cell.getAttribute('data-id');
    var original  = parseFloat(cell.getAttribute('data-original')) || 0;
    var currentVal = pendingAdjustments[id] ? pendingAdjustments[id].new_qty : original;
    var committed = false;

    cell.innerHTML = '<input type="number" class="inv-qty-input" step="1" value="' + currentVal + '" autofocus>';

    var input = cell.querySelector('.inv-qty-input');
    input.select();

    function commit() {
      if (committed) return;
      committed = true;

      var raw = input.value.trim();
      if (raw === '') { cancel(); return; }
      var val = parseFloat(raw);
      if (isNaN(val) || val < 0) { cancel(); return; }

      var row = cell.closest('tr');

      if (val === original) {
        removePendingAdjustment(id);
        row.classList.remove('inv-row--edited');
        cell.classList.remove('is-edited');
        cell.innerHTML = '<span class="inv-qty-val">' + fmtQty(original) + '</span>';
        cell.setAttribute('data-edited-label', 'EDITED');
      } else {
        trackPendingAdjustment(id, original, val, row);
        row.classList.add('inv-row--edited');
        cell.classList.add('is-edited');
        cell.innerHTML = '<span class="inv-old-qty">' + fmtQty(original) + '</span><span class="inv-qty-val">' + fmtQty(val) + '</span>';
        cell.setAttribute('data-edited-label', 'EDITED');
      }

      refreshAllRows();
    }

    function cancel() {
      if (committed) return;
      committed = true;
      var oldVal = pendingAdjustments[id] ? pendingAdjustments[id].new_qty : original;
      cell.innerHTML = '<span class="inv-qty-val">' + fmtQty(oldVal) + '</span>';
    }

    input.addEventListener('blur', function() {
      setTimeout(commit, 100);
    });
    input.addEventListener('keydown', function(ev) {
      if (ev.key === 'Enter') { ev.preventDefault(); commit(); }
      if (ev.key === 'Escape') { ev.preventDefault(); cancel(); }
    });
  }

  /* ── Refresh all visible rows to match pending state ── */
  function refreshAllRows() {
    $$('tr[data-id]', tbody).forEach(function(row) {
      var id = row.getAttribute('data-id');
      var cell = row.querySelector('.inv-qty-cell');
      if (!cell) return;

      var pend = pendingAdjustments[id];
      if (pend) {
        row.classList.add('inv-row--edited');
        cell.classList.add('is-edited');
        cell.innerHTML = '<span class="inv-old-qty">' + fmtQty(pend.original_qty) + '</span><span class="inv-qty-val">' + fmtQty(pend.new_qty) + '</span>';
        cell.setAttribute('data-edited-label', 'EDITED');
      } else {
        row.classList.remove('inv-row--edited');
        cell.classList.remove('is-edited');
        var orig = parseFloat(cell.getAttribute('data-original')) || 0;
        cell.innerHTML = '<span class="inv-qty-val">' + fmtQty(orig) + '</span>';
        cell.setAttribute('data-edited-label', 'EDITED');
      }
    });
  }

  /* ── Save Changes ── */
  function initSave() {
    saveBtn.addEventListener('click', function() {
      if (saveBtn.disabled) return;

      var adjustments = Object.keys(pendingAdjustments).map(function(id) {
        var p = pendingAdjustments[id];
        return { id: p.id, original_qty: p.original_qty, new_qty: p.new_qty };
      });

      if (!adjustments.length) return;

      saveBtn.disabled = true;
      saveBtn.innerHTML = '<span class="inv-save-spinner"></span> Saving...';
      hideNotice();

      fetch(SAVE_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ adjustments: adjustments })
      })
      .then(function(r) {
        return r.json().then(function(data) { return { status: r.status, data: data }; });
      })
      .then(function(result) {
        var status = result.status;
        var data   = result.data;

        if (status === 200) {
          showNotice('success', data.message || 'Adjustments saved successfully.');

          /* Update local product quantities */
          if (data.products) {
            data.products.forEach(function(u) {
              var prod = products.filter(function(p) { return p.id == u.id; });
              if (prod.length) { prod[0].qty = u.qty; }
            });
          }

          pendingAdjustments = {};
          applyFiltersAndRender();
          updateStats(data.stats);

          saveBtn.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none">' +
            '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' +
            '<path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' +
            '</svg><span>Save Changes</span>';
          saveBtn.disabled = true;
        } else {
          saveBtn.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none">' +
            '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' +
            '<path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' +
            '</svg><span>Save Changes</span>';

          if (status === 409) {
            /* Conflict — update quantities for conflicted items */
            var conflicts = data.conflicts || [];
            showNotice('conflict', 'Quantity conflicts detected for some items. Please review and re-edit.');

            conflicts.forEach(function(c) {
              var prod = products.filter(function(p) { return p.id == c.id; });
              if (prod.length) { prod[0].qty = c.current_qty; }

              /* Remove from pending so user can re-edit */
              delete pendingAdjustments[c.id];
            });
            applyFiltersAndRender();
            saveBtn.disabled = false;
          } else {
            showNotice('error', data.message || 'An error occurred while saving.');
            saveBtn.disabled = false;
          }
        }
      })
      .catch(function() {
        showNotice('error', 'A network error occurred. Please try again.');
        saveBtn.innerHTML =
          '<svg viewBox="0 0 24 24" fill="none">' +
          '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' +
          '<path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>' +
          '</svg><span>Save Changes</span>';
        saveBtn.disabled = false;
      });
    });
  }

  /* ── Init ── */
  loadProducts();
  initTabs();
  initSort();
  initSearch();
  initSave();
})();
