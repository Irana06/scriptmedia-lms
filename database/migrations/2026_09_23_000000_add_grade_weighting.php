<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            // Default "tugas": baris lama semuanya tugas biasa sebelum kategori ada.
            $table->string('category', 10)->default('tugas')->after('title');
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->string('category', 10)->default('kuis')->after('title');
        });

        Schema::create('grade_weight_settings', function (Blueprint $table): void {
            $table->id();
            $table->decimal('tugas', 5, 2);
            $table->decimal('kuis', 5, 2);
            $table->decimal('uts', 5, 2);
            $table->decimal('uas', 5, 2);
            $table->timestamps();
        });

        // Baris tunggal (id 1). Nilai default umum di sekolah: tugas 20%, kuis 20%,
        // UTS 25%, UAS 35%. Admin bisa mengubahnya di Struktur Akademik > Bobot Nilai.
        DB::table('grade_weight_settings')->insert([
            'id' => 1,
            'tugas' => 20,
            'kuis' => 20,
            'uts' => 25,
            'uas' => 35,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_weight_settings');

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropColumn('category');
        });

        Schema::table('assignments', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }
};
