<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_audits', function (Blueprint $table): void {
            $table->id();
            // final = nilai akhir rapor, assignment = nilai tugas, essay = nilai esai kuis.
            $table->string('kind', 20);
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item');
            $table->decimal('old_score', 5, 2)->nullable();
            $table->decimal('new_score', 5, 2);
            // Tetap tersimpan walau akun pengubahnya dihapus.
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_name');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_audits');
    }
};
