<?php

namespace App\Livewire\Admin;

use App\Models\GradeAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.guru-admin')]
#[Title('Riwayat Nilai')]
class GradeHistory extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $kind = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedKind(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $audits = GradeAudit::query()
            ->with('student:id,name,nisn,nis', 'classSubject.schoolClass:id,name')
            ->when(trim($this->search) !== '', fn (Builder $query): Builder => $query->where(fn (Builder $nested): Builder => $nested
                ->whereHas('student', fn (Builder $student): Builder => $student->where('name', 'like', '%'.trim($this->search).'%'))
                ->orWhere('changed_by_name', 'like', '%'.trim($this->search).'%')
                ->orWhere('item', 'like', '%'.trim($this->search).'%')))
            ->when(array_key_exists($this->kind, GradeAudit::KINDS), fn (Builder $query): Builder => $query->where('kind', $this->kind))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25);

        return view('livewire.admin.grade-history', ['audits' => $audits]);
    }
}
