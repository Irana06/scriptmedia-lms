<div class="mx-auto max-w-3xl space-y-6">
    @if ($attempt->submitted_at)
        <x-theme.card class="py-12 text-center"><span class="mx-auto flex size-14 items-center justify-center rounded-full bg-tosca text-white"><flux:icon.check class="size-7" /></span><h1 class="mt-5 text-2xl">Kuis sudah dikumpulkan</h1><p class="mt-2 text-sm text-ink-soft">{{ $attempt->score !== null ? 'Skor kamu: '.$attempt->score : 'Jawaban esai sedang menunggu penilaian guru.' }}</p><x-theme.button href="{{ route('student.learning.index', ['classSubjectId' => $quiz->class_subject_id, 'tab' => 'quizzes']) }}" class="mt-6">Kembali ke ruang belajar</x-theme.button></x-theme.card>
    @else
        <div x-data="{ seconds: {{ $secondsRemaining }}, sent: false }" x-init="const timer = setInterval(() => { if (seconds > 0) seconds--; if (seconds <= 0 && !sent) { sent = true; clearInterval(timer); $wire.submitQuiz(); } }, 1000)" class="sticky top-20 z-20 flex items-center justify-between rounded-2xl border border-line bg-white/95 p-4 shadow-sm backdrop-blur">
            <div><p class="text-sm font-semibold text-navy">{{ $quiz->title }}</p><p class="mt-0.5 text-xs text-ink-soft">{{ $quiz->questions->count() }} soal</p></div>
            <div class="rounded-xl bg-orange/15 px-4 py-2 text-center"><p class="text-xs font-semibold text-ink-soft">Sisa waktu</p><p class="font-mono text-lg font-bold text-navy" x-text="String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0')"></p></div>
        </div>
        <form wire:submit="submitQuiz" class="space-y-4">
            @foreach ($quiz->questions as $question)
                <x-theme.card><div class="flex gap-3"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-navy text-sm font-semibold text-white">{{ $loop->iteration }}</span><div class="min-w-0 flex-1"><p class="font-semibold leading-7 text-navy">{{ $question->question }}</p>
                    @if ($question->type === 'mc')<div class="mt-4 space-y-2">@foreach($question->choices as $choice)<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-3 hover:border-tosca"><input wire:model="answers.{{ $question->id }}" type="radio" value="{{ $choice->id }}" class="mt-1"><span class="text-sm text-ink">{{ $choice->label }}</span></label>@endforeach</div>
                    @else<textarea wire:model="answers.{{ $question->id }}" rows="5" placeholder="Tulis jawabanmu..." class="mt-4 w-full rounded-xl border border-line px-3 py-2"></textarea>@endif
                    @error('answers.'.$question->id) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div></div></x-theme.card>
            @endforeach
            <x-theme.button type="submit" variant="orange" class="w-full" wire:confirm="Kumpulkan jawaban sekarang?">Kumpulkan jawaban</x-theme.button>
        </form>
    @endif
</div>
