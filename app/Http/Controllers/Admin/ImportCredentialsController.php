<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportCredentialsController extends Controller
{
    public function __invoke(DataImport $dataImport): BinaryFileResponse
    {
        $path = DB::transaction(function () use ($dataImport): string {
            $lockedImport = DataImport::query()->lockForUpdate()->findOrFail($dataImport->id);

            abort_if($lockedImport->downloaded_at !== null, 410, 'File kartu akun sudah pernah diunduh.');
            abort_unless($lockedImport->status === 'done' && $lockedImport->result_file_path, 404);
            abort_unless(Storage::disk('local')->exists($lockedImport->result_file_path), 410, 'File kartu akun sudah tidak tersedia.');

            $lockedImport->update(['downloaded_at' => now()]);

            return $lockedImport->result_file_path;
        });

        return response()
            ->download(Storage::disk('local')->path($path), "kartu-akun-{$dataImport->type}-{$dataImport->id}.xlsx")
            ->deleteFileAfterSend(true);
    }
}
