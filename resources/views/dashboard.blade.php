<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Despacho Aramovil</h2></x-slot>
    <div class="max-w-7xl mx-auto py-6 px-4 space-y-5" id="dispatch-panel" data-url="{{ route('dispatch.data') }}" data-integration="{{ route('dispatch.integration') }}">
        @if(session('status'))
            <p role="status" class="rounded bg-cyan-50 p-3 text-cyan-950">{{ session('status') }}</p>
        @endif
        @if(session('dispatch_error'))
            <p role="alert" class="rounded bg-red-50 p-3 text-red-900">{{ session('dispatch_error') }}</p>
        @endif
        @if(session('dispatch_invitation'))
            <div class="rounded bg-cyan-50 p-3">
                <p>Comparte esta invitación con el conductor por un canal privado. Vence en 24 horas.</p>
                <code class="break-all">{{ session('dispatch_invitation') }}</code>
            </div>
        @endif
        <p id="dispatch-message" role="status" aria-live="polite" class="text-gray-700">Conecta tu cuenta de propietario para consultar tu flotilla.</p>
        <form id="dispatch-login" method="post" action="{{ route('dispatch.login') }}" class="bg-white rounded-lg p-4 sm:p-6 grid gap-4 sm:grid-cols-2">
            @csrf
            <div>
                <x-input-label for="dispatch-phone" value="Teléfono del propietario" />
                <x-text-input id="dispatch-phone" name="telefono" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" required autocomplete="username" :value="old('telefono')" class="block w-full" />
                <x-input-error :messages="$errors->get('telefono')" />
            </div>
            <div>
                <x-input-label for="dispatch-password" value="Contraseña de la flotilla" />
                <x-text-input id="dispatch-password" name="password" type="password" required maxlength="128" autocomplete="current-password" class="block w-full" />
                <x-input-error :messages="$errors->get('password')" />
            </div>
            <div class="sm:col-span-2"><x-primary-button>Conectar flotilla</x-primary-button></div>
        </form>
        <noscript><p>Activa JavaScript para consultar las unidades disponibles y los viajes de la flotilla.</p></noscript>
        <section id="dispatch-content" hidden class="space-y-5">
            <div class="bg-white rounded-lg p-4 sm:p-6">
                <h3 class="font-semibold text-lg mb-4">Unidades</h3>
                <div id="units" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
            </div>
            <form method="post" action="{{ route('dispatch.trips.store') }}" class="bg-white rounded-lg p-4 sm:p-6 grid gap-4 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="_submission" value="{{ $nonces['trip'] }}">
                <h3 class="font-semibold text-lg sm:col-span-2">Solicitar un viaje</h3>
                <div>
                    <x-input-label for="trip-origin" value="Recogida" />
                    <x-text-input id="trip-origin" name="origen" required maxlength="300" :value="old('origen')" class="block w-full" />
                    <x-input-error :messages="$errors->get('origen')" />
                </div>
                <div>
                    <x-input-label for="trip-destination" value="Destino" />
                    <x-text-input id="trip-destination" name="destino" required maxlength="300" :value="old('destino')" class="block w-full" />
                    <x-input-error :messages="$errors->get('destino')" />
                </div>
                <div>
                    <x-input-label for="trip-passenger" value="Pasajero (opcional)" />
                    <x-text-input id="trip-passenger" name="pasajero" maxlength="120" :value="old('pasajero')" class="block w-full" />
                    <x-input-error :messages="$errors->get('pasajero')" />
                </div>
                <div>
                    <x-input-label for="unit-select" value="Unidad" />
                    <select name="numeroUnidad" id="unit-select" data-initial="{{ old('numeroUnidad') }}" required class="block w-full rounded border-gray-300"></select>
                    <x-input-error :messages="$errors->get('numeroUnidad')" />
                </div>
                <div class="sm:col-span-2"><x-primary-button id="submit-trip" disabled>Solicitar al conductor</x-primary-button></div>
            </form>
            <form method="post" action="{{ route('dispatch.invitations.store') }}" class="bg-white rounded-lg p-4 sm:p-6 space-y-3">
                @csrf
                <input type="hidden" name="_submission" value="{{ $nonces['invitation'] }}">
                <h3 class="font-semibold text-lg">Invitar a un conductor</h3>
                <div>
                    <x-input-label for="invitation-unit" value="Unidad sin conductor" />
                    <select name="numeroUnidad" id="invitation-unit" data-initial="{{ old('numeroUnidad') }}" required class="block w-full sm:max-w-xs rounded border-gray-300"></select>
                    <x-input-error :messages="$errors->invitation->get('numeroUnidad')" />
                </div>
                <x-primary-button id="submit-invitation" disabled>Crear invitación</x-primary-button>
            </form>
            <div class="bg-white rounded-lg p-4 sm:p-6"><h3 class="font-semibold text-lg mb-4">Solicitudes y viajes</h3><div id="trips" class="space-y-3"></div></div>
            <details class="bg-white rounded-lg p-4 sm:p-6"><summary>Estado de integración</summary><pre id="integration" class="whitespace-pre-wrap break-words text-sm mt-4"></pre></details>
        </section>
    </div>
    <script type="module">
        const panel = document.getElementById('dispatch-panel');
        const byId = id => document.getElementById(id);
        const message = text => { byId('dispatch-message').textContent = text; };
        let connected = false;

        async function load(url) {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if (response.status === 401) {
                connected = false;
                byId('dispatch-login').hidden = false;
                byId('dispatch-content').hidden = true;
            }
            const result = await response.json();
            if (!response.ok) throw new Error(result.msg || 'No se pudo consultar el despacho.');
            return result;
        }
        function line(parent, text, tag = 'p') {
            const item = document.createElement(tag);
            item.textContent = text;
            parent.append(item);
            return item;
        }
        function locationLink(card, label, url) {
            const a = line(card, label, 'a');
            a.href = url;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.className = 'underline block';
        }
        function updateSelect(id, button, units) {
            const select = byId(id);
            const value = select.value || select.dataset.initial;
            delete select.dataset.initial;
            select.replaceChildren(new Option(units.length ? 'Selecciona una unidad' : 'No hay unidades disponibles', ''));
            for (const unit of units) select.add(new Option('Unidad ' + unit.numero, unit.numero));
            if (units.some(unit => unit.numero === value)) select.value = value;
            byId(button).disabled = !units.length;
        }
        async function refresh() {
            const data = await load(panel.dataset.url);
            connected = true;
            byId('dispatch-login').hidden = true;
            byId('dispatch-content').hidden = false;
            message('Flotilla conectada. Las unidades y los viajes se actualizan automáticamente.');

            const units = byId('units');
            units.replaceChildren();
            if (!data.unidades.length) line(units, 'La flotilla no tiene unidades registradas.');
            for (const unit of data.unidades) {
                const card = document.createElement('article');
                card.className = 'border rounded p-3 break-words';
                line(card, 'Unidad ' + unit.numero + ' · ' + unit.estado, 'strong');
                line(card, unit.choferNombre);
                line(card, unit.gpsVigente ? 'GPS actualizado' : 'Sin GPS reciente');
                if (unit.gpsVigente && Number.isFinite(unit.latitud) && Number.isFinite(unit.longitud)) {
                    locationLink(card, 'Ver ubicación', 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(unit.latitud + ',' + unit.longitud));
                }
                units.append(card);
            }
            updateSelect('unit-select', 'submit-trip', data.unidades);
            updateSelect('invitation-unit', 'submit-invitation', data.unidades.filter(unit => unit.choferNombre === 'Sin asignar'));

            const trips = byId('trips');
            trips.replaceChildren();
            if (!data.viajes.length) line(trips, 'No hay solicitudes ni viajes en tu flotilla.');
            for (const trip of data.viajes) {
                const card = document.createElement('article');
                card.className = 'border rounded p-3 break-words';
                line(card, trip.id + ' · ' + trip.estado, 'strong');
                line(card, trip.pasajero + ' · ' + trip.origen + ' → ' + trip.destino);
                line(card, 'Unidad ' + (trip.numeroUnidad || 'pendiente de aceptación') + ' · Canal ' + trip.canal);
                if (Number.isFinite(trip.latitud) && Number.isFinite(trip.longitud)) {
                    locationLink(card, 'Ver recogida', 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(trip.latitud + ',' + trip.longitud));
                }
                trips.append(card);
            }
        }
        document.querySelector('#dispatch-content details').addEventListener('toggle', async event => {
            if (!event.currentTarget.open) return;
            try {
                byId('integration').textContent = JSON.stringify(await load(panel.dataset.integration), null, 2);
            } catch (error) {
                byId('integration').textContent = error.message;
            }
        });
        try {
            await refresh();
        } catch (error) {
            message(error.message);
        }
        async function poll() {
            if (!document.hidden && connected) {
                try { await refresh(); } catch (error) { message(error.message); }
            }
            setTimeout(poll, 6000);
        }
        setTimeout(poll, 6000);
    </script>
</x-app-layout>
