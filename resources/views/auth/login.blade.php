<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CONTROL A Login</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <main class="login-page">
        <section class="login-image-panel" aria-hidden="true"></section>

        <section class="login-panel" aria-label="CONTROL A login">
            <div class="login-panel-inner">
                <img src="{{ asset('images/login/logo.png') }}" alt="CONTROL A logo" class="login-logo">

                <h1 class="company-name">
                    CONTROL A TRADING AND<br>
                    SERVICES CORP.
                </h1>

                @if (session('status'))
                    <div class="status-alert" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" class="login-form" id="loginForm">
                    @csrf

                    <div class="field-group">
                        <label for="user_id" class="field-label">User ID</label>
                        <input
                            id="user_id"
                            type="text"
                            name="user_id"
                            value="{{ old('user_id') }}"
                            required
                            autocomplete="username"
                            class="field-input @error('user_id') field-error @enderror"
                        >
                        @error('user_id')
                            <p class="field-error-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field-group password-group">
                        <label for="password" class="field-label">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="field-input @error('password') field-error @enderror"
                        >
                        @error('password')
                            <p class="field-error-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="remember-row">
                        <input type="checkbox" name="remember" class="remember-checkbox">
                        <span>REMEMBER ME</span>
                    </label>

                    <button type="submit" class="login-button" id="submitBtn">
                        <span id="btnText">LOGIN</span>
                    </button>
                </form>

                <a href="#" class="privacy-link">Privacy Policy</a>
            </div>
        </section>
    </main>

    <script src="{{ asset('js/login.js') }}" defer></script>
</body>
</html>
