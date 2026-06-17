<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

final class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (session('super_admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $adminEmail = (string) config('admin.email');
        $adminHash = (string) config('admin.password');

        if ($adminEmail === '' || $adminHash === '') {
            return back()->withErrors(['email' => 'Admin no configurado en .env (ADMIN_EMAIL / ADMIN_PASSWORD_HASH).']);
        }

        if (
            ! hash_equals(strtolower($adminEmail), strtolower($validated['email']))
            || ! Hash::check($validated['password'], $adminHash)
        ) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('super_admin', true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('super_admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
