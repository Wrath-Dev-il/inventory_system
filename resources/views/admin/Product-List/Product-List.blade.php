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
    <link rel="stylesheet" href="{{ asset('css/admin-product-list.css') }}?v={{ filemtime(public_path('css/admin-product-list.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Product List',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Master List'],
                    ['label' => 'Product List', 'active' => true],
                ],
            ])

            <section
                class="admin-panel admin-product-list"
                data-product-list
                data-product-details-url-template="{{ $productDetailsUrlTemplate }}"
                data-product-store-url="{{ $productStoreUrl }}"
                data-product-update-url="{{ $productUpdateUrl }}"
                data-product-destroy-url-template="{{ $productDestroyUrlTemplate }}"
            >
                <div class="admin-product-list__header">
                    <div>
                        <p class="admin-product-list__kicker">Inventory Master Data</p>
                        <h2>Product List</h2>
                        <p class="admin-product-list__subtitle">{{ $subtitle }}</p>
                    </div>
                </div>

                <div class="product-notice" data-product-notice hidden></div>

                <section class="product-card-grid" aria-label="Product summary">
                    <article class="product-metric-card">
                        <span class="product-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="m21 8-9-5-9 5 9 5 9-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M3 8v8l9 5 9-5V8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>Total Products</span>
                        <strong data-stat="total_products">{{ number_format($stats['total_products']) }}</strong>
                    </article>
                    <article class="product-metric-card">
                        <span class="product-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>High Stocks</span>
                        <strong data-stat="high_stocks">{{ number_format($stats['high_stocks']) }}</strong>
                    </article>
                    <article class="product-metric-card">
                        <span class="product-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Low Stocks</span>
                        <strong data-stat="low_stocks">{{ number_format($stats['low_stocks']) }}</strong>
                    </article>
                    <article class="product-metric-card">
                        <span class="product-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Average Cost</span>
                        <strong data-stat="average_cost">{{ $stats['average_cost'] !== null ? '₱ '.number_format($stats['average_cost'], 2) : 'N/A' }}</strong>
                    </article>
                    <article class="product-metric-card">
                        <span class="product-metric-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M3 17 9 11l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 7h7v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>Ave. Gross Profit</span>
                        <strong data-stat="average_gross_profit">{{ $stats['average_gross_profit'] !== null ? '₱ '.number_format($stats['average_gross_profit'], 2) : 'N/A' }}</strong>
                    </article>
                </section>

                @if (! $tableExists)
                    <div class="admin-product-list__empty">
                        <div class="admin-empty-page__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="m21 8-9-5-9 5 9 5 9-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M3 8v8l9 5 9-5V8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p>No <strong>{{ $tableName }}</strong> table exists in the <strong>inventory_system</strong> database yet.</p>
                    </div>
                @else
                    <section class="product-table-section" aria-label="Product table">
                    <div class="product-table-toolbar">
                    <form method="GET" action="{{ route('admin.products.index') }}" class="product-list-toolbar" data-product-search-form>
                        <button type="button" class="product-action-button product-action-button--secondary" data-product-filter-button>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Filter</span>
                        </button>
                        <div class="product-filter-panel" data-product-filter-panel>
                            <label class="product-control">
                                <span>Filter</span>
                                <select name="filter" data-product-filter>
                                    @foreach ($filters as $value => $label)
                                        <option value="{{ $value }}" @selected($activeFilter === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="product-control product-control--search">
                                <span>Search products</span>
                                <input type="search" name="q" value="{{ $globalSearch }}" placeholder="Search across all columns" data-product-global-search>
                            </label>
                        </div>
                    </form>

                        <div class="product-table-toolbar__actions">
                            <button type="button" class="product-action-button product-action-button--secondary" data-product-add-button>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <span>Add Item</span>
                            </button>
                            <button type="button" class="product-action-button product-action-button--primary" data-product-save-edits disabled>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                                <span>Save Edit</span>
                            </button>
                            <button type="button" class="product-action-button product-action-button--primary" data-product-view-button disabled>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <span>View</span>
                            </button>
                            <button type="button" class="product-action-button product-action-button--danger" data-product-delete-button disabled>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>

                    <div class="product-table-shell">
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>Item No</th>
                                    <th>Product</th>
                                    <th>Brand</th>
                                    <th>Unit</th>
                                    <th>QTY</th>
                                    <th>Restock Level</th>
                                    <th>Item Source</th>
                                    <th>Cost In Yuan</th>
                                    <th>Cost In Peso</th>
                                    <th>Selling Price</th>
                                    <th>Price Online</th>
                                </tr>
                                <tr class="product-table__filters">
                                    @foreach (['item_no' => 'Item No', 'product' => 'Product', 'brand' => 'Brand', 'unit' => 'Unit', 'qty' => 'QTY', 'restock_level' => 'Restock Level', 'item_source' => 'Item Source', 'cost_in_yuan' => 'Cost In Yuan', 'cost_in_peso' => 'Cost In Peso', 'selling_price' => 'Selling Price', 'price_online' => 'Price Online'] as $column => $label)
                                        <th>
                                            <input form="product-column-search-form" type="search" name="search[{{ $column }}]" value="{{ $searches[$column] ?? '' }}" aria-label="Search {{ $label }}" placeholder="Search" data-column-search>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr
                                        tabindex="0"
                                        data-product-row
                                        data-product-id="{{ $product['id'] }}"
                                        class="product-row product-row--{{ $product['stock_status']['tone'] }}"
                                    >
                                        <td data-field="item_no" data-value="{{ $product['item_no'] }}">{{ $product['item_no'] }}</td>
                                        <td data-editable data-field="product" data-value="{{ $product['product'] }}">{{ filled($product['product']) ? $product['product'] : '—' }}</td>
                                        <td data-editable data-field="brand" data-value="{{ $product['brand'] }}">{{ filled($product['brand']) ? $product['brand'] : '—' }}</td>
                                        <td data-editable data-field="unit" data-value="{{ $product['unit'] }}">{{ filled($product['unit']) ? $product['unit'] : '—' }}</td>
                                        <td data-editable data-type="number" data-field="qty" data-value="{{ $product['qty'] }}">{{ $product['qty'] !== null ? number_format($product['qty']) : '—' }}</td>
                                        <td data-editable data-type="number" data-field="restock_level" data-value="{{ $product['restock_level'] }}">{{ $product['restock_level'] !== null ? number_format($product['restock_level']) : '—' }}</td>
                                        <td data-editable data-field="item_source" data-value="{{ $product['item_source'] }}">{{ filled($product['item_source']) ? $product['item_source'] : '—' }}</td>
                                        <td data-editable data-type="money" data-field="cost_in_yuan" data-value="{{ $product['cost_in_yuan'] }}">{{ $product['cost_in_yuan'] !== null ? '¥ '.number_format($product['cost_in_yuan'], 2) : '—' }}</td>
                                        <td data-editable data-type="money" data-field="cost_in_peso" data-value="{{ $product['cost_in_peso'] }}">{{ $product['cost_in_peso'] !== null ? '₱ '.number_format($product['cost_in_peso'], 2) : '—' }}</td>
                                        <td data-editable data-type="money" data-field="selling_price" data-value="{{ $product['selling_price'] }}">{{ $product['selling_price'] !== null ? '₱ '.number_format($product['selling_price'], 2) : '—' }}</td>
                                        <td data-editable data-type="money" data-field="price_online" data-value="{{ $product['price_online'] }}">{{ $product['price_online'] !== null ? '₱ '.number_format($product['price_online'], 2) : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="product-table__empty">No products match the current search or filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    </section>

                    <form id="product-column-search-form" method="GET" action="{{ route('admin.products.index') }}" hidden>
                        <input type="hidden" name="filter" value="{{ $activeFilter }}">
                        <input type="hidden" name="q" value="{{ $globalSearch }}">
                    </form>

                    <div class="product-pagination">
                        <p>
                            @if ($products->total() > 0)
                                Showing {{ number_format($products->firstItem()) }}-{{ number_format($products->lastItem()) }} of {{ number_format($products->total()) }} products
                            @else
                                Showing 0 products
                            @endif
                        </p>
                        {{ $products->links() }}
                    </div>
                @endif

                <div class="product-editor-modal" data-product-add-modal hidden>
                    <div class="product-editor-modal__backdrop" data-product-add-close></div>
                    <form class="product-editor-modal__dialog" data-product-add-form>
                        <header class="product-editor-modal__header">
                            <div>
                                <p>Step 1</p>
                                <h2>Add Item</h2>
                            </div>
                            <button type="button" class="product-icon-button" data-product-add-close aria-label="Close add item">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="product-editor-modal__body">
                            <label class="product-field"><span>Item No</span><input type="text" name="item_no" value="{{ $nextItemNo }}" readonly data-next-item-no></label>
                            <label class="product-field"><span>Product Name</span><input type="text" name="product" required></label>
                            <label class="product-field"><span>Brand</span><input type="text" name="brand"></label>
                            <label class="product-field"><span>Unit</span><input type="text" name="unit"></label>
                            <label class="product-field"><span>QTY</span><input type="number" name="qty" min="0" step="1" value="0" required></label>
                            <label class="product-field"><span>Restock Level</span><input type="number" name="restock_level" min="0" step="1" value="0" required></label>
                            <label class="product-field">
                                <span>Item Source</span>
                                <select name="item_source_id" data-item-source-select>
                                    <option value="">— Select Item Source —</option>
                                    @foreach ($itemSources as $source)
                                        <option value="{{ $source->id }}" data-multiplier="{{ $source->currentEquivalency?->multiplier }}">
                                            {{ $source->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="product-field product-field--multiplier" data-multiplier-display hidden>
                                <span>Multiplier</span>
                                <span data-multiplier-value>—</span>
                            </div>
                            <div class="product-field product-field--cost product-field--cost-yuan">
                                <span>Cost in Yuan</span>
                                <span class="product-cost-input">
                                    <span class="product-cost-prefix">¥</span>
                                    <input type="number" name="cost_in_yuan" min="0" step="0.01" value="0.00" data-cost-yuan>
                                </span>
                            </div>
                            <div class="product-field product-field--cost product-field--cost-peso">
                                <span>Cost in Peso</span>
                                <span class="product-cost-input">
                                    <span class="product-cost-prefix">₱</span>
                                    <input type="number" name="cost_in_peso" min="0" step="0.01" value="0.00" data-cost-peso>
                                </span>
                            </div>
                            <div class="product-field product-field--no-multiplier" data-no-multiplier hidden>
                                <span class="product-field__warning">No multiplier configured for this Item Source. Configure a multiplier in <a href="{{ route('admin.product-configuration.index') }}" target="_blank">Product Configuration</a> first.</span>
                            </div>
                            <label class="product-field"><span>Selling Price</span><input type="number" name="selling_price" min="0" step="0.01" value="0.00" required></label>
                            <label class="product-field"><span>Price Online</span><input type="number" name="price_online" min="0" step="0.01"></label>
                            <label class="product-field"><span>Sea Freight</span><input type="number" name="sea_freight" min="0" step="0.01" value="0.00"></label>
                            <label class="product-field"><span>Air Freight</span><input type="number" name="air_freight" min="0" step="0.01" value="0.00"></label>
                        </div>
                        <footer class="product-editor-modal__footer">
                            <button type="button" class="product-action-button product-action-button--secondary" data-product-add-close>Cancel</button>
                            <button type="submit" class="product-action-button product-action-button--primary">Save Item</button>
                        </footer>
                    </form>
                </div>

                <div class="product-view-modal" data-product-view-modal hidden>
                    <div class="product-view-modal__backdrop" data-product-modal-close></div>
                    <article class="product-view-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="product-view-title">
                        <header class="product-view-modal__header">
                            <div>
                                <p class="product-view-modal__kicker">Product Details</p>
                                <h2 id="product-view-title" data-product-detail="product">—</h2>
                                <span data-product-detail="item_no">—</span>
                            </div>
                            <button type="button" class="product-view-modal__close" data-product-modal-close aria-label="Close product details">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>

                        <div class="product-view-modal__body">
                            <section class="product-detail-section">
                                <h3>Product Information</h3>
                                <div class="product-detail-grid product-detail-grid--two">
                                    <div class="product-detail-field"><span>Item No</span><strong data-product-detail="item_no">—</strong></div>
                                    <div class="product-detail-field"><span>Product</span><strong data-product-detail="product">—</strong></div>
                                    <div class="product-detail-field"><span>Brand</span><strong data-product-detail="brand">—</strong></div>
                                    <div class="product-detail-field"><span>Unit</span><strong data-product-detail="unit">—</strong></div>
                                </div>
                            </section>

                            <section class="product-detail-section">
                                <div class="product-detail-section__title-row">
                                    <h3>Stock Information</h3>
                                    <span class="stock-status stock-status--unknown" data-product-stock-status>Unknown</span>
                                </div>
                                <div class="product-detail-grid product-detail-grid--three">
                                    <div class="product-detail-field"><span>QTY</span><strong data-product-detail="qty">—</strong></div>
                                    <div class="product-detail-field"><span>Restock Level</span><strong data-product-detail="restock_level">—</strong></div>
                                    <div class="product-detail-field"><span>Item Source</span><strong data-product-detail="item_source">—</strong></div>
                                </div>
                            </section>

                            <section class="product-detail-section">
                                <h3>Product Cost</h3>
                                <div class="product-detail-grid product-detail-grid--two">
                                    <div class="product-detail-field"><span>Cost In Yuan</span><strong data-product-detail="cost_yuan">—</strong></div>
                                    <div class="product-detail-field"><span>Cost In Peso</span><strong data-product-detail="cost_peso">—</strong></div>
                                </div>
                            </section>

                            <section class="product-detail-section">
                                <h3>Pricing</h3>
                                <div class="product-detail-grid product-detail-grid--two">
                                    <div class="product-detail-field"><span>Selling Price</span><strong data-product-detail="selling_price">—</strong></div>
                                    <div class="product-detail-field"><span>Price Online</span><strong data-product-detail="price_online">—</strong></div>
                                </div>
                            </section>

                            <section class="product-detail-section">
                                <h3>Freight</h3>
                                <div class="product-detail-grid product-detail-grid--two">
                                    <div class="product-detail-field"><span>Sea Freight</span><strong data-product-detail="sea_freight">—</strong></div>
                                    <div class="product-detail-field"><span>Air Freight</span><strong data-product-detail="air_freight">—</strong></div>
                                </div>
                            </section>

                            <section class="product-detail-section product-detail-section--summary">
                                <h3>Cost &amp; Profit Summary</h3>
                                <div class="product-summary-grid">
                                    <div class="product-summary-card"><span>Total Cost</span><strong data-product-detail="total_cost">—</strong></div>
                                    <div class="product-summary-card"><span>Item Selling Price</span><strong data-product-detail="item_selling_price">—</strong></div>
                                    <div class="product-summary-card" data-profit-card><span>Estimated Profit</span><strong data-product-detail="estimated_profit">—</strong></div>
                                    <div class="product-summary-card"><span>Mark Up</span><strong data-product-detail="markup">—</strong></div>
                                </div>
                            </section>
                        </div>

                        <footer class="product-view-modal__footer">
                            <button type="button" class="product-action-button product-action-button--primary" data-product-modal-close>Close</button>
                        </footer>
                    </article>
                </div>

                <div class="product-delete-modal" data-product-delete-modal hidden>
                    <div class="product-delete-modal__backdrop" data-product-delete-close></div>
                    <article class="product-delete-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="product-delete-title">
                        <header class="product-delete-modal__header">
                            <span class="product-delete-modal__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <button type="button" class="product-delete-modal__close" data-product-delete-close aria-label="Close delete confirmation">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="product-delete-modal__body">
                            <h2 id="product-delete-title">Delete Product?</h2>
                            <p>You are about to delete:</p>
                            <div class="product-delete-modal__target">
                                <strong data-product-delete-item-no>--</strong>
                                <span data-product-delete-name>--</span>
                            </div>
                            <p class="product-delete-modal__warning">This action will remove the selected product from the active inventory list while keeping existing sales and inventory history intact.</p>
                            <p class="product-delete-modal__error" data-product-delete-error hidden></p>
                        </div>
                        <footer class="product-delete-modal__footer">
                            <button type="button" class="product-action-button product-action-button--secondary" data-product-delete-close>Cancel</button>
                            <button type="button" class="product-action-button product-action-button--danger" data-product-delete-confirm>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span data-product-delete-confirm-text>Delete Product</span>
                            </button>
                        </footer>
                    </article>
                </div>

                <div class="product-success-modal" data-product-success-modal hidden>
                    <div class="product-success-modal__backdrop" data-product-success-close></div>
                    <article class="product-success-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="product-success-title">
                        <header class="product-success-modal__header">
                            <span class="product-success-modal__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <button type="button" class="product-success-modal__close" data-product-success-close aria-label="Close success message">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </header>
                        <div class="product-success-modal__body">
                            <h2 id="product-success-title" data-product-success-title>Product Updated Successfully</h2>
                            <p data-product-success-message>The product action completed successfully.</p>
                            <dl class="product-success-modal__details" data-product-success-details hidden></dl>
                        </div>
                        <footer class="product-success-modal__footer">
                            <button type="button" class="product-action-button product-action-button--success" data-product-success-close>Done</button>
                        </footer>
                    </article>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-product-list.js') }}" defer></script>
</body>
</html>
