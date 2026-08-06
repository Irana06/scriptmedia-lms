<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs([
            'siswa.password.required',
            'siswa.password.update',
            'logout',
        ])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Password wajib diganti sebelum melanjutkan.',
                ], Response::HTTP_LOCKED);
            }

            return redirect()->route('siswa.password.required');
        }

        return $next($request);
    }
}
