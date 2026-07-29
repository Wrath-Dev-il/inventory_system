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
    <link rel="stylesheet" href="{{ asset('css/admin-sales-quotation.css') }}?v={{ filemtime(public_path('css/admin-sales-quotation.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Sales Quotations',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Sales'],
                    ['label' => 'Sales Quotations', 'active' => true],
                ],
            ])

            <section
                class="admin-panel"
                data-sq-list
                data-sq-store-url="{{ $storeUrl }}"
                data-sq-data-url="{{ $dataUrl }}"
                data-sq-customers-url="{{ $customersUrl }}"
                data-sq-products-url="{{ $productsUrl }}"
                data-sq-show-url-template="{{ $showUrlTemplate }}"
                data-sq-print-url-template="{{ $printUrlTemplate }}"
                data-sq-logo-url="{{ $logoUrl }}"
            >
                <div class="so-header">
                    <div>
                        <p class="so-header__kicker">Sales Management</p>
                        <h2>Sales Quotations</h2>
                        <p class="so-header__subtitle">{{ $subtitle }}</p>
                    </div>
                </div>

                <div class="sq-notice" data-sq-notice hidden></div>

                <div class="so-toolbar">
                    <div class="so-toolbar__search">
                        <input type="search" data-sq-search-input placeholder="Search quotations..." style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;width:260px;">
                    </div>
                    <div class="so-toolbar__actions">
                        <button type="button" class="sq-btn sq-btn--primary" data-sq-create-button>
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>Create Quotation</span>
                        </button>
                    </div>
                </div>

                <div class="sq-table-shell">
                    <table class="sq-table">
                        <thead>
                            <tr>
                                <th data-sort="quotation_no">Quotation No.<span class="sq-sort-icon">&#9660;</span></th>
                                <th data-sort="customer_name_snapshot">Customer<span class="sq-sort-icon">&#9660;</span></th>
                                <th data-sort="grand_total">Total<span class="sq-sort-icon">&#9660;</span></th>
                                <th data-sort="quotation_date">Date<span class="sq-sort-icon">&#9660;</span></th>
                                <th data-sort="status">Status<span class="sq-sort-icon">&#9660;</span></th>
                                <th data-sort="terms_snapshot">Terms<span class="sq-sort-icon">&#9660;</span></th>
                                <th>Action</th>
                            </tr>
                            <tr class="admin-table__filters">
                                <th><input type="search" class="sq-table__filter-input" name="quotation_no" placeholder="Search" data-sq-col-search></th>
                                <th><input type="search" class="sq-table__filter-input" name="customer_name_snapshot" placeholder="Search" data-sq-col-search></th>
                                <th><input type="search" class="sq-table__filter-input" name="grand_total" placeholder="Search" data-sq-col-search></th>
                                <th><input type="search" class="sq-table__filter-input" name="quotation_date" placeholder="Search" data-sq-col-search></th>
                                <th><input type="search" class="sq-table__filter-input" name="status" placeholder="Search" data-sq-col-search></th>
                                <th><input type="search" class="sq-table__filter-input" name="terms_snapshot" placeholder="Search" data-sq-col-search></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody data-sq-tbody>
                            <tr><td colspan="7" class="sq-table__empty">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="sq-pagination" data-sq-pagination></div>

                {{-- CREATE MODAL (3-step) --}}
                <div class="sq-modal" data-sq-modal hidden>
                    <div class="sq-modal__backdrop" data-sq-modal-close></div>
                    <div class="sq-modal__dialog sq-modal__dialog--large">
                        <div class="sq-modal__header">
                            <div>
                                <p data-sq-modal-kicker>Create Quotation</p>
                                <h2 data-sq-modal-title>New Sales Quotation</h2>
                            </div>
                            <button type="button" class="sq-modal__close" data-sq-modal-close>
                                <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </div>

                        <div class="sq-steps">
                            <div class="sq-step is-active" data-sq-step="1"><span class="sq-step__number">1</span> Customer & Details</div>
                            <div class="sq-step" data-sq-step="2"><span class="sq-step__number">2</span> Select Products</div>
                            <div class="sq-step" data-sq-step="3"><span class="sq-step__number">3</span> Review</div>
                        </div>

                        <div class="sq-modal__body">
                            {{-- STEP 1: Customer & Details --}}
                            <div data-sq-step-panel="1">
                                <div class="sq-field-row">
                                    <div class="sq-field">
                                        <span class="sq-field__label">Quotation No.</span>
                                        <input type="text" data-sq-no readonly value="(Auto-generated)">
                                    </div>
                                    <div class="sq-field">
                                        <span class="sq-field__label">Quotation Date</span>
                                        <input type="date" data-sq-date readonly value="{{ now()->toDateString() }}">
                                    </div>
                                </div>

                                <div class="sq-field" style="margin-top:16px;">
                                    <span class="sq-field__label">Customer <em>Required</em></span>
                                    <div data-sq-customer-display>
                                        <button type="button" class="sq-btn sq-btn--secondary" data-sq-select-customer>
                                            <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2"/></svg>
                                            Select Customer
                                        </button>
                                    </div>
                                    <div class="sq-review-field" data-sq-price-ref-display style="display:none;margin-top:8px;">
                                        <div class="sq-review-field__label">Price Reference</div>
                                        <div class="sq-review-field__value" data-sq-price-ref-label></div>
                                    </div>
                                </div>

                                <div class="sq-field-row" style="margin-top:16px;">
                                    <div class="sq-field">
                                        <span class="sq-field__label">Sales</span>
                                        <input type="text" data-sq-sales placeholder="e.g. Direct">
                                    </div>
                                    <div class="sq-field">
                                        <span class="sq-field__label">Attention To</span>
                                        <input type="text" data-sq-attention-to placeholder="e.g. Mr. Juan Dela Cruz">
                                    </div>
                                </div>

                                <div class="sq-field-row--3 sq-field-row" style="margin-top:16px;">
                                    <div class="sq-field">
                                        <span class="sq-field__label">Lead Time</span>
                                        <input type="date" data-sq-lead-time>
                                    </div>
                                    <div class="sq-field">
                                        <span class="sq-field__label">Valid Until</span>
                                        <input type="date" data-sq-valid-until>
                                    </div>
                                    <div class="sq-field">
                                        <span class="sq-field__label">Mode of Payment</span>
                                        <input type="text" data-sq-mode-of-payment placeholder="e.g. Bank Transfer, Cash, Check">
                                    </div>
                                </div>

                                <div class="sq-field-row" style="margin-top:16px;">
                                    <div class="sq-field">
                                        <span class="sq-field__label">Warranty</span>
                                        <input type="text" data-sq-warranty placeholder="e.g. 1 year">
                                    </div>
                                    <div class="sq-field">
                                        <span class="sq-field__label">Payment Terms</span>
                                        <textarea data-sq-payment-terms placeholder="Enter payment terms..." rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="sq-field-row" style="margin-top:16px;">
                                    <div class="sq-field">
                                        <span class="sq-field__label">Delivery Terms</span>
                                        <textarea data-sq-delivery-terms placeholder="Enter delivery terms..." rows="2"></textarea>
                                    </div>
                                    <div class="sq-field">
                                        <span class="sq-field__label">Cancellation Terms</span>
                                        <textarea data-sq-cancellation-terms placeholder="Enter cancellation terms..." rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="sq-field" style="margin-top:16px;">
                                    <span class="sq-field__label">Remarks</span>
                                    <textarea data-sq-remarks placeholder="Optional remarks..." rows="2"></textarea>
                                </div>
                            </div>

                            {{-- STEP 2: Products --}}
                            <div data-sq-step-panel="2" hidden>
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                                    <div>
                                        <input type="search" data-sq-items-search placeholder="Filter selected items..." style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:4px;font-size:13px;width:240px;">
                                    </div>
                                    <button type="button" class="sq-btn sq-btn--secondary" data-sq-select-items>
                                        <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        Select Items
                                    </button>
                                </div>
                                <div data-sq-items-container>
                                    <p style="color:#94a3b8;text-align:center;padding:24px;">No items selected yet. Click "Select Items" to add products.</p>
                                </div>
                                <div class="sq-total-amount" data-sq-items-total style="display:none;"></div>
                            </div>

                            {{-- STEP 3: Review --}}
                            <div data-sq-step-panel="3" hidden>
                                <div class="sq-review-summary">
                                    <div class="sq-review-field">
                                        <div class="sq-review-field__label">Customer</div>
                                        <div class="sq-review-field__value" data-sq-review-customer>{{ $companyName }}</div>
                                    </div>
                                    <div class="sq-review-field">
                                        <div class="sq-review-field__label">Items</div>
                                        <div class="sq-review-field__value" id="sq-review-item-count">0</div>
                                    </div>
                                </div>
                                <div class="sq-review-print-stage" data-sq-print-preview></div>
                            </div>
                        </div>

                        <div class="sq-modal__footer">
                            <button type="button" class="sq-btn sq-btn--secondary" data-sq-modal-close>Cancel</button>
                            <button type="button" class="sq-btn sq-btn--secondary" data-sq-step-back hidden>Back</button>
                            <button type="button" class="sq-btn sq-btn--primary" data-sq-step-next>Next</button>
                        </div>
                    </div>
                </div>

                {{-- CUSTOMER SELECTION MODAL --}}
                <div class="sq-modal" data-sq-customer-modal hidden>
                    <div class="sq-modal__backdrop" data-sq-customer-close></div>
                    <div class="sq-modal__dialog">
                        <div class="sq-modal__header">
                            <div>
                                <p>Step 1</p>
                                <h2>Select Customer</h2>
                            </div>
                            <button type="button" class="sq-modal__close" data-sq-customer-close>
                                <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <div class="sq-modal__body">
                            <div class="sq-field sq-modal-search">
                                <input type="search" data-sq-customer-search placeholder="Search customers...">
                            </div>
                            <div class="sq-select-table-shell">
                                <table class="sq-select-table">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Customer Name<input type="search" class="sq-select-table__filter" placeholder="Filter" data-sq-customer-col-search data-sq-customer-col-search-field="customer_name"></th>
                                            <th>TIN</th>
                                            <th>Price Ref</th>
                                            <th>Sales Agent</th>
                                            <th>Address</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody data-sq-customer-list></tbody>
                                </table>
                            </div>
                            <div class="sq-select-pagination" data-sq-customer-pagination></div>
                        </div>
                        <div class="sq-modal__footer">
                            <button type="button" class="sq-btn sq-btn--secondary" data-sq-customer-close>Close</button>
                        </div>
                    </div>
                </div>

                {{-- PRODUCT SELECTION MODAL --}}
                <div class="sq-modal" data-sq-product-modal hidden>
                    <div class="sq-modal__backdrop" data-sq-product-close></div>
                    <div class="sq-modal__dialog sq-modal__dialog--large">
                        <div class="sq-modal__header">
                            <div>
                                <p>Step 2</p>
                                <h2>Select Products</h2>
                            </div>
                            <button type="button" class="sq-modal__close" data-sq-product-close>
                                <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <div class="sq-modal__body">
                            <div class="sq-field sq-modal-search">
                                <input type="search" data-sq-product-search placeholder="Search products...">
                            </div>
                            <div class="sq-select-table-shell">
                                <table class="sq-select-table">
                                    <thead>
                                        <tr>
                                            <th style="width:32px;"></th>
                                            <th>Item No.</th>
                                            <th>Product<input type="search" class="sq-select-table__filter" placeholder="Filter" data-sq-product-col-search data-sq-product-col-search-field="product"></th>
                                            <th>Brand<input type="search" class="sq-select-table__filter" placeholder="Filter" data-sq-product-col-search data-sq-product-col-search-field="brand"></th>
                                            <th>Unit</th>
                                            <th>Selling Price</th>
                                            <th>Unit Price (VAT Ex)</th>
                                        </tr>
                                    </thead>
                                    <tbody data-sq-product-list></tbody>
                                </table>
                            </div>
                            <div class="sq-select-pagination" data-sq-product-pagination></div>
                        </div>
                        <div class="sq-modal__footer">
                            <button type="button" class="sq-btn sq-btn--secondary" data-sq-product-close>Cancel</button>
                            <button type="button" class="sq-btn sq-btn--primary" data-sq-product-apply>Apply Selection</button>
                        </div>
                    </div>
                </div>

                {{-- CONFIRM MODAL --}}
                <div class="sq-modal" data-sq-confirm-modal hidden>
                    <div class="sq-modal__backdrop" data-sq-confirm-close></div>
                    <div class="sq-modal__dialog sq-modal__dialog--small">
                        <div class="sq-modal__body" style="text-align:center;">
                            <div class="sq-confirm-icon sq-confirm-icon--warning">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2"/></svg>
                            </div>
                            <h3 data-sq-confirm-title>Confirm</h3>
                            <p data-sq-confirm-message>Are you sure?</p>
                        </div>
                        <div class="sq-modal__footer" style="justify-content:center;">
                            <button type="button" class="sq-btn sq-btn--secondary" data-sq-confirm-close>Cancel</button>
                            <button type="button" class="sq-btn sq-btn--primary" data-sq-confirm-proceed>Yes, Create</button>
                        </div>
                    </div>
                </div>

                {{-- SUCCESS MODAL --}}
                <div class="sq-modal" data-sq-success-modal hidden>
                    <div class="sq-modal__backdrop" data-sq-success-close></div>
                    <div class="sq-modal__dialog sq-modal__dialog--small">
                        <div class="sq-modal__body" style="text-align:center;">
                            <div class="sq-confirm-icon sq-confirm-icon--success">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M22 4 12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <h3 data-sq-success-title>Success</h3>
                            <p data-sq-success-message></p>
                        </div>
                        <div class="sq-modal__footer" style="justify-content:center;">
                            <button type="button" class="sq-btn sq-btn--primary" data-sq-success-close>OK</button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('js/admin-sales-quotation.js') }}?v={{ filemtime(public_path('js/admin-sales-quotation.js')) }}"></script>
</body>
</html>
