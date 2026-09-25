<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Butuh cron `* * * * * php artisan schedule:run` di server (lihat DEPLOY.md).
Schedule::command('sekolah:backup --simpan=14')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('sekolah:backup --dengan-berkas --simpan=4')->weeklyOn(0, '02:30')->withoutOverlapping();
