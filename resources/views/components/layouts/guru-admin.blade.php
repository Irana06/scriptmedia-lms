@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-offwhite text-ink">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            <div
                x-cloak
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-navy/50 backdrop-blur-sm lg:hidden"
                aria-hidden="true"
                @click="sidebarOpen = false"
            ></div>

            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col overflow-y-auto bg-navy text-white transition-transform duration-200 lg:translate-x-0"
            >
                <div class="flex h-20 items-center justify-between border-b border-white/10 px-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                        <span class="flex size-10 items-center justify-center rounded-xl bg-orange text-navy shadow-lg shadow-orange/20">
                            <flux:icon.academic-cap class="size-6" />
                        </span>
                        <span>
                            <span class="block text-lg font-semibold leading-none text-white">RuangKelas</span>
                            <span class="mt-1 block text-[11px] uppercase tracking-[0.18em] text-white/55">ScriptMedia LMS</span>
                        </span>
                    </a>

                    <button type="button" class="rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Tutup menu" @click="sidebarOpen = false">
                        <flux:icon.x-mark class="size-5" />
                    </button>
                </div>

                <nav class="flex-1 px-4 py-6" aria-label="Navigasi utama">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/40">Ruang kerja</p>
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('dashboard') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition',
                            'bg-white/12 font-semibold text-white' => request()->routeIs('dashboard'),
                            'text-white/70 hover:bg-white/8 hover:text-white' => ! request()->routeIs('dashboard'),
                        ]) wire:navigate>
                            <flux:icon.home class="size-5" />
                            Dashboard
                        </a>
                        <a href="{{ route('students.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm text-white/70 transition hover:bg-white/8 hover:text-white" wire:navigate>
                            <flux:icon.users class="size-5" />
                            Kelas & Siswa
                        </a>
                        @role('admin')
                            <a href="{{ route('admin.academic.index') }}" @class([
                                'flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition',
                                'bg-white/12 font-semibold text-white' => request()->routeIs('admin.academic.*'),
                                'text-white/70 hover:bg-white/8 hover:text-white' => ! request()->routeIs('admin.academic.*'),
                            ]) wire:navigate>
                                <flux:icon.building-library class="size-5" />
                                Struktur Akademik
                            </a>
                            <a href="{{ route('admin.imports.index') }}" @class([
                                'flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition',
                                'bg-white/12 font-semibold text-white' => request()->routeIs('admin.imports.*'),
                                'text-white/70 hover:bg-white/8 hover:text-white' => ! request()->routeIs('admin.imports.*'),
                            ]) wire:navigate>
                                <flux:icon.document-arrow-up class="size-5" />
                                Import Akun
                            </a>
                            <a href="{{ route('admin.report-cards.index') }}" @class([
                                'flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition',
                                'bg-white/12 font-semibold text-white' => request()->routeIs('admin.report-cards.*'),
                                'text-white/70 hover:bg-white/8 hover:text-white' => ! request()->routeIs('admin.report-cards.*'),
                            ]) wire:navigate>
                                <flux:icon.document-text class="size-5" />
                                Rapor Siswa
                            </a>
                        @endrole
                        @role('guru')
                            <a href="{{ route('teacher.learning.index') }}" @class([
                                'flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition',
                                'bg-white/12 font-semibold text-white' => request()->routeIs('teacher.learning.*'),
                                'text-white/70 hover:bg-white/8 hover:text-white' => ! request()->routeIs('teacher.learning.*'),
                            ]) wire:navigate>
                                <flux:icon.book-open class="size-5" />
                                Pembelajaran
                            </a>
                            <a href="{{ route('teacher.evaluation.index') }}" @class([
                                'flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition',
                                'bg-white/12 font-semibold text-white' => request()->routeIs('teacher.evaluation.*'),
                                'text-white/70 hover:bg-white/8 hover:text-white' => ! request()->routeIs('teacher.evaluation.*'),
                            ]) wire:navigate>
                                <flux:icon.clipboard-document-check class="size-5" />
                                Nilai & Presensi
                            </a>
                        @endrole
                        <a href="{{ route('communications.index') }}" @class([
                            'flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition',
                            'bg-white/12 font-semibold text-white' => request()->routeIs('communications.*'),
                            'text-white/70 hover:bg-white/8 hover:text-white' => ! request()->routeIs('communications.*'),
                        ]) wire:navigate>
                            <flux:icon.megaphone class="size-5" />
                            Pengumuman & Kalender
                        </a>
                    </div>

                    <p class="mt-8 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/40">Pengaturan</p>
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm text-white/70 transition hover:bg-white/8 hover:text-white" wire:navigate>
                            <flux:icon.cog-6-tooth class="size-5" />
                            Profil
                        </a>
                    </div>
                </nav>

                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3 rounded-xl bg-white/8 p-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-tosca text-sm font-semibold text-white">
                            {{ auth()->user()?->initials() ?? 'GA' }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ auth()->user()?->name ?? 'Guru/Admin' }}</p>
                            <p class="truncate text-xs text-white/50">{{ auth()->user()?->hasRole('admin') ? 'Administrator' : 'Guru' }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg p-2 text-white/50 hover:bg-white/10 hover:text-white" aria-label="Keluar">
                                <flux:icon.arrow-right-start-on-rectangle class="size-5" />
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="min-h-screen lg:pl-72">
                <header class="sticky top-0 z-30 flex h-16 items-center border-b border-line bg-offwhite/90 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                    <button type="button" class="mr-3 rounded-xl border border-line bg-white p-2.5 text-navy lg:hidden" aria-label="Buka menu" @click="sidebarOpen = true">
                        <flux:icon.bars-3 class="size-5" />
                    </button>
                    <div>
                        <p class="text-xs text-ink-soft">SMA Nusantara</p>
                        <p class="text-sm font-semibold text-navy">Tahun Ajaran 2026/2027</p>
                    </div>
                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" class="relative rounded-xl border border-line bg-white p-2.5 text-ink-soft transition hover:text-navy" aria-label="Notifikasi">
                            <flux:icon.bell class="size-5" />
                            <span class="absolute right-2 top-2 size-2 rounded-full bg-orange ring-2 ring-white"></span>
                        </button>
                    </div>
                </header>

                <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
