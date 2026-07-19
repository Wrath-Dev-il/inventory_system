document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('[data-user-management]');
    if (!root) return;

    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    var currentRole = parseInt(document.querySelector('[data-user-tab].is-active')?.getAttribute('data-role') || '1');
    var currentPage = 1;
    var currentSearches = {};
    var manageUserId = null;
    var confirmCallback = null;

    var fetchUrlTemplate = root.getAttribute('data-fetch-url');
    var storeUrl = root.getAttribute('data-store-url');
    var updateUrlTemplate = root.getAttribute('data-update-url-template');
    var destroyUrlTemplate = root.getAttribute('data-destroy-url-template');

    var tableBody = root.querySelector('[data-user-table-body]');
    var paginationEl = root.querySelector('[data-user-pagination]');
    var tabs = root.querySelectorAll('[data-user-tab]');
    var searchInputs = root.querySelectorAll('[data-user-column-search]');
    var manageModal = root.querySelector('[data-user-manage-modal]');
    var manageForm = root.querySelector('[data-user-manage-form]');
    var manageCloseButtons = root.querySelectorAll('[data-user-manage-close]');
    var addModal = root.querySelector('[data-user-add-modal]');
    var addForm = root.querySelector('[data-user-add-form]');
    var addButton = root.querySelector('[data-user-add-button]');
    var addCloseButtons = root.querySelectorAll('[data-user-add-close]');
    var confirmModal = root.querySelector('[data-user-confirm-modal]');
    var confirmCloseButtons = root.querySelectorAll('[data-user-confirm-close]');
    var confirmCancel = root.querySelector('[data-user-confirm-cancel]');
    var confirmProceed = root.querySelector('[data-user-confirm-proceed]');
    var confirmTitle = root.querySelector('[data-user-confirm-title]');
    var confirmMessage = root.querySelector('[data-user-confirm-message]');
    var successModal = root.querySelector('[data-user-success-modal]');
    var successCloseButtons = root.querySelectorAll('[data-user-success-close]');
    var successTitle = root.querySelector('[data-user-success-title]');
    var successMessage = root.querySelector('[data-user-success-message]');
    var deleteButton = root.querySelector('[data-user-delete-button]');
    var saveButton = root.querySelector('[data-user-save-button]');
    var statEls = root.querySelectorAll('[data-user-stat]');

    function fetchJson(url, options) {
        var opts = options || {};
        opts.headers = opts.headers || {};
        opts.headers['Accept'] = 'application/json';
        opts.headers['X-Requested-With'] = 'XMLHttpRequest';
        opts.headers['X-CSRF-TOKEN'] = csrfToken;
        if (opts.body && !(opts.body instanceof FormData)) {
            opts.headers['Content-Type'] = 'application/json';
        }
        return fetch(url, opts).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) {
                    var err = new Error(data.message || 'Request failed');
                    err.status = res.status;
                    err.data = data;
                    throw err;
                }
                return data;
            });
        });
    }

    function openModal(modal) {
        if (modal) modal.style.display = '';
    }

    function closeModal(modal) {
        if (modal) modal.style.display = 'none';
    }

    function showSuccess(title, message) {
        if (successTitle) successTitle.textContent = title || 'Success';
        if (successMessage) successMessage.textContent = message || 'Action completed successfully.';
        openModal(successModal);
    }

    function showConfirm(title, message, callback) {
        if (confirmTitle) confirmTitle.textContent = title || 'Confirm Action';
        if (confirmMessage) confirmMessage.textContent = message || 'Are you sure?';
        confirmCallback = callback;
        openModal(confirmModal);
    }

    function updateStats(stats) {
        if (!stats) return;
        statEls.forEach(function (el) {
            var key = el.getAttribute('data-user-stat');
            if (stats[key] !== undefined) {
                el.textContent = Number(stats[key]).toLocaleString();
            }
        });
    }

    function escapeHtml(str) {
        if (str == null) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function buildRowHtml(user) {
        var roleLabels = { 1: 'Admin', 2: 'Employee 1', 3: 'Employee 2' };
        var roleBadgeClass = 'user-role-badge--' + user.account_type;
        var roleLabel = roleLabels[user.account_type] || 'Unknown';
        var userJson = escapeHtml(JSON.stringify(user));
        return '<tr class="admin-users__row" data-user-row data-user-id="' + user.login_ID + '">' +
            '<td>' + escapeHtml(user.User_ID) + '</td>' +
            '<td>\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022</td>' +
            '<td><span class="user-role-badge ' + roleBadgeClass + '">' + escapeHtml(roleLabel) + '</span></td>' +
            '<td>' + escapeHtml(user.access_modules || '--') + '</td>' +
            '<td><button type="button" class="user-manage-button" data-user-manage=\'' + userJson + '\'>Manage</button></td>' +
            '</tr>';
    }

    function buildPaginationHtml(meta, role, searches) {
        if (meta.total === 0) {
            return '<p>Showing 0 users</p>';
        }
        var html = '<p>Showing ' + Number(meta.from).toLocaleString() + '-' + Number(meta.to).toLocaleString() + ' of ' + Number(meta.total).toLocaleString() + ' users</p>';
        html += '<nav><div style="display:flex;gap:6px">';
        if (meta.current_page > 1) {
            html += '<a href="#" data-page="' + (meta.current_page - 1) + '" style="min-width:34px;min-height:34px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d8dee9;border-radius:7px;background:#fff;color:#344054;padding:6px 10px;font-size:13px;font-weight:800;text-decoration:none">&laquo;</a>';
        }
        for (var i = 1; i <= meta.last_page; i++) {
            if (i === meta.current_page) {
                html += '<span aria-current="page" style="min-width:34px;min-height:34px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #071a3d;border-radius:7px;background:#071a3d;color:#fff;padding:6px 10px;font-size:13px;font-weight:800">' + i + '</span>';
            } else {
                html += '<a href="#" data-page="' + i + '" style="min-width:34px;min-height:34px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d8dee9;border-radius:7px;background:#fff;color:#344054;padding:6px 10px;font-size:13px;font-weight:800;text-decoration:none">' + i + '</a>';
            }
        }
        if (meta.current_page < meta.last_page) {
            html += '<a href="#" data-page="' + (meta.current_page + 1) + '" style="min-width:34px;min-height:34px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d8dee9;border-radius:7px;background:#fff;color:#344054;padding:6px 10px;font-size:13px;font-weight:800;text-decoration:none">&raquo;</a>';
        }
        html += '</div></nav>';
        return html;
    }

    function fetchUsers(role, page, searches) {
        var params = new URLSearchParams();
        params.set('page', page || 1);
        if (searches) {
            Object.keys(searches).forEach(function (key) {
                if (searches[key]) {
                    params.set('search[' + key + ']', searches[key]);
                }
            });
        }
        var url = fetchUrlTemplate.replace('__ROLE__', role) + '?' + params.toString();

        tableBody.innerHTML = '<tr><td colspan="5" class="admin-users__table-empty" style="padding:34px 16px;text-align:center;color:#667085">Loading...</td></tr>';

        fetchJson(url).then(function (data) {
            if (data.users.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="admin-users__table-empty">No users found for this role.</td></tr>';
            } else {
                tableBody.innerHTML = data.users.map(buildRowHtml).join('');
            }
            paginationEl.innerHTML = buildPaginationHtml(data.pagination, role, searches);
            currentPage = page || 1;
            currentSearches = searches || {};
        }).catch(function () {
            tableBody.innerHTML = '<tr><td colspan="5" class="admin-users__table-empty" style="color:#dc2626">Failed to load users. Please try again.</td></tr>';
        });
    }

    function searchTimeout() {
        var searches = {};
        searchInputs.forEach(function (input) {
            var column = input.getAttribute('data-user-column-search');
            if (column && input.value) {
                searches[column] = input.value;
            }
        });
        fetchUsers(currentRole, 1, searches);
    }

    var searchTimers = {};
    searchInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            var column = this.getAttribute('data-user-column-search');
            if (searchTimers[column]) clearTimeout(searchTimers[column]);
            searchTimers[column] = setTimeout(searchTimeout, 350);
        });
    });

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var role = parseInt(this.getAttribute('data-role'));
            if (role === currentRole) return;
            currentRole = role;
            tabs.forEach(function (t) {
                t.classList.remove('is-active');
                t.setAttribute('aria-selected', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-selected', 'true');
            searchInputs.forEach(function (input) { input.value = ''; });
            currentSearches = {};
            fetchUsers(role, 1, {});
        });
    });

    paginationEl.addEventListener('click', function (e) {
        var link = e.target.closest('[data-page]');
        if (link) {
            e.preventDefault();
            var page = parseInt(link.getAttribute('data-page'));
            fetchUsers(currentRole, page, currentSearches);
        }
    });

    tableBody.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-user-manage]');
        if (!btn) return;
        var userData = btn.getAttribute('data-user-manage');
        try {
            var user = JSON.parse(userData);
        } catch (_) {
            return;
        }
        manageUserId = user.login_ID;
        var fields = manageForm.querySelectorAll('[data-manage-field]');
        fields.forEach(function (field) {
            var name = field.getAttribute('data-manage-field');
            var val = user[name] !== undefined && user[name] !== null ? user[name] : '';
            if (name === 'Password') {
                field.value = '';
            } else if (name === 'account_type') {
                field.value = String(val);
            } else if (name === 'Gender') {
                field.value = val;
            } else {
                field.value = val;
            }
        });
        var title = manageForm.querySelector('[data-manage-modal-title]');
        if (title) title.textContent = 'Manage User - ' + (user.User_ID || '');
        openModal(manageModal);
    });

    manageCloseButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(manageModal); });
    });

    manageForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(manageForm);
        var data = {};
        formData.forEach(function (value, key) {
            data[key] = value;
        });
        var url = updateUrlTemplate.replace('__USER_ID__', manageUserId);

        showConfirm(
            'Save Changes?',
            'Are you sure you want to update this user account?',
            function () {
                fetchJson(url, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                }).then(function (result) {
                    closeModal(confirmModal);
                    closeModal(manageModal);
                    showSuccess('User Updated', result.message || 'User updated successfully.');
                    if (result.stats) updateStats(result.stats);
                    fetchUsers(currentRole, currentPage, currentSearches);
                }).catch(function (err) {
                    closeModal(confirmModal);
                    showSuccess('Error', err.data?.message || err.message || 'Failed to update user.');
                });
            }
        );
    });

    deleteButton.addEventListener('click', function () {
        if (!manageUserId) return;
        showConfirm(
            'Delete Account?',
            'This action will permanently remove this user account. Are you sure?',
            function () {
                var url = destroyUrlTemplate.replace('__USER_ID__', manageUserId);
                fetchJson(url, {
                    method: 'DELETE'
                }).then(function (result) {
                    closeModal(confirmModal);
                    closeModal(manageModal);
                    showSuccess('Account Deleted', result.message || 'User deleted successfully.');
                    if (result.stats) updateStats(result.stats);
                    fetchUsers(currentRole, currentPage, currentSearches);
                    manageUserId = null;
                }).catch(function (err) {
                    closeModal(confirmModal);
                    showSuccess('Error', err.data?.message || err.message || 'Failed to delete user.');
                });
            }
        );
    });

    addButton.addEventListener('click', function () {
        addForm.reset();
        openModal(addModal);
    });

    addCloseButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(addModal); });
    });

    addForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(addForm);
        var data = {};
        formData.forEach(function (value, key) {
            data[key] = value;
        });

        showConfirm(
            'Create User?',
            'Are you sure you want to create this new user account?',
            function () {
                fetchJson(storeUrl, {
                    method: 'POST',
                    body: JSON.stringify(data)
                }).then(function (result) {
                    closeModal(confirmModal);
                    closeModal(addModal);
                    showSuccess('User Created', result.message || 'User created successfully.');
                    if (result.stats) updateStats(result.stats);
                    fetchUsers(currentRole, currentPage, currentSearches);
                }).catch(function (err) {
                    closeModal(confirmModal);
                    showSuccess('Error', err.data?.message || err.message || 'Failed to create user.');
                });
            }
        );
    });

    confirmCloseButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(confirmModal); });
    });
    if (confirmCancel) confirmCancel.addEventListener('click', function () { closeModal(confirmModal); });
    if (confirmProceed) confirmProceed.addEventListener('click', function () {
        closeModal(confirmModal);
        if (typeof confirmCallback === 'function') {
            confirmCallback();
            confirmCallback = null;
        }
    });

    successCloseButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(successModal); });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (!manageModal.hasAttribute('hidden')) closeModal(manageModal);
            if (!addModal.hasAttribute('hidden')) closeModal(addModal);
            if (!confirmModal.hasAttribute('hidden')) closeModal(confirmModal);
            if (!successModal.hasAttribute('hidden')) closeModal(successModal);
        }
    });
});
