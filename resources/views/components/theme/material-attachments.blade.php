@props(['files'])

<div x-data="{ preview: null }" x-effect="document.body.style.overflow = preview ? 'hidden' : ''" @keydown.escape.window="preview = null">
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($files as $file)
            @php
                $youtubeId = $file->youtubeVideoId();
                $previewUrl = $file->isExternal()
                    ? $file->file_path
                    : route('learning.files.material', ['materialFile' => $file, 'preview' => 1]);
                $openUrl = $file->isExternal() ? $file->file_path : route('learning.files.material', $file);
            @endphp

            @if ($youtubeId)
                <article class="overflow-hidden rounded-2xl border border-line bg-white sm:col-span-2">
                    <div class="aspect-video bg-navy">
                        <iframe
                            class="size-full"
                            src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}"
                            title="Video YouTube materi: {{ $file->material->title }}"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        ></iframe>
                    </div>
                    <div class="flex items-center justify-between gap-3 p-3">
                        <div class="min-w-0"><p class="text-sm font-semibold text-navy">Video YouTube</p><p class="truncate text-xs text-ink-soft">Tonton langsung tanpa meninggalkan materi</p></div>
                        <a href="{{ $openUrl }}" target="_blank" rel="noopener noreferrer" class="shrink-0 text-xs font-semibold text-tosca-ink">Buka di YouTube ↗</a>
                    </div>
                </article>
            @elseif ($file->type === 'image')
                <button
                    type="button"
                    class="group overflow-hidden rounded-2xl border border-line bg-white text-left transition hover:-translate-y-0.5 hover:border-tosca/45 hover:shadow-lg"
                    x-on:click="preview = { type: 'image', src: @js($previewUrl), title: @js('Foto materi '.$loop->iteration) }"
                >
                    <span class="relative block aspect-video overflow-hidden bg-offwhite">
                        <img src="{{ $previewUrl }}" alt="Foto pendukung materi {{ $file->material->title }}" loading="lazy" class="size-full object-cover transition duration-300 group-hover:scale-105" />
                        <span class="absolute bottom-2 right-2 rounded-full bg-navy/90 px-3 py-1.5 text-xs font-semibold text-white">Perbesar</span>
                    </span>
                    <span class="block p-3"><span class="text-sm font-semibold text-navy">Foto materi</span><span class="mt-0.5 block text-xs text-ink-soft">Ketuk untuk melihat layar penuh</span></span>
                </button>
            @elseif ($file->type === 'video')
                <button
                    type="button"
                    class="group overflow-hidden rounded-2xl border border-line bg-white text-left transition hover:-translate-y-0.5 hover:border-tosca/45 hover:shadow-lg"
                    x-on:click="preview = { type: 'video', src: @js($previewUrl), title: @js('Video materi '.$loop->iteration) }"
                >
                    <span class="relative flex aspect-video items-center justify-center overflow-hidden bg-gradient-to-br from-navy to-navy-mid">
                        <video src="{{ $previewUrl }}" preload="metadata" muted playsinline class="absolute inset-0 size-full object-cover opacity-55"></video>
                        <span class="relative flex size-14 items-center justify-center rounded-full bg-white text-navy shadow-lg"><flux:icon.play class="ml-0.5 size-6" /></span>
                    </span>
                    <span class="block p-3"><span class="text-sm font-semibold text-navy">Video materi</span><span class="mt-0.5 block text-xs text-ink-soft">Ketuk untuk memutar layar penuh</span></span>
                </button>
            @elseif ($file->type === 'pdf')
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer" class="flex min-h-28 self-start items-center gap-4 rounded-2xl border border-line bg-white p-4 transition hover:-translate-y-0.5 hover:border-orange/50 hover:shadow-lg">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-orange/15 text-orange-ink"><flux:icon.document-text class="size-6" /></span>
                    <span class="min-w-0"><span class="block text-sm font-semibold text-navy">Dokumen PDF</span><span class="mt-1 block text-xs leading-5 text-ink-soft">Buka atau unduh materi pada tab baru</span></span>
                    <flux:icon.arrow-top-right-on-square class="ml-auto size-5 shrink-0 text-tosca" />
                </a>
            @else
                <a href="{{ $openUrl }}" target="_blank" rel="noopener noreferrer" class="flex min-h-28 self-start items-center gap-4 rounded-2xl border border-line bg-white p-4 transition hover:-translate-y-0.5 hover:border-tosca/45 hover:shadow-lg">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-tosca-tint text-tosca-ink"><flux:icon.link class="size-6" /></span>
                    <span class="min-w-0"><span class="block text-sm font-semibold text-navy">Tautan pendukung</span><span class="mt-1 block truncate text-xs text-ink-soft">{{ parse_url($file->file_path, PHP_URL_HOST) ?: $file->file_path }}</span></span>
                    <flux:icon.arrow-top-right-on-square class="ml-auto size-5 shrink-0 text-tosca" />
                </a>
            @endif
        @empty
            <span class="rounded-xl bg-offwhite px-3 py-2 text-sm text-ink-soft sm:col-span-2">Belum ada lampiran pada materi ini.</span>
        @endforelse
    </div>

    <template x-teleport="body">
        <div
            x-cloak
            x-show="preview"
            x-transition.opacity
            role="dialog"
            aria-modal="true"
            aria-label="Pratinjau media materi"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-navy/95 p-3 sm:p-8"
            x-on:click.self="preview = null"
        >
            <button type="button" x-on:click="preview = null" class="absolute right-3 top-3 z-10 flex size-11 items-center justify-center rounded-full bg-white text-navy shadow-lg sm:right-6 sm:top-6" aria-label="Tutup pratinjau"><flux:icon.x-mark class="size-6" /></button>
            <template x-if="preview?.type === 'image'">
                <img :src="preview.src" :alt="preview.title" class="max-h-[calc(100dvh-1.5rem)] max-w-full rounded-xl object-contain shadow-2xl sm:max-h-[calc(100dvh-4rem)]" />
            </template>
            <template x-if="preview?.type === 'video'">
                <video :src="preview.src" controls autoplay playsinline class="max-h-[calc(100dvh-1.5rem)] max-w-full rounded-xl bg-black shadow-2xl sm:max-h-[calc(100dvh-4rem)]"></video>
            </template>
        </div>
    </template>
</div>
