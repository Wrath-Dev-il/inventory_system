(function () {
    'use strict';

    var root = document.querySelector('[data-salesman-list]');
    if (!root) return;

    var selectedId = null;
    var customerPage = 1;

    var els = {
        addBtn: root.querySelector('[data-sal-add]'),
        editBtn: root.querySelector('[data-sal-edit]'),
        viewBtn: root.querySelector('[data-sal-view]'),
        deleteBtn: root.querySelector('[data-sal-delete]'),
        addModal: root.querySelector('[data-sal-add-modal]'),
        editModal: root.querySelector('[data-sal-edit-modal]'),
        viewModal: root.querySelector('[data-sal-view-modal]'),
        deleteModal: root.querySelector('[data-sal-delete-modal]'),
        successModal: root.querySelector('[data-sal-success-modal]'),
        confirmModal: root.querySelector('[data-sal-confirm-modal]'),
        addForm: root.querySelector('[data-sal-add-form]'),
        editForm: root.querySelector('[data-sal-edit-form]'),
        successTitle: root.querySelector('[data-sal-success-title]'),
        successMsg: root.querySelector('[data-sal-success-msg]'),
        deleteTargetName: root.querySelector('[data-sal-delete-name]'),
        deleteTargetNo: root.querySelector('[data-sal-delete-no]'),
        deleteConfirm: root.querySelector('[data-sal-delete-confirm]'),
        viewAgentNo: root.querySelector('[data-sal-view-agent-no]'),
        viewTitle: root.querySelector('[data-sal-view-title]'),
        viewName: root.querySelector('[data-sal-view-name]'),
        viewPhone: root.querySelector('[data-sal-view-phone]'),
        viewCommission: root.querySelector('[data-sal-view-commission]'),
        viewDateStarted: root.querySelector('[data-sal-view-date-started]'),
        viewAssigned: root.querySelector('[data-sal-view-assigned]'),
        viewCustomerBody: root.querySelector('[data-sal-view-customer-body]'),
        viewCustomerInfo: root.querySelector('[data-sal-view-customer-info]'),
        viewPagination: root.querySelector('[data-sal-view-pagination]'),
        statTotal: root.querySelector('[data-sal-stat-total]'),
        statAssigned: root.querySelector('[data-sal-stat-assigned]'),
        statUnassigned: root.querySelector('[data-sal-stat-unassigned]'),
        addAgentNo: root.querySelector('[data-sal-add-agent-no]'),
        editAgentNo: root.querySelector('[data-sal-edit-agent-no]'),
        editId: root.querySelector('[data-sal-edit-id]'),
        confirmBtn: root.querySelector('[data-sal-confirm-btn]'),
        confirmMsg: root.querySelector('[data-sal-confirm-msg]'),
    };

    var urls = {
        store: root.getAttribute('data-sal-store-url'),
        detailsTemplate: root.getAttribute('data-sal-details-template'),
        customersTemplate: root.getAttribute('data-sal-customers-template'),
        updateTemplate: root.getAttribute('data-sal-update-template'),
        destroyTemplate: root.getAttribute('data-sal-destroy-template'),
    };

    function templateUrl(url, id) {
        return url.replace('__AGENT_ID__', id);
    }

    function selectRow(id) {
        selectedId = id;
        var rows = root.querySelectorAll('[data-sal-row]');
        rows.forEach(function (r) {
            r.classList.toggle('is-selected', r.getAttribute('data-sal-id') === id);
        });
        updateButtons();
    }

    function updateButtons() {
        var hasSelection = selectedId !== null;
        els.editBtn.disabled = !hasSelection;
        els.viewBtn.disabled = !hasSelection;
        els.deleteBtn.disabled = !hasSelection;
    }

    function openModal(modal) {
        if (modal) modal.hidden = false;
    }

    function closeModal(modal) {
        if (modal) modal.hidden = true;
    }

    function showSuccess(title, msg) {
        if (els.successTitle) els.successTitle.textContent = title;
        if (els.successMsg) els.successMsg.textContent = msg || '';
        openModal(els.successModal);
    }

    function updateStats(stats) {
        if (els.statTotal) els.statTotal.textContent = (stats.total_agents || 0).toLocaleString();
        if (els.statAssigned) els.statAssigned.textContent = (stats.assigned_agents || 0).toLocaleString();
        if (els.statUnassigned) els.statUnassigned.textContent = (stats.unassigned_agents || 0).toLocaleString();
    }

    function csrfField() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Row selection (delegated)
    root.addEventListener('click', function (e) {
        var row = e.target.closest('[data-sal-row]');
        if (row) {
            selectRow(row.getAttribute('data-sal-id'));
        }
    });

    // Sort headers
    root.querySelectorAll('[data-sal-sort]').forEach(function (th) {
        th.addEventListener('click', function () {
            var column = th.getAttribute('data-sal-sort');
            var sortInput = document.getElementById('sal-sort-input');
            var dirInput = document.getElementById('sal-direction-input');
            var form = document.getElementById('sal-search-form');
            if (!sortInput || !dirInput || !form) return;

            var currentSort = sortInput.value;
            var currentDir = dirInput.value;

            if (currentSort === column) {
                if (currentDir === 'asc') {
                    dirInput.value = 'desc';
                } else {
                    sortInput.value = '';
                    dirInput.value = '';
                }
            } else {
                sortInput.value = column;
                dirInput.value = 'asc';
            }

            form.submit();
        });
    });

    // Add button
    if (els.addBtn) {
        els.addBtn.addEventListener('click', function () {
            if (els.addForm) els.addForm.reset();
            openModal(els.addModal);
        });
    }

    // Edit button
    if (els.editBtn) {
        els.editBtn.addEventListener('click', function () {
            if (!selectedId) return;
            if (!els.editForm) return;

            var detailsUrl = templateUrl(urls.detailsTemplate, selectedId);

            fetch(detailsUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var agent = data.agent;
                    if (els.editAgentNo) els.editAgentNo.value = agent.agent_no || '';
                    if (els.editId) els.editId.value = agent.id;
                    if (els.editForm.elements.name) els.editForm.elements.name.value = agent.name || '';
                    if (els.editForm.elements.phone) els.editForm.elements.phone.value = agent.phone || '';
                    if (els.editForm.elements.commission_percentage) els.editForm.elements.commission_percentage.value = agent.commission_percentage || '';
                    if (els.editForm.elements.date_started) els.editForm.elements.date_started.value = agent.date_started || '';
                    openModal(els.editModal);
                })
                .catch(function () {
                    showSuccess('Error', 'Could not load agent details.');
                });
        });
    }

    // View button
    if (els.viewBtn) {
        els.viewBtn.addEventListener('click', function () {
            if (!selectedId) return;

            customerPage = 1;
            var detailsUrl = templateUrl(urls.detailsTemplate, selectedId);

            fetch(detailsUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var agent = data.agent;
                    if (els.viewTitle) els.viewTitle.textContent = agent.name || '--';
                    if (els.viewAgentNo) els.viewAgentNo.textContent = agent.agent_no || '--';
                    if (els.viewName) els.viewName.textContent = agent.name || '--';
                    if (els.viewPhone) els.viewPhone.textContent = agent.phone || '--';
                    if (els.viewCommission) els.viewCommission.textContent = (agent.commission_percentage || 0) + '%';
                    if (els.viewDateStarted) els.viewDateStarted.textContent = agent.date_started || '--';
                    if (els.viewAssigned) els.viewAssigned.textContent = (agent.customers_count || 0) + ' customer(s)';
                    openModal(els.viewModal);
                    loadViewCustomers(selectedId, 1);
                })
                .catch(function () {
                    showSuccess('Error', 'Could not load agent details.');
                });
        });
    }

    function loadViewCustomers(agentId, page) {
        if (!els.viewCustomerBody) return;
        var url = templateUrl(urls.customersTemplate, agentId) + '?page=' + page;

        if (els.viewCustomerInfo) {
            els.viewCustomerInfo.textContent = 'Loading customers...';
        }

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                renderViewCustomers(data);
            })
            .catch(function () {
                if (els.viewCustomerInfo) els.viewCustomerInfo.textContent = 'Could not load customers.';
            });
    }

    function renderViewCustomers(data) {
        var tbody = els.viewCustomerBody;
        if (!tbody) return;

        tbody.innerHTML = '';
        var customers = data.customers || [];
        var pagination = data.pagination || {};

        if (els.viewCustomerInfo) {
            els.viewCustomerInfo.textContent = customers.length + ' customer(s) assigned';
        }

        if (customers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="sal-table__empty">No customers assigned.</td></tr>';
            if (els.viewPagination) els.viewPagination.innerHTML = '';
            return;
        }

        customers.forEach(function (c) {
            var tr = document.createElement('tr');
            var badgeClass = c.price_reference === 'yellow' ? 'sal-ref-badge--yellow' : 'sal-ref-badge--green';
            tr.innerHTML = '<td>' + escapeHtml(c.customer_name) + '</td>' +
                '<td><span class="sal-ref-badge ' + badgeClass + '">' + escapeHtml(c.price_reference_label) + '</span></td>' +
                '<td>' + (c.outstanding_invoices || 0) + '</td>' +
                '<td>&#8369;' + (c.outstanding_total || 0).toFixed(2) + '</td>';
            tbody.appendChild(tr);
        });

        renderViewPagination(pagination);
    }

    function renderViewPagination(pagination) {
        var container = els.viewPagination;
        if (!container) return;

        if (!pagination.last_page || pagination.last_page <= 1) {
            container.innerHTML = '<span>Showing ' + (pagination.total || 0) + ' customer(s)</span>';
            return;
        }

        var html = '<span>Page ' + pagination.current_page + ' of ' + pagination.last_page + ' (' + (pagination.total || 0) + ' customers)</span><div>';
        html += '<button type="button" data-sal-cust-page="' + (pagination.current_page - 1) + '"' + (pagination.current_page <= 1 ? ' disabled' : '') + '>Previous</button>';
        html += '<button type="button" data-sal-cust-page="' + (pagination.current_page + 1) + '"' + (pagination.current_page >= pagination.last_page ? ' disabled' : '') + ' style="margin-left:0.5rem">Next</button>';
        html += '</div>';
        container.innerHTML = html;
    }

    // View customer pagination (delegated)
    if (els.viewPagination) {
        els.viewPagination.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-sal-cust-page]');
            if (btn && !btn.disabled) {
                var page = parseInt(btn.getAttribute('data-sal-cust-page'), 10);
                if (page > 0 && selectedId) {
                    customerPage = page;
                    loadViewCustomers(selectedId, page);
                }
            }
        });
    }

    // Delete button
    if (els.deleteBtn) {
        els.deleteBtn.addEventListener('click', function () {
            if (!selectedId) return;

            var detailsUrl = templateUrl(urls.detailsTemplate, selectedId);

            fetch(detailsUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var agent = data.agent;
                    if (els.deleteTargetName) els.deleteTargetName.textContent = agent.name || '--';
                    if (els.deleteTargetNo) els.deleteTargetNo.textContent = agent.agent_no || '--';
                    openModal(els.deleteModal);
                })
                .catch(function () {
                    showSuccess('Error', 'Could not load agent details.');
                });
        });
    }

    // Delete confirm
    if (els.deleteConfirm) {
        els.deleteConfirm.addEventListener('click', function () {
            if (!selectedId) return;

            var destroyUrl = templateUrl(urls.destroyTemplate, selectedId);

            fetch(destroyUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfField(),
                    'Accept': 'application/json',
                },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    closeModal(els.deleteModal);
                    if (data.blocked) {
                        showSuccess('Cannot Delete', data.message || 'This agent has assigned customers.');
                    } else if (data.deleted) {
                        selectedId = null;
                        updateButtons();
                        if (data.stats) updateStats(data.stats);
                        showSuccess('Deleted', data.message || 'Sales agent deleted successfully.');
                        removeRow(data.agent.id);
                    }
                })
                .catch(function () {
                    closeModal(els.deleteModal);
                    showSuccess('Error', 'An error occurred while deleting.');
                });
        });
    }

    function removeRow(id) {
        var row = root.querySelector('[data-sal-id="' + id + '"]');
        if (row) row.remove();
        var remaining = root.querySelectorAll('[data-sal-row]').length;
        if (remaining === 0) {
            var tbody = root.querySelector('[data-sal-tbody]');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="5" class="sal-table__empty">No sales agents found.</td></tr>';
            }
        }
    }

    // Add form submit
    if (els.addForm) {
        els.addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var form = els.addForm;
            var data = new FormData(form);

            fetch(urls.store, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfField(),
                    'Accept': 'application/json',
                },
                body: data,
            })
                .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
                .then(function (result) {
                    if (result.status >= 400) {
                        var errors = result.data.errors || {};
                        var msg = Object.values(errors).flat().join('\n') || result.data.message || 'Validation failed.';
                        showSuccess('Error', msg);
                        return;
                    }
                    closeModal(els.addModal);
                    if (result.data.stats) updateStats(result.data.stats);
                    showSuccess('Added', result.data.message || 'Sales agent added successfully.');
                    addRow(result.data.agent);
                    if (els.addForm) els.addForm.reset();
                })
                .catch(function () {
                    showSuccess('Error', 'An error occurred while saving.');
                });
        });
    }

    function addRow(agent) {
        var tbody = root.querySelector('[data-sal-tbody]');
        if (!tbody) return;

        var empty = tbody.querySelector('.sal-table__empty');
        if (empty) tbody.innerHTML = '';

        var tr = document.createElement('tr');
        tr.setAttribute('data-sal-row', '');
        tr.setAttribute('data-sal-id', agent.id);
        tr.setAttribute('tabindex', '0');
        tr.innerHTML = '<td>' + escapeHtml(agent.name) + '</td>' +
            '<td>' + escapeHtml(agent.phone || '--') + '</td>' +
            '<td>' + (agent.commission_percentage || 0) + '%</td>' +
            '<td>' + (agent.customers_count || 0) + '</td>' +
            '<td>' + escapeHtml(agent.date_started || '--') + '</td>';
        tbody.insertBefore(tr, tbody.firstChild);
    }

    // Edit form - show confirmation first
    if (els.editForm) {
        els.editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!selectedId) return;

            var form = els.editForm;
            var data = new FormData(form);

            if (els.confirmMsg) {
                els.confirmMsg.textContent = 'Update sales agent "' + (data.get('name') || '') + '"?';
            }
            openModal(els.confirmModal);

            if (els.confirmBtn) {
                var newBtn = els.confirmBtn.cloneNode(true);
                els.confirmBtn.parentNode.replaceChild(newBtn, els.confirmBtn);
                els.confirmBtn = newBtn;

                els.confirmBtn.addEventListener('click', function () {
                    closeModal(els.confirmModal);
                    submitEditForm(selectedId, form, data);
                });
            }
        });
    }

    function submitEditForm(id, form, formData) {
        formData.append('_method', 'PATCH');
        var updateUrl = templateUrl(urls.updateTemplate, id);

        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfField(),
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
            .then(function (result) {
                if (result.status >= 400) {
                    var errors = result.data.errors || {};
                    var msg = Object.values(errors).flat().join('\n') || result.data.message || 'Validation failed.';
                    showSuccess('Error', msg);
                    return;
                }
                closeModal(els.editModal);
                if (result.data.stats) updateStats(result.data.stats);
                showSuccess('Updated', result.data.message || 'Sales agent updated successfully.');
                updateRow(result.data.agent);
            })
            .catch(function () {
                showSuccess('Error', 'An error occurred while updating.');
            });
    }

    function updateRow(agent) {
        var row = root.querySelector('[data-sal-id="' + agent.id + '"]');
        if (!row) return;
        row.innerHTML = '<td>' + escapeHtml(agent.name) + '</td>' +
            '<td>' + escapeHtml(agent.phone || '--') + '</td>' +
            '<td>' + (agent.commission_percentage || 0) + '%</td>' +
            '<td>' + (agent.customers_count || 0) + '</td>' +
            '<td>' + escapeHtml(agent.date_started || '--') + '</td>';
    }

    // Modal close handlers
    function setupModalClose(modal, backdrop, closeBtns) {
        if (!modal) return;
        if (backdrop) backdrop.addEventListener('click', function () { closeModal(modal); });
        closeBtns.forEach(function (btn) {
            if (btn) btn.addEventListener('click', function () { closeModal(modal); });
        });
    }

    setupModalClose(els.addModal,
        root.querySelector('[data-sal-add-close]'),
        root.querySelectorAll('[data-sal-add-close]')
    );

    setupModalClose(els.editModal,
        root.querySelector('[data-sal-edit-close]'),
        root.querySelectorAll('[data-sal-edit-close]')
    );

    setupModalClose(els.viewModal,
        root.querySelector('[data-sal-view-close]'),
        root.querySelectorAll('[data-sal-view-close]')
    );

    setupModalClose(els.deleteModal,
        root.querySelector('[data-sal-delete-close]'),
        root.querySelectorAll('[data-sal-delete-close]')
    );

    setupModalClose(els.confirmModal,
        root.querySelector('[data-sal-confirm-close]'),
        root.querySelectorAll('[data-sal-confirm-close]')
    );

    setupModalClose(els.successModal,
        root.querySelector('[data-sal-success-close]'),
        root.querySelectorAll('[data-sal-success-close]')
    );

    function escapeHtml(str) {
        if (str == null) return '--';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }
})();
