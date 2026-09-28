<x-dynamic-component :component="$layout" :title="$announcement->title">
    <div class="mx-auto max-w-3xl space-y-6">
        <a href="{{ $listUrl }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-tosca-ink hover:underline" wire:navigate>
            <flux:icon.arrow-left class="size-4" />
            Semua pengumuman
        </a>

        <x-theme.card>
            <article>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-orange/15 text-orange-ink"><flux:icon.megaphone class="size-5" /></span>
                    <x-theme.badge :tone="$announcement->target === 'all' ? 'navy' : 'orange'">{{ $announcement->target === 'all' ? 'Seluruh sekolah' : 'Kelas '.$announcement->schoolClass?->name }}</x-theme.badge>
                </div>
                <h1 class="mt-4 text-2xl leading-tight text-navy sm:text-3xl">{{ $announcement->title }}</h1>
                <p class="mt-2 text-sm text-ink-soft">
                    {{ $announcement->creator?->name ?? 'Sekolah' }} ·
                    <time datetime="{{ $announcement->created_at?->toIso8601String() }}">{{ $announcement->created_at?->translatedFormat('l, d F Y · H:i') }}</time>
                </p>
                <div class="mt-6 whitespace-pre-line border-t border-line pt-6 text-base leading-7 text-ink">{{ $announcement->body }}</div>
            </article>
        </x-theme.card>

        @if ($others->isNotEmpty())
            <section>
                <h2 class="text-lg text-navy">Pengumuman lainnya</h2>
                <x-theme.card :padding="false" class="mt-3 overflow-hidden">
                    <div class="divide-y divide-line">
                        @foreach ($others as $other)
                            <a href="{{ route('announcements.show', $other) }}" class="flex items-start gap-3 px-5 py-4 transition hover:bg-offwhite sm:px-6" wire:navigate>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold text-navy">{{ $other->title }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-soft">{{ $other->created_at?->diffForHumans() }} · {{ $other->target === 'all' ? 'Seluruh sekolah' : 'Kelas '.$other->schoolClass?->name }}</span>
                                </span>
                                @if (in_array($other->id, $unreadIds, true))
                                    <span class="mt-1.5 size-2 shrink-0 rounded-full bg-orange" aria-label="Belum dibaca"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </x-theme.card>
            </section>
        @endif
    </div>
</x-dynamic-component>
