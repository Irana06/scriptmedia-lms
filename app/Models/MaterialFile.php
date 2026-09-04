<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property int $id @property int $material_id @property string $file_path @property string $type */
#[Fillable(['material_id', 'file_path', 'type'])]
class MaterialFile extends Model
{
    public function isExternal(): bool
    {
        return filter_var($this->file_path, FILTER_VALIDATE_URL) !== false;
    }

    public function youtubeVideoId(): ?string
    {
        if ($this->type !== 'link' || ! $this->isExternal()) {
            return null;
        }

        $host = strtolower((string) parse_url($this->file_path, PHP_URL_HOST));
        $path = trim((string) parse_url($this->file_path, PHP_URL_PATH), '/');
        $videoId = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = explode('/', $path)[0];
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if (str_starts_with($path, 'embed/') || str_starts_with($path, 'shorts/')) {
                $videoId = explode('/', $path)[1] ?? null;
            } else {
                parse_str((string) parse_url($this->file_path, PHP_URL_QUERY), $query);
                $videoId = is_string($query['v'] ?? null) ? $query['v'] : null;
            }
        }

        return is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1
            ? $videoId
            : null;
    }

    /** @return BelongsTo<Material, $this> */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
