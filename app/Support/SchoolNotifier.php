<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\SchoolNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Titik kirim notifikasi lonceng. Semua dikirim langsung (tanpa queue) dan
 * hanya ke penerima yang relevan — satu kelas paling banyak puluhan orang.
 */
class SchoolNotifier
{
    public static function assignmentGraded(AssignmentSubmission $submission, float $score): void
    {
        $submission->loadMissing('assignment.classSubject.subject', 'student');
        $title = $submission->assignment->title;
        $subject = $submission->assignment->classSubject->subject->name;
        $scoreText = Score::format($score);

        $submission->student->notify(new SchoolNotification(
            title: 'Tugas sudah dinilai',
            body: "{$subject} · {$title}: nilai {$scoreText}",
            url: route('student.activities.index'),
            icon: 'check-badge',
        ));

        Notification::send($submission->student->guardians()->get(), new SchoolNotification(
            title: "Tugas {$submission->student->name} dinilai",
            body: "{$subject} · {$title}: nilai {$scoreText}",
            url: route('dashboard.ortu'),
            icon: 'check-badge',
        ));
    }

    public static function assignmentSubmitted(AssignmentSubmission $submission): void
    {
        $submission->loadMissing('assignment.classSubject.teacher', 'student');
        $classSubject = $submission->assignment->classSubject;

        $classSubject->teacher?->notify(new SchoolNotification(
            title: 'Kiriman tugas baru',
            body: "{$submission->student->name} mengumpulkan \"{$submission->assignment->title}\"",
            url: route('teacher.learning.index', ['classSubjectId' => $classSubject->id, 'tab' => 'assignments']),
            icon: 'inbox-arrow-down',
        ));
    }

    public static function newAssignment(Assignment $assignment): void
    {
        self::toClassStudents($assignment->classSubject()->firstOrFail(), new SchoolNotification(
            title: 'Tugas baru',
            body: "{$assignment->title} · batas {$assignment->deadline->translatedFormat('d M, H:i')}",
            url: route('student.learning.index', ['classSubjectId' => $assignment->class_subject_id, 'tab' => 'assignments']),
            icon: 'clipboard-document-list',
        ));
    }

    public static function newQuiz(Quiz $quiz): void
    {
        self::toClassStudents($quiz->classSubject()->firstOrFail(), new SchoolNotification(
            title: 'Kuis baru',
            body: "{$quiz->title} · dibuka {$quiz->open_at->translatedFormat('d M, H:i')}",
            url: route('student.learning.index', ['classSubjectId' => $quiz->class_subject_id, 'tab' => 'quizzes']),
            icon: 'puzzle-piece',
        ));
    }

    public static function guardianLinkRequested(User $guardian, User $student): void
    {
        $admins = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->get();

        Notification::send($admins, new SchoolNotification(
            title: 'Permintaan tautan orang tua',
            body: "{$guardian->name} meminta akses ke data {$student->name}",
            url: route('admin.guardians.index'),
            icon: 'home-modern',
        ));
    }

    private static function toClassStudents(ClassSubject $classSubject, SchoolNotification $notification): void
    {
        Notification::send($classSubject->schoolClass()->firstOrFail()->students()->get(), $notification);
    }
}
