<x-layouts.siswa title="Dashboard Siswa">
    <div class="space-y-7">
        <section class="relative overflow-hidden rounded-card bg-gradient-to-br from-navy to-navy-mid px-5 py-7 text-white sm:px-8 sm:py-9">
            <div class="absolute -right-12 -top-16 size-48 rounded-full border-[28px] border-white/5"></div>
            <div class="relative"><x-theme.badge tone="orange">{{ now()->translatedFormat('l, d F Y') }}</x-theme.badge><h1 class="mt-4 text-2xl text-white sm:text-3xl">Halo, {{ auth()->user()->name }}!</h1><p class="mt-2 text-sm text-white/70">Lihat jadwal, tugas, pengumuman, dan hasil belajar terbarumu.</p></div>
        </section>

        <section>
            <div class="flex items-center justify-between"><h2 class="text-xl">Jadwal hari ini</h2><a href="{{ route('student.learning.index') }}" class="text-sm font-semibold text-tosca-ink" wire:navigate>Ruang belajar</a></div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($todaySchedules as $schedule)
                    <a href="{{ route('student.learning.index', ['classSubjectId' => $schedule->class_subject_id]) }}" class="group rounded-card border border-line border-t-4 border-t-tosca bg-white p-5 shadow-[0_16px_40px_-28px_rgba(11,37,69,0.42)] transition hover:-translate-y-0.5 hover:border-tosca" wire:navigate><x-theme.badge tone="neutral">{{ substr($schedule->start_time,0,5) }}–{{ substr($schedule->end_time,0,5) }}</x-theme.badge><h3 class="mt-4 text-lg group-hover:text-tosca-ink">{{ $schedule->classSubject->subject->name }}</h3><p class="mt-1 text-sm text-ink-soft">{{ $schedule->classSubject->teacher->name }}</p><p class="mt-3 text-xs font-semibold text-tosca-ink">Buka ruang belajar →</p></a>
                @empty
                    <x-theme.card class="sm:col-span-2 lg:col-span-4 py-10 text-center"><p class="text-sm text-ink-soft">Tidak ada jadwal pelajaran hari ini.</p></x-theme.card>
                @endforelse
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-5 sm:px-6"><div><h2 class="text-xl">Tugas mendekati deadline</h2><p class="mt-1 text-xs text-ink-soft">Klik tugas untuk melihat instruksi dan mengumpulkan jawaban.</p></div><x-theme.badge tone="orange">{{ $upcomingAssignments->count() }} aktif</x-theme.badge></div>
                <div class="divide-y divide-line">
                    @forelse($upcomingAssignments as $assignment)
                        <a href="{{ route('student.learning.index', ['tab' => 'assignments', 'classSubjectId' => $assignment->class_subject_id, 'assignmentId' => $assignment->id]) }}" class="group flex gap-4 px-5 py-4 transition hover:bg-orange/5 sm:px-6" wire:navigate>
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-orange/15 text-orange"><flux:icon.clipboard-document-check class="size-5" /></span>
                            <div class="min-w-0 flex-1"><div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold text-navy group-hover:text-tosca-ink">{{ $assignment->title }}</h3><x-theme.badge tone="orange">{{ $assignment->deadline->diffForHumans() }}</x-theme.badge></div><p class="mt-1 text-sm text-ink-soft">{{ $assignment->classSubject->subject->name }} · {{ $assignment->classSubject->teacher->name }}</p><p class="mt-2 line-clamp-2 text-xs leading-5 text-ink-soft">{{ $assignment->description ?: 'Buka untuk melihat detail dan mengunggah jawaban.' }}</p><p class="mt-2 text-xs font-semibold text-tosca-ink">Batas {{ $assignment->deadline->translatedFormat('d M Y, H:i') }} →</p></div>
                        </a>
                    @empty
                        <div class="p-10 text-center text-sm text-ink-soft">Tidak ada tugas yang jatuh tempo minggu ini.</div>
                    @endforelse
                </div>
            </x-theme.card>

            <x-theme.card>
                <div class="flex items-start justify-between gap-3"><div><h2 class="text-xl">Nilai terbaru</h2><p class="mt-1 text-xs text-ink-soft">Nilai akhir yang sudah diterbitkan guru.</p></div><a href="{{ route('student.evaluation.index') }}" class="text-sm font-semibold text-tosca-ink" wire:navigate>Lihat semua</a></div>
                <div class="mt-5 space-y-3">
                    @forelse($recentGrades as $grade)
                        <a href="{{ route('student.evaluation.index', ['semesterId' => $grade->semester_id, 'classSubjectId' => $grade->class_subject_id]) }}#rincian-nilai" class="group flex items-center justify-between rounded-xl bg-offwhite p-3 transition hover:bg-tosca-tint" wire:navigate><div><p class="text-sm font-semibold text-navy group-hover:text-tosca-ink">{{ $grade->classSubject->subject->name }}</p><p class="text-xs text-ink-soft">Nilai akhir · Predikat {{ $grade->predikat }}</p><p class="mt-1 text-xs font-semibold text-tosca-ink">Lihat rincian tugas & kuis →</p></div><span class="text-xl font-semibold text-tosca-ink">{{ number_format((float)$grade->final_score,0) }}</span></a>
                    @empty
                        <p class="text-sm text-ink-soft">Belum ada nilai akhir.</p>
                    @endforelse
                </div>
            </x-theme.card>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-5 sm:px-6"><div><h2 class="text-xl">Pengumuman terbaru</h2><p class="mt-1 text-xs text-ink-soft">Klik untuk membuka pusat informasi.</p></div><a href="{{ route('student.activities.index', ['tab' => 'info']) }}" class="text-sm font-semibold text-tosca-ink" wire:navigate>Lihat semua</a></div>
                <div class="divide-y divide-line">
                    @forelse($announcements as $item)
                        <a id="announcement-{{ $item->id }}" href="{{ route('student.activities.index', ['tab' => 'info']) }}#announcement-{{ $item->id }}" class="block px-5 py-4 transition hover:bg-tosca-tint/35 sm:px-6" wire:navigate><div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold text-navy">{{ $item->title }}</h3><x-theme.badge :tone="$item->target==='all'?'navy':'orange'">{{ $item->target==='all'?'Sekolah':$item->schoolClass?->name }}</x-theme.badge></div><p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-soft">{{ $item->body }}</p><p class="mt-2 text-xs text-ink-soft">{{ $item->creator->name }} · {{ $item->created_at?->diffForHumans() }}</p><p class="mt-2 text-xs font-semibold text-tosca-ink">Buka informasi →</p></a>
                    @empty
                        <div class="p-10 text-center text-sm text-ink-soft">Belum ada pengumuman.</div>
                    @endforelse
                </div>
            </x-theme.card>

            <x-theme.card>
                <div class="flex items-center justify-between"><div><h2 class="text-xl">Agenda akademik</h2><p class="mt-1 text-xs text-ink-soft">Jadwal penting sekolah mendatang.</p></div><flux:icon.calendar-days class="size-6 text-tosca" /></div>
                <div class="mt-5 space-y-2">
                    @forelse($events as $event)
                        <a href="{{ route('student.activities.index', ['tab' => 'info']) }}#event-{{ $event->id }}" class="group flex gap-3 rounded-xl p-2 transition hover:bg-tosca-tint" wire:navigate><span class="flex size-11 shrink-0 flex-col items-center justify-center rounded-xl bg-tosca-tint text-navy"><strong>{{ $event->date->format('d') }}</strong><small>{{ $event->date->translatedFormat('M') }}</small></span><div><p class="text-sm font-semibold text-navy group-hover:text-tosca-ink">{{ $event->title }}</p><p class="mt-0.5 text-xs leading-5 text-ink-soft">{{ $event->description }}</p><p class="mt-1 text-xs font-semibold text-tosca-ink">{{ $event->date->translatedFormat('l, d F Y') }} →</p></div></a>
                    @empty
                        <p class="text-sm text-ink-soft">Belum ada agenda mendatang.</p>
                    @endforelse
                </div>
            </x-theme.card>
        </div>
    </div>
</x-layouts.siswa>
