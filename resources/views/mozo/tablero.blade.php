<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Mesas · {{ $comercio->nombre }}</title>
    @vite(['resources/css/app.css'])
    <style>
        /* Pensado para una tablet apoyada en la barra: los botones se tocan con el dedo
           pulgar y de costado, no con un mouse. Nada más chico de 44px de alto. */
        .mesa-card {
            text-align: left; width: 100%; padding: 14px; border-radius: 14px;
            border: 1px solid rgb(var(--c-line)); background: rgb(var(--c-elev-2));
            cursor: pointer; transition: transform .12s ease, border-color .12s ease;
        }
        .mesa-card:active { transform: scale(.98); }
        .mesa-card.ocupada { border-color: rgb(var(--c-accent)); }
        .mesa-card.libre { opacity: .55; }
        .mesa-numero { font-size: 22px; font-weight: 800; }
        .ronda-bloque { border-left: 2px solid rgb(var(--c-accent)); padding-left: 10px; }
        .item-fila { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 0; }
        .btn-qty {
            width: 40px; height: 40px; border-radius: 10px; font-size: 18px; font-weight: 700;
            border: 1px solid rgb(var(--c-line)); background: rgb(var(--c-elev-1));
            color: rgb(var(--c-ink)); cursor: pointer;
        }
        dialog::backdrop { background: rgba(0,0,0,.6); }
        dialog { border: 0; padding: 0; background: transparent; max-width: 640px; width: 94vw; }
    </style>
