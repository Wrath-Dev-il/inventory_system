document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('[data-sq-list]');
    if (!root) return;

    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var storeUrl = root.dataset.sqStoreUrl || '';
    var dataUrl = root.dataset.sqDataUrl || '';
    var customersUrl = root.dataset.sqCustomersUrl || '';
    var productsUrl = root.dataset.sqProductsUrl || '';
    var showUrlTemplate = root.dataset.sqShowUrlTemplate || '';
    var printUrlTemplate = root.dataset.sqPrintUrlTemplate || '';
    var logoUrl = root.dataset.sqLogoUrl || '';
    var VAT_RATE = 0.12;
    var VAT_MULTIPLIER = 1.12;

    var state = {
        selectedCustomer: null,
        selectedItems: [],
        selectedProductIds: [],
        currentStep: 1,
        editingQuotation: null,
    };

    var notice = root.querySelector('[data-sq-notice]');
    var createBtn = root.querySelector('[data-sq-create-button]');
    var modal = root.querySelector('[data-sq-modal]');
    var modalClose = root.querySelectorAll('[data-sq-modal-close]');
    var modalKicker = root.querySelector('[data-sq-modal-kicker]');
    var modalTitle = root.querySelector('[data-sq-modal-title]');
    var stepPanels = { 1: root.querySelector('[data-sq-step-panel="1"]'), 2: root.querySelector('[data-sq-step-panel="2"]'), 3: root.querySelector('[data-sq-step-panel="3"]') };
    var stepIndicators = { 1: root.querySelector('[data-sq-step="1"]'), 2: root.querySelector('[data-sq-step="2"]'), 3: root.querySelector('[data-sq-step="3"]') };
    var stepBack = root.querySelector('[data-sq-step-back]');
    var stepNext = root.querySelector('[data-sq-step-next]');
    var modalFooterClose = root.querySelector('.sq-modal__footer [data-sq-modal-close]');

    var customerDisplay = root.querySelector('[data-sq-customer-display]');
    var quotationNoDisplay = root.querySelector('[data-sq-no]');
    var quotationDateDisplay = root.querySelector('[data-sq-date]');
    var salesInput = root.querySelector('[data-sq-sales]');
    var remarksInput = root.querySelector('[data-sq-remarks]');
    var paymentTermsInput = root.querySelector('[data-sq-payment-terms]');
    var cancellationTermsInput = root.querySelector('[data-sq-cancellation-terms]');
    var deliveryTermsInput = root.querySelector('[data-sq-delivery-terms]');
    var leadTimeInput = root.querySelector('[data-sq-lead-time]');
    var validUntilInput = root.querySelector('[data-sq-valid-until]');
    var warrantyInput = root.querySelector('[data-sq-warranty]');
    var modeOfPaymentInput = root.querySelector('[data-sq-mode-of-payment]');
    var attentionToInput = root.querySelector('[data-sq-attention-to]');
    var priceRefDisplay = root.querySelector('[data-sq-price-ref-display]');
    var priceRefLabel = root.querySelector('[data-sq-price-ref-label]');

    var selectItemsBtn = root.querySelector('[data-sq-select-items]');
    var itemsContainer = root.querySelector('[data-sq-items-container]');
    var itemsSearch = root.querySelector('[data-sq-items-search]');
    var itemsTotal = root.querySelector('[data-sq-items-total]');

    var customerModal = root.querySelector('[data-sq-customer-modal]');
    var customerModalClose = root.querySelectorAll('[data-sq-customer-close]');
    var customerSearch = root.querySelector('[data-sq-customer-search]');
    var customerList = root.querySelector('[data-sq-customer-list]');
    var customerPagination = root.querySelector('[data-sq-customer-pagination]');
    var customerColSearches = root.querySelectorAll('[data-sq-customer-col-search]');

    var productModal = root.querySelector('[data-sq-product-modal]');
    var productModalClose = root.querySelectorAll('[data-sq-product-close]');
    var productSearch = root.querySelector('[data-sq-product-search]');
    var productList = root.querySelector('[data-sq-product-list]');
    var productPagination = root.querySelector('[data-sq-product-pagination]');
    var productColSearches = root.querySelectorAll('[data-sq-product-col-search]');
    var productApplyBtn = root.querySelector('[data-sq-product-apply]');

    var confirmModal = root.querySelector('[data-sq-confirm-modal]');
    var confirmClose = root.querySelectorAll('[data-sq-confirm-close]');
    var confirmTitle = root.querySelector('[data-sq-confirm-title]');
    var confirmMessage = root.querySelector('[data-sq-confirm-message]');
    var confirmProceed = root.querySelector('[data-sq-confirm-proceed]');

    var successModal = root.querySelector('[data-sq-success-modal]');
    var successClose = root.querySelectorAll('[data-sq-success-close]');
    var successTitle = root.querySelector('[data-sq-success-title]');
    var successMessage = root.querySelector('[data-sq-success-message]');

    var tbody = root.querySelector('[data-sq-tbody]');
    var pagination = root.querySelector('[data-sq-pagination]');

    function showNotice(msg, type) { notice.textContent = msg; notice.className = 'sq-notice sq-notice--' + type; notice.hidden = false; setTimeout(function () { notice.hidden = true; }, 5000); }
    function openModal(el) { el.hidden = false; document.body.style.overflow = 'hidden'; }
    function closeModal(el) { el.hidden = true; document.body.style.overflow = ''; }
    function fmt(n) { return '\u20B1' + Number(n).toFixed(2); }
    function escapeHtml(str) { var d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    function calcWithoutVat(price) { return Math.round((price / VAT_MULTIPLIER) * 100) / 100; }

    function calcItemPrice(item) { return Number(item.unit_price || 0); }
    function calcUnitPriceWithTax(item) { return Math.round(calcItemPrice(item) * VAT_MULTIPLIER * 100) / 100; }
    function calcTaxAmount(item) { return Math.round(calcItemPrice(item) * VAT_RATE * Number(item.quantity || 0) * 100) / 100; }
    function calcLineTotal(item) { return Math.round(calcUnitPriceWithTax(item) * Number(item.quantity || 0) * 100) / 100; }

    function calcSubtotal(items) { return items.reduce(function (s, item) { return s + Math.round(calcItemPrice(item) * Number(item.quantity || 0) * 100) / 100; }, 0); }
    function calcTax(items) { return items.reduce(function (s, item) { return s + calcTaxAmount(item); }, 0); }
    function calcGrandTotal(items) { return items.reduce(function (s, item) { return s + calcLineTotal(item); }, 0); }

    function reloadPage() { window.location.reload(); }

    // ========== MAIN TABLE ==========
    function loadTable(page, searchParams) {
        var params = new URLSearchParams();
        if (page) params.set('page', page);
        if (searchParams) {
            for (var key in searchParams) if (searchParams[key]) params.set('search[' + key + ']', searchParams[key]);
        }
        var searchQ = root.querySelector('[data-sq-search-input]');
        if (searchQ && searchQ.value) params.set('q', searchQ.value);

        fetch(dataUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                tbody.innerHTML = '';
                data.quotations.forEach(function (q) {
                    var tr = document.createElement('tr');
                    tr.dataset.sqRow = '';
                    tr.dataset.sqId = q.id;
                    tr.innerHTML =
                        '<td><strong>' + escapeHtml(q.quotation_no) + '</strong></td>' +
                        '<td>' + escapeHtml(q.customer_name_snapshot || '--') + '</td>' +
                        '<td>' + fmt(q.grand_total) + '</td>' +
                        '<td>' + escapeHtml(q.quotation_date || '--') + '</td>' +
                        '<td><span class="sq-badge sq-badge--' + (q.status || 'draft') + '">' + escapeHtml(q.status || 'draft') + '</span></td>' +
                        '<td>' + (q.terms_snapshot ? escapeHtml(q.terms_snapshot) : '--') + '</td>' +
                        '<td class="sq-actions-cell">' +
                            '<button type="button" class="sq-action-btn" data-sq-view><svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg><span>View</span></button>' +
                            '<button type="button" class="sq-action-btn" data-sq-print><svg viewBox="0 0 24 24" fill="none"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2"/><path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2"/></svg><span>Print</span></button>' +
                        '</td>';
                    tbody.appendChild(tr);
                });
                if (data.quotations.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="sq-table__empty">No quotations yet. Click "Create Quotation" to start.</td></tr>';
                }
                renderPagination(data, page, searchParams);
            });
    }

    function renderPagination(data, page, searchParams) {
        pagination.innerHTML = '';
        if (data.last_page <= 1) return;
        var html = '<p>Showing ' + ((data.current_page - 1) * data.per_page + 1) + '-' + Math.min(data.current_page * data.per_page, data.total) + ' of ' + data.total + '</p><div class="sq-pagination__links">';
        if (data.current_page > 1) html += '<button type="button" class="sq-pagination__link" data-sq-page="' + (data.current_page - 1) + '">Previous</button>';
        for (var i = 1; i <= data.last_page; i++) {
            html += '<button type="button" class="sq-pagination__link' + (i === data.current_page ? ' is-active' : '') + '" data-sq-page="' + i + '">' + i + '</button>';
        }
        if (data.current_page < data.last_page) html += '<button type="button" class="sq-pagination__link" data-sq-page="' + (data.current_page + 1) + '">Next</button>';
        html += '</div>';
        pagination.innerHTML = html;
        pagination.querySelectorAll('[data-sq-page]').forEach(function (btn) {
            btn.addEventListener('click', function () { loadTable(parseInt(this.dataset.sqPage), getSearchParams()); });
        });
    }

    function getSearchParams() {
        var params = {};
        root.querySelectorAll('[data-sq-col-search]').forEach(function (input) {
            if (input.value) params[input.name] = input.value;
        });
        return params;
    }

    // Column search
    root.querySelectorAll('[data-sq-col-search]').forEach(function (input) {
        input.addEventListener('change', function () { loadTable(1, getSearchParams()); });
    });
    var globalSearch = root.querySelector('[data-sq-search-input]');
    if (globalSearch) {
        globalSearch.addEventListener('change', function () { loadTable(1, getSearchParams()); });
    }

    // Sort
    root.querySelectorAll('[data-sort]').forEach(function (th) {
        th.addEventListener('click', function () {
            var field = this.dataset.sort;
            var url = new URL(window.location.href);
            var currentDir = url.searchParams.get('direction');
            if (url.searchParams.get('sort') === field) {
                if (currentDir === 'asc') url.searchParams.set('direction', 'desc');
                else if (currentDir === 'desc') { url.searchParams.delete('sort'); url.searchParams.delete('direction'); }
                else url.searchParams.set('direction', 'asc');
            } else {
                url.searchParams.set('sort', field);
                url.searchParams.set('direction', 'asc');
            }
            window.location.href = url.toString();
        });
    });

    // ========== MODAL WIZARD ==========
    function goToStep(step) {
        state.currentStep = step;
        for (var i = 1; i <= 3; i++) {
            if (stepPanels[i]) stepPanels[i].hidden = i !== step;
            if (stepIndicators[i]) {
                stepIndicators[i].className = 'sq-step';
                if (i < step) stepIndicators[i].classList.add('is-completed');
                else if (i === step) stepIndicators[i].classList.add('is-active');
            }
        }
        if (stepBack) stepBack.hidden = step === 1;
        if (stepNext) stepNext.textContent = step === 3 ? 'Create Quotation' : 'Next';
        updateStepControls();
    }

    function updateStepControls() {
        if (!stepNext || stepNext.hidden) return;
        if (state.currentStep === 1) {
            stepNext.disabled = !state.selectedCustomer;
        } else if (state.currentStep === 2) {
            stepNext.disabled = state.selectedItems.length === 0;
        } else {
            stepNext.disabled = false;
        }
    }

    function resetModal() {
        state.selectedCustomer = null;
        state.selectedItems = [];
        state.selectedProductIds = [];
        state.currentStep = 1;
        state.editingQuotation = null;
        salesInput.value = '';
        remarksInput.value = '';
        paymentTermsInput.value = '';
        cancellationTermsInput.value = '';
        deliveryTermsInput.value = '';
        leadTimeInput.value = '';
        validUntilInput.value = '';
        warrantyInput.value = '';
        modeOfPaymentInput.value = '';
        attentionToInput.value = '';
        if (priceRefDisplay) priceRefDisplay.style.display = 'none';
        customerDisplay.innerHTML = '<button type="button" class="sq-btn sq-btn--secondary" data-sq-select-customer><svg viewBox="0 0 24 24" fill="none" width="16" height="16"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2"/></svg> Select Customer</button>';
        itemsContainer.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:24px;">No items selected yet. Click "Select Items" to add products.</p>';
        if (itemsTotal) itemsTotal.textContent = '';
        quotationNoDisplay.value = '(Auto-generated)';
        quotationDateDisplay.value = new Date().toISOString().slice(0, 10);
        stepNext.hidden = false;
        stepNext.disabled = false;
        if (modalFooterClose) modalFooterClose.textContent = 'Cancel';
        goToStep(1);
        modalKicker.textContent = 'Create Quotation';
        modalTitle.textContent = 'New Sales Quotation';
        stepNext.textContent = 'Next';
        stepBack.hidden = true;
    }

    // ========== CUSTOMERS ==========
    function loadCustomers(page, searchData, globalQ) {
        var params = new URLSearchParams();
        if (page) params.set('page', page);
        if (globalQ) params.set('q', globalQ);
        if (searchData) { for (var key in searchData) { if (searchData[key]) params.set('search[' + key + ']', searchData[key]); } }
        fetch(customersUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                customerList.innerHTML = '';
                data.customers.forEach(function (c) {
                    var isSelected = state.selectedCustomer && state.selectedCustomer.id === c.id;
                    var row = document.createElement('tr');
                    row.className = isSelected ? 'is-selected' : '';
                    row.dataset.sqCustomer = JSON.stringify(c);
                    row.innerHTML = '<td>' + escapeHtml(c.customer_no) + '</td><td>' + escapeHtml(c.customer_name) + '</td><td>' + escapeHtml(c.tin || '--') + '</td><td><span class="sq-badge sq-badge--' + c.price_reference + '">' + escapeHtml(c.price_reference_label) + '</span></td><td>' + escapeHtml(c.sales_agent || '--') + '</td><td>' + escapeHtml(c.address || '--') + '</td><td><button type="button" class="sq-btn sq-btn--sm sq-btn--' + (isSelected ? 'danger' : 'primary') + '" data-sq-customer-select-action>' + (isSelected ? 'Deselect' : 'Select') + '</button></td>';
                    (function (cust) {
                        row.addEventListener('click', function (e) {
                            if (e.target.closest('[data-sq-customer-select-action]')) return;
                            selectCustomer(cust); closeModal(customerModal);
                        });
                        var actionBtn = row.querySelector('[data-sq-customer-select-action]');
                        if (actionBtn) actionBtn.addEventListener('click', function (e) { e.stopPropagation(); selectCustomer(cust); closeModal(customerModal); });
                    })(c);
                    customerList.appendChild(row);
                });
                if (data.customers.length === 0) customerList.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;">No customers found.</td></tr>';
                paginateCustomers(data, page, searchData, globalQ);
            });
    }

    function paginateCustomers(data, page, searchData, globalQ) {
        customerPagination.innerHTML = '';
        if (data.last_page <= 1) return;
        var html = '';
        if (data.current_page > 1) html += '<button type="button" data-cp="' + (data.current_page - 1) + '">Previous</button>';
        for (var i = 1; i <= data.last_page; i++) html += '<button type="button" data-cp="' + i + '"' + (i === data.current_page ? ' style="font-weight:700;background:#071a3d;color:#fff;"' : '') + '>' + i + '</button>';
        if (data.current_page < data.last_page) html += '<button type="button" data-cp="' + (data.current_page + 1) + '">Next</button>';
        customerPagination.innerHTML = html;
        customerPagination.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () { loadCustomers(parseInt(this.dataset.cp), getCustomerSearchData(), customerSearch.value); });
        });
    }

    function getCustomerSearchData() {
        var data = {};
        customerColSearches.forEach(function (input) { if (input.value) data[input.dataset.sqCustomerColSearch] = input.value; });
        return data;
    }

    function selectCustomer(c) {
        state.selectedCustomer = c;
        updateCustomerDisplay();
        updatePriceRefDisplay();
        updateStepControls();
    }

    function updateCustomerDisplay() {
        if (state.selectedCustomer) {
            customerDisplay.innerHTML = '<div class="sq-customer-selected"><div class="sq-customer-selected__info"><div class="sq-customer-selected__name">' + escapeHtml(state.selectedCustomer.customer_name) + '</div><div class="sq-customer-selected__detail">' + escapeHtml(state.selectedCustomer.customer_no) + ' &middot; ' + escapeHtml(state.selectedCustomer.price_reference_label) + '</div></div><button type="button" class="sq-btn sq-btn--sm sq-btn--danger" data-sq-clear-customer>Remove</button></div>';
        } else {
            customerDisplay.innerHTML = '<button type="button" class="sq-btn sq-btn--secondary" data-sq-select-customer><svg viewBox="0 0 24 24" fill="none" width="16" height="16"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2"/></svg> Select Customer</button>';
            customerDisplay.querySelector('[data-sq-select-customer]').addEventListener('click', function () { openModal(customerModal); loadCustomers(1); });
        }
    }

    function updatePriceRefDisplay() {
        if (!priceRefDisplay || !priceRefLabel) return;
        if (state.selectedCustomer) {
            priceRefDisplay.style.display = 'block';
            priceRefLabel.textContent = (state.selectedCustomer.price_reference_label || 'Price Reference') + ' — ' + (state.selectedCustomer.price_reference || '').toUpperCase();
        } else {
            priceRefDisplay.style.display = 'none';
        }
    }

    // ========== PRODUCTS ==========
    function loadProducts(page, searchData, globalQ) {
        var params = new URLSearchParams();
        if (page) params.set('page', page);
        if (globalQ) params.set('q', globalQ);
        if (searchData) { for (var key in searchData) { if (searchData[key]) params.set('search[' + key + ']', searchData[key]); } }
        state.selectedProductIds.forEach(function (id) { params.append('selected_ids[]', id); });
        fetch(productsUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                productList.innerHTML = '';
                data.products.forEach(function (p) {
                    var isSelected = state.selectedProductIds.indexOf(p.id) !== -1;
                    var row = document.createElement('tr');
                    row.className = isSelected ? 'is-selected' : '';
                    row.dataset.sqProduct = JSON.stringify(p);
                    row.innerHTML = '<td><input type="checkbox" class="sq-product-checkbox" data-sq-product-checkbox value="' + p.id + '"' + (isSelected ? ' checked' : '') + '></td><td>' + escapeHtml(p.item_no) + '</td><td>' + escapeHtml(p.product) + '</td><td>' + escapeHtml(p.brand) + '</td><td>' + escapeHtml(p.unit) + '</td><td>' + p.selling_price.toFixed(2) + '</td><td>' + fmt(calcWithoutVat(p.selling_price)) + '</td>';
                    (function (prod, sel) {
                        var checkbox = row.querySelector('[data-sq-product-checkbox]');
                        row.addEventListener('click', function (e) {
                            if (e.target.closest('.sq-product-checkbox')) return;
                            if (checkbox) { checkbox.checked = !checkbox.checked; toggleProductCheck(prod, checkbox.checked); }
                        });
                        if (checkbox) checkbox.addEventListener('change', function () { toggleProductCheck(prod, this.checked); });
                    })(p, isSelected);
                    productList.appendChild(row);
                });
                if (data.products.length === 0) productList.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;">No products found.</td></tr>';
                paginateProducts(data, page, searchData, globalQ);
            });
    }

    function paginateProducts(data, page, searchData, globalQ) {
        productPagination.innerHTML = '';
        if (data.last_page <= 1) return;
        var html = '';
        if (data.current_page > 1) html += '<button type="button" data-pp="' + (data.current_page - 1) + '">Previous</button>';
        for (var i = 1; i <= data.last_page; i++) html += '<button type="button" data-pp="' + i + '"' + (i === data.current_page ? ' style="font-weight:700;background:#071a3d;color:#fff;"' : '') + '>' + i + '</button>';
        if (data.current_page < data.last_page) html += '<button type="button" data-pp="' + (data.current_page + 1) + '">Next</button>';
        productPagination.innerHTML = html;
        productPagination.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () { loadProducts(parseInt(this.dataset.pp), getProductSearchData(), productSearch.value); });
        });
    }

    function getProductSearchData() {
        var data = {};
        productColSearches.forEach(function (input) { if (input.value) data[input.dataset.sqProductColSearch] = input.value; });
        return data;
    }

    function toggleProductCheck(prod, checked) {
        var idx = state.selectedProductIds.indexOf(prod.id);
        if (checked && idx === -1) {
            state.selectedProductIds.push(prod.id);
            var unitPrice = calcWithoutVat(prod.selling_price);
            state.selectedItems.push({
                product_id: prod.id,
                item_no: prod.item_no,
                product: prod.product,
                brand: prod.brand,
                unit: prod.unit,
                qty: prod.qty,
                quantity: 1,
                unit_price: unitPrice,
                selling_price: prod.selling_price,
            });
        } else if (!checked && idx !== -1) {
            state.selectedProductIds.splice(idx, 1);
            state.selectedItems = state.selectedItems.filter(function (item) { return item.product_id !== prod.id; });
        }
        renderSelectedItems();
        updateStepControls();
    }

    // ========== RENDER ITEMS ==========
    function renderSelectedItems() {
        if (state.selectedItems.length === 0) {
            itemsContainer.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:24px;">No items selected yet. Click "Select Items" to add products.</p>';
            if (itemsTotal) itemsTotal.textContent = '';
            updateStepControls();
            return;
        }
        var searchTerm = itemsSearch ? itemsSearch.value.toLowerCase() : '';
        var filtered = state.selectedItems.map(function (item, index) { return { item: item, index: index }; });
        if (searchTerm) {
            filtered = filtered.filter(function (entry) {
                var i = entry.item;
                return (i.item_no && i.item_no.toLowerCase().includes(searchTerm)) || (i.product && i.product.toLowerCase().includes(searchTerm)) || (i.brand && i.brand.toLowerCase().includes(searchTerm));
            });
        }
        var html = '<div class="sq-table-shell"><table class="sq-table"><thead><tr><th>Item No.</th><th>Product</th><th>Brand</th><th>Unit</th><th>QTY</th><th>Unit Price (VAT Ex)</th><th>Unit Price (VAT Inc)</th><th>Line Total</th><th>Action</th></tr></thead><tbody>';
        filtered.forEach(function (entry) {
            var item = entry.item;
            var idx = entry.index;
            var up = calcItemPrice(item);
            var upTax = calcUnitPriceWithTax(item);
            var lt = calcLineTotal(item);
            html += '<tr>' +
                '<td>' + escapeHtml(item.item_no) + '</td>' +
                '<td>' + escapeHtml(item.product) + '</td>' +
                '<td>' + escapeHtml(item.brand) + '</td>' +
                '<td>' + escapeHtml(item.unit) + '</td>' +
                '<td><input type="number" class="sq-table__filter-input" style="width:65px;text-align:right;" value="' + Number(item.quantity || 1).toFixed(0) + '" min="1" step="1" data-sq-item-qty="' + idx + '"></td>' +
                '<td><input type="number" class="sq-table__filter-input" style="width:95px;text-align:right;" value="' + up.toFixed(2) + '" min="0" step="0.01" data-sq-item-price="' + idx + '"></td>' +
                '<td style="text-align:right;font-weight:600;">' + fmt(upTax) + '</td>' +
                '<td style="text-align:right;font-weight:600;color:#166534;" data-sq-item-total="' + idx + '">' + fmt(lt) + '</td>' +
                '<td><button type="button" class="sq-icon-btn sq-icon-btn--danger" data-sq-item-remove="' + idx + '"><svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2"/></svg></button></td></tr>';
        });
        html += '</tbody></table></div>';
        itemsContainer.innerHTML = html;

        itemsContainer.querySelectorAll('[data-sq-item-qty]').forEach(function (input) {
            function sync() {
                var idx = parseInt(input.dataset.sqItemQty);
                var val = parseFloat(input.value) || 1;
                if (val < 1) val = 1;
                state.selectedItems[idx].quantity = val;
                var totalCell = itemsContainer.querySelector('[data-sq-item-total="' + idx + '"]');
                if (totalCell) totalCell.textContent = fmt(calcLineTotal(state.selectedItems[idx]));
                if (state.currentStep === 3) buildReview();
                updateTotals();
            }
            input.addEventListener('input', sync);
            input.addEventListener('change', function () {
                if (parseFloat(this.value) < 1) { state.selectedItems[parseInt(this.dataset.sqItemQty)].quantity = 1; renderSelectedItems(); return; }
                sync();
            });
        });

        itemsContainer.querySelectorAll('[data-sq-item-price]').forEach(function (input) {
            function sync() {
                var idx = parseInt(input.dataset.sqItemPrice);
                var val = parseFloat(input.value) || 0;
                if (val < 0) val = 0;
                state.selectedItems[idx].unit_price = val;
                var upTaxCell = input.closest('tr').querySelector('td:nth-child(7)');
                var totalCell = itemsContainer.querySelector('[data-sq-item-total="' + idx + '"]');
                var lt = calcLineTotal(state.selectedItems[idx]);
                if (upTaxCell) upTaxCell.textContent = fmt(calcUnitPriceWithTax(state.selectedItems[idx]));
                if (totalCell) totalCell.textContent = fmt(lt);
                if (state.currentStep === 3) buildReview();
                updateTotals();
            }
            input.addEventListener('input', sync);
            input.addEventListener('change', sync);
        });

        itemsContainer.querySelectorAll('[data-sq-item-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(this.dataset.sqItemRemove);
                var item = state.selectedItems[idx];
                if (item) {
                    var pid = state.selectedProductIds.indexOf(item.product_id);
                    if (pid !== -1) state.selectedProductIds.splice(pid, 1);
                    state.selectedItems.splice(idx, 1);
                    renderSelectedItems();
                    updateStepControls();
                }
            });
        });
        updateTotals();
        updateStepControls();
    }

    function updateTotals() {
        if (!itemsTotal) return;
        itemsTotal.textContent = 'Subtotal (VAT Ex): ' + fmt(calcSubtotal(state.selectedItems)) + ' | VAT: ' + fmt(calcTax(state.selectedItems)) + ' | Grand Total (VAT Inc): ' + fmt(calcGrandTotal(state.selectedItems));
    }

    // ========== BUILD REVIEW ==========
    function buildReview() {
        var preview = root.querySelector('[data-sq-print-preview]');
        if (!preview) return;

        var customer = state.selectedCustomer;
        var now = new Date();
        var dateStr = now.toISOString().slice(0, 10);
        var timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        var rowCount = Math.max(25, state.selectedItems.length);
        var rowsHtml = '';
        var grandTotal = calcGrandTotal(state.selectedItems);
        var attention = attentionToInput ? attentionToInput.value : '';
        var preparedBy = '(You)';

        for (var i = 0; i < rowCount; i++) {
            var item = state.selectedItems[i];
            if (item) {
                var upTax = calcUnitPriceWithTax(item);
                var lt = calcLineTotal(item);
                rowsHtml += '<tr><td class="qp-c">' + (i + 1) + '</td><td class="qp-desc">' + escapeHtml(((item.product || '') + ' ' + (item.brand || '')).trim().toUpperCase()) + '</td><td class="qp-c">' + Number(item.quantity || 0).toFixed(0) + '</td><td class="qp-c">' + escapeHtml(item.unit || '') + '</td><td class="qp-r">' + upTax.toFixed(2) + '</td><td class="qp-r">' + lt.toFixed(2) + '</td></tr>';
            } else {
                rowsHtml += '<tr><td class="qp-c"></td><td></td><td></td><td></td><td></td><td></td></tr>';
            }
        }

        var attentionRow = attention ? '<div class="qp-info-label">Attention:</div><div class="qp-info-value">' + escapeHtml(attention) + '</div>' : '';

        var termsHtml = '';
        var terms = [];
        if (leadTimeInput.value) terms.push('Lead Time: ' + leadTimeInput.value);
        if (validUntilInput.value) terms.push('Offer Valid Until: ' + validUntilInput.value);
        if (paymentTermsInput.value) terms.push('Payment Terms: ' + paymentTermsInput.value);
        if (modeOfPaymentInput.value) terms.push('Mode of Payment: ' + modeOfPaymentInput.value);
        if (deliveryTermsInput.value) terms.push('Delivery Terms: ' + deliveryTermsInput.value);
        if (cancellationTermsInput.value) terms.push('Cancellation Terms: ' + cancellationTermsInput.value);
        if (warrantyInput.value) terms.push('Warranty: ' + warrantyInput.value);
        if (terms.length > 0) {
            termsHtml = '<section class="qp-terms"><h3>Terms &amp; Conditions</h3><ul>';
            terms.forEach(function (t) { termsHtml += '<li>' + escapeHtml(t) + '</li>'; });
            termsHtml += '</ul></section>';
        }

        preview.innerHTML =
            '<div class="qp-sheet">' +
                '<section class="qp-top">' +
                    '<div class="qp-company">' +
                        '<img class="qp-logo" src="' + escapeHtml(logoUrl) + '" alt="CONTROL A logo">' +
                        '<div><h1 class="qp-company-name">CONTROL A TRADING AND SERVICES CORP.</h1>' +
                        '<div class="qp-company-lines"><div>601-163-860-00000</div><div>728 GENERAL LUIS ST. CAYBIGA CALOOCAN CITY</div><div>0945 825 8802</div></div></div>' +
                    '</div>' +
                    '<aside><div class="qp-title">Sales Quotation</div>' +
                    '<div class="qp-info-grid">' +
                        '<div class="qp-info-label">Quotation No.:</div><div class="qp-info-value">(Auto)</div>' +
                        '<div class="qp-info-label">Date:</div><div class="qp-info-value">' + dateStr + '</div>' +
                        '<div class="qp-info-label">Prepared by:</div><div class="qp-info-value">' + preparedBy + '</div>' +
                        '<div class="qp-info-label">Time:</div><div class="qp-info-value">' + timeStr + '</div>' +
                        attentionRow +
                    '</div></aside>' +
                '</section>' +
                '<section class="qp-sold-to">' +
                    '<div class="qp-sold-row"><div class="qp-sold-label">Customer Name:</div><div class="qp-sold-value">' + escapeHtml(((customer && customer.customer_name) || '').toUpperCase()) + '</div></div>' +
                    '<div class="qp-sold-row"><div class="qp-sold-label">TIN No:</div><div class="qp-sold-value">' + escapeHtml((customer && customer.tin) || '') + '</div></div>' +
                    '<div class="qp-sold-row"><div class="qp-sold-label">Address:</div><div class="qp-sold-value">' + escapeHtml(((customer && customer.address) || '').toUpperCase()) + '</div></div>' +
                '</section>' +
                '<table class="qp-order-table"><thead><tr><th style="width:12mm;">Item No:</th><th>Item Description</th><th style="width:12mm;">Qty</th><th style="width:14mm;">Unit</th><th style="width:23mm;">Unit Price</th><th style="width:25mm;">Total Price</th></tr></thead><tbody>' + rowsHtml + '</tbody></table>' +
                '<section class="qp-bottom"><div class="qp-total-box"><div class="qp-total-row qp-total-row--green"><div class="qp-total-label">TOTAL AMOUNT</div><div class="qp-amount">' + grandTotal.toFixed(2) + '</div></div><div class="qp-total-row"><div class="qp-sign-label">PREPARED BY:</div><div></div></div><div class="qp-total-row"><div class="qp-sign-label">CHECKED BY:</div><div></div></div><div class="qp-total-row"><div class="qp-sign-label">NOTED BY:</div><div></div></div></div></section>' +
                termsHtml +
            '</div>';
    }

    // ========== EVENTS ==========
    createBtn.addEventListener('click', function () {
        resetModal();
        openModal(modal);
    });

    modalClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(modal); }); });

    // Customer
    customerSearch.addEventListener('input', function () { loadCustomers(1, getCustomerSearchData(), this.value); });
    customerColSearches.forEach(function (input) { input.addEventListener('input', function () { loadCustomers(1, getCustomerSearchData(), customerSearch.value); }); });
    customerDisplay.addEventListener('click', function (e) {
        if (e.target.closest('[data-sq-select-customer]')) { openModal(customerModal); loadCustomers(1); }
        if (e.target.closest('[data-sq-clear-customer]')) { state.selectedCustomer = null; updateCustomerDisplay(); updatePriceRefDisplay(); updateStepControls(); }
    });
    customerModalClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(customerModal); }); });

    // Products
    productSearch.addEventListener('input', function () { loadProducts(1, getProductSearchData(), this.value); });
    productColSearches.forEach(function (input) { input.addEventListener('input', function () { loadProducts(1, getProductSearchData(), productSearch.value); }); });
    selectItemsBtn.addEventListener('click', function () { openModal(productModal); loadProducts(1); });
    productModalClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(productModal); }); });
    productApplyBtn.addEventListener('click', function () { closeModal(productModal); renderSelectedItems(); updateStepControls(); });

    if (itemsSearch) itemsSearch.addEventListener('input', function () { renderSelectedItems(); });

    stepBack.addEventListener('click', function () { if (state.currentStep > 1) goToStep(state.currentStep - 1); });

    stepNext.addEventListener('click', function () {
        if (state.currentStep === 1) {
            if (!state.selectedCustomer) { showNotice('Please select a customer.', 'error'); return; }
            goToStep(2);
        } else if (state.currentStep === 2) {
            if (state.selectedItems.length === 0) { showNotice('Please select at least one product.', 'error'); return; }
            buildReview();
            goToStep(3);
        } else if (state.currentStep === 3) {
            confirmTitle.textContent = 'Confirm Sales Quotation';
            confirmMessage.textContent = 'Are you sure you want to create this sales quotation?';
            confirmProceed.textContent = 'Yes, Create';
            openModal(confirmModal);
        }
    });

    confirmClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(confirmModal); }); });
    successClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(successModal); reloadPage(); }); });

    confirmProceed.addEventListener('click', function () { createQuotation(); });

    // ========== CREATE ==========
    function createQuotation() {
        var items = state.selectedItems.map(function (i) {
            return { product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price, offer_description: '' };
        });
        var body = {
            customer_id: state.selectedCustomer.id,
            sales: salesInput.value,
            remarks: remarksInput.value,
            payment_terms: paymentTermsInput.value,
            cancellation_terms: cancellationTermsInput.value,
            delivery_terms: deliveryTermsInput.value,
            lead_time_at: leadTimeInput.value,
            valid_until: validUntilInput.value,
            warranty: warrantyInput.value,
            mode_of_payment: modeOfPaymentInput.value,
            attention_to: attentionToInput.value,
            items: items,
        };
        closeModal(confirmModal);
        fetch(storeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body), credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
            .then(function (res) {
                if (res.status === 201) {
                    closeModal(modal);
                    successTitle.textContent = 'Sales Quotation Created';
                    successMessage.innerHTML = 'Quotation #' + escapeHtml(res.data.quotation.quotation_no) + ' has been created. <button type="button" class="sq-btn sq-btn--sm sq-btn--primary" onclick="window.open(\'' + escapeHtml(res.data.print_url) + '\', \'_blank\')" style="margin-top:8px;">Print Now</button>';
                    openModal(successModal);
                } else {
                    showNotice(res.data.message || 'Failed to create quotation.', 'error');
                }
            })
            .catch(function () { showNotice('An error occurred.', 'error'); });
    }

    // ========== VIEW ==========
    root.addEventListener('click', function (e) {
        var viewBtn = e.target.closest('[data-sq-view]');
        if (!viewBtn) return;
        var row = viewBtn.closest('[data-sq-row]');
        if (!row) return;
        var id = row.dataset.sqId;
        var url = showUrlTemplate.replace('__SALES_QUOTATION_ID__', id);
        window.open(url, '_blank');
    });

    // ========== PRINT ==========
    root.addEventListener('click', function (e) {
        var printBtn = e.target.closest('[data-sq-print]');
        if (!printBtn) return;
        var row = printBtn.closest('[data-sq-row]');
        if (!row) return;
        var id = row.dataset.sqId;
        var url = printUrlTemplate.replace('__SALES_QUOTATION_ID__', id);
        window.open(url, '_blank');
    });

    // ========== INIT ==========
    loadTable(1);
});
