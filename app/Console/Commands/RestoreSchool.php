<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class RestoreSchool extends Command
{
    protected $signature = 'sekolah:restore
        {berkas : Nama berkas di storage/app/private/backups, atau path lengkap ke berkas ZIP}
        {--force : Lewati konfirmasi}';

    protected $description = 'Pulihkan data sekolah dari berkas cadangan (menimpa data sekarang)';

    public function handle(BackupService $backups): int
    {
        $file = (string) $this->argument('berkas');
        $path = is_file($file) ? $file : $backups->pathFor($file);

        $this->warn('Seluruh data sekolah saat ini akan DIGANTI dengan isi cadangan.');
        $this->line('Saran: buat cadangan terbaru dulu dengan `php artisan sekolah:backup`.');

        if (! $this->option('force') && ! confirm('Lanjutkan pemulihan?', default: false)) {
            $this->line('Dibatalkan.');

            return self::FAILURE;
        }

        $tables = $backups->restore($path);
        $this->call('optimize:clear');
        $this->info("Pemulihan selesai: {$tables} tabel dipulihkan.");

        return self::SUCCESS;
    }
}
