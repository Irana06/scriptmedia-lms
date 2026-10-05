# Teknis: arsitektur dan pengembangan

## Struktur

```
app/Auth/StudentAccess     penentu data siswa siapa yang boleh dilihat/diubah
app/Livewire/<Peran>/      halaman interaktif (Admin, Teacher, Student, Guardian)
app/Http/Controllers/      dashboard, rapor PDF, unduhan berkas, manifest PWA
app/Services/              logika besar: impor akun, nilai akhir, kuis, cadangan, kenaikan kelas, pantauan
app/Support/               helper kecil: notifikasi, audiens pengumuman, format nilai
app/Console/Commands/      sekolah:setup | admin-password | backup | restore
resources/views/components/layouts/   kulit per peran: guru-admin, siswa, ortu
routes/web.php             route dikelompokkan per middleware peran
```

## Aturan penting

- **Data siswa selalu lewat `StudentAccess`** (`viewedStudent()` untuk membaca, `actingStudent()` untuk
  aksi), bukan `Auth::id()`. Orang tua hanya boleh lewat jalur baca.
- Otorisasi dua lapis: middleware `role:` di route **dan** scope data di komponen (guru hanya kelas
  yang diampu, wali kelas hanya kelasnya).
- Akun buatan sistem (impor, reset, wizard) diberi password acak + wajib ganti saat login.
- Notifikasi tidak di-queue (hosting sekolah umumnya tanpa worker).
- `SchoolProfile` dan `GradeWeightSetting` adalah baris tunggal (id 1).
- Berkas materi/tugas/cadangan ada di disk `local` (lewat controller dengan cek izin); logo dan gambar
  soal di disk `public`.

## Alur utama

| Alur | Lokasi |
|---|---|
| Nilai akhir berbobot tugas/kuis/UTS/UAS, deskripsi capaian dari KKM | `FinalGradeService`, `CompetencyDescription`, `Teacher\EvaluationManager` |
| Kuis (acak per siswa, skor otomatis PG, esai dinilai guru) | `Student\QuizPlayer`, `QuizScoringService` |
| Impor akun Excel Dapodik/EMIS (NIS bila tanpa NISN, guru tanpa email) | `AccountImportService` |
| Orang tua (daftar, tautan disetujui admin) | `Guardian\GuardianHome`, `Admin\GuardianManager`, `GuardianLink` |
| Cadangan ZIP (JSON Lines per tabel) & pemulihan | `BackupService` |
| Jejak perubahan nilai | `GradeAudit::record()` |

## Mengembangkan

```bash
composer setup && php artisan db:seed --class=DemoSeeder
composer dev                                   # atau Herd: http://lms-engine.test
php artisan test --filter=NamaTest             # satu test
composer test                                  # wajib hijau sebelum push
```

Isi `APP_DEMO_MODE=true` di `.env` lokal untuk tombol masuk demo.

Konvensi:
- Teks antarmuka dan komentar dalam Bahasa Indonesia; nama kode dalam Bahasa Inggris. Komentar hanya
  untuk menjelaskan "kenapa".
- Pakai komponen tema `x-theme.*` dan warna yang sudah ada.
- Setiap fitur disertai feature test, dan halamannya ditambahkan ke `tests/Feature/PageSmokeTest.php`.
- PHPStan: perbaiki penyebabnya, jangan pakai `@phpstan-ignore`/baseline. `env()` hanya di `config/`.
- Commit: `feat:`/`fix:`/`docs:` dalam Bahasa Indonesia, **tanpa** `Co-Authored-By` asisten AI.

**Aset frontend:** `public/build` ikut di-commit karena cPanel tidak menjalankan Node. Setelah
mengubah tampilan:

```bash
npm run build:subfolder && git add public/build    # versi /lms untuk cPanel
```

Untuk server di root domain, build ulang di server dengan `npm run build`.
