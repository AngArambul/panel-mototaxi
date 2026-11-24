<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aramovil · Panel de mototaxis</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 antialiased">
    <main class="mx-auto max-w-4xl px-6 py-16 sm:py-24">
        <p class="font-semibold text-cyan-300">Tala y Los Ruiseñores</p>
        <h1 class="mt-3 text-4xl font-bold sm:text-6xl">Aramovil</h1>
        <p class="mt-5 max-w-xl text-lg">Panel de mototaxis para consultar la flotilla, gestionar solicitudes y administrar cuentas web.</p>
        <div class="mt-8 flex flex-wrap gap-4">
            @auth
                <a class="rounded bg-cyan-300 px-5 py-3 font-semibold text-slate-900 focus-visible:outline focus-visible:outline-offset-4" href="{{ route('dashboard') }}">Entrar al panel</a>
            @else
                <a class="rounded bg-cyan-300 px-5 py-3 font-semibold text-slate-900 focus-visible:outline focus-visible:outline-offset-4" href="{{ route('login') }}">Iniciar sesión</a>
                <a class="rounded border border-cyan-300 px-5 py-3 font-semibold text-cyan-100 focus-visible:outline focus-visible:outline-offset-4" href="{{ route('register') }}">Crear cuenta</a>
            @endauth
        </div>
    </main>
    <footer class="mx-auto max-w-4xl px-6 pb-8 text-sm text-slate-300">
        <a class="underline focus-visible:outline focus-visible:outline-offset-4" href="{{ route('legal') }}">Privacidad y eliminación de datos</a>
    </footer>
</body>
</html>
