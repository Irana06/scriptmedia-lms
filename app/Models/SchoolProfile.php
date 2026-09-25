<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Profil sekolah: satu baris (id 1). Dipakai untuk branding (logo, nama di
 * header dan halaman masuk) dan kop rapor. Diisi lewat `sekolah:setup` atau
 * halaman Profil Sekolah admin.
 *
 * @property int $id
 * @property string $name
 * @property string|null $npsn
 * @property string|null $address
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $principal_name
 * @property string|null $principal_nip
 * @property string|null $logo_path
 */
#[Fillable(['name', 'npsn', 'address', 'city', 'phone', 'email', 'principal_name', 'principal_nip', 'logo_path'])]
class SchoolProfile extends Model
{
    public const PLACEHOLDER_NAME = 'Sekolah Baru';

    public static function current(): self
    {
        // Dipanggil di banyak layout dalam satu request; cukup satu query.
        return once(fn (): self => self::query()->firstOrCreate(['id' => 1], ['name' => self::PLACEHOLDER_NAME]));
    }

    public function isConfigured(): bool
    {
        return $this->name !== self::PLACEHOLDER_NAME;
    }

    /** Nama untuk branding: nama sekolah bila sudah diisi, nama produk bila belum. */
    public function brandName(): string
    {
        return $this->isConfigured() ? $this->name : 'RuangKelas';
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path !== null ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * Logo sebagai data URI untuk PDF: DomPDF tidak mengambil gambar lewat URL,
     * dan path file lokal bergantung pada konfigurasi chroot server.
     */
    public function logoDataUri(): ?string
    {
        if ($this->logo_path === null || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($this->logo_path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) Storage::disk('public')->get($this->logo_path));
    }
}
