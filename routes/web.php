<?php

use App\Http\Controllers\Admin\ImportCredentialsController;
use App\Http\Controllers\Admin\ImportTemplateController;
use App\Http\Controllers\Admin\ReportCardController;
use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\Auth\GuardianRegistrationController;
use App\Http\Controllers\Auth\RequiredPasswordController;
use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\LearningFileController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Livewire\Admin\AcademicSetup;
use App\Livewire\Admin\AccountImport;
use App\Livewire\Admin\GuardianManager;
use App\Livewire\Admin\ReportCards;
use App\Livewire\CommunicationManager;
use App\Livewire\Guardian\GuardianHome;
use App\Livewire\Student\ActivityCenter;
use App\Livewire\Student\EvaluationSummary;
use App\Livewire\Student\LearningCenter;
use App\Livewire\Student\QuizPlayer;
use App\Livewire\Teacher\EvaluationManager;
use App\Livewire\Teacher\LearningManager;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::post('demo/login/{role}', DemoLoginController::class)
        ->middleware('throttle:10,1')
        ->whereIn('role', ['admin', 'guru', 'siswa', 'ortu'])
        ->name('demo.login');
    Route::get('siswa/login', [StudentLoginController::class, 'create'])->name('siswa.login');
    Route::post('siswa/login', [StudentLoginController::class, 'store'])
        ->middleware('throttle:student-login')
        ->name('siswa.login.store');

    Route::view('ortu/masuk', 'auth.ortu-masuk')->name('ortu.login');
    Route::get('ortu/daftar', [GuardianRegistrationController::class, 'create'])->name('ortu.register');
    Route::post('ortu/daftar', [GuardianRegistrationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('ortu.register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardRedirectController::class)->name('dashboard');
    Route::get('berkas/materi/{materialFile}', [LearningFileController::class, 'material'])->name('learning.files.material');
    Route::get('berkas/tugas/{submission}', [LearningFileController::class, 'submission'])->name('learning.files.submission');

    Route::get('wajib-ganti-password', [RequiredPasswordController::class, 'edit'])
        ->name('siswa.password.required');
    Route::put('wajib-ganti-password', [RequiredPasswordController::class, 'update'])
        ->name('siswa.password.update');

    Route::middleware('role:siswa')->group(function () {
        Route::get('dashboard/siswa', [RoleDashboardController::class, 'student'])->name('dashboard.siswa');
        Route::livewire('siswa/aktivitas', ActivityCenter::class)->name('student.activities.index');
        Route::livewire('siswa/belajar', LearningCenter::class)->name('student.learning.index');
        Route::livewire('siswa/kuis/{quiz}', QuizPlayer::class)->name('student.quizzes.play');
        Route::livewire('siswa/nilai', EvaluationSummary::class)->name('student.evaluation.index');
    });

    // Orang tua tidak diwajibkan verifikasi email: yang menjaga data anak adalah
    // persetujuan tautan oleh admin, bukan kepemilikan alamat email.
    Route::livewire('ortu', GuardianHome::class)
        ->middleware('role:ortu')
        ->name('dashboard.ortu');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard/admin', [RoleDashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('dashboard.admin');
    Route::livewire('admin/akademik', AcademicSetup::class)
        ->middleware('role:admin')
        ->name('admin.academic.index');
    Route::middleware('role:admin')->prefix('admin/import')->name('admin.imports.')->group(function () {
        Route::get('/', AccountImport::class)->name('index');
        Route::get('template/{type}', ImportTemplateController::class)->name('template');
        Route::get('{dataImport}/kartu-akun', ImportCredentialsController::class)->name('credentials');
    });
    // Admin melihat rapor semua siswa. Guru hanya rapor kelas yang diwalikannya —
    // dicek di dalam komponen/controller, bukan lewat middleware role saja.
    Route::middleware('role:admin|guru')->prefix('admin/rapor')->name('admin.report-cards.')->group(function () {
        Route::get('/', ReportCards::class)->name('index');
        Route::get('{semester}/{student}', ReportCardController::class)->name('download');
    });
    Route::livewire('admin/orang-tua', GuardianManager::class)
        ->middleware('role:admin')
        ->name('admin.guardians.index');
    Route::get('dashboard/guru', [RoleDashboardController::class, 'teacher'])
        ->middleware('role:guru')
        ->name('dashboard.guru');
    Route::livewire('guru/pembelajaran', LearningManager::class)
        ->middleware('role:guru')
        ->name('teacher.learning.index');
    Route::livewire('guru/evaluasi', EvaluationManager::class)
        ->middleware('role:guru')
        ->name('teacher.evaluation.index');

    Route::middleware('role:admin')->group(function () {
        Route::get('guru', [TeacherController::class, 'index'])->name('teachers.index');
        Route::post('guru/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])
            ->name('teachers.password.reset');
    });

    Route::middleware('role:admin|guru')->group(function () {
        Route::livewire('komunikasi', CommunicationManager::class)->name('communications.index');
        Route::get('siswa', [StudentController::class, 'index'])->name('students.index');
        Route::post('siswa/{student}/reset-password', [StudentController::class, 'resetPassword'])
            ->name('students.password.reset');
    });
});

require __DIR__.'/settings.php';
