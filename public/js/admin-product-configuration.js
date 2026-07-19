(function () {
  'use strict';

  var baseUrl = window.location.pathname.replace(/\/admin\/product-configuration.*/, '');
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
  var pcNotice = document.getElementById('pcNotice');

  function showNotice(message, type) {
    if (!pcNotice) return;
    pcNotice.textContent = message;
    pcNotice.className = 'pc-notice pc-notice--' + type;
    pcNotice.hidden = false;
    setTimeout(function () { pcNotice.hidden = true; }, 5000);
  }

  /* ── Shared Item Source State ── */
  var itemSources = [];

  /* ── Render Item Sources List ── */
  function renderItemSourcesList() {
    var list = document.getElementById('pcSourceList');
    var empty = document.getElementById('pcSourceEmpty');
    if (!list) return;

    if (itemSources.length === 0) {
      list.innerHTML = '<p class="pc-empty" id="pcSourceEmpty">No item sources yet.</p>';
      return;
    }

    if (empty) empty.remove();

    var html = '';
    itemSources.forEach(function (s) {
      html += '<span class="pc-source-item">' +
        '<span class="pc-source-item__name">' + escapeHtml(s.name) + '</span>' +
        '<span class="pc-source-actions">' +
        '<button type="button" class="pc-source-btn pc-source-btn--edit" data-action="edit" data-id="' + s.id + '" data-name="' + escapeAttr(s.name) + '" title="Edit"><svg viewBox="0 0 24 24" fill="none"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></button>' +
        '<button type="button" class="pc-source-btn pc-source-btn--delete" data-action="delete" data-id="' + s.id + '" data-name="' + escapeAttr(s.name) + '" title="Delete"><svg viewBox="0 0 24 24" fill="none"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>' +
        '</span>' +
        '</span>';
    });
    list.innerHTML = html;

    list.querySelectorAll('[data-action="edit"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openEditModal(parseInt(this.getAttribute('data-id')), this.getAttribute('data-name'));
      });
    });
    list.querySelectorAll('[data-action="delete"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openDeleteModal(parseInt(this.getAttribute('data-id')), this.getAttribute('data-name'));
      });
    });
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function escapeAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  /* ── Add source to shared state and re-render ── */
  function addItemSourceToState(source) {
    var exists = itemSources.some(function (s) { return s.id === source.id; });
    if (exists) return;

    itemSources.push(source);
    itemSources.sort(function (a, b) { return a.name.localeCompare(b.name); });

    renderItemSourcesList();
    renderEquivTable();
  }

  function updateItemSourceInState(id, newName) {
    var found = false;
    itemSources.forEach(function (s) {
      if (s.id === id) {
        s.name = newName;
        found = true;
      }
    });
    if (!found) return;

    itemSources.sort(function (a, b) { return a.name.localeCompare(b.name); });
    renderItemSourcesList();
    renderEquivTable();
  }

  function removeItemSourceFromState(id) {
    itemSources = itemSources.filter(function (s) { return s.id !== id; });
    renderItemSourcesList();
    renderEquivTable();
  }

  /* ── Reload full source list from server ── */
  function reloadSources(callback) {
    fetch(baseUrl + '/admin/product-configuration/sources', {
      headers: { 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success && Array.isArray(data.data)) {
        itemSources = data.data;
        itemSources.sort(function (a, b) { return a.name.localeCompare(b.name); });
        renderItemSourcesList();
        renderEquivTable();
      }
      if (callback) callback();
    })
    .catch(function () {
      if (callback) callback();
    });
  }

  /* ── Item Source Form ── */
  var sourceForm = document.getElementById('pcSourceForm');
  var sourceInput = document.getElementById('pcSourceInput');
  var sourceBtn = document.getElementById('pcSourceBtn');

  sourceForm?.addEventListener('submit', function (e) {
    e.preventDefault();
    var name = sourceInput.value.trim();
    if (!name) return;

    sourceBtn.disabled = true;
    sourceBtn.innerHTML = '<span class="pc-spinner"></span>';

    fetch(baseUrl + '/admin/product-configuration/sources', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: JSON.stringify({ name: name })
    })
    .then(function (r) { return r.json().then(function (d) { d._status = r.status; return d; }); })
    .then(function (data) {
      if (data.success && data.data && data.data.item_source) {
        sourceInput.value = '';
        addItemSourceToState(data.data.item_source);
        showNotice(data.message || 'Item source added.', 'success');
      } else {
        var msg = data.message || 'Failed to add item source.';
        if (data.errors && data.errors.name) {
          msg = data.errors.name.join('; ');
        }
        showNotice(msg, 'error');
      }
    })
    .catch(function () { showNotice('Network error.', 'error'); })
    .finally(function () {
      sourceBtn.disabled = false;
      sourceBtn.textContent = 'Add Item Source';
    });
  });

  /* ── Modals ── */
  function openEditModal(id, name) {
    var btn = document.getElementById('pcEditBtn');
    btn.disabled = false;
    btn.textContent = 'Save Changes';
    document.getElementById('pcEditId').value = id;
    document.getElementById('pcEditName').value = name;
    document.getElementById('pcEditModalOverlay').hidden = false;
    document.getElementById('pcEditName').focus();
    document.getElementById('pcEditName').select();
    document.body.style.overflow = 'hidden';
  }

  function openDeleteModal(id, name) {
    var btn = document.getElementById('pcDeleteBtn');
    btn.disabled = false;
    btn.textContent = 'Delete';
    document.getElementById('pcDeleteId').value = id;
    document.getElementById('pcDeleteName').textContent = name;
    document.getElementById('pcDeleteModalOverlay').hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeAllModals() {
    document.getElementById('pcEditModalOverlay').hidden = true;
    document.getElementById('pcDeleteModalOverlay').hidden = true;
    document.body.style.overflow = '';
  }

  /* Close modals on overlay click */
  document.querySelectorAll('.pc-modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === this) closeAllModals();
    });
  });

  /* Close modals on Escape */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAllModals();
  });

  /* Edit modal handlers */
  document.getElementById('pcEditModalClose')?.addEventListener('click', closeAllModals);
  document.getElementById('pcEditCancel')?.addEventListener('click', closeAllModals);

  document.getElementById('pcEditForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    var id = parseInt(document.getElementById('pcEditId').value);
    var name = document.getElementById('pcEditName').value.trim();
    if (!name) return;

    var btn = document.getElementById('pcEditBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="pc-spinner"></span>';

    fetch(baseUrl + '/admin/product-configuration/sources/' + id, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: JSON.stringify({ name: name })
    })
    .then(function (r) { return r.json().then(function (d) { d._status = r.status; return d; }); })
    .then(function (data) {
      if (data.success) {
        updateItemSourceInState(id, name);
        closeAllModals();
        showNotice(data.message || 'Item source updated.', 'success');
      } else {
        var msg = data.message || 'Failed to update item source.';
        if (data.errors && data.errors.name) {
          msg = data.errors.name.join('; ');
        }
        showNotice(msg, 'error');
      }
    })
    .catch(function () { showNotice('Network error.', 'error'); })
    .finally(function () {
      btn.disabled = false;
      btn.textContent = 'Save Changes';
    });
  });

  /* Delete modal handlers */
  document.getElementById('pcDeleteModalClose')?.addEventListener('click', closeAllModals);
  document.getElementById('pcDeleteCancel')?.addEventListener('click', closeAllModals);

  document.getElementById('pcDeleteBtn')?.addEventListener('click', function () {
    var id = parseInt(document.getElementById('pcDeleteId').value);
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="pc-spinner"></span>';

    fetch(baseUrl + '/admin/product-configuration/sources/' + id, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        removeItemSourceFromState(id);
        closeAllModals();
        showNotice(data.message || 'Item source deleted.', 'success');
      } else {
        showNotice(data.message || 'Failed to delete item source.', 'error');
        btn.disabled = false;
        btn.textContent = 'Delete';
      }
    })
    .catch(function () { showNotice('Network error.', 'error'); btn.disabled = false; btn.textContent = 'Delete'; });
  });

  /* ── Converter State ── */
  var converterState = {
    rate: (function () {
      var btn = document.getElementById('pcRefreshRateBtn');
      return btn ? parseFloat(btn.getAttribute('data-pc-rate')) || 0 : 0;
    })()
  };

  /* ── Yuan to Peso Converter ── */
  var yuanInput = document.getElementById('pcYuanInput');
  var pesoOutput = document.getElementById('pcPesoOutput');

  yuanInput?.addEventListener('input', function () {
    var yuan = parseFloat(this.value) || 0;
    var peso = yuan * converterState.rate;
    pesoOutput.value = peso.toFixed(4);
  });

  /* ── Refresh Rate ── */
  var refreshBtn = document.getElementById('pcRefreshRateBtn');
  refreshBtn?.addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="pc-spinner"></span>';

    fetch(baseUrl + '/admin/product-configuration/rate/refresh', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        converterState.rate = parseFloat(data.rate) || 0;
        btn.setAttribute('data-pc-rate', converterState.rate);
        var statusDiv = document.getElementById('pcRateStatus');
        if (statusDiv) {
          statusDiv.innerHTML =
            '<div class="pc-rate-info">' +
            '<span class="pc-rate-label">1 CNY = <strong>&#8369;' + converterState.rate.toFixed(6) + '</strong></span>' +
            '<span class="pc-rate-source">' + (data.provider || '') + '</span>' +
            '<span class="pc-rate-time">Updated: ' + (data.retrieved_at || '') + '</span>' +
            '</div>';
        }
        showNotice(data.message || 'Rate refreshed.', 'success');
      } else {
        showNotice(data.message || 'Failed to refresh rate.', 'error');
      }
    })
    .catch(function () { showNotice('Network error.', 'error'); })
    .finally(function () {
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M4 12a8 8 0 0 1 15.57-3M22 12a8 8 0 0 1-15.57 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M18 5v4h-4M6 19v-4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Refresh Rate';
    });
  });

  /* ── Equivalency Table ── */
  function renderEquivTable() {
    var tbody = document.getElementById('pcEquivTableBody');
    if (!tbody) return;

    if (itemSources.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="pc-empty-cell">No item sources yet.</td></tr>';
      return;
    }

    var html = '';
    itemSources.forEach(function (s) {
      var mult = (s.current_equivalency && s.current_equivalency.multiplier > 0) ? s.current_equivalency.multiplier : '';
      var pesoPreview = parseFloat(mult || 0) * 1;
      html += '<tr>' +
        '<td class="pc-equiv-source-name">' + escapeHtml(s.name) + '</td>' +
        '<td><div class="pc-converter__input-wrap"><span class="pc-converter__prefix">&times;</span><input type="number" class="pc-input pc-converter__input pc-equiv-mult" data-sid="' + s.id + '" step="0.0001" min="0.0001" placeholder="0.0000" value="' + mult + '"></div></td>' +
        '<td><div class="pc-converter__input-wrap"><span class="pc-converter__prefix">&yen;</span><input type="number" class="pc-input pc-converter__input pc-equiv-yuan" data-sid="' + s.id + '" step="0.0001" min="0" value="1" placeholder="1.0000"></div></td>' +
        '<td><div class="pc-converter__input-wrap"><span class="pc-converter__prefix">&#8369;</span><input type="text" class="pc-input pc-converter__input--result pc-equiv-peso" data-sid="' + s.id + '" readonly value="' + pesoPreview.toFixed(4) + '"></div></td>' +
        '<td><button type="button" class="pc-btn pc-btn--primary pc-equiv-save" data-sid="' + s.id + '"' + (!mult ? ' disabled' : '') + '>Save</button></td>' +
        '</tr>';
    });
    tbody.innerHTML = html;

    tbody.querySelectorAll('.pc-equiv-mult').forEach(function (el) {
      el.addEventListener('input', function () { updateEquivRow(this); });
    });
    tbody.querySelectorAll('.pc-equiv-yuan').forEach(function (el) {
      el.addEventListener('input', function () { updateEquivRow(this); });
    });
    tbody.querySelectorAll('.pc-equiv-save').forEach(function (el) {
      el.addEventListener('click', function () { saveEquivRow(this); });
    });
  }

  function updateEquivRow(el) {
    var tr = el.closest('tr');
    if (!tr) return;
    var mult = parseFloat(tr.querySelector('.pc-equiv-mult').value) || 0;
    var yuan = parseFloat(tr.querySelector('.pc-equiv-yuan').value) || 0;
    tr.querySelector('.pc-equiv-peso').value = (yuan * mult).toFixed(4);
    var btn = tr.querySelector('.pc-equiv-save');
    if (btn) btn.disabled = !(mult > 0);
  }

  function saveEquivRow(btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="pc-spinner"></span>';

    var tr = btn.closest('tr');
    var sourceId = btn.getAttribute('data-sid');
    var mult = tr.querySelector('.pc-equiv-mult').value;
    var yuan = tr.querySelector('.pc-equiv-yuan').value;

    var payload = { item_source_id: sourceId, multiplier: mult };
    if (yuan && parseFloat(yuan) !== 1) {
      payload.yuan_amount = yuan;
    }

    fetch(baseUrl + '/admin/product-configuration/equivalencies', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        showNotice(data.message || 'Equivalency saved.', 'success');
        reloadSources(function () { loadLogs(1); });
      } else {
        showNotice(data.message || 'Failed to save equivalency.', 'error');
        btn.disabled = false;
        btn.textContent = 'Save';
      }
    })
    .catch(function () { showNotice('Network error.', 'error'); btn.disabled = false; btn.textContent = 'Save'; });
  }

  /* ── Logs Table ── */
  var logsBody = document.getElementById('pcLogsBody');
  var pageInfo = document.getElementById('pcPageInfo');
  var pageLinks = document.getElementById('pcPageLinks');

  var currentPage = 1;
  var currentSort = 'date';
  var currentDir = 'desc';

  function getSearchParams() {
    var params = {};
    document.querySelectorAll('[data-search]').forEach(function (input) {
      var val = input.value.trim();
      if (val) params[input.getAttribute('data-search')] = val;
    });
    return params;
  }

  function loadLogs(page) {
    currentPage = page || 1;
    var searchParams = getSearchParams();

    var qs = '?page=' + currentPage + '&sort=' + currentSort + '&dir=' + currentDir;
    Object.keys(searchParams).forEach(function (k) {
      qs += '&' + k + '=' + encodeURIComponent(searchParams[k]);
    });

    logsBody.innerHTML = '<tr><td colspan="5" class="pc-empty-cell">Loading...</td></tr>';

    fetch(baseUrl + '/admin/product-configuration/logs' + qs, {
      headers: { 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.data || !data.data.length) {
        logsBody.innerHTML = '<tr><td colspan="5" class="pc-empty-cell">No conversion logs found.</td></tr>';
        pageInfo.textContent = '';
        pageLinks.innerHTML = '';
        return;
      }

      var html = '';
      data.data.forEach(function (row) {
        html += '<tr>' +
          '<td>' + (row.item_source || '—') + '</td>' +
          '<td>&times; ' + parseFloat(row.multiplier).toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4}) + '</td>' +
          '<td>&yen; ' + parseFloat(row.yuan_amount).toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4}) + '</td>' +
          '<td>&#8369; ' + parseFloat(row.peso_amount).toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4}) + '</td>' +
          '<td>' + (row.logged_at || '—') + '</td>' +
          '</tr>';
      });
      logsBody.innerHTML = html;

      var pg = data.pagination || {};
      pageInfo.textContent = 'Page ' + (pg.current_page || '?') + ' of ' + (pg.last_page || '?') + ' (' + (pg.total || 0) + ' entries)';

      var linksHtml = '';
      if (pg.current_page > 1) {
        linksHtml += '<button class="pc-page-btn" data-page="' + (pg.current_page - 1) + '">Prev</button>';
      } else {
        linksHtml += '<button class="pc-page-btn" disabled>Prev</button>';
      }

      for (var i = 1; i <= (pg.last_page || 1); i++) {
        linksHtml += '<button class="pc-page-btn' + (i === pg.current_page ? ' is-active' : '') + '" data-page="' + i + '">' + i + '</button>';
      }

      if (pg.current_page < pg.last_page) {
        linksHtml += '<button class="pc-page-btn" data-page="' + (pg.current_page + 1) + '">Next</button>';
      } else {
        linksHtml += '<button class="pc-page-btn" disabled>Next</button>';
      }
      pageLinks.innerHTML = linksHtml;

      document.querySelectorAll('.pc-page-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var p = parseInt(this.getAttribute('data-page'));
          if (p) loadLogs(p);
        });
      });
    })
    .catch(function () {
      logsBody.innerHTML = '<tr><td colspan="5" class="pc-empty-cell">Error loading logs.</td></tr>';
    });
  }

  /* Sort */
  document.querySelectorAll('.pc-th-sort').forEach(function (th) {
    th.addEventListener('click', function () {
      var sort = this.getAttribute('data-sort');
      if (currentSort === sort) {
        currentDir = currentDir === 'asc' ? 'desc' : 'asc';
      } else {
        currentSort = sort;
        currentDir = 'asc';
      }

      document.querySelectorAll('.pc-th-sort').forEach(function (s) { s.classList.remove('is-asc', 'is-desc'); });
      this.classList.add('is-' + currentDir);

      loadLogs(1);
    });
  });

  /* Search inputs debounce */
  var searchTimers = {};
  document.querySelectorAll('[data-search]').forEach(function (input) {
    input.addEventListener('input', function () {
      var key = this.getAttribute('data-search');
      clearTimeout(searchTimers[key]);
      searchTimers[key] = setTimeout(function () { loadLogs(1); }, 400);
    });
  });

  /* ── Initialize shared source state from server ── */
  reloadSources(function () {
    loadLogs(1);
  });

})();
