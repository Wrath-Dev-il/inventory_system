<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Sync - {{ $companyName ?? 'CONTROL A' }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}?v={{ filemtime(public_path('css/admin-layout.css')) }}">
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <div class="admin-app">
        @include('Partials.Admin-sidebar')
        <main class="admin-main">
            @include('Partials.Admin-navbar', [
                'pageTitle' => 'Data Sync',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'System Security'],
                    ['label' => 'Data Sync', 'active' => true],
                ],
            ])
            <section class="admin-panel admin-empty-page">
                <div class="admin-empty-page__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 12a8 8 0 0 1 15.57-3M22 12a8 8 0 0 1-15.57 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M18 5v4h-4M6 19v-4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2>Data Sync</h2>
                <p>This module is under construction. Check back later.</p>
            </section>
        </main>
    </div>
    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
</body>
</html>
