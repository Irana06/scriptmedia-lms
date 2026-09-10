<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Nomor induk internal sekolah. Dipakai sebagai identitas masuk siswa
            // yang NISN-nya belum terbit — umum terjadi di awal tahun ajaran.
            $table->string('nis')->nullable()->index()->after('nisn');

            // Guru yayasan, honorer, dan swasta umumnya tidak memiliki NIP
            // karena NIP hanya diberikan kepada ASN. NUPTK menggantikannya.
            $table->string('nuptk')->nullable()->unique()->after('nip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['nis']);
            $table->dropUnique(['nuptk']);
            $table->dropColumn(['nis', 'nuptk']);
        });
    }
};
