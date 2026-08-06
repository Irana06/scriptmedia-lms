<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequiredPasswordController extends Controller
{
    use PasswordValidationRules;

    public function edit(): View
    {
        return view('auth.wajib-ganti-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => $this->passwordRules(),
        ]);

        $request->user()->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard.siswa')
            ->with('status', 'Password berhasil diperbarui. Selamat belajar!');
    }
}
