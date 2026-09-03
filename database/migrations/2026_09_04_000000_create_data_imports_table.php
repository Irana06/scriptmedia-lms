<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 1)->nullable()->after('nik');
            $table->string('nip')->nullable()->unique()->after('gender');
        });

        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);
            $table->string('file_path');
            $table->foreignId('imported_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 10)->default('processing');
            $table->json('failures')->nullable();
            $table->string('result_file_path')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nip']);
            $table->dropColumn(['gender', 'nip']);
        });
    }
};
