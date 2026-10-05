# Serah terima proyek RuangKelas

Dokumen ini untuk pengembang/tim yang melanjutkan proyek. Baca ini dulu, lalu
[arsitektur.md](arsitektur.md) dan [pengembangan.md](pengembangan.md).

Per Oktober 2026: aplikasi **berfungsi penuh untuk empat peran** dan sudah pernah berjalan di
cPanel perusahaan. `composer test` hijau (±200 test, PHPStan level 7 bersih). Pekerjaan yang belum
selesai ada di bagian [Pekerjaan tertunda](#pekerjaan-tertunda).

## Status fitur

| Area | Status |
|---|---|
| Autentikasi 4 peran, wajib ganti password, 2FA & passkey (Fortify) | Selesai |
| Struktur akademik: tahun ajaran, semester, kelas, mapel + KKM, guru pengampu, jadwal anti-bentrok | Selesai |
| Impor akun dari Excel Dapodik/EMIS (siswa tanpa NISN, guru tanpa email/NIP) + kartu akun | Selesai |
| Materi (multi-berkas, tautan), tugas + penilaian, kuis (PG/esai, acak, gambar, impor Excel) | Selesai |
| Nilai akhir berbobot (tugas/kuis/UTS/UAS), deskripsi capaian gaya Kurikulum Merdeka, presensi | Selesai |
| Rapor PDF berkop, leger nilai & rekap presensi Excel (admin + wali kelas) | Selesai |
| Modul orang tua (daftar sendiri, tautan disetujui admin, hanya baca) | Selesai |
| Pengumuman (halaman sendiri, status baca), kalender, notifikasi lonceng | Selesai |
| Pantauan siswa berisiko, kenaikan kelas massal, riwayat perubahan nilai | Selesai |
| Profil & logo sekolah (branding), PWA, wizard `sekolah:setup` | Selesai |
| Cadangan otomatis + pemulihan, menu Cadangan Data | Selesai |
| Notifikasi WhatsApp/email | **Ditunda** (keputusan pemilik proyek) |

## Repositori

- Saat ini di akun GitHub pribadi pengembang: `https://github.com/Irana06/scriptmedia-lms` (branch
  `master`).
- **Untuk dipindahkan ke akun/organisasi perusahaan**: GitHub → repo → *Settings* → *Danger Zone* →
  *Transfer ownership*. Riwayat commit, issue, dan URL lama (dialihkan otomatis) ikut pindah.
- Setelah dipindah, perbarui remote di server cPanel:
  `git remote set-url origin <url-baru>` di `~/school/lms-engine`. Bila repo privat, server butuh
  *deploy key* (read-only) di repo baru.

## Lingkungan

| Lingkungan | Alamat | Detail |
|---|---|---|
| Produksi perusahaan (cPanel) | `school.scriptmedia.net/lms` | Folder `~/school/lms-engine`, subfolder `/lms` (symlink `~/school/lms` → `public`), MySQL, PHP 8.4 via `$PHP84`, deploy `scripts/server-deploy.sh`. Lihat [DEPLOY.md](../DEPLOY.md). Akses terminal lewat login cPanel perusahaan |
| Demo publik | `https://lms.yushika.my.id` | Server pribadi pengembang (Nginx + Cloudflare Tunnel, SQLite, data demo). **Bukan aset perusahaan** dan bisa dimatikan kapan saja setelah kontrak berakhir. Buat demo sendiri dengan [deploy-vps.md](deploy-vps.md) + `DemoSeeder` |
| Lokal | `http://lms-engine.test` (Herd) | SQLite, lihat [pengembangan.md](pengembangan.md) |

## Akun dan kredensial

> Aturan: **jangan pernah commit nilai rahasia** (`.env`, password produksi, private key, token) ke
> repositori. Kredensial produksi diserahkan lewat jalur aman (password manager atau dokumen
> terpisah), lalu **diganti oleh penerima** setelah serah terima.

### Akun demo (aman dicantumkan, data contoh)

Dibuat oleh `php artisan db:seed --class=DemoSeeder`. Semua password: **`Demo12345!`**.
Dengan `APP_DEMO_MODE=true`, halaman depan juga punya tombol masuk sekali klik per peran.

| Peran | Halaman masuk | Login | Nama |
|---|---|---|---|
| Admin | `/login` | `admin.demo@example.com` | Admin Demo |
| Guru (wali 7A Demo) | `/login` | `guru.demo@example.com` | Ibu Ratna Demo |
| Guru (wali 7B Demo) | `/login` | `hendra.demo@example.com` | Pak Hendra Demo |
| Guru | `/login` | `yuli.demo@example.com` | Bu Yuli Demo |
| Guru tanpa email | `/login` | username `iwan.demo` | Pak Iwan Demo |
| Siswa | `/siswa/login` | NISN `0099000001` s.d. `0099000020` | Budi Santoso, Siti Aisyah, ... |
| Orang tua (tertaut ke Budi) | `/ortu/masuk` | `ortu.demo@example.com` | Ibu Sri Demo |
| Orang tua (permintaan menunggu) | `/ortu/masuk` | `joko.demo@example.com` | Pak Joko Demo |

### Akun bawaan pengembangan

`php artisan db:seed` (DatabaseSeeder → AdminSeeder) membuat admin dari `.env`:
`LMS_ADMIN_EMAIL` / `LMS_ADMIN_PASSWORD` (bawaan `admin@example.com` / `change-this-password`).
Seeder ini **menolak berjalan di produksi** bila password masih bawaan. Untuk produksi pakai
`php artisan sekolah:setup`.

### Inventaris kredensial

| Kredensial | Disimpan di | Pemegang | Cara mengganti / memulihkan |
|---|---|---|---|
| Admin aplikasi tiap sekolah | Database instalasi sekolah (hash) | Admin sekolah | Admin lain, atau di server: `php artisan sekolah:admin-password <email>` |
| Guru, siswa, orang tua | Database (hash) | Masing-masing | Reset oleh admin (guru juga bisa mereset siswa) |
| `APP_KEY` | `.env` di tiap server | Pengelola server | **Jangan diganti** pada instalasi berjalan: sesi, cookie, dan rahasia 2FA terenkripsi dengannya. Simpan salinannya bersama cadangan |
| Database produksi (MySQL) | `.env` server (`DB_*`) + cPanel → MySQL Databases | Perusahaan | Ubah password di cPanel, perbarui `.env`, `php artisan config:cache` |
| Login cPanel / SSH server perusahaan | Pengelola hosting perusahaan | Perusahaan (diberikan atasan) | Lewat penyedia hosting |
| Repositori GitHub | Akun pengembang (sampai dipindah) | Pengembang → perusahaan | Transfer ownership (lihat atas); cabut akses pengembang setelahnya |
| Server demo `lms.yushika.my.id`, Cloudflare, SSH key-nya | Milik pribadi pengembang | Pengembang | Tidak diserahkan |

## Dokumen untuk pengguna sekolah

- **Manual Admin Sekolah** dan **Kebijakan Privasi Data** (templat UU PDP) dibuat sebagai dokumen
  Claude di akun pengembang. Ekspor atau bagikan ke perusahaan sebelum kontrak berakhir. Isinya
  perlu diperbarui bila fitur berubah.
- Kebijakan privasi masih punya tempat kosong untuk **kontak penanggung jawab data** sekolah.

## Pekerjaan tertunda

Prioritas tinggi:

1. **Uji tampilan manual di browser** untuk fitur terbaru (Profil Sekolah, Pantauan Siswa, Kenaikan
   Kelas, Riwayat Nilai, Cadangan Data, lonceng & halaman pengumuman, kuis acak/bergambar). Semua
   halaman lolos test render, tetapi belum diperiksa visual satu per satu, terutama di HP.
2. **Konfirmasi format rapor Kurikulum Merdeka** dengan klien. Saat ini: KKM per mapel (bawaan 75),
   deskripsi capaian otomatis yang bisa disunting guru, predikat A–E tetap ada
   (`App\Support\CompetencyDescription`, `resources/views/pdf/report-card.blade.php`).
3. **Deploy terbaru ke cPanel perusahaan** begitu akses kembali (`server-deploy.sh`), lalu pasang
   cron `schedule:run` di cPanel (lihat DEPLOY.md) agar cadangan otomatis berjalan.

Ditunda / ide lanjutan:

- Notifikasi WhatsApp atau email ke orang tua (`App\Support\SchoolNotifier` sudah jadi titik kirim
  tunggal; tinggal menambah channel). Email keluar belum dikonfigurasi (`MAIL_MAILER=log`).
- Bobot nilai masih satu untuk seluruh sekolah, bukan per tahun ajaran. Mengubah bobot lalu
  "Hitung otomatis" di kelas tahun lalu akan menghasilkan angka berbeda dari rapor yang sudah dicetak
  (nilai tersimpan tidak berubah sendiri).
- Situs demo publik memberi akses admin ke siapa pun; pertimbangkan reset data demo terjadwal.
- Presensi baru default "Hadir" untuk setiap siswa (disengaja, mengikuti kebiasaan buku presensi).

## Keputusan desain yang perlu dipertahankan

- Orang tua **hanya membaca**; tidak pernah bertindak atas nama anak (`StudentAccess`).
- Tautan orang tua ke anak wajib disetujui admin, karena jadwal anak sama dengan lokasi anak.
- Satu instalasi per sekolah (tanpa multi-tenant). Data antarsekolah tidak pernah bercampur.
- Notifikasi dan proses lain tidak memakai queue (hosting sekolah umumnya tanpa worker).
- Tidak ada `mysqldump` pada cadangan (sering tidak tersedia di cPanel).
