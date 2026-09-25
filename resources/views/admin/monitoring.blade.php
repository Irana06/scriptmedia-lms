<x-layouts.guru-admin title="Pantauan Siswa">
    <div class="space-y-6">
        <x-theme.section-header
            eyebrow="Peringatan dini"
            title="Pantauan siswa & kelas"
            :description="'Siswa yang perlu perhatian dan statistik tiap kelas · '.$periodLabel.'.'"
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <x-theme.card>
                <p class="text-sm text-ink-soft">Siswa perlu perhatian</p>
                <p class="mt-2 text-3xl font-semibold text-navy">{{ $atRisk->count() }}</p>
            </x-theme.card>
            <x-theme.card>
                <p class="text-sm text-ink-soft">Kehadiran di bawah {{ \App\Services\EarlyWarningService::ATTENDANCE_THRESHOLD }}%</p>
                <p class="mt-2 text-3xl font-semibold text-navy">{{ $atRisk->filter(fn ($row) => $row['attendanceRate'] !== null && $row['attendanceRate'] < \App\Services\EarlyWarningService::ATTENDANCE_THRESHOLD)->count() }}</p>
            </x-theme.card>
            <x-theme.card>
                <p class="text-sm text-ink-soft">Punya nilai di bawah KKM</p>
                <p class="mt-2 text-3xl font-semibold text-navy">{{ $atRisk->where('belowKkm', '>', 0)->count() }}</p>
            </x-theme.card>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ([
                ['title' => 'Kehadiran per kelas', 'key' => 'attendanceRate', 'max' => 100, 'suffix' => '%', 'reference' => \App\Services\EarlyWarningService::ATTENDANCE_THRESHOLD, 'referenceLabel' => 'batas '.\App\Services\EarlyWarningService::ATTENDANCE_THRESHOLD.'%'],
                ['title' => 'Rata-rata nilai akhir per kelas', 'key' => 'averageScore', 'max' => 100, 'suffix' => '', 'reference' => null, 'referenceLabel' => null],
            ] as $chart)
                <x-theme.card>
                    <h2 class="text-lg">{{ $chart['title'] }}</h2>
                    <div class="mt-5 space-y-3" role="table" aria-label="{{ $chart['title'] }}">
                        @forelse ($classStats as $stat)
                            @php($value = $stat[$chart['key']])
                            <div class="grid grid-cols-[4.5rem_1fr_3.5rem] items-center gap-3" role="row">
                                <span class="truncate text-sm font-semibold text-navy" role="rowheader">{{ $stat['name'] }}</span>
                                <span class="relative h-6" role="cell">
                                    <span class="absolute inset-y-2.5 left-0 right-0 rounded-full bg-offwhite"></span>
                                    @if ($value !== null)
                                        <span
                                            class="absolute inset-y-1 left-0 rounded-r-[4px] bg-tosca transition-[width]"
                                            style="width: {{ min(100, $value / $chart['max'] * 100) }}%"
                                            title="{{ $stat['name'] }}: {{ \App\Support\Score::format($value) }}{{ $chart['suffix'] }} · {{ $stat['students'] }} siswa"
                                        ></span>
                                    @endif
                                    @if ($chart['reference'])
                                        <span class="absolute inset-y-0 w-px border-l-2 border-dashed border-ink-soft/60" style="left: {{ $chart['reference'] }}%" aria-hidden="true"></span>
                                    @endif
                                </span>
                                <span class="text-right text-sm tabular-nums text-ink-soft" role="cell">{{ $value === null ? '–' : \App\Support\Score::format($value).$chart['suffix'] }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-soft">Belum ada kelas di tahun ajaran aktif.</p>
                        @endforelse
                    </div>
                    @if ($chart['referenceLabel'] && $classStats->isNotEmpty())
                        <p class="mt-4 flex items-center gap-2 text-xs text-ink-soft"><span class="inline-block h-3 w-px border-l-2 border-dashed border-ink-soft/60"></span>Garis putus-putus: {{ $chart['referenceLabel'] }}</p>
                    @endif
                </x-theme.card>
            @endforeach
        </div>

        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="border-b border-line px-5 py-5 sm:px-6">
                <h2 class="text-lg">Siswa yang perlu perhatian</h2>
                <p class="mt-1 text-xs text-ink-soft">Kehadiran di bawah {{ \App\Services\EarlyWarningService::ATTENDANCE_THRESHOLD }}%, nilai akhir di bawah KKM, atau {{ \App\Services\EarlyWarningService::MISSING_ASSIGNMENT_THRESHOLD }}+ tugas lewat tenggat tanpa dikumpulkan.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-offwhite text-ink-soft"><tr><th class="px-5 py-3 sm:px-6">Siswa</th><th class="px-5 py-3">Kelas</th><th class="px-5 py-3">Kehadiran</th><th class="px-5 py-3">Alasan</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($atRisk as $row)
                            <tr>
                                <td class="px-5 py-3.5 font-semibold text-navy sm:px-6">{{ $row['name'] }}</td>
                                <td class="px-5 py-3.5 text-ink-soft">{{ $row['className'] }}</td>
                                <td class="px-5 py-3.5 tabular-nums text-ink-soft">{{ $row['attendanceRate'] === null ? '–' : $row['attendanceRate'].'%' }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($row['reasons'] as $reason)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-orange/12 px-2.5 py-1 text-xs font-semibold text-navy"><flux:icon.exclamation-triangle class="size-3.5 text-orange-ink" />{{ $reason }}</span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center"><flux:icon.check-badge class="mx-auto size-8 text-tosca" /><p class="mt-2 text-sm text-ink-soft">Tidak ada siswa yang perlu perhatian saat ini.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-theme.card>
    </div>
</x-layouts.guru-admin>
