<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0A1628">
    <title>Wartungsarbeiten — DKGZ</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white">
    <div class="mx-auto flex min-h-screen w-full max-w-[680px] flex-col justify-center px-6 py-24">
        <p class="font-mono text-sm tabular-nums text-gray-400">Fehler 503</p>
        <h1 class="pt-4 text-h1 font-bold text-navy-700">Wartungsarbeiten</h1>
        <p class="measure-lead pt-4 text-lead leading-relaxed text-gray-600">{{ $message }}</p>
        <span class="mt-8 flex h-[3px] w-10" aria-hidden="true">
            <span class="flex-1 bg-flag-black"></span>
            <span class="flex-1 bg-flag-red"></span>
            <span class="flex-1 bg-flag-gold"></span>
        </span>
        <p class="pt-2 text-xs text-gray-400">Deutsche KFZ-Gutachterzentrale</p>
    </div>
</body>
</html>
