<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentGrade;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\CalendarEvent;
use App\Models\ClassSubject;
use App\Models\DataImport;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = $this->user(
            email: 'admin.demo@example.com',
            name: 'Admin Demo',
            role: 'admin',
        );
        $teacher = $this->user(
            email: 'guru.demo@example.com',
            name: 'Ibu Ratna Demo',
            role: 'guru',
            extra: ['nip' => 'DEMO-GURU-001'],
        );

        $students = collect([
            ['name' => 'Budi Santoso', 'nisn' => '0099000001', 'nik' => '3273000000000001', 'gender' => 'L'],
            ['name' => 'Siti Aisyah', 'nisn' => '0099000002', 'nik' => '3273000000000002', 'gender' => 'P'],
            ['name' => 'Andi Pratama', 'nisn' => '0099000003', 'nik' => '3273000000000003', 'gender' => 'L'],
        ])->map(fn (array $student): User => $this->user(
            email: $student['nisn'].'@students.invalid',
            name: $student['name'],
            role: 'siswa',
            extra: [
                'username' => $student['nisn'],
                'nisn' => $student['nisn'],
                'nik' => $student['nik'],
                'gender' => $student['gender'],
                'must_change_password' => false,
            ],
        ));

        $year = AcademicYear::query()->updateOrCreate(
            ['year_label' => '2026/2027 (Demo)'],
            ['is_active' => true],
        );
        $semester = Semester::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Ganjil'],
            ['start_date' => '2026-07-01', 'end_date' => '2026-12-31'],
        );
        $class = SchoolClass::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'name' => '7A Demo'],
            ['homeroom_teacher_id' => $teacher->id],
        );
        $class->students()->syncWithoutDetaching($students->pluck('id'));

        $subjects = collect([
            ['name' => 'Matematika Demo', 'code' => 'MAT-DEMO', 'day' => 'Senin', 'start' => '08:00', 'end' => '09:30'],
            ['name' => 'IPA Demo', 'code' => 'IPA-DEMO', 'day' => 'Rabu', 'start' => '10:00', 'end' => '11:30'],
        ])->map(function (array $item) use ($class, $teacher): ClassSubject {
            $subject = Subject::query()->updateOrCreate(
                ['code' => $item['code']],
                ['name' => $item['name']],
            );
            $classSubject = ClassSubject::query()->updateOrCreate(
                ['class_id' => $class->id, 'subject_id' => $subject->id],
                ['teacher_id' => $teacher->id],
            );
            Schedule::query()->updateOrCreate(
                ['class_subject_id' => $classSubject->id, 'day' => $item['day'], 'start_time' => $item['start']],
                ['end_time' => $item['end']],
            );

            return $classSubject;
        });

        $math = $subjects->first();
        $science = $subjects->last();

        $mathMaterial = Material::query()->updateOrCreate(
            ['class_subject_id' => $math->id, 'title' => 'Mengenal Persamaan Linear'],
            ['description' => 'Pelajari konsep persamaan linear melalui ringkasan, visual, dan video. Buka setiap lampiran, lalu catat contoh penerapannya sebelum mengerjakan tugas.', 'order' => 1],
        );
        $scienceMaterial = Material::query()->updateOrCreate(
            ['class_subject_id' => $science->id, 'title' => 'Klasifikasi Makhluk Hidup'],
            ['description' => 'Amati foto dan video, kemudian bandingkan ciri setiap kelompok makhluk hidup menggunakan tautan bacaan pendukung.', 'order' => 1],
        );

        $pdfPath = 'learning/demo/ringkasan-persamaan-linear.pdf';
        Storage::disk('local')->put($pdfPath, Pdf::loadHTML(<<<'HTML'
            <html><body style="font-family: DejaVu Sans, sans-serif; color: #0b2545; padding: 28px;">
                <h1 style="color: #0b2545;">Ringkasan Persamaan Linear</h1>
                <p><strong>Tujuan:</strong> memahami bentuk ax + b = c dan menentukan nilai x.</p>
                <h2 style="color: #177876;">Langkah penyelesaian</h2>
                <ol><li>Sederhanakan kedua ruas.</li><li>Pindahkan konstanta ke ruas lainnya.</li><li>Bagi kedua ruas dengan koefisien x.</li></ol>
                <div style="background: #f4fafa; border-left: 5px solid #f4a300; padding: 14px; margin: 20px 0;"><strong>Contoh:</strong> 2x + 4 = 10 → 2x = 6 → x = 3.</div>
                <h2 style="color: #177876;">Latihan mandiri</h2>
                <p>Selesaikan: 3x + 5 = 20, 4x - 8 = 12, dan 2(x + 3) = 14.</p>
            </body></html>
            HTML)->setPaper('a4')->output());
        $mathMaterial->files()->where('file_path', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf')->delete();

        foreach ([
            ['type' => 'image', 'file_path' => 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=1200&q=80'],
            ['type' => 'pdf', 'file_path' => $pdfPath],
            ['type' => 'link', 'file_path' => 'https://www.youtube.com/watch?v=fNk_zzaMoSs'],
        ] as $attachment) {
            $mathMaterial->files()->updateOrCreate(['file_path' => $attachment['file_path']], ['type' => $attachment['type']]);
        }

        foreach ([
            ['type' => 'image', 'file_path' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1200&q=80'],
            ['type' => 'video', 'file_path' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4'],
            ['type' => 'link', 'file_path' => 'https://id.wikipedia.org/wiki/Klasifikasi_biologis'],
        ] as $attachment) {
            $scienceMaterial->files()->updateOrCreate(['file_path' => $attachment['file_path']], ['type' => $attachment['type']]);
        }

        Assignment::query()->updateOrCreate(
            ['class_subject_id' => $math->id, 'title' => 'Latihan Persamaan Linear'],
            ['description' => 'Kerjakan lima soal latihan dan unggah jawaban dalam format PDF.', 'deadline' => now()->addDays(7)],
        );
        Assignment::query()->updateOrCreate(
            ['class_subject_id' => $science->id, 'title' => 'Observasi Lingkungan Sekolah'],
            ['description' => 'Catat lima jenis makhluk hidup yang ditemukan di lingkungan sekolah.', 'deadline' => now()->addDays(10)],
        );
        $completedAssignment = Assignment::query()->updateOrCreate(
            ['class_subject_id' => $math->id, 'title' => 'Ringkasan Materi Persamaan Linear'],
            ['description' => 'Buat ringkasan satu halaman beserta satu contoh soal dan pembahasannya.', 'deadline' => now()->subDays(2)],
        );

        $quiz = Quiz::query()->updateOrCreate(
            ['class_subject_id' => $math->id, 'title' => 'Kuis Persamaan Linear'],
            ['duration_minutes' => 20, 'open_at' => now()->subDay(), 'close_at' => now()->addDays(14)],
        );
        $question = $quiz->questions()->updateOrCreate(
            ['question' => 'Nilai x dari 2x + 4 = 10 adalah ...'],
            ['type' => 'mc'],
        );
        foreach ([['2', false], ['3', true], ['4', false], ['5', false]] as [$label, $isCorrect]) {
            $question->choices()->updateOrCreate(['label' => $label], ['is_correct' => $isCorrect]);
        }

        $demoStudent = $students->firstOrFail();
        $answerPath = 'learning/demo/jawaban-budi-persamaan-linear.pdf';
        Storage::disk('local')->put($answerPath, Pdf::loadHTML(<<<'HTML'
            <html><body style="font-family: DejaVu Sans, sans-serif; color: #0b2545; padding: 28px;">
                <h1>Jawaban Ringkasan Persamaan Linear</h1>
                <p><strong>Nama:</strong> Budi Santoso</p>
                <p>Persamaan linear satu variabel memiliki bentuk umum ax + b = c.</p>
                <p><strong>Contoh:</strong> 3x + 5 = 20, sehingga 3x = 15 dan x = 5.</p>
            </body></html>
            HTML)->setPaper('a4')->output());
        $submission = AssignmentSubmission::query()->updateOrCreate(
            ['assignment_id' => $completedAssignment->id, 'student_id' => $demoStudent->id],
            ['file_path' => $answerPath, 'submitted_at' => now()->subDays(3)],
        );
        AssignmentGrade::query()->updateOrCreate(
            ['submission_id' => $submission->id],
            ['score' => 92, 'feedback' => 'Ringkasan sudah runtut dan contoh soal benar. Pertahankan cara penulisan langkah penyelesaiannya.'],
        );

        $attempt = QuizAttempt::query()->updateOrCreate(
            ['quiz_id' => $quiz->id, 'student_id' => $demoStudent->id],
            ['started_at' => now()->subMinutes(12), 'submitted_at' => now()->subMinutes(5), 'score' => 100],
        );
        $correctChoice = $question->choices()->where('is_correct', true)->firstOrFail();
        $attempt->answers()->updateOrCreate(
            ['question_id' => $question->id],
            ['answer' => (string) $correctChoice->id, 'score' => 1],
        );

        foreach ($students as $index => $student) {
            foreach ([[$math, 88 - $index], [$science, 90 - $index]] as [$classSubject, $score]) {
                Grade::query()->updateOrCreate(
                    ['student_id' => $student->id, 'class_subject_id' => $classSubject->id, 'semester_id' => $semester->id],
                    ['final_score' => $score, 'predikat' => $score >= 90 ? 'A' : 'B'],
                );
            }
            Attendance::query()->updateOrCreate(
                ['class_id' => $class->id, 'student_id' => $student->id, 'date' => now()->startOfDay()],
                ['status' => 'hadir'],
            );
        }

        Announcement::query()->updateOrCreate(
            ['title' => '[Demo] Selamat datang di RuangKelas'],
            [
                'body' => 'Data ini disiapkan untuk mencoba alur admin, guru, dan siswa.',
                'target' => 'all',
                'class_id' => null,
                'created_by' => $admin->id,
            ],
        );
        Announcement::query()->updateOrCreate(
            ['title' => '[Demo] Persiapan belajar kelas 7A'],
            [
                'body' => 'Silakan periksa materi, tugas, dan kuis yang sudah tersedia.',
                'target' => 'class',
                'class_id' => $class->id,
                'created_by' => $teacher->id,
            ],
        );
        CalendarEvent::query()->updateOrCreate(
            ['title' => '[Demo] Evaluasi tengah semester'],
            ['date' => now()->addDays(14)->toDateString(), 'description' => 'Agenda contoh untuk pengujian kalender.', 'created_by' => $admin->id],
        );

        $this->seedSecondClass($admin, $year, $semester, $class);
        $this->seedAttendanceHistory($semester);
        $this->seedImportHistory($admin);
    }

    /**
     * Satu kelas berisi tiga siswa tidak memperlihatkan apa pun soal skala.
     * Kelas kedua memberi guru sesuatu untuk dipindah-pindah saat demo, dan
     * memberi admin struktur akademik yang terlihat seperti sekolah sungguhan.
     */
    private function seedSecondClass(User $admin, AcademicYear $year, Semester $semester, SchoolClass $firstClass): void
    {
        $teachers = collect([
            ['name' => 'Pak Hendra Demo', 'email' => 'hendra.demo@example.com', 'nip' => '198203152008011003'],
            ['name' => 'Bu Yuli Demo', 'email' => 'yuli.demo@example.com', 'nuptk' => '7845762663300012'],
            // Tanpa email dan tanpa NIP/NUPTK: memperlihatkan guru sekolah swasta
            // yang masuk memakai username.
            ['name' => 'Pak Iwan Demo', 'email' => 'iwan.demo@guru.invalid', 'username' => 'iwan.demo'],
        ])->map(fn (array $item): User => $this->user(
            email: $item['email'],
            name: $item['name'],
            role: 'guru',
            extra: array_filter([
                'username' => $item['username'] ?? null,
                'nip' => $item['nip'] ?? null,
                'nuptk' => $item['nuptk'] ?? null,
            ]),
        ));

        $secondClass = SchoolClass::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'name' => '7B Demo'],
            ['homeroom_teacher_id' => $teachers[0]->id],
        );

        // NISN demo melanjutkan urutan tiga siswa pertama agar tidak bentrok.
        $names = [
            'Dewi Anggraini', 'Rizky Ramadhan', 'Putri Maharani', 'Fajar Nugroho',
            'Intan Permata', 'Bayu Setiawan', 'Nabila Zahra', 'Dimas Prasetyo',
            'Salsabila Putri', 'Yoga Pratama', 'Citra Lestari', 'Arif Wibowo',
            'Melati Kusuma', 'Reza Alfarizi', 'Anisa Rahmawati', 'Galih Saputra',
            'Tiara Amelia',
        ];

        $newStudents = collect($names)->values()->map(function (string $name, int $index): User {
            $nisn = str_pad((string) (99000004 + $index), 10, '0', STR_PAD_LEFT);

            return $this->user(
                email: $nisn.'@students.invalid',
                name: $name,
                role: 'siswa',
                extra: [
                    'username' => $nisn,
                    'nisn' => $nisn,
                    'gender' => $index % 2 === 0 ? 'P' : 'L',
                    'must_change_password' => false,
                ],
            );
        });

        // Sebagian masuk 7A agar kelas pertama tidak lagi hanya berisi tiga siswa.
        $firstClass->students()->syncWithoutDetaching($newStudents->take(7)->pluck('id'));
        $secondClass->students()->syncWithoutDetaching($newStudents->skip(7)->pluck('id'));

        $catalogue = [
            ['name' => 'Bahasa Indonesia Demo', 'code' => 'BIN-DEMO', 'teacher' => 0, 'day' => 'Selasa', 'start' => '07:00', 'end' => '08:30'],
            ['name' => 'IPS Demo', 'code' => 'IPS-DEMO', 'teacher' => 1, 'day' => 'Kamis', 'start' => '09:00', 'end' => '10:30'],
            ['name' => 'Bahasa Inggris Demo', 'code' => 'BIG-DEMO', 'teacher' => 2, 'day' => 'Jumat', 'start' => '07:30', 'end' => '09:00'],
        ];

        foreach ([$firstClass, $secondClass] as $offset => $target) {
            foreach ($catalogue as $item) {
                $subject = Subject::query()->updateOrCreate(
                    ['code' => $item['code']],
                    ['name' => $item['name']],
                );
                $classSubject = ClassSubject::query()->updateOrCreate(
                    ['class_id' => $target->id, 'subject_id' => $subject->id],
                    ['teacher_id' => $teachers[$item['teacher']]->id],
                );

                // Kelas kedua digeser satu jam supaya guru yang sama tidak bentrok.
                $start = Carbon::createFromFormat('H:i', $item['start'])->addHours($offset * 3);
                $end = Carbon::createFromFormat('H:i', $item['end'])->addHours($offset * 3);

                Schedule::query()->updateOrCreate(
                    ['class_subject_id' => $classSubject->id, 'day' => $item['day'], 'start_time' => $start->format('H:i:s')],
                    ['end_time' => $end->format('H:i:s')],
                );

                Material::query()->updateOrCreate(
                    ['class_subject_id' => $classSubject->id, 'title' => 'Pengantar '.$item['name']],
                    ['description' => 'Materi pembuka yang dipakai untuk menguji tampilan daftar materi.', 'order' => 1],
                );
                Assignment::query()->updateOrCreate(
                    ['class_subject_id' => $classSubject->id, 'title' => 'Tugas Pekan Pertama'],
                    ['description' => 'Kerjakan latihan pada buku halaman pertama bab ini.', 'deadline' => now()->addDays(5 + $offset)],
                );

                foreach ($target->students as $position => $student) {
                    $score = 72 + (($student->id + $position) % 23);

                    Grade::query()->updateOrCreate(
                        ['student_id' => $student->id, 'class_subject_id' => $classSubject->id, 'semester_id' => $semester->id],
                        ['final_score' => $score, 'predikat' => match (true) {
                            $score >= 90 => 'A',
                            $score >= 80 => 'B',
                            default => 'C',
                        }],
                    );
                }
            }
        }

        Announcement::query()->updateOrCreate(
            ['title' => '[Demo] Pembagian wali kelas 7B'],
            [
                'body' => 'Kelas 7B diampu oleh Pak Hendra sebagai wali kelas. Silakan hubungi beliau untuk urusan administrasi kelas.',
                'target' => 'class',
                'class_id' => $secondClass->id,
                'created_by' => $admin->id,
            ],
        );

        $this->seedDemoTeacherWeek($semester, $firstClass, $secondClass);
    }

    /**
     * Tombol "Masuk Guru" di halaman depan memakai akun Ibu Ratna. Tanpa ini,
     * dashboard-nya hanya berisi satu kelas, jadwal kosong di sebagian besar
     * hari, dan tidak ada tugas yang menunggu dinilai — sisi guru terlihat mati
     * justru saat didemokan.
     */
    private function seedDemoTeacherWeek(Semester $semester, SchoolClass $firstClass, SchoolClass $secondClass): void
    {
        $teacher = User::query()->where('email', 'guru.demo@example.com')->firstOrFail();
        $subjects = Subject::query()->whereIn('code', ['MAT-DEMO', 'IPA-DEMO'])->get()->keyBy('code');

        /** @var array<string, array<string, ClassSubject>> $classSubjects */
        $classSubjects = [];

        foreach ([$firstClass, $secondClass] as $class) {
            foreach ($subjects as $code => $subject) {
                $classSubjects[$class->name][$code] = ClassSubject::query()->updateOrCreate(
                    ['class_id' => $class->id, 'subject_id' => $subject->id],
                    ['teacher_id' => $teacher->id],
                );
            }
        }

        // Satu sesi setiap hari sekolah agar "Jadwal hari ini" tidak pernah kosong
        // kapan pun demo dilakukan. Slot dipilih supaya tidak bentrok dengan mapel
        // lain di kelas yang sama maupun dengan sesi Ibu Ratna yang sudah ada.
        $week = [
            ['Senin', $secondClass, 'MAT-DEMO', '10:00', '11:30'],
            ['Selasa', $firstClass, 'IPA-DEMO', '09:00', '10:30'],
            ['Rabu', $secondClass, 'IPA-DEMO', '07:00', '08:30'],
            ['Kamis', $secondClass, 'MAT-DEMO', '07:00', '08:30'],
            ['Jumat', $firstClass, 'MAT-DEMO', '13:00', '14:30'],
            ['Sabtu', $secondClass, 'IPA-DEMO', '08:00', '09:30'],
        ];

        foreach ($week as [$day, $class, $code, $start, $end]) {
            Schedule::query()->updateOrCreate(
                ['class_subject_id' => $classSubjects[$class->name][$code]->id, 'day' => $day, 'start_time' => $start.':00'],
                ['end_time' => $end.':00'],
            );
        }

        // Kumpulan tugas yang belum dinilai, supaya alur penilaian bisa langsung
        // didemokan. Budi, akun tombol "Masuk Siswa", sengaja tidak ikut agar
        // tugas ini tetap muncul di daftar deadline-nya.
        $assignment = Assignment::query()
            ->where('class_subject_id', $classSubjects[$firstClass->name]['MAT-DEMO']->id)
            ->where('title', 'Latihan Persamaan Linear')
            ->firstOrFail();

        $answerPath = 'learning/demo/jawaban-latihan-persamaan-linear.pdf';
        Storage::disk('local')->put($answerPath, Pdf::loadHTML(<<<'HTML'
            <html><body style="font-family: DejaVu Sans, sans-serif; color: #0b2545; padding: 28px;">
                <h1>Jawaban Latihan Persamaan Linear</h1>
                <p>1. 3x + 5 = 20, maka 3x = 15 dan x = 5.</p>
                <p>2. 4x - 8 = 12, maka 4x = 20 dan x = 5.</p>
                <p>3. 2(x + 3) = 14, maka x + 3 = 7 dan x = 4.</p>
            </body></html>
            HTML)->setPaper('a4')->output());

        $submitters = $firstClass->students()->get()
            ->reject(fn (User $student): bool => $student->username === '0099000001')
            ->take(5)
            ->values();

        foreach ($submitters as $position => $student) {
            AssignmentSubmission::query()->updateOrCreate(
                ['assignment_id' => $assignment->id, 'student_id' => $student->id],
                ['file_path' => $answerPath, 'submitted_at' => now()->subHours(6 + $position * 5)],
            );
        }

        // Nilai akhir untuk semua siswa di kedua kelas. firstOrCreate menjaga
        // nilai tiga siswa pertama yang ditulis tangan di atas tetap utuh.
        foreach ([$firstClass, $secondClass] as $class) {
            $students = $class->students()->get();

            foreach ($classSubjects[$class->name] as $classSubject) {
                foreach ($students as $student) {
                    $score = 70 + (($student->id * 7 + $classSubject->id) % 26);

                    Grade::query()->firstOrCreate(
                        ['student_id' => $student->id, 'class_subject_id' => $classSubject->id, 'semester_id' => $semester->id],
                        ['final_score' => $score, 'predikat' => match (true) {
                            $score >= 90 => 'A',
                            $score >= 80 => 'B',
                            default => 'C',
                        }],
                    );
                }
            }
        }
    }

    /**
     * Presensi satu hari membuat halaman rekap terlihat kosong. Riwayat dua
     * pekan dengan variasi izin, sakit, dan alpa memperlihatkan gunanya rekap.
     */
    private function seedAttendanceHistory(Semester $semester): void
    {
        $classes = SchoolClass::query()->with('students')->where('academic_year_id', $semester->academic_year_id)->get();
        $statuses = ['hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'izin', 'sakit', 'hadir', 'alpa'];

        foreach ($classes as $class) {
            foreach ($class->students as $student) {
                $date = now()->startOfDay();

                for ($day = 0; $day < 14; $day++) {
                    $date = $date->copy()->subDay();

                    if ($date->isWeekend()) {
                        continue;
                    }

                    // Cari dengan objek tanggal, bukan teks "Y-m-d". Cast "date" menyimpan
                    // "Y-m-d 00:00:00", dan SQLite membandingkan tanggal sebagai teks —
                    // pencarian memakai "Y-m-d" tidak pernah menemukan baris lama, sehingga
                    // seeder gagal saat dijalankan ulang.
                    Attendance::query()->updateOrCreate(
                        ['class_id' => $class->id, 'student_id' => $student->id, 'date' => $date->startOfDay()],
                        ['status' => $statuses[($student->id + $day) % count($statuses)]],
                    );
                }
            }
        }
    }

    /**
     * Riwayat impor yang sudah selesai supaya alur admin bisa didemokan tanpa
     * harus benar-benar mengunggah berkas di depan calon pengguna.
     */
    private function seedImportHistory(User $admin): void
    {
        DataImport::query()->updateOrCreate(
            ['file_path' => 'imports/demo/impor-siswa-awal-tahun.xlsx'],
            [
                'type' => 'siswa',
                'imported_by' => $admin->id,
                'total_rows' => 22,
                'success_count' => 20,
                'failed_count' => 2,
                'status' => 'done',
                'failures' => [
                    ['row' => 9, 'message' => 'NISN atau NIS harus diisi.'],
                    ['row' => 17, 'message' => 'Kelas 7C tidak ditemukan pada tahun ajaran aktif.'],
                ],
                'downloaded_at' => now()->subDays(3),
            ],
        );
    }

    /** @param array<string, mixed> $extra */
    private function user(string $email, string $name, string $role, array $extra = []): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            array_merge([
                'name' => $name,
                'password' => 'Demo12345!',
                'email_verified_at' => now(),
            ], $extra),
        );
        $user->syncRoles([$role]);

        return $user;
    }
}
