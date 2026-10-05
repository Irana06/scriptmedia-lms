# Serah terima

Kondisi per Oktober 2026: semua fitur untuk empat peran **berfungsi**, `composer test` hijau
(±200 test). Notifikasi WhatsApp/email **ditunda**.

## Repositori

- Sekarang: `https://github.com/Irana06/scriptmedia-lms` (akun pribadi pengembang, branch `master`).
- Pindahkan ke akun perusahaan: GitHub → *Settings* → *Transfer ownership*. Setelah itu di server
  jalankan `git remote set-url origin <url-baru>`. Bila repo privat, tambahkan *deploy key*.

## Server

| | Alamat | Catatan |
|---|---|---|
| Produksi (cPanel perusahaan) | `school.scriptmedia.net/lms` | Folder `~/school/lms-engine`, MySQL, deploy lewat `git pull`. Cara menjalankan perintah: [server.md](server.md) |
| Demo | `https://lms.yushika.my.id` | Server pribadi pengembang, **bukan aset perusahaan**, bisa dimatikan kapan saja |
| Lokal | `http://lms-engine.test` | Herd + SQLite |

## Akun demo

Dibuat oleh `DemoSeeder`. Password semua akun: **`Demo12345!`**

| Peran | Masuk di | Login |
|---|---|---|
| Admin | `/login` | `admin.demo@example.com` |
| Guru | `/login` | `guru.demo@example.com`, `hendra.demo@example.com`, `yuli.demo@example.com`, username `iwan.demo` |
| Siswa | `/siswa/login` | NISN `0099000001` s.d. `0099000020` |
| Orang tua | `/ortu/masuk` | `ortu.demo@example.com` (tertaut), `joko.demo@example.com` (menunggu persetujuan) |

## Kredensial produksi

Jangan pernah commit nilai rahasia ke repo. Serahkan lewat jalur aman, lalu minta penerima
menggantinya.

| Kredensial | Lokasi | Cara reset |
|---|---|---|
| Admin aplikasi | Database sekolah | `php artisan sekolah:admin-password <email>` di server |
| Guru/siswa/orang tua | Database | Reset oleh admin di aplikasi |
| Database MySQL | `.env` server + cPanel → MySQL Databases | Ganti di cPanel, ubah `.env`, `artisan config:cache` |
| `APP_KEY` | `.env` server | **Jangan diganti** (sesi dan 2FA terenkripsi dengannya); simpan salinannya |
| Login cPanel | Perusahaan | Lewat penyedia hosting |
| GitHub | Akun pengembang | Transfer ownership, lalu cabut akses pengembang |

## Tugas tertunda

1. Deploy versi terbaru ke cPanel begitu bisa diakses, dan pasang cron cadangan ([server.md](server.md)).
2. Uji tampilan fitur baru secara manual di browser dan HP (semua halaman sudah lolos test render).
3. Konfirmasi format rapor Kurikulum Merdeka dengan klien (sekarang: KKM per mapel, deskripsi
   capaian otomatis yang bisa disunting, predikat A–E).
4. Ide lanjutan: notifikasi WhatsApp/email (titik kirimnya `App\Support\SchoolNotifier`), bobot nilai
   per tahun ajaran, reset otomatis data demo.

Dokumen untuk pengguna sekolah (*Manual Admin*, *Kebijakan Privasi*) ada di akun Claude pengembang;
ekspor atau bagikan sebelum kontrak berakhir.

## Yang harus dipertahankan

- Orang tua **hanya membaca**, tidak pernah bertindak atas nama anak (`App\Auth\StudentAccess`).
- Tautan orang tua–anak wajib disetujui admin.
- Satu instalasi per sekolah; tanpa queue worker; cadangan tanpa `mysqldump`.
