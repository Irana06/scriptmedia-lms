# Panduan pengembangan

## Kebutuhan

- PHP 8.4 (minimal 8.3) dengan ekstensi: `pdo_sqlite`, `pdo_mysql`, `gd`, `zip`, `intl`, `mbstring`,
  `xml`, `bcmath`, `fileinfo`
- Composer 2, Node.js 20+ (dipakai 24), npm
- Disarankan [Laravel Herd](https://herd.laravel.com) di Windows/macOS: situs otomatis tersedia di
  `http://lms-engine.test`. Tanpa Herd, pakai `composer dev` (http://localhost:8000).

## Setup pertama

```bash
git clone <url-repo> lms-engine && cd lms-engine
composer setup                                  # composer install, .env, APP_KEY, migrate (SQLite), npm install, build
php artisan db:seed --class=DemoSeeder          # data demo lengkap (aman diulang)
```

Isi `.env` lokal yang biasanya diubah:

```dotenv
APP_URL=http://lms-engine.test     # atau http://localhost:8000
APP_DEMO_MODE=true                 # tombol masuk demo di halaman depan
DB_CONNECTION=sqlite               # bawaan; database/database.sqlite
```

Akun demo ada di [serah-terima.md](serah-terima.md#akun-dan-kredensial).

## Menjalankan

```bash
composer dev        # php artisan serve + queue:listen + vite (hot reload)
```

Dengan Herd cukup `npm run dev` untuk hot reload CSS/JS.

## Test dan kualitas kode

```bash
composer test                                    # wajib hijau sebelum push
php artisan test --filter=GuardianModuleTest     # satu berkas/kelas test
composer lint                                    # rapikan format (Pint)
```

`composer test` = `config:clear` → Pint (cek format) → PHPStan level 7 → Pest. Test memakai SQLite
in-memory dan **tidak** menyentuh database lokal. Saat ini ±200 test; dua di antaranya sengaja
dilewati (fitur Fortify yang dimatikan: registrasi publik dan reset password via email).

Aturan PHPStan di proyek ini: perbaiki penyebabnya, **jangan** menambah `@phpstan-ignore`, baseline,
atau `@var` untuk membungkam error. `env()` hanya boleh dipanggil di folder `config/`.

## Konvensi

- Bahasa antarmuka dan komentar kode: **Bahasa Indonesia**. Nama kelas/metode/kolom: bahasa Inggris
  (PascalCase kelas, camelCase metode, snake_case kolom, kebab-case berkas Blade).
- Komentar hanya untuk "kenapa", bukan "apa".
- Halaman interaktif = komponen Livewire berbasis kelas di `app/Livewire/<Peran>/`, view di
  `resources/views/livewire/<peran>/`. Logika besar dipindah ke `app/Services/`.
- Pakai komponen tema (`x-theme.card`, `x-theme.button`, `x-theme.badge`, `x-theme.section-header`)
  dan token warna yang ada; jangan menambah warna baru.
- Data siswa: **selalu** lewat `StudentAccess` / `ResolvesStudent`, bukan `Auth::id()`
  (lihat [arsitektur.md](arsitektur.md#studentaccess-penting)).
- Setiap fitur baru disertai feature test (`tests/Feature/<Topik>Test.php`). Untuk alur yang
  menampilkan halaman, tambahkan rute ke `tests/Feature/PageSmokeTest.php`.
- Notifikasi tidak boleh di-queue (`ShouldQueue`) karena server sekolah umumnya tanpa queue worker.

## Commit dan rilis

- Pesan commit gaya Conventional Commit, isi Bahasa Indonesia: `feat: ...`, `fix: ...`, `docs: ...`,
  `build: ...`, `test: ...`. Badan pesan menjelaskan **alasan**. Lihat `git log`.
- **Tanpa** trailer `Co-Authored-By` asisten AI.
- Branch utama: `master`. Server mengambil kode dengan `git pull` dari `master`.

### Aset frontend (penting)

`public/build/` ikut di-commit karena server cPanel tidak menjalankan Node. Setiap kali mengubah
Blade/CSS/JS yang memengaruhi kelas Tailwind:

```bash
npm run build:subfolder     # untuk instalasi cPanel di /lms — versi ini yang di-commit
git add public/build
```

Untuk instalasi di root domain (VPS, home server), build ulang di server dengan `npm run build`
(lihat [deploy-vps.md](deploy-vps.md)). Jika subfolder bukan `/lms`, ubah `--base` di
`package.json`.

## Menambah fitur — contoh alur

1. Migrasi baru di `database/migrations/` (nama bertanggal, selalu dengan `down()`).
2. Model + relasi; docblock `@property` multi-baris (PHPStan membaca dari sini).
3. Komponen Livewire/controller + route dengan middleware peran yang tepat.
4. Otorisasi di dalam komponen (scope data ke pengguna), bukan hanya middleware.
5. View dengan komponen tema; tautan sidebar di `components/layouts/guru-admin.blade.php` bila perlu.
6. Test fitur + tambah ke smoke test; `composer test`.
7. `npm run build:subfolder` bila ada kelas CSS baru, commit, push, deploy.

## Hal yang perlu diketahui

- `SchoolProfile::current()` memakai `once()` (satu query per request); baris id=1 selalu ada.
- Locale aplikasi `id`: `translatedFormat()` dan `diffForHumans()` sudah berbahasa Indonesia.
- Presensi disimpan dengan objek tanggal (`Carbon::parse(...)->startOfDay()`), bukan teks `Y-m-d`:
  di SQLite pencarian teks tidak cocok dengan kolom `date` yang di-cast.
- Pada cPanel subfolder, **jangan** `route:cache`/`optimize` (lihat DEPLOY.md).
