<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupDownloadController extends Controller
{
    public function __invoke(string $name, BackupService $backups): BinaryFileResponse
    {
        try {
            $path = $backups->pathFor($name);
        } catch (RuntimeException) {
            abort(404);
        }

        return response()->download($path, $name, ['Cache-Control' => 'no-store']);
    }
}
