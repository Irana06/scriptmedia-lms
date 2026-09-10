@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-offwhite text-ink">
        <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-6xl items-center px-4 sm:px-6">
                <a href="{{ route('dashboard.siswa') }}" class="flex items-center gap-2.5" wire:navigate>
                    <span class="flex size-9 items-center justify-center rounded-xl bg-navy text-orange">
                        <flux:icon.academic-cap class="size-5" />
                    </span>
                    <span class="text-lg font-semibold text-navy">RuangKelas</span>
                </a>

                <nav class="ml-10 hidden items-center gap-1 rounded-full bg-offwhite p-1 md:flex" aria-label="Navigasi siswa">
                    <a href="{{ route('dashboard.siswa') }}" @class(['rounded-full px-4 py-2 text-sm transition', 'bg-white font-semibold text-navy shadow-sm' => request()->routeIs('dashboard.siswa'), 'text-ink-soft hover:text-navy' => ! request()->routeIs('dashboard.siswa')]) wire:navigate>Beranda</a>
                    <a href="{{ route('student.learning.index', ['tab' => 'materials']) }}" @class(['rounded-full px-4 py-2 text-sm transition', 'bg-white font-semibold text-navy shadow-sm' => request()->routeIs('student.learning.*'), 'text-ink-soft hover:text-navy' => ! request()->routeIs('student.learning.*')]) wire:navigate>Belajar</a>
                    <a href="{{ route('student.activities.index') }}" @class(['rounded-full px-4 py-2 text-sm transition', 'bg-white font-semibold text-navy shadow-sm' => request()->routeIs('student.activities.*'), 'text-ink-soft hover:text-navy' => ! request()->routeIs('student.activities.*')]) wire:navigate>Aktivitas</a>
                    <a href="{{ route('student.evaluation.index') }}" @class(['rounded-full px-4 py-2 text-sm transition', 'bg-white font-semibold text-navy shadow-sm' => request()->routeIs('student.evaluation.*'), 'text-ink-soft hover:text-navy' => ! request()->routeIs('student.evaluation.*')]) wire:navigate>Nilai</a>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <a href="{{ route('profile.edit') }}" class="flex size-9 items-center justify-center rounded-full bg-tosca text-xs font-semibold text-navy" aria-label="Buka profil" wire:navigate>
                        {{ auth()->user()?->initials() ?? 'SN' }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex size-9 items-center justify-center rounded-full border border-line text-ink-soft transition hover:border-orange hover:text-orange-ink" aria-label="Keluar" data-test="logout-button">
                            <flux:icon.arrow-right-start-on-rectangle class="size-5" />
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-6 pb-24 sm:px-6 md:py-8 md:pb-10">
            {{ $slot }}
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2 md:hidden" aria-label="Navigasi bawah siswa">
            <div class="mx-auto grid max-w-lg grid-cols-5">
                <a href="{{ route('dashboard.siswa') }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-2 py-1.5 transition', 'bg-tosca-tint font-semibold text-navy' => request()->routeIs('dashboard.siswa'), 'text-ink-soft' => ! request()->routeIs('dashboard.siswa')]) wire:navigate>
                    <flux:icon.home class="size-5" />
                    <span class="text-xs">Beranda</span>
                </a>
                <a href="{{ route('student.learning.index', ['tab' => 'materials']) }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-2 py-1.5 transition', 'bg-tosca-tint font-semibold text-navy' => request()->routeIs('student.learning.*') && request('tab', 'materials') === 'materials', 'text-ink-soft' => ! (request()->routeIs('student.learning.*') && request('tab', 'materials') === 'materials')]) wire:navigate>
                    <flux:icon.book-open class="size-5" />
                    <span class="text-xs">Materi</span>
                </a>
                <a href="{{ route('student.learning.index', ['tab' => 'assignments']) }}" @class(['relative flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-2 py-1.5 transition', 'bg-tosca-tint font-semibold text-navy' => request()->routeIs('student.learning.*') && request('tab') === 'assignments', 'text-ink-soft' => ! (request()->routeIs('student.learning.*') && request('tab') === 'assignments')]) wire:navigate>
                    <span class="absolute right-4 top-0 size-2 rounded-full bg-orange"></span>
                    <flux:icon.clipboard-document-check class="size-5" />
                    <span class="text-xs">Tugas</span>
                </a>
                <a href="{{ route('student.activities.index') }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-1 py-1.5 transition', 'bg-tosca-tint font-semibold text-navy' => request()->routeIs('student.activities.*'), 'text-ink-soft' => ! request()->routeIs('student.activities.*')]) wire:navigate>
                    <flux:icon.bell-alert class="size-5" />
                    <span class="text-xs">Aktivitas</span>
                </a>
                <a href="{{ route('student.evaluation.index') }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-2 py-1.5 transition', 'bg-tosca-tint font-semibold text-navy' => request()->routeIs('student.evaluation.*'), 'text-ink-soft' => ! request()->routeIs('student.evaluation.*')]) wire:navigate>
                    <flux:icon.chart-bar class="size-5" />
                    <span class="text-xs">Nilai</span>
                </a>
            </div>
        </nav>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
