<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">

    <title inertia>{{ config('app.name') }}</title>

    @vite('resources/js/app.ts')
    @inertiaHead
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    @inertia
</body>
</html>