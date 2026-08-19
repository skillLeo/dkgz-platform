<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0A1628">

    {{-- No preconnect to any font CDN: IBM Plex is bundled from @fontsource. --}}

    <title inertia>{{ config('app.name', 'DKGZ Deutsche KFZ-Gutachterzentrale') }}</title>

    <link rel="icon" href="{{ $faviconUrl ?? '/favicon.ico' }}" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    {{-- Admin-configured brand colours, injected as custom properties so a
         colour change in the admin rethemes the site without a rebuild. --}}
    @if (! empty($brandCss))
        <style>:root{ {!! $brandCss !!} }</style>
    @endif

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-white text-gray-800 antialiased">
    @inertia
</body>
</html>
