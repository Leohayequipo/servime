<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Mesas · {{ $comercio->nombre }}</title>
    @vite(['resources/css/app.css'])
    {{-- Misma librería y misma config que el panel (layouts/app.blade.php). Esta vista no
         usa ese layout —corre sin login, con su propio HTML— así que los helpers hay que
         traerlos, pero el `confirm()` nativo no va: no matchea la estética. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* El atributo `hidden` tiene que ocultar siempre. Las clases de botón (btn-secondary,
           btn-danger) fijan display:inline-flex y le ganaban: el "Agregar «…»" del buscador
           salía como una píldora vacía, y "Cerrar mesa" aparecía en las mesas libres. */
        [hidden] { display: none !important; }

        /* Pensado para una tablet apoyada en la barra: los botones se tocan con el dedo
           pulgar y de costado, no con un mouse. Nada más chico de 44px de alto. */
        .mesa-card {
            display: flex; flex-direction: column;
            border-radius: 14px; overflow: hidden;
            border: 1px solid rgb(var(--c-line)); background: rgb(var(--c-elev-2));
            transition: border-color .12s ease;
        }
        .mesa-card.ocupada { border-color: rgb(var(--c-accent)); }
        /* La libre queda atenuada pero no muerta: se puede abrir desde el tablero. */
        .mesa-card.libre { opacity: .6; border-style: dashed; }
        .mesa-card.libre:hover { opacity: .85; }

        /* El cuerpo abre el detalle; el pie libera la mesa. Van separados porque un botón
           adentro de otro no es HTML válido. */
        .mesa-card-cuerpo {
            flex: 1; width: 100%; text-align: left; padding: 14px;
            background: none; border: 0; color: inherit; cursor: pointer;
            transition: transform .12s ease;
        }
        .mesa-card-cuerpo:active { transform: scale(.98); }
        .mesa-card-cuerpo:focus-visible { outline: 2px solid rgb(var(--c-accent)); outline-offset: -2px; }

        /* Discreto a propósito: liberar la mesa es la acción menos frecuente de la
           tarjeta, y un botón fuerte acá invitaría a tocarlo sin querer. */
        .mesa-card-liberar {
            width: 100%; padding: 9px; min-height: 40px;
            background: rgb(var(--c-elev-1)); border: 0;
            border-top: 1px solid rgb(var(--c-line));
            color: rgb(var(--c-ink-soft)); font-size: 12px; font-weight: 600; cursor: pointer;
        }
        .mesa-card-liberar:hover { background: rgb(var(--c-danger) / .12); color: rgb(var(--c-danger)); }
        .mesa-card-liberar:focus-visible { outline: 2px solid rgb(var(--c-accent)); outline-offset: -2px; }
        .mesa-numero { font-size: 22px; font-weight: 800; }
        .ronda-bloque { border-left: 2px solid rgb(var(--c-accent)); padding-left: 10px; }
        .item-fila { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 0; }

        /* Lo que acaba de entrar. La señal es lo que evita que el mozo, sin saber si su
           toque hizo algo, lo repita y cargue el plato dos veces. */
        .item-fila.recien {
            animation: entro 1.8s ease-out;
            border-radius: 8px;
            margin: 0 -8px; padding-left: 8px; padding-right: 8px;
        }
        @keyframes entro {
            0%, 55% { background: rgb(var(--c-ok) / .28); }
            100%    { background: transparent; }
        }
        .btn-qty:disabled { opacity: .5; cursor: default; }

        /* Botón de la carta mientras viaja el request y cuando confirmó. */
        #sugerencias button.agregando { opacity: .6; }
        #sugerencias button.agregado,
        #agregarLibre.agregado {
            background: rgb(var(--c-ok));
            border-color: rgb(var(--c-ok));
            color: #fff;
        }
        #sugerencias button.agregado span { color: #fff; }

        @media (prefers-reduced-motion: reduce) {
            .item-fila.recien { animation: none; background: rgb(var(--c-ok) / .18); }
        }
        .btn-qty {
            width: 40px; height: 40px; border-radius: 10px; font-size: 18px; font-weight: 700;
            border: 1px solid rgb(var(--c-line)); background: rgb(var(--c-elev-1));
            color: rgb(var(--c-ink)); cursor: pointer;
        }
        /* Se pega arriba del contenido scrolleable: el título y la salida siguen a mano
           por más que el mozo baje 20 platos en el buscador.

           `top` es -20px y no 0 a propósito: el contenedor tiene 20px de relleno (p-5) y
           el navegador lo respeta al pegar, así que con 0 la cabecera quedaba 20px más
           abajo de donde la pone el margen negativo. Sin scrollear tapaba el principio
           del pedido, y scrolleando el pedido se veía pasar por arriba del título. */
        .detalle-cab {
            position: sticky; top: -20px; z-index: 3;
            display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
            margin: -20px -20px 16px; padding: 20px;
            background: rgb(var(--c-elev-2));
            border-bottom: 1px solid rgb(var(--c-line));
        }
        /* 44px: el mínimo que se toca con el pulgar sin errarle. */
        .detalle-cerrar {
            flex: none; width: 44px; height: 44px; border-radius: 12px;
            background: rgb(var(--c-elev-1)); border: 1px solid rgb(var(--c-line));
            color: rgb(var(--c-ink)); font-size: 26px; line-height: 1; cursor: pointer;
        }
        .detalle-cerrar:hover { background: rgb(var(--c-danger) / .12); color: rgb(var(--c-danger)); }
        .detalle-cerrar:focus-visible { outline: 2px solid rgb(var(--c-accent)); outline-offset: 2px; }

        /* Deshacer el cierre. Discreto pero identificable: es una acción rara y no tiene
           que competir con "tocá para abrir". */
        .mesa-card-reabrir {
            width: 100%; padding: 9px; min-height: 40px;
            background: rgb(var(--c-ok) / .12); border: 0;
            border-top: 1px solid rgb(var(--c-line));
            color: rgb(var(--c-ok)); font-size: 12px; font-weight: 700; cursor: pointer;
        }
        .mesa-card-reabrir:hover { background: rgb(var(--c-ok) / .22); }
        .mesa-card-reabrir:disabled { opacity: .5; cursor: default; }
        .mesa-card-reabrir:focus-visible { outline: 2px solid rgb(var(--c-accent)); outline-offset: -2px; }

        .cerradas {
            margin-top: 2.5rem; padding: 14px 16px; border-radius: 14px;
            background: rgb(var(--c-elev-2)); border: 1px solid rgb(var(--c-line));
        }
        .cerradas summary {
            cursor: pointer; font-weight: 700; color: rgb(var(--c-ink));
            list-style: none; padding: 4px 0;
        }
        .cerradas summary::-webkit-details-marker { display: none; }
        .cerradas summary::before { content: '▸ '; color: rgb(var(--c-accent)); }
        .cerradas[open] summary::before { content: '▾ '; }

        .cerrada-fila {
            display: flex; align-items: stretch; gap: 8px;
            border-top: 1px solid rgb(var(--c-line));
        }
        .cerrada-cuerpo {
            flex: 1; text-align: left; padding: 10px 0;
            background: none; border: 0; color: inherit; cursor: pointer;
        }
        .cerrada-fila .mesa-card-reabrir {
            width: auto; padding: 0 14px; border-top: 0; border-radius: 8px; margin: 8px 0;
        }
        .cerrada-auto {
            font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
            color: rgb(var(--c-warn)); border: 1px solid rgb(var(--c-warn) / .4);
            border-radius: 99px; padding: 1px 7px;
        }
        .cerrada-detalle {
            padding: 4px 0 12px 12px;
            border-left: 2px solid rgb(var(--c-line));
            margin-left: 4px;
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

        {{-- Lo que ya no está en el tablero. Va plegado: es para cuando pasa algo (un
             reclamo, una mesa que se cerró por error), no para mirar todo el tiempo. --}}
        <details class="cerradas" id="bloqueCerradas">
            <summary>Mesas cerradas hoy</summary>
            <div id="listaCerradas"></div>
        </details>

        <p class="text-text-secondary text-xs mt-8 text-center">
            Los totales son orientativos, calculados con los precios del menú. No son la cuenta.
        </p>
    </div>

    {{-- Detalle de una mesa. Se usa <dialog> nativo: no hay librería de modales acá y es
         una pantalla que tiene que abrir instantáneo en una tablet vieja. --}}
    <dialog id="detalle">
        <div class="bg-secondary-dark border border-accent-gray rounded-2xl p-5 max-h-[86vh] overflow-y-auto">
            {{-- El encabezado queda fijo al scrollear y se lleva la X.

                 Tocar afuera cierra igual, pero no alcanza como única salida: con la carta
                 desplegada el diálogo ocupa toda la pantalla y no queda fondo que tocar,
                 y en un teléfono no hay tecla Escape. Por eso la X tiene que estar
                 siempre a la vista, no sólo arriba de todo. --}}
            <div class="detalle-cab">
                <div class="min-w-0">
                    <h2 id="detalleTitulo" class="text-2xl font-bold text-text-primary truncate"></h2>
                    <p id="detalleResumen" class="text-text-secondary text-sm"></p>
                </div>
                <button type="button" id="cerrarDetalle" class="detalle-cerrar" aria-label="Cerrar">×</button>
            </div>

            <div id="detalleRondas" class="space-y-4 mb-5"></div>

            {{-- Mesa libre: se abre desde acá, sin esperar a que alguien pida por el
                 teléfono. Va con botón y no al primer toque para que un roce en la
                 tablet apoyada en la barra no abra mesas solo. --}}
            <div id="bloqueAbrir" hidden>
                <p class="text-text-secondary text-sm mb-3">
                    Nadie pidió desde el teléfono todavía. Abrila para cargarle vos lo que pidan.
                </p>
                <button type="button" id="btnAbrirMesa" class="btn-primary w-full">
                    Abrir mesa
                </button>
            </div>

            {{-- "Agregame un agua": el caso que más se repite en el salón.

                 Se navega por categorías y se toca, no se escribe: el mozo no se sabe la
                 carta de memoria, y tener que tipear el nombre exacto es buscar a ciegas.
                 El buscador queda arriba como atajo para el que ya sabe qué quiere. --}}
            <div class="border-t border-accent-gray/50 pt-4" id="bloqueAgregar">
                <label for="buscarPlato" class="block text-sm font-semibold text-text-primary mb-2">
                    Agregar a la mesa
                </label>
                {{-- Las categorías van primero: tocar es la vía principal, escribir es el
                     atajo del que ya sabe qué quiere. Y el buscador queda pegado a la
                     grilla que filtra, en vez de separado de ella por cuatro filas de chips. --}}
                <div id="categorias" class="flex flex-wrap gap-2 mb-3"></div>

                <input type="text" id="buscarPlato" class="input-field mb-3 text-sm" autocomplete="off"
                       placeholder="…o buscá en toda la carta">

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

        // El total al pie de la lista, como en una cuenta. Si hay platos sin precio en la
        // carta se avisa: el total no los incluye y el cliente tiene que saberlo.
        function filaTotal(mesa) {
            const sinPrecio = mesa.rondas.flatMap(r => r.items)
                .filter(it => it.precio_centavos === null || it.precio_centavos === undefined)
                .reduce((n, it) => n + it.qty, 0);
            return `
                <div class="border-t border-accent-gray/50 pt-3">
                    <div class="flex justify-between items-baseline">
                        <span class="text-text-secondary">Total</span>
                        <span class="text-text-primary font-bold text-lg">${plata(mesa.total_centavos)}</span>
                    </div>
                    ${sinPrecio ? `<div class="text-text-secondary text-xs text-right">sin contar ${sinPrecio} plato(s) sin precio</div>` : ''}
                </div>`;
        }

        // "$ 6.500,00", o "$ 13.000,00 · 2 × $ 6.500,00" si hay más de uno. Sin precio en
        // la carta no se inventa un $0: se dice que no tiene.
        function precioDeFila(it) {
            if (it.precio_centavos === null || it.precio_centavos === undefined) return 'sin precio';
            return it.qty > 1
                ? `${plata(it.subtotal_centavos)} · ${it.qty} × ${plata(it.precio_centavos)}`
                : plata(it.precio_centavos);
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
                    // Si se cerró hace poco, se ofrece deshacer: el botón de cerrar está a
                    // un toque y equivocarse de mesa es cuestión de tiempo.
                    const deshacer = m.reabrir_id
                        ? `<button class="mesa-card-reabrir" data-reabrir="${m.reabrir_id}">
                               ↩ Reabrir · cerrada hace ${m.cerrada_hace}'
                           </button>`
                        : '';

                    return `<div class="mesa-card libre">
                        <button class="mesa-card-cuerpo" data-mesa="${m.mesa_id}">
                            <div class="mesa-numero text-text-primary">${m.numero}</div>
                            <div class="text-text-secondary text-sm">Libre · tocá para abrir</div>
                        </button>
                        ${deshacer}
                    </div>`;
                }

                // Los minutos desde el último pedido son lo que le dice al mozo a qué mesa
                // ir: una mesa que pidió recién puede esperar, una de hace 20 no.
                //
                // "Cerrar mesa" va acá y no sólo adentro del detalle: cuando el mozo pasa
                // y ve que el grupo se fue, no tiene por qué entrar a la comanda para
                // liberarla. Es un <div> con dos botones y no un botón grande, porque un
                // botón adentro de otro no es HTML válido.
                return `<div class="mesa-card ocupada">
                    <button class="mesa-card-cuerpo" data-mesa="${m.mesa_id}">
                        <div class="flex items-baseline justify-between">
                            <span class="mesa-numero text-text-primary">${m.numero}</span>
                            <span class="text-text-secondary text-xs">hace ${m.minutos}'</span>
                        </div>
                        <div class="text-accent-blue font-semibold text-sm mt-1">${m.items_total} ítem(s)</div>
                        <div class="text-text-secondary text-sm">${plata(m.total_centavos)}</div>
                        ${m.etiqueta ? `<div class="text-text-secondary text-xs mt-1">${m.etiqueta}</div>` : ''}
                    </button>
                    <button class="mesa-card-liberar" data-cerrar="${m.mesa_id}">Cerrar mesa</button>
                </div>`;
            }).join('');

            grilla.querySelectorAll('[data-mesa]').forEach(b => {
                b.addEventListener('click', () => abrirDetalle(parseInt(b.dataset.mesa, 10)));
            });
            grilla.querySelectorAll('[data-cerrar]').forEach(b => {
                b.addEventListener('click', () => cerrarMesa(parseInt(b.dataset.cerrar, 10)));
            });
            grilla.querySelectorAll('[data-reabrir]').forEach(b => {
                b.addEventListener('click', () => reabrirMesa(b.dataset.reabrir, b));
            });
        }

        function mesaPorId(id) {
            return mesas.find(m => m.mesa_id === id);
        }

        // También se entra a una mesa libre: es el caso más común del salón, donde el
        // grupo le dicta el pedido al mozo sin tocar el teléfono.
        function abrirDetalle(mesaId) {
            const mesa = mesaPorId(mesaId);
            if (!mesa) return;

            mesaAbierta = mesaId;
            renderDetalle();
            if (!dialogo.open) dialogo.showModal();
        }

        async function abrirMesa() {
            const mesa = mesaPorId(mesaAbierta);
            const data = await pedir(`/mozo/${TOKEN}/mesa/${mesa.mesa_id}/abrir`, { method: 'POST' });

            if (data?.success) {
                aplicar(data.mesas);
                // Queda el cursor en el buscador: el mozo abrió la mesa para cargar algo.
                document.getElementById('buscarPlato').focus();
            }
        }

        function renderDetalle() {
            const mesa = mesaPorId(mesaAbierta);

            if (!mesa) {
                dialogo.close();
                mesaAbierta = null;
                return;
            }

            document.getElementById('detalleTitulo').textContent =
                `Mesa ${mesa.numero}${mesa.etiqueta ? ' · ' + mesa.etiqueta : ''}`;
            document.getElementById('detalleResumen').textContent = mesa.ocupada
                ? `${mesa.items_total} ítem(s) · ${plata(mesa.total_centavos)} · último pedido hace ${mesa.minutos}'`
                : 'Libre';

            // Mesa libre: lo único que se ofrece es abrirla. Cargar platos o cerrarla no
            // tiene sentido hasta que exista la comanda.
            document.getElementById('bloqueAbrir').hidden = mesa.ocupada;
            document.getElementById('bloqueAgregar').hidden = !mesa.ocupada;
            document.getElementById('cerrarMesa').hidden = !mesa.ocupada;

            if (!mesa.ocupada) {
                document.getElementById('detalleRondas').innerHTML = '';
                return;
            }

            document.getElementById('detalleRondas').innerHTML = mesa.rondas.map(r => `
                <div class="ronda-bloque">
                    {{-- Sin el número de ronda: al mozo no le sirve saber que es la sexta,
                         le sirve la hora y de dónde vino. El número era un detalle de cómo
                         está armado por dentro, filtrado a la pantalla. --}}
                    <div class="text-text-secondary text-xs uppercase tracking-wide mb-1">
                        ${r.hora} · ${r.del_mozo ? 'lo agregaste vos' : 'pidieron desde la mesa'}
                    </div>
                    ${r.items.map(it => `
                        <div class="item-fila ${it.id === itemNuevo ? 'recien' : ''}">
                            <div class="min-w-0">
                                <div class="text-text-primary">${it.nombre}</div>
                                ${it.nota ? `<div class="text-text-secondary text-xs">${it.nota}</div>` : ''}
                                {{-- El precio de cada plato: para cuando la mesa pregunta
                                     "¿a ver qué gastamos?" y el mozo le muestra la pantalla.
                                     Con más de uno, el subtotal primero y la cuenta al lado. --}}
                                <div class="text-text-secondary text-sm mt-0.5">${precioDeFila(it)}</div>
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
            `).join('') + filaTotal(mesa);

            const rondas = document.getElementById('detalleRondas');
            rondas.querySelectorAll('[data-mas]').forEach(b => b.addEventListener('click',
                () => cambiarQty(b.dataset.mas, +1, b)));
            rondas.querySelectorAll('[data-menos]').forEach(b => b.addEventListener('click',
                () => cambiarQty(b.dataset.menos, -1, b)));
            rondas.querySelectorAll('[data-borrar]').forEach(b => b.addEventListener('click',
                () => borrarItem(b.dataset.borrar, b)));
        }

        function qtyActual(itemId) {
            const mesa = mesaPorId(mesaAbierta);
            for (const r of mesa.rondas) {
                const it = r.items.find(i => String(i.id) === String(itemId));
                if (it) return it.qty;
            }
            return 1;
        }

        // Los + / − / × se bloquean mientras viaja el request por el mismo motivo que el
        // botón de agregar: dos toques seguidos sumaban dos.
        async function cambiarQty(itemId, delta, boton) {
            const mesa = mesaPorId(mesaAbierta);
            if (boton) boton.disabled = true;

            const data = await pedir(`/mozo/${TOKEN}/comanda/${mesa.comanda_id}/item/${itemId}`, {
                method: 'PATCH',
                body: JSON.stringify({ qty: Math.max(0, qtyActual(itemId) + delta) }),
            });

            if (data?.success) {
                itemNuevo = Number(itemId);
                aplicar(data.mesas);
                setTimeout(() => { itemNuevo = null; }, 2000);
            } else if (boton) {
                boton.disabled = false;
            }
        }

        async function borrarItem(itemId, boton) {
            const mesa = mesaPorId(mesaAbierta);
            if (boton) boton.disabled = true;

            const data = await pedir(`/mozo/${TOKEN}/comanda/${mesa.comanda_id}/item/${itemId}`, {
                method: 'DELETE',
            });

            if (data?.success) aplicar(data.mesas);
            else if (boton) boton.disabled = false;
        }

        // Qué ítem entró recién, para marcarlo en la lista de rondas al re-renderizar.
        let itemNuevo = null;

        /**
         * Agrega un plato y AVISA que lo hizo.
         *
         * Sin señal, el mozo no sabe si su toque hizo algo y lo repite: el plato entra
         * dos veces y se descubre cuando llega el pedido a la mesa. Por eso el botón se
         * bloquea mientras el request viaja, confirma en verde, y la fila nueva se
         * destaca un momento en la comanda de arriba.
         */
        async function agregarItem(nombre, precio, boton) {
            const mesa = mesaPorId(mesaAbierta);
            if (!mesa?.comanda_id) return;

            const original = boton ? boton.innerHTML : null;
            if (boton) {
                boton.disabled = true;
                boton.classList.add('agregando');
            }

            const data = await pedir(`/mozo/${TOKEN}/comanda/${mesa.comanda_id}/item`, {
                method: 'POST',
                body: JSON.stringify({ nombre: nombre, qty: 1, precio: precio || null }),
            });

            if (!data?.success) {
                if (boton) { boton.disabled = false; boton.classList.remove('agregando'); }
                return;
            }

            itemNuevo = data.item_id;
            aplicar(data.mesas);
            // Se olvida después del flash: si no, el refresco de cada 8s lo volvería a
            // destacar toda la noche.
            setTimeout(() => { itemNuevo = null; }, 2000);

            if (boton && document.body.contains(boton)) {
                boton.classList.remove('agregando');
                boton.classList.add('agregado');
                boton.innerHTML = `<span>✓ ${nombre}</span><span>agregado</span>`;
                setTimeout(() => {
                    boton.classList.remove('agregado');
                    boton.innerHTML = original;
                    boton.disabled = false;
                }, 1400);
            }

            // El buscador se limpia recién después de la confirmación, así el mozo ve
            // qué fue lo que agregó.
            setTimeout(() => {
                const buscador = document.getElementById('buscarPlato');
                if (buscador.value) { buscador.value = ''; renderPlatos(); }
            }, 1400);
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
                () => agregarItem(b.dataset.nombre, b.dataset.precio, b)));

            // Lo que no está en la carta igual se tiene que poder cargar: el mozo no puede
            // quedarse sin anotar algo porque el menú no lo tenga.
            libre.hidden = q.length === 0;
            if (q) {
                const texto = document.getElementById('buscarPlato').value.trim();
                libre.textContent = `Agregar "${texto}" (no está en la carta)`;
                libre.onclick = () => agregarItem(texto, null, libre);
            }
        }

        // Mismos colores y forma que las confirmaciones del panel.
        const swalConfig = {
            background: 'rgb(var(--c-elev-2))',
            color: 'rgb(var(--c-ink))',
            iconColor: 'rgb(var(--c-accent))',
            confirmButtonColor: 'rgb(var(--c-ok))',
            cancelButtonColor: 'rgb(var(--c-danger))',
        };

        function avisar(mensaje) {
            Swal.fire({ ...swalConfig, title: 'Ups', text: mensaje, icon: 'info', confirmButtonText: 'OK' });
        }

        // Se llama desde la tarjeta del tablero y desde el detalle, así que recibe la mesa
        // en vez de asumir cuál está abierta.
        async function cerrarMesa(mesaId) {
            const mesa = mesaPorId(mesaId);
            if (!mesa?.comanda_id) return;

            const { isConfirmed } = await Swal.fire({
                ...swalConfig,
                title: `¿Cerrar la mesa ${mesa.numero}?`,
                text: `${mesa.items_total} ítem(s) por ${plata(mesa.total_centavos)}. Queda libre para el próximo grupo; lo que pidieron no se borra.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar',
                // Sólo cuando se cierra desde el detalle: SweetAlert no es modal como el
                // diálogo del navegador, así que sin esto el <dialog> queda por encima y
                // el mozo no ve lo que está confirmando.
                ...(dialogo.open ? { target: dialogo } : {}),
            });

            if (!isConfirmed) return;

            const data = await pedir(`/mozo/${TOKEN}/comanda/${mesa.comanda_id}/cerrar`, { method: 'POST' });
            if (data?.success) {
                aplicar(data.mesas);
                if (dialogo.open) { dialogo.close(); mesaAbierta = null; }
            }
        }

        async function reabrirMesa(comandaId, boton) {
            if (boton) boton.disabled = true;

            const data = await pedir(`/mozo/${TOKEN}/comanda/${comandaId}/reabrir`, { method: 'POST' });

            if (data?.success) {
                aplicar(data.mesas);
            } else if (data) {
                avisar(data.message || 'No se pudo reabrir la mesa.');
                if (boton) boton.disabled = false;
            }
        }

        // ── Mesas cerradas hoy ──────────────────────────────────────────────────
        async function verCerradas() {
            const caja = document.getElementById('listaCerradas');
            caja.innerHTML = '<p class="text-text-secondary text-sm py-3">Buscando…</p>';

            const data = await pedir(`/mozo/${TOKEN}/cerradas`);
            if (!data?.success) return;

            if (!data.comandas.length) {
                caja.innerHTML = '<p class="text-text-secondary text-sm py-3">Todavía no se cerró ninguna mesa hoy.</p>';
                return;
            }

            caja.innerHTML = data.comandas.map(c => `
                <div class="cerrada-fila">
                    <button class="cerrada-cuerpo" data-detalle="${c.comanda_id}">
                        <div class="flex items-baseline gap-2">
                            <span class="text-text-primary font-bold">Mesa ${c.numero}</span>
                            <span class="text-text-secondary text-xs">${c.desde}–${c.hasta} · ${c.minutos}'</span>
                            ${c.cerrada_por === 'auto' ? '<span class="cerrada-auto">se cerró sola</span>' : ''}
                        </div>
                        <div class="text-text-secondary text-sm">${c.items_total} ítem(s) · ${plata(c.total_centavos)}</div>
                    </button>
                    ${c.se_puede_reabrir ? `<button class="mesa-card-reabrir" data-reabrir="${c.comanda_id}">↩ Reabrir</button>` : ''}
                </div>
                <div class="cerrada-detalle" id="det-${c.comanda_id}" hidden>
                    ${c.rondas.map(r => `
                        <div class="text-text-secondary text-xs mt-2">${r.hora} · ${r.del_mozo ? 'lo agregó el mozo' : 'pidieron desde la mesa'}</div>
                        ${r.items.map(i => `<div class="text-text-primary text-sm">${i.qty}× ${i.nombre}</div>`).join('')}
                    `).join('')}
                </div>
            `).join('');

            caja.querySelectorAll('[data-detalle]').forEach(b => b.addEventListener('click', () => {
                const det = document.getElementById('det-' + b.dataset.detalle);
                det.hidden = !det.hidden;
            }));
            caja.querySelectorAll('[data-reabrir]').forEach(b => b.addEventListener('click',
                () => reabrirMesa(b.dataset.reabrir, b)));
        }

        function aplicar(nuevas) {
            mesas = nuevas;
            renderGrilla();
            if (mesaAbierta !== null) renderDetalle();
            document.getElementById('actualizado').textContent =
                new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        }

        document.getElementById('cerrarMesa').addEventListener('click', () => cerrarMesa(mesaAbierta));
        document.getElementById('cerrarDetalle').addEventListener('click', () => {
            dialogo.close();
            mesaAbierta = null;
        });
        document.getElementById('btnAbrirMesa').addEventListener('click', abrirMesa);
        document.getElementById('buscarPlato').addEventListener('input', renderPlatos);
        dialogo.addEventListener('close', () => { mesaAbierta = null; });

        // Refresco automático. Polling y no una conexión abierta: en hosting compartido y
        // con el wifi de un local, un fetch cada 8s es mucho más confiable, y con veinte
        // mesas no se nota.
        setInterval(async () => {
            const data = await pedir(`/mozo/${TOKEN}/estado`);
            if (data?.success) aplicar(data.mesas);
        }, 8000);

        // Se consulta al desplegar y no en cada refresco: el mozo abre esto cuando pasa
        // algo, no lo mira todo el tiempo.
        document.getElementById('bloqueCerradas').addEventListener('toggle', (e) => {
            if (e.target.open) verCerradas();
        });

        renderGrilla();
        renderCategorias();
        renderPlatos();
    </script>
</body>
</html>
