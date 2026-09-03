<?php

use App\Http\Controllers\Admin\ImportCredentialsController;
use App\Http\Controllers\Admin\ImportTemplateController;
use App\Http\Controllers\Admin\ReportCardController;
use App\Http\Controllers\Auth\RequiredPasswordController;
use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\LearningFileController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\StudentController;
use App\Livewire\Admin\AcademicSetup;
use App\Livewire\Admin\AccountImport;
use App\Livewire\Admin\ReportCards;
use App\Livewire\CommunicationManager;
use App\Livewire\Student\EvaluationSummary;
use App\Livewire\Student\LearningCenter;
use App\Livewire\Student\QuizPlayer;
use App\Livewire\Teacher\EvaluationManager;
use App\Livewire\Teacher\LearningManager;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('siswa/login', [StudentLoginController::class, 'create'])->name('siswa.login');
    Route::post('siswa/login', [StudentLoginController::class, 'store'])
        ->middleware('throttle:student-login')
        ->name('siswa.login.store');
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
        Route::livewire('siswa/belajar', LearningCenter::class)->name('student.learning.index');
        Route::livewire('siswa/kuis/{quiz}', QuizPlayer::class)->name('student.quizzes.play');
        Route::livewire('siswa/nilai', EvaluationSummary::class)->name('student.evaluation.index');
    });
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
    Route::middleware('role:admin')->prefix('admin/rapor')->name('admin.report-cards.')->group(function () {
        Route::get('/', ReportCards::class)->name('index');
        Route::get('{semester}/{student}', ReportCardController::class)->name('download');
    });
    Route::get('dashboard/guru', [RoleDashboardController::class, 'teacher'])
        ->middleware('role:guru')
        ->name('dashboard.guru');
    Route::livewire('guru/pembelajaran', LearningManager::class)
        ->middleware('role:guru')
        ->name('teacher.learning.index');
    Route::livewire('guru/evaluasi', EvaluationManager::class)
        ->middleware('role:guru')
        ->name('teacher.evaluation.index');

    Route::middleware('role:admin|guru')->group(function () {
        Route::livewire('komunikasi', CommunicationManager::class)->name('communications.index');
        Route::get('siswa', [StudentController::class, 'index'])->name('students.index');
        Route::post('siswa/{student}/reset-password', [StudentController::class, 'resetPassword'])
            ->name('students.password.reset');
    });
});

require __DIR__.'/settings.php';
