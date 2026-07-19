<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Product Configuration - {{ $companyName }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}?v={{ filemtime(public_path('css/admin-layout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin-product-configuration.css') }}?v={{ filemtime(public_path('css/admin-product-configuration.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')

        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Product Configuration',
                'pageSubtitle' => 'Manage item sources, Yuan-to-Peso rates, and equivalency multipliers.',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Master List'],
                    ['label' => 'Product Configuration', 'active' => true],
                ],
            ])

            <section class="admin-panel" data-pc-app>
                <div class="pc-notice" id="pcNotice" hidden></div>

                <div class="pc-grid">
                    <div class="pc-card" id="pcSourcesCard">
                        <div class="pc-card__header">
                            <span class="pc-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 4h6v5H4zM14 4h6v5h-6zM4 15h6v5H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M10 6.5h2a2 2 0 0 1 2 2V17h-4M14 17h2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <h2 class="pc-card__title">Item Sources</h2>
                        </div>
                        <div class="pc-card__body">
                            <form class="pc-inline-form" id="pcSourceForm">
                                @csrf
                                <input type="text" class="pc-input" id="pcSourceInput" name="name" placeholder="Enter item source" maxlength="200" autocomplete="off">
                                <button type="submit" class="pc-btn pc-btn--primary" id="pcSourceBtn">Add Item Source</button>
                            </form>
                            <div class="pc-source-list" id="pcSourceList">
                                @forelse ($sources as $source)
                                    <span class="pc-source-tag">{{ $source->name }}</span>
                                @empty
                                    <p class="pc-empty" id="pcSourceEmpty">No item sources yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="pc-card" id="pcConverterCard">
                        <div class="pc-card__header">
                            <span class="pc-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 20V10M8 14l4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4 4h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <h2 class="pc-card__title">Yuan to Peso Converter</h2>
                        </div>
                        <div class="pc-card__body">
                            <div id="pcRateStatus">
                                @if ($rateConfigured)
                                    @if ($rate)
                                        <div class="pc-rate-info">
                                            <span class="pc-rate-label">1 CNY = <strong>&#8369;{{ number_format($rate['rate'], 6) }}</strong></span>
                                            <span class="pc-rate-source">{{ $rate['provider'] }}</span>
                                            @if (!empty($rate['cached']))
                                                <span class="pc-rate-badge pc-rate-badge--warn">Cached</span>
                                            @endif
                                            <span class="pc-rate-time">Updated: {{ $rate['retrieved_at'] ?? 'N/A' }}</span>
                                        </div>
                                    @else
                                        <p class="pc-empty">Unable to load currency rate.</p>
                                    @endif
                                @else
                                    <p class="pc-empty">Google currency rate is not configured.</p>
                                @endif
                            </div>

                            <div class="pc-converter">
                                <div class="pc-converter__field">
                                    <label class="pc-converter__label">Yuan</label>
                                    <div class="pc-converter__input-wrap">
                                        <span class="pc-converter__prefix">&yen;</span>
                                        <input type="number" class="pc-input pc-converter__input" id="pcYuanInput" step="0.0001" min="0" placeholder="0.0000" {{ !$rateConfigured || !$rate ? 'disabled' : '' }}>
                                    </div>
                                </div>
                                <div class="pc-converter__arrow" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="pc-converter__field">
                                    <label class="pc-converter__label">Peso</label>
                                    <div class="pc-converter__input-wrap">
                                        <span class="pc-converter__prefix">&#8369;</span>
                                        <input type="text" class="pc-input pc-converter__input pc-converter__input--result" id="pcPesoOutput" readonly value="0.0000">
                                    </div>
                                </div>
                            </div>

                            @if ($rateConfigured)
                                <button class="pc-btn pc-btn--secondary pc-btn--rate" id="pcRefreshRateBtn" data-pc-rate="{{ $rate['rate'] ?? '' }}">
                                    <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
                                        <path d="M4 12a8 8 0 0 1 15.57-3M22 12a8 8 0 0 1-15.57 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M18 5v4h-4M6 19v-4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Refresh Rate
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pc-card" id="pcEquivCard">
                    <div class="pc-card__header">
                        <span class="pc-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M8 21h8M12 17v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <h2 class="pc-card__title">Item Source Equivalency</h2>
                    </div>
                    <div class="pc-card__body">
                        <div class="pc-equiv-table-wrap">
                            <table class="pc-equiv-table">
                                <thead>
                                    <tr>
                                        <th>Item Source</th>
                                        <th>Multiplier</th>
                                        <th>Yuan Preview</th>
                                        <th>Peso Preview</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="pcEquivTableBody">
                                    <tr><td colspan="5" class="pc-empty-cell">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="pc-card" id="pcLogsCard">
                    <div class="pc-card__header">
                        <span class="pc-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <h2 class="pc-card__title">Conversion Logs</h2>
                    </div>
                    <div class="pc-card__body">
                        <div class="pc-logs-shell">
                            <table class="pc-logs-table">
                                <thead>
                                    <tr>
                                        <th class="pc-th-sort" data-sort="item_source">Item Source<span class="pc-sort-icon">&#9650;</span></th>
                                        <th class="pc-th-sort" data-sort="multiplier">Multiplier<span class="pc-sort-icon">&#9650;</span></th>
                                        <th class="pc-th-sort" data-sort="yuan">Yuan<span class="pc-sort-icon">&#9650;</span></th>
                                        <th class="pc-th-sort" data-sort="peso">Peso<span class="pc-sort-icon">&#9650;</span></th>
                                        <th class="pc-th-sort" data-sort="date">Date<span class="pc-sort-icon">&#9650;</span></th>
                                    </tr>
                                    <tr>
                                        <th><span class="pc-search-wrap"><input type="search" data-search="search_item_source" placeholder="Search source" aria-label="Search Item Source"></span></th>
                                        <th><span class="pc-search-wrap"><input type="search" data-search="search_multiplier" placeholder="Search multiplier" aria-label="Search Multiplier"></span></th>
                                        <th><span class="pc-search-wrap"><input type="search" data-search="search_yuan" placeholder="Search Yuan" aria-label="Search Yuan"></span></th>
                                        <th><span class="pc-search-wrap"><input type="search" data-search="search_peso" placeholder="Search Peso" aria-label="Search Peso"></span></th>
                                        <th><span class="pc-search-wrap"><input type="search" data-search="search_date" placeholder="Search date" aria-label="Search Date"></span></th>
                                    </tr>
                                </thead>
                                <tbody id="pcLogsBody">
                                    <tr><td colspan="5" class="pc-empty-cell">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pc-pagination" id="pcPagination">
                            <p id="pcPageInfo"></p>
                            <div class="pc-pagination__links" id="pcPageLinks"></div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Edit Modal --}}
            <div class="pc-modal-overlay" id="pcEditModalOverlay" hidden>
                <div class="pc-modal" role="dialog" aria-labelledby="pcEditModalTitle" aria-modal="true">
                    <div class="pc-modal__header">
                        <h3 class="pc-modal__title" id="pcEditModalTitle">Edit Item Source</h3>
                        <button type="button" class="pc-modal__close" id="pcEditModalClose" aria-label="Close">&times;</button>
                    </div>
                    <form id="pcEditForm">
                        <div class="pc-modal__body">
                            <input type="hidden" id="pcEditId">
                            <label class="pc-converter__label" for="pcEditName">Source Name</label>
                            <input type="text" class="pc-input" id="pcEditName" maxlength="200" autocomplete="off" required>
                        </div>
                        <div class="pc-modal__footer">
                            <button type="button" class="pc-btn pc-btn--secondary" id="pcEditCancel">Cancel</button>
                            <button type="submit" class="pc-btn pc-btn--primary" id="pcEditBtn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Delete Confirmation Modal --}}
            <div class="pc-modal-overlay" id="pcDeleteModalOverlay" hidden>
                <div class="pc-modal pc-modal--sm" role="dialog" aria-labelledby="pcDeleteModalTitle" aria-modal="true">
                    <div class="pc-modal__header">
                        <h3 class="pc-modal__title" id="pcDeleteModalTitle">Delete Item Source</h3>
                        <button type="button" class="pc-modal__close" id="pcDeleteModalClose" aria-label="Close">&times;</button>
                    </div>
                    <div class="pc-modal__body">
                        <input type="hidden" id="pcDeleteId">
                        <p>Are you sure you want to delete <strong id="pcDeleteName"></strong>?</p>
                        <p class="pc-empty" style="margin-top:.5rem">This action cannot be undone.</p>
                    </div>
                    <div class="pc-modal__footer">
                        <button type="button" class="pc-btn pc-btn--secondary" id="pcDeleteCancel">Cancel</button>
                        <button type="button" class="pc-btn pc-btn--primary" style="background:#dc2626;border-color:#dc2626" id="pcDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
    <script src="{{ asset('js/admin-product-configuration.js') }}?v={{ filemtime(public_path('js/admin-product-configuration.js')) }}"></script>
</body>
</html>
