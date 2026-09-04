<div class="space-y-6">
    <x-theme.section-header eyebrow="Tetap terarah" title="Pusat aktivitas" description="Prioritas belajar, riwayat pengerjaan, dan informasi sekolah dalam satu halaman." />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-theme.card>
            <p class="text-sm text-ink-soft">Perlu dikerjakan</p>
            <p class="mt-2 text-3xl font-semibold text-navy">{{ $todoCount }}</p>
            <p class="mt-2 text-xs text-ink-soft">Tugas dan kuis yang belum selesai</p>
        </x-theme.card>
        <x-theme.card>
            <p class="text-sm text-ink-soft">Sudah selesai</p>
            <p class="mt-2 text-3xl font-semibold text-navy">{{ $completedActivities }}<span class="text-base text-ink-soft">/{{ $totalActivities }}</span></p>
            <p class="mt-2 text-xs text-ink-soft">Aktivitas pada kelas aktif</p>
        </x-theme.card>
        <x-theme.card class="border-tosca/30 bg-tosca-tint/40">
            <div class="flex items-center justify-between"><p class="text-sm text-ink-soft">Progres belajar</p><strong class="text-navy">{{ $progress }}%</strong></div>
            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-white"><div class="h-full rounded-full bg-tosca transition-all" style="width: {{ $progress }}%"></div></div>
            <p class="mt-3 text-xs text-ink-soft">Berdasarkan tugas dan kuis</p>
        </x-theme.card>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-line bg-white p-1.5">
        <div class="flex min-w-max gap-1">
            @foreach(['todo' => 'Perlu dikerjakan', 'history' => 'Riwayat', 'info' => 'Informasi'] as $value => $label)
                <button wire:click="$set('tab', '{{ $value }}')" @class(['rounded-xl px-5 py-2.5 text-sm font-semibold', 'bg-navy text-white' => $tab === $value, 'text-ink-soft hover:text-navy' => $tab !== $value])>{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @if($tab === 'todo')
        <div class="space-y-4">
            @foreach($overdueAssignments as $assignment)
                <x-theme.card class="border-red-200">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div><div class="flex flex-wrap items-center gap-2"><x-theme.badge tone="danger">Terlambat</x-theme.badge><span class="text-xs text-ink-soft">{{ $assignment->classSubject->subject->name }} · {{ $assignment->classSubject->teacher->name }}</span></div><h2 class="mt-2 text-lg">{{ $assignment->title }}</h2><p class="mt-1 line-clamp-2 text-sm leading-6 text-ink-soft">{{ $assignment->description ?: 'Belum ada instruksi tambahan.' }}</p><p class="mt-2 text-xs font-semibold text-red-700">Ditutup {{ $assignment->deadline->translatedFormat('d M Y, H:i') }}</p></div>
                        <x-theme.button href="{{ route('student.learning.index', ['tab' => 'assignments', 'classSubjectId' => $assignment->class_subject_id, 'assignmentId' => $assignment->id]) }}" variant="outline">Lihat detail</x-theme.button>
                    </div>
                </x-theme.card>
            @endforeach
            @foreach($pendingAssignments as $assignment)
                <x-theme.card>
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div><div class="flex flex-wrap items-center gap-2"><x-theme.badge tone="orange">Tugas</x-theme.badge><span class="text-xs text-ink-soft">{{ $assignment->classSubject->subject->name }} · {{ $assignment->classSubject->teacher->name }}</span></div><h2 class="mt-2 text-lg">{{ $assignment->title }}</h2><p class="mt-1 line-clamp-2 text-sm leading-6 text-ink-soft">{{ $assignment->description ?: 'Belum ada instruksi tambahan.' }}</p><p class="mt-2 text-xs font-semibold text-orange-ink">Batas {{ $assignment->deadline->translatedFormat('d M Y, H:i') }} · {{ $assignment->deadline->diffForHumans() }}</p></div>
                        <x-theme.button href="{{ route('student.learning.index', ['tab' => 'assignments', 'classSubjectId' => $assignment->class_subject_id, 'assignmentId' => $assignment->id]) }}">Lihat & kerjakan</x-theme.button>
                    </div>
                </x-theme.card>
            @endforeach
            @foreach($activeQuizzes as $quiz)
                <x-theme.card class="border-tosca/30">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div><div class="flex flex-wrap items-center gap-2"><x-theme.badge tone="tosca">Kuis aktif</x-theme.badge><span class="text-xs text-ink-soft">{{ $quiz->classSubject->subject->name }}</span></div><h2 class="mt-2 text-lg">{{ $quiz->title }}</h2><p class="mt-1 text-sm text-ink-soft">{{ $quiz->duration_minutes }} menit · tutup {{ $quiz->close_at->diffForHumans() }}</p></div>
                        <x-theme.button href="{{ route('student.quizzes.play', $quiz) }}" variant="navy">Mulai kuis</x-theme.button>
                    </div>
                </x-theme.card>
            @endforeach
            @if($todoCount === 0)<x-theme.card class="py-14 text-center"><flux:icon.check-circle class="mx-auto size-10 text-tosca" /><h2 class="mt-4 text-lg">Semua sudah beres</h2><p class="mt-1 text-sm text-ink-soft">Belum ada tugas atau kuis yang perlu dikerjakan.</p></x-theme.card>@endif
        </div>
    @elseif($tab === 'history')
        <div class="grid gap-6 lg:grid-cols-2">
            <x-theme.card :padding="false" class="overflow-hidden"><div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-xl">Pengumpulan tugas</h2></div><div class="divide-y divide-line">@forelse($submissions as $submission)<div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6"><div><p class="font-semibold text-navy">{{ $submission->assignment->title }}</p><p class="mt-1 text-xs text-ink-soft">{{ $submission->assignment->classSubject->subject->name }} · {{ $submission->submitted_at->translatedFormat('d M Y, H:i') }}</p></div>@if($submission->grade)<x-theme.badge tone="tosca">Nilai {{ number_format((float) $submission->grade->score, 0) }}</x-theme.badge>@else<x-theme.badge tone="neutral">Menunggu nilai</x-theme.badge>@endif</div>@empty<div class="p-10 text-center text-sm text-ink-soft">Belum ada tugas yang dikumpulkan.</div>@endforelse</div></x-theme.card>
            <x-theme.card :padding="false" class="overflow-hidden"><div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-xl">Riwayat kuis</h2></div><div class="divide-y divide-line">@forelse($quizAttempts as $attempt)<div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6"><div><p class="font-semibold text-navy">{{ $attempt->quiz->title }}</p><p class="mt-1 text-xs text-ink-soft">{{ $attempt->quiz->classSubject->subject->name }} · {{ $attempt->submitted_at?->translatedFormat('d M Y, H:i') }}</p></div><x-theme.badge :tone="$attempt->score === null ? 'neutral' : 'navy'">{{ $attempt->score === null ? 'Diproses' : 'Nilai '.number_format((float) $attempt->score, 0) }}</x-theme.badge></div>@empty<div class="p-10 text-center text-sm text-ink-soft">Belum ada kuis yang diselesaikan.</div>@endforelse</div></x-theme.card>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
            <x-theme.card :padding="false" class="overflow-hidden"><div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-xl">Pengumuman</h2></div><div class="divide-y divide-line">@forelse($announcements as $announcement)<article id="announcement-{{ $announcement->id }}" class="scroll-mt-24 px-5 py-4 target:bg-tosca-tint/40 sm:px-6"><div class="flex flex-wrap items-start justify-between gap-2"><h3 class="font-semibold text-navy">{{ $announcement->title }}</h3><x-theme.badge :tone="$announcement->target === 'all' ? 'navy' : 'orange'">{{ $announcement->target === 'all' ? 'Sekolah' : $announcement->schoolClass?->name }}</x-theme.badge></div><p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-soft">{{ $announcement->body }}</p><p class="mt-3 text-xs text-ink-soft">Diterbitkan {{ $announcement->creator->name }} · {{ $announcement->created_at?->translatedFormat('d M Y, H:i') }}</p></article>@empty<div class="p-10 text-center text-sm text-ink-soft">Belum ada pengumuman.</div>@endforelse</div></x-theme.card>
            <x-theme.card><h2 class="text-xl">Agenda mendatang</h2><div class="mt-5 space-y-4">@forelse($events as $event)<div id="event-{{ $event->id }}" class="flex scroll-mt-24 gap-3 rounded-xl p-2 target:bg-tosca-tint/50"><span class="flex size-11 shrink-0 flex-col items-center justify-center rounded-xl bg-tosca-tint text-navy"><strong>{{ $event->date->format('d') }}</strong><small>{{ $event->date->translatedFormat('M') }}</small></span><div><p class="text-sm font-semibold text-navy">{{ $event->title }}</p><p class="mt-0.5 text-xs leading-5 text-ink-soft">{{ $event->description }}</p><p class="mt-1 text-xs font-semibold text-tosca-ink">{{ $event->date->translatedFormat('l, d F Y') }}</p></div></div>@empty<p class="text-sm text-ink-soft">Belum ada agenda mendatang.</p>@endforelse</div></x-theme.card>
        </div>
    @endif
</div>
