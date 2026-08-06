<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $route = match (true) {
            $user->hasRole('admin') => 'dashboard.admin',
            $user->hasRole('guru') => 'dashboard.guru',
            $user->hasRole('siswa') => 'dashboard.siswa',
            default => abort(403, 'Akun belum memiliki role.'),
        };

        return redirect()->route($route);
    }
}
