<?php

namespace App\Http\Controllers;

use App\Models\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $this->checkTooManyFailedAttempts($request);

        $login = Login::query()
            ->where('User_ID', $request->input('user_id'))
            ->first();

        if (! $login || ! $this->passwordMatches($request->input('password'), $login)) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'user_id' => __('These credentials do not match our records.'),
            ]);
        }

        if ((int) $login->account_type !== 1) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'user_id' => __('This account is not allowed to access the admin dashboard.'),
            ]);
        }

        $this->upgradeLegacyPasswordIfNeeded($request->input('password'), $login);

        Auth::login($login);

        $request->session()->regenerate();
        RateLimiter::clear($this->throttleKey($request));

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }

    /**
     * Check if too many failed login attempts.
     */
    protected function checkTooManyFailedAttempts(Request $request)
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'user_id' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('user_id')).'|'.$request->ip());
    }

    protected function passwordMatches(string $submittedPassword, Login $login): bool
    {
        $storedPassword = (string) $login->Password;

        if ($this->isLegacyPlainTextPassword($storedPassword)) {
            return hash_equals($storedPassword, $submittedPassword);
        }

        return Hash::check($submittedPassword, $storedPassword);
    }

    protected function upgradeLegacyPasswordIfNeeded(string $submittedPassword, Login $login): void
    {
        $storedPassword = (string) $login->Password;

        if (! $this->isLegacyPlainTextPassword($storedPassword)) {
            return;
        }

        $login->forceFill([
            'Password' => Hash::make($submittedPassword),
        ])->save();
    }

    protected function isLegacyPlainTextPassword(string $storedPassword): bool
    {
        $info = password_get_info($storedPassword);

        return ($info['algo'] ?? null) === 0
            || ($info['algo'] ?? null) === null
            || ($info['algoName'] ?? 'unknown') === 'unknown';
    }
}
