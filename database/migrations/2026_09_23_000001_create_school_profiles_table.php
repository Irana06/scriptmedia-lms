<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // NPSN: nomor identitas sekolah di Dapodik/EMIS, dipakai sekolah swasta
            // maupun negeri. Opsional karena beberapa sekolah baru belum punya.
            $table->string('npsn', 20)->nullable();
            $table->timestamps();
        });

        // Baris tunggal (id 1), diisi ulang lewat `php artisan sekolah:setup` atau
        // langsung oleh admin. Nama sementara supaya tampilan tidak kosong sebelum
        // wizard dijalankan.
        DB::table('school_profiles')->insert([
            'id' => 1,
            'name' => 'Sekolah Baru',
            'npsn' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('school_profiles');
    }
};
