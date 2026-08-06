<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('dashboard/siswa', 'dashboards.siswa')->name('dashboard.siswa');
});

require __DIR__.'/settings.php';
