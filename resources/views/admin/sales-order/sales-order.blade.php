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
    <link rel="stylesheet" href="{{ asset('css/admin-sales-order.css') }}?v={{ filemtime(public_path('css/admin-sales-order.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Sales Orders',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Sales'],
                    ['label' => 'Sales Orders', 'active' => true],
                ],
            ])

            <section
                class="admin-panel"
                data-sales-order-list
                data-store-url="{{ $salesOrderStoreUrl }}"
                data-update-url-template="{{ $salesOrderUpdateUrl }}"
                data-show-url-template="{{ $salesOrderShowUrlTemplate }}"
                data-print-so-url-template="{{ $salesOrderPrintSalesOrderUrl }}"
                data-print-si-url-template="{{ $salesOrderPrintSalesInvoiceUrl }}"
                data-print-both-url-template="{{ $salesOrderPrintBothUrl }}"
                data-logo-url="{{ asset('images/login/logo.png') }}"
            >
                <div class="so-header">
                    <div>
                        <p class="so-header__kicker">Sales Management</p>
                        <h2>Sales Orders</h2>
                        <p class="so-header__subtitle">{{ $subtitle }}</p>
                    </div>
                </div>

                <div class="so-notice" data-so-notice hidden></div>

                <div class="so-toolbar">
                    <form method="GET" action="{{ route('admin.sales-order.index') }}" class="so-toolbar__search" data-so-search-form>
                        <input type="search" name="q" value="{{ request('q', '') }}" placeholder="Search sales orders..." style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;width:260px;">
                    </form>
                    <div class="so-toolbar__actions">
                        <select name="price_filter" class="so-filter-select" data-so-price-filter>
                            <option value="">All Customers</option>
                            <option value="green" {{ $priceFilter === 'green' ? 'selected' : '' }}>Green Customer</option>
                            <option value="yellow" {{ $priceFilter === 'yellow' ? 'selected' : '' }}>Yellow Customer</option>
                        </select>
                        <button type="button" class="so-btn so-btn--primary" data-so-create-button>
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>Create Invoice</span>
                        </button>
                    </div>
                </div>

                <div class="so-table-shell">
                    <table class="so-table">
                        <thead>
                            <tr>
                                <th data-sort="so_no">SO/SN No.<span class="so-sort-icon">&#9660;</span></th>
                                <th data-sort="customer_name_snapshot">Customer Name<span class="so-sort-icon">&#9660;</span></th>
                                <th data-sort="total_with_vat">Total<span class="so-sort-icon">&#9660;</span></th>
                                <th data-sort="total_ordered_qty">Ordered QTY<span class="so-sort-icon">&#9660;</span></th>
                                <th data-sort="order_date">Date<span class="so-sort-icon">&#9660;</span></th>
                                <th data-sort="status">Order Status<span class="so-sort-icon">&#9660;</span></th>
                                <th data-sort="payment_status">Payment Status<span class="so-sort-icon">&#9660;</span></th>
                                <th>Action</th>
                            </tr>
                            <tr class="admin-table__filters">
                                <th><input type="search" class="so-table__filter-input" name="search[so_no]" value="{{ $searches['so_no'] ?? '' }}" placeholder="Search" data-so-col-search></th>
                                <th><input type="search" class="so-table__filter-input" name="search[customer_name_snapshot]" value="{{ $searches['customer_name_snapshot'] ?? '' }}" placeholder="Search" data-so-col-search></th>
                                <th><input type="search" class="so-table__filter-input" name="search[total_with_vat]" value="{{ $searches['total_with_vat'] ?? '' }}" placeholder="Search" data-so-col-search></th>
                                <th><input type="search" class="so-table__filter-input" name="search[total_ordered_qty]" value="{{ $searches['total_ordered_qty'] ?? '' }}" placeholder="Search" data-so-col-search></th>
                                <th><input type="search" class="so-table__filter-input" name="search[order_date]" value="{{ $searches['order_date'] ?? '' }}" placeholder="Search" data-so-col-search></th>
                                <th><input type="search" class="so-table__filter-input" name="search[status]" value="{{ $searches['status'] ?? '' }}" placeholder="Search" data-so-col-search></th>
                                <th><input type="search" class="so-table__filter-input" name="search[payment_status]" value="{{ $searches['payment_status'] ?? '' }}" placeholder="Search" data-so-col-search></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($salesOrders as $order)
                                <tr data-so-row data-so-id="{{ $order->id }}">
                                    <td><strong>{{ $order->so_no }}</strong></td>
                                    <td>{{ $order->customer_name_snapshot ?? '--' }}</td>
                                    <td>
                                        <span>With VAT: &#8369;{{ number_format($order->total_with_vat, 2) }}</span>
                                        <br><span style="color:#64748b;font-size:12px;">Without VAT: &#8369;{{ number_format($order->total_without_vat, 2) }}</span>
                                    </td>
                                    <td>{{ number_format($order->total_ordered_qty, 2) }}</td>
                                    <td>{{ $order->order_date?->toDateString() ?? '--' }}</td>
                                    <td><span class="so-badge so-badge--{{ $order->status }}">{{ $order->status }}</span></td>
                                    <td><span class="so-badge so-badge--{{ $order->payment_status }}">{{ $order->payment_status }}</span></td>
                                    <td class="so-actions-cell">
                                        <button type="button" class="so-action-btn" data-so-print title="Print sales order">
                                            <svg viewBox="0 0 24 24" fill="none"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2"/><path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2"/></svg>
                                            <span>Print</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="so-table__empty">No sales orders yet. Click "Create Invoice" to start.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($salesOrders->hasPages())
                    <div class="so-pagination">
                        <p>Showing {{ number_format($salesOrders->firstItem()) }}-{{ number_format($salesOrders->lastItem()) }} of {{ number_format($salesOrders->total()) }} sales orders</p>
                        <div class="so-pagination__links">{{ $salesOrders->links('vendor.pagination.tailwind') }}</div>
                    </div>
                @endif
            {{-- CREATE/EDIT MODAL (3-step) --}}
    <div class="so-modal" data-so-modal hidden>
        <div class="so-modal__backdrop" data-so-modal-close></div>
        <div class="so-modal__dialog so-modal__dialog--large">
            <div class="so-modal__header">
                <div>
                    <p data-so-modal-kicker>Create Invoice</p>
                    <h2 data-so-modal-title>New Sales Order</h2>
                </div>
                <button type="button" class="so-modal__close" data-so-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>

            <div class="so-steps">
                <div class="so-step is-active" data-so-step="1"><span class="so-step__number">1</span> Basic Info</div>
                <div class="so-step" data-so-step="2"><span class="so-step__number">2</span> Select Products</div>
                <div class="so-step" data-so-step="3"><span class="so-step__number">3</span> Review</div>
            </div>

            <div class="so-modal__body">
                {{-- STEP 1: Basic Info --}}
                <div data-so-step-panel="1">
                    <div class="so-field-row">
                        <div class="so-field">
                            <span class="so-field__label">SO No.</span>
                            <input type="text" data-so-no readonly value="(Will be auto-generated)">
                        </div>
                        <div class="so-field">
                            <span class="so-field__label">Date Created</span>
                            <input type="text" data-so-date readonly value="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    <div class="so-field" style="margin-top:16px;">
                        <span class="so-field__label">Select Customer</span>
                        <div data-so-customer-display>
                            <button type="button" class="so-btn so-btn--secondary" data-so-select-customer>
                                <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2"/></svg>
                                Select Customer
                            </button>
                        </div>
                        <div class="so-field__hint">Search and select one customer for this sales order.</div>
                    </div>
                    <div class="so-field" style="margin-top:12px;">
                        <span class="so-field__label">Sales Channel</span>
                        <select data-so-sales-channel>
                            <option value="">Select sales channel</option>
                            <option value="Caloocan">Caloocan</option>
                            <option value="Laguna">Laguna</option>
                        </select>
                    </div>
                </div>

                {{-- STEP 2: Select Products --}}
                <div data-so-step-panel="2" hidden>
                    <button type="button" class="so-btn so-btn--primary" style="margin-bottom:16px;" data-so-select-items>
                        <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Select Items
                    </button>

                    <div style="margin-bottom:12px;">
                        <input type="search" class="so-table__filter-input" style="width:260px;" placeholder="Search selected items..." data-so-items-search>
                    </div>

                    <div data-so-price-ref-display style="display:none;margin-bottom:12px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:13px;color:#166534;">
                        <strong data-so-price-ref-label></strong> &middot; Discount: <strong data-so-discount-percent-display>0.00%</strong>
                    </div>
                    <div data-so-items-container>
                        <p style="color:#94a3b8;text-align:center;padding:24px;">No items selected yet. Click "Select Items" to add products.</p>
                    </div>
                </div>

                {{-- STEP 3: Review --}}
                <div data-so-step-panel="3" hidden>
                    <div class="so-review-tabs">
                        <button type="button" class="so-review-tab is-active" data-so-review-tab="with-vat">WITH VAT — A SALES ORDER</button>
                        <button type="button" class="so-review-tab" data-so-review-tab="without-vat">WITHOUT VAT — A SALES INVOICE</button>
                    </div>

                    <div class="so-review-print-stage" data-so-print-preview></div>
                </div>
            </div>

            <div class="so-modal__footer">
                <button type="button" class="so-btn so-btn--secondary" data-so-step-back hidden>Back</button>
                <button type="button" class="so-btn so-btn--ghost" data-so-modal-close>Cancel</button>
                <button type="button" class="so-btn so-btn--primary" data-so-step-next>Next</button>
            </div>
        </div>
    </div>

    {{-- CUSTOMER SELECTOR MODAL --}}
    <div class="so-modal" data-so-customer-modal hidden>
        <div class="so-modal__backdrop" data-so-customer-close></div>
        <div class="so-modal__dialog so-modal__dialog--large">
            <div class="so-modal__header">
                <div><p>Select Customer</p><h2>Choose a customer for this sales order</h2></div>
                <button type="button" class="so-modal__close" data-so-customer-close><svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/></svg></button>
            </div>
            <div class="so-modal__body">
                <div style="margin-bottom:12px;">
                    <input type="search" class="so-table__filter-input" placeholder="Search customers..." data-so-customer-search>
                </div>
                <div class="so-select-table-shell">
                    <table class="so-select-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Customer Name</th>
                                <th>TIN</th>
                                <th>Price Ref</th>
                                <th>Sales Agent</th>
                                <th>Salesman</th>
                                <th>Address</th>
                                <th>Action</th>
                            </tr>
                            <tr>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-customer-col-search="customer_no"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-customer-col-search="customer_name"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-customer-col-search="tin"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-customer-col-search="price_reference"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-customer-col-search="sales_agent"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-customer-col-search="salesman_name"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-customer-col-search="address"></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody data-so-customer-list></tbody>
                    </table>
                </div>
                <div class="so-select-pagination" data-so-customer-pagination></div>
            </div>
            <div class="so-modal__footer">
                <button type="button" class="so-btn so-btn--secondary" data-so-customer-close>Cancel</button>
            </div>
        </div>
    </div>

    {{-- PRODUCT SELECTOR MODAL --}}
    <div class="so-modal" data-so-product-modal hidden>
        <div class="so-modal__backdrop" data-so-product-close></div>
        <div class="so-modal__dialog so-modal__dialog--large">
            <div class="so-modal__header">
                <div><p>Select Products</p><h2>Choose products for this sales order</h2></div>
                <button type="button" class="so-modal__close" data-so-product-close><svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/></svg></button>
            </div>
            <div class="so-modal__body">
                <div style="margin-bottom:12px;">
                    <input type="search" class="so-table__filter-input" placeholder="Search products..." data-so-product-search>
                </div>
                <div class="so-select-table-shell">
                    <table class="so-select-table">
                        <thead>
                            <tr>
                                <th>Item No.</th>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Unit</th>
                                <th>Available QTY</th>
                                <th>Selling Price</th>
                                <th>Action</th>
                            </tr>
                            <tr>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-product-col-search="item_no"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-product-col-search="product"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-product-col-search="brand"></th>
                                <th><input type="search" class="so-select-table__filter" placeholder="Search" data-so-product-col-search="unit"></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody data-so-product-list></tbody>
                    </table>
                </div>
                <div class="so-select-pagination" data-so-product-pagination></div>
            </div>
            <div class="so-modal__footer">
                <button type="button" class="so-btn so-btn--secondary" data-so-product-close>Cancel</button>
                <button type="button" class="so-btn so-btn--primary" data-so-product-apply>Apply Selected Products</button>
            </div>
        </div>
    </div>

    {{-- CONFIRMATION MODAL --}}
    <div class="so-modal" data-so-confirm-modal hidden>
        <div class="so-modal__backdrop" data-so-confirm-close></div>
        <div class="so-modal__dialog so-modal__dialog--small">
            <div class="so-modal__body">
                <div class="so-confirm-icon so-confirm-icon--warning">
                    <svg viewBox="0 0 24 24" fill="none"><path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2"/><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <div class="so-confirm-body">
                    <h3 data-so-confirm-title>Confirm Sales Order</h3>
                    <p data-so-confirm-message>Are you sure you want to create this sales order?</p>
                </div>
            </div>
            <div class="so-modal__footer" style="justify-content:center;">
                <button type="button" class="so-btn so-btn--secondary" data-so-confirm-close>Cancel</button>
                <button type="button" class="so-btn so-btn--primary" data-so-confirm-proceed>Yes, Create</button>
            </div>
        </div>
    </div>

    {{-- SUCCESS MODAL --}}
    <div class="so-modal" data-so-success-modal hidden>
        <div class="so-modal__backdrop" data-so-success-close></div>
        <div class="so-modal__dialog so-modal__dialog--small">
            <div class="so-modal__body">
                <div class="so-confirm-icon so-confirm-icon--success">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="so-confirm-body">
                    <h3 data-so-success-title>Sales Order Created Successfully</h3>
                    <p data-so-success-message>The sales order has been created and stock has been deducted.</p>
                </div>
            </div>
            <div class="so-modal__footer" style="justify-content:center;">
                <button type="button" class="so-btn so-btn--secondary" data-so-success-close>Close</button>
            </div>
        </div>
    </div>

    {{-- PRINT RECEIPT MODAL --}}
    <div class="so-modal" data-so-print-modal hidden>
        <div class="so-modal__backdrop" data-so-print-close></div>
        <div class="so-modal__dialog so-modal__dialog--small">
            <div class="so-modal__header">
                <div><p>Print Receipt</p><h2>Choose print format</h2></div>
                <button type="button" class="so-modal__close" data-so-print-close><svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/></svg></button>
            </div>
            <div class="so-modal__body">
                <div class="so-print-actions">
                    <button type="button" class="so-print-action-btn" data-so-print-action="sales-order">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2"/><path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2"/></svg>
                        Print Sales Order — With VAT
                    </button>
                    <button type="button" class="so-print-action-btn" data-so-print-action="sales-invoice">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2"/><path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2"/></svg>
                        Print Sales Invoice — Without VAT
                    </button>
                    <button type="button" class="so-print-action-btn" data-so-print-action="both">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2"/><path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2"/></svg>
                        Print Both
                    </button>
                </div>
            </div>
            <div class="so-modal__footer" style="justify-content:center;">
                <button type="button" class="so-btn so-btn--secondary" data-so-print-close>Close</button>
            </div>
        </div>
    </div>

        </section>
        </main>
    </div>

    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-sales-order.js') }}?v={{ filemtime(public_path('js/admin-sales-order.js')) }}" defer></script>
</body>
</html>
