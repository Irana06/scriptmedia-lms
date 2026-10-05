# RuangKelas — LMS sekolah (ScriptMedia)

LMS untuk sekolah tatap muka (SMP/SMA/SMK/madrasah), dijual sebagai template: **satu instalasi =
satu sekolah**. Peran: **admin**, **guru**, **siswa**, **orang tua** (hanya melihat data anak yang
tautannya disetujui admin).

Laravel 13 · PHP 8.4 · Livewire 4 · Flux · Tailwind 4 · Pest · MySQL (produksi) / SQLite (lokal & test).

## Dokumen

| Dokumen | Isi |
|---|---|
| [docs/serah-terima.md](docs/serah-terima.md) | **Mulai di sini**: status, akun & kredensial, server, tugas tertunda |
| [docs/teknis.md](docs/teknis.md) | Arsitektur dan cara mengembangkan |
| [docs/server.md](docs/server.md) | Perintah di server, deploy, perawatan, pemecahan masalah |
| [DEPLOY.md](DEPLOY.md) | Catatan rinci deploy cPanel (subfolder `/lms`) |

## Mulai cepat (lokal)

```bash
composer setup                            # install, .env, key, migrate, build
php artisan db:seed --class=DemoSeeder    # data demo; password semua akun: Demo12345!
composer dev                              # atau buka http://lms-engine.test dengan Herd
composer test                             # Pint + PHPStan + test, wajib hijau sebelum push
```
