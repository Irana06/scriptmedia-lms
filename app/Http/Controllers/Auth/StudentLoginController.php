<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.siswa-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $student = User::query()
            ->where('username', $credentials['username'])
            ->first();

        if (! $student?->hasRole('siswa') || ! Hash::check($credentials['password'], $student->password)) {
            throw ValidationException::withMessages([
                'username' => 'NISN atau password tidak sesuai.',
            ]);
        }

        Auth::login($student, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended(route(
            $student->must_change_password ? 'siswa.password.required' : 'dashboard.siswa',
            absolute: false,
        ));
    }
}
