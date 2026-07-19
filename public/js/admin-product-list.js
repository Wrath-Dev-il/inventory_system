document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-product-list]');

    if (!root) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const rows = Array.from(root.querySelectorAll('[data-product-row]'));
    const viewButton = root.querySelector('[data-product-view-button]');
    const deleteButton = root.querySelector('[data-product-delete-button]');
    const saveEditButton = root.querySelector('[data-product-save-edits]');
    const addButton = root.querySelector('[data-product-add-button]');
    const filterButton = root.querySelector('[data-product-filter-button]');
    const viewModal = root.querySelector('[data-product-view-modal]');
    const addModal = root.querySelector('[data-product-add-modal]');
    const deleteModal = root.querySelector('[data-product-delete-modal]');
    const successModal = root.querySelector('[data-product-success-modal]');
    const addForm = root.querySelector('[data-product-add-form]');
    const tableShell = root.querySelector('.product-table-shell');
    const closeViewButtons = root.querySelectorAll('[data-product-modal-close]');
    const closeAddButtons = root.querySelectorAll('[data-product-add-close]');
    const closeDeleteButtons = root.querySelectorAll('[data-product-delete-close]');
    const closeSuccessButtons = root.querySelectorAll('[data-product-success-close]');
    const confirmDeleteButton = root.querySelector('[data-product-delete-confirm]');
    const confirmDeleteText = root.querySelector('[data-product-delete-confirm-text]');
    const deleteItemNo = root.querySelector('[data-product-delete-item-no]');
    const deleteProductName = root.querySelector('[data-product-delete-name]');
    const deleteError = root.querySelector('[data-product-delete-error]');
    const successTitle = root.querySelector('[data-product-success-title]');
    const successMessage = root.querySelector('[data-product-success-message]');
    const successDetails = root.querySelector('[data-product-success-details]');
    const filterSelect = root.querySelector('[data-product-filter]');
    const globalSearch = root.querySelector('[data-product-global-search]');
    const columnSearches = Array.from(root.querySelectorAll('[data-column-search]'));
    const notice = root.querySelector('[data-product-notice]');
    const detailsUrlTemplate = root.dataset.productDetailsUrlTemplate || '';
    const storeUrl = root.dataset.productStoreUrl || '';
    const updateUrl = root.dataset.productUpdateUrl || '';
    const destroyUrlTemplate = root.dataset.productDestroyUrlTemplate || '';
    const stockStatus = root.querySelector('[data-product-stock-status]');
    const profitCard = root.querySelector('[data-profit-card]');
    const pendingEdits = new Map();
    let selectedProductId = null;
    let selectedRow = null;
    let productPendingDelete = null;
    let isDeletingProduct = false;
    let searchTimer = null;
    const successStorageKey = 'adminProductListSuccess';

    document.documentElement.dataset.adminProductListReady = 'true';

    function isMissing(value) {
        return value === null || value === undefined || value === '';
    }

    function displayText(value) {
        return isMissing(value) ? '\u2014' : String(value);
    }

    function numberValue(value) {
        if (isMissing(value)) {
            return null;
        }

        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function formatNumber(value) {
        const parsed = numberValue(value);
        return parsed === null ? '\u2014' : new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(parsed);
    }

    function formatCurrency(value, symbol) {
        const parsed = numberValue(value);

        if (parsed === null) {
            return '\u2014';
        }

        const formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Math.abs(parsed));

        return `${parsed < 0 ? '-' : ''}${symbol} ${formatted}`;
    }

    function formatPeso(value) {
        return formatCurrency(value, '\u20b1');
    }

    function formatYuan(value) {
        return formatCurrency(value, '\u00a5');
    }

    function formatTableCurrency(value, code) {
        const parsed = numberValue(value);

        if (parsed === null) {
            return '\u2014';
        }

        return `${code} ${new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(parsed)}`;
    }

    function formatPercentage(value, unavailableReason) {
        const parsed = numberValue(value);

        if (unavailableReason === 'zero_total_cost') {
            return 'N/A';
        }

        if (parsed === null) {
            return '\u2014';
        }

        return `${parsed.toFixed(2)}%`;
    }

    function showNotice(message, type) {
        if (!notice) {
            return;
        }

        notice.textContent = message;
        notice.hidden = false;
        notice.classList.toggle('is-error', type === 'error');
    }

    function hideNotice() {
        if (notice) {
            notice.hidden = true;
        }
    }

    function isTextEntryTarget(target) {
        if (!(target instanceof HTMLElement)) {
            return false;
        }

        return Boolean(target.closest('input, textarea, select, [contenteditable="true"]'));
    }

    function scrollProductTableSideways(direction) {
        if (!tableShell) {
            return;
        }

        const distance = Math.max(120, Math.round(tableShell.clientWidth * 0.35));

        tableShell.scrollBy({
            left: direction * distance,
            behavior: 'smooth',
        });
    }

    function isProductModalOpen() {
        return [viewModal, addModal, deleteModal, successModal].some(function (modal) {
            return modal && !modal.hidden;
        });
    }

    function closeProductSuccessModal() {
        closeModal(successModal);
    }

    function showProductSuccessModal(config) {
        if (!successModal) {
            return;
        }

        if (successTitle) {
            successTitle.textContent = config.title || 'Product Action Completed';
        }

        if (successMessage) {
            successMessage.textContent = config.message || 'The product action completed successfully.';
        }

        if (successDetails) {
            successDetails.textContent = '';
            const details = Array.isArray(config.details) ? config.details.filter(function (detail) {
                return detail && !isMissing(detail.value);
            }) : [];

            details.forEach(function (detail) {
                const label = document.createElement('dt');
                const value = document.createElement('dd');
                label.textContent = detail.label;
                value.textContent = detail.value;
                successDetails.append(label, value);
            });

            successDetails.hidden = details.length === 0;
        }

        openModal(successModal);
    }

    function queueProductSuccessModal(config) {
        try {
            sessionStorage.setItem(successStorageKey, JSON.stringify(config));
        } catch (error) {
            return;
        }
    }

    function consumeQueuedProductSuccessModal() {
        let queued = null;

        try {
            queued = sessionStorage.getItem(successStorageKey);
            sessionStorage.removeItem(successStorageKey);
        } catch (error) {
            return;
        }

        if (!queued) {
            return;
        }

        try {
            showProductSuccessModal(JSON.parse(queued));
        } catch (error) {
            return;
        }
    }

    async function fetchJson(url, options) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options?.headers || {}),
            },
            ...options,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const firstError = payload.errors
                ? Object.values(payload.errors).flat()[0]
                : null;

            throw new Error(firstError || payload.message || 'The product request failed.');
        }

        return payload;
    }

    function detailsUrl(productId) {
        return detailsUrlTemplate.replace('__PRODUCT_ID__', encodeURIComponent(productId));
    }

    function destroyUrl(productId) {
        return destroyUrlTemplate.replace('__PRODUCT_ID__', encodeURIComponent(productId));
    }

    function setDetail(name, value) {
        root.querySelectorAll(`[data-product-detail="${name}"]`).forEach(function (node) {
            node.textContent = value;
        });
    }

    function renderProduct(product) {
        setDetail('item_no', displayText(product.item_no));
        setDetail('product', displayText(product.product));
        setDetail('brand', displayText(product.brand));
        setDetail('unit', displayText(product.unit));
        setDetail('qty', formatNumber(product.qty));
        setDetail('restock_level', formatNumber(product.restock_level));
        setDetail('item_source', displayText(product.item_source));
        setDetail('cost_yuan', formatYuan(product.cost_yuan));
        setDetail('cost_peso', formatPeso(product.cost_peso));
        setDetail('selling_price', formatPeso(product.selling_price));
        setDetail('price_online', formatPeso(product.price_online));
        setDetail('sea_freight', formatPeso(product.sea_freight));
        setDetail('air_freight', formatPeso(product.air_freight));
        setDetail('total_cost', formatPeso(product.total_cost));
        setDetail('item_selling_price', formatPeso(product.item_selling_price));
        setDetail('estimated_profit', formatPeso(product.estimated_profit));
        setDetail('markup', formatPercentage(product.markup, product.markup_unavailable_reason));

        const stock = product.stock_status || { label: 'Unknown', tone: 'unknown' };

        if (stockStatus) {
            stockStatus.textContent = stock.label || 'Unknown';
            stockStatus.className = `stock-status stock-status--${stock.tone || 'unknown'}`;
        }

        if (profitCard) {
            profitCard.classList.toggle('is-negative', numberValue(product.estimated_profit) < 0);
        }
    }

    function updateProductActionButtons() {
        const hasSelection = Boolean(selectedProductId);

        if (viewButton) {
            viewButton.disabled = !hasSelection;
        }

        if (deleteButton) {
            deleteButton.disabled = !hasSelection;
        }
    }

    function selectedProductSnapshot() {
        if (!selectedRow || !selectedProductId) {
            return null;
        }

        const itemNoCell = selectedRow.querySelector('[data-field="item_no"]');
        const productCell = selectedRow.querySelector('[data-field="product"]');

        return {
            id: selectedProductId,
            itemNo: itemNoCell?.dataset.value || itemNoCell?.textContent?.trim() || '',
            name: productCell?.dataset.pendingValue || productCell?.dataset.value || productCell?.textContent?.trim() || '',
        };
    }

    function clearSelectedProduct() {
        if (selectedRow) {
            selectedRow.classList.remove('is-selected');
            selectedRow.removeAttribute('aria-selected');
        }

        selectedRow = null;
        selectedProductId = null;
        updateProductActionButtons();
    }

    function setSelectedRow(row) {
        if (selectedRow) {
            selectedRow.classList.remove('is-selected');
            selectedRow.removeAttribute('aria-selected');
        }

        selectedRow = row;
        selectedProductId = row?.dataset.productId || null;

        if (selectedRow) {
            selectedRow.classList.add('is-selected');
            selectedRow.setAttribute('aria-selected', 'true');
        }

        updateProductActionButtons();
    }

    function openModal(modal) {
        if (!modal) {
            return;
        }

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('button, input, select')?.focus();
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.hidden = true;
        document.body.style.overflow = '';
    }

    async function openSelectedProduct() {
        if (!selectedProductId || !viewButton) {
            return;
        }

        hideNotice();
        viewButton.disabled = true;

        try {
            const payload = await fetchJson(detailsUrl(selectedProductId), { method: 'GET' });
            renderProduct(payload.product);
            openModal(viewModal);
        } catch (error) {
            showNotice(error.message, 'error');
        } finally {
            updateProductActionButtons();
        }
    }

    function showDeleteError(message) {
        if (!deleteError) {
            showNotice(message, 'error');
            return;
        }

        deleteError.textContent = message;
        deleteError.hidden = false;
    }

    function clearDeleteError() {
        if (deleteError) {
            deleteError.hidden = true;
            deleteError.textContent = '';
        }
    }

    function openDeleteProductModal() {
        if (!selectedProductId || !deleteModal) {
            return;
        }

        productPendingDelete = selectedProductSnapshot();

        if (!productPendingDelete) {
            return;
        }

        hideNotice();
        clearDeleteError();

        if (deleteItemNo) {
            deleteItemNo.textContent = displayText(productPendingDelete.itemNo);
        }

        if (deleteProductName) {
            deleteProductName.textContent = displayText(productPendingDelete.name);
        }

        openModal(deleteModal);
    }

    function closeDeleteProductModal() {
        if (isDeletingProduct) {
            return;
        }

        closeModal(deleteModal);
    }

    function refreshUrlAfterDelete(remainingRowsOnPage) {
        const url = new URL(window.location.href);
        const page = Number(url.searchParams.get('page') || '1');

        if (remainingRowsOnPage <= 0 && page > 1) {
            url.searchParams.set('page', String(page - 1));
        }

        url.hash = '';
        return url.toString();
    }

    function clearPendingEditsForProduct(productId) {
        Array.from(pendingEdits.keys()).forEach(function (key) {
            if (key.startsWith(`${productId}:`)) {
                pendingEdits.delete(key);
            }
        });

        updateSaveEditState();
    }

    async function confirmDeleteProduct() {
        if (!productPendingDelete || !confirmDeleteButton || isDeletingProduct) {
            return;
        }

        const productToDelete = productPendingDelete;
        const rowsBeforeDelete = root.querySelectorAll('[data-product-row]').length;
        isDeletingProduct = true;
        clearDeleteError();
        confirmDeleteButton.disabled = true;

        if (confirmDeleteText) {
            confirmDeleteText.textContent = 'Deleting...';
        }

        try {
            const payload = await fetchJson(destroyUrl(productToDelete.id), { method: 'DELETE' });
            const deletedProduct = payload.product || {};
            const itemNo = deletedProduct.item_no || productToDelete.itemNo;
            const productName = deletedProduct.product || productToDelete.name;

            updateStats(payload.stats);
            clearPendingEditsForProduct(productToDelete.id);
            closeModal(deleteModal);

            if (selectedRow) {
                selectedRow.remove();
            }

            clearSelectedProduct();
            queueProductSuccessModal({
                title: 'Product Deleted Successfully',
                message: 'The selected product has been removed from the inventory successfully.',
                details: [
                    { label: 'Item No', value: itemNo },
                    { label: 'Product', value: productName },
                ],
            });

            window.location.href = refreshUrlAfterDelete(rowsBeforeDelete - 1);
        } catch (error) {
            showDeleteError(error.message || 'Unable to delete product.');
        } finally {
            isDeletingProduct = false;
            confirmDeleteButton.disabled = false;

            if (confirmDeleteText) {
                confirmDeleteText.textContent = 'Delete Product';
            }
        }
    }

    function submitSearch(form) {
        if (!form) {
            return;
        }

        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(function () {
            const pageField = form.querySelector('input[name="page"]');
            if (pageField) {
                pageField.remove();
            }

            syncSearchForm(form);
            form.requestSubmit();
        }, 350);
    }

    function setHidden(form, name, value) {
        const existing = Array.from(form.elements).find(function (element) {
            return element.name === name;
        });

        if (existing) {
            if (existing.type === 'hidden' || existing.dataset.syncHidden === 'true') {
                existing.value = value || '';
            }

            return;
        }

        let input = form.querySelector(`input[type="hidden"][name="${name}"][data-sync-hidden="true"]`);

        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.dataset.syncHidden = 'true';
            form.appendChild(input);
        }

        input.value = value || '';
    }

    function syncSearchForm(form) {
        if (filterSelect) {
            setHidden(form, 'filter', filterSelect.value);
        }

        if (globalSearch) {
            setHidden(form, 'q', globalSearch.value);
        }

        columnSearches.forEach(function (input) {
            setHidden(form, input.name, input.value);
        });
    }

    function editKey(id, field) {
        return `${id}:${field}`;
    }

    function updateSaveEditState() {
        if (saveEditButton) {
            saveEditButton.disabled = pendingEdits.size === 0;
        }
    }

    function normalizeComparable(value, type) {
        if (type === 'number' || type === 'money') {
            const parsed = numberValue(value);
            return parsed === null ? '' : String(parsed);
        }

        return String(value ?? '').trim();
    }

    function startCellEdit(cell) {
        const row = cell.closest('[data-product-row]');

        if (!row || cell.querySelector('input')) {
            return;
        }

        setSelectedRow(row);

        const originalValue = cell.dataset.value ?? '';
        const type = cell.dataset.type || 'text';
        const input = document.createElement('input');
        input.className = 'product-edit-input';
        input.type = type === 'text' ? 'text' : 'number';
        input.step = type === 'money' ? '0.01' : '1';
        input.min = type === 'text' ? '' : '0';
        input.value = originalValue;

        cell.textContent = '';
        cell.appendChild(input);
        input.focus();
        input.select();
        let canceled = false;

        function commit() {
            if (canceled) {
                return;
            }

            const nextValue = input.value;
            const field = cell.dataset.field;
            const id = row.dataset.productId;
            const key = editKey(id, field);
            const changed = normalizeComparable(originalValue, type) !== normalizeComparable(nextValue, type);

            if (changed) {
                pendingEdits.set(key, { id, field, value: nextValue });
                cell.classList.add('is-changed');
            } else {
                pendingEdits.delete(key);
                cell.classList.remove('is-changed');
            }

            cell.dataset.pendingValue = nextValue;
            cell.textContent = formatCellValue(field, nextValue);
            updateSaveEditState();
        }

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                input.blur();
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                canceled = true;
                cell.textContent = formatCellValue(cell.dataset.field, originalValue);
            }
        });

        input.addEventListener('blur', commit, { once: true });
    }

    function formatCellValue(field, value) {
        if (field === 'cost_in_yuan') {
            return formatTableCurrency(value, '\u00a5');
        }

        if (['cost_in_peso', 'selling_price', 'price_online'].includes(field)) {
            return formatTableCurrency(value, '\u20b1');
        }

        if (['qty', 'restock_level'].includes(field)) {
            return formatNumber(value);
        }

        return displayText(value);
    }

    function updateStats(stats) {
        if (!stats) {
            return;
        }

        const values = {
            total_products: formatNumber(stats.total_products),
            high_stocks: formatNumber(stats.high_stocks),
            low_stocks: formatNumber(stats.low_stocks),
            average_cost: stats.average_cost === null ? 'N/A' : formatTableCurrency(stats.average_cost, '\u20b1'),
            average_gross_profit: stats.average_gross_profit === null ? 'N/A' : formatTableCurrency(stats.average_gross_profit, '\u20b1'),
        };

        Object.entries(values).forEach(function ([key, value]) {
            const node = root.querySelector(`[data-stat="${key}"]`);
            if (node) {
                node.textContent = value;
            }
        });
    }

    function updateRow(product) {
        const row = root.querySelector(`[data-product-row][data-product-id="${product.id}"]`);

        if (!row) {
            return;
        }

        row.classList.remove('product-row--low', 'product-row--near', 'product-row--high', 'product-row--unknown');
        row.classList.add(`product-row--${product.stock_status?.tone || 'unknown'}`);

        const values = {
            product: product.product,
            brand: product.brand,
            unit: product.unit,
            qty: product.qty,
            restock_level: product.restock_level,
            item_source: product.item_source,
            cost_in_yuan: product.cost_in_yuan,
            cost_in_peso: product.cost_in_peso,
            selling_price: product.selling_price,
            price_online: product.price_online,
        };

        Object.entries(values).forEach(function ([field, value]) {
            const cell = row.querySelector(`[data-field="${field}"]`);
            if (!cell) {
                return;
            }

            cell.dataset.value = value ?? '';
            cell.textContent = formatCellValue(field, value);
            cell.classList.remove('is-changed');
        });
    }

    async function savePendingEdits() {
        if (!pendingEdits.size || !saveEditButton) {
            return;
        }

        hideNotice();
        saveEditButton.disabled = true;

        try {
            const payload = await fetchJson(updateUrl, {
                method: 'PATCH',
                body: JSON.stringify({ edits: Array.from(pendingEdits.values()) }),
            });

            (payload.products || []).forEach(updateRow);
            updateStats(payload.stats);
            pendingEdits.clear();
            updateSaveEditState();
            showProductSuccessModal({
                title: 'Product Changes Saved',
                message: 'Your product changes have been saved successfully.',
                details: [
                    {
                        label: 'Updated',
                        value: `${(payload.products || []).length} ${(payload.products || []).length === 1 ? 'product' : 'products'} updated successfully.`,
                    },
                ],
            });
        } catch (error) {
            showNotice(error.message, 'error');
            updateSaveEditState();
        }
    }

    async function saveNewProduct(event) {
        event.preventDefault();

        if (!addForm) {
            return;
        }

        hideNotice();

        addCostInputModeToForm(addForm);
        const formData = new FormData(addForm);
        const body = Object.fromEntries(formData.entries());
        const submitButton = addForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            const payload = await fetchJson(storeUrl, {
                method: 'POST',
                body: JSON.stringify(body),
            });
            const createdProduct = (payload.products || [])[0] || {};
            const nextItemNo = addForm.querySelector('[data-next-item-no]');

            closeModal(addModal);
            addForm.reset();

            if (nextItemNo && payload.next_item_no) {
                nextItemNo.value = payload.next_item_no;
            }

            queueProductSuccessModal({
                title: 'Product Added Successfully',
                message: 'The product has been added to the inventory successfully.',
                details: [
                    { label: 'Item No', value: createdProduct.item_no },
                    { label: 'Product', value: createdProduct.product },
                ],
            });

            window.location.href = window.location.href.split('#')[0];
        } catch (error) {
            showNotice(error.message, 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    rows.forEach(function (row) {
        row.addEventListener('click', function () {
            setSelectedRow(row);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                setSelectedRow(row);
            }
        });
    });

    root.querySelectorAll('[data-editable]').forEach(function (cell) {
        cell.addEventListener('click', function (event) {
            event.stopPropagation();
            startCellEdit(cell);
        });
    });

    if (viewButton) {
        viewButton.addEventListener('click', openSelectedProduct);
    }

    if (deleteButton) {
        deleteButton.addEventListener('click', openDeleteProductModal);
    }

    if (saveEditButton) {
        saveEditButton.addEventListener('click', savePendingEdits);
    }

    if (addButton) {
        addButton.addEventListener('click', function () {
            addForm?.reset();
            resetProductCostState();
            openModal(addModal);
        });
    }

    if (filterButton) {
        filterButton.addEventListener('click', function () {
            filterSelect?.focus();
        });
    }

    if (addForm) {
        addForm.addEventListener('submit', saveNewProduct);
    }

    closeViewButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(viewModal);
            viewButton?.focus();
        });
    });

    closeAddButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(addModal);
            addForm?.reset();
            resetProductCostState();
            addButton?.focus();
        });
    });

    closeDeleteButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeDeleteProductModal();
            deleteButton?.focus();
        });
    });

    closeSuccessButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeProductSuccessModal();
        });
    });

    if (confirmDeleteButton) {
        confirmDeleteButton.addEventListener('click', confirmDeleteProduct);
    }

    if (filterSelect) {
        filterSelect.addEventListener('change', function () {
            submitSearch(filterSelect.form);
        });
    }

    if (globalSearch) {
        globalSearch.addEventListener('input', function () {
            submitSearch(globalSearch.form);
        });
    }

    columnSearches.forEach(function (input) {
        input.addEventListener('input', function () {
            submitSearch(input.form);
        });
    });

    document.addEventListener('keydown', function (event) {
        if ((event.key === 'ArrowLeft' || event.key === 'ArrowRight') && !isTextEntryTarget(event.target) && !isProductModalOpen()) {
            event.preventDefault();
            scrollProductTableSideways(event.key === 'ArrowRight' ? 1 : -1);
            return;
        }

        if (event.key !== 'Escape') {
            return;
        }

        if (viewModal && !viewModal.hidden) {
            closeModal(viewModal);
        }

        if (addModal && !addModal.hidden) {
            closeModal(addModal);
        }

        if (deleteModal && !deleteModal.hidden) {
            closeDeleteProductModal();
        }

        if (successModal && !successModal.hidden) {
            closeProductSuccessModal();
        }
    });

    let isUpdatingCostFields = false;
    let lastEditedCostField = null;

    function getSelectedItemSourceMultiplier() {
        const select = root.querySelector('[data-item-source-select]');
        if (!select) return null;
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) return null;
        const raw = option.dataset.multiplier;
        if (!raw) return null;
        const parsed = parseFloat(raw);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    }

    function updateMultiplierDisplay() {
        const display = root.querySelector('[data-multiplier-display]');
        const value = root.querySelector('[data-multiplier-value]');
        const noMultiplier = root.querySelector('[data-no-multiplier]');
        const saveButton = addForm?.querySelector('button[type="submit"]');
        const yuanInput = root.querySelector('[data-cost-yuan]');
        const pesoInput = root.querySelector('[data-cost-peso]');
        const multiplier = getSelectedItemSourceMultiplier();

        if (multiplier !== null) {
            if (display) display.hidden = false;
            if (value) value.textContent = '\u00d7' + multiplier;
            if (noMultiplier) noMultiplier.hidden = true;
            if (yuanInput) yuanInput.disabled = false;
            if (pesoInput) pesoInput.disabled = false;
            if (saveButton && addForm?.querySelector('[data-item-source-select]')?.selectedIndex > 0) saveButton.disabled = false;
            return;
        }

        const hasSelection = (function () {
            const s = root.querySelector('[data-item-source-select]');
            return s && s.selectedIndex > 0;
        })();

        if (hasSelection) {
            if (display) display.hidden = true;
            if (noMultiplier) noMultiplier.hidden = false;
            if (saveButton) saveButton.disabled = true;
        } else {
            if (display) display.hidden = true;
            if (noMultiplier) noMultiplier.hidden = true;
            if (saveButton) saveButton.disabled = false;
        }

        if (yuanInput) yuanInput.disabled = true;
        if (pesoInput) pesoInput.disabled = true;
    }

    function calculatePesoFromYuan(yuan, multiplier) {
        return yuan * multiplier;
    }

    function calculateYuanFromPeso(peso, multiplier) {
        return peso / multiplier;
    }

    function recalculateCosts(sourceField) {
        if (isUpdatingCostFields) return;
        isUpdatingCostFields = true;

        const multiplier = getSelectedItemSourceMultiplier();
        const yuanInput = root.querySelector('[data-cost-yuan]');
        const pesoInput = root.querySelector('[data-cost-peso]');

        if (multiplier === null || !yuanInput || !pesoInput) {
            isUpdatingCostFields = false;
            return;
        }

        const yuan = parseFloat(yuanInput.value) || 0;
        const peso = parseFloat(pesoInput.value) || 0;

        if (sourceField === 'yuan') {
            pesoInput.value = calculatePesoFromYuan(yuan, multiplier).toFixed(2);
        } else if (sourceField === 'peso') {
            yuanInput.value = calculateYuanFromPeso(peso, multiplier).toFixed(2);
        }

        isUpdatingCostFields = false;
    }

    function handleYuanInput() {
        lastEditedCostField = 'yuan';
        recalculateCosts('yuan');
    }

    function handlePesoInput() {
        lastEditedCostField = 'peso';
        recalculateCosts('peso');
    }

    function handleItemSourceChange() {
        const multiplier = getSelectedItemSourceMultiplier();
        updateMultiplierDisplay();

        if (multiplier === null || !lastEditedCostField) return;

        recalculateCosts(lastEditedCostField);
    }

    function resetProductCostState() {
        lastEditedCostField = null;
        isUpdatingCostFields = false;
        updateMultiplierDisplay();
    }

    function initializeProductCostInputs() {
        const select = root.querySelector('[data-item-source-select]');
        const yuanInput = root.querySelector('[data-cost-yuan]');
        const pesoInput = root.querySelector('[data-cost-peso]');

        if (!select) return;

        select.addEventListener('change', handleItemSourceChange);

        if (yuanInput) {
            yuanInput.addEventListener('input', handleYuanInput);
        }

        if (pesoInput) {
            pesoInput.addEventListener('input', handlePesoInput);
        }

        updateMultiplierDisplay();
    }

    function addCostInputModeToForm(form) {
        if (!form) return;
        const existing = form.querySelector('input[name="cost_input_mode"]');
        if (existing) existing.remove();
        if (lastEditedCostField) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'cost_input_mode';
            input.value = lastEditedCostField;
            form.appendChild(input);
        }
    }

    updateSaveEditState();
    updateProductActionButtons();
    consumeQueuedProductSuccessModal();
    initializeProductCostInputs();
});
