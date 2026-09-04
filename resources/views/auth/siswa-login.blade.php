<x-layouts::auth title="Masuk Siswa">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-center gap-3 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-orange/15 text-orange-ink">
                <flux:icon.academic-cap class="size-6" />
            </span>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-orange-ink">Portal siswa</p>
                <x-auth-header title="Siap melanjutkan belajar?" description="Masuk dengan NISN dan password dari sekolah." />
            </div>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('siswa.login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="username"
                label="NISN"
                :value="old('username')"
                type="text"
                inputmode="numeric"
                required
                autofocus
                autocomplete="username"
                placeholder="Contoh: 0012345678"
            />

            <flux:input
                name="password"
                label="Password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Password"
                viewable
            />

            <flux:checkbox name="remember" label="Ingat saya" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" icon:trailing="arrow-right" class="min-h-11 w-full" data-test="student-login-button">
                Masuk ke RuangKelas
            </flux:button>
        </form>

        <div class="rounded-2xl border border-line bg-offwhite p-4 text-center text-sm text-ink-soft">
            <span>Admin atau guru?</span>
            <flux:link class="font-semibold" :href="route('login')" wire:navigate>Buka portal staf</flux:link>
        </div>
    </div>
</x-layouts::auth>
