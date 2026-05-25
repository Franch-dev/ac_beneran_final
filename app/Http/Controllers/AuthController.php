<?php

namespace App\Http\Controllers;

use App\Support\InternalRedirectPath;
use App\Support\PlatformNavigation;
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

        return view('auth.login', [
'redirectTo' => InternalRedirectPath::normalize($request->query('redirect')),
            'platformHomeUrl' => PlatformNavigation::homeUrl(),
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:255',
            'redirect' => 'nullable|string|max:2048',
        ]);

        // Only keys that exist on users — never pass redirect into attempt() (Laravel adds every
        // non-password credential as a WHERE column; users has no redirect column).
        $credentials = [
            'email' => strtolower(trim($validated['email'])),
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
