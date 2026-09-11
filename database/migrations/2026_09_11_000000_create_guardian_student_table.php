<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Orang tua jarang dihubungi lewat email. Nomor HP dipakai admin untuk
            // memastikan pendaftar benar wali siswa sebelum menyetujui tautan.
            $table->string('phone', 20)->nullable()->after('email');
        });

        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship', 10)->nullable();

            // Orang tua boleh daftar sendiri, tetapi tautan ke anak baru berlaku
            // setelah admin menyetujuinya. NISN tidak rahasia, jadi mengetahuinya
            // saja tidak cukup untuk melihat jadwal dan aktivitas seorang anak.
            $table->string('status', 10)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['guardian_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }
};
