<x-layouts::auth title="Daftar Orang Tua">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-center gap-3 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-tosca-tint text-tosca-ink">
                <flux:icon.home-modern class="size-6" />
            </span>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-tosca-ink">Portal orang tua</p>
                <x-auth-header title="Buat akun orang tua" description="Pantau jadwal, kehadiran, dan tugas anak di sekolah." />
            </div>
        </div>

        <form method="POST" action="{{ route('ortu.register.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input name="name" label="Nama lengkap" :value="old('name')" type="text" required autofocus autocomplete="name" placeholder="Nama sesuai KTP" />
            @error('name')
                <p class="-mt-3 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="email" label="Email" :value="old('email')" type="email" required autocomplete="email" placeholder="nama@email.com" />
            @error('email')
                <p class="-mt-3 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="phone" label="Nomor HP / WhatsApp" :value="old('phone')" type="tel" required autocomplete="tel" placeholder="08xxxxxxxxxx" />
            @error('phone')
                <p class="-mt-3 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="password" label="Password" type="password" required autocomplete="new-password" placeholder="Password" viewable />
            @error('password')
                <p class="-mt-3 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="password_confirmation" label="Ulangi password" type="password" required autocomplete="new-password" placeholder="Ulangi password" viewable />

            <div class="rounded-2xl bg-tosca-tint/60 p-4 text-xs leading-5 text-navy">
                Setelah mendaftar, ajukan tautan ke anak dari beranda. Sekolah akan memastikan Anda wali siswa tersebut sebelum jadwal dan kehadirannya bisa dilihat.
            </div>

            <flux:button variant="primary" type="submit" icon:trailing="arrow-right" class="min-h-11 w-full" data-test="guardian-register-button">
                Buat akun
            </flux:button>
        </form>

        <div class="rounded-2xl border border-line bg-offwhite p-4 text-center text-sm text-ink-soft">
            <span>Sudah punya akun?</span>
            <flux:link class="font-semibold" :href="route('ortu.login')" wire:navigate>Masuk</flux:link>
        </div>
    </div>
</x-layouts::auth>
