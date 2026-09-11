# Deploy ke cPanel (subfolder)

Catatan ini berasal dari pemasangan nyata di `school.scriptmedia.net/lms`, bukan dari asumsi.

## Bentuk pemasangan

Document root subdomain menunjuk ke `~/school`. Aplikasi berada di `~/school/lms-engine`,
dan `~/school/lms` adalah **symlink** ke `~/school/lms-engine/public`:

```bash
ln -s ~/school/lms-engine/public ~/school/lms
```

Symlink ini penting. Tanpa penyejajaran itu, perhitungan base path Laravel meleset.

## Jangan pernah cache route

`php artisan route:cache` — dan karena itu juga `php artisan optimize` — **merusak seluruh
aplikasi** pada pemasangan subfolder. Halaman depan akan menjawab `405 Method Not Allowed`
dengan header `Allow` yang berisi verb yang tidak masuk akal.

Sebabnya ada di `CompiledRouteCollection::requestWithoutTrailingSlash()`: sebelum
mencocokkan route, Laravel memangkas garis miring di ujung `REQUEST_URI`. Untuk `/lms/`
hasilnya `/lms`, yang tidak lagi berawalan `/lms/` seperti `SCRIPT_NAME`, sehingga deteksi
base path gagal dan Laravel mencari route bernama `lms`. Ini hanya terjadi saat route
di-cache — tanpa cache, pencocokan memakai request asli dan semuanya normal.

Cache lain aman dan tetap dianjurkan:

```bash
php artisan config:cache && php artisan view:cache && php artisan event:cache
```

Kalau suatu saat aplikasi dipindah ke document root tersendiri, larangan ini gugur.

## Build aset harus tahu subfoldernya

URL font dan gambar tertanam di dalam CSS **saat build**, bukan saat runtime, sehingga
`ASSET_URL` di `.env` tidak cukup — ia hanya mempengaruhi tag `<link>` dan `<script>`
yang dibuat Blade. Build dengan:

```bash
npm run build:subfolder
```

Script itu menjalankan `vite build --base=/lms/build/` dan bekerja di Windows maupun Linux.
Kalau subfoldernya bukan `/lms`, sesuaikan flag `--base` di `package.json`.

Melewatkan langkah ini menghasilkan font 404 dari `/build/assets/...` — perhatikan tidak
adanya prefiks `/lms`.

## Cara cepat

Setelah kode baru ada di server, satu perintah menjalankan semua langkah di bawah dengan
urutan yang benar:

```bash
PHP84=$PHP84 bash ~/school/lms-engine/scripts/server-deploy.sh
```

Tambahkan `--demo` untuk sekalian menjalankan `DemoSeeder`. Skrip ini berhenti sebelum
migrasi bila `DB_CONNECTION` bukan `mysql`, menghapus cache route yang tertinggal,
memperingatkan bila aset tidak di-build untuk `/lms`, dan memeriksa halaman depan menjawab
200 di akhir.

`PHP84=$PHP84` di depan perintah memastikan skrip memakai PHP 8.4 walau variabel itu tidak
di-export di shell.

## Urutan rilis

```bash
cd ~/school/lms-engine
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan view:cache && php artisan event:cache
php artisan storage:link
```

Jangan unggah `bootstrap/cache/` — biarkan digenerate di server.

## Data demo

```bash
php artisan db:seed --class=DemoSeeder --force
```

`DemoSeeder` memakai `updateOrCreate` di seluruh bagiannya, jadi aman dijalankan berulang.
Ia menyiapkan dua kelas, empat guru, dua puluh siswa, riwayat presensi dua pekan, dan satu
riwayat impor yang sudah selesai.

Semua akun demo memakai password `Demo12345!`. Salah satu guru, Pak Iwan Demo, sengaja
dibuat tanpa email dan masuk memakai username `iwan.demo` — untuk memperlihatkan alur guru
sekolah swasta yang tidak punya email.

Akun orang tua demo: `ortu.demo@example.com` (Ibu Sri Demo) sudah tertaut ke Budi Santoso,
dan `joko.demo@example.com` (Pak Joko Demo) punya permintaan tautan ke Siti Aisyah yang
**sengaja dibiarkan menunggu**, supaya alur persetujuan di menu admin *Orang Tua* bisa
didemokan. Menjalankan ulang seeder tidak mengembalikan permintaan yang sudah diputuskan.

## Sebelum deploy

- **Cek `DB_CONNECTION` di `.env` server** (`grep DB_CONNECTION ~/school/lms-engine/.env`).
  Server memakai MySQL.
- Migrasi `2026_09_11_000000_create_guardian_student_table` menambah tabel tautan orang tua
  dan kolom `phone` di `users`. Wajib dijalankan sebelum halaman orang tua dibuka.

Tombol masuk sekali klik di halaman depan muncul hanya bila `APP_DEMO_MODE=true` di `.env`.
**Matikan sebelum sekolah memasukkan data sungguhan** — tombol itu memberi akses admin penuh
kepada siapa pun yang membuka halaman depan.
