<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-gray-900">Usuarios</h1></x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 py-6 sm:px-6">
        @if (session('status'))
            <p role="status" class="rounded bg-green-50 p-3 text-green-900">{{ session('status') }}</p>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-700">Cuentas del panel web</p>
            <a href="{{ route('accounts.create') }}" class="rounded bg-indigo-700 px-4 py-2 text-sm font-semibold text-white focus-visible:ring-2">Crear usuario</a>
        </div>

        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Cuentas">
            @forelse ($accounts as $account)
                <li class="min-w-0 rounded-lg bg-white p-4 shadow-sm">
                    <h2 class="break-words font-semibold text-gray-900">{{ $account->nombre }}</h2>
                    <p class="break-all text-sm text-gray-700">{{ $account->email ?? 'Sin correo' }}</p>
                    @if ($account->telefono)
                        <p class="text-sm text-gray-700">{{ $account->telefono }}</p>
                    @endif
                    <p class="mt-2 text-sm text-gray-800">{{ ucfirst(strtolower($account->rol)) }} · {{ $account->isActive() ? 'Activo' : 'Desactivado' }}</p>
                    @can('update', $account)
                        <a href="{{ route('accounts.edit', $account) }}" class="mt-3 inline-block text-sm font-semibold text-indigo-700 underline focus-visible:ring-2">Editar cuenta<span class="sr-only"> de {{ $account->nombre }}</span></a>
                    @endcan
                </li>
            @empty
                <li class="rounded bg-white p-4 text-gray-700">No hay cuentas disponibles.</li>
            @endforelse
        </ul>

        {{ $accounts->links() }}
    </div>
</x-app-layout>
