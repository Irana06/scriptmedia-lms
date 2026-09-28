<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property string $target
 * @property int|null $class_id
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'body', 'target', 'class_id', 'created_by'])]
class Announcement extends Model
{
    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<User, $this> */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')->withPivot('read_at');
    }

    public function markReadBy(User $user): void
    {
        $this->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);
    }

    /**
     * Pengumuman yang belum dibaca $user. Yang terbit sebelum batas "semua sudah
     * dibaca" (tombol Tandai semua dibaca, atau saat akun dibuat) dianggap sudah
     * dibaca tanpa perlu satu baris per pengumuman.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnreadBy(Builder $query, User $user): Builder
    {
        $readUpTo = $user->announcements_seen_at ?? $user->created_at;

        return $query
            ->when($readUpTo, fn (Builder $nested) => $nested->where('announcements.created_at', '>', $readUpTo))
            ->whereDoesntHave('readers', fn (Builder $readers) => $readers->whereKey($user->id));
    }
}
