(function () {
    'use strict';

    var state = {
        selectedId: null,
        originalValues: null,
        draftValues: null,
        dirty: false,
        page: 1,
        perPage: 50,
        searches: {},
        paymentFilter: 'all',
        priceReferenceFilter: 'all',
        sortColumn: null,
        sortDirection: null,
        loading: false,
        listings: [],
        total: 0,
        lastPage: 1,
    };

    var app = document.getElementById('salesListingApp');
    if (!app) return;

    var tbody = app.querySelector('[data-sl-tbody]');
    var notice = app.querySelector('[data-sl-notice]');
    var loading = app.querySelector('[data-sl-loading]');
    var paginationEl = app.querySelector('[data-sl-pagination]');
    var saveBtn = app.querySelector('[data-save-btn]');
    var modalOverlay = document.querySelector('[data-modal-overlay]');
    var debounceTimers = {};

    function getDataUrl() {
        var params = new URLSearchParams();
        params.set('page', state.page);
        params.set('per_page', state.perPage);
        params.set('payment_filter', state.paymentFilter);
        params.set('price_filter', state.priceReferenceFilter);
        params.set('sort', state.sortColumn || '');
        params.set('direction', state.sortDirection || '');

        for (var key in state.searches) {
            if (state.searches[key]) {
                params.set('search[' + key + ']', state.searches[key]);
            }
        }

        return route('admin.sales-listing.data') + '?' + params.toString();
    }

    function getMetricsUrl() {
        return route('admin.sales-listing.metrics');
    }

    function getUpdateUrl(id) {
        return route('admin.sales-listing.update', { salesListing: id });
    }

    function route(name, params) {
        var base = window.location.pathname.replace(/\/admin\/sales-listing.*$/, '');
        var routes = {
            'admin.sales-listing.data': base + '/admin/sales-listing/data',
            'admin.sales-listing.metrics': base + '/admin/sales-listing/metrics',
            'admin.sales-listing.update': base + '/admin/sales-listing/' + (params ? params.salesListing : '0'),
        };
        return routes[name] || base + '/admin/sales-listing';
    }

    loadMetrics();
    loadSalesListings();

    function loadMetrics() {
        fetch(getMetricsUrl(), {
            headers: { 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            renderMetrics(data);
        })
        .catch(function () {});
    }

    function renderMetrics(data) {
        var paidEl = app.querySelector('[data-metric="paid"]');
        var unpaidEl = app.querySelector('[data-metric="unpaid"]');
        var overdueEl = app.querySelector('[data-metric="overdue"]');

        if (paidEl) paidEl.textContent = data.paid || 0;
        if (unpaidEl) unpaidEl.textContent = data.unpaid || 0;
        if (overdueEl) overdueEl.textContent = data.overdue || 0;
    }

    function loadSalesListings() {
        if (state.loading) return;
        state.loading = true;
        loading.removeAttribute('hidden');

        fetch(getDataUrl(), {
            headers: { 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            state.listings = data.listings || [];
            state.total = data.total || 0;
            state.lastPage = data.last_page || 1;
            renderSalesListings();
            renderPagination();
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="14" class="sl-empty-cell">Failed to load data. Please refresh.</td></tr>';
        })
        .finally(function () {
            state.loading = false;
            loading.setAttribute('hidden', '');
        });
    }

    function renderSalesListings() {
        if (state.listings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="14" class="sl-empty-cell">No sales listings found.</td></tr>';
            return;
        }

        var html = '';

        for (var i = 0; i < state.listings.length; i++) {
            var item = state.listings[i];
            var rowClass = getRowClass(item);

            html += '<tr class="' + rowClass + '" data-listing-id="' + item.id + '" data-sales-order-id="' + item.sales_order_id + '">';
            html += '<td class="sl-table__readonly"><strong>SO: ' + escapeHtml(item.so_no) + '</strong><br><span style="font-size:11px;">DR: --</span></td>';
            html += '<td><input type="date" class="sl-table__editable" data-field="billing_date" value="' + (item.billing_date || '') + '" disabled></td>';
            html += '<td class="sl-table__readonly" data-vat-ex>' + (item.transaction_type === 'vat_ex' ? '&#8369;' + formatNumber(item.vat_exclusive_total) : '--') + '</td>';
            html += '<td class="sl-table__readonly" data-vat-inc>' + (item.transaction_type !== 'vat_ex' ? '&#8369;' + formatNumber(item.total_with_vat) : '--') + '</td>';
            html += '<td>';
            html += '<select class="sl-table__editable" data-field="transaction_type" disabled>';
            html += '<option value="">-- Select --</option>';
            html += '<option value="vat_ex"' + (item.transaction_type === 'vat_ex' ? ' selected' : '') + '>VAT Ex.</option>';
            html += '<option value="vat_inc"' + (item.transaction_type === 'vat_inc' ? ' selected' : '') + '>VAT Inc.</option>';
            html += '</select></td>';
            html += '<td class="sl-table__readonly">' + escapeHtml(item.customer_name) + '</td>';
            html += '<td><input type="text" class="sl-table__editable" data-field="po_no" value="' + escapeHtml(item.po_no || '') + '" disabled></td>';
            html += '<td><input type="text" class="sl-table__editable" data-field="sales_invoice_no" value="' + escapeHtml(item.sales_invoice_no || '') + '" disabled></td>';
            html += '<td><input type="text" class="sl-table__editable" data-field="quotation_no" value="' + escapeHtml(item.quotation_no || '') + '" disabled></td>';
            html += '<td class="sl-table__readonly">' + escapeHtml(item.sales_agent) + '</td>';
            html += '<td>';
            html += '<select class="sl-table__editable" data-field="initial_payment_status" disabled>';
            html += '<option value="paid"' + (item.initial_payment_status === 'paid' ? ' selected' : '') + '>Paid</option>';
            html += '<option value="unpaid"' + (item.initial_payment_status === 'unpaid' ? ' selected' : '') + '>Unpaid</option>';
            html += '</select></td>';
            html += '<td>';
            html += '<select class="sl-table__editable" data-field="final_payment_status" disabled>';
            html += '<option value="paid"' + (item.final_payment_status === 'paid' ? ' selected' : '') + '>Paid</option>';
            html += '<option value="unpaid"' + (item.final_payment_status === 'unpaid' ? ' selected' : '') + '>Unpaid</option>';
            html += '</select></td>';
            html += '<td><input type="text" class="sl-table__editable" data-field="actual_payment_remarks" value="' + escapeHtml(item.actual_payment_remarks || '') + '" disabled></td>';
            html += '<td>';
            html += '<select class="sl-table__editable" data-field="sales_channel" disabled>';
            html += '<option value="">-- Select --</option>';
            html += '<option value="Caloocan"' + (item.sales_channel === 'Caloocan' ? ' selected' : '') + '>Caloocan</option>';
            html += '<option value="Laguna"' + (item.sales_channel === 'Laguna' ? ' selected' : '') + '>Laguna</option>';
            html += '</select></td>';
            html += '</tr>';
        }

        tbody.innerHTML = html;

        tbody.querySelectorAll('tr[data-listing-id]').forEach(function (row) {
            row.addEventListener('mousedown', function (e) {
                var tag = e.target.tagName;
                var isFormControl = tag === 'INPUT' || tag === 'SELECT';
                if (isFormControl && row.classList.contains('is-selected')) return;
                handleRowClick(row);
            });
        });

        tbody.querySelectorAll('.sl-table__editable').forEach(function (input) {
            input.addEventListener('change', function () {
                handleFieldChange(input);
            });
        });

        if (state.selectedId) {
            var selectedRow = tbody.querySelector('tr[data-listing-id="' + state.selectedId + '"]');
            if (selectedRow) {
                selectRow(selectedRow);
            } else {
                clearRowSelection();
            }
        }
    }

    function getRowClass(item) {
        if (state.selectedId === item.id) return '';

        if (item.final_payment_status === 'paid') return 'is-paid';

        if (item.price_reference === 'green') return 'is-green';

        if (item.price_reference === 'yellow') return 'is-yellow';

        return '';
    }

    function handleRowClick(row) {
        var id = parseInt(row.getAttribute('data-listing-id'));

        if (state.selectedId === id) {
            if (!state.dirty) {
                clearRowSelection();
                return;
            }
        }

        if (state.selectedId !== null && state.dirty) {
            if (!confirm('You have unsaved changes. Discard changes?')) {
                return;
            }
        }

        if (state.selectedId !== null) {
            var prevRow = tbody.querySelector('tr[data-listing-id="' + state.selectedId + '"]');
            if (prevRow) {
                restoreRowBusinessColor(prevRow);
            }
        }

        clearRowSelection();
        selectRow(row);
    }

    function selectRow(row) {
        var id = parseInt(row.getAttribute('data-listing-id'));
        state.selectedId = id;

        row.classList.add('is-selected');
        enableSelectedRowControls(row);
        captureOriginalValues(row);

        state.draftValues = copyValues(state.originalValues);
        state.dirty = false;
        updateSaveButton();
    }

    function clearRowSelection() {
        state.selectedId = null;
        state.originalValues = null;
        state.draftValues = null;
        state.dirty = false;
        updateSaveButton();

        tbody.querySelectorAll('tr.is-selected').forEach(function (r) {
            r.classList.remove('is-selected');
            disableRowControls(r);
        });
    }

    function enableSelectedRowControls(row) {
        row.querySelectorAll('.sl-table__editable').forEach(function (el) {
            el.removeAttribute('disabled');
        });
    }

    function disableRowControls(row) {
        row.querySelectorAll('.sl-table__editable').forEach(function (el) {
            el.setAttribute('disabled', '');
        });
    }

    function captureOriginalValues(row) {
        var values = {};
        row.querySelectorAll('[data-field]').forEach(function (el) {
            values[el.getAttribute('data-field')] = el.value;
        });
        state.originalValues = values;
    }

    function copyValues(obj) {
        var copy = {};
        for (var key in obj) {
            if (obj.hasOwnProperty(key)) copy[key] = obj[key];
        }
        return copy;
    }

    function handleFieldChange(input) {
        if (state.selectedId === null) return;

        var field = input.getAttribute('data-field');
        var row = input.closest('tr[data-listing-id]');

        if (field === 'transaction_type') {
            updateVatDisplay(row, input.value);
        }

        if (state.draftValues) {
            state.draftValues[field] = input.value;
        }

        calculateDirtyState();
    }

    function updateVatDisplay(row, transactionType) {
        var vatExCell = row.querySelector('[data-vat-ex]');
        var vatIncCell = row.querySelector('[data-vat-inc]');

        if (!vatExCell || !vatIncCell) return;

        var listingId = parseInt(row.getAttribute('data-listing-id'));
        var item = findListing(listingId);
        if (!item) return;

        if (transactionType === 'vat_ex') {
            vatExCell.innerHTML = '&#8369;' + formatNumber(item.vat_exclusive_total);
            vatIncCell.innerHTML = '--';
        } else {
            vatExCell.innerHTML = '--';
            vatIncCell.innerHTML = '&#8369;' + formatNumber(item.total_with_vat);
        }
    }

    function findListing(id) {
        for (var i = 0; i < state.listings.length; i++) {
            if (state.listings[i].id === id) return state.listings[i];
        }
        return null;
    }

    function calculateDirtyState() {
        if (!state.originalValues || !state.draftValues) {
            state.dirty = false;
            updateSaveButton();
            return;
        }

        var dirty = false;
        for (var key in state.originalValues) {
            if (state.originalValues.hasOwnProperty(key)) {
                var orig = (state.originalValues[key] || '').toString();
                var draft = (state.draftValues[key] || '').toString();
                if (orig !== draft) {
                    dirty = true;
                    break;
                }
            }
        }

        state.dirty = dirty;
        updateSaveButton();
    }

    function updateSaveButton() {
        saveBtn.disabled = !(state.selectedId !== null && state.dirty);
    }

    function restoreRowBusinessColor(row) {
        var id = parseInt(row.getAttribute('data-listing-id'));
        var item = findListing(id);

        row.className = '';
        if (item) {
            var cls = getRowClass(item);
            if (cls) row.classList.add(cls);
        }
    }

    saveBtn.addEventListener('click', function () {
        if (saveBtn.disabled) return;
        openSaveConfirmation();
    });

    function openSaveConfirmation() {
        var item = findListing(state.selectedId);
        if (!item) return;

        var changes = [];
        for (var key in state.draftValues) {
            if (state.draftValues.hasOwnProperty(key)) {
                var orig = (state.originalValues[key] || '').toString();
                var draft = (state.draftValues[key] || '').toString();
                if (orig !== draft) {
                    changes.push({
                        field: key.replace(/_/g, ' '),
                        oldValue: orig,
                        newValue: draft,
                    });
                }
            }
        }

        if (changes.length === 0) {
            state.dirty = false;
            updateSaveButton();
            return;
        }

        var changesHtml = '';
        for (var i = 0; i < changes.length; i++) {
            changesHtml += '<div class="change-item">';
            changesHtml += '<span class="change-field">' + changes[i].field + '</span>';
            changesHtml += '<span><span class="change-old">' + escapeHtml(changes[i].oldValue || '(empty)') + '</span> &rarr; <span class="change-new">' + escapeHtml(changes[i].newValue || '(empty)') + '</span></span>';
            changesHtml += '</div>';
        }

        showModal(
            'Confirm Sales Listing Changes',
            '<p><strong>Sales Order:</strong> ' + escapeHtml(item.so_no) + '</p>' +
            '<p style="margin-top:8px;font-weight:600;">Changed fields:</p>' +
            changesHtml,
            [
                { label: 'Cancel', class: 'sl-modal__btn--cancel', action: hideModal },
                { label: 'Confirm Save', class: 'sl-modal__btn--confirm', action: function () { hideModal(); submitSalesListingUpdate(); } },
            ]
        );
    }

    function submitSalesListingUpdate() {
        var payload = {
            billing_date: state.draftValues.billing_date || null,
            transaction_type: state.draftValues.transaction_type || null,
            po_no: state.draftValues.po_no || null,
            sales_invoice_no: state.draftValues.sales_invoice_no || null,
            quotation_no: state.draftValues.quotation_no || null,
            initial_payment_status: state.draftValues.initial_payment_status || 'unpaid',
            final_payment_status: state.draftValues.final_payment_status || 'unpaid',
            actual_payment_remarks: state.draftValues.actual_payment_remarks || null,
            sales_channel: state.draftValues.sales_channel || null,
        };

        loading.removeAttribute('hidden');

        fetch(getUpdateUrl(state.selectedId), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(payload),
        })
        .then(function (r) {
            return r.json().then(function (data) { return { status: r.status, data: data }; });
        })
        .then(function (result) {
            loading.setAttribute('hidden', '');

            if (result.status >= 200 && result.status < 300) {
                showSuccessModal(result.data.listing.so_no);
                loadSalesListings();
                if (result.data.metrics) renderMetrics(result.data.metrics);
                clearRowSelection();
            } else {
                showNotice(result.data.message || 'Failed to update.', 'error');
            }
        })
        .catch(function () {
            loading.setAttribute('hidden', '');
            showNotice('Network error. Please try again.', 'error');
        });
    }

    function showSuccessModal(soNo) {
        showModal(
            'Success',
            '<p>Sales Listing Updated Successfully</p><p><strong>Sales Order:</strong> ' + escapeHtml(soNo) + '</p>',
            [
                { label: 'OK', class: 'sl-modal__btn--confirm', action: hideModal },
            ]
        );
    }

    function showModal(title, bodyHtml, buttons) {
        modalOverlay.removeAttribute('hidden');
        modalOverlay.querySelector('[data-modal-title]').textContent = title;
        modalOverlay.querySelector('[data-modal-body]').innerHTML = bodyHtml;

        var footer = modalOverlay.querySelector('[data-modal-footer]');
        footer.innerHTML = '';
        for (var i = 0; i < buttons.length; i++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sl-modal__btn ' + buttons[i].class;
            btn.textContent = buttons[i].label;
            btn.addEventListener('click', buttons[i].action);
            footer.appendChild(btn);
        }
    }

    function hideModal() {
        modalOverlay.setAttribute('hidden', '');
    }

    if (modalOverlay) {
        var closeBtn = modalOverlay.querySelector('[data-modal-close]');
        if (closeBtn) closeBtn.addEventListener('click', hideModal);
        modalOverlay.addEventListener('click', function (e) {
            if (e.target === modalOverlay) hideModal();
        });
    }

    function showNotice(message, type) {
        notice.textContent = message;
        notice.className = 'sl-notice sl-notice--' + type;
        notice.removeAttribute('hidden');
        setTimeout(function () { notice.setAttribute('hidden', ''); }, 5000);
    }

    document.querySelectorAll('[data-payment-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-payment-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            state.paymentFilter = btn.getAttribute('data-payment-filter');
            state.page = 1;
            clearRowSelection();
            loadSalesListings();
        });
    });

    document.querySelectorAll('[data-price-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-price-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            state.priceReferenceFilter = btn.getAttribute('data-price-filter');
            state.page = 1;
            clearRowSelection();
            loadSalesListings();
        });
    });

    document.querySelectorAll('[data-col-search]').forEach(function (input) {
        input.addEventListener('input', function () {
            var column = input.getAttribute('data-col-search');
            var value = input.value;

            if (debounceTimers[column]) clearTimeout(debounceTimers[column]);

            debounceTimers[column] = setTimeout(function () {
                if (value) {
                    state.searches[column] = value;
                } else {
                    delete state.searches[column];
                }
                state.page = 1;
                clearRowSelection();
                loadSalesListings();
            }, 300);
        });
    });

    document.querySelectorAll('[data-sort]').forEach(function (th) {
        th.addEventListener('click', function () {
            var column = th.getAttribute('data-sort');

            if (state.sortColumn !== column) {
                state.sortColumn = column;
                state.sortDirection = 'asc';
            } else {
                if (state.sortDirection === 'asc') {
                    state.sortDirection = 'desc';
                } else if (state.sortDirection === 'desc') {
                    state.sortColumn = null;
                    state.sortDirection = null;
                }
            }

            clearRowSelection();
            loadSalesListings();
            updateSortIcons();
        });
    });

    function updateSortIcons() {
        document.querySelectorAll('[data-sort]').forEach(function (th) {
            var icon = th.querySelector('.sl-sort-icon');
            var col = th.getAttribute('data-sort');
            if (col === state.sortColumn) {
                icon.textContent = state.sortDirection === 'asc' ? '\u25B2' : '\u25BC';
            } else {
                icon.textContent = '';
            }
        });
    }

    function renderPagination() {
        if (state.lastPage <= 1) {
            paginationEl.innerHTML = state.total > 0
                ? '<span class="sl-page-info">Showing all ' + state.total + ' records</span>'
                : '';
            return;
        }

        var html = '';
        html += '<button type="button" class="sl-page-btn"' + (state.page <= 1 ? ' disabled' : '') + ' data-page="' + (state.page - 1) + '">&laquo; Prev</button>';
        html += '<span class="sl-page-info">Page ' + state.page + ' of ' + state.lastPage + ' (' + state.total + ' records)</span>';
        html += '<button type="button" class="sl-page-btn"' + (state.page >= state.lastPage ? ' disabled' : '') + ' data-page="' + (state.page + 1) + '">Next &raquo;</button>';

        paginationEl.innerHTML = html;

        paginationEl.querySelectorAll('[data-page]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var page = parseInt(btn.getAttribute('data-page'));
                if (page < 1 || page > state.lastPage || page === state.page) return;
                state.page = page;
                clearRowSelection();
                loadSalesListings();
            });
        });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(num) {
        if (num === null || num === undefined) return '0.00';
        return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
})();
