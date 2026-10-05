# Operasional dan perawatan

Pekerjaan rutin setelah aplikasi dipakai sekolah. Perintah dijalankan di folder aplikasi di server
(`~/school/lms-engine` di cPanel; di cPanel ganti `php` dengan `$PHP84`).

## Ringkasan rutin

| Kapan | Apa |
|---|---|
| Otomatis tiap malam 01.30 | Cadangan data (`sekolah:backup`, 14 terakhir disimpan) |
| Otomatis tiap Minggu 02.30 | Cadangan lengkap + berkas unggahan (4 terakhir) |
| Mingguan | Unduh satu cadangan dari menu **Cadangan Data**, simpan di luar server |
| Bulanan | Cek `storage/logs/laravel.log`, sisa ruang disk, menu **Pantauan Siswa** |
| Awal semester | Pastikan semester baru ada dan tanggalnya benar (Struktur Akademik) |
| Akhir tahun ajaran | Kenaikan kelas (lihat bawah) |
| Setiap rilis | `composer test` hijau → push → deploy → cek halaman depan dan log |

Cadangan otomatis **hanya berjalan bila cron `schedule:run` terpasang** (DEPLOY.md /
deploy-vps.md). Cek dengan `php artisan schedule:list` dan lihat isi `storage/app/private/backups/`.

## Cadangan dan pemulihan

```bash
php artisan sekolah:backup                     # data saja (cepat)
php artisan sekolah:backup --dengan-berkas     # data + materi/tugas/logo (bisa besar)
php artisan sekolah:restore cadangan-2026-10-05-013000.zip   # MENIMPA seluruh data
```

Sebelum pemulihan, buat cadangan terbaru dulu. Pemulihan menolak cadangan dari versi aplikasi yang
lebih baru; perbarui kode dan jalankan `migrate` terlebih dahulu. Setelah pulih, jalankan
`php artisan optimize:clear` (otomatis) dan cek login admin.

Admin juga bisa membuat dan mengunduh cadangan dari menu **Cadangan Data** (dari browser hanya
"data saja", karena cadangan lengkap bisa melewati batas waktu request).

## Akun

| Masalah | Solusi |
|---|---|
| Siswa/guru/ortu lupa password | Admin: menu Kelas & Siswa / Data Guru / Orang Tua → Reset Password. Guru bisa mereset siswa. Password baru tampil sekali, wajib diganti saat login |
| **Admin lupa password** | Di server: `php artisan sekolah:admin-password` (daftar admin), lalu `php artisan sekolah:admin-password email@admin` |
| Tidak ada admin sama sekali | `php artisan sekolah:setup`, jawab "ya" saat ditanya menambah admin baru |
| Orang tua tidak bisa melihat anak | Menu Orang Tua: permintaan mungkin masih menunggu atau ditolak. Admin bisa menambahkan tautan langsung |
| Siswa dulu diimpor dengan NIS, sekarang punya NISN | Impor ulang dengan **NISN dan NIS** terisi. Akun lama dikenali lewat NIS, NISN ditambahkan, dan username berubah menjadi NISN. Impor ulang selalu membuat password baru, jadi bagikan kartu akun yang baru |
| Impor menolak baris "cocok dengan dua akun berbeda" | NISN baris itu milik satu akun dan NIS-nya milik akun lain. Periksa data siswa; kemungkinan salah ketik di Excel atau di Dapodik |

## Pergantian tahun ajaran

1. **Struktur Akademik → Tahun & semester**: buat tahun ajaran baru (jangan diaktifkan dulu) beserta
   semester Ganjil/Genap.
2. **Kenaikan Kelas**: pilih tahun asal dan tujuan → **Salin daftar kelas** → periksa saran
   (7A→8A, kelas tertinggi→Lulus) → atur siswa yang tinggal kelas/pindah → **Proses** dengan opsi
   "Jadikan tahun ajaran aktif".
3. Atur wali kelas dan guru pengampu tahun baru di Struktur Akademik.
4. Impor siswa baru (tingkat terbawah) lewat **Import Akun**.
5. Susun jadwal baru. Rapor tahun lalu tetap bisa diunduh (pilih semester lama di menu Rapor).

## Memperbarui aplikasi

- cPanel: di PC `git push origin master`, lalu di server
  `PHP84=$PHP84 bash ~/school/lms-engine/scripts/server-deploy.sh` (lihat DEPLOY.md).
- VPS/server sendiri: [deploy-vps.md](deploy-vps.md#8-memperbarui-aplikasi).

Setiap rilis yang mengubah tampilan harus menyertakan `public/build` hasil
`npm run build:subfolder` (untuk cPanel).

## Log

- Aplikasi: `storage/logs/laravel.log` (izin 664 agar web server dan cron sama-sama bisa menulis).
- Web server: `/var/log/nginx/error.log` (VPS) atau *Errors* di cPanel.
- Reset password admin lewat terminal tercatat di log aplikasi; reset lewat aplikasi tercatat di
  tabel `password_reset_logs`; perubahan nilai di menu **Riwayat Nilai**.

## Pemecahan masalah

| Gejala | Penyebab & solusi |
|---|---|
| Halaman depan `405 Method Not Allowed` (cPanel `/lms`) | Route ter-cache di instalasi subfolder. `php artisan route:clear`; jangan pakai `route:cache`/`optimize` di subfolder |
| Font/ikon 404 di `/build/assets/...` (cPanel) | Aset tidak di-build untuk subfolder: `npm run build:subfolder`, commit, deploy |
| Tampilan berantakan, konsol browser "mixed content" | Di belakang tunnel/proxy tanpa `TRUSTED_PROXIES=127.0.0.1`. Isi lalu `php artisan config:cache` |
| Unggah gagal / `413 Request Entity Too Large` | Batas Nginx (`client_max_body_size`) atau PHP (`upload_max_filesize`, `post_max_size`) terlalu kecil |
| `Permission denied` di storage / `attempt to write a readonly database` | `chmod -R g+w storage bootstrap/cache database`, pastikan grup `www-data` dan setgid (deploy-vps.md langkah 3) |
| Logo tidak muncul di rapor PDF | Ekstensi PHP `gd` belum terpasang |
| Header menampilkan "Belum ada tahun ajaran aktif" | Aktifkan tahun ajaran di Struktur Akademik |
| Nilai akhir otomatis kosong | Belum ada nilai tugas/kuis dalam rentang tanggal semester, atau tanggal semester salah |
| Deploy cPanel berhenti "DB_CONNECTION seharusnya mysql" | `.env` server memakai database yang salah; perbaiki dulu sebelum migrasi |
| Perubahan `.env` tidak berpengaruh | Config ter-cache: `php artisan config:cache` |

## Situs demo

Situs demo memakai `APP_DEMO_MODE=true`, sehingga **siapa pun bisa masuk sebagai admin** dari halaman
depan. Jangan pernah mengaktifkannya di server sekolah sungguhan. Untuk mengembalikan data demo:
`php artisan db:seed --class=DemoSeeder --force` (memperbarui data demo, tidak menghapus data lain).
