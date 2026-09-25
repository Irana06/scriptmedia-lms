<div class="space-y-7">
    {{-- Selalu pakai @php versi blok di file ini: Blade memasangkan @php pertama
         dengan @endphp pertama, jadi mencampur versi satu baris dan versi blok akan
         menelan markup di antaranya. --}}
    <section class="relative overflow-hidden rounded-card bg-gradient-to-br from-navy to-navy-mid px-5 py-7 text-white sm:px-8 sm:py-9">
        <div class="absolute -right-12 -top-16 size-48 rounded-full border-[28px] border-white/5"></div>
        <div class="relative">
            <x-theme.badge tone="orange">{{ now()->translatedFormat('l, d F Y') }}</x-theme.badge>
            <h1 class="mt-4 text-2xl text-white sm:text-3xl">Halo, {{ auth()->user()->name }}!</h1>
            <p class="mt-2 text-sm text-white/70">Pantau aktivitas, jadwal, dan perkembangan belajar anak di sekolah.</p>
        </div>
    </section>

    @if (session('guardian_status'))
        <div class="rounded-card border border-tosca/40 bg-tosca-tint/60 px-5 py-4 text-sm text-navy" role="status">{{ session('guardian_status') }}</div>
    @endif

    @if ($children->count() > 1)
        <nav class="flex flex-wrap gap-2" aria-label="Pilih anak">
            @foreach ($children as $child)
                <button
                    type="button"
                    wire:key="child-{{ $child->id }}"
                    wire:click="selectChild({{ $child->id }})"
                    @class([
                        'rounded-full border px-4 py-2 text-sm transition',
                        'border-navy bg-navy font-semibold text-white' => $child->id === $selectedChildId,
                        'border-line bg-white text-ink-soft hover:border-tosca hover:text-navy' => $child->id !== $selectedChildId,
                    ])
                >{{ $child->name }}</button>
            @endforeach
        </nav>
    @endif

    @if ($overview)
        <section class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-tosca-ink">Anak</p>
                <h2 class="mt-1 text-2xl">{{ $overview['student']->name }}</h2>
                <p class="mt-1 text-sm text-ink-soft">{{ $overview['className'] ?? 'Belum ditempatkan di kelas' }}</p>
            </div>
            <x-theme.badge tone="neutral">Hanya melihat</x-theme.badge>
        </section>

        <section>
            <h3 class="text-xl">Jadwal hari ini</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($overview['todaySchedules'] as $schedule)
                    <div class="rounded-card border border-line border-t-4 border-t-tosca bg-white p-5">
                        <x-theme.badge tone="neutral">{{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</x-theme.badge>
                        <p class="mt-4 text-lg font-semibold text-navy">{{ $schedule->classSubject->subject->name }}</p>
                        <p class="mt-1 text-sm text-ink-soft">{{ $schedule->classSubject->teacher->name }}</p>
                    </div>
                @empty
                    <x-theme.card class="py-10 text-center sm:col-span-2 lg:col-span-4">
                        <p class="text-sm text-ink-soft">Tidak ada jadwal pelajaran hari ini.</p>
                    </x-theme.card>
                @endforelse
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="border-b border-line px-5 py-5 sm:px-6">
                    <h3 class="text-xl">Perkembangan belajar</h3>
                    <p class="mt-1 text-xs text-ink-soft">Nilai akhir yang sudah diterbitkan guru{{ $overview['semester'] ? ' · semester '.$overview['semester']->name : '' }}.</p>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($overview['grades'] as $grade)
                        <li class="px-5 py-3.5 sm:px-6">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium text-navy">{{ $grade->classSubject->subject->name }}</span>
                                <span class="flex items-center gap-2">
                                    <span @class(['text-lg font-semibold tabular-nums', 'text-navy' => (float) $grade->final_score >= $grade->classSubject->subject->kkm, 'text-red-600' => (float) $grade->final_score < $grade->classSubject->subject->kkm])>{{ \App\Support\Score::format($grade->final_score) }}</span>
                                    <x-theme.badge tone="tosca-soft">{{ $grade->predikat }}</x-theme.badge>
                                </span>
                            </div>
                            @if ($grade->description)
                                <p class="mt-1.5 text-xs leading-5 text-ink-soft">{{ $grade->description }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="p-10 text-center text-sm text-ink-soft">Nilai akhir semester ini belum diterbitkan guru.</li>
                    @endforelse
                </ul>
            </x-theme.card>

            <x-theme.card>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="text-xl">Kehadiran</h3>
                    @if ($overview['attendanceRate'] !== null)
                        <p class="text-sm text-ink-soft">Tingkat kehadiran <strong class="text-lg tabular-nums text-navy">{{ $overview['attendanceRate'] }}%</strong></p>
                    @endif
                </div>
                <p class="mt-1 text-xs text-ink-soft">
                    {{ $overview['semester'] ? 'Semester '.$overview['semester']->name.' '.($overview['semester']->academicYear?->year_label ?? '') : '30 hari terakhir' }}
                </p>
                <div class="mt-5 grid grid-cols-4 gap-2 text-center">
                    @foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'] as $status => $label)
                        <div class="rounded-xl bg-offwhite px-2 py-3">
                            <p class="text-2xl font-semibold tabular-nums text-navy">{{ $overview['attendanceCounts'][$status] }}</p>
                            <p class="mt-1 text-xs text-ink-soft">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
                <ul class="mt-5 divide-y divide-line text-sm">
                    @forelse ($overview['attendanceRecords'] as $record)
                        @php
                            $statusTone = match ($record->status) {
                                'hadir' => 'tosca-soft',
                                'alpa' => 'danger',
                                default => 'orange',
                            };
                        @endphp
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span class="text-ink-soft">{{ $record->date->translatedFormat('l, d M Y') }}</span>
                            <x-theme.badge :tone="$statusTone">{{ ucfirst($record->status) }}</x-theme.badge>
                        </li>
                    @empty
                        <li class="py-6 text-center text-ink-soft">Belum ada catatan kehadiran.</li>
                    @endforelse
                </ul>
            </x-theme.card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="border-b border-line px-5 py-5 sm:px-6">
                    <h3 class="text-xl">Tugas</h3>
                    <p class="mt-1 text-xs text-ink-soft">Dua pekan terakhir hingga dua pekan ke depan.</p>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($overview['assignments'] as $assignment)
                        @php
                            $submission = $assignment->submissions->first();
                            $assignmentGrade = $submission?->grade;
                            [$assignmentTone, $assignmentLabel] = match (true) {
                                $assignmentGrade !== null => ['tosca', 'Nilai '.\App\Support\Score::format($assignmentGrade->score)],
                                $submission !== null => ['tosca-soft', 'Sudah dikumpulkan'],
                                $assignment->deadline->isFuture() => ['orange', 'Belum dikumpulkan'],
                                default => ['danger', 'Tidak dikumpulkan'],
                            };
                        @endphp
                        <li class="px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="font-semibold text-navy">{{ $assignment->title }}</p>
                                <x-theme.badge :tone="$assignmentTone">{{ $assignmentLabel }}</x-theme.badge>
                            </div>
                            <p class="mt-1 text-sm text-ink-soft">{{ $assignment->classSubject->subject->name }} · batas {{ $assignment->deadline->translatedFormat('d M Y, H:i') }}</p>
                            @if ($assignmentGrade?->feedback)
                                <p class="mt-2 rounded-xl bg-offwhite px-3 py-2 text-xs leading-5 text-ink-soft"><strong class="text-navy">Catatan guru:</strong> {{ $assignmentGrade->feedback }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="p-10 text-center text-sm text-ink-soft">Tidak ada tugas dalam rentang ini.</li>
                    @endforelse
                </ul>
            </x-theme.card>

            <x-theme.card :padding="false" class="overflow-hidden">
                <div class="border-b border-line px-5 py-5 sm:px-6">
                    <h3 class="text-xl">Kuis</h3>
                    <p class="mt-1 text-xs text-ink-soft">Status dan skor pengerjaan.</p>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($overview['quizzes'] as $quiz)
                        @php
                            $attempt = $quiz->attempts->first();
                            [$quizTone, $quizLabel] = match (true) {
                                $attempt !== null && $attempt->score !== null => ['tosca', 'Skor '.\App\Support\Score::format($attempt->score)],
                                $attempt !== null => ['tosca-soft', 'Sudah dikerjakan'],
                                $quiz->open_at->isFuture() => ['neutral', 'Dibuka '.$quiz->open_at->translatedFormat('d M')],
                                $quiz->close_at->isFuture() => ['orange', 'Sedang dibuka'],
                                default => ['danger', 'Tidak dikerjakan'],
                            };
                        @endphp
                        <li class="px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="font-semibold text-navy">{{ $quiz->title }}</p>
                                <x-theme.badge :tone="$quizTone">{{ $quizLabel }}</x-theme.badge>
                            </div>
                            <p class="mt-1 text-sm text-ink-soft">{{ $quiz->classSubject->subject->name }} · ditutup {{ $quiz->close_at->translatedFormat('d M Y, H:i') }}</p>
                        </li>
                    @empty
                        <li class="p-10 text-center text-sm text-ink-soft">Tidak ada kuis dalam rentang ini.</li>
                    @endforelse
                </ul>
            </x-theme.card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-theme.card>
                <h3 class="text-xl">Jadwal pekan ini</h3>
                <div class="mt-4 space-y-4">
                    @forelse ($overview['week'] as $day => $daySchedules)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-tosca-ink">{{ $day }}</p>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($daySchedules as $schedule)
                                    <li class="flex items-baseline gap-3 text-sm">
                                        <span class="w-24 shrink-0 tabular-nums text-ink-soft">{{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</span>
                                        <span class="font-medium text-navy">{{ $schedule->classSubject->subject->name }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-sm text-ink-soft">Jadwal pelajaran belum diatur.</p>
                    @endforelse
                </div>
            </x-theme.card>

            <x-theme.card>
                <h3 class="text-xl">Agenda sekolah</h3>
                <div class="mt-5 space-y-2">
                    @forelse ($overview['events'] as $event)
                        <div class="flex gap-3 rounded-xl p-2">
                            <span class="flex size-11 shrink-0 flex-col items-center justify-center rounded-xl bg-tosca-tint text-navy"><strong>{{ $event->date->format('d') }}</strong><small>{{ $event->date->translatedFormat('M') }}</small></span>
                            <div>
                                <p class="text-sm font-semibold text-navy">{{ $event->title }}</p>
                                <p class="mt-0.5 text-xs leading-5 text-ink-soft">{{ $event->description }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-ink-soft">Belum ada agenda mendatang.</p>
                    @endforelse
                </div>
            </x-theme.card>
        </div>

        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="border-b border-line px-5 py-5 sm:px-6"><h3 class="text-xl">Pengumuman</h3></div>
            <div class="divide-y divide-line">
                @forelse ($overview['announcements'] as $announcement)
                    <div class="px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap justify-between gap-2">
                            <p class="font-semibold text-navy">{{ $announcement->title }}</p>
                            <x-theme.badge :tone="$announcement->target === 'all' ? 'navy' : 'orange'">{{ $announcement->target === 'all' ? 'Sekolah' : $announcement->schoolClass?->name }}</x-theme.badge>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-soft">{{ $announcement->body }}</p>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-ink-soft">Belum ada pengumuman.</div>
                @endforelse
            </div>
        </x-theme.card>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
        <x-theme.card>
            <h3 class="text-xl">{{ $children->isEmpty() ? 'Hubungkan akun dengan anak' : 'Hubungkan anak lain' }}</h3>
            <p class="mt-1 text-sm leading-6 text-ink-soft">Isi NISN atau NIS serta nama lengkap anak sesuai rapor. Sekolah akan memastikan Anda wali siswa tersebut sebelum data anak bisa dilihat.</p>

            <form wire:submit="requestLink" class="mt-5 flex flex-col gap-4">
                <flux:input wire:model="studentIdentifier" label="NISN atau NIS" type="text" inputmode="numeric" autocomplete="off" />
                @error('studentIdentifier')
                    <p class="-mt-2 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                @enderror

                <flux:input wire:model="studentName" label="Nama lengkap anak" type="text" autocomplete="off" />
                @error('studentName')
                    <p class="-mt-2 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                @enderror

                <label class="flex flex-col gap-1.5 text-sm font-medium text-navy">
                    Hubungan dengan anak
                    <select wire:model="relationship" class="rounded-xl border border-line bg-white px-3 py-2.5 text-sm font-normal text-ink focus:border-tosca focus:outline-none focus:ring-2 focus:ring-tosca/30">
                        <option value="">Pilih hubungan</option>
                        @foreach ($relationships as $option)
                            <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                </label>
                @error('relationship')
                    <p class="-mt-2 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                @enderror

                <flux:button type="submit" variant="primary" class="min-h-11">Kirim permintaan</flux:button>
            </form>
        </x-theme.card>

        <x-theme.card>
            <h3 class="text-xl">Status permintaan</h3>
            <ul class="mt-4 divide-y divide-line">
                @forelse ($linkRequests as $linkRequest)
                    <li class="flex items-center justify-between gap-3 py-3" wire:key="link-request-{{ $linkRequest->id }}">
                        <div>
                            <p class="font-semibold text-navy">{{ $linkRequest->student->name }}</p>
                            <p class="text-xs text-ink-soft">Diajukan {{ $linkRequest->created_at?->translatedFormat('d M Y') }} · {{ ucfirst((string) $linkRequest->relationship) }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <x-theme.badge :tone="$linkRequest->status === 'pending' ? 'orange' : 'danger'">
                                {{ $linkRequest->status === 'pending' ? 'Menunggu persetujuan' : 'Ditolak' }}
                            </x-theme.badge>
                            @if ($linkRequest->status === 'pending')
                                <button
                                    type="button"
                                    wire:click="cancelRequest({{ $linkRequest->id }})"
                                    wire:confirm="Batalkan permintaan untuk {{ $linkRequest->student->name }}?"
                                    class="text-xs font-semibold text-ink-soft underline-offset-2 transition hover:text-red-700 hover:underline"
                                >Batalkan</button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-sm text-ink-soft">Tidak ada permintaan yang menunggu.</li>
                @endforelse
            </ul>
        </x-theme.card>
    </div>
</div>
