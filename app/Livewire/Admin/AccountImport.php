<?php

namespace App\Livewire\Admin;

use App\Models\DataImport;
use App\Services\AccountImportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('components.layouts.guru-admin')]
#[Title('Import Akun')]
class AccountImport extends Component
{
    use WithFileUploads;

    public string $type = 'siswa';

    public ?TemporaryUploadedFile $file = null;

    public ?int $latestImportId = null;

    public function import(AccountImportService $service): void
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(['siswa', 'guru'])],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        /** @var TemporaryUploadedFile $file */
        $file = $validated['file'];
        $path = $file->store('imports/source', 'local');

        $dataImport = DataImport::query()->create([
            'type' => $validated['type'],
            'file_path' => $path,
            'imported_by' => Auth::id(),
            'status' => 'processing',
        ]);

        try {
            $service->process($dataImport);
            $this->latestImportId = $dataImport->id;
            $this->reset('file');
            $this->dispatch('import-finished');
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('file', 'File tidak dapat dibaca. Pastikan format dan nama kolom sesuai template.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.account-import', [
            'imports' => DataImport::query()->with('importer')->latest()->limit(15)->get(),
            'latestImport' => $this->latestImportId ? DataImport::query()->find($this->latestImportId) : null,
        ]);
    }
}
