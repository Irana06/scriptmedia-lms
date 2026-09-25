<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            // Pengumuman disiarkan ke banyak orang sekaligus, jadi tidak disalin per
            // pengguna ke tabel notifications; cukup dicatat kapan terakhir dilihat.
            $table->timestamp('announcements_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('announcements_seen_at');
        });

        Schema::dropIfExists('notifications');
    }
};
