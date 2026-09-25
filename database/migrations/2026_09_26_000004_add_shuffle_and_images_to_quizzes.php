<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            // Kuis lama tetap berurutan seperti saat dibuat; kuis baru diacak (default di form).
            $table->boolean('shuffle')->default(false)->after('close_at');
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('question');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropColumn('shuffle');
        });
    }
};
