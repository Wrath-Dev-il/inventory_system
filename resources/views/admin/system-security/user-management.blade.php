<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ $companyName }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}?v={{ filemtime(public_path('css/admin-layout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-user-management.css') }}?v={{ filemtime(public_path('css/admin-user-management.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')
        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'User Management',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'System Security'],
                    ['label' => 'User Management', 'active' => true],
                ],
            ])
            <section
                class="admin-panel admin-users"
                data-user-management
                data-fetch-url="{{ $fetchUrl }}"
                data-store-url="{{ $storeUrl }}"
                data-update-url-template="{{ $updateUrlTemplate }}"
                data-destroy-url-template="{{ $destroyUrlTemplate }}"
            >
                <div class="admin-users__header">
                    <div>
                        <p class="admin-users__kicker">System Security</p>
                        <h2>User Management</h2>
                        <p class="admin-users__subtitle">Manage system users, roles and access permissions.</p>
                    </div>
                    <button type="button" class="user-action-button user-action-button--primary" data-user-add-button>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M19 8v6M16 11h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Add User</span>
                    </button>
                </div>

                <section class="admin-users__cards" aria-label="User summary">
                    <article class="user-metric-card">
                        <span class="user-metric-card__icon user-metric-card__icon--admin" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 15a6 6 0 0 0-6 6h12a6 6 0 0 0-6-6Z" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M17 3a3 3 0 0 1 3 3M20 3a3 3 0 0 1 3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Admin</span>
                        <strong data-user-stat="admin">{{ number_format($stats['admin']) }}</strong>
                    </article>
                    <article class="user-metric-card">
                        <span class="user-metric-card__icon user-metric-card__icon--employee1" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Employee 1</span>
                        <strong data-user-stat="employee_1">{{ number_format($stats['employee_1']) }}</strong>
                    </article>
                    <article class="user-metric-card">
                        <span class="user-metric-card__icon user-metric-card__icon--employee2" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Employee 2</span>
                        <strong data-user-stat="employee_2">{{ number_format($stats['employee_2']) }}</strong>
                    </article>
                </section>

                <section class="admin-users__table-section" aria-label="User table">
                    <div class="admin-users__tabs" role="tablist">
                        @foreach ([1 => 'Admin', 2 => 'Employee 1', 3 => 'Employee 2'] as $roleVal => $roleLabel)
                            <button
                                type="button"
                                role="tab"
                                class="admin-users__tab {{ $currentRole === $roleVal ? 'is-active' : '' }}"
                                data-user-tab
                                data-role="{{ $roleVal }}"
                                aria-selected="{{ $currentRole === $roleVal ? 'true' : 'false' }}"
                            >{{ $roleLabel }}</button>
                        @endforeach
                    </div>

                    <div class="admin-users__table-shell" data-user-table-container>
                        <table class="admin-users__table" data-user-table>
                            <thead>
                                <tr>
                                    <th>User-ID</th>
                                    <th>Password</th>
                                    <th>Role</th>
                                    <th>Access Modules</th>
                                    <th>Action</th>
                                </tr>
                                <tr class="admin-users__filters">
                                    <th><input type="search" placeholder="Search" data-user-column-search="User_ID"></th>
                                    <th><input type="search" placeholder="Search" data-user-column-search="Password"></th>
                                    <th><input type="search" placeholder="Search" data-user-column-search="account_type"></th>
                                    <th><input type="search" placeholder="Search" data-user-column-search="access_modules"></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody data-user-table-body>
                                @forelse ($users as $user)
                                    <tr class="admin-users__row" data-user-row data-user-id="{{ $user->login_ID }}">
                                        <td>{{ $user->User_ID }}</td>
                                        <td>••••••••</td>
                                        <td><span class="user-role-badge user-role-badge--{{ $user->account_type }}">{{ $roles[$user->account_type] ?? 'Unknown' }}</span></td>
                                        <td>{{ $user->access_modules ?? '--' }}</td>
                                        <td><button type="button" class="user-manage-button" data-user-manage='{!! json_encode($user->only(['login_ID', 'User_ID', 'account_type', 'access_modules', 'Email', 'User_First_Name', 'User_Middle_Name', 'User_Last_Name', 'Gender'])) !!}'>Manage</button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="admin-users__table-empty">No users found for this role.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-users__pagination" data-user-pagination>
                        @if ($users->total() > 0)
                            <p>Showing {{ number_format($users->firstItem()) }}-{{ number_format($users->lastItem()) }} of {{ number_format($users->total()) }} users</p>
                            {{ $users->appends(['role' => $currentRole])->links() }}
                        @else
                            <p>Showing 0 users</p>
                        @endif
                    </div>
                </section>

                <div class="user-manage-modal" data-user-manage-modal style="display:none">
                    <div class="user-manage-modal__backdrop" data-user-manage-close></div>
                    <form class="user-manage-modal__dialog" data-user-manage-form>
                        <header class="user-manage-modal__header">
                            <div>
                                <p>User Account</p>
                                <h2 data-manage-modal-title>Manage User</h2>
                            </div>
                            <button type="button" class="user-icon-button" data-user-manage-close aria-label="Close">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="user-manage-modal__body">
                            <input type="hidden" name="login_ID" data-manage-field="login_ID">
                            <label class="user-field">
                                <span>User-ID</span>
                                <input type="text" name="User_ID" data-manage-field="User_ID" required>
                            </label>
                            <label class="user-field">
                                <span>Password <em>Leave blank to keep current</em></span>
                                <input type="password" name="Password" data-manage-field="Password" placeholder="New password">
                            </label>
                            <label class="user-field">
                                <span>Role</span>
                                <select name="account_type" data-manage-field="account_type" required>
                                    @foreach ($roles as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="user-field">
                                <span>Access Modules</span>
                                <input type="text" name="access_modules" data-manage-field="access_modules" placeholder="e.g. dashboard,products,customers">
                            </label>
                            <label class="user-field">
                                <span>Email</span>
                                <input type="email" name="Email" data-manage-field="Email">
                            </label>
                            <label class="user-field">
                                <span>First Name</span>
                                <input type="text" name="User_First_Name" data-manage-field="User_First_Name">
                            </label>
                            <label class="user-field">
                                <span>Middle Name</span>
                                <input type="text" name="User_Middle_Name" data-manage-field="User_Middle_Name">
                            </label>
                            <label class="user-field">
                                <span>Last Name</span>
                                <input type="text" name="User_Last_Name" data-manage-field="User_Last_Name">
                            </label>
                            <label class="user-field">
                                <span>Gender</span>
                                <select name="Gender" data-manage-field="Gender">
                                    <option value="">Select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </label>
                        </div>
                        <footer class="user-manage-modal__footer">
                            <button type="button" class="user-action-button user-action-button--danger" data-user-delete-button>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Delete Account
                            </button>
                            <div class="user-manage-modal__footer-right">
                                <button type="button" class="user-action-button user-action-button--secondary" data-user-manage-close>Cancel</button>
                                <button type="submit" class="user-action-button user-action-button--primary" data-user-save-button>Save Changes</button>
                            </div>
                        </footer>
                    </form>
                </div>

                <div class="user-add-modal" data-user-add-modal style="display:none">
                    <div class="user-add-modal__backdrop" data-user-add-close></div>
                    <form class="user-add-modal__dialog" data-user-add-form>
                        <header class="user-add-modal__header">
                            <div>
                                <p>New Account</p>
                                <h2>Add User</h2>
                            </div>
                            <button type="button" class="user-icon-button" data-user-add-close aria-label="Close">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="user-add-modal__body">
                            <label class="user-field">
                                <span>User-ID</span>
                                <input type="text" name="User_ID" required>
                            </label>
                            <label class="user-field">
                                <span>Password</span>
                                <input type="password" name="Password" required>
                            </label>
                            <label class="user-field">
                                <span>Role</span>
                                <select name="account_type" required>
                                    @foreach ($roles as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="user-field">
                                <span>Access Modules</span>
                                <input type="text" name="access_modules" placeholder="e.g. dashboard,products,customers">
                            </label>
                            <label class="user-field">
                                <span>Email</span>
                                <input type="email" name="Email">
                            </label>
                            <label class="user-field">
                                <span>First Name</span>
                                <input type="text" name="User_First_Name">
                            </label>
                            <label class="user-field">
                                <span>Middle Name</span>
                                <input type="text" name="User_Middle_Name">
                            </label>
                            <label class="user-field">
                                <span>Last Name</span>
                                <input type="text" name="User_Last_Name">
                            </label>
                            <label class="user-field">
                                <span>Gender</span>
                                <select name="Gender">
                                    <option value="">Select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </label>
                        </div>
                        <footer class="user-add-modal__footer">
                            <button type="button" class="user-action-button user-action-button--secondary" data-user-add-close>Cancel</button>
                            <button type="submit" class="user-action-button user-action-button--primary">Create User</button>
                        </footer>
                    </form>
                </div>

                <div class="user-confirm-modal" data-user-confirm-modal style="display:none">
                    <div class="user-confirm-modal__backdrop" data-user-confirm-close></div>
                    <article class="user-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="user-confirm-title">
                        <header class="user-confirm-modal__header">
                            <span class="user-confirm-modal__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <button type="button" class="user-icon-button" data-user-confirm-close aria-label="Close">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="user-confirm-modal__body">
                            <h2 id="user-confirm-title" data-user-confirm-title>Confirm Action</h2>
                            <p data-user-confirm-message>Are you sure?</p>
                        </div>
                        <footer class="user-confirm-modal__footer">
                            <button type="button" class="user-action-button user-action-button--secondary" data-user-confirm-cancel>Cancel</button>
                            <button type="button" class="user-action-button" data-user-confirm-proceed>Proceed</button>
                        </footer>
                    </article>
                </div>

                <div class="user-success-modal" data-user-success-modal style="display:none">
                    <div class="user-success-modal__backdrop" data-user-success-close></div>
                    <article class="user-success-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="user-success-title">
                        <header class="user-success-modal__header">
                            <span class="user-success-modal__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <button type="button" class="user-icon-button" data-user-success-close aria-label="Close">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="user-success-modal__body">
                            <h2 id="user-success-title" data-user-success-title>Success</h2>
                            <p data-user-success-message>Action completed successfully.</p>
                        </div>
                        <footer class="user-success-modal__footer">
                            <button type="button" class="user-action-button user-action-button--success" data-user-success-close>Done</button>
                        </footer>
                    </article>
                </div>
            </section>
        </main>
    </div>
    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-user-management.js') }}?v={{ filemtime(public_path('js/admin-user-management.js')) }}" defer></script>
</body>
</html>
