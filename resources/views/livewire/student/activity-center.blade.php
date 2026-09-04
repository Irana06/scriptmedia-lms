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
            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-xl">Pengumpulan tugas</h2><p class="mt-1 text-xs text-ink-soft">Ketuk tugas untuk melihat file, ketepatan waktu, dan umpan balik.</p></div>
                <div class="divide-y divide-line">
                    @forelse($submissions as $submission)
                        @php
                            $isLate = $submission->submitted_at->isAfter($submission->assignment->deadline);
                        @endphp
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 transition hover:bg-offwhite sm:px-6">
                                <div class="min-w-0"><p class="font-semibold text-navy">{{ $submission->assignment->title }}</p><p class="mt-1 text-xs text-ink-soft">{{ $submission->assignment->classSubject->subject->name }} · {{ $submission->submitted_at->translatedFormat('d M Y, H:i') }}</p><span class="mt-2 inline-block text-xs font-semibold text-tosca-ink group-open:hidden">Lihat rincian ↓</span><span class="mt-2 hidden text-xs font-semibold text-tosca-ink group-open:inline">Tutup rincian ↑</span></div>
                                @if($submission->grade)<x-theme.badge tone="tosca">Nilai {{ number_format((float) $submission->grade->score, 0) }}</x-theme.badge>@else<x-theme.badge tone="neutral">Menunggu nilai</x-theme.badge>@endif
                            </summary>
                            <div class="border-t border-line bg-offwhite/55 px-5 py-5 sm:px-6">
                                <div class="grid gap-3 text-sm sm:grid-cols-2">
                                    <div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Pengajar</p><p class="mt-1 font-semibold text-navy">{{ $submission->assignment->classSubject->teacher->name }}</p></div>
                                    <div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Batas pengumpulan</p><p class="mt-1 font-semibold text-navy">{{ $submission->assignment->deadline->translatedFormat('d M Y, H:i') }}</p></div>
                                    <div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Waktu pengumpulan</p><p class="mt-1 font-semibold text-navy">{{ $submission->submitted_at->translatedFormat('d M Y, H:i') }}</p></div>
                                    <div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Ketepatan waktu</p><p @class(['mt-1 font-semibold', 'text-red-700' => $isLate, 'text-tosca-ink' => ! $isLate])>{{ $isLate ? 'Terlambat '.$submission->assignment->deadline->diffForHumans($submission->submitted_at, true) : 'Tepat waktu' }}</p></div>
                                </div>
                                <div class="mt-4 rounded-xl bg-white p-4"><p class="text-xs text-ink-soft">Umpan balik guru</p><p class="mt-2 text-sm leading-6 text-navy">{{ $submission->grade?->feedback ?: ($submission->grade ? 'Belum ada catatan tambahan dari guru.' : 'Umpan balik tersedia setelah tugas dinilai.') }}</p></div>
                                <div class="mt-4 flex flex-wrap gap-2"><a href="{{ route('learning.files.submission', $submission) }}" target="_blank" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-navy px-3 text-sm font-semibold text-white"><flux:icon.document-arrow-down class="size-4" />Buka jawaban</a><a href="{{ route('student.learning.index', ['tab' => 'assignments', 'classSubjectId' => $submission->assignment->class_subject_id, 'assignmentId' => $submission->assignment->id]) }}" class="inline-flex min-h-10 items-center rounded-xl border border-line bg-white px-3 text-sm font-semibold text-navy">Lihat tugas</a></div>
                            </div>
                        </details>
                    @empty
                        <div class="p-10 text-center text-sm text-ink-soft">Belum ada tugas yang dikumpulkan. Riwayat akan muncul otomatis setelah kamu mengirim jawaban.</div>
                    @endforelse
                </div>
            </x-theme.card>

            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-xl">Riwayat kuis</h2><p class="mt-1 text-xs text-ink-soft">Ketuk kuis untuk melihat durasi dan rincian jawabanmu.</p></div>
                <div class="divide-y divide-line">
                    @forelse($quizAttempts as $attempt)
                        @php
                            $questionCount = $attempt->quiz->questions->count();
                            $answeredCount = $attempt->answers->filter(fn ($answer) => filled($answer->answer))->count();
                            $correctCount = $attempt->answers->filter(fn ($answer) => $answer->question->type === 'mc' && (float) $answer->score === 1.0)->count();
                            $pendingEssayCount = $attempt->answers->filter(fn ($answer) => $answer->question->type === 'essay' && $answer->score === null)->count();
                            $durationMinutes = $attempt->submitted_at ? max(1, (int) ceil($attempt->started_at->diffInSeconds($attempt->submitted_at) / 60)) : null;
                        @endphp
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 transition hover:bg-offwhite sm:px-6">
                                <div class="min-w-0"><p class="font-semibold text-navy">{{ $attempt->quiz->title }}</p><p class="mt-1 text-xs text-ink-soft">{{ $attempt->quiz->classSubject->subject->name }} · {{ $attempt->submitted_at?->translatedFormat('d M Y, H:i') }}</p><span class="mt-2 inline-block text-xs font-semibold text-tosca-ink group-open:hidden">Lihat rincian ↓</span><span class="mt-2 hidden text-xs font-semibold text-tosca-ink group-open:inline">Tutup rincian ↑</span></div>
                                <x-theme.badge :tone="$attempt->score === null ? 'neutral' : 'navy'">{{ $attempt->score === null ? 'Diproses' : 'Nilai '.number_format((float) $attempt->score, 0) }}</x-theme.badge>
                            </summary>
                            <div class="border-t border-line bg-offwhite/55 px-5 py-5 sm:px-6">
                                <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                    <div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Soal</p><p class="mt-1 font-semibold text-navy">{{ $questionCount }}</p></div>
                                    <div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Terjawab</p><p class="mt-1 font-semibold text-navy">{{ $answeredCount }}/{{ $questionCount }}</p></div>
                                    <div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Benar</p><p class="mt-1 font-semibold text-tosca-ink">{{ $correctCount }}</p></div>
                                    <div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Durasi</p><p class="mt-1 font-semibold text-navy">{{ $durationMinutes ? $durationMinutes.' menit' : '—' }}</p></div>
                                </div>
                                @if($pendingEssayCount > 0)<p class="mt-3 rounded-xl bg-orange/15 px-3 py-2 text-xs font-semibold text-orange-ink">{{ $pendingEssayCount }} jawaban esai masih menunggu penilaian guru.</p>@endif
                                <div class="mt-4 space-y-2">
                                    @foreach($attempt->answers as $answer)
                                        @php
                                            $selectedChoice = $answer->question->type === 'mc'
                                                ? $answer->question->choices->firstWhere('id', (int) $answer->answer)?->label
                                                : null;
                                        @endphp
                                        <div class="rounded-xl bg-white p-3"><div class="flex items-start justify-between gap-3"><p class="text-sm font-semibold text-navy">{{ $loop->iteration }}. {{ $answer->question->question }}</p>@if($answer->score !== null)<x-theme.badge :tone="(float) $answer->score > 0 ? 'tosca' : 'danger'">{{ (float) $answer->score > 0 ? 'Benar' : 'Belum tepat' }}</x-theme.badge>@else<x-theme.badge tone="neutral">Menunggu</x-theme.badge>@endif</div><p class="mt-2 text-xs leading-5 text-ink-soft">Jawabanmu: <span class="font-semibold text-navy">{{ $selectedChoice ?: ($answer->answer ?: 'Tidak dijawab') }}</span></p></div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="p-10 text-center text-sm text-ink-soft">Belum ada kuis yang diselesaikan. Hasil akan tersimpan otomatis setelah kuis dikirim.</div>
                    @endforelse
                </div>
            </x-theme.card>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-xl">Pengumuman</h2><p class="mt-1 text-xs text-ink-soft">Ketuk pengumuman untuk melihat penerbit dan sasaran informasi.</p></div>
                <div class="divide-y divide-line">
                    @forelse($announcements as $announcement)
                        <details id="announcement-{{ $announcement->id }}" class="group scroll-mt-24 target:bg-tosca-tint/40">
                            <summary class="cursor-pointer list-none px-5 py-4 transition hover:bg-offwhite sm:px-6"><div class="flex flex-wrap items-start justify-between gap-2"><h3 class="font-semibold text-navy">{{ $announcement->title }}</h3><x-theme.badge :tone="$announcement->target === 'all' ? 'navy' : 'orange'">{{ $announcement->target === 'all' ? 'Sekolah' : $announcement->schoolClass?->name }}</x-theme.badge></div><p class="mt-2 line-clamp-2 text-sm leading-6 text-ink-soft">{{ $announcement->body }}</p><span class="mt-2 inline-block text-xs font-semibold text-tosca-ink group-open:hidden">Baca selengkapnya ↓</span><span class="mt-2 hidden text-xs font-semibold text-tosca-ink group-open:inline">Tutup ↑</span></summary>
                            <div class="border-t border-line bg-offwhite/55 px-5 py-5 sm:px-6"><p class="whitespace-pre-line text-sm leading-7 text-navy">{{ $announcement->body }}</p><div class="mt-4 grid gap-3 text-sm sm:grid-cols-2"><div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Diterbitkan oleh</p><p class="mt-1 font-semibold text-navy">{{ $announcement->creator->name }}</p></div><div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Ditujukan kepada</p><p class="mt-1 font-semibold text-navy">{{ $announcement->target === 'all' ? 'Seluruh warga sekolah' : 'Siswa kelas '.$announcement->schoolClass?->name }}</p></div><div class="rounded-xl bg-white p-3 sm:col-span-2"><p class="text-xs text-ink-soft">Waktu terbit</p><p class="mt-1 font-semibold text-navy">{{ $announcement->created_at?->translatedFormat('l, d F Y · H:i') }} · {{ $announcement->created_at?->diffForHumans() }}</p></div></div></div>
                        </details>
                    @empty
                        <div class="p-10 text-center text-sm text-ink-soft">Belum ada pengumuman untuk sekolah atau kelasmu.</div>
                    @endforelse
                </div>
            </x-theme.card>

            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="text-xl">Agenda mendatang</h2><p class="mt-1 text-xs text-ink-soft">Jadwal sekolah yang belum lewat.</p></div>
                <div class="divide-y divide-line">
                    @forelse($events as $event)
                        <details id="event-{{ $event->id }}" class="group scroll-mt-24 target:bg-tosca-tint/50">
                            <summary class="flex cursor-pointer list-none gap-3 px-5 py-4 transition hover:bg-offwhite sm:px-6"><span class="flex size-11 shrink-0 flex-col items-center justify-center rounded-xl bg-tosca-tint text-navy"><strong>{{ $event->date->format('d') }}</strong><small>{{ $event->date->translatedFormat('M') }}</small></span><div class="min-w-0 flex-1"><p class="text-sm font-semibold text-navy">{{ $event->title }}</p><p class="mt-0.5 line-clamp-2 text-xs leading-5 text-ink-soft">{{ $event->description ?: 'Belum ada keterangan tambahan.' }}</p><p class="mt-1 text-xs font-semibold text-tosca-ink">{{ $event->date->translatedFormat('l, d F Y') }}</p><span class="mt-2 inline-block text-xs font-semibold text-tosca-ink group-open:hidden">Lihat agenda ↓</span><span class="mt-2 hidden text-xs font-semibold text-tosca-ink group-open:inline">Tutup ↑</span></div></summary>
                            <div class="border-t border-line bg-offwhite/55 px-5 py-5 sm:px-6"><p class="whitespace-pre-line text-sm leading-7 text-navy">{{ $event->description ?: 'Belum ada keterangan tambahan untuk agenda ini.' }}</p><div class="mt-4 space-y-3 text-sm"><div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Tanggal pelaksanaan</p><p class="mt-1 font-semibold text-navy">{{ $event->date->translatedFormat('l, d F Y') }} · {{ $event->date->isToday() ? 'Hari ini' : $event->date->diffForHumans() }}</p></div><div class="rounded-xl bg-white p-3"><p class="text-xs text-ink-soft">Dibuat oleh</p><p class="mt-1 font-semibold text-navy">{{ $event->creator->name }}</p></div></div></div>
                        </details>
                    @empty
                        <div class="p-10 text-center text-sm text-ink-soft">Belum ada agenda mendatang.</div>
                    @endforelse
                </div>
            </x-theme.card>
        </div>
    @endif
</div>
