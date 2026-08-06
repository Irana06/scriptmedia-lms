<x-layouts::auth title="Masuk Siswa">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Masuk sebagai siswa" description="Gunakan NISN dan password yang diberikan sekolah." />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('siswa.login.store') }}" class="flex flex-col gap-6">
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
                placeholder="Masukkan NISN"
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

            <flux:button variant="primary" type="submit" class="w-full" data-test="student-login-button">
                Masuk ke RuangKelas
            </flux:button>
        </form>

        <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            Admin atau guru?
            <flux:link :href="route('login')" wire:navigate>Masuk dengan email</flux:link>
        </p>
    </div>
</x-layouts::auth>