</head>
<body class="h-full bg-background">
    <div class="max-w-6xl mx-auto px-4 py-6">

        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">{{ $comercio->nombre }}</h1>
                <p class="text-text-secondary text-sm">
                    Mesas · actualizado <span id="actualizado">recién</span>
                </p>
            </div>
            <form method="POST" action="{{ route('mozo.salir', $comercio->mozo_token) }}">
                @csrf
                <button type="submit" class="btn-secondary">Salir</button>
            </form>
        </div>

        <div id="grilla" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3"></div>

        <p class="text-text-secondary text-xs mt-8 text-center">
            Los totales son orientativos, calculados con los precios del menú. No son la cuenta.
        </p>
    </div>

    {{-- Detalle de una mesa. Se usa <dialog> nativo: no hay librería de modales acá y es
         una pantalla que tiene que abrir instantáneo en una tablet vieja. --}}
    <dialog id="detalle">
        <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-5 max-h-[86vh] overflow-y-auto">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 id="detalleTitulo" class="text-2xl font-bold text-text-primary"></h2>
                    <p id="detalleResumen" class="text-text-secondary text-sm"></p>
                </div>
                <button type="button" id="cerrarDetalle" class="btn-secondary px-4 py-2">Cerrar</button>
            </div>

            <div id="detalleRondas" class="space-y-4 mb-5"></div>

            {{-- "Agregame un agua": el caso que más se repite en el salón.

                 Se navega por categorías y se toca, no se escribe: el mozo no se sabe la
                 carta de memoria, y tener que tipear el nombre exacto es buscar a ciegas.
                 El buscador queda arriba como atajo para el que ya sabe qué quiere. --}}
            <div class="border-t border-accent-gray/50 pt-4">
                <label for="buscarPlato" class="block text-sm font-semibold text-text-primary mb-2">
                    Agregar a la mesa
                </label>
                <input type="text" id="buscarPlato" class="input-field mb-3" autocomplete="off"
                       placeholder="Buscar en la carta…">

                <div id="categorias" class="flex flex-wrap gap-2 mb-3"></div>
                <div id="sugerencias" class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto"></div>

                <button type="button" id="agregarLibre" class="btn-secondary w-full mt-2" hidden></button>
            </div>

            <button type="button" id="cerrarMesa" class="btn-danger w-full mt-5">
                Cerrar mesa (se fueron)
            </button>
        </div>
    </dialog>

    <script>
        const TOKEN = @json($comercio->mozo_token);
        const PLATOS = @json($platos);
        let mesas = @json($mesas);
        let mesaAbierta = null;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const dialogo = document.getElementById('detalle');

        function plata(centavos) {
            return (centavos / 100).toLocaleString('es-AR', {
                style: 'currency', currency: 'ARS', minimumFractionDigits: 2, maximumFractionDigits: 2,
            });
        }

        function pedir(url, opciones = {}) {
            return fetch(url, {
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                ...opciones,
            }).then(r => {
                // 401 = la sesión de la tablet venció (o el dueño rotó el link): hay que
                // volver a poner el PIN, no dejar la pantalla mostrando datos viejos.
                if (r.status === 401) { location.href = `/mozo/${TOKEN}`; return null; }
                return r.json();
            });
        }

        function renderGrilla() {
            const grilla = document.getElementById('grilla');

            grilla.innerHTML = mesas.map(m => {
                if (!m.ocupada) {
                    return `<button class="mesa-card libre" data-mesa="${m.mesa_id}">
                        <div class="mesa-numero text-text-primary">${m.numero}</div>
                        <div class="text-text-secondary text-sm">Libre</div>
                    </button>`;
                }

                // Los minutos desde el último pedido son lo que le dice al mozo a qué mesa
                // ir: una mesa que pidió recién puede esperar, una de hace 20 no.
                return `<button class="mesa-card ocupada" data-mesa="${m.mesa_id}">
                    <div class="flex items-baseline justify-between">
                        <span class="mesa-numero text-text-primary">${m.numero}</span>
                        <span class="text-text-secondary text-xs">hace ${m.minutos}'</span>
                    </div>
                    <div class="text-accent-blue font-semibold text-sm mt-1">${m.items_total} ítem(s)</div>
                    <div class="text-text-secondary text-sm">${plata(m.total_centavos)}</div>
                    ${m.etiqueta ? `<div class="text-text-secondary text-xs mt-1">${m.etiqueta}</div>` : ''}
                </button>`;
            }).join('');

            grilla.querySelectorAll('[data-mesa]').forEach(b => {
                b.addEventListener('click', () => abrirDetalle(parseInt(b.dataset.mesa, 10)));
            });
        }

        function mesaPorId(id) {
            return mesas.find(m => m.mesa_id === id);
        }

        function abrirDetalle(mesaId) {
            const mesa = mesaPorId(mesaId);
            if (!mesa || !mesa.ocupada) return;   // una mesa libre no tiene nada que mostrar

            mesaAbierta = mesaId;
            renderDetalle();
            if (!dialogo.open) dialogo.showModal();
        }

        function renderDetalle() {
            const mesa = mesaPorId(mesaAbierta);

            // Si la mesa se cerró desde otra tablet mientras este mozo la tenía abierta,
            // el diálogo se cierra solo en vez de quedar operando sobre algo que ya no existe.
            if (!mesa || !mesa.ocupada) {
                dialogo.close();
                mesaAbierta = null;
                return;
            }

            document.getElementById('detalleTitulo').textContent =
                `Mesa ${mesa.numero}${mesa.etiqueta ? ' · ' + mesa.etiqueta : ''}`;
            document.getElementById('detalleResumen').textContent =
                `${mesa.items_total} ítem(s) · ${plata(mesa.total_centavos)} · último pedido hace ${mesa.minutos}'`;

            document.getElementById('detalleRondas').innerHTML = mesa.rondas.map(r => `
                <div class="ronda-bloque">
                    <div class="text-text-secondary text-xs uppercase tracking-wide mb-1">
                        Ronda ${r.ronda} · ${r.hora}${r.del_mozo ? ' · lo agregaste vos' : ''}
                    </div>
                    ${r.items.map(it => `
                        <div class="item-fila">
                            <div class="min-w-0">
                                <div class="text-text-primary">${it.nombre}</div>
                                ${it.nota ? `<div class="text-text-secondary text-xs">${it.nota}</div>` : ''}
                            </div>
                            <div class="flex items-center gap-2 flex-none">
                                <button class="btn-qty" data-menos="${it.id}">−</button>
                                <span class="text-text-primary font-semibold w-6 text-center">${it.qty}</span>
                                <button class="btn-qty" data-mas="${it.id}">+</button>
                                <button class="btn-qty" data-borrar="${it.id}" title="Quitar">×</button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `).join('');

            const rondas = document.getElementById('detalleRondas');
            rondas.querySelectorAll('[data-mas]').forEach(b => b.addEventListener('click',
                () => cambiarQty(b.dataset.mas, +1)));
            rondas.querySelectorAll('[data-menos]').forEach(b => b.addEventListener('click',
                () => cambiarQty(b.dataset.menos, -1)));
            rondas.querySelectorAll('[data-borrar]').forEach(b => b.addEventListener('click',
                () => borrarItem(b.dataset.borrar)));
        }

        function qtyActual(itemId) {
            const mesa = mesaPorId(mesaAbierta);
            for (const r of mesa.rondas) {
                const it = r.items.find(i => String(i.id) === String(itemId));
                if (it) return it.qty;
            }
            return 1;
        }

        async function cambiarQty(itemId, delta) {
            const mesa = mesaPorId(mesaAbierta);
            const data = await pedir(`/mozo/${TOKEN}/comanda/${mesa.comanda_id}/item/${itemId}`, {
                method: 'PATCH',
                body: JSON.stringify({ qty: Math.max(0, qtyActual(itemId) + delta) }),
            });
            if (data?.success) aplicar(data.mesas);
        }

        async function borrarItem(itemId) {
            const mesa = mesaPorId(mesaAbierta);
            const data = await pedir(`/mozo/${TOKEN}/comanda/${mesa.comanda_id}/item/${itemId}`, {
                method: 'DELETE',
            });
            if (data?.success) aplicar(data.mesas);
        }

        async function agregarItem(nombre, precio) {
            const mesa = mesaPorId(mesaAbierta);
            const data = await pedir(`/mozo/${TOKEN}/comanda/${mesa.comanda_id}/item`, {
                method: 'POST',
                body: JSON.stringify({ nombre: nombre, qty: 1, precio: precio || null }),
            });
            if (data?.success) {
                aplicar(data.mesas);
                document.getElementById('buscarPlato').value = '';
                renderPlatos();
            }
        }

        // La carta agrupada por categoría, en el orden en que viene del menú.
        const CATEGORIAS = [...new Set(PLATOS.map(p => p.categoria))];
        let categoriaActiva = CATEGORIAS[0] || null;

        function escapar(t) {
            return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        }

        function renderCategorias() {
            const caja = document.getElementById('categorias');
            if (!caja) return;

            caja.innerHTML = CATEGORIAS.map(c => `
                <button type="button" class="btn-secondary px-3 py-2 text-xs ${c === categoriaActiva ? 'border-accent-blue text-accent-blue' : ''}"
                        data-cat="${escapar(c)}">${escapar(c)}</button>
            `).join('');

            caja.querySelectorAll('[data-cat]').forEach(b => b.addEventListener('click', () => {
                categoriaActiva = b.dataset.cat;
                document.getElementById('buscarPlato').value = '';
                renderCategorias();
                renderPlatos();
            }));
        }

        /**
         * Los platos que se muestran: los de la categoría abierta, o el resultado de la
         * búsqueda si el mozo escribió algo (ahí se busca en TODA la carta, no sólo en la
         * categoría, porque el que escribe ya sabe qué quiere).
         */
        function renderPlatos() {
            const caja = document.getElementById('sugerencias');
            const libre = document.getElementById('agregarLibre');
            const q = document.getElementById('buscarPlato').value.trim().toLowerCase();

            const lista = q
                ? PLATOS.filter(p => p.nombre.toLowerCase().includes(q)).slice(0, 20)
                : PLATOS.filter(p => p.categoria === categoriaActiva);

            caja.innerHTML = lista.length
                ? lista.map(p => `
                    <button type="button" class="btn-secondary flex-col items-start text-left px-3 py-3 h-auto"
                            data-nombre="${escapar(p.nombre)}" data-precio="${escapar(p.precio || '')}">
                        <span class="text-text-primary leading-tight">${escapar(p.nombre)}</span>
                        <span class="text-text-secondary text-xs mt-1">${escapar(p.precio || 's/precio')}</span>
                    </button>
                `).join('')
                : `<p class="text-text-secondary text-sm col-span-2 py-3">Nada con ese nombre en la carta.</p>`;

            caja.querySelectorAll('[data-nombre]').forEach(b => b.addEventListener('click',
                () => agregarItem(b.dataset.nombre, b.dataset.precio)));

            // Lo que no está en la carta igual se tiene que poder cargar: el mozo no puede
            // quedarse sin anotar algo porque el menú no lo tenga.
            libre.hidden = q.length === 0;
            if (q) {
                libre.textContent = `Agregar "${document.getElementById('buscarPlato').value.trim()}" (no está en la carta)`;
                libre.onclick = () => agregarItem(document.getElementById('buscarPlato').value.trim(), null);
            }
        }

        async function cerrarMesa() {
            const mesa = mesaPorId(mesaAbierta);
            if (!confirm(`¿Cerrar la mesa ${mesa.numero}? Queda libre para el próximo grupo.`)) return;

            const data = await pedir(`/mozo/${TOKEN}/comanda/${mesa.comanda_id}/cerrar`, { method: 'POST' });
            if (data?.success) {
                aplicar(data.mesas);
                dialogo.close();
                mesaAbierta = null;
            }
        }

        function aplicar(nuevas) {
            mesas = nuevas;
            renderGrilla();
            if (mesaAbierta !== null) renderDetalle();
            document.getElementById('actualizado').textContent =
                new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        }

        document.getElementById('cerrarDetalle').addEventListener('click', () => {
            dialogo.close();
            mesaAbierta = null;
        });
        document.getElementById('cerrarMesa').addEventListener('click', cerrarMesa);
        document.getElementById('buscarPlato').addEventListener('input', renderPlatos);
        dialogo.addEventListener('close', () => { mesaAbierta = null; });

        // Refresco automático. Polling y no una conexión abierta: en hosting compartido y
        // con el wifi de un local, un fetch cada 8s es mucho más confiable, y con veinte
        // mesas no se nota.
        setInterval(async () => {
            const data = await pedir(`/mozo/${TOKEN}/estado`);
            if (data?.success) aplicar(data.mesas);
        }, 8000);

        renderGrilla();
        renderCategorias();
        renderPlatos();
    </script>
</body>
</html>
