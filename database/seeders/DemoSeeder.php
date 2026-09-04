<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\CalendarEvent;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        Material::query()->updateOrCreate(
            ['class_subject_id' => $math->id, 'title' => 'Mengenal Persamaan Linear'],
            ['description' => 'Materi pengantar persamaan linear satu variabel beserta contoh penerapannya.', 'order' => 1],
        );
        Material::query()->updateOrCreate(
            ['class_subject_id' => $science->id, 'title' => 'Klasifikasi Makhluk Hidup'],
            ['description' => 'Ringkasan ciri dan pengelompokan makhluk hidup.', 'order' => 1],
        );

        Assignment::query()->updateOrCreate(
            ['class_subject_id' => $math->id, 'title' => 'Latihan Persamaan Linear'],
            ['description' => 'Kerjakan lima soal latihan dan unggah jawaban dalam format PDF.', 'deadline' => now()->addDays(7)],
        );
        Assignment::query()->updateOrCreate(
            ['class_subject_id' => $science->id, 'title' => 'Observasi Lingkungan Sekolah'],
            ['description' => 'Catat lima jenis makhluk hidup yang ditemukan di lingkungan sekolah.', 'deadline' => now()->addDays(10)],
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
