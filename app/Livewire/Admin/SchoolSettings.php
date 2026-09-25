<?php

namespace App\Livewire\Admin;

use App\Models\SchoolProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.guru-admin')]
#[Title('Profil Sekolah')]
class SchoolSettings extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $npsn = '';

    public string $address = '';

    public string $city = '';

    public string $phone = '';

    public string $email = '';

    public string $principalName = '';

    public string $principalNip = '';

    public ?TemporaryUploadedFile $logo = null;

    public function mount(): void
    {
        $profile = SchoolProfile::current();
        $this->name = $profile->isConfigured() ? $profile->name : '';
        $this->npsn = (string) $profile->npsn;
        $this->address = (string) $profile->address;
        $this->city = (string) $profile->city;
        $this->phone = (string) $profile->phone;
        $this->email = (string) $profile->email;
        $this->principalName = (string) $profile->principal_name;
        $this->principalNip = (string) $profile->principal_nip;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'principalName' => ['nullable', 'string', 'max:255'],
            'principalNip' => ['nullable', 'string', 'max:30'],
            // PNG/JPG saja: DomPDF tidak andal merender SVG maupun WebP di kop rapor.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
        ], [], [
            'name' => 'nama sekolah',
            'principalName' => 'nama kepala sekolah',
            'principalNip' => 'NIP kepala sekolah',
        ]);

        $profile = SchoolProfile::current();
        $data = [
            'name' => $validated['name'],
            'npsn' => $this->nullIfEmpty($validated['npsn']),
            'address' => $this->nullIfEmpty($validated['address']),
            'city' => $this->nullIfEmpty($validated['city']),
            'phone' => $this->nullIfEmpty($validated['phone']),
            'email' => $this->nullIfEmpty($validated['email']),
            'principal_name' => $this->nullIfEmpty($validated['principalName']),
            'principal_nip' => $this->nullIfEmpty($validated['principalNip']),
        ];

        if ($this->logo !== null) {
            $oldLogo = $profile->logo_path;
            $data['logo_path'] = $this->logo->store('branding', 'public');

            if ($oldLogo !== null) {
                Storage::disk('public')->delete($oldLogo);
            }
        }

        $profile->update($data);
        $this->reset('logo');

        session()->flash('school_status', 'Profil sekolah tersimpan.');
    }

    public function removeLogo(): void
    {
        $profile = SchoolProfile::current();

        if ($profile->logo_path !== null) {
            Storage::disk('public')->delete($profile->logo_path);
            $profile->update(['logo_path' => null]);
        }

        session()->flash('school_status', 'Logo dihapus.');
    }

    public function render(): View
    {
        return view('livewire.admin.school-settings', ['profile' => SchoolProfile::current()]);
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
