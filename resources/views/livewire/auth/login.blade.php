<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-center gap-3 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-tosca-tint text-tosca-ink">
                <flux:icon.identification class="size-6" />
            </span>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-tosca-ink">Portal staf</p>
                <x-auth-header title="Selamat datang kembali" description="Masuk dengan email akun admin atau guru Anda." />
            </div>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Alamat email"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="nama@sekolah.id"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    label="Password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        Lupa password?
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" label="Ingat saya" :checked="old('remember')" />

            <div class="flex items-center justify-end pt-1">
                <flux:button variant="primary" type="submit" icon:trailing="arrow-right" class="min-h-11 w-full" data-test="login-button">
                    Masuk ke RuangKelas
                </flux:button>
            </div>
        </form>

        <div class="rounded-2xl border border-line bg-offwhite p-4 text-center text-sm text-ink-soft">
            <span>Siswa masuk menggunakan NISN.</span>
            <flux:link class="font-semibold" :href="route('siswa.login')" wire:navigate>Buka portal siswa</flux:link>
        </div>
    </div>
</x-layouts::auth>
