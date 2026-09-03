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

                <nav class="ml-10 hidden items-center gap-2 md:flex" aria-label="Navigasi siswa">
                    <a href="{{ route('dashboard.siswa') }}" class="rounded-full bg-tosca-tint px-4 py-2 text-sm font-semibold text-navy-mid" wire:navigate>Beranda</a>
                    <a href="{{ route('student.learning.index', ['tab' => 'materials']) }}" class="rounded-full px-4 py-2 text-sm text-ink-soft hover:bg-offwhite hover:text-navy" wire:navigate>Materi</a>
                    <a href="{{ route('student.learning.index', ['tab' => 'assignments']) }}" class="rounded-full px-4 py-2 text-sm text-ink-soft hover:bg-offwhite hover:text-navy" wire:navigate>Tugas</a>
                    <a href="{{ route('student.evaluation.index') }}" class="rounded-full px-4 py-2 text-sm text-ink-soft hover:bg-offwhite hover:text-navy" wire:navigate>Nilai</a>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <button type="button" class="relative rounded-xl p-2.5 text-ink-soft hover:bg-offwhite hover:text-navy" aria-label="Notifikasi">
                        <flux:icon.bell class="size-5" />
                        <span class="absolute right-2 top-2 size-2 rounded-full bg-orange ring-2 ring-white"></span>
                    </button>
                    <a href="{{ route('profile.edit') }}" class="flex size-9 items-center justify-center rounded-full bg-tosca text-xs font-semibold text-white" aria-label="Buka profil" wire:navigate>
                        {{ auth()->user()?->initials() ?? 'SN' }}
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 pb-24 sm:px-6 md:py-8 md:pb-10">
            {{ $slot }}
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2 md:hidden" aria-label="Navigasi bawah siswa">
            <div class="mx-auto grid max-w-md grid-cols-4">
                <a href="{{ route('dashboard.siswa') }}" class="flex flex-col items-center gap-1 rounded-xl px-2 py-1.5 text-navy" wire:navigate>
                    <flux:icon.home class="size-5" />
                    <span class="text-[11px] font-semibold">Beranda</span>
                </a>
                <a href="{{ route('student.learning.index', ['tab' => 'materials']) }}" class="flex flex-col items-center gap-1 rounded-xl px-2 py-1.5 text-ink-soft" wire:navigate>
                    <flux:icon.book-open class="size-5" />
                    <span class="text-[11px]">Materi</span>
                </a>
                <a href="{{ route('student.learning.index', ['tab' => 'assignments']) }}" class="relative flex flex-col items-center gap-1 rounded-xl px-2 py-1.5 text-ink-soft" wire:navigate>
                    <span class="absolute right-4 top-0 size-2 rounded-full bg-orange"></span>
                    <flux:icon.clipboard-document-check class="size-5" />
                    <span class="text-[11px]">Tugas</span>
                </a>
                <a href="{{ route('student.evaluation.index') }}" class="flex flex-col items-center gap-1 rounded-xl px-2 py-1.5 text-ink-soft" wire:navigate>
                    <flux:icon.chart-bar class="size-5" />
                    <span class="text-[11px]">Nilai</span>
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
