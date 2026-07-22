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
    <link rel="stylesheet" href="{{ asset('css/admin-customer-list.css') }}?v={{ filemtime(public_path('css/admin-customer-list.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Customer List',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Master List'],
                    ['label' => 'Customer List', 'active' => true],
                ],
            ])

            <section
                class="admin-panel admin-customers"
                data-customer-list
                data-customer-store-url="{{ $customerStoreUrl }}"
                data-customer-details-url-template="{{ $customerDetailsUrlTemplate }}"
                data-customer-update-url="{{ $customerUpdateUrl }}"
                data-customer-destroy-url-template="{{ $customerDestroyUrlTemplate }}"
                data-sales-agents='@json($salesAgents)'
            >
                <div class="admin-customers__header">
                    <div>
                        <p class="admin-customers__kicker">Master Data</p>
                        <h2>Customer List</h2>
                        <p class="admin-customers__subtitle">{{ $subtitle }}</p>
                    </div>
                </div>

                <div class="customer-notice" data-customer-notice hidden></div>

                <section class="admin-customers__cards" aria-label="Customer summary">
                    <article class="customer-metric-card">
                        <span class="customer-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Total Customer</span>
                        <strong data-customer-stat="total_customers">{{ number_format($stats['total_customers']) }}</strong>
                    </article>
                    <article class="customer-metric-card">
                        <span class="customer-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M18 5h3v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>Yellow Customer</span>
                        <strong data-customer-stat="yellow_customers">{{ number_format($stats['yellow_customers']) }}</strong>
                    </article>
                    <article class="customer-metric-card">
                        <span class="customer-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <span>Green Customer</span>
                        <strong data-customer-stat="green_customers">{{ number_format($stats['green_customers']) }}</strong>
                    </article>
                    <article class="customer-metric-card">
                        <span class="customer-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <rect x="3" y="6" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M3 12h18" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <span>Total Sales Agent</span>
                        <strong data-customer-stat="total_sales_agents">{{ number_format($stats['total_sales_agents']) }}</strong>
                    </article>
                </section>

                @if (! $tableExists)
                    <div class="admin-customers__empty">
                        <p>The customer database tables are not available yet. Run migrations to enable the Customer List.</p>
                    </div>
                @else
                    <section class="admin-customers__table-section" aria-label="Customer table">
                        <div class="admin-customers__toolbar">
                            <form method="GET" action="{{ route('admin.customers.index') }}" class="admin-customers__search-form" data-customer-search-form>
                                <label class="customer-control customer-control--search">
                                    <span>Search customers</span>
                                    <input type="search" name="q" value="{{ $globalSearch }}" placeholder="Search across all columns" data-customer-global-search>
                                </label>
                            </form>

                            <div class="admin-customers__toolbar-actions">
                                <button type="button" class="customer-action-button customer-action-button--primary" data-customer-add-button>
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    <span>Add Customer</span>
                                </button>
                                <button type="button" class="customer-action-button customer-action-button--primary" data-customer-view-button disabled>
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span>View</span>
                                </button>
                                <button type="button" class="customer-action-button customer-action-button--danger" data-customer-delete-button disabled>
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    <span>Delete</span>
                                </button>
                                <button type="button" class="customer-action-button customer-action-button--primary" data-customer-save-updates disabled>
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        <path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    </svg>
                                    <span>Save Update</span>
                                </button>
                            </div>
                        </div>

                        <div class="admin-customers__table-shell">
                            <table class="admin-customers__table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Customer Name</th>
                                        <th>Tin</th>
                                        <th>Price Reference</th>
                                        <th>%Disc</th>
                                        <th>Salesman</th>
                                        <th>Address</th>
                                        <th>Date Started</th>
                                        <th>Terms</th>
                                    </tr>
                                    <tr class="admin-customers__filters">
                                        @foreach (['customer_no' => 'No', 'customer_name' => 'Customer Name', 'tin' => 'Tin', 'price_reference' => 'Price Reference', 'discount_percent' => '%Disc', 'salesman' => 'Salesman', 'address' => 'Address', 'date_started' => 'Date Started', 'terms' => 'Terms'] as $column => $label)
                                            <th>
                                                <input form="customer-column-search-form" type="search" name="search[{{ $column }}]" value="{{ $searches[$column] ?? '' }}" aria-label="Search {{ $label }}" placeholder="Search" data-customer-column-search>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($customers as $customer)
                                        <tr
                                            tabindex="0"
                                            class="admin-customers__row admin-customers__row--{{ $customer['price_reference'] }}"
                                            data-customer-row
                                            data-customer-id="{{ $customer['id'] }}"
                                        >
                                            <td data-field="customer_no" data-value="{{ $customer['customer_no'] }}">{{ $customer['customer_no'] }}</td>
                                            <td data-customer-editable data-field="customer_name" data-value="{{ $customer['customer_name'] }}">{{ filled($customer['customer_name']) ? $customer['customer_name'] : '--' }}</td>
                                            <td data-customer-editable data-field="tin" data-value="{{ $customer['tin'] }}">{{ filled($customer['tin']) ? $customer['tin'] : '--' }}</td>
                                            <td data-customer-editable data-type="price-reference" data-field="price_reference" data-value="{{ $customer['price_reference'] }}"><span class="customer-reference-badge customer-reference-badge--{{ $customer['price_reference'] }}">{{ $customer['price_reference_label'] }}</span></td>
                                            <td data-customer-editable data-type="number" data-field="discount_percent" data-value="{{ $customer['discount_percent'] }}">{{ number_format($customer['discount_percent'] ?? 0, 2) }}%</td>
                                            <td data-customer-editable data-type="sales-agent" data-field="sales_agent_id" data-value="{{ $customer['sales_agent_id'] }}">{{ filled($customer['salesman_name']) ? $customer['salesman_name'] : '--' }}</td>
                                            <td data-customer-editable data-field="address" data-value="{{ $customer['address'] }}">{{ filled($customer['address']) ? $customer['address'] : '--' }}</td>
                                            <td data-customer-editable data-type="date" data-field="date_started" data-value="{{ $customer['date_started'] }}">{{ filled($customer['date_started']) ? $customer['date_started'] : '--' }}</td>
                                            <td data-customer-editable data-field="terms" data-value="{{ $customer['terms'] }}">{{ filled($customer['terms']) ? $customer['terms'] : '--' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="admin-customers__table-empty">No customers match the current search.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <form id="customer-column-search-form" method="GET" action="{{ route('admin.customers.index') }}" hidden>
                        <input type="hidden" name="q" value="{{ $globalSearch }}">
                    </form>

                    <div class="admin-customers__pagination">
                        <p>
                            @if ($customers->total() > 0)
                                Showing {{ number_format($customers->firstItem()) }}-{{ number_format($customers->lastItem()) }} of {{ number_format($customers->total()) }} customers
                            @else
                                Showing 0 customers
                            @endif
                        </p>
                        {{ $customers->links() }}
                    </div>
                @endif

                <div class="customer-modal" data-customer-add-modal hidden>
                    <div class="customer-modal__backdrop" data-customer-add-close></div>
                    <form class="customer-modal__dialog customer-modal__dialog--large" data-customer-add-form>
                        <header class="customer-modal__header">
                            <div>
                                <p>Add Customer</p>
                                <h2>Customer Information</h2>
                            </div>
                            <button type="button" class="customer-icon-button" data-customer-add-close aria-label="Close add customer">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>

                        <div class="customer-modal__body customer-modal__body--grid">
                            <label class="customer-field"><span>No <em>Auto Generated</em></span><input type="text" name="customer_no" value="{{ $nextCustomerNo }}" readonly data-next-customer-no></label>
                            <label class="customer-field"><span>Customer Name</span><input type="text" name="customer_name" required></label>
                            <label class="customer-field"><span>Tin</span><input type="text" name="tin"></label>
                            <fieldset class="customer-field customer-field--radio">
                                <legend>Price Reference</legend>
                                <label class="customer-radio-card customer-radio-card--green">
                                    <input type="radio" name="price_reference" value="green" checked data-price-reference-radio>
                                    <span>Green</span>
                                </label>
                                <label class="customer-radio-card customer-radio-card--yellow">
                                    <input type="radio" name="price_reference" value="yellow" data-price-reference-radio>
                                    <span>Yellow</span>
                                </label>
                            </fieldset>
                            <label class="customer-field"><span>%Disc <em data-discount-hint></em></span><input type="number" name="discount_percent" min="0" max="100" step="0.01" value="0"></label>
                            <label class="customer-field">
                                <span>Salesman</span>
                                <select name="sales_agent_id">
                                    <option value="">Select salesman</option>
                                    @foreach ($salesAgents as $agent)
                                        <option value="{{ $agent['id'] }}">{{ $agent['name'] }} ({{ $agent['agent_no'] }})</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="customer-field"><span>Date Started As Customer</span><input type="date" name="date_started"></label>
                            <label class="customer-field"><span>Terms</span><input type="text" name="terms" placeholder="Cash, 7 Days, 30 Days, or agreed terms"></label>
                            <label class="customer-field">
                                <span>Address</span>
                                <textarea name="address" rows="3" required></textarea>
                            </label>
                        </div>

                        <footer class="customer-modal__footer">
                            <button type="button" class="customer-action-button customer-action-button--secondary" data-customer-add-close>Cancel</button>
                            <button type="submit" class="customer-action-button customer-action-button--primary" data-customer-create-button>Save Customer</button>
                        </footer>
                    </form>
                </div>

                <div class="customer-view-modal" data-customer-view-modal hidden>
                    <div class="customer-view-modal__backdrop" data-customer-view-close></div>
                    <article class="customer-view-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="customer-view-title">
                        <header class="customer-view-modal__header">
                            <div>
                                <p>Customer Details</p>
                                <h2 id="customer-view-title" data-customer-detail="customer_name">--</h2>
                                <span data-customer-detail="customer_no">--</span>
                            </div>
                            <button type="button" class="customer-icon-button" data-customer-view-close aria-label="Close customer details">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="customer-view-modal__body">
                            <section class="customer-detail-section">
                                <h3>Customer Information</h3>
                                <div class="customer-detail-grid">
                                    <div><span>No</span><strong data-customer-detail="customer_no">--</strong></div>
                                    <div><span>Customer Name</span><strong data-customer-detail="customer_name">--</strong></div>
                                    <div><span>Tin</span><strong data-customer-detail="tin">--</strong></div>
                                </div>
                            </section>
                            <section class="customer-detail-section">
                                <h3>Pricing Reference</h3>
                                <div class="customer-detail-grid">
                                    <div><span>Price Reference</span><strong data-customer-detail="price_reference_label">--</strong></div>
                                    <div><span>%Disc</span><strong data-customer-detail="discount_percent">--</strong></div>
                                </div>
                            </section>
                            <section class="customer-detail-section">
                                <h3>Sales Information</h3>
                                <div class="customer-detail-grid">
                                    <div><span>Salesman</span><strong data-customer-detail="salesman">--</strong></div>
                                    <div><span>Date Started As Customer</span><strong data-customer-detail="date_started">--</strong></div>
                                    <div><span>Terms</span><strong data-customer-detail="terms">--</strong></div>
                                </div>
                            </section>
                            <section class="customer-detail-section">
                                <h3>Customer Address</h3>
                                <p class="customer-detail-address" data-customer-detail="address">--</p>
                            </section>
                        </div>
                        <footer class="customer-view-modal__footer">
                            <button type="button" class="customer-action-button customer-action-button--primary" data-customer-view-close>Close</button>
                        </footer>
                    </article>
                </div>

                <div class="customer-delete-modal" data-customer-delete-modal hidden>
                    <div class="customer-delete-modal__backdrop" data-customer-delete-close></div>
                    <article class="customer-delete-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="customer-delete-title">
                        <header class="customer-delete-modal__header">
                            <span class="customer-delete-modal__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <button type="button" class="customer-icon-button" data-customer-delete-close aria-label="Close delete confirmation">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="customer-delete-modal__body">
                            <h2 id="customer-delete-title">Delete Customer?</h2>
                            <p>You are about to delete:</p>
                            <div class="customer-delete-modal__target">
                                <strong data-customer-delete-no>--</strong>
                                <span data-customer-delete-name>--</span>
                            </div>
                            <p class="customer-delete-modal__warning">This action will remove the selected customer if it has no transaction references.</p>
                            <p class="customer-delete-modal__error" data-customer-delete-error hidden></p>
                        </div>
                        <footer class="customer-delete-modal__footer">
                            <button type="button" class="customer-action-button customer-action-button--secondary" data-customer-delete-close>Cancel</button>
                            <button type="button" class="customer-action-button customer-action-button--danger" data-customer-delete-confirm><span data-customer-delete-confirm-text>Delete Customer</span></button>
                        </footer>
                    </article>
                </div>

                <div class="customer-success-modal" data-customer-success-modal hidden>
                    <div class="customer-success-modal__backdrop" data-customer-success-close></div>
                    <article class="customer-success-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="customer-success-title">
                        <header class="customer-success-modal__header">
                            <span class="customer-success-modal__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <button type="button" class="customer-icon-button" data-customer-success-close aria-label="Close success message">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="customer-success-modal__body">
                            <h2 id="customer-success-title" data-customer-success-title>Customer Updated Successfully</h2>
                            <p data-customer-success-message>The customer action completed successfully.</p>
                            <dl class="customer-success-modal__details" data-customer-success-details hidden></dl>
                        </div>
                        <footer class="customer-success-modal__footer">
                            <button type="button" class="customer-action-button customer-action-button--success" data-customer-success-close>Done</button>
                        </footer>
                    </article>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-customer-list.js') }}?v={{ filemtime(public_path('js/admin-customer-list.js')) }}" defer></script>
</body>
</html>
