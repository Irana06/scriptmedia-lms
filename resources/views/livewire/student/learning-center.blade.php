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
            <div class="relative"><x-theme.badge tone="orange">{{ $selected->schoolClass->name }}</x-theme.badge><h1 class="mt-4 text-2xl text-white sm:text-3xl">{{ $selected->subject->name }}</h1><p class="mt-2 text-sm text-white/70">{{ $selected->teacher->name }}</p></div>
        </section>

        <div class="overflow-x-auto rounded-2xl border border-line bg-white p-1.5">
            <div class="flex min-w-max gap-1">
                @foreach (['materials' => 'Materi', 'assignments' => 'Tugas', 'quizzes' => 'Kuis'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')" @class(['rounded-xl px-5 py-2.5 text-sm font-semibold', 'bg-navy text-white' => $tab === $key, 'text-ink-soft' => $tab !== $key])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if ($tab === 'materials')
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($selected->materials->sortBy('order') as $material)
                    <x-theme.card><div class="flex items-start gap-4"><span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-tosca-tint text-tosca"><flux:icon.book-open class="size-5" /></span><div><p class="text-sm font-semibold text-navy">{{ $material->order }}. {{ $material->title }}</p><p class="mt-2 text-sm leading-6 text-ink-soft">{{ $material->description }}</p></div></div><div class="mt-4 flex flex-wrap gap-2">@foreach($material->files as $file)<a href="{{ $file->type === 'link' ? $file->file_path : route('learning.files.material', $file) }}" target="_blank" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-offwhite px-3 text-sm font-semibold text-navy"><flux:icon.arrow-top-right-on-square class="size-4" />Buka {{ strtoupper($file->type) }}</a>@endforeach</div></x-theme.card>
                @empty
                    <x-theme.card class="sm:col-span-2 py-14 text-center"><p class="text-sm text-ink-soft">Belum ada materi.</p></x-theme.card>
                @endforelse
            </div>
        @elseif ($tab === 'assignments')
            <div class="space-y-4">
                @forelse ($selected->assignments as $assignment)
                    @php($submission = $assignment->submissions->first())
                    <x-theme.card>
                        <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg">{{ $assignment->title }}</h2><p class="mt-1 text-sm text-ink-soft">{{ $assignment->description }}</p></div><x-theme.badge :tone="now()->isAfter($assignment->deadline) ? 'neutral' : 'orange'">{{ now()->isAfter($assignment->deadline) ? 'Ditutup' : 'Batas '.$assignment->deadline->format('d M, H:i') }}</x-theme.badge></div>
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
