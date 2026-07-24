document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-sales-order-list]');
    if (!root) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const storeUrl = root.dataset.storeUrl || '';
    const updateUrlTemplate = root.dataset.updateUrlTemplate || '';
    const showUrlTemplate = root.dataset.showUrlTemplate || '';
    const printSoUrlTemplate = root.dataset.printSoUrlTemplate || '';
    const printSiUrlTemplate = root.dataset.printSiUrlTemplate || '';
    const printBothUrlTemplate = root.dataset.printBothUrlTemplate || '';
    const logoUrl = root.dataset.logoUrl || '';

    // State
    let editingId = null;
    let selectedCustomer = null;
    let selectedItems = [];
    let selectedProductIds = new Set();
    let currentStep = 1;
    let previewOrder = null;
    let discountPercent = 0;
    const VAT_RATE = 0.12;
    const VAT_MULTIPLIER = 1.12;

    // DOM refs
    const notice = root.querySelector('[data-so-notice]');
    const createBtn = root.querySelector('[data-so-create-button]');
    const modal = root.querySelector('[data-so-modal]');
    const modalClose = root.querySelectorAll('[data-so-modal-close]');
    const modalKicker = root.querySelector('[data-so-modal-kicker]');
    const modalTitle = root.querySelector('[data-so-modal-title]');
    const stepPanels = { 1: root.querySelector('[data-so-step-panel="1"]'), 2: root.querySelector('[data-so-step-panel="2"]'), 3: root.querySelector('[data-so-step-panel="3"]') };
    const stepIndicators = { 1: root.querySelector('[data-so-step="1"]'), 2: root.querySelector('[data-so-step="2"]'), 3: root.querySelector('[data-so-step="3"]') };
    const stepBack = root.querySelector('[data-so-step-back]');
    const stepNext = root.querySelector('[data-so-step-next]');

    // Step 1
    const soNoDisplay = root.querySelector('[data-so-no]');
    const soDateDisplay = root.querySelector('[data-so-date]');
    const customerDisplay = root.querySelector('[data-so-customer-display]');
    const selectCustomerBtn = root.querySelector('[data-so-select-customer]');
    const salesChannel = root.querySelector('[data-so-sales-channel]');

    // Step 2
    const selectItemsBtn = root.querySelector('[data-so-select-items]');
    const itemsContainer = root.querySelector('[data-so-items-container]');
    const itemsSearch = root.querySelector('[data-so-items-search]');

    // Step 3
    const reviewTabs = root.querySelectorAll('[data-so-review-tab]');
    const printPreview = root.querySelector('[data-so-print-preview]');
    const modalFooterClose = root.querySelector('.so-modal__footer [data-so-modal-close]');

    // Customer modal
    const customerModal = root.querySelector('[data-so-customer-modal]');
    const customerModalClose = root.querySelectorAll('[data-so-customer-close]');
    const customerSearch = root.querySelector('[data-so-customer-search]');
    const customerList = root.querySelector('[data-so-customer-list]');
    const customerPagination = root.querySelector('[data-so-customer-pagination]');
    const customerColSearches = root.querySelectorAll('[data-so-customer-col-search]');

    // Product modal
    const productModal = root.querySelector('[data-so-product-modal]');
    const productModalClose = root.querySelectorAll('[data-so-product-close]');
    const productSearch = root.querySelector('[data-so-product-search]');
    const productList = root.querySelector('[data-so-product-list]');
    const productPagination = root.querySelector('[data-so-product-pagination]');
    const productColSearches = root.querySelectorAll('[data-so-product-col-search]');
    const productApplyBtn = root.querySelector('[data-so-product-apply]');

    // Confirmation modal
    const confirmModal = root.querySelector('[data-so-confirm-modal]');
    const confirmClose = root.querySelectorAll('[data-so-confirm-close]');
    const confirmTitle = root.querySelector('[data-so-confirm-title]');
    const confirmMessage = root.querySelector('[data-so-confirm-message]');
    const confirmProceed = root.querySelector('[data-so-confirm-proceed]');

    // Success modal
    const successModal = root.querySelector('[data-so-success-modal]');
    const successClose = root.querySelectorAll('[data-so-success-close]');
    const successTitle = root.querySelector('[data-so-success-title]');
    const successMessage = root.querySelector('[data-so-success-message]');

    // Print modal
    const printModal = root.querySelector('[data-so-print-modal]');
    const printModalClose = root.querySelectorAll('[data-so-print-close]');
    const printActions = root.querySelectorAll('[data-so-print-action]');

    // (Delete modal removed — main table no longer shows delete action)

    // Price filter
    const priceFilter = root.querySelector('[data-so-price-filter]');

    // ========== UTILITY ==========
    function showNotice(msg, type) { notice.textContent = msg; notice.className = 'so-notice so-notice--' + type; notice.hidden = false; setTimeout(function () { notice.hidden = true; }, 5000); }

    function openModal(el) { el.hidden = false; document.body.style.overflow = 'hidden'; }
    function closeModal(el) { el.hidden = true; document.body.style.overflow = ''; }

    function calcWithoutVat(price) { return Math.round((price / VAT_MULTIPLIER) * 100) / 100; }
    function fmt(n) { return '&#8369;' + Number(n).toFixed(2); }
    function escapeHtml(str) { var div = document.createElement('div'); div.textContent = str; return div.innerHTML; }
    function lineTotal(item) { return Math.round(Number(item.ordered_qty || 0) * Number(item.selling_price || 0) * 100) / 100; }
    function vatExclusiveUnit(item) { return calcWithoutVat(Number(item.selling_price || 0)); }
    function vatExclusiveLine(item) { return Math.round(Number(item.ordered_qty || 0) * vatExclusiveUnit(item) * 100) / 100; }
    function discountedUnitPrice(item) { var sp = Number(item.selling_price || 0); var rate = discountPercent / 100; return Math.round(sp * (1 - rate) * 100) / 100; }
    function discountAmountPerUnit(item) { var sp = Number(item.selling_price || 0); var rate = discountPercent / 100; return Math.round(sp * rate * 100) / 100; }
    function discountedLineTotal(item) { return Math.round(Number(item.ordered_qty || 0) * discountedUnitPrice(item) * 100) / 100; }
    function vatExclusiveDiscountedUnit(item) { return calcWithoutVat(discountedUnitPrice(item)); }
    function vatExclusiveDiscountedLine(item) { return Math.round(Number(item.ordered_qty || 0) * vatExclusiveDiscountedUnit(item) * 100) / 100; }
    function availableQty(item) { return Number(item.available_qty ?? item.qty ?? 0); }
    function stockIssue(item) {
        var ordered = Number(item.ordered_qty || 0);
        var available = availableQty(item);
        if (ordered <= 0) return 'Ordered quantity must be greater than zero.';
        if (ordered > available) return 'Ordered quantity is greater than available quantity (' + available.toFixed(2) + ').';
        return '';
    }
    function hasStockIssues() { return selectedItems.some(function (item) { return stockIssue(item) !== ''; }); }
    function updateStepControls() {
        if (!stepNext || stepNext.hidden) return;
        stepNext.disabled = currentStep === 2 && (selectedItems.length === 0 || hasStockIssues());
    }

    function reloadPage() { window.location.reload(); }

    function updatePriceRefDisplay() {
        var display = root.querySelector('[data-so-price-ref-display]');
        var label = root.querySelector('[data-so-price-ref-label]');
        var discDisplay = root.querySelector('[data-so-discount-percent-display]');
        if (!display || !label || !discDisplay) return;
        if (selectedCustomer && discountPercent > 0) {
            display.style.display = 'block';
            label.textContent = (selectedCustomer.price_reference_label || 'Price Reference') + ' — ' + (selectedCustomer.price_reference || '').toUpperCase();
            discDisplay.textContent = discountPercent.toFixed(2) + '%';
        } else if (selectedCustomer && discountPercent === 0) {
            display.style.display = 'block';
            label.textContent = (selectedCustomer.price_reference_label || 'Price Reference') + ' — ' + (selectedCustomer.price_reference || '').toUpperCase();
            discDisplay.textContent = '0.00% (No discount configured)';
        } else {
            display.style.display = 'none';
        }
    }

    // ========== STEP MANAGEMENT ==========
    function goToStep(step) {
        currentStep = step;
        for (var i = 1; i <= 3; i++) {
            stepPanels[i].hidden = i !== step;
            stepIndicators[i].className = 'so-step';
            if (i < step) stepIndicators[i].classList.add('is-completed');
            else if (i === step) stepIndicators[i].classList.add('is-active');
        }
        stepBack.hidden = step === 1;
        stepNext.textContent = step === 3 ? (editingId ? 'Save Changes' : 'Create') : 'Next';
        updateStepControls();
    }

    function resetModal() {
        editingId = null;
        selectedCustomer = null;
        selectedItems = [];
        selectedProductIds = new Set();
        previewOrder = null;
        discountPercent = 0;
        currentStep = 1;
        salesChannel.value = '';
        customerDisplay.innerHTML = '<button type="button" class="so-btn so-btn--secondary" data-so-select-customer><svg viewBox="0 0 24 24" fill="none" width="16" height="16"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2"/></svg> Select Customer</button>';
        itemsContainer.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:24px;">No items selected yet. Click "Select Items" to add products.</p>';
        if (printPreview) printPreview.innerHTML = '';
        stepNext.hidden = false;
        stepNext.disabled = false;
        if (modalFooterClose) modalFooterClose.textContent = 'Cancel';
        goToStep(1);
        modalKicker.textContent = 'Create Invoice';
        modalTitle.textContent = 'New Sales Order';
        stepNext.textContent = 'Next';
        stepBack.hidden = true;
    }

    // ========== LOAD CUSTOMERS ==========
    function loadCustomers(page, searchData, globalQ) {
        var params = new URLSearchParams();
        if (page) params.set('page', page);
        if (globalQ) params.set('q', globalQ);
        if (searchData) { for (var key in searchData) { if (searchData[key]) params.set('search[' + key + ']', searchData[key]); } }

        fetch(storeUrl.replace('/store', '').replace(/\/+$/, '') + '/api/customers?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                customerList.innerHTML = '';
                data.customers.forEach(function (c) {
                    var isSelected = selectedCustomer && selectedCustomer.id === c.id;
                    var row = document.createElement('tr');
                    row.className = isSelected ? 'is-selected' : '';
                    row.dataset.customer = JSON.stringify(c);
                    row.innerHTML = '<td>' + escapeHtml(c.customer_no) + '</td><td>' + escapeHtml(c.customer_name) + '</td><td>' + escapeHtml(c.tin || '--') + '</td><td><span class="so-badge so-badge--' + c.price_reference + '">' + escapeHtml(c.price_reference_label) + '</span></td><td>' + escapeHtml(c.sales_agent || '--') + '</td><td>' + escapeHtml(c.salesman_name || '--') + '</td><td>' + escapeHtml(c.address || '--') + '</td><td><button type="button" class="so-btn so-btn--sm so-btn--' + (isSelected ? 'danger' : 'primary') + '" data-customer-select-action>' + (isSelected ? 'Deselect' : 'Select') + '</button></td>';
                    (function (cust) {
                        row.addEventListener('click', function (e) {
                            if (e.target.closest('[data-customer-select-action]')) return;
                            selectCustomer(cust); closeModal(customerModal);
                        });
                        var actionBtn = row.querySelector('[data-customer-select-action]');
                        if (actionBtn) {
                            actionBtn.addEventListener('click', function (e) { e.stopPropagation(); selectCustomer(cust); closeModal(customerModal); });
                        }
                    })(c);
                    customerList.appendChild(row);
                });
                if (data.customers.length === 0) customerList.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">No customers found.</td></tr>';

                paginateCustomers(data, page, searchData, globalQ);
            });
    }

    function paginateCustomers(data, page, searchData, globalQ) {
        customerPagination.innerHTML = '';
        if (data.last_page <= 1) return;
        var html = '';
        if (data.current_page > 1) html += '<button type="button" data-cp="' + (data.current_page - 1) + '">Previous</button>';
        for (var i = 1; i <= data.last_page; i++) {
            html += '<button type="button" data-cp="' + i + '"' + (i === data.current_page ? ' style="font-weight:700;background:#071a3d;color:#fff;"' : '') + '>' + i + '</button>';
        }
        if (data.current_page < data.last_page) html += '<button type="button" data-cp="' + (data.current_page + 1) + '">Next</button>';
        customerPagination.innerHTML = html;
        customerPagination.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () { loadCustomers(parseInt(this.dataset.cp), getCustomerSearchData(), customerSearch.value); });
        });
    }

    function getCustomerSearchData() {
        var data = {};
        customerColSearches.forEach(function (input) { if (input.value) data[input.dataset.soCustomerColSearch] = input.value; });
        return data;
    }

    function selectCustomer(c) {
        if (selectedCustomer && selectedCustomer.id === c.id) {
            selectedCustomer = null;
            discountPercent = 0;
        } else {
            selectedCustomer = c;
            discountPercent = Number(c.discount_percent) || 0;
            if (selectedItems.length > 0) {
                selectedItems.forEach(function (item) {
                    item.discount_percent = discountPercent;
                });
                renderSelectedItems();
                if (currentStep === 3) buildReview();
            }
        }
        updateCustomerDisplay();
        updatePriceRefDisplay();
    }

    function updateCustomerDisplay() {
        if (selectedCustomer) {
            customerDisplay.innerHTML = '<div class="so-customer-selected"><div class="so-customer-selected__info"><div class="so-customer-selected__name">' + escapeHtml(selectedCustomer.customer_name) + '</div><div class="so-customer-selected__detail">' + escapeHtml(selectedCustomer.customer_no) + ' &middot; ' + escapeHtml(selectedCustomer.price_reference_label) + ' &middot; ' + (selectedCustomer.discount_percent || 0).toFixed(2) + '% disc</div></div><button type="button" class="so-btn so-btn--sm so-btn--danger" data-so-clear-customer>Remove</button></div>';
        } else {
            customerDisplay.innerHTML = '<button type="button" class="so-btn so-btn--secondary" data-so-select-customer><svg viewBox="0 0 24 24" fill="none" width="16" height="16"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2"/></svg> Select Customer</button>';
            customerDisplay.querySelector('[data-so-select-customer]').addEventListener('click', function () { openModal(customerModal); loadCustomers(1); });
        }
    }

    // ========== LOAD PRODUCTS ==========
    function loadProducts(page, searchData, globalQ) {
        var params = new URLSearchParams();
        if (page) params.set('page', page);
        if (globalQ) params.set('q', globalQ);
        if (searchData) { for (var key in searchData) { if (searchData[key]) params.set('search[' + key + ']', searchData[key]); } }
        selectedProductIds.forEach(function (id) { params.append('selected_ids[]', id); });

        fetch(storeUrl.replace('/store', '').replace(/\/+$/, '') + '/api/products?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                productList.innerHTML = '';
                data.products.forEach(function (p) {
                    var isSelected = selectedProductIds.has(p.id);
                    var row = document.createElement('tr');
                    row.className = isSelected ? 'is-selected' : '';
                    if (!p.selectable && !isSelected) row.className += ' is-disabled';
                    row.dataset.product = JSON.stringify(p);
                    row.innerHTML = '<td>' + escapeHtml(p.item_no) + '</td><td>' + escapeHtml(p.product) + '</td><td>' + escapeHtml(p.brand) + '</td><td>' + escapeHtml(p.unit) + '</td><td>' + p.qty.toFixed(2) + '</td><td>' + fmt(p.selling_price) + '</td><td>' + (p.selectable || isSelected ? '<button type="button" class="so-btn so-btn--sm so-btn--' + (isSelected ? 'danger' : 'primary') + '" data-product-select-action>' + (isSelected ? 'Deselect' : 'Select') + '</button>' : '<span style="color:#dc2626;font-size:12px;">Out of stock</span>') + '</td>';
                    (function (prod, sel) {
                        row.addEventListener('click', function (e) {
                            if (e.target.closest('[data-product-select-action]')) return;
                            if (prod.selectable || sel) toggleProduct(prod);
                        });
                        var actionBtn = row.querySelector('[data-product-select-action]');
                        if (actionBtn) {
                            actionBtn.addEventListener('click', function (e) { e.stopPropagation(); toggleProduct(prod); });
                        }
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
        for (var i = 1; i <= data.last_page; i++) {
            html += '<button type="button" data-pp="' + i + '"' + (i === data.current_page ? ' style="font-weight:700;background:#071a3d;color:#fff;"' : '') + '>' + i + '</button>';
        }
        if (data.current_page < data.last_page) html += '<button type="button" data-pp="' + (data.current_page + 1) + '">Next</button>';
        productPagination.innerHTML = html;
        productPagination.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () { loadProducts(parseInt(this.dataset.pp), getProductSearchData(), productSearch.value); });
        });
    }

    function getProductSearchData() {
        var data = {};
        productColSearches.forEach(function (input) { if (input.value) data[input.dataset.soProductColSearch] = input.value; });
        return data;
    }

    function toggleProduct(p) {
        if (selectedProductIds.has(p.id)) {
            selectedProductIds.delete(p.id);
            selectedItems = selectedItems.filter(function (i) { return i.product_id !== p.id; });
        } else {
            selectedProductIds.add(p.id);
            selectedItems.push({ product_id: p.id, item_no: p.item_no, product: p.product, brand: p.brand, unit: p.unit, qty: p.qty, available_qty: p.qty, selling_price: p.selling_price, ordered_qty: 1, discount_percent: discountPercent });
        }
        loadProducts(parseInt(productPagination.querySelector('button[style*="font-weight:700"]')?.dataset.pp) || 1, getProductSearchData(), productSearch.value);
        renderSelectedItems();
        updateStepControls();
    }

    // ========== RENDER SELECTED ITEMS ==========
    function renderSelectedItems() {
        if (selectedItems.length === 0) {
            itemsContainer.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:24px;">No items selected yet. Click "Select Items" to add products.</p>';
            updateStepControls();
            return;
        }
        var searchTerm = itemsSearch ? itemsSearch.value.toLowerCase() : '';
        var filtered = selectedItems.map(function (item, index) { return { item: item, index: index }; });
        if (searchTerm) {
            filtered = filtered.filter(function (entry) {
                var i = entry.item;
                return (i.item_no && i.item_no.toLowerCase().includes(searchTerm)) ||
                       (i.product && i.product.toLowerCase().includes(searchTerm)) ||
                       (i.brand && i.brand.toLowerCase().includes(searchTerm)) ||
                       (i.unit && i.unit.toLowerCase().includes(searchTerm));
            });
        }
        var html = '<div class="so-table-shell"><table class="so-table"><thead><tr><th>Item No.</th><th>Product</th><th>Brand</th><th>Unit</th><th>Available QTY</th><th>Order QTY</th><th>Discount</th><th>Selling Price</th><th>Selling Price After Discount</th><th>Discounted Total</th><th>Action</th></tr><tr class="admin-table__filters"><th><input type="search" class="so-table__filter-input" placeholder="Search" data-so-item-filter="item_no"></th><th><input type="search" class="so-table__filter-input" placeholder="Search" data-so-item-filter="product"></th><th><input type="search" class="so-table__filter-input" placeholder="Search" data-so-item-filter="brand"></th><th><input type="search" class="so-table__filter-input" placeholder="Search" data-so-item-filter="unit"></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr></thead><tbody>';
        filtered.forEach(function (entry) {
            var item = entry.item;
            var idx = entry.index;
            var issue = stockIssue(item);
            var dUnit = discountedUnitPrice(item);
            var dLine = discountedLineTotal(item);
            html += '<tr class="' + (issue ? 'so-item-row--invalid' : '') + '">' +
                '<td>' + escapeHtml(item.item_no) + '</td>' +
                '<td>' + escapeHtml(item.product) + '</td>' +
                '<td>' + escapeHtml(item.brand) + '</td>' +
                '<td>' + escapeHtml(item.unit) + '</td>' +
                '<td>' + availableQty(item).toFixed(2) + '</td>' +
                '<td><input type="number" class="so-table__filter-input" style="width:72px;text-align:right;" value="' + item.ordered_qty + '" min="0.01" max="' + availableQty(item) + '" step="0.01" data-item-qty="' + idx + '"><div class="so-item-stock-error" ' + (issue ? '' : 'hidden') + ' data-item-error="' + idx + '">' + escapeHtml(issue) + '</div></td>' +
                '<td style="text-align:center;font-weight:600;">' + (discountPercent > 0 ? discountPercent.toFixed(2) + '%' : '0.00%') + '</td>' +
                '<td style="text-align:right;">' + fmt(item.selling_price) + '</td>' +
                '<td style="text-align:right;font-weight:600;">' + fmt(dUnit) + '</td>' +
                '<td style="text-align:right;font-weight:600;color:#166534;" data-item-discounted-total="' + idx + '">' + fmt(dLine) + '</td>' +
                '<td><button type="button" class="so-icon-btn so-icon-btn--danger" data-item-remove="' + idx + '"><svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2"/></svg></button></td></tr>';
        });
        html += '</tbody></table></div>';
        itemsContainer.innerHTML = html;

        // Wire up qty changes
        itemsContainer.querySelectorAll('[data-item-qty]').forEach(function (input) {
            function syncQty() {
                var idx = parseInt(this.dataset.itemQty);
                var val = parseFloat(this.value);
                if (isNaN(val)) val = 0;
                selectedItems[idx].ordered_qty = val;
                var totalCell = itemsContainer.querySelector('[data-item-discounted-total="' + idx + '"]');
                if (totalCell) totalCell.innerHTML = fmt(discountedLineTotal(selectedItems[idx]));
                var error = stockIssue(selectedItems[idx]);
                var row = this.closest('tr');
                var errorEl = itemsContainer.querySelector('[data-item-error="' + idx + '"]');
                if (row) row.classList.toggle('so-item-row--invalid', Boolean(error));
                if (errorEl) {
                    errorEl.textContent = error;
                    errorEl.hidden = !error;
                }
                updateStepControls();
                if (currentStep === 3) buildReview();
            }
            input.addEventListener('input', syncQty);
            input.addEventListener('change', function () {
                if (Number(selectedItems[parseInt(this.dataset.itemQty)].ordered_qty || 0) <= 0) {
                    selectedItems[parseInt(this.dataset.itemQty)].ordered_qty = 1;
                    renderSelectedItems();
                    return;
                }
                syncQty.call(this);
            });
        });

        // Wire up removes
        itemsContainer.querySelectorAll('[data-item-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(this.dataset.itemRemove);
                var item = selectedItems[idx];
                if (item) { selectedProductIds.delete(item.product_id); selectedItems.splice(idx, 1); renderSelectedItems(); updateStepControls(); }
            });
        });

        // Wire up column filters
        itemsContainer.querySelectorAll('[data-so-item-filter]').forEach(function (input) {
            input.addEventListener('input', function () { renderSelectedItems(); });
        });
        updateStepControls();
    }

    // ========== BUILD REVIEW ==========
    function buildReview() {
        var activeTab = root.querySelector('.so-review-tab.is-active');
        var isVat = activeTab && activeTab.dataset.soReviewTab === 'with-vat';
        var docTitle = isVat ? 'A-Sales Order' : 'Sales Invoice';
        var transactionType = isVat ? 'VAT EX' : 'NO VAT';
        var unitHeader = isVat ? 'VAT Ex Unit Price' : 'Unit Price';
        var totalHeader = isVat ? 'VAT Ex Total Price' : 'Total Price';
        var totalLabel = isVat ? 'VAT EX TOTAL' : 'TOTAL AMOUNT';
        var preparedBy = previewOrder ? (previewOrder.prepared_by_name_snapshot || '--') : '--';
        var paymentStatus = previewOrder ? (previewOrder.payment_status || 'Unpaid') : 'Unpaid';
        var rowCount = Math.max(25, selectedItems.length);
        var displayTotal = 0;
        var rowsHtml = '';

        for (var i = 0; i < rowCount; i++) {
            var item = selectedItems[i];
            if (item) {
                var unitPrice = isVat ? vatExclusiveUnit(item) : Number(item.selling_price || 0);
                var itemTotal = isVat ? vatExclusiveDiscountedLine(item) : discountedLineTotal(item);
                displayTotal += itemTotal;
                var displayDiscUnitPrice = isVat ? vatExclusiveDiscountedUnit(item) : discountedUnitPrice(item);
                rowsHtml += '<tr><td class="center">' + (i + 1) + '</td><td class="desc">' + escapeHtml(((item.product || '') + ' ' + (item.brand || '')).trim().toUpperCase()) + '</td><td class="center">' + Number(item.ordered_qty || 0).toFixed(0) + '</td><td class="center">' + escapeHtml(item.unit || '') + '</td><td class="right">' + unitPrice.toFixed(2) + '</td><td class="right">' + itemTotal.toFixed(2) + '</td></tr>';
            } else {
                rowsHtml += '<tr><td class="center"></td><td></td><td></td><td></td><td></td><td></td></tr>';
            }
        }

        if (!printPreview) return;
        printPreview.innerHTML =
            '<div class="so-preview-page">' +
                '<section class="so-preview-top">' +
                    '<div class="so-preview-company">' +
                        '<img class="so-preview-logo" src="' + escapeHtml(logoUrl) + '" alt="CONTROL A logo">' +
                        '<div><h1>CONTROL A TRADING AND SERVICES CORP.</h1>' +
                        '<div class="so-preview-company-lines"><div>601-163-860-00000</div><div>728 GENERAL LUIS ST. CAYBIGA CALOOCAN CITY</div><div>0945 825 8802</div></div></div>' +
                    '</div>' +
                    '<aside><div class="so-preview-title">' + docTitle + '</div>' +
                    '<div class="so-preview-info">' +
                        '<div class="label">Transaction Type:</div><div class="value">' + transactionType + '</div>' +
                        '<div class="label">S.O. No.:</div><div class="value">' + escapeHtml(soNoDisplay.value || '(Auto)') + '</div>' +
                        '<div class="label">Date:</div><div class="value">' + escapeHtml(soDateDisplay.value || '') + '</div>' +
                        '<div class="label">Prepared by:</div><div class="value">' + escapeHtml(preparedBy) + '</div>' +
                        '<div class="label">Sales Channel:</div><div class="value">' + escapeHtml((salesChannel.value || '--').toUpperCase()) + '</div>' +
                        '<div class="label">Payment Status:</div><div class="value">' + escapeHtml(paymentStatus.toUpperCase()) + '</div>' +
                        '<div class="label">Time:</div><div class="value">--</div>' +
                    '</div></aside>' +
                '</section>' +
                '<section class="so-preview-sold">' +
                    '<div><strong>Sold To:</strong></div><div>' + escapeHtml(((selectedCustomer && selectedCustomer.customer_name) || '').toUpperCase()) + '</div>' +
                    '<div><strong>TIN No:</strong></div><div>' + escapeHtml((selectedCustomer && selectedCustomer.tin) || '') + '</div>' +
                    '<div><strong>Address:</strong></div><div>' + escapeHtml(((selectedCustomer && selectedCustomer.address) || '').toUpperCase()) + '</div>' +
                '</section>' +
                '<table class="so-preview-order-table"><thead><tr><th style="width:13mm;">Item No:</th><th>Item Description</th><th style="width:13mm;">Qty</th><th style="width:15mm;">Unit</th><th style="width:23mm;">' + unitHeader + '</th><th style="width:25mm;">' + totalHeader + '</th></tr></thead><tbody>' + rowsHtml + '</tbody></table>' +
                '<section class="so-preview-bottom">' +
                    '<div class="so-preview-total"><div class="green">' + totalLabel + '</div><div class="green amount">' + displayTotal.toFixed(2) + '</div><div>PREPARED BY:</div><div></div><div>CHECKED BY:</div><div></div></div>' +
                '</section>' +
            '</div>';
    }

    // ========== EVENTS ==========
    // Create button
    createBtn.addEventListener('click', function () {
        resetModal();
        openModal(modal);
    });

    // Modal close
    modalClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(modal); }); });

    // Customer search
    customerSearch.addEventListener('input', function () { loadCustomers(1, getCustomerSearchData(), this.value); });
    customerColSearches.forEach(function (input) {
        input.addEventListener('input', function () { loadCustomers(1, getCustomerSearchData(), customerSearch.value); });
    });

    // Customer modal (delegated — button gets replaced by resetModal)
    customerDisplay.addEventListener('click', function (e) {
        if (e.target.closest('[data-so-select-customer]')) { openModal(customerModal); loadCustomers(1); }
        if (e.target.closest('[data-so-clear-customer]')) { selectedCustomer = null; updateCustomerDisplay(); }
    });
    customerModalClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(customerModal); }); });

    // Product search
    productSearch.addEventListener('input', function () { loadProducts(1, getProductSearchData(), this.value); });
    productColSearches.forEach(function (input) {
        input.addEventListener('input', function () { loadProducts(1, getProductSearchData(), productSearch.value); });
    });

    // Product modal
    selectItemsBtn.addEventListener('click', function () { openModal(productModal); loadProducts(1); });
    productModalClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(productModal); }); });
    productApplyBtn.addEventListener('click', function () { closeModal(productModal); renderSelectedItems(); });

    // Items search
    if (itemsSearch) { itemsSearch.addEventListener('input', function () { renderSelectedItems(); }); }

    // Step back
    stepBack.addEventListener('click', function () { if (currentStep > 1) goToStep(currentStep - 1); });

    // Step next
    stepNext.addEventListener('click', function () {
        if (currentStep === 1) {
            if (!selectedCustomer) { showNotice('Please select a customer.', 'error'); return; }
            if (!salesChannel.value) { showNotice('Please select a sales channel.', 'error'); return; }
            goToStep(2);
        } else if (currentStep === 2) {
            if (selectedItems.length === 0) { showNotice('Please select at least one product.', 'error'); return; }
            if (hasStockIssues()) { showNotice('Fix ordered quantities before continuing. Ordered QTY must not exceed available QTY.', 'error'); updateStepControls(); return; }
            buildReview();
            goToStep(3);
        } else if (currentStep === 3) {
            confirmTitle.textContent = editingId ? 'Confirm Update' : 'Confirm Sales Order';
            confirmMessage.textContent = editingId ? 'Are you sure you want to update this sales order?' : 'Are you sure you want to create this sales order? Stock will be deducted.';
            confirmProceed.textContent = editingId ? 'Yes, Update' : 'Yes, Create';
            openModal(confirmModal);
        }
    });

    // Review tabs
    reviewTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            reviewTabs.forEach(function (t) { t.classList.remove('is-active'); });
            this.classList.add('is-active');
            buildReview();
        });
    });

    // Confirm proceed
    confirmProceed.addEventListener('click', function () {
        if (editingId) {
            updateOrder();
        } else {
            createOrder();
        }
    });

    confirmClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(confirmModal); }); });

    // Success close
    successClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(successModal); reloadPage(); }); });

    // Print modal
    printModalClose.forEach(function (el) { el.addEventListener('click', function () { closeModal(printModal); }); });
    printActions.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var orderId = printModal.dataset.soPrintOrderId;
            var type = this.dataset.soPrintAction;
            var url;
            if (type === 'sales-order') url = printSoUrlTemplate.replace('__SALES_ORDER_ID__', orderId);
            else if (type === 'sales-invoice') url = printSiUrlTemplate.replace('__SALES_ORDER_ID__', orderId);
            else url = printBothUrlTemplate.replace('__SALES_ORDER_ID__', orderId);
            window.open(url, '_blank');
        });
    });

    // (Delete event listener removed)

    // Price filter
    priceFilter.addEventListener('change', function () {
        var url = new URL(window.location.href);
        if (this.value) url.searchParams.set('price_filter', this.value);
        else url.searchParams.delete('price_filter');
        window.location.href = url.toString();
    });

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

    // Column search
    root.querySelectorAll('[data-so-col-search]').forEach(function (input) {
        input.addEventListener('change', function () {
            var url = new URL(window.location.href);
            var name = this.name;
            if (this.value) url.searchParams.set(name, this.value);
            else url.searchParams.delete(name);
            window.location.href = url.toString();
        });
    });

    // Global search
    var globalSearch = root.querySelector('[data-so-search-form] input[name="q"]');
    if (globalSearch) {
        globalSearch.addEventListener('change', function () {
            this.form.submit();
        });
    }

    // ========== CREATE ORDER ==========
    function createOrder() {
        if (hasStockIssues()) { showNotice('Fix ordered quantities before creating the sales order.', 'error'); goToStep(2); return; }
        var items = selectedItems.map(function (i) { return { product_id: i.product_id, ordered_qty: i.ordered_qty, discount_percent: i.discount_percent || 0 }; });
        var body = { customer_id: selectedCustomer.id, sales_channel: salesChannel.value, items: items };

        closeModal(confirmModal);
        fetch(storeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) })
            .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
            .then(function (res) {
                if (res.status === 201) {
                    closeModal(modal);
                    successTitle.textContent = 'Sales Order Created Successfully';
                    successMessage.textContent = 'Sales Order #' + res.data.sales_order.so_no + ' has been created and stock has been deducted.';
                    openModal(successModal);
                } else {
                    showNotice(res.data.message || 'Failed to create sales order.', 'error');
                }
            })
            .catch(function () { showNotice('An error occurred while creating the sales order.', 'error'); });
    }

    // (View and Edit event listeners removed — main table no longer shows these actions)

    // ========== UPDATE ORDER ==========
    function updateOrder() {
        if (hasStockIssues()) { showNotice('Fix ordered quantities before updating the sales order.', 'error'); goToStep(2); return; }
        var items = selectedItems.map(function (i) { return { product_id: i.product_id, ordered_qty: i.ordered_qty, discount_percent: i.discount_percent || 0 }; });
        var body = { customer_id: selectedCustomer.id, sales_channel: salesChannel.value, items: items, _method: 'PUT' };

        closeModal(confirmModal);
        var url = updateUrlTemplate.replace('__SALES_ORDER_ID__', editingId);
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) })
            .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
            .then(function (res) {
                if (res.status === 200) {
                    closeModal(modal);
                    successTitle.textContent = 'Sales Order Updated Successfully';
                    successMessage.textContent = 'Sales Order #' + res.data.sales_order.so_no + ' has been updated.';
                    openModal(successModal);
                } else {
                    showNotice(res.data.message || 'Failed to update.', 'error');
                }
            })
            .catch(function () { showNotice('An error occurred.', 'error'); });
    }

    // ========== PRINT ==========
    root.addEventListener('click', function (e) {
        var printBtn = e.target.closest('[data-so-print]');
        if (!printBtn) return;
        var row = printBtn.closest('[data-so-row]');
        if (!row) return;
        printModal.dataset.soPrintOrderId = row.dataset.soId;
        openModal(printModal);
    });

    // (Delete/Cancel event listener removed)

    // (Dropdown toggle event listener removed)
});
