<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso denegado · Aramovil</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-xl px-6 py-16">
        <p class="font-semibold text-cyan-900">Aramovil</p>
        <h1 class="mt-3 text-3xl font-bold">Acceso denegado</h1>
        <p class="mt-4">Tu cuenta no tiene permiso para abrir esta página.</p>
        <a class="mt-6 inline-block font-semibold text-cyan-900 underline focus-visible:outline focus-visible:outline-offset-4" href="{{ route('home') }}">Volver al inicio</a>
    </main>
</body>
</html>
