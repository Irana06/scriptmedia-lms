<x-layouts::auth title="Masuk Orang Tua">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-center gap-3 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-tosca-tint text-tosca-ink">
                <flux:icon.home-modern class="size-6" />
            </span>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-tosca-ink">Portal orang tua</p>
                <x-auth-header title="Selamat datang" description="Masuk untuk memantau jadwal, kehadiran, dan tugas anak." />
            </div>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="email"
                label="Email atau username"
                :value="old('email')"
                type="text"
                required
                autofocus
                autocomplete="username"
                autocapitalize="none"
                spellcheck="false"
                placeholder="nama@email.com"
            />
            @error('email')
                <p class="-mt-3 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="password" label="Password" type="password" required autocomplete="current-password" placeholder="Password" viewable />

            <flux:checkbox name="remember" label="Ingat saya" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" icon:trailing="arrow-right" class="min-h-11 w-full" data-test="guardian-login-button">
                Masuk
            </flux:button>
        </form>

        <div class="rounded-2xl border border-line bg-offwhite p-4 text-center text-sm text-ink-soft">
            <span>Belum punya akun?</span>
            <flux:link class="font-semibold" :href="route('ortu.register')" wire:navigate>Daftar sebagai orang tua</flux:link>
        </div>
    </div>
</x-layouts::auth>
