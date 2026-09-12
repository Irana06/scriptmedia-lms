<div>
    <x-theme.section-header
        eyebrow="Akun Orang Tua"
        title="Orang tua & wali"
        description="Setujui tautan setelah memastikan pendaftar benar wali siswa, atau tambahkan akun orang tua langsung dari sekolah."
    />

    @if (session('guardian_admin_status'))
        <div class="mt-6 rounded-card border border-tosca/40 bg-tosca-tint/60 px-5 py-4 text-sm text-navy" role="status">{{ session('guardian_admin_status') }}</div>
    @endif

    @if ($createdCredentials)
        <div class="mt-6 rounded-card border border-orange/40 bg-orange/10 p-5" role="alert">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <x-theme.badge tone="orange">Tampilkan satu kali</x-theme.badge>
                    <h2 class="mt-3 text-lg">Akun untuk {{ $createdCredentials['name'] }}</h2>
                    <p class="mt-1 text-sm text-ink-soft">Masuk dengan <strong class="text-navy">{{ $createdCredentials['login'] }}</strong>. Sampaikan langsung; password wajib diganti saat pertama masuk.</p>
                </div>
                <code class="select-all rounded-xl bg-navy px-5 py-3 text-center text-lg font-semibold tracking-wider text-white">{{ $createdCredentials['password'] }}</code>
            </div>
        </div>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_.6fr]">
        <x-theme.card :padding="false" class="overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-5 sm:px-6">
                <div>
                    <h2 class="text-lg">Permintaan menunggu</h2>
                    <p class="mt-1 text-xs text-ink-soft">Hubungi pendaftar atau wali kelas sebelum menyetujui.</p>
                </div>
                <x-theme.badge tone="orange">{{ $pending->count() }}</x-theme.badge>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-line bg-offwhite text-xs uppercase tracking-wider text-ink-soft">
                        <tr>
                            <th class="px-5 py-4 font-semibold sm:px-6">Orang tua</th>
                            <th class="px-5 py-4 font-semibold">Anak</th>
                            <th class="px-5 py-4 font-semibold">Hubungan</th>
                            <th class="px-5 py-4 text-right font-semibold sm:px-6">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($pending as $link)
                            <tr wire:key="pending-{{ $link->id }}">
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-semibold text-navy">{{ $link->guardian->name }}</p>
                                    <p class="mt-1 text-xs text-ink-soft">
                                        {{ str_ends_with($link->guardian->email, '.invalid') ? $link->guardian->username : $link->guardian->email }}{{ $link->guardian->phone ? ' · '.$link->guardian->phone : '' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-navy">{{ $link->student->name }}</p>
                                    <p class="mt-1 text-xs text-ink-soft">{{ $link->student->nisn ?? $link->student->nis }} · {{ $link->student->schoolClasses->pluck('name')->implode(', ') ?: 'Tanpa kelas' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-ink-soft">{{ ucfirst((string) $link->relationship) }}</td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="approve({{ $link->id }})" wire:confirm="Setujui tautan ini? Orang tua akan bisa melihat jadwal, kehadiran, tugas, dan nilai anak." class="rounded-lg bg-navy px-3 py-2 text-xs font-semibold text-white transition hover:bg-navy-mid">Setujui</button>
                                        <button type="button" wire:click="reject({{ $link->id }})" wire:confirm="Tolak permintaan ini?" class="rounded-lg border border-line px-3 py-2 text-xs font-semibold text-ink-soft transition hover:border-red-300 hover:text-red-700">Tolak</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-ink-soft">Tidak ada permintaan yang menunggu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-theme.card>

        <x-theme.card>
            <h2 class="text-lg">Tambah orang tua</h2>
            <p class="mt-1 text-xs leading-5 text-ink-soft">Akun yang ditambahkan sekolah langsung terhubung. Tanpa email, dibuatkan username dari nama.</p>

            <form wire:submit="createGuardian" class="mt-5 flex flex-col gap-4">
                <flux:input wire:model="name" label="Nama lengkap" type="text" />
                @error('name')
                    <p class="-mt-2 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                @enderror

                <flux:input wire:model="email" label="Email (opsional)" type="email" />
                @error('email')
                    <p class="-mt-2 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                @enderror

                <flux:input wire:model="phone" label="Nomor HP (opsional)" type="tel" />
                @error('phone')
                    <p class="-mt-2 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                @enderror

                <flux:input wire:model="studentIdentifier" label="NISN atau NIS anak" type="text" inputmode="numeric" />
                @error('studentIdentifier')
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

                <flux:button type="submit" variant="primary" class="min-h-11">Tambah dan hubungkan</flux:button>
            </form>
        </x-theme.card>
    </div>

    <x-theme.card :padding="false" class="mt-6 overflow-hidden">
        <div class="border-b border-line px-5 py-5 sm:px-6">
            <h2 class="text-lg">Orang tua terhubung</h2>
            <p class="mt-1 text-xs text-ink-soft">Memutus tautan langsung menghentikan akses orang tua ke data anak.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-line bg-offwhite text-xs uppercase tracking-wider text-ink-soft">
                    <tr>
                        <th class="px-5 py-4 font-semibold sm:px-6">Orang tua</th>
                        <th class="px-5 py-4 font-semibold">Anak</th>
                        <th class="px-5 py-4 font-semibold">Disetujui</th>
                        <th class="px-5 py-4 text-right font-semibold sm:px-6">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($approved as $link)
                        <tr wire:key="approved-{{ $link->id }}">
                            <td class="px-5 py-4 sm:px-6">
                                <p class="font-semibold text-navy">{{ $link->guardian->name }}</p>
                                <p class="mt-1 text-xs text-ink-soft">{{ str_ends_with($link->guardian->email, '.invalid') ? $link->guardian->username : $link->guardian->email }} · {{ ucfirst((string) $link->relationship) }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-navy">{{ $link->student->name }}</p>
                                <p class="mt-1 text-xs text-ink-soft">{{ $link->student->schoolClasses->pluck('name')->implode(', ') ?: 'Tanpa kelas' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-soft">{{ $link->reviewed_at?->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="resetPassword({{ $link->guardian_id }})" wire:confirm="Reset password {{ $link->guardian->name }}? Password lama tidak berlaku lagi." class="rounded-lg border border-line px-3 py-2 text-xs font-semibold text-navy transition hover:border-tosca hover:text-tosca-ink">Reset password</button>
                                    <button type="button" wire:click="revoke({{ $link->id }})" wire:confirm="Putuskan tautan ini? Orang tua tidak akan bisa melihat data anak lagi." class="rounded-lg border border-line px-3 py-2 text-xs font-semibold text-ink-soft transition hover:border-red-300 hover:text-red-700">Putuskan</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-ink-soft">Belum ada orang tua yang terhubung.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-theme.card>
</div>
