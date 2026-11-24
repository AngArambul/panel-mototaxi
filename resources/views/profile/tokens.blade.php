<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Tokens de acceso</h2></x-slot>

    <div class="max-w-3xl mx-auto p-4 space-y-4">
        <p>Los tokens permiten consultar tu perfil por API durante 30 días. Puedes revocarlos en cualquier momento.</p>
        @if($issuedToken)
            <section class="rounded border border-amber-400 bg-amber-50 p-4" aria-label="Token recién emitido">
                <p class="font-semibold">Copia este token ahora. Se muestra una sola vez.</p>
                <code class="break-all" translate="no">{{ $issuedToken }}</code>
            </section>
        @endif
        <form method="post" action="{{ route('profile.tokens.store') }}" class="rounded bg-white p-4 space-y-3">
            @csrf
            <div>
                <x-input-label for="nombre" value="Nombre del token" />
                <x-text-input id="nombre" name="nombre" :value="old('nombre')" required maxlength="60" class="block w-full" />
                <x-input-error :messages="$errors->get('nombre')" />
            </div>
            <div>
                <x-input-label for="password" value="Contraseña actual" />
                <x-text-input id="password" name="password" type="password" required autocomplete="current-password" class="block w-full" />
                <x-input-error :messages="$errors->get('password')" />
            </div>
            <x-primary-button>Generar token</x-primary-button>
        </form>
        <section class="rounded bg-white p-4 space-y-3" aria-label="Tokens emitidos">
            @forelse($tokens as $token)
                <div class="flex flex-wrap justify-between gap-2 border-b py-2">
                    <span class="break-all">{{ $token->name }} · Vence {{ $token->expires_at?->format('Y-m-d') ?? 'sin vencimiento' }}</span>
                    <form method="post" action="{{ route('profile.tokens.destroy', $token->id) }}" onsubmit="return confirm('¿Revocar este token?')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Revocar</x-danger-button>
                    </form>
                </div>
            @empty
                <p>Todavía no tienes tokens.</p>
            @endforelse
            {{ $tokens->links() }}
        </section>
    </div>
</x-app-layout>
