<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - {{ $companyName }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}?v={{ filemtime(public_path('css/admin-layout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v={{ filemtime(public_path('css/admin-dashboard.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Dashboard',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Dashboard', 'active' => true],
                ],
            ])

            @if ($errors->any())
                <div class="admin-alert admin-alert--error">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="admin-card-grid" aria-label="Dashboard summary">
                <article class="metric-card">
                    <div class="metric-card__icon metric-card__icon--product" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="m21 8-9-5-9 5 9 5 9-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M3 8v8l9 5 9-5V8" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M12 13v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="metric-card__label">Total Product</p>
                    <p class="metric-card__value">{{ number_format($totalProducts) }}</p>
                    <span class="metric-card__hint">Product records</span>
                </article>

                <article class="metric-card">
                    <div class="metric-card__icon metric-card__icon--warning" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="m10.29 3.86-8.4 14A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.71-3.14l-8.4-14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="metric-card__label">Low Stock</p>
                    <p class="metric-card__value">{{ number_format($lowStockCount) }}</p>
                    <span class="metric-card__hint">{{ $stockRuleLabel }}</span>
                </article>

                <article class="metric-card">
                    <div class="metric-card__icon metric-card__icon--supplier" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M10 17h4V5H2v12h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 8h4l4 4v5h-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="7.5" cy="17.5" r="2.5" stroke="currentColor" stroke-width="2"/>
                            <circle cx="16.5" cy="17.5" r="2.5" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <p class="metric-card__label">Total Supplier</p>
                    <p class="metric-card__value">{{ number_format($totalSuppliers) }}</p>
                    <span class="metric-card__hint">Supplier records</span>
                </article>

                <article class="metric-card">
                    <div class="metric-card__icon metric-card__icon--customer" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="metric-card__label">Total Customer</p>
                    <p class="metric-card__value">{{ number_format($totalCustomers) }}</p>
                    <span class="metric-card__hint">Customer records</span>
                </article>
            </section>

            <section class="admin-content-grid">
                <article class="admin-panel admin-panel--table">
                    <div class="admin-panel__header">
                        <div>
                            <p class="admin-panel__kicker">Inventory</p>
                            <h2 class="admin-panel__title">Product Stock</h2>
                        </div>

                        <div class="stock-tabs" role="tablist" aria-label="Product stock tabs">
                            <button type="button" class="stock-tab {{ $activeStockTab === 'high' ? 'is-active' : '' }}" data-stock-tab="high" role="tab" aria-selected="{{ $activeStockTab === 'high' ? 'true' : 'false' }}">High Stock Products</button>
                            <button type="button" class="stock-tab {{ $activeStockTab === 'low' ? 'is-active' : '' }}" data-stock-tab="low" role="tab" aria-selected="{{ $activeStockTab === 'low' ? 'true' : 'false' }}">Low Stock Product</button>
                        </div>
                    </div>

                    <div class="stock-tab-panel {{ $activeStockTab === 'high' ? 'is-active' : '' }}" data-stock-panel="high" role="tabpanel" @if ($activeStockTab !== 'high') hidden @endif>
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="stock-search-form" data-stock-search-form>
                            <input type="hidden" name="active_stock_tab" value="high">
                            <input type="hidden" name="low_product" value="{{ $stockFilters['low']['product'] }}">
                            <input type="hidden" name="low_unit" value="{{ $stockFilters['low']['unit'] }}">
                            <input type="hidden" name="low_qty" value="{{ $stockFilters['low']['qty'] }}">
                            @if (request()->filled('low_page'))
                                <input type="hidden" name="low_page" value="{{ request('low_page') }}">
                            @endif

                            <div class="admin-table-wrap">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Unit</th>
                                            <th>Available QTY</th>
                                        </tr>
                                        <tr class="admin-table__filters">
                                            <th><input type="search" name="high_product" value="{{ $stockFilters['high']['product'] }}" placeholder="Search Product" aria-label="Search high stock product"></th>
                                            <th><input type="search" name="high_unit" value="{{ $stockFilters['high']['unit'] }}" placeholder="Search Unit" aria-label="Search high stock unit"></th>
                                            <th><input type="search" name="high_qty" value="{{ $stockFilters['high']['qty'] }}" placeholder="Search QTY" aria-label="Search high stock available quantity"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($highStockProducts as $product)
                                            <tr>
                                                <td>{{ $product->product }}</td>
                                                <td>{{ $product->unit }}</td>
                                                <td><span class="qty-badge qty-badge--high">{{ number_format((float) $product->available_qty) }}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="admin-table__empty">No high stock products found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($highStockProducts)
                                <div class="admin-table-pagination">
                                    <p>
                                        Showing {{ $highStockProducts->firstItem() ?? 0 }}-{{ $highStockProducts->lastItem() ?? 0 }}
                                        of {{ $highStockProducts->total() }} products
                                    </p>
                                    @if ($highStockProducts->hasPages())
                                        <nav class="admin-table-pages" aria-label="High stock pagination">
                                            @if ($highStockProducts->onFirstPage())
                                                <span class="admin-table-page-link admin-table-page-link--wide is-disabled">Previous</span>
                                            @else
                                                <a class="admin-table-page-link admin-table-page-link--wide" href="{{ $highStockProducts->previousPageUrl() }}">Previous</a>
                                            @endif

                                            @foreach ($highStockProducts->getUrlRange(1, $highStockProducts->lastPage()) as $page => $url)
                                                @if ($page === $highStockProducts->currentPage())
                                                    <span class="admin-table-page-link is-active" aria-current="page">{{ $page }}</span>
                                                @else
                                                    <a class="admin-table-page-link" href="{{ $url }}">{{ $page }}</a>
                                                @endif
                                            @endforeach

                                            @if ($highStockProducts->hasMorePages())
                                                <a class="admin-table-page-link admin-table-page-link--wide" href="{{ $highStockProducts->nextPageUrl() }}">Next</a>
                                            @else
                                                <span class="admin-table-page-link admin-table-page-link--wide is-disabled">Next</span>
                                            @endif
                                        </nav>
                                    @endif
                                </div>
                            @endif
                        </form>
                    </div>

                    <div class="stock-tab-panel {{ $activeStockTab === 'low' ? 'is-active' : '' }}" data-stock-panel="low" role="tabpanel" @if ($activeStockTab !== 'low') hidden @endif>
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="stock-search-form" data-stock-search-form>
                            <input type="hidden" name="active_stock_tab" value="low">
                            <input type="hidden" name="high_product" value="{{ $stockFilters['high']['product'] }}">
                            <input type="hidden" name="high_unit" value="{{ $stockFilters['high']['unit'] }}">
                            <input type="hidden" name="high_qty" value="{{ $stockFilters['high']['qty'] }}">
                            @if (request()->filled('high_page'))
                                <input type="hidden" name="high_page" value="{{ request('high_page') }}">
                            @endif

                            <div class="admin-table-wrap">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Unit</th>
                                            <th>Available QTY</th>
                                        </tr>
                                        <tr class="admin-table__filters">
                                            <th><input type="search" name="low_product" value="{{ $stockFilters['low']['product'] }}" placeholder="Search Product" aria-label="Search low stock product"></th>
                                            <th><input type="search" name="low_unit" value="{{ $stockFilters['low']['unit'] }}" placeholder="Search Unit" aria-label="Search low stock unit"></th>
                                            <th><input type="search" name="low_qty" value="{{ $stockFilters['low']['qty'] }}" placeholder="Search QTY" aria-label="Search low stock available quantity"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lowStockProducts as $product)
                                            <tr>
                                                <td>{{ $product->product }}</td>
                                                <td>{{ $product->unit }}</td>
                                                <td><span class="qty-badge qty-badge--low">{{ number_format((float) $product->available_qty) }}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="admin-table__empty">No low stock products found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($lowStockProducts)
                                <div class="admin-table-pagination">
                                    <p>
                                        Showing {{ $lowStockProducts->firstItem() ?? 0 }}-{{ $lowStockProducts->lastItem() ?? 0 }}
                                        of {{ $lowStockProducts->total() }} products
                                    </p>
                                    @if ($lowStockProducts->hasPages())
                                        <nav class="admin-table-pages" aria-label="Low stock pagination">
                                            @if ($lowStockProducts->onFirstPage())
                                                <span class="admin-table-page-link admin-table-page-link--wide is-disabled">Previous</span>
                                            @else
                                                <a class="admin-table-page-link admin-table-page-link--wide" href="{{ $lowStockProducts->previousPageUrl() }}">Previous</a>
                                            @endif

                                            @foreach ($lowStockProducts->getUrlRange(1, $lowStockProducts->lastPage()) as $page => $url)
                                                @if ($page === $lowStockProducts->currentPage())
                                                    <span class="admin-table-page-link is-active" aria-current="page">{{ $page }}</span>
                                                @else
                                                    <a class="admin-table-page-link" href="{{ $url }}">{{ $page }}</a>
                                                @endif
                                            @endforeach

                                            @if ($lowStockProducts->hasMorePages())
                                                <a class="admin-table-page-link admin-table-page-link--wide" href="{{ $lowStockProducts->nextPageUrl() }}">Next</a>
                                            @else
                                                <span class="admin-table-page-link admin-table-page-link--wide is-disabled">Next</span>
                                            @endif
                                        </nav>
                                    @endif
                                </div>
                            @endif
                        </form>
                    </div>
                </article>

                <article class="admin-panel admin-panel--chart">
                    @php
                        $qtyLabels = $qtyDistribution['labels'] ?? [];
                        $qtyValues = array_map('floatval', $qtyDistribution['values'] ?? []);
                        $qtyTotal = (float) ($qtyDistribution['total'] ?? array_sum($qtyValues));
                        $qtyPalette = ['#071a3d', '#facc15', '#6c0622', '#2563eb', '#16a34a', '#f97316', '#7c3aed', '#0f766e', '#dc2626', '#64748b'];
                        $qtyAngle = 0;
                        $qtyGradientStops = [];

                        foreach ($qtyValues as $index => $value) {
                            if ($qtyTotal <= 0 || $value <= 0) {
                                continue;
                            }

                            $start = $qtyAngle;
                            $end = $start + (($value / $qtyTotal) * 360);
                            $qtyAngle = $end;
                            $qtyGradientStops[] = $qtyPalette[$index % count($qtyPalette)].' '.$start.'deg '.$end.'deg';
                        }
                    @endphp

                    <div class="admin-panel__header">
                        <div>
                            <p class="admin-panel__kicker">Stock Levels</p>
                            <h2 class="admin-panel__title">Overall QTY Distribution</h2>
                        </div>
                    </div>

                    <div class="chart-shell">
                        <canvas id="qtyDistributionChart" aria-label="Overall QTY Distribution chart" hidden></canvas>
                        <div class="brand-donut" id="qtyDistributionFallback" @if ($qtyTotal <= 0) hidden @endif>
                            <div
                                class="brand-donut__graphic"
                                data-brand-donut-graphic
                                style="background: conic-gradient({{ implode(', ', $qtyGradientStops) }});"
                            >
                                <span>
                                    <small>Total QTY</small>
                                    <strong data-brand-donut-total>{{ number_format($qtyTotal, 0) }}</strong>
                                </span>
                            </div>
                            <div class="brand-donut__legend" data-brand-donut-legend>
                                @foreach ($qtyLabels as $index => $label)
                                    @php
                                        $value = $qtyValues[$index] ?? 0;
                                        $percent = $qtyTotal > 0 ? ($value / $qtyTotal) * 100 : 0;
                                    @endphp
                                    <div class="brand-donut__legend-item">
                                        <span class="brand-donut__swatch" style="background: {{ $qtyPalette[$index % count($qtyPalette)] }}"></span>
                                        <span class="brand-donut__name">{{ $label }}</span>
                                        <strong>{{ number_format($value, 0) }}</strong>
                                        <em>{{ number_format($percent, 2) }}%</em>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="chart-empty" id="qtyDistributionEmptyState" @if ($qtyTotal > 0) hidden @endif>No quantity data available.</div>
                    </div>
                </article>
            </section>

            @if (! empty($schemaNotes))
                <section class="schema-note-panel">
                    @foreach ($schemaNotes as $note)
                        <p>{{ $note }}</p>
                    @endforeach
                </section>
            @endif
        </main>
    </div>

    <script type="application/json" id="qtyDistributionData">@json($qtyDistribution)</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-dashboard.js') }}?v={{ filemtime(public_path('js/admin-dashboard.js')) }}" defer></script>
</body>
</html>
