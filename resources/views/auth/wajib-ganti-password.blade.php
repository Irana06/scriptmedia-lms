<x-layouts::auth title="Ganti Password">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Buat password baru" description="Untuk keamanan akun, ganti password sementara sebelum mulai belajar." />

        <div class="rounded-xl border border-orange/30 bg-orange/10 p-4 text-sm leading-6 text-navy">
            Password baru hanya boleh diketahui oleh kamu. Jangan berikan kepada teman atau pihak lain.
        </div>

        <form method="POST" action="{{ route('siswa.password.update') }}" class="flex flex-col gap-6">
            @csrf
            @method('PUT')

            <flux:input
                name="password"
                label="Password baru"
                type="password"
                required
                autofocus
                autocomplete="new-password"
                viewable
            />

            <flux:input
                name="password_confirmation"
                label="Ulangi password baru"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full">
                Simpan password dan lanjutkan
            </flux:button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <flux:button variant="ghost" type="submit">Keluar dari akun</flux:button>
        </form>
    </div>
</x-layouts::auth>
