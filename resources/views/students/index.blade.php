<x-layouts.guru-admin title="Data Siswa">
    <x-theme.section-header
        eyebrow="Akun Siswa"
        title="Data siswa"
        description="Reset password hanya jika siswa tidak dapat masuk. Password baru ditampilkan satu kali."
    />

    @if (session('generated_password'))
        <div class="mt-6 rounded-card border border-orange/40 bg-orange/10 p-5" role="alert">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <x-theme.badge tone="orange">Tampilkan satu kali</x-theme.badge>
                    <h2 class="mt-3 text-lg">Password baru untuk {{ session('reset_student_name') }}</h2>
                    <p class="mt-1 text-sm text-ink-soft">Salin dan sampaikan langsung kepada siswa. Password ini tidak dapat dilihat lagi setelah halaman ditutup.</p>
                </div>
                <code class="select-all rounded-xl bg-navy px-5 py-3 text-center text-lg font-semibold tracking-wider text-white">{{ session('generated_password') }}</code>
            </div>
        </div>
    @endif

    <x-theme.card :padding="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-line bg-offwhite text-xs uppercase tracking-wider text-ink-soft">
                    <tr>
                        <th class="px-5 py-4 font-semibold sm:px-6">Siswa</th>
                        <th class="px-5 py-4 font-semibold">NISN / NIS</th>
                        <th class="px-5 py-4 font-semibold">Status akun</th>
                        <th class="px-5 py-4 text-right font-semibold sm:px-6">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($students as $student)
                        <tr>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="font-semibold text-navy">{{ $student->name }}</p>
                                @if ($student->nik)
                                    <p class="mt-1 text-xs text-ink-soft">NIK {{ $student->nik }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-soft">
                                @if ($student->nisn)
                                    {{ $student->nisn }}
                                @elseif ($student->nis)
                                    {{ $student->nis }} <span class="text-xs">(NIS)</span>
                                @else
                                    {{ $student->username }}
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <x-theme.badge :tone="$student->must_change_password ? 'orange' : 'tosca-soft'">
                                    {{ $student->must_change_password ? 'Wajib ganti password' : 'Aktif' }}
                                </x-theme.badge>
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <form method="POST" action="{{ route('students.password.reset', $student) }}">
                                    @csrf
                                    <x-theme.button
                                        type="submit"
                                        variant="outline"
                                        class="min-h-9 px-3 py-2"
                                        onclick="return confirm('Reset password siswa ini?')"
                                    >
                                        Reset password
                                    </x-theme.button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-ink-soft">Belum ada akun siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="border-t border-line px-5 py-4 sm:px-6">
                {{ $students->links() }}
            </div>
        @endif
    </x-theme.card>
</x-layouts.guru-admin>
