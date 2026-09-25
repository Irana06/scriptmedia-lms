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
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@endif
<link rel="manifest" href="{{ route('manifest') }}">
<meta name="theme-color" content="#0B2545">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ \App\Models\SchoolProfile::current()->brandName() }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register(@js(asset('sw.js'))).catch(() => {}));
    }
</script>

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    window.localStorage.setItem('flux.appearance', 'light');
</script>
@fluxAppearance
