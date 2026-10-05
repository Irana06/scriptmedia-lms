# Arsitektur

Aplikasi Laravel monolit dengan halaman interaktif berbasis **Livewire 4 (komponen kelas)**. Tidak
ada API terpisah atau SPA; semua halaman dirender server dan dinavigasi dengan `wire:navigate`.

## Struktur folder

```
app/
  Actions/              Aksi tunggal yang dipakai beberapa tempat (ResetAccountPassword, Fortify)
  Auth/StudentAccess    Satu-satunya penentu "data siswa siapa yang boleh dilihat/diubah"
  Console/Commands/     sekolah:setup, sekolah:admin-password, sekolah:backup, sekolah:restore
  Exports/ Imports/     Laravel Excel: template impor, kartu akun, leger nilai, rekap presensi, soal kuis
  Http/Controllers/     Halaman non-Livewire: dashboard, rapor PDF, unduhan berkas, manifest PWA
  Http/Middleware/      EnsurePasswordIsChanged (paksa ganti password sementara)
  Livewire/             Halaman interaktif, dikelompokkan per peran (Admin/, Teacher/, Student/, Guardian/)
  Models/               Eloquent
  Notifications/        SchoolNotification (notifikasi lonceng, channel database)
  Services/             Logika bisnis yang cukup besar (impor akun, nilai akhir, cadangan, kenaikan kelas, ...)
  Support/              Helper kecil tanpa state (format nilai, hari sekolah, audiens pengumuman, ...)
resources/views/
  components/layouts/   guru-admin (sidebar staf), siswa, ortu — tiga "kulit" per peran
  livewire/             View komponen Livewire, folder mengikuti app/Livewire
  pdf/report-card       Rapor PDF (DomPDF)
routes/web.php          Semua route, dikelompokkan per middleware peran
routes/console.php      Jadwal cadangan otomatis
scripts/server-deploy.sh  Deploy cPanel (git pull + langkah artisan)
```

## Peran dan otorisasi

Peran memakai `spatie/laravel-permission`: `admin`, `guru`, `siswa`, `ortu`. Lapisan pengamanan:

1. **Middleware route** (`role:admin`, `role:guru`, `role:admin|guru`, `role:siswa`, `role:ortu`) di
   `routes/web.php` — menyaring siapa yang boleh membuka halaman.
2. **Di dalam komponen/controller** — data selalu di-*scope*: guru hanya kelas yang diampunya
   (`teacher_id = Auth::id()`), wali kelas hanya kelas yang diwalikannya (`homeroom_teacher_id`),
   siswa/ortu lewat `StudentAccess`.

### StudentAccess (penting)

`App\Auth\StudentAccess` memisahkan dua jalur:

- `viewedStudent()` — siswa yang **datanya dilihat**. Untuk siswa: dirinya sendiri. Untuk orang tua:
  anak yang dipilih, dan hanya bila tautannya **disetujui admin** (`GuardianLink` status `approved`).
- `actingStudent()` — siswa yang **melakukan aksi** (kumpul tugas, kerjakan kuis). Selalu siswa
  yang login, **tidak pernah orang tua**.

Komponen siswa memakai trait `ResolvesStudent` (`viewedStudentId()` / `actingStudentId()`).
**Jangan memakai `Auth::id()` langsung** untuk data siswa. Fitur baru untuk orang tua hanya boleh
memakai jalur baca. Unduhan berkas (`LearningFileController`) juga memakai `StudentAccess::canView()`.

### Akun dan login

- Admin & guru: `/login` (Fortify) dengan email **atau username** (guru swasta tanpa email diberi
  username, email internal `@guru.invalid`).
- Siswa: `/siswa/login` dengan NISN atau NIS (email internal `@students.invalid`).
- Orang tua: `/ortu/masuk`, bisa daftar sendiri di `/ortu/daftar` lalu mengajukan tautan ke anak
  (NISN/NIS + nama lengkap, maks. 5 percobaan/jam, pesan error sengaja seragam).
- Akun yang dibuat sistem (impor, reset, wizard) diberi password acak + `must_change_password`;
  middleware `EnsurePasswordIsChanged` memaksa ganti di login pertama.
