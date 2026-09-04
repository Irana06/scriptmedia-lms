<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <x-theme.section-header eyebrow="Ruang belajar" title="Materi, tugas & kuis" description="Pilih mata pelajaran untuk melihat aktivitas kelasmu." />
        <label class="block min-w-64 text-sm font-semibold text-navy">Mata pelajaran
            <select wire:model.live="classSubjectId" class="mt-2 min-h-11 w-full rounded-xl border border-line bg-white px-3">
                @forelse ($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->subject->name }} · {{ $subject->schoolClass->name }}</option>
                @empty
                    <option value="">Belum ada mata pelajaran</option>
                @endforelse
            </select>
        </label>
    </div>

    @if (session('learning_status'))
        <div class="rounded-2xl border border-tosca/25 bg-tosca-tint px-4 py-3 text-sm font-semibold text-navy">{{ session('learning_status') }}</div>
    @endif

    @if (! $selected)
        <x-theme.card class="py-14 text-center"><flux:icon.book-open class="mx-auto size-10 text-tosca" /><p class="mt-4 text-sm text-ink-soft">Kamu belum ditempatkan di kelas dengan mata pelajaran aktif.</p></x-theme.card>
    @else
        <section class="relative overflow-hidden rounded-card bg-gradient-to-br from-navy to-navy-mid p-6 text-white sm:p-8">
            <div class="absolute -right-12 -top-12 size-40 rounded-full border-[24px] border-white/5"></div>
            <div class="relative"><x-theme.badge tone="orange">{{ $selected->schoolClass->name }}</x-theme.badge><h2 class="mt-4 text-2xl text-white sm:text-3xl">{{ $selected->subject->name }}</h2><p class="mt-2 text-sm text-white/70">{{ $selected->teacher->name }}</p></div>
        </section>

        <div class="overflow-x-auto rounded-2xl border border-line bg-white p-1.5">
            <div class="flex min-w-max gap-1">
                @foreach (['materials' => 'Materi', 'assignments' => 'Tugas', 'quizzes' => 'Kuis'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')" @class(['rounded-xl px-5 py-2.5 text-sm font-semibold', 'bg-navy text-white' => $tab === $key, 'text-ink-soft' => $tab !== $key])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if ($tab === 'materials')
            <div class="space-y-4">
                @forelse ($selected->materials->sortBy('order') as $material)
                    <details class="group rounded-card border border-line bg-white shadow-[0_16px_40px_-28px_rgba(11,37,69,0.42)] transition open:border-tosca/40">
                        <summary class="flex cursor-pointer list-none items-start gap-4 p-5 sm:p-6"><span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-tosca-tint text-tosca"><flux:icon.book-open class="size-5" /></span><div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-navy">{{ $material->order }}. {{ $material->title }}</p><p class="mt-1 text-xs text-ink-soft">{{ $material->files->count() }} lampiran · diterbitkan {{ $material->created_at?->translatedFormat('d M Y') }}</p></div><span class="text-xs font-semibold text-tosca-ink group-open:hidden">Buka detail ↓</span><span class="hidden text-xs font-semibold text-tosca-ink group-open:inline">Tutup ↑</span></div><p class="mt-2 line-clamp-2 text-sm leading-6 text-ink-soft">{{ $material->description ?: 'Guru belum menambahkan ringkasan materi.' }}</p></div></summary>
                        <div class="border-t border-line px-5 pb-5 pt-4 sm:px-6 sm:pb-6"><div class="grid gap-3 text-sm sm:grid-cols-2"><div class="rounded-xl bg-offwhite p-3"><p class="text-xs text-ink-soft">Pengajar</p><p class="mt-1 font-semibold text-navy">{{ $selected->teacher->name }}</p></div><div class="rounded-xl bg-offwhite p-3"><p class="text-xs text-ink-soft">Mata pelajaran</p><p class="mt-1 font-semibold text-navy">{{ $selected->subject->name }} · {{ $selected->schoolClass->name }}</p></div></div><div class="mt-4"><p class="text-sm font-semibold text-navy">Deskripsi materi</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-soft">{{ $material->description ?: 'Belum ada deskripsi tambahan.' }}</p></div><div class="mt-5"><p class="mb-3 text-sm font-semibold text-navy">Lampiran & media</p><x-theme.material-attachments :files="$material->files" /></div></div>
                    </details>
                @empty
                    <x-theme.card class="py-14 text-center"><p class="text-sm text-ink-soft">Belum ada materi.</p></x-theme.card>
                @endforelse
            </div>
        @elseif ($tab === 'assignments')
            <div class="space-y-4">
                @forelse ($selected->assignments as $assignment)
                    @php($submission = $assignment->submissions->first())
                    <x-theme.card id="assignment-{{ $assignment->id }}" :class="$assignmentId === (string) $assignment->id ? 'border-orange ring-2 ring-orange/20' : ''">
                        <div class="flex flex-wrap items-start justify-between gap-3"><div>@if($assignmentId === (string) $assignment->id)<p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-orange-ink">Tugas yang dipilih</p>@endif<h2 class="text-lg">{{ $assignment->title }}</h2><p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-soft">{{ $assignment->description ?: 'Guru belum menambahkan instruksi khusus.' }}</p></div><x-theme.badge :tone="now()->isAfter($assignment->deadline) ? 'neutral' : 'orange'">{{ now()->isAfter($assignment->deadline) ? 'Ditutup' : 'Batas '.$assignment->deadline->format('d M, H:i') }}</x-theme.badge></div>
                        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3"><div class="rounded-xl bg-offwhite p-3"><p class="text-xs text-ink-soft">Mata pelajaran</p><p class="mt-1 font-semibold text-navy">{{ $selected->subject->name }}</p></div><div class="rounded-xl bg-offwhite p-3"><p class="text-xs text-ink-soft">Batas pengumpulan</p><p class="mt-1 font-semibold text-navy">{{ $assignment->deadline->translatedFormat('d M Y, H:i') }}</p></div><div class="rounded-xl bg-offwhite p-3"><p class="text-xs text-ink-soft">Status</p><p class="mt-1 font-semibold text-navy">{{ $submission ? ($submission->grade ? 'Sudah dinilai' : 'Sudah dikumpulkan') : (now()->isAfter($assignment->deadline) ? 'Terlambat' : 'Belum dikumpulkan') }}</p></div></div>
                        @if ($submission)
                            <div class="mt-4 rounded-xl bg-tosca-tint p-4 text-sm"><p class="font-semibold text-navy">Sudah dikumpulkan {{ $submission->submitted_at->format('d M Y, H:i') }}</p><p class="mt-1 text-ink-soft">Nilai: {{ $submission->grade?->score ?? 'Belum dinilai' }}</p>@if($submission->grade?->feedback)<p class="mt-1 text-ink-soft">{{ $submission->grade->feedback }}</p>@endif</div>
                        @endif
                        <form wire:submit="submitAssignment({{ $assignment->id }})" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            <label class="flex-1 text-sm font-semibold text-navy">{{ $submission ? 'Ganti jawaban' : 'Unggah jawaban' }}<input wire:model="submissionFile" type="file" class="mt-2 block w-full text-sm" @disabled(now()->isAfter($assignment->deadline)) /></label>
                            <x-theme.button type="submit" :disabled="now()->isAfter($assignment->deadline)" wire:loading.attr="disabled" wire:target="submitAssignment,submissionFile"><span wire:loading.remove wire:target="submitAssignment">{{ $submission ? 'Kirim ulang' : 'Kumpulkan' }}</span><span wire:loading wire:target="submitAssignment">Mengirim...</span></x-theme.button>
                        </form>
                        @error('submissionFile') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </x-theme.card>
                @empty
                    <x-theme.card class="py-14 text-center"><p class="text-sm text-ink-soft">Belum ada tugas.</p></x-theme.card>
                @endforelse
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($selected->quizzes as $quiz)
                    @php($attempt = $quiz->attempts->first())
                    @php($available = now()->between($quiz->open_at, $quiz->close_at) && $quiz->questions->isNotEmpty())
                    <x-theme.card><div class="flex items-start justify-between gap-3"><span class="flex size-11 items-center justify-center rounded-xl bg-orange/15 text-orange"><flux:icon.clock class="size-5" /></span><x-theme.badge :tone="$available ? 'tosca' : 'neutral'">{{ $available ? 'Tersedia' : (now()->isBefore($quiz->open_at) ? 'Belum dibuka' : 'Ditutup') }}</x-theme.badge></div><h2 class="mt-4 text-lg">{{ $quiz->title }}</h2><p class="mt-1 text-sm text-ink-soft">{{ $quiz->questions->count() }} soal · {{ $quiz->duration_minutes }} menit</p>
                        @if ($attempt?->submitted_at)<div class="mt-4 rounded-xl bg-tosca-tint p-3 text-sm font-semibold text-navy">{{ $attempt->score !== null ? 'Skor '.$attempt->score : 'Menunggu penilaian esai' }}</div>@elseif($available)<x-theme.button href="{{ route('student.quizzes.play', $quiz) }}" class="mt-4">{{ $attempt ? 'Lanjutkan kuis' : 'Mulai kuis' }}</x-theme.button>@endif
                    </x-theme.card>
                @empty
                    <x-theme.card class="sm:col-span-2 py-14 text-center"><p class="text-sm text-ink-soft">Belum ada kuis.</p></x-theme.card>
                @endforelse
            </div>
        @endif
    @endif
</div>
