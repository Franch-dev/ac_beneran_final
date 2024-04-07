<?php

namespace App\Http\Controllers;

use App\Support\InternalRedirectPath;
use App\Support\PlatformNavigation;
use App\Support\DebugBfd979Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            $target = InternalRedirectPath::normalize($request->query('redirect'));

            return redirect()->to($target ?? PlatformNavigation::homeUrl());
        }

        // #region agent log
        DebugBfd979Log::write('H3', 'login_get_served', [
            'host' => $request->getHost(),
            'session_id_suffix' => $request->hasSession() ? substr((string) $request->session()->getId(), -8) : null,
        ]);
        // #endregion

        return view('auth.login', [
            'redirectTo' => InternalRedirectPath::normalize($request->query('redirect')),
        ]);
    }

    public function login(Request $request)
    {
        // #region agent log
        DebugBfd979Log::write('H4', 'login_post_csrf_ok', [
            'host' => $request->getHost(),
        ]);
        // #endregion

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'redirect' => 'nullable|string',
        ]);

        // Only keys that exist on users — never pass redirect into attempt() (Laravel adds every
        // non-password credential as a WHERE column; users has no redirect column).
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $target = InternalRedirectPath::normalize($validated['redirect'] ?? null);
            if ($target !== null) {
                return redirect()->to($target);
            }

            return redirect()->intended(PlatformNavigation::homeUrl());
        }

        return back()->withErrors(['email' => 'Email atau password salah.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(PlatformNavigation::homeUrl());
    }
}
