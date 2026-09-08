<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public function __invoke(Request $request, string $role): RedirectResponse
    {
        abort_unless(config('app.demo_mode'), 404);

        $accounts = [
            'admin' => ['email' => 'admin.demo@example.com', 'role' => 'admin'],
            'guru' => ['email' => 'guru.demo@example.com', 'role' => 'guru'],
            'siswa' => ['email' => '0099000001@students.invalid', 'role' => 'siswa'],
        ];
        abort_unless(array_key_exists($role, $accounts), 404);

        $account = $accounts[$role];
        $user = User::query()->where('email', $account['email'])->firstOrFail();
        abort_unless($user->hasRole($account['role']), 404);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
