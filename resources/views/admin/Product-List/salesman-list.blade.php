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
    <link rel="stylesheet" href="{{ asset('css/admin-salesman-list.css') }}?v={{ filemtime(public_path('css/admin-salesman-list.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Sales Agent List',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Master List'],
                    ['label' => 'Sales Agent List', 'active' => true],
                ],
            ])

            <section
                class="admin-panel admin-salesman"
                data-salesman-list
                data-sal-store-url="{{ $agentStoreUrl }}"
                data-sal-details-template="{{ $agentDetailsUrlTemplate }}"
                data-sal-customers-template="{{ $agentCustomersUrlTemplate }}"
                data-sal-update-template="{{ $agentUpdateUrlTemplate }}"
                data-sal-destroy-template="{{ $agentDestroyUrlTemplate }}"
            >
                <div class="admin-salesman__header">
                    <div>
                        <p class="admin-salesman__kicker">Master Data</p>
                        <h2>Sales Agent List</h2>
                        <p class="admin-salesman__subtitle">{{ $subtitle }}</p>
                    </div>
                </div>

                <div class="sal-card-grid">
                    <article class="sal-metric-card">
                        <span class="sal-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="sal-metric-card__body">
                            <span>Total Sales Agent</span>
                            <strong data-sal-stat-total>{{ number_format($stats['total_agents']) }}</strong>
                        </div>
                    </article>
                    <article class="sal-metric-card">
                        <span class="sal-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <div class="sal-metric-card__body">
                            <span>Assigned</span>
                            <strong data-sal-stat-assigned>{{ number_format($stats['assigned_agents']) }}</strong>
                        </div>
                    </article>
                    <article class="sal-metric-card">
                        <span class="sal-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <rect x="3" y="6" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M3 12h18" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <div class="sal-metric-card__body">
                            <span>Unassigned</span>
                            <strong data-sal-stat-unassigned>{{ number_format($stats['unassigned_agents']) }}</strong>
                        </div>
                    </article>
                </div>

                <div class="sal-table-section">
                    <div class="sal-toolbar">
                        <div class="sal-toolbar-actions">
                            <button type="button" class="sal-action-button sal-action-button--primary" data-sal-add>
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <span>Add Agent</span>
                            </button>
                            <button type="button" class="sal-action-button sal-action-button--primary" data-sal-view disabled>
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <span>View</span>
                            </button>
                            <button type="button" class="sal-action-button sal-action-button--primary" data-sal-edit disabled>
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                                <span>Edit</span>
                            </button>
                            <button type="button" class="sal-action-button sal-action-button--danger" data-sal-delete disabled>
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>

                    <div class="sal-table-shell">
                        <table class="sal-table">
                            <thead>
                                <tr>
                                    <th class="sal-sortable" data-sal-sort="name">Sales Agent<span class="sal-sort-indicator {{ $sortColumn === 'name' ? 'sal-sort-active' : '' }}">{{ $sortColumn === 'name' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▲' }}</span></th>
                                    <th class="sal-sortable" data-sal-sort="phone">Contact No.<span class="sal-sort-indicator {{ $sortColumn === 'phone' ? 'sal-sort-active' : '' }}">{{ $sortColumn === 'phone' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▲' }}</span></th>
                                    <th class="sal-sortable" data-sal-sort="commission_percentage">Commission %<span class="sal-sort-indicator {{ $sortColumn === 'commission_percentage' ? 'sal-sort-active' : '' }}">{{ $sortColumn === 'commission_percentage' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▲' }}</span></th>
                                    <th class="sal-sortable" data-sal-sort="customers_count">Assigned Customer<span class="sal-sort-indicator {{ $sortColumn === 'customers_count' ? 'sal-sort-active' : '' }}">{{ $sortColumn === 'customers_count' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▲' }}</span></th>
                                    <th class="sal-sortable" data-sal-sort="date_started">Date Started<span class="sal-sort-indicator {{ $sortColumn === 'date_started' ? 'sal-sort-active' : '' }}">{{ $sortColumn === 'date_started' ? ($sortDirection === 'asc' ? '▲' : '▼') : '▲' }}</span></th>
                                </tr>
                                <tr>
                                    <form id="sal-search-form" method="GET" action="{{ route('admin.sales-agents.index') }}">
                                        @foreach (['name' => 'Sales Agent', 'phone' => 'Contact No.', 'commission_percentage' => 'Commission %', 'date_started' => 'Date Started'] as $col => $label)
                                            @if ($col === 'commission_percentage')
                                                <th><input type="search" name="search[{{ $col }}]" value="{{ $searches[$col] ?? '' }}" aria-label="Search Commission %" placeholder="Search" data-sal-search></th>
                                            @elseif ($col === 'date_started')
                                                <th><input type="search" name="search[{{ $col }}]" value="{{ $searches[$col] ?? '' }}" aria-label="Search Date Started" placeholder="Search" data-sal-search></th>
                                            @else
                                                <th><input type="search" name="search[{{ $col }}]" value="{{ $searches[$col] ?? '' }}" aria-label="Search {{ $label }}" placeholder="Search" data-sal-search></th>
                                            @endif
                                        @endforeach
                                        <th></th>
                                    </form>
                                </tr>
                            </thead>
                            <tbody data-sal-tbody>
                                @forelse ($agents as $agent)
                                    <tr
                                        tabindex="0"
                                        data-sal-row
                                        data-sal-id="{{ $agent->id }}"
                                    >
                                        <td>{{ $agent->name }}</td>
                                        <td>{{ $agent->phone ?? '--' }}</td>
                                        <td>{{ number_format($agent->commission_percentage ?? 0, 2) }}%</td>
                                        <td>{{ $agent->customers_count ?? $agent->customers()->count() }}</td>
                                        <td>{{ $agent->date_started?->toDateString() ?? '--' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="sal-table__empty">No sales agents found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sal-pagination">
                    <p>
                        @if ($agents->total() > 0)
                            Showing {{ number_format($agents->firstItem()) }}-{{ number_format($agents->lastItem()) }} of {{ number_format($agents->total()) }} sales agents
                        @else
                            Showing 0 sales agents
                        @endif
                    </p>
                    {{ $agents->links() }}
                </div>

                <input type="hidden" id="sal-sort-input" name="sort" value="{{ $sortColumn }}" form="sal-search-form">
                <input type="hidden" id="sal-direction-input" name="direction" value="{{ $sortDirection }}" form="sal-search-form">

                {{-- Add Agent Modal --}}
                <div class="sal-modal" data-sal-add-modal hidden>
                    <div class="sal-modal__backdrop" data-sal-add-close></div>
                    <form class="sal-modal__dialog" data-sal-add-form>
                        <header class="sal-modal__header">
                            <div>
                                <p>Add Sales Agent</p>
                                <h2>Agent Information</h2>
                            </div>
                            <button type="button" class="sal-icon-button" data-sal-add-close aria-label="Close add agent">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="sal-modal__body">
                            <label class="sal-field">
                                <span>Agent No. <em>Auto Generated</em></span>
                                <input type="text" name="agent_no" value="{{ $nextAgentNo }}" readonly data-sal-add-agent-no>
                            </label>
                            <label class="sal-field">
                                <span>Sales Agent</span>
                                <input type="text" name="name" required>
                            </label>
                            <label class="sal-field">
                                <span>Contact No.</span>
                                <input type="tel" name="phone">
                            </label>
                            <label class="sal-field">
                                <span>Commission Percentage</span>
                                <div class="sal-commission-wrap">
                                    <input type="number" name="commission_percentage" min="0" max="100" step="0.01" value="0" required>
                                </div>
                            </label>
                            <label class="sal-field">
                                <span>Date Started</span>
                                <input type="date" name="date_started" max="{{ date('Y-m-d') }}" required>
                            </label>
                        </div>
                        <footer class="sal-modal__footer">
                            <button type="button" class="sal-action-button sal-action-button--secondary" data-sal-add-close>Cancel</button>
                            <button type="submit" class="sal-action-button sal-action-button--primary">Save Agent</button>
                        </footer>
                    </form>
                </div>

                {{-- Edit Agent Modal --}}
                <div class="sal-modal" data-sal-edit-modal hidden>
                    <div class="sal-modal__backdrop" data-sal-edit-close></div>
                    <form class="sal-modal__dialog" data-sal-edit-form>
                        <header class="sal-modal__header">
                            <div>
                                <p>Edit Sales Agent</p>
                                <h2>Agent Information</h2>
                            </div>
                            <button type="button" class="sal-icon-button" data-sal-edit-close aria-label="Close edit agent">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="sal-modal__body">
                            <input type="hidden" data-sal-edit-id>
                            <label class="sal-field">
                                <span>Agent No.</span>
                                <input type="text" data-sal-edit-agent-no readonly>
                            </label>
                            <label class="sal-field">
                                <span>Sales Agent</span>
                                <input type="text" name="name" required>
                            </label>
                            <label class="sal-field">
                                <span>Contact No.</span>
                                <input type="tel" name="phone">
                            </label>
                            <label class="sal-field">
                                <span>Commission Percentage</span>
                                <div class="sal-commission-wrap">
                                    <input type="number" name="commission_percentage" min="0" max="100" step="0.01" value="0" required>
                                </div>
                            </label>
                            <label class="sal-field">
                                <span>Date Started</span>
                                <input type="date" name="date_started" max="{{ date('Y-m-d') }}" required>
                            </label>
                        </div>
                        <footer class="sal-modal__footer">
                            <button type="button" class="sal-action-button sal-action-button--secondary" data-sal-edit-close>Cancel</button>
                            <button type="submit" class="sal-action-button sal-action-button--primary">Update Agent</button>
                        </footer>
                    </form>
                </div>

                {{-- View Agent Modal --}}
                <div class="sal-modal" data-sal-view-modal hidden>
                    <div class="sal-modal__backdrop" data-sal-view-close></div>
                    <article class="sal-modal__dialog sal-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="sal-view-title">
                        <header class="sal-modal__header">
                            <div>
                                <p>Sales Agent Details</p>
                                <h2 id="sal-view-title" data-sal-view-title>--</h2>
                            </div>
                            <button type="button" class="sal-icon-button" data-sal-view-close aria-label="Close agent details">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="sal-modal__body">
                            <div class="sal-view-layout">
                                <div class="sal-view-details">
                                    <h3 style="font-size:0.9375rem;font-weight:600;color:var(--sal-text);margin-bottom:0.75rem;">Agent Information</h3>
                                    <dl class="sal-detail-grid">
                                        <dt>Agent No.</dt>
                                        <dd data-sal-view-agent-no>--</dd>
                                        <dt>Name</dt>
                                        <dd data-sal-view-name>--</dd>
                                        <dt>Contact No.</dt>
                                        <dd data-sal-view-phone>--</dd>
                                        <dt>Commission</dt>
                                        <dd data-sal-view-commission>--</dd>
                                        <dt>Date Started</dt>
                                        <dd data-sal-view-date-started>--</dd>
                                        <dt>Assigned</dt>
                                        <dd data-sal-view-assigned>--</dd>
                                    </dl>
                                </div>
                                <div class="sal-view-customers">
                                    <h3>Assigned Customers</h3>
                                    <p style="font-size:0.8125rem;color:var(--sal-text-muted);margin-bottom:0.75rem;" data-sal-view-customer-info></p>
                                    <div class="sal-table-shell">
                                        <table class="sal-table">
                                            <thead>
                                                <tr>
                                                    <th>Customer Name</th>
                                                    <th>Price Reference</th>
                                                    <th>Remaining Invoice</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody data-sal-view-customer-body>
                                                <tr><td colspan="4" class="sal-table__empty">Select an agent to view customers.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="sal-view-pagination" data-sal-view-pagination></div>
                                </div>
                            </div>
                        </div>
                        <footer class="sal-modal__footer">
                            <button type="button" class="sal-action-button sal-action-button--primary" data-sal-view-close>Close</button>
                        </footer>
                    </article>
                </div>

                {{-- Delete Confirmation Modal --}}
                <div class="sal-modal" data-sal-delete-modal hidden>
                    <div class="sal-modal__backdrop" data-sal-delete-close></div>
                    <article class="sal-modal__dialog sal-modal__dialog--sm" role="dialog" aria-modal="true" aria-labelledby="sal-delete-title">
                        <header class="sal-modal__header--center">
                            <div class="sal-warning-icon">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </header>
                        <div class="sal-modal__body" style="text-align:center;">
                            <h2 id="sal-delete-title" style="font-size:1.125rem;font-weight:600;margin-bottom:0.5rem;">Delete Sales Agent?</h2>
                            <p style="color:var(--sal-text-muted);font-size:0.875rem;margin-bottom:0.75rem;">You are about to delete:</p>
                            <div class="sal-delete-target">
                                <strong data-sal-delete-name>--</strong>
                                <span data-sal-delete-no>--</span>
                            </div>
                            <p style="color:var(--sal-text-muted);font-size:0.8125rem;">Only unassigned sales agents can be deleted.</p>
                        </div>
                        <footer class="sal-modal__footer sal-modal__footer--center">
                            <button type="button" class="sal-action-button sal-action-button--secondary" data-sal-delete-close>Cancel</button>
                            <button type="button" class="sal-action-button sal-action-button--danger" data-sal-delete-confirm>Delete Agent</button>
                        </footer>
                    </article>
                </div>

                {{-- Confirmation Modal (for edit) --}}
                <div class="sal-modal" data-sal-confirm-modal hidden>
                    <div class="sal-modal__backdrop" data-sal-confirm-close></div>
                    <article class="sal-modal__dialog sal-modal__dialog--sm" role="dialog" aria-modal="true">
                        <header class="sal-modal__header">
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <span class="sal-confirm-icon">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <div>
                                    <p style="margin:0 0 4px;color:#071a3d;font-size:12px;font-weight:850;letter-spacing:0;text-transform:uppercase;">Confirm Changes</p>
                                    <h2 style="margin:0;color:#111827;font-weight:850;font-size:1.125rem;">Update Sales Agent?</h2>
                                </div>
                            </div>
                            <button type="button" class="sal-icon-button" data-sal-confirm-close aria-label="Close confirmation">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="sal-modal__body">
                            <p data-sal-confirm-msg style="font-size:0.9375rem;color:var(--sal-text);"></p>
                        </div>
                        <footer class="sal-modal__footer sal-modal__footer--center">
                            <button type="button" class="sal-action-button sal-action-button--secondary" data-sal-confirm-close>Cancel</button>
                            <button type="button" class="sal-action-button sal-action-button--primary" data-sal-confirm-btn>Confirm</button>
                        </footer>
                    </article>
                </div>

                {{-- Success Modal --}}
                <div class="sal-modal" data-sal-success-modal hidden>
                    <div class="sal-modal__backdrop" data-sal-success-close></div>
                    <article class="sal-modal__dialog sal-modal__dialog--sm" role="dialog" aria-modal="true" aria-labelledby="sal-success-title">
                        <header class="sal-modal__header--center">
                            <div class="sal-success-icon">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </header>
                        <div class="sal-modal__body" style="text-align:center;">
                            <h2 id="sal-success-title" data-sal-success-title style="font-size:1.25rem;font-weight:600;margin-bottom:0.5rem;">Success</h2>
                            <p data-sal-success-msg style="color:var(--sal-text-muted);font-size:0.875rem;"></p>
                        </div>
                        <footer class="sal-modal__footer sal-modal__footer--center">
                            <button type="button" class="sal-action-button sal-action-button--success" data-sal-success-close>Done</button>
                        </footer>
                    </article>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-salesman-list.js') }}?v={{ filemtime(public_path('js/admin-salesman-list.js')) }}" defer></script>
</body>
</html>
