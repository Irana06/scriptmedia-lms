<?php

use App\Http\Controllers\Auth\RequiredPasswordController;
use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\StudentController;
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

    Route::middleware('role:siswa')->group(function () {
        Route::get('siswa/wajib-ganti-password', [RequiredPasswordController::class, 'edit'])
            ->name('siswa.password.required');
        Route::put('siswa/wajib-ganti-password', [RequiredPasswordController::class, 'update'])
            ->name('siswa.password.update');

        Route::view('dashboard/siswa', 'dashboards.siswa')->name('dashboard.siswa');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard/admin', 'dashboard')
        ->middleware('role:admin')
        ->name('dashboard.admin');
    Route::view('dashboard/guru', 'dashboard')
        ->middleware('role:guru')
        ->name('dashboard.guru');

    Route::middleware('role:admin|guru')->group(function () {
        Route::get('siswa', [StudentController::class, 'index'])->name('students.index');
        Route::post('siswa/{student}/reset-password', [StudentController::class, 'resetPassword'])
            ->name('students.password.reset');
    });
});

require __DIR__.'/settings.php';
