<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-gray-900">{{ $account->exists ? 'Editar usuario' : 'Crear usuario' }}</h1></x-slot>

    <div class="mx-auto max-w-2xl space-y-4 px-4 py-6 sm:px-6">
        <a href="{{ route('accounts.index') }}" class="text-sm font-semibold text-indigo-700 underline focus-visible:ring-2">Volver a usuarios</a>
        <form method="post" action="{{ $account->exists ? route('accounts.update', $account) : route('accounts.store') }}" class="space-y-4 rounded-lg bg-white p-4 shadow-sm sm:p-6">
            @csrf
            @if ($account->exists)
                @method('PUT')
            @endif

            <div>
                <x-input-label for="nombre" value="Nombre completo" />
                <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" :value="old('nombre', $account->nombre)" maxlength="100" required autocomplete="off" />
                <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="email" value="Correo electrónico" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $account->email)" maxlength="150" :required="! $account->exists || $account->email !== null" autocomplete="off" />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="telefono" value="Teléfono (opcional)" />
                <x-text-input id="telefono" name="telefono" type="tel" class="mt-1 block w-full" :value="old('telefono', $account->telefono)" maxlength="16" autocomplete="off" />
                <x-input-error :messages="$errors->get('telefono')" class="mt-1" />
            </div>
            @if ($account->exists)
                <p class="text-sm text-gray-800">Rol {{ ucfirst(strtolower($account->rol)) }}</p>
            @else
                <div>
                    <x-input-label for="rol" value="Rol" />
                    <select name="rol" id="rol" class="mt-1 block w-full rounded border-gray-300" required>
                        <option value="PASAJERO" @selected(old('rol') === 'PASAJERO')>Pasajero</option>
                        <option value="CONDUCTOR" @selected(old('rol') === 'CONDUCTOR')>Conductor</option>
                    </select>
                    <x-input-error :messages="$errors->get('rol')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password" value="Contraseña inicial" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" minlength="8" maxlength="72" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" maxlength="72" />
                </div>
            @endif
            <x-primary-button>Guardar</x-primary-button>
        </form>

        @if ($account->exists)
            <div class="rounded-lg bg-white p-4 shadow-sm sm:p-6">
                @if ($account->isActive())
                    <details>
                        <summary class="cursor-pointer font-semibold text-red-800 focus-visible:ring-2">Desactivar cuenta</summary>
                        <p class="my-2 text-sm text-gray-700">Se revocarán sus sesiones y tokens del panel. Su historial se conservará.</p>
                        <form method="post" action="{{ route('accounts.destroy', $account) }}">
                            @csrf
                            @method('DELETE')
                            <x-danger-button>Confirmar desactivación</x-danger-button>
                        </form>
                    </details>
                @else
                    <form method="post" action="{{ route('accounts.activate', $account) }}">
                        @csrf
                        @method('PATCH')
                        <x-secondary-button type="submit">Reactivar cuenta</x-secondary-button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
