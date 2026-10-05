# RuangKelas — LMS sekolah (ScriptMedia)

RuangKelas adalah *learning management system* untuk sekolah tatap muka (SMP/SMA/SMK/madrasah),
dijual sebagai template: **satu instalasi = satu sekolah** (database terpisah per klien). Target
utama sekolah swasta yang datanya berasal dari Dapodik/EMIS, tetapi juga cocok untuk sekolah negeri.

Empat peran:

| Peran | Bisa apa |
|---|---|
| **Admin** | Profil & logo sekolah, struktur akademik (tahun ajaran, kelas, mapel, guru pengampu, jadwal, bobot nilai, KKM), impor akun dari Excel, persetujuan akun orang tua, rapor PDF & leger Excel, pantauan siswa berisiko, kenaikan kelas, riwayat nilai, cadangan data |
| **Guru** | Materi, tugas, kuis (acak per siswa, soal bergambar, impor soal Excel), penilaian, nilai akhir + deskripsi capaian, presensi, pengumuman; wali kelas bisa unduh rapor kelasnya |
| **Siswa** | Belajar, kumpulkan tugas, kerjakan kuis, lihat nilai, presensi, pengumuman |
| **Orang tua** | **Hanya melihat** data anak yang tautannya disetujui admin: jadwal, nilai, presensi, pengumuman |

Fitur pendukung: notifikasi lonceng, halaman pengumuman, PWA (bisa dipasang di HP), wizard
instalasi sekolah baru, cadangan & pemulihan otomatis.

## Teknologi

Laravel 13 · PHP 8.4 (minimal 8.3) · Livewire 4 (komponen berbasis kelas) · Flux · Tailwind CSS 4 ·
Vite · Pest 5 · PHPStan level 7 (Larastan) · Pint · DomPDF (rapor) · Laravel Excel (impor/ekspor) ·
Spatie Permission (peran) · Fortify (autentikasi). Database: MySQL di produksi, SQLite untuk
pengembangan dan test.

## Mulai cepat (pengembangan lokal)

```bash
composer setup                 # install, .env, key, migrate, build aset
php artisan db:seed --class=DemoSeeder
composer dev                   # server + queue + Vite
```

Lalu set `APP_DEMO_MODE=true` di `.env` untuk tombol masuk demo di halaman depan. Panduan
lengkap: [docs/pengembangan.md](docs/pengembangan.md).

## Dokumentasi

| Dokumen | Isi |
|---|---|
| [docs/serah-terima.md](docs/serah-terima.md) | **Mulai dari sini.** Status proyek, akun & kredensial, server, pekerjaan tertunda |
| [docs/arsitektur.md](docs/arsitektur.md) | Struktur kode, model data, aturan otorisasi, alur penting |
| [docs/pengembangan.md](docs/pengembangan.md) | Setup lokal, konvensi kode, test, cara menambah fitur |
| [DEPLOY.md](DEPLOY.md) | Deploy ke cPanel (subfolder `/lms`, git pull + `scripts/server-deploy.sh`) |
| [docs/deploy-vps.md](docs/deploy-vps.md) | Deploy ke VPS/server sendiri (Nginx + php-fpm, opsional Cloudflare Tunnel) |
| [docs/operasional.md](docs/operasional.md) | Perawatan rutin: cadangan, pemulihan, pembaruan, akun terkunci, pergantian tahun ajaran, pemecahan masalah |
| [AGENTS.md](AGENTS.md) | Aturan singkat untuk asisten AI/kontributor |

Panduan untuk pengguna akhir (non-teknis): *Manual Admin Sekolah* dan *Kebijakan Privasi Data*
(tautan di [docs/serah-terima.md](docs/serah-terima.md)).

## Perintah yang sering dipakai

```bash
composer test                              # Pint + PHPStan + seluruh test (wajib hijau sebelum push)
php artisan sekolah:setup                  # instalasi sekolah baru: profil, admin pertama, tahun ajaran
php artisan sekolah:admin-password [email] # buat password baru untuk admin yang lupa password
php artisan sekolah:backup [--dengan-berkas]
php artisan sekolah:restore <berkas.zip>
npm run build                              # aset untuk instalasi di root domain
npm run build:subfolder                    # aset untuk instalasi di /lms (cPanel) — yang di-commit
```
