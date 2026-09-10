<x-layouts.guru-admin title="Data Guru">
    <x-theme.section-header
        eyebrow="Akun Guru"
        title="Data guru"
        description="Guru yang masuk memakai username tidak bisa memakai tautan lupa password. Reset dari sini, lalu sampaikan password barunya secara langsung."
    />

    @if (session('generated_password'))
        <div class="mt-6 rounded-card border border-orange/40 bg-orange/10 p-5" role="alert">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <x-theme.badge tone="orange">Tampilkan satu kali</x-theme.badge>
                    <h2 class="mt-3 text-lg">Password baru untuk {{ session('reset_user_name') }}</h2>
                    <p class="mt-1 text-sm text-ink-soft">Salin dan sampaikan langsung kepada guru. Password ini tidak dapat dilihat lagi setelah halaman ditutup.</p>
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
                        <th class="px-5 py-4 font-semibold sm:px-6">Guru</th>
                        <th class="px-5 py-4 font-semibold">Masuk dengan</th>
                        <th class="px-5 py-4 font-semibold">Status akun</th>
                        <th class="px-5 py-4 text-right font-semibold sm:px-6">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($teachers as $teacher)
                        @php($usesUsername = str_ends_with($teacher->email, '.invalid'))
                        <tr>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="font-semibold text-navy">{{ $teacher->name }}</p>
                                <p class="mt-1 text-xs text-ink-soft">
                                    @if ($teacher->nip)
                                        NIP {{ $teacher->nip }}
                                    @elseif ($teacher->nuptk)
                                        NUPTK {{ $teacher->nuptk }}
                                    @else
                                        Tanpa NIP/NUPTK
                                    @endif
                                </p>
                            </td>
                            <td class="px-5 py-4 text-sm">
                                <span class="text-navy">{{ $usesUsername ? $teacher->username : $teacher->email }}</span>
                                @if ($usesUsername)
                                    <x-theme.badge tone="neutral" class="ml-2">Username</x-theme.badge>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <x-theme.badge :tone="$teacher->must_change_password ? 'orange' : 'tosca-soft'">
                                    {{ $teacher->must_change_password ? 'Wajib ganti password' : 'Aktif' }}
                                </x-theme.badge>
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                @if ($teacher->isNot(auth()->user()) && ! $teacher->hasRole('admin'))
                                    <form method="POST" action="{{ route('teachers.password.reset', $teacher) }}">
                                        @csrf
                                        <x-theme.button
                                            type="submit"
                                            variant="outline"
                                            class="min-h-9 px-3 py-2"
                                            onclick="return confirm('Reset password guru ini?')"
                                        >
                                            Reset password
                                        </x-theme.button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-ink-soft">Belum ada akun guru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($teachers->hasPages())
            <div class="border-t border-line px-5 py-4 sm:px-6">
                {{ $teachers->links() }}
            </div>
        @endif
    </x-theme.card>
</x-layouts.guru-admin>
