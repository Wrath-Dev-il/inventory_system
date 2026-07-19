<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inventory Adjustment - {{ $companyName }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}?v={{ filemtime(public_path('css/admin-layout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v={{ filemtime(public_path('css/admin-dashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-inventory-adjustment.css') }}?v={{ filemtime(public_path('css/admin-inventory-adjustment.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Inventory Adjustment',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Inventory Management'],
                    ['label' => 'Inventory Adjustment', 'active' => true],
                ],
            ])

            <section class="admin-panel inv-panel" data-inv-adjustment>
                <div class="admin-card-grid" id="invStats">
                    <article class="metric-card">
                        <div class="metric-card__icon metric-card__icon--product" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="m21 8-9-5-9 5 9 5 9-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M3 8v8l9 5 9-5V8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M12 13v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <p class="metric-card__label">Total Products</p>
                        <p class="metric-card__value" id="statTotal">{{ number_format($stats['total_products']) }}</p>
                        <span class="metric-card__hint">Product records</span>
                    </article>
                    <article class="metric-card">
                        <div class="metric-card__icon metric-card__icon--high" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="metric-card__label">High Stock</p>
                        <p class="metric-card__value" id="statHigh">{{ number_format($stats['high_stocks']) }}</p>
                        <span class="metric-card__hint">Above threshold</span>
                    </article>
                    <article class="metric-card">
                        <div class="metric-card__icon metric-card__icon--low" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <p class="metric-card__label">Low Stock</p>
                        <p class="metric-card__value" id="statLow">{{ number_format($stats['low_stocks']) }}</p>
                        <span class="metric-card__hint">At or below threshold</span>
                    </article>
                </div>

                <div class="inv-notice" id="invNotice" hidden></div>

                <div class="inv-sticky-bar" id="invStickyBar">
                    <div class="inv-sticky-bar__inner">
                        <span class="inv-sticky-bar__count" id="invPendingCount">No pending changes</span>
                        <button class="inv-btn inv-btn--primary inv-btn--sticky" id="invSaveBtn" disabled>
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            </svg>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </div>

                <div class="inv-toolbar">
                    <div class="inv-tabs" role="tablist">
                        <button class="inv-tab is-active" data-tab="all" role="tab" aria-selected="true">All Products</button>
                        <button class="inv-tab" data-tab="high" role="tab" aria-selected="false">High Stock</button>
                        <button class="inv-tab" data-tab="low" role="tab" aria-selected="false">Low Stock</button>
                    </div>
                </div>

                <div class="inv-table-shell" id="invTableShell">
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th class="inv-th-sort" data-sort="item_no">Item No<span class="inv-sort-icon">&#9650;</span></th>
                                <th class="inv-th-sort" data-sort="product">Product<span class="inv-sort-icon">&#9650;</span></th>
                                <th class="inv-th-sort" data-sort="brand">Brand<span class="inv-sort-icon">&#9650;</span></th>
                                <th class="inv-th-sort" data-sort="unit">Unit<span class="inv-sort-icon">&#9650;</span></th>
                                <th class="inv-th-sort" data-sort="restock_level">Restock Lvl<span class="inv-sort-icon">&#9650;</span></th>
                                <th class="inv-th-sort" data-sort="qty">Qty<span class="inv-sort-icon">&#9650;</span></th>
                            </tr>
                            <tr>
                                <th><span class="inv-search-wrap"><input type="search" data-search="item_no" placeholder="Search" aria-label="Search Item No"></span></th>
                                <th><span class="inv-search-wrap"><input type="search" data-search="product" placeholder="Search" aria-label="Search Product"></span></th>
                                <th><span class="inv-search-wrap"><input type="search" data-search="brand" placeholder="Search" aria-label="Search Brand"></span></th>
                                <th><span class="inv-search-wrap"><input type="search" data-search="unit" placeholder="Search" aria-label="Search Unit"></span></th>
                                <th><span class="inv-search-wrap"><input type="search" data-search="restock_level" placeholder="Search" aria-label="Search Restock Level"></span></th>
                                <th><span class="inv-search-wrap"><input type="search" data-search="qty" placeholder="Search" aria-label="Search Qty"></span></th>
                            </tr>
                        </thead>
                        <tbody id="invTableBody">
                            <tr><td colspan="6" class="inv-empty">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="inv-pagination" id="invPagination">
                    <p id="invPageInfo"></p>
                    <div class="inv-pagination__links" id="invPageLinks"></div>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-inventory-adjustment.js') }}?v={{ filemtime(public_path('js/admin-inventory-adjustment.js')) }}"></script>
</body>
</html>
