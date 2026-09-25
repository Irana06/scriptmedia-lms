<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Cadangan data sekolah dalam satu berkas ZIP yang tidak bergantung pada
 * mysqldump (sering tidak tersedia di hosting cPanel): setiap tabel disimpan
 * sebagai JSON Lines, dibaca per potongan supaya hemat memori.
 */
class BackupService
{
    public const DIRECTORY = 'backups';

    /** Tabel sementara yang tidak perlu dicadangkan maupun dipulihkan. */
    private const SKIPPED_TABLES = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    public function create(bool $withFiles = false): string
    {
        $name = 'cadangan-'.now()->format('Y-m-d-His').($withFiles ? '-lengkap' : '').'.zip';
        $path = Storage::disk('local')->path(self::DIRECTORY.'/'.$name);
        File::ensureDirectoryExists(dirname($path));

        $workDir = storage_path('app/backup-tmp-'.Str::random(8));
        File::ensureDirectoryExists($workDir);

        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Berkas cadangan tidak bisa dibuat.');
            }

            $tables = $this->tables();
            foreach ($tables as $table) {
                $file = "{$workDir}/{$table}.jsonl";
                $handle = fopen($file, 'w');
                if ($handle === false) {
                    throw new RuntimeException("Tidak bisa menulis tabel {$table}.");
                }

                $query = DB::table($table);
                $order = Schema::hasColumn($table, 'id') ? 'id' : Schema::getColumnListing($table)[0];
                $query->orderBy($order)->chunk(500, function (Collection $rows) use ($handle): void {
                    foreach ($rows as $row) {
                        fwrite($handle, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
                    }
                });
                fclose($handle);
                $zip->addFile($file, "tables/{$table}.jsonl");
            }

            $zip->addFromString('manifest.json', json_encode([
                'app' => 'RuangKelas',
                'created_at' => now()->toIso8601String(),
                'database' => DB::getDriverName(),
                'tables' => $tables,
                'migrations' => DB::table('migrations')->orderBy('id')->pluck('migration')->all(),
                'with_files' => $withFiles,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            if ($withFiles) {
                foreach ([Storage::disk('local'), Storage::disk('public')] as $index => $disk) {
                    $prefix = $index === 0 ? 'files/local/' : 'files/public/';
                    foreach ($disk->allFiles() as $relative) {
                        if (str_starts_with($relative, self::DIRECTORY.'/') || str_starts_with($relative, 'backup-tmp-') || str_starts_with($relative, 'livewire-tmp/')) {
                            continue;
                        }
                        $zip->addFile($disk->path($relative), $prefix.$relative);
                    }
                }
            }

            $zip->close();
        } finally {
            File::deleteDirectory($workDir);
        }

        return $name;
    }

    /** @return list<array{name: string, size: int, created_at: Carbon}> */
    public function list(): array
    {
        $disk = Storage::disk('local');

        $backups = [];
        foreach ($disk->files(self::DIRECTORY) as $file) {
            if (str_ends_with($file, '.zip')) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => $disk->size($file),
                    'created_at' => Carbon::createFromTimestamp($disk->lastModified($file)),
                ];
            }
        }

        // Nama berkas berisi tanggal-jam, jadi urutan nama = urutan waktu dibuat.
        usort($backups, fn (array $a, array $b): int => strcmp($b['name'], $a['name']));

        return $backups;
    }

    /**
     * Hapus cadangan lama sejenis (hanya-data atau lengkap), sisakan $keep
     * terbaru; cadangan harian tidak menghapus cadangan lengkap mingguan.
     */
    public function prune(int $keep, bool $withFiles = false): int
    {
        $sameKind = array_values(array_filter(
            $this->list(),
            fn (array $backup): bool => str_ends_with($backup['name'], '-lengkap.zip') === $withFiles,
        ));
        $old = array_slice($sameKind, $keep);
        foreach ($old as $backup) {
            Storage::disk('local')->delete(self::DIRECTORY.'/'.$backup['name']);
        }

        return count($old);
    }

