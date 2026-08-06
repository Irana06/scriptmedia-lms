<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class RoleLoginResponse implements LoginResponseContract
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        /** @var Request $request */
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        $route = match (true) {
            $user?->hasRole('admin') => 'dashboard.admin',
            $user?->hasRole('guru') => 'dashboard.guru',
            $user?->must_change_password === true => 'siswa.password.required',
            default => 'dashboard.siswa',
        };

        return redirect()->intended(route($route, absolute: false));
    }
}