- Reset password terpusat di `App\Actions\ResetAccountPassword` (admin: siswa/guru/ortu; guru: siswa;
  tidak ada yang mereset admin lewat aplikasi — pakai `php artisan sekolah:admin-password`).

## Model data (ringkas)

```
AcademicYear 1─* Semester
AcademicYear 1─* SchoolClass (tabel classes) *─* User[siswa]   (class_students; satu kelas per tahun ajaran)
SchoolClass 1─* ClassSubject *─1 Subject (kkm)   ClassSubject *─1 User[guru] (teacher_id)
ClassSubject 1─* Schedule | Material(→MaterialFile) | Assignment(→Submission→AssignmentGrade) | Quiz(→Question→Choice, →Attempt→Answer)
Grade (nilai akhir per siswa × ClassSubject × Semester, + predikat & deskripsi capaian)
Attendance (per siswa × kelas × tanggal)
GuardianLink (guardian_student: ortu ↔ siswa, status pending/approved/rejected)
Announcement (target all | class) ─ announcement_reads (status baca per pengguna)
CalendarEvent, DataImport (riwayat impor), PasswordResetLog, GradeAudit (jejak perubahan nilai)
notifications (notifikasi lonceng bawaan Laravel)
Singleton id=1: SchoolProfile (profil & logo), GradeWeightSetting (bobot tugas/kuis/UTS/UAS)
```

Keanggotaan kelas tahun lalu **tidak dihapus** saat kenaikan kelas, supaya rapor lama tetap bisa
dibuka. Siswa lulus ditandai `users.graduated_at`.

## Alur penting

- **Nilai akhir** (`FinalGradeService`): rata-rata per kategori (tugas/kuis/UTS/UAS) dari tugas dan
  kuis dalam rentang semester, digabung sesuai `GradeWeightSetting`. Kategori tanpa nilai ditiadakan
  dan bobotnya dibagi proporsional. Guru menekan *Hitung otomatis*, boleh mengoreksi, lalu simpan.
  Deskripsi capaian dibuat `CompetencyDescription` dari nilai + KKM mapel bila guru tidak mengisi.
- **Skor kuis** (`QuizScoringService`): PG otomatis 0/1, esai dinilai guru 0–1; skor = persen benar.
  Urutan soal/pilihan diacak per siswa secara deterministik (dari id percobaan).
- **Impor akun** (`AccountImportService`): membaca Excel Dapodik/EMIS apa adanya (banyak alias nama
  kolom), NISN boleh diganti NIS, guru tanpa email/NIP diberi username. Impor ulang memperbarui,
  tidak menggandakan.
- **Notifikasi** (`SchoolNotifier`): dikirim sinkron (server cPanel umumnya tanpa queue worker).
  Pengumuman tidak disalin per pengguna; status baca lewat `announcement_reads` + batas
  `users.announcements_seen_at` untuk "tandai semua dibaca".
- **Cadangan** (`BackupService`): tiap tabel → JSON Lines dalam ZIP (tanpa mysqldump), opsional
  berkas unggahan. Pemulihan mengosongkan semua tabel dulu lalu mengisi ulang dengan cek foreign key
  dimatikan.
- **Jejak nilai** (`GradeAudit::record`): setiap perubahan nilai akhir/tugas/esai dicatat; hanya
  ditambah, tidak pernah diubah.

## Penyimpanan berkas

| Disk | Isi | Akses |
|---|---|---|
| `local` (storage/app/private) | Materi, kiriman tugas, hasil impor, cadangan | Lewat controller dengan cek izin (`LearningFileController`, unduhan admin) |
| `public` (storage/app/public → public/storage) | Logo sekolah, gambar soal kuis | URL publik (nama berkas acak) |

## Tampilan

Tailwind 4 dengan token warna ScriptMedia (navy `#0B2545`, tosca `#2CA6A4`, orange `#F4A300`,
off-white `#F4FAFA`), font Questrial, komponen tema di `resources/views/components/theme/`
(`card`, `button`, `badge`, `section-header`). Branding sekolah lewat `<x-school-mark>` dan
`SchoolProfile::current()->brandName()`.

`public/build/` **di-commit** (dibuat dengan `npm run build:subfolder` untuk cPanel di `/lms`),
karena server cPanel tidak menjalankan Node.
