<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupSchool extends Command
{
    protected $signature = 'sekolah:backup
        {--dengan-berkas : Sertakan berkas unggahan (materi, tugas, logo); bisa besar}
        {--simpan=14 : Jumlah cadangan terbaru yang disimpan, sisanya dihapus}';

    protected $description = 'Buat cadangan data sekolah (dijadwalkan harian)';

    public function handle(BackupService $backups): int
    {
        $withFiles = (bool) $this->option('dengan-berkas');
        $name = $backups->create($withFiles);
        $pruned = $backups->prune(max(1, (int) $this->option('simpan')), $withFiles);

        $this->info("Cadangan dibuat: storage/app/private/backups/{$name}");
        if ($pruned > 0) {
            $this->line("{$pruned} cadangan lama dihapus.");
        }

        return self::SUCCESS;
    }
}
