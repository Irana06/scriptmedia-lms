<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'RuangKelas'])
    </head>
    <body class="min-h-screen bg-offwhite text-ink">
        <main class="relative isolate flex min-h-screen items-center overflow-hidden px-4 py-10 sm:px-6 lg:px-8">
            <div class="absolute inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-tosca-tint to-transparent"></div>
            <div class="absolute -right-20 top-16 -z-10 size-72 rounded-full border-[48px] border-orange/10"></div>

            <div class="mx-auto grid w-full max-w-6xl overflow-hidden rounded-card border border-line bg-white shadow-xl shadow-navy/10 lg:grid-cols-[1.15fr_.85fr]">
                <section class="relative overflow-hidden bg-gradient-to-br from-navy to-navy-mid px-6 py-10 text-white sm:px-10 lg:px-14 lg:py-16">
                    <div class="absolute -bottom-24 -right-20 size-72 rounded-full border-[44px] border-white/5"></div>
                    <div class="relative max-w-xl">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-orange text-navy">
                                <flux:icon.academic-cap class="size-6" />
                            </span>
                            <div>
                                <p class="text-xl font-semibold">RuangKelas</p>
                                <p class="text-xs uppercase tracking-[0.18em] text-white/55">ScriptMedia LMS</p>
                            </div>
                        </div>

                        <x-theme.badge tone="orange" class="mt-12">Portal sekolah</x-theme.badge>
                        <h1 class="mt-5 text-3xl leading-tight text-white sm:text-4xl">Satu ruang untuk kegiatan belajar di sekolah.</h1>
                        <p class="mt-5 max-w-lg text-base leading-7 text-white/70">Akses materi, tugas, penilaian, presensi, pengumuman, dan administrasi kelas sesuai peranmu.</p>

                        <div class="mt-10 grid gap-3 sm:grid-cols-3">
                            @foreach ([['book-open', 'Materi & tugas'], ['clipboard-document-check', 'Nilai & presensi'], ['megaphone', 'Info sekolah']] as [$icon, $label])
                                <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                                    <flux:icon :name="$icon" class="size-5 text-orange-light" />
                                    <p class="mt-3 text-sm font-semibold text-white">{{ $label }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="flex items-center px-6 py-10 sm:px-10 lg:px-12">
                    <div class="w-full">
                        <p class="text-sm font-semibold text-tosca-ink">Pilih akses</p>
                        <h2 class="mt-2 text-2xl">Masuk ke akunmu</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-soft">Gunakan jalur masuk yang sesuai dengan peran di sekolah.</p>

                        <div class="mt-8 space-y-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="group flex min-h-20 items-center gap-4 rounded-2xl border border-line p-4 transition hover:border-tosca hover:bg-tosca-tint/40">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-navy text-white"><flux:icon.home class="size-5" /></span>
                                    <span class="min-w-0 flex-1"><strong class="block text-navy">Buka dashboard</strong><span class="mt-1 block text-sm text-ink-soft">Lanjutkan sebagai {{ auth()->user()->name }}</span></span>
                                    <flux:icon.arrow-right class="size-5 text-tosca transition group-hover:translate-x-1" />
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="group flex min-h-20 items-center gap-4 rounded-2xl border border-line p-4 transition hover:border-tosca hover:bg-tosca-tint/40">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-navy text-white"><flux:icon.user-group class="size-5" /></span>
                                    <span class="min-w-0 flex-1"><strong class="block text-navy">Admin & guru</strong><span class="mt-1 block text-sm text-ink-soft">Masuk menggunakan email sekolah</span></span>
                                    <flux:icon.arrow-right class="size-5 text-tosca transition group-hover:translate-x-1" />
                                </a>
                                <a href="{{ route('siswa.login') }}" class="group flex min-h-20 items-center gap-4 rounded-2xl border border-line p-4 transition hover:border-orange hover:bg-orange/5">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-orange text-navy"><flux:icon.academic-cap class="size-5" /></span>
                                    <span class="min-w-0 flex-1"><strong class="block text-navy">Siswa</strong><span class="mt-1 block text-sm text-ink-soft">Masuk menggunakan NISN</span></span>
                                    <flux:icon.arrow-right class="size-5 text-orange transition group-hover:translate-x-1" />
                                </a>
                            @endauth
                        </div>

                        @if(config('app.demo_mode') && ! auth()->check())
                            <div class="mt-6 rounded-2xl border border-tosca/25 bg-tosca-tint/45 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-tosca text-white"><flux:icon.beaker class="size-5" /></span>
                                    <div><p class="font-semibold text-navy">Coba akun demo</p><p class="mt-1 text-xs leading-5 text-ink-soft">Untuk preview saja — pilih peran untuk masuk tanpa mengetik kredensial.</p></div>
                                </div>
                                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                                    @foreach ([['admin', 'Admin', 'shield-check'], ['guru', 'Guru', 'academic-cap'], ['siswa', 'Siswa', 'user']] as [$role, $label, $icon])
                                        <form method="POST" action="{{ route('demo.login', $role) }}">
                                            @csrf
                                            <button type="submit" class="group flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border border-line bg-white px-3 py-2 text-sm font-semibold text-navy transition hover:-translate-y-px hover:border-tosca hover:text-tosca-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tosca focus-visible:ring-offset-2"><flux:icon :name="$icon" class="size-4 text-tosca" />Masuk {{ $label }}<flux:icon.arrow-right class="size-4 transition group-hover:translate-x-0.5" /></button>
                                        </form>
                                    @endforeach
                                </div>
                                <p class="mt-3 text-xs text-ink-soft">Data akun dibuat oleh <code class="rounded bg-white px-1.5 py-0.5 text-navy">DemoSeeder</code>.</p>
                            </div>
                        @endif

                        <p class="mt-8 text-sm leading-6 text-ink-soft">Siswa tidak dapat mendaftar sendiri. Hubungi admin sekolah jika belum menerima akun atau memerlukan reset password.</p>
                    </div>
                </section>
            </div>
        </main>
        @fluxScripts
    </body>
</html>
