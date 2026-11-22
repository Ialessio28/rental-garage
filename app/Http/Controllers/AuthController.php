<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Registration, login and logout.
 */
class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Log the user in. Tenants can only log in after the admin approves them.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withInput()->withErrors(['email' => 'Wrong email or password.']);
        }

        if (! $request->user()->isApproved()) {
            Auth::logout();

            return back()->withInput()->withErrors(['email' => 'Your account has not been approved by the admin.']);
        }

        $request->session()->regenerate();

        return redirect('/');
    }

    /**
     * Show the tenant registration form.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new tenant. The account stays "pending" until the admin checks the NIK.
     */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create($data + ['role' => 'tenant', 'status' => 'pending']);

        return redirect()->route('login')
            ->with('success', 'Registration sent. You can log in after the admin approves your account.');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
