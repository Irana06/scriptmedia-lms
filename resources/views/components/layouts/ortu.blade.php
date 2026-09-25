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
                <a href="{{ route('dashboard.ortu') }}" class="flex items-center gap-2.5" wire:navigate>
                    <x-school-mark class="size-9 rounded-xl" tone="bg-navy text-orange" icon="home-modern" icon-class="size-5" />
                    <span class="flex min-w-0 flex-col leading-tight">
                        <span class="max-w-48 truncate text-lg font-semibold text-navy sm:max-w-none">{{ \App\Models\SchoolProfile::current()->brandName() }}</span>
                        <span class="text-[11px] uppercase tracking-[0.16em] text-ink-soft">Orang tua</span>
                    </span>
                </a>

                <div class="ml-auto flex items-center gap-2">
                    <a href="{{ route('profile.edit') }}" class="flex size-9 items-center justify-center rounded-full bg-tosca text-xs font-semibold text-navy" aria-label="Buka profil" wire:navigate>
                        {{ auth()->user()?->initials() ?? 'OT' }}
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

        <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 md:py-8">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
