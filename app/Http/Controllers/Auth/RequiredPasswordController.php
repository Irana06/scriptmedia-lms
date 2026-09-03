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

        $route = match (true) {
            $request->user()->hasRole('admin') => 'dashboard.admin',
            $request->user()->hasRole('guru') => 'dashboard.guru',
            default => 'dashboard.siswa',
        };

        return redirect()
            ->route($route)
            ->with('status', 'Password berhasil diperbarui.');
    }
}
