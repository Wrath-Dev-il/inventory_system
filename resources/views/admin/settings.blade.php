<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Settings - {{ $companyName }}</title>

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
                'pageTitle' => 'Settings',
                'breadcrumbs' => [
                    ['label' => 'Portal'],
                    ['label' => 'Account'],
                    ['label' => 'Settings', 'active' => true],
                ],
            ])

            <section class="admin-panel admin-empty-page">
                <div class="admin-empty-page__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.05.05a2 2 0 1 1-2.83 2.83l-.05-.05A1.8 1.8 0 0 0 15 19.4a1.8 1.8 0 0 0-1 .6V20a2 2 0 1 1-4 0v-.08a1.8 1.8 0 0 0-1-.52 1.8 1.8 0 0 0-1.98.36l-.05.05a2 2 0 1 1-2.83-2.83l.05-.05A1.8 1.8 0 0 0 4.6 15a1.8 1.8 0 0 0-.6-1H4a2 2 0 1 1 0-4h.08a1.8 1.8 0 0 0 .52-1 1.8 1.8 0 0 0-.36-1.98l-.05-.05a2 2 0 1 1 2.83-2.83l.05.05A1.8 1.8 0 0 0 9 4.6a1.8 1.8 0 0 0 1-.6V4a2 2 0 1 1 4 0v.08a1.8 1.8 0 0 0 1 .52 1.8 1.8 0 0 0 1.98-.36l.05-.05a2 2 0 1 1 2.83 2.83l-.05.05A1.8 1.8 0 0 0 19.4 9c.2.35.41.68.6 1H20a2 2 0 1 1 0 4h-.08a1.8 1.8 0 0 0-.52 1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2>Settings</h2>
                <p>Admin settings page is ready for future configuration controls.</p>
            </section>
        </main>
    </div>

    <script src="{{ asset('js/admin-layout.js') }}" defer></script>
</body>
</html>
