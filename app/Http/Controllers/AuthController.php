<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function index(): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        return Auth::user()->role === 'admin'
            ? redirect()->route('admin.dashboard')
            : redirect()->route('dashboard');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        $user = User::where('username', $request->input('username'))->first();

        if ($user && Hash::check($request->input('password'), $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();

            return $user->role === 'admin'
                ? redirect()->route('admin.dashboard')
                : redirect()->route('dashboard');
        }

        return back()
            ->with('error', 'Invalid username or password.')
            ->withInput($request->only('username'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $username = trim((string) $request->input('username'));
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $confirm = (string) $request->input('confirm_password');

        $request->validate([
            'username' => ['required', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'min:8'],
            'confirm_password' => ['required'],
        ]);

        if ($password !== $confirm) {
            return back()
                ->with('error', 'Passwords do not match.')
                ->withInput($request->except('password', 'confirm_password'));
        }

        if (User::where('username', $username)->orWhere('email', $email)->exists()) {
            return back()
                ->with('error', 'Username or email is already taken.')
                ->withInput($request->except('password', 'confirm_password'));
        }

        User::create([
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'user',
        ]);

        return back()->with('success', 'Account created! You can now log in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
