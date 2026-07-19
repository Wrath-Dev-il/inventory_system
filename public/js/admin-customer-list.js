document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-customer-list]');

    if (!root) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const rows = Array.from(root.querySelectorAll('[data-customer-row]'));
    const salesAgents = JSON.parse(root.dataset.salesAgents || '[]');
    const storeUrl = root.dataset.customerStoreUrl || '';
    const detailsUrlTemplate = root.dataset.customerDetailsUrlTemplate || '';
    const updateUrl = root.dataset.customerUpdateUrl || '';
    const destroyUrlTemplate = root.dataset.customerDestroyUrlTemplate || '';
    const addButton = root.querySelector('[data-customer-add-button]');
    const viewButton = root.querySelector('[data-customer-view-button]');
    const deleteButton = root.querySelector('[data-customer-delete-button]');
    const saveUpdateButton = root.querySelector('[data-customer-save-updates]');
    const addModal = root.querySelector('[data-customer-add-modal]');
    const viewModal = root.querySelector('[data-customer-view-modal]');
    const deleteModal = root.querySelector('[data-customer-delete-modal]');
    const successModal = root.querySelector('[data-customer-success-modal]');
    const addForm = root.querySelector('[data-customer-add-form]');
    const createCustomerButton = root.querySelector('[data-customer-create-button]');
    const tableShell = root.querySelector('.admin-customers__table-shell');
    const closeAddButtons = root.querySelectorAll('[data-customer-add-close]');
    const closeViewButtons = root.querySelectorAll('[data-customer-view-close]');
    const closeDeleteButtons = root.querySelectorAll('[data-customer-delete-close]');
    const closeSuccessButtons = root.querySelectorAll('[data-customer-success-close]');
    const confirmDeleteButton = root.querySelector('[data-customer-delete-confirm]');
    const confirmDeleteText = root.querySelector('[data-customer-delete-confirm-text]');
    const deleteNo = root.querySelector('[data-customer-delete-no]');
    const deleteName = root.querySelector('[data-customer-delete-name]');
    const deleteError = root.querySelector('[data-customer-delete-error]');
    const successTitle = root.querySelector('[data-customer-success-title]');
    const successMessage = root.querySelector('[data-customer-success-message]');
    const successDetails = root.querySelector('[data-customer-success-details]');
    const globalSearch = root.querySelector('[data-customer-global-search]');
    const columnSearches = Array.from(root.querySelectorAll('[data-customer-column-search]'));
    const notice = root.querySelector('[data-customer-notice]');
    const pendingEdits = new Map();
    const successStorageKey = 'adminCustomerListSuccess';
    let selectedCustomerId = null;
    let selectedRow = null;
    let customerPendingDelete = null;
    let isDeletingCustomer = false;
    let searchTimer = null;
    let greenDiscountDraft = addForm?.querySelector('input[name="discount_percent"]')?.value || '0';
    let lastSelectedPriceReference = null;

    document.documentElement.dataset.adminCustomerListReady = 'true';

    function isMissing(value) {
        return value === null || value === undefined || value === '';
    }

    function displayText(value) {
        return isMissing(value) ? '--' : String(value);
    }

    function numberValue(value) {
        if (isMissing(value)) {
            return null;
        }

        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function formatPercent(value) {
        const parsed = numberValue(value);
        return parsed === null ? '--' : `${parsed.toFixed(2)}%`;
    }

    function referenceLabel(value) {
        return value === 'yellow' ? 'Yellow' : 'Green';
    }

    function showNotice(message) {
        if (!notice) {
            return;
        }

        notice.textContent = message;
        notice.hidden = false;
    }

    function hideNotice() {
        if (notice) {
            notice.hidden = true;
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

            throw new Error(firstError || payload.message || 'The customer request failed.');
        }

        return payload;
    }

    function detailsUrl(customerId) {
        return detailsUrlTemplate.replace('__CUSTOMER_ID__', encodeURIComponent(customerId));
    }

    function destroyUrl(customerId) {
        return destroyUrlTemplate.replace('__CUSTOMER_ID__', encodeURIComponent(customerId));
    }

    function openModal(modal) {
        if (!modal) {
            return;
        }

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('button, input, select, textarea')?.focus();
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.hidden = true;
        document.body.style.overflow = '';
    }

    function getSelectedPriceReference() {
        return (addForm?.querySelector('input[name="price_reference"]:checked')?.value || '').toLowerCase();
    }

    function updateCustomerCreateButtonState() {
        if (!addForm || !createCustomerButton) {
            return;
        }

        const selected = getSelectedPriceReference();
        createCustomerButton.classList.remove('admin-customers__create-button--green', 'admin-customers__create-button--yellow');

        if (selected === 'green') {
            createCustomerButton.classList.add('admin-customers__create-button--green');
        }

        if (selected === 'yellow') {
            createCustomerButton.classList.add('admin-customers__create-button--yellow');
        }
    }

    function updateCustomerActionButtons() {
        const hasSelection = Boolean(selectedCustomerId);

        if (viewButton) {
            viewButton.disabled = !hasSelection;
        }

        if (deleteButton) {
            deleteButton.disabled = !hasSelection;
        }
    }

    function updateSaveUpdateButton() {
        if (saveUpdateButton) {
            saveUpdateButton.disabled = pendingEdits.size === 0;
        }
    }

    function setSelectedRow(row) {
        if (selectedRow) {
            selectedRow.classList.remove('is-selected');
            selectedRow.removeAttribute('aria-selected');
        }

        selectedRow = row;
        selectedCustomerId = row?.dataset.customerId || null;

        if (selectedRow) {
            selectedRow.classList.add('is-selected');
            selectedRow.setAttribute('aria-selected', 'true');
        }

        updateCustomerActionButtons();
    }

    function clearSelectedCustomer() {
        if (selectedRow) {
            selectedRow.classList.remove('is-selected');
            selectedRow.removeAttribute('aria-selected');
        }

        selectedRow = null;
        selectedCustomerId = null;
        updateCustomerActionButtons();
    }

    function selectedCustomerSnapshot() {
        if (!selectedRow || !selectedCustomerId) {
            return null;
        }

        const noCell = selectedRow.querySelector('[data-field="customer_no"]');
        const nameCell = selectedRow.querySelector('[data-field="customer_name"]');

        return {
            id: selectedCustomerId,
            customerNo: noCell?.dataset.value || noCell?.textContent?.trim() || '',
            customerName: nameCell?.dataset.pendingValue || nameCell?.dataset.value || nameCell?.textContent?.trim() || '',
        };
    }

    function setDetail(name, value) {
        root.querySelectorAll(`[data-customer-detail="${name}"]`).forEach(function (node) {
            node.textContent = value;
        });
    }

    function renderCustomerDetails(customer) {
        setDetail('customer_no', displayText(customer.customer_no));
        setDetail('customer_name', displayText(customer.customer_name));
        setDetail('tin', displayText(customer.tin));
        setDetail('price_reference_label', displayText(customer.price_reference_label));
        setDetail('discount_percent', formatPercent(customer.discount_percent));
        setDetail('sales_agent', displayText(customer.sales_agent));
        setDetail('date_started', displayText(customer.date_started));
        setDetail('terms', displayText(customer.terms));
        setDetail('address', displayText(customer.address));
    }

    async function openSelectedCustomer() {
        if (!selectedCustomerId || !viewButton) {
            return;
        }

        hideNotice();
        viewButton.disabled = true;

        try {
            const payload = await fetchJson(detailsUrl(selectedCustomerId), { method: 'GET' });
            renderCustomerDetails(payload.customer);
            openModal(viewModal);
        } catch (error) {
            showNotice(error.message);
        } finally {
            updateCustomerActionButtons();
        }
    }

    function updateDiscountState(form) {
        if (!form) {
            return;
        }

        const selected = getSelectedPriceReference() || 'green';
        const discount = form.querySelector('input[name="discount_percent"]');
        const hint = form.querySelector('[data-discount-hint]');

        if (!discount) {
            return;
        }

        if (selected === 'yellow') {
            if (lastSelectedPriceReference !== 'yellow') {
                greenDiscountDraft = discount.value || '';
            }

            discount.value = '20';
            discount.readOnly = true;
            if (hint) {
                hint.textContent = 'Auto 20% for Yellow Customer';
            }
            lastSelectedPriceReference = 'yellow';
            updateCustomerCreateButtonState();
            return;
        }

        if (lastSelectedPriceReference === 'yellow') {
            discount.value = greenDiscountDraft || '';
        } else {
            greenDiscountDraft = discount.value || '';
        }

        discount.readOnly = false;
        if (hint) {
            hint.textContent = 'Editable for Green Customer';
        }
        lastSelectedPriceReference = 'green';
        updateCustomerCreateButtonState();
    }

    function handlePriceReferenceChange() {
        updateDiscountState(addForm);
        updateCustomerCreateButtonState();
    }

    function showCustomerSuccessModal(config) {
        if (!successModal) {
            return;
        }

        if (successTitle) {
            successTitle.textContent = config.title || 'Customer Action Completed';
        }

        if (successMessage) {
            successMessage.textContent = config.message || 'The customer action completed successfully.';
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

    function closeCustomerSuccessModal() {
        closeModal(successModal);
    }

    function queueCustomerSuccessModal(config) {
        try {
            sessionStorage.setItem(successStorageKey, JSON.stringify(config));
        } catch (error) {
            return;
        }
    }

    function consumeQueuedCustomerSuccessModal() {
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
            showCustomerSuccessModal(JSON.parse(queued));
        } catch (error) {
            return;
        }
    }

    function updateStats(stats) {
        if (!stats) {
            return;
        }

        Object.entries(stats).forEach(function ([key, value]) {
            const node = root.querySelector(`[data-customer-stat="${key}"]`);
            if (node) {
                node.textContent = new Intl.NumberFormat('en-US').format(value || 0);
            }
        });
    }

    async function submitAddCustomer(event) {
        event.preventDefault();

        if (!addForm) {
            return;
        }

        hideNotice();
        updateDiscountState(addForm);

        const submitButton = addForm.querySelector('button[type="submit"]');
        const body = Object.fromEntries(new FormData(addForm).entries());
        const originalSubmitText = submitButton?.textContent || 'Save Customer';

        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        updateCustomerCreateButtonState();

        try {
            const payload = await fetchJson(storeUrl, {
                method: 'POST',
                body: JSON.stringify(body),
            });
            const customer = (payload.customers || [])[0] || {};
            const nextNo = addForm.querySelector('[data-next-customer-no]');

            closeModal(addModal);
            addForm.reset();
            updateDiscountState(addForm);

            if (nextNo && payload.next_customer_no) {
                nextNo.value = payload.next_customer_no;
            }

            queueCustomerSuccessModal({
                title: 'Customer Added Successfully',
                message: 'The customer has been added successfully.',
                details: [
                    { label: 'No', value: customer.customer_no },
                    { label: 'Customer', value: customer.customer_name },
                ],
            });

            window.location.href = window.location.href.split('#')[0];
        } catch (error) {
            showNotice(error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalSubmitText;
            updateCustomerCreateButtonState();
        }
    }

    function editKey(id, field) {
        return `${id}:${field}`;
    }

    function normalizeComparable(value, type) {
        if (type === 'number') {
            const parsed = numberValue(value);
            return parsed === null ? '' : String(parsed);
        }

        return String(value ?? '').trim();
    }

    function salesAgentName(id) {
        const agent = salesAgents.find(function (item) {
            return String(item.id) === String(id);
        });

        return agent ? agent.name : '--';
    }

    function formatCellValue(field, value) {
        if (field === 'price_reference') {
            const reference = value === 'yellow' ? 'yellow' : 'green';
            return `<span class="customer-reference-badge customer-reference-badge--${reference}">${referenceLabel(reference)}</span>`;
        }

        if (field === 'discount_percent') {
            return formatPercent(value);
        }

        if (field === 'sales_agent_id') {
            return displayText(salesAgentName(value));
        }

        return displayText(value);
    }

    function setCellDisplay(cell, field, value) {
        if (field === 'price_reference') {
            cell.innerHTML = formatCellValue(field, value);
            return;
        }

        cell.textContent = formatCellValue(field, value);
    }

    function createEditor(cell, field, type, originalValue) {
        if (type === 'price-reference') {
            const select = document.createElement('select');
            select.className = 'customer-edit-select';
            select.innerHTML = '<option value="green">Green</option><option value="yellow">Yellow</option>';
            select.value = originalValue || 'green';
            return select;
        }

        if (type === 'sales-agent') {
            const select = document.createElement('select');
            select.className = 'customer-edit-select';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'No sales agent assigned';
            select.appendChild(empty);
            salesAgents.forEach(function (agent) {
                const option = document.createElement('option');
                option.value = agent.id;
                option.textContent = `${agent.name} (${agent.agent_no})`;
                select.appendChild(option);
            });
            select.value = originalValue || '';
            return select;
        }

        const input = document.createElement('input');
        input.className = 'customer-edit-input';
        input.type = type === 'date' ? 'date' : (type === 'number' ? 'number' : 'text');
        input.step = type === 'number' ? '0.01' : '';
        input.min = type === 'number' ? '0' : '';
        input.max = type === 'number' ? '100' : '';
        input.value = originalValue || '';
        return input;
    }

    function beginCustomerCellEdit(cell) {
        const row = cell.closest('[data-customer-row]');

        if (!row || cell.querySelector('input, select')) {
            return;
        }

        setSelectedRow(row);

        const originalValue = cell.dataset.value ?? '';
        const field = cell.dataset.field;
        const type = cell.dataset.type || 'text';
        const editor = createEditor(cell, field, type, originalValue);
        let canceled = false;

        cell.textContent = '';
        cell.appendChild(editor);
        editor.focus();
        if (editor.select) {
            editor.select();
        }

        function commit() {
            if (canceled) {
                return;
            }

            let nextValue = editor.value;
            if (field === 'price_reference' && nextValue === 'yellow') {
                const discountCell = row.querySelector('[data-field="discount_percent"]');
                if (discountCell) {
                    pendingEdits.set(editKey(row.dataset.customerId, 'discount_percent'), {
                        id: row.dataset.customerId,
                        field: 'discount_percent',
                        value: 20,
                    });
                    discountCell.dataset.pendingValue = '20';
                    discountCell.classList.add('is-changed');
                    setCellDisplay(discountCell, 'discount_percent', 20);
                }
            }

            const key = editKey(row.dataset.customerId, field);
            const changed = normalizeComparable(originalValue, type === 'number' ? 'number' : 'text')
                !== normalizeComparable(nextValue, type === 'number' ? 'number' : 'text');

            if (changed) {
                pendingEdits.set(key, { id: row.dataset.customerId, field, value: nextValue });
                cell.classList.add('is-changed');
            } else {
                pendingEdits.delete(key);
                cell.classList.remove('is-changed');
            }

            cell.dataset.pendingValue = nextValue;
            setCellDisplay(cell, field, nextValue);
            updateSaveUpdateButton();
        }

        editor.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                editor.blur();
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                canceled = true;
                setCellDisplay(cell, field, originalValue);
            }
        });

        editor.addEventListener('blur', commit, { once: true });
    }

    function updateRow(customer) {
        const row = root.querySelector(`[data-customer-row][data-customer-id="${customer.id}"]`);

        if (!row) {
            return;
        }

        row.classList.remove('admin-customers__row--green', 'admin-customers__row--yellow');
        row.classList.add(`admin-customers__row--${customer.price_reference || 'green'}`);

        const values = {
            customer_name: customer.customer_name,
            tin: customer.tin,
            price_reference: customer.price_reference,
            discount_percent: customer.discount_percent,
            sales_agent_id: customer.sales_agent_id,
            address: customer.address,
            date_started: customer.date_started,
            terms: customer.terms,
        };

        Object.entries(values).forEach(function ([field, value]) {
            const cell = row.querySelector(`[data-field="${field}"]`);
            if (!cell) {
                return;
            }

            cell.dataset.value = value ?? '';
            delete cell.dataset.pendingValue;
            setCellDisplay(cell, field, value);
            cell.classList.remove('is-changed');
        });
    }

    async function saveCustomerUpdates() {
        if (!pendingEdits.size || !saveUpdateButton) {
            return;
        }

        hideNotice();
        saveUpdateButton.disabled = true;

        try {
            const payload = await fetchJson(updateUrl, {
                method: 'PATCH',
                body: JSON.stringify({ edits: Array.from(pendingEdits.values()) }),
            });

            (payload.customers || []).forEach(updateRow);
            updateStats(payload.stats);
            pendingEdits.clear();
            updateSaveUpdateButton();
            showCustomerSuccessModal({
                title: 'Customer Changes Saved',
                message: 'Your customer changes have been saved successfully.',
                details: [
                    {
                        label: 'Updated',
                        value: `${(payload.customers || []).length} ${(payload.customers || []).length === 1 ? 'customer' : 'customers'} updated successfully.`,
                    },
                ],
            });
        } catch (error) {
            showNotice(error.message);
            updateSaveUpdateButton();
        }
    }

    function showDeleteError(message) {
        if (!deleteError) {
            showNotice(message);
            return;
        }

        deleteError.textContent = message;
        deleteError.hidden = false;
    }

    function clearDeleteError() {
        if (deleteError) {
            deleteError.textContent = '';
            deleteError.hidden = true;
        }
    }

    function openDeleteCustomerModal() {
        customerPendingDelete = selectedCustomerSnapshot();

        if (!customerPendingDelete || !deleteModal) {
            return;
        }

        hideNotice();
        clearDeleteError();

        if (deleteNo) {
            deleteNo.textContent = displayText(customerPendingDelete.customerNo);
        }

        if (deleteName) {
            deleteName.textContent = displayText(customerPendingDelete.customerName);
        }

        openModal(deleteModal);
    }

    function closeDeleteCustomerModal() {
        if (isDeletingCustomer) {
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

    function clearPendingEditsForCustomer(customerId) {
        Array.from(pendingEdits.keys()).forEach(function (key) {
            if (key.startsWith(`${customerId}:`)) {
                pendingEdits.delete(key);
            }
        });

        updateSaveUpdateButton();
    }

    async function confirmDeleteCustomer() {
        if (!customerPendingDelete || !confirmDeleteButton || isDeletingCustomer) {
            return;
        }

        const customerToDelete = customerPendingDelete;
        const rowsBeforeDelete = root.querySelectorAll('[data-customer-row]').length;
        isDeletingCustomer = true;
        clearDeleteError();
        confirmDeleteButton.disabled = true;

        if (confirmDeleteText) {
            confirmDeleteText.textContent = 'Deleting...';
        }

        try {
            const payload = await fetchJson(destroyUrl(customerToDelete.id), { method: 'DELETE' });
            const deletedCustomer = payload.customer || {};
            const customerNo = deletedCustomer.customer_no || customerToDelete.customerNo;
            const customerName = deletedCustomer.customer_name || customerToDelete.customerName;

            updateStats(payload.stats);
            clearPendingEditsForCustomer(customerToDelete.id);
            closeModal(deleteModal);

            if (selectedRow) {
                selectedRow.remove();
            }

            clearSelectedCustomer();
            queueCustomerSuccessModal({
                title: 'Customer Deleted Successfully',
                message: 'The selected customer has been removed successfully.',
                details: [
                    { label: 'No', value: customerNo },
                    { label: 'Customer', value: customerName },
                ],
            });

            window.location.href = refreshUrlAfterDelete(rowsBeforeDelete - 1);
        } catch (error) {
            showDeleteError(error.message || 'Unable to delete customer.');
        } finally {
            isDeletingCustomer = false;
            confirmDeleteButton.disabled = false;

            if (confirmDeleteText) {
                confirmDeleteText.textContent = 'Delete Customer';
            }
        }
    }

    function submitSearch(form) {
        if (!form) {
            return;
        }

        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(function () {
            syncSearchForm(form);
            form.requestSubmit();
        }, 350);
    }

    function setHidden(form, name, value) {
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
        if (globalSearch) {
            setHidden(form, 'q', globalSearch.value);
        }

        columnSearches.forEach(function (input) {
            setHidden(form, input.name, input.value);
        });
    }

    function isTextEntryTarget(target) {
        if (!(target instanceof HTMLElement)) {
            return false;
        }

        return Boolean(target.closest('input, textarea, select, [contenteditable="true"]'));
    }

    function isCustomerModalOpen() {
        return [addModal, viewModal, deleteModal, successModal].some(function (modal) {
            return modal && !modal.hidden;
        });
    }

    function scrollCustomerTableSideways(direction) {
        if (!tableShell) {
            return;
        }

        tableShell.scrollBy({
            left: direction * Math.max(120, Math.round(tableShell.clientWidth * 0.35)),
            behavior: 'smooth',
        });
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

    root.querySelectorAll('[data-customer-editable]').forEach(function (cell) {
        cell.addEventListener('click', function (event) {
            event.stopPropagation();
            beginCustomerCellEdit(cell);
        });
    });

    if (addButton) {
        addButton.addEventListener('click', function () {
            addForm?.reset();
            handlePriceReferenceChange();
            openModal(addModal);
        });
    }

    if (viewButton) {
        viewButton.addEventListener('click', openSelectedCustomer);
    }

    if (saveUpdateButton) {
        saveUpdateButton.addEventListener('click', saveCustomerUpdates);
    }

    if (deleteButton) {
        deleteButton.addEventListener('click', openDeleteCustomerModal);
    }

    if (addForm) {
        addForm.addEventListener('submit', submitAddCustomer);
        addForm.querySelectorAll('[data-price-reference-radio]').forEach(function (radio) {
            radio.addEventListener('change', handlePriceReferenceChange);
        });

        addForm.querySelector('input[name="discount_percent"]')?.addEventListener('input', function (event) {
            if (getSelectedPriceReference() === 'green') {
                greenDiscountDraft = event.target.value || '';
            }
        });
    }

    closeAddButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(addModal);
            addButton?.focus();
        });
    });

    closeViewButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(viewModal);
            viewButton?.focus();
        });
    });

    closeDeleteButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeDeleteCustomerModal();
            deleteButton?.focus();
        });
    });

    closeSuccessButtons.forEach(function (button) {
        button.addEventListener('click', closeCustomerSuccessModal);
    });

    if (confirmDeleteButton) {
        confirmDeleteButton.addEventListener('click', confirmDeleteCustomer);
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
        if ((event.key === 'ArrowLeft' || event.key === 'ArrowRight') && !isTextEntryTarget(event.target) && !isCustomerModalOpen()) {
            event.preventDefault();
            scrollCustomerTableSideways(event.key === 'ArrowRight' ? 1 : -1);
            return;
        }

        if (event.key !== 'Escape') {
            return;
        }

        if (addModal && !addModal.hidden) {
            closeModal(addModal);
        }

        if (viewModal && !viewModal.hidden) {
            closeModal(viewModal);
        }

        if (deleteModal && !deleteModal.hidden) {
            closeDeleteCustomerModal();
        }

        if (successModal && !successModal.hidden) {
            closeCustomerSuccessModal();
        }
    });

    updateDiscountState(addForm);
    updateCustomerCreateButtonState();
    updateCustomerActionButtons();
    updateSaveUpdateButton();
    consumeQueuedCustomerSuccessModal();
});
