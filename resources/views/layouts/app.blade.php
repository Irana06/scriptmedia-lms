@role('siswa')
    <x-layouts.siswa :title="$title ?? null">
        {{ $slot }}
    </x-layouts.siswa>
@else
    <x-layouts.guru-admin :title="$title ?? null">
        {{ $slot }}
    </x-layouts.guru-admin>
@endrole
