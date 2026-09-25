<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            // KKM/KKTP per mata pelajaran; 75 adalah nilai yang paling umum dipakai.
            $table->unsignedTinyInteger('kkm')->default(75)->after('code');
        });

        Schema::table('grades', function (Blueprint $table): void {
            // Deskripsi capaian kompetensi ala rapor Kurikulum Merdeka.
            $table->text('description')->nullable()->after('predikat');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table): void {
            $table->dropColumn('description');
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropColumn('kkm');
        });
    }
};