    public function pathFor(string $name): string
    {
        // Nama berkas saja; tidak boleh keluar dari folder cadangan.
        if (preg_match('/^cadangan-[0-9-]+(-lengkap)?\.zip$/', $name) !== 1 || ! Storage::disk('local')->exists(self::DIRECTORY.'/'.$name)) {
            throw new RuntimeException('Cadangan tidak ditemukan.');
        }

        return Storage::disk('local')->path(self::DIRECTORY.'/'.$name);
    }

    /**
     * Pulihkan data dari berkas cadangan. Menimpa seluruh isi tabel yang ada di
     * cadangan; hanya dipanggil dari perintah Artisan dengan konfirmasi.
     *
     * @return int jumlah tabel yang dipulihkan
     */
    public function restore(string $zipPath): int
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Berkas cadangan tidak bisa dibuka.');
        }

        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        if (! is_array($manifest) || ($manifest['app'] ?? null) !== 'RuangKelas' || ! is_array($manifest['tables'] ?? null)) {
            throw new RuntimeException('Ini bukan berkas cadangan RuangKelas.');
        }

        $current = DB::table('migrations')->pluck('migration')->all();
        $unknown = array_diff((array) ($manifest['migrations'] ?? []), $current);
        if ($unknown !== []) {
            throw new RuntimeException('Cadangan berasal dari versi aplikasi yang lebih baru. Perbarui aplikasi lalu jalankan migrate dulu.');
        }

        $restored = 0;
        Schema::disableForeignKeyConstraints();

        try {
            $tables = array_values(array_filter(
                $manifest['tables'],
                fn ($table): bool => is_string($table) && $table !== 'migrations' && ! in_array($table, self::SKIPPED_TABLES, true)
                    && Schema::hasTable($table) && $zip->locateName("tables/{$table}.jsonl") !== false,
            ));

            DB::transaction(function () use ($zip, $tables, &$restored): void {
                // Kosongkan semua tabel dulu, baru isi ulang: urutan tabel (induk/anak)
                // jadi tidak berpengaruh, dan cascade tidak menghapus data yang sudah dipulihkan.
                foreach ($tables as $table) {
                    DB::table($table)->delete();
                }

                foreach ($tables as $table) {
                    $stream = $zip->getStream("tables/{$table}.jsonl");
                    if ($stream === false) {
                        continue;
                    }

                    $columns = Schema::getColumnListing($table);
                    $batch = [];

                    while (($line = fgets($stream)) !== false) {
                        $row = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                        // Kolom yang sudah tidak ada di versi sekarang diabaikan.
                        $batch[] = array_intersect_key((array) $row, array_flip($columns));

                        if (count($batch) === 200) {
                            DB::table($table)->insert($batch);
                            $batch = [];
                        }
                    }
                    fclose($stream);

                    if ($batch !== []) {
                        DB::table($table)->insert($batch);
                    }
                    $restored++;
                }
            });
            $this->restoreFiles($zip);
        } finally {
            Schema::enableForeignKeyConstraints();
            $zip->close();
        }

        return $restored;
    }

    private function restoreFiles(ZipArchive $zip): void
    {
        $disks = ['files/local/' => Storage::disk('local'), 'files/public/' => Storage::disk('public')];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = (string) $zip->getNameIndex($index);

            foreach ($disks as $prefix => $disk) {
                $relative = substr($entry, strlen($prefix));
                // Tolak path yang mencoba keluar dari folder penyimpanan.
                if (! str_starts_with($entry, $prefix) || $relative === '' || str_contains($relative, '..')) {
                    continue;
                }

                $stream = $zip->getStream($entry);
                if ($stream !== false) {
                    $disk->writeStream($relative, $stream);
                    fclose($stream);
                }
            }
        }
    }

    /** @return list<string> */
    private function tables(): array
    {
        $tables = [];
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];

            if (! in_array($name, self::SKIPPED_TABLES, true)) {
                $tables[] = $name;
            }
        }

        return $tables;
    }
}
