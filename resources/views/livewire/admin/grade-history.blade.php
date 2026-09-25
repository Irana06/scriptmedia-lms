<div>
    <x-theme.section-header
        eyebrow="Jejak audit"
        title="Riwayat perubahan nilai"
        description="Setiap nilai yang diberikan atau diubah guru tercatat di sini — siapa, kapan, dari berapa ke berapa. Catatan tidak bisa diubah atau dihapus."
    />

    <x-theme.card :padding="false" class="mt-6 overflow-hidden">
        <div class="grid gap-3 border-b border-line p-5 sm:grid-cols-[1fr_220px] sm:px-6">
            <label class="block text-sm font-semibold text-navy">Cari siswa, guru, atau tugas
                <input wire:model.live.debounce.400ms="search" type="search" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" placeholder="Nama siswa atau guru" />
            </label>
            <label class="block text-sm font-semibold text-navy">Jenis nilai
                <select wire:model.live="kind" class="mt-2 min-h-11 w-full rounded-xl border border-line bg-white px-3 text-sm">
                    <option value="">Semua</option>
                    @foreach (\App\Models\GradeAudit::KINDS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </label>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-offwhite text-ink-soft"><tr><th class="px-5 py-3 sm:px-6">Waktu</th><th class="px-5 py-3">Siswa</th><th class="px-5 py-3">Nilai</th><th class="px-5 py-3">Perubahan</th><th class="px-5 py-3">Oleh</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($audits as $audit)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3.5 text-ink-soft sm:px-6">{{ $audit->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-5 py-3.5"><p class="font-semibold text-navy">{{ $audit->student->name }}</p><p class="text-xs text-ink-soft">{{ $audit->classSubject?->schoolClass?->name }}</p></td>
                            <td class="px-5 py-3.5"><p class="text-navy">{{ $audit->item }}</p><p class="text-xs text-ink-soft">{{ \App\Models\GradeAudit::KINDS[$audit->kind] ?? $audit->kind }}</p></td>
                            <td class="whitespace-nowrap px-5 py-3.5 tabular-nums">
                                @if ($audit->old_score === null)
                                    <span class="text-ink-soft">Baru</span> <strong class="text-navy">{{ \App\Support\Score::format($audit->new_score) }}</strong>
                                @else
                                    <span class="text-ink-soft line-through">{{ \App\Support\Score::format($audit->old_score) }}</span>
                                    <flux:icon.arrow-right class="mx-1 inline size-3.5 text-ink-soft" />
                                    <strong class="text-navy">{{ \App\Support\Score::format($audit->new_score) }}</strong>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $audit->changed_by_name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-ink-soft">Belum ada riwayat nilai{{ $search !== '' ? ' yang cocok' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($audits->hasPages())
            <div class="border-t border-line px-5 py-4 sm:px-6">{{ $audits->links() }}</div>
        @endif
    </x-theme.card>
</div>
