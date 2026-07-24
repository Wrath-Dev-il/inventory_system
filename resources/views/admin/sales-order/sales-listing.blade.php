<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sales Listing' }} - {{ $companyName }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}?v={{ filemtime(public_path('css/admin-layout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-sales-listing.css') }}?v={{ filemtime(public_path('css/admin-sales-listing.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Sales Listing',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Sales'],
                    ['label' => 'Sales Listing', 'active' => true],
                ],
            ])

            <section class="admin-panel" id="salesListingApp" data-sales-listing-app>
                <div class="sl-header">
                    <div>
                        <p class="sl-header__kicker">Sales Management</p>
                        <h2>Sales Listing</h2>
                        <p class="sl-header__subtitle">Manage sales orders, invoices, and payments.</p>
                    </div>
                </div>

                <div class="sl-cards" id="slMetricsCards">
                    <article class="sl-card">
                        <span class="sl-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="sl-card__label">Paid</span>
                        <strong class="sl-card__value" data-metric="paid">0</strong>
                    </article>
                    <article class="sl-card">
                        <span class="sl-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span class="sl-card__label">Unpaid</span>
                        <strong class="sl-card__value" data-metric="unpaid">0</strong>
                    </article>
                    <article class="sl-card">
                        <span class="sl-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span class="sl-card__label">Overdue</span>
                        <strong class="sl-card__value" data-metric="overdue">0</strong>
                    </article>
                </div>

                <div class="sl-notice" data-sl-notice hidden></div>

                <div class="sl-toolbar">
                    <div class="sl-toolbar__filters">
                        <div class="sl-filter-group">
                            <button type="button" class="sl-filter-btn is-active" data-payment-filter="all">All</button>
                            <button type="button" class="sl-filter-btn" data-payment-filter="paid">Paid</button>
                            <button type="button" class="sl-filter-btn" data-payment-filter="unpaid">Unpaid</button>
                            <button type="button" class="sl-filter-btn" data-payment-filter="overdue">Overdue</button>
                        </div>
                        <div class="sl-filter-group">
                            <button type="button" class="sl-filter-btn is-active" data-price-filter="all">All Invoices</button>
                            <button type="button" class="sl-filter-btn" data-price-filter="green">Green Invoices</button>
                            <button type="button" class="sl-filter-btn" data-price-filter="yellow">Yellow Invoices</button>
                        </div>
                    </div>
                    <div class="sl-toolbar__actions">
                        <button type="button" class="sl-btn sl-btn--primary" data-save-btn disabled>Save Changes</button>
                    </div>
                </div>

                <div class="sl-table-shell">
                    <div class="sl-table-wrapper">
                        <table class="sl-table" data-sl-table>
                            <thead>
                                <tr>
                                    <th data-sort="so_no">S.O. & D.R No.<span class="sl-sort-icon"></span></th>
                                    <th data-sort="billing_date">Billing Date<span class="sl-sort-icon"></span></th>
                                    <th data-sort="vat_exclusive_total">VAT Ex Total<span class="sl-sort-icon"></span></th>
                                    <th data-sort="total_with_vat">VAT Inc. Total<span class="sl-sort-icon"></span></th>
                                    <th data-sort="transaction_type">Transaction Type<span class="sl-sort-icon"></span></th>
                                    <th data-sort="customer_name">Customer Name<span class="sl-sort-icon"></span></th>
                                    <th data-sort="po_no">P.O. No.<span class="sl-sort-icon"></span></th>
                                    <th data-sort="sales_invoice_no">Sales Invoice No.<span class="sl-sort-icon"></span></th>
                                    <th data-sort="quotation_no">Quotation No.<span class="sl-sort-icon"></span></th>
                                    <th data-sort="sales_agent">Sales Agent<span class="sl-sort-icon"></span></th>
                                    <th data-sort="initial_payment_status">Initial Payment Status<span class="sl-sort-icon"></span></th>
                                    <th data-sort="final_payment_status">Final Payment Status<span class="sl-sort-icon"></span></th>
                                    <th data-sort="actual_payment_remarks">Actual Payment Remarks<span class="sl-sort-icon"></span></th>
                                    <th data-sort="sales_channel">Sales Channel<span class="sl-sort-icon"></span></th>
                                </tr>
                                <tr class="sl-table__filters">
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="so_no" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="billing_date" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="vat_exclusive_total" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="total_with_vat" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="transaction_type" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="customer_name" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="po_no" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="sales_invoice_no" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="quotation_no" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="sales_agent" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="initial_payment_status" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="final_payment_status" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="actual_payment_remarks" placeholder="Search"></th>
                                    <th><input type="search" class="sl-table__filter-input" data-col-search="sales_channel" placeholder="Search"></th>
                                </tr>
                            </thead>
                            <tbody data-sl-tbody>
                                <tr class="sl-empty-row">
                                    <td colspan="14" class="sl-empty-cell">Loading sales listings...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sl-pagination" data-sl-pagination></div>

                <div class="sl-loading" data-sl-loading hidden>
                    <div class="sl-loading__spinner"></div>
                </div>
            </section>
        </main>
    </div>

    <div class="sl-modal-overlay" data-modal-overlay hidden>
        <div class="sl-modal" role="dialog" aria-modal="true">
            <div class="sl-modal__header">
                <h3 class="sl-modal__title" data-modal-title></h3>
                <button type="button" class="sl-modal__close" data-modal-close>&times;</button>
            </div>
            <div class="sl-modal__body" data-modal-body></div>
            <div class="sl-modal__footer" data-modal-footer></div>
        </div>
    </div>

    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-sales-listing.js') }}?v={{ filemtime(public_path('js/admin-sales-listing.js')) }}"></script>
</body>
</html>
