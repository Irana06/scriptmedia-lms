<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" wire:poll.60s>
    <button
        type="button"
        @click="open = ! open"
        class="relative flex size-10 items-center justify-center rounded-xl border border-line bg-white text-ink-soft shadow-sm transition hover:border-tosca hover:text-navy"
        aria-label="Notifikasi{{ $unreadTotal > 0 ? ", {$unreadTotal} belum dibaca" : '' }}"
        data-test="notification-bell"
    >
        <flux:icon.bell class="size-5" />
        @if ($unreadTotal > 0)
            <span data-test="notification-count" class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-orange px-1 text-[11px] font-semibold text-navy ring-2 ring-white">{{ $unreadTotal > 9 ? '9+' : $unreadTotal }}</span>
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-card border border-line bg-white shadow-xl shadow-navy/10"
    >
        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <p class="font-semibold text-navy">Notifikasi</p>
            @if ($unreadTotal > 0)
                <button type="button" wire:click="markAllRead" class="text-xs font-semibold text-tosca-ink hover:underline">Tandai semua dibaca</button>
            @endif
        </div>

        <div class="max-h-96 divide-y divide-line overflow-y-auto">
            @if ($newAnnouncements > 0)
                <button type="button" wire:click="openAnnouncements" class="flex w-full items-start gap-3 bg-orange/5 px-4 py-3 text-left transition hover:bg-offwhite">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-orange/15 text-orange-ink"><flux:icon.megaphone class="size-4" /></span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-navy">{{ $newAnnouncements }} pengumuman baru</span>
                        <span class="block text-xs text-ink-soft">Ketuk untuk membaca</span>
                    </span>
                </button>
            @endif

            @forelse ($notifications as $notification)
                <button type="button" wire:click="open('{{ $notification->id }}')" @class(['flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-offwhite', 'bg-tosca-tint/40' => $notification->read_at === null])>
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-tosca-tint text-tosca-ink">
                        <flux:icon :name="$notification->data['icon'] ?? 'bell'" class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-navy">{{ $notification->data['title'] ?? 'Notifikasi' }}</span>
                        <span class="mt-0.5 block line-clamp-2 text-xs text-ink-soft">{{ $notification->data['body'] ?? '' }}</span>
                        <span class="mt-1 block text-[11px] text-ink-soft/80">{{ $notification->created_at?->diffForHumans() }}</span>
                    </span>
                    @if ($notification->read_at === null)
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-orange" aria-label="Belum dibaca"></span>
                    @endif
                </button>
            @empty
                @if ($newAnnouncements === 0)
                    <div class="px-4 py-10 text-center">
                        <flux:icon.bell-slash class="mx-auto size-8 text-ink-soft/60" />
                        <p class="mt-2 text-sm text-ink-soft">Belum ada notifikasi.</p>
                    </div>
                @endif
            @endforelse
        </div>
    </div>
</div>
