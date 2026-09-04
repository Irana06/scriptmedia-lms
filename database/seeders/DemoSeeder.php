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
