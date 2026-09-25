<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_profiles', function (Blueprint $table): void {
            $table->string('address')->nullable()->after('npsn');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('phone', 30)->nullable()->after('city');
            $table->string('email')->nullable()->after('phone');
            $table->string('principal_name')->nullable()->after('email');
            $table->string('principal_nip', 30)->nullable()->after('principal_name');
            $table->string('logo_path')->nullable()->after('principal_nip');
        });
    }

    public function down(): void
    {
        Schema::table('school_profiles', function (Blueprint $table): void {
            $table->dropColumn(['address', 'city', 'phone', 'email', 'principal_name', 'principal_nip', 'logo_path']);
        });
    }
};
