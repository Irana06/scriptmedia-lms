<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php($brandName = \App\Models\SchoolProfile::current()->isConfigured() ? \App\Models\SchoolProfile::current()->name : config('app.name', 'RuangKelas'))
<title>
    {{ filled($title ?? null) ? $title.' - '.$brandName : $brandName }}
</title>

@php($schoolLogo = \App\Models\SchoolProfile::current()->logoUrl())
@if ($schoolLogo)
    <link rel="icon" href="{{ $schoolLogo }}">
    <link rel="apple-touch-icon" href="{{ $schoolLogo }}">
@else
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
@endif

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    window.localStorage.setItem('flux.appearance', 'light');
</script>
@fluxAppearance
