<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Despacho Aramovil</h2></x-slot>
    <div class="max-w-7xl mx-auto py-8 px-4 space-y-6" id="dispatch-panel">
        <p id="dispatch-message" role="status" aria-live="polite" class="text-gray-700">Conecta tu cuenta de propietario para consultar tu flotilla.</p>
        <form id="dispatch-login" class="bg-white rounded-lg p-6 flex flex-wrap gap-4 items-end">
            <label>Teléfono del propietario<input name="telefono" inputmode="numeric" pattern="[0-9]{10}" required class="block rounded border-gray-300" autocomplete="username"></label>
            <label>Contraseña<input name="password" type="password" required class="block rounded border-gray-300" autocomplete="current-password"></label>
            <button class="bg-gray-800 text-white rounded px-4 py-2">Conectar flotilla</button>
        </form>
        <section id="dispatch-content" hidden class="space-y-6">
            <div class="bg-white rounded-lg p-6"><h3 class="font-semibold text-lg mb-4">Unidades</h3><div id="units" class="grid gap-4 md:grid-cols-3"></div></div>
            <form id="dispatch-trip" class="bg-white rounded-lg p-6 flex flex-wrap gap-3 items-end">
                <label>Recogida<input name="origen" required maxlength="300" class="block rounded border-gray-300"></label>
                <label>Destino<input name="destino" required maxlength="300" class="block rounded border-gray-300"></label>
                <label>Pasajero<input name="pasajero" maxlength="120" class="block rounded border-gray-300"></label>
                <label>Unidad<select name="numeroUnidad" id="unit-select" required class="block rounded border-gray-300"></select></label>
                <button class="bg-gray-800 text-white rounded px-4 py-2">Solicitar al conductor</button>
            </form>
            <div class="bg-white rounded-lg p-6"><h3 class="font-semibold text-lg mb-4">Solicitudes y viajes</h3><div id="trips" class="space-y-3"></div></div>
            <details class="bg-white rounded-lg p-6"><summary>Estado de integración</summary><pre id="integration" class="whitespace-pre-wrap text-sm mt-4"></pre></details>
        </section>
    </div>
    <script>
        (() => {
            const byId = id => document.getElementById(id);
            const message = text => { byId('dispatch-message').textContent = text; };
            let connected = false;
            async function api(path, data) {
                const response = await fetch(path, { method: data ? 'POST' : 'GET', credentials: 'same-origin',
                    headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
                    body: data ? JSON.stringify(data) : undefined });
                const result = await response.json();
                if (!response.ok) { if (response.status === 401) { connected = false; byId('dispatch-login').hidden = false; byId('dispatch-content').hidden = true; } throw new Error(result.msg || result.message || 'No se pudo completar la operación.'); }
                return result;
            }
            function line(parent, text, tag = 'p') { const item = document.createElement(tag); item.textContent = text; parent.append(item); return item; }
            async function refresh() {
                const data = await api('/dispatch/data');
                connected = true; byId('dispatch-login').hidden = true; byId('dispatch-content').hidden = false;
                byId('units').replaceChildren();
                const selected = byId('unit-select').value; byId('unit-select').replaceChildren();
                for (const unit of data.unidades) {
                    const card = document.createElement('article'); card.className = 'border rounded p-3';
                    line(card, `Unidad ${unit.numero} · ${unit.estado}`, 'strong');
                    line(card, unit.choferNombre); line(card, unit.gpsVigente ? 'GPS actualizado' : 'Sin GPS reciente');
                    if (unit.choferNombre === 'Sin asignar') {
                        const button = line(card, 'Crear invitación', 'button'); button.type = 'button'; button.className = 'underline mt-2';
                        button.onclick = async () => { try { const r = await api('/dispatch/invitation', {numeroUnidad:unit.numero}); message(`Invitación para unidad ${unit.numero}: ${r.codigoInvitacion}. Vence en 24 horas.`); } catch (e) { message(e.message); } };
                    }
                    if (unit.gpsVigente && unit.latitud !== null) { const a = line(card, 'Ver ubicación', 'a'); a.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(unit.latitud+','+unit.longitud)}`; a.target = '_blank'; a.rel = 'noopener noreferrer'; a.className = 'underline block'; }
                    byId('units').append(card);
                    const option = new Option(`Unidad ${unit.numero}`, unit.numero); byId('unit-select').add(option);
                }
                if ([...byId('unit-select').options].some(o => o.value === selected)) byId('unit-select').value = selected;
                byId('trips').replaceChildren();
                if (!data.viajes.length) line(byId('trips'), 'No hay solicitudes ni viajes en tu flotilla.');
                for (const trip of data.viajes) {
                    const card = document.createElement('article'); card.className = 'border rounded p-3';
                    line(card, `${trip.id} · ${trip.estado}`, 'strong'); line(card, `${trip.pasajero} · ${trip.origen} → ${trip.destino}`);
                    line(card, `Unidad: ${trip.numeroUnidad || 'Pendiente de aceptación'} · Canal: ${trip.canal}`);
                    if (trip.latitud !== null) { const a = line(card, 'Ver recogida', 'a'); a.href = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(trip.latitud+','+trip.longitud)}`; a.target = '_blank'; a.rel = 'noopener noreferrer'; a.className = 'underline'; }
                    byId('trips').append(card);
                }
            }
            byId('dispatch-login').onsubmit = async e => { e.preventDefault(); try { const form = e.currentTarget; await api('/dispatch/login', Object.fromEntries(new FormData(form))); form.reset(); await refresh(); message('Flotilla conectada. Las solicitudes se actualizan automáticamente.'); } catch (error) { message(error.message); } };
            byId('dispatch-trip').onsubmit = async e => { e.preventDefault(); try { const r = await api('/dispatch/trips', Object.fromEntries(new FormData(e.currentTarget))); message(r.msg); await refresh(); } catch (error) { message(error.message); } };
            document.querySelector('#dispatch-content details').addEventListener('toggle', async e => { if (e.currentTarget.open) try { byId('integration').textContent = JSON.stringify(await api('/dispatch/integration'), null, 2); } catch(error) { message(error.message); } });
            async function loop() { if (!document.hidden && connected) try { await refresh(); } catch(error) { message(error.message); } setTimeout(loop, 3000); }
            refresh().catch(() => {}); loop();
        })();
    </script>
</x-app-layout>
