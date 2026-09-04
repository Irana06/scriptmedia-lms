<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <x-theme.section-header eyebrow="Ruang guru" title="Kelola pembelajaran" description="Bagikan materi, tugas, dan kuis untuk kelas yang Anda ajarkan." />
        <label class="block min-w-72 text-sm font-semibold text-navy">
            Kelas & mata pelajaran
            <select wire:model.live="classSubjectId" class="mt-2 min-h-11 w-full rounded-xl border border-line bg-white px-3 text-sm">
                @forelse ($teachingAssignments as $item)
                    <option value="{{ $item->id }}">{{ $item->schoolClass->name }} · {{ $item->subject->name }}</option>
                @empty
                    <option value="">Belum ada kelas yang diampu</option>
                @endforelse
            </select>
        </label>
    </div>

    @if (session('learning_status'))
        <div class="rounded-2xl border border-tosca/25 bg-tosca-tint px-4 py-3 text-sm font-semibold text-navy">{{ session('learning_status') }}</div>
    @endif

    @if (! $selected)
        <x-theme.card class="py-14 text-center">
            <flux:icon.book-open class="mx-auto size-10 text-tosca" />
            <h2 class="mt-4 text-xl">Belum ada kelas yang dapat dikelola</h2>
            <p class="mt-2 text-sm text-ink-soft">Admin perlu menempatkan Anda sebagai guru pengampu terlebih dahulu.</p>
        </x-theme.card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-line bg-white p-1.5">
            <div class="flex min-w-max gap-1">
                @foreach (['materials' => 'Materi', 'assignments' => 'Tugas', 'quizzes' => 'Kuis & bank soal'] as $key => $label)
                    <button type="button" wire:click="$set('tab', '{{ $key }}')" @class([
                        'rounded-xl px-5 py-2.5 text-sm font-semibold transition',
                        'bg-navy text-white' => $tab === $key,
                        'text-ink-soft hover:bg-offwhite hover:text-navy' => $tab !== $key,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if ($tab === 'materials')
            <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <x-theme.card>
                    <h2 class="text-xl">Tambah materi</h2>
                    <form wire:submit="saveMaterial" class="mt-5 space-y-4">
                        <label class="block text-sm font-semibold text-navy">Judul<input wire:model="materialTitle" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                        @error('materialTitle') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <label class="block text-sm font-semibold text-navy">Deskripsi<textarea wire:model="materialDescription" rows="3" class="mt-2 w-full rounded-xl border border-line px-3 py-2"></textarea></label>
                        <label class="block text-sm font-semibold text-navy">Urutan<input wire:model="materialOrder" type="number" min="0" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                        <div class="rounded-2xl border border-line bg-offwhite p-4">
                            <label class="block text-sm font-semibold text-navy">Foto, video, atau PDF
                                <input wire:model="materialUploads" type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime,application/pdf" class="mt-2 block w-full text-sm" />
                            </label>
                            <p class="mt-2 text-xs leading-5 text-ink-soft">Bisa memilih beberapa file sekaligus. Maksimal 8 file, masing-masing 50 MB.</p>
                            @error('materialUploads') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('materialUploads.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="block text-sm font-semibold text-navy">Tautan materi atau YouTube
                            <textarea wire:model="materialLinks" rows="3" placeholder="Satu tautan per baris&#10;https://youtube.com/watch?v=..." class="mt-2 w-full rounded-xl border border-line px-3 py-2"></textarea>
                        </label>
                        <p class="-mt-2 text-xs leading-5 text-ink-soft">Lampiran file dan tautan boleh digunakan bersamaan.</p>
                        @error('materialLinks') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <x-theme.button type="submit" class="w-full" wire:loading.attr="disabled">Simpan materi</x-theme.button>
                    </form>
                </x-theme.card>
                <div class="space-y-4">
                    @forelse ($selected->materials->sortBy('order') as $material)
                        <x-theme.card class="flex gap-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-tosca-tint text-tosca"><flux:icon.book-open class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div><p class="text-sm font-semibold text-navy">{{ $material->order }}. {{ $material->title }}</p><p class="mt-1 text-sm leading-6 text-ink-soft">{{ $material->description }}</p></div>
                                    <button wire:click="deleteMaterial({{ $material->id }})" wire:confirm="Hapus materi ini?" class="text-sm text-red-600">Hapus</button>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($material->files as $file)
                                        <a href="{{ $file->isExternal() ? $file->file_path : route('learning.files.material', $file) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-full bg-offwhite px-3 py-1.5 text-sm font-semibold text-navy"><flux:icon.arrow-top-right-on-square class="size-4" />{{ $file->youtubeVideoId() ? 'YOUTUBE' : strtoupper($file->type) }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </x-theme.card>
                    @empty
                        <x-theme.card class="py-14 text-center"><p class="text-sm text-ink-soft">Belum ada materi untuk kelas ini.</p></x-theme.card>
                    @endforelse
                </div>
            </div>
        @elseif ($tab === 'assignments')
            <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <x-theme.card>
                    <h2 class="text-xl">Buat tugas</h2>
                    <form wire:submit="saveAssignment" class="mt-5 space-y-4">
                        <label class="block text-sm font-semibold text-navy">Judul<input wire:model="assignmentTitle" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                        <label class="block text-sm font-semibold text-navy">Instruksi<textarea wire:model="assignmentDescription" rows="4" class="mt-2 w-full rounded-xl border border-line px-3 py-2"></textarea></label>
                        <label class="block text-sm font-semibold text-navy">Deadline<input wire:model="assignmentDeadline" type="datetime-local" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                        @error('assignmentDeadline') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <x-theme.button type="submit" class="w-full">Terbitkan tugas</x-theme.button>
                    </form>
                </x-theme.card>
                <div class="space-y-4">
                    @forelse ($selected->assignments as $assignment)
                        <x-theme.card>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div><h3 class="text-lg">{{ $assignment->title }}</h3><p class="mt-1 text-sm text-ink-soft">Batas {{ $assignment->deadline->format('d M Y, H:i') }}</p></div>
                                <div class="flex items-center gap-3"><x-theme.badge tone="orange">{{ $assignment->submissions->count() }} kiriman</x-theme.badge><button wire:click="deleteAssignment({{ $assignment->id }})" wire:confirm="Hapus tugas dan seluruh kiriman?" class="text-sm text-red-600">Hapus</button></div>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-ink-soft">{{ $assignment->description }}</p>
                            @if ($assignment->submissions->isNotEmpty())
                                <div class="mt-5 overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b border-line text-ink-soft"><tr><th class="py-2">Siswa</th><th class="py-2">Dikirim</th><th class="py-2">Nilai</th><th class="py-2 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-line">
                                    @foreach ($assignment->submissions as $submission)
                                        <tr><td class="py-3 font-semibold text-navy">{{ $submission->student->name }}</td><td class="py-3">{{ $submission->submitted_at->format('d M, H:i') }}</td><td class="py-3">{{ $submission->grade?->score ?? '—' }}</td><td class="py-3 text-right"><a href="{{ route('learning.files.submission', $submission) }}" class="font-semibold text-tosca">Unduh</a><button wire:click="editGrade({{ $submission->id }})" class="ml-3 font-semibold text-navy">Nilai</button></td></tr>
                                    @endforeach
                                </tbody></table></div>
                            @endif
                        </x-theme.card>
                    @empty
                        <x-theme.card class="py-14 text-center"><p class="text-sm text-ink-soft">Belum ada tugas untuk kelas ini.</p></x-theme.card>
                    @endforelse
                    @if ($gradingSubmissionId)
                        <x-theme.card class="border-tosca">
                            <h3 class="font-semibold text-navy">Nilai kiriman siswa</h3>
                            <form wire:submit="saveGrade" class="mt-4 grid gap-4 sm:grid-cols-[140px_1fr_auto] sm:items-end">
                                <label class="text-sm font-semibold text-navy">Nilai<input wire:model="gradeScore" type="number" min="0" max="100" step="0.01" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                                <label class="text-sm font-semibold text-navy">Umpan balik<input wire:model="gradeFeedback" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                                <x-theme.button type="submit">Simpan nilai</x-theme.button>
                            </form>
                        </x-theme.card>
                    @endif
                </div>
            </div>
        @else
            <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <x-theme.card>
                    <h2 class="text-xl">Buat kuis</h2>
                    <form wire:submit="saveQuiz" class="mt-5 space-y-4">
                        <label class="block text-sm font-semibold text-navy">Judul<input wire:model="quizTitle" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                        <label class="block text-sm font-semibold text-navy">Durasi (menit)<input wire:model="quizDuration" type="number" min="1" max="240" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                        <label class="block text-sm font-semibold text-navy">Dibuka<input wire:model="quizOpenAt" type="datetime-local" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                        <label class="block text-sm font-semibold text-navy">Ditutup<input wire:model="quizCloseAt" type="datetime-local" class="mt-2 min-h-11 w-full rounded-xl border border-line px-3" /></label>
                        @error('quizCloseAt') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <x-theme.button type="submit" class="w-full">Buat kuis</x-theme.button>
                    </form>
                </x-theme.card>
                <div class="space-y-5">
                    @forelse ($selected->quizzes as $quiz)
                        <x-theme.card>
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="text-lg">{{ $quiz->title }}</h3><p class="mt-1 text-sm text-ink-soft">{{ $quiz->duration_minutes }} menit · {{ $quiz->open_at->format('d M H:i') }}–{{ $quiz->close_at->format('d M H:i') }}</p></div><button wire:click="deleteQuiz({{ $quiz->id }})" wire:confirm="Hapus kuis ini?" class="text-sm text-red-600">Hapus</button></div>
                            <div class="mt-5 space-y-3">
                                @foreach ($quiz->questions as $question)
                                    <div class="rounded-2xl bg-offwhite p-4"><div class="flex justify-between gap-3"><p class="text-sm font-semibold text-navy">{{ $loop->iteration }}. {{ $question->question }}</p><button wire:click="deleteQuestion({{ $question->id }})" class="text-xs text-red-600">Hapus</button></div><x-theme.badge tone="neutral" class="mt-2">{{ $question->type === 'mc' ? 'Pilihan ganda' : 'Esai' }}</x-theme.badge></div>
                                @endforeach
                            </div>
                            <details class="mt-5 rounded-2xl border border-line p-4" @if($quiz->questions->isEmpty()) open @endif>
                                <summary class="cursor-pointer text-sm font-semibold text-navy">Tambah soal</summary>
                                <div class="mt-4 space-y-3">
                                    <textarea wire:model="questionText" rows="2" placeholder="Tulis pertanyaan" class="w-full rounded-xl border border-line px-3 py-2"></textarea>
                                    <select wire:model.live="questionType" class="min-h-11 w-full rounded-xl border border-line px-3"><option value="mc">Pilihan ganda</option><option value="essay">Esai</option></select>
                                    @if ($questionType === 'mc')
                                        @foreach ($choices as $index => $choice)
                                            <label class="flex items-center gap-3"><input wire:model="correctChoice" type="radio" value="{{ $index }}" /><input wire:model="choices.{{ $index }}" placeholder="Pilihan {{ chr(65 + $index) }}" class="min-h-11 flex-1 rounded-xl border border-line px-3" /></label>
                                        @endforeach
                                    @endif
                                    @error('questionText') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                    @error('choices.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                    <x-theme.button type="button" wire:click="addQuestion({{ $quiz->id }})" variant="outline">Simpan soal</x-theme.button>
                                </div>
                            </details>
                            @if ($quiz->attempts->isNotEmpty())
                                <div class="mt-5 border-t border-line pt-4"><h4 class="text-sm font-semibold text-navy">Hasil siswa</h4><div class="mt-3 space-y-3">
                                    @foreach ($quiz->attempts as $attempt)
                                        <div class="rounded-xl bg-offwhite p-3"><div class="flex justify-between gap-3"><span class="text-sm font-semibold text-navy">{{ $attempt->student->name }}</span><span class="text-sm">{{ $attempt->score !== null ? $attempt->score : 'Menunggu nilai esai' }}</span></div>
                                            @foreach ($attempt->answers->where('question.type', 'essay') as $answer)
                                                <div class="mt-3 border-t border-line pt-3"><p class="text-sm text-ink-soft">{{ $answer->question->question }}</p><p class="mt-1 text-sm text-navy">{{ $answer->answer ?: 'Tidak dijawab' }}</p>@if($answer->score === null)<div class="mt-2 flex gap-2"><input wire:model="essayScore" type="number" min="0" max="1" step="0.1" placeholder="0–1" class="min-h-9 w-24 rounded-lg border border-line px-2"><button wire:click="gradeEssay({{ $answer->id }})" class="text-sm font-semibold text-tosca">Simpan</button></div>@endif</div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div></div>
                            @endif
                        </x-theme.card>
                    @empty
                        <x-theme.card class="py-14 text-center"><p class="text-sm text-ink-soft">Belum ada kuis untuk kelas ini.</p></x-theme.card>
                    @endforelse
                </div>
            </div>
        @endif
    @endif
</div>
