/*
 * Demo para inmobiliarias — catálogo con buscador, ficha, chat y cartel con QR.
 *
 * Todo sale de propiedades.json; el único pedido al servidor es chat.php (el chat con IA y
 * el número de WhatsApp de soporte). Es una PRUEBA: vive entera en esta carpeta y se borra
 * borrándola.
 */
(() => {
    'use strict';

    const DATOS_URL = 'propiedades.json?v=1';
    const CHAT_URL = 'chat.php';
    const CLAVE_CHAT = 'demo-inmobiliaria-chat-v1';
    const pagina = document.body.dataset.pagina;

    const OPERACIONES = { venta: 'Venta', alquiler: 'Alquiler', temporario: 'Temporario' };
    const OPERACION_CARTEL = { venta: 'SE VENDE', alquiler: 'SE ALQUILA', temporario: 'ALQUILER TEMPORARIO' };
    const TIPOS = { departamento: 'Departamento', casa: 'Casa', ph: 'PH' };
    const COMODIDADES = {
        pileta: 'Pileta', parrilla: 'Parrilla', balcon: 'Balcón', patio: 'Patio', terraza: 'Terraza',
        jardin: 'Jardín', gimnasio: 'Gimnasio', seguridad: 'Seguridad 24 h', sum: 'SUM', lavadero: 'Lavadero',
    };
    const CARACTERISTICAS = {
        cochera: ['Con cochera', p => p.cocheras > 0],
        apto_credito: ['Apto crédito', p => !!p.caracteristicas?.apto_credito],
        mascotas: ['Acepta mascotas', p => !!p.caracteristicas?.mascotas],
        amoblado: ['Amoblado', p => !!p.caracteristicas?.amoblado],
    };
    // Ambientes: "5" significa "5 o más".
    const AMBIENTES = ['1', '2', '3', '4', '5'];
    const DORMITORIOS = [1, 2, 3, 4];
    const SINONIMOS = {
        depto: 'departamento', dpto: 'departamento', deptos: 'departamento', departamentos: 'departamento',
        casas: 'casa', alquilar: 'alquiler', alquileres: 'alquiler', comprar: 'venta', compra: 'venta',
        temporal: 'temporario', piscina: 'pileta', quincho: 'parrilla', amueblado: 'amoblado',
        mono: 'monoambiente', garage: 'cochera', garaje: 'cochera',
    };

    const svg = (cuerpo, extra = '') =>
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" ${extra}>${cuerpo}</svg>`;
    const ICONOS = {
        buscar: svg('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>'),
        filtros: svg('<path d="M4 6h16M7 12h10M10 18h4"/>'),
        ubicacion: svg('<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>'),
        chat: svg('<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>'),
        cerrar: svg('<path d="M6 6l12 12M18 6 6 18"/>'),
        enviar: svg('<path d="M5 12h14M13 6l6 6-6 6"/>'),
        izq: svg('<path d="m15 6-6 6 6 6"/>'),
        der: svg('<path d="m9 6 6 6-6 6"/>'),
        volver: svg('<path d="M19 12H5M11 6l-6 6 6 6"/>'),
        check: svg('<path d="m5 12 5 5 9-10"/>'),
        casa: svg('<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/>'),
        qr: svg('<rect x="4" y="4" width="6" height="6"/><rect x="14" y="4" width="6" height="6"/><rect x="4" y="14" width="6" height="6"/><path d="M14 14h2v2h-2zM18 18h2v2h-2zM14 18v2M18 14h2"/>'),
        imprimir: svg('<path d="M7 9V4h10v5"/><rect x="4" y="9" width="16" height="8" rx="2"/><path d="M7 14h10v6H7z"/>'),
        whatsapp: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>',
    };

    let datos = null;
    let whatsapp = null;

    // ── Utilidades ───────────────────────────────────────────────────────────

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const numero = n => new Intl.NumberFormat('es-AR').format(n);
    const normalizar = s => String(s ?? '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    const precioTexto = p => p.precio == null ? 'Consultar precio' : `${p.moneda === 'USD' ? 'USD' : '$'} ${numero(p.precio)}`;
    const periodoTexto = p => p.precio_periodo === 'mes' ? '/mes' : '';
    const ambientesTexto = p => p.ambientes === 1 ? 'Monoambiente' : `${p.ambientes} ambientes`;
    // Sin expensas aparte, la primera frase de la nota explica por qué ("Sin expensas", "incluidas"...).
    const expensasTexto = p => p.expensas ? `+ $ ${numero(p.expensas)} expensas` : (p.nota_precio || '').split('.')[0];

    const fotoUrl = (p, archivo) => `fotos/${p.slug}/${archivo}`;
    const urlFicha = (slug, extra = '') => `propiedad.html?p=${encodeURIComponent(slug)}${extra}`;
    const textoWhatsapp = p => p
        ? `Hola! Estaba probando la demo de ServiME para inmobiliarias (ficha: ${p.titulo}) y quiero saber más.`
        : 'Hola! Estaba probando la demo de ServiME para inmobiliarias y quiero saber más.';

    const guardado = {
        leer(clave) { try { return JSON.parse(sessionStorage.getItem(clave)); } catch { return null; } },
        escribir(clave, valor) { try { sessionStorage.setItem(clave, JSON.stringify(valor)); } catch { /* modo privado: no pasa nada */ } },
    };

    // Una foto que no carga se cambia por un relleno: nunca una imagen rota.
    document.addEventListener('error', e => {
        const img = e.target;
        if (img.tagName === 'IMG' && img.hasAttribute('data-foto')) {
            const relleno = document.createElement('div');
            relleno.className = 'foto-vacia';
            relleno.innerHTML = ICONOS.casa;
            img.replaceWith(relleno);
        }
    }, true);

    /** Los links de WhatsApp se arman cuando llega el número de soporte (lo da chat.php). */
    function linkWhatsapp(texto) {
        return whatsapp ? `https://wa.me/${whatsapp}?text=${encodeURIComponent(texto)}` : '/';
    }
    function actualizarWhatsapp() {
        document.querySelectorAll('a[data-wa]').forEach(a => { a.href = linkWhatsapp(a.dataset.wa); });
    }
    function cargarWhatsapp() {
        fetch(CHAT_URL, { headers: { Accept: 'application/json' } })
            .then(r => r.ok ? r.json() : null)
            .then(r => { whatsapp = r?.whatsapp || null; actualizarWhatsapp(); })
            .catch(() => { /* sin número, el botón lleva a la landing */ });
    }

    // ── Piezas comunes ───────────────────────────────────────────────────────

    function montarEncabezado(conHeader = true) {
        const inmo = datos.inmobiliaria;
        const franja = `
            <div class="demo-franja no-imprimir"><div class="contenedor">
                <span class="demo-etiqueta">DEMO</span>
                <span class="demo-etiqueta en-desarrollo">EN DESARROLLO</span>
                <a href="/">Hecho con ServiME</a>
                <p>Estamos construyendo ServiME para inmobiliarias: así verían tus clientes tus propiedades. Datos y fotos de ejemplo.</p>
            </div></div>`;
        const header = `
            <header class="inmo-header"><div class="contenedor">
                <a class="inmo-logo" href="./" aria-label="Inicio de ${esc(inmo.nombre)}">${esc(inmo.iniciales)}</a>
                <a class="inmo-nombre" href="./"><strong>${esc(inmo.nombre)}</strong><span>${esc(inmo.tagline)}</span></a>
                <a class="btn-wa-icono" data-wa="${esc(textoWhatsapp(null))}" href="/" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp">${ICONOS.whatsapp}</a>
            </div></header>`;
        document.body.insertAdjacentHTML('afterbegin', franja + (conHeader ? header : ''));
    }

    function tarjetaHTML(p) {
        const datosCortos = [
            ambientesTexto(p),
            p.dormitorios > 0 ? `${p.dormitorios} dorm.` : null,
            `${p.m2_cubiertos} m²`,
            p.cocheras > 0 ? 'Cochera' : null,
        ].filter(Boolean);

        return `
            <a class="tarjeta" href="${urlFicha(p.slug)}">
                <div class="tarjeta-foto">
                    <img src="${fotoUrl(p, '1-sm.webp')}" alt="${esc(p.titulo)}" loading="lazy" data-foto>
                    <span class="insignia">${OPERACIONES[p.operacion]}</span>
                </div>
                <div class="tarjeta-cuerpo">
                    <div class="tarjeta-precio">${esc(precioTexto(p))} <small>${periodoTexto(p)}</small></div>
                    <div class="tarjeta-expensas">${esc(expensasTexto(p))}</div>
                    <h3 class="tarjeta-titulo">${esc(p.titulo)}</h3>
                    <div class="tarjeta-datos">${datosCortos.map(d => `<span>${esc(d)}</span>`).join('')}</div>
                    <div class="tarjeta-barrio">${ICONOS.ubicacion}${esc(p.barrio)}, ${esc(p.localidad)}</div>
                </div>
            </a>`;
    }

    // ── Catálogo y buscador ─────────────────────────────────────────────────

    const estadoInicial = () => ({
        texto: '', operacion: '', tipos: new Set(), ambientes: new Set(), dormMin: 0,
        moneda: 'USD', precioMin: '', precioMax: '', barrio: '',
        caracteristicas: new Set(), comodidades: new Set(), orden: 'relevancia',
    });
    let estado = estadoInicial();

    /** Cada filtro activo es un criterio. Sirve para filtrar y para puntuar las "más parecidas". */
    function criterios() {
        const c = [];
        const palabras = normalizar(estado.texto).split(/\s+/).filter(w => w.length > 1).map(w => SINONIMOS[w] || w);

        if (palabras.length) {
            c.push(p => {
                const pajar = normalizar([
                    p.titulo, p.barrio, p.localidad, TIPOS[p.tipo], OPERACIONES[p.operacion], p.descripcion,
                    ambientesTexto(p), `${p.ambientes} ambientes`, p.cocheras > 0 ? 'cochera' : '',
                    ...(p.amenities || []).map(a => COMODIDADES[a] || a),
                    ...Object.entries(CARACTERISTICAS).filter(([, [, ok]]) => ok(p)).map(([, [n]]) => n),
                ].join(' '));
                return palabras.every(w => pajar.includes(w) || (w.endsWith('s') && pajar.includes(w.slice(0, -1))));
            });
        }
        if (estado.operacion) c.push(p => p.operacion === estado.operacion);
        if (estado.tipos.size) c.push(p => estado.tipos.has(p.tipo));
        if (estado.ambientes.size) c.push(p => [...estado.ambientes].some(a => a === '5' ? p.ambientes >= 5 : p.ambientes === Number(a)));
        if (estado.dormMin) c.push(p => p.dormitorios >= estado.dormMin);
        if (estado.precioMin !== '' || estado.precioMax !== '') {
            const min = estado.precioMin === '' ? 0 : Number(estado.precioMin);
            const max = estado.precioMax === '' ? Infinity : Number(estado.precioMax);
            // Las monedas no se convierten: un filtro en USD solo mira propiedades en USD.
            c.push(p => p.moneda === estado.moneda && p.precio != null && p.precio >= min && p.precio <= max);
        }
        if (estado.barrio) c.push(p => p.barrio === estado.barrio);
        estado.caracteristicas.forEach(k => c.push(CARACTERISTICAS[k][1]));
        estado.comodidades.forEach(k => c.push(p => (p.amenities || []).includes(k)));

        return c;
    }

    function ordenar(lista) {
        const copia = [...lista];
        // Para ordenar por precio se agrupa por moneda (primero USD): comparar dólares con pesos no tiene sentido.
        const porPrecio = signo => (a, b) => (a.moneda === b.moneda ? signo * ((a.precio ?? 0) - (b.precio ?? 0)) : (a.moneda === 'USD' ? -1 : 1));
        if (estado.orden === 'precio-asc') copia.sort(porPrecio(1));
        if (estado.orden === 'precio-desc') copia.sort(porPrecio(-1));
        if (estado.orden === 'm2') copia.sort((a, b) => b.m2_cubiertos - a.m2_cubiertos);
        return copia;
    }

    function cantidadFiltrosPanel() {
        return estado.tipos.size + estado.ambientes.size + (estado.dormMin ? 1 : 0)
            + (estado.precioMin !== '' || estado.precioMax !== '' ? 1 : 0) + (estado.barrio ? 1 : 0)
            + estado.caracteristicas.size + estado.comodidades.size;
    }

    function renderCatalogo() {
        const inmo = datos.inmobiliaria;
        const barrios = [...new Set(datos.propiedades.map(p => p.barrio))].sort();
        const chip = (grupo, valor, texto) => `<button type="button" class="chip" data-grupo="${grupo}" data-valor="${esc(valor)}" aria-pressed="false">${esc(texto)}</button>`;

        document.getElementById('app').innerHTML = `
            <section class="hero">
                <h1>Encontrá tu próximo lugar</h1>
                <p>Venta, alquiler y temporarios de ${esc(inmo.nombre)}. Buscá, filtrá o preguntale a ${esc(inmo.asistente)}.</p>
            </section>

            <section class="buscador" aria-label="Buscador de propiedades">
                <label class="campo-busqueda">
                    <span class="sr-only">Buscar</span>
                    ${ICONOS.buscar}
                    <input type="search" id="busqueda" placeholder="Barrio, tipo, “pileta”, “3 ambientes”…" autocomplete="off" enterkeyhint="search">
                </label>
                <div class="fila-buscador">
                    <div class="segmentado" role="group" aria-label="Operación">
                        <button type="button" data-operacion="" aria-pressed="true">Todas</button>
                        ${Object.entries(OPERACIONES).map(([k, v]) => `<button type="button" data-operacion="${k}" aria-pressed="false">${v}</button>`).join('')}
                    </div>
                    <button type="button" class="btn-filtros" id="btn-filtros" aria-expanded="false" aria-controls="panel-filtros">
                        ${ICONOS.filtros} Filtros <span class="contador-filtros" id="contador-filtros" hidden></span>
                    </button>
                    <select class="orden" id="orden" aria-label="Ordenar">
                        <option value="relevancia">Más relevantes</option>
                        <option value="precio-asc">Menor precio</option>
                        <option value="precio-desc">Mayor precio</option>
                        <option value="m2">Más metros</option>
                    </select>
                </div>

                <div class="panel-filtros" id="panel-filtros" hidden>
                    <div class="grupo-filtro"><h3>Tipo</h3><div class="chips">
                        ${Object.entries(TIPOS).map(([k, v]) => chip('tipos', k, v)).join('')}
                    </div></div>
                    <div class="grupo-filtro"><h3>Ambientes</h3><div class="chips">
                        ${AMBIENTES.map(a => chip('ambientes', a, a === '1' ? 'Monoambiente' : a === '5' ? '5 o más' : a)).join('')}
                    </div></div>
                    <div class="grupo-filtro"><h3>Dormitorios</h3><div class="chips">
                        ${DORMITORIOS.map(d => chip('dormMin', d, `${d} o más`)).join('')}
                    </div></div>
                    <div class="grupo-filtro"><h3>Precio</h3>
                        <div class="precio-filtro">
                            <select id="moneda" aria-label="Moneda"><option value="USD">USD</option><option value="ARS">$</option></select>
                            <input type="number" id="precio-min" inputmode="numeric" min="0" placeholder="Desde" aria-label="Precio desde">
                            <input type="number" id="precio-max" inputmode="numeric" min="0" placeholder="Hasta" aria-label="Precio hasta">
                        </div>
                    </div>
                    <div class="grupo-filtro"><h3>Barrio</h3>
                        <select id="barrio" aria-label="Barrio"><option value="">Todos los barrios</option>${barrios.map(b => `<option>${esc(b)}</option>`).join('')}</select>
                    </div>
                    <div class="grupo-filtro"><h3>Características</h3><div class="chips">
                        ${Object.entries(CARACTERISTICAS).map(([k, [n]]) => chip('caracteristicas', k, n)).join('')}
                    </div></div>
                    <div class="grupo-filtro"><h3>Comodidades</h3><div class="chips">
                        ${Object.entries(COMODIDADES).map(([k, v]) => chip('comodidades', k, v)).join('')}
                    </div></div>
                    <div class="acciones-filtros">
                        <button type="button" class="btn-texto" data-limpiar>Limpiar filtros</button>
                        <button type="button" class="btn btn-marca" id="ver-resultados">Ver propiedades</button>
                    </div>
                </div>
            </section>

            <div class="resumen-resultados" aria-live="polite">
                <span id="contador"></span>
                <button type="button" class="btn-texto" data-limpiar id="limpiar" hidden>Limpiar</button>
            </div>
            <div id="vacio"></div>
            <div class="grilla" id="grilla"></div>`;

        const $ = id => document.getElementById(id);
        const panel = $('panel-filtros');

        $('busqueda').addEventListener('input', e => { estado.texto = e.target.value; aplicar(); });
        document.querySelectorAll('[data-operacion]').forEach(b => b.addEventListener('click', () => {
            estado.operacion = b.dataset.operacion;
            aplicar();
        }));
        $('btn-filtros').addEventListener('click', () => {
            panel.hidden = !panel.hidden;
            $('btn-filtros').setAttribute('aria-expanded', String(!panel.hidden));
        });
        $('ver-resultados').addEventListener('click', () => {
            panel.hidden = true;
            $('btn-filtros').setAttribute('aria-expanded', 'false');
            $('contador').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        $('orden').addEventListener('change', e => { estado.orden = e.target.value; aplicar(); });
        $('moneda').addEventListener('change', e => { estado.moneda = e.target.value; aplicar(); });
        $('precio-min').addEventListener('input', e => { estado.precioMin = e.target.value; aplicar(); });
        $('precio-max').addEventListener('input', e => { estado.precioMax = e.target.value; aplicar(); });
        $('barrio').addEventListener('change', e => { estado.barrio = e.target.value; aplicar(); });

        panel.querySelectorAll('.chip').forEach(b => b.addEventListener('click', () => {
            const { grupo, valor } = b.dataset;
            if (grupo === 'dormMin') {
                estado.dormMin = estado.dormMin === Number(valor) ? 0 : Number(valor);
            } else {
                estado[grupo].has(valor) ? estado[grupo].delete(valor) : estado[grupo].add(valor);
            }
            aplicar();
        }));

        document.addEventListener('click', e => {
            if (e.target.closest('[data-limpiar]')) limpiar();
        });

        aplicar();
    }

    function limpiar() {
        const orden = estado.orden;
        estado = estadoInicial();
        estado.orden = orden;
        document.getElementById('busqueda').value = '';
        ['precio-min', 'precio-max'].forEach(id => { document.getElementById(id).value = ''; });
        document.getElementById('moneda').value = 'USD';
        document.getElementById('barrio').value = '';
        aplicar();
    }

    function aplicar() {
        // Botones y chips reflejan el estado.
        document.querySelectorAll('[data-operacion]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.operacion === estado.operacion)));
        document.querySelectorAll('#panel-filtros .chip').forEach(b => {
            const { grupo, valor } = b.dataset;
            const activo = grupo === 'dormMin' ? estado.dormMin === Number(valor) : estado[grupo].has(valor);
            b.setAttribute('aria-pressed', String(activo));
        });
        const enPanel = cantidadFiltrosPanel();
        const contadorFiltros = document.getElementById('contador-filtros');
        contadorFiltros.hidden = enPanel === 0;
        contadorFiltros.textContent = enPanel;

        const lista = criterios();
        const resultados = ordenar(datos.propiedades.filter(p => lista.every(ok => ok(p))));
        const hayFiltros = lista.length > 0;

        document.getElementById('limpiar').hidden = !hayFiltros;
        document.getElementById('ver-resultados').textContent = resultados.length === 1 ? 'Ver 1 propiedad' : `Ver ${resultados.length} propiedades`;
        document.getElementById('contador').innerHTML = resultados.length === 1
            ? '<strong>1</strong> propiedad'
            : `<strong>${resultados.length}</strong> propiedades`;

        const vacio = document.getElementById('vacio');
        const grilla = document.getElementById('grilla');

        if (resultados.length) {
            vacio.innerHTML = '';
            grilla.innerHTML = resultados.map(tarjetaHTML).join('');
            return;
        }

        // Sin resultados no se deja la pantalla vacía: las que más filtros cumplen.
        const parecidas = datos.propiedades
            .map(p => ({ p, puntos: lista.filter(ok => ok(p)).length }))
            .sort((a, b) => b.puntos - a.puntos)
            .slice(0, 3)
            .map(x => x.p);

        vacio.innerHTML = `
            <div class="vacio">
                <h2>Ninguna propiedad cumple todo eso</h2>
                <p>Probá sacar algún filtro, o contale a ${esc(datos.inmobiliaria.asistente)} qué buscás y te sugiere opciones.</p>
                <div class="botones">
                    <button type="button" class="btn btn-borde" data-limpiar>Limpiar filtros</button>
                    <button type="button" class="btn btn-marca" data-abrir-chat>${ICONOS.chat} Preguntarle</button>
                </div>
            </div>
            <p class="subtitulo-parecidas">Las más parecidas a lo que buscás</p>`;
        grilla.innerHTML = parecidas.map(tarjetaHTML).join('');
    }

    // ── Ficha ────────────────────────────────────────────────────────────────

    function renderFicha() {
        const parametros = new URLSearchParams(location.search);
        const p = datos.propiedades.find(x => x.slug === parametros.get('p'));
        const app = document.getElementById('app');

        if (!p) {
            app.innerHTML = `
                <a class="volver" href="./">${ICONOS.volver} Todas las propiedades</a>
                <div class="vacio"><h2>No encontramos esa propiedad</h2>
                <p>Puede que ya no esté publicada. Mirá las demás o consultanos.</p>
                <div class="botones"><a class="btn btn-marca" href="./">Ver propiedades</a></div></div>`;
            return null;
        }

        const inmo = datos.inmobiliaria;
        document.title = `${p.titulo} · ${inmo.nombre}`;
        const desdeQr = parametros.get('o') === 'qr';
        const c = p.caracteristicas || {};

        const datosFicha = [
            ['Ambientes', p.ambientes === 1 ? 'Mono' : p.ambientes],
            ['Dormitorios', p.dormitorios],
            ['Baños', p.banos],
            ['Cocheras', p.cocheras || '—'],
            ['Cubiertos', `${p.m2_cubiertos} m²`],
            ['Totales', `${p.m2_totales} m²`],
            ['Antigüedad', p.antiguedad === 0 ? 'A estrenar' : `${p.antiguedad} años`],
            ['Tipo', TIPOS[p.tipo]],
        ];
        const destacadas = [
            c.apto_credito && 'Apto crédito',
            c.mascotas && 'Acepta mascotas',
            c.amoblado && 'Amoblado',
            ...(p.amenities || []).map(a => COMODIDADES[a] || a),
        ].filter(Boolean);

        const fotos = p.fotos.map((f, i) => `
            <figure><img src="${fotoUrl(p, f)}" alt="${esc(p.titulo)} — foto ${i + 1}" ${i ? 'loading="lazy"' : ''} data-foto></figure>`).join('');
        const otras = datos.propiedades.filter(x => x.slug !== p.slug);
        const textoWa = esc(textoWhatsapp(p));

        app.innerHTML = `
            <a class="volver" href="./">${ICONOS.volver} Todas las propiedades</a>
            ${desdeQr ? `<p class="aviso-qr">${ICONOS.qr} Llegaste escaneando el cartel de esta propiedad. En la versión real, la inmobiliaria ve cuántas personas lo escanean.</p>` : ''}

            <div class="ficha">
                <div class="ficha-galeria galeria" aria-roledescription="carrusel" aria-label="Fotos">
                    <div class="galeria-pista" id="pista" tabindex="0">${fotos}</div>
                    <button type="button" class="galeria-flecha ant" data-paso="-1" aria-label="Foto anterior">${ICONOS.izq}</button>
                    <button type="button" class="galeria-flecha sig" data-paso="1" aria-label="Foto siguiente">${ICONOS.der}</button>
                    <span class="galeria-contador" id="contador-fotos">1 / ${p.fotos.length}</span>
                </div>

                <div class="ficha-lateral">
                    <div class="ficha-cabecera">
                        <div class="etiquetas"><span class="etiqueta">${OPERACIONES[p.operacion]}</span><span class="etiqueta">${TIPOS[p.tipo]}</span></div>
                        <h1>${esc(p.titulo)}</h1>
                        <div class="ficha-ubicacion">${ICONOS.ubicacion}${esc(p.barrio)}, ${esc(p.localidad)}</div>
                    </div>
                    <div class="caja-precio">
                        <div class="precio">${esc(precioTexto(p))} <small>${periodoTexto(p)}</small></div>
                        ${p.expensas ? `<div class="expensas">+ $ ${numero(p.expensas)} de expensas por mes</div>` : ''}
                        ${p.nota_precio ? `<div class="nota">${esc(p.nota_precio)}</div>` : ''}
                        <div class="botones">
                            <a class="btn btn-wa" data-wa="${textoWa}" href="/" target="_blank" rel="noopener">${ICONOS.whatsapp} Consultar por WhatsApp</a>
                            <button type="button" class="btn btn-borde" data-abrir-chat>${ICONOS.chat} Preguntarle a ${esc(inmo.asistente)}</button>
                        </div>
                        <p class="nota-demo">En la versión real, el botón abre el WhatsApp de la inmobiliaria con la consulta ya escrita. En esta demo te escribe a ServiME.</p>
                    </div>
                </div>

                <div class="ficha-detalle">
                    <div class="datos">${datosFicha.map(([k, v]) => `<div class="dato"><span>${k}</span><strong>${esc(v)}</strong></div>`).join('')}</div>
                    ${destacadas.length ? `<section class="seccion"><h2>Características</h2><div class="caracteristicas">
                        ${destacadas.map(d => `<span class="caracteristica">${ICONOS.check}${esc(d)}</span>`).join('')}
                    </div></section>` : ''}
                    <section class="seccion"><h2>Descripción</h2><p>${esc(p.descripcion)}</p></section>
                    <p class="codigo">Código ${esc(p.codigo)} · <a class="link-cartel" href="cartel.html?p=${encodeURIComponent(p.slug)}">Ver el cartel con QR de esta propiedad</a></p>
                </div>
            </div>

            <section class="otras seccion">
                <h2>Otras propiedades</h2>
                <div class="grilla">${otras.map(tarjetaHTML).join('')}</div>
            </section>

            <div class="barra-acciones">
                <a class="btn btn-wa" data-wa="${textoWa}" href="/" target="_blank" rel="noopener">${ICONOS.whatsapp} WhatsApp</a>
                <button type="button" class="btn btn-marca" data-abrir-chat>${ICONOS.chat} Preguntar</button>
            </div>`;

        document.body.classList.add('con-barra');
        montarGaleria(p.fotos.length);

        return p;
    }

    function montarGaleria(total) {
        const pista = document.getElementById('pista');
        const contador = document.getElementById('contador-fotos');
        const actual = () => Math.round(pista.scrollLeft / pista.clientWidth);

        pista.addEventListener('scroll', () => { contador.textContent = `${actual() + 1} / ${total}`; }, { passive: true });
        document.querySelectorAll('.galeria-flecha').forEach(b => b.addEventListener('click', () => {
            const destino = (actual() + Number(b.dataset.paso) + total) % total;
            pista.scrollTo({ left: destino * pista.clientWidth, behavior: 'smooth' });
        }));
        pista.addEventListener('keydown', e => {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                const destino = Math.min(total - 1, Math.max(0, actual() + (e.key === 'ArrowRight' ? 1 : -1)));
                pista.scrollTo({ left: destino * pista.clientWidth, behavior: 'smooth' });
            }
        });
    }

    // ── Chat ────────────────────────────────────────────────────────────────

    const Chat = {
        propiedad: null,
        historial: [],   // lo que se le manda al modelo: { role, content }
        registro: [],    // lo que se dibuja: { de: 'yo'|'bot', texto, slugs, wa }
        esperando: false,

        montar(propiedad) {
            this.propiedad = propiedad;
            const inmo = datos.inmobiliaria;
            const previo = guardado.leer(CLAVE_CHAT);
            if (previo && Array.isArray(previo.historial) && Array.isArray(previo.registro)) {
                this.historial = previo.historial.slice(-20);
                this.registro = previo.registro.slice(-40);
            }

            const chips = propiedad
                ? ['¿Cuánto son las expensas?', '¿Tiene cochera?', '¿Puedo ir a verla?', '¿Hay algo parecido más barato?']
                : ['Busco para alquilar', '¿Qué tienen con pileta?', 'Algo apto crédito', '¿Aceptan mascotas?', 'Tengo USD 250.000'];

            document.body.insertAdjacentHTML('beforeend', `
                <button type="button" class="chat-fab" data-abrir-chat>${ICONOS.chat} Preguntale a ${esc(inmo.asistente)}</button>
                <section class="chat-panel" id="chat-panel" hidden role="dialog" aria-modal="true" aria-label="Chat con ${esc(inmo.asistente)}">
                    <div class="chat-cabecera">
                        <span class="chat-avatar" aria-hidden="true">${esc(inmo.asistente.charAt(0))}</span>
                        <div><strong>${esc(inmo.asistente)}</strong><span>Asistente de ${esc(inmo.nombre)} · responde al instante</span></div>
                        <button type="button" class="chat-cerrar" id="chat-cerrar" aria-label="Cerrar chat">${ICONOS.cerrar}</button>
                    </div>
                    <div class="chat-mensajes" id="chat-mensajes" aria-live="polite"></div>
                    <div class="chat-chips" id="chat-chips">${chips.map(t => `<button type="button" class="chip">${esc(t)}</button>`).join('')}</div>
                    <form class="chat-form" id="chat-form">
                        <label class="sr-only" for="chat-input">Tu consulta</label>
                        <input id="chat-input" type="text" maxlength="500" autocomplete="off" placeholder="Escribí tu consulta…" enterkeyhint="send">
                        <button type="submit" id="chat-enviar" aria-label="Enviar">${ICONOS.enviar}</button>
                    </form>
                </section>`);

            document.addEventListener('click', e => {
                if (e.target.closest('[data-abrir-chat]')) this.abrir();
            });
            document.getElementById('chat-cerrar').addEventListener('click', () => this.cerrar());
            document.addEventListener('keydown', e => { if (e.key === 'Escape') this.cerrar(); });
            document.getElementById('chat-form').addEventListener('submit', e => {
                e.preventDefault();
                const input = document.getElementById('chat-input');
                this.enviar(input.value);
                input.value = '';
            });
            document.querySelectorAll('#chat-chips .chip').forEach(b => b.addEventListener('click', () => this.enviar(b.textContent)));

            this.dibujar();
        },

        abrir() {
            document.getElementById('chat-panel').hidden = false;
            document.getElementById('chat-mensajes').scrollTop = 1e9;
            // En el celular no se abre el teclado solo: taparía las sugerencias.
            if (window.matchMedia('(min-width: 700px)').matches) document.getElementById('chat-input').focus();
        },

        cerrar() {
            document.getElementById('chat-panel').hidden = true;
        },

        bienvenida() {
            const inmo = datos.inmobiliaria;
            return this.propiedad
                ? `¡Hola! Soy ${inmo.asistente} 👋 ¿Querés saber algo de este ${TIPOS[this.propiedad.tipo].toLowerCase()} en ${this.propiedad.barrio}? Preguntame lo que quieras.`
                : `¡Hola! Soy ${inmo.asistente} 👋 Contame qué estás buscando (barrio, ambientes, presupuesto) y te muestro lo que tenemos.`;
        },

        formatear(texto) {
            return esc(texto)
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .split(/\n{2,}/)
                .map(par => `<p>${par.replace(/\n/g, '<br>')}</p>`)
                .join('');
        },

        miniTarjetas(slugs) {
            const props = slugs.map(s => datos.propiedades.find(p => p.slug === s)).filter(Boolean);
            if (!props.length) return '';
            return `<div class="chat-tarjetas">${props.map(p => `
                <a class="mini-tarjeta" href="${urlFicha(p.slug)}">
                    <img src="${fotoUrl(p, '1-sm.webp')}" alt="" loading="lazy" data-foto>
                    <div><strong>${esc(precioTexto(p))} ${periodoTexto(p)}</strong><span>${esc(p.titulo)}</span><span>${esc(p.barrio)}</span></div>
                </a>`).join('')}</div>`;
        },

        dibujar() {
            const caja = document.getElementById('chat-mensajes');
            const html = [`<div class="msg bot">${this.formatear(this.bienvenida())}</div>`];

            this.registro.forEach(m => {
                html.push(`<div class="msg ${m.de === 'yo' ? 'yo' : 'bot'}">${this.formatear(m.texto)}</div>`);
                if (m.slugs?.length) html.push(this.miniTarjetas(m.slugs));
                if (m.wa) {
                    const texto = esc(textoWhatsapp(this.propiedad));
                    html.push(`<a class="btn btn-wa chat-wa" data-wa="${texto}" href="${linkWhatsapp(textoWhatsapp(this.propiedad))}" target="_blank" rel="noopener">${ICONOS.whatsapp} Escribir por WhatsApp</a>`);
                }
            });
            if (this.esperando) html.push('<div class="msg bot escribiendo" aria-label="Escribiendo"><i></i><i></i><i></i></div>');

            caja.innerHTML = html.join('');
            caja.scrollTop = caja.scrollHeight;
            document.getElementById('chat-enviar').disabled = this.esperando;
            document.getElementById('chat-chips').hidden = this.registro.length > 0;
        },

        guardar() {
            guardado.escribir(CLAVE_CHAT, { historial: this.historial.slice(-20), registro: this.registro.slice(-40) });
        },

        async enviar(texto) {
            texto = String(texto || '').trim().slice(0, 500);
            if (!texto || this.esperando) return;

            this.abrir();
            this.registro.push({ de: 'yo', texto });
            this.esperando = true;
            this.dibujar();

            let respuesta = null;
            try {
                const r = await fetch(CHAT_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ message: texto, history: this.historial.slice(-10), slug: this.propiedad?.slug || null }),
                });
                respuesta = await r.json();
            } catch {
                respuesta = null;
            }

            this.esperando = false;
            const contenido = respuesta?.response || 'Uy, no pude responder ahora. Probá de nuevo en un ratito.';
            const slugs = Array.isArray(respuesta?.propiedades) ? respuesta.propiedades : [];
            this.registro.push({ de: 'bot', texto: contenido, slugs, wa: /whats\s?app/i.test(contenido) || !!respuesta?.tope });

            // Al modelo solo se le devuelve lo que de verdad se conversó, no los mensajes de error.
            if (respuesta?.response && !respuesta?.tope) {
                this.historial.push({ role: 'user', content: texto }, { role: 'assistant', content: contenido });
            }

            this.guardar();
            this.dibujar();
        },
    };

    // ── Cartel con QR ────────────────────────────────────────────────────────

    function renderCartel() {
        const parametros = new URLSearchParams(location.search);
        const p = datos.propiedades.find(x => x.slug === parametros.get('p')) || datos.propiedades[0];
        const inmo = datos.inmobiliaria;
        document.title = `Cartel · ${p.titulo}`;

        const url = new URL(urlFicha(p.slug, '&o=qr'), document.baseURI).href;
        const enLocal = ['localhost', '127.0.0.1', '::1'].includes(location.hostname);

        document.getElementById('app').innerHTML = `
            <div class="cartel-herramientas no-imprimir">
                <a class="btn btn-borde" href="${urlFicha(p.slug)}">${ICONOS.volver} Volver a la ficha</a>
                <select id="elegir" aria-label="Propiedad">
                    ${datos.propiedades.map(x => `<option value="${esc(x.slug)}" ${x.slug === p.slug ? 'selected' : ''}>${esc(x.titulo)}</option>`).join('')}
                </select>
                <button type="button" class="btn btn-marca" id="imprimir">${ICONOS.imprimir} Imprimir</button>
                <p>Así sería el cartel de la propiedad. Escanealo con el celular: abre la ficha.${enLocal
                    ? ' <strong>Ojo: estás en localhost y el celular no va a poder abrirlo. Entrá a la demo con la IP de tu compu (por ejemplo http://192.168.0.10:8000).</strong>'
                    : ''}</p>
            </div>
            <article class="cartel">
                <div class="cartel-operacion">${OPERACION_CARTEL[p.operacion]}</div>
                <div class="cartel-cuerpo">
                    <h2>${esc(p.titulo)}</h2>
                    <div class="cartel-barrio">${esc(p.barrio)}, ${esc(p.localidad)}</div>
                    <div class="cartel-qr" id="qr" role="img" aria-label="Código QR de la ficha"></div>
                    <div class="cartel-escanea">Escaneá para ver fotos, precio y consultar</div>
                </div>
                <div class="cartel-pie"><span class="inmo-logo">${esc(inmo.iniciales)}</span><strong>${esc(inmo.nombre)}</strong></div>
            </article>`;

        const caja = document.getElementById('qr');
        if (typeof window.qrcode === 'function') {
            const qr = window.qrcode(0, 'M');
            qr.addData(url);
            qr.make();
            caja.innerHTML = qr.createSvgTag({ cellSize: 8, margin: 2, scalable: true });
        } else {
            caja.innerHTML = `<p style="word-break: break-all">${esc(url)}</p>`;
        }

        document.getElementById('elegir').addEventListener('change', e => {
            location.search = `?p=${encodeURIComponent(e.target.value)}`;
        });
        document.getElementById('imprimir').addEventListener('click', () => window.print());
    }

    // ── Arranque ─────────────────────────────────────────────────────────────

    fetch(DATOS_URL)
        .then(r => r.json())
        .then(json => {
            datos = json;

            if (pagina === 'cartel') {
                montarEncabezado(false);
                renderCartel();
                return;
            }

            montarEncabezado(true);
            const propiedad = pagina === 'propiedad' ? renderFicha() : (renderCatalogo(), null);
            Chat.montar(propiedad);
            cargarWhatsapp();
        })
        .catch(() => {
            document.getElementById('app').innerHTML = '<div class="vacio"><h2>No se pudo cargar la demo</h2><p>Probá recargar la página.</p></div>';
        });
})();
