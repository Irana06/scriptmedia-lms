<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('nisn')->nullable()->index()->after('email');
            $table->string('nik')->nullable()->after('nisn');
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['nisn']);
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'nisn', 'nik', 'must_change_password']);
        });
    }
};
