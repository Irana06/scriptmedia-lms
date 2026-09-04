@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-offwhite antialiased">
        <main class="relative isolate flex min-h-svh items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
            <div aria-hidden="true" class="absolute -left-28 top-8 -z-10 size-80 rounded-full bg-tosca/10 blur-3xl"></div>
            <div aria-hidden="true" class="absolute -bottom-36 right-0 -z-10 size-96 rounded-full bg-orange/10 blur-3xl"></div>

            <div class="auth-shell grid min-w-0 overflow-hidden rounded-card border border-line bg-white shadow-[0_24px_70px_rgba(11,37,69,0.12)] lg:grid-cols-[0.9fr_1.1fr]">
                <aside class="relative hidden min-h-[620px] overflow-hidden bg-navy p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <div aria-hidden="true" class="absolute -right-20 -top-20 size-64 rounded-full border-[36px] border-white/5"></div>
                    <div aria-hidden="true" class="absolute -bottom-24 -left-20 size-72 rounded-full bg-tosca/15"></div>

                    <a href="{{ route('home') }}" class="relative inline-flex items-center gap-3" wire:navigate>
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-tosca text-navy shadow-lg shadow-black/10">
                            <flux:icon.academic-cap class="size-7" />
                        </span>
                        <span>
                            <span class="block text-xl font-semibold tracking-tight">RuangKelas</span>
                            <span class="block text-xs text-white/65">Learning Management System</span>
                        </span>
                    </a>

                    <div class="relative max-w-sm">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/80">Portal pembelajaran sekolah</span>
                        <h1 class="mt-5 text-4xl font-semibold leading-tight text-white">Belajar, mengajar, dan bertumbuh dalam satu ruang.</h1>
                        <p class="mt-4 max-w-xs text-sm leading-6 text-white/65">Akses materi, tugas, presensi, dan perkembangan belajar dengan akun sekolah Anda.</p>
                    </div>

                    <div class="relative flex items-center gap-3 border-t border-white/10 pt-6 text-sm text-white/65">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-white/10 text-tosca">
                            <flux:icon.shield-check class="size-5" />
                        </span>
                        <span>Akses aman untuk warga sekolah</span>
                    </div>
                </aside>

                <section class="auth-panel flex min-h-[560px] min-w-0 flex-col px-6 py-7 sm:px-12 sm:py-9 lg:min-h-[620px] lg:px-16">
                    <div class="flex items-center justify-between border-b border-line pb-5 lg:hidden">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" wire:navigate>
                            <span class="flex size-10 items-center justify-center rounded-xl bg-navy text-white">
                                <flux:icon.academic-cap class="size-6" />
                            </span>
                            <span class="font-semibold text-navy">RuangKelas</span>
                        </a>
                        <span class="hidden rounded-full bg-tosca-tint px-3 py-1 text-xs font-medium text-tosca-ink sm:inline-flex">Portal sekolah</span>
                    </div>

                    <div class="flex flex-1 items-center py-8">
                        <div class="auth-form mx-auto min-w-0">
                            {{ $slot }}
                        </div>
                    </div>

                    <p class="text-center text-xs text-ink-soft/75">&copy; {{ now()->year }} RuangKelas · Sistem pembelajaran sekolah</p>
                </section>
            </div>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
