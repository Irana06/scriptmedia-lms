<?php

namespace App\Http\Controllers;

use App\Models\SchoolProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Manifest PWA dibuat per sekolah: nama aplikasi di layar utama HP adalah nama
 * sekolah, dan ikonnya logo sekolah bila sudah diunggah.
 */
class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $school = SchoolProfile::current();
        $icons = [
            ['src' => asset('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ];

        if ($school->logo_path !== null && Storage::disk('public')->exists($school->logo_path)) {
            $size = getimagesize(Storage::disk('public')->path($school->logo_path));

            if ($size !== false) {
                array_unshift($icons, [
                    'src' => (string) $school->logoUrl(),
                    'sizes' => "{$size[0]}x{$size[1]}",
                    'type' => $size['mime'],
                    'purpose' => 'any',
                ]);
            }
        }

        return response()->json([
            'name' => $school->brandName(),
            'short_name' => Str::limit($school->brandName(), 12, ''),
            'description' => 'Portal belajar '.$school->brandName(),
            'start_url' => url('/dashboard'),
            'scope' => url('/').'/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#F4FAFA',
            'theme_color' => '#0B2545',
            'lang' => 'id',
            'icons' => $icons,
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }
}
