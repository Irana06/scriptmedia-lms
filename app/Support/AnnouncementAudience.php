<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pengumuman mana yang boleh dilihat seorang pengguna, dan ke halaman mana ia
 * membacanya. Siswa dan orang tua hanya melihat pengumuman umum dan pengumuman
 * kelas (tahun ajaran aktif) milik siswa atau anaknya.
 */
class AnnouncementAudience
{
    /** @return Builder<Announcement> */
    public static function visibleTo(User $user): Builder
    {
        $query = Announcement::query();

        if ($user->hasAnyRole(['admin', 'guru'])) {
            return $query;
        }

        $studentIds = $user->hasRole('ortu')
            ? $user->children()->pluck('users.id')->all()
            : [$user->id];

        $classIds = SchoolClass::query()
            ->whereHas('academicYear', fn (Builder $year): Builder => $year->where('is_active', true))
            ->whereHas('students', fn (Builder $students): Builder => $students->whereIn('users.id', $studentIds))
            ->pluck('id')
            ->all();

        return $query->where(fn (Builder $nested): Builder => $nested
            ->where('target', 'all')
            ->orWhere(fn (Builder $class): Builder => $class->where('target', 'class')->whereIn('class_id', $classIds)));
    }

    public static function readingRoute(User $user): string
    {
        return match (true) {
            $user->hasAnyRole(['admin', 'guru']) => route('communications.index'),
            $user->hasRole('ortu') => route('dashboard.ortu'),
            default => route('dashboard.siswa'),
        };
    }
}
