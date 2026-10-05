# Server: perintah, deploy, perawatan

## cPanel (produksi perusahaan)

Buka **Terminal** di cPanel. **Setiap kali membuka terminal baru**, aktifkan dulu lingkungannya:

```bash
source ~/school/lms-engine/scripts/activate.sh
cd ~/school/lms-engine
```

`activate.sh` mengisi dua variabel, karena `php` bawaan server ini bukan PHP 8.4:

| Variabel | Isi | Dipakai untuk |
|---|---|---|
| `$PHP84` | `/opt/alt/php84/usr/bin/php` | **semua** perintah artisan |
| `$COMPOSER84` | `$PHP84 /usr/local/bin/composer` | **semua** perintah composer |

```bash
$PHP84 artisan migrate --force
$PHP84 artisan sekolah:admin-password admin@sekolah.sch.id
$COMPOSER84 install --no-dev --optimize-autoloader
```

Tanpa `source activate.sh`, kedua variabel kosong dan perintah di atas gagal. (Dulu dipakai berkas
yang sama di folder project e-commerce `scriptm1/scriptmedia-ecommerxe/activate.sh`; sekarang LMS punya
salinannya sendiri di `scripts/activate.sh`.)

**Deploy versi baru** (di PC: `git push origin master` dulu):

```bash
bash ~/school/lms-engine/scripts/server-deploy.sh
```

Skrip ini mengaktifkan `activate.sh` sendiri, menarik kode, memasang dependensi dengan `$COMPOSER84`
bila `composer.lock` berubah, menjalankan migrasi, dan membangun cache. Skrip berhenti sendiri bila
PHP bukan 8.3+, `DB_CONNECTION` bukan `mysql`, atau ada berkas yang diubah langsung di server. Rinciannya, termasuk
larangan `route:cache`/`optimize` di subfolder `/lms`, ada di [../DEPLOY.md](../DEPLOY.md).

**Cron cadangan otomatis** (cPanel → *Cron Jobs* → *Once Per Minute*). Cron tidak membaca
`activate.sh` dengan sendirinya, jadi diaktifkan di perintah itu:

```bash
source ~/school/lms-engine/scripts/activate.sh && cd ~/school/lms-engine && $PHP84 artisan schedule:run >> /dev/null 2>&1
```

**Instalasi sekolah baru**: setelah migrasi pertama, jalankan `$PHP84 artisan sekolah:setup`.

## VPS / server sendiri (Nginx)

Langkah singkat, misalnya domain `lms.sekolah.sch.id` dan folder `/var/www/lms-engine`:

1. Paket: `nginx php8.4-fpm` + ekstensi `mysql sqlite3 gd zip intl mbstring xml bcmath curl`,
   Composer, Node 20+.
2. Folder milik user deploy dengan grup `www-data`: `chown deploy:www-data` dan `chmod 2775`.
3. `composer install --no-dev -o`, `npm ci && npm run build` (**bukan** `build:subfolder`), lalu
   `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `DB_*`.
   Isi juga `TRUSTED_PROXIES=127.0.0.1` bila di belakang Cloudflare Tunnel/proxy.
4. `php artisan key:generate && php artisan migrate --force && php artisan storage:link &&
   php artisan sekolah:setup`, kemudian `php artisan config:cache route:cache view:cache` (route cache
   aman di root domain).
5. Nginx: `root .../public`, `try_files $uri $uri/ /index.php?$query_string`, `client_max_body_size 64M`.
   Batas upload PHP dinaikkan lewat pool php-fpm tersendiri (`php_admin_value[upload_max_filesize]
   = 64M`), bukan `PHP_VALUE`.
6. Cloudflare Tunnel: tambahkan `hostname` di `/etc/cloudflared/config.yml` sebelum aturan 404, lalu
   `cloudflared tunnel route dns <id> <domain>`.
7. Cron: `* * * * * cd /var/www/lms-engine && umask 002 && php artisan schedule:run >> /dev/null 2>&1`.
8. Izin: `chmod -R g+w storage bootstrap/cache database`.

## Perawatan

| Kapan | Apa |
|---|---|
| Otomatis tiap malam & Minggu | Cadangan data / data + berkas (butuh cron) |
| Mingguan | Unduh satu cadangan dari menu **Cadangan Data**, simpan di luar server |
| Akhir tahun ajaran | Buat tahun ajaran baru → menu **Kenaikan Kelas** → impor siswa baru → jadwal |

```bash
artisan sekolah:backup [--dengan-berkas]          # buat cadangan
artisan sekolah:restore cadangan-....zip          # pulihkan (MENIMPA semua data)
artisan sekolah:admin-password [email]            # admin lupa password
artisan sekolah:setup                             # tambah admin / instalasi baru
```

(Di cPanel awali dengan `$PHP84` dan composer dengan `$COMPOSER84`; di VPS cukup `php`/`composer`.)

## Pemecahan masalah

| Gejala | Solusi |
|---|---|
| `405 Method Not Allowed` di cPanel `/lms` | `$PHP84 artisan route:clear`; jangan `route:cache`/`optimize` di subfolder |
| Font/ikon 404 di cPanel | Aset belum di-build untuk subfolder: `npm run build:subfolder`, commit, deploy |
| Tampilan rusak, *mixed content* | Isi `TRUSTED_PROXIES=127.0.0.1`, lalu `artisan config:cache` |
| Unggah gagal / 413 | Naikkan `client_max_body_size` (Nginx) dan `upload_max_filesize`/`post_max_size` (PHP) |
| *Permission denied* / *readonly database* | `chmod -R g+w storage bootstrap/cache database` |
| Logo tidak tampil di rapor PDF | Pasang ekstensi PHP `gd` |
| Perubahan `.env` tidak berpengaruh | `artisan config:cache` |
| Siswa dulu diimpor dengan NIS, kini punya NISN | Impor ulang dengan NISN **dan** NIS terisi; akun lama diperbarui |

Log aplikasi ada di `storage/logs/laravel.log`.
