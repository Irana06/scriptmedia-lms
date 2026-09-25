<?php

namespace App\Livewire\Admin;

use App\Services\BackupService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('components.layouts.guru-admin')]
#[Title('Cadangan Data')]
class Backups extends Component
{
    public function createBackup(BackupService $backups): void
    {
        // Dari browser hanya data (cepat); cadangan lengkap berkas dibuat terjadwal
        // lewat cron karena bisa berukuran besar dan melewati batas waktu request.
        $name = $backups->create();
        $backups->prune(14);
        session()->flash('backup_status', "Cadangan {$name} berhasil dibuat.");
    }

    public function deleteBackup(string $name, BackupService $backups): void
    {
        try {
            $path = $backups->pathFor($name);
        } catch (RuntimeException) {
            abort(404);
        }

        Storage::disk('local')->delete(BackupService::DIRECTORY.'/'.basename($path));
        session()->flash('backup_status', "Cadangan {$name} dihapus.");
    }

    public function render(BackupService $backups): View
    {
        return view('livewire.admin.backups', ['backups' => $backups->list()]);
    }
}
